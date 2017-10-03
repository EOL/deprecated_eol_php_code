<?php
namespace php_active_record;
/* North American Mammals 
estimated execution time: 1-2 minutes
*/
$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");

/*
$query = "SELECT s.species_id, g.genus_name, concat(g.genus_name,' ',s.species_name) AS `sci_name`, f.family_name, o.order_name, s.avg_length, 
s.avg_length_sp, s.range_length, s.range_length_sp, s.avg_weight, s.avg_weight_sp, s.range_weight, s.range_weight_sp, s.conservation_status_notes, 
s.conservation_status_notes_sp, s.common_name, s.common_name_sp, s.other_names, s.other_names_sp, s.refs, s.refs_sp, s.links, s.links_sp, 
s.dimorphism, s.dimorphism_sp, s.legend, s.legend_sp, s.refs, s.refs_sp, s.adaptation, s.adaptation_sp, cs.conservation_status_id, 
cs.conservation_status_title, cs.conservation_status_title_sp, cs.conservation_status_abbrev 
From nam_species s 
LEFT JOIN nam_genus g ON s.genus_id = g.genus_id 
LEFT Join nam_family f ON g.family_id = f.Family_ID 
LEFT Join nam_orders o ON f.order_id = o.order_id 
LEFT Join nam_conservation_status cs ON s.conservation_status_id = cs.id";

We've requested the partner to provide us with just a text dump of the result of the query above, but it seems they'll just
continue providing us with the Access MDB.
*/

$remote_file = "https://dl.dropboxusercontent.com/u/7597512/NorthAmericanMammals/data_from_sql_export.txt"; //no longer publicly accessible
$remote_file = "http://localhost/cp/North American Mammals/data_from_sql_export.txt";
$text_file = Functions::save_remote_file_to_local($remote_file, array('download_wait_time' => 1000000, 'timeout' => 600));

require_library('connectors/FishBaseAPI');
$fields = array("species_id", "genus_name", "sci_name", "family_name", "order_name", "avg_length", "avg_length_sp", "range_length", "range_length_sp", "avg_weight", "avg_weight_sp", "range_weight", "range_weight_sp", "conservation_status_notes", "conservation_status_notes_sp", "common_name", "common_name_sp", "other_names", "other_names_sp", "refs", "refs_sp", "links", "links_sp", "dimorphism", "dimorphism_sp", "legend", "legend_sp", "refs(2)", "refs_sp(2)", "adaptation", "adaptation_sp", "conservation_status_id", "conservation_status_title", "conservation_status_title_sp", "conservation_status_abbrev");
$taxa = FishBaseAPI::make_array($text_file, $fields, "", array());

$resource_id = 85; //for North American Mammals
$schema_taxa = array();
$used_taxa = array();

