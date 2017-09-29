<?php
exit;
/* AntWeb is now giving us a complete resource XML with <mediaURL> element for their image objects */
include_once(dirname(__FILE__) . "/../../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

$file = trim(Functions::get_remote_file("http://antweb.org/getEOL.do"));
//$file = trim(Functions::get_remote_file("../../temp/ants.xml"));
//echo "$file";
$xml = simplexml_load_string($file);
$taxon_index = 0;
foreach($xml->taxon as $taxon) {
    $i = 0;
    $label_index = 0;
    foreach($taxon->dataObject as $dataObject) {
        $i++;
        $dataObject_dc = $dataObject->children("http://purl.org/dc/elements/1.1/");
        
        if($identifier = @$dataObject_dc->identifier) {
            if(preg_match("/^\/images\//", $identifier, $arr)) {
                if(preg_match("/_l_[0-9]{1,}_high\.jpg/", $identifier)) {
                    $label_index = $i;
                    continue;
                }
                //echo "$identifier\n";
                $dataObject->addChild("mediaURL", "http://www.antweb.org" . str_replace(" ", "%20", $identifier));
            }
        }
    }
    
    if($label_index) {
        $label_index -= 1;
        //echo "Unsetting taxon[$taxon_index]->dataObject[$label_index]\n";
        unset($xml->taxon[$taxon_index]->dataObject[$label_index]);
    }
    
    $taxon_index++;
}

if($taxon_index) {
    $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "24.xml";
    if(!($OUT = fopen($old_resource_path, "w+")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
      return;
    }
    fwrite($OUT, $xml->asXML());
    fclose($OUT);
}

?>