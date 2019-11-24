<?php
namespace php_active_record;
/* DATA-1790: CITES species records
cites_taxa	Wednesday 2019-01-02 04:09:07 AM	{"measurement_or_fact.tab":18128,"occurrence.tab":18128,"taxon.tab":75623,"vernacular_name.tab":30384}
cites_taxa	Wednesday 2019-01-09 09:42:31 AM	{"measurement_or_fact.tab":57748,"occurrence.tab":57748,"taxon.tab":75623,"vernacular_name.tab":30384}
This run seems erroneous:
cites_taxa	Friday 2019-11-22 08:58:59 PM	    {"measurement_or_fact_specific.tab":91042,"occurrence_specific.tab":91042,"taxon.tab":38862,"vernacular_name.tab":29532,"time_elapsed":{"sec":37821.53,"min":630.36,"hr":10.51}}
This run seems OK:
cites_taxa	Saturday 2019-11-23 06:59:43 PM	    {"measurement_or_fact_specific.tab":154576,"occurrence_specific.tab":154576,"taxon.tab":80558,"vernacular_name.tab":30405,"time_elapsed":{"sec":63636.93,"min":1060.62,"hr":17.68}}
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