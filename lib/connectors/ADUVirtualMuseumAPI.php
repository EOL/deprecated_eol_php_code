<?php
namespace php_active_record;
// connector: [716 among others]
class ADUVirtualMuseumAPI
{
    function __construct($folder, $database = null)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->media_ids = array();
        $this->domain = "http://vmus.adu.org.za/";
        $this->Records_per_page = 50; //20;
        
        $this->database = $database;
        /*
        [0] => birdpix
        [1] => bop
        [2] => echinomap
        [3] => safap
        [4] => sabca
        [5] => vimma
        [6] => odonata
        [7] => phown
        [8] => sarca
        [9] => scorpionmap
        [10] => spidermap
        [11] => vith
        */
        $this->download_options = array('resource_id' => 716, 'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'expire_seconds' => 60*60*24*30*2); //2 months expire
        // $this->download_options['expire_seconds'] = 0; // DO NOT DO THIS. The query_id will change value thus the URL will change as well.
    }

    function get_all_taxa()
    {
        if($this->database) $databases = array($this->database);
        else                $databases = self::get_main_groups();
        print_r($databases); //exit;
        // $databases = array("echinomap", "spidermap"); // debug
        $total = count($databases);
        $i = 0;
        foreach($databases as $database) {
            $i++;
            self::process_database($database, "$i of $total");
        }
        $this->create_archive();
    }
    
    private function process_database($database, $remark)
    {
        $path = $this->domain . "/vm_view_db.php?database=" . $database . "&Records_per_page=" . $this->Records_per_page . "&start=";
        $url = $path . "0";
        $details = self::get_queryID_totalNum($url, $database);
        if(!$details) return;

        // /* Forcing to use specific query_id
        $details['query_id'] = 965935;
        // $details['query_id'] = ''; //best option so URL can be cached properly since the query_id is not changing.
        // */
        
        print_r($details); //exit;
        $loops = ceil($details["numRows"] / $this->Records_per_page);
        echo "\n loops: $loops \n";
        $m = $loops/3;
        $start = $this->Records_per_page;
        for($i = 1; $i <= $loops; $i++) {
            if(($i % 1000) == 0) echo "\n $i of $loops [$database] [$remark] \n";
            $url = $path . $start . "&query_id=" . $details["query_id"];
            $start += $this->Records_per_page;
            
            /* breakdown when caching:
            $cont = false;
            // if($i >=  1    && $i < $m) $cont = true;
            // if($i >=  $m   && $i < $m*2) $cont = true;
            if($i >=  $m*2 && $i < $m*3) $cont = true;
            if(!$cont) continue;
            */

            if($html = Functions::lookup_with_cache($url, $this->download_options)) self::process_html($html, $database);
            // if($i >= 3) break; // debug
        }
    }

