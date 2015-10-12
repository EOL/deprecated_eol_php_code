<?php
namespace php_active_record;

class RelateHierarchies
{
    private static $rank_priority = array('family' => 1, 'order' => .8, 'class' => .6, 'phylum' => .4, 'kingdom' => .2);

    function __construct($options = array())
    {
        // default values
        if(!isset($options['hierarchy_to_compare_against'])) $options['hierarchy_to_compare_against'] = NULL;
        if(!isset($options['hierarchy_entry_ids_to_compare'])) $options['hierarchy_entry_ids_to_compare'] = NULL;
        // validations
        if(!$options['hierarchy_to_compare']) return false;
        if(get_class($options['hierarchy_to_compare']) != 'php_active_record\Hierarchy') return false;
        if($options['hierarchy_to_compare_against'] && get_class($options['hierarchy_to_compare_against']) != 'php_active_record\Hierarchy') return false;
        if($options['hierarchy_entry_ids_to_compare'] && !is_array($options['hierarchy_entry_ids_to_compare'])) return false;
        // assignments
        $this->hierarchy_to_compare = $options['hierarchy_to_compare'];
        $this->hierarchy_to_compare_against = $options['hierarchy_to_compare_against'];
        $this->hierarchy_entry_ids_to_compare = $options['hierarchy_entry_ids_to_compare'];
        $this->use_synonyms_for_matching = true;

        $this->mysqli =& $GLOBALS['mysqli_connection'];
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries')) return false;
        $this->solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');

        $this->set_ranks_matched_at_kingdom();
        $this->set_rank_comparison_array();
        $this->set_hierarchy_ids_with_good_synonymies();
    }

    // this method will use its own transactions so commit any open transactions before using
    public function process_hierarchy()
    {
        if(!$this->hierarchy_to_compare ) return false;
        if (!$this->solr)
        {
          debug("SOLR SERVER not defined or can't ping hierarchy_entries, can't begin Relate Hierarchies!");
         return false;
        }
        $this->time_comparisons_started = microtime(true);
        $this->total_entry_comparisons = 0;
        $this->create_temp_file_for_relationships();

        if($this->hierarchy_entry_ids_to_compare)
        {
            $this->iterate_through_selected_entries();
        } else
        {
            $this->iterate_through_entire_hierarchy();
        }
        $this->finalize_process();
    }

    private function iterate_through_entire_hierarchy()
    {
        debug("RelateHierarchies::iterate_through_entire_hierarchy {$this->hierarchy_to_compare->id}");
        // get the record count for the loop below
        $query = "hierarchy_id:". $this->hierarchy_to_compare->id ."&rows=1";
        $response = $this->solr->query($query);
        $this->total_comparisons_to_be_made = $response->numFound;
        unset($response);

        $iteration_size = 10000;
        for($i=0 ; $i<$this->total_comparisons_to_be_made ; $i += $iteration_size)
        {
            $query = "hierarchy_id:". $this->hierarchy_to_compare->id ."&rows=$iteration_size&start=$i";
            $entries_from_solr = $this->solr->get_results($query);
            $this->iterate_through_entries($entries_from_solr);
        }
    }

    private function iterate_through_selected_entries()
    {
        // this size is much smaller than above as we need to take all these IDs and send them in a single URL
        $iteration_size = 200;
        $batches = array_chunk($this->hierarchy_entry_ids_to_compare, $iteration_size);
        $this->total_comparisons_to_be_made = count($this->hierarchy_entry_ids_to_compare);
         debug("++ START RelateHierarchies::iterate_through_selected_entries ($this->hierarchy_entry_ids_to_compare), $this->total_comparisons_to_be_made entries");
         $batch_no = 1;
        foreach($batches as $batch)
        {
            $query = "hierarchy_id:". $this->hierarchy_to_compare->id ." AND id:(". implode(" OR ", $batch) .")&rows=$iteration_size";
            $entries_from_solr = $this->solr->get_results($query);
            $count = count($entries_from_solr);
            debug("RelateHierarchies::iterate_through_entries , batch:$batch_no, no_of_entries:$count");
            $this->iterate_through_entries($entries_from_solr);
            $batch_no++;
        }
        debug("-- END RelateHierarchies::iterate_through_selected_entries");
    }

