<?php
/* connector for AmphibiaWeb */
set_time_limit(0);

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
$resource = new Resource(21);
$new_resource_path = LOCAL_ROOT . "temp/".$resource->id.".xml";

$new_resource_xml = Functions::get_remote_file($resource->accesspoint_url);
$new_resource_xml = utf8_encode($new_resource_xml);

// These may look like the same wrong characters - but they are several different wrong characters
$new_resource_xml = str_replace("", "\"", $new_resource_xml);
$new_resource_xml = str_replace("", "\"", $new_resource_xml);
$new_resource_xml = str_replace("", "-", $new_resource_xml);
/*
$new_resource_xml = str_replace(".", "", $new_resource_xml);
$new_resource_xml = str_replace(".", "", $new_resource_xml);
$new_resource_xml = str_replace(".", "", $new_resource_xml);
*/

$OUT = fopen($new_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);

unset($new_resource_xml);

print"<hr>$new_resource_path<hr>"; //exit;

$do_count=0;

$taxa = array();
$xml = simplexml_load_file($new_resource_path);
foreach(@$xml->species as $species)
{
    $amphibID = (int) trim($species->amphib_id);
    $genus = utf8_decode((string) trim($species->genus));
    $speciesName = utf8_decode((string) trim($species->species));
    $order = utf8_decode((string) trim($species->ordr));
    $family = utf8_decode((string) trim($species->family));
    $commonNames = utf8_decode((string) trim($species->common_name));
    $submittedBy = utf8_decode((string) trim($species->submittedby));
    $editedBy = utf8_decode((string) trim($species->editedby));
    $description = utf8_decode((string) trim($species->description));
    $distribution = utf8_decode((string) trim($species->distribution));
    $life_history = utf8_decode((string) trim($species->life_history));
    $trends_and_threats = utf8_decode((string) trim($species->trends_and_threats));
    $relation_to_humans = utf8_decode((string) trim($species->relation_to_humans));
    $comments = utf8_decode((string) trim($species->comments));
    
    $description = str_replace("\n"," ",$description);
    while(preg_match("/<p> *<p>/i",$description)) $description = preg_replace("/<p> *<p>/i","<p>",$description);
    $description = str_replace("  "," ",$description);
    
    $distribution = str_replace("\n"," ",$distribution);
    while(preg_match("/<p> *<p>/i",$distribution)) $distribution = preg_replace("/<p> *<p>/i","<p>",$distribution);
    $distribution = str_replace("  "," ",$distribution);
    
    $life_history = str_replace("\n"," ",$life_history);
    while(preg_match("/<p> *<p>/i",$life_history)) $life_history = preg_replace("/<p> *<p>/i","<p>",$life_history);
    $life_history = str_replace("  "," ",$life_history);
    
    $trends_and_threats = str_replace("\n"," ",$trends_and_threats);
    while(preg_match("/<p> *<p>/i",$trends_and_threats)) $trends_and_threats = preg_replace("/<p> *<p>/i","<p>",$trends_and_threats);
    $trends_and_threats = str_replace("  "," ",$trends_and_threats);
    
    $relation_to_humans = str_replace("\n"," ",$relation_to_humans);
    while(preg_match("/<p> *<p>/i",$relation_to_humans)) $relation_to_humans = preg_replace("/<p> *<p>/i","<p>",$relation_to_humans);
    $relation_to_humans = str_replace("  "," ",$relation_to_humans);
    
    $comments = str_replace("\n"," ",$comments);
    while(preg_match("/<p> *<p>/i",$comments)) $comments = preg_replace("/<p> *<p>/i","<p>",$comments);
    $comments = str_replace("  "," ",$comments);    
  
    
    $pageURL = "http://amphibiaweb.org/cgi/amphib_query?where-genus=".$genus."&where-species=".$speciesName."&account=amphibiaweb";
    
    if(!$submittedBy) continue;
    
    $agents = array();
    if($submittedBy)
    {
        $parts = preg_split("/(,| and )/",$submittedBy);
        while(list($key,$val)=each($parts))
        {
            $val = trim($val);
            if(!$val) continue;
            
            $agentParameters = array();
            $agentParameters["role"] = "author";
            $agentParameters["fullName"] = $val;
            
            $agents[] = new SchemaAgent($agentParameters);
        }
    }
    
    $nameString = trim($genus." ".$speciesName);
    
	//==================================================================================================
	
    $taxonParameters = array();
    $taxonParameters["identifier"] = $amphibID;
    $taxonParameters["source"] = $pageURL;
    $taxonParameters["kingdom"] = "Animalia";
    $taxonParameters["phylum"] = "Chordata";
    $taxonParameters["class"] = "Amphibia";
    $taxonParameters["order"] = $order;
    $taxonParameters["family"] = $family;
    $taxonParameters["scientificName"] = $nameString;
    $taxonParameters["dataObjects"] = array();
    
    $dataObjects = array();
    if($distribution) 		$dataObjects[] = get_data_object("Distribution and Habitat", $distribution, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution");
    if($life_history) 		$dataObjects[] = get_data_object("Life History, Abundance, Activity, and Special Behaviors", $life_history, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Trends");
    if($trends_and_threats) $dataObjects[] = get_data_object("Life History, Abundance, Activity, and Special Behaviors", $trends_and_threats, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Threats");
    if($relation_to_humans) $dataObjects[] = get_data_object("Relation to Humans", $relation_to_humans, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#RiskStatement");    

    if(trim($description)!="")
    {
        if(trim($comments)!="")$description .= "<br>&nbsp;<br>" . $comments;
    }
    else
    {
        if(trim($comments)!="")$description = $comments;    
    }    
    if($description)$dataObjects[] = get_data_object("Description", $description, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription");
    
    //if($comments) 			$dataObjects[] = get_data_object("Comments", $comments, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription");        
    
    foreach($dataObjects as $k => $v)
    {
        $taxonParameters["dataObjects"][] = new SchemaDataObject($v);
        unset($v);
    }
    
    $taxa[] = new SchemaTaxon($taxonParameters);
}


function get_data_object($title, $description, $subject)
{
    global $resource;
    global $pageURL;
    global $agents;
    global $do_count;
    
    $do_count++;
    
    $dataObjectParameters = array();
    $dataObjectParameters["identifier"] = "do_" . $do_count;        
    $dataObjectParameters["title"] = $title;
    $dataObjectParameters["description"] = $description;
    $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";
    $dataObjectParameters["mimeType"] = "text/plain";
    $dataObjectParameters["language"] = "en";
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by/3.0/";
    $dataObjectParameters["source"] = $pageURL;
    $dataObjectParameters["agents"] = $agents;
    $dataObjectParameters["audiences"] = array();
    
    $audienceParameters = array();
    $audienceParameters["label"] = "Expert users";
    $v["audiences"][] = new SchemaAudience($audienceParameters);
    $audienceParameters["label"] = "General public";
    $v["audiences"][] = new SchemaAudience($audienceParameters);

    $dataObjectParameters["subjects"] = array();
    $subjectParameters = array();
    $subjectParameters["label"] = $subject;
    $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);

    return $dataObjectParameters;
}


////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($taxa);

$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";

$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

//print "<hr>removed: $new_resource_path";

shell_exec("rm ".$new_resource_path);

?>