<?php

class WikimediaPage
{
    public  $xml;
    private $simple_xml;
    private $data_object_parameters;
    private $galleries = array();

    /* see  http://www.mediawiki.org/wiki/Manual:MIME_type_detection
            http://dublincore.org/documents/dcmi-type-vocabulary/#H7
            http://dublincore.org/usage/meetings/2004/03/Relator-codes.html */
    private static $default_role = 'creator';
    private static $mediatypes = array(
        'BITMAP'  => array('dcmitype'=>'http://purl.org/dc/dcmitype/StillImage', 'role'=>'photographer'),
        'DRAWING' => array('dcmitype'=>'http://purl.org/dc/dcmitype/StillImage', 'role'=>'illustrator'),
        'AUDIO'   => array('dcmitype'=>'http://purl.org/dc/dcmitype/Sound',      'role'=>'recorder'),
        'VIDEO'   => array('dcmitype'=>'http://purl.org/dc/dcmitype/MovingImage','role'=>'creator'),
        // 'MULTIMEDIA' => '',
        'TEXT'    => array('dcmitype'=>'http://purl.org/dc/dcmitype/Text',       'role'=>'author'));

    // see http://commons.wikimedia.org/wiki/Help:Namespaces for relevant numbers
    public static $NS = array('Gallery' => 0, 'Media' => 6, 'Template' => 10, 'Category' => 14);
    private static $GALLERIES_UNRELATED_TO_TAXON = array('Related species');

