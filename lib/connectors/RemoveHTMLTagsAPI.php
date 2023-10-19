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
        // step 1: replace <a> tags
        if(preg_match_all("/<a (.*?)<\/a>/ims", $str, $arr)) { // print_r($arr[1]);
            /*Array(
                [0] => href='http://eol.org/page/173'>jumps over
            )*/
            if($lines = $arr[1]) {
                foreach($lines as $line) { echo "\n[$line]\n";
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
                    if($href && $link_txt) {
                        echo "\n[$href][$link_txt]\n";
                    }
                }
            }

        }

        // $left = '<table role="presentation">'; $right = '</table>';
        // $desc = self::remove_all_in_between_inclusive($left, $right, $desc);

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