<?php
//#!/usr/local/bin/php
/* North American Mammals connector */
exit;

define("ENVIRONMENT", "development");
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];

/*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
*/

$resource = new Resource(888);
//print $resource->id; exit;

$schema_taxa = array();
$used_taxa = array();


$query="
Select
nam_species.species_id,
nam_genus.genus_name,
concat(nam_genus.genus_name,' ',nam_species.species_name) AS `sci_name`,
nam_family.family_name,
nam_orders.order_name,

nam_species.avg_length,
nam_species.range_length,
nam_species.avg_weight,
nam_species.range_weight,

nam_species.conservation_status_notes,
nam_species.common_name,
nam_species.other_names,
nam_species.refs, nam_species.links, nam_species.dimorphism,
nam_species.legend,nam_species.refs

From nam_species 
inner Join nam_genus ON nam_species.genus_id = nam_genus.genus_id
left Join nam_family ON nam_genus.family_id = nam_family.Family_ID
left Join nam_orders ON nam_family.order_id = nam_orders.order_id
left Join nam_conservation_status ON nam_species.conservation_status_id = nam_conservation_status.id ";
//$query .= " limit 200 ";

$result = $mysqli->query($query);    

$ctr=0;
$do_cnt=0;
while($row=$result->fetch_assoc())     

{    
    $ctr++;
    $dwc_Kingdom        = "Animalia";
    $dwc_Order          = trim($row["order_name"]);
    $dwc_Family         = trim($row["family_name"]);
    $dwc_Genus          = trim($row["genus_name"]);
    $dwc_ScientificName = trim($row["sci_name"]);
    //$taxon_identifier   = str_replace(" ", "_", $dwc_ScientificName) . "_$ctr";
    $taxon_identifier   = $row["species_id"];
    
    if(@$used_taxa[$taxon_identifier])
    {
        $taxon_parameters = $used_taxa[$taxon_identifier];
    }else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"]     = $taxon_identifier;
        $taxon_parameters["kingdom"]        = $dwc_Kingdom;
        $taxon_parameters["order"]          = $dwc_Order;
        $taxon_parameters["family"]         = $dwc_Family;
        $taxon_parameters["genus"]          = $dwc_Genus;
        $taxon_parameters["scientificName"] = $dwc_ScientificName;        
        $taxon_parameters["source"]         = "http://www.mnh.si.edu/mna/image_info.cfm?species_id=" . $row["species_id"];
        
        $taxon_parameters["dataObjects"]= array();        
        $used_taxa[$taxon_identifier] = $taxon_parameters;
    }    
    
    $do_cnt++;
    $dc_identifier = "$taxon_identifier" . "_" . $do_cnt;
    //$dcterms_created = trim($xml->image->dateCreated);  
    //$dcterms_modified = trim($xml->image->dateLastModified);
    //$thumbnailURL = trim($xml->image->thumbUrl);
    $dc_source = "http://www.mnh.si.edu/mna/image_info.cfm?species_id=" . $row["species_id"];
    $agent_name = "Smithsonian Institution - North American Mammals";
    //$image_type = trim($xml->image->imageType);  
    //$copyright_text = trim($xml->image->copyrightText);
    
    //$license_text = trim($xml->image->creativeCommons);
    //$license = null;
    
    $description = $row["legend"];            
    if($row["dimorphism"] != "")$description .= "<br><br>Sexual Dimorphism: $row[dimorphism]";    
    if($row["avg_length"] != "")$description .= "<br><br>Length: <br>Average: $row[avg_length]";
    if($row["range_length"] != "")$description .= "<br>Range: $row[range_length]";
    if($row["avg_weight"] != "")$description .= "<br><br>Weight: <br>Average: $row[avg_weight]";
    if($row["range_weight"] != "")$description .= "<br>Range: $row[range_weight]";    
    if($row["links"] != "")$description .= "<br><br>Links:<br>" . str_ireplace("<br><br>", "<br>", $row["links"]);
    
    $description = str_ireplace(".", "", $description);
    $description = str_ireplace(".", "", $description);
    
    $reference = $row["refs"];            
    $reference = str_ireplace(".", "", $reference);
    $reference = str_ireplace(".", "", $reference);    
    
    $data_object_parameters = get_data_object($dc_identifier, $agent_name, $dc_source, $description, $reference);       
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

function get_data_object($id, $agent_name, $dc_source, $description, $reference)
{
    $dataObjectParameters = array();
    //$dataObjectParameters["title"] = $title;
    $dataObjectParameters["description"] = $description;
    
    $dataObjectParameters["identifier"] = $id;
    
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;
    
    $dataObjectParameters["rightsHolder"] = "Smithsonian Institution";    
    
    ///////////////////////////////////    
    $dataObjectParameters["subjects"] = array();
    $subjectParameters = array();
    $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
    $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
    ///////////////////////////////////
    
    $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";    
    $dataObjectParameters["mimeType"] = "text/html";        
    $dataObjectParameters["language"] = "en";

    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by/3.0/";
    
    //$dataObjectParameters["thumbnailURL"] = "";
    //$dataObjectParameters["mediaURL"] = "";    
    
    $dataObjectParameters["source"] = $dc_source;
    
    if($agent_name != "")
    {
        $agent = array(0 => array( "role" => "project" , "homepage" => "http://www.mnh.si.edu/mna/main.cfm" , $agent_name) );    
        $agents = array();
        foreach($agent as $agent)
        {  
            $agentParameters = array();
            $agentParameters["role"]     = $agent["role"];
            $agentParameters["homepage"] = $agent["homepage"];
            $agentParameters["logoURL"]  = "";        
            $agentParameters["fullName"] = $agent[0];
            $agents[] = new SchemaAgent($agentParameters);
        }
        $dataObjectParameters["agents"] = $agents;    
    }

    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();    
    $audienceParameters = array();
    
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
    
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
    
    ///////////////////////////////////////////////////////////////////

    $references = array();

    $referenceParameters = array();
    $referenceParameters["fullReference"] = $reference;
    $references[] = new SchemaReference($referenceParameters);    
    
    $dataObjectParameters["references"] = $references;         

    ///////////////////////////////////////////////////////////////////

    return $dataObjectParameters;
}


?>