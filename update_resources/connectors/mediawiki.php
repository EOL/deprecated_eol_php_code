<?php
namespace php_active_record;
/* 
Hook up a MediaWiki instance to BHL APIs for text & metadata editing #148
https://github.com/EOL/eol_php_code/issues/148
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiLiteratureEditorAPI');

$timestart = time_elapsed();
$resource_id = 1;
$func = new WikiLiteratureEditorAPI($resource_id, 'http://editors.eol.localhost/LiteratureEditor/api.php');
$func->generate_archive();
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
