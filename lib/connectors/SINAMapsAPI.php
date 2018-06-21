<?php
namespace php_active_record;
// connector: [670]
class SINAMapsAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->media_ids = array();
        $this->sina_domain = "http://entnemdept.ufl.edu/walker/buzz/";
        $this->html_species_list["cricket"] = $this->sina_domain . "cricklist.htm";
        $this->html_species_list["katydid"] = $this->sina_domain . "katylist.htm";
        $this->additional_maps[343][] = $this->sina_domain . "343dm.htm";
        $this->additional_maps[341][] = $this->sina_domain . "341dm.htm";
        $this->additional_maps[484][] = $this->sina_domain . "484mdlc.htm";
        $this->additional_maps[535][] = $this->sina_domain . "535dm2.htm";
        $this->additional_maps[172][] = $this->sina_domain . "172ms.htm";
        $this->additional_maps[191][] = $this->sina_domain . "191dlc.htm";
        $this->additional_maps[141][] = $this->sina_domain . "141m2.htm";
        $this->additional_maps[141][] = $this->sina_domain . "141m3.htm";
        $this->additional_maps[141][] = $this->sina_domain . "141m4.htm";
        
        $this->download_options = array('timeout' => 200000, 'download_attempts' => 2, 'delay_in_minutes' => 0.5, 'expire_seconds' => 60*60*24*25);
        
    }

    function get_all_taxa()
    {
        foreach($this->html_species_list as $key => $html_path) {
            echo "\n $key path: [$html_path]\n";
            self::process_html($html_path);
            sleep(2);
        }
        $this->create_archive();
    }
    
    private function process_html($html_path)
    {
        // <i><A href="362a.htm">Gryllotalpa cultriger</a></i>
        if($html = Functions::lookup_with_cache($html_path, $this->download_options)) {
            if(preg_match_all("/<i><A href=\"(.*?)\"/ims", $html, $arr)) {
                foreach($arr[1] as $string) {
                    $string = str_ireplace("a.htm", "m.htm", $string);
                    // $string = "302m.htm"; //123m.htm 071m.htm 318m.htm //debug
                    $url = $this->sina_domain . $string;
                    $urls = array();
                    $urls[] = $url;
                    $id = intval($string);
                    echo " [$id] ";
                    if(isset($this->additional_maps[$id])) {
                        foreach($this->additional_maps[$id] as $url) $urls[] = $url;
                    }
                    foreach($urls as $url) {
                        if($url == "http://entnemdept.ufl.edu/walker/buzz/636m.htm") $url = "http://entnemdept.ufl.edu/walker/buzz/636dm.htm"; //special
                        if($rec = self::get_map_data($url)) {
                            $parts = pathinfo(@$rec["map"]);
                            $rec["taxon_id"] = intval($parts["filename"]);
                            if(!$rec["taxon_id"]) {
                                echo "\n investigate blank taxon_id [$url]\n";
                                // print_r($parts); 
                                // print_r($rec);
                                // exit;
                                continue;
                            }
                            $rec["source_url"] = $this->sina_domain . Functions::format_number_with_leading_zeros($rec["taxon_id"], 3) . "a.htm";
                            $this->create_instances_from_taxon_object($rec, array());
                            $ref_ids = array();
                            $agent_ids = array();
                            
                            $rec["caption"] = "Version of manually-generated dot map displayed above, showing U.S. and Canadian records, was harvested from SINA on " . date("M-d-Y") . ".<br><br>" . @$rec["caption"];
                            
                            if(@$rec["map"]) self::get_images($rec["sciname"], @$rec["caption"], $rec["taxon_id"], $parts["filename"], $rec["map"], $rec["source_url"], $ref_ids, $agent_ids);
                            if(@$rec["computer_gen_map"]) {
                                $parts = pathinfo($rec["computer_gen_map"]);
                                $ref_ids = array();
                                $agent_ids = array();
                                $caption = $rec["as_of"];
                                if($rec["link_back"]) $caption .= "<br><br>" . 'See also this <a href="' . $rec["link_back"] . '">manually generated dot map</a> showing U.S. and Canadian records, with shaded area showing likely general distribution.';
                                self::get_images($rec["sciname"], $caption, $rec["taxon_id"], $parts["filename"], $rec["computer_gen_map"], $rec["source_url"], $ref_ids, $agent_ids);
                            }
                        }
                        // break; //debug
                    }
                    // break; //debug
                }
            }
            else echo "\n investigate 01 [$html_path]";
        }
        else echo "\n investigate 02 [$html_path]";
    }

    private function get_map_data($url)
    {
        $rec = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            // manual adjustment
            if($url == "http://entnemdept.ufl.edu/walker/buzz/334m.htm") $html = str_ireplace('<div align="center">', '</div><div align="center">', $html);

            if(preg_match("/<b>(.*?)<\/b>/ims", $html, $arr)) $rec["vernacular"] = strip_tags($arr[1]);
            else echo "\n investigate no vernacular [$url]";
            if(preg_match("/<i>(.*?)<\/i>/ims", $html, $arr)) $rec["sciname"] = strip_tags($arr[1]);
            else echo "\n investigate no sciname [$url]";
            if(preg_match_all("/<div align=\"center\">(.*?)<\/div>/ims", $html, $arr)) {
                $temp = $arr[1];
                if(@$temp[1]) $caption = $temp[1];
                elseif(@$temp[0]) $caption = $temp[0]; //http://entnemdept.ufl.edu/walker/buzz/302m.htm
                if(preg_match("/<img src=\"(.*?)\"/ims", $caption, $arr)) {
                    $map_image = $arr[1];
                    $rec["map"] = $this->sina_domain . $map_image;
                }
                else {
                    if($map_image = self::get_map_image_retry($html)) {
                        $rec["map"] = $this->sina_domain . $map_image;
                        echo "\n retry successfull\n";
                    }
                    else {
                        echo "\n investigate no map image [$url]\n";
                        echo "\n investigate retry still no map 1 [$url]\n";
                        return array();
                    }
                }
                $caption = trim(strip_tags($caption, "<br><a>"));
                $caption = str_ireplace(array("\n", chr(13), chr(10), "\t"), "", $caption);
                if(substr($caption, 0, 4) == "<br>") $caption = trim(substr($caption, 4, strlen($caption)));
                $caption = str_ireplace(array("<br>  "), "<br>", $caption);
                $caption = str_ireplace('"> Computer-generated', '">Computer-generated', $caption);
                if    (preg_match("/<a href=\"(.*?)\">Computer-generated/ims", $caption, $arr)) $rec["computer_gen_map"] = $this->sina_domain . $arr[1];
                elseif(preg_match("/<a href=\"(.*?)\">  Computer-generated/ims", $caption, $arr)) $rec["computer_gen_map"] = $this->sina_domain . $arr[1];
                elseif(preg_match("/<a href=\"(.*?)\">County-level distribution map/ims", $caption, $arr)) $rec["computer_gen_map"] = $this->sina_domain . $arr[1];
                // else echo "\n investigate no computer gen map [$url]\n"; acceptable case
                
                //further check for 'computer_gen_map' e.g. http://entnemdept.ufl.edu/walker/buzz/123m.htm or 318m.htm
                if(is_numeric(stripos(@$rec["computer_gen_map"], "href="))) {
                    for($x = 0; $x <= 10; $x++) {
                        if(preg_match("/<a href=\"(.*?)xxx/ims", $rec["computer_gen_map"]."xxx", $arr)) $rec["computer_gen_map"] = $this->sina_domain . $arr[1];
                        else break;
                    }
                }
                
                $caption = str_ireplace('href="', 'href="' . $this->sina_domain, $caption);
                $caption = str_ireplace('Computer-generated distribution map', '<br>See also this computer-generated U.S. distribution map', $caption);
                $rec["caption"] = $caption;
                // echo "\n caption: [$caption]\n";
                $rec["as_of"] = self::get_as_of_date($caption);
            }
            else {
                // e.g. http://entnemdept.ufl.edu/walker/buzz/401m.htm
                // echo "\n investigate no <div> [$url]\n";
                if($map_image = self::get_map_image_retry($html)) {
                    $rec["map"] = $this->sina_domain . $map_image;
                    if(preg_match("/<p>(.*?)\./ims", $html, $arr)) {
                        $caption = strip_tags($arr[1]) . ".";
                        $rec["caption"] = $caption;
                        $rec["as_of"] = self::get_as_of_date($caption);
                    }
                    echo "\n retry successfull\n";
                }
                else {
                    echo "\n investigate retry still no map 2 [$url]\n";
                    return array();
                }
            }
        }
        else echo "\n investigate 03 [$url]";
        $rec["link_back"] = $url;
        return $rec;
    }

    private function get_as_of_date($caption)
    {
        $as_of = "";
        // working but temporarily removed.
        // if(preg_match("/produced in(.*?)from /ims", $caption, $arr))
        // {
        //     $as_of = "Map was generated in Singing Insects of North America in " . trim($arr[1]) . ".";
        // }
        if($as_of) $as_of .= "<br>";
        $as_of .= "Version of computer-generated U.S. distribution map displayed above was harvested from SINA on " . date("M-d-Y") . ".";
        return $as_of;
    }
    
    private function get_map_image_retry($html)
    {
        //<img src="302md.gif"
        if(preg_match_all("/<img src=\"(.*?)\"/ims", $html, $arr)) {
            $exclude = array("blank.gif", "specpage.gif", "nextimag.gif", "previmag.gif");
            $arr = $arr[1];
            $arr = array_diff($arr, $exclude);
            $arr = array_values($arr);
            if($arr[0] && count($arr) == 1) return $arr[0];
        }
        return false;
    }
    
    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $parts = explode(" ", $rec["sciname"]);
        if(count($parts) > 1) $genus = trim($parts[0]);
        else $genus = "";
        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID                     = (string) $rec["taxon_id"];
        $taxon->taxonRank                   = (string) "species";
        $taxon->scientificName              = (string) $rec["sciname"];
        $taxon->vernacularName              = (string) $rec["vernacular"];
        $taxon->genus                       = (string) $genus;
        $taxon->furtherInformationURL       = $rec["source_url"];
        $this->taxa[$taxon->taxonID] = $taxon;
    }

    private function get_object_reference_ids($ref)
    {
        $reference_ids = array();
        $r = new \eol_schema\Reference();
        $r->full_reference = (string) $ref;
        $r->identifier = md5($r->full_reference);
        $reference_ids[] = $r->identifier;
        if(!in_array($r->identifier, $this->resource_reference_ids)) {
           $this->resource_reference_ids[] = $r->identifier;
           $this->archive_builder->write_object_to_file($r);
        }
        return $reference_ids;
    }

    private function get_images($sciname, $description, $taxon_id, $media_id, $media_url, $source_url, $reference_ids, $agent_ids)
    {
        /* this has to be done because there are many maps written in html as jpg but are actually gif. in the site these maps are not showing, meaning typo in html.
        since there is only a handful of jpg maps,  i set all maps to gif */
        $jpeg_maps = array("343dm.jpg", "341dm.jpg", "484ddm.jpg", "535dm2.jpg", "172ms.jpg", "202md.jpg");
        $parts = pathinfo($media_url);
        if(!in_array($parts["basename"], $jpeg_maps)) $media_url = str_ireplace(".jpg", ".gif", $media_url);
        
        if(in_array($media_id, $this->media_ids)) return;
        $this->media_ids[] = $media_id;
        $mr = new \eol_schema\MediaResource();
        if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids)      $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID        = (string) $taxon_id;
        $mr->identifier     = (string) $media_id;
        $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
        $mr->language       = 'en';
        $mr->format         = Functions::get_mimetype($media_url);
        $mr->furtherInformationURL = $source_url;
        $mr->CVterm         = "";
        $mr->Owner          = "";
        $mr->rights         = "";
        $mr->title          = "Distribution of $sciname in North America north of Mexico";
        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc/3.0/";
        $mr->audience       = 'Everyone';
        
        $description = str_ireplace("available on this site", "available on the Singing Insects of North America site", $description);
        $mr->description    = $description;
        $mr->subtype        = "Map";
        $mr->accessURI      = (string) trim($media_url);
        $this->archive_builder->write_object_to_file($mr);
    }
    function create_archive()
    {
        foreach($this->taxa as $t) {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }

}
?>