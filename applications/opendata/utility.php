<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

require_library('OpenData');
$func = new OpenData();

/* this generates this file: [CKAN_uploaded_files.txt]. Done, run once only.
if(Functions::is_production()) $path = "/extra/ckan_resources/";
else                           $path = "/Volumes/AKiTiO4/web/cp/summary_data_resources/page_ids/";
$func->get_all_ckan_resource_files($path);
*/


$func->connect_old_file_system_with_new();


?>
