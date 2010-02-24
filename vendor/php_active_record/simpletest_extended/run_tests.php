<?php

$GLOBALS['ENV_NAME'] = 'test';
require_once(__DIR__ . '/../../../config/environment.php');

// bit of a hack to get the environment to try to connect to the database
// and fail if there is an issue. SimpleTest would trap the error otherwise
$GLOBALS['db_connection']->query('select 1');

require_once(DOC_ROOT . 'vendor/simpletest/autorun.php');
require_once(DOC_ROOT . 'vendor/php_active_record/simpletest_extended/simpletest_unit_base.php');
require_once(DOC_ROOT . 'vendor/php_active_record/simpletest_extended/simpletest_web_base.php');

$test_name = @$_GET["test"];
if(!$test_name && @$argv[2]) $test_name = $argv[2];

php_active_record\debug('Starting tests');
$start_time = php_active_record\time_elapsed();

$group_test = new GroupTest('All tests');

// entered a test directory name
if(preg_match("/^([a-z_]+)\/?$/i", $test_name, $arr))
{
    if(!is_dir(DOC_ROOT . "test/simpletest/$arr[1]/"))
    {
        echo "ERROR: directory simpletest/$arr[1]/ does not exist";
        exit;
    }
    get_tests_from_dir(DOC_ROOT . "test/simpletest/$arr[1]/", $group_test, false);
}elseif($test_name)
{
    if(!file_exists(DOC_ROOT . "test/simpletest/$test_name.php"))
    {
        echo "ERROR: test simpletest/$test_name.php does not exist";
        exit;
    }
    require_once(DOC_ROOT . "test/simpletest/$test_name.php");
    
    if(preg_match("/^[^\/]+\/(.*)$/", $test_name, $arr)) $test_name = $arr[1];
    
    $test_name = "php_active_record\\" . php_active_record\to_camel_case($test_name);
    $group_test->addTestCase(new $test_name());
}else
{
    get_tests_from_dir(DOC_ROOT . "test/simpletest/", $group_test, true);
}

if(@$argv[1] == 'cl') $group_test->run(new TextReporter());
else $group_test->run(new HtmlReporter());

$end_time = php_active_record\time_elapsed();

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
               
               $file = "php_active_record\\" . php_active_record\to_camel_case($file);
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
