<?php

class WikimediaPage
{
    public $xml;
    private $simple_xml;

    function __construct($xml)
    {
        if(preg_match("/^<\?xml version=\"1\.0\"\?><api><query>/", $xml))
        {
            $this->xml = $xml;
            $this->simple_xml = @simplexml_load_string($this->xml);
            $this->text = (string) $this->simple_xml->query->pages->page->revisions->rev;
            $this->title = (string) $this->simple_xml->query->pages->page['title'];
            $this->ns = (integer) $this->simple_xml->query->pages->page['ns'];
            $this->contributor = (string) $this->simple_xml->query->pages->page->revisions->rev['user'];
            if (isset($this->simple_xml->query->pages->page['redirect'])) 
            {
                $this->redirect = (string) $this->simple_xml->query->pages->page['redirect']->attributes()->title;
            }
        }else
        {
            $this->xml = $xml;
            $this->simple_xml = @simplexml_load_string($this->xml);
            $this->text = (string) $this->simple_xml->revision->text;
            $this->title = (string) $this->simple_xml->title;
            $this->ns = (integer) $this->simple_xml->ns;
            $this->contributor = (string) $this->simple_xml->revision->contributor->username;
            if (isset($this->simple_xml->redirect)) 
            {
                $this->redirect = (string) $this->simple_xml->redirect->attributes()->title;
            }
        }
    }

    public static function from_api($title)
    {
        $api_url = "http://commons.wikimedia.org/w/api.php?action=query&format=xml&prop=revisions&titles=".urlencode($title)."&rvprop=ids|timestamp|user|content&redirects";
        echo $api_url."\n";
        return new WikimediaPage(php_active_record\Functions::get_remote_file($api_url));
    }

    // see http://commons.wikimedia.org/wiki/Help:Namespaces for relevant numbers
    public static $NS = array('Gallery' => 0, 'Media' => 6, 'Template' => 10, 'Category' => 14);
    public static function fast_is_gallery($xml)  {return (substr($xml, strpos($xml, "<ns>")+4, 2) == '0<');}  //Fast versions.
    public static function fast_is_media($xml)    {return (substr($xml, strpos($xml, "<ns>")+4, 2) == '6<');}  //These don't
    public static function fast_is_template($xml) {return (substr($xml, strpos($xml, "<ns>")+4, 3) == '10<');} //require a
    public static function fast_is_category($xml) {return (substr($xml, strpos($xml, "<ns>")+4, 3) == '14<');} //parsed page
    public static function fast_is_gallery_category_or_template($xml) 
    {
        $test = substr($xml, strpos($xml, "<ns>")+4, 3);
        return ($test == '0</' || $test == '14<' || $test == '10<');
    }

    //these are less dependent on the exact XML string, but require a page to have been parsed, so are slower
    public function is_gallery() {
        return ($this->ns == self::$NS['Gallery']);
    }

    public function is_media() {
        return ($this->ns == self::$NS['Media']);
    }

    public function is_category() {
        return ($this->ns == self::$NS['Category']);
    }

    public function is_template() {
        return ($this->ns == self::$NS['Template']);
    }

    public static function expand_templates($text)
    {
        $url = "http://commons.wikimedia.org/w/api.php?action=expandtemplates&format=xml&text=". urlencode($text);
        $response = \php_active_record\Functions::lookup_with_cache($url, array('validation_regex' => '<text', 'expire_seconds' => 518400));
        $hash = simplexml_load_string($response);
        if(@$hash->expandtemplates) return (string) $hash->expandtemplates[0];
    }

    public function expanded_text()
    {
        if(isset($this->expanded_text)) return $this->expanded_text;
        $url = "http://commons.wikimedia.org/w/api.php?action=parse&format=xml&prop=text&redirects&page=". urlencode($this->title);
        $response = \php_active_record\Functions::lookup_with_cache($url, array('validation_regex' => '<text', 'expire_seconds' => 518400));
        $hash = simplexml_load_string($response);
        if(@$hash->parse->text) $this->expanded_text = (string) $hash->parse->text[0];
        return $this->expanded_text;
    }

