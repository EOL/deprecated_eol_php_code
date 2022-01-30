<?php
namespace php_active_record;
/* connector: GBIF country nodes - type records & classification resource
872 Germany
886 France
887 Netherlands
892 Brazil
893 Sweden
894 United Kingdom
including iDigBio [885]

php update_resources/connectors/872.php
php update_resources/connectors/886.php
php update_resources/connectors/887.php
php update_resources/connectors/892.php
php update_resources/connectors/893.php
php update_resources/connectors/894.php
php update_resources/connectors/885.php

The URL zip paths came from an email from downloads@gbif.org. That is upon request to download from their site: as of Oct 3, 2019 downloads
e.g. 
wget -q http://api.gbif.org/v1/occurrence/download/request/0010139-190918142434337.zip -O /extra/other_files/GBIF_DwCA/Germany_0010139-190918142434337.zip
wget -q http://api.gbif.org/v1/occurrence/download/request/0010142-190918142434337.zip -O /extra/other_files/GBIF_DwCA/Sweden_0010142-190918142434337.zip
wget -q http://api.gbif.org/v1/occurrence/download/request/0010147-190918142434337.zip -O /extra/other_files/GBIF_DwCA/UK_0010147-190918142434337.zip
wget -q http://api.gbif.org/v1/occurrence/download/request/0010150-190918142434337.zip -O /extra/other_files/GBIF_DwCA/France_0010150-190918142434337.zip
wget -q http://api.gbif.org/v1/occurrence/download/request/0010183-190918142434337.zip -O /extra/other_files/GBIF_DwCA/Brazil_0010183-190918142434337.zip
wget -q http://api.gbif.org/v1/occurrence/download/request/0010181-190918142434337.zip -O /extra/other_files/GBIF_DwCA/Netherlands_0010181-190918142434337.zip

all from eol-archive:
872	Thursday 2018-08-02 08:54:52 PM	{"measurement_or_fact.tab":639195,"occurrence.tab":167662,"taxon.tab":80072}
872	Friday 2019-10-04 08:09:27 PM	{"measurement_or_fact.tab":1049247,"occurrence.tab":271867,"taxon.tab":151012,"time_elapsed":{"sec":1029.14,"min":17.15,"hr":0.29}}
872	Thursday 2019-10-10 04:12:00 AM	{"measurement_or_fact.tab":1049247,"occurrence.tab":271867,"taxon.tab":151012,"time_elapsed":{"sec":991.04,"min":16.52,"hr":0.28}}

886	Thursday 2018-08-02 10:48:13 PM	{"measurement_or_fact.tab":837637,"occurrence.tab":212745,"taxon.tab":95622}
886	Friday 2019-10-04 08:25:37 PM	{"measurement_or_fact.tab":1074057,"occurrence.tab":271039,"taxon.tab":137903,"time_elapsed":{"sec":959.86,"min":16,"hr":0.27}}
886	Thursday 2019-10-10 04:27:40 AM	{"measurement_or_fact.tab":1074057,"occurrence.tab":271039,"taxon.tab":137903,"time_elapsed":{"sec":930.49,"min":15.51,"hr":0.26}}

887	Thursday 2018-08-02 10:57:39 PM	{"measurement_or_fact.tab":533798,"occurrence.tab":139483,"taxon.tab":52758}
887	Friday 2019-10-04 08:31:40 PM	{"measurement_or_fact.tab":115120,"occurrence.tab":31837,"taxon.tab":17599,"time_elapsed":{"sec":353.23,"min":5.89,"hr":0.1}}
887	Thursday 2019-10-10 04:33:48 AM	{"measurement_or_fact.tab":115120,"occurrence.tab":31837,"taxon.tab":17599,"time_elapsed":{"sec":358.26,"min":5.97,"hr":0.1}}

892	Thursday 2018-08-02 10:58:59 PM	{"measurement_or_fact.tab":49469,"occurrence.tab":12433,"taxon.tab":5953}
892	Friday 2019-10-04 08:42:04 PM	{"measurement_or_fact.tab":301212,"occurrence.tab":75544,"taxon.tab":25667,"time_elapsed":{"sec":617,"min":10.28,"hr":0.17}}
892	Thursday 2019-10-10 04:38:24 AM	{"measurement_or_fact.tab":301212,"occurrence.tab":75544,"taxon.tab":25667,"time_elapsed":{"sec":269.77,"min":4.5,"hr":0.07000000000000001}}

893	Thursday 2018-08-02 11:04:27 PM	{"measurement_or_fact.tab":341938,"occurrence.tab":87627,"taxon.tab":50393}
893	Friday 2019-10-04 08:47:07 PM	{"measurement_or_fact.tab":341938,"occurrence.tab":87627,"taxon.tab":50393,"time_elapsed":{"sec":295.06,"min":4.92,"hr":0.08}}
893	Thursday 2019-10-10 04:43:25 AM	{"measurement_or_fact.tab":341938,"occurrence.tab":87627,"taxon.tab":50393,"time_elapsed":{"sec":295.5,"min":4.93,"hr":0.08}}

894	Thursday 2018-08-02 11:12:17 PM	{"measurement_or_fact.tab":499598,"occurrence.tab":135121,"taxon.tab":81241}
894	Friday 2019-10-04 09:12:40 PM	{"measurement_or_fact.tab":1705335,"occurrence.tab":444151,"taxon.tab":233336,"time_elapsed":{"sec":1522.83,"min":25.38,"hr":0.42}}
894	Thursday 2019-10-10 05:09:10 AM	{"measurement_or_fact.tab":1705335,"occurrence.tab":444151,"taxon.tab":233336,"time_elapsed":{"sec":1536.05,"min":25.6,"hr":0.43}}

was removed in mapping (GISD/mapped_location_strings.txt)
        added from GBIF country nodes (DATA-1809) on Oct 6, 2019 start ------------	
        secondarytype	http://rs.tdwg.org/ontology/voc/TaxonName#SecondaryType
        supplementarytype	http://rs.tdwg.org/ontology/voc/TaxonName#SupplementaryType
        added from GBIF country nodes (DATA-1809) on Oct 6, 2019 end ------------	
These 4 per Jen: "Eli. I think we'll leave them as strings for now. They sound like fringe categories."
'secondarytype' 'exlectotype' 'supplementarytype' 'exisotype'
*/
class GBIFCountryTypeRecordAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->occurrence_ids = array();
        $this->debug = array();
        $this->spreadsheet_options = array('resource_id' => 'gbif', 'cache' => 1, 'timeout' => 3600, 'file_extension' => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2); //set 'cache' to 0 if you don't want to cache spreadsheet
        $this->spreadsheet_options['expire_seconds'] = 60*60*24*1; //expires after 1 day
        // $this->spreadsheet_options['expire_seconds'] = 60*60; //during dev only
        
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 1, 'expire_seconds' => false); //60*60*24*365
        
        if(Functions::is_production()) {
            $this->download_options['cache_path'] = "/extra/eol_cache_gbif/";
        }
        else {
            $this->download_options['resource_id'] = "gbif";
            $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache/";
        }

        // for iDigBio
        $this->IDB_service["record"] = "http://api.idigbio.org/v1/records/";
        $this->IDB_service["recordset"] = "http://api.idigbio.org/v1/recordsets/";
    }
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris1 = Functions::additional_mappings($mappings, 60*60*1); //add more mappings used in the past | 1 hour expires
        echo "\nURIs 1 total: ".count($this->uris1)."\n";
        // echo "\n".$this->uris['hapantotype'];
        // echo "\n".$this->uris['hypotype'];
        // echo "\n".$this->uris['plesiotype'];
        // echo "\n".$this->uris['syntype'];
        // echo "\n".$this->uris['plastotype'];
        // echo "\n".$this->uris['secondarytype'];         x    secondary type ( http://rs.tdwg.org/ontology/voc/TaxonName#SecondaryType ) 
        // echo "\n".$this->uris['supplementarytype'];     x    supplementary Type ( http://rs.tdwg.org/ontology/voc/TaxonName#SupplementaryType ) 
        // echo "\n".$this->uris['exlectotype'];           x
        // echo "\n".$this->uris['exisotype'];             x
    }
    function export_gbif_to_eol($params) //main program for GBIF country nodes
    {
        self::initialize_mapping();
        if(!is_dir($this->download_options['cache_path']))  mkdir($this->download_options['cache_path']);
        
        $this->uris2 = self::get_uris($params, $params["uri_file"]);
        $this->specific_mapping_for_this_resource = $this->uris2;
        echo "\nURIs 2 total: ".count($this->uris2)."\n";
        
        $this->uris = array_merge($this->uris1, $this->uris2);
        echo "\nURIs total: ".count($this->uris)."\n"; //exit;
        unset($this->uris1); unset($this->uris2);
        // print_r($this->uris); print_r($this->specific_mapping_for_this_resource); exit;
        
        $params["uri_type"] = "citation";
        if($file = @$params["citation_file"]) $this->citations = self::get_uris($params, $file);
        
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($params["dwca_file"], "meta.xml", array("timeout" => 7200, "expire_seconds" => false)); //60*60*24*25
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $this->harvester = new ContentArchiveReader(NULL, $archive_path);
        if(!(@$this->harvester->tables["http://rs.tdwg.org/dwc/terms/occurrence"][0]->fields)) // take note the index key is all lower case
        {
            echo "\nInvalid archive file. Program will terminate.\n";
            return false;
        }

        if($params["dataset"] == "GBIF") {
            $params["row_type"] = "http://rs.tdwg.org/dwc/terms/occurrence";
            $params["location"] = "occurrence.txt";
            self::process_row_type($params);
        }
        elseif($params["dataset"] == "iDigBio") {
            $params["row_type"] = "http://rs.tdwg.org/dwc/terms/occurrence";
            $params["location"] = "occurrence.txt";
            self::process_row_type($params);
        }
        /* old ways
        self::process_row_type(array("row_type" => 'http://rs.gbif.org/terms/1.0/Multimedia', "location" => "multimedia.txt"));
        self::create_instances_from_taxon_object($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/occurrence'));
        self::get_media_objects($harvester->process_row_type('http://rs.gbif.org/terms/1.0/Multimedia'));
        self::get_objects($harvester->process_row_type('http://eol.org/schema/media/Document'));
        self::get_references($harvester->process_row_type('http://rs.gbif.org/terms/1.0/Reference'));
        */
        $this->archive_builder->finalize(TRUE);
        recursive_rmdir($temp_dir); // remove temp dir
        print_r($this->debug);
    }
    function get_uris($params, $spreadsheet)
    {
        $fields = array();
        if($params["dataset"] == "GBIF") {
            $fields["sex"] = "sex_uri";
            $fields["typeStatus"] = "typeStatus_uri";
            if(@$params["country"] == "Sweden") $fields["datasetKey"]      = "Type Specimen Repository URI"; //exception to the rule
            else                                $fields["institutionCode"] = "institutionCode_uri";          //rule case
            
            if(@$params["uri_type"] == "citation") { // additional fields when processing citation spreadsheets
                $fields["datasetKey France"]  = "BibliographicCitation"; //886
                $fields["datasetKey UK"]      = "BibliographicCitation"; //894
                $fields["datasetKey Germany"] = "BibliographicCitation"; //872
                $fields["datasetKey Brazil"]  = "BibliographicCitation"; //892
                $fields["datasetKey"]         = "BibliographicCitation"; //from Netherlands (887), Sweden (893) spreadsheet
            }

        }
        else $fields = $params["fields"];
        
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();

        if($val = @$params["spreadsheet_options"]) $spreadsheet_options = $val;
        else                                       $spreadsheet_options = $this->spreadsheet_options;
        
        $uris = array();
        echo("\nspreadsheet: [$spreadsheet]\n"); //debug
        if($spreadsheet) {
            if($arr = $func->convert_spreadsheet($spreadsheet, 0, $spreadsheet_options)) {
                 foreach($fields as $key => $value) {
                     $i = 0;
                     if(@$arr[$key]) {
                         foreach($arr[$key] as $item) {
                             $item = trim($item);
                             if($item) {
                                 $temp = $arr[$value][$i];
                                 $temp = trim(str_replace(array("\n"), "", $temp));
                                 $uris[$item] = $temp;
                                 if(!Functions::is_utf8($temp)) echo "\nnot utf8: [$temp]\n";
                             }
                             $i++;
                         }
                     }
                 }
            }
        }
        return $uris;
    }
    private function process_row_type($params, $callback = NULL, $parameters = NULL)
    {
        $row_type = $params["row_type"];
        $location = $params["location"];
        if(isset($this->harvester->tables[strtolower($row_type)])) {
            foreach($this->harvester->tables[strtolower($row_type)] as $table_definition) {
                if($table_definition->location != $location) continue;
                $this->harvester->file_iterator_index = 0;
                // rows are on newlines, so we can stream the file with an iterator
                if($table_definition->lines_terminated_by == "\n") {
                    $parameters['archive_table_definition'] =& $table_definition;
                    $i = 0;
                    foreach(new FileIterator($table_definition->file_uri) as $line_number => $line) {
                        $line = Functions::conv_to_utf8($line);
                        if(!Functions::is_utf8($line)) exit("\nnot utf8\n");
                        $i++;
                        if(($i % 10000) == 0) echo "\n" . $params["type"] . " - $i ";
                        // else                  echo "\n" . $params["type"] . " -> $i ";
                        
                        /* breakdown when caching - iDigBIO up to 5 simultaneous connectors
                        $m = 200000;
                        $cont = false;
                        // if($i >=  1    && $i < $m)    $cont = true;
                        // if($i >=  $m   && $i < $m*2)  $cont = true;
                        // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                        // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                        // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                        if(!$cont) continue;
                        */
                        
                        $parameters['archive_line_number'] = $line_number;
                        if($table_definition->ignore_header_lines) $parameters['archive_line_number'] += 1;
                        $fields = $this->harvester->parse_table_row($table_definition, $line, $parameters);
                        if($fields == "this is the break message") break;
                        if($fields && $callback) call_user_func($callback, $fields, $parameters);
                        elseif($fields) {
                            $fields = array_map('trim', $fields);
                            if(!trim((string) $fields["http://rs.tdwg.org/dwc/terms/scientificName"])) continue;

                            if($val = self::get_taxon_id($fields)) $fields["taxon_id"] = $val;
                            else continue; //no taxon_id - found just 1 record here, maybe the last row in text file.

                            if($params["dataset"] == "GBIF") {
                                $fields["dataset"] = "GBIF";
                                $fields["country"] = $params["country"];
                                if($params["type"] == "structured data")                self::create_type_records_gbif($fields);
                                elseif($params["type"] == "classification resource")    self::create_classification_gbif($fields);
                            }
                            elseif($params["dataset"] == "iDigBio") {
                                $fields["dataset"] = "iDigBio";
                                if($params["type"] == "structured data")                self::create_type_records_idigbio($fields);
                            }
                            // old ways: elseif($row_type == "http://rs.gbif.org/terms/1.0/Multimedia") self::get_media_objects($fields);
                        }
                        // if($i >= 1000) break; //debug - used during preview mode
                    }
                }
                // otherwise we need to load the entire file into memory and split it
                else exit("\n -does not go here- \n");
            }
        }
    }
    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec["taxon_id"];
        $taxon->scientificName  = self::clean_sciname((string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"]);
        if(!$taxon->scientificName) return false;
        $taxon->kingdom         = (string) $rec["http://rs.tdwg.org/dwc/terms/kingdom"];
        $taxon->phylum          = (string) $rec["http://rs.tdwg.org/dwc/terms/phylum"];
        $taxon->class           = (string) $rec["http://rs.tdwg.org/dwc/terms/class"];
        $taxon->order           = (string) $rec["http://rs.tdwg.org/dwc/terms/order"];
        $taxon->family          = (string) $rec["http://rs.tdwg.org/dwc/terms/family"];
        $taxon->genus           = (string) $rec["http://rs.tdwg.org/dwc/terms/genus"];
        $taxon->taxonRank       = strtolower((string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"]);

        $taxon = self::check_sciname_ancestry_values($taxon);

        if(in_array($taxon->taxonRank, array("var.", "f.", "var"))) $taxon->taxonRank = "";
        if($taxon->scientificName || $taxon->genus || $taxon->family || $taxon->order || $taxon->class || $taxon->phylum || $taxon->kingdom) {
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
        }
        /* Have not specified by Jen based on DATA-1557
        $taxon->scientificNameAuthorship  = (string) @$rec["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"]; // not all records have scientificNameAuthorship
        $taxon->taxonomicStatus = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
        $taxon->taxonRemarks    = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRemarks"];
        $taxon->namePublishedIn = (string) $rec["http://rs.tdwg.org/dwc/terms/namePublishedIn"];
        $taxon->rightsHolder    = (string) $rec["http://purl.org/dc/terms/rightsHolder"];
        */
        return true;
    }
    private function check_sciname_ancestry_values($taxon)
    {    //scientificname should not be equal to any of the ancestry
        $canonical = Functions::canonical_form($taxon->scientificName);
        if(self::remove_parenthesis($taxon->kingdom) == $canonical) $taxon->kingdom = '';
        if(self::remove_parenthesis($taxon->phylum) == $canonical)  $taxon->phylum = '';
        if(self::remove_parenthesis($taxon->class) == $canonical)   $taxon->class = '';
        if(self::remove_parenthesis($taxon->order) == $canonical)   $taxon->order = '';
        if(self::remove_parenthesis($taxon->family) == $canonical)  $taxon->family = '';
        if(self::remove_parenthesis($taxon->genus) == $canonical)   $taxon->genus = '';
        return $taxon;
    }
    private function remove_parenthesis($str)
    {
        $str = str_replace(array("(", ")"), "", $str);
        return trim($str);
    }
    private function create_classification_gbif($rec)
    {
        $species = trim((string) $rec["http://rs.gbif.org/terms/1.0/species"]);
        $sciname = trim((string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"]);
        if(!$species || !$sciname) return;
        if(Functions::canonical_form($species) == Functions::canonical_form($sciname)) return;

        $taxon_id = md5($species);
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $taxon_id;
        $taxon->scientificName  = self::clean_sciname($species);
        if(!$taxon->scientificName) return;
        $taxon->kingdom         = (string) $rec["http://rs.tdwg.org/dwc/terms/kingdom"];
        $taxon->phylum          = (string) $rec["http://rs.tdwg.org/dwc/terms/phylum"];
        $taxon->class           = (string) $rec["http://rs.tdwg.org/dwc/terms/class"];
        $taxon->order           = (string) $rec["http://rs.tdwg.org/dwc/terms/order"];
        $taxon->family          = (string) $rec["http://rs.tdwg.org/dwc/terms/family"];
        $taxon->genus           = (string) $rec["http://rs.tdwg.org/dwc/terms/genus"];
        $taxon->taxonRank       = strtolower((string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"]);

        $taxon = self::check_sciname_ancestry_values($taxon);

        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        
        //create the synonym
        $synonym_taxon_id = md5($sciname);
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $synonym_taxon_id;
        $taxon->scientificName  = self::clean_sciname($sciname);
        if(!$taxon->scientificName) return;
        $taxon->taxonRank       = strtolower((string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"]);
        $taxon->taxonomicStatus     = "synonym";
        $taxon->acceptedNameUsageID = $taxon_id;
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function clean_sciname($name) //https://eol-jira.bibalex.org/browse/DATA-1549?focusedCommentId=66627&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66627
    {
        $name = str_replace(array("?", '"', "'"), "", trim($name));
        //--------------------------
        /* "(Archaeidae)" ->  I think for cases where there's just one string, all in parentheses,
                              the parentheses can just be stripped and the string used as is. https://eol-jira.bibalex.org/browse/DATA-1549?focusedCommentId=66629&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66629
        */
        /*Sure- two of your own examples above qualify for removal: CASE 1
        (Blattoidea) melanderi
        (Protoblattoidea)? minor

        The other two categories are:
        The entire string is inside parentheses, eg: (Archaeidae) => Archaeidae --- CASE 2
        AND
        Subgenus usage, which can be modeled as: --- CASE 3
        string1 (string2) string3
        There might be additional strings following- subspecies name and/or authority information- but if there are at least 3 strings, 
        and only the second one is in parentheses, the record is probably a legit subgenus usage and requires no intervention.
        */
        
        if(self::get_number_of_words($name) == 1) $name = str_replace(array("(", ")"), "", $name); //CASE 2
        elseif(self::get_number_of_words($name) == 2 && self::there_is_string_inside_and_outside_parenthesis($name)) return false; //CASE 1
        elseif(self::get_number_of_words($name) >= 3) {} //leave as is --- CASE 3
        //--------------------------
        if(ctype_digit($name[0])) return false; //exclude if first char is digit
        if(stripos($name, " egg") !== false) return false; //string is found e.g. "'trilobite eggs'"
        //--------------------------
        $name = str_replace("( ", "(", $name); // Micrarionta ( Eremarionta) chacei
        $name = str_replace(" )", ")", $name); // Epitonium (Punctiscala ) colimanum
        return Functions::remove_whitespace($name);
    }
    private function there_is_string_inside_and_outside_parenthesis($name)
    {
        if(preg_match_all("/\((.*?)\)/ims", $name, $arr)) { //there is/are set of parenthesis
            $results = $arr[1];
            if(count($results) == 1) {
                if($results[0]) { //there is something inside the parenthesis
                    $tmp = str_replace("(".$results[0].")", "", $name); //remove the parenthesis first to check if there are other strings outside parenthesis
                    if(trim($tmp)) return true;
                }
            }
            else return true; //more than 1 set of parenthesis e.g. should be like this at this point: "(string1) (string2)"
        }
    }
    private function get_number_of_words($name)
    {
        $arr = explode(" ", Functions::remove_whitespace($name));
        return count($arr);
    }
    /*
    Hi Jen,
    Attached are the unique [dwc:institutionCode] and [dwc:typeStatus] - xxx.xls.
    I didn't clean them. Please flag those that are not to be used, to be ignored especially for the typeStatus.
    Thanks.
    
    Hi Jen, there are values for [dwc:typeStatus] equal to these:
    basis of illustration of <a href=""http://arctos.database.museum/name/Insecta"">Insecta</a>, page 3 in <a href=""http://arctos.database.museum/publication/10006415"">Grimaldi and Triplehorn 2008</a>
    referral of <a href=""http://arctos.database.museum/name/Ursus arctos""><i>Ursus arctos</i> (Linnaeus, 1758)</a>, page 74 in <a href=""http://arctos.database.museum/publication/10006542"">Talbot et al. 2006</a>
    Anyway, I just ignored them.
    */
    private function get_taxon_id($rec)
    {
        $taxon_id = trim((string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"]);
        if(!$taxon_id) {
            if    ($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"]))   $taxon_id = str_replace(" ", "_", Functions::canonical_form($val));//md5($val);
            elseif($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/genus"]))            $taxon_id = md5($val);
            elseif($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/family"]))           $taxon_id = md5($val);
            elseif($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/order"]))            $taxon_id = md5($val);
            elseif($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/class"]))            $taxon_id = md5($val);
            elseif($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/phylum"]))           $taxon_id = md5($val);
            elseif($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/kingdom"]))          $taxon_id = md5($val);
            else exit("\n got it \n");
        }
        return $taxon_id;
    }
    private function get_institution_name($rec) //only for iDigBio
    {
        $record_id = (string) $rec[""];
        if($html = Functions::lookup_with_cache($this->IDB_service["record"].$record_id, $this->download_options)) {
            $json = json_decode($html);
            $recordset = (string) $json->{"idigbio:links"}->{"recordset"}[0];
            if($html = Functions::lookup_with_cache($recordset, $this->download_options)) {
                $json = json_decode($html);
                $institution = (string) $json->{"idigbio:data"}->{"collection_name"};
                if($val = (string) $json->{"idigbio:data"}->{"institution_web_address"}) $institution .= " {" . $val . "}";
                if($institution && !is_numeric(substr($institution,0,3))) return $institution;
            }
        }
        return "";
    }
    private function create_type_records_idigbio($rec) // structured data
    {
        if(trim($rec['taxon_id']) == "University") { //reported here: https://eol-jira.bibalex.org/browse/DATA-1549?focusedCommentId=66616&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66616
            print_r($rec); //exit;
            return;
        }
        /*Array(
            [] => 00036b59-d95a-4b19-b8ea-135aa7b97ecc
            [http://portal.idigbio.org/terms/inhs/dead] => 
            [http://portal.idigbio.org/terms/inhs/total_Males] => 
            [http://rs.tdwg.org/dwc/terms/county] => 
            [http://rs.tdwg.org/dwc/terms/locality] => Puaucho, 14.5 km W
            [http://portal.idigbio.org/terms/tribe] => 
            [http://rs.tdwg.org/dwc/terms/infraspecificEpithet] => 
            [http://rs.tdwg.org/dwc/terms/rightsHolder] => 
            [http://rs.tdwg.org/dwc/terms/lifeStage] => adult
            ...
            [http://purl.org/dc/terms/modified] => 2010-10-19 10:21:32.0
            [http://portal.idigbio.org/terms/fcc/superfamily] => 
            [http://rs.tdwg.org/dwc/terms/footprintWKT] => 
            [http://rs.tdwg.org/dwc/terms/datasetID] => 
            [http://rs.tdwg.org/dwc/terms/year] => 1994
            [taxon_id] => Holotrochus_chilensis
            [dataset] => iDigBio
        )*/
        
        
        
        if(count($rec) != 200) exit("\n count is not 200: " . count($rec));
        
        $rec["catnum"] = $rec[""];
        if(!$rec["catnum"]) {
            print_r($rec);
            exit("\n no catnum \n");
        }
        /* sample values
        [] => 000dab68-93a2-4c59-ac3b-b1b498982d00
        [http://purl.org/dc/terms/source]               => http://purl.oclc.org/net/edu.harvard.huh/guid/uuid/224312dc-484c-4f32-bcf0-09ecd76edb03
        [http://portal.idigbio.org/terms/etag]          => 28b6c48e2015bf7fa7a81aa06086edb3f60eae88
        [http://portal.idigbio.org/terms/uuid]          => 000dab68-93a2-4c59-ac3b-b1b498982d00
        [http://rs.tdwg.org/dwc/terms/occurrenceID]     => http://purl.oclc.org/net/edu.harvard.huh/guid/uuid/224312dc-484c-4f32-bcf0-09ecd76edb03
        [http://rs.tdwg.org/dwc/terms/catalogNumber]    => barcode-00097328
        [http://rs.tdwg.org/dwc/terms/collectionID]     => urn:lsid:biocol.org:col:15631
        */
        $institution = self::get_institution($rec);
        $typestatus = self:: get_type_status_iDigBio($rec);
        
        if(!$institution || !$typestatus) return;
        if(@$this->uris[$institution] == "EXCLUDE") return; //added a new way to compare, see below

        // start - a new way to compare institution from what is listed in the spreadsheet e.g. "CAS Botany (BOT) {http://www.calacademy.org/scientists/botany-collections}" is NOT DIRECTLY found in spreadsheet.
        // but "CAS Botany (BOT)" is found.
        $institution_strings = array();
        $institution_strings[] = $institution;
        $temp = explode(" {", $institution);
        if($val = $temp[0]) $institution_strings[] = $val;
        foreach($institution_strings as $institution_string) {
            if(@$this->uris[$institution_string] == "EXCLUDE") return;
        }
        // end
        
        if($occurrenceID = (string) $rec[""]) $rec["source"] = "https://www.idigbio.org/portal/records/" . $occurrenceID;
        
        // start
        $institution_uri = self::get_uri($institution, "institution");
        $typestatus_uri = self::get_uri($typestatus, "TypeInformation");
        $rec["institutionCode"] = $institution;
        
        $tax_status = self::create_instances_from_taxon_object($rec);
        if($institution_uri && $typestatus_uri && $tax_status) {
            if(strtolower($typestatus_uri) == "exclude") return; //https://eol-jira.bibalex.org/browse/DATA-1549?focusedCommentId=65600&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65600
            $parent_id = self::add_string_types($rec, $institution_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", "true");
            self::add_string_types($rec, $typestatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus", 'child', $parent_id);
            if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificName"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName", 'child', $parent_id);
            
            // /*
            //not a standard element in occurrence, but in the XLS specs from Jen
            $uris = array("http://rs.tdwg.org/dwc/terms/verbatimDepth",
                          "http://rs.tdwg.org/dwc/terms/collectionID",
                          "http://rs.tdwg.org/dwc/terms/county",
                          "http://rs.tdwg.org/dwc/terms/country",
                          "http://rs.tdwg.org/dwc/terms/waterBody",
                          "http://rs.tdwg.org/dwc/terms/higherGeography",
                          "http://rs.tdwg.org/dwc/terms/stateProvince",
                          "http://rs.tdwg.org/dwc/terms/continent",
                          "http://rs.tdwg.org/dwc/terms/georeferenceRemarks",
                          "http://rs.tdwg.org/dwc/terms/verbatimCoordinates",
                          "http://rs.tdwg.org/dwc/terms/island",
                          "http://rs.tdwg.org/dwc/terms/islandGroup",
                          "http://rs.tdwg.org/dwc/terms/maximumDepthInMeters",
                          "http://rs.tdwg.org/dwc/terms/minimumDepthInMeters",
                          "http://rs.tdwg.org/dwc/terms/maximumElevationInMeters",
                          "http://rs.tdwg.org/dwc/terms/minimumElevationInMeters",
                          "http://rs.tdwg.org/dwc/terms/latestEraOrHighestErathem");
            foreach($uris as $uri) {
                if($val = $rec[$uri]) self::add_string_types($rec, Functions::import_decode($val), $uri, 'child', $parent_id);
            }
            // */
        }
    }
    private function get_institution($rec) //only for iDigBio
    {
        $rightsHolder = trim((string) $rec["http://purl.org/dc/terms/rightsHolder"]);
        if(!$rightsHolder) $rightsHolder = trim((string) $rec["http://rs.tdwg.org/dwc/terms/rightsHolder"]);
        
        $ownerInstitutionCode   = trim((string) $rec["http://rs.tdwg.org/dwc/terms/ownerInstitutionCode"]);
        if(!$ownerInstitutionCode) $ownerInstitutionCode = trim((string) $rec["http://rs.tdwg.org/dwc/terms/institutionCode"]);
        
        $datasetName = trim((string) $rec["http://rs.tdwg.org/dwc/terms/datasetName"]);

        if(is_numeric(substr($rightsHolder,0,2))) $rightsHolder = "";
        if(is_numeric(substr($ownerInstitutionCode,0,2))) $ownerInstitutionCode = "";
        if(is_numeric(substr($datasetName,0,2))) $datasetName = "";

        $institution = '';
        if((!$rightsHolder && !$ownerInstitutionCode) || (!$rightsHolder && (is_numeric(substr($datasetName,0,3)) || !$datasetName))) {
            /*
            echo "\n will start search for institution_name... =====";
            echo "\n datasetID:" . $rec["http://rs.tdwg.org/dwc/terms/datasetID"];
            echo "\n datasetName:" . $rec["http://rs.tdwg.org/dwc/terms/datasetName"];
            echo "\n collectionID:" . $rec["http://rs.tdwg.org/dwc/terms/collectionID"];
            echo "\n collectionCode:" . $rec["http://rs.tdwg.org/dwc/terms/collectionCode"];
            echo "\n institutionID:" . $rec["http://rs.tdwg.org/dwc/terms/institutionID"];
            echo "\n ownerInstitutionCode:" . $rec["http://rs.tdwg.org/dwc/terms/ownerInstitutionCode"];
            echo "\n institutionCode:" . $rec["http://rs.tdwg.org/dwc/terms/institutionCode"];
            echo "\n dwc:rightsHolder:" . $rec["http://rs.tdwg.org/dwc/terms/rightsHolder"];
            echo "\n dc:rightsHolder:" . $rec["http://purl.org/dc/terms/rightsHolder"];
            echo "\n recordID:" . $rec["http://portal.idigbio.org/terms/recordID"];
            echo "\n recordId:" . $rec["http://portal.idigbio.org/terms/recordId"];
            */
            $institution = self::get_institution_name($rec);
            // echo "\n found institution_name1: [$institution] =====\n";

            if(!$institution) { // 2nd option for institution value
                $institution_arr = array();
                if($val = $rec["http://rs.tdwg.org/dwc/terms/institutionCode"]) $institution_arr[$val] = '';
                if($val = $rec["http://rs.tdwg.org/dwc/terms/ownerInstitutionCode"]) $institution_arr[$val] = '';
                if($val = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"]) $institution_arr[$val] = '';
                if($val = $rec["http://rs.tdwg.org/dwc/terms/collectionID"]) $institution_arr[$val] = '';
                if($val = $rec["http://rs.tdwg.org/dwc/terms/institutionID"]) $institution_arr[$val] = '';
                if($val = $rec["http://rs.tdwg.org/dwc/terms/datasetID"]) $institution_arr[$val] = '';
                if($val = $rec["http://rs.tdwg.org/dwc/terms/rightsHolder"]) $institution_arr[$val] = '';
                if($val = $rec["http://purl.org/dc/terms/rightsHolder"]) $institution_arr[$val] = '';
                if($val = $rec["http://rs.tdwg.org/dwc/terms/datasetName"]) $institution_arr[$val] = '';
                $institution_arr = array_keys($institution_arr);
                foreach($institution_arr as $val) {
                    if(substr($val, 0, 4) != "urn:") $institution .= "($val) ";
                }
                $institution = trim($institution);
            }
            // echo "\n found institution_name2: [$institution] =====\n";
        }
        // else $institution = self::get_institution_name($rec); // debug --- comment in normal operation, use this if you want to API-call all institution recordsets

        /* for stats
        $all = "[$rightsHolder]-[$ownerInstitutionCode]-[$datasetName]-[$institution]";
        if(isset($this->debug["all"][$all])) $this->debug["all"][$all]++;
        else                                 $this->debug["all"][$all] = 1;
        */
        
        //start final formatting
        $final = "";
        if($val = $rightsHolder) $final .= $val;
        if($val = $ownerInstitutionCode) {
            if($final) $final .= " ($val)";
            else       $final .= "($val)";
        }
        if($val = $institution) {
            if(!is_numeric(substr($val,0,3))) {
                if($final) $final .= " - $val";
                else       $final .= "$val";
            }
        }
        $final = trim($final);
        if($datasetName && !is_numeric(stripos($final, $datasetName))) {
            if(!is_numeric(substr($datasetName,0,3))) {
                if($final) $final .= " - $datasetName";
                else       $final .= "$datasetName";
            }
        }
        $final = trim($final);
        /* for stats
        if(isset($this->debug["final"][$final])) $this->debug["final"][$final]++;
        else                                     $this->debug["final"][$final] = 1;
        
        $institutionCode = (string) $rec["http://rs.tdwg.org/dwc/terms/institutionCode"];
        if(isset($this->debug["institutionCode"][$institutionCode])) $this->debug["institutionCode"][$institutionCode]++;
        else                                                         $this->debug["institutionCode"][$institutionCode] = 1;
        */
        return $final;
    }
    private function  get_type_status_iDigBio($rec)
    {
        $types = array("TYPE", "COTYPE", "ISOTYPE", "SYNTYPE", "HOLOTYPE", "LECTOTYPE", "PARATYPE", "NEOTYPE", "EXTYPE", "TOPOTYPE", "ISOSYNTYPE", 
        "ISOLECTOTYPE", "ORIGINALMATERIAL", "PARALECTOTYPE", "ICONOTYPE", "EXHOLOTYPE", "EPITYPE", "NOTATYPE", "EXPARATYPE", "ALLOTYPE", "ISONEOTYPE", 
        "EXEPITYPE", "PARANEOTYPE", "PLASTONEOTYPE", "ALLOLECTOTYPE");

        $orig_typestatus = (string) $rec["http://rs.tdwg.org/dwc/terms/typeStatus"];
        $orig_typestatus = trim(str_replace(array("\n"), " ", $orig_typestatus));
        
        $temp = explode(" ", $orig_typestatus);
        $typestatus = $temp[0];
        
        if(strtolower($orig_typestatus) == "authentic (not a type)") $typestatus = "NOTATYPE";
        else {
            //e.g. [photo of isotype] [microslide of isotype] and the likes
            if(in_array(strtolower($temp[0]), array("photo", "photos", "photograph", "microslide", "microslides", "subculture", "subcult.", "fragment", "part", "drawing", "cultures", "indicated", "isolate", "subisolate", "photomicrographs", "compared", "part")) && 
               in_array(@$temp[1], array("of", "from", "as", "with"))) $typestatus = @$temp[2];
            //e.g. [possible paratype] [? holotype of f. ulmicola] and the likes
            elseif(in_array(strtolower($temp[0]), array("possible", "probable", "topotypic", "cultivar", "scudder's", "indicated", "probably", "prob.", "likely", "recurvispina", "?"))) $typestatus = @$temp[1];
            // original material - and combination of upper/lower cases
            elseif(strtolower($temp[0]) == "original" && strtolower(@$temp[1]) == "material") $typestatus = "ORIGINALMATERIAL";
            else {
                foreach($types as $type) {
                    if(is_numeric(stripos($orig_typestatus, $type))) $typestatus = $type;
                }
            }
        }

        if(in_array(strtolower($typestatus), array("publ?", "hypodigm?", "voucher[?]", "vouchers?", "c.fr.", ".", "59.436", "457.2"))) $typestatus = "";

        $typestatus = str_replace(array("?", "."), "", $typestatus);
        if($typestatus == "ISOLECTOTY") $typestatus = "ISOLECTOTYPE";
        if($typestatus == "Typus") $typestatus = "TYPE";
        if($typestatus == "Syntypus") $typestatus = "SYNTYPE";

        if(in_array(strtolower($typestatus), array("authentic", "referral", "standard", "part", "not", "host", "erroneous", "additional", "unknown",
        "taxon", "ethanol", "other", "1715", "figured", "published", "uncertain", "secondary", "p*", "basis", "hypodigm", "dna", "voucher",
        "publ", "fig", "none", "vouchers", "'flavoconii'", "othonus", "10%", "2169", "q", "ehanol", "cited", "mentioned", "<no", "h*", "null", "etahanol", 
        "unverified", "measured", "typodigm", "primary", "original", "herbarium", 
        "ms", "etahnol", "the", "gift", "helicopha", "see", "nt", "possible", "para", "president", "on", "within", "pinned", "flowering", 
        "yale", "h", "see", "flowering", "e", "is", "probable", "historically", "microslide", "119", "possibly", 
        "849", "2844", "orig", "new", "aglaostigma", "petrified", "2654", "w", "sexupara", "1103", "3600", "ethonal", "23589", "ex", "xx", "microslides", 
        "p", "nomen", "new", "conspecific", "correspondence", "2013-09-24", "260", "2008-10-06", "unverified", "aphis", 
        "flowers", "xx", "fruit", "2518", "perdita", "2300", "556", "1183", "photo", "2013-09-25", "mixed"))) $typestatus = "";
        
        if(!$typestatus) {
            $occurrenceStatus = trim((string) $rec["http://rs.tdwg.org/dwc/terms/occurrenceStatus"]);
            foreach($types as $type) {
                if(is_numeric(stripos($occurrenceStatus, $type))) $typestatus = $type;
            }
        }
        
        if($typestatus == "Paralectotpye")  $typestatus = "PARALECTOTYPE";
        if($typestatus == "Paratye")        $typestatus = "PARATYPE";
        if($typestatus == "PARATOPOTYES")   $typestatus = "PARATOPOTYPE";
        if($typestatus == "Paraytpe")       $typestatus = "PARATYPE";
        if($typestatus == "Paraytpes")      $typestatus = "PARATYPE";
        if($typestatus == "Synytpe")        $typestatus = "SYNTYPE";
        if($typestatus == "SYNYTPES")       $typestatus = "SYNTYPE";
        if($typestatus == "Isotypus")       $typestatus = "ISOTYPE";
        if($typestatus == "co-type")        $typestatus = "COTYPE";
        if($typestatus == "Paraype")        $typestatus = "PARATYPE";
        if($typestatus == "Holotypus")      $typestatus = "HOLOTYPE";
        if($typestatus == "ISOTYPUS")       $typestatus = "ISOTYPE";
        if($typestatus == "ISOTYPA")        $typestatus = "ISOTYPE";
        if($typestatus == "Protoype")       $typestatus = "PROTOTYPE";
        if($typestatus == "SYNYTPE")        $typestatus = "SYNTYPE";
        if($typestatus == "TOPOPTYE")       $typestatus = "TOPOTYPE";
        
        $typestatus = strtoupper($typestatus);
        if($typestatus == "PARATYPES")      $typestatus = "PARATYPE";
        if($typestatus == "PARALECTOTYPES") $typestatus = "PARALECTOTYPE";

        /* working for stats
        if(isset($this->debug["typeStatus"][$typestatus])) $this->debug["typestatus"][$typestatus]++;
        else                                               $this->debug["typestatus"][$typestatus] = 1;
        */
        return $typestatus;
    }
    private function create_type_records_gbif($rec) // structured data
    {
        if(!$rec = self::valid_record($rec)) return;
        
        if(!$val = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"]) return;
        if($val = (string) $rec["http://rs.gbif.org/terms/1.0/gbifID"]) $rec["catnum"] = $val;
        else exit("\n no GBIF id \n");

        if($rec["country"] == "Sweden") {
            if($datasetKey = (string) $rec["http://rs.gbif.org/terms/1.0/datasetKey"]) {
                if(!$institutionCode_uri = self::get_uri($datasetKey, "datasetKey")) return;
                $institutionCode = self::get_contributor_name($datasetKey);
            }
        }
        else { //exit("\nelix 100\n");
            if(!$institutionCode = (string) $rec["http://rs.tdwg.org/dwc/terms/institutionCode"]) return;
            if(!$institutionCode_uri = self::get_institutionCode_uri($institutionCode, "institutionCode")) return;
            if($institutionCode_uri == "Exclude- literature dataset") return;
            // exit("\n[$institutionCode] [$institutionCode_uri]\n");
        }
        
        $rec["institutionCode"] = $institutionCode;

        if(!$typeStatus = (string) $rec["http://rs.tdwg.org/dwc/terms/typeStatus"]) return;
        if(!$typeStatus_uri = self::get_uri($typeStatus, "TypeInformation")) return;
        if($typeStatus_uri == "EXCLUDE") return;

        /* gbifID --- also, construct http://purl.org/dc/terms/source using this, eg:http://www.gbif.org/occurrence/1022646132 */
        if($val = (string) $rec["http://rs.gbif.org/terms/1.0/gbifID"]) {
            if(substr($val,0,4) == "http")  $rec["source"] = $val;
            else                            $rec["source"] = "http://www.gbif.org/occurrence/" . $val;
        }

        /* datasetKey --- use to construct http://purl.org/dc/terms/contributor, eg: http://www.gbif.org/dataset/85714c48-f762-11e1-a439-00145eb45e9a */
        if($val = (string) $rec["http://rs.gbif.org/terms/1.0/datasetKey"]) {
            $rec["contributor"] = self::get_contributor_name($val);
        }

        $tax_status = self::create_instances_from_taxon_object($rec);
        if($institutionCode_uri && $typeStatus_uri && $tax_status) {
            $parent_id = self::add_string_types($rec, $institutionCode_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", "true");
            self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus", 'child', $parent_id); //new
            // self::add_string_types($rec, $typeStatus_uri, "http://eol.org/schema/terms/TypeInformation"); // old but working
            if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificName"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName", 'child', $parent_id);
            
            // no standard column in occurrence --- added after the last force-harvest for Germany and France
            if($val = $rec["http://rs.tdwg.org/dwc/terms/verbatimDepth"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/verbatimDepth", 'child', $parent_id);
            if($val = $rec["http://rs.tdwg.org/dwc/terms/countryCode"])   self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/countryCode", 'child', $parent_id);
        }
    }
    private function valid_record($rec)
    {
        foreach(array_keys($rec) as $field) {
            $rec[$field] = Functions::import_decode($rec[$field]);
            if(!Functions::is_utf8($rec[$field])) {
                exit("\n not utf8 \n"); echo " " . $rec[$field];
                return false;
            }
        }
        return $rec;
    }
    private function get_uri($value, $field)
    {
        if(in_array($field, array("sex", "TypeInformation"))) $value = strtoupper($value);
        if($field == "sex") {
            if(in_array($value, array("MALE AND FEMALE", "MALE , FEMALE")))                             $value = "MALE AND FEMALE";
            elseif($value == "M")                                                                       $value = "MALE";
            elseif($value == "F")                                                                       $value = "FEMALE";
            elseif(in_array($value, array("1M", "MALE ?", "5M")))                                       $value = "MALE";
            elseif(in_array($value, array("2F", "1F")))                                                 $value = "FEMALE";
            elseif(in_array($value, array("UN", "U", "NOT RECORDED; NOT RECORDED")))                    $value = "UNKNOWN";
            elseif(in_array($value, array("2F, 1M", "7F, 2M", "2F, 75M", "2F, 4M", "42F, 30M")))        $value = "MALE AND FEMALE";
            elseif(is_numeric(stripos($value, " MALE")) && is_numeric(stripos($value, " FEMALE")))      $value = "MALE AND FEMALE";
            elseif(                                        is_numeric(stripos($value, "FEMALE")))       $value = "FEMALE";
            elseif(                                        is_numeric(stripos($value, " MALE")))        $value = "MALE";
            elseif(                                        is_numeric(stripos($value, "UNDETERMINED"))) $value = "UNDETERMINED";
            elseif(                                        is_numeric(stripos($value, "UNKNOWN")))      $value = "UNKNOWN";
            elseif(                                        is_numeric(stripos($value, "M;")))           $value = "MALE";
            elseif(                                        is_numeric(stripos($value, "F;")))           $value = "FEMALE";
        }
        if($val = @$this->uris[$value]) return $val;
        elseif($val = @$this->uris[strtolower($value)]) return $val;
        elseif($val = @$this->uris[strtoupper($value)]) return $val;
        else {
            if(in_array($field, array("TypeInformation"))) {
                $value = strtolower($value);
                if($value == '|syntype') $value = 'syntype';
                if($val = @$this->uris[$value]) return $val;
                else {
                    $this->debug["undefined"][$field][$value] = '';
                    return $value;
                }
            }
            else {
                $this->debug["undefined"][$field][$value] = '';
                if($field == "sex") return "";
                return $value;
            }
        }
    }


    private function get_institutionCode_uri($value, $field)
    {
        if($val = @$this->specific_mapping_for_this_resource[$value]) return $val;
        else {
            $this->debug["undefined"][$field][$value] = '';
            return $value;
        }
    }

    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "", $parent = false)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"];
        
        /* not generating DATA entries
        if    ($rec["dataset"] == "iDigBio")    $occurrence_id = $catnum;
        elseif($rec["dataset"] == "GBIF")       $occurrence_id = $catnum;
        else                                    $occurrence_id = $taxon_id . '_' . $catnum;
        */
        // $occurrence_id = md5($taxon_id . '_' . $catnum); //1st choice
        $occurrence_id = $catnum;

        $m = new \eol_schema\MeasurementOrFact();
        $to_MoF = array();
        // =====================
        if($measurementOfTaxon == "true") {
            $ret = $this->add_occurrence($taxon_id, $occurrence_id, $rec);
            $occurrence_id = $ret['oID'];
            $to_MoF = $ret['to_MoF'];
            
            $m->occurrenceID = $occurrence_id;
            $m->measurementOfTaxon = $measurementOfTaxon;

            $m->source              = $rec["source"];
            $m->contributor         = @$rec["contributor"];
            if($val = @$rec["http://rs.gbif.org/terms/1.0/datasetKey"]) //only for GBIF resources (not for iDigBio)
            {
                if($citation = @$this->citations[$val]) {
                    if($citation != "EXCLUDE") $m->bibliographicCitation = $citation;
                }
            }
            if($rec["dataset"] == "iDigBio") {
                if($referenceID = self::prepare_reference(trim((string) $rec["http://purl.org/dc/terms/references"]))) $m->referenceID = $referenceID;
            }
        }
        // =====================
        $m->measurementType = $measurementType;
        $m->measurementValue = Functions::import_decode($value);
        if($measurementOfTaxon == 'child') {
            /*
            Child record in MoF:
                - doesn't have: occurrenceID | measurementOfTaxon
                - has parentMeasurementID
                - has also a unique measurementID, as expected.
            Minimum columns on a child record in MoF:
                - measurementID 		    - measurementType
                - measurementValue		    - parentMeasurementID
            */
            $m->occurrenceID = "";
            $m->measurementOfTaxon = "";
            $m->parentMeasurementID = $parent;
        }
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $parent = $m->measurementID;
        $this->archive_builder->write_object_to_file($m);
        // return $m->measurementID; //moved below
        
        /* For child records - copied template from another resource
        $m = new \eol_schema\MeasurementOrFact_specific(); //NOTE: used a new class MeasurementOrFact_specific() for non-standard fields like 'm->label'
        $m->occurrenceID        = '';
        $m->measurementOfTaxon  = '';
        $m->measurementType     = 'https://eol.org/schema/terms/exemplary';
        $m->measurementValue    = $this->exemplary[$rec['label']];
        $m->parentMeasurementID = $parent;
        $m->measurementRemarks = $rec['label'];
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
        */
        
        // /* New: for DATA-1875: recoding unrecognized fields
        if($to_MoF) {
            foreach($to_MoF as $fld => $val) { //echo " -goes here 1- ";
                if($val) { //echo " -goes here 2- ";
                    
                    /*
                    Child record in MoF:
                        - doesn't have: occurrenceID | measurementOfTaxon
                        - has parentMeasurementID
                        - has also a unique measurementID, as expected.
                    Minimum columns on a child record in MoF:
                        - measurementID 		    - measurementType
                        - measurementValue		    - parentMeasurementID
                    */

                    /* initial eyeball saw these 3 predicates that should be child MoF records
                    http://rs.tdwg.org/dwc/terms/catalogNumber
                    http://rs.tdwg.org/dwc/terms/collectionCode
                    http://rs.tdwg.org/dwc/terms/institutionCode
                    */
                    $m2 = new \eol_schema\MeasurementOrFact();
                    $rek = array();
                    $rek['http://rs.tdwg.org/dwc/terms/occurrenceID'] = ''; //$occurrence_id;
                    $rek['http://eol.org/schema/measurementOfTaxon'] = '';
                    $rek['http://eol.org/schema/parentMeasurementID'] = $parent;
                    $rek['http://rs.tdwg.org/dwc/terms/measurementType'] = 'http://rs.tdwg.org/dwc/terms/'.pathinfo($fld, PATHINFO_BASENAME);
                    $rek['http://rs.tdwg.org/dwc/terms/measurementValue'] = $val;
                    $rek['http://rs.tdwg.org/dwc/terms/measurementID'] = md5(json_encode($rek)); //md5("$occurrence_id|$fld|$val");
                    $uris = array_keys($rek);
                    foreach($uris as $uri) { //echo " -goes here 3- ";
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $m2->$field = $rek[$uri];
                    }
                    if(!isset($this->mIDs[$m2->measurementID])) {
                        $this->archive_builder->write_object_to_file($m2);
                        $this->mIDs[$m2->measurementID] = '';
                    }
                }
            }
        }
        // */
        
        return $m->measurementID;
    }
    private function prepare_reference($citation)
    {
        if($citation) {
            $r = new \eol_schema\Reference();
            $r->full_reference = (string) $citation;
            $r->identifier = md5($r->full_reference);
            if(substr($citation, 0, 5) == "http:") $r->uri = $citation;
            if(!isset($this->resource_reference_ids[$r->identifier])) {
               $this->resource_reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
            return $r->identifier;
        }
    }
    private function add_occurrence($taxon_id, $occurrence_id, $rec)
    {
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        
        if($rec["dataset"] == "GBIF") {
            // /* temporarily commented
            /* move as rows in MoF with mOfTaxon = false
            $o->institutionCode     = $rec["institutionCode"];
            $o->catalogNumber       = $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"];
            $o->collectionCode      = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"];
            */
            $to_MoF['institutionCode'] = $rec['institutionCode'];
            $to_MoF['catalogNumber'] = $rec['http://rs.tdwg.org/dwc/terms/catalogNumber'];
            $to_MoF['collectionCode'] = $rec['http://rs.tdwg.org/dwc/terms/collectionCode'];
            
            $o->decimalLatitude     = $rec["http://rs.tdwg.org/dwc/terms/decimalLatitude"];
            $o->decimalLongitude    = $rec["http://rs.tdwg.org/dwc/terms/decimalLongitude"];
            $o->identifiedBy        = $rec["http://rs.tdwg.org/dwc/terms/identifiedBy"];
            $o->locality            = $rec["http://rs.tdwg.org/dwc/terms/locality"];
            $o->recordedBy          = $rec["http://rs.tdwg.org/dwc/terms/recordedBy"];
            $o->verbatimElevation   = $rec["http://rs.tdwg.org/dwc/terms/verbatimElevation"];
            $o->occurrenceRemarks   = $rec["http://rs.tdwg.org/dwc/terms/occurrenceRemarks"];
            // */
            
            // /*
            $sex = self::get_uri((string) $rec["http://rs.tdwg.org/dwc/terms/sex"], "sex");
            $o->sex = $sex == "http://eol.org/schema/terms/unknown" ? "" : $sex;
            // */

            $day = ""; $month = ""; $year = "";
            if($val = $rec["http://rs.tdwg.org/dwc/terms/eventDate"]) {
                if($val != "--") $o->eventDate = $val;
            }
            elseif($day = $rec["http://rs.tdwg.org/dwc/terms/day"] || $month = $rec["http://rs.tdwg.org/dwc/terms/month"] || $year = $rec["http://rs.tdwg.org/dwc/terms/year"]) {
                $o->eventDate = "";
                if($day != "--") $o->eventDate = $day;
                if($month != "--") $o->eventDate .= "-".$month;
                if($year != "--") $o->eventDate .= "-".$year;
            }
        }
        elseif($rec["dataset"] == "iDigBio") {
            $occurrenceID = trim((string) $rec[""]);
            //catalogNumber
            $catalogNumber = "";
            if($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"])) $catalogNumber .= $val;
            if($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/collectionCode"])) $catalogNumber .= " " . $val;
            $catalogNumber = trim($catalogNumber);
            //eventDate
            $eventDate = "";
            if    ($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/eventDate"])) $eventDate = $val;
            elseif($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/verbatimEventDate"])) $eventDate = $val;
            if($eventDate === 0 || is_numeric(stripos($eventDate, "unknown"))) $eventDate = "";
            
            $o->locality            = $rec["http://rs.tdwg.org/dwc/terms/locality"];
            $o->recordedBy          = $rec["http://rs.tdwg.org/dwc/terms/recordedBy"];
            $o->verbatimElevation   = $rec["http://rs.tdwg.org/dwc/terms/verbatimElevation"];
            $o->verbatimLatitude    = $rec["http://rs.tdwg.org/dwc/terms/verbatimLatitude"];
            $o->verbatimLongitude   = $rec["http://rs.tdwg.org/dwc/terms/verbatimLongitude"];
            $o->samplingProtocol    = $rec["http://rs.tdwg.org/dwc/terms/samplingProtocol"];
            $o->preparations        = $rec["http://rs.tdwg.org/dwc/terms/preparations"];

            $o->catalogNumber       = $catalogNumber;
            $o->collectionCode      = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"];
            $o->institutionCode     = $rec["http://rs.tdwg.org/dwc/terms/institutionCode"];

            // /* move as child rows in MoF with mOfTaxon = "" {blank}
            $to_MoF['institutionCode'] = $o->institutionCode;
            $to_MoF['catalogNumber'] = $o->catalogNumber;
            $to_MoF['collectionCode'] = $o->collectionCode;
            // */
            
            $o->individualCount     = $rec["http://rs.tdwg.org/dwc/terms/individualCount"];
            $o->decimalLongitude    = $rec["http://rs.tdwg.org/dwc/terms/decimalLongitude"];
            $o->decimalLatitude     = $rec["http://rs.tdwg.org/dwc/terms/decimalLatitude"];
            $o->eventDate           = $eventDate;
            
            $sex = trim((string) $rec["http://rs.tdwg.org/dwc/terms/sex"]);
            //some sex values are actually lifestage values
            $lifestage = false;
            if    (is_numeric(stripos($sex, "ADULT")))          $lifestage = "ADULT";
            elseif(is_numeric(stripos($sex, "EMBRYO")))         $lifestage = "EMBRYO";
            elseif(is_numeric(stripos($sex, "EGGS")))           $lifestage = "EGG";
            elseif(is_numeric(stripos($sex, "HATCHLING")))      $lifestage = "HATCHLING";
            elseif(is_numeric(stripos($sex, "COPULA")))         $lifestage = "ADULT"; //now defined as adult: https://eol-jira.bibalex.org/browse/DATA-1549?focusedCommentId=65758&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65758
            elseif($sex == "LARVAE")                            $lifestage = "LARVAE";
            elseif($sex == "SHELL")                             $lifestage = "EMBRYO IN SHELL";
            elseif($sex == "META-YOUNG")                        $lifestage = "YOUNG";
            elseif(in_array($sex, array("JUVENILE", "JUV")))    $lifestage = "JUVENILE";
            if($val = $lifestage) {
                // /*
                $lifeStage = self::get_uri($val, "lifeStage");
                $o->lifeStage = $lifeStage == "http://eol.org/schema/terms/unknown" ? "" : $lifeStage;
                // */
                
                // /* per https://eol-jira.bibalex.org/browse/DATA-1549?focusedCommentId=65758&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65758
                if(strtolower($o->lifeStage) == 'copula') $o->lifeStage = 'http://www.ebi.ac.uk/efo/EFO_0001272'; //adult
                // */
            }
            else {
                $sex = self::get_uri($sex, "sex");
                $o->sex = $sex == "http://eol.org/schema/terms/unknown" ? "" : $sex;
            }
            
            $o->identifiedBy                = $rec["http://rs.tdwg.org/dwc/terms/identifiedBy"];
            $o->reproductiveCondition       = $rec["http://rs.tdwg.org/dwc/terms/reproductiveCondition"];
        }

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        // return $o->occurrenceID; //orig
        return array('oID' => $o->occurrenceID, 'to_MoF' => $to_MoF); //due to DATA-1875: recoding unrecognized fields

        /* old ways
        $this->occurrence_ids[$occurrence_id] = '';
        return;
        */
    }
    public function get_contributor_name($datasetKey) //for GBIF only
    {
        $url = "http://www.gbif.org/dataset/".$datasetKey;
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            // <title property="dc:title">Herbarium Berolinense - Dataset detail</title>
            if(preg_match("/\"dc:title\">(.*?)\- Dataset detail/ims", $html, $arr)) {
                if(!Functions::is_utf8($arr[1])) exit("\n culprit is contributor name \n");
                return Functions::import_decode(trim($arr[1]));
            }
        }
    }
}
?>