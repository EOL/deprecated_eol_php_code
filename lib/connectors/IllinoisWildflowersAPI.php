<?php
namespace php_active_record;
/* connector: [34]
http://www.illinoiswildflowers.info/
*/
class IllinoisWildflowersAPI
{
    /* numbers from last connector run:
    taxon = 1243
    dwc:ScientificName = 1243
    taxon reference = 0
    synonym = 0
    commonName = 1244
    
    DataObjects = 9551 - 9709
    reference = 9551 - 9709
    
    DataObjects breakdown
    texts = 5801 - 5959
    images = 3750
    videos = 0
    sounds = 0
    
    SPM breakdown
    GeneralDescription = 1914 - 2072
    Uses = 884
    Distribution = 1058
    Habitat = 1058
    Associations = 887
    */
    public function __construct()
    {
        $this->path = 'http://www.illinoiswildflowers.info/';
        $this->urls = array();
        $this->urls[] = array("active" => 1, "type" => "prairie",  "file" => "prairie/plant_index.htm");
        $this->urls[] = array("active" => 1, "type" => "savanna",  "file" => "savanna/savanna_index.htm");
        $this->urls[] = array("active" => 1, "type" => "wetland",  "file" => "wetland/wetland_index.htm");
        $this->urls[] = array("active" => 1, "type" => "woodland", "file" => "woodland/woodland_index.htm");
        $this->urls[] = array("active" => 1, "type" => "weeds",    "file" => "weeds/weed_index.htm");
        $this->urls[] = array("active" => 1, "type" => "grasses",  "file" => "grasses/grass_index.htm");
        $this->urls[] = array("active" => 1, "type" => "trees",    "file" => "trees/tree_index.htm");
    }

