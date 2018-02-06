<?php
namespace php_active_record;
/*connector for multiple resources.
This will use the collections API with the dataObjects API to generate a DwCA file.
First client is the LifeDesk resources e.g. http://eol.org/collections/9528/images?sort_by=1&view_as=3
Eventually all LifeDesks from this ticket will be processed: 
https://eol-jira.bibalex.org/browse/DATA-1569

estimated execution time:
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/CollectionsAPI');
$collection_id = 9528; //for iNat
$func = new CollectionsAPI($collection_id);
$func->generate_link_backs();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\nelapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>