<?php
namespace php_active_record;
/* connector: [957 - German] */

class WikipediaRegionalAPI
{
    function __construct($resource_id, $language_code)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->language_code = $language_code;
        $this->wikipedia_api = "http://en.wikipedia.org/w/api.php";
        $this->download_options = array('resource_id' => $resource_id, 'expire_seconds' => false, 'download_wait_time' => 5000000, 'timeout' => 10800, 'download_attempts' => 1); //'delay_in_minutes' => 1
        $this->ranks['de'] = array("reich", "klasse", "ordnung", "familie", "gattung");
        $this->ranks_en['reich']     = "kingdom";        
        $this->ranks_en['klasse']     = "class";
        $this->ranks_en['ordnung']     = "order";
        $this->ranks_en['familie']     = "family";
        $this->ranks_en['gattung']     = "genus";
    }

    function generate_archive()
    {
        self::get_taxa_with_taxobox();
        $this->archive_builder->finalize(TRUE);
    }

    private function get_taxa_with_taxobox()
    {
        $eilimit = 500; //orig 500 debug
        $continue = false;
        $i = 0;
        $k = 0; //just used when caching, running multiple connectors
        while(true)
        {
            $url = $this->wikipedia_api . "?action=query&list=embeddedin&eititle=Template:taxobox&eilimit=$eilimit&format=json&continue=";
            if($continue) $url .= "&eicontinue=" . $continue;
            echo "\n [$url] \n";
            if($json = Functions::lookup_with_cache($url, $this->download_options))
            {
                $j = json_decode($json);
                if($val = @$j->continue->eicontinue) $continue = $val;
                else $continue = false;
                
                /* breakdown when caching: as of 2015June03 total is 561 loops
                $k++;
                $cont = false;
                // if($k >=  1   && $k < 187) $cont = true;
                // if($k >=  187 && $k < 374) $cont = true;
                // if($k >=  374 && $k < 561) $cont = true;
                if(!$cont) continue;
                */
                
                if($recs = $j->query->embeddedin)
                {
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
        $title = array();
        foreach($recs as $rec)
        {
            if($val = $rec->title) $titles[] = $val;
            if(count($titles) == 10) //10 is the manageable no. for the wikipedia api to respond correctly. 50 is max but saw unreliable results.
            {
                $url = $this->wikipedia_api . "?action=query&prop=langlinks&format=json&llprop=url&lllang=" . $this->language_code . "&continue=&titles=" . implode("|", $titles);
                if($json = Functions::lookup_with_cache($url, $this->download_options))
                {
                    $j = json_decode($json);
                    echo "\n$url\n";
                    self::process_language_specific_pages($j, $url);
                }
                $titles = array();
                // break; //debug - to get only the first 10 taxa or titles
            }
        }
    }
    
    private function process_language_specific_pages($recs, $url)
    {
        if(!@$recs->query)
        {
            echo "\n Number of records:"; print_r($recs);
            $options = $this->download_options;
            $options['expire_seconds'] = 0;
            Functions::lookup_with_cache($url, $options);
            return; //this means you have to run the connector again
        }
        foreach($recs->query->pages as $page_id => $rec)
        {
            $rekord = array();
            if($url = @$rec->langlinks[0]->url)
            {
                echo "\n[$url]\n";
                $this->exclude_url['de'] = array("http://de.wikipedia.org/wiki/Fuchsf%C3%A4cherschwanz", "http://de.wikipedia.org/wiki/Bali_cattle", "http://de.wikipedia.org/wiki/Benutzer:Ad_Libertatem/Spielwiese", 
                "http://de.wikipedia.org/wiki/Benutzer:Aquarius_Rising", "http://de.wikipedia.org/wiki/User:The_Mighty_Kinkle", "http://de.wikipedia.org/wiki/Br%C3%A4unling_(Vogel)", "http://de.wikipedia.org/wiki/Tachyoryctinae");
                if(in_array($url, $this->exclude_url[$this->language_code])) continue;
                
                $rekord['title'] = $rec->langlinks[0]->{"*"};
                $domain_name = self::get_domain_name($url);
                if($html = Functions::lookup_with_cache($url, $this->download_options))
                {
                    $html = str_ireplace('href="//', "href=xxxxxx", $html);
                    $html = str_ireplace('href="/', 'href="http://' . $domain_name . '/', $html);
                    $html = str_ireplace('href=xxxxxx', 'href="//', $html);                    
                    if(substr_count($html, "<div ") != substr_count($html, "</div>")) $rekord['not equal divs'] = true;
                    /*
                    <div id="content" class="mw-body" role="main">
                    <div id="mw-content-text" lang="de" dir="ltr" class="mw-content-ltr"> --- the ending div for this is after the <div id="footer" role="contentinfo">
                    <div id="mw-navigation">
                    <div id="footer" role="contentinfo">
                    */
                    if(preg_match("/<div id=\"mw-content-text\" lang=\"de\" dir=\"ltr\" class=\"mw-content-ltr\">(.*?)<div id=\"mw-navigation\">/ims", $html, $arr))
                    {
                        $rekord['comprehensive_desc'] = self::format_wiki_substr($arr[1]);
                        // $rekord['comprehensive_desc'] = $rekord['title'] . ': my comprehensive report'; //debug
                    }
                    $rekord['sciname']          = self::get_sciname($html);
                    $rekord['ancestry']      = self::get_ancestry($html);
                    $rekord['permalink']      = self::get_permalink($html);
                    $rekord['brief_desc']      = self::get_brief_description($html);
                    $rekord['last_modified'] = self::get_last_modified($html);
                    $rekord['citation']      = self::get_citation($rekord['title'], $rekord['permalink'], $rekord['last_modified']);
                    
                    if(is_numeric(stripos($html, 'summary="Taxobox">')))
                    {
                        if(!@$rekord['sciname']) //needs investigation
                        {
                            echo "\n no sciname:[$html]";
                            print_r($rekord); exit;
                        }
                        else 
                        {
                            // if($url == "http://de.wikipedia.org/wiki/Lactobacillus_paracasei") exit; //debug
                            self::create_archive($rekord);
                        }
                        
                    }
                }
            }// with valid url
        }
    }
    
    private function get_domain_name($url)
    {
        if(preg_match("/http:\/\/(.*?)\//ims", pathinfo($url, PATHINFO_DIRNAME), $arr)) return $arr[1];
    }
    
    private function get_sciname($html)
    {
        if(preg_match_all("/<td class=\"taxo-name\">(.*?)<\/td>/ims", $html, $arr))
        {
            $items = array_reverse($arr[1]);
            foreach($items as $item)
            {
                if($val = trim(strip_tags($item))) return $val;
            }
        }

        // second option e.g. http://de.wikipedia.org/wiki/Piranhas
        if(preg_match("/<td class=\"taxo-bild\" style=\"font-size:smaller;\">(.*?)<\/td>/ims", $html, $arr))
        {
            $sciname = strip_tags($arr[1], "<i>");
            if(preg_match("/<i>(.*?)<\/i>/ims", $sciname, $arr)) return $arr[1];
            elseif(preg_match("/\((.*?)\)/ims", $sciname, $arr))
            {
                if(!is_numeric(stripos($arr[1], "."))) //e.g. (E. a. americanus)
                {
                    if(!(substr_count($arr[1], " ") > 2)) return $arr[1]; //e.g. (Die Länge des weißen Striches entspricht 1 Mikrometer) in http://de.wikipedia.org/wiki/Haptophyta    
                }
            }
            else $backup_sciname =  $sciname;
        }
        return false;
    }

    private function get_ancestry($html)
    {
        $ancestry = array();
        if(preg_match("/<table class=\"toptextcells\"(.*?)<\/table>/ims", $html, $arr))
        {
            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $arr2))
            {
                foreach($arr2[1] as $item)
                {
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $item, $arr3))
                    {
                        $td = $arr3[1];
                        foreach($this->ranks[$this->language_code] as $rank)
                        {
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
    
    private function get_permalink($html)
    {
        // <li id="t-permalink"><a href="/w/index.php?title=Piranhas&amp;oldid=140727080" title="Dauerhafter Link zu dieser Seitenversion">Permanenter Link</a></li>
        if(preg_match("/<li id=\"t-permalink\"><a href=\"(.*?)\"/ims", $html, $arr)) return html_entity_decode($arr[1]);
    }
    
    private function get_brief_description($html)
    {
        if(preg_match("/summary=\"Taxobox\">(.*?)<p><\/p>/ims", $html, $arr)) 
        {
            if(preg_match_all("/<p>(.*?)<\/p>/ims", $arr[1], $arr2)) return self::format_wiki_substr(array_pop($arr2[1])); //get the last <p>xxx</p> 
        }
        elseif(preg_match("/summary=\"Taxobox\">(.*?)<h2>/ims", $html, $arr)) 
        {
            if(preg_match_all("/<p>(.*?)<\/p>/ims", $arr[1], $arr2)) return self::format_wiki_substr(array_pop($arr2[1])); //get the last <p>xxx</p> 
        }        
    }
    
    private function get_last_modified($html)
    {
        if($this->language_code == 'de')
        {
            // <li id="footer-info-lastmod"> Diese Seite wurde zuletzt am 12. Dezember 2013 um 20:57 Uhr geändert.</li>
            if(preg_match("/<li id=\"footer-info-lastmod\"> Diese Seite wurde zuletzt am(.*?)Uhr geändert./ims", $html, $arr)) return trim(str_replace(" um ", ", ", $arr[1]));
        }
    }
    
    private function get_citation($title, $permalink, $last_modified)
    {
        if($this->language_code == 'de')
        {
            return "Seite „" . $title . "“. In: Wikipedia, Die freie Enzyklopädie. Bearbeitungsstand: " . $last_modified . ". URL: " . $permalink . " (Abgerufen: " . date("d. F Y, h:i T") . ")"; 
        }
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
        $t = new \eol_schema\Taxon();
        $t->taxonID                 = md5($rec['permalink']);
        $t->scientificName          = $rec['sciname'];
        $t->order                   = @$rec['ancestry']['order'];
        $t->family                  = @$rec['ancestry']['family'];
        $t->genus                   = @$rec['ancestry']['genus'];
        $t->furtherInformationURL   = $rec['permalink'];

        $ranks = array("order", "family", "genus");
        foreach($ranks as $rank)
        {
            if($t->$rank == $t->scientificName) $t->$rank = ''; //any of the ancestry names cannot be same as the scientificname
        }

        if(!isset($this->taxon_ids[$t->taxonID]))
        {
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
        self::create_media_object($media);

        // Brief Summary
        $media['identifier']             = md5($rec['permalink']."Brief Summary");
        $media['title']                  = $rec['title'] . ': Brief Summary';
        $media['description']            = $rec['brief_desc'];
        $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
        self::create_media_object($media);
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
    
    private function format_wiki_substr($substr)
    {
        return str_replace(array("\n", "\t"), "", Functions::remove_whitespace($substr));
    }

}
?>


