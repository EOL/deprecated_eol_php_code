<?php



/* Set your working local paths */
define("LOCAL_ROOT", dirname(__FILE__) ."/../");
define("DOC_ROOT", LOCAL_ROOT); // alias

/* Set to true if using content server apps or creating thumbnails */
define("USING_IMAGEMAGICK", true);

/* Set to true if using content server apps or creating thumbnails */
if(!defined("USING_SPM")) define("USING_SPM", false);

/* Set the remote login for pulling content via ssh on the content server */
define("CONTENT_PARTNER_USER",      "");
define("CONTENT_PARTNER_PASSWORD",  "");

require_once(LOCAL_ROOT.'config/constants.php');
require_once(LOCAL_ROOT.'config/includes.php');



/* Set to true if debugging */
if(!defined("MYSQL_DEBUG"))         define("MYSQL_DEBUG", false);
if(!defined("DEBUG"))               define("DEBUG", false);
if(!defined("DEBUG_TO_FILE"))       define("DEBUG_TO_FILE", false);
if(!defined("DEBUG_FILE_FLUSH"))    define("DEBUG_FILE_FLUSH", true);
if(!defined("MYSQL_READ_ONLY"))     define("MYSQL_READ_ONLY", false);
if(!defined("MYSQL_MASTER"))        define("MYSQL_MASTER", false);

/* Set your working MySQL environment */
if(!defined("ENVIRONMENT"))
{
    define("ENVIRONMENT", "development");
    //define("ENVIRONMENT", "test");
    //define("ENVIRONMENT", "integration");
    //define("ENVIRONMENT", "production");
}

/* comment these 3 lines out if you are not using MySQL */
$GLOBALS['mysqli_connection'] = load_mysql_environment(ENVIRONMENT);
register_shutdown_function("shutdown_check");
$GLOBALS['mysqli'] =& $GLOBALS['mysqli_connection'];




if(DEBUG && DEBUG_TO_FILE)
{
    $open_state = "a+";
    if(DEBUG_FILE_FLUSH) $open_state = "w+";
    $GLOBALS['debug_file'] = fopen(LOCAL_ROOT."temp/application.log", $open_state);
}

if(MYSQL_DEBUG || DEBUG) ob_implicit_flush(true);

/* Some PHP ini settings */
ini_set('memory_limit',             '1200M');
ini_set('max_execution_time',       '36000');
//ini_set('default_socket_timeout',   DOWNLOAD_TIMEOUT_SECONDS);
//error_reporting(0);

/* Empty arrays for caching */
$GLOBALS['class_instances']     = array();
$GLOBALS['table_ids']           = array();
$GLOBALS['languages']           = array();
$GLOBALS['find_by_ids']         = array();
$GLOBALS['tables_find_by_id']   = array();
$GLOBALS['table_fields']        = array();
$GLOBALS['function_returns']    = array();

$GLOBALS['no_cache']['agents']              = true;
$GLOBALS['no_cache']['canonical_forms']     = true;
$GLOBALS['no_cache']['clean_names']         = true;
$GLOBALS['no_cache']['data_objects']        = true;
$GLOBALS['no_cache']['hierarchy_entries']   = true;
$GLOBALS['no_cache']['name_languages']      = true;
$GLOBALS['no_cache']['names']               = true;
$GLOBALS['no_cache']['normalized_links']    = true;
$GLOBALS['no_cache']['normalized_names']    = true;
$GLOBALS['no_cache']['synonyms']            = true;
$GLOBALS['no_cache']['taxa']                = true;
$GLOBALS['no_cache']['taxon_concept_names'] = true;
$GLOBALS['no_cache']['taxon_concepts']      = true;

Functions::sci_parts();
Functions::author_parts();
Functions::junk_parts();

?>