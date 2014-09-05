<?php
namespace php_active_record;
/* connector: [90]
This will screen scrape information from: http://www.iucn-tftsg.org/pub-chron/
*/

class ConservationBiologyTurtlesAPI
{
    function __construct($folder)
    {
        $this->turtles_site = "http://www.iucn-tftsg.org/pub-chron/";
        $this->subject = array();
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
    }

    private function prepare_taxa_urls()
    {
        if($html = Functions::lookup_with_cache($this->turtles_site, array('download_wait_time' => 1000000, 'timeout' => 240, 'download_attempts' => 5)))
        {
            if(preg_match_all("/href=\"http:\/\/www.iucn\-tftsg\.org\/cbftt\/toc\-ind\/toc\/(.*?)<\/a>/ims", $html, $arr))
            {
                $records = $arr[1];
                $records = array_filter(array_map('trim', $records)); // will trim all values of the array
                $records = array_filter(array_map('strip_tags', $records));
                $temp = array();
                foreach($records as $record)
                {
                    if(preg_match("/(.*?)\/\">/ims", $record, $arr))
                    {
                        $index = $arr[1];
                        $record = substr($record, stripos($record, '/">') + 3, strlen($record));
                        @$temp[$index] .= $record;
                    }
                }
                //manual delete
                $temp['checklist'] = "";
                $temp = array_filter($temp); // will delete array if value is blank
                $records = array();
                foreach($temp as $key => $value)
                {
                    $records[$key]["sciname"] = $value;
                    $records[$key]["url"] = "http://www.iucn-tftsg.org/cbftt/toc-ind/toc/" . $key;
                }
                $records1 = $records;
            }
            if(preg_match_all("/href=\"\.\.\/cbftt\/(.*?)<\/a>/ims", $html, $arr))
            {
                $records = $arr[1];
                $records = array_filter(array_map('trim', $records)); // will trim all values of the array
                $records = array_filter(array_map('strip_tags', $records));
                $temp = array();
                foreach($records as $record)
                {
                    if(preg_match("/(.*?)\/\">/ims", $record, $arr))
                    {
                        $index = $arr[1];
                        $record = substr($record, stripos($record, '/">') + 3, strlen($record));
                        @$temp[$index] .= $record;
                    }
                }
                //manual delete
                $temp['toc-ind/toc/checklist'] = "";
                $temp = array_filter($temp); // will delete if value is blank
                $records = array();
                foreach($temp as $key => $value)
                {
                    $records[$key]["sciname"] = $value;
                    $records[$key]["url"] = "http://www.iucn-tftsg.org/cbftt/" . $key;
                }
            }

            $records = array_merge($records1, $records);
            //separate name from common names; remove counter 001, 002
            foreach($records as $key => $record)
            {
                $name = $record['sciname'];
                $temp = explode("&ndash;", $name);
                $name = trim($temp[0]);
                $common_names = trim($temp[1]);
                // manual adjustment
                if($key == "mauremys-japonica") $name = "Mauremys japonica (Temminck and Schlegel 1835)";
                elseif($key == "chelodina-longicollis-031") $name = "Chelodina longicollis (Shaw 1794)";
                elseif(in_array($name, array("034Rhinoclemmys nasuta (Boulenger 1902)", "028Aldabrachelys arnoldi (Bour 1982)", "025Melanochelys tricarinata (Blyth 1856)"))) $name = trim(substr($name, 3, strlen($name)));
                elseif($name == "Sternotherus carinatus (Gray 1856)"){}
                else
                {
                    // remove counter 001, 002
                    $name = explode(". ", $name);
                    $name = $name[1];
                }
                $records[$key]['sciname'] = $name;
                $records[$key]['vernacular'] = $common_names;
                $records[$key]['taxonID'] = "iucn_ssc_" . str_ireplace(" ", "_", $name);
            }
            return $records;
        }
        else
        {
            debug("\n\nDown: $this->turtles_site. Connector will terminate.");
            return false;
        }
    }