    // For details see http://commons.wikimedia.org/w/api.php
    public static $API_URL = 'http://commons.wikimedia.org/w/api.php';
    public static $max_titles_per_lookup = 50;
    public static $max_categories_per_lookup = 500;
    // Maximum URL length ~ 8100 chars (https://www.mediawiki.org/wiki/API:FAQ#do_really_long_API_urls_not_work)
    public static $max_http_chars = 8100;
    // For allowed length of titles string, allow an extra 300 chars for ?action=query&..blah.blah.<TITLES>.continue=blah,blah
    // declare this as a function because static variables can't be initialized using e.g. strlen()
    public static function max_encoded_characters_in_titles() {return self::$max_http_chars - strlen(self::$API_URL) - 300;}

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
            }elseif($this->simple_xml)
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
            } else
            {
                $this->text = $this->title = $this->ns = $this->contributor = $this->timestamp = "";
            }
        }
    }

    public static function from_api($title)
    {
        $api_url = self::$API_URL.'?action=query&format=xml&prop=revisions&titles='. urlencode($title) .'&rvprop=ids|timestamp|user|content&redirects';
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
        $url = self::$API_URL.'?action=expandtemplates&format=xml&text='. urlencode($text);
        $response = \php_active_record\Functions::lookup_with_cache($url, array('validation_regex' => '<text', 'expire_seconds' => 518400));
        $hash = simplexml_load_string($response);
        if(@$hash->expandtemplates) return (string) $hash->expandtemplates[0];
    }

    public function expanded_text()
    {
        if(isset($this->expanded_text)) return $this->expanded_text;
        $url = self::$API_URL.'?action=parse&format=xml&prop=text&redirects&page='. urlencode($this->title);
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
        foreach(array("[Ii]nformation", "[Ss]pecimen", "[Ff]lickr") as $template_name)
        {
            $this->information = WikiParser::template_as_array($this->active_wikitext(), $template_name);
            if(!empty($this->information)) break;
        }
        // remove the template name
        array_shift($this->information);
        // Some templates, e.g. http://commons.wikimedia.org/wiki/Template:Flickr have "photographer" not (as we normally expect) "author"
        if (!(array_key_exists('author', $this->information) || array_key_exists('Author', $this->information)) && array_key_exists('photographer', $this->information))
            $this->information['author'] = $this->information['photographer'];
        return $this->information;
    }

    private function fill_includes_recursively(&$arr, $include_arrays, &$visited = array())
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
        $mesg = "";
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
        $Taxonav = array('taxo' => $this->taxonav_as_array("[Tt]axonavigation"), 'last_mod' => @strtotime($this->timestamp));
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

    public function best_license($potential_licenses, $is_wikitext = true)
    {
        // Can be used to identify either licenses in templates {{cc-by-1.0}} or categories [[CC-BY-1.0]]
        // usually these are of the same format, apart from e.g. {{XXX-no known copyright restrictions}}
        // Currently we miss files categorised under the FAL (http://en.wikipedia.org/wiki/Free_Art_License)
        // which may in future be compatible with CC-BY (see http://wiki.creativecommons.org/Version_4#Compatibility)
        // Also for images like https://commons.wikimedia.org/wiki/File:Aequorea1.jpeg which only require
        // attribution only, we could consider redistributing as CC-BY, but some legal opinion is probably needed
        // (see https://commons.wikimedia.org/wiki/Template_talk:Attribution#Compatibility_with_CC-BY)
        $identified_licenses = array();
        foreach($potential_licenses as $potential_license)
        {
            // catch e.g. PD-USGov-CIA-WF, etc and Copyrighted_free_use (but *not* "Copyrighted free use provided that")
            if(preg_match("/^(PD|Public[ _]domain.*|CC-PD|usaid|nih|noaa|CopyrightedFreeUse|Copyrighted[ _]Free[ _]Use(?![ _]provided[ _]that))($| |-)/mui", $potential_license))
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
            if(preg_match("/^CC-(BY(?:-NC)?(?:-ND)?(?:-SA)?)(.*)$/mui", $potential_license, $arr))
            {
                $license = strtolower($arr[1]);
                $rest = $arr[2];
                if(preg_match("/^-?([0-9]\.[0-9])/u", $rest, $arr)) $version = $arr[1];
                else $version = "3.0";
                if($license === 'by-nc-nd') continue;
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

    public function initialize_data_object($url, $mimetype, $dcmi, $role)
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

        if($this->point())
        {
            $this->data_object_parameters["point"] = new \SchemaPoint($this->point());
        }

        if($this->location())
        {
            $this->data_object_parameters["location"] = $this->location();
        }

        $this->data_object_parameters['mediaURL'] = $url;

        $this->data_object_parameters['dataType'] = $dcmi;

        $this->set_mimeType(php_active_record\Functions::get_mimetype($this->title));
        if (!empty($mimetype)) $this->set_mimeType($mimetype);

        $this->data_object_parameters["agents"] = array();
        if ($a = $this->agent_parameters($role))
        {
            if(php_active_record\Functions::is_utf8($a['fullName'])) $this->data_object_parameters["agents"][] = new SchemaAgent($a);
        }

        // the following properties may be overridden later by category data from the API.
        $this->licenses = $this->licenses_via_wikitext();
        $this->set_license($this->best_license($this->licenses));
    }

    public function reset_role_to_default()
    {
        //use this for edge cases where the normal "role" title for this media type is inappropriate
        if (count($this->data_object_parameters["agents"]))
        {
            $this->data_object_parameters["agents"][0]->role = self::$default_role;
        }
    }

    public function has_license()
    {
        if(empty($this->data_object_parameters['license'])) return false;
        else return true;
    }

    public function has_valid_mime_type($valid_mime_types)
    {
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


    public function set_mimeType($mimeType)
    {
        if(isset($this->data_object_parameters['mimeType']) && ($this->data_object_parameters['mimeType'] != $mimeType))
        {
            echo "Overriding mimeType for $this->title : current = ". $this->data_object_parameters['mimeType'] .", new = $mimeType\n";
        }
        $this->data_object_parameters['mimeType'] = $mimeType;
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

    public function agent_parameters($role="")
    {
        if(isset($this->agent_parameters)) return $this->agent_parameters;
        $author = $this->author();

        //some complicated regexps for sanitizing author information
        $homepage = "";
        $email = "";
        if(preg_match("/<a href='mailto:(.+?)'>/u", $author, $arr)) $email = $arr[1];
        if(preg_match("/<a href='(http.+?)'>/u", $author, $arr)) $homepage = $arr[1];
        //only allow wiki users, disallow arbitrary URLs (presumably to avoid linking to malicious sites)
        if(!preg_match("/\/wiki\/(user|:[a-z]{2})/ui", $homepage) || preg_match("/;/u", $homepage)) $homepage = "";

        $author = preg_replace("/<br\W*>/iu", "\n", $author);
        //insert a space between a word char or appropriate punctuation "." "," ")" and a following html tag
        $author = preg_replace("/(?<=[\w\.\),])<(?!\s*\/)/u", " <", $author);

        $author = strip_tags($author);
        //replace bullet points (* ) or indents (:) with simple newlines
        $author = preg_replace("/^\s*(:+|\*+)/mu", "\n", $author);
        //trim newlines at start & end
        $author = preg_replace("/^\n+/u", "", $author);
        $author = preg_replace("/\n+$/u", "", $author);

        //Newlines to sentences. 1) Colons at end-of-line are not new sentences, UNLESS followed by a line with a colon
        //see e.g. http://commons.wikimedia.org/wiki/File:Schistosoma_bladder_histopathology.jpeg
        $author = preg_replace("/:\n+([^\n]+)(?=:)/u", ". $1", $author);
        // 2) Make sentence from newline (unless the line ends in punctuation already)
        $author = preg_replace("/(?<![\.,:;])\s*\n+/u", ". ", $author);
        //contract multiple spaces
        $author = preg_replace("/\s+/u", " ", $author);

        //some unneeded text which is commonly found in Author attributions,
        //e.g. File:Black_Ruby_Barb_700.jpg, File:Aspidistra_elatior_Amomokawo_BotGardBln1205.jpg, File:Lumbar_plant_acerleaf_sick.jpg
        $author = preg_replace("/\(talk\)/ui", "", $author);
        $author = preg_replace("/^(photo(graph)? |image |picture )?(taken )?by(:| and)?/ui", "", $author);

        //swap copyright text for ©
        $author = preg_replace("/^\bcopyright\b/ui", "©", $author);
        $author = preg_replace("/\(c\)/ui", "©", $author);

        //remove copyright sign & potential date (plus comma)
        $author = preg_replace("/©( *)(\d\d\d\d *,?)?(by)?/", "", $author);

        //replace e.g. &eacute with unicode é
        $author = WikiParser::mb_trim(html_entity_decode($author));

        $agent_parameters = array();
        if($author)
        {
            $agent_parameters["fullName"] = $author;
            if(strlen($homepage) && php_active_record\Functions::is_ascii($homepage) && !preg_match("/[\[\]\(\)'\",;\^]/u", $homepage)) $agent_parameters["homepage"] = str_replace(" ", "_", $homepage);
            if(strlen($email) && php_active_record\Functions::is_ascii($email)) $agent_parameters["email"] = $email;
            if ($role)
            {
                $agent_parameters["role"] = $role;
            } else {
                $agent_parameters["role"] = self::$default_role;
            }
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
                if($attr === "author" || $attr === "Author") $author = WikiParser::strip_syntax($val, true);
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
                if($attr === "permission" || $attr === "Permission") $rights = WikiParser::strip_syntax($val, true);
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
                if($attr === "description" || $attr === "Description")
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
            if(!isset($location[8]))
            {
                // see http://commons.wikimedia.org/wiki/Template:Location_dec
                if(isset($location[1]) && isset($location[2]) && is_numeric($location[1]) && is_numeric($location[2]))
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
                // lazy - assume parameters 1-7 are set too
                $this->georef["latitude"] = $location[1] + ((($location[2] * 60) + ($location[3])) / 3600);
                if(stripos($location[4], "N") === false) $this->georef["latitude"] *= -1;
                $this->georef["longitude"] = $location[5] + ((($location[6] * 60) + ($location[7])) / 3600);
                if(stripos($location[8], "W") === true) $this->georef["longitude"] *= -1;
                if(isset($location[9]))
                {
                    $this->location = $location[9];
                }
            }
        }
        if($this->georef && (! is_numeric($this->georef["latitude"]) || ! is_numeric($this->georef["longitude"]) || ! $this->georef["latitude"] || ! $this->georef["longitude"]))
        {
            $this->georef = false;
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
        $text = $this->remove_unrelated_species_text($text);
        $lines = explode("\n", $text);
        foreach($lines as $line)
        {
            /* < > [ ] | { } not allowed in titles, so if we see this, it is end of filename (spots e.g. Image:xxx.jpg</gallery>)
             see http://en.wikipedia.org/wiki/Wikipedia:Naming_conventions_(technical_restrictions)#Forbidden_characters
             This is a horribly long regexp because "spaces" in the string may be "unicode spaces" or control characters
             - matched by [\pZ\pC]. */
            if(preg_match("/^[\pZ\pC]*\[{0,2}[\pZ\pC]*(Image|File)[\pZ\pC]*:(.*?)([|#<>{}[\]]|$)/iums", $line, $arr))
            {
                $media[] = WikiParser::make_valid_pagetitle(rawurldecode($arr[2]));
            }
        }
        return $media;
    }

    public function remove_unrelated_species_text($text)
    {
        // This will remove galleries of images for 'related' but not the same taxon.
        // See #DATA-749, http://commons.wikimedia.org/wiki/Boletus
        if(preg_match("/(==(". implode("|", self::$GALLERIES_UNRELATED_TO_TAXON) .")==\s*<gallery>.*?<\/gallery>)/iums", $text, $arr))
        {
            $text = str_replace($arr[1], '', $text);
        }
        return $text;
    }

    public function contains_template($template)
    {
        return preg_match("/\{\{".$template."\s*[\|\}]/u", $this->active_wikitext());
    }

    public function transcluded_categories()
    {
        //look for transclusions like {{:Category:Homo sapiens}} or {{Category:Homo sapiens}}
        if(preg_match_all("/\{\{:?Category:([^\}]+)\s*\}\}/u", $this->active_wikitext(), $arr))
        {
            return array_map(function($cat) { return "Category:".\WikiParser::make_valid_pagetitle($cat);}, $arr[1]);
        } else
        {
            return array();
        }
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

    public static function call_API(&$url, $titles)
    {
        if(!$titles) return array();
        // return an array with $title => json_result
        $real_titles = array_combine($titles, $titles);
        $results = array();
        //use curl_version and phpversion to give a polite user-agent to Commons, see http://meta.wikimedia.org/wiki/User-Agent_policy
        $curl_info = curl_version();
        $curl_options = array(
            'user_agent' => 'EoLWikimediaHarvestingBot/1.0 (http://EoL.org; https://github.com/EOL; tech@eol.org) PHP_'.phpversion().'-libcurl/'.$curl_info['version'],
            'download_wait_time' => 5000000,
            'download_attempts' => 3,
            'encoding' => 'gzip,deflate',
            'validation_regex' => 'query',
            'expire_seconds' => 518400);

        if(count($titles) > self::$max_titles_per_lookup)
        {
            echo "ERROR: only allowed a maximum of ". self::$max_titles_per_lookup ." titles in a single API query.\n";
            return $results;
        }elseif(count($titles) == 0) return;

        $url .= "&titles=". urlencode(implode("|", $titles));
        $continue = "&continue=";
        while(!empty($continue))
        {
            $result = php_active_record\Functions::lookup_with_cache($url.$continue, $curl_options);

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
                // All redirected pages should have been caught in the XML dump.
                // Here we should only have pages which have changed since the dump was produced.
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
        $url = self::$API_URL.'?action=query&format=json&prop=imageinfo%7Ccategories&iiprop=url%7Cmime%7Cmediatype&cllimit='. self::$max_categories_per_lookup .'&redirects';

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
                $page->initialize_data_object_from_api_response($json_info, $url);
                $page->initialize_categories_from_api_response($json_info, $url);
            }
        }
    }

    private function initialize_data_object_from_api_response($json_info, $reference_url)
    {
        // set URL, mimetype, mediatype
        $url = @$json_info['imageinfo'][0]['url'];
        $mimetype = @$json_info['imageinfo'][0]['mime'];
        $mediatype = @$json_info['imageinfo'][0]['mediatype'];
        $dcmi="";
        $role="";

        if (empty($url))
        {
            $url = "";
            echo "That's odd. No URL returned in API query for $this->title (from $reference_url)\n";
        };
        if (empty($mimetype))
        {
            echo "That's odd. No mimetype returned in API query for $this->title (from $reference_url)\n";
        };
        if (empty($mediatype))
        {
            echo "That's odd. No mediatype returned in API query for $this->title (from $reference_url)\n";
        } elseif (!isset(self::$mediatypes[$mediatype]))
        {
            echo "Non-compatible mediatype: $mediatype for $this->title (from $reference_url)\n";
        } else {
            $dcmi = self::$mediatypes[$mediatype]['dcmitype'];
            $role = self::$mediatypes[$mediatype]['role'];
        }

        if($dcmi) $this->initialize_data_object($url, $mimetype, $dcmi, $role);
        else echo "\nWill not create object. \n url=[$url], \n mimetype=[$mimetype], \n dcmi=[$dcmi], role=[$role]\n";
    }

    private function initialize_categories_from_api_response($json_info, $reference_url)
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
                    echo "That's odd. The category ". $cat['title'] ." doesn't start with 'Category:' in API query for $this->title (from $reference_url).\n";
                }
            }
        }else
        {
            echo "That's odd. No categories returned in API query for $this->title (from $reference_url)\n";
        }
    }

    public static function check_page_titles($array_of_titles)
    {
        $url = self::$API_URL.'?action=query&format=json&prop=imageinfo&iiprop=url&redirects';
        return self::call_API($url, $array_of_titles);
    }

}

