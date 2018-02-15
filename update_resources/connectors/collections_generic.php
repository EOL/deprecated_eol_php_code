<?php
namespace php_active_record;
/* 
This connector will use COLLECTIONS and dataObject API to process EOL XML:
1. convert EOL XML to DwCA
2. if there are media objects, it will use COLLECTIONS media view to get data_object_id's then use dataObject API to get object metadata
3. it will save media objects to /other_files/media/01/ or /23/. The last 2 chars of a data_object_id.
4. this will also combine 2 DwCA (IF NEEDED).
e.g.
- EOL_afrotropicalbirds.tar.gz               ---> for text objects
- EOL_afrotropicalbirds_multimedia.tar.gz    ---> for media objects

http://services.eol.org/resources/40.xml.gz
shhh quiet... - a hack in services.eol.org
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/LifeDeskToEOLAPI');
$func1 = new LifeDeskToEOLAPI();

require_library('connectors/ConvertEOLtoDWCaAPI');
require_library('connectors/CollectionsScrapeAPI');
require_library('connectors/DwCA_Utility');

$final = array();
$lifedesks = array(785); $final = array_merge($final, $lifedesks); //12 785
//==============================================================================================================================


//todo: has a dwca with text objects but media_url is offline
$info[785] = array('id' => 103533, 'domain' => 'http://www.eol.org/content_partners/700/resources/785', 'OpenData_title' => 'Inventaire National du Patrimoine Naturel', 'resource_id' => 785);
$info[785]['xml_path'] = "";
$info[785]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

$info[12] = array('id' => 174, 'domain' => 'http://www.eol.org/content_partners/12/resources/12', 'OpenData_title' => 'Initial Biolib.de Import', 'resource_id' => 12);
$info[12]['xml_path'] = "";
$info[12]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')



//==============================================================================================================================

/* this works OK. but was decided not to add ancestry if original source doesn't have ancestry. Makes sense.
$ancestry[40] = array('kingdom' => 'Animalia', 'phylum' => 'Chordata', 'class' => 'Aves'); 
*/

// /* normal operation
foreach($final as $ld) {
    $params[$ld]["local"]["lifedesk"] = $info[$ld]['xml_path'];
    $params[$ld]["local"]["name"]     = $ld;
    $params[$ld]["local"]["ancestry"] = @$ancestry[$ld];
}
$final = array_unique($final);
print_r($final); echo "\nTotal resource(s): ".count($final)."\n"; //exit;
$cont_compile = false;

foreach($final as $lifedesk) {
    $taxa_from_orig_LifeDesk_XML = array();
    $path = false;
    if(Functions::url_exists($info[$lifedesk]['xml_path'])) {
        $infox = $func1->get_taxa_from_EOL_XML($info[$lifedesk]['xml_path']);
        $taxa_from_orig_LifeDesk_XML = $infox['taxa_from_EOL_XML'];
        $path                        = $infox['xml_path']; //e.g. '/Library/WebServer/Documents/eol_php_code/tmp/dir_50900/anagetext.xml'
        // print_r($infox); exit;
        convert_xml_2_dwca($path, "EOL_".$lifedesk); //convert XML to DwCA
        $cont_compile = true;
    }

    // start generate the 2nd DwCA -------------------------------
    $resource_id = "EOL_".$lifedesk."_multimedia";
    if($collection_id = @$info[$lifedesk]['id']) { //9528;
        $func2 = new CollectionsScrapeAPI($resource_id, $collection_id, $info[$lifedesk]['data_types']);
        $func2->start($taxa_from_orig_LifeDesk_XML);
        Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means resource folder will be deleted
        $cont_compile = true;
    }
    else echo "\nNo Collection for this resource.\n";
    // end generate the 2nd DwCA -------------------------------
    
    //  --------------------------------------------------- start compiling the 2 DwCA files into 1 final DwCA --------------------------------------------------- 
    if($cont_compile) {
        $dwca_file = false;
        $resource_id = "EOL_".$lifedesk."_final";
        $func2 = new DwCA_Utility($resource_id, $dwca_file); //2nd param is false bec. it'll process multiple archives, see convert_archive_files() in library DwCA_Utility.php

        $archives = array();
        /* use this if we're getting taxa info (e.g. ancestry) from Collection
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."EOL_".$lifedesk."_multimedia.tar.gz")) $archives[] = "EOL_".$lifedesk."_multimedia";
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."EOL_".$lifedesk.".tar.gz"))            $archives[] = "EOL_".$lifedesk;
        */
        // Otherwise let the taxa from LifeDesk XML be prioritized
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."EOL_".$lifedesk.".tar.gz"))            $archives[] = "EOL_".$lifedesk;
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."EOL_".$lifedesk."_multimedia.tar.gz")) $archives[] = "EOL_".$lifedesk."_multimedia";

        $func2->convert_archive_files($archives); //this is same as convert_archive(), only it processes multiple DwCA files not just one.
        unset($func2);
        Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means it will delete workind directory e.g. /40/ inside /resources/40/
        
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

    if($path) {
        // remove temp dir
        $parts = pathinfo($path);
        recursive_rmdir($parts["dirname"]); debug("\n temporary directory removed: " . $parts["dirname"]);
    }
    
} //end foreach()
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function convert_xml_2_dwca($path, $resource_id)
{
    $params["eol_xml_file"] = $path;
    $params["filename"]     = "no need to mention here.xml";
    $params["dataset"]      = "EOL XML files";
    $params["resource_id"]  = $resource_id;
    $func = new ConvertEOLtoDWCaAPI($resource_id);
    
    /* u need to set this to expire now = 0 ... if there is change in ancestry information... */
    // $func->export_xml_to_archive($params, true, 60*60*24*15); // true => means it is an XML file, not an archive file nor a zip file. Expires in 15 days.
    $func->export_xml_to_archive($params, true, 0); // true => means it is an XML file, not an archive file nor a zip file. Expires now.

    Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means resource folder will be deleted
    Functions::delete_if_exists(CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".xml");
}

?>