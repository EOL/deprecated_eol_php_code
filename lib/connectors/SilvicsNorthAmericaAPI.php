<?php
namespace php_active_record;
/* connector: [419] 
Connector scrapes the site: http://www.na.fs.fed.us/spfo/pubs/silvics_manual/table_of_contents.htm
Assembles the information and generates the EOL XML.
Data comes from 2 main pages:
Conifers: http://www.na.fs.fed.us/spfo/pubs/silvics_manual/Volume_1/vol1_Table_of_contents.htm
Hardwoods: http://www.na.fs.fed.us/spfo/pubs/silvics_manual/volume_2/vol2_Table_of_contents.htm
*/
class SilvicsNorthAmericaAPI
{
    public function __construct($test_run = false, $debug_info = true)
    {
        $this->test_run = $test_run;
        $this->debug_info = $debug_info;
        $this->path = 'http://www.na.fs.fed.us/spfo/pubs/silvics_manual';
        $this->urls = array();
        $this->urls[] = array("active" => 1, "type" => "conifers", "folder" => "Volume_1", "page" => "vol1_Table_of_contents.htm", "ancestry" => array("kingdom" => "Plantae", "phylum" => "", "class" => "", "order" => "", "family" => ""));
        $this->urls[] = array("active" => 1, "type" => "hardwoods", "folder" => "Volume_2", "page" => "vol2_Table_of_contents.htm", "ancestry" => array("kingdom" => "Plantae", "phylum" => "", "class" => "", "order" => "", "family" => ""));
    }

    function get_all_taxa($resource_id)
    {
        $success = self::get_associations();
        if($success === false) return false;
        if($this->debug_info) print "\n\n total: " . count($GLOBALS['taxon']) . "\n";
        $all_taxa = array();
        $i = 0;
        $total = count(array_keys($GLOBALS['taxon']));
        foreach($GLOBALS['taxon'] as $taxon_name => $record)
        {
            $i++; 
            if($this->debug_info) print "\n$i of $total " . $taxon_name;
            $record["taxon_name"] = $taxon_name;
            $arr = self::get_silvics_taxa($record);
            $page_taxa = $arr[0];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        if(!($OUT = fopen($resource_path, "w")))
        {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $temp_file_path);
              return;
         }
        fwrite($OUT, $xml);
        fclose($OUT);
        return $all_taxa; //used for testing
    }

    function get_associations()
    {
        $i = 0;
        foreach($this->urls as $path)
        {
            $url = $this->path . "/" . $path['folder'] . "/" . $path['page'];
            if($path["active"])
            {
                if($this->debug_info) print "\n\n$i " . $path['type'] . " [$url]\n";        
                if    ($path['type'] == "conifers")  $success = self::process_conifers($url, $path["ancestry"], $path['folder'], $path['type']);
                elseif($path['type'] == "hardwoods") $success = self::process_conifers($url, $path["ancestry"], $path['folder'], $path['type']);
                if($success === false) return false;
            }
            $i++;
        }
    }

    function process_conifers($url, $ancestry, $folder, $type)
    {
        if(!$html = Functions::get_remote_file($url))
        {
            print("\n\n Content partner's server is down3, $url\n");
            echo "\nProgram will terminate.\n"; // this has to be terminated bec. the entire section either conifers or hardwoods is down.
            return false;
        }
        // manual adjustment
        $html = str_ireplace('<I><FONT FACE="Arial">', '<FONT FACE="Arial"><I>', $html);
        $html = str_ireplace('</FONT></I>', '</I></FONT>', $html);

        if(preg_match_all("/<FONT FACE=\"Arial\"><I>(.*?)<\/I>/ims", $html, $matches))
        {
            $i = 0;
            $continue = true;
            foreach($matches[1] as $match)
            {
                /* /purs_spdwell.htm" name="purs_spdwell">Veronica peregrina (Purslane Speedwell) */
                if(preg_match("/>(.*?)</ims", $match, $string_match)) $taxon_name = self::clean_str($string_match[1]);
                $taxon_name = utf8_encode($taxon_name);

                /* https://jira.eol.org/browse/DATA-1095?page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel&focusedCommentId=37475#comment-37475
                Manually change from one name to another:
                From: Didymopanax morototoni -- To: Schefflera morototoni
                From: Chamaecyparis nootkatensis -- To: Cupressus nootkatensis
                */
                if($taxon_name == "Didymopanax morototoni") $taxon_name = "Schefflera morototoni";
                if($taxon_name == "Chamaecyparis nootkatensis") $taxon_name = "Cupressus nootkatensis";
                
                /* debug - use to start process only for this taxon
                if($taxon_name == "Larix laricina") $continue = false;
                if($continue) continue;
                */

                if(preg_match("/\"(.*?)\"/ims", $match, $string_match)) $html = self::clean_str($string_match[1]);
                $GLOBALS['taxon'][$taxon_name]['html'] = $this->path . "/" . $folder . "/" . $html;
                $GLOBALS['taxon'][$taxon_name]['ancestry'] = $ancestry;
                $i++; 
                if($this->test_run)
                {
                    if($i >= 3) break; //debug
                }
            }
        }
        $success = self::get_title_description($type, $taxon_name);
        if($success === false) return false;
    }

