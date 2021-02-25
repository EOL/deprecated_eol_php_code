<?php
namespace php_active_record;
/*
php update_resources/connectors/SDR_consolid8.php
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* //main operation
require_library('connectors/DwCA_Utility');
// ini_set('memory_limit','9096M'); //required
$resource_id = "parent_BV_consolid8";
$dwca = CONTENT_RESOURCE_LOCAL_PATH.'parent_basal_values.tar.gz';
$func = new DwCA_Utility($resource_id, $dwca);
$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.tdwg.org/dwc/terms/occurrence');
$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, true, $timestart);
// */
?>