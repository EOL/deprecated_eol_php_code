<?php
namespace php_active_record;
/* LifeDesk to EOL export
estimated execution time: a few seconds per LifeDesk
- Use the LifeDesk EOL XML here: http://afrotropicalbirds.lifedesks.org/eol-partnership.xml.gz --- first LD to process
- Remove the furtherInformationURL entries, or leave them blank.
- strip tags in <references>
- Then set a force-harvest using the new/updated resource XML.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/LifeDeskToEOLAPI');
$timestart = time_elapsed();
$func = new LifeDeskToEOLAPI();

$lifedesks = array("afrotropicalbirds");
$lifedesks = array("araneae", "drosophilidae", "mochokidae", "batrach", "berry");           //DATA-1597
$lifedesks = array("gastrotricha", "reduviidae", "heteroptera", "capecodlife", "diptera");  //DATA-1599

foreach($lifedesks as $ld)
{
    $params[$ld]["remote"]["lifedesk"]      = "http://" . $ld . ".lifedesks.org/eol-partnership.xml.gz";
    $params[$ld]["remote"]["name"]          = $ld;
    $params[$ld]["dropbox"]["lifedesk"]     = "";
    $params[$ld]["dropbox"]["name"]         = $ld;
    $params[$ld]["local"]["lifedesk"]       = "http://localhost/~eolit/cp/LD2EOL/" . $ld . "/eol-partnership.xml.gz";
    $params[$ld]["local"]["name"]           = $ld;
}

foreach($lifedesks as $lifedesk) $func->export_lifedesk_to_eol($params[$lifedesk]["local"]);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>