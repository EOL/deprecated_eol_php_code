<?php
namespace php_active_record;
/* DATA-1790: CITES species records
cites_taxa	Wednesday 2019-01-02 04:09:07 AM	{"measurement_or_fact.tab":18128,"occurrence.tab":18128,"taxon.tab":75623,"vernacular_name.tab":30384}
cites_taxa	Wednesday 2019-01-09 09:42:31 AM	{"measurement_or_fact.tab":57748,"occurrence.tab":57748,"taxon.tab":75623,"vernacular_name.tab":30384}

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CITESspeciesAPI');
ini_set('memory_limit','7096M'); //required for checking uniqueness of measurementID
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 'cites_taxa';
$func = new CITESspeciesAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>