    function get_all_taxa($resource_id)
    {
        self::get_associations();
        echo "\n\n total: " . count($GLOBALS['taxon']) . "\n";
        $all_taxa = array();
        $i = 0;
        $total = count(array_keys($GLOBALS['taxon']));
        foreach($GLOBALS['taxon'] as $taxon_name => $record)
        {
            $i++; echo "\n$i of $total " . $taxon_name;
            $record["taxon_name"] = $taxon_name;
            $arr = self::get_visitors_taxa($record);
            $page_taxa = $arr[0];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        if(!($OUT = fopen($resource_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $resource_path);
          return;
        }
        fwrite($OUT, $xml);
        fclose($OUT);
    }

    function get_associations()
    {
        $i = 0;
        foreach($this->urls as $path)
        {
            if($path["active"])
            {
                echo "\n\n$i " . $path['type'] . " " . $path['file'] . "\n";
                $url = $this->path . $path['file'];
                self::process_insects($url, $path['type']);
            }
            $i++;
        }
    }

    function process_insects($url, $type)
    {
        if(!$html = Functions::lookup_with_cache($url, array('timeout' => 1200, 'download_attempts' => 5))) // 20mins timeout, 5 attempts
        {
            echo("\n\n Content partner's server is down, $url\n");
            return;
        }
        /* <a href="plantx/pf_foxglovex.htm">Agalinis purpurea (Purple False Foxglove)</a> */
        
        if($type == 'prairie') $key_term = 'plantx';
        else                   $key_term = 'plants';
        
        if(preg_match_all("/href=\"$key_term(.*?)<\/a>/ims", $html, $matches))
        {
            $i = 0;
            foreach($matches[1] as $match)
            {
                /*/purs_spdwell.htm" name="purs_spdwell">Veronica peregrina (Purslane Speedwell)*/
                if(preg_match("/>(.*?)\(/ims", $match, $string_match)) $taxon_name = strip_tags(self::clean_str($string_match[1]));
                else continue;
                $taxon_name = utf8_encode($taxon_name);
                echo "\n[$taxon_name]\n";
                if(preg_match("/\/(.*?)\"/ims", $match, $string_match)) $html = self::clean_str($string_match[1]);
                if(preg_match("/\((.*?)\)/ims", $match, $string_match)) 
                {
                    $common_name = self::clean_str($string_match[1]);
                    $GLOBALS['taxon'][$taxon_name]['comnames'][] = $common_name;
                }
                if($type == 'prairie') $html = $type . "/plantx/$html";
                else                   $html = $type . "/plants/$html";
                echo "\nhtml: [$html]";
                $GLOBALS['taxon'][$taxon_name]['html'] = $html;
                // $i++; if($i >= 5) break; //debug
            }
        }
        self::get_title_description($type);
    }

    function get_title_description($type = null)
    {
        foreach($GLOBALS['taxon'] as $taxon_name => $value)
        {
            // if($taxon_name != "Agalinis purpurea") continue; //debug
            // if (!in_array($taxon_name, array('Acer rubrum', 'Acer nigrum'))) continue; //debug

            if(@$value['Description'] != "" || 
               @$value['Cultivation'] != "" ||
               @$value['Range &amp; Habitat'] != "" ||
               @$value['Faunal Associations'] != "" ||
               @$value['Photographic Location'] != "" ||
               @$value['Comments'] != ""
               ) continue;

            $url = $this->path . $value['html'];
            $GLOBALS['taxon'][$taxon_name]['html'] = $url;

            echo "\n $url -- $taxon_name";
            if(!$html = Functions::lookup_with_cache($url, array('timeout' => 1200, 'download_attempts' => 5)))
            {
                echo("\n\n Content partner's server is down, $url\n");
                $GLOBALS['taxon'][$taxon_name]['Description'] = 'no objects';
                continue;
            }
            $html = self::clean_str($html);
            self::get_family($html, $taxon_name);
            if(preg_match_all("/<BLOCKQUOTE>(.*?)<\/BLOCKQUOTE>/ims", $html, $matchez)) // this can only be just preg_match not preg_match_all
            {
                foreach($matchez[1] as $matchz)
                {
                    $desc = self::clean_str($matchz) . '<br>'; // added '<br>' to get the last text block
                    self::get_images($desc, $type, $taxon_name);
                    if(preg_match_all("/<font color=\"#33cc33\">(.*?)<br>/ims", $desc, $matches))
                    {
                        $texts = $matches[1];
                        foreach($texts as $text)
                        {
                            $temp = strip_tags($text, "<br><i>");
                            $temp = explode(":", $temp);
                            if($temp) $GLOBALS['taxon'][$taxon_name][@$temp[0]] = @$temp[1]; //placed @ bec sometimes there are extra ':' in the text.
                        }
                    }
                    else
                    {
                        $GLOBALS['taxon'][$taxon_name]['Description'] = 'no objects';
                        echo "\n investigate: (no objects) $type - $taxon_name \n";
                        continue;
                    }
                }
            }
            else // webpage might have changed. scraping script has to be updated.
            {
                $GLOBALS['taxon'][$taxon_name]['Description'] = 'no objects';
                echo "\n investigate: (no <BLOCKQUOTE>) $type - $taxon_name \n";
                self::scrape_second_try($html, $taxon_name, $type);
            }

        }
    }

    function scrape_second_try($html, $taxon_name, $type)
    {
        $html = self::clean_str($html);
        self::get_images($html, $type, $taxon_name);
        $html = str_ireplace('<span style="font-weight: bold; color: rgb(51, 204, 51);"><br>', 'zzz xxxyyy', $html);
        $html = str_ireplace('<span style="font-weight: bold; color: rgb(51, 204, 51); font-family: Times New Roman;">', 'zzz xxxyyy', $html);
        $html = str_ireplace('<span style="font-weight: bold; color: rgb(51, 204, 51);">', 'zzz xxxyyy', $html);
        $html = str_ireplace('<span style="font-weight: bold; color: rgb(51, 204, 0); font-family: Times New Roman;">', 'zzz xxxyyy', $html);
        $html = str_ireplace('<span style="font-weight: bold; color: rgb(51, 204, 0);">', 'zzz xxxyyy', $html);
        $html = str_ireplace('<span style="color: rgb(51, 204, 0); font-weight: bold;">', 'zzz xxxyyy', $html);
        $html = str_ireplace('<span style="font-family: Times New Roman;">Return</span>', 'zzz xxxyyy', $html);
        if(preg_match_all("/xxxyyy(.*?)zzz/ims", $html, $matches))
        {
            echo "\n 2nd-try successful - $type - $taxon_name \n";
            $i = 0;
            $texts = $matches[1];
            foreach($texts as $text)
            {
                $texts[$i] = trim(strip_tags($text, "<i>"));
                $i++;
            }
            // build-up paragraphs
            foreach($texts as $text)
            {
                $temp = explode(":", $text);
                if($temp) $GLOBALS['taxon'][$taxon_name][@$temp[0]] = @$temp[1]; //placed @ bec sometimes there are extra ':' in the text.
            }
        }
        else
        {
            $GLOBALS['taxon'][$taxon_name]['Description'] = 'no objects';
            echo "\n investigate: ALERT: 2nd-try failed $type - $taxon_name \n";
        }
    }

    function get_family($html, $taxon_name)
    {
        /* Mallow family (Mallow family) */
        if(stripos($html, '(Mallow family)')) $GLOBALS['taxon'][$taxon_name]['family'] = 'Malvaceae'; //hard-coded

        /* Figwort family (Scrophulariaceae) | Grass family (Poaceae) */
        if(!@$GLOBALS['taxon'][$taxon_name]['family'])
        {
            if(preg_match("/family \((.*?)\)/ims", $html, $match)) $GLOBALS['taxon'][$taxon_name]['family'] = $match[1];
        }

        /* <BR>Pteridaceae (Brake family) */
        if(!@$GLOBALS['taxon'][$taxon_name]['family'])
        {
            if(stripos($html, '(Brake family)')) $GLOBALS['taxon'][$taxon_name]['family'] = 'Pteridaceae';
        }
    }

    function get_images($html, $type, $taxon_name)
    {
        if(preg_match_all("/<img(.*?)>/ims", $html, $matches))
        {
            foreach($matches[1] as $str)
            {
                if(preg_match("/src=\"(.*?)\"/ims", $str, $match)) 
                {
                    $img = str_replace('..', '', $this->path . $type . $match[1]);
                    $GLOBALS['taxon'][$taxon_name]['images'][] = $img;
                }                
            }
        }
    }
    
    public static function get_visitors_taxa($taxon_record)
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

    function parse_xml($taxon_record)
    {
        $arr_data = array();
        $arr_objects = array();
        $arr_objects = self::get_objects($taxon_record, $arr_objects);
        $common_names = self::get_common_names(@$taxon_record['comnames']);
        $arr_data[] = array("identifier"   => str_replace(" ", "_", $taxon_record['taxon_name']) . "_IL_wildflower",
                            "source"       => @$taxon_record['html'],
                            "kingdom"      => 'Plantae',
                            "phylum"       => @$taxon_record['ancestry']['phylum'],
                            "class"        => @$taxon_record['ancestry']['class'],
                            "order"        => @$taxon_record['ancestry']['order'],
                            "family"       => @$taxon_record['family'],
                            "genus"        => '',
                            "sciname"      => $taxon_record['taxon_name'],
                            "reference"    => array(), // formerly taxon_refs
                            "synonyms"     => array(),
                            "commonNames"  => $common_names,
                            "data_objects" => $arr_objects
                           );
        return $arr_data;
    }

    function get_objects($record, $arr_objects)
    {
        /*
        Description ---> General description
        Cultivation ---> Uses (Title: Cultivation)
        Range & Habitat ---> Distribution (Title: Range and Habitat in Illinois), Habitat (Title" Range and Habitat in Illinois)
        Faunal Associations ---> Associations
        Photographic Location ---> use as caption for any photographs appearing on page
        Comments --> General description (Title: Comments)
        Distribution map -- pull out of text.
        */

        $texts = array();
        if(@$record['Description'] && @$record['Description'] != 'no objects') $texts[] = array("desc" => $record['Description'], 
                                                                               "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription",
                                                                               "title"    => 'Description',
                                                                               "type"     => 'Description');
        if(@$record['Cultivation']) $texts[] = array("desc"     => $record['Cultivation'], 
                                                     "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses",
                                                     "title"    => 'Cultivation',
                                                     "type"     => 'Cultivation');

        if(@$record['Range &amp; Habitat'])
        {
            $texts[] = array("desc"     => $record['Range &amp; Habitat'], 
                             "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution",
                             "title"    => 'Range and Habitat in Illinois',
                             "type"     => 'Distribution');
            $texts[] = array("desc"     => $record['Range &amp; Habitat'], 
                             "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat",
                             "title"    => 'Range and Habitat in Illinois',
                             "type"     => 'Habitat');
        }
        if(@$record['Faunal Associations']) $texts[] = array("desc"     => $record['Faunal Associations'], 
                                                             "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations",
                                                             "title"    => 'Faunal Associations',
                                                             "type"     => 'Faunal Associations');
        if(@$record['Comments']) $texts[] = array("desc"    => $record['Comments'], 
                                                  "subject" => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription",
                                                  "title"   => 'Comments',
                                                  "type"    => 'Comments');

        foreach($texts as $text)
        {
            $agent = array();
            $agent[] = array("role" => 'source', "homepage" => 'http://www.illinoiswildflowers.info/index.htm', "fullName" => 'John Hilty');
            $refs = self::get_references();
            $identifier     = str_replace(" ", "_", $record['taxon_name'] . "_IL_wildflower_" . $text['type']);
            $description    = utf8_encode($text['desc']);
            $license        = "http://creativecommons.org/licenses/by-nc/3.0/";
            $agent          = $agent;
            $rightsHolder   = "John Hilty";
            $rights         = "Copyright © 2002-" . date("Y") . " by Dr. John Hilty";
            $location       = '';
            $dataType       = "http://purl.org/dc/dcmitype/Text";
            $mimeType       = "text/html";
            $title          = $text['title'];
            $subject        = $text['subject'];
            $source         = $record['html'];
            $mediaURL       = '';
            $refs           = $refs;
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, '', $arr_objects);
        }
        
        if(!@$record['images']) return $arr_objects;
        foreach(@$record['images'] as $image)
        {
            $additionalInformation = '';
            $path_parts = pathinfo($image);
            $identifier = $path_parts['basename'];
            if($path_parts['extension'] == 'jpg') 
            {
                $mimeType = 'image/jpeg';
                $title = '';
                $description = utf8_encode(@$record['Photographic Location']);
            }
            else
            {
                $mimeType = 'image/gif';
                $title = "County-level distribution of " . $record['taxon_name'];
                $description = '';
                $additionalInformation = '<subtype>map</subtype>';
            }
            $agent = array();
            $agent[] = array("role" => 'source', "homepage" => 'http://www.illinoiswildflowers.info/index.htm', "fullName" => 'John Hilty');
            $refs = self::get_references();
            $license        = "http://creativecommons.org/licenses/by-nc/3.0/";
            $agent          = $agent;
            $rightsHolder   = "John Hilty";
            $rights         = "Copyright © 2002-" . date("Y") . " by Dr. John Hilty";
            $location       = '';
            $dataType       = "http://purl.org/dc/dcmitype/StillImage";
            $subject        = '';
            $source         = $record['html'];
            $mediaURL       = $image;
            $refs           = $refs;
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $additionalInformation, $arr_objects);
        }
        return $arr_objects;
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $additionalInformation, $arr_objects)
    {
        $arr_objects[] = array("identifier"   => $identifier,
                               "dataType"     => $dataType,
                               "mimeType"     => $mimeType,
                               "title"        => $title,
                               "source"       => $source,
                               "description"  => $description,
                               "mediaURL"     => $mediaURL,
                               "agent"        => $agent,
                               "license"      => $license,
                               "location"     => $location,
                               "rights"       => $rights,
                               "rightsHolder" => $rightsHolder,
                               "reference"    => $refs,
                               "subject"      => $subject,
                               "language"     => "en",
                               "additionalInformation" => $additionalInformation
                              );
        return $arr_objects;
    }

