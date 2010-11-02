<?php

/* best to leave the PHP settings at the top in case they are overridden in another environment */
ini_set('memory_limit', '1000M');
ini_set('max_execution_time', '360');

/* Default Environment */
if(!isset($GLOBALS['ENV_NAME'])) $GLOBALS['ENV_NAME'] = 'development';

define('WEB_ROOT', 'http://localhost/eol_php_code/');   // URL prefix of this installation
define('MAGICK_HOME', '/usr/local/ImageMagick/');       // path to ImageMagick home directory
define('MYSQL_BIN_PATH', 'mysql ');                     // path to mysql binary. THE SPACE AT THE END IS IMPORTANT


/* Initialize app - this should be towards the top of environment.php, but declare the WEB_ROOT first 
   this will load values from ./environments/ENV_NAME.php before values below
*/
require_once(dirname(__FILE__) . '/boot.php');






/* the 'default' hierarchy - the one which gets matched to new taxa first */
if(!defined('DEFAULT_HIERARCHY_LABEL')) define('DEFAULT_HIERARCHY_LABEL', 'Species 2000 & ITIS Catalogue of Life: Annual Checklist 2010');


/* MEMCACHED */
//$GLOBALS['ENV_MEMCACHED_SERVER'] = 'localhost';
$GLOBALS['ENV_ENABLE_CACHING'] = true;


/* Modules needed */
require_library('Functions');
require_library('ContentManager');
require_library('SSH2Connection');
require_library('SchemaConnection');
require_library('SchemaParser');
require_library('SchemaValidator');
require_library('CompareHierarchies');
require_library('ControllerBase');
require_library('MysqlBase');
require_library('NamesFunctions');
require_library('Tasks');
require_vendor('eol_content_schema');
require_vendor('solr');
require_vendor('darwincore');


/* For content downloading */
# where content partner logos will be downloaded to (mnust be web accessible)
if(!defined('CONTENT_PARTNER_LOCAL_PATH'))  define('CONTENT_PARTNER_LOCAL_PATH',    DOC_ROOT . 'applications/content_server/content_partners/');
# where harvested media will be downloaded to (mnust be web accessible)
if(!defined('CONTENT_LOCAL_PATH'))          define('CONTENT_LOCAL_PATH',            DOC_ROOT . 'applications/content_server/content/');
# where harvested media will eb temporarily stored before being moved the above directory
if(!defined('CONTENT_TEMP_PREFIX'))         define('CONTENT_TEMP_PREFIX',           DOC_ROOT . 'applications/content_server/tmp/');
# where resource XML files will be downloaded to
if(!defined('CONTENT_RESOURCE_LOCAL_PATH')) define('CONTENT_RESOURCE_LOCAL_PATH',   DOC_ROOT . 'applications/content_server/resources/');
if(!defined('CONTENT_GNI_RESOURCE_PATH'))   define('CONTENT_GNI_RESOURCE_PATH',     DOC_ROOT . 'applications/content_server/gni_tcs_files/');
# the default large/small size of content partner logos - larger versions will be scaled to this size using ImageMagick
if(!defined('PARTNER_LOGO_LARGE'))          define('PARTNER_LOGO_LARGE',            '100x100');
if(!defined('PARTNER_LOGO_SMALL'))          define('PARTNER_LOGO_SMALL',            '60x60');
# the default sizes of downloaded images - larger versions will be scaled to this size using ImageMagick
# large - used on species pages
# medium - used on the homepage rotating images
# small - used as thumbnails on species pages
if(!defined('CONTENT_IMAGE_LARGE'))         define('CONTENT_IMAGE_LARGE',           '460x345');
if(!defined('CONTENT_IMAGE_MEDIUM'))        define('CONTENT_IMAGE_MEDIUM',          '147x147');
if(!defined('CONTENT_IMAGE_SMALL'))         define('CONTENT_IMAGE_SMALL',           '62x47');


/* table data which will not get cached - there are too many rows */
$GLOBALS['no_cache']['agents']              = true;
$GLOBALS['no_cache']['canonical_forms']     = true;
$GLOBALS['no_cache']['content_partners']    = true;
$GLOBALS['no_cache']['data_objects']        = true;
$GLOBALS['no_cache']['harvest_events']      = true;
$GLOBALS['no_cache']['hierarchies']         = true;
$GLOBALS['no_cache']['hierarchy_entries']   = true;
$GLOBALS['no_cache']['name_languages']      = true;
$GLOBALS['no_cache']['names']               = true;
$GLOBALS['no_cache']['resources']           = true;
$GLOBALS['no_cache']['synonyms']            = true;
$GLOBALS['no_cache']['taxa']                = true;
$GLOBALS['no_cache']['taxon_concept_names'] = true;
$GLOBALS['no_cache']['taxon_concepts']      = true;


?>