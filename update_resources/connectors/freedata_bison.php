<?php
namespace php_active_record;
/* Connector for BISON resource: https://eol-jira.bibalex.org/browse/DATA-1699
1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreshDataBisonAPI');
$timestart = time_elapsed();

// $a1 = array(1,2,3,4);
// if (($key = array_search('3', $a1)) !== false) {
//     unset($a1[$key]);
// }
// print_r($a1);
// exit("\n");

exit("\nend for now, might be accidentally overwritten\n");
$func = new FreshDataBisonAPI("bison"); //'bison' will be a folder name
$func->start();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

/*
Good source for Solr search info:
https://www.tutorialspoint.com/apache_solr/apache_solr_querying_data.htm
Good source for Solr query functions:
http://yonik.com/solr/query-syntax/
https://cwiki.apache.org/confluence/display/solr/Function+Queries

List of Data Providers:
https://bison.usgs.gov/#providers

Solr API Occurrence search:
https://bison.usgs.gov/solr/occurrences/select/?q=*:*

https://bison.usgs.gov/solr/occurrences/select/?q=providerID:440 and decimalLatitude:exists(decimalLatitude)&start=1&rows=1
https://bison.usgs.gov/solr/occurrences/select/?q=decimalLatitude:if(exists(decimalLatitude))

http://localhost:8983/solr/select?q=*:*&sort=dist(2, point1, point2) desc

https://bison.usgs.gov/solr/occurrences/select/?q=providerID:440&fq=exists(query({!v='year:2012'}))
-> works OK

https://bison.usgs.gov/solr/occurrences/select/?q=providerID:440&fq=decimalLatitude:[* TO *]&wt=json&start=0&rows=2
https://bison.usgs.gov/solr/occurrences/select/?q=providerID:440&fq=decimalLatitude:[* TO *]&start=0&rows=2
-> works OK

another e.g.
q=*:*&fq=publisher_s:Bantam&rows=3&sort=pubyear_i desc&fl=title_t,pubyear_i
*/
?>
