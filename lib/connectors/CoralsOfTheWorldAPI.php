<?php
namespace php_active_record;
// connector: [corals]
class CoralsOfTheWorldAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->domain = "http://coral.aims.gov.au";
        $this->species_list = $this->domain . "/info/factsheets.jsp";
        $this->download_options = array("download_wait_time" => 1000000, "timeout" => 1800, "download_attempts" => 1, "expire_seconds" => 5184000, "delay_in_minutes" => 1);
        $this->debug = array();
    }

    function get_all_taxa()
    {
        $taxa = self::get_taxa_list();
        $total = count($taxa);
        $i = 0;
        foreach($taxa as $taxon)
        {
            $i++;
            if(($i % 50) == 0) echo "\n $i of $total - " . $taxon["sciname"] . "\n";
            if($html = Functions::lookup_with_cache($this->domain . $taxon["source"], $this->download_options))
            {
                $rec = self::parse_html($html, $taxon);
                $taxon["authorship"] = $rec["authorship"];
                self::create_instances_from_taxon_object($taxon, $rec["texts"]);
                self::get_objects($taxon, $rec["images"], "image");
                $texts = self::arrange_texts($rec["texts"], $taxon["taxon_id"]);
                self::get_objects($taxon, $texts, "text");
            }
            // break; //debug
        }
        $this->archive_builder->finalize(TRUE);
        // print_r($this->debug);
    }

    private function arrange_texts($texts, $taxon_id)
    {
        $final = array();
        $texts["Taxonomic note"] = "";
        foreach($texts as $topic => $desc)
        {
            if($desc) $final[] = array("identifier" => $taxon_id . "_$topic", "description" => $desc, "subject" => self::get_subject($topic));
        }
        return $final;
    }
    
    private function get_subject($topic)
    {
        switch($topic)
        {
            case "Colour":          return "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology";
            case "Habitat":         return "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat";
            case "Abundance":       return "http://rs.tdwg.org/ontology/voc/SPMInfoItems#PopulationBiology";
            case "Similar species": return "http://rs.tdwg.org/ontology/voc/SPMInfoItems#LookAlikes";
            case "GenDesc":         return "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology";
        }
    }
    
    private function parse_html($html, $taxon)
    {
        $rec = array();
        // for authorship
        if(preg_match("/<p class=\"surname\">(.*?)<\/p>/ims", $html, $arr)) $rec["authorship"] = trim($arr[1]);
        // for the different topics
        $texts = array();
        $topics = array("Colour", "Habitat", "Abundance", "Similar species", "Taxonomic note"); // e.g. Taxonomic note:
        foreach($topics as $topic)
        {
            if(preg_match("/<b>" . $topic . ":<\/b>(.*?)<\/p>/ims", $html, $arr)) $texts[$topic] = $arr[1];
            if($topic == "Similar species")
            {
                $desc = str_ireplace(' class="fullname" ', " ", $texts[$topic]);
                $desc = str_ireplace('href="/factsheet', 'href="' . $this->domain . '/factsheet', $desc);
                $desc = str_replace(array("\n", "\t", "\r", chr(9), chr(10), chr(13), "   "), "", $desc);
                $texts[$topic] = trim($desc);
            }
        }
        // for the general description - enclosed by <p></p>
        if(preg_match("/<p class=\"surname\">(.*?)<b>Colour:<\/b>/ims", $html, $arr))
        {
            if(preg_match("/<p>(.*?)<\/p>/ims", $arr[1], $arr)) $texts["GenDesc"] = $arr[1];
        }
        $texts = array_map('trim', $texts);
        $rec["texts"] = $texts;
        // for images
        $images = array();
        if(preg_match_all("/<a class=\"fancybox\" rel=\"group\" (.*?)<\/a>/ims", $html, $arr))
        {
            foreach($arr[1] as $line)
            {
                $media_url = false;
                if(preg_match("/href=\"(.*?)\"/ims", $line, $arr2)) $media_url = $arr2[1];
                if(preg_match("/alt='(.*?)'/ims", $line, $arr2))
                {
                    $caption = $arr2[1];
                    $photographer = self::get_photographer($caption);
                    $this->debug[$photographer] = '';
                }
                if($media_url)
                {
                    $parts = pathinfo($media_url);
                    $images[] = array("identifier" => $parts["filename"], "media_url" => $media_url, "description" => $caption, "photographer" => $photographer);
                }
            }
        }
        $rec["images"] = $images;
        return $rec;
    }
    
    private function get_photographer($string)
    {
        $photographer = "";
        $parts = explode(". ", $string);
        $parts = array_map('trim', $parts);
        foreach($parts as $part)
        {
            $words = explode(" ", $part);
            $cont = false;
            foreach($words as $word)
            {
                if(ctype_upper(substr($word,0,1))) $cont = true;
                else
                {
                    $cont = false;
                    break;
                }
            }
            if($cont) $photographer = trim($part);
        }
        // remove "." if last char in photographer
        if(substr($photographer, -1) == ".") $photographer = substr($photographer, 0, strlen($photographer)-1);
        
        // manual checking, this is needed bec. there is no clear distinction for photographer name
        $remove_if_this_exists_in_photographer = array("Australia", "Indonesia", "Japan", "Guam", "Philippines", "New ", "Oman", "Vietnam", "Ocean", " Sea", "Africa", "Micronesia", "Caribbean", "Kuwait", "Sri Lanka", " Islands", " Gulf", "Showing", "Surface", " USA", "Polynesia", "Vanuatu", "Madagascar", "Tanzania", "Palau", "Tahiti", "Fiji", "Mediterranean", "Hawaii", "Thailand", "Brazil", "Taiwan", "Mesenterina");
        foreach($remove_if_this_exists_in_photographer as $word)
        {
            if(is_numeric(stripos($photographer, $word)))
            {
                $photographer = "";
                break;
            }
        }
        return $photographer;
    }
    
    private function get_taxa_list()
    {
        $taxa = array();
        if($html = Functions::lookup_with_cache($this->species_list, $this->download_options))
        {
            if(preg_match_all("/<a class=\"fullname\"(.*?)<\/a>/ims", $html, $arr))
            {
                $rows = array_map('trim', $arr[1]);
                foreach($rows as $row)
                {
                    if(preg_match("/speciesCode=(.*?)\"/ims", $row, $arr))  $id     = $arr[1];
                    if(preg_match("/\">(.*?)xxx/ims", $row."xxx", $arr))    $name   = trim($arr[1]);
                    if(preg_match("/href=\"(.*?)\"/ims", $row, $arr))       $source = $arr[1];
                    $taxa[] = array("taxon_id" => $id, "sciname" => $name, "source" => $source);
                }
            }
        }
        return $taxa;
    }

    private function get_objects($taxon, $records, $type)
    {
        foreach($records as $rec)
        {
            $mr = new \eol_schema\MediaResource();
            if($type == "text")
            {
                $mr->type               = 'http://purl.org/dc/dcmitype/Text';
                $mr->format             = 'text/html';
                $mr->CVterm             = $rec["subject"];
                $mr->bibliographicCitation = "Australian Institute of Marine Science, (" . date("Y") . "). AIMS Coral Fact Sheets - " . $taxon["sciname"] . 
                ". Viewed " . date("d M Y") . ". http://coral.aims.gov.au/factsheet.jsp?speciesCode=" . $taxon["taxon_id"];
            }
            elseif($type == "image")
            {
                $mr->type               = 'http://purl.org/dc/dcmitype/StillImage';
                $mr->format             = Functions::get_mimetype($rec["media_url"]);
                $mr->accessURI          = $rec["media_url"];
                $mr->title              = "";
            }
            $mr->taxonID                = $taxon["taxon_id"];
            $mr->identifier             = $rec["identifier"];
            $mr->language               = 'en';
            $mr->furtherInformationURL  = $this->domain . $taxon["source"];
            $mr->description            = $rec["description"];
            $mr->UsageTerms             = 'http://creativecommons.org/licenses/by-nc/3.0/';
            $mr->Owner                  = @$rec["photographer"] ? $rec["photographer"] : "Australian Institute of Marine Science";
            
            if($val = @$rec["photographer"])
            {
                $agent_ids = self::create_agent($val);
                if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
            }
            
            if(!isset($this->object_ids[$mr->identifier]))
            {
                $this->object_ids[$mr->identifier] = 1;
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }

    private function create_agent($agent)
    {
        $agent_ids = array();
        $r = new \eol_schema\Agent();
        $r->term_name = $agent;
        $r->agentRole = 'photographer';
        $r->identifier = md5("$agent|" . $r->agentRole);
        // $r->term_homepage = '';
        $agent_ids[] = $r->identifier;
        if(!isset($this->resource_agent_ids[$r->identifier]))
        {
           $this->resource_agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }

    private function create_instances_from_taxon_object($rec, $texts)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec["taxon_id"];
        $taxon->scientificName              = $rec["sciname"];
        $taxon->scientificNameAuthorship    = @$rec["authorship"];
        $taxon->furtherInformationURL       = $this->domain . $rec["source"];
        if($val = @$texts["Taxonomic note"]) $taxon->taxonRemarks = $val;
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxon_ids[$taxon->taxonID] = 1;
            $this->archive_builder->write_object_to_file($taxon);
        }
    }

}
?>