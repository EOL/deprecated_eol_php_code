<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/COLLAB-1006 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server
ini_set('memory_limit','15096M'); //15096M

require_library('connectors/WikiDataMtce_ResourceAPI');
require_library('connectors/WikiDataMtceAPI');
$func = new WikiDataMtceAPI();

/*
Q37887397 TAB P214 TAB "96480189"
https://quickstatements.toolforge.org/#/v1=

Q4115189|P4000|Q13184|S854|"https://eol.org/pages/1109554"

-------------------------------------------------------------
Hi Jen,
Update:
I'm able to add 2 traits using an input file with these 2 rows:
Q4115189|P4000|Q13184|S854|"https://eol.org/pages/1109554"
Q4115189|P9714|Q155

These 2 rows actually means:
[Wikidata Sandbox] [has fruit type] [berry] [reference URL] [-value-]
[Wikidata Sandbox] [taxon range] [Brazil]

I added the traits to a [Wikidata SandBox] entity. Only used for testing. Obviously not "an instance of a taxon".
See here: https://www.wikidata.org/wiki/Q4115189

All steps can be done by running a script that will do these:
1. query our Graph database
2. generate an input file for QuickStatements.
3. match EOL taxon with WikiData entity (e.g. Gadus morhua -> Q199788)
4. script then finally submits the file(s) to QuickStatements.

So pasting of actual spreadsheet data to forms by users can actually be avoided.
But this assumes that we already have mapping of EOL entities (taxa, predicates, values) to WikiData IDs.
Anyway some values can be plain strings/numbers. Like the [reference URL] above.
Thanks.
-------------------------------------------------------------
curl https://quickstatements.toolforge.org/api.php \
	-d action=import \
	-d submit=1 \
	-d username=Eagbayani \
	-d "batchname=Eli batch3" \
	--data-raw 'token=$2y$10$dEhijZQf/c3kTQGFAlUKj.JMs2Qdb4N/UUl/eFOyHVZ1sAu6vVGnS' \
	--data-urlencode data@test.qs

    {"status":"OK","debug":{"format":"v1","temporary":false,"openpage":0},"batch_id":108278}
	-> used in initial review

curl https://quickstatements.toolforge.org/api.php \
	-d action=import \
	-d submit=1 \
	-d username=EOLTraits \
	-d "batchname=Removal 1" \
	--data-raw 'token=$2y$10$hz0sJt78sWQZavuLhlvNBev9ACNiUK3zFaF9Mu.WJFURYPXb6LmNy' \
	--data-urlencode data@removal.qs

	--data-urlencode data@test.qs

https://quickstatements.toolforge.org/api.php?action=import&submit=1&username=EOLTraits&token=%242y%2410%24hz0sJt78sWQZavuLhlvNBev9ACNiUK3zFaF9Mu.WJFURYPXb6LmNy&format=v1&data=Q4115189%7CP9714%7CP155&compress=0&batchname=eol_traits_sbox_1


https://quickstatements.toolforge.org/api.php?action=import&submit=1&username=Eagbayani&token=%242y%2410%24dEhijZQf%2Fc3kTQGFAlUKj.JMs2Qdb4N%2FUUl%2FeFOyHVZ1sAu6vVGnS&format=v1&data=Q4115189%7CP4000%7CQ13184%7CS854%7C%22https%3A%2F%2Feol.org%2Fpages%2F1109554%22&batchname=Eli_batch1
{"status":"OK","debug":{"format":"v1","temporary":false,"openpage":0},"batch_id":108266}

Hi Katja,
Thanks for guide on how to use identifier-map for taxa mappings.
https://eol-jira.bibalex.org/browse/COLLAB-1006?focusedCommentId=67209&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67209
And yes, for those blank p.canonical I will generate a separate EOL-id, wikidata-id file mapping 
based on identifier-map for every processed query.


USING DOI:
Check if DOI already exists.
e.g. DOI https://doi.org/10.1111/j.1469-185X.1984.tb00411.x
use service: 
https://sourcemd.toolforge.org/index_old.php?id=10.1111%2Fj.1469-185X.1984.tb00411.x&doit=Check+source

https://doi.org/10.1007/978-3-662-02899-5

https://sourcemd.toolforge.org/index_old.php?id=10.1007/978-3-662-02899-5&doit=Check+source

en:Flowering Plants · Dicotyledons
DOI [P356]	:	"10.1007/978-3-662-02899-5"
instance of [P31]	:	scholarly article [Q13442814]
title [P1476]	:	en:"Flowering Plants · Dicotyledons"
publication date [P577] :	1993

CREATE
LAST	P356	"10.1007/978-3-662-02899-5"
LAST	P31	Q13442814
LAST	P1476	en:"Flowering Plants · Dicotyledons"
LAST	Len	"Flowering Plants · Dicotyledons"
LAST	P577	+1993-00-00T00:00:00Z/9


-------------------------------
Polar Bear - Catalogue of Life - Reference - DOI
DOI: 
https://www.wikidata.org/wiki/Property:P356

https://quickstatements.toolforge.org/api.php
	?action=import
	&submit=1
	&username=Eagbayani
	&token=%242y%2410%24dEhijZQf%2Fc3kTQGFAlUKj.JMs2Qdb4N%2FUUl%2FeFOyHVZ1sAu6vVGnS
	&format=v1
	&data=Q4115189%7CP9714%7CP155
	&compress=0
	&batchname=Eli_batch2
*/
/*
$param = "Q4115189|P4000|Q13184|S854|".'"https://eol.org/pages/1109554"';
// [sandbox]  [has fruit type]  [berry] [reference URL] [-value-]
$param = "Q4115189|P9714|Q155";
//  [sandbox] [taxon range]   [Brazil]
// -> sandbox test
# replace TAB with "|" and newline with "||"
$param = str_replace("\t", "|", $param);
$param = str_replace("\n", "||", $param);
# apply URL encoding to the string, which will replace "|" with "%7C", 
# double quote with "%22", space with "%20", slash "/" with "%2F", etc.
$param = urlencode($param);
# put https://quickstatements.toolforge.org/#/v1= in front of it
$pre_url = "https://quickstatements.toolforge.org/#/v1=";
$url = $pre_url.$param;
exit("\n[$url]\n");
*/

