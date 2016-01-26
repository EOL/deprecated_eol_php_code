<?php
namespace php_active_record;
/* connector: [mediawiki.php] */

class WikiLiteratureEditorAPI
{
    function __construct($resource_id, $mediawiki_api)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->wikipedia_api = $mediawiki_api; //http://editors.eol.localhost/LiteratureEditor/api.php
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
            $url = $this->wikipedia_api . "?action=query&list=allpages&aplimit=$eilimit&format=json&continue=";
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
            // print_r($rec); exit;
            // if($rec->title != "42194843") continue; //debug only
            // if($rec->title != "42194845") continue; //debug only
            // if($rec->title != "33870179") continue; //debug only --with copyrightstatus
            // if($rec->title != "13128418") continue; //debug only --with licensor (13128418, 30413122)
            // if($rec->title != "42194845") continue; //debug only --without licensor
            if($rec->title != "30413130") continue; //debug only

            echo "\n" . $rec->title;
            $url = $this->wikipedia_api . "?action=query&titles=" . urlencode($rec->title) . "&format=json&prop=revisions&rvprop=content";
            $json = Functions::lookup_with_cache($url, array('expire_seconds' => true)); //this expire_seconds should always be true
            $arr = json_decode($json, true);
            foreach(@$arr['query']['pages'] as $page) //there is really just one page here...
            {
                if($val = @$page['revisions'][0]['*'])
                {
                    if($data = self::parse_wiki_content($val))
                    {
                        if(isset($data['Taxa Found in Page']['NameConfirmed'])) self::create_archive($data);
                        else echo "\n[no taxa found for wiki: ".$data['Page Summary']['PageID']."]\n";
                    }
                }
            }
        }
    }

    private function create_archive($rec)
    {
        //get taxon_ids =========================
        $taxon_ids = array();
        foreach($rec['Taxa Found in Page']['NameConfirmed'] as $name)
        {
            if($name = trim($name))
            {
                $taxon_ids[str_replace(" ", "_", strtolower($name))] = '';
            }
        }
        $taxon_ids = array_keys($taxon_ids);
        //=======================================
        
        foreach($rec['Taxa Found in Page']['NameConfirmed'] as $name)
        {
            if(!trim($name)) continue;
            if(stripos($name, 'NameConfirmed') !== false) continue; //string is found
            
            $t = new \eol_schema\Taxon();
            $t->taxonID                 = str_replace(" ", "_", strtolower($name));
            $t->scientificName          = $name;
            // $t->order                   = @$rec['ancestry']['order'];
            // $t->family                  = @$rec['ancestry']['family'];
            // $t->genus                   = @$rec['ancestry']['genus'];
            // $t->furtherInformationURL   = $rec['permalink'];
            // $t->$rank = ''; 
            if(!isset($this->taxon_ids[$t->taxonID]))
            {
                $this->taxon_ids[$t->taxonID] = '';
                $this->archive_builder->write_object_to_file($t);
            }

            //start media objects
            $media = array();

            // text object
            $media['identifier']             = $rec['Page Summary']['PageID'];
            $media['title']                  = @$rec['User-defined Title']['text']; //per Katja: user enters title
            $media['description']            = $rec['OCR Text']['text'];
            $media['CVterm']                 = $rec['Subject Type']['text'];
            $media['audience']               = $rec['Audience Type']['text'];
            

            // below here is same for the next text object
            $media['taxonID']                = implode("|", $taxon_ids);
            $media['type']                   = "http://purl.org/dc/dcmitype/Text";
            $media['format']                 = "text/html";
            $media['language']               = self::format_language($rec['Item Summary']['Language']);

            $media['Owner']                  = self::format_owner($rec);
            $media['Publisher']              = 'Biodiversity Heritage Library';
            $media['rights']                 = $rec['Item Summary']['Rights'];
            
            $media['UsageTerms']             = self::format_license($rec);
            $media['furtherInformationURL']  = $rec['Page Summary']['PageUrl'];
            
            $media['agent']                  = $rec['agent'];
            
            $media['reference_ids'] = array();
            if($val = $rec['User-defined References']) $media['reference_ids'] = self::get_reference_ids($val);
            
            self::create_media_object($media);

            // // Brief Summary
            // $media['identifier']             = md5($rec['permalink']."Brief Summary");
            // $media['title']                  = $rec['title'] . ': Brief Summary';
            // $media['description']            = $rec['brief_desc'];
            // $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
            // self::create_media_object($media);
        }//foreach name
    }

    private function get_reference_ids($recs)
    {
        /*
        [0] => Array
            (
                [id] => ref1
                [ref] => John's Handbook, Third Edition, Doe-Roe Co., 1973.
            )

        [1] => Array
            (
                [id] => ref2
                [ref] => [http://www.eol.org Link text], my second sample reference.
                [url] => http://www.eol.org
                [link_text] => Link text
            )

        [2] => Array
            (
                [id] => ref3
                [ref] => [www.fishbase.org FishBase link], my 3rd sample reference.
            )
        */
        $reference_ids = array();
        foreach($recs as $rec)
        {
            $ref_id = md5($rec['ref']);
            $reference_ids[] = $ref_id;
            $ref_url = @$rec['url']; //not all ref records have 'url'
            $citation = trim($rec['ref']);
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
            $r->term_homepage = $rec["homepage"];
            $agent_ids[] = $r->identifier;
            if(!isset($this->resource_agent_ids[$r->identifier]))
            {
               $this->resource_agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }

    private function format_language($lang)
    {
        return substr($lang,0,2);
    }
    
    private function format_owner($rec)
    {
        if(isset($rec['Licensor']['text']))
        {
            if($val = $rec['Licensor']['text']) return $val;
        }
        else return $rec['Item Summary']['CopyrightStatus'];
    }
    
    private function format_license($rec)
    {
        if(isset($rec['License Type']['text']))
        {
            if($val = $rec['License Type']['text']) return $val;
        }
        else return $rec['Item Summary']['LicenseUrl'];
    }
    
    private function parse_wiki_content($wiki)
    {
        $rec = array();

        $rec['agent'] = self::process_agent($wiki);
        
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
                        $rec[$index] = str_replace("'", "", $rec[$index]);
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

                }
                else
                {
                    echo "\nInvestigate 001\n"; exit;
                }
            }
        }
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

    private function process_agent($wiki)
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
    
    private function process_ocr_text($section, $str)
    {
        /*
            ===User-defined Title (optional)===
            {| class="wikitable" name="User-defined Title" style="color:green; background-color:#ffffcc;"
            |''enter title here''
            |-
            |}
            
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
                        if($values[$j] != "-") $fields[$label][] = $values[$j];
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
                $fields[$a[1]] = $a[2];
            }
        }
        return $fields;
    }

}
?>
