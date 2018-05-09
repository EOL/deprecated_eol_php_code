<?php
namespace php_active_record;
/* connector: 145 
Partner provided three customized XML. Connector parses all three, assembles the information and generates the EOL XML.
*/
define("NHS_DOC_1", "http://www.rkwalton.com/nhsjumpers_videositemap.xml");
define("NHS_DOC_2", "http://www.rkwalton.com/nhswasps_videositemap.xml");
define("NHS_DOC_3", "http://www.rkwalton.com/nhsskippers_videositemap.xml");

/*
obsolete: "http://www.rkwalton.com/nhswaspstwo_videositemap.xml"
*/

class NaturalHistoryServicesAPI
{
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $domain="http://www.rkwalton.com/";
        $urls = array(
            0 => array("path1" => NHS_DOC_1, "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Arachnida", "order" => "Araneae", "family" => "Salticidae",
                       "taxon_source_url" => $domain . "jump.php"), "active" => 1),  // Salticidae - Jumping Spiders
            1 => array("path1" => NHS_DOC_2, "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Hymenoptera", "family" => "",
                       "taxon_source_url" => $domain . "wasps.php"), "active" => 1),  // Other Hymenopterans
            2 => array("path1" => NHS_DOC_3, "ancestry" => array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta",
                       "taxon_source_url" => $domain . "skippers.php"), "active" => 1),  // butterfly like insects
                     );
        foreach($urls as $url)
        {
            if($url["active"])
            {
                $page_taxa = self::get_NHS_taxa($url["path1"], $url["ancestry"]);
                $all_taxa = array_merge($all_taxa, $page_taxa);
            }
        }
        return $all_taxa;
    }

    public static function get_NHS_taxa($url, $ancestry)
    {
        global $used_collection_ids;
        $response = self::search_collections($url, $ancestry);//this will output the raw (but structured) output from the external service
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;
            $used_collection_ids[$rec["sciname"]] = true;
        }
        return $page_taxa;
    }

    public static function search_collections($url, $ancestry)//this will output the raw (but structured) output from the external service
    {
        $response = self::scrape_species_page($url, $ancestry);
        return $response;
    }

    public static function scrape_species_page($url, $ancestry)
    {
        $arr_acknowledgement = self::prepare_acknowledgement();
        $arr_scraped = array();
        $arr_photos = array();
        $arr_sciname = array();
        $ctr = 0;
        print $url . "\n";
        $xml = Functions::get_hashed_response($url);
        print "taxa count = " . count($xml) . "\n";
        foreach($xml->url as $u)
        {
            $u_video = $u->children("http://www.google.com/schemas/sitemap-video/1.0");
            if($url == "http://www.rkwalton.com/nhsskippers_videositemap.xml") $string = $u_video->video->description;
            else $string = $u_video->video->title;
            $scientific_names = self::get_sciname($string, $url);
            if(!$scientific_names) continue;
            foreach($scientific_names as $sciname)
            {
                $exclude = array("Spiders In The Field", "Spiders and jumping Spiders", "Introduction to Solitary Wasps");
                if (in_array($sciname, $exclude)) continue;
                print "\n" . "[$sciname]";

                $acknowledgement = self::get_acknowledgement($sciname, $arr_acknowledgement);

                $description = "$acknowledgement<br>" . $u_video->video->description;
                if($u_video->video->description) $description .= "<br>Duration: " . $u_video->video->duration . " seconds";
                //object agents
                $agent = array();
                $agent[] = array("role" => "author", "homepage" => "http://www.rkwalton.com", "name" => "Richard K. Walton");
                $arr_photos["$sciname"][] = array("identifier" => $u_video->video->content_loc . "_" . str_replace(" ", "_", $sciname),
                                                  "mediaURL" => str_ireplace(' ','',$u_video->video->content_loc),
                                                  "mimeType" => "video/mp4",
                                                  "dataType" => "http://purl.org/dc/dcmitype/MovingImage",                                  
                                                  "description" => $description,
                                                  "title" => $u_video->video->description,
                                                  "location" => "",
                                                  "dc_source" => $u->loc,
                                                  "thumbnailURL" => $u_video->video->thumbnail_loc,
                                                  "agent" => $agent);
                $arr_sciname[$sciname] = 1;
            }
        }

        foreach(array_keys($arr_sciname) as $sci)
        {
            $arr_scraped[]=array("id" => $ctr,
                                 "kingdom" => $ancestry["kingdom"],
                                 "phylum" => $ancestry["phylum"],
                                 "class" => $ancestry["class"],
                                 "order" => @$ancestry["order"],
                                 "family" => @$ancestry["family"],
                                 "sciname" => $sci,
                                 "dc_source" => $ancestry["taxon_source_url"],
                                 "photos" => $arr_photos["$sci"]
                                );
        }
        return $arr_scraped;
    }

    static function get_sciname($string, $group)
    {
        $sciname = array();
        // special chars
        $string = str_ireplace(" jumping spider video", "", $string);
        $string = str_ireplace("_", " ", $string);

        if($group == NHS_DOC_3) //nhsskippers
        {
            $pos = strripos($string, ':'); // gets string after ':'
            if(is_numeric($pos)) $string = trim(substr($string, $pos+1, strlen($string)));
            $names = explode(",", $string); 
            foreach($names as $name)
            {
                $pos = strripos($name, '-'); // gets string after '-'
                if(is_numeric($pos)) $sciname[] = trim(substr($name, $pos+1, strlen($name)));
            }
        }
        else
        {
            // for nhsjumpers and nhswasps
            $pos = strripos($string, '-'); // gets string after '-'
            if(is_numeric($pos)) $string = trim(substr($string, $pos+1, strlen($string)));
            $pos = stripos($string, ':'); // gets string before ':'
            if(is_numeric($pos)) $string = trim(substr($string, 0, $pos));
            $sciname[] = trim($string);
        }
        return $sciname;
    }

    public static function prepare_acknowledgement()
    {
        require_library('XLSParser');
        $parser = new XLSParser();

        // $spreadsheet = "http://localhost/cp_new/NaturalHistoryServices/Acknowledgments.xls";
        $spreadsheet = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/NaturalHistoryServices/Acknowledgments.xls";
        $temp = Functions::save_remote_file_to_local($spreadsheet, array('cache' => 1, 'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'file_extension' => 'xls', 'expire_seconds' => false));
        $arr = $parser->convert_sheet_to_array($temp);
        
        $acknowledgement = array();
        $k = 0;
        foreach($arr["sciname"] as $sciname) {
            $sci = trim(str_ireplace(".mp4", "", $sciname));
            for ($i = 1; $i <= 3; $i++) {
                if(@$arr["person" . $i][$k])$acknowledgement[$sci][] = @$arr["person" . $i][$k];
            }
            $k++;
        }
        unlink($temp);
        return $acknowledgement;
    }

    public static function get_acknowledgement($sciname, $arr)
    {
        if(!@$arr["$sciname"]) return;
        $acknowledgement = "";
        foreach(@$arr["$sciname"] as $person)
        {
            if($acknowledgement) $acknowledgement .= ", " . $person;
            else $acknowledgement .= $person;
        }
        if($acknowledgement) return "Acknowledgments: " . $acknowledgement . "<br>";
        else return;
    }

    public static function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;
        $taxon["identifier"] = "";
        $taxon["source"] = $rec["dc_source"];
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["kingdom"] = ucfirst(trim($rec["kingdom"]));
        $taxon["phylum"] = ucfirst(trim($rec["phylum"]));
        $taxon["class"] = ucfirst(trim($rec["class"]));
        $taxon["order"] = ucfirst(trim($rec["order"]));
        $taxon["family"] = ucfirst(trim($rec["family"]));
        if(@!$taxon["genus"] && @preg_match("/^([^ ]+) /", ucfirst(trim($rec["sciname"])), $arr)) $taxon["genus"] = $arr[1];
        $photos = $rec["photos"];
        if($photos)
        {
            foreach($photos as $photo)
            {
                $data_object = self::get_data_object($photo);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new \SchemaDataObject($data_object);
            }
        }
        $taxon_object = new \SchemaTaxon($taxon);
        return $taxon_object;
    }

    public static function get_data_object($rec)
    {
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = $rec["identifier"];
        $data_object_parameters["source"] = $rec["dc_source"];
        $data_object_parameters["dataType"] = $rec["dataType"];
        $data_object_parameters["mimeType"] = @$rec["mimeType"];
        $data_object_parameters["mediaURL"] = @$rec["mediaURL"];
        $data_object_parameters["rights"] = "Richard K. Walton - Natural History Services - Online Video";
        $data_object_parameters["rightsHolder"] = "Richard K. Walton";
        $data_object_parameters["title"] = @$rec["title"];
        $data_object_parameters["description"] = utf8_encode($rec["description"]);
        $data_object_parameters["location"] = utf8_encode($rec["location"]);
        $data_object_parameters["license"] = 'http://creativecommons.org/licenses/by-nc/3.0/';
        $data_object_parameters["language"] = 'en';
        $data_object_parameters["thumbnailURL"] = @$rec["thumbnailURL"];
        if(@$rec["subject"])
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = @$rec["subject"];
            $data_object_parameters["subjects"][] = new \SchemaSubject($subjectParameters);
        }
         if(@$rec["agent"])
         {
             $agents = array();
             foreach($rec["agent"] as $a)
             {
                 $agentParameters = array();
                 $agentParameters["role"] = $a["role"];
                 $agentParameters["homepage"] = $a["homepage"];
                 $agentParameters["logoURL"] = "";
                 $agentParameters["fullName"] = $a["name"];
                 $agents[] = new \SchemaAgent($agentParameters);
             }
             $data_object_parameters["agents"] = $agents;
         }
        return $data_object_parameters;
    }

}
?>