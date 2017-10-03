<?php
namespace php_active_record;
// connector: [sf]
class SpeciesFileAPI
{
    // http://phasmida.speciesfile.org/ws.asmx/SimpleHierarchy
    // http://orthoptera.speciesfile.org/ws.asmx/TaxaPage?strTaxonNameID=1157791
    // http://orthoptera.speciesfile.org/ws.asmx/TCSbyID?strTaxonNameID=1200151
    // http://orthoptera.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=1157791
    
    const TAXA_PAGE_API = ".speciesfile.org/ws.asmx/TaxaPage?strTaxonNameID=";
    const TCS_BY_ID_API = ".speciesfile.org/ws.asmx/TCSbyID?strTaxonNameID=";
    const HIERARCHY_API = ".speciesfile.org/ws.asmx/SimpleHierarchy";
    const TAXON_URL = ".speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
    
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->media_ids = array();
        
        $this->groups = array();
        // $this->groups[] = array("name" => "orthoptera", "url" => "http://orthoptera" . self::HIERARCHY_API);
        // $this->groups[] = array("name" => "phasmida",   "url" => "http://phasmida"   . self::HIERARCHY_API);
        $this->groups[] = array("name" => "plecoptera", "url" => "http://plecoptera" . self::HIERARCHY_API);
        
        // $this->groups[] = array("name" => "phasmida",    "url" => "http://localhost/~eolit/cp/OSF/phasmida.xml");
        // $this->groups[] = array("name" => "plecoptera",  "url" => "http://localhost/~eolit/cp/OSF/plecoptera.xml");
        // $this->groups[] = array("name" => "orthoptera",  "url" => "http://localhost/~eolit/cp/OSF/orthoptera.xml");
        

        $this->excluded_ids["orthoptera"] = array("1100385", "1106701", "1107177", "1109180", "1110823", "1111414", "1112877", "1112881", "1114370", "1114392", "1114881", "1115689", "1122563", "1122663", "1126944", "1127006", "1139750");
        $this->excluded_ids["phasmida"]   = array("1216728", "1216731");

