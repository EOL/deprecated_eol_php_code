<?php
namespace php_active_record;
/* This is a specific connector for the mineralogy spreadsheet where the worksheets were exported to tab-delimited text files using MS Spreadsheet for Mac (DATA-1619).
execution time: 7 minutes

					2015Jun4	Jun30
measurement_or_fact	986312		986312
occurrence		 	322805		322136
reference	 		64			64
taxon				293068		293067
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EOLSpreadsheetTextToArchiveAPI');
$timestart = time_elapsed();

$params['text_files_path'] = 'http://localhost/cp/SpreadsheetToArchive/mineralogy 2015/';
$params['extensions'] = array('taxa', 'occurrences', 'measurements', 'references');

$resource_id = 799;
$func = new EOLSpreadsheetTextToArchiveAPI($resource_id);
$func->convert_to_dwca($params);
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
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
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>