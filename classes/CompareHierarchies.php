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
            $result = $mysqli->query("SELECT 1 FROM new_hierarchy_entry_relationships r JOIN hierarchy_entries he1 ON (r.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (r.hierarchy_entry_id_2=he2.id) WHERE (he1.hierarchy_id=$hierarchy->id AND he2.hierarchy_id=$compare_to_hierarchy->id) OR (he2.hierarchy_id=$hierarchy->id AND he1.hierarchy_id=$compare_to_hierarchy->id) LIMIT 1");
            if($result && $row=$result->fetch_assoc())
            {
                $mysqli->query("DELETE r FROM new_hierarchy_entry_relationships r JOIN hierarchy_entries he1 ON (r.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (r.hierarchy_entry_id_2=he2.id) WHERE (he1.hierarchy_id=$hierarchy->id AND he2.hierarchy_id=$compare_to_hierarchy->id) OR (he2.hierarchy_id=$hierarchy->id AND he1.hierarchy_id=$compare_to_hierarchy->id)");
            }
        }else
        {
            $result = $mysqli->query("SELECT 1 FROM new_hierarchy_entry_relationships r JOIN hierarchy_entries he1 ON (r.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (r.hierarchy_entry_id_2=he2.id) WHERE he1.hierarchy_id=$hierarchy->id OR he2.hierarchy_id=$hierarchy->id LIMIT 1");
            if($result && $row=$result->fetch_assoc())
            {
                $mysqli->query("DELETE r FROM new_hierarchy_entry_relationships r JOIN hierarchy_entries he1 ON (r.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (r.hierarchy_entry_id_2=he2.id) WHERE he1.hierarchy_id=$hierarchy->id OR he2.hierarchy_id=$hierarchy->id");
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
                    ob_flush();
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
        $mysqli->load_data_infile($sql_filepath, "new_hierarchy_entry_relationships");
        
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