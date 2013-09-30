<?php
namespace php_active_record;
// connector: [683] formerly 661
class DipteraCentralAmericaAPI
{
    function __construct($folder)
    {
        $this->domain = "http://www.phorid.net/diptera/";
        $this->taxa_list_url = $this->domain . "diptera_index.html";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->do_ids = array();
    }

    function get_all_taxa()
    {
        if($records = self::parse_html())
        {
            $i = 0;
            $total = count($records);
            echo "\n total records: $total";
            foreach($records as $taxon => $rec)
            {
                $i++;
                echo "\n $i of $total: " . $taxon;
                if(isset($rec[0]["image"])) self::prepare_images($taxon, $rec);
                else $this->create_instances_from_taxon_object($taxon, $rec, array());
            }
            $this->create_archive();
        }
    }

    private function prepare_object_refs($connections)
    {
        $reference_ids = array();
        $string = "";
        foreach($connections as $conn)
        {
            if($conn["title"] == "Selected References") $string = $conn["desc"];
        }
        if(preg_match_all("/<li>(.*?)<\/li>/ims", $string, $arr))
        {
            $refs = $arr[1];
            foreach($refs as $ref)
            {
                $ref = (string) trim($ref);
                if(!$ref) continue;
                $r = new \eol_schema\Reference();
                $r->full_reference = $ref;
                $r->identifier = md5($ref);
                $reference_ids[] = $r->identifier;
                if(!in_array($r->identifier, $this->resource_reference_ids))
                {
                   $this->resource_reference_ids[] = $r->identifier;
                   $this->archive_builder->write_object_to_file($r);
                }
            }
        }
        return $reference_ids;
    }

    private function prepare_images($taxon, $images)
    {
        $reference_ids = array();
        $ref_ids = array();
        $agent_ids = array();
        foreach($images as $rec)
        {
            echo "\n - " . $taxon . " - " . $rec['url'];
            $media_url = $rec["image"];
            echo "\n media url: " . $media_url . "\n\n";
            $path_parts = pathinfo($rec["image"]);
            $identifier = (string) $rec["taxon_id"] . "_" . str_replace(" ", "_", $path_parts["basename"]);
            if(in_array($identifier, $this->do_ids)) continue;
            else $this->do_ids[] = $identifier;
            $mr = new \eol_schema\MediaResource();
            if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
            if($agent_ids)      $mr->agentID = implode("; ", $agent_ids);
            $mr->taxonID                = (string) $rec["taxon_id"];
            $mr->identifier             = $identifier;
            $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
            $mr->language               = 'en';
            $mr->format                 = (string) Functions::get_mimetype($media_url);
            $mr->furtherInformationURL  = (string) $rec['url'];
            $mr->accessURI              = (string) $media_url;
            $mr->Owner                  = "";
            $mr->UsageTerms             = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $mr->description            = (string) $rec["caption"];
            $this->archive_builder->write_object_to_file($mr);
            $this->create_instances_from_taxon_object($taxon, $rec, $reference_ids);
        }
    }

