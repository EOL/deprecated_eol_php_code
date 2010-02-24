<?php

/* Default Environment */
$GLOBALS['ENV_NAME'] = "development";
define("WEB_ROOT","http://localhost/eol_php_code/");

/* PHP Settings */
ini_set('memory_limit', '1200M');
ini_set('max_execution_time', '36000');
ini_set('display_errors', true);

/* Memcached */
$GLOBALS["ENV_MEMCACHED_SERVER"] = "localhost";

/* Debugging */
// show debug() messages
$GLOBALS['ENV_DEBUG'] = true;
// include mysql debug() messages
$GLOBALS['ENV_MYSQL_DEBUG'] = true;
// write all debug() messages to log/ENVIRONMENT.log
$GLOBALS['ENV_DEBUG_TO_FILE'] = true;
// set to false if you want the log to constantly grow
$GLOBALS['ENV_DEBUG_FILE_FLUSH'] = true;





/* Set the remote login for pulling content via ssh on the content server */
$GLOBALS["CONTENT_PARTNER_USER"]        = "";
$GLOBALS["CONTENT_PARTNER_PASSWORD"]    = "";

define("DEBUG_PARSE_TAXON_LIMIT", 0);

define('SOLR_SERVER', 'http://localhost:8983/solr');

define('SOLR_FILE_DELIMITER', '|');
define('SOLR_MULTI_VALUE_DELIMETER', ';');

define('DEFAULT_HIERARCHY_LABEL', 'Species 2000 & ITIS Catalogue of Life: Annual Checklist 2009');
define('PHP_BIN_PATH', '/usr/local/bin/php ');

########################################
/* Content Server */

if(!defined('DOWNLOAD_WAIT_TIME')) define("DOWNLOAD_WAIT_TIME", "300000"); //.1 seconds
define("DOWNLOAD_ATTEMPTS", "2");
define("DOWNLOAD_TIMEOUT_SECONDS", "10");

define("CONTENT_PARTNER_LOCAL_PATH", "/Users/pleary/Apache/eol_php_code/applications/content_server/content_partners/");
define("CONTENT_LOCAL_PATH", "/Users/pleary/Apache/eol_php_code/applications/content_server/content/");
define("CONTENT_TEMP_PREFIX", "/Users/pleary/Apache/eol_php_code/applications/content_server/tmp/");
define("CONTENT_RESOURCE_LOCAL_PATH", "/Users/pleary/Apache/eol_php_code/applications/content_server/resources/");
define("CONTENT_GNI_RESOURCE_PATH", "/Users/pleary/Apache/eol_php_code/applications/content_server/gni_tcs_files/");
define("PARTNER_LOGO_LARGE", "100x100");
define("PARTNER_LOGO_SMALL", "60x60");
define("CONTENT_IMAGE_LARGE", "460x345");
define("CONTENT_IMAGE_MEDIUM", "147x147");
define("CONTENT_IMAGE_SMALL", "62x47");

define('USING_IMAGEMAGICK', true);
if(defined('USING_IMAGEMAGICK') && USING_IMAGEMAGICK)
{
    define("MAGICK_HOME", "/usr/local/ImageMagick/");
    putenv("MAGICK_HOME=".MAGICK_HOME);
    putenv("PATH=".MAGICK_HOME."/bin/:".getenv("PATH"));
    putenv("DYLD_LIBRARY_PATH=".MAGICK_HOME."/lib");
}

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
$GLOBALS['no_cache']['data_objects']        = true;
$GLOBALS['no_cache']['hierarchy_entries']   = true;
$GLOBALS['no_cache']['name_languages']      = true;
$GLOBALS['no_cache']['names']               = true;
$GLOBALS['no_cache']['synonyms']            = true;
$GLOBALS['no_cache']['taxa']                = true;
$GLOBALS['no_cache']['taxon_concept_names'] = true;
$GLOBALS['no_cache']['taxon_concepts']      = true;












// The following block needs to remain in tact
// START
/* Override with any settings from /config/environments/ENVIRONMENT.php */
if(file_exists(dirname(__FILE__) . '/environments/' . $GLOBALS['ENV_NAME'] . '.php'))
{
    require_once(dirname(__FILE__) . '/environments/' . $GLOBALS['ENV_NAME'] . '.php');
}
/* Initialize app */
require_once(dirname(__FILE__) . '/boot.php');
// END


// modules need to be included at the end of this file, after the boot loader is run
require_module('eol_content_schema');
require_module('solr');
require_module('darwincore');


?>