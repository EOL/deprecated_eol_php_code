<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/COLLAB-1006 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server

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
	-d "batchname=Eli Jan test 1" \
	--data-raw 'token=$2y$10$dEhijZQf/c3kTQGFAlUKj.JMs2Qdb4N/UUl/eFOyHVZ1sAu6vVGnS' \
	--data-urlencode data@test.qs

    {"status":"OK","debug":{"format":"v1","temporary":false,"openpage":0},"batch_id":108278}
	-> used in initial review

https://quickstatements.toolforge.org/api.php?action=import&submit=1&username=Eagbayani&token=%242y%2410%24dEhijZQf%2Fc3kTQGFAlUKj.JMs2Qdb4N%2FUUl%2FeFOyHVZ1sAu6vVGnS&format=v1&data=Q4115189%7CP4000%7CQ13184%7CS854%7C%22https%3A%2F%2Feol.org%2Fpages%2F1109554%22&batchname=Eli_batch1
{"status":"OK","debug":{"format":"v1","temporary":false,"openpage":0},"batch_id":108266}

Hi Katja,
Thanks for guide on how to use identifier-map for taxa mappings.
https://eol-jira.bibalex.org/browse/COLLAB-1006?focusedCommentId=67209&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67209
And yes, for those blank p.canonical I will generate a separate EOL-id, wikidata-id file mapping 
based on identifier-map for every processed query.

Hi Jen,
Yes, I should be able to parse bibliographicCitation string and get its difference parts if needed.
One of the three options mentioned by Katja, AnyStyle looks promising. 
I'm actually using it now and it should serve our purpose.
https://github.com/inukshuk/anystyle
{code = "Just a test"}
$ ruby run.rb "Paul, C.R.C. and Smith, A.B., 1984. The early radiation and phylogeny of echinoderms. Biological Reviews, 59(4), pp.443-481. https://doi.org/10.1111/j.1469-185X.1984.tb00411.x"
[{:author=>
   [{:family=>"Paul", :given=>"C.R.C."}, {:family=>"Smith", :given=>"A.B."}],
  :date=>["1984"],
  :title=>["The early radiation and phylogeny of echinoderms"],
  :volume=>["59"],
  :pages=>["443–481"],
  :url=>["https://doi.org/10.1111/j.1469-185X.1984.tb00411.x"],
  :type=>"article-journal",
  :"container-title"=>["Biological Reviews"],
  :issue=>["4"],
  :doi=>["10.1111/j.1469-185X.1984.tb00411.x"]}]
{code}


Hi Jen,
Please correct if I misunderstood the reference algorithm.
Using t.source, t.citation, ref.literal

* if t.source is DOI, OR t.citation string has DOI
	** use that as Reference -- DOI (P356)
	** follow USING DOI below
* else: No DOI to use.
	** if t.citation is 'significant'
		*** create an item using t.citation if it doesn't exist yet. If exists, use it.
		*** then link to it via stated in (P248)
	** else: insignificant non-DOI sources
		*** parse t.citation string and get different parts. 
		*** use whatever is available, as Reference
			**** author (P50)
			**** publisher (P123) 
			**** place of publication (P291)
			**** page(s) (P304)
			**** issue (P433)
			**** volume	(P478)
			**** publication date (P577)
			**** chapter (P792)
			**** title (P1476) 
			**** ? editor (P98)
		*** else: if t.source is non-Wikidata URL and worthy as wikidata source, use it as
			**** reference URL (P854) or retrieved (P813)

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



* if exists:
	** then link to it via stated in (P248)
	** e.g. this case: https://www.wikidata.org/wiki/Q56079384
	** P248 -- Q56079384
* else:
	** create an item.
	** then link to it via stated in (P248)

It will be easier if we can already add possible WikiData items that we will need before hand.
Thanks.


ruby run.rb "Paul, C.R.C. and Smith, A.B., 1984. The early radiation and phylogeny of echinoderms. Biological Reviews, 59(4), pp.443-481. https://doi.org/10.1111/j.1469-185X.1984.tb00411.x"

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

shell_exec("curl $latest_itis_url -o ".dirname(__FILE__)."/itis.tar.gz");
*/

/* working - good test
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

/* copied template - not used but works OK
====================================================
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
====================================================
*/
require_library('connectors/CypherQueryAPI');
$resource_id = 'eol';
$func = new CypherQueryAPI($resource_id);

