<?php
namespace php_active_record;
/* This can be a template for any resource, a means to enter taxon rows for the undefined parentNameUsageIDs
first client is: wikidata-hierarchy.tar.gz (DATA-1668)

Many errors:
wikidata-hierarchy	        Tue 2022-02-08 10:02:56 AM	{"taxon.tab":3365727, "time_elapsed":{"sec":353281.95, "min":5888.03, "hr":98.13, "day":4.09}} eol->archive
wikidata-hierarchy-final	Fri 2022-02-11 08:22:30 AM	{"taxon.tab":3365754, "time_elapsed":{"sec":1105.31, "min":18.42, "hr":0.31}} eol->archive
More stable:
wikidata-hierarchy	        Mon 2022-02-14 07:03:04 AM	{"taxon.tab":3350652, "time_elapsed":{"sec":10372.9, "min":172.88, "hr":2.88}}
wikidata-hierarchy-final	Mon 2022-02-14 08:48:27 AM	{"taxon.tab":3365214, "time_elapsed":{"sec":5338.23, "min":88.97, "hr":1.48}}

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M');
$timestart = time_elapsed();
$resource_id = "wikidata-hierarchy-final";
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/wikidata-hierarchy.tar.gz';
// $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/wikidata-hierarchy.tar.gz';

$ctr = 1;
$undefined = process_resource_url($dwca_file, $resource_id, $timestart, $ctr);

while($undefined) { $ctr++;
    $resource_id = "wikidata-hierarchy-final";
    $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/wikidata-hierarchy-final.tar.gz';
    // $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/wikidata-hierarchy-final.tar.gz';
    $undefined = process_resource_url($dwca_file, $resource_id, $timestart, $ctr);
}


function process_resource_url($dwca_file, $resource_id, $timestart, $ctr)
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
    echo "\nUndefined parents now [$ctr]: ".count($undefined)."\n";
    
    //now u can delete working dir
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id/");
    
    return $undefined;
}
?>