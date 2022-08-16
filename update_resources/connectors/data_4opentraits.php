<?php
namespace php_active_record;
/* DATA-1909: resource metadata and summary-from-resource-data export from CKAN
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/Data_OpenTraits');
$timestart = time_elapsed();

/*
$url = "https://opendata.eol.org/dataset/86081133-3db1-4ffc-8b1f-2bbba1d1f948/resource/b9951366-90e8-475e-927e-774b95faf7ed/download/hardtomatch.tar.gz";
$url = "http://rs.tdwg.org/dwc/terms/taxon";
print_r(pathinfo($url));
echo "\n";
echo pathinfo($url, PATHINFO_BASENAME);
exit("\n-end test-\n");
*/

$func = new Data_OpenTraits();
// $func->start();

/* test during dev:
$hc[2] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Gnathifera|Syndermata";
$hc[38] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Annelida|Pleistoannelida|Sedentaria|Clitellata|Hirudinea|Acanthobdellidea";
$hc[46] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Annelida|Pleistoannelida|Sedentaria|Clitellata";
$func->process_pipe_delim_values($hc);
*/

// /*
$ids = array(3585, 3587, 3589, 3685, 3907, 3923, 4525, 4535, 93136, 2968283, 17240251, 23476847, 47181178);
foreach($ids as $eol_id) $func->lookup_DH($eol_id);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>