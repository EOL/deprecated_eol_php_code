<?php
namespace php_active_record;
/*
cephbase	Wednesday 2018-08-29 04:28:13 AM	{"agent.tab":27,"media_resource.tab":1656,"reference.tab":9135,"taxon.tab":1623} 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CephBaseAPI');

/* //tests
$url = "http://cephbase.eol.org/sites/cephbase.eol.org/files/cb0001.jpg";
print_r(pathinfo($url, PATHINFO_BASENAME)); exit;
*/

$timestart = time_elapsed();
$resource_id = 'cephbase';
$resource_id = 'cephbase_caching';

$func = new CephBaseAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>