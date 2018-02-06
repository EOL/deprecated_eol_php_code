<?php
namespace php_active_record;
/*connector for multiple resources.
This will use the collections API with the dataObjects API to generate a DwCA file.
First client is the LifeDesk resources e.g. http://eol.org/collections/9528/images?sort_by=1&view_as=3
Eventually all LifeDesks from this ticket will be processed: 
https://eol-jira.bibalex.org/browse/DATA-1569

estimated execution time:
*/


Just FYI.
The parameter 'page' in Collections API is not working.
Attention: Jeremy Rice. page=1 and page=2 is giving the same results:
page 1: https://eol.org/api/collections/1.0?id=9528&page=1&per_page=50&filter=&sort_by=recently_added&sort_field=&cache_ttl=&language=en&format=json
page 2: https://eol.org/api/collections/1.0?id=9528&page=2&per_page=50&filter=&sort_by=recently_added&sort_field=&cache_ttl=&language=en&format=json

But I can scrape the HTML as second option. Which I think I did on another resource because of the same problem.
http://eol.org/collections/9528/images?page=1&sort_by=1&view_as=3
http://eol.org/collections/9528/images?page=2&sort_by=1&view_as=3

Will proceed with 2nd option for now.


include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/CollectionsAPI');
$collection_id = 9528; //for Afrotropical birds LifeDesk
$func = new CollectionsAPI($collection_id);
$func->generate_link_backs();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\nelapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>