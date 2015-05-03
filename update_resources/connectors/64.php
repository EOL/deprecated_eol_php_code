<?php
/* Radiolaria connector
estimated execution time: 3-5 secs.

Partner provided a non EOL-compliant XML service for all their species.
Connector parses this XML and generates the EOL-compliant XML.

dataObject.dc:identifier is blank

*/
$timestart = microtime(1);

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$resource = new Resource(64);

$species_url = "http://www.radiolaria.org/species.htm?sp_id=";
$schema_taxa = array();
$used_taxa = array();

print "<hr> $resource->accesspoint_url";

$xml = @simplexml_load_file($resource->accesspoint_url);
$i=0;
$sciname = array();//just for debugging
$rightsHolder = "Radiolaria.org";

foreach($xml->species_list->species as $main)
{
    $taxon_identifier = $main->sp_id;
    $dwc_ScientificName = $main->sp_name;
    $parenthesis_start = "";
    $parenthesis_end = "";
    if($main->author_year_paranthesis == 1)
    {
        $parenthesis_start = "(";
        $parenthesis_end = ")";
    }
    if($main->sp_author != "") $dwc_ScientificName .= " " . $parenthesis_start . $main->sp_author . " " . $main->sp_year . "$parenthesis_end";

    //$rank = $tt->TaxonHeading->RankDesignation;

    $i++;
    $sciname["$dwc_ScientificName"] = $dwc_ScientificName;

    $taxon = str_replace(" ", "_", $dwc_ScientificName);

    if(@$used_taxa[$taxon])
    {
        $taxon_parameters = $used_taxa[$taxon];
    }else
    {
        $taxon_parameters = array();

        $main_citation="";

        $taxon_parameters["identifier"]     = "radiolaria_" . $taxon_identifier;
        $taxon_parameters["scientificName"] = $dwc_ScientificName;
        $taxon_parameters["modified"]       = $main->sp_date;
        $taxon_parameters["source"]         = $main->source;
        $taxon_parameters["order"]          = ucfirst(strtolower($main->type));
        $taxon_parameters["family"]         = $main->family;

        $taxon_parameters["dataObjects"]= array();

        $used_taxa[$taxon] = $taxon_parameters;
    }
    foreach($main->descriptions as $desc)
    {   $j=0;
        foreach($desc as $description)
        {
            $j++;
            $object_id = "radiolaria_" . $taxon_identifier . "_desc_" . $j;
            $temp = process_dataobjects($description,1,$object_id);
        }
    }
    foreach($main->images as $desc)
    {   $j=0;
        foreach($desc as $image)
        {
            $j++;
            $object_id = "radiolaria_" . $taxon_identifier . "_img_" . $j;
            $temp = process_dataobjects($image,2,$object_id);
        }
    }
    //print"<hr>";
}
// /*
print "\n
i = $i \n
sciname = " . count($sciname);
print "\n";
print count(array_keys($sciname));
print "\n";
print count(array_keys($used_taxa));
// */
// end loop through






foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new \SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}
fwrite($OUT, $new_resource_xml);
fclose($OUT);
print "<hr>$old_resource_path<hr>";
////////////////////// ---
$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";

echo "\n\n Done processing.";
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################




function process_dataobjects($item,$type,$object_id)//$type 1 = text object; 2 = image object
{
    global $taxon_identifier;
    global $taxon_parameters;
    global $used_taxa;
    global $taxon;
    global $species_url;
    global $main;

    $dc_source       = $species_url . $taxon_identifier;
    $dcterms_created = "";
    $ref             = "";
    if($type == 1) //text
    {
        $dc_identifier   = "";
        $description    = trim($item->de_description);

        $description = str_ireplace("Dimensions.", "Dimensions. ", $description);
        $description = str_ireplace("Habitat.", "Habitat. ", $description);

        $title          = "Description";
        $subject        = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
        $mediaURL       = "";
        $dataType       = "http://purl.org/dc/dcmitype/Text";
        $mimeType       = "text/html";

        $agentParameters = array();
        $agentParameters["role"] = "author";
        $agentParameters["fullName"] = $item->de_author . " " . $item->de_year;
        $agents[] = new \SchemaAgent($agentParameters);

        $license          = "http://creativecommons.org/licenses/" . $item->de_license;
        $dcterms_modified = $item->de_date;
    }
    else //image
    {
        $dc_identifier   = $item->url;
        $description    = trim($item->im_description);
        $title          = "";
        $subject        = "";
        $mediaURL       = $item->url;
        $dataType       = "http://purl.org/dc/dcmitype/StillImage";
        $mimeType       = "image/jpeg";

        if($item->photo_by != "")
        {
            $agentParameters = array();
            $agentParameters["role"] = "author";
            $agentParameters["fullName"] = $item->photo_by;
            $agents[] = new \SchemaAgent($agentParameters);
        }
        $license          = "http://creativecommons.org/licenses/" . $item->im_license;
        $dcterms_modified = $item->im_date;
    }

    if(isset($main->contributed_by))
    {
        $agentParameters["role"] = "source";
        $agentParameters["fullName"] = $main->contributed_by;
        $agents[] = new \SchemaAgent($agentParameters);
    }

    $agentParameters["role"] = "project";
    $agentParameters["fullName"] = "Radiolaria.org";
    $agentParameters["homepage"] = "http://www.radiolaria.org/index.htm";
    $agents[] = new \SchemaAgent($agentParameters);

    $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents);
    $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_parameters);
    $used_taxa[$taxon] = $taxon_parameters;

}//function process_dataobjects($arr)

function get_data_object($id, $created, $modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents_arr)
{
    global $rightsHolder;

    $dataObjectParameters = array();
    $dataObjectParameters["title"] = $title;
    $dataObjectParameters["rightsHolder"] = $rightsHolder;

    if($subject)
    {
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = $subject;
        $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);
    }

    //$description = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $description);
    $dataObjectParameters["description"] = trim($description);

    $dataObjectParameters["identifier"] = $id;
    $dataObjectParameters["created"] = $created;
    $dataObjectParameters["modified"] = $modified;
    $dataObjectParameters["rightsHolder"] = $rightsHolder;
    $dataObjectParameters["dataType"] = $dataType;
    $dataObjectParameters["mimeType"] = $mimeType;
    $dataObjectParameters["language"] = "en";
    $dataObjectParameters["license"] = $license;
    $dataObjectParameters["mediaURL"] = $mediaURL;
    $dataObjectParameters["source"] = $dc_source;

    if($agents_arr)
    {
        $dataObjectParameters["agents"] = $agents_arr;
    }

    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();
    $audienceParameters = array();

    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);

    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);
    ///////////////////////////////////

    ///////////////////////////////////
    ///*working
    if($ref != "")
    {
        $dataObjectParameters["references"] = array();
        $referenceParameters = array();
        $referenceParameters["fullReference"] = trim($ref);
        $references[] = new \SchemaReference($referenceParameters);
        /*not working...
        $referenceParam["referenceIdentifiers"][] = array("label" => "label" , "value" => "value");
        */
        $dataObjectParameters["references"] = $references;
    }
    ///////////////////////////////////
    return $dataObjectParameters;
}
?>