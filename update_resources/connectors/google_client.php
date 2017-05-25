<?php
namespace php_active_record;
/* 
This will use the Google Client Library, and will access a Google Sheet
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GoogleClientAPI');
$timestart = time_elapsed();

$func = new GoogleClientAPI();
$params['spreadsheetID'] = '1-nTN2i_epQzl-rOaQJjIFbVRUfVirVKZpTEwC8kH7k8';
$params['range']         = 'Sheet1!A2:C'; //where "A" is the starting column, "C" is the ending column, and "2" is the starting row.

$params['spreadsheetID'] = '1gHdrWRaZbEKp3bCI7kXhN95le-jGvQOXXxeVpgmypJ4';
$params['range']         = 'Sheet1!A1:C'; //where "A" is the starting column, "C" is the ending column, and "2" is the starting row.

$func->access_google_sheet($params);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