    function get_all_taxa($records = false)
    {
        self::initialize_subjects();
        if(!$records) $records = self::prepare_taxa_urls();
        $i = 0;
        $total = count($records);
        foreach($records as $record)
        {
            $i++;
            debug("\n $i of $total");
            // if($record['url'] != "http://www.iucn-tftsg.org/cbftt/kinosternon-scorpioides-albogulare-064") continue; //debug
            self::prepare_data($record);
            // if($i >= 10) break; // debug
        }
        $this->create_archive();
    }

    private function prepare_data($rec)
    {
        $descriptions = array();
        debug("\n\n" . " - " . $rec['sciname'] . " - " . $rec['taxonID'] . " - " . $rec['url'] . "\n");
        if($html = Functions::lookup_with_cache($rec['url'], array('download_wait_time' => 3000000, 'timeout' => 240, 'download_attempts' => 5)))
        {
            $html = str_ireplace("www.iucn&ndash;tftsg.org", "www.iucn-tftsg.org", $html);
            $agent_ids = self::get_object_agents($html, $rec);
            $rec = self::get_descriptions_from_html($html, $rec);
            $texts = $rec['texts'];
            $reference_ids = self::get_object_reference_ids($texts['citation']);
            self::get_texts($rec, $agent_ids, $reference_ids);
            self::get_images($rec, array(), $reference_ids);
            $this->create_instances_from_taxon_object($rec, array());
            self::get_vernacular_names($rec);
            self::get_synonyms($rec);
        }
    }

    private function get_synonyms($obj)
    {
        $synonyms = @$obj['texts']['synonymy'];
        if(!$synonyms) return;
        //manual adjustment
        $synonyms = str_ireplace("Geoemyda yuwonoi McCord, Iverson, and Boeadi 1995", "Geoemyda yuwonoi McCord Iverson and Boeadi 1995", $synonyms);
        $synonyms = explode(",", $synonyms);
        debug("synonyms:");
        foreach($synonyms as $name)
        {
            $name = str_ireplace(array(".", "&nbsp;"), "", trim($name));
            $name = self::clean_str($name);
            debug("[$name]");
            $synonym = new \eol_schema\Taxon();
            $synonym->scientificName = (string) trim($name);
            $synonym->acceptedNameUsageID = $obj['taxonID'];
            $synonym->taxonomicStatus = 'synonym';
            $synonym->taxonID = md5($obj['taxonID'] . "|$synonym->scientificName|$synonym->taxonomicStatus");
            if(!$synonym->scientificName) continue;
            if(!isset($this->taxon_ids[$synonym->taxonID]))
            {
                $this->archive_builder->write_object_to_file($synonym);
                $this->taxon_ids[$synonym->taxonID] = 1;
            }
        }
    }

    private function get_vernacular_names($obj)
    {
        if(!$obj['vernacular'])
        {
            debug("\n No vernacular names");
            return;
        }
        $vernaculars = explode(",", $obj['vernacular']);
        debug("common names:");
        foreach($vernaculars as $common_name)
        {
            $common_name = str_ireplace(array(".", "&nbsp;"), "", trim($common_name));
            $common_name = self::clean_str($common_name);
            debug("[$common_name]");
            if($common_name == '') continue;
            $vernacular = new \eol_schema\VernacularName();
            $vernacular->taxonID = $obj['taxonID'];
            $vernacular->vernacularName = (string) trim($common_name);
            $vernacular->language = 'en';
            $vernacular_id = md5("$vernacular->taxonID|$vernacular->vernacularName|$vernacular->language");
            if(!$vernacular->vernacularName) continue;
            if(!isset($this->vernacular_name_ids[$vernacular_id]))
            {
                $this->archive_builder->write_object_to_file($vernacular);
                $this->vernacular_name_ids[$vernacular_id] = 1;
            }
        }
    }

