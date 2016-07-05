<?php
namespace php_active_record;
/* connector: [mediawiki.php] */

class WikiLiteratureEditorAPI
{
    function __construct($resource_id, $mediawiki_api)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->mediawiki_api = $mediawiki_api; //http://editors.eol.localhost/LiteratureEditor/api.php
        $this->download_options = array('resource_id' => $resource_id, 'expire_seconds' => false, 'download_wait_time' => 5000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
    }

    function generate_archive()
    {
        self::list_all_pages();
        $this->archive_builder->finalize(TRUE);
    }
    
    private function list_all_pages()
    {
        $eilimit = 2; //orig 500 debug
        $continue = false;
        $i = 0;
        $k = 0; //just used when caching, running multiple connectors
        while(true)
        {
            $url = $this->mediawiki_api . "?action=query&list=allpages&aplimit=$eilimit&format=json&apnamespace=5000&continue=";
            if($continue) $url .= "&apcontinue=" . $continue;
            echo "\n [$url] \n";
            if($json = Functions::lookup_with_cache($url, $this->download_options))
            {
                $j = json_decode($json);
                if($val = @$j->continue->apcontinue) $continue = $val;
                else $continue = false;
                
                /* breakdown when caching: as of 2015June03 total is 561 loops
                $k++;
                $cont = false;
                // if($k >=  1   && $k < 187) $cont = true;
                // if($k >=  187 && $k < 374) $cont = true;
                // if($k >=  374 && $k < 561) $cont = true;
                if(!$cont) continue;
                */
                
                if($recs = $j->query->allpages)
                {
                    $i = $i + count($recs);
                    echo "\n" . count($recs) . " -- " . number_format($i) . " [$continue]\n";
                    // print_r($recs);
                    self::process_pages($recs);
                }
                
            }
            else break;
            if(!$continue) break; //ends loop; all ids are processed
            // break; //debug
        }
    }
    
    private function process_pages($recs)
    {
        foreach($recs as $rec)
        {
            if($rec->title != "ForHarvesting:16194405 ae66e9b6f430af7e694cad4cf1d6f295") continue; //debug only
            echo "\n" . $rec->title;


            /* from old implementation - ver 1
            $url = $this->mediawiki_api . "?action=query&titles=" . urlencode($rec->title) . "&format=json&prop=revisions&rvprop=content";
            $json = Functions::lookup_with_cache($url, array('expire_seconds' => true)); //this expire_seconds should always be true
            $arr = json_decode($json, true);
            foreach(@$arr['query']['pages'] as $page) //there is really just one page here...
            {
                if($val = @$page['revisions'][0]['*'])
                {
                    if($data = self::parse_wiki_content($val))
                    {
                        if(isset($data['Taxa Found in Page']['text'])) self::create_archive($data);
                        else echo "\n[no taxa found for wiki: ".$data['Page Summary']['PageID']."]\n";
                    }
                }
            }
            */
            
            $info = self::get_wiki_text($rec->title);
            $params = self::get_void_part($info['content']);
            if(!$params['header_title']) continue; //to exclude the likes of "Main Page"
            if($params['ocr_text'] && $params['taxon_asso']) self::create_archive($params);
        }
    }

    //===============start from BHL controller
    function get_wiki_text($wiki_title)
    {
        /*
        $url = "/LiteratureEditor/api.php?action=query&meta=userinfo&uiprop=groups|realname&format=json";
        $json = self::get_api_result($url);
        */
        $url = $this->mediawiki_api . "?action=query&titles=" . urlencode($wiki_title) . "&format=json&prop=revisions&rvprop=content|timestamp";
        // echo "<br>[$url]<br>";
        $json = Functions::lookup_with_cache($url, array('expire_seconds' => true)); //this expire_seconds should always be true
        $arr = json_decode($json, true);
        // echo "<pre>";print_r($arr);echo "</pre>";//exit;
        foreach(@$arr['query']['pages'] as $page) //there is really just one page here...
        {
            $arr = array();
            $arr['content']   = (string) @$page['revisions'][0]['*'];
            $arr['timestamp'] = (string) @$page['revisions'][0]['timestamp'];
            return $arr;
        }
        return false;
    }
    
