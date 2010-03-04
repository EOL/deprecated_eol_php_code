<?php

/* Default Environment */
if(!isset($GLOBALS['ENV_NAME'])) $GLOBALS['ENV_NAME'] = 'development';


define('WEB_ROOT', 'http://localhost/eol_php_code/');
/* Initialize app - this should be at the top of environment.php, just after declaring the default ENV
   but you you need to declare the WEB_ROOT, do that first */
require_once(dirname(__FILE__) . '/boot.php');


/* this is the absolute path to the PHP binary
   THE SPACE AT THE END IS IMPORTANT */
if(!defined('PHP_BIN_PATH')) define('PHP_BIN_PATH', '/usr/local/bin/php ');


/* where ImageMagick is installed */
if(!defined('MAGICK_HOME')) define('MAGICK_HOME', '/usr/local/ImageMagick/');


/* the 'default' hierarchy - the one which gets matched to new taxa first */
if(!defined('DEFAULT_HIERARCHY_LABEL')) define('DEFAULT_HIERARCHY_LABEL', 'Species 2000 & ITIS Catalogue of Life: Annual Checklist 2009');


/* MEMCACHED */
$GLOBALS['ENV_MEMCACHED_SERVER'] = 'localhost';
$GLOBALS['ENV_ENABLE_CACHING'] = true;


/* Modules needed */
require_module('eol_content_schema');
require_module('solr');
require_module('darwincore');


/* Solr delimeters */
define('SOLR_FILE_DELIMITER', '|');
define('SOLR_MULTI_VALUE_DELIMETER', ';');


/* For content downloading */
if(!defined('CONTENT_PARTNER_LOCAL_PATH'))  define('CONTENT_PARTNER_LOCAL_PATH',    DOC_ROOT . 'applications/content_server/content_partners/');
if(!defined('CONTENT_LOCAL_PATH'))          define('CONTENT_LOCAL_PATH',            DOC_ROOT . 'applications/content_server/content/');
if(!defined('CONTENT_TEMP_PREFIX'))         define('CONTENT_TEMP_PREFIX',           DOC_ROOT . 'applications/content_server/tmp/');
if(!defined('CONTENT_RESOURCE_LOCAL_PATH')) define('CONTENT_RESOURCE_LOCAL_PATH',   DOC_ROOT . 'applications/content_server/resources/');
if(!defined('CONTENT_GNI_RESOURCE_PATH'))   define('CONTENT_GNI_RESOURCE_PATH',     DOC_ROOT . 'applications/content_server/gni_tcs_files/');
if(!defined('PARTNER_LOGO_LARGE'))          define('PARTNER_LOGO_LARGE',            '100x100');
if(!defined('PARTNER_LOGO_SMALL'))          define('PARTNER_LOGO_SMALL',            '60x60');
if(!defined('CONTENT_IMAGE_LARGE'))         define('CONTENT_IMAGE_LARGE',           '460x345');
if(!defined('CONTENT_IMAGE_MEDIUM'))        define('CONTENT_IMAGE_MEDIUM',          '147x147');
if(!defined('CONTENT_IMAGE_SMALL'))         define('CONTENT_IMAGE_SMALL',           '62x47');

if(!defined('DOWNLOAD_WAIT_TIME')) define('DOWNLOAD_WAIT_TIME', '300000'); //.3 seconds
define('DOWNLOAD_ATTEMPTS', '2');
define('DOWNLOAD_TIMEOUT_SECONDS', '10');


/* for the Flickr connector */
$GLOBALS['flickr_licenses'] = array();
//$GLOBALS['flickr_licenses'][0] = "All Rights Reserved";
$GLOBALS['flickr_licenses'][1] = "http://creativecommons.org/licenses/by-nc-sa/2.0/";
$GLOBALS['flickr_licenses'][2] = "http://creativecommons.org/licenses/by-nc/2.0/";
//$GLOBALS['flickr_licenses'][3] = "http://creativecommons.org/licenses/by-nc-nd/2.0/";
$GLOBALS['flickr_licenses'][4] = "http://creativecommons.org/licenses/by/2.0/";
$GLOBALS['flickr_licenses'][5] = "http://creativecommons.org/licenses/by-sa/2.0/";
//$GLOBALS['flickr_licenses'][6] = "http://creativecommons.org/licenses/by-nd/2.0/";


/* table data which will not get cached - there are too many rows */
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


/* setting the ImageMagick path */
putenv('MAGICK_HOME='. MAGICK_HOME);
putenv('PATH='. MAGICK_HOME .'/bin/:'.getenv('PATH'));
putenv('DYLD_LIBRARY_PATH='. MAGICK_HOME .'/lib');


?>