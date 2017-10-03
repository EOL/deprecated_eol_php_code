<?php
namespace php_active_record;
// connector: []
class CaliforniaRareFruitAPI
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
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
        $this->site_domain = "http://www.crfg.org/";
        $this->fruit_links = array("fruit_facts"        => $this->site_domain . "pubs/frtfacts.html",
                                   "domain_fruit_facts" => $this->site_domain . "pubs/");
    }

    /*
    http://www.crfg.org/descr/descr_main_a1.html
    http://www.crfg.org/descr/descr_main_a2.html
    http://www.crfg.org/descr/descr_main_a?.html
    ...
    http://www.crfg.org/descr/descr_main_z1.html
    http://www.crfg.org/descr/descr_main_z2.html
    http://www.crfg.org/descr/descr_main_z?.html
    ...
    */

    function get_all_taxa()
    {
        $records = self::process_fruit_facts();
        self::process_data($records);
        $this->create_archive();
    }

    private function get_fruit_paths()
    {
        if($html = Functions::get_remote_file($this->fruit_links["fruit_facts"], array('download_wait_time' => 3000000, 'timeout' => 240, 'download_attempts' => 2, 'delay_in_minutes' => 1)))
        {
            if(preg_match_all("/<li>(.*?)<\/a>/ims", $html, $arr)) return $arr[1];
        }
        echo "\n investigate: fruit list not available\n";
        return false;
    }
    
    private function process_fruit_facts()
    {
        $fruit_paths = self::get_fruit_paths();
        if(!$fruit_paths) return;
        $this->search_terms = self::get_search_terms();
        $records = array();
        $i = 0;
        foreach($fruit_paths as $path)
        {
            $i++;
            // if($i >= 2) break; // debug
            // if($i != 42) continue; // debug
            $record = array();
            if(preg_match("/\"(.*?)\"/ims", $path, $arr2))
            {
                if($arr2[1] == "ff/edible-palms.html") continue; // mulitiple taxa in a page
                if($arr2[1] == "ff/apricot_low_chill.html") continue; // different HTML structure
                
                $url = $this->fruit_links["domain_fruit_facts"] . $arr2[1];
                if($html = Functions::get_remote_file($url, array('download_wait_time' => 3000000, 'timeout' => 240, 'download_attempts' => 2, 'delay_in_minutes' => 1)))
                {
                    $record["source_url"] = $url;
                    //manual adjustments
                    $html = str_replace("<b>Soils</b>", "<b>Soils:</b>", $html);
                    $html = str_replace("<b>Related species</b>", "<b>Related species:</b>", $html);
                    $html = str_replace("<b>Adaptation</b>", "<b>Adaptation:</b>", $html);
                    $html = str_replace("<b>Pruning</b>", "<b>Pruning:</b>", $html);

                    if(preg_match_all("/<h2><i>(.*?)<\/i>/ims", $html, $arr3)) 
                    {
                        $record["taxon"] = trim(strip_tags($arr3[1][0]));
                        $record["taxon_id"] = str_replace(" ", "_", $record["taxon"]);
                        $record["family"] = strip_tags($arr3[1][1]);
                    }
                    if(preg_match("/src=\"(.*?)\"/ims", $html, $arr3)) $record["image"] = $arr3[1];
                    if(preg_match("/&\#169;(.*?)Questions or comments/ims", $html, $arr3))
                    {
                        $holder = "&#169; " . trim($arr3[1]);
                        $holder = strip_tags($holder, "<a>");
                        $holder = str_ireplace('href="/index.html"', 'href="' . $this->site_domain . 'index.html"', $holder);
                        $record["rightsHolder"] = strip_tags($holder);
                    }
                    // actual text descriptions
                    foreach($this->search_terms as $key => $value)
                    {
                        $end_strings = array("<p>", "<h"); // possible end strings
                        foreach($end_strings as $end_string)
                        {
                            if(isset($record[$value])) break;
                            if(preg_match("/$key<\/b>(.*?)$end_string/ims", $html, $arr3)) $record[$value] = $arr3[1];
                        }
                    }
                    foreach($record as $key => $value) $record[$key] = str_ireplace(array("\n"), " ", $value);
                    $record = array_filter(array_map('trim', $record));
                }
            }
            print "\n count: " . count($record) . "\n";
            if($record) $records[$record["taxon"]] = $record;
        } // end foreach
        return $records;
    }

    private function process_data($records)
    {
        foreach($records as $rec)
        {
            $this->create_instances_from_taxon_object($rec, array());
            self::create_text_objects($rec);
        }
    }

    private function create_text_objects($rec)
    {
        foreach($this->search_terms as $key => $topic)
        {
            if(isset($rec[$topic]))
            {
                $params = array("description"   => $rec[$topic],
                                "taxon_id"      => $rec["taxon_id"],
                                "title"         => $this->subjects[$topic]["title"],
                                "subject"       => $this->subjects[$topic]["subj"],
                                "media_id"      => $rec["taxon_id"] . "_" . $topic,
                                "source_url"    => $rec["source_url"],
                                "rightsHolder"  => $rec["rightsHolder"]);
                self::get_texts($params);
            }
        }
    }

    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID                     = (string) $rec["taxon_id"];
        $taxon->taxonRank                   = "";
        $taxon->scientificName              = (string) $rec["taxon"];
        $taxon->scientificNameAuthorship    = "";
        $taxon->family                      = (string) $rec["family"];
        $taxon->genus                       = "";
        $this->taxa[$taxon->taxonID] = $taxon;
    }

    private function get_object_reference_ids($ref)
    {
        $reference_ids = array();
        $r = new \eol_schema\Reference();
        $r->full_reference = (string) $ref;
        $r->identifier = md5($r->full_reference);
        $reference_ids[] = $r->identifier;
        if(!in_array($r->identifier, $this->resource_reference_ids)) 
        {
           $this->resource_reference_ids[] = $r->identifier;
           $this->archive_builder->write_object_to_file($r);
        }
        return $reference_ids;
    }

    private function get_texts($params, $reference_ids = null, $agent_ids = null)
    {
        $mr = new \eol_schema\MediaResource();
        if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID = $params["taxon_id"];
        $mr->identifier = $params["media_id"];
        $mr->type = 'http://purl.org/dc/dcmitype/Text';
        $mr->language = 'en';
        $mr->format = 'text/html';
        $mr->furtherInformationURL = $params["source_url"];
        $mr->description = utf8_encode($params["description"]);
        $mr->CVterm = $params["subject"];
        $mr->title = $params["title"];
        $mr->creator = '';
        $mr->CreateDate = '';
        $mr->modified = '';
        $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
        $mr->Owner = $params["rightsHolder"];
        $mr->publisher = '';
        $mr->audience = 'Everyone';
        $mr->bibliographicCitation = '';
        $this->archive_builder->write_object_to_file($mr);
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

    private function get_search_terms()
    {
        $search_terms["Common Names\:"] = "common_names";
        $search_terms["Common Name\:"] = "common_name";
        $search_terms["Origin\:"] = "origin";
        $search_terms["Species\:"] = "species";
        $search_terms["Distant Affinity\:"] = "distant_affinity";
        $search_terms["Related species\:"] = "related_species";
        $search_terms["Adaptation\:"] = "adaptation";
        // DESCRIPTION
        $search_terms["Growth Habit\:"] = "growth_habit";
        $search_terms["Growth Habits\:"] = "growth_habits";
        $search_terms["Foliage\:"] = "foliage";
        $search_terms["Leaves\:"] = "leaves";
        $search_terms["Flowers\:"] = "flowers";
        $search_terms["Fruits\:"] = "fruits";
        $search_terms["Fruit\:"] = "fruit";
        // CULTURE
        $search_terms["Location\:"] = "location";
        $search_terms["Site Preparation\:"] = "site_preparation";
        $search_terms["Soil\:"] = "soil";
        $search_terms["Soils\:"] = "soils";
        $search_terms["Irrigation\:"] = "irrigation";
        $search_terms["Fertilizing\:"] = "fertilizing";
        $search_terms["Fertilization\:"] = "fertilization";
        $search_terms["Frost Protection\:"] = "frost_protection";
        $search_terms["Pruning\:"] = "pruning";
        $search_terms["Propagation\:"] = "propagation";
        $search_terms["Pests and diseases\:"] = "pests_and_diseases";
        $search_terms["Harvest\:"] = "harvest";
        $search_terms["Fruit Harvest\:"] = "fruit_harvest";
        $search_terms["Commercial Potential\:"] = "commercial_potential";
        $search_terms["Plant selection\:"] = "plant_selection";
        $search_terms["Miscellaneous\:"] = "miscellaneous";
        $search_terms["Misc.\:"] = "misc";
        
        /* subjects */
        $subjects["common_names"]["subj"] = $this->EOL . "#Taxonomy";
        $subjects["common_name"]["subj"] = $this->EOL . "#Taxonomy";
        $subjects["origin"]["subj"] = $this->SPM . "#GeneralDescription";
        $subjects["species"]["subj"] = $this->EOL . "#Taxonomy";
        $subjects["distant_affinity"]["subj"] = $this->EOL . "#Taxonomy";
        $subjects["related_species"]["subj"] = $this->EOL . "#Taxonomy";
        $subjects["adaptation"]["subj"] = $this->SPM . "#GeneralDescription";
        // DESCRIPTION
        $subjects["growth_habit"]["subj"] = $this->SPM . "#GeneralDescription";
        $subjects["growth_habits"]["subj"] = $this->SPM . "#GeneralDescription";
        $subjects["foliage"]["subj"] = $this->SPM . "#GeneralDescription";
        $subjects["leaves"]["subj"] = $this->SPM . "#GeneralDescription";
        $subjects["flowers"]["subj"] = $this->SPM . "#GeneralDescription";
        $subjects["fruits"]["subj"] = $this->SPM . "#GeneralDescription";
        $subjects["fruit"]["subj"] = $this->SPM . "#GeneralDescription";
        // CULTURE
        $subjects["location"]["subj"] = $this->SPM . "#Habitat";
        $subjects["site_preparation"]["subj"] = $this->SPM . "#Habitat";
        $subjects["soil"]["subj"] = $this->SPM . "#Ecology";
        $subjects["soils"]["subj"] = $this->SPM . "#Ecology";
        $subjects["irrigation"]["subj"] = $this->SPM . "#Ecology";
        $subjects["fertilizing"]["subj"] = $this->SPM . "#Ecology";
        $subjects["fertilization"]["subj"] = $this->SPM . "#Ecology";
        $subjects["frost_protection"]["subj"] = $this->SPM . "#Habitat";
        $subjects["pruning"]["subj"] = $this->SPM . "#Ecology";
        $subjects["propagation"]["subj"] = $this->SPM . "#Dispersal";
        $subjects["pests_and_diseases"]["subj"] = $this->SPM . "#Ecology";
        $subjects["harvest"]["subj"] = $this->SPM . "#Use";
        $subjects["fruit_harvest"]["subj"] = $this->SPM . "#Use";
        $subjects["commercial_potential"]["subj"] = $this->SPM . "#Use";
        $subjects["plant_selection"]["subj"] = $this->SPM . "#Ecology";
        $subjects["miscellaneous"]["subj"] = $this->EOL . "#Notes";
        $subjects["misc"]["subj"] = $this->EOL . "#Notes";

        /* titles */
        $subjects["common_names"]["title"] = "Common names";
        $subjects["common_name"]["title"] = "Common names";
        $subjects["origin"]["title"] = "Origin";
        $subjects["species"]["title"] = "Species";
        $subjects["distant_affinity"]["title"] = "Distant affinity";
        $subjects["related_species"]["title"] = "Related species";
        $subjects["adaptation"]["title"] = "Adaptation";
        // DESCRIPTION
        $subjects["growth_habit"]["title"] = "Description: Growth habits";
        $subjects["growth_habits"]["title"] = "Description: Growth habits";
        $subjects["foliage"]["title"] = "Description: Foliage";
        $subjects["leaves"]["title"] = "Description: Leaves";
        $subjects["flowers"]["title"] = "Description: Flowers";
        $subjects["fruits"]["title"] = "Description: Fruits";
        $subjects["fruit"]["title"] = "Description: Fruits";
        // CULTURE
        $subjects["location"]["title"] = "Culture: Location";
        $subjects["site_preparation"]["title"] = "Culture: Site preparation";
        $subjects["soil"]["title"] = "Culture: Soil";
        $subjects["soils"]["title"] = "Culture: Soil";
        $subjects["irrigation"]["title"] = "Culture: Irrigation";
        $subjects["fertilizing"]["title"] = "Culture: Fertilization";
        $subjects["fertilization"]["title"] = "Culture: Fertilization";
        $subjects["frost_protection"]["title"] = "Culture: Frost protection";
        $subjects["pruning"]["title"] = "Culture: Pruning";
        $subjects["propagation"]["title"] = "Culture: Propagation";
        $subjects["pests_and_diseases"]["title"] = "Culture: Pests and diseases";
        $subjects["harvest"]["title"] = "Culture: Harvest";
        $subjects["fruit_harvest"]["title"] = "Culture: Fruit harvest";
        $subjects["commercial_potential"]["title"] = "Culture: Commercial potential";
        $subjects["plant_selection"]["title"] = "Culture: Plant selection";
        $subjects["miscellaneous"]["title"] = "Notes:";
        $subjects["misc"]["title"] = "Notes:";

        $this->subjects = $subjects;
        return $search_terms;
    }

}
?>