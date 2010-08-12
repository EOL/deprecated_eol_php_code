<?php

class NameTag
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
    public function __construct($html, $open_tag = null, $close_tag = null)
    {
        $this->taxonfinder_client = new TaxonFinderClient();
        $this->initialize($html, $open_tag, $close_tag);
    }
    
    public function reset($html, $open_tag = null, $close_tag = null)
    {
        $this->initialize($html, $open_tag, $close_tag);
    }
    
    public function initialize($html, $open_tag = null, $close_tag = null)
    {
        $this->html = $html;
        $this->html_elements = array();
        $this->marked_html = $this->html;
        $this->global_offset = 0;
        $this->tag_id = 0;
        //$this->taxonfinder_client = new TaxonFinderClient();
        
        if($open_tag)
        {
            $this->open_tag = $open_tag;
            $this->close_tag = $close_tag;
        }else
        {
            $this->open_tag = '<namelink class="namelink" rel="namelink_$ID" id="namelink_$ID" found="$FOUND_NAME" predicted="$COMPLETE_NAME">';
            $this->close_tag = "</namelink>";
        }
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
        //echo "GETTING ELEMENT FROM: " . htmlspecialchars($tag) . "<br>";
        
        $tag = strtolower($tag);
        if(substr($tag, 0, 1) == "/") $tag = substr($tag, 1);
        
        if($tag == "p" || $tag == "td" || $tag == "tr" || $tag == "table" || $tag == "hr")
        {
            return array(TAXONFINDER_STOP_KEYWORD, -1);
        }
        
        return false;
    }
    
    
    
    
    
    
    
    
    
    
    
    public function find_names($html)
    {
        $all_names = array();
        $tokens = self::explode_html($html);
        
        $parameters = array();
        $name_start_index = 0;
        $name_last_index = 0;
        $word_list_matches = 0;
        foreach($tokens as $index => $element)
        {
            $word = $element[0];
            $parameters["word"] = $word;
            
            // echo "<pre>";
            // print_r($parameters);
            // echo "</pre>";
            
            $parameters = $this->taxonfinder_client->check_word($parameters);
            
            // echo "<pre>";
            // print_r($parameters);
            // echo "</pre><hr>";
            
            // we found the end of our potential name string
            if($parameters["return_string"])
            {
                if(!$name_start_index)
                {
                    $name_start_index = $index;
                    $name_last_index = $index;
                }
                
                if(preg_match("/\[/", $parameters["return_string"]))
                {
                    if($word_list_matches) $word_list_matches = "G".$word_list_matches;
                    else $word_list_matches = "G";
                    $parameters["return_code"] = "G".$parameters["return_code"];
                }
                if($word_list_matches) $name_last_index -= strlen($word_list_matches) - strlen($parameters["return_code"]);
                
                $return_strings = self::clean_return_string($parameters["return_string"]);
                $starting_offset = $tokens[$name_start_index][1];
                $all_names[] = array('verbatimString' => $return_strings[0], 'dwc:ScientificName' => $return_strings[1], 'offsets' => array($starting_offset));
                
                $name_start_index = 0;
                $name_last_index = 0;
                $word_list_matches = 0;
            }
            
            // we found the end of our potential name string and we also found a complete uninomial
            if($parameters["return_string_2"])
            {
                $name_start_index = $index;
                $name_last_index = $index;
                
                $return_strings = self::clean_return_string($parameters["return_string_2"]);
                $starting_offset = $tokens[$name_start_index][1];
                $all_names[] = array('verbatimString' => $return_strings[0], 'dwc:ScientificName' => $return_strings[1], 'offsets' => array($starting_offset));
                
                $name_start_index = 0;
                $name_last_index = 0;
                $word_list_matches = 0;
            }
            
            // we found the next word in our potential name string
            if($parameters["current_string"] && !$parameters["return_string"] && $name_start_index)
            {
                $name_last_index = $index;
                $word_list_matches = $parameters["word_list_matches"];
            }
            
            // we found the first word in a potential name string
            if($parameters["current_string"] && !$name_start_index)
            {
                $name_start_index = $index;
                $name_last_index = $index;
                $word_list_matches = $parameters["word_list_matches"];
            }
            
            // we had a potential name string, but it really wasn't a name
            if($name_start_index && !@$parameters["current_string"] && !@$parameters["return_string"])
            {
                $name_start_index = 0;
                $name_last_index = 0;
                $word_list_matches = 0;
            }
            
            unset($parameters["return_string"]);
            unset($parameters["return_code"]);
            unset($parameters["return_string_2"]);
            unset($parameters["return_code_2"]);
        }
        
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
    
    private function find_and_markup_names()
    {
        $this->marked_html = $this->html;
        
        $parameters = array();
        $name_start_index = 0;
        $name_last_index = 0;
        $word_list_matches = 0;
        foreach($this->html_elements as $index => $element)
        {
            $word = $element[0];
            $parameters["word"] = $word;
            
            // echo "<pre>";
            // print_r($parameters);
            // echo "</pre>";
            
            $parameters = $this->taxonfinder_client->check_word($parameters);
            
            // echo "<pre>";
            // print_r($parameters);
            // echo "</pre><hr>";
            
            // we found the end of our potential name string
            if($parameters["return_string"])
            {
                if(!$name_start_index)
                {
                    $name_start_index = $index;
                    $name_last_index = $index;
                }
                
                if(preg_match("/\[/", $parameters["return_string"]))
                {
                    if($word_list_matches) $word_list_matches = "G".$word_list_matches;
                    else $word_list_matches = "G";
                    $parameters["return_code"] = "G".$parameters["return_code"];
                }
                if($word_list_matches) $name_last_index -= strlen($word_list_matches) - strlen($parameters["return_code"]);
                                
                $this->add_tag_around_name($name_start_index, $name_last_index, $parameters["return_string"]);
                
                $name_start_index = 0;
                $name_last_index = 0;
                $word_list_matches = 0;
            }
            
            // we found the end of our potential name string and we also found a complete uninomial
            if($parameters["return_string_2"])
            {
                $name_start_index = $index;
                $name_last_index = $index;
                
                $this->add_tag_around_name($name_start_index, $name_last_index, $parameters["return_string_2"]);
                
                $name_start_index = 0;
                $name_last_index = 0;
                $word_list_matches = 0;
            }
            
            // we found the next word in our potential name string
            if($parameters["current_string"] && !$parameters["return_string"] && $name_start_index)
            {
                $name_last_index = $index;
                $word_list_matches = $parameters["word_list_matches"];
            }
            
            // we found the first word in a potential name string
            if($parameters["current_string"] && !$name_start_index)
            {
                $name_start_index = $index;
                $name_last_index = $index;
                $word_list_matches = $parameters["word_list_matches"];
            }
            
            // we had a potential name string, but it really wasn't a name
            if($name_start_index && !@$parameters["current_string"] && !@$parameters["return_string"])
            {
                $name_start_index = 0;
                $name_last_index = 0;
                $word_list_matches = 0;
            }
            
            unset($parameters["return_string"]);
            unset($parameters["return_code"]);
            unset($parameters["return_string_2"]);
            unset($parameters["return_code_2"]);
        }
    }
    
    // This function will edit the values of html and global_offset as they are passed by reference
    private function add_tag_around_name($name_start_index, $name_last_index, $found_name_string)
    {
        $predicted_name_string = $found_name_string;
        
        if(preg_match("/\[/", $found_name_string))
        {
            $found_name_string = preg_replace("/\[.*?\]/", ".", $found_name_string);
            $predicted_name_string = str_replace("[", "", $predicted_name_string);
            $predicted_name_string = str_replace("]", "", $predicted_name_string);
        }
        
        $open_tag = $this->create_open_tag($found_name_string, $predicted_name_string);
        $close_tag = $this->create_close_tag($found_name_string, $predicted_name_string);
        
        // The text from the elements - not necessarily exactly the same as the name string
        $first_word_in_name = $this->html_elements[$name_start_index][0];
        $last_word_in_name = $this->html_elements[$name_last_index][0];
        
        $first_letter_in_name = substr($found_name_string, 0, 1);
        $last_letter_in_name = substr($found_name_string, -1);
        
        $starting_offset = $this->html_elements[$name_start_index][1] + $this->global_offset;
        $this->global_offset += strlen($open_tag);
        $ending_offset = $this->html_elements[$name_last_index][1] + strlen($last_word_in_name) + $this->global_offset;
        $this->global_offset += strlen($close_tag);
        
        // Accounting for stuff at the beginning and end of found name string
        if(preg_match("/^(.*?)".preg_quote($first_letter_in_name, "/")."/iums", $first_word_in_name, $arr))
        {
            $starting_offset += strlen($arr[1]);
        }
        
        if(preg_match("/.*".preg_quote($last_letter_in_name, "/")."(.*?)\z/iums", $last_word_in_name, $arr))
        {
            $ending_offset -= strlen($arr[1]);
        }
                
        $this->marked_html = substr($this->marked_html, 0, $starting_offset) . $open_tag . substr($this->marked_html, $starting_offset);
        $this->marked_html = substr($this->marked_html, 0, $ending_offset) . $close_tag . substr($this->marked_html, $ending_offset);
    }
    
    private function create_open_tag($found_name_string, $predicted_name_string)
    {
        $this->tag_id++;
        
        $open_tag = str_replace("\$ID", $this->tag_id, $this->open_tag);
        $open_tag = str_replace("\$FOUND_NAME", $found_name_string, $open_tag);
        $open_tag = str_replace("\$COMPLETE_NAME", $predicted_name_string, $open_tag);
        
        return $open_tag;
    }
    
    private function create_close_tag($found_name_string, $predicted_name_string)
    {        
        $close_tag = str_replace("\$ID", $this->tag_id, $this->close_tag);
        $close_tag = str_replace("\$FOUND_NAME", $found_name_string, $close_tag);
        $close_tag = str_replace("\$COMPLETE_NAME", $predicted_name_string, $close_tag);
        
        return $close_tag;
    }
    
    
    
    
    
    
    
    
    
    // public static function find_names($elements)
    // {
    //     $client = new TaxonFinderClient();
    //     
    //     $parameters = array();
    //     foreach($elements as $element)
    //     {
    //         $word = $element[0];
    //         $parameters["word"] = $word;
    //         
    //         // echo "<pre>";
    //         // print_r($parameters);
    //         // echo "</pre>";
    //         
    //         $parameters = $client->check_word($parameters);
    //         
    //         if($str = $parameters["return_string"])
    //         {
    //             //echo "We found <b>$str</b><br>";
    //             flush();
    //         }
    //         if($str = $parameters["return_string_2"])
    //         {
    //             //echo "We also found <b>$str</b><br>";
    //             flush();
    //         }
    //         
    //         unset($parameters["return_string"]);
    //         unset($parameters["return_code"]);
    //         unset($parameters["return_string_2"]);
    //         unset($parameters["return_code_2"]);
    //     }
    //     
    // }
}


?>