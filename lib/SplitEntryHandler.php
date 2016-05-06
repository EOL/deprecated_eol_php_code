<?php
namespace php_active_record;

class SplitEntryHandler
{
    public static function split_entry($args)
    {
        $hierarchy_entry_id = @$args['hierarchy_entry_id'];
        $bad_match_hierarchy_entry_id = @$args['bad_match_hierarchy_entry_id'];
        $confirmation = @$args['confirmed'];
        if(!$hierarchy_entry_id || !is_numeric($hierarchy_entry_id) ||
           !$bad_match_hierarchy_entry_id || !is_numeric($bad_match_hierarchy_entry_id))
        {
            throw new \Exception("split_entry.php [hierarchy_entry_id] [bad_match_hierarchy_entry_id] [confirmed]");
        }

        \CodeBridge::print_message("Splitting HE# $hierarchy_entry_id from $bad_match_hierarchy_entry_id");
        $he = HierarchyEntry::find($hierarchy_entry_id);
        $bad_he = HierarchyEntry::find($hierarchy_entry_id);
        if(!$he->id || !$bad_he->id) throw new \Exception("Invalid ID");
        if($he->taxon_concept_id != $bad_he->taxon_concept_id) throw new \Exception("The bad match ID isn't from the same concept");

        if($confirmation == 'confirmed')
        {
            $user_id = 13;  # 13 is Patrick's user ID - TODO - this should be an argument.  :|
            $new_taxon_concept_id = HierarchyEntry::split_from_concept_static($hierarchy_entry_id);
            $GLOBALS['db_connection']->query("INSERT IGNORE INTO curated_hierarchy_entry_relationships (hierarchy_entry_id_1, hierarchy_entry_id_2, user_id, equivalent)
                VALUES ($hierarchy_entry_id, $bad_match_hierarchy_entry_id, $user_id, 0)");
            \CodeBridge::print_message("Done. HE# $hierarchy_entry_id was split into a new concept # $new_taxon_concept_id");
        }else
        {
            echo "\n\nIf confirmed, this would remove:\n";
            print_r($he);
            echo "Name: ".$he->name->string."\n\nFrom:\n";
            print_r($he->taxon_concept);
            $descendant_objects = TaxonConcept::count_descendants_objects($he->taxon_concept_id);
            echo "\n\nDescendant Objects:  $descendant_objects\n\n";
        }
    }
}

?>
