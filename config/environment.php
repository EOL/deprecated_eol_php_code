<?php

/* NOTE - prefer using constants (like FOO and BAR) for values that you don't want to change during a run. Use
 * $GLOBALS if you may want to set the values, then override in certain environments. */
if(!isset($GLOBALS['DEFAULT_TIMEZONE'])) $GLOBALS['DEFAULT_TIMEZONE'] = 'America/New_York';
date_default_timezone_set($GLOBALS['DEFAULT_TIMEZONE']);  // Required by resque...

/* best to leave the PHP settings at the top in case they are overridden in another environment */
ini_set('memory_limit', '1024M'); // 1GB maximum memory usage
ini_set('max_execution_time', 60*60*24*15); // 15 days | 21600 = 6 hours
ini_set('display_errors', false);

/* Default Environment */
if(!isset($GLOBALS['ENV_NAME'])) $GLOBALS['ENV_NAME'] = 'production';
// passing in the CLI arguments
set_and_load_proper_environment($argv);


if(!defined('PS_LITE_CMD')) define('PS_LITE_CMD', 'ps -eo uid,pid,ppid,stime,tty,time,command'); // No -f
// if(!defined('WEB_ROOT')) define('WEB_ROOT', 'http://localhost/eol_php_code/');  // URL prefix of this installation
if(!defined('WEB_ROOT')) define('WEB_ROOT', 'https://editors.eol.org/eol_php_code/');  // URL prefix of this installation

if(!defined('MYSQL_BIN_PATH')) define('MYSQL_BIN_PATH', 'mysql ');              // path to mysql binary. THE SPACE AT THE END IS IMPORTANT
if(!defined('CONVERT_BIN_PATH')) define('CONVERT_BIN_PATH', 'convert');        // path to imagemagick convert binary
if(!defined('GUNZIP_BIN_PATH')) define('GUNZIP_BIN_PATH', 'gunzip');
if(!defined('UNZIP_BIN_PATH')) define('UNZIP_BIN_PATH', 'unzip');
if(!defined('TAR_BIN_PATH')) define('TAR_BIN_PATH', 'tar');
if(!defined('FILE_BIN_PATH')) define('FILE_BIN_PATH', 'file');
if(!defined('RESQUE_HOST')) define('RESQUE_HOST', 'localhost:6379');

if(!isset($GLOBALS['ENV_DEBUG'])) $GLOBALS['ENV_DEBUG'] = true;
if(!isset($GLOBALS['ENV_MYSQL_DEBUG'])) $GLOBALS['ENV_MYSQL_DEBUG'] = true;
if(!isset($GLOBALS['ENV_DEBUG_TO_FILE'])) $GLOBALS['ENV_DEBUG_TO_FILE'] = false;
if(!isset($GLOBALS['ENV_DEBUG_FILE_FLUSH'])) $GLOBALS['ENV_DEBUG_FILE_FLUSH'] = false;

if(!isset($GLOBALS['ENV_ENABLE_CACHING'])) $GLOBALS['ENV_ENABLE_CACHING'] = true;

if(!defined('SPARQL_ENDPOINT')) define("SPARQL_ENDPOINT", "http://localhost:8890/sparql");
if(!defined('SPARQL_UPLOAD_ENDPOINT')) define("SPARQL_UPLOAD_ENDPOINT", "http://localhost:8890/DAV/home/dba/upload");
if(!defined('SPARQL_USERNAME')) define("SPARQL_USERNAME", "dba");
if(!defined('SPARQL_PASSWORD')) define("SPARQL_PASSWORD", "dba");

/* Initialize app - this should be towards the top of environment.php,
   but declare the WEB_ROOT and caching settings first.
   This will load values from ./environments/ENV_NAME.php before values below
*/
require_once(dirname(__FILE__) . '/boot.php');

// $GLOBALS['log_file'] = fopen(DOC_ROOT . "temp/processes.log", "a+");



/* the 'default' hierarchy - the one which gets matched to new taxa first */
if(!defined('DEFAULT_HIERARCHY_LABEL')) define('DEFAULT_HIERARCHY_LABEL', 'Species 2000 & ITIS Catalogue of Life: Annual Checklist 2010');

/* default application language */
if(!defined('DEFAULT_LANGUAGE_ISO_CODE')) define('DEFAULT_LANGUAGE_ISO_CODE', 'en');
if(!defined('DEFAULT_LANGUAGE_LABEL')) define('DEFAULT_LANGUAGE_LABEL', 'English');




