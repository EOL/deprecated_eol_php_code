<?php
namespace php_active_record;
/* LifeDesk to EOL export
estimated execution time:

- Use the LifeDesk EOL XML here: http://afrotropicalbirds.lifedesks.org/eol-partnership.xml.gz --- first LD to process
- Remove the furtherInformationURL entries, or leave them blank.
- strip tags in <references>
- Then set a force-harvest using the new/updated resource XML.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/LifeDeskToEOLAPI');
$timestart = time_elapsed();
$func = new LifeDeskToEOLAPI();
// ==================================================================================================
// afrotropicalbirds remote
$params["afrotropicalbirds"]["remote"]["lifedesk"]      = "http://afrotropicalbirds.lifedesks.org/eol-partnership.xml.gz";
$params["afrotropicalbirds"]["remote"]["name"]          = "afrotropicalbirds";
// afrotropicalbirds Dropbox
$params["afrotropicalbirds"]["dropbox"]["lifedesk"]     = "";
$params["afrotropicalbirds"]["dropbox"]["name"]         = "afrotropicalbirds";
// afrotropicalbirds local
$params["afrotropicalbirds"]["local"]["lifedesk"]       = "http://localhost/~eolit/cp/LD2EOL/afrotropicalbirds/eol-partnership.xml.gz";
$params["afrotropicalbirds"]["local"]["name"]           = "afrotropicalbirds";
// ==================================================================================================

/* paste here which Lifedesk you want to export: e.g. $parameters = $params["afrotropicalbirds"]["dropbox"]; */
$parameters = $params["afrotropicalbirds"]["local"];
if($parameters) $func->export_lifedesk_to_eol($parameters);
else echo "\nNothing to process. Program will terminate\n";

/* To run them all:
$lifedesks = array("afrotropicalbirds");
foreach($lifedesks as $lifedesk) $func->export_lifedesk_to_eol($params[$lifedesk]["local"]);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>