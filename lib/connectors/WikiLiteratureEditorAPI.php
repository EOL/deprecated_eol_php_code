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
        $this->download_options = array('resource_id' => $resource_id, 'expire_seconds' => true, 'download_wait_time' => 5000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->namespace = 5000; //ForHarvesting
    }

    function generate_archive()
    {
        self::list_all_pages();
        $this->archive_builder->finalize(TRUE);
    }
    
    private function list_all_pages()
    {
        $eilimit = 500; //orig 500 debug
        $continue = false;
        $i = 0;
        $k = 0; //just used when caching, running multiple connectors
        while(true)
        {
            $url = $this->mediawiki_api . "?action=query&list=allpages&aplimit=$eilimit&format=json&apnamespace=" . $this->namespace . "&continue=";
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
            // if($rec->title != "ForHarvesting:16194405 ae66e9b6f430af7e694cad4cf1d6f295") continue; //debug only
            echo "\n" . $rec->title . "\n";
            self::process_title($rec->title);
        }
    }
    
    function process_title($title)
    {
        $info = self::get_wiki_text($title);
        $params = self::get_void_part($info['content']);
        if($params['header_title']) //to exclude the likes of "Main Page"
        {
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
        // print_r($p);
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
                $media['compiler']               = $p['compiler'];
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
        
        //=========================================
        if($temp = @$media['agent'])
        {
            $temp = explode(";", $temp);
            $temp = array_map('trim', $temp);
            $recs = array(); $rec = array();
            foreach($temp as $t)
            {
                $rec['fullName'] = $t;
                $rec['role'] = "author";
                if($rec) $recs[] = $rec;
            }
            if($agent_ids = self::create_agents($recs)) $mr->agentID = implode("; ", $agent_ids);
        }
        //=========================================
        // [http://editors.eol.localhost/LiteratureEditor/wiki/User:Contributor1 Contributor one]; [http://editors.eol.localhost/LiteratureEditor/wiki/User:EAgbayani Eli E. Agbayani]
        if($temps = @$media['compiler'])
        {
            $recs = array();
            $temps = explode(";", $temps);
            $temps = array_map('trim', $temps);
            foreach($temps as $temp)
            {
                $arr = explode(" ", $temp);
                $homepage = trim(substr($arr[0], 1, strlen($arr[0])));
                array_shift($arr);
                $name = implode(" ", $arr);
                $name = substr($name, 0, -1);
                $recs[] = array('fullName' => $name, 'role' => "compiler", 'homepage' => $homepage);
            }
            if($agent_ids = self::create_agents($recs)) $mr->agentID = implode("; ", $agent_ids);
        }
        //=========================================

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
        foreach($agents as $rec)
        {
            if($agent = (string) trim($rec["fullName"]))
            {
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
        }
        return $agent_ids;
    }
    
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
    
}
?>
