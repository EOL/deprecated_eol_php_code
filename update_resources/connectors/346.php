<?php
namespace php_active_record;
/* connector for National Museum of Natural History Image Collection - part of 120 176 341 342 343 344 346
estimated execution time: 6.9 hours
Connector reads the XML provided by partner and 
- sets the image rating.
- If needed ingests TypeInformation text dataObjects
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('ResourceDataObjectElementsSetting');
exit("\nAs of last check, XML has invalid chars. Not yet fixed by partner.\n");
$timestart = time_elapsed();
$resource_id = 346; 

// $resource_path = "http://localhost/eol_php_code/applications/content_server/resources/eli.xml";
// $resource_path = "http://localhost/cp/OpenData/EOLxml_2_DWCA/nmnh-botany-response.xml.gz";
$resource_path = Functions::get_accesspoint_url_if_available($resource_id, "http://collections.mnh.si.edu/services/eol/nmnh-botany-response.xml.gz"); //Botany Resource
echo "\n processing resource:\n $resource_path \n\n";

$nmnh = new ResourceDataObjectElementsSetting($resource_id, $resource_path, 'http://purl.org/dc/dcmitype/StillImage', 2);
$xml = $nmnh->load_xml_string();

$xml = remove_location_tag($xml); echo "\n --- remove_location_tag() DONE\n"; //kind a hack, totally removes <location> tags. Since there are invalid chars inside <location> tags. Makes the XML invalid.
// will remove this line once NMNH fixes their XML.

$xml = $nmnh->set_data_object_rating_on_xml_document(60*60*24*25, $xml); echo "\n --- set_data_object_rating_on_xml_document() DONE\n"; //no params means will use default expire_seconds = 25 days
//debug 0 -> expire_seconds param debug only
$xml = $nmnh->fix_NMNH_xml($xml); echo "\n --- fix_NMNH_xml() DONE\n";

//manual fix DATA-1189, until partner fixes their data
$xml = str_ireplace("Photograph of Photograph of", "Photograph of", $xml); echo "\n --- str_replace() 1 DONE\n";

//manual fix DATA-1205
$xml = replace_Indet_sp($xml); echo "\n - replace_Indet_sp() DONE\n";
$xml = remove_blank_taxon_entry($xml); echo "\n --- remove_blank_taxon_entry() DONE\n";

require_library('connectors/INBioAPI');
$xml = INBioAPI::assign_eol_subjects($xml); echo "\n --- assign_eol_subjects() DONE\n";

//fix DATA-1420
$xml = $nmnh->remove_data_object_of_certain_element_value("mimeType", "image/x-adobe-dng", $xml); echo "\n --- remove_data_object_of_certain_element_value() DONE\n";

$nmnh->save_resource_document($xml); echo "\n --- save_resource_document() DONE\n";
// $nmnh->call_xml_2_dwca($resource_id, "NMNH XML files"); echo "\n --- call_xml_2_dwca() DONE\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

function remove_location_tag($xml) //works OK for small XML files but not for NMNH Botany XML which is too big for memory. We need to wait for them until they fix their XML. Make it valid XML.
{
    /* 1st option works only for small XML size
    if(preg_match_all("/<location>(.*?)<\/location>/ims", $xml, $arr)) {
        $i = 0;
        foreach($arr[1] as $str) {
            $i++; echo " $i ";
            $xml = str_replace("<location>$str</location>", "", $xml, 1);
        }
    }
    */

    /* still doesn't work for big XML, memory consumed
    if(preg_match_all("/<location>(.*?)<\/location>/ims", $xml, $arr)) {
        foreach($arr[1] as $str) {
            $index = "<location>$str</location>";
            $replacements[$index] = '';
        }
        $xml = str_replace(array_keys($replacements), array_values($replacements), $xml);
    }
    */
    
    // another option is to get chunks of the XML, do the str_replace for every chunk and save it cumulatively in a separate temp XML.
    // will do it this way if needed or if partner won't fix their XML's invalid chars.

    return $xml;
}

function remove_blank_taxon_entry($xml)
{
    $xml = preg_replace('/\s*(<[^>]*>)\s*/','$1',$xml); // remove whitespaces
    $xml = str_ireplace(array("<taxon></taxon>", "<taxon/>"), "", $xml);
    return $xml;
}