class TaxonomyParameters
{
    // Wiki names from https://commons.wikimedia.org/wiki/Template:Taxonavigation, listed here from most precise to least precise
    // 'subspecies' and 'species' are not part of the EoL output, but may be used to construct scientificName later
    public static $wiki_to_standard = array(
            "Varietas"  => "variety",
            "Subspecies"=> "subspecies",
            "Species"   => "species",
            "Genus"     => "genus",
            "Familia"   => "family",
            "Ordo"      => "order",
            "Classis"   => "class",
            "Phylum"    => "phylum",
            "Regnum"    => "kingdom");
    public static $extra_params = array("Authority" => "authority");
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
        $wiki_rank = WikiParser::mb_trim($wiki_rank);
        //remove notho- designation, see http://ibot.sav.sk/icbn/frameset/0071AppendixINoHa003.htm
        $wiki_rank = preg_replace("/^notho/i", "", $wiki_rank);
        $wiki_rank = WikiParser::mb_ucfirst($wiki_rank);
        $text = strip_tags(WikiParser::strip_syntax($wikitext));

        //translate all listed in $wiki_to_standard, plus the Authority field
        $allowed_params = self::$wiki_to_standard + self::$extra_params;
        if (array_key_exists($wiki_rank, $allowed_params))
        {
            return $this->add_info($allowed_params[$wiki_rank], $text);
        }
        return "";
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
        if($rank === 'authority')
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
        if($name === '') return $return_message;

