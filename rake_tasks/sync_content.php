#!/usr/local/bin/php
<?php


$param = @$argv[1];
$value = @$argv[2];


if($param == "-d" && preg_match("/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/([0-9]{2})$/", $value, $arr))
{
    
    define('DEBUG', true);
    define('MYSQL_DEBUG', true);
    //define("ENVIRONMENT", "wattle");
    include_once("/data/www/eol_php_code/config/start.php");
    
    
    ContentManager::sync_to_content_servers($arr[1], $arr[2], $arr[3], $arr[4]);
}elseif($param == "-cp")
{

    define('DEBUG', true);
    define('MYSQL_DEBUG', true);
    //define("ENVIRONMENT", "wattle");
    include_once("/data/www/eol_php_code/config/start.php");


    ContentManager::sync_partner_logos();
}else
{
    echo "\n\n";
    echo "\t-d YYYY/MM/DD/HH    content\n";
    echo "\t-cp                 content partner logos\n";
    
    echo "\n";
}




?>