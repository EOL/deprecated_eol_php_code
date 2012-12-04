<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('LifedeskAPI');

$function = @$_REQUEST["function"];
$search = @$_REQUEST["search"];
$id = @$_REQUEST["id"];
$sid = @$_REQUEST["sid"];
$format = @$_REQUEST["format"];
$callback = @$_REQUEST["callback"];
$ancestry = @$_REQUEST["ancestry"];
$hierarchy_id = @$_REQUEST["hierarchy_id"];

$connection = new LifeDeskAPI();

$results = array();

switch($function)
{
    case "search":
        $results = $connection->search($search, $hierarchy_id);
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
