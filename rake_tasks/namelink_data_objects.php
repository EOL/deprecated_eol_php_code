<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
ob_implicit_flush();
@ob_end_flush();

$batch_size = 1000;
$pool_size = 2;

$result = $GLOBALS['db_connection']->query("SELECT max(id) max FROM data_objects");
if($result && $row=$result->fetch_assoc())
{
    $min = 0;
    $max = $row['max'];
    
    for($i=$min ; $i<=$max ; $i+=$batch_size)
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
        
        $script = "namelink_data_objects_worker.php $i ". ($i+$batch_size);
        echo "$script\n";
        shell_exec(PHP_BIN_PATH . dirname(__FILE__) . "/$script ENV_NAME=".$GLOBALS['ENV_NAME']." > /dev/null 2>/dev/null &");
        // waiting 1 second after each new thread is created
        usleep(1000000);
    }
}

echo "\n\ndone\n\n";



?>