<?php
namespace php_active_record;
/* connector: [221]
Connector scrapes the site: 
and assembles the information and generates the EOL DWC-A.
*/
class RosarioBeachMarineLabAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('download_wait_time' => 500000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false;
        $this->url['main'] = "http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/";
        $this->url['list'] = $this->url['main'] . "Species_Index.html";
        $this->url['biblio'] = $this->url['main'] . "Annotated_Bibliography.html";
    }

    function get_all_taxa($resource_id)
    {
        $this->references = self::parse_references();
        $exclude = array("http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/http://wallawalla.edu", "http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/Cnidaria/Class-Scyphozoa/Order-Semaeostomeae/Family-Ulmaridae/Aurelia_aurita.html", //synonym of Aurelia_labiata
                         "http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/Arthropoda/Crustacea/Maxillopoda/Cirripedia/Loxothylacus%20panopaei.html");
        if($html = Functions::lookup_with_cache($this->url['list'], $this->download_options))
        {
            $pos = stripos($html, '<a NAME="A">');
            $html = trim(substr($html, $pos+11, strlen($html)));
            if(preg_match_all("/href=\"(.*?)\"/ims", $html, $arr1))
            {
                $g = 0;
                foreach($arr1[1] as $path)
                {
                    $g++;
                    if(($g % 100) == 0) echo "\n" . number_format($g);
                    
                    if(is_numeric(stripos($path, '_key.html'))) continue;
                    if(is_numeric(stripos($path, '_key1.html'))) continue;
                    if(is_numeric(stripos($path, '-key.html'))) continue;
                    if(is_numeric(stripos($path, 'PhylumNemerteaKey.html'))) continue;

                    //manual adjustments
                    $path = str_ireplace("Triopha_catalinae.html", "Triopha_Catalinae.html", $path);
                    $path = str_ireplace("Chelysoma_productum.html", "Chelyosoma_productum.html", $path);

                    $url = $this->url['main'] . $path;
                    if(in_array($url, $exclude)) continue;

                    //debug
                    // $url = "http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/Nemertea/Paranemertes_peregrina.html";
                    // $url = "http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/Arthropoda/Crustacea/Malacostraca/Eumalacostraca/Eucarida/Decapoda/Brachyura/Family_Cancridae/Cancer_gracilis.html";
                    // $url = "http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/Arthropoda/Crustacea/Malacostraca/Eumalacostraca/Peracarida/Lophogastrida/Neognathophausia_ingens.html";
                    //has both refs and scientific articles
                    // $url = "http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/Ctenophora/Pleurobrachia_bachei.html";

					$options = $this->download_options;                    
                    if($html = Functions::lookup_with_cache($url, $options))
                    {
						$html = str_replace(array("\n"), " ", $html); //needs this to get the correct family name e.g. http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/Arthropoda/Crustacea/Malacostraca/Eumalacostraca/Eucarida/Decapoda/Caridea/Family_Oplophoridae/Acanthephyra_curtirostris.html

                        $rec = array();
                        $rec['taxon_id'] = pathinfo($url, PATHINFO_BASENAME);
                        $rec['source'] = $url;
                        if(preg_match("/<h2(.*?)<\/h2>/ims", $html, $arr2))
						{
							$rec['sciname'] = self::clean_string("<h2" . $arr2[1]);
						}
                        else
						{
							echo "\n no sciname [$url]\n";
							return;
						}
                        if(preg_match("/Common name\(s\):(.*?)</ims", $html, $arr2)) $rec['comnames'] = self::clean_string($arr2[1]);
                        if(preg_match("/Synonyms:(.*?)<\/td>/ims", $html, $arr2))    $rec['synonyms'] = self::clean_string($arr2[1]);

                        if(preg_match("/Phylum (.*?)</ims", $html, $arr2))    $rec['ancestry']['phylum'] = self::clean_string($arr2[1]);
                        if(preg_match("/Class (.*?)</ims", $html, $arr2))     $rec['ancestry']['class']  = self::clean_string($arr2[1]);

                        if(preg_match_all("/Order (.*?)</ms", $html, $arr2))      $rec['ancestry']['order']  = self::clean_string($arr2[1][0]);
                        elseif(preg_match_all("/>Order (.*?)</ms", $html, $arr2)) $rec['ancestry']['order']  = self::clean_string($arr2[1][0]);
                        elseif(preg_match_all("/ Order (.*?)</ms", $html, $arr2)) $rec['ancestry']['order']  = self::clean_string($arr2[1][0]);
						
                        if(preg_match_all("/Family (.*?)</ims", $html, $arr2)) 
						{
							$rec['ancestry']['family'] = self::clean_string($arr2[1][0]);
						}

                        if(preg_match("/Description:(.*?)<p>/ims", $html, $arr2))        $rec['txt']['desc']         = self::clean_string($arr2[1]);
                        if(preg_match("/Similar Species:(.*?)<p>/ims", $html, $arr2))    $rec['txt']['lookalikes']   = self::clean_string($arr2[1]);
                        if(preg_match("/Geographical Range:(.*?)<p>/ims", $html, $arr2)) $rec['txt']['distribution'] = self::clean_string($arr2[1]);
                        if(preg_match("/Depth Range:(.*?)<p>/ims", $html, $arr2))        $rec['txt']['depth range']  = self::clean_string($arr2[1]);
                        if(preg_match("/Habitat:(.*?)<p>/ims", $html, $arr2))            $rec['txt']['habitat']      = self::clean_string($arr2[1]);
                        if(preg_match("/Natural History:(.*?)<hr/ims", $html, $arr2))    $rec['txt']['biology']      = self::clean_string($arr2[1]);
                        
                        $rec['images'] = self::parse_images_from_table($html);
                        $rec['img'] = self::get_images_from_GenNotesObservations($html);
                        if(preg_match_all("/src=\"(.*?)\"/ims", $html, $arr2)) $rec['all_images'] = $arr2[1];

                        $rec['editors']    = self::get_page_editors($html);
                        $rec['references'] = self::assign_reference($html);

						/* to be used when debugging
						if($url == "http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/Annelida/Alvinellidae/Paralvinella_palmiformis.html")
						{
	                    	print_r($rec);
						}
						*/
                        self::create_archive($rec);
                    }
                    // break; //debug
                }
            }
        }
        $this->archive_builder->finalize(TRUE);
    }
    
    private function get_images_from_GenNotesObservations($html)
    {
        //parse images from "General Notes and Observations:  Locations, abundances, unusual behaviors:"
        if(preg_match("/unusual behaviors:(.*?)Authors and Editors of/ims", $html, $arr2))
        {
            if(is_numeric(stripos($arr2[1], '<table'))) //e.g. Paranemertes_peregrina.html
            {
                if(preg_match("/xxx(.*?)<table/ims", "xxx".$arr2[1], $arr3))
                {
                    $sub_html = $arr3[1];
                    $temp = explode("<p>", $sub_html);
                    $reks = array();
                    foreach($temp as $t)
                    {
                        $t = str_ireplace(array("\n", "&nbsp;", "<br>"), " ", $t);
                        $t = str_ireplace(array("   ", "  "), " ", $t);
                        $rek = array();
                        if(preg_match("/src=\"(.*?)\"/ims", $t, $arr2)) $rek['src'] = $arr2[1];
                        if($val = trim(strip_tags($t))) $rek['caption'] = $val;
                        if($rek) $reks[] = $rek;
                    }
                    $final = array();
                    foreach($reks as $rek)
                    {
                        $caption = "";
                        if($val = @$rek['src']) $src = $val;
                        if($val = @$rek['caption'])
                        {
                            $caption = $val;
                            $agent = self::get_photographer_from_caption($val);
                        }
                        if(isset($src) && $caption) $final[] = array("src" => $src, "caption" => $caption, "agent" => $agent);
                    }
                    if($final) return $final;
                }
            }
            else //e.g. Pododesmus_macroschisma.html
            {
                $final = array();
                $sub_html = $arr2[1];
                $temp = explode("<p>", $sub_html);
                foreach($temp as $t)
                {
                    if(is_numeric(stripos($t, 'src='))) // 'img src'
                    {
                        $t = strip_tags($t, "<img><br>");
                        $t = trim(str_ireplace(array("\n", "&nbsp;"), " ", $t));
                        $t = explode("<br>", $t);
                        $t = array_map('trim', $t);
                        $t = array_filter($t);
                        $rek = array();
                        foreach($t as $s)
                        {
                            if(preg_match("/src=\"(.*?)\"/ims", $s, $arr2))
                            {
                                $rek['src'] = $arr2[1];
                                // if($val = trim(strip_tags($s))) @$rek['caption'] .= " " . $val;
                            }
                            else @$rek['caption'] .= " " . $s;
                        }
                        if($val = trim(@$rek['caption']))
                        {
                            $rek['caption'] = $val;
                            $rek['agent'] = self::get_photographer_from_caption($val);
                        }
                        if($rek) $final[] = $rek;
                    }
                    else
                    {
                        $t = trim(str_ireplace(array("\n", "&nbsp;"), " ", $t));
                        if($t = trim(strip_tags($t)))
                        {
                            @$rek['caption'] .= " " . $t;
                            if($val = trim(@$rek['caption']))
                            {
                                $rek['caption'] = $val;
                                $rek['agent'] = self::get_photographer_from_caption($val);
                                $final[] = $rek;
                            }
                        }
                    }
                }
                $final = self::adjust_final_list($final);
                return $final;
            }
        }
        return false;
    }
    
    private function adjust_final_list($list)
    {
        /*
        [img] => Array
                (
                    [0] => Array
                        (
                            [src] => Tectura_fenestrataInsideDLC2008-12s.jpg
                        )
                    [1] => Array
                        (
                            [src] => Tectura_fenestrataInsideDLC2008-12s.jpg
                            [caption] => Inside view of the same two shells above.  Note how the inside is quite dark, blue-gray, with a large darker rown bblotch at the apex.
                            [agent] => 
                        )
                )
        */
        $arr = array();
        foreach($list as $img) $arr[@$img['src']] = array('caption' => @$img['caption'], 'agent' => @$img['agent']);
        $final = array();
        foreach($arr as $key => $img) $final[] = array('src' => $key, 'caption' => $img['caption'], 'agent' => $img['agent']);
        return $final;
    }
    
    private function get_page_editors($html)
    {
        $editors = array();
        $html = str_ireplace("\n", " ", $html);
        if(preg_match("/Authors and Editors of Page:(.*?)<script/ims", $html, $arr))
        {
            $temp = explode("<br>", $arr[1]);
            foreach($temp as $t)
            {
                $str = "";
                if    (preg_match("/(.*?)Created original page/ims", "".$t, $arr2)) $str = trim($arr2[1]);
                elseif(preg_match("/(.*?)Updated page with/ims", "".$t, $arr2)) $str = trim($arr2[1]);
                elseif(preg_match("/page developed by(.*?)\(/ims", $t, $arr2))  $str = trim($arr2[1]);
                if($str)
                {
                    $str = self::remove_parenthesis($str);
                    $str = str_ireplace(":", "", $str);
                    if($val = self::clean_string($str)) $editors[] = $val;
                }
                // <br>Dave Cowles (2009):&nbsp; Created original page
                // <br>Jonathan Cowles (2007):&nbsp; Updated page with CSS
                // <br>CSS coding for page developed by Jonathan Cowles (2007)
            }
        }
        return $editors;
    }

    private function create_archive($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxon_id'];
        $taxon->scientificName          = utf8_encode($rec['sciname']);
        $taxon->furtherInformationURL   = $rec['source'];
        $taxon->phylum  = @$rec['ancestry']['phylum'];
        $taxon->class   = @$rec['ancestry']['class'];
        $taxon->order   = @$rec['ancestry']['order'];
        $taxon->family  = @$rec['ancestry']['family'];
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        if(@$rec['comnames']) self::generate_comnames($rec);
        if(@$rec['synonyms']) self::generate_synonyms($rec);

        // for text objects
        $param = array("taxon_id" => $rec['taxon_id'], "source" => $rec['source'], "agent_ids"  => self::generate_agent_ids($rec['editors'], 'editor'));
        if($val = @$rec['txt']['desc'])
        {
            $param["identifier"] = $rec['taxon_id']."_desc";
            $param["desc"]       = $val;
            $param["subject"]    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description";
            $param["reference_ids"] = self::generate_reference_ids($rec['references']);
            self::generate_text_object($param);
        }
        $param["reference_ids"] = array();
        if($val = @$rec['txt']['lookalikes'])
        {
            $param["identifier"] = $rec['taxon_id']."_lookalikes";
            $param["desc"]       = "<b>How to Distinguish from Similar Species:</b> " . $val;
            $param["subject"]    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#LookAlikes";
            self::generate_text_object($param);
        }
        if($val = @$rec['txt']['biology'])
        {
            $param["identifier"] = $rec['taxon_id']."_biology";
            $param["desc"]       = "<b>Biology/Natural History:</b> " . $val;
            $param["subject"]    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
            self::generate_text_object($param);
        }
        if($val = @$rec['txt']['habitat'])
        {
            $param["identifier"] = $rec['taxon_id']."_habitat";
            $param["desc"]       = $val;
            $param["subject"]    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat";
            self::generate_text_object($param);
        }
        if($val = @$rec['txt']['distribution'])
        {
            $param["identifier"] = $rec['taxon_id']."_distribution";
            $param["desc"]       = "<b>Geographical Range:</b> " . $val;
            $param["subject"]    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
            self::generate_text_object($param);
        }
        if($val = @$rec['txt']['depth range'])
        {
            $param["identifier"] = $rec['taxon_id']."_depth";
            $param["desc"]       = "<b>Depth Range:</b> " . $val;
            $param["subject"]    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat";
            self::generate_text_object($param);
        }
        
        // for image objects
        $param = array("taxon_id" => $rec['taxon_id'], "source" => $rec['source'], "type" => "http://purl.org/dc/dcmitype/StillImage");
        foreach($rec['images']['src'] as $src) $rec['img'][] = array("src" => $src, "caption" => @$rec['images']['caption'], "agent" => @$rec['images']['agent']);
        $rec['img'] = self::append_all_other_images($rec['all_images'], $rec['img']);
        if($val = @$rec['img'])
        {
            foreach($val as $i)
            {
                if($param["identifier"] = $i['src'])
                {
                    $param["desc"]       = @$i['caption'];
                    $param["mediaURL"]   = pathinfo($param['source'], PATHINFO_DIRNAME) . "/" . $i['src'];
                    $param["agent_ids"]  = self::generate_agent_ids(array(@$i['agent']), 'photographer');
                    $param['format']     = Functions::get_mimetype($i['src']);
                    self::generate_object($param);
                }
            }
        }
    }

    private function append_all_other_images($all_images, $images)
    {
        $all_defined_src = array();
        foreach($images as $i) $all_defined_src[] = $i['src'];
        foreach($all_images as $src)
        {
            if(!in_array($src, $all_defined_src)) $images[] = array("src" => $src, "caption" => "", "agent" => "");
        }
        return $images;
    }

    private function generate_reference_ids($refs)
    {
        $reference_ids = array();
        foreach($refs as $ref)
        {
            $val = false;
            if($val = @$this->references[$ref]) $with_url = true; //ref found in Annotated_Bibliography.html
            else                                                  //ref not found in Annotated_Bibliography.html; e.g. http://www.wallawalla.edu/academics/departments/biology/rosario/inverts/Arthropoda/Crustacea/Malacostraca/Eumalacostraca/Peracarida/Lophogastrida/Neognathophausia_ingens.html
            {
                $with_url = false;
                if(str_word_count($ref) > 15) $val = $ref;
            }

            if($val)
            {
                $r = new \eol_schema\Reference();
                $r->full_reference  = $val;
                $r->identifier      = md5($r->full_reference);
                if($with_url) $r->uri = $this->url['biblio'] . "#" . $ref;
                $reference_ids[] = $r->identifier;
                if(!isset($this->reference_ids[$r->identifier]))
                {
                    $this->reference_ids[$r->identifier] = '';
                    $this->archive_builder->write_object_to_file($r);
                }
            }
        }
        return $reference_ids;
    }
    
    private function generate_agent_ids($agents, $role)
    {
        $agent_ids = array();
        foreach($agents as $agent)
        {
            if(!$agent) continue;
            $r = new \eol_schema\Agent();
            $r->term_name       = $agent;
            $r->agentRole       = $role;
            $r->identifier      = md5("$r->term_name|$r->agentRole");
            // $r->term_homepage   = '';
            $agent_ids[] = $r->identifier;
            if(!isset($this->agent_ids[$r->identifier]))
            {
               $this->agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }

    private function generate_text_object($rek)
    {
        $rek['type']    = "http://purl.org/dc/dcmitype/Text";
        $rek['format']  = "text/html";
        self::generate_object($rek);
    }
    
    private function generate_object($o)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $o['taxon_id'];
        $mr->identifier     = $o['identifier'];
        $mr->type           = $o['type'];
        $mr->language       = 'en';
        $mr->format         = $o['format'];
        $mr->accessURI      = @$o['mediaURL'];
        $mr->CVterm         = @$o['subject'];
        $mr->description    = $o['desc'];
        $mr->Owner          = "Rosario Beach Marine Laboratory";
        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/2.0/";
        if($reference_ids = @$o['reference_ids']) $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids     = @$o['agent_ids'])     $mr->agentID = implode("; ", $agent_ids);
        $mr->furtherInformationURL = $o['source'];
        
        if(!isset($this->obj_ids[$mr->identifier]))
        {
            $this->obj_ids[$mr->identifier] = '';
            $this->archive_builder->write_object_to_file($mr);
        }
    }

    private function generate_synonyms($rec)
    {
        $names = explode(",", $rec['synonyms']);
        $names = array_map('trim', $names);
        foreach($names as $name)
        {
            if(!$name) continue;
            $syn_id = md5($name);
            if(isset($this->taxon_ids[$syn_id])) continue;
            $this->taxon_ids[$syn_id] = '';
            
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID             = $syn_id;
            $taxon->scientificName      = utf8_encode($name);
            $taxon->acceptedNameUsageID = $rec['taxon_id'];
            $taxon->taxonomicStatus     = 'synonym';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }

    private function generate_comnames($rec)
    {   //some names are separated by , or ; or .
        $str = str_ireplace(array(".",";"), ",", $rec['comnames']);
        $str = self::remove_parenthesis($str);
        $names = explode(",", $str);
        $names = array_map('trim', $names);
        $names = array_filter($names); //remove null arrays
        $names = array_unique($names); //make unique
        $names = array_values($names); //reindex key
        foreach($names as $name)
        {
            if(!$name) continue;
            if(isset($this->comnames[$name])) continue;
            $this->comnames[$name] = '';
            
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $rec['taxon_id'];
            $v->vernacularName  = $name;
            $v->language        = 'en';
            $this->archive_builder->write_object_to_file($v);
        }
    }

    private function parse_references()
    {
        $final = array();
        if($html = Functions::lookup_with_cache($this->url['biblio'], $this->download_options))
        {
            $temp = explode('<a NAME="', $html);
            foreach($temp as $t)
            {
                if(preg_match("/xxx(.*?)\"/ims", "xxx".$t, $arr))
                {
                    if($title = self::clean_string($arr[1]))
                    {
                        if(preg_match("/\">(.*?)xxx/ims", $t."xxx", $arr)) $final[$title] = self::clean_string($arr[1]);
                    }
                }
            }
        }
        return $final;
    }
    
    private function assign_reference($html)
    {
        if(preg_match("/references:(.*?)<hr/ims", $html, $arr)) $html = $arr[1];

        // process scientific articles
        $scientific_articles = array();
        if(preg_match("/scientific articles:(.*?)xxx/ims", $html."xxx", $arr))
        {
            $html2 = str_replace(array("\n", "&nbsp;"), " ", $arr[1]);
            $html2 = str_ireplace(array("Dichotomous Keys:", "General References:", "Scientific Articles:", "Web sites:"), "", $html2);
            $html2 = Functions::remove_whitespace($html2);
            if(preg_match_all("/<p>(.*?)<\/p>/ims", $html2, $arr))
            {
                $temp = array_map('strip_tags', $arr[1]);
                $temp = array_map('trim', $temp);
                $temp = array_filter($temp); //remove null arrays
                $temp = array_values($temp); //reindex key
                if($temp) $scientific_articles = $temp;
            }
        }
        // end scientific articles


        //1st option: the one with #, e.g. Cancer_gracilis.html
        $option1 = array();
        if(preg_match_all("/html\#(.*?)\"/ims", $html, $arr)) $option1 = array_map('urldecode', $arr[1]);
        if($option1) return array_merge($option1, $scientific_articles);
        
        //2nd option: the one without hyperlinks, e.g. Paranemertes_peregrina.html
        $temp = explode("<br>&nbsp;", $html);
        $temp = array_map('strip_tags', $temp);
        $final = array();
        foreach($temp as $t)
        {
            $t = explode("\n", $t);
            $t = trim($t[0]);
            $final[$t] = '';
        }
        $option2 = array_keys($final);
        $option2 = array_filter($option2); //remove null values
        if($option2) return array_merge($option2, $scientific_articles);
        
        //3rd option: the one with actual reference body, e.g. Neognathophausia_ingens.html
        $temp = explode("<p>", $html);
        $temp = array_map('strip_tags', $temp);
        $final = array();
        foreach($temp as $t)
        {
            $t = self::clean_string($t);
            $t = str_ireplace(array("Dichotomous Keys:", "General References:", "Scientific Articles:", "Web sites:"), "", $t);
            $final[] = trim($t);
        }
        $final = array_map('trim', $final);
        $option3 = array_filter($final); //remove null values
        if($option3)
        {
            $temp = array_merge($option3, $scientific_articles);
            $temp = array_filter($temp); //remove null arrays
            $temp = array_unique($temp); //make unique
            $temp = array_values($temp); //reindex key
            return $temp;
        }
        
        return array();
    }

    private function parse_images_from_table($html)
    {
        if(preg_match("/<table(.*?)<\/table>/ims", $html, $arr))
        {
            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $arr2))
            {
                $rek = array();
                foreach($arr2[1] as $t)
                {
                    if(is_numeric(stripos($t, 'Common name(s)'))) continue;
                    if(is_numeric(stripos($t, 'Phylum '))) continue;
                    if(is_numeric(stripos($t, 'Family '))) continue;
                    
                    if(preg_match_all("/src=\"(.*?)\"/ims", $t, $arr3)) $rek['src'] = $arr3[1];
                    else @$rek['caption'] .= strip_tags($t, "<i>");
                }
                if($val = @$rek['caption'])
                {
                    $rek['caption'] = trim(str_ireplace(array("\n", "&nbsp;"), " ", $val));
                    $rek['caption'] = trim(str_ireplace(array("   ", "  "), " ", $rek['caption']));
                    $rek['agent'] = self::get_photographer_from_caption($rek['caption']);
                }
                return $rek;
            }
        }
        return false;
    }
    
    private function get_photographer_from_caption($caption)
    {
        if(preg_match("/Photo by(.*?)\,/ims", $caption, $arr3))
        {
            $str = trim(str_ireplace(":", "", $arr3[1]));
            $arr = explode(" ", $str);
            return trim(@$arr[0] . " " . @$arr[1]);
        }
        return false;
    }

    private function clean_string($string)
    {
        $string = trim(strip_tags(str_ireplace(array("\n"), " ", $string)));
        $string = trim(str_ireplace(array("&nbsp;"), " ", $string));
        $string = trim(str_ireplace(array("  "), " ", $string));
		$string = self::remove_parenthesis($string);
        return $string;
    }
    
    private function remove_parenthesis($string)
    {
        $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
        $string = trim(preg_replace('/\s*\[[^)]*\]/', '', $string)); //remove brackets
        return $string;
    }

}
?>