    function get_title_description($type = null, $taxon_name)
    {
        foreach($GLOBALS['taxon'] as $taxon_name => $value)
        {
            if(@$GLOBALS['taxon'][$taxon_name]['sciname'] || @$GLOBALS['taxon'][$taxon_name]['texts']) continue;
            sleep(5); //debug 
            $url = $value['html'];
            if($this->debug_info) print "\n\n $url -- $taxon_name";

            $trials = 1;
            $success = 0;
            while($success == 0 && $trials < 5)
            {
                if($html = Functions::get_remote_file($url)) $success = 1;
                else
                {
                    $trials++;
                    print "\n Down: $url";
                    print "\n Will wait for 30 seconds and will try again. Trial #" . $trials;
                    sleep(30);
                }
            }
            if($trials >= 5)
            {
                print "\n Will skip to the next species after $trials unsuccessful trials";
                continue;
            }

            if    (preg_match("/<FONT SIZE=\"\+3\">(.*?)<\/FONT>/ims", $html, $arr)) $GLOBALS['taxon'][$taxon_name]['sciname'] = self::clean_str(strip_tags($arr[1]));
            elseif(preg_match("/<FONT SIZE=\"\+2\">(.*?)<\/FONT>/ims", $html, $arr)) $GLOBALS['taxon'][$taxon_name]['sciname'] = self::clean_str(strip_tags($arr[1]));
            $GLOBALS['taxon'][$taxon_name]['sciname'] = str_ireplace('&amp;', '&', $GLOBALS['taxon'][$taxon_name]['sciname']);
            if(preg_match("/<FONT SIZE=\"\+4\">(.*?)<\/FONT>/ims", $html, $arr)) $GLOBALS['taxon'][$taxon_name]['comnames'][] = self::clean_str($arr[1]);
            if($GLOBALS['taxon'][$taxon_name]['sciname'] == "Didymopanax morototoni (Aubl.) Decne. & Planch.") 
            {
                $GLOBALS['taxon'][$taxon_name]['sciname'] = "Schefflera morototoni";
                $GLOBALS['taxon'][$taxon_name]['comnames'] = array();
            }
            elseif($GLOBALS['taxon'][$taxon_name]['sciname'] == "Chamaecyparis nootkatensis (D. Don)  Spach") 
            {
                $GLOBALS['taxon'][$taxon_name]['sciname'] = "Cupressus nootkatensis";
                $GLOBALS['taxon'][$taxon_name]['comnames'] = array();
            }

            // manual adjustment
            $html = str_ireplace('<H2></H2>', '', $html); //only for hardwoods
            $html = str_ireplace('<H3>', '<xxx><H3>', $html);
            $html = str_ireplace('<H2>', '<xxx><H2>', $html);
            $html = str_ireplace('<H1>', '<xxx><H1>', $html); //only for hardwoods
            $html = str_ireplace('<H4>', '<xxx><H4>', $html); //only for hardwoods

            if($type == "hardwoods")
            {
                $html = str_ireplace('<P><FONT><B></B></FONT></P>', '', $html); //only for hardwoods
                $html = str_ireplace('<P><FONT><B>', '<P><B>', $html); //only for hardwoods
                $html = str_ireplace('</B></FONT></P>', '</B></P>', $html); //only for hardwoods
                $html = str_ireplace('Damaging Agents-Robusta </B>', 'Damaging Agents-</B> Robusta ', $html);
                $html = str_ireplace('Growth and Yield-Black </B>', 'Growth and Yield-</B> Black ', $html);
                $html = str_ireplace('Rooting Habit-Aigeiros-</B>', 'Rooting Habit-</B> Aigeiros- ', $html);
                $html = str_ireplace('Growth and Yield-Bitternut </B>', 'Growth and Yield-</B> Bitternut ', $html);
            }
            elseif($type == "conifers")
            {
                $html = str_ireplace('<P><B>Vegetative Reproduction-White-cedar </B>', '<P><B>Vegetative Reproduction-</B> White-cedar ', $html); //only for conifers
                $html = str_ireplace('<P><B>Seedling Development-Germination </B>', '<P><B>Seedling Development-</B> Germination ', $html); //only for conifers
            }

            $html = str_ireplace('<P><B><FONT SIZE="+1">Native Range</FONT></B></P>', '<H3>Native Range</H3>', $html);
            $html = str_ireplace('<H2>Native Range</H2>', '<H3>Native Range</H3>', $html);
            // manual adjustment - hardwoods
            $html = str_ireplace(array("<H1></H1>", "<H2></H2>"), "", trim($html)); // to properly get 'brief summary'

            $texts = array();
            // brief summary - start ---------------------
            $brief_summary = "";
            if($type == "hardwoods")
            {
                if(preg_match("/<\/FONT><\/H1>(.*?)<H/ims", $html, $match)) $brief_summary = $match[1];
                if($brief_summary == "")
                {
                    if(preg_match("/<\/B><\/P>(.*?)<H/ims", $html, $match)) $brief_summary = trim($match[1]);
                }
            }
            else
            {
                if(preg_match("/<\/B><\/P>(.*?)<H/ims", $html, $match)) $brief_summary = trim($match[1]);
            }
            $brief_summary_with_all_tags = str_ireplace('<xxx>', '', $brief_summary);
            $brief_summary = strip_tags($brief_summary_with_all_tags,"<p><i>");
            $brief_summary = str_ireplace('ALIGN="CENTER"', '', $brief_summary);
            if($brief_summary) $texts[] = array("title" => "brief summary", "description" => $brief_summary);
            $agents_and_family_info = self::get_agents_and_family($brief_summary_with_all_tags, trim($url));
            $GLOBALS['taxon'][$taxon_name]['agents'] = $agents_and_family_info['agents'];
            $GLOBALS['taxon'][$taxon_name]['ancestry']['family'] = $agents_and_family_info['family'];
            // brief summary - end ---------------------

            // get "<H2>Special Uses</H2>" and "<H2>Genetics</H2>" independently
            $special_uses = "";
            $genetics = "";
            if(preg_match("/<H2>Special Uses<\/H2>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/<H1>Special Uses<\/H1>(.*?)<xxx>/ims", $html, $match)) $special_uses = $match[1];
            if(preg_match("/<H2>Genetics<\/H2>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/<H1>Genetics<\/H1>(.*?)<xxx>/ims", $html, $match)) $genetics = trim($match[1]);
            if($genetics == "")
            {
                //http://www.na.fs.fed.us/spfo/pubs/silvics_manual/Volume_1/larix/occidentalis.htm
                if(preg_match("/<H2>Genetics<\/H2>(.*?)<H2>Literature Cited/ims", $html, $match)) $genetics = strip_tags(trim($match[1]),"<P><I>");
            }
            if(preg_match("/<H3>Native Range<\/H3>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/Native Range<\/FONT><\/H4>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/Range<\/FONT><\/H4>(.*?)<xxx>/ims", $html, $match))
            {
                $native_range = $match[1];
                if(preg_match("/<IMG SRC\=\"(.*?)\"/ims", $match[1], $map))
                {
                    $path_parts = pathinfo($url);
                    $map_url = $path_parts['dirname'] . "/" . $map[1];
                    $texts[] = array("title" => "maps tab", "description" => $map_url);
                    $native_range = str_ireplace($map[1], $map_url, $native_range);
                }
                $texts[] = array("title" => "Native Range", "description" => $native_range);
            }
            if(preg_match("/<H3>Climate<\/H3>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/<H2>Climate<\/H2>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/Climate<\/FONT><\/H4>(.*?)<xxx>/ims", $html, $match)) $texts[] = array("title" => "Climate", "description" => $match[1]);
            if(preg_match("/<H3>Soils and Topography<\/H3>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/<H2>Soils and Topography<\/H2>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/Soils and Topography<\/FONT><\/H4>(.*?)<xxx>/ims", $html, $match)) $texts[] = array("title" => "Soils and Topography", "description" => $match[1]);
            if(preg_match("/<H3>Associated Forest Cover<\/H3>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/<H2>Associated Forest Cover<\/H2>(.*?)<xxx>/ims", $html, $match) ||
               preg_match("/Associated Forest Cover<\/FONT><\/H4>(.*?)<xxx>/ims", $html, $match)) $texts[] = array("title" => "Associated Forest Cover", "description" => $match[1]);
            if(preg_match_all("/<H3>(.*?)<xxx>/ims", $html, $matches))
            {
                foreach($matches[1] as $match)
                {
                    $title = ""; $description = "";
                    if(preg_match("/eee(.*?)<\/H3>/ims", "eee".$match, $arr)) $title = strip_tags(trim($arr[1]));
                    if(preg_match("/<\/H3>(.*?)eee/ims", $match."eee", $arr)) 
                    {
                        $description = trim($arr[1]);
                        $texts = self::divide_whole_text_to_texts($description, $texts);
                    }else
                    {
                        echo "\n 111 walang text within texts...\n";
                        return false;
                    }
                    /* this is if you want to get the entire text section as 1 <dataObject>
                    if($title) $texts[] = array("title" => $title, "description" => $description);
                    */
                }
            }
            if($type == "hardwoods")
            {
                // e.g. Acer macrophyllum
                if(preg_match_all("/<H2>(.*?)<xxx>/ims", $html, $matches))
                {
                    foreach($matches[1] as $match)
                    {
                        $title = ""; $description = "";
                        if(preg_match("/eee(.*?)<\/H2>/ims", "eee".$match, $arr)) $title = strip_tags(trim($arr[1]));
                        if(preg_match("/<\/H2>(.*?)eee/ims", $match."eee", $arr)) 
                        {
                            $description = trim($arr[1]);
                            $texts = self::divide_whole_text_to_texts($description, $texts);
                        }else
                        {
                            echo "\n 222 walang text within texts...\n";
                            return false;
                        }
                    }
                }
                // e.g. Acer nigrum
                if(preg_match_all("/<H4>(.*?)<xxx>/ims", $html, $matches))
                {
                    foreach($matches[1] as $match)
                    {
                        $title = ""; $description = "";
                        if(preg_match("/eee(.*?)<\/H4>/ims", "eee".$match, $arr)) $title = strip_tags(trim($arr[1]));
                        if(preg_match("/<\/H4>(.*?)eee/ims", $match."eee", $arr)) 
                        {
                            $description = trim($arr[1]);
                            $texts = self::divide_whole_text_to_texts($description, $texts);
                        }else
                        {
                            echo "\n 333 walang text within texts...\n";
                            return false;
                        }
                    }
                }
            }
            if($genetics)     $texts[] = array("title" => "Genetics", "description" => $genetics);
            if($special_uses) $texts[] = array("title" => "Special Uses", "description" => $special_uses);
            $GLOBALS['taxon'][$taxon_name]['texts'] = $texts;
            $html = str_ireplace("Literature Cited </H2>", "Literature Cited</H2>", $html);
            if    (preg_match("/Literature Cited<\/H2>(.*?)<\/BODY>/ims", $html, $match)) $GLOBALS['taxon'][$taxon_name]['taxon_ref'] = $match[1];
            elseif(preg_match("/Literature Cited<\/H1>(.*?)<\/BODY>/ims", $html, $match)) $GLOBALS['taxon'][$taxon_name]['taxon_ref'] = $match[1];
            elseif(preg_match("/Literature Cited<\/H3>(.*?)<\/BODY>/ims", $html, $match)) $GLOBALS['taxon'][$taxon_name]['taxon_ref'] = $match[1];
            elseif(preg_match("/Literature Cited<\/H4>(.*?)<\/BODY>/ims", $html, $match)) $GLOBALS['taxon'][$taxon_name]['taxon_ref'] = $match[1];
            elseif(preg_match("/Literature Cited<\/FONT><\/H4>(.*?)<\/BODY>/ims", $html, $match)) $GLOBALS['taxon'][$taxon_name]['taxon_ref'] = $match[1];
            elseif(preg_match("/<\/B>Literature Cited(.*?)<\/BODY>/ims", $html, $match)) $GLOBALS['taxon'][$taxon_name]['taxon_ref'] = $match[1];
        }
    }

