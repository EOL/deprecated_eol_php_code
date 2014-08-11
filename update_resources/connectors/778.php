<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FaloDataConnector');

$resource_id = 778;
$spg_falo_url = 'http://tiny.cc/FALO';
$temporary_falo_url = 'https://www.dropbox.com/s/04yyog1kdwq04l8/FALO.xlsx?dl=1'; // Lisa's version
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
  Functions::set_resource_status_to_force_harvest($resource_id);
}
else {
  debug('Error harvesting FaloDataConnector: '
    . $caught_exception->getMessage());
}

unset($connector); // Run 'destruct' method before script ends.

?>
