<?php
namespace php_active_record;
/* LifeDesk to Scratchpad migration
estimated execution time:
This generates an archive (.tar.gz) file in: DOC_ROOT/tmp/ folder
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/LifeDeskToScratchpadAPI');
$timestart = time_elapsed();
// /*
$params = array();
$desks = array("nemertea", "peracarida", "syrphidae", "tunicata", "leptogastrinae", "continenticola", "pelagics", "parmotrema", "liquensbr", "liquensms", "staurozoa", 
    "cnidaria", "porifera", "sacoglossa", "buccinids", "apoidea", "opisthostoma", "borneanlandsnails", "malaypeninsularsnail", "sipuncula", "hawaiilandsnails", 
    "ostracoda", "ampullariidae", "cephaloleia", "mormyrids", "terrslugs", "agrilus", "camptosomata", "urbanfloranyc", "marineinvaders", "neritopsine", 
    "polycladida", "tabanidae", "squatlobsters", "simuliidae", "proctotrupidae", "opisthobranchia", "katydidsfrombrazil", "hypogymnia", "salamandersofchina", 
    "ebasidiolichens", "hundrednewlichens", "molluscacolombia", "lincolnsflorafauna", "arachnids", "congofishes", "indiareeffishes", "olivirv", "avesamericanas", 
    "neotropnathistory", "quercus", "caterpillars", "africanamphibians", "neotropicalfishes", "dinoflagellate", "chess", "diatoms", "deepseacoral", "choreutidae", 
    "taiwanseagrasses", "odonata", "alpheidae", "tearga", "canopy", "naididae", "ebivalvia", "compositae", "korupplants", "scarabaeinae", "cyanolichens", "annelida", 
    "polychaetasouthocean", "batrach", "echinoderms"); // "terrslugs" ***

$desks = array("echinoderms");
foreach($desks as $desk) {
    //remote
    $params[$desk]["remote"]["lifedesk"]          = "http://" . $desk . ".lifedesks.org/eol-partnership.xml.gz";
    $params[$desk]["remote"]["bibtex_file"]       = "";
    $params[$desk]["remote"]["scratchpad_images"] = "";
    $params[$desk]["remote"]["name"]              = $desk;
    //Dropbox
    $params[$desk]["dropbox"]["lifedesk"]          = "";
    $params[$desk]["dropbox"]["bibtex_file"]       = "";
    $params[$desk]["dropbox"]["scratchpad_images"] = "";
    $params[$desk]["dropbox"]["name"]              = $desk;
    //local
    $params[$desk]["local"]["lifedesk"]           = "http://localhost/cp/LD2Scratchpad/" . $desk . "/eol-partnership.xml.gz";
    $params[$desk]["local"]["bibtex_file"]        = "";
    $params[$desk]["local"]["scratchpad_images"]  = "http://localhost/cp/LD2Scratchpad/" . $desk . "/file_importer_image_xls.xls";
    $params[$desk]["local"]["name"]               = $desk;
    $params[$desk]["local"]["scratchpad_biblio"]  = "http://localhost/cp/LD2Scratchpad/" . $desk . "/node_importer_biblio_xls.xls";
    if($desk == "africanamphibians") $params[$desk]["local"]["scratchpad_taxonomy"]= "http://localhost/cp/LD2Scratchpad/" . $desk . "/taxonomy_importer_xls.xls";
    
    $func = new LifeDeskToScratchpadAPI();
    $func->export_lifedesk_to_scratchpad($params[$desk]["local"]);
}
// */
/* start: Generate taxonomy of a LifeDesk 
// neotropicalfishes local
$lifedesk = 'neotropicalfishes';
$lifedesk = 'echinoderms';

$params = array();
$params[$lifedesk]["local"]["lifedesk"]   = "http://localhost/cp/LD2Scratchpad/" . $lifedesk . "/eol-partnership.xml.gz";
$params[$lifedesk]["local"]["name"]       = $lifedesk;
$parameters = $params[$lifedesk]["local"];
$func = new LifeDeskToScratchpadAPI();
$func->export_lifedesk_taxonomy($parameters);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>