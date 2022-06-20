<?php
namespace php_active_record;
/* This will be an all-in-one BHL download facility. 
Note: ConvertioAPI.php - for converting PDF to text.

Part - consists of multiple pages
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BHL_Download_API');
// ini_set('memory_limit','12096M'); //this can be removed and choose a caching solution. But let us try this first.
$timestart = time_elapsed();

$func = new BHL_Download_API();
$page_id = "52894701";
$page_id = "29882730";
$page_id = "47086838";
$page_id = "29947515";
$page_id = "40409488";
$page_id = "48619917";
$page_id = "52894691";
$page_id = '59914358';
// $func->GetPageMetadata($page_id); exit;

$search = "brachypterous";
$search = "saproxylic";
$func->PublicationSearch($search);

$idtype = 'bhl';
$item_id = '269395';
$item_id = '262632';
$item_id = '292464';
// $func->GetItemMetadata($item_id, $idtype); exit; //with OcrText and with multiple pages

$part_id = '263683';
// $part_id = '241705'; //has external URL
// $part_id = '98698'; //has external URL
$part_id = '303321';
$idtype = 'bhl';
// $func->GetPartMetadata($part_id, $idtype); //1 object (part) result, no OcrText yet, but with multiple pages


?>