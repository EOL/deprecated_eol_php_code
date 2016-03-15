<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('TopImages');

$log = HarvestProcessLog::create(array('process_name' => 'Top Images'));

$date = date('m/d/Y h:i:s a', time());
print "## TopImages Start: $date\n";

$top_images = new TopImages();
$top_images->begin_process();
$top_images->top_concept_images(true);
$top_images->top_concept_images(false);

$date = date('m/d/Y h:i:s a', time());
print "## TopImages End: $date\n";

$log->finished();

?>
