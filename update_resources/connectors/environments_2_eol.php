<?php
namespace php_active_record;
/* DATA-1851: reconstructing the Environments-EOL resource
Next step now is to combine all the steps within a general connector:
1. read any EOL DwCA resource (with text objects)
2. generate individual txt files for the articles with filename convention.
3. run environment_tagger against these text files
4. generate the raw file: eol_tags_noParentTerms.tsv
5. generate the updated DwCA resource, now with Trait data from Environments
    5.1 append in MoF the new environments trait data
    5.2 include the following (if available) from the text object where trait data was derived from. Reflect this in the MoF.
      5.2.1 source - http://purl.org/dc/terms/source
      5.2.2 bibliographicCitation - http://purl.org/dc/terms/bibliographicCitation
      5.2.3 contributor - http://purl.org/dc/terms/contributor
      5.2.4 referenceID - http://eol.org/schema/reference/referenceID

php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags", "resource":"AmphibiaWeb text", "subjects":"Distribution"}'
php update_resources/connectors/environments_2_eol.php _ '{"task": "build_info_tables"}'

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/Environments2EOLAPI');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$task = $param['task'];
$resource = @$param['resource'];
$func = new Environments2EOLAPI($param);

if($task == 'generate_eol_tags') $func->generate_eol_tags($resource);   //step 1
if($task == 'build_info_tables') $func->build_info_tables();            //step 2

// Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>
