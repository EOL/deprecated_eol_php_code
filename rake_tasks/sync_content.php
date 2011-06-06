<?php
namespace php_active_record;


$param = @$argv[1];
$value = @$argv[2];


if($param == "-d" && preg_match("/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/([0-9]{2})$/", $value, $arr))
{
    include_once(dirname(__FILE__) . "/../config/environment.php");
    
    
    ContentManager::sync_to_content_servers($arr[1], $arr[2], $arr[3], $arr[4]);
}elseif($param == "-cp")
{
    include_once(dirname(__FILE__) . "/../config/environment.php");


    ContentManager::sync_partner_logos();
}else
{
    echo "\n\n";
    echo "\t-d YYYY/MM/DD/HH    content\n";
    echo "\t-cp                 content partner logos\n";
    
    echo "\n";
}




?>