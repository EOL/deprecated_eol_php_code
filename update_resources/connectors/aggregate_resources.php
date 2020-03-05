<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

$resource_id = 'wikipedia_combined_languages';
require_library('connectors/DwCA_Aggregator');
$func = new DwCA_Aggregator($resource_id);

/* Orig in meta.xml has capital letters. Just a note reminder.
  rowType="http://rs.tdwg.org/dwc/terms/Taxon"
*/
$langs = array('ta', 'el', 'ceb');
$func->combine_DwCAs($langs);
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>