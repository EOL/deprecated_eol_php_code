<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. 

wikipedia_combined_languages	    Friday 2020-05-08 06:21:06 AM	{"media_resource.tab":99277, "taxon.tab":25775, "time_elapsed":{"sec":576.91, "min":9.62, "hr":0.16}}
wikipedia_combined_languages_batch2	Friday 2020-05-08 06:22:23 AM	{"media_resource.tab":3033, "taxon.tab":3366, "time_elapsed":{"sec":653.4, "min":10.89, "hr":0.18}}

for monthly report (hak excluded)
wikipedia_combined_languages	    Friday 2020-05-08 09:30:31 AM	{"media_resource.tab":99277, "taxon.tab":25775, "time_elapsed":{"sec":595.56, "min":9.93, "hr":0.17}}
wikipedia_combined_languages_batch2	Friday 2020-05-08 09:31:43 AM	{"media_resource.tab":2815, "taxon.tab":3188, "time_elapsed":{"sec":667.3, "min":11.12, "hr":0.19}}

wget https://editors.eol.org/eol_php_code/applications/content_server/resources/wikipedia_combined_languages_batch2.tar.gz
wget -c https://editors.eol.org/eol_php_code/applications/content_server/resources/wikipedia_combined_languages.tar.gz

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');

for ($x = 1; $x <= 2; $x++) {
    if($x == 1)     $resource_id = 'wikipedia_combined_languages';
    elseif($x == 2) $resource_id = 'wikipedia_combined_languages_batch2';
    $func = new DwCA_Aggregator($resource_id);
    $langs = $func->get_langs();
    echo "\nProcessing [$resource_id] languages:[".count($langs[$x])."]...\n";
    $func->combine_wikipedia_DwCAs($langs[$x]);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>