<?php
namespace php_active_record;
/* This is a generic connector for converting CSV DwCA to EOL DwCA. First client for this connector is the myspecies.info Scratchpad resources.
e.g. http://www.eol.org/content_partners/373/resources/268 -- Bryozoa of the British Isles

Note: The first choice to use is: php update_resources/connectors/dwca_utility.php _ {resource_id}
But it is running out of memory because the text files are actually CSV files. And dwca_utility.php loads entire extension into memory.

resource 430 used its own 430.php

template:
$resources[res_id] = array('dwca' => "http://xxxxx.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //res_name
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CSV2DwCA_Utility_generic');
$timestart = time_elapsed();

/*
$resources[220] = array('dwca' => "http://diptera.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Scratchpad export - Diptera taxon pages
$resources[268] = array('dwca' => "http://britishbryozoans.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Bryozoa of the British Isles
$resources[549] = array('dwca' => "http://antkey.org/eol-dwca.zip", 'bigfileYN' => false); //Antkey
$resources[363] = array('dwca' => "http://pngbirds.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //PNG_Birds
$resources[754] = array('dwca' => "http://anolislizards.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Anolis Scratchpad
$resources[755] = array('dwca' => "http://xyleborini.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Xyleborini Ambrosia Beetles
$resources[756] = array('dwca' => "http://neotropical-pollination.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Neotropical Pollination
// $resources[884] = array('dwca' => "http://phthiraptera.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Phthiraptera
*/


$resources[550] = array('dwca' => "http://continenticola.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Continenticola Classification -- https://opendata.eol.org/dataset/continenticola-classification-550
$resources[812] = array('dwca' => "http://eolhotlist.myspecies.info/dwca.zip", 'bigfileYN' => false); //EOL hotlist Scratchpad -- https://opendata.eol.org/dataset/eol-hotlist-scratchpad

// /*
$resources[139] = array('dwca' => "http://africanamphibians.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //African Amphibian Lifedesk
$resources[222] = array('dwca' => "http://africhthy.org/eol-dwca.zip", 'bigfileYN' => false); //Mormyridae - African weakly electric fishes
$resources[92] = array('dwca' => "http://alpheidae.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Alpheidae
$resources[160] = array('dwca' => "http://ampullariidae.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Ampullariidae
$resources[231] = array('dwca' => "http://annelida.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Annelida
$resources[84] = array('dwca' => "http://leptogastrinae.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Leptogastrinae LifeDesk
$resources[258] = array('dwca' => "http://apoidea.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Leafcutter, mason, and resin bees
$resources[273] = array('dwca' => "http://avesamericanas.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Colaboraciones Americanas Sobre Aves
// $resources[156] = array('dwca' => "LD", 'bigfileYN' => false); //Genus Opisthostoma
// $resources[163] = array('dwca' => "LD", 'bigfileYN' => false); //Malay Peninsular Terrestrial Molluscs
$resources[164] = array('dwca' => "http://borneanlandsnails.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Bornean Terrestrial Molluscs
// $resources[129] = array('dwca' => "LD", 'bigfileYN' => false); //Cataloging Diversity in the Sacoglossa
$resources[130] = array('dwca' => "http://buccinids.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Buccinid Gastropods
$resources[110] = array('dwca' => "http://camptosomata.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Cryptocephalinae of the World
$resources[485] = array('dwca' => "http://caterpillars.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Plant-Caterpillar-Parasitoid Interactions
$resources[287] = array('dwca' => "http://cephaloleia.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Cephaloleia LifeDesk resource
$resources[144] = array('dwca' => "http://chess.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //ChEss
$resources[61] = array('dwca' => "http://choreutidae.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Metalmark Moths
$resources[746] = array('dwca' => "http://cnidaria.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Cnidaria Lifedesk
$resources[199] = array('dwca' => "http://compositae.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Compositae LifeDesk
$resources[118] = array('dwca' => "http://continenticola.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Continenticola
$resources[109] = array('dwca' => "http://cyanolichens.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Cyanolichen Index
$resources[213] = array('dwca' => "http://emollusks.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //eBivalvia contents
$resources[216] = array('dwca' => "http://hundrednewlichens.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //e100NewLichens
$resources[82] = array('dwca' => "http://hypogymnia.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Hypogymnia lifedesk
$resources[248] = array('dwca' => "http://korupplants.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Vascular Plants of Korup National Park
$resources[232] = array('dwca' => "http://marineinvaders.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Marine Invaders of the NE Pacific
$resources[186] = array('dwca' => "http://neotropicalfishes.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Shorefishes LifeDesk
$resources[246] = array('dwca' => "http://neotropnathistory.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Yanayacu Natural History Research Group
$resources[148] = array('dwca' => "http://neritopsine.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Neritopsine Gastropods LifeDesk
$resources[166] = array('dwca' => "http://newworldcarabidae.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //eAgra
$resources[294] = array('dwca' => "http://opisthobranchia.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Opisthobranchia LifeDesk
$resources[274] = array('dwca' => "http://parmotrema.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Parmotrema
$resources[432] = array('dwca' => "http://pelagics.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Pelagic Invertebrates LifeDesks
$resources[372] = array('dwca' => "http://peracarida.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Marine, terrestrial and freshwater isopods
$resources[235] = array('dwca' => "http://polycladida.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //A Catalogue of Tropical Polyclad Flatworms
$resources[127] = array('dwca' => "http://quercus.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Oaks of the Americas
$resources[250] = array('dwca' => "http://salamandersofchina.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Salamanders of China
$resources[124] = array('dwca' => "http://scarabaeinae.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Scarabaeinae dung beetles
$resources[146] = array('dwca' => "http://simuliidae.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Smithsonian Simuliidae Type Specimens
$resources[174] = array('dwca' => "http://sipuncula.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Sipuncula LifeDesk
$resources[230] = array('dwca' => "http://soostracoda.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Ostracoda Lifedesk
$resources[425] = array('dwca' => "http://squatlobsters.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Squat Lobster LIFEDESK
$resources[147] = array('dwca' => "http://syrphidae.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Flower flies and bee lice
$resources[215] = array('dwca' => "http://terrslugs.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //slug tree
$resources[105] = array('dwca' => "http://tunicata.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Tunicata
$resources[410] = array('dwca' => "http://urbanfloranyc.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Urban Flora of NYC
// */

$debug = array();
foreach($resources as $resource_id => $info) {
    echo "\n --------------------processing $resource_id --------------------\n";
    $func = new CSV2DwCA_Utility_generic($resource_id, $info['dwca']);
    if($func->convert_archive()) {
        Functions::finalize_dwca_resource($resource_id, $info['bigfileYN'], true); //3rd param is deleteFolderYN ------- 2nd params is true coz it is a big file
    }
    else {
        echo "\nPartner's DwCA has a problem. [$resource_id] [".$info['dwca']."]\n";
        $debug['problem with dwca'][$resource_id] = '';
    }
    echo "\n --------------------END $resource_id --------------------\n";
}
if($debug) print_r($debug);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
