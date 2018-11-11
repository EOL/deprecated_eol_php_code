<?php

/* Spyc is our YAML parser */
require_once dirname(__FILE__) . '/../vendor/spyc/spyc.php';

if(strtolower(substr(php_uname(), 0, 3)) == 'win') define('SYSTEM_OS', 'Windows');
else define('SYSTEM_OS', 'Unix');

/* USING US EST as default timezone */
if(!isset($GLOBALS['DEFAULT_TIMEZONE'])) $GLOBALS['DEFAULT_TIMEZONE'] = 'America/New_York';
date_default_timezone_set($GLOBALS['DEFAULT_TIMEZONE']);
setlocale(LC_ALL, 'en_US.utf8');

/* set the root paths */
$root = preg_replace("/config$/", "", dirname(__FILE__));

echo "\norig: [$root]\n";
if(defined('CACHE_PATH')) echo "\nCACHE_PATH yy 01: ".CACHE_PATH."\n";

$ret = prepare_jenkins($argv, $root);
$root = $ret[0];
if(!defined('CACHE_PATH')) define('CACHE_PATH', $ret[1]);
echo "\nnew: [$root]\n";
if(defined('CACHE_PATH')) echo "\nCACHE_PATH yy 02: ".CACHE_PATH."\n";

define('DOC_ROOT', $root);
define('LOCAL_ROOT', DOC_ROOT);
//if(!defined('WEB_ROOT')) define('WEB_ROOT', 'http://' . @$_SERVER['SERVER_NAME'] . '/');
if(!defined('WEB_ROOT')) define('WEB_ROOT', 'http://' . gethostbyname(gethostname()) . '/');
define('LOCAL_WEB_ROOT', WEB_ROOT);

if(!defined('PHP_BIN_PATH')) define('PHP_BIN_PATH', PHP_BINDIR . '/php ');
if(!defined('MYSQL_BIN_PATH')) define('MYSQL_BIN_PATH', 'mysql ');



require_all_classes_recursively(DOC_ROOT . 'vendor/php_active_record/classes/');

if(defined('USING_SPM') && USING_SPM)
{
    require_once(DOC_ROOT . "vendor/rdfapi-php/api/RdfAPI.php");
    require_once(DOC_ROOT . "lib/Functions.php");
    require_once(DOC_ROOT . "vendor/rdf/RDFDocument.php");
    require_once(DOC_ROOT . "vendor/rdf/RDFDocumentElement.php");
}else require_all_classes_recursively(DOC_ROOT . 'app/models/');

set_exception_handler(array('php_active_record\ActiveRecordError', 'handleException'));
set_error_handler(array('php_active_record\ActiveRecordError', 'handleError'));

/* Should really always be set to true */
if(!isset($GLOBALS['ENV_USE_MYSQL'])) $GLOBALS['ENV_USE_MYSQL'] = true;

/* Set to true if debugging */
if(!isset($GLOBALS['ENV_MYSQL_DEBUG']))         $GLOBALS['ENV_MYSQL_DEBUG'] = false;
if(!isset($GLOBALS['ENV_DEBUG']))               $GLOBALS['ENV_DEBUG'] = false;
if(!isset($GLOBALS['ENV_DEBUG_TO_FILE']))       $GLOBALS['ENV_DEBUG_TO_FILE'] = false;
if(!isset($GLOBALS['ENV_DEBUG_FILE']))          $GLOBALS['ENV_DEBUG_FILE'] = DOC_ROOT . 'log/' . $GLOBALS['ENV_NAME'] . '.log';
if(!isset($GLOBALS['ENV_DEBUG_FILE_FLUSH']))    $GLOBALS['ENV_DEBUG_FILE_FLUSH'] = true;
if(!isset($GLOBALS['ENV_MYSQL_READ_ONLY']))     $GLOBALS['ENV_MYSQL_READ_ONLY'] = false;
if(!isset($GLOBALS['ENV_MYSQL_ONLY_MASTER']))   $GLOBALS['ENV_MYSQL_ONLY_MASTER'] = false;

if(@$GLOBALS['ENV_USE_MYSQL'])
{
    /* comment these 3 lines out if you are not using MySQL */
    $GLOBALS['db_connection'] = load_mysql_environment($GLOBALS['ENV_NAME']);
    $GLOBALS['mysqli_connection'] = $GLOBALS['db_connection'];
    register_shutdown_function('php_active_record\shutdown_check');
}

