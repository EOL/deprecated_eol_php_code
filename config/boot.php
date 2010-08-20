<?php

/* Set your working development environment 
   if a web request and there is a paremeter ENV_NAME=$ENV that gets priority
   if a CLI request and there is an argument ENV_NAME=$ENV that gets second priority
   if a constant ENVIRONMENT exists that gets third priority
*/
if(isset($_REQUEST['ENV_NAME'])) $GLOBALS['ENV_NAME'] = $_REQUEST['ENV_NAME'];
elseif(isset($argv) && $match = in_array_regex('ENV_NAME=(.+)', $argv)) $GLOBALS['ENV_NAME'] = $match[1];
elseif(defined('ENVIRONMENT')) $GLOBALS['ENV_NAME'] = ENVIRONMENT;
if(!isset($GLOBALS['ENV_NAME']))
{
    // Environments are currently only used to configure the proper MySQL connection as defined in database.yml
    $GLOBALS['ENV_NAME'] = 'development';
}

/* Override with any settings from /config/environments/ENVIRONMENT.php */
if(file_exists(dirname(__FILE__) . '/environments/' . $GLOBALS['ENV_NAME'] . '.php'))
{
    require_once(dirname(__FILE__) . '/environments/' . $GLOBALS['ENV_NAME'] . '.php');
}

/* requiring PEAR package Horde/Yaml to import *.yml files */
require_once 'Horde/Yaml.php';
require_once 'Horde/Yaml/Loader.php';
require_once 'Horde/Yaml/Node.php';
require_once 'Horde/Yaml/Exception.php';

if(strtolower(substr(php_uname(), 0, 3)) == 'win') define('SYSTEM_OS', 'Windows');
else define('SYSTEM_OS', 'Unix');

/* set the root paths */
$root = preg_replace("/config$/", "", dirname(__FILE__));
define('DOC_ROOT', $root);
define('LOCAL_ROOT', DOC_ROOT);
//if(!defined('WEB_ROOT')) define('WEB_ROOT', 'http://' . @$_SERVER['SERVER_NAME'] . '/');
if(!defined('WEB_ROOT')) define('WEB_ROOT', 'http://' . gethostbyname(gethostname()) . '/');
define('LOCAL_WEB_ROOT', WEB_ROOT);

if(!defined('PHP_BIN_PATH')) define('PHP_BIN_PATH', PHP_BINDIR . '/php ');
if(!defined('MYSQL_BIN_PATH')) define('MYSQL_BIN_PATH', 'mysql ');



require_once(DOC_ROOT . 'lib/MysqlBase.php');
require_all_classes_recursively(DOC_ROOT . 'vendor/php_active_record/classes/');

if(defined('USING_SPM') && USING_SPM)
{
    require_once(DOC_ROOT . "vendor/rdfapi-php/api/RdfAPI.php");
    require_once(DOC_ROOT . "lib/Functions.php");
    require_once(DOC_ROOT . "vendor/rdf/RDFDocument.php");
    require_once(DOC_ROOT . "vendor/rdf/RDFDocumentElement.php");
}else require_all_classes_recursively(DOC_ROOT . 'app/models/');

set_exception_handler(array('ActiveRecordError', 'handleException'));
set_error_handler(array('ActiveRecordError', 'handleError'));

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
    register_shutdown_function('shutdown_check');
}

if((@$GLOBALS['ENV_DEBUG'] || @$GLOBALS['ENV_MYSQL_DEBUG']) && @$GLOBALS['ENV_DEBUG_TO_FILE'])
{
    $open_state = 'a+';
    if($GLOBALS['ENV_DEBUG_FILE_FLUSH']) $open_state = 'w+';
    $GLOBALS['ENV_DEBUG_FILE_HANDLE'] = fopen($GLOBALS['ENV_DEBUG_FILE'], $open_state);
}

/* Auto flush echo statements to the screen when debugging */
if(@$GLOBALS['ENV_MYSQL_DEBUG'] || @$GLOBALS['ENV_DEBUG']) ob_implicit_flush(true);


/* Caching is turned off by default */
if(!isset($GLOBALS['ENV_ENABLE_CACHING'])) $GLOBALS['ENV_ENABLE_CACHING'] = false;

/* will try to connect to memcached, or default to using memory */
Cache::restart();


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
define('DOWNLOAD_TIMEOUT_SECONDS', '10');


/* USING US EST as default timezone */
if(!isset($GLOBALS['DEFAULT_TIMEZONE'])) $GLOBALS['DEFAULT_TIMEZONE'] = 'America/New_York';
date_default_timezone_set($GLOBALS['DEFAULT_TIMEZONE']);
setlocale(LC_ALL, 'en_US.utf8');

// sets a static start time to base later comparisons on
time_elapsed();

/* defining some functions which are needed by the boot loader */

function load_mysql_environment($environment = NULL)
{
    if(!file_exists(DOC_ROOT . 'config/database.yml'))
    {
        trigger_error('Booting failure: /config/database.yml does\'t exit', E_USER_ERROR);
        return false;
    }
    $environments = Horde_Yaml::loadFile(DOC_ROOT . 'config/database.yml');
    
    $possible_environments = array_keys($environments);
    if(!in_array($environment, $possible_environments))
    {
        trigger_error('Booting failure: environment `'. $environment .'` doesn\'t exit', E_USER_ERROR);
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
    
    return new MysqliConnection($MYSQL_SERVER, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DATABASE, $MYSQL_ENCODING, $MYSQL_PORT, $MYSQL_SOCKET, $MASTER_MYSQL_SERVER, $MASTER_MYSQL_USER, $MASTER_MYSQL_PASSWORD, $MASTER_MYSQL_DATABASE, $MASTER_MYSQL_ENCODING, $MASTER_MYSQL_PORT, $MASTER_MYSQL_SOCKET);
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

/* finds the first instance of $needle in $haystack and returns the resulting match array */
function in_array_regex($needle, $haystack)
{
    if(!is_array($haystack)) return false;
    foreach($haystack as $element)
    {
        if(preg_match('/^'. str_replace('/', '\/', $needle) .'$/', $element, $arr)) return $arr;
    }
    return false;
}

?>