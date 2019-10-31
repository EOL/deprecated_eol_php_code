<?php
namespace php_active_record;
class WikipediaAPI
{
    function __construct()
    {
    }
    function remove_categories_section($html, $url, $language_code)
    {   /* should end here:
        <noscript><img src="//nl.wikipedia.org/wiki/Special:CentralAutoLogin            ---> orig when doing view source html
        <noscript><img src="https://nl.wikipedia.org/wiki/Special:CentralAutoLogin      ---> actual value of $html (IMPORTANT REMINDER)
        */
        $limit = '<noscript><img src="https://'.$language_code.'.wikipedia.org/wiki/Special:CentralAutoLogin';
        if(stripos($html, $limit) !== false) { //string is found
            if(preg_match("/xxx(.*?)".preg_quote($limit,'/')."/ims", "xxx".$html, $arr)) {
                $final = $arr[1];
                /* stats count - debug only
                echo "\n start div: ".substr_count($final, '<div')."\n";
                echo "\n end div: ".substr_count($final, '</div')."\n"; exit;
                */
                $html = $final; //since there are additional steps below
            }
        }
        else {
            // echo "\n--- $html ---\n";
            echo("\n-----\nNot found, investigate [$language_code]\n[$url]\n-----\n"); //Previously exits here.
            // Cause for investigation, check final wiki if OK, since we continued process for now.
        }
        
        /* additional sections to remove e.g. lang 'nl' for Mus musculus */
        $html = self::code_the_steps('<table class="navigatiesjabloon"', '</tbody></table>', $html);
        $html = self::code_the_steps('<div id="normdaten"', '</div>', $html);
        
        /* sv Mus musculus */
        $html = self::code_the_steps('<table class="navbox"', '</table></td></tr></tbody></table>', $html, true);
        
        /* for 'no' */
        $html = self::code_the_steps('<table class="navbox hlist"', '</table></td></tr></tbody></table>', $html);

        if($language_code == "hu") { /* for hu Eli updates: 10-30-2019 */
            $html = self::code_the_steps('<table cellspacing="0" class="nowraplinks mw-collapsible mw-autocollapse"', '</tbody></table>', $html);
            $html = self::code_the_steps('<table class="navbox noprint noviewer"', '</div></div>', $html);
            $html = self::code_the_steps('<table class="navbox authoritycontrol"', '</div></div>', $html);
        }

        /* for 'ca' */
        $html = self::code_the_steps('<div role="navigation" class="navbox"', '</tbody></table></div>', $html, true);
        $html = self::code_the_steps('<div style="right:10px; display:none;" class="topicon">', '</div>', $html);
        
        /* for uk */
        $html = self::code_the_steps('<table cellspacing="0" class="navbox"', '</table></td></tr></tbody></table>', $html);
        if($language_code == "uk") {
            $html = self::code_the_steps('<table align="center" border="0" cellpadding="0" cellspacing="4" class="metadata">', '</table>', $html);
            $html = self::code_the_steps('<div id="catlinks" class="catlinks"', '</div></div>', $html);
        }
        
        /* for cs */
        $html = self::code_the_steps('<div id="portallinks" class="catlinks"', '</div>', $html, true);
        $html = self::code_the_steps('<div class="catlinks"', '</div>', $html, true);
        
        return $html;
    }
    function code_the_steps($left, $right, $html, $multiple = false)
    {
        if($multiple) {
            if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                foreach($arr[1] as $str) {
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, '', $html);
                }
            }
        }
        else {
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                $substr = $left.$arr[1].$right;
                $html = str_ireplace($substr, '', $html);
            }
        }
        return $html;
    }
}
?>
