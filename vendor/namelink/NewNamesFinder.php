<?php

class NewNamesFinder
{
    private $html;
    private $html_elements;
    private $marked_html;
    private $global_offset;
    private $tag_id;
    private $open_tag;
    private $close_tag;
    private $taxonfinder_client;
    
    // instance functions
    public function __construct($html)
    {
        $this->initialize($html);
    }
    
    public function initialize($html)
    {
        $this->html = $html;
        $this->html_elements = array();
        $this->marked_html = $this->html;
        $this->global_offset = 0;
        $this->tag_id = 0;
    }
    
    public function markup_html()
    {
        $elements = self::explode_html($this->html);
        $this->html_elements = self::remove_tags_from_elements($elements);
        
        $this->find_and_markup_names();
        
        return $this->marked_html;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    // static functions
    
    public static function explode_html($html)
    {
        $words_temp = preg_split("/( |&nbsp;|<|>|\t|\n|\r|;|\.)/i", $html, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_OFFSET_CAPTURE);
        
        $num = count($words_temp);
        $words = array();
        $n = 0;
        foreach($words_temp as $element)
        {
            $word = $element[0];
            $offset = $element[1];
            
            if($word == "<")
            {
                $words[$n] = $element;
            }elseif($word == ">")
            {
                $words[$n-1] = self::merge_exploded_elements($words[$n-1], $element);
            }elseif($word==".")
            {
                $words[$n-1] = self::merge_exploded_elements($words[$n-1], $element);
            }elseif($word==";")
            {
                $words[$n-1] = self::merge_exploded_elements($words[$n-1], $element);
            }elseif(preg_match("/^[[:space:]]*$/",$word) && $n!=0)
            {
                $words[$n-1] = self::merge_exploded_elements($words[$n-1], $element);
            }else
            {
                @$words[$n] = self::merge_exploded_elements($words[$n], $element);
                $n++;
            }
        }
        
        return $words;
    }
    
    public static function merge_exploded_elements($a, $b)
    {
        $new_element = array();
        $new_element[0] = $a[0] . $b[0];
        if(!$a) $new_element[1] = $b[1];
        else $new_element[1] = $a[1];
        
        return $new_element;
    }
    
    public static function remove_tags_from_elements($elements)
    {
        $cleaned_elements = array();
        
        $within_tag = false;
        foreach($elements as $element)
        {
            $word = trim($element[0]);
            
            if($within_tag)
            {
                // ...tag>
                if(preg_match("/^(.*)>\z/ims", $word, $arr))
                {
                    if($e = self::get_element_from_tag($arr[1])) $cleaned_elements[] = $e;
                    $within_tag = false;
                }
                continue;
            }
            
            // <tag>
            if(preg_match("/^<(\/?.*[a-z0-9-]\/?)>\z/ims", $word, $arr))
            {
                if($e = self::get_element_from_tag($arr[1])) $cleaned_elements[] = $e;
                continue;
            }
            
            // <tag...
            if(preg_match("/^<([a-z0-9!].*)\z/ims", $word, $arr))
            {
                if($e = self::get_element_from_tag($arr[1])) $cleaned_elements[] = $e;
                $within_tag = true;
                continue;
            }
            
            $cleaned_elements[] = $element;
        }
        
        $cleaned_elements[] = array(TAXONFINDER_STOP_KEYWORD, -1);
        
        return $cleaned_elements;
    }
    
    private static function get_element_from_tag($tag)
    {
        $tag = strtolower($tag);
        if(substr($tag, 0, 1) == "/") $tag = substr($tag, 1);
        
        if($tag == "p" || $tag == "td" || $tag == "tr" || $tag == "table" || $tag == "hr")
        {
            return array(TAXONFINDER_STOP_KEYWORD, -1);
        }
        
        return false;
    }
    
    
    
    
    
    
    
    
    
    
    
    public static function find_names($html)
    {
        $all_names = array();
        $tokens = self::explode_html($html);
        $elements = self::remove_tags_from_elements($tokens);
        
        $uninomial_ranks = 'gen|genus|subgen|fam|subfam|superfam|tribe|subtribe|supertribe|class|order|suborder|infraorder|superorder|class|subclass|superclass';
        $binomial_ranks = 'sp|species|genus|gen|comb';
        $nov_terms = 'nov|n|new';
        
        $text = "";
        foreach($elements as $index => $element)
        {
            $word = trim($element[0]);
            $text .= "$word ";
        }
        $text = preg_replace("/(;)/", TAXONFINDER_STOP_KEYWORD, $text);
        $text = preg_replace("/(\n|\r|\t|,|\.|:|;)/", " ", $text);
        $text = str_replace("&nbsp;", " ", $text);
        $text = str_replace("&amp;", "&", $text);
        $text = str_replace(" et ", " nov ", $text);
        while(preg_match("/  /",$text)) $text = str_replace("  "," ",$text);
                
        //echo $text;
        
        if(preg_match_all("/ ([".UPPER."][".LOWER."-]{2,}) ((($uninomial_ranks) ($nov_terms) |($nov_terms) ($uninomial_ranks) )+)/i", $text, $arr, PREG_SET_ORDER))
        {
            foreach($arr as $match)
            {
                $name = trim($match[0]);
                if(preg_match("/^([".UPPER."][".LOWER."-]{2,})/", $name)) $all_names[$match[0]] = 1;
            }
        }
        
        if(preg_match_all("/ ([".UPPER."][".LOWER."-]{2,} [".LOWER."-]{2,}) ((($binomial_ranks) ($nov_terms) |($nov_terms) ($binomial_ranks) )+)/i", $text, $arr, PREG_SET_ORDER))
        {
            foreach($arr as $match)
            {
                $name = trim($match[0]);
                if(preg_match("/^([".UPPER."][".LOWER."-]{2,} [".LOWER."-]{2,})/", $name)) $all_names[$match[0]] = 1;
            }
        }
        
        // 
        // $previous_word = "";
        // $previous_word2 = "";
        // $previous_word3 = "";
        // 
        // foreach($tokens as $index => $element)
        // {
        //     $word = trim($element[0]);
        //     if(isset($tokens[$index+1][0])) $next_word = trim($tokens[$index+1][0]);
        //     else $next_word = "";
        //     echo "$word\n";
        //     
        //     if(preg_match("/^(gen|subgen|fam|subfam|superfam|tribe|subtribe|supertribe|class|order|suborder|infraorder|superorder|class|subclass|superclass)[,\.]*$/i", $word))
        //     {
        //         if(preg_match("/^(nov|n)[,\.]*$/i", $next_word) && preg_match("/^[".UPPER."][".LOWER."]{2,}$/", $previous_word))
        //         {
        //             $all_names[] = "$previous_word $word $next_word";
        //         }
        //         
        //         if(preg_match("/^(nov|n)[,\.]*$/i", $previous_word) && preg_match("/^[".UPPER."][".LOWER."]{2,}$/", $previous_word2))
        //         {
        //             $all_names[] = "$previous_word2 $previous_word $word";
        //         }
        //     }
        //     
        //     if(preg_match("/^(sp|gen|comb)[,\.]*$/i", $word))
        //     {
        //         if(preg_match("/^(nov|n)[,\.]*$/i", $next_word) && preg_match("/^[".LOWER."]{2,}$/", $previous_word) && preg_match("/^[".UPPER."][".LOWER."]{2,}$/", $previous_word2))
        //         {
        //             $all_names[] = "$previous_word2 $previous_word $word $next_word";
        //         }
        //         
        //         if(preg_match("/^(nov|n)[,\.]*$/i", $previous_word) && preg_match("/^[".LOWER."]{2,}$/", $previous_word2) && preg_match("/^[".UPPER."][".LOWER."]{2,}$/", $previous_word3))
        //         {
        //             $all_names[] = "$previous_word3 $previous_word2 $previous_word $word";
        //         }
        //     }
        //     
        //     $previous_word3 = $previous_word2;
        //     $previous_word2 = $previous_word;
        //     $previous_word = $word;
        // }
        
        return $all_names;
    }
    
    private function clean_return_string($found_name_string)
    {
        $predicted_name_string = $found_name_string;
        if(preg_match("/\[/", $found_name_string))
        {
            $found_name_string = preg_replace("/\[.*?\]/", ".", $found_name_string);
            $predicted_name_string = str_replace("[", "", $predicted_name_string);
            $predicted_name_string = str_replace("]", "", $predicted_name_string);
        }
        
        return array($found_name_string, $predicted_name_string);
    }
}





class new_names_finder
{

