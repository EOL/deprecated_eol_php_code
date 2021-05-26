<?php
namespace php_active_record;
/* */

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");

require_library('connectors/ContributorsMapAPI');

/* working OK
$func = new ContributorsMapAPI('21_ENV');
$map = $func->get_contributor_mappings();
print_r($map);
*/

// /* get FishBase collaborators list
$func = new ContributorsMapAPI('21_ENV');
$map = $func->get_collab_name_and_ID_from_FishBase();
// print_r($map);

// */

exit("\n-end-\n");
?>
