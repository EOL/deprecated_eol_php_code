<?php
namespace php_active_record;
define("CONABIO_SPECIES_LIST", "http://conabioweb.conabio.gob.mx/xmleol/EolList.xml");
class ConabioAPI
{
    function combine_all_xmls($resource_id)
    {
        if(!$species_urls = self::get_species_urls()) return;
        print "\n\n Start compiling all XML...\n";
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        $OUT = fopen($old_resource_path, "w+");
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
            if(!Functions::get_hashed_response($filename))
            {
                print "\n $filename - invalid XML";
                continue;
            }
            $contents = Functions::get_remote_file($filename);
            $contents = str_ireplace("http://creativecommons.org/licenses/by-nc-sa/2.5/mx/", "http://creativecommons.org/licenses/by-nc-sa/2.5/", $contents);
            if($contents)
            {
                $pos1 = stripos($contents, "<taxon>");
                $pos2 = stripos($contents, "</response>");
                $str  = substr($contents, $pos1, $pos2-$pos1);
                fwrite($OUT, $str);
            }
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        print "\n All XML compiled\n -end-of-process- \n";
    }

    function get_species_urls()
    {
        $species_urls = array();
        if($xml = Functions::get_hashed_response(CONABIO_SPECIES_LIST))
        {
            foreach($xml->id as $url)
            {
                print "\n $url";
                $species_urls[] = $url;
            }
        }
        return $species_urls;
    }
}
?>