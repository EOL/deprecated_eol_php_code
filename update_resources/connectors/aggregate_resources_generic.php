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

$DwCAs = array();
$DwCAs[] = 'wikipedia-ceb_5of10';
$DwCAs[] = 'wikipedia-ceb_1of10';
$DwCAs[] = 'wikipedia-ceb_2of10';
$DwCAs[] = 'wikipedia-ceb_9of10';
$DwCAs[] = 'wikipedia-ceb_8of10';
$DwCAs[] = 'wikipedia-ceb_4of10';
$DwCAs[] = 'wikipedia-ceb_6of10';
$DwCAs[] = 'wikipedia-ceb_10of10';
$DwCAs[] = 'wikipedia-ceb_3of10';
print_r($DwCAs);

$func = new DwCA_Aggregator("wikipedia-ceb");
$func->combine_DwCAs($DwCAs);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>