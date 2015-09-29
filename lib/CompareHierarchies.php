<?php
namespace php_active_record;

class CompareHierarchies
{
    // number of rows from each hierarchy to compare in an iteration
    private static $solr_iteration_size = 10000;

    // this method will use its own transactions so commit any open transactions
    // before using
    public static function begin_concept_assignment($hierarchy_id = null, $use_synonyms_for_merging = false)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        if(!defined('SOLR_SERVER') ||
          !SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries')) {
          debug("ERROR: No Solr server; cannot begin_concept_assignment");
          return false;
        }

        // looking up which hierarchies might have nodes in preview mode
        // this will save time later on when we need to check published vs preview taxa
        self::lookup_preview_harvests();
        $confirmed_exclusions = self::check_curator_assertions();

        $hierarchies_compared = array();
        if($default_id = Hierarchy::default_id())
        {
            $default_hierarchy = Hierarchy::find($default_id);
            // TODO: This should NOT be hard-coded. ...particularly without an
            // explanation. :| ...And isn't this just ... undone as soon as we
            // leave this block by "$hierarchy_lookup_ids2 = array();"? ...This
            // seems stupid.
            if(@$default_hierarchy->id) $hierarchy_lookup_ids2 = array($default_id => 1475377);
        }

        $hierarchy_lookup_ids2 = array();
        // TODO: This is a SLOW, huge query. If we had a denormalized HE count
        // on each hierarchy (and, truly, we should), it would be super-fast. Do
        // this.
        $result = $mysqli->query("SELECT id ,hierarchy_entries_count as count FROM hierarchies ");
        while($result && $row=$result->fetch_assoc())
        {
            // TODO: DON'T hard-code this (this is GBIF Nub Taxonomy).
            // Instead, add an attribute to hierarchies called "no_comparisons"
            // and check that, and make sure the migration for that sets the
            // value of that field to true for all hierarchies with a ).
            // Also make sure curators can set that value from the resource
            // page.
            if($row['id'] == 129) continue;
            $hierarchy_lookup_ids2[$row['id']] = $row['count'];
        }
        // TODO: This is, again, a GBIF Nub taxonomy:
        $hierarchy_lookup_ids2[800] = 1;
        arsort($hierarchy_lookup_ids2);

        // if the function is passed a hierarchy_id then make the first loop
        // just that hierarchy otherwise make the first loop the same as the
        // inner loop - compare everything with everything else
        if($hierarchy_id)
        {
            $hierarchy1 = Hierarchy::find($hierarchy_id);
            // TODO: Don't we have this in $hierarchy_lookup_ids2 ?
            $count1 = $hierarchy1->count_entries();
            $hierarchy_lookup_ids1[$hierarchy_id] = $count1;
        }else $hierarchy_lookup_ids1 = $hierarchy_lookup_ids2;