    private function iterate_through_entries(&$entries_from_solr)
    {
        $this->hierarchy_entry_matches = array();
        foreach($entries_from_solr as $entry_from_solr)
        {
            $this->start_comparing_entry_from_solr($entry_from_solr);
        }
        unset($entry_from_solr);
        unset($entries_from_solr);

        $this->write_relationships_to_temp_file();
        unset($this->hierarchy_entry_matches);
    }

    private function start_comparing_entry_from_solr(&$entry_from_solr)
    {
        if(@!$entry_from_solr->rank_id) $entry_from_solr->rank_id = 0;
        if(isset($entry_from_solr->name))
        {
            $search_name = rawurlencode($entry_from_solr->name);
            # TODO: what about subgenera?
            if(Name::is_surrogate($entry_from_solr->name)) $search_canonical = "";
            elseif(isset($entry_from_solr->kingdom) && (strtolower($entry_from_solr->kingdom) == 'virus' || strtolower($entry_from_solr->kingdom) == 'viruses')) $search_canonical = "";
            elseif($canonical_form_string = @$entry_from_solr->canonical_form) $search_canonical = rawurlencode($canonical_form_string);
            else $search_canonical = "";
            if(preg_match("/virus$/", $search_canonical)) $search_canonical = "";

            $query_bits = array();
            if($search_canonical) $query_bits[] = "canonical_form_string:\"$search_canonical\"";
            $query_bits[] = "name:\"$search_name\"";
            if($this->use_synonyms_for_matching && $search_canonical) $query_bits[] = "synonym_canonical:\"$search_canonical\"";
            $query = "(". implode(" OR ", $query_bits) .")";

            if($this->hierarchy_to_compare_against) $query .= " AND hierarchy_id:". $this->hierarchy_to_compare_against->id;
            // don't compare these hierarchies to themselves
            if($this->hierarchy_to_compare->complete) $query .= " NOT hierarchy_id:". $this->hierarchy_to_compare->id;
            // don't relate NCBI to itself
            if($this->hierarchy_to_compare->id == 1172) $query .= " NOT hierarchy_id:759";
            if($this->hierarchy_to_compare->id == 759) $query .= " NOT hierarchy_id:1172";
            $query .= "&rows=400";

            $matching_entries_from_solr = $this->solr->get_results($query);
            foreach($matching_entries_from_solr as $matching_entry_from_solr)
            {
                if(@!$matching_entry_from_solr->rank_id) $matching_entry_from_solr->rank_id = 0;

                $score = $this->compare_entries_from_solr($entry_from_solr, $matching_entry_from_solr);
                if($score) $this->hierarchy_entry_matches[$entry_from_solr->id][$matching_entry_from_solr->id] = $score;

                $score2 = $this->compare_entries_from_solr($matching_entry_from_solr, $entry_from_solr);
                if($score2) $this->hierarchy_entry_matches[$matching_entry_from_solr->id][$entry_from_solr->id] = $score2;
            }
        }

        $this->total_entry_comparisons++;
        if($this->total_entry_comparisons % 200 == 0 && $GLOBALS['ENV_DEBUG'])
        {
            $this->show_processing_stats();
        }
    }

    public function compare_entries_from_solr(&$e1, &$e2)
    {
        if($e1->id == $e2->id) return null;
        if(!isset($e1->name) || !isset($e2->name)) return null;
        if($this->hierarchy_to_compare->complete && $e1->hierarchy_id == $e2->hierarchy_id) return null;
        if($this->rank_conflict($e1, $e2)) return null;

        // viruses are a pain and will not match properly right now
        $is_virus = false;
        if(preg_match("/virus$/i", $e1->name) || preg_match("/virus$/i", $e2->name)) $is_virus = true;
        if(isset($e1->kingdom) && (strtolower($e1->kingdom) == 'virus' || strtolower($e1->kingdom) == 'viruses')) $is_virus = true;
        if(isset($e2->kingdom) && (strtolower($e2->kingdom) == 'virus' || strtolower($e2->kingdom) == 'viruses')) $is_virus = true;

        $name_match = $this->compare_names($e1, $e2, $is_virus);

        // synonym matching - make it negative to show it was a synonym match
        if(!$name_match && !$is_virus)
          $name_match = $this->compare_synonyms($e1, $e2) * -1;

        $ancestry_match = $this->compare_ancestries($e1, $e2);

        // an ancestry was empty so use name match only (at half score)
        if(is_null($ancestry_match)) $total_score = $name_match * .5;

        // ancestry match was at a resonable rank, weight scores
        elseif($ancestry_match) $total_score = $name_match * $ancestry_match;

        // ancestries did not match at all therefore the match fails
        else $total_score = 0;

        return $total_score;
    }

