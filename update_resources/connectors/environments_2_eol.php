<?php
namespace php_active_record;
/* DATA-1851: reconstructing the Environments-EOL resource
Next step now is to combine all the steps within a general connector:
1. read any EOL DwCA resource (with text objects)
2. generate individual txt files for the articles with filename convention.
3. run environment_tagger against these text files
4. generate the raw file: eol_tags_noParentTerms.tsv
5. add annotations
6. generate the Trait DwCA resource
php update_resources/connectors/environments_2_eol.php _ '{"task": "gen_txt_files_4_articles", "resource":"AmphibiaWeb text"}'

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/Environments2EOLAPI');
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$task = $param['task'];
$resource = @$param['resource'];
$func = new Environments2EOLAPI($param);

if($task == 'gen_txt_files_4_articles') $func->gen_txt_files_4_articles($resource);


// Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>
