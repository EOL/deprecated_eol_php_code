<?php
namespace php_active_record;
/*
WORMS archive
Now partner provides/hosts a DWC-A file. Connector also converts Distribution text into structured data.
estimated execution time: 30 mins, excluding download time of the archive file from partner's server
*Started Jenkins execution 2017-Oct-10. Took 22 mins.
                                                                                                        2017
                24Sep'14    20Nov'14                    1Dec'14     1Apr'15     8Apr    15Jun   8March  6-Oct   10-Oct
agent:          [922]       948                         948         1015        1033    1044    1245    1352    1388
measurement:    [1,172,968] 1,484,488   diff 311,520    293,645     383423      383981  384798  411206  425373  428106
media_resource: [101,513]   102,009     diff 496        102,009     134461      134708  135570  144212  152067  160716
occurrence:     [279,966]   576,055                     291,683     380880      381457  382283          422847  425583
reference:      [319987]    322257                      322257      550506      552080  554390  581465  629030  637206
taxon:          [311866]    313006      diff 1,140      313006      539550      540877  512283  532578  573202  581849
vernacular:     [42231]     42226                       42226       46654       46657   46661   47809   72552   73967
                            
                            2017
with media objects:         6-Oct   10-Oct
[accepted]  =>      327966  332194  337287
[count]     =>      539549  573202  581849
[synonym]   =>      211583  203983  207575
[]          =>                      36987

dynamic hierarchy   27Apr2017   18May2017
[accepted] =>       303570      300105
[synonym] =>        134132      135024
[] =>               5329        0
[count] =>          443031      435129

total no parent:    134134      135026          ? not sure about these counts
/terms/taxon:       443440      435608          ? not sure about these counts

                            10-Oct
Total undefined parents:    2503
Total taxa without parents: 207577

Historical:
26	Tuesday 2017-10-10 12:02:32 AM	{"agent.tab":1388,"measurement_or_fact.tab":428106,"media_resource.tab":160716,"occurrence.tab":425583,"reference.tab":637206,"taxon.tab":581849,"vernacular_name.tab":73967}
26	Monday 2017-12-04 07:53:33 PM	{"agent.tab":1403,"measurement_or_fact.tab":431072,"media_resource.tab":164848,"occurrence.tab":428547,"reference.tab":661032,"taxon.tab":603823,"vernacular_name.tab":74103}
26	Friday 2018-03-02 08:54:46 AM	{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116}
26	Wednesday 2018-03-07 10:44:40 AM{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116}
26	Wednesday 2018-03-07 07:09:46 PM{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116} all-hash measurementID

exec time: ~30 minutes
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WormsArchiveAPI');
$timestart = time_elapsed();
ini_set('memory_limit','7096M'); //required

$resource_id = 26;
// $resource_id = "26_taxonomy";

// /* //main operation
$func = new WormsArchiveAPI($resource_id);

//only one of these 2 will run at any given time
$func->get_all_taxa("media_objects"); //'media_objects' is for original resource = 26
// $func->get_all_taxa("taxonomy");      //'taxonomy' not sure when was used

Functions::finalize_dwca_resource($resource_id, false, false); //3rd param should be false so it doesn't remove the /26/ folder which will be used below when diagnosing...
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