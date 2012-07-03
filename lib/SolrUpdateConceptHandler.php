<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

class SolrUpdateConceptHandler
{

  function update_concept($args)
  {

    if(!$args['taxon_concept_id'] || !is_numeric($args['taxon_concept_id']))
    {
        echo "\n\n\t#update_concept([taxon_concept_id])\n\n";
        exit;
    }

    $taxon_concept = TaxonConcept::find($args['taxon_concept_id']);
    Tasks::update_taxon_concept_names($args['taxon_concept_id']);        
    
    require_library('FlattenHierarchies');
    $he = new FlattenHierarchies();
    $he->flatten_hierarchies_from_concept_id($args['taxon_concept_id']);  // make sure hierarchy info is up-to-date
    TaxonConcept::reindex_descendants_objects($args['taxon_concept_id']); // make sure objects are indexed for display
    TaxonConcept::reindex_for_search($args['taxon_concept_id']);          // make sure objects are indexed for search

  }

}

?>
