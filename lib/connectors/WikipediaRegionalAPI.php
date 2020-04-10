<?php
namespace php_active_record;
/*
require_once(DOC_ROOT . '/vendor/ForceUTF8/Encoding.php');
use \ForceUTF8\Encoding;  // It's namespaced now.
*/

/* connector: [957 - German] */

class WikipediaRegionalAPI
{
    function __construct($resource_id, $language_code)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->language_code = $language_code;
        $this->wikipedia_api = "http://en.wikipedia.org/w/api.php";

        if(Functions::is_production())  $path = '/extra/eol_cache_wiki_regions/';                   //for eol-archive
        else                            $path = '/Volumes/Thunderbolt4/eol_cache_wiki_regions/';    //for local
        if($resource_id == 957) $this->download_options = array('resource_id' => $resource_id,  'download_wait_time' => 3000000, 'timeout' => 10800, 'download_attempts' => 1); //'delay_in_minutes' => 1
        else                    $this->download_options = array('cache_path' => $path,          'download_wait_time' => 3000000, 'timeout' => 10800, 'download_attempts' => 1); //'delay_in_minutes' => 1
        $this->download_options['expire_seconds'] = 60*60*24*25;
        
        $this->ranks['de'] = array("reich", "klasse", "ordnung", "familie", "gattung");
        $this->ranks_en['reich']    = "kingdom";
        $this->ranks_en['klasse']   = "class";
        $this->ranks_en['ordnung']  = "order";
        $this->ranks_en['familie']  = "family";
        $this->ranks_en['gattung']  = "genus";
        $this->word_User_for_this_region = "Benutzer";

        //translations
        $this->trans['Page']['en'] = "Page";
        $this->trans['Modified']['en'] = "Modified";
        $this->trans['Retrieved']['en'] = "Retrieved";

        $this->trans['Page']['de'] = "Seite";
        $this->trans['Modified']['de'] = "Bearbeitungsstand";
        $this->trans['Retrieved']['de'] = "Abgerufen";

        $this->trans['Page']['es'] = "Página";
        $this->trans['Modified']['es'] = "Modificado";
        $this->trans['Retrieved']['es'] = "Recuperado";

