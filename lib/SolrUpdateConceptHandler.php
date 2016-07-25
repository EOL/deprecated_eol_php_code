<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

class SolrUpdateConceptHandler
{

  // NOTE there is no "confirm" on this method, it just runs.
  public static function update_concept($taxon_concept_id)
  {

    if(!$taxon_concept_id || !is_numeric($taxon_concept_id))
    {
        echo "\n\n\t#update_concept([taxon_concept_id])\n\n";
        return false;
    }

    $taxon_concept = TaxonConcept::find($taxon_concept_id);
    Tasks::update_taxon_concept_names($taxon_concept_id);

    $he = new FlattenHierarchies();
    // I don't think we want to do this here. :S
    // $he->flatten_hierarchies_from_concept_id($taxon_concept_id);  // make sure hierarchy info is up-to-date
    TaxonConcept::reindex_descendants_objects($taxon_concept_id); // make sure objects are indexed for display
    TaxonConcept::reindex_for_search($taxon_concept_id);          // make sure objects are indexed for search

  }

}

?>
