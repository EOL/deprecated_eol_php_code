<?php
namespace php_active_record;
/* connector: [35]
   DATA-1590 Please update STRI Neotropical Fishes resource source links
*/
class STRIorigAPI
{
    function __construct($folder)
    {
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 1);
        // $this->download_options["expire_seconds"] = false; // "expire_seconds" -- false => won't expire; 0 => expires now
        $this->domain             = "http://biogeodb.stri.si.edu";
        $this->image_list_page    = $this->domain . "/caribbean/en/contributors/images";
        $this->image_summary_page = $this->domain . "/caribbean/en/pages/random/";
        $this->taxa_list_page     = $this->domain . "/caribbean/en/thefishes/systematic";
        $this->taxa_list_page     = $this->domain . "/caribbean/en/thefishes/systematic?sort=Alphabetic&display=Scientific";
        $this->biogeodb_taxon_summary_page          = $this->domain . "/caribbean/en/thefishes/species/";
        $this->neotropicalfishes_taxon_summary_page = "http://neotropicalfishes.myspecies.info/taxonomy/term/";
    }

    function process_xml($params)
    {
        $all_taxa = self::get_taxa_list_from_biogeodb();
        $this->taxa_id_list = array_merge($all_taxa, self::get_taxa_list_from_myspecies());
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($params["eol_xml_file"], $params["filename"], array("timeout" => 7200, "expire_seconds" => false));
        print_r($paths);
        $params["path"] = $paths["temp_dir"];
        $xml = self::update_xml($params);
        recursive_rmdir($paths["temp_dir"]); // remove temp dir
        return $xml;
    }

    private function update_xml($params)
    {
        $file = $params["path"] . $params["filename"];
        echo "\n[$file]\n";
        $contents = file_get_contents($file);
        $xml = simplexml_load_string($contents);
        foreach($xml->taxon as $t)
        {
            $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
            $t_dwc      = $t->children("http://rs.tdwg.org/dwc/dwcore/");
            $sciname = (string) $t_dwc->ScientificName;
            if(isset($this->taxa_id_list["bio"][$sciname]))     $t_dc->source = $this->biogeodb_taxon_summary_page . $this->taxa_id_list["bio"][$sciname];
            elseif(isset($this->taxa_id_list["neo"][$sciname])) $t_dc->source = $this->neotropicalfishes_taxon_summary_page . $this->taxa_id_list["neo"][$sciname];
            else
            {
                $temp = explode(" ", $sciname);
                if($temp[1] == "species")
                {
                    $sciname = $temp[0];
                    if(isset($this->taxa_id_list["bio"][$sciname]))     $t_dc->source = $this->biogeodb_taxon_summary_page . $this->taxa_id_list["bio"][$sciname];
                    elseif(isset($this->taxa_id_list["neo"][$sciname])) $t_dc->source = $this->neotropicalfishes_taxon_summary_page . $this->taxa_id_list["neo"][$sciname];
                    else $t_dc->source = "";
                }
                else $t_dc->source = "http://biogeodb.stri.si.edu/caribbean/en/pages"; //home page of the new site
            }
            
            if($objects = @$t->dataObject)
            {
                foreach($objects as $o)
                {
                    $o_dc = $o->children("http://purl.org/dc/elements/1.1/");
                    if(@$o_dc->source) $o_dc->source = $t_dc->source;
                }
            }
            // break; //debug
        }
        return $xml->asXML();
    }

    private function get_taxa_list_from_myspecies()
    {
        //first get main taxa from myspecies
        $url = "http://neotropicalfishes.myspecies.info/ajaxblocks?blocks=tinytax-6&path=taxonomy/term/2423";
        // $url = "http://neotropicalfishes.myspecies.info/tinytax/get/15942"; //debug
        $records = self::get_id_list_from_myspecies($url);
        $recs_to_process = $records;
        for($i=1; $i<=20; $i++)
        {
            echo "\n[$i]";
            $recs = self::loop_node($recs_to_process);
            echo " - " . count($recs);
            if($recs) $records = array_merge($records, $recs);
            $recs_to_process = $recs;
        }
        //final array assignment
        print "\ntotal: " . count($records);
        $final = array();
        foreach($records as $rec) $final["neo"][$rec["sciname"]] = $rec["id"];
        return $final;
    }

    private function loop_node($records)
    {
        $records2 = array();
        foreach($records as $rec)
        {
            // echo "\n" . $rec["sciname"];
            if($rec["more"])
            {
                if($val = self::get_id_list_from_myspecies("http://neotropicalfishes.myspecies.info/tinytax/get/" . $rec["id"])) $records2 = array_merge($records2, $val);
            }
        }
        return $records2;
    }
    
    private function get_id_list_from_myspecies($url)
    {
        $records = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match_all("/id=\\\u0022tinytax\-(.*?)\\\u003C\\\\\/a\\\u003E/ims", $html, $arr))
            {
                // print_r($arr[1]);
                foreach($arr[1] as $line)
                {
                    $pos = strrpos($line, "u003E");
                    $sciname = substr($line, $pos+5, strlen($line));
                    $more = false;
                    if(!$sciname)
                    {
                        if(preg_match_all("/\\\u003E(.*?)\\\/ims", "xxx".$line, $arr2))
                        {
                            $ar = array_map('trim', $arr2[1]);
                            $ar = array_filter($ar);
                            $ar = array_values($ar);
                            $sciname = implode(" ", $ar);
                        }
                    }
                    if(preg_match("/xxx(.*?)\\\u0022/ims", "xxx".$line, $arr2)) $id = $arr2[1];
                    if(is_numeric(strpos($line, "u0022Open"))) $more = true;
                    $records[] = array("id" => $id, "sciname" => $sciname, "more" => $more);
                }
            }
        }
        // if($records) echo " - " . count($records); //print_r($records); //debug
        return $records;
    }
    
    private function get_taxa_list_from_biogeodb()
    {
        $records = array();
        if($html = Functions::lookup_with_cache($this->taxa_list_page, $this->download_options))
        {
            if(preg_match("/<div class=\"results well customscrollbar\">(.*?)<form/ims", $html, $arr))
            {
                if(preg_match_all("/<a (.*?)<\/a>/ims", $arr[1], $arr))
                {
                    foreach($arr[1] as $line)
                    {
                        $id = false; $sciname = false;
                        if(preg_match("/id=\"(.*?)\"/ims", $line, $arr2))       $id      = $arr2[1];
                        if(preg_match("/'>(.*?)xxx/ims", $line."xxx", $arr2))   $sciname = $arr2[1];
                        if($id && $sciname) $records["bio"][$sciname] = $id;
                    }
                }
            }
        }
        return $records;
    }

}
?>