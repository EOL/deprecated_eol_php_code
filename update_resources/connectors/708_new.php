<?php
namespace php_active_record;
/* From this adjustment request by Jen:
https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=63624&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63624

from old 708.php
708	Saturday 2018-11-24 02:48:32 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":198537,"taxon.tab":196193}
708	Sunday 2018-11-25 04:26:42 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":198537,"taxon.tab":196193}

Starting Aug 2, 2019
708	Friday 2019-08-02 11:06:04 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645483,"taxon.tab":196192}

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

$resource_id = 708;
$dwca_file = 'http://localhost/cp/Environments/legacy/708_25Nov2018.tar.gz';
process_resource_url($dwca_file, $resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function process_resource_url($dwca_file, $resource_id)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    /* Orig in meta.xml has capital letters. Just a note reminder.
    rowType="http://rs.tdwg.org/dwc/terms/Occurrence"
    rowType="http://rs.tdwg.org/dwc/terms/MeasurementOrFact"
    rowType="http://rs.tdwg.org/dwc/terms/Taxon"
    rowType="http://eol.org/schema/reference/Reference"
    */

    $preferred_rowtypes = array('http://eol.org/schema/reference/reference');
    /* These 3 will be processed in New_EnvironmentsEOLDataConnector.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/occurrence
    http://rs.tdwg.org/dwc/terms/measurementorfact
    http://rs.tdwg.org/dwc/terms/taxon
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id);
}
?>
