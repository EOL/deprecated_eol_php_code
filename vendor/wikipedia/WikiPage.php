<?php

class WikiPage
{
    public $xml;
    private $simple_xml;
    public static $API_URL = 'http://en.wikipedia.org/w/api.php';
    
    function __construct($xml)
    {
        $this->xml = $xml;
        $this->simple_xml = simplexml_load_string($this->xml);
        // this is from the API, not the dump
        if(preg_match("/^<\?xml version=\"1\.0\"\?><api><query>/", $xml))
        {
            $this->text = (string) $this->simple_xml->query->pages->page->revisions->rev;
            $this->title = (string) $this->simple_xml->query->pages->page['title'];
            $this->pageid = (string) $this->simple_xml->query->pages->page['pageid'];
            $this->contributor = (string) $this->simple_xml->query->pages->page->revisions->rev['user'];
            $this->revision = (string) $this->simple_xml->query->pages->page->revisions->rev['revid'];
            $this->timestamp = (string) $this->simple_xml->query->pages->page->revisions->rev['timestamp'];
        }elseif($this->simple_xml)
        {
            $this->text = (string) $this->simple_xml->revision->text;
            $this->title = (string) $this->simple_xml->title;
            $this->pageid = (string) $this->simple_xml->id;
            $this->contributor = (string) $this->simple_xml->revision->contributor->username;
            $this->revision = (string) $this->simple_xml->revision->id;
            $this->timestamp = (string) $this->simple_xml->revision->timestamp;
        }
    }

    public static function from_api($title)
    {
        $api_url = self::$API_URL.'?action=query&format=xml&prop=revisions&titles='. urlencode($title) .'&rvprop=ids|timestamp|user|content&redirects';
        return new WikiPage(php_active_record\Functions::get_remote_file($api_url));
    }

    public function is_scientific()
    {
        if(preg_match("/\n *\| *(regnum|phylum|classis|superordo|ordo|familia|genus|species|binomial|subspecies|variety|trinomial) *=/i", $this->xml)) return true;
        
        return false;
    }
    
    public function text()
    {
        if($this->simple_xml)
        {
            return $this->simple_xml->revision->text;
        }
        
        return false;
    }
    
    public function title()
    {
        if($this->simple_xml)
        {
            return $this->simple_xml->title;
        }
        
        return false;
    }
    
    public function scientific_names()
    {
        $taxonomy = $this->taxonomy();
        
        $name = "";
        
        
        $names = array();
        
        if(@$taxonomy["subdivision"])
        {
            // $subdivisions = explode("<br>", $taxonomy["subdivision"]);
            // foreach($subdivisions as $subdivision)
            // {
            //     $subdivision = trim($subdivision);
            //     $names[] = php_active_record\Functions::import_decode($subdivision, true, true);
            // }
        }elseif(@$taxonomy["includes"])
        {
            // $includes = explode("<br>", $taxonomy["includes"]);
            // foreach($includes as $include)
            // {
            //     $include = trim($include);
            //     $include = preg_replace("/^:/", "", $include);
            //     $names[] = php_active_record\Functions::import_decode($include, true, true);
            // }
        }else
        {
            foreach($GLOBALS['taxon_rank_priority'] as $rank)
            {
                if(@$taxonomy[$rank] && !preg_match("/<br/",$taxonomy[$rank]))
                {
                    $names[] = php_active_record\Functions::import_decode($taxonomy[$rank], true, true);
                    break;
                }
            }
        }
        
        // Remove some extra junk
        foreach($names as $key => $name)
        {
            while(preg_match("/&dagger;/", $name)) $name = trim(str_replace("&dagger;", " ", $name));
            while(preg_match("/†/u", $name)) $name = trim(str_replace("†", " ", $name));
            while(preg_match("/\*/", $name)) $name = trim(str_replace("*", "", $name));
            while(preg_match("/\?/", $name)) $name = trim(str_replace("?", "", $name));
            while(preg_match("/'/", $name)) $name = trim(str_replace("'", "", $name));
            $name = preg_replace("/ in part$/ims", "", $name);
            
            $names[$key] = $name;
        }
        
        return $names;
    }
    
