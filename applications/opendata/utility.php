<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

require_library('OpenData');
$func = new OpenData();

/* this generates this file: [CKAN_uploaded_files.txt]. Done, run once only.
// https://editors.eol.org/eol_php_code/applications/content_server/resources/CKAN_uploaded_files.txt
if(Functions::is_production()) $path = "/extra/ckan_resources/";
else                           $path = "/Volumes/AKiTiO4/web/cp/summary_data_resources/page_ids/";
$func->get_all_ckan_resource_files($path);
*/

/* this generates this file: [CKAN_file_system.txt]. Done, run once also.
// https://editors.eol.org/eol_php_code/applications/content_server/resources/CKAN_file_system.txt
$func->connect_old_file_system_with_new();
*/

/* this will make a copy of the ckan-hidden-filename (the uploaded file) to the "telling" name.
// 64fb7016-eec6-4b20-9320-db5ae0a434c5 https://opendata.eol.org/dataset/6c70b436-5503-431f-8bf3-680fea5e1b05/resource/64fb7016-eec6-4b20-9320-db5ae0a434c5/download/finland.zip    upload  16-eec6-4b20-9320-db5ae0a434c5  /extra/ckan_resources/64f/b70/
// 6575df9b-3a60-4ed1-bea3-3c558a826509 archive.zip upload  9b-3a60-4ed1-bea3-3c558a826509  /extra/ckan_resources/657/5df/
// 659a6499-9a20-473d-b4dd-fcdfb044b1eb https://opendata.eol.org/dataset/c99917cf-7790-4608-a7c2-5532fb47da32/resource/659a6499-9a20-473d-b4dd-fcdfb044b1eb/download/bassstrait.zip upload  99-9a20-473d-b4dd-fcdfb044b1eb  /extra/ckan_resources/659/a64/

require_library('OpenData_utility');
$func = new OpenData_utility();
$func->copy_uploaded_files_to_a_telling_name();
*/

// /* create a text file with resource_id and new_url
require_library('OpenData_utility');
$func = new OpenData_utility();
$func->create_resourceID_newURL_file();
// */

?>