/* Modules needed */
php_active_record\require_library('Functions');
php_active_record\require_library('ContentManager');
php_active_record\require_library('SSH2Connection');
php_active_record\require_library('SchemaConnection');
php_active_record\require_library('SchemaParser');
php_active_record\require_library('SchemaValidator');
php_active_record\require_library('CompareHierarchies');
php_active_record\require_library('ControllerBase');
php_active_record\require_library('NamesFunctions');
php_active_record\require_library('Tasks');
php_active_record\require_library('FileIterator');
php_active_record\require_library('MysqliResultIterator');
php_active_record\require_library('MysqliResultFileIterator');
php_active_record\require_library('ArchiveDataIngester');
php_active_record\require_library('ContentArchiveValidator');
php_active_record\require_library('RelateHierarchies');
php_active_record\require_library('FlattenHierarchies');
php_active_record\require_library('SparqlClient');
php_active_record\require_vendor('eol_content_schema');
php_active_record\require_vendor('solr');
php_active_record\require_vendor('darwincore');
php_active_record\require_vendor('eol_content_schema_v2');


/* For content downloading */
# where harvested media will be downloaded to (must be web accessible)
if(!defined('CONTENT_LOCAL_PATH'))          define('CONTENT_LOCAL_PATH',            DOC_ROOT . 'applications/content_server/content/');
# where harvested media will be temporarily stored before being moved the above directory
if(!defined('CONTENT_TEMP_PREFIX'))         define('CONTENT_TEMP_PREFIX',           DOC_ROOT . 'applications/content_server/tmp/');
# where resource XML files will be downloaded to
if(!defined('CONTENT_RESOURCE_LOCAL_PATH')) define('CONTENT_RESOURCE_LOCAL_PATH',   DOC_ROOT . 'applications/content_server/resources/');
if(!defined('CONTENT_GNI_RESOURCE_PATH'))   define('CONTENT_GNI_RESOURCE_PATH',     DOC_ROOT . 'applications/content_server/gni_tcs_files/');
# where datasets prepared by app servers will reside (must be web accessible)
if(!defined('CONTENT_DATASET_PATH'))        define('CONTENT_DATASET_PATH',          DOC_ROOT . 'applications/content_server/datasets/');

if(!isset($GLOBALS['MAIN_CACHE_PATH'])) $GLOBALS['MAIN_CACHE_PATH'] = 'tmp/cache/';

// this may not be needed anymore
if(!defined('WEB_ROOT')) define('MAGICK_HOME', '/usr/local/ImageMagick/');       // path to ImageMagick home directory

/* table data which will not get cached - there are too many rows */
$GLOBALS['no_cache']['agents']              = true;
$GLOBALS['no_cache']['canonical_forms']     = true;
$GLOBALS['no_cache']['content_partners']    = true;
$GLOBALS['no_cache']['data_objects']        = true;
$GLOBALS['no_cache']['harvest_events']      = true;
$GLOBALS['no_cache']['harvest_process_logs'] = true;
$GLOBALS['no_cache']['hierarchies']         = true;
$GLOBALS['no_cache']['hierarchy_entries']   = true;
$GLOBALS['no_cache']['name_languages']      = true;
$GLOBALS['no_cache']['names']               = true;
$GLOBALS['no_cache']['resources']           = true;
$GLOBALS['no_cache']['synonyms']            = true;
$GLOBALS['no_cache']['taxa']                = true;
$GLOBALS['no_cache']['taxon_concept_names'] = true;
$GLOBALS['no_cache']['taxon_concepts']      = true;



/* Set your working development environment
   if a web request and there is a paremeter ENV_NAME=$ENV that gets priority
   if a CLI request and there is an argument ENV_NAME=$ENV that gets second priority
   if a constant ENVIRONMENT exists that gets third priority
*/
function set_and_load_proper_environment(&$argv = NULL)
{
    if(isset($_REQUEST['ENV_NAME'])) $GLOBALS['ENV_NAME'] = $_REQUEST['ENV_NAME'];
    elseif(isset($argv) && $results = in_array_regex('ENV_NAME=(.+)', $argv))
    {
        $GLOBALS['ENV_NAME'] = $results['matches'][1];
        unset($argv[$results['key']]);
    }
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
    /* Override with any settings from /config/environments/local.php */
    if(file_exists(dirname(__FILE__) . '/environments/local.php'))
    {
        require_once(dirname(__FILE__) . '/environments/local.php');
    }
}

/* finds the first instance of $needle in $haystack and returns the resulting match array */
function in_array_regex($needle, $haystack)
{
    if(!is_array($haystack)) return false;
    foreach($haystack as $key => $value)
    {
        if(preg_match('/^'. str_replace('/', '\/', $needle) .'$/', $value, $arr))
        {
            return array('key' => $key, 'value' => $value, 'matches' => $arr);
        }
    }
    return false;
}


?>
