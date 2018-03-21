<?php
namespace php_active_record;
/*
Before partner provides a TSV file:
estimated execution time: 14 minutes: 40k images | 50 minutes: 72k images

Now partner provides/hosts a DWC-A file. Together with images they also now share text objects as well.
estimated execution time: 55 minutes for:
                                                5Jan'15     28Jan'15
 images:                114,658     114,103     138,879     138,878
 measurementorfact:     201,088     201,088     239,256     106,336
 occurrences                                                26,154
 taxa:                  17,499      17,499      19,627      19,627
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MCZHarvardArchiveAPI');

$timestart = time_elapsed();
$resource_id = 201;
$func = new MCZHarvardArchiveAPI($resource_id);

$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true);
// $func->get_mediaURL_for_first_40k_images(); //this is a utility

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>