/*
// $t_citation = "Paul, C.R.C. and Smith, A.B., 1984. The early radiation and phylogeny of echinoderms. Biological Reviews, 59(4), pp.443-481.";
// $t_source = "https://doi.org/10.1111/j.1469-185X.1984.tb00411.x";
// $t_citation = "J. Kuijt, B. Hansen. 2014. The families and genera of vascular plants. Volume XII; Flowering Plants: Eudicots - Santalales, Balanophorales. K. Kubitzki (ed). Springer Nature";
// $t_source = ""; //none
// $citation = "Henry, Meghan, et al. The 2020 Annual Homeless Assessment Report (AHAR) to Congress. U.S. Dept. of Housing and Urban Development, Jan. 2021, https://www.huduser.gov/portal/sites/default/files/pdf/2020-AHAR-Part-1.pdf.";
// $citation = "The Big Lebowski. Directed by Joel Coen, performances by Jeff Bridges and Julianne Moore, Polygram Filmed Entertainment, 1998. Las Vegas, Nevada";

// $t_citation = "Fornoff, Felix; Dechmann, Dina; Wikelski, Martin. 2012. Observation of movement and activity via radio-telemetry reveals diurnal behavior of the neotropical katydid Philophyllia Ingens (Orthoptera: Tettigoniidae). Ecotropica, 18 (1):27-34";
// $t_source = ""; //none

$t_citation = "McDermott, F. (1964). The Taxonomy of the Lampyridae (Coleoptera). Transactions of the American Entomological Society (1890-), 90(1), 1-72. Retrieved January 29, 2021, from http://www.jstor.org/stable/25077867";
$t_source = "https://www.wikidata.org/entity/Q116263059"; //none

$t_citation = "L. Desutter-Grandcolas. 1995. Toward the Knowledge of the Evolutionary Biology of Phalangopsid Crickets (Orthoptera: Grylloidea: Phalangopsidae): Data, Questions and Evolutionary Scenarios. Journal of Orthoptera Research, No. 4 (Aug., 1995), pp. 163-175";
$t_source = "https://doi.org/10.2307/3503472";


$t_source = "https://doi.org/10.1007/978-1-4020-6359-6_3929";
$t_citation = "Heppner J.B. (2008) Valdivian Archaic Moths (Lepidoptera: Heterobathmiidae). In: Capinera J.L. (eds) Encyclopedia of Entomology. Springer, Dordrecht. https://doi.org/10.1007/978-1-4020-6359-6_3929";

$func->create_citation_if_does_not_exist($t_citation, $t_source); exit("\n- end create_citation_if_does_not_exist() -\n");
// $func->create_WD_for_citation($t_citation, $t_source); exit("\n- end create_WD_for_citation() -\n");
*/

