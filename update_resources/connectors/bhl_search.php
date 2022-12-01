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
-> this will generate in /resources/reports/BHL/
    - file [entities_brachypterous.jsonl]
    - file [corpus_brachypterous.txt]
    - folder [pages_brachypterous]

Step:
Copy these 2:   - file [corpus_brachypterous.txt]
                - folder [pages_brachypterous]
To:             /textmine_data_brachypterous/data_BHL/

Step:
Copy this:  - file [entities_brachypterous.jsonl]
To:         /textmine_data_archive/data_text/

Step 2:
python process_entities_file.py
-> enter term in this .py file "saproxylic" or "brachypterous"
-> this will flag the entities file [entities_brachypterous.jsonl] which names are "plant or fungi".
And generates a new file [entities_brachypterous_upd.jsonl]
-> you can then copy [entities_brachypterous_upd.jsonl] to [entities_brachypterous.jsonl]. Overwritting the latter.

Step: copy [entities_brachypterous.jsonl] to: /textmine_data_brachypterous/data_BHL/entities_brachypterous.jsonl


Step 3: python divide_corpus.py
-> generate /parts_brachypterous/part_xxx.txt

Step 4: python textmine_loop_page.py
Step 5: python append_scinames.py
Step 6: start table-list type assertions
- assertion_lists.py
- process_list_types.py
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BHL_Download_API');
// ini_set('memory_limit','12096M'); //this can be removed and choose a caching solution. But let us try this first.
$timestart = time_elapsed();

$func = new BHL_Download_API();

/* new method --- works OK
// $str = "A. scybalarius";
// $str = "G. stercorosus";
// $str = "G. pyrenaeus";
$str = "T. nasuta"; //"E. von";//"G. vernalis";//"G. stercorosus";//"G. pyrenaeus";
$complete = $func->complete_name($str); 
exit("\n[$complete]\nstop muna\n");
*/

/*
$page_id = "52894691";
$page_id = '59914358';
// $func->GetPageMetadata(array('page_id'=>$page_id)); exit;
// e.g. 
// https://www.biodiversitylibrary.org/api3?op=GetPageMetadata&pageid=40563865&ocr=t&names=t&apikey=4ae9b497-37bf-4186-a91c-91f92b2f6e7d
*/

$search = "saproxylic";
/*
coprophagous - single-sentence done, table-list running...
androviviparous - not found in BHL

*/
// /* new block
$searches = array("oviparous", "ovoviviparous", "viviparous", "ectoparasitic", "kleptoparasitic", 
"necrophagous", "parasitic", "predatory", "saprotrophic", "carnivorous", "detritivorous", "omnivorous", 
"epigeic", "fossorial", "cursorial");
$searches = array("xylophagous");
foreach($searches as $search) {
    $func = new BHL_Download_API();
    $func->PublicationSearch($search);
}
// */

/* works OK - main program
$func->PublicationSearch($search); //this generates big corpus for a given SearchTerm
*/
exit("\n--- end PublicationSearch($search) ---\n");

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