        $this->processed_id = "";
        
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 300, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->download_options["expire_seconds"] = false;
    }
    /*
    58,698 xml files in seagate
    next process common names , ecology, scrutity, etc...
    */

    function get_all_taxa()
    {
        /* self::archive_xml(); exit; */ // save XML files for each taxon for each Order locally before site goes dark
        self::process_taxa_per_order();
        $this->archive_builder->finalize(TRUE);
    }

    private function process_taxa_per_order()
    {
        $options = $this->download_options;
        $options["timeout"] = 900;
        $options["download_wait_time"] = 0; //debug normal operation = 20000000 is 20 seconds
        foreach($this->groups as $group)
        {
            echo "\n accessing " . $group["name"] . "...\n";
            if($contents = Functions::lookup_with_cache($group["url"], $options))
            {
                $xml = simplexml_load_string($contents);
                self::create_instances_from_taxon_object($xml->RootName, $group, "", "");
                $total = count($xml->TaxonName);
                $i = 0;
                $last_genus = "";
                $last_binomial = ""; // just for checking
                foreach($xml->TaxonName as $t)
                {
                    // $t{"ID"} = "1119253"; //debug
                    // $t{"ID"} = "1144043"; //debug
                    $i++;
                    
                    /* breakdown when caching
                    $m = 24500;
                    $cont = false;
                    // if($i >=  1    && $i < $m)    $cont = true;
                    if($i >=  $m   && $i < $m*2)  $cont = true;
                    // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                    // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                    // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                    if(!$cont) continue;
                    */
                    
                    if($val = @$this->excluded_ids[$group["name"]])
                    {
                        if(in_array($t{"ID"}, $val)) continue;
                    }
                    
                    if(($i % 1000) == 0) echo "\n $i of $total from " . $group["name"] . " \n";
                    
                    if($t{"Rank"} == "genus") $last_genus = $t{"name"};
                    if(isset($t{"FullName"})) $last_binomial = $t{"FullName"};
                    
                    self::create_instances_from_taxon_object($t, $group, $last_genus, $last_binomial);
                    self::parse_taxon_xml($t, $group);
                    
                    // break; //debug
                    // if($i == 5) break; //debug
                }
            }
        }
    }

    private function parse_taxon_xml($t, $group)
    {
        $this->processed_id = "";
        // $url = "http://orthoptera.speciesfile.org/ws.asmx/TaxaPage?strTaxonNameID=1132537"; //with many data //debug
        // $url = "http://orthoptera.speciesfile.org/ws.asmx/TaxaPage?strTaxonNameID=1110649"; //debug
        // $url = "http://plecoptera.speciesfile.org/ws.asmx/TaxaPage?strTaxonNameID=1157791"; //debug with audio object

        $url = "http://" . $group["name"] . self::TAXA_PAGE_API . $t{"ID"};
        if($contents = Functions::lookup_with_cache($url, $this->download_options))
        {
            if($xml = simplexml_load_string($contents))
            {
                if($html = $xml->TaxaPage->PageContent->CurrentTaxon->TaxonDetails)
                {
                    self::parse_images('<li class="IM">', $html, $t, $group);
                    self::parse_distribution_map('<li class="LO">', $html, $t, $group);
                    self::parse_vernacular($html, $t); // Common name(s): &nbsp;Graceful-winged Stick-insect</li>
                    self::parse_audio($html, $t, $group);
                    
                    // self::parse_type_specimen_info($html, $t, $group); //working but incomplete process, should get all specimen records
                }
            }
        }
        else
        {   // this happens when local copy is not available
            if($this->processed_id == $t{"ID"})
            {
                echo "\n investigate id [$this->processed_id] cannot access \n";
                return;
            }
            $this->processed_id = $t{"ID"};
            
            $f = fopen(DOC_ROOT . "/temp/" . $group["name"] . "OSF_invalid_xml.txt", "a");
            fwrite($f, $t{"ID"} . "\n");
            fclose($f);
        }
    }
    
    private function parse_citation($html)
    {
        if(preg_match("/Citations\:<ul>(.*?)<\/ul>/ims", $html, $arr))
        {
            $citation = "";
            if(preg_match_all("/<li class=\"CI\">(.*?)<\/li>/ims", $arr[1], $arr2))
            {
                foreach($arr2[1] as $line) $citation .= "$line<br>";
            }
            $citation = strip_tags($citation, "<a><br><i>");
            return $citation;
        }
    }
    
    private function parse_type_specimen_info($html, $t, $group)
    {
        if(preg_match("/Type specimen information\:<ul>(.*?)<\/ul>/ims", $html, $arr))
        {
            $rec = array();
            $rec["taxon_id"] = (string) $t{"ID"};
            if(preg_match_all("/<li class=\"SP\">(.*?)<\/li>/ims", $arr[1], $arr2))
            {
                $rec["description"] = "";
                foreach($arr2[1] as $line) $rec["description"] .= "$line<br>";
                $rec["media_id"] = $rec["taxon_id"] . "_typeinfo";
                $rec["title"] = "Type specimen information";
            }
            if(@$rec["description"])
            {
                $rec = array_map('trim', $rec);
                $rec["group"] = $group;
                $rec["source_url"] = "http://" . $group["name"] . self::TAXON_URL . $rec["taxon_id"];
                if(strpos($rec["description"], "see specimen data for details") === false) self::create_data_object($rec);
            }
        }
    }

    private function parse_audio($html, $t, $group)
    {
        if(preg_match("/Sound recordings\:(.*?)<\/ul>/ims", $html, $arr))
        {
            if(preg_match_all("/<li class=\"DE\">(.*?)<\/li>/ims", $arr[1], $arr2))
            {
                foreach($arr2[1] as $line)
                {
                    $rec = array();
                    $rec["taxon_id"] = (string) $t{"ID"};
                    if(preg_match("/href=\"(.*?)\"/ims", $line, $arr))
                    {
                        $rec["source_url"] = $arr[1];
                        $rec["source_url"] = str_ireplace("speciesfile.org/PlaySound.aspx", "speciesfile.org/Common/basic/PlaySound.aspx", $arr[1]);
                        if(preg_match("/&SoundID=(.*?)xxx/ims", $rec["source_url"]."xxx", $arr)) $rec["media_id"] = $arr[1];
                    }
                    if(preg_match("/<\/a>(.*?)xxx/ims", $line."xxx", $arr)) $rec["description"] = $arr[1];
                    $rec["title"] = "Sound Recording from " . $t{"Rank"} . " " . self::get_scientific_name($t);
                    $rec = self::get_other_html_info($rec["source_url"], $rec);
                    $rec = array_map('trim', $rec);
                    $rec["group"] = $group;
                    if($val = @$rec["sound_url"])
                    {
                        $rec["sound_url"] = "http://" . $group["name"] . ".speciesfile.org" . $val;
                        self::create_data_object($rec);
                    }
                }
            }
        }
    }
    
    private function parse_vernacular($html, $t)
    {
        if(preg_match("/Common name\(s\)\:(.*?)<\/li>/ims", $html, $arr))
        {
            $vernaculars = str_ireplace("&nbsp;", "", $arr[1]);
            // to separate those that are comma-separated common names
            $vernaculars = explode(",", $vernaculars);
            // to separate those that are semicolon-separated common names
            $vernaculars = implode(";", $vernaculars);
            $vernaculars = explode(";", $vernaculars);
            $vernaculars = array_map('trim', $vernaculars);
            foreach($vernaculars as $vernacular)
            {
                $v = new \eol_schema\VernacularName();
                $v->taxonID = (string) $t["ID"];
                $v->vernacularName = $vernacular;
                $v->language = ""; //language is not specified
                $vernacular_id = md5("$v->vernacularName|$v->language");
                if(!$v->vernacularName) continue;
                if(!isset($this->vernacular_name_ids[$vernacular_id]))
                {
                    $this->archive_builder->write_object_to_file($v);
                    $this->vernacular_name_ids[$vernacular_id] = 1;
                }
            }
        }
    }
    
    private function parse_images($str, $html, $t, $group)
    {
        if(preg_match_all("/$str(.*?)<\/li>/ims", $html, $arr))
        {
            foreach($arr[1] as $img)
            {
                $rec = array();
                $rec["taxon_id"] = (string) $t{"ID"};
                if(preg_match("/href=\"(.*?)\"/ims", $img, $arr2))               $rec["source_url"] = str_ireplace("Common/ShowImage.aspx", "Common/basic/ShowImage.aspx", $arr2[1]);
                if(preg_match("/src=\"(.*?)\"/ims", $img, $arr2))                $rec["media_url"] = $arr2[1] . "&Width=960";
                if(preg_match("/ImageID=(.*?)\&/ims", $rec["media_url"], $arr2)) $rec["media_id"] = $arr2[1];
                if(preg_match("/<\/a>(.*?)xxx/ims", $img."xxx", $arr2))          $rec["description"] = trim($arr2[1]);
                $rec = self::get_other_html_info($rec["source_url"], $rec);
                $rec = array_map('trim', $rec);
                $rec["group"] = $group;
                if(@$rec["media_url"]) self::create_data_object($rec);
            }
        }
    }
    
    private function get_other_html_info($url, $rec)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match("/<small>Source:(.*?)<\/small>/ims", $html, $arr)) $rec["rights_statement"] = trim($arr[1]);
            if(preg_match("/<span id=\"lbl_Mp3Link\"><a href=\"(.*?).MP3\"><\/span>/ims", $html, $arr)) $rec["sound_url"] = trim($arr[1]).".mp3";
            elseif(preg_match("/<span id=\"lbl_WavLink\"><a href=\"(.*?).WAV\"><\/span>/ims", $html, $arr)) $rec["sound_url"] = trim($arr[1]).".wav";
            if(preg_match("/\"Creative Commons (.*?)\"/ims", $html, $arr)) $rec["CreativeCommons"] = trim($arr[1]);
        }
        return $rec;
    }

    private function parse_distribution_map($str, $html, $t, $group)
    {
        if(preg_match_all("/$str(.*?)<\/li>/ims", $html, $arr))
        {
            // echo "\n";echo $html;echo "\n"; exit;
            foreach($arr[1] as $line)
            {
                if(strpos($line, "MapGen") === false) continue; // only distribution maps will be processed
                $rec = array();
                $rec["taxon_id"] = (string) $t{"ID"};
                $rec["media_url"] = "";
                if(preg_match("/href=\"(.*?)\"/ims", $line, $arr2)) $rec["source_url"] = $arr2[1];
                if(preg_match("/src=\"(.*?)\"/ims", $line, $arr2)) $rec["media_url"] = str_ireplace("Width=120", "Width=960", $arr2[1]);
                if(preg_match("/<\/a>(.*?)xxx/ims", $line."xxx", $arr2))
                {
                    $rec["description"] = "Note: Distribution maps are often incomplete due to the workload of entering data.<br>" . trim($arr2[1]) . "<br>";
                    $rec["description"] .= self::map_legend();
                    $rec["description"] = trim(str_ireplace(array(chr(9), chr(10), chr(13)), "", $rec["description"]));
                    $rec["description"] = Functions::remove_whitespace($rec["description"]);
                }
                $rec["media_id"] = "map_" . $rec["taxon_id"];
                $rec["title"] = "Distribution for " . $t{"Rank"} . " " . self::get_scientific_name($t);

                $rec = array_map('trim', $rec);
                $rec["group"] = $group;
                $rec["subtype"] = "Map";
                $rec["citation"] = self::parse_citation($html);
                $rec["CreativeCommons"] = "BY-SA"; //distribution maps is be default BY-SA, until discovered
                if($rec["media_url"]) self::create_data_object($rec);
                else
                {
                    echo "\n investigate no map";
                    echo "\n $str";
                    echo "\n $html";
                    echo "\n [" . $rec["taxon_id"] . "] [$group]\n";
                }
            }
        }
    }

    private function create_instances_from_taxon_object($t, $group, $last_genus, $last_binomial)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = (string) $t{"ID"};
        $taxon->taxonRank                   = (string) self::get_rank($t);
        $taxon->scientificName              = (string) self::get_scientific_name($t);
        
        if    ($t{"Status"} == "valid") $taxon->parentNameUsageID = $t{"AboveID"};
        elseif($t{"Status"} == "synonym")
        {
            $taxon->acceptedNameUsageID = $t{"AboveID"};
            if(in_array($taxon->taxonRank, array("species", "subspecies")))
            {
                $sciname = (string) trim($last_genus . " " . $t{"name"});
                // just checking
                $parts = explode(" ", $last_binomial);
                if($last_genus != trim($parts[0])) 
                {
                    echo "\n investigate 2 genuses not equal: [$last_genus] and [" . trim($parts[0]) . "]\n";
                    print_r($t);
                    print_r($taxon);
                }
            }
            else $sciname = (string) $t{"name"};
            $taxon->scientificName = $sciname;
        }
        elseif(in_array($t{"Status"}, array("nomen dubium", "nomen nudum", "temporary"))) return; // no instructions yet but more likely to ignore
        else
        {
            echo "\n investigate unknown status: " . $t{"Status"} . "\n";
            print_r($rec);
            return;
        }
        
        $taxon->furtherInformationURL   = "http://" . $group["name"] . self::TAXON_URL . $taxon->taxonID;
        $taxon->taxonomicStatus         = $t{"Status"};
        $taxon->taxonRemarks            = self::get_taxon_remarks($t);

        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxon_ids[$taxon->taxonID] = 1;
            $this->archive_builder->write_object_to_file($taxon);
        }
    }

    private function get_scientific_name($t)
    {
        if    (isset($t{"FullName"})) return $t{"FullName"};
        elseif(isset($t{"name"}))     return $t{"name"};
        else echo "\n investigate no sciname \n";
        print_r($t);
    }

    private function get_rank($t)
    {
        $rank = "";
        if(isset($t{"Rank"}))       $rank = $t{"Rank"};
        elseif(isset($t{"rank"}))   $rank = $t{"rank"};
        
        if($rank == "species group")        $rank = "species";
        if($rank == "superfamily group")    $rank = "superfamily";
        if($rank == "species subgroup")     $rank = "species";
        if($rank == "genus group")          $rank = "genus";
        if($rank == "subfamily group")      $rank = "subfamily";
        if($rank == "species series")       $rank = "series";
        
        if($rank) return $rank;
        
        if($t{"Status"} != "synonym")
        {
            echo "\n investigate no rank \n";
            print_r($t);
        }
    }
    
    private function get_taxon_remarks($t)
    {
        $string = "";
        foreach($t->PreviousName as $PreviousName)
        {
            if($string) $string .= ", " . $PreviousName;
            else $string = $PreviousName;
        }
        return $string;
    }

    private function create_data_object($rec)
    {
        if(in_array($rec["media_id"], $this->media_ids)) return;
        $this->media_ids[] = $rec["media_id"];
        $mr = new \eol_schema\MediaResource();
        if(@$rec["reference_ids"])  $mr->referenceID = implode("; ", $rec["reference_ids"]);
        if(@$rec["agent_ids"]) $mr->agentID = implode("; ", $rec["agent_ids"]);
        $mr->taxonID                = (string) $rec["taxon_id"];
        $mr->identifier             = (string) $rec["media_id"];
        $mr->language               = 'en';
        if(@$rec["media_url"])
        {
            $mr->accessURI          = $rec["media_url"];
            $mr->type               = "http://purl.org/dc/dcmitype/StillImage";
            if($val = Functions::get_mimetype($rec["media_url"])) $mr->format = $val;
            else                                                  $mr->format = "image/jpeg";
        }
        elseif($val = @$rec["sound_url"])
        {
            $mr->accessURI          = $val;
            $mr->type               = "http://purl.org/dc/dcmitype/Sound";
            $mr->format             = Functions::get_mimetype($val);
        }
        else
        {
            $mr->type               = "http://purl.org/dc/dcmitype/Text";
            $mr->format             = "text/html";
            $mr->CVterm             = "http://www.eol.org/voc/table_of_contents#TypeInformation";
            if(!$rec["description"]) return;
        }
        $mr->subtype = @$rec["subtype"];
        $mr->bibliographicCitation = @$rec["citation"];

        // $mr->rights = @$rec["rights_statement"];
        
        if(@$rec["rights_statement"]) $mr->Owner = @$rec["rights_statement"];
        else                          $mr->Owner = ucfirst($rec["group"]["name"]) . " Species File Online " . date("Y") . ".";
        
        if($val = @$rec["title"]) $mr->title = $val;
        if($license = self::valid_license($rec)) $mr->UsageTerms = $license;
        else
        {
            echo "\ninvalid license\n";
            print_r($rec); exit;
        }
        $mr->description            = (string) $rec["description"];
        $mr->furtherInformationURL  = @$rec["source_url"];
        $this->archive_builder->write_object_to_file($mr);
    }

    private function valid_license($rec)
    {
        if(@$rec["CreativeCommons"] == "BY-SA") return "http://creativecommons.org/licenses/by-sa/3.0/";
        else
        {
            echo "\nundefine license\n";
            print_r($rec); exit;
        }
    }

    // private function create_agent($agent)
    // {
    //     $agent_ids = array();
    //     $agents = explode(";", $agent);
    //     foreach($agents as $agentz)
    //     {
    //         $comma_separated = explode(",", $agentz);
    //         foreach($comma_separated as $agent)
    //         {
    //             $info = self::parse_agent($agent);
    //             $agent = $info["agent"];
    //             $role = $info["role"];
    //             if($agent)
    //             {
    //                 $r = new \eol_schema\Agent();
    //                 $r->term_name = $agent;
    //                 $r->agentRole = $role;
    //                 $r->identifier = md5($r->term_name . "|" . $r->agentRole);
    //                 $r->term_homepage = "";
    //                 $agent_ids[] = $r->identifier;
    //                 if(!in_array($r->identifier, $this->resource_agent_ids))
    //                 {
    //                    $this->resource_agent_ids[] = $r->identifier;
    //                    $this->archive_builder->write_object_to_file($r);
    //                 }
    //             }
    //         }
    //     }
    //     return array_unique($agent_ids);
    // }
    
    private function map_legend()
    {
        return '<table><tr><td align="right"><b>Geographic levels</b></td><td>&nbsp;&nbsp;</td><td><b>Land</b></td><td>&nbsp;&nbsp;</td><td><b>Ocean</b></td></tr><tr><td align="right">No data or not present</td><td></td>
        <td align="center"><img alt="land default; not present" height="20px" width="30px" src="http://phasmida.speciesfile.org/Common/img_logo/Land0.bmp"/></td><td></td>
        <td align="center"><img alt="ocean default" height="20px" width="30px" src="http://phasmida.speciesfile.org/Common/img_logo/sea0.bmp"/></td></tr>
        <tr><td align="right"><span>Level 1 present</span></td><td></td><td align="center"><img alt="TDWG level 1 land color" height="20px" src="http://phasmida.speciesfile.org/Common/img_logo/Land1.bmp" width="30px"/></td><td></td>
        <td align="center"><img alt="TDWG level 1 ocean color" height="20px" src="http://phasmida.speciesfile.org/Common/img_logo/sea1.bmp" width="30px"/></td></tr>
        <tr><td align="right"><span>Level 2 present</span></td><td></td><td align="center"><img alt="TDWG level 2 land color" height="20px" src="http://phasmida.speciesfile.org/Common/img_logo/Land2.bmp" width="30px"/></td><td></td>
        <td align="center"><img alt="TDWG level 2 ocean color" height="20px" src="http://phasmida.speciesfile.org/Common/img_logo/sea2.bmp" width="30px"/></td></tr>
        <tr><td align="right"><span>Level 3 present</span></td><td></td><td align="center"><img alt="TDWG level 2 land color" height="20px" src="http://phasmida.speciesfile.org/Common/img_logo/Land3.bmp" width="30px"/></td><td></td>
        <td align="center"><img alt="TDWG level 2 ocean color" height="20px" src="http://phasmida.speciesfile.org/Common/img_logo/sea3.bmp" width="30px"/></td></tr>
        <tr><td colspan="5" align="center"><i>Blue shades locate oceanic islands<br />included in the distribution.</i></td></tr></table>';
    }

    private function archive_xml() //utility
    {
        $ids = array();
        foreach($this->groups as $group)
        {
            echo "\n accessing " . $group["name"] . "...\n";
            if($contents = Functions::lookup_with_cache($this->temp_dir . $group["name"] . ".xml", $this->download_options))
            {
                $xml = simplexml_load_string($contents);
                $total = count($xml->TaxonName);
                $i = 0;
                foreach($xml->TaxonName as $t)
                {
                    $id = (string) $t{"ID"};
                    if(in_array($id, $this->excluded_ids)) continue;
                    // if(intval($id) < 1112214) continue; // debug
                    if(!isset($ids[$id])) $ids[$id] = 1;
                    else echo "\n investigate id was repeated \n";
                    $i++;
                    echo "\n $i of $total from " . $group["name"] . " \n";
                    self::save_xml_to_local($t, $group);
                }
            }
        }
        echo "\n\n XML archiving done... \n\n";
    }
    
    private function save_xml_to_local($t, $group) //utility
    {
        $saved = false;
        $services = array();
        $services[] = array("type" => "page", "url" => "http://" . $group["name"] . self::TAXA_PAGE_API . $t{"ID"});
        $services[] = array("type" => "taxon", "url" => "http://" . $group["name"] . self::TCS_BY_ID_API . $t{"ID"});
        foreach($services as $service)
        {
            echo "\n url: [" . $service["url"] . "]\n";
            if($xml_content = Functions::lookup_with_cache($service["url"], $this->download_options)) $saved = true;
            else
            {
                $f = fopen($this->temp_dir . "/invalid_xml.txt", "a");
                fwrite($f, $t{"ID"} . "\n");
                fclose($f);
            }
        }
        echo "\n [$saved]\n " . $t{"ID"};
    }

    function some_stats() //utility
    {
        $records = array();
        $i = 0;
        foreach($this->groups as $group)
        {
            echo "\n" . $group["url"];
            if($contents = Functions::lookup_with_cache($group["url"], $this->download_options))
            {
                $xml = simplexml_load_string($contents);
                foreach($xml->TaxonName as $t)
                {
                    $status = (string) $t{"Status"};
                    $records[$status][] = $status;
                    $i++;
                }
            }
        }
        // print "\n\n valid: "        . count($records["valid"]);
        // print "\n synonym: "        . count($records["synonym"]);
        // print "\n nomen dubium: "   . count($records["nomen dubium"]);
        // print "\n nomen nudum: "    . count($records["nomen nudum"]);
        // print "\n temporary: "      . count($records["temporary"]);
        $statuses = array(); $k = 0;
        foreach($records as $status => $value)
        {
            print "\n $status: " . count($records[$status]);
            $k += count($records[$status]);
        }
        print "\n total: $i";
        print "\n total: $k\n";
        
        
        exit("\n\n");
    }

}
?>