        foreach($hierarchy_lookup_ids1 as $id1 => $count1)
        {
            $hierarchy1 = Hierarchy::find($id1);
            if(@!$hierarchy1->id) {
              debug("ERROR: Attempt to compare hierarchy $id1, but it was missing");
              continue;
            }

            foreach($hierarchy_lookup_ids2 as $id2 => $count2)
            {
                $hierarchy2 = Hierarchy::find($id2);
                if(@!$hierarchy2->id) {
                  debug("WARNING: Skipping compare of missing hierarchy $id2");
                  continue;
                }

                // already compared - skip
                if(isset($hierarchies_compared[$hierarchy1->id][$hierarchy2->id]))
                  continue;

                debug("Comparing hierarchy $id1 ($hierarchy1->label; $count1 entries) to $id2 ($hierarchy2->label; $count2 entries)");
                // have the smaller hierarchy as the first parameter so the
                // comparison will be quicker
                if($count1 < $count2) {
                    self::assign_concepts_across_hierarchies($hierarchy1, $hierarchy2, $confirmed_exclusions, $use_synonyms_for_merging);
                } else {
                    self::assign_concepts_across_hierarchies($hierarchy2, $hierarchy1, $confirmed_exclusions, $use_synonyms_for_merging);
                }

                $hierarchies_compared[$hierarchy1->id][$hierarchy2->id] = 1;
                $hierarchies_compared[$hierarchy2->id][$hierarchy1->id] = 1;
            }
        }
    }

    public static function assign_concepts_across_hierarchies($hierarchy1, $hierarchy2, $confirmed_exclusions = array(), $use_synonyms_for_merging = false)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        debug("Assigning concepts from $hierarchy2->label ($hierarchy2->id) to $hierarchy1->label ($hierarchy1->id)");

        // hierarchy is the same and its 'complete' meaning its been curated and
        // all nodes should be different taxa so there no need to compare it to
        // itself. Other hierarchies are not 'complete' such as Flickr which can
        // have several entries for the same taxon
        if($hierarchy1->id == $hierarchy2->id && $hierarchy1->complete)
        {
           debug("Skipping:: Hierarchies are equivilant and Complete");
          return;
        }

        // store all changes made this session
        $superceded = array();
        $entries_matched = array();
        $concepts_seen = array();

        $visible_id = Visibility::visible()->id;
        $preview_id = Visibility::preview()->id;

        $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entry_relationship');

        $main_query = "hierarchy_id_1:$hierarchy1->id AND (visibility_id_1:$visible_id OR visibility_id_1:$preview_id) AND hierarchy_id_2:$hierarchy2->id AND (visibility_id_2:$visible_id OR visibility_id_2:$preview_id) AND same_concept:false&sort=relationship asc, visibility_id_1 asc, visibility_id_2 asc, confidence desc, hierarchy_entry_id_1 asc, hierarchy_entry_id_2 asc";
        $response = $solr->query($main_query . "&rows=1");
        $total_results = $response->numFound;
        unset($response);
        debug("querying solr(hierarchy_entry_relationship), got $total_results relations..");
        $mysqli->begin_transaction();
        for($i=0 ; $i<$total_results ; $i += self::$solr_iteration_size)
        {
            // the global variable which will hold all mathces for this iteration
            $GLOBALS['hierarchy_entry_matches'] = array();

            $this_query = $main_query . "&rows=".self::$solr_iteration_size."&start=$i";
            $entries = $solr->get_results($this_query);
            foreach($entries as $entry)
            {
                if($entry->relationship == 'syn')
                {
                    if(!$use_synonyms_for_merging) continue;
                    if($entry->confidence < .25) continue;
                }

                $id1 = $entry->hierarchy_entry_id_1;
                $visibility_id1 = $entry->visibility_id_1;
                $tc_id1 = $entry->taxon_concept_id_1;
                $id2 = $entry->hierarchy_entry_id_2;
                $visibility_id2 = $entry->visibility_id_2;
                $tc_id2 = $entry->taxon_concept_id_2;
                $score = $entry->confidence;

                // this node in hierarchy 1 has already been matched
                if($hierarchy1->complete && isset($entries_matched[$id2])) continue;
                if($hierarchy2->complete && isset($entries_matched[$id1])) continue;
                $entries_matched[$id1] = 1;
                $entries_matched[$id2] = 1;

                // this comparison happens here instead of the query to ensure
                // the sorting is always the same if this happened in the query
                // and the entry was related to more than one taxa, and this
                // function is run more than once then we'll start to get huge
                // groups of concepts - all transitively related to one another
                if($tc_id1 == $tc_id2) continue;

                // get all the recent supercedures withouth looking in the DB
                while(isset($superceded[$tc_id1])) $tc_id1 = $superceded[$tc_id1];
                while(isset($superceded[$tc_id2])) $tc_id2 = $superceded[$tc_id2];
                if($tc_id1 == $tc_id2) continue;

                $tc_id1 = TaxonConcept::get_superceded_by($tc_id1);
                $tc_id2 = TaxonConcept::get_superceded_by($tc_id2);
                if($tc_id1 == $tc_id2) continue;

                // if even after all recent changes we still have different
                // concepts, merge them
                if($tc_id1 != $tc_id2)
                {
                    debug("Comparing hierarchy_entry($id1) :: hierarchy_entry($id2)");
                    // compare visible entries to other published entries
                    if($hierarchy1->complete && $visibility_id1 == $visible_id && self::concept_published_in_hierarchy($tc_id2, $hierarchy1->id)) { debug("NO: concept 2 published in hierarchy 1"); continue; }
                    if($hierarchy2->complete && $visibility_id2 == $visible_id && self::concept_published_in_hierarchy($tc_id1, $hierarchy2->id)) { debug("NO: concept 1 published in hierarchy 2"); continue; }

                    // compare preview entries to entries in the latest harvest events
                    if($hierarchy1->complete && $visibility_id1 == $preview_id && self::concept_preview_in_hierarchy($tc_id2, $hierarchy1->id)) { debug("NO: concept 2 preview in hierarchy 1"); continue; }
                    if($hierarchy2->complete && $visibility_id2 == $preview_id && self::concept_preview_in_hierarchy($tc_id1, $hierarchy2->id)) { debug("NO: concept 1 preview in hierarchy 2"); continue; }

                    if(self::curators_denied_relationship($id1, $tc_id1, $id2, $tc_id2, $superceded, $confirmed_exclusions))
                    {
                        debug("The merger of $id1 and $id2 (concepts $tc_id1 and $tc_id2) has been rejected by a curator");
                        continue;
                    }

                    if($hierarchy_id = self::concept_merger_effects_other_hierarchies($tc_id1, $tc_id2))
                    {
                        debug("The merger of $id1 and $id2 (concepts $tc_id1 and $tc_id2) is not allowed by a curated hierarchy ($hierarchy_id)");
                        continue;
                    }
                    debug("TaxonMatch::($tc_id1) = ($tc_id2)");
                    debug("TaxonConcept::supercede_by_ids($tc_id1, $tc_id2)");
                    TaxonConcept::supercede_by_ids($tc_id1, $tc_id2);
                    $superceded[max($tc_id1, $tc_id2)] = min($tc_id1, $tc_id2);

                    static $count = 0;
                    $count++;
                    if($count%50==0) $mysqli->commit();
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
            $harvest_event = HarvestEvent::find($row['max']);
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
        $result = $mysqli->query("SELECT SQL_NO_CACHE he.hierarchy_id, he.taxon_concept_id FROM hierarchy_entries he JOIN hierarchies h ON (he.hierarchy_id=h.id) WHERE he.taxon_concept_id=$tc_id1 AND h.complete=1 AND he.visibility_id=".Visibility::visible()->id);
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_id = $row['hierarchy_id'];
            $counts[$hierarchy_id] = 1;
        }
        $result = $mysqli->query("SELECT SQL_NO_CACHE he.hierarchy_id, he.taxon_concept_id FROM hierarchy_entries he JOIN hierarchies h ON (he.hierarchy_id=h.id) WHERE he.taxon_concept_id=$tc_id2 AND h.complete=1 AND he.visibility_id=".Visibility::visible()->id);
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_id = $row['hierarchy_id'];
            if(isset($counts[$hierarchy_id])) return $hierarchy_id;
        }

        return false;
    }

    private static function concept_published_in_hierarchy($taxon_concept_id, $hierarchy_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];

        $result = $mysqli->query("SELECT 1 FROM hierarchy_entries WHERE taxon_concept_id=$taxon_concept_id AND hierarchy_id=$hierarchy_id AND visibility_id=".Visibility::visible()->id." LIMIT 1");
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
}

?>
