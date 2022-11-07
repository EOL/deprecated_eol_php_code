<?php
namespace php_active_record;
/* This will be an all-in-one BHL download facility. 
Note: ConvertioAPI.php - for converting PDF to text.

Part - consists of multiple pages

BHL API: 
https://about.biodiversitylibrary.org/tools-and-services/developer-and-data-tools/#APIs

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
// $func->GetPageMetadata(array('page_id'=>$page_id)); exit;

$search = "brachypterous";
// $search = "saproxylic";
// /* works OK
$func->PublicationSearch($search); exit("\n--- end PublicationSearch($search) ---\n");
// */

$idtype = 'bhl';
$item_id = '269395';
$item_id = '262632';
$item_id = '292464';
$func->GetItemMetadata(array('item_id'=>$item_id, 'idtype'=>$idtype)); exit("\n--- end GetItemMetadata() ---\n"); //with OcrText and with multiple pages
// https://www.biodiversitylibrary.org/api3?op=GetItemMetadata&id=285968&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d
// https://www.biodiversitylibrary.org/api3?op=GetItemMetadata&id=135948&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d
                   // https://www.biodiversitylibrary.org/itempdf/135948
                   // https://www.biodiversitylibrary.org/item/135948

https://www.biodiversitylibrary.org/api3?op=GetItemMetadata&id=292464&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d
https://www.biodiversitylibrary.org/api3?op=GetTitleMetadata&id=177982&idtype=bhl&items=t&format=xml&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d


$part_id = '263683';
// $part_id = '241705'; //has external URL
// $part_id = '98698'; //has external URL
$part_id = '303321';
$idtype = 'bhl';
$func->GetPartMetadata(array('part_id'=>$part_id, 'idtype'=>$idtype)); //1 object (part) result, no OcrText yet, but with multiple pages
?>