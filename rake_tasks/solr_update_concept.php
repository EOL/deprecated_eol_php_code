<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$taxon_concept_id = @$argv[1];
$confirmed = @$argv[2];

if(!$taxon_concept_id || !is_numeric(str_replace(',', '', $taxon_concept_id)))
{
    echo "\n\n\tsolr_update_concept.php [taxon_concept_id] [confirmed]\n\n";
    write_to_resource_harvesting_log("solr_update_concept.php [taxon_concept_id] [confirmed]");
    exit;
}

$taxon_concept_ids = explode(",", $taxon_concept_id);



if($confirmed == 'confirmed')
{
    foreach($taxon_concept_ids as $tc_id)
    {
        $taxon_concept = TaxonConcept::find($tc_id);
        Tasks::update_taxon_concept_names(array($tc_id));

        $he = new FlattenHierarchies();
        // I don't think we want to do this here.
        // $he->flatten_hierarchies_from_concept_id($tc_id);  // make sure hierarchy info is up-to-date
        TaxonConcept::reindex_descendants_objects($tc_id); // make sure objects are indexed for display
        TaxonConcept::reindex_for_search($tc_id);          // make sure objects are indexed for search
    }
}else
{
    foreach($taxon_concept_ids as $tc_id)
    {
        $descendant_objects = TaxonConcept::count_descendants_objects($tc_id);
        $descendants = TaxonConcept::count_descendants($tc_id);
        echo "\n\nTaxonConcept: $tc_id\n";
        echo "Descendant Objects:  $descendant_objects\n";
        echo "Descendant Concepts: $descendants\n";
    }
}


?>