    private function rank_conflict(&$e1, &$e2)
    {
      // NOTE: Was the intent here an &&? As written, it will follow this branch
      // if EITHER of the entries has a rank group (which I imagine is 99.99% of
      // the time).
        if(isset($this->rank_groups[$e1->rank_id]) || isset($this->rank_groups[$e2->rank_id]))
        {
            $group1 = @$this->rank_groups[$e1->rank_id];
            $group2 = @$this->rank_groups[$e2->rank_id];
            if($e1->rank_id && $e2->rank_id && $group1 != $group2) return 1;
        }else // the rank groups are not known:
        {
            if($e1->rank_id && $e2->rank_id && $e1->rank_id != $e2->rank_id) return 1;
        }
        return 0;
    }

    private function compare_names(&$e1, &$e2, $is_virus = false)
    {
        // names are assigned and identical
        if($e1->name && $e2->name && $e1->name == $e2->name) return 1;
        // canonical_forms are assigned and identical
        if(!$is_virus && @$e1->canonical_form && @$e2->canonical_form && $e1->canonical_form == $e2->canonical_form) return .5;
        return 0;
    }

    private function compare_synonyms(&$e1, &$e2)
    {
        // Entry1's name in Entry2's synonymy
        if(isset($this->hierarchy_ids_with_good_synonymies[$e2->hierarchy_id]) &&
            isset($e2->synonym) && in_array($e1->name, $e2->synonym)) return 1;
        // Entry2's name in Entry1's synonymy
        if(isset($this->hierarchy_ids_with_good_synonymies[$e1->hierarchy_id]) &&
            isset($e1->synonym) && in_array($e2->name, $e1->synonym)) return 1;

        // Entry1's canonical_form in Entry2's synonymy
        if(isset($this->hierarchy_ids_with_good_synonymies[$e2->hierarchy_id]) &&
            isset($e2->synonym_canonical) && @in_array($e1->canonical_form, $e2->synonym_canonical)) return .5;
        // Entry2's canonical_form in Entry1's synonymy
        if(isset($this->hierarchy_ids_with_good_synonymies[$e1->hierarchy_id]) &&
            isset($e1->synonym_canonical) && @in_array($e2->canonical_form, $e1->synonym_canonical)) return .5;
        return 0;
    }

    private function compare_ancestries(&$e1, &$e2)
    {
        // check each rank in order of priority and return the respective weight on match
        $score = 0;
        $entry1_without_hierarchy = true;
        $entry2_without_hierarchy = true;
        $entry1_first_rank = null;
        $entry2_first_rank = null;

        foreach(self::$rank_priority as $rank => $weight)
        {
            $rank1 = null;
            $rank2 = null;
            if(isset($e1->$rank) && $r = $e1->$rank)
            {
                $rank1 = $r;
                if(!$entry1_first_rank) $entry1_first_rank = $rank;
            }
            if(isset($e2->$rank) && $r = $e2->$rank)
            {
                $rank2 = $r;
                if(!$entry2_first_rank) $entry2_first_rank = $rank;
            }
            if($rank1) $entry1_without_hierarchy = false;
            if($rank2) $entry2_without_hierarchy = false;

            if($rank1 && $rank2 && $rank1 == $rank2 && !preg_match("/^(unassigned|not assigned|unknown|incertae sedis)/i", $rank1))
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
            if($entry1_first_rank == 'kingdom') return .2;
            if($entry2_first_rank == 'kingdom') return .2;
            // fail if the match is kingdom and we have something at a lower rank
            $kingdom_match_valid_1 = in_array($e1->rank_id, $this->ranks_matched_at_kingdom);
            $kingdom_match_valid_2 = in_array($e2->rank_id, $this->ranks_matched_at_kingdom);
            if(!($kingdom_match_valid_1 || $kingdom_match_valid_2) &&
                ($e1->rank_id == $e2->rank_id || !$e1->rank_id || !$e2->rank_id)) $score = 0;
        }
        return $score;
    }

