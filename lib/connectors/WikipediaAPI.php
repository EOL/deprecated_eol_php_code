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
    function remove_edit_sections($html, $url) //remove 'edit' sections and others
    {   /* e.g. es
        <h2>
            <span id="Bibliograf.C3.ADa"></span><span class="mw-headline" id="Bibliografía">Bibliografía</span>
            <span class="mw-editsection">
                <span class="mw-editsection-bracket">[</span>
                <a href="http://es.wikipedia.org/w/index.php?title=Cetacea&amp;action=edit&amp;section=53" title="Editar sección: Bibliografía">editar</a>
                <span class="mw-editsection-bracket">]</span>
            </span>
        </h2>*/
        /* e.g. it
        <h2>
        <span class="mw-headline" id="Etimologia">Etimologia</span>
        <span class="mw-editsection">
            <span class="mw-editsection-bracket">[</span>
            <a href="/w/index.php?title=Panthera_leo&amp;veaction=edit&amp;section=1" class="mw-editsection-visualeditor" title="Modifica la sezione Etimologia">modifica</a>
            <span class="mw-editsection-divider"> | </span>
            <a href="/w/index.php?title=Panthera_leo&amp;action=edit&amp;section=1" title="Modifica la sezione Etimologia">modifica wikitesto</a>
            <span class="mw-editsection-bracket">]</span>
        </span>
        </h2>*/
        $html = str_ireplace('<span class="mw-editsection-bracket">[</span>', '', $html);
        $html = str_ireplace('<span class="mw-editsection-bracket">]</span>', '', $html);
        $html = str_ireplace('<span class="mw-editsection-divider"> | </span>', '', $html);
        if(preg_match_all("/<span class=\"mw-editsection\">(.*?)<\/span>/ims", $html, $arr)) {
            foreach($arr[1] as $str) {
                $substr = '<span class="mw-editsection">'.$str.'</span>';
                $html = str_ireplace($substr, '', $html);
            }
        }
        /* Please remove the srcset and data-file-width elements from all images */ //and probably data-file-height, assumed by Eli
        $removes = array("srcset", "data-file-width", "data-file-height");
        foreach($removes as $remove) {
            if(preg_match_all("/".$remove."=\"(.*?)\"/ims", $html, $arr)) {
                foreach($arr[1] as $str) {
                    $substr = $remove.'="'.$str.'"';
                    // echo "\n --- $substr --- \n";
                    $html = str_ireplace($substr, '', $html);
                }
            }
        }
        /* remove everything after the end of the Bibliografía section. */
        $first10langs = array("en", "es", "it", "de", "fr", "zh", "ru", "pt", "ja", "ko");
        if(in_array($this->language_code, $first10langs)) $html = self::remove_everything_after_bibliographic_section($html);
        else                                              $html = self::remove_categories_section($html, $url, $this->language_code); //seems can also be used for the first 10 languages :-)
        $html = self::remove_ctex_verion_spans($html);
        return $html;
    }
    private function remove_ctex_verion_spans($html)
    {   /*
    <span title="ctx_ver=Z39.88-2004&rfr_id=info%3Asid%2Fes.wikipedia.org%3APanthera+leo&rft.au=Baratay%2C+Eric&rft.aufirst=Eric&rft.aulast=Baratay&rft.btitle=Zoo%3A+a+history+of+zoological+gardens+in+the+West&rft.date=2002&rft.genre=book&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook">
    <span> </span></span> 
    <span>La referencia utiliza el parámetro obsoleto <code>|coautores=</code> (<a href="http://es.wikipedia.org/wiki/Ayuda:Errores_en_las_referencias#deprecated_params" title="Ayuda:Errores en las referencias">ayuda</a>)</span>

    <span title="ctx_ver=Z39.88-2004&rfr_id=info%3Asid%2Fes.wikipedia.org%3APanthera+leo&rft.au=Baratay%2C+Eric&rft.aufirst=Eric&rft.aulast=Baratay&rft.btitle=Zoo%3A+a+history+of+zoological+gardens+in+the+West&rft.date=2002&rft.genre=book&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook"><span> </span></span> <span>La referencia utiliza el parámetro obsoleto <code>|coautores=</code> (<a href="http://es.wikipedia.org/wiki/Ayuda:Errores_en_las_referencias#deprecated_params" title="Ayuda:Errores en las referencias">ayuda</a>)</span>
        */
        $html = str_ireplace("<span> </span>", "", $html);
        if(preg_match_all("/<span title=\"ctx_ver=(.*?)<\/span>/ims", $html, $arr)) {
            foreach($arr[1] as $str) {
                $substr = '<span title="ctx_ver='.$str.'</span>';
                // echo "\n --- $substr --- \n";
                $html = str_ireplace($substr, '', $html);
            }
        }
        return $html;
    }
    private function bibliographic_section_per_language()
    {
        if($this->language_code == "es") return '<span class="mw-headline" id="Bibliografía">Bibliografía</span>';
        if($this->language_code == "it") return '<span class="mw-headline" id="Bibliografia">Bibliografia</span>';
        if($this->language_code == "en") return '<span class="mw-headline" id="References">References</span>';
        if($this->language_code == "de") return '<span class="mw-headline" id="Einzelnachweise">Einzelnachweise</span>';
        if($this->language_code == "ko") return '<span class="mw-headline" id="각주">각주</span>';
        if($this->language_code == "fr") return '<span class="mw-headline" id="Notes_et_références">Notes et références</span>';
        if($this->language_code == "ru") return '<span class="mw-headline" id="Примечания">Примечания</span>';
        if($this->language_code == "pt") return '<span class="mw-headline" id="Bibliografias">Bibliografias</span>';
        if($this->language_code == "zh") return '<span class="mw-headline" id="參考資料">參考資料</span>';
        if($this->language_code == "ja") return '<span class="mw-headline" id="脚注">脚注</span>';
        /* not used
        if($this->language_code == "nl") return '<span class="mw-headline" id="Literatuur">Literatuur</span>';
        if($this->language_code == "pl") return '<span class="mw-headline" id="Bibliografia">Bibliografia</span>';
        if($this->language_code == "sv") return '<span class="mw-headline" id="Referenser">Referenser</span>';          //may have other options
        if($this->language_code == "vi") return '<span class="mw-headline" id="Chú_thích">Chú thích</span>';            //may have other options
        */
    }
    private function get_section_name_after_bibliographic_section($html, $biblio_section = false)
    {
        if(!$biblio_section) $biblio_section = self::bibliographic_section_per_language();
        if(preg_match_all("/<h2>(.*?)<\/h2>/ims", $html, $arr)) {
            // if($GLOBALS['ENV_DEBUG']) print_r($arr[1]);
            $i = -1;
            foreach($arr[1] as $tmp) {
                $i++;
                if(stripos($tmp, $biblio_section) !== false) return @$arr[1][$i+1]; //string is found
            }
        }
        return false;
    }
    function remove_everything_after_bibliographic_section($html)
    {
        if($section_after_biblio = self::get_section_name_after_bibliographic_section($html)) { //for en, es, it, so far
            debug("\nsection_after_biblio: [$section_after_biblio]\n");
            if(preg_match("/xxx(.*?)".preg_quote($section_after_biblio,'/')."/ims", "xxx".$html, $arr)) return $arr[1];
        }
        else {
            debug("\nNo section after biblio detected [$this->language_code]\n");
            /* start customize per language: */
            if($this->language_code == "fr") {
                $section_after_biblio = '<ul id="bandeau-portail" class="bandeau-portail">';
                if(preg_match("/xxx(.*?)".preg_quote($section_after_biblio,'/')."/ims", "xxx".$html, $arr)) return $arr[1];
            }
            elseif($this->language_code == "ru") { //another suggested biblio_section for 'ru'
                if($ret = self::second_try_sect_after_biblio('<span class="mw-headline" id="В_культуре">В культуре</span>', $html)) return $ret;
            }
            elseif($this->language_code == "pt") {
                if($ret = self::second_try_sect_after_biblio('<span class="mw-headline" id="Referencias">Referencias</span>', $html)) return $ret;
                if($ret = self::second_try_sect_after_biblio('<span class="mw-headline" id="Bibliografia">Bibliografia</span>', $html)) return $ret;
            }
            elseif($this->language_code == "zh") {
                if($ret = self::second_try_sect_after_biblio('<span class="mw-headline" id="參考文獻">參考文獻</span>', $html)) return $ret;
            }
            else debug("\n---No contingency for [$this->language_code]\n");
            /* end customize */
        }
        
        if($this->language_code == "de") { /* <table id="Vorlage_Exzellent" <table id="Vorlage_Lesenswert" */
            if(preg_match("/<table id=\"Vorlage_Exzellent(.*?)xxx/ims", $html."xxx", $arr)) {
                $substr = '<table id="Vorlage_Exzellent'.$arr[1].'xxx';
                $html = str_ireplace($substr, '', $html."xxx");
            }
            if(preg_match("/<div id=\"normdaten\" class=\"catlinks(.*?)xxx/ims", $html."xxx", $arr)) {
                $substr = '<div id="normdaten" class="catlinks'.$arr[1].'xxx';
                $html = str_ireplace($substr, '', $html."xxx");
            }
            elseif(preg_match("/<div id=\"catlinks\" class=\"catlinks\"(.*?)xxx/ims", $html."xxx", $arr)) {
                $substr = '<div id="catlinks" class="catlinks"'.$arr[1].'xxx';
                $html = str_ireplace($substr, '', $html."xxx");
            }
        }
        elseif($this->language_code == "fr") { /* <div class="navbox-container" style="clear:both;"> */
            // exit("\n$html\n");
            if(preg_match("/<div class=\"navbox-container\"(.*?)xxx/ims", $html."xxx", $arr)) {
                $substr = '<div class="navbox-container"'.$arr[1].'xxx';
                $html = str_ireplace($substr, '', $html."xxx");
                // exit("\n".$html."\n001\n\n");
            }
        }
        else { //for ko
            if(preg_match("/<div role=\"navigation\" class=\"navbox\"(.*?)xxx/ims", $html."xxx", $arr)) {
                $substr = '<div role="navigation" class="navbox"'.$arr[1].'xxx';
                $html = str_ireplace($substr, '', $html."xxx");
            }
            // may use in the future
            // if(preg_match("/<div class=\"navbox(.*?)xxx/ims", $html."xxx", $arr)) {
            //     $substr = '<div class="navbox'.$arr[1].'xxx';
            //     $html = str_ireplace($substr, '', $html."xxx");
            // }
        }
        return $html;
    }
    private function second_try_sect_after_biblio($biblio_section, $html)
    {
        if($section_after_biblio = self::get_section_name_after_bibliographic_section($html, $biblio_section)) {
            debug("\nsection_after_biblio: [$section_after_biblio]\n");
            if(preg_match("/xxx(.*?)".preg_quote($section_after_biblio,'/')."/ims", "xxx".$html, $arr)) return $arr[1];
        }
    }
}
?>