/* create traits
$citation = "Fornoff, Felix; Dechmann, Dina; Wikelski, Martin. 2012. Observation of movement and activity via radio-telemetry reveals diurnal behavior of the neotropical katydid Philophyllia Ingens (Orthoptera: Tettigoniidae). Ecotropica, 18 (1):27-34"; # 1st group
// $citation = "McDermott, F. (1964). The Taxonomy of the Lampyridae (Coleoptera). Transactions of the American Entomological Society (1890-), 90(1), 1-72. Retrieved January 29, 2021, from http://www.jstor.org/stable/25077867"; //2nd group
$input = array();
$input["params"] = array("citation" => $citation);
$input["type"] = "wikidata_base_qry_citation";
$input["per_page"] = 500; // 500 worked ok
$input["trait kind"] = "trait"; //only 2 recs here
// $input["trait kind"] = "inferred_trait";
// $func->create_WD_traits($input); exit("\n-end create_WD_traits() -\n");
$func->divide_exportfile_send_2quickstatements($input); exit("\n-end divide_exportfile_send_2quickstatements() -\n");
*/

// print_r(pathinfo("/opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/cypher/2b2b74fb3966af72c6eb34ec9855f626/export_file.qs"));
// exit;

// Q16757851|P9566|Q101029366|S3452|Q90856597 /*Pseudohemihyalea edwardsii|diel cycle|nocturnal|inferred from|Phylogenomics reveals the... */

/*
require_library('connectors/DwCA_RunGNParser');
$gnparser = new DwCA_RunGNParser(false, 'gnparser', false);
$sciname = "[Scytonema hofmanni] UTEX B 1581";
$sciname = str_replace(array("[", "]"), "", $sciname);
$canonical = $gnparser->run_gnparser($sciname, 'simple');
exit("\n[$canonical]\n[$sciname]\n");
*/

/* Manual adjustments/fix in WD
- We have now deleted all statements (traits) written to WikiData with reference == https://www.wikidata.org/wiki/Q90856597
From User_talk:EOLTraits

- All records from Flora which use P183 before are now using P9714

- Flora do Brasil and Kubitzki are being run the 2nd time around to fix the errors due to server load. QuickStatements

Will see how to best accomplish the 2 adjustments:
--- will now use only P248. Will no longer use P3452. Have everything that is written to WD to use P248.
--- will re-run query for Kawahara et al. Generate a new export file. Will expect to be half the size of the original. 
Then will start writing to WD. This was the resource we've recently removed from WD.
*/

/* ========== run all resources - MAIN OPERATION ==========
// exit;
$spreadsheet = "circadian_rythm_resources_sans_pantheria.csv";
$spreadsheet = "resources_list.csv"; //run per resource --- e.g. "Flora do Brasil (753)" "Kubitzki et al (822)"

// $func->run_all_resources($spreadsheet, 'generate trait reports'); //generate trait reports // orig
// $func->run_all_resources($spreadsheet, 'generate trait reports', 'pnas'); //generate trait reports
$func->run_all_resources($spreadsheet, 'generate trait reports', '822'); //generate trait reports


// $func->run_all_resources($spreadsheet, 'create WD traits', 753); //read export file and send to QuickStatements
// $func->run_all_resources($spreadsheet, 'remove WD traits'); //read export file and send to QuickStatements - only for those traits in WD already.
exit("\n----- end main operation -----\n");
========== end ========== */

// 10
// Q15396511|P9714|Q388614|S3452|Q117188304

// 50
// Q17208451|P9714|Q375816|S248|Q117034902

// /Volumes/OWC_Express/resources_3/reports/cypher/ad06e5acb28ba5c95518e74182a86863/export_file.qs
// /Volumes/OWC_Express/resources_3/reports/cypher/6c401cec15f1976a46d4eb3b47cc1c48/export_file.qs


/* works OK
$taxon = "Jimenezia";
$taxon = "Posoqueria latifolia";
// $obj = $func->get_WD_obj_using_string($taxon, 'all'); print_r($obj); 
// $obj = $func->get_WD_obj_using_string($taxon, 'entity_id'); print_r($obj); 
// $obj = $func->is_instance_of_taxon($taxon); print_r($obj); 

$page_id = '1095440';
$obj = $func->get_wikidata_obj_using_EOL_pageID($page_id, $taxon); print_r($obj); 

exit("\n-end test-\n");
*/

/* works OK
$taxon = "Alison";
$taxon = "Gadus"; $taxon = "Parendacustes pahangi";
if($func->is_instance_of_taxon($taxon)) echo "\n[$taxon] is a valid taxon.\n";
else                                    echo "\n[$taxon] is not a taxon.\n";
*/

/* spreadsheet lookup - works OK!
$func->get_WD_entity_mappings();
*/

