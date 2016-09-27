<?php
namespace php_active_record;
/* connector for Flora of Zimbabwe
estimated execution time: 10 minutes
Partner provides 4 EOL resource XML files. The connector just combines all and generates the final resource file.
Also adds <rank> element for names that were entered twice; as dwc:ScientificName and as a higher-level taxon name e.g. genus, family
*/

/*
as of Feb 27, 2013
There are 7 instances where an image dataObject id is repeated (used twice), but it has the same <mediaURL> so I didn't bother removing it.
    warning: has duplicates do_id: [102420-3.jpg][Asplenium blastophorum Hieron.]
    warning: has duplicates do_id: [126760-1.jpg][Afzelia quanzensis Welw.]
    warning: has duplicates do_id: [126760-2.jpg][Afzelia quanzensis Welw.]
    warning: has duplicates do_id: [126760-3.jpg][Afzelia quanzensis Welw.]
    warning: has duplicates do_id: [150140-1.jpg][Platostoma strictum (Hiern) A.J. Paton]
    warning: has duplicates do_id: [150140-2.jpg][Platostoma strictum (Hiern) A.J. Paton]
    warning: has duplicates do_id: [150140-3.jpg][Platostoma strictum (Hiern) A.J. Paton]

                            8Dec2014
taxon:      11082   11435   11435
reference:  4659    7738    7738
synonym:    5194    6128    6128
commonName: 4574    5386    5386
texts:      28110   30028   30028
images:     12129   17938   17938

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 327;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$download_options = array('timeout' => 1200, 'download_attempts' => 5);
// $download_options["expire_seconds"] = 0; // 0 -> expires now, false -> never expires

$temp_family_path = CONTENT_RESOURCE_LOCAL_PATH . "flora_of_zimbabwe_family_taxa.xml";
$temp_genera_path = CONTENT_RESOURCE_LOCAL_PATH . "flora_of_zimbabwe_genera_taxa.xml";
add_rank_element("http://zimbabweflora.co.zw/speciesdata/utilities/eol_families.xml", $temp_family_path, "family", $download_options);
add_rank_element("http://zimbabweflora.co.zw/speciesdata/utilities/eol_genera.xml", $temp_genera_path, "genus", $download_options);

//debug
// add_rank_element("http://localhost/~eolit/eol_php_code/applications/content_server/resources/eol_families.xml", $temp_family_path, "family");
// add_rank_element("http://localhost/~eolit/eol_php_code/applications/content_server/resources/eol_genera.xml", $temp_genera_path, "genus");

$files = array();
$files[] = $temp_family_path;
$files[] = $temp_genera_path;
$files[] = "http://zimbabweflora.co.zw/speciesdata/utilities/eol_species1.xml";
$files[] = "http://zimbabweflora.co.zw/speciesdata/utilities/eol_species2.xml";

combine_remote_eol_resource_files($resource_id, $files, $download_options);
unlink($temp_family_path);
unlink($temp_genera_path);

$xml = Functions::lookup_with_cache($resource_path, $download_options);
$xml = fix_higher_level_names_entered_twice($xml); // this also fixes duplicate taxon and dataObject identifiers

echo "\n\n has duplicate identifiers: " . check_for_duplicate_identifiers($xml) . "\n";

if(!($OUT = fopen($resource_path, "w")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
  return;
}
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_harvest_requested($resource_id);
Functions::gzip_resource_xml($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "\n\n Done processing.";



function add_rank_element($source, $destination, $rank, $download_options)
{
    $xml = Functions::lookup_with_cache($source, $download_options);
    $xml = str_ireplace("</dwc:ScientificName>", "</dwc:ScientificName><rank>" . $rank . "</rank>", $xml);
    if(!($OUT = fopen($destination, "w")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $destination);
      return;
    }
    fwrite($OUT, $xml);
    fclose($OUT);
}

function fix_higher_level_names_entered_twice($xml_string) // fixes duplicate object identifiers with different dataObject info
{
    $taxon_ids = array();
    $xml = simplexml_load_string($xml_string);
    foreach($xml->taxon as $taxon)
    {
        $t_dc = $taxon->children("http://purl.org/dc/elements/1.1/");
        $t_dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");
        $family         = trim($t_dwc->Family);
        $genus          = trim($t_dwc->Genus);
        $scientificname = trim($t_dwc->ScientificName);
        $taxon_identifier = trim($t_dc->identifier);

        // fix duplicate identifiers
        $has_duplicates = false;
        if(in_array($taxon_identifier, $taxon_ids))
        {
            // echo "\n\n has duplicates: [$taxon_identifier][$scientificname]\n";
            $has_duplicates = true;
            $taxon_identifier = $taxon_identifier . "_" . str_ireplace(" ", "_", $scientificname);
            $t_dc->identifier = $taxon_identifier;
        }
        else $taxon_ids[] = $taxon_identifier;
        //end

        if($family == $scientificname)
        {
            // echo "\n same family -- f[$family] g[$genus] sn[$scientificname]";
            unset($t_dwc->Family);
        }
        elseif($genus == $scientificname)
        {
            // echo "\n same genus -- f[$family] g[$genus] sn[$scientificname]";
            unset($t_dwc->Genus);
        }
        if($has_duplicates)
        {
            foreach($taxon->dataObject as $do)
            {
                $t_dc2 = $do->children("http://purl.org/dc/elements/1.1/");            
                $t_dc2->identifier = trim($t_dc2->identifier) . "_" . str_ireplace(" ", "_", $scientificname);
            }
        }

    }
    return $xml->asXML();
}

function check_for_duplicate_identifiers($xml_string)
{
    $taxon_ids = array();
    $do_ids = array();
    $xml = simplexml_load_string($xml_string);
    foreach($xml->taxon as $taxon)
    {
        $t_dc = $taxon->children("http://purl.org/dc/elements/1.1/");
        $t_dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");
        $scientificname = trim($t_dwc->ScientificName);
        
        $taxon_identifier = trim($t_dc->identifier);
        if(in_array($taxon_identifier, $taxon_ids))
        {
            echo "\n\n warning: has duplicate taxon_id: [$taxon_identifier][$scientificname]\n";
        }
        else $taxon_ids[] = $taxon_identifier;
        foreach($taxon->dataObject as $do)
        {
            $t_dc2 = $do->children("http://purl.org/dc/elements/1.1/");            
            $do_identifier = trim($t_dc2->identifier);
            if(in_array($do_identifier, $do_ids))
            {
                echo "\n\n warning: has duplicates do_id: [$do_identifier][$scientificname]\n";
            }
            else $do_ids[] = $do_identifier;
        }
    }
    if($taxon_ids || $do_ids) return true;
    else return false;
}

function combine_remote_eol_resource_files($resource_id, $files, $download_options)
{
    debug("\n\n Start compiling all XML...");
    if(!($OUT = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", "w")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");
      return;
    }
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
        echo "\n $filename ";
        $contents = Functions::lookup_with_cache($filename, $download_options);
        if($contents != "")
        {
            $pos1 = stripos($contents, "<taxon>");
            $pos2 = stripos($contents, "</response>");
            $str  = substr($contents, $pos1, $pos2-$pos1);
            if($pos1) fwrite($OUT, $str);
        }
        else echo "\n no contents [$filename]";
    }
    fwrite($OUT, "</response>");
    fclose($OUT);
    echo "\n All XML compiled\n\n";
}

?>