$ctr = 0;
foreach($taxa as $row)
{
    $ctr++; 
    print "$ctr - ";
    $dwc_Kingdom        = "Animalia";
    $dwc_Order          = trim($row["order_name"]);
    $dwc_Family         = trim($row["family_name"]);
    $dwc_Genus          = trim($row["genus_name"]);
    $dwc_ScientificName = trim($row["sci_name"]);
    $taxon_identifier   = "NAM_" . $row["species_id"];
    if(@$used_taxa[$taxon_identifier]) $taxon_parameters = $used_taxa[$taxon_identifier];
    else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"]     = $taxon_identifier;
        $taxon_parameters["kingdom"]        = $dwc_Kingdom;
        $taxon_parameters["order"]          = $dwc_Order;
        $taxon_parameters["family"]         = $dwc_Family;
        $taxon_parameters["genus"]          = $dwc_Genus;
        $taxon_parameters["scientificName"] = $dwc_ScientificName;
        $taxon_parameters["source"]         = "http://www.mnh.si.edu/mna/image_info.cfm?species_id=" . $row["species_id"];
        $taxon_parameters["commonNames"]    = array();
        $taxon_parameters["commonNames"][]  = new \SchemaCommonName(array("name" => trim($row["common_name"]), "language" => "en"));
        $othernames = array();
        $othernames[] = array("name" => trim($row["other_names"]), "language" => "en");
        $othernames[] = array("name" => trim($row["other_names_sp"]), "language" => "es");
        foreach($othernames as $other_names)
        {
            if($other_names["name"] != "")
            {
                if(stripos($other_names["name"], ",") != "")
                {
                    $names = explode(",", $other_names["name"]);
                    foreach($names as $name) $taxon_parameters["commonNames"][] = new \SchemaCommonName(array("name" => trim($name), "language" => $other_names["language"]));
                }
                else $taxon_parameters["commonNames"][] = new \SchemaCommonName(array("name" => $other_names["name"], "language" => $other_names["language"]));
            }
        }
        $taxon_parameters["dataObjects"] = array();
        $used_taxa[$taxon_identifier] = $taxon_parameters;
    }

    $dc_source    = "http://www.mnh.si.edu/mna/image_info.cfm?species_id=" . $row["species_id"];
    $dc_source_sp = "http://www.mnh.si.edu/mna/image_info.cfm?species_id=" . $row["species_id"] . "&lang=_sp";

    //remove special chars
    $adaptation_sp = str_ireplace("", "", $row["adaptation_sp"]);
    $adaptation_sp = str_ireplace("", "", $adaptation_sp);
    $adaptation = str_ireplace("", "", $row["adaptation"]);
    $refs = str_ireplace("", "", $row["refs"]);
    $refs = utf8_decode(str_ireplace("", "", $refs));
    $refs_sp = str_ireplace("", "", $row["refs_sp"]);
    $refs_sp = utf8_decode(str_ireplace("", "", $refs_sp));

    if(trim($refs) != "") $refs = "Original description: " . $refs;
    if(trim($refs_sp) != "") $refs_sp = "Original description: " . $refs_sp;

    $legend = str_ireplace("", "", $row["legend"]);
    $legend_sp = str_ireplace("", "", $row["legend_sp"]);

    if($data_object_params = get_GeneralDescription($legend, $adaptation, $row["links"], $refs, "en", $taxon_identifier, $dc_source)) {$taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_params);}
    if($data_object_params = get_GeneralDescription($legend_sp, $adaptation_sp, $row["links_sp"], $refs_sp, "es", $taxon_identifier, $dc_source_sp)) {$taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_params);}
    // suggested by Leo, no refs for the other text objects
    $refs = array();
    $refs_sp = array();
    if($data_object_params = get_size($row["dimorphism"], $row["avg_length"], $row["range_length"], $row["avg_weight"], $row["range_weight"], $refs, "en", $taxon_identifier, $dc_source)) {$taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_params);}
    if($data_object_params = get_size($row["dimorphism_sp"], $row["avg_length_sp"], $row["range_length_sp"], $row["avg_weight_sp"], $row["range_weight_sp"], $refs_sp, "es", $taxon_identifier, $dc_source_sp)) {$taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_params);}
    if($data_object_params = get_ConservationStatus($row["conservation_status_notes"], $refs, "en", $taxon_identifier, $dc_source)) {$taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_params);}
    if($data_object_params = get_ConservationStatus($row["conservation_status_notes_sp"], $refs_sp, "es", $taxon_identifier, $dc_source_sp)) {$taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_params);}

    /* they removed their distribution maps from their site
    $url = "http://www.mnh.si.edu/mna/thumbnails/maps/" . str_repeat("0", 3-strlen($row["species_id"])) . $row["species_id"] . ".gif";
    $handle = fopen($url, "r");
    if($handle)
    {
        $dc_identifier = "$taxon_identifier" . "_Distribution";
        $description = "<img src='$url'>";
        $title = "Distribution in North America";
        $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
        $data_object_params = get_data_object($dc_identifier, $dc_source, $description, $refs, $subject, $title, "en");
        $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_params);
    }
    */
    
    $used_taxa[$taxon_identifier] = $taxon_parameters;
}

foreach($used_taxa as $taxon_parameters) $schema_taxa[] = new \SchemaTaxon($taxon_parameters);
$new_resource_xml = \SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}
fwrite($OUT, $new_resource_xml);
fclose($OUT);

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

function get_ConservationStatus($conservation_status_notes, $reference, $language, $taxon_identifier, $dc_source)
{
    if($conservation_status_notes != "")
    {
        $dc_identifier = $taxon_identifier . "_ConservationStatus";
        $title = "Status";
        if($language == "es") 
        {
            $dc_identifier .= "_es";
            $title = "Estado";
        }
        $description = $conservation_status_notes;    
        $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus";
        return get_data_object($dc_identifier, $dc_source, $description, $reference, $subject, $title, $language);
    }
}

