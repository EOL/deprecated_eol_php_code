<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FaloDataConnector');
/*      Mar25
taxa    11976
*/
$resource_id = 778;

// $spg_falo_url = 'http://tiny.cc/FALO'; //not working
$spg_falo_url = 'https://dl.dropboxusercontent.com/u/7597512/NCBI_GGI/ALF2015.xlsx';

$temporary_falo_url = 'https://www.dropbox.com/s/04yyog1kdwq04l8/FALO.xlsx?dl=1'; // Lisa's version
$temporary_falo_url = 'http://localhost/~eolit/cp/NCBIGGI/ALF2015.xlsx'; 		  // local

$source_url = $spg_falo_url; // pointing to SPG's version, dynamic

$caught_exception = null;

$connector = new FaloDataConnector($resource_id, $source_url);
try {
  $connector->begin();
}
catch (\Exception $e) {
  $caught_exception = $e;
}

if (is_null($caught_exception) &&
  filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/taxon.tab") > 10000) {
  Functions::set_resource_status_to_harvest_requested($resource_id);
  Functions::count_resource_tab_files($resource_id);
}
else {
  debug('Error harvesting FaloDataConnector: '
    . $caught_exception->getMessage());
}

unset($connector); // Run 'destruct' method before script ends.

?>
