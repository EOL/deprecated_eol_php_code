<?php
namespace php_active_record;
/* connector: [505 - plants] [509 - animals]
This will screen scrape information from: http://www.fs.fed.us/database/feis/
*/

class USDAfsfeisAPI
{
    function __construct($folder, $group)
    {
        $this->fsfeis_domain = "http://www.fs.fed.us/database/feis/";
        $this->main_groups = $group;
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
        $this->temp_page_reference_nos = array();
        $this->class_name['Reptile'] = "Reptilia";
        $this->class_name['Chelonia'] = "Reptilia";
        $this->class_name['Amphibian'] = "Amphibia";
        $this->class_name['Insect'] = "Insecta";
        $this->class_name['Mammal'] = "Mammalia";
        $this->class_name['Bird'] = "Aves";
        $this->exclude_vernaculars = array("http://www.fs.fed.us/database/feis/animals/bird/cent/all.html", "http://www.fs.fed.us/database/feis/animals/bird/tymp/all.html", "http://www.fs.fed.us/database/feis/plants/fern/lygspp/all.html", "http://www.fs.fed.us/database/feis/plants/forb/carspp3/all.html", "http://www.fs.fed.us/database/feis/plants/forb/../vine/cynspp/all.html", "http://www.fs.fed.us/database/feis/plants/forb/../vine/diospp/all.html", "http://www.fs.fed.us/database/feis/plants/forb/dipspp/all.html", "http://www.fs.fed.us/database/feis/plants/forb/galspp/all.html", "http://www.fs.fed.us/database/feis/plants/forb/kumspp/all.html", "http://www.fs.fed.us/database/feis/plants/forb/linspp/all.html", "http://www.fs.fed.us/database/feis/plants/forb/melspp/all.html", "http://www.fs.fed.us/database/feis/plants/forb/polspp/all.html", "http://www.fs.fed.us/database/feis/plants/forb/../vine/vinspp/all.html", "http://www.fs.fed.us/database/feis/plants/graminoid/brocar/all.html", "http://www.fs.fed.us/database/feis/plants/graminoid/brospp/all.html", "http://www.fs.fed.us/database/feis/plants/graminoid/carino/all.html", "http://www.fs.fed.us/database/feis/plants/graminoid/carspp2/all.html", "http://www.fs.fed.us/database/feis/plants/graminoid/impspp/all.html", "http://www.fs.fed.us/database/feis/plants/../lichens/claspp/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/../tree/alninc/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/corcor/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/cytspp/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/ligspp/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/lonspp/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/quespp2/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/rhaspp/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/../tree/tamspp/all.html", "http://www.fs.fed.us/database/feis/plants/tree/casspp/all.html", "http://www.fs.fed.us/database/feis/plants/tree/pinell/all.html", "http://www.fs.fed.us/database/feis/plants/tree/popspp/all.html", "http://www.fs.fed.us/database/feis/plants/tree/quespp1/all.html", "http://www.fs.fed.us/database/feis/plants/tree/quefal/all.html", "http://www.fs.fed.us/database/feis/plants/vine/wisspp/all.html");
        
        $this->debug_spg = array();
        $this->debug_toberemoved = array();
        $this->script_count = array("old" => 0, "new" => 0);

        // main-topics ==================================================================================
        $this->topics["INTRODUCTORY"] = array("AUTHORSHIP AND CITATION", "ABBREVIATION", "SYNONYMS", "NRCS PLANT CODE", "COMMON NAMES", "TAXONOMY", "LIFE FORM", "FEIS ABBREVIATION", "ORDER", "CLASS", "FEDERAL LEGAL STATUS", "OTHER STATUS");
        $this->topics["DISTRIBUTION AND OCCURRENCE"] = array("GENERAL DISTRIBUTION", "ECOSYSTEMS", "STATES", "BLM PHYSIOGRAPHIC REGIONS", "KUCHLER PLANT ASSOCIATIONS", "SAF COVER TYPES", "SRM (RANGELAND) COVER TYPES", "HABITAT TYPES AND PLANT COMMUNITIES", "PLANT COMMUNITIES", "SITE CHARACTERISTICS AND PLANT COMMUNITIES", "PLANT ASSOCIATIONS");
        $this->topics["WILDLIFE DISTRIBUTION AND OCCURRENCE"] = $this->topics["DISTRIBUTION AND OCCURRENCE"];
        $this->topics["BIOLOGICAL DATA AND HABITAT REQUIREMENTS"] = array("LIFE HISTORY", "PREFERRED HABITAT", "COVER REQUIREMENTS", "FOOD HABITS", "PREDATORS", "MANAGEMENT CONSIDERATIONS", "DISEASES AND SOURCES OF MORTALITY", "FEDERAL LEGAL STATUS", "OTHER STATUS", "BIOLOGICAL DATA", "SOURCES OF MORTALITY", "MORTALITY");
        $this->topics["MANAGEMENT CONSIDERATIONS"] = array("IMPORTANCE TO LIVESTOCK AND WILDLIFE", "PALATABILITY", "NUTRITIONAL VALUE", "COVER VALUE", "VALUE FOR REHABILITATION OF DISTURBED SITES", "OTHER USES AND VALUES", "OTHER MANAGEMENT CONSIDERATIONS", "UTILIZATION", "OTHER UTILIZATIONS", "OTHER USES", "SPREAD, IMPACTS, AND CONTROL");
        $this->topics["BOTANICAL AND ECOLOGICAL CHARACTERISTICS"] = array("GENERAL BOTANICAL CHARACTERISTICS", "RAUNKIAER LIFE FORM", "REGENERATION PROCESSES", "SITE CHARACTERISTICS", "SUCCESSIONAL STATUS", "SEASONAL DEVELOPMENT", "LIFE FORM");
        $this->topics["GENERAL INFORMATION ON BIOLOGY AND ECOLOGY"] = $this->topics["BOTANICAL AND ECOLOGICAL CHARACTERISTICS"];
        $this->topics["FIRE ECOLOGY"] = array("FIRE ECOLOGY OR ADAPTATIONS", "POSTFIRE REGENERATION STRATEGY", "FIRE ECOLOGY AND ADAPTATIONS", "FIRE REGIMES", "FIRE EFFECTS", "FIRE MANAGEMENT CONSIDERATIONS", "FIRE REGIME TABLE");
        $this->topics["FIRE EFFECTS"] = array("IMMEDIATE FIRE EFFECT ON PLANT", "DISCUSSION AND QUALIFICATION OF FIRE EFFECT", "PLANT RESPONSE TO FIRE", "DISCUSSION AND QUALIFICATION OF PLANT RESPONSE", "FIRE MANAGEMENT CONSIDERATIONS", "FIRE CASE STUDIES", "DIRECT FIRE EFFECTS", "HABITAT-RELATED FIRE EFFECTS", "INDIRECT FIRE EFFECTS", "FIRE REGIMES", "FIRE EFFECTS", "FUELS AND FIRE REGIMES", "FIRE REGIME", "DIRECT FIRE EFFECTS ON ANIMALS", "FIRE USE", "FUELS");
        $this->topics["FIRE EFFECTS AND USE"]        = $this->topics["FIRE EFFECTS"];
        $this->topics["FIRE EFFECTS AND MANAGEMENT"] = $this->topics["FIRE EFFECTS"];
        // sub-topics ==================================================================================
        $this->subtopics["DISEASES AND SOURCES OF MORTALITY"] = array("Predators", "Diseases and parasites", "Weather", "Other sources of mortality");
        $this->subtopics["FUELS AND FIRE REGIMES"] = array("Fuels", "FIRE REGIMES");
        $this->subtopics["FIRE EFFECTS"] = array("IMMEDIATE FIRE EFFECT ON PLANT", "POSTFIRE REGENERATION STRATEGY", "FIRE ADAPTATIONS AND PLANT RESPONSE TO FIRE", "Fire adaptations", "Plant response to fire", "Effects of repeated fire");
        $this->subtopics["GENERAL BOTANICAL CHARACTERISTICS"] = array("Botanical description", "Raunkiaer life form", "Common buckthorn aboveground description", "Common buckthorn belowground description", "Dahurian buckthorn description", "Life span and age distribution", "Plant architecture and stand structure");
        $this->subtopics["REGENERATION PROCESSES"] = array("Pollination and breeding system", "Seed production", "Seed dispersal", "Seed banking", "Germination", "Seedling establishment and plant growth", "Vegetative regeneration", "Seedling establishment", "Plant growth", "Germination and seedling establishment", "Seedling establishment and growth", "Pollination and breeding system", "Flower and seed production", "Germination, seedling establishment, and plant growth", "Seed production and dispersal", "Pollination", "Seed production, dispersal, banking, and germination", "Flower, fruit, and seed production", "Breeding system and pollination", "Breeding system", "Seed dispersal, seed banking, and germination", "Plant development and survival");
        $this->subtopics["SITE CHARACTERISTICS AND PLANT COMMUNITIES"] = array("Topography", "Elevation", "Soils and moisture", "Climate", "Soils");
        $this->subtopics["PREDATORS"] = array("Coyote predation", "Other predators", "Factors influencing predation rates", "Parasites and disease");
        $this->subtopics["FOOD HABITS"] = array("Diet", "Nutrition and energetics", "Forage site selection", "Foraging behavior", "Hunting methods", "Diet composition", "Influence of prey availability", "Overlap of coyote and swift fox diets", "Effects of low prey abundance", "Conifer seed characteristics", "Foraging and caching", "Feeding activity", "Forage-site selection", "Earthworm availability", "Diet Selection");
        $this->subtopics["MANAGEMENT CONSIDERATIONS"] = array("Status and threats", "Habitat management", "Population management", "Population status and threats", "Threats", "Population Status", "Management Recommendations", "Population trends", "Hunting", "Crop depredation", "Human disturbance", "Endangered species considerations", "Factors influencing population size", "Plantation management", "Stand-level details", "Spatial and temporal considerations", "Brown-headed cowbird control");
        $this->subtopics["INDIRECT FIRE EFFECTS"] = array("Moose population response to postfire vegetation changes", "Postfire vegetation changes and succession", "Case studies", "Other factors", "Parasites", "Indirect fire effects by region", "Mule deer interactions with fuels and fire effects", "Diseases and parasites", "Response to fire and use of burned areas", "Fire effects on cover", "Fire effects on food", "Wildfire Case Study 1", "Wildfire Case Study 2", "Habitat", "Associated species", "Fire-dependant nature of Kirtland's warbler habitat", "Potential detrimental impacts of fire in Kirtland's warbler habitat", "Comparison of habitats of varying origin", "Demography and use", "Differences in habitat characteristics", "Elk population response to postfire vegetation changes", "Elk use of postfire communities", "Elk condition", "Elk interactions with fuels and fire effects", "Case study: 1988 Yellowstone fires", "Fire effects on vegetation", "Fire effects on American woodcock foods");
        $this->subtopics["LIFE HISTORY"] = array("Physical description", "Physiology", "Courtship and mating", "Reproduction and development", "Social behavior", "Home range and movements", "Population density", "Survival", "Life span", "Longevity and survivorship", "Maturation", "Reproduction", "Annual cycle", "Activity and movement", "Density and home range", "Activity patterns", "Development", "Dispersal", "Survivorship", "Home Range", "Density", "Reproduction and longevity", "Molt", "Migration", "Irruptions and extralimital wandering", "Description", "Social organization", "Age at first reproduction", "Clutch size", "Incubation period", "Nest description", "Nest success", "Development and dispersal of young", "Calendar dates for specific events", "Diurnal habit of species", "Home range size and spatial relationships", "Migration or seasonal movement", "Distinguishing characteristics", "Phenology", "Reproductive biology", "Breeding behavior", "Mortality", "Flight", "Territories and colonies", "Reproductive success", "Brown-headed cowbird parasitism", "Daily activity", "Annual activity", "Social interactions", "Growth", "Longevity", "Home range and movement");
        $this->subtopics["PREFERRED HABITAT"] = array("Elevation", "Topography", "Soil", "Water availability", "Plant species composition", "Forage", "Successional status of elk habitats", "Slope", "Aspect", "Escape terrain", "Forage site selection", "Movement corridors", "Cover requirements", "Cover", "Foraging sites", "Successional status of mule deer habitats", "Edge habitat", "Age and gender", "Predation risk", "Other factors", "Edge habitats", "Vegetation structure/cover type", "Landscape characteristics", "Water", "Potential association with prairie dogs", "Plant community characteristics", "General cover requirements", "Habitat associations for specific life history activities", "Prey dynamics", "Predation and/or competition", "Climate", "General Habitat", "Seasonal Variation", "Movements", "Succession", "Habitat characteristics", "Habitat use", "Landscape use", "Habitat of migratory subspecies", "Habitat of nonmigratory subspecies", "Preferred cover", "Use of burned areas", "Landscape", "Habitat", "Limiting habitat features", "Territoriality", "Home range and density", "Breeding habitat", "Jack pine age and size", "Stand density", "Landscape factors", "Factors influencing duration of occupancy", "Live lower limbs", "Ground cover", "Wintering habitat", "Plant associations used as habitat", "Stand ages", "Landscape-level requirements", "Diurnal habitats", "Nocturnal habitats", "Habitat used during migration");
        $this->subtopics["COVER REQUIREMENTS"] = array("Burrows", "Nests", "Den use", "Den structure", "Topography", "Vegetation composition and height", "Soil", "Water", "Roads", "Associated species", "Cliff nest sites", "Cliff characteristics", "Nest elevation", "Cliff exposure", "Alternate nest and roost sites", "Nest competition", "Nest fidelity", "Denning", "Resting Sites");
        $this->subtopics["BIOLOGICAL DATA"] = array("Life history", "Diet");
        // sub-sub-topics ==================================================================================
        $this->sub_subtopics["Germination"] = array("Environmental factors", "Seed characteristics");
        $this->sub_subtopics["Postfire vegetation changes and succession"] = array("Forage quantity", "Forage nutritional quality", "Forage palatability", "Other changes");
        $this->sub_subtopics["Indirect fire effects by region"] = array("Boreal forest", "Pacific Northwest", "California", "Southwest", "Great Basin", "Rocky Mountains", "Great Plains");
        $this->sub_subtopics["Other factors"] = array("Mule deer, predator, and fire interactions", "Mule deer, other ungulate, and fire interactions", "Livestock presence in burned areas", "Travel patterns", "Physical barriers", "Weather and use of burned areas", "Size and shape of burned areas");
        $this->sub_subtopics["Habitat associations for specific life history activities"] = array("Resting", "Denning", "Traveling", "Foraging");
        $this->sub_subtopics["Life history"] = array("Physical description", "Courtship and mating", "Reproduction and development", "Social behavior", "Movements and home range", "Population density", "Life span and survival", "Home range and movements", "Territory", "Home range", "Density", "Dispersal", "Survival", "Causes of mortality", "Predation", "Hunting", "Population cycles", "Reproduction", "Reproductive timing", "Reproductive output", "Cooperative breeding", "Territories/Density", "Breeding");
        $this->sub_subtopics["Diet"] = array("Temporal and spatial variation in the diet", "Age", "Forage quality");
        // sub-sub-sub-topics ==================================================================================
        $this->sub_sub_subtopics["Movements and home range"] = array("Daily activity", "Seasonal movements and migration", "Dispersal", "Home range");
        $this->sub_sub_subtopics["Life span and survival"] = array("Predators", "Diseases and parasites", "Malnutrition and weather", "Fawn survival", "Hunting", "Calf survival");
        $this->download_options = array('resource_id' => 'FEIS', 'expire_seconds' => 60*60*24*25, 'download_wait_time' => 1000000, 'timeout' => 60*2, 'download_attempts' => 1, 
        'delay_in_minutes' => 0.5);
        // $this->download_options['expire_seconds'] = false;
        /* To fix: PHP curl error The requested URL returned error: 403 Forbidden.
           add 'user_agent' option in $this->download_options as seen below. */
        $this->download_options['user_agent'] = 'User-Agent: curl/7.39.0';
        $this->csv_taxa_list['plants'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/FEIS_groups/plants.csv"; //see http://localhost/cp_new/FEIS_plants/readmeli.txt
        $this->csv_taxa_list['animals'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/FEIS_groups/animals.csv"; //see http://localhost/cp_new/FEIS_plants/readmeli.txt
        $this->csv_taxa_list['lichens'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/FEIS_groups/lichens.csv"; //see http://localhost/cp_new/FEIS_plants/readmeli.txt

    }

    private function find_spm_given_subject($subject)
    {
        $groups = array($this->topics, $this->subtopics, $this->sub_subtopics, $this->sub_sub_subtopics);
        $spm = false;
        foreach($groups as $group) {
            $topics = array_keys($group);
            foreach($topics as $topic) {
                if(isset($this->subject[$topic]['category'])) $spm = @$this->subject[$topic]['category'];
                foreach($group[$topic] as $group_topic) {
                    if(isset($this->subject[$group_topic]['category'])) $spm = $this->subject[$group_topic]['category'];
                    if($subject == $group_topic) return $spm;
                }
            }
        }
        return false;
    }

    public function prepare_taxa_urls()
    {
        foreach($this->main_groups as $kingdom => $group) {
            $i = 0;
            $local_csv = Functions::save_remote_file_to_local($this->csv_taxa_list[$group], array('cache' => 1));
            if(!$file = Functions::file_open($local_csv, "r")) {
                exit("\nCannot access CSV file [$this->csv_taxa_list[$group]]\n");
                return;
            }
            while(!feof($file)) {
                $temp = fgetcsv($file);
                if(!$temp) continue;
                $i++;
                if(($i % 500) == 0) echo "\n".number_format($i);
                if($i == 1) continue;
                elseif($i == 2) $fields = $temp;
                else {
                    $rec = array();
                    $k = 0;
                    foreach($temp as $t) {
                        $rec[$fields[$k]] = $t;
                        $k++;
                    }
                    if($rec) {
                        /* Array(
                            [Acronym] => ABIAMA
                            [Link] => https://www.fs.fed.us/database/feis/plants/tree/abiama/all.html
                            [Scientific Name] => Abies amabilis
                            [Common Name] => Pacific silver fir
                            [Review Date] => 1992
                            [Fire Study Availability] => Not Available
                            [Fire Regime Availability] => Available
                        )*/
                        // print_r($rec);
                        $filenames[$rec['Acronym']] = array("taxonID" => $rec['Acronym'], "url" => $rec['Link'], "sciname" => $rec['Scientific Name'], "vernacular" => $rec['Common Name'], "kingdom" => $kingdom);
                    }
                    // if($i > 5) break;  //debug only
                }
            } // end while{}
            fclose($file);
            unlink($local_csv);
        }//end foreach()
        // exit("\n".count($filenames)."\n");
        return array_values($filenames);
    }
    public function prepare_taxa_urls_v1() //obsolete
    {
        $taxonIDs = array();
        foreach($this->main_groups as $kingdom => $group) {
            // echo "\n Group: $group";
            if($html = Functions::lookup_with_cache($this->fsfeis_domain . $group . "/index.html", $this->download_options)) {
                if(preg_match("/Choose one of the following(.*?)<\/ol>/ims", $html, $arr)) $html = trim($arr[1]);
                else continue;
                if(preg_match_all("/href=\"(.*?)\"/ims", $html, $arr)) $pages = $arr[1];
                foreach($pages as $page) {
                    if(stripos($page, "index.html") == "") $page .= "index.html";
                    $filename = $this->fsfeis_domain . $group . "/" . $page;
                    
                    /* //debug
                    if(!in_array($filename, array(
                        //plants
                        "http://www.fs.fed.us/database/feis/plants/bryophyte/index.html",
                        "http://www.fs.fed.us/database/feis/plants/cactus/index.html",
                        "http://www.fs.fed.us/database/feis/plants/fern/index.html",
                        "http://www.fs.fed.us/database/feis/plants/tree/index.html",
                        "http://www.fs.fed.us/database/feis/plants/forb/index.html",
                        "http://www.fs.fed.us/database/feis/plants/graminoid/index.html",
                        "http://www.fs.fed.us/database/feis/lichens/index.html",
                        "http://www.fs.fed.us/database/feis/plants/vine/index.html",
                        "http://www.fs.fed.us/database/feis/plants/shrub/index.html",
                        //animals
                        "http://www.fs.fed.us/database/feis/animals/amphibian/index.html",
                        "http://www.fs.fed.us/database/feis/animals/reptile/index.html",
                        "http://www.fs.fed.us/database/feis/animals/mammal/index.html",
                        "http://www.fs.fed.us/database/feis/animals/bird/index.html",
                        "http://www.fs.fed.us/database/feis/animals/arthropod/index.html"
                    ))) continue;
                    */
                    
                    if($html = Functions::lookup_with_cache($filename, $this->download_options)) {
                        if(preg_match("/Common Name(.*?)<\/table>/ims", $html, $arr)) {
                           $html = trim($arr[1]);
                           if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
                                $records = $arr[1];
                                foreach($records as $record) {
                                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $record, $arr)) {
                                        $pagex = $arr[1];
                                        if(preg_match("/href=\"(.*?)\"/ims", $pagex[0], $arr)) $part = trim($arr[1]);
                                        if(preg_match("/\">(.*?)<\/a>/ims", $pagex[0], $arr)) $taxonID = trim($arr[1]);
                                        // http://www.fs.fed.us/database/feis/plants/bryophyte/aulpal/all.html
                                        $url = str_ireplace("/index.html", "", $filename) . "/" . $part;
                                        // echo "\n --- $url \n";
                                        if(!in_array($taxonID, $taxonIDs)) {
                                            $taxonIDs[] = $taxonID;
                                            $filenames[] = array("taxonID" => $taxonID, "url" => $url, "sciname" => $pagex[1], "vernacular" => $pagex[2], "kingdom" => $kingdom);
                                        }
                                    }
                                }
                           }
                           else echo "\n ALERT: - no records gathered";
                        }
                        else echo "\n ALERT: - nothing on $filename";
                    }
                    else echo "\n ALERT: Down1: $filename";
                }
            }
            else return array(); // Down main group page: $this->fsfeis_domain . $group . "/index.html"; 
        }
        return $filenames;
    }

    function get_all_taxa()
    {
        self::initialize_subjects();
        $records = self::prepare_taxa_urls();
        /* // debug
        $records = array();
        // $records[] = array("taxonID" => "apco", "url" => "http://www.fs.fed.us/database/feis/animals/bird/apco/all.html", "sciname" => "Aphelocoma coerulescens", "vernacular" => "a2s11a1sas", "kingdom" => "Animalia");
        $records[] = array("taxonID" => "aisp", "url" => "http://www.fs.fed.us/database/feis/animals/bird/aisp/all.html", "sciname" => "Aix sponsa", "vernacular" => "a2sd11a1sas", "kingdom" => "Animalia");
        */
        $urls = array();
        $total = count($records); $i = 0;
        foreach($records as $record) {
            $i++;
            if(($i % 100) == 0) echo "\n$i of $total";
            /* for unique URLs
            if(!in_array($record["url"], $urls))
            {
                self::prepare_data($record);
                $urls[] = $record["url"];
            }
            */
            self::prepare_data($record);
            // if($i >= 5) break; //debug only
        }
        $this->create_archive();
        
        /* debug
        echo "\n urls with <img>\n";
        print_r($this->debug_spg);
        print_r($this->debug_toberemoved);
        print_r($this->script_count);
        */
    }

