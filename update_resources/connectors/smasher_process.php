<?php
namespace php_active_record;
/* First draft of EOL Dynamic Hierarchy: Smasher output processing: https://eol-jira.bibalex.org/browse/TRAM-580 */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DHSmasherOutputAPI');
$timestart = time_elapsed();

// This is build using this google sheet: https://docs.google.com/spreadsheets/d/1mBgsVZi1hqcwz37ZZI_5mEHdlXS_RiXpP_CY3RnLzXA/edit#gid=0
// and this Dropbox folder: https://www.dropbox.com/scl/fo/nn34xhcjcoxnuro65ryve/AACaBly_BUjkJDHMdHBr-hoka?dl=0&oref=e&r=AAdw397jN5KF6_UtynJjAYlSPhAGW7V4VHBVxs7tSv4Rrj0brXhqgzCKAOvkTbx0j-Tv6NI8eLsU1QHfg3o8VTy8r6GqE0jbHGXkn0S411EhTgS3hd1dQaIIILlsVFUJhpqGuD3Y1PYN_lApCOIQL5JiLCSQfQwvFe7KhsqHx3311A&sm=1
$p["smasher"] = array("desc" => "Smasher Output file", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/EOLDynamicHierarchyDraftAug2017/dwh_taxa.txt");
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
$p["ictv"] = array("desc" => "ICTV Virus Taxonomy", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwh_taxa_accepted.txt");
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
$p["WOR"] = array("desc" => "WoRMS: use original WoRMS2EOL file for this", "url" => "http://localhost/cp/WORMS/WoRMS2EoL/taxon.txt");
$p["ZOR"] = array("desc" => "Zoraptera Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-zoraptera-v1.4/taxon.txt");

$func = new DHSmasherOutputAPI($p);
$func->start();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
