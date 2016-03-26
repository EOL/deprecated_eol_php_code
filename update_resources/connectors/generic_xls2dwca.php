<?php
namespace php_active_record;
/* This is a generic connector for a spreadsheet resource
execution time: varies on how big the spreadsheet is
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EOLSpreadsheetToArchiveAPI');
$timestart = time_elapsed();

//sample spreadsheets
$params['spreadsheet_url_path'] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 22/Barton Finkel 2013.xlsx';
$params['spreadsheet_url_path'] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 22/Barton Pershing 2013.xlsx';
$params['spreadsheet_url_path'] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 22/Olenina 2006.xlsx';
$params['spreadsheet_url_path'] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 24/EGG CHARACTERISTICS AND BREEDING SEASON FOR WOODS HOLE SPECIES.xls';

$resource_id = 1;
$func = new EOLSpreadsheetToArchiveAPI($resource_id);
$func->convert_to_dwca($params);
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