    public function taxonomy()
    {
        if(isset($this->taxonomy)) return $this->taxonomy;
        
        $taxonomy = array();
        if(preg_match("/(\{\{\s*Taxobox.*?\}\})(.*)/msi", $this->text, $arr))
        {
            // this is a work around to make sure entire taxobox contents are retrieved
            list($taxobox, $junk) = WikiParser::balance_tags("{{", "}}", $arr[1], $arr[2], true);
            
            // using a custom wikiparser to turn the wiki text into HTML-free usable text
            $stripped_taxobox = WikiParser::strip_syntax($taxobox, false, $this->title());
            
            $parts = explode("|", $stripped_taxobox);
            foreach($parts as $part)
            {
                if(preg_match("/^\s*([^\s]*)\s*=(.*)$/ms", $part, $arr))
                {
                    $attribute = trim($arr[1]);
                    $value = trim($arr[2]);
                    
                    $value = strip_tags(WikiParser::strip_syntax($value));
                    if(preg_match("/^(.*?)</ims", $value, $arr)) $value = $arr[1];
                    while(preg_match("/^(.*)\(or .*?\)\s*$/ims", $value, $arr)) $value = $arr[1];
                    
                    $value = str_ireplace("&dagger;", " ", $value);
                    $value = str_ireplace("&mdash;", " ", $value);
                    $value = str_replace("†", " ", $value);
                    $value = str_replace("*", "", $value);
                    $value = str_replace("?", "", $value);
                    $value = str_replace("'", "", $value);
                    $value = str_ireplace("(unplaced)", "", $value);
                    
                    $value = preg_replace("/verify source/ims", "", $value);
                    $value = preg_replace("/incertae sedis/ims", "", $value);
                    $value = preg_replace("/ in part$/ims", "", $value);
                    $value = preg_replace("/\((formerly|but|see) .*?\)/ims", "", $value);
                    $value = preg_replace("/ and\s*$/ims", "", $value);
                    $value = preg_replace("/^clade \s*$/ims", "", $value);
                    
                    // get rid of extra whitespace
                    while(preg_match("/  /", $value)) $value = str_replace("  ", " ", $value);
                    $value = trim($value);
                    
                    // reasons for skipping this attribute
                    if(preg_match("/^[a-z]/", $value)) continue;
                    if(preg_match("/(^| |\()disputed/ims", $value)) continue;
                    if(preg_match("/ or /ims", $value)) continue;
                    if(preg_match("/(taxobox|uncertain|\[\[|\]\]|possibly)/ims", $value)) continue;
                    if(!$value) continue;
                    if(!php_active_record\Functions::is_utf8($value)) continue;
                    
                    $taxonomy[$attribute] = $value;
                }
            }
        }
        
        // expanding abbreviated species (P. saltator) with genus value
        if(@$taxonomy["species"] && preg_match("/^[A-Z][a-z]{0,2}\.( .*)$/", $taxonomy["species"], $arr))
        {
            if(@$taxonomy["genus"] && preg_match("/^[^ ]*$/", $taxonomy["genus"])) $taxonomy["species"] = $taxonomy["genus"] . $arr[1];
        }
        
        if(@$taxonomy["binomial"] && preg_match("/^[A-Z][a-z]{0,2}\.( .*)$/", $taxonomy["binomial"], $arr))
        {
            if(@$taxonomy["genus"] && preg_match("/^[^ ]*$/", $taxonomy["genus"])) $taxonomy["binomial"] = $taxonomy["genus"] . $arr[1];
        }
        
        if(@$taxonomy["subspecies"] && preg_match("/^[A-Z][a-z]{0,2}\.( .*)$/", $taxonomy["subspecies"], $arr))
        {
            if(@$taxonomy["genus"] && preg_match("/^[^ ]*$/", $taxonomy["genus"])) $taxonomy["subspecies"] = $taxonomy["genus"] . $arr[1];
        }
        
        if(@$taxonomy["trinomial"] && preg_match("/^[A-Z][a-z]{0,2}\.( .*)$/", $taxonomy["trinomial"], $arr))
        {
            if(@$taxonomy["genus"] && preg_match("/^[^ ]*$/", $taxonomy["genus"])) $taxonomy["trinomial"] = $taxonomy["genus"] . $arr[1];
        }
        
        
        // appending attributes' authorities
        foreach($taxonomy as $key => $value)
        {
            if(@$taxonomy[$key."_authority"]) $taxonomy[$key] .= " " . $taxonomy[$key."_authority"];
        }
        
        $this->taxonomy = $taxonomy;
        return $taxonomy;
    }
    