        $this->trans['Page']['fr'] = "Page";
        $this->trans['Modified']['fr'] = "Modifié";
        $this->trans['Retrieved']['fr'] = "Récupéré";
    }
    /* exit("\nThis is no longer being used. Replaced by wikipedia.php for sometime now0.\n");
    function generate_archive()
    {
        self::get_taxa_with_taxobox();
        $this->archive_builder->finalize(TRUE);
    }
    private function get_taxa_with_taxobox()
    {
        exit("\nThis is no longer being used. Replaced by wikipedia.php for sometime now1.\n");
        $eilimit = 500; //orig 500 debug
        $continue = false;
        $i = 0;
        $k = 0; //just used when caching, running multiple connectors
        while(true) {
            $url = $this->wikipedia_api . "?action=query&list=embeddedin&eititle=Template:taxobox&eilimit=$eilimit&format=json&continue=";
            if($continue) $url .= "&eicontinue=" . $continue;
            // echo "\n [$url] \n";
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $j = json_decode($json);
                if($val = @$j->continue->eicontinue) $continue = $val;
                else $continue = false;

                $k++;
                if(($k % 100) == 0) echo "\n count: $k";
                // breakdown when caching: as of 2015June03 total is 561 loops
                // $cont = false;
                // // if($k >=  1   && $k < 187) $cont = true;
                // // if($k >=  187 && $k < 374) $cont = true;
                // // if($k >=  374 && $k < 561) $cont = true;
                // if(!$cont) continue;
                
                if($recs = $j->query->embeddedin) {
                    $i = $i + count($recs);
                    echo "\n" . count($recs) . " -- " . number_format($i) . "\n";
                    self::process_pages_with_taxobox($recs);
                }
            }
            else break;
            if(!$continue) break; //ends loop; all ids are processed
            // break; //debug
        }
    }
    private function process_pages_with_taxobox($recs)
    {
        exit("\nThis is no longer being used. Replaced by wikipedia.php for sometime now2.\n");
        $title = array();
        foreach($recs as $rec) {
            if($val = $rec->title) $titles[] = $val;
            if(count($titles) == 10) //10 is the manageable no. for the wikipedia api to respond correctly. 50 is max but saw unreliable results.
            {
                $url = $this->wikipedia_api . "?action=query&prop=langlinks&format=json&llprop=url&lllang=" . $this->language_code . "&continue=&titles=" . implode("|", $titles);
                if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                    $j = json_decode($json);
                    // echo "\n$url\n";
                    self::process_language_specific_pages($j, $url);
                }
                $titles = array();
                // break; //debug - to get only the first 10 taxa or titles
            }
        }
    }
    private function process_language_specific_pages($recs, $url)
    {
        exit("\nThis is no longer being used. Replaced by wikipedia.php for sometime now3.\n");
        if(!@$recs->query) {
            echo "\n Number of records:"; print_r($recs);
            $options = $this->download_options;
            $options['expire_seconds'] = 0; // 0 -> expires now, orig value; false -> does not expire
            Functions::lookup_with_cache($url, $options);
            return; //this means you have to run the connector again
        }
        foreach($recs->query->pages as $page_id => $rec) {
            $rekord = array();
            if($url = @$rec->langlinks[0]->url) {
                // echo "\n[$url]\n";
                $this->exclude_url['de'] = array("http://de.wikipedia.org/wiki/Fuchsf%C3%A4cherschwanz", "https://de.wikipedia.org/wiki/Fuchsf%C3%A4cherschwanz", 
                                                 "http://de.wikipedia.org/wiki/Bali_cattle", "https://de.wikipedia.org/wiki/Bali_cattle", "http://de.wikipedia.org/wiki/Br%C3%A4unling_(Vogel)", 
                                                 "http://de.wikipedia.org/wiki/Tachyoryctinae");
                $this->exclude_url['es'] = array("http://es.wikipedia.org/wiki/eli_boy");
                $this->exclude_url['fr'] = array("http://es.wikipedia.org/wiki/eli_boy");
                
                if(in_array($url, $this->exclude_url[$this->language_code])) continue;
            
                // e.g. "/wiki/Benutzer:"
                if(stripos($url, "/wiki/" . $this->word_User_for_this_region . ":") !== false) continue; //string is found
                if(stripos($url, "/wiki/User:") !== false) continue; //string is found
                
                $rekord['title'] = self::format_wiki_substr($rec->langlinks[0]->{"*"});
                $domain_name = self::get_domain_name($url);
                if($html = Functions::lookup_with_cache($url, $this->download_options)){
                    $html = self::prepare_wiki_for_parsing($html, $domain_name);
                    if(substr_count($html, "<div ") != substr_count($html, "</div>")) $rekord['not equal divs'] = true;
                    // <div id="content" class="mw-body" role="main">
                    // <div id="mw-content-text" lang="de" dir="ltr" class="mw-content-ltr"> --- the ending div for this is after the <div id="footer" role="contentinfo">
                    // <div id="mw-navigation">
                    // <div id="footer" role="contentinfo">

                    $rekord['comprehensive_desc'] = self::get_comprehensive_desc($html);
                    $rekord['sciname']          = self::get_sciname($html);
                    $rekord['ancestry']         = self::get_ancestry($html);
                    $rekord['permalink']        = self::get_permalink($html);
                    $rekord['brief_desc']       = self::get_brief_description($html);
                    $rekord['last_modified']    = self::get_last_modified($html);
                    $rekord['phrase']           = self::get_wikipedia_phrase($html);
                    $rekord['citation']         = self::get_citation($rekord['title'], $rekord['permalink'], $rekord['last_modified'], $rekord['phrase']);
                    
                    // print_r($rekord); //exit;
                    
                    if(is_numeric(stripos($html, 'summary="Taxobox">'))) {
                        if(!@$rekord['sciname']) { //needs investigation
                            echo "\n no sciname:[$html]";
                            print_r($rekord); //exit;
                        }
                        else {
                            // if($url == "http://de.wikipedia.org/wiki/Lactobacillus_paracasei") exit; //debug
                            self::create_archive($rekord);
                        }
                        
                    }
                }
            }// with valid url
        }
    }
    */
    function prepare_wiki_for_parsing($html, $domain_name)
    {
        $html = str_ireplace('href="//', "href=xxxxxx", $html);
        $html = str_ireplace('href="/', 'href="http://' . $domain_name . '/', $html);
        $html = str_ireplace('href=xxxxxx', 'href="//', $html);
        
        //new
        $html = str_ireplace('src="//', 'src="https://', $html);
        $html = str_ireplace('srcset="//', 'srcset="https://', $html);
        
        return $html;
    }
    function get_comprehensive_desc($html)
    {
        $lang = $this->language_code;
        if(preg_match("/<div id=\"mw-content-text\" lang=\"$lang\" dir=\"ltr\" class=\"mw-content-ltr\">(.*?)<div id=\"mw-navigation\">/ims", $html, $arr)) return self::format_wiki_substr($arr[1]);
//                      <div id=\"mw-content-text\" lang=\"$lang\" dir=\"ltr\" class=\"mw-content-ltr\">
//                      <div id="mw-content-text" lang="bs" dir="ltr" class="mw-content-ltr">
//                      <div id="mw-navigation">
        else {
            if($lang == 'no')             $lang = 'nb'; //2nd option for 'no' Norwegian is to use 'nb'.
            elseif($lang == 'zh-min-nan') $lang = 'nan';
            elseif($lang == 'bat-smg')    $lang = 'sgs';
            else {
                // /* for future investigation. Initial finding is that the article is not worthy to publish
                // echo "\n$html\n";
                echo("\nInvestigate WikipediaRegionalAPI 1st try [$lang]...\n");
                // exit(-1);
                // */
            }
            if(preg_match("/<div id=\"mw-content-text\" lang=\"$lang\" dir=\"ltr\" class=\"mw-content-ltr\">(.*?)<div id=\"mw-navigation\">/ims", $html, $arr)) return self::format_wiki_substr($arr[1]);
            else exit("\nInvestigate WikipediaRegionalAPI 2nd try [$lang] [".strlen($html)."]...\n$html\n");
        }
    }
    function get_domain_name($url)
    {
        if(preg_match("/http:\/\/(.*?)\//ims", pathinfo($url, PATHINFO_DIRNAME), $arr)) return $arr[1];
        if(preg_match("/https:\/\/(.*?)\//ims", pathinfo($url, PATHINFO_DIRNAME), $arr)) return $arr[1];
    }
    private function get_sciname($html) //for 'de' only
    {
        if(preg_match_all("/<td class=\"taxo-name\">(.*?)<\/td>/ims", $html, $arr)) {
            $items = array_reverse($arr[1]);
            foreach($items as $item) {
                if($val = trim(strip_tags($item))) return $val;
            }
        }

        // second option e.g. http://de.wikipedia.org/wiki/Piranhas
        if(preg_match("/<td class=\"taxo-bild\" style=\"font-size:smaller;\">(.*?)<\/td>/ims", $html, $arr)) {
            $sciname = strip_tags($arr[1], "<i>");
            if(preg_match("/<i>(.*?)<\/i>/ims", $sciname, $arr)) return $arr[1];
            elseif(preg_match("/\((.*?)\)/ims", $sciname, $arr)) {
                if(!is_numeric(stripos($arr[1], "."))) { //e.g. (E. a. americanus)
                    if(!(substr_count($arr[1], " ") > 2)) return $arr[1]; //e.g. (Die Länge des weißen Striches entspricht 1 Mikrometer) in http://de.wikipedia.org/wiki/Haptophyta    
                }
            }
            else $backup_sciname =  $sciname;
        }
        return false;
    }
    private function get_ancestry($html) //for 'de' only
    {
        $ancestry = array();
        if(preg_match("/<table class=\"toptextcells\"(.*?)<\/table>/ims", $html, $arr)) {
            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $arr2)) {
                foreach($arr2[1] as $item) {
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $item, $arr3)) {
                        $td = $arr3[1];
                        foreach($this->ranks[$this->language_code] as $rank) {
                            if(is_numeric(stripos($td[0], ">$rank</a>"))) //e.g. >Ordnung</a>
                            {
                                if(preg_match("/\((.*?)\)/ims", strip_tags($td[1], "<i>"), $arr4)) $ancestry[$this->ranks_en[$rank]] = strip_tags($arr4[1]);
                                elseif(preg_match("/<i>(.*?)<\/i>/ims", strip_tags($td[1], "<i>"), $arr4)) $ancestry[$this->ranks_en[$rank]] = $arr4[1];
                            }
                        }
                    }
                }
            }
        }
        return $ancestry;
    }
    function get_permalink($html)
    {
        // <li id="t-permalink"><a href="/w/index.php?title=Piranhas&amp;oldid=140727080" title="Dauerhafter Link zu dieser Seitenversion">Permanenter Link</a></li>
        if(preg_match("/<li id=\"t-permalink\"><a href=\"(.*?)\"/ims", $html, $arr)) return html_entity_decode($arr[1]);
    }
    private function get_brief_description($html) //for 'de' only
    {
        if(preg_match("/summary=\"Taxobox\">(.*?)<p><\/p>/ims", $html, $arr)) {
            if(preg_match_all("/<p>(.*?)<\/p>/ims", $arr[1], $arr2)) return self::format_wiki_substr(array_pop($arr2[1])); //get the last <p>xxx</p> 
        }
        elseif(preg_match("/summary=\"Taxobox\">(.*?)<h2>/ims", $html, $arr)) {
            if(preg_match_all("/<p>(.*?)<\/p>/ims", $arr[1], $arr2)) return self::format_wiki_substr(array_pop($arr2[1])); //get the last <p>xxx</p> 
        }
    }
    function get_last_modified($html)
    {
        if($this->language_code == 'de') {
            // <li id="footer-info-lastmod"> Diese Seite wurde zuletzt am 12. Dezember 2013 um 20:57 Uhr geändert.</li>
            if(preg_match("/<li id=\"footer-info-lastmod\"> Diese Seite wurde zuletzt am(.*?)Uhr geändert\./ims", $html, $arr)) return trim(str_replace(" um ", ", ", $arr[1]));
        }
        if($this->language_code == 'es') {
            //<li id="footer-info-lastmod"> Esta página fue modificada por última vez el 18 mar 2017 a las 10:54.</li>
            if(preg_match("/<li id=\"footer-info-lastmod\"> Esta página fue modificada por última vez el(.*?)\./ims", $html, $arr)) return trim(str_replace(" a las ", ", ", $arr[1]));
        }
        if($this->language_code == 'fr') {
            //<li id="footer-info-lastmod"> Dernière modification de cette page le 22 juillet 2016, à 08:05.</li>
            if(preg_match("/<li id=\"footer-info-lastmod\"> Dernière modification de cette page le(.*?)\./ims", $html, $arr)) return trim(str_replace(", à ", ", ", $arr[1]));
        }
        if($this->language_code == 'en') {
            //<li id="footer-info-lastmod"> This page was last modified on 18 March 2017, at 20:43.</li>
            if(preg_match("/<li id=\"footer-info-lastmod\"> This page was last modified on(.*?)\./ims", $html, $arr)) return trim(str_replace(", at ", ", ", $arr[1]));
        }
        
        // <li id="footer-info-lastmod"> Deze pagina is het laatst bewerkt op 10 mrt 2017 om 13:22.</li>
        return self::get_start_of_numerical_part($html);
    }
    private function get_start_of_numerical_part($html)
    {
        if(preg_match("/<li id=\"footer-info-lastmod\">(.*?)<\/li>/ims", $html, $arr)) {
            $str = $arr[1]; //echo "\n$str\n";
            for ($x = 0; $x <= strlen($str); $x++) {
                if(is_numeric(substr($str,$x,1))) break;
            }
            $final = trim(substr($str,$x,strlen($str)));
            // echo "\n[" . $final . "]\n";
            if(substr($final,-1) == ".") $final = trim(substr($final,0,strlen($final)-1)); //remove last char if it is period
            // echo "\n[" . $final . "]\n";
            return $final;
        }
    }
    function translate_source_target_lang($source_text, $source_lang, $target_lang) /* Still being used by wikipedia.php connector -> WikiDataAPI.php library */
    {
        // based from: https://ctrlq.org/code/19909-google-translate-api
        $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=" . $source_lang . "&tl=" . $target_lang . "&dt=t&q=" . $source_text;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        // if($target_lang == 'min') $options['expire_seconds'] = 0; //debug only
        $ret_str = '';
        if($json = Functions::lookup_with_cache($url, $options)) { //always cache expires false, since this is just a term translation
            // if(preg_match("/\"(.*?)\"/ims", $json, $arr)) return ucfirst($arr[1]); //orig
            if(preg_match("/\"(.*?)\"/ims", $json, $arr)) $ret_str = trim(self::format_wiki_substr(ucfirst($arr[1])));
        }
        if(strlen($ret_str) <= 2) $ret_str = $source_text;
        return $ret_str;
    }
    private function translate($source_text)
    {
        if($val = @$this->trans[$source_text][$this->language_code]) return $val;
        else return self::translate_source_target_lang($source_text, "en", $this->language_code);
    }
    function get_wikipedia_phrase($html)
    {
        //<div id="siteSub">De Wikipedia, la enciclopedia libre</div>
        if(preg_match("/<div id=\"siteSub\">(.*?)<\/div>/ims", $html, $arr)) return ucfirst(trim($arr[1]));
    }
    function get_citation($title, $permalink, $last_modified, $phrase, $translated_terms = array())
    {
        /* orig
        if($this->language_code == 'de')
        {return "Seite '" . $title . "'. In: Wikipedia, Die freie Enzyklopädie. Bearbeitungsstand: " . $last_modified . ". URL: " . $permalink . " (Abgerufen: " . date("d. F Y, h:i T") . ")";}
        */
        if(!$translated_terms) return self::translate("Page") . " '" . $title . "'. $phrase. " . self::translate("Modified") . ": " . $last_modified . ". URL: " . $permalink . " (" . self::translate("Retrieved") . ": " . date("d. F Y, h:i T") . ")";
        else                   return $translated_terms["Page"] . " '" . $title . "'. $phrase. " . $translated_terms["Modified"] . ": " . $last_modified . ". URL: " . $permalink . " (" . $translated_terms["Retrieved"] . ": " . date("d. F Y, h:i T") . ")";
    }
    /* not used since it will call another extra webpage
    private function get_citation_v1($html)
    {
        // <li id="t-cite"><a href="/w/index.php?title=Spezial:Zitierhilfe&amp;page=Zwergst%C3%B6rwels&amp;id=133499084" title="Hinweise, wie diese Seite zitiert werden kann">Artikel zitieren</a></li>
        if(preg_match("/<li id=\"t-cite\"><a href=\"(.*?)\"/ims", $html, $arr))
        {
            $url = html_entity_decode($arr[1]);
            if($html = Functions::lookup_with_cache($url, $this->download_options))
            {
                if(preg_match("/Einfache Zitatangabe zum Kopieren<\/span>(.*?)<\/p>/ims", $html, $arr))
                {
                    if(preg_match("/<p>(.*?)xxx/ims", $arr[1]."xxx", $arr2)) return $arr2[1];
                }
            }
        }
    }
    */
    private function create_archive($rec)
    {
        if($rec['comprehensive_desc'] || $rec['brief_desc']) {}
        else return; //if there are no objects, don't create the taxon as well. New Apr 17, 2019
        
        $t = new \eol_schema\Taxon();
        $t->taxonID                 = md5($rec['permalink']);
        $t->scientificName          = $rec['sciname'];
        $t->order                   = @$rec['ancestry']['order'];
        $t->family                  = @$rec['ancestry']['family'];
        $t->genus                   = @$rec['ancestry']['genus'];
        $t->furtherInformationURL   = $rec['permalink'];

        $ranks = array("order", "family", "genus");
        foreach($ranks as $rank) {
            if($t->$rank == $t->scientificName) $t->$rank = ''; //any of the ancestry names cannot be same as the scientificname
        }

        if(!isset($this->taxon_ids[$t->taxonID])) {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }

        //start media objects
        $media = array();
        
        // Comprehensive Description
        $media['identifier']             = md5($rec['permalink']."Comprehensive Description");
        $media['title']                  = $rec['title'];
        $media['description']            = $rec['comprehensive_desc'];
        $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description';
        // below here is same for the next text object
        $media['taxonID']                = $t->taxonID;
        $media['type']                   = "http://purl.org/dc/dcmitype/Text";
        $media['format']                 = "text/html";
        $media['language']               = $this->language_code;
        $media['Owner']                  = 'Wikipedia Autoren und Herausgeber';
        $media['UsageTerms']             = 'http://creativecommons.org/licenses/by-sa/3.0/';
        $media['furtherInformationURL'] = $rec['permalink'];
        if($media['description']) self::create_media_object($media);

        // Brief Summary
        $media['identifier']             = md5($rec['permalink']."Brief Summary");
        $media['title']                  = $rec['title'] . ': Brief Summary';
        $media['description']            = $rec['brief_desc'];
        $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
        if($media['description']) self::create_media_object($media);
    }
    private function create_media_object($media)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID                = $media['taxonID'];
        $mr->identifier             = $media['identifier'];
        $mr->type                   = $media['type'];
        $mr->format                 = $media['format'];
        $mr->language               = $media['language'];
        $mr->Owner                  = $media['Owner'];
        $mr->title                  = $media['title'];
        $mr->UsageTerms             = $media['UsageTerms'];
        $mr->description            = $media['description'];
        $mr->CVterm                 = $media['CVterm'];
        $mr->furtherInformationURL     = $media['furtherInformationURL'];
        if(!isset($this->object_ids[$mr->identifier]))
        {
            $this->object_ids[$mr->identifier] = '';
            $this->archive_builder->write_object_to_file($mr);
        }
    }
    private function format_wiki_substr($substr) //https://en.wikipedia.org/wiki/Control_character
    {   /*
        0 (null, NUL, \0, ^@), originally intended to be an ignored character, but now used by many programming languages to mark the end of a string.
        7 (bell, BEL, \a, ^G), which may cause the device receiving it to emit a warning of some kind (usually audible).
        8 (backspace, BS, \b, ^H), used either to erase the last character printed or to overprint it.
        9 (horizontal tab, HT, \t, ^I), moves the printing position some spaces to the right.
        10 (line feed, LF, \n, ^J), used as the end of line marker in most UNIX systems and variants.
        11 (vertical tab, VT, \v, ^K), vertical tabulation.
        12 (form feed, FF, \f, ^L), to cause a printer to eject paper to the top of the next page, or a video terminal to clear the screen.
        13 (carriage return, CR, \r, ^M), used as the end of line marker in Classic Mac OS, OS-9, FLEX (and variants). A carriage return/line feed pair is used by CP/M-80 and its derivatives including DOS and Windows, and by Application Layer protocols such as HTTP.
        26 (Control-Z, SUB, EOF, ^Z).
        27 (escape, ESC, \e (GCC only), ^[).
        127 (delete, DEL, ^?), originally intended to be an ignored chara

        $substr = str_replace(chr(32).chr(160), "", $substr);
        $substr = str_replace(array("\t\t\t\t"), "", $substr);
        $substr = str_replace(array("\r\n"), "", $substr);
        $substr = str_replace(array("\t", "\n", "\v", "\f", "\r"), "", $substr);
        $substr = str_replace(array(chr(9), chr(10), chr(11), chr(12), chr(13)), "", $substr);
        $substr = str_replace(array(chr(127), chr(129), chr(141), chr(143), chr(144), chr(157)), "", $substr);
        for ($x = 0; $x <= 31; $x++) $substr = str_replace(chr($x), "", $substr);
        $substr = str_replace(array("\t\n", "\n", "\r", "\t", "\o", "\xOB", "\11", "\011", "", ""), " ", trim($substr));
        $substr = str_replace(array("\r\n", "\n", "\r", "\t", "\0", "\x0B", "\t"), '', $substr);
        $substr = str_replace(" ", " ", $substr);
        */

        $substr = Functions::import_decode($substr);
        $substr = str_replace(array("\n", "\t", "\r", chr(9), chr(10), chr(13)), "", $substr);
        return Functions::remove_whitespace($substr);

        // $substr = Encoding::fixUTF8($substr);
        // $substr = Encoding::toUTF8($substr);
        
        /*
        Detect character encoding with current detect_order 
        echo "\n111 - ".mb_detect_encoding($substr)." -- ". strlen($substr) ."\n";
        "auto" is expanded according to mbstring.language 
        echo "\n222 - ".mb_detect_encoding($substr, "auto")." -- ". strlen($substr) ."\n";
        Specify encoding_list character encoding by comma separated list 
        $detected = mb_detect_encoding($substr, "UTF-8, JIS, eucjp-win, sjis-win");
        echo "\n333 - ".$detected." -- ". strlen($substr) ."\n";
        */
    }
    private function remove_utf8_bom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        /* another option:
        text = str_replace("\xEF\xBB\xBF",'',$text); 
        */
        return $text;
    }
    /* will be replaced by WikiData
    $this->ranks['es'] = array("reino", "filo", "clase", "orden", "familia", "género");
    $this->ranks_en['reino']    = "kingdom";
    $this->ranks_en['filo']     = "phylum";
    $this->ranks_en['clase']    = "class";
    $this->ranks_en['orden']    = "order";
    $this->ranks_en['familia']  = "family";
    $this->ranks_en['género']   = "genus";
    $this->word_User_for_this_region = "Usuario";

    $this->ranks['fr'] = array("règne", "embranchement", "classe", "ordre", "famille", "genre");
    $this->ranks_en['reino']    = "règne";
    $this->ranks_en['filo']     = "embranchement"; //"phylum";
    $this->ranks_en['clase']    = "classe";
    $this->ranks_en['orden']    = "ordre";
    $this->ranks_en['familia']  = "famille";
    $this->ranks_en['género']   = "genre";
    $this->word_User_for_this_region = "Utilisateur";
    */
}
?>