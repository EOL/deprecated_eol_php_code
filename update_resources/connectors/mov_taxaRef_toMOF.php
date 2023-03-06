<?php
namespace php_active_record;
/* Adjustment request from here:
https://eol-jira.bibalex.org/browse/DATA-1919?focusedCommentId=67376&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67376
Adjustment means, removing the taxa reference and move it to MoF for that taxa.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* 1st client: Brazilian Flora: https://opendata.eol.org/dataset/brazilian-flora/resource/04e94dff-d997-4e3f-946c-2c4bf5173256
$resource_id = 'Brazilian_Flora'; //destination DWCA filename
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/BF.tar.gz'; //normal operation
// $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/BF.tar.gz'; //during dev.
// */

/* 2nd client: xxx
*/

process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    $preferred_rowtypes = array('http://rs.gbif.org/terms/1.0/vernacularname' ,'http://eol.org/schema/reference/reference', 'http://rs.tdwg.org/dwc/terms/occurrence');
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
    /* These two (2) files will be processed in Mov_TaxaRef_2MOF_API.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>