    function get_agents_and_family($string, $url)
    {
        $family = "";
        $names = array();
        $agents = array();
        $string = str_ireplace("</B> </P>", "</B></P>", $string);
        $string = str_ireplace("Tappeiner, II", "Tappeiner II", $string);
        if(preg_match_all("/<P><B>(.*?)<\/B><\/P>/ims", $string, $matches))
        {
            foreach($matches[1] as $match)
            {
                //get family
                /* <P><B>Aceraceae -- Maple family</B></P> */

                //manual adjustment
                if(trim($match) == "Leguminosae Legume family") $family = "Leguminosae";

                $arr = explode("--", $match);
                if(count($arr) > 1 && $family == "") $family = trim($arr[0]);
                else
                {
                    //get agents' names
                    //manual adjustment
                    $match = str_ireplace("David F. Olson, Jr.", "David F. Olson Jr.", $match);

                    $match = str_ireplace(" and", ",", $match);
                    $match = strip_tags($match);
                    $names = explode(",", $match);
                }
            }
        }
        else
        {
            //manual adjustment
            if(strtolower($url) == strtolower("http://www.na.fs.fed.us/spfo/pubs/silvics_manual/volume_2/acer/barbatum.htm"))
            {
                $family = "Aceraceae";
                $names[] = "Earle R Jones, Jr.";
            }
            elseif(strtolower($url) == strtolower("http://www.na.fs.fed.us/spfo/pubs/silvics_manual/Volume_2/alnus/rubra.htm"))
            {
                $family = "Betulaceae";
                $names[] = "Constance A. Harrington";
            }
        }
        foreach($names as $name)
        {
            if(trim($name) != "") $agents[] = array("role" => 'author', "homepage" => $url, "fullName" => trim($name));
        }
        return array("family" => strip_tags($family), "agents" => $agents);
    }

