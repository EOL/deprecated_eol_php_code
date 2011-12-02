<?php
namespace php_active_record;
/* connector: 123 */
define("SERVICE_URL", "http://www.aquamaps.org/webservice/getAMap.php?");
define("FISHBASE_URL", "http://www.fishbase.us/summary/speciessummary.php?id=");
define("SEALIFEBASE_URL", "http://www.sealifebase.org/summary/speciessummary.php?id=");
define("MAP_RESIZER_URL", "http://www.aquamaps.org/imagethumb/workimagethumb.php?s=");
define("CACHED_MAPS_URL", "http://www.aquamaps.org/imagethumb/cached_maps");

class AquamapsAPIv2
{
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $path = DOC_ROOT . "/update_resources/connectors/files/AquaMaps/";
        $urls = array( 0  => array( "path" => $path . "aquamaps_species_list.XML"  , "active" => 1),  // all 8k species
                       1  => array( "path" => $path . "aquamaps_species_list2.XML" , "active" => 0)   // test just 3 species
                     );
        foreach($urls as $url)
        {
            if($url["active"])
            {
                $arr = self::get_aquamaps_taxa($url["path"], $used_collection_ids);
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];            
                print "\n page_taxa count: " . count($page_taxa) . "\n\n";
                if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            }
        }
        return $all_taxa;
    }

    public static function get_aquamaps_taxa($url, $used_collection_ids)
    {
        $response = self::parse_xml($url);//this will output the raw (but structured) output from the external service
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            $used_collection_ids[$rec["sciname"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    function parse_xml($url)
    {
        $arr_scraped=array();
        $xml = Functions::get_hashed_response($url);
        $ctr = 0;
        $total = sizeof($xml->RECORD);
        foreach($xml->RECORD as $rec)
        {
            $ctr++;
            print "\n $ctr of $total";
            if(substr($rec->SPECIESID, 0, 3)=="Fis") $source_dbase_link = "<a target='$rec->SpecCode' href='" . FISHBASE_URL . $rec->SpecCode . "'>FishBase</a>";
            else                                     $source_dbase_link = "<a target='$rec->SpecCode' href='" . SEALIFEBASE_URL . $rec->SpecCode . "'>SeaLifeBase</a>";
            //start distribution
            $genus = $rec->Genus;
            $species = $rec->Species;
            $arr_objects = self::get_aquamaps($genus, $species, $source_dbase_link);
            if(!$arr_objects) continue;
            if(preg_match("/&SpecID=(.*?)(&?$|&)/ims", $arr_objects[0]["source"], $matches)) {$species_id = trim($matches[1]);} //ends with & or end of string
            else $species_id = "";
            print "\n [$genus $species] [$species_id]";
            $arr_scraped[] = array("id"           => $ctr,
                                   "identifier"   => $species_id,
                                   "sciname"      => $rec->Genus . ' ' . $rec->Species,
                                   "genus"        => $rec->Genus,
                                   "family"       => $rec->Family,
                                   "order"        => $rec->Order,
                                   "class"        => $rec->Class,
                                   "phylum"       => $rec->Phylum,
                                   "kingdom"      => $rec->Kingdom,
                                   "photos"       => array(),
                                   "dc_source"    => $arr_objects[0]["source"],
                                   "data_objects" => $arr_objects
                                  );
        }
        return $arr_scraped;
    }

    function get_aquamaps($genus, $species, $source_dbase_link)
    {
        $param = "genus=" . $genus . "&species=" . $species;
        $fn = SERVICE_URL . $param;
        $xml = Functions::get_hashed_response($fn);
        $html = $xml->section_body;
        if($html == "")
        {
            print "\nNo AquaMaps - $genus $species";
            return array();
        }

        if(is_numeric(stripos($html, "has not yet been reviewed"))) $review = "un-reviewed";
        else                                                        $review = "reviewed";

        if(preg_match("/href=\'http:\/\/(.*?)\'>/ims", $html, $matches)) {$sourceURL = "http://" . trim($matches[1]);}
        else                                                              $sourceURL = "";
        $attribution = "$source_dbase_link <a target='aquamaps' href='http://www.aquamaps.org'>AquaMaps</a> ";
        if(preg_match("/Data sources:(.*?)<\/font><\/td>/ims", $html, $matches)){$attribution .= trim($matches[1]) . "";}
        $attribution = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB","\xA0"), '', $attribution);
        $attribution = "Data sources: " . $attribution;
        /* http://www.aquamaps.org/imagethumb/file_destination/exp_8_pic_ITS-180469.jpg */

        $maps = array();
        //============================================================================================
        $m2050 = "";
        if(preg_match("/\/2050\/(.*?)&quot;/ims", $html, $matches))
        {   $m2050 = trim($matches[1]) . "";
            $m2050 = CACHED_MAPS_URL . "/2050/" . $m2050;
            print "\n 2050: $m2050";
            $description = "<a target='am $genus $species' href='$sourceURL'>Year 2050 range</a>";
            $description .= self::additional_string($genus, $species, $review, $attribution);
            $title = "AquaMaps for $genus $species (Year 2050 range)";            
            $maps[] = array("description" => $description, "identifier" => $genus . "_" . $species . "_2050", "src" => $m2050, "title" => $title);
        }
        //============================================================================================
        $pointmap = "";
        if(preg_match("/\/pointmap\/(.*?)&quot;/ims", $html, $matches))
        {
            $pointmap = trim($matches[1]) . "";
            $pointmap = CACHED_MAPS_URL . "/pointmap/" . $pointmap;
            print "\n pointmap: $pointmap";
            $description = "<a target='am $genus $species' href='$sourceURL'>PointMap</a>";
            $description .= self::additional_string($genus, $species, $review, $attribution, 0);
            $title = "AquaMaps for $genus $species (PointMap)";
            $maps[] = array("description" => $description, "identifier" => $genus . "_" . $species . "_pointmap", "src" => $pointmap, "title" => $title);
        }
        //============================================================================================
        $suitable = "";
        if(preg_match("/\/suitable\/(.*?)&quot;/ims", $html, $matches))
        {
            $suitable = trim($matches[1]) . "";
            $suitable = CACHED_MAPS_URL . "/suitable/" . $suitable;
            print "\n suitable: $suitable";
            $description = "<a target='am $genus $species' href='$sourceURL'>All suitable habitat</a>";
            $description .= self::additional_string($genus, $species, $review, $attribution);
            $title = "AquaMaps for $genus $species (All suitable habitat)";
            $maps[] = array("description" => $description, "identifier" => $genus . "_" . $species . "_suitable", "src" => $suitable, "title" => $title);
        }
        //============================================================================================
        $native_range = "";
        if(preg_match("/=\&quot\;\s*(.*?)\&quot\;\'\>\s*Native range\s*/ims", $html, $matches))
        {   
            $native = trim($matches[1]) . "";
            print "\n native: $native";
            $description = "<a target='am $genus $species' href='$sourceURL'>Native range</a>";
            $description .= self::additional_string($genus, $species, $review, $attribution);
            $title = "AquaMaps for $genus $species (Native range)";
            $maps[] = array("description" => $description, "identifier" => $genus . "_" . $species . "_native", "src" => $native, "title" => $title);
        }
        //============================================================================================
        /*
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\xA0"), '', $str);
        */

        $dataType = "http://purl.org/dc/dcmitype/StillImage";
        $mimeType = "image/jpeg";
        $agent = array();
        /* no clear agent
        $agent[] = array("role" => "author", "homepage" => "http://www.aquamaps.org/main/home.php", "fullName" => "AquaMaps Team");
        */
        $license = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        $location = "";
        $rightsHolder = "AquaMaps";
        $refs = array();
        $subject = "";
        $source = $sourceURL;
        $arr_objects = array();
        foreach($maps as $map)
        {
            $identifier = $map["identifier"];
            $description = $map["description"];
            $mediaURL = $map["src"];
            $title = $map["title"];
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
        }
        return $arr_objects;
    }

    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject)
    {
        return array( "identifier"   => $identifier,
                      "dataType"     => $dataType,
                      "mimeType"     => $mimeType,
                      "title"        => $title,
                      "source"       => $source,
                      "description"  => $description,
                      "mediaURL"     => $mediaURL,
                      "agent"        => $agent,
                      "license"      => $license,
                      "location"     => $location,
                      "rightsHolder" => $rightsHolder,
                      "object_refs"  => $refs,
                      "subject"      => $subject,
                      "language"     => "en"
                    );
    }

    private function additional_string($genus, $species, $review, $attribution, $legend = 1)
    {
        $str = "<br>Computer Generated Map of <i>$genus $species</i>";
        if($legend) $str .= " ($review)<br><img src='http://www.aquamaps.org/pic/probability1.gif'>";
        $str .= "<br> $attribution";
        return $str;
    }

}
?>