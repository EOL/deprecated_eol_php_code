<?php
namespace php_active_record;
/* this is a utility to test Pensoft annotation */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

$timestart = time_elapsed();

require_library('connectors/Functions_Pensoft');
require_library('connectors/Pensoft2EOLAPI');

$param = array("task" => "generate_eol_tags_pensoft", "resource" => "all_BHL", "resource_id" => "TreatmentBank", "subjects" => "Uses");

$func = new Pensoft2EOLAPI($param);

/* option 1 works, but it skips a lot of steps that is needed in real-world connector run.
$json = $func->run_partial($desc);
$arr = json_decode($json); print_r($arr);
*/

/* option 2 --- didn't get to work
$basename = "ile_-_173"."ice";
$desc = strip_tags($desc);
$desc = trim(Functions::remove_whitespace($desc));
// $func->results = array();
$arr = $func->retrieve_annotation($basename, $desc); //it is in this routine where the pensoft annotator is called/run
// $arr = json_decode($json); 
print_r($arr);
*/

/*
$sciname = "Gadur morhuaspp.";
if(Functions::valid_sciname_for_traits($sciname)) exit("\n[$sciname] valid\n");
else                                              exit("\n[$sciname] invalid\n");
*/

// /* option 3 from AntWebAPI.php --- worked OK!
// /* This is used for accessing Pensoft annotator to get ENVO URI given habitat string.
$param['resource_id'] = 24; //AntWeb resource ID
require_library('connectors/Functions_Pensoft');
require_library('connectors/Pensoft2EOLAPI');
$pensoft = new Pensoft2EOLAPI($param);
$pensoft->initialize_remaps_deletions_adjustments();
// /* to test if these 4 variables are populated.
echo "\n From Pensoft Annotator:";
echo("\n remapped_terms: "              .count($pensoft->remapped_terms)."\n");
echo("\n mRemarks: "                    .count($pensoft->mRemarks)."\n");
echo("\n delete_MoF_with_these_labels: ".count($pensoft->delete_MoF_with_these_labels)."\n");
echo("\n delete_MoF_with_these_uris: "  .count($pensoft->delete_MoF_with_these_uris)."\n");

