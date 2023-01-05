<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/COLLAB-1006 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server

require_library('connectors/CypherQueryAPI');
$resource_id = 'eol';
$func = new CypherQueryAPI($resource_id);

/*
Q37887397 TAB P214 TAB "96480189"
https://quickstatements.toolforge.org/#/v1=

Q4115189|P4000|Q13184|S854|"https://eol.org/pages/1109554"


test.qs

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






curl https://quickstatements.toolforge.org/api.php \
	-d action=import \
	-d submit=1 \
	-d username=Eagbayani \
	-d "batchname=Eli batch3" \
	--data-raw 'token=$2y$10$dEhijZQf/c3kTQGFAlUKj.JMs2Qdb4N/UUl/eFOyHVZ1sAu6vVGnS' \
	--data-urlencode data@test.qs
    
    {"status":"OK","debug":{"format":"v1","temporary":false,"openpage":0},"batch_id":108278}

https://quickstatements.toolforge.org/api.php?action=import&submit=1&username=Eagbayani&token=%242y%2410%24dEhijZQf%2Fc3kTQGFAlUKj.JMs2Qdb4N%2FUUl%2FeFOyHVZ1sAu6vVGnS&format=v1&data=Q4115189%7CP4000%7CQ13184%7CS854%7C%22https%3A%2F%2Feol.org%2Fpages%2F1109554%22&batchname=Eli_batch1
{"status":"OK","debug":{"format":"v1","temporary":false,"openpage":0},"batch_id":108266}

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

/* copied template - not used but works OK
====================================================
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
====================================================
*/

/* good example
$source = "https://doi.org/10.1111/j.1469-185X.1984.tb00411.x";
$input["params"] = array("source" => $source);
$input["type"] = "wikidata_base_qry_source";
$input["per_page"] = 100; // 100 finished ok
$func->query_trait_db($input);
*/

// /* good example
$citation = "J. Kuijt, B. Hansen. 2014. The families and genera of vascular plants. Volume XII; Flowering Plants: Eudicots - Santalales, Balanophorales. K. Kubitzki (ed). Springer Nature";
$input["params"] = array("citation" => $citation);
$input["type"] = "wikidata_base_qry_citation";
$input["per_page"] = 500; // 500 worked ok
$func->query_trait_db($input);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>