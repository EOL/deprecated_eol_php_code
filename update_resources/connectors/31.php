<?php
namespace php_active_record;
/* eol-archive:
31	Thursday 2018-04-05 02:14:58 AM	{"media_resource.tab":60942, "taxon.tab":11854}
31	Monday 2020-04-13 02:57:32 PM	{"media_resource.tab":62691, "taxon.tab":12248, "time_elapsed":false}

Mac Mini
31	Thursday 2020-05-07 07:52:43 PM	{"media_resource.tab":62691, "taxon.tab":12249, "time_elapsed":false}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BiopixAPI');

$biopix_connector = new BiopixAPI;
$biopix_connector->get_all_taxa();

Functions::finalize_dwca_resource(31, false, true); //2nd param NOT a big file | 3rd param YES will delete working folders /31/
?>
