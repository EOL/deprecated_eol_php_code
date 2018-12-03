<?php
namespace php_active_record;
/* First draft of EOL Dynamic Hierarchy: Smasher output processing: https://eol-jira.bibalex.org/browse/TRAM-580 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
// echo "\n[".Functions::canonical_form("Aahithis Schallreuter 1988")."]";
// echo "\n[".Functions::canonical_form("Abstrusomyzus Jensen & Stoetzel, 1999")."]";
// echo "\n[".Functions::canonical_form("Aages prior Barovskij, 1926")."]";
// exit("\n");

require_library('connectors/DHSmasherOutputAPI');
$timestart = time_elapsed();

// This is built using this google sheet: https://docs.google.com/spreadsheets/d/1mBgsVZi1hqcwz37ZZI_5mEHdlXS_RiXpP_CY3RnLzXA/edit#gid=0
// and this Dropbox folder: https://www.dropbox.com/scl/fo/nn34xhcjcoxnuro65ryve/AACaBly_BUjkJDHMdHBr-hoka?dl=0&oref=e&r=AAdw397jN5KF6_UtynJjAYlSPhAGW7V4VHBVxs7tSv4Rrj0brXhqgzCKAOvkTbx0j-Tv6NI8eLsU1QHfg3o8VTy8r6GqE0jbHGXkn0S411EhTgS3hd1dQaIIILlsVFUJhpqGuD3Y1PYN_lApCOIQL5JiLCSQfQwvFe7KhsqHx3311A&sm=1
$p["smasher"] = array("desc" => "Smasher Output file", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/EOLDynamicHierarchyDraftAug2017/dwh_taxa.txt");
$p["EHE"] = array("desc" => "EOL Hierarchy Entries (EHE)", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/richness_and_names.tsv");

$p["AMP"] = array("desc" => "Amphibia Genera & Species", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/amphibia/amphibia.txt");
$p["APH"] = array("desc" => "Aphid Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-aphid-v8.6/taxon.txt");
$p["BLA"] = array("desc" => "Cockroach Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-blattodea-v8.8/taxon.txt");
$p["COL"] = array("desc" => "Coleorrhyncha Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-coleorrhyncha-v9.6/taxon.txt");
$p["COR"] = array("desc" => "Coreoidea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-coreoidea-v8.6/taxon.txt");
$p["DER"] = array("desc" => "Dermaptera Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-dermaptera-v8.6/taxon.txt");
$p["EET"] = array("desc" => "Earthworms", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/eolearthwormpatch/taxa.txt");
$p["EMB"] = array("desc" => "Embioptera Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-embioptera-v8.6/taxon.txt");
$p["gbif"] = array("desc" => "GBIF Backbone Taxonomy (original version)", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/backbone-current/Taxon.tsv");
$p["GRY"] = array("desc" => "Grylloblattodea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-grylloblattodea-v1.4/taxon.txt");

// $p["ictv"] = array("desc" => "ICTV Virus Taxonomy", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwh_taxa_accepted.txt");
$p["ictv"] = array("desc" => "ICTV Virus Taxonomy", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/ictv-virustaxonomy-with-higherclassification/taxon.tab");

$p["IOC"] = array("desc" => "IOC World Bird List with higherClassification", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/ioc-birdlist-with-higherclassification/taxon.tab");
$p["lhw"] = array("desc" => "World Checklist of Hornworts and Liverworts", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/liverhornworts/liverhornworts.txt");
$p["LYG"] = array("desc" => "Lygaeoidea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-lygaeoidea-v1.0/taxon.txt");
$p["MAN"] = array("desc" => "Mantophasmatodea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-mantophasmatodea-v1.4/taxon.txt");
$p["MNT"] = array("desc" => "Mantodea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-mantodea-v8.6/taxon.txt");
$p["ODO"] = array("desc" => "World Odonata List", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/odonata/odonata.txt");
$p["ONY"] = array("desc" => "Oliveira et al. 2012 Onychophora", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/oliveira2012onychophora/taxa.txt");
$p["ORTH"] = array("desc" => "Orthoptera Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-orthoptera-v12.6/taxon.txt");
$p["PHA"] = array("desc" => "Phasmida Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-phasmida-v10.6/taxon.txt");
$p["PLE"] = array("desc" => "Plecoptera Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-plecoptera-v8.6/taxon.txt");
$p["PPG"] = array("desc" => "Pteridophyte Phylogeny Group Classification", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/ppg12016/ferntaxa.txt");
$p["PSO"] = array("desc" => "Psocodea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-psocodea-v8.6/taxon.txt");
$p["SPI"] = array("desc" => "Spiders Species List", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/spiders/spiders.txt");
$p["TER"] = array("desc" => "Krishna et al. 2013 Termites", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/termites/termites.txt");
$p["TPL"] = array("desc" => "The Plant List with literature", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca/taxa.txt");
$p["trunk"] = array("desc" => "Dynamic Hierarchy Trunk 14 June 2017", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dynamichierarchytrunk14jun201720170615085118/taxon.txt");
$p["WOR"] = array("desc" => "WoRMS: use original WoRMS2EOL file for this", "url" => "http://localhost/cp/WORMS/WoRMS2EoL/taxon.txt"); //will use downloaded Aug 16, 2017
$p["ZOR"] = array("desc" => "Zoraptera Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-zoraptera-v1.4/taxon.txt");


// /* sample IOC process.txt  -> should get 18990
$rek = Array(
    'taxonID' => -725255,
    'acceptedNameUsageID' => -725255,
    'parentNameUsageID' => -574211,
    'scientificName' => 'Abeillia',
    'taxonRank' => 'genus',
    'source' => 'IOC:0a215d62991d7bce254cd66daea778ba',
    'taxonomicStatus' => 'accepted' );
$first = Array(
    'first_source' => 'IOC:0a215d62991d7bce254cd66daea778ba',
    'acronym' => 'IOC',
    'taxon_id' => '0a215d62991d7bce254cd66daea778ba' );
$sciname = "Abeillia";
// */

