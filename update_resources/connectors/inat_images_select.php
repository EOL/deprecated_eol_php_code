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
-> to process only one taxon. e.g. Gadus morhua done

php inat_images_select.php _ '{"taxonID":"c74d829f291b0471dc8e469dec09a3dc"}'
-> https://www.inaturalist.org/taxa/78856	Ribes indecorum done

php inat_images_select.php _ '{"taxonID":"802348bd4ba248a598da11918485f140"}'
-> 	https://www.inaturalist.org/taxa/61495	Erythemis simplicicollis Say, 1839	Animalia	Arthropoda	Insecta	Odonata	

php inat_images_select.php _ '{"taxonID":"07cb0ee9203934e5fc3fbc2ccfcee1e3"}'
-> 	https://www.inaturalist.org/taxa/372465	Trigoniophthalmus alternatus done

php inat_images_select.php _ '{"taxonID":"e4fdd48749104fcd0c01c1ae79eed4ce"}'
-> 	https://www.inaturalist.org/taxa/129115	Knautia arvensis (L.) Coult.	Plantae	Tracheophyta	Magnoliopsida	Dipsacales	

$ nohup php inat_images_select.php _ > terminal_inat_images_select.out
  nohup php inat_images_select.php _ > terminal_inat_images_select_Aug7.out

-> use 'nohup' so it continues even after logging out of the terminal

For diagnostics:
    ps --help simple
    ps -r 
        -> very helpful, if u want to check current running processes
        -> e.g. 462805 pts/0    R+     0:16 php inat_images_select.php _
    
    cd /var/www/html/eol_php_code/update_resources/connectors/
    tail terminal_inat_images_select.out
    tail terminal_inat_images_select_Aug7.out

    cat terminal_inat_images_select.out
        -> to see progress, very convenient
    ps -p 462805
        -> to investigate a running PID
    kill -9 462805
        -> to kill a running PID
    cat /var/www/html/eol_php_code/update_resources/connectors/terminal_inat_images_select.out
        -> to monitor runtime
    ls -lt /var/www/html/eol_php_code/applications/blur_detection_opencv_eol/eol_images/
    ls -lt /var/www/html/eol_php_code/update_resources/connectors/terminal_inat_images_select.out
    ls /extra/other_files/iNat_image_DwCA/cache_image_score/
    find /extra/other_files/iNat_image_DwCA/cache_image_score/ -type f | wc -l
    wc -l /extra/eol_php_resources/inat_images_3Mcap_working/media_resource_working.tab
    wc -l /extra/eol_php_resources/inat_images_3Mcap2_working/media_resource_working.tab

    cat /extra/eol_php_resources/inat_images_3Mcap_working/media_resource_working.tab
    
    grep "https://inaturalist-open-data.s3.amazonaws.com/photos/54756158/original.jpeg" /extra/eol_php_resources/inat_images_3Mcap_working/media_resource_working.tab
    

inat_images_3Mcap	Fri 2022-03-25 08:42:08 AM	{"agent.tab":71631, "media_resource.tab":1644277, "taxon.tab":290388, "time_elapsed":{"sec":1369861.24, "min":22831.02, "hr":380.52, "day":15.86}}

https://dev.to/ko31/using-imagemagick-to-easily-split-an-image-file-13hb
-> split image into 16 equal parts.
*/

