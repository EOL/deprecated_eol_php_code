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
            $this->contributor = (string) $this->simple_xml->query->pages->page->revisions->rev['user'];
        }else
        {
            $this->xml = $xml;
            $this->simple_xml = @simplexml_load_string($this->xml);
            $this->text = (string) $this->simple_xml->revision->text;
            $this->title = (string) $this->simple_xml->title;
            $this->contributor = (string) $this->simple_xml->revision->contributor->username;
        }
    }

    public static function from_api($title)
    {
        $api_url = "http://commons.wikimedia.org/w/api.php?action=query&format=xml&prop=revisions&titles=".urlencode($title)."&rvprop=ids|timestamp|user|content&redirects";
        echo $api_url."\n";
        return new WikimediaPage(php_active_record\Functions::get_remote_file($api_url));
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

    public function information()
    {
        if(isset($this->information)) return $this->information;

        $information = array();
        if(preg_match("/(\{\{Information.*?\}\})(.*)/ms", $this->text, $arr))
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
            if(preg_match("/\}\}\s*\n(\s*----\s*\n)?((\*?(genus|species):.*?\n)*)/ims", $this->text, $arr))
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
            if(!php_active_record\Functions::is_utf8($name) || preg_match("/\{/", $name))
            {
                $taxonomy = array();
                break;
            }
        }

        reset($taxonomy);
        $this->taxonomy = $taxonomy;
        return $taxonomy;
    }

    public function taxon_parameters()
    {
        if(isset($this->taxon_parameters)) return $this->taxon_parameters;
        $taxonomy = $this->taxonomy();
        if(!$taxonomy) return array();
        $taxon_rank = key($taxonomy);
        $taxon_name = current($taxonomy);

        $taxon_parameters = array();
        if($taxon_rank!='regnum' && $v = @$taxonomy['regnum']) $taxon_parameters['kingdom'] = $v;
        if($taxon_rank!='phylum' && $v = @$taxonomy['phylum']) $taxon_parameters['phylum'] = $v;
        if($taxon_rank!='classis' && $v = @$taxonomy['classis']) $taxon_parameters['class'] = $v;
        if($taxon_rank!='ordo' && $v = @$taxonomy['ordo']) $taxon_parameters['order'] = $v;
        if($taxon_rank!='familia' && $v = @$taxonomy['familia']) $taxon_parameters['family'] = $v;
        if($taxon_rank!='genus' && $v = @$taxonomy['genus']) $taxon_parameters['genus'] = $v;
        $taxon_parameters['scientificName'] = $taxon_name;
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
        foreach($licenses as $key => $val)
        {
            // PD-USGov-CIA-WF
            if(preg_match("/^(pd|public domain.*|cc-pd|usaid|nih|noaa|CopyrightedFreeUse|Copyrighted Free Use)($| |-)/i", $val))
            {
                $data_object_parameters["license"] = "http://creativecommons.org/licenses/publicdomain/";
                break;
            }
            // cc-zero
            if(preg_match("/^cc-zero/i", $val))
            {
                $data_object_parameters["license"] = "http://creativecommons.org/publicdomain/zero/1.0/";
                break;
            }
            // no known copyright restrictions
            if(preg_match("/^(flickr-)?no known copyright restrictions/i", $val))
            {
                $data_object_parameters["license"] = "http://www.flickr.com/commons/usage/";
                break;
            }
            // cc-by-sa-2.5,2.0,1.0-de
            if(preg_match("/^cc-(by(-nc)?(-nd)?(-sa)?)(.*)$/i", $val, $arr))
            {
                $license = strtolower($arr[1]);
                $rest = $arr[2];

                if(preg_match("/^-?([0-9]\.[0-9])/", $rest, $arr)) $version = $arr[1];
                else $version = "3.0";

                $data_object_parameters["license"] = "http://creativecommons.org/licenses/$license/$version/";
                break;
            }
            // cc-sa-1.0
            if(preg_match("/^(cc-sa)(.*)$/i", $val, $arr))
            {
                $license = "by-sa";
                $rest = $arr[2];

                if(preg_match("/^-?([0-9]\.[0-9])/", $rest, $arr)) $version = $arr[1];
                else $version = "3.0";

                $data_object_parameters["license"] = "http://creativecommons.org/licenses/$license/$version/";
                break;
            }
            // can be relicensed as cc-by-sa-3.0
            if(preg_match("/migration=relicense/i", $val))
            {
                $data_object_parameters["license"] = "http://creativecommons.org/licenses/by-sa/3.0/";
                break;
            }
        }
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

        if(preg_match_all("/(\{\{.*?\}\})/", $this->text, $matches, PREG_SET_ORDER))
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

    public function images()
    {
        $images = array();

        $text = $this->text;
        $lines = explode("\n", $text);
        foreach($lines as $line)
        {
            if(preg_match("/^\s*\[{0,2}\s*(image|file)\s*:(.*?)(\||$)/ims", $line, $arr))
            {
                $images[] = trim($arr[2]);
            }
        }

        return $images;
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
