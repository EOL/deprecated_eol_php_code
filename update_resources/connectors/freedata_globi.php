<?php
namespace php_active_record;
/* Connector for a GloBI resource: https://eol-jira.bibalex.org/browse/DATA-1684

1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreshDataGlobiAPI');
$timestart = time_elapsed();

$params = array("zip_path" => "http://localhost/cp/FreshData/GloBI/Ecological-Database-of-the-World-s-Insect-Pathogens-master.zip",
                // "zip_path" => "https://github.com/millerse/Ecological-Database-of-the-World-s-Insect-Pathogens/archive/master.zip",
                "dataset" => "Ecological-Database-of-the-World-s-Insect-Pathogens",
                "folder" => "GloBI_Ecological-DB-of-the-World-s-Insect-Pathogens");

$func = new FreshDataGlobiAPI($params['folder']);
$func->start($params);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
