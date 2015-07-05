<?php
namespace php_active_record;
/*
WORMS archive
Now partner provides/hosts a DWC-A file. Connector also converts Distribution text into structured data.
estimated execution time: 30 mins, excluding download time of the archive file from partner's server

                24Sep'14    20Nov'14                    1Dec'14     1Apr'15     8Apr		15Jun
agent:          [922]       948                         948         1015        1033		1044
measurement:    [1,172,968] 1,484,488   diff 311,520    293,645     383423      383981		384798
media_resource: [101,513]   102,009     diff 496        102,009     134461      134708		135570
occurrence:     [279,966]   576,055                     291,683     380880      381457		382283
reference:      [319987]    322257                      322257      550506      552080		554390
taxon:          [311866]    313006      diff 1,140      313006      539550      540877		512283
vernacular:     [42231]     42226                       42226       46654       46657		46661

[establishmentMeans] => Array
    [] => 
    [Alien] => 
    [Native - Endemic] => 
    [Native - Non-endemic] => 
    [Native] => 
    [Origin uncertain] => 
    [Origin unknown] => 
[occurrenceStatus] => Array
    [present] => 
    [excluded] => 
    [doubtful] => 

[accepted]  => 327966
[synonym]   => 211583
[count]     => 539549
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WormsArchiveAPI');
$timestart = time_elapsed();
$resource_id = 26;
$func = new WormsArchiveAPI($resource_id);
$func->get_all_taxa();
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000) // orig 1000
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
    Functions::count_resource_tab_files($resource_id);

	if($undefined_uris = Functions::get_undefined_uris_from_resource($resource_id)) print_r($undefined_uris);
    echo "\nUndefined URIs: " . count($undefined_uris) . "\n";

	require_library('connectors/DWCADiagnoseAPI');
	$func = new DWCADiagnoseAPI();
	$func->check_unique_ids($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>