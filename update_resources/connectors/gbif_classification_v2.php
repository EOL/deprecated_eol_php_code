<?php
namespace php_active_record;
/* GBIF classification update - https://eol-jira.bibalex.org/browse/DATA-1826 
gbif_classification	    Monday 2019-09-23 09:15:26 PM	{"taxon.tab":2845723}
gbif_classification_pre	Tuesday 2019-11-12 12:52:55 PM	{"taxon.tab":2674301,"time_elapsed":{"sec":452183.7,"min":7536.4,"hr":125.61,"day":5.23}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 'gbif_classification_pre2';
require_library('connectors/GBIF_classificationAPI');

$func = new GBIF_classificationAPI($resource_id);
// /* main operation --- will generate: gbif_classification_pre.tar.gz. Will run in eol-archive.
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
// */

/* 2 Reminders: 
------------------------------------------------------------------------------------------------------
1. For eoldynamichierarchywithlandmarks.zip, the meta.xml is manually edited by Eli.
That is edited rowtype = http://rs.tdwg.org/dwc/terms/taxon
The orig value is wrong (occurrence).
------------------------------------------------------------------------------------------------------
2. For eolpageids.csv, this was also edited by Eli. Added headers, that is column names (DH_id,EOL_id).
e.g. Array(
            [DH_id] => -1
            [EOL_id] => 2913056
        )
------------------------------------------------------------------------------------------------------
*/

/* utility
$func->utility_compare_2_DH_09(); //just ran locally. Not yet in eol-archive
*/

//====================================================================================================
/* Use the DH09 EOL_id for the remaining conflicts between the API & DH09 mappings.
      Since I'm using manually edited eoldynamichierarchywithlandmarks/meta.xml (wrong rowtype in meta.xml)
      and
      eolpageids.csv with added headers. SO THIS WILL BE RUN IN Mac Mini ONLY. */
/*
$resource_id = 'gbif_classification';
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/gbif_classification_pre.tar.gz';
$dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_2/gbif_classification_pre.tar.gz';
require_library('connectors/DwCA_Utility');
$func = new DwCA_Utility($resource_id, $dwca_file);

// Orig in meta.xml has capital letters. Just a note reminder.

$preferred_rowtypes = array();
// This 1 will be processed in GBIF_classificationAPI.php which will be called from DwCA_Utility.php
// http://rs.tdwg.org/dwc/terms/Taxon

$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
*/
//====================================================================================================

// /* tests
// run_tests($func);
// */

// run_1test($func);
// Used APIs for debugging:
// https://eol.org/api/pages/1.0/84.json?details=true
// https://eol.org/api/search/1.0.json?q=Limbochromis&page=1&exact=true



/* utility ========================== works OK
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";

$undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
else           echo "\nOK: All acceptedNameUsageID have entries.\n";
===================================== */
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

