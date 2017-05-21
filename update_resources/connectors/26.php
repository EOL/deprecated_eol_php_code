<?php
namespace php_active_record;
/*
WORMS archive
Now partner provides/hosts a DWC-A file. Connector also converts Distribution text into structured data.
estimated execution time: 30 mins, excluding download time of the archive file from partner's server

                24Sep'14    20Nov'14                    1Dec'14     1Apr'15     8Apr    15Jun   8March
agent:          [922]       948                         948         1015        1033    1044    1245
measurement:    [1,172,968] 1,484,488   diff 311,520    293,645     383423      383981  384798  411206
media_resource: [101,513]   102,009     diff 496        102,009     134461      134708  135570  144212
occurrence:     [279,966]   576,055                     291,683     380880      381457  382283
reference:      [319987]    322257                      322257      550506      552080  554390  581465
taxon:          [311866]    313006      diff 1,140      313006      539550      540877  512283  532578
vernacular:     [42231]     42226                       42226       46654       46657   46661   47809

with media objects: 
[accepted]  =>      327966
[synonym]   =>      211583
[count]     =>      539549

dynamic hierarchy   27Apr2017   18May2017
[accepted] =>       303570      300105
[synonym] =>        134132      135024
[] =>               5329        0
[count] =>          443031      435129

total no parent:    134134      135026
/terms/taxon:       443440      435608

exec time: ~30 minutes
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WormsArchiveAPI');
$timestart = time_elapsed();
$resource_id = 26;

// /* //main operation
$func = new WormsArchiveAPI($resource_id);
$func->get_all_taxa("taxonomy"); //'taxonomy' or 'media_objects'
Functions::finalize_dwca_resource($resource_id);
// */

// /* utility ==========================
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);

$without = $func->get_all_taxa_without_parent($resource_id, true); //true means output will write to text file
echo "\nTotal taxa without parents:" . count($without)."\n";
// =====================================*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

/* as of March 7, 2017
[ranks] => Array
    (
        [kingdom] => 
        [phylum] => 
        [subphylum] => 
        [order] => 
        [class] => 
        [subclass] => 
        [family] => 
        [suborder] => 
        [subfamily] => 
        [superfamily] => 
        [superorder] => 
        [infraorder] => 
        [superclass] => 
        [genus] => 
        [subkingdom] => 
        [tribe] => 
        [subgenus] => 
        [species] => 
        [variety] => 
        [subspecies] => 
        [section] => 
        [subsection] => 
        [form] => 
        [subvariety] => 
        [subform] => 
        [] => 
        [subtribe] => 
    )

[status] => Array
    (
        [accepted] => 
        [synonym] => 
        [] => 
    )

[establishmentMeans] => Array
    (
        [] => 
        [Alien] => 
        [Native - Endemic] => 
        [Native] => 
        [Origin uncertain] => 
        [Origin unknown] => 
        [Native - Non-endemic] => 
    )

[occurrenceStatus] => Array
    (
        [present] => 
        [excluded] => 
        [doubtful] => 
    )
*/
?>