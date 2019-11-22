<?php
namespace php_active_record;
/* DATA-1841
first step: this will generate 1000.tar.gz. But has duplicate taxon identifiers. Culprit is from the source xls file.
php update_resources/connectors/spreadsheet_2_dwca.php _ 1000
2nd step:
php update_resources/connectors/1000.php

1000	    Friday 2019-11-22 02:58:26 AM{"agents.txt":0,"associations.txt":0,"common names.txt":0,"events.txt":0,"measurements or facts.txt":4242,"media.txt":0,"occurrences.txt":4242,"references.txt":0,"taxa.txt":4242}
1000_final	Friday 2019-11-22 02:58:44 AM{"measurement_or_fact.tab":4242,"occurrence.tab":4142,"taxon.tab":4142,"time_elapsed":false}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = '1000_final';
if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/1000.tar.gz';
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_2/1000.tar.gz';
process_resource_url($dwca_file, $resource_id, $timestart);
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