    function get_void_part($str)
    {
        if(preg_match("/Void\|(.*?)\}\}/ims", $str, $arr))
        {
            $json = "{" . $arr[1] . "}";
            $params = json_decode($json, true);
            return $params;
        }
        return false;
    }
    //===============end from BHL controller

    private function create_archive($p)
    {
        print_r($p);
        
        //get taxon_ids =========================
        $taxon_ids = array(); //initialize
        $names = explode(";", $p['taxon_asso']);
        $names = array_map("trim", $names);
        foreach($names as $name)
        {
            if($name = trim($name)) $taxon_ids[str_replace(" ", "_", strtolower($name))] = '';
        }
        $taxon_ids = array_keys($taxon_ids);
        //=======================================
        
        foreach($names as $name)
        {
            if(!trim($name)) continue;
            if(stripos($name, 'NameConfirmed') !== false) continue; //string is found
            
            $t = new \eol_schema\Taxon();
            $t->taxonID                 = str_replace(" ", "_", strtolower($name));
            $t->scientificName          = $name;
            /* not supplied at the moment
            $t->order                   = @$rec['ancestry']['order'];
            $t->family                  = @$rec['ancestry']['family'];
            $t->genus                   = @$rec['ancestry']['genus'];
            $t->furtherInformationURL   = $rec['permalink'];
            $t->$rank = ''; 
            */
            if(!isset($this->taxon_ids[$t->taxonID]))
            {
                $this->taxon_ids[$t->taxonID] = '';
                $this->archive_builder->write_object_to_file($t);
            }

            //start media objects
            $media = array(); //initialize

            // text object
            $media['title']                  = $p['header_title'];
            $media['CVterm']                 = $p['subject_type'];
            $media['audience']               = self::format_audience($p);

            $descriptions = self::format_descriptions($p['ocr_text']);
            // print_r($descriptions);
            foreach($descriptions as $description)
            {
                $media['identifier']             = md5($p['wiki_title'].$description);
                $media['description']            = $description;

                // below here is same for the next text object
                $media['taxonID']                = implode("|", $taxon_ids);
                $media['type']                   = "http://purl.org/dc/dcmitype/Text";
                $media['format']                 = "text/html";
                $media['language']               = self::format_language($p['language']);
                $media['Owner']                  = $p['rightsholder'];
                $media['Publisher']              = 'Biodiversity Heritage Library';
                $media['rights']                 = ''; //ask Katja about it
                $media['UsageTerms']             = self::format_license($p['license_type']);
                $media['furtherInformationURL']  = str_replace("api.php", "wiki/", $this->mediawiki_api) . $p['wiki_title'];
                $media['agent']                  = $p['agents'];
                $media['bibliographicCitation']  = $p['bibliographicCitation'];

                $media['reference_ids'] = array();
                if($val = $p['references']) $media['reference_ids'] = self::get_reference_ids($val);

                self::create_media_object($media);

                // // Brief Summary
                // $media['identifier']             = md5($rec['permalink']."Brief Summary");
                // $media['title']                  = $rec['title'] . ': Brief Summary';
                // $media['description']            = $rec['brief_desc'];
                // $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
                // self::create_media_object($media);
                
            }

        }//foreach name
    }

    private function get_reference_ids($str)
    {
        $refs = explode("\n", $str);
        $refs = array_map('trim', $refs);
        $refs = array_filter($refs);
        
        $reference_ids = array();
        foreach($refs as $ref)
        {
            $ref_id = md5($ref);
            $reference_ids[] = $ref_id;
            $ref_url = '';
            $citation = trim($ref);
            self::add_reference($citation, $ref_id, $ref_url);
        }
        return $reference_ids;
    }
    
