<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('TopImages');

$log = HarvestProcessLog::create(array('process_name' => 'Top Images'));

$top_images = new TopImages();
$top_images->begin_process();
$top_images->top_concept_images(true);
$top_images->top_concept_images(false);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/clear_eol_cache.php ENV_NAME=". $GLOBALS['ENV_NAME']);

$log->finished();

?>