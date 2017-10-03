<?php
namespace php_active_record;
/* Orthoptera, Phasmida, Plecoptera - Species File
estimated execution time: orthoptera - 3 hours if it is already cached

orthoptera
media_resource.tab  [96805]
taxon.tab           [44367]
vernacular_name.tab [2053]

images  29952
maps    66853
total   96805

Phasmida
StillImage:         8867
            Map:    3655
            image:  5212
vernacularname:    359
taxon:             4713

Plecoptera
StillImage:         3288
audio/x-wav:        2
            Map:    3235
            image:  53
vernacularname:    728
taxon:             5260
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SpeciesFileAPI');

$timestart = time_elapsed();
$resource_id = 173;

// check_content(); exit;

$func = new SpeciesFileAPI($resource_id);
// $func->some_stats(); exit;

$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function check_content()
{
    // $filename = DOC_ROOT . "/taxon.tab";
    $filename = DOC_ROOT . "/media_resource.tab";
    // $filename = DOC_ROOT . "/vernacular_name.tab";
    $rec = array();
    foreach(new FileIterator($filename) as $line_number => $line) {
        if($line) {
            $values = explode("\t", trim($line));
                // [0] => map_1102231
                // [1] => 1102231
                // [2] => http://purl.org/dc/dcmitype/StillImage
                // [3] => Map
                // [4] => image/jpeg
            
            if(isset($rec["subtype"][$values[3]])) $rec["subtype"][$values[3]]++;
            else $rec["subtype"][$values[3]] = 0;
            
            if(isset($rec["format"][$values[4]])) $rec["format"][$values[4]]++;
            else $rec["format"][$values[4]] = 0;
        }
    }
    print_r($rec);
}
?>
