<?php
namespace php_active_record;
/* From this adjustment request by Jen: DATA-1833

from legacy https://editors.eol.org/eol_php_code/applications/content_server/resources/legacy_SC_australia.tar.gz

*The legacy SC_australia.tar.gz was renamed to legacy_SC_australia.tar.gz. We're going to use the latter moving forward.
-----------------------------------------------------------------------------------------------------------------------
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

$resource_id = 'SC_australia';
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/legacy_SC_australia.tar.gz';
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
    rowType="http://rs.tdwg.org/dwc/terms/MeasurementOrFact">
    rowType="http://rs.tdwg.org/dwc/terms/Occurrence">
    rowType="http://eol.org/schema/reference/Reference">
    */

    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.tdwg.org/dwc/terms/occurrence', 'http://eol.org/schema/reference/reference');
    /* This 1 will be processed in SpeciesChecklistAPI.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/measurementorfact
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id);
}
?>