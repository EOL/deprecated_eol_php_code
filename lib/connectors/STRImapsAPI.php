<?php
namespace php_active_record;
/* connectors: [358] 
Connector processes the original XML resource (http://eol.org/content_partners/44/resources/35) 
and checks if the map image exists. If it does, it generates a map dataObject for the Maps tab.
*/
class STRImapsAPI
{
    public function __construct()
    {
        $this->map_url = 'http://biogeodb.stri.si.edu/sftep/images/automaps/smapxxx.png';
        $this->taxon_page = 'http://biogeodb.stri.si.edu/sftep/taxon_option_main.php?lvl=S&id=';
        
        $this->orig_xml = 'http://services.eol.org/resources/35.xml';
    }

    function get_all_taxa($resource_id)
    {
        // $xml = Functions::get_hashed_response(CONTENT_RESOURCE_LOCAL_PATH . "35.xml");
        $xml = Functions::get_hashed_response($this->orig_xml, array('expire_seconds' => false));
        
        $all_taxa = array();
        $i = 0;
        $total = count($xml->taxon);
        foreach($xml->taxon as $t)
        {
            $i++;
            $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
            $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
            print "\n $i of $total " . $t_dc->identifier;
            $url = str_replace('xxx', str_replace('STRI-fish-', '', $t_dc->identifier), $this->map_url);
            print " [$url] ";
            if ($file = fopen($url, "r"))
            {
                if(stripos(fgets($file), "no_website_found")) print " - no map";
                else
                {
                    print " - with map";
                    $taxon_record["taxon"] = array( "sciname"  => $t_dwc->ScientificName,
                                                    "family"   => $t_dwc->Family,
                                                    "kingdom"  => '',
                                                    "phylum"   => '',
                                                    "class"    => '',
                                                    "order"    => '',
                                                    "id"       => str_replace('STRI-fish-', '', $t_dc->identifier),
                                                    "mediaURL" => $url,
                                                    "source"   => $this->taxon_page . str_replace('STRI-fish-', '', $t_dc->identifier)
                                                  );
                    $taxon_record["dataobjects"] = array();
                    $arr = self::get_stri_taxa($taxon_record);
                    $page_taxa = $arr[0];
                    if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                    unset($page_taxa);
                }
            }else{
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $url);
            }
        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        if(!($OUT = fopen($resource_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
          return;
        }
        fwrite($OUT, $xml);
        fclose($OUT);
    }

    public static function get_stri_taxa($taxon_record)
    {
        $response = self::parse_xml($taxon_record);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
        }
        return array($page_taxa);
    }

    private function parse_xml($taxon_record)
    {
        $taxon_entry = array();
        $agent = array();
        $agent[] = array("role" => "compiler", "homepage" => "http://www.neotropicalfishes.org/sftep/index.php", "logoURL" => "http://www.neotropicalfishes.org/sftep/images/logo1.gif", "fullName" => "D. Ross Robertson");
        $arr_objects[] = array(   "identifier"   => @$taxon_record['taxon']["id"] . "_map",
                                  "dataType"     => 'http://purl.org/dc/dcmitype/StillImage',
                                  "mimeType"     => 'image/png',
                                  "title"        => "Distribution of " . $taxon_record['taxon']['sciname'] . " in the Tropical Eastern Pacific",
                                  "source"       => $taxon_record['taxon']['source'],
                                  "description"  => "Distribution of " . $taxon_record['taxon']['sciname'] . " in the Tropical Eastern Pacific",
                                  "mediaURL"     => $taxon_record['taxon']["mediaURL"],
                                  "agent"        => $agent,
                                  "license"      => "http://creativecommons.org/licenses/by-nc/3.0/",
                                  "location"     => '',
                                  "rightsHolder" => "Shorefishes of the tropical eastern Pacific online information system. www.stri.org/sftep",
                                  "reference"    => array(),
                                  "subject"      => '',
                                  "modified"     => '',
                                  "created"      => '',
                                  "language"     => 'en',
                                  "additionalInformation" => '<subtype>map</subtype>'
                              );
        $taxon_entry[] = array("identifier"   => $taxon_record['taxon']['id'],
                            "source"       => $taxon_record['taxon']['source'],
                            "kingdom"      => $taxon_record['taxon']['kingdom'],
                            "phylum"       => $taxon_record['taxon']['phylum'],
                            "class"        => $taxon_record['taxon']['class'],
                            "order"        => $taxon_record['taxon']['order'],
                            "family"       => $taxon_record['taxon']['family'],
                            "genus"        => "",
                            "sciname"      => $taxon_record['taxon']['sciname'],
                            "reference"    => array(),
                            "synonyms"     => array(),
                            "commonNames"  => array(),
                            "data_objects" => $arr_objects
                           );
        return $taxon_entry;
    }

}
?>