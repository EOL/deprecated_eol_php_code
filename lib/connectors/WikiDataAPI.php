<?php
namespace php_active_record;
// require_once DOC_ROOT . '/vendor/JsonCollectionParser-master/src/Parser.php';
require_library('connectors/WikipediaRegionalAPI');

/* 
https://en.wikipedia.org/wiki/List_of_Wikipedias

commons dump: https://dumps.wikimedia.org/commonswiki/20170320/
postponed: eliagbayani@ELIs-Mac-mini ~: 
wget    http://dumps.wikimedia.org/commonswiki/latest/commonswiki-latest-pages-articles.xml.bz2
wget -c http://dumps.wikimedia.org/commonswiki/latest/commonswiki-latest-pages-articles.xml.bz2


https://dumps.wikimedia.org/commonswiki/20170320/commonswiki-20170320-pages-articles.xml.bz2

wget -c https://dumps.wikimedia.org/commonswiki/20170320/commonswiki-20170320-pages-articles-multistream-index.txt.bz2

used api for commons:
https://commons.wikimedia.org/wiki/Commons:API/MediaWiki
others:
https://tools.wmflabs.org/magnus-toolserver/commonsapi.php
https://commons.wikimedia.org/wiki/Commons:Commons_API
using page id -> https://commons.wikimedia.org/?curid=29447337
*/

