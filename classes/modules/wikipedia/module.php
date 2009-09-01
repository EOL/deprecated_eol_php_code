<?php

Functions::require_classes_from_dir(dirname(__FILE__)."/");

if(!defined("WIKI_PREFIX")) define("WIKI_PREFIX", "http://en.wikipedia.org/wiki/");
if(!defined("WIKI_USER_PREFIX")) define("WIKI_USER_PREFIX", "http://commons.wikimedia.org/wiki/User:");

$GLOBALS['iso_639_2_codes'] = array();
if(file_exists(dirname(__FILE__) . '/iso_639_2.txt'))
{
    $lines = file(dirname(__FILE__) . '/iso_639_2.txt');
    foreach($lines as $line)
    {
        $line = rtrim($line, "\n");
        $parts = explode("\t", $line);
        if(isset($parts[0]) && strlen($parts[0])==2 && isset($parts[1]))
        {
            $GLOBALS['iso_639_2_codes'][$parts[0]] = $parts[1];
        }
    }
}

?>