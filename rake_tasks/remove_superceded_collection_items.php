<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli =& $GLOBALS['db_connection'];


$collection_item_ids_updated = array();
$taxon_concept_ids_updated_from = array();
$taxon_concept_ids_updated_to = array();

$result = $mysqli->query("SELECT ci.id, tc.id taxon_concept_id, tc.supercedure_id
    FROM collection_items ci
    JOIN taxon_concepts tc ON (ci.collected_item_id = tc.id)
    WHERE ci.collected_item_type = 'TaxonConcept'
    AND tc.supercedure_id != 0");
    
$mysqli->begin_transaction();
while($result && $row=$result->fetch_assoc())
{
    $item_id = $row['id'];
    $taxon_concept_id = $row['taxon_concept_id'];
    $supercedure_id = TaxonConcept::get_superceded_by($row['supercedure_id']);
    
    $collection_item_ids_updated[] = $item_id;
    $taxon_concept_ids_updated_from[$taxon_concept_id] = 1;
    $taxon_concept_ids_updated_to[] = $supercedure_id;
    
    echo "UPDATE IGNORE collection_items SET collected_item_id=$supercedure_id WHERE collected_item_id=$taxon_concept_id AND collected_item_type='TaxonConcept'\n";
    $mysqli->update("UPDATE IGNORE collection_items SET collected_item_id=$supercedure_id WHERE collected_item_id=$taxon_concept_id AND collected_item_type='TaxonConcept'");
}

if($collection_item_ids_updated)
{
    foreach($taxon_concept_ids_updated_from as $taxon_concept_id => $junk)
    {
        echo "DELETE FROM collection_items WHERE collected_item_id=$taxon_concept_id AND collected_item_type='TaxonConcept'\n";
        $mysqli->update("DELETE FROM collection_items WHERE collected_item_id=$taxon_concept_id AND collected_item_type='TaxonConcept'");
    }
    $mysqli->commit();
    
    print_r($collection_item_ids_updated);
    $indexer = new CollectionItemIndexer();
    $indexer->index_collection_items($collection_item_ids_updated);
}
$mysqli->end_transaction();



?>