    public function common_names($taxon_name)
    {
        if(isset($this->common_names)) return $this->common_names;
        
        $common_names = array();
        if(preg_match_all("/\n\[\[([a-z]{2}):(.{0,100})\]\]\n/i", $this->text, $arr, PREG_SET_ORDER))
        {
            foreach($arr as $match)
            {
                $lang = $match[1];
                $name = $match[2];
                if($name == $taxon_name || $name == php_active_record\Functions::canonical_form($taxon_name)) continue;
                if(!php_active_record\Functions::is_utf8($name)) continue;
                //if(isset($GLOBALS['iso_639_2_codes'][$lang])) $lang = $GLOBALS['iso_639_2_codes'][$lang];
                
                $common_names[] = new SchemaCommonName(array('name' => $name, 'language' => $lang));
            }
        }
        
        $this->common_names = $common_names;
        return $common_names;
    }
    
    public function taxon_parameters()
    {
        if(isset($this->taxon_parameters)) return $this->taxon_parameters;
        $taxonomy = $this->taxonomy();
        
        $taxon_rank = "";
        $taxon_name = "";
        
        foreach($GLOBALS['taxon_rank_priority'] as $rank)
        {
            if(@$taxonomy[$rank])
            {
                $taxon_name = $taxonomy[$rank];
                $taxon_rank = $rank;
                break;
            }
        }
        
        if(!$taxon_name)
        {
            $this->taxon_parameters = array();
            return $this->taxon_parameters;
        }
        
        $taxon_parameters = array();
        //$taxon_parameters["identifier"] = $this->pageid;
        if($taxon_rank!='regnum' && $v = @$taxonomy['regnum']) $taxon_parameters['kingdom'] = $v;
        if($taxon_rank!='phylum' && $v = @$taxonomy['phylum']) $taxon_parameters['phylum'] = $v;
        if($taxon_rank!='classis' && $v = @$taxonomy['classis']) $taxon_parameters['class'] = $v;
        if($taxon_rank!='ordo' && $v = @$taxonomy['ordo']) $taxon_parameters['order'] = $v;
        if($taxon_rank!='familia' && $v = @$taxonomy['familia']) $taxon_parameters['family'] = $v;
        if($taxon_rank!='genus' && $v = @$taxonomy['genus']) $taxon_parameters['genus'] = $v;
        $taxon_parameters['scientificName'] = $taxon_name;
        $taxon_parameters["source"] = "http://en.wikipedia.org/w/index.php?title=". str_replace(" ", "_", $this->title);
        $taxon_parameters['commonNames'] = $this->common_names($taxon_name);
        
        if($taxon_rank == 'familia' || $taxon_rank == 'genus' || @$taxon_parameters['family'] || @$taxon_parameters['species'] || @$taxon_parameters['genus'])
        {
            $taxon_parameters['dataObjects'] = array();
            $this->taxon_parameters = $taxon_parameters;
            return $taxon_parameters;
        }else
        {
            echo "   not a family or below\n";
            $this->taxon_parameters = array();
            return $this->taxon_parameters;
        }
    }
    
