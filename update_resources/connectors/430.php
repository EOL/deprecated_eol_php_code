<?php
namespace php_active_record;
/* This can be a generic connector for CSV DwCA resources. (Another similar resource is try.php)

Jenkins execution time: 2 days 19 hours

http://www.eol.org/content_partners/441/resources/430
https://eol-jira.bibalex.org/browse/DATA-1707

Partner supplied DwCA has errors:
http://www.inaturalist.org/taxa/eol_media.dwca.zip

This connector will fix that.
Note: The first choice to fix this is: php update_resources/connectors/dwca_utility.php _ 430
But it is running out of memory because the text files are actually CSV files. And dwca_utility.php loads entire extension into memory.

Errors

File: media.csv : Message: Duplicate identifiers
File: media.csv : 
URI: http://rs.tdwg.org/ac/terms/accessURI
Message: Invalid URL

File: media.csv
URI: http://eol.org/schema/media/thumbnailURL
Message: Invalid URL
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CSV2DwCA_Utility');
// ini_set('memory_limit','4096M');
$timestart = time_elapsed();

// $dwca_file = "http://localhost/cp/iNaturalist/eol_media.dwca.zip";
$dwca_file = "http://www.inaturalist.org/taxa/eol_media.dwca.zip";

$resource_id = 430;
$func = new CSV2DwCA_Utility($resource_id, $dwca_file);
$func->convert_archive();
Functions::finalize_dwca_resource($resource_id, true, true); //3rd param is deleteFolderYN ------- 2nd params is true coz it is a big file
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
