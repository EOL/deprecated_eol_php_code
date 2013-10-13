<?php

class WikimediaPage
{
    public  $xml;
    private $simple_xml;
    private $data_object_parameters;
    private $galleries = array();

    // see http://www.mediawiki.org/wiki/Manual:MIME_type_detection
    private static $mediatypes = array(
                                    'BITMAP'  => 'http://purl.org/dc/dcmitype/StillImage',
                                    'DRAWING' => 'http://purl.org/dc/dcmitype/StillImage',
                                    'AUDIO'   => 'http://purl.org/dc/dcmitype/Sound',
                                    'VIDEO'   => 'http://purl.org/dc/dcmitype/MovingImage',
                                    // 'MULTIMEDIA' => '',
                                    'TEXT'    => 'http://purl.org/dc/dcmitype/Text');
    // see http://commons.wikimedia.org/wiki/Help:Namespaces for relevant numbers
    public static $NS = array('Gallery' => 0, 'Media' => 6, 'Template' => 10, 'Category' => 14);

    function __construct($xml)
    {
        $this->xml = $xml;
        $this->simple_xml = @simplexml_load_string($this->xml);
        // Oddly, wikimedia dumps occasionally have random newlines within tags.
        if(!is_object($this->simple_xml))
        {
            // Hence simplexml_load_string() sometimes fails (this is rare, though).
            echo "ERROR, malformed xml";
            if(preg_match("/<title>([^<]+)/u", $this->xml, $arr)) echo " for page titled <".$arr[1].">\n";
            else echo ":\n".$this->xml."\n";
            $this->text = $this->title = $this->ns = $this->contributor = $this->timestamp = "";
        }else
        {
            if(preg_match("/^<\?xml version=\"1\.0\"\?><api><query>/", $xml))
            {
                $this->text = (string) $this->simple_xml->query->pages->page->revisions->rev;
                $this->title = (string) $this->simple_xml->query->pages->page['title'];
                $this->ns = (integer) $this->simple_xml->query->pages->page['ns'];
                $this->contributor = (string) $this->simple_xml->query->pages->page->revisions->rev['user'];
                $this->timestamp = (string) $this->simple_xml->query->pages->page->revisions->rev['timestamp'];
                if(isset($this->simple_xml->query->pages->page['redirect']))
                {
                    $this->redirect = (string) $this->simple_xml->query->pages->page['redirect']->attributes()->title;
                }
            }else
            {
                $this->text = (string) $this->simple_xml->revision->text;
                $this->title = (string) $this->simple_xml->title;
                $this->ns = (integer) $this->simple_xml->ns;
                $this->contributor = (string) $this->simple_xml->revision->contributor->username;
                $this->timestamp = (string) $this->simple_xml->revision->timestamp;
                if(isset($this->simple_xml->redirect))
                {
                    $this->redirect = (string) $this->simple_xml->redirect->attributes()->title;
                }
            }
        }
    }

    public static function from_api($title)
    {
        $api_url = "http://commons.wikimedia.org/w/api.php?action=query&format=xml&prop=revisions&titles=". urlencode($title) ."&rvprop=ids|timestamp|user|content&redirects";
        echo $api_url."\n";
        return new WikimediaPage(php_active_record\Functions::get_remote_file($api_url));
    }

    // Fast versions - these do not require a parsed page
    public static function fast_is_gallery($xml)
    {
        return (substr($xml, strpos($xml, "<ns>")+4, 2) == '0<');
    }
    public static function fast_is_media($xml)
    {
        return (substr($xml, strpos($xml, "<ns>")+4, 2) == '6<');
    }
    public static function fast_is_template($xml)
    {
        return (substr($xml, strpos($xml, "<ns>")+4, 3) == '10<');
    }
    public static function fast_is_category($xml)
    {
        return (substr($xml, strpos($xml, "<ns>")+4, 3) == '14<');
    }
    public static function fast_is_gallery_category_or_template($xml)
    {
        $test = substr($xml, strpos($xml, "<ns>")+4, 3);
        return ($test == '0</' || $test == '14<' || $test == '10<');
    }

    public static function fast_is_gallery_category_or_media($xml)
    {
        $test = substr($xml, strpos($xml, "<ns>")+4, 3);
        return ($test == '6</' || $test == '14<' || $test == '0</');
    }

