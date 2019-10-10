<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1551

Before partner provides a TSV file:
estimated execution time: 14 minutes: 40k images | 50 minutes: 72k images

Now partner provides/hosts a DWC-A file. Together with images they also now share text objects as well.
estimated execution time: 55 minutes for:
                                                5Jan'15     28Jan'15
 images:                114,658     114,103     138,879     138,878
 measurementorfact:     201,088     201,088     239,256     106,336
 occurrences                                                26,154
 taxa:                  17,499      17,499      19,627      19,627

201	Tuesday 2018-02-27 09:28:41 AM	{"measurement_or_fact.tab":129220,"media_resource.tab":180170,"occurrence.tab":31424,"taxon.tab":25467}
201	Wednesday 2018-03-21 12:24:43 AM{"measurement_or_fact.tab":130476,"media_resource.tab":181033,"occurrence.tab":31733,"taxon.tab":25494} no measurementID yet
201	Wednesday 2018-03-21 12:48:40 AM{"measurement_or_fact.tab":128349,"media_resource.tab":181033,"occurrence.tab":31733,"taxon.tab":25494} with measurementID and unique at that.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MCZHarvardArchiveAPI');

$timestart = time_elapsed();
$resource_id = 201;
$func = new MCZHarvardArchiveAPI($resource_id);

$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
// $func->get_mediaURL_for_first_40k_images(); //this is a utility
?>