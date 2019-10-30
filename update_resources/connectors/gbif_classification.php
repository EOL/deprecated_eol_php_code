<?php
namespace php_active_record;
/* GBIF classification update - https://eol-jira.bibalex.org/browse/DATA-1826 
gbif_classification	Monday 2019-09-23 09:15:26 PM	{"taxon.tab":2845723}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 'gbif_classification_pre';
require_library('connectors/GBIF_classificationAPI');

$func = new GBIF_classificationAPI($resource_id);
// /* main operation
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
// */

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

/* tests
run_tests($func);
*/

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

function run_tests($func)
{
    $sciname = 'Ciliophora';
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

    $sciname = 'Cavernicola'; $rec = Array(
        'http://rs.tdwg.org/dwc/terms/taxonID' => '4774221',
        'http://rs.tdwg.org/dwc/terms/datasetID' => '9ca92552-f23a-41a8-a140-01abaa31c931',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'Cavernicola Barber, 1937',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => 'Barber, 1937',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'Cavernicola',
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'genus',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'accepted'
    );
    run_test($sciname, $rec, $func);
    
    $sciname = 'Sphinx'; $rec = Array(
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
    $sciname = 'Erica multiflora multiflora';
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
    
    $sciname = 'Macronotops sexmaculatus'; $rec = Array(
        'http://rs.tdwg.org/dwc/terms/taxonID' => '1081098',
        'http://rs.tdwg.org/dwc/terms/datasetID' => '7ddf754f-d193-4cc9-b351-99906754a03b',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'Macronotops sexmaculatus (Kraatz, 1894)',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => '(Kraatz, 1894)',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'Macronotops sexmaculatus',
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'species',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'accepted'
    );
    run_test($sciname, $rec, $func);
    
    $sciname = 'Capsosiraceae'; $rec = Array(
        'http://rs.tdwg.org/dwc/terms/taxonID' => '1891',
        'http://rs.tdwg.org/dwc/terms/datasetID' => '7ddf754f-d193-4cc9-b351-99906754a03b',
        'http://rs.tdwg.org/dwc/terms/scientificName' => 'Capsosiraceae',
        'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship' => '',
        'http://rs.gbif.org/terms/1.0/canonicalName' => 'Capsosiraceae',
        'http://rs.tdwg.org/dwc/terms/taxonRank' => 'family',
        'http://rs.tdwg.org/dwc/terms/taxonomicStatus' => 'accepted'
    );
    
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
        if($ret['id'] == '46724417') echo "\n -OK";
        else                         echo "\n -Error";
    }
    if($sciname == 'Cavernicola') {
        if($ret['id'] == '46481316') echo "\n -OK";
        else                         echo "\n -Error";
    }
    if($sciname == 'Sphinx') {
        if($ret['id'] == '50708') echo "\n -OK";
        else                      echo "\n -Error";
    }
    if($sciname == 'Erica multiflora multiflora') {
        if($ret['id'] == '52540300') echo "\n -OK";
        else                         echo "\n -Error";
    }
    
    if($sciname == 'Macronotops sexmaculatus') {
        if($ret['id'] == '52612677') echo "\n -OK";
        else                         echo "\n -Error";
    }
    if($sciname == 'Capsosiraceae') {
        if($ret['id'] == '45281240') echo "\n -OK";
        elseif($ret['id'] == '3267') echo "\n -OK";
        else                         echo "\n -Error";
    }



}
?>