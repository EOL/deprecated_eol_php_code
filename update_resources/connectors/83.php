<?php
namespace php_active_record;
/* MorphBank connector
estimated execution time: 6 hours
set as a cron task to run every month

Partner provides a list of URL's and each URL will list ID's.
Then connector uses their service to read each ID and get the information needed.

Not being used anymore, but maybe revived, depends on MorphBank
$url_list_of_group_ids = "http://www.morphbank.net/eolids.xml";
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$mysqli =& $GLOBALS['mysqli_connection'];
$resource_id = 83;
$details_method_prefix = "http://services.morphbank.net/mb3/request?method=id&format=svc&limit=2&id=";
$image_ids = array();
$schema_taxa = array();
$used_taxa = array();
$url_list_of_image_ids = "http://services.morphbank.net/mb3/request?method=eol&format=id&limit=-1";
/* Excludes MorphBank IDs as suggested by BioImages Vanderbuilt */
$excluded_MorphBank_IDs = prepare_excluded_ids();
if($url_list_of_image_ids)
{
    print "\n [url_list_of_image_ids: $url_list_of_image_ids] \n";
    $response = Functions::get_remote_file($url_list_of_image_ids, array('download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 5));
    $image_id_xml = simplexml_load_string($response);
    if($image_id_xml) 
    {
        foreach($image_id_xml->id as $id) $image_ids[] = $id;
    }
}
$total_image_ids = count($image_ids);
print "\n count of image ID's = $total_image_ids";
if($total_image_ids == 0) exit("\n Program will terminate. MorphBank service not ready.");
/* loop through image ids */
$k = 0;
foreach($image_ids as $image_id)
{
    $k++;
    if($k % 5000 == 0) sleep(60); // might need this as MorphBank service chokes on continues request. They have not yet solved this problem. Service still chokes.
    print "\n $image_id [$k of $total_image_ids]";
    print "\n " . $details_method_prefix . $image_id . " \n";
    if(!$xml = Functions::get_hashed_response($details_method_prefix . $image_id)) continue;
    foreach($xml->object as $object)
    {
        if(!$object["type"] == "Image") break;
        $dwc = $object->children("http://rs.tdwg.org/dwc/dwcore/");
        $dwc_Kingdom = trim($dwc->Kingdom);
        $dwc_Phylum = trim($dwc->Phylum);
        $dwc_Class = trim($dwc->Class);
        $dwc_Order = trim($dwc->Order);
        $dwc_Family = trim($dwc->Family);
        $dwc_Genus = trim($dwc->Genus);
        $dwc_ScientificName = trim($dwc->ScientificName);
        $taxon_identifier = str_replace(" ", "_", $dwc_ScientificName);
        if(@$used_taxa[$taxon_identifier]) $taxon_parameters = $used_taxa[$taxon_identifier];
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
            $taxon_parameters["scientificName"] = $dwc_ScientificName;
            $taxon_parameters["source"] = "http://www.morphbank.net/Browse/ByImage/?tsnKeywords=" . urlencode($dwc_ScientificName) . "&spKeywords=&viewKeywords=&localityKeywords=&activeSubmit=2";
            $taxon_parameters["dataObjects"] = array();
            $used_taxa[$taxon_identifier] = $taxon_parameters;
        }
        if(!in_array(trim($object->sourceId->morphbank), $excluded_MorphBank_IDs))
        {
            $dc_identifier = trim($object->sourceId->morphbank);
            $dcterms_created = trim($object->dateCreated);
            $dcterms_modified = trim($object->dateLastModified);
            $thumbnailURL = trim($object->thumbUrl);
            $dc_source = trim($object->detailPageUrl);
            $agent = array();
            $agent[] = array("role" => "source", "homepage" => "http://www.morphbank.net/?id=" . $object->submittedBy->userId, trim($object->submittedBy->uin));
            $image_type = trim($object->imageType);
            $copyright_text = trim($object->copyrightText);
            $license_text = trim($object->creativeCommons);
            $license = null;
            if(preg_match("/(http:\/\/creativecommons\.org\/licenses\/[^\/]+\/[^\/]+\/)/", $license_text, $arr)){$license = $arr[1];}
            if(stripos($license, "publicdomain") != "") $license = "http://creativecommons.org/licenses/publicdomain/";
            if(!$license) continue;
            $desc = null;
            if($object->dateCreated)$desc .= "<br>Date created: " . $object->dateCreated;
            if($object->dateLastModified)$desc .= "<br>Date modified: " . $object->dateLastModified;
            if($object->dateToPublish)$desc .= "<br>Date published: " . $object->dateToPublish;
            if($object->submittedBy)$desc .= "<br>Submitted by: " . $object->submittedBy->uin;
            if($object->copyrightText)$desc .= "<br>Copyright: " . $object->copyrightText;
            if($object->viewAngle)$desc .= "<br>View angle: " . $object->viewAngle;
            if($object->imagingTechnique)$desc .= "<br>Imaging technique: " . $object->imagingTechnique;
            if($object->imagingPreparationTechnique)$desc .= "<br>Imaging preparation technique: " . $object->imagingPreparationTechnique;
            if($object->specimenPart)$desc .= "<br>Specimen part: " . $object->specimenPart;
            if($object->developmentalStage)$desc .= "<br>Developmental stage: " . $object->developmentalStage;
            if($object->sex)$desc .= "<br>Sex: " . $object->sex;
            if($object->form)$desc .= "<br>Form: " . $object->form;            
            if($desc) $desc = substr($desc, 4, strlen($desc));
            $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $copyright_text, $license, $agent, $desc, "image");
            $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_parameters);
        }
        $used_taxa[$taxon_identifier] = $taxon_parameters;
    }
}

foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new \SchemaTaxon($taxon_parameters);
}
$new_resource_xml = \SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}
fwrite($OUT, $new_resource_xml);
fclose($OUT);
// set MorphBank to Harvest Requested
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 600)
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::harvest_requested()->id . " WHERE id=" . $resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours  \n";
echo "\n\n Done processing.";

function get_data_object($id, $created, $modified, $rightsHolder, $license, $agent, $description, $type)
{
    $dataObjectParameters = array();
    if($type == "text")
    {
        $dataObjectParameters["identifier"] = "txt_" . $id;
        $dataObjectParameters["title"] = "Specimen Info";
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
        $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);
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
    $agents = array();
    foreach($agent as $agent)
    {  
        $agentParameters = array();
        $agentParameters["role"]     = $agent["role"];
        $agentParameters["homepage"] = $agent["homepage"];
        $agentParameters["logoURL"]  = "";        
        $agentParameters["fullName"] = $agent[0];
        $agents[] = new \SchemaAgent($agentParameters);
    }
    $dataObjectParameters["agents"] = $agents;    
    $dataObjectParameters["audiences"] = array();
    $audienceParameters = array();
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);
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