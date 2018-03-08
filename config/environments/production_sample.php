<?php

ini_set('memory_limit', '4096M'); // 4GB maximum memory usage
ini_set('max_execution_time', '604800'); // 7 days maximum running time
ini_set('display_errors', true);

// make sure you define the proper URL to the root directory of this installation
define('WEB_ROOT', 'http:// *PRODUCTION_WEB_SERVER_HOST* /eol_php_code/');

// this will create a file which will log certain rake tasks run
if(!($GLOBALS['log_file'] = fopen(DOC_ROOT . 'temp/processes.log', 'a+')))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $DOC_ROOT . 'temp/processes.log');
}

// make there there is at least a .3 second delay between
// requests to remote servers
if(!defined('DOWNLOAD_TIMEOUT_SECONDS')) define('DOWNLOAD_TIMEOUT_SECONDS', '30');

define('SOLR_SERVER', 'http:// *PRODUCTION_SOLR_IP* :8080/solr');

define('LOGGING_DB', 'eol_logging_production');

define('FLICKR_API_KEY', 'xxx');
define('FLICKR_SHARED_SECRET', 'xxx');
define('FLICKR_AUTH_TOKEN', 'xxx');

define('GOOGLE_API_CLIENT_ID', 'xxx');
define('GOOGLE_API_ACCOUNT_NAME', 'xxx');
define('GOOGLE_API_KEY_FILE', 'xxx/google-privatekey.p12');


?>