    private function get_descriptions_from_html($html, $rec)
    {
        $descriptions = array();
        $html = str_ireplace("Synonomy", "Synonymy", $html);
        $html = str_ireplace("<strong>", "<b>", $html);
        $html = str_ireplace("</strong>", "</b>", $html);
        $html = strip_tags($html, "<p><b><a><i><div><img>");
        if(preg_match("/<b>Distribution:<\/b>(.*?)<\/div>/ims", $html, $arr))
        {
            $map = $arr[1];
            if(preg_match("/src\=\"(.*?)\"/ims", $map, $arr)) $descriptions['map_image'] = $arr[1];
            $map_caption = trim(strip_tags($map));
            // to remove extra tags at the start & end of texts
            $strings_2be_removed = array("&nbsp;");
            $map_caption = self::remove_first_part_of_string($strings_2be_removed, trim($map_caption));
            $descriptions['map_caption'] = $map_caption;
            debug("\n\n map caption: [" . $descriptions['map_caption'] . "]");
            debug("\n\n map image: [" . $descriptions['map_image'] . "]");
        }
        else debug("\n No map");

        $html = strip_tags($html, "<p><b><a><i>");
        if(preg_match("/Summary<\/b>(.*?)<b>Distribution/ims", $html, $arr))
        {
            $summary = $arr[1];
            $descriptions["SUMMARY"] = $summary;
        }
        else debug("\n No SUMMARY");

        if(preg_match("/<b>Distribution<\/b>(.*?)<b>Synonymy/ims", $html, $arr))
        {
            $distribution = $arr[1];
            $descriptions["DISTRIBUTION"] = $distribution;
        }
        else debug("\n No DISTRIBUTION");

        if(preg_match("/<b>Subspecies(.*?)<b>Status/ims", $html, $arr))
        {
            $subspecies = $arr[1];
            $descriptions["SUBSPECIES"] = $subspecies;
        }
        else
        {
            if(!in_array(trim($rec['url']), array("http://www.iucn-tftsg.org/pelusios-castanoides-intergularis-010/",
                                                  "http://www.iucn-tftsg.org/pelusios-subniger-parietalis-016/",
                                                  "http://www.iucn-tftsg.org/pelusios-seychellensis-018/",
                                                  "http://www.iucn-tftsg.org/apalone-spinifera-atra-021/",
                                                  "http://www.iucn-tftsg.org/kinosternon-scorpioides-albogulare-064/",
                                                  "http://www.iucn-tftsg.org/cbftt/toc-ind/toc/pelusios-castanoides-intergularis-010",
                                                  "http://www.iucn-tftsg.org/cbftt/toc-ind/toc/pelusios-subniger-parietalis-016",
                                                  "http://www.iucn-tftsg.org/cbftt/toc-ind/toc/pelusios-seychellensis-018",
                                                  "http://www.iucn-tftsg.org/cbftt/toc-ind/toc/apalone-spinifera-atra-021",
                                                  "http://www.iucn-tftsg.org/cbftt/toc-ind/toc/kinosternon-scorpioides-albogulare-064"
                                                  ))) debug("\n No subspecies");
        }

        if(preg_match("/<b>Status<\/b>(.*?)<p /ims", $html, $arr))
        {
            $status = $arr[1];
            $descriptions["STATUS"] = $status;
        }
        else debug("\n No STATUS");
        
        if(preg_match("/<b>Synonymy<\/b>(.*?)<b>Subspecies/ims", $html, $arr) ||
           preg_match("/<b>Synonymy<\/b>(.*?)<b>Status/ims", $html, $arr)
        )
        {
            $synonymy = trim(strip_tags($arr[1]));
            $strings_2be_removed = array(".", "&ndash;");
            $synonymy = self::remove_first_part_of_string($strings_2be_removed, $synonymy);
            $descriptions["synonymy"] = $synonymy;
        }
        else debug("\n No synonyms");
        
        if(preg_match("/<b>Citation(.*?)\/cbftt/ims", $html, $arr) || preg_match("/Citation:<\/b>(.*?)<a/ims", $html, $arr))
        {
            $citation = trim(strip_tags($arr[1])) . "/cbftt";
            $citation = self::clean_str($citation);
            $strings_2be_removed = array(":");
            $citation = self::remove_first_part_of_string($strings_2be_removed, $citation);
            $citation = self::remove_last_part_of_string(array("&nbsp;"), $citation);
            $descriptions["citation"] = $citation;
            debug("\n\n citation: [$citation]");
        }
        else debug("\n No citation");

        $rec['texts'] = $descriptions;
        return $rec;
    }