/* ----------------------------------- test functions
if(Functions::is_production())  $cache_path = '/extra/other_files/iNat_image_DwCA/cache_image_score/';
else                            $cache_path = '/Volumes/AKiTiO4/web/cp/iNat_image_DwCA/cache_image_score/';
require_library('connectors/CacheMngtAPI');
$func2 = new CacheMngtAPI($cache_path);

require_library('connectors/iNatImagesSelectAPI');
$func = new iNatImagesSelectAPI(false, false, false, false);
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_11.jpg'; done
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_06.jpg'; //done
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_08.jpg'; done
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_17.jpg'; //done
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_19.jpg'; //done
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_14.jpg'; done
// Mar 31
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_20.jpg'; done
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_10.jpg'; done
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_03.jpg'; done
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Knautia_arvensis/original_04.jpg'; done

$accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Ribes_indecorum/original_0.jpg';
$accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Ribes_indecorum/original_4.jpg';
$accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Ribes_indecorum/original_9.jpg';
$accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Ribes_indecorum/original_20.jpg';
// $accessURI = 'http://localhost/other_files/iNat_imgs/PLANT_Ribes_indecorum/original_13.jpg';

// $accessURI = 'http://localhost/other_files/iNat_imgs/dragonfly/original4.jpg';
// $accessURI = 'http://localhost/other_files/iNat_imgs/dragonfly/original2.jpg';
// $accessURI = 'http://localhost/other_files/iNat_imgs/dragonfly/original3.jpg';
// $accessURI = 'https://inaturalist-open-data.s3.amazonaws.com/photos/529119/original.jpg';
$accessURI = 'https://inaturalist-open-data.s3.amazonaws.com/photos/54776039/original.jpeg';

$uris = array('https://inaturalist-open-data.s3.amazonaws.com/photos/54784100/original.jpeg', 'https://inaturalist-open-data.s3.amazonaws.com/photos/54847938/original.jpg');
    // 42.3468312806 | 121.217790016
    // 42.3468312806 | 121.217790016

foreach($uris as $accessURI) {
    $arr = $func->get_blurriness_score($accessURI, false, $func2); //2nd param true means overwrite download, will re-download
    print_r($arr);
    // if(file_exists($arr['local'])) $arr = $func->average_score($arr);
    // else exit("\ndoes not exist: ".$arr['local']."\n");
    // print_r($arr);
}

exit("\n-end test functions-\n");
----------------------------------- */

/* test
$url = "https://static.inaturalist.org/photos/10116865/original.jpg";
$url = "http://static.inaturalist.org/photos/2535955/original.jpg";
$url = "https://static.inaturalist.org/photos/7741420/original.jpg";
require_library('connectors/iNatImagesSelectAPI');
$func = new iNatImagesSelectAPI(false, false, false, false);
// $photo_id = $func->get_photo_id_from_url($url); echo "\n$url\n[$photo_id]\n"; //working but not used.
$new_url = $func->switch_domain_on_image_url($url); echo "\n[$url]\n[$new_url]\n";
exit("\n-end test-\n");
*/


