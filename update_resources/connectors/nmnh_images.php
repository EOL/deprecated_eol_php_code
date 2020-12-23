<?php
namespace php_active_record;
/* DATA-1871: new NMNH image connector from GBIF export
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NMNHimagesAPI');

$timestart = time_elapsed();
$resource_id = 'nmnh_images';
$func = new NMNHimagesAPI($resource_id, false, true);
$func->start();
// exit("\nstop muna\n");
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //2nd param False - not a big file | 3rd param True - can delete working folder
?>
