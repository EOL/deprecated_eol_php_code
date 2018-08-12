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
$Animalia_without_Chordata = "Arthropoda 81,654,589; Mollusca 6,640,957; Annelida 2,031,985; Cnidaria 1,435,832; Echinodermata 907,437; Porifera 564,454; Brachiopoda 504,619; Bryozoa 338,611; Nematoda 243,731; Cephalorhyncha 168,918; 
                  Platyhelminthes 160,587; Chaetognatha 115,313; Rotifera 64,182; Nemertea 45,238; Ctenophora 40,845; Sipuncula 37,001; Hemichordata 26,923; Phoronida 14,433; Acanthocephala 5,541; Nematomorpha 3,456; Tardigrada 2,854; 
                  Onychophora 2,549; Xenacoelomorpha 1,923; Entoprocta 1,861; Myxozoa 1,364; Gastrotricha 887; Orthonectida 108; Dicyemida 101; Gnathostomulida 36; Cycliophora 3; Micrognathozoa 1; Unknown_phylum 854,566";
$func->generate_url_with_usageKey($Animalia_without_Chordata, "Animalia_without_Chordata"); //utility
$Chordata_without_Aves = "Actinopterygii 26,400,977; Mammalia 14,210,085; Reptilia 5,992,355; Amphibia 4,967,838; Elasmobranchii 1,646,146; Ascidiacea 186,610; Cephalaspidomorphi 86,887; Holocephali 43,804; Thaliacea 43,418; 
                          Appendicularia 35,952; Sarcopterygii 26,316; Myxini 7,913; Leptocardii 4,777; Unknown_class 358,881";
$func->generate_url_with_usageKey($Chordata_without_Aves, "Chordata_without_Aves"); //utility
$Aves_without_Passeriformes = "Charadriiformes 63,393,276; Anseriformes 52,707,292; Accipitriformes 27,627,396; Piciformes 22,129,714; Columbiformes 19,342,107; Pelecaniformes 19,114,549; Gruiformes 8,037,030; Suliformes 7,410,096; 
    Apodiformes 7,059,640; Falconiformes 5,894,143; Podicipediformes 5,347,434; Galliformes 4,659,430; Psittaciformes 4,584,173; Coraciiformes 4,513,038; Strigiformes 3,357,522; Cuculiformes 2,476,840; 
    Gaviiformes 1,889,386; Procellariiformes 1,523,815; Caprimulgiformes 846,944; Ciconiiformes 733,124; Bucerotiformes 592,615; Sphenisciformes 491,392; Coliiformes 298,584; Trogoniformes 240,170; 
    Phoenicopteriformes 167,253; Musophagiformes 144,624; Otidiformes 139,762; Tinamiformes 113,376; Casuariiformes 68,197; Pteroclidiformes 49,287; Struthioniformes 40,424; Phaethontiformes 29,365; 
    Rheiformes 8,760; Opisthocomiformes 7,178; Eurypygiformes 6,493; Cariamiformes 5,504; Apterygiformes 1,525; Leptosomiformes 1,081; Mesitornithiformes 549; Unknown_order 299,336";    
$func->generate_url_with_usageKey($Aves_without_Passeriformes, "Aves_without_Passeriformes"); //utility
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
