<?php
/* 
    In php.ini this has to be loaded:
    extension=php_xsl.dll
*/
require_once(dirname(__FILE__) . '/../../config/environment.php');
$url  = $_REQUEST['url'];
$what = $_REQUEST['what'];
if(isset($_REQUEST['download'])) $download = $_REQUEST['download'];
else                             $download = 0;

$domain = "10.19.19.226";
$path2XMLresource = "resources";

//start get first xmlns value
if(substr($url,0,4) != "http") $url = "http://$domain/$path2XMLresource/".$url.".xml";
if($download) exit("<a href='$url'>$url</a>");

$xml = simplexml_load_file($url);
$namespaces = $xml->getNamespaces(true);

$xml = new DOMDocument;
if(!$xml->load($url)) exit("<p>File not found. <a href='javascript:self.close()'>&lt;&lt; Back to menu</a>");

$xsl = new DOMDocument;
if($what == 'transform')
{    
    if($namespaces[''] == "http://www.eol.org/transfer/content/0.1")     $xsl->load('EOL2GNI_01.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.2") $xsl->load('EOL2GNI_02.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.3") $xsl->load('EOL2GNI_03.xsl');
}
elseif($what == 'evaluate')
{    
    if($namespaces[''] == "http://www.eol.org/transfer/content/0.1")     $xsl->load('EOL_evaluate_01.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.2") $xsl->load('EOL_evaluate_02.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.3") $xsl->load('EOL_evaluate_03.xsl');
}
elseif($what == 'evaluate_spm')
{    
    if($namespaces[''] == "http://www.eol.org/transfer/content/0.1")     $xsl->load('EOL_evaluate_spm_01.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.2") $xsl->load('EOL_evaluate_spm_02.xsl');
    elseif($namespaces[''] == "http://www.eol.org/transfer/content/0.3") $xsl->load('EOL_evaluate_spm_03.xsl');
}
$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl);
if($what == 'transform')
{
    //start save
    $res_id = str_ireplace(".xml", "", basename($url));
    $temp = $proc->transformToXML($xml);
    $filename = $res_id;
    $filename .= "_GNI";
    if(defined('CONTENT_GNI_RESOURCE_PATH')) $write_path_prefix = CONTENT_GNI_RESOURCE_PATH;
    else                                     $write_path_prefix = "XML/";
    $path = $write_path_prefix . $filename . ".xml";
    $path = str_ireplace("\\", "/", $path);
    $path = str_ireplace("//", "/", $path);
    if (!($fn = fopen($path, 'w')))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $path);
      return;
    }
    fputs($fn, $temp);
    fclose($fn);
    $source_path = str_ireplace(DOC_ROOT, LOCAL_WEB_ROOT, CONTENT_GNI_RESOURCE_PATH);
    $source_path = str_ireplace("//", "/", $source_path);
    $source_path .= "$filename.xml";
    $source_path = str_ireplace("http:/", "", $source_path);
    $source_path = str_ireplace("\\", "/", $source_path);
    print"
    Beast: <a href='http://$domain/gni_resources/$filename.xml'>Download GNI-TCS XML</a><hr>
    Local: <a href=http://$source_path>Download GNI-TCS XML*</a><hr>
    <a href='javascript:self.close()'>&lt;&lt; Back to menu</a>";
    //end save    
}
elseif($what == 'evaluate'){echo $proc->transformToXML($xml);}
elseif($what == 'evaluate_spm'){echo $proc->transformToXML($xml);}
?>