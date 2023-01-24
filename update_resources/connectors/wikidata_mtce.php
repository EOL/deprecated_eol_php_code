<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/COLLAB-1006 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server
ini_set('memory_limit','15096M'); //15096M

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
	-d "batchname=BATCH 1" \
	--data-raw 'token=$2y$10$hz0sJt78sWQZavuLhlvNBev9ACNiUK3zFaF9Mu.WJFURYPXb6LmNy' \
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

$func->create_citation_if_does_not_exist($t_citation, $t_source); exit("\n- end create_citation_if_does_not_exist() -\n");
*/

// /* create traits
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
// */

// /* run all resources
$spreadsheet = "circadian_rythm_resources_sans_pantheria.csv";
$func->run_all_resources($spreadsheet);
// */

/* works OK
$taxon = "Jimenezia";
$obj = $func->get_WD_obj_using_string($taxon, 'all');
print_r($obj); exit("\n-end test-\n");
*/

/* works OK
$taxon = "Alison";
if($func->is_instance_of_taxon($taxon)) echo "\n[$taxon] is a valid taxon.\n";
else                                    echo "\n[$taxon] is not a taxon.\n";
*/

/* spreadsheet lookup - works OK!
$func->get_WD_entity_mappings();
*/

/* parse citations: source bibliographicCitation
$refs = array();
$refs[] = "https://doi.org/10.1007/978-3-662-02604-5_50 C. N. PAGE. 1990. Ginkgoaceae. In: The families and genera of vascular plants. Volume I; Pteridophytes and Gymnosperms. K. Kubitzki, K. U. Kramer and P. S. Green, eds.";
$refs[] = "https://doi.org/10.1007/978-3-662-02604-5_54 C. N. PAGE. 1990. Araucariaceae. In: The families and genera of vascular plants. Volume I; Pteridophytes and Gymnosperms. K. Kubitzki, K. U. Kramer and P. S. Green, eds.";
$refs[] = "https://doi.org/10.1007/978-3-662-03531-3_1 Kubitzki, K. 1998. Conspectus of families treated in this volume. In: The families and genera of vascular plants. Volume IV; Flowering Plants - Monocotyledons. Alismatanae and Commelinanae (except Gramineae). K. Kubitzki, ed.";
$refs[] = "https://doi.org/10.1007/978-3-319-15332-2 E.A. Kellogg. 2015. The families and genera of vascular plants. Volume XIII; Flowering Plants: Monocots - Poaceae. K. Kubitzki, ed. Springer Nature.";
$refs[] = "https://doi.org/10.1007/978-3-662-02899-5 K. Kubitzki, J.G. Rohwer and V. Bittrich eds. 1993. The families and genera of vascular plants. Volume II; Flowering Plants: Dicotyledons. Magnoliid, Hamamelid and Caryophyllid families. Springer Nature.";
$refs[] = "https://www.wikidata.org/entity/Q116222930 Brusca, R.C. and Brusca, G.J., 2003. Invertebrates. 2nd. Sunderland, Mass.: Sinauer Associates, 936 pp.";
$func->utility_parse_refs($refs);
*/



$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>