    public function data_object_parameters($download_text = true)
    {
        if(isset($this->data_object_parameters)) return $this->data_object_parameters;
        
        $data_object_parameters = array();
        
        $data_object_parameters["identifier"] = $this->pageid;
        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/Text";
        $data_object_parameters["mimeType"] = "text/html";
        $data_object_parameters["title"] = $this->title;
        $data_object_parameters["language"] = 'en';
        $data_object_parameters["license"] = "http://creativecommons.org/licenses/by-sa/3.0/";
        $data_object_parameters["source"] = "http://en.wikipedia.org/w/index.php?title=". str_replace(" ", "_", $this->title) ."&oldid=". $this->revision;
        $data_object_parameters["subjects"] = array(new SchemaSubject(array("label" => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription")));
        if($this->timestamp)
        {
            $revision_date = date_create($this->timestamp);
            $data_object_parameters["bibliographicCitation"] = "\"$this->title.\" <i>Wikipedia, The Free Encyclopedia</i>. ". date_format($revision_date, 'j M Y, H:i') ." UTC. ". date('j M Y') ." &lt;<a href=\"". $data_object_parameters["source"] ."\">". $data_object_parameters["source"] ."</a>&gt;.";
        }
        
        if($download_text && $description = $this->get_page_html())
        {
            $data_object_parameters["description"] = $description;
        }
        if(@!$data_object_parameters["description"]) $data_object_parameters = array();
        
        return $data_object_parameters;
    }
    
    
    
    
    
    
    public function get_page_html()
    {
        $response = php_active_record\Functions::get_hashed_response_fake_browser("http://en.wikipedia.org/w/api.php?action=parse&format=xml&prop=text&oldid=$this->revision");
        if(@$response->parse->text)
        {
            return self::wikipedia_to_eol_html($response->parse->text);
        }
        return false;
    }

    public static function wikipedia_to_eol_html($html)
    {
        // get rid of the edit links
        $html = preg_replace("/<span class=\"editsection\">.*?<\/span>/", "", $html);
        
        // truncate the page at the External Links section which is usually last
        $html = preg_replace("/<h2> *<span class=\"mw-headline\" id=\"External_links\"> *External links *<\/span>(.*)$/ims", '', $html);
        
        // remove the Taxobox panel
        if(preg_match("/^(.*?)(<table class=\"[^\"]*biota.*)$/ims", $html, $arr))
        {
            $html = $arr[1] . self::remove_block('<table', '<\/table>', $arr[2], 1);
        }
        
        // remove some styles which add columns - this doesn't work well in our text panel
        $html = preg_replace("/references-column-count-[0-9]+/", "", $html);
        $html = preg_replace("/references-column-count/", "", $html);
        $html = preg_replace("/-moz-column-count: *[0-9]+;/", "", $html);
        $html = preg_replace("/column-count: *2;/", "", $html);
        $html = preg_replace("/-moz-column-width: *[0-9]+em;/", "", $html);
        $html = preg_replace("/column-width: *[0-9]+em;/", "", $html);
        
        // update all relative paths
        $html = preg_replace("/src=\"\/\//", "src=\"http://", $html);
        $html = preg_replace("/src=\"\//", "src=\"http://en.wikipedia.org/", $html);
        $html = preg_replace("/href=\"\/\//", "target=\"wikipedia\" href=\"http://", $html);
        $html = preg_replace("/href=\"\//", "target=\"wikipedia\" href=\"http://en.wikipedia.org/", $html);
        
        $html = '<div id="globalWrapper"><div id="column-content"><div id="content"><div id="bodyContent">' . $html . '</div></div></div></div>';
        
        // remove unnecessary newlines
        $html = str_replace("\r", "", $html);
        $html = str_replace("\n", "", $html);
        return $html;
    }
    
    private static function remove_block($open_tag, $close_tag, $text, $number_of_end_tags)
    {
        while($number_of_end_tags)
        {
            if(preg_match("/^((.*?$close_tag){".$number_of_end_tags."})(.*)$/ims", $text, $arr))
            {
                $to_remove = $arr[1];
                $text = $arr[3];

                $num_open = preg_match_all("/$open_tag/ms", $to_remove, $arr);
                $num_close = preg_match_all("/$close_tag/ms", $to_remove, $arr);
                if($num_close < $num_open) $number_of_end_tags = $num_open - $num_close;
                else $number_of_end_tags = 0;
            }else break;
        }

        return $text;
    }
    
    
    
    
    
    public function __toString()
    {
        $str = "<b>".$this->title."</b><br>";
        $str .= "<a href='http://en.wikipedia.org/w/index.php?title=". str_replace(" ", "_", $this->title) ."&oldid=". $this->revision ."' target='wiki_page'>go to revision</a><br>";
        //$str .= "<div style='background-color:#DDDDDD;'><pre>".htmlspecialchars($page->xml)."</pre></div>\n";
        $str .= php_active_record\Functions::print_pre($this->taxonomy(), 1);
        $str .= php_active_record\Functions::print_pre($this->taxon_parameters(), 1);
        $str .= php_active_record\Functions::print_pre($this->data_object_parameters(), 1);
        $str .= "<hr>";
        return $str;
    }
}

?>