    public function active_wikitext()
    {   //the text we should search for when looking for templates, categories, etc.
        if(isset($this->active_wikitext)) return $this->active_wikitext;
        $this->active_wikitext = WikiParser::active_wikitext($this->text);
        return $this->active_wikitext;
    }

    public function information()
    {
        if(isset($this->information)) return $this->information;

        $information = array();
        if(preg_match("/(\{\{Information.*?\}\})(.*)/ms", $this->active_wikitext(), $arr))
        {
            list($information_box, $junk) = WikiParser::balance_tags("{{", "}}", $arr[1], $arr[2], true);

            $parts = preg_split("/(^|\n)\s*\|/", $information_box);
            while($part = array_shift($parts))
            {
                // further split on |Attribute=
                while(preg_match("/^(.+?)\|([A-Z][a-z]+=.*$)/ms", $part, $arr))
                {
                    $part = $arr[1];
                    array_unshift($parts, $arr[2]);
                }
                if(preg_match("/^\s*([^\s]*)\s*=(.*)$/ms", $part, $arr))
                {
                    $attribute = strtolower(trim($arr[1]));
                    $value = trim($arr[2]);
                    $information[$attribute] = $value;
                }
            }
        }

        $this->information = $information;
        return $information;
    }

    public function taxonomy()
    {
        if(isset($this->taxonomy)) return $this->taxonomy;
        $taxonomy = array();
        if(preg_match("/^<div style=\"float:left; margin:2px auto; border-top:1px solid #ccc; border-bottom:1px solid #aaa; font-size:97%\">(.*?)<\/div>\n/ms", $this->expanded_text(), $arr))
        {
            $taxonomy_box = $arr[1];
            $authority = "";
            if(preg_match("/^<div.*?>(.*)/", $taxonomy_box, $arr)) $taxonomy_box = $arr[1];
            if(preg_match("/^'''.*?''':&nbsp;(.*)/", $taxonomy_box, $arr)) $taxonomy_box = $arr[1];
            $parts = preg_split("/<\/b> ?&#160;• ?/", trim($taxonomy_box));
            while($part = array_pop($parts))
            {
                if(preg_match("/^(<b>)?([a-z]+)(<b>)?:&#160;(.*)$/ims", $part, $arr))
                {
                    $attribute = strtolower(trim($arr[2]));
                    $value = strip_tags(WikiParser::strip_syntax(trim($arr[4])));
                    $value = str_replace(" (genus)", "", $value);
                    $taxonomy[$attribute] = strip_tags(WikiParser::strip_syntax($value));
                }
            }

            // there are often some extra ranks under the Taxonnavigation box
            if(preg_match("/\}\}\s*\n(\s*----\s*\n)?((\*?(genus|species):.*?\n)*)/ims", $this->active_wikitext(), $arr))
            {
                $entries = explode("\n", $arr[2]);
                foreach($entries as $entry)
                {
                    if(preg_match("/^\*?(genus|species):(.*)/ims", trim($entry), $arr))
                    {
                        $rank = strtolower($arr[1]);
                        $name = preg_replace("/\s+/", " ", WikiParser::strip_syntax(trim($arr[2])));
                        $taxonomy[$rank] = $name;
                    }
                }
            }
        }
        foreach($taxonomy as &$name)
        {
            $name = str_ireplace("/ type species/", " ", $name);
            $name = preg_replace("/<br ?\/>/", " ", $name);
            $name = trim(preg_replace("/\s+/", " ", WikiParser::strip_syntax(trim($name))));
            $name = ucfirst($name);
        }

        reset($taxonomy);
        $this->taxonomy = $taxonomy;
        return $taxonomy;
    }

