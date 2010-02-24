<?php

require_once "Horde/Yaml.php";
require_once "Horde/Yaml/Loader.php";
require_once "Horde/Yaml/Node.php";

require_once(LOCAL_ROOT."classes/MysqlBase.php");

if(defined("USING_SPM") && USING_SPM)
{
    require_once(LOCAL_ROOT . "classes/MysqlBase.php");
    require_once(LOCAL_ROOT . "classes/MysqlConnection.php");
    require_once(LOCAL_ROOT . "classes/modules/rdfapi-php/api/RdfAPI.php");
    require_once(LOCAL_ROOT . "classes/Functions.php");
    
    require_once(LOCAL_ROOT . "classes/TDWG/RDFDocument.php");
    require_once(LOCAL_ROOT . "classes/TDWG/RDFDocumentElement.php");
    
    require_all_classes(LOCAL_ROOT . "classes/TDWG/");
    
}else require_all_classes(LOCAL_ROOT . "classes/");

Functions::require_module('eol_content_schema');
Functions::require_module('solr');
Functions::require_module('darwincore');

date_default_timezone_set('America/New_York');
setlocale(LC_ALL, 'en_US.ASCII');

if(strtolower(substr(php_uname(), 0, 3)) == 'win') define('SYSTEM_OS', 'Windows');
else define('SYSTEM_OS', 'Unix');















########################################
/* Functions */

function require_all_classes($dir)
{
    if($handle = opendir($dir))
    {
       while(false !== ($file = readdir($handle)))
       {
           if($file != "." && $file != "..")
           {
               if(preg_match("/\.php$/",trim($file))) require_once($dir.$file);
               elseif(!preg_match("/\./", $file) && $file != "modules" && $file != "TDWG") require_all_classes($dir.$file."/");
           }
       }
       closedir($handle);
    }
}

function load_mysql_environment($environment = ENVIRONMENT)
{
    $environments = Horde_Yaml::loadFile(LOCAL_ROOT."config/database.yml");
    $GLOBALS['environments'] = $environments;
    
    if(!@$environments[$environment]) return NULL;
    
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
    
    if(MYSQL_READ_ONLY && !MYSQL_MASTER)
    {
        $MASTER_MYSQL_SERVER    = "";
        $MASTER_MYSQL_USER      = "";
        $MASTER_MYSQL_PASSWORD  = "";
        $MASTER_MYSQL_DATABASE  = "";
        $MASTER_MYSQL_ENCODING  = "";
        $MASTER_MYSQL_PORT      = "";
        $MASTER_MYSQL_SOCKET    = "";
    }
    
    if(MYSQL_MASTER && !MYSQL_READ_ONLY)
    {
        $MYSQL_SERVER   = $MASTER_MYSQL_SERVER;
        $MYSQL_USER     = $MASTER_MYSQL_USER;
        $MYSQL_PASSWORD = $MASTER_MYSQL_PASSWORD;
        $MYSQL_DATABASE = $MASTER_MYSQL_DATABASE;
        $MYSQL_ENCODING = $MASTER_MYSQL_ENCODING;
        $MYSQL_PORT     = $MASTER_MYSQL_PORT;
        $MYSQL_SOCKET   = $MASTER_MYSQL_SOCKET;
    }
    
    return new MysqlConnection($MYSQL_SERVER, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DATABASE, $MYSQL_ENCODING, $MYSQL_PORT, $MYSQL_SOCKET, $MASTER_MYSQL_SERVER, $MASTER_MYSQL_USER, $MASTER_MYSQL_PASSWORD, $MASTER_MYSQL_DATABASE, $MASTER_MYSQL_ENCODING, $MASTER_MYSQL_PORT, $MASTER_MYSQL_SOCKET);
}

function shutdown_check()
{
    if(@$GLOBALS['mysqli_connection']->transaction_in_progress) $GLOBALS['mysqli_connection']->rollback();
    if(MYSQL_DEBUG) Functions::debug("\n\n<hr>Shutting down<br>\n\n\n");
    
    if(DEBUG && DEBUG_TO_FILE && @$GLOBALS['debug_file'])
    {
        fclose($GLOBALS['debug_file']);
    }
}

function render_template($filename, $parameters = NULL, $return = false)
{
    $filename = "templates/" . $filename . ".php";
    if(is_file($filename))
    {
        if(is_array($parameters)) extract($parameters);
        
        ob_start();
        include $filename;
        $contents = ob_get_contents();
        ob_end_clean();
        
        if($return) return $contents;
        else echo $contents;
    }else print "template $filename does not exist";
    
    return false;
}

?>