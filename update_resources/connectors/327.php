<?php
namespace php_active_record;
/* connector for Flora of Zimbabwe
estimated execution time: 10 minutes
Partner provides 4 EOL resource XML files. The connector just combines all and generates the final resource file.
Also adds <rank> element for names that were entered twice; as dwc:ScientificName and as a higher-level taxon name e.g. genus, family
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 327;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";

$temp_family_path = CONTENT_RESOURCE_LOCAL_PATH . "flora_of_zimbabwe_family_taxa.xml";
$temp_genera_path = CONTENT_RESOURCE_LOCAL_PATH . "flora_of_zimbabwe_genera_taxa.xml";
add_rank_element("http://zimbabweflora.co.zw/speciesdata/utilities/eol_families.xml", $temp_family_path, "family");
add_rank_element("http://zimbabweflora.co.zw/speciesdata/utilities/eol_genera.xml", $temp_genera_path, "genus");

//debug
// add_rank_element("http://localhost/~eolit/eol_php_code/applications/content_server/resources/eol_families.xml", $temp_family_path, "family");
// add_rank_element("http://localhost/~eolit/eol_php_code/applications/content_server/resources/eol_genera.xml", $temp_genera_path, "genus");

$files = array();
$files[] = $temp_family_path;
$files[] = $temp_genera_path;
$files[] = "http://zimbabweflora.co.zw/speciesdata/utilities/eol_species1.xml";
$files[] = "http://zimbabweflora.co.zw/speciesdata/utilities/eol_species2.xml";

combine_remote_eol_resource_files($resource_id, $files);
unlink($temp_family_path);
unlink($temp_genera_path);

$xml = Functions::get_remote_file($resource_path);
$xml = fix_higher_level_names_entered_twice($xml);

$OUT = fopen($resource_path, "w");
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
exit("\n\n Done processing.");



function add_rank_element($source, $destination, $rank)
{
    $xml = Functions::get_remote_file($source, DOWNLOAD_WAIT_TIME, 0);
    $xml = str_ireplace("</dwc:ScientificName>", "</dwc:ScientificName><rank>" . $rank . "</rank>", $xml);
    $OUT = fopen($destination, "w");
    fwrite($OUT, $xml);
    fclose($OUT);
}

function fix_higher_level_names_entered_twice($xml_string)
{
    $xml = simplexml_load_string($xml_string);
    foreach($xml->taxon as $taxon)
    {
        $t_dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");                         
        $family         = trim($t_dwc->Family);
        $genus          = trim($t_dwc->Genus);
        $scientificname = trim($t_dwc->ScientificName);
        if($family == $scientificname)
        {
            print "\n same family -- f[$family] g[$genus] sn[$scientificname]";
            unset($t_dwc->Family);
        }
        elseif($genus == $scientificname)
        {
            print "\n same genus -- f[$family] g[$genus] sn[$scientificname]";
            unset($t_dwc->Genus);
        }
    }
    return $xml->asXML();
}

function combine_remote_eol_resource_files($resource_id, $files)
{
    debug("\n\n Start compiling all XML...");
    $OUT = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", "w");
    $str = "<?xml version='1.0' encoding='utf-8' ?>\n";
    $str .= "<response\n";
    $str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
    $str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
    $str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
    $str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
    $str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
    $str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
    $str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
    $str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
    fwrite($OUT, $str);
    foreach($files as $filename)
    {
        print "\n $filename ";
        $contents = Functions::get_remote_file($filename, DOWNLOAD_WAIT_TIME, 0);
        if($contents != "")
        {
            $pos1 = stripos($contents, "<taxon>");
            $pos2 = stripos($contents, "</response>");
            $str  = substr($contents, $pos1, $pos2-$pos1);
            if($pos1) fwrite($OUT, $str);
        }
        else print "\n no contents [$filename]";
    }
    fwrite($OUT, "</response>");
    fclose($OUT);
    print"\n All XML compiled\n\n";
}

?>