/* good example
$source = "https://doi.org/10.1111/j.1469-185X.1984.tb00411.x";
$source = "https://doi.org/10.1073/pnas.1907847116";

$sources = array();
$sources[] = "https%3A%2F%2Fdoi.org%2F10.1073%2Fpnas.1907847116";
$sources[] = "https%3A%2F%2Fwww.delta-intkey.com%2Fbritin%2Flep%2Fwww%2Fendromid.htm";
$sources[] = "https%3A%2F%2Fwww.wikidata.org%2Fentity%2FQ116262527";
$sources[] = "https%3A%2F%2Fdoi.org%2F10.1111%2Fj.1365-2311.1985.tb00736.x";
$sources[] = "https%3A%2F%2Fdoi.org%2F10.1007%2F978-1-4020-6359-6_1885";
foreach($sources as $source) { $source = urldecode($source); echo "\n[$source]\n"; 
	$input["params"] = array("source" => $source);
	$input["type"] = "wikidata_base_qry_source";
	$input["per_page"] = 500; // 500 finished ok
	
	$input["trait kind"] = "trait";
	$func->query_trait_db($input);
	
	$input["trait kind"] = "inferred_trait";
	$func->query_trait_db($input);	
}
exit("\n-end-\n");

*/

/* good example
// $citation = "J. Kuijt, B. Hansen. 2014. The families and genera of vascular plants. Volume XII; Flowering Plants: Eudicots - Santalales, Balanophorales. K. Kubitzki (ed). Springer Nature";
// $citation = "Paul, C.R.C. and Smith, A.B., 1984. The early radiation and phylogeny of echinoderms. Biological Reviews, 59(4), pp.443-481.";

$citation = "Fornoff, Felix; Dechmann, Dina; Wikelski, Martin. 2012. Observation of movement and activity via radio-telemetry reveals diurnal behavior of the neotropical katydid Philophyllia Ingens (Orthoptera: Tettigoniidae). Ecotropica, 18 (1):27-34"; # 1st group
$citation = "McDermott, F. (1964). The Taxonomy of the Lampyridae (Coleoptera). Transactions of the American Entomological Society (1890-), 90(1), 1-72. Retrieved January 29, 2021, from http://www.jstor.org/stable/25077867"; # 2nd group
$input = array();
$input["params"] = array("citation" => $citation);
$input["type"] = "wikidata_base_qry_citation";
$input["per_page"] = 500; // 500 worked ok

$input["trait kind"] = "trait"; //2 records only
$func->query_trait_db($input);

$input["trait kind"] = "inferred_trait";
$func->query_trait_db($input);
*/

/* with resource_id --- query has an error
$resource_id = 1054;
$input = array();
$input["params"] = array("resource_id" => $resource_id);
$input["type"] = "wikidata_base_qry_resourceID";
$input["per_page"] = 500; // 500 worked ok

// $input["trait kind"] = "trait"; //2 records only
// $func->query_trait_db($input);

$input["trait kind"] = "inferred_trait";
$func->query_trait_db($input);
*/

/* run all resources
// exit("\npnas is running...\n");
$spreadsheet = "circadian_rythm_resources_sans_pantheria.csv";
$func->run_all_resources($spreadsheet);
*/

/*
$input = array();
$input["params"] = array();
$input["type"] = "traits_stop_at";
$input["per_page"] = 500; // 500 worked ok
$arr = $func->get_traits_stop_at($input);
print_r($arr);
exit("\n-end-\n");
*/

/*
Saved OK [/Volumes/Crucial_2TB/eol_cache/cypher_query/b7/17/b7172623b0095c0271e738f4eebc6fbc.json]
Saved OK [/Volumes/Crucial_2TB/eol_cache/cypher_query/60/69/6069cfe15ad7643e22e33ee52149f6d8.json]
 No. of rows: 0
*/


/*
1070940
https://query.wikidata.org/sparql?query=SELECT ?s WHERE {VALUES ?id {"3393129"} ?s wdt:P1566 ?id }
https://stackoverflow.com/questions/74244994/query-multiple-geonameids-in-sparql-query-on-wikidata
*/

// /* for individual resource IDs --- working OK
$input = array();
/* 1st client
$input["params"] = array("resource_id" => 753); // 753-Flora do Brasil | 822-Kubitzki
$input["type"] = "wikidata_base_qry_resourceID";
*/

// /* 2nd client
$input["params"] = array("source" => "https://doi.org/10.1007/s13127-017-0350-6"); // 
$input["type"] = "wikidata_base_qry_source";
// */

$input["per_page"] = 1000;

$input["trait kind"] = "trait"; //print_r($input); exit;
$func->query_trait_db($input);

$input["trait kind"] = "inferred_trait";
$func->query_trait_db($input);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>