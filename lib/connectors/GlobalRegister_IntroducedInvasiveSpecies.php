<?php
namespace php_active_record;
/* connector: global_register_IIS.php

wget -q http://api.gbif.org/v1/occurrence/download/request/0010139-190918142434337.zip -O /extra/other_files/GBIF_DwCA/Germany_0010139-190918142434337.zip

http://ipt.ala.org.au/
http://ipt.ala.org.au/rss.do
*/
class GlobalRegister_IntroducedInvasiveSpecies
{
    function __construct($resource_id, $makeDwCA = true)
    {
        $this->resource_id = $resource_id;
        if($makeDwCA) {
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        /* Advised to re-harvest quarterly: https://eol-jira.bibalex.org/browse/DATA-1838?focusedCommentId=64734&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64734 */
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 
        'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
        
        $this->service['list of ISSG datasets'] = 'https://www.gbif.org/api/dataset/search?facet=type&facet=publishing_org&facet=hosting_org&facet=publishing_country&facet=project_id&facet=license&locale=en&offset=OFFSET_NO&publishing_org=cdef28b1-db4e-4c58-aa71-3c5238c2d0b5&type=CHECKLIST';
        $this->service['dataset'] = 'https://api.gbif.org/v1/dataset/DATASET_KEY/document';
        $this->south_africa = '3cabcf37-db13-4dc1-9bf3-e6f3fbfbbe23';
        
        if(Functions::is_production()) {
            // $this->download_options['cache_path'] = "/extra/eol_cache_gbif/";
            $this->dwca_folder = '/extra/other_files/GBIF_DwCA/ISSG/';
        }
        else {
            // $this->download_options['resource_id'] = "gbif";
            // $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache/";
            $this->dwca_folder = CONTENT_RESOURCE_LOCAL_PATH.'ISSG/';
        }
        $this->comparison_file = CONTENT_RESOURCE_LOCAL_PATH.'dataset_comparison.txt';
        $this->exclude['taxon'] = array('specificEpithet', 'infraspecificEpithet', 'acceptedNameUsage', 'language', 'license', 'rightsHolder', 
                                        'bibliographicCitation', 'datasetID', 'datasetName', 'references');
        $this->exclude['speciesprofile'] = array('isMarine', 'isFreshwater', 'isTerrestrial');
        $this->debug = array();
        $this->synonym_statuses = array('proparte_synonym', 'synonym', 'synoym', 'homotypic_synonym', 'heterotypic_synonym', 'homotypic synonym', 'species proparte synonym');
        $this->cumulative_taxon_fields = array('http://rs.tdwg.org/dwc/terms/taxonID', 'http://rs.tdwg.org/dwc/terms/acceptedNameUsageID', 'http://rs.tdwg.org/dwc/terms/scientificName', 'http://rs.tdwg.org/dwc/terms/acceptedNameUsage', 
'http://rs.tdwg.org/dwc/terms/kingdom', 'http://rs.tdwg.org/dwc/terms/phylum', 'http://rs.tdwg.org/dwc/terms/class', 'http://rs.tdwg.org/dwc/terms/order', 
'http://rs.tdwg.org/dwc/terms/family', 'http://rs.tdwg.org/dwc/terms/genus', 'http://rs.tdwg.org/dwc/terms/specificEpithet', 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet', 
'http://rs.tdwg.org/dwc/terms/taxonRank', 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship', 'http://rs.tdwg.org/dwc/terms/taxonomicStatus', 'http://rs.tdwg.org/dwc/terms/taxonRemarks', 
'http://purl.org/dc/terms/language', 'http://purl.org/dc/terms/license', 'http://purl.org/dc/terms/rightsHolder', 'http://purl.org/dc/terms/bibliographicCitation', 
'http://rs.tdwg.org/dwc/terms/datasetID', 'http://rs.tdwg.org/dwc/terms/datasetName', 'http://purl.org/dc/terms/references'); //for synonym report
        $this->synonym_report_for_katja = CONTENT_RESOURCE_LOCAL_PATH.'GRIIS_synonym_report.txt';
        $this->dataset_page = 'https://www.gbif.org/dataset/';
    }
    private function synonym_report_header()
    {
        foreach($this->cumulative_taxon_fields as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            $fields[] = $field;
        }
        $fn = Functions::file_open($this->synonym_report_for_katja, "w");
        fwrite($fn, implode("\t", $fields)."\n");
        fclose($fn);
    }
    function start($report_only_YN = false)
    {
        self::synonym_report_header();
        
        require_library('connectors/TraitGeneric'); $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        self::initialize_mapping();
        
        $this->report_only_YN = $report_only_YN;
        $dataset_keys = self::get_all_dataset_keys(); //123 datasets as of Oct 11, 2019
        echo "\ndataset_keys total: ".count($dataset_keys)."\n";
        $i = 0;
        foreach($dataset_keys as $dataset_key) { $i++;                          //1st loop is to just generate the $this->info[$dataset_key]
            $this->info[$dataset_key] = self::get_dataset_info($dataset_key);
            /* debug only
            if($dataset_key == '3cabcf37-db13-4dc1-9bf3-e6f3fbfbbe23') {
                print_r($this->info[$dataset_key]); exit;
            }
            */
            // print_r($this->info); exit;
            // if($i >= 10) break; //debug only
        }
        $i = 0;
        foreach($dataset_keys as $dataset_key) { $i++;                          //2nd loop
            $this->current_dataset_key = $dataset_key;
            self::process_dataset($dataset_key);
            // if($i >= 10) break; //debug only
        }
        if($this->debug) print_r($this->debug);
        $this->archive_builder->finalize(TRUE);
    }
    private function process_dataset($dataset_key)
    {   /*Array(
            [6d9e952f-948c-4483-9807-575348147c7e] => Array(
                    [orig] => https://ipt.inbo.be/resource?r=unified-checklist
                    [download_url] => https://ipt.inbo.be/archive.do?r=unified-checklist
                )
        )*/
        $dwca = $this->info[$dataset_key]['download_url'];
        echo "\ndownload_extract_dwca: [$dwca]...\n";
        $info = self::download_extract_dwca($dwca, $dataset_key);
        if(!$info) {
            exit("\nCannot download_extract dwca: [$dwca] [$dataset_key]\n");
        }
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        if($tables = @$info['harvester']->tables) {}
        else {
            print_r($info);
            exit("\nCannot access tables for this dataset_key: [$dataset_key]\n");
        }
        
        
        if($this->report_only_YN == 'utility_report') { //utility report only - for Jen
            if($val = @$tables['http://rs.gbif.org/terms/1.0/distribution'][0]) {
                self::process_distribution($val);
            }
        }
        elseif($this->report_only_YN == 'synonym_report') { //Utility only. To compile all taxon fields for synonym report for Katja. Used once only.
            $meta = $tables['http://rs.tdwg.org/dwc/terms/taxon'][0];
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $this->taxon_fields[$field['term']] = '';
            }
            print_r($this->taxon_fields); /* This was then just manually copied to be the cumulative fields for taxon from all 123 datasets. Used in $this->cumulative_taxon_fields */
        }
        else { //main operation - generating DwCA
            self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
            if($val = @$tables['http://rs.gbif.org/terms/1.0/speciesprofile'][0]) self::process_speciesprofile($val);
            if($val = @$tables['http://rs.gbif.org/terms/1.0/distribution'][0]) self::process_distribution($val);
        }
        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        debug("\n temporary directory removed: $temp_dir\n");
        // */
    }
    private function format_taxonID($rec)
    {
        $orig_taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID']; //posterity
        $sciname = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
        if($author = @$rec['http://rs.tdwg.org/dwc/terms/scientificNameAuthorship']) $sciname = trim(str_replace($author, '', $sciname));
        $sciname = Functions::canonical_form($sciname);
        // $id = trim(str_replace(" ", "_", strtolower($sciname)));
        $id = md5($sciname);
        $this->info_map_taxonID[$orig_taxonID] = $id;
        return $id;
    }
    private function process_taxon($meta)
    {   //print_r($meta);
        echo "\nprocess_taxon...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => https://www.gbif.org/species/1010644
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => https://www.gbif.org/species/1010644
                [http://rs.tdwg.org/dwc/terms/scientificName] => Hanseniella caldaria (Hansen, 1903)
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsage] => Hanseniella caldaria (Hansen, 1903)
                [http://rs.tdwg.org/dwc/terms/kingdom] => Animalia
                [http://rs.tdwg.org/dwc/terms/phylum] => Arthropoda
                [http://rs.tdwg.org/dwc/terms/class] => Symphyla
                [http://rs.tdwg.org/dwc/terms/order] => 
                [http://rs.tdwg.org/dwc/terms/family] => Scutigerellidae
                [http://rs.tdwg.org/dwc/terms/genus] => Hanseniella
                [http://rs.tdwg.org/dwc/terms/specificEpithet] => caldaria
                [http://rs.tdwg.org/dwc/terms/infraspecificEpithet] => 
                [http://rs.tdwg.org/dwc/terms/taxonRank] => SPECIES
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => (Hansen, 1903)
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => ACCEPTED
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => Sources considered for this taxon: https://doi.org/10.15468/3pmlxs
                [http://purl.org/dc/terms/language] => en
                [http://purl.org/dc/terms/license] => http://creativecommons.org/licenses/by/4.0/legalcode
                [http://purl.org/dc/terms/rightsHolder] => 
                [http://purl.org/dc/terms/bibliographicCitation] => https://www.gbif.org/species/1010644: Hanseniella caldaria (Hansen, 1903) in GBIF Secretariat (2017). GBIF Backbone Taxonomy. Checklist dataset https://doi.org/10.15468/39omei
                [http://rs.tdwg.org/dwc/terms/datasetID] => https://doi.org/10.15468/xoidmd
                [http://rs.tdwg.org/dwc/terms/datasetName] => Global Register of Introduced and Invasive Species - Belgium
                [http://purl.org/dc/terms/references] => https://www.gbif.org/species/1010644
            )*/
            //===========================================================================================================================================================
            /* identifiers. Taxon IDs may be of the form "https://www.gbif.org/species/1031677", or just the numeric part thereof. 
            IDs in each resource will need something appended to them (resource name or country name?) to make them unique among all the resources. 
            You can assume there's only 1 occurrence per taxon, and construct an occurrence file with a 1:1 relationship of taxa to occurrences. 
            The same IDs are used in the files we'll rely on for measurementOrFact, so you'll need to map from the taxon IDs to those as occurrence IDs in the measurementOrFact file, 
            if you follow me. */
            $rec['http://rs.tdwg.org/dwc/terms/taxonID'] = self::format_taxonID($rec); // obsolete scheme was self::format_gbif_id($rec['http://rs.tdwg.org/dwc/terms/taxonID']);
            if(isset($rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID'])) {
                $rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID'] = self::format_gbif_id($rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']);
                
                /* from Eli: if taxonID == acceptedNameUsageID, then latter should be blank */
                if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == $rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']) $rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID'] = '';
            }
            $rec['http://rs.tdwg.org/dwc/terms/taxonRank'] = strtolower($rec['http://rs.tdwg.org/dwc/terms/taxonRank']);
            $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'] = strtolower($rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus']);
            //===========================================================================================================================================================
            /* manual massaging since like Great Britain (1288ee7d-d67c-4e23-8d95-409973067383) has swapped values for taxonRank and taxonomicStatus
            Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 1288ee7d-d67c-4e23-8d95-409973067383_22146
                [http://rs.tdwg.org/dwc/terms/scientificName] => Abies alba Mill.
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsage] => 
                [http://rs.tdwg.org/dwc/terms/kingdom] => Plantae
                [http://rs.tdwg.org/dwc/terms/phylum] => Tracheophyta
                [http://rs.tdwg.org/dwc/terms/class] => Pinopsida
                [http://rs.tdwg.org/dwc/terms/order] => Pinales
                [http://rs.tdwg.org/dwc/terms/family] => Pinaceae
                [http://rs.tdwg.org/dwc/terms/taxonRank] => accepted
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => species
            )*/
            $datasets_need_swapping = array('1288ee7d-d67c-4e23-8d95-409973067383', 'f6ae66a3-f267-4d03-8541-fdfa7ffc9eaf', '6b45e498-23e8-4e39-8620-77011495e42c', 'd006d8bb-cf1e-46ff-a054-c6768e23d86d', '137a287c-911c-40b5-8051-6dc86b110bcc', '2f7ea7d1-a73f-46f6-b790-7339126a999f', '7b6661a3-5bbd-4dbb-b3e4-0c26df341977', 'e1459ba8-561c-4be1-9ede-c31d16c3ef87', '5fd0d7a4-0381-4d6b-ad34-24e99a7b4247', '4a5c1429-3f25-4b2e-8ab6-d281c2c3df49', '69233277-0946-4931-8115-3b247a81a051', '51f5af06-7176-4ec1-b86e-776d11bc49c8', '543c8dbe-d386-4f87-8125-3a0ebd7784a4', '9ea091a2-6b54-47e1-80ac-36b921865b1f', '2001dd74-2069-43cf-95e4-1f9b39238a1b', '09d0256f-a986-4fee-9252-819ff12069e1', '5149ebfa-3873-4b11-8acb-3940303c793f', 'a368d019-028c-4f87-adcf-90771fd666f9', '2e76af52-48a9-4b89-81b8-441860dbed9e', '11891f2f-3cd7-4d13-a340-8041295af072', 'ced9186d-2778-4a32-b245-7506893061bc', 'f83db6d8-9849-4554-9d78-375bce27660f', 'f998e11b-5074-464e-84ba-a8bdc9556472', '8a73c1ea-82bb-4ada-bb69-d17b73e4719a', '0103d6f8-1f34-4f5d-a456-93dbcfe9f615', 'f77acd30-c3d5-429e-8592-d277e3f4cef3', '05539304-10a1-4ce8-a01f-693fbacf6ceb', 'bba03061-2924-4716-a3e8-8b8c268cfb89', '97ee2123-27de-4a95-9e6f-ea1f57c7c115', 'dd216b75-0282-4ee3-b99a-cfd00b1c8b3f', '9df5cb8b-c433-47b3-b077-d6f09c0c7aaa', 'cbd5726e-b5b7-4a1a-af70-0bc4a842aa2b', 'b2e5f15d-44e2-480d-b68c-c6d0627288f2', '46d612a6-90b3-4f50-8b9a-a290d1780b76', '016c16c3-d907-4c88-97dd-97ad62c8130e');
            if(in_array($this->current_dataset_key, $datasets_need_swapping)) {
                $temp = $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'];
                $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'] = $rec['http://rs.tdwg.org/dwc/terms/taxonRank'];
                $rec['http://rs.tdwg.org/dwc/terms/taxonRank'] = $temp;
            }
            //===========================================================================================================================================================
            $this->debug['taxonomicStatus'][$rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus']] = ''; //for stats only
            // /* debug only
            if(in_array($rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'], array('species', 'subspecies', 'genus', 'variety', 'form'))) {
                // print_r($rec); //exit("\ndataset: ".$this->current_dataset_key."\n");
                $this->debug['datasets with species as status'][$this->current_dataset_key] = '';
            }
            // */
            /*Array(
                [accepted] => 
                [doubtful] => 
                [none] => 
                [] => 
                
                [proparte_synonym] => 
                [synonym] => 
                [homotypic_synonym] => 
                [heterotypic_synonym] => 
                [homotypic synonym] => 
                [synoym] => 
                [species proparte synonym] => 
            )*/
            // continue; //debug only
            //===========================================================================================================================================================
            /*
            acceptedName columns: there's also some funny business in the taxa file that seems widespread in these files: the typical files have a column called acceptedNameUsage, 
            which, for records with taxonomicStatus=SYNONYM (or similar), contains a namestring, which does not appear elsewhere in the file. 
            Katja would like to look into mapping these later, but for now, please remove all the synonym records, and put them into a separate report which we'll deal with later. 
            And please remove any corresponding MoF records for these taxa. Not all resources have this problem- Belgium, for instance, has an acceptedNameUsageID column and behaves normally. 
            Only those resources with records of synonyms, but no acceptedNameUsageID column need this treatment. We don't need to keep acceptedNameUsage in the global resource file at all.
            */
            if(in_array($rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'], $this->synonym_statuses)) { //a synonym
                self::write_synonyms_report_for_katja($rec);
                if(!isset($rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID'])) {
                    $this->synonym_taxa_excluded[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = '';
                    continue;
                }
                else { //there is acceptedNameUsageID
                    if(!$rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']) { // but has no value, blank
                        $this->synonym_taxa_excluded[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = '';
                        continue;
                    }
                }
                // /* new 2019-10-31, contradicts above: remove all synonyms per: https://eol-jira.bibalex.org/browse/DATA-1838?focusedCommentId=64089&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64089
                $this->synonym_taxa_excluded[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = '';
                continue;
                // */
            }
            //===========================================================================================================================================================
            /* taxonomicStatus: there may be a few other values represented in this column. For instance, for records with taxonomicStatus=DOUBTFUL, 
            please remove the string, leaving that cell blank, and place DOUBTFUL instead in a new, taxonRemarks column. */
            $status_2move_to_taxonRemarks = array('doubtful');
            if(in_array($rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'], $status_2move_to_taxonRemarks)) {
                $rec['http://rs.tdwg.org/dwc/terms/taxonRemarks'] = $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'];
                $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'] = '';
            }
            if($rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'] == 'none') $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'] = '';
            //===========================================================================================================================================================
            if(!$rec['http://rs.tdwg.org/dwc/terms/scientificName']) continue;
            if(in_array($rec['http://rs.tdwg.org/dwc/terms/taxonRank'], array('synonym'))) $rec['http://rs.tdwg.org/dwc/terms/taxonRank'] = '';
            //===========================================================================================================================================================
            $o = new \eol_schema\Taxon();
            if(isset($rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID'])) unset($rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']); //new 2019-10-31
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                if(in_array($field, $this->exclude['taxon'])) continue;
                $o->$field = $rec[$uri];
            }
            // print_r($o); exit;

            if(!isset($this->taxon_ids[$o->taxonID])) {
                $this->taxon_ids[$o->taxonID] = '';
                $this->archive_builder->write_object_to_file($o);
            }
            // if($i >= 20) break; //debug only
        }
    }
    private function write_synonyms_report_for_katja($rec)
    {
        $fn = Functions::file_open($this->synonym_report_for_katja, "a");
        foreach($this->cumulative_taxon_fields as $uri) $val[] = @$rec[$uri];
        fwrite($fn, implode("\t", $val)."\n");
        fclose($fn);
    }
    private function process_speciesprofile($meta)
    {   //print_r($meta);
        echo "\nprocess_speciesprofile...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            } // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => https://www.gbif.org/species/1010644
                [http://rs.gbif.org/terms/1.0/isMarine] => FALSE
                [http://rs.gbif.org/terms/1.0/isFreshwater] => FALSE
                [http://rs.gbif.org/terms/1.0/isTerrestrial] => TRUE
                [http://rs.gbif.org/terms/1.0/isInvasive] => 
                [http://rs.tdwg.org/dwc/terms/habitat] => terrestrial
                [http://purl.org/dc/terms/source] => https://www.gbif.org/species/148437977: Hanseniella caldaria (Hansen, 1903) in Reyserhove L, Groom Q, Adriaens T, Desmet P, Dekoninck W, Van Keer K, Lock K (2018). Ad hoc checklist of alien species in Belgium. Version 1.2. Research Institute for Nature and Forest (INBO). Checklist dataset https://doi.org/10.15468/3pmlxs
            )*/
            
            // $rec['http://rs.tdwg.org/dwc/terms/taxonID'] = self::format_gbif_id($rec['http://rs.tdwg.org/dwc/terms/taxonID']); obsolete
            if($val = @$this->info_map_taxonID[$rec['http://rs.tdwg.org/dwc/terms/taxonID']]) $rec['http://rs.tdwg.org/dwc/terms/taxonID'] = $val;
            else {
                print_r($rec); exit("\nthis taxonID from SpeciesProfile doesn't exist in taxon.txt\n");
                continue;
            }
            
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID']; //just to shorten the var.
            if(isset($this->synonym_taxa_excluded[$taxonID])) continue; //remove all MoF for synonym taxa
            //===========================================================================================================================================================
            /*
            start here...
            For speciesprofile: the usual columns are isInvasive, habitat, source. We'll get one record for habitat, and use isInvasive, sometimes, 
            to modify a record from the distribution file.

            Habitat measurementType: http://eol.org/schema/terms/Habitat. 
                    measurementValue mapping: 
                        Terrestrial-> http://purl.obolibrary.org/obo/ENVO_00000446, 
                        Marine-> http://purl.obolibrary.org/obo/ENVO_00000447, 
                        Freshwater-> http://purl.obolibrary.org/obo/ENVO_00000873.
            
            isInvasive: disregard unless the value is "true" or "yes". If it is, find the record for this occurrence from the distribution file, 
            and change its measurementType to http://eol.org/schema/terms/InvasiveRange.
            */
            
            if($habitat = @$rec['http://rs.tdwg.org/dwc/terms/habitat']) {
                
                // /* manual adjustments
                $habitat = str_ireplace('TerrestrialIFreshwater', 'Terrestrial|Freshwater', $habitat);
                if(strtolower($habitat) == 'TerrestrialIFreshwater') $habitat = "terrestrial|freshwater";
                $habitat = str_replace(array(",","/"), "|", $habitat);
                // */
                
                $habitats = explode("|", $habitat);
                // if(count($habitats) > 1) print_r($rec); //debug only
                $habitats = array_map('trim', $habitats);
                foreach($habitats as $habitat) {
                    if(!$habitat) continue;
                    $mValue = self::get_uri($habitat,'habitat');
                    // $mType = 'http://eol.org/schema/terms/Habitat'; //obsolete
                    $mType = 'http://purl.obolibrary.org/obo/RO_0002303'; //DATA-1841
                    if(!$mValue) continue;
                    
                    // /* manual adjustment
                    if(is_array($mValue)) { //$habitat = 'host'
                        $tmp_arr = $mValue;
                        if($tmp_arr[0] == 'http://eol.org/schema/terms/EcomorphologicalGuild') {
                            $mValue = $tmp_arr[1];
                            $mType = $tmp_arr[0];
                        }
                    }
                    // */
                    
                    $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                    $save = array();
                    $save['taxon_id'] = $taxon_id;
                    $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                    $save['measurementRemarks'] = $habitat;
                    /* by Eli
                    $save['source'] = self::get_source_from_taxonID_or_source($rec);
                    */
                    $save['bibliographicCitation'] = @$rec['http://purl.org/dc/terms/source'];
                    $save['source'] = $this->dataset_page.$this->current_dataset_key;
                    if($mValue && $mType) $this->func->add_string_types($save, $mValue, $mType, "true");
                }
            }
            //===========================================================================================================================================================
            if(in_array(strtolower($rec['http://rs.gbif.org/terms/1.0/isInvasive']), array('true', 'yes'))) {
                $this->taxon_id_with_mType_InvasiveRange[$taxonID] = '';
            }
            //===========================================================================================================================================================
            // if($i >= 10) break; //debug only
            //===========================================================================================================================================================
        }
    }
    private function process_distribution($meta)
    {   //print_r($meta);
        echo "\nprocess_distribution...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => https://www.gbif.org/species/1010644
                [http://rs.tdwg.org/dwc/terms/locationID] => ISO_3166-2:BE
                [http://rs.tdwg.org/dwc/terms/locality] => Belgium
                [http://rs.tdwg.org/dwc/terms/countryCode] => BE
                [http://rs.tdwg.org/dwc/terms/occurrenceStatus] => present
                [http://rs.tdwg.org/dwc/terms/establishmentMeans] => introduced
                [http://rs.tdwg.org/dwc/terms/eventDate] => 2018/2018
                [http://purl.org/dc/terms/source] => https://www.gbif.org/species/148437977: Hanseniella caldaria (Hansen, 1903) in Reyserhove L, Groom Q, Adriaens T, Desmet P, Dekoninck W, Van Keer K, Lock K (2018). Ad hoc checklist of alien species in Belgium. Version 1.2. Research Institute for Nature and Forest (INBO). Checklist dataset https://doi.org/10.15468/3pmlxs
            )*/
            if($this->report_only_YN) { //utility report only - for Jen
                $this->debug['oS_eM'][$rec['http://rs.tdwg.org/dwc/terms/occurrenceStatus'].":".$rec['http://rs.tdwg.org/dwc/terms/establishmentMeans']] = '';
                continue;
            }
            else { //normal operation - for DwCA creation
                // $rec['http://rs.tdwg.org/dwc/terms/taxonID'] = self::format_gbif_id($rec['http://rs.tdwg.org/dwc/terms/taxonID']); obsolete
                if($val = @$this->info_map_taxonID[$rec['http://rs.tdwg.org/dwc/terms/taxonID']]) $rec['http://rs.tdwg.org/dwc/terms/taxonID'] = $val;
                else {
                    print_r($rec); exit("\nthis taxonID from Distribution doesn't exist in taxon.txt\n");
                    continue;
                }
                
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID']; //just to shorten the var.
                if(isset($this->synonym_taxa_excluded[$taxonID])) continue; //remove all MoF for synonym taxa
                //===========================================================================================================================================================
                /* For distribution: the usual columns are countryCode, occurrenceStatus, establishmentMeans. 
                - measurementValue will come from countryCode 
                (if you don't need me to map those, please go ahead and match them to our country URIs; I'm happy to help with mapping if needed). 
                - measurementType will be determined by occurrenceStatus and establishmentMeans. I think you'd better send me a report of all combinations of the two fields in the dataset, 
                and I'll make you a mapping to measurementType from that.
                */
                $mValue = self::get_uri($rec['http://rs.tdwg.org/dwc/terms/countryCode'], 'countryCode');
                $mType = self::get_mType_4distribution($rec['http://rs.tdwg.org/dwc/terms/occurrenceStatus'], $rec['http://rs.tdwg.org/dwc/terms/establishmentMeans']);

                // /* from speciesprofile specs
                if(isset($this->taxon_id_with_mType_InvasiveRange[$taxonID])) $mType = 'http://eol.org/schema/terms/InvasiveRange';
                // */

                /* Thanks for the additional fields report! The terrain is not as messy as I'd feared. 
                Let's map both http://rs.tdwg.org/dwc/terms/locality and http://rs.tdwg.org/dwc/terms/locationID to http://rs.tdwg.org/dwc/terms/locality. 
                I think the simple method would be to attach it as a column in measurementOrFact? Feel free to use whatever method you think best, though. 
                If both columns are present (eg: the Belgium file) discard locationID and use locality.
                */
                $occur_locality = '';
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/locationID']) $occur_locality = $val;
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/locality']) $occur_locality = $val;

                if(!$mType) continue; //exclude DISCARD
                $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $save = array();
                $save['taxon_id'] = $taxon_id;
                $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                $save['measurementRemarks'] = $rec['http://rs.tdwg.org/dwc/terms/establishmentMeans']." (".$rec['http://rs.tdwg.org/dwc/terms/occurrenceStatus'].")";
                $save['occur']['establishmentMeans'] = @$rec['http://rs.tdwg.org/dwc/terms/establishmentMeans'];
                $save['occur']['locality'] = $occur_locality;
                $save['occur']['eventDate'] = @$rec['http://rs.tdwg.org/dwc/terms/eventDate'];
                $save['occur']['occurrenceRemarks'] = @$rec['http://rs.tdwg.org/dwc/terms/occurrenceRemarks'];
                /* by Eli
                $save['source'] = self::get_source_from_taxonID_or_source($rec);
                */
                $save['bibliographicCitation'] = @$rec['http://purl.org/dc/terms/source'];
                $save['source'] = $this->dataset_page.$this->current_dataset_key;
                if($mValue && $mType) $this->func->add_string_types($save, $mValue, $mType, "true");
                //===========================================================================================================================================================
                // if($i >= 10) break; //debug only
                //===========================================================================================================================================================
            }
        }
    }
    /* not used anymore...
    private function get_source_from_taxonID_or_source($rec)
    {   //e.g. "https://www.gbif.org/species/141266826: Philadelphus"
        if($val = @$rec['http://purl.org/dc/terms/source']) {
            $arr = explode(": ", $val);
            if(substr($arr[0],0,4) == 'http') return $arr[0];
            else return $val;
        }
        // return "https://www.gbif.org/species/".$rec['http://rs.tdwg.org/dwc/terms/taxonID']; --- outright wrong! since not all taxonID is using GBIF convention for taxonID.
    }
    */
    private function get_uri($value, $field)
    {   
        $orig = $value;
        if($field == 'habitat') {
            $value = strtolower($value);
            if(in_array($value, array('terrrestrial', 'terresstrial', 'terretrial', 'terrestre'))) $value = 'terrestrial';
            switch($value) {
                case "terrestrial": return "http://purl.obolibrary.org/obo/ENVO_00000446";
                case "marine": return "http://purl.obolibrary.org/obo/ENVO_00000447";
                case "freshwater": return "http://purl.obolibrary.org/obo/ENVO_00000873";
                case "higr?fila": return false; //DISCARD
                case "higr��fila": return false; //DISCARD
                case "host": return array('http://eol.org/schema/terms/EcomorphologicalGuild', 'https://www.wikidata.org/entity/Q2374421');
                // default: $this->debug["undefined"][$field][$value] = '';
            }
        }

        /* fron Jen: https://eol-jira.bibalex.org/browse/DATA-1838?focusedCommentId=64048&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64048
        [KH] =>http://www.geonames.org/1527747 (but don’t rely on this ever again- I think it’s a typo) */

        $value = $orig;
        switch($value) {
            case "KH": return "http://www.geonames.org/1527747";
            default:
        }
        if($val = @$this->uris[$value]) return $val;
        elseif($val = @$this->uris[strtolower($value)]) return $val;
        else {
            $this->debug["undefined"][$field][$value] = '';
            return $orig;
        }
    }
    public function get_mType_4distribution($oS, $eM)
    {
        /*
        [present:introduced] =>http://eol.org/schema/terms/IntroducedRange
        [Present:Alien] =>http://eol.org/schema/terms/IntroducedRange
        [Present:Native|Alien] =>http://eol.org/schema/terms/Present
        [present:alien] =>http://eol.org/schema/terms/IntroducedRange
        [Alien:Present] =>http://eol.org/schema/terms/IntroducedRange
        [present:native|alien] =>http://eol.org/schema/terms/Present
        [Alien:] =>http://eol.org/schema/terms/IntroducedRange
        [present:Alien] =>http://eol.org/schema/terms/IntroducedRange
        [Present:Alien|Native] =>http://eol.org/schema/terms/Present
        [Present:Cryptogenic|Uncertain] =>DISCARD
        [present:cryptogenic|uncertain] =>DISCARD
        [present:Cryptogenic|uncertain] =>DISCARD
        [Cryptogenic|Uncertain:Present] =>DISCARD
        [Alien:Uncertain] =>DISCARD
        [Cryptogenic|Uncertain:] =>DISCARD
        [Cryptogenic|Uncertain:Uncertain] =>DISCARD
        [Uncertain:Alien] =>DISCARD
        [present:Cryptogenic/uncertain] =>DISCARD
        [present:cryptogenic/uncertain] =>DISCARD
        [Present:Uncertain] =>DISCARD
        [present:Cryptogenic|Uncertain] =>DISCARD
        */
        $combo = strtolower("$oS:$eM");
        if(stripos($combo, 'uncertain') !== false) { //string is found
            return false;
        }
        switch ($combo) {
            case "present:introduced": return "http://eol.org/schema/terms/IntroducedRange";
            case "present:alien": return "http://eol.org/schema/terms/IntroducedRange";
            case "present:native|alien": return "http://eol.org/schema/terms/Present";
            case "alien:present": return "http://eol.org/schema/terms/IntroducedRange";
            case "present:native|alien": return "http://eol.org/schema/terms/Present";
            case "alien:": return "http://eol.org/schema/terms/IntroducedRange";
            case "present:alien|native": return "http://eol.org/schema/terms/Present";
            default:
                if(!$combo) return false;
                else exit("\n combo [$combo] no mapping yet.\n");
        }
        return $combo;
    }
    private function format_gbif_id($str)
    {   //e.g. https://www.gbif.org/species/1010644
        // return $this->current_dataset_key.'_'.pathinfo($str, PATHINFO_FILENAME); obsolete...
        return $str;
    }
    function compare_meta_between_datasets() //utility to generate a report
    {
        $dataset_keys = self::get_all_dataset_keys(); //123 datasets as of Oct 11, 2019
        print_r($dataset_keys);
        $i = 0;
        foreach($dataset_keys as $dataset_key) { $i++;
            $this->info[$dataset_key] = self::get_dataset_info($dataset_key);
            // print_r($this->info); exit;
            // if($i >= 10) break; //debug only
        }
        $this->fhandle = Functions::file_open($this->comparison_file, "w");
        echo "\nSouth Africa\n";
        $this->south_africa = self::investigate_dataset($this->south_africa);
        // print_r($this->south_africa);
        // print_r($dataset_keys); exit;
        $i = 0;
        foreach($dataset_keys as $dataset_key) { $i++; echo "\n$i. $dataset_key\n";
            if($dataset_key == $this->south_africa) continue;
            // self::start_comparison('6d9e952f-948c-4483-9807-575348147c7e'); //e.g. Belgium
            self::start_comparison($dataset_key);
            // if($i >= 10) break; //debug only
        }
        fclose($this->fhandle);
        exit("\n-end utility-\n");
    }
    private function start_comparison($dataset_key)
    {
        echo "\n------------------------------------------------------------------------------\n".$this->info[$dataset_key]['dataset_name']."\n";
        fwrite($this->fhandle, "\n----------------------------------START - ".$this->info[$dataset_key]['dataset_name']." ----------------------------------\n"); 
        $country = self::investigate_dataset($dataset_key);
        /* compare no. of rowtypes against South Africa */
        if($arr = array_diff($country['rowtypes'], $this->south_africa['rowtypes'])) {
            echo "\nThere are extra tables not found in South Africa.\n";
            fwrite($this->fhandle, "\nThere are extra tables not found in South Africa.\n");
            $arr = array_values($arr); //reindex key
            print_r($arr);
            //start write to text
            $txt = implode("\n", $arr);
            fwrite($this->fhandle, $txt);
        }
        /* now compare fields in each rowtype */
        foreach($this->south_africa['rowtypes'] as $rt) {
            if($arr = array_diff($country[$rt], $this->south_africa[$rt])) {
                echo "\nThere are extra fields in [$rt], not found in South Africa.\n";
                fwrite($this->fhandle, "\n\nThere are extra fields in [$rt], not found in South Africa.\n");
                $arr = array_values($arr); //reindex key
                print_r($arr);
                //start write to text
                $txt = implode("\n", $arr);
                fwrite($this->fhandle, $txt);
            }
        }
        fwrite($this->fhandle, "\n----------------------------------END - ".$this->info[$dataset_key]['dataset_name']." ----------------------------------\n"); 
    }
    private function investigate_dataset($dataset_key)
    {   /*Array(
            [6d9e952f-948c-4483-9807-575348147c7e] => Array(
                    [orig] => https://ipt.inbo.be/resource?r=unified-checklist
                    [download_url] => https://ipt.inbo.be/archive.do?r=unified-checklist
                )
        )*/
        $info = self::download_extract_dwca($this->info[$dataset_key]['download_url'], $dataset_key);
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        $tables = $info['harvester']->tables;
        // self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        $rowtypes = array_keys($tables);
        $final = array();
        foreach($rowtypes as $rowtype) {
            $meta = $tables[$rowtype][0];
            // print_r($meta);
            $fields = self::get_all_fields($meta);
            // print_r($fields);
            $final[$rowtype] = $fields;
        }
        $final['rowtypes'] = $rowtypes;

        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        debug("\n temporary directory removed: $temp_dir\n");
        // */
        // print_r($final);
        return $final;
    }
    private function get_all_fields($meta)
    {
        foreach($meta->fields as $f) $final[$f['term']] = '';
        return array_keys($final);
    }
    private function download_extract_dwca($url, $dataset_key)
    {
        $target = $this->dwca_folder."$dataset_key.zip";
        if(!file_exists($target)) {
            // $out = shell_exec("wget -q $url -O $target");
            $out = shell_exec("wget $url -O $target");
            echo "\n$out\n";
        }
        else debug("\nalready exists: [$target]\n");
        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*30); //probably default expires in a month 60*60*24*30. Not false.
        $paths = $func->extract_archive_file($target, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit;
        // */

        /* development only -- no longer used since DwCAs are quite small enough.
        $paths = Array (
            'archive_path' => "/Library/WebServer/Documents/eol_php_code/tmp/flora_dir_29170/",
            'temp_dir' => "/Library/WebServer/Documents/eol_php_code/tmp/flora_dir_29170/"
        );
        */
        
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!(@$tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    public function get_dataset_info($dataset_key)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false; //delibarately false, coz dataset info doesn't change that much
        $url = str_replace('DATASET_KEY', $dataset_key, $this->service['dataset']);
        if($xml = Functions::lookup_with_cache($url, $options)) {
            if(preg_match("/<title>(.*?)<\/title>/ims", $xml, $arr)) $dataset_name = $arr[1];
            else exit("\nInvestigate: cannot get dataset name ($dataset_key)\n");
            $aI = ''; $download_url = '';
            if(preg_match_all("/<alternateIdentifier>(.*?)<\/alternateIdentifier>/ims", $xml, $arr)) {
                foreach($arr[1] as $aI) {
                    if(substr($aI,0,4) == 'http') {
                        // echo "\n$aI";
                        /* string manipulate from: $aI to: $download_url
                        https://ipt.inbo.be/resource?r=unified-checklist        -   https://ipt.inbo.be/archive.do?r=unified-checklist
                        http://ipt.ala.org.au/resource?r=griis-united_kingdom   -   http://ipt.ala.org.au/archive.do?r=griis-united_kingdom
                        */
                        $download_url = str_replace('resource?', 'archive.do?', $aI);
                    }
                }
            }
            // echo "\n --- $dataset_name\n";
            $citation = self::parse_citation($xml);
            return array('dataset_name' => $dataset_name, 'dataset_key' => $dataset_key, 'orig' => $aI, 'download_url' => $download_url, 'citation' => $citation);
        }
        else exit("\ndataset_key ($dataset_key) not found...\n");
    }
    private function parse_citation($xml)
    {   /*
        <citation>
            iNaturalist.org (2019). iNaturalist Research-grade Observations. Occurrence dataset https://doi.org/10.15468/ab3s5x accessed via GBIF.org on 2019-10-24.
        </citation>
        OR
        <citation identifier="http://doi.org/10.15468/l6smob">
            Robinson T, Ivey P, Powrie L, Winter P, Wong L J, Pagad S (2019). Global Register of Introduced and Invasive Species- South Africa. Version 2.4. Invasive Species Specialist Group ISSG. Checklist dataset https://doi.org/10.15468/l6smob accessed via GBIF.org on 2019-10-24.
        </citation>
        */
        if(preg_match("/<citation>(.*?)<\/citation>/ims", $xml, $arr)) return $arr[1];
        elseif(preg_match("/<citation (.*?)<\/citation>/ims", $xml, $arr)) {
            $str = $arr[1];
            $pos = strpos($str, ">");
            if($pos === false) { // echo "The string '$findme' was not found in the string '$str'";
                exit("\n---------\n$str\n---------\nInvestigate: citation parsing.\n");
            }
            else { // echo "The string '$findme' was found in the string '$str' and exists at position $pos";
                return trim(substr($str, $pos+1, strlen($str)));
            }
        }
    }
    private function get_all_dataset_keys()
    {
        if($total_datasets = self::get_total_no_datasets()) {
            $counter = ceil($total_datasets/20) - 1; //minus 1 is important. Needed due to the nature of offset values
            $offset = 0;
            for($i = 0; $i <= $counter; $i++) {
                // echo "\n$offset";
                $url = str_replace('OFFSET_NO', $offset, $this->service['list of ISSG datasets']);
                if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                    $obj = json_decode($json);
                    foreach($obj->results as $res) $dataset_keys[$res->key] = '';
                }
                $offset = $offset + 20;
            }
            return array_keys($dataset_keys);
        }
    }
    private function get_total_no_datasets()
    {   $url = str_replace('OFFSET_NO', '0', $this->service['list of ISSG datasets']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json);
            return $obj->count;
        }
    }
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings, 60*60*24); //add more mappings used in the past
        // print_r($this->uris); exit;
        if($this->uris['Oceanic'] == 'http://purl.obolibrary.org/obo/ENVO_00000447') echo "\nRe-mapping is good.\n";
        else echo "\nERROR: Re-mapping failed.\n";
        echo "\nURIs total: ".count($this->uris)."\n";
    }
    /*================================================================= copied templates below ======================================================================*/
    /*
    function x_start($info)
    {   $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        unset($this->occurrenceID_bodyPart);
        require_library('connectors/TraitGeneric'); $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        self::initialize_mapping(); //for location string mappings
        self::process_per_state();
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            } // print_r($rec); exit;
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nprocess_occurrence...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            $uris = array_keys($rec);
            $uris = array('http://rs.tdwg.org/dwc/terms/occurrenceID', 'http://rs.tdwg.org/dwc/terms/taxonID', 'http:/eol.org/globi/terms/bodyPart');
            if($bodyPart = @$this->occurrenceID_bodyPart[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']]) $rec['http:/eol.org/globi/terms/bodyPart'] = $bodyPart;
            else                                                                                             $rec['http:/eol.org/globi/terms/bodyPart'] = '';
            $o = new \eol_schema\Occurrence_specific();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function create_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID  = $rec["Symbol"];
        $taxon->scientificName  = $rec["Scientific Name with Author"];
        $taxon->taxonomicStatus = 'valid';
        $taxon->family  = $rec["Family"];
        $taxon->source = $rec['source_url'];
        // $taxon->taxonRank       = '';
        // $taxon->taxonRemarks    = '';
        // $taxon->rightsHolder    = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function create_vernacular($rec)
    {   if($comname = $rec['National Common Name']) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $rec["Symbol"];
            $v->vernacularName  = $comname;
            $v->language        = 'en';
            $this->archive_builder->write_object_to_file($v);
        }
    }
    */
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
