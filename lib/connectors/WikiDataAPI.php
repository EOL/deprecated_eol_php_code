<?php
namespace php_active_record;
// require_once DOC_ROOT . '/vendor/JsonCollectionParser-master/src/Parser.php';
require_library('connectors/WikipediaRegionalAPI');

/* */

class WikiDataAPI
{
    function __construct($folder, $lang)
    {
        $this->resource_id = $folder;
        $this->language_code = $lang;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->download_options = array('cache_path' => '/Volumes/Thunderbolt4/eol_cache_wiki_regions/', 'expire_seconds' => false, 'download_wait_time' => 3000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->debug = array();
        
        //start
        $this->wiki_data_json = "/Volumes/Thunderbolt4/wikidata/latest-all.json";
        $this->wiki_data_taxa_json = "/Volumes/Thunderbolt4/wikidata/latest-all-taxon.json"; //used in utility to create an all-taxon dump
        // $this->wiki_data_json = "/Volumes/Thunderbolt4/wikidata/latest-all-taxon.json"; //used in utility to create an all-taxon dump


        // $this->property['taxon name'] = "P225";
        // $this->property['taxon rank'] = "P105";

        $this->trans['editors']['en'] = "Wikipedia authors and editors";
        $this->trans['editors']['de'] = "Wikipedia Autoren und Herausgeber";
        $this->trans['editors']['es'] = "Autores y editores de Wikipedia";
        
    }

    function get_all_taxa()
    {
        self::create_all_taxon_dump(); exit;
        
        self::initialize_files();
        self::parse_wiki_data_json();
        self::add_parent_entries();
        $this->archive_builder->finalize(TRUE);
        unlink($this->TEMP_FILE_PATH);
    }
    
    private function add_parent_entries()
    {
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
    
    private function initialize_files()
    {
        $this->TEMP_FILE_PATH = temp_filepath();
        if(!($f = Functions::file_open($this->TEMP_FILE_PATH, "w"))) return;
        fclose($f);
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

    private function create_all_taxon_dump() // utility to create an all-taxon dump
    {
        if(!($f = Functions::file_open($this->wiki_data_taxa_json, "w"))) return;
        // fwrite($f, "{");
        
        $e = 0; $i = 0; $k = 0;
        foreach(new FileIterator($this->wiki_data_json) as $line_number => $row)
        {
            $k++;
            if(($k % 20000) == 0) echo " $k";
            
            if(stripos($row, "Q16521") !== false) //string is found -- "taxon"
            {
                $e++;
                fwrite($f, $row."\n");
                // if($e == 3) break;
            }
            else $i++;
        }
        // fwrite($f, "}");
        fclose($f);
        
        echo "\ntaxa  wikis: [$e]\n";
        echo "\nnon-taxa  wikis: [$i]\n";
        exit;
    }

    private function parse_wiki_data_json()
    {
        $i = 0; $j = 0;
        $k = 0; $m = 4624000; //only for breakdown when caching
        foreach(new FileIterator($this->wiki_data_json) as $line_number => $row)
        {
            /* breakdown when caching:
            $k++; echo " $k";
            $cont = false;
            // if($k >=  1   && $k < $m) $cont = true;
            // if($k >=  $m && $k < $m*2) $cont = true;
            // if($k >=  $m*2 && $k < $m*3) $cont = true;
            // if($k >=  $m*3 && $k < $m*4) $cont = true;
            if($k >=  $m*4 && $k < $m*5) $cont = true;
            if(!$cont) continue;
            */

            if(stripos($row, "Q16521") !== false) //string is found -- "taxon"
            {
                /* remove the last char which is "," a comma */
                $row = substr($row,0,strlen($row)-1); //removes last char which is "," a comma
                $arr = json_decode($row);

                /* for debug start ======================
                $arr = self::get_object('Q36611');
                $arr = $arr->entities->Q36611;
                for debug end ======================== */
                
                if(is_object($arr))
                {
                    $rek = array();
                     // /*
                     $rek['taxon_id'] = $arr->id;
                     if($rek['taxon'] = self::get_taxon_name($arr->claims))
                     {
                         // /* normal operation
                         if($rek['sitelinks'] = self::get_taxon_sitelinks_by_lang($arr->sitelinks)) //if true then create DwCA for it
                         {
                             // print_r($arr); exit; //debug
                             
                             $i++; 
                             $rek['rank'] = self::get_taxon_rank($arr->claims);
                             $rek['parent'] = self::get_taxon_parent($arr->claims);
                             $rek = self::get_other_info($rek);
                             self::create_archive($rek);
                             self::save_ancestry_to_temp($rek['parent']);
                             print_r($rek); //exit;
                             
                         }
                         print_r($rek); //exit;
                         
                         
                         // break;              //debug - process just 1 rec
                         if($i >= 10) break;  //debug
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
        $t->taxonID                 = $rec['taxon_id'];
        $t->scientificName          = $rec['taxon'];
        $t->taxonRank               = $rec['rank'];
        $t->parentNameUsageID       = $rec['parent']['id'];
        $t->source                  = $rec['other']['permalink'];

        if(!isset($this->taxon_ids[$t->taxonID]))
        {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }

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
            if($html = Functions::lookup_with_cache($url, $this->download_options))
            {
                $html = $func->prepare_wiki_for_parsing($html, $domain_name);
                $rek['other']['title'] = $title;
                // $rek['other']['comprehensive_desc'] = $func->get_comprehensive_desc($html);
                $rek['other']['comprehensive_desc'] = "elix elix elicha elicha";
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

    private function get_taxon_rank($claims)
    {
        if($id = (string) @$claims->P105[0]->mainsnak->datavalue->value->id)
        {
            return self::lookup_value($id);
        }
        return false;
    }

    private function get_taxon_parent($claims)
    {
        $parent = array();
        if($id = (string) @$claims->P171[0]->mainsnak->datavalue->value->id)
        {
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
    
    private function lookup_value($id)
    {
        if($obj = self::get_object($id))
        {
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

    // private function checkaddslashes($str){       
    //     if(strpos(str_replace("\'",""," $str"),"'")!=false)
    //         return addslashes($str);
    //     else
    //         return $str;
    // }

}
?>