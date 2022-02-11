<?php
namespace php_active_record;
/* This can be a template for any resource, a means to enter taxon rows for the undefined parentNameUsageIDs
first client is: wikidata-hierarchy.tar.gz (DATA-1668)

             3365727
{"taxon.tab":3365754, "time_elapsed":{"sec":1802.9, "min":30.05, "hr":0.5}} Mac Mini
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M');
$timestart = time_elapsed();
$resource_id = "wikidata-hierarchy-final";
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/wikidata-hierarchy.tar.gz';
// $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/wikidata-hierarchy.tar.gz';

process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    /* Orig in meta.xml has capital letters. Just a note reminder. */
    $preferred_rowtypes = false;
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
    
    /* This will be processed in FillUpMissingParentsAPI.php which will be called from DwCA_Utility.php
    */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
    
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    echo "\nUndefined parents now: ".count($undefined)."\n";
    
    //now u can delete working dir
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id/");
}
?>