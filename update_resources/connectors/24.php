#!/usr/local/bin/php
<?php

//define("MYSQL_DEBUG", true);
$path = "";
if(preg_match("/^(.*\/)[^\/]+/", $_SERVER["_"], $arr)) $path = $arr[1];
include_once($path."../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];




//$xml = simplexml_load_file("amphib_dump.xml",'SimpleXMLElement',LIBXML_NOCDATA);
$xml = simplexml_load_file(LOCAL_ROOT . "temp/antweb2eol_080731.xml");
$taxon_index = 0;
foreach(@$xml->taxon as $taxon)
{
    $i = 0;
    $label_index = 0;
    foreach(@$taxon->dataObject as $dataObject)
    {
        $i++;
        $dataObject_dc = $dataObject->children("http://purl.org/dc/elements/1.1/");
        
        if($identifier = @$dataObject_dc->identifier)
        {
            if(preg_match("/^\/images\//", $identifier, $arr))
            {
                if(preg_match("/_l_[0-9]{1,}_high\.jpg/", $identifier))
                {
                    $label_index = $i;
                    continue;
                }
                //echo "$identifier\n";
                $dataObject->addChild("mediaURL", "http://www.antweb.org" . str_replace(" ", "%20", $identifier));
            }
        }
    }
    
    if($label_index)
    {
        $label_index -= 1;
        //echo "Unsetting taxon[$taxon_index]->dataObject[$label_index]\n";
        unset($xml->taxon[$taxon_index]->dataObject[$label_index]);
    }
    
    $taxon_index++;
}


$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "24.xml";

$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $xml->asXML());
fclose($OUT);


?>