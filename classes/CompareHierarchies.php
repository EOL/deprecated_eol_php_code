<?php

class CompareHierarchies
{
    // number of rows from each hierarchy to compare in an iteration
    private static $iteration_size = 10000;
    
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
        
        // looking up which hierarchies might have nodes in preview mode
        // this will save time later on when we need to check published vs preview taxa
        self::lookup_preview_harvests();
        
        $hierarchies_compared = array();
        $hierarchy_lookup_ids2 = array(Hierarchy::col_2009() => 1347615);
        
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
                //if(!in_array($hierarchy2->id, array(147))) continue;
                
                if(isset($hierarchies_compared[$hierarchy1->id][$hierarchy2->id])) continue;
                
                // have the smaller hierarchy as the first parameter so the comparison will be quicker
                if($count1 < $count2)
                {
                    echo "Assigning $hierarchy1->label ($hierarchy1->id) to $hierarchy2->label ($hierarchy2->id)\n";
                    self::assign_concepts_across_hierarchies($hierarchy1, $hierarchy2);
                }else
                {
                    echo "Assigning $hierarchy2->label ($hierarchy2->id) to $hierarchy1->label ($hierarchy1->id)\n";
                    self::assign_concepts_across_hierarchies($hierarchy2, $hierarchy1);
                }
                
