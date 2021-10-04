<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's.
Kubitzki	Thu 2021-09-23 12:27:04 AM	{"media_resource.tab":10947, "taxon.tab":10780, "time_elapsed":{"sec":24.74, "min":0.41, "hr":0.01}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');
$resource_id = 'Kubitzki'; //DATA-1894
$func = new DwCA_Aggregator($resource_id, false, 'regular');
$resource_ids = array("volii1993", "voliii1998", "volv2003", "volvi2004", "volvii2004", "volviii2007", "volix2007", "volx2011", "volxi2014", "volxii2015", "volxiii2015", "volxiv2016", "volxv2018");
$func->combine_MoftheAES_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>