function get_Size($dimorphism, $avg_length, $range_length, $avg_weight, $range_weight, $reference, $language, $taxon_identifier, $dc_source)
{
    $description = "";
    if($dimorphism != "")
    {
        if    ($language == "en") $description .= "Sexual Dimorphism: $dimorphism";
        elseif($language == "es") $description .= "Dimorfismo Sexual: $dimorphism";
    } 
    if($description != "") $description .= "<br><br>";
    if($avg_length != "" or $range_length != "")
    {
        if    ($language == "en") $description .= "Length:";
        elseif($language == "es") $description .= "Longitud:";
    }
    if($avg_length != "") 
    {
        if    ($language == "en") $description .= "<br>Average: $avg_length";
        elseif($language == "es") $description .= "<br>Promedio: $avg_length";
    }
    if($range_length != "")
    {
        if    ($language == "en") $description .= "<br>Range: $range_length";
        elseif($language == "es") $description .= "<br>Rango: $range_length";
    }
    if($description != "") $description .= "<br><br>";
    if($avg_weight != "" or $range_weight != "")
    {
        if    ($language == "en") $description .= "Weight:";
        elseif($language == "es") $description .= "Peso:";
    }
    if($avg_weight != "")
    { 
        if    ($language == "en") $description .= "<br>Average: $avg_weight";
        elseif($language == "es") $description .= "<br>Promedio: $avg_weight";
    }
    if($range_weight != "")
    {
        if    ($language == "en") $description .= "<br>Range: $range_weight";
        elseif($language == "es") $description .= "<br>Rango: $range_weight";
    }
    if($description != "")
    {
        $dc_identifier = $taxon_identifier . "_Size";
        $title = "Size in North America";
        if($language == "es")
        {
            $dc_identifier .= "_es";
            $title = "Tamaño en América del Norte";
        }
        $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size";
        return get_data_object($dc_identifier, $dc_source, $description, $reference, $subject, $title, $language);
    }
}

function get_GeneralDescription($legend, $adaptation, $links, $reference, $language, $taxon_identifier, $dc_source)
{
    $dc_identifier = $taxon_identifier . "_GenDesc";
    $title = "Description";
    if($language == "es")
    {
        $dc_identifier .= "_es";
        $title = "Descripción";
    }
    $description = $legend;
    if($adaptation != "")
    {
        if    ($language == "en") $description .= "<br><br>Adaptation: $adaptation";
        elseif($language == "es") $description .= "<br><br>Adaptación: $adaptation";
    }
    if($links != "")
    {
        // remove the double qoutes around 'links'
        $links = str_replace('""', '"', trim($links));
        $links = substr($links, 1, strlen($links)-2);
        if    ($language == "en") $links = "<br><br>Links:<br>" . str_ireplace("<br><br>", "<br>", $links);
        elseif($language == "es") $links = "<br><br>Enlaces:<br>" . str_ireplace("<br><br>", "<br>", $links);
        $description .= $links;
    }
    //$subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
    $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology";
    // re-mapped so it will show in Brief Summary and not in Comprehensive Description

    return get_data_object($dc_identifier, $dc_source, $description, $reference, $subject, $title, $language);
}

function get_data_object($id, $dc_source, $description, $reference, $subject, $title, $language)
{
    $dataObjectParameters = array();
    $dataObjectParameters["title"] = $title;
    $dataObjectParameters["description"] = $description;
    $dataObjectParameters["identifier"] = $id;
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;
    $dataObjectParameters["rightsHolder"] = "Smithsonian Institution";

    $dataObjectParameters["subjects"] = array();
    $subjectParameters = array();
    $subjectParameters["label"] = $subject;
    $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);

    $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";
    $dataObjectParameters["mimeType"] = "text/html";
    $dataObjectParameters["language"] = $language;
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by/3.0/";
    //$dataObjectParameters["thumbnailURL"] = "";
    //$dataObjectParameters["mediaURL"] = "";
    $dataObjectParameters["source"] = $dc_source;
    $agent_name = "Smithsonian Institution - North American Mammals";
    if($agent_name != "")
    {
        $agentz = array(0 => array( "role" => "project" , "homepage" => "http://www.mnh.si.edu/mna/main.cfm" , $agent_name) );
        $agents = array();
        foreach($agentz as $agent)
        {  
            $agentParameters = array();
            $agentParameters["role"]     = $agent["role"];
            $agentParameters["homepage"] = $agent["homepage"];
            $agentParameters["logoURL"]  = "";
            $agentParameters["fullName"] = $agent[0];
            $agents[] = new \SchemaAgent($agentParameters);
        }
        $dataObjectParameters["agents"] = $agents;
    }

    $dataObjectParameters["audiences"] = array();
    $audienceParameters = array();
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);

    if($reference)
    {
      $references = array();
      $referenceParameters = array();
      $reference = utf8_encode($reference);
      $referenceParameters["fullReference"] = $reference;
      $references[] = new \SchemaReference($referenceParameters);
      $dataObjectParameters["references"] = $references;
    }

    return $dataObjectParameters;
}

?>