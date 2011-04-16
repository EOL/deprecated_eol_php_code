<?php
exit
/* North American Mammals 
estimated execution time: 1-2 minutes

Connector will not be ran from BEAST because data is coming from provider's MDB.

*/
$timestart = microtime(1);

//exit;

include_once(dirname(__FILE__) . "/../../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

$resource = new Resource(1); //orig is 85 for North American Mammals
//print $resource->id; exit;

$bad_char='';

$schema_taxa = array();
$used_taxa = array();

$query="Select
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
nam_species.legend,nam_species.refs,
nam_species.adaptation

From nam_species 
inner Join nam_genus ON nam_species.genus_id = nam_genus.genus_id
left Join nam_family ON nam_genus.family_id = nam_family.Family_ID
left Join nam_orders ON nam_family.order_id = nam_orders.order_id
left Join nam_conservation_status ON nam_species.conservation_status_id = nam_conservation_status.id ";
//$query .= " limit 10 ";   //for debug only

$result = $mysqli->query($query);    

print "<hr>" . $result->num_rows . "<br>";

$ctr=0;
$do_cnt=0;
while($row=$result->fetch_assoc())     
{    
    $ctr++; print " $ctr - ";
    $dwc_Kingdom        = "Animalia";
    $dwc_Order          = trim($row["order_name"]);
    $dwc_Family         = trim($row["family_name"]);
    $dwc_Genus          = trim($row["genus_name"]);
    $dwc_ScientificName = trim($row["sci_name"]);
    
    //$taxon_identifier   = "NAM_" . str_ireplace(" ","_",trim($row["sci_name"]));
    $taxon_identifier   = "NAM_" . $row["species_id"];    
    
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
        
        $taxon_parameters["commonNames"] = array();
        $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => trim($row["common_name"]), "language" => "en"));
        $other_names = trim($row["other_names"]);
        if($other_names != "")
        {
            if(stripos($other_names,",")!="")
            {
                $r = explode(",",$other_names); //comma-separated to array $r
                for ($i = 0; $i <= sizeof($r)-1; $i++) 
                {
                    $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => trim($r[$i]), "language" => "en"));           
                }                        
            }
            else
            {
                $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => $other_names, "language" => "en"));
            }
        }

        $taxon_parameters["dataObjects"]= array();        
        $used_taxa[$taxon_identifier] = $taxon_parameters;
    }    
    
    $do_cnt++;
    $dc_identifier = "$taxon_identifier" . "_GenDesc";
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
    
    if($row["adaptation"] != "")$description .= "<br><br>Adaptation: $row[adaptation]";        

    if($row["links"] != "")$description .= "<br><br>Links:<br>" . str_ireplace("<br><br>", "<br>", $row["links"]);
    $reference = $row["refs"];                    
    
    $title="Description";
    $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
    $data_object_parameters = get_data_object($dc_identifier, $agent_name, $dc_source, $description, $reference, $subject, $title);       
    $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);
    
    //start another dataobject
    $description="";
    if($row["dimorphism"] != "")$description .= "Sexual Dimorphism: $row[dimorphism]";    
    
    if($description != "")$description .= "<br><br>";

    if($row["avg_length"] != "" or $row["range_length"] != "")$description .= "Length:";
    if($row["avg_length"] != "")$description .= "<br>Average: $row[avg_length]";
    if($row["range_length"] != "")$description .= "<br>Range: $row[range_length]";
    
    if($description != "")$description .= "<br><br>";

    if($row["avg_weight"] != "" or $row["range_weight"] != "")$description .= "Weight:";
    if($row["avg_weight"] != "")$description .= "<br>Average: $row[avg_weight]";
    if($row["range_weight"] != "")$description .= "<br>Range: $row[range_weight]";    

    if($description != "")
    {
        $do_cnt++;
        $dc_identifier = "$taxon_identifier" . "_Size";

        $title="Size in North America";
        $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size";                
        $data_object_parameters = get_data_object($dc_identifier, $agent_name, $dc_source, $description, $reference, $subject, $title);       
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);
    }
    
    //end another dataobject
    
     
    // /* a second dataobject     
    if($row["conservation_status_notes"] != "")
    {
        $do_cnt++;
        $dc_identifier = "$taxon_identifier" . "_ConservationStatus";
        $description = $row["conservation_status_notes"];    
        $title = "Status";
        $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus";
        $data_object_parameters = get_data_object($dc_identifier, $agent_name, $dc_source, $description, $reference, $subject, $title);               
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);
    }
    // */
    
    // 3rd dataobject
    $url = "http://www.mnh.si.edu/mna/thumbnails/maps/" . str_repeat("0", 3-strlen($row["species_id"])) . $row["species_id"] . ".gif";
 	$handle = fopen($url, "r");	
	if ($handle)
	{
        $do_cnt++;
        $dc_identifier = "$taxon_identifier" . "_Distribution";
        $description = "<table border='0' align='center'><tr><td align='center'><br><img src='$url'></td></tr></table>";
        $title = "Distribution in North America";
        $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
        $data_object_parameters = get_data_object($dc_identifier, $agent_name, $dc_source, $description, $reference, $subject, $title);               
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);        	
    }    
    //
    
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

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";

exit("\n\n Done processing.");
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################


function get_data_object($id, $agent_name, $dc_source, $description, $reference, $subject, $title)
{
    $dataObjectParameters = array();       
    
    $dataObjectParameters["title"] = $title;    
    
    $dataObjectParameters["description"] = $description;
    
    $dataObjectParameters["identifier"] = $id;
    
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;
    
    $dataObjectParameters["rightsHolder"] = "Smithsonian Institution";    
    
    ///////////////////////////////////    
    $dataObjectParameters["subjects"] = array();
    $subjectParameters = array();
    $subjectParameters["label"] = $subject;
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
    $reference = utf8_encode($reference);
    //$reference = "<![CDATA[" . $reference . "]]>";    
    $referenceParameters["fullReference"] = $reference;    
    $references[] = new SchemaReference($referenceParameters);        
    $dataObjectParameters["references"] = $references;         

    ///////////////////////////////////////////////////////////////////

    return $dataObjectParameters;
}


?>