/*
NEED TO INVESTIGATE THIS: https://inaturalist-open-data.s3.amazonaws.com/photos/54776039/original.jpeg --- 127.496908501 | 694.308756733

e1786636763fb9018b5a21525ae50992	b796680b6e72c867ee7f4ed072ddbf37	http://purl.org/dc/dcmitype/StillImage	image/jpeg			https://inaturalist-open-data.s3.amazonaws.com/photos/54756158/original.jpeg	https://www.inaturalist.org/photos/54756158	2019-10-22T07:04:02-07:00	en	http://creativecommons.org/licenses/by-nc/4.0/	Rafael Angel Arenas Wong	158ba8069d90c10d4f82293b0140f710; 8c848336cd6f220161b832e014483fdd	127.496908501 | 694.308756733
1b04d4327f8486790313e1dfef67ee22	21fb140b620560501d12e2c171219eba	http://purl.org/dc/dcmitype/StillImage	image/jpeg			https://inaturalist-open-data.s3.amazonaws.com/photos/54776039/original.jpeg	https://www.inaturalist.org/photos/54776039	2019-09-16T14:43:48-07:00	en	http://creativecommons.org/licenses/by-nc/4.0/	darcyoh	158ba8069d90c10d4f82293b0140f710; 4831cbd7f59193ac911557d9d5dba0b5	127.496908501 | 694.308756733

57d673a38a122a637e3f2a3d51f41c07	59c7e97030753cb4407386efd8fcde66	http://purl.org/dc/dcmitype/StillImage	image/jpeg			https://inaturalist-open-data.s3.amazonaws.com/photos/54784100/original.jpeg	https://www.inaturalist.org/photos/54784100	2019-10-18T14:44:30-07:00	en	http://creativecommons.org/licenses/by-nc/4.0/	Nathaniel Sharp	158ba8069d90c10d4f82293b0140f710; 0bedb513782fed9622030f2100e15a2f	42.3468312806 | 121.217790016
7043c075c7b033daa13a8d551dbd603e	4b1ceb15ad5703853b61701d409e4fc0	http://purl.org/dc/dcmitype/StillImage	image/jpeg			https://inaturalist-open-data.s3.amazonaws.com/photos/54847938/original.jpg	https://www.inaturalist.org/photos/54847938	2019-10-23T16:20:07-07:00	en	http://creativecommons.org/licenses/by-nc/4.0/	Marco Floriani	158ba8069d90c10d4f82293b0140f710; a402fd28e0d7b1d621e5819f1f2927e5	42.3468312806 | 121.217790016

bc0102c3c1da436762d17ed6fc0c30ea	d9fa7351cb85353b36f32e51af9d0cef	http://purl.org/dc/dcmitype/StillImage	image/jpeg			https://inaturalist-open-data.s3.amazonaws.com/photos/54828166/original.jpeg	https://www.inaturalist.org/photos/54828166	2019-10-22T19:04:37-07:00	en	http://creativecommons.org/licenses/by-nc/4.0/	Donna Pomeroy	158ba8069d90c10d4f82293b0140f710; a039991f706754ef9356cbd4730f8215	151.008833318 | 206.412103619
7bd01a82c22f26635d5972e01c1c002f	b786f06eb68b82b3b36a21b35808a0f4	http://purl.org/dc/dcmitype/StillImage	image/jpeg			https://inaturalist-open-data.s3.amazonaws.com/photos/54832329/original.jpeg	https://www.inaturalist.org/photos/54832329	2019-10-21T20:45:15-07:00	en	http://creativecommons.org/licenses/by-nc/4.0/	Arno Beidts	158ba8069d90c10d4f82293b0140f710; bba39dd8d128817796b4389cb4a8776a	151.008833318 | 206.412103619

grep "https://inaturalist-open-data.s3.amazonaws.com/photos/54828166/original.jpeg" /extra/eol_php_resources/inat_images_3Mcap_working/media_resource_working.tab
*/

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$params                     = json_decode(@$argv[2], true);
$taxonID = @$params['taxonID'];
// exit("\ntaxonID: [$taxonID]\n");

$source_dwca = 'inat_images';           //resource generated from inat_images.php (150 images per taxon) --- media_resource.tab : 10,836,311
$source_dwca = 'inat_images_20limit';   //resource generated from inat_images.php --- media_resource.tab : 3,292,778
$source_dwca = 'inat_images_100limit';  //resource generated from inat_images.php --- media_resource.tab : 8,742,707 - future ideal, eventually
$source_dwca = 'inat_images_40limit';   //resource generated from inat_images.php --- media_resource.tab : 5,144,786 - currently being used
$resource_id = 'inat_images_100cap';    //new resource --- stopped --- did not materialize

/* 1st combo: finished OK
$source_dwca = 'inat_images_40limit';   //resource generated from inat_images.php --- media_resource.tab : 5,144,786
$resource_id = 'inat_images_3Mcap';     //new resource (update in DwCA_Utility.php)
*/

// /* 2nd combo: FINALLY agreed upon to be used as iNat image resource
$source_dwca = 'inat_images_100limit';  //resource generated from inat_images.php --- media_resource.tab : 8,742,707 - future ideal, eventually
$resource_id = 'inat_images_3Mcap_2';   //new resource (update in DwCA_Utility.php)
// */

/*IMPORTANT: after generating from cmdline inat_images_3Mcap_2.tar.gz don't forget to chmod 775 it. */

/* 3rd combo: did not materialize
$source_dwca = 'inat_images';  //resource generated from inat_images.php (150 images per taxon) --- media_resource.tab : 10,836,311
$resource_id = 'inat_images_3Mcap_3';   //new resource (update in DwCA_Utility.php)
*/

if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$source_dwca.'.tar.gz';
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$source_dwca.'.tar.gz'; //during dev only

process_resource_url($dwca_file, $resource_id, $timestart, $params);

function process_resource_url($dwca_file, $resource_id, $timestart, $params)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file, $params);

    $excluded_rowtypes = false;
    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon'); //'http://eol.org/schema/agent/agent' //normal operation

    /* This will be processed in iNatImagesSelectAPI.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>