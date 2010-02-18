<?php


define('DEBUG', true);
define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);
$path = "";
//if(preg_match("/^(.*\/)[^\/]+/", $_SERVER["_"], $arr)) $path = $arr[1];
include_once($path."../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];
exit;


$mysqli->begin_transaction();

$mock_collection = Functions::mock_object("Collection", array("agent_id" => Agent::find("Shorefishes of the Tropical Eastern Pacific Online Information System"), "title" => "Shorefishes of the Tropical Eastern Pacific", "vetted" => 1, "link" => "http://www.stri.org/sftep", "uri" => "FOREIGNKEY"));
$collection_id = Collection::insert($mock_collection);
$collection = new Collection($collection_id);


$collection->add_mapping('Betula pumila L.', 'http://www.wetwebmedia.com/marine/fishes/wrasses/bodianus/index.htm');

$mysqli->end_transaction();


?>