    function divide_whole_text_to_texts($description, $texts)
    {
        // to exclude tables as an independent text object
        $description = str_ireplace('<P><B>Table </B>', '<br><br><strong>Table </strong>', $description);
        $description = str_ireplace('<P><B>Table 2-</B>', '<br><br><strong>Table 2-</strong>', $description);

        // manual adjustment
        $description = str_ireplace('<B> </B>', ' ', $description);
        $description = str_ireplace('<I> </I>', '', $description); // Reaction to Competition - http://www.na.fs.fed.us/spfo/pubs/silvics_manual/volume_2/acacia/koa.htm
        $description = str_ireplace('<P><FONT SIZE="-1"><B><FONT SIZE="+1">', '<P><B>', $description); // Seed Production and Dissemination - http://www.na.fs.fed.us/spfo/pubs/silvics_manual/volume_2/acacia/koa.htm
        $description = str_ireplace('</FONT>- </B></FONT>', '- </B>', $description); // Seed Production and Dissemination - http://www.na.fs.fed.us/spfo/pubs/silvics_manual/volume_2/acacia/koa.htm
        $description = str_ireplace('<P><B>', '<yyy><P><B>', $description);
        if(preg_match_all("/<P><B>(.*?)<yyy>/ims", $description."<yyy>", $matches))
        {
            foreach($matches[1] as $match)
            {
                $parts = explode("</B>", $match, 2);
                if($parts[0]) $title       = trim($parts[0]);
                else continue; // these are those: Native Distribution, Climate, Soils and Topography, Associated Forest Cover
                if($parts[1]) $description = trim($parts[1]);
                //manual special cases
                if($title == "Flowering and Fruiting-The")
                {
                    $title = str_ireplace('Flowering and Fruiting-The', 'Flowering and Fruiting', $title);
                    $description = "The " . $description;
                }
                $title = str_replace('-', '', $title);
                $title = self::clean_str($title);
                if($title && stripos($title, "No information available") == "") $texts[] = array("title" => $title, "description" => $description);
            }
        }
        return $texts;
    }

