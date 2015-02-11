<?php
namespace php_active_record;
/* connector: [] DATA-1591 New STRI fish resource. Scrape with photographer name filter */

class STRInewAPI
{
    function __construct($resource_id = false)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 1);
        // $this->download_options["expire_seconds"] = false; // "expire_seconds" -- false => won't expire; 0 => expires now
        $this->domain             = "http://biogeodb.stri.si.edu";
        $this->image_list_page    = $this->domain . "/caribbean/en/contributors/images";
        $this->image_summary_page = $this->domain . "/caribbean/en/pages/random/";
        $this->taxa_list_page     = $this->domain . "/caribbean/en/thefishes/systematic";
        $this->taxa_list_page     = $this->domain . "/caribbean/en/thefishes/systematic?sort=Alphabetic&display=Scientific";
    }

    function get_all_taxa()
    {
        $contributors = self::get_contributors();
        $records = self::get_images($contributors);
        self::process_records($records);
        $this->archive_builder->finalize(TRUE);
    }
    
    private function get_images($contributors)
    {
        $records = array();
        foreach($contributors as $contributor => $path)
        {
            if($html = Functions::lookup_with_cache($path, $this->download_options))
            {
                if(preg_match("/<h3 class=\"black\" style=\"height: 80px; padding-top: 3px;padding-left: 10px;\">(.*?)<script>/ims", $html, $arr))
                {
                    if(preg_match_all("/<div class=\"thumbnail white span2\"(.*?)<p style=\"height: 8px;\">/ims", $arr[1], $arr))
                    {
                        foreach($arr[1] as $line)
                        {
                            $rec = array();
                            if(preg_match("/<img src=\"(.*?)\"/ims", $line, $arr2))   $rec["img"] = $arr2[1];
                            if(preg_match("/<a href=\"(.*?)\"/ims", $line, $arr2))    $rec["url"] = $arr2[1];
                            if(preg_match("/<a href=\"(.*?)<\/a>/ims", $line, $arr2)) $rec["sciname"] = strip_tags('<a href="' . $arr2[1]);
                            if(preg_match("/<\/a>,(.*?)<\/div>/ims", $line, $arr2))   $rec["vernacular"] = trim($arr2[1]);
                            if($rec) $records[$contributor][] = $rec;
                        }
                    }
                }
            }
        }
        return $records;
    }
    
    private function get_contributors()
    {
        $allowed = array("Robertson Ross", "Robertson &", "Bryant Kevin", "Cox Carol & Bob", "Garin James");
        if($html = Functions::lookup_with_cache($this->image_list_page, $this->download_options))
        {
            if(preg_match("/<ul id=\"scbar\"(.*?)<\/ul>/ims", $html, $arr))
            {
                if(preg_match_all("/<a href=(.*?)<\/a>/ims", $arr[1], $arr))
                {
                    $lines = array();
                    foreach($arr[1] as $line)
                    {
                        foreach($allowed as $str)
                        {
                            if(is_numeric(stripos($line, $str)))
                            {
                                $lines[$line] = '';
                                break;
                            }
                        }
                    }
                    $contributors = array();
                    foreach(array_keys($lines) as $line)
                    {
                        if(preg_match("/\"(.*?)\"/ims", $line, $arr)) $path = $this->domain . trim($arr[1]);
                        if(preg_match("/<\/i>(.*?)xxx/ims", $line."xxx", $arr)) $contributors[trim($arr[1])] = $path;
                    }
                    print_r($contributors);
                    return $contributors;
                }
            }
        }
        return false;
    }

    private function process_records($records)
    {
        /*
        [257] => Array
                (
                    [img] => /caribbean/resources/img/images/species/3908_3406.jpg
                    [url] => /caribbean/en/thefishes/species/3908
                    [sciname] => Xyrichtys novacula
                    [vernacular] => Pearly razorfish
                )
        */
        foreach($records as $contributor => $contributor_images)
        {
            foreach($contributor_images as $rec)
            {
                if($rec["sciname"])
                {
                    $taxon = new \eol_schema\Taxon();
                    $taxon->taxonID         = str_replace(" ", "_", $rec["sciname"]);
                    $taxon->scientificName  = $rec["sciname"];
                    if($val = $rec["url"])  $taxon->furtherInformationURL = $this->domain . $val;
                    if(!isset($this->taxa[$taxon->taxonID]))
                    {
                        $this->taxa[$taxon->taxonID] = '';
                        $this->archive_builder->write_object_to_file($taxon);
                    }
                }
                if($rec["img"])
                {
                    $temp       = pathinfo($rec["img"]);
                    $identifier = $temp["filename"];
                    $agent_ids  = self::process_agent($contributor);

                    $mr = new \eol_schema\MediaResource();
                    if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
                    $mr->taxonID                = $taxon->taxonID;
                    $mr->identifier             = $identifier;
                    $mr->type                   = 'http://purl.org/dc/dcmitype/StillImage';
                    $mr->format                 = Functions::get_mimetype($rec["img"]);
                    if(preg_match("/_(.*?)\./ims", $rec["img"], $arr)) $mr->furtherInformationURL  = $this->image_summary_page . $arr[1];
                    $mr->UsageTerms             = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
                    $mr->accessURI              = $this->domain . $rec["img"];
                    $mr->Owner                  = $contributor;
                    $mr->description            = '';
                    $mr->publisher              = '';
                    $mr->CVterm                 = '';
                    $mr->title                  = '';
                    $mr->spatial                = ''; //this is 'location' in attribution in EOL's data_object page
                    if(!isset($this->object_ids[$mr->identifier]))
                    {
                        $this->object_ids[$mr->identifier] = '';
                        $this->archive_builder->write_object_to_file($mr);
                    }
                }
            }//inner loop
        }//outer loop
    }//function end

    private function process_agent($contributor)
    {
        $agent_ids = array();
        $r = new \eol_schema\Agent();
        $r->term_name       = $contributor;
        $r->identifier      = md5($r->term_name);
        $r->agentRole       = "creator";
        $r->term_homepage   = "";
        $agent_ids[] = $r->identifier;
        if(!isset($this->resource_agent_ids[$r->identifier]))
        {
           $this->resource_agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }

}
?>