/*
For #7

These should NOT anymore suggest an EOLid. Nothing found in API.
- 7367811 7899521 Verbascum cheiranthifollum var. cheiranthifollum
- 9738186 2370086 Limbochromis robertsi robertsi
- 9674520 2370086 Limbochromis robertsi van-den Audenaerde & Loiselle, 1971

*/
function run_1test($func)
{
    // $sciname = 'Verbascum cheiranthifollum var. cheiranthifollum'; //none

    // $sciname = 'Erica multiflora subsp. multiflora'; //52540300
    // $sciname = 'Erica multiflora multiflora'; //should be 52540300

    // $sciname = 'Limbochromis robertsi robertsi'; //none
    // $sciname = 'Limbochromis robertsi van-den Audenaerde & Loiselle, 1971'; //none
    // $sciname = 'Limbochromis robertsi'; //46572794

    $sciname = 'Najas'; //35130
    $rec = Array(
        "http://rs.tdwg.org/dwc/terms/taxonID" => '2865618',
        "http://rs.tdwg.org/dwc/terms/datasetID" => '7ddf754f-d193-4cc9-b351-99906754a03b',
        "http://rs.tdwg.org/dwc/terms/scientificName" => 'Najas L.',
        "http://rs.tdwg.org/dwc/terms/scientificNameAuthorship" => 'L.',
        "http://rs.gbif.org/terms/1.0/canonicalName" => 'Najas',
        "http://rs.tdwg.org/dwc/terms/taxonRank" => 'genus',
        "http://rs.tdwg.org/dwc/terms/taxonomicStatus" => 'accepted',
    );
    
    $sciname = 'Najas marina angustifolia'; //35130
    $rec = Array(
        "http://rs.tdwg.org/dwc/terms/taxonID" => '7952446',
        "http://rs.tdwg.org/dwc/terms/datasetID" => '0e61f8fe-7d25-4f81-ada7-d970bbb2c6d6',
        "http://rs.tdwg.org/dwc/terms/scientificName" => 'Najas marina var. angustifolia A.Braun',
        "http://rs.tdwg.org/dwc/terms/scientificNameAuthorship" => 'A.Braun',
        "http://rs.gbif.org/terms/1.0/canonicalName" => 'Najas marina angustifolia',
        "http://rs.tdwg.org/dwc/terms/taxonRank" => 'variety',
        "http://rs.tdwg.org/dwc/terms/taxonomicStatus" => 'accepted',
    );
    
    // /*
    $sciname = 'Cingulata';
    // $sciname = 'Felis ocreata griselda';
    // $sciname = 'Enallagma cyathigerum vernale';
    // $sciname = 'Vicia';
    // $sciname = 'Najas';
    // $sciname = 'Saccharomycetes';
    // $sciname = 'Verbascum cheiranthifollum cheiranthifollum'; //should be 60976
    // $sciname = 'Limbochromis robertsi van-den'; //should be 46572793 by Eli BUT NOT 10885
    // $sciname = 'Limbochromis robertsi robertsi'; //should be 46572793 by Eli BUT NOT 10885
    // $sciname = 'Pelmatochromis'; //should be 10885
    // $sciname = 'Polychaeta';

    $rec = Array(
        'http://rs.tdwg.org/dwc/terms/taxonID' => 'xxx',
        'http://rs.tdwg.org/dwc/terms/datasetID' => 'xxx',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'xxx',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => 'xxx',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'xxx',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'xxx',

        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'genus',
        // 'http://rs.tdwg.org/dwc/terms/taxonRank' => 'species',
        // 'http://rs.tdwg.org/dwc/terms/taxonRank' => 'class',
        // 'http://rs.tdwg.org/dwc/terms/taxonRank' => 'subspecies',
        // 'http://rs.tdwg.org/dwc/terms/taxonRank' => 'variety',
        // 'http://rs.tdwg.org/dwc/terms/taxonRank' => 'family',
        // 'http://rs.tdwg.org/dwc/terms/taxonRank' => 'order',
    );
    // */
    
    
    $ret = $func->main_sciname_search($sciname, $rec);
    if($ret) print_r($ret); //good debug
    else echo "\nNo API result.\n";
}
function run_tests($func)
{
    $sciname = 'Ciliophora'; //should be 46724417
    $rec = Array(
        'http://rs.tdwg.org/dwc/terms/taxonID' => '3269382',
        'http://rs.tdwg.org/dwc/terms/datasetID' => '7ddf754f-d193-4cc9-b351-99906754a03b',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'Ciliophora Petr.',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => 'Petr.',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'Ciliophora',
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'genus',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'accepted'
    );
    run_test($sciname, $rec, $func);

    $sciname = 'Cavernicola'; $rec = Array( //should be 46481316
        'http://rs.tdwg.org/dwc/terms/taxonID' => '4774221',
        'http://rs.tdwg.org/dwc/terms/datasetID' => '9ca92552-f23a-41a8-a140-01abaa31c931',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'Cavernicola Barber, 1937',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => 'Barber, 1937',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'Cavernicola',
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'genus',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'accepted'
    );
    run_test($sciname, $rec, $func);
    
    $sciname = 'Sphinx'; $rec = Array( //should be 50708
        'http://rs.tdwg.org/dwc/terms/taxonID' => '1864404',
        'http://rs.tdwg.org/dwc/terms/datasetID' => 'd8fb1600-d636-4b35-aa0d-d4f292c1b424',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'Sphinx Linnaeus, 1758',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => 'Linnaeus, 1758',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'Sphinx',
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'genus',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'accepted'
    );
    run_test($sciname, $rec, $func);
    
    // $sciname = 'Erica multiflora subsp. multiflora';
    $sciname = 'Erica multiflora multiflora'; //should be 52540300
    $rec = Array(
        'http://rs.tdwg.org/dwc/terms/taxonID' => '7328508',
        'http://rs.tdwg.org/dwc/terms/datasetID' => '7ddf754f-d193-4cc9-b351-99906754a03b',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'Erica multiflora subsp. multiflora',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => '',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'Erica multiflora multiflora',
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'subspecies',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'accepted'
    );
    run_test($sciname, $rec, $func);
    
    $sciname = 'Macronotops sexmaculatus'; $rec = Array( //should be 52612677
        'http://rs.tdwg.org/dwc/terms/taxonID' => '1081098',
        'http://rs.tdwg.org/dwc/terms/datasetID' => '7ddf754f-d193-4cc9-b351-99906754a03b',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'Macronotops sexmaculatus (Kraatz, 1894)',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => '(Kraatz, 1894)',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'Macronotops sexmaculatus',
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'species',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'accepted'
    );
    run_test($sciname, $rec, $func);
    
    $sciname = 'Capsosiraceae'; $rec = Array( //should be 45281240 (or 3267)
        'http://rs.tdwg.org/dwc/terms/taxonID' => '1891',
        'http://rs.tdwg.org/dwc/terms/datasetID' => '7ddf754f-d193-4cc9-b351-99906754a03b',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'Capsosiraceae',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => '',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'Capsosiraceae',
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'family',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'accepted'
    );
    run_test($sciname, $rec, $func);
    
    $sciname = 'Saccharomycetes'; $rec = Array( //should be 5678
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'class'
    ); run_test($sciname, $rec, $func);
    $sciname = 'Pelmatochromis'; $rec = Array( //should be 10885
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'genus'
    ); run_test($sciname, $rec, $func);
}
function run_test($sciname, $rec, $func)
{
    $ret = $func->main_sciname_search($sciname, $rec);
    // print_r($ret); //good debug
    /* Array(
        [id] => 46724417
        [title] => Ciliophora
        [link] => https://eol.org/pages/46724417
        [content] => Ciliophora; Ciliophora Petrak in H. Sydow & Petrak, 1929
    )*/
    if($sciname == 'Ciliophora') {
        if($ret['id'] == '46724417') echo "\n -OK $sciname \n";
        else                         echo "\n -Error $sciname \n";
    }
    if($sciname == 'Cavernicola') {
        if($ret['id'] == '46481316') echo "\n -OK $sciname \n";
        else                         echo "\n -Error $sciname \n";
    }
    if($sciname == 'Sphinx') {
        if($ret['id'] == '50708') echo "\n -OK $sciname \n";
        else                      echo "\n -Error $sciname \n";
    }
    if($sciname == 'Erica multiflora multiflora') {
        if($ret['id'] == '52540300') echo "\n -OK $sciname \n";
        else                         echo "\n -Error $sciname \n";
    }
    if($sciname == 'Macronotops sexmaculatus') {
        if($ret['id'] == '52612677') echo "\n -OK $sciname \n";
        else                         echo "\n -Error $sciname \n";
    }
    if($sciname == 'Capsosiraceae') {
        if($ret['id'] == '45281240') echo "\n -OK $sciname \n";
        elseif($ret['id'] == '3267') echo "\n -OK $sciname \n";
        else                         echo "\n -Error $sciname \n";
    }
    if($sciname == 'Saccharomycetes') {
        if($ret['id'] == '5678') echo "\n -OK $sciname \n";
        else                     echo "\n -Error $sciname \n";
    }
    if($sciname == 'Pelmatochromis') {
        if($ret['id'] == '10885') echo "\n -OK $sciname \n";
        else                      echo "\n -Error $sciname \n";
    }
}
?>