    public static function get_silvics_taxa($taxon_record)
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
        if($taxon_record['sciname']) $sciname = $taxon_record['sciname'];
        else $sciname = $taxon_record['taxon_name'];
        $taxon_refs = array();
        $taxon_refs[] = array("url" => '', "fullReference" => $taxon_record['taxon_ref']);
        $arr_data[] = array("identifier"   => str_replace(" ", "_", $taxon_record['taxon_name']) . "_silvics",
                            "source"       => @$taxon_record['html'],
                            "kingdom"      => @$taxon_record['ancestry']['kingdom'],
                            "phylum"       => @$taxon_record['ancestry']['phylum'],
                            "class"        => @$taxon_record['ancestry']['class'],
                            "order"        => @$taxon_record['ancestry']['order'],
                            "family"       => @$taxon_record['ancestry']['family'],
                            "genus"        => '',
                            "sciname"      => $sciname,
                            "reference"    => $taxon_refs, // formerly taxon_refs
                            "synonyms"     => array(),
                            "commonNames"  => $common_names,
                            "data_objects" => $arr_objects
                           );
        return $arr_data;
    }

    function get_objects($record, $arr_objects)
    {
        $texts = array();
        foreach($record['texts'] as $text)
        {
            $subjects['Native Range']                   = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
            $subjects['Climate']                        = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat";
            $subjects['Soils and Topography']           = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat";
            $subjects['Associated Forest Cover']        = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations";
            /* Reproduction and Early Growth */
            $subjects['Flowering and Fruiting']         = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Flowering and fruiting']         = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Flowering and Seed Production']  = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Flowering, Seed Production, and Dissemination'] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Seed Production']                          = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Seed Production and Dissemination']        = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Seedling Development']                     = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Seed Dissemination']                       = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Seedling Establishment and Development']   = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Site Preparation and Planting in Florida'] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Vegetative Reproduction']        = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Vegetable Reproduction']         = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            $subjects['Vegetative Propagation']         = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction";
            /* Sapling and Pole Stages to Maturity */
            $subjects['Growth and Yield']        = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Growth";
            $subjects['Rotting Habit']           = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Growth";
            $subjects['Rooting Habit']           = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Ecology";
            $subjects['Reaction to Competition'] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Ecology";
            $subjects['Damaging Agents']         = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Diseases";
            $subjects['Damaging Agent']          = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Diseases";
            /* Rooting Habit and Damaging Agents- No published information is available on rooting habit or damaging agents of Ogeechee tupelo. */
            $subjects['Population Differences']  = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Genetics";
            $subjects['Races and Hybrids']       = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Genetics";
            $subjects['Genetics']                = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Genetics";
            $subjects['Special Uses']            = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses";
            $subjects['brief summary']           = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology";
            $subjects['maps tab']                = "";
            $title = $text['title'];
            $title_no_tags = strip_tags($title);
            $title_to_use = $title_no_tags;
            if(in_array($title_no_tags, array("Native Range", "brief summary"))) $title_to_use = "";
            if(@$subjects[$title_no_tags] || $title_no_tags == "maps tab") //remove the @ when developing/debugging...
            {
                $texts[] = array("desc"     => $text['description'], 
                                 "subject"  => $subjects[$title_no_tags],
                                 "title"    => $title_to_use,
                                 "type"     => $title_no_tags);
            }
        }
        foreach($texts as $text)
        {
            $refs = self::get_references();
            $identifier     = str_replace(" ", "_", $record['taxon_name']) . "_silvics_" . str_ireplace(" ", "_", $text['type']);
            $description    = str_ireplace("</P>", "<BR/><BR/>", $text['desc']);
            $description    = strip_tags($description, "<I><BR>");
            $license        = "http://creativecommons.org/licenses/by-nc/3.0/";
            $agent          = $record['agents'];
            $rightsHolder   = "";
            $rights         = "";
            $location       = "";
            $dataType       = "http://purl.org/dc/dcmitype/Text";
            $mimeType       = "text/html";
            $title          = $text['title'];
            $subject        = $text['subject'];
            $source         = $record['html'];
            $mediaURL       = "";
            $refs           = $refs;
            $additionalInformation = "";
            if($text['type'] == "maps tab")
            {
                $description    = "";
                $subject        = "";
                $title          = "Native range of <i>" . $record['taxon_name'] . "</i>";
                $dataType       = "http://purl.org/dc/dcmitype/StillImage";
                $mimeType       = "image/jpeg";
                $mediaURL       = $text['desc'];
                $additionalInformation = "<subtype>map</subtype>";
            }
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $additionalInformation, $arr_objects);
        }
        return $arr_objects;
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $additionalInformation, $arr_objects)
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
                              "language"     => "en",
                              "additionalInformation" => $additionalInformation
                            );
        return $arr_objects;
    }

    function get_references()
    {
        $reference = "Burns, Russell M., and Barbara H. Honkala, technical coordinators. 1990. Silvics of North America: 1. Conifers; 2. Hardwoods. 
        Agriculture Handbook 654 (Supersedes Agriculture Handbook 271,Silvics of Forest Trees of the United States, 1965). 
        U.S. Department of Agriculture, Forest Service, Washington, DC. vol.2, 877 pp.";
        $refs = array();
        $refs[] = array("url" => 'http://www.na.fs.fed.us/spfo/pubs/silvics_manual/table_of_contents.htm', "fullReference" => $reference);
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
        $str = str_ireplace(array("    ", "   ", "   "), " ", trim($str));
        return $str;
    }

}
?>