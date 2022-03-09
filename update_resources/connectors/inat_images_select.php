<?php
namespace php_active_record;
/* This will process inat_images.tar.gz and select 100 images per taxon, with blur detection */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false; //orig value should be -> false
// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */
// ini_set('memory_limit','7096M');
$timestart = time_elapsed();

// print_r($argv);
/* not needed here
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
*/

/* ------------------------------------ in command-line in eol-archive:
IMPORTANT TO CD to: cd /var/www/html/eol_php_code/update_resources/connectors/
# this will be run in command-line since python can't be accessed in Jenkins
php inat_images_select.php _
# generates inat_images_100cap.tar.gz

$ nohup php inat_images_select.php _ > terminal_inat_images_select.out
-> use 'nohup' so it continues even after logging out of the terminal

For diagnostics:
    ps --help simple
    ps -r 
        -> very helpful, if u want to delete current running process
    cat terminal_inat_images_select.txt
        -> to see progress, very convenient
    ps -p 17943
        -> to investigate a running PID
    cat /var/www/html/eol_php_code/update_resources/connectors/terminal_inat_images_select.out
        -> to monitor runtime
    ls -lt /var/www/html/eol_php_code/applications/blur_detection_opencv_eol/eol_images/
    ls -lt /var/www/html/eol_php_code/update_resources/connectors/terminal_inat_images_select.out
    ls /extra/other_files/iNat_image_DwCA/cache_image_score/
    find /extra/other_files/iNat_image_DwCA/cache_image_score/ -type f | wc -l
    kill -9 17943
*/

$resource_id = 'inat_images';           //resource generated from inat_images.php (150 images per taxon)
$resource_id = 'inat_images_40limit';   //resource generated from inat_images.php --- media_resource.tab : 5144786
// $resource_id = 'inat_images_20limit';   //resource generated from inat_images.php --- media_resource.tab : 3292778
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
// $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz'; //during dev only
$resource_id = 'inat_images_100cap'; //new resource --- currently running, caching image scores --- did not materialize
$resource_id = 'inat_images_3Mcap'; //new resource --- currently running, caching image scores

process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    $excluded_rowtypes = false;
    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon'); //'http://eol.org/schema/agent/agent'

    /* This will be processed in DeltasHashIDsAPI.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>