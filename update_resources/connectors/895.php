<?php
namespace php_active_record;
/* estimated execution time:  56 minutes eol-archive

        31Dec   5Jan    12Jan   15Jan
Taxa:   2056    1951    1944    1936
Image:  3353    3293    3302    3293
agent   1       1       1       1

895	Sunday 2018-08-05 11:50:38 AM	    {"agent.tab":1,"media_resource.tab":4339,"taxon.tab":2331} eol-archive
895	Tuesday 2018-08-07 10:35:07 AM	    {"agent.tab":1,"media_resource.tab":4339,"taxon.tab":2331} eol-archive
895	Wednesday 2018-08-08 11:36:22 AM	{"agent.tab":1,"media_resource.tab":4339,"taxon.tab":2331}
895	Thursday 2018-08-16 10:10:04 AM	    {"agent.tab":1,"media_resource.tab":4339,"taxon.tab":2331}
895	Sunday 2018-09-16 10:59:31 AM	    {"agent.tab":1,"media_resource.tab":4374,"taxon.tab":2344}
895	Tuesday 2018-10-16 11:01:46 AM	    {"agent.tab":1,"media_resource.tab":4394,"taxon.tab":2351}
895	Friday 2018-11-16 11:06:32 AM	    {"agent.tab":1,"media_resource.tab":4421,"taxon.tab":2358}
895	Sunday 2018-12-16 11:03:50 AM	    {"agent.tab":1,"media_resource.tab":4441,"taxon.tab":2362}
895	Wednesday 2019-01-16 11:08:07 AM	{"agent.tab":1,"media_resource.tab":4419,"taxon.tab":2352}
895	Saturday 2019-02-16 11:10:52 AM	    {"agent.tab":1,"media_resource.tab":4443,"taxon.tab":2362}
895	Monday 2019-02-18 10:15:36 AM	    {"agent.tab":1, "media_resource.tab":4443, "taxon.tab":2362}
895	Wed 2020-12-16 10:11:44 AM	        {"agent.tab":1, "media_resource.tab":4457, "taxon.tab":2369, "time_elapsed":false}
895	Sun 2022-09-25 10:34:55 AM	        {"agent.tab":1, "media_resource.tab":162,  "taxon.tab":114, "time_elapsed":false} - error, limbo state
895	Mon 2022-09-26 07:14:36 AM	        {"agent.tab":1, "media_resource.tab":1470, "taxon.tab":1038, "time_elapsed":false} - salvaged OK, acceptable
895	Mon 2022-09-26 01:52:59 PM	        {"agent.tab":1, "media_resource.tab":1844, "taxon.tab":1263, "time_elapsed":false}
-> improved coverage, but will still check for false taxon names e.g. 'butterfly'
895	Tue 2022-09-27 01:13:32 AM	        {"agent.tab":1, "media_resource.tab":1844, "taxon.tab":1259, "time_elapsed":false}

Partner website seems lacking maintenance:
https://www.treknature.com/viewphotos.php@l=5&p=262614.html
https://www.treknature.com/viewphotos.php@l=5&p=259574.html
https://www.treknature.com/viewphotos.php@l=5&p=251406.html
https://www.treknature.com/viewphotos.php?l=5&p=259117.html
https://www.treknature.com/viewphotos.php?l=5&p=129698.html
https://www.treknature.com/viewphotos.php@l=5&p=129698.html
https://www.treknature.com/viewphotos.php@l=5&p=310596.html
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TrekNatureAPI');

$timestart = time_elapsed();
$resource_id = 895;
$func = new TrekNatureAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\nDone processing.";
?>
