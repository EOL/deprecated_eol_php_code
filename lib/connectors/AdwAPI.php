<?php
namespace php_active_record;
/* connector: [adw_new] ADW comprehensive
eol.org/content_partners/8
eol.org/content_partners/650
*/

class AdwAPI
{
    function __construct($folder = null)
    {
        exit("\nThis one seems obsolete already.\n");
        if($folder) {
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 172800, 'download_attempts' => 2, 'delay_in_minutes' => 1);
        $this->download_options["expire_seconds"] = false;
        $this->domain                            = "http://animaldiversity.ummz.umich.edu";
        $this->adw_page["start"]                 = $this->domain . "/accounts/Animalia/";
        $this->adw_page["contributor_galleries"] = $this->domain . "/collections/contributors/";
        $this->ranks = array("kingdom", "phylum", "class", "order", "family", "genus");
        $this->valid_licenses = array("http://creativecommons.org/licenses/by-nc-sa/3.0/", "http://creativecommons.org/licenses/by-sa/3.0/", "http://creativecommons.org/licenses/by-nc/3.0/", "http://creativecommons.org/licenses/by/3.0/", "http://creativecommons.org/licenses/publicdomain/", "http://creativecommons.org/licenses/by-nc/2.5/", "http://creativecommons.org/licenses/by-sa/2.5/");
        $this->uri_list = "http://localhost/~eolit/cp/ADW/for connector/ADW_measurements_values_list temp.txt";
        $this->object_ids = array();
        $this->dump_file = DOC_ROOT . "temp/adw_wrong_urls.txt";
        $this->debug = array();
    }

    function get_all_taxa()
    {
        $this->uris = self::get_uris(); // like the get_uris() in GBIF country nodes, assembles all URI's 
        // $taxa["Gadus morhua"] = array("rank" => "species", "source" => "/accounts/Gadus_morhua/", "vernacular" => ""); //debug
        $taxa = self::assemble_taxa_list();
        self::process_taxa($taxa);
        self::prepare_image_data("maps");
        self::prepare_image_data("pictures");
        self::prepare_image_data("sounds");
        self::prepare_contributor_galleries(); // to access all media from all contributors, may wait for SPG's go signal before using this
        
        /* sample way to access all media from one contributor - not part of normal operation
        $taxa_with_media = self::get_taxa_with_media("http://animaldiversity.ummz.umich.edu/collections/contributors/phil_myers/");
        self::get_media_data($taxa_with_media, "pictures");
        */
        
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }

    private function process_taxa($taxa)
    {
        /*  [Platyhelminthes] => Array
            (
                [rank] => Unspecified
                [source] => /accounts/Platyhelminthes/
                [vernacular] => flatworms
            )*/
        foreach($taxa as $taxon_name => $rec) self::add_taxon($taxon_name, $rec);
    }
    
    private function add_taxon($taxon_name, $rec)
    {
        $taxon_id = str_replace(" ", "_", $taxon_name);
        if(isset($this->taxon_ids[$taxon_id])) return;
        $t = new \eol_schema\Taxon();
        $t->taxonID = $taxon_id;
        $t->scientificName = $taxon_name;
        $t->furtherInformationURL = $this->domain . $rec["source"];

        if($ancestry = self::get_ancestry($taxon_id, $t->furtherInformationURL))
        {
            foreach($this->ranks as $rank)
            {
                if($val = @$ancestry[$rank]) $t->$rank = $val;
            }
        }

        $this->archive_builder->write_object_to_file($t);
        $this->taxon_ids[$taxon_id] = '';
        
        $rec["taxon_id"] = $taxon_id;
        if($val = @$rec["vernacular"]) self::add_vernacular($val, $rec);
        self::add_articles($rec);
    }

    private function add_articles($rec)
    {
        $url = $this->domain . $rec["source"];
        $page = self::parse_adw_taxon_page($url);
        foreach($page as $topic => $r)
        {
            if(in_array($topic, array("contributors", "references", "cite", "reference_ids", "agent_ids"))) continue;
            if($paragraphs = $r["articles"]) self::special_text_object($rec, $url, $paragraphs, $page, $topic); // no subtopic
        }
        //special text objects
        if($paragraphs = @$page["reproduction"]["Breeding interval"]) self::special_text_object($rec, $url, $paragraphs, $page, "reproduction", "Breeding interval");
        if($paragraphs = @$page["reproduction"]["Breeding season"])   self::special_text_object($rec, $url, $paragraphs, $page, "reproduction", "Breeding season");
        self::add_structured_data($page, $rec["taxon_id"]);
    }

    private function special_text_object($rec, $url, $paragraphs, $page, $topic, $subtopic = false)
    {
        $article = "";
        foreach($paragraphs as $text)
        {
            if($article) $article .= "<p>" . $text;
            else $article = $text;
        }
        $identifier = $rec["taxon_id"]."/$topic";
        if($subtopic) $identifier .= "_" . str_replace(" ", "_", $subtopic);
        $o = array("taxon_id" => $rec["taxon_id"], "identifier" => $identifier, "dataType" => "http://purl.org/dc/dcmitype/Text", 
            "mimeType"              => "text/html",
            "source"                => $url,
            "subject"               => self::get_subject($topic),
            "description"           => self::clean_article($article, $url),
            "rights"                => "© " . Date("Y") . " The Regents of the University of Michigan and its licensors",
            "rightsHolder"          => "The Regents of the University of Michigan and its licensors",
            "license"               => "http://creativecommons.org/licenses/by-nc-sa/3.0/",
            "bibliographicCitation" => $page["cite"],
            "reference_ids"         => $page["reference_ids"],
            "agent_ids"             => $page["agent_ids"]);
        self::add_object($o);
    }
    
