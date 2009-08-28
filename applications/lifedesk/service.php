<?php

define('MYSQL_DEBUG', 1);
define('DEBUG', 1);
define('DEBUG_TO_FILE', 1);
define("ENVIRONMENT", "production");

include_once("../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];


$function = @$_GET["function"];
$search = @$_GET["search"];
$id = @$_GET["id"];
$sid = @$_GET["sid"];
$format = @$_GET["format"];
$callback = @$_GET["callback"];
$ancestry = @$_GET["ancestry"];


if(!$function) $function = @$_POST["function"];
if(!$search) $search = @$_POST["search"];
if(!$id) $id = @$_POST["id"];
if(!$sid) $sid = @$_POST["sid"];
if(!$format) $format = @$_POST["format"];
if(!$callback) $callback = @$_POST["callback"];
if(!$ancestry) $ancestry = @$_POST["ancestry"];


$connection = new LifeDeskAPI();

$results = array();

switch($function)
{
    case "search":
        //if($mysqli) printf("Host info: %s\n", $mysqli->host_info);
        $results = $connection->search($search);
        break;
    case "details":
        $results = $connection->details($id,$ancestry);
        break;
    case "details_tcs":
        header('Content-type: text/xml');
        echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";
        echo "<DataSet xmlns='http://www.tdwg.org/schemas/tcs/1.01' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.tdwg.org/schemas/tcs/1.01 http://www.tdwg.org/standards/117/files/TCS101/v101.xsd'>\n";
        echo "  <MetaData>\n";
        echo "    <Simple>Some meta data</Simple>\n";
        echo "  </MetaData>\n";
        
        if($id) echo $connection->details_tcs($id);
        elseif($sid) echo $connection->details_tcs_synonym($sid);
        
        echo "</DataSet>";
        exit;
        break;
}








if($format=="json")
{
    header('Content-type: text/plain');
    displayJSONresult($results,$callback);
}else
{
    header('Content-type: text/xml');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n";
    echo "<results xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\">\n";
    displayXMLresult($results,"  ");
    echo "</results>";
}















function displayJSONresult($results,$callback)
{
    if($callback)
    {
        $callback = trim($callback);
        if(preg_match("/[^a-z0-9_\[\]\.]/",$callback,$arr)) $callback = "";
    }
            
    if($callback) echo $callback."(";
    echo json_encode($results);
    if($callback) echo ")";
}

function displayXMLresult($results,$prefix)
{
    while(list($key,$val)=each($results))
    {
        if(is_int($key)) $key = "value";
        
        if(is_array($val))
        {
            echo $prefix."<".$key.">\n";
            displayXMLresult($val,$prefix."  ");
            echo $prefix."</".$key.">\n";
        }else
        {
            echo $prefix."<".$key.">".htmlspecialchars($val)."</".$key.">\n";
        }
    }
}




?>