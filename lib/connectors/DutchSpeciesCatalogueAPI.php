<?php
namespace php_active_record;
/* connector: 68 
Partner provided 2 services. First, is a list of all their taxa with IDs.
And the 2nd is a service that generates the individual EOL XML using a taxon ID.
*/
define("DUTCH_SPECIES_LIST", "http://www.nederlandsesoorten.nl/eol/EolList.xml");
define("TAXON_SERVICE", "http://www.nederlandsesoorten.nl/get?site=nlsr&view=nlsr&page_alias=conceptcard&version=EOL&cid=");

class DutchSpeciesCatalogueAPI
{
    function combine_all_xmls($resource_id)
    {
        if(!$species_urls = self::get_species_urls()) return;
        debug("\n\n Start compiling all XML...");
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        if(!($OUT = fopen($old_resource_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $old_resource_path);
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
        $i = 0;
        $total = sizeof($species_urls);
        foreach($species_urls as $filename)
        {
            $i++;
            print "\n $i of $total";
            sleep(2);
            $contents = Functions::get_remote_file($filename);
            if($xml = simplexml_load_string($contents))
            {
                $contents = str_ireplace("http://creativecommons.org/licenses/by-nc-sa/2.5/mx/", "http://creativecommons.org/licenses/by-nc-sa/2.5/", $contents);
                if($contents)
                {
                    $pos1 = stripos($contents, "<taxon>");
                    $pos2 = stripos($contents, "</response>");
                    $str  = substr($contents, $pos1, $pos2-$pos1);
                    fwrite($OUT, $str);
                }
            }
            else
            {
                print "\n $filename - invalid XML";
                continue;
            }
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        print "\n All XML compiled\n -end-of-process- \n";
    }

    function get_species_urls()
    {
        $species_urls = array();
        if($xml = Functions::get_hashed_response(DUTCH_SPECIES_LIST))
        {
            foreach($xml->id as $id)
            {
                print "\n $id";
                $pos = stripos($id, "/");
                $id = trim(substr($id, $pos+1, strlen($id)));
                print "\n $id";
                $species_urls[] = TAXON_SERVICE . $id;
            }
        }
        else echo "\n\n Remote XML not available.";
        return $species_urls;
    }
}
?>