<?php
namespace php_active_record;
/* */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

$resource_id = 1;
$dwca_file = CONTENT_RESOURCE_LOCAL_PATH.'wikipedia-el.tar.gz';
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
      rowType="http://eol.org/schema/media/Document"
      rowType="http://rs.tdwg.org/dwc/terms/Taxon"
    */

    $preferred_rowtypes = array('http://eol.org/schema/media/document', 'http://eol.org/schema/reference/reference', 'http://eol.org/schema/agent/agent');
    $preferred_rowtypes = array();
    /* These 4 will be processed in USDAPlants2019.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/taxon
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id);
}
?>