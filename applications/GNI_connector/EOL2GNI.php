<?php

require_once(dirname(__FILE__) . '/../../config/environment.php');
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

// /*
$domain = "10.19.19.226";
$path2XMLresource = "resources";
// */

/*
$domain = "127.0.0.1";
$path2XMLresource = "eol_php_code/applications/content_server/resources";
*/

//start get first xmlns value
if(substr($url,0,4) != "http") $url = "http://$domain/$path2XMLresource/".$url.".xml";
if($download) exit("<a href='$url'>$url</a>");

$xml = simplexml_load_file($url);           //print_r($xml);
$namespaces = $xml->getNamespaces(true);    //var_dump($namespaces);

$xml = new DOMDocument;        
if(!$xml->load($url)) exit("<p>File not found. <a href='javascript:self.close()'>&lt;&lt; Back to menu</a>");

$xsl = new DOMDocument;        
if($what == 'transform')
{    
    if($namespaces[''] == "http://www.eol.org/transfer/content/0.1")    $xsl->load('EOL2GNI_01.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.2")$xsl->load('EOL2GNI_02.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.3")$xsl->load('EOL2GNI_03.xsl');
}
elseif($what == 'evaluate')
{    
    if($namespaces[''] == "http://www.eol.org/transfer/content/0.1")    $xsl->load('EOL_evaluate_01.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.2")$xsl->load('EOL_evaluate_02.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.3")$xsl->load('EOL_evaluate_03.xsl');
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
    
    /* for debug    
    if(defined('CONTENT_GNI_RESOURCE_PATH')) exit("it is defined");
    else exit("not defined");
    */
    
    if(defined('CONTENT_GNI_RESOURCE_PATH')) $write_path_prefix = CONTENT_GNI_RESOURCE_PATH;
    else                                     $write_path_prefix = "XML/";
    
    $path = $write_path_prefix . $filename .".xml";
    
    //print"<hr>$path<hr>";
    $path = str_ireplace("\\", "/", $path);
    $path = str_ireplace("//", "/", $path);
    //print"<hr>$path<hr>";
    $fn = fopen($path, 'w');    
    fputs($fn, $temp);                
    fclose($fn);        
    
    /*
    define("DOC_ROOT","C:\\webroot\\eol_php_code\\");
    define("LOCAL_WEB_ROOT","http://localhost/eol_php_code/");
    CONTENT_GNI_RESOURCE_PATH
    */
    
    $source_path = str_ireplace(DOC_ROOT, LOCAL_WEB_ROOT, CONTENT_GNI_RESOURCE_PATH);
    $source_path = str_ireplace("//", "/", $source_path);
    $source_path .= "$filename.xml";
    $source_path = str_ireplace("http:/", "", $source_path);
    $source_path = str_ireplace("\\", "/", $source_path);
    //exit("<hr>$source_path");    
    
    print"
    Beast: <a href='http://$domain/gni_resources/$filename.xml'>Download GNI-TCS XML</a><hr>    
    Local: <a href=http://$source_path>Download GNI-TCS XML*</a><hr>           
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