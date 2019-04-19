<?php
namespace php_active_record;
/* DATA-1618 internationalize wikipedia! Test case: German Wikipedia
                    2015Jun2    Aug18   Aug21
media_resource.tab  54605       55242   55280
taxon.tab           27300       27618   27637
*/

exit("\nThis is no longer being used. Replaced by wikipedia.php for sometime now.\n");

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikipediaRegionalAPI');
$timestart = time_elapsed();
$resource_id = 957;
$func = new WikipediaRegionalAPI($resource_id, 'de');

// $resource_id = 1;
// $func = new WikipediaRegionalAPI($resource_id, 'es');

// $resource_id = 173;
// $func = new WikipediaRegionalAPI($resource_id, 'fr');


$func->generate_archive();
Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
