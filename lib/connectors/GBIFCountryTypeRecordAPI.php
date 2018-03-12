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
        $this->spreadsheet_options = array('resource_id' => 'gbif', 'cache' => 0, 'timeout' => 3600, 'file_extension' => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2); //we don't want to cache spreadsheet
        // for iDigBio
        $this->download_options = array('resource_id' => 'gbif', 'download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 1, 'expire_seconds' => false); //60*60*24*365
        $this->IDB_service["record"] = "http://api.idigbio.org/v1/records/";
        $this->IDB_service["recordset"] = "http://api.idigbio.org/v1/recordsets/";
    }

    function export_gbif_to_eol($params)
    {
        $this->uris = self::get_uris($params, $params["uri_file"]);
        $params["uri_type"] = "citation";
        if($file = @$params["citation_file"]) $this->citations = self::get_uris($params, $file);
        
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($params["dwca_file"], "meta.xml", array("timeout" => 7200, "expire_seconds" => 0));
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $this->harvester = new ContentArchiveReader(NULL, $archive_path);
        if(!(@$this->harvester->tables["http://rs.tdwg.org/dwc/terms/occurrence"][0]->fields)) // take note the index key is all lower case
        {
            echo "\nInvalid archive file. Program will terminate.\n";
            return false;
        }

        if($params["dataset"] == "GBIF")
        {
            $params["row_type"] = "http://rs.tdwg.org/dwc/terms/occurrence";
            $params["location"] = "occurrence.txt";
            self::process_row_type($params);
        }
        elseif($params["dataset"] == "iDigBio")
        {
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
        if($params["dataset"] == "GBIF")
        {
            $fields["sex"] = "sex_uri";
            $fields["typeStatus"] = "typeStatus_uri";
            if(@$params["country"] == "Sweden") $fields["datasetKey"]      = "Type Specimen Repository URI"; //exception to the rule
            else                                $fields["institutionCode"] = "institutionCode_uri";          //rule case
            
            if(@$params["uri_type"] == "citation") // additional fields when processing citation spreadsheets
            {
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
        if($spreadsheet)
        {
            if($arr = $func->convert_spreadsheet($spreadsheet, 0, $spreadsheet_options))
            {
                 foreach($fields as $key => $value)
                 {
                     $i = 0;
                     if(@$arr[$key])
                     {
                         foreach($arr[$key] as $item)
                         {
                             $item = trim($item);
                             if($item)
                             {
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
        if(isset($this->harvester->tables[strtolower($row_type)]))
        {
            foreach($this->harvester->tables[strtolower($row_type)] as $table_definition)
            {
                if($table_definition->location != $location) continue;
                $this->harvester->file_iterator_index = 0;
                // rows are on newlines, so we can stream the file with an iterator
                if($table_definition->lines_terminated_by == "\n")
                {
                    $parameters['archive_table_definition'] =& $table_definition;
                    $i = 0;
                    foreach(new FileIterator($table_definition->file_uri) as $line_number => $line)
                    {
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
                        elseif($fields)
                        {
                            $fields = array_map('trim', $fields);
                            if(!trim((string) $fields["http://rs.tdwg.org/dwc/terms/scientificName"])) continue;
                            $fields["taxon_id"] = self::get_taxon_id($fields);
                            
                            if($params["dataset"] == "GBIF")
                            {
                                $fields["dataset"] = "GBIF";
                                $fields["country"] = $params["country"];
                                if($params["type"] == "structured data")                self::create_type_records_gbif($fields);
                                elseif($params["type"] == "classification resource")    self::create_classification_gbif($fields);
                            }
                            elseif($params["dataset"] == "iDigBio")
                            {
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
        $taxon->scientificName  = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
        $taxon->kingdom         = (string) $rec["http://rs.tdwg.org/dwc/terms/kingdom"];
        $taxon->phylum          = (string) $rec["http://rs.tdwg.org/dwc/terms/phylum"];
        $taxon->class           = (string) $rec["http://rs.tdwg.org/dwc/terms/class"];
        $taxon->order           = (string) $rec["http://rs.tdwg.org/dwc/terms/order"];
        $taxon->family          = (string) $rec["http://rs.tdwg.org/dwc/terms/family"];
        $taxon->genus           = (string) $rec["http://rs.tdwg.org/dwc/terms/genus"];
        $taxon->taxonRank       = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"];

        $taxon = self::check_sciname_ancestry_values($taxon);

        if(in_array($taxon->taxonRank, array("var.", "f.", "var"))) $taxon->taxonRank = "";
        if($taxon->scientificName || $taxon->genus || $taxon->family || $taxon->order || $taxon->class || $taxon->phylum || $taxon->kingdom)
        {
            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
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
    }

    private function check_sciname_ancestry_values($taxon)
    {    //scientificname should not be equal to any of the ancestry
        $canonical = Functions::canonical_form($taxon->scientificName);
        if($taxon->kingdom == $canonical)     $taxon->kingdom = '';
        if($taxon->phylum == $canonical)     $taxon->phylum = '';
        if($taxon->class == $canonical)     $taxon->class = '';
        if($taxon->order == $canonical)     $taxon->order = '';
        if($taxon->family == $canonical)     $taxon->family = '';
        if($taxon->genus == $canonical)     $taxon->genus = '';
        return $taxon;
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
        $taxon->scientificName  = $species;
        $taxon->kingdom         = (string) $rec["http://rs.tdwg.org/dwc/terms/kingdom"];
        $taxon->phylum          = (string) $rec["http://rs.tdwg.org/dwc/terms/phylum"];
        $taxon->class           = (string) $rec["http://rs.tdwg.org/dwc/terms/class"];
        $taxon->order           = (string) $rec["http://rs.tdwg.org/dwc/terms/order"];
        $taxon->family          = (string) $rec["http://rs.tdwg.org/dwc/terms/family"];
        $taxon->genus           = (string) $rec["http://rs.tdwg.org/dwc/terms/genus"];
        $taxon->taxonRank       = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"];

        $taxon = self::check_sciname_ancestry_values($taxon);

        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        
        //create the synonym
        $synonym_taxon_id = md5($sciname);
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $synonym_taxon_id;
        $taxon->scientificName  = $sciname;
        $taxon->taxonRank       = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"];
        $taxon->taxonomicStatus     = "synonym";
        $taxon->acceptedNameUsageID = $taxon_id;
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
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
        if(!$taxon_id)
        {
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
        if($html = Functions::lookup_with_cache($this->IDB_service["record"].$record_id, $this->download_options))
        {
            $json = json_decode($html);
            $recordset = (string) $json->{"idigbio:links"}->{"recordset"}[0];
            if($html = Functions::lookup_with_cache($recordset, $this->download_options))
            {
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

        if(count($rec) != 200) exit("\n count is not 200: " . count($rec));
        
        $rec["catnum"] = $rec[""];
        if(!$rec["catnum"])
        {
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
        foreach($institution_strings as $institution_string)
        {
            if(@$this->uris[$institution_string] == "EXCLUDE") return;
        }
        // end
        
        if($occurrenceID = (string) $rec[""]) $rec["source"] = "https://www.idigbio.org/portal/records/" . $occurrenceID;
        
        // start
        $institution_uri = self::get_uri($institution, "institution");
        $typestatus_uri = self::get_uri($typestatus, "TypeInformation");
        $rec["institutionCode"] = $institution;
        if($institution_uri && $typestatus_uri)
        {
            self::add_string_types($rec, $institution_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", "true");
            self::add_string_types($rec, $typestatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
            if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificName"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName");
            
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
            foreach($uris as $uri)
            {
                if($val = $rec[$uri]) self::add_string_types($rec, Functions::import_decode($val), $uri);
            }
            // */
            
            self::create_instances_from_taxon_object($rec);
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
        if((!$rightsHolder && !$ownerInstitutionCode) || (!$rightsHolder && (is_numeric(substr($datasetName,0,3)) || !$datasetName)))
        {
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
            $institution = self::get_institution_name($rec);
            echo "\n found institution_name1: [$institution] =====\n";

            if(!$institution) // 2nd option for institution value
            {
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
                foreach($institution_arr as $val)
                {
                    if(substr($val, 0, 4) != "urn:") $institution .= "($val) ";
                }
                $institution = trim($institution);
            }
            echo "\n found institution_name2: [$institution] =====\n";
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
        if($val = $ownerInstitutionCode)
        {
            if($final) $final .= " ($val)";
            else       $final .= "($val)";
        }
        if($val = $institution)
        {
            if(!is_numeric(substr($val,0,3)))
            {
                if($final) $final .= " - $val";
                else       $final .= "$val";
            }
        }
        $final = trim($final);
        if($datasetName && !is_numeric(stripos($final, $datasetName)))
        {
            if(!is_numeric(substr($datasetName,0,3)))
            {
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
        else
        {
            //e.g. [photo of isotype] [microslide of isotype] and the likes
            if(in_array(strtolower($temp[0]), array("photo", "photos", "photograph", "microslide", "microslides", "subculture", "subcult.", "fragment", "part", "drawing", "cultures", "indicated", "isolate", "subisolate", "photomicrographs", "compared", "part")) && 
               in_array(@$temp[1], array("of", "from", "as", "with"))) $typestatus = @$temp[2];
            //e.g. [possible paratype] [? holotype of f. ulmicola] and the likes
            elseif(in_array(strtolower($temp[0]), array("possible", "probable", "topotypic", "cultivar", "scudder's", "indicated", "probably", "prob.", "likely", "recurvispina", "?"))) $typestatus = @$temp[1];
            // original material - and combination of upper/lower cases
            elseif(strtolower($temp[0]) == "original" && strtolower(@$temp[1]) == "material") $typestatus = "ORIGINALMATERIAL";
            else
            {
                foreach($types as $type)
                {
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
        
        if(!$typestatus)
        {
            $occurrenceStatus = trim((string) $rec["http://rs.tdwg.org/dwc/terms/occurrenceStatus"]);
            foreach($types as $type)
            {
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

        if($rec["country"] == "Sweden")
        {
            if($datasetKey = (string) $rec["http://rs.gbif.org/terms/1.0/datasetKey"])
            {
                if(!$institutionCode_uri = self::get_uri($datasetKey, "datasetKey")) return;
                $institutionCode = self::get_contributor_name("http://www.gbif.org/dataset/" . $datasetKey);
            }
        }
        else
        {
            if(!$institutionCode = (string) $rec["http://rs.tdwg.org/dwc/terms/institutionCode"]) return;
            if(!$institutionCode_uri = self::get_uri($institutionCode, "institutionCode")) return;
            if($institutionCode_uri == "Exclude- literature dataset") return;
        }
        
        $rec["institutionCode"] = $institutionCode;

        if(!$typeStatus = (string) $rec["http://rs.tdwg.org/dwc/terms/typeStatus"]) return;
        if(!$typeStatus_uri = self::get_uri($typeStatus, "TypeInformation")) return;
        if($typeStatus_uri == "EXCLUDE") return;

        /* gbifID --- also, construct http://purl.org/dc/terms/source using this, eg:http://www.gbif.org/occurrence/1022646132 */
        if($val = (string) $rec["http://rs.gbif.org/terms/1.0/gbifID"])
        {
            if(substr($val,0,4) == "http")  $rec["source"] = $val;
            else                            $rec["source"] = "http://www.gbif.org/occurrence/" . $val;
        }

        /* datasetKey --- use to construct http://purl.org/dc/terms/contributor, eg: http://www.gbif.org/dataset/85714c48-f762-11e1-a439-00145eb45e9a */
        if($val = (string) $rec["http://rs.gbif.org/terms/1.0/datasetKey"])
        {
            $rec["contributor"] = self::get_contributor_name("http://www.gbif.org/dataset/" . $val);
        }

        if($institutionCode_uri && $typeStatus_uri)
        {
            self::add_string_types($rec, $institutionCode_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", "true");
            self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus"); //new
            // self::add_string_types($rec, $typeStatus_uri, "http://eol.org/schema/terms/TypeInformation"); // old but working
            if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificName"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName");
            
            // no standard column in occurrence --- added after the last force-harvest for Germany and France
            if($val = $rec["http://rs.tdwg.org/dwc/terms/verbatimDepth"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/verbatimDepth");
            if($val = $rec["http://rs.tdwg.org/dwc/terms/countryCode"])   self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/countryCode");                
            
            self::create_instances_from_taxon_object($rec);
        }
    }

    private function valid_record($rec)
    {
        foreach(array_keys($rec) as $field)
        {
            $rec[$field] = Functions::import_decode($rec[$field]);
            if(!Functions::is_utf8($rec[$field]))
            {
                exit("\n not utf8 \n"); echo " " . $rec[$field];
                return false;
            }
        }
        return $rec;
    }
    
    private function get_uri($value, $field)
    {
        if(in_array($field, array("sex", "TypeInformation"))) $value = strtoupper($value);
        if($field == "sex")
        {
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
        else
        {
            $this->debug["undefined"][$field][$value] = '';
            if($field == "sex") return "";
            return $value;
        }
    }

    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
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
        $this->add_occurrence($taxon_id, $occurrence_id, $rec);
        $m->occurrenceID = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        // =====================
        if($measurementOfTaxon == "true") {
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
        $this->archive_builder->write_object_to_file($m);
    }
    
    private function prepare_reference($citation)
    {
        if($citation)
        {
            $r = new \eol_schema\Reference();
            $r->full_reference = (string) $citation;
            $r->identifier = md5($r->full_reference);
            if(substr($citation, 0, 5) == "http:") $r->uri = $citation;
            if(!isset($this->resource_reference_ids[$r->identifier]))
            {
               $this->resource_reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
            return $r->identifier;
        }
    }

    private function add_occurrence($taxon_id, $occurrence_id, $rec)
    {
        if(isset($this->occurrence_ids[$occurrence_id])) return;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        
        if($rec["dataset"] == "GBIF")
        {
            // /* temporarily commented
            $o->institutionCode     = $rec["institutionCode"];
            $o->catalogNumber       = $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"];
            $o->collectionCode      = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"];
            $o->decimalLatitude     = $rec["http://rs.tdwg.org/dwc/terms/decimalLatitude"];
            $o->decimalLongitude    = $rec["http://rs.tdwg.org/dwc/terms/decimalLongitude"];
            $o->identifiedBy        = $rec["http://rs.tdwg.org/dwc/terms/identifiedBy"];
            $o->locality            = $rec["http://rs.tdwg.org/dwc/terms/locality"];
            $o->recordedBy          = $rec["http://rs.tdwg.org/dwc/terms/recordedBy"];
            $o->verbatimElevation   = $rec["http://rs.tdwg.org/dwc/terms/verbatimElevation"];
            $o->occurrenceRemarks   = $rec["http://rs.tdwg.org/dwc/terms/occurrenceRemarks"];
            // */
            $o->sex                 = self::get_uri((string) $rec["http://rs.tdwg.org/dwc/terms/sex"], "sex");

            $day = ""; $month = ""; $year = "";
            if($val = $rec["http://rs.tdwg.org/dwc/terms/eventDate"])
            {
                if($val != "--") $o->eventDate = $val;
            }
            elseif($day = $rec["http://rs.tdwg.org/dwc/terms/day"] || $month = $rec["http://rs.tdwg.org/dwc/terms/month"] || $year = $rec["http://rs.tdwg.org/dwc/terms/year"])
            {
                $o->eventDate = "";
                if($day != "--") $o->eventDate = $day;
                if($month != "--") $o->eventDate .= "-".$month;
                if($year != "--") $o->eventDate .= "-".$year;
            }
        }
        elseif($rec["dataset"] == "iDigBio")
        {
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
            elseif(is_numeric(stripos($sex, "COPULA")))         $lifestage = "COPULA"; //still undefined
            elseif($sex == "LARVAE")                            $lifestage = "LARVAE";
            elseif($sex == "SHELL")                             $lifestage = "EMBRYO IN SHELL";
            elseif($sex == "META-YOUNG")                        $lifestage = "YOUNG";
            elseif(in_array($sex, array("JUVENILE", "JUV")))    $lifestage = "JUVENILE";
            if($val = $lifestage)   $o->lifeStage = self::get_uri($val, "lifeStage");
            else                    $o->sex       = self::get_uri($sex, "sex");
            
            $o->identifiedBy                = $rec["http://rs.tdwg.org/dwc/terms/identifiedBy"];
            $o->reproductiveCondition       = $rec["http://rs.tdwg.org/dwc/terms/reproductiveCondition"];
        }

        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return;
    }

    private function get_contributor_name($url)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            // <title property="dc:title">Herbarium Berolinense - Dataset detail</title>
            if(preg_match("/\"dc:title\">(.*?)\- Dataset detail/ims", $html, $arr))
            {
                if(!Functions::is_utf8($arr[1])) exit("\n culprit is contributor name \n");
                return Functions::import_decode(trim($arr[1]));
            }
        }
    }

}
?>