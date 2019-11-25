<?php
namespace php_active_record;
/* From this adjustment request by Jen:
https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=63624&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63624

from old 708.php
708	Saturday 2018-11-24 02:48:32 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":198537,"taxon.tab":196193}
708	Sunday 2018-11-25 04:26:42 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":198537,"taxon.tab":196193}

started using 708_new.php - Aug 2, 2019
708	Friday 2019-08-02 11:19:20 AM	{"measurement_or_fact.tab":742379,"occurrence.tab":645483,"reference.tab":198537,"taxon.tab":196192} - consistent OK
708	Friday 2019-08-02 11:38:34 AM	{"measurement_or_fact.tab":742379,"occurrence.tab":645483,"reference.tab":198537,"taxon.tab":196192} - 1st in eol->archive
708	Friday 2019-08-09 01:26:06 AM	{"measurement_or_fact.tab":742378,"occurrence.tab":645482,"reference.tab":198537,"taxon.tab":196191} - remove in taxon.tab a blank sciname
after DATA-1841: terms remapping -> nos. shouldn't be affected, so consistent OK
708	Monday 2019-11-25 04:03:57 AM	{"measurement_or_fact.tab":742378,"occurrence.tab":645482,"reference.tab":198537,"taxon.tab":196191,"time_elapsed":{"sec":341.02,"min":5.68,"hr":0.09}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

$resource_id = 708;
if(Functions::is_production())  $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/708_25Nov2018.tar.gz';
else                            $dwca_file = 'http://localhost/cp/Environments/legacy/708_25Nov2018.tar.gz';
process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
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
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>