    private function get_queryID_totalNum($url, $database)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match("/\&query_id\=(.*?)\&/ims", $html, $match)) $query_id = $match[1];
            if(preg_match("/\&numRows\=(.*?)\&/ims", $html, $match)) $numRows = $match[1];
            if($query_id && $numRows) {
                self::process_html($html, $database);
                return array("query_id" => $query_id, "numRows" => $numRows);
            }
            else echo "\n investigate: query_id or numRows not available\n";
        }
        else echo "\n investigate: page down [$url]\n";
        return false;
    }
    
    private function process_html($html, $database)
    {
        $records = array();
        $html = str_replace('<table width="700" border="1" cellspacing="5" cellpadding="0" align="center">', "xxxyyy", $html);
        $html = str_replace('<br /><table', "yyyzzz", $html);
        if(preg_match("/xxxyyy(.*?)yyyzzz/ims", $html, $match)) {
            $html = str_replace('<table width="100%" border="0" cellspacing="0" cellpadding="0">', "<table>", $match[1]);
            if(preg_match_all("/<table>(.*?)<\/table>/ims", $html, $match)) {
                foreach($match[1] as $html) {
                    $record = array();
                    $html = strip_tags($html, "<td><img><a>");
                    if(preg_match("/(.*?)identification pending/ims", $html, $match2)) continue;
                    if(preg_match("/alt=\"(.*?)\"/ims", $html, $match2)) {
                        $record["image"] = $match2[1];
                        if(preg_match("/there are(.*?)photos/ims", $html, $match2)) $record["image_count"] = trim($match2[1]);
                        elseif(preg_match("/there is(.*?)photo/ims", $html, $match2)) $record["image_count"] = trim($match2[1]);
                    }
                    if(preg_match("/Species\:(.*?)\\n/ims", $html, $match2)) {
                        $record["species"] = $match2[1];
                        $temp = explode("--", $record["species"]);
                        $record["taxon_id"] = trim($temp[0]);
                        $record["species"] = trim($temp[1]);
                        $record["species"] = trim(str_ireplace(array("kingdom", "phylum", "class", "order", "family", "genus"), "", $record["species"]));
                    }
                    /*
                    Hagen's Sprite (sub sp. tropicanum) -- Damselflies (Coenagrionidae) 
                        (Theraphosidae) 
                                 Long Skimmer -- Dragonflies (Libellulidae) 
                    */
                    if(preg_match("/\&nbsp\;\&nbsp\;(.*?)\\n/ims", $html, $match2)) {
                         $record["vernacular"] = trim(str_ireplace("&nbsp;", "", $match2[1]));
                         $record["vernacular"] = self::remove_parenthesis($record["vernacular"]);
                    }
                    if(preg_match_all("/\((.*?)\)/ims", $html, $match2)) {
                        foreach($match2[1] as $match3) // to get the last parenthesis - which is the family
                        {
                             $record["family"] = trim($match3);
                        }
                        if($record["family"] == $record["species"]) $record["family"] = "";
                    }
                    
                    if(preg_match("/Observer\:(.*?)\\n/ims", $html, $match2)) {
                        $record["observer"] = $match2[1];
                        $temp = explode(";", $record["observer"]);
                        
                        if(count($temp) == 2) {
                            $index2 = 1;
                            $record["observer"] = trim($temp[0]);
                        }
                        elseif(count($temp) > 2) { // multiple observers
                            $index2 = count($temp) - 1;
                            $pos = strrpos($match2[1], ";");
                            $record["observer"] = trim(substr($match2[1], 0, $pos));
                        }
                        
                        $temp = explode(".", $temp[$index2]);
                        $record["date_of_record"] = trim(str_replace("date:", "", $temp[0]));
                        $record["province"] = trim(@$temp[1]);
                    }
                    if(preg_match("/Record status\:(.*?)\\n/ims", $html, $match2)) $record["record_status"] = $match2[1];
                    if(preg_match("/<a href=\"(.*?)\"/ims", $html, $match2)) $record["source_url"] = $match2[1];
                    
                    //start July 10, 2018 - adjustments ----------------------------------------------------------------------------
                    // e.g. http://vmus.adu.org.za//vm_view_record.php?database=vimma&Vm_number=41
                    $temp = $record['image'];
                    $temp = explode('VM Number', $temp);
                    if($val = @$temp[1]) {
                        $record['VM Number'] = trim($temp[1]);
                    }
                    else {
                        if($record['VM Number'] = self::get_vm_number_from_old_source_url($record['source_url'])) {}
                        else {
                            echo "\n$html\n";
                            print_r($record);
                            exit("\nno vm no.\n");
                        }
                    }
                    $record['source_url'] = "http://vmus.adu.org.za/vm_view_record.php?database=$database&Vm_number=".$record['VM Number'];
                    $record['accessURIs'] = self::get_media_urls($record, $database);
                    //end July 10, 2018 - adjustments ----------------------------------------------------------------------------
                    
                    if($record) $records[] = $record;
                }
            }
            else echo "\n investigate 02 process_html() failed. strlen = ".strlen($html)." \n";
        }
        else echo "\n investigate 01 process_html() failed. \n";
        // print_r($records);
        self::create_instances_from_taxon_object($records);
    }
    private function get_vm_number_from_old_source_url($url)
    {
        $url = str_replace("Vm_number=0&", "", $url);
        echo "\n[$url]\n";
        if(preg_match("/Vm_number\=(.*?)\&/ims", $url, $arr)) return $arr[1];
        return false;
    }
    private function get_media_urls($rec, $database)
    {
        $uris = array();
        if(@$rec['image_count']) {
            $str = Functions::format_number_with_leading_zeros($rec['VM Number'], 6);
            //e.g. http://vmus.adu.org.za/vimma/004785-1.jpg
            for($i = 1; $i <= $rec['image_count']; $i++) $uris[] = $this->domain.$database."/".$str."-".$i.".jpg";
        }
        return $uris;
    }
    private function get_main_groups()
    {
        $groups = array();
        if($html = Functions::lookup_with_cache($this->domain, $this->download_options)) {
            if(preg_match_all("/href=\"vm_search\.php(.*?)\"/ims", $html, $match)) {
                foreach($match[1] as $line) {
                    if(preg_match("/database\=(.*?)\&/ims", $line, $match2)) $groups[] = $match2[1];
                }
            }
        }
        else echo "\n investigate: main site is down\n";
        print_r($groups);
        return $groups;
    }

    function create_instances_from_taxon_object($records)
    {
        foreach($records as $rec) {
            $rec = array_map('trim', $rec);
            if(!$rec["species"]) continue; // e.g. first record in echinomap
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = (string) $rec["taxon_id"];
            $taxon->scientificName              = (string) $rec["species"];
            $taxon->family                      = (string) @$rec["family"];
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->taxa[$taxon->taxonID] = $taxon;
                $this->taxon_ids[$taxon->taxonID] = 1;
                if($rec["vernacular"]) self::create_vernacular($rec);
            }
            $description = self::generate_description($rec);
            $source_url = $rec["source_url"];
            if(!@$rec["image_count"]) continue; // means there is no image for this record
            if($rec["observer"]) $agent_ids = self::create_agent($rec["observer"]);
            else $agent_ids = array();
            
            /* old, not used anymore
            for($i = 1; $i <= $rec["image_count"]; $i++) {
                $media_id = str_replace("-1", "-$i", $rec["image"]);
                $media_url = $this->domain . $media_id;
                $media_id = str_ireplace(array(".jpg"), "", $media_id);
                self::create_images($description, $rec["taxon_id"], $media_id, $media_url, array(), $agent_ids, $source_url);
            }
            */
            /*
            [accessURIs] => Array
                            (
                                [0] => http://vmus.adu.org.za/vimma/000088-1.jpg
                                [1] => http://vmus.adu.org.za/vimma/000088-2.jpg
                            )
            */
            foreach($rec['accessURIs'] as $media_url) {
                // print_r(pathinfo($media_url));
                $media_id = pathinfo($media_url, PATHINFO_FILENAME);
                self::create_images($description, $rec["taxon_id"], $media_id, $media_url, array(), $agent_ids, $source_url);
            }
            
            
        }
    }

    private function generate_description($rec)
    {
        $description = "";
        if($rec["observer"])        $description .= "Observer: "        . $rec["observer"] . "<br>";
        if($rec["date_of_record"])  $description .= "Date of record: "  . $rec["date_of_record"] . "<br>";
        if($rec["province"])        $description .= "Province: "        . $rec["province"] . "<br>";
        if($rec["species"])         $description .= "Species name: "    . $rec["species"] . "<br>";
        if($rec["vernacular"])      $description .= "Common name: "     . $rec["vernacular"] . "<br>";
        if($rec["family"])          $description .= "Family: "          . $rec["family"] . "<br>";
        if($rec["record_status"])   $description .= "Record status: "   . $rec["record_status"] . "<br>";
        return $description;
    }

    private function create_images($description, $taxon_id, $media_id, $media_url, $reference_ids, $agent_ids, $source_url)
    {
        if(in_array($media_id, $this->media_ids)) return;
        $this->media_ids[] = $media_id;
        $mr = new \eol_schema\MediaResource();
        if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID                = (string) $taxon_id;
        $mr->identifier             = (string) $media_id;
        $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
        $mr->language               = 'en';
        $mr->format                 = Functions::get_mimetype($media_url);
        $mr->CVterm                 = "";
        $mr->rights                 = "";
        $mr->Owner                  = "Animal Demography Unit " . date("Y") . ". Department of Biological Sciences - University of Cape Town";
        $mr->title                  = "";
        $mr->UsageTerms             = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->description            = (string) $description;
        $mr->accessURI              = $media_url;
        $mr->furtherInformationURL  = $source_url;
        $this->archive_builder->write_object_to_file($mr);
    }

    private function create_agent($agent)
    {
        //manual adjustment
        $agent = str_ireplace("Dave and Catriona Kennedy", "Dave Kennedy; Catriona Kennedy", $agent);
        $agent = str_ireplace("Dave Kennedy. Anne Kennedy", "Dave Kennedy; Anne Kennedy", $agent);
        $agent = str_ireplace("Dave; Anne; Tina;", "Dave Kennedy; Anne Kennedy; Tina Kennedy;", $agent);
        $agent = str_ireplace("Dave Kennedy and Anne Kennedy", "Dave Kennedy; Anne Kennedy", $agent);
        $agent = str_ireplace("Tina Kennedy and Samantha Kennedy", "Tina Kennedy; Samantha Kennedy", $agent);
        $agent = str_ireplace("MacKenzie, Pat and Rodney", "Pat MacKenzie; Rodney MacKenzie", $agent);
        $agent = str_ireplace("Barry and Sue Schultz", "Barry Schultz; Sue Schultz", $agent);
        $agent = str_ireplace("Mark and Tom Darling", "Mark Darling; Tom Darling", $agent);
        $agent = str_ireplace("Tom and Des Darling", "Tom Darling; Des Darling", $agent);
        $agent = str_ireplace("Neal and Elaine Goodes Australia", "Neal Goodes; Elaine Goodes", $agent);
        $agent = str_ireplace("Zaloumis, Alex", "Alex Zaloumis", $agent);
        $agent = str_ireplace("Gerrans, Colin", "Colin Gerrans", $agent);
        $agent = str_ireplace("M. Dobson.", "M. Dobson", $agent);
        $agent = str_ireplace("le Roux E.R. & Wagenaar W.", "le Roux E.R.; Wagenaar W.", $agent);
        $agent = str_ireplace("le Roux E.R. and Goemas W.", "le Roux E.R.; Goemas W.", $agent);
        $agent = str_ireplace("le Roux E.R. & Lottering A.D.J.", "le Roux E.R.; Lottering A.D.J.", $agent);
        $agent = str_ireplace("Rautenbach I.L. & Haacke W.D.", "Rautenbach I.L.; Haacke W.D.", $agent);
        $agent = str_ireplace("Theron J. & du Plessis J.", "Theron J.; du Plessis J.", $agent);

        $agent_ids = array();
        $agents = explode(";", $agent);
        foreach($agents as $agentz) {
            $comma_separated = explode(",", $agentz);
            foreach($comma_separated as $agent) {
                $info = self::parse_agent($agent);
                $agent = $info["agent"];
                $role = $info["role"];
                if($agent) {
                    $r = new \eol_schema\Agent();
                    $r->term_name = $agent;
                    $r->agentRole = $role;
                    $r->identifier = md5($r->term_name . "|" . $r->agentRole);
                    $r->term_homepage = "";
                    $agent_ids[] = $r->identifier;
                    if(!in_array($r->identifier, $this->resource_agent_ids)) {
                       $this->resource_agent_ids[] = $r->identifier;
                       $this->archive_builder->write_object_to_file($r);
                    }
                }
            }
        }
        return array_unique($agent_ids);
    }
    
    private function parse_agent($agent)
    {
        if(preg_match("/\(photo(.*?)\)/ims", $agent, $match)) {
            $role = "photographer";
            $agent = self::remove_parenthesis($agent);
        }
        elseif(preg_match("/photo(.*?)/ims", $agent, $match)) $role = "photographer";
        elseif(preg_match("/picture(.*?)/ims", $agent, $match)) $role = "photographer";
        elseif(preg_match("/who took the pics(.*?)/ims", $agent, $match)) $role = "photographer";
        else $role = "recorder";
        // manual adjustment
        $pos = stripos($agent, " by ");
        if(is_numeric($pos)) $agent = trim(substr($agent, $pos+4, strlen($agent)));
        if(is_numeric(stripos($agent, "guide"))) $agent = "";
        $agent = trim(str_ireplace(array("who took the pics", "submitted on behalf of", "Pictures and info courtesy of", "on behalf of", "Photographs courtesy", "Photographs:", "photos:", "took the photograph", "photographers", "photographer", "photography", "photos", "Photographs", "My self and the owner of the compound"), "", $agent));
        if(in_array($agent, array("Camera trap located on stream bank", "Photographs", "GM"))) $agent = "";
        if(is_numeric($agent)) $agent = "";
        if($agent == "Dave") $agent = "Dave Kennedy";
        if($agent == "Anne") $agent = "Anne Kennedy";
        if($agent == "Tina") $agent = "Tina Kennedy";
        return array("agent" => trim($agent), "role" => $role);
    }

    private function create_vernacular($rec)
    {
        if(!@$rec["vernacular"]) return;
        $vernaculars = explode(";", $rec["vernacular"]);
        foreach($vernaculars as $common_name) {
            if(!trim($common_name)) continue;
            $v = new \eol_schema\VernacularName();
            $v->taxonID = $rec["taxon_id"];
            $v->vernacularName = trim($common_name);
            $v->language = "en";
            $vernacular_id = md5("$v->taxonID|$v->vernacularName|$v->language");
            if(!in_array($vernacular_id, $this->vernacular_name_ids)) {
               $this->vernacular_name_ids[] = $vernacular_id;
               $this->archive_builder->write_object_to_file($v);
            }
        }
    }
    private function create_archive()
    {
        foreach($this->taxa as $t) {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }
    private function remove_parenthesis($string)
    {
        return trim(preg_replace('/\s*\([^)]*\)/', '', $string));
    }
}
?>