<?php
namespace php_active_record;

require_vendor('eol_content_schema_v2');

class NatureServeAPI
{
    // https://services.natureserve.org/idd/rest/ns/v1.1/globalSpecies/comprehensive?NSAccessKeyId=72ddf45a-c751-44c7-9bca-8db3b4513347&uid=ELEMENT_GLOBAL.2.104386
    const API_PREFIX = "https://services.natureserve.org/idd/rest/ns/v1.1/globalSpecies/comprehensive?NSAccessKeyId=72ddf45a-c751-44c7-9bca-8db3b4513347&uid=";
    const SPECIES_LIST_URL = "https://tranxfer.natureserve.org/download/longterm/EOL/gname_uid_crosswalk.xml";
    const IMAGE_API_PREFIX = "https://services.natureserve.org/idd/rest/ns/v1/globalSpecies/images?uid=";
    
    public function __construct()
    {
    }
    
    public function get_all_taxa()
    {
        if(!file_exists(DOC_ROOT . 'tmp/natureserve')) mkdir(DOC_ROOT . 'tmp/natureserve');
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => DOC_ROOT . "/temp/dwc_archive_test/"));
        
        $species_list_path = DOC_ROOT . "update_resources/connectors/files/natureserve_species_list.xml";
        // shell_exec("rm -f $species_list_path");
        // shell_exec("curl ". self::SPECIES_LIST_URL ." -o $species_list_path");
        
        $reader = new \XMLReader();
        $reader->open($species_list_path);
        $records = array();
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "DATA_RECORD")
            {
                $record = simplexml_load_string($reader->readOuterXML(), null, LIBXML_NOCDATA);
                $records[] = (string) $record->EGT_UID;
            }
        }
        echo "Total Records: ". count($records) ."\n";
        
        $chunk_size = 5;
        $this->request_timeout = 120; // seconds
        //shuffle($records);
        
        array_unshift($records, 'ELEMENT_GLOBAL.2.102211'); // Polar bear
        array_unshift($records, 'ELEMENT_GLOBAL.2.105926'); // American Bullfrog
        array_unshift($records, 'ELEMENT_GLOBAL.2.104777'); // White tailed deer
        array_unshift($records, 'ELEMENT_GLOBAL.2.100925'); // golden eagle
        $records = array_unique($records);
        $chunks = array_chunk($records, $chunk_size);
        $i = 0;
        $start_time = time_elapsed();
        foreach($chunks as $chunk)
        {
            $this->lookup_multiple_ids($chunk);
            if($i % 500 == 0) print_r($this->archive_builder->file_columns);
            
            $i += $chunk_size;
            $estimated_total_time = (((time_elapsed() - $start_time) / $i) * count($records));
            echo "Time spent ($i records) ". time_elapsed() ."\n";
            echo "Estimated total seconds : $estimated_total_time\n";
            echo "Estimated total hours : ". ($estimated_total_time / (60 * 60)) ."\n";
            echo "Memory : ". memory_get_usage() ."\n";
        }
        
        $this->archive_builder->finalize();
    }
    
    private function lookup_multiple_ids($ids)
    {
        $url = self::API_PREFIX . implode(",", $ids);
        $details_xml = $this->lookup_with_cache($url);
        $xml = simplexml_load_string($details_xml);
        foreach($xml->globalSpecies as $species_record)
        {
            $this->process_species_xml($species_record);
        }
    }
    
    private function lookup_with_cache($url, $image = false)
    {
        $hash = md5($url);
        if($image) $cache_path = DOC_ROOT . "tmp/natureserve/images/$hash.xml";
        else $cache_path = DOC_ROOT . "tmp/natureserve/$hash.xml";
        if(file_exists($cache_path))
        {
            $details_xml = file_get_contents($cache_path);
            if($image && $details_xml && preg_match("/<\/images>/", $details_xml)) return $details_xml;
            elseif(!$image && $details_xml && preg_match("/<\/globalSpeciesList>/", $details_xml)) return $details_xml;
            @unlink($cache_path);
        }
        
        $details_xml = Functions::get_remote_file($url, NULL, $this->request_timeout);
        $FILE = fopen($cache_path, 'w+');
        fwrite($FILE, $details_xml);
        fclose($FILE);
        return $details_xml;
    }
    
    private function get_images($id)
    {
        $url = self::IMAGE_API_PREFIX . $id;
        $details_xml = $this->lookup_with_cache($url, true);
        $xml = simplexml_load_string($details_xml);
        foreach($xml->image as $image)
        {
            // $this->process_species_xml($species_record);
        }
    }
    
    
    
    private function process_species_xml($details_xml)
    {
        $this->current_details_xml = $details_xml;
        $identifier = (string) $this->current_details_xml['uid'];
        $this->get_images($identifier);
        /*
        $this->write_taxonomy();
        // $this->write_references();
        $this->write_natureserve_status();
        $this->write_conservation_status_factors();
        $this->write_distribution();
        $this->write_ecology_and_life_history();
        $this->write_economic_attributes();
        $this->write_management_summary();
        $this->write_population_occurrence();
        $this->write_images();
        */
    }
    
    private function write_taxonomy()
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
        $t->vernacularName =(string) @$this->current_details_xml->classification->names->natureServePrimaryGlobalCommonName;
        $this->archive_builder->write_object_to_file($t);
    }
    
    private function write_natureserve_status()
    {
        if($statuc = @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus)
        {
            $status = $this->current_details_xml->conservationStatus->natureServeStatus->globalStatus;
            $blocks = array();
            $blocks[] = @self::description_from_block($status->rank->code, 'Global Status');
            $blocks[] = @self::description_from_block($status->statusLastReviewed, 'Global Status Last Reviewed');
            $blocks[] = @self::description_from_block($status->statusLastChanged, 'Global Status Last Changed');
            if($block = @self::description_from_block($status->roundedRank->code, 'Rounded Global Status'))
            {
                if(@trim((string) $status->roundedRank->description)) $block .= " - ". $status->roundedRank->description;
                $blocks[] = $block;
            }
            $blocks[] = @self::description_from_block($status->reasons, 'Reasons');
            
            if(isset($status->nationalStatuses->nationalStatus))
            {
                foreach($status->nationalStatuses->nationalStatus as $status)
                {
                    if(@trim((string) $status->rank->code))
                    {
                        $block = "<b>National Status:</b> ". $status['nationName'] ." - ". $status->rank->code;
                        if(@trim((string) $status->statusLastReviewed))
                        {
                            $block .= " (". $status->statusLastReviewed .")";
                        }
                        $blocks[] = $block;
                    }
                }
            }
            
            // array_filter removes any null or empty string values
            if($blocks = array_filter($blocks)) 
            {
                $this->write_text_description("NatureServe Conservation Status",
                    "natureserve_status",
                    "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Conservation",
                    implode("<br/><br/>", $blocks));
            }
        }
    }
    
    
    private function write_conservation_status_factors()
    {
        if($conservation = @$this->current_details_xml->conservationStatus->natureServeStatus->globalStatus->conservationStatusFactors)
        {
            $blocks = array();
            $blocks[] = @self::description_from_block($conservation->globalAbundance->description, 'Global Abundance');
            $blocks[] = @self::description_from_block($conservation->globalAbundance->comments, 'Global Abundance Comments');
            $blocks[] = @self::description_from_block($conservation->estimatedNumberOfOccurrences->description, 'Estimated Number of Element Occurrences');
            $blocks[] = @self::description_from_block($conservation->estimatedNumberOfOccurrences->comments, 'Estimated Number of Element Occurrences Comments');
            $blocks[] = @self::description_from_block($conservation->globalShortTermTrend->description, 'Global Short Term Trend');
            $blocks[] = @self::description_from_block($conservation->globalShortTermTrend->comments, 'Global Short Term Trend Comments');
            $blocks[] = @self::description_from_block($conservation->globalLongTermTrend->description, 'Global Long Term Trend');
            $blocks[] = @self::description_from_block($conservation->globalLongTermTrend->comments, 'Global Long Term Trend Comments');
            $blocks[] = @self::description_from_block($conservation->globalProtection->description, 'Global Protection');
            $blocks[] = @self::description_from_block($conservation->globalProtection->comments, 'Global Protection Comments');
            $blocks[] = @self::description_from_block($conservation->globalProtection->needs, 'Global Protection Needs');
            $blocks[] = @self::description_from_block($conservation->threat->degreeOfThreat->description, 'Degree of Threat');
            $blocks[] = @self::description_from_block($conservation->threat->scope, 'Threat Scope');
            $blocks[] = @self::description_from_block($conservation->threat->severity, 'Threat Severity');
            $blocks[] = @self::description_from_block($conservation->threat->immediacy, 'Threat Immediacy');
            $blocks[] = @self::description_from_block($conservation->threat->comments, 'Threats');
            $blocks[] = @self::description_from_block($conservation->intrinsicVulnerabity->comments, 'Intrinsic Vulnerability');
            $blocks[] = @self::description_from_block($conservation->environmentalSpecificity->description, 'Environmental Specificy');
            $blocks[] = @self::description_from_block($conservation->otherConsiderations, 'Other Considerations');
            
            // array_filter removes any null or empty string values
            if($blocks = array_filter($blocks)) 
            {
                $this->write_text_description("NatureServe Conservation Status Factors",
                    "conservation_status_factors",
                    "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Conservation",
                    implode("<br/><br/>", $blocks));
            }
        }
    }
    
    private function write_distribution()
    {
        if($distribution = @$this->current_details_xml->distribution)
        {
            $blocks = array();
            $blocks[] = @self::description_from_block($distribution->globalRange->description, 'Global Range');
            $blocks[] = @self::description_from_block($distribution->globalRange->comments, 'Global Range Comments');
            $blocks[] = @self::description_from_block($distribution->endemism->description, 'Endemism');
            
            // array_filter removes any null or empty string values
            if($blocks = array_filter($blocks)) 
            {
                $this->write_text_description("Distribution",
                    "distribution",
                    "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution",
                    implode("<br/><br/>", $blocks));
            }
            
            if($conservation_map_url = @trim((string) $distribution->conservationStatusMap))
            {
                $this->write_image_description("U.S. States and Canadian Provinces",
                    "conservation_map",
                    'map',
                    $conservation_map_url);
            }
            
            if($range_map = @$distribution->rangeMap)
            {
                $this->write_image_description("Range Map",
                    "range_map",
                    'map',
                    (string) @$range_map->rangeMapURI,
                    array('contributor' => @trim((string) $range_map->rangeMapCompilers)));
            }
        }
    }
    
    private function write_ecology_and_life_history()
    {
        if($ecology = @$this->current_details_xml->ecologyAndLifeHistory)
        {
            $blocks = array();
            $blocks[] = @self::description_from_block($ecology->ecologyAndLifeHistoryDescription->shortGeneralDescription, 'Basic Description');
            $blocks[] = @self::description_from_block($ecology->ecologyAndLifeHistoryDescription->generalDescription, 'General Description');
            $blocks[] = @self::description_from_block($ecology->diagnosticCharacteristics, 'Diagnostic Characteristics');
            $blocks[] = @self::description_from_block($ecology->reproductionComments, 'Reproduction Comments');
            $blocks[] = @self::description_from_block($ecology->knownPests, 'Known Pests');
            $blocks[] = @self::description_from_block($ecology->ecologyComments, 'Ecology Comments');
            $blocks[] = @self::description_from_block($ecology->habitatType->description, 'Habitat Type');
            $blocks[] = @self::description_from_block($ecology->migration->nonMigrant, 'Non-Migrant');
            $blocks[] = @self::description_from_block($ecology->migration->locallyMigrant, 'Locally Migrant');
            $blocks[] = @self::description_from_block($ecology->migration->longDistanceMigrant, 'Long Distance Migrant');
            $blocks[] = @self::description_from_block($ecology->migration->mobilityAndMigrationComments, 'Mobility and Migration Comments');
            $blocks[] = @self::description_from_block($ecology->habitats->habitatComments, 'Habitat Comments');
            $blocks[] = @self::description_from_block($ecology->foodHabits->foodComments, 'Food Comments');
            $blocks[] = @self::description_from_block($ecology->phenologies->phenologyComments, 'Phenology Comments');
            $blocks[] = @self::description_from_block($ecology->length, 'Length');
            $blocks[] = @self::description_from_block($ecology->weight, 'Weight');
            
            // array_filter removes any null or empty string values
            if($blocks = array_filter($blocks))
            {
                $this->write_text_description("Ecology and Life History",
                    "ecology_and_life_history",
                    "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Ecology",
                    implode("<br/><br/>", $blocks),
                    array(  'creator' => @trim((string) $ecology->ecologyAndLifeHistoryAuthors['displayValue']),
                            'created' => @trim((string) $ecology->ecologyAndLifeHistoryEditionDate)));
            }
        }
    }
    
    private function write_economic_attributes()
    {
        if($economy = @$this->current_details_xml_xml->economicAttributes)
        {
            $blocks = array();
            $blocks[] = @self::description_from_block($economy->economicComments, 'Economic Comments');
            
            // array_filter removes any null or empty string values
            if($blocks = array_filter($blocks))
            {
                $this->write_text_description("Economic Attributes",
                    "economic_attributes",
                    "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses",
                    implode("<br/><br/>", $blocks));
            }
        }
    }
    
    private function write_management_summary()
    {
        if($management = @$this->current_details_xml->managementSummary)
        {
            $blocks = array();
            $blocks[] = @self::description_from_block($management->stewardshipOverview, 'Stewardship Overview');
            $blocks[] = @self::description_from_block($management->speciesImpact, 'Species Impact');
            $blocks[] = @self::description_from_block($management->restorationPotential, 'Restoration Potential');
            $blocks[] = @self::description_from_block($management->preserveSelectionAndDesignConsiderations, 'Preserve Selection and Design Considerations');
            $blocks[] = @self::description_from_block($management->managementRequirements, 'Management Requirements');
            $blocks[] = @self::description_from_block($management->monitoringRequirements, 'Monitoring Requirements');
            $blocks[] = @self::description_from_block($management->managementPrograms, 'Management Programs');
            $blocks[] = @self::description_from_block($management->monitoringPrograms, 'Monitoring Programs');
            $blocks[] = @self::description_from_block($management->managementResearchPrograms, 'Management Research Programs');
            $blocks[] = @self::description_from_block($management->managementResearchNeeds, 'Management Research Needs');
            $blocks[] = @self::description_from_block($management->biologicalResearchNeeds, 'Biological Research Needs');
            $blocks[] = @self::description_from_block($management->additionalTopics, 'Additional Topics');
            
            // array_filter removes any null or empty string values
            if($blocks = array_filter($blocks))
            {
                $this->write_text_description("Management Summary",
                    "management_summary",
                    "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Management",
                    implode("<br/><br/>", $blocks),
                    array(  'creator' => @trim((string) $management->managementSummaryAuthors['displayValue']),
                            'created' => @trim((string) $management->editionDate)));
            }
        }
    }
    
    private function write_population_occurrence()
    {
        if($population = @$this->current_details_xml->populationOccurrence)
        {
            $blocks = array();
            foreach($population->delineations->delineation as $delineation)
            {
                if(@$delineation['groupName']) $blocks[] = "<b>Group Class:</b> ". $delineation['groupName'];
                if(@$delineation['migratoryUseType']) $blocks[] = "<b>Use Class:</b> ". $delineation['migratoryUseType'];
                if(isset($delineation->subTypes))
                {
                    $subtypes = array();
                    foreach($delineation->subTypes->subType as $subtype) $subtypes[] = (string) $subtype;
                    if($subtypes)
                    {
                        $blocks[] = "<b>Subtype(s):</b> " . implode(", ", $subtypes);
                    }
                }
                $blocks[] = @self::description_from_block($delineation->minimumCriteriaForOccurrence, 'Minimum Criteria for an Occurrence');
                $blocks[] = @self::description_from_block($delineation->mappingGuidance, 'Mapping Guidance');
                if(isset($delineation->separation))
                {
                    $blocks[] = @self::description_from_block($delineation->separation->barriers, 'Separation Barriers');
                    $blocks[] = @self::description_from_block($delineation->separation->distanceForUnsuitableHabitat, 'Separation Distance for Unsuitable Habitat');
                    $blocks[] = @self::description_from_block($delineation->separation->distanceForSuitableHabitat, 'Separation Distance for Suitable Habitat');
                    $blocks[] = @self::description_from_block($delineation->separation->alternateSeparationProcedure, 'Alternate Separation Procedure');
                    $blocks[] = @self::description_from_block($delineation->separation->separationJustification, 'Separation Justification');
                }
                
                // array_filter removes any null or empty string values
                if($blocks = array_filter($blocks))
                {
                    $this->write_text_description("Population Delineation",
                        "population_delineation",
                        "http://rs.tdwg.org/ontology/voc/SPMInfoItems#PopulationBiology",
                        implode("<br/><br/>", $blocks),
                        array(  'creator' => @trim((string) $delineation->delineationAuthors['displayValue']),
                                'created' => @trim((string) $delineation->versionDate)));
                }
                break;
            }
        }
    }
    
    private function write_images()
    {
        if($images = @$this->current_details_xml->speciesImages)
        {
            foreach($images->speciesImage as $speciesImage)
            {
                $mr = new \eol_schema\MediaResource();
                $mr->taxonID = (string) $this->current_details_xml['uid'];
                $mr->mediaResourceID = $mr->taxonID . "_image_" . $speciesImage->imageMetadataId;
                $mr->type = 'http://purl.org/dc/dcmitype/StillImage';
                $mr->language = 'en';
                $mr->mimeType = 'image/jpeg';
                $mr->additionalInformationURL = (string) @$this->current_details_xml->natureServeExplorerURI;
                foreach($speciesImage->imageVersions->imageURL as $imageURL)
                {
                    if($imageURL['imageType'] == 'Detail') $mr->fileURL = (string) $imageURL;
                }
                $mr->creator = @$speciesImage->imageCreatorCredit;
                $mr->rights = @$speciesImage->imageRights;
                $mr->rightsHolder = @$speciesImage->imageCopyright;
                $mr->license = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }
    
    private function write_references()
    {
        $references = array();
        if($r = (string) @$this->current_details_xml->classification->names->scientificName->conceptReference->formattedFullCitation)
        {
            $references[trim($r)] = 1;
        }
        if(isset($this->current_details_xml->references))
        {
            foreach($this->current_details_xml->references->citation as $reference)
            {
                $references[trim((string) $reference)] = 1;
            }
        }
        // this is some kind of placeholder reference and likely is not to be displayed
        unset($references["NatureServe. Unpublished. Concept reference for taxa where a reference cannot be recorded due to insufficient BCD data for conversion; to be used as a placeholder until the correct citation is identified."]);
        unset($references["NatureServe. Unpublished. Concept reference for taxa for which no reference which describes the circumscription has been recorded; to be used as a placeholder until such a citation is identified."]);
        unset($references["NatureServe. Unpublished. Concept reference for taxa which have not yet been described; to be used as a placeholder until a citation is available which describes the circumscription of the taxon."]);
    }
    
    private function write_text_description($title, $id_suffix, $subject, $description, $options = array())
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID = (string) $this->current_details_xml['uid'];
        $mr->mediaResourceID = $mr->taxonID . "_" . $id_suffix;
        $mr->type = 'http://purl.org/dc/dcmitype/Text';
        $mr->language = 'en';
        $mr->mimeType = 'text/html';
        $mr->additionalInformationURL = (string) @$this->current_details_xml->natureServeExplorerURI;
        $mr->description = $description;
        $mr->subject = $subject;
        $mr->title = $title;
        $mr->creator = @$options['creator'];
        $mr->created = @$options['created'];
        $mr->license = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        $this->archive_builder->write_object_to_file($mr);
    }
    
    private function write_image_description($title, $id_suffix, $subtype, $fileURL, $options = array())
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID = (string) $this->current_details_xml['uid'];
        $mr->mediaResourceID = $mr->taxonID . "_" . $id_suffix;
        $mr->type = 'http://purl.org/dc/dcmitype/StillImage';
        $mr->subtype = $subtype;
        $mr->language = 'en';
        $mr->mimeType = 'image/gif';
        $mr->additionalInformationURL = (string) @$this->current_details_xml->natureServeExplorerURI;
        $mr->title = "U.S. States and Canadian Provinces";
        $mr->fileURL = $fileURL;
        $mr->creator = @$options['creator'];
        $mr->created = @$options['created'];
        $mr->license = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        $this->archive_builder->write_object_to_file($mr);
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
}

?>
