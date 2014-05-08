<?php
namespace php_active_record;
/* MycoBank Classification - uses their webservice to get their entire classification of > 400k current name and synonyms
estimated execution time: 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MycoBankAPI');
$timestart = time_elapsed();
$resource_id = 671;
$func = new MycoBankAPI($resource_id);
$func->get_all_taxa();

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function utility_append_text_loop()
{
    echo "\n backing up first...";
    $filename = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump.txt";
    copy($filename, "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump_backup.txt");
    echo "\n backup done. \n";
    for ($x=1; $x <= 1; $x++)
    {
        $str = Functions::format_number_with_leading_zeros($x, "2");
        $filename = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump_add" . $str . ".txt";
        $READ = fopen($filename, "r");
        $contents = fread($READ, filesize($filename));
        fclose($READ);
        echo "\n copying... $filename";
        $filename = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump.txt";
        echo "\n to... $filename\n";
        $WRITE = fopen($filename, "a");
        fwrite($WRITE, $contents);
        fclose($WRITE);
    }
}

?>