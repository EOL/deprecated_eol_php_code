<?php
namespace php_active_record;
/* For the 3rd smasher run.
TRAM-991: Smasher run for DH 2

Note: separation files in zip format is provided by Katja, both for ver 1.0 (newSeparationFiles.zip) and 
                                                                    ver 1.1 (separationFiles.zip) and now
                                                                    ver 2.0 taxonomy.tsv attached in TRAM-991
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
/* e.g. php dws.php _ gbif */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/DHSourceHierarchiesAPI_v3');
$timestart = time_elapsed();
ini_set('memory_limit','7096M'); //required

/*
$haystack = "ASW:v-Diasporus-sapo-Batista-Köhler-Mebert-Hertz-and-Vesely-2016-Zool.-J.-Linn.-Soc.-178:-274.";
$replace = "_elix_";
$needle = ":";
$pos = strpos($haystack, $needle);
if ($pos !== false) {
    $new = substr_replace($haystack, $replace, $pos, strlen($needle));
}
echo "\n$haystack";
echo "\n$new";
exit("\n");
*/

// /* //main operation ------------------------------------------------------------
$resource_id = "2019_04_04";    //for previous
$resource_id = "2021_02_09";    //for TRAM-991
$func = new DHSourceHierarchiesAPI_v3($resource_id);
/*
$func->start($cmdline_params['what']); //main to generate the respective taxonomy.tsv (and synonym.tsv if available).
*/
// $func->syn_integrity_check(); exit("\n-end syn_integrity_check-\n"); //to check record integrity of 
// synoyms spreadsheet: 1XreJW9AMKTmK13B32AhiCVc7ZTerNOH6Ck_BJ2d4Qng
/* but this check is driven by taxonID and NOT by the sciname. It is the sciname that is important.
So generally we don't need this syn_integrity_check(). We can just add to phython file all those we know that are synonyms.
*/

// /* Eli-only-inspired initiative - should work but abandoned for now as Katja lessens the [New DH Synonyms] spreadsheet anyway.
$func->generate_separation_files_using_NewDHSynonyms_googleSheet(); exit("\n-end generate_separation_files_using_NewDHSynonyms_googleSheet-\n");
// */

// $func->generate_python_file();           exit("\n-end generate_python_file-\n"); //to generate script entry to build_dwh.py
// $func->clean_up_destination_folder();    exit("\n-end cleanup-\n");              //to do before uploading hierarchies to eol-smasher server

// $func->test($cmdline_params['what']);                    //for testing only

// this is now OBSOLETE in TRAM-805: Dynamic Hierarchy Version 1.1.
// $func->start($cmdline_params['what'], "CLP_adjustment"); //from CLP #3 from: https://eol-jira.bibalex.org/browse/TRAM-800?focusedCommentId=63045&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63045

// $func->compare_results();                                //a utility to compare results. During initial stages
// -------------------------------------------------------------------------------- */

/* Notes for TRAM-991
----------------------------------------------------------------
based on taxStatus worksheet in google sheet: https://docs.google.com/spreadsheets/d/1A08xM14uDjsrs-R5BXqZZrbI_LiDNKeO6IfmpHHc6wg/edit#gid=2121540051
ODO won't submit any synonym.tsv in Smasher.
So ignore /zFailures/ODO_duplicates_syn.txt
----------------------------------------------------------------
BOM_duplicates_syn.txt.proc has been processed to exclude these synonym duplicates in its synonym.tsv for Smasher
So just ignore /zFailures/BOM_duplicates_syn.txt.proc
----------------------------------------------------------------
From Katja: https://eol-jira.bibalex.org/browse/TRAM-991?focusedCommentId=65627&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65627

Regarding Homonyms, I wasn't sure whether you already had a script to feed homonym information into the separation taxonomy file. 
It sounds like you don't and that's fine. Let's proceed with the basic separation taxonomy.tsv already attached to this ticket. 
Most of the homonyms should already be covered in this file, and we can use the DH Homonyms doc to check for bad merges in the smasher output:
https://docs.google.com/spreadsheets/d/1IMw75qXtEqS9TvHg0fZ1swKxFgvCJCd4XMfrKTpJ2Qk/edit#gid=365059775
I will add the homonyms from resourceHomonyms.txt to this doc. If we get bad merges in this smasher run, we can amend the separation taxonomy as needed and do another smasher run.
----------------------------------------------------------------
HOW TO GENERATE THE taxonomy.tsv separation file:
1. load the taxonomy.tsv given by Katja (attached in TRAM-991) into Numbers
2. remove the extra column (higherClassification)
3. remove the header row
4. save - export to tsv file (taxonomy.tsv) -> this will be our separation file
----------------------------------------------------------------
----------------------------------------------------------------
----------------------------------------------------------------
*/

