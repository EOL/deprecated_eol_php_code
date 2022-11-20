<?php
namespace php_active_record;
/* This will be an all-in-one BHL download facility. 
Note: ConvertioAPI.php - for converting PDF to text.

Part - consists of multiple pages

BHL API: 
https://about.biodiversitylibrary.org/tools-and-services/developer-and-data-tools/#APIs

STEPS FOR TEXTMINING BHL:
Step 1:
php bhl_search.php
-> run PublicationSearch()
-> enter term in this .php file "saproxylic" or "brachypterous"

Step 2:
python process_entities_file.py
-> enter term in this .py file "saproxylic" or "brachypterous"
-> this will flag entities file which names are "plant or fungi"

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BHL_Download_API');
// ini_set('memory_limit','12096M'); //this can be removed and choose a caching solution. But let us try this first.
$timestart = time_elapsed();

$func = new BHL_Download_API();
$page_id = "52894701";
$page_id = "52894691";
$page_id = '59914358';
// $func->GetPageMetadata(array('page_id'=>$page_id)); exit;
// e.g. https://www.biodiversitylibrary.org/api3?op=GetPageMetadata&pageid=47086871&ocr=t&names=t&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d

$search = "brachypterous";
$search = "saproxylic";
/* works OK - main program
$func->PublicationSearch($search); exit("\n--- end PublicationSearch($search) ---\n"); //this generates big corpus for a given SearchTerm
*/
exit("\n---end PublicationSearch($search) ---\n");

/* an item has a TitleID and multiple [Pages] with [OcrText] --- works OK inside BHL_Download_API.php
$idtype = 'bhl';
$item_id = '269395';
$item_id = '262632';
$item_id = '181000';
$func->GetItemMetadata(array('item_id'=>$item_id, 'idtype'=>$idtype, 'needle'=>$search)); exit("\n--- end GetItemMetadata() ---\n"); 
exit("\n---end GetItemMetadata() ---\n");
*/

// /* Works OK - very helpful
$marker = ""; 
$start_row = "Table 1 : List of Saproxylic beetles recorded from Savernake Forest"; //"The Coleopterist, 23: 116-127"; //"Table 4—Red-listed saproxylic beetle species"; //"APPENDIX A";
$item_id = '135948'; //'255349'; //'292464'; //'233784'; //'124942'; //'285968'; //'135948'; //'179311';
$string = "Leptura sexguttata"; //"Eutheia linearis"; //"Philothermus tasmanicus"; //"Thamiaraea cinnamomea"; //"Platylomalus terrareginae"; //"Epuraea rufomarginata"; //"Leptusa pulchella"; //"Grynobius planus"; //"Plegaderus dissectus"; //"Paromalus flavicornis"; //"Abraeus globosus";
$page_id = $func->get_PageId_where_string_exists_in_ItemID($item_id, $string, $marker, $start_row);
echo "\npage_id = [$page_id]\n";
exit("\n--- end 2nd purpose GetItemMetadata() ---\n"); 
// */

//Leptura sexguttata		part_31.txt	Table 1 : List of Saproxylic beetles recorded from Savernake Forest	135948
// Platystomos albinus      part_9.txt  The Coleopterist, 23: 116-127   255349
//Eutheia linearis		part_1.txt	Table 4—Red-listed saproxylic beetle species	292464
//                                  Table 4—Red-listed saproxylic beetle species


// https://www.biodiversitylibrary.org/api3?op=GetItemMetadata&id=285968&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d
// https://www.biodiversitylibrary.org/api3?op=GetItemMetadata&id=135948&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d
                   // https://www.biodiversitylibrary.org/itempdf/135948
                   // https://www.biodiversitylibrary.org/item/135948

// https://www.biodiversitylibrary.org/api3?op=GetItemMetadata&id=292464&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d
// https://www.biodiversitylibrary.org/api3?op=GetTitleMetadata&id=177982&idtype=bhl&items=t&format=xml&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d


$part_id = '263683';
// $part_id = '241705'; //has external URL
// $part_id = '98698'; //has external URL
$part_id = '303321';
$idtype = 'bhl';
$func->GetPartMetadata(array('part_id'=>$part_id, 'idtype'=>$idtype)); //1 object (part) result, no OcrText yet, but with multiple pages
?>