    public function taxon_parameters()
    {
        static $wiki_to_EoL = array("regnum"=>"kingdom", "phylum"=>"phylum", "classis"=>"class", "ordo"=>"order", "familia"=>"family", "genus"=>"genus", "species"=>"scientificName");

        if(isset($this->taxon_parameters)) return $this->taxon_parameters;
        $taxonomy = $this->taxonomy();
        if(!$taxonomy) return array();

        foreach ($wiki_to_EoL as $wiki => $EoL) {
            if (!empty($taxonomy[$wiki]))
            {
                $name = $taxonomy[$wiki];
                if (!php_active_record\Functions::is_utf8($name) || preg_match("/\{|\}/u", $name))
                {
                    print "Invalid characters in taxonomy fields ($wiki = $name) for $this->title. Ignoring this level.\n";
                } else {
                    if (($wiki=="species") && !preg_match("/\s+/", $name)) //no space in spp name, could be just the epithet
                    {
                        if (empty($taxonomy['genus'])) 
                        {
                            echo "Single-word species ($name) but no genus in $this->title. Ignoring this part of the classification.\n";
                            continue;
                        } elseif (preg_match("/unidentified|unknown/i", $name)) {
                            echo "Species in $this->title listed as unidentified. Ignoring this part of the classification.\n";
                            continue;
                        } elseif (mb_strtolower($name, "UTF-8") != $name) {
                            echo "Single-word species ($name) has CaPs in $this->title. Ignoring this part of the classification.\n"; 
                            continue;
                        }
                        $name = $taxonomy['genus']." ".$name;
                    }
                    $best = $taxon_parameters[$EoL] = $name;
                }
            }
        }

        if (!empty($best)) $taxon_parameters['scientificName'] = $best;

        //$taxon_parameters["identifier"] = str_replace(" ", "_", $this->title);
        //$taxon_parameters["source"] = "http://commons.wikimedia.org/wiki/".str_replace(" ", "_", $this->title);

        $taxon_parameters['dataObjects'] = array();
        $this->taxon_parameters = $taxon_parameters;
        return $taxon_parameters;
    }

    public function data_object_parameters()
    {
        if(isset($this->data_object_parameters)) return $this->data_object_parameters;

        $data_object_parameters = array();
        $licenses = $this->licenses();
        $data_object_parameters["license"] = self::match_license(implode("\n",$licenses)); //search all at once: return best
        if(!isset($data_object_parameters["license"]))
        {
            echo "DEFAULT LICENSE: $this->title\n";
            if(!$licenses) $data_object_parameters["license"] = "http://creativecommons.org/licenses/by-sa/3.0/";
            else
            {
                echo "LICENSE: $this->title\n";
                print_r($licenses);
                return false;
            }
        }

        $data_object_parameters["identifier"] = str_replace(" ", "_", $this->title);
        # unfortunately we have to alter the identifier to make strings with different cases look different
        # so I'm just adding up the ascii values of the strings and appending that to the identifier
        $data_object_parameters["identifier"] .= "_" . array_sum(array_map('ord', str_split($data_object_parameters["identifier"])));
        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $data_object_parameters["mimeType"] = php_active_record\Functions::get_mimetype($this->title);
        $data_object_parameters["title"] = $this->title;
        $data_object_parameters["source"] = "http://commons.wikimedia.org/wiki/".str_replace(" ", "_", $this->title);
        $data_object_parameters["description"] = $this->description();
        // $data_object_parameters["rights"] = $this->rights();
        $data_object_parameters["language"] = 'en';

        //if($this->description() && preg_match("/([^".UPPER.LOWER."0-9\/,\.;:'\"\(\)\[\]\{\}\|\!\?~@#\$%+_\^&\*<>=\n\r -])/ims", $this->description(), $arr))
        if($data_object_parameters["description"] && !php_active_record\Functions::is_utf8($data_object_parameters["description"]))
        {
            $data_object_parameters["description"] = "";

            //echo "THIS IS BAD:<br>\n";
            //echo $this->description()."<br>\n";
        }

        $data_object_parameters["agents"] = array();
        if($a = $this->agent_parameters())
        {
            if(php_active_record\Functions::is_utf8($a['fullName'])) $data_object_parameters["agents"][] = new SchemaAgent($a);
        }

        return $data_object_parameters;
    }

