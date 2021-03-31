<?php
namespace php_active_record;
/* testing ConvertioAPI */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertioAPI');
$func = new ConvertioAPI();
$api_id = $func->initialize_request();
$source = '/Volumes/AKiTiO4/other_files/epub/SCtZ-0007.epub';
$filename = 'SCtZ-0007.epub';
$func->upload_local_file($source, $filename, $api_id);
sleep(60);
$status = $func->check_status($api_id);
exit("\n-end-\n");

?>