<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");

class ReindexHandler
{

  public static function reindex_concept($args)
  {

    if (array_key_exists('flatten', $args)) {
      require_library('FlattenHierarchies');
      $he = new FlattenHierarchies();
      $he->flatten_hierarchies_from_concept_id($taxon_concept_id);
    }
    TaxonConcept::reindex_descendants_objects($args['taxon_concept_id']);
    TaxonConcept::reindex_for_search($args['taxon_concept_id']);
    TaxonConcept::unlock_classifications_by_id($args['taxon_concept_id']);

  }

}

?>
