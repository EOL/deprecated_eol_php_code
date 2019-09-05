<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

require_library('OpenData');
$func = new OpenData();
$func->get_all_ckan_resource_files('');

?>