if((@$GLOBALS['ENV_DEBUG'] || @$GLOBALS['ENV_MYSQL_DEBUG']) && @$GLOBALS['ENV_DEBUG_TO_FILE'])
{
    $open_state = 'a+';
    if($GLOBALS['ENV_DEBUG_FILE_FLUSH']) $open_state = 'w+';
    if(!($GLOBALS['ENV_DEBUG_FILE_HANDLE'] = fopen($GLOBALS['ENV_DEBUG_FILE'], $open_state)))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$GLOBALS['ENV_DEBUG_FILE']);
    }
}

/* Auto flush echo statements to the screen when debugging */
if(@$GLOBALS['ENV_MYSQL_DEBUG'] || @$GLOBALS['ENV_DEBUG']) ob_implicit_flush(true);


/* Caching is turned off by default */
if(!isset($GLOBALS['ENV_ENABLE_CACHING'])) $GLOBALS['ENV_ENABLE_CACHING'] = false;

/* will try to connect to memcached, or default to using memory */
php_active_record\Cache::restart();


/* ImageMagick */
if(defined('MAGICK_HOME'))
{
    /* setting the ImageMagick path */
    putenv('MAGICK_HOME='. MAGICK_HOME);
    putenv('PATH='. MAGICK_HOME .'/bin/:'.getenv('PATH'));
    putenv('DYLD_LIBRARY_PATH='. MAGICK_HOME .'/lib');
}


/* unicode characters for regular expressions */
define('UPPER','A-ZÀÂÅÅÃÄÁÆČÇÉÈÊËÍÌÎÏÑÓÒÔØÕÖÚÙÛÜßĶŘŠŞŽŒ');
define('LOWER','a-záááàâåãäăæčćçéèêëĕíìîïǐĭñńóòôøõöŏúùûüůśšşřğžźýýÿœœ');


/* file downloads should be throttled by adding delays */
if(!defined('DOWNLOAD_WAIT_TIME')) define('DOWNLOAD_WAIT_TIME', '300000'); //.3 seconds
define('DOWNLOAD_ATTEMPTS', '2');
if(!defined('DOWNLOAD_TIMEOUT_SECONDS')) define('DOWNLOAD_TIMEOUT_SECONDS', '30');

// sets a static start time to base later comparisons on
php_active_record\time_elapsed();

/* defining some functions which are needed by the boot loader */

function environment_defined($environment_name)
{
    if(!file_exists(DOC_ROOT . 'config/database.yml'))
    {
        // trigger_error('Booting failure: /config/database.yml doesn\'t exist', E_USER_ERROR);
        return false;
    }
    $environments = Spyc::YAMLLoad(DOC_ROOT . 'config/database.yml');
    
    $possible_environments = array_keys($environments);
    if(in_array($environment_name, $possible_environments))
    {
        return true;
    }
    
    // trigger_error('Booting failure: environment `'. $environment .'` doesn\'t exist', E_USER_ERROR);
    return false;
}

function prepare_jenkins($argv, $root)
{
    print_r($argv);
    if($jenkins_or_cron = @$argv[1]) {
        echo "\ngoes here 01\n";
        if($jenkins_or_cron == "jenkins") {
            echo "\ngoes here 02\n";
            if($root != "/Library/WebServer/Documents/eol_php_code/") { //means Jenkins in eol-archive is running
                echo "\ngoes here 03\n";
                $GLOBALS['ENV_NAME'] = 'jenkins_production';
                $cache_path = '/html/cache_LiteratureEditor/';  //for archive
                $root = '/html/eol_php_code/';
            }
            else { //means Jenkins in Mac mini is running
                $GLOBALS['ENV_NAME'] = 'jenkins_development';
                $cache_path = '/Volumes/MacMini_HD2/cache_LiteratureEditor/';   //for mac mini
            }
        }
        else { //means NOT Jenkins
            if($root != "/Library/WebServer/Documents/eol_php_code/") $cache_path = '/var/www/html/cache_LiteratureEditor/';        //for archive
            else                                                      $cache_path = '/Volumes/MacMini_HD2/cache_LiteratureEditor/'; //for mac mini
        }
    }
    else echo "\ngoes here 04\n";
    return array($root, $cache_path);
}

