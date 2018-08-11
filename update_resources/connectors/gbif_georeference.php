<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFoccurrenceAPI');
$timestart = time_elapsed();
$resource_id = 1;

/* tests...
$path2 = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_taxa_csv/";
$taxonkey = 2;
$final = get_md5_path($path2, $taxonkey);
echo "\n[$final]\n";
exit;
*/


$func = new GBIFoccurrenceAPI($resource_id);
$func->start(); //normal operation

/* utilities - did not use anymore sice eol-archive Jenkins was able to download big files from GBIF.
$func->divide_Passeriformes(); //utility
$Aves_without_Passeriformes = "Charadriiformes 63,393,276; Anseriformes 52,707,292; Accipitriformes 27,627,396; Piciformes 22,129,714; Columbiformes 19,342,107; Pelecaniformes 19,114,549; Gruiformes 8,037,030; Suliformes 7,410,096; 
    Apodiformes 7,059,640; Falconiformes 5,894,143; Podicipediformes 5,347,434; Galliformes 4,659,430; Psittaciformes 4,584,173; Coraciiformes 4,513,038; Strigiformes 3,357,522; Cuculiformes 2,476,840; 
    Gaviiformes 1,889,386; Procellariiformes 1,523,815; Caprimulgiformes 846,944; Ciconiiformes 733,124; Bucerotiformes 592,615; Sphenisciformes 491,392; Coliiformes 298,584; Trogoniformes 240,170; 
    Phoenicopteriformes 167,253; Musophagiformes 144,624; Otidiformes 139,762; Tinamiformes 113,376; Casuariiformes 68,197; Pteroclidiformes 49,287; Struthioniformes 40,424; Phaethontiformes 29,365; 
    Rheiformes 8,760; Opisthocomiformes 7,178; Eurypygiformes 6,493; Cariamiformes 5,504; Apterygiformes 1,525; Leptosomiformes 1,081; Mesitornithiformes 549; Unknown order 299,336";    
$func->divide_Passeriformes($Aves_without_Passeriformes, 40000000); //utility
*/

// $func->save_ids_to_text_from_many_folders(); //utility, important as last step

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function get_md5_path($path, $taxonkey)
{
    $md5 = md5($taxonkey);
    $cache1 = substr($md5, 0, 2);
    $cache2 = substr($md5, 2, 2);
    if(!file_exists($path . $cache1)) mkdir($path . $cache1);
    if(!file_exists($path . "$cache1/$cache2")) mkdir($path . "$cache1/$cache2");
    return $path . "$cache1/$cache2/";
}
?>
