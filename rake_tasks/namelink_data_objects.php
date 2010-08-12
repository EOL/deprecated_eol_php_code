<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
ob_implicit_flush();
ob_end_flush();


$result = $GLOBALS['mysqli']->query("SELECT count(*) count FROM data_objects WHERE description!='' AND (visibility_id=".Visibility::find('preview')." OR (visibility_id=".Visibility::find('visible')." AND published=1))");
if($result && $row=$result->fetch_assoc())
{
    $count = $row['count'];
    
    for($i=0 ; $i<$count ; $i+=10000)
    {
        $script = "./tag_data_objects.php $i 10000";
        echo $script."\n";
        
        shell_exec("$script > /dev/null 2>/dev/null &");
        sleep("1");
    }
}

echo "\n\ndone\n\n";

?>