    private function add_reference($citation, $ref_id, $ref_url = false)
    {
        if($citation)
        {
            $r = new \eol_schema\Reference();
            $r->full_reference = (string) $citation;
            $r->identifier = $ref_id;
            if($ref_url) $r->uri = $ref_url;
            if(!isset($this->resource_reference_ids[$r->identifier]))
            {
               $this->resource_reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
    }
    
    private function create_media_object($media)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID                = $media['taxonID'];
        $mr->identifier             = $media['identifier'];
        $mr->type                   = $media['type'];
        $mr->format                 = $media['format'];
        $mr->language               = $media['language'];
        $mr->Owner                  = @$media['Owner'];
        $mr->publisher              = @$media['Publisher'];
        $mr->rights                 = $media['rights'];
        $mr->title                  = $media['title'];
        $mr->UsageTerms             = $media['UsageTerms'];
        $mr->description            = $media['description'];
        $mr->CVterm                 = $media['CVterm'];
        $mr->bibliographicCitation  = $media['bibliographicCitation'];
        $mr->furtherInformationURL  = $media['furtherInformationURL'];
        
        if($val = @$media['agent'])
        {
            if($agent_ids = self::create_agents($val)) $mr->agentID = implode("; ", $agent_ids);
        }

        $mr->audience = $media['audience'];
        
        if($val = @$media['reference_ids']) $mr->referenceID = implode("; ", $val);
        
        if(!isset($this->object_ids[$mr->identifier]))
        {
            $this->object_ids[$mr->identifier] = '';
            $this->archive_builder->write_object_to_file($mr);
        }
    }

    private function format_descriptions($str)
    {
        $descs = explode("\n", $str);
        $descs = array_map('trim', $descs);
        $descs = array_filter($descs);
        return $descs;
    }
    
    private function format_audience($p)
    {
        $str = "";
        if(isset($p['scientists'])) $str .= "scientists; ";
        if(isset($p['public']))     $str .= "public; ";
        if(isset($p['children']))   $str .= "children; ";
        $str = trim($str);
        return substr($str, 0, -1);
    }
    
    private function create_agents($agents)
    {
        $agent_ids = array();
        $agents = explode(";", $agents);
        $agents = array_map('trim', $agents);
        foreach($agents as $agent)
        {
            if(!$agent) continue;
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->identifier = md5("$agent|" . 'author');
            $r->agentRole = 'author';
            $r->term_homepage = "";
            $agent_ids[] = $r->identifier;
            if(!isset($this->resource_agent_ids[$r->identifier]))
            {
               $this->resource_agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }

    /* not used anymore
    private function create_agents($agents)
    {
        $agent_ids = array();
        foreach($agents as $rec)
        {
            $agent = (string) trim($rec["fullName"]);
            if(!$agent) continue;
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->identifier = md5("$agent|" . $rec["role"]);
            $r->agentRole = $rec["role"];
            $r->term_homepage = @$rec["homepage"];
            $agent_ids[] = $r->identifier;
            if(!isset($this->resource_agent_ids[$r->identifier]))
            {
               $this->resource_agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    */
    
    private function format_language($lang_name)
    {
        $langs = array(
            array("name" => "English",           "abb" => "en"), //en
            array("name" => "Spanish",           "abb" => "es"), //es
            array("name" => "French",            "abb" => "fr"), //fr
            array("name" => "German",            "abb" => "de"), //de
            array("name" => "Portugus-Brasil",   "abb" => "br"), //br
            array("name" => "Portugus-Portugal", "abb" => "pt") //pt
        );
        foreach($langs as $lang)
        {
            if($lang['name'] == $lang_name) return $lang['abb'];
        }
    }
    private function format_license($license_value)
    {
        //license array came from BHL controller
        $licenses = array(
        array("value" => "Attribution 3.0",                             "t" => "CC BY",                           "url" => "http://creativecommons.org/licenses/by/3.0/"),
        array("value" => "Attribution-NonCommercial 3.0",               "t" => "CC BY NC",                        "url" => "http://creativecommons.org/licenses/by-nc/3.0/"),
        array("value" => "Attribution-ShareAlike 3.0",                  "t" => "CC BY SA",                        "url" => "http://creativecommons.org/licenses/by-sa/3.0/"),
        array("value" => "Attribution-NonCommercial-ShareAlike 3.0",    "t" => "CC BY NC SA",                     "url" => "http://creativecommons.org/licenses/by-nc-sa/3.0/"),
        array("value" => "Public Domain",                               "t" => "Public Domain",                   "url" => "http://creativecommons.org/licenses/publicdomain/"),
        array("value" => "no known copyright restrictions",             "t" => "no known copyright restrictions", "url" => "no known copyright restrictions"));
        foreach($licenses as $license)
        {
            if($license['value'] == $license_value) return $license['url'];
        }
    }
    
    private function parse_wiki_content($wiki)
    {
        $rec = array();
        $rec['agent'] = self::process_provider_agent($wiki);
        
        if(preg_match_all("/class=\"wikitable\"(.*?)\|\}/ims", $wiki, $arr))
        {
            foreach($arr[1] as $section)
            {
                if(preg_match("/name=\"(.*?)\"/ims", $section, $arr2))
                {
                    $index = $arr2[1];
                    
                    if(stripos($section, 'scope="row"') !== false) //string is found
                    {
                        echo "\n $index is row";
                        $rec[$index] = self::process_row_table($section);
                    }
                    elseif(stripos($section, 'scope="col"') !== false) //string is found
                    {
                        echo "\n $index is col";
                        $rec[$index] = self::process_col_table($section);
                    }

                    elseif(stripos($section, 'name="Taxa Found in Page"') !== false) //string is found
                    {
                        echo "\n $index is Taxa Found in Page";
                        $rec[$index] = self::process_ocr_text($section, "Taxa Found in Page");
                    }

                    elseif(stripos($section, 'name="OCR Text"') !== false) //string is found
                    {
                        echo "\n $index is OCR Text";
                        $rec[$index] = self::process_ocr_text($section, "OCR Text");
                    }

                    elseif(stripos($section, 'name="Subject Type"') !== false) //string is found
                    {
                        echo "\n $index is Subject Type";
                        $rec[$index] = self::process_ocr_text($section, "Subject Type");
                    }
                    
                    elseif(stripos($section, 'name="Audience Type"') !== false) //string is found
                    {
                        echo "\n $index is Audience Type";
                        $rec[$index] = self::process_ocr_text($section, "Audience Type");
                    }
                    
                    elseif(stripos($section, 'name="User-defined Title"') !== false) //string is found
                    {
                        echo "\n $index is User-defined Title";
                        $rec[$index] = self::process_ocr_text($section, "User-defined Title");
                        $rec[$index] = str_replace("''", "", $rec[$index]);
                    }

                    elseif(stripos($section, 'name="User-defined References"') !== false) //string is found
                    {
                        echo "\n $index is User-defined References";
                        $temp = self::process_ocr_text($section, "User-defined References");
                        $rec[$index] = self::parse_ref($temp['text']);
                    }

                    elseif(stripos($section, 'name="Licensor"') !== false) //string is found
                    {
                        echo "\n $index is Licensor";
                        $rec[$index] = self::process_ocr_text($section, "Licensor");
                    }

                    elseif(stripos($section, 'name="License Type"') !== false) //string is found
                    {
                        echo "\n $index is License Type";
                        $rec[$index] = self::process_ocr_text($section, "License Type");
                    }

                    elseif(stripos($section, 'name="Bibliographic Citation"') !== false) //string is found
                    {
                        echo "\n $index is Bibliographic Citation";
                        $rec[$index] = self::process_ocr_text($section, "Bibliographic Citation");
                    }
                    
                    elseif(stripos($section, 'name="Authors"') !== false) //string is found
                    {
                        echo "\n $index is Authors";
                        $rec[$index] = self::process_multiple_row_text($section, "Authors");
                    }
                    

                }
                else
                {
                    echo "\nInvestigate 001\n"; exit;
                }
            }
        }
        
        if($val = $rec['Authors']) $rec['agent'] = array_merge($rec['agent'], self::process_author_agent($val));
        
        
        print_r($rec); //exit;
        return $rec;
    }

    private function parse_ref($str)
    {
        $recs = array();
        $exclude = array("John's Handbook, Third Edition, Doe-Roe Co., 1972.", "[http://www.eol.org Link text], my 2nd sample reference.");
        /*
        <ref name="ref1">John's Handbook, Third Edition, Doe-Roe Co., 1972.</ref><!-- Put your reference here or leave it as is. This sample won't be imported -->
        <ref name="ref2">[http://www.eol.org Link text], my 2nd sample reference.</ref>
        */
        
        //1st step
        if(preg_match_all("/<ref name=(.*?)<\/ref>/ims", $str, $arr))
        {
            foreach($arr[1] as $t)
            {
                $a = explode('">', $t);
                if(!in_array($a[1], $exclude)) $recs[] = array("id" => str_replace('"', "", $a[0]), "ref" => $a[1]);
            }
        }
        
        //2nd step
        $final = array();
        $i = 0;
        foreach($recs as $rec)
        {
            if(preg_match("/\[http:(.*?)\]/ims", $rec['ref'], $arr))
            {
                // echo "\n[$arr[1]]\n";
                $temp = explode(" ", $arr[1]);
                // print_r($temp);
                $recs[$i]['url'] = "http:".$temp[0];
                array_shift($temp);
                $recs[$i]['link_text'] = implode(" ", $temp);
            }
            $i++;
        }
        return $recs;
    }

    private function process_provider_agent($wiki)
    {
        $agent = array();
        //Contributing User: [http://editors.eol.localhost/LiteratureEditor/wiki/User:EAgbayani <b>EAgbayani</b>]
        if(preg_match("/<b>(.*?)<\/b>/ims", $wiki, $arr))
        {
            $agent['fullName'] = $arr[1];
            if(preg_match("/Contributing User: \[http:\/\/(.*?) /ims", $wiki, $arr))
            {
                $agent['homepage'] = "http://" . $arr[1];
                $agent['role'] = 'provider';
            }
            return array($agent);
        }
        return false;
    }

    private function process_author_agent($names)
    {
        $agents = array();
        foreach($names as $name)
        {
            $agent_id = false;
            if(preg_match("/\{(.*?)\}/ims", $name, $arr)) $agent_id = $arr[1];
            $name = str_replace(' {'.$agent_id.'}', "", $name);
            $agent = array();
            $agent['fullName'] = $name;
            if($agent_id) $agent['homepage'] = "http://www.biodiversitylibrary.org/creator/" . $agent_id . "#/titles";
            $agent['role'] = 'author';
            $agents[] = $agent;
        }
        return $agents;
    }

    private function process_ocr_text($section, $str)
    {
        /*
            ===User-defined Title (optional)===
            {| class="wikitable" style="color:green; background-color:#ffffcc;" name="User-defined Title"
            |+ style="caption-side:right;"|[[Image:arrow-up icon.png|link=#top|Go top]]
            |''enter title here''
            |-
            |}
        */
        if(preg_match("/Go top\]\](.*?)\|-/ims", $section, $arr))
        {
            $text = trim($arr[1]);
            $text = substr($text, 1, strlen($text));
            return array("text" => $text);
        }
    }

    private function process_multiple_row_text($section, $str)
    {
        /*
            ===Authors===
            {| class="wikitable" style="color:green; background-color:#ffffcc;" name="Authors"
            |+ style="caption-side:right;"|[[Image:arrow-up icon.png|link=#top|Go top]]
            |British Museum (Natural History).
            |-
            |Gray, John Edward
            |-
            |}
        */
        if(preg_match("/Go top\]\](.*?)xxx/ims", $section."xxx", $arr))
        {
            if(preg_match_all("/\|(.*?)\|-/ims", $arr[1], $arr2))
            {
                return array_map("trim", $arr2[1]);
            }
        }
    }

    
    private function process_col_table($section)
    {
        $fields = array();
        $a = explode("\n", $section);

        //get labels
        if(preg_match_all("/scope=\"col\"\|(.*?)\|/ims", $a[3]."|", $arr))
        {
            $labels = array_map("trim", $arr[1]);
        }

        //get values
        for($i = 4; $i <= count($a)-1; $i++)
        {
            // echo "\n[$i]" . count($a[$i]) . "\n";
            $values = explode("||", "|".$a[$i]."||");
            $values = array_map("trim", $values);
            array_pop($values);     //remove first value
            array_shift($values);   //remove last value
            if(count($labels) == count($values))
            {
                if(count($labels) == count($values))
                {
                    $j = -1;
                    foreach($labels as $label)
                    {
                        $j++;
                        if($values[$j] != "-") $fields[$label][] = self::remove_comments($values[$j]);
                    }
                }
                else
                {
                    echo "\nInvestigate 002\n";
                    echo "\n[$section]\n";
                    exit;
                }
            }
        }
        return $fields;
    }

    private function process_row_table($section)
    {
        $fields = array();
        if(preg_match_all("/scope=\"row\"(.*?)\|\-/ims", $section, $arr))
        {
            foreach($arr[1] as $temp)
            {
                $a = explode("|", $temp);
                $a = array_map("trim", $a);
                $fields[$a[1]] = self::remove_comments($a[2]);
            }
        }
        return $fields;
    }
    
    private function remove_comments($str)
    {
        return trim(preg_replace('/\s*\<!--[^)]*\-->/', '', $str)); //remove <!-- -->
    }

}
?>
