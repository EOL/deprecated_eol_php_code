<?php
namespace php_active_record;

class Functions
{
    // the first two functions are from http://us3.php.net/manual/en/function.utf8-encode.php#85866
    // Returns true if $string is valid UTF-8 and false otherwise.
    public static function is_utf8($string)
    {
        // From http://w3.org/International/questions/qa-forms-utf-8.html
        return preg_match('%^(?:
              [\x09\x0A\x0D\x20-\x7E]            # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
            |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )*$%xs', $string);
    }
    
    public static function is_ascii($string)
    {
        return preg_match('%^(?:
              [\x09\x0A\x0D\x20-\x7E]            # ASCII
        )*$%xs', $string);
    }
    
    public function to_camel_case($str)
    {
        $str = str_replace('_', ' ', $str);
        $str = ucwords($str);
        $str = str_replace(' ', '', $str);
        return $str;
    }
    
    public static function array_to_xml($array, $prefix = "  ")
    {
        $xml = "";
        
        foreach($array as $key => $val)
        {
            if(is_int($key)) $key = "value";

            if(is_array($val))
            {
                $xml = $prefix."<".$key.">\n";
                $xml .= self::array_to_xml($val, $prefix."  ");
                $xml .= $prefix."</".$key.">\n";
            }else
            {
                $xml .= $prefix."<".$key.">".htmlspecialchars($val)."</".$key.">\n";
            }
        }
        
        return $xml;
    }
    
    // public static function display($text)
    // {
    //     if(DEBUG_TO_FILE) fwrite($GLOBALS['debug_file'], str_pad(self::time_elapsed(), 12, " ", STR_PAD_LEFT)." -> ". $text."\n");
    //     else
    //     {
    //         echo "$text<br>\n";
    //         flush();
    //     }
    // }
    
    // public static function mysql_debug($string)
    // {
    //     if(defined('MYSQL_DEBUG') && MYSQL_DEBUG)
    //     {
    //         self::display($string." :: [".self::get_last_function(4)."]");
    //         //self::display("Stacktrace: ".self::stacktrace());
    //     }
    // }
    
    // public static function debug($string)
    // {
    //     if(defined('DEBUG') && DEBUG)
    //     {
    //         self::display($string." :: [".self::get_last_function(3)."]");
    //         //self::display("Stacktrace: ".self::stacktrace());
    //     }
    // }
    
    public static function log($string)
    {
        if(isset($GLOBALS['log_file']) && $GLOBALS['ENV_NAME'] != 'test')
        {
            fwrite($GLOBALS['log_file'], date('H:i:s m.d.Y').": $string\n");
        }
    }
    
    public static function file_hours_since_modified($path)
    {
        if(!file_exists($path)) return false;
        
        $stat = stat($path);
        $hours = ceil(((time() - $stat['mtime']) / 60) / 60);
        
        return $hours;
    }
    
    public static function grep_processlist($string)
    {
        $count = 0;
        @exec('ps -ef', $ps);
        foreach($ps as $process)
        {
            if(preg_match("/".preg_quote($string, '/')."/", $process)) $count++;
        }
        return $count;
    }
    
    public static function get_remote_file($remote_url, $download_wait_time = DOWNLOAD_WAIT_TIME, $timeout = DOWNLOAD_TIMEOUT_SECONDS)
    {
        debug("Grabbing $remote_url: attempt 1");
        
        $context = stream_context_create(array('http' => array('timeout' => $timeout)));
        
        $file = @self::fake_user_agent_http_get($remote_url, $timeout);
        usleep($download_wait_time);
        
        $attempts = 1;
        while(!$file && $attempts < DOWNLOAD_ATTEMPTS)
        {
            debug("Grabbing $remote_url: attempt ".($attempts+1));
            
            $file = @self::fake_user_agent_http_get($remote_url, $timeout);
            usleep($download_wait_time);
            $attempts++;
        }
        
        if($attempts >= DOWNLOAD_ATTEMPTS)
        {
            debug("failed download file");
            return false;
        }
        
        unset($context);
        debug("received file");
        
        return $file;
    }
    
    public static function get_remote_file_fake_browser($remote_url, $download_wait_time = DOWNLOAD_WAIT_TIME)
    {
        debug("Grabbing $remote_url: attempt 1: waiting $download_wait_time");
        
        $file = @self::fake_user_agent_http_get($remote_url);
        usleep($download_wait_time);
        
        $attempts = 1;
        while(!$file && $attempts < DOWNLOAD_ATTEMPTS)
        {
            debug("Grabbing $remote_url: attempt ".($attempts+1));
            
            $file = @self::fake_user_agent_http_get($remote_url);
            usleep($download_wait_time);
            $attempts++;
        }
        debug("received file");
        
        return $file;
    }
    
    public static function get_hashed_response($url, $download_wait_time = DOWNLOAD_WAIT_TIME)
    {
        $response = self::get_remote_file($url, $download_wait_time);
        
        $hash = simplexml_load_string($response);
        
        return $hash;
    }
    
    public static function get_hashed_response_fake_browser($url, $download_wait_time = DOWNLOAD_WAIT_TIME)
    {
        $response = self::get_remote_file_fake_browser($url, $download_wait_time);
        
        $hash = simplexml_load_string($response);
        
        return $hash;
    }
    
    
    // see http://www.php.net/manual/en/function.filesize.php#92462
    public static function remote_file_size($uri)
    {
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data === false) return null;
        
        $content_length = null;
        if(preg_match('/Content-Length: (\d+)/', $data, $matches))
        {
            $content_length = ((int) $matches[1]) / 1024;
        }
        
