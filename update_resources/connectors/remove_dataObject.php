<?php
namespace php_active_record;
/*
for reference use connectors: 346, 343, etc... those with NMNH 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

eol_xml_stats();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

function eol_xml_stats()
{
    $path = "http://localhost/eol_php_code/applications/content_server/resources/218.xml";
    $reader = new \XMLReader();
    $reader->open($path);
    $i = 0;
    $dist_count = 0;
    $taxa_count = 0;
    while(@$reader->read())
    {
        if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon")
        {
            $string = $reader->readOuterXML();
            $string = str_ireplace("dc:", "dc_", $string);
            $string = str_ireplace("dwc:", "dwc_", $string);
            
            if($xml = simplexml_load_string($string))
            {
                $taxa_with_dist = false;
                $taxon_id = (string) $xml->dc_identifier;
                print "[$taxon_id]";
                foreach($xml->dataObject as $o)
                {
                    if(@$o->subject == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution")
                    {
                        $dist_count++;
                        $taxa_with_dist = true;
                    }
                }
                if($taxa_with_dist) $taxa_count++;
            }
            
        }
    }
    print "\n\n";
    print "\n distribution: [$dist_count]";
    print "\n taxa with dist: [" . $taxa_count . "]";
    print "\n\n";
}

function remove_dataObject()
{
    require_library('ResourceDataObjectElementsSetting');
    $resource_id = 346; 
    $resource_path = "http://localhost/eol_php_code/applications/content_server/resources/346.xml.gz";
    $nmnh = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
    $xml = $nmnh->load_xml_string();
    $xml = $nmnh->remove_data_object_of_certain_element_value("mimeType", "image/x-adobe-dng", $xml);
    $nmnh->save_resource_document($xml);
}

?>