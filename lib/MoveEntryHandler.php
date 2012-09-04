<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");

class MoveEntryHandler
{

  public static function move_entry($args)
  {

    if(!$args['taxon_concept_id_from'] || !is_numeric($args['taxon_concept_id_from']) ||
       !$args['hierarchy_entry_id'] || !is_numeric($args['hierarchy_entry_id']) ||
       !$args['taxon_concept_id_to'] || !is_numeric($args['taxon_concept_id_to']) ||
       !$args['bad_match_hierarchy_entry_id'] || !is_numeric($args['bad_match_hierarchy_entry_id']))
    {
        throw new \Exception("split_concept.php [taxon_concept_id_from] [hierarchy_entry_id] [taxon_concept_id_to] [bad_match_hierarchy_entry_id] [confirmed] [reindex?]");
    }

    echo "++ [" . date('g:i A', time()) . "] Moving HE#" . $args['hierarchy_entry_id'] . " from TC#" . $args['taxon_concept_id_from'] . " to TC#" .
      $args['taxon_concept_id_to'] . " avoiding HE#" . $args['bad_match_hierarchy_entry_id'] . "\n";

    $tc_from = TaxonConcept::find($args['taxon_concept_id_from']);
    $tc_to = TaxonConcept::find($args['taxon_concept_id_to']);
    $he = HierarchyEntry::find($args['hierarchy_entry_id']);
    $bad_he = HierarchyEntry::find($args['bad_match_hierarchy_entry_id']);
    if($args['reindex'] == 'true' || $args['reindex'] == 'reindex' || $args['reindex'] == 'update' || $args['reindex'] == 1) $args['reindex'] = true;
    else $args['reindex'] = false;

    if(!$he->id || !$tc_from->id || !$tc_to->id || !$bad_he->id)
    {
        throw new \Exception("Invalid ID");
    }

    if($he->taxon_concept_id != $tc_from->id)
    {
        throw new \Exception("This entry is not in the source concept");
    }
    if($he->taxon_concept_id != $bad_he->taxon_concept_id)
    {
        throw new \Exception("The bad match ID isn't from the same concept");
    }

    if($args['confirmed'] == 'confirmed' || $args['confirmed'] == 'force')
    {
        if($args['confirmed'] == 'force') $force_move_if_disallowed = true;
        else $force_move_if_disallowed = false;
        $update_caches = false;
        $user_id = 13;  # 13 is Patrick's user ID
        
        // TODO Need to look through all the HEs in the TC we're moving *to* and cycle through them to make sure none of
        // them are blocking the move:
        foreach ($tc_to->hierarchy_entries as $tc_he) $GLOBALS['db_connection']->query("DELETE FROM curated_hierarchy_entry_relationships WHERE hierarchy_entry_id_1=" .$args['hierarchy_entry_id'] . " AND hierarchy_entry_id_2=" . $tc_he->id . " AND equivalent=0");

        /* HierarchyEntry::move_to_concept_static(he_id, tc_id, force); */
        $moved = HierarchyEntry::move_to_concept_static($args['hierarchy_entry_id'], $args['taxon_concept_id_to'], $force_move_if_disallowed, $args['reindex']);
        throw new \Exception("This move is not allowed; it would affect other hierarchies") unless $moved;
        $GLOBALS['db_connection']->query("INSERT IGNORE INTO curated_hierarchy_entry_relationships VALUES (" . $args['hierarchy_entry_id'] . ", " . $args['bad_match_hierarchy_entry_id'] . ", $user_id, 0)");
        echo "\nMoved " . $args['hierarchy_entry_id'] . " to " . $args['taxon_concept_id_to'] . "\n\n";

        if($args['reindex_solr'] == 'reindex_solr') // NOTE - this can ONLY be specified by the resque task, ATM.
        {
          require_library("SolrUpdateConceptHandler");
          SolrUpdateConceptHandler::update_concept($args['taxon_concept_id_from']);
          SolrUpdateConceptHandler::update_concept($args['taxon_concept_id_to']);
          TaxonConcept::unlock_classifications_by_id($args['taxon_concept_id_from'], $args['notify']);
          TaxonConcept::unlock_classifications_by_id($args['taxon_concept_id_to'], $args['notify']);
        }

        echo "++ [" . date('g:i A', time()) . "] Done.\n";

    }else
    {
        echo "\n\n";
        echo "Removing:\n";
        print_r($he);
        echo "Name: ".$he->name->string."\n\n";
        echo "From:\n";
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
