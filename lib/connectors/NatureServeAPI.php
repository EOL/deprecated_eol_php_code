<?php
namespace php_active_record;
/* connector: [263] 
This needs an estimate of 80K API calls. Doable but maybe not scalable for frequent harvest. Cache expires by default in 25 days from Functions::lookup_with_cache()
*/
class NatureServeAPI
{
    // https://services.natureserve.org/idd/rest/ns/v1.1/globalSpecies/comprehensive?NSAccessKeyId=72ddf45a-c751-44c7-9bca-8db3b4513347&uid=ELEMENT_GLOBAL.2.104386
    const API_PREFIX = "https://services.natureserve.org/idd/rest/ns/v1.1/globalSpecies/comprehensive?NSAccessKeyId=72ddf45a-c751-44c7-9bca-8db3b4513347&uid=";
    // const SPECIES_LIST_URL = "https://tranxfer.natureserve.org/download/longterm/EOL/gname_uid_crosswalk.xml";
    // const SPECIES_LIST_URL = "http://localhost/cp_new/NatureServe/gname_uid_crosswalk.xml";
    const SPECIES_LIST_URL  = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/NatureServe/gname_uid_crosswalk.xml";
    const IMAGE_API_PREFIX = "https://services.natureserve.org/idd/rest/ns/v1/globalSpecies/images?uid=";
    
    public function __construct()
    {
        $this->global_status_code_labels();
        $this->global_status_qualifiers();
        $this->national_status_code_labels();
        $this->national_status_qualifiers();
        
        // $this->download_options = array("cache" => 1, "download_wait_time" => 500000, "timeout" => 3600, "download_attempts" => 1); //"delay_in_minutes" => 1
        // $this->download_options['expire_seconds'] = 60*60*24*30; //preferably monthly
    }
    