    private function prepare_data($rec)
    {
        $reference_ids = array();
        $ref_ids = array();
        $agent_ids = array();
        $descriptions = array();
        // echo "\n\n" . " - " . $rec['sciname'] . " - " . $rec['taxonID'] . " - " . $rec['url'];
        if($html = Functions::lookup_with_cache($rec['url'], $this->download_options)) {
            $html = str_ireplace('href="all.html#', 'href="#', $html);
            $html = str_ireplace(array("<br />", "<br >", "<br/>"), "<br>", trim($html));
            $html = str_ireplace("<br> <br>", "<br><br>", trim($html));
            $html = str_ireplace("<a name='REFERENCES'>", '<a name="REFERENCES">', $html);
            $html = str_ireplace("AUTHORSHIP AND CITATION :", "AUTHORSHIP AND CITATION:", $html);
            $html = str_ireplace("HABITAT-RELATED FIRE EFFECTS", "HABITAT RELATED FIRE EFFECTS", $html);
            $html = str_ireplace("APPENDIX: FIRE REGEIME TABLE", "APPENDIX: FIRE REGIME TABLE", $html);
            $html = str_ireplace("DIRECT FIRE EFFECTS ON ANIMALS", "DIRECT FIRE EFFECTS ON ANIMAL", $html);
            $html = str_ireplace("IMPORTANCE TO WILDLIFE AND LIVESTOCK", "IMPORTANCE TO LIVESTOCK AND WILDLIFE", $html);
            $html = str_ireplace("GENERAL  BOTANICAL CHARACTERISTICS", "GENERAL BOTANICAL CHARACTERISTICS", $html);
            $html = str_ireplace("Habitat Management", "Habitat management", $html);
            $html = str_ireplace("fire regimes", "FIRE REGIMES", $html);
            $html = str_ireplace("Foraging Behavior", "Foraging behavior", $html);
            $html = str_ireplace("Raunkiaer life form", "RAUNKIAER LIFE FORM", $html);
            $html = str_ireplace("Immediate fire effect on plant", "IMMEDIATE FIRE EFFECT ON PLANT", $html);
            $html = str_ireplace("Postfire regeneration strategy", "POSTFIRE REGENERATION STRATEGY", $html);
            $html = str_ireplace("Fire adaptations and plant response to fire", "FIRE ADAPTATIONS AND PLANT RESPONSE TO FIRE", $html);
            $html = str_ireplace("SITE CHARACTERISITICS AND PLANT COMMUNITIES", "SITE CHARACTERISTICS AND PLANT COMMUNITIES", $html);
            if(in_array($rec["url"], array("http://www.fs.fed.us/database/feis/plants/graminoid/panrep/all.html", "http://www.fs.fed.us/database/feis/plants/graminoid/junbal/all.html", "http://www.fs.fed.us/database/feis/plants/forb/melspp/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/ameuta/all.html", "http://www.fs.fed.us/database/feis/plants/forb/eupcyp/all.html", "http://www.fs.fed.us/database/feis/plants/graminoid/spocom/all.html"))) {
                $html = str_ireplace(array("\n", "\t"), "", $html);
                $html = str_ireplace("BOTANICAL AND ECOLOGICAL       CHARACTERISTICS", "BOTANICAL AND ECOLOGICAL CHARACTERISTICS", $html);
                $html = str_ireplace("DISCUSSION AND QUALIFICATION   OF FIRE EFFECT", "DISCUSSION AND QUALIFICATION OF FIRE EFFECT", $html);
                $html = str_ireplace("DISCUSSION AND QUALIFICATION   OF PLANT RESPONSE", "DISCUSSION AND QUALIFICATION OF PLANT RESPONSE", $html);
                $html = str_ireplace("Seedling establishment and plant   growth", "Seedling establishment and plant growth", $html);
                $html = str_ireplace("IMPORTANCE TO LIVESTOCK    AND WILDLIFE", "IMPORTANCE TO LIVESTOCK AND WILDLIFE", $html);
                $html = str_ireplace("VALUE FOR    REHABILITATION OF DISTURBED SITES", "VALUE FOR REHABILITATION OF DISTURBED SITES", $html);
                $html = str_ireplace("DISCUSSION AND      QUALIFICATION OF FIRE EFFECT", "DISCUSSION AND QUALIFICATION OF FIRE EFFECT", $html);
                $html = str_ireplace("DISCUSSION AND      QUALIFICATION OF PLANT RESPONSE", "DISCUSSION AND QUALIFICATION OF PLANT RESPONSE", $html);
                $html = str_ireplace("Fire adaptations and plant   response to fire", "FIRE ADAPTATIONS AND PLANT RESPONSE TO FIRE", $html);
            }
            if(is_numeric(stripos($html, "<!DOCTYPE html PUBLIC"))) $rec["with_line_break"] = false;
            else                                                    $rec["with_line_break"] = true;
            
            /* //debug
            if(stripos($html, "<img ") != "" && stripos($html, "photo by") != "") $this->debug_spg[$rec['url']] = 1;
            */
            
            $this->temp_page_reference_nos = array();
            self::get_references_from_html($html);
            $orig_rec = $rec;
            if($rec = self::assemble_page_framework($rec, $html)) {
                // echo "\npass here 01\n";
            }
            else {
                $rec = self::get_descriptions_from_html($html, $orig_rec);
                // echo "\npass here 02\n";
            }
            self::get_texts($rec, $agent_ids);
        }
        $this->create_instances_from_taxon_object($rec, $reference_ids); //$reference_ids are deliberately NULL
    }

    private function clean_li_tags($text)
    {
        $k = 0;
        foreach($text as $txt)
        {
            if(substr_count($txt, '<li>') != substr_count($txt, '</li>'))
            {
                $elements = explode("\n", $txt);
                $elements = array_filter(array_map('trim', $elements)); // will trim all values of the array
                $elements = array_values($elements); // this will fix the index values 0,1,2
                $i = 0;
                foreach($elements as $element)
                {
                    $element = trim($element);
                    if(substr($element, -5) != "</li>") $elements[$i] = $element . "</li>";
                    $i++;
                }
                $text[$k] = implode("\n", $elements);
            }
            $k++;
        }
        return $text;
    }

    private function assemble_page_framework($rec, $html)
    {
        $chapters = array();
        $topics = array();
        if($html) {
            $html = str_ireplace("<ul></ul>", "", $html);
            // manual adjustments
            if(preg_match_all("/<ul>(.*?)<\/ul>/ims", $html, $arr)) {
                $temp = $arr[1];
                $temp = self::clean_li_tags($temp);
                $i = 0;
                foreach($temp as $t) {
                    // manual fix; add "</li>"
                    if(!preg_match_all("/<li>(.*?)<\/li>/ims", $t, $arr)) $t = str_ireplace("</a>", "</a></li>", $t);
                    if(preg_match_all("/<li>(.*?)<\/li>/ims", $t, $arr)) {
                        $arr[1] = array_filter(array_map('trim', $arr[1])); // will trim all values of the array
                        if($i == 0) $chapters = $arr[1];
                        else $topics[] = $arr[1];
                    }
                    else {} //echo "\n ALERT: Need to investigate: [$t]\n";
                    $i++;
                }
            }
        }
        // print_r($chapters); print_r($topics); exit; // debug
        $urls_to_use_old_script = array("http://www.fs.fed.us/database/feis/animals/reptile/crho/all.html", "http://www.fs.fed.us/database/feis/plants/forb/potnew/all.html", "http://www.fs.fed.us/database/feis/plants/tree/poptre/all.html");
        $urls_to_use_new_script = array("");
        if(count(@$topics) < 3 || in_array($rec["url"], $urls_to_use_old_script)) {
            // echo "\n Page used the OLD script " . $rec["url"] . " [" . count($topics) . "] \n";
            $this->script_count["old"]++;
            if(!in_array($rec["url"], $urls_to_use_new_script)) return false;
        } 
        else {
            // echo "\n Page used the NEW script " . $rec["url"] . " [" . count($topics) . "] \n";
            $this->script_count["new"]++;
        }
        $chapters = self::get_href_and_link_texts($chapters);
        // set all chapters' link to uppercase
        $i = 0;
        foreach($chapters as $chapter) {
            $chapters[$i]["link"] = strtoupper($chapter["link"]);
            $i++;
        }
        $topicx = array();
        foreach($topics as $topic) $topicx[] = self::get_href_and_link_texts($topic);
        
        //manual adjustment
        if($rec["url"] == "http://www.fs.fed.us/database/feis/animals/bird/boum/all.html") $topicx[4] = array_merge($topicx[4], $topicx[5]);
        
        $this->page_scores = array();
        $chapters = self::assign_topics_for_chapters($chapters, $topicx, $rec["url"]);
        $this->page_scores = array();
        $chapters = self::assign_topics_for_subchapters($chapters, $topicx, $rec["url"]);
        $this->page_scores = array();
        $chapters = self::assign_topics_for_sub_subchapters($chapters, $topicx, $rec["url"]);
        $this->page_scores = array();
        $chapters = self::assign_topics_for_sub_sub_subchapters($chapters, $topicx, $rec["url"]);

        //manual adjustment
        if($rec["url"] == "http://www.fs.fed.us/database/feis/animals/bird/apco/all.html") {
            $chapters[2]["connect"][0]["connect2"][0]["connect3"][3]["link"] = "Dispersal";
            $chapters[2]["connect"][0]["connect2"][0]["connect3"][3]["href"] = "#Dispersal";
            $chapters[2]["connect"][0]["connect2"][0]["connect3"][4]["link"] = "Survival";
            $chapters[2]["connect"][0]["connect2"][0]["connect3"][4]["href"] = "#Survival";
            $chapters[2]["connect"][0]["connect2"][0]["connect3"][5] = null;
            array_pop($chapters[2]["connect"][0]["connect2"][0]["connect3"]); // deletes ["connect3"][5], last array value of this node
        }

        // print_r($chapters); //debug
        $rec = self::generate_articles($chapters, $rec, $html);
        return $rec;
    }

