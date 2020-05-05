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
        return $tag; //e.g. "<div" or "<table"
    }
    public function get_real_coverage($left, $html)
    {
        $final = array();
        while($html = self::main_real_coverage($left, $html)) {
            if($html) $final[] = $html;
        }
        if($final) return end($final);
        return false;
    }
    public function process_needle($html, $needle, $multipleYN = false) //not multiple process, but only one search of a needle
    {
        $orig = $html;
        if($tmp = $this->get_pre_tag_entry($html, $needle)) {
            $left = $tmp . $needle;
            $html = self::process_left($html, $left);

            if($multipleYN) { //a little dangerous, if not used properly it will nuke the html
                // /* should work but it nukes html
                if($orig == $html) return $html;
                else $html = self::process_needle($html, $needle, true);
                // */
            }
            else return $html; //means single process only
        }
        return $html;
    }
    public function process_left($html, $left)
    {
        if($val = self::get_real_coverage($left, $html)) $html = $val; //get_real_coverage() assumes that html has balanced open and close tags.
        return $html;
    }
    private function main_real_coverage($left, $html)
    {
        //1st: get tag name 
        $tag = self::get_tag_name($left);
        if(!$tag) return false;
        // echo "\ntag_name: [$tag]\n";
        
        //2nd get pos of $left
        $pos = strpos($html, $left);
        if($pos === false) return false;
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
    public function process_external_links($html, $id)
    {
        $left = '<span class="mw-headline" id="'.$id.'"'; $right = '<!--';
        return $this->remove_all_in_between_inclusive($left, $right, $html, false);
    }
}
/* testing nuke html
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Acacia'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Bald Eagle'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Rosa'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Fungi'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Aves'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Panthera tigris'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'sunflower'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Hominidae'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Coronaviridae'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Tracheophyta'
*/
?>