        return $content_length;
    }
    
    public static function ping($uri)
    {
        return (self::remote_file_size($uri) !== null);
    }
    
    // public static function temp_filepath($relative_from_root = false, $extension = 'file')
    // {
    //     if($relative_from_root) $prefix = "";
    //     else $prefix = DOC_ROOT;
    //     
    //     $filepath = $prefix ."temp/tmp_". self::random_digits(5) .".$extension";
    //     while(glob($filepath))
    //     {
    //         $filepath = $prefix ."temp/tmp_". self::random_digits(5) .".$extension";
    //     }
    //     
    //     return $filepath;
    // }
    
    // public function random_digits($number, $start = 0)
    // {
    //     $start = "1".str_repeat($start, $number);
    //     $end = "1".str_repeat(9, $number);
    //     $random = rand($start,$end);
    //     return substr($random, 1);
    // }
    
    public static function curl_post_request($url, $parameters_array = array())
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if(isset($parameters_array) && is_array($parameters_array)) curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters_array);
        
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        
        debug("Sending post request to $url with params ".print_r($parameters_array, 1).": only attempt");
        $result = curl_exec($ch);
        
        if(0 == curl_errno($ch))
        {
            curl_close($ch);
            return $result;
        }
        echo "Curl error ($url): " . curl_error($ch);
        return false;
    }
    
    public static function fake_user_agent_http_get($url, $timeout = 50)
    {
        if(substr($url, 0, 1) == "/") $url = "file://" . $url;
        
        // $agents[] = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; Media Center PC 5.0)";
        // $agents[] = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)";
        // $agents[] = "Opera/9.63 (Windows NT 6.0; U; ru) Presto/2.1.1";
        // $agents[] = "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5";
        // $agents[] = "Mozilla/5.0 (X11; U; Linux i686 (x86_64); en-US; rv:1.8.1.18) Gecko/20081203 Firefox/2.0.0.18";
        // $agents[] = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.16) Gecko/20080702 Firefox/2.0.0.16";
        $agents[] = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1";
        //$agent = $agents[rand(0,(count($agents)-1))];
        $agent = $agents[0];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        
        // ignores and just trusts https
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        debug("Sending get request to $url : only attempt");
        $result = curl_exec($ch);
        
        if(0 == curl_errno($ch))
        {
            curl_close($ch);
            return $result;
        }
        echo "Curl error ($url): " . curl_error($ch);
        return false;
    }
    
    
    public static function cmp_hierarchy_entries($a, $b)
    {
        if ($a->name()->string == $b->name()->string) return 0;
        return ($a->name()->string < $b->name()->string) ? -1 : 1;
    }
    
    public static function cmp_references($a, $b)
    {
        if ($a->fullReference == $b->fullReference) return 0;
        
        return ($a->fullReference < $b->fullReference) ? -1 : 1;
    }
    
    public static function cmp_references_arkive($a, $b)
    {
        if ($a->fullReference == $b->fullReference) return 0;
        
        $match1 = $a->fullReference;
        $match2 = $b->fullReference;
        
        if(preg_match("/^([0-9]+)/",$a->fullReference,$arr)) $match1 = $arr[1];
        if(preg_match("/^([0-9]+)/",$b->fullReference,$arr)) $match2 = $arr[1];
        
        return ($match1 < $match2) ? -1 : 1;
    }
    
    public static function cmp_nodes($a, $b)
    {
        if ($a->name == $b->name) return 0;
        
        return ($a->name < $b->name) ? -1 : 1;
    }
    
    public static function canonical_form($string)
    {
        self::sci_parts();
        self::author_parts();
        self::junk_parts();
        
        if(preg_match("/^X (.*)$/i",$string,$arr)) $string = $arr[1];
        $string = str_ireplace(" tipo veneto","",$string);
        $string = str_replace("×"," ",$string);
        $string = preg_replace("/[[:space:]]+/", " ", trim($string));
        $string = preg_replace("/[[:space:]]/", " ", trim($string));
        $words = explode(" ", trim($string));
        $num = count($words);
        if(preg_match("/^\??\"?\[?\(?([^\"\[\]\(\)]*)\)?\]?\"?$/",$words[0],$arr)) $words[0] = $arr[1];
        if(preg_match("/^(.*)\?$/",$words[0],$arr)) $words[0] = $arr[1];
        if($words[0]=="Not") return "";
        $words[0] = str_replace("[","",$words[0]);
        $words[0] = str_replace("]","",$words[0]);
        $words[0] = preg_replace("/{\?}/","",$words[0]);
        $words[0] = preg_replace("/\{[0-9\. ]*\}/","",$words[0]);
        if(preg_match("/^[^".UPPER.LOWER."]*([".UPPER.LOWER."]*)[^".UPPER.LOWER."]*$/u",$words[0],$arr)) $words[0] = $arr[1];
        $words[0] = str_replace("[","",$words[0]);
        $words[0] = str_replace("]","",$words[0]);
        $return_string = $words[0];
        if(@preg_match("/^([".LOWER."].*)\)$/u",$words[1],$arr))
        {
            $words[1] = $arr[1];
            if(preg_match("/^(.*)\?$/",$words[1],$arr)) $words[1] = $arr[1];
            if(preg_match("/^[^".UPPER.LOWER."]*([".UPPER.LOWER."]*)[^".UPPER.LOWER."]*$/u",$words[1],$arr)) $words[1] = $arr[1];
            $return_string.=" $words[1]";
            return $return_string;
        }
        
        for($i=1 ; $i<$num ; $i++)
        {
            if(preg_match("/^[".UPPER."\(]/u",$words[$i]) || (preg_match("/[0-9]/",$words[$i])&&!preg_match("/^[1-2]?[0-9]?\-?[".LOWER."]+$/u",$words[$i]))) continue;
            if(preg_match("/^[^0-9".UPPER.LOWER."]*([0-9".UPPER.LOWER."]*)[^0-9".UPPER.LOWER."]*$/u",$words[$i],$arr)) $words[$i] = $arr[1];
            if(preg_match("/[".UPPER."]/u",$words[$i])||preg_match("/\[/u",$words[$i])||
            preg_match("/.\../u",$words[$i])||!preg_match("/[[:alpha:]]/u",$words[$i])) continue;
            
            if(@$GLOBALS["SCI_PARTS"][$words[$i]] || @$GLOBALS["AUTHOR_PARTS"][$words[$i]] || @$GLOBALS["JUNK_PARTS"][$words[$i]]) continue;
            
            if(strlen($words[$i])>1) $return_string.=" $words[$i]";
        }
        
        unset($string);
        unset($words);
        return trim($return_string);
    }
    
    public static function ranked_canonical_form($string)
    {
        require_library('RubyNameParserClient');
        if(!isset($GLOBALS['NAME_PARSER_CLIENT'])) $GLOBALS['NAME_PARSER_CLIENT'] = new RubyNameParserClient();
        return $GLOBALS['NAME_PARSER_CLIENT']->lookup_string($string);
    }
    
    public static function italicized_form($string)
    {
        $canonical_form = self::canonical_form($string);
        
        $words = explode(" ",$canonical_form);
        
        foreach($words as $w)
        {
            $string = preg_replace("/(^|[^0-9".UPPER.LOWER."])".preg_quote($w, "/")."([^0-9".UPPER.LOWER."]|$)/","\\1|-n-|".$w."|-/n-|\\2",$string);
        }
        unset($words);
        
        while(preg_match("/\|-\/n-\| \|-n-\|/",$string,$arr)) $string = str_replace("|-/n-| |-n-|"," ",$string);
        
        $string = str_replace("|-n-|","<i>",$string);
        $string = str_replace("|-/n-|","</i>",$string);
        $string = str_replace("<i><i>", "<i>", $string);
        $string = str_replace("</i></i>", "</i>", $string);
        
        unset($canonical_form);
        return $string;
    }
    
    public static function class_name($path)
    {
        if(SYSTEM_OS == "Windows")
        {
            if(preg_match("/\\\([^\\\]+)\.php$/", $path, $arr)) $path = $arr[1];
        }elseif(preg_match("/\/([^\/]+)\.php$/", $path, $arr)) $path = $arr[1];
        return strtolower(preg_replace("/([a-z])([A-Z])/", "\\1_".strtolower("\\2"), $path));
    }
    
    public static function generate_guid()
    {
        return md5(uniqid("eol".rand(), true));
    }
    
    public static function mock_object($class, $params)
    {
        $mock_object = new $class(false);
        foreach($params as $k => $v)
        {
            $mock_object->$k = $v;
        }
        unset($params);
        
        return $mock_object;
    }
    
    public static function get_last_function($index)
    {
        if(!$index) $index = 2;
        $backtrace = debug_backtrace();
        return @$backtrace[$index]['class'].": ".@$backtrace[$index]['function'];
    }
    
    public static function stacktrace()
    {
        return "<pre>".print_r(debug_backtrace(), 1)."</pre>";
    }
    
    
    
    public static function common_names_are_same($cn1, $cn2)
    {
        if(!$cn1 && !$cn2) return true;
        if(!$cn1 || !$cn2) return false;
        
        $common_names_1 = array();
        foreach($cn1 as $k) 
        {
            $common_names_1[] = $k->common_name."|".$k->language_id;
        }
        
        $common_names_2 = array();
        foreach($cn2 as $k)
        {
            $common_names_2[] = $k->common_name."|".$k->language_id;
        }
        
        if (array_diff($common_names_1, $common_names_2)) return false;
        return true;
    }
    
    public static function references_are_same($refs1, $refs2)
    {
        if(!$refs1 && !$refs2) return true;
        if(!$refs1 || !$refs2) return false;
        
        $references_1 = array();
        foreach($refs1 as $k)
        {
            $references_1[] = $k->id;
        }
        
        $references_2 = array();
        foreach($refs2 as $k)
        {
            $references_2[] = $k->id;
        }
        
        if (array_diff($references_1, $references_2)) return false;
        return true;
    }
    
    public static function agents_are_same($agents1, $agents2)
    {
        $agents_1 = array();
        foreach($agents1 as $k)
        {
            $agents_1[] = $k->full_name;
        }
        
        $agents_2 = array();
        foreach($agents2 as $k)
        {
            $agents_2[] = $k->full_name;
        }
        
        if (array_diff($agents_1, $agents_2)) return false;
        return true;
    }

    public static function create_fixture($table)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $return = "";
        
        $i = 1;
        $result = $mysqli->query("SELECT * FROM $table");
        while($result && $row=$result->fetch_assoc())
        {
            $return .= $table."_$i:\n";
            foreach($row as $k => $v)
            {
                if($v != '') $return .= "    $k: $v\n";
            }
            $return .= "\n";
            
            $i++;
        }
        
        return $return;
    }

    public static function load_fixtures($environment = "test")
    {
        if($GLOBALS['ENV_NAME']!=$environment) return false;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $files = Functions::get_fixture_files();
        
        $fixture_data = (object) array();
        
        $mysqli->begin_transaction();
        foreach($files as $table)
        {
            $fixture_data->$table = (object) array();
            
            $rows = Spyc::YAMLLoad(DOC_ROOT."tests/fixtures/$table.yml");
            foreach($rows as $id => $row)
            {
                $fixture_data->$table->$id = (object) array();
                
                $query = "INSERT INTO $table (`";
                $query .= implode("`, `", array_keys($row));
                $query .= "`) VALUES ('";
                $query .= implode("', '", $row);
                $query .= "')";
                
                $mysqli->insert($query);
                
                foreach($row as $k => $v)
                {
                    $fixture_data->$table->$id->$k = $v;
                }
            }
        }
        $mysqli->end_transaction();
        
        return $fixture_data;
    }
    
    public static function get_files_in_dir($dir)
    {
        $files = array();

        if($handle = opendir($dir))
        {
           while(false !== ($file = readdir($handle)))
           {
               if(substr($file, 0, 1) != ".")
               {
                   $files[] = $file;
               }
           }
           closedir($handle);
        }

        return $files;
    }
    
    public static function get_fixture_files()
    {
        $files = array();
        
        $dir = DOC_ROOT."tests/fixtures/";
        $files_in_dir = self::get_files_in_dir($dir);
        foreach($files_in_dir as $file)
        {
           if(preg_match("/^(.*)\.yml$/",trim($file), $arr))
           {
               $files[] = $arr[1];
           }
        }
        
        return $files;
    }
    
    public static function require_module($module)
    {
        $module_path = DOC_ROOT . "classes/modules/$module/module.php";
        require_once($module_path);
    }
    
    public static function require_classes_from_dir($dir, $recursive = false)
    {
        if($handle = opendir($dir))
        {
            while(false !== ($file = readdir($handle)))
            {
                if($file != "." && $file != "..")
                {
                    if(preg_match("/\.php$/", trim($file))) require_once($dir . $file);
                    elseif($recursive && !preg_match("/\./", $file) && $file != "modules" && $file != "TDWG") self::require_classes_from_dir($dir . $file . "/", true);
                }
            }
            closedir($handle);
        }
    }
    
    public static function import_decode($string, $remove_shitespace = false, $decode = true)
    {
        if($decode)
        {
            $string = str_replace('&nbsp;',  ' ', $string);
            $string = htmlspecialchars_decode(html_entity_decode($string, ENT_COMPAT, 'UTF-8'));
        }
        
        $string = str_replace(" ", " ", $string);
        //utf-8 0x0A (nobreak space) does not get inserted into mysql properly, we change it back to &nbsp; 
        //$string = str_replace("\xA0", "&nbsp;", $string);
        $string = str_replace("\x0A", "&nbsp;", $string);
        
        if($remove_shitespace) $string = self::remove_whitespace($string);
        return trim($string);
    }
    
    public static function remove_whitespace($string)
    {
        while(preg_match("/  /",$string)) $string = trim(str_replace("  "," ",$string));
        return trim($string);
    }
    
    // from http://www.php.net/manual/en/function.in-array.php#89256
    public static function array_searchi($needle, $haystack)
    {
        if(!is_array($haystack)) return null;
        $needle = strtolower($needle);
        foreach($haystack as $key => $value)
        {
            if(strtolower($value) == $needle) return $key;
        }
        return null;
    }
    
    public static function utf8_to_ascii($nameString)
    {
        // source code at http://us3.php.net/manual/en/function.iconv.php#93609
        
        $r = '';
        $s1 = @iconv('UTF-8', 'ASCII//TRANSLIT', $nameString);
        $j = 0;
        for ($i = 0; $i < strlen($s1); $i++) {
            $ch1 = $s1[$i];
            $ch2 = substr($nameString, $j++, 1);
            if (strstr('`^~\'"', $ch1) !== false) {
                if ($ch1 <> $ch2) {
                    --$j;
                    continue;
                }
            }
            $r .= ($ch1=='?') ? $ch2 : $ch1;
        }
        return $r;
        
        /*
        $nameString = preg_replace("/[ÀÂÅÃÄÁẤẠ]/u", "A", $nameString);
        $nameString = preg_replace("/[ÉÈÊË]/u", "E", $nameString);
        $nameString = preg_replace("/[ÍÌÎÏ]/u", "I", $nameString);
        $nameString = preg_replace("/[ÓÒÔØÕÖỚỔ]/u", "O", $nameString);
        $nameString = preg_replace("/[ÚÙÛÜ]/u", "U", $nameString);
        $nameString = preg_replace("/[Ý]/u", "Y", $nameString);
        $nameString = preg_replace("/Æ/u", "AE", $nameString);
        $nameString = preg_replace("/[ČÇ]/u", "C", $nameString);
        $nameString = preg_replace("/[ŠŞ]/u", "S", $nameString);
        $nameString = preg_replace("/[Đ]/u", "D", $nameString);
        $nameString = preg_replace("/Ž/u", "Z", $nameString);
        $nameString = preg_replace("/Ñ/u", "N", $nameString);
        $nameString = preg_replace("/Œ/u", "OE", $nameString);
        $nameString = preg_replace("/ß/u", "B", $nameString);
        $nameString = preg_replace("/Ķ/u", "K", $nameString);
        $nameString = preg_replace("/[áàâåãäăãắảạậầằ]/u", "a", $nameString);
        $nameString = preg_replace("/[éèêëĕěếệểễềẻ]/u", "e", $nameString);
        $nameString = preg_replace("/[íìîïǐĭīĩỉï]/u", "i", $nameString);
        $nameString = preg_replace("/[óòôøõöŏỏỗộơọỡốơồờớổ]/u", "o", $nameString);
        $nameString = preg_replace("/[úùûüůưừựủứụ]/u", "u", $nameString);
        $nameString = preg_replace("/[žź]/u", "z", $nameString);
        
        $nameString = preg_replace("/[đ]/u", "d", $nameString);
        $nameString = preg_replace("/æ/u", "ae", $nameString);
        $nameString = preg_replace("/[čćç]/u", "c", $nameString);
        $nameString = preg_replace("/[ñńň]/u", "n", $nameString);
        $nameString = preg_replace("/œ/u", "oe", $nameString);
        $nameString = preg_replace("/[śšş]/u", "s", $nameString);
        $nameString = preg_replace("/ř/u", "r", $nameString);
        $nameString = preg_replace("/ğ/u", "g", $nameString);
        $nameString = preg_replace("/Ř/u", "R", $nameString);
        return $nameString;
        */
    }
    
    public static function clean_name($name)
    {
        $name = str_replace("."," ",$name);
        $name = str_replace(","," ",$name);
        $name = str_replace(";"," ",$name);
        $name = str_replace("-"," - ",$name);
        $name = str_replace("("," ( ",$name);
        $name = str_replace(")"," ) ",$name);
        $name = str_replace("["," [ ",$name);
        $name = str_replace("]"," ] ",$name);
        $name = str_replace("{"," { ",$name);
        $name = str_replace("}"," } ",$name);
        $name = str_replace(":"," : ",$name);
        $name = str_replace("&"," & ",$name);
        $name = str_replace("?"," ? ",$name);
        $name = str_replace("*"," * ",$name);
        $name = str_replace("×"," × ",$name);
        $name = str_replace(" and "," & ",$name);
        $name = str_replace(" et "," & ",$name);
        while(preg_match("/  /",$name)) $name = str_replace("  "," ",$name);
        $name = preg_replace("/([A-Z])/e","''.strtolower('\\1').''",$name);
        
        $name = str_replace("À","à",$name);
        $name = str_replace("Â","â",$name);
        $name = str_replace("Å","å",$name);
        $name = str_replace("Ã","ã",$name);
        $name = str_replace("Ä","ä",$name);
        $name = str_replace("Á","á",$name);
        $name = str_replace("Æ","æ",$name);
        $name = str_replace("C","c",$name);
        $name = str_replace("Ç","ç",$name);
        $name = str_replace("Č","č",$name);
        $name = str_replace("É","é",$name);
        $name = str_replace("È","è",$name);
        $name = str_replace("Ë","ë",$name);
        $name = str_replace("Í","í",$name);
        $name = str_replace("Ì","ì",$name);
        $name = str_replace("Ï","ï",$name);
        $name = str_replace("Ň","ň",$name);
        $name = str_replace("Ñ","ñ",$name);
        $name = str_replace("Ñ","ñ",$name);
        $name = str_replace("Ó","ó",$name);
        $name = str_replace("Ò","ò",$name);
        $name = str_replace("Ô","ô",$name);
        $name = str_replace("Ø","ø",$name);
        $name = str_replace("Õ","õ",$name);
        $name = str_replace("Ö","ö",$name);
        $name = str_replace("Ú","ú",$name);
        $name = str_replace("Ù","ù",$name);
        $name = str_replace("Ü","ü",$name);
        $name = str_replace("R","r",$name);
        $name = str_replace("Ŕ","ŕ",$name);
        $name = str_replace("Ř","ř",$name);
        $name = str_replace("Ŗ","ŗ",$name);
        $name = str_replace("Š","š",$name);
        $name = str_replace("Š","š",$name);
        $name = str_replace("Ş","ş",$name);
        $name = str_replace("Ž","ž",$name);
        $name = str_replace("Œ","œ",$name);
        return trim($name);
    }
    
    public static function author_parts()
    {
        if(@!$GLOBALS["AUTHOR_PARTS"])
        {
            $array = array();
            $array["d'"]=true;
            $array["der"]=true;
            $array["du"]=true;
            $array["den"]=true;
            $array["le"]=true;
            $array["la"]=true;
            $array["de"]=true;
            $array["da"]=true;
            $array["del"]=true;
            $array["delle"]=true;
            $array["della"]=true;
            $array["des"]=true;
            $array["van"]=true;
            $array["von"]=true;
            $array["y"]=true;
            
            $GLOBALS["AUTHOR_PARTS"] = $array;
        }
    }
    
    public static function sci_parts()
    {
        if(@!$GLOBALS["SCI_PARTS"])
        {
            $array = array();
            $array["unranked"]=true;
            $array["susbsp"]=true;
            $array["lus"]=true;
            $array["sf"]=true;
            $array["subv"]=true;
            $array["susp"]=true;
            $array["pseudosp"]=true;
            $array["subvariety"]=true;
            $array["variety"]=true;
            $array["subspecies"]=true;
            $array["subgroup"]=true;
            $array["group"]=true;
            $array["subfam"]=true;
            $array["spp"]=true;
            $array["convar"]=true;
            $array["forma"]=true;
            $array["fo"]=true;
            $array["form"]=true;
            $array["subforma"]=true;
            $array["subgen"]=true;
            $array["subg"]=true;
            $array["subf"]=true;
            $array["subvar"]=true;
            $array["nothovar"]=true;
            $array["nothosubsp"]=true;
            $array["variant"]=true;
            $array["var"]=true;
            $array["subsp"]=true;
            $array["sp"]=true;
            $array["ssp"]=true;
            $array["subgenus"]=true;
            $array["group"]=true;
            $array["species"]=true;
            $array["generic"]=true;
            $array["genus"]=true;
            $array["genera"]=true;
            $array["complex"]=true;
            $array["section"]=true;
            $array["genus"]=true;
            $array["morph"]=true;
            $array["mstr"]=true;
            $array["notho"]=true;
            $array["chr"]=true;
            $array["mutation"]=true;
            $array["mutatio"]=true;
            $array["biogroup"]=true;
            $array["sec"]=true;
            $array["lato"]=true;
            $array["juvenile"]=true;
            $array["variété"]=true;
            $array["holotype"]=true;
            $array["cross"]=true;
            $array["f"]=true;
            $array["x"]=true;
            
            $GLOBALS["SCI_PARTS"] = $array;
        }
    }
    
    public static function sci_parts1()
    {
        if(@!$GLOBALS["SCI_PARTS1"])
        {
            $array = array();
            $array["unranked"]=true;
            $array["susbsp"]=true;
            $array["lus"]=true;
            $array["sf"]=true;
            $array["subv"]=true;
            $array["susp"]=true;
            $array["pseudosp"]=true;
            $array["subvariety"]=true;
            $array["variety"]=true;
            $array["subspecies"]=true;
            $array["subgroup"]=true;
            $array["group"]=true;
            $array["subfam"]=true;
            $array["spp"]=true;
            $array["convar"]=true;
            $array["fo"]=true;
            $array["form"]=true;
            $array["subforma"]=true;
            $array["subgen"]=true;
            $array["subg"]=true;
            $array["subf"]=true;
            $array["nothovar"]=true;
            $array["nothosubsp"]=true;
            $array["variant"]=true;
            $array["ssp"]=true;
            $array["subgenus"]=true;
            $array["group"]=true;
            $array["species"]=true;
            $array["generic"]=true;
            $array["genus"]=true;
            $array["genera"]=true;
            $array["complex"]=true;
            $array["section"]=true;
            $array["genus"]=true;
            $array["morph"]=true;
            $array["mstr"]=true;
            $array["notho"]=true;
            $array["chr"]=true;
            $array["mutation"]=true;
            $array["mutatio"]=true;
            $array["biogroup"]=true;
            $array["sec"]=true;
            $array["lato"]=true;
            $array["juvenile"]=true;
            $array["variété"]=true;
            $array["holotype"]=true;
            $array["cross"]=true;
            
            $GLOBALS["SCI_PARTS1"] = $array;
        }
    }
    
    public static function sci_parts2()
    {
        if(@!$GLOBALS["SCI_PARTS2"])
        {
            $array = array();
            $array["var"]=true;
            $array["subsp"]=true;
            $array["sp"]=true;
            $array["forma"]=true;
            $array["f"]=true;
            $array["x"]=true;
            $array["subvar"]=true;
            
            $GLOBALS["SCI_PARTS2"] = $array;
        }
    }
    
    public static function junk_parts()
    {
        if(@!$GLOBALS["JUNK_PARTS"])
        {
            $array = array();
            $array["cultiv"]=true;
            $array["enrichment"]=true;
            $array["culture"]=true;
            $array["clone"]=true;
            $array["str"]=true;
            $array["doubtful"]=true;
            $array["dubious"]=true;
            $array["emended"]=true;
            $array["com"]=true;
            $array["auth"]=true;
            $array["sens"]=true;
            $array["partim"]=true;
            $array["fi"]=true;
            $array["indicated"]=true;
            $array["lat"]=true;
            $array["id"]=true;
            $array["ab"]=true;
            $array["loc"]=true;
            $array["and"]=true;
            $array["&"]=true;
            $array["&amp;"]=true;
            $array["corrig"]=true;
            $array["pv"]=true;
            $array["mult"]=true;
            $array["cv"]=true;
            $array["inval"]=true;
            $array["aff"]=true;
            $array["ambig"]=true;
            $array["anon"]=true;
            $array["orth"]=true;
            $array["hyb"]=true;
            $array["gen"]=true;
            $array["nomen"]=true;
            $array["invalid"]=true;
            $array["prep"]=true;
            $array["dela"]=true;
            $array["press"]=true;
            $array["illeg"]=true;
            $array["ssel"]=true;
            $array["hl"]=true;
            $array["ll"]=true;
            $array["super"]=true;
            $array["pro"]=true;
            $array["hybr"]=true;
            $array["plur"]=true;
            $array["nk"]=true;
            $array["as"]=true;
            $array["to"]=true;
            $array["type"]=true;
            $array["nud"]=true;
            $array["et"]=true;
            $array["al"]=true;
            $array["accord"]=true;
            $array["according"]=true;
            $array["orthographic"]=true;
            $array["emend"]=true;
            $array["of"]=true;
            $array["authors"]=true;
            $array["nom"]=true;
            $array["comb"]=true;
            $array["nov"]=true;
            $array["ined"]=true;
            $array["cons"]=true;
            $array["sensu"]=true;
            $array["hort"]=true;
            $array["p.p"]=true;
            $array["not"]=true;
            $array["strain"]=true;
            $array["cf"]=true;
            $array["status"]=true;
            $array["unclear"]=true;
            $array["fide"]=true;
            $array["see"]=true;
            $array["comment"]=true;
            $array["bis"]=true;
            $array["specified"]=true;
            $array["be"]=true;
            $array["filled"]=true;
            $array["fil"]=true;
            $array["questionable"]=true;
            $array["the"]=true;
            $array["arid"]=true;
            $array["acc"]=true;
            $array["region"]=true;
            $array["eul"]=true;
            $array["ms"]=true;
            $array["beauv"]=true;
            $array["prop"]=true;
            $array["nm"]=true;
            $array["fort"]=true;
            $array["mut"]=true;
            $array["stat"]=true;
            $array["plants"]=true;
            $array["nec"]=true;
            $array["given"]=true;
            $array["cited"]=true;
            $array["typ"]=true;
            $array["ign"]=true;
            $array["often"]=true;
            $array["referred"]=true;
            $array["superfl"]=true;
            $array["parte"]=true;
            $array["plants"]=true;
            $array["pl"]=true;
            $array["fig"]=true;
            $array["no"]=true;
            $array["prelo"]=true;
            $array["maly"]=true;
            $array["schneider"]=true;
            $array["apud"]=true;
            $array["sine"]=true;
            $array["typo"]=true;
            $array["abbreviation"]=true;
            $array["recorded"]=true;
            $array["label"]=true;
            $array["on"]=true;
            $array["hybridized"]=true;
            $array["with"]=true;
            $array["unspecified"]=true;
            $array["rke"]=true;
            $array["illegible"]=true;
            $array["biotype"]=true;
            $array["race"]=true;
            $array["biotype"]=true;
            $array["vag"]=true;
            $array["tax"]=true;
            $array["x"]=true;
            $array["west"]=true;
            $array["auctor"]=true;
            $array["toni"]=true;
            $array["assigned"]=true;
            $array["sect"]=true;
            $array["subsect"]=true;
            $array["series"]=true;
            $array["ser"]=true;
            //$array["typus"]=true;
            $array["dos"]=true;
            $array["rn"]=true;
            $array["editor"]=true;
            $array["di"]=true;
            $array["list"]=true;
            $array["pl"]=true;
            $array["applicable"]=true;
            $array["undet"]=true;
            $array["species"]=true;
            $array["col"]=true;
            $array["area"]=true;
            $array["op"]=true;
            $array["cit"]=true;
            $array["ey"]=true;
            $array["zu"]=true;
            $array["und"]=true;
            $array["name"]=true;
            $array["only"]=true;
            $array["excl"]=true;
            $array["syn"]=true;
            $array["or"]=true;
            $array["also"]=true;
            $array["by"]=true;
            $array["ex"]=true;
            $array["in"]=true;
            $array["auct"]=true;
            $array["non"]=true;
            $array["date"]=true;
            $array["inter"]=true;
            $array["before"]=true;
            $array["vel"]=true;
            $array["sep"]=true;
            $array["nat"]=true;
            $array["bekannt"]=true;
            $array["ter"]=true;
            $array["É"]=true;
            $array["nr"]=true;
            $array["aberr"]=true;
            $array["nr"]=true;
            $array["between"]=true;
            $array["rus"]=true;
            $array["ent"]=true;
            $array["synanamorph"]=true;
            $array["anamorph"]=true;
            $array["zur"]=true;
            $array["ul"]=true;
            $array["lu"]=true;
            $array["circa"]=true;
            $array["pls"]=true;
            $array["ante"]=true;
            $array["testa"]=true;
            $array["prior"]=true;
            $array["generic"]=true;
            $array["post"]=true;
            $array["etc"]=true;
            $array["binom"]=true;
            //$array["do"]=true;
            $array["nex"]=true;
            $array["auctt"]=true;
            $array["stricto"]=true;
            $array["das"]=true;
            $array["dates"]=true;
            $array["from"]=true;
            $array["doubtful"]=true;
            $array["dubious"]=true;
            $array["emended"]=true;
            $array["com"]=true;
            $array["partim"]=true;
            $array["fi"]=true;
            $array["indicated"]=true;
            $array["lat"]=true;
            $array["ii"]=true;
            $array["ry"]=true;
            $array["ndez"]=true;
            $array["lez"]=true;
            $array["lc"]=true;
            $array["rskov"]=true;
            $array["nudum"]=true;
            $array["sbsp"]=true;
            $array["morpha"]=true;
            $array["esp"]=true;
            $array["mph"]=true;
            $array["s-sp"]=true;
            $array["subs"]=true;
            $array["variété"]=true;
            $array["forme"]=true;
            $array["subspec"]=true;
            $array["sous-type"]=true;
            $array["inte"]=true;
            $array["subspp"]=true;
            $array["indet"]=true;
            $array["corrected"]=true;
            $array["none"]=true;
            $array["iber"]=true;
            $array["eur"]=true;
            $array["balcan"]=true;
            $array["nonn"]=true;
            $array["fl"]=true;
            $array["cauc"]=true;
            $array["armen"]=true;
            $array["inc"]=true;
            $array["orient"]=true;
            $array["ross"]=true;
            $array["med"]=true;
            $array["germ"]=true;
            $array["boreal"]=true;
            $array["boruss"]=true;
            $array["amer"]=true;
            $array["prol"]=true;
            $array["ca"]=true;
            $array["but"]=true;
            $array["misapplied"]=true;
            $array["subst"]=true;
            $array["for"]=true;
            $array["int"]=true;
            $array["several"]=true;
            $array["error"]=true;
            $array["pers"]=true;
            $array["comm"]=true;
            $array["nudum"]=true;
            $array["errore"]=true;
            $array["incertae"]=true;
            $array["sedis"]=true;
            $array["sic"]=true;
            $array["substit"]=true;
            $array["web"]=true;
            $array["site"]=true;
            $array["viii"]=true;
            $array["oblit"]=true;
            $array["new"]=true;
            $array["xxxx"]=true;
            $array["an"]=true;
            $array["objective"]=true;
            $array["synonym"]=true;
            $array["now"]=true;
            $array["bottom"]=true;
            $array["both"]=true;
            $array["pictures"]=true;
            $array["synonymy"]=true;
            $array["uncertain"]=true;
            $array["substit"]=true;
            $array["under"]=true;
            $array["inc"]=true;
            $array["sed"]=true;
            $array["spelling"]=true;
            $array["brit"]=true;
            $array["irj"]=true;
            $array["mf"]=true;
            $array["subfo"]=true;
            $array["sport"]=true;
            $array["tribe"]=true;
            $array["subtribe"]=true;
            $array["subser"]=true;
            $array["subtrib"]=true;
            $array["trib"]=true;
            $array["sebsp"]=true;
            $array["lusus"]=true;
            $array["sub"]=true;
            $array["gr"]=true;
            $array["oblvar"]=true;
            $array["nra"]=true;
            $array["fam"]=true;
            $array["en"]=true;
            $array["mey"]=true;
            $array["susbp"]=true;
            $array["sre"]=true;
            $array["subtr"]=true;
            $array["subdiv"]=true;
            $array["pars"]=true;
            $array["quad"]=true;
            $array["typum"]=true;
            $array["set"]=true;
            $array["rouy"]=true;
            $array["opiz"]=true;
            $array["agsp"]=true;
            $array["ourk"]=true;
            //$array["proles"]=true;
            $array["liu"]=true;
            $array["ecad"]=true;
            $array["substirps"]=true;
            $array["groupa"]=true;
            $array["groupe"]=true;
            $array["divis"]=true;
            $array["nothosect"]=true;
            $array["nothomorph"]=true;
            $array["em"]=true;
            $array["nsubsp"]=true;
            $array["monstr"]=true;
            $array["rev"]=true;
            $array["basionym"]=true;
            $array["quoad"]=true;
            $array["ven"]=true;
            $array["order"]=true;
            $array["mon"]=true;
            $array["superord"]=true;
            $array["ord"]=true;
            $array["subvars"]=true;
            $array["cm"]=true;
            $array["supertrib"]=true;
            $array["mnstr"]=true;
            $array["ren"]=true;
            $array["subset"]=true;
            $array["subtribus"]=true;
            $array["agg"]=true;
            $array["jr"]=true;
            $array["nothof"]=true;
            $array["nothogen"]=true;
            $array["nothosubgen"]=true;
            $array["individual"]=true;
            //$array["index"]=true;
            $array["supsp"]=true;
            $array["attr"]=true;
            $array["incorrectly"]=true;
            $array["ined;cf"]=true;
            $array["el"]=true;
            $array["various"]=true;
            $array["cultivars"]=true;
            $array["af"]=true;
            $array["valide"]=true;
            $array["publ"]=true;
            $array["class"]=true;
            $array["sufam"]=true;
            $array["xx"]=true;
            $array["xxx"]=true;
            $array["xxxx"]=true;
            $array["quib"]=true;
            $array["ap"]=true;
            $array["subap"]=true;
            $array["grupo"]=true;
            $array["gruppe"]=true;
            $array["oec"]=true;
            $array["prole"]=true;
            $array["nothsect"]=true;
            $array["nssp"]=true;
            $array["nopthosubsp"]=true;
            $array["jun"]=true;
            $array["rx"]=true;
            $array["like"]=true;
            $array["ascribed"]=true;
            $array["included"]=true;
            $array["rejected"]=true;
            $array["segregates"]=true;
            $array["ngstr"]=true;
            $array["nothosubg"]=true;
            $array["subclassis"]=true;
            $array["eds"]=true;
            $array["spec"]=true;
            $array["ty"]=true;
            $array["ed"]=true;
            $array["herb"]=true;
            
            $GLOBALS["JUNK_PARTS"] = $array;
        }
    }
    
    public static function time_elapsed()
    {
        static $a;
        if(!isset($a)) $a = microtime(true);
        return (string) round(microtime(true)-$a, 6);
    }
    
    public static function catch_exception($e)
    {
        echo "Caught exception with message '".$e->getMessage()."' in ".$e->getFile().":".$e->getLine()."<br>\nStack trace:<br>\n".$e->getTraceAsString()."<br>\n";
    }
    
    public static function print_pre($array, $return = false)
    {
        $str = "<pre>";
        $str .= print_r($array, 1);
        $str .= "</pre>";
        
        if($return) return $str;
        echo $str;
    }
    
    public static function print_r_public($str, $return = false)
    {
        $str = print_r($str, true);
        $regex = "/\n( +)\[[^\]]*?:(private|protected)\] =>.*?(\n\\1\[)/ims";
        while(preg_match($regex, $str)) $str = preg_replace($regex, "\\3", $str);
        
        if($return) return $str;
        echo $str;
    }
    
    /* Given the file path, get the MIME type */
    public static function get_mimetype($file_path)
    {
        $path_info = pathinfo($file_path);
        $extension = strtolower($path_info['extension']);
        $mimetype="";
        $mpg=array("mpg","mpeg");
        if      ($extension == "wmv")         $mimetype="video/x-ms-wmv";
        elseif  ($extension == "avi")         $mimetype="video/x-msvideo";
        elseif  ($extension == "mp4")         $mimetype="video/mp4";
        elseif  ($extension == "mov")         $mimetype="video/quicktime";
        elseif  (in_array($extension, $mpg))  $mimetype="video/mpeg";
        elseif  ($extension == "flv")         $mimetype="video/x-flv";
        elseif  ($extension == "gif")         $mimetype="image/gif";
        elseif  ($extension == "jpg")         $mimetype="image/jpeg";
        return $mimetype;
    }
    
    public static function language_to_iso_code()
    {
        $iso_639_2_codes = array();
        if(file_exists(DOC_ROOT . 'vendor/wikipedia/iso_639_2.txt'))
        {
            $lines = file(DOC_ROOT . 'vendor/wikipedia/iso_639_2.txt');
            foreach($lines as $line)
            {
                $line = rtrim($line, "\n");
                $parts = explode("\t", $line);
                if(isset($parts[0]) && strlen($parts[0])==2 && isset($parts[1]))
                {
                    $iso_639_2_codes[$parts[1]] = $parts[0];
                }
            }
        }
        return $iso_639_2_codes;
    }

    public static function cardinal_to_ordinal($number)
    {
        switch(substr($number, -1))
        {
            case 1: $number .= "st"; break;
            case 2: $number .= "nd"; break;
            case 3: $number .= "rd"; break;
            default: $number .= "th"; break;
        }
        return $number;
    }

    public static function format_number_with_leading_zeros($num, $padding)
    {
        return str_pad($num, $padding, "0", STR_PAD_LEFT);
    }

    public static function prepare_taxon_params($rec)
    {
        $taxon = array();
        $taxon["identifier"]     = trim(@$rec["identifier"]);
        $taxon["source"]         = @$rec["source"];
        $taxon["kingdom"]        = ucfirst(trim(@$rec["kingdom"]));
        $taxon["phylum"]         = ucfirst(trim(@$rec["phylum"]));
        $taxon["class"]          = ucfirst(trim(@$rec["class"]));
        $taxon["order"]          = ucfirst(trim(@$rec["order"]));
        $taxon["family"]         = ucfirst(trim(@$rec["family"]));
        $taxon["genus"]          = ucfirst(trim(@$rec["genus"]));
        $taxon["scientificName"] = ucfirst(trim(@$rec["sciname"]));
        $taxon["rank"]           = @$rec["rank"];
        //-------------------------------------------------------------------------------------------------
        if(@$rec["commonNames"])
        {
            $taxon["commonNames"] = array();
            foreach($rec["commonNames"] as $comname)
            {
                $taxon["commonNames"][] = new \SchemaCommonName(array("name" => $comname["name"], "language" => $comname["language"]));
            }
        }
        //-------------------------------------------------------------------------------------------------
        if(@$rec["synonyms"])
        {
            $taxon["synonyms"] = array();
            foreach($rec["synonyms"] as $syn)
            {
                $taxon["synonyms"][] = new \SchemaSynonym(array("synonym" => $syn["synonym"], "relationship" => $syn["relationship"]));
            }
        }
        //-------------------------------------------------------------------------------------------------
        $taxon = Functions::prepare_agent_params($rec, $taxon);
        $taxon["created"]  = trim(@$rec["created"]);
        $taxon["modified"] = trim(@$rec["modified"]);
        $taxon = Functions::prepare_reference_params($rec, $taxon);
        $taxon["additionalInformation"] = trim(@$rec["additionalInformation"]);
        //-------------------------------------------------------------------------------------------------
        if(@$rec["data_objects"])
        {
            foreach($rec["data_objects"] as $object)
            {
                if($data_object = Functions::prepare_data_object_params($object)) $taxon["dataObjects"][] = new \SchemaDataObject($data_object);
            }
        }
        //-------------------------------------------------------------------------------------------------
        $taxon_parameters = new \SchemaTaxon($taxon);
        return $taxon_parameters;
    }

    public static function prepare_data_object_params($rec)
    {
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = trim(@$rec["identifier"]);
        $data_object_parameters["dataType"]   = trim(@$rec["dataType"]);
        $data_object_parameters["mimeType"]   = trim(@$rec["mimeType"]);
        $data_object_parameters = Functions::prepare_agent_params($rec, $data_object_parameters);
        $data_object_parameters["created"]               = trim(@$rec["created"]);
        $data_object_parameters["modified"]              = trim(@$rec["modified"]);
        $data_object_parameters["title"]                 = trim(@$rec["title"]);
        $data_object_parameters["language"]              = trim(@$rec["language"]);
        $data_object_parameters["license"]               = trim(@$rec["license"]);
        $data_object_parameters["rights"]                = trim(@$rec["rights"]);
        $data_object_parameters["rightsHolder"]          = trim(@$rec["rightsHolder"]);
        $data_object_parameters["bibliographicCitation"] = trim(@$rec["bibliographicCitation"]);
        //-------------------------------------------------------------------------------------------------
        if(@$rec["audience"])
        {
            $data_object_parameters["audiences"] = array();    
            $audienceParameters = array();  
            foreach(@$rec["audience"] as $audience)
            {
                $audienceParameters["label"] = $audience;
                $data_object_parameters["audiences"][] = new \SchemaAudience($audienceParameters);
            }
        }
        //-------------------------------------------------------------------------------------------------
        $data_object_parameters["source"] = trim(@$rec["source"]);
        //-------------------------------------------------------------------------------------------------
        if(trim(@$rec["subject"]))
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = trim(@$rec["subject"]);
            $data_object_parameters["subjects"][] = new \SchemaSubject($subjectParameters);
        }
        //-------------------------------------------------------------------------------------------------
        $data_object_parameters["description"]  = trim(@$rec["description"]);
        $data_object_parameters["mediaURL"]     = trim(@$rec["mediaURL"]);
        $data_object_parameters["thumbnailURL"] = trim(@$rec["thumbnailURL"]);
        $data_object_parameters["location"]     = trim(@$rec["location"]);
        $data_object_parameters = Functions::prepare_reference_params($rec, $data_object_parameters);
        $data_object_parameters["additionalInformation"] = trim(@$rec["additionalInformation"]);
        //-------------------------------------------------------------------------------------------------
        return $data_object_parameters;
    }

    public static function prepare_reference_params($rec, $taxon_or_data_object_param)
    {
        if(@$rec["reference"])
        {
            $taxon_or_data_object_param["references"] = array();
            $reference = array();
            $attributes = array("bici", "coden", "doi", "eissn", "handle", "isbn", "issn", "lsid", "oclc", "sici", "url", "urn");
            foreach(@$rec["reference"] as $ref)
            {
                $referenceParam = array();
                $referenceParam["fullReference"] = $ref["fullReference"];
                foreach($attributes as $attribute)
                {
                    if(@$ref[$attribute]) $referenceParam["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => $attribute, "value" => trim(@$ref[$attribute])));
                }
                $reference[] = new \SchemaReference($referenceParam);
            }
            $taxon_or_data_object_param["references"] = $reference;
        }
        return $taxon_or_data_object_param;
    }

    public static function prepare_agent_params($rec, $taxon_or_data_object_param)
    {
        if(@$rec["agent"])
        {
            $agents = array();
            foreach(@$rec["agent"] as $agent)
            {  
                $agentParameters = array();
                $agentParameters["role"]     = @$agent["role"];
                $agentParameters["homepage"] = @$agent["homepage"];
                $agentParameters["logoURL"]  = @$agent["logoURL"];
                $agentParameters["fullName"] = @$agent["fullName"];
                $agents[] = new \SchemaAgent($agentParameters);
            }
            $taxon_or_data_object_param["agents"] = $agents;
        }
        return $taxon_or_data_object_param;
    }

    public static function set_resource_status_to_force_harvest($resource_id)
    {
        if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 600) $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
    }

    //4 functions for queueing task in connectors
    public static function add_a_task($task, $filename)
    {
        if($READ = fopen($filename, "a"))
        {
            fwrite($READ, $task);
            fclose($READ);
        }
    }

    public static function get_a_task($filename)
    {
        if($READ = fopen($filename, "r"))
        {
            $line = fgets($READ);
            fclose($READ);
            return $line;
        }
    }

    public static function delete_a_task($task, $filename)
    {
        if($READ = fopen($filename, 'r'))
        {
            $task_list = fread($READ, filesize($filename));
            fclose($READ);
            $task_list = str_ireplace($task, "", $task_list);
            //saving
            $OUT = fopen($filename, 'w');
            fwrite($OUT, $task_list);
            fclose($OUT);
        }
    }

    public static function run_another_connector_instance($resource_id, $times)
    {
        for($i = 1; $i <= $times; $i++)
        {
            print "\n run " . self::cardinal_to_ordinal($i + 1) . " instance--";
            shell_exec('php ' . DOC_ROOT . 'update_resources/connectors/' . $resource_id . '_next.php 0 > null &');
            sleep(5);
        }
    }

    public static function kill_running_connectors($resource_id)
    {
        $myPID = getmypid();
        print "\n myPID: $myPID \n"; //this won't get killed
        $pattern = "update_resources/connectors/" . $resource_id . "|update_resources/connectors/" . $resource_id . "_next";
        $command = "ps -x | egrep " . "'($pattern).php'";
        $output = trim(shell_exec($command));
        $jobs = explode("\n", $output);
        $jobs = array_values($jobs);
        $pids = array();
        foreach($jobs as $job) if($job) $pids[] = substr($job, 0, strpos($job, ' '));
        asort($pids);
        if($pids)
        {
            print_r($jobs);
            foreach($pids as $pid)
            {
                if($pid <> $myPID)
                {
                    print "\n kill $pid ";
                    shell_exec('kill ' . $pid);
                } 
            }
        }
        else print "\n That connector is not running at the moment.";
        print "\n\n";
    }

    public function create_work_list_from_master_file($master_file, $divisor, $destination_folder, $filename_prefix, $work_list)
    {
        $FILE = fopen($master_file, "r");
        $i = 0;
        $file_ctr = 0;
        $str = "";
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $i++;
                $str .= $line;
                print "\n$i. $line";
                if($i == $divisor)//no. of rows per text file
                {
                    $file_ctr++;
                    $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 3);
                    $OUT = fopen($destination_folder . $filename_prefix . $file_ctr_str . ".txt", "w");
                    fwrite($OUT, $str);
                    fclose($OUT);
                    $str = "";
                    $i = 0;
                }
            }
        }
        //last writes
        if($str)
        {
            $file_ctr++;
            $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 3);
            $OUT = fopen($destination_folder . $filename_prefix . $file_ctr_str . ".txt", "w");
            fwrite($OUT, $str);
            fclose($OUT);
        }
        //create work_list
        $str = "";
        for($i = 1; $i <= $file_ctr; $i++) $str .= $filename_prefix . Functions::format_number_with_leading_zeros($i, 3) . "\n";
        if($fp = fopen($work_list, "w"))
        {
            fwrite($fp, $str);
            fclose($fp);
        }
    }

    function combine_all_eol_resource_xmls($resource_id, $files)
    {
        print "\n\n Start compiling all XML...";
        $OUT = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", "w");
        $str = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $str .= "<response\n";
        $str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
        $str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
        fwrite($OUT, $str);
        foreach (glob($files) as $filename)
        {
            print "\n $filename ";
            $READ = fopen($filename, "r");
            $contents = fread($READ, filesize($filename));
            fclose($READ);
            if($contents) 
            {
                $pos1 = stripos($contents, "<taxon>");
                $pos2 = stripos($contents, "</response>");
                $str  = substr($contents, $pos1, $pos2-$pos1);
                if($pos1) fwrite($OUT, $str);
            }
            else print "\n no contents [$filename]";
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        print"\n All XML compiled\n\n";
    }

}
?>