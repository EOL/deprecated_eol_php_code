<?php

class WikiParser
{
    public static function strip_syntax($string, $format = false, $pagename = false)
    {
        if($format == false)
        {
            $string = str_replace("&nbsp;", " ", $string);
        }
        $string = htmlspecialchars_decode(html_entity_decode($string));
        $string = htmlspecialchars_decode(html_entity_decode($string));
        
        // [[ ... ]]
        while(preg_match("/(\[\[.*?\]\])(.*)$/ms", $string, $arr))
        {
            $match = $arr[1];
            list($match, $junk) = self::balance_tags("[[", "]]", $match, $arr[2]);
            $replacement = self::format_brackets($match, $format);
            $string = preg_replace("/".preg_quote($match, "/")."/", $replacement, $string);
        }
        
        // [http://... The text to link to]
        while(preg_match("/(\[\s*(https?:\/\/[^ ]+) (.*?)\])(.*)$/ims", $string, $arr))
        {
            $match = $arr[1];
            if($format) $string = preg_replace("/".preg_quote($match, "/")."/", "<a href='$arr[2]'>$arr[3]</a>", $string);
            else $string = preg_replace("/".preg_quote($match, "/")."/", $arr[3], $string);
        }
        
        // [http://...]
        while(preg_match("/(\[\s*(https?:\/\/[^ ]+)\])(.*)$/ims", $string, $arr))
        {
            $match = $arr[1];
            if($format) $string = preg_replace("/".preg_quote($match, "/")."/", "<a href='$arr[2]'>$arr[2]</a>", $string);
            else $string = preg_replace("/".preg_quote($match, "/")."/", "", $string);
        }
        
        // {{ ... }}
        while(preg_match("/(\{\{.*?\}\})(.*)/ms", $string, $arr))
        {
            $match = $arr[1];
            list($match, $junk) = self::balance_tags("{{", "}}", $match, $arr[2]);
            $replacement = self::format_curly_brackets($match, $format, $pagename);
            $string = preg_replace("/".preg_quote($match, "/")."/", $replacement, $string);
        }
        
        // <ref... />
        while(preg_match("/(<ref[^>]*\/>)(.*)/ims", $string, $arr))
        {
            $match = $arr[1];
            $string = preg_replace("/".preg_quote($match, "/")."/", "", $string);
        }
        
        // <ref...> ... </ref>
        while(preg_match("/(<ref[^>]*>.*?<\/ref>)(.*)/ims", $string, $arr))
        {
            $match = $arr[1];
            list($match, $junk) = self::balance_tags("<ref", "</ref>", $match, $arr[2]);
            $replacement = self::format_reference($match, $format);
            $string = preg_replace("/".preg_quote($match, "/")."/", $replacement, $string);
        }
        
        // <!-- ... -->
        while(preg_match("/(<\!--.*?-->)(.*)/ms", $string, $arr))
        {
            $match = $arr[1];
            list($match, $junk) = self::balance_tags("<!--", "-->", $match, $arr[2]);
            $replacement = self::format_html_comment($match, $format);
            $string = preg_replace("/".preg_quote($match, "/")."/", $replacement, $string);
        }
        
        
        
        if($format) $string = preg_replace("/'''(.*?)'''/", "<b>\\1</b>", $string);
        else $string = preg_replace("/'''(.*?)'''/", "\\1", $string);
        $string = preg_replace("/'''/", "", $string); //kill off any remaining unmatched ''' to avoid misinterpretting ''''' as '' '' '
        
        if($format) $string = preg_replace("/''(.*?)''/", "<i>\\1</i>", $string);
        else $string = preg_replace("/''(.*?)''/", "\\1", $string);
        
        if($format) $string = preg_replace("/====(.*?)====/", "<h4>\\1</h4>", $string);
        else $string = preg_replace("/====(.*?)====/", "\\1", $string);
        
        if($format) $string = preg_replace("/===(.*?)===/", "<h3>\\1</h3>", $string);
        else $string = preg_replace("/===(.*?)===/", "\\1", $string);
        
        if($format) $string = preg_replace("/==(.*?)==/", "<h2>\\1</h2>", $string);
        else $string = preg_replace("/==(.*?)==/", "\\1", $string);
        
        return htmlspecialchars_decode(trim($string));
    }
    