/* should get: 46501030
$rek = Array(
    'taxonID' => -678537,
    'acceptedNameUsageID' => -678537,
    'parentNameUsageID' => -558588,
    'scientificName' => 'Aahithis Schallreuter, 1988', //'Aahithis',
    'taxonRank' => 'genus',
    'source' => 'WOR:769244',
    'taxonomicStatus' => 'accepted');
$first = Array(
    'first_source' => 'WOR:769244',
    'acronym' => 'WOR',
    'taxon_id' => 769244);
$sciname = "Aahithis Schallreuter, 1988";
*/



/*
$rek = Array(
    'taxonID' => -2318121,
    'acceptedNameUsageID' => -2318121,
    'parentNameUsageID' => -2213496,
    'scientificName' => 'Abstrusomyzus',
    'taxonRank' => 'genus',
    'source' => 'APH:1166419',
    'taxonomicStatus' => 'accepted');
$first = Array(
    'first_source' => 'APH:1166419',
    'acronym' => 'APH',
    'taxon_id' => 1166419,
    'scientificName' => 'Abstrusomyzus Jensen & Stoetzel, 1999');
$sciname = "Abstrusomyzus Jensen & Stoetzel, 1999";
*/
/*
$rek = Array(
    'scientificName' => 'Abeillia abeillei abeillei',
    'taxonRank' => 'subspecies',
    'source' => 'IOC:5bccbf8955a2b2ad4b0722696a733326');
$first = Array(
    'first_source' => 'IOC:5bccbf8955a2b2ad4b0722696a733326',
    'acronym' => 'IOC',
    'taxon_id' => '5bccbf8955a2b2ad4b0722696a733326',
    'scientificName' => 'Abeillia abeillei abeillei (Lesson, R & Delattre, 1839)');
$sciname = 'Abeillia abeillei abeillei (Lesson, R & Delattre, 1839)';
*/
/*
$rek = Array(
    'taxonID' => -1645981,
    'acceptedNameUsageID' => -1645981,
    'parentNameUsageID' => -1410966,
    'scientificName' => 'Aages',
    'taxonRank' => 'genus',
    'source' => 'gbif:4741594',
    'taxonomicStatus' => 'accepted');
$first = Array(
    'first_source' => 'gbif:4741594',
    'acronym' => 'gbif',
    'taxon_id' => 4741594,
    'scientificName' => 'Aages Barovskii, 1926');
$sciname = "Aages Barovskii, 1926";
*/
/*
$rek = Array
(
    'taxonID' => -1311393,
    'scientificName' => 'Abdopus',
    'taxonRank' => 'genus',
    'source' => "trunk:f013ee1d-ac41-4b26-8158-5a53b5d86a9b,WOR:409947");
$first = Array
(
    'first_source' => "trunk:f013ee1d-ac41-4b26-8158-5a53b5d86a9b",
    'acronym' => 'trunk',
    'taxon_id' => "f013ee1d-ac41-4b26-8158-5a53b5d86a9b",
    'scientificName' => 'Abdopus');
$sciname = "Abdopus";
*/
/*
$rek = Array(
    'taxonID' => -1221427,
    'scientificName' => 'Acanthagenys rufogularis',
    'taxonRank' => 'species',
    'source' => 'IOC:b65f3a45d872392933244436eaa2ceb9',
    'taxonomicStatus' => 'accepted'
);
$first = Array
(
    'first_source' => 'IOC:b65f3a45d872392933244436eaa2ceb9',
    'acronym' => 'IOC',
    'taxon_id' => 'b65f3a45d872392933244436eaa2ceb9',
    'scientificName' => 'Acanthagenys rufogularis Gould, 1838'
);
$sciname = "Acanthagenys rufogularis Gould, 1838";
*/


