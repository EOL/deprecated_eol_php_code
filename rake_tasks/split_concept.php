<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$taxon_concept_id_from = @$argv[1];
$hierarchy_entry_id = @$argv[2];
$taxon_concept_id_to = @$argv[3];
$bad_match_hierarchy_entry_id = @$argv[4];
$confirmed = @$argv[5];
$reindex = @trim($argv[6]);


if(!$taxon_concept_id_from || !is_numeric($taxon_concept_id_from) ||
    !$hierarchy_entry_id || !is_numeric($hierarchy_entry_id) ||
    !$taxon_concept_id_to || !is_numeric($taxon_concept_id_to) ||
    !$bad_match_hierarchy_entry_id || !is_numeric($bad_match_hierarchy_entry_id))
{
    echo "\n\n\tsplit_concept.php [taxon_concept_id_from] [hierarchy_entry_id] [taxon_concept_id_to] [bad_match_hierarchy_entry_id] [confirmed] [reindex?]\n\n";
    exit;
}

$tc_from = TaxonConcept::find($taxon_concept_id_from);
$tc_to = TaxonConcept::find($taxon_concept_id_to);
$he = HierarchyEntry::find($hierarchy_entry_id);
$bad_he = HierarchyEntry::find($bad_match_hierarchy_entry_id);
if($reindex == 'true' || $reindex == 'reindex' || $reindex == 'update' || $reindex == 1) $reindex = true;
else $reindex = false;

if(!$he->id || !$tc_from->id || !$tc_to->id || !$bad_he->id)
{
    echo "\n\nInvalid ID\n";
    exit;
}

if($he->taxon_concept_id != $tc_from->id)
{
    echo "\n\nThis entry is not in the source concept\n";
    exit;
}
if($he->taxon_concept_id != $bad_he->taxon_concept_id)
{
    echo "\n\nThe bad match ID isn't from the same concept\n";
    exit;
}

if($confirmed == 'confirmed' || $confirmed == 'force')
{
    if($confirmed == 'force') $force_move_if_disallowed = true;
    else $force_move_if_disallowed = false;
    $update_caches = false;
    $user_id = 13;  # 13 is Patrick's user ID
    
    /* HierarchyEntry::move_to_concept_static(he_id, tc_id, force); */
    HierarchyEntry::move_to_concept_static($hierarchy_entry_id, $taxon_concept_id_to, $force_move_if_disallowed, $reindex);
    $GLOBALS['db_connection']->query("INSERT IGNORE INTO curated_hierarchy_entry_relationships VALUES ($hierarchy_entry_id, $bad_match_hierarchy_entry_id, $user_id, 0)");
    echo "\nMoved $hierarchy_entry_id to $taxon_concept_id_to\n\n";
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

?>