    public static function match_license($val)
    {
        // PD-USGov-CIA-WF
        if(preg_match("/^(pd|public domain.*|cc-pd|usaid|nih|noaa|CopyrightedFreeUse|Copyrighted Free Use)($| |-)/imu", $val))
        {
            return("http://creativecommons.org/licenses/publicdomain/");
        }
        // cc-zero
        if(preg_match("/^cc-zero/imu", $val))
        {
            $data_object_parameters["license"] = "http://creativecommons.org/publicdomain/zero/1.0/";
            break;
        }
        // no known copyright restrictions
        if(preg_match("/^(flickr-)?no known copyright restrictions/i", $val))
        {
            return("http://www.flickr.com/commons/usage/");
        }
        // simple cc-by-2.5,2.0,1.0-de preferred
        if(preg_match("/^cc-(by)(-\d.*)$/imu", $val, $arr))
        {
           $license = strtolower($arr[1]);
           $rest = $arr[2];

           if(preg_match("/^-?([0-9]\.[0-9])/u", $val, $arr)) $version = $arr[1];
           else $version = "3.0";

           return("http://creativecommons.org/licenses/$license/$version/");
        }
        // cc-by-sa-2.5,2.0,1.0-de, next most preferred
        if(preg_match("/^cc-(by-sa)(-\d.*)$/imu", $val, $arr))
        {
            $license = strtolower($arr[1]);
            $rest = $arr[2];

            if(preg_match("/^-?([0-9]\.[0-9])/u", $rest, $arr)) $version = $arr[1];
            else $version = "3.0";

            return("http://creativecommons.org/licenses/$license/$version/");
        }
        // cc-sa-1.0
        if(preg_match("/^(cc-sa)(.*)$/imu", $val, $arr))
        {
            $license = "by-sa";
            $rest = $arr[2];

            if(preg_match("/^-?([0-9]\.[0-9])/", $rest, $arr)) $version = $arr[1];
            else $version = "3.0";

            return("http://creativecommons.org/licenses/$license/$version/");
        }
        // can be relicensed as cc-by-sa-3.0
        if(preg_match("/migration=relicense/iu", $val))
        {
            return("http://creativecommons.org/licenses/by-sa/3.0/");
        }
        
        // catch all the rest of the cc-licenses, if we've got this far
        if(preg_match("/^cc-(by(-nc)?(-nd)?(-sa)?)(.*)$/imu", $val, $arr))
        {
            $license = strtolower($arr[1]);
            $rest = $arr[2];

            if(preg_match("/^-?([0-9]\.[0-9])/u", $rest, $arr)) $version = $arr[1];
            else $version = "3.0";

            return("http://creativecommons.org/licenses/$license/$version/");
        }
        return(null);
    }

    public function agent_parameters()
    {
        if(isset($this->agent_parameters)) return $this->agent_parameters;
        $author = $this->author();

        $homepage = "";
        if(preg_match("/<a href='(.*?)'>/", $author, $arr)) $homepage = $arr[1];
        if(!preg_match("/\/wiki\/(user|:[a-z]{2})/i", $homepage) || preg_match("/;/", $homepage)) $homepage = "";
        $author = preg_replace("/<a href='(.*?)'>/", "", $author);
        $author = str_replace("</a>", "", $author);
        $author = str_replace("©", "", $author);
        $author = str_replace("\xc2\xA9", "", $author); // should be the same as above
        $author = str_replace("\xA9", "", $author); // should be the same as above

        $agent_parameters = array();
        if($author)
        {
            $agent_parameters["fullName"] = htmlspecialchars($author);
            if(php_active_record\Functions::is_ascii($homepage) && !preg_match("/[\[\]\(\)'\",;\^]/", $homepage)) $agent_parameters["homepage"] = str_replace(" ", "_", $homepage);
            $agent_parameters["role"] = 'photographer';
        }

        $this->agent_parameters = $agent_parameters;
        return $agent_parameters;
    }

