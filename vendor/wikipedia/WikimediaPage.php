<?php

class WikimediaPage
{
    public  $xml;
    private $simple_xml;
    private $data_object_parameters;

    // see http://www.mediawiki.org/wiki/Manual:MIME_type_detection
    private static $mediatypes = array(
                                    'BITMAP'  => 'http://purl.org/dc/dcmitype/StillImage',
                                    'DRAWING' => 'http://purl.org/dc/dcmitype/StillImage',
                                    'AUDIO'   => 'http://purl.org/dc/dcmitype/Sound',
                                    'VIDEO'   => 'http://purl.org/dc/dcmitype/MovingImage',
//                                  'MULTIMEDIA' => '',
                                    'TEXT'    => 'http://purl.org/dc/dcmitype/Text');
    // see http://commons.wikimedia.org/wiki/Help:Namespaces for relevant numbers
    public static $NS = array('Gallery' => 0, 'Media' => 6, 'Template' => 10, 'Category' => 14);

    function __construct($xml)
    {
        $this->xml = $xml;
        $this->simple_xml = @simplexml_load_string($this->xml);
        if(preg_match("/^<\?xml version=\"1\.0\"\?><api><query>/", $xml))
        {
            $this->text = (string) $this->simple_xml->query->pages->page->revisions->rev;
            $this->title = (string) $this->simple_xml->query->pages->page['title'];
            $this->ns = (integer) $this->simple_xml->query->pages->page['ns'];
            $this->contributor = (string) $this->simple_xml->query->pages->page->revisions->rev['user'];
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
            if(isset($this->simple_xml->redirect))
            {
                $this->redirect = (string) $this->simple_xml->redirect->attributes()->title;
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

    //these are less dependent on the exact XML string, but require a page to have been parsed, so are slower
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

    private function fill_includes_recursively(&$arr, &$include_arrays, &$visited=array())
    {
        if(!empty($arr['include']))
        {
            $include = "Template:".ucfirst($arr['include']);
            if(!isset($visited[$include])) // stops infinite recursive loops
            {
                $visited[$include] = 1;
                if(isset($include_arrays[$include]))
                {
                    self::fill_includes_recursively($include_arrays[$include], $include_arrays, $visited);
                    $arr = array_merge($include_arrays[$include], $arr);
                }else
                {
                    echo "Found a non-existent Taxonav include value: '$include' within ".implode(":",$arr)."\n";
                    flush();
                }
                unset($arr['include']);
            }
        }
        return $arr;
    }

    public function taxonomy_via_API()
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
                    $value = str_replace(" (genus)", "", $value); //this is never used, as far as I can tell
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
                        if(!isset($taxonomy[$rank]))
                        {
                            // usually the name is in italics, followed by the authority. If we spot this, just take the name
                            // avoids adding the authority to the Genus name, e.g. http://commons.wikimedia.org/wiki/Byblis_filifolia
                            $name = preg_replace("/^(.*)<\/i>.*/", "$1", WikiParser::strip_syntax($arr[2], true));
                            $taxonomy[$rank] = preg_replace("/\s+/u", " ", trim(strip_tags($name)));
                        }
                    }
                }
            }
        }
        foreach($taxonomy as &$name)
        {
            $name = str_ireplace("/ type species/u", " ", $name);
            $name = preg_replace("/<br ?\/>/u", " ", $name);
            $name = trim(preg_replace("/\s+/u", " ", WikiParser::strip_syntax(trim($name))));
            $name = ucfirst($name);
        }

        reset($taxonomy);
        $this->taxonomy = $taxonomy;
        return $taxonomy;
    }

    public function taxonomy_via_wikitext(&$taxonav_include_arrays)
    {
        // Note that unlike taxonomy_via_API, this deliberately does not include the authority name in the scientificName
        // This also means we don't have problems with non-ascii characters in authority names, etc.
        if(isset($this->taxonomy)) return $this->taxonomy;
        $Taxonavigation = $this->taxonav_as_array("[Tt]axonavigation");
        $taxonomy = self::fill_includes_recursively($Taxonavigation, $taxonav_include_arrays);

        array_walk($taxonomy, function(&$val) { $val = strip_tags(WikiParser::strip_syntax(trim($val))); });

        // there are often some extra ranks under the Taxonnavigation box -
        if(preg_match("/\}\}\s*\n(\s*----\s*\n)?((\*?(genus|species):.*?\n)*)/ims", $this->active_wikitext(), $arr))
        {
            $entries = explode("\n", $arr[2]);
            foreach($entries as $entry)
            {
                if(preg_match("/^\*?(genus|species):(.*)/ims", trim($entry), $arr))
                {
                    $rank = strtolower($arr[1]);
                    if(!isset($taxonomy[$rank]))
                    {
                        // usually the name is in italics, followed by the authority. If we spot this, just take the name
                        // avoids adding the authority to the Genus name, e.g. http://commons.wikimedia.org/wiki/Byblis_filifolia
                        $name = preg_replace("/^(.*)<\/i>.*/", "$1", WikiParser::strip_syntax($arr[2], true));
                        $taxonomy[$rank] = preg_replace("/\s+/u", " ", trim(strip_tags($name)));
                    }
                }
            }
        }
        $this->taxonomy = $taxonomy;
        return $taxonomy;
    }

    public function taxonomy_coverage_score()
    {
        if(!isset($this->taxon_parameters)) $this->taxon_parameters();
        $taxon_score = 0;
        $rank_weight = 1;
        foreach(self::wiki_ranks_to_eol_ranks() as $wiki_rank => $eol_rank)
        {
            $rank_weight *= 2;
            if(isset($this->taxon_parameters[$eol_rank])) $taxon_score += $rank_weight;
        }
        return $taxon_score;
    }

    private static function wiki_ranks_to_eol_ranks()
    {
        static $ranks = array(
            "regnum"    => "kingdom",
            "phylum"    => "phylum",
            "classis"   => "class",
            "ordo"      => "order",
            "familia"   => "family",
            "genus"     => "genus",
            "species"   => "scientificName");
        return $ranks;
    }

    public function taxon_parameters(&$taxonav_include_arrays = null)
    {
        if(isset($this->taxon_parameters)) return $this->taxon_parameters;
        if(empty($taxonav_include_arrays)) $taxonomy = $this->taxonomy_via_API();
        else $taxonomy = $this->taxonomy_via_wikitext($taxonav_include_arrays);

        if(!$taxonomy) return array();

        $taxon_parameters = array();
        //attempts to set $taxon_parameters['scientificName'] to the lowest level

        $best_rank = null;
        foreach(self::wiki_ranks_to_eol_ranks() as $wiki_rank => $eol_rank)
        {
            $best_score *= 2;
            if(empty($taxonomy[$wiki_rank])) continue;
            $name = $taxonomy[$wiki_rank];
            if(!php_active_record\Functions::is_utf8($name) || preg_match("/\{|\}/u", $name))
            {
                echo "Invalid characters in taxonomy fields ($wiki_rank = $name) for $this->title . Ignoring this level.\n";
                continue;
            }
            if(preg_match("/unidentified|unknown|incertae sedis| incertae/i", $name))
            {
                echo "Name in $this->title listed as unidentified ($name). Ignoring this part of the classification.\n";
                continue;
            }
            // remove preceeding fossil: e.g. Fossil Pectinidae
            if(preg_match("/^fossil (.*)$/i", $name, $arr)) $name = ucfirst(trim($arr[1]));
            if($wiki_rank == 'genus' && preg_match("/^([A-Z][^ ]+) [a-z]/", $name, $arr))
            {
                echo "Multi-word genus ($name) in $this->title getting shortened to ". $arr[1] ."\n";
                if(empty($taxonomy['species']))
                {
                    echo "...and setting the species to $name\n";
                    $taxonomy['species'] = $name;
                }
                $name = $arr[1];
            }
            // no space in spp name, could be just the epithet
            if(($wiki_rank == "species") && !preg_match("/\s/", $name))
            {
                if(empty($taxonomy['genus']))
                {
                    echo "Single-word species ($name) but no genus in $this->title . Ignoring this part of the classification.\n";
                    continue;
                }elseif(mb_strtolower($name, "UTF-8") != $name)
                {
                    echo "Single-word species ($name) has CaPs in $this->title . Ignoring this part of the classification.\n";
                    continue;
                }
                $name = $taxonomy['genus'] ." ". $name;
            }
            if($wiki_rank != 'species')
            {
                while(preg_match("/( \(.*?\))/", $name, $arr)) $name = str_replace($arr[1], '', $name);
                if(preg_match("/[ \(\)]/", $name))
                {
                    echo "Non-species $wiki_rank ($name) has issues in $this->title.\n";
                }
            }
            if($wiki_rank == 'species' && empty($taxonomy['genus']) && preg_match("/^([A-Z][^ ]+) [a-z]/", $name, $arr))
            {
                $taxonomy['genus'] = $arr[1];
            }
            $taxon_parameters[$eol_rank] = $name;
            $best_rank = $eol_rank;
        }

        if($best_rank)
        {
            $best_name = $taxon_parameters[$best_rank];
            unset($taxon_parameters[$best_rank]);
            if(isset($taxonomy['authority']))
            {
                $best_name = trim($best_name ." ". $taxonomy['authority']);
            }
            $taxon_parameters['scientificName'] = $best_name;
        }
        $taxon_parameters['dataObjects'] = array();
        $this->taxon_parameters = $taxon_parameters;
        return $taxon_parameters;
    }

    public function weighted_taxonomy_score()
    {
        // used when we have a choice between 2 taxonomies: pick the one with the highest score
        // TODO = improve algorithm, so that better-filled out taxonomies scaore higher, all else being equal
        // if equal score, categories are a bit more trustworthy
        if($this->is_category()) return $this->taxonomy_coverage_score() + 0.2;
        elseif($this->is_gallery()) return $this->taxonomy_coverage_score() + 0.1;
        else return $this->taxonomy_coverage_score() + 0.0;
    }

    public static function match_license($val, $searching_wikitext = true)
    {
        if($searching_wikitext) $regex_modifiers = "";
        else $regex_modifiers = "i";
        // The licenses should be listed in order of preference
        // PD-USGov-CIA-WF
        if(preg_match("/(^|\n)(PD|Public domain.*|CC-PD|usaid|nih|noaa|CopyrightedFreeUse|Copyrighted Free Use)($| |-)/mu" . $regex_modifiers, $val))
        {
            return "http://creativecommons.org/licenses/publicdomain/";
        }
        // cc-zero
        if(preg_match("/(^|\n)CC-Zero/mu" . $regex_modifiers, $val))
        {
            return "http://creativecommons.org/publicdomain/zero/1.0/";
        }
        // no known copyright restrictions
        if(preg_match("/(^|\n)(flickr-)?no known copyright restrictions/mu" . $regex_modifiers, $val))
        {
            return "http://www.flickr.com/commons/usage/";
        }
        // {{gfdl|migration=relicense}} can be relicensed as cc-by-sa-3.0
        if($searching_wikitext && preg_match("/migration=relicense/mu" . $regex_modifiers, $val))
        {
            return "http://creativecommons.org/licenses/by-sa/3.0/";
        }
        // simple cc-by-2.5,2.0,1.0-de preferred
        if(preg_match("/(^|\n)CC-(BY)(-\d.*)$/mu" . $regex_modifiers, $val, $arr))
        {
            $license = strtolower($arr[2]);
            $rest = $arr[3];
            if(preg_match("/^-?([0-9]\.[0-9])/u", $rest, $arr)) $version = $arr[1];
            else $version = "3.0";
            return "http://creativecommons.org/licenses/$license/$version/";
        }
        // cc-by-sa-2.5,2.0,1.0-de, next most preferred
        if(preg_match("/(^|\n)CC-(BY-SA)(-\d.*)$/mu" . $regex_modifiers, $val, $arr))
        {
            $license = strtolower($arr[2]);
            $rest = $arr[3];
            if(preg_match("/^-?([0-9]\.[0-9])/u", $rest, $arr)) $version = $arr[1];
            else $version = "3.0";
            return "http://creativecommons.org/licenses/$license/$version/";
        }
        // cc-sa-1.0
        if(preg_match("/(^|\n)(CC-SA)(.*)$/mu" . $regex_modifiers, $val, $arr))
        {
            $license = "by-sa";
            $rest = $arr[3];
            if(preg_match("/^-?([0-9]\.[0-9])/u", $rest, $arr)) $version = $arr[1];
            else $version = "3.0";
            return "http://creativecommons.org/licenses/$license/$version/";
        }
        // catch all the rest of the cc-licenses, if we've got this far
        if(preg_match("/(^|\n)CC-(BY(-NC)?(-ND)?(-SA)?)(.*)$/mu" . $regex_modifiers, $val, $arr))
        {
            $license = strtolower($arr[2]);
            $rest = $arr[3];
            if(preg_match("/^-?([0-9]\.[0-9])/u", $rest, $arr)) $version = $arr[1];
            else $version = "3.0";
            return "http://creativecommons.org/licenses/$license/$version/";
        }
        return null;
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

        // the following properties may be overridden later by data from the API.
        $licenses = $this->licenses_via_wikitext();
        $this->set_license(self::match_license(implode("\n", $licenses)));
        $this->set_mimeType(php_active_record\Functions::get_mimetype($this->title));
    }

    public function has_license()
    {
        if(empty($this->data_object_parameters['license'])) return false;
        else return true;
    }

    public function has_valid_mime_type()
    {
        static $valid_mime_types = array('image/png', 'image/jpeg', 'image/gif', 'application/ogg', 'image/svg+xml', 'image/tiff');
        if(empty($this->data_object_parameters['mimeType'])) return false;
        if(!in_array($this->data_object_parameters['mimeType'], $valid_mime_types)) return false;
        return true;
    }

    public function reassess_licenses_with_additions($potential_license_categories)
    {
        if(!$potential_license_categories) return;
        if($license = \WikimediaPage::match_license(implode("\n", $potential_license_categories), false))
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

    public function set_gallery($galleryname)
    {
        $this->gallery = $galleryname;
    }

    public function get_gallery()
    {   // NB not all media pages will be associated with a gallery
        if(isset($this->gallery)) return $this->gallery;
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

    public function licenses_via_wikitext() //this just looks through the plain wikitext for things like {{GFDL}}
    {
        if(isset($this->licenses)) return $this->licenses;
        $licenses = array();
        if(preg_match_all("/(\{\{.*?\}\})/u", $this->active_wikitext(), $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                // echo "potential license: $match[1]\n";
                while(preg_match("/(\{|\|)(cc-.*?|pd|pd-.*?|gfdl|gfdl-.*?|noaa|usaid|nih|copyrighted free use|CopyrightedFreeUse|creative commons.*?|migration=.*?|flickr-no known copyright.*?|no known copyright.*?)(\}|\|)(.*)/umsi", $match[1], $arr))
                {
                    $licenses[] = trim($arr[2]);
                    $match[1] = $arr[3].$arr[4];
                }
            }
        }
        if(!$licenses && preg_match("/permission\s*=\s*(cc-.*?|gpl.*?|public domain.*?|creative commons .*?)(\}|\|)/umsi", $this->text, $arr))
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
                if($attr == "author" || $attr == "Author") $author = self::convert_diacritics(WikiParser::strip_syntax($val, true));
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
            {  // see http://commons.wikimedia.org/wiki/Template:Location_dec
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
            { // see http://commons.wikimedia.org/wiki/Template:Location
                if(isset($location[8])) //lazy - assume 1-7 are also set if so
                {

                    $this->georef["latitude"] = $location[1]+((($location[2]*60)+($location[3]))/3600);
                    if(stripos($location[4], "N") === false) $this->georef["latitude"] *= -1;
                    $this->georef["longitude"] = $location[5]+((($location[6]*60)+($location[7]))/3600);
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
            # < > [ ] | { } not allowed in titles, so if we see this, it is end of filename (spots e.g. Image:xxx.jpg</gallery>)
            # see http://en.wikipedia.org/wiki/Wikipedia:Naming_conventions_(technical_restrictions)#Forbidden_characters
            if(preg_match("/^\s*\[{0,2}\s*(Image|File)\s*:\s*(\S)(.*?)\s*([|#<>{}[\]]|$)/iums", $line, $arr))
            {
                $first_letter = $arr[2];
                $rest = $arr[3];
                // In <title>, all pages have a capital first letter, and single spaces replace any combo of spaces + underscores
                // Can't use ucfirst() as this string may be unicode.
                $media[] = mb_strtoupper($first_letter,'utf-8') . preg_replace("/[_ ]+/u", " ", $rest);
            }
        }
        return $media;
    }

    public function contains_template($template)
    {
        return (preg_match("/\{\{".$template."\s*[\|\}]/u", $this->active_wikitext()));
    }

    public function taxonav_as_array($template_name, $strip_syntax = true)
    {   // A special format for Taxonavigations, where e.g. param[1] is "Cladus" and param[2] is "magnoliids"
        // Place param[1] as the key, and param[2] as the value of the returned array, so that e.g.
        // Taxonavigation|Cladus|magnoliids|Ordo|Laurales becomes [0=>Taxonavigation, Cladus=>magnoliids, Ordo=>Laurales]
        //
        // Take care when using, as $template_name is allowed to be a RegExp.
        $plain_array = WikiParser::template_as_array($this->active_wikitext(), $template_name);
        $tnav_array  = array();

        foreach($plain_array as $param => $value)
        {
            $value = trim($value);
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
                                $tnav_array[lcfirst($value)] = WikiParser::strip_syntax($plain_array[$param+1]);
                            }else
                            {
                                $tnav_array[lcfirst($value)] = $plain_array[$param+1];
                            }
                        }else
                        {
                           echo "Note: there don't seem to be the right number of parameters in $template_name within http://commons.wikimedia.org/wiki/$this->title\n";
                        }
                    }
                }
            }else
            {
                $tnav_array[$param] = $value;
            }
        }
        return $tnav_array;
    }

    public static $max_titles_per_lookup = 50; //see see http://commons.wikimedia.org/w/api.php
    public static $max_categories_per_lookup = 500; //see see http://commons.wikimedia.org/w/api.php

    public static function call_API(&$url, $titles)
    {
        if(!$titles) return array();
        //return an array with $title => json_result
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
            $result = php_active_record\Functions::lookup_with_cache($url.$contunue, array(
                'download_wait_time' => 5000000,
                'download_attempts' => 3,
                'user_agent' => 'gzip,deflate',
                'validation_regex' => 'query',
                'expire_seconds' => 518400));

            $json = json_decode($result, true); //return as an associative array
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
                        echo "ERROR: couldn't find $title when querying API ($url)\n";
                        continue;
                    }

                    // see http://www.mediawiki.org/wiki/API:Query#Missing_and_invalid_titles
                    if(array_key_exists("missing",$page))
                    {
                        echo "The file $title is missing from Commons. Perhaps it has been deleted? Leaving it out.\n";
                        continue;
                    }

                    // see http://www.mediawiki.org/wiki/API:Query#Missing_and_invalid_titles
                    if(array_key_exists("invalid",$page))
                    {
                        echo "The name $title is invalid. Leaving it out.\n";
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

?>
