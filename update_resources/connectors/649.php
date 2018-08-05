<?php
namespace php_active_record;
/* Articles (will remain un-published)
estimated execution time: 48 minutes
                          11 minutes in eol-archive
649	Saturday 2018-08-04 12:28:22 PM	{"media_resource.tab":4840,"reference.tab":8248,"taxon.tab":763,"vernacular_name.tab":751}  eol-archive
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FeaturedCreaturesAPI');

$timestart = time_elapsed();
$resource_id = "649";
// $GLOBALS['ENV_DEBUG'] = false;
$func = new FeaturedCreaturesAPI($resource_id);
$func->get_all_taxa(true); // 'true' if to generate text articles, 'false' for outlinks

Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
exit("\n Done processing.");
?>