    function get_names($text)
    {  
        $this->all_names = array();
        
        $text = preg_replace("/<\/?(!|[a-z])[^>]*>/i"," ",$text);
        //$text = html_entity_decode($text);
        $text = preg_replace("/(\n|\r|\t|,|\.|:|;)/"," ",$text);
        $text = str_replace("&nbsp;"," ",$text);
        $text = str_replace("&amp;","&",$text);
        $text = str_replace(" et "," nov ",$text);
        while(preg_match("/  /",$text)) $text = str_replace("  "," ",$text);
        
        //echo "$text<br>";
        
        if(preg_match_all("/ ([$this->capital][$this->lower-]{2,}) ((gen|subgen|fam|subfam|superfam|tribe|subtribe|supertribe|class|order|suborder|infraorder|superorder|class|subclass|superclass) (nov|n)|(nov|n) (gen|subgen|fam|subfam|superfam|tribe|subtribe|supertribe|class|order|suborder|infraorder|superorder|class|subclass|superclass)) /",$text,$arr))
        {
            while(list($key,$val)=each($arr[0])) 
            {
                $text = str_replace($val," ",$text);
                $fullname = $val;
                $namepart = $arr[1][$key];
                $typepart = $arr[2][$key];
                $words = explode(" ",$typepart);
                if($words[0]=="n"||$words[0]=="nov") $this->all_names[$namepart][$words[1]] = true;
                else $this->all_names[$namepart][$words[0]] = true;
            }
        }
        
        if(preg_match_all("/ ([$this->capital][$this->lower-]{2,} [$this->lower-]{2,}) (((sp|gen|comb) (nov|n) |(nov|n) (sp|gen|comb) )+)/",$text,$arr))
        {
            while(list($key,$val)=each($arr[0])) 
            {
                $text = str_replace($val," ",$text);
                $fullname = $val;
                $namepart = $arr[1][$key];
                $typepart = $arr[2][$key];
                $words = explode(" ",$typepart);
                $num = count($words)-1;
                for($i=0 ; $i<$num ; $i+=2)
                {
                    if($words[$i]=="n"||$words[$i]=="nov") $this->all_names[$namepart][$words[$i+1]] = true;
                    else $this->all_names[$namepart][$words[$i]] = true;
                }
            }
        }
    }
}



?>