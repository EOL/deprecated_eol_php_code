<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M');
// $GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();

require_library('connectors/EOLHarvestPublishAPI');
$func = new EOLHarvestPublishAPI();
$func->generate_cache_for_EOLResourcesHarvestList(); //this one: http://content.eol.org/?page=1&per_page=15
unset($func);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>