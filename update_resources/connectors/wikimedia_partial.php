<?php
namespace php_active_record;
/* */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikimediaPartialRes');
$timestart = time_elapsed();

$resource_id = "wikimedia_partial";
$func = new WikimediaPartialRes($resource_id);

$func->generate_partial_wikimedia_resource(); //main operation
/* no need to do this, not a real DwCA resource. Just a temporary step.
Functions::finalize_dwca_resource($resource_id, false);
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
