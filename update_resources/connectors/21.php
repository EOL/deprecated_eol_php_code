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
start($resource_id); // this generates the 21.xml in CONTENT_RESOURCE_LOCAL_PATH

//start
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

// $params["eol_xml_file"] = "http://localhost/eol_php_code/applications/content_server/resources/21.xml";
$params["eol_xml_file"] = CONTENT_RESOURCE_LOCAL_PATH . "21.xml";
$params["filename"]     = "21.xml";
$params["dataset"]      = "Amphibiaweb";
$params["resource_id"]  = 21;

$resource_id = $params["resource_id"];
$func = new ConvertEOLtoDWCaAPI($resource_id);

//we need to export from XML to archive due to bad chars in XML
$func->export_xml_to_archive($params, true); //true means it is an XML file, and not zip or gzip

Functions::finalize_dwca_resource($resource_id);
unlink($params["eol_xml_file"]);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
//end

function start($resource_id)
{
    $new_resource_path = DOC_ROOT . "temp/".$resource_id.".xml";

    // $file = 'http://localhost/cp/Amphibiaweb/amphib_dump.xml';
    $file = 'http://amphibiaweb.org/amphib_dump.xml';
    if(!$new_resource_xml = Functions::lookup_with_cache($file, array('timeout' => 1200, 'download_attempts' => 5, 'expire_seconds' => 60*60*24*25))) //cache expires in 25 days
    {
        echo("\n\n Content partner's server is down, connector will now terminate.\n");
    }else
    {
        // These may look like the same wrong characters - but they are several different wrong characters
        $new_resource_xml = str_replace("", "\"", $new_resource_xml);
        $new_resource_xml = str_replace("", "\"", $new_resource_xml);
        $new_resource_xml = str_replace("", "-", $new_resource_xml);

        if(!($OUT = Functions::file_open($new_resource_path, "w+"))) return;
        fwrite($OUT, $new_resource_xml);
        fclose($OUT);
        unset($new_resource_xml);

        $taxa = array();
        $xml = simplexml_load_file($new_resource_path);
        $total = count($xml->species);

        $i=0;
        foreach(@$xml->species as $species)
        {
            $i++; if(($i % 1000) == 0) echo "\n $i of $total ";
            
            $amphibID = (int) trim($species->amphib_id);
            $genus = format_utf8((string) trim($species->genus));
            
            $speciesName = format_utf8((string) trim($species->species));
            if(!$speciesName) $speciesName = format_utf8((string) trim($species->specificepithet)); //https://github.com/EOL/eol_php_code/issues/152
            
            $order = format_utf8((string) trim($species->ordr));
            $family = format_utf8((string) trim($species->family));

            $commonNames = format_utf8((string) trim($species->common_name));
            $commonNames = explode(",", $commonNames);

            $submittedBy = format_utf8((string) trim($species->submittedby));
            $editedBy = format_utf8((string) trim($species->editedby));
            $description = format_utf8((string) trim($species->description));
            $distribution = format_utf8((string) trim($species->distribution));
            $life_history = format_utf8((string) trim($species->life_history));
            $trends_and_threats = format_utf8((string) trim($species->trends_and_threats));
            $relation_to_humans = format_utf8((string) trim($species->relation_to_humans));
            $comments = format_utf8((string) trim($species->comments));

            $ref = format_utf8((string) trim($species->refs));
            $separator = "&lt;p&gt;";
            $separator = "<p>";
            $ref = explode($separator, $ref);

            $refs = array();
            foreach($ref as $r) $refs[] = array("fullReference" => trim($r));

            $description = fix_article($description);
            $distribution = fix_article($distribution);
            $life_history = fix_article($life_history);
            $trends_and_threats = fix_article($trends_and_threats);
            $relation_to_humans = fix_article($relation_to_humans);
            $comments = fix_article($comments);

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

            foreach($commonNames as $common_name)
            {
                $taxonParameters['commonNames'][] = new \SchemaCommonName(array("name" => $common_name, "language" => "en"));
            }

            $taxonParameters["dataObjects"] = array();

            $dataObjects = array();
            if($distribution)       $dataObjects[] = get_data_object($amphibID . "_distribution","Distribution and Habitat", $distribution, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution", $refs, $agents, $pageURL);
            if($life_history)       $dataObjects[] = get_data_object($amphibID . "_life_history","Life History, Abundance, Activity, and Special Behaviors", $life_history, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Trends", $refs, $agents, $pageURL);
            if($trends_and_threats) $dataObjects[] = get_data_object($amphibID . "_trends_threats","Life History, Abundance, Activity, and Special Behaviors", $trends_and_threats, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Threats", $refs, $agents, $pageURL);
            if($relation_to_humans) $dataObjects[] = get_data_object($amphibID . "_relation_to_humans","Relation to Humans", $relation_to_humans, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#RiskStatement", $refs, $agents, $pageURL);    

            if($description != "") if($comments != "") $description .=  $comments;
            else if($comments != "" ) $description = $comments;    
            if($description) $dataObjects[] = get_data_object($amphibID . "_description", "Description", $description, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription", $refs, $agents, $pageURL);

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
        if(!($OUT = Functions::file_open($old_resource_path, "w+"))) return;
        fwrite($OUT, $new_resource_xml);
        fclose($OUT);
        // Functions::set_resource_status_to_harvest_requested($resource_id);
        shell_exec("rm ".$new_resource_path);
        // Functions::gzip_resource_xml($resource_id);
        // $elapsed_time_sec = microtime(1)-$timestart;
        // echo "\n";
        // echo "elapsed time = $elapsed_time_sec sec                 \n";
        // echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
        // echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
        // echo "\n\n Done processing.";
    }
    
}

function fix_article($article)
{
    $article = str_ireplace(array("\n", "\t", "</p>"), "", $article);
    if(substr($article, 0, 3) == "<p>") $article = trim(substr($article, 3, strlen($article)));
    $article = str_ireplace("<p>", "------", $article);

    // bring back <p> and </p>
    $article = trim(str_ireplace("------", "</p><p>", $article));
    if($article == "") return;
    $article = "<p>" . $article . "</p>";
    $article = str_ireplace(array("<br><br>", "<p></p>"), "", $article);
    $article = str_ireplace(array("<BR></p>"), "</p>", $article);

    // make <img src=''> and <a href=''> work
    $article = str_ireplace('href="/amazing_amphibians', 'href="http://amphibiaweb.org/amazing_amphibians', $article);
    $article = str_ireplace('src="/images', 'src="http://amphibiaweb.org/images', $article);

    return trim($article);
}

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

function format_utf8($str)
{
    if(Functions::is_utf8($str)) return $str;
    else return utf8_encode($str);
}
?>