    function get_references()
    {
        $reference = "Hilty, J. Editor. " . date("Y") . ". Illinois Wildflowers. World Wide Web electronic publication. flowervisitors.info, version " . date("m/Y") . 
        ".<br>See: <a href='http://www.illinoiswildflowers.info/files/line_drawings.htm'>Botanical Terminology and Line Drawings</a>,
                   <a href='http://www.illinoiswildflowers.info/files/ecological_terms.html'>Ecological Terminology</a>,
                   <a href='http://www.illinoiswildflowers.info/files/description.htm'>Website Description</a>,
                   <a href='http://www.illinoiswildflowers.info/files/linksx.htm'>Links to Other Websites</a>,
                   <a href='http://www.illinoiswildflowers.info/files/reference_materials.htm'>Reference Materials</a>";
        $refs = array();
        $refs[] = array("url" => '', "fullReference" => $reference);
        return $refs;
    }

    function get_common_names($names)
    {
        $arr_names = array();
        if($names)
        {
            foreach($names as $name) $arr_names[] = array("name" => $name, "language" => 'en');
        }
        return $arr_names;
    }

    function clean_str($str)
    {
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB"), " ", trim($str));
        $str = str_ireplace(array("    "), " ", trim($str));
        $str = str_ireplace(array("   "), " ", trim($str));
        $str = str_ireplace(array("  "), " ", trim($str));
        return $str;
    }

}
?>
