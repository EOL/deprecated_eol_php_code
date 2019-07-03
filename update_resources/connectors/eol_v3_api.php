<?php
namespace php_active_record;
/* used for /lib/Eol_v3_API.php */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server

require_library('connectors/Eol_v3_API');

$resource_id = 'eol';
// /* normal operation ============================================
$func = new Eol_v3_API($resource_id);
$func->start(); //normal operation
// finalize_archive($resource_id, true);
// ================================================================*/

/* post DWC-A analysis =========================================
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_unique_ids($resource_id);
$func->check_if_all_parents_have_entries($resource_id);
================================================================*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function finalize_archive($resource_id, $big_file = false)
{
    if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 10) {
        if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id)) {
            recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
            Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        }
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
        Functions::count_resource_tab_files($resource_id);
        if(!$big_file) {
            if($undefined_uris = Functions::get_undefined_uris_from_resource($resource_id)) print_r($undefined_uris);
            echo "\nUndefined URIs: " . count($undefined_uris) . "\n";
            require_library('connectors/DWCADiagnoseAPI');
            $func = new DWCADiagnoseAPI();
            $func->check_unique_ids($resource_id);
        }
    }
}

?>
