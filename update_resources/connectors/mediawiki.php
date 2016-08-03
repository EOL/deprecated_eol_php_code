<?php
namespace php_active_record;
/* 
Hook up a MediaWiki instance to BHL APIs for text & metadata editing #148
https://github.com/EOL/eol_php_code/issues/148
*/

$domain = "editors.eol.localhost";
$domain = "editors.eol.org";

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiLiteratureEditorAPI');
$timestart = time_elapsed();

$params =& $_GET;
if(!$params) $params =& $_POST;

if(isset($params['wiki_title']))
{
    $wiki_title = $params['wiki_title'];
    $resource_id = str_replace(array(":"," "),"_",$wiki_title);
    $func = new WikiLiteratureEditorAPI($resource_id, 'http://' . $domain . '/LiteratureEditor/api.php');
    $func->process_title($wiki_title);
    $func->archive_builder->finalize(true);
    finalize_dwca_resource($resource_id);
    if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/taxon.tab") > 0) echo "<br>[SUCCESS]<br>";
    else echo "<br>[FAIL]<br>";
}
else
{
    $resource_id = 1006;
    if($val = @$params['archive_id']) $resource_id = $val;
    $func = new WikiLiteratureEditorAPI($resource_id, 'http://' . $domain . '/LiteratureEditor/api.php');
    $func->generate_archive();
    finalize_dwca_resource($resource_id);
    if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/taxon.tab") > 0) echo "<br>[SUCCESS]<br>";
    else echo "<br>[FAIL]<br>";
    
    $elapsed_time_sec = time_elapsed() - $timestart;
    echo "\n\n";
    echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
    echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
    echo "\nDone processing.\n";
}

function finalize_dwca_resource($resource_id, $big_file = false)
{
    if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 0)
    {
        if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
        {
            recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
            Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        }
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
        /* works but not needed
        echo "<pre>";
        Functions::count_resource_tab_files($resource_id);
        echo "</pre>";
        */
    }
}

?>
