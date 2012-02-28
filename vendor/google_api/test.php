<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_vendor('google_api');

$spreadsheet_tables_api = new \google_api\GoogleSpreadsheetsAPI($GLOBALS['GOOGLE_USERNAME'], $GLOBALS['GOOGLE_PASSWORD'], @$_SESSION['GOOGLE_AUTH_TOKEN'], 'Hotlist Spreadsheet Reader');

// get list of current user's spreadsheets
$response = $spreadsheet_tables_api->get_spreadsheets();
print_r($response);



/* This bit will drill down and give the contents of one sheet of one Spreadsheet Document */

// foreach($response->entry as $entry)
// {
//     if($entry->title == "SPG Hotlist Official Version")
//     {
//         $URL_for_spreadsheet = $entry->content['src'];
//         $spreadsheet_repsonse = $spreadsheet_tables_api->get_response($URL_for_spreadsheet);
//         $sheet_url = $spreadsheet_repsonse->entry->link[0]['href'];
//         $worksheet_repsonse = $spreadsheet_tables_api->get_response($sheet_url);
//         print_r($sheet_url);
//         print_r($worksheet_repsonse);
//     }
// }

?>