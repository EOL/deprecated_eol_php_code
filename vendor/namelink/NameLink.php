<?php

class NameLink
{
    public static function markup_html_from_url($url, $change_relative_links = true)
    {
        $html = file_get_contents($url);
        if($change_relative_links) $html = self::replace_relative_paths($html, $url);
        
        return self::markup_html($html);
    }
    
    public static function replace_tags_with_collection($html, $callback)
    {
        if(!$callback) return $html;
        
        // check if the callback is a static function
        if(preg_match("/^(.*?)::(.*)$/", $callback, $arr))
        {
            // return the html if the static function doesn't exist
            if(!method_exists($arr[1], $arr[2])) return $html;
        }elseif(!function_exists($callback)) return $html;
        
        if(preg_match_all("/<namelink class=\"(.*?)\" rel=\"(.*?)\" id=\"(.*?)\" found=\"(.*?)\" predicted=\"(.*?)\">(.*?)<\/namelink>/", $html, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $arr)
            {
                $class = trim($arr[1]);
                $rel = trim($arr[2]);
                $id = trim($arr[3]);
                $found = trim($arr[4]);
                $predicted = trim($arr[5]);
                $text = trim($arr[6]);
                
                //echo "$id: $predicted: $found<br>\n";
                
                //echo "$predicted<br>\n";
                $return = call_user_func($callback, $predicted);
                $first_link = @$return[0];
                if($first_link)
                {
                    $html = str_replace($arr[0], '<a href="'.$first_link['url'].'">'.$found.'</a>', $html);
                }else $html = str_replace($arr[0], $found, $html);
                //echo "$return<br>\n";
            }
        }
        
        return $html;
    }
    
    public static function wrap_tags($html, $start_tag, $end_tag)
    {
        if(!$start_tag || !$end_tag) return $html;
        
        $html = preg_replace("/(<namelink.*?>)/ims", "\\1" . $start_tag, $html);
        $html = preg_replace("/(<\/namelink>)/ims", $end_tag . "\\1", $html);
        
        return $html;
    }
    
    
    
    
    
    
    
    
    
    
    
    public static function add_to_head($html, $to_include)
    {
        // #TODO
        if(preg_match("/(<head>)/i", $html, $arr))
        {
            $html = str_replace($arr[1], "<head>". $to_include . "\n", $html);
        }else $html = "<head>". $to_include ."\n</head>\n" . $html;
        
        return $html;
    }
    
    public static function add_to_end($html, $to_include)
    {
        // #TODO
        if(preg_match("/^(.*)(<\/body>.*?)$/i", $html, $arr))
        {
            $html = $arr[1] . $to_include . $arr[2];
        }elseif(preg_match("/^(.*)(<\/html>.*?)$/i", $html, $arr))
        {
            $html = $arr[1] . $to_include . $arr[2];
        }else $html = $html . $to_include;
        
        return $html;
    }
    
    public static function replace_relative_paths($html, $url)
    {
        $domain = self::get_domain_prefix($url);
        $base_url = self::get_base_url($url);
        
        $to_include = '<base href="'. $base_url .'/">';
        
        if(preg_match("/(<head>)/i", $html, $arr))
        {
            $html = str_replace($arr[1], "<head>". $to_include . "\n", $html);
        }else $html = "<head>". $to_include ."\n</head>\n" . $html;
        
        if(preg_match_all("/ ((href|src|action|background)=(['\"])(.*?)\\3)/", $html, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $arr)
            {
                $path = $arr[4];
                $absolute_path = self::make_absolute_path($path, $domain, $base_url);
                
                $replace = $arr[2] . "=" . $arr[3] . $absolute_path . $arr[3];
                
                $html = str_replace($arr[1], $replace, $html);
            }
        }
        
        return $html;
    }
    
    public function make_absolute_path($path, $domain, $base_url)
    {
        if(preg_match("/^[a-z]+:\/\//i", $path)) return $path;
        
        if(preg_match("/^\//", $path)) return $domain . $path;
        
        return $base_url . "/" . $path;  
    }
    
    public function get_domain_prefix($url)
    {
        // from http://www.apsnet.org/online/common/names/cucurbit.asp
        // makes http://www.apsnet.org
        
        if(preg_match("/^(.*:\/\/[^\/]+)/", $url, $arr))
        {
            return $arr[1];
        }
        
        return false;
    }
    
    public function get_base_url($url)
    {
        // from http://www.apsnet.org/online/common/names/cucurbit.asp
        // makes http://www.apsnet.org/online/common/names
        
        if(preg_match("/^(.*\/[^.\/]+)$/", $url, $arr))
        {
            return $arr[1];
        }
        
        if(preg_match("/^(.*)\//", $url, $arr))
        {
            return $arr[1];
        }
        
        return false;
    }
}


?>