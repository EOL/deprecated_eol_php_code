<?php

include_once(dirname(__FILE__) . "/../../../config/environment.php");



$new_resource_path = DOC_ROOT . "temp/30.xml";

$file = file_get_contents($new_resource_path);
$array = unserialize($file);

$all_taxa = array();
foreach($array as $taxon)
{
    $taxon_references = array();
    if(@is_array($taxon["references"]))
    {
        foreach($taxon["references"] as $reference)
        {
            $taxon_references[] = new \SchemaReference($reference);
        }
    }
    $taxon["references"] = $taxon_references;
    
    $data_objects = array();
    foreach($taxon["dataObjects"] as $data_object)
    {
        $data_object_references = array();
        if(@is_array($data_object["references"]))
        {
            foreach($data_object["references"] as $reference) $data_object_references[] = new \SchemaReference($reference);
        }
        $data_object["references"] = $data_object_references;
        
        $data_object_subjects = array();
        if(@is_array($data_object["subjects"]))
        {
            foreach($data_object["subjects"] as $subject) $data_object_subjects[] = new \SchemaSubject($subject);
        }
        $data_object["subjects"] = $data_object_subjects;
        
        $data_object_agents = array();
        if(@is_array($data_object["agents"]))
        {
            foreach($data_object["agents"] as $agent) $data_object_agents[] = new \SchemaAgent($agent);
        }
        $data_object["agents"] = $data_object_agents;
        
        $data_objects[] = new \SchemaDataObject($data_object);
    }
    $taxon["dataObjects"] = $data_objects;
    
    $all_taxa[] = new \SchemaTaxon($taxon);
}


$new_resource_xml = SchemaDocument::get_taxon_xml($all_taxa);

$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "30.xml";

if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}
fwrite($OUT, $new_resource_xml);
fclose($OUT);

//shell_exec("rm ".$new_resource_path);

?>