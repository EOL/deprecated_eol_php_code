<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");

class SplitEntryHandler
{

  public static function split_entry($args)
  {

    if(!$args['hierarchy_entry_id'] || !is_numeric($args['hierarchy_entry_id']) || !$args['bad_match_hierarchy_entry_id'] || !is_numeric($args['bad_match_hierarchy_entry_id']))
    {
        throw new \Exception("split_entry.php [hierarchy_entry_id] [bad_match_hierarchy_entry_id] [confirmed]");
    }

    echo "++ Splitting HE#", $args['hierarchy_entry_id'], " from ", $args['bad_match_hierarchy_entry_id'], "\n";

    $he = HierarchyEntry::find($args['hierarchy_entry_id']);
    $bad_he = HierarchyEntry::find($args['bad_match_hierarchy_entry_id']);

    if(!$he->id || !$bad_he->id)
    {
        throw new \Exception("Invalid ID");
    }

    if($he->taxon_concept_id != $bad_he->taxon_concept_id)
    {
        throw new \Exception("The bad match ID isn't from the same concept");
    }

    if($args['confirmed'] == 'confirmed')
    {
        $user_id = 13;  # 13 is Patrick's user ID - TODO - this should be an argument.  :|
        $update_caches = true;
        echo HierarchyEntry::split_from_concept_static($args['hierarchy_entry_id'], $update_caches)."\n";
        $GLOBALS['db_connection']->query("INSERT IGNORE INTO curated_hierarchy_entry_relationships VALUES (" .$args['hierarchy_entry_id'] . ", " . $args['bad_match_hierarchy_entry_id'] . ", $user_id, 0)");

        if($args['reindex'] == 'reindex') // NOTE - this can ONLY be specified by the resque task, ATM.
        {
          require_library("SolrUpdateConceptHandler");
          SolrUpdateConceptHandler::update_concept($he->taxon_concept_id);
          TaxonConcept::unlock_classifications_by_id($bad_he->taxon_concept_id, $args['notify']);
          $tc_id = HierarchyEntry::find($args['hierarchy_entry_id'])->taxon_concept_id;
          TaxonConcept::unlock_classifications_by_id($tc_id, $args['notify']); // No need to unlock, but notify!
        }

        echo "++ Done.\n";

    }else
    {
        echo "\n\n";
        echo "Removing:\n";
        print_r($he);
        echo "Name: ".$he->name->string."\n\n";
        echo "From:\n";
        print_r($he->taxon_concept);
        
        $descendant_objects = TaxonConcept::count_descendants_objects($he->taxon_concept_id);
        echo "\n\nDescendant Objects:  $descendant_objects\n\n";
    }

  }

}

?>