    public function get_all_taxa()
    {
        if(!file_exists(DOC_ROOT . 'tmp/dwc_archive_test')) mkdir(DOC_ROOT . 'tmp/dwc_archive_test');
        if(!file_exists(DOC_ROOT . 'tmp/natureserve')) mkdir(DOC_ROOT . 'tmp/natureserve');
        if(!file_exists(DOC_ROOT . 'tmp/natureserve/images')) mkdir(DOC_ROOT . 'tmp/natureserve/images');
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => DOC_ROOT . "/tmp/dwc_archive_test/"));
        
        $species_list_path = DOC_ROOT . "update_resources/connectors/files/natureserve_species_list.xml";
        shell_exec("rm -f $species_list_path");
        shell_exec("curl ". self::SPECIES_LIST_URL ." -o $species_list_path");
        
        $reader = new \XMLReader();
        $reader->open($species_list_path);
        $records = array(); $i = 0; $m = 80871/6; //as of May 3, 2018 -- Total Records: 80871
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "DATA_RECORD") {
                $i++;
                if(($i % 1000) == 0) echo " ".number_format($i)." ";
                
                /* breakdown when caching:
                $cont = false;
                // if($i >=  1    && $i < $m) $cont = true;
                // if($i >=  $m   && $i < $m*2) $cont = true;
                // if($i >=  $m*2 && $i < $m*3) $cont = true;
                // if($i >=  $m*3 && $i < $m*4) $cont = true;
                // if($i >=  $m*4 && $i < $m*5) $cont = true;
                // if($i >=  $m*5 && $i < $m*6) $cont = true;

                // if($i >=  ($m*5)-3000 && $i < $m*5) $cont = true;
                if($i >=  ($m*6)-3000 && $i < $m*6) $cont = true;

                if(!$cont) continue;
                */
                
                $record = simplexml_load_string($reader->readOuterXML(), null, LIBXML_NOCDATA);
                $records[] = (string) $record->EGT_UID;
            }
            // if($i >= 20) break; //debug only
        }
        echo "Total Records: ". count($records) ."\n";

        $chunk_size = 1;

        // shuffle($records);
        // array_unshift($records, 'ELEMENT_GLOBAL.2.104470'); // Bald eagle
        array_unshift($records, 'ELEMENT_GLOBAL.2.102211'); // Polar bear
        // array_unshift($records, 'ELEMENT_GLOBAL.2.106470'); // bobcat - Lynx rufus
        // array_unshift($records, 'ELEMENT_GLOBAL.2.104731'); // striped bass - Morone saxatilis
        array_unshift($records, 'ELEMENT_GLOBAL.2.105926'); // American Bullfrog
        array_unshift($records, 'ELEMENT_GLOBAL.2.104777'); // White tailed deer
        array_unshift($records, 'ELEMENT_GLOBAL.2.100925'); // golden eagle
        
        $records = array_unique($records);
        $chunks = array_chunk($records, $chunk_size);
        $i = 0;
        $start_time = time_elapsed();
        foreach($chunks as $chunk) {
            $this->lookup_multiple_ids($chunk);
            // if($i % 500 == 0) print_r($this->archive_builder->file_columns);
            
            $i += $chunk_size;
            if($i % 1000 == 0) {
                $estimated_total_time = (((time_elapsed() - $start_time) / $i) * count($records));
                echo "Time spent ($i records) ". time_elapsed() ."\n";
                echo "Estimated total seconds : $estimated_total_time\n";
                echo "Estimated total hours : ". ($estimated_total_time / (60 * 60)) ."\n";
                echo "Memory : ". memory_get_usage() ."\n";
            }
            // if($i >= 100) break;
        }
        $this->archive_builder->finalize();
    }
    
    private function lookup_multiple_ids($ids)
    {
        $url = self::API_PREFIX . implode(",", $ids);
        $details_xml = Functions::lookup_with_cache($url, array('validation_regex' => '<\/globalSpeciesList>')); //default expires in 25 days
        $xml = simplexml_load_string($details_xml);
        if($val = @$xml->globalSpecies) {
            $i = 0;
            foreach($val as $species_record) {
                $i++;
                if($i % 1000 == 0) echo "\n$i.";
                $this->process_species_xml($species_record);
            }
        }
    }
    
    private function process_species_xml($details_xml)
    {
        $this->current_details_xml = $details_xml;
        $scientific_name = $this->current_details_xml->classification->names->scientificName->unformattedName;
        if(preg_match("/(sp|pop)\. [0-9]/", $scientific_name)) return;
        // echo "$scientific_name\n";
        
        $identifier = (string) $this->current_details_xml['uid'];
        $this->get_images($identifier);
        
        $taxon_reference_ids = $this->references();
        
        $this->write_taxonomy($taxon_reference_ids);
        $text = "";
        $this->append_description($text, @$this->current_details_xml->classification->taxonomy->formalTaxonomy->taxonomicComments, 'Comments');
        $this->write_text_description("", "taxonomic_comments", "http://www.eol.org/voc/table_of_contents#Taxonomy", $text);
        
        $this->write_natureserve_status();
        $this->national_statuses();
        $this->global_abundance();
        $this->number_of_occurrences();
        $this->trends();
        $this->threats();
        $this->map_images();
        $this->global_range();
        $this->national_range();
        $this->endemism();
        $this->economic_uses();
        $this->management_summary();
        $this->global_protection();
        $this->management();
        $this->taxon_biology();
        $this->diagnostic_description();
        $this->life_cycle();
        $this->reproduction();
        $this->associations();
        $this->ecology();
        $this->migration();
        $this->habitat();
        $this->trophic_strategy();
        $this->cyclicity();
        $this->size();
    }
    
    private function write_taxonomy($reference_ids = array())
    {
        $scientific_name = (string) @$this->current_details_xml->classification->names->scientificName->unformattedName;
        $author = (string) @$this->current_details_xml->classification->names->scientificName->nomenclaturalAuthor;
        $canonical_form = self::canonical_form($scientific_name);
        $rank = 'species';
        if(preg_match("/(.*? .*?) (.*)/", $canonical_form, $arr)) $rank = 'subspecies';
        $full_scientific_name = trim($scientific_name . " " . $author);
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = (string) $this->current_details_xml['uid'];
        $t->kingdom = (string) @$this->current_details_xml->classification->taxonomy->formalTaxonomy->kingdom;
        $t->phylum = (string) @$this->current_details_xml->classification->taxonomy->formalTaxonomy->phylum;
        $t->class = (string) @$this->current_details_xml->classification->taxonomy->formalTaxonomy->class;
        $t->order = (string) @$this->current_details_xml->classification->taxonomy->formalTaxonomy->order;
        $t->family = (string) @$this->current_details_xml->classification->taxonomy->formalTaxonomy->family;
        $t->genus = (string) @$this->current_details_xml->classification->taxonomy->formalTaxonomy->genus;
        $t->scientificName = $full_scientific_name;
        $t->taxonRank = $rank;
        $t->source = (string) @$this->current_details_xml->natureServeExplorerURI;
        // $t->source = "http://www.natureserve.org/explorer/servlet/NatureServe?loadTemplate=species_RptComprehensive.wmt&elKey=" . $t->taxonID;
        $t->vernacularName = (string) @$this->current_details_xml->classification->names->natureServePrimaryGlobalCommonName;
        if($reference_ids) $t->referenceID = implode("; ", $reference_ids);
        $this->archive_builder->write_object_to_file($t);
    }
    
    private function write_natureserve_status()
    {
        $status = @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus;
        if(!$status) return;
        $text = "";
        
        if($rounded_rank_code = (string) @$status->roundedRank->code) {
            $text = "<p><strong>Rounded Global Status Rank</strong>: ";
            if($rank_description = (string) @$status->roundedRank->description) $text .= "<a href=\"http://www.natureserve.org/explorer/ranking.htm#globalstatus\">". $rounded_rank_code ."</a> - $rank_description";
            elseif($value = $this->get_global_status_code_from_label((string) $rounded_rank_code)) $text .= $value;
            $text .= "</p>";
        }
        elseif($rank_code = (string) @$status->rank->code) {
            $text .= "<p><strong>Global Status Rank</strong>:";
            if($rank_description = (string) @$status->roundedRank->description) $text .= "<a href=\"http://www.natureserve.org/explorer/ranking.htm#globalstatus\">". $rank_code ."</a> - $rank_description";
            elseif($value = $this->get_global_status_code_from_label((string) $rounded_rank_code)) $text .= $value;
            $text .= "</p>";
        }
        
        $this->append_description($text, @$status->reasons, 'Reasons');
        
        static $vulnerability_codes = array('A' => 'Highly Vulnerable', 'B' => 'Moderately Vulnerable',
            'C' => 'Not Intrinsically Vulnerable', 'U' => 'Unknown');
        $this->append_code_description_comment($text,  @$status->conservationStatusFactors->intrinsicVulnerability,
            'Intrinsic Vulnerability', $vulnerability_codes);
        
        static $specificity_codes = array('A' => 'Very Narrow', 'B' => 'Narrow', 'C' => 'Moderate', 'D' => 'Broad', 'U' => 'Unknown');
        $this->append_code_description_comment($text,  @$status->conservationStatusFactors->environmentalSpecificity,
            'Environmental Specificity', $specificity_codes);
        
        $this->append_description($text, @$status->conservationStatusFactors->otherConsiderations, 'Other Considerations');
        $this->write_text_description("NatureServe Conservation Status", "conservation_status",
            "http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus", $text,
            array(  'creator' => @trim((string) $status->conservationStatusFactors->conservationStatusAuthors['displayValue'])));
    }
    
    private function national_statuses()
    {
        $descriptions = array();
        if($statuses = @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->nationalStatuses)
        {
            foreach($statuses->nationalStatus as $status) {
                $description = "<h5>". $status['nationName'] ."</h5>";
                if($rounded_rank_code = (string) @$status->roundedRank->code) {
                    $description .= "<p><strong>Rounded National Status Rank</strong>: ";
                    if($rank_description = (string) @$status->roundedRank->description) $description .= "<a href=\"http://www.natureserve.org/explorer/ranking.htm#natsub\">$rounded_rank_code</a> - $rank_description";
                    elseif($value = $this->get_national_status_code_from_label((string) $rounded_rank_code)) $description .= $value;
                    $description .= "</p>";
                }elseif($rank_code = (string) @$status->rank->code)
                {
                    $descriptions[] .= "<p><strong>National Status Rank</strong>:";
                    if($rank_description = (string) @$status->roundedRank->description) $description .= "<a href=\"http://www.natureserve.org/explorer/ranking.htm#natsub\">$rank_code</a> - $rank_description";
                    elseif($value = $this->get_national_status_code_from_label((string) $rounded_rank_code)) $description .= $value;
                    
                    $description .= "</p>";
                }
                $descriptions[] = $description;
            }
        }
        $descriptions = array_filter($descriptions);
        if($descriptions) {
            $this->write_text_description("National NatureServe Conservation Status", "national_conservation_status",
              "http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus",
              implode("", $descriptions),
              array(  'creator' => @trim((string) $this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->conservationStatusFactors->conservationStatusAuthors['displayValue'])));
        }
    }
    
    private function global_abundance()
    {
        $text = $this->get_code_description_comment( @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->conservationStatusFactors->globalAbundance);
        $this->write_text_description("Global Abundance", "global_abundance", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#PopulationBiology", $text);
    }
    
    private function number_of_occurrences()
    {
        static $occurrence_codes = array('Z' => '0 occurrences', 'A' => '1-5 occurrences', 'B' => '6-20 occurrences',
            'C' => '21-80 occurrences', 'D' => '81-300 occurrences', 'E' => '>300 occurrences', 'U' => 'Unknown');
        $text = $this->get_code_description_comment( @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->conservationStatusFactors->estimatedNumberOfOccurrences, 'Estimated Number of Occurrences', $occurrence_codes);
        if($text) {
            $text = "<p>Note: For many non-migratory species, occurrences are roughly equivalent to populations.</p>" . $text;
            $this->write_text_description("Number of Occurrences", "occurrences", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#PopulationBiology", $text);
        }
    }
    
    private function trends()
    {
        $text = "";
        static $trend_codes = array(
            'A' => 'Severely declining (decline of >70% in population size, range, area occupied, and|or number or condition of occurrences)',
            'B' => 'Very rapidly declining (decline of 50-70%)',
            'C' => 'Rapidly declining (decline of 30-50%)',
            'D' => 'Declining (decline of 10-30%)',
            'E' => 'Stable (unchanged or remaining within ±10% fluctuation)',
            'F' => 'Increasing (increase of >10%)',
            'U' => 'Unknown (short-term trend unknown)');
        
        $this->append_code_description_comment($text,  @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->conservationStatusFactors->globalShortTermTrend,
            'Global Short Term Trend', $trend_codes);
        
        $this->append_code_description_comment($text,  @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->conservationStatusFactors->globalLongTermTrend,
            'Global Long Term Trend', $trend_codes);
        
        $this->write_text_description("", "global_trends", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Trends", $text);
    }
    
    private function threats()
    {
        $text = "";
        static $threat_codes = array(
            'A' => 'Very threatened throughout its range communities directly exploited or their composition and structure irreversibly threatened by man-made forces, including exotic species',
            'B' => 'Moderately threatened throughout its range, communities provide natural resources that when exploited alter the composition and structure of the community over the long-term, but are apparently recoverable',
            'C' => 'Not very threatened throughout its range, communities often provide natural resources that when exploited alter the composition and structure over the short-term, or communities are self-protecting because they are unsuitable for other uses',
            'D' => 'Unthreatened throughout its range, communities may be threatened in minor portions of the range or degree of variation falls within natural variation',
            'U' => 'Unknown (short-term trend unknown)');
        
        $this->append_code_description_comment($text,  @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->conservationStatusFactors->threat->degreeOfThreat,
            'Degree of Threat', $threat_codes);
        
        $this->append_description($text, @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->conservationStatusFactors->threat->comments, 'Comments');
        $this->write_text_description("", "threats", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Threats", $text);
    }
    
    private function map_images()
    {
        $this->write_image_description("U.S. States and Canadian Provinces", "conservation_map", 'map',
            (string) @$this->current_details_xml->distribution->conservationStatusMap,
            array('description' => '<img src="http://content60.eol.org/content/2011/12/06/23/17639.png"/><p>NatureServe conservation status ranks for U.S. states and Canada provinces.  See <a href="http://www.natureserve.org/explorer/ranking.htm#natsub">NatureServe Conservation Status</a> for more information about the ranks.</p>', 'rating' => 2));
            
        $this->write_image_description("Range Map", "range_map", 'map',
            (string) @$this->current_details_xml->distribution->rangeMap->rangeMapURI,
            array('creator' => @trim((string) $this->current_details_xml->distribution->rangeMap->rangeMapCompilers), 'rating' => 2, 'description' => trim('New World Range Map. ' . @trim((string) $this->current_details_xml->distribution->rangeMap->rangeMapCompilers))));
    }
    
    private function global_range()
    {
        if($distribution = @$this->current_details_xml->distribution) {
            $text = "";
            $range = (string) @$distribution->globalRange->description;
            $comments = (string) @$distribution->globalRange->comments;
            if($range && $comments) $text = "($range) $comments";
            elseif($range) $text = $range;
            elseif($comments) $text = $comments;
            if($text) {
                $this->write_text_description("", "global_range", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution",
                    "<p><strong>Global Range</strong>: $text", array("rating" => 2));
            }
        }
    }
    
    private function national_range()
    {
        if($nations = @$this->current_details_xml->distribution->nations) {
            $descriptions = array();
            foreach($nations->nation as $nation) {
                $text = "";
                $this->append_description($text, @$nation->nationalDistributions->nationalDistribution->origin, 'Origin');
                $this->append_description($text, @$nation->nationalDistributions->nationalDistribution->regularity, 'Regularity');
                $this->append_description($text, @$nation->nationalDistributions->nationalDistribution->currentPresenceAbsence, 'Currently');
                $this->append_description($text, @$nation->nationalDistributions->nationalDistribution->distributionConfidence, 'Confidence');
                $this->append_description($text, @$nation->nationalDistributions->nationalDistribution->population, 'Type of Residency');
                if($text) {
                    $descriptions[] = "<h5>". $nation['nationName'] ."</h5>" . $text;
                }
            }
            $descriptions = array_filter($descriptions);
            if($descriptions) {
                $this->write_text_description("National Distribution", "national_distributions",
                  "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution", implode("", $descriptions), array("rating" => 2));
            }
        }
    }
    
    private function endemism()
    {
        if($endemism = @$this->current_details_xml->distribution->endemism) {
            static $endemism_codes = array('S' => 'state|province endemic', 'N' => 'national endemic', 'M' => 'multinational distribution',
                'MSB' => 'occurs in multiple nations, breeds in only one subnation',
                'MNB' => 'occurs in multiple nations, breeds in multiple state|provinces of only one nation',
                'NSB' => 'occurs in multiple subnations of only one nation, breeds in only one state|province');
            if($text = $this->get_code_description_comment($endemism, '', $endemism_codes)) {
                $this->write_text_description("", "endemism", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution", $text);
            }
        }
    }
    
    private function economic_uses()
    {
        if($econ = @$this->current_details_xml->economicAttributes) {
            $text = "";
            if($uses = @$econ->economicUses) {
                $all_uses = array();
                foreach(@$uses->economicUse as $use) $all_uses[] = (string) $use;
                if($all_uses) $text .= "<p><strong>Uses</strong>: ". implode($all_uses, ", ")."</p>";
            }
            $all_methods = array();
            if($uses = @$econ->productionMethods) {
                foreach(@$uses->productionMethod as $method) $all_methods[] = (string) $method;
                if($all_methods) $text .= "<p><strong>Production Methods</strong>: ". implode($all_methods, ", ")."</p>";
            }
            $this->append_description($text, @$econ->economicComments, 'Comments');
            $this->write_text_description("Economic Uses", "economic_uses", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Use", $text);
        }
    }
    
    private function management_summary()
    {
        if($management = @$this->current_details_xml->managementSummary) {
            $text = "";
            $this->append_description($text, @$management->stewardshipOverview, 'Stewardship Overview');
            $this->append_description($text, @$management->speciesImpact, 'Species Impact');
            $this->append_description($text, @$management->economicComments, 'Comments');
            $this->write_text_description("", "management_summary", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#RiskStatement", $text,
                array(  'creator' => @trim((string) $management->managementSummaryAuthors['displayValue']),
                        'created' => @trim((string) $management->editionDate)));
        }
    }
    
    private function global_protection()
    {
        $text = "";
        if($protection = @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->conservationStatusFactors->globalProtection) {
            static $protection_codes = array(
                'A' => 'No occurrences appropriately protected and managed',
                'B' => 'Few (1-3) occurrences appropriately protected and managed',
                'C' => 'Several (4-12) occurrences appropriately protected and managed',
                'D' => 'many (13-40) occurrences appropriately protected and managed',
                'E' => 'very many (>40) occurrences appropriately protected and managed',
                'U' => 'unknown whether any occurrences appropriately protected and managed');
            $text = $this->get_code_description_comment($protection, 'Global Protection', $protection_codes);
            $this->append_description($text, @$protection->needs, 'Needs');
            $this->write_text_description("", "global_protection", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Management", $text);
        }
    }
    
    private function management()
    {
        if($management = @$this->current_details_xml->managementSummary) {
            $text = "";
            $this->append_description($text, @$management->restorationPotential, 'Restoration Potential');
            $this->append_description($text, @$management->preserveSelectionAndDesignConsiderations, 'Preserve Selection and Design Considerations');
            $this->append_description($text, @$management->managementRequirements, 'Management Requirements');
            $this->append_description($text, @$management->managementPrograms, 'Management Programs');
            $this->append_description($text, @$management->monitoringPrograms, 'Monitoring Programs');
            $this->append_description($text, @$management->managementResearchPrograms, 'Management Research Programs');
            $this->append_description($text, @$management->managementResearchNeeds, 'Management Research Needs');
            $this->append_description($text, @$management->biologicalResearchNeeds, 'Biological Research Needs');
            $this->append_description($text, @$management->additionalTopics, 'Comments');
            $this->write_text_description("", "management", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Management", $text,
                array(  'creator' => @trim((string) $management->managementSummaryAuthors['displayValue']),
                        'created' => @trim((string) $management->editionDate)));
        }
    }
    
    private function taxon_biology()
    {
        // echo "WE NEED TO HIDE THESE\n";
        // if($ecology = @$this->current_details_xml->ecologyAndLifeHistory)
        // {
        //     $text = "";
        //     $this->append_description($text, @$ecology->ecologyAndLifeHistoryDescription->shortGeneralDescription);
        //     $this->append_description($text, @$ecology->ecologyAndLifeHistoryDescription->generalDescription);
        //     $this->append_description($text, @$ecology->ecologyAndLifeHistoryDescription->technicalDescription);
        //     $this->write_text_description("", "ecology_life_history", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology", $text,
        //         array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
        //                 'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        // }
    }
    
    private function diagnostic_description()
    {
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            $text = "";
            $this->append_description($text, @$ecology->diagnosticCharacteristics);
            $this->write_text_description("", "diagnostic_description", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function life_cycle()
    {
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            $text = "";
            $all_durations = array();
            if($durations = @$ecology->durations) {
                foreach(@$durations->duration as $duration) $all_durations[] = (string) $duration;
                if($all_durations) $text .= "<p><strong>Persistence</strong>: ". implode($all_durations, ", ")."</p>";
            }
            $this->write_text_description("", "life_cycle", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#LifeCycle", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function reproduction()
    {
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            $text = "";
            $this->append_description($text, @$ecology->reproductionComments);
            $this->write_text_description("", "reproduction", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function associations()
    {
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            $text = "";
            $this->append_description($text, @$ecology->knownPests, 'Known Pests');
            $this->write_text_description("", "reproduction", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function ecology()
    {
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            $text = "";
            $this->append_description($text, @$ecology->ecologyComments);
            $this->write_text_description("", "ecology_comments", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Ecology", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function migration()
    {
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            $text = "";
            $this->append_description($text, @$ecology->migration->nonMigrant, 'Non-Migrant');
            $this->append_description($text, @$ecology->migration->locallyMigrant, 'Locally Migrant');
            $this->append_description($text, @$ecology->migration->longDistanceMigrant, 'Long Distance Migrant');
            $this->append_description($text, @$ecology->migration->mobilityAndMigrationComments);
            
            $text = str_replace("Non-Migrant</strong>: Yes", "Non-Migrant</strong>: Yes. At least some populations of this species do not make significant seasonal migrations.  Juvenile dispersal is not considered a migration.", $text);
            $text = str_replace("Non-Migrant</strong>: No", "Non-Migrant</strong>: No. All populations of this species make significant seasonal migrations.", $text);
            $text = str_replace("Locally Migrant</strong>: Yes", "Locally Migrant</strong>: Yes. At least some populations of this species make local extended movements (generally less than 200 km) at particular times of the year (e.g., to breeding or wintering grounds, to hibernation sites).", $text);
            $text = str_replace("Locally Migrant</strong>: No", "Locally Migrant</strong>: No. No populations of this species make local extended movements (generally less than 200 km) at particular times of the year (e.g., to breeding or wintering grounds, to hibernation sites).", $text);
            $text = str_replace("Long Distance Migrant</strong>: Yes", "Locally Migrant</strong>: Yes. At least some populations of this species make annual migrations of over 200 km.", $text);
            $text = str_replace("Long Distance Migrant</strong>: No", "Locally Migrant</strong>: No. No populations of this species make annual migrations of over 200 km.", $text);
            
            $this->write_text_description("", "migration", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Migration", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function habitat()
    {
        $text = "";
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            static $habitat_codes = array('M' => 'Marine', 'F' => 'Freshwater', 'T' => 'Terrestrial');
            $text = $this->get_code_description_comment(@$ecology->habitatType, 'Habitat Type', $habitat_codes);
            $this->append_description($text, @$ecology->habitats->habitatComments, 'Comments');
            $this->write_text_description("", "habitat", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function trophic_strategy()
    {
        $text = "";
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            $this->append_description($text, @$ecology->foodHabits->foodComments, 'Comments');
            $this->write_text_description("", "food_habits", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TrophicStrategy", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function cyclicity()
    {
        $text = "";
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            $this->append_description($text, @$ecology->phenologies->phenologyComments, 'Comments');
            $this->write_text_description("", "cyclicity", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Cyclicity", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function size()
    {
        $text = "";
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory) {
            $this->append_description($text, @$ecology->length, 'Length');
            $this->append_description($text, @$ecology->weight, 'Weight');
            $this->write_text_description("", "size", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size", $text,
                array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                        'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
        }
    }
    
    private function references()
    {
        $references = array();
        if($r = (string) @$this->current_details_xml->classification->names->scientificName->conceptReference->formattedFullCitation) {
            $ref_string = trim((string) $r);
            while(preg_match("/^(.*)&lt;br&gt;$/", $ref_string, $arr)) $ref_string = $arr[1];
            while(preg_match("/^(.*)<br>$/", $ref_string, $arr)) $ref_string = $arr[1];
            $references[$ref_string] = 1;
        }
        if(isset($this->current_details_xml->references)) {
            foreach($this->current_details_xml->references->citation as $reference) {
                $ref_string = trim((string) $reference);
                while(preg_match("/^(.*)&lt;br&gt;$/", $ref_string, $arr)) $ref_string = $arr[1];
                while(preg_match("/^(.*)<br>$/", $ref_string, $arr)) $ref_string = $arr[1];
                $references[$ref_string] = 1;
            }
        }
        // this is some kind of placeholder reference and likely is not to be displayed
        unset($references["NatureServe. Unpublished. Concept reference for taxa where a reference cannot be recorded due to insufficient BCD data for conversion; to be used as a placeholder until the correct citation is identified."]);
        unset($references["NatureServe. Unpublished. Concept reference for taxa for which no reference which describes the circumscription has been recorded; to be used as a placeholder until such a citation is identified."]);
        unset($references["NatureServe. Unpublished. Concept reference for taxa which have not yet been described; to be used as a placeholder until a citation is available which describes the circumscription of the taxon."]);
        return $this->write_references($references);
    }
    
    private function get_images($id)
    {
        $url = self::IMAGE_API_PREFIX . $id;
        $details_xml = Functions::lookup_with_cache($url, array('validation_regex' => '<\/images>')); //default expires in 25 days
        $xml = simplexml_load_string($details_xml);
        foreach($xml->image as $image) {
            // User Warning: Undefined property `mediaResourceID` on eol_schema\MediaResource as defined by `http://editors.eol.org/other_files/ontology/media_extension.xml` in /Library/WebServer/Documents/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 190
            // User Warning: Undefined property `mimeType` on eol_schema\MediaResource as defined by `http://editors.eol.org/other_files/ontology/media_extension.xml` in /Library/WebServer/Documents/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 190
            // User Warning: Undefined property `locality` on eol_schema\MediaResource as defined by `http://editors.eol.org/other_files/ontology/media_extension.xml` in /Library/WebServer/Documents/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 190
            // User Warning: Undefined property `additionalInformationURL` on eol_schema\MediaResource as defined by `http://editors.eol.org/other_files/ontology/media_extension.xml` in /Library/WebServer/Documents/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 190
            // User Warning: Undefined property `fileURL` on eol_schema\MediaResource as defined by `http://editors.eol.org/other_files/ontology/media_extension.xml` in /Library/WebServer/Documents/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 190
            // User Warning: Undefined property `rightsHolder` on eol_schema\MediaResource as defined by `http://editors.eol.org/other_files/ontology/media_extension.xml` in /Library/WebServer/Documents/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 190
            
            $dc = $image->children("http://purl.org/dc/terms/");
            if($dc->rights != 'Public Domain') continue;
            
            // print_r($dc); exit;
            
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID = $id;
            $mr->identifier = $id . "_image_" . $image->id; //mediaResourceID
            $mr->title = $dc->title;
            $mr->type = 'http://purl.org/dc/dcmitype/StillImage';
            $mr->language = 'en';
            $mr->format = @$mr->mimeType ?: $dc->format; //mimeType
            $mr->description = trim($dc->description);
            $mr->LocationCreated = $dc->coverage; //locality
            $mr->furtherInformationURL = $image->natureServeExplorerURI; //additionalInformationURL
            $mr->accessURI = $dc->identifier; //fileURL
            $mr->creator = $dc->creator;
            $mr->Owner = @$mr->rightsHolder ?: $dc->rightsHolder; //rightsHolder
            $mr->UsageTerms = 'http://creativecommons.org/licenses/publicdomain/';
            if($mr->Owner == 'Public Domain') $mr->Owner = '';
             
            if(@$dc->identifier && preg_match("/&RES=([0-9]+)X/", $dc->identifier, $arr)) {
                $width = $arr[1];
                if(@$dc->isVersionOf && preg_match("/&RES=([0-9]+)X/", $dc->isVersionOf, $arr)) {
                    $other_width = $arr[1];
                    if($width < $other_width) continue;
                }
            }else continue;
            $this->archive_builder->write_object_to_file($mr);
        }
    }
    private function write_text_description($title, $id_suffix, $subject, $description, $options = array())
    {
        if(!$description) return;
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID = (string) $this->current_details_xml['uid'];
        $mr->identifier = $mr->taxonID . "_" . $id_suffix;
        $mr->type = 'http://purl.org/dc/dcmitype/Text';
        $mr->language = 'en';
        $mr->format = 'text/html';
        $mr->furtherInformationURL = (string) @$this->current_details_xml->natureServeExplorerURI;
        $mr->description = $description;
        $mr->CVterm = $subject;
        $mr->title = $title;
        $mr->creator = @$options['creator'];
        $mr->CreateDate = @$options['created'];
        $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
        $mr->Owner = 'NatureServe';
        if(@$options['rating']) $mr->Rating = $options['rating'];
        $this->archive_builder->write_object_to_file($mr);
    }
    
    private function write_image_description($title, $id_suffix, $subtype, $fileURL, $options = array())
    {
        if(!$fileURL) return;
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID = (string) $this->current_details_xml['uid'];
        $mr->identifier = $mr->taxonID . "_" . $id_suffix;
        $mr->type = 'http://purl.org/dc/dcmitype/StillImage';
        $mr->subtype = $subtype;
        $mr->language = 'en';
        $mr->format = 'image/gif';
        $mr->furtherInformationURL = (string) @$this->current_details_xml->natureServeExplorerURI;
        $mr->title = $title;
        $mr->accessURI = $fileURL;
        $mr->creator = @$options['creator'];
        $mr->CreateDate = @$options['created'];
        $mr->description = @$options['description'];
        $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
        $mr->Owner = 'NatureServe';
        if(@$options['rating']) $mr->Rating = $options['rating'];
        $this->archive_builder->write_object_to_file($mr);
    }
    
    private function write_references($references)
    {
        if(!$references) return;
        $reference_ids = array();
        foreach($references as $ref => $junk) {
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref;
            $r->identifier = md5($ref);
            $this->archive_builder->write_object_to_file($r);
            $reference_ids[] = $r->identifier;
        }
        return $reference_ids;
    }
    
    private static function description_from_block($xml, $title)
    {
        if(!trim((string) $xml)) return NULL;
        $string = (string) $xml;
        if($string == 'false') $string = "No";
        if($string == 'true') $string = "Yes";
        $description = "<b>$title:</b> ". $string;
        if(@trim((string) $xml['units'])) $description .= " ". $xml['units'];
        return $description;
    }
    
    private static function append_description(&$working_text, $node, $title = null)
    {
        $node_text = trim((string) $node);
        if(!$node_text) return;
        if($node_text == 'false') $node_text = "No";
        if($node_text == 'true') $node_text = "Yes";
        if($title) $node_text = "<strong>$title</strong>: ". $node_text;
        if($units = @trim((string) $node['units'])) $node_text .= " $units";
        $node_text = "<p>$node_text</p>";
        $working_text .= $node_text;
        return $node_text;
    }
    
    private function get_code_description_comment($node, $title = null, $codes = array())
    {
        $text = "";
        $this->append_code_description_comment($text, $node, $title, $codes);
        return $text;
    }
    
    private function append_code_description_comment(&$working_text, $node, $title = null, $codes = array())
    {
        if(!$node) return null;
        $node_text = (string) @$node->description;
        $comments = (string) @$node->comments;
        if(!$node_text && $code = (string) @$node->code) {
            $node_text = $code;
            if($value = @$codes[$code]) $node_text .= " : $value";
        }
        
        if($node_text) {
            if($title) $node_text = "<strong>$title</strong>: " . $node_text;
            $node_text = "<p>$node_text</p>";
            if($comments) $node_text .= "<p><strong>Comments</strong>: $comments</p>";
            $working_text .= $node_text;
            return $node_text;
        }
        return null;
    }
    
    private static function canonical_form($string)
    {
        $canonical_form = $string;
        $canonical_form = preg_replace("/^x (.*)$/", "$1", $canonical_form);
        $canonical_form = preg_replace("/ pop\. .+$/", "", $canonical_form);
        $canonical_form = preg_replace("/ ssp\. [0-9]+$/", "", $canonical_form);
        $canonical_form = preg_replace("/ sp\..*$/", "", $canonical_form);
        $canonical_form = preg_replace("/ nr\..*$/", "", $canonical_form);
        $canonical_form = preg_replace("/ cf\..*$/", "", $canonical_form);
        $canonical_form = preg_replace("/ var\. [0-9]+$/", "", $canonical_form);
        $canonical_form = preg_replace("/ [0-9]+$/", "", $canonical_form);
        $canonical_form = preg_replace("/ \([A-Z][a-z]+\)/", "", $canonical_form);
        $canonical_form = preg_replace("/ hybrid$/", "", $canonical_form);
        $canonical_form = preg_replace("/ n\.$/", "", $canonical_form);
        $canonical_form = preg_replace("/ new genus$/", "", $canonical_form);
        $canonical_form = preg_replace("/ Genus 1 species$/", "", $canonical_form);
        $canonical_form = str_replace(" var. ", " ", $canonical_form);
        $canonical_form = str_replace(" aff. ", " ", $canonical_form);
        $canonical_form = str_replace(" ssp. ", " ", $canonical_form);
        $canonical_form = str_replace(" x ", " ", $canonical_form);
        return $canonical_form;
    }
    
    public function get_global_status_code_from_label($original_label)
    {
        $complete_labels = array();
        $labels = explode(",", $original_label);
        foreach($labels as $label) {
            $label = trim($label);
            if(preg_match_all("/([GT][0-9]|GX|GH|GU|GNR|GNA)([\?QC]{0,3})/", $label, $complete_codes, PREG_SET_ORDER)) {
                // print_r($complete_codes);
                foreach($complete_codes as $complete_code) {
                    $code = $complete_code[1];
                    $qualifiers = str_split($complete_code[2]);
                    if($complete_label = @$this->global_status_code_labels[$code]) {
                        $complete_qualifiers = array();
                        foreach($qualifiers as $qualifier) {
                            if($v = @$this->global_status_qualifiers[$qualifier]) $complete_qualifiers[] = $v;
                        }
                        $complete_labels[] = array('label' => $complete_label, 'qualifier' => implode(", ", $complete_qualifiers), 
                            'code' => $complete_code[0]);
                    }
                }
            }
        }
        if(!$complete_labels) return $original_label;
        if(count($complete_labels) > 1) {
            foreach($complete_labels as $k => $v) {
                $complete_labels[$k] = $v['code'] .": ". $v['label'];
                if($v['qualifier']) $complete_labels[$k] .= " - ".$v['qualifier'];
            }
            return "<a href=\"http://www.natureserve.org/explorer/ranking.htm#globalstatus\">" . $original_label . "</a> : " . 
                implode(", ", $complete_labels);
        }else {
            return "<a href=\"http://www.natureserve.org/explorer/ranking.htm#globalstatus\">" . $original_label . "</a> - " . $complete_labels[0]['label'];
        }
        return implode(", ", $complete_labels);
    }
    
    private function global_status_code_labels()
    {
        if(@$this->global_status_code_labels) return $this->global_status_code_labels;
        $this->global_status_code_labels = array();
        $this->global_status_code_labels['GX'] = "Presumed Extinct";
        $this->global_status_code_labels['GH'] = "Possibly Extinct";
        $this->global_status_code_labels['G1'] = "Critically Imperiled";
        $this->global_status_code_labels['G2'] = "Imperiled";
        $this->global_status_code_labels['G3'] = "Vulnerable";
        $this->global_status_code_labels['G4'] = "Apparently Secure";
        $this->global_status_code_labels['G5'] = "Secure";
        $this->global_status_code_labels['T1'] = "Infraspecific Taxon Critically Imperiled";
        $this->global_status_code_labels['T2'] = "Infraspecific Taxon Imperiled";
        $this->global_status_code_labels['T3'] = "Infraspecific Taxon Vulnerable";
        $this->global_status_code_labels['T4'] = "Infraspecific Taxon Apparently Secure";
        $this->global_status_code_labels['T5'] = "Infraspecific Taxon Secure";
        $this->global_status_code_labels['GU'] = "Unrankable";
        $this->global_status_code_labels['GNR'] = "Unranked";
        $this->global_status_code_labels['GNA'] = "Not Applicable";
        return $this->global_status_code_labels;
    }
    
    private function global_status_qualifiers()
    {
        if(@$this->global_status_qualifiers) return $this->global_status_qualifiers;
        $this->global_status_qualifiers = array();
        $this->global_status_qualifiers['?'] = "Inexact Numeric Rank";
        $this->global_status_qualifiers['Q'] = "Questionable taxonomy that may reduce conservation priority";
        $this->global_status_qualifiers['C'] = "Captive or Cultivated Only";
        return $this->global_status_qualifiers;
    }
    
    public function get_national_status_code_from_label($original_label)
    {
        $complete_labels = array();
        
        $labels = explode(",", $original_label);
        foreach($labels as $label) {
            $label = trim($label);
            if(preg_match_all("/([NS]([0-9]|X|H|U|NR|NA))(N$|[\?BM]{0,3})/", $label, $complete_codes, PREG_SET_ORDER)) {
                // print_r($complete_codes);
                foreach($complete_codes as $complete_code) {
                    $abbreviation = $complete_code[0];
                    $code = $complete_code[1];
                    $qualifiers = str_split($complete_code[3]);
                    if($complete_label = @$this->national_status_code_labels[$code]) {
                        $complete_qualifiers = array();
                        foreach($qualifiers as $qualifier) {
                            if($v = @$this->national_status_qualifiers[$qualifier]) $complete_qualifiers[] = $v;
                        }
                        $complete_labels[] = array('label' => $complete_label, 'qualifier' => implode(", ", $complete_qualifiers), 
                            'code' => $complete_code[0]);
                    }
                }
            }
        }
        
        if(!$complete_labels) return $original_label;
        if(count($complete_labels) > 1) {
            foreach($complete_labels as $k => $v) {
                $complete_labels[$k] = $v['code'] .": ". $v['label'];
                if($v['qualifier']) $complete_labels[$k] .= " - ".$v['qualifier'];
            }
            return "<a href=\"http://www.natureserve.org/explorer/ranking.htm#natsub\">" . $original_label . "</a> : " . 
                implode(", ", $complete_labels);
        }else {
            return "<a href=\"http://www.natureserve.org/explorer/ranking.htm#natsub\">" . $original_label . "</a> - " . $complete_labels[0]['label'];
        }
        return implode(", ", $complete_labels);
    }
    
    private function national_status_code_labels()
    {
        if(@$this->national_status_code_labels) return $this->national_status_code_labels;
        $this->national_status_code_labels = array();
        $this->national_status_code_labels['NX'] = "Presumed Extirpated";
        $this->national_status_code_labels['SX'] = $this->national_status_code_labels['NX'];
        $this->national_status_code_labels['NH'] = "Possibly Extirpated";
        $this->national_status_code_labels['SH'] = $this->national_status_code_labels['NH'];
        $this->national_status_code_labels['N1'] = "Critically Imperiled";
        $this->national_status_code_labels['S1'] = $this->national_status_code_labels['N1'];
        $this->national_status_code_labels['N2'] = "Imperiled";
        $this->national_status_code_labels['S2'] = $this->national_status_code_labels['N2'];
        $this->national_status_code_labels['N3'] = "Vulnerable";
        $this->national_status_code_labels['S3'] = $this->national_status_code_labels['N3'];
        $this->national_status_code_labels['N4'] = "Apparently Secure";
        $this->national_status_code_labels['S4'] = $this->national_status_code_labels['N4'];
        $this->national_status_code_labels['N5'] = "Secure";
        $this->national_status_code_labels['S5'] = $this->national_status_code_labels['N5'];
        $this->national_status_code_labels['NU'] = "Unrankable";
        $this->national_status_code_labels['SU'] = $this->national_status_code_labels['NU'];
        $this->national_status_code_labels['NNR'] = "Unranked";
        $this->national_status_code_labels['SNR'] = $this->national_status_code_labels['NNR'];
        $this->national_status_code_labels['NNA'] = "Not Applicable";
        $this->national_status_code_labels['SNA'] = $this->national_status_code_labels['NNA'];
        return $this->national_status_code_labels;
    }
    
    private function national_status_qualifiers()
    {
        if(@$this->national_status_qualifiers) return $this->national_status_qualifiers;
        $this->national_status_qualifiers = array();
        $this->national_status_qualifiers['?'] = "Inexact Numeric Rank";
        $this->national_status_qualifiers['B'] = "Breeding";
        $this->national_status_qualifiers['N'] = "Nonbreeding";
        $this->national_status_qualifiers['M'] = "Migrant";
        return $this->national_status_qualifiers;
    }
}
?>
