<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$id1 = @$argv[1];
$id2 = @$argv[2];
$confirmed = @$argv[3];

if(!$id1 || !is_numeric($id1) || !$id2 || !is_numeric($id2))
{
    echo "\n\n\tsupercede_concepts.php [id1] [id2] [confirmed]\n\n";
    exit;
}

if($confirmed == 'confirmed')
{
    TaxonConcept::supercede_by_ids($id1, $id2, true);
    echo "\nPages $id1 and $id2 have been merged to: " . min($id1, $id2)."\n\n";
}else
{
    $descendant_objects = TaxonConcept::count_descendants_objects($id1);
    $descendants = TaxonConcept::count_descendants($id1);
    echo "\n\nTaxonConcept1: $id1\n";
    echo "Descendant Objects:  $descendant_objects\n";
    echo "Descendant Concepts: $descendants\n";
    
    $descendant_objects = TaxonConcept::count_descendants_objects($id2);
    $descendants = TaxonConcept::count_descendants($id2);
    echo "\n\nTaxonConcept2: $id2\n";
    echo "Descendant Objects:  $descendant_objects\n";
    echo "Descendant Concepts: $descendants\n";
}

?>