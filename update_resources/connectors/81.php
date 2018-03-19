<?php
namespace php_active_record;
/* connector for BOLDS --- higher-level taxa
estimated execution time: 15,33,30,41 hours for slow connection
                        : 4.8 hours if API requests are already cached
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

/* used generic_services.eol.org for now. So just to bring it to V3. Might need to update connector to get latest data.
81	Sunday 2018-03-18 11:51:40 AM	{"agent.tab":2,"media_resource.tab":126506,"taxon.tab":47943}
*/
return;

require_library('connectors/BoldsAPI');

$folder = DOC_ROOT . "update_resources/connectors/files/BOLD";
if(!file_exists($folder)) mkdir($folder , 0777);

// this will generate the: DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_master_list.txt"
require_library('connectors/BoldsImagesAPIv2');
$func = new BoldsImagesAPIv2();
$func->generate_higher_level_taxa_list();
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done generate_higher_level_taxa_list() \n\n";

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

$resource_id = 81;
$bolds = new BoldsAPI();
$bolds->initialize_text_files();
$bolds->start_process($resource_id, false);

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 1000)
{
    Functions::set_resource_status_to_harvest_requested($resource_id);
    $command_line = "gzip -c " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml >" . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml.gz";
    $output = shell_exec($command_line);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n\n Done processing.";

?>