function replace_Indet_sp($xml_string)
{
    if(!is_numeric(stripos($xml_string, "Indet"))) return $xml_string;
    echo "\n\n this resource has 'Indet.' taxa \n";
    // $xml = simplexml_load_string($xml_string);
    $xml = simplexml_load_string($xml_string, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
    $i = 0;
    foreach($xml->taxon as $taxon)
    {
        $i++;
        $dc = $taxon->children("http://purl.org/dc/elements/1.1/");
        $dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");
        $dcterms = $taxon->children("http://purl.org/dc/terms/");
        echo "\n " . $dc->identifier . " -- sciname: [" . $dwc->ScientificName."]";
        if(is_numeric(stripos($dwc->ScientificName, "Indet")) ||
           is_numeric(stripos($dwc->Kingdom, "Indet")) ||
           is_numeric(stripos($dwc->Phylum, "Indet")) ||
           is_numeric(stripos($dwc->Class, "Indet")) ||
           is_numeric(stripos($dwc->Order, "Indet")) ||
           is_numeric(stripos($dwc->Family, "Indet")) ||
           is_numeric(stripos($dwc->Genus, "Indet"))
        )
        {
            if(isset($dwc->Genus)) $ancestry['Genus'] = (string) $dwc->Genus;
            if(isset($dwc->Family)) $ancestry['Family'] = (string) $dwc->Family;
            if(isset($dwc->Order)) $ancestry['Order'] = (string) $dwc->Order;
            if(isset($dwc->Class)) $ancestry['Class'] = (string) $dwc->Class;
            if(isset($dwc->Phylum)) $ancestry['Phylum'] = (string) $dwc->Phylum;
            if(isset($dwc->Kingdom)) $ancestry['Kingdom'] = (string) $dwc->Kingdom;
            $ancestry['ScientificName'] = (string) $dwc->ScientificName;

            $ancestry = get_names($ancestry);
            echo "\n final sciname: [" . $ancestry['ScientificName'] . "]";

            $dwc->ScientificName = $ancestry['ScientificName'];
            if(isset($dwc->Genus)) $dwc->Genus = $ancestry['Genus'];
            if(isset($dwc->Family)) $dwc->Family = $ancestry['Family'];
            if(isset($dwc->Order)) $dwc->Order = $ancestry['Order'];
            if(isset($dwc->Class)) $dwc->Class = $ancestry['Class'];
            if(isset($dwc->Phylum)) $dwc->Phylum = $ancestry['Phylum'];
            if(isset($dwc->Kingdom)) $dwc->Kingdom = $ancestry['Kingdom'];
            if(!$ancestry['ScientificName'])
            {
                echo "\n deleted identifier: [" . $dc->identifier . "] \n";
                unset($dc->identifier);
                unset($dc->source);
                unset($dwc->Kingdom);
                unset($dwc->Phylum);
                unset($dwc->Class);
                unset($dwc->Order);
                unset($dwc->Family);
                unset($dwc->Genus);
                unset($dwc->ScientificName);
                unset($xml->taxon[$i-1]->commonName);
                unset($xml->taxon[$i-1]->synonym);
                unset($dcterms->created);
                unset($dcterms->modified);
                unset($xml->taxon[$i-1]->reference);
                unset($xml->taxon[$i-1]->dataObject);
            }
        }
    } //end foreach
    return $xml->asXML();
}

function get_names($ancestry)
{
    // first loop is to remove all Indet taxon entries
    foreach($ancestry as $rank => $name) {
        if(is_numeric(stripos($name, "Indet"))) {
            $ancestry[$rank] = "";
            echo "\n $rank has [$name] now removed.";
        }
    }

    // if ScientificName is blank, then it will get the immediate higher taxon if it exists
    if($ancestry['ScientificName'] == "") {
        foreach($ancestry as $rank => $name)
        {
            if(trim($name) != "") {
                echo "\n This will be the new ScientificName: [$name] \n";
                $ancestry['ScientificName'] = $name;
                $ancestry[$rank] = "";
                return $ancestry;
            }
        }
    }
    return $ancestry;
}

?>