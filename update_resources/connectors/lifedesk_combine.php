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
$lifedesks = array("afrotropicalbirds"); $final = array_merge($final, $lifedesks);    //testing...afrotropicalbirds  leptogastrinae    calintertidalinverts

/* normal operation
$lifedesks = array("drosophilidae", "mochokidae", "berry", "echinoderms", "eleodes", "empidinae");                  $final = array_merge($final, $lifedesks);
$lifedesks = array("gastrotricha", "reduviidae", "heteroptera", "capecodlife", "idorids", "evaniidae");             $final = array_merge($final, $lifedesks);
$lifedesks = array("araneoidea", "archaeoceti", "calintertidalinverts", "chileanbees", "halictidae", "nlbio");      $final = array_merge($final, $lifedesks);
$lifedesks = array("surinamewaterbeetles", "scarabaeoidea", "pipunculidae", "ncfishes", "biomarks");                $final = array_merge($final, $lifedesks);
$lifedesks = array("spiderindia", "speciesindia", "skinklink", "scarab", "nzicn", "bcbiodiversity");                $final = array_merge($final, $lifedesks);
$lifedesks = array("pterioidea", "westernghatfishes", "cephalopoda");                                               $final = array_merge($final, $lifedesks);
*/

$info['araneae']            = array('id'=>203, 'LD_domain' => 'http://araneae.lifedesks.org/', 'OpenData_title' => 'Spiders LifeDesk');
$info['eolspecies']         = array('id'=>204, 'LD_domain' => 'http://eolspecies.lifedesks.org/', 'OpenData_title' => 'EOL Rapid Response Team LifeDesk');
$info['trilobites']         = array('id'=>206, 'LD_domain' => 'http://trilobites.lifedesks.org/', 'OpenData_title' => 'Trilobites Online Database LifeDesk');
$info['indianadunes']       = array('id'=>211, 'LD_domain' => 'http://indianadunes.lifedesks.org/', 'OpenData_title' => 'Indiana Dunes Bioblitz LifeDesk');
$info['eolinterns']         = array('id'=>228, 'LD_domain' => 'http://eolinterns.lifedesks.org/', 'OpenData_title' => 'EOL Interns LifeDesk LifeDesk');
$info['psora']              = array('id'=>232, 'LD_domain' => 'http://psora.lifedesks.org/', 'OpenData_title' => 'The lichen genus Psora LifeDesk');
$info['corvidae']           = array('id'=>234, 'LD_domain' => 'http://corvidae.lifedesks.org/', 'OpenData_title' => 'Corvid Corroborree LifeDesk');
$info['plantsoftibet']      = array('id'=>241, 'LD_domain' => 'http://plantsoftibet.lifedesks.org/', 'OpenData_title' => 'Plants of Tibet LifeDesk');
$info['caprellids']         = array('id'=>242, 'LD_domain' => 'http://caprellids.lifedesks.org/', 'OpenData_title' => 'Caprellids LifeDesk LifeDesk');
$info['pleurotomariidae']   = array('id'=>268, 'LD_domain' => 'http://pleurotomariidae.lifedesks.org/', 'OpenData_title' => 'Pleurotomariidae LifeDesk');
$info['halictidae']         = array('id'=>299, 'LD_domain' => 'http://halictidae.lifedesks.org/', 'OpenData_title' => 'Halictidae LifeDesk');
$info['batrach']            = array('id'=>307, 'LD_domain' => 'http://batrach.lifedesks.org/', 'OpenData_title' => 'Batrachospermales LifeDesk');
$info['deepseafishes']      = array('id'=>308, 'LD_domain' => 'http://deepseafishes.lifedesks.org/', 'OpenData_title' => 'Deep-sea Fishes of the World LifeDesk');
$info['arczoo']             = array('id'=>322, 'LD_domain' => 'http://arczoo.lifedesks.org/', 'OpenData_title' => 'iArcZoo LifeDesk');
$info['snakesoftheworld']   = array('id'=>328, 'LD_domain' => 'http://snakesoftheworld.lifedesks.org/', 'OpenData_title' => 'Snake Species of the World LifeDesk');
$info['mexinverts']         = array('id'=>330, 'LD_domain' => 'http://mexinverts.lifedesks.org/', 'OpenData_title' => 'LifeDesk Invertebrados Marinos de México LifeDesk');
$info['rotifera']           = array('id'=>336, 'LD_domain' => 'http://rotifera.lifedesks.org/', 'OpenData_title' => 'Marine Rotifera LifeDesk');
$info['echinoderms']        = array('id'=>340, 'LD_domain' => 'http://echinoderms.lifedesks.org/', 'OpenData_title' => 'Discover Life LifeDesk');
$info['maldivesnlaccadives'] = array('id'=>346, 'LD_domain' => 'http://maldivesnlaccadives.lifedesks.org/', 'OpenData_title' => 'Maldives and Laccadives LifeDesk');
$info['thrasops']           = array('id'=>347, 'LD_domain' => 'http://thrasops.lifedesks.org/', 'OpenData_title' => 'African Snakes of the Genus Thrasops LifeDesk');
$info['afrotropicalbirds']  = array('id'=>9528, 'LD_domain' => 'http://afrotropicalbirds.lifedesks.org/', 'OpenData_title' => 'Afrotropical birds in the RMCA LifeDesk');
$info['philbreo']           = array('id'=>16553, 'LD_domain' => 'http://philbreo.lifedesks.org/', 'OpenData_title' => 'Amphibians and Reptiles of the Philippines LifeDesk');
$info['diptera']            = array('id'=>111622, 'LD_domain' => 'http://diptera.lifedesks.org/', 'OpenData_title' => 'EOL Rapid Response Team Diptera LifeDesk');
$info['leptogastrinae']     = array('id'=>219, 'LD_domain' => 'http://leptogastrinae.lifedesks.org/', 'OpenData_title' => 'Leptogastrinae LifeDesk');