    // [[ ... ]]
    public static function format_brackets($string, $format = false)
    {
        $string = substr($string, 2, -2);
        if(preg_match("/^\s*image:(.*)\|(.*?)$/ims", $string, $arr))
        {
            //$string = self::strip_syntax($arr[2]);
            $string = $arr[2];
        }elseif(preg_match("/^\s*w:(.*)\|(.*?)$/ims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif(preg_match("/^\s*(:category:)(.*)\|(.*?)$/ims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]$arr[2]'>$arr[3]</a>";
            else $string = $arr[3];
        }elseif(preg_match("/^\s*(:category:)(.*?)$/ims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]$arr[2]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif(preg_match("/^\s*(:[a-z]{2}:|user:)([^\|]*)$/ims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]$arr[2]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif(preg_match("/^\s*user:(.*)\|(.*?)$/ims", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_USER_PREFIX."$arr[1]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif(preg_match("/^\s*cite/ms", $string, $arr))
        {
            $string = "";
        }elseif(preg_match("/^\s*http:\/\/[^ ]+$/ms", $string, $arr))
        {
            if($format) $string = "<a href='$string'>$string</a>";
            else $string = "";
        }elseif(preg_match("/^(.*?)\|(.*)$/ms", $string, $arr))
        {
            if($format) $string = "<a href='".WIKI_PREFIX."$arr[1]'>$arr[2]</a>";
            else $string = $arr[2];
        }elseif($format && preg_match("/^[^\s]+$/ms", $string, $arr))
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
        
        if(preg_match("/^\s*([a-z]{2})\s*\|\s*(.*)$/ims", $string, $arr))
        {
            if($l = &$GLOBALS['iso_639_2_codes'][$arr[1]]) $language = $l;
            else $language = "Unknown language ($arr[1])";
            if($format) $string = "<b>$language:</b> $arr[2] ";
            else $string = "language: ".$arr[2]." ";
        }
        
        if($string == "pagename" && $pagename)
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
    
    public static function strip_tags($string)
    {
        $string = preg_replace("/<(.*?)>(.*?)<\/\\1>/u", "\\2", $string);
        
        return $string;
    }
    
    public static function strip_comments($string)
    {
        $string = preg_replace("/<\!\-\-(.*?)\-\->/ui", "", $string);
        return $string;
    }

    private static function replace_active_wikitext($string) 
    {   //allows us to replace contents of <nowiki> with content that will not be parsed
        static $search=array("[[", "]]", "{{", "}}", "''", "'''");
        static $replace=array("&#91;&#91;", "&#93;&#93;", "&#123;&#123;", "&#125;&#125;", "&#39;&#39;", "&#39;&#39;&#39;");
        return str_replace($search, $replace, $string[1]);
    }

    public static function active_wikitext($string)
    {
        $string = self::strip_comments($string);
        $string = preg_replace_callback("/<nowiki>(.*?)<\/nowiki>/i", "self::replace_active_wikitext", $string);
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
            //echo "$num_close :: $num_open<br>";
            $balance = false;
            if(preg_match("/^(.*?$close_tag)(.*)$/iums", $stream, $arr))
            {
                list($text, $stream) = self::balance_tags($open_tag, $close_tag, $text.$arr[1], $arr[2]);
                
                $num_open = preg_match_all("/$open_tag/iums", $text, $arr);
                $num_close = preg_match_all("/$close_tag/iums", $text, $arr);
                if($num_close < $num_open) $balance = true;
            }
        }
        
        if($strip_tags)
        {
            //echo "/^.*?". $open_tag ."(.*)". $close_tag ."/ims<br>";
            if(preg_match("/^.*?". $open_tag ."(.*)". $close_tag ."/ims", $text, $arr)) $text = $arr[1];
            //echo $text;
        }
        
        return array($text, $stream);
    }
}

?>