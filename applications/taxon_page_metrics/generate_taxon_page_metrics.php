<?php
namespace php_active_record;
$GLOBALS['ENV_DEBUG'] = true;
include_once(dirname(__FILE__) . "/../../config/environment.php");

$time_start = time_elapsed();
require_library('TaxonPageMetrics');
$stats = new TaxonPageMetrics();
$stats->insert_page_metrics(); // 1.36 hours

sleep(20);
shell_exec("php " . DOC_ROOT . "applications/taxon_page_metrics/generate_eol_stats.php");

$time_elapsed_sec = time_elapsed() - $time_start;
echo "\n elapsed time = $time_elapsed_sec sec               ";
echo "\n elapsed time = " . $time_elapsed_sec/60 . " mins   ";
echo "\n elapsed time = " . $time_elapsed_sec/60/60 . " hrs ";
?>