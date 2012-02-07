<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../../config/environment.php");
$start_time = time_elapsed();            

require_library('TaxonPageMetrics');
$page_metrics = new TaxonPageMetrics();

// execution time: 17 minutes
$page_metrics->generate_taxon_concept_with_bhl_links_textfile();

// execution time: 11 hours --- will be replaced once we store taxon_concept_id in PAGE_NAMES table.
$page_metrics->generate_taxon_concept_with_bhl_publications_textfile();

/* work in progress
$page_metrics->get_concepts_with_bhl_publications();
*/

$time_elapsed_sec = time_elapsed() - $start_time;
echo "\n\n elapsed time = " . $time_elapsed_sec/60 . " mins   ";
echo "\n elapsed time = " . $time_elapsed_sec/60/60 . " hrs ";
?>