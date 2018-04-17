<?php
namespace php_active_record;
/* Featured Creatures Taxa Outlinks
estimated execution time: 8 seconds 

http://www.eol.org/content_partners/602/resources/648

648	Tuesday 2018-04-17 02:20:38 AM	{"media_resource.tab":774,"taxon.tab":774} eol-archive
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FeaturedCreaturesAPI');

$timestart = time_elapsed();
$resource_id = "648";

$func = new FeaturedCreaturesAPI($resource_id);
$func->get_all_taxa(false); // 'true' if to generate text articles, 'false' for outlinks

Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
exit("\n Done processing.");
?>