                $hierarchies_compared[$hierarchy1->id][$hierarchy2->id] = 1;
                $hierarchies_compared[$hierarchy2->id][$hierarchy1->id] = 1;
            }
        }
    }
    
    private static function assign_concepts_across_hierarchies($hierarchy1, $hierarchy2)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
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
        
        $mysqli->begin_transaction();
        
        $result = $mysqli->query("SELECT he1.id id1, he1.visibility_id visibility_id1, he1.taxon_concept_id tc_id1, he2.id id2, he2.visibility_id visibility_id2, he2.taxon_concept_id tc_id2, hr.score FROM hierarchy_entry_relationships hr JOIN hierarchy_entries he1 ON (hr.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (hr.hierarchy_entry_id_2=he2.id) WHERE hr.relationship='name' AND he1.hierarchy_id=$hierarchy1->id AND he1.visibility_id IN ($visible_id, $preview_id) AND he2.hierarchy_id=$hierarchy2->id AND he2.visibility_id IN ($visible_id, $preview_id) AND he1.id!=he2.id ORDER BY he1.visibility_id ASC, he2.visibility_id ASC, score DESC, id1 ASC, id2 ASC");
        
        $rows = $result->num_rows;
        $row_num = 0;
        $i=0;
        while($result && $row = $result->fetch_assoc())
        {
            $row_num++;
            $id1 = $row['id1'];
            $visibility_id1 = $row['visibility_id1'];
            $tc_id1 = $row['tc_id1'];
            $id2 = $row['id2'];
            $visibility_id2 = $row['visibility_id2'];
            $tc_id2 = $row['tc_id2'];
            $score = $row['score'];
            
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
                
                if(self::concept_merger_effects_other_hierarchies($tc_id1, $tc_id2))
                {
                    echo "The merger of $id1 and $id2 (concepts $tc_id1 and $tc_id2) will cause a transitive loop\n";
                    continue;
                }
                $i++;
                //if($i%1==0) echo "supercede_by_ids($tc_id1, $tc_id2): $score. $row_num of $rows. mem: ".memory_get_usage()."\n";
                TaxonConcept::supercede_by_ids($tc_id1, $tc_id2);
                $superceded[max($tc_id1, $tc_id2)] = min($tc_id1, $tc_id2);
            }
        }
        $mysqli->end_transaction();
    }
    
    private static function lookup_preview_harvests()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $GLOBALS['hierarchy_preview_harvest_event'] = array();
        $result = $mysqli->query("SELECT hr.hierarchy_id, max(he.id) as max FROM hierarchies_resources hr JOIN harvest_events he ON (hr.resource_id=he.resource_id) GROUP BY hr.hierarchy_id");
        while($result && $row=$result->fetch_assoc())
        {
            $harvest_event = new HarvestEvent($row['max']);
            if(!$harvest_event->published_at) $GLOBALS['hierarchy_preview_harvest_event'][$row['hierarchy_id']] = $row['max'];
        }
    }
    
    private static function concept_merger_effects_other_hierarchies($tc_id1, $tc_id2)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $counts = array();
        $result = $mysqli->query("SELECT he.hierarchy_id, he.taxon_concept_id FROM hierarchy_entries he JOIN hierarchies h ON (he.hierarchy_id=h.id) WHERE he.taxon_concept_id IN ($tc_id1, $tc_id2) AND h.complete=1");
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
            $result = $mysqli->query("SELECT 1 FROM harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) JOIN hierarchy_entries he ON (t.hierarchy_entry_id=he.id) WHERE het.harvest_event_id=".$GLOBALS['hierarchy_preview_harvest_event'][$hierarchy_id]." AND he.taxon_concept_id=$taxon_concept_id AND he.hierarchy_id=$hierarchy_id LIMIT 1");
            if($result && $row=$result->fetch_assoc())
            {
                return true;
            }
        }
        
        return false;
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
        
        $start_time = microtime(true);
        
        $mysqli->begin_transaction();
        
        // get a path to a tmp file which doesn't exist yet
        $sql_filepath = Functions::temp_filepath();
        $SQL_FILE = fopen($sql_filepath, "w+");
        
        // delete all records which will conflict with this comparison session
        if($compare_to_hierarchy)
        {
            $result = $mysqli->query("SELECT 1 FROM hierarchy_entry_relationships r JOIN hierarchy_entries he1 ON (r.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (r.hierarchy_entry_id_2=he2.id) WHERE (he1.hierarchy_id=$hierarchy->id AND he2.hierarchy_id=$compare_to_hierarchy->id) OR (he2.hierarchy_id=$hierarchy->id AND he1.hierarchy_id=$compare_to_hierarchy->id) LIMIT 1");
            if($result && $row=$result->fetch_assoc())
            {
                $mysqli->query("DELETE r FROM hierarchy_entry_relationships r JOIN hierarchy_entries he1 ON (r.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (r.hierarchy_entry_id_2=he2.id) WHERE (he1.hierarchy_id=$hierarchy->id AND he2.hierarchy_id=$compare_to_hierarchy->id) OR (he2.hierarchy_id=$hierarchy->id AND he1.hierarchy_id=$compare_to_hierarchy->id)");
            }
        }else
        {
            $result = $mysqli->query("SELECT 1 FROM hierarchy_entry_relationships r JOIN hierarchy_entries he1 ON (r.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (r.hierarchy_entry_id_2=he2.id) WHERE he1.hierarchy_id=$hierarchy->id OR he2.hierarchy_id=$hierarchy->id LIMIT 1");
            if($result && $row=$result->fetch_assoc())
            {
                $mysqli->query("DELETE r FROM hierarchy_entry_relationships r JOIN hierarchy_entries he1 ON (r.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (r.hierarchy_entry_id_2=he2.id) WHERE he1.hierarchy_id=$hierarchy->id OR he2.hierarchy_id=$hierarchy->id");
            }
        }
        
        
        $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
        //$solr->optimize();
        
        $query = "{!lucene}hierarchy_id:$hierarchy->id&rows=1";
        $response = $solr->query($query);
        $total_results = $response['numFound'];
        unset($response);
        
        $searches_this_round = 0;
        static $total_searches = 0;
        
        for($i=0 ; $i<$total_results ; $i += self::$iteration_size)
        {
            $query = "{!lucene}hierarchy_id:$hierarchy->id&rows=".self::$iteration_size."&start=$i";
            
            // the global variable which will hold all mathces for this iteration
            $GLOBALS['hierarchy_entry_matches'] = array();
            
            $entries = $solr->get_results($query);
            foreach($entries as $entry)
            {
                self::compare_entry($solr, $hierarchy, $entry, $compare_to_hierarchy, $match_synonyms);
                
                $searches_this_round++;
                $total_searches++;
                if($searches_this_round % 500 == 0)
                {
                    $time = Functions::time_elapsed();
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
                        
                        fwrite($SQL_FILE, "$id1\t$id2\t'$type'\t$score\t''\n");
                    }
                }
                unset($GLOBALS['hierarchy_entry_matches']);
            }
        }
        
        fclose($SQL_FILE);
        echo 'loading data\n';
        $mysqli->load_data_infile($sql_filepath, "hierarchy_entry_relationships");
        
        // remove the tmp file
        @unlink($sql_filepath);
        
        $mysqli->end_transaction();
    }
    
    public static function compare_entry(&$solr, &$hierarchy, &$entry, &$compare_to_hierarchy = null, $match_synonyms = false)
    {
        if($entry->name)
        {
            $search_name = rawurlencode($entry->canonical_form);
            $query = "{!lucene}(canonical_form_string:\"". $search_name ."\"";
            if($match_synonyms) $query .= " OR synonym_canonical:\"". $search_name ."\"";
            $query .= ")";
            if($compare_to_hierarchy) $query .= " AND hierarchy_id:$compare_to_hierarchy->id";
            $query .= "&rows=500";
            
            $matching_entries = $solr->get_results($query);
            foreach($matching_entries as $matching_entry)
            {
                if($hierarchy->complete && $entry->hierarchy_id == $matching_entry->hierarchy_id) continue;
                
                static $total_comparisons = 0;
                static $total_matches = 0;
                static $total_bad_matches = 0;
                $total_comparisons++;
                
                $score = self::compare_hierarchy_entries($entry, $matching_entry);
                if($score) $GLOBALS['hierarchy_entry_matches'][$entry->id][$matching_entry->id] = $score;
                
                $score2 = self::compare_hierarchy_entries($matching_entry, $entry);
                if($score2) $GLOBALS['hierarchy_entry_matches'][$matching_entry->id][$entry->id] = $score2;
                
                // if($score)
                // {
                //     $total_matches++;
                //     if($total_matches % 100 == 0)
                //     {
                //         echo "Good Match $total_matches of $total_comparisons (".round(($total_matches/$total_comparisons)*100, 2)."%)<table border><tr><td valign=top>". Functions::print_pre($entry, 1) ."</td><td valign=top>". Functions::print_pre($matching_entry, 1) ."</td></tr></table><hr>\n";
                //     }
                // }elseif(!is_null($score))
                // {
                //     $total_bad_matches++;
                //     if($total_bad_matches % 10 == 0)
                //     {
                //         echo "Non-Match $total_bad_matches of $total_comparisons (".round(($total_bad_matches/$total_comparisons)*100, 2)."%)<table border><tr><td valign=top>". Functions::print_pre($entry, 1) ."</td><td valign=top>". Functions::print_pre($matching_entry, 1) ."</td></tr></table><hr>\n";
                //     }
                // }
            }
        }
    }
    
    public static function compare_hierarchy_entries($entry1, $entry2)
    {
        if($entry1->id == $entry2->id) return null;
        if(self::rank_conflict($entry1, $entry2)) return null;
        
        // viruses are a pain and will not match properly right now
        if(strtolower($entry1->kingdom) == 'virus' || strtolower($entry1->kingdom) == 'viruses') return null;
        if(strtolower($entry2->kingdom) == 'virus' || strtolower($entry2->kingdom) == 'viruses') return null;
        
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
        if($entry1->rank_id && $entry2->rank_id && $entry1->rank_id != $entry2->rank_id) return 1;
        return 0;
    }
    
    public static function compare_names(&$entry1, &$entry2)
    {
        // names are assigned and identical
        if($entry1->name && $entry2->name && $entry1->name == $entry2->name) return 1;
        
        // canonical_forms are assigned and identical
        if($entry1->canonical_form && $entry2->canonical_form && $entry1->canonical_form == $entry2->canonical_form) return .5;
        
        return 0;
    }
    
    public static function compare_synonyms(&$entry1, &$entry2)
    {
        // one name is in the other's synonym list
        if(in_array($entry1->name, $entry2->synonym)) return 1;
        if(in_array($entry2->name, $entry1->synonym)) return 1;
        
        // one canonical_form is in the other's synonym list
        if(in_array($entry1->canonical_form, $entry2->synonym_canonical)) return .5;
        if(in_array($entry2->canonical_form, $entry1->synonym_canonical)) return .5;
        
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
            if($entry1->$rank) $entry1_without_hierarchy = false;
            if($entry2->$rank) $entry2_without_hierarchy = false;
            
            if($entry1->$rank && $entry2->$rank && $entry1->$rank == $entry2->$rank && !preg_match("/^(unassigned|not assigned)/i", $entry1->$rank))
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
            $ranks_matched_at_kingdom = array(Rank::insert('kingdom'), Rank::insert('phylum'), Rank::insert('class'), Rank::insert('order'));
            
            // fail if the match is kingdom and we have something at a lower rank
            if(!(in_array($entry1->rank_id, $ranks_matched_at_kingdom) || in_array($entry2->rank_id, $ranks_matched_at_kingdom)) &&
                ($entry1->rank_id == $entry2->rank_id || !$entry1->rank_id || !$entry2->rank_id)) $score = 0;
        }
        
        return $score;
    }
}

?>