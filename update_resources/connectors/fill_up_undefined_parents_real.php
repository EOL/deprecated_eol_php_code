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
Below here is after back and forth exchanges and adjustments: should be a good stable resource.
wikidata-hierarchy-final	Sun 2022-02-20 11:51:15 PM	{"taxon.tab":3323627, "time_elapsed":{"sec":9562.62, "min":159.38, "hr":2.66}}
Below is after adding EOLid, so as expected nos. shouldn't change
wikidata_hierarchy	        Mon 2022-03-07 10:30:43 AM	{"taxon.tab":3323627, "time_elapsed":{"sec":1127.85, "min":18.8, "hr":0.31}}

------------------------------------ 1st client: in command-line in eol-archive:
IMPORTANT TO CD to: cd /var/www/html/eol_php_code/update_resources/connectors/
# this will be run in command-line since gnparser can't be accessed in Jenkins
php fill_up_undefined_parents.php _ '{"resource_id": "wikidata-hierarchy-final", "source_dwca": "wikidata-hierarchy"}'
# generates wikidata-hierarchy-final.tar.gz

$ nohup php fill_up_undefined_parents.php _ '{"resource_id": "wikidata-hierarchy-final", "source_dwca": "wikidata-hierarchy"}' > terminal_fill_up_undefined_parents.out
-> use 'nohup' so it continues even after logging out of the terminal

------------------------------------ 2nd client: can run in jenkins since gnparser is not needed for this resource
php fill_up_undefined_parents.php _ '{"resource_id": "wikipedia_en_traits_tmp4", "source_dwca": "wikipedia_en_traits_tmp3"}'
php5.6 fill_up_undefined_parents.php jenkins '{"resource_id": "wikipedia_en_traits_tmp4", "source_dwca": "wikipedia_en_traits_tmp3", "resource": "fillup_missing_parents"}'
-> generates wikipedia_en_traits_tmp4.tar.gz

------------------------------------ 3rd client: same as 2nd client. For all text wikipedia languages (es, de, etc.)
php fill_up_undefined_parents.php _ '{"resource_id": "80", "source_dwca": "80", "resource": "fillup_missing_parents"}'
php fill_up_undefined_parents.php _ '{"resource_id": "wikipedia-sv", "source_dwca": "wikipedia-sv", "resource": "fillup_missing_parents"}'

# generates 80.tar.gz

For diagnostics:
    ps --help simple
    ps -r 
        -> very helpful, if u want to check current running processes
    ps -p 30544
        -> to investigate a running PID
    kill -9 30544
        -> to kill a running PID
    cat terminal_fill_up_undefined_parents.txt
        -> to see progress, very convenient
    $ cat /var/www/html/eol_php_code/update_resources/connectors/terminal_fill_up_undefined_parents.out
        -> to monitor runtime
*/

// https://editors.eol.org/eol_php_code/applications/content_server/resources/wikipedia_en_traits_tmp1_undefined_parent_ids.txt
// https://editors.eol.org/eol_php_code/applications/content_server/resources/wikipedia_en_traits_tmp3_undefined_parent_ids.txt

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M');

$timestart = time_elapsed();
echo "\n--------------------START: fillup missing parent entries--------------------\n";
// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
$source_dwca = $param['source_dwca'];


// /* during development --- or when investigating
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */

/* just a test
$status = chmod(CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".tar.gz", 0775);
exit("\nFile permission update: [$status]\n");
*/

if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$source_dwca.'.tar.gz';
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/'.$source_dwca.'.tar.gz';

$ctr = 1;
$undefined = process_resource_url($dwca_file, $resource_id, $timestart, $ctr, $param);

while($undefined) { $ctr++;
    if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
    else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/'.$resource_id.'.tar.gz';
    $undefined = process_resource_url($dwca_file, $resource_id, $timestart, $ctr, $param);
}
echo "\n--------------------END: fillup missing parent entries--------------------\n";

function process_resource_url($dwca_file, $resource_id, $timestart, $ctr, $param)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file, $param);

    /* Orig in meta.xml has capital letters. Just a note reminder. */
    $preferred_rowtypes = false;
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');

    /* works but decided to have all resources to carry_over the media document
    if(in_array($resource_id, array("wikipedia-war", "wikipedia-ceb", "wikipedia-sv"))) {
        $excluded_rowtypes[] = 'http://eol.org/schema/media/document'; //bec war, ceb, sv have a big media tab
    }
    */
    $excluded_rowtypes[] = 'http://eol.org/schema/media/document'; //now for all resources, not just the big ones like ceb, war, sv

    
    /* This will be processed in FillUpMissingParentsAPI.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    // echo "\n===Ready to finalize...\n";
    Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
    
    $status = chmod(CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".tar.gz", 0775);
    echo "\nFile permission update: [$status]\n";
    
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    echo "\nUndefined parents now [$ctr]: ".count($undefined)."\n";
    
    //now u can delete working dir
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id/");
    
    return $undefined;
}
?>