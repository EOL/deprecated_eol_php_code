#!/usr/local/bin/php
<?php
/* MorphBank connector */
//exit;
//define("ENVIRONMENT", "development");
define("ENVIRONMENT", "slave_32");
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
$k=0;
foreach($image_ids as $image_id)
{
    $image_details_url = $details_method_prefix . $image_id;
    $xml = simplexml_load_file($image_details_url);    
    $dwc = $xml->specimen->children("http://rs.tdwg.org/dwc/dwcore/");    
    $dwcc = $xml->specimen->children("http://rs.tdwg.org/dwc/curatorial/");        
    $dwcg = $xml->specimen->children("http://rs.tdwg.org/dwc/geospatial/");    
    
    
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
        

        /* start first dataobject - text */     
        if(isset($xml->specimen->sourceId->morphbank))
        {    
            $dc_identifier = trim($xml->specimen->sourceId->morphbank);            
            $dcterms_created = trim($xml->specimen->dateCreated);  
            $dcterms_modified = trim($xml->specimen->dateLastModified);
            $thumbnailURL = trim($xml->specimen->thumbUrl);
            $dc_source = trim($xml->specimen->detailPageUrl);
            $agent_name = trim($xml->specimen->submittedBy);       
            $image_type = trim($xml->specimen->imageType);  
    
            $copyright_text = "";    
            $license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        
            // /*
    
            $desc = null;
            if($dwc->Sex)$desc .= "<br>Sex: " . $dwc->Sex;
            if($dwc->LifeStage)$desc .= "<br>LifeStage: " . $dwc->LifeStage;    
            if($dwc->Collector)$desc .= "<br>Collector: " . $dwc->Collector;
            if($dwc->CatalogNumber)$desc .= "<br>CatalogNumber: " . $dwc->CatalogNumber;
            if($dwc->EarliestDateCollected)$desc .= "<br>EarliestDateCollected: " . $dwc->EarliestDateCollected;
            if($dwc->BasisOfRecord)$desc .= "<br>BasisOfRecord: " . $dwc->BasisOfRecord;    
            if($dwc->InstitutionCode)$desc .= "<br>InstitutionCode: " . $dwc->InstitutionCode;
            //if($dwcc->TypeStatus)$desc .= "<br>TypeStatus: " . $dwcc->TypeStatus;
            if($dwc->DateIdentified)$desc .= "<br>: " . $dwc->DateIdentified;
            if($dwc->Country)$desc .= "<br>Country: " . $dwc->Country;
            //if($dwcg->CoordinateUncertaintyInMeters)$desc .= "<br>CoordinateUncertaintyInMeters: " . $dwcg->CoordinateUncertaintyInMeters;
            if($dwc->Locality)$desc .= "<br>Locality: " . $dwc->Locality;
            if($desc)$desc = substr($desc,4,strlen($desc));
    
            //<dwcg:DecimalLatitude>41.7944</dwcg:DecimalLatitude>
            //<dwcg:DecimalLongitude>-124.0883</dwcg:DecimalLongitude>
        
            // */
        
            $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $copyright_text, $license, $agent_name, $desc, "text");       
            $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);         
        }
        /* end first dataobject - text */     
        
        
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

    $desc = null;
    if($xml->view->dateCreated)$desc .= "<br>Date created: " . $xml->view->dateCreated;
    if($xml->view->dateLastModified)$desc .= "<br>Date modified: " . $xml->view->dateLastModified;    
    if($xml->view->dateToPublish)$desc .= "<br>Date published: " . $xml->view->dateToPublish;    
    if($xml->view->submittedBy)$desc .= "<br>Submitted by: " . $xml->view->submittedBy;    
    
    if($xml->image->copyrightText)$desc .= "<br>Copyright: " . $xml->image->copyrightText;    
    
    if($xml->view->viewAngle)$desc .= "<br>View angle: " . $xml->view->viewAngle;    
    if($xml->view->imagingTechnique)$desc .= "<br>Imaging technique: " . $xml->view->imagingTechnique;    
    if($xml->view->imagingPreparationTechnique)$desc .= "<br>Imaging preparation technique: " . $xml->view->imagingPreparationTechnique;
    if($xml->view->specimenPart)$desc .= "<br>Specimen part: " . $xml->view->specimenPart;    
    if($xml->view->developmentalStage)$desc .= "<br>Developmental stage: " . $xml->view->developmentalStage;    
    if($xml->view->sex)$desc .= "<br>Sex: " . $xml->view->sex;
    if($xml->view->form)$desc .= "<br>Form: " . $xml->view->form;    
    if($desc)$desc = substr($desc,4,strlen($desc));
        
    $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $copyright_text, $license, $agent_name, $desc, "image");       
    $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
 
    
    $used_taxa[$taxon_identifier] = $taxon_parameters;            
    
    $k++;if($k == 6)break;
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

function get_data_object($id, $created, $modified, $rightsHolder, $license, $agent_name ,$description, $type)
{
    $dataObjectParameters = array();
    
    if($type == "text")
    {   
        $dataObjectParameters["identifier"] = "txt_" . $id;    
        $dataObjectParameters["title"] = "Specimen Info";
        ///////////////////////////////////    
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
        ///////////////////////////////////        
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";    
        $dataObjectParameters["mimeType"] = "text/html";        
    }
    else
    {
        $dataObjectParameters["identifier"] = "img_" . $id;    
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $dataObjectParameters["mimeType"] = "image/jpeg";
        $dataObjectParameters["thumbnailURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=thumb";
        $dataObjectParameters["mediaURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=jpg";        
    }
    
    $dataObjectParameters["description"] = $description;        
    $dataObjectParameters["created"] = $created;
    $dataObjectParameters["modified"] = $modified;    
    $dataObjectParameters["rightsHolder"] = $rightsHolder;            
    $dataObjectParameters["language"] = "en";    
    $dataObjectParameters["license"] = $license;        
    $dataObjectParameters["source"] = "http://www.morphbank.net/?id=" . $id;
    ///////////////////////////////////
    $agentParameters = array();
    $agentParameters["role"] = "source";
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