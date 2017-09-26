<?php
namespace php_active_record;
/* This is a specific connector for the mineralogy spreadsheet where the worksheets were exported to tab-delimited text files using MS Spreadsheet for Mac (DATA-1619). This is one-time export.
execution time: 7 minutes

                        2015Jun4    Jun30
measurement_or_fact     986312      986312
occurrence              322805      322136
reference               64          64
taxon                   293068      293067
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EOLSpreadsheetToArchiveAPI');
$timestart = time_elapsed();

/* original resource: mineralogy
$params['text_files_path'] = 'http://localhost/cp/SpreadsheetToArchive/mineralogy 2015/';
$params['extensions'] = array('taxa', 'occurrences', 'measurements', 'references');
$resource_id = 799;
*/

// /* Sarah Miller's big spreadsheets
// $params['text_files_path'] = 'http://localhost/eol_php_code/public/tmp/xls/big/WWF 2/';
// $params['extensions'] = array('taxa', 'occurrences', 'measurements');
// $resource_id = "WWF 2";

// $params['text_files_path'] = 'http://localhost/eol_php_code/public/tmp/xls/big/WWF/';
// $params['extensions'] = array('taxa', 'occurrences', 'measurements');
// $resource_id = "WWF";

// $params['text_files_path'] = 'http://localhost/eol_php_code/public/tmp/xls/big/Avian body sizes in relation to fecundity, mating system, display behavior, and resource sharing Export/';
// $params['extensions'] = array('taxa', 'occurrences', 'measurements', 'references');
// $resource_id = "Avian body sizes in relation to fecundity, mating system, display behavior, and resource sharing Export";

// $params['text_files_path'] = 'http://localhost/eol_php_code/public/tmp/xls/big/WWF Habitats/';
// $params['extensions'] = array('taxa', 'occurrences', 'measurements');
// $resource_id = "WWF Habitats";


// /*
// https://www.dropbox.com/s/k5yzq5jv5hd1p2s/WFF Regions version 2.xlsx?dl=0
$params['text_files_path'] = 'http://localhost/eol_php_code/public/tmp/xls/big/WFF Regions version 2/';
$params['extensions'] = array('taxa', 'occurrences', 'measurements');
$resource_id = "WFF Regions version 2";
// */

/*
[WWF Habitats](https://www.dropbox.com/s/dlybjsx410h90rh/WWF Habitats.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/WWF_Habitats.tar.gz)
[Avian body sizes in relation to fecundity%2C mating system, display behavior, and resource sharing Export](https://www.dropbox.com/s/bnrbmrttgithwa1/Avian body sizes in relation to fecundity%2C mating system%2C display behavior%2C and resource sharing Export.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Avian_body_sizes_in_relation_to_fecundity%2C_mating_system%2C_display_behavior%2C_and_resource_sharing_Export.tar.gz)
[WWF.xlsx](https://www.dropbox.com/s/k49tww9xgb2xd8k/WWF.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/WWF.tar.gz)
[WWF 2.xlsx](https://www.dropbox.com/s/u17km6pnylf6cx3/WWF 2.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/WWF_2.tar.gz)
*/


// [xxx](spreadsheet) is VALID - [DWC-A](dwca)
// */

/*
https://www.dropbox.com/s/0rfmqrj97v6e37y/Dragonflies Measurements 2.xlsx?dl=0
$params['text_files_path'] = 'http://localhost/eol_php_code/public/tmp/xls/big/Dragonflies Measurements 2/';
$params['extensions'] = array('taxa', 'occurrences', 'measurements', 'references');
$resource_id = "Dragonflies Measurements 2";

8 April 2016
[Dragonflies Measurements 2.xlsx](https://www.dropbox.com/s/0rfmqrj97v6e37y/Dragonflies Measurements 2.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Dragonflies_Measurements_2.tar.gz)
*/


$resource_id = str_replace(" ", "_", $resource_id);
$func = new EOLSpreadsheetToArchiveAPI($resource_id);
$func->convert_spreadsheet_text_to_dwca($params);
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
