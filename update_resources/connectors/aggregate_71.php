<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');

$resource_id = 71; 			//orig
$resource_id = "71_test";	//during investigation
$preferred_rowtypes = array("http://rs.tdwg.org/dwc/terms/taxon", "http://rs.gbif.org/terms/1.0/vernacularname", "http://eol.org/schema/agent/agent", "http://eol.org/schema/media/document");
$func = new DwCA_Aggregator($resource_id);
$filenames = array('71_1of6', '71_2of6', '71_3of6', '71_4of6', '71_5of6', '71_6of6'); # 71_1of6.tar.gz
$func->combine_DwCAs($filenames, $preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>