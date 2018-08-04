<?php
namespace php_active_record;
/* Articles (will remain un-published)
estimated execution time: 48 minutes */

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