        /* Make hybrid names a single word, replacing space after the × sign with a non-breaking space
           Treat X, x or × as hybrid indicators if they are at the start or preceeded by a space, e.g. "X Cleistoza" becomes 
           ×_nbsp_Cleistoza and Salix × pendulina becomes Salix ×_nbsp_pendulina. This also helps us delimit species and genera names */
        static $multiply_sign_and_nonbreaking_space = "× "; //make sure the "space" in this string is actually a NBSP
        $name = preg_replace("/(?<=^| )(× *|x +)/iu", $multiply_sign_and_nonbreaking_space, $name);

        if($rank === 'genus')
        {
            if(preg_match("/^([A-Z×][^ ]+) [a-z×]/u", $name, $arr))
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
            if(!empty($this->taxon_params['species']) && !preg_match("/ /", $this->taxon_params['species']))
            {
                $this->taxon_params['species'] = $name . ' ' . $this->taxon_params['species'];
            }
        }
        if(($rank === 'species') || ($rank === 'subspecies') || ($rank === 'variety'))
        {
            /* TODO - caution here with virus species names, which can contain multiple words and capitals. We need something like
                  if ($this->taxon_params['domain'] != "Viruses") ...
               only we don't currently store the domain name, so we can't check. This is only a problem if the Species field contains a
               multi-word epithet that happens to start with a capital letter, and we haven't yet defined a genus (pretty rare), in which case we will
               assume a genus name from the first word of the epithet, or if Species field contains a single-word epithet with caps, in which case
               the epithet will be ignored and a warning given (so e.g. we currently miss https://commons.wikimedia.org/wiki/Category:Theilovirus) */

            // multiple words in (sub)species (this is the norm)
            if(preg_match("/ /", $name))
            {
                if(empty($this->taxon_params['genus']) && preg_match("/^([A-Z×][^ ]+) [a-z×]/u", $name, $arr))
                {
                    $this->taxon_params['genus'] = $arr[1];
                    if($GLOBALS['ENV_DEBUG']) $return_message = "Genus ".$this->taxon_params['genus']." initially set from $rank name ('$name'). ";
                }
                if (($rank === 'subspecies') || ($rank === 'variety'))
                {
                    //sometimes in longer subspecies or variety names, people forget to put the dot after subsp. or have ssp instead. Standardise these
                    $name = preg_replace("/ (subsp|ssp\.?) /i", " subsp. ", $name);
                }
                if ($rank === 'variety')
                {
                    //sometimes people forget to put the dot after var. Standardise these
                    $name = preg_replace("/ var /i", " var. ", $name);
                    //TODO - we don't cope with multi-word varieties which are epithets, most likely seen incorrectly in 
                    //cultivars, e.g. Varietas='my variety name'.
                }
            }
            // single word in 'species', 'subspecies', or variety - this could be an epithet
            else
            {
                if(mb_strtolower($name, "UTF-8") != $name)
                {
                    $return_message = "Single-word $rank ('$name') has CaPs: ignoring this part of the classification. ";
                    return $return_message;
                }
                if ($rank === 'species')
                {
                    if (empty($this->taxon_params['genus']))
                    {
                        $return_message .= "Single word specific name, but no genus given: ignoring the species information. ";
                        return $return_message;
                    }
                    $name = $this->taxon_params['genus'] . ' ' . $name;
                }
                if ($rank === 'subspecies')
                {
                    if (empty($this->taxon_params['species']))
                    {
                        $return_message .= "Single word subspecific name, but no species given: ignoring the subspecific information. ";
                        return $return_message;
                    }
                    $name = $this->taxon_params['species'] . ' ' . $name;
                }
                if ($rank === 'variety')
                {
                    //In plants, a single-word variety name might exist alongside a subspecies, e.g. subspecies|Brassica rapa subsp. nipposinica|varietas|perviridis
                    //At this point, we assume that any single-word species or subspecies names have already been fully filled out by previous calls to this code 
                    if (empty($this->taxon_params['subspecies']))
                    {
                    	// We have a single word variety, but no trinomial to append it to => look for a binomial instead
                        if (empty($this->taxon_params['species']))
                        {
                            $return_message .= "Single word variety name, but neither species nor subspecies given: ignoring the variety information. ";
                            return $return_message;
                        }
                        $name = $this->taxon_params['species'] . ' var. ' . $name;
                    } else {
                        $name = $this->taxon_params['subspecies'] . ' var. ' . $name;
                    }
                }
            }
        }else
        {
            /* By wikimedia commons convention, taxon names like "Zeus", "Viola", or "Turbo" that already have unrelated wikimedia pages
            are given gallery and category names like "Zeus (fish)", "Viola (Violaceae)" and "Turbo (genus)" which appear as Taxonavigation names.
            So we should remove any terminal part of the name that is in (round) parentheses */
            $name = preg_replace("/ \(.*?\) *$/u", "", $name);

            if(preg_match("/[ \(\)]/", $name))
            {
                // We make an exception here for classes 'Gamma Proteobacteria', 'Alpha Proteobacteria' etc.
                if(!preg_match("/^\w+ proteobacteria$/i", $name))
                {
                    $return_message .= "A classification level above that of species ($rank = '$name') has brackets or spaces: ignoring it. ";
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
        static $not_returned = array('species' => null, 'subspecies' => null, 'variety' => null);
        // species and lower level detail in EoL is contained in scientificName, so we don't return these fields
        $array_to_return = array_diff_key($this->taxon_params, $not_returned);
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
        //kill off warnings about unset timezones (wiki timestamps are always stamped with GMT (Zulu=Z) time)
        return (@strtotime($this->page_timestamp) > @strtotime($compare_to->page_timestamp));
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
        if(count(array_diff_assoc($this->taxon_params, $compare_to->taxon_params)) == 0) return true;
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
