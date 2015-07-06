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
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>