<?php
namespace php_active_record;
/* DATA-1841
first step: this will generate 1000.tar.gz. But has duplicate taxon identifiers. Culprit is from the source xls file.
php update_resources/connectors/spreadsheet_2_dwca.php _ 1000
2nd step:
php update_resources/connectors/1000.php
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = '1000_final';
if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/1000.tar.gz';
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_2/1000.tar.gz';
process_resource_url($dwca_file, $resource_id, $timestart);
echo "\nDone processing.\n";
function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    /* Orig in meta.xml has capital letters. Just a note reminder. */

    $preferred_rowtypes = array(); //blank like this means all rowtypes will be proccessed in DwCA_Utility.php
    /* These 4 will be processed in xxx.php which will be called from DwCA_Utility.php
    http://...
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means delete folder
}
?>