$descs = array();
$descs[] = "(crops: peanuts, rice, sugarcane); (littoral: dune); (nest/prey: mud dauber nest [f]); (orchard: grapefruit, orange); (plants: bluebonnets, Indian paintbrush, miscellaneous vegetation, vegetation, next to cotton field); (soil/woodland: saltcedar)";
$descs[] = "SAIAB 60874  , 19  (of 23) specimens, SL 6.6–9.8 cm, Mozambique: Zambezi System : Zambezi River: island bank off the Marromeu harbour, 18 ◦ 17 ′ 08.63 ′′ S, 35 ";
$descs[] = "Additional material examined. Afghanistan: Nuristan province, Kamdeš [= Kamdesh, ca. 35°25'N 71°20'E], 1400 m, 19.ix.1971, O.N. Kabakov leg., 1 male, 1 female ( ZMAS), 1 male, female ( JRUC); same data, 1600 m, 20.ix.1971, 1 male, 2 females ( ZMAS); Nuristan province, Paprok [ca. 35°33'N 71°17'E], 2000 m, 25.ix.1971, O.N. Kabakov leg., 2 males ( ZMAS); Nuristan province, N Waygal [ca. 35°12'N 70°58'E], 3500 m, 2.vii.1972, O.N. Kabakov leg., 1 male, 2 females ( ZMAS), 1 male ( JRUC); N Waygal [ca. 35°12'N 70°59'E], 2700 m, 6.vii.1972, O.N. Kabakov leg., 2 males ( ZMAS); same data, 7.vii.1972, 2 females ( ZMAS); Pakistan: Azad Jammu and Kashmir, Muzaffarabad env., top of Leepa valley [ca. 34°20'N, 73°55'E], 3200–3300 m, 14.vi.1997, Heinz leg., 2 males, 3 females ( SMNS); Dir [= Khyber Pakhtunkhwa province], Gujar Levy Post env., Lawarai pass [ca. 35°21'N, 71°48'E], 2800–3100 m, 5.–7.vii.1997, Heinz leg., 1 female ( SMNS); Gilgit-Baltistan, Nanga Parbat Mt., Rama env. [ca. 35°20'N, 74°48'E], 3000–3500 m, 27.–30.vi.1997, Heinz leg., 1 male, 4 females ( SMNS); Northern Areas [= Gilgit-Baltistan], Gilgit district, Bagrot Valley, 36°02'32.6''N, 74°34'8.3''E, 2600 m, 250 m from Hinarki Glacier snout, pitfall trap, 25.x.–2.xi.2008, L. Latella leg., 2 males, 1 female ( MCSV); Northern Areas [= Gilgit-Baltistan], Gilgit district, Kargah Valley, 35°54'45.8''N, 74°15'26.9''E, 1611 m, 26.x.– 3.xi.2008, pitfall trap, L. Latella leg., 2 males ( MCSV, SMNS), 1 female ( MCSV); Northern Areas [= Gilgit- Baltistan], Gilgit district, Kargah Valley, Neelo Cave, 35°53'51.4''N, 74°14'17.8''E, 1694 m, 3.xi.2008, L. Latella leg., 2 males, 1 female ( MCSV); Nepal: Myagdi district, Daulagiri Himal, upper Myagdi Khola valley, Dshungel Camp [= Jungle Camp, ca. 28°36'N, 83°23'E], 3050 m, 2.vii.1998, Berndt & Schmidt leg., 2 females ( SMNS); Myagdi district, Daulagiri, SE slope, upper Rahucat Khola (river) [=Rahughat Khola], upper Dwari village [ca. 28°30'N, 83°28'E], 2200 m, 11.v.2002, Schmidt leg., 2 males, 1 female ( SMNS); Kali Gandaki valley, upper Lete [ca. 28°37'N, 83°38'E], 2900 m, 19.v.2002, J. Schmidt leg., 2 males ( SMNS); Annapurna Mts., South Himal, Dhasia Khola [ca. 28°28'N, 84°00'E], 2900 m, 21.v.2001, J. Schmidt leg., 1 female ( SMNS); Mustang district, Dhaulagiri, SE slope, SW slope of Lete pass [ca. 28°24'N, 83°41'E], 3800–3900 m, 15.v.2002, J. Schmidt leg., 1 female ( SMNS); Ganesh Himal, Jaisuli Kund env. [ca. 28°17'N, 85°05'E], 4300–4500 m, 13.–16.vi.2000, Expedition I. Ghalé, S. Tamang, R. Santa & S. Gurung, 2 females ( SMNS); India: “MUSEUM PARIS / DARDJEELING / HARMAND [leg.] 1836-91 [p] // 1836 / 1891 [hw, round label] // TYPE [p, red characters] //";
$descs[] = "Myślenice distr. , Osieczany, Stobiecki, 1 ex ( ISEA) ; Nowy S cz distr., Rytro­Radziejowa, Stobiecki, 4 exx ( ISEA) ; Nowy S cz distr., Stobiecki, 12 exx ( ISEA) ; Przemyśl, Kotula , 5 exx ( ISEA) ; Przemyśl, Trella , 31 exx ( ISEA) ; Przeworsk distr. , Rocibórz, Stobiecki, 1 ex ( ISEA) ; Racibórz , 1903, H. Nowotny, 2 exx ( USMB) ; Rzeszów distr. , Czudec, Stobiecki, 2 exx ( ISEA) ; Tarnów, Stobiecki , 1 ex ( ISEA) ; Warszawa­ Bielany , 23.V.1900, 6 exx ( USMB) ; 18.V.1901, 2 exx ( USMB) ; 21.IV.1902, 5 exx ( USMB) ; Warszawa­ Saska Kępa , 13.IX.1891, 1 ex ( USMB) ; 2.X.1901, 1 ex ( USMB) ; Warszawa­ Ṡwider , 2.VI.1900, 1 ex ( USMB) ; 3.VI.1902, 3 exx ( USMB) ; 20.VI.1904, 1 ex ( USMB) ; 9.VII.1904, 1 ex ( USMB) ; Warszawa­Zegrze , 1.VI.1933, Tenenbaum, 1 ex ( FMNH) ; Wrocław, Letzner , 1 ex ( DEI) . Romania: Cornutel , 6 exx ( NMW) ; Galatz, Letzner , 2 exx ( DEI) ; Sinaia Prahova­Tal , 900–1600 m, 11–17.VIII.1982 ,M. Schülke, 5 exx ( ZMHB). Russia: Poltava , VIII.1957, 1 ex ( MNHN) . Slovakia: Košice, 1924, Schüvalley";
$descs[] = "sоmetimes аррeаring соmрletelу brоwn if lightсоlоured with соmрlete mediаn саrinа аnd unifоrmlу meshlike соriасeоus tо аlutасeоus.";
$descs[] = " ; Mutìnice, J. Myślenice distr.  Slovakia: Košice, 1924";
$descs[] = "usuаllу with соmрlete mediаn саrinа hаving а соmрletelу sсulрtured sсrоbаl а соmрlete mediаn саrinа";
$descs[] = "Male genitalia: DISTINCTLY SHApED ( FIgS. 2E, f  ). TEgmEN ( FIg. 2E ) RATHER WIDE, WIDEST AT mIDDLE, mEDIAL DISTAL EXCISION DEEp, V-SHApED (RATIO DTIN/LETE = 0.32–0.33), INNER mARgINS WITHOUT ANY pROJECTION; RATIO LETE/WITE = 1.19–1.20. RATIO THLE/LETE = 0.21. MEDIAN LObE Of AEDEAgUS mODERATELY ELONgATE, RATIO LEAE/WIAE = 1.90– 1.95, EXHIbITINg mAXImUm WIDTH NEARLY AT DISTAL THIRD, WITH NARROWLY AND ObTUSELY SpATULATE ApEX ( FIg. 2f  ). MAIN SCLERITES Of INTERNAL SAC (ENDOpHALLUS) LONg AND ROD-SHApED IN bOTH DORSAL AND LATERAL vIEW.";