    public function licenses()
    {
        if(isset($this->licenses)) return $this->licenses;

        $licenses = array();

        if(preg_match_all("/(\{\{.*?\}\})/", $this->active_wikitext(), $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                // echo "potential license: $match[1]\n";
                while(preg_match("/(\{|\|)(cc-.*?|pd|pd-.*?|gfdl|gfdl-.*?|noaa|usaid|nih|copyrighted free use|CopyrightedFreeUse|creative commons.*?|migration=.*?|flickr-no known copyright.*?|no known copyright.*?)(\}|\|)(.*)/msi", $match[1], $arr))
                {
                    $licenses[] = trim($arr[2]);
                    $match[1] = $arr[3].$arr[4];
                }
            }
        }

        if(!$licenses && preg_match("/permission\s*=\s*(cc-.*?|gpl.*?|public domain.*?|creative commons .*?)(\}|\|)/msi", $this->text, $arr))
        {
            $licenses[] = trim($arr[1]);
        }

        $this->licenses = $licenses;
        return $licenses;
    }

    public function author()
    {
        if(isset($this->author)) return $this->author;

        $author = "";

        if($info = $this->information())
        {
            foreach($info as $attr => $val)
            {
                if($attr == "author") $author = self::convert_diacritics(WikiParser::strip_syntax($val, true));
            }
        }

        /* no longer considering the last editor to be the author. This was causing various bots to be deemed author */
        // if((!$author || !Functions::is_utf8($author)) && $this->contributor && Functions::is_utf8($this->contributor))
        // {
        //     $this->contributor = self::convert_diacritics($this->contributor);
        //     $author = "<a href='".WIKI_USER_PREFIX."$this->contributor'>$this->contributor</a>";
        // }

        $this->author = $author;
        return $author;
    }

    public function rights()
    {
        if(isset($this->rights)) return $this->rights;
        $rights = "";
        if($info = $this->information())
        {
            foreach($info as $attr => $val)
            {
                if($attr == "permission") $rights = self::convert_diacritics(WikiParser::strip_syntax($val, true));
            }
        }
        $this->rights = $rights;
        return $rights;
    }

    public function description()
    {
        if(isset($this->description)) return $this->description;

        $authors = array();

        $description = "";
        if($info = $this->information())
        {
            foreach($info as $attr => $val)
            {
                if($attr == "description")
                {
                    $description = WikiParser::strip_syntax($val, true);
                }
            }
        }

        $this->description = $description;
        return $description;
    }

    public function media_on_page()
    {
        $media = array();

        $text = $this->active_wikitext();
        $lines = explode("\n", $text);
        foreach($lines as $line)
        {
            # < > [ ] | { } not allowed in titles, so if we see this, it is end of filename (spots e.g. Image:xxx.jpg</gallery>)
            # see http://en.wikipedia.org/wiki/Wikipedia:Naming_conventions_(technical_restrictions)#Forbidden_characters
            if(preg_match("/^\s*\[{0,2}\s*(Image|File)\s*:\s*(\S)(.*?)\s*([|#<>{}[\]]|$)/iums", $line, $arr))
            {
                $first_letter = $arr[2];
                $rest = $arr[3];
                //In <title>, all pages have a capital first letter, and single spaces replace any combo of spaces + underscores
                //Can't use ucfirst() as this string may be unicode.
                $media[] = mb_strtoupper($first_letter,'utf-8').preg_replace("/[_ ]+/u", " ", $rest); 
            }
        }

        return $media;
    }

    public static function convert_diacritics($string)
    {
        $string = str_replace('ä', '&amp;auml;', $string);
        $string = str_replace('å', '&amp;aring;', $string);
        $string = str_replace('é', '&amp;eacute;', $string);
        $string = str_replace('ï', '&amp;iuml;', $string);
        $string = str_replace('ö', '&amp;ouml;', $string);
        return $string;
    }
}






?>
