<?php
namespace php_active_record;
/* */
class ParseListTypeAPI
{
    function __construct()
    {
        // $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);

    }
    /*#################################################################################################################################*/
    function parse_list_type_pdf($input)
    {
        print_r($input); exit("\nelix\n");
    }
    
    
    /*################################## Jen's utility ################################################################################*/
    function is_title_inside_epub_YN($title, $txtfile)
    {   // exit("\n$title\n$txtfile\n");
        $title .= '';
        $ret = self::check_title($txtfile, $title);
        if(!$ret['found']) {
            // echo "\nTITLE NOT FOUND\n---------------epub content\n".$ret['ten_rows']."\n---------------\n";
            return false;
        }
        else return true; //exit("\ntitle found\n");
    }
    private function check_title($txtfile, $title)
    {
        $i = 0; $final = "";
        foreach(new FileIterator($txtfile) as $line => $row) { $i++;
            $row = trim($row). "\n";
            $final .= $row;
            if($i >= 100) break;
        }
        $final = trim($final);
        $final = str_replace(array("/"), "", $final);
        
        $title = self::get_first_8_words($title);
        $title = str_ireplace("Caddisflies ", "Caddisflies, ", $title); //manual
        
        if(stripos($final, $title) !== false) { //string is found
            return array("found" => true);
        }
        else  return array("found" => false, "ten_rows" => $final);
    }
    private function get_first_8_words($title)
    {
        $a = explode(" ", $title);
        return implode(" ", array($a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7]));
    }
}
?>