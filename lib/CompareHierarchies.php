<?php

class CompareHierarchies
{
    // number of rows from each hierarchy to compare in an iteration
    private static $solr_iteration_size = 10000;
    
    // weights
    private static $rank_priority = array(
                            'family'    => 1,
                            'order'     => .8,
                            'class'     => .6,
                            'phylum'    => .4,
                            'kingdom'   => .2);
    
    
    
    // this method will use its own transactions so commit any open transactions before using
    public static function begin_concept_assignment($hierarchy_id = null)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries')) return false;
        
        // looking up which hierarchies might have nodes in preview mode
        // this will save time later on when we need to check published vs preview taxa
        self::lookup_preview_harvests();
        $confirmed_exclusions = self::check_curator_assertions();
        
        $hierarchies_compared = array();
        if($default_id = Hierarchy::default_id())
        {
            $default_hierarchy = new Hierarchy($default_id);
            if(@$default_hierarchy->id) $hierarchy_lookup_ids2 = array($default_id => 1347615);
        }
        
        $hierarchy_lookup_ids2 = array();
        $result = $mysqli->query("SELECT h.id, count(*) as count  FROM hierarchies h JOIN hierarchy_entries he ON (h.id=he.hierarchy_id) GROUP BY h.id ORDER BY count(*) ASC");
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_lookup_ids2[$row['id']] = $row['count'];
        }
        
        // if the function is passed a hierarchy_id then make the first loop just that hierarchy
        // otherwise make the first loop the same as the inner loop - compare everything with everything else
        if($hierarchy_id)
        {
            $hierarchy1 = new Hierarchy($hierarchy_id);
            $count1 = $hierarchy1->count_entries();
            $hierarchy_lookup_ids1[$hierarchy_id] = $count1;
        }else $hierarchy_lookup_ids1 = $hierarchy_lookup_ids2;
        
        foreach($hierarchy_lookup_ids1 as $id1 => $count1)
        {
            $hierarchy1 = new Hierarchy($id1);
            
            foreach($hierarchy_lookup_ids2 as $id2 => $count2)
            {
                $hierarchy2 = new Hierarchy($id2);
                
                // already compared - skip
                if(isset($hierarchies_compared[$hierarchy1->id][$hierarchy2->id])) continue;
                
                // have the smaller hierarchy as the first parameter so the comparison will be quicker
                if($count1 < $count2)
                {
                    debug("Assigning $hierarchy1->label ($hierarchy1->id) to $hierarchy2->label ($hierarchy2->id)");
                    self::assign_concepts_across_hierarchies($hierarchy1, $hierarchy2, $confirmed_exclusions);
                }else
                {
                    debug("Assigning $hierarchy2->label ($hierarchy2->id) to $hierarchy1->label ($hierarchy1->id)");
                    self::assign_concepts_across_hierarchies($hierarchy2, $hierarchy1, $confirmed_exclusions);
                }
                
                $hierarchies_compared[$hierarchy1->id][$hierarchy2->id] = 1;
                $hierarchies_compared[$hierarchy2->id][$hierarchy1->id] = 1;
            }
        }
    }
    
    public static function assign_concepts_across_hierarchies($hierarchy1, $hierarchy2, $confirmed_exclusions = array())
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        debug("Assigning $hierarchy2->label ($hierarchy2->id) to $hierarchy1->label ($hierarchy1->id)");
        
        // hierarchy is the same and its 'complete' meaning its been curated and all nodes should be different taxa
        // so there no need to compare it to itself. Other hierarchies are not 'complete' such as Flickr which
        // can have several entries for the same taxon
        if($hierarchy1->id == $hierarchy2->id && $hierarchy1->complete) return;
        
        // store all changes made this session
        $superceded = array();
        $entries_matched = array();
        $concepts_seen = array();
        
        $visible_id = Visibility::insert('visible');
        $preview_id = Visibility::insert('preview');
        
        $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entry_relationship');
        
        $main_query = "relationship:name AND hierarchy_id_1:$hierarchy1->id AND (visibility_id_1:$visible_id OR visibility_id_1:$preview_id) AND hierarchy_id_2:$hierarchy2->id AND (visibility_id_2:$visible_id OR visibility_id_2:$preview_id) AND same_concept:false&sort=visibility_id_1 asc, visibility_id_2 asc, confidence desc, hierarchy_entry_id_1 asc, hierarchy_entry_id_2 asc";
        echo $main_query . "&rows=1\n\n";
        $response = $solr->query($main_query . "&rows=1");
        $total_results = $response->numFound;
        unset($response);
        
        $mysqli->begin_transaction();
        for($i=0 ; $i<$total_results ; $i += self::$solr_iteration_size)
        {
            // the global variable which will hold all mathces for this iteration
            $GLOBALS['hierarchy_entry_matches'] = array();
            
            $this_query = $main_query . "&rows=".self::$solr_iteration_size."&start=$i";
            echo "$this_query\n\n";
            $entries = $solr->get_results($this_query);
            foreach($entries as $entry)
            {
                $id1 = $entry->hierarchy_entry_id_1[0];
                $visibility_id1 = $entry->visibility_id_1[0];
                $tc_id1 = $entry->taxon_concept_id_1[0];
                $id2 = $entry->hierarchy_entry_id_2[0];
                $visibility_id2 = $entry->visibility_id_2[0];
                $tc_id2 = $entry->taxon_concept_id_2[0];
                $score = $entry->confidence[0];
                
                // this node in hierarchy 1 has already been matched
                if($hierarchy1->complete && isset($entries_matched[$id2])) continue;
                if($hierarchy2->complete && isset($entries_matched[$id1])) continue;
                $entries_matched[$id1] = 1;
                $entries_matched[$id2] = 1;
                
                // this comparison happens here instead of the query to ensure the sorting is always the same
                // if this happened in the query and the entry was related to more than one taxa, and this function is run more than once
                // then we'll start to get huge groups of concepts - all transitively related to one another
                if($tc_id1 == $tc_id2) continue;
                
                // get all the recent supercedures withouth looking in the DB
                while(isset($superceded[$tc_id1])) $tc_id1 = $superceded[$tc_id1];
                while(isset($superceded[$tc_id2])) $tc_id2 = $superceded[$tc_id2];
                
                // if even after all recent changes we still have different concepts, merge them
                if($tc_id1 != $tc_id2)
                {
                    // compare visible entries to other published entries
                    if($hierarchy1->complete && $visibility_id1 == $visible_id && self::concept_published_in_hierarchy($tc_id2, $hierarchy1->id)) continue;
                    if($hierarchy2->complete && $visibility_id2 == $visible_id && self::concept_published_in_hierarchy($tc_id1, $hierarchy2->id)) continue;
                    
                    // compare preview entries to entries in the latest harvest events
                    if($hierarchy1->complete && $visibility_id1 == $preview_id && self::concept_preview_in_hierarchy($tc_id2, $hierarchy1->id)) continue;
                    if($hierarchy2->complete && $visibility_id2 == $preview_id && self::concept_preview_in_hierarchy($tc_id1, $hierarchy2->id)) continue;
                    
                    if(self::curators_denied_relationship($id1, $tc_id1, $id2, $tc_id2, $superceded, $confirmed_exclusions))
                    {
                        debug("The merger of $id1 and $id2 (concepts $tc_id1 and $tc_id2) has been rejected by a curator");
                        continue;
                    }
                    
                    if(self::concept_merger_effects_other_hierarchies($tc_id1, $tc_id2))
                    {
                        debug("The merger of $id1 and $id2 (concepts $tc_id1 and $tc_id2) will cause a transitive loop");
                        continue;
                    }
                    TaxonConcept::supercede_by_ids($tc_id1, $tc_id2);
                    echo "TaxonConcept::supercede_by_ids($tc_id1, $tc_id2);\n";
                    $superceded[max($tc_id1, $tc_id2)] = min($tc_id1, $tc_id2);
                    
                    static $i = 0;
                    $i++;
                    if($i%50==0) $mysqli->commit();
                }
            }
        }
        $mysqli->end_transaction();
    }
    
    private static function lookup_preview_harvests()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $GLOBALS['hierarchy_preview_harvest_event'] = array();
        $result = $mysqli->query("SELECT r.hierarchy_id, max(he.id) as max FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) GROUP BY r.hierarchy_id");
        while($result && $row=$result->fetch_assoc())
        {
            $harvest_event = new HarvestEvent($row['max']);
            if(!$harvest_event->published_at) $GLOBALS['hierarchy_preview_harvest_event'][$row['hierarchy_id']] = $row['max'];
        }
    }
    
    private static function curators_denied_relationship($id1, $tc_id1, $id2, $tc_id2, $superceded, $confirmed_exclusions = array())
    {
        if(isset($confirmed_exclusions[$id1]))
        {
            foreach($confirmed_exclusions[$id1] as $tc_id => $equivalent)
            {
                while(isset($superceded[$tc_id])) $tc_id = $superceded[$tc_id];
                if($tc_id == $tc_id2) return true;
            }
            reset($confirmed_exclusions[$id1]);
        }elseif(isset($confirmed_exclusions[$id2]))
        {
            foreach($confirmed_exclusions[$id2] as $tc_id => $equivalent)
            {
                while(isset($superceded[$tc_id])) $tc_id = $superceded[$tc_id];
                if($tc_id == $tc_id1) return true;
            }
            reset($confirmed_exclusions[$id2]);
        }
        return false;
    }
    
    private static function concept_merger_effects_other_hierarchies($tc_id1, $tc_id2)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $counts = array();
        $result = $mysqli->query("SELECT SQL_NO_CACHE he.hierarchy_id, he.taxon_concept_id FROM hierarchy_entries he JOIN hierarchies h ON (he.hierarchy_id=h.id) WHERE he.taxon_concept_id IN ($tc_id1, $tc_id2) AND h.complete=1 AND he.visibility_id=".Visibility::insert('visible'));
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_id = $row['hierarchy_id'];
            if(isset($counts[$hierarchy_id])) return true;
            $counts[$hierarchy_id] = 1;
        }
        
        return false;
    }
    
    private static function concept_published_in_hierarchy($taxon_concept_id, $hierarchy_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $result = $mysqli->query("SELECT 1 FROM hierarchy_entries WHERE taxon_concept_id=$taxon_concept_id AND hierarchy_id=$hierarchy_id AND visibility_id=".Visibility::insert('visible')." LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            return true;
        }
        return false;
    }
    
    private static function concept_preview_in_hierarchy($taxon_concept_id, $hierarchy_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        if(isset($GLOBALS['hierarchy_preview_harvest_event'][$hierarchy_id]))
        {
            $result = $mysqli->query("SELECT 1 FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) WHERE hehe.harvest_event_id=".$GLOBALS['hierarchy_preview_harvest_event'][$hierarchy_id]." AND he.taxon_concept_id=$taxon_concept_id AND he.hierarchy_id=$hierarchy_id LIMIT 1");
            if($result && $row=$result->fetch_assoc())
            {
                return true;
            }
        }
        
        return false;
    }
    
    private static function check_curator_assertions()
    {
        $assertions = array();
        $result = $GLOBALS['mysqli_connection']->query("SELECT he1.id id1, he1.taxon_concept_id tc_id1, he2.id id2, he2.taxon_concept_id tc_id2, equivalent FROM curated_hierarchy_entry_relationships cher JOIN hierarchy_entries he1 ON (cher.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (cher.hierarchy_entry_id_2=he2.id) WHERE cher.equivalent=0");
        while($result && $row=$result->fetch_assoc())
        {
            $id1 = $row['id1'];
            $id2 = $row['id2'];
            $tc_id1 = $row['tc_id1'];
            $tc_id2 = $row['tc_id2'];
            $equivalent = $row['equivalent'];
            $assertions[$id1][$tc_id2] = $equivalent;
            $assertions[$id2][$tc_id1] = $equivalent;
        }
        return $assertions;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    ////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////
    /*
        All this code below is for populating the hierarchy_entry_relationships table
    */
    
    
    
    // this method will use its own transactions so commit any open transactions before using
    public static function process_hierarchy($hierarchy, $compare_to_hierarchy = null, $match_synonyms = false)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries')) return false;
        $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
        $start_time = microtime(true);
        
        $GLOBALS['ranks_matched_at_kingdom'] = array(Rank::insert('kingdom'), Rank::insert('phylum'), Rank::insert('class'), Rank::insert('order'));
        
        $mysqli->query("CREATE TABLE IF NOT EXISTS `he_relations_tmp` (
          `hierarchy_entry_id_1` int(10) unsigned NOT NULL,
          `hierarchy_entry_id_2` int(10) unsigned NOT NULL,
          `relationship` varchar(30) NOT NULL,
          `score` double NOT NULL,
          PRIMARY KEY  (`hierarchy_entry_id_1`,`hierarchy_entry_id_2`),
          KEY `hierarchy_entry_id_2` (`hierarchy_entry_id_2`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $mysqli->delete("TRUNCATE TABLE he_relations_tmp");
        
        // get a path to a tmp file which doesn't exist yet
        $sql_filepath = temp_filepath();
        $SQL_FILE = fopen($sql_filepath, "w+");
        
        // get the record count for the loop below
        $query = "hierarchy_id:$hierarchy->id&rows=1";
        $response = $solr->query($query);
        $total_results = $response->numFound;
        unset($response);
        
        $searches_this_round = 0;
        static $total_searches = 0;
        
        for($i=0 ; $i<$total_results ; $i += self::$solr_iteration_size)
        {
            $query = "hierarchy_id:$hierarchy->id&rows=".self::$solr_iteration_size."&start=$i";
            
            // the global variable which will hold all mathces for this iteration
            $GLOBALS['hierarchy_entry_matches'] = array();
            
            $entries = $solr->get_results($query);
            foreach($entries as $entry)
            {
                self::compare_entry($solr, $hierarchy, $entry, $compare_to_hierarchy, $match_synonyms);
                
                $searches_this_round++;
                $total_searches++;
                if($searches_this_round % 100 == 0)
                {
                    $time = time_elapsed();
                    $compare_time = microtime(true) - $start_time;
                    echo "Records: $searches_this_round of $total_results ($total_searches total)<br>\n";
                    echo "Speed:   ". round($total_searches/$time, 2) ." r/s<br>\n";
                    echo "Memory:  ". memory_get_usage() ."<br>\n";
                    echo "Time:    $time s<br>\n";
                    echo "Left:    ". round(($total_results * $compare_time/$searches_this_round) - $compare_time, 2) ." s<br><br>\n\n";
                    flush();
                    @ob_flush();
                }
            }
            unset($entries);
            
            
            // the iteration produced matches
            if($GLOBALS['hierarchy_entry_matches'])
            {
                foreach($GLOBALS['hierarchy_entry_matches'] as $id1 => $arr)
                {
                    foreach($arr as $id2 => $score)
                    {
                        if($score < 0)
                        {
                            $score = abs($score);
                            $type = 'syn';
                        }else $type = 'name';
                        
                        fwrite($SQL_FILE, "$id1\t$id2\t$type\t$score\t\n");
                    }
                }
                unset($GLOBALS['hierarchy_entry_matches']);
            }
        }
        
        fclose($SQL_FILE);
        $mysqli->load_data_infile($sql_filepath, "he_relations_tmp", 'IGNORE', '', 500000);
        @unlink($sql_filepath);
        
        self::insert_curator_assertions($hierarchy);
        
        $solr_indexer = new HierarchyEntryRelationshipIndexer();
        $solr_indexer->index($hierarchy, $compare_to_hierarchy);
    }
    
    public static function compare_entry(&$solr, &$hierarchy, &$entry, &$compare_to_hierarchy = null, $match_synonyms = false)
    {
        if(isset($entry->name) && isset($entry->canonical_form))
        {
            $search_name = rawurlencode($entry->canonical_form[0]);
            $query = "(canonical_form_string:\"". $search_name ."\"";
            if($match_synonyms) $query .= " OR synonym_canonical:\"". $search_name ."\"";
            $query .= ")";
            if($compare_to_hierarchy) $query .= " AND hierarchy_id:$compare_to_hierarchy->id";
            // don't compare these hierarchies to themselves
            if($hierarchy->complete) $query .= " NOT hierarchy_id:$hierarchy->id";
            $query .= "&rows=500";
            
            $matching_entries = $solr->get_results($query);
            foreach($matching_entries as $matching_entry)
            {
                $score = self::compare_hierarchy_entries($entry, $matching_entry);
                if($score) $GLOBALS['hierarchy_entry_matches'][$entry->id[0]][$matching_entry->id[0]] = $score;
                
                $score2 = self::compare_hierarchy_entries($matching_entry, $entry);
                if($score2) $GLOBALS['hierarchy_entry_matches'][$matching_entry->id[0]][$entry->id[0]] = $score2;
            }
        }
    }
    
    public static function compare_hierarchy_entries($entry1, $entry2)
    {
        if($entry1->id[0] == $entry2->id[0]) return null;
        if(!isset($entry1->name) || !isset($entry2->name)) return null;
        if(self::rank_conflict($entry1, $entry2)) return null;
        
        // viruses are a pain and will not match properly right now
        if(isset($entry1->kingdom) && (strtolower($entry1->kingdom[0]) == 'virus' || strtolower($entry1->kingdom[0]) == 'viruses')) return null;
        if(isset($entry2->kingdom) && (strtolower($entry2->kingdom[0]) == 'virus' || strtolower($entry2->kingdom[0]) == 'viruses')) return null;
        
        $name_match = self::compare_names($entry1, $entry2);
        
        // synonym matching - cut the score in half and make it negative to show it was a synonym match
        if(!$name_match) $name_match = self::compare_synonyms($entry1, $entry2) * -1;
        
        $ancestry_match = self::compare_ancestries($entry1, $entry2);
        
        // an ancestry was empty to use name match only
        if(is_null($ancestry_match)) $total_score = $name_match * .5;
        
        // ancestry match was at a resonable rank, weight scores
        elseif($ancestry_match) $total_score = $name_match * $ancestry_match;
        
        // ancestries did not match at all therefore the match fails
        else $total_score = 0;
        
        return $total_score;
    }
    
    public static function rank_conflict(&$entry1, &$entry2)
    {
        // the ranks are not the same
        if($entry1->rank_id[0] && $entry2->rank_id[0] && $entry1->rank_id[0] != $entry2->rank_id[0]) return 1;
        return 0;
    }
    
    public static function compare_names(&$entry1, &$entry2)
    {
        // names are assigned and identical
        if($entry1->name[0] && $entry2->name[0] && $entry1->name[0] == $entry2->name[0]) return 1;
        
        // canonical_forms are assigned and identical
        if($entry1->canonical_form[0] && $entry2->canonical_form[0] && $entry1->canonical_form[0] == $entry2->canonical_form[0]) return .5;
        
        return 0;
    }
    
    public static function compare_synonyms(&$entry1, &$entry2)
    {
        // one name is in the other's synonym list
        if(isset($entry2->synonym) && in_array($entry1->name[0], $entry2->synonym)) return 1;
        if(isset($entry1->synonym) && in_array($entry2->name[0], $entry1->synonym)) return 1;
        
        // one canonical_form is in the other's synonym list
        if(isset($entry2->synonym_canonical) && in_array($entry1->canonical_form[0], $entry2->synonym_canonical)) return .5;
        if(isset($entry1->synonym_canonical) && in_array($entry2->canonical_form[0], $entry1->synonym_canonical)) return .5;
        
        return 0;
    }
    
    public static function compare_ancestries(&$entry1, &$entry2)
    {
        // check each rank in order of priority and return the respective weight on match
        $score = 0;
        $entry1_without_hierarchy = true;
        $entry2_without_hierarchy = true;
        foreach(self::$rank_priority as $rank => $weight)
        {
            $rank1 = null;
            $rank2 = null;
            if(isset($entry1->$rank) && $r = $entry1->$rank) $rank1 = $r[0];
            if(isset($entry2->$rank) && $r = $entry2->$rank) $rank2 = $r[0];
            if($rank1) $entry1_without_hierarchy = false;
            if($rank2) $entry2_without_hierarchy = false;
            
            if($rank1 && $rank2 && $rank1 == $rank2 && !preg_match("/^(unassigned|not assigned|unknown)/i", $rank1))
            {
                $score = $weight;
                break;
            }
        }
        
        // one entry has none if its ancestry filled out so disregard ancestry from comparison
        if($entry1_without_hierarchy || $entry2_without_hierarchy) return null;
        
        // matched at kingdom level. Make sure a few criteria are met before succeeding
        if($score == .2)
        {
            // fail if the match is kingdom and we have something at a lower rank
            if(!(in_array($entry1->rank_id[0], $GLOBALS['ranks_matched_at_kingdom']) || in_array($entry2->rank_id[0], $GLOBALS['ranks_matched_at_kingdom'])) &&
                ($entry1->rank_id[0] == $entry2->rank_id[0] || !$entry1->rank_id[0] || !$entry2->rank_id[0])) $score = 0;
        }
        
        return $score;
    }
    
    public static function insert_curator_assertions($hierarchy)
    {
        // entry 1 is in target hierarchy
        $outfile = $GLOBALS['mysqli_connection']->select_into_outfile("SELECT he1.id id1, he2.id id2, 'name', 1, '' FROM curated_hierarchy_entry_relationships cher JOIN hierarchy_entries he1 ON (cher.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (cher.hierarchy_entry_id_2=he2.id) WHERE cher.equivalent=1 AND he1.hierarchy_id=$hierarchy->id");
        $GLOBALS['mysqli_connection']->load_data_infile($outfile, "hierarchy_entry_relationships");
        unlink($outfile);
        
        $outfile = $GLOBALS['mysqli_connection']->select_into_outfile("SELECT he2.id id1, he1.id id2, 'name', 1, '' FROM curated_hierarchy_entry_relationships cher JOIN hierarchy_entries he1 ON (cher.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (cher.hierarchy_entry_id_2=he2.id) WHERE cher.equivalent=1 AND he1.hierarchy_id=$hierarchy->id");
        $GLOBALS['mysqli_connection']->load_data_infile($outfile, "hierarchy_entry_relationships");
        unlink($outfile);
        
        
        // entry 2 is in target hierarchy
        $outfile = $GLOBALS['mysqli_connection']->select_into_outfile("SELECT he1.id id1, he2.id id2, 'name', 1, '' FROM curated_hierarchy_entry_relationships cher JOIN hierarchy_entries he1 ON (cher.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (cher.hierarchy_entry_id_2=he2.id) WHERE cher.equivalent=1 AND he2.hierarchy_id=$hierarchy->id");
        $GLOBALS['mysqli_connection']->load_data_infile($outfile, "hierarchy_entry_relationships");
        unlink($outfile);
        
        $outfile = $GLOBALS['mysqli_connection']->select_into_outfile("SELECT he2.id id1, he1.id id2, 'name', 1, '' FROM curated_hierarchy_entry_relationships cher JOIN hierarchy_entries he1 ON (cher.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (cher.hierarchy_entry_id_2=he2.id) WHERE cher.equivalent=1 AND he2.hierarchy_id=$hierarchy->id");
        $GLOBALS['mysqli_connection']->load_data_infile($outfile, "hierarchy_entry_relationships");
        unlink($outfile);
    }
}

?>