    private function insert_curator_assertions()
    {
        // entry 1 is in target hierarchy
        $outfile = $this->mysqli->select_into_outfile("SELECT NULL, he1.id id1, he2.id id2, 'name', 1, '' FROM curated_hierarchy_entry_relationships cher JOIN hierarchy_entries he1 ON (cher.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (cher.hierarchy_entry_id_2=he2.id) WHERE cher.equivalent=1 AND he1.hierarchy_id=". $this->hierarchy_to_compare->id);
        $GLOBALS['mysqli_connection']->load_data_infile($outfile, $this->relations_table_name);
        unlink($outfile);
        // inverse
        $outfile = $this->mysqli->select_into_outfile("SELECT NULL, he2.id id1, he1.id id2, 'name', 1, '' FROM curated_hierarchy_entry_relationships cher JOIN hierarchy_entries he1 ON (cher.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (cher.hierarchy_entry_id_2=he2.id) WHERE cher.equivalent=1 AND he1.hierarchy_id=". $this->hierarchy_to_compare->id);
        $GLOBALS['mysqli_connection']->load_data_infile($outfile, $this->relations_table_name);
        unlink($outfile);

        // entry 2 is in target hierarchy
        $outfile = $this->mysqli->select_into_outfile("SELECT NULL, he1.id id1, he2.id id2, 'name', 1, '' FROM curated_hierarchy_entry_relationships cher JOIN hierarchy_entries he1 ON (cher.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (cher.hierarchy_entry_id_2=he2.id) WHERE cher.equivalent=1 AND he2.hierarchy_id=". $this->hierarchy_to_compare->id);
        $GLOBALS['mysqli_connection']->load_data_infile($outfile, $this->relations_table_name);
        unlink($outfile);
        // inverse
        $outfile = $this->mysqli->select_into_outfile("SELECT NULL, he2.id id1, he1.id id2, 'name', 1, '' FROM curated_hierarchy_entry_relationships cher JOIN hierarchy_entries he1 ON (cher.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (cher.hierarchy_entry_id_2=he2.id) WHERE cher.equivalent=1 AND he2.hierarchy_id=". $this->hierarchy_to_compare->id);
        $GLOBALS['mysqli_connection']->load_data_infile($outfile, $this->relations_table_name);
        unlink($outfile);
    }