    private function get_texts($rec, $agent_ids, $reference_ids)
    {
        $texts = $rec['texts'];
        $subjects = array_keys($texts);
        // this loop will just check if all topics are mapped with a subject
        foreach($subjects as $subject)
        {
            if(!in_array($subject, array("synonymy", "citation", "map_image", "map_caption"))) // these won't be text objects
            {
                debug("\n\n $subject: " . $this->subject[$subject]['category']);
                $description = (string) utf8_encode($texts[$subject]);
                $description = trim(strip_tags($description, "<a><i>"));
                $description = self::clean_str($description);
                $description = str_ireplace(array("&nbsp;"), " ", $description);
                // to remove extra tags at the start & end of texts
                $strings_2be_removed = array("</b>", ".", "&ndash;", "&mdash;");
                $description = self::remove_first_part_of_string($strings_2be_removed, trim($description));
                $description = self::remove_last_part_of_string(array("</p>", "<b>", "&nbsp;", "&mdash;"), trim($description));
                if(in_array(trim(strtolower($description)), array("none recognized.", "none.", "none currently recognized.", "no subspecies have been described.", "there are no recognized subspecies.", "no subspecies currently recognized."))) continue;
                // debug("description: \n[$description]");
                if($subject == "SUMMARY")       $dc_identifier = "GenDesc_" . $rec['taxonID'];
                if($subject == "DISTRIBUTION")  $dc_identifier = "Distribution_" . $rec['taxonID'];
                if($subject == "STATUS")        $dc_identifier = "Status_" . $rec['taxonID'];
                if($subject == "SUBSPECIES")    $dc_identifier = "Subspecies_" . $rec['taxonID'];
                if(trim($description))
                {
                    $mr = new \eol_schema\MediaResource();
                    if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
                    if($agent_ids)      $mr->agentID = implode("; ", $agent_ids);
                    $mr->taxonID        = (string) $rec['taxonID'];
                    $mr->identifier     = (string) $dc_identifier;
                    $mr->type           = "http://purl.org/dc/dcmitype/Text";
                    $mr->language       = 'en';
                    $mr->format         = "text/html";
                    $mr->furtherInformationURL = (string) self::clean_str(trim($rec['url']));
                    $mr->CVterm         = (string) $this->subject[$subject]['category'];
                    $mr->Owner          = "IUCN/SSC Tortoise and Freshwater Turtle Specialist Group";
                    $mr->rights         = "Copyright 2009 Chelonian Research Foundation";
                    $mr->title          = (string) $this->subject[$subject]['title'];
                    $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                    $mr->audience       = 'Everyone';
                    $mr->description    = (string) $description;
                    // $mr->bibliographicCitation = (string) $bibliographic_citation;
                    $this->archive_builder->write_object_to_file($mr);
                }
            }
        }
    }

