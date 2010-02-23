<?php

define("ENVIRONMENT", "test");
//define("MYSQL_DEBUG", true);
define("CLI", !isset($_SERVER['HTTP_USER_AGENT']));

require_once("../config/start.php");
require_once(SIMPLE_TEST.'autorun.php');


$test_name = @$_GET["test"];

Functions::time_elapsed();
$group_test = new GroupTest('All tests');

if($test_name)
{
    $test_name = "test_".$test_name;
    require_once($test_name.".php");
    
    $group_test->addTestCase(new $test_name());
}else get_all_tests($group_test);


if(CLI) $group_test->run(new TextReporter());
else $group_test->run(new HtmlReporter());

echo "\nRunning time: ".Functions::time_elapsed()."\n\n";









function get_all_tests(&$group_test)
{
    $dir = LOCAL_ROOT."tests/";
    if($handle = opendir($dir))
    {
       while(false !== ($file = readdir($handle)))
       {
           if(preg_match("/^(test_.*)\.php/", trim($file), $arr))
           {
               $file = $arr[1];
               require_once($dir.$file.".php");

               $group_test->addTestCase(new $file());
           }
       }
       closedir($handle);
    }
}

?>
