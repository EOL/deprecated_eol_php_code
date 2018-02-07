<?php
namespace php_active_record;
/* This will combine 2 LifeDesk DwCA e.g.
- LD_afrotropicalbirds.tar.gz
- LD_afrotropicalbirds_multimedia.tar.gz
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/LifeDeskToEOLAPI');
$func1 = new LifeDeskToEOLAPI();

require_library('connectors/ConvertEOLtoDWCaAPI');
require_library('connectors/CollectionsScrapeAPI');
require_library('connectors/DwCA_Utility');


$final = array();
$lifedesks = array("afrotropicalbirds");                                                      $final = array_merge($final, $lifedesks);    //testing...

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
    $params[$ld]["dropbox"]["lifedesk"]     = "";
    $params[$ld]["dropbox"]["name"]         = $ld;
    $params[$ld]["local"]["lifedesk"]       = "http://localhost/cp/LD2EOL/" . $ld . "/eol-partnership.xml.gz";
    $params[$ld]["local"]["name"]           = $ld;
}
$final = array_unique($final);
print_r($final); echo "\n".count($final)."\n"; //exit;
foreach($final as $lifedesk) 
{
    $func1->export_lifedesk_to_eol($params[$lifedesk]["local"]); unset($func1);
    convert("LD_".$lifedesk); //convert XML to DwCA

    // start generate the 2nd DwCA -------------------------------
    $resource_id = "LD_".$lifedesk."_multimedia";
    $collection_id = 9528; 
    $func2 = new CollectionsScrapeAPI($resource_id, $collection_id);
    $func2->start();
    Functions::finalize_dwca_resource($resource_id, false, false); //3rd param true means resource folder will be deleted
    // end generate the 2nd DwCA -------------------------------
    
    //  --------------------------------------------------- start compiling the 2 DwCA files into 1 final DwCA --------------------------------------------------- 
    $dwca_file = false;
    $resource_id = "LD_".$lifedesk."_final";
    $func2 = new DwCA_Utility($resource_id, $dwca_file); //2nd param is false bec. it'll process multiple archives, see convert_archive_files() in library DwCA_Utility.php
    $final = array("LD_".$lifedesk, "LD_".$lifedesk."_multimedia"); //e.g. this assumes this file exists => CONTENT_RESOURCE_LOCAL_PATH."LD_afrotropicalbirds.tar.gz"
    $func2->convert_archive_files($final); //this is same as convert_archive(), only it processes multiple DwCA files not just one.
    unset($func2);
    Functions::finalize_dwca_resource($resource_id);
    //  --------------------------------------------------- end compiling the 2 DwCA files into 1 final DwCA --------------------------------------------------- 
}
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function convert($resource_id)
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