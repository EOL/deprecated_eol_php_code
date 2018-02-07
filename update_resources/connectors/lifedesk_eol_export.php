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

$final = array();
$lifedesks = array("afrotropicalbirds");                                                      $final = array_merge($final, $lifedesks);    //testing...leptogastrinae

/* normal operation
$lifedesks = array("afrotropicalbirds");                                                                 $final = array_merge($final, $lifedesks);    //DATA-1516
$lifedesks = array("araneae", "drosophilidae", "mochokidae", "batrach", "berry");                        $final = array_merge($final, $lifedesks);    //DATA-1597
$lifedesks = array("gastrotricha", "reduviidae", "heteroptera", "capecodlife", "diptera");               $final = array_merge($final, $lifedesks);    //DATA-1599
$lifedesks = array("trilobites", "echinoderms", "snakesoftheworld", "pleurotomariidae", "psora");        $final = array_merge($final, $lifedesks);    //DATA-1600
$lifedesks = array("plantsoftibet", "philbreo", "rotifera", "maldivesnlaccadives", "mexinverts");        $final = array_merge($final, $lifedesks);    //DATA-1601
$lifedesks = array("araneoidea", "archaeoceti", "arczoo", "calintertidalinverts", "caprellids");         $final = array_merge($final, $lifedesks);    //DATA-1607
$lifedesks = array("chileanbees", "corvidae", "deepseafishes", "eleodes", "empidinae");                  $final = array_merge($final, $lifedesks);    //DATA-1608
$lifedesks = array("surinamewaterbeetles", "scarabaeoidea", "pipunculidae", "ncfishes", "indianadunes"); $final = array_merge($final, $lifedesks);    //DATA-1609
$lifedesks = array("idorids", "evaniidae", "eolinterns", "halictidae", "eolspecies");                    $final = array_merge($final, $lifedesks);    //DATA-1611
$lifedesks = array("spiderindia", "speciesindia", "skinklink", "scarab", "nzicn");                       $final = array_merge($final, $lifedesks);    //DATA-1612
$lifedesks = array("bcbiodiversity", "pterioidea", "halictidae", "westernghatfishes", "cephalopoda");    $final = array_merge($final, $lifedesks);    //DATA-1613
$lifedesks = array("calintertidalinverts", "biomarks", "nlbio", "thrasops");                             $final = array_merge($final, $lifedesks);    //DATA-1614
$lifedesks = array("echinoderms");                                                                       $final = array_merge($final, $lifedesks);    //DATA-1631
*/
// /* normal operation
foreach($final as $ld) {
    $params[$ld]["remote"]["lifedesk"]      = "http://" . $ld . ".lifedesks.org/eol-partnership.xml.gz";
    $params[$ld]["remote"]["name"]          = $ld;
    $params[$ld]["local"]["lifedesk"]       = "http://localhost/cp_new/LD2EOL/" . $ld . "/eol-partnership.xml.gz";
    $params[$ld]["local"]["lifedesk"]       = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/LD2EOL/$ld/eol-partnership.xml.gz";
    $params[$ld]["local"]["name"]           = $ld;
}
$final = array_unique($final);
print_r($final); echo "\n".count($final)."\n"; //exit;
foreach($final as $lifedesk) $func->export_lifedesk_to_eol($params[$lifedesk]["local"]);
// */

/* Below are steps made to accomplish this: Basically to ingest what is left with LifeDesk from EoL V2.
https://eol-jira.bibalex.org/browse/DATA-1569?focusedCommentId=62037&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62037
*/

//  --------------------------------------------------- start conversion XML to DwCA --------------------------------------------------- http://services.eol.org/resources/LD_afrotropicalbirds.xml.gz
// /* normal operation
require_library('connectors/ConvertEOLtoDWCaAPI');
foreach($final as $lifedesk) 
{
    if(Functions::url_exists($params[$lifedesk]["local"]["lifedesk"])) convert_xml_2_dwca("LD_".$lifedesk);
}
// */
//  --------------------------------------------------- end conversion XML to DwCA ---------------------------------------------------

//  --------------------------------------------------- start compiling all DwCA files into 1 final DwCA --------------------------------------------------- 
/* working OK - but was never used. Idea was good but maintenance wise it is better to have individual DwCA files.
require_library('connectors/DwCA_Utility');
$dwca_file = false;
$resource_id = "lifedesks";
$func = new DwCA_Utility($resource_id, $dwca_file); //2nd param is false bec. it'll process multiple archives, see convert_archive_files() in library DwCA_Utility.php

$final = array("LD_afrotropicalbirds", "somefile"); //e.g. this assumes this file exists => CONTENT_RESOURCE_LOCAL_PATH."LD_afrotropicalbirds.tar.gz"
$func->convert_archive_files($final); //this is same as convert_archive(), only it processes multiple DwCA files not just one.
Functions::finalize_dwca_resource($resource_id);
unset($func);
*/
//  --------------------------------------------------- end compiling all DwCA files into 1 final DwCA --------------------------------------------------- 

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function convert_xml_2_dwca($resource_id)
{
    $params["eol_xml_file"] = "http://localhost/eol_php_code/applications/content_server/resources/".$resource_id.".xml"; //e.g. LD_afrotropicalbirds
    $params["filename"]     = "no need to mention here.xml";
    $params["dataset"]      = "LifeDesk XML files";
    $params["resource_id"]  = $resource_id;
    $func = new ConvertEOLtoDWCaAPI($resource_id);
    $func->export_xml_to_archive($params, true, 60*60*24*15); // true => means it is an XML file, not an archive file nor a zip file. Expires in 15 days.
    Functions::finalize_dwca_resource($resource_id, false, false); //3rd param true means resource folder will be deleted
    Functions::delete_if_exists(CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".xml");
}

?>