// $arr = $func->get_eol_id($rek, $first, $sciname); 
// echo "\n------"; print_r($arr); echo "\n------";

$p["folder"] = "nothing";//"gbif 47.27";//"WOR 43.8hrs";//"TPL 46.7hrs";//"nothing"; // "less3big" done //"trunk done"; //"IOC done"; //default is 'smasher', but can be any of the 30 acronyms

// /*
$func = new DHSmasherOutputAPI($p);
$func->start($p["folder"]); //value is any of the 30 acronyms, used to generate individual DWC-A files for each of the 30
// */

// $func->utility();    //creating local cache based on resource files from google sheet
// $func->utility2();   //caching EOL API search name | AND | getting EOLid
// $func->utility3();    // creation of EOL Hierarchy Entries (EHE) aa ab ac... text files  //last generated Aug 16, 2017 Eastern

/*
append_multiple_hierarchies();
$c = Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . "/EOL_dynamic_hierarchy/taxa.txt"); echo "\nEOL: [$c]\n";
$c = Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . "/gbif/taxa.txt");                  echo "\ngbif: [$c]\n";
$c = Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . "/TPL/taxa.txt");                   echo "\nTPL: [$c]\n";
$c = Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . "/WOR/taxa.txt");                   echo "\nWOR: [$c]\n";
$c = Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . "/less3big/taxa.txt");              echo "\nless3big: [$c]\n";
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function append_multiple_hierarchies()
{
    $WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH."/EOL_dynamic_hierarchy/taxa.txt", "a");
    $arr = array("WOR", "TPL", "less3big"); //started with WOR
    foreach($arr as $acronym)
    {
        $filename = CONTENT_RESOURCE_LOCAL_PATH."/$acronym/taxa.txt";
        $i = 0;
        foreach(new FileIterator($filename) as $line => $row) {
            $i++;
            if(($i % 10000) == 0) echo " --$i-- [$acronym] ";
            if($i > 1 && $row) fwrite($WRITE, $row . "\n");
        }
    }
    fclose($WRITE);
}
/* --2717000-- [gbif]  --2718000-- [gbif] Sending get request to http://api.gbif.org/v1/species/462429 : only attempt :: [lib/Functions.php [204]]<br>
Curl error (http://api.gbif.org/v1/species/462429): The requested URL returned error: 404 Not Found :: [lib/Functions.php [204]]<br>
attempt 1 failed, will try again after 2 seconds :: [lib/connectors/DHSmasherOutputAPI.php [923]]<br>
Will delay for 1 minute(s), then will try again. Number of attempts will be reset. :: [lib/connectors/DHSmasherOutputAPI.php [923]]<br>
Sending get request to http://api.gbif.org/v1/species/462429 : only attempt :: [lib/Functions.php [204]]<br>
Curl error (http://api.gbif.org/v1/species/462429): The requested URL returned error: 404 Not Found :: [lib/Functions.php [204]]<br>
attempt 1 failed, will try again after 2 seconds :: [lib/connectors/DHSmasherOutputAPI.php [923]]<br>
failed download file after 1 attempts :: [lib/connectors/DHSmasherOutputAPI.php [923]]<br>

From gbif but not found in API
Completed update_resources/connectors/smasher_process.php :: [ []]<br>
*/

/* last count, success OK: actual total count is: total: [2724673]

--2723000-- [gbif]  --2724000-- [gbif] 

elapsed time = 2836.3440699333 minutes 
elapsed time = 47.272401165556 hours 
*/
?>
