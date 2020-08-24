<?php
namespace php_active_record;
/* DATA-1842: EOL image bundles for Katie

wget -q https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /extra/other_files/image_bundles/xxx/740.027116-1.jpg

wget -nc https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /Volumes/AKiTiO4/other_files/bundle_images/xxx/740.027116-1.jpg

php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":919224, "sci":"wormy guy"}'
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":328672, "sci":"Panthera leo"}'

php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":695, "sci":"Aves"}'
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":164, "sci":"Arthropoda"}'
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":1642, "sci":"Mammalia"}'

php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":7631, "sci":"Chiroptera"}' //only has 17K+ images
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":747, "sci":"Lepidoptera"}'

Next batch as of Apr 10, 2020: Squamata (Lizards And Snakes), Anura (Frogs And Toads), Coleoptera (Beetles), and Carnivora (Carnivores)
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":1704, "sci":"Squamata"}'
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":1553, "sci":"Anura"}'
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":345, "sci":"Coleoptera"}'
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":7662, "sci":"Carnivora"}'
Next batch as of Jun 29, 2020:
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":282, "sci":"Angiosperms"}'
Next batch as of Aug 24, 2020
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":282, "sci":"Angiosperms", "with_limit_images_per_family_YN" = 1}' ///DATA-1861
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server

print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$param = json_decode($params['json'], true);
print_r($param);

require_library('connectors/Eol_v3_API');
$resource_id = '';
$func = new Eol_v3_API($resource_id);

if($param['sci'] == 'Angiosperms') {
    echo "\ngoes here 100\n";
    require_library('connectors/DHConnLib');
    $func2 = new DHConnLib($resource_id);
    $func2->initialize_get_ancestry_func();
}
else $func2 = false;

if(Functions::is_production()) $path = '/extra/other_files/bundle_images/files/';
else                           $path = '/Volumes/AKiTiO4/other_files/bundle_images/files/';


// $destination = CONTENT_RESOURCE_LOCAL_PATH.'images_for_'.str_replace(" ", "_", $param['sci']).".txt"; //false;

if(@$param['with_limit_images_per_family_YN']) {
    echo "\ngoes here 200\n";
    
    // /* max 10 images per family bundles
    $destination = $path.'images_for_'.str_replace(" ", "_", $param['sci'])."_max10imgPerFam.txt"; //false;
    $func->get_images_per_eol_page_id($param, array(), $destination, 20000, $func2); //normal operation
    // */
}
else { //original bundles
    echo "\ngoes here 300\n";
    
    /* 1K bundles
    $destination = $path.'images_for_'.str_replace(" ", "_", $param['sci']).".txt"; //false;
    $func->get_images_per_eol_page_id($param, array(), $destination, 1000); //normal operation
    */

    // /* 20K bundles
    $destination = $path.'images_for_'.str_replace(" ", "_", $param['sci'])."_20K.txt"; //false;
    $func->get_images_per_eol_page_id($param, array(), $destination, 20000, $func2); //normal operation
    // */

    /* 10K bundles
    $destination = $path.'images_for_'.str_replace(" ", "_", $param['sci'])."_10K.txt"; //false;
    $func->get_images_per_eol_page_id($param, array(), $destination, 10000); //normal operation
    */
}

/* working but not used for now...
$func->bundle_images_4download_per_eol_page_id($param, $destination); //normal operation
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>