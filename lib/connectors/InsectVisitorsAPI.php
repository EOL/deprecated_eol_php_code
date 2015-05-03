<?php
namespace php_active_record;
/* connector: [143]
http://www.illinoiswildflowers.info/
Connector scrapes the site: http://www.illinoiswildflowers.info/flower_insects/index.htm
and assembles the information and generates the EOL XML.
*/
class InsectVisitorsAPI
{
    public function __construct($test_run = false)
    {
        $this->test_run = $test_run;
        $this->path['home']         = 'http://www.illinoiswildflowers.info/flower_insects';
        $this->path['observers']    = "http://www.illinoiswildflowers.info/flower_insects/files/observers.htm";
        $this->path['activities']   = "http://www.illinoiswildflowers.info/flower_insects/files/abbreviations.htm";
        $this->urls = array();
        $this->urls[] = array("active" => 1, "type" => "insects", "ancestry" => array("kingdom" => "Animalia", "phylum" => "", "class" => "", "order" => "", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "birds", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Chordata", "class" => "Aves", "order" => "", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "bees", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "wasps", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "flies", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Diptera", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "moths", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Lepidoptera", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "beetles", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Coleoptera", "family" => ""));
        /* not all under Hemiptera, so better not put Order here anymore but scrape the Order and Family from the actual page - DATA-1325 */
        $this->urls[] = array("active" => 1, "type" => "bugs", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "", "family" => ""));
        $this->file_urls = array();
        $this->file_urls[] = array("active" => 1, "type" => "lt_bee", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "st_bee", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "wasps", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "flies", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Diptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "beetles", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Coleoptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "plant_bugs", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hemiptera", "family" => ""));
        $this->file_urls[] = array("active" => 1, "type" => "lepidoptera", "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Lepidoptera", "family" => ""));
        
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 1);
    }

    function get_all_taxa($resource_id)
    {
        $this->observers = self::get_observers();
        $this->activities = self::get_activities();
        self::get_associations();
        self::get_general_descriptions();
        self::prepare_common_names();
        echo("\n total: " . count($GLOBALS['taxon']) . "\n");
        $all_taxa = array();
        $i = 0;
        $total = count(array_keys($GLOBALS['taxon']));
        foreach($GLOBALS['taxon'] as $taxon_name => $record)
        {
            $i++; 
            if(($i % 100) == 0) echo("\n$i of $total " . $taxon_name);
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
        return $all_taxa; //used for testing
    }

    private function get_general_descriptions()
    {
        $i = 0;
        foreach($this->file_urls as $path)
        {
            $url = $this->path['home'] . '/files/' . $path["type"] . ".htm";
            if($path["active"])
            {
                if(($i % 100) == 0) echo "\n -$i";
                self::process_gen_desc($url, $path["ancestry"], $path['type']);
                $i++;
                if($this->test_run) break; //just get 1 url
            }
        }
        if($i == 0) return;
        //special cases
        self::fix_multiple_names_with_separators();
    }

    private function fix_multiple_names_with_separators()
    {
        foreach($GLOBALS['taxon'] as $taxon_name => $record)
        {
            $scinames = array();
            if($taxon_name == "Formicidae and Myrmicidae") $scinames = explode(" and ", $taxon_name);
            elseif(in_array($taxon_name, array("Sciaridae, Mycetophilidae", "Tephretidae, Drosophilidae"))) $scinames = explode(", ", $taxon_name);
            if($scinames)
            {
                $scinames = array_map('trim', $scinames);
                foreach($scinames as $sciname)
                {
                    $GLOBALS['taxon'][$sciname]['ancestry'] = $record['ancestry'];
                    $GLOBALS['taxon'][$sciname]['gendesc']  = $record['gendesc'];
                    $GLOBALS['taxon'][$sciname]['html']     = $record['html'];
                }
            }
        }
        unset($GLOBALS['taxon']['Formicidae and Myrmicidae']);
        unset($GLOBALS['taxon']['Sciaridae, Mycetophilidae']);
        unset($GLOBALS['taxon']['Tephretidae, Drosophilidae']);
    }

    private function process_gen_desc($url, $ancestry, $type)
    {
        if(!$html = Functions::lookup_with_cache($url, $this->download_options)) return;
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

    private function get_associations()
    {
        $bird_type = array("birds", "bees", "wasps", "flies", "moths", "beetles", "bugs");
        $i = 0;
        foreach($this->urls as $path)
        {
            if($path['type'] == 'insects') $url = "http://www.illinoiswildflowers.info/flower_insects/index.htm";
            else                           $url = $this->path['home'] . '/insects/' . $path['type'] . ".htm";
            if($path["active"])
            {
                echo("\n$i " . $path['type'] . " [$url]\n");
                if($path['type'] == "insects")              self::process_insects($url, $path["ancestry"]);
                elseif(in_array($path['type'], $bird_type)) self::process_birds($url, $path["ancestry"], $path['type']);
            }
            $i++;
            if($this->test_run)
            {
                if($i >= 2) break;
            }
        }
    }

    private function process_birds($url, $ancestry, $type)
    {
        if(!$html = Functions::lookup_with_cache($url, $this->download_options)) return;
        /*HREF="birds/hummingbird.htm" NAME="hummingbird">Archilochus colubris</A><BR></B><FONT COLOR="#000000">(Ruby-Throated Hummingbird)</FONT></FONT></FONT></TD>*/
        if(preg_match_all("/href=\"$type(.*?)<\/td>/ims", $html, $matches))
        {
            $i = 0;
            foreach($matches[1] as $match)
            {
                $match = strip_tags($match, "<a>");
                /*/hummingbird.htm" NAME="hummingbird">Archilochus colubris</A><BR></B><FONT COLOR="#000000">(Ruby-Throated Hummingbird)</FONT></FONT></FONT>*/
                if(preg_match("/>(.*?)<\/a>/ims", $match, $string_match)) $taxon_name = self::clean_str($string_match[1]);
                if(preg_match("/\/(.*?)\"/ims", $match, $string_match)) $html = self::clean_str($string_match[1]);
                $taxon_name = utf8_encode($taxon_name);
                // if($taxon_name != "Vernonia × illinoensis") continue; //debug
                if(preg_match("/\((.*?)\)/ims", $match, $string_match))
                {
                    $common_name = self::clean_str($string_match[1]);
                    $GLOBALS['taxon'][$taxon_name]['comnames'][] = self::clean_str($common_name);
                }
                $GLOBALS['taxon'][$taxon_name]['html'] = "/$type/$html";
                $GLOBALS['taxon'][$taxon_name]['ancestry'] = $ancestry;
                $i++; 
                if($this->test_run)
                {
                    if($i >= 1) break; //debug
                }
            }
        }
        self::get_title_description();
    }

    private function process_insects($url, $ancestry)
    {
        if(!$html = Functions::lookup_with_cache($url, $this->download_options)) return;
        /*<a href="plants/velvetleaf.htm" name="velvetleaf">Abutilon theophrastii (Velvet Leaf)</a>*/
        if(preg_match_all("/href=\"plants(.*?)<\/a>/ims", $html, $matches))
        {
            $i = 0;
            foreach($matches[1] as $match)
            {
                /*/purs_spdwell.htm" name="purs_spdwell">Veronica peregrina (Purslane Speedwell)*/
                if(preg_match("/>(.*?)\(/ims", $match, $string_match)) $taxon_name = self::clean_str($string_match[1]);
                $taxon_name = utf8_encode($taxon_name);
                // if($taxon_name != "Vernonia × illinoensis") continue; //debug
                if(preg_match("/\/(.*?)\"/ims", $match, $string_match)) $html = self::clean_str($string_match[1]);
                if(preg_match("/\((.*?)\)/ims", $match, $string_match))
                {
                    $common_name = self::clean_str($string_match[1]);
                    $GLOBALS['taxon'][$taxon_name]['comnames'][] = $common_name;
                }
                $GLOBALS['taxon'][$taxon_name]['html'] = "/plants/$html";
                $GLOBALS['taxon'][$taxon_name]['ancestry'] = $ancestry;
                $i++; 
                if($this->test_run)
                {
                    if($i >= 1) break; //debug
                }
            }
        }
        self::get_title_description('insects');
    }

    private function get_title_description($type = null)
    {
        foreach($GLOBALS['taxon'] as $taxon_name => $value)
        {
            // if($taxon_name != "Hylaeus affinis") continue; //debug
            if(@$value['association'] != "" || @$value['gendesc'] != "") continue;
            $url = $this->path['home'] . '/insects/' . $value['html'];
            if($type == 'insects') $url = str_ireplace("/insects/", "/", $url);
            $GLOBALS['taxon'][$taxon_name]['html'] = $url;
            if(!$html = Functions::lookup_with_cache($url, $this->download_options))
            {
                echo("\n\n Content partner's server is down, $url\n");
                $GLOBALS['taxon'][$taxon_name]['association'] = 'no object';
                continue;
            }
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
            if(@$GLOBALS['taxon'][$taxon_name]['association'] && $type != "insects")
            {
                $family_order = self::get_order_and_family($GLOBALS['taxon'][$taxon_name]['association']);
                $GLOBALS['taxon'][$taxon_name]['ancestry']['family'] = @$family_order[0];
                $GLOBALS['taxon'][$taxon_name]['ancestry']['order'] = @$family_order[1];
            }
        }
    }
    
    private function get_order_and_family($desc)
    {
        // Adelphocoris lineolatus Goeze: Miridae, Hemiptera<BR> - http://www.illinoiswildflowers.info/flower_insects/insects/bugs/adelphocoris_lineolatus.htm
        if(preg_match("/:(.*?)<BR>/ims", $desc, $match)) 
        {
            $string = $match[1];
            $names = explode(",", $string);
            if(count($names) == 2)
            {
                $names[0] = self::remove_parenthesis($names[0]);
                $names[1] = self::remove_parenthesis($names[1]);
                return $names;
            }
            else return array();
        }
    }
    
    private function remove_parenthesis($string)
    {
        return trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
    }

    private function prepare_common_names()
    {
        $urls = array("http://www.illinoiswildflowers.info/flower_insects/files/family_names.htm", "http://www.illinoiswildflowers.info/flower_insects/files/common_names.htm");
        foreach($urls as $url)
        {
            if(!$html = Functions::lookup_with_cache($url, $this->download_options)) return;
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
            if($this->test_run) break; //debug - to get only 1 url, not 2 (family_names.htm, common_names.htm)
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

    private function get_visitors_taxa($taxon_record)
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
                            "sciname"      => str_ireplace(array(" sp.", " spp.", " spp"), "", $taxon_record['taxon_name']),
                            "reference"    => array(),
                            "synonyms"     => array(),
                            "commonNames"  => $common_names,
                            "data_objects" => $arr_objects
                           );
        return $arr_data;
    }

    private function get_objects($record, $arr_objects)
    {
        $texts = array();
        if(@$record['gendesc'])     $texts[] = array("desc"     => $record['gendesc'],
                                                     "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription",
                                                     "title"    => '',
                                                     "type"     => 'gendesc');
        if(@$record['association'] && @$record['association'] != 'no object')
                                    $texts[] = array("desc"     => $record['association'],
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
            
            if($val = self::generate_abbreviation_section_for_activities($description)) $description .= "<br>Insect activities:<br>" . $val;
            if($val = self::generate_abbreviation_section_for_observers($description)) $description .= "<br>Scientific observers:<br>" . $val;
            
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
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $arr_objects);
        }
        return $arr_objects;
    }

    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $arr_objects)
    {
        $arr_objects[]=array( "identifier"   => $identifier,
                              "dataType"     => $dataType,
                              "mimeType"     => $mimeType,
                              "title"        => $title,
                              "source"       => $source,
                              "description"  => utf8_encode($description),
                              "mediaURL"     => $mediaURL,
                              "agent"        => $agent,
                              "license"      => $license,
                              "location"     => utf8_encode($location),
                              "rights"       => $rights,
                              "rightsHolder" => $rightsHolder,
                              "reference"    => $refs,
                              "subject"      => $subject,
                              "language"     => "en");
        return $arr_objects;
    }

    private function get_references()
    {
        $reference = "Hilty, J. Editor. " . date("Y") . ". Insect Visitors of Illinois Wildflowers.
        World Wide Web electronic publication. illinoiswildflowers.info, version (" . date("m/Y")  . ")
        <br>See: 
        <a href='" . $this->path['activities'] . "'>Abbreviations for Insect Activities</a>,
        <a href='" . $this->path['observers'] . "'>Abbreviations for Scientific Observers</a>,
        <a href='http://www.illinoiswildflowers.info/flower_insects/files/references.htm'>References for behavioral observations</a>";
        $refs = array();
        $refs[] = array("url" => '', "fullReference" => $reference);
        return $refs;
    }

    private function get_common_names($names)
    {
        $arr_names = array();
        if($names)
        {
            foreach($names as $name) $arr_names[] = array("name" => $name, "language" => 'en');
        }
        return $arr_names;
    }

    private function clean_str($str)
    {
        return str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "    ", "   ", "  "), " ", trim($str));
    }

    private function get_observers()
    {
        $items = array();
        if($html = Functions::lookup_with_cache($this->path['observers'], $this->download_options))
        {
            //manual adjustment
            $html = self::clean_str(functions::remove_whitespace($html));
            $html = str_ireplace('<font color="#3333ff">(Rb)</font> <font color="#3333ff">=</font> <font color="#3333ff">Charles Robertson</font>', '<font color="#3333ff">(Rb) = Charles Robertson</font>', $html);
            $html = str_ireplace('<font color="#3333ff">(Mch)</font> <font color="#3333ff">= Theodore B. Mitchell</font>', '<font color="#3333ff">(Mch) = Theodore B. Mitchell</font>', $html);
            
            if(preg_match_all("/<font color=\"#3333ff\">(.*?)<\/font>/ims", $html, $arr))
            {
                foreach($arr[1] as $item)
                {
                    if(preg_match("/\((.*?)\)/ims", $item, $arr2)) $items[$arr2[1]] = $item;
                }
            }
        }
        return $items;
    }

    private function get_activities()
    {
        $items = array();
        if($html = Functions::lookup_with_cache($this->path['activities'], $this->download_options))
        {
            //manual adjustment
            $html = self::clean_str(functions::remove_whitespace($html));
            $html = str_ireplace('insect visitors</FONT></P>', 'insect visitors<BR><BR>', $html);
            $html = str_ireplace('<P ALIGN="LEFT"><FONT FACE="Times New Roman">prf', '<BR><BR>prf', $html);
            $html = strip_tags($html, "<BR>");

            if(preg_match_all("/<BR>(.*?)<BR>/ims", $html, $arr))
            {
                foreach($arr[1] as $item)
                {
                    if(preg_match("/xxx(.*?) =/ims", "xxx".$item, $arr2)) $items[trim($arr2[1])] = $item;
                }
            }
        }
        return $items;
    }

    private function generate_abbreviation_section_for_activities($string)
    {
        $found = array();
        foreach($this->activities as $key => $value)
        {
            if(strpos($string, " $key") !== false) $found[$key] = '';
        }
        $desc = "";
        foreach(array_keys($found) as $item) $desc .= $this->activities[$item] . "<br>";
        return $desc;
    }

    private function generate_abbreviation_section_for_observers($string)
    {
        $found = array();
        foreach($this->observers as $key => $value)
        {
            if    (strpos($string, "($key)") !== false) $found[$key] = '';
            elseif(strpos($string, "($key,") !== false) $found[$key] = '';
            elseif(strpos($string, " $key,") !== false) $found[$key] = '';
            elseif(strpos($string, " $key)") !== false) $found[$key] = '';
        }
        $desc = "";
        foreach(array_keys($found) as $item) $desc .= $this->observers[$item] . "<br>";
        return $desc;
    }

}
?>