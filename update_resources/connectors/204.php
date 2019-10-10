<?php
include_once(dirname(__FILE__) . "/../../config/environment.php");

$path_to_raw_file = DOC_ROOT . "update_resources/connectors/files/tol_new.xml";
$path_to_updated_file = DOC_ROOT . "update_resources/connectors/files/tol-all-content-nc_updated.xml";
$path_to_final_file = DOC_ROOT . "update_resources/connectors/files/tol-all-content-nc_final.xml";

// // convert weird characters in the file
// $file = file_get_contents($path_to_raw_file);
// $file = convert_file($file);
// 
// $OUT = fopen($path_to_updated_file, "w+");
// fwrite($OUT, $file);
// fclose($OUT);
// unset($file);

$reader = new XMLReader();
$reader->open($path_to_raw_file);

if(!($OUT = fopen($path_to_final_file, "w+"))) {
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$path_to_final_file);
  return;
}
scan_recursively($reader, $OUT);
fclose($OUT);

// // convert it again because new bad characters are introduced somehow
// $file = file_get_contents($path_to_final_file);
// $file = convert_file($file);
// 
// $OUT = fopen($path_to_final_file, "w+");
// fwrite($OUT, $file);
// fclose($OUT);
// unset($file);

function scan_recursively($reader, &$OUT)
{
    $node_string = "";
    while(@$reader->read()) {
        switch($reader->nodeType) {
            case XMLReader::END_ELEMENT:
                fwrite($OUT, "</" . $reader->name.">\n");
                return $node_string;
                break;
            case XMLReader::ELEMENT:
                $node_string = "<" . $reader->name;
                if($reader->hasAttributes) {
                    while($reader->moveToNextAttribute()) {
                        $node_string .= " " . $reader->name . "=\"";
                        $node_string .= handle_encoded_string($reader->value) . "\"";
                    }
                }
                fwrite($OUT, $node_string .= ">");
                if(!$reader->isEmptyElement) {
                    $node_string .= scan_recursively($reader, $OUT);
                }
                break;
            case XMLReader::TEXT:
            case XMLReader::CDATA:
                $node_string = handle_encoded_string($reader->value);
                fwrite($OUT, $node_string);
        }
    }
    return $node_string;
}

function handle_encoded_string($string)
{
    $string = htmlspecialchars_decode(html_entity_decode($string, ENT_COMPAT, 'UTF-8'));
    $string = htmlspecialchars_decode(html_entity_decode($string, ENT_COMPAT, 'UTF-8'));
    //$string = htmlspecialchars_decode(html_entity_decode($string, ENT_COMPAT, 'UTF-8'));
    
    // finally convert the special characters back to encode things like & < > ...
    $string = htmlspecialchars($string);
    $string = str_replace("&amp;#x2019;", "'", $string);
    $string = preg_replace("/&amp;(lsquo|rsquo|ndash|ldquo|rdquo|bdquo|prime|sigma|dagger|hellip|mdash|ge|beta|mu|alpha|apos|phi);/ims", "&\\1;", $string);
    $string = preg_replace("/&amp;#([0-9]{2,5});/ims", "&\\1;", $string);
    
    if(preg_match("/&amp;(#?[0-9a-z]{1,9});/", $string, $arr)) echo $arr[1]."\n";
    
    $string = str_replace("&lsquo;", "&#8216;", $string);
    $string = str_replace("&rsquo;", "&#8217;", $string);
    $string = str_replace("&ndash;", "&#8211;", $string);
    $string = str_replace("&ldquo;", "&#8220;", $string);
    $string = str_replace("&rdquo;", "&#8221;", $string);
    $string = str_replace("&bdquo;", "&#8222;", $string);
    $string = str_replace("&prime;", "&#8242;", $string);
    $string = str_replace("&sigma;", "&#963;", $string);
    $string = str_replace("&dagger;", "&#8224;", $string);
    $string = str_replace("&hellip;", "&#8230;", $string);
    $string = str_replace("&mdash;", "&#8212;", $string);
    $string = str_replace("&ge;", "&#8805;", $string);
    $string = str_replace("&beta;", "&#946;", $string);
    $string = str_replace("&mu;", "&#956;", $string);
    $string = str_replace("&alpha;", "&#945;", $string);
    $string = str_replace("&apos;", "'", $string);
    $string = str_replace("&phi;", "&#966;", $string);
    return $string;
}
function convert_file($file)
{
    $file = str_replace("\x92", "'", $file);
    $file = str_replace("\xE1", "á", $file);
    $file = str_replace("\xE4", "ä", $file);
    $file = str_replace("\xF1", "ñ", $file);
    $file = str_replace("\xFA", "u", $file);
    $file = str_replace("\xED", "í", $file);
    $file = str_replace("\xE9", "é", $file);
    $file = str_replace("\xF3", "ó", $file);
    $file = str_replace("\xE3", "ã", $file);
    $file = str_replace("\xFC", "ü", $file);
    $file = str_replace("\xB0", "°", $file);
    $file = str_replace("\xDF", "ß", $file);
    $file = str_replace("\xBA", "°", $file);
    $file = str_replace("\xE8", "è", $file);
    $file = str_replace("\xF8", "ø", $file);
    $file = str_replace("\xE5", "å", $file);
    $file = str_replace("\xB5", "µ", $file);
    return $file;
}
?>