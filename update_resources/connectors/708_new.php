<?php
namespace php_active_record;
/* From this adjustment request by Jen:
https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=63624&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63624
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;


/* Get all resources from OpenData
require_library('connectors/New_EnvironmentsEOLDataConnector');
$func = new New_EnvironmentsEOLDataConnector(false, false);
generate_new_dwca($func);                   //main script to generate DwCA
create_new_resources_in_opendata($func);    //script to create resources in two pre-defined datasets in opendata.eol.org.
unset($func);
*/

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

    /* Orig in meta.xml has capital letters.
    rowType="http://rs.tdwg.org/dwc/terms/Occurrence"
    rowType="http://rs.tdwg.org/dwc/terms/MeasurementOrFact"
    rowType="http://rs.tdwg.org/dwc/terms/Taxon"
    rowType="http://eol.org/schema/reference/Reference"
    */

    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/occurrence', 'http://eol.org/schema/reference/reference');
    /* These 2 will be processed in New_EnvironmentsEOLDataConnector.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/measurementorfact
    http://rs.tdwg.org/dwc/terms/taxon
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id);
}
?>