/* when updating just certain files. e.g. file build_dwh.py
scp build_dwh.py smasher:~/temp/.
scp taxonomy.tsv smasher:~/temp/.
scp synonyms.tsv smasher:~/temp/.

cp build_dwh.py /home/annethessen/reference-taxonomy/
cp synonyms.tsv /home/annethessen/reference-taxonomy/tax/separation/
cp taxonomy.tsv /home/annethessen/reference-taxonomy/tax/separation/
*/

/*
start smasher terminal steps:

step1: from macmini
scp Archive1.zip smasher:~/temp/.
scp separationFiles.zip smasher:~/temp/.

For TRAM-991:
scp Archive.zip smasher:~/temp/.
====================================================================================================
step2:
in smasher
from eagbayani/temp folder

For Previous:
cp Archive1.zip /home/annethessen/reference-taxonomy/t/tax_2019_04/
cp synonyms.tsv /home/annethessen/reference-taxonomy/tax/separation/
cp taxonomy.tsv /home/annethessen/reference-taxonomy/tax/separation/

For TRAM-991:
cp Archive.zip /home/annethessen/reference-taxonomy/t/tax_2021_02/
cd /home/annethessen/reference-taxonomy/t/tax_2021_02/
unzip Archive.zip
cp build_dwh.py /home/annethessen/reference-taxonomy/
cp synonyms.tsv /home/annethessen/reference-taxonomy/tax/separation/
cp taxonomy.tsv /home/annethessen/reference-taxonomy/tax/separation/


cp taxonomy_Katja.tsv /home/annethessen/reference-taxonomy/tax/separation/taxonomy.tsv

====================================================================================================
step:
cp /tax_2019_04/build_dwh.py /home/annethessen/reference-taxonomy/
cp /tax_2021_02/build_dwh.py /home/annethessen/reference-taxonomy/
====================================================================================================
step:
To execute python file that builds dwh on the server type this into command line:
cd /home/annethessen/reference-taxonomy/
bin/jython build_dwh.py
====================================================================================================
step: zip the /test/ folder
zip -r test_2019_04_04.zip test
zip -r test_2021_02_21.zip test
cp test_2021_02_21.zip /home/eagbayani/temp/

zip -r results_2021_02_23.zip test
cp results_2021_02_23.zip /home/eagbayani/temp/

zip -r results_2021_02_23_orig.zip test
cp results_2021_02_23_orig.zip /home/eagbayani/temp/

zip -r results_2021_02_24.zip test
cp results_2021_02_24.zip /home/eagbayani/temp/


#zip -r results_default_taxonomy_tsv.zip test
#zip -r results_big_taxonomy_tsv_with_complete_hierarchy.zip test

====================================================================================================
step: in Mac Mini
scp smasher:~/temp/test_2019_04_04.zip ~/Desktop/
scp smasher:~/temp/test_2021_02_21.zip ~/Desktop/

scp smasher:~/temp/results_2021_02_23.zip ~/Desktop/
scp smasher:~/temp/results_2021_02_23_orig.zip ~/Desktop/

scp smasher:~/temp/results_2021_02_24.zip ~/Desktop/

====================================================================================================
step: copy to eol-archive for Katja

scp taxon_with_higherClassification.tab.zip archive:~/temp/.
scp 2019_04_04.tar.gz archive:~/temp/.
scp results_2019_04_04.zip archive:~/temp/.

Hi Katja, here are the reports. First crack at the DH ver. 1.1:
Here is the raw Smasher output: https://editors.eol.org/other_files/DWH/1.1/results_2019_04_04.zip
Here is the DwCA based on Smasher output: https://editors.eol.org/other_files/DWH/1.1/2019_04_04.tar.gz
Here is the taxon.tab file with higherClassification based on DwCA: https://editors.eol.org/other_files/DWH/1.1/taxon_with_higherClassification.tab.zip


scp results_2021_02_23.zip archive:~/temp/.
scp results_2021_02_23_orig.zip archive:~/temp/.
scp taxonomy.tsv archive:~/temp/.
scp synonyms.tsv archive:~/temp/.


for TRAM-991:
scp ForReview_Feb21.zip archive:~/temp/.
cp ForReview_Feb21.zip /extra/other_files/DWH/2.0/
Hi Katja,
First crack, for review.
Here is the input files and Smasher output files.
https://editors.eol.org/other_files/DWH/2.0/ForReview_Feb21.zip
The separation files are in:
input_2021_02_21/synonyms.tsv
input_2021_02_21/taxonomy.tsv
input_2021_02_21/taxonomy_Katja.tsv - what Katja attached in TRAM-991.
Thanks,
Eli

cp synonyms.tsv /extra/other_files/DWH/2.0/
scp newTaxonomy.tsv smasher:~/temp/.
cp newTaxonomy.tsv /home/annethessen/reference-taxonomy/tax/separation/taxonomy.tsv

scp results_2021_02_24.zip archive:~/temp/.
cp results_2021_02_24.zip /extra/other_files/DWH/2.0/


Hi Katja,
I think I found why there is so few on my first run. COL was excluded in the Smasher app. That is now fixed.
Here is the latest Smasher results using the original (separation) taxonomy.tsv you've attached in TRAM-991.
https://editors.eol.org/other_files/DWH/2.0/results_2021_02_23_orig.zip

Here is the Smasher results using the taxonomy.tsv with added names with complete hierarchy.
https://editors.eol.org/other_files/DWH/2.0/results_2021_02_23.zip
Here is the taxonomy.tsv with added names with complete hierarchy.
https://editors.eol.org/other_files/DWH/2.0/taxonomy.tsv
https://editors.eol.org/other_files/DWH/2.0/synonyms.tsv

For review.
Thanks.


*/