/* this works OK. but was decided not to add ancestry if original source doesn't have ancestry. Makes sense.
$ancestry['afrotropicalbirds'] = array('kingdom' => 'Animalia', 'phylum' => 'Chordata', 'class' => 'Aves'); 
*/

/* un-comment in normal operation
$final = array_merge($final, array_keys($info)); 
*/

// /* normal operation
foreach($final as $ld) {
    /*
    $params[$ld]["remote"]["lifedesk"]      = "http://" . $ld . ".lifedesks.org/eol-partnership.xml.gz";
    $params[$ld]["remote"]["name"]          = $ld;
    */
    $params[$ld]["local"]["lifedesk"]       = "http://localhost/cp_new/LD2EOL/" . $ld . "/eol-partnership.xml.gz";
    $params[$ld]["local"]["lifedesk"]       = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/LD2EOL/$ld/eol-partnership.xml.gz";
    $params[$ld]["local"]["name"]           = $ld;
    $params[$ld]["local"]["ancestry"]       = @$ancestry[$ld];
}
$final = array_unique($final);
print_r($final); echo "\n".count($final)."\n"; //exit;
$cont_compile = false;

foreach($final as $lifedesk) {
    $taxa_from_orig_LifeDesk_XML = array(); //https://eol-jira.bibalex.org/browse/DATA-1569?focusedCommentId=62081&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62081
    $taxa_from_orig_LifeDesk_XML = $func1->export_lifedesk_to_eol($params[$lifedesk]["local"]);
    if(Functions::url_exists($params[$lifedesk]["local"]["lifedesk"])) {
        convert_xml_2_dwca("LD_".$lifedesk); //convert XML to DwCA
        $cont_compile = true;
    }

    // start generate the 2nd DwCA -------------------------------
    $resource_id = "LD_".$lifedesk."_multimedia";
    if($collection_id = @$info[$lifedesk]['id']) { //9528;
        $func2 = new CollectionsScrapeAPI($resource_id, $collection_id);
        $func2->start($taxa_from_orig_LifeDesk_XML);
        Functions::finalize_dwca_resource($resource_id, false, false); //3rd param true means resource folder will be deleted
        $cont_compile = true;
    }
    else echo "\nNo Collection for this LifeDesk.\n";
    // end generate the 2nd DwCA -------------------------------
    
    //  --------------------------------------------------- start compiling the 2 DwCA files into 1 final DwCA --------------------------------------------------- 
    if($cont_compile) {
        $dwca_file = false;
        $resource_id = "LD_".$lifedesk."_final";
        $func2 = new DwCA_Utility($resource_id, $dwca_file); //2nd param is false bec. it'll process multiple archives, see convert_archive_files() in library DwCA_Utility.php

        $archives = array();
        /* use this if we're getting taxa info (e.g. ancestry) from Collection
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."LD_".$lifedesk."_multimedia.tar.gz")) $archives[] = "LD_".$lifedesk."_multimedia";
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."LD_".$lifedesk.".tar.gz"))            $archives[] = "LD_".$lifedesk;
        */
        // Otherwise let the taxa from LifeDesk XML be prioritized
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."LD_".$lifedesk.".tar.gz"))            $archives[] = "LD_".$lifedesk;
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."LD_".$lifedesk."_multimedia.tar.gz")) $archives[] = "LD_".$lifedesk."_multimedia";


        $func2->convert_archive_files($archives); //this is same as convert_archive(), only it processes multiple DwCA files not just one.
        unset($func2);
        Functions::finalize_dwca_resource($resource_id);
        
        /* working but removed since sometimes a LifeDesk only provides names without objects at all
        //---------------------new start generic_normalize_dwca() meaning remove taxa without objects, only leave taxa with objects in final dwca
        $tar_gz = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz";
        if(file_exists($tar_gz)) {
            $func = new DwCA_Utility($resource_id, $tar_gz);
            $func->convert_archive_normalized();
            Functions::finalize_dwca_resource($resource_id);
        }
        //---------------------new end
        */
    }
    //  --------------------------------------------------- end compiling the 2 DwCA files into 1 final DwCA --------------------------------------------------- 
}
// */


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
    
    /* u need to set this to expire now = 0 ... if there is change in ancestry information... */
    // $func->export_xml_to_archive($params, true, 60*60*24*15); // true => means it is an XML file, not an archive file nor a zip file. Expires in 15 days.
    $func->export_xml_to_archive($params, true, 0); // true => means it is an XML file, not an archive file nor a zip file. Expires now.

    Functions::finalize_dwca_resource($resource_id, false, false); //3rd param true means resource folder will be deleted
    Functions::delete_if_exists(CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".xml");
}

?>