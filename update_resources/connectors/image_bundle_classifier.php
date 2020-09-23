<?php
namespace php_active_record;
/* Image bundles for the next classifier - DATA-1865
estimated execution time:
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ImageBundleClassifierAPI');
$timestart = time_elapsed();

$func = new ImageBundleClassifierAPI();
$func->task1_Herbarium_Sheets();
?>