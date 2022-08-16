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
$func->start();

/* test during dev:
$hc[2] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Gnathifera|Syndermata";
$hc[38] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Annelida|Pleistoannelida|Sedentaria|Clitellata|Hirudinea|Acanthobdellidea";
$hc[46] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Annelida|Pleistoannelida|Sedentaria|Clitellata";
$func->process_pipe_delim_values($hc);
*/

/*
$ids = array(46447570, 46447609, 46447611, 46447618, 46448168, 46448598, 46448684, 46450580, 46450691, 46450862, 46451183, 46451269, 46451274, 46451775, 46451779, 46459698, 46465037, 
46465655, 46466613, 46466840, 46467106, 46467110, 46469524, 46469676, 46470422, 46470499, 46471962, 46471973, 46473364, 46474253, 46474372, 46474962, 46478638, 46478708, 
46480539, 46481336, 46481866, 46481871, 46481877, 46495214, 46495338, 46495340, 46495445, 46495447, 46495453, 46495459, 46495460, 46495542, 46495592, 46495605, 46495607, 
46495617, 46495730, 46496068, 46496150, 46496176, 46496219, 46496227, 46496363, 46496650, 46496743, 46496761, 46497156, 46497521, 46498016, 46498171, 46498173, 46498182);
foreach($ids as $eol_id) $func->lookup_DH($eol_id);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>