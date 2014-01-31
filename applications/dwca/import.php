<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_vendor('darwincore');




//$uri = '/Users/pleary/Downloads/catlife.tar.gz';
$uri = 'http://gnaclr.globalnames.org/files/a9995ace-f04f-49e2-8e14-4fdbc810b08a/index_fungorum.tar.gz';

try
{
    $dwca = new DarwinCoreArchiveHarvester($uri);
    
    $taxa = $dwca->get_core_taxa();
    $vernaculars = $dwca->get_vernaculars();
    
    $taxa = array_merge($taxa, $vernaculars);
    
    $hierarchy = Hierarchy::create(array('label' => 'Index Fungorum'));
    $importer = new TaxonImporter($hierarchy, Vetted::trusted()->id, Visibility::visible()->id, 1);
    $importer->import_taxa($taxa);
}catch(Exception $e)
{
    var_dump($e);
    exit;
}


















// 
// 
// $agent_params = array(  "full_name"     => "$uri",
//                         "acronym"       => "NCBI");
//                             
// $agent_id = Agent::insert(Functions::mock_object("Agent", $agent_params));
// $agent_hierarchy_id = Hierarchy::find_by_agent_id($agent_id);
// if($agent_hierarchy_id)
// {
//     $agent_hierarchy = new Hierarchy($agent_hierarchy_id);
//     $hierarchy_group_id = $agent_hierarchy->hierarchy_group_id;
//     $hierarchy_group_version = $agent_hierarchy->latest_group_version()+1;
// }else
// {
//     $hierarchy_group_id = Hierarchy::next_group_id();
//     $hierarchy_group_version = 1;
// }
// $hierarchy_params = array(  "label"                     => "$uri",
//                             "description"               => "latest export",
//                             "agent_id"                  => $agent_id,
//                             "hierarchy_group_id"        => $hierarchy_group_id,
//                             "hierarchy_group_version"   => $hierarchy_group_version);
// $hierarchy = new Hierarchy(Hierarchy::insert(Functions::mock_object("Hierarchy", $hierarchy_params)));
// 
// 
// 






?>