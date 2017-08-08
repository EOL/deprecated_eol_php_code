<?php
namespace php_active_record;
/* Connector for a GloBI resource: https://eol-jira.bibalex.org/browse/DATA-1684

1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreshDataGlobiAPI');
$timestart = time_elapsed();

//this dataset doesn't have lat lon values
$params = array("dataset" => "Ecological-Database-of-the-World-s-Insect-Pathogens",
                "folder" => "GloBI-Ecological-DB-of-the-World-s-Insect-Pathogens",
                // "zip_path" => "http://localhost/cp/FreshData/GloBI/Ecological-Database-of-the-World-s-Insect-Pathogens-master.zip",
                // "zip_path" => "http://localhost/cp/FreshData/GloBI/resource01/master.zip", //this has similar effect with github download of master.zip
                "zip_path" => "https://github.com/millerse/Ecological-Database-of-the-World-s-Insect-Pathogens/archive/master.zip",
                "zip_folder" => "Ecological-Database-of-the-World-s-Insect-Pathogens-master" //this is the folder where github's master.zip extracts to
                );

// /* this dataset was submitted instead for review:
$params = array("dataset" => "Ant-Plant-Interactions",
                "folder" => "GloBI-Ant-Plant-Interactions",
                "zip_path" => "https://github.com/millerse/Ant-Plant-Interactions/archive/master.zip",
                "zip_folder" => "Ant-Plant-Interactions-master" //this is the folder where github's master.zip extracts to
                );
// */

$func = new FreshDataGlobiAPI($params['folder']);
$func->start($params);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