/* This is needed before generating the DwCA. text file will then be appended to MySQL table ids_scinames in DWH database.
$func->save_all_ids_from_all_hierarchies_2MySQL(); exit("\n-end txt 2MySQL-\n"); //one-time only. NOT YET DONE FOR Ver 1.1.
*/
/*
$ mysql -u root -p --local-infile DWH;
copy table structure only:
mysql> CREATE TABLE ids_scinames LIKE ids_scinames_v1;
to load from txt file:
mysql> load data local infile '/Users/eliagbayani/Desktop/eee/eli_tar_gz/eli/write2mysql.txt' into table ids_scinames;
mysql> load data local infile 'write2mysql.txt' into table ids_scinames;

For TRAM-807:
mysql> load data local infile '/Volumes/AKiTiO4/d_w_h/2019_04/zFiles/write2mysql_v2.txt' into table ids_scinames;
*/

/* =========== generate DwCA --- OK
$func->generate_dwca($resource_id);
Functions::finalize_dwca_resource($resource_id, false, false);
=========== */




/* utility ========================== a good utility after generating DwCA --- OK
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);

$undefined_accepted = $func->check_if_all_parents_have_entries($resource_id, true, false, false, "acceptedNameUsageID");
echo "\nTotal undefined_accepted:" . count($undefined_accepted)."\n"; unset($undefined_accepted);
=====================================*/

/* another utility but was never used actually:
$without = $func->get_all_taxa_without_parent($resource_id, true); //true means output will write to text file
echo "\nTotal taxa without parents:" . count($without)."\n";
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
