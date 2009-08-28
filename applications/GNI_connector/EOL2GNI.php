<?php

require_once(dirname(__FILE__) . '/../../config/start.php');
ini_set('memory_limit','3000M');
set_time_limit(0);
$url = get_val_var('url');
$what = get_val_var('what');
$download = get_val_var('download');

// for command line =============================
/*
$what = 'transform';
$url  = 'http://10.19.19.226/resources/11.xml';
*/
/*
$what = 'transform';
$url  = 'http://10.19.19.57/~dimus/eol_tcs/';
*/
// =========================================
//print "$what<hr>"; exit;


//start get first xmlns value
$url = "http://10.19.19.226/resources/".$url.".xml";

if($download)
{
	//print"<META HTTP-EQUIV='Refresh' Content='0; URL=$url'>";	
	exit("<a href='$url'>$url</a>");
}


$xml = simplexml_load_file($url);           //print_r($xml);
$namespaces = $xml->getNamespaces(true);    //var_dump($namespaces);

/*array(5)  {        [""]=> string(39) "http://www.eol.org/transfer/content/0.1" 
                     ["xsi"]=> string(41) "http://www.w3.org/2001/XMLSchema-instance" 
                     ["dc"]=> string(32) "http://purl.org/dc/elements/1.1/" 
                     ["dwc"]=> string(30) "http://rs.tdwg.org/dwc/dwcore/" 
                     ["dcterms"]=> string(25) "http://purl.org/dc/terms/" 
            } */    
//print "xsi = " . $namespaces['xsi'] . "<br>";
//print "dwc = " . $namespaces['dwc'] . "<br>";
//print " = " . $namespaces['']         . "<hr>";
//exit;
//end



$xml = new DOMDocument;        
if($xml->load($url))
{
    /* working well this is to read attribute values from any part of the XML
    //$xdoc = new DomDocument;
    //$xdoc->Load('C:/php/xml_files/candidate.xml');
    $candidatename = $xml->getElementsByTagName('response')->item(0);
    $attribNode = $candidatename->getAttributeNode('son');

    echo "<HTML><Head>";
    echo "<title> Getting Attribute Example</title>";
    echo "</Head><body><B>";
    echo "Attribute Name is :".$attribNode->name;
    echo "<BR>Attribute Value is :".$attribNode->value;
    echo "</B></body></HTML>";
    exit;
    */
    
    
    /*
    $node1 = $xml->createElementNS("http://www.eol.org/transfer/content/0.2", "xmlns"); 
    $node1 = $xml->appendChild($node1);
    $xml->removeChild($node1); 
    */    
}
else
{
    exit("<p>File not found. <a href='javascript:self.close()'>&lt;&lt; Back to menu</a>");
}

$xsl = new DOMDocument;        

if($what == 'transform')
{    
    if($namespaces[''] == "http://www.eol.org/transfer/content/0.1")$xsl->load('EOL2GNI_01.xsl');
    else                                                            $xsl->load('EOL2GNI.xsl');
}
elseif($what == 'evaluate')
{    
    if($namespaces[''] == "http://www.eol.org/transfer/content/0.1")$xsl->load('EOL_evaluate_01.xsl');
    else                                                            $xsl->load('EOL_evaluate.xsl');
}
$proc = new XSLTProcessor;    
/* //######################################################################################################
in php.ini this has to be un-commented 
    extension=php_xsl.dll
//###################################################################################################### */
$proc->importStyleSheet($xsl);

if($what == 'transform')
{
    //start save
    
    $res_id = str_ireplace(".xml", "", basename($url));
    
    //print"<hr> $url <br> " . basename($url) . "<br> $res_id <hr>";        

    $temp = $proc->transformToXML($xml);
    $filename = $res_id;
    $filename .= "_GNI";
    //$filename .= "_" . date("Ymd_His",time()) ;
        
    if(defined('CONTENT_GNI_RESOURCE_PATH')) $write_path_prefix = CONTENT_GNI_RESOURCE_PATH;
    else $write_path_prefix = "XML/";
    
    $path = $write_path_prefix . $filename .".xml";
    
    //print"<hr>$path<hr>";
    $path = str_ireplace("\\", "/", $path);
    $path = str_ireplace("//", "/", $path);
    //print"<hr>$path<hr>";
    $fn = fopen($path, 'w');    
    fputs($fn, $temp);                
    fclose($fn);        
    
    /*
    define("LOCAL_ROOT","C:\\webroot\\eol_php_code\\");
    define("LOCAL_WEB_ROOT","http://localhost/eol_php_code/");
    CONTENT_GNI_RESOURCE_PATH
    */
    
    $source_path = str_ireplace(LOCAL_ROOT, LOCAL_WEB_ROOT, CONTENT_GNI_RESOURCE_PATH);
    $source_path = str_ireplace("//", "/", $source_path);
    $source_path .= "$filename.xml";
    $source_path = str_ireplace("http:/", "", $source_path);
    //exit("<hr>$source_path");
    
    
    print"
    <a href='http://10.19.19.226/gni_resources/$filename.xml'>Download GNI-TCS XML</a><hr>    
    <a href=http://$source_path>Download GNI-TCS XML*</a><hr>           
    <a href='javascript:self.close()'>&lt;&lt; Back to menu</a>
    ";    
    
    //<p>filename = $filename    
    //end save    
}
elseif($what == 'evaluate'){echo $proc->transformToXML($xml);}

function get_val_var($v)
{   if(isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif(isset($_POST["$v"])){$var=$_POST["$v"];}    
    if(isset($var)){return $var;}else{return NULL;}
}
?>