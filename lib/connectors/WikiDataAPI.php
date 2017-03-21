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
        $this->lookup['Q'] = "https://www.wikidata.org/wiki/";

        // $this->property['taxon name'] = "P225";
        // $this->property['taxon rank'] = "P105";
        
    }

    function get_all_taxa()
    {
        self::parse_wiki_data_json();
        // $this->archive_builder->finalize(TRUE);
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
            
            /* remove the last char which is "," a comma and escape the ' with \' */
            $row = substr($row,0,strlen($row)-1); //removes last char which is "," a comma

            if(stripos($row, "Q16521") !== false) //string is found -- "taxon"
            {
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
                         /* works well
                         if($rek['sitelinks'] = self::get_taxon_sitelinks_by_lang($arr->sitelinks)) //if true then create DwCA for it
                         {
                             $i++; 
                             $rek['rank'] = self::get_taxon_rank($arr->claims);
                             $rek['parent'] = self::get_taxon_parent($arr->claims);
                             $rek = self::get_other_info($rek);
                         }
                         print_r($rek); //exit;
                         */
                         
                         // /* utility: this is to count how many articles per language
                         if($arr = self::get_taxon_sitelinks($arr->sitelinks))
                         {
                             foreach($arr as $a)
                             {
                                 $str = str_replace("wiki", "", $a->site);
                                 // echo " ".$str;
                                 $this->debug[$str]++;
                             }
                             // if($j > 100) break;
                             // exit;
                         }
                         
                         // */
                         
                         
                     }
                     else $j++;
                     // */
                     // exit;
                }
                else 
                {
                    echo "\nnot ok\n";
                    exit;
                }
                
            }
            else $j++;
            
            
        }
        echo "\ntotal taxon wikis = [$i]\n";
        echo "\ntotal non-taxon wikis = [$j]\n";
        print_r($this->debug);
        
    }

    private function get_other_info($rek)
    {
        $func = new WikipediaRegionalAPI(null, $this->language_code);
        
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
                $rek['other']['permalink']        = $func->get_permalink($html);
                $rek['other']['last_modified']    = $func->get_last_modified($html);
                $rek['other']['citation']         = $func->get_citation($rek['other']['title'], $rek['other']['permalink'], $rek['other']['last_modified']);
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
