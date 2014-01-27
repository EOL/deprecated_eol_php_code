<?php

class WikiParser
{
    public static function mb_trim($string)
    {
        // Several wikitext examples have control characters or odd unicode spaces at the end or beginning
        // This url may be helpful: http://www.php.net/manual/en/regexp.reference.unicode.php
        // Notably, often category names are pasted with an extraneous Left-to-Right mark (U+200E)
        //  at the end, which we match here with \pC and then trim.
        return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u','',$string);
    }

    public static function mb_ucfirst($string)
    {
        $firstletter = mb_strtoupper(mb_substr($string, 0, 1));
        return $firstletter.mb_substr($string, 1);
    }

    public static function make_valid_pagetitle($string)
    {
        // In <title>, all pages have a capital first letter, and single spaces replace any combo of underscores,
        // whitespace (tabs etc), and true (unicode) spaces. We should retain unicode control characters, as in
        // File:ആഫ്രിക്ക<U+0D7B>_ഒച്ച്<U+200C>_(Achatina_fulica)_കേരളത്തി<U+0D7D>_(2012).JPG
        $string = preg_replace("/[_\s\pZ]+/u", " ", $string);
        $string = self::mb_trim($string);
        return self::mb_ucfirst($string);
    }

    public static function strip_syntax($string, $format = false, $pagename = false)
    {
        if($format == false)
        {
            $string = str_replace("&nbsp;", " ", $string);
        }
        $string = htmlspecialchars_decode(html_entity_decode($string));

        // [[ ... ]]
        while(preg_match("/(\[\[.*?\]\])(.*)$/ums", $string, $arr))
        {
            $match = $arr[1];
            list($match, $junk) = self::balance_tags("[[", "]]", $match, $arr[2]);
            $replacement = self::format_brackets($match, $format);
            $string = str_replace($match, $replacement, $string);
        }

        // [http://... The text to link to]
        while(preg_match("/(\[\s*((?:https?:\/\/|mailto:)[^ ]+)\s(.*?)\])(.*)$/uims", $string, $arr))
        {
            $match = $arr[1];
            if($format) $string = str_replace($match, "<a href='$arr[2]'>$arr[3]</a>", $string);
            else $string = str_replace($match, $arr[3], $string);
        }

        // [http://...]
        while(preg_match("/(\[\s*((?:https?:\/\/|mailto:)[^ ]+?)\])(.*)$/uims", $string, $arr))
        {
            $match = $arr[1];
            if($format) $string = str_replace($match, "<a href='$arr[2]'>$arr[2]</a>", $string);
            else $string = str_replace($match, "", $string);
        }

        // {{ ... }}
        while(preg_match("/(\{\{.*?\}\})(.*)/ums", $string, $arr))
        {
            $match = $arr[1];
            list($match, $junk) = self::balance_tags("{{", "}}", $match, $arr[2]);
            $replacement = self::format_curly_brackets($match, $format, $pagename);
            $string = str_replace($match, $replacement, $string);
        }

        // <ref... />
        while(preg_match("/(<ref[^>]*\/>)(.*)/iums", $string, $arr))
        {
            $match = $arr[1];
            $string = str_replace($match, "", $string);
        }

        // <ref...> ... </ref>
        while(preg_match("/(<ref[^>]*>.*?<\/ref>)(.*)/iums", $string, $arr))
        {
            $match = $arr[1];
            list($match, $junk) = self::balance_tags("<ref", "</ref>", $match, $arr[2]);
            $replacement = self::format_reference($match, $format);
            $string = str_replace($match, $replacement, $string);
        }

        // <!-- ... -->
        while(preg_match("/(<\!--.*?-->)(.*)/ums", $string, $arr))
        {
            $match = $arr[1];
            list($match, $junk) = self::balance_tags("<!--", "-->", $match, $arr[2]);
            $replacement = self::format_html_comment($match, $format);
            $string = str_replace($match, $replacement, $string);
        }

        if($format) $string = preg_replace("/'''(.*?)'''/u", "<b>\\1</b>", $string);
        else $string = preg_replace("/'''(.*?)'''/u", "\\1", $string);
        // kill off any remaining unmatched ''' to avoid misinterpretting ''''' as '' '' '
        $string = str_replace("'''", "", $string);

        if($format) $string = preg_replace("/''(.*?)''/u", "<i>\\1</i>", $string);
        else $string = preg_replace("/''(.*?)''/u", "\\1", $string);

        if($format) $string = preg_replace("/====(.*?)====/u", "<h4>\\1</h4>", $string);
        else $string = preg_replace("/====(.*?)====/u", "\\1", $string);

        if($format) $string = preg_replace("/===(.*?)===/u", "<h3>\\1</h3>", $string);
        else $string = preg_replace("/===(.*?)===/u", "\\1", $string);

        if($format) $string = preg_replace("/==(.*?)==/u", "<h2>\\1</h2>", $string);
        else $string = preg_replace("/==(.*?)==/u", "\\1", $string);

        return htmlspecialchars_decode(trim($string));
    }

