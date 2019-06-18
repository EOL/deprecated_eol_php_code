<?php
namespace php_active_record;
/* connector for CalPhotos - Moorea Biocode EoL XML resource -- https://eol-jira.bibalex.org/browse/DATA-1618
execution time: 

Moorea Biocode (ID = 330)
Numbers from the partners XML:
taxa	images
4521	21047	(last harvest)
4528	21047	(next harvest)

330	Saturday 2019-05-25 01:31:24 AM	{"agent.tab":27,"media_resource.tab":20942,"taxon.tab":4492}
330	Saturday 2019-06-01 01:31:19 AM	{"agent.tab":27,"media_resource.tab":20942,"taxon.tab":4492} eol-archive
330	Monday 2019-06-03 12:44:21 AM	{"agent.tab":27,"media_resource.tab":20942,"taxon.tab":4492} Mac mini
330	Monday 2019-06-17 10:50:22 PM	{"agent.tab":27,"media_resource.tab":20942,"taxon.tab":4492} eol-archive -- start of adding: http://rs.tdwg.org/ac/terms/derivedFrom
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

/* first part of the operation: includes converting XML to DwCA and some additional media field 'derivedFrom' in media tab.
require_library('connectors/INBioAPI');
$resource_id = '330pre';

$xml_resource = "http://calphotos.berkeley.edu/eol_biocode.xml.gz";
// $xml_resource = "http://localhost/cp/CalPhotos/eol_biocode.xml.gz"; //local debug only

$func = new INBioAPI();
$info = $func->extract_archive_file($xml_resource, "eol_biocode.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*5)); //expires in 5 days
if(!$info) return;
print_r($info);
$temp_dir = $info['temp_dir'];

$xml_string = Functions::get_remote_file($temp_dir . "eol_biocode.xml");
$xml_string = Functions::remove_invalid_bytes_in_XML($xml_string);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($WRITE = Functions::file_open($resource_path, "w"))) return;
fwrite($WRITE, $xml_string);
fclose($WRITE);

// remove tmp dir
if($temp_dir) shell_exec("rm -fr $temp_dir");

// Functions::gzip_resource_xml($resource_id); //no longer needed as it will be converted to DwC-A
Functions::set_resource_status_to_harvest_requested($resource_id);

//start convert EOL XML to EOL DwCA
require_library('ResourceDataObjectElementsSetting');
$nmnh = new ResourceDataObjectElementsSetting($resource_id);
$nmnh->call_xml_2_dwca($resource_id, "Moorea Biocode", false); //false means not NMNH resource
*/
/*=========================start 2nd part of the connector================================
this constitues to the 'step 2' part of the DATA-1810:
Step 2 is a mapping:
-in the attached mapping file, for each record, look for the BMOO string in http://rs.tdwg.org/ac/terms/derivedFrom, in the mapping's Field Number column.
-if it appears, overwrite the taxon information for that record with the contents of the columns Family, Genus and Full Name (> scientificName)
that mapping process may be a bit weird because the media-> taxa relationship is many-> one. One thing that may help is that the taxa you will be updating should not overlap with the taxa that are not updated. So it should be safe to delete any "old" taxon record connected to a record you're updating. I think you'll want to de-duplicate the new taxon records before you assign them taxonIDs, though. If you see what I mean. Sorry this one is so complicated.
Another option you might prefer: When you identify the records with "contributor's ID # BMOO" strings at the furtherInformationURL, remove them from the existing resource, and make a new resource just for the matching records.
*/
$resource_id = 330;
require_library('connectors/MooreaBiocodeAPI');
$func = new MooreaBiocodeAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id);
$func->investigate_taxon_tab(); //just a utility to check the final taxon.tab
/*=========================end 2nd part of the connector================================*/



$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>