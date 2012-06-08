<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

if(filesize(DOC_ROOT . 'log/production.log') >= 1073741824) // 1GB
{
    shell_exec("tar -czf " . DOC_ROOT . "log/production.log." . date('m.d.y-H:i:s') . ".tar.gz --directory=" . DOC_ROOT . "log/ production.log");
    shell_exec("cat /dev/null > " . DOC_ROOT . "log/production.log");
    
}

?>