$i = 0;
foreach($descs as $desc) { $i++;
    $ret = run_desc($desc, $pensoft);
    echo "\n[$i] - "; echo("[$ret]");
    if($i == 1) { if($ret == "mud|orchard|soil|dune")           echo " -OK-"; else echo " -ERROR-"; }
    if($i == 2) { if($ret == "island|river")                    echo " -OK-"; else echo " -ERROR-"; }
    if($i == 3) { if($ret == "glacier|valley|cave|pass|river")  echo " -OK-"; else echo " -ERROR-"; }
    if($i == 4) { if($ret == "")                                echo " -OK-"; else echo " -ERROR-"; }
    if($i == 5) { if($ret == "")                                echo " -OK-"; else echo " -ERROR-"; }
    if($i == 6) { if($ret == "")                                echo " -OK-"; else echo " -ERROR-"; }
    if($i == 7) { if($ret == "")                                echo " -OK-"; else echo " -ERROR-"; }
    if($i == 8) { if($ret == "")                                echo " -OK-"; else echo " -ERROR-"; }
}
echo "\n-end tests-\n";
// */
function run_desc($desc, $pensoft) {
    $basename = md5($desc);
    $desc = strip_tags($desc);
    $desc = trim(Functions::remove_whitespace($desc));
    $pensoft->results = array();
    $final = array();
    if($arr = $pensoft->retrieve_annotation($basename, $desc)) {
        // print_r($arr);
        foreach($arr as $uri => $rek) $final[] = $rek['lbl'];
    }
    return implode("|", $final);    
}
?>