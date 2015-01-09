<?php
namespace php_active_record;
/* connector: [891] NMNH type records */
class NMNHTypeRecordAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->occurrence_ids = array();
        $this->debug = array();
    }

    function export_gbif_to_eol($params)
    {
        $this->uris = self::get_uris($params);
        // print_r($this->uris);
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($params["dwca_file"], "meta.xml", array("timeout" => 7200, "expire_seconds" => false)); // "expire_seconds" -- false => won't expire; 0 => expires now
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $this->harvester = new ContentArchiveReader(NULL, $archive_path);
        if(!(@$this->harvester->tables["http://rs.tdwg.org/dwc/terms/occurrence"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        if($params["dataset"] == "NMNH")
        {
            $params["row_type"] = "http://rs.tdwg.org/dwc/terms/occurrence";
            $params["location"] = "occurrence.txt";
            self::process_row_type($params);
        }
        elseif($params["dataset"] == "iDigBio") {}

        $this->archive_builder->finalize(TRUE);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        print_r($this->debug);
    }

    private function get_uris($params)
    {
        $fields = array();
        if($params["dataset"] == "NMNH")
        {
            $fields["institutionCode"]  = "institutionCode_uri";
            $fields["sex"]              = "sex_uri";
            $fields["typeStatus"]       = "typeStatus_uri";
            $fields["lifeStage"]        = "lifeStage_uri";
            $fields["collectionCode"]   = "collectionCode_uri";
        }
        elseif($params["dataset"] == "iDigBio") {}
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
                        
                        /* breakdown when caching
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
                            if(!self::valid_typestatus($fields["http://rs.tdwg.org/dwc/terms/typeStatus"], $fields["http://rs.tdwg.org/dwc/terms/scientificName"])) continue;
                            $fields["taxon_id"] = self::get_taxon_id($fields);
                            if($params["dataset"] == "NMNH")
                            {
                                $fields["dataset"] = "NMNH";
                                if($params["type"] == "structured data")                self::create_type_records_nmnh($fields);
                                elseif($params["type"] == "classification resource")    self::create_classification_gbif($fields);
                            }
                            elseif($params["dataset"] == "iDigBio") {}
                            // old ways: elseif($row_type == "http://rs.gbif.org/terms/1.0/Multimedia") self::get_media_objects($fields);
                        }
                        // if($i >= 100) break; //debug - used during preview mode
                        // if($i >= 10000) break; //debug - used during preview mode
                    }
                }
                // otherwise we need to load the entire file into memory and split it
                else exit("\n -does not go here- \n");
            }
        }
    }

    private function process_multiple_typestatuses($string, $sciname)
    {
        $arr = array();
        $separators = array(";", "+", ",");
        foreach($separators as $separator)
        {
            $temp = array_map('trim', explode($separator, $string));
            $arr = array_merge($arr, $temp);
        }
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        //remove array values with separator chars
        $i = 0;
        foreach($arr as $value)
        {
            if(self::valid_typestatus($value, $sciname))
            {
                foreach($separators as $separator)
                {
                    if(is_numeric(stripos($value, $separator))) $arr[$i] = null;
                }
            }
            else $arr[$i] = null;
            $i++;
        }
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        $URIs = array();
        foreach($arr as $typeStatus) $URIs[] = self::get_uri($typeStatus, "typeStatus");
        return $URIs;
    }
    
    private function valid_typestatus($typestatus, $sciname)
    {
        if(!$typestatus = trim(strtolower($typestatus))) return false;
        $exclude = array("nomen nudem", "not a type", "voucher", "ms");
        if(in_array($typestatus, $exclude)) return false;
        //ms *type (e.g., MS Holotype, MS Lectotype, MS Paralectotype, MS Paratype)
        if(substr($typestatus,0,3) == "ms " && !self::string_with_separator($typestatus)) return false;
        // sciname must not be blank
        if(!$sciname) return false;
        //The ScientificName is of the form “Genus sp.”, e.g., Alnus sp. - Note: names like Alnus maritima subsp. oklahomensis or Carex nutans var. japonica are fine, so you should not skip all records with . in them.
        if(strtolower(substr($sciname, -4)) == " sp.") return false;
        return true;
    }
    
    private function string_with_separator($string)
    {
        $separators = array(";", "+", ",");
        foreach($separators as $separator)
        {
            if(is_numeric(stripos($string, $separator))) return true;
        }
        return false;
    }

    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec["taxon_id"];
        $taxon->scientificName  = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
        if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"]) $taxon->scientificName .= " " . $val;
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
    }

    private function get_taxon_id($rec)
    {
        $taxon_id = "";
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/kingdom"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/phylum"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/class"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/order"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/family"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/genus"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/subgenus"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/specificEpithet"];
        $taxon_id .= (string) $rec["http://rs.tdwg.org/dwc/terms/infraspecificEpithet"];
        return md5($taxon_id);
    }

    private function create_type_records_nmnh($rec) // structured data
    {
        $rec["catnum"] = $rec[""];
        //source
        $collectionCode = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"]; // e.g. Invertebrate Zoology
        $coll1 = array("Amphibians & Reptiles" => "herps", "Birds" => "birds", "Botany" => "botany", "Fishes" => "fishes", "Mammals" => "mammals", "Paleobiology" => "paleo");
        $coll2 = array("Entomology" => "ento", "Invertebrate Zoology" => "iz");
        $colls = array_merge($coll1, $coll2);
        if(in_array($collectionCode, array_keys($colls)))
        {
            if(in_array($collectionCode, array_keys($coll1)))
            {
                $catalogNumber = $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"]; // e.g. 537358.11032345
                $temp = explode(".", $catalogNumber);
                $id = strtolower($temp[0]);
                $rec["source"] = "http://collections.mnh.si.edu/search/" . $coll1[$collectionCode] . "/?nb=" . $id;
            }
            elseif(in_array($collectionCode, array_keys($coll2))) $rec["source"] = "http://collections.mnh.si.edu/search/" . $coll2[$collectionCode] . "/?qt=" . str_replace(" ", "+", $rec["http://rs.tdwg.org/dwc/terms/scientificName"]);
        }
        else
        {
            print_r($rec);
            echo "\nundefined collectionCode [$collectionCode]\n"; exit;
        }
        
        $institutionCode_uri = "http://biocol.org/urn:lsid:biocol.org:col:34871";
        $typeStatus_uri = false;
        $typeStatus_uri_arr = false;
        $typeStatus = $rec["http://rs.tdwg.org/dwc/terms/typeStatus"];
        if(!self::string_with_separator($typeStatus)) $typeStatus_uri = self::get_uri($typeStatus, "typeStatus");
        else                                          $typeStatus_uri_arr = self::process_multiple_typestatuses($typeStatus, $rec["http://rs.tdwg.org/dwc/terms/scientificName"]);
        
        if($institutionCode_uri && ($typeStatus_uri || $typeStatus_uri_arr))
        {
            self::add_string_types($rec, $institutionCode_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", "true");
            if($typeStatus_uri_arr)
            {
                foreach($typeStatus_uri_arr as $typeStatus_uri) self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
            }
            else self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
            
            if($val = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"]) self::add_string_types($rec, self::get_uri($val, "collectionCode"), "http://rs.tdwg.org/dwc/terms/collectionCode");

            $associatedSequences = $rec["http://rs.tdwg.org/dwc/terms/associatedSequences"];
            if($associatedSequences && $associatedSequences != "Genbank:") self::add_string_types($rec, $associatedSequences, "http://rs.tdwg.org/dwc/terms/associatedSequences");

            $fields = array("http://rs.tdwg.org/dwc/terms/recordNumber", "http://rs.tdwg.org/dwc/terms/otherCatalogNumbers", "http://rs.tdwg.org/dwc/terms/associatedMedia", "http://rs.tdwg.org/dwc/terms/startDayOfYear", "http://rs.tdwg.org/dwc/terms/endDayOfYear", "http://rs.tdwg.org/dwc/terms/year", "http://rs.tdwg.org/dwc/terms/month", "http://rs.tdwg.org/dwc/terms/day", "http://rs.tdwg.org/dwc/terms/verbatimEventDate", "http://rs.tdwg.org/dwc/terms/fieldNumber", "http://rs.tdwg.org/dwc/terms/higherGeography", "http://rs.tdwg.org/dwc/terms/continent", "http://rs.tdwg.org/dwc/terms/waterBody", "http://rs.tdwg.org/dwc/terms/islandGroup", "http://rs.tdwg.org/dwc/terms/island", "http://rs.tdwg.org/dwc/terms/country", "http://rs.tdwg.org/dwc/terms/stateProvince", "http://rs.tdwg.org/dwc/terms/county", "http://rs.tdwg.org/dwc/terms/minimumElevationInMeters", "http://rs.tdwg.org/dwc/terms/maximumElevationInMeters", "http://rs.tdwg.org/dwc/terms/verbatimDepth", "http://rs.tdwg.org/dwc/terms/minimumDepthInMeters", "http://rs.tdwg.org/dwc/terms/maximumDepthInMeters", "http://rs.tdwg.org/dwc/terms/verbatimCoordinateSystem", "http://rs.tdwg.org/dwc/terms/geodeticDatum", "http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters", "http://rs.tdwg.org/dwc/terms/georeferenceProtocol", "http://rs.tdwg.org/dwc/terms/georeferenceRemarks", "http://rs.tdwg.org/dwc/terms/identificationQualifier");
            foreach($fields as $field)
            {
                if($val = $rec[$field]) self::add_string_types($rec, $val, $field);
            }

            self::create_instances_from_taxon_object($rec);
        }
    }

    private function get_uri($value, $field)
    {
        if(in_array($field, array("sex", "typeStatus"))) $value = strtoupper($value);

        if($field == "typeStatus")
        {
            $value = str_ireplace("TYPES", "TYPE", $value);
            $value = str_ireplace("PROBABLE", "POSSIBLE", $value);
            $value = str_ireplace("POSSIBLE COTYPE (FIDE M. R. BROWNING)", "POSSIBLE COTYPE", $value);
            $value = str_ireplace("NEOTYPE COLLECTION", "NEOTYPE", $value);
        }

        if($field == "sex")
        {
            //remove "s"
            $value = str_ireplace("MALES", "MALE", $value);
            $value = str_ireplace("HERMAPHRODITES", "HERMAPHRODITE", $value);
            
            //manual adjustment
            if($value == 0 || $value == "0") $value = ""; //ignore
            
            //various case statements
            if(is_numeric(stripos($value, "?"))) {} // use verbatim value
            elseif(self::has_male($value) && self::has_female($value) && !self::has_hermaphrodite($value))  $value = "MALE_FEMALE";
            elseif(self::has_male($value) && self::has_female($value) && self::has_hermaphrodite($value))   $value = "MALE_FEMALE_HERMAPHRODITE";
            elseif(self::has_male($value) && !self::has_female($value) && self::has_hermaphrodite($value))  $value = "MALE_HERMAPHRODITE";
            elseif(!self::has_male($value) && self::has_female($value) && self::has_hermaphrodite($value))  $value = "FEMALE_HERMAPHRODITE";
            elseif(self::has_male($value) && !self::has_female($value) && !self::has_hermaphrodite($value)) $value = "MALE";
            elseif(!self::has_male($value) && self::has_female($value) && !self::has_hermaphrodite($value)) $value = "FEMALE";
            elseif(!self::has_male($value) && !self::has_female($value) && self::has_hermaphrodite($value)) $value = "HERMAPHRODITE";
            elseif(self::has_unknown($value))
            {
                if(    self::has_male($value) && self::has_female($value) && !self::has_hermaphrodite($value))  $value = "MALE_FEMALE";
                elseif(self::has_male($value) && self::has_female($value) && self::has_hermaphrodite($value))   $value = "MALE_FEMALE_HERMAPHRODITE";
                elseif(self::has_male($value) && !self::has_female($value) && self::has_hermaphrodite($value))  $value = "MALE_HERMAPHRODITE";
                elseif(!self::has_male($value) && self::has_female($value) && self::has_hermaphrodite($value))  $value = "FEMALE_HERMAPHRODITE";
                elseif(self::has_male($value) && !self::has_female($value) && !self::has_hermaphrodite($value)) $value = "MALE";
                elseif(!self::has_male($value) && self::has_female($value) && !self::has_hermaphrodite($value)) $value = "FEMALE";
                elseif(!self::has_male($value) && !self::has_female($value) && self::has_hermaphrodite($value)) $value = "HERMAPHRODITE";
                else {} // use verbatim value
            }
            else {} // use verbatim value
        }
        
        if($field == "lifeStage")
        {
            $value = str_replace(";;", ";", $value);
            $value = str_replace(";;", ";", $value);
            $value = str_ireplace("JUVENILES", "JUVENILE", $value);

            if(in_array($value, array("ADULT;", "; ADULT", "ADULT; ADULT", "BRANCHIATE ADULT", "ADULT; WINGS UNKNOWN")))    $value = "ADULT";
            elseif(in_array($value, array("PUPA;", "PUPAL EXUVIA")))                                                        $value = "PUPA";
            elseif(in_array($value, array("; JUVENILE; JUVENILE; EMBRYO", "; I; OVIGEROUS", "JUVENILE, EGG")))              $value = "JUVENILE & OVIGEROUS";
            elseif(in_array($value, array("; LARVAE", "; LARVAE V", "; LARVAE;", "LARVA; LARVA")))                          $value = "LARVA";
            elseif(in_array($value, array("; COPEPODID", "COPEPODID IV; COPEPODID V")))                                     $value = "COPEPODID";
            elseif(in_array($value, array("; JUVENILE;", "JUVENILE;", "; JUVENILE")))                                       $value = "JUVENILE";
            elseif(in_array($value, array("; OVIGEROUS", "OVIGEROUS;", "; OVIGEROUS;", "; OVIGEROUS; OVIGEROUS")))          $value = "OVIGEROUS";
            elseif(in_array($value, array("PREMATURE", "; PREMATURE", "; IMMATURE")))                                       $value = "IMMATURE"; // by Eli
            elseif(in_array($value, array("1 ADULT, 1 LARVA", "ADULT WITH 2 EGGS")))                                        $value = "ADULT & LARVA";
            elseif($value == "; OVIGEROUS; JUVENILE V")                 $value = "JUVENILE V & OVIGEROUS";
            elseif($value == "; EMBRYO")                                $value = "EMBRYO";
            elseif($value == "; PRANIZA")                               $value = "PRANIZA";
            elseif($value == "ADULT; JUVENILE")                         $value = "ADULT & JUVENILE";
            elseif($value == "PUPA; PUPA; ADULT")                       $value = "ADULT & PUPA";
            elseif($value == "SUBADULT")                                $value = "ADULT & SUBADULT";    // by Eli
            elseif($value == "NEARLY ADULT")                            $value = "YOUNG ADULT";         // by Eli
            elseif($value == "ADULT, JUVS(2), LARVAE(6)")               $value = "ADULT & LARVA";       // by Eli
            elseif($value == "ADULT; EGG")                              $value = "ADULT & LARVA";       // by Eli
            elseif($value == "FLOWERING AND IMMATURE FRUIT")            $value = "IMMATURE FRUIT";
            elseif($value == "; MANCA")                                 $value = "MANCA";
            elseif(is_numeric(stripos($value, "ADULT; EXUVIAE")))       $value = "ADULT";               // by Eli
            elseif(is_numeric(stripos($value, "ADULT; SUBADULT")))      $value = "ADULT & SUBADULT";
            elseif(is_numeric(stripos($value, "ADULT; NYMPH")))         $value = "ADULT & NYMPH";
            elseif(is_numeric(stripos($value, "JUVENILE A")))           $value = "JUVENILE";
            elseif(is_numeric(stripos($value, "JUVENILE V")))           $value = "JUVENILE";
            elseif(is_numeric(stripos($value, "JUVENILE I")))           $value = "JUVENILE";
            elseif(is_numeric(stripos($value, "LARVA; PUPA")))          $value = "LARVA & PUPA";
            elseif(is_numeric(stripos($value, "ADULT; LARVA")))         $value = "ADULT & LARVA";
            elseif(is_numeric(stripos($value, "JUVENILE; OVIGEROUS")))  $value = "JUVENILE & OVIGEROUS";
            elseif(is_numeric(stripos($value, "OVIGEROUS; JUVENILE")))  $value = "JUVENILE & OVIGEROUS";
            elseif(is_numeric(stripos($value, "NYMPH; NYMPH")))         $value = "NYMPH";
            elseif(is_numeric(stripos($value, "A-")))                   $value = "JUVENILE";
            elseif(is_numeric(stripos($value, "J-")))                   $value = "JUVENILE";
            elseif(is_numeric(stripos($value, "I; JUVENILE")))          $value = "JUVENILE";
            elseif(is_numeric(stripos($value, "I; JUVENILE")))          $value = "JUVENILE";
            elseif(is_numeric(stripos($value, "COPEPODID III")))        $value = "COPEPODID III";
            elseif(is_numeric(stripos($value, "ADULT; ADULT; ADULT")))  $value = "ADULT";
            elseif(is_numeric(stripos($value, "OVIGEROUS; IMMATURE")))  $value = "IMMATURE & OVIGEROUS";
        }

        if($val = @$this->uris[$value]) return $val;
        else
        {
            $this->debug["undefined"][$field][$value] = '';
            return $value;
        }
    }
    
    private function has_male($sex)
    {
        if(substr($sex,0,4) == "MALE"           ||
           is_numeric(stripos($sex, " MALE"))   ||
           is_numeric(stripos($sex, ",MALE"))   ||
           is_numeric(stripos($sex, ";MALE"))   ||
           is_numeric(stripos($sex, "+MALE"))) return true;
        return false;
    }

    private function has_female($sex)
    {
        if(is_numeric(stripos($sex, "female"))) return true;
        return false;
    }

    private function has_hermaphrodite($sex)
    {
        if(is_numeric(stripos($sex, "HERMAPHRODITE")) || is_numeric(stripos($sex, "INTERSEX"))) return true;
        return false;
    }

    private function has_unknown($sex)
    {
        if(is_numeric(stripos($sex, "UNKNOWN"))   ||
           is_numeric(stripos($sex, "UNCERTAIN"))) return true;
        return false;
    }

    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        
        if($rec["dataset"] == "NMNH") $occurrence_id = $catnum;
        else                          $occurrence_id = $taxon_id . '_' . $catnum;
        
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
        
        $o->institutionCode     = "USNM";
        $o->collectionCode      = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"]; //verbatim here
        $o->catalogNumber       = $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"];
        $o->occurrenceRemarks   = $rec["http://rs.tdwg.org/dwc/terms/occurrenceRemarks"]; //careful: there are quotes, commas, semicolons in this field.
        $o->recordedBy          = $rec["http://rs.tdwg.org/dwc/terms/recordedBy"];
        $o->individualCount     = $rec["http://rs.tdwg.org/dwc/terms/individualCount"];
        if($val = self::get_uri((string) $rec["http://rs.tdwg.org/dwc/terms/sex"], "sex")) $o->sex = $val;
        //lifestage
        $lifeStage = strtoupper((string) $rec["http://rs.tdwg.org/dwc/terms/lifeStage"]);
        if(!in_array($lifeStage, array("LIFESTAGE", "RESEARCH", "STERILE", "TOP; BOTTOM", "UNKNOWN"))) $o->lifeStage = self::get_uri($lifeStage, "lifeStage");

        $o->preparations        = $rec["http://rs.tdwg.org/dwc/terms/preparations"];
        $o->fieldNotes          = $rec["http://rs.tdwg.org/dwc/terms/fieldNotes"];
        $o->locality            = $rec["http://rs.tdwg.org/dwc/terms/locality"];
        $o->verbatimElevation   = $rec["http://rs.tdwg.org/dwc/terms/verbatimElevation"];
        $o->verbatimLatitude    = $rec["http://rs.tdwg.org/dwc/terms/verbatimLatitude"];
        $o->verbatimLongitude   = $rec["http://rs.tdwg.org/dwc/terms/verbatimLongitude"];
        $o->decimalLatitude     = $rec["http://rs.tdwg.org/dwc/terms/decimalLatitude"];
        $o->decimalLongitude    = $rec["http://rs.tdwg.org/dwc/terms/decimalLongitude"];
        $o->identifiedBy        = $rec["http://rs.tdwg.org/dwc/terms/identifiedBy"];

        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return;
    }

}
?>