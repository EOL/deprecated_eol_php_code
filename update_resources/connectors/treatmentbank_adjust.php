<?php
namespace php_active_record;
/* From these adjustments, starting here:
https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=66874&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66874
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 'TreatmentBank_adjustment_01'; //generates TreatmentBank_adjustment_01.tar.gz
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/TreatmentBank_ENV.tar.gz';
process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    $preferred_rowtypes = array('');
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
    /* All 3 files will be processed in DwCA_Rem_Taxa_Adjust_MoF_API.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>