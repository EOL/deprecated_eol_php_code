<?php
namespace php_active_record;
/* Animal Demography unit (ADU) Virtual Museum - MammalMAP (31 minutes execution time)
estimated execution time for 12 databases: 59 hours if records_per_page = 50.

*We are not yet getting any database aside from 'vimma'. Same case in V2. No instruction in V3.

716	Friday 2018-08-03 04:15:27 AM	{"agent.tab":932,"media_resource.tab":19245,"taxon.tab":689,"vernacular_name.tab":676}  eol-archive
716	Monday 2019-02-18 05:36:33 AM	{"agent.tab":915,"media_resource.tab":18896,"taxon.tab":683,"vernacular_name.tab":673}  MacMini
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ADUVirtualMuseumAPI');
$timestart = time_elapsed();
$resource_id = 716;
$func = new ADUVirtualMuseumAPI($resource_id, "vimma"); //worked well as of July 11, 2018
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

/*
 182 of 236 [birdpix]
Grabbing http://vmus.adu.org.za//vm_view_db.php?database=birdpix&Records_per_page=20&start=3620&query_id=29738
 investigate 01 process_html() failed. 
*/

?>