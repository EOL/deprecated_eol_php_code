<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
ob_implicit_flush();
@ob_end_flush();

$batch_size = 1000;
$pool_size = 2;

// about 1.7 million on 10.4.10 = minimum of 28 minutes running @ 1 second iterations of 1000
$result = $GLOBALS['db_connection']->query("SELECT max(id) max FROM data_objects");
if($result && $row=$result->fetch_assoc())
{
    $min = 0;
    if($lines = file(DOC_ROOT . 'temp/namelink_last_maximum_object_id.txt'))
    {
        $min = trim($lines[0]);
    }
    $max = $row['max'];
    
    echo "Linking data object IDs $min through $max\n";
    
    // work backwards
    for($i=$max ; $i>$min ; $i-=$batch_size)
    {
        // add up to $pool_size workers to pool. Wait for workers to finish to add more
        $count_processes = Functions::grep_processlist('namelink_data_objects_worker');
        while($count_processes >= $pool_size)
        {
            echo "pool is full - waiting\n";
            // wait 5 seconds before checking pool again
            sleep(2);
            $count_processes = Functions::grep_processlist('namelink_data_objects_worker');
        }
        
        //$script = "namelink_data_objects_worker.php $i ". ($i+$batch_size);
        $script = "namelink_data_objects_worker.php ".($i-$batch_size)." $i";
        echo "$script\n";
        shell_exec(PHP_BIN_PATH . dirname(__FILE__) . "/$script ENV_NAME=".$GLOBALS['ENV_NAME']." > /dev/null 2>/dev/null &");
        // waiting 1 second after each new thread is created
        usleep(1000000);
    }
    
    if(!($FILE = fopen(DOC_ROOT . 'temp/namelink_last_maximum_object_id.txt', 'w+')))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT . 'temp/namelink_last_maximum_object_id.txt');
      return;
    }

    fwrite($FILE, $max);
    fclose($FILE);
}



echo "\n\ndone\n\n";



?>