    private function create_temp_file_for_relationships()
    {
        $this->relationships_temp_file_path = temp_filepath();  // get a path to a tmp file which doesn't exist yet
        if(!($this->RELATIONSHIPS_FILE = fopen($this->relationships_temp_file_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->relationships_temp_file_path);
          return;
        }
    }

    private function write_relationships_to_temp_file()
    {
        foreach($this->hierarchy_entry_matches as $hierarchy_entry_id1 => $arr)
        {
            foreach($arr as $hierarchy_entry_id2 => $score)
            {
                $type = 'name';
                if($score < 0)
                {
                    $score = abs($score);
                    $type = 'syn';
                }
                fwrite($this->RELATIONSHIPS_FILE, "NULL\t$hierarchy_entry_id1\t$hierarchy_entry_id2\t$type\t$score\n");
            }
        }
    }

    private function finalize_process()
    {
        fclose($this->RELATIONSHIPS_FILE);
        $this->create_relations_temp_table();
        if($this->mysqli->load_data_infile($this->relationships_temp_file_path, $this->relations_table_name, 'IGNORE', '', 500000, 500000))
          @unlink($this->relationships_temp_file_path);

        self::insert_curator_assertions();

        $solr_indexer = new HierarchyEntryRelationshipIndexer($this->relations_table_name);
        debug("++ START HierarchyEntryRelationshipIndexer::index");
        $solr_indexer->index(array('hierarchy' => $this->hierarchy_to_compare, 'hierarchy_entry_ids' => $this->hierarchy_entry_ids_to_compare));
        debug("-- END HierarchyEntryRelationshipIndexer::index");
        $this->mysqli->delete("DROP TABLE $this->relations_table_name");
    }

    private function create_relations_temp_table()
    {
        $temp_table_index = 1;
        $relations_table_name = "he_relations_tmp_". $temp_table_index;
        while($this->mysqli->table_exists($relations_table_name))
        {
            $temp_table_index += 1;
            $relations_table_name = "he_relations_tmp_". $temp_table_index;
        }
        $this->mysqli->query("CREATE TABLE IF NOT EXISTS `$relations_table_name` (
          `id` int(10) unsigned NOT NULL auto_increment,
          `hierarchy_entry_id_1` int(10) unsigned NOT NULL,
          `hierarchy_entry_id_2` int(10) unsigned NOT NULL,
          `relationship` varchar(30) NOT NULL,
          `score` double NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE (`hierarchy_entry_id_1`,`hierarchy_entry_id_2`),
          KEY `hierarchy_entry_id_2` (`hierarchy_entry_id_2`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->mysqli->delete("TRUNCATE TABLE $relations_table_name");
        $this->relations_table_name = $relations_table_name;
    }

    // NOTE: This is broken. The end result will be ranks_matched_at_kingdom[]
    // populated with the id of the (same) kingdom rank 4 times: [183, 183, 183,
    // 183] in point of fact. ...Why is that useful? Thoughts below.
    private function set_ranks_matched_at_kingdom()
    {
        $ranks_to_lookup = array( 'kingdom', 'phylum', 'class', 'order' );
        $this->ranks_matched_at_kingdom = array();
        foreach($ranks_to_lookup as $rank_label)
        {
          // THOUGHT: Was the intent here to lookup $rank_label?! Was this
          // intended to look up ALL of the rank ids with that label?
            if($rank = Rank::find_or_create_by_translated_label('kingdom'))
            {
                $this->ranks_matched_at_kingdom[] = $rank->id;
            }
        }
    }

    private function set_rank_comparison_array()
    {
        $this->rank_groups = array();
        foreach($this->mysqli->iterate("SELECT id, rank_group_id FROM ranks WHERE rank_group_id != 0") as $row)
        {
            $this->rank_groups[$row['id']] = $row['rank_group_id'];
        }
    }

    private function set_hierarchy_ids_with_good_synonymies()
    {
        $this->hierarchy_ids_with_good_synonymies = array();
        $this->hierarchy_ids_with_good_synonymies[903] = 1; // ITIS
        $this->hierarchy_ids_with_good_synonymies[759] = 1; // NCBI
        $this->hierarchy_ids_with_good_synonymies[123] = 1; // WORMS
        $this->hierarchy_ids_with_good_synonymies[949] = 1; // COL 2012
        $this->hierarchy_ids_with_good_synonymies[787] = 1; // ReptileDB
        $this->hierarchy_ids_with_good_synonymies[622] = 1; // IUCN
        $this->hierarchy_ids_with_good_synonymies[636] = 1; // Tropicos
        $this->hierarchy_ids_with_good_synonymies[143] = 1; // Fishbase
        $this->hierarchy_ids_with_good_synonymies[860] = 1; // Avibase
    }

    private function show_processing_stats()
    {
        $processing_time_so_far = microtime(true) - $this->time_comparisons_started;
        $records_per_second = $this->total_entry_comparisons / $processing_time_so_far;
        // Memory:    ". memory_get_usage() ."
        // Time:      ". round($processing_time_so_far, 2)." s
        debug("
        SOLR comparisons:\n
        Records:   $this->total_entry_comparisons / $this->total_comparisons_to_be_made
        Time Left: " . round(($this->total_comparisons_to_be_made -
          $this->total_entry_comparisons) / $records_per_second, 2) .
          "s (@" . round($records_per_second, 2) . "/s)\n\n");
        // flush();
        // @ob_flush();
    }
}

?>
