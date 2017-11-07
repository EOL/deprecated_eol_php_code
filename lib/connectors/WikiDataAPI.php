<?php
namespace php_active_record;
// require_once DOC_ROOT . '/vendor/JsonCollectionParser-master/src/Parser.php';
require_library('connectors/WikipediaRegionalAPI');

/* 
https://en.wikipedia.org/wiki/List_of_Wikipedias

commons dump: https://dumps.wikimedia.org/commonswiki/

wget -c https://dumps.wikimedia.org/commonswiki/latest/commonswiki-latest-pages-articles.xml.bz2
wget -c https://dumps.wikimedia.org/wikidatawiki/entities/latest-all.json.gz

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
        
        $this->path['raw_dump']       = "/Volumes/Thunderbolt4/wikidata/latest-all.json";       //from https://dumps.wikimedia.org/wikidatawiki/entities/latest-all.json.gz
        $this->path['wiki_data_json'] = "/Volumes/Thunderbolt4/wikidata/latest-all-taxon.json"; //an all_taxon dump generated from raw [latest-all.json.gz]
        $this->path['commons']        = "/Volumes/Thunderbolt4/wikidata/wikimedia/pages-articles.xml.bz2/commonswiki-latest-pages-articles.xml"; //from http://dumps.wikimedia.org/commonswiki/latest/commonswiki-latest-pages-articles.xml.bz2
        $this->path['wikimedia_cache'] = "/Volumes/Thunderbolt4/wikimedia_cache/";
        

        // $this->property['taxon name'] = "P225";
        // $this->property['taxon rank'] = "P105";

        $this->trans['editors']['en'] = "Wikipedia authors and editors";
        $this->trans['editors']['de'] = "Wikipedia Autoren und Herausgeber";
        $this->trans['editors']['es'] = "Autores y editores de Wikipedia";
        
        $this->passed_already = false; //use to create a fake meta.xml
        
        $this->save_all_filenames = false; //use to save all media filenames to text file; normal operation is false; => not being used since a lookup is still needed
        
        $this->license['public domain']   = "http://creativecommons.org/licenses/publicdomain/";
        $this->license['by']              = "http://creativecommons.org/licenses/by/3.0/";
        $this->license['by-nc']           = "http://creativecommons.org/licenses/by-nc/3.0/";
        $this->license['by-sa']           = "http://creativecommons.org/licenses/by-sa/3.0/";
        $this->license['by-nc-sa']        = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $this->license['no restrictions'] = "No known copyright restrictions";
    }

    function get_all_taxa()
    {
        /* VERY IMPORTANT - everytime we get a fresh new wikidata dump. The raw dump has all categories not just taxa.
        This utility will create an all-taxon dump, which our connector will use.
        self::create_all_taxon_dump(); //a utility that generates an all-taxon dump, generates overnight 
        exit; 
        */
        
        /* testing
        // $arr = self::process_file("Dark_Blue_Tiger_-_tirumala_septentrionis_02614.jpg");
        // $arr = self::process_file("Prairie_Dog_(Cynomys_sp.),_Auchingarrich_Wildlife_Centre_-_geograph.org.uk_-_1246985.jpg");
        // [file in question] => Array
        //     (
        //         [File:] => Aix_sponsa_dis.PNG
        //         [File:] => Aix_sponsa_dis1.PNG
        //         [File:] => 
        //     )
        $arr = self::process_file("Chinese_Honeysuckle_(349237385).jpg");
        print_r($arr);
        exit("\n-Finished testing-\n");
        */
        
        if(!@$this->trans['editors'][$this->language_code]) {
            $func = new WikipediaRegionalAPI($this->resource_id, $this->language_code);
            $this->trans['editors'][$this->language_code] = $func->translate_source_target_lang("Wikipedia authors and editors", "en", $this->language_code);
        }
        
        self::initialize_files();
        self::parse_wiki_data_json();
        self::add_parent_entries(); //not sure if we need it but gives added value to taxonomy
        $this->archive_builder->finalize(TRUE);

        //start ============================================================= needed adjustments
        if($this->what == "wikipedia") {
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
        echo "\n----start debug array\n";
        print_r($this->debug); //exit;
        echo "\n----end debug array\n";
        
        //write to file $this->debug contents
        $f = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH."/wikimedia_debug_".date("Y-m-d H").".txt", "w");
        $index = array_keys($this->debug);
        foreach($index as $i) {
            fwrite($f, "\n$i ---"."\n");
            foreach(array_keys($this->debug[$i]) as $row) fwrite($f, "$row"."\n");
        }
        fclose($f);
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
        if($this->what == "wikipedia") {
            $this->media_cols = "identifier,taxonID,type,format,CVterm,title,description,furtherInformationURL,language,UsageTerms,Owner";
            $this->media_cols = explode(",", $this->media_cols);
            $this->media_extension = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_working" . "/media_resource.eli";
            if(!($f = Functions::file_open($this->media_extension, "w"))) return;
            fwrite($f, implode("\t", $this->media_cols)."\n");
            fclose($f);
        }
    }
    
    private function add_parent_entries()
    {
        echo "\n\nStart add parent entries...\n\n";
        foreach(new FileIterator($this->TEMP_FILE_PATH) as $line_number => $row) {
            $arr = json_decode($row, true);
            while(@$arr['parent']) {
                //first record
                $rec = array();
                $rec['id']          = $arr['id'];
                $rec['taxon_name']  = $arr['taxon_name'];
                $rec['rank']        = $arr['rank'];
                $rec['parent_id']   = @$arr['parent']['id'];
                self::create_parent_taxon($rec);
                $arr = @$arr['parent']; //trigger a loop
                if(!@$arr['parent']) {  //if true, then get the last record
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
        if(!isset($this->taxon_ids[$t->taxonID])) {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }
        /*
        [id] => Q25833
        [taxon_name] => Eutheria
        [rank] => infraclass
        [parent_id] => Q130942
        */
    }

    private function parse_wiki_data_json()
    {
        $exit_now = false; //only used during debug
        $actual = 0;
        $i = 0; $j = 0;
        $k = 0; $m = 4624000; $m = 300000; //only for breakdown when caching
        foreach(new FileIterator($this->path['wiki_data_json']) as $line_number => $row) {
            $k++; 
            if(($k % 100000) == 0) echo " ".number_format($k)." ";
            echo " ".number_format($k)." ";
            /* breakdown when caching:
            $cont = false;
            // if($k >=  1    && $k < $m) $cont = true; done
            // if($k >=  $m   && $k < $m*2) $cont = true; done
            // if($k >=  $m*2 && $k < $m*3) $cont = true; done
            // if($k >=  $m*3 && $k < $m*4) $cont = true;  done
            // if($k >=  $m*4 && $k < $m*5) $cont = true; done
            // if($k >=  $m*5 && $k < $m*6) $cont = true;
            // if($k >=  $m*6 && $k < $m*7) $cont = true; done
            // if($k >=  $m*7 && $k < $m*8) $cont = true;   done
            // if($k >=  2400000 && $k < 3000000) $cont = true; //2,400,000 - 3,000,000 done

            // these 3 have many pages, but just a stub page with under-construction feel
            // if($k >= 1132112 && $k < $m*5) $cont = true; // nl
            // if($k >= 601476 && $k < $m*5) $cont = true; // sv
            // if($k >= 1154430 && $k < $m*5) $cont = true; // vi

            if($k >= 1 && $k < 50000) $cont = true;   //wikimedia total taxa = 2,208,086
            else break;
            
            // if($k >= 1000000) $cont = true;   //wikimedia total taxa = 2,208,086
            
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
                
                if(is_object($arr)) {
                    $rek = array();
                     // /*
                     $rek['taxon_id'] = trim((string) $arr->id);
                     if($rek['taxon'] = self::get_taxon_name($arr)) //old working param is $arr->claims
                     {
                         // /* normal operation ==========================
                         if($rek['sitelinks'] = self::get_taxon_sitelinks_by_lang($arr->sitelinks)) //if true then create DwCA for it
                         {
                             $i++; 
                             $rek['rank'] = self::get_taxon_rank($arr->claims);
                             $rek['author'] = self::get_authorship($arr->claims);
                             $rek['author_yr'] = self::get_authorship_date($arr->claims);
                             $rek['parent'] = self::get_taxon_parent($arr->claims);

                             $rek['com_gallery'] = self::get_commons_gallery($arr->claims);
                             $rek['com_category'] = self::get_commons_category($arr->claims);
                             
                             echo "\n $this->language_code ".$rek['taxon_id']." - ";
                             if($this->what == "wikipedia") $rek = self::get_other_info($rek); //uncomment in normal operation
                             if($this->what == "wikimedia") {
                                 if($url = @$rek['com_category'])   $rek['obj_category'] = self::get_commons_info($url);
                                 if($url = @$rek['com_gallery'])    $rek['obj_gallery'] = self::get_commons_info($url);
                                 
                                 /* eli's debug
                                 if($a = @$rek['obj_category']) {}//print_r($a);
                                 if($b = @$rek['obj_gallery']) {}//print_r($b);
                                 if($a || $b)
                                 {
                                     print_r($rek);
                                     $exit_now = true;
                                     // exit("\nmeron commons\n");
                                 }
                                 */ //eli's debug end
                             }
                             
                             if($rek['taxon_id']) {
                                 $ret = self::create_archive($rek);
                                 if($ret) self::save_ancestry_to_temp($rek['parent']);
                                 
                                 // if(!@$rek['other']['comprehensive_desc']) { print_r($rek); exit("\ninvestigate\n"); }
                                 // print_r($rek);
                                 // break;              //debug - process just 1 rec
                                 
                                 $actual++; echo " [$actual] ";
                                 // if($actual >= 5000) break;   //debug - used only on batch of 5000 articles per language
                             }
                         }
                         // print_r($rek); //exit("\nstop muna\n");
                         // if($i >= 20) break;   //debug
                         // ===============================*/ //end normal operation
                         
                         /* utility: this is to count how many articles per language ==============
                         if($arr = self::get_taxon_sitelinks($arr->sitelinks)) {
                             foreach($arr as $a) {
                                 $str = str_replace("wiki", "", $a->site);
                                 // echo " ".$str;
                                 $this->debug[$str]++;
                             }
                             // if($j > 100) break; //debug
                         }
                         ==========================================================================*/
                     }
                     else $j++;
                     // */
                }
                else exit("\n --not ok-- \n");
                
                // break; //debug get first taxon wiki only
                // if($k > 5000) break; //10000
                // if($exit_now) break;
                
            } //end of taxon wiki
            else $j++; //non-taxon wiki
            // if($exit_now) break;
            
        } //main loop
        echo "\ntotal taxon wikis = [$i]\n";
        echo "\ntotal non-taxon wikis = [$j]\n";
    }

    private function save_ancestry_to_temp($ancestry)
    {
        $id = $ancestry['id'];
        if(!isset($this->saved_ids[$id])) {
            $this->saved_ids[$id] = '';
            if(!($f = Functions::file_open($this->TEMP_FILE_PATH, "a"))) return;
            fwrite($f, json_encode($ancestry)."\n");
            fclose($f);
        }
    }
    
    private function create_archive($rec)
    {
        if($this->what == "wikimedia") {
            if(!@$rec['obj_gallery'] && !@$rec['obj_category']) return;
        }
        if($this->what == "wikipedia") {
            if(!trim(@$rec['other']['comprehensive_desc'])) return;
        }
        
        if(!@$rec['taxon']) return;
        $t = new \eol_schema\Taxon();
        $t->taxonID                  = $rec['taxon_id'];
        $t->scientificName           = $rec['taxon'];
        if($t->scientificNameAuthorship = $rec['author']) {
            if($year = $rec['author_yr']) {
                //+1831-01-01T00:00:00Z
                $year = substr($year,1,4);
                $t->scientificNameAuthorship .= ", $year";
            }
        }
        
        $t->taxonRank                = $rec['rank'];
        $t->parentNameUsageID        = $rec['parent']['id'];
        
        if($val = @$rec['other']['permalink']) $t->source = $val;
        else                                   $t->source = "https://www.wikidata.org/wiki/".$t->taxonID;

        if(!isset($this->taxon_ids[$t->taxonID])) {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }

        // if($rec['taxon_id'] == "Q5113" && $this->language_code == "ja") return; //debug force
        // if($rec['taxon_id'] == "Q5113") return; //Aves is problematic...debug force

        //start media objects
        $media = array();
        
        if($this->what == "wikipedia") {
            if($description = trim(@$rec['other']['comprehensive_desc'])) {
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
                
                // Brief Summary
                $media['identifier']             = md5($rec['taxon_id']."Brief Summary");
                $media['title']                  = $rec['other']['title'] . ': Brief Summary';
                $media['description']            = $rec['other']['brief_summary'];
                $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
                if($media['description']) self::create_media_object($media);
            }
        }
        
        if($this->what == "wikimedia") {
            if($commons = @$rec['obj_gallery'])     self::create_commons_objects($commons, $t);
            if($commons = @$rec['obj_category'])    self::create_commons_objects($commons, $t);
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
    
    private function format_license($license, $LicenseShortName="")
    {
        $license          = self::clean_html($license);
        $LicenseShortName = self::clean_html($LicenseShortName);
        //regular EOL licenses
        if(stripos($license, "creativecommons.org/licenses/publicdomain/") !== false)   return $this->license['public domain'];
        if(stripos($license, "creativecommons.org/licenses/by/") !== false)             return $this->license['by'];
        if(stripos($license, "creativecommons.org/licenses/by-nc/") !== false)          return $this->license['by-nc'];
        if(stripos($license, "creativecommons.org/licenses/by-sa/") !== false)          return $this->license['by-sa'];
        if(stripos($license, "creativecommons.org/licenses/by-nc-sa/") !== false)       return $this->license['by-nc-sa'];
        
        if(stripos($license, "gpl") !== false) {
            $this->debug['gpl count']++;
            return "invalid";
        }

        //others...
        if($license == "http://creativecommons.org/licenses/by-sa")          return $this->license['by-sa']; //exact match
        if(stripos($license, "creativecommons.org/publicdomain/") !== false) return $this->license['public domain'];
        if(stripos($license, "creativecommons.org/licenses/sa/") !== false)  return $this->license['by-sa']; //[http://creativecommons.org/licenses/sa/1.0/]
        if($license == "http://creativecommons.org/licenses/by")             return $this->license['by']; //exact match
        if($license == "https://www.flickr.com/commons/usage/")              return $this->license['public domain']; //exact match
        if(urldecode($license) == "http://biodivlib.wikispaces.com/Permissions#Content provided under Due Diligence") return $this->license['no restrictions']; //exact match
        if($license == "http://wiki.data.gouv.fr/wiki/Licence_Ouverte_/_Open_Licence") return $this->license['public domain']; //exact match

        //should be invalid per Jen:
        if(stripos($license, "creativecommons.org/licenses/by-nc-nd/") !== false) return "invalid";
        if(stripos($license, "commons.wikimedia.org/wiki/File:") !== false) return "invalid";
        $proven_invalid_licenseurl = array("http://www.gnu.org/copyleft/fdl.html", "http://www.gnu.org/licenses/old-licenses/fdl-1.2.html", "http://www.gnu.org/licenses/gpl.html",
        "www.gnu.org/licenses/fdl-1.3.html", "http://artlibre.org/licence/lal/en", "http://www.gnu.org/licenses/lgpl.html");
        if(in_array($license, $proven_invalid_licenseurl)) return "invalid";
        
        // added Oct 16, 2017
        if(stripos($license, "nationalarchives.gov.uk/doc/open-government-licence") !== false) return "invalid"; //"http://www.nationalarchives.gov.uk/doc/open-government-licence/version/3"

        //blank license
        if(!$license) {
            if(in_array($LicenseShortName, array("Public domain", "cc0"))) return $this->license['public domain'];

            //multiple shortnames separated by "|"
            $shortnames = explode("|", strtolower($LicenseShortName)); //"self|Cc-zero"
            foreach($shortnames as $shortname) {
                if(in_array($shortname, array("cc-zero", "cc0", "cc-0")))    return $this->license['public domain'];
                if(substr($shortname,0,3) == "pd-")                          return $this->license['public domain']; //"PD-self" "PD-author" "pd-???" etc.
                if(stripos($shortname, "bild-pd") !== false)                 return $this->license['public domain'];
                if($shortname == "attribution")                              return $this->license['by'];
                if(substr($shortname,0,14) == strtolower("public domain "))  return $this->license['public domain']; // e.g. "Public Domain Mark"
                if(substr($shortname,0,3) == strtolower("pd/"))              return $this->license['public domain']; // e.g. "Pd/1923|1982"
                if($shortname == strtolower("FlickrVerifiedByUploadWizard")) return $this->license['by'];

                if(substr($shortname,0,6) == "cc-by-")                          return $this->license['by'];
                if(substr($shortname,0,9) == "cc-by-nc-")                       return $this->license['by-nc'];
                if(substr($shortname,0,9) == "cc-by-sa-")                       return $this->license['by-sa'];
                if(substr($shortname,0,12) == "cc-by-nc-sa-")                   return $this->license['by-nc-sa'];
                if(stripos($shortname, "self|own-pd") !== false)                return $this->license['public domain'];
                if(stripos($shortname, "no known copyright restriction") !== false) return $this->license['no restrictions'];
                if(stripos($shortname, "BHL-no known restriction") !== false)       return $this->license['no restrictions'];
            }
            
            //should be invalid per Jen
            if(!$LicenseShortName) return "invalid";
            if(stripos($LicenseShortName, "Custom license marker") !== false) return "invalid";
            if(stripos($LicenseShortName, "ExtractedFromNSRW") !== false) return "invalid";
            if(stripos($LicenseShortName, "copyright protection") !== false) return "invalid";
            if(stripos($LicenseShortName, "Copyrighted") !== false) return "invalid";
            if(stripos($LicenseShortName, "FOLP|") !== false) return "invalid";
            if(stripos($LicenseShortName, "GFDL") !== false) return "invalid";
            if(stripos($LicenseShortName, "self|") !== false) return "invalid";
            if(stripos($LicenseShortName, "User:Flickr") !== false) return "invalid";
            if(stripos($LicenseShortName, "User:Ksd5") !== false) return "invalid";
            if(stripos($LicenseShortName, " Monet ") !== false) return "invalid";
            if(stripos($LicenseShortName, "Bild-") !== false) return "invalid";
            if(stripos($LicenseShortName, "Pixabay|") !== false) return "invalid";
            if(stripos($LicenseShortName, "illustration of the Saxaul Sparrow") !== false) return "invalid";
            $invalid_exact = array("BSD", "FAL", "Faroe stamps", "Fotothek-License", "FWS Image", "GPL", "NARA-cooperation", "NAUMANN", "NPS", "Parasite", "unsplash", "WikiAfrica/TNA", "јв-ја");
            foreach($invalid_exact as $exact) {
                if($exact == $LicenseShortName) return "invalid";
            }
            // [Information|Description=en|1=An illustration of the Saxaul Sparrow (''Passer ammondendri'', called the "Turkestan Sparrow" in the book the illustration was published in)]
            
            //added Oct 16, 2017
            if(stripos($LicenseShortName, "Permission= publiek domein") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, " PD-old") !== false) return $this->license['public domain']; //"# PD-old"
            if(stripos($LicenseShortName, " PD-US") !== false) return $this->license['public domain']; //"<!-- PD-US"
            if(stripos($LicenseShortName, "Template:PD-") !== false) return $this->license['public domain']; //    [Template:PD-Australia] => 
            if(stripos($LicenseShortName, "Brooklyn_Museum-no_known_restriction") !== false) return $this->license['no restrictions']; //"Brooklyn_Museum-no_known_restrictions"
            if(stripos($LicenseShortName, "CDC-PHIL|") !== false) return $this->license['public domain']; //"CDC-PHIL|id=2741"
            if(stripos($LicenseShortName, "Massel_tow_Credit") !== false) return "invalid"; //"Template:Massel_tow_Credit"
            if(stripos($LicenseShortName, "Blacknclick") !== false) return "invalid"; //[User:Blacknclick/Permission]
            if($LicenseShortName == "FWS") return $this->license['public domain']; //exact match
            if($LicenseShortName == "FCO") return "invalid"; //exact match --- invalid coz OGL something...
            if(stripos($LicenseShortName, "OGL|") !== false) return "invalid"; //[OGL|1=Photo: MoD/MOD] --- invalid coz OGL
            if($LicenseShortName == "OGL") return "invalid"; //exact match
            if(stripos($LicenseShortName, "KOGL-") !== false) return "invalid"; //[KOGL-type1]
            if($LicenseShortName == "PAOC") return "invalid"; //exact match
            if($LicenseShortName == "LGPL") return "invalid"; //exact match
            if($LicenseShortName == "LarsenCopyright") return "invalid"; //exact match
            if($LicenseShortName == "Attribution Entomart") return "invalid"; //exact match
            if(stripos($LicenseShortName, "CC-BY-2.0 stated") !== false) return $this->license['by']; //[(photo: CC-BY-2.0 stated)PD-US] => 
            if($LicenseShortName == "Flickr-Brooklyn-Museum-image") return $this->license['by-sa']; //exact match
            if(stripos($LicenseShortName, "license=GPL") !== false) return "invalid"; //[Free screenshot|license=GPL] => 
            if(stripos($LicenseShortName, "Jim Deacon") !== false) return "invalid"; //[=From the website of the author:"IMPORTANT: COPYRIGHT WAIVERAll of the author's images are shown as [© Jim Deacon]. They can be used freely, for any purpose, without restriction.Please ACKNOWLEDGE THE SOURCE AS: Courtesy of Jim Deacon, The University of Edinburg" http://helios.bto.ed.ac.uk/bto/FungalBiology/index.htm#top== int:license-header] => 
            if($LicenseShortName == "NOAA") return $this->license['public domain']; //exact match
            if($LicenseShortName == "anonymous-EU") return $this->license['public domain']; //exact match
            if($LicenseShortName == "AerialPhotograph-mlitJP") return "invalid"; //exact match
            if(stripos($LicenseShortName, "flickrreview|Leoboudv|") !== false) return $this->license['by-sa']; //[flickrreview|Leoboudv|2014-10-26] => 
            if(stripos($LicenseShortName, "authored by [[User:Arp|Arp]]") !== false) return $this->license['by']; //[This image is authored by [[User:Arp|Arp]]. It was uploaded to waarneming.nl and later copied to commons at a time that waarneming.nl did not yet properly support the only ''really'' free and unhampered license (CC0 Public Domain dedication) preferred by the author, so it was originally uploaded (here) as CC-BY, but it's '''not''' limited in it's use for remixing by that hampered license scheme. It is in fact available as: cc0] => 
            if(stripos($LicenseShortName, "user:Anonymous101") !== false) return $this->license['public domain']; //[user:Anonymous101/template] => 
            if($LicenseShortName == "Dead link") return "invalid"; //exact match
            if(stripos($LicenseShortName, "Hans is short for Johan") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "user=Ww2censor") !== false) return $this->license['public domain']; //[LicenseReview|site=http://biodiversitylibrary.org/page/43064802#page/440/mode/1up|user=Ww2censor|date=2015-09-04] => 
            if($LicenseShortName == "insignia") return "invalid"; //exact match
            if(stripos($LicenseShortName, "GNU|") !== false) return "invalid"; //[GNU|month=December|day=2|year=2008|migration=review] => 
            if(stripos($LicenseShortName, "User:Fir0002") !== false) return "invalid"; //[User:Fir0002/20D|migration=relicense] => 
            if(stripos($LicenseShortName, "Flickrreview|Lewis Hulbert") !== false) return "invalid"; //[Flickrreview|Lewis Hulbert|2014-10-25] => 

            //added Oct 17
            if(stripos($LicenseShortName, "public domain=") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "PD-old") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "PD-self") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "a CC-0") !== false) return $this->license['by'];
            if(stripos($LicenseShortName, "PD-user") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "in the public domain") !== false) return $this->license['public domain'];
            if($LicenseShortName == "Flickr-State-Library-NSW-image") return $this->license['no restrictions']; //exact match
            if($LicenseShortName == "WikiAfrica/SIA") return $this->license['no restrictions']; //exact match
            if(stripos($LicenseShortName, "No license since|") !== false) return "invalid";
            if($LicenseShortName == "East German Post") return "invalid"; //exact match
            if($LicenseShortName == "Kopimi") return "invalid"; //exact match
            if(stripos($LicenseShortName, "TARS631") !== false) return "invalid"; //TARS631 at Tramwayforum.at
            if($LicenseShortName == "Business journal") return "invalid"; //exact match
            if($LicenseShortName == "<!-- !-") return "invalid"; //exact match
            if($LicenseShortName == "== int:filedesc") return "invalid"; //exact match
            if(substr($LicenseShortName,0,12) == "SLNSW-image|") return $this->license['public domain'];
            
            //added Oct 18
            $valid_pd = array("USDA", "USFWS", "USGS", "Anonymous-EU", "DEA");
            if(in_array($LicenseShortName, $valid_pd)) return $this->license['public domain'];
            if(stripos($LicenseShortName, "Malayalam loves Wikimedia") !== false) return "invalid"; //Malayalam loves Wikimedia event|year=2011|month=April
            if(stripos($LicenseShortName, "Images by Rob Lavinsky") !== false) return $this->license['by-sa']; //Images by Rob Lavinsky
            if($LicenseShortName == "AndréWadman") return "invalid"; //exact match
            if(stripos($LicenseShortName, "user=INeverCry") !== false) return "invalid";
            if(stripos($LicenseShortName, "User:Ram-Man") !== false) return "invalid";
            if(stripos($LicenseShortName, "User:Sidpatil") !== false) return "invalid";

            // added Oct 19
            if(stripos($LicenseShortName, "ZooKeys-License") !== false) return $this->license['by'];
            if(stripos($LicenseShortName, "Flickr-change-of-license") !== false) return $this->license['by'];
            if(stripos($LicenseShortName, "-cc-by-") !== false) return $this->license['by']; //ifb-cc-by-2.5|Vesselina Lazarova|http://www.imagesfrombulgaria.com/v/bulgarian-food/Parjena_Caca.JPG.html
            if(stripos($LicenseShortName, "cc-by-sa") !== false) return $this->license['by-sa'];
            if(stripos($LicenseShortName, "Geograph|") !== false) return $this->license['by-sa']; //Geograph|691836|Trish Steel
            if(stripos($LicenseShortName, " cc-by-sa") !== false) return $this->license['by-sa']; //Thomas Pruß cc-by-sa
            if(stripos($LicenseShortName, "WikiAfrica") !== false) return $this->license['by-sa']; //WikiAfrica/Ton Rulkens|2012-10-07
            if(stripos($LicenseShortName, "Wiki Loves Earth") !== false) return $this->license['by-sa']; //Wiki Loves Earth 2014|cat
            if(stripos($LicenseShortName, "Walters Art Museum") !== false) return $this->license['by-sa']; //Walters Art Museum license|type=2D
            if($LicenseShortName == "IUCN map permission") return $this->license['public domain']; //exact match
            if($LicenseShortName == "Justphotos.ru") return $this->license['by-sa']; //exact match
            if($LicenseShortName == "MAV-FMVZ USP-license") return $this->license['by-sa']; //exact match
            if($LicenseShortName == "cc-world66") return $this->license['by-sa']; //exact match
            if($LicenseShortName == "Cc-sa") return $this->license['by-sa']; //exact match
            
            //Oct 25
            if($LicenseShortName == "YouTube CC-BY") return $this->license['by']; //exact match
            $arr = array("cc-a-", "cc-by-"); //findme exists (case insensitive) anywhere in string and followed by digit OR space
            foreach($arr as $findme) {
                $findme = preg_quote($findme, '/');
                if(preg_match("/".$findme."[0-9| ]/ims", $LicenseShortName, $arr)) {
                    return $this->license['by'];
                }
            }
            $findme = "cc-sa-";
            $findme = preg_quote($findme, '/');
            if(preg_match("/".$findme."[0-9| ]/ims", $LicenseShortName, $arr)) { //findme exists (case insensitive) anywhere in string and followed by digit OR space
                return $this->license['by-sa'];
            }
            
            // [public domain] exact
            $arr = array("cc-pd", "pdphoto.org", "Folger Shakespeare Library partnership");
            foreach($arr as $p) {
                if(strtolower($LicenseShortName) == $p) return $this->license['public domain'];
            }
            
            //['by'] stripos
            $arr = array("CC BY ", "CC-BY ", " CC-BY|", "CC-BY ", "CC-Layout", "picasareview", "AntWeb permission");
            foreach($arr as $p) {
                if(stripos($LicenseShortName, $p) !== false) return $this->license['by'];
            }
            //[by-sa] stripos
            $arr = array("Nationaal Archief", "Malayalam loves Wikipedia event");
            foreach($arr as $p) {
                if(stripos($LicenseShortName, $p) !== false) return $this->license['by-sa'];
            }

            //[by] exact match
            $arr = array("Premier.gov.ru", "Akkasemosalman");
            foreach($arr as $p) {
                if($LicenseShortName == $p) return $this->license['by']; //exact match
            }

            //[by-sa] exact match
            $arr = array("TamilWiki Media Contest", "RCE-license", "Wikimedia trademark", "gardenology");
            foreach($arr as $p) {
                if($LicenseShortName == $p) return $this->license['by-sa']; //exact match
            }

            /* WILL REMAIN INVALID: as of Oct 30
            [blank_license] => Array
                (
            [MLW3‬] => 
            [] => 
            [dvdm-h6|migration=relicense] => 
            [BMC] => 
            [] => 
            [Zachi Evenor] => 
            [Team|event=Wikipedia Takes Waroona|team=Team Flower|id=19] => 
            [Wuzur] => 
            [<br/>(original text|nobold=1|1=Klettenlabkraut in Weizen] => 
            [Andes] => 
            [Assessments|enwiki=1|enwiki-nom=Bicolored Antbird] => 
            [Youtube|Junichi Kubota] => 
            [Personality rights] => 
            [personality rights] => 
            [NO Facebook Youtube license] => 
            [spomenikSVN|7914] => 
            [Location|36|2|59.1|N|139|9|1.8|E|type:landmark_region:JP-29_scale:2000] => 
            [s*derivative work: [[User:B kimmel|B kimmel]] ([[User talk:B kimmel|<span class="signature-talk">talk</span>]])|Permission=|other_versions=] => 
            [Flickreview|Yuval Y|20:49, 16 June 2011 (UTC)] => 
            [Tasnim] => 
            [OTRS|2008072210012641] => 
            [IBC] => 
            [QualityImage] => 
            [youtube] => 
            [MUSE|OTRS=yes] => 
            [DYKfile|28 December|2006|type=image] => 
            [Bilderwerkstatt|editor=[[:de:Benutzer:Denis Barthel|Denis Barthel]]|orig=Yucca_recurvifolia_fh_1183.24_ALA_AAA.jpg|changes=Perspektive, Ausschnitt, kleinere Edits] => 
            [OTRS|2012011510006576] => 
            [Location dec|46.122186|7.071841|source:Flickr] => 
            [Beeld en Geluid Wiki] => 
            [[[:en:Category:Frog images]]|Source=Transferred from|en.wikipedia] => 
            [Bilderwerkstatt|editor=[[:de:Benutzer:Saman|Saman]]|orig=|changes=Etwas Staub entfernt, Kontrast und Tonwertkorrektur verändert] => 
            [retouched|cropped] => 
            [RetouchedPicture|cropped ''Sciurus spadiceus'' (frame) into a portrait|editor=Jacek555|orig=Sciurus spadiceus (frame).jpg] => 
            [piqs|101897|babychen] => 
            [personality] => 
            [RetouchedPicture|Created GIF animation from sequence of images] => 
            [!-] => 
            [] => 
            [Youtube|channelxxxvol1] => 
            [Picswiss|migration=relicense] => 
            [[[Category:Megalops atlanticus]]] => 
            [Volganet.ru] => 
            [@|link=http://www.opencage.info/pics.e/large_8238.asp|txt=opencage-] => 
            ["] => 
            [RetouchedPicture|Screenshot for distribution map|editor=Obsidian Soul|orig=Australia Victoria location map highways.svg] => 
            [|Source=transferred from|en.wikipedia|Syp|CommonsHelper] => 
            [Folger Shakespeare Library partnership] => 
            [DYKfile|25 March|2008|type=image] => 
                )
            */
            
            //seemingly calphotos images:
            $arr = array("Vladlen Henríquez permission", "Mehregan Ebrahimi permission", "Václav Gvoždík permission", "Diogo B. Provete permission", "Franco Andreone permission", 
            "Josiah H. Townsend permission", "Pierre Fidenci permission", "Alessandro Catenazzi permission", "Stanley Trauth permission", 
            "Raquel Rocha Santos permission", "Mauricio Rivera Correa permission", "LarsCurfsCCSA3.0", "civertan license");
            if(in_array($LicenseShortName, $arr)) return $this->license['by-sa'];
            
            // for public domain - stripos
            $pd = array();
            $pd[] = "PD-US";
            $pd[] = "PD-NASA";
            $pd[] = "RatEatingSunflowerseads.jpg";
            $pd[] = "under public domain term";
            $pd[] = "From U.S. Fish and Wildlife";
            $pd[] = "Koninklijke Bibliotheek";
            $pd[] = "Latvian coins";
            $pd[] = "Russian museum photo";
            $pd[] = "USPresidentialTransition";
            foreach($pd as $p) {
                if(stripos($LicenseShortName, $p) !== false) return $this->license['public domain'];
            }

            // for invalid - stripos
            $inv = array();
            $inv[] = "editor=Kilom691";
            $inv[] = "LicenseReview|";
            $inv[] = "Frank FrägerGPL";
            $inv[] = "GPL|";
            $inv[] = "Remove this line and insert a license";
            $inv[] = "boilerplate metadata";
            $inv[] = "by-nc-nd";
            $inv[] = "PermissionOTRS";
            $inv[] = "You may choose one of the following licenses";
            $inv[] = "Mindaugas Urbonas";
            $inv[] = "Warsaw_ZOO_-_Bovidae_young";
            $inv[] = "plos";
            foreach($inv as $p) {
                if(stripos($LicenseShortName, $p) !== false) return "invalid";
            }

            //last resorts...
            if(stripos($LicenseShortName, "Information|Description") !== false) return "invalid";
            if(stripos($LicenseShortName, "Information |Description") !== false) return "invalid";
            if(stripos($LicenseShortName, "Information| Desc") !== false) return "invalid";
            if(stripos($LicenseShortName, "flickrreview|") !== false) return "invalid";
            if(stripos($LicenseShortName, "ImageNote|") !== false) return "invalid";
            if(stripos($LicenseShortName, "Check categories|") !== false) return "invalid";
            if(stripos($LicenseShortName, "LOC-image|") !== false) return "invalid";
            if(stripos($LicenseShortName, "gebruiker:Jürgen") !== false) return "invalid";
            
            // for invalid - exact match
            $arr = "Youtube|TimeScience,Imagicity,MaleneThyssenCredit,Fdrange,Arne and Bent Larsen license,Korea.net,Atelier graphique,KIT-license,Open Beelden,MUSE permission,volganet.ru,NoCoins,Stan Shebs photo,self,Multi-license,Link,WTFPL-1,En|A person kneeling next to a seal.,self2|FAL|,Fifty Birds,Laboratorio grafico,== Original upload log,Norwegian coat of arms,User:Arp/License,User:Erin Silversmith/Licence,trademark,benjamint5D,custom,Lang,User:Arjun01/I,Apache|Google,easy-border,LA2-Blitz,Autotranslate|1=1|,Frianvändning,Self,Location|57|47|35|N|152|23|39|W,OGL2,User:Pudding4brains/License,ScottForesman,FoP-Hungary,License,<!-- Ambox";
            $arr = explode(",", $arr);
            foreach($arr as $a) {
                if($LicenseShortName == $a) return "invalid"; //exact match
            }

            /*
            User:Chell Hill/CHillPix
            User:Beria/License
            User:Kadellar/credit
            ...and many many more...
            */
            if(substr(strtolower($LicenseShortName),0,5) == "user:") return "invalid"; //starts with "User:"


            $this->debug['blank_license'][$LicenseShortName] = ''; //utility debug - important
            /* finally if LicenseShortName is still undefined it will be considered 'invalid' */
            return "invalid";
        }
        
        return $license;
    }
    private function valid_license_YN($license)
    {
        $valid = array($this->license['public domain'], $this->license['by'], $this->license['by-nc'], $this->license['by-sa'], $this->license['by-nc-sa'], $this->license['no restrictions']);
        if(in_array($license, $valid)) return true;
        else                           return false;
    }
    private function create_commons_objects($commons, $t)
    {
        foreach($commons as $com) {
            $formatted_license = self::format_license(@$com['LicenseUrl'], @$com['LicenseShortName']);
            if(!self::valid_license_YN($formatted_license)) $this->debug['invalid_LicenseUrl'][$formatted_license] = '';
            else
            {
                /*
                [pageid] => 56279236
                [timestamp] => 2017-03-23T23:20:37Z
                [ImageDescription] => Summary <table cellpadding="4"> <tr> <td lang="en">DescriptionAPI</td> <td> English: Simplified cladogram showing that the whales are paraphyletic with respect to the dolphins and porpoises. The clade Cetacea includes all these animals. </td> </tr> <tr> <td lang="en">Date</td> <td lang="en">14 February 2017</td> </tr> <tr> <td lang="en">Source</td> <td>This file was derived from <a href="https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.png" title="File:Whales are Paraphyletic.png">Whales are Paraphyletic.png</a>: <a href="https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.png" ></a><br /></td> </tr> <tr> <td lang="en">Author</td> <td> Original: <a href="https://commons.wikimedia.org/wiki/User:Chiswick_Chap" title="User:Chiswick Chap">Chiswick Chap</a> Vectorisation: <a href="https://commons.wikimedia.org/wiki/User:CheChe" title="User:CheChe">CheChe</a> </td> </tr> </table> <br /> <table > <tr> <td></td> <td>This is a <i><a href="https://en.wikipedia.org/wiki/Image_editing" title="w:Image editing">retouched picture</a></i>, which means that it has been digitally altered from its original version. The original can be viewed here: <a href="https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.png" title="File:Whales are Paraphyletic.png">Whales are Paraphyletic.png</a>. Modifications made by <a href="https://commons.wikimedia.org/wiki/User:CheChe" title="User:CheChe">CheChe</a>. </td> </tr> </table> Licensing <table cellspacing="8" cellpadding="0" > <tr> <td> <table lang="en"> <tr> <td rowspan="3"><br /> </td> <td lang="en">This file is licensed under the <a href="https://en.wikipedia.org/wiki/en:Creative_Commons" title="w:en:Creative Commons">Creative Commons</a> <a rel="nofollow" href="http://creativecommons.org/licenses/by-sa/4.0/deed.en">Attribution-Share Alike 4.0 International</a> license.</td> <td rowspan="3"></td> </tr> <tr> <td></td> </tr> <tr lang="en"> <td> http://creativecommons.org/licenses/by-sa/4.0 CC BY-SA 4.0 Creative Commons Attribution-Share Alike 4.0 truetrue </td> </tr> </table> </td> </tr> </table>
                [LicenseShortName] => self|cc-by-sa-4.0
                [LicenseUrl] => http://creativecommons.org/licenses/by-sa/4.0/deed.en
                [title] => Whales are Paraphyletic.svg
                [other] => Array (
                        [date] => 2017-02-14
                        [author] => *Original: [[User:Chiswick Chap|Chiswick Chap]]
                        [source] => {{derived from|Whales are Paraphyletic.png|display=50}}
                        [permission] => 
                    )
                [date] => 2017-02-14
                [Artist] => Array (
                        [0] => Array (
                                [name] => Chiswick Chap
                                [homepage] => https://commons.wikimedia.org/wiki/User:Chiswick_Chap
                            )
                    )
                [fromx] => dump
                [source_url] => https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.svg
                [media_url] => https://upload.wikimedia.org/wikipedia/commons/3/30/Whales_are_Paraphyletic.svg
                */

                // /*
                $media = array();
                $media['identifier']             = $com['pageid'];
                $media['title']                  = $com['title'];
                $media['description']            = $com['ImageDescription'];
                // $media['CVterm']                 = ''; not applicable - EOL subject
                // below here is same for the next text object
                $media['taxonID']                = $t->taxonID;
                $media['format']                 = Functions::get_mimetype($com['media_url']);
                
                // if($com['media_url'] == "https://upload.wikimedia.org/wikipedia/commons/0/07/Opilion_stalking_lavender_sunset_September.jpeg")
                // {
                //     print_r($com); exit;
                // }
                
                if(!$media['format']) {
                    $this->debug['undefined media ext. excluded'][pathinfo($com['media_url'], PATHINFO_EXTENSION)] = '';
                    continue;
                }
                $media['type']                   = Functions::get_datatype_given_mimetype($media['format']);
                
                // if(!$media['type']) $this->debug['undefined DataType 1'][@$media['format']] = '';
                // if(!$media['type']) $this->debug['undefined DataType 2'][@$com['media_url']] = '';
                
                $media['language']               = $this->language_code;
                $media['Owner']                  = '';
                $media['UsageTerms']             = $formatted_license; //$com['LicenseUrl']; //license
                $media['furtherInformationURL']  = $com['source_url'];
                $media['accessURI']              = $com['media_url'];
                
                // print_r($com);
                $role = Functions::get_role_given_datatype($media['type']);
                if($agent_ids = self::gen_agent_ids($com['Artist'], $role)) $media['agentID'] = implode("; ", $agent_ids);

                if(!@$media['agentID']) {
                    echo "\n-------start investigate--------Undefined index: agentID---\n";
                    print_r($com);
                    print_r($media);
                    $this->debug['file in question'][pathinfo($media['furtherInformationURL'], PATHINFO_BASENAME)] = '';
                    // exit("\nUndefined index: agentID --------------------\n");
                }

                $media = self::last_quality_check($media); //removes /n and /t inside values. May revisit this as it may not be the sol'n for 2 rows with wrong no. of columns.
                
                $mr = new \eol_schema\MediaResource(); //for Wikimedia objects only
                $mr->taxonID                = $media['taxonID'];
                $mr->identifier             = $media['identifier'];
                $mr->type                   = $media['type'];
                $mr->format                 = $media['format'];
                $mr->language               = $media['language'];
                $mr->UsageTerms             = $media['UsageTerms'];
                // $mr->CVterm                 = $media['CVterm'];
                $mr->description            = $media['description'];
                /* debug only
                echo "\n=========================\n";
                echo "[".$mr->description."]";
                echo "\n=========================\n";
                */
                $mr->accessURI              = $media["accessURI"];
                $mr->furtherInformationURL  = $media['furtherInformationURL'];
                $mr->title                  = $media['title'];
                $mr->Owner                  = $media['Owner'];
                $mr->agentID                = $media['agentID'];
                
                if(!isset($this->object_ids[$mr->identifier])) {
                    $this->object_ids[$mr->identifier] = '';
                    $this->archive_builder->write_object_to_file($mr);
                }
                // */
            }
        }
    }
    private function last_quality_check($media)
    {
        $fields = array_keys($media);
        foreach($fields as $field) {
            $media[$field] = str_replace("\t", " ", $media[$field]);
            $media[$field] = str_replace("\n", "<br>", $media[$field]);
        }
        return $media;
    }
    private function gen_agent_ids($artists, $role)
    {   
        /* $artists must not be:
        Array (
            [name] => Wikigraphists
            [homepage] => https://en.wikipedia.org/wiki/Wikipedia:Graphics_Lab
            [role] => creator
        )
        but rather:
        Array(
            0 => Array
            (
                [name] => Wikigraphists
                [homepage] => https://en.wikipedia.org/wiki/Wikipedia:Graphics_Lab
                [role] => creator
            )
        )
        */
        if(isset($artists['name']))
        {
            $temp = $artists;
            $artists = array();
            $artists[] = $temp;
        }
        
        $agent_ids = array();
        foreach($artists as $a) {
            if(!$a['name']) continue;
            $r = new \eol_schema\Agent();
            $r->term_name       = $a['name'];
            $r->agentRole       = ($val = @$a['role']) ? (string) $val : (string) $role;

            /* to capture erroneous artist entries
            if(strlen($r->agentRole) == 1)
            {
                print_r($artists);
                exit("\nagent role is just 1 char\n");
            }
            */

            $r->term_homepage   = @$a['homepage'];
            $r->identifier      = md5("$r->term_name|$r->agentRole");
            $agent_ids[] = $r->identifier;
            if(!isset($this->agent_ids[$r->identifier])) {
               $this->agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    
    private function get_commons_info($url)
    {
        $final = array();
        // <a href="/wiki/File:A_hand-book_to_the_primates_(Plate_XL)_(5589462024).jpg"
        // <a href="/wiki/File:Irrawaddy_Dolphin.jpg"
        echo("\nelix:[$url]\n");
        $options = $this->download_options;
        if($html = Functions::lookup_with_cache($url, $options)) {
            if(preg_match_all("/<a href=\"\/wiki\/File:(.*?)\"/ims", $html, $arr)) {
                $files = array_values(array_unique($arr[1]));
                // print_r($files); //exit;
                
                //for utility use only, will not pass here on normal operation =========================== start
                if($this->save_all_filenames) { 
                    self::save_filenames_2file($files);
                    return;
                }
                //for utility use only, will not pass here on normal operation =========================== end
                
                $limit = 0;
                foreach($files as $file) { // https://commons.wikimedia.org/wiki/File:Eyes_of_gorilla.jpg
                    $rek = self::process_file($file);
                    if($rek == "continue") continue;
                    if(!$rek) continue;
                    
                    /* debug only
                    $rek = self::process_file("Red_stingray2.jpg"); //8680729
                    // $rek = self::process_file("Soft-shell_crab_on_ice.jpg"); //10964578
                    // $rek = self::process_file("Slifkin.jpg"); //11930268
                    // $rek = self::process_file("Clone_war_of_sea_anemones_3.jpg"); //18645958
                    $final[] = $rek;
                    break; //debug
                    */
                    
                    // print_r($rek); //exit;
                    
                    if($rek['pageid']) {
                        $final[] = $rek;
                        $limit++;
                    }
                    // if($limit >= 35) break; //no. of images to get
                }
                // exit("\n cha222 \n");
            }
        }
        // print_r($final);exit;
        return $final;
    }
    
    private function process_file($file) //e.g. Abhandlungen_aus_dem_Gebiete_der_Zoologie_und_vergleichenden_Anatomie_(1841)_(16095238834).jpg
    {
        $rek = array();
        // if(false) //will force to use API data - debug only
        if($filename = self::has_cache_data($file)) { //Eyes_of_gorilla.jpg - used in normal operation -- get media info from commons
            echo "\nused cache data";
            $rek = self::get_media_metadata_from_json($filename, $file);
            if($rek == "protected") return "continue";
            if(!$rek) {
                echo "\njust used api data instead";
                /*
                if(!in_array($file, array("The_marine_mammals_of_the_north-western_coast_of_North_America,_described_and_illustrated;_together_with_an_account_of_the_American_whale-fishery_(1874)_(14598172619).jpg", 
                "The_marine_mammals_of_the_north-western_coast_of_North_America_described_and_illustrated_(microform)_-_together_with_an_account_of_the_American_whale-fishery_(1874)_(20624848441).jpg"))) exit("\n111 [$file] 222\n");
                */
                $rek = self::get_media_metadata_from_api($file);
            }
            // print_r($rek); exit;
        }
        else {
            echo "\nused api data";
            $rek = self::get_media_metadata_from_api($file);
        }
        if(!$rek) return false;
        
        $rek['source_url']  = "https://commons.wikimedia.org/wiki/File:".$file;
        $rek['media_url']   = self::get_media_url($file);
        $rek['Artist']      = self::format_artist($rek['Artist']);
        $rek['ImageDescription'] = Functions::remove_this_last_char_from_str($rek['ImageDescription'], "|");
        
        //will capture in report source of various invalid data (to check afterwards) but will not stop process.
        if(!self::url_is_valid($rek['source_url'])) {
            $this->debug['invalid source_url'][$rek['pageid']] = '';
            $rek['source_url'] = '';
        }
        if(!self::url_is_valid($rek['media_url'])) {
            $this->debug['invalid media_url'][$rek['pageid']] = '';
            return false;
        }

        /* not the proper place here...
        if(!self::valid_license_YN($rek['LicenseUrl'])) {
            $rek['LicenseUrl'] = self::format_license($rek['LicenseUrl']);
            if(!self::valid_license_YN($rek['LicenseUrl'])) {
                print_r($rek); exit("\nstop muna tayo\n");
                $this->debug['invalid license pageid is'][$rek['pageid']] = '';
                return false;
            }
        }
        */
        
        // if(!self::lang_is_valid())
        // print_r($rek); exit("\nice\n");
        /* ditox
        URI: http://purl.org/dc/terms/language
        Message: Language should use standardized ISO 639 language codes
        Line Value:  Burma creeper, Chinese honeysuckle, Rangoon creeper (English) 
        */
        
        return $rek;
    }
    private function url_is_valid($url)
    {
        $url = trim($url);
        if(substr($url,0,7) == "http://") return true;
        if(substr($url,0,8) == "https://") return true;
        return false;
    }
    private function lang_is_valid($lang)
    {
        $lang = trim($lang);
        if(strlen($lang) <= 3) return true;
        else                   return false;
    }

    private function format_artist($str)
    {
        if(is_array($str)) return $str;
        $str = trim($str);
        // [Artist] => [[User:Chiswick Chap|Ian Alexander]]
        if(preg_match("/\[\[User:(.*?)\]\]/ims", $str, $a)) {
            $arr = explode("|", $a[1]);
            $arr = array_unique($arr);
            $final = array();
            foreach($arr as $t) $final[] = array('name' => $t, 'homepage' => "https://commons.wikimedia.org/wiki/User:".str_replace(" ", "_", $t));
            if($final) return $final;
        }
        
        //[Artist] => <a rel="nofollow" class="external text" href="https://www.flickr.com/people/126377022@N07">Internet Archive Book Images</a>
        if(substr($str,0,3) == "<a " && substr_count($str, '</a>') == 1) {
            $temp = array();
            if(preg_match("/>(.*?)<\/a>/ims", $str, $a))    $temp['name'] = $a[1];
            if(preg_match("/href=\"(.*?)\"/ims", $str, $a)) $temp['homepage'] = $a[1];
            if($temp) {
                $final[] = $temp;
                return $final;
            }
        }
        
        //[Artist] => <span lang="en">Anonymous</span>
        if(substr($str,0,6) == "<span " && substr_count($str, '</span>') == 1) {
            return array(array('name' => strip_tags($str)));
        }
        
        return $str;
    }
    
    private function has_cache_data($file)
    {
        if($filename = self::taxon_media($file)) {
            if(filesize($filename) > 0) return $filename;
        }
        return false;
    }
    
    private function wiki_protected($wiki)
    {
        if(stripos($wiki, "{{Mprotected}}") !== false) return true; //string is found
        if(stripos($wiki, "Wiktionary-logo") !== false) return true; //string is found
        if(stripos($wiki, "Wikispecies-logo") !== false) return true; //string is found
        return false;
    }
    private function get_media_metadata_from_json($filename, $title)
    {
        $json = file_get_contents($filename);
        $json = self::clean_html($json);
        $dump_arr = json_decode($json, true);
        $rek = array();
        $rek['pageid'] = $dump_arr['id'];
        
        // if($rek['pageid'] == "36373984") print_r($dump_arr); //exit;
        
        $rek['timestamp'] = $dump_arr['revision']['timestamp'];

        $wiki = $dump_arr['revision']['text'];
        if(self::wiki_protected($wiki)) return "protected";

        //================================================================ ImageDescription
        if($rek['ImageDescription'] = self::convert_wiki_2_html($wiki)) {}
        else return false;
        //================================================================ LicenseShortName
        // == {{int:license-header}} ==
        // {{Flickr-no known copyright restrictions}}
        if(preg_match("/== \{\{int:license-header\}\} ==(.*?)\}\}/ims", $wiki, $a) ||
           preg_match("/==\{\{int:license-header\}\}==(.*?)\}\}/ims", $wiki, $a))
        {
            $tmp = trim(str_replace("{", "", $a[1]));
            $rek['LicenseShortName'] = $tmp;
        }
        //================================================================ LicenseUrl
        //  -- http://creativecommons.org/licenses/by-sa/3.0 
        if(preg_match("/http:\/\/creativecommons.org\/licenses\/(.*?)\"/ims", $rek['ImageDescription'], $a)) {
            $rek['LicenseUrl'] = "http://creativecommons.org/licenses/" . $a[1];
        }
        //================================================================ title
        if($rek['title'] = self::get_title_from_ImageDescription($rek['ImageDescription'])) {}
        else $rek['title'] = str_replace("_", " ", $title);

        //================================================================ other metadata
        /*
        |date=1841
        |author=Schlegel, H. (Hermann), 1804-1884
        |source=https://www.flickr.com/photos/internetarchivebookimages/16095238834/
        |permission={{User:Fæ/Flickr API}}
        */
        if(preg_match("/\|date\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['date'] = $a[1];
        if(preg_match("/\|author\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['author'] = trim($a[1]);
        if(preg_match("/\|source\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['source'] = $a[1];
        if(preg_match("/\|permission\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['permission'] = $a[1];
        $rek['date'] = @$rek['other']['date'];
        //================================================================ Artist
        $rek['Artist'] = trim(@$rek['other']['author']);

        if(!$rek['Artist']) { //became the 1st option. Before was just the 2nd option
            echo "\nelix went here aaa\n";
            if($val = self::second_option_for_artist_info($dump_arr)) $rek['Artist'][] = $val;
        }
        
        if(!$rek['Artist']) {
            echo "\nelix went here bbb\n";
            $rek['Artist'] = self::get_artist_from_ImageDescription($rek['ImageDescription']); //get_media_metadata_from_json()
        }
        if(!$rek['Artist']) {
            echo "\nelix went here ccc\n";
            if($val = self::get_artist_from_special_source($wiki, '')) $rek['Artist'][] = $val; //get_media_metadata_from_json()
        }
        // parse this value = "[http://www.panoramio.com/user/6099584?with_photo_id=56065015 Greg N]"
        
        // /* ================================ new Oct 7, 2017 -- comment it first...
        if(is_array($rek['Artist'])) {
            // echo "\nartist is ARRAY()"; print_r($rek['Artist']); //debug only
        }
        else {
            echo "\nartist is STRING: [".$rek['Artist']."]\n";
            /* //new first option
                [revision] => Array
                    (
                        [id] => 178748754
                        [parentid] => 139462069
                        [timestamp] => 2015-11-10T22:44:04Z
                        [contributor] => Array
                            (
                                [username] => Mariomassone
                                [id] => 412814
            */
            if($val = @$dump_arr['revision']['contributor']['username']) {
                unset($rek['Artist']);
                $rek['Artist'][] = array('name' => $val, 'homepage' => "https://commons.wikimedia.org/wiki/User:".$val, 'role' => 'source');
            }
            
            elseif(substr($rek['Artist'],0,5) == "[http") { //[https://sites.google.com/site/thebrockeninglory/ Brocken Inaglory]
                $tmp_arr = explode(" ", $rek['Artist']);
                unset($rek['Artist']);
                $temp = array();
                $temp['homepage'] = trim($tmp_arr[0]);

                $tmp_arr[0] = null;
                $tmp_arr = array_filter($tmp_arr);
                $temp['name'] = implode(" ", $tmp_arr);

                // remove "[" "]"
                $temp['name'] = str_replace(array("[","]"), "", $temp['name']);
                $temp['homepage'] = str_replace(array("[","]"), "", $temp['homepage']);

                //start special
                if(!$temp['name'] && $temp['homepage'] == "https://www.flickr.com/photos/hdport/") $temp['name'] = "Hunter Desportes";
                //end special
                
                if($temp['name']) $rek['Artist'][] = $temp;
                
                //start another special 
                /*[other] => Array (
                         [date] => 2009-03-13
                         [author] => [https://www.flickr.com/photos/sempivirens/]
                         [source] => [https://www.flickr.com/photos/sempivirens/3355235281]
                         [permission] => {{User:FlickreviewR/reviewed-pass-change|Sequoia Hughes|http://flickr.com/photos/29225241@N04/3355235281|2015-01-23 19:50:34|cc-by-2.0|cc-by-sa-2.0}}
                computed homepage is "https://www.flickr.com/photos/sempivirens/" but blank name */
                // print_r($rek['other']); exit;
                if(preg_match("/User\:(.*?)\//ims", $rek['other']['permission'], $a)) {
                    $rek['Artist'][] = array('name' => $a[1], 'homepage' => "https://commons.wikimedia.org/wiki/User:".$a[1], 'role' => 'source');
                }
                //end another special
                
                //start another special 
                /* [LicenseShortName] => User:FlickreviewR/reviewed-pass|Jon David Nelson|https://flickr.com/photos/65771669@N07/15115751721|2015-12-01 12:50:33|cc-by-2.0| */
                if(preg_match("/User\:(.*?)\//ims", $rek['LicenseShortName'], $a)) {
                    $rek['Artist'][] = array('name' => $a[1], 'homepage' => "https://commons.wikimedia.org/wiki/User:".$a[1], 'role' => 'source');
                }
                //end another special 

            }
            /* this is covered in elseif() below this
            elseif(substr($rek['Artist'],0,7) == "[[User:") //[[User:Tomascastelazo|Tomas Castelazo]]
            {
                $temp = str_replace(array("[","]"), "", $rek['Artist']);
                $tmp_arr = explode("|", $temp);
                unset($rek['Artist']);
                if($name = @$tmp_arr[1]) $rek['Artist'][] = array('name' => $name, 'homepage' => "https://commons.wikimedia.org/wiki/".$tmp_arr[0]);
            }
            */
            //possible values --> "[[User:Victuallers]]" "[[User:Tomascastelazo|Tomas Castelazo]]" "*Original: [[User:Chiswick Chap|Chiswick Chap]]"
            elseif(stripos($rek['Artist'], "[[User:") !== false && stripos($rek['Artist'], "]]") !== false) //string is found //e.g. *Original: [[User:Chiswick Chap|Chiswick Chap]]
            {
                echo "\nartist value is: ".$rek['Artist']."\n";
                if(preg_match_all("/\[\[(.*?)\]\]/ims", $rek['Artist'], $a))
                {
                    // print_r($a);
                    unset($rek['Artist']);
                    foreach($a[1] as $t)
                    {
                        $tmp_arr = explode("|", $t); //"[[User:Tomascastelazo|Tomas Castelazo]]" "*Original: [[User:Chiswick Chap|Chiswick Chap]]"
                        if($name = @$tmp_arr[1]) $rek['Artist'][] = array('name' => $name, 'homepage' => "https://commons.wikimedia.org/wiki/".$tmp_arr[0]);
                        else //"[[User:Victuallers]]"
                        {
                            $user = str_ireplace("User:", "", $t);
                            $rek['Artist'][] = array('name' => $user, 'homepage' => "https://commons.wikimedia.org/wiki/User:".$user);
                        }
                    }
                }
            }
            else {
                $name = $rek['Artist'];
                unset($rek['Artist']);
                $rek['Artist'][] = array('name' => $name);
            }
            // else exit("\nInvestiage this artist string\n");
            
            if(is_array($rek['Artist'])) {
                // echo "\nartist is now also ARRAY()\n"; print_r($rek['Artist']);
            }
            else echo "\nSTILL not an array...investigate...\n";
            
        }
        // ================================ */
        
        //================================================================ END
        $rek['fromx'] = 'dump';
        
        /* good debug for Artist dump
        if($rek['pageid'] == "36125309")
        {
            echo "\n=================investigate dump data===========start\n";
            print_r($dump_arr);
            print_r($rek);
            echo "\n=================investigate dump data===========end\n";
            exit("\nwait..investigate here...\n");
        }
        */
        return $rek;
    }
    private function second_option_for_artist_info($arr)
    {
        /*(
            [title] => File:Brassica oleracea2.jpg
            [ns] => 6
            [id] => 56480
            [revision] => Array
                (
                    [id] => 141570217
                    [parentid] => 26626799
                    [timestamp] => 2014-12-06T05:29:10Z
                    [contributor] => Array
                        (
                            [username] => JarektBot
                            [id] => 472310
                        )
        */
        if($val = @$arr['revision']['contributor']['username']) {
            $a['name'] = $val;
            $a['homepage'] = "https://commons.wikimedia.org/wiki/User:$val";
            $a['role'] = "source";
            return $a;
        }
        elseif($val = @$arr['revision']['text']) {
            if(stripos($val, "{{Wellcome Images}}") !== false) { //string is found
                return array('name' => "Wellcome Images", 'homepage' => "https://wellcomeimages.org/", 'role' => 'creator');
            }
        }
        return false;
    }
    private function get_artist_from_ImageDescription($description)
    {
        // <td lang="en">Author</td> 
        // <td><a href="https://commons.wikimedia.org/wiki/User:Sardaka" title="User:Sardaka">Sardaka</a></td> 
        if(preg_match("/>Author<\/td>(.*?)<\/td>/ims", $description, $a)) {
            echo "\nelix 111\n";
            $temp = $a[1];
            $final = array(); $atemp = array();
            if(preg_match("/href=\"(.*?)\"/ims", $temp, $a)) $atemp['homepage'] = trim($a[1]);
            if(preg_match("/\">(.*?)<\/a>/ims", $temp, $a)) $atemp['name'] = trim($a[1]);
            if(@$atemp['name']) {
                $final[] = $atemp;
                return $final;
            }
            else {
                echo "\nelix 222\n";
                // <td lang="en">Author</td>
                // <td>Museo Nacional de Chile.</td>
                // echo("\n[@$a[1]]\n");
                if($name = trim(strip_tags($temp))) {
                    $final[] = array('name' => $name);
                    return $final;
                }
                else echo "\nelix 333\n";
            }
        }

        /* heavy so commented first, will see how the preview goes in V3 and get back to this
        elseif(stripos($description, "{{Wellcome Images}}") !== false) { //string is found
            return array('name' => "Wellcome Images", 'homepage' => "https://wellcomeimages.org/", 'role' => 'creator');
        }
        */
        
        elseif(preg_match("/Photographer<\/td>(.*?)<\/td>/ims", $description, $a)) { //<td >Photographer</td> <td>Hans Hillewaert</td>
            echo "\nelix 333 333\n";
            $temp = $a[1];
            $final = array(); $atemp = array();
            if(preg_match("/href=\"(.*?)\"/ims", $temp, $a)) $atemp['homepage'] = trim($a[1]);
            $atemp['name'] = strip_tags(trim($temp)); //format_artist
            if(@$atemp['name']) {
                $final[] = $atemp;
                return $final;
            }
        }
        else {
            echo "\nelix 555\n";
            // echo "\n$description\n";
            // wiki/User:Bewareofdog" title="en:User:Bewareofdog"
            if(preg_match("/wiki\/User\:(.*?)\"/ims", $description, $a)) {
                echo "\nelix 444\n";
                $final[] = array('name' => $a[1], 'homepage' => "https://commons.wikimedia.org/wiki/User:".$a[1]);
                // print_r($final); exit("\n$description\n");
                return $final;
            }
            elseif(preg_match("/Fotograf oder Zeichner\:(.*?)Lizenzstatus/ims", $description, $a)) //Fotograf oder Zeichner: Goldlocki Lizenzstatus:
            {
                if($val = trim($a[1])) {
                    $final[] = array('name' => $val);
                    return $final;
                }
            }
            elseif(stripos($description, "Category:Wikigraphists") !== false) { //string is found
                return array('name' => "Wikigraphists", 'homepage' => "https://en.wikipedia.org/wiki/Wikipedia:Graphics_Lab", 'role' => 'creator');
            }
            elseif(stripos($description, "Medicago italica") !== false) { //string is found
                return array('name' => "Medicago italica", 'homepage' => "", 'role' => 'source');
            }
        }
        return false;
    }
    
    private function remove_portions_of_wiki($wiki)
    {
        // =={{Assessment}}==
        $wiki = str_ireplace("=={{Assessment}}==", "", $wiki);

        //{{Assessment }}
        if(preg_match("/\{\{Assessment(.*?)\}\}/ims", $wiki, $a)) $wiki = str_ireplace("{{Assessment" . $a[1] . "}}", "", $wiki);

        // {{User:FlickreviewR }}
        if(preg_match("/\{\{User:FlickreviewR(.*?)\}\}/ims", $wiki, $a)) $wiki = str_ireplace("{{User:FlickreviewR" . $a[1] . "}}", "", $wiki);
        
        // {{Check categories }}
        if(preg_match("/\{\{Check categories(.*?)\}\}/ims", $wiki, $a)) $wiki = str_ireplace("{{Check categories" . $a[1] . "}}", "", $wiki);
        
        //===Versions:===
        $wiki = str_ireplace("===Versions:===", "", $wiki);
        
        //test...
        /*
        $wiki = str_ireplace("== {{int:license-header}} ==", "", $wiki);
        $wiki = str_ireplace("{{self|cc-by-sa-3.0}}", "", $wiki);
        */
        
        $wiki = str_ireplace("{{gardenology}}", "", $wiki); //e.g. Gardenology.org-IMG_2825_rbgs11jan.jpg
        return $wiki;
    }
    
    private function convert_wiki_2_html($wiki)
    {
        $url = "https://www.mediawiki.org/w/api.php?action=parse&contentmodel=wikitext&format=json&text=";
        $url = "https://commons.wikimedia.org/w/api.php?action=parse&contentmodel=wikitext&format=json&text="; //much better API version

        $wiki = self::remove_portions_of_wiki($wiki);
        $count = strlen($wiki);
        echo "\ncount = [$count]\n";
        if($count >= 2995) return false; //2995 //4054 //6783
        
        $options = $this->download_options;
        $options['expire_seconds'] = false; //always false
        if($json = Functions::lookup_with_cache($url.urlencode($wiki), $options)) {
            $arr = json_decode($json, true);
            // echo "\n==========\n";
            // print_r($arr);
            
            $html = $arr['parse']['text']['*'];
            if(preg_match("/elix(.*?)<!--/ims", "elix".$html, $a)) {
                $html = trim($a[1]);
                $html = str_ireplace('href="//', 'href="http://', $html);
                $html = str_ireplace('href="/', 'href="https://commons.wikimedia.org/', $html);
                $html = self::format_wiki_substr($html);
                
                $html = str_ireplace("&nbsp;", " ", $html);
                $html = Functions::remove_whitespace($html);
                
                /*
                //double Template:Information field -> not needed when using the commons.wikimedia.org API
                $temp = '<a href="https://commons.wikimedia.org/w/index.php?title=Template:Information_field&action=edit&redlink=1" class="new" title="Template:Information field (page does not exist)">Template:Information field</a>';
                $html = str_ireplace($temp.$temp, $temp, $html);
                */
                
                //remove style
                if(preg_match_all("/style=\"(.*?)\"/ims", $html, $a)) {
                    foreach($a[1] as $style) $html = str_ireplace('style="'.$style.'"', "", $html);
                }

                //others
                $html = str_ireplace(" (page does not exist)", "", $html);
                
                /*
                //Template removal when using API mediawiki.org -> not needed when using the commons.wikimedia.org API
                $html = str_ireplace('<a href="https://commons.wikimedia.org/w/index.php?title=Template:Date-time_separator&action=edit&redlink=1" class="new" title="Template:Date-time separator">Template:Date-time separator</a>', "", $html);
                $html = str_ireplace('<a href="https://commons.wikimedia.org/w/index.php?title=Template:Formatting_error&action=edit&redlink=1" class="new" title="Template:Formatting error">Template:Formatting error</a>', "", $html);
                $html = str_ireplace('<a href="https://commons.wikimedia.org/w/index.php?title=Template:Own&action=edit&redlink=1" class="new" title="Template:Own">Template:Own</a>', "Own work", $html);
                $arr = array("Self", "Location dec", "Geograph");
                foreach($arr as $t) $html = str_ireplace('<a href="https://commons.wikimedia.org/w/index.php?title=Template:'.str_replace(" ", "_", $t).'&action=edit&redlink=1" class="new" title="Template:'.$t.'">Template:'.$t.'</a>', "", $html);
                */
                
                $html = strip_tags($html, "<table><tr><td><br><a><i>"); //strip_tags

                $html = str_ireplace("([//www.mediawiki.org/w/index.php?title=API&action=purge# purge])", "", $html);
                $html = Functions::remove_whitespace($html);
                
                $html = str_ireplace('[<a href="https://commons.wikimedia.org/w/index.php?title=API&action=edit&section=1" class="mw-redirect" title="Edit section: Summary">edit</a>]', "", $html);
                $html = str_ireplace('[<a href="https://commons.wikimedia.org/w/index.php?title=API&action=edit&section=2" class="mw-redirect" title="Edit section: Licensing">edit</a>]', "", $html);
                
                $arr = array("class", "id");
                foreach($arr as $attrib)
                {
                    //remove class="" id=""
                    if(preg_match_all("/$attrib=\"(.*?)\"/ims", $html, $a)) {
                        foreach($a[1] as $style) $html = str_ireplace($attrib.'="'.$style.'"', "", $html);
                    }
                }
                
                $html = str_ireplace("<tr >", "<tr>", $html);
                $html = str_ireplace("<td >", "<td>", $html);

                //remove 2 rows before 'License'
                $html = str_ireplace("I, the copyright holder of this work, hereby publish it under the following license:", "", $html);
                $html = str_ireplace("You are free: to share – to copy, distribute and transmit the work to remix – to adapt the work Under the following conditions: attribution – You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work). share alike – If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.", "", $html);

                $html = Functions::remove_whitespace($html); //always the last step
                
                //remove Flickr's long licensing portion
                $html = str_ireplace('Licensing <table cellspacing="8" cellpadding="0" lang="en" > <tr> <td><i>This image was taken from <a href="https://commons.wikimedia.org/wiki/Flickr" title="Flickr">Flickr</a>'."'".'s <a rel="nofollow" href="https://flickr.com/commons">The Commons</a>. The uploading organization may have various reasons for determining that no known copyright restrictions exist, such as:<br /></i> The copyright is in the public domain because it has expired; The copyright was injected into the public domain for other reasons, such as failure to adhere to required formalities or conditions; The institution owns the copyright but is not interested in exercising control; or The institution has legal rights sufficient to authorize others to use the work without restrictions. More information can be found at <a rel="nofollow" href="https://flickr.com/commons/usage/">https://flickr.com/commons/usage/</a> Please add additional <a href="https://commons.wikimedia.org/wiki/Commons:Copyright_tags" title="Commons:Copyright tags">copyright tags</a> to this image if more specific information about copyright status can be determined. See <a href="https://commons.wikimedia.org/wiki/Special:MyLanguage/Commons:Licensing" title="Special:MyLanguage/Commons:Licensing">Commons:Licensing</a> for more information.No known copyright restrictionsNo restrictionshttps://www.flickr.com/commons/usage/false </td> </tr> </table>', "", $html);
             
                //remove {{PD-scan|PD-old-100}} long licensing portion
                $html = str_ireplace('Licensing <table cellspacing="8" cellpadding="0" > <tr> <td>This image is in the <a href="https://en.wikipedia.org/wiki/public_domain" title="w:public domain">public domain</a> because it is a mere mechanical scan or photocopy of a public domain original, or – from the available evidence – is so similar to such a scan or photocopy that no copyright protection can be expected to arise. The original itself is in the public domain for the following reason: <table > <tr> <td>Public domainPublic domainfalsefalse</td> </tr> </table> <table lang="en"> <tr> <td rowspan="2"></td> <td> This work is in the <a href="https://en.wikipedia.org/wiki/public_domain" title="en:public domain">public domain</a> in its country of origin and other countries and areas where the <a href="https://en.wikipedia.org/wiki/List_of_countries%27_copyright_length" title="w:List of countries'."'".' copyright length">copyright term</a> is the author'."'".'s life plus 100 years or less. You must also include a <a href="https://commons.wikimedia.org/wiki/Commons:Copyright_tags#United_States" title="Commons:Copyright tags">United States public domain tag</a> to indicate why this work is in the public domain in the United States. </td> </tr> <tr> <td colspan="2"> <a rel="nofollow" href="https://creativecommons.org/publicdomain/mark/1.0/deed.en">This file has been identified as being free of known restrictions under copyright law, including all related and neighboring rights.</a> </td> </tr> </table> This tag is designed for use where there may be a need to assert that any enhancements (eg brightness, contrast, colour-matching, sharpening) are in themselves insufficiently creative to generate a new copyright. It can be used where it is unknown whether any enhancements have been made, as well as when the enhancements are clear but insufficient. For known raw unenhanced scans you can use an appropriate <a href="https://commons.wikimedia.org/wiki/Template:PD-old" title="Template:PD-old">{{PD-old}}</a> tag instead. For usage, see <a href="https://commons.wikimedia.org/wiki/Commons:When_to_use_the_PD-scan_tag" title="Commons:When to use the PD-scan tag">Commons:When to use the PD-scan tag</a>. Note: This tag applies to scans and photocopies only. For photographs of public domain originals taken from afar, <a href="https://commons.wikimedia.org/wiki/Template:PD-Art" title="Template:PD-Art">{{PD-Art}}</a> may be applicable. See <a href="https://commons.wikimedia.org/wiki/Commons:When_to_use_the_PD-Art_tag" title="Commons:When to use the PD-Art tag">Commons:When to use the PD-Art tag</a>.</td> </tr> </table>', "", $html);
            }
            $html = self::clean_html($html);
            $html = self::more_desc_removed($html);
            return $html;
        }
        return false;
    }
    
    private function adjust_image_desc($html)
    {
        $html = trim(self::remove_space($html));
        $html = Functions::get_str_up_to_this_chars_only($html, "<b>Text Appearing Before Image: </b>");
        $html = Functions::remove_whitespace($html);
        $html = strip_tags($html,'<a><b><br>');
        return $html;
    }

    private function more_desc_removed($html)
    {
        $findme = '</table> Licensing <table';
        $html = trim($html);
        $pos = stripos($html, $findme);
        // The !== operator can also be used.  Using != would not work as expected because the position of 'a' is 0. The statement (0 != false) evaluates to false.
        if($pos !== false) { //echo "The string '$findme' exists at position $pos";
            $html = substr($html,0,$pos);
            $html .= "</table>";
        }
        else 
        {
            $findme = '</table> Licensing[';
            $html = trim($html);
            $pos = stripos($html, $findme);
            // The !== operator can also be used.  Using != would not work as expected because the position of 'a' is 0. The statement (0 != false) evaluates to false.
            if($pos !== false) { //echo "The string '$findme' exists at position $pos";
                $html = substr($html,0,$pos);
                $html .= "</table>";
            }
        }
        return Functions::remove_whitespace($html);
    }
    private function clean_html($html)
    {
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("</table> |", "</table>", $html);
        $html = str_ireplace(" |||| ", "; ", $html); //was a weird sol'n to an imageDescription with weird chars. But it worked :-)
        return $html;
        // return Functions::remove_whitespace($html);
    }
    
    /*
    private function last_chance_for_description($str)
    {
        if(preg_match("/\|en =(.*?)\\\n/ims", $str, $a))
        {
            // |en = Inflorescence of [[:en:Oregano|Oregano]].
            // Origanum_vulgare_-_harilik_pune.jpg
            if($val = trim($a[1])) return $val;
        }
        if(preg_match("/\|Description=(.*?)\\\n/ims", $str, $a))
        {
            if($val = trim($a[1])) return $val;
        }
        if(preg_match("/\|Description (.*?)\\\n/ims", $str, $a))
        {
            if($val = trim($a[1])) return $val;
        }
        if(preg_match("/\|description (.*?)\\\n/ims", $str, $a))
        {
            if($val = trim($a[1])) return $val;
        }
        if(preg_match("/\| Description (.*?)\\\n/ims", $str, $a))
        {
            if($val = trim($a[1])) return $val;
        }
        // if(preg_match("/elix(.*?)\\\n/ims", "elix".$str, $a)) //get first row in the wiki text
        // {
        //     if($val = trim($a[1])) return $val;
        // }
        return false;
    }
    */
    
    private function get_media_metadata_from_api($file)
    {   //https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&iiprop=extmetadata&titles=Image:Gorilla_498.jpg
        $rek = array();
        $options = $this->download_options;
        $options['expire_seconds'] = false; //this can be 2 months
        if($json = Functions::lookup_with_cache("https://commons.wikimedia.org/w/api.php?format=json&action=query&prop=imageinfo&iiprop=extmetadata&titles=Image:".$file, $options))
        {
            $json = self::clean_html($json); //new ditox eli
            $arr = json_decode($json, true);
            // print_r($arr); exit;

            $arr = array_values($arr["query"]["pages"]);
            $arr = $arr[0];
            echo "\nresult: " . count($arr) . "\n";
            // print_r($arr); //exit;
            if(!isset($arr['pageid'])) return array();
            $rek['pageid'] = self::format_wiki_substr($arr['pageid']);

            $rek['ImageDescription'] = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['ImageDescription']['value']);
            $rek['ImageDescription'] = self::adjust_image_desc($rek['ImageDescription']);

            if($rek['title'] = self::get_title_from_ImageDescription($rek['ImageDescription'])) {}
            else $rek['title'] = self::format_wiki_substr($arr['title']);
            
            /*
            if($rek['pageid'] == "865581") //good debug api
            {
                echo "\n=======investigate api data =========== start\n";
                print_r($arr); exit;
                echo "\n=======investigate api data =========== end\n";
            }
            */
            
            if($val = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['Credit']['value'])) {
                if(stripos($val, "int-own-work") !== false) return false; //string is found ---- invalid license
            }
            
            //start artist ====================
            if($val = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['Artist']['value'])) {
                $val = str_ireplace("\n", "", $val);
                if(stripos($val, "User:Aktron") !== false) return false; //string is found ---- invalid license
                // User:Sevela.p
                elseif(stripos($val, "Tom Habibi") !== false) $rek['Artist'][] = array('name' => 'Tom Habibi', 'homepage' => 'http://commons.wikimedia.org/wiki/User:Tomhab~commonswiki', 'role' => 'source');

                elseif(preg_match_all("/<li>(.*?)<\/li>/ims", $val, $a)) $rek['Artist'] = self::process_li_separated_artists($a);
                
                else
                { //original block
                    $atemp = array();
                    if(preg_match("/href=\"(.*?)\"/ims", $val, $a)) {
                        $hpage = trim($a[1]);
                        if(substr($hpage,0,24) == '//commons.wikimedia.org/') $atemp['homepage'] = "https:".$hpage;
                        else                                                  $atemp['homepage'] = trim($a[1]); //orig
                    }
                    if(preg_match("/\">(.*?)<\/a>/ims", $val, $a)) {
                        echo "\nelicha 111\n";
                        $atemp['name'] = self::remove_role_from_name(strip_tags(trim($a[1]),''));
                        $atemp['role'] = 'author';
                    }
                    if(@$atemp['name']) $rek['Artist'][] = $atemp;
                    else                $rek['Artist'][] = array('name' => self::remove_space(strip_tags($val,'')), 'role' => 'author'); // e.g. <span lang="en">Anonymous</span>
                    
                    if(self::invalid_artist_name_value($rek)) $rek['Artist'] = array();
                }
            }
            if(!@$rek['Artist'])
            {
                $rek['Artist'] = self::get_artist_from_ImageDescription($rek['ImageDescription']);
                echo "\n ice 111\n";
                if(self::invalid_artist_name_value($rek)) $rek['Artist'] = array();
            }
            if(!@$rek['Artist']) {
                if($val = @$arr['imageinfo'][0]['extmetadata']['Credit']['value']) {
                    $val = str_ireplace("\n", "", $val);
                    echo "\n ice 222\n";
                    $credit_value = strip_tags($val);
                    if(stripos($credit_value, "http://wellcomeimages.org") !== false) $rek['Artist'][] = array('name' => 'Wellcome Images', 'homepage' => 'http://wellcomeimages.org', 'role' => 'source');
                    elseif(stripos($credit_value, "by the British Library") !== false) $rek['Artist'][] = array('name' => 'The British Library', 'homepage' => 'https://www.bl.uk/', 'role' => 'source');
                    else $rek['Artist'][] = array('name' => strip_tags($val));
                }
                if(self::invalid_artist_name_value($rek)) $rek['Artist'] = array();
            }
            if(!@$rek['Artist']) { //e.g. Files from Wellcome Images
                echo "\n ice 333\n";
                if($val = self::get_artist_from_special_source(@$arr['imageinfo'][0]['extmetadata']['Categories']['value'], $rek['title'])) $rek['Artist'][] = $val; //get_media_metadata_from_api()
            }
            if(!@$rek['Artist']) {
                echo "\n ice 444\n";
                if($val = self::get_artist_from_special_source($rek['ImageDescription'])) $rek['Artist'][] = $val; //get_media_metadata_from_api()
            }
            
            if($rek['Artist']) $rek['Artist'] = self::flickr_lookup_if_needed($rek['Artist']);
            
            
            //end artist ========================
            
            $rek['LicenseUrl']       = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['LicenseUrl']['value']);
            $rek['LicenseShortName'] = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['LicenseShortName']['value']);
            if($val = @$arr['imageinfo'][0]['extmetadata']['DateTime']['value'])             $rek['date'] = self::format_wiki_substr($val);
            elseif($val = @$arr['imageinfo'][0]['extmetadata']['DateTimeOriginal']['value']) $rek['date'] = self::format_wiki_substr($val);
            $rek['fromx'] = 'api'; //object metadata from API;
            
            /* debug only
            if(!$rek['Artist']) {
                print_r($arr);
                exit("\nwala artist...\n");
            }
            */
            /* debug only
            if($rek['Artist'][0]['name'] == '<span lang="en">Anonymous</span>') {
                print_r($arr);
                exit("\n investigate...\n");
            }
            */
        }
        return $rek; //$arr
    }
    private function flickr_lookup_if_needed($arr)
    {
        $i = 0;
        foreach($arr as $a) {
            if($name = $a['name']) {
                if(substr($name,0,strlen("http://www.flickr.com/photos/")) == "http://www.flickr.com/photos/") $arr[$i]['name'] = self::realname_Flickr_lookup($a['name']);
                if(substr($name,0,strlen("https://www.flickr.com/photos/")) == "https://www.flickr.com/photos/") $arr[$i]['name'] = self::realname_Flickr_lookup($a['name']);
            }
        }
        return $arr;
    }
    private function realname_Flickr_lookup($url) //from https://www.flickr.com/services/api/
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false; //this can always be false
        $options['delay_in_minutes'] = 0;
        if(preg_match("/photos\/(.*?)xxx/ims", $url."xxx", $a)) {
            $user_id = $a[1];
            $user_id = Functions::remove_this_last_char_from_str($user_id, "/");
            if(stripos($user_id, "@N") !== false) return self::get_Flickr_user_realname_using_userID($user_id, $options); //string is found
            else { //$user_id is a username
                // $user_id = 'dkeats'; //debug only
                $api_call = "https://api.flickr.com/services/rest/?method=flickr.people.findByUsername&api_key=".FLICKR_API_KEY."&username=".$user_id."&format=json&nojsoncallback=1";
                if($json = Functions::lookup_with_cache($api_call, $options)) {
                    $arr = json_decode($json, true);
                    if($user_id = @$arr['user']['id']) {
                        return self::get_Flickr_user_realname_using_userID($user_id, $options);
                    }
                }
            }
        }
        return $url;
    }
    private function get_Flickr_user_realname_using_userID($user_id, $options)
    {
        $api_call = "https://api.flickr.com/services/rest/?method=flickr.people.getInfo&api_key=".FLICKR_API_KEY."&user_id=".$user_id."&format=json&nojsoncallback=1";
        if($json = Functions::lookup_with_cache($api_call, $options)) {
            $arr = json_decode($json, true);
            if($val = @$arr['person']['realname']['_content']) return "$val ($user_id)";
            elseif($val = @$arr['person']['username']['_content']) return "$val ($user_id)";
            else return "Flickr user_id $user_id";
        }
        else return "Flickr user_id $user_id";
    }
    private function process_li_separated_artists($arr)
    {
        $final = array();
        foreach($arr[1] as $item) {
            if(preg_match("/wiki\/User\:(.*?)\"/ims", $item, $a)) $final[] = array("name" => $a[1], 'homepage' => 'https://commons.wikimedia.org/wiki/User:'.$a[1], 'role' => 'author');
            else                                                  $final[] = array("name" => self::remove_space(strip_tags($item)), 'role' => 'author', 'homepagae' => 'media_urlx');
        }
        return $final;
    }
    private function invalid_artist_name_value($rek)
    {
        if(Functions::get_mimetype($rek['Artist'][0]['name'])) return true; //name should not be an image path
        // elseif(self::url_is_valid($rek['Artist'][0]['name']))  return true; //name should not be a url - DON'T USE THIS, WILL REMAIN COMMENTED, at this point we can accept URL values as it will be resolved later
        return false;
    }
    private function remove_role_from_name($str)
    {
        $str = self::remove_space($str);
        $remove = array("Creator:");
        return str_ireplace($remove, "", $str);
    }
    private function remove_space($str)
    {
        $str = str_replace("&nbsp;", " ", $str);
        return Functions::remove_whitespace($str);
    }
    private function get_artist_from_special_source($categories, $title = "") //$categories can be any block of string
    {
        $categories = Functions::remove_whitespace($categories);
        
        if(stripos($categories, "Template Unknown (author)") !== false) { //string is found
            return array('name' => "Wikimedia Commons", 'homepage' => "https://commons.wikimedia.org/wiki/$title", 'role' => 'recorder');
        }
        if(stripos($categories, "Files from Wellcome Images") !== false) { //string is found
            return array('name' => "Wellcome Images", 'homepage' => "https://wellcomeimages.org/", 'role' => 'source');
        }
        elseif(stripos($categories, "{{Wellcome Images}}") !== false) { //string is found
            return array('name' => "Wellcome Images", 'homepage' => "https://wellcomeimages.org/", 'role' => 'source');
        }
        elseif(stripos($categories, "Files with no machine-readable author|Files with no machine-readable source") !== false) { //string is found
            return array('name' => "Wikimedia Commons", 'homepage' => $title, 'role' => 'recorder');
        }
        if(preg_match("/Photographer\:(.*?)\\n/ims", $categories, $a)) { //Photographer: Richard Ling <wikipedia@rling.com>
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'photographer');
        }
        if(preg_match("/Uploader\:(.*?)\\n/ims", $categories, $a)) { //Uploader: [[user:de:Necrophorus|Necrophorus]] 15:30, 8. Sep 2004 (CEST)
            $str = trim($a[1]);
            if($arr = self::parse_str_with_User_enclosed_in_brackets($str)) return $arr;
            else return array('name' => $str, 'homepage' => $title, 'role' => 'source');
        }

        if(preg_match("/Creator\: (.*?)\\n/ims", $categories, $a)) { //:Creator: Harrison, George
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'creator');
        }
        if(preg_match("/Publisher\: (.*?)\\n/ims", $categories, $a)) { //:Publisher: U.S. Fish and Wildlife Service
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'publisher');
        }
        if(preg_match("/Source\: (.*?)\\n/ims", $categories, $a)) { //:Source: WO-EE-4138
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'source');
        }

        if(stripos($categories, "User:The Emirr") !== false && stripos($categories, "Permission ={{Cypron}}") !== false) { //strings are found
            return array('name' => 'The Emirr/MapLab', 'homepage' => 'https://commons.wikimedia.org/wiki/User:The_Emirr/MapLab/Cypron', 'role' => 'author');
        }
        
        //Images from the CDC Public Health Image Library
        if(stripos($categories, "Public Health Image") !== false) { //strings are found
            return array('name' => 'CDC Public Health Image Library', 'homepage' => 'https://commons.wikimedia.org/wiki/Template:CDC-PHIL', 'role' => 'source');
        }

        if(stripos($categories, "PD US HHS CDC") !== false) { //strings are found
            return array('name' => 'Centers for Disease Control and Prevention', 'homepage' => 'https://en.wikipedia.org/wiki/Centers_for_Disease_Control_and_Prevention', 'role' => 'source');
        }
        
        //last options
        if(preg_match("/Photo by (.*?)\./ims", $categories, $a)) { //from wiki text - description: "...Photo by Gus van Vliet."
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'photographer');
        }
        if(preg_match("/\[\[Category:Photographs by (.*?)\]\]/ims", $categories, $a)) { //from wiki text - description: "...[[Category:Photographs by Ernst Schäfer]]..."
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'photographer');
        }

        if(stripos($categories, "Files from Flickr's 'The Commons'") !== false) { //string is found
            return array('name' => "Flickr's 'The Commons'", 'homepage' => "https://flickr.com/commons", 'role' => 'source');
        }
        //real last option
        if(stripos($categories, "[[Category:Media missing infobox template]]") !== false) { //string is found
            return array('name' => "Wikimedia Commons", 'homepage' => "https://commons.wikimedia.org/wiki/Main_Page", 'role' => 'source');
        }
        return false;
    }
    private function parse_str_with_User_enclosed_in_brackets($str)
    {
        if(stripos($str, "[[User:") !== false && stripos($str, "]]") !== false) { //string is found //e.g. *Original: [[User:Chiswick Chap|Chiswick Chap]]
            if(preg_match("/\[\[(.*?)\]\]/ims", $str, $a)) {
                $tmp_arr = explode("|", $a[1]); //"[[User:Tomascastelazo|Tomas Castelazo]]" "*Original: [[User:Chiswick Chap|Chiswick Chap]]"
                if($name = @$tmp_arr[1]) return array('name' => $name, 'homepage' => "https://commons.wikimedia.org/wiki/".$tmp_arr[0]);
                else { //"[[User:Victuallers]]"
                    $user = str_ireplace("User:", "", $a[1]);
                    return array('name' => $user, 'homepage' => "https://commons.wikimedia.org/wiki/User:".$user);
                }
            }
        }
    }
    private function get_title_from_ImageDescription($desc)
    {
        $desc = strip_tags($desc, "<br>");
        if(preg_match("/Title:(.*?)<br>/ims", $desc, $arr)) return trim($arr[1]);
        return false;
    }
    
    private function wiki2html($str)
    {
        if(preg_match_all("/\[(.*?)\]/ims", $str, $a)) {
            $divided = array();
            foreach($a[1] as $tmp) {
                $arr = explode(" ", $tmp);
                $url = $arr[0];
                array_shift($arr);
                $link_text = implode(" ", $arr);
                $divided[] = array("url" => $url, "link_text" => $link_text);
            }
            $i = 0;
            foreach($a[1] as $tmp) {
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

    private function create_media_object($media) //for wikipedia only
    {
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
        if(!isset($this->object_ids[$media['identifier']])) {
            if(!($f = Functions::file_open($this->media_extension, "a"))) return;
            fwrite($f, $row);
            fclose($f);
        }
        // */

        // /*
        if(!$this->passed_already) {
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
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
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
    
    private function get_other_info($rek)
    {
        $func = new WikipediaRegionalAPI($this->resource_id, $this->language_code);
        if($title = $rek['sitelinks']->title) {
            // $title = "Dicorynia"; //debug
            $url = "https://" . $this->language_code . ".wikipedia.org/wiki/" . str_replace(" ", "_", $title);
            $domain_name = $func->get_domain_name($url);

            $options = $this->download_options;
            // if($rek['taxon_id'] == "Q5113") $options['expire_seconds'] = true; //debug only force

            if($html = Functions::lookup_with_cache($url, $options)) {
                if(self::bot_inspired($html)) {
                    echo("\nbot inspired: [$url]\n");
                    return $rek;
                }
                $rek['other'] = array();
                $html = $func->prepare_wiki_for_parsing($html, $domain_name);
                $rek['other']['title'] = $title;

                $desc = $func->get_comprehensive_desc($html);
                $rek['other']['comprehensive_desc'] = self::additional_desc_format($desc);
                
                // $rek['other']['comprehensive_desc'] = "the quick brown fox jumps over the lazy dog...";  //debug
                $rek['other']['brief_summary'] = self::create_brief_summary($rek['other']['comprehensive_desc']);
                $rek['other']['permalink']        = $func->get_permalink($html);
                $rek['other']['last_modified']    = $func->get_last_modified($html);
                $rek['other']['phrase']           = $func->get_wikipedia_phrase($html);
                $rek['other']['citation']         = $func->get_citation($rek['other']['title'], $rek['other']['permalink'], $rek['other']['last_modified'], $rek['other']['phrase']);
            }
        }
        return $rek;
    }
    private function additional_desc_format($desc)
    {
        // remove class and style attributes in tags
        // e.g. class="infobox biota" 
        // e.g. style="text-align: left; width: 200px; font-size: 100%"
        if(preg_match_all("/class=\"(.*?)\"/ims", $desc, $arr)) {
            foreach($arr[1] as $item) $desc = str_replace('class="'.$item.'"', "", $desc);
        }
        if(preg_match_all("/style=\"(.*?)\"/ims", $desc, $arr)) {
            foreach($arr[1] as $item) $desc = str_replace('style="'.$item.'"', "", $desc);
        }
        
        // removes html comments <!-- ??? -->
        if(preg_match_all("/<\!\-\-(.*?)\-\->/ims", $desc, $arr)) {
            foreach($arr[1] as $item) $desc = str_replace('<!--'.$item.'-->', "", $desc);
        }

        $desc = Functions::remove_whitespace($desc);
        $desc = str_replace(' >',">",$desc);

        $arr = array("<p></p>","<div></div>");
        $desc = str_ireplace($arr, "", $desc);
        $desc = trim(self::remove_space($desc));

        // echo "\n----------------------------------Comprehensive Desc";
        // echo "\n[".$desc."]";
        // echo "\n----------------------------------\n";
        return $desc;
    }
    private function create_brief_summary($desc)
    {
        $tmp = Functions::get_str_up_to_this_chars_only($desc, "<h2");
        $tmp = self::remove_space($tmp);
        $tmp = strip_tags($tmp,'<table><tr><td><a><img><br><p>');
        $tmp = Functions::exclude_str_before_this_chars($tmp, "</table>"); //3rd param by default is "last" occurrence

        // remove inline anchor e.g. <a href="#cite_note-1">[1]</a>
        if(preg_match_all("/<a href=\"#(.*?)<\/a>/ims", $tmp, $arr)) {
            foreach($arr[1] as $item) {
                $tmp = str_replace('<a href="#'.$item.'</a>', "", $tmp);
            }
        }

        $arr = array("<p></p>");
        $tmp = trim(str_ireplace($arr, "", $tmp));
        /* debug
        echo "\n----------------------------------Brief Summary";
        echo "\n[".$tmp."]";
        echo "\n---------------------------------- no tags";
        echo "\n[".strip_tags($tmp)."]";
        echo "\n----------------------------------\n";
        */
        return $tmp;
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
        if($id = (string) @$claims->P171[0]->mainsnak->datavalue->value->id) {
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
        if($obj = self::get_object($id)) {
            /* debug only
            if($id == "Q27661141") {
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
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
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
        $raw_dump       = $this->path['raw_dump'];       //RAW fresh dump. NOT TO USE READILY - very big with all categories not just TAXA.
        $all_taxon_dump = $this->path['wiki_data_json']; //will use this instead. An all-taxon dump
        $f = Functions::file_open($all_taxon_dump, "w");
        $e = 0; $i = 0; $k = 0;
        foreach(new FileIterator($raw_dump) as $line_number => $row) {
            $k++;
            if(($k % 20000) == 0) echo " $k";
            if(stripos($row, "Q16521") !== false) { //string is found -- "taxon"
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
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_filenames_" . date("Y_m") . ".txt";
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
        $main_path = $this->path['wikimedia_cache'];
        $i = 0;
        foreach(new FileIterator(CONTENT_RESOURCE_LOCAL_PATH."wikimedia_filenames_2017_10.txt") as $line_number => $file) {
            $md5 = md5($file);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
            if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
            $filename = $main_path . "$cache1/$cache2/$md5.json";
            if(!file_exists($filename)) {
                echo "\n " . number_format($i) . " creating file: $file";
                if($FILE = Functions::file_open($filename, 'w'))  fclose($FILE);
            }
            $i++; 
            // if($i >= 100) break; //debug
        }
    }

    function fill_in_temp_files_with_wikimedia_dump_data()
    {
        $path = $this->path['commons']
        $reader = new \XMLReader();
        $reader->open($path);
        $i = 0;
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "page") {
                $page_xml = $reader->readOuterXML();
                $t = simplexml_load_string($page_xml, null, LIBXML_NOCDATA);
                $title = $t->title;
                // $title = "File:Two Gambel's Quail (Callipepla gambelii) - Paradise Valley, Arizona, ca 2004.png";
                $title = str_replace("File:", "", $title);
                $title = str_replace(" ", "_", $title);
                if($filename = self::taxon_media($title)) {
                    if(filesize($filename) == 0) {
                        echo "\n found taxon wikimedia \n";
                        $json = json_encode($t);
                        if($FILE = Functions::file_open($filename, 'w')) // normal
                        {
                            fwrite($FILE, $json);
                            fclose($FILE);
                        }
                        echo("\n[$filename] saved content\n");
                        exit("\nmeaning, this was not saved the last time this utility was ran...\n");
                    }
                    else echo("\nalready saved: [$filename]\n");
                }
                else echo "\n negative \n"; //meaning this media file is not encountered in the taxa wikidata process.
                
                /* just tests
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
        $main_path = $this->path['wikimedia_cache'];
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
        $path = $this->path['commons'];
        /*
        $i = 0;
        foreach(new FileIterator($path) as $line_number => $row) {
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
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "page") {
                $page_xml = $reader->readOuterXML();
                $t = simplexml_load_string($page_xml, null, LIBXML_NOCDATA);
                $page_id = $t->id;
                if($page_id == "47821") {
                    print_r($t); exit("\nfound 47821\n");
                }
                echo "\n$page_id";
                $title = $t->title;
                if(substr($title,0,5) == "File:") {
                    print_r($t); 
                    exit("\n$page_xml\n");
                }
                if($title == "File:Abhandlungen aus dem Gebiete der Zoologie und vergleichenden Anatomie (1841) (16095238834).jpg") {
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
    if($html = Functions::lookup_with_cache("https://commons.wikimedia.org/wiki/File:".str_replace(" ", "_", $file), $options)) {
        //<a href="https://upload.wikimedia.org/wikipedia/commons/6/67/Western_Gorilla_area.png">
        if(preg_match_all("/<a href=\"https:\/\/upload.wikimedia.org(.*?)\"/ims", $html, $arr)) {
            $files2 = array_values(array_unique($arr[1]));
            $rek['media_url'] = "https://upload.wikimedia.org".$files2[0];
        }
    }
    */
    
    /*
    // for ImageDescription 1st option
    if(preg_match("/== \{\{int:filedesc\}\} ==(.*?)\}\}\\\n/ims", $wiki, $a)) {
        // echo "\n $a[1] \n";
        if(preg_match_all("/\'\'\'(.*?)<br>/ims", $a[1], $a2)) {
            $tmp = $a2[1];
            $i = 0;
            foreach($tmp as $t) {
                $t = str_replace("'", "", $t); $tmp[$i] = $t;
                if(stripos($t, "view book online") !== false) $tmp[$i] = null; //string is found
                if(stripos($t, "Text Appearing") !== false) $tmp[$i] = null; //string is found
                if(stripos($t, "Note About Images") !== false) $tmp[$i] = null; //string is found
                if(strlen($t) < 5) $tmp[$i] = null;
                $i++;
            }
            $tmp = array_filter($tmp);
            $i = 0;
            foreach($tmp as $t) {
                $tmp[$i] = self::wiki2html($t);
                $i++;
            }
            $rek['ImageDescription'] = trim(implode("<br>", $tmp));
        }
        
        //cases where ImageDescription is still blank
        // if($rek['pageid'] == "52428898")
        if(true) {
            //e.g. [pageid] => 52428898
            if(!@$rek['ImageDescription']) {
                if(preg_match("/\|Description=\{\{(.*?)\}\}/ims", $a[1]. "}}", $a2)) //2nd option
                {
                    $temp = $a2[1];
                    $arr = explode("|1=", $temp); //since "en|1=" or "ja|1=" etc...
                    $rek['ImageDescription'] = $arr[1];
                    if($rek['ImageDescription']) {}
                    elseif($rek['ImageDescription'] = $temp) {}
                    else {
                        // print_r($arr);
                        exit("\n $a[1] - investigate desc 111");
                    }
                }
                elseif($rek['ImageDescription'] = self::last_chance_for_description($wiki)) {
                    // print_r($rek);
                    // exit("\nstop muna 222\n");
                }
                else {
                    print("\n $wiki -->> investigate no ImageDescription 222\n");
                    return false;
                }
            }
            else echo "\nelicha\n";
            // print_r($rek);
        }
        // exit;
    }
    elseif(preg_match("/\|Description=\{\{(.*?)\}\}/ims", $wiki, $a)) //2nd option
    {
        $temp = $a[1];
        $arr = explode("|1=", $temp); //since "en|1=" or "ja|1=" etc...
        $rek['ImageDescription'] = $arr[1];
    }
    elseif($rek['ImageDescription'] = self::last_chance_for_description($wiki)) //3rd option
    {
        print_r($rek);
        // exit("\nstop muna\n");
    }
    else {
        print("\ninvestigate no ImageDescription 111\n");
        return false; // use API instead
    }
    */

}
?>