/*
function prepare_jenkins($argv, $root)
{
    if($jenkins_or_cron = @$argv[1]) {
        if($jenkins_or_cron == "jenkins") {
            if($root != "/Library/WebServer/Documents/eol_php_code/") { //means Jenkins in eol-archive is running
                $GLOBALS['ENV_NAME'] = 'jenkins_production';
                define('CACHE_PATH', '/html/cache_LiteratureEditor/');  //for archive
                return '/html/eol_php_code/';
            }
            else { //means Jenkins in Mac mini is running
                $GLOBALS['ENV_NAME'] = 'jenkins_development';
                define('CACHE_PATH', '/Volumes/MacMini_HD2/cache_LiteratureEditor/');   //for mac mini
            }
        }
        else { //means NOT Jenkins
            if($root != "/Library/WebServer/Documents/eol_php_code/") define('CACHE_PATH', '/var/www/html/cache_LiteratureEditor/');        //for archive
            else                                                      define('CACHE_PATH', '/Volumes/MacMini_HD2/cache_LiteratureEditor/'); //for mac mini
        }
    }
    return $root;
}
*/

function load_mysql_environment($environment = NULL)
{
    if(!file_exists(DOC_ROOT . 'config/database.yml'))
    {
        trigger_error('Booting failure: /config/database.yml doesn\'t exist', E_USER_ERROR);
        return false;
    }
    $environments = Spyc::YAMLLoad(DOC_ROOT . 'config/database.yml');
    
    $possible_environments = array_keys($environments);
    if(!in_array($environment, $possible_environments))
    {
        trigger_error('Booting failure: environment `'. $environment .'` doesn\'t exist', E_USER_ERROR);
        return false;
    }
    
    $MYSQL_SERVER           = $environments[$environment]['host'];
    $MYSQL_USER             = $environments[$environment]['username'];
    $MYSQL_PASSWORD         = $environments[$environment]['password'];
    $MYSQL_DATABASE         = $environments[$environment]['database'];
    $MYSQL_ENCODING         = $environments[$environment]['encoding'];
    $MYSQL_PORT             = @$environments[$environment]['port']; 
    $MYSQL_SOCKET           = @$environments[$environment]['socket'];
    $MASTER_MYSQL_SERVER    = @$environments[$environment]['master_host'];
    $MASTER_MYSQL_USER      = @$environments[$environment]['master_username'];
    $MASTER_MYSQL_PASSWORD  = @$environments[$environment]['master_password'];
    $MASTER_MYSQL_DATABASE  = @$environments[$environment]['master_database'];
    $MASTER_MYSQL_ENCODING  = @$environments[$environment]['master_encoding'];
    $MASTER_MYSQL_PORT      = @$environments[$environment]['master_port'];
    $MASTER_MYSQL_SOCKET    = @$environments[$environment]['master_socket'];
    
    // unsets all master information which will cause writes to fail
    if(@$GLOBALS['ENV_MYSQL_READ_ONLY'])
    {
        $MASTER_MYSQL_SERVER    = '';
        $MASTER_MYSQL_USER      = '';
        $MASTER_MYSQL_PASSWORD  = '';
        $MASTER_MYSQL_DATABASE  = '';
        $MASTER_MYSQL_ENCODING  = '';
        $MASTER_MYSQL_PORT      = '';
        $MASTER_MYSQL_SOCKET    = '';
    }
    
    if(@$GLOBALS['ENV_MYSQL_ONLY_MASTER'] && @!$GLOBALS['ENV_MYSQL_READ_ONLY'])
    {
        $MYSQL_SERVER   = $MASTER_MYSQL_SERVER;
        $MYSQL_USER     = $MASTER_MYSQL_USER;
        $MYSQL_PASSWORD = $MASTER_MYSQL_PASSWORD;
        $MYSQL_DATABASE = $MASTER_MYSQL_DATABASE;
        $MYSQL_ENCODING = $MASTER_MYSQL_ENCODING;
        $MYSQL_PORT     = $MASTER_MYSQL_PORT;
        $MYSQL_SOCKET   = $MASTER_MYSQL_SOCKET;
    }
    
    return new php_active_record\MysqliConnection($MYSQL_SERVER, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DATABASE, $MYSQL_ENCODING, $MYSQL_PORT, $MYSQL_SOCKET, $MASTER_MYSQL_SERVER, $MASTER_MYSQL_USER, $MASTER_MYSQL_PASSWORD, $MASTER_MYSQL_DATABASE, $MASTER_MYSQL_ENCODING, $MASTER_MYSQL_PORT, $MASTER_MYSQL_SOCKET);
}

function require_all_classes_recursively($dir)
{
    if($handle = opendir($dir))
    {
       while(false !== ($file = readdir($handle)))
       {
           if($file != '.' && $file != '..')
           {
               if(preg_match("/\.php$/",trim($file))) require_once($dir.$file);
               elseif(!preg_match("/\./", $file)) require_all_classes_recursively($dir.$file.'/');
           }
       }
       closedir($handle);
    }
}

?>