    private function generate_articles($chapters, $rec, $html)
    {
        $items = array();
        foreach($chapters as $chapter) {
            $link = str_ireplace(array("\n", "\t"), "", $chapter["link"]); // not yet needed
            $items[] = array("topic" => $link, "href" => $chapter["href"]);
            if(@$chapter["connect"]) {
                foreach($chapter["connect"] as $con1) {
                    $link = str_ireplace(array("\n", "\t"), "", $con1["link"]);
                    $items[] = array("topic" => $link, "href" => $con1["href"]);
                    if(@$con1["connect2"]) {
                        foreach($con1["connect2"] as $con2) {
                            $link = str_ireplace(array("\n", "\t"), "", $con2["link"]);
                            $items[] = array("topic" => $link, "href" => $con2["href"]);
                            if(@$con2["connect3"]) {
                                foreach($con2["connect3"] as $con3) {
                                    $link = str_ireplace(array("\n", "\t"), "", $con3["link"]); // not yet needed
                                    $items[] = array("topic" => $link, "href" => $con3["href"]);
                                    if(@$con3["connect4"]) {
                                        foreach($con3["connect4"] as $con4) {
                                            $link = str_ireplace(array("\n", "\t"), "", $con4["link"]); // not yet needed
                                            $items[] = array("topic" => $link, "href" => $con4["href"]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // print_r($items); //debug
        if(is_array($items) && $items) {
            // if(count($items) != count(array_unique($items))) {} //echo "\n ALERT: with duplicate entries. " . $rec["url"] . "\n";
        }
        
        $descriptions = array();
        $i = -1;
        $items_tobe_excluded = array("INTRODUCTORY", "FEIS ABBREVIATION", "NRCS PLANT CODE", "ABBREVIATION" , "FIRE CASE STUDIES");
        $topics_to_check = array("BIOLOGICAL DATA AND HABITAT REQUIREMENTS", "DISTRIBUTION AND OCCURRENCE", "WILDLIFE DISTRIBUTION AND OCCURRENCE", "BOTANICAL AND ECOLOGICAL CHARACTERISTICS", "FIRE ECOLOGY", "MANAGEMENT CONSIDERATIONS", "FIRE EFFECTS", "FIRE EFFECTS AND USE", "FIRE EFFECTS AND MANAGEMENT");
        foreach($items as $item) {
            $i++; //echo "[$i]";
            $href1 = str_ireplace(array("#", "all.html"), "", $item["href"]);
            $href2 = @$items[$i+1]["href"];
            $href2 = str_ireplace(array("#", "all.html"), "", $href2);
            $topic = trim($item["topic"]);
            
            // manual adjustment
            if($topic == "155 LIFE FORM") $topic = "LIFE FORM";
            if(in_array($topic, array("170 PLANT ASSOCIATIONS", "65 PLANT ASSOCIATIONS", "49 PLANT ASSOCIATIONS"))) $topic = "PLANT ASSOCIATIONS";
            
            // echo "\n $topic --- $href1 -- $href2";
            if(in_array($topic, $items_tobe_excluded)) continue;

            $to_be_excluded = false;
            if(in_array($topic, $topics_to_check)) {
                $to_be_excluded = self::check_if_topic_will_be_excluded($topic, $items);
                if($to_be_excluded) continue;
            }

            $html = str_ireplace(array(' id="' . $href1 . '"', ' id="' . $href2 . '"'), "", $html);
            $html = str_ireplace(array('id="' . $href1 . '" ', 'id="' . $href2 . '" '), "", $html);
            $href1 = str_ireplace("/", "\/", $href1);
            $href2 = str_ireplace("/", "\/", $href2);
            $href1 = str_ireplace("(", "\(", $href1);
            $href2 = str_ireplace("(", "\(", $href2);
            $href1 = str_ireplace(")", "\)", $href1);
            $href2 = str_ireplace(")", "\)", $href2);
            
            if(preg_match("/<a name\=\"$href1\">(.*?)<a name\=\"$href2\">/ims", $html, $arr))
            {
                // seemingly new topics in lower case, but already exist as upper case
                $small_topic = strtolower($topic);
                if(in_array($small_topic, array("diet", "life history"))) $topic = strtoupper($topic);
                // end -

                $arr[1] = str_ireplace(array("$topic:", "$topic :"), "", $arr[1]);
                $descriptions[$topic] = $arr[1];

                if($topic == "LIFE FORM") $lifeform = $descriptions[$topic];

                // APPENDIX: FIRE REGIME TABLE
                if(in_array($href1, array("APPENDIX: FIRE REGIME TABLE", "AppendixFireRegimeTable"))) {
                    $link_text = "Follow this link to the U.S. Forest Service Fire Effects Information Service to see a table with fire regime information that may be relevant to habitats in which this species occurs";
                    $link = false;
                    if($href1 == "APPENDIX: FIRE REGIME TABLE") $link = $rec['url'] . "#APPENDIX: FIRE REGIME TABLE";
                    elseif($href1 == "AppendixFireRegimeTable") $link = $rec['url'] . "#AppendixFireRegimeTable";
                    if($link) $descriptions["APPENDIX: FIRE REGIME TABLE"] = "<a href='" . $link . "'>$link_text</a>";
                }

                // authorship and citation
                if($topic == "AUTHORSHIP AND CITATION") $descriptions[$topic] = self::get_authorship_citation(self::clean_str(strip_tags($arr[1]), true));

                // ORDER, CLASS
                if(in_array($topic, array("ORDER", "CLASS"))) {
                    $rec[strtolower($topic)] = self::clean_str(strip_tags($arr[1]), true);
                    unset($descriptions[$topic]);
                    // echo "\n $topic: [" . $rec[strtolower($topic)] . "] \n";
                }
                
                // SYNONYMS, COMMON NAMES
                if(in_array($topic, array("SYNONYMS", "COMMON NAMES")))
                {
                    if($topic == "COMMON NAMES" && in_array($rec["url"], $this->exclude_vernaculars)) {}
                    else {
                        $temp = self::clean_str(strip_tags($arr[1], "<br><a>"));
                        $temp = self::further_clean($temp, $topic, $html);
                        $descriptions[$topic] = $temp;
                    }
                }

                // TAXONOMY
                if($topic == "TAXONOMY") {
                    $strings_2be_removed = array("\n", "</a>", "</span>", "</b>", "<br>");
                    $temp = self::remove_first_part_of_string($strings_2be_removed, $arr[1]);
                    $temp = utf8_encode($temp);
                    if(Functions::is_utf8($temp)) $descriptions[$topic] = $temp;
                    else $descriptions[$topic] = "";
                }

            }
        }
        // echo "\n count: " . count($descriptions) . "\n";
        $rec['texts'] = $descriptions;
        return $rec;
    }

    private function check_if_topic_will_be_excluded($topic, $items)
    {
        if(isset($this->topics[$topic])) {
            foreach($this->topics[$topic] as $t) {
                foreach($items as $item) {
                    if($t == $item["topic"]) {
                        // echo "\n ALERT: excluded: $topic [$t]==[" . $item["topic"] . "]\n";
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function assign_topics_for_sub_sub_subchapters($chapters, $topics, $url)
    {
        $j = -1;
        foreach($chapters as $chapter) {
            $j++;
            $k = -1;
            // echo "\n" . $chapter["link"];
            if(!@$chapter["connect"]) continue;
            foreach($chapter["connect"] as $connect) {
                $k++;
                if(!@$connect["connect2"]) continue;
                $k2 = -1;
                foreach($connect["connect2"] as $c) {
                    $k2++;
                    if(!@$c["connect3"]) continue;
                    $k3 = 0;
                    foreach($c["connect3"] as $c2) {
                        // echo "\n - " . $c2["link"];
                        $subtopic = $c2["link"];
                        if(isset($this->sub_sub_subtopics[$subtopic])) {
                            $arr = self::topics_indexkey_for_chapter($this->sub_sub_subtopics[$subtopic], $topics);
                            $topics_indexkey_for_chapter = $arr[0];
                            $score = $arr[1];
                            $scorevalue = $arr[2];
                            if(is_numeric($topics_indexkey_for_chapter)) {
                                $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3"][$k3]["url"] = $url;
                                $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3"][$k3]["score"] = $scorevalue;
                                if($scorevalue >= count($topics[$topics_indexkey_for_chapter])/2) {
                                    $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3"][$k3]["connect4_score"] = $score;
                                    $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3"][$k3]["connect4"] = $topics[$topics_indexkey_for_chapter];
                                }
                                else {
                                    $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3"][$k3]["connect4_score_x"] = $score;
                                    $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3"][$k3]["connect4_x"] = $topics[$topics_indexkey_for_chapter];
                                }
                            }
                        }
                        $k3++;
                    }
                }
            }
        }
        return $chapters;
    }

    private function assign_topics_for_sub_subchapters($chapters, $topics, $url)
    {
        $not_allowed = array();
        $not_allowed[] = array("main" => "PREFERRED HABITAT", "connect" => "Other factors"); // http://www.fs.fed.us/database/feis/animals/mammal/odhe/all.html#PreferredHabitat
        // this means that under PREFERRED HABITAT, if it will have a sub-topic 'Other factors', then 'Other factors' won't have onward links
        $j = -1;
        foreach($chapters as $chapter)
        {
            $j++;
            $k = -1;
            // echo "\n" . $chapter["link"];
            if(!@$chapter["connect"]) continue;
            foreach($chapter["connect"] as $connect)
            {
                $k++;
                if(!@$connect["connect2"]) continue;
                $k2 = 0;
                foreach($connect["connect2"] as $c)
                {
                    // echo "\n - " . $c["link"];
                    $subtopic = $c["link"];
                    if(isset($this->sub_subtopics[$subtopic]) && !in_array(array("main" => $connect["link"], "connect" => $subtopic), $not_allowed))
                    {
                        // echo "\n === " . $connect["link"] . " === ". $subtopic;
                        $arr = self::topics_indexkey_for_chapter($this->sub_subtopics[$subtopic], $topics);
                        $topics_indexkey_for_chapter = $arr[0];
                        $score = $arr[1];
                        $scorevalue = $arr[2];
                        if(is_numeric($topics_indexkey_for_chapter)) 
                        {
                            $chapters[$j]["connect"][$k]["connect2"][$k2]["url"] = $url;
                            $chapters[$j]["connect"][$k]["connect2"][$k2]["score"] = $scorevalue;
                            if($scorevalue >= count($topics[$topics_indexkey_for_chapter])/2)
                            {
                                $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3_score"] = $score;
                                $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3"] = $topics[$topics_indexkey_for_chapter];
                            }
                            else
                            {
                                $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3_score_x"] = $score;
                                $chapters[$j]["connect"][$k]["connect2"][$k2]["connect3_x"] = $topics[$topics_indexkey_for_chapter];
                            }
                        }
                    }
                    $k2++;
                }
            }
        }
        return $chapters;
    }
    
    private function assign_topics_for_subchapters($chapters, $topics, $url)
    {
        $j = -1;
        foreach($chapters as $chapter)
        {
            $j++;
            $k = 0;
            // echo "\n" . $chapter["link"];
            if(!@$chapter["connect"]) continue;
            foreach($chapter["connect"] as $connect)
            {
                // echo "\n - " . $connect["link"];
                $subtopic = $connect["link"];
                if(isset($this->subtopics[$subtopic]))
                {
                    $arr = self::topics_indexkey_for_chapter($this->subtopics[$subtopic], $topics);
                    $topics_indexkey_for_chapter = $arr[0];
                    $score = $arr[1];
                    $scorevalue = $arr[2];
                    if(is_numeric($topics_indexkey_for_chapter)) 
                    {
                        $chapters[$j]["connect"][$k]["url"] = $url;
                        $chapters[$j]["connect"][$k]["score"] = $scorevalue;
                        if($scorevalue >= count($topics[$topics_indexkey_for_chapter])/2)
                        {
                            $chapters[$j]["connect"][$k]["connect2_score"] = $score;
                            $chapters[$j]["connect"][$k]["connect2"] = $topics[$topics_indexkey_for_chapter];
                        }
                        else
                        {
                            $chapters[$j]["connect"][$k]["connect2_score_x"] = $score;
                            $chapters[$j]["connect"][$k]["connect2_x"] = $topics[$topics_indexkey_for_chapter];
                        }
                    }
                }
                $k++;
            }
        }
        return $chapters;
    }

    private function assign_topics_for_chapters($chapters, $topics, $url)
    {
        $j = 0;
        foreach($chapters as $chapter)
        {
            // echo "\n --- " . $chapter["link"];
            $index = $chapter["link"];
            if(!isset($this->topics[$index])) 
            {
                // echo "\n ALERT: Topics for chapter [$index] is not yet initialized.\n";
                continue;
            }
            $arr = self::topics_indexkey_for_chapter($this->topics[$index], $topics);
            $topics_indexkey_for_chapter = $arr[0];
            $score = $arr[1];
            if(is_numeric($topics_indexkey_for_chapter)) $chapters[$j]["connect"] = $topics[$topics_indexkey_for_chapter];
            $j++;
        }
        return $chapters;
    }

    private function topics_indexkey_for_chapter($topic_list, $topics)
    {
        $scores = array();
        $i = 0;
        foreach($topics as $topic)
        {
            $continue = true;
            foreach($topic as $t)
            {
                if(in_array($t["link"], $topic_list))
                {
                    if(isset($scores[$i])) $scores[$i]++;
                    else $scores[$i] = 1;
                }
            }
            $i++;
        }
        // echo "\n";
        if($scores)
        {
            $this->page_scores[] = $scores;
            $maxs = array_keys($scores, max($scores)); // this will get the index of the array with the highest value => $maxs[0]
            return array($maxs[0], $scores, $scores[$maxs[0]]);
        }
    }

    private function get_href_and_link_texts($items)
    {
        $itemz = array();
        foreach($items as $item)
        {
            $item = strip_tags($item, "<a>");
            $item = str_ireplace(array("\n", "\t"), "", $item);
            $href = false;
            $link = false;
            if(preg_match("/href\=\"(.*?)\">/ims", $item, $arr)) $href = $arr[1];
            elseif(preg_match("/href\=\'(.*?)\'>/ims", $item, $arr)) $href = $arr[1];
            if(preg_match("/\">(.*?)<\/a>/ims", $item, $arr)) $link = $arr[1];
            elseif(preg_match("/\'>(.*?)<\/a>/ims", $item, $arr)) $link = $arr[1];
            if($href && $link) $itemz[] = array("link" => $link, "href" => $href);
        }
        return $itemz;
    }

    private function get_authorship_citation($authorship_citation)
    {
        $strings_2be_removed = array("</a>", "</b>", "<br>");
        $authorship_citation = self::remove_first_part_of_string($strings_2be_removed, $authorship_citation);
        $authorship_citation = self::remove_last_part_of_string(array("<br>", "["), $authorship_citation);
        $authorship_citation .= " Available: http://www.fs.fed.us/database/feis/";
        $pos = stripos($authorship_citation, "var months");
        if(is_numeric($pos)) $authorship_citation = trim(substr($authorship_citation, 0, $pos));
        $authorship_citation = self::remove_last_part_of_string(array("["), $authorship_citation);
        // echo "\n meron authorship and citation:\n[$authorship_citation]\n";
        return $authorship_citation;
    }
    
    private function get_descriptions_from_html($html, $rec)
    {
        $descriptions = array();
        if(preg_match("/AUTHORSHIP AND CITATION\:(.*?)Available\:/ims", $html, $arr))
        {
            $authorship_citation = self::clean_str(strip_tags($arr[1]), true);
            $authorship_citation = self::get_authorship_citation($authorship_citation);
            $descriptions["AUTHORSHIP AND CITATION"] = $authorship_citation;
        }
        else echo "\n ALERT: no authorship and citation: " . $rec['url'];
        if($rec["kingdom"] == "Animalia")
        {
            $html = str_ireplace("ORDER :", "ORDER:", $html);
            $html = str_ireplace("CLASS :", "CLASS:", $html);
            if(preg_match("/ORDER\:(.*?)<a name\=\"CLASS\"/ims", $html, $arr) ||
               preg_match("/ORDER\:(.*?)<b>CLASS\:/ims", $html, $arr)) $order = trim(strip_tags($arr[1]));
            if(isset($order))
            {
                if(preg_match("/(.*?)\(/ims", $order, $arr)) $order = trim($arr[1]); //remove parenthesis
                // echo "\n order:[$order]\n";
                $rec["order"] = $order;
            }
            if(isset($rec["order"]))
            {
                if($rec["order"] == "Caudata") $rec["class"] = "Amphibian";
                elseif(in_array($rec["order"], array("Passeriformes", "Piciformes", "Ciconiiformes", "Galliformes"))) $rec["class"] = "Bird";
                elseif(in_array($rec["order"], array("Artiodactyla", "Carnivora"))) $rec["class"] = "Mammal";
            }
            else
            {
                if(preg_match("/CLASS\:(.*?)<a name\=\"FEDERAL LEGAL STATUS\"/ims", $html, $arr) ||
                   preg_match("/CLASS\:(.*?)<a name\=\"FederalLegalStatus\"/ims", $html, $arr) ||
                   preg_match("/CLASS\:(.*?)<b>FEDERAL LEGAL STATUS/ims", $html, $arr) ||
                   preg_match("/CLASS\:(.*?)<a name\=\"DistributionAndOccurrence\"/ims", $html, $arr) ||
                   preg_match("/CLASS\:(.*?)<a name\=\"DISTRIBUTION AND OCCURRENCE\"/ims", $html, $arr)
                   ) $class = trim(strip_tags($arr[1]));
                   if(isset($class))
                   {
                       if(preg_match("/(.*?)\(/ims", $class, $arr)) $class = trim($arr[1]); //remove parenthesis
                       $rec["class"] = $class;
                   }
            }
            if(isset($rec["class"])) {} //echo "\n class:" . $rec["class"];
        }

        // taxonomy
        if(preg_match("/TAXONOMY \:(.*?)<b>/ims", $html, $arr)) $taxonomy = $arr[1];
        else
        {
            if(preg_match("/TAXONOMY\:(.*?)<span/ims", $html, $arr)) $taxonomy = $arr[1];
            elseif(preg_match("/TAXONOMY\:(.*?)<em/ims", $html, $arr)) $taxonomy = $arr[1];
            elseif(preg_match("/TAXONOMY\:(.*?)<br><br>/ims", $html, $arr)) $taxonomy = $arr[1];
            elseif(preg_match("/TAXONOMY\:(.*?)<b>/ims", $html, $arr)) $taxonomy = $arr[1];
        }
        if(isset($taxonomy))
        {
            $taxonomy = utf8_encode($taxonomy);
            if(Functions::is_utf8($taxonomy)) $descriptions["TAXONOMY"] = $taxonomy;
        }

        // SYNONYMS
        if(preg_match("/SYNONYMS \:(.*?)<b>/ims", $html, $arr)) $synonyms = self::clean_str(strip_tags($arr[1], "<br><a>"));
        elseif(preg_match("/SYNONYMS\:(.*?)<br><br>/ims", $html, $arr)) $synonyms = self::clean_str(strip_tags($arr[1], "<br><a>"));
        if(isset($synonyms)) $synonyms = self::further_clean($synonyms, "SYNONYMS", $html);

        if(isset($synonyms)) $descriptions["SYNONYMS"] = $synonyms;
        else {} //echo "\n -no synonyms- \n"; //debug

        // COMMON NAMES
        if(preg_match("/COMMON NAMES \:(.*?)<b>/ims", $html, $arr)) $comnames = self::clean_str(strip_tags($arr[1], "<br><a>"));
        elseif(preg_match("/COMMON NAMES\:(.*?)<br><br>/ims", $html, $arr)) $comnames = self::clean_str(strip_tags($arr[1], "<br><a>"));
        elseif(preg_match("/COMMON NAMES\:(.*?)<\/td/ims", $html, $arr)) $comnames = self::clean_str(strip_tags($arr[1], "<br><a>"));
        if(isset($comnames)) 
        {
            $comnames = self::further_clean($comnames, "COMMON NAMES", $html);
            $descriptions["COMMON NAMES"] = $comnames;
        }
        else {} //echo "\n -no comnames- \n"; //debug

        if(in_array($rec["url"], array("http://www.fs.fed.us/database/feis/plants/shrub/ceaoph/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/bernev/all.html")))
        {
            if(preg_match("/LIFE FORM\:(.*?)<hr/ims", $html, $arr))
            {
                $lifeform = $arr[1];
                if(!(stripos($lifeform, "federal legal status") != "" || stripos($lifeform, "other status") != "")) $descriptions["LIFE FORM"] = $lifeform;
            }
        }
        else
        {
            $html = str_ireplace("<b>LIFE FORM :", "LIFE FORM:", $html);
            if(preg_match("/LIFE FORM\:(.*?)<br><br>/ims", $html, $arr)) $lifeform = $arr[1];
            elseif(preg_match("/LIFE FORM\:(.*?)<b>/ims", $html, $arr))  $lifeform = $arr[1];
            if(isset($lifeform))
            {
                $descriptions["LIFE FORM"] = $lifeform;
                if(stripos($lifeform, "federal legal status") != "" || stripos($lifeform, "other status") != "")
                {
                    $descriptions["LIFE FORM"] = "";
                    if(preg_match("/(.*?)<a name\=/ims", $lifeform, $arr))
                    {
                        $lifeform = $arr[1];
                        $descriptions["LIFE FORM"] = $lifeform;
                        if(stripos($lifeform, "federal legal status") != "" || stripos($lifeform, "other status") != "")
                        {
                            $descriptions["LIFE FORM"] = "";
                            if(preg_match("/(.*?)<b>/ims", $lifeform, $arr))
                            {
                                $lifeform = $arr[1];
                                if(!(stripos($lifeform, "federal legal status") != "" || stripos($lifeform, "other status") != "")) $descriptions["LIFE FORM"] = $lifeform;
                            }
                        }
                    }
                }
            }
        }

        $html = str_ireplace("FEDERAL LEGAL STATUS :", "FEDERAL LEGAL STATUS:", $html);
        if($rec["kingdom"] == "Plantae")
        {
            if(preg_match("/FEDERAL LEGAL STATUS\:(.*?)<br><br>/ims", $html, $arr)) $federal_stat = $arr[1];
            elseif(preg_match("/FEDERAL LEGAL STATUS\:(.*?)<b>/ims", $html, $arr))  $federal_stat = $arr[1];
            if(isset($federal_stat))
            {
                $descriptions["FEDERAL LEGAL STATUS"] = $federal_stat;
                if(stripos($federal_stat, "other status") != "")
                {
                    $descriptions["FEDERAL LEGAL STATUS"] = "";
                    if(preg_match("/(.*?)<a name\=/ims", $federal_stat, $arr)) 
                    {
                        $federal_stat = $arr[1];
                        $descriptions["FEDERAL LEGAL STATUS"] = $federal_stat;
                        if(stripos($federal_stat, "other status") != "")
                        {
                            $descriptions["FEDERAL LEGAL STATUS"] = "";
                            if(preg_match("/(.*?)<b>/ims", $federal_stat, $arr))
                            {
                                $federal_stat = $arr[1];
                                if(stripos($federal_stat, "other status") == "") $descriptions["FEDERAL LEGAL STATUS"] = $federal_stat;
                            }
                            
                        }
                    }
                }
            }
            if(isset($descriptions["FEDERAL LEGAL STATUS"]))
            {
                if(stripos($descriptions["FEDERAL LEGAL STATUS"], "No special status") != "") $descriptions["FEDERAL LEGAL STATUS"] = "";
                if(stripos($descriptions["FEDERAL LEGAL STATUS"], "No legal status") != "") $descriptions["FEDERAL LEGAL STATUS"] = "";
            }
        } // kingdom = Plantae

        $html = str_ireplace("OTHER STATUS :", "OTHER STATUS:", $html);
        if(preg_match("/OTHER STATUS\:(.*?)<a name\=/ims", $html, $arr)) $other_stat = $arr[1];
        if(isset($other_stat)) {
            $descriptions["OTHER STATUS"] = $other_stat;
            if(stripos($other_stat, "Management considerations") != "") //for http://www.fs.fed.us/database/feis/animals/bird/piar/all.html
            {
                $descriptions["OTHER STATUS"] = "";
                if(preg_match("/(.*?)Management considerations\:/ims", $other_stat, $arr)) $descriptions["OTHER STATUS"] = $arr[1];
            }
        }
        
        /* DISTRIBUTION AND OCCURRENCE */
        $html = str_ireplace("GENERAL DISTRIBUTION :", "GENERAL DISTRIBUTION:", $html);
        if(in_array($rec["url"], array("http://www.fs.fed.us/database/feis/plants/shrub/ceaoph/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/bernev/all.html"))) {
            if(preg_match("/GENERAL DISTRIBUTION\:(.*?)United States\:/ims", $html, $arr)) $general_dist = $arr[1];
            elseif(preg_match("/GENERAL DISTRIBUTION\:(.*?)States\:/ims", $html, $arr)) $general_dist = $arr[1];
        }
        else {
            if(preg_match("/GENERAL DISTRIBUTION\:(.*?)<b>ECOSYSTEMS/ims", $html, $arr)) $general_dist = $arr[1];
            elseif(preg_match("/GENERAL DISTRIBUTION\:(.*?)<span/ims", $html, $arr)) $general_dist = $arr[1];
        }
        if(isset($general_dist)) $descriptions["GENERAL DISTRIBUTION"] = $general_dist;

        if(preg_match("/ECOSYSTEMS \:(.*?)<b>STATES/ims", $html, $arr) || preg_match("/ECOSYSTEMS\:(.*?)<a name\=\"STATES\"/ims", $html, $arr) || preg_match("/<a name\=\"ECOSYSTEMS\">(.*?)<a name\=\"STATES/ims", $html, $arr)) 
        {
            $ecosystems = $arr[1];
            $descriptions["ECOSYSTEMS"] = $ecosystems;
        }

        if(in_array($rec["url"], array("http://www.fs.fed.us/database/feis/plants/shrub/ceaoph/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/bernev/all.html"))) {
            if(preg_match("/states\:(.*?)Site Characteristics\:/ims", $html, $arr)) $states = $arr[1];
        }
        else {
            if(preg_match("/STATES \:(.*?)<b>BLM/ims", $html, $arr) || preg_match("/STATES\:(.*?)<a name\=\"BLM PHYSIOGRAPHIC REGIONS\"/ims", $html, $arr) || //animals
               preg_match("/<a name\=\"STATES\/PROVINCES\">(.*?)<a name\=\"BLM PHYSIOGRAPHIC REGIONS\"/ims", $html, $arr)) $states = $arr[1];
        }
        if(isset($states)) {
            $states = str_ireplace("(key to state/province abbreviations)", "", $states);
            $descriptions["STATES"] = $states;
        }
        
        if(preg_match("/BLM PHYSIOGRAPHIC REGIONS \:(.*?)<b>KUCHLER/ims", $html, $arr) || preg_match("/BLM PHYSIOGRAPHIC REGIONS\:(.*?)<a name\=\"KUCHLER PLANT ASSOCIATIONS\"/ims", $html, $arr) || //animals
           preg_match("/<a name\=\"BLM PHYSIOGRAPHIC REGIONS\">(.*?)<a name\=\"KUCHLER PLANT ASSOCIATIONS\"/ims", $html, $arr)) 
        {
            $blm_regions = $arr[1];
            $descriptions["BLM PHYSIOGRAPHIC REGIONS"] = $blm_regions;
        }
        
        if(preg_match("/KUCHLER PLANT ASSOCIATIONS \:(.*?)<b>SAF/ims", $html, $arr) || preg_match("/KUCHLER PLANT ASSOCIATIONS\:(.*?)<a name\=\"SAF COVER TYPES\"/ims", $html, $arr) || //animals
           preg_match("/<a name\=\"KUCHLER PLANT ASSOCIATIONS\">(.*?)<a name\=\"SAF COVER TYPES\"/ims", $html, $arr))
        {
            $kuchler_assoc = $arr[1];
            $descriptions["KUCHLER PLANT ASSOCIATIONS"] = $kuchler_assoc;
        }
        
        if(preg_match("/SAF COVER TYPES \:(.*?)<b>SRM/ims", $html, $arr) || preg_match("/SAF COVER TYPES\:(.*?)<a name\=\"SRM \(RANGELAND\) COVER TYPES\"/ims", $html, $arr) || //animals
           preg_match("/<a name\=\"SAF COVER TYPES\">(.*?)<a name\=\"SRM \(RANGELAND\) COVER TYPES\"/ims", $html, $arr)) 
        {
            $saf_types = $arr[1];
            $descriptions["SAF COVER TYPES"] = $saf_types;
        }
        
        if(preg_match("/SRM \(RANGELAND\) COVER TYPES \:(.*?)<b>HABITAT TYPES AND PLANT COMMUNITIES/ims", $html, $arr))  $srm_types = $arr[1];
        elseif(preg_match("/SRM \(RANGELAND\) COVER TYPES \:(.*?)<b>PLANT COMMUNITIES/ims", $html, $arr))                $srm_types = $arr[1];
        elseif(preg_match("/SRM \(RANGELAND\) COVER TYPES\:(.*?)<a name\=\"PLANT COMMUNITIES\"/ims", $html, $arr))       $srm_types = $arr[1]; //animals
        elseif(preg_match("/<a name\=\"SRM \(RANGELAND\) COVER TYPES\">(.*?)<a name\=\"HABITAT TYPES/ims", $html, $arr)) $srm_types = $arr[1];
        if(isset($srm_types)) $descriptions["SRM (RANGELAND) COVER TYPES"] = $srm_types;

        if(in_array($rec["url"], array("http://www.fs.fed.us/database/feis/plants/shrub/ceaoph/all.html", "http://www.fs.fed.us/database/feis/plants/shrub/bernev/all.html")))
        {
            if(preg_match("/plant communities\:(.*?)<a name\=/ims", $html, $arr)) $habitat_types = $arr[1];
        }
        else
        {
            if(preg_match("/HABITAT TYPES AND PLANT COMMUNITIES\:(.*?)<a name\=\"BOTANICAL AND ECOLOGICAL CHARACTERISTICS/ims", $html, $arr)) $habitat_types = $arr[1];
            elseif(preg_match("/HABITAT TYPES AND PLANT COMMUNITIES\:(.*?)<a name\=\"BotanicalAndEcologicalCharacteristics/ims", $html, $arr)) $habitat_types = $arr[1];
            elseif(preg_match("/HABITAT TYPES AND PLANT COMMUNITIES \:(.*?)<a name\=\"MANAGEMENT CONSIDERATIONS/ims", $html, $arr)) $habitat_types = $arr[1];
        }
        if(isset($habitat_types)) 
        {
            if(is_numeric(stripos($habitat_types, '<a name="MANAGEMENT CONSIDERATIONS"></a>')))
            {
                if(preg_match("/HABITAT TYPES AND PLANT COMMUNITIES\:(.*?)<a name\=\"MANAGEMENT CONSIDERATIONS/ims", $html, $arr)) $habitat_types = $arr[1];
            }
            $descriptions["HABITAT TYPES AND PLANT COMMUNITIES"] = $habitat_types;   
        }

        if($rec["kingdom"] == "Animalia")
        {
            //animals
            $html = str_ireplace("<b>PLANT COMMUNITIES :", "<b>PLANT COMMUNITIES:", $html);
            if(preg_match("/PLANT COMMUNITIES\:(.*?)<a name\=\"BIOLOGICAL DATA AND HABITAT REQUIREMENTS\"/ims", $html, $arr) || preg_match("/PLANT COMMUNITIES\:(.*?)<a name\=\"BiologicalDataAndHabitatRequirements\"/ims", $html, $arr) || preg_match("/PLANT COMMUNITIES\:(.*?)<a name\=\"BIOLOGICAL DATA AND HABITAT\"/ims", $html, $arr))
            {
                $plant_comm = $arr[1];
                $descriptions["PLANT COMMUNITIES"] = $plant_comm;
            }

            /* BIOLOGICAL DATA AND HABITAT REQUIREMENTS */ //animals
            // LIFE HISTORY subtopics
            $html_utf8 = utf8_encode($html);
            $html_utf8 = str_ireplace("Phenology</font>", "Phenology", $html_utf8);
            if(preg_match("/Ontogeny<\/b>(.*?)Phenology<\/b>/ims", $html_utf8, $arr))
            {
                $ontogeny = $arr[1] . "xxx";
                if(preg_match("/Mating:(.*?)Reproductive rates:/ims", $ontogeny, $arr)) $descriptions["Mating"] = $arr[1];
                if(preg_match("/Reproductive rates:(.*?)Gestation and litter size:/ims", $ontogeny, $arr)) $descriptions["Reproductive rates"] = $arr[1];
                if(preg_match("/Gestation and litter size:(.*?)Development:/ims", $ontogeny, $arr)) $descriptions["Gestation and litter size"] = $arr[1];
                if(preg_match("/Development:(.*?)Survival rates and mortality:/ms", $ontogeny, $arr)) $descriptions["Development"] = $arr[1];
                if(preg_match("/Survival rates and mortality:(.*?)xxx/ims", $ontogeny, $arr)) $descriptions["Survival rates and mortality"] = $arr[1];
            }
            if(preg_match("/Phenology<\/b>(.*?)Home range\:/ims", $html_utf8, $arr)) $descriptions["Phenology"] = $arr[1];
            if(preg_match("/Home range\:(.*?)<a name\=\"PREFERRED HABITAT\"/ims", $html_utf8, $arr)) $descriptions["Home range"] = $arr[1];
            if(preg_match("/Physical description\:(.*?)<a name\=\"Physiology\">/ims", $html, $arr)) $descriptions["Physical description"] = $arr[1];
            elseif(preg_match("/Physical description\:(.*?)<a name\=\"CourtshipAndMating\">/ims", $html, $arr)) $descriptions["Physical description"] = $arr[1];
            if(preg_match("/Physiology\:(.*?)<a name\=\"CourtshipAndMating\">/ims", $html, $arr)) $descriptions["Physiology"] = $arr[1];
            if(preg_match("/Courtship and mating\:(.*?)<a name\=\"ReproductionAndDevelopment\">/ims", $html, $arr)) $descriptions["Courtship and mating"] = $arr[1];
            if(preg_match("/Reproduction and development\:(.*?)<a name\=\"SocialBehavior\">/ims", $html, $arr)) $descriptions["Reproduction and development"] = $arr[1];
            elseif(preg_match("/Reproduction and development\:(.*?)<a name\=\"Growth\">/ims", $html, $arr)) $descriptions["Reproduction and development"] = $arr[1];
            if(preg_match("/Social behavior\:(.*?)<a name\=\"HomeRangeAndMovements\">/ims", $html, $arr)) $descriptions["Social behavior"] = $arr[1];
            elseif(preg_match("/Social behavior\:(.*?)<a name\=\"MovementsAndHomeRange\">/ims", $html, $arr)) $descriptions["Social behavior"] = $arr[1];
            if(preg_match("/Home range and movements\:(.*?)<a name\=\"PopulationDensity\">/ims", $html, $arr)) $descriptions["Home range and movements"] = $arr[1];
            elseif(preg_match("/Movements and home range\:(.*?)<a name\=\"PopulationDensity\">/ims", $html, $arr)) $descriptions["Home range and movements"] = $arr[1];
            elseif(preg_match("/Home range and movement\:(.*?)<a name\=\"PREFERRED HABITAT\">/ims", $html, $arr)) $descriptions["Home range and movements"] = $arr[1];
            if(preg_match("/Population density\:(.*?)<a name\=\"Survival\">/ims", $html, $arr)) $descriptions["Population density"] = $arr[1];
            elseif(preg_match("/Population density\:(.*?)<a name\=\"LifeSpanAndSurvival\">/ims", $html, $arr)) $descriptions["Population density"] = $arr[1];
            elseif(preg_match("/Population density\:(.*?)<a name\=\"Home Range and Movements\">/ims", $html, $arr)) $descriptions["Population density"] = $arr[1];
            if(preg_match("/Survival\:(.*?)<a name\=\"LifeSpan\">/ims", $html, $arr)) $descriptions["Survival"] = $arr[1];
            elseif(preg_match("/Survival\:(.*?)<a name\=\"Population Density\">/ims", $html, $arr)) $descriptions["Survival"] = $arr[1];
            if(preg_match("/Life span\:(.*?)<a name\=\"DiseasesAndSourcesOfMortality\">/ims", $html, $arr)) $descriptions["Life span"] = $arr[1];
            if(preg_match("/Life span and survival\:(.*?)<a name\=\"Diet\">/ims", $html, $arr)) $descriptions["Life span and survival"] = $arr[1];
            /* other life history subtopics e.g http://www.fs.fed.us/database/feis/animals/reptile/gopo/all.html - Daily activity, Annual activity, Social interactions, Growth, Longevity */
            if(preg_match("/Daily activity\:(.*?)<a name\=\"Annual Activity\">/ims", $html, $arr)) $descriptions["Daily activity"] = $arr[1];
            if(preg_match("/Annual activity\:(.*?)<a name\=\"Social Interactions\">/ims", $html, $arr)) $descriptions["Annual activity"] = $arr[1];
            if(preg_match("/Social interactions\:(.*?)<a name\=\"Reproduction and Development\">/ims", $html, $arr)) $descriptions["Social interactions"] = $arr[1];
            if(preg_match("/Growth\:(.*?)<a name\=\"Longevity\">/ims", $html, $arr)) $descriptions["Growth"] = $arr[1];
            if(preg_match("/Longevity\:(.*?)<a name\=\"Survival\">/ims", $html, $arr)) $descriptions["Longevity"] = $arr[1];
            /* other life history subtopics e.g http://www.fs.fed.us/database/feis/animals/reptile/goag/all.html - Longevity and survivorship, Maturation, Reproduction, Annual cycle, Activity and movement, Density and home range */ 
            if(preg_match("/Longevity and survivorship<\/a><\/font>(.*?)<a name\=\"Maturation\">/ims", $html, $arr)) $descriptions["Longevity and survivorship"] = $arr[1];
            if(preg_match("/Maturation<\/a><\/font>(.*?)<a name\=\"Reproduction\">/ims", $html, $arr)) $descriptions["Maturation"] = $arr[1];
            if(preg_match("/Reproduction<\/a><\/font>(.*?)<a name\=\"Annual Cycle\">/ims", $html, $arr)) $descriptions["Reproduction"] = $arr[1];
            if(preg_match("/Annual cycle<\/a><\/font>(.*?)<a name\=\"Activity and movement\">/ims", $html, $arr)) $descriptions["Annual cycle"] = $arr[1];
            elseif(preg_match("/Annual cycle<\/a>\:(.*?)<a name\=\"Activity and movement\">/ims", $html, $arr)) $descriptions["Annual cycle"] = $arr[1];
            if(preg_match("/Activity and movement<\/a><\/font>(.*?)<a name\=\"Density and home range\">/ims", $html, $arr)) $descriptions["Activity and movement"] = $arr[1];
            if(preg_match("/Density and home range<\/a><\/font>(.*?)<a name\=\"PREFERRED HABITAT\">/ims", $html, $arr)) $descriptions["Density and home range"] = $arr[1];
            //animals
            if(!(@$descriptions["Mating"] || @$descriptions["Reproductive rates"] || @$descriptions["Gestation and litter size"] ||
                 @$descriptions["Development"] || @$descriptions["Survival rates and mortality"] ||
                 @$descriptions["Ontogeny"] || @$descriptions["Phenology"] || @$descriptions["Home range"] ||
                 @$descriptions["Physical description"] || @$descriptions["Physiology"] ||
                 @$descriptions["Courtship and mating"] || @$descriptions["Reproduction and development"] || 
                 @$descriptions["Social behavior"] || @$descriptions["Home range and movements"] || @$descriptions["Population density"] || 
                 @$descriptions["Survival"] || @$descriptions["Life span"] || @$descriptions["Life span and survival"] ||
                 @$descriptions["Daily activity"] || @$descriptions["Annual activity"] || @$descriptions["Social interactions"] ||
                 @$descriptions["Growth"] || @$descriptions["Longevity"] ||
                 @$descriptions["Longevity and survivorship"] || @$descriptions["Maturation"] || @$descriptions["Reproduction"] ||
                 @$descriptions["Annual cycle"] || @$descriptions["Activity and movement"] || @$descriptions["Density and home range"]))
            {
                if(preg_match("/LIFE HISTORY\:(.*?)<a name\=\"DiseasesAndSourcesOfMortality\"/ims", $html, $arr)) $life_history = $arr[1];
                elseif(preg_match("/LIFE HISTORY\:(.*?)<a name\=\"Diseases And Sources Of Mortality\"/ims", $html, $arr)) $life_history = $arr[1];
                elseif(preg_match("/LIFE HISTORY\:(.*?)<a name\=\"Diet\"/ims", $html, $arr)) $life_history = $arr[1]; //http://www.fs.fed.us/database/feis/animals/bird/apco/all.html
                elseif(preg_match("/LIFE HISTORY\:(.*?)<a name\=\"PREFERRED HABITAT\"/ims", $html, $arr)) $life_history = $arr[1]; //http://www.fs.fed.us/database/feis/animals/mammal/neal/all.html
                if(isset($life_history)) $descriptions["LIFE HISTORY"] = $life_history;
                else
                {   //animals
                    if(preg_match("/BIOLOGICAL DATA\:(.*?)<a name\=\"PreferredHabitat\"/ims", $html, $arr)) 
                    {
                        $biological_data = $arr[1];
                        $descriptions["BIOLOGICAL DATA"] = $biological_data;
                    }
                }
            }
            if(preg_match("/DIET\:(.*?)<a name\=\"PreferredHabitat\"/ims", $html, $arr)) $descriptions["DIET"] = $arr[1]; // http://www.fs.fed.us/database/feis/animals/bird/apco/all.html
            //animals
            if(preg_match("/DISEASES AND SOURCES OF MORTALITY\:(.*?)<a name\=\"PreferredHabitat\"/ims", $html, $arr) || preg_match("/DISEASES AND SOURCES OF MORTALITY\:(.*?)<a name\=\"Preferred Habitat\"/ims", $html, $arr))
            {
                $diseases = $arr[1];
                $descriptions["DISEASES AND SOURCES OF MORTALITY"] = $diseases;
            }
            //animals
            $html = str_ireplace("<b>TIMING OF MAJOR LIFE HISTORY EVENTS :", "<b>TIMING OF MAJOR LIFE HISTORY EVENTS:", $html);
            if(preg_match("/TIMING OF MAJOR LIFE HISTORY EVENTS\:(.*?)<a name\=\"PREFERRED HABITAT\"/ims", $html, $arr) || preg_match("/TIMING OF MAJOR LIFE HISTORY EVENTS\:(.*?)<b>PREFERRED HABITAT/ims", $html, $arr))
            {
                $major_life_history = $arr[1];
                $descriptions["TIMING OF MAJOR LIFE HISTORY EVENTS"] = $major_life_history;
            }
            //animals
            $html = str_ireplace("<b>PREFERRED HABITAT :", "<b>PREFERRED HABITAT:", $html);
            if(preg_match("/PREFERRED HABITAT\:(.*?)<a name\=\"COVER REQUIREMENTS\"/ims", $html, $arr) || preg_match("/PREFERRED HABITAT\:(.*?)<a name\=\"COVERREQUIREMENTS\"/ims", $html, $arr) || preg_match("/PREFERRED HABITAT\:(.*?)<a name\=\"FoodHabits\"/ims", $html, $arr) || preg_match("/PREFERRED HABITAT\:(.*?)<a name\=\"Food Habits\"/ims", $html, $arr) || preg_match("/PREFERRED HABITAT\:(.*?)<b>COVER REQUIREMENTS/ims", $html, $arr) || preg_match("/PREFERRED HABITAT\:(.*?)<a name\=\"ManagementConsiderations\"/ims", $html, $arr)) $pref_habitat = $arr[1];
            if(isset($pref_habitat)) $descriptions["PREFERRED HABITAT"] = $pref_habitat;

            if(!is_numeric(stripos(@$descriptions["PREFERRED HABITAT"], "Cover requirements:"))) // check if 'cover requirements' is already inside 'preferred habitat'
            {   //animals
                $html = str_ireplace("<b>COVER REQUIREMENTS :", "<b>COVER REQUIREMENTS:", $html);
                if(preg_match("/COVER REQUIREMENTS\:(.*?)<a name\=\"FOOD HABITS\"/ims", $html, $arr) || preg_match("/COVER REQUIREMENTS\:(.*?)<b>FOOD HABITS/ims", $html, $arr) || preg_match("/<a name\=\"CoverRequirements\">(.*?)<a name\=\"FOODHABITS\"/ims", $html, $arr))
                {
                    $cover_req = $arr[1];
                    $descriptions["COVER REQUIREMENTS"] = $cover_req;
                }
            }
            //animals
            $html = str_ireplace("<b>FOOD HABITS :", "<b>FOOD HABITS:", $html); // 1 of 2
            if(preg_match("/FOOD HABITS\:(.*?)<a name\=\"PREDATORS\"/ims", $html, $arr) ||
               preg_match("/FOOD HABITS\:(.*?)<a name\=\"FederalLegalStatus\"/ims", $html, $arr) ||
               preg_match("/FOOD HABITS\:(.*?)<a name\=\"Federal Legal Status\"/ims", $html, $arr) ||
               preg_match("/FOOD HABITS\:(.*?)<a name\=\"Management Considerations\"/ims", $html, $arr) ||
               preg_match("/FOOD HABITS\:(.*?)<b>PREDATORS/ims", $html, $arr)) // 2 of 2
            {
                $food_habits = $arr[1];
                $descriptions["FOOD HABITS"] = $food_habits;
            }
            //animals
            if(preg_match("/FEDERAL LEGAL STATUS\:(.*?)<a name\=\"OtherStatus\"/ims", $html, $arr)) $federal_legal_stat = $arr[1];
            elseif(preg_match("/FEDERAL LEGAL STATUS\:(.*?)<a name\=\"Other Status\"/ims", $html, $arr)) $federal_legal_stat = $arr[1];
            elseif(preg_match("/FEDERAL LEGAL STATUS\:(.*?)<b>OTHER STATUS:/ims", $html, $arr)) $federal_legal_stat = $arr[1];
            if(isset($federal_legal_stat)) $descriptions["FEDERAL LEGAL STATUS"] = $federal_legal_stat;
            //animals
            if(preg_match("/OTHER STATUS\:(.*?)<a name\=\"ManagementConsiderations\"/ims", $html, $arr)) $descriptions["OTHER STATUS"] = $arr[1];
            if(!is_numeric(stripos(@$descriptions["DISEASES AND SOURCES OF MORTALITY"], "Predators:")) && !is_numeric(stripos(@$descriptions["Life span and survival"], "Predators:"))) // check if 'predators' is already inside 'DISEASES AND SOURCES OF MORTALITY' and 'Life span and survival'
            {
                //animals
                $html = str_ireplace("<b>PREDATORS :", "<b>PREDATORS:", $html);
                if(preg_match("/PREDATORS\:(.*?)<a name\=\"MANAGEMENT CONSIDERATIONS\"/ims", $html, $arr) || preg_match("/PREDATORS\:(.*?)<b>MANAGEMENT CONSIDERATIONS/ims", $html, $arr) || preg_match("/PREDATORS\:(.*?)<a name\=\"federallegalstatus\"/ims", $html, $arr)) $predators = $arr[1];
                if(isset($predators)) $descriptions["PREDATORS"] = $predators;
            }
            //animals
            $html = str_ireplace("MANAGEMENT CONSIDERATIONS :", "MANAGEMENT CONSIDERATIONS:", $html);
            if(preg_match("/MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"FIRE EFFECTS AND USE\"/ims", $html, $arr) || preg_match("/MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"FIRE EFFECTS\"/ims", $html, $arr) || preg_match("/MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"FireEffectsAndManagement\"/ims", $html, $arr) || preg_match("/MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"Fire Effects And Management\"/ims", $html, $arr) || preg_match("/<b>MANAGEMENT CONSIDERATIONS\:(.*?)<b>REFERENCES/ims", $html, $arr)) $descriptions["MANAGEMENT CONSIDERATIONS"] = $arr[1];
            //animals
            $html = str_ireplace("<b>DIRECT FIRE EFFECTS ON ANIMALS :", "<b>DIRECT FIRE EFFECTS ON ANIMALS:", $html);
            if(preg_match("/DIRECT FIRE EFFECTS ON ANIMAL\:(.*?)<a name\=\"HABITAT RELATED FIRE EFFECTS\"/ims", $html, $arr) || preg_match("/DIRECT FIRE EFFECTS ON ANIMALS\:(.*?)<b>HABITAT RELATED FIRE EFFECTS/ims", $html, $arr) || preg_match("/DIRECT FIRE EFFECTS\:(.*?)<a name\=\"IndirectFireEffects\"/ims", $html, $arr) || preg_match("/DIRECT FIRE EFFECTS\:(.*?)<a name\=\"Indirect Fire Effects\"/ims", $html, $arr) || preg_match("/DIRECT FIRE EFFECTS ON ANIMALS\:(.*?)<a name\=\"HABITAT-RELATED FIRE EFFECTS\"/ims", $html, $arr)) $descriptions["DIRECT FIRE EFFECTS ON ANIMAL"] = $arr[1];
            if(preg_match("/INDIRECT FIRE EFFECTS\:(.*?)<a name\=\"FireRegimes\"/ims", $html, $arr) || preg_match("/INDIRECT FIRE EFFECTS\:(.*?)<a name\=\"Fire Regimes\"/ims", $html, $arr)) $descriptions["INDIRECT FIRE EFFECTS"] = $arr[1];
            if(preg_match("/FUELS AND FIRE REGIMES\:(.*?)<a name\=\"FIRE MANAGEMENT CONSIDERATIONS/ims", $html, $arr)) $fuels_and_fire_regimes = $arr[1];
            elseif(preg_match("/FUELS AND FIRE REGIMES\:(.*?)<a name\=\"FireManagementConsiderations\"/ims", $html, $arr)) $fuels_and_fire_regimes = $arr[1];
            if(isset($fuels_and_fire_regimes)) $descriptions["FUELS AND FIRE REGIMES"] = $fuels_and_fire_regimes;
            //animals
            $html = str_ireplace("<b>HABITAT RELATED FIRE EFFECTS :", "<b>HABITAT RELATED FIRE EFFECTS:", $html);
            if(preg_match("/HABITAT RELATED FIRE EFFECTS\:(.*?)<a name\=\"FIRE USE\"/ims", $html, $arr) || preg_match("/HABITAT RELATED FIRE EFFECTS\:(.*?)<b>FIRE USE/ims", $html, $arr) || preg_match("/Habitat-related Fire Effects\:(.*?)<a name\=\"FIRE USE\"/ims", $html, $arr) || preg_match("/Habitat-related Fire Effects\:(.*?)<b>FIRE USE/ims", $html, $arr)) $habitat_related = $arr[1];
            elseif(preg_match("/HABITAT-RELATED FIRE EFFECTS\:(.*?)<a name\=\"FIRE MANAGEMENT CONSIDERATIONS\"/ims", $html, $arr) || preg_match("/HABITAT RELATED FIRE EFFECTS\:(.*?)<a name\=\"FIRE MANAGEMENT CONSIDERATIONS\"/ims", $html, $arr)) $habitat_related = $arr[1];
            if(isset($habitat_related)) $descriptions["HABITAT RELATED FIRE EFFECTS"] = $habitat_related;
            //animals
            $html = str_ireplace("<b>FIRE USE :", "<b>FIRE USE:", $html);
            if(preg_match("/FIRE USE\:(.*?)<b>REFERENCES/ims", $html, $arr)) $fire_use1 = trim($arr[1]);
            if(preg_match("/FIRE USE\:(.*?)<a name\=\"REFERENCES\"/ims", $html, $arr)) $fire_use2 = trim($arr[1]);
            if(isset($fire_use1) && isset($fire_use2))
            {
                $fire_use = $fire_use1;
                if(strlen($fire_use2) < strlen($fire_use)) $fire_use = $fire_use2;
                $descriptions["FIRE USE"] = $fire_use;
            }
            elseif(isset($fire_use1)) $descriptions["FIRE USE"] = $fire_use1;
            elseif(isset($fire_use2)) $descriptions["FIRE USE"] = $fire_use2;
        }//if($rec["kingdom"] == "Animalia")

        /* MANAGEMENT CONSIDERATIONS */
        if(preg_match("/WOOD PRODUCTS VALUE \:(.*?)<b>IMPORTANCE TO LIVESTOCK/ims", $html, $arr) || preg_match("/WOOD PRODUCTS VALUE \:(.*?)<b>IMPORTANCE TO WILDLIFE/ims", $html, $arr)) $descriptions["WOOD PRODUCTS VALUE"] = $arr[1];
        if(preg_match("/IMPORTANCE TO LIVESTOCK AND WILDLIFE \:(.*?)<b>PALATABILITY/ims", $html, $arr) || preg_match("/IMPORTANCE TO WILDLIFE AND LIVESTOCK\:(.*?)<a name\=\"Palatability/ims", $html, $arr) || preg_match("/IMPORTANCE TO WILDLIFE AND LIVESTOCK\:(.*?)<a name\=\"VALUE FOR REHABILITATION OF DISTURBED SITES/ims", $html, $arr) || preg_match("/IMPORTANCE TO LIVESTOCK AND WILDLIFE\:(.*?)<a name\=\"VALUE FOR REHABILITATION OF DISTURBED SITES/ims", $html, $arr) || preg_match("/IMPORTANCE TO LIVESTOCK AND WILDLIFE\:(.*?)<a name\=\"OTHER USES/ims", $html, $arr)) 
        {
            $impt_livestock = $arr[1];
            $descriptions["IMPORTANCE TO LIVESTOCK AND WILDLIFE"] = $impt_livestock;
        }
        if(preg_match("/PALATABILITY \:(.*?)<b>NUTRITIONAL VALUE/ims", $html, $arr))
        {
            $palatability = $arr[1];
            $descriptions["PALATABILITY"] = $palatability;
        }
        if(preg_match("/PALATABILITY\/NUTRITIONAL VALUE \:(.*?)<b>COVER VALUE/ims", $html, $arr))        $palatability_and_nutritional = $arr[1];
        elseif(preg_match("/PALATABILITY AND NUTRITIONAL VALUE \:(.*?)<b>COVER VALUE/ims", $html, $arr)) $palatability_and_nutritional = $arr[1];
        elseif(preg_match("/PALATABILITY AND NUTRITIONAL VALUE\:(.*?)<a name\=\"CoverValue\"/ims", $html, $arr)) $palatability_and_nutritional = $arr[1];
        if(isset($palatability_and_nutritional)) $descriptions["PALATABILITY AND NUTRITIONAL VALUE"] = $palatability_and_nutritional;
        if(preg_match("/NUTRITIONAL VALUE \:(.*?)<b>COVER VALUE/ims", $html, $arr)) 
        {
            $nutritional_value = $arr[1];
            $descriptions["NUTRITIONAL VALUE"] = $nutritional_value;
        }
        if(preg_match("/COVER VALUE \:(.*?)<b>VALUE FOR REHABILITATION/ims", $html, $arr))                  $cover_value = $arr[1];
        elseif(preg_match("/Cover Value for Wildlife \:(.*?)<b>VALUE FOR REHABILITATION/ims", $html, $arr)) $cover_value = $arr[1];
        if(isset($cover_value)) $descriptions["COVER VALUE"] = $cover_value;
        if(preg_match("/VALUE FOR REHABILITATION OF DISTURBED SITES \:(.*?)<b>OTHER USES/ims", $html, $arr)) $rehabilitation_value = $arr[1];
        elseif(preg_match("/VALUE FOR REHABILITATION OF DISTURBED SITES\:(.*?)<a name\=\"OTHER USES/ims", $html, $arr)) $rehabilitation_value = $arr[1];
        if(isset($rehabilitation_value)) $descriptions["VALUE FOR REHABILITATION OF DISTURBED SITES"] = $rehabilitation_value;
        if(preg_match("/OTHER USES AND VALUES \:(.*?)<b>OTHER MANAGEMENT/ims", $html, $arr)) $other_uses = $arr[1];
        elseif(preg_match("/OTHER USES\:(.*?)<a name\=\"OTHER MANAGEMENT CONSIDERATIONS/ims", $html, $arr)) $other_uses = $arr[1];
        elseif(preg_match("/OTHER USES\:(.*?)<a name\=\"Impacts\"/ims", $html, $arr)) $other_uses = $arr[1];
        elseif(preg_match("/OTHER USES\:(.*?)<a name\=\"IMPACTS AND CONTROL\"/ims", $html, $arr)) $other_uses = $arr[1];
        if(isset($other_uses)) $descriptions["OTHER USES AND VALUES"] = $other_uses;
        if(preg_match("/IMPACTS\:(.*?)<a name\=\"Control\"/ims", $html, $arr)) $impacts = $arr[1];
        if(isset($impacts)) $descriptions["IMPACTS"] = $impacts;
        if(preg_match("/CONTROL\:(.*?)<a name\=\"AppendixFireRegimeTable\"/ims", $html, $arr)) $control = $arr[1];
        if(isset($control)) $descriptions["CONTROL"] = $control;
        if(preg_match("/IMPACTS AND CONTROL\:(.*?)<a name\=\"REFERENCES\"/ims", $html, $arr)) $impacts_and_control = $arr[1];
        if(isset($impacts_and_control)) $descriptions["IMPACTS AND CONTROL"] = $impacts_and_control;
        if(preg_match("/OTHER MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"BOTANICAL AND ECOLOGICAL/ims", $html, $arr)) $other_management = $arr[1];
        elseif(preg_match("/OTHER MANAGEMENT CONSIDERATIONS \:(.*?)<a name\=\"BOTANICAL AND ECOLOGICAL/ims", $html, $arr)) $other_management = $arr[1];
        elseif(preg_match("/OTHER MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"APPENDIX: FIRE REGIME TABLE/ims", $html, $arr)) $other_management = $arr[1];
        elseif(preg_match("/OTHER MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"REFERENCES\"/ims", $html, $arr)) $other_management = $arr[1];
        if(isset($other_management)) $descriptions["OTHER MANAGEMENT CONSIDERATIONS"] = $other_management;

        /* BOTANICAL AND ECOLOGICAL CHARACTERISTICS */
        if(preg_match("/GENERAL BOTANICAL CHARACTERISTICS \:(.*?)<b>RAUNKIAER/ims", $html, $arr)) $gen_botanical = $arr[1];
        elseif(preg_match("/GENERAL BOTANICAL CHARACTERISTICS\:(.*?)<a name\=\"RAUNKIAER LIFE FORM/ims", $html, $arr)) $gen_botanical = $arr[1];
        elseif(preg_match("/GENERAL BOTANICAL CHARACTERISTICS\:(.*?)<a name\=\"SeasonalDevelopment\"/ims", $html, $arr)) $gen_botanical = $arr[1];
        if(isset($gen_botanical)) $descriptions["GENERAL BOTANICAL CHARACTERISTICS"] = $gen_botanical;
        if(!is_numeric(stripos(@$descriptions["GENERAL BOTANICAL CHARACTERISTICS"], "Raunkiaer"))) // check if 'Raunkiaer' is already inside 'GENERAL BOTANICAL CHARACTERISTICS'
        {
            if(preg_match("/RAUNKIAER LIFE FORM \:(.*?)<b>REGENERATION/ims", $html, $arr)) $raunkiaer_lifeform = $arr[1];
            elseif(preg_match("/<a name\=\"RAUNKIAER LIFE FORM\">(.*?)<a name\=\"REGENERATION PROCESSES\"/ims", $html, $arr)) $raunkiaer_lifeform = $arr[1];
            if(isset($raunkiaer_lifeform)) $descriptions["RAUNKIAER LIFE FORM"] = $raunkiaer_lifeform;
        }
        if(preg_match("/REGENERATION PROCESSES \:(.*?)<b>SITE CHARACTERISTICS/ims", $html, $arr)) $regene_proc = $arr[1];
        elseif(preg_match("/REGENERATION PROCESSES\:(.*?)<a name\=\"SITE CHARACTERISTICS/ims", $html, $arr)) $regene_proc = $arr[1];
        elseif(preg_match("/REGENERATION PROCESSES\:(.*?)<a name\=\"SiteCharacteristics\"/ims", $html, $arr)) $regene_proc = $arr[1];
        if(isset($regene_proc)) $descriptions["REGENERATION PROCESSES"] = $regene_proc;
        if(preg_match("/SITE CHARACTERISTICS \:(.*?)<b>SUCCESSIONAL STATUS/ims", $html, $arr)) $site_char = $arr[1];
        elseif(preg_match("/SITE CHARACTERISTICS\:(.*?)<a name\=\"SUCCESSIONAL STATUS/ims", $html, $arr)) $site_char = $arr[1];
        elseif(preg_match("/SITE CHARACTERISTICS\:(.*?)<a name\=\"SuccessionalStatus\"/ims", $html, $arr)) $site_char = $arr[1];
        if(isset($site_char)) $descriptions["SITE CHARACTERISTICS"] = $site_char;
        if(preg_match("/SUCCESSIONAL STATUS \:(.*?)<b>SEASONAL DEVELOPMENT/ims", $html, $arr)) $successional_stat = $arr[1];
        elseif(preg_match("/SUCCESSIONAL STATUS\:(.*?)<a name\=\"SEASONAL DEVELOPMENT/ims", $html, $arr)) $successional_stat = $arr[1];
        elseif(preg_match("/SUCCESSIONAL STATUS\:(.*?)<a name\=\"SeasonalDevelopment\"/ims", $html, $arr)) $successional_stat = $arr[1];
        elseif(preg_match("/SUCCESSIONAL STATUS\:(.*?)<a name\=\"FireEffectsAndManagement\"/ims", $html, $arr)) $successional_stat = $arr[1];
        if(isset($successional_stat)) $descriptions["SUCCESSIONAL STATUS"] = $successional_stat;
        $html = str_ireplace("SEASONAL DEVELOPMENT :", "SEASONAL DEVELOPMENT:", $html);
        if(preg_match("/SEASONAL DEVELOPMENT\:(.*?)<a name\=\"FIRE ECOLOGY/ims", $html, $arr)) $seasonal_dev = $arr[1];
        elseif(preg_match("/SEASONAL DEVELOPMENT\:(.*?)<a name\=\'FIRE EFFECTS AND MANAGEMENT/ims", $html, $arr)) $seasonal_dev = $arr[1];
        elseif(preg_match("/SEASONAL DEVELOPMENT\:(.*?)<a name\=\"RegenerationProcesses\"/ims", $html, $arr)) $seasonal_dev = $arr[1];
        if(isset($seasonal_dev)) $descriptions["SEASONAL DEVELOPMENT"] = $seasonal_dev;
        /* FIRE EFFECTS AND MANAGEMENT  */
        if(preg_match("/FIRE EFFECTS\:(.*?)<a name\=\"FUELS AND FIRE REGIMES/ims", $html, $arr)) $fire_effects = $arr[1];
        elseif(preg_match("/FIRE EFFECTS\:(.*?)<a name\=\"FuelsAndFireRegimes\"/ims", $html, $arr)) $fire_effects = $arr[1];
        if(isset($fire_effects)) $descriptions["FIRE EFFECTS"] = $fire_effects;
        /* FIRE ECOLOGY */
        if(preg_match("/FIRE ECOLOGY OR ADAPTATIONS \:(.*?)<b>POSTFIRE REGENERATION/ims", $html, $arr)) $fire_ecology = $arr[1];
        elseif(preg_match("/FIRE ECOLOGY OR ADAPTATIONS\:(.*?)<a name\=\"POSTFIRE REGENERATION/ims", $html, $arr)) $fire_ecology = $arr[1];
        elseif(preg_match("/FIRE ECOLOGY OR ADAPTATIONS\:(.*?)<a name\=\"PostfireRegeneration/ims", $html, $arr)) $fire_ecology = $arr[1];
        if(isset($fire_ecology)) $descriptions["FIRE ECOLOGY OR ADAPTATIONS"] = $fire_ecology;
        $html = str_ireplace("POSTFIRE REGENERATION STRATEGY :", "POSTFIRE REGENERATION STRATEGY:", $html);
        if(preg_match("/POSTFIRE REGENERATION STRATEGY\:(.*?)<a name\=\"FIRE EFFECTS/ims", $html, $arr))                 $postfire_rege = $arr[1];
        elseif(preg_match("/<a name\=\"POSTFIRE REGENERATION STRATEGY\">(.*?)<a name\=\"FIRE EFFECTS/ims", $html, $arr)) $postfire_rege = $arr[1];
        elseif(preg_match("/<a name\=\"POSTFIRE REGENERATION STRATEGY\">(.*?)<a name\=\"FIREEFFECTS/ims", $html, $arr)) $postfire_rege = $arr[1];
        if(isset($postfire_rege)) $descriptions["POSTFIRE REGENERATION STRATEGY"] = $postfire_rege;
        if(!is_numeric(stripos(@$descriptions["FUELS AND FIRE REGIMES"], "Fire regimes:")) && !is_numeric(stripos(@$descriptions["FIRE ECOLOGY OR ADAPTATIONS"], "Fire regimes:"))) // check if 'Fire regimes' is already inside 'FUELS AND FIRE REGIMES' or 'FIRE ECOLOGY OR ADAPTATIONS'
        {
            if(preg_match("/FIRE REGIMES\:(.*?)<a name\=\"FireManagementConsiderations\"/ims", $html, $arr) || preg_match("/FIRE REGIMES\:(.*?)<a name\=\"Fire Management Considerations\"/ims", $html, $arr)) $descriptions["FIRE REGIMES"] = $arr[1];
        }
        /* FIRE EFFECTS */
        if(preg_match("/IMMEDIATE FIRE EFFECT ON LICHEN\:(.*?)<a name\=\"DISCUSSION AND QUALIFICATION OF FIRE EFFECT/ims", $html, $arr)) $imm_fire_effect_lichen = $arr[1];
        if(isset($imm_fire_effect_lichen)) $descriptions["IMMEDIATE FIRE EFFECT ON LICHEN"] = $imm_fire_effect_lichen;
        if(preg_match("/LICHEN RESPONSE TO FIRE\:(.*?)<a name\=\"DISCUSSION AND QUALIFICATION OF LICHEN RESPONSE/ims", $html, $arr)) $lichen_response_2fire = $arr[1];
        if(isset($lichen_response_2fire)) $descriptions["LICHEN RESPONSE TO FIRE"] = $lichen_response_2fire;
        if(preg_match("/DISCUSSION AND QUALIFICATION OF LICHEN RESPONSE\:(.*?)<a name\=\"FIRE MANAGEMENT CONSIDERATIONS/ims", $html, $arr)) $d_and_q_of_lichen_response = $arr[1];
        if(isset($d_and_q_of_lichen_response)) $descriptions["DISCUSSION AND QUALIFICATION OF LICHEN RESPONSE"] = $d_and_q_of_lichen_response;
        if(preg_match("/IMMEDIATE FIRE EFFECT ON PLANT \:(.*?)<b>DISCUSSION AND QUALIFICATION OF FIRE EFFECT/ims", $html, $arr)) $immediate_fire_effect = $arr[1];
        elseif(preg_match("/IMMEDIATE FIRE EFFECT ON PLANT\:(.*?)<a name\=\"DISCUSSION AND QUALIFICATION OF FIRE EFFECT/ims", $html, $arr)) $immediate_fire_effect = $arr[1];
        if(isset($immediate_fire_effect)) $descriptions["IMMEDIATE FIRE EFFECT ON PLANT"] = $immediate_fire_effect;
        if(preg_match("/DISCUSSION AND QUALIFICATION OF FIRE EFFECT \:(.*?)<b>PLANT RESPONSE TO FIRE/ims", $html, $arr)) $discussion_fire_effect = $arr[1];
        elseif(preg_match("/DISCUSSION AND QUALIFICATION OF FIRE EFFECT\:(.*?)<a name\=\"PLANT RESPONSE TO FIRE/ims", $html, $arr)) $discussion_fire_effect = $arr[1];
        elseif(preg_match("/DISCUSSION AND QUALIFICATION OF FIRE EFFECT\:(.*?)<a name\=\"LICHEN RESPONSE TO FIRE/ims", $html, $arr)) $discussion_fire_effect = $arr[1];
        if(isset($discussion_fire_effect)) $descriptions["DISCUSSION AND QUALIFICATION OF FIRE EFFECT"] = $discussion_fire_effect;
        
        if(preg_match("/PLANT RESPONSE TO FIRE \:(.*?)<b>DISCUSSION AND QUALIFICATION OF PLANT RESPONSE/ims", $html, $arr)) $plant_response_2fire = $arr[1];
        elseif(preg_match("/PLANT RESPONSE TO FIRE\:(.*?)<a name\=\"DISCUSSION AND QUALIFICATION OF PLANT RESPONSE/ims", $html, $arr)) $plant_response_2fire = $arr[1];
        if(isset($plant_response_2fire)) $descriptions["PLANT RESPONSE TO FIRE"] = $plant_response_2fire;
        
        if(preg_match("/DISCUSSION AND QUALIFICATION OF PLANT RESPONSE \:(.*?)<b>FIRE MANAGEMENT CONSIDERATIONS/ims", $html, $arr)) $discussion_plant_response = $arr[1];
        elseif(preg_match("/DISCUSSION AND QUALIFICATION OF PLANT RESPONSE\:(.*?)<a name\=\"FIRE MANAGEMENT CONSIDERATIONS/ims", $html, $arr)) $discussion_plant_response = $arr[1];
        if(isset($discussion_plant_response))
        {
            if(in_array($rec['url'], array("http://www.fs.fed.us/database/feis/plants/vine/smirot/all.html", "http://www.fs.fed.us/database/feis/plants/tree/tsucan/all.html", "http://www.fs.fed.us/database/feis/plants/tree/quevel/all.html", "http://www.fs.fed.us/database/feis/plants/tree/querub/all.html", "http://www.fs.fed.us/database/feis/plants/tree/quemar/all.html", "http://www.fs.fed.us/database/feis/plants/tree/quealb/all.html", "http://www.fs.fed.us/database/feis/plants/tree/pinvir/all.html", "http://www.fs.fed.us/database/feis/plants/tree/pinstr/all.html")) || is_numeric(stripos($html, "<!DOCTYPE html PUBLIC"))) $discussion_plant_response = str_ireplace(array("<br>", "\n"), " ", $discussion_plant_response);
            $descriptions["DISCUSSION AND QUALIFICATION OF PLANT RESPONSE"] = $discussion_plant_response;
        }
        if    (preg_match("/FIRE MANAGEMENT CONSIDERATIONS \:(.*?)<a name\=\"FIRE CASE STUDIES/ims", $html, $arr)) $fire_mgnt_considerations = $arr[1];
        elseif(preg_match("/FIRE MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"AppendixFireRegimeTable/ims", $html, $arr)) $fire_mgnt_considerations = $arr[1];
        elseif(preg_match("/FIRE MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"Appendix: Fire Regime Table/ims", $html, $arr)) $fire_mgnt_considerations = $arr[1];
        elseif(preg_match("/FIRE MANAGEMENT CONSIDERATIONS \:(.*?)<a name\=\"REFERENCES/ims", $html, $arr)) $fire_mgnt_considerations = $arr[1];
        elseif(preg_match("/FIRE MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"REFERENCES/ims", $html, $arr)) $fire_mgnt_considerations = $arr[1];
        elseif(preg_match("/FIRE MANAGEMENT CONSIDERATIONS\:(.*?)<a name\=\"MANAGEMENT CONSIDERATIONS/ims", $html, $arr)) $fire_mgnt_considerations = $arr[1];
        elseif(preg_match("/FIRE CONSIDERATIONS\:(.*?)<a name\=\"AppendixFireRegimeTable/ims", $html, $arr)) $fire_mgnt_considerations = $arr[1];
        elseif(preg_match("/FIRE CONSIDERATIONS\:(.*?)<a name\=\"Appendix: Fire Regime Table/ims", $html, $arr)) $fire_mgnt_considerations = $arr[1];
        if(isset($fire_mgnt_considerations)) $descriptions["FIRE MANAGEMENT CONSIDERATIONS"] = $fire_mgnt_considerations;
        $link_text = "Follow this link to the U.S. Forest Service Fire Effects Information Service to see a table with fire regime information that may be relevant to habitats in which this species occurs";
        $link = false;
        if(preg_match("/<a name\=\"APPENDIX: FIRE REGIME TABLE\">(.*?)/ims", $html, $arr)) $link = $rec['url'] . "#APPENDIX: FIRE REGIME TABLE";
        elseif(preg_match("/<a name\=\"AppendixFireRegimeTable\">(.*?)/ims", $html, $arr)) $link = $rec['url'] . "#AppendixFireRegimeTable";
        if($link) $descriptions["APPENDIX: FIRE REGIME TABLE"] = "<a href='" . $link . "'>$link_text</a>";
        /* FIRE CASE STUDIES */
        $html = str_ireplace("FIRE CASE STUDY CITATION :", "FIRE CASE STUDY CITATION:", $html);
        $html = str_ireplace("SEASON/SEVERITY CLASSIFICATION :", "SEASON/SEVERITY CLASSIFICATION:", $html);
        $html = str_ireplace("STUDY LOCATION :", "STUDY LOCATION:", $html);
        $html = str_ireplace("PREFIRE HABITAT :", "PREFIRE HABITAT:", $html);
        $html = str_ireplace("SITE DESCRIPTION :", "SITE DESCRIPTION:", $html);
        $html = str_ireplace("FIRE DESCRIPTION :", "FIRE DESCRIPTION:", $html);
        $html = str_ireplace("FIRE EFFECTS ON ANIMAL SPECIES AND HABITAT :", "FIRE EFFECTS ON ANIMAL SPECIES AND HABITAT:", $html);
        $html = str_ireplace("FIRE MANAGEMENT IMPLICATIONS :", "FIRE MANAGEMENT IMPLICATIONS:", $html);
        if(preg_match("/<b>FIRE CASE STUDY CITATION\:(.*?)<b>FIRE CASE STUDY REFERENCE\:/ims", $html, $arr)) $descriptions["FIRE CASE STUDY CITATION"] = self::clean_str(strip_tags($arr[1]), true);
        if(preg_match("/<b>FIRE CASE STUDY REFERENCE\:(.*?)<b>SEASON\/SEVERITY CLASSIFICATION\:/ims", $html, $arr)) $descriptions["FIRE CASE STUDY REFERENCE"] = self::clean_str(strip_tags($arr[1]), true);
        if(preg_match("/<b>SEASON\/SEVERITY CLASSIFICATION\:(.*?)<b>STUDY LOCATION\:/ims", $html, $arr)) $descriptions["SEASON/SEVERITY CLASSIFICATION"] = $arr[1];
        if(preg_match("/<b>STUDY LOCATION\:(.*?)<b>PREFIRE HABITAT\:/ims", $html, $arr)) $descriptions["STUDY LOCATION"] = $arr[1];
        if(preg_match("/<b>PREFIRE HABITAT\:(.*?)<b>SITE DESCRIPTION\:/ims", $html, $arr)) $descriptions["PREFIRE HABITAT"] = $arr[1];
        if(preg_match("/<b>SITE DESCRIPTION\:(.*?)<b>FIRE DESCRIPTION\:/ims", $html, $arr)) $descriptions["SITE DESCRIPTION"] = $arr[1];
        if(preg_match("/<b>FIRE DESCRIPTION\:(.*?)<b>FIRE EFFECTS ON ANIMAL SPECIES AND HABITAT\:/ims", $html, $arr)) $descriptions["FIRE DESCRIPTION"] = $arr[1];
        if(preg_match("/<b>FIRE EFFECTS ON ANIMAL SPECIES AND HABITAT\:(.*?)<b>FIRE MANAGEMENT IMPLICATIONS\:/ims", $html, $arr)) $descriptions["FIRE EFFECTS ON ANIMAL SPECIES AND HABITAT"] = $arr[1];
        if(preg_match("/<b>FIRE MANAGEMENT IMPLICATIONS\:(.*?)<a name\=\"REFERENCES\"/ims", $html, $arr)) $descriptions["FIRE MANAGEMENT IMPLICATIONS"] = $arr[1];
        $rec['texts'] = $descriptions;
        return $rec;
    }

    private function further_clean($names, $what, $html)
    {
        $problems = array("NRCS PLANT CODE", "TAXONOMY", "LIFE FORM", "FEDERAL LEGAL STATUS", "OTHER STATUS", "DISTRIBUTION AND OCCURRENCE", "GENERAL DISTRIBUTION", "ECOSYSTEMS", "STATES", "BLM PHYSIOGRAPHIC REGIONS", "KUCHLER PLANT ASSOCIATIONS", "SAF COVER TYPES", "SRM (RANGELAND) COVER TYPES", "HABITAT TYPES AND PLANT COMMUNITIES", "GENERAL DISTRIBUTION", "ECOSYSTEMS");
        if($what == "COMMON NAMES") $problems[] = "SYNONYMS";
        foreach($problems as $problem) {
            if(is_numeric(stripos($names, $problem))) {
                // echo "\n problem hit: $problem ($what)";
                if(preg_match("/$what\:(.*?)<span/ims", $html, $arr)) {
                    // echo "\n further_cleaned...$what\n";
                    $names = self::clean_str(strip_tags($arr[1], "<br><a>"));
                    //test again
                    foreach($problems as $problem) {
                        if(is_numeric(stripos($names, $problem))) return "";
                    }
                    return $names;
                }
            }
        }
        return $names;
    }

    private function remove_tag_attribute($html, $attribute)
    {
        $dom = new \DOMDocument;                                        // init new DOMDocument
        @$dom->loadHTML($html);                                         // load HTML into it --- REMINDER: put @ to disable Warnings. Remove it otherwise.
        $xpath = new \DOMXPath($dom);                                   // create a new XPath
        $nodes = $xpath->query('//*[@' . $attribute . ']');             // Find elements with a style attribute
        foreach ($nodes as $node) $node->removeAttribute($attribute);   // Remove style attribute
        if(preg_match("/<body>(.*?)<\/body>/ims", $dom->saveHTML(), $arr)) return $arr[1];
        return "";
    }
    
    private function get_texts($rec, $agent_ids)
    {
        $texts = $rec['texts'];
        $subjects = array_keys($texts);
        // echo "\n arr1: " . count($texts);
        // echo "\n arr2: " . count($subjects);
        // this loop will just check if all topics are mapped with a subject
        $i = 0;
        foreach($subjects as $subject) {
            if(!in_array(strtolower(self::clean_str(strip_tags($texts[$subject]), true)), array("", "no-entry", "none", "no special status", "no entry", "no_entry", "no additional information is available on this topic.", "see other status", "no information is available on this topic.")) && 
               !in_array($subject, array("AUTHORSHIP AND CITATION", "FIRE CASE STUDY CITATION", "FIRE CASE STUDY REFERENCE")) && // these won't be text objects
               !is_numeric(stripos($subject, "case stud"))) // to exclude all Case Studies
            {
                // echo "\n\n $subject: " . @$this->subject[$subject]['category'] . "\n";
                $description = (string) utf8_encode($texts[$subject]);
                $description = str_ireplace("\n<a ", " <a ", $description);
                $description = str_ireplace("</a> \n", "</a> ", $description);
                $description = str_ireplace("</a>, \n", "</a>, ", $description);
                $description = str_ireplace(" \nTable ", " Table ", $description);
                $description = str_ireplace(" \n(Table ", " (Table ", $description);
                $description = str_ireplace(") \n[<a ", ") [<a ", $description);
                $description = str_ireplace("</a>) \n[<a ", "</a>) [<a ", $description);
                $description = str_ireplace(" \n(<a ", " (<a ", $description);
                $description = str_ireplace("</a>), \n", "</a>), ", $description);
                $topics_with_line_break = array("SAF COVER TYPES", "KUCHLER PLANT ASSOCIATIONS", "BLM PHYSIOGRAPHIC REGIONS", "STATES", "ECOSYSTEMS", "SRM (RANGELAND) COVER TYPES", "SYNONYMS", "COMMON NAMES", "TAXONOMY", "DISCUSSION AND QUALIFICATION OF PLANT RESPONSE", "NUTRITIONAL VALUE", "PALATABILITY", "OTHER MANAGEMENT CONSIDERATIONS", "HABITAT TYPES AND PLANT COMMUNITIES");

                // $urls_with_line_break = array("http://www.fs.fed.us/database/feis/animals/bird/aisp/all.html");
                $urls_with_line_break = array();
                if(in_array($rec['url'], $urls_with_line_break) || $rec["with_line_break"]) $description = str_ireplace("\n", "<br>", $description);
                else
                {
                    if(in_array($subject, $topics_with_line_break)) 
                    {
                        $description = str_ireplace("\n", "<br>", $description);
                    }
                }

                if(stripos($description, "width=") != "") $description = self::remove_tag_attribute($description, "width");
                if(stripos($description, "height=") != "") $description = self::remove_tag_attribute($description, "height");
                if(stripos($description, "bgcolor=") != "") $description = self::remove_tag_attribute($description, "bgcolor");
                // to remove extra blank lines
                $description = str_ireplace("<br>  <br>", "<br><br>", $description);
                $description = str_ireplace("<br> <br>", "<br><br>", $description);
                $description = str_ireplace("<br><br><p>", "<br><br>", $description);
                $description = str_ireplace("<br></ul><br><br>", "</ul>", $description);
                $description = str_ireplace("</p><br><br>", "<br>", $description);
                $description = str_ireplace("<br><tr>", "<tr>", $description);
                $description = str_ireplace("<tr><br>", "<tr>", $description);
                $description = str_ireplace("<br><br><ul>", "<ul>", $description);
                $description = str_ireplace("<br><li>", "<li>", $description);
                $description = str_ireplace("</ul><br>", "</ul>", $description);
                $description = str_ireplace("</tr><br>", "</tr>", $description);
                $description = str_ireplace("</table><br><br>", "</table><br>", $description);
                $description = str_ireplace("</td><br>", "</td>", $description);
                $description = str_ireplace("<tr><br><td>", "<tr><td>", $description);
                $description = str_ireplace("<br><td>", "<td>", $description);
                $description = str_ireplace("<br><br><br><br><br>", "<br><br>", $description);
                $description = str_ireplace("<br><br><br><br>", "<br><br>", $description);
                $description = str_ireplace("<br><br><br>", "<br><br>", $description);
                $description = str_ireplace("<br><br></p>", "</p>", $description);
                $description = str_ireplace("<br><p", "<p", $description);
                foreach(array(".","0","1","2","3","4","5","6","7","8","9","(") as $char) // <.05 http://www.fs.fed.us/database/feis/plants/shrub/vaculi/all.html
                {
                    if(is_numeric(stripos($description, "<".$char))) $description = str_ireplace("<".$char, " less than ".$char, $description);
                    // if(is_numeric(stripos($description, ">".$char))) // never use this
                }
                $description = self::clean_str(strip_tags($description, "<a><br><b><table><tr><td><ul><ol><li><strong><p><img>"), true);
                // to maintain the hyperlinks in the text
                $description = str_ireplace('href="../../../', 'href="' . $this->fsfeis_domain, $description);
                $description = str_ireplace('href="all.html', 'href="' . $rec['url'], $description);
                $description = str_ireplace('href="references.html#', 'href="#', $description); //for animals http://www.fs.fed.us/database/feis/animals/arthropod/acal/all.html
                $description = str_ireplace('href="#', 'href="' . $rec['url'] . "#", $description);
                // to maintain <img src>
                $description = str_ireplace('src="', 'width="300" height="265" src="' . str_ireplace("all.html", "", $rec['url']) . "/", $description);
                // to remove extra tags at the start & end of texts
                $strings_2be_removed = array("<strong></strong>", "<b></b><br>", "</a>", "</b>", "<b>", "<br>", "<b>", "<br>", "</strong>", "<br>", "<p>", "<br>", '"', ">", "</a>", "<br>", "</p>", "$subject</a>:", "</b>", "</strong>");
                $description = self::remove_first_part_of_string($strings_2be_removed, trim($description));
                $description = preg_replace('/<a name\=\'MANAGEMENT CONSIDERATIONS\'><\/a>/', '', $description);
                $description = preg_replace('/<a name\=\'FIRE EFFECTS\'><\/a>/', '', $description); 
                $description = preg_replace('/<a name\=\'BOTANICAL AND ECOLOGICAL CHARACTERISTICS\'><\/a>/', '', $description);
                $description = trim(str_ireplace("<b>REFERENCES : </b> NO-ENTRY", "", $description));
                $strings_2be_removed = array("<strong></strong>", "<strong>", "</b>", "<b>", "<br>", '<a name="ManagementConsiderations">MANAGEMENT CONSIDERATIONS:</a>', '<a name="IMPACTS AND CONTROL"> IMPACTS AND CONTROL:</a>', "<a name='FIRE EFFECTS AND USE'></a>", "<a name='BIOLOGICAL DATA AND HABITAT REQUIREMENTS'></a>", '<a name="SOURCES%20OF%20MORTALITY"> SOURCES OF MORTALITY:</a>', '<p class="subframeContent">', "</p>", "<br>", "<p>" , "</p>", "<strong></strong>", "<p>", "</p>");
                $description = self::remove_last_part_of_string($strings_2be_removed, trim($description));
                $description = str_ireplace("<br><br><br><br>", "<br><br>", $description);
                $description = str_ireplace("<br><br><br>", "<br><br>", $description);
                $description = str_ireplace(array("&Acirc;&#151;"), "", trim($description));
                if(substr($description,0,8) == "<strong>" && substr($description, -9) == "</strong>")
                {
                    $description = self::remove_first_part_of_string(array("<strong>"), trim($description));
                    $description = self::remove_last_part_of_string(array("</strong>"), trim($description));
                }
                if(substr($description,0,4) == "<ul>" && substr($description, -5) == "</ul>") $description = "";

                $reference_ids = self::get_object_reference_ids($description);
                
                /* FIRE CASE STUDY */
                $fire_case_study_topics = array("SEASON/SEVERITY CLASSIFICATION", "STUDY LOCATION", "PREFIRE HABITAT", "SITE DESCRIPTION", "FIRE DESCRIPTION", "FIRE EFFECTS ON ANIMAL SPECIES AND HABITAT", "FIRE MANAGEMENT IMPLICATIONS");
                if(in_array($subject, $fire_case_study_topics)) {
                    if(@$texts["FIRE CASE STUDY REFERENCE"]) {
                        $r = new \eol_schema\Reference();
                        $r->full_reference = (string) $texts["FIRE CASE STUDY REFERENCE"];
                        $r->identifier = md5($r->full_reference);
                        $reference_ids[] = $r->identifier;
                        if(!in_array($r->identifier, $this->resource_reference_ids)) {
                           $this->resource_reference_ids[] = $r->identifier;
                           $this->archive_builder->write_object_to_file($r);
                        }
                    }
                    else $bibliographic_citation = @$texts["FIRE CASE STUDY CITATION"]  != '' ? $texts["FIRE CASE STUDY CITATION"] : '';
                }
                else $bibliographic_citation = @$texts["AUTHORSHIP AND CITATION"]  != '' ? $texts["AUTHORSHIP AND CITATION"] : '';
                
                if(!@$this->subject[$subject]['category']) {
                    if($spm = self::find_spm_given_subject($subject)) {} //echo "\n found SPM for subject [$subject]: [$spm]\n"; 
                    else {} //echo "\n no SPM found for subject [$subject]\n";
                }
                else $spm = $this->subject[$subject]['category'];
                if(!@$this->subject[$subject]['title']) {
                    $title = $subject;
                    // echo "\n subject becomes the title: [$subject]\n";
                }
                else $title = @$this->subject[$subject]['title'];
                if($description == "" || $spm == "") {
                    // echo "\n will continue...[$description][$spm]";
                    continue;
                }
                $description = self::adjust_paragraph(trim($description));
                if(in_array($subject, array("LIFE FORM", "RAUNKIAER LIFE FORM"))) $description = self::process_life_form($description);
                else 
                {
                    if($subject != "APPENDIX: FIRE REGIME TABLE") $description = self::word_more_info($description);
                }
                if(isset($this->subject[$subject]['description'])) $description = $this->subject[$subject]['description'] . "<br><br>" . $description;
                if($rec["with_line_break"] && is_numeric(stripos($description, "&nbsp;&nbsp;&nbsp;&nbsp;"))) $description = "<pre>$description</pre>";

                $description = self::disable_photos($description);
                
                // echo "\n description: \nsss[$description]jjj"; //debug
                // start debug display
                // if(in_array($subject, array("PLANT RESPONSE TO FIRE"))) exit("\n\n $subject: \nsss[$description]jjj\n\n"); //debug

                $i++;
                $mr = new \eol_schema\MediaResource();
                if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
                if($agent_ids)      $mr->agentID = implode("; ", $agent_ids);
                $mr->taxonID        = (string) $rec['taxonID'];
                $mr->identifier     = (string) $rec['taxonID'] . "_" . str_replace(" ", "_", $subject);
                $mr->type           = "http://purl.org/dc/dcmitype/Text";
                $mr->language       = 'en';
                $mr->format         = "text/html";
                $mr->furtherInformationURL = (string) self::clean_str(trim($rec['url']), true);
                $mr->CVterm         = (string) $spm;
                $mr->Owner          = "";
                if(isset($this->subject[$subject]['link'])) {
                    if(!($rec["kingdom"] == "Animalia" && in_array($subject, array("RAUNKIAER LIFE FORM", "SUCCESSIONAL STATUS", "SEASONAL DEVELOPMENT")))) $description = "<a alt='" . $title . "' href='" . $this->subject[$subject]['link'] . "'>" . "More info on this topic." . "</a><br> <br>" . $description;
                }
                $mr->title          = (string) $title;
                $mr->UsageTerms     = "http://creativecommons.org/licenses/publicdomain/";
                $mr->audience       = 'Everyone';
                $mr->description    = (string) $description;
                $mr->bibliographicCitation = (string) $bibliographic_citation;
                $this->archive_builder->write_object_to_file($mr);
            }
        }
        // echo "\n\n count = $i\n";
        if($i <= 10) {} //echo "ALERT: - less than 10x - " . $rec["url"] . "\n"; 
    }
    
    private function disable_photos($html)
    {
        $html = self::clean_str(strip_tags($html, "<a><br><b><table><tr><td><ul><ol><li><strong><p>"), true);
        $start_pos = stripos($html, "Photo by");
        if(is_numeric($start_pos)) {
            $pos = $start_pos + 8;
            while(true) {
                $char = substr($html, $pos, 1);
                if($char == "<") break;
                if($pos-$start_pos >= 500) return $html;
                $pos++;
            }
            $string_tobe_removed = substr($html, $start_pos, $pos-$start_pos);
            // echo "\n to be removed: [" . $string_tobe_removed . "]\n";
            $this->debug_toberemoved[$string_tobe_removed] = 1;
            return str_ireplace($string_tobe_removed, "", $html);
        }
        return $html;
    }
    private function process_life_form($description)
    {
        $description = trim(strip_tags($description, "<br>"));
        $pos = stripos($description, '<a name="InformationAvailable">');
        if(is_numeric($pos)) $description = trim(substr($description, 0, $pos));
        $pos = stripos($description, '<a name="DISTRIBUTION%20AND%20OCCURRENCE">');
        if(is_numeric($pos)) $description = trim(substr($description, 0, $pos));
        $description = self::word_more_info($description, "LIFE FORM"); // includes RAUNKIAER LIFE FORM
        return $description;
    }

    private function word_more_info($description, $subject = NULL)
    {
        $terms = $this->word;
        $more_info = "";
        $used = array();
        foreach(array_keys($terms) as $word) {
            $link = $terms[$word];
            $strings = array();
            if($subject == "LIFE FORM") $strings[] = $word;
            else {
                $strings[] = " " . $word . " ";
                $strings[] = " " . $word . ".";
                $strings[] = " " . $word . ",";
                $strings[] = " " . $word . ";";
            }
            foreach($strings as $string) {
                if(is_numeric(stripos(strip_tags($description), $string))) {
                    if(!in_array($word, $used)) {
                        if($more_info) $more_info .= ", <a href='$link'><i>$word</i></a>";
                        else $more_info .= "<a href='$link'><i>$word</i></a>";
                        $used[] = $word;
                    }
                }
            }
        }
        $word_term = count($used) > 1 ? "terms" : "term";
        if($more_info) $description = "More info for the $word_term: " . $more_info . "<br><br>" . $description;
        return utf8_encode($description);
    }

    private function adjust_paragraph($description)
    {
        $pos = stripos($description, "<p>");
        if(is_numeric($pos)) {
            if($pos > 0) {
                $first = trim(substr($description, 0, $pos));
                $second = trim(substr($description, $pos, strlen($description)));
                if(substr($first, -4) == "</p>") $description = "<p>$first" . $second;
                else                             $description = "<p>$first</p>" . $second;
            }
        }
        // else $description = "<p>$description</p>"; // no need to do this because we actually want an HTML-free text object
        return $description;
    }

    private function get_object_reference_ids($description)
    {
        $reference_ids = array();
        $temp = array();
        if(preg_match_all("/\/all\.html\#(.*?)\"/ims", $description, $arr)) {
            asort($arr[1]);
            foreach(array_unique($arr[1]) as $page_ref_no) {
                /* old ways
                if($page_ref_no == 77) echo "\n[" .              $this->temp_page_reference_nos[$page_ref_no] . "]";
                if(is_numeric($page_ref_no)) $reference_ids[] = @$this->temp_page_reference_nos[$page_ref_no];
                */
                if($val = @$this->temp_page_reference_nos[$page_ref_no]) $reference_ids[] = $val;
            }
        }
        return $reference_ids;
    }

    private function remove_first_part_of_string($chars_2be_removed, $string)
    {
        foreach($chars_2be_removed as $chars) {
            $len = strlen($chars);
            while(substr($string, 0, $len) == $chars) {
               $string = trim(substr($string, $len, strlen($string))); //chars at the beginning of the string is removed
            }
        }
        return $string;
    }

    private function remove_last_part_of_string($chars_2be_removed, $string)
    {
        foreach($chars_2be_removed as $chars) {
            while(substr($string, -strlen($chars)) == $chars) {
                $string = trim(substr($string, 0, strlen($string) - strlen($chars)));
            }
        }
        return $string;
    }

    function clean_str($str, $with_breaks = false)
    {
        if($with_breaks) $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011", "	", ""), " ", trim($str));
        return trim($str);
    }
    /* not being used
    private function loop_references($references_array, $reference_ids)
    {
        foreach($references_array as $ref) {
            $ref = (string) trim($ref);
            if(!$ref) continue;
            $r = new \eol_schema\Reference();
            $r->full_reference = (string) $ref;
            $r->identifier = md5($r->full_reference);
            $reference_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_reference_ids)) {
               $this->resource_reference_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $reference_ids;
    }
    */
    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon_id = (string) $rec['taxonID'];
        $taxon->taxonID = $taxon_id;
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonRank = '';
        $scientificName = (string) utf8_encode($rec['sciname']);
        if(!$scientificName) {
            echo "\n ALERT: blank scientificName [$scientificName]";
            return; //blank
        } 
        $taxon->scientificName              = $scientificName;
        $taxon->vernacularName              = (string) $rec['vernacular'];
        $taxon->kingdom                     = (string) $rec["kingdom"];
        $taxon->class                       = (string) trim(@$rec["class"]) != "" ? $this->class_name[$rec["class"]]  : "";
        $taxon->order                       = (string) trim(@$rec["order"]) != "" ? $rec["order"] : "";
        $this->taxa[$taxon_id] = $taxon;
        
        //for common names:
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $taxon_id;
        $v->vernacularName  = $taxon->vernacularName;
        $v->language        = 'en';
        $this->archive_builder->write_object_to_file($v);
    }
    
    function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(true);
    }

    private function get_references_from_html($html)
    {
        $html = str_ireplace("<a name='REFERENCES'>", '<a name="REFERENCES">', $html);
        if(preg_match("/<a name\=\"REFERENCES\"(.*?)<script/ims", $html, $arr) || preg_match("/<a name\=\"REFERENCES\"(.*?)<\/body>/ims", $html, $arr) || preg_match("/<a name\=\"REFERENCES(.*?)<\/body>/ims", $html, $arr))
        {
            $html = $arr[1];
            $html = strip_tags($html, "<a><blockquote>"); // e.g. http://www.fs.fed.us/database/feis/plants/forb/tanvul/all.html
            if(preg_match_all("/<blockquote>(.*?)<\/blockquote>/ims", $html, $arr) || preg_match_all("/<a name\=\"(.*?)\]/ims", $html, $arr)) {
                foreach($arr[1] as $ref) {
                    if(substr($ref, 0, 7) == "<a name") $ref = (string) trim(utf8_encode($ref));
                    else                                $ref = (string) '<a name="' . trim(utf8_encode($ref)) . "]";
                    if(preg_match("/<a name\=\"(.*?)\"/ims", $ref, $arr2)) $page_ref_no = $arr2[1];
                    $ref = self::clean_str(strip_tags($ref), true);
                    if($ref) {
                        $r = new \eol_schema\Reference();
                        $r->full_reference = (string) trim($ref);
                        $r->identifier = md5($r->full_reference);
                        $this->temp_page_reference_nos[$page_ref_no] = $r->identifier;
                        if(!in_array($r->identifier, $this->resource_reference_ids)) {
                           $this->resource_reference_ids[] = $r->identifier;
                           $this->archive_builder->write_object_to_file($r);
                        }
                    }
                    else continue;
                }
            }
        }
        else echo "\n\n wasn't able to locate REFERENCES: ";
    }

    private function generate_glossary_terms()
    {
        $url = "http://www.fs.fed.us/database/feis/glossary2.html";
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            $html = (string) utf8_encode($html);
            if(preg_match_all("/<strong>(.*?)<\/strong>/ims", $html, $arr))
            {
                foreach($arr[1] as $line)
                {
                    // <a name="caudex">caudex:</a> --- $this->word['caudex'] = "http://www.fs.fed.us/database/feis/glossary2.html#caudex";
                    $href = false;
                    $term = false;
                    if(preg_match("/\"(.*?)\"/ims", $line, $match)) $href = $match[1];
                    $line = str_ireplace(":", "", $line); // placed here bec sometimes the ':' is placed inside href
                    if(preg_match("/>(.*?)</ims", $line, $match)) $term = $match[1];
                    if($href && $term) $this->word[$term] = $url ."#" . $href;
                }
            }
        }
        // other 'Life form' terms, using wikipedia
        $this->word['bryophyte'] = "http://en.wikipedia.org/wiki/Bryophyte";
        $this->word['cactus'] = "http://en.wikipedia.org/wiki/Cactus";
        $this->word['fern'] = "http://en.wikipedia.org/wiki/Fern";
        $this->word['forb'] = "http://en.wikipedia.org/wiki/Forb";
        $this->word['lichen'] = "http://en.wikipedia.org/wiki/Lichen";
        $this->word['tree'] = "http://en.wikipedia.org/wiki/Tree";
        $this->word['vine'] = "http://en.wikipedia.org/wiki/Vine";
        $this->word['shrub'] = "http://en.wikipedia.org/wiki/Shrub";
        $this->word['bryophytes'] = "http://en.wikipedia.org/wiki/Bryophyte";
        $this->word['cacti'] = "http://en.wikipedia.org/wiki/Cactus";
        $this->word['ferns'] = "http://en.wikipedia.org/wiki/Fern";
        $this->word['forbs'] = "http://en.wikipedia.org/wiki/Forb";
        $this->word['lichens'] = "http://en.wikipedia.org/wiki/Lichen";
        // $this->word['trees'] = "http://en.wikipedia.org/wiki/Tree";
        $this->word['vines'] = "http://en.wikipedia.org/wiki/Vine";
        $this->word['shrubs'] = "http://en.wikipedia.org/wiki/Shrub";
        ksort($this->word);
    }
    
    private function initialize_subjects()
    {
        self::generate_glossary_terms();
        $this->subject['TAXONOMY']['title'] = "Taxonomy";
        $this->subject['TAXONOMY']['category'] = $this->EOL . "#Taxonomy";
        $this->subject['SYNONYMS']['title'] = "Synonyms";
        $this->subject['SYNONYMS']['category'] = $this->EOL . "#Taxonomy";
        $this->subject['COMMON NAMES']['title'] = "Common Names";
        $this->subject['COMMON NAMES']['category'] = $this->EOL . "#Taxonomy";
        $this->subject['FEDERAL LEGAL STATUS']['title'] = "U.S. Federal Legal Status";
        $this->subject['FEDERAL LEGAL STATUS']['category'] = $this->SPM . "#ConservationStatus";
        $this->subject['OTHER STATUS']['title'] = "Conservation Status";
        $this->subject['OTHER STATUS']['category'] = $this->SPM . "#ConservationStatus";
        $this->subject['GENERAL DISTRIBUTION']['title'] = "Distribution";
        $this->subject['GENERAL DISTRIBUTION']['category'] = $this->SPM . "#Distribution";
        $this->subject['ECOSYSTEMS']['title'] = "Habitat: Ecosystem";
        $this->subject['ECOSYSTEMS']['category'] = $this->SPM . "#Habitat";
        $this->subject['ECOSYSTEMS']['link'] = "http://en.wikipedia.org/wiki/Forest-Range_Environmental_Study_Ecosystems";
        $this->subject['ECOSYSTEMS']['description'] = "This species is known to occur in the following ecosystem types (as named by the U.S. Forest Service in their Forest and Range Ecosystem [FRES] Type classification):";
        $this->subject['STATES']['title'] = "Occurrence in North America";
        $this->subject['STATES']['category'] = $this->SPM . "#Distribution";
        $this->subject['BLM PHYSIOGRAPHIC REGIONS']['title'] = "Regional Distribution in the Western United States";
        $this->subject['BLM PHYSIOGRAPHIC REGIONS']['category'] = $this->SPM . "#Distribution";
        $this->subject['BLM PHYSIOGRAPHIC REGIONS']['link'] = "http://books.google.com/books?id=ha6uE0A8uzUC&pg=PA126&lpg=PA126&dq=Bureau+of+land+management+physiographic+regions+appendix+G&source=bl&ots=tv5NWlmvJm&sig=Bq9gtbLuMHBqoqF-W3gJ0H38cv8&hl=en&sa=X&ei=WT65UMrpFJLG0AH0p4DYDA&ved=0CEsQ6AEwBg";
        $this->subject['BLM PHYSIOGRAPHIC REGIONS']['description'] = "This species can be found in the following regions of the western United States (according to the Bureau of Land Management classification of Physiographic Regions of the western United States):";
        $this->subject['KUCHLER PLANT ASSOCIATIONS']['title'] = "Habitat: Plant Associations";
        $this->subject['KUCHLER PLANT ASSOCIATIONS']['category'] = $this->SPM . "#Habitat";
        $this->subject['KUCHLER PLANT ASSOCIATIONS']['link'] = "http://books.google.com/books?id=ha6uE0A8uzUC&pg=PA115&lpg=PA115&dq=FRES29&source=bl&ots=tv5NWlhtOu&sig=JuAMeBPPvvXj3zRgng86YHqsKPY&hl=en&sa=X&ei=Qyq5UOrFDMTq0QHm2IHoCg&ved=0CDQQ6AEwAQ#v=snippet&q=Pfister%20appendix%20d&f=false";
        $this->subject['KUCHLER PLANT ASSOCIATIONS']['description'] = "This species is known to occur in association with the following plant community types (as classified by Küchler 1964):";
        $this->subject['SAF COVER TYPES']['title'] = "Habitat: Cover Types";
        $this->subject['SAF COVER TYPES']['category'] = $this->SPM . "#Habitat";
        $this->subject['SAF COVER TYPES']['link'] = "http://books.google.com/books?id=ha6uE0A8uzUC&pg=PA111&lpg=PA111&dq=SAF+cover+types+appendix+E&source=bl&ots=tv5NWlnxQr&sig=DOYqJvpYk3o5jkCJrWKFg9b3rW8&hl=en&sa=X&ei=U0O5UNvjO6uF0QHKkYGYCw&ved=0CEoQ6AEwBQ#v=onepage&q=SAF%20cover%20types%20appendix%20E&f=false";
        $this->subject['SAF COVER TYPES']['description'] = "This species is known to occur in association with the following cover types (as classified by the Society of American Foresters):";
        $this->subject['SRM (RANGELAND) COVER TYPES']['title'] = "Habitat: Rangeland Cover Types";
        $this->subject['SRM (RANGELAND) COVER TYPES']['category'] = $this->SPM . "#Habitat";
        $this->subject['SRM (RANGELAND) COVER TYPES']['link'] = "http://books.google.com/books?id=ha6uE0A8uzUC&pg=PA17&lpg=PA17&dq=Fire+Effects+Information+System:+User%27s+Guide+society+range+management&source=bl&ots=tv5NWlnAOu&sig=6A-EokUTMquov4A2SbJyBo4Ds9Q&hl=en&sa=X&ei=b0S5UNSiBYWw0QHroYCQCg&ved=0CC4Q6AEwAA#v=onepage&q=society%20for%20range%20management%20%28SRM%29%20cover%20types%20Pacific%20Northwest&f=false";
        $this->subject['SRM (RANGELAND) COVER TYPES']['description'] = "This species is known to occur in association with the following Rangeland Cover Types (as classified by the Society for Range Management, SRM):";
        //animals
        $this->subject['PLANT COMMUNITIES']['title'] = "Associated Plant Communities";
        $this->subject['PLANT COMMUNITIES']['category'] = $this->SPM . "#Habitat";
        $this->subject['TIMING OF MAJOR LIFE HISTORY EVENTS']['title'] = "Timing of Major Life History Events";
        $this->subject['TIMING OF MAJOR LIFE HISTORY EVENTS']['category'] = $this->SPM . "#Ecology";
        $this->subject['BIOLOGICAL DATA']['title'] = "Biological Data";
        $this->subject['BIOLOGICAL DATA']['category'] = $this->SPM . "#Habitat";
        $this->subject['PREFERRED HABITAT']['title'] = "Preferred Habitat";
        $this->subject['PREFERRED HABITAT']['category'] = $this->SPM . "#Habitat";
        $this->subject['Diurnal habitats']['title'] = "Diurnal habitats";
        $this->subject['Nocturnal habitats']['title'] = "Nocturnal habitats";
        $this->subject['Habitat used during migration']['title'] = "Habitat used during migration";
        $this->subject['Diurnal habitats']['category'] = $this->SPM . "#Habitat";
        $this->subject['Nocturnal habitats']['category'] = $this->SPM . "#Habitat";
        $this->subject['Habitat used during migration']['category'] = $this->SPM . "#Habitat";
        $this->subject['Plant associations used as habitat']['title'] = "Plant associations used as habitat";
        $this->subject['Stand ages']['title'] = "Stand ages";
        $this->subject['Landscape-level requirements']['title'] = "Landscape-level requirements";
        $this->subject['Plant associations used as habitat']['category'] = $this->SPM . "#Habitat";
        $this->subject['Stand ages']['category'] = $this->SPM . "#Habitat";
        $this->subject['Landscape-level requirements']['category'] = $this->SPM . "#Habitat";
        $this->subject['Breeding habitat']['title'] = "Breeding habitat";
        $this->subject['Jack pine age and size']['title'] = "Jack pine age and size";
        $this->subject['Stand density']['title'] = "Stand density";
        $this->subject['Landscape factors']['title'] = "Landscape factors";
        $this->subject['Factors influencing duration of occupancy']['title'] = "Factors influencing duration of occupancy";
        $this->subject['Live lower limbs']['title'] = "Live lower limbs";
        $this->subject['Ground cover']['title'] = "Ground cover";
        $this->subject['Wintering habitat']['title'] = "Wintering habitat";
        $this->subject['Breeding habitat']['category'] = $this->SPM . "#Habitat";
        $this->subject['Jack pine age and size']['category'] = $this->SPM . "#Habitat";
        $this->subject['Stand density']['category'] = $this->SPM . "#Habitat";
        $this->subject['Landscape factors']['category'] = $this->SPM . "#Habitat";
        $this->subject['Factors influencing duration of occupancy']['category'] = $this->SPM . "#Habitat";
        $this->subject['Live lower limbs']['category'] = $this->SPM . "#Habitat";
        $this->subject['Ground cover']['category'] = $this->SPM . "#Habitat";
        $this->subject['Wintering habitat']['category'] = $this->SPM . "#Habitat";
        $this->subject['Landscape']['title'] = "Preferred Habitat: Landscape";
        $this->subject['Habitat']['title'] = "Habitat"; 
        $this->subject['Limiting habitat features']['title'] = "Preferred Habitat: Limiting habitat features";
        $this->subject['Territoriality']['title'] = "Preferred Habitat: Territoriality";
        $this->subject['Home range and density']['title'] = "Preferred Habitat: Home range and density";
        $this->subject['Landscape']['category'] = $this->SPM . "#Habitat";
        $this->subject['Habitat']['category'] = $this->SPM . "#Habitat"; 
        $this->subject['Limiting habitat features']['category'] = $this->SPM . "#Habitat";
        $this->subject['Territoriality']['category'] = $this->SPM . "#Behaviour";
        $this->subject['Home range and density']['category'] = $this->SPM . "#Ecology";
        $this->subject['Breeding season habitat']['title'] = "Breeding season habitat of migratory subspecies";
        $this->subject['Staging and migrating habitat']['title'] = "Staging and migrating habitat of migratory subspecies";
        $this->subject['Wintering habitat']['title'] = "Wintering habitat of migratory subspecies";
        $this->subject['Breeding season habitat']['category'] = $this->SPM . "#Habitat";
        $this->subject['Staging and migrating habitat']['category'] = $this->SPM . "#Habitat";
        $this->subject['Wintering habitat']['category'] = $this->SPM . "#Habitat";
        $this->subject['Habitat of migratory subspecies']['title'] = "Habitat of migratory subspecies";
        $this->subject['Habitat of nonmigratory subspecies']['title'] = "Habitat of nonmigratory subspecies";
        $this->subject['Preferred cover']['title'] = "Preferred cover";
        $this->subject['Use of burned areas']['title'] = "Use of burned areas";
        $this->subject['Habitat of migratory subspecies']['category'] = $this->SPM . "#Habitat";
        $this->subject['Habitat of nonmigratory subspecies']['category'] = $this->SPM . "#Habitat";
        $this->subject['Preferred cover']['category'] = $this->SPM . "#Habitat";
        $this->subject['Use of burned areas']['category'] = $this->SPM . "#Habitat";
        $this->subject['Burrows']['title'] = "Cover Requirements: Burrows";
        $this->subject['Nests']['title'] = "Cover Requirements: Nests";
        $this->subject['Burrows']['category'] = $this->SPM . "#Ecology";
        $this->subject['Nests']['category'] = $this->SPM . "#Habitat";
        $this->subject['Elevation']['title'] = "Preferred Habitat: Elevation";
        $this->subject['Topography']['title'] = "Preferred Habitat: Topography";
        $this->subject['Soil']['title'] = "Preferred Habitat: Soil";
        $this->subject['Water availability']['title'] = "Preferred Habitat: Water availability";
        $this->subject['Plant species composition']['title'] = "Preferred Habitat: Plant species composition";
        $this->subject['Slope']['title'] = "Preferred Habitat: Slope";
        $this->subject['Aspect']['title'] = "Preferred Habitat: Aspect";
        $this->subject['Escape terrain']['title'] = "Preferred Habitat: Escape terrain";
        $this->subject['Forage site selection']['title'] = "Preferred Habitat: Forage site selection";
        $this->subject['Movement corridors']['title'] = "Preferred Habitat: Movement corridors";
        $this->subject['Cover requirements']['title'] = "Preferred Habitat: Cover requirements";
        $this->subject['Cover']['title'] = "Preferred Habitat: Cover";
        $this->subject['Foraging sites']['title'] = "Preferred Habitat: Foraging sites";
        $this->subject['Successional status of mule deer habitats']['title'] = "Preferred Habitat: Successional status of mule deer habitats";
        $this->subject['Edge habitat']['title'] = "Preferred Habitat: Edge habitat";
        $this->subject['Edge habitats']['title'] = "Preferred Habitat: Edge habitats";
        $this->subject['Age and gender']['title'] = "Preferred Habitat: Age and gender";
        $this->subject['Predation risk']['title'] = "Preferred Habitat: Predation risk";
        $this->subject['Other factors']['title'] = "Preferred Habitat: Other factors";
        $this->subject['Elevation']['category'] = $this->SPM . "#Habitat";
        $this->subject['Topography']['category'] = $this->SPM . "#Habitat";
        $this->subject['Soil']['category'] = $this->SPM . "#Habitat";
        $this->subject['Water availability']['category'] = $this->SPM . "#Habitat";
        $this->subject['Plant species composition']['category'] = $this->SPM . "#Habitat";
        $this->subject['Slope']['category'] = $this->SPM . "#Habitat";
        $this->subject['Aspect']['category'] = $this->SPM . "#Habitat";
        $this->subject['Escape terrain']['category'] = $this->SPM . "#Habitat";
        $this->subject['Forage site selection']['category'] = $this->SPM . "#Behaviour";
        $this->subject['Movement corridors']['category'] = $this->SPM . "#Habitat";
        $this->subject['Cover requirements']['category'] = $this->SPM . "#Habitat";
        $this->subject['Cover']['category'] = $this->SPM . "#Habitat";
        $this->subject['Foraging sites']['category'] = $this->SPM . "#Habitat";
        $this->subject['Successional status of mule deer habitats']['category'] = $this->SPM . "#Habitat";
        $this->subject['Edge habitat']['category'] = $this->SPM . "#Habitat";
        $this->subject['Edge habitats']['category'] = $this->SPM . "#Habitat";
        $this->subject['Age and gender']['category'] = $this->SPM . "#Habitat";
        $this->subject['Predation risk']['category'] = $this->SPM . "#Habitat";
        $this->subject['Other factors']['category'] = $this->SPM . "#Habitat";
        $this->subject['Vegetation structure/cover type']['title'] = "Preferred Habitat: Vegetation structure/cover type";
        $this->subject['Landscape characteristics']['title'] = "Preferred Habitat: Landscape characteristics";
        $this->subject['Water']['title'] = "Preferred Habitat: Water";
        $this->subject['Potential association with prairie dogs']['title'] = "Preferred Habitat: Potential association with prairie dogs";
        $this->subject['Vegetation structure/cover type']['category'] = $this->SPM . "#Habitat";
        $this->subject['Landscape characteristics']['category'] = $this->SPM . "#Habitat";
        $this->subject['Water']['category'] = $this->SPM . "#Habitat";
        $this->subject['Potential association with prairie dogs']['category'] = $this->SPM . "#Habitat";
        $this->subject['Plant community characteristics']['title'] = "Preferred Habitat: Plant community characteristics";
        $this->subject['General cover requirements']['title'] = "Preferred Habitat: General cover requirements";
        $this->subject['Habitat associations for specific life history activities']['title'] = "Preferred Habitat: Habitat associations for specific life history activities";
        $this->subject['Prey dynamics']['title'] = "Preferred Habitat: Prey dynamics";
        $this->subject['Predation and/or competition']['title'] = "Preferred Habitat: Predation and/or competition";
        $this->subject['Climate']['title'] = "Preferred Habitat: Climate";
        $this->subject['Plant community characteristics']['category'] = $this->SPM . "#Habitat";
        $this->subject['General cover requirements']['category'] = $this->SPM . "#Habitat";
        $this->subject['Habitat associations for specific life history activities']['category'] = $this->SPM . "#Habitat";
        $this->subject['Prey dynamics']['category'] = $this->SPM . "#Habitat";
        $this->subject['Predation and/or competition']['category'] = $this->SPM . "#Habitat";
        $this->subject['Climate']['category'] = $this->SPM . "#Habitat";
        $this->subject['General Habitat']['title'] = "General Habitat";
        $this->subject['Seasonal Variation']['title'] = "Seasonal Variation";
        $this->subject['Movements']['title'] = "Movements";
        $this->subject['General Habitat']['category'] = $this->SPM . "#Habitat";
        $this->subject['Seasonal Variation']['category'] = $this->SPM . "#Habitat";
        $this->subject['Movements']['category'] = $this->SPM . "#Ecology";
        $this->subject['Succession']['title'] = "Preferred Habitat: Succession";
        $this->subject['Habitat characteristics']['title'] = "Habitat characteristics";
        $this->subject['Habitat use']['title'] = "Habitat use";
        $this->subject['Landscape use']['title'] = "Landscape use";
        $this->subject['Succession']['category'] = $this->SPM . "#Habitat";
        $this->subject['Habitat characteristics']['category'] = $this->SPM . "#Habitat";
        $this->subject['Habitat use']['category'] = $this->SPM . "#Habitat";
        $this->subject['Landscape use']['category'] = $this->SPM . "#Habitat";
        $this->subject['Resting']['title'] = "Habitat associations: Resting";
        $this->subject['Denning']['title'] = "Habitat associations: Denning";
        $this->subject['Traveling']['title'] = "Habitat associations: Traveling";
        $this->subject['Foraging']['title'] = "Habitat associations: Foraging";
        $this->subject['COVER REQUIREMENTS']['title'] = "Cover Requirements";
        $this->subject['COVER REQUIREMENTS']['category'] = $this->SPM . "#Habitat";
        $this->subject['Cliff nest sites']['title'] = "Cover Requirements: Cliff nest sites";
        $this->subject['Cliff characteristics']['title'] = "Cover Requirements: Cliff characteristics";
        $this->subject['Nest elevation']['title'] = "Cover Requirements: Nest elevation";
        $this->subject['Cliff exposure']['title'] = "Cover Requirements: Cliff exposure";
        $this->subject['Alternate nest and roost sites']['title'] = "Cover Requirements: Alternate nest and roost sites";
        $this->subject['Nest competition']['title'] = "Cover Requirements: Nest competition";
        $this->subject['Nest fidelity']['title'] = "Cover Requirements: Nest fidelity";
        $this->subject['Cliff nest sites']['category'] = $this->SPM . "#Ecology";
        $this->subject['Cliff characteristics']['category'] = $this->SPM . "#Ecology";
        $this->subject['Nest elevation']['category'] = $this->SPM . "#Ecology";
        $this->subject['Cliff exposure']['category'] = $this->SPM . "#Ecology";
        $this->subject['Alternate nest and roost sites']['category'] = $this->SPM . "#Ecology";
        $this->subject['Nest competition']['category'] = $this->SPM . "#Ecology";
        $this->subject['Nest fidelity']['category'] = $this->SPM . "#Ecology";
        $this->subject['Den use']['title'] = "Cover Requirements: Den use";
        $this->subject['Den structure']['title'] = "Cover Requirements: Den structure";
        $this->subject['Topography']['title'] = "Cover Requirements: Topography";
        $this->subject['Vegetation composition and height']['title'] = "Cover Requirements: Vegetation composition and height";
        $this->subject['Soil']['title'] = "Cover Requirements: Soil";
        $this->subject['Water']['title'] = "Cover Requirements: Water";
        $this->subject['Roads']['title'] = "Cover Requirements: Roads";
        $this->subject['Associated species']['title'] = "Cover Requirements: Associated species";
        $this->subject['Den use']['category'] = $this->SPM . "#Ecology";
        $this->subject['Den structure']['category'] = $this->SPM . "#Ecology";
        $this->subject['Topography']['category'] = $this->SPM . "#Ecology";
        $this->subject['Vegetation composition and height']['category'] = $this->SPM . "#Ecology";
        $this->subject['Soil']['category'] = $this->SPM . "#Ecology";
        $this->subject['Water']['category'] = $this->SPM . "#Ecology";
        $this->subject['Roads']['category'] = $this->SPM . "#Ecology";
        $this->subject['Associated species']['category'] = $this->SPM . "#Ecology";
        $this->subject['FOOD HABITS']['title'] = "Food Habits";
        $this->subject['FOOD HABITS']['category'] = $this->SPM . "#TrophicStrategy";
        $this->subject['Feeding activity']['title'] = "Feeding activity";
        $this->subject['Forage-site selection']['title'] = "Forage-site selection";
        $this->subject['Earthworm availability']['title'] = "Earthworm availability";
        $this->subject['Feeding activity']['category'] = $this->SPM . "#TrophicStrategy";
        $this->subject['Forage-site selection']['category'] = $this->SPM . "#TrophicStrategy";
        $this->subject['Earthworm availability']['category'] = $this->SPM . "#TrophicStrategy";
        $this->subject['Conifer seed characteristics']['title'] = "Conifer seed characteristics";
        $this->subject['Conifer seed characteristics']['category'] = $this->SPM . "#Associations";
        $this->subject['Foraging and caching']['title'] = "Foraging and caching";
        $this->subject['Foraging and caching']['category'] = $this->SPM . "#Behaviour";
        $this->subject['Foraging behavior']['title'] = "Foraging behavior";
        $this->subject['Foraging behavior']['category'] = $this->SPM . "#Behaviour";
        $this->subject['Hunting methods']['title'] = "Hunting methods";
        $this->subject['Hunting methods']['category'] = $this->SPM . "#Behaviour";
        $this->subject['Diet composition']['title'] = "Food Habits: Diet composition";
        $this->subject['Influence of prey availability']['title'] = "Food Habits: Influence of prey availability";
        $this->subject['Overlap of coyote and swift fox diets']['title'] = "Food Habits: Overlap of coyote and swift fox diets";
        $this->subject['Effects of low prey abundance']['title'] = "Food Habits: Effects of low prey abundance";
        $this->subject['Diet composition']['category'] = $this->SPM . "#Associations";
        $this->subject['Influence of prey availability']['category'] = $this->SPM . "#Associations";
        $this->subject['Overlap of coyote and swift fox diets']['category'] = $this->SPM . "#Associations";
        $this->subject['Effects of low prey abundance']['category'] = $this->SPM . "#Associations";
        $this->subject['Nutrition and energetics']['title'] = "Food Habits: Nutrition and energetics";
        $this->subject['Nutrition and energetics']['category'] = $this->SPM . "#Physiology";
        $this->subject['PREDATORS']['title'] = "Predators";
        $this->subject['PREDATORS']['category'] = $this->SPM . "#Associations";
        $this->subject['Coyote predation']['title'] = "Predators: Coyote predation";
        $this->subject['Other predators']['title'] = "Predators: Other predators";
        $this->subject['Factors influencing predation rates']['title'] = "Predators: Factors influencing predation rates";
        $this->subject['Parasites and disease']['title'] = "Predators: Parasites and disease";
        $this->subject['Coyote predation']['category'] = $this->SPM . "#Associations";
        $this->subject['Other predators']['category'] = $this->SPM . "#Associations";
        $this->subject['Factors influencing predation rates']['category'] = $this->SPM . "#Associations";
        $this->subject['Parasites and disease']['category'] = $this->SPM . "#Associations";
        $this->subject['DIRECT FIRE EFFECTS ON ANIMAL']['title'] = "Direct Effects of Fire";
        $this->subject['DIRECT FIRE EFFECTS ON ANIMAL']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE EFFECTS AND MANAGEMENT']['title'] = "Fire Effects and Management";
        $this->subject['FIRE EFFECTS AND MANAGEMENT']['category'] = $this->SPM . "#Ecology";
        $this->subject['MANAGEMENT CONSIDERATIONS']['title'] = "Management Considerations";
        $this->subject['MANAGEMENT CONSIDERATIONS']['category'] = $this->SPM . "#Management";
        $this->subject['Factors influencing population size']['title'] = "Factors influencing population size";
        $this->subject['Plantation management']['title'] = "Plantation management";
        $this->subject['Stand-level details']['title'] = "Stand-level details";
        $this->subject['Spatial and temporal considerations']['title'] = "Spatial and temporal considerations";
        $this->subject['Brown-headed cowbird control']['title'] = "Brown-headed cowbird control";
        $this->subject['Factors influencing population size']['category'] = $this->SPM . "#PopulationBiology";
        $this->subject['Plantation management']['category'] = $this->SPM . "#Management";
        $this->subject['Stand-level details']['category'] = $this->SPM . "#Management";
        $this->subject['Spatial and temporal considerations']['category'] = $this->SPM . "#Management";
        $this->subject['Brown-headed cowbird control']['category'] = $this->SPM . "#Management";
        $this->subject['Population trends']['title'] = "Management Considerations: Population trends";
        $this->subject['Hunting']['title'] = "Management Considerations: Hunting";
        $this->subject['Crop depredation']['title'] = "Management Considerations: Crop depredation";
        $this->subject['Human disturbance']['title'] = "Management Considerations: Human disturbance";
        $this->subject['Endangered species considerations']['title'] = "Management Considerations: Endangered species considerations";
        $this->subject['Population trends']['category'] = $this->SPM . "#Trends";
        $this->subject['Hunting']['category'] = $this->SPM . "#Threats";
        $this->subject['Crop depredation']['category'] = $this->SPM . "#RiskStatement";
        $this->subject['Human disturbance']['category'] = $this->SPM . "#Threats";
        $this->subject['Endangered species considerations']['category'] = $this->SPM . "#Management";
        $this->subject['Threats']['title'] = "Management Considerations: Threats";
        $this->subject['Status and threats']['title'] = "Management Considerations: Status and threats";
        $this->subject['Habitat management']['title'] = "Management Considerations: Habitat management";
        $this->subject['Population management']['title'] = "Management Considerations: Population management";
        $this->subject['Population status and threats']['title'] = "Management Considerations: Population status and threats";
        $this->subject['Threats']['category'] = $this->SPM . "#Threats";
        $this->subject['Status and threats']['category'] = $this->SPM . "#ConservationStatus";
        $this->subject['Habitat management']['category'] = $this->SPM . "#Management";
        $this->subject['Population management']['category'] = $this->SPM . "#Management";
        $this->subject['Population status and threats']['category'] = $this->SPM . "#ConservationStatus";
        $this->subject['Population Status']['title'] = "Population Status";
        $this->subject['Management Recommendations']['title'] = "Management Recommendations";
        $this->subject['Population Status']['category'] = $this->SPM . "#ConservationStatus";
        $this->subject['Management Recommendations']['category'] = $this->SPM . "#Management";
        $this->subject['LIFE HISTORY']['title'] = "Life History";
        $this->subject['LIFE HISTORY']['category'] = $this->SPM . "#Ecology"; 
        $this->subject['Territory']['title'] = "Territory";
        $this->subject['Causes of mortality']['title'] = "Causes of mortality";
        $this->subject['Predation']['title'] = "Predation";
        $this->subject['Population cycles']['title'] = "Population cycles";
        $this->subject['Reproductive timing']['title'] = "Reproductive timing";
        $this->subject['Reproductive output']['title'] = "Reproductive output";
        $this->subject['Territory']['category'] = $this->SPM . "#Habitat";
        $this->subject['Causes of mortality']['category'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Predation']['category'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Population cycles']['category'] = $this->SPM . "#PopulationBiology";
        $this->subject['Reproductive timing']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Reproductive output']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Territories and colonies']['title'] = "Territories and colonies";
        $this->subject['Reproductive success']['title'] = "Reproductive success";
        $this->subject['Brown-headed cowbird parasitism']['title'] = "Brown-headed cowbird parasitism";
        $this->subject['Territories and colonies']['category'] = $this->SPM . "#PopulationBiology";
        $this->subject['Reproductive success']['category'] = $this->SPM . "#Ecology";
        $this->subject['Brown-headed cowbird parasitism']['category'] = $this->SPM . "#Ecology";
        $this->subject['Distinguishing characteristics']['title'] = "Distinguishing characteristics";
        $this->subject['Reproductive biology']['title'] = "Reproductive biology";
        $this->subject['Breeding behavior']['title'] = "Breeding behavior";
        $this->subject['Mortality']['title'] = "Mortality";
        $this->subject['Flight']['title'] = "Flight";
        $this->subject['Distinguishing characteristics']['category'] = $this->SPM . "#Morphology";
        $this->subject['Reproductive biology']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Breeding behavior']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Mortality']['category'] = $this->SPM . "#Ecology";
        $this->subject['Flight']['category'] = $this->SPM . "#Behaviour";
        $this->subject['DISEASES AND SOURCES OF MORTALITY']['title'] = "Diseases and Sources of Mortality";
        $this->subject['DISEASES AND SOURCES OF MORTALITY']['category'] = $this->SPM . "#Ecology";
        $this->subject['Predators']['title'] = "Sources of Mortality: Predators";
        $this->subject['Diseases and parasites']['title'] = "Sources of Mortality: Diseases and parasites";
        $this->subject['Weather']['title'] = "Sources of Mortality: Weather";
        $this->subject['Other sources of mortality']['title'] = "Other sources of mortality";
        $this->subject['Predators']['category'] = $this->SPM . "#Ecology";
        $this->subject['Diseases and parasites']['category'] = $this->SPM . "#Ecology";
        $this->subject['Weather']['category'] = $this->SPM . "#Ecology";
        $this->subject['Other sources of mortality']['category'] = $this->SPM . "#Ecology";
        $this->subject['SOURCES OF MORTALITY']['title'] = "Sources of Mortality";
        $this->subject['SOURCES OF MORTALITY']['category'] = $this->SPM . "#Ecology";
        $this->subject['MORTALITY']['title'] = "Mortality";
        $this->subject['MORTALITY']['category'] = $this->SPM . "#LifeExpectancy";
        $this->subject['FIRE USE']['title'] = "Use of Fire in Population Management";
        $this->subject['FIRE USE']['category'] = $this->SPM . "#Management";
        //LIFE HISTORY sub-topics
        $this->subject['Daily activity']['title'] = "Life History: Movements and home range: Daily activity";
        $this->subject['Seasonal movements and migration']['title'] = "Life History: Movements and home range: Seasonal movements and migration";
        $this->subject['Dispersal']['title'] = "Life History: Movements and home range: Dispersal";
        $this->subject['Home range']['title'] = "Life History: Movements and home range: Home range";
        $this->subject['Daily activity']['category'] = $this->SPM . "#Behaviour";
        $this->subject['Seasonal movements and migration']['category'] = $this->SPM . "#Migration";
        $this->subject['Dispersal']['category'] = $this->SPM . "#Dispersal";
        $this->subject['Home range']['category'] = $this->SPM . "#Ecology";
        $this->subject['Predators']['title'] = "Life span and survival: Predators";
        $this->subject['Diseases and parasites']['title'] = "Life span and survival: Diseases and parasites";
        $this->subject['Malnutrition and weather']['title'] = "Life span and survival: Malnutrition and weather";
        $this->subject['Fawn survival']['title'] = "Life span and survival: Fawn survival";
        $this->subject['Predators']['category'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Diseases and parasites']['category'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Malnutrition and weather']['category'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Fawn survival']['category'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Ontogeny']['title'] = "Ontogeny"; $this->subject['Ontogeny']['category'] = $this->SPM . "#Ecology"; 
        // under Ontogeny:
        $this->subject['Mating']['title'] = "Mating"; 
        $this->subject['Mating']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Reproductive rates']['title'] = "Reproductive rates"; 
        $this->subject['Reproductive rates']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Gestation and litter size']['title'] = "Gestation and litter size"; 
        $this->subject['Gestation and litter size']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Development']['title'] = "Development"; 
        $this->subject['Development']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Survival rates and mortality']['title'] = "Survival rates and mortality"; 
        $this->subject['Survival rates and mortality']['category'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Phenology']['title'] = "Phenology"; $this->subject['Phenology']['category'] = $this->SPM . "#Cyclicity"; 
        $this->subject['Home range']['title'] = "Home range"; $this->subject['Home range']['category'] = $this->SPM . "#Ecology"; 
        $this->subject['Physical description']['title'] = "Physical description"; $this->subject['Physical description']['category'] = $this->SPM . "#Morphology";
        $this->subject['Physiology'] = array('title' => "Physiology", 'category' => $this->SPM . "#Physiology"); 
        $this->subject['Courtship and mating']['title'] = "Courtship and mating"; $this->subject['Courtship and mating']['category'] = $this->SPM . "#Reproduction"; 
        $this->subject['Reproduction and development']['title'] = "Reproduction and development"; $this->subject['Reproduction and development']['category'] = $this->SPM . "#Reproduction"; 
        $this->subject['Social behavior']['title'] = "Social behavior"; $this->subject['Social behavior']['category'] = $this->SPM . "#Behaviour"; 
        $this->subject['Home range and movements']['title'] = "Home range and movements"; $this->subject['Home range and movements']['category'] = $this->SPM . "#Migration"; 
        $this->subject['Home range and movement']['title'] = "Home range and movement"; $this->subject['Home range and movement']['category'] = $this->SPM . "#Migration"; 
        $this->subject['Movements and home range']['title'] = "Movements and home range"; $this->subject['Movements and home range']['category'] = $this->SPM . "#Ecology"; 
        $this->subject['Population density']['title'] = "Population density"; $this->subject['Population density']['category'] = $this->SPM . "#PopulationBiology"; 
        $this->subject['Survival']['title'] = "Survival"; $this->subject['Survival']['category'] = $this->SPM . "#LifeExpectancy"; 
        $this->subject['Life span']['title'] = "Life span"; $this->subject['Life span']['category'] = $this->SPM . "#LifeExpectancy"; 
        $this->subject['Life span and survival']['title'] = "Life span and survival"; $this->subject['Life span and survival']['category'] = $this->SPM . "#LifeExpectancy"; 
        //other LIFE HISTORY sub-topics
        $this->subject['Daily activity']['title'] = "Daily activity";  $this->subject['Daily activity']['category'] = $this->SPM . "#Migration";
        $this->subject['Annual activity']['title'] = "Annual activity"; $this->subject['Annual activity']['category'] = $this->SPM . "#Ecology";
        $this->subject['Social interactions']['title'] = "Social interactions"; $this->subject['Social interactions']['category'] = $this->SPM . "#Behaviour"; 
        $this->subject['Growth']['title'] = "Growth"; $this->subject['Growth']['category'] = $this->SPM . "#Growth";
        $this->subject['Longevity']['title'] = "Longevity"; $this->subject['Longevity']['category'] = $this->SPM . "#LifeExpectancy"; 
        $this->subject['Longevity and survivorship']['title'] = "Longevity and survivorship"; $this->subject['Longevity and survivorship']['category'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Maturation']['title'] = "Maturation"; $this->subject['Maturation']['category'] = $this->SPM . "#Growth";
        $this->subject['Reproduction']['title'] = "Reproduction"; $this->subject['Reproduction']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Annual cycle']['title'] = "Annual cycle";  $this->subject['Annual cycle']['category'] = $this->SPM . "#Cyclicity";
        $this->subject['Activity and movement']['title'] = "Activity and movement"; $this->subject['Activity and movement']['category'] = $this->SPM . "#Ecology"; 
        $this->subject['Density and home range']['title'] = "Density and home range"; $this->subject['Density and home range']['category'] = $this->SPM . "#Ecology"; 
        $this->subject['Reproduction and longevity']['title'] = "Life History: Reproduction and longevity";
        $this->subject['Molt']['title'] = "Life History: Molt";
        $this->subject['Migration']['title'] = "Life History: Migration";
        $this->subject['Irruptions and extralimital wandering']['title'] = "Life History: Irruptions and extralimital wandering";
        $this->subject['Reproduction and longevity']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Molt']['category'] = $this->SPM . "#Cyclicity";
        $this->subject['Migration']['category'] = $this->SPM . "#Migration";
        $this->subject['Irruptions and extralimital wandering']['category'] = $this->SPM . "#Ecology";
        $this->subject['Description']['title'] = "Description"; 
        $this->subject['Social organization']['title'] = "Social organization"; 
        $this->subject['Age at first reproduction']['title'] = "Age at first reproduction"; 
        $this->subject['Clutch size']['title'] = "Clutch size"; 
        $this->subject['Incubation period']['title'] = "Incubation period"; 
        $this->subject['Nest description']['title'] = "Nest description"; 
        $this->subject['Nest success']['title'] = "Nest success"; 
        $this->subject['Development and dispersal of young']['title'] = "Development and dispersal of young"; 
        $this->subject['Calendar dates for specific events']['title'] = "Calendar dates for specific events"; 
        $this->subject['Diurnal habit of species']['title'] = "Diurnal habit of species"; 
        $this->subject['Home range size and spatial relationships']['title'] = "Home range size and spatial relationships"; 
        $this->subject['Migration or seasonal movement']['title'] = "Migration or seasonal movement"; 
        $this->subject['Description']['category'] = $this->SPM . "#Morphology";
        $this->subject['Social organization']['category'] = $this->SPM . "#Behaviour";
        $this->subject['Age at first reproduction']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Clutch size']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Incubation period']['category'] = $this->SPM . "#Reproduction"; 
        $this->subject['Nest description']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Nest success']['category'] = $this->SPM . "#Reproduction";
        $this->subject['Development and dispersal of young']['category'] = $this->EOL . "#Development";
        $this->subject['Calendar dates for specific events']['category'] = $this->SPM . "#Cyclicity";
        $this->subject['Diurnal habit of species']['category'] = $this->SPM . "#Behaviour";
        $this->subject['Home range size and spatial relationships']['category'] = $this->SPM . "#Ecology";
        $this->subject['Migration or seasonal movement']['category'] = $this->SPM . "#Migration";
        $this->subject['Survivorship']['title'] = "Survivorship"; $this->subject['Survivorship']['category'] = $this->SPM . "#LifeExpectancy"; 
        $this->subject['Home Range']['title'] = "Home Range"; $this->subject['Home Range']['category'] = $this->SPM . "#Ecology"; 
        $this->subject['Density']['title'] = "Density"; $this->subject['Density']['category'] = $this->SPM . "#PopulationBiology"; 
        $this->subject['Development']['title'] = "Development"; $this->subject['Development']['category'] = $this->EOL . "#Development";
        $this->subject['Dispersal']['title'] = "Dispersal"; $this->subject['Dispersal']['category'] = $this->SPM . "#Dispersal"; 
        $this->subject['Activity patterns']['title'] = "Activity patterns"; $this->subject['Activity patterns']['category'] = $this->SPM . "#Cyclicity"; 
        //Habitat-related Fire Effects
        $this->subject['HABITAT RELATED FIRE EFFECTS']['title'] = "Habitat-related Fire Effects";
        $this->subject['HABITAT RELATED FIRE EFFECTS']['category'] = $this->SPM . "#Ecology";
        $this->subject['DIRECT FIRE EFFECTS']['title'] = "Direct Effects of Fire";
        $this->subject['DIRECT FIRE EFFECTS']['category'] = $this->SPM . "#Ecology";
        $this->subject['INDIRECT FIRE EFFECTS']['title'] = "Indirect Effects of Fire";
        $this->subject['INDIRECT FIRE EFFECTS']['category'] = $this->SPM . "#Ecology";
        $this->subject['Elk population response to postfire vegetation changes']['title'] = "Elk population response to postfire vegetation changes";
        $this->subject['Elk use of postfire communities']['title'] = "Elk use of postfire communities";
        $this->subject['Elk condition']['title'] = "Elk condition";
        $this->subject['Elk interactions with fuels and fire effects']['title'] = "Elk interactions with fuels and fire effects";
        $this->subject['Case study: 1988 Yellowstone fires']['title'] = "Case study: 1988 Yellowstone fires";
        $this->subject['Fire effects on vegetation']['title'] = "Fire effects on vegetation";
        $this->subject['Fire effects on American woodcock foods']['title'] = "Fire effects on American woodcock foods";
        $this->subject['Elk population response to postfire vegetation changes']['category'] = $this->SPM . "#Ecology";
        $this->subject['Elk use of postfire communities']['category'] = $this->SPM . "#Ecology";
        $this->subject['Elk condition']['category'] = $this->SPM . "#Ecology";
        $this->subject['Elk interactions with fuels and fire effects']['category'] = $this->SPM . "#Ecology";
        $this->subject['Case study: 1988 Yellowstone fires']['category'] = $this->EOL . "#Notes";
        $this->subject['Fire effects on vegetation']['category'] = $this->SPM . "#Ecology";
        $this->subject['Fire effects on American woodcock foods']['category'] = $this->SPM . "#Ecology";
        $this->subject['Ruffed grouse populations and occurrence']['title'] = "Ruffed grouse populations and occurrence";
        $this->subject['Brooding']['title'] = "Brooding";
        $this->subject['Foraging']['title'] = "Foraging";
        $this->subject['Ruffed grouse populations and occurrence']['category'] = $this->SPM . "#PopulationBiology";
        $this->subject['Brooding']['category'] = $this->SPM . "#Habitat";
        $this->subject['Foraging']['category'] = $this->SPM . "#Habitat";
        $this->subject["Fire-dependant nature of Kirtland's warbler habitat"]['title'] = "Fire-dependant nature of Kirtland's warbler habitat";
        $this->subject["Potential detrimental impacts of fire in Kirtland's warbler habitat"]['title'] = "Potential detrimental impacts of fire in Kirtland's warbler habitat";
        $this->subject['Comparison of habitats of varying origin']['title'] = "Comparison of habitats of varying origin";
        $this->subject['Demography and use']['title'] = "Demography and use";
        $this->subject['Differences in habitat characteristics']['title'] = "Differences in habitat characteristics";
        $this->subject["Fire-dependant nature of Kirtland's warbler habitat"]['category'] = $this->SPM . "#Habitat";
        $this->subject["Potential detrimental impacts of fire in Kirtland's warbler habitat"]['category'] = $this->SPM . "#Threats";
        $this->subject['Comparison of habitats of varying origin']['category'] = $this->SPM . "#PopulationBiology";
        $this->subject['Demography and use']['category'] = $this->SPM . "#PopulationBiology";
        $this->subject['Differences in habitat characteristics']['category'] = $this->SPM . "#Habitat";
        $this->subject['Moose population response to postfire vegetation changes']['title'] = "Indirect Effects of Fire: Moose population response to postfire vegetation changes";
        $this->subject['Postfire vegetation changes and succession']['title'] = "Indirect Effects of Fire: Postfire vegetation changes and succession";
        $this->subject['Case studies']['title'] = "Indirect Effects of Fire: Case studies";
        $this->subject['Other factors']['title'] = "Indirect Effects of Fire: Other factors";
        $this->subject['Parasites']['title'] = "Indirect Effects of Fire: Parasites";
        $this->subject['Indirect fire effects by region']['title'] = "Indirect Effects of Fire: Indirect fire effects by region";
        $this->subject['Mule deer interactions with fuels and fire effects']['title'] = "Indirect Effects of Fire: Mule deer interactions with fuels and fire effects";
        $this->subject['Diseases and parasites']['title'] = "Indirect Effects of Fire: Diseases and parasites";
        $this->subject['Moose population response to postfire vegetation changes']['category'] = $this->SPM . "#PopulationBiology";
        $this->subject['Postfire vegetation changes and succession']['category'] = $this->SPM . "#Ecology";
        $this->subject['Case studies']['category'] = $this->EOL . "#Notes";
        $this->subject['Other factors']['category'] = $this->SPM . "#Ecology";
        $this->subject['Parasites']['category'] = $this->SPM . "#Associations";
        $this->subject['Indirect fire effects by region']['category'] = $this->SPM . "#Ecology";
        $this->subject['Mule deer interactions with fuels and fire effects']['category'] = $this->SPM . "#Ecology";
        $this->subject['Diseases and parasites']['category'] = $this->SPM . "#Associations";
        $this->subject['Response to fire and use of burned areas']['title'] = "Indirect Effects of Fire: Response to fire and use of burned areas";
        $this->subject['Fire effects on cover']['title'] = "Indirect Effects of Fire: Fire effects on cover";
        $this->subject['Fire effects on food']['title'] = "Indirect Effects of Fire: Fire effects on food";
        $this->subject['Wildfire Case Study 1']['title'] = "Indirect Effects of Fire: Wildfire Case Study 1";
        $this->subject['Wildfire Case Study 2']['title'] = "Indirect Effects of Fire: Wildfire Case Study 2";
        $this->subject['Response to fire and use of burned areas']['category'] = $this->SPM . "#Ecology";
        $this->subject['Fire effects on cover']['category'] = $this->SPM . "#Ecology";
        $this->subject['Fire effects on food']['category'] = $this->SPM . "#Ecology";
        $this->subject['Wildfire Case Study 1']['category'] = $this->EOL . "#Notes";
        $this->subject['Wildfire Case Study 2']['category'] = $this->EOL . "#Notes";
        $this->subject['Mule deer, predator, and fire interactions']['title'] = "Inderect Effects of Fire: Other factors: Mule deer, predator, and fire interactions";
        $this->subject['Mule deer, other ungulate, and fire interactions']['title'] = "Inderect Effects of Fire: Other factors: Mule deer, other ungulate, and fire interactions";
        $this->subject['Livestock presence in burned areas']['title'] = "Inderect Effects of Fire: Other factors: Livestock presence in burned areas";
        $this->subject['Travel patterns']['title'] = "Inderect Effects of Fire: Other factors: Travel patterns";
        $this->subject['Physical barriers']['title'] = "Inderect Effects of Fire: Other factors: Physical barriers";
        $this->subject['Weather and use of burned areas']['title'] = "Inderect Effects of Fire: Other factors: Weather and use of burned areas";
        $this->subject['Size and shape of burned areas']['title'] = "Inderect Effects of Fire: Other factors: Size and shape of burned areas";
        $this->subject['Mule deer, predator, and fire interactions']['category'] = $this->SPM . "#Ecology";
        $this->subject['Mule deer, other ungulate, and fire interactions']['category'] = $this->SPM . "#Ecology";
        $this->subject['Livestock presence in burned areas']['category'] = $this->SPM . "#Ecology";
        $this->subject['Travel patterns']['category'] = $this->SPM . "#Ecology";
        $this->subject['Physical barriers']['category'] = $this->SPM . "#Ecology";
        $this->subject['Weather and use of burned areas']['category'] = $this->SPM . "#Ecology";
        $this->subject['Size and shape of burned areas']['category'] = $this->SPM . "#Ecology";
        $this->subject['Boreal forest']['title'] = "Indirect fire effects by region: Boreal forest";
        $this->subject['Pacific Northwest']['title'] = "Indirect fire effects by region: Pacific Northwest";
        $this->subject['California']['title'] = "Indirect fire effects by region: California";
        $this->subject['Southwest']['title'] = "Indirect fire effects by region: Southwest";
        $this->subject['Great Basin']['title'] = "Indirect fire effects by region: Great Basin";
        $this->subject['Rocky Mountains']['title'] = "Indirect fire effects by region: Rocky Mountains";
        $this->subject['Great Plains']['title'] = "Indirect fire effects by region: Great Plains";
        $this->subject['FIRE REGIMES']['title'] = "Fire Regimes";
        $this->subject['FIRE REGIMES']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE REGIME']['title'] = "Fire Regimes";
        $this->subject['FIRE REGIME']['category'] = $this->SPM . "#Ecology";
        $this->subject['Fuels']['title'] = "Fuels";
        $this->subject['Fuels']['category'] = $this->SPM . "#Ecology";
        $this->subject['APPENDIX: FIRE REGIME TABLE']['title'] = "Fire Regime Table";
        $this->subject['APPENDIX: FIRE REGIME TABLE']['category'] = $this->SPM . "#Ecology";
        $this->subject['Fire Management Considerations']['title'] = "Fire Management Considerations";
        $this->subject['Fire Management Considerations']['category'] = $this->SPM . "#Management";
        $this->subject['DIET']['title'] = "Diet";
        $this->subject['DIET']['category'] = $this->SPM . "#TrophicStrategy";
        $this->subject['Temporal and spatial variation in the diet']['title'] = "Temporal and spatial variation in the diet";
        $this->subject['Age']['title'] = "Age";
        $this->subject['Forage quality']['title'] = "Forage quality";
        $this->subject['Temporal and spatial variation in the diet']['category'] = $this->SPM . "#Ecology";
        $this->subject['Age']['category'] = $this->SPM . "#Ecology";
        $this->subject['Forage quality']['category'] = $this->SPM . "#Ecology";
        $this->subject['Behaviors']['title'] = "Behaviors";
        $this->subject['Behaviors']['category'] = $this->SPM . "#Behaviour";
        //plants
        $this->subject['GENERAL INFORMATION ON DISTRIBUTION']['title'] = "General Information On Distribution";
        $this->subject['GENERAL INFORMATION ON DISTRIBUTION']['category'] = $this->SPM . "#Distribution";
        $this->subject['STATES/PROVINCES']['title'] = "States or Provinces";
        $this->subject['STATES/PROVINCES']['category'] = $this->SPM . "#Distribution";
        $this->subject['HABITAT TYPES AND PLANT COMMUNITIES']['title'] = "Key Plant Community Associations";
        $this->subject['HABITAT TYPES AND PLANT COMMUNITIES']['category'] = $this->SPM . "#Habitat";
        $this->subject['Soils and moisture']['title'] = "Soils and moisture";
        $this->subject['Soils and moisture']['category'] = $this->SPM . "#Habitat";
        $this->subject['SITE CHARACTERISTICS AND PLANT COMMUNITIES']['title'] = "Site Characteristics and Plant Communities";
        $this->subject['SITE CHARACTERISTICS AND PLANT COMMUNITIES']['category'] = $this->SPM . "#Habitat";
        $this->subject['PALATIBILITY']['title'] = "Palatibility to livestock and wildlife";
        $this->subject['PALATIBILITY']['category'] = $this->SPM . "#Use";
        $this->subject['IMPORTANCE TO LIVESTOCK AND WILDLIFE']['title'] = "Importance to Livestock and Wildlife";
        $this->subject['IMPORTANCE TO LIVESTOCK AND WILDLIFE']['category'] = $this->SPM . "#Use";
        $this->subject['NUTRITIONAL VALUE FOR WILDLIFE']['title'] = "Nutritional value for wildlife";
        $this->subject['NUTRITIONAL VALUE FOR WILDLIFE']['category'] = $this->SPM . "#Use";
        $this->subject['NUTRITIONAL VALUE']['title'] = "Nutritional Value";
        $this->subject['NUTRITIONAL VALUE']['category'] = $this->SPM . "#Use";
        $this->subject['PALATABILITY AND NUTRITIONAL VALUE']['title'] = "Palatability and Nutritional Value";
        $this->subject['PALATABILITY AND NUTRITIONAL VALUE']['category'] = $this->SPM . "#Use";
        $this->subject['PALATABILITY']['title'] = "Palatability";
        $this->subject['PALATABILITY']['category'] = $this->SPM . "#Use";
        $this->subject['COVER VALUE FOR WILDLIFE']['title'] = "Cover value for wildlife";
        $this->subject['COVER VALUE FOR WILDLIFE']['category'] = $this->SPM . "#Use";
        $this->subject['COVER VALUE']['title'] = "Cover Value";
        $this->subject['COVER VALUE']['category'] = $this->SPM . "#Use";
        $this->subject['VALUE FOR REHABILITATION OF DISTURBED SITES']['title'] = "Value for rehabilitation of disturbed sites";
        $this->subject['VALUE FOR REHABILITATION OF DISTURBED SITES']['category'] = $this->SPM . "#Use";
        $this->subject['UTILIZATION']['title'] = "Utilization";
        $this->subject['UTILIZATION']['category'] = $this->SPM . "#Use";
        $this->subject['OTHER UTILIZATIONS']['title'] = "Other Utilizations";
        $this->subject['OTHER UTILIZATIONS']['category'] = $this->SPM . "#Use";
        $this->subject['OTHER USES AND VALUES']['title'] = "Other uses and values";
        $this->subject['OTHER USES AND VALUES']['category'] = $this->SPM . "#Use";
        $this->subject['OTHER USES']['title'] = "Other uses and values";
        $this->subject['OTHER USES']['category'] = $this->SPM . "#Use";
        $this->subject['IMPACTS']['title'] = "Management Considerations: Impacts";
        $this->subject['IMPACTS']['category'] = $this->SPM . "#Management";
        $this->subject['CONTROL']['title'] = "Management Considerations: Control";
        $this->subject['CONTROL']['category'] = $this->SPM . "#Management";
        $this->subject['OTHER MANAGEMENT CONSIDERATIONS']['title'] = "Management considerations";
        $this->subject['OTHER MANAGEMENT CONSIDERATIONS']['category'] = $this->SPM . "#Management";
        $this->subject['GENERAL BOTANICAL CHARACTERISTICS']['title'] = "Description";
        $this->subject['GENERAL BOTANICAL CHARACTERISTICS']['category'] = $this->SPM . "#Morphology";
        $this->subject['Botanical description']['title'] = "Description";
        $this->subject['Botanical description']['category'] = $this->SPM . "#Morphology";
        $this->subject['RAUNKIAER LIFE FORM']['title'] = "Growth Form (according to Raunkiær Life-form classification)";
        $this->subject['RAUNKIAER LIFE FORM']['category'] = $this->SPM . "#Ecology";
        $this->subject['RAUNKIAER LIFE FORM']['link'] = "http://en.wikipedia.org/wiki/Raunki%C3%A6r_plant_life-form";
        $this->subject['LIFE FORM']['title'] = "Life Form";
        $this->subject['LIFE FORM']['category'] = $this->SPM . "#Ecology";
        $this->subject['REGENERATION PROCESSES']['title'] = "Regeneration Processes";
        $this->subject['REGENERATION PROCESSES']['category'] = $this->SPM . "#Ecology";
        $this->subject['Pollination and breeding system']['title'] = "Pollination and breeding system";
        $this->subject['Seed production']['title'] = "Seed production";
        $this->subject['Seed dispersal']['title'] = "Seed dispersal";
        $this->subject['Seed banking']['title'] = "Seed banking";
        $this->subject['Germination']['title'] = "Germination";
        $this->subject['Seedling establishment and plant growth']['title'] = "Seedling establishment and plant growth";
        $this->subject['Vegetative regeneration']['title'] = "Vegetative regeneration";
        $this->subject['SITE CHARACTERISTICS']['title'] = "Habitat characteristics";
        $this->subject['SITE CHARACTERISTICS']['category'] = $this->SPM . "#Habitat";
        $this->subject['SUCCESSIONAL STATUS']['title'] = "Successional Status";
        $this->subject['SUCCESSIONAL STATUS']['category'] = $this->SPM . "#Ecology";
        $this->subject['SUCCESSIONAL STATUS']['link'] = "http://en.wikipedia.org/wiki/Ecological_succession";
        $this->subject['SEASONAL DEVELOPMENT']['title'] = "Phenology";
        $this->subject['SEASONAL DEVELOPMENT']['category'] = $this->SPM . "#Cyclicity";
        $this->subject['SEASONAL DEVELOPMENT']['link'] = "http://www.usanpn.org/about/phenology";
        $this->subject['FIRE ECOLOGY OR ADAPTATIONS']['title'] = "Fire Ecology";
        $this->subject['FIRE ECOLOGY OR ADAPTATIONS']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE ECOLOGY']['title'] = "Fire Ecology";
        $this->subject['FIRE ECOLOGY']['category'] = $this->SPM . "#Ecology";
        $this->subject['POSTFIRE REGENERATION STRATEGY']['title'] = "Post-fire Regeneration";
        $this->subject['POSTFIRE REGENERATION STRATEGY']['category'] = $this->SPM . "#Ecology";
        $this->subject['IMMEDIATE FIRE EFFECT ON PLANT']['title'] = "Immediate Effect of Fire";
        $this->subject['IMMEDIATE FIRE EFFECT ON PLANT']['category'] = $this->SPM . "#Ecology";
        $this->subject['DISCUSSION AND QUALIFICATION OF FIRE EFFECT']['title'] = "Broad-scale Impacts of Fire";
        $this->subject['DISCUSSION AND QUALIFICATION OF FIRE EFFECT']['category'] = $this->SPM . "#Ecology";
        $this->subject['PLANT RESPONSE TO FIRE']['title'] = "Plant Response to Fire";
        $this->subject['PLANT RESPONSE TO FIRE']['category'] = $this->SPM . "#Ecology";
        $this->subject['DISCUSSION AND QUALIFICATION OF PLANT RESPONSE']['title'] = "Broad-scale Impacts of Plant Response to Fire";
        $this->subject['DISCUSSION AND QUALIFICATION OF PLANT RESPONSE']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE MANAGEMENT CONSIDERATIONS']['title'] = "Fire Management Considerations";
        $this->subject['FIRE MANAGEMENT CONSIDERATIONS']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE EFFECTS']['title'] = "Fire Effects";
        $this->subject['FIRE EFFECTS']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE EFFECTS AND USE']['title'] = "Fire Effects";
        $this->subject['FIRE EFFECTS AND USE']['category'] = $this->SPM . "#Ecology";
        $this->subject['FUELS AND FIRE REGIMES']['title'] = "Fuels and Fire Regimes";
        $this->subject['FUELS AND FIRE REGIMES']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE ADAPTATIONS AND PLANT RESPONSE TO FIRE']['title'] = "Fire adaptations and plant response to fire";
        $this->subject['FIRE ADAPTATIONS AND PLANT RESPONSE TO FIRE']['category'] = $this->SPM . "#Ecology";
        $this->subject['IMPACTS AND CONTROL']['title'] = "Impacts and Control";
        $this->subject['IMPACTS AND CONTROL']['category'] = $this->SPM . "#Management";
        $this->subject['WOOD PRODUCTS VALUE']['title'] = "Wood Products Value";
        $this->subject['WOOD PRODUCTS VALUE']['category'] = $this->SPM . "#Use";
        $this->subject['IMMEDIATE FIRE EFFECT ON LICHEN']['title'] = "Immediate Fire Effect on Lichen";
        $this->subject['IMMEDIATE FIRE EFFECT ON LICHEN']['category'] = $this->SPM . "#Ecology";
        $this->subject['LICHEN RESPONSE TO FIRE']['title'] = "Lichen Response to Fire";
        $this->subject['LICHEN RESPONSE TO FIRE']['category'] = $this->SPM . "#Ecology";
        $this->subject['DISCUSSION AND QUALIFICATION OF LICHEN RESPONSE']['title'] = "Discussion and Qualification of Lichen Response";
        $this->subject['DISCUSSION AND QUALIFICATION OF LICHEN RESPONSE']['category'] = $this->SPM . "#Ecology";
        /* FIRE CASE STUDIES */
        $this->subject['FIRE CASE STUDY CITATION']['title'] = "";
        $this->subject['FIRE CASE STUDY CITATION']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE CASE STUDY REFERENCE']['title'] = "";
        $this->subject['FIRE CASE STUDY REFERENCE']['category'] = $this->SPM . "#Ecology";
        $this->subject['SEASON/SEVERITY CLASSIFICATION']['title'] = "Season/Severity Classification";
        $this->subject['SEASON/SEVERITY CLASSIFICATION']['category'] = $this->SPM . "#Ecology";
        $this->subject['STUDY LOCATION']['title'] = "Study Location";
        $this->subject['STUDY LOCATION']['category'] = $this->SPM . "#Ecology";
        $this->subject['PREFIRE HABITAT']['title'] = "Prefire Habitat";
        $this->subject['PREFIRE HABITAT']['category'] = $this->SPM . "#Ecology";
        $this->subject['SITE DESCRIPTION']['title'] = "Site Description";
        $this->subject['SITE DESCRIPTION']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE DESCRIPTION']['title'] = "Fire Description";
        $this->subject['FIRE DESCRIPTION']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE EFFECTS ON ANIMAL SPECIES AND HABITAT']['title'] = "Fire Effects on Animal Species and Habitat";
        $this->subject['FIRE EFFECTS ON ANIMAL SPECIES AND HABITAT']['category'] = $this->SPM . "#Ecology";
        $this->subject['FIRE MANAGEMENT IMPLICATIONS']['title'] = "Fire Management Implications";
        $this->subject['FIRE MANAGEMENT IMPLICATIONS']['category'] = $this->SPM . "#Ecology";
    }

}
?>