    // these are less dependent on the exact XML string, but require a page to have been parsed, so are slower
    public function is_gallery()
    {
        return ($this->ns == self::$NS['Gallery']);
    }
    public function is_media()
    {
        return ($this->ns == self::$NS['Media']);
    }
    public function is_category()
    {
        return ($this->ns == self::$NS['Category']);
    }
    public function is_template()
    {
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
    {
        // the text we should search for when looking for templates, categories, etc.
        if(isset($this->active_wikitext)) return $this->active_wikitext;
        $this->active_wikitext = WikiParser::active_wikitext($this->text);
        return $this->active_wikitext;
    }

    public function information()
    {
        if(isset($this->information)) return $this->information;
        foreach(array("[Ii]nformation", "[Ss]pecimen") as $template_name)
        {
            $this->information = WikiParser::template_as_array($this->active_wikitext(), $template_name);
            if(!empty($this->information)) break;
        }
        // remove the template name
        array_shift($this->information);
        return $this->information;
    }

    private function fill_includes_recursively(&$arr, $include_arrays, &$visited=array())
    {
        if(!empty($arr['taxo']['Include']))
        {
            $include = "Template:".(WikiParser::make_valid_pagetitle($arr['taxo']['Include']));
            if(!isset($visited[$include])) // stops infinite recursive loops
            {
                $visited[$include] = true;
                if(isset($include_arrays[$include]))
                {
                    $this->fill_includes_recursively($include_arrays[$include], $include_arrays, $visited);
                    $arr['taxo'] = array_merge($include_arrays[$include]['taxo'], $arr['taxo']);
                    $arr['last_mod'] = max($include_arrays[$include]['last_mod'], $arr['last_mod']);
                }else
                {
                    echo "Found non-existent Taxonav include value: '$include' within ".implode(":",$arr['taxo'])." in page $this->title\n";
                    flush();
                }
                unset($arr['taxo']['Include']);
            }
        }
        return $arr;
    }

    public function taxonomy_via_API()
    {
        // can't use actual parsed text to detect last changed time
        $taxonomy = new TaxonomyParameters($this->timestamp, null);
        $mesg="";
        if(preg_match("/^<div style=\"float:left; margin:2px auto; border-top:1px solid #ccc; border-bottom:1px solid #aaa; font-size:97%\">(.*?)<\/div>\n/ms", $this->expanded_text(), $arr))
        {
            $taxonomy_box = $arr[1];
            $authority = "";
            if(preg_match("/^<div.*?>(.*)/", $taxonomy_box, $arr)) $taxonomy_box = $arr[1];
            if(preg_match("/^'''.*?''':&nbsp;(.*)/", $taxonomy_box, $arr)) $taxonomy_box = $arr[1];
            $parts = preg_split("/<\/b> ?&#160;• ?/", WikiParser::mb_trim($taxonomy_box));
            while($part = array_pop($parts))
            {
                if(preg_match("/^(<b>)?([a-z]+)(<b>)?:&#160;(.*)$/ims", $part, $arr))
                {
                    $taxonomy->add_wiki_info($arr[2], $arr[4]);
                }
            }
        }

        $mesg .= $this->fill_taxon_info_from_outside_Taxonavigation($taxonomy);

        if(!empty($mesg)) echo "<http://commons.wikimedia.org/wiki/".$this->title."> ".$mesg."\n";
        return $taxonomy;
    }

    private function taxonomy_via_wikitext(&$taxonav_include_arrays)
    {
        $Taxonav = array('taxo'=>$this->taxonav_as_array("[Tt]axonavigation"), 'last_mod'=>strtotime($this->timestamp));
        $Taxonav = $this->fill_includes_recursively($Taxonav, $taxonav_include_arrays);
        $taxonomy = new TaxonomyParameters($this->timestamp, $Taxonav['last_mod']);
        $mesg = "";
        foreach($Taxonav['taxo'] as $wiki_rank => $name)
        {
            $mesg .= $taxonomy->add_wiki_info($wiki_rank, $name);
        }
        $mesg .= $this->fill_taxon_info_from_outside_Taxonavigation($taxonomy);
        if(!empty($mesg)) echo "<".$this->title."> ".$mesg."\n";
        return $taxonomy;
    }

    private function fill_taxon_info_from_outside_Taxonavigation(&$taxonomy)
    {
        $mesg = "";
        // there are often some extra ranks under the Taxonnavigation box -
        if(preg_match("/\}\}\s*\n(\s*----\s*\n)?((\*?(genus|species):.*?\n)*)/muis", $this->active_wikitext(), $arr))
        {
            $entries = explode("\n", $arr[2]);
            // make sure species is checked last (to use linked authority)
            foreach(array('genus','species') as $rank)
            {
                foreach($entries as $entry)
                {
                    if(is_null($taxonomy->get($rank)) && preg_match("/^\*?$rank:(.*)/muis", WikiParser::mb_trim($entry), $arr))
                    {
                        // usually the name is in italics, followed by the authority
                        // e.g. http://commons.wikimedia.org/wiki/Shorea_roxburghii
                        // mark the difference by placing a newline between the two
                        $fullname = preg_replace("/<\/i>/", "$0\n", WikiParser::strip_syntax($arr[1], true), 1);
                        $fullname = explode("\n", $fullname);
                        $mesg .= $taxonomy->add_info($rank,  strip_tags($fullname[0]));
                        if(is_null($taxonomy->get($rank)))
                        {
                            $mesg .= "Info gleaned from outside Taxonavigation box: $rank set to '".$taxonomy->get($rank)."'";
                            if(isset($fullname[1]))
                            {
                                // if we end up using this species or genus data, we should use associated authority
                                if(empty($taxonomy->authority)) $mesg .= " & authority set to"; else $mesg .= " & authority replaced by";
                                $taxonomy->add_info('authority', strip_tags($fullname[1]));
                                $mesg .=" '".$taxonomy->authority."'";
                            }
                            $mesg .= ". ";
                        }
                    }
                }
            }
        }
        return $mesg;
    }

    public function taxonomy(&$taxonav_include_arrays = null)
    {
        if(!isset($this->taxonomy))
        {
            if(empty($taxonav_include_arrays)) $this->taxonomy = $this->taxonomy_via_API();
            else $this->taxonomy = $this->taxonomy_via_wikitext($taxonav_include_arrays);
        }
        return $this->taxonomy;
    }

    private static function license_types()
    {
        // The licenses should be listed in order of preference
        static $license_types = array(
            'public domain',
            'http://creativecommons.org/publicdomain/zero/',
            'http://www.flickr.com/commons/usage/',
            'http://creativecommons.org/licenses/by/',
            'http://creativecommons.org/licenses/by-nc/',
            'http://creativecommons.org/licenses/by-sa/',
            'http://creativecommons.org/licenses/by-nc-sa/');
        return $license_types;
    }

    private static function sort_identified_licenses($a, $b)
    {
        $license_types = self::license_types();
        $a_index = array_search($a['category'], $license_types);
        $b_index = array_search($b['category'], $license_types);
        if($a_index == $b_index)
        {
            if($a['version'] == $b['version']) return 0;
            // larger version first
            return (floatval($a['version']) > floatval($b['version'])) ? -1 : 1;
        }
        // smaller index first
        return ($a_index < $b_index) ? -1 : 1;
    }

    public function best_license($potential_licenses, $is_wikitext=true)
    {
        // can be used to identify either licenses in templates {{cc-by-1.0}} or categories [[CC-BY-1.0]]
        // usually these are of the same format, apart from e.g. {{XXX-no known copyright restrictions}}
        // Currently we miss files categorised under the FAL (http://en.wikipedia.org/wiki/Free_Art_License)
        // These could probably be included somehow
        $identified_licenses = array();
        foreach($potential_licenses as $potential_license)
        {
            // PD-USGov-CIA-WF
            if(preg_match("/^(PD|Public domain.*|CC-PD|usaid|nih|noaa|CopyrightedFreeUse|Copyrighted Free Use)($| |-)/mui", $potential_license))
            {
                $identified_licenses[] = array(
                    'license'   => 'public domain',
                    'category'  => 'public domain',
                    'version'   => null);
            }
            // cc-zero
            if(preg_match("/^CC-Zero/mui", $potential_license))
            {
                $identified_licenses[] = array(
                    'license'   => 'http://creativecommons.org/publicdomain/zero/1.0/',
                    'category'  => 'http://creativecommons.org/publicdomain/zero/',
                    'version'   => '1.0');
            }
            // no known copyright restrictions
            // (listed under http://commons.wikimedia.org/wiki/Category:No_known_restrictions_license_tags)
            if((($is_wikitext) && preg_match("/^(flickr-)?no known copyright restrictions/mui", $potential_license)) ||
               ((!$is_wikitext) && preg_match("/^Files from Flickr's 'The Commons'/mui", $potential_license)))
            {
                $identified_licenses[] = array(
                    'license'   => 'http://www.flickr.com/commons/usage/',
                    'category'  => 'http://www.flickr.com/commons/usage/',
                    'version'   => null);
            }
            // {{gfdl|migration=relicense}} can be relicensed as cc-by-sa-3.0 (when categories, CC-BY-SA-3.0 is automatically set)
            if($is_wikitext && preg_match("/migration=relicense/mui", $potential_license))
            {
                $identified_licenses[] = array(
                    'license'   => 'http://creativecommons.org/licenses/by-sa/3.0/',
                    'category'  => 'http://creativecommons.org/licenses/by-sa/',
                    'version'   => '3.0');
            }
            // cc-sa-1.0
            if(preg_match("/^(CC-SA)(.*)$/mui", $potential_license, $arr))
            {
                $license = "by-sa";
                $rest = $arr[2];
                if(preg_match("/^-?([0-9]\.[0-9])/u", $rest, $arr)) $version = $arr[1];
                else $version = "3.0";
                $identified_licenses[] = array(
                    'license'   => "http://creativecommons.org/licenses/$license/$version/",
                    'category'  => "http://creativecommons.org/licenses/$license/",
                    'version'   => $version);
            }
            // catch all the cc-licenses
            if(preg_match("/^CC-(BY(-NC)?(-ND)?(-SA)?)(.*)$/mui", $potential_license, $arr))
            {
                $license = strtolower($arr[1]);
                $rest = $arr[2];
                if(preg_match("/^-?([0-9]\.[0-9])/u", $rest, $arr)) $version = $arr[1];
                else $version = "3.0";
                if($license == 'by-nc-nd') continue;
                $identified_licenses[] = array(
                    'license'   => "http://creativecommons.org/licenses/$license/$version/",
                    'category'  => "http://creativecommons.org/licenses/$license/",
                    'version'   => $version);
            }
        }
        if(!$identified_licenses) return null;
        usort($identified_licenses, array('\WikimediaPage', 'sort_identified_licenses'));
        return $identified_licenses[0]['license'];
    }

    public function get_data_object_parameters()
    {
        return $this->data_object_parameters;
    }

    public function initialize_data_object()
    {
        $this->data_object_parameters["title"] = $this->title;
        $this->data_object_parameters["identifier"] = str_replace(" ", "_", $this->title);
        // unfortunately we have to alter the identifier to make strings with different cases look different
        // so I'm just adding up the ascii values of the strings and appending that to the identifier
        $this->data_object_parameters["identifier"] .= "_" . array_sum(array_map('ord', str_split($this->data_object_parameters["identifier"])));
        $this->data_object_parameters["source"] = "http://commons.wikimedia.org/wiki/".str_replace(" ", "_", $this->title);
        $this->data_object_parameters["language"] = 'en';
        if($this->description() && !php_active_record\Functions::is_utf8($this->description()))
        {
            $this->data_object_parameters["description"] = "";
        }else $this->data_object_parameters["description"] = $this->description();

        $this->data_object_parameters["agents"] = array();
        if($a = $this->agent_parameters())
        {
            if(php_active_record\Functions::is_utf8($a['fullName'])) $this->data_object_parameters["agents"][] = new SchemaAgent($a);
        }

        if($this->point())
        {
            $this->data_object_parameters["point"] = new \SchemaPoint($this->point());
        }

        if($this->location())
        {
            $this->data_object_parameters["location"] = $this->location();
        }

        // the following properties may be overridden later by category data from the API.
        $this->licenses = $this->licenses_via_wikitext();
        $this->set_license($this->best_license($this->licenses));
        $this->set_mimeType(php_active_record\Functions::get_mimetype($this->title));
    }

    public function has_license()
    {
        if(empty($this->data_object_parameters['license'])) return false;
        else return true;
    }

    public function has_valid_mime_type()
    {
        static $valid_mime_types = array('image/png', 'image/jpeg', 'image/gif', 'application/ogg', 'image/svg+xml', 'image/tiff', 'image/x-xcf');
        if(empty($this->data_object_parameters['mimeType'])) return false;
        if(!in_array($this->data_object_parameters['mimeType'], $valid_mime_types)) return false;
        return true;
    }

    public function reassess_licenses_with_additions($potential_license_categories)
    {
        if(!$potential_license_categories) return;
        $this->licenses = array_merge($this->licenses, $potential_license_categories);
        if($license = $this->best_license($this->licenses, false))
        {
            $this->set_license($license);
        }
    }

    public function set_license($license)
    {
        if(isset($this->data_object_parameters['license']) && ($this->data_object_parameters['license'] != $license))
        {
            echo "Overriding license for $this->title : current = ". $this->data_object_parameters['license'] .", new = $license\n";
        }
        $this->data_object_parameters['license'] = $license;
    }

    public function set_mediaURL($mediaURL)
    {
        if(isset($this->data_object_parameters['mediaURL']) && ($this->data_object_parameters['mediaURL'] != $mediaURL))
        {
            echo "Overriding mediaURL for $this->title : current = ". $this->data_object_parameters['mediaURL'] .", new = $mediaURL\n";
        }
        $this->data_object_parameters['mediaURL'] = $mediaURL;
    }


    public function set_mimeType($mimeType)
    {
        if(isset($this->data_object_parameters['mimeType']) && ($this->data_object_parameters['mimeType'] != $mimeType))
        {
            echo "Overriding mimeType for $this->title : current = ". $this->data_object_parameters['mimeType'] .", new = $mimeType\n";
        }
        $this->data_object_parameters['mimeType'] = $mimeType;
    }

    public function set_mediatype($mediatype)
    {
        if(isset(self::$mediatypes[$mediatype]))
        {
            $dataType = self::$mediatypes[$mediatype];
            if(isset($this->data_object_parameters['dataType']) && ($this->data_object_parameters['dataType'] != $dataType))
            {
                echo "Overriding dataType for $this->title : current = ". $this->data_object_parameters['dataType'] .", new = $dataType\n";
            }
            $this->data_object_parameters['dataType'] = $dataType;
        }else
        {
            echo "Non-compatible mediatype: $mediatype for $this->title\n";
            $this->data_object_parameters['dataType'] = "";
        }
    }

    public function set_additionalInformation($text)
    {
        if(isset($this->data_object_parameters['additionalInformation']))
        {
            $this->data_object_parameters['additionalInformation'] .= $text;
        }else $this->data_object_parameters['additionalInformation'] = $text;
    }

    public function get_categories($added_categories_only = false)
    {
        if(!property_exists($this,'categories_from_wikitext'))
        {
            $this->categories_from_wikitext = array();
            if(preg_match_all('/\[\[\s*[Cc]ategory:\s*(.*?)\s*(?:\]\]|\|)/uS', $this->active_wikitext(), $matches))
            {
                $this->categories_from_wikitext = $matches[1];
            }
        }
        if(!property_exists($this,'added_categories')) $this->added_categories = array();
        if($added_categories_only) return($this->added_categories);
        else return($this->categories_from_wikitext + $this->added_categories);
    }

    public function add_extra_category($category)
    {
        $this->added_categories[] = $category;
    }

    public function add_galleries($array_of_galleries)
    {
        $this->galleries = array_merge($this->galleries, $array_of_galleries);
    }

    public function get_galleries()
    {
        // NB not all media pages will be associated with a gallery
        if(isset($this->galleries)) return $this->galleries;
        else return null;
    }

    public function agent_parameters()
    {
        if(isset($this->agent_parameters)) return $this->agent_parameters;
        $author = $this->author();

        $homepage = "";
        if(preg_match("/<a href='(.*?)'>/u", $author, $arr)) $homepage = $arr[1];
        if(!preg_match("/\/wiki\/(user|:[a-z]{2})/ui", $homepage) || preg_match("/;/u", $homepage)) $homepage = "";
        $author = preg_replace("/<a href='(.*?)'>/u", "", $author);
        $author = str_replace("</a>", "", $author);
        $author = str_replace("©", "", $author);
        $author = str_replace("\xc2\xA9", "", $author); // should be the same as above
        $author = str_replace("\xA9", "", $author); // should be the same as above

        $agent_parameters = array();
        if($author)
        {
            $agent_parameters["fullName"] = htmlspecialchars($author);
            if(php_active_record\Functions::is_ascii($homepage) && !preg_match("/[\[\]\(\)'\",;\^]/u", $homepage)) $agent_parameters["homepage"] = str_replace(" ", "_", $homepage);
            $agent_parameters["role"] = 'photographer';
        }

        $this->agent_parameters = $agent_parameters;
        return $agent_parameters;
    }

    // this just looks through the plain wikitext for things like {{GFDL}}
    public function licenses_via_wikitext()
    {
        if(isset($this->licenses_via_wikitext)) return $this->licenses_via_wikitext;
        $licenses = array();
        if(preg_match_all("/(\{\{.*?\}\})/u", $this->active_wikitext(), $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                while(preg_match("/(\{|\|)(cc-.*?|pd|pd-.*?|gfdl|gfdl-.*?|noaa|usaid|nih|copyrighted free use|CopyrightedFreeUse|creative commons.*?|migration=.*?|flickr-no known copyright.*?|no known copyright.*?)(\}|\|)(.*)/umsi", $match[1], $arr))
                {
                    $licenses[] = WikiParser::mb_trim($arr[2]);
                    $match[1] = $arr[3].$arr[4];
                }
            }
        }
        if(!$licenses && preg_match("/permission\s*=\s*(cc-.*?|gpl.*?|public domain.*?|creative commons .*?)(\}|\|)/umsi", $this->text, $arr))
        {
            $licenses[] = WikiParser::mb_trim($arr[1]);
        }
        $this->licenses_via_wikitext = $licenses;
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
                if($attr == "author" || $attr == "Author") $author = self::convert_diacritics(WikiParser::strip_syntax($val, true));
            }
        }

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
                if($attr == "permission" || $attr == "Permission") $rights = self::convert_diacritics(WikiParser::strip_syntax($val, true));
            }
        }
        $this->rights = $rights;
        return $rights;
    }

    public function description()
    {
        if(isset($this->description)) return $this->description;

        $description = "";
        if($info = $this->information())
        {
            foreach($info as $attr => $val)
            {
                if($attr == "description" || $attr == "Description")
                {
                    $description = WikiParser::strip_syntax($val, true);
                }
            }
        }

        $this->description = $description;
        return $description;
    }

    public function point()
    {
        if(isset($this->georef)) return $this->georef;

        $this->georef = false;
        $this->location = false;
        if(count($location = WikiParser::template_as_array($this->active_wikitext(), "(?:[Oo]bject )?[Ll]ocation(?: dec)?")))
        {
            if(substr($location[0], -3) == "dec")
            {
                // see http://commons.wikimedia.org/wiki/Template:Location_dec
                if(isset($location[1]) && isset($location[2]))
                {
                    $this->georef["latitude"] = $location[1];
                    $this->georef["longitude"] = $location[2];
                }
                if(isset($location[3]))
                {
                    $this->location = $location[3];
                }
            }else
            {
                // see http://commons.wikimedia.org/wiki/Template:Location
                // lazy - assume 1-7 are also set if so
                if(isset($location[8]))
                {
                    $this->georef["latitude"] = $location[1] + ((($location[2] * 60) + ($location[3])) / 3600);
                    if(stripos($location[4], "N") === false) $this->georef["latitude"] *= -1;
                    $this->georef["longitude"] = $location[5] + ((($location[6] * 60) + ($location[7])) / 3600);
                    if(stripos($location[8], "W") === false) $this->georef["longitude"] *= -1;
                }
                if(isset($location[9]))
                {
                    $this->location = $location[9];
                }
            }
         }

        return $this->georef;
    }

    public function location()
    {
        // Very crude: just bungs the 9th parameter giving GeoHack server metadata into "location": coded region, heading, scale, etc.
        if(isset($this->location)) return $this->location;
        $this->point();
        return $this->location;
    }

    public function media_on_page()
    {
        $media = array();
        $text = $this->active_wikitext();
        $lines = explode("\n", $text);
        foreach($lines as $line)
        {
            /* < > [ ] | { } not allowed in titles, so if we see this, it is end of filename (spots e.g. Image:xxx.jpg</gallery>)
             see http://en.wikipedia.org/wiki/Wikipedia:Naming_conventions_(technical_restrictions)#Forbidden_characters
             This is a horribly long regexp because "spaces" in the string may be "unicode spaces" or control characters
             - matched by [\pZ\pC]. */
            if(preg_match("/^[\pZ\pC]*\[{0,2}[\pZ\pC]*(Image|File)[\pZ\pC]*:(.*?)([|#<>{}[\]]|$)/iums", $line, $arr))
            {
                $media[] = WikiParser::make_valid_pagetitle(urldecode($arr[2]));
            }
        }
        return $media;
    }

    public function contains_template($template)
    {
        return preg_match("/\{\{".$template."\s*[\|\}]/u", $this->active_wikitext());
    }

    public function taxonav_as_array($template_name, $strip_syntax = true)
    {
        // A special format for Taxonavigations, where e.g. param[1] is "Cladus" and param[2] is "magnoliids"
        // Place param[1] as the key, and param[2] as the value of the returned array, so that e.g.
        // Taxonavigation|Cladus|magnoliids|Ordo|Laurales becomes [0=>Taxonavigation, Cladus=>magnoliids, Ordo=>Laurales]
        // Take care when using, as $template_name is allowed to be a RegExp.
        $plain_array = WikiParser::template_as_array($this->active_wikitext(), $template_name);
        $tnav_array  = array();

        foreach($plain_array as $param => $value)
        {
            $value = WikiParser::mb_trim($value);
            // numerical array elements get reassigned.
            if(is_int($param))
            {
                // $param = 1,3,5
                if($param % 2)
                {
                    if($value != "")
                    {
                        if(isset($plain_array[$param+1]))
                        {
                            if($strip_syntax)
                            {
                                $tnav_array[WikiParser::mb_ucfirst($value)] = WikiParser::strip_syntax($plain_array[$param+1]);
                            }else
                            {
                                $tnav_array[WikiParser::mb_ucfirst($value)] = $plain_array[$param+1];
                            }
                        }else
                        {
                           echo "Note: there don't seem to be the right number of parameters in $template_name within http://commons.wikimedia.org/wiki/$this->title\n";
                        }
                    }
                }
            }else
            {
                $tnav_array[WikiParser::mb_ucfirst($param)] = $value;
            }
        }
        return $tnav_array;
    }

    // see see http://commons.wikimedia.org/w/api.php
    public static $max_titles_per_lookup = 50;
    // see see http://commons.wikimedia.org/w/api.php
    public static $max_categories_per_lookup = 500;

    public static function call_API(&$url, $titles)
    {
        if(!$titles) return array();
        // return an array with $title => json_result
        $real_titles = array_combine($titles, $titles);
        $results = array();
        // be polite to Commons, see http://meta.wikimedia.org/wiki/User-Agent_policy
        // *** Should be something like 'EoLHarvestingCode/1.0 (https://github.com/EOL; XXX@eol.org) ';
        static $user_agent = false;

        if(count($titles) > self::$max_titles_per_lookup)
        {
            echo "ERROR: only allowed a maximum of ". self::$max_titles_per_lookup ." titles in a single API query.\n";
            return;
        }elseif(count($titles) == 0) return;

        $url .= "&titles=". urlencode(implode("|", $titles));
        $continue = "&continue=";
        while(!empty($continue))
        {
            $result = php_active_record\Functions::lookup_with_cache($url.$continue, array(
                'download_wait_time' => 5000000,
                'download_attempts' => 3,
                'user_agent' => 'gzip,deflate',
                'validation_regex' => 'query',
                'expire_seconds' => 518400));

            // return as an associative array
            $json = json_decode($result, true);
            $query = $json['query'];
            if(isset($query['normalized']))
            {
                foreach($query['normalized'] as $norm)
                {
                    $from = (string) $norm['from'];
                    $to = (string) $norm['to'];
                    echo "Possible error: page title '$from' should really be '$to'. This may cause problems later.\n";
                    $real_titles[$to] = $from;
                }
            }
            if(isset($query['redirects']))
            {
                // All redirected pages in galleries should have been caught in the XML dump.
                // Here we should only have pages which have changed since the dump was produced.
                // Note that we do not yet catch pages that refer to redirected categories.
                foreach($query['redirects'] as $redir)
                {
                    $from = (string) $redir['from'];
                    $to = (string) $redir['to'];
                    echo "Page $from has been redirected to $to, but doesn't seem to be redirected in the XML dump";
                    echo "(has it changed since the dump?). Using Wikitext from old version, but url, categories, etc. from new.\n";
                    $real_titles[$to] = $from;
                }
            }

            if(!isset($query['pages']))
            {
                echo "\nERROR: couldn't get JSON API query from $url\n";
            } else
            {
                foreach($query['pages'] as $page)
                {
                    if(empty($page['title']))
                    {
                        if(isset($page['pageid']))
                        {
                            echo "ERROR: empty title when querying API - pageId =".((string) $page['pageid'])."($url)\n";
                        }else
                        {
                           /* odd "feature" of mediawiki API: when you get a redirect, not only does it return info on the
                           new page to which the redirect points, but also an empty page ( JSON = {"imagerepository":""} )
                           corresponding to the original, old page. We can safely ignore these empty results */
                        }
                        continue;
                    }

                    $title = (string) $page['title'];

                    if(!isset($real_titles[$title]))
                    {
                        echo "ERROR: couldn't find <$title> when querying API ($url)\n";
                        continue;
                    }

                    // see http://www.mediawiki.org/wiki/API:Query#Missing_and_invalid_titles
                    if(array_key_exists("missing",$page))
                    {
                        echo "The file <$title> is missing from Commons. Perhaps it has been deleted? Leaving it out.\n";
                        continue;
                    }

                    // see http://www.mediawiki.org/wiki/API:Query#Missing_and_invalid_titles
                    if(array_key_exists("invalid",$page))
                    {
                        echo "The name <$title> is invalid. Leaving it out.\n";
                        continue;
                    }

                    // we've already done one pass
                    if(isset($results[$real_titles[$title]]))
                    {
                        $results[$real_titles[$title]] = array_merge_recursive($results[$real_titles[$title]], $page);
                    }else $results[$real_titles[$title]] = $page;
                }
            }
            if(isset($json['continue']))
            {
                $continue = "&". http_build_query($json['continue'], "", "&");
            }else $continue = "";
        }
        return $results;
    }

    public static function process_pages_using_API(&$array_of_pages)
    {
        // Work on an array of pages, querying the Mediawiki API about them.
        // If the page is missing or invalid (e.g. has been deleted), then remove it from the array.
        // see http://commons.wikimedia.org/w/api.php
        $url = 'http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo%7Ccategories&iiprop=url%7Cmime%7Cmediatype&clprop=hidden&cllimit='. self::$max_categories_per_lookup .'&redirects';

        $titles = array_map(function($page) { return $page->title; }, $array_of_pages);
        $json_array = self::call_API($url, $titles);
        foreach($array_of_pages as $index => &$page)
        {
            if(!isset($json_array[$page->title]))
            {
                unset($array_of_pages[$index]);
            }else
            {
                $json_info = $json_array[$page->title];
                $page->initialize_data_object_from_api_response($json_info);
                $page->initialize_categories_from_api_response($json_info);
            }
        }
    }

    private function initialize_data_object_from_api_response($json_info)
    {
        $this->initialize_data_object();
        // set URL, mimetype, mediatype
        if(isset($json_info['imageinfo']) && isset($json_info['imageinfo'][0]))
        {
            // URL
            if(isset($json_info['imageinfo'][0]['url']))
            {
                $this->set_mediaURL($json_info['imageinfo'][0]['url']);
            }else
            {
                $this->set_mediaURL("");
                echo "That's odd. No URL returned in API query for $this->title (in $url)\n";
            }
            // mime
            if(isset($json_info['imageinfo'][0]['mime']))
            {
                // TOimage/svg+xml
                // application/ogg
                $this->set_mimeType($json_info['imageinfo'][0]['mime']);
            }else
            {
                echo "That's odd. No mimeType returned in API query for $this->title (in $url)\n";
            }
            // mediatype
            if(isset($json_info['imageinfo'][0]['mediatype']))
            {
                $this->set_mediatype($json_info['imageinfo'][0]['mediatype']);
            }else
            {
                echo "That's odd. No mediatype returned in API query for $this->title (in $url)\n";
            }
        }
    }

    private function initialize_categories_from_api_response($json_info)
    {
        // fill in categories - this will allow us to check taxonomy, license, & map-type later
        if(isset($json_info['categories']))
        {
            foreach($json_info['categories'] as $cat)
            {
                if(strpos($cat['title'], "Category:") === 0)
                {
                    $this->add_extra_category(substr($cat['title'], 9));
                }else
                {
                    $this->add_extra_category($cat['title']);
                    echo "That's odd. The category ". $cat['title'] ." doesn't start with 'Category:' in API query for $this->title .\n";
                }
            }
        }else
        {
            echo "That's odd. No categories returned in API query for $this->title (in $url)\n";
        }
    }

    public static function check_page_titles($array_of_titles)
    {
        $url = "http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo&iiprop=url&redirects";
        return self::call_API($url, $array_of_titles);
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

class TaxonomyParameters
{
    // listed as most precise to least precise
    // 'species' is not past of the EoL output, but may be used to construct scientificName later
    public static $wiki_to_standard = array(
            "Species"   => "species",
            "Genus"     => "genus",
            "Familia"   => "family",
            "Ordo"      => "order",
            "Classis"   => "class",
            "Phylum"    => "phylum",
            "Regnum"    => "kingdom");
    private $taxon_params = array();
    private $page_timestamp;
    public  $authority;
    public  $last_taxonomy_change;

    public function __construct($page_timestamp = null, $last_taxonomy_change = null)
    {
        $this->page_timestamp = $page_timestamp;
        $this->last_taxonomy_change = $last_taxonomy_change;
    }

    public function get($standard_rank)
    {
        return @$this->taxon_params[$standard_rank];
    }


    public function add_wiki_info($wiki_rank, $wikitext)
    {
        $wiki_rank = WikiParser::mb_ucfirst(WikiParser::mb_trim($wiki_rank));
        $text = strip_tags(WikiParser::strip_syntax($wikitext));

        if($wiki_rank == 'Authority') return $this->add_info('authority', $text);
        if(empty(self::$wiki_to_standard[$wiki_rank])) return "";
        return $this->add_info(self::$wiki_to_standard[$wiki_rank], $text);
    }

    public function add_info($rank, $text)
    {
        $return_message = "";
        $name = WikiParser::mb_trim($text);
        if(!php_active_record\Functions::is_utf8($name) || preg_match("/\{|\}/u", $name))
        {
            $return_message = "Invalid characters in taxonomy fields ($rank = $name): ignoring this level. ";
            return $return_message;
        }
        // multiple spaces of any sort to single normal space
        $name = preg_replace("/\pZ+/u", " ", $name);
        if($rank == 'authority')
        {
            $this->authority = $name;
            return $return_message;
        }
        if(preg_match("/unidentified|unknown|incertae sedis| incertae/i", $name))
        {
            $return_message = "Name listed as unidentified ($rank = $name): ignoring this level. ";
            return $return_message;
        }
        // remove preceeding fossil: e.g. Fossil Pectinidae
        if(preg_match("/^fossil (.*)$/i", $name, $arr)) $name = WikiParser::mb_ucfirst(trim($arr[1]));
        // don't set anything if the string is empty
        if($name == '') return $return_message;
        if($rank == 'genus')
        {
            if(preg_match("/^([A-Z][^ ]+) [a-z]/", $name, $arr))
            {
                // careful with e.g. Category:Rosa_laxa which has Genus = 'Rosa species'
                $return_message = "Multi-word genus ($name) getting shortened to ". $arr[1];
                if(empty($this->taxon_params['species']))
                {
                    $return_message .=  " and species initially set to $name";
                    $this->taxon_params['species'] = $name;
                }
                $return_message .=  ". ";
                $name = $arr[1];
            }
            // species was set with just the epithet
            if(!empty($this->taxon_params['species']) && !preg_match("/\s/", $this->taxon_params['species']))
            {
                $this->taxon_params['species'] = $name . ' ' . $this->taxon_params['species'];
            }
        }
        if($rank == 'species')
        {
            // multiple words in species (this is the norm)
            if(preg_match("/ /", $name))
            {
                if(empty($this->taxon_params['genus']) && preg_match("/^([A-Z][^ ]+) [a-z]/", $name, $arr))
                {
                    $this->taxon_params['genus'] = $arr[1];
                    if($GLOBALS['ENV_DEBUG']) $return_message = "Genus ".$this->taxon_params['genus']." initially set from species name ('$name'). ";
                }
            }
            // single word in 'species' - this could be an epithet
            else
            {
                if(mb_strtolower($name, "UTF-8") != $name)
                {
                    $return_message = "Single-word species ('$name') has CaPs: ignoring this part of the classification. ";
                    return $return_message;
                }
                if(!empty($this->taxon_params['genus']))
                {
                    $name = $this->taxon_params['genus'] . ' ' . $name;
                }
            }
        }else
        {
            while(preg_match("/( \(.*?\))/", $name, $arr)) $name = str_replace($arr[1], '', $name);
            if(preg_match("/[ \(\)]/", $name))
            {
                // We make an exception here for classes 'Gamma Proteobacteria', 'Alpha Proteobacteria' etc.
                if(!preg_match("/^\w+ proteobacteria$/i", $name))
                {
                    $return_message .= "A classification level above that of species ($rank = '$name') has issues with brackets or spaces. ";
                    return $return_message;
                }
            }
        }
        $this->taxon_params[$rank] = $name;
        return $return_message;
    }

    public function scientificName()
    {
        if(!isset($this->scientificName))
        {
            $this->scientificName = "";
            // scientificName should be the lowest (most precise) classification level
            foreach(self::$wiki_to_standard as $rank)
            {
                if(!empty($this->taxon_params[$rank]))
                {
                    if(empty($this->authority))
                    {
                        $this->scientificName = $this->taxon_params[$rank];
                        break;
                    }else
                    {
                        // would be nice to mark up authority in some way here
                        $this->scientificName = $this->taxon_params[$rank] . " " . $this->authority;
                        break;
                    }
                }
            }
        }
        return $this->scientificName;
    }

    public function asEoLtaxonObject()
    {
        // calculate what EoL needs from the levels that we know about
        static $spp = array('species'=>null);
        $array_to_return = array_diff_key($this->taxon_params, $spp); // "species" level detail in EoL is contained in scientificName
        $array_to_return['scientificName'] = $this->scientificName();
        $array_to_return['dataObjects'] = array();
        return $array_to_return;
    }

    // in all the following functions, we assume $this->taxon_params does not contain empty values
    // as add_info() doesn't allow setting a taxonomic level witb an empty name

    public function number_of_levels()
    {
        return count($this->taxon_params);
    }

    public function identical_taxonomy_to($compare_to)
    {
        // note that some might have empty taxon params. We can ignore these
        return ($this->taxon_params == $compare_to->taxon_params);
    }

    public function page_younger_than($compare_to)
    {
        return (strtotime($this->page_timestamp) > strtotime($compare_to->page_timestamp));
    }

    public function is_less_precise_than($compare_to)
    {
        foreach(self::$wiki_to_standard as $std_key)
        {
            if(!empty($this->taxon_params[$std_key]) || (!empty($compare_to->taxon_params[$std_key])))
            {
                return empty($this->taxon_params[$std_key]);
            }
        }
        return false;
    }

    public function is_nested_in($compare_to)
    {
        if(count(array_diff_assoc($this->taxon_params, $compare_to->taxon_params))==0) return true;
        return false;
    }

    public function overlaps_without_conflict($compare_to)
    {
        // true e.g. if one array contains
        foreach(self::$wiki_to_standard as $std_key)
        {
            if(empty($this->taxon_params[$std_key])) continue;
            if(empty($compare_to->taxon_params[$std_key])) continue;
            if($this->taxon_params[$std_key] != $compare_to->taxon_params[$std_key]) return false;
        }
        return true;
    }
}

?>