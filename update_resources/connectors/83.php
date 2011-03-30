<?php
/* MorphBank connector 
estimated execution time: 6 hours
Partner provides a list of URL's and each URL will list ID's.
Then connector uses their service to read each ID and get the information needed.
*/

$timestart = microtime(1);

/* Temporarily not being used
$url_id_list = "http://www.morphbank.net/eolids.xml";
$url_id_list = "http://127.0.0.1/eol_php_code/update_resources/connectors/files/MorphBank/eolids.xml";
$url_id_list = "http://pandanus.eol.org/public/content_partners/MorphBank/eolids.xml";
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
$resource_id = 83;
$details_method_prefix = "http://services.morphbank.net/mb/request?method=id&format=svc&limit=2&id=";
$image_ids = array();
$schema_taxa = array();
$used_taxa = array();
$url="http://services.morphbank.net/mb2/request?method=eol&format=id&limit=-1";

/* Excluded IDs as suggested by BioImages Vanderbuilt */
$excluded_MorphBank_IDs = prepare_excluded_ids();

if($url)
{
    print "\n [$url] \n";
    $url = trim($url);
    if($image_id_xml = simplexml_load_file($url)){}
    else continue;
    foreach($image_id_xml->id as $id)
    {
        $image_ids[] = $id;
    }
}
$total_image_ids = count($image_ids);
print "\n count of image ID's = $total_image_ids";

/* loop through image ids */
$k=0;
foreach($image_ids as $image_id)
{
    $k++;    
    print "\n $image_id  [$k of $total_image_ids]";
    $image_details_url = $details_method_prefix . $image_id;
    if($xml = simplexml_load_file($image_details_url)){}
    else continue;
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
    }
    else
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
    
    /* start dataobject - image */
    if(!in_array(trim($xml->image->sourceId->morphbank),$excluded_MorphBank_IDs))
    {
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
        if(stripos($license,"publicdomain") != "")$license="http://creativecommons.org/licenses/publicdomain/";
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
    }
    /* end dataobject - image */    
    $used_taxa[$taxon_identifier] = $taxon_parameters;
    //if($k == 3)break; //debug    
}

foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new SchemaTaxon($taxon_parameters);
}
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);

// set MorphBank to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml"))
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert('Force Harvest')." WHERE id=".$resource_id);
}
print "\n --end-- ";

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");

//==========================================================================================
function get_data_object($id, $created, $modified, $rightsHolder, $license, $agent_name ,$description, $type)
{
    $dataObjectParameters = array();
    if($type == "text")
    {   
        $dataObjectParameters["identifier"] = "txt_" . $id;
        $dataObjectParameters["title"] = "Specimen Info";
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
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

    $agentParameters = array();
    $agentParameters["role"] = "source";
    $agentParameters["fullName"] = $agent_name;
    $agents[] = new SchemaAgent($agentParameters);
    $dataObjectParameters["agents"] = $agents;

    $dataObjectParameters["audiences"] = array();
    $audienceParameters = array();
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
    return $dataObjectParameters;
}

function prepare_excluded_ids()
{
    require_library('XLSParser');
    $parser = new XLSParser();
    $filename = DOC_ROOT . "/update_resources/connectors/files/MorphBank/original-mb-upload-2010-11-22.xls";
    $excluded_ids = array();
    $arr = $parser->convert_sheet_to_array($filename);
    return $arr['Morphbank ID'];
}

?>