    // [[ ... ]]
    public static function format_brackets($string, $format = false)
    {
        $string = substr($string, 2, -2);
        if(preg_match("/^\s*image:(.*)\|(.*?)$/uims", $string, $arr))
        {
            $string = $arr[2];
        }elseif(preg_match("/^\s*w:(.*)\|(.*?)$/uims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif(preg_match("/^\s*category:/uims", $string, $arr))
        {
           $string = "";
        }elseif(preg_match("/^\s*(:category:)(.*)\|(.*?)$/uims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]$arr[2]'>$arr[3]</a>";
            else $string = $arr[3];
        }elseif(preg_match("/^\s*(:category:)(.*?)$/uims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]$arr[2]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif(preg_match("/^\s*(:[a-z]{2}:|user:)([^\|]*)$/uims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]$arr[2]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif(preg_match("/^\s*user:(.*)\|(.*?)$/uims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_USER_PREFIX."$arr[1]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif(preg_match("/^\s*cite/ums", $string, $arr))
        {
            $string = "";
        }elseif(preg_match("/^\s*http:\/\/[^ ]+$/uims", $string, $arr))
        {
            if($format) $string = "<a href='$string'>$string</a>";
            else $string = "";
        }elseif(preg_match("/^(.*?)\|(.*)$/ums", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif($format && preg_match("/^[^\s]+$/ums", $string, $arr))
        {
            $string = "<a href='".WIKI_PREFIX."$string'>$string</a>";
        }

        return $string;
    }

    public static function format_reference($string, $format = false)
    {
        $string = substr($string, 5, -6);

        if($format)
        {
            $string = " ";
        }else
        {
            $string = " ";
        }

        return $string;
    }

    // {{ ... }}
    public static function format_curly_brackets($string, $format = false, $pagename = false)
    {
        $string = trim(substr($string, 2, -2));

        if(preg_match("/^\s*([a-z]{2})\s*\|\s*(.*)$/uims", $string, $arr))
        {
            if($l = &$GLOBALS['iso_639_2_codes'][strtolower($arr[1])]) $language = $l;
            else $language = "Unknown language ($arr[1])";
            if($format) $string = "<b>$language:</b> $arr[2] ";
            else $string = "$language: ".$arr[2]." ";
        }

        if(preg_match("/^\s*(Multilingual description|mld\n|mld\||Translation table.*?\n)(.*)$/uims", $string, $arr))
        {
            $string = trim($arr[2]);
            if(preg_match_all("/(\|([a-z]{2})=)/uims", $string, $matches, PREG_SET_ORDER))
            {
                foreach($matches as $arr)
                {
                    if($l = &$GLOBALS['iso_639_2_codes'][strtolower($arr[2])]) $language = $l;
                    else $language = "Unknown language ($arr[2])";
                    if($format) $string = str_replace($arr[1], "<b>$language:</b> ", $string);
                    else $string = str_replace($arr[1], "$language: ", $string);
                }
                $string = str_replace("  ", " ", $string);
            }
        }

        if($string === "pagename" && $pagename)
        {
            $string = $pagename;
        }

        return $string;
    }

    public static function format_html_comment($string, $format = false)
    {
        if(!$format) $string = "";
        else $string = "";

        return $string;
    }

    public static function strip_comments($string)
    {
        $string = preg_replace("/<\!\-\-(.*?)\-\->/us", "", $string);
        return $string;
    }

    private static function replace_active_wikitext($string)
    {
        // allows us to replace contents of <nowiki> with content that will not be parsed
        static $search = array("[[", "]]", "{{", "}}", "''", "'''", "|");
        static $replace = array("&#91;&#91;", "&#93;&#93;", "&#123;&#123;", "&#125;&#125;", "&#39;&#39;", "&#39;&#39;&#39;", "&#124;");
        return str_replace($search, $replace, $string[1]);
    }

    public static function active_wikitext($string)
    {
        $string = self::strip_comments($string);
        $string = preg_replace_callback("/<nowiki>(.*?)<\/nowiki>/uis", "self::replace_active_wikitext", $string);
        return $string;
    }

    public static function balance_tags($open_tag, $close_tag, $text, $stream, $strip_tags = false)
    {
        $open_tag = preg_quote($open_tag, "/");
        $close_tag = preg_quote($close_tag, "/");
        $num_open = preg_match_all("/$open_tag/iums", $text, $arr);
        $num_close = preg_match_all("/$close_tag/iums", $text, $arr);

        $balance = false;
        if($num_close < $num_open) $balance = true;
        while($balance)
        {
            $balance = false;
            if(preg_match("/^(.*?$close_tag)(.*)$/iums", $stream, $arr))
            {
                list($text, $stream) = self::balance_tags($open_tag, $close_tag, $text.$arr[1], $arr[2]);
                $num_open = preg_match_all("/$open_tag/iums", $text, $arr);
                $num_close = preg_match_all("/$close_tag/iums", $text, $arr);
                if($num_close < $num_open) $balance = true;
            }
        }
        if($strip_tags && preg_match("/^.*?". $open_tag ."(.*)". $close_tag ."/ims", $text, $arr)) $text = $arr[1];
        return array($text, $stream);
    }

    public static function template_as_array($wikitext, $TemplateName = 'Information', $offset = 0)
    {
        // parses the first $TemplateName found. Use $offset to parse other identically named templates later in the text
        // returns empty array if no template found, otherwise template name is the first parameter.

        // Implementation: look for sections of a template separated by | characters. This is slightly complex,
        // because | characters don't count when nested inside other templates (a common occurrence), or when
        // nested inside [[ ]] braces, or in <!-- html comments --> or in "Parser Extension Tags" (for
        // a list of these, see e.g. http://commons.wikimedia.org/wiki/Special:Version#sv-parser-tags .

        //How about inside [ | ], as in http://commons.wikimedia.org/wiki/File:Hornwort structures.jpg ?

        // However, our job at parsing is made easier, because apart from {{ }}, the tags don't allow
        // multiple nesting, so <nowiki> $string </nowiki> is a bracketed pair even if $string = <nowiki>.

        // make sure <ref comes after <references
        static $singly_nested_tags = array(
            "<!--" => "-->",
            "[[" => "]]",
            "{{{" => "}}}",
            "<categorytree" => "</categorytree>",
            "<charinsert" => "</charinsert>" ,
            "<gallery" => "</gallery>",
            "<hiero" => "</hiero>",
            "<imagemap" => "</imagemap>",
            "<inputbox" => "</inputbox>",
            "<math" => "</math>",
            "<nowiki" => "</nowiki>",
            "<poem" => "</poem>",
            "<pre" => "</pre>",
            "<score" => "</score>",
            "<section" => "</section>",
            "<source" => "</source>",
            "<syntaxhighlight" => "</syntaxhighlight>",
            "<templatedata" => "</templatedata>",
            "<timeline" => "</timeline>",
            "<references" => "</references>",
            "<ref" => "</ref>" );
        static $parse_other = array("|", "=", "{{", "}}");
        static $search_RE = null;
        if(!isset($search_RE))
        {
            // setup search-replace array (once only)
            $match_strings = array_merge(array_keys($singly_nested_tags),$parse_other);
            // these will be concatenated as alternations in a RegExp, so make sure they are escaped
            array_walk($match_strings, function(&$val, $key) {$val = preg_quote($val, "/");});
            $search_RE = implode("|", $match_strings);

            function add_initial_xml_close(&$val, $key)
            {
                // this is an xml tag, so also look for a closing "/>" on the initial tag, e.g. <ref />
                // RE is not perfect - e.g. won't spot <ref name=">" />. You need a proper parser for that.
                if($val[0]==="<") $val = "\G[^>]+(?<=\/)>|".preg_quote($val, "/");
                else $val = preg_quote($val, "/");
            }
            array_walk($singly_nested_tags, 'add_initial_xml_close');
        }

        $template_params = array();
        if(preg_match("/\{\{\s*$TemplateName\s*[\|\}].*$/us", $wikitext, $arr, 0, $offset))
        {
            $wikitext = $arr[0];
            $nested_curly = 0;
            // skip the first {{
            $section_start = $curr_pos = 2;
            $first = true;
            while($matched = preg_match("/$search_RE/iu", $wikitext, $arr, PREG_OFFSET_CAPTURE, $curr_pos))
            {
                $match = $arr[0][0];
                $match_start = $arr[0][1];
                if($match === "|")
                {
                    if($nested_curly == 0)
                    {
                        $value = trim(substr($wikitext, $section_start, $match_start-$section_start));
                        // we have set the array key, but not filled it
                        // fill the last one with the right value
                        if(is_null(end($template_params))) $template_params[key($template_params)] = $value;
                        // add this as the next numeric key
                        else $template_params[] = $value;
                        $section_start = $match_start + 1;
                        $first = false;
                    }
                    $curr_pos = $match_start + 1;
                }elseif($match === "=")
                {
                    if($nested_curly == 0)
                    {
                        if($first || is_null(end($template_params)))
                        {
                            // ignore this "="
                        }else
                        {
                            // note that although some templates allow [Aa]ttribute = , parameter names are actually case sensitive
                            $param_name = trim(substr($wikitext, $section_start, $match_start-$section_start));
                            if(array_key_exists($param_name, $template_params)) unset($template_params[$param_name]);
                            $template_params[$param_name] = null;
                            $section_start = $match_start + 1;
                        }
                    }
                    $curr_pos = $match_start + 1;
                }elseif($match == "{{")
                {
                    $nested_curly++;
                    $curr_pos = $match_start + 2;
                }elseif($match === "}}")
                {
                    $nested_curly--;
                    if($nested_curly < 0) break;
                    $curr_pos = $match_start + 2;
                }else
                {
                    $curr_pos = $match_start + strlen($match);
                    // Found a tag: multiple nesting not allowed, so just jump to the next matching close tag
                    if(preg_match("/".$singly_nested_tags[strtolower($match)]."/uis", $wikitext, $arr, PREG_OFFSET_CAPTURE, $curr_pos))
                    {
                        $curr_pos = $arr[0][1]+strlen($arr[0][0]);
                    }else
                    {
                        // tag not closed. Uh oh.
                        print "Returning best-guess template parameters, even though tags aren't closed properly (couldn't find";
                        print " close of '$match': first 300 chars are ".strtr(substr($wikitext,0,300),"\r\n","| ").")\n";
                        // return the best so far
                        break;
                    }
                }
            }
            // fill the last value in the array
            if($matched)
            {
                $value = trim(substr($wikitext, $section_start, $match_start-$section_start));
            }else
            {
                $value = trim(substr($wikitext, $section_start));
                print "Returning best-guess template parameters, even though template not closed properly (couldn't find ";
                print " final '}}': first 300 chars are ".strtr(substr($wikitext,0,300),"\r\n","| ").")\n";
            }
            // we have set the array key, but not filled it
            if(is_null(end($template_params))) $template_params[key($template_params)] = $value;
            // add this as the next numeric key
            else $template_params[] = $value;
        }
        return $template_params;
    }
}

?>