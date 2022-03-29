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
Main implementation: 
php inat_images_select.php _
# generates inat_images_100cap.tar.gz

Secondary implementation:
php inat_images_select.php _ '{"taxonID":"6c2d12b42fa5108952956024716c2267"}'
-> to process only one taxon. e.g. Gadus morhua

php inat_images_select.php _ '{"taxonID":"c74d829f291b0471dc8e469dec09a3dc"}'
-> https://www.inaturalist.org/taxa/78856	Ribes indecorum Eastw.	Plantae	Tracheophyta	Magnoliopsida	Saxifragales	Grossulariaceae	Ribes	species

php inat_images_select.php _ '{"taxonID":"802348bd4ba248a598da11918485f140"}'
-> 	https://www.inaturalist.org/taxa/61495	Erythemis simplicicollis Say, 1839	Animalia	Arthropoda	Insecta	Odonata	

php inat_images_select.php _ '{"taxonID":"07cb0ee9203934e5fc3fbc2ccfcee1e3"}'
-> 	https://www.inaturalist.org/taxa/372465	Trigoniophthalmus alternatus (Silvestri, 1904)	Animalia	Arthropoda	Insecta	Archaeognatha	Machilidae	Trigoniophthalmus	species

php inat_images_select.php _ '{"taxonID":"e4fdd48749104fcd0c01c1ae79eed4ce"}'
-> 	https://www.inaturalist.org/taxa/129115	Knautia arvensis (L.) Coult.	Plantae	Tracheophyta	Magnoliopsida	Dipsacales	

$ nohup php inat_images_select.php _ > terminal_inat_images_select.out
-> use 'nohup' so it continues even after logging out of the terminal

For diagnostics:
    ps --help simple
    ps -r 
        -> very helpful, if u want to delete current running process
    cat terminal_inat_images_select.txt
        -> to see progress, very convenient
    ps -p 302021
        -> to investigate a running PID
    cat /var/www/html/eol_php_code/update_resources/connectors/terminal_inat_images_select.out
        -> to monitor runtime
    ls -lt /var/www/html/eol_php_code/applications/blur_detection_opencv_eol/eol_images/
    ls -lt /var/www/html/eol_php_code/update_resources/connectors/terminal_inat_images_select.out
    ls /extra/other_files/iNat_image_DwCA/cache_image_score/
    find /extra/other_files/iNat_image_DwCA/cache_image_score/ -type f | wc -l
    kill -9 302021

inat_images_3Mcap	Fri 2022-03-25 08:42:08 AM	{"agent.tab":71631, "media_resource.tab":1644277, "taxon.tab":290388, "time_elapsed":{"sec":1369861.24, "min":22831.02, "hr":380.52, "day":15.86}}

https://dev.to/ko31/using-imagemagick-to-easily-split-an-image-file-13hb
-> split image into 16 equal parts.

*/

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$params                     = json_decode(@$argv[2], true);
$taxonID = @$params['taxonID'];
// exit("\ntaxonID: [$taxonID]\n");

$source_dwca = 'inat_images';           //resource generated from inat_images.php (150 images per taxon) --- media_resource.tab : 10836311
$source_dwca = 'inat_images_20limit';   //resource generated from inat_images.php --- media_resource.tab : 3292778
$source_dwca = 'inat_images_100limit';  //resource generated from inat_images.php --- media_resource.tab : 8742707 - future ideal, eventually
$source_dwca = 'inat_images_40limit';   //resource generated from inat_images.php --- media_resource.tab : 5144786 - currently being used

$resource_id = 'inat_images_100cap'; //new resource --- stopped --- did not materialize

/* 1st combo: finished OK
$source_dwca = 'inat_images_40limit';   //resource generated from inat_images.php --- media_resource.tab : 5,144,786
$resource_id = 'inat_images_3Mcap';     //new resource (update in DwCA_Utility.php)
*/

// /* 2nd combo: currently processing...
$source_dwca = 'inat_images_100limit';  //resource generated from inat_images.php --- media_resource.tab : 8,742,707 - future ideal, eventually
$resource_id = 'inat_images_3Mcap_2';   //new resource (update in DwCA_Utility.php)
// */

$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$source_dwca.'.tar.gz';
// $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$source_dwca.'.tar.gz'; //during dev only

process_resource_url($dwca_file, $resource_id, $timestart, $params);


function process_resource_url($dwca_file, $resource_id, $timestart, $params)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file, $params);

    $excluded_rowtypes = false;
    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon'); //'http://eol.org/schema/agent/agent'

    /* This will be processed in iNatImagesSelectAPI.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>