<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. 
   This was copied from aggregate_resources.php
   This is a utility. This finishes unfinished Wikipedia language connectors, for some reason.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');

$res = "wikipedia-ceb";
$res = "wikipedia-sv";

$DwCAs = array();
$DwCAs[] = $res.'_1of10';
$DwCAs[] = $res.'_2of10';
$DwCAs[] = $res.'_3of10';
$DwCAs[] = $res.'_4of10';
$DwCAs[] = $res.'_5of10';
$DwCAs[] = $res.'_6of10';
$DwCAs[] = $res.'_7of10';
$DwCAs[] = $res.'_8of10';
$DwCAs[] = $res.'_9of10';
$DwCAs[] = $res.'_10of10';
print_r($DwCAs);

$resource_id = $res;
$func = new DwCA_Aggregator($resource_id);
$func->combine_DwCAs($DwCAs);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>