    private function add_structured_data($data, $taxon_id)
    {
        $this->measurements_without_uri = array("range mass", "average mass", "range depth", "range length", "range number of offspring");
        $this->measurements_with_just_one_line = array("range number of offspring", "average time to hatching", "range age at sexual or reproductive maturity (female)", "range age at sexual or reproductive maturity (male)", "range lifespan", "average lifespan", "iucn red list", "us federal list", "cites");
        $this->measurements_with_units = array("range mass", "average mass", "range depth", "range length");
        $this->units = array("ft", "mm", "in", "cm", "m", "km", "g", "kg", "lb", "cm^3 oxygen/hour ", "(cm3.O2/g/hr)", "cm3.O2/g/hr", "watts ", "(W)", "W", "minutes", "hours", "days", "weeks", "months", "years", "cm^2", "m^2", "km^2", "hectares", "individual/hectare", "individuals/km^2");
        $this->measurements_without_uri = array_merge($this->measurements_without_uri, $this->measurements_with_just_one_line);
        $excluded_topics    = array("contributors", "references", "cite" , "reference_ids", "agent_ids", "predation");
        $excluded_subtopics = array("breeding interval", "breeding season");
        
        foreach($data as $topic => $values)
        {
            if(in_array($topic, $excluded_topics)) continue;
            // echo "\n[topic: $topic]";
            foreach($values as $measure => $recs)
            {
                $measure = strtolower($measure);
                if(in_array($measure, $excluded_subtopics)) continue;
                
                if($measure != "articles") //disregard articles
                {
                    // echo "\n[measure: $measure]";
                    foreach($recs as $index => $value)
                    {
                        if(!is_numeric($index)) continue;
                        $rec = array();
                        $rec["taxon_id"] = $taxon_id;
                        $measurementRemarks = "";
                        $value_uri = "";
                        $measurementOfTaxon = true;
                        
                        if(in_array($measure, $this->measurements_without_uri))
                        {
                            $valuex = $value;
                            $rec["catnum"] = str_replace(" ", "_", $measure);
                            if(in_array($measure, $this->measurements_with_units))
                            {
                                foreach($this->units as $unit)
                                {
                                    if(substr($valuex, -strlen($unit)) == $unit) $rec["catnum"] .= "_".$unit;
                                }
                            }
                        }
                        else
                        {
                            $valuex = self::get_value($value);
                            $rec["catnum"] = str_replace(" ", "_", $value);
                        }
                        self::add_string_types($rec, $measure, $valuex, self::get_value($measure), $measurementRemarks, $value_uri, $measurementOfTaxon);
                        if(in_array($measure, $this->measurements_with_just_one_line)) break;
                    }
                }
            }
        }
    }
    
    private function get_value($value)
    {
        if($val = @$this->uris[$value]) return $val;
        else return "http://no_uri_yet/" . str_replace(" ", "_", $value);
    }

    private function add_string_types($rec, $label, $value, $mtype, $measurementRemarks = null, $value_uri = false, $measurementOfTaxon = false)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence_id;

        if($measurementOfTaxon)
        {
            $m->measurementOfTaxon = 'true';
            $m->measurementRemarks = $measurementRemarks;
            $m->source = '';
            $m->contributor = '';
        }
        