    private function get_images($rec, $agent_ids, $reference_ids)
    {
        $texts = $rec['texts'];
        if(@$texts['map_caption'] && @$texts['map_image'])
        {
            $description = (string) utf8_encode($texts['map_caption']);
            $mediaURL = (string) utf8_encode($texts['map_image']);
            $dc_identifier = "map_" . $rec['taxonID'];
            if(trim($description) && trim($mediaURL))
            {
                $mr = new \eol_schema\MediaResource();
                if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
                if($agent_ids)      $mr->agentID = implode("; ", $agent_ids);
                $mr->taxonID        = (string) $rec['taxonID'];
                $mr->identifier     = (string) $dc_identifier;
                $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
                $mr->language       = 'en';
                $mr->format         = Functions::get_mimetype($mediaURL);
                $mr->furtherInformationURL = (string) self::clean_str(trim($rec['url']));
                $mr->CVterm         = "";
                $mr->Owner          = "IUCN/SSC Tortoise and Freshwater Turtle Specialist Group";
                $mr->rights         = "Copyright 2009 Chelonian Research Foundation";
                $mr->title          = "Distribution";
                $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                $mr->audience       = 'Everyone';
                $mr->description    = (string) $description;
                $mr->subtype        = "Map";
                $mr->accessURI      = $mediaURL;
                // $mr->bibliographicCitation = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }

    private function get_object_reference_ids($citation)
    {
        $reference_ids = array();
        if($citation)
        {
            $r = new \eol_schema\Reference();
            $r->full_reference = (string) $citation;
            $r->identifier = md5($r->full_reference);
            $reference_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_reference_ids)) 
            {
               $this->resource_reference_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $reference_ids;
    }

    private function get_object_agents($html, $rec)
    {
        // get last common name
        $names = explode(",", $rec['vernacular']);
        $names = array_filter(array_map('trim', $names)); // will trim all values of the array
        $name = trim($names[count($names)-1]);
        $name = str_ireplace(array("."), "", $name);
        // manual adjustment
        if(trim($name) == "Dunn&#39;s Mud Turtle") $name = "Cabeza de Trozo";
        if(preg_match("/$name(.*?)Summary/ims", $html, $arr))
        {
            // get partial html
            $html = $arr[1];
            // get first <p> block
            if(preg_match("/<p style\=\"text-align\: center\;\">(.*?)<p style\=\"text-align\: center\;\">/ims", $html, $arr))
            {
                $agents = strip_tags($arr[1]);
                // manual adjustment
                $agents = str_ireplace("and ", ",", $agents);
                $agents = str_ireplace(", Jr.", " Jr.", $agents);
                $agents = str_ireplace(", Sr.", " Sr.", $agents);
                $agents = str_ireplace(array("&nbsp;"), "", $agents);
                $agents = preg_replace("/[0-9]/", "", $agents);
                $agents = explode(",", $agents);
                $agents = array_filter(array_map('trim', $agents)); // will trim all values of the array
                // create agents.tab
                $agent_ids = array();
                foreach($agents as $agent)
                {
                    $agent = (string) trim($agent);
                    if(!$agent) continue;
                    $r = new \eol_schema\Agent();
                    $r->term_name = $agent;
                    $r->identifier = md5("$agent|author");
                    $r->agentRole = "author";
                    $r->term_homepage = "http://www.iucn-tftsg.org/";
                    $agent_ids[] = $r->identifier;
                    if(!in_array($r->identifier, $this->resource_agent_ids)) 
                    {
                       $this->resource_agent_ids[] = $r->identifier;
                       $this->archive_builder->write_object_to_file($r);
                    }
                }
                return $agent_ids;
            }
        }
    }

    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon_id = (string)$rec['taxonID'];
        $taxon->taxonID = $taxon_id;
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonRank = '';
        $scientificName = (string) utf8_encode($rec['sciname']);
        if(!$scientificName) return;
        $taxon->scientificName  = $scientificName;
        $taxon->kingdom         = 'Animalia';
        $taxon->genus           = substr($scientificName, 0, stripos($scientificName, " "));
        $this->taxa[$taxon_id] = $taxon;
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

    private function initialize_subjects()
    {
        $this->subject['DISTRIBUTION']['title'] = "Distribution";
        $this->subject['DISTRIBUTION']['category'] = $this->SPM . "#Distribution";
        $this->subject['STATUS']['title'] = "Status";
        $this->subject['STATUS']['category'] = $this->SPM . "#ConservationStatus";
        $this->subject['SUBSPECIES']['title'] = "Subspecies";
        $this->subject['SUBSPECIES']['category'] = $this->EOL . "#Taxonomy";
        $this->subject['SUMMARY']['title'] = "Summary";
        $this->subject['SUMMARY']['category'] = $this->SPM . "#TaxonBiology";
    }

    private function remove_first_part_of_string($chars_2be_removed, $string)
    {
        foreach($chars_2be_removed as $chars)
        {
            $len = strlen($chars);
            while(substr($string, 0, $len) == $chars) 
            {
               $string = trim(substr($string, $len, strlen($string))); //chars at the beginning of the string is removed
            }
        }
        return $string;
    }

    private function remove_last_part_of_string($chars_2be_removed, $string)
    {
        foreach($chars_2be_removed as $chars)
        {
            while(substr($string, -strlen($chars)) == $chars) 
            {
                $string = trim(substr($string, 0, strlen($string) - strlen($chars)));
            }
        }
        return $string;
    }

    function clean_str($str)
    {
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011", "", ""), " ", trim($str));
        return trim($str);
    }

}
?>