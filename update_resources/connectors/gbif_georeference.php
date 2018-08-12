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
// $func->start(); //normal operation

// /* utilities - did not use anymore sice eol-archive Jenkins was able to download big files from GBIF.
/* not needed anymore
$str = "Emberizidae 47,974,189; Corvidae 26,810,696; Fringillidae 26,790,215; Muscicapidae 23,524,613; Paridae 21,411,352; Parulidae 19,862,826; Hirundinidae 13,326,097; Tyrannidae 11,681,875; Sturnidae 10,296,016; 
Troglodytidae 8,482,647; Passeridae 7,908,627; Sittidae 5,986,857; Motacillidae 5,824,783; Mimidae 5,322,296; Turdidae 5,295,616; Icteridae 5,114,154; Meliphagidae 4,783,774; Regulidae 4,642,834; 
Phylloscopidae 4,628,110; Sylviidae 4,412,725; Vireonidae 4,225,714; Cardinalidae 3,254,863; Acrocephalidae 3,168,676; Alaudidae 2,407,476; Bombycillidae 2,401,638; Thraupidae 2,095,446; Cracticidae 2,018,782; 
Acanthizidae 2,008,252; Laniidae 1,894,795; Ploceidae 1,551,590; Estrildidae 1,459,220; Polioptilidae 1,456,911; Aegithalidae 1,443,686; Certhiidae 1,438,771; Cisticolidae 1,425,057; Rhipiduridae 1,335,859; 
Monarchidae 1,228,359; Prunellidae 1,183,662; Pachycephalidae 1,168,217; Pycnonotidae 1,111,132; Furnariidae 907,014; Maluridae 899,084; Zosteropidae 887,133; Campephagidae 802,259; Nectariniidae 802,164; 
Petroicidae 784,399; Pardalotidae 672,523; Calcariidae 556,862; Malaconotidae 552,610; Locustellidae 551,371; Oriolidae 548,871; Thamnophilidae 535,263; Dicruridae 503,299; Cettiidae 393,329; Artamidae 391,443; 
Cinclidae 381,550; Climacteridae 362,350; Remizidae 347,682; Cotingidae 327,210; Leiothrichidae 304,148; Dicaeidae 281,594; Corcoracidae 205,414; Ptilonorhynchidae 195,209; Psophodidae 192,301; Panuridae 184,435; 
Pipridae 176,394; Pomatostomidae 175,360; Ptilogonatidae 170,596; Coerebidae 154,959; Timaliidae 147,177; Viduidae 145,689; Platysteiridae 128,407; Macrosphenidae 105,338; Pellorneidae 96,782; Rhinocryptidae 70,779; 
Menuridae 70,299; Neosittidae 69,981; Grallariidae 65,785; Oreoicidae 57,805; Aegithinidae 43,345; Buphagidae 39,308; Paradisaeidae 38,828; Stenostiridae 38,624; Formicariidae 37,774; Pittidae 36,417; 
Chloropseidae 30,051; Tephrodornithidae 28,863; Prionopidae 26,999; Promeropidae 26,462; Eurylaimidae 21,384; Peucedramidae 17,675; Tichodromidae 15,889; Orthonychidae 15,317; Irenidae 14,816; Vangidae 13,258; 
Conopophagidae 10,769; Donacobiidae 10,757; Nicatoridae 8,073; Machaerirhynchidae 7,253; Pnoepygidae 6,516; Acanthisittidae 5,877; Melanocharitidae 5,506; Dulidae 4,872; Bernieridae 4,771; Dendrocolaptidae 4,474; 
Atrichornithidae 3,137; Melanopareiidae 2,999; Callaeatidae 2,941; Chaetopidae 2,361; Scotocercidae 1,572; Philepittidae 1,551; Paradoxornithidae 1,512; Notiomystidae 1,410; Hyliotidae 1,262; Paramythiidae 1,211; 
Erythrocercidae 1,117; Eupetidae 965; Tityridae 957; Hypocoliidae 875; Passerellidae 838; Cnemophilidae 710; Arcanatoridae 520; Sapayoaidae 494; Pityriaseidae 457; Elachuridae 295; Picathartidae 258; Grallinidae 217; 
Mohoidae 185; Hylocitreidae 105; Urocynchramidae 87; Callaeidae 18; Colluricinclidae 13; Drepanididae 12; Tersinidae 8; Cettidae 4; Falcunculidae 3; Ptiliogonatidae 3; Oxyruncidae 2; Pityriasidae 2; Ephthianuridae 1; 
Eopsaltridae 1; Unknown_family 19,605";
*/

$func->divide_Passeriformes(); //utility
$Aves_without_Passeriformes = "Charadriiformes 63,393,276; Anseriformes 52,707,292; Accipitriformes 27,627,396; Piciformes 22,129,714; Columbiformes 19,342,107; Pelecaniformes 19,114,549; Gruiformes 8,037,030; Suliformes 7,410,096; 
    Apodiformes 7,059,640; Falconiformes 5,894,143; Podicipediformes 5,347,434; Galliformes 4,659,430; Psittaciformes 4,584,173; Coraciiformes 4,513,038; Strigiformes 3,357,522; Cuculiformes 2,476,840; 
    Gaviiformes 1,889,386; Procellariiformes 1,523,815; Caprimulgiformes 846,944; Ciconiiformes 733,124; Bucerotiformes 592,615; Sphenisciformes 491,392; Coliiformes 298,584; Trogoniformes 240,170; 
    Phoenicopteriformes 167,253; Musophagiformes 144,624; Otidiformes 139,762; Tinamiformes 113,376; Casuariiformes 68,197; Pteroclidiformes 49,287; Struthioniformes 40,424; Phaethontiformes 29,365; 
    Rheiformes 8,760; Opisthocomiformes 7,178; Eurypygiformes 6,493; Cariamiformes 5,504; Apterygiformes 1,525; Leptosomiformes 1,081; Mesitornithiformes 549; Unknown order 299,336";    
$func->divide_Passeriformes($Aves_without_Passeriformes, 40000000); //utility
// */

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
