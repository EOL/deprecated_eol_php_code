<?php

define('ENVIRONMENT', 'development');
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_vendor('darwincore');




$uri = '/Users/pleary/Downloads/catlife.tar.gz';
$uri = '/Users/pleary/Downloads/diatoms.tar.gz';
try
{
    $dwca = new DarwinCoreArchiveHarvester($uri);
}catch(Exception $e)
{
    var_dump($e);
    exit;
}

$taxa = $dwca->get_core_taxa();

echo $taxa[0];
















$agent_params = array(  "full_name"     => "$uri",
                        "acronym"       => "NCBI");
                            
$agent_id = Agent::insert(Functions::mock_object("Agent", $agent_params));
$agent_hierarchy_id = Hierarchy::find_by_agent_id($agent_id);
if($agent_hierarchy_id)
{
    $agent_hierarchy = new Hierarchy($agent_hierarchy_id);
    $hierarchy_group_id = $agent_hierarchy->hierarchy_group_id;
    $hierarchy_group_version = $agent_hierarchy->latest_group_version()+1;
}else
{
    $hierarchy_group_id = Hierarchy::next_group_id();
    $hierarchy_group_version = 1;
}
$hierarchy_params = array(  "label"                     => "$uri",
                            "description"               => "latest export",
                            "agent_id"                  => $agent_id,
                            "hierarchy_group_id"        => $hierarchy_group_id,
                            "hierarchy_group_version"   => $hierarchy_group_version);
$hierarchy = new Hierarchy(Hierarchy::insert(Functions::mock_object("Hierarchy", $hierarchy_params)));


$importer = new TaxonImporter($hierarchy, 5, 1);
$importer->import_taxa($taxa);








// $taxon = new DarwinCoreTaxon(array("ScientificName" => "Aus bus"));
// echo $taxon;
// 
// $taxon2 = new DarwinCoreTaxon(array("http://rs.tdwg.org/dwc/terms/ScientificName" => "Audds bddus"));
// echo $taxon2;


?>