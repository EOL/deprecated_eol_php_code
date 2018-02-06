<?php
namespace php_active_record;
/* connector for multiple resources.
This will use the collections page (scraping) with the dataObjects API to generate a DwCA file.
First client are the LifeDesk resources e.g. http://eol.org/collections/9528/images?sort_by=1&view_as=3
Eventually all LifeDesks from this ticket will be processed: 
https://eol-jira.bibalex.org/browse/DATA-1569

estimated execution time:
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CollectionsScrapeAPI');
$timestart = time_elapsed();

$resource_id = "afrotropicalbirds_multimedia";
$collection_id = 106941; //106941 no taxon for its data_objects; //242; //358; //260; //325; //9528;

$func = new CollectionsScrapeAPI($resource_id, $collection_id);
$func->start();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>