<?php
namespace php_active_record;

class MoveEntryHandler
{
    public static function move_entry($args)
    {
        $taxon_concept_id_from = @$args['taxon_concept_id_from'];
        $hierarchy_entry_id = @$args['hierarchy_entry_id'];
        $taxon_concept_id_to = @$args['taxon_concept_id_to'];
        $bad_match_hierarchy_entry_id = @$args['bad_match_hierarchy_entry_id'];
        $confirmation = @$args['confirmed'];
        if(!$taxon_concept_id_from || !is_numeric($taxon_concept_id_from) ||
           !$hierarchy_entry_id || !is_numeric($hierarchy_entry_id) ||
           !$taxon_concept_id_to || !is_numeric($taxon_concept_id_to) ||
           !$bad_match_hierarchy_entry_id || !is_numeric($bad_match_hierarchy_entry_id))
        {
            throw new \Exception("split_concept.php [taxon_concept_id_from] [hierarchy_entry_id] [taxon_concept_id_to] [bad_match_hierarchy_entry_id] [confirmed] [reindex?]");
        }
        
        \CodeBridge::print_message("Moving HE# $hierarchy_entry_id from TC# $taxon_concept_id_from to TC# " .
            "$taxon_concept_id_to avoiding HE# $bad_match_hierarchy_entry_id");
        
        $tc_from = TaxonConcept::find($taxon_concept_id_from);
        $tc_to = TaxonConcept::find($taxon_concept_id_to);
        $he = HierarchyEntry::find($hierarchy_entry_id);
        $bad_he = HierarchyEntry::find($bad_match_hierarchy_entry_id);
        if(!$he->id || !$tc_from->id || !$tc_to->id || !$bad_he->id) throw new \Exception("Invalid ID");
        if($he->taxon_concept_id != $tc_from->id) throw new \Exception("This entry is not in the source concept");
        if($he->taxon_concept_id != $bad_he->taxon_concept_id) throw new \Exception("The bad match ID isn't from the same concept");
        
        if($confirmation == 'confirmed' || $confirmation == 'force')
        {
            if($confirmation == 'force') $force_move_if_disallowed = true;
            else $force_move_if_disallowed = false;
            $user_id = 13;  # 13 is Patrick's user ID
            
            // TODO Need to look through all the HEs in the TC we're moving *to* and cycle through them to make sure none of
            // them are blocking the move:
            foreach ($tc_to->hierarchy_entries as $tc_he)
            {
                $GLOBALS['db_connection']->query("DELETE FROM curated_hierarchy_entry_relationships
                    WHERE hierarchy_entry_id_1=$hierarchy_entry_id AND hierarchy_entry_id_2=". $tc_he->id ." AND equivalent=0");
            }
            
            $moved = HierarchyEntry::move_to_concept_static($hierarchy_entry_id, $taxon_concept_id_to, $force_move_if_disallowed, true);
            if(!$moved)
            {
                \CodeBridge::print_message("NOT ALLOWED: throwing exception");
                throw new \Exception("This move is not allowed; it would affect other hierarchies");
            }
            $GLOBALS['db_connection']->query("INSERT IGNORE INTO curated_hierarchy_entry_relationships VALUES ($hierarchy_entry_id, $bad_match_hierarchy_entry_id, $user_id, 0)");
            \CodeBridge::print_message("Done. Moved $hierarchy_entry_id to $taxon_concept_id_to");
        }else
        {
            echo "\n\nRemoving:\n";
            print_r($he);
            echo "Name: ".$he->name->string."\n\nFrom:\n";
            print_r($tc_from);
            echo "To:\n";
            print_r($tc_to);
            $descendant_objects = TaxonConcept::count_descendants_objects($tc_from->id);
            $descendants = TaxonConcept::count_descendants($tc_from->id);
            echo "\n\nTaxonConcept1: $tc_from->id\n";
            echo "Descendant Objects:  $descendant_objects\n";
            echo "Descendant Concepts: $descendants\n";
            
            $descendant_objects = TaxonConcept::count_descendants_objects($tc_to->id);
            $descendants = TaxonConcept::count_descendants($tc_to->id);
            echo "\n\nTaxonConcept1: $tc_to->id\n";
            echo "Descendant Objects:  $descendant_objects\n";
            echo "Descendant Concepts: $descendants\n";
            echo "\n\nDon't forget to solr_update_concept.php\n\n";
        }
    }
}

?>
