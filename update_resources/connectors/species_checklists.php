<?php
namespace php_active_record;
/* DATA-1817 */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* //main operation
require_library('connectors/DwCA_Utility');

$dwca_file = 'http://localhost/cp/DATA-1817/indianocean.zip';
$dwca_file = 'https://opendata.eol.org/dataset/c99917cf-7790-4608-a7c2-5532fb47da32/resource/f6f7145c-bc58-4182-ac23-e5a80cf0edcc/download/indianocean.zip';
$resource_id = 'SC_'.get_basename($dwca_file);
$func = new DwCA_Utility($resource_id, $dwca_file);

/* No preferred. Will get all.
$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/reference/reference');
*/

$func->convert_archive();
Functions::finalize_dwca_resource($resource_id);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function get_basename($url)
{
    return pathinfo($url, PATHINFO_FILENAME);
}
?>
