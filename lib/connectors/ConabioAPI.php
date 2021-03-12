<?php
namespace php_active_record;
/* connector: [100] - for Conabio
              [106] - for Tamborine Mt.
Partner provided a service to list all their taxa with a link to the individual EOL XML for each taxon.
Connector loops through the list XML and combines all individual taxon XML to generate the final EOL XML.
*/
define("CONABIO_SPECIES_LIST", "http://conabioweb.conabio.gob.mx/xmleol/EolList.xml");
define("TAMBORINE_SPECIES_LIST", "http://www.biodiversity.com.au/eol_xml/");

class ConabioAPI
{
    function __construct()
    {
        $this->download_options = array("download_wait_time" => 1000000, "timeout" => 3600, "delay_in_minutes" => 2);
        $this->download_options['expire_seconds'] = 60*60*24*30; //ideal is 1 month expiration //false - does not expire;
    }

    function combine_all_xmls($resource_id)
    {
        if($resource_id == 100) $species_urls = self::get_CONABIO_species_urls();
        if($resource_id == 106) $species_urls = self::get_Tamborine_species_urls();
        if(!$species_urls) return;
        debug("\n\n Start compiling all XML...");
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        if(!($OUT = Functions::file_open($old_resource_path, "w+"))) return;
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
            if(($i % 500) == 0) print "\n $i of $total ";
            if($contents = Functions::lookup_with_cache($filename, $this->download_options))
            {
                // manual adjustments
                $contents = str_ireplace("text/plain", "text/html", $contents); //for Conabio (resource_id = 100)
                if($resource_id == 106) $contents = str_ireplace(array("*"), "", $contents); // tamborine mt.
                if($resource_id == 100) $contents = str_ireplace("http://creativecommons.org/licenses/by-nc-sa/2.5/mx/", "http://creativecommons.org/licenses/by-nc-sa/2.5/", $contents); // conabio.

                if($xml = simplexml_load_string($contents))
                {
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
                    print "\n\n [$filename] - invalid XML \n\n";
                    continue;
                }
            }
            // if($i >= 5) break; //debug
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        print "\n All XML compiled\n -end-of-process- \n";
    }

    private function get_Tamborine_species_urls()
    {
        $species_urls = array();
        if($contents = Functions::lookup_with_cache(TAMBORINE_SPECIES_LIST . "list.xml", $this->download_options))
        {
            if($xml = simplexml_load_string($contents))
            {
                foreach($xml->files->file as $file) $species_urls[] = TAMBORINE_SPECIES_LIST . $file;
            }
        }
        return $species_urls;
    }

    private function get_CONABIO_species_urls()
    {
        $species_urls = array();
        if($contents = Functions::lookup_with_cache(CONABIO_SPECIES_LIST, $this->download_options))
        {
            if($xml = simplexml_load_string($contents))
            {
                foreach($xml->id as $url) $species_urls[] = $url;
            }
        }
        return $species_urls;
    }
}
?>