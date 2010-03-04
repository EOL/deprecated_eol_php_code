<?php

if(defined('E_DEPRECATED')) error_reporting(E_ALL & ~E_DEPRECATED);
else error_reporting(E_ALL);
ini_set("display_errors", 1);

/* forcing the test environment and turning off caching */
$GLOBALS['ENV_NAME'] = 'test';
$GLOBALS['ENV_ENABLE_CACHING'] = false;
require_once(dirname(__FILE__) . '/../config/environment.php');

require_once(DOC_ROOT . 'vendor/simpletest/autorun.php');
require_once(DOC_ROOT . 'classes/modules/simpletest_extended/simpletest_unit_base.php');
require_once(DOC_ROOT . 'classes/modules/simpletest_extended/simpletest_web_base.php');

$test_name = @$_GET["test"];
if(!$test_name && @$argv[1]) $test_name = $argv[1];

debug('Starting tests');
$start_time = time_elapsed();

$group_test = new GroupTest('All tests');

// entered a test directory name
if(preg_match("/^([a-z_]+)\/?$/i", $test_name, $arr))
{
    if(!is_dir(DOC_ROOT . "tests/$arr[1]/"))
    {
        trigger_error("Directory tests/$arr[1]/ does not exist", E_USER_ERROR);
        exit;
    }
    get_tests_from_dir(DOC_ROOT . "tests/$arr[1]/", $group_test, false);
}elseif($test_name)
{
    if(preg_match("/^(.*)\.php/", $test_name, $arr)) $test_name = $arr[1];
    if(!file_exists(DOC_ROOT . "tests/$test_name.php"))
    {
        trigger_error("Test tests/$test_name.php does not exist", E_USER_ERROR);
        exit;
    }
    require_once(DOC_ROOT . "tests/$test_name.php");
    
    if(preg_match("/^[^\/]+\/(.*)$/", $test_name, $arr)) $test_name = $arr[1];
    
    $test_name = $test_name;
    $group_test->addTestCase(new $test_name());
}else
{
    get_tests_from_dir(DOC_ROOT . "tests/", $group_test, true);
}

if(!isset($_SERVER['HTTP_USER_AGENT'])) $group_test->run(new TextReporter());
else $group_test->run(new HtmlReporter());

$end_time = time_elapsed();

echo "Tests ran in ". ($end_time-$start_time) ." seconds\n\n";




function get_tests_from_dir($dir, &$group_test, $recursive)
{
    $files = read_dir($dir);
    foreach($files as $file)
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
}

?>