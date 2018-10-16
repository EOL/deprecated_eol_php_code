<?php
namespace php_active_record;
/* DATA-1770: separate wikimedia into an images resource and a common names resource */

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', true);
// define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false;
require_library('connectors/DwCA_Utility');
ini_set('memory_limit','7096M'); //required

// $resource_id = 'wikimedia_comnames';
// $func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . "71" . ".tar.gz");
// $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.gbif.org/terms/1.0/vernacularname');
// $func->convert_archive($preferred_rowtypes);
// Functions::finalize_dwca_resource($resource_id);
// $func = '';

$resource_id = '71_new';
$func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . "71" . ".tar.gz");
$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/agent/agent', 'http://eol.org/schema/media/document');
$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>