        $m->measurementUnit = "";
        $m->measurementType = $mtype;
        if($value_uri)  $m->measurementValue = $value_uri;
        else            $m->measurementValue = $value;
        $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
    }

    private function create_references($refs, $url)
    {
        if(!$refs) return array();
        $reference_ids = array();
        foreach($refs as $ref)
        {
            $r = new \eol_schema\Reference();
            $r->full_reference = strip_tags($ref, "<a><span><em><tt>");
            if(!$r->full_reference) continue;
            $r->identifier = md5($ref);
            if(preg_match("/<p id=\"(.*?)\"/ims", $ref, $arr)) $r->uri = $url . "#" . $arr[1]; //<p id="13D83400-8B34-11E2-8D6A-002500F14F28">
            $reference_ids[] = $r->identifier;
            if(!isset($this->reference_ids[$r->identifier]))
            {
               $this->reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return array_unique($reference_ids);
    }
    
    private function add_object($o)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $o['taxon_id'];
        $mr->identifier     = $o['identifier'];
        $mr->type           = $o['dataType'];
        $mr->language       = 'en';
        $mr->format         = $o['mimeType'];
        $mr->subtype        = @$o['subtype'];
        $mr->furtherInformationURL = $o['source'];
        $mr->accessURI      = @$o['mediaURL'];
        $mr->thumbnailURL   = @$o['thumbnailURL'];
        $mr->CVterm         = $o['subject'];
        $mr->Owner          = $o['rightsHolder'];
        $mr->rights         = $o['rights'];
        $mr->title          = @$o['title'];
        $mr->UsageTerms     = $o['license'];
        $mr->description    = $o['description'];
        // $mr->description    = 'x'; //debug
        
        // if(!Functions::is_utf8($mr->description)) continue;
        $mr->LocationCreated = @$o['location'];
        $mr->bibliographicCitation = $o['bibliographicCitation'];
        if($reference_ids = @$o['reference_ids']) $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids     = @$o['agent_ids'])     $mr->agentID     = implode("; ", $agent_ids);
        $this->archive_builder->write_object_to_file($mr);
    }
    
    private function clean_article($text, $url)
    {
        $text = strip_tags($text, "<a><p>");
        $text = str_ireplace('<a href="#', '<a href="' . $url . '#', $text);
        return $text;
    }
    
    private function get_subject($topic)
    {
        $path = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#";
        switch($topic)
        {
            case 'brief_summary':           return $path.'TaxonBiology';
            case 'geographic_range':        return $path.'Distribution';
            case 'habitat':                 return $path.'Habitat';
            case 'physical_description':    return $path.'Morphology';
            case 'reproduction':            return $path.'Reproduction';
            case 'lifespan_longevity':      return $path.'LifeExpectancy';
            case 'food_habits':             return $path.'TrophicStrategy';
            case 'conservation_status':     return $path.'ConservationStatus';
            case 'development':             return $path.'Development';
        }
        if    (in_array($topic, array("communication", "behavior")))                                    return $path.'Behaviour';
        elseif(in_array($topic, array("predation", "ecosystem_roles")))                                 return $path.'Associations';
        elseif(in_array($topic, array("economic_importance_positive", "economic_importance_negative"))) return $path.'Uses';
        elseif(in_array($topic, array("diversity", "comments")))                                        return $path.'Notes';
        exit("\nstop! no subject: [$topic]\n");
        return false;
    }

    private function add_vernacular($vernacular, $rec)
    {
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec["taxon_id"];
        $v->vernacularName  = $vernacular;
        $v->language        = "en";
        $this->archive_builder->write_object_to_file($v);
    }
    
    private function parse_adw_taxon_page($url)
    {
        if($titles = self::get_taxon_page_topics($url))
        {
            $sections = self::get_page_sections($titles, $url);
            $lists = self::parse_sections($sections, $url);
        } 
        else // e.g. http://animaldiversity.ummz.umich.edu/accounts/Cephalocarida/ -- most likely for higher-level taxon
        {
            $lists = self::parse_short_taxon_page($url);
        }
        
        $lists["reference_ids"] = self::create_references(@$lists["references"]["articles"], $url);
        $agent_info = self::create_agents(@$lists["contributors"]["articles"], $lists);
        $lists["agent_ids"] = $agent_info["agent_ids"];
        return $lists;
    }
    
    private function create_agents($lines, $lists=false)
    {
        $lines = array_map('trim', $lines);
        if(!$lines) return array();
        $lines = array_values($lines);
        if(!$lines) return array();
        /*
        Array
        (
            [0] => <p >Renee Sherman Mulcrone (editor).
            [1] => <p >Shelby Freda (author), University of Michigan-Ann Arbor, Teresa Friedrich (editor), University of Michigan-Ann Arbor.
        )
        */
        $agents = array();
        foreach($lines as $line)
        {
            $line = strip_tags($line);
            $line = self::arrange_separator_inside_parenthesis($line); // e.g. Courtney Wilmot (author), Kevin Wehrly (editor, instructor), University of Michigan-Ann Arbor. 
            $names = explode(",", $line);
            $agents = array_merge($agents, $names);
        }
        
        $agents = array_map('trim', $agents);
        $k = 0;
        foreach($agents as $agent) // remove end char '.'
        {
            if(substr($agent, -1) == ".") $agent = substr($agent, 0, strlen($agent)-1);
            $agents[$k] = $agent;
            $k++;
        }
        $agents = array_unique($agents);
        
        $records = array();
        foreach($agents as $agent)
        {
            if(preg_match("/\((.*?)\)/ims", $agent, $arr))
            {
                $role = $arr[1];
                $roles = explode(";", $role);
                $total_roles = array();
                foreach($roles as $role) $total_roles = array_merge($total_roles, explode(",", $role));
                
                $roles = $total_roles;
                $roles = array_map('trim', $roles);
                $roles = array_unique($roles);
                
                $agent = trim(preg_replace('/\s*\([^)]*\)/', '', $agent)); //remove parenthesis
                foreach($roles as $role)
                {
                    if    ($role == "instructor")       $role = "director";
                    elseif($role == "earlier author")   $role = "author";
                    elseif($role == "research")         $role = "recorder";
                    elseif($role == "recordist")        $role = "recorder";
                    elseif($role == "audio capture")    $role = "recorder";
                    elseif($role == "identification")   $role = "author";
                    elseif($role == "donor")            $role = "project";
                    elseif($role == "media")            $role = "source";
                    if($agent) $records[] = array("agent" => $agent, "role" => $role);
                }
            }
            // else $records[] = array("agent" => $agent, "role" => "project"); working but decided to exclude (e.g. Radford University, Animal Diversity Web Staff)
        }
        
        $agent_ids = array();
        $i = 0;
        foreach($records as $a)
        {
            $r = new \eol_schema\Agent();
            $r->term_name       = self::format_string($a['agent']);
            $r->agentRole       = $a['role'];
            $r->identifier      = md5("$r->term_name|$r->agentRole");
            // $r->term_homepage   = '';
            if(in_array($a['role'], array("copyright holder", "copyright_holder")))
            {
                $r->agentRole       = "author";
                $r->identifier      = md5("$r->term_name|$r->agentRole");
                $this->agent_copyright_holders[$r->identifier] = '';
            }
            $agent_ids[] = $r->identifier;
            $records[$i]["identifier"] = $r->identifier;
            if(!isset($this->agent_ids[$r->identifier]))
            {
               $this->agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
            $i++;
        }
        if(!$agent_ids)
        {
            echo "\n investigate: No agent_ids \n";
            print_r($lines);
            print_r($records);
            return array();
        }
        return array("agent_ids" => $agent_ids, "records" => $records);
    }

    private function parse_short_taxon_page($url)
    {
        $info = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match_all("/<section class=\"hyphenate\">(.*?)<\/section>/ims", $html, $arr))
            {
                $temp = explode("<strong>References</strong>:", $arr[1][0]);
                if(count($temp) == 1) $temp = explode("<strong>References:</strong>", $arr[1][0]);
                if(count($temp) == 1) $temp = explode("<strong>Source</strong>:", $arr[1][0]);
                if(count($temp) == 1) $temp = explode("<strong>Source:</strong>", $arr[1][0]);
                // if(count($temp) == 1) print("\nInvestigate: no refs [$url]\n");
                
                $articles = $temp[0];
                $references = @$temp[1];
                $contributors = @$arr[1][1];
                
                //articles
                $articles = Functions::remove_whitespace(strip_tags($articles, "<p><ul><li>")); // deliberately remove <a> tags
                $articles = str_replace("\n", " ", $articles);
                if(preg_match_all("/<p>(.*?)<\/p>/ims", $articles, $arr)) $articles = $arr[1];
                
                //references
                $references = Functions::remove_whitespace(strip_tags($references, "<p><li>"));
                $references = str_replace("\n", " ", $references);
                if(preg_match_all("/<p>(.*?)<\/p>/ims", $references, $arr)) $references = $arr[1];
                elseif(preg_match_all("/<li>(.*?)<\/li>/ims", $references, $arr)) $references = $arr[1];
                // else // -- no refs
                
                //contributors
                // <h3 id="contributors">Contributors</h3>
                if(is_numeric(stripos($contributors, 'id="contributors"')))
                {
                    if(preg_match_all("/<p>(.*?)<\/p>/ims", $contributors, $arr))
                    {
                        $info["contributors"]["articles"] = $arr[1];
                    }
                }
                
                $info["brief_summary"]["articles"] = $articles;
                $info["references"]["articles"]    = $references;
                $info["cite"]                      = self::cite_this_page($html);
            }
        }
        return $info;
    }
    
    private function cite_this_page($html)
    {
        if(preg_match("/To cite this page\:(.*?)<\/p>/ims", $html, $arr)) return trim($arr[1]);
    }
    
    private function parse_sections($sections, $url)
    {
        foreach($sections as $topic_id => $html)
        {
            // text article
            $paragraphs = array();
            if(preg_match_all("/<p(.*?)<\/p>/ims", $html, $arr))
            {
                foreach($arr[1] as $para)
                {
                    $p = "<p " . str_replace("\n", "", Functions::remove_whitespace($para));
                    $paragraphs[] = $p;
                }
            }
            $lists[$topic_id] = self::parse_lists($html, $topic_id);
            $lists[$topic_id]["articles"] = $paragraphs;
        }
        if($html = Functions::lookup_with_cache($url, $this->download_options)) $lists["cite"] = self::cite_this_page($html);
        return $lists;
    }
    
    private function prepare_image_data($type)
    {
        if($html = Functions::lookup_with_cache($this->adw_page["start"], $this->download_options))
        {
            if(preg_match("/<a name=\"feature-" . $type . "\" href=\"(.*?)\"/ims", $html, $arr))
            {
                $url = $this->domain . $arr[1];
                $taxa_with_media = self::get_taxa_with_media($url);
                self::get_media_data($taxa_with_media, $type);
            }
        }
    }
    
    private function get_media_data($taxa_urls, $type)
    {
        $wrong_urls = self::get_urls_from_dump($this->dump_file);
        $total = count($taxa_urls);
        $k = 0;
        foreach($taxa_urls as $url)
        {
            if(in_array($url, $wrong_urls)) continue;
            $k++;
            // if($k > 10) break; //debug
            
            echo "\n get_media_data [$type] $k of $total ";
            /* breakdown when caching
            $m = 5000;
            $cont = false;
            // if($k >=  1    && $k < $m)    $cont = true;
            // if($k >=  $m   && $k < $m*2)  $cont = true;
            // if($k >=  $m*2 && $k < $m*3)  $cont = true;
            // if($k >=  $m*3 && $k < $m*4)  $cont = true;
            if(!$cont) continue;
            */
            
            // $url = "/accounts/Animalia/pictures/collections/contributors/h_c_kyllingstad/White_eyesSanDiegoZoo/?start=19485"; //debug
            // $url = "/accounts/Animalia/pictures/collections/contributors/tanya_dewey/cheetah11/?start=135"; //debug
            // $url = "/collections/contributors/phil_myers/ADW_molluscs3_4_03/Arion_subfuscus/"; //debug
            // $url = "/collections/contributors/david_blank/Himalayan_griffon/?start=870"; //debug
            // $url = "/collections/contributors/skulls/nandinia/n._binotata/n._binotatamovie/?start=1200"; //debug with movie
            
            $rec = array();
            $rec["source"] = $url;
            if($html = Functions::lookup_with_cache($this->domain.$url, $this->download_options))
            {
                $rec["cite"] = self::cite_this_page($html);
                
                if(preg_match("/<h3>Caption<\/h3>(.*?)<\/blockquote>/ims", $html, $arr)) $rec["caption"] = trim(strip_tags($arr[1], "<em>"));
                
                if(preg_match("/class=\"taxon-link rank-(.*?)<\/a>/ims", $html, $arr))
                {
                    if(preg_match("/>(.*?)_xxx/ims", $arr[1]."_xxx", $arr)) $rec["taxon"] = $arr[1];
                    else
                    {
                        echo "\nInvestigate: no taxon 01 [$url]\n";
                        continue;
                    }
                }
                else
                {
                    if(preg_match("/<em>(.*?)<\/em>/ims", @$rec["caption"], $arr)) $rec["taxon"] = $arr[1];
                    else continue; //no identification yet in the actual page
                }
                
                if(preg_match("/<h3>Date Taken<\/h3>(.*?)<\/p>/ims", $html, $arr))                          $rec["date_taken"] = trim(strip_tags($arr[1]));
                if(preg_match("/<h3>Location<\/h3>(.*?)<\/p>/ims", $html, $arr))                            $rec["location"] = trim(strip_tags($arr[1]));
                elseif(preg_match("/<h3>Caption<\/h3>(.*?)<\/p>/ims", $html, $arr))                         $rec["caption"] = trim(strip_tags($arr[1]));
                if(preg_match("/<li class=\"keywords-header\">Subject<\/li>(.*?)<\/li>/ims", $html, $arr))  $rec["subject"] = trim(strip_tags($arr[1]));
                if(preg_match("/<li class=\"keywords-header\">Type<\/li>(.*?)<\/li>/ims", $html, $arr))     $rec["type"] = trim(strip_tags($arr[1]));
                if(preg_match_all("/<li class=\"keywords-header\">Life Stages And Gender<\/li>(.*?)<\/li>/ims", $html, $arr))
                {
                    foreach($arr[1] as $item) $rec["lifestage_gender"][] = trim(strip_tags($item));
                }
                if(preg_match_all("/<li class=\"keywords-header\">Anatomy<\/li>(.*?)<\/li>/ims", $html, $arr))
                {
                    foreach($arr[1] as $item) $rec["anatomy"][] = trim(strip_tags($item));
                }
                if(preg_match("/<h3>Contributors<\/h3>(.*?)<\/div>/ims", $html, $arr))      $rec["contributors"] = trim(strip_tags($arr[1]));
                if(preg_match("/<h3>Conditions of Use<\/h3>(.*?)<\/div>/ims", $html, $arr)) $rec["license"] = trim(strip_tags($arr[1], "<a>"));
                if($type == "sounds")
                {
                    if(preg_match("/<a class=\"media\" href=\"(.*?)\"/ims", $html, $arr)) $rec["media"] = trim($arr[1]);
                }
                else
                {
                    if(preg_match("/Up to: " . strtoupper($type) . "(.*?)View Full Size/ims", $html, $arr))
                    {
                        if(preg_match("/src=\"(.*?)\"/ims", $arr[1], $arr)) $rec["media"] = trim($arr[1]);
                    }
                    // elseif(preg_match("/Up to: " . "Myers, Phil" . "(.*?)View Full Size/ims", $html, $arr))
                    // {
                    //     if(preg_match("/src=\"(.*?)\"/ims", $arr[1], $arr)) $rec["media"] = trim($arr[1]);
                    // }
                    elseif(preg_match("/Up to: " . "" . "(.*?)View Full Size/ims", $html, $arr))
                    {
                        if(preg_match("/src=\"(.*?)\"/ims", $arr[1], $arr)) $rec["media"] = trim($arr[1]);
                    }
                    else
                    {
                        if(preg_match("/<a class=\"media\" href=\"(.*?)\"/ims", $html, $arr)) $rec["media"] = trim($arr[1]);
                    }
                }
                
            }
            else
            {
                self::save_to_dump($url, $this->dump_file);
                continue;
            }
            
            if($val = $rec["taxon"]) $rec["taxon_id"] = str_replace(" ", "_", $val);
            if(@$rec["media"]) self::create_media_object($rec, $type);
            else
            {
                echo "\n no media [$type]\n";
                $this->debug["no media"][$rec["source"]] = '';
                print_r($rec); continue;
            }
            // break; //debug
        }
    }
    
    private function create_media_object($rec, $type)
    {
        /*
        [source]    => /accounts/Animalia/pictures/collections/contributors/james_dowlinghealey/Tibicencanicularis2/?start=18300
        [cite]      => Myers, P., R. Espinosa, C. S. Parr, T. Jones, G. S. Hammond, and T. A. Dewey. 2014. The Animal Diversity Web (online). Accessed at http://animaldiversity.org.
        [taxon]     => Villosa iris
        [caption]   => This map shows the watersheds of rivers in Michigan. Watersheds where this species has been found are colored yellow, those that this species is not recorded from are blue. The creation of this map was made possible by the Michigan Natural Heritage Small Grant Program, paid for by the Michigan Non-game Wildlife Fund.
        [subject]   => Map :: Distribution
        [type]      => Illustration
        [lifestage_gender][0] => Larva
        [contributors]  => Renee Sherman Mulcrone (illustrator; copyright holder)
        [license]       => <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/"></a>This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/">Creative Commons Attribution-Noncommercial-Share Alike 3.0 Unported License</a>.
        [media]         => /collections/contributors/mussel_distribution/Villosa_iris_map/large.jpg
        */

        if(!isset($this->taxon_ids[$rec["taxon_id"]]))
        {
            $t = new \eol_schema\Taxon();
            $t->taxonID = $rec["taxon_id"];
            $t->scientificName = $rec["taxon"];
            $t->furtherInformationURL = $this->domain . $rec["source"];
            if($ancestry = self::get_ancestry($rec["taxon_id"]))
            {
                foreach($this->ranks as $rank)
                {
                    if($val = @$ancestry[$rank]) $t->$rank = $val;
                }
            }
            $this->archive_builder->write_object_to_file($t);
            $this->taxon_ids[$rec["taxon_id"]] = '';
        }
        
        //start desc
        $desc = "";
        if($val = @$rec["caption"])             $desc .= $val;
        if($val = @$rec["subject"])             $desc .= "<br>Subject: " . $val;
        if($val = @$rec["type"])                $desc .= "<br>Type: " . $val;
        if($vals = @$rec["lifestage_gender"])
        {
            foreach($vals as $val) $desc .= "<br>Life Stages And Gender: " . $val;
        }
        if($vals = @$rec["anatomy"])
        {
            foreach($vals as $val) $desc .= "<br>Anatomy: " . $val;
        }
        if($val = @$rec["date_taken"])          $desc .= "<br>Date Taken: " . $val;
        if($val = @$rec["location"])            $desc .= "<br>Location: " . $val;
        if($val = @$rec["contributors"])        $desc .= "<br>Contributors: " . $val;
        
        //start agents
        $agent_ids = array(); $agent_records = array();
        if($agent_info = self::create_agents(array(@$rec["contributors"])))
        {
            $agent_ids     = $agent_info["agent_ids"];
            $agent_records = $agent_info["records"];
        }
        /*  illustrator; copyright holder
            photographer; copyright holder
        e.g.
        <dc:rights>Copyright Phil Myers</dc:rights>
        <dcterms:rightsHolder>Phil Myers</dcterms:rightsHolder>
        */

        //start rights & rightsHolder
        $rights = ""; $rightsHolder = "";
        $from_Phil_Myers = false;
        foreach($agent_records as $agent_rec)
        {
            if(isset($this->agent_copyright_holders[$agent_rec["identifier"]]))
            {
                if(in_array($agent_rec["agent"], array("Phil Myers", "Philip Myers")) && in_array($agent_rec["role"], array("copyright holder", "copyright_holder"))) $from_Phil_Myers = true;
                $rights       = "Copyright " . $agent_rec["agent"];
                $rightsHolder = $agent_rec["agent"];
            }
        }

        //start license
        if(!isset($rec["license"]))
        {
            if($from_Phil_Myers) $rec["license"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/"; //since there are images without license from Phil Myers
            else
            {
                echo "\n investigate: no license [$type]\n";
                $this->debug["no license url"][$rec["source"]] = '';
                print_r($rec);
            }
        }
        $license = "";
        if    (preg_match("/<a rel=\"license\" href=\"(.*?)\"/ims", @$rec["license"], $arr)) $license = $arr[1];
        elseif(preg_match("/<a href=\"(.*?)\"/ims", @$rec["license"], $arr))                 $license = $arr[1];
        elseif($val = @$rec["license"])                                                      $license = $val;
        
        $license = self::format_string($license);
        if(!in_array($license, $this->valid_licenses))
        {
            if($attribution = self::further_check_license($license, $rightsHolder))
            {
                $license        = $attribution["license"];
                $rights         = $attribution["rights"];
                $rightsHolder   = $attribution["agent"];
            }
            else
            {
                $this->debug["invalid license"]["$license [$rightsHolder]"] = '';
                return;
            }
        }

        if(in_array($type, array("maps", "pictures")))
        {
            $dataType = "http://purl.org/dc/dcmitype/StillImage";
            /*  /accounts/Animalia/pictures/collections/contributors/phil_myers/lepidoptera/Pieridae/Abaeis0791/
                                            collections/contributors/phil_myers/lepidoptera/Pieridae/Abaeis0791
            */
            if(preg_match("/pictures\/(.*?)_xxx/ims", $rec["source"]."_xxx", $arr)) $identifier = $arr[1];
            else $identifier = $rec["source"];
        }
        elseif($type == "sounds")
        {
            $dataType = "http://purl.org/dc/dcmitype/Sound";
            /*
            /accounts/Animalia/sounds/collections/contributors/naturesongs/coopers1/
                                      collections/contributors/naturesongs/coopers1
            */
            if(preg_match("/sounds\/(.*?)\/_xxx/ims", $rec["source"]."_xxx", $arr)) $identifier = $arr[1];
            else $identifier = $rec["source"];
        }

        $rec["media"] = str_ireplace("/large.", "/medium.", $rec["media"]);

        /*                         collections/contributors/james_dowlinghealey/Macawblue3
                                   collections/contributors/james_dowlinghealey/Macawblue3/?start=30     Ara_ararauna    http://purl.org/dc/dcmitype/StillImage  image/jpeg      x   http://animaldiversity.ummz.umich.edu/collections/contributors/james_dowlinghealey/Macawblue3/?start=30                                 en  http://creativecommons.org/licenses/by-nc-sa/3.0/   Copyright James Dowling-Healey  James Dowling-Healey    Myers, P., R. Espinosa, C. S. Parr, T. Jones, G. S. Hammond, and T. A. Dewey. 2014. The Animal Diversity Web (online). Accessed at http://animaldiversity.org.  244633929999b7a4dde21d478182b47c; a8ae6a72a7c288902289f4a442f076cf          http://animaldiversity.ummz.umich.edu/collections/contributors/james_dowlinghealey/Macawblue3/medium.jpg
        accounts/Animalia/pictures/collections/contributors/james_dowlinghealey/Macawblue3/?start=1560   Ara_ararauna    http://purl.org/dc/dcmitype/StillImage  image/jpeg      x   http://animaldiversity.ummz.umich.edu/accounts/Animalia/pictures/collections/contributors/james_dowlinghealey/Macawblue3/?start=1560    en  http://creativecommons.org/licenses/by-nc-sa/3.0/   Copyright James Dowling-Healey  James Dowling-Healey    Myers, P., R. Espinosa, C. S. Parr, T. Jones, G. S. Hammond, and T. A. Dewey. 2014. The Animal Diversity Web (online). Accessed at http://animaldiversity.org.  244633929999b7a4dde21d478182b47c; a8ae6a72a7c288902289f4a442f076cf          http://animaldiversity.ummz.umich.edu/collections/contributors/james_dowlinghealey/Macawblue3/medium.jpg
        */
        
        $identifier = trim($identifier);
        $parts = pathinfo($identifier);
        $identifier = $parts["dirname"];
        if(substr($identifier, -1) == "/") $identifier = substr($identifier, 0, strlen($identifier)-1);
        if(substr($identifier, 0, 1) == "/") $identifier = substr($identifier, 1, strlen($identifier));
        $identifier = trim($identifier);

        if(isset($this->object_ids[$identifier])) return;
        else $this->object_ids[$identifier] = '';

        $mimeType = Functions::get_mimetype($rec["media"]);
        if(is_numeric(stripos($mimeType, 'video/'))) $dataType = "http://purl.org/dc/dcmitype/MovingImage";

        $o = array("taxon_id" => $rec["taxon_id"],
            "identifier"            => $identifier,
            "dataType"              => $dataType,
            "source"                => $this->domain.$rec["source"],
            "mimeType"              => $mimeType, 
            "subject"               => "",
            "description"           => $desc,
            "rights"                => $rights,
            "rightsHolder"          => $rightsHolder,
            "license"               => $license, 
            "bibliographicCitation" => $rec["cite"],
            // "reference_ids" => $page["reference_ids"],
            "agent_ids"             => $agent_ids,
            "mediaURL"              => $this->domain.$rec["media"]);
        if($type == "maps") $o["subtype"] = "map";
        self::add_object($o);
    }
    
    private function get_ancestry($taxon_id, $url = false)
    {
        $ancestry = array();
        if($url) $html = Functions::lookup_with_cache($url, $this->download_options);
        else
        {
            $url = "http://animaldiversity.ummz.umich.edu/accounts/$taxon_id/classification/#$taxon_id";
            $html = Functions::lookup_with_cache($url, $this->download_options);
        }
        if(!$html)
        {
            echo "\ninvestigate: no lookup: [$url]\n"; //exit();
            return $ancestry;
        }
        if(preg_match("/<div class=\"classification well\">(.*?)<\/ul>/ims", $html, $arr))
        {
            if(preg_match_all("/<li>(.*?)<\/li>/ims", $arr[1], $arr))
            {
                foreach($arr[1] as $block)
                {
                    if(preg_match("/<span class=\"rank\">(.*?)<\/span>/ims", $block, $temp))
                    {
                        $rank = strtolower($temp[1]);
                        if(preg_match("/class=\"taxon-name rank-" . $rank . "\">(.*?)<\/a>/ims", $block, $temp)) $ancestry[$rank] = $temp[1];
                    }
                }
            }
        }
        return $ancestry;
    }
    
    private function get_taxa_with_media($url)
    {
        $taxa_list = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            $k = 0;
            while(true)
            {
                if($html = Functions::lookup_with_cache($url . "?start=$k", $this->download_options))
                {
                    if(preg_match_all("/<a class=\"img rewrite\" href=\"(.*?)\"/ims", $html, $arr)) $taxa_list = array_merge($taxa_list, $arr[1]);
                    else break;
                }
                $k = $k + 15;
                // break; //debug
            }
        }
        return array_unique($taxa_list);
    }
    
    public static function parse_lists($html, $topic_id)
    {
        $html = '<?xml version="1.0" encoding="utf-8"?><eli>' . $html . '</eli>';
        $xml = simplexml_load_string($html);
        $info = array();
        foreach($xml->ul as $ul)
        {
            $i = 0;
            foreach($ul->li as $li)
            {
                //============================================================================
                /*
                <ul class="keywords donthyphenate ">
                   <li class="keywords-header">Habitat Regions</li>
                   <li>
                     <a class="gloss" href="#20020904145595">temperate</a>
                   </li>
                   <li>
                     <a class="gloss" href="#20020904145582">saltwater or marine</a>
                   </li>
                 </ul>
                */
                $i++;
                if($i == 1) $topic = (string) $li;
                else
                {
                    if($val = $li->a)    $info[$topic][] = (string) $val;
                    if($val = $li->span) $info[$topic][] = (string) $val;
                    
                    /*
                    <ul class="keywords donthyphenate ">
                       <li class="keywords-header">Primary Diet</li>
                       <li>
                         <a class="gloss" href="#20020904145419">carnivore</a>
                         <ul>
                           <li>
                             <a class="gloss" href="#20020904145838">piscivore</a>
                           </li>
                           <li>
                             <span>eats non-insect arthropods</span>
                           </li>
                         </ul>
                       </li>
                     </ul>
                    */
                    $val2 = $val;
                    if($li->ul)
                    {
                        if($li->a)    $topic2 = (string) $li->a;
                        if($li->span) $topic2 = (string) $li->span;
                        
                        foreach($li->ul->li as $li)
                        {
                            if($val = $li->a)    $info[$topic][$topic2][] = (string) $val;
                            if($val = $li->span) $info[$topic][$topic2][] = (string) $val;
                        }
                    }
                    
                }
                //============================================================================
                /*
                <li>
                  <dl>
                    <dt>Range mass</dt>
                    <dd>96 (high)  kg</dd>
                    <dd class="english">211.45 (high)  lb</dd>
                  </dl>
                </li>
                */
                if($li->dl)
                {
                    if($val = $li->dl->dt)
                    {
                        if($li->dl->dt->a && $li->dl->dt->a != "[Link]") $topic = (string) $li->dl->dt->a;
                        else                                             $topic = (string) $li->dl->dt;
                        
                        $k = -1;
                        foreach($li->dl->dd as $item)
                        {
                            $k++;
                            if    ($val = (string) $item->span) $info[$topic][] = $val;
                            elseif($val = (string) $item)       $info[$topic][] = $val;
                            /*
                            <dd>
                              <span>Vulnerable</span>
                              <br />
                              <small><a class="external-link" href="http://www.iucnredlist.org/apps/redlist/details/8784">More information</a></small>
                            </dd>
                            */
                            if($val = trim((string) $item->small->a))
                            {
                                $info[$topic][$val] = (string) $item->small->a{'href'};
                            }

                            /*
                              <dl>
                                <dt>Known Predators</dt>
                                <dd>
                                  <ul>
                                    <li>sharks (<a class="taxon-link rank-class" href="/accounts/Chondrichthyes/">Chondrichthyes</a>)</li>
                                    <li>harp and harbor seals (<a class="taxon-link rank-genus" href="/accounts/Phoca/">Phoca</a>)</li>
                                  </ul>
                                </dd>
                              </dl>
                            */
                            if($item->ul)
                            {
                                foreach($item->ul->li as $li)
                                {
                                    $temp = trim($li);
                                    if($val = (string) $li->span) $temp .= "[$val]";
                                    else
                                    {
                                        foreach($li->a as $a) $temp .= "[$a]";
                                    }
                                    $info[$topic][] = (string) $temp;
                                }
                            }
                            
                        }
                    }
                }
                //============================================================================
            }
        }
        return $info;
    }
    
    private function get_page_sections($titles, $url)
    {
        $sections = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            foreach($titles as $title)
            {
                /* <a href="#geographic_range">Geographic Range</a> */
                if(preg_match("/<a href=\"#(.*?)\"/ims", $title, $arr)) $topic_id = $arr[1];
                if(preg_match("/\">(.*?)<\/a>/ims", $title, $arr)) $topic_title = $arr[1];
                /* <h3 id="habitat">Habitat</h3>???</section> */
                $topic_title2 = str_replace("/", "\/", $topic_title);
                if(preg_match("/<h3 id=\"" . $topic_id . "\">$topic_title2<\/h3>(.*?)<\/section>/ims", $html, $arr)) $sections[$topic_id] = $arr[1];
                else echo "\nInvestigate: cannot get $topic_id - $topic_title [$url]\n";
            }
        }
        $section = array_map('trim', $sections);
        return $sections;
    }
    
    private function get_taxon_page_topics($url)
    {
        $titles = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match("/<nav class=\"contents\">(.*?)<\/nav>/ims", $html, $arr))
            {
                if(preg_match_all("/<li>(.*?)<\/li>/ims", $arr[1], $arr)) $titles = array_map('trim', $arr[1]);
            }
        }
        return $titles;
    }
    
    private function assemble_taxa_list()
    {
        $taxa = array();
        $accessed = array();
        $records = self::get_related_taxa_from_page($this->adw_page["start"]);
        if($records) $taxa = array_merge($taxa, $records);
        echo "\n - " . count($taxa);
        for($i=1; $i<=10; $i++) // orig limit is 10 //debug
        {
            echo "\nloop[$i]" . count($records) . "\n";
            foreach($records as $r)
            {
                if(isset($accessed[$r["source"]])) continue;
                else $accessed[$r["source"]] = '';
                
                $rekords = self::get_related_taxa_from_page($this->domain . $r["source"]);
                if($rekords) $taxa = array_merge($taxa, $rekords);
            }
            $records = $taxa;
        }
        echo "\n count: " . count($taxa) . "\n";
        return $taxa;
    }
    
    private function get_related_taxa_from_page($url)
    {
        $taxa = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match("/<h3>Related Taxa<\/h3>(.*?)<\/ul>/ims", $html, $arr))
            {
                if(preg_match_all("/<li>(.*?)<\/li>/ims", $arr[1], $arr))
                {
                    foreach($arr[1] as $item)
                    {
                        if(preg_match("/<span class=\"rank\">(.*?)<\/span>/ims", $item, $arr2)) $rank = $arr2[1];
                        if(preg_match("/rank-" . strtolower($rank) . "\">(.*?)<\/a>/ims", $item, $arr2))
                        {
                            $sciname = $arr2[1];
                            $taxa[$sciname]["rank"] = $rank;
                            if(preg_match("/<a href=\"(.*?)\"/ims", $item, $arr2))                             $taxa[$sciname]["source"] = $arr2[1];
                            if(preg_match("/<span class=\"vernacular-name\">(.*?)<\/span>/ims", $item, $arr2)) $taxa[$sciname]["vernacular"] = $arr2[1];
                        }
                    }
                }
            }
        }
        return $taxa;
    }

    private function arrange_separator_inside_parenthesis($str)
    {
        /* Courtney Wilmot (author), University of Michigan-Ann Arbor, Kevin Wehrly (editor, instructor), University of Michigan-Ann Arbor.
        should be (editor; instructor) */
        $inside_parenthesis = false;
        for($i=0; $i < strlen($str); $i++)
        {
            $char = $str[$i];
            if($char == "(") $inside_parenthesis = true;
            elseif($char == ")") $inside_parenthesis = false;
            if($char == "," && $inside_parenthesis) $str[$i] = ";";
        }
        return $str;
    }

    private function save_to_dump($data, $filename)
    {
        if(!($WRITE = fopen($filename, "a")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
          return;
        }
        if($data && is_array($data)) fwrite($WRITE, json_encode($data, true) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }

    private function get_urls_from_dump($fname)
    {
        $urls = array();
        if($filename = Functions::save_remote_file_to_local($fname, $this->download_options))
        {
            foreach(new FileIterator($filename) as $line_number => $line)
            {
                if($line) $urls[$line] = '';
            }
            unlink($filename);
        }
        return array_keys($urls);
    }

    private function get_uris()
    {
        $uris = array();
        $options = $this->download_options;
        $options["cache"] = 1;
        // $options["expire_seconds"] = 0;
        if($filename = Functions::save_remote_file_to_local($this->uri_list, $options))
        {
            foreach(new FileIterator($filename) as $line_number => $line)
            {
                if($line)
                {
                    $arr = explode("--", $line);
                    if(count($arr) > 1)
                    {
                        $measurement = strtolower(trim(str_ireplace(array(":", "-"), "", $arr[0])));
                        $value       = strtolower(trim(str_ireplace(array(":", "-"), "", $arr[1])));
                        $uris[$measurement] = $value;
                    }
                }
            }
            unlink($filename);
        }
        return $uris;
    }

    private function prepare_contributor_galleries()
    {
        // get urls for each contributor
        $urls = array();
        if($html = Functions::lookup_with_cache($this->adw_page["contributor_galleries"], $this->download_options))
        {
            if(preg_match("/<ol class=\"unstyled\">(.*?)<\/ol>/ims", $html, $arr))
            {
                if(preg_match_all("/<li>(.*?)<\/li>/ims", $arr[1], $arr))
                {
                    foreach($arr[1] as $block)
                    {
                        if(preg_match("/<a href=\"(.*?)\"/ims", $block, $temp)) $urls[$temp[1]] = '';
                    }
                }
            }
        }
        
        // loop to each contributor and get all media
        $i = 0;
        foreach(array_keys($urls) as $url)
        {
            $i++;
            /* breakdown when caching
            $m = 100;
            $cont = false;
            // if($i >=  1    && $i < $m)    $cont = true;
            // if($i >=  $m   && $i < $m*2)  $cont = true;
            // if($i >=  $m*2 && $i < $m*3)  $cont = true; Done
            if(!$cont) continue;
            */
            $type = "pictures";
            if($url == "/collections/contributors/naturesongs/") $type = "sounds";
            
            //manual adjustment, not images of taxa but of habitats
            if(in_array($url, array("/collections/contributors/habitat_images/"))) continue;
            
            echo "\ncontributor: [$url]\n";
            $taxa_with_media = self::get_taxa_with_media($this->domain . $url);
            self::get_media_data($taxa_with_media, $type);
            // if($i > 5) break; //debug
        }
    }

    private function further_check_license($license, $rightsHolder)
    {
        $licenses = array(
        "1" => "This resource may not be downloaded and used without permission of the copyright holder except for educational fair use.",
        "2" => "This resource may not be downloaded and used without permission of the copyright holder except for educational use.",
        "5" => "This resource may be freely used for any non-commercial purpose as long as you credit the copyright holder. For commercial use, please contact the copyright holder.",
        "6" => "This resource may not be downloaded and used without permission of the copyright holder except for educational fair use.  This resource may not be downloaded and used without permission of the copyright holder except for educational fair use.",
        "9" => "This resource may not be downloaded and used without permission of the copyright holder except for educational fair use. Please contact the copyright holder for permission for other uses.",
        "3" => "This media file may be freely used for any non-commercial purpose as long as you credit the copyright holder below. For commercial use, please contact the copyright holder at wanderingalbatross@earthlink.net  © Sharon Chester 1995",
        "4" => "This resource may not be downloaded and used without permission of the copyright holder except for non-productized educational use. You may contact the copyright holder, Joseph Dougherty, at &lt;josephd@ecology.org&gt;. Credit to the photograper must be displayed wherever the photograph is used.",
        "7" => "Copyright Cal Vornberger. This resource may not be downloaded and used without permission of the copyright holder except for educational fair use.",
        "8" => "This photo was made by the late Dr. W.H. (Herb) Wagner, Jr., formerly of the University of Michigan Herbarium and Department of Biology. This is one of many of Dr. Wagner's photographic slides that were donated to the Insect Division of the University of Michigan Museum of Zoology after his death. This image may be used without permission only for non-profit educational purposes. Permission from the Museum of Zoology must be sought for any other use. Contact the Collections Coordinator of the Insect Division (link under More Information).",
        "11" => "(c) Copyright 2006 Kimberly Ann Smith. Creative Commons Deed Attribution-NonCommercial 2.5 You are free to copy, distribute, display, and perform the work and to make derivative works under the following conditions: * Attribution. You must attribute the work in the manner specified by the author or licensor.  * Noncommercial. You may not use this work for commercial purposes. For any reuse or distribution, you must make clear to others the license terms of this work. Any of these conditions can be waived if you get permission from the copyright holder.",
        "10" => "This file is licensed under the Creative Commons Attribution ShareAlike 2.5 License. In short: you are free to share and make derivative works of the file under the conditions that you appropriately attribute it, and that you distribute it only under a license identical to this one.   This file is licensed under the Creative Commons Attribution ShareAlike 2.5 License. In short: you are free to share and make derivative works of the file under the conditions that you appropriately attribute it, and that you distribute it only under a license identical to this one.",
        "12" => "This resource may be used for noncommercial purposes, under the Creative Commons Attribution-NonCommercial 2.5 license.",
        "13" => "This media file may be freely used for any non-commercial purpose as long as you credit the copyright holder below. For commercial use, please contact the copyright holder.  This media file may be freely used for any non-commercial purpose as long as you credit the copyright holder below. For commercial use, please contact the copyright holder.");
        if    ($license == $licenses[3] && $rightsHolder == "Sharon Chester")               return array("agent" => $rightsHolder, "email" => "wanderingalbatross@earthlink.net", "rights" => "© Sharon Chester 1995",      "license" => "http://creativecommons.org/licenses/by-nc-sa/3.0/");
        elseif($license == $licenses[4] && $rightsHolder == "Joseph Dougherty")             return array("agent" => $rightsHolder, "email" => "josephd@ecology.org",              "rights" => "© $rightsHolder",            "license" => "http://creativecommons.org/licenses/by-nc-sa/3.0/");
        elseif($license == $licenses[7] && $rightsHolder == "Cal Vornberger")               return array("agent" => $rightsHolder, "email" => "",                                 "rights" => "© $rightsHolder",            "license" => "http://creativecommons.org/licenses/by-nc-sa/3.0/");
        elseif($license == $licenses[8] && $rightsHolder == "Dr. W.H. (Herb) Wagner, Jr.")  return array("agent" => $rightsHolder, "email" => "",                                 "rights" => "© $rightsHolder",            "license" => "http://creativecommons.org/licenses/by-nc-sa/3.0/");
        elseif($license == $licenses[11] && $rightsHolder == "Kimberly Ann Smith")          return array("agent" => $rightsHolder, "email" => "",                                 "rights" => "© 2006 Kimberly Ann Smith",  "license" => "http://creativecommons.org/licenses/by-nc/2.5/");
        elseif($license == $licenses[13] && $rightsHolder == "Mauricio A. Muñoz")           return array("agent" => $rightsHolder, "email" => "",                                 "rights" => "© $rightsHolder",            "license" => "http://creativecommons.org/licenses/by-nc-sa/3.0/");
        elseif(in_array($license, array($licenses[1],$licenses[2],$licenses[5],$licenses[6],$licenses[9])))
        {
            if($rightsHolder) return array("agent" => $rightsHolder, "email" => "", "rights" => "© $rightsHolder", "license" => "http://creativecommons.org/licenses/by-nc-sa/3.0/");
        }
        elseif($license == $licenses[10])
        {
            if($rightsHolder) return array("agent" => $rightsHolder, "email" => "", "rights" => "", "license" => "http://creativecommons.org/licenses/by-sa/2.5/");
        }
        elseif($license == $licenses[12])
        {
            if($rightsHolder) return array("agent" => $rightsHolder, "email" => "", "rights" => "", "license" => "http://creativecommons.org/licenses/by-nc/2.5/");
        }
        return false;
    }

    private function format_string($string)
    {
        return str_ireplace(array("\n"), " ", trim(Functions::remove_whitespace($string)));
    }

    private function get_href($string)
    {
        if(preg_match("/<a href=\"#(.*?)\"/ims", $string, $arr)) return $arr[1];
    }

}
?>