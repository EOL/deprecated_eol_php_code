<?php

ini_set('memory_limit', '4096M'); // 4GB maximum memory usage
ini_set('max_execution_time', '604800'); // 7 days maximum running time
ini_set('display_errors', true);

define('PEER_SITE_ID', 1);

// make sure you define the proper URL to the root directory of this installation
define('WEB_ROOT', 'http:// *PRODUCTION_WEB_SERVER_HOST* /eol_php_code/');

// this will create a file which will log certain rake tasks run
$GLOBALS['log_file'] = fopen(DOC_ROOT . "temp/processes.log", "a+");

// make there there is at least a .3 second delay between
// requests to remote servers
if(!defined('DOWNLOAD_TIMEOUT_SECONDS')) define('DOWNLOAD_TIMEOUT_SECONDS', '30');

define('SOLR_SERVER', 'http:// *PRODUCTION_SOLR_IP* :8080/solr');

define("CYBERSOURCE_PUBLIC_KEY", "xxx");
define("CYBERSOURCE_PRIVATE_KEY", "xxx");
define("CYBERSOURCE_SERIAL_NUMBER", "xxx");
define("CYBERSOURCE_MERCHANT_ID", "xxx");

define("LOGGING_DB", "eol_logging_production");

define("FLICKR_API_KEY", "xxx");
define("FLICKR_SHARED_SECRET", "xxx");
define("FLICKR_PLEARY_AUTH_TOKEN", "xxx");
define("FLICKR_PLEARY_USER_ID", "xxx");

define("GOOGLE_ANALYTICS_API_USERNAME", "xxx");
define("GOOGLE_ANALYTICS_API_PASSWORD", "xxx");


?>