class WikiDataAPI
{
    function __construct($folder, $lang, $what = "wikipedia")
    {
        $this->what = $what;
        $this->resource_id = $folder;
        $this->language_code = $lang;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->download_options = array('cache_path' => '/Volumes/Thunderbolt4/eol_cache_wiki_regions/', 'expire_seconds' => false, 'download_wait_time' => 3000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->debug = array();
        
        //start
        // $this->wiki_data_json        = "/Volumes/Thunderbolt4/wikidata/latest-all.json"; //from fresh dump
        // $this->wiki_data_taxa_json   = "/Volumes/Thunderbolt4/wikidata/latest-all-taxon.json"; //used in utility to create an all-taxon dump -> create_all_taxon_dump()
        $this->wiki_data_json           = "/Volumes/Thunderbolt4/wikidata/latest-all-taxon.json"; //used in utility to create an all-taxon dump

        // $this->property['taxon name'] = "P225";
        // $this->property['taxon rank'] = "P105";

        $this->trans['editors']['en'] = "Wikipedia authors and editors";
        $this->trans['editors']['de'] = "Wikipedia Autoren und Herausgeber";
        $this->trans['editors']['es'] = "Autores y editores de Wikipedia";
        
        $this->passed_already = false; //use to create a fake meta.xml
        
        $this->save_all_filenames = false; //use to save all media filenames to text file; normal operation is false; => not being used since a lookup is still needed
    }

    function get_all_taxa()
    {
        if(!@$this->trans['editors'][$this->language_code]) 
        {
            $func = new WikipediaRegionalAPI($this->resource_id, $this->language_code);
            $this->trans['editors'][$this->language_code] = $func->translate_source_target_lang("Wikipedia authors and editors", "en", $this->language_code);
        }
        
        /* self::create_all_taxon_dump(); exit; //a utility, generates overnight */
        
        self::initialize_files();
        self::parse_wiki_data_json();
        self::add_parent_entries();
        $this->archive_builder->finalize(TRUE);

        //start ============================================================= needed adjustments
        if($this->what == "wikipedia")
        {
            unlink(CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_working" . "/media_resource.tab");  //remove generated orig test media_resource.tab
            Functions::file_rename($this->media_extension, CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_working" . "/media_resource.tab");  //rename .eli to .tab

            //mimic the compression in $this->archive_builder->finalize()
            $info = pathinfo(CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_working");
            $temporary_tarball_path = \php_active_record\temp_filepath();
            $final_tarball_path = $info['dirname'] ."/". $info['basename'] .".tar.gz";
            shell_exec("tar -czf $temporary_tarball_path --directory=". $info['dirname'] ."/". $info['basename'] ." .");
            @unlink($final_tarball_path);
            if(copy($temporary_tarball_path, $final_tarball_path))
              unlink($temporary_tarball_path);
        }
        //end =============================================================

        unlink($this->TEMP_FILE_PATH);
    }

    private function initialize_files()
    {
        $this->TEMP_FILE_PATH = temp_filepath();
        if(!($f = Functions::file_open($this->TEMP_FILE_PATH, "w"))) return;
        fclose($f);
        /*
        <field index="0" term="http://purl.org/dc/terms/identifier"/>
        <field index="1" term="http://rs.tdwg.org/dwc/terms/taxonID"/>
        <field index="2" term="http://purl.org/dc/terms/type"/>
        <field index="3" term="http://purl.org/dc/terms/format"/>
        <field index="4" term="http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm"/>
        <field index="5" term="http://purl.org/dc/terms/title"/>
        <field index="6" term="http://purl.org/dc/terms/description"/>
        <field index="7" term="http://rs.tdwg.org/ac/terms/furtherInformationURL"/>
        <field index="8" term="http://purl.org/dc/terms/language"/>
        <field index="9" term="http://ns.adobe.com/xap/1.0/rights/UsageTerms"/>
        <field index="10" term="http://ns.adobe.com/xap/1.0/rights/Owner"/>
        */
        // /*
        if($this->what == "wikipedia")
        {
            $this->media_cols = "identifier,taxonID,type,format,CVterm,title,description,furtherInformationURL,language,UsageTerms,Owner";
            $this->media_cols = explode(",", $this->media_cols);
            $this->media_extension = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_working" . "/media_resource.eli";
            if(!($f = Functions::file_open($this->media_extension, "w"))) return;
            fwrite($f, implode("\t", $this->media_cols)."\n");
            fclose($f);
        }
        // */
    }
    
    private function add_parent_entries()
    {
        echo "\n\nStart add parent entries...\n\n";
        foreach(new FileIterator($this->TEMP_FILE_PATH) as $line_number => $row)
        {
            $arr = json_decode($row, true);
            while(@$arr['parent'])
            {
                //first record
                $rec = array();
                $rec['id']          = $arr['id'];
                $rec['taxon_name']  = $arr['taxon_name'];
                $rec['rank']        = $arr['rank'];
                $rec['parent_id']   = @$arr['parent']['id'];
                self::create_parent_taxon($rec);
                
                $arr = @$arr['parent']; //trigger a loop

                if(!@$arr['parent'])  //if true, then get the last record
                {
                    $rec = array();
                    $rec['id']          = $arr['id'];
                    $rec['taxon_name']  = $arr['taxon_name'];
                    $rec['rank']        = $arr['rank'];
                    $rec['parent_id']   = @$arr['parent']['id'];
                    self::create_parent_taxon($rec);
                }
            }
        }
    }
    
    private function create_parent_taxon($rec)
    {
        if(!@$rec['taxon_name']) return;
        $t = new \eol_schema\Taxon();
        $t->taxonID                 = $rec['id'];
        $t->scientificName          = $rec['taxon_name'];
        $t->taxonRank               = $rec['rank'];
        $t->parentNameUsageID       = @$rec['parent_id'];
        $t->source                  = "https://www.wikidata.org/wiki/".$t->taxonID;
        if(!isset($this->taxon_ids[$t->taxonID]))
        {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }
        // print_r($rec);
        /*
        [id] => Q25833
        [taxon_name] => Eutheria
        [rank] => infraclass
        [parent_id] => Q130942
        */
    }

    private function parse_wiki_data_json()
    {
        $actual = 0;
        $i = 0; $j = 0;
        $k = 0; $m = 4624000; $m = 600000; //only for breakdown when caching
        foreach(new FileIterator($this->wiki_data_json) as $line_number => $row)
        {
            $k++; echo " ".number_format($k)." ";
            /* breakdown when caching:
            $cont = false;
            // if($k >=  1    && $k < $m) $cont = true;           //1 -   600,000
            // if($k >=  $m   && $k < $m*2) $cont = true;   //600,000 - 1,200,000
            // if($k >=  $m*2 && $k < $m*3) $cont = true; //1,200,000 - 1,800,000
            // if($k >=  $m*3 && $k < $m*4) $cont = true; //1,800,000 - 2,400,000
            // if($k >=  $m*4 && $k < $m*5) $cont = true; //2,400,000 - 3,000,000
            
            // if($k >= 668,006 && $k < $m*5) $cont = true; // nl
            // if($k >= 520,538 && $k < $m*5) $cont = true; // sv
            // if($k >= 747,157 && $k < $m*5) $cont = true; // vi

            // if($k >= 1 && $k < 100) $cont = true;   //wikimedia total taxa = 2,208,086

            if(!$cont) continue;
            */

            if(stripos($row, "Q16521") !== false) //string is found -- "taxon"
            {
                /* remove the last char which is "," a comma */
                $row = substr($row,0,strlen($row)-1); //removes last char which is "," a comma
                $arr = json_decode($row);

                /* for debug start ====================== Q4589415 - en with blank taxon name | Q5113 - jap with erroneous desc | ko Q8222313 has invalid parent | Q132634
                $arr = self::get_object('Q6707390');
                $arr = $arr->entities->Q6707390;
                for debug end ======================== */
                
                if(is_object($arr))
                {
                    $rek = array();
                     // /*
                     $rek['taxon_id'] = trim((string) $arr->id);
                     if($rek['taxon'] = self::get_taxon_name($arr)) //old working param is $arr->claims
                     {
                         // /* normal operation
                         if($rek['sitelinks'] = self::get_taxon_sitelinks_by_lang($arr->sitelinks)) //if true then create DwCA for it
                         {
                             // print_r($arr); //debug
                             $i++; 
                             $rek['rank'] = self::get_taxon_rank($arr->claims);
                             $rek['author'] = self::get_authorship($arr->claims);
                             $rek['author_yr'] = self::get_authorship_date($arr->claims);
                             $rek['parent'] = self::get_taxon_parent($arr->claims);

                             $rek['com_gallery'] = self::get_commons_gallery($arr->claims);
                             $rek['com_category'] = self::get_commons_category($arr->claims);
                             
                             echo "\n $this->language_code ".$rek['taxon_id']." - ";
                             if($this->what == "wikipedia") $rek = self::get_other_info($rek); //uncomment in normal operation
                             if($this->what == "wikimedia")
                             {
                                 if($url = @$rek['com_category'])   $rek['obj_category'] = self::get_commons_info($url);
                                 if($url = @$rek['com_gallery'])    $rek['obj_gallery'] = self::get_commons_info($url);
                             }
                             
                             if($rek['taxon_id'])
                             {
                                 $ret = self::create_archive($rek);
                                 if($ret) self::save_ancestry_to_temp($rek['parent']);
                                 
                                 // if(!@$rek['other']['comprehensive_desc']) { print_r($rek); exit("\ninvestigate\n"); }
                                 print_r($rek);
                                 // break;              //debug - process just 1 rec
                                 
                                 $actual++; echo " [$actual] ";
                                 // if($actual >= 5000) break;   //debug - used only on batch of 5000 articles per language
                             }
                         }
                         // print_r($rek);
                         // if($i >= 100) break;   //debug
                         // */
                         
                         /* utility: this is to count how many articles per language
                         if($arr = self::get_taxon_sitelinks($arr->sitelinks))
                         {
                             foreach($arr as $a)
                             {
                                 $str = str_replace("wiki", "", $a->site);
                                 // echo " ".$str;
                                 $this->debug[$str]++;
                             }
                             // if($j > 100) break; //debug
                         }
                         */
                     }
                     else $j++;
                     // */
                }
                else exit("\nnot ok\n");
            }
            else
            {
                $j++;
                echo " -x- ";
            }
        }
        echo "\ntotal taxon wikis = [$i]\n";
        echo "\ntotal non-taxon wikis = [$j]\n";
        print_r($this->debug);
    }

    private function save_ancestry_to_temp($ancestry)
    {
        $id = $ancestry['id'];
        if(!isset($this->saved_ids[$id]))
        {
            $this->saved_ids[$id] = '';
            if(!($f = Functions::file_open($this->TEMP_FILE_PATH, "a"))) return;
            fwrite($f, json_encode($ancestry)."\n");
            fclose($f);
        }
    }
    
    private function create_archive($rec)
    {
        if($this->what == "wikimedia")
        {
            if(!@$rec['obj_gallery'] && !@$rec['obj_category']) return;
        }
        if($this->what == "wikipedia")
        {
            if(!trim(@$rec['other']['comprehensive_desc'])) return;
        }
        
        
        if(!@$rec['taxon']) return;
        $t = new \eol_schema\Taxon();
        $t->taxonID                  = $rec['taxon_id'];
        $t->scientificName           = $rec['taxon'];
        if($t->scientificNameAuthorship = $rec['author'])
        {
            if($year = $rec['author_yr'])
            {
                //+1831-01-01T00:00:00Z
                $year = substr($year,1,4);
                $t->scientificNameAuthorship .= ", $year";
            }
        }
        
        $t->taxonRank                = $rec['rank'];
        $t->parentNameUsageID        = $rec['parent']['id'];
        
        if($val = @$rec['other']['permalink']) $t->source = $val;
        else                                   $t->source = "https://www.wikidata.org/wiki/".$t->taxonID;

        if(!isset($this->taxon_ids[$t->taxonID]))
        {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }

        // if($rec['taxon_id'] == "Q5113" && $this->language_code == "ja") return; //debug force
        // if($rec['taxon_id'] == "Q5113") return; //Aves is problematic...debug force

        //start media objects
        $media = array();
        
        if($description = trim(@$rec['other']['comprehensive_desc']))
        {
            // Comprehensive Description
            $media['identifier']             = md5($rec['taxon_id']."Comprehensive Description");
            $media['title']                  = $rec['other']['title'];
            $media['description']            = $description;
            $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description';
            // below here is same for the next text object
            $media['taxonID']                = $t->taxonID;
            $media['type']                   = "http://purl.org/dc/dcmitype/Text";
            $media['format']                 = "text/html";
            $media['language']               = $this->language_code;
            $media['Owner']                  = $this->trans['editors'][$this->language_code];
            $media['UsageTerms']             = 'http://creativecommons.org/licenses/by-sa/3.0/';
            $media['furtherInformationURL'] = $rec['other']['permalink'];
            self::create_media_object($media);
        }

        /* // Brief Summary - works well for 'de'
        $media['identifier']             = md5($rec['permalink']."Brief Summary");
        $media['title']                  = $rec['title'] . ': Brief Summary';
        $media['description']            = $rec['brief_desc'];
        $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
        if($media['description']) self::create_media_object($media);
        */
        return true;
    }
    
    private function get_commons_info($url)
    {
        $final = array();
        // <a href="/wiki/File:A_hand-book_to_the_primates_(Plate_XL)_(5589462024).jpg"
        // <a href="/wiki/File:Irrawaddy_Dolphin.jpg"
        $options = $this->download_options;
        if($html = Functions::lookup_with_cache($url, $options))
        {
            if(preg_match_all("/<a href=\"\/wiki\/File:(.*?)\"/ims", $html, $arr))
            {
                $files = array_values(array_unique($arr[1]));
                print_r($files); //exit("\n cha111 \n");
                if($this->save_all_filenames)
                {
                    self::save_filenames_2file($files);
                    return;
                }
                
                $limit = 0;
                foreach($files as $file)
                {   // https://commons.wikimedia.org/wiki/File:Eyes_of_gorilla.jpg
                    $rek = array();
                    
                    if($filename = self::has_cache_data($file)) //Eyes_of_gorilla.jpg
                    // if(false)
                    {
                        $rek = self::get_media_metadata_from_json($filename, $file);
                    }
                    else
                    {
                        $rek = self::get_media_metadata_from_api($file);
                    }
                    $rek['source_url']  = "https://commons.wikimedia.org/wiki/File:".$file;
                    $rek['media_url']   = self::get_media_url($file);
                    print_r($rek); exit;
                    if($rek['pageid'])
                    {
                        $final[] = $rek;
                        $limit++;
                    }
                    if($limit >= 35) break; //no. of images to get
                }
                exit("\n cha222 \n");
            }
        }
        return $final;
    }
    
    private function has_cache_data($file)
    {
        if($filename = self::taxon_media($file))
        {
            if(filesize($filename) > 0) return $filename;
        }
        return false;
    }
    
    private function get_media_metadata_from_json($filename, $title)
    {
        $json = file_get_contents($filename);
        $arr = json_decode($json, true);
        // print_r($arr); exit;
        $rek = array();
        $rek['pageid'] = $arr['id'];
        $rek['title'] = str_replace("_", " ", $title);
        $wiki = $arr['revision']['text'];

        // for LicenseShortName
        // == {{int:license-header}} ==
        // {{Flickr-no known copyright restrictions}}
        if(preg_match("/== \{\{int:license-header\}\} ==(.*?)\}\}/ims", $wiki, $a))
        {
            $tmp = trim(str_replace("{", "", $a[1]));
            $rek['LicenseShortName'] = $tmp;
        }
        // for ImageDescription
        if(preg_match("/== \{\{int:filedesc\}\} ==(.*?)\}\}/ims", $wiki, $a))
        {
            echo "\n $a[1] \n";
            if(preg_match_all("/\'\'\'(.*?)<br>/ims", $a[1], $a2))
            {
                $tmp = $a2[1];
                $i = 0;
                foreach($tmp as $t)
                {
                    $t = str_replace("'", "", $t); $tmp[$i] = $t;
                    if(stripos($t, "view book online") !== false) $tmp[$i] = null; //string is found
                    if(stripos($t, "Text Appearing") !== false) $tmp[$i] = null; //string is found
                    if(stripos($t, "Note About Images") !== false) $tmp[$i] = null; //string is found
                    if(strlen($t) < 5) $tmp[$i] = null;
                    $i++;
                }
            }
            $tmp = array_filter($tmp);
            print_r($tmp);
            $i = 0;
            foreach($tmp as $t)
            {
                $tmp[$i] = self::wiki2html($t);
                $i++;
            }
            print_r($tmp);
            $rek['ImageDescription'] = implode("<br>", $tmp);
            // exit;
        }
        // for other metadata
        /*
        |date=1841
        |author=Schlegel, H. (Hermann), 1804-1884
        |source=https://www.flickr.com/photos/internetarchivebookimages/16095238834/
        |permission={{User:Fæ/Flickr API}}
        */
        if(preg_match("/\|date\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['date'] = $a[1];
        if(preg_match("/\|author\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['author'] = $a[1];
        if(preg_match("/\|source\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['source'] = $a[1];
        if(preg_match("/\|permission\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['permission'] = $a[1];
        

        //print_r($arr); 
        // exit("\n $wiki \n");
        return $rek;
    }
    
    private function get_media_metadata_from_api($file)
    {   //https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&iiprop=extmetadata&titles=Image:Gorilla_498.jpg
        $rek = array();
        if($json = Functions::lookup_with_cache("https://commons.wikimedia.org/w/api.php?format=json&action=query&prop=imageinfo&iiprop=extmetadata&titles=Image:".$file, $this->download_options))
        {
            $arr = json_decode($json, true);
            $arr = array_values($arr["query"]["pages"]);
            $arr = $arr[0];
            echo "\nresult: " . count($arr) . "\n";
            // print_r($arr); exit;
            if(!isset($arr['pageid'])) return array();
            $rek['pageid'] = self::format_wiki_substr($arr['pageid']);
            /* better to use just the one below
            if($val = @$arr['imageinfo'][0]['extmetadata']['ObjectName']['value'])  $rek['title'] = self::format_wiki_substr($val);
            else                                                                    $rek['title'] = self::format_wiki_substr($arr['title']);
            */
            $rek['title'] = self::format_wiki_substr($arr['title']);
            $rek['ImageDescription'] = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['ImageDescription']['value']);
            $rek['Artist']           = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['Artist']['value']);
            $rek['LicenseUrl']       = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['LicenseUrl']['value']);
            $rek['LicenseShortName'] = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['LicenseShortName']['value']);
            if($val = @$arr['imageinfo'][0]['extmetadata']['DateTime']['value'])             $rek['date'] = self::format_wiki_substr($val);
            elseif($val = @$arr['imageinfo'][0]['extmetadata']['DateTimeOriginal']['value']) $rek['date'] = self::format_wiki_substr($val);
        }
        return $rek;
    }
    
    private function wiki2html($str)
    {
        if(preg_match_all("/\[(.*?)\]/ims", $str, $a))
        {
            $divided = array();
            foreach($a[1] as $tmp)
            {
                $arr = explode(" ", $tmp);
                $url = $arr[0];
                array_shift($arr);
                $link_text = implode(" ", $arr);
                $divided[] = array("url" => $url, "link_text" => $link_text);
            }
            $i = 0;
            foreach($a[1] as $tmp)
            {
                $str = str_replace("[" . $tmp . "]", "<a href='" . $divided[$i]['url'] . "'>" . $divided[$i]['link_text'] . "</a>", $str);
                $i++;
            }
        }
        return $str;
    }
    
    private function get_media_url($file)
    {   // $file = "DKoehl_Irrawaddi_Dolphin_jumping.jpg";
        // $file = "Lycopodiella_cernua_estróbilos.jpg";
        // $file = "Lycopodiella_cernua_estr%C3%B3bilos.jpg";
        $file = urldecode($file);
        $md5 = md5($file);
        $char1 = substr($md5,0,1);
        $char2 = substr($md5,1,1);
        return "https://upload.wikimedia.org/wikipedia/commons/$char1/$char1$char2/" . str_replace(" ", "_", $file);
    }
    
    private function format_wiki_substr($substr) //https://en.wikipedia.org/wiki/Control_character
    {   
        $substr = Functions::import_decode($substr);
        $substr = Functions::remove_whitespace($substr);
        return str_replace(array("\n", "\t", "\r", chr(9), chr(10), chr(13)), "", $substr);
    }

    private function create_media_object($media)
    {
        // /*
        $row = "";
        $i = 0;
        $total_cols = count($this->media_cols);
        foreach($this->media_cols as $key)
        {
            $i++;
            $row .= $media[$key];
            if($i == $total_cols) $row .= "\n";
            else                  $row .= "\t";
        }
        if(!isset($this->object_ids[$media['identifier']]))
        {
            if(!($f = Functions::file_open($this->media_extension, "a"))) return;
            fwrite($f, $row);
            fclose($f);
        }
        // */

        // /*
        if(!$this->passed_already)
        {
            $this->passed_already = true;
            
            $mr = new \eol_schema\MediaResource();
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
            if(!isset($this->object_ids[$mr->identifier]))
            {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
        // */
        
        /*
        $mr = new \eol_schema\MediaResource();
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
        if(!isset($this->object_ids[$mr->identifier]))
        {
            $this->object_ids[$mr->identifier] = '';
            $this->archive_builder->write_object_to_file($mr);
        }
        */
    }
    
    private function get_other_info($rek)
    {
        $func = new WikipediaRegionalAPI($this->resource_id, $this->language_code);
        
        if($title = $rek['sitelinks']->title)
        {
            // $title = "Dicorynia"; //debug
            $url = "https://" . $this->language_code . ".wikipedia.org/wiki/" . str_replace(" ", "_", $title);
            $domain_name = $func->get_domain_name($url);

            $options = $this->download_options;
            // if($rek['taxon_id'] == "Q5113") $options['expire_seconds'] = true; //debug only force

            if($html = Functions::lookup_with_cache($url, $options))
            {
                if(self::bot_inspired($html))
                {
                    echo("\nbot inspired: [$url]\n");
                    return $rek;
                }
                
                $rek['other'] = array();
                $html = $func->prepare_wiki_for_parsing($html, $domain_name);
                $rek['other']['title'] = $title;
                $rek['other']['comprehensive_desc'] = $func->get_comprehensive_desc($html);
                // $rek['other']['comprehensive_desc'] = "elix elix elicha elicha";  //debug
                $rek['other']['permalink']        = $func->get_permalink($html);
                $rek['other']['last_modified']    = $func->get_last_modified($html);
                $rek['other']['phrase']           = $func->get_wikipedia_phrase($html);
                $rek['other']['citation']         = $func->get_citation($rek['other']['title'], $rek['other']['permalink'], $rek['other']['last_modified'], $rek['other']['phrase']);
            }
        }
        return $rek;
    }
    
    private function get_taxon_name($arr)
    {
        $claims = $arr->claims;
        if($val = @$claims->P225[0]->mainsnak->datavalue->value) return (string) $val;
        elseif(in_array($arr->id, array("Q4589415")))   //special case for a ko & en article
        {
            if($val = @$arr->labels->en->value) return (string) $val;
        }
        
        /* this introduced new probs, thus commented
        elseif($val = @$arr->labels->en->value) return (string) $val;
        else
        {
            // print_r($arr);
            // exit("\nno taxon name, pls investigate...\n");
        }
        */
        return false;
    }

    private function get_authorship($claims)
    {
        if($id = @$claims->P225[0]->qualifiers->P405[0]->datavalue->value->id) return self::lookup_value($id);
        return false;
    }

    private function get_authorship_date($claims)
    {
        if($date = @$claims->P225[0]->qualifiers->P574[0]->datavalue->value->time) return (string) $date;
        return false;
    }

    private function get_taxon_rank($claims)
    {
        if($id = (string) @$claims->P105[0]->mainsnak->datavalue->value->id) return self::lookup_value($id);
        return false;
    }

    private function get_commons_gallery($claims) //https://commons.wikimedia.org/wiki/Gorilla%20gorilla
    {
        if($val = (string) @$claims->P935[0]->mainsnak->datavalue->value) return "https://commons.wikimedia.org/wiki/" . str_replace(" ", "_", $val);
        return false;
    }

    private function get_commons_category($claims) //https://commons.wikimedia.org/wiki/Category:Gorilla%20gorilla
    {
        if($val = (string) @$claims->P373[0]->mainsnak->datavalue->value) return "https://commons.wikimedia.org/wiki/Category:" . str_replace(" ", "_", $val);
        return false;
    }

    private function get_taxon_parent($claims)
    {
        $parent = array();
        if($id = (string) @$claims->P171[0]->mainsnak->datavalue->value->id)
        {
            $id = self::replace_id_if_redirected($id);

            $parent['id'] = $id;
            $parent['name'] = self::lookup_value($id);
            //start get rank
            if($obj = self::get_object($id))
            {
                $parent['taxon_name'] = self::get_taxon_name($obj->entities->$id); //old working param is $obj->entities->$id->claims
                $parent['rank'] = self::get_taxon_rank($obj->entities->$id->claims);
                $parent['parent'] = self::get_taxon_parent($obj->entities->$id->claims);
            }
            return $parent;
        }
        return false;
    }
    
    private function replace_id_if_redirected($id)
    {
        $this->redirects['Q13862468'] = "Q10794768";
        $this->redirects['Q14050218'] = "Q10804328";
        $this->redirects['Q14469766'] = "Q10824551";
        $this->redirects['Q14376190'] = "Q10820737";
        $this->redirects['Q14513318'] = "Q10713968";
        $this->redirects['Q15029351'] = "Q13167464";
        $this->redirects['Q18583887'] = "Q13167388";
        $this->redirects['Q18549914'] = "Q13167487";
        $this->redirects['Q16481559'] = "Q10762052";
        $this->redirects['Q21446808'] = "Q10745346";
        $this->redirects['Q18519941'] = "Q23005859"; //later homonym
        $this->redirects['Q27661141'] = "Q777139";   //later homonym
        $this->redirects['Q7225609']  = "Q28148175"; //later homonym
        $this->redirects['Q18522963'] = "Q10827989"; //redirected
        $this->redirects['Q18591107'] = "Q16986192"; //redirected
        $this->redirects['Q21438944'] = "Q21223073"; //duplicated
        $this->redirects['Q13231238'] = "Q13167447"; //redirected
        $this->redirects['Q26288710'] = "Q24976183"; //redirected
        if($val = @$this->redirects[$id]) return $val;
        return $id;
    }
    
    private function lookup_value($id)
    {
        if($obj = self::get_object($id))
        {
            /* debug only
            if($id == "Q27661141")
            {
                print_r($obj); exit;
            }
            if(!isset($obj->entities->$id->labels->en->value)) //e.g. Q5614965 
            {
                print_r($obj->entities); exit("\npls investigate 01\n");
            }
            */
            if($val = (string) @$obj->entities->$id->labels->en->value) return $val;
        }
    }
    
    private function get_object($id)
    {
        $url = "https://www.wikidata.org/wiki/Special:EntityData/" . $id . ".json";
        if($json = Functions::lookup_with_cache($url, $this->download_options))
        {
            $obj = json_decode($json);
            return $obj;
        }
        return false;
    }

    private function get_taxon_sitelinks($sitelinks)
    {
        if($obj = @$sitelinks) return $obj;
        return false;
    }
    
    private function get_taxon_sitelinks_by_lang($sitelinks)
    {
        $str = $this->language_code."wiki";
        if($obj = @$sitelinks->$str) return $obj;
        return false;
    }

    private function create_all_taxon_dump() // utility to create an all-taxon dump
    {
        if(!($f = Functions::file_open($this->wiki_data_taxa_json, "w"))) return;
        $e = 0; $i = 0; $k = 0;
        foreach(new FileIterator($this->wiki_data_json) as $line_number => $row)
        {
            $k++;
            if(($k % 20000) == 0) echo " $k";
            if(stripos($row, "Q16521") !== false) //string is found -- "taxon"
            {
                $e++;
                fwrite($f, $row."\n");
            }
            else $i++;
        }
        fclose($f);
        echo "\ntaxa  wikis: [$e]\n";
        echo "\nnon-taxa  wikis: [$i]\n";
    }

    private function bot_inspired($html)
    {
        if(stripos($html, "Robot icon.svg") !== false && stripos($html, "Lsjbot") !== false) return true; //string is found
        return false;
    }

    private function save_filenames_2file($files)
    {
        //save to text file
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_filenames_" . date("Y_m_d") . ".txt";
        $WRITE_pageid = fopen($txtfile, "a");
        fwrite($WRITE_pageid, implode("\n", $files) . "\n");
        fclose($WRITE_pageid);
    }
    
    // ============================ start temp file generation ================================================================================================
    function create_temp_files_based_on_wikimedia_filenames()
    {
        /*
        $files = array();
        $files[] = "Abhandlungen_aus_dem_Gebiete_der_Zoologie_und_vergleichenden_Anatomie_(1841)_(16095238834).jpg";
        $files[] = "Abhandlungen_aus_dem_Gebiete_der_Zoologie_und_vergleichenden_Anatomie_(1841)_(16531419109).jpg";
        $files[] = "C%C3%A9tac%C3%A9s_de_l%27Antarctique_(Baleinopt%C3%A8res,_ziphiid%C3%A9s,_delphinid%C3%A9s)_(1913)_(20092715714).jpg";
        $files[] = str_replace(" ", "_", "Two Gambel's Quail (Callipepla gambelii) - Paradise Valley, Arizona, ca 2004.png");
        foreach($files as $file)
        */
        $main_path = "/Volumes/Thunderbolt4/wikimedia_cache/";
        $i = 0;
        foreach(new FileIterator(CONTENT_RESOURCE_LOCAL_PATH."wikimedia_filenames_2017_04_19.txt") as $line_number => $file)
        {
            $md5 = md5($file);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
            if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
            $filename = $main_path . "$cache1/$cache2/$md5.json";
            if(!file_exists($filename))
            {
                echo "\n " . number_format($i) . " creating file: $file";
                if($FILE = Functions::file_open($filename, 'w'))  fclose($FILE);
            }
            $i++; 
            // if($i >= 100) break; //debug
        }
    }

    function fill_in_temp_files_with_wikimedia_dump_data()
    {
        $path = "/Volumes/Thunderbolt4/wikidata/wikimedia/pages-articles.xml.bz2/commonswiki-latest-pages-articles.xml";
        $reader = new \XMLReader();
        $reader->open($path);
        $i = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "page")
            {
                $page_xml = $reader->readOuterXML();
                $t = simplexml_load_string($page_xml, null, LIBXML_NOCDATA);

                $title = $t->title;
                // $title = "File:Two Gambel's Quail (Callipepla gambelii) - Paradise Valley, Arizona, ca 2004.png";
                $title = str_replace("File:", "", $title);
                $title = str_replace(" ", "_", $title);
                if($filename = self::taxon_media($title))
                {
                    if(filesize($filename) == 0)
                    {
                        echo "\n found taxon wikimedia \n";
                        $json = json_encode($t);
                        if($FILE = Functions::file_open($filename, 'w')) // normal
                        {
                            fwrite($FILE, $json);
                            fclose($FILE);
                        }
                        echo("\n[$filename] saved content\n");
                    }
                    else echo("\nalready saved: [$filename]\n");
                }
                else echo "\n negative \n";
                
                /*
                if(substr($title,0,5) == "File:")
                {
                    print_r($t); 
                    $json = json_encode($t);
                    $arr = json_decode($json, true);
                    print_r($arr);
                    exit("\n---\n");
                }
                if($title == "File:Abhandlungen aus dem Gebiete der Zoologie und vergleichenden Anatomie (1841) (16095238834).jpg")
                {
                    print_r($t); exit("\n111\n");
                }
                */
            }
        }
        /*
        <page>
            <title>South Pole</title>
            <ns>0</ns>
            <id>1883</id>
            <revision>
                  <id>209011112</id>
                  <parentid>140212602</parentid>
                  <timestamp>2016-10-06T22:13:52Z</timestamp>
                  <contributor>
                        <username>CommonsDelinker</username>
                        <id>70842</id>
                  </contributor>
                  <comment>Removed Sastrugi.jpg; deleted by [[User:Ronhjones|Ronhjones]] because: [[:c:COM:L|Copyright violation]]: OTRS 2016100610022578 - From Antarctic Photo Library. Image not taken by employee of National Science Foundation. Needs permission from photographer..</comment>
                  <model>wikitext</model>
                  <format>text/x-wiki</format>
                  <text xml:space="preserve">all wiki text...</text>
                  <sha1>6dpwe9r97p716sg3uzcta9mgc5xlvsk</sha1>
            </revision>
        </page>
        */
    }

    private function taxon_media($title)
    {
        $main_path = "/Volumes/Thunderbolt4/wikimedia_cache/";
        $md5 = md5($title);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        if(file_exists($filename)) return $filename;
        else return false;
    }

    function fill_in_temp_files_with_wikimedia_metadata() //just during testing...
    {
        $title = "File:Two Gambel's Quail (Callipepla gambelii) - Paradise Valley, Arizona, ca 2004.png";
        $title = str_replace("File:", "", $title);
        $title = str_replace(" ", "_", $title);
        if(self::taxon_media($title)) echo "\n yes";
        else echo "\n no";
    }
    
    function process_wikimedia_txt_dump() //initial verification of the wikimedia dump file
    {
        $path = "/Volumes/Thunderbolt4/wikidata/wikimedia/commonswiki-20170320-pages-articles-multistream-index.txt";
        $path = "/Volumes/Thunderbolt4/wikidata/wikimedia/pages-articles.xml.bz2/commonswiki-20170320-pages-articles1.xml-p000000001p006457504";
        $path = "/Volumes/Thunderbolt4/wikidata/wikimedia/pages-articles.xml.bz2/commonswiki-20170320-pages-articles2.xml-p006457505p016129764";
        $path = "/Volumes/Thunderbolt4/wikidata/wikimedia/pages-articles.xml.bz2/commonswiki-latest-pages-articles.xml";
        /*
        $i = 0;
        foreach(new FileIterator($path) as $line_number => $row)
        {
            $i++;
            // $arr = json_decode($row);
            echo "\n" . $row;
            // print_r($row); 
            if($i >= 90000) exit("\n-end-\n");
        }
        */
        $reader = new \XMLReader();
        $reader->open($path);
        $i = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "page")
            {
                $page_xml = $reader->readOuterXML();
                $t = simplexml_load_string($page_xml, null, LIBXML_NOCDATA);

                $page_id = $t->id;
                if($page_id == "47821")
                {
                    print_r($t); exit("\nfound 47821\n");
                }
                echo "\n$page_id";
                
                $title = $t->title;
                if(substr($title,0,5) == "File:")
                {
                    print_r($t); 
                    exit("\n$page_xml\n");
                }
                if($title == "File:Abhandlungen aus dem Gebiete der Zoologie und vergleichenden Anatomie (1841) (16095238834).jpg")
                {
                    print_r($t); exit("\n111\n");
                }
                // $i++; if($i%100==0) debug("Parsed taxon $i");
            }
        }
    }
    // ============================ end temp file generation ==================================================================================================

    // private function checkaddslashes($str){
    //     if(strpos(str_replace("\'",""," $str"),"'")!=false)
    //         return addslashes($str);
    //     else
    //         return $str;
    // }
    
    /* works but expensive
    if($html = Functions::lookup_with_cache("https://commons.wikimedia.org/wiki/File:".str_replace(" ", "_", $file), $options))
    {
        //<a href="https://upload.wikimedia.org/wikipedia/commons/6/67/Western_Gorilla_area.png">
        if(preg_match_all("/<a href=\"https:\/\/upload.wikimedia.org(.*?)\"/ims", $html, $arr))
        {
            $files2 = array_values(array_unique($arr[1]));
            $rek['media_url'] = "https://upload.wikimedia.org".$files2[0];
        }
    }
    */
    
    

}
?>