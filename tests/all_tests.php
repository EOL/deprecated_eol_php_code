<?php

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set("display_errors", 1);

define("ENVIRONMENT", "test");
//define("MYSQL_DEBUG", true);
define("CLI", !isset($_SERVER['HTTP_USER_AGENT']));

require_once("../config/start.php");

require_once(LOCAL_ROOT . '/classes/modules/simpletest/autorun.php');
require_once(LOCAL_ROOT . '/classes/modules/simpletest_extended/simpletest_unit_base.php');
require_once(LOCAL_ROOT . '/classes/modules/simpletest_extended/simpletest_web_base.php');

$test_name = @$_GET["test"];
if(!$test_name && @$argv[1]) $test_name = $argv[1];

Functions::debug('Starting tests');
$start_time = Functions::time_elapsed();

$group_test = new GroupTest('All tests');

// entered a test directory name
if(preg_match("/^([a-z_]+)\/?$/i", $test_name, $arr))
{
    if(!is_dir(LOCAL_ROOT . "tests/$arr[1]/"))
    {
        echo "ERROR: directory tests/$arr[1]/ does not exist";
        exit;
    }
    get_tests_from_dir(LOCAL_ROOT . "tests/$arr[1]/", $group_test, false);
}elseif($test_name)
{
    if(!file_exists(LOCAL_ROOT . "tests/$test_name.php"))
    {
        echo "ERROR: test tests/$test_name.php does not exist";
        exit;
    }
    require_once(LOCAL_ROOT . "tests/$test_name.php");
    
    if(preg_match("/^[^\/]+\/(.*)$/", $test_name, $arr)) $test_name = $arr[1];
    
    $test_name = $test_name;
    $group_test->addTestCase(new $test_name());
}else
{
    get_tests_from_dir(LOCAL_ROOT . "tests/", $group_test, true);
}

if(CLI) $group_test->run(new TextReporter());
else $group_test->run(new HtmlReporter());

$end_time = Functions::time_elapsed();

echo "Tests ran in ". ($end_time-$start_time) ." seconds\n\n";




function get_tests_from_dir($dir, &$group_test, $recursive)
{
    if($handle = opendir($dir))
    {
       while(false !== ($file = readdir($handle)))
       {
           if(!$recursive && preg_match("/^(test_.*)\.php/", $file, $arr))
           {
               $file = $arr[1];
               require_once($dir . $file.".php");
               
               $file = $file;
               $group_test->addTestCase(new $file());
           }elseif($recursive && is_dir($dir .'/'. $file) && !preg_match("/^\./", $file))
           {
               get_tests_from_dir($dir .'/'. $file .'/', $group_test, false);
           }
       }
       closedir($handle);
    }
}

?>