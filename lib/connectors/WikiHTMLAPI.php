<?php
namespace php_active_record;
class WikiHTMLAPI
{
    function __construct()
    {
    }
    private function get_tag_name($html) //set to public during development
    {
        $arr = explode(" ", $html);
        $tag = $arr[0];
        return $tag;
    }
    public function get_real_coverage($left, $html)
    {
        //1st: get tag name 
        $tag = self::get_tag_name($left);
        // echo "\ntag_name: [$tag]\n";
        
        //2nd get pos of $left
        $pos = strpos($html, $left);
        // echo "\npos of left: [$pos]\n";
        // echo "\n".substr($html, $pos, 10)."\n"; //debug
        
        //3rd: get ending tag of $tag
        $ending_tag = self::get_ending_tag($tag); // e.g. </table>
        // echo "\nending_tag: [$ending_tag]\n";
        
        //4th: initialize vars
        $open_tag = 1;
        $close_tag = 0;
        $len = strlen($tag);
        
        //5th: start moving substr in search of open_tag (<table) and close_tag (</table>)
        $start_pos = $pos+1;
        while($open_tag >= 1) {
            $str = substr($html, $start_pos, $len);
            // echo "\nopen: [$str]";
            if($str == $tag) $open_tag++;
            
            $str2 = substr($html, $start_pos, $len+2);
            // echo "\nclose: [$str2]";
            if($str2 == "") return false; //meaning the html does not have a balance open and close tags. Invalid html structure.
            if($str2 == $ending_tag) $open_tag--;
            
            if($open_tag < 1) break;
            $start_pos++;
        }
        // echo "\nfinal open: [$str]";
        // echo "\nfinal close: [$str2]";
        
        //6th get substr of entire coverage
        $num = $start_pos + ($len+2);
        $num = $num - $pos;
        $final = substr($html, $pos, $num);
        return str_replace($final, '', $html);
    }
    private function get_ending_tag($tag)
    {   //e.g. "<table"
        return str_replace("<", "</", $tag). ">";
    }
}
?>