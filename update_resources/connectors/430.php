<?php
namespace php_active_record;
/* 
http://www.eol.org/content_partners/441/resources/430
https://eol-jira.bibalex.org/browse/DATA-1707

Partner supplied DwCA has errors:
http://www.inaturalist.org/taxa/eol_media.dwca.zip

This connector will fix that.
Note: The first choice to fix this is: php update_resources/connectors/dwca_utility.php _ 430
But it is running out of memory.

Errors

File: media.csv
Message: Duplicate identifiers

File: media.csv
URI: http://rs.tdwg.org/ac/terms/accessURI
Message: Invalid URL

File: media.csv
URI: http://eol.org/schema/media/thumbnailURL
Message: Invalid URL
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/INaturalistImagesAPI');
// ini_set('memory_limit','4096M'); //314,5728,000
$timestart = time_elapsed();

$dwca_file = "http://www.inaturalist.org/taxa/eol_media.dwca.zip";
$dwca_file = "http://localhost/cp/iNaturalist/eol_media.dwca.zip";

$resource_id = 430;
$func = new INaturalistImagesAPI($resource_id, $dwca_file);
$func->convert_archive();
// $func->start_fix_supplied_archive_by_partner();

// Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>