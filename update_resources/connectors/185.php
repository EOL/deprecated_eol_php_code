<?php
namespace php_active_record;
/* Turbellarian Taxonomic Database
estimated execution time: 20 hours

Run the utility (check_taxon_tab) for some diagnostics.

last harvest:
http://eol.org/schema/agent/agent: 4
http://rs.tdwg.org/dwc/terms/taxon: 13227
http://purl.org/dc/dcmitype/Text: 6213
http://purl.org/dc/dcmitype/StillImage: 3423
http://eol.org/schema/reference/reference: 5891

no parent information -- 10408
1413	http://turbellaria.umaine.edu/turb3.php?action=1&code=1413	10409	10408	Itannia ornata murva	Du Bois-Reymond Marcus, 1957	synonym	
1411,1412,1413,10407,10408,10409
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TurbellarianAPI');
$timestart = time_elapsed();
$resource_id = 185;
$func = new TurbellarianAPI($resource_id);
$func->get_all_taxa();
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
}
// $func->check_taxon_tab(DOC_ROOT . "/taxon.tab", false); // utility, just some stats...
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>