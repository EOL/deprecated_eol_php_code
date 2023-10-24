<?php
namespace php_active_record;
/* connector: [remove_html_tags.php]
*/
class RemoveHTMLTagsAPI
{
    function __construct($folder = null)
    {
        exit("\nDoes not go here... [__construct]\n");
    }

    public static function remove_html_tags($str)
    {   
        $GLOBALS['debug_YN'] = false;
        // $GLOBALS['debug_YN'] = true;

        $orig_str = $str;
        $str = Functions::remove_whitespace($str);
        $str = strip_tags($str, "<a><img>");

        // /* for <img> tags
        $str = self::remove_img_tags($str);
        // */
        
        // /* for <a> tags
        $input = array();
        $input[] = array("tag_name" => "a", "prop_name" => "href");
        // $input[] = array("tag_name" => "img", "prop_name" => "src"); //was handled separately
        foreach($input as $in) {
            $str = self::process_input($in, $str);
        }
        // */

        $str = strip_tags($str); //deletes all tags, and those that are not written properly. e.g. href=http:eol.org/pages/173 -> without quotes
        return $str;
        // $left = '<table role="presentation">'; $right = '</table>';
        // $desc = self::remove_all_in_between_inclusive($left, $right, $desc);

    }
    private static function remove_img_tags($str)
    {
        if(preg_match_all("/<img (.*?)>/ims", $str, $arr)) { //print_r($arr[1]); //exit("\nstop muna...\n");
            /*Array(
                [0] => class="class ko" src="https://mydomain.com/eli.jpg" style="..."
            )*/
            $lines = $arr[1];
            foreach($lines as $line) {
                $orig = "<img $line>";
                $src = false;
                if    (preg_match("/src=\"(.*?)\"/ims", $line, $arr2)) $src = $arr2[1];
                elseif(preg_match("/src=\'(.*?)\'/ims", $line, $arr2)) $src = $arr2[1];
                else exit("\n-ERROR: src not found-\n");
                if($src) {
                    $target = " (image, $src) ";
                    $str = str_replace($orig, $target, $str);
                    $str = Functions::remove_whitespace($str);
                }
            }
        }
        return $str;
    }
    private static function process_input($in, $str)
    {
        $tag = $in['tag_name']; $prop = $in['prop_name'];
        // step 1: replace <a> tags
        if(preg_match_all("/<".$tag." (.*?)<\/".$tag.">/ims", $str, $arr)) { //print_r($arr[1]); //exit("\nstop muna...\n");
            /*Array(
                [0] => href='http://eol.org/page/173'>jumps over
            )
            Array(
                [0] => href=javascript:openNewWindow('http://content.lib.utah.edu/w/d.php?d')>Hear Northern Cricket Frog calls at the Western Sound Archive.
            )*/
            if($lines = $arr[1]) {
                foreach($lines as $line) { //echo "\n[$line]\n";
                    $href = false;
                    $link_txt = false;
                    if(preg_match("/".$prop."=\'(.*?)\'/ims", $line, $arr2)) {
                        $href = $arr2[1];
                        if    (preg_match("/\'>(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                        elseif(preg_match("/\' >(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                        elseif(preg_match("/>(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                        else exit("\nInvestigate 1 cannot get link_txt:\n[$line]\n");    
                    }
                    elseif(preg_match("/".$prop."=\"(.*?)\"/ims", $line, $arr2)) {
                        $href = $arr2[1];
                        if    (preg_match("/\">(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                        elseif(preg_match("/\" >(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                        elseif(preg_match("/>(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                        else exit("\nInvestigate 2 cannot get link_txt:\n[$line]\n");
                    }
                    // elseif(preg_match("/".$prop."=java(.*?)>/ims", $line, $arr2)) {
                    //     $href = "xxx";
                    //     if(preg_match("/\>(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                    // }
                    
                    /* "<a href=javascript:openNewWindow('http://fishbase.org')>FishBase</a>" */
                    /* [href=javascript:openNewWindow('https://sites.google.com/view/debanlab/movies');>here.] */
                    elseif(preg_match("/".$prop."=javascript:openNewWindow\(\'(.*?)\'\)/ims", $line, $arr2)) {
                        $href = $arr2[1];
                        if(preg_match("/\'\)>(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                        if(preg_match("/>(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                        else exit("\nInvestigate 3 cannot get link_txt:\n[$line]\n");
                    }
                    /* '<a href=javascript:openNewWindow("http://fishbase.org")>FishBase</a>' */
                    elseif(preg_match("/".$prop."=javascript:openNewWindow\(\"(.*?)\"\)/ims", $line, $arr2)) {
                        $href = $arr2[1];
                        if(preg_match("/\"\)>(.*?)elicha/ims", $line."elicha", $arr3)) $link_txt = $arr3[1];
                        else exit("\nInvestigate 4 cannot get link_txt:\n[$line]\n");
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
            // else exit("\nnandito pala...\n");
        }
        return $str;
    }
    private static function remove_anchor_tags($href, $link_txt, $str, $line, $tag)
    {   /* [http://eol.org/page/173] [jumps over] */
        if($GLOBALS['debug_YN']) {
            echo "\nline: [$line]\n";
            echo "\n[$href][$link_txt]\n";    
        }
        $last_char = substr($link_txt, -1);
        // echo "\nlast char: [$last_char]\n";

        if(in_array($last_char, array(".", ",", ";"))) {
            $link_txt = substr($link_txt,0,strlen($link_txt)-1);
            if($link_txt != $href)  $target = "$link_txt ($href)$last_char";    //regular assumption
            else                    $target =  "($link_txt)".$last_char;        // e.g. <a href="https://eol.org/page/173" >https://eol.org/page/173;</a>
            $target .= " ";
        }
        else { //the rest goes here...
            if($link_txt != $href) $target = "$link_txt ($href)";   //regular assumption
            else                   $target = "($link_txt)";         // e.g. <a href="https://eol.org/page/173" >https://eol.org/page/173</a>
        }
        // /* special
        if(strtolower(substr($href,0,4)) != 'http') $href = "xxx"; //e.g. <a href='elicha'>http://zoologi.snm.ku.dk</a>
        if($href == "xxx") $target = $link_txt;
        // */

        $line = "<$tag $line</$tag>";
        if($GLOBALS['debug_YN']) {
            echo "\nline: [$line]";
            echo "\ntarget: [$target]\n";    
        }
        $str = str_replace($line, $target, $str);
        $str = Functions::remove_whitespace($str);
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