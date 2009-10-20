<?php
//#!/usr/local/bin/php
/* MorphBank connector */
//exit;

//define("ENVIRONMENT", "development");
//define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", false);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];

/*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
*/

$resource = new Resource(83); //exit($resource->id);

$details_method_prefix = "http://services.morphbank.net/mb/request?method=id&format=svc&limit=2&id=";
$image_ids = array();

$schema_taxa = array();
$used_taxa = array();

//get all image ids
/* working but not being used as advised by Greg from MorphBank
$limit_of_inventory = 25; //300000
$inventory_method_url = "http://services.morphbank.net/mb/request?method=search&objecttype=Image&keywords=&limit=$limit_of_inventory&format=id&firstResult=100000";
$image_id_xml = simplexml_load_file($inventory_method_url);
foreach($image_id_xml->id as $id)
{$image_ids[] = $id;}
*/

$id_list_url = "http://services.morphbank.net/mb/request?method=search&objecttype=Image&limit=-1&keywords=baskauf&format=id";
$image_id_xml = simplexml_load_file($id_list_url);
foreach($image_id_xml->id as $id)
{
    $image_ids[] = $id;
    print $id . " - ";
}
print "<hr>" . count($image_ids); //exit;

// loop through image ids
foreach($image_ids as $image_id)
{
    $image_details_url = $details_method_prefix . $image_id;
    $xml = simplexml_load_file($image_details_url);    
    $dwc = $xml->specimen->children("http://rs.tdwg.org/dwc/dwcore/");    
    $dwc_Kingdom = trim($dwc->Kingdom);
    $dwc_Phylum = trim($dwc->Phylum);
    $dwc_Class = trim($dwc->Class);
    $dwc_Order = trim($dwc->Order);
    $dwc_Family = trim($dwc->Family);
    $dwc_Genus = trim($dwc->Genus);
    $dwc_ScientificName = trim($dwc->ScientificName);
    $taxon_identifier = str_replace(" ", "_", $dwc_ScientificName);    
    if(@$used_taxa[$taxon_identifier])
    {
        $taxon_parameters = $used_taxa[$taxon_identifier];
    }else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = $taxon_identifier;
        $taxon_parameters["kingdom"] = $dwc_Kingdom;
        $taxon_parameters["phylum"] = $dwc_Phylum;
        $taxon_parameters["class"] = $dwc_Class;
        $taxon_parameters["order"] = $dwc_Order;
        $taxon_parameters["family"] = $dwc_Family;
        $taxon_parameters["genus"] = $dwc_Genus;
        $taxon_parameters["scientificName"]= $dwc_ScientificName;        
        $taxon_parameters["source"] = "http://www.morphbank.net/Browse/ByImage/?tsnKeywords=" . urlencode($dwc_ScientificName) . "&spKeywords=&viewKeywords=&localityKeywords=&activeSubmit=2";        
        $taxon_parameters["dataObjects"]= array();        
        $used_taxa[$taxon_identifier] = $taxon_parameters;
    }        
    $dc_identifier = trim($xml->image->sourceId->morphbank);            
    $dcterms_created = trim($xml->image->dateCreated);  
    $dcterms_modified = trim($xml->image->dateLastModified);
    $thumbnailURL = trim($xml->image->thumbUrl);
    $dc_source = trim($xml->image->detailPageUrl);
    $agent_name = trim($xml->image->submittedBy);       
    $image_type = trim($xml->image->imageType);  
    $copyright_text = trim($xml->image->copyrightText);    
    $license_text = trim($xml->image->creativeCommons);
    $license = null;
    if(preg_match("/(http:\/\/creativecommons\.org\/licenses\/[^\/]+\/[^\/]+\/)/", $license_text, $arr)){$license = $arr[1];}
    if(!$license) continue;    
    $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $copyright_text, $license, $agent_name);       
    $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
    /* a second dataobject     
    $data_object_parameters = get_data_object("eli", $dcterms_created, $dcterms_modified, $copyright_text, $license, $agent_name);       
    $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);
    */    
    $used_taxa[$taxon_identifier] = $taxon_parameters;        
}

/*
print "<pre>";
print_r($used_taxa);
print "</pre>";
exit;
*/

foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

function get_data_object($id, $created, $modified, $rightsHolder, $license, $agent_name)
{
    $dataObjectParameters = array();
    //$dataObjectParameters["title"] = $title;
    //$dataObjectParameters["description"] = $description;    
    $dataObjectParameters["identifier"] = $id;    
    $dataObjectParameters["created"] = $created;
    $dataObjectParameters["modified"] = $modified;    
    $dataObjectParameters["rightsHolder"] = $rightsHolder;        
    $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
    $dataObjectParameters["mimeType"] = "image/jpeg";
    $dataObjectParameters["language"] = "en";    
    $dataObjectParameters["license"] = $license;        
    $dataObjectParameters["thumbnailURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=thumb";
    $dataObjectParameters["mediaURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=jpg";        
    $dataObjectParameters["source"] = "http://www.morphbank.net/?id=" . $id;
    ///////////////////////////////////
    $agentParameters = array();
    $agentParameters["role"] = "photographer";
    $agentParameters["fullName"] = $agent_name;
    $agents[] = new SchemaAgent($agentParameters);
    $dataObjectParameters["agents"] = $agents;    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();    
    $audienceParameters = array();    
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    return $dataObjectParameters;
}
?>