<?php
namespace php_active_record;
/*  
Processes a DwCA file, preferably an EOL DwCA file.
For non-EOL DwCA file, the result archive will only consist of extensions and fields that are understood by the EOL DwCA.
*Another similar library is DWCA_Utility_cmd.php. This one will process a DwCA taxa extension (taxon.tab/txt/tsv). And this one is run as command-line in terminal.

$ php dwca_utility.php jenkins 704   //with jenkins (in eol-archive). Just plain conversion to EOL DwCA
$ php dwca_utility.php jenkins 704 Y //with jenkins (in eol-archive), and with higherClassification

$ php dwca_utility.php _ 704         //no jenkins with resource_id (in MacMini)
$ php dwca_utility.php _ 704 Y       //no jenkins with resource_id (in MacMini), and with higherClassification

$ php dwca_utility.php              //no resource_id, will use basename of whatever $dwca_file is as resource_id. e.g. ioc-birdlist.tar.gz

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DwCA_Utility');
$timestart = time_elapsed();
ini_set("memory_limit","9000M"); // trying for the dwh_try3.zip, didn't work yet

//===========================================================================================new - start -- handles cmdline params
// print_r($argv);
$cmdline_params['jenkins_or_cron']                  = @$argv[1]; //irrelevant here
$cmdline_params['resource_id']                      = @$argv[2]; //useful here
$cmdline_params['generate_higherClassification_YN'] = @$argv[3]; //useful here ('Y' or 'N')
print_r($cmdline_params);
$resource_id = false;
if($val = $cmdline_params['resource_id'])
{
    $resource_id = $val;
    if($resource_id == 704) $dwca_file = "https://opendata.eol.org/dataset/7a17dc15-cb08-4e41-b901-6af5fd89bcd7/resource/3c56c4e4-3be7-463b-b958-22fbc560cf0d/download/pantheria.zip";
    elseif($resource_id == 430) // WIP
    {
        ini_set('memory_limit','7096M'); //required
        // $dwc_file = "http://www.inaturalist.org/taxa/eol_media.dwca.zip";
        $dwca_file = "http://localhost/cp/iNaturalist/eol_media.dwca.zip";
    }
    else exit("\nProgram will terminate. Invalid resource_id [$resource_id].\n\n");
}
else //no resource_id
{
    // $dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
    // $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/ioc-birdlist.tar.gz";
    // $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/ICTV-virus_taxonomy.tar.gz";
    // $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/26.tar.gz";
    // $dwca_file = "http://localhost/cp/dynamic_hierarchy/amphibia.zip";
    // $dwca_file = "http://localhost/cp/dynamic_hierarchy/dwca-phasmida-v10.6.zip";
    // $dwca_file = "http://localhost/cp/dynamic_hierarchy/dwh_try3.zip"; //very big one
    
    // /* WIP - waiting for feedback...
    $dwca_file = "http://localhost/cp/dynamic_hierarchy/z/eoldynamichierarchydraftaug2017.zip"; //needs to be fixed first: https://eol-jira.bibalex.org/browse/DATA-1709
    $dwca_file = "http://localhost/cp/dynamic_hierarchy/z/dynamic.tar.gz"; //this is the fixed version
    // */
    
    $resource_id = get_base_filename($dwca_file, $cmdline_params['generate_higherClassification_YN']);
}
//===========================================================================================new - end

// /* //main operation
$func = new DwCA_Utility($resource_id, $dwca_file);
if($cmdline_params['generate_higherClassification_YN'] == "Y") $func->convert_archive_by_adding_higherClassification();
else                                                           $func->convert_archive(); //this is same as above; just doesn't generate higherClassification
Functions::finalize_dwca_resource($resource_id);
unset($func);
// */

/* //utility - useful when generating higherClassification
// $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/dwca-phasmida-v10-with-higherClassification.tar.gz"; //debug -> if you want to supply a diff. dwca
$func = new DwCA_Utility(NULL, $dwca_file);
$func->count_records_in_dwca();
unset($func);
*/

/* utility - useful when generating higherClassification
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, false); //true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function get_base_filename($dwca_file, $generate_higherClassification_YN)
{
    $info = pathinfo($dwca_file);
    $arr = explode(".", $info['filename']);
    if($generate_higherClassification_YN == "Y") return $arr[0]."-with-higherClassification";
    else                                         return $arr[0]."-adjusted";
}
?>
