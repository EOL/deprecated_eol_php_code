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
        // step 1: replace <a> tags
        if(preg_match_all("/<a (.*?)<\/a>/ims", $str, $arr)) { // print_r($arr[1]);
            /*Array(
                [0] => href='http://eol.org/page/173'>jumps over
            )*/
            if($lines = $arr[1]) {
                foreach($lines as $line) { //echo "\n[$line]\n";
                    $href = false;
                    $link_txt = false;
                    if(preg_match("/href=\'(.*?)\'/ims", $line, $arr2)) {
                        $href = $arr2[1];
                        if(preg_match("/\'>(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                    }
                    elseif(preg_match("/href=\"(.*?)\"/ims", $line, $arr2)) {
                        $href = $arr2[1];
                        if(preg_match("/\">(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                    }
                    else continue;
                    $href = trim($href);
                    $link_txt = trim($link_txt);
                    if($href && $link_txt) {
                        // echo "\n[$href][$link_txt]\n";
                        $str = self::remove_anchor_tags($href, $link_txt, $str, $line);
                    }
                } //end foreach()
            }

        }
        $str = strip_tags($str);
        return $str;
        // $left = '<table role="presentation">'; $right = '</table>';
        // $desc = self::remove_all_in_between_inclusive($left, $right, $desc);

    }
    private static function remove_anchor_tags($href, $link_txt, $str, $line)
    {   /* [http://eol.org/page/173] [jumps over] */
        echo "\nline: [$line]\n";
        echo "\n[$href][$link_txt]\n";
        $last_char = substr($link_txt, -1);
        echo "\nlast char: [$last_char]\n";
        if(self::is_a_letter($last_char)) { echo "\nLetter OK\n";
            $target = "$link_txt ($href)";
        }
        else  { echo "\nNot a letter\n";
            $link_txt = substr($link_txt,0,strlen($link_txt)-1);
            $target = "$link_txt ($href)$last_char";
        }

        $line = "<a $line</a>";
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