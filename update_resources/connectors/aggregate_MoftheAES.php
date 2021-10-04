<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');
$resource_id = 'MoftheAES_resources';
$func = new DwCA_Aggregator($resource_id, false, 'regular');
$resource_ids = array("118935", "120081", "120082", "118986", "118920", "120083", "118237",
"118941", "118950", "118936", "118946", "118978", "119035", "119187", "119188", "119520", "120602", "27822", "30354", "30355");
/* 20 documents as of Jul 29, 2021 */

/* rowtypes
"http://rs.tdwg.org/dwc/terms/taxon", "http://eol.org/schema/media/document", 
"http://rs.tdwg.org/dwc/terms/occurrence", "http://rs.tdwg.org/dwc/terms/measurementorfact"
*/
$func->combine_MoftheAES_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>