/*
$doi = "10.1073/pnas.1907847116";
$doi = "https://doi.org/10.2307/3503472";
$doi = "https://doi.org/10.1007/978-1-4020-6359-6_1885"; //no WikiData yet
$id = $func->get_WD_entityID_for_DOI($doi);
exit("\n[$id]\n");
*/

/* this entry created the entity: https://www.wikidata.org/wiki/Q116459937 using SourceMD + QuickStatements
https://sourcemd.toolforge.org/index_old.php

CREATE
LAST	P356	"10.1007/978-1-4020-6359-6_1885"
LAST	P31	Q13442814
LAST	P1476	en:"Oriental Swallowtail Moths (Lepidoptera: Epicopeiidae)"
LAST	Len	"Oriental Swallowtail Moths (Lepidoptera: Epicopeiidae)"
LAST	P304	"2693-2693"
LAST	P577	+2008-00-00T00:00:00Z/9
LAST	P2093	"John B. Heppner"	P1545	"1"
LAST	P2093	"John B. Heppner"	P1545	"2"
LAST	P2093	"Minos E. Tzanakakis"	P1545	"3"
LAST	P2093	"Minos E. Tzanakakis"	P1545	"4"
LAST	P2093	"Minos E. Tzanakakis"	P1545	"5"
LAST	P2093	"Pauline O. Lawrence"	P1545	"6"
LAST	P2093	"John L. Capinera"	P1545	"7"
LAST	P2093	"Rod Nagoshi"	P1545	"8"
LAST	P2093	"Günter Gerlach"	P1545	"9"
LAST	P2093	"Hugh Smith"	P1545	"10"
LAST	P2093	"John L. Capinera"	P1545	"11"
LAST	P2093	"John B. Heppner"	P1545	"12"
LAST	P2093	"John B. Heppner"	P1545	"13"
LAST	P2093	"James L. Nation"	P1545	"14"
LAST	P2093	"Alan A. Berryman"	P1545	"15"
LAST	P2093	"Simon R. Leather"	P1545	"16"
LAST	P2093	"John B. Heppner"	P1545	"17"
*/

/* parse citations: source bibliographicCitation - a utility for Jen
$refs = array();
$refs[] = "https://doi.org/10.1007/978-3-662-02604-5_50 C. N. PAGE. 1990. Ginkgoaceae. In: The families and genera of vascular plants. Volume I; Pteridophytes and Gymnosperms. K. Kubitzki, K. U. Kramer and P. S. Green, eds.";
$refs[] = "https://doi.org/10.1007/978-3-662-02604-5_54 C. N. PAGE. 1990. Araucariaceae. In: The families and genera of vascular plants. Volume I; Pteridophytes and Gymnosperms. K. Kubitzki, K. U. Kramer and P. S. Green, eds.";
$refs[] = "https://doi.org/10.1007/978-3-662-03531-3_1 Kubitzki, K. 1998. Conspectus of families treated in this volume. In: The families and genera of vascular plants. Volume IV; Flowering Plants - Monocotyledons. Alismatanae and Commelinanae (except Gramineae). K. Kubitzki, ed.";
$refs[] = "https://doi.org/10.1007/978-3-319-15332-2 E.A. Kellogg. 2015. The families and genera of vascular plants. Volume XIII; Flowering Plants: Monocots - Poaceae. K. Kubitzki, ed. Springer Nature.";
$refs[] = "https://doi.org/10.1007/978-3-662-02899-5 K. Kubitzki, J.G. Rohwer and V. Bittrich eds. 1993. The families and genera of vascular plants. Volume II; Flowering Plants: Dicotyledons. Magnoliid, Hamamelid and Caryophyllid families. Springer Nature.";
$refs[] = "https://www.wikidata.org/entity/Q116222930 Brusca, R.C. and Brusca, G.J., 2003. Invertebrates. 2nd. Sunderland, Mass.: Sinauer Associates, 936 pp.";
$func->utility_parse_refs($refs);
*/

/*
$label = $func->get_WD_obj_using_id("Q248924", 'label'); //Q310890 Q16521
echo("\n[$label]\n");
if(stripos($label, "taxon") !== false) { //string is found
	echo "\n OK instance of $label\n";
}
else echo "\n WRONG instance of $label\n";
*/

/* utility - run down of all citations. Create WD item for those not in WikiData yet. Works OK!
// $spreadsheet = "circadian_rythm_resources_sans_pantheria.csv"; exit("\nAll DOIs for this CSV already has a WD entry.\n"); //DONE for this csv
$spreadsheet = "resources_list.csv";
$func->run_down_all_citations($spreadsheet);
exit("\n- end run_down_all_citations() -\n");
*/

