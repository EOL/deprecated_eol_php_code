<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$hierarchy_entry_id = @$argv[1];
$bad_match_hierarchy_entry_id = @$argv[2];
$confirmed = @$argv[3];

if(!$hierarchy_entry_id || !is_numeric($hierarchy_entry_id) || !$bad_match_hierarchy_entry_id || !is_numeric($bad_match_hierarchy_entry_id))
{
    echo "\n\n\tsplit_entry.php [hierarchy_entry_id] [bad_match_hierarchy_entry_id] [confirmed]\n\n";
    exit;
}

$he = HierarchyEntry::find($hierarchy_entry_id);
$bad_he = HierarchyEntry::find($bad_match_hierarchy_entry_id);

if(!$he->id || !$bad_he->id)
{
    echo "\n\nInvalid ID\n";
    exit;
}

if($he->taxon_concept_id != $bad_he->taxon_concept_id)
{
    echo "\n\nThe bad match ID isn't from the same concept\n";
    exit;
}

if($confirmed == 'confirmed')
{
    $user_id = 13;  # 13 is Patrick's user ID
    $update_caches = true;
    echo HierarchyEntry::split_from_concept_static($hierarchy_entry_id, $update_caches)."\n";
    $GLOBALS['db_connection']->query("INSERT IGNORE INTO curated_hierarchy_entry_relationships VALUES ($hierarchy_entry_id, $bad_match_hierarchy_entry_id, $user_id, 0)");
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

?>