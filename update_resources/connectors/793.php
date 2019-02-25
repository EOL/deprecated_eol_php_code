<?php
namespace php_active_record;
/*
Femorale images and data - DATA-1454
estimated execution time:

http://rs.tdwg.org/dwc/terms/taxon:Total        : 22173
http://purl.org/dc/dcmitype/StillImage          : 122322
http://rs.tdwg.org/dwc/terms/measurementorfact  : 22171

793	Thursday 2019-02-21 03:40:21 PM	{"measurement_or_fact.tab":22171,"media_resource.tab":179124,"occurrence.tab":22171,"taxon.tab":22173}

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FemoraleAPI');

$timestart = time_elapsed();
$resource_id = 793;
$func = new FemoraleAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>