/* utility: check files sent by Katja - for corrections and mapping
echo "\n-----------------------------\n";
$fixedOnWikiData_arr = $func->get_all_ids_from_Katja_row31('fixedOnWikiData', 2);
echo "\nfixedOnWikiData_arr: ".count($fixedOnWikiData_arr)."\n";
print_r($fixedOnWikiData_arr);

echo "\n-----------------------------\n";
$arr1 = $func->get_all_ids_from_Katja_row31('remove', 1); // remove routine should be for ALL resources - By Eli.
$arr2 = $func->get_all_ids_from_Katja_row31('remove', 2); // remove routine should be for ALL resources - By Eli.
$removed_from_row_31 = array_merge($arr1, $arr2);
echo "\narr1: ".count($arr1)."\n";
echo "\narr2: ".count($arr2)."\n"; print_r($arr2);
echo "\nremoved_from_row_31: ".count($removed_from_row_31)."\n";

echo "\n-----------------------------\n";
$pairs = $func->get_all_ids_from_Katja_row31('IDcorrections', 2); // ID corrections should only for specific resource
echo "\npairs: ".count($pairs)."\n";
print_r($pairs);
*/

// /* ==================== utility --- working OK, pretty good actually.
$func = new WikiDataMtceAPI();

// -----------------------
// $func->adjust_from_P183_to_P9714(); //works OK --- runs independently --- used it for 1 client only so far, Flora do Brasil
// -----------------------
// $folder = 'b7d1eed3b50a55e116f6ce1860799580'; //17
// $folder = '235efdac07bccc11294325cdc5ba2b82'; //19
// $folder = '4db1f77b95d2b7fd9915d185d5ecc9e5'; //20
// $folder = 'e0d1a5c0bf6b3c6e12b2a63a5947bc06'; //21 done
// $folder = '09a199e19f357dec40318e3c94810cfc'; //22 done

// $folder = '963b17dddd32e409a73062283e79a806'; //23 done
// $folder = 'f2470d48aaac4639d273cdb7f19c92ce'; //24 done

// $folders = array($folder);
// $func->adjust_from_S3452_to_S248($folders); //works OK --- runs independently
// exit("\n-quick stop-\n");
// -----------------------
// $input['report for'] = "S248_31";
// $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file_S248.qs";
// $func->run_any_qs_export_file($input); //works OK --- runs independently
// exit("\n-end 1-\n");
// -----------------------

// $arr = $func->get_ancestry_given_taxon_entity('Q17601063'); print_r($arr); //test func

// ----------------------- works OK! --- for deletion
$folder = '010ec04622367d0f937d8e597c46ce9e'; //row 31
$folder = '6c401cec15f1976a46d4eb3b47cc1c48'; //Kubitzki inferred trait
$folder = '7141cc792d9cd7e39d58dd5a7262d22f'; //Kubitzki trait n=7038

// $func->prep_export_file_4deletion($folder); exit;
// $input['report for'] = "del_row_31";
// $input['report for'] = "del_822_inferred";
// $input['report for'] = "del_822_trait";
// $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file_4del.qs"; //row 31, 822 inferred
// $func->run_any_qs_export_file($input); //works OK --- runs independently
// exit("\nsss\n");
// -----------------------

$folder = '6c401cec15f1976a46d4eb3b47cc1c48'; //Kubitzki inferred trait | no need to adjust export_file.qs. Can proceed with write.
// $folder = '7141cc792d9cd7e39d58dd5a7262d22f'; //Kubitzki trait
$folder = '010ec04622367d0f937d8e597c46ce9e'; //row_31 running --- hits: 260744 -> 521488

$folders = array($folder);
// $func->adjust_del_row_then_add($folders); //works OK --- runs independently
// exit("\n-quick stop-\n");

$input['report for'] = "DelAdd_trait";
$input['report for'] = "822_inferred";
$input['report for'] = "row_31";

$input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file_DelAdd.qs";	//DelAdd_trait
$input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file.qs";		//822_inferred row_31

$func->run_any_qs_export_file($input); //works OK --- runs independently
exit("\n-end 02-\n");
// ==================== */

        /* currently being done...
        - deletion of row 31 --- DONE
		- now S248 row 31 --- running

		- DelAdd trait of Kubitzki --- DONE
		- inferred trait Kubitzki --- running

		- now S248 row 23 --- DONE
        - confirmation of Flora do Brasil --- DONE
        */


// -Q13536186|P9566|Q101029366|S3452|Q90856597
// https://www.wikidata.org/wiki/Q13536186
// Q13536186|P9566|Q4284186|S248|Q105622564
// Q13536186|P9566|Q4284186|S304|22



$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>