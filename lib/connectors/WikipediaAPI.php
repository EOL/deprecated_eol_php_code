<?php
namespace php_active_record;
class WikipediaAPI
{
    function __construct()
    {
    }
    function some_initialization()
    {   //for wikipedia redirect issues
        if($this->language_code == 'be-x-old') $this->language_code = 'be-tarask';
        
        //some default translations:
        $trans['Page']['en'] = "Page";
        $trans['Modified']['en'] = "Modified";
        $trans['Retrieved']['en'] = "Retrieved";
        $trans['Page']['de'] = "Seite";
        $trans['Modified']['de'] = "Bearbeitungsstand";
        $trans['Retrieved']['de'] = "Abgerufen";
        $trans['Page']['es'] = "Página";
        $trans['Modified']['es'] = "Modificado";
        $trans['Retrieved']['es'] = "Recuperado";
        $trans['Page']['fr'] = "Page";
        $trans['Modified']['fr'] = "Modifié";
        $trans['Retrieved']['fr'] = "Récupéré";
        
        /* *** szl nv pnb br mrj nn hsb pms azb sco zh-yue ia oc qu koi frr udm ba an zh-min-nan sw te io kv csb fo os cv kab sah nds lmo pa wa vls gv wuu nah dsb kbd to mdf 
               li as olo mhr pcd vep se gn rue ckb bh myv scn dv pam xmf cdo bar nap lfn vo nds-nl bo stq --> to avoid re-doing lookup_cache() knowing the remote won't respond */
        /*
        $lang = 'stq';
        $trans['Page'][$lang] = "Page";
        $trans['Modified'][$lang] = "Modified";
        $trans['Retrieved'][$lang] = "Retrieved";
        $trans['Wikipedia authors and editors'][$lang] = "Wikipedia authors and editors";
        */
        
        // assignments for languages without default values:
        $func = new WikipediaRegionalAPI($this->resource_id, $this->language_code);
        $terms = array('Wikipedia authors and editors', 'Page', 'Modified', 'Retrieved');
        foreach($terms as $term) {
            if($val = @$trans[$term][$this->language_code]) $this->pre_trans[$term] = $val;
            else                                            $this->pre_trans[$term] = $func->translate_source_target_lang($term, "en", $this->language_code);
        }
        $this->trans['editors'][$this->language_code] = $this->pre_trans['Wikipedia authors and editors'];
    }
    function taxon_wiki_per_language_stats($sitelinks)
    {
        // print_r($sitelinks); exit;
        /* 1st version: OK but triggers API calls. Better to use what the json dump gives.
        foreach($sitelinks as $key => $val) { // echo "\n".$val->url;
            // https://za.wikipedia.org/wiki/Saeceij
            // https://zh-min-nan.wikipedia.org/wiki/Sai
            // https://zh-yue.wikipedia.org/wiki/%E7%8D%85%E5%AD%90
            // https://zh.wikipedia.org/wiki/%E7%8B%AE
            // https://zu.wikipedia.org/wiki/Ibhubesi
            if(preg_match("/\/\/(.*?)\.wikipedia/ims", $val->url, $arr)) {
                $lang_abbrev = $arr[1];
                // echo " -- $lang_abbrev";
                @$this->count_taxon_wiki_per_lang[$lang_abbrev]++;
            }
        }
        */
        
        /* start 2nd version
        Array(
            [commonswiki] => stdClass Object(
                    [site] => commonswiki
                    [title] => Panthera leo
                    [badges] => Array(
                        )
                )
            [eswikiquote] => stdClass Object(
                    [site] => eswikiquote
                    [title] => León
                    [badges] => Array(
                        )
                )
            [hywwiki] => stdClass Object(
                     [site] => hywwiki
                     [title] => Առիւծ
                     [badges] => Array(
                         )
                 )
            [nds_nlwiki] => stdClass Object(
                 [site] => nds_nlwiki
                 [title] => Leeuw
                 [badges] => Array(
                     )
        )*/
        $keys = array_keys($sitelinks);
        // print_r($keys); exit;
        foreach($keys as $key) {
            $key = str_replace('_', '-', $key);
            $exclude = "commonswiki,wikiquote,wiktionary,voyage,news,books,source,versity,species";
            $exclude = explode(',', $exclude);
            $cont = false;
            foreach($exclude as $str) {
                if(stripos($key, $str) !== false) { //string is found
                    $cont = true;
                    break;
                }
            }
            if($cont) continue;
            $lang_abbrev = str_replace('wiki', '', $key);
            @$this->count_taxon_wiki_per_lang[$lang_abbrev]++;
        }
    }
    function eli_sort($multi_array)
    {
        $data = array();
        foreach($multi_array as $key => $value) $data[] = array('language' => $key, 'count' => $value);
        // Obtain a list of columns
        /* before PHP 5.5.0
        foreach ($data as $key => $row) {
            $language[$key]  = $row['language'];
            $count[$key] = $row['count'];
        }
        */
        
        // as of PHP 5.5.0 you can use array_column() instead of the above code
        $language  = array_column($data, 'language');
        $count = array_column($data, 'count');

        // Sort the data with language descending, count ascending
        // Add $data as the last parameter, to sort by the common key
        array_multisort($count, SORT_DESC, $language, SORT_ASC, $data);
        return $data;
    }
    function create_wikipedia_object($media) //for wikipedia only
    {
        $media['description'] = self::last_html_clean($media['description']);
        // /*
        $row = "";
        $i = 0;
        $total_cols = count($this->media_cols);
        foreach($this->media_cols as $key) {
            $i++;
            $row .= $media[$key];
            if($i == $total_cols) $row .= "\n";
            else                  $row .= "\t";
        }
        
        /* good debug to write to HTML for testing ***
        if($media['CVterm'] == 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description')  $file = DOC_ROOT.$this->debug_taxon.".html";
        if($media['CVterm'] == 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology') $file = DOC_ROOT."TaxonBiology.html";
        echo "\nfile: [$file]\n";
        $f = Functions::file_open($file, "w");
        fwrite($f, $media['description']);
        fclose($f); //exit;
        */
        
        if(!isset($this->object_ids[$media['identifier']])) {
            $this->object_ids[$media['identifier']] = '';
            if(!($f = Functions::file_open($this->media_extension, "a"))) return;
            fwrite($f, $row);
            fclose($f);
        }
        // */

        // /*
        if(!$this->passed_already) {
            $this->passed_already = true;
            echo "\nshould pass here only once\n";
            $mr = new \eol_schema\MediaResource(); //for Wikipedia objects only --- it is just used to make a fake meta.xml
            $mr->taxonID                = $media['taxonID'];
            $mr->identifier             = $media['identifier'];
            $mr->type                   = $media['type'];
            $mr->format                 = $media['format'];
            $mr->language               = $media['language'];
            $mr->UsageTerms             = $media['UsageTerms'];
            $mr->CVterm                 = $media['CVterm'];
            $mr->description            = "test data"; //$media['description'];
            $mr->furtherInformationURL  = $media['furtherInformationURL'];
            $mr->title                  = $media['title'];
            $mr->Owner                  = $media['Owner'];
            $this->archive_builder->write_object_to_file($mr);
        }
        // */
        /*  $mr = new \eol_schema\MediaResource();
            $mr->taxonID                = $media['taxonID'];
            $mr->identifier             = $media['identifier'];
            $mr->type                   = $media['type'];
            $mr->format                 = $media['format'];
            $mr->language               = $media['language'];
            $mr->UsageTerms             = $media['UsageTerms'];
            $mr->CVterm                 = $media['CVterm'];
            $mr->description            = $media['description'];
            $mr->furtherInformationURL  = $media['furtherInformationURL'];
            $mr->title                  = $media['title'];
            $mr->Owner                  = $media['Owner'];
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        */
    }
    private function last_html_clean($html)
    {
        $html = trim($html);
        $remove_last_tags = array("<h2>", "<p>", "<b>");
        foreach($remove_last_tags as $tag) {
            $length = strlen($tag);
            if(substr($html, -1*$length) == $tag) $html = substr($html, 0, -1*$length);
        }
        return $html;
    }
    function retrieve_info_on_bot_wikis()
    {   
        /* 1st step: is to check if wikipedia_bot_file needs normalizing, meaning making each row unique. Remove duplicates. */
        self::make_wikipedia_bot_file_unique();
        /* 2nd step: retrieval proper */
        self::main_retrieval(); //this creates $this->title_is_bot
    }
    private function main_retrieval() //this creates $this->title_is_bot
    {
        $this->title_is_bot = array();
        if(file_exists($this->wikipedia_bot_file)) {
            foreach(new FileIterator($this->wikipedia_bot_file) as $line_number => $row) {
                if($val = trim($row)) $this->title_is_bot[$val] = '';
            }
        }
        echo "\nTitles that are bot-created: "; 
        if(count($this->title_is_bot) < 500) print_r($this->title_is_bot);
        else echo count($this->title_is_bot);
    }
    private function make_wikipedia_bot_file_unique()
    {
        if(file_exists($this->wikipedia_bot_file)) {
            $date_today = date("y-m-d",time());
            $last_modified = date("y-m-d", filemtime($this->wikipedia_bot_file));
            if($last_modified < $date_today) { //then we'll need to check and remove duplicates if there are any...
                self::main_retrieval(); //this creates $this->title_is_bot
                $temp_file = temp_filepath();
                if(!($f = Functions::file_open($temp_file, "w"))) return;
                foreach($this->title_is_bot as $row => $blank) {
                    if($row) fwrite($f, $row."\n");
                }
                fclose($f);
                Functions::file_rename($temp_file, $this->wikipedia_bot_file);
            }
        }
    }
    private function log_bot_wiki($title)
    {
        if(!($f = Functions::file_open($this->wikipedia_bot_file, "a"))) return;
        fwrite($f, $title."\n");
        fclose($f);
    }
    function get_other_info($rek)
    {
        $func_region = new WikipediaRegionalAPI($this->resource_id, $this->language_code);
        if($title = $rek['sitelinks']->title) {
            // $title = "Dicorynia"; //debug
            $url = "https://" . $this->language_code . ".wikipedia.org/wiki/" . str_replace(" ", "_", $title);
            $domain_name = $func_region->get_domain_name($url);

            $options = $this->download_options;
            // if($rek['taxon_id'] == "Q5113") $options['expire_seconds'] = true; //debug only force

            if($html = Functions::lookup_with_cache($url, $options)) { //preferabley monthly expires
                if(self::bot_inspired($html)) {
                    // echo("\nbot inspired: [$url]\n");
                    self::log_bot_wiki(str_replace(" ", "_", $title));
                    return $rek;
                }
                $rek['other'] = array();
                $html = self::remove_infobox($html); //new DATA-1785
                $html = $func_region->prepare_wiki_for_parsing($html, $domain_name);
                $rek['other']['title'] = $title;

                $desc = $func_region->get_comprehensive_desc($html);
                if(!$desc) return $rek;
                $desc = self::remove_edit_sections($desc, $url); //new https://eol-jira.bibalex.org/browse/DATA-1785
                $rek['other']['comprehensive_desc'] = self::additional_desc_format($desc);
                
                // $rek['other']['comprehensive_desc'] = "the quick brown fox jumps over the lazy dog...";  //debug
                $rek['other']['brief_summary'] = self::create_brief_summary($rek['other']['comprehensive_desc']);
                $rek['other']['permalink']        = $func_region->get_permalink($html);
                $rek['other']['last_modified']    = $func_region->get_last_modified($html);
                $rek['other']['phrase']           = $func_region->get_wikipedia_phrase($html);
                $rek['other']['citation']         = $func_region->get_citation($rek['other']['title'], $rek['other']['permalink'], $rek['other']['last_modified'], $rek['other']['phrase'], $this->pre_trans);
                
                // /* if TaxonBiology == Description; then disregard TaxonBiology
                $var1 = trim(strip_tags($rek['other']['comprehensive_desc']));
                $var2 = trim(strip_tags($rek['other']['brief_summary']));
                if($var1 == $var2) $rek['other']['brief_summary'] = '';
                // */
                
            }
            else echo("\ncannot lookup\n[$url] [$this->language_code]\n");
        }
        return $rek;
    }
    private function create_brief_summary($desc)
    {
        $tmp = Functions::get_str_up_to_this_chars_only($desc, "<h2");
        $tmp = WikiDataAPI::remove_space($tmp);
        $tmp = strip_tags($tmp,'<table><tr><td><a><img><br><p>');
        $tmp = Functions::exclude_str_before_this_chars($tmp, "</table>"); //3rd param by default is "last" occurrence

        // remove inline anchor e.g. <a href="#cite_note-1">[1]</a>
        if(preg_match_all("/<a href=\"#(.*?)<\/a>/ims", $tmp, $arr)) {
            foreach($arr[1] as $item) {
                $tmp = str_replace('<a href="#'.$item.'</a>', "", $tmp);
            }
        }

        $tmp = trim(str_ireplace('<p> </p>', "<p></p>", $tmp));
        $arr = array("<p></p>");
        $tmp = trim(str_ireplace($arr, "", $tmp));
        /* debug
        echo "\n----------------------------------Brief Summary";
        echo "\n[".$tmp."]";
        echo "\n---------------------------------- no tags";
        echo "\n[".strip_tags($tmp)."]";
        echo "\n----------------------------------\n";
        */
        
        $tmp = Functions::remove_this_last_char_from_str($tmp, "|"); //first client is Q13182 for language 'hi' Hindi
        
        if(trim(strip_tags($tmp)) == '') return '';
        return $tmp;
    }
    private function additional_desc_format($desc)
    {   //new: https://eol-jira.bibalex.org/browse/DATA-1800?focusedCommentId=63385&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63385
        $classes2remain = self::get_class_names_from_UL_tags($desc);
        // print_r($classes2remain);
        
        // remove class and style attributes in tags
        // e.g. class="infobox biota" 
        // e.g. style="text-align: left; width: 200px; font-size: 100%"
        if(preg_match_all("/class=\"(.*?)\"/ims", $desc, $arr)) {
            $tmp = array_unique($arr[1]);
            $tmp = array_values($tmp); //reindex
            foreach($tmp as $item) {
                if(!in_array($item, $classes2remain)) $desc = str_replace('class="'.$item.'"', "", $desc);
            }
        }
        if(preg_match_all("/style=\"(.*?)\"/ims", $desc, $arr)) {
            foreach($arr[1] as $item) $desc = str_replace('style="'.$item.'"', "", $desc);
        }
        
        /* remove <style> tags e.g. <style data-mw-deduplicate="TemplateStyles:r151431924">.mw-parser-output h1 #sous_titre_h1{display:block;font-size:0.7em;line-height:1.3em;margin:0.2em 0 0.1em 0.5em}</style> */
        if(preg_match_all("/<style (.*?)<\/style>/ims", $desc, $arr)) {
            foreach($arr[1] as $item) $desc = str_replace('<style '.$item.'</style>', "", $desc);
        }
        
        // removes html comments <!-- ??? -->
        if(preg_match_all("/<\!\-\-(.*?)\-\->/ims", $desc, $arr)) {
            foreach($arr[1] as $item) $desc = str_replace('<!--'.$item.'-->', "", $desc);
        }

        $desc = Functions::remove_whitespace($desc);
        $desc = str_replace(' >',">",$desc);

        $arr = array("<p></p>","<div></div>");
        $desc = str_ireplace($arr, "", $desc);
        $desc = trim(WikiDataAPI::remove_space($desc));

        // echo "\n----------------------------------Comprehensive Desc";
        // echo "\n[".$desc."]";
        // echo "\n----------------------------------\n";
        
        /* for sr Eli updates: 10-25-2019 */
        $left = '<table role="presentation">'; $right = '</table>';
        $desc = self::remove_all_in_between_inclusive($left, $right, $desc);
        /* <span id="Spolja.C5.A1nje_veze"></span><span id="Spoljašnje_veze">Spoljašnje veze</span> */
        $left = '<span id="Spolja'; $right = '</span>';
        $desc = self::remove_all_in_between_inclusive($left, $right, $desc);
        
        if($this->language_code == 'af') {
            $left = '<div role="navigation"'; $right = '</div>';
            $desc = self::remove_all_in_between_inclusive($left, $right, $desc);
        }
        
        $desc = str_replace("<hr /> <hr />", "<hr />", $desc);
        $desc = str_ireplace('<p><br /> </p>', '', $desc);
        
        if($this->language_code == 'mn') {
            if(substr($desc,0,43) == '<div> </td></tr> </tbody></table> <p><br />') {
                $desc = str_ireplace('<div> </td></tr> </tbody></table> <p><br /> ', '<div><p>', $desc);
            }
        }

        if($this->language_code == 'vls') {
            $left = '<dl><dd><small><i>'; $right = '</i></small></dd></dl>';
            $desc = self::remove_all_in_between_inclusive($left, $right, $desc, true);
        }
        
        if($this->language_code == 'li') {
            $desc = trim(str_replace("<div> <hr />", "", $desc));
        }
        
        if($this->language_code == 'or') {
            $left = '<span><div align="right">'; $right = '</div></span>';
            $desc = self::remove_all_in_between_inclusive($left, $right, $desc, true);
        }
        
        $desc = str_ireplace('<p><br />', '<p>', $desc);
        
        // /* final test
        $test = trim(strip_tags($desc));
        if(!$test) $desc = '';
        if(str_word_count($test) <= 4) $desc = '';
        // */
        
        return $desc;
    }
    private function remove_all_in_between_inclusive($left, $right, $html, $includeRight = true)
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
    private function get_class_names_from_UL_tags($html)
    {   // e.g. from DATA-1800 "gallery mw-gallery-traditional", "gallery mw-gallery-packed"
        $final = array();
        if(preg_match_all("/<ul(.*?)>/ims", $html, $arr)) {
            $tmp = array_unique($arr[1]);
            $tmp = array_values($tmp); //reindex
            foreach($tmp as $str) {
                if(preg_match("/class=\"(.*?)\"/ims", $str, $arr)) $final[$arr[1]] = '';
            }
        }
        return array_keys($final);
    }
    private function code_the_steps_v2($left, $right, $right2, $html)
    {
        $orig = $html;
        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
            $substr = $left.$arr[1]; //.$right; //no right term here
            $html = str_ireplace($substr, '', $html);
            
            if(stripos($html, "Special:CentralAutoLogin") !== false) {} //string is found
            else { //try ending with '<p>'. Means using '<div class' erased most of the article already
                if($right2) {
                    $html = $orig;
                    $left = '<table class="toccolours"';
                    if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right2, '/')."/ims", $html, $arr)) {
                        $substr = $left.$arr[1]; //.$right; //no right term here
                        $html = str_ireplace($substr, '', $html);
                    }
                }
            }
        }
        return $html;
    }
    private function delete_this_point_and_beyond($str, $html) //working but not yet used
    {
        $html = "123456789"; $str="56"; //good debug
        if($pos = strpos($html, $str)) {
            $html = substr($html,0,$pos);
            exit("\n$pos\n[$html]\n");
        }
        return $html;
    }
    private function get_pre_tag_entry($html, $right, $start_tag = "<")
    {
        $minus = strlen($start_tag);
        if($pos = strpos($html, $right)) { // echo "\npos = [$pos]\n";
            $orig_pos = $pos;
            $char = '';
            $sought = array();
            while($char != $start_tag) {
                $char = substr($html, $pos-$minus, strlen($start_tag));
                // echo "\n[$char]";
                $sought[] = $char;
                $pos = $pos - 1;
                if($pos <= 1) return '';
            }
            $sought = array_reverse($sought);
            $sought = implode("", $sought); // echo "\n[$sought]\n";
            return $sought;
        }
        // else debug("\nNeedle not found\n");
    }
    private function does_external_links_came_last($html)
    {
        // exit("\n<h2>\n".substr($html,0,10)."\n<h2>\n");
        // exit($html);
        /*
        Liens_externes          Lien_externe
        Notes_et_références     Références
        '<span class="mw-headline" id="Liens_externes"'
        */

        $count_1a = 0; $count_2a = 0;
        $left = '<!DOCTYPE'; $right = '<span class="mw-headline" id="Liens_externes"';
        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) $count_1a = strlen($arr[1]);
        $left = '<!DOCTYPE'; $right = '<span class="mw-headline" id="Notes_et_références"';
        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) $count_2a = strlen($arr[1]);
        // echo("\n[$count_1a] [$count_2a]\n");
        if($count_1a && $count_2a) {
            if($count_1a < $count_2a) return false;
            else return true;
        }

        $count_1b = 0; $count_2b = 0;
        $left = '<!DOCTYPE'; $right = '<span class="mw-headline" id="Liens_externes"';
        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) $count_1b = strlen($arr[1]);
        $left = '<!DOCTYPE'; $right = '<span class="mw-headline" id="Notes_et_références"';
        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) $count_2b = strlen($arr[1]);
        // echo("\n[$count_1b] [$count_2b]\n");
        if($count_1b && $count_2b) {
            if($count_1b < $count_2b) return false;
            else return true;
        }

        if($count_2a == 0 && $count_2b == 0) return true; //e.g. Pacific halibut
        // exit("\n<h2>\n");
    }
    private function remove_infobox($html) //and html form elements e.g. <input type...>
    {
        //remove all the time
        $left = '<span style="display:none; visibility:hidden">'; $right = '</span>';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        
        $left = '<table style="margin-bottom:5px; margin-top:5px; margin: 0 10%; border-collapse: collapse; background: #fbfbfb; border: 1px solid #aaa; border-left: 10px solid #1e90ff; border-left: 10px solid #1e90ff;;Shkencë" class="metadata plainlinks noprint">'; $right = '</table>';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

        $left = '<div class="shortdescription nomobile noexcerpt noprint searchaux" style="display:none"'; $right = '</div>';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        
        $left = '<div class="boilerplate metadata"'; $right = '</div>';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        
        $left = '<div class="dablink"'; $right = '</div>';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        
        $left = '<table class="metadata plainlinks ambox ambox-'; $right = '</table>';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

        $left = '<table class="metadata plainlinks stub"'; $right = '</table>';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

        $left = '<div class="notice metadata"'; $right = '</div>';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

        if($this->language_code != 'pam') { //
            $left = '<div class="infobox sisterproject"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        
        $left = '<table style="background:none; text-align:left; padding:2px 0;" class="metadata"'; $right = '<!--';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        
        $needle = 'class="metadata plainlinks ambox ambox-';
        if($tmp = self::get_pre_tag_entry($html, $needle)) {
            $left = $tmp . $needle; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        
        $needle = 'class="noprint plainlinks ambox ambox-';
        if($tmp = self::get_pre_tag_entry($html, $needle)) {
            $left = $tmp . $needle; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }

        //section below
        $left = '<div class="boilerplate"'; $right = '</div>';
        $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        
        /* -------------------------------------------- customized below -------------------------------------------- */
        if($this->language_code == 'de') { //
            //infobox e.g. Coronaviridae
            // <table cellpadding="2" class="float-right taxobox" id="Vorlage_Infobox_Virus"
            $needle = 'id="Vorlage_Infobox';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $rights = array('<p>Die <i>', '<p><i><b>', '<p><b>');
                foreach($rights as $right) {
                    $left = $tmp . $needle;
                    $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
                }
            }
            
            //another infobox
            $left = '<table id="Vorlage_'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //section above
            $left = '<table id="Vorlage_'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //weblinks for de - weblinks removed but not section after it
            $left = '<span class="mw-headline" id="Weblinks">'; $right = '<span class="mw-headline" id="';
            $html2 = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            if($html != $html2) $html = $html2;
            else {
                $left = '<span class="mw-headline" id="Weblinks">'; $right = '<!--';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }

            //section below
            $left = '<div class="BoxenVerschmelzen">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below again
            // <div class="NavFrame navigation-not-searchable"
            $left = '<div class="NavFrame'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            // unique to de maybe, or not
            $left = '<div class="printfooter">'; $right = '<div id="mw-navigation">';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            // <div id="catlinks" class="catlinks"
            $left = '<div id="catlinks"'; $right = '<div id="mw-navigation">';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'stq') { //
            //section above
            $left = '<div class="center" style="width:auto; margin-left:auto; margin-right:auto;">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //infobox
            // <table border="1" cellspacing="0" style="float:right;margin-left:0.5em">
            $needle = 'style="float:right;margin-left:0.5em"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $rights = array('<p>Die <b>', '<p>Doo <i><b>', '<p>Doo <b>', '<p>Do <b>', '<p>Do <i><b>', '<p>Ne <b>', '<p>Juu <b>', '<p>Ju <b>', '<p><i><b>', '<p><b>');
                foreach($rights as $right) {
                    $left = $tmp . $needle;
                    $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
                }
            }
            
            //section below
            $left = '<table align="left" width="50%" id="toc">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            // <table style="margin:0.5em 0 0.5em 0; clear:both" width="50%" class="toccolours">
            $needle = 'class="toccolours"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<!--';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //external links
            $left = '<span class="mw-headline" id="Wällen_uut_dät_Internet"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //image below
            // <a href="/wiki/Bielde:Wiki_letter_w.svg"
            $needle = 'href="/wiki/Bielde:Wiki_letter_w.svg"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</a>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }
        }
        if($this->language_code == 'nds-nl') { //
            //section above
            // <table width="100%" border="0" cellspacing="8" cellpadding="0" style="background-color: #f9f9f9; border-bottom: 1px solid #aaaaaa; border-top: 1px solid #aaaaaa; font-size: 95%; margin-bottom: 1em">
            $needle = 'style="background-color: #f9f9f9; border-bottom: 1px solid #aaaaaa; border-top: 1px solid #aaaaaa; font-size: 95%; margin-bottom: 1em"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }
            
            $left = '<table id="spoiler"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //external links
            $left = '<span class="mw-headline" id="Uutgaonde_verwiezing"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<span class="mw-headline" id="Uutgaonde_verwiezingen"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<span class="mw-headline" id="Uutgaonde_verwiezings"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<table class="toccolours"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<a href="/wiki/Bestaand:Commons-logo.svg"'; $right = '</td>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $needle = 'class="extiw"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</td>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
        }
        if($this->language_code == 'vo') { //
            //infobox
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:white; clear:right; width:200px;"'; $right = "<p><b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:white; clear:right; width:200px;"'; $right = "<p>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $left = '<span class="mw-headline" id="Yüms_plödik"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Yüms_plödk"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'eo') { //
            //section above
            $left = '<table class="noprint plainlinks"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<div style="background-color:#f9f9f9; border-bottom:1px solid #aaa; padding:0.5em; font-style:italic;">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table style="width: 100%; font-size: 95%; border-bottom: 1px solid #AAAAAA; margin-bottom: 1em; position:relative; background-color:#F9F9F9; padding:0.5em;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //inside infobox
            $left = '<th style="background-color: pink;">'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox
            // <table style="position:relative; background:white; width:23em; -moz-box-shadow: 4px 4px 4px #CCC; -webkit-box-shadow: 4px 4px 4px #CCC; box-shadow: 4px 4px 4px #CCC;" class="taxobox">
            $needle = 'class="taxobox';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<p>La <b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p><i><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p><i>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //another infobox
            // <table border="1" cellspacing="0" style="float:right;margin-left:0.5em">
            $needle = 'style="float:right;margin-left:0.5em"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<p>La <b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p><i><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }

            //external links
            $left = '<span class="mw-headline" id="Eksteraj_ligiloj"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            /* commented since it sometimes goes high above the "Table of Contents".
            //"see also" section
            $left = '<span class="mw-headline" id="Vidu_ankaŭ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            */
            
            //section below
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'nap') { //
            //section above
            $left = '<div class="variant"'; $right = "</div>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //section above another
            $left = '<table align="center" class="messagebox"'; $right = "</table>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //infobox
            $left = '<table class="infobox"'; $right = "<p>'O <b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = "<p>'E <b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = "<p>Ll' <b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'bar') { //
            //section above
            // <table align="center" style="margin-top: 6px; margin-bottom: 6px; padding: 0.1em; border: 1px solid #B5B5B5; background-color: #FFFFFF; text-align: center; width: 100%;">
            $needle = 'style="margin-top: 6px; margin-bottom: 6px; padding: 0.1em; border: 1px solid #B5B5B5; background-color: #FFFFFF; text-align: center; width: 100%;"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }
            
            //section above another
            $left = '<table id="Vorlage_Weiterleitungshinweis"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //infobox
            // <table cellpadding="2" cellspacing="1" width="300px" class="taxobox float-right" id="Vorlage_Taxobox" summary="Taxobox">
            $needle = 'class="taxobox';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<p>Da <b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p>De <b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p>A <b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p>As <b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<div class="';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //external links
            $left = '<span class="mw-headline" id="Im_Netz"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Weblinks"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'cdo') { //
            //infobox
            // <table style="float:right; margin:0 0 .5em .5em; background-color: #fff; clear:right; border:1px #aaa solid; border-collapse:collapse; width:200px; padding:2.5px;">
            $needle = 'style="float:right; margin:0 0 .5em .5em; background-color: #fff; clear:right; border:1px #aaa solid; border-collapse:collapse; width:200px; padding:2.5px;"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<p><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //section below
            // <table align="center" style="border:1px solid #AAAAAA; text-align:center;">
            $needle = 'style="border:1px solid #AAAAAA; text-align:center;"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }
            
            $left = '<table width="300px" align="right" cellpadding="5" class="noprint"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'xmf') { //
            //inside infobox
            $left = '<th style="background:lightgreen;">'; $right = '</th>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //infobox
            // <table border="1" cellpadding="3" cellspacing="0" class="toccolours" style="background: #ffffff; border-collapse: collapse;">
            $needle = 'style="background: #ffffff; border-collapse: collapse;"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<p><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '</table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //section below
            $left = '<td style="vertical-align:top; padding:3px 7px">'; $right = '</tr>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $left = '<span class="mw-headline" id="რესურსეფი_ინტერნეტის"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'pam') { //
            //weird above section - Plantae
            // <a href="/w/index.php?title=Template:Taxobox_begin&amp;action=edit&amp;redlink=1" class="new" title="Template:Taxobox begin (alang bulung a anti kaniti)">
            // <div class="thumb tright">
            $needle = 'title="Template:Taxobox begin';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<div class="';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //weird infobox - Formicidae
            $left = '<div class="infobox sisterproject">'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //inside infobox
            $left = '<tr style="background:pink;">'; $right = '</tbody></table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<th style="background:pink;">'; $right = '</tbody></table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //infobox 
            $needle = 'style="float: right; margin: 0 0 1em 1em; width:150px; border-collapse: collapse; font-size: 95%; clear: right"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<p>Ing <b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p>Deng <b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<ul><li><a ';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //external links
            $left = '<span class="mw-headline" id="Suglung_palual"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Suglung_a_palwal"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Suglung_Palwal"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'gd') { //
            //infobox 
            $needle = 'style="margin: 0 0 1em 1em; background: #f9f9f9; border: 1px #aaa solid; border-collapse: collapse; font-size: 95%;"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<p>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '</tbody></table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }
            
            //external links
            $left = '<span class="mw-headline" id="Ceanglaichean_a-mach"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'scn') { //
            //note above
            $left = '<i><b>Nota disambigua</b>'; $right = '</i>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //section above
            $left = '<div class="noprint"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //infobox
            $vars = array("<p>L'<b>", '<p><br />');
            foreach($vars as $var) {
                $left = '<table style="margin: 0 0 0.5em 1em; border-collapse:collapse; float:right;"'; $right = $var;
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }

            $vars = array('<p>Lu <b>', '<p>La <b>', '<p><br />', '<p><b>', '<p>');
            foreach($vars as $var) {
                $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:white; clear:right; width:200px;"'; $right = $var;
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }

            //another infobox-like
            $left = '<table align="right" border="1" bgcolor="#FFFFCC" cellspacing="0" cellpadding="1">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //external links
            $left = '<span class="mw-headline" id="Lijami_a_fora"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Lijami_di_fora"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Liami_esterni"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<table class="toccolours"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<div class="toccolours'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //another section below
            $needle = 'style="font-weight:bold; float:right; border:solid #008 2px;margin-left:5px;margin-bottom:5px"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<!--';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
        }
        if($this->language_code == 'fr') { //
            //sections above
            // <div id="" class="bandeau-container homonymie plainlinks" style="">
            $needle = 'class="bandeau-container homonymie plainlinks';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                // $left = $tmp . $needle; $right = '<div id=';
                // $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<div class="infobox';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //infobox
            $left = '<div class="infobox'; $right = '<p>Les <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<div class="infobox'; $right = '<p>Le <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<div class="infobox'; $right = '<p>La <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<div class="infobox'; $right = "<p>L'<b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<div class="infobox'; $right = '<p>Le <a';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<div class="infobox'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<div class="infobox'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $external_links_came_last_YN = self::does_external_links_came_last($html);

            $left = '<span class="mw-headline" id="Filmographie"'; $right = '<span class="mw-headline" id=';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Articles_connexes"'; $right = '<span class="mw-headline" id=';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Bibliographie"'; $right = '<span class="mw-headline" id=';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Études_générales"'; $right = '<span class="mw-headline" id=';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Chasse"'; $right = '<span class="mw-headline" id=';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Articles_connexes"'; $right = '<span class="mw-headline" id=';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            if($external_links_came_last_YN) {
                // exit("\ngoes here\n");
                $left = '<span class="mw-headline" id="Liens_externes"'; $right = '<!--';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = '<span class="mw-headline" id="Lien_externe"'; $right = '<!--';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            else {
                // exit("\ngoes fff\n");
                $left = '<span class="mw-headline" id="Liens_externes"'; $right = '<span class="mw-headline" id=';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = '<span class="mw-headline" id="Lien_externe"'; $right = '<span class="mw-headline" id=';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            // may want to add this - Formicidae
            //Références_externes

            $left = '<span class="mw-headline" id="Autres_liens_externes"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //nav box below
            $left = '<div class="printfooter">'; $right = '<div id="mw-navigation">';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //section below
            $left = '<div class="autres-projets boite-grise boite-a-droite noprint js-interprojets"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<ul id="bandeau-portail"'; $right = '</ul>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table class="navbox collapsible noprint autocollapse"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'myv') { //
            //infobox
            // <table border="1" cellpadding="3" cellspacing="0" class="toccolours" style="background: #ffffff; border-collapse: collapse;">
            $needle = 'style="background: #ffffff; border-collapse: collapse;"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<p><br />';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //infobox also
            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'bh') { //
            //external links
            $left = '<span class="mw-headline" id="बाहरी_कड़ी"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'or') { //
            //section above
            // <div id="purl" class="NavFrame collapsed"
            $needle = 'class="NavFrame collapsed"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</div>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }
            
            //infobox
            // <table class="infobox biota biota-infobox">
            $left = '<table class="infobox'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $left = '<span class="mw-headline" id="ଅଧିକ_ତଥ୍ୟ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="ବାହାର_ଆଧାର"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="ଅନ୍ୟାନ୍ୟ_ଲିଂକ୍"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="ବାହ୍ୟ_ସଂଯୋଗ_ସବୁ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="ବାହାର_ତଥ୍ୟ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'als') { //
            //infobox
            $left = '<table class="taxobox"'; $right = '<p>De <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="taxobox"'; $right = '<p>D <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="taxobox"'; $right = "<p>D' <b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="taxobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox another
            $needle = 'id="Vorlage_Taxobox" summary="Taxobox"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }

            //section below
            $needle = 'style="background-color: #f9f9f9; border-top: 1px solid #aaaaaa; font-size: 95%; margin-top: 1em; clear: both"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }

            //weblinks, sometimes found in the middle of the body
            $left = '<span class="mw-headline" id="Weblink"'; $right = '<span class="mw-headline" id="Fueßnote"';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Weblink"'; $right = '<span class="mw-headline" id="Fuessnoote"';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Weblingg"'; $right = '<span class="mw-headline" id="Fuessnoote"';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Weblingg"'; $right = '<span class="mw-headline" id="Fuessnote"';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //test first
            $left = '<span class="mw-headline" id="Weblink"'; $right = '<span class="mw-headline" id="';
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {}
            else {
                //external links
                $left = '<span class="mw-headline" id="Weblink"'; $right = '<!--';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }

            //test first
            $left = '<span class="mw-headline" id="Weblingg"'; $right = '<span class="mw-headline" id="';
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {}
            else {
                //external links
                $left = '<span class="mw-headline" id="Weblingg"'; $right = '<!--';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
        }
        if($this->language_code == 'es') { //
            //middle body
            $left = '<table class="toccolours" style="float:right;margin-left: 1em;">'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox
            $left = '<table class="infobox"'; $right = '<div class="rellink noprint">';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '</div></td></tr></tbody></table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table class="infobox"'; $right = '<p>Las <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '<p>El <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '<p>Los <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '<p>Para la <a';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '<p>En la <a';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '<p><i><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table class="infobox"'; $right = '<p>En <a';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //inside infobox
            $left = '<th colspan="3" style="text-align:center;background-color: #D3D3A4;"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $left = '<span class="mw-headline" id="Enlaces_externos"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<div class="printfooter">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            $left = '<div id="mw-normal-catlinks" class="mw-normal-catlinks">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<div id="mw-hidden-catlinks"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'gu') { //
            //2nd infobox like box
            $left = '<table class="infobox nowrap"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //infobox
            $left = '<table class="infobox biography vcard"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //external links
            $left = '<span class="mw-headline" id="બાહ્ય_કડીઓ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<span class="mw-headline" id="બાહ્ય_લિંક્સ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<span class="mw-headline" id="બાહ્ય_લિન્ક્સ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<span class="mw-headline" id="બ્રાહ્ય_લિંક્સ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="બાહ્ય_ક્ડીઓ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'mt') { //
            //external links
            $left = '<span class="mw-headline" id="Links_Esterni"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Link_Esterni"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<span class="mw-headline" id="Ħoloq_esterni"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'rue') { //
            // //section below
            // $left = '<div class="boilerplate"'; $right = '</div>';
            // $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //external links
            $left = '<span class="mw-headline" id="Вонкашнї_лінкы"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'gn') { //
            //link above
            $needle = 'title="Categoría:Wikipedia:Artículos que usan ficha sin datos en filas"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</a>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }
            
            //external links
            $left = '<span class="mw-headline" id="Joaju"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<span class="mw-headline" id="Joaju_(inglyesñe\'ẽme)"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'ht') { //
            //infobox
            $needle = 'style="margin: 0 0 1em 1em; border-style: solid; border-color: #999; border-top-width: 1px; border-left-width: 1px; border-right-width: 2px; border-bottom-width: 2px; background-color: #CFC"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            }

            //section above
            $left = '<div class="plainlinks"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //box below
            $left = '<table align="right" style="width:250px;border:solid 1px blue; background-color: #eeffff; padding: 0.1em;align:right;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'si') { //
            //inside infobox
            $left = '<tr style="background:rgb(235,235,210);">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $left = '<span class="mw-headline" id="බාහිර_සබැදි"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'am') { //
            //infobox
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:white; clear:right; width:200px;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'en') { //
            //infobox
            $left = '<table class="infobox'; $right = '<p>The <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //external links
            $left = '<span class="mw-headline" id="External_links">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<div class="printfooter">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<div id="catlinks" class="catlinks"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<div id="mw-hidden-catlinks"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'se') { //
            //section above
            $left = '<div style="clear: both; background-color: #f9f9f9; text-align: left; padding: 0 1em; border: 1px solid #aaaaaa; border-right-width: 1px; border-bottom-width: 1px; margin: 0em 0em 0em 0em;">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //section below
            $needle = 'style="clear: right; border: solid #aaa 1px; margin: 0 0 1em 1em; font-size: 90%; background: #f9f9f9"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<!--';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
        }
        if($this->language_code == 'vep') { //
            //infobox
            $needle = 'style="border:1px solid #aaa; background:#ffffff; border-collapse:collapse; text-align:center"';
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '<p><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
            
            //specific image link
            $left = '<div id="floating_object16"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //section below
            $left = '<table align="center" class="toccolours"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'pcd') { //
            //infobox
            $needle = 'style="margin: 0 0 1em 1em; border: 1px solid #999; background-color: #FFFFFF"'; //<table align="right" rules="all" cellpadding="3" cellspacing="0" border="0" style="margin: 0 0 1em 1em; border: 1px solid #999; background-color: #FFFFFF">
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = "<p>Ch' <b>";
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<p><b>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

                $left = $tmp . $needle; $right = '<dl><dt>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
                
                $left = $tmp . $needle; $right = '<p>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
        }
        if($this->language_code == 'tg') { //
            //infobox

            // bad sol'n
            // $left = '<table class="infobox"'; $right = '<h2>';
            // $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<a href="/wiki/%D0%90%D0%BA%D1%81:%D0%9B%D0%BE%D0%B3%D0%BE_%D0%AD%D0%A1%D0%A2.png" class="image" title="Энсиклопедияи Советии Тоҷик"'; $right = '</i>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table align="center" border="0" cellpadding="0" cellspacing="4" style="background: none;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'mhr') { //
            //section below
            $left = '<table style="background:none; text-align:left; padding: 2px 0" class="metadata"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'bat-smg') { //
            //infobox
            $left = '<table style="margin: 0 0 0.5em 1em; background-color: white; border-collapse:collapse; float:right;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<table border="0" align="right" width="200" cellpadding="4" cellspacing="0" class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'as') { //
            //external links
            $left = '<span class="mw-headline" id="বাহ্যিক_সংযোগ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="বহিঃসংযোগ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<span class="mw-headline" id="বহি:সংযোগ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'li') { //
            //section above
            $left = '<p><small>Dit artikel is gesjreve'; $right = '</p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //remove star
            $left = '<div class="Titel_item2"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //image below
            $left = '<a href="/wiki/Plaetje:Wiki_letter_w.svg"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'mdf') { //
            //section below
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'to') { //
            //infobox
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:#EEE; clear:right; width:200px;text-align:center;"'; $right = '<p>Ko e <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:#EEE; clear:right; width:200px;text-align:center;"'; $right = '<p>Ko e ngaahi <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:#EEE; clear:right; width:200px;text-align:center;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:#EEE; clear:right; width:200px;text-align:center;"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'kbd') { //
            //inside infobox
            $left = '<tr style="" class="plainlinksneverexpand">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox
            $left = '<div style="float:right; clear:right; margin:0 0 0.5em 1em;">'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<div style="float:right; clear:right; margin:0 0 0.5em 1em;">'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $left = '<span class="mw-headline" id="ТехьэпӀэхэр"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'dsb') { //
            //section above
            /*
            $needle = 'class="plainlinks ambox ambox-'; //<table style="width:98%; margin:0.3em auto;" class="plainlinks ambox ambox-notice">
            if($tmp = self::get_pre_tag_entry($html, $needle, '<table')) { //TODO: using 3rd param not "<"
                $left = $tmp . $needle; $right = '</table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true); exit("\n[$left]\n");
            }
            else exit("\nelix\n");
            */
            
            // <table style="width:98%; margin:0.3em auto;" class="plainlinks ambox ambox-notice">
            $left = '<table style="width:98%; margin:0.3em auto;" class="plainlinks ambox ambox-'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //inside infobox
            $left = '<td align="center" colspan="2" style="background-color:#eeaaaa;padding:0.1em 0.5em;">'; $right = '</td></tr></tbody></table></div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //infobox
            $left = '<table class="taxobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<div class="noprint" style='; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $needle = 'class="plainlinks ambox ambox-style"'; //<table style="background:#E8FFE0;font-size:smaller;padding:1ex;" class="plainlinks ambox ambox-style">
            if($tmp = self::get_pre_tag_entry($html, $needle)) {
                $left = $tmp . $needle; $right = '</table>';
                $html = self::remove_all_in_between_inclusive($left, $right, $html, true); //exit("\n[$left]\n");
            }
            
            //external links
            $left = '<span class="mw-headline" id="Eksterne_wotkazy">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Eksterne_wótkaze"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'mi') { //
            //infobox
            $left = '<table class="infobox biota"'; $right = '<p>Ko te <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table class="infobox biota"'; $right = '<p>Ko <i><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<tr style="background:#90EE90;">'; $right = '</tbody>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="toccolours"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'nah') { //
            //section above
            $left = '<table align="center" width="100%" id="toc">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //inside infobox
            $left = '<tr bgcolor="90EE90">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<tr bgcolor="D3D3A4">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox
            $left = '<table class="toccolours"'; $right = '<p>In <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="toccolours"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="toccolours"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<table width="33%" class="noprint toccolours"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $left = '<div class="infobox sisterproject">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'wuu') { //
            //section below-middle
            $left = '<div class="notice metadata"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //section below
            $left = '<div class="infobox sisterproject">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $left = '<span class="mw-headline" id="链接进来">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<span class="mw-headline" id="外部連結"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'gv') { //
            $html = str_replace('<hr />', '', $html);
            
            //inside infobox
            // $left = '<tr style="background:#90EE90;">'; $right = '</table>';
            // $left = '<tr style="background:#D3D3A4;">'; $right = '</table>';
            //          <tr style="background:#ADD8E6;">
            $left = '<tr style="background:#'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<div style="font-size:small; width: 80%; padding: 3px; background: #f7f8ff; border: 1px solid gray; margin: 0 auto;">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'vls') { //
            $html = str_replace('<hr />', '', $html);
            //infobox
            $left = '<table class="plainlinks"'; $right = '<p>De <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="plainlinks"'; $right = '<p>Et <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="plainlinks"'; $right = "<p>'t <b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="plainlinks"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section above
            $left = '<div style="text-align:right">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //section below
            $left = '<table class="toccolours"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table style="margin:0.5em 0 0.5em 0; clear:both" width="100%" class="toccolours"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'wa') { //
            //section on right side
            $left = '<div class="noprint wikilien_alternatif"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //section below, like external links
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'pa') { //
            //inside infobox
            $left = '<div class="NavHead"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            $left = '<div class="NavContent"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<tr style="background:;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'lmo') { //
            //inside infobox
            $left = '<tr style="background:#90EE90;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<tr style="background:lightblue;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox
            $left = '<table class="infobox biota"'; $right = '<p>Ul <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox biota"'; $right = '<p>I <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox biota"'; $right = '<p>El <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox biota"'; $right = '<p>La <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            // section above
            $left = '<div style="float: center; border: 1px solid #c0c0c0; background: #f8f8f8; margin: 1px;"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table style="" class="metadata noprint plainlinks avviso avviso-contenuto"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'nds') { //
            //infobox
            $left = '<table class="taxobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table class="taxobox"'; $right = '<p>De <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="taxobox"'; $right = '<p>En <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section above
            $left = '<div id="Vorlaag_Disse_Artikel"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'sah') { //
            //inside infobox
            $left = '<tr style="background:#D3D3A4;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox
            $left = '<div style="float:right; clear:right; margin:0 0 0.5em 1em;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<table style="margin:0 auto;" align="center" width="100%" class="toccolours"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'cv') { //
            //inside infobox
            $left = '<table width="100%" cellspacing="0" cellpadding="0" style="background:&#91;&#91;:Шаблон:Taxobox color&#93;&#93;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            $left = '<tr style="" class="plainlinksneverexpand">'; $right = '</tr>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //infobox
            $left = '<table cellpadding="3" cellspacing="0" style="border:1px solid #aaa; background:#ffffff; border-collapse:collapse; text-align:center"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section above
            $left = '<div style="padding-left:1em; padding-bottom:.5em; margin-top:-.5em; margin-bottom:.5em; font-style:italic; border-bottom:1px #AAA solid;"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //sections below
            $left = '<table class="metadata plainlinks navigation-box"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<div class="floatleft">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<div class="infobox sisterproject noprint wikisource-box"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table class="notice metadata plainlinks"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            $left = '<table class="infobox vcard"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table class="navbox collapsible autocollapse nowraplinks"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'os') { //
            //infobox
            $left = '<table cellpadding="3" cellspacing="0" style="border:1px solid #aaa; background:#ffffff; border-collapse:collapse; text-align:center"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'fo') { //
            //infobox
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:white; clear:right; width:200px;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section below
            $left = '<table cellpadding="0" cellspacing="0" style="background-color: #f9f9f9; border: 1px solid #aaa; padding: 5px; padding-left: 10px; text-align: left; font-size: 90%; margin-top: 1em;"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<div class="noprint"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'co') { //
            //infobox
            $left = '<table style="margin: 0 0 0.5em 1em; border-collapse:collapse; float:right;"'; $right = '<p>U <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table style="margin: 0 0 0.5em 1em; border-collapse:collapse; float:right;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table style="margin: 0 0 0.5em 1em; border-collapse:collapse; float:right;"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table align="right" rules="all" cellpadding="3" cellspacing="0" border="0" style="margin: 0 0 1em 1em; border: 1px solid #999; background-color: #'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table style="margin: 0 0 0.5em 1em; border-collapse:collapse; float:right;"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //remove section inside 'under construction page'
            $left = '<div class="floatleft">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //remove under construction message
            $left = '<div class="toccolours itwiki_template_avviso"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'mr') { //
            //section above
            $left = '<div class="floatleft"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            // moved up...
            // $left = '<div class="dablink"'; $right = '</div>';
            // $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //infobox
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //section above
            $left = '<div class="infobox sisterproject"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table style="font-weight:normal;font-size:95%;font-style: italic bold" class="ambox सूचना"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table style="width:60%;font-weight:normal;font-size:100%;font-style: italic bold;" class="ambox सूचना"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table style="" class="ambox ambox-content"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table style="" class="ambox ambox-'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //section below
            $left = '<table style="" class="ambox सूचना">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //external links
            $left = '<span class="mw-headline" id="बाह्य_दुवे"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'csb') { //
            //remove <dd>
            $left = '<dd>'; $right = '</dd>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'sq') { //
            //infobox
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //remove section above
            $left = '<table width="100%" style="background-color:#fdfdfd; border-bottom:1px solid #aaaaaa; font-size:95%; margin-top:-5px; margin-bottom:1em; padding:3px 8px;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //external links
            $left = '<span class="mw-headline" id="Lidhje_të_jashtme"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="mw-headline" id="Lidhje_te_jashtme"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'tt') { //
            //remove section inside infobox
            $left = '<th class="metadata"'; $right = '</th>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<th style=";background: #EE82EE">'; $right = '</th>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<td align="center">'; $right = '</td>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //infobox
            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //remove img
            $left = '<td class="ambox-image">'; $right = '</td>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            /* remove section below
            <table class="metadata plainlinks ambox ambox-style">
            <table class="metadata plainlinks ambox ambox-content">
            <table class="metadata plainlinks ambox ambox-good">
            */
            $left = '<table class="metadata plainlinks ambox ambox-'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //another section below
            $left = '<div class="floatnone">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<div class="notice metadata"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'kn') { //
            //infobox
            $left = '<table class="infobox biota"'; $right = '</tbody></table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //external links
            $left = '<span class="mw-headline" id="ಬಾಹ್ಯ_ಕೊಂಡಿಗಳು"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //remove section below
            $left = '<table class="metadata plainlinks stub"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'su') { //
            //inside infobox
            $left = '<table style="background:white; margin: 0 0 0.5em 1em; border-collapse:collapse; float:right; clear:right;"'; $right = "<p><b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //another infobox
            $left = '<table style="background:white; margin: 0 0 0.5em 1em; border-collapse:collapse; float:right; clear:right;"'; $right = "<p>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //remove under construction section
            $left = '<table align="center" style="border: 1px solid #79b; background: #FFFFDD; width: 80%; margin: 0 auto 1em auto; padding: .2em; text-align: justify;">'; $right = "</table>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //remove notify as stub page
            $left = '<table width="100%" cellspacing="0" style="padding: 0px; border: 1px dotted steelblue; clear: both; background: white; margin: 5px 0px; font-size: small; font-size: 92%;">'; $right = "</table>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //remove display:none style
            $left = '<table style="display: none"'; $right = "</table>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'lb') { //
            //inside infobox
            $left = '<table class="toccolours" style="width:300px; margin: 0 0 0.5em 1em; float:right; clear:right; border-collapse: collapse"'; $right = "<p>D'<b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="toccolours" style="width:300px; margin: 0 0 0.5em 1em; float:right; clear:right; border-collapse: collapse"'; $right = "D'<b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table class="toccolours" style="width:300px; margin: 0 0 0.5em 1em; float:right; clear:right; border-collapse: collapse"'; $right = "Den <b>";
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table class="toccolours" style="width:300px; margin: 0 0 0.5em 1em; float:right; clear:right; border-collapse: collapse"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //remove sections above
            $left = '<table cellspacing="8" cellpadding="0" class="hintergrundfarbe1 rahmenfarbe2"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table cellspacing="8" cellpadding="0" class="hintergrundfarbe1 rahmenfarbe1"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'kv') { //
            //section below
            $left = '<table style="background: none; padding: 2px 0" class="metadata">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'mn') { //
            //inside infobox
            $left = '<tr style="background:#transparent;">'; $right = '</tbody>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //inside infobox - map
            $left = '<tr style="background:pink;">'; $right = '</tbody>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //inside infobox - map
            $left = '<tr style="background:#FFC0CB;">'; $right = '</tbody>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //external links
            $left = '<span class="mw-headline" id="Гадны_холбоос"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //external links
            $left = '<span class="mw-headline" id="Гадаад_холбоос"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //electronic links
            $left = '<span class="mw-headline" id="Цахим_холбоос"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'my') { //
            //inside infobox
            $left = '<th colspan="2" style="text-align: center; background-color: rgb(180,250,180)">Divisions'; $right = '</th>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<td colspan="2" style="text-align: left">'; $right = '</td>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //nested <div>'s, 3 total. Start removing inner most then move outward.
            $left = '<div style="margin-left: 60px;">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            $left = '<div class="floatleft">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            $left = '<div class="infobox sisterproject">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'io') { //
            //infobox - general
            $left = '<table style="background-color:#F8F8F8; border:2px solid pink; padding:5px;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //remove section below
            $left = '<table class="navbox collapsible collapsed nowraplinks noprint"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            //another section
            $left = '<table align="center" class="noprint"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //nested <div>'s, 3 total. Start removing inner most then move outward.
            $left = '<div class="floatnone">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            $left = '<div style="float: left;">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            $left = '<div class="noprint"'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'ku') { //
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //external links
            $left = '<span class="mw-headline" id="Girêdanên_derve"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'bs') { //
            //infobox - general
            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //external links
            $left = '<span class="mw-headline" id="Vanjski_linkovi"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'uz') { //
            //infobox - general
            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //another infobox
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:white; clear:right; width:200px;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:white; clear:right; width:200px;"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);

            //remove section below
            $left = '<table class="plainlinks fmbox fmbox-system"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            //another section
            $left = '<table class="metadata plainlinks stub"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'te') { //
            //external links
            $left = '<span class="mw-headline" id="బయటి_లింకులు"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'sw') { //
            //infobox - general
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; clear:right; width:200px;"'; $right = '<p><br />';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; clear:right; width:200px;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox another
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; clear:right; width:250px;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'zh-min-nan') { //
            //infobox - general
            $left = '<table style="float:right; margin:0 0 .5em .5em; background-color: #fff; clear:right; border:1px #aaa solid; border-collapse:collapse; width:200px; padding:2.5px;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; clear:right; width:200px;"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table style="float:right; margin:0 0 .5em .5em; background-color: #fff; clear:right; border:1px #aaa solid; border-collapse:collapse; width:200px; padding:2.5px;"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'an') { //
            //remove small icon gif
            $left = '<img alt="Articlo d&#39;os 1000"'; $right = '>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
            
            //infobox - general
            $left = '<table class="infobox_v2"'; $right = '<p>O <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox_v2"'; $right = '<p>As <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox_v2"'; $right = '<p>Os <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox_v2"'; $right = '<p>Un <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox_v2"'; $right = '<p>Los <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //another infobox
            $left = '<table class="infobox_v2"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //infobox geography
            $left = '<table class="infobox geography"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //external links
            $left = '<span class="mw-headline" id="Vinclos_externos"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'ba') { //
            //infobox - general
            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //infobox - another type
            $left = '<table cellpadding="3" cellspacing="0" style="border:1px solid #aaa; background:#ffffff; border-collapse:collapse; text-align:center"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'frr') { //
            //infobox - general
            $left = '<table cellpadding="2" cellspacing="1" width="300" class="taxobox float-right toptextcells" id="Vorlage_Taxobox_öömrang"'; $right = '<p>At <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //infobox - another try
            $left = '<table cellpadding="2" cellspacing="1" width="300" class="taxobox float-right toptextcells" id="Vorlage_Taxobox_öömrang"'; $right = '<p>Di <b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //infobox - another try
            $left = '<table cellpadding="2" cellspacing="1" width="300" class="taxobox float-right toptextcells" id="Vorlage_Taxobox_öömrang"'; $right = '<p>Di ';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //infobox - another try
            $left = '<table cellpadding="2" cellspacing="1" width="300" class="taxobox float-right toptextcells" id="Vorlage_Taxobox_öömrang"'; $right = '<p>A ';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //infobox - another try
            $left = '<table cellpadding="2" cellspacing="1" width="300" class="taxobox float-right toptextcells" id="Vorlage_Taxobox_öömrang"'; $right = '<p>At ';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //infobox - another try
            $left = '<table cellpadding="2" cellspacing="1" width="300" class="taxobox float-right toptextcells" id="Vorlage_Taxobox_öömrang"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //infobox - another try
            $left = '<table cellpadding="2" cellspacing="1" width="300" class="taxobox float-right toptextcells" id="Vorlage_Taxobox_öömrang"'; $right = '<p><br />';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'qu') { //
            //infobox - general
            $left = '<table class="toccolours"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //another infobox
            $left = '<table class="toccolours"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'oc') { //
            //infobox - general
            $left = '<div class="infobox_v3 large taxobox_v3 zoologia animal bordered"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //another infobox
            $left = '<div class="infobox_v3 large taxobox_v3 planta bordered"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //ugly navbox
            $left = '<table class="navbox collapsible noprint autocollapse"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'ne') { //Nepali
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'be-tarask') { //redirected from be-x-old -- 
            //infobox - general
            $left = '<table class="infobox vcard"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //infobox try again...
            $left = '<table class="infobox vcard"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //remove other bottom section
            $left = '<table cellspacing="0" class="navbox hlist"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
        }
        if($this->language_code == 'ia') { //Interlingua
            //another infobox type
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; background:white; clear:right; width:200px;"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            $left = '<table width="320px" class="infobox"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'jv') { //Javanese
            $orig_html = $html;
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p>';
            $html1 = self::remove_all_in_between_inclusive($left, $right, $html, false);
            //external links
            $left = '<span class="mw-headline" id="Pranala_njaba"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'tl') { //Tagalog
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p>';
            $html1 = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html2 = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            if(strlen($html1) < strlen($html2)) $html = $html1;
            if(strlen($html2) < strlen($html1)) $html = $html2;
            else                                $html = $html1;
        
            $html = $html2; //just decided to use html2 instead
        }
        if($this->language_code == 'fy') { //West Frisian
            $orig_html = $html;
            //infobox - general
            $left = '<table class="toccolours vatop infobox"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            if($html == $orig_html) {
                // exit("\ngoes here\n");
                //another infobox type
                $left = '<div class="mw-parser-output">'; $right = '<p>'; //for Mus musculus
                $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            }
        }
        if($this->language_code == 'hi') { //Hindi
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //remove external links section
            $left = '<span class="mw-headline" id="बाहरी_संबंध"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'sco') { //Scots
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'ky') { //Kirghiz
            //infobox - general
            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'mk') { //Macedonian
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //remove external links section
            $left = '<span class="mw-headline" id="Надворешни_врски"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            // //remove <abbr> ----- may not need this anymore since they're inside <span style="display:none; visibility:hidden"> already.
            // $left = '<div class="plainlinks hlist navbar mini"'; $right = '</div>';
            // $html = self::remove_all_in_between_inclusive($left, $right, $html, true);
        }
        if($this->language_code == 'el') { //Greek
            //infobox - general
            $left = '<table class="infobox biota"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //remove external links section
            $left = '<span class="mw-headline" id="Εξωτερικοί_σύνδεσμοι"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'ta') { //Tamil
            //infobox
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //remove external links section
            $left = '<span class="mw-headline" id="வெளி_இணைப்புகள்"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'bn') { //Bengali
            //infobox
            $left = '<table class="infobox biota"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'ga') { //Irish
            //infobox
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-spacing: 3px 3px; border: #aaa 1px solid; float:right; clear:right; width:200px;"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'pms') { //Piedmontese
            //infobox
            $left = '<table style="position:relative; margin: 0 0 0.5em 1em; border-collapse: collapse; float:right; clear:right; width:200px;"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'sl') { //Slovenian
            //infobox
            $left = '<table class="infobox biota"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //remove external links section
            $left = '<span class="mw-headline" id="Zunanje_povezave">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'hsb') { //Upper Sorbian
            //infobox
            $left = '<table class="taxobox"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'sk') { //Slovak
            //infobox
            $left = '<table class="infobox"'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //remove external links section
            $left = '<span class="mw-headline" id="Externé_odkazy"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'nn') { //Norwegian (Nynorsk)
            //infobox
            $left = '<table class="toccolours"'; $right = '<p>';
            $html1 = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //infobox
            $left = '<table class="toccolours"'; $right = '<p><b>';
            $html2 = self::remove_all_in_between_inclusive($left, $right, $html, false);

            if(strlen($html1) < strlen($html2)) $html = $html1;
            else                                $html = $html2;
        }
        if($this->language_code == 'hy') { //Armenian
            //remove external links section
            $left = '<span class="mw-headline" id="Արտաքին_հղումներ"'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'is') { //Icelandic
            //remove links section
            $left = '<span class="mw-headline" id="Tenglar'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //remove contact section
            $left = '<span class="mw-headline" id="Tengill'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //remove section inside infobox
            $left = '<td style="padding:0 .5em; text-align:left;">'; $right = '</td>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            //infobox
            $left = '<table class="infobox'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'lv') { //Latvian
            //remove section
            $left = '<td colspan="2" style="text-align:center;font-weight:normal;text-align:left;">'; $right = '</td>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            //infobox
            $left = '<table class="infobox'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
            
            //remove external links
            $left = '<span class="mw-headline" id="Ārējās_saites">'; $right = '</table></td></tr></tbody></table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);

            $left = '<span class="mw-headline" id="Ārējās_saites">'; $right = '<!--';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'kk') { //Kazakh
            // first option...
            // $left = '<table class="infobox'; $right = '</table>';
            // $html = self::remove_all_in_between_inclusive($left, $right, $html);

            //important: remove section
            $left = '<p><a class="mw-selflink selflink">'; $right = '</p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);

            $left = '<p><span style="font-style: italic">'; $right = '</p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);

            $left = '<p><span style="font-size:smaller;">'; $right = '</span></p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            $left = '<td style="padding:0 .5em; text-align:left;">'; $right = '</td>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);

            $left = '<table class="infobox'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'ms') { //Malay
            $left = '<table border="1" cellpadding="2" cellspacing="0" width="260px" align="right" style="margin-left: 10px; bgcolor: white; margin-bottom: 5px">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
        }
        if($this->language_code == 'th') { //Thai
            $left = '<table class="infobox'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
        }
        if($this->language_code == 'be') {
            //infobox
            $left = '<table class="infobox"'; $right = '<p><b>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //navigation box
            $left = '<table cellspacing="0" class="navbox'; $right = '</table></td></tr></tbody></table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
        }
        if($this->language_code == 'da') {
            //remove section
            $left = '<span class="mw-headline" id="Eksterne_henvisninger">'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            $left = '<span class="lovende" style="display:none">'; $right = '</span>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            $left = '<b>Søsterprojekter med yderligere information'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            $left = '<td style="padding: 0.25em 0.5em;">'; $right = '</td>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            //external links
            $left = '<span class="mw-headline" id="Eksterne_links">'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'la') {
            
            //other collabsible section
            $left = '<div style="padding:0.2em 0.1em 0.1em 0.2em; font-size:80%">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            //infobox
            $left = '<div style="width:18em; float:right; clear:right; margin:0 0 2em 1em; box-shadow:8px 8px 8px #CCC; text-align:center; background:'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false); //4th param is if $right is included to be removed or not. This case is false, means not removed.
            
            //remove
            $left = '<span class="mw-headline" id="Nexus_externi">'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);

            //remove
            $left = '<span class="mw-headline" id="Nexus_externus">'; $right = '<p>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html, false);
        }
        if($this->language_code == 'br') {
            
            //remove un-important section. Messes up below if not removed.
            $left = '<div style="margin:0 10px;float: left;">'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            //remove 'under construction' section. e.g. Plantae https://br.wikipedia.org/wiki/Plant
            $left = '<div style="border:1px solid #E47B10;'; $right = '</div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            //remove infobox
            // $left = '<table align="right" rules="all"'; $right = '<p>';
            // $html = self::remove_all_in_between_inclusive($left, $right, $html);

            $left = '<table align="right" rules="all"'; $right = '</td></tr></tbody></table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            $left = '</th></tr>'; $right = '</td></tr></tbody></table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            // exit("\n$html\n");
            
        }
        
        if($this->language_code == 'gl') {
            //remove infobox
            $left = '<table class="toccolours"'; $right = '</table></div>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            //remove extra info below. e.g. sunflower https://gl.wikipedia.org/wiki/Xirasol
            $left = '<table>'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            //remove external links e.g. Gadus morhua https://gl.wikipedia.org/wiki/Bacallau
            $left = '<span class="mw-headline" id="Ligazóns_externas">'; $right = '</span>.</cite>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            $left = '<span class="mw-headline" id="Ligazóns_externas">'; $right = '</li></ul>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
        }
        
        if($this->language_code == 'hr') {
            $left = '<table style="vertical-align:center; background:transparent;">'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            //remove 'External links' section
            $left = '<span class="mw-headline" id="Vanjske_poveznice">'; $right = '</tbody></table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            $left = '<span class="mw-headline" id="Vanjske_poveznice">'; $right = '<h2>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
            
            //remove un-needed box on top. e.g. Mus musculus https://hr.wikipedia.org/wiki/Doma%C4%87i_mi%C5%A1
            $left = '<table border="0" class="messagebox plainlinks"'; $right = '</table>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
            
        }
        
        if($this->language_code == "nv") {
            $html = self::code_the_steps('<table class="wikitable"', '</table>', $html);
            $html = self::code_the_steps('<table class="navbox collapsible"', '</table>', $html);
        }
        if($this->language_code == "ast") { /* for hu Eli updates: 11-08-2019 */
            $html = str_replace("</table>\n", "</table>", $html);
            $left = '<table border="1" cellspacing="0" cellpadding="2" style="margin-left: 1em; margin-bottom: 0.5em;float:right">';
            $right = '</tbody></table><p>';
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                $substr = $left.$arr[1].$right;
                // $html = str_ireplace($substr, '', $html); //orig
                $html = str_ireplace($substr, '<p>', $html); //2nd param is '</div>' not '' bec. '</div>' was added in $right above.
            }
            //---------------------------------------------------------------------
            $left = '<table border="1" cellspacing="0" align="right" cellpadding="2">';
            $right = '</tbody></table><p>';
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                $substr = $left.$arr[1].$right;
                // $html = str_ireplace($substr, '', $html); //orig
                $html = str_ireplace($substr, '<p>', $html); //2nd param is '</div>' not '' bec. '</div>' was added in $right above.
            }
            //---------------------------------------------------------------------
            $left = '<table class="infobox_v2">';
            $right = '</tbody></table><p>';
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                $substr = $left.$arr[1].$right;
                // $html = str_ireplace($substr, '', $html); //orig
                $html = str_ireplace($substr, '<p>', $html); //2nd param is '</div>' not '' bec. '</div>' was added in $right above.
            }
        }
        if($this->language_code == "az") { /* for hu Eli updates: 11-08-2019 */
            $html = str_replace("</tr>\n", "</tr>", $html);
            $html = self::code_the_steps('<table class="infobox', '</tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table>', $html);
            //---------------------------------------------------------------------
            $html = str_replace("</table>\n", "</table>", $html);
            $left = '<table cellpadding="3" cellspacing="0" style="border:1px solid #aaa; background:#ffffff;';
            $right = '</tbody></table></div>';
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                $substr = $left.$arr[1].$right;
                // $html = str_ireplace($substr, '', $html); //orig
                $html = str_ireplace($substr, '</div>', $html); //2nd param is '</div>' not '' bec. '</div>' was added in $right above.
            }
        }
        if($this->language_code == "cy") { /* for hu Eli updates: 11-07-2019 */
            // $html = self::code_the_steps('<table class="infobox biota', '</tbody></table></div>', $html);
            $left = '<table class="infobox biota';
            $right = '</tbody></table></div>';
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                $substr = $left.$arr[1].$right;
                // $html = str_ireplace($substr, '', $html); //orig
                $html = str_ireplace($substr, '</div>', $html); //2nd param is '</div>' not '' bec. '</div>' was added in $right above.
            }
        }
        if($this->language_code == "bg") { /* for hu Eli updates: 11-06-2019 */
            $html = self::code_the_steps('<table class="infobox', '</tr></tbody></table>', $html);
        }
        if($this->language_code == "min") { /* for hu Eli updates: 11-06-2019 */
            $html = self::code_the_steps('<table class="infobox biota"', '</td></tr></tbody></table>', $html);
            $html = self::code_the_steps('<table class="metadata plainlinks stub"', '</tr></tbody></table>', $html);
        }
        if($this->language_code == "hy") { /* for hu Eli updates: 11-04-2019 */
            $left = '<table cellpadding="3" cellspacing="0" style="border:1px solid #aaa; background:#ffffff; border-collapse:collapse; text-align:center">';
            $html = self::code_the_steps($left, '</tr></tbody></table>', $html); //Panthera leo
            $left = '<table class="toccolours" style="float:right; clear:right; width:300px; margin-left: 1em;">';
            $html = self::code_the_steps($left, '</tr></tbody></table>', $html); //Pale fox
        }
        if($this->language_code == "hu") { /* for hu Eli updates: 10-30-2019 */
            $html = self::code_the_steps('<tr class="taxobox-heading">', '</td></tr>', $html, true);
            // <table class="infobox biota taxobox taxobox-animalia">
            $html = self::code_the_steps('<table class="infobox', '</tbody></table>', $html, true);
        }
        if($this->language_code == "tr") {
            $left = '<table class="infobox biota"';
            $right = '<p><b>';
            $right2 = '<div class="thumb';
            $html = self::code_the_steps_v2($left, $right, $right2, $html);
        }
        if($this->language_code == "uk") {
            $left = '<table class="toccolours"';
            $right = '<div class';
            $right2 = '<p>';
            $html = self::code_the_steps_v2($left, $right, $right2, $html);
            /* orig
            $orig = $html;
            $left = '<table class="toccolours"'; $right = '<div class';
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                $substr = $left.$arr[1]; //.$right; //no right term here
                $html = str_ireplace($substr, '', $html);
                
                if(stripos($html, "Special:CentralAutoLogin") !== false) {} //string is found
                else { //try ending with '<p>'. Means using '<div class' erased most of the article already
                    $html = $orig;
                    $left = '<table class="toccolours"'; $right = '<p>';
                    if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                        $substr = $left.$arr[1]; //.$right; //no right term here
                        $html = str_ireplace($substr, '', $html);
                    }
                }
            }
            */
        }
        if($this->language_code == "no") {
            $html = self::code_the_steps('<table class="taksoboks"', '</tr></tbody></table>', $html);
        }
        if($this->language_code == "vi") {
            $html = self::code_the_steps('<table class="infobox taxobox"', '</tr></tbody></table>', $html);
        }
        if($this->language_code == "pl") {
            $html = self::code_the_steps('<table class="infobox">', '</td></tr></tbody></table>', $html);
        }
        if($this->language_code == "nl") {
            /*
            <table class="toccolours vatop infobox"
            option 1: <a href="/wiki/Portaal:Zoogdieren" title="Portaal:Zoogdieren">Zoogdieren</a>
            option 1: <a href="/wiki/Portaal:Biologie" title="Portaal:Biologie">Biologie</a>
            option 1: <div class="noprint thumb tright"
            option 2: <div class="thumb tright">
            option 3: <div class="thumb tleft">
            */
            $html = self::code_the_steps('<table class="toccolours vatop infobox"', '<a href="/wiki/Portaal:Zoogdieren" title="Portaal:Zoogdieren">Zoogdieren</a>', $html);
            $html = self::code_the_steps('<table class="toccolours vatop infobox"', '<a href="/wiki/Portaal:Biologie" title="Portaal:Biologie">Biologie</a>', $html);

            if(preg_match("/<table class=\"toccolours vatop infobox\"(.*?)<div class=\"noprint thumb tright\"/ims", $html, $arr)) {
                $substr = '<table class="toccolours vatop infobox"'.$arr[1];
                $html = str_ireplace($substr, '', $html);
            }
            elseif(preg_match("/<table class=\"toccolours vatop infobox\"(.*?)<div class=\"thumb tright\">/ims", $html, $arr)) {
                $substr = '<table class="toccolours vatop infobox"'.$arr[1];
                $html = str_ireplace($substr, '', $html);
            }
            elseif(preg_match("/<table class=\"toccolours vatop infobox\"(.*?)<div class=\"thumb tleft\">/ims", $html, $arr)) {
                $substr = '<table class="toccolours vatop infobox"'.$arr[1];
                $html = str_ireplace($substr, '', $html);
            }

            /* remove weird auto inclusion */
            $tmp = '<img alt="" src="//upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Klippfiskproduksjon.jpg/260px-Klippfiskproduksjon.jpg" width="260" height="195" class="thumbimage" srcset="//upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Klippfiskproduksjon.jpg/390px-Klippfiskproduksjon.jpg 1.5x, //upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Klippfiskproduksjon.jpg/520px-Klippfiskproduksjon.jpg 2x" data-file-width="1024" data-file-height="768" />';
            $html = str_ireplace($tmp, "", $html);
        }

        //used in ru - Russian
        if($this->language_code == "ru") {
            $option = array();
            if(preg_match("/<table class=\"infobox\"(.*?)<div class=\"thumb tright\">/ims", $html, $arr)) { //for ru, Gadus morhua
                $substr = '<table class="infobox"'.$arr[1].'<div class="thumb tright">';
                $tmp = str_ireplace($substr, '<div class="thumb tright">', $html);
                $option[1] = array('strlen' => strlen($arr[1]), 'html' => $tmp);
            }
            if(preg_match("/<table class=\"infobox\"(.*?)<div class=\"dablink noprint\">/ims", $html, $arr)) { //for ru, Panthera leo
                $substr = '<table class="infobox"'.$arr[1].'<div class="dablink noprint">';
                $tmp = str_ireplace($substr, '<div class="dablink noprint">', $html);
                $option[2] = array('strlen' => strlen($arr[1]), 'html' => $tmp);
            }
            if(@$option[1] && @$option[2]) {
                if($option[1]['strlen'] < $option[2]['strlen']) $html = $option[1]['html'];
                else                                            $html = $option[2]['html'];
            }
            elseif(@$option[1]) $html = $option[1]['html'];
            elseif(@$option[2]) $html = $option[2]['html'];
        }
        
        if(preg_match("/<table class=\"infobox bordered\"(.*?)<\/table>/ims", $html, $arr)) { //for es, 
            $substr = '<table class="infobox bordered"'.$arr[1].'</table>';
            $html = str_ireplace($substr, '', $html);
        }
        elseif(preg_match("/<table class=\"infobox_v2\"(.*?)<\/table>/ims", $html, $arr)) { //for es, 
            $substr = '<table class="infobox_v2"'.$arr[1].'</table>';
            $html = str_ireplace($substr, '', $html);
        }
        elseif(preg_match("/<table class=\"infobox\"(.*?)<\/table>/ims", $html, $arr)) { //for es & sv (Swedish)
            $substr = '<table class="infobox"'.$arr[1].'</table>';
            $html = str_ireplace($substr, '', $html);
        }
        elseif(preg_match("/<table class=\"infobox biota\"(.*?)<\/table>/ims", $html, $arr)) { //for en, 
            $substr = '<table class="infobox biota"'.$arr[1].'</table>';
            $html = str_ireplace($substr, '', $html);
        }
        elseif(preg_match("/<table class=\"sinottico\"(.*?)<\/table>/ims", $html, $arr)) { //for it, 
            $substr = '<table class="sinottico"'.$arr[1].'</table>';
            $html = str_ireplace($substr, '', $html);
        }
        
        if($this->language_code == "lt") {
            if(preg_match("/<table class=\"toccolours\"(.*?)<\/tbody><\/table>/ims", $html, $arr)) {
                $substr = '<table class="toccolours"'.$arr[1].'</tbody></table>';
                $html = str_ireplace($substr, '', $html);
            }
            if(preg_match_all("/<small>(.*?)<\/small>/ims", $html, $arr)) {
                foreach($arr[1] as $item) {
                    $substr = '<small>'.$item.'</small>';
                    $html = str_ireplace($substr, '', $html);
                }
            }
            if(preg_match("/<table border=\"0\" align=\"right\" width=\"250\" cellpadding=\"4\" cellspacing=\"0\" class=\"noprint\"(.*?)<\/tbody><\/table>/ims", $html, $arr)) { //for ka)
                $substr = '<table border="0" align="right" width="250" cellpadding="4" cellspacing="0" class="noprint"'.$arr[1].'</tbody></table>';
                $html = str_ireplace($substr, '', $html);
            }
            if(preg_match("/<div class=\"noprint\"(.*?)<\/i><\/div>/ims", $html, $arr)) {
                $substr = '<div class="noprint"'.$arr[1].'</i></div>';
                $html = str_ireplace($substr, '', $html);
            }
            
            if(preg_match_all("/<div id=\"Vorlage_Lesenswert\">(.*?)<\/div>/ims", $html, $arr)) {
                foreach($arr[1] as $item) {
                    $substr = '<div id="Vorlage_Lesenswert">'.$item.'</div>';
                    $html = str_ireplace($substr, '', $html);
                }
            }
        }
        
        if(in_array($this->language_code, array("ka", "lt"))) {
            if(preg_match_all("/<div class=\"noresize\">(.*?)<\/div>/ims", $html, $arr)) { //for ka)
                foreach($arr[1] as $item) {
                    $substr = '<div class="noresize">'.$item.'</div>';
                    $html = str_ireplace($substr, '', $html);
                }
            }
            if(preg_match_all("/<img (.*?)>/ims", $html, $arr)) { //for ka)
                foreach($arr[1] as $item) {
                    if(stripos($item, 'distribution') !== false) { //string is found
                        $substr = '<img '.$item.'>';
                        $html = str_ireplace($substr, '', $html);
                    }
                }
            }
            if(preg_match_all("/<tr class=\"\"(.*?)<\/tr>/ims", $html, $arr)) { //for ka)
                foreach($arr[1] as $item) {
                    $substr = '<tr class=""'.$item.'</tr>';
                    $html = str_ireplace($substr, '', $html);
                }
            }
            if(preg_match("/<table width=\"100%\" class=\"plainlinks\"(.*?)<\/table>/ims", $html, $arr)) { //for ka)
                $substr = '<table width="100%" class="plainlinks"'.$arr[1].'</table>';
                $html = str_ireplace($substr, '', $html);
            }
            if(preg_match("/<table class=\"infobox\"(.*?)<\/tbody><\/table>/ims", $html, $arr)) { //for ka)
                $substr = '<table class="infobox"'.$arr[1].'</tbody></table>';
                $html = str_ireplace($substr, '', $html);
            }

            $left = '<img alt="Šis straipsnis';
            $right = '/>';
            $html = self::remove_all_in_between_inclusive($left, $right, $html);
        }
        /* old
        if($this->language_code == "fr") {
            if(preg_match("/<div class=\"infobox_(.*?)<p>/ims", $html, $arr)) { //for fr, <div class="infobox_v3 large taxobox_v3 zoologie animal bordered" style="width:20em">
                $substr = '<div class="infobox_'.$arr[1]."<p>";
                $html = str_ireplace($substr, '', $html);
            }
        }
        */
        if($this->language_code == "de") {
            /*<table cellpadding="2" class="float-right taxobox" id="Vorlage_Taxobox" style="width:300px;" summary="Taxobox">*/
            if(preg_match("/summary=\"Taxobox\">(.*?)<\/table>/ims", $html, $arr)) { //for de, 
                $substr = 'summary="Taxobox">'.$arr[1].'</table>';
                $html = str_ireplace($substr, '></table>', $html);
            }
        }

        if($this->language_code == "zh") { //e.g. Gadus morhua
            /*
            <tr style="text-align:center; background:rgb(211,211,164);">
            <th><a href="/wiki/%E7%95%B0%E5%90%8D" title="異名">異名</a>
            </th></tr>
            <tr>
            <td style="padding:0 .5em; text-align:left;">
            <ul><li><i>Gadus arenosus</i><br><small>Mitchill, 1815</small></li>
            <li><i>Gadus callarias</i><br><small>Linnaeus, 1758</small></li>
            <li><i>Gadus callarias hiemalis</i><br><small>Taliev, 1931</small></li>
            <li><i>Gadus callarias kildinensis</i><br><small>Derjugin, 1920</small></li>
            <li><i>Gadus heteroglossus</i><br><small>Walbaum, 1792</small></li>
            <li><i>Gadus morhua callarias</i><br><small>Linnaeus, 1758</small></li>
            <li><i>Gadus morhua kildinensis</i><br><small>Derjugin, 1920</small></li></ul>
            </td></tr></tbody></table>
            */
            $part = '<th><a href="/wiki/%E7%95%B0%E5%90%8D" title="異名">異名</a>';
            if(preg_match("/".preg_quote($part,'/')."(.*?)<\/td><\/tr><\/tbody><\/table>/ims", "xxx".$html, $arr)) {
                $substr = $part.$arr[1].'</td></tr></tbody></table>';
                $html = str_ireplace($substr, '</tr></tbody></table>', $html);
            }
        }

        if($this->language_code == "pt") { //e.g. Polar bear
            /*
            <div class="NavHead" style="font-size: 105%; background: transparent; text-align: left;">Sinonímia da espécie<sup id="cite_ref-Wozencraft2005_2-0" class="reference"><a href="#cite_note-Wozencraft2005-2"><span>[</span>2<span>]</span></a></sup></div>
            <ul class="NavContent" style="text-align: left; font-size: 105%; margin-top: 0; margin-bottom: 0; line-height: inherit;"><li style="line-height: inherit; margin: 0"> <i>Ursus marinus</i> <span style="font-size:85%;">Pallas, 1776</span>
             </li><li style="line-height: inherit; margin: 0"> <i>Ursus polaris</i> <span style="font-size:85%;">Shaw, 1792</span>
             </li><li style="line-height: inherit; margin: 0"> <i>Thalassarctos eogroenlandicus</i> <span style="font-size:85%;">Knottnerus-Meyer, 1908</span>
             </li><li style="line-height: inherit; margin: 0"> <i>Thalassarctos jenaensis</i> <span style="font-size:85%;">Knottnerus-Mayer, 1908</span>
            </li></ul>
            */
            $part = '<div class="NavHead" style="font-size: 105%; background: transparent; text-align: left;">Sinonímia da espécie';
            if(preg_match("/".preg_quote($part,'/')."(.*?)<\/li><\/ul>/ims", "xxx".$html, $arr)) {
                $substr = $part.$arr[1].'</li></ul>';
                $html = str_ireplace($substr, '', $html);
            }
        }
        
        if($this->language_code == "ja") { //e.g. Panthera leo
            /*
            <tr>
            <th style="background:rgb(211,211,164); text-align:center;"><a href="/wiki/%E3%82%B7%E3%83%8E%E3%83%8B%E3%83%A0" title="シノニム">シノニム</a>
            </th></tr>
            <tr>
            <td>
            <p><i>Felis leo</i> Linnaeus, 1758<sup id="cite_ref-haas_et_al_4-1" class="reference"><a href="#cite_note-haas_et_al-4">&#91;4&#93;</a></sup>
            <br />
            <i>Leo leo hollisteri</i> Allen, 1924<sup id="cite_ref-haas_et_al_4-2" class="reference"><a href="#cite_note-haas_et_al-4">&#91;4&#93;</a></sup>
            </p>
            </td></tr>
            */
            $part = '<a href="/wiki/%E3%82%B7%E3%83%8E%E3%83%8B%E3%83%A0" title="シノニム">シノニム</a>';
            if(preg_match("/".preg_quote($part,'/')."(.*?)<\/td><\/tr>/ims", "xxx".$html, $arr)) {
                $substr = $part.$arr[1].'</td></tr>';
                $html = str_ireplace($substr, '</th></tr>', $html);
            }
        }
        /* remove form elements e.g. <input type="checkbox" role="button" id="toctogglecheckbox" /> */
        if(preg_match_all("/<input type=(.*?)>/ims", $html, $arr)) {
            foreach($arr[1] as $str) {
                $substr = '<input type='.$str.'>';
                $html = str_ireplace($substr, '', $html);
            }
        }
        
        /* additional sections to remove */ // e.g. Panthera leo 'nl'
        $html = self::code_the_steps('<div id="tpl_Woordenboek"', '</div>', $html);
        $html = self::code_the_steps('<div class="interProject wiktionary"', '</div>', $html);
        
        /* 'sv' 'de' Polar bear | nl Gadus morhua  -->> remove erroneous video play */
        for($i = 0; $i <= 10; $i++) {
            $html = self::code_the_steps('<div id="mwe_player_'.$i.'"', '</div>', $html, true);
        }

        /* cs Panthera leo */
        $left = '<div class="navbox noprint"';
        $right = '<div class';
        $right = '<div class="catlinks"';
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
            // exit("\n".count($arr[1])."\n");
            foreach($arr[1] as $str) {
                $substr = $left.$str; //.$right; //no right term here
                $html = str_ireplace($substr, '', $html);
            }
        }

        /* uk Animalia - these are the boxes after biblio */
        $html = self::code_the_steps('<table cellspacing="0" class="navbox"', '</td></tr></tbody></table></td></tr></tbody></table>', $html);
        
        /* ro Polar bear - remove a warning message on top of the article */
        $html = self::code_the_steps('<table class="metadata plainlinks ambox ambox ambox-content ambox-content"', '</table>', $html);
        $html = self::code_the_steps('<table class="metadata plainlinks ambox ambox ambox-style ambox-style"', '</table>', $html);
        
        return $html;
    }
    private function remove_categories_section($html, $url, $language_code)
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
            echo("\n-----\nNot found, investigate [$language_code]\n[$url]\n[$limit]\nstrlen HTML = ".count($html)."\n-----\n"); //Previously exits here.
            // Cause for investigation, check final wiki if OK, since we continued process for now.
        }
        
        // /* remove - general purpose sections: Eli updates: 11-04-2019 
        $html = self::code_the_steps('<span class="error mw-ext-cite-error"', '</span>', $html, true);
        // */
        
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
        if($language_code == "eu") { /* for eu Eli updates: 11-04-2019 */
            $html = self::code_the_steps('<table class="navbox collapsible autocollapse"', '</tbody></table>', $html);
            $html = self::code_the_steps('<h2><span class="mw-headline" id="Kanpo_loturak">', '</h2>', $html);
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
        
        // /* remove - general purpose sections: Eli updates: 11-04-2019 
        $html = self::code_the_steps('<td class="mbox-image"', '</td>', $html, true);
        $html = self::code_the_steps('<td class="mbox-text"', '</td>', $html, true);
        // */
        
        if($language_code == "bg") {
            $html = self::code_the_steps('<div id="stub" class="boilerplate metadata plainlinks noprint"', '</div></div>', $html);
        }

        if($language_code == "cy") {
            $html = self::code_the_steps('<div class="floatnone">', '</div>', $html);
            // exit("\n$html\n");
            $html = self::code_the_steps('<div class="infobox"', '</div></div>', $html);
            $html = self::code_the_steps('<div class="infobox"', '</div>&nbsp;</div>', $html);
            $html = self::code_the_steps('<div style="clear: both; background-color: #f9f9f9;', '</div>', $html);
        }
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
        $html = str_ireplace('<span class="mw-editsection-divider"> • </span>', '', $html); //first client is lang = 'vo'
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
        $first10langs = array("en", "es", "it", "de", "zh", "ru", "pt", "ja", "ko"); //removed "fr"
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
        /* old
        if($this->language_code == "fr") return '<span class="mw-headline" id="Notes_et_références">Notes et références</span>';
        */
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
            
            /* old
            if($this->language_code == "fr") {
                $section_after_biblio = '<ul id="bandeau-portail" class="bandeau-portail">';
                if(preg_match("/xxx(.*?)".preg_quote($section_after_biblio,'/')."/ims", "xxx".$html, $arr)) return $arr[1];
            }
            */
            
            if($this->language_code == "ru") { //another suggested biblio_section for 'ru'
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
        /* old
        elseif($this->language_code == "fr") { //<div class="navbox-container" style="clear:both;">
            if(preg_match("/<div class=\"navbox-container\"(.*?)xxx/ims", $html."xxx", $arr)) {
                $substr = '<div class="navbox-container"'.$arr[1].'xxx';
                $html = str_ireplace($substr, '', $html."xxx");
            }
        }
        */
        else { //for ko and for the rest of the languages...not good
            if(preg_match("/<div role=\"navigation\" class=\"navbox\"(.*?)xxx/ims", $html."xxx", $arr)) {
                $substr = '<div role="navigation" class="navbox"'.$arr[1].'xxx';
                $html = str_ireplace($substr, '', $html."xxx");
            }
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
    private function bot_inspired($html)
    {
        if(stripos($html, "Robot icon.svg") !== false && stripos($html, "Lsjbot") !== false) return true; //string is found
        return false;
    }
}
?>
