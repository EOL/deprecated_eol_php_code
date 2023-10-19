<?php
namespace php_active_record;
/* connector: [remove_html_tags.php]
*/
class RemoveHTMLTagsAPI
{
    function __construct($folder = null)
    {
    }

    public static function remove_html_tags($str)
    {
        $orig_str = $str;
        $str = strip_tags($str, "<a> <img>");

        $input = array();
        $input[] = array("tag_name" => "a", "prop_name" => "href");
        $input[] = array("tag_name" => "img", "prop_name" => "src");
        foreach($input as $in) {
            $str = self::process_input($in, $str);
        } //end foreach() main
        
        return $str;
        // $left = '<table role="presentation">'; $right = '</table>';
        // $desc = self::remove_all_in_between_inclusive($left, $right, $desc);

    }
    private static function process_input($in, $str)
    {
        $tag = $in['tag_name']; $prop = $in['prop_name'];
        // step 1: replace <a> tags
        if(preg_match_all("/<".$tag." (.*?)<\/".$tag.">/ims", $str, $arr)) { print_r($arr[1]); //exit("\nstop muna...\n");
            /*Array(
                [0] => href='http://eol.org/page/173'>jumps over
            )Array(
                [0] => src='https://mydomain.com/eli.jpg'>My picture.
            )*/
            if($lines = $arr[1]) {
                foreach($lines as $line) { //echo "\n[$line]\n";
                    $href = false;
                    $link_txt = false;
                    if(preg_match("/".$prop."=\'(.*?)\'/ims", $line, $arr2)) {
                        $href = $arr2[1];
                        if(preg_match("/\'>(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                    }
                    elseif(preg_match("/".$prop."=\"(.*?)\"/ims", $line, $arr2)) {
                        $href = $arr2[1];
                        if(preg_match("/\">(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                    }
                    else continue;
                    $href = trim($href);
                    $link_txt = trim($link_txt);
                    if($href && $link_txt) {
                        // echo "\n[$href][$link_txt]\n";
                        $str = self::remove_anchor_tags($href, $link_txt, $str, $line, $tag);
                    }
                } //end foreach() line
            }
        }
        return $str;
    }
    private static function remove_anchor_tags($href, $link_txt, $str, $line, $tag)
    {   /* [http://eol.org/page/173] [jumps over] */
        echo "\nline: [$line]\n";
        echo "\n[$href][$link_txt]\n";
        $last_char = substr($link_txt, -1);
        echo "\nlast char: [$last_char]\n";

        if(in_array($last_char, array(".", ",", ";", "-"))) {
            $link_txt = substr($link_txt,0,strlen($link_txt)-1);
            $target = "$link_txt ($href)$last_char";
        }
        else { //the rest goes here...
            $target = "$link_txt ($href)";
        }

        $line = "<$tag $line</$tag>";
        echo "\nline: [$line]\n";
        echo "\ntarget: [$target]\n";
        $str = str_replace($line, $target, $str);
        return $str;
    }
    private static function is_a_letter($char)
    {
        if(preg_match('/[a-zA-Z]/', $char)) return true;
        return false;
    }

    public static function remove_all_in_between_inclusive($left, $right, $html, $includeRight = true)
    {
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
            foreach($arr[1] as $str) {
                if($includeRight) { //original
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, '', $html);
                }
                else { //meaning exclude right
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, $right, $html);
                }
            }
        }
        return $html;
    }



}
?>