    private function parse_html()
    {
        $records = array();
        if($html = Functions::get_remote_file($this->taxa_list_url, array('timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 2)))
        {
            $html = str_ireplace(array(' width="150"', ' align="left"', ' width="300"'), "", $html);
            if(preg_match_all("/<p class=\"FamilyNames\">(.*?)<\/div>/ims", $html, $arr))
            {
                $i = 0;
                foreach($arr[1] as $block)
                {
                    $i++;
                    // if($i != 3) continue; //debug -- to select which block to process, e.g. choosing "Lower Cyclorrhapha families:"
                    if(preg_match("/(.*?)\:/ims", $block, $match)) $group_name = trim($match[1]);
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $block, $match)) 
                    {
                        foreach($match[1] as $line)
                        {
                            $taxon_name = "";
                            $url = "";
                            if(is_numeric(stripos($line, "href=")))
                            {
                                if(preg_match("/>(.*?)</ims", $line, $match)) $taxon_name = trim($match[1]);
                                if(preg_match("/\"(.*?)\"/ims", $line, $match)) $url = trim($match[1]);
                            }
                            else $taxon_name = $line;
                            if($taxon_name != "&nbsp;")
                            {
                                if($url) $records[$taxon_name]["url"] = $this->domain . $url;
                                $records[$taxon_name]["rank"] = "family";
                                $records[$taxon_name]["taxon_id"] = self::get_taxon_id($taxon_name);
                            }
                        }
                    }
                }
            }
        }
        else
        {
            echo ("\n Problem with the remote file: $this->taxa_list_url");
            return false;
        }
        $records = self::get_genera($records);
        return $records;
    }

    private function get_taxon_id($name)
    {
        if(is_numeric(stripos($name, " sp"))) return str_ireplace(" ", "_", $name);
        else return str_ireplace(" ", "_", Functions::canonical_form($name));
    }

    private function get_genera($records)
    {
        $i = 0; $total = count($records);
        echo "\n cumulative total records: $total";
        $image_records = array();
        foreach($records as $taxon => $info)
        {
            $i++;
            echo "\n $i of $total: " . $taxon . "\n";
            // if($i != 4) continue; //debug --- to select which family to process, e.g. choosing "Phoridae" under "Lower Cyclorrhapha families:"
            if($url = @$info["url"]) 
            {
                if($html = Functions::get_remote_file($url, array('download_wait_time' => 3000000, 'timeout' => 240, 'download_attempts' => 2, 'delay_in_minutes' => 2)))
                {
                    //manual adjustment
                    $html = str_ireplace("Microdon Megacephalus", "Microdon megacephalus", $html);

                    $image_records = array_merge($image_records, self::get_images_from_genera_list_page($html, $url, $taxon));
                    /*
                    <div class="DipteraGenera">
                      <p><em>Amphicnephes</em> Loew </p>
                      <p><em>Rivellia</em> Robineau-Desvoidy </p>
                      <p><em>Senopterina</em> Macquart</p>
                    </div>
                    */
                    if(preg_match("/<div class=\"DipteraGenera\">(.*?)<\/div>/ims", $html, $match))
                    {
                        if(preg_match_all("/<p>(.*?)<\/p>/ims", $match[1], $matches))
                        {
                            $k = 0;
                            foreach($matches[1] as $genera)
                            {
                                // start getting images per genera
                                $k++; 
                                // if($k != 1) continue; //debug -- to select what row, which genera to get image from
                                if(preg_match("/openBrWindow\(\'(.*?)\'/ims", $genera, $arr))
                                {
                                    $image_page_url = $arr[1];
                                    $path_parts = pathinfo($url);
                                    $image_page_url = $path_parts["dirname"] . "/" . $image_page_url;
                                    echo("\n image_page_url: [$image_page_url] \n ");
                                    
                                    if($popup_page = Functions::get_remote_file($image_page_url, array('download_wait_time' => 3000000, 'timeout' => 240, 'download_attempts' => 2, 'delay_in_minutes' => 2)))
                                    {
                                        $records = self::scrape_image_info($popup_page, $records, $image_page_url, $taxon);
                                    }
                                }

                                // start getting each genera name
                                $genera = trim(strip_tags($genera));
                                if(!preg_match("/(Undescribed|undet)/i", $genera))
                                {
                                    $records[$genera]["url"] = $url;
                                    $records[$genera]["rank"] = "genus";
                                    $records[$genera]["family"] = $taxon;
                                    $records[$genera]["taxon_id"] = self::get_taxon_id($genera);
                                }
                            }
                        }
                        else echo "\n\n alert: investigate 01 - no genera list detected: $url \n\n";
                    }
                }
            }
            // if($i >= 1) break; //debug -- limit the no. of families
        }
        $records = array_merge($records, $image_records);
        return $records;
    }

    private function get_images_from_genera_list_page($html, $url, $family)
    {
        /*
        <div class="DipteraImage">
            <img src="tabanidae_image1.jpg" width="400" height="282" alt="Tabanus albocirculas" />
                <p class="PhotoLabels"><em>Tabanus albocirculas</em> Hine 1907, Costa Rica: La Selva Biological Station</p>
                <p class="PhotoLabels">&nbsp;</p>
            <img src="tabanidae_image2.jpg" width="400" height="304" alt="Chlorotabanus mexicanus" />
                <p class="PhotoLabels"><em>Chlorotabanus mexicanus</em> (Linnaeus 1758), Costa Rica: 29 km W Tortuguero</p>
        </div>
        <div class="DipteraImage"><img src="ptychopteridae_image.jpg" width="400" height="293" alt="Ptychoptera townesi" />
        <span class="PhotoLabels"><em>Ptychoptera townesi</em> Alexander 1943, USA: California: 4mi SW Stirling City</span>
        </div>
        */
        /*
        <div class="DipteraImage"><img src="pseudopomyzidae_image.jpg" width="400" height="278" alt="undet. Pseudopomyzidae" />
           <p class="PhotoLabels">undet. Pseudopomyzidae, Costa Rica: Albergue de Heliconia</p>
         </div>        
        */
        /*
        <div class="DipteraImage">
            <img src="syrphidae_image1.jpg" width="400" height="366" alt="Microdon megacephalus" /><span class="PhotoLabels"><em>Microdon Megacephalus</em> 
            Shannon 1929, Costa Rica: Santa Rosa NP</span>
            <p>&nbsp;</p>
          <img src="syrphidae_image2.jpg" width="400" height="314" alt="Ornidia obesa" /><span class="PhotoLabels"><em>Ornidia obesa</em> (Fabricius 1775), 
          Mexico: hills west of Fortin de las Flores </span></div>
        */
        $records = array();
        if(preg_match("/<div class=\"DipteraImage\">(.*?)<\/div>/ims", $html, $match)) $records = self::scrape_image_info($match[1], $records, $url, $family);
        return $records;
    }

    private function scrape_image_info($match, $records, $url, $family)
    {
        $match = str_ireplace("<p>&nbsp;</p>", "", $match);
        if(preg_match_all("/<img src=(.*?)<\/p>/ims", $match, $matches) || preg_match_all("/<img src=(.*?)<\/span>/ims", $match, $matches))
        {
            foreach($matches[1] as $line)
            {
                $image = "";
                $taxon = "";
                $caption = "";
                $rank = "";
                if(preg_match("/\"(.*?)\"/ims", $line, $match))
                {
                    $image = $match[1];
                    $path_parts = pathinfo($url);
                    $image = $path_parts["dirname"] . "/" . $image;
                }
                $line .= "xxx";
                if(preg_match("/class=\"PhotoLabels\">(.*?)xxx/ims", $line, $match))
                {
                    $caption = trim(strip_tags($match[1], "<em><i>"));
                    $caption = str_ireplace(array("\n", "\r", "&nbsp;"), " ", $caption);
                    $taxon = explode(",", $caption);
                    $taxon = strip_tags($taxon[0]);
                    $taxon = trim(str_ireplace(array("undet."), "", $taxon));
                }
                if($taxon == $family)
                {
                    $family = "";
                    $rank = "family";
                }
                $records[$taxon][] = array("url" => $url, "rank" => $rank, "family" => $family, "image" => $image, "caption" => $caption, "taxon_id" => self::get_taxon_id($taxon));
            }
        }
        return $records;
    }
    
    function create_instances_from_taxon_object($sciname, $rec, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID                 = $rec["taxon_id"];
        $taxonRemarks                   = "";
        $taxon->scientificName          = (string) $sciname;
        $taxon->family                  = (string) @$rec['family'];
        $taxon->taxonRank               = (string) $rec['rank'];
        $taxon->furtherInformationURL   = (string) @$rec['url']; // e.g. some families are not hyperlinked
        $this->taxa[$rec["taxon_id"]] = $taxon;
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

}
?>