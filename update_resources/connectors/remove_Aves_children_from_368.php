<?php
namespace php_active_record;
/* From this adjustment request by Jen: https://eol-jira.bibalex.org/browse/DATA-1814?focusedCommentId=63686&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63686
Request is to remove all descendants of Aves (https://paleobiodb.org/classic/checkTaxonInfo?taxon_no=36616).
from the DwCA 368.tar.gz.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;
$resource_id = 368;
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/368.tar.gz';
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/368_orig.tar.gz';
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
    rowType="http://rs.tdwg.org/dwc/terms/Taxon">
    rowType="http://rs.tdwg.org/dwc/terms/Occurrence">
    rowType="http://rs.tdwg.org/dwc/terms/MeasurementOrFact">
    rowType="http://rs.gbif.org/terms/1.0/VernacularName">
    */

    $preferred_rowtypes = array(); //no prefered. All will be customized
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id);
}
?>