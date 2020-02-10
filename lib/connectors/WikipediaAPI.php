<?php
namespace php_active_record;
class WikipediaAPI
{
    function __construct()
    {
    }
    function some_initialization()
    {   //some default translations:
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
        
        /* *** e.g. szl, nv, pnb, br -- to avoid re-doing lookup_cache() knowing the remote won't respond
        $trans['Page']['br'] = "Page";
        $trans['Modified']['br'] = "Modified";
        $trans['Retrieved']['br'] = "Retrieved";
        $trans['Wikipedia authors and editors']['br'] = "Wikipedia authors and editors";
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
        if($media['CVterm'] == 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description')  $file = DOC_ROOT."Description.html";
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
    private function remove_infobox($html) //and html form elements e.g. <input type...>
    {
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
        
        if($this->language_code == "fr") {
            if(preg_match("/<div class=\"infobox_(.*?)<p>/ims", $html, $arr)) { //for fr, <div class="infobox_v3 large taxobox_v3 zoologie animal bordered" style="width:20em">
                $substr = '<div class="infobox_'.$arr[1]."<p>";
                $html = str_ireplace($substr, '', $html);
            }
        }
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
            echo("\n-----\nNot found, investigate [$language_code]\n[$url]\n-----\n"); //Previously exits here.
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
    private function bot_inspired($html)
    {
        if(stripos($html, "Robot icon.svg") !== false && stripos($html, "Lsjbot") !== false) return true; //string is found
        return false;
    }
}
?>
