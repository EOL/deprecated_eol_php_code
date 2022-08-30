<?php
namespace php_active_record;
/* DATA-1909: resource metadata and summary-from-resource-data export from CKAN
nohup php update_resources/connectors/data_4opentraits.php _ > terminal.out
-> use 'nohup' so it continues even after logging out of the terminal
For diagnostics:
    ps --help simple
    ps -r 
        -> very helpful, if u want to check current running processes
    cat terminal_textmine_loop.out
        -> to see progress, very convenient
    ps -p 85790
        -> to investigate a running PID
    kill -9 85790
        -> to kill a running PID

40 9's
19 11's
total: 59 files
1st run 2.5 hrs
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/Data_OpenTraits');
$timestart = time_elapsed();

/*
$url = "https://opendata.eol.org/dataset/86081133-3db1-4ffc-8b1f-2bbba1d1f948/resource/b9951366-90e8-475e-927e-774b95faf7ed/download/hardtomatch.tar.gz";
$url = "http://rs.tdwg.org/dwc/terms/taxon";
print_r(pathinfo($url));
echo "\n";
echo pathinfo($url, PATHINFO_BASENAME);
exit("\n-end test-\n");
*/

$func = new Data_OpenTraits();
/* a utility, run once only. But didn't get to do it anymore.
$func->save_higherClassifaction_as_cache();
*/

$func->start(); // main operation

/* test during dev:
$hc[2] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Gnathifera|Syndermata";
$hc[38] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Annelida|Pleistoannelida|Sedentaria|Clitellata|Hirudinea|Acanthobdellidea";
$hc[46] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Annelida|Pleistoannelida|Sedentaria|Clitellata";
$func->process_pipe_delim_values($hc);
*/

/* a utility
$ids = array(46476166, 46476236, 46476707, 46479639, 46479641, 46501816, 46544725);
foreach($ids as $eol_id) $func->lookup_DH($eol_id);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
/*
============================================================
layout: dataset
id: chan-2010
name: Chan, 2010
contentURL: https://opendata.eol.org/dataset/9a357065-cc63-4fcf-8c51-c383d6c82453/resource/d1274220-fa0c-40f0-8e6c-86df6cab0089/download/archive.zip
datasetDOI_URL: https://opendata.eol.org/dataset/chan-2010
contactName: Jen Hammock
contactEmail: secretariat@eol.org|jen.hammock@gmail.org
license: CC0
traitList: UBERON_0002104
higherGeography:
decimalLatitude:
decimalLongitude:
taxon: 
taxon: Eryonoidea
eventDate:
paperDOIcitation: 
description: TY Chan. 2010. Annotated checklist of the world's marine lobsters (Crustacea: Decapoda: Astacidea, Glypheidea, Achelata, Polychelida).  The Raffles Bulletin of Zoology, 2010 Supplement No. 23: 153‚Äì181	https://research.nhm.org/pdfs/31609/31609.pdf
taxaList: 
usefulClasses:
dataStandard:
standardizationScripts:
webpage:
============================================================
============================================================
layout: dataset
id: bruce-1988
name: Bruce, 1988
contentURL: https://opendata.eol.org/dataset/03df4d7a-d388-44f9-954e-82da6af999fc/resource/e199fb19-f500-454a-9839-b25eb5442b4d/download/bruce.zip
datasetDOI_URL: https://opendata.eol.org/dataset/bruce-1988
contactName: Jen Hammock
contactEmail: secretariat@eol.org|jen.hammock@gmail.org
license: CC0
traitList: UBERON_0002104
higherGeography:
decimalLatitude:
decimalLongitude:
taxon: 
taxon: Thaumastochelidae
eventDate:
paperDOIcitation: https://doi.org/10.1071/IT9880903
description: AJ Bruce. 1988. Thaumastochelopsis wardi, gen. et. sp. nov., a new blind deep-sea lobster from the coral sea (Crustacea : Decapoda : Nephropidea). Invertebrate Taxonomy 2(7) 903 - 914	https://doi.org/10.1071/IT9880903
taxaList: 
usefulClasses:
dataStandard:
standardizationScripts:
webpage:
============================================================
*/
?>