<?php
namespace php_active_record;
/* http://eol.org/content_partners/179/resources/383
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ITISConnector');
$timestart = time_elapsed();

$resource_id = 383;
$itis = new ITISConnector($resource_id);
$itis->build_archive();
Functions::set_resource_status_to_harvest_requested($resource_id);

/*                                      2017
   as of            Sep23    Mar30      Oct2
measurement         186666   189993     232027
occurrence          186666   189993     232027
reference           9116     9303       12230
taxon               651900   656733     754947
vernacular_name     102992   103371     107152
*/

Functions::count_resource_tab_files($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n elapsed time = $elapsed_time_sec seconds           \n";
echo " elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo " elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>