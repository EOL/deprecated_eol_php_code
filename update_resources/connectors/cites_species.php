<?php
namespace php_active_record;
/* DATA-1790: CITES species records
cites_taxa	Wednesday 2019-01-02 04:09:07 AM	{"measurement_or_fact.tab":18128,"occurrence.tab":18128,"taxon.tab":75623,"vernacular_name.tab":30384}
cites_taxa	Wednesday 2019-01-09 09:42:31 AM	{"measurement_or_fact.tab":57748,"occurrence.tab":57748,"taxon.tab":75623,"vernacular_name.tab":30384}
This run seems erroneous:
cites_taxa	Friday 2019-11-22 08:58:59 PM	    {"MoF.tab":91042, "occurrence.tab":91042,"taxon.tab":38862,"vernacular_name.tab":29532,"time_elapsed":{"sec":37821.53,"min":630.36,"hr":10.51}}
This run seems OK:
cites_taxa	Saturday 2019-11-23 06:59:43 PM	    {"MoF.tab":154576, "occurrence.tab":154576, "taxon.tab":80558, "vernacular_name.tab":30405, "time_elapsed":{"sec":63636.93,"min":1060.62,"hr":17.68}}
Last refreshed is too small than previous. Will use previous moving forward.
cites_taxa	Thu 2021-06-03 05:17:58 PM	        {"MoF.tab":18000,  "occurrence.tab":18000,  "taxon.tab":27762, "vernacular_name.tab":13371, "time_elapsed":{"sec":24549.67, "min":409.16, "hr":6.82}}
Moving forward:
cites_taxa	Fri 2021-06-04 02:54:02 AM	        {"MoF.tab":154576, "occurrence.tab":154576, "taxon.tab":80558, "vernacular_name.tab":30405, "time_elapsed":{"sec":163.96, "min":2.73, "hr":0.05}}

Previous stable run is now renamed as: cites_taxa_23Nov2019.tar.gz
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

/* worked for the longest time. But in 2021-06-03 it is no longer working. Connector needs to be updated. To-do.
require_library('connectors/CITESspeciesAPI');
ini_set('memory_limit','7096M'); //required for checking uniqueness of measurementID
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 'cites_taxa';
$func = new CITESspeciesAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
*/

// /* - as of 2021-06-03, we are now just going to make use of the old, last stable DwCA -> cites_taxa_23Nov2019.tar.gz
//    - the change needed is to remove the column measurementDeterminedBy in MoF. That's it.
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 'cites_taxa';
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/cites_taxa_23Nov2019.tar.gz';
require_library('connectors/DwCA_Utility');
$func = new DwCA_Utility($resource_id, $dwca_file);
$preferred_rowtypes = array(); //blank like this means all rowtypes will be proccessed in DwCA_Utility.php
$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means delete folder
// */
?>