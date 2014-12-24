<?php
namespace php_active_record;
/* connector: [872, 886, 887] GBIF country nodes (including iDigBio) - type records & classification resource */
class GBIFCountryTypeRecordAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->occurrence_ids = array();
        $this->gbifID_taxonID = array();
        $this->debug = array();
        
        // for iDigBio
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 1);
        $this->download_options["expire_seconds"] = false; // "expire_seconds" -- false => won't expire; 0 => expires now
        $this->IDB_service["record"] = "http://api.idigbio.org/v1/records/";
        $this->IDB_service["recordset"] = "http://api.idigbio.org/v1/recordsets/";
    }

    function export_gbif_to_eol($params)
    {
        $this->uris = self::get_uris($params);
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($params["dwca_file"], "meta.xml", array("timeout" => 7200, "expire_seconds" => 0)); // "expire_seconds" -- false => won't expire; 0 => expires now
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $this->harvester = new ContentArchiveReader(NULL, $archive_path);
        if(!(@$this->harvester->tables["http://rs.tdwg.org/dwc/terms/occurrence"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
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
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        print_r($this->debug);
    }

    private function get_uris($params)
    {
        $fields = array();
        if($params["dataset"] == "GBIF")
        {
            $fields["sex"] = "sex_uri";
            $fields["typeStatus"] = "typeStatus_uri";
            if($params["country"] == "Sweden") $fields["datasetKey"]      = "Type Specimen Repository URI"; //exception to the rule
            else                               $fields["institutionCode"] = "institutionCode_uri";          //rule case
        }
        elseif($params["dataset"] == "iDigBio")
        {
            $fields["institutionCode"] = "institutionCode_uri";
            $fields["sex"] = "sex_uri";
            $fields["typeStatus"] = "typeStatus_uri";
        }
        
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        $spreadsheet_options = array("cache" => 1, "timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2);
        $spreadsheet_options["expire_seconds"] = 0; // false => won't expire; 0 => expires now
        $uris = array();
        if($spreadsheet = @$params["uri_file"])
        {
            if($arr = $func->convert_spreadsheet($spreadsheet, 0, $spreadsheet_options))
            {
                 foreach($fields as $key => $value)
                 {
                     $i = 0;
                     foreach($arr[$key] as $item)
                     {
                         $item = trim($item);
                         if($item) $uris[$item] = $arr[$value][$i];
                         $i++;
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
                        $i++;
                        if(($i % 10000) == 0) echo "\n" . $params["type"] . " - $i ";
                        // echo "\n[$i] - ";
                        
                        /* breakdown when caching
                        $m = 200000;
                        $cont = false;
                        // if($i >=  1    && $i < $m)    $cont = true;
                        // if($i >=  $m   && $i < $m*2)  $cont = true;
                        // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                        // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                        // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                        
                        // if($i >= 1  && $i < 500000)  $cont = true;
                        // if($i >= 500000  && $i < 1000000)  $cont = true;
                        
                        if(!$cont) continue;
                        */
                        
                        $parameters['archive_line_number'] = $line_number;
                        if($table_definition->ignore_header_lines) $parameters['archive_line_number'] += 1;
                        $fields = $this->harvester->parse_table_row($table_definition, $line, $parameters);
                        if($fields == "this is the break message") break;
                        if($fields && $callback) call_user_func($callback, $fields, $parameters);
                        elseif($fields)
                        {
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
                        // if($i >= 100) break; //debug - used during preview mode
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
        $taxon_id   = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
        $sciname    = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
        if(!$taxon_id) $taxon_id = md5($sciname);
        return $taxon_id;
    }

    private function get_institution_name($rec)
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
        if($this->uris[$institution] == "EXCLUDE") return;
        
        if($occurrenceID = (string) $rec[""]) $rec["source"] = "https://www.idigbio.org/portal/records/" . $occurrenceID;
        
        // start
        $institution_uri = self::get_uri($institution, "institution");
        $typestatus_uri = self::get_uri($typestatus, "TypeInformation");
        if($institution_uri && $typestatus_uri)
        {
            self::add_string_types($rec, $institution_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", "true");
            self::add_string_types($rec, $institution, "http://rs.tdwg.org/dwc/terms/institutionCode");
            self::add_string_types($rec, $typestatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
            if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificName"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName");
            self::create_instances_from_taxon_object($rec);
        }
    }
    
    private function get_institution($rec)
    {
        $rightsHolder           = trim((string) $rec["http://purl.org/dc/terms/rightsHolder"]);
        $ownerInstitutionCode   = trim((string) $rec["http://rs.tdwg.org/dwc/terms/ownerInstitutionCode"]);
        $datasetName            = trim((string) $rec["http://rs.tdwg.org/dwc/terms/datasetName"]);

        if(is_numeric(substr($rightsHolder,0,2))) $rightsHolder = "";
        if(is_numeric(substr($ownerInstitutionCode,0,2))) $ownerInstitutionCode = "";
        if(is_numeric(substr($datasetName,0,2))) $datasetName = "";

        $institution = '';
        if((!$rightsHolder && !$ownerInstitutionCode) || (!$rightsHolder && (is_numeric(substr($datasetName,0,3)) || !$datasetName))) $institution = self::get_institution_name($rec);
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
        if(isset($this->debug["final"][$final])) $this->debug["final"][$final]++;
        else                                     $this->debug["final"][$final] = 1;

        /* for stats
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
        if(!$val = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"]) return;
        $rec["catnum"] = (string) $rec["http://rs.gbif.org/terms/1.0/gbifID"]; //am back using just plain gbifID

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
            // echo "\ncontributor: " . $rec["contributor"] . "\n";
        }

        if($institutionCode_uri && $typeStatus_uri)
        {
            self::add_string_types($rec, $institutionCode_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", "true");
            self::add_string_types($rec, $institutionCode, "http://rs.tdwg.org/dwc/terms/institutionCode");
            self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
            if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificName"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName");
            self::create_instances_from_taxon_object($rec);
        }
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
        $catnum = $rec["catnum"];
        
        if    ($rec["dataset"] == "iDigBio")    $occurrence_id = $catnum;
        elseif($rec["dataset"] == "GBIF")       $occurrence_id = $catnum;
        else                                    $occurrence_id = $taxon_id . '_' . $catnum; // this is not being used at the moment
        
        $m = new \eol_schema\MeasurementOrFact();
        $this->add_occurrence($taxon_id, $occurrence_id, $rec);
        $m->occurrenceID = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        if($measurementOfTaxon ==  "true")
        {   // so that measurementRemarks (and source, contributor, etc.) appears only once in the [measurement_or_fact.tab]
            $m->measurementRemarks = '';
            $m->source = $rec["source"];
            $m->contributor = @$rec["contributor"];
            /* not used at the moment
            if($referenceID = self::prepare_reference((string) $rec["http://eol.org/schema/reference/referenceID"]))
            {
                $m->referenceID = $referenceID;
            }
            */
        }
        $m->measurementType = $measurementType;
        $m->measurementValue = (string) $value;
        $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $occurrence_id, $rec)
    {
        if(isset($this->occurrence_ids[$occurrence_id])) return;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        
        if($rec["dataset"] == "GBIF")
        {
            $o->catalogNumber = $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"];
            $o->collectionCode = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"];
            $o->decimalLatitude = $rec["http://rs.tdwg.org/dwc/terms/decimalLatitude"];
            $o->decimalLongitude = $rec["http://rs.tdwg.org/dwc/terms/decimalLongitude"];
            $o->identifiedBy = $rec["http://rs.tdwg.org/dwc/terms/identifiedBy"];
            $o->locality = $rec["http://rs.tdwg.org/dwc/terms/locality"];
            $o->recordedBy = $rec["http://rs.tdwg.org/dwc/terms/recordedBy"];
            $o->sex = self::get_uri((string) $rec["http://rs.tdwg.org/dwc/terms/sex"], "sex");
            $o->verbatimElevation = $rec["http://rs.tdwg.org/dwc/terms/verbatimElevation"];
            // no standard column
            // $o->verbatimDepth = $rec["http://rs.tdwg.org/dwc/terms/verbatimDepth"];
            // $o->countryCode = $rec["http://rs.tdwg.org/dwc/terms/countryCode"];
            $o->occurrenceRemarks = $rec["http://rs.tdwg.org/dwc/terms/occurrenceRemarks"];
            $o->eventDate = $rec["http://rs.tdwg.org/dwc/terms/eventDate"];
            if(!$o->eventDate) $o->eventDate =  $rec["http://rs.tdwg.org/dwc/terms/day"] . "-" . $rec["http://rs.tdwg.org/dwc/terms/month"] . "-" . $rec["http://rs.tdwg.org/dwc/terms/year"];
        }
        elseif($rec["dataset"] == "iDigBio")
        {
            $occurrenceID = trim((string) $rec[""]);
            //catalogNumber
            if($val = $occurrenceID) $catalogNumber = $val;
            else
            {
                $catalogNumber = "";
                if($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"])) $catalogNumber .= $val;
                if($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/collectionCode"])) $catalogNumber .= " " . $val;
                $catalogNumber = trim($catalogNumber);
            }
            //eventDate
            $eventDate = "";
            if    ($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/eventDate"])) $eventDate = $val;
            elseif($val = trim((string) $rec["http://rs.tdwg.org/dwc/terms/verbatimEventDate"])) $eventDate = $val;
            if($eventDate == 0 || is_numeric(stripos($eventDate, "unknown"))) $eventDate = "";
            
            $o->occurrenceID                = $rec["http://rs.tdwg.org/dwc/terms/occurrenceID"];
            $o->locality                    = $rec["http://rs.tdwg.org/dwc/terms/locality"];
            $o->recordedBy                  = $rec["http://rs.tdwg.org/dwc/terms/recordedBy"];
            $o->verbatimElevation           = $rec["http://rs.tdwg.org/dwc/terms/verbatimElevation"];
            $o->verbatimLatitude            = $rec["http://rs.tdwg.org/dwc/terms/verbatimLatitude"];
            $o->verbatimLongitude           = $rec["http://rs.tdwg.org/dwc/terms/verbatimLongitude"];
            $o->samplingProtocol            = $rec["http://rs.tdwg.org/dwc/terms/samplingProtocol"];
            $o->decimalLatitude             = $rec["http://rs.tdwg.org/dwc/terms/decimalLatitude"];
            $o->preparations                = $rec["http://rs.tdwg.org/dwc/terms/preparations"];
            $o->catalogNumber               = $catalogNumber;
            $o->individualCount             = $rec["http://rs.tdwg.org/dwc/terms/individualCount"];
            $o->decimalLongitude            = $rec["http://rs.tdwg.org/dwc/terms/decimalLongitude"];
            $o->eventDate                   = $eventDate;
            $o->sex                         = self::get_uri((string) $rec["http://rs.tdwg.org/dwc/terms/sex"], "sex");
            $o->identifiedBy                = $rec["http://rs.tdwg.org/dwc/terms/identifiedBy"];
            $o->reproductiveCondition       = $rec["http://rs.tdwg.org/dwc/terms/reproductiveCondition"];

            /*
            //not a standard element in occurrence, but in the XLS specs from Jen
            $o->county                      = $rec["http://rs.tdwg.org/dwc/terms/county"];
            $o->country                     = $rec["http://rs.tdwg.org/dwc/terms/country"];
            $o->verbatimDepth               = $rec["http://rs.tdwg.org/dwc/terms/verbatimDepth"];
            $o->verbatimCoordinates         = $rec["http://rs.tdwg.org/dwc/terms/verbatimCoordinates"];
            $o->maximumElevationInMeters    = $rec["http://rs.tdwg.org/dwc/terms/maximumElevationInMeters"];
            $o->waterBody                   = $rec["http://rs.tdwg.org/dwc/terms/waterBody"];
            $o->island                      = $rec["http://rs.tdwg.org/dwc/terms/island"];
            $o->islandGroup                 = $rec["http://rs.tdwg.org/dwc/terms/islandGroup"];
            $o->maximumDepthInMeters        = $rec["http://rs.tdwg.org/dwc/terms/maximumDepthInMeters"];
            $o->minimumDepthInMeters        = $rec["http://rs.tdwg.org/dwc/terms/minimumDepthInMeters"];
            $o->minimumElevationInMeters    = $rec["http://rs.tdwg.org/dwc/terms/minimumElevationInMeters"];
            $o->georeferenceRemarks         = $rec["http://rs.tdwg.org/dwc/terms/georeferenceRemarks"];
            $o->higherGeography             = $rec["http://rs.tdwg.org/dwc/terms/higherGeography"];
            $o->stateProvince               = $rec["http://rs.tdwg.org/dwc/terms/stateProvince"];
            $o->continent                   = $rec["http://rs.tdwg.org/dwc/terms/continent"];
            $o->latestEraOrHighestErathem   = $rec["http://rs.tdwg.org/dwc/terms/latestEraOrHighestErathem"];
            */
        }

        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return;
    }

    private function get_contributor_name($url)
    {
        $options = array('expire_seconds' => false, 'download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 2, 'delay_in_minutes' => 2); // 15mins timeout
        if($html = Functions::lookup_with_cache($url, $options))
        {
            // <title property="dc:title">Herbarium Berolinense - Dataset detail</title>
            if(preg_match("/\"dc:title\">(.*?)\- Dataset detail/ims", $html, $arr)) return trim($arr[1]);
        }
    }

}
?>