<?php
namespace php_active_record;
/* connector for AmphibiaWeb 
execution time: 2.5 minutes
Partner provided a non EOL-compliant XML file for all their species.
Connector parses this XML and generates the EOL-compliant XML.
<taxon> and <dataObject> have dc:identifier
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
$resource_id = 21;
$new_resource_path = DOC_ROOT . "temp/".$resource_id.".xml";

$file = 'http://localhost/~eolit/eol_php_code/applications/content_server/resources/amphib_dump.xml';
$file = 'http://amphibiaweb.org/amphib_dump.xml';
$new_resource_xml = file_get_contents($file);
$new_resource_xml = utf8_encode($new_resource_xml);

// These may look like the same wrong characters - but they are several different wrong characters
$new_resource_xml = str_replace("", "\"", $new_resource_xml);
$new_resource_xml = str_replace("", "\"", $new_resource_xml);
$new_resource_xml = str_replace("", "-", $new_resource_xml);

$OUT = fopen($new_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
unset($new_resource_xml);

$taxa = array();
$xml = simplexml_load_file($new_resource_path);
$total = count($xml->species);

$i=0;
foreach(@$xml->species as $species)
{
    $i++;
    print "\n $i of $total";
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

    $ref = utf8_decode((string) trim($species->refs));
    $separator = "&lt;p&gt;";
    $separator = "<p>";
    $ref = explode($separator, $ref);

    $refs = array();
    foreach($ref as $r) $refs[] = array("fullReference" => trim($r));
    
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
            $agents[] = new \SchemaAgent($agentParameters);
        }
    }
    $nameString = trim($genus." ".$speciesName);
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
    if($distribution)       $dataObjects[] = get_data_object($amphibID . "_distribution","Distribution and Habitat", $distribution, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution", $refs, $agents, $pageURL);
    if($life_history)       $dataObjects[] = get_data_object($amphibID . "_life_history","Life History, Abundance, Activity, and Special Behaviors", $life_history, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Trends", $refs, $agents, $pageURL);
    if($trends_and_threats) $dataObjects[] = get_data_object($amphibID . "_trends_threats","Life History, Abundance, Activity, and Special Behaviors", $trends_and_threats, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Threats", $refs, $agents, $pageURL);
    if($relation_to_humans) $dataObjects[] = get_data_object($amphibID . "_relation_to_humans","Relation to Humans", $relation_to_humans, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#RiskStatement", $refs, $agents, $pageURL);    

    if(trim($description) != "") if(trim($comments)!="") $description .= "<br>&nbsp;<br>" . $comments;
    else if(trim($comments) !="" ) $description = $comments;    
    if($description) $dataObjects[] = get_data_object($amphibID . "_description","Description", $description, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription", $refs, $agents, $pageURL);
    
    /* we didn't get <comments>
    if($comments)       $dataObjects[] = get_data_object("Comments", $comments, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription", $refs, $agents, $pageURL);        
    */
    
    foreach($dataObjects as $k => $v)
    {
        $taxonParameters["dataObjects"][] = new \SchemaDataObject($v);
        unset($v);
    }
    $taxa[] = new \SchemaTaxon($taxonParameters);
    //if($i >= 5) break; //debug
}

$new_resource_xml = \SchemaDocument::get_taxon_xml($taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
shell_exec("rm ".$new_resource_path);
$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");

function get_data_object($id, $title, $description, $subject, $refs, $agents, $pageURL)
{
    $dataObjectParameters = array();
    $dataObjectParameters["identifier"] = $id;
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
    $v["audiences"][] = new \SchemaAudience($audienceParameters);
    $audienceParameters["label"] = "General public";
    $v["audiences"][] = new \SchemaAudience($audienceParameters);

    $dataObjectParameters["subjects"] = array();
    $subjectParameters = array();
    $subjectParameters["label"] = $subject;
    $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);

    $rec["reference"] = $refs;
    $dataObjectParameters = Functions::prepare_reference_params($rec, $dataObjectParameters);
    return $dataObjectParameters;
}
?>
