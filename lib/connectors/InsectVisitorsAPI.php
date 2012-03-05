<?php
namespace php_active_record;
/* connector: [143]  
http://www.illinoiswildflowers.info/
http://www.illinoiswildflowers.info/flower_insects/index.htm
*/
class InsectVisitorsAPI
{
    public function __construct()
    {
        $this->path = 'http://www.illinoiswildflowers.info/flower_insects';
        $this->urls = array();
        $this->urls[] = array("active" => 1, "type" => "insects", "ancestry" => array("kingdom" => "Plantae", "phylum" => "", "class" => "", "order" => "", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "birds", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Chordata", "class" => "Aves", "order" => "", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "bees", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "wasps", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "flies", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Diptera", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "moths", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Lepidoptera", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "beetles", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Coleoptera", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "bugs", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hemiptera", "family" => ""));
        $this->file_urls = array();
        $this->file_urls[] = array("active" => 1, "type" => "lt_bee", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "st_bee", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "wasps", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "flies", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Diptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "beetles", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Coleoptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "plant_bugs", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hemiptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "lepidoptera", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Lepidoptera", "family" => ""));
    }

    function get_all_taxa($resource_id)
    {
        self::get_associations();
        self::get_general_descriptions();
        self::prepare_common_names();
        print "\n\n total: " . count($GLOBALS['taxon']) . "\n";
        $all_taxa = array();
        $i = 0;
        $total = count(array_keys($GLOBALS['taxon']));
        foreach($GLOBALS['taxon'] as $taxon_name => $record)
        {
            $i++; print "\n$i of $total " . $taxon_name;
            $record["taxon_name"] = $taxon_name;
            $arr = self::get_visitors_taxa($record);
            $page_taxa = $arr[0];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        $OUT = fopen($resource_path, "w");
        fwrite($OUT, $xml);
        fclose($OUT);
    }

    function get_general_descriptions()
    {
        $i = 0;
        foreach($this->file_urls as $path)
        {
            $url = $this->path . '/files/' . $path["type"] . ".htm";
            if($path["active"])
            {
                print "\n\n$i" . " " . $url . "\n";        
                self::process_gen_desc($url, $path["ancestry"], $path['type']);
                $i++;
            }
        }
        if($i == 0) return;
        //special cases
        // Formicidae and Myrmicidae
        $GLOBALS['taxon']['Formicidae']['ancestry']     = $GLOBALS['taxon']['Formicidae and Myrmicidae']['ancestry'];
        $GLOBALS['taxon']['Formicidae']['gendesc']      = $GLOBALS['taxon']['Formicidae and Myrmicidae']['gendesc'];
        $GLOBALS['taxon']['Formicidae']['html']         = $GLOBALS['taxon']['Formicidae and Myrmicidae']['html'];
        $GLOBALS['taxon']['Myrmicidae']['ancestry']     = $GLOBALS['taxon']['Formicidae and Myrmicidae']['ancestry'];
        $GLOBALS['taxon']['Myrmicidae']['gendesc']      = $GLOBALS['taxon']['Formicidae and Myrmicidae']['gendesc'];
        $GLOBALS['taxon']['Myrmicidae']['html']         = $GLOBALS['taxon']['Formicidae and Myrmicidae']['html'];
        // Sciaridae, Mycetophilidae
        $GLOBALS['taxon']['Sciaridae']['ancestry']      = $GLOBALS['taxon']['Sciaridae, Mycetophilidae']['ancestry'];
        $GLOBALS['taxon']['Sciaridae']['gendesc']       = $GLOBALS['taxon']['Sciaridae, Mycetophilidae']['gendesc'];
        $GLOBALS['taxon']['Sciaridae']['html']          = $GLOBALS['taxon']['Sciaridae, Mycetophilidae']['html'];
        $GLOBALS['taxon']['Mycetophilidae']['ancestry'] = $GLOBALS['taxon']['Sciaridae, Mycetophilidae']['ancestry'];
        $GLOBALS['taxon']['Mycetophilidae']['gendesc']  = $GLOBALS['taxon']['Sciaridae, Mycetophilidae']['gendesc'];
        $GLOBALS['taxon']['Mycetophilidae']['html']     = $GLOBALS['taxon']['Sciaridae, Mycetophilidae']['html'];
        // Tephretidae, Drosophilidae
        $GLOBALS['taxon']['Tephretidae']['ancestry']    = $GLOBALS['taxon']['Tephretidae, Drosophilidae']['ancestry'];
        $GLOBALS['taxon']['Tephretidae']['gendesc']     = $GLOBALS['taxon']['Tephretidae, Drosophilidae']['gendesc'];
        $GLOBALS['taxon']['Tephretidae']['html']        = $GLOBALS['taxon']['Tephretidae, Drosophilidae']['html'];
        $GLOBALS['taxon']['Drosophilidae']['ancestry']  = $GLOBALS['taxon']['Tephretidae, Drosophilidae']['ancestry'];
        $GLOBALS['taxon']['Drosophilidae']['gendesc']   = $GLOBALS['taxon']['Tephretidae, Drosophilidae']['gendesc'];
        $GLOBALS['taxon']['Drosophilidae']['html']      = $GLOBALS['taxon']['Tephretidae, Drosophilidae']['html'];
        unset($GLOBALS['taxon']['Formicidae and Myrmicidae']);
        unset($GLOBALS['taxon']['Sciaridae, Mycetophilidae']);
        unset($GLOBALS['taxon']['Tephretidae, Drosophilidae']);
    }

    function process_gen_desc($url, $ancestry, $type)
    {
        print "\n\n file: $url \n";
        $html = Functions::get_remote_file($url);
        $html = str_ireplace("&amp;", "and", $html);
        $html = self::clean_str($html);
        if(preg_match("/<BLOCKQUOTE>(.*?)<\/BLOCKQUOTE>/ims", $html, $match))
        {
            $html = '<BR><BR>' . $match[1];
            $records = array();
            $str = str_ireplace('<BR><BR>' , '&records[]=', $html);
            parse_str($str);
            foreach($records as $rec)
            {
                $html = strip_tags($rec, "<BR><I>");
                if(preg_match("/(.*?)\(/ims", $html, $match)) 
                {
                    $taxon_name = self::clean_str($match[1]);
                    $description = self::clean_str($html);
                    $GLOBALS['taxon'][$taxon_name]['gendesc'] = $description;
                    $GLOBALS['taxon'][$taxon_name]['ancestry'] = $ancestry;
                    $GLOBALS['taxon'][$taxon_name]['html'] = $url;
                }
            }
        }
    }

    function get_associations()
    {
        $bird_type = array("birds", "bees", "wasps", "flies", "moths", "beetles", "bugs");
        $i = 0;
        foreach($this->urls as $path)
        {
            if($path['type'] == 'insects') $url = "http://www.illinoiswildflowers.info/flower_insects/index.htm";
            else                           $url = $this->path . '/insects/' . $path['type'] . ".htm";
            if($path["active"])
            {
                print "\n\n$i " . $path['type'] . " [$url]\n";        
                if($path['type'] == "insects")              self::process_insects($url, $path["ancestry"]);
                elseif(in_array($path['type'], $bird_type)) self::process_birds($url, $path["ancestry"], $path['type']);
            }
            $i++;
        }
    }

    function process_birds($url, $ancestry, $type)
    {
        $html = Functions::get_remote_file($url);
        /*HREF="birds/hummingbird.htm" NAME="hummingbird">Archilochus colubris</A><BR></B><FONT COLOR="#000000">(Ruby-Throated Hummingbird)</FONT></FONT></FONT></TD>*/
        if(preg_match_all("/href=\"$type(.*?)<\/td>/ims", $html, $matches))
        {
            $i = 0;
            foreach($matches[1] as $match)
            {
                /*/hummingbird.htm" NAME="hummingbird">Archilochus colubris</A><BR></B><FONT COLOR="#000000">(Ruby-Throated Hummingbird)</FONT></FONT></FONT>*/
                if(preg_match("/>(.*?)<\/a>/ims", $match, $string_match)) $taxon_name = self::clean_str($string_match[1]);
                if(preg_match("/\/(.*?)\"/ims", $match, $string_match)) $html = self::clean_str($string_match[1]);
                if(preg_match("/\((.*?)\)/ims", $match, $string_match)) 
                {
                    $common_name = self::clean_str($string_match[1]);
                    $GLOBALS['taxon'][$taxon_name]['comnames'][] = self::clean_str($common_name);
                }
                $GLOBALS['taxon'][$taxon_name]['html'] = "/$type/$html";
                $GLOBALS['taxon'][$taxon_name]['ancestry'] = $ancestry;
                //$i++; if($i >= 3) break; //debug
            }
        }
        self::get_title_description();
    }

    function process_insects($url, $ancestry)
    {
        $html = Functions::get_remote_file($url);
        /*<a href="plants/velvetleaf.htm" name="velvetleaf">Abutilon theophrastii (Velvet Leaf)</a>*/
        if(preg_match_all("/href=\"plants(.*?)<\/a>/ims", $html, $matches))
        {
            $i = 0;
            foreach($matches[1] as $match)
            {
                /*/purs_spdwell.htm" name="purs_spdwell">Veronica peregrina (Purslane Speedwell)*/
                if(preg_match("/>(.*?)\(/ims", $match, $string_match)) $taxon_name = self::clean_str($string_match[1]);
                if(preg_match("/\/(.*?)\"/ims", $match, $string_match)) $html = self::clean_str($string_match[1]);
                if(in_array($html, array('ill_ironweed.htm', 'hybrid_cardinal.htm'))) continue; //for Vernonia × illinoensis (Illinois Ironweed)
                if(preg_match("/\((.*?)\)/ims", $match, $string_match)) 
                {
                    $common_name = self::clean_str($string_match[1]);
                    $GLOBALS['taxon'][$taxon_name]['comnames'][] = $common_name;
                }
                $taxon_name = utf8_encode($taxon_name);
                $GLOBALS['taxon'][$taxon_name]['html'] = "/plants/$html";
                $GLOBALS['taxon'][$taxon_name]['ancestry'] = $ancestry;
                // $i++; if($i >= 3) break; //debug
            }
        }
        self::get_title_description('insects');
    }

    function get_title_description($type = null)
    {
        foreach($GLOBALS['taxon'] as $taxon_name => $value)
        {
            if(@$value['association_title'] != "") continue;

            $url = $this->path . '/insects/' . $value['html'];
            if($type == 'insects') $url = str_ireplace("/insects/", "/", $url);
            $GLOBALS['taxon'][$taxon_name]['html'] = $url;

            print "\n $url -- $taxon_name";
            $html = Functions::get_remote_file($url);
            if(preg_match("/<B>(.*?)<BLOCKQUOTE>/ims", $html, $match))
            {
                $title = strip_tags(self::clean_str($match[1]), "<BR>");
                $title = str_ireplace("<BR>", " ", $title);
                $title .= " in Illinois";
                $GLOBALS['taxon'][$taxon_name]['association_title'] = $title;
            }
            if(preg_match("/<BLOCKQUOTE>(.*?)<\/BLOCKQUOTE>/ims", $html, $match))
            {
                $desc = strip_tags(self::clean_str($match[1]), "<BR><I>");
                $desc = self::clean_str($desc);
                $GLOBALS['taxon'][$taxon_name]['association'] = $desc;
            }
        }
    }

    function prepare_common_names()
    {
        $urls = array("http://www.illinoiswildflowers.info/flower_insects/files/family_names.htm", "http://www.illinoiswildflowers.info/flower_insects/files/common_names.htm");
        foreach($urls as $url)
        {
            $html = Functions::get_remote_file($url);
            $html = str_ireplace("</FONT></FONT></FONT>", "<U>", $html); // so that last block is included in preg_match_all
            $html = str_ireplace("etc.", "", $html);
            if(preg_match_all("/<\/U>(.*?)<U>/ims", $html, $matches))
            {
                foreach($matches[1] as $str)
                {
                    $records = array();
                    $str = str_ireplace('"#000000">', '"#000000"><BR>', $str); //to get the first row in a block
                    $str = str_ireplace('<BR>' , '&records[]=', $str);  
                    parse_str($str);
                    foreach($records as $key => $value) $records[$key] = trim(self::clean_str(strip_tags($value))); //do some cleaning
                    foreach($records as $rec)
                    {
                        if($rec)
                        {
                            $taxon_name = '';
                            $common_name = '';
                            if(preg_match("/(.*?)\=/ims", $rec, $match)) 
                            {
                                $taxon_name = trim($match[1]);
                                if(preg_match("/(.*?)\(/ims", $taxon_name, $match)) $taxon_name = trim($match[1]);
                            }
                            if(preg_match("/\=(.*?)xxx/ims", $rec . "xxx", $match)) $common_name = trim($match[1]);
                            $GLOBALS['taxon'][$taxon_name]['comnames'][] = $common_name;
                        }
                    }
                }
            }
            // break; //debug - to get only 1 url
        }
        
        //split those comma-separated common names
        foreach($GLOBALS['taxon'] as $taxon_name => $value)
        {
            if(!@$value['comnames']) continue;
            $i = 0;
            foreach(@$value['comnames'] as $common_name)
            {
                $names = explode(",", $common_name);
                if(count($names) > 1) 
                {
                    foreach($names as $name) $GLOBALS['taxon'][$taxon_name]['comnames'][] = trim($name);
                    $GLOBALS['taxon'][$taxon_name]['comnames'][$i] = null; //delete those with comma
                }
                $i++;
            }
            $GLOBALS['taxon'][$taxon_name]['comnames'] = array_filter($GLOBALS['taxon'][$taxon_name]['comnames']); //remove null arrays
            $GLOBALS['taxon'][$taxon_name]['comnames'] = array_unique($GLOBALS['taxon'][$taxon_name]['comnames']); //make unique
            $GLOBALS['taxon'][$taxon_name]['comnames'] = array_values($GLOBALS['taxon'][$taxon_name]['comnames']); //reindex key
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
        $arr_data[] = array("identifier"   => str_replace(" ", "_", $taxon_record['taxon_name']) . "_flower_visitors",
                            "source"       => @$taxon_record['html'],
                            "kingdom"      => @$taxon_record['ancestry']['kingdom'],
                            "phylum"       => @$taxon_record['ancestry']['phylum'],
                            "class"        => @$taxon_record['ancestry']['class'],
                            "order"        => @$taxon_record['ancestry']['order'],
                            "family"       => @$taxon_record['ancestry']['family'],
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
        $texts = array();
        if(@$record['gendesc'])     $texts[] = array("desc"     => $record['gendesc'], 
                                                     "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription",
                                                     "title"    => '',
                                                     "type"     => 'gendesc');
        if(@$record['association']) $texts[] = array("desc"     => $record['association'], 
                                                     "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations",
                                                     "title"    => @$record['association_title'],
                                                     "type"     => 'association');
        /* no title: Hypoxis hirsuta, Celastrus scandens */
        foreach($texts as $text)
        {
            $agent = array();
            $agent[] = array("role" => 'source', "homepage" => 'http://www.illinoiswildflowers.info/flower_insects/index.htm', "fullName" => 'John Hilty');
            $refs = self::get_references();
            $identifier     = str_replace(" ", "_", $record['taxon_name']) . "_flower_visitors_" . $text['type'];
            $description    = $text['desc'];
            $license        = "http://creativecommons.org/licenses/by-nc/3.0/";
            $agent          = $agent;
            $rightsHolder   = "John Hilty";
            $rights         = "Copyright © 2002-2010 by Dr. John Hilty";
            $location       = '';
            $dataType       = "http://purl.org/dc/dcmitype/Text";
            $mimeType       = "text/html";
            $title          = $text['title'];
            $subject        = $text['subject'];
            $source         = $record['html'];
            $mediaURL       = '';
            $refs           = $refs;
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $arr_objects);
        }
        return $arr_objects;
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $arr_objects)
    {
        $arr_objects[]=array( "identifier"   => $identifier,
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
                              "language"     => "en"
                            );
        return $arr_objects;
    }

    function get_references()
    {
        $reference = "Hilty, J. Editor. 2010. Insect Visitors of Illinois Wildflowers. World Wide Web electronic publication. flowervisitors.info, version (09/2010).<br>See: <a href='http://www.illinoiswildflowers.info/flower_insects/files/abbreviations.htm'>Abbreviations for Insect Activities</a>, <a href='http://www.illinoiswildflowers.info/flower_insects/files/observers.htm'>Abbreviations for Scientific Observers</a>, <a href='http://www.illinoiswildflowers.info/flower_insects/files/references.htm'>References for behavioral observations</a>";
        $refs = array();
        $refs[] = array("url" => $reference['url'], "fullReference" => $reference);
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
        // this line counts how many # as num, and repeats this char in num times, then replaces these chars with just 1 space ' ' 
        // $str = str_replace(str_repeat(" ", substr_count($str, ' ')), ' ', $str);
        return $str;
    }

}
?>