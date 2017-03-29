<?php
namespace php_active_record;
// require_once DOC_ROOT . '/vendor/JsonCollectionParser-master/src/Parser.php';
require_library('connectors/WikipediaRegionalAPI');

/* 
https://en.wikipedia.org/wiki/List_of_Wikipedias
*/

class WikiDataAPI
{
    function __construct($folder, $lang, $taxonomy = false)
    {
        $this->taxonomy = $taxonomy;
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
        unlink($this->TEMP_FILE_PATH);
    }

    private function initialize_files()
    {
        $this->TEMP_FILE_PATH = temp_filepath();
        if(!($f = Functions::file_open($this->TEMP_FILE_PATH, "w"))) return;
        fclose($f);
    }
    
    private function add_parent_entries()
    {
        echo "\n\nStart add parent entries...\n\n";
        foreach(new FileIterator($this->TEMP_FILE_PATH) as $line_number => $row)
        {
            $arr = json_decode($row, true);
            // print_r($arr);
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
        $i = 0; $j = 0;
        $k = 0; $m = 4624000; $m = 600000; //only for breakdown when caching
        foreach(new FileIterator($this->wiki_data_json) as $line_number => $row)
        {
            $k++; echo " ".number_format($k)." ";
            /* breakdown when caching:
            $cont = false;
            
            if($k >=  565000    && $k < $m) $cont = true;
            // if($k >=  994000    && $k < $m*2) $cont = true;
            // if($k >=  1461000    && $k < $m*3) $cont = true;
            
            // if($k >=  1    && $k < $m) $cont = true;           //1 -   600,000
            // if($k >=  $m   && $k < $m*2) $cont = true;   //600,000 - 1,200,000
            // if($k >=  $m*2 && $k < $m*3) $cont = true; //1,200,000 - 1,800,000
            // if($k >=  $m*3 && $k < $m*4) $cont = true; //1,800,000 - 2,400,000
            // if($k >=  $m*4 && $k < $m*5) $cont = true; //2,400,000 - 3,000,000
            if(!$cont) continue;
            */

            if(stripos($row, "Q16521") !== false) //string is found -- "taxon"
            {
                /* remove the last char which is "," a comma */
                $row = substr($row,0,strlen($row)-1); //removes last char which is "," a comma
                $arr = json_decode($row);

                /* for debug start ======================
                $arr = self::get_object('Q5113');
                $arr = $arr->entities->Q5113;
                for debug end ======================== */
                
                if(is_object($arr))
                {
                    $rek = array();
                     // /*
                     $rek['taxon_id'] = trim((string) $arr->id);
                     if($rek['taxon'] = self::get_taxon_name($arr->claims))
                     {
                         // /* normal operation
                         if($rek['sitelinks'] = self::get_taxon_sitelinks_by_lang($arr->sitelinks)) //if true then create DwCA for it
                         {
                             // print_r($arr); exit; //debug
                             
                             $i++; 
                             $rek['rank'] = self::get_taxon_rank($arr->claims);
                             $rek['author'] = self::get_authorship($arr->claims);
                             $rek['author_yr'] = self::get_authorship_date($arr->claims);
                             $rek['parent'] = self::get_taxon_parent($arr->claims);
                             if(!$this->taxonomy) $rek = self::get_other_info($rek); //uncomment in normal operation
                             // print_r($rek); exit;
                             
                             if($rek['taxon_id'])
                             {
                                 self::create_archive($rek);
                                 self::save_ancestry_to_temp($rek['parent']);
                             }
                             // break;              //debug - process just 1 rec
                             
                         }
                         print_r($rek); //exit;
                         
                         
                         // break;              //debug - process just 1 rec
                         // if($i >= 7) break; //debug
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
        $t->source                   = $rec['other']['permalink'];

        if(!isset($this->taxon_ids[$t->taxonID]))
        {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }

        // if($rec['taxon_id'] == "Q5113" && $this->language_code == "ja") return; //debug
        

        //start media objects
        $media = array();
        
        // Comprehensive Description
        $media['identifier']             = md5($rec['taxon_id']."Comprehensive Description");
        $media['title']                  = $rec['other']['title'];
        $media['description']            = $rec['other']['comprehensive_desc'];
        $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description';
        // below here is same for the next text object
        $media['taxonID']                = $t->taxonID;
        $media['type']                   = "http://purl.org/dc/dcmitype/Text";
        $media['format']                 = "text/html";
        $media['language']               = $this->language_code;
        $media['Owner']                  = $this->trans['editors'][$this->language_code];
        $media['UsageTerms']             = 'http://creativecommons.org/licenses/by-sa/3.0/';
        $media['furtherInformationURL'] = $rec['other']['permalink'];
        if($media['description']) self::create_media_object($media);

        /* // Brief Summary - works well for 'de'
        $media['identifier']             = md5($rec['permalink']."Brief Summary");
        $media['title']                  = $rec['title'] . ': Brief Summary';
        $media['description']            = $rec['brief_desc'];
        $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
        if($media['description']) self::create_media_object($media);
        */
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
    
    private function get_other_info($rek)
    {
        $func = new WikipediaRegionalAPI($this->resource_id, $this->language_code);
        
        if($title = $rek['sitelinks']->title)
        {
            // $title = "Dicorynia"; //debug
            $url = "https://" . $this->language_code . ".wikipedia.org/wiki/" . str_replace(" ", "_", $title);
            $domain_name = $func->get_domain_name($url);

            $options = $this->download_options;
            // if($rek['taxon_id'] == "Q5113") $options['expire_seconds'] = false; //debug only

            if($html = Functions::lookup_with_cache($url, $options))
            {
                if(self::bot_inspired($html))
                {
                    // exit("\nbot inspired: [$url]\n");
                    return $rek;
                }
                
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
    
    private function get_taxon_name($claims)
    {
        if($val = @$claims->P225[0]->mainsnak->datavalue->value) return (string) $val;
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
                $parent['taxon_name'] = self::get_taxon_name($obj->entities->$id->claims);
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
            if($id == "Q27661141") //debug only
            {
                // print_r($obj); exit;
            }
            return (string) $obj->entities->$id->labels->en->value;
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

    // private function checkaddslashes($str){       
    //     if(strpos(str_replace("\'",""," $str"),"'")!=false)
    //         return addslashes($str);
    //     else
    //         return $str;
    // }

}
?>