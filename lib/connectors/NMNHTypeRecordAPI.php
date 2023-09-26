<?php
namespace php_active_record;
/*  connector: [891] NMNH type records
    connector: [947] NHM type records
    
    multimedia: 
    _id,license,format,rightsHolder,title,identifier,type
    _id,license,title,format,rightsHolder,identifier,type
    
    
Hi Jen, good question.
Yes there is only 1:1 per occurrence for every measurement record where "measurementOfTaxon" is TRUE.
The other ~9 records are metadata where "measurementOfTaxon" is FALSE.
Please see sample below:

OCCURRENCE.TAB extension
occurrenceID ========== taxonID
1551370 ========== Bodianus_insularis
2826799 ========== Zygomyia_eluta

MEASUREMENT_OR_FACT.TAB extension
occurrenceID ========== measurementOfTaxon ========== measurementType ========== measurementValue
1551370 ========== true ========== http://eol.org/schema/terms/TypeSpecimenRepository ========== http://biocol.org/urn:lsid:biocol.org:col:34665
1551370 ========== (null) ========== http://rs.tdwg.org/dwc/terms/typeStatus ========== http://rs.tdwg.org/ontology/voc/TaxonName#Paratype
1551370 ========== (null) ========== http://rs.tdwg.org/dwc/terms/collectionID ========== ZOO
1551370 ========== (null) ========== http://rs.tdwg.org/dwc/terms/otherCatalogNumbers ========== NHMUK:ecatalogue:3108644
1551370 ========== (null) ========== http://rs.tdwg.org/dwc/terms/higherGeography ========== South America; Brazil
1551370 ========== (null) ========== http://rs.tdwg.org/dwc/terms/continent ========== South America
1551370 ========== (null) ========== http://rs.tdwg.org/dwc/terms/waterBody ========== Southwest Atlantic Ocean
1551370 ========== (null) ========== http://rs.tdwg.org/dwc/terms/country ========== Brazil
1551370 ========== (null) ========== http://rs.tdwg.org/dwc/terms/identificationQualifier ========== TYPE STATUS CHECKED (Eschmeyer Catalogue 2005).  See Notes.

2826799 ========== true ========== http://eol.org/schema/terms/TypeSpecimenRepository ========== http://biocol.org/urn:lsid:biocol.org:col:34665
2826799 ========== (null) ========== http://rs.tdwg.org/dwc/terms/typeStatus ========== http://rs.tdwg.org/ontology/voc/TaxonName#Paratype
2826799 ========== (null) ========== http://rs.tdwg.org/dwc/terms/collectionID ========== BMNH(E)
2826799 ========== (null) ========== http://rs.tdwg.org/dwc/terms/otherCatalogNumbers ========== NHMUK:ecatalogue:868838
2826799 ========== (null) ========== http://rs.tdwg.org/dwc/terms/year ========== 1922
2826799 ========== (null) ========== http://rs.tdwg.org/dwc/terms/month ========== 08
2826799 ========== (null) ========== http://rs.tdwg.org/dwc/terms/day ========== 20
2826799 ========== (null) ========== http://rs.tdwg.org/dwc/terms/higherGeography ========== New Zealand; Canterbury; Governor's Bay, Near Christchurch
2826799 ========== (null) ========== http://rs.tdwg.org/dwc/terms/country ========== New Zealand
2826799 ========== (null) ========== http://rs.tdwg.org/dwc/terms/stateProvince ========== Canterbury

    
*/
class NMNHTypeRecordAPI
{
    function __construct($folder)
    {
        exit("\nObsolete: NMNHTypeRecordAPI.php\n");
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->occurrence_ids = array();
        $this->debug = array();
        $this->typeStatus_separators = array(";", "+", " & ", " AND ", ",");
        //for NHM
        $this->download_options = array('expire_seconds' => false, 'download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->service['specimen'] = "http://data.nhm.ac.uk/api/action/datastore_search?resource_id=05ff2255-c38a-40c9-b657-4ccb55ab2feb";
    }

    function start($params) // NMNH and NHM uses this script --- formerly export_gbif_to_eol()
    {
        $this->uris = self::get_uris($params);
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($params["dwca_file"], "meta.xml", array("timeout" => 7200, "expire_seconds" => 60*60*24*25)); // "expire_seconds" -- false => won't expire; 0 => expires now
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $this->harvester = new ContentArchiveReader(NULL, $archive_path);
        if(!(@$this->harvester->tables["http://rs.tdwg.org/dwc/terms/occurrence"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        if    ($params["dataset"] == "NMNH")    self::process_row_type($params);
        elseif($params["dataset"] == "NHM")     self::process_row_type_from_NHM($temp_dir . "/" . $params['location']);
        $this->archive_builder->finalize(TRUE);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        print_r($this->debug);
    }

    private function get_uris($params)
    {
        $fields = array();
        if(in_array($params["dataset"], array("NMNH", "NHM")))
        {
            $fields["institutionCode"]  = "institutionCode_uri";
            $fields["sex"]              = "sex_uri";
            $fields["typeStatus"]       = "typeStatus_uri";
            $fields["lifeStage"]        = "lifeStage_uri";
            $fields["collectionCode"]   = "collectionCode_uri";
        }
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        $spreadsheet_options = array("cache" => 1, "timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2);
        $spreadsheet_options["expire_seconds"] = 0; // false => won't expire; 0 => expires now (orig value)
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
                                // print_r($fields); //good debug -- show single record or row
                                if($params["type"] == "structured data")                self::create_type_records_nmnh($fields);
                                // elseif($params["type"] == "classification resource")    self::create_classification_gbif($fields); was never used here
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

    private function process_multiple_typestatuses($string, $sciname)
    {
        $arr = array();
        foreach($this->typeStatus_separators as $separator)
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
            $info = self::format_typeStatus($value);
            $value = $info['type_status'];
            if(self::valid_typestatus($value, $sciname))
            {
                foreach($this->typeStatus_separators as $separator)
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
        foreach($arr as $typeStatus)
        {
            $info = self::format_typeStatus($typeStatus);
            $typeStatus = $info['type_status'];
            $URIs[] = self::get_uri($typeStatus, "typeStatus");
        }
        return $URIs;
    }
    
    private function valid_typestatus($typestatus, $sciname)
    {
        if(!$typestatus = trim(strtolower($typestatus))) return false;
        $exclude = array("nomen nudem", "not a type", "voucher", "ms", "non-type", "non type", "none", "type - nom. nud.",
                "?", "additional specimen", "an", "bleeker specimen", "c", "cited", "cited; cited; cited",
                "figured", "figured; cited", "figured; figured", "figured; referred", "g.incrassatus and genus sphoerocephalus",
                "juvenile", "nomen nudem", "nomenclatural standard", "pt", "referred", "referred; referred", "referred; referred; referred",
                "schubotzi", "stated not type on evac.", "trophotype", "typestatus", "voucher");
        if(in_array($typestatus, $exclude)) return false;
        //ms *type (e.g., MS Holotype, MS Lectotype, MS Paralectotype, MS Paratype)
        if(substr($typestatus,0,3) == "ms " && !self::string_with_separator($typestatus)) return false;
        // sciname must not be blank
        if(!$sciname) return false;
        //The ScientificName is of the form “Genus sp.”, e.g., Alnus sp. - Note: names like Alnus maritima subsp. oklahomensis or Carex nutans var. japonica are fine, so you should not skip all records with . in them.
        if(strtolower(substr($sciname, -4)) == " sp.") return false;
        if(substr($typestatus, 0, 8) == "cast of ") return false;
        return true;
    }
    
    private function string_with_separator($string)
    {
        $no_separator = array("TYPE, NO.17 = LECTOTYPE", "TYPE, NO. 15 = LECTOTYPE", "LECTOTYPE, TYPE", "HOLOTYPE, TYPE", "SYNTYPE OF LEPRALIA ERRATA, WATERS");
        if(in_array($string, $no_separator)) return false;
        foreach($this->typeStatus_separators as $separator)
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
    
        if($rec['dataset'] == "NMNH")
        {
            if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"]) $taxon->scientificName .= " " . $val;
        }
        elseif($rec['dataset'] == "NHM")
        {
            /* this is excluded since our name matching algorithm isn't able to match names correctly to its correct taxon_concept, thus decided to strip the author part of the name. 
            https://jira.eol.org/browse/DATA-1615?focusedCommentId=59928&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-59928 */
        }
        
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
        if($rec['dataset'] == "NMNH")
        {
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
        }
        elseif($rec['dataset'] == "NHM")
        {
            // if(!$institutionCode_uri = self::get_uri($rec['institutionCode'], "institutionCode")) return;
            if(!$institutionCode_uri = self::get_uri($rec['http://rs.tdwg.org/dwc/terms/institutionCode'], "institutionCode")) return;
            if($val = @$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']) $rec["source"] = "http://data.nhm.ac.uk/specimen/" . $val;
        }
        
        $typeStatus_uri = false;
        $typeStatus_uri_arr = false;
        $typeStatus = $rec["http://rs.tdwg.org/dwc/terms/typeStatus"];

        $info = self::format_typeStatus($typeStatus);
        $typeStatus                 = $info['type_status'];
        $rec['measurement_remarks'] = $info['measurement_remarks'];
        
        if(self::string_with_separator($typeStatus)) $typeStatus_uri_arr = self::process_multiple_typestatuses($typeStatus, $rec["http://rs.tdwg.org/dwc/terms/scientificName"]);
        else                                         $typeStatus_uri = self::get_uri($typeStatus, "typeStatus");
        
        if($institutionCode_uri && ($typeStatus_uri || $typeStatus_uri_arr))
        {
            self::add_string_types($rec, $institutionCode_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", "true");
            if($typeStatus_uri_arr)
            {
                foreach($typeStatus_uri_arr as $typeStatus_uri) self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
            }
            else self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
            
            if($val = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"]) self::add_string_types($rec, self::get_uri($val, "collectionCode"), "http://rs.tdwg.org/dwc/terms/collectionID");

            $associatedSequences = $rec["http://rs.tdwg.org/dwc/terms/associatedSequences"];
            if($associatedSequences && $associatedSequences != "Genbank:") self::add_string_types($rec, $associatedSequences, "http://rs.tdwg.org/dwc/terms/associatedSequences");

            $fields = array("http://rs.tdwg.org/dwc/terms/recordNumber", "http://rs.tdwg.org/dwc/terms/otherCatalogNumbers", "http://rs.tdwg.org/dwc/terms/startDayOfYear", "http://rs.tdwg.org/dwc/terms/endDayOfYear", "http://rs.tdwg.org/dwc/terms/year", "http://rs.tdwg.org/dwc/terms/month", "http://rs.tdwg.org/dwc/terms/day", "http://rs.tdwg.org/dwc/terms/verbatimEventDate", "http://rs.tdwg.org/dwc/terms/fieldNumber", "http://rs.tdwg.org/dwc/terms/higherGeography", "http://rs.tdwg.org/dwc/terms/continent", "http://rs.tdwg.org/dwc/terms/waterBody", "http://rs.tdwg.org/dwc/terms/islandGroup", "http://rs.tdwg.org/dwc/terms/island", "http://rs.tdwg.org/dwc/terms/country", "http://rs.tdwg.org/dwc/terms/stateProvince", "http://rs.tdwg.org/dwc/terms/county", "http://rs.tdwg.org/dwc/terms/minimumElevationInMeters", "http://rs.tdwg.org/dwc/terms/maximumElevationInMeters", "http://rs.tdwg.org/dwc/terms/verbatimDepth", "http://rs.tdwg.org/dwc/terms/minimumDepthInMeters", "http://rs.tdwg.org/dwc/terms/maximumDepthInMeters", "http://rs.tdwg.org/dwc/terms/verbatimCoordinateSystem", "http://rs.tdwg.org/dwc/terms/geodeticDatum", "http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters", "http://rs.tdwg.org/dwc/terms/georeferenceProtocol", "http://rs.tdwg.org/dwc/terms/georeferenceRemarks", "http://rs.tdwg.org/dwc/terms/identificationQualifier");
            if($rec['dataset'] == "NMNH") $fields[] = "http://rs.tdwg.org/dwc/terms/associatedMedia";
            foreach($fields as $field)
            {
                if($val = $rec[$field]) self::add_string_types($rec, $val, $field);
            }

            self::create_instances_from_taxon_object($rec);
        }
    }

    private function get_uri($value, $field)
    {
        $value = trim(str_replace("'", "", $value));
        if(in_array($field, array("sex", "typeStatus"))) $value = strtoupper($value);
        
        if($field == "typeStatus")
        {
            $info = self::format_typeStatus($value);
            $value = $info['type_status'];
        }
        
        if($field == "sex")
        {
            //remove "s"
            $value = str_ireplace("MALES", "MALE", $value);
            $value = str_ireplace("HERMAPHRODITES", "HERMAPHRODITE", $value);
            if($value == "FEMAL") $value = "FEMALE";
            
            /*
            Any values that have a ? - use verbatim value
            Strings like "Worker", "sex", "no sex" - use verbatim value
            [WORKER] => 
            [MALE?] => 
            */
            
            //manual adjustment
            if($value === 0 || $value === "0") $value = ""; //ignore
            
            //various case statements
            if(is_numeric(stripos($value, "?"))) {} // use verbatim value
            elseif(in_array($value, array("F SUBAD", "F AD")))                                              $value = "FEMALE";
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
                elseif(in_array($value, array("UNCERTAIN", "SEX UNKNOWN", "UNKNOWN; UNKNOWN", "UNSEXED", "(UNSEXED)"))) $value = "UNKNOWN";
                else {} // use verbatim value
            }
            else {} // use verbatim value
        }
        
        if($field == "lifeStage")
        {
            /* NMNH
              [EXUVIAE] => 
              [HALF-GROWN] => 
              [; JUVENILE; LARVAE V] => 
              [OVIGEROUS; LARVAE V] => 
              [; AMPLEXUS] => 
              [I; II; OVIGEROUS;] => 
              [I; OVIGEROUS;] => 
              [I; OVIGEROUS] => 
              [I; II; OVIGEROUS] => 
              [II; OVIGEROUS] => 
              [NEUTER] => 
              [; NEUTER] => 
              [; OVIGEROUS; SUBADULT] => 
              [METAMORPH, LARVAE] => 
              [YOUNG OF THE YEAR] => 
              [FIRST YEAR] => 
              [; OVIGEROUS; PRANIZA] => 
              [; IMMATURE; JUVENILE; MANCA] => 
              [; IMMATURE; JUVENILE] => 
              [PREMATURE; JUVENILE] => 
              [AGED] => 
            */
            
            $value = str_replace(";;", ";", $value);
            $value = str_replace(";;", ";", $value);
            $value = str_ireplace("JUVENILES", "JUVENILE", $value);
            $value = str_ireplace("ADULT ANDJUVENILE", "ADULT & JUVENILE", $value);

            if(in_array($value, array("ADULT;", "; ADULT", "ADULT; ADULT", "BRANCHIATE ADULT", "ADULT; WINGS UNKNOWN", "ADULT(S)", "ADULTS", "ADULT (ANTENNAE ONLY)"))) $value = "ADULT";
            elseif(in_array($value, array("PUPA;", "PUPAL EXUVIA", "PUPAE, 3")))                                            $value = "PUPA";
            elseif(in_array($value, array("; JUVENILE; JUVENILE; EMBRYO", "; I; OVIGEROUS", "JUVENILE, EGG")))              $value = "JUVENILE & OVIGEROUS";
            elseif(in_array($value, array("; LARVAE", "; LARVAE V", "; LARVAE;", "LARVA; LARVA", "LARVAL", "LARVAL STAGE", "TANTULUS LARVA", "CHAETOSPHAERA LARVAE"))) $value = "LARVA";
            elseif(in_array($value, array("; COPEPODID", "COPEPODID IV; COPEPODID V", "COPEPODIDS, STAGE III", "COPEPODID STAGES V AND IV", "COPEPODIDS, STAGE V", "COPEPODIDS, STAGE IV", "COPEPODID (STAGES IV AND V)")))            $value = "COPEPODID";
            elseif(in_array($value, array("; JUVENILE;", "JUVENILE;", "; JUVENILE", "JUV.", "JUVENILE STAGE V", "JUVENILE, 1"))) $value = "JUVENILE";
            elseif(in_array($value, array("; OVIGEROUS", "OVIGEROUS;", "; OVIGEROUS;", "; OVIGEROUS; OVIGEROUS")))          $value = "OVIGEROUS";
            elseif(in_array($value, array("PREMATURE", "; PREMATURE", "; IMMATURE", "IMMATURE (3)", "IMMATURE, 1")))        $value = "IMMATURE"; // by Eli
            elseif(in_array($value, array("1 ADULT, 1 LARVA", "ADULT WITH 2 EGGS")))                                        $value = "ADULT & LARVA";
            elseif($value == "; OVIGEROUS; JUVENILE V")                 $value = "JUVENILE V & OVIGEROUS";
            elseif($value == "; EMBRYO")                                $value = "EMBRYO";
            elseif($value == "; PRANIZA")                               $value = "PRANIZA";
            elseif($value == "ADULT; JUVENILE")                         $value = "ADULT & JUVENILE";
            elseif(in_array($value, array("PUPA; PUPA; ADULT", "ADULT; PUPARIUM"))) $value = "ADULT & PUPA";
            elseif($value == "SUBADULT")                                $value = "ADULT & SUBADULT";    // by Eli
            elseif($value == "NEARLY ADULT")                            $value = "YOUNG ADULT";         // by Eli
            elseif($value == "ADULT, JUVS(2), LARVAE(6)")               $value = "ADULT & LARVA";       // by Eli
            elseif($value == "ADULT; EGG")                              $value = "ADULT & LARVA";       // by Eli
            elseif($value == "FLOWERING AND IMMATURE FRUIT")            $value = "IMMATURE FRUIT";
            elseif(in_array($value, array("; MANCA", "MANCA III", "MANCA (1)", "MANCA I AND II"))) $value = "MANCA";
            elseif(in_array($value, array("SEXUALLY MATURE MALE", "SEXUALLY MATURE"))) $value = "MATURE";
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
            elseif(is_numeric(stripos($value, "JUVENILE; JUVENILE")))   $value = "JUVENILE";
        }

        if($val = @$this->uris[$value])
        {
            if(in_array($val, array("Exclude- literature dataset", "EXCLUDE"))) return '';
            else                                                                return $val; //success
        }
        else
        {
            $this->debug["undefined"][$field][$value] = '';
            return $value; // returned verbatim
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
           is_numeric(stripos($sex, "UNCERTAIN")) ||
           is_numeric(stripos($sex, "UNSEXED"))) return true;
        return false;
    }

    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        
        if(in_array($rec["dataset"], array("NMNH", "NHM"))) $occurrence_id = $catnum;
        else                                                $occurrence_id = $taxon_id . '_' . $catnum;
        
        $m = new \eol_schema\MeasurementOrFact();
        $this->add_occurrence($taxon_id, $occurrence_id, $rec);
        $m->occurrenceID = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        if($measurementOfTaxon ==  "true")
        {   // so that measurementRemarks (and source, contributor, etc.) appears only once in the [measurement_or_fact.tab]
            $m->measurementRemarks = @$rec['measurement_remarks'];
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
        
        /*
            connector: [891] NMNH type records -- should have $->institutionCode = USNM
            connector: [947] NHM type records -- should have $->institutionCode = NHMUK
            ... using what is in their DWC-A file which is in [http://rs.tdwg.org/dwc/terms/institutionCode] has the correct values, so I will now use that field
            // [http://rs.tdwg.org/dwc/terms/institutionCode] => USNM
            // [http://rs.tdwg.org/dwc/terms/institutionCode] => NHMUK
        */
        
        $o->institutionCode     = $rec["http://rs.tdwg.org/dwc/terms/institutionCode"];
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
    
    private function format_typeStatus($value)
    {
        $value = trim(Functions::remove_whitespace($value));
        if(is_numeric(stripos($value, " ")) || is_numeric(stripos($value, "/"))) $measurement_remarks = $value;
        else                                                                     $measurement_remarks = "";
        
        $value = trim(strtoupper($value));
        $value = str_ireplace(array("[", "]", "!"), "", $value);
        $value = str_ireplace(" ?", "?", $value);
        $value = str_ireplace("TYPES", "TYPE", $value);
        $value = str_ireplace("PROBABLE", "POSSIBLE", $value);
        $value = str_ireplace("NEOTYPE COLLECTION", "NEOTYPE", $value);
        $value = str_ireplace("TYPE.", "TYPE", $value);

        if(substr($value, 0, 8) == "TYPE OF ")                                                                                               $value = "TYPE";
        elseif(substr($value, 0, 11) == "SYNTYPE OF ")                                                                                       $value = "SYNTYPE";
        elseif(substr($value, 0, 17) == "SCHIZOSYNTYPE OF ")                                                                                 $value = "SCHIZOSYNTYPE";
        elseif(substr($value, 0, 18) == "SCHIZOPARATYPE OF ")                                                                                $value = "SCHIZOPARATYPE";
        elseif(in_array($value, array("TYPE, NO. 15 = LECTOTYPE", "TYPE, NO.17 = LECTOTYPE", "LECTOTYPE/TYPE", "LECTOTYPE, TYPE")))          $value = "LECTOTYPE";
        elseif(in_array($value, array("SYNTYTPE", "SYTNTYPE", "SYNYPES", "SYNTPE", "SYNTYPE MAMILLATA")))                                    $value = "SYNTYPE";
        elseif(in_array($value, array("PARALECTO", "PARALECTOYPES", "PARALECTOYPE")))                                                        $value = "PARALECTOTYPE";
        elseif(in_array($value, array("SYNTYPE OR HOLOTYPE?", "?HOLOTYPE OR SYNTYPE", "HOLOTYPE OR SYNTYPE", "SYNTYPE OR HOLOTYPE")))        $value = "SYNTYPE? + HOLOTYPE?";
        elseif(in_array($value, array("POSS./PROB. PARALECTOTYPE", "PARALECTOTYPE (POSSIBLE)", "POSSIBLE PARALECTOTYPE", "?PARALECTOTYPE"))) $value = "PARALECTOTYPE?";
        elseif(in_array($value, array("PT OF HOLOTYPE", "PART OF HOLOTYPE", "HOLOTYPE (PART)")))                                             $value = "HOLOTYPE FRAGMENT";
        elseif(in_array($value, array("PART OF TYPE", "PT OF TYPE", "PART OF TYPE MATERIAL", "PT OF TYPE MATERIAL", "TYPE (PART)")))         $value = "TYPE FRAGMENT";
        elseif(in_array($value, array("?PT OF TYPE?", "?PT OF TYPE OF REGULARIS?")))                                                         $value = "UNCONFIRMED TYPE"; //same as 'TYPE?'
        elseif(in_array($value, array("TYPE - HOLOTYPE", "HOLOTYPE LIMNOTRAGUS SELOUSI", "COTYPE (HOLOTYPE", "HOLOTYPE, TYPE")))             $value = "HOLOTYPE";
        elseif(in_array($value, array("SYNYTPE", "FIGURED SYNTYPE")))       $value = "SYNTYPE";
        elseif(in_array($value, array("TOPTYPE", "TOPOTYPICAL")))           $value = "TOPOTYPE";
        elseif(in_array($value, array("COTYPUS", "CO-TYPE")))               $value = "COTYPE";
        elseif($value == "POSSIBLE COTYPE (FIDE M. R. BROWNING)")           $value = "POSSIBLE COTYPE";
        elseif($value == "SYNTYPE OR PARALECTOTYPE")                        $value = "SYNTYPE? + PARALECTOTYPE?";
        elseif($value == "SYNTYPE OR LECTOTYPE")                            $value = "SYNTYPE? + LECTOTYPE?";
        elseif($value == "TOPOTYPE (STATED BY THE DONOR TO BE PARATYPE)")   $value = "TOPOTYPE? + PARATYPE?";
        elseif($value == "HOLOTYPE/PARATYPE?")                              $value = "HOLOTYPE + PARATYPE?";
        elseif($value == "HOLOTYPE/SYNTYPE")                                $value = "HOLOTYPE + SYNTYPE";
        elseif($value == "SYNTYPE/HOLOTYPE")                                $value = "HOLOTYPE + SYNTYPE";
        elseif($value == "HOLOTYPE/LECTOTYPE")                              $value = "HOLOTYPE + LECTOTYPE";
        elseif($value == "NEOTYPE (POSSIBLE)")                              $value = "NEOTYPE?";
        elseif($value == "LECTOTYPE (POSSIBLE)")                            $value = "LECTOTYPE?";
        elseif($value == "ALLOTYPE (POSSIBLE)")                             $value = "ALLOTYPE?";
        elseif($value == "ORIGINAL MATERIAL.")                              $value = "ORIGINALMATERIAL";
        elseif($value == "PART OF LECTOTYPE")                               $value = "LECTOTYPE FRAGMENT";
        elseif($value == "PART OF PARATYPE")                                $value = "PARATYPE FRAGMENT";
        elseif($value == "ISTOTYPE")                                        $value = "ISOTYPE";
        elseif($value == "PARATYPE (ALLOTYPE)")                             $value = "ALLOTYPE";
        elseif(in_array($value, array("PARATYPE #5", "PARATYPE V", "PARATYPE I", "PARATYPE II", "PARATYPE #2", "PARATYPE #3", "PARATYPE (NO.52)", "PARATYPE #1", "PARATYPE #9", "PARATYPE III", "PARATYPE II AND III", "PARATYPE III AND IV", "PARATYPE #10", "PARATYPE #7", "PARATYPE #4", "PARATYPE #6", "PARATYPE (NO.65)", "PARATYPE #8", "PARAYPE", "PARATYPE)"))) $value = "PARATYPE";
        return array("type_status" => $value, "measurement_remarks" => $measurement_remarks);
    }
    
    private function process_row_type_from_NHM($csv_file)
    {
        $i = 0;
        if(!($file = Functions::file_open($csv_file,"r"))) return;
        while(!feof($file))
        {
            $temp = fgetcsv($file);
            $i++;
            if(($i % 100000) == 0) echo "\n" . number_format($i) . " - ";
            if($i == 1)
            {
                $fields = $temp;
                if(count($fields) != 71)
                {
                    // $this->debug["not71"][$fields[0]] = '';
                    @$this->debug["not71"]++;
                    continue;
                }
            }
            else
            {
                $rec = array();
                $k = 0;
                // 2 checks if valid record
                if(!$temp) continue;
                if(count($temp) != 71)
                {
                    // $this->debug["not71"][$temp[0]] = '';
                    @$this->debug["not71"]++;
                    continue;
                }
                foreach($temp as $t)
                {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                $rec_object = json_decode(json_encode($rec), FALSE); //convert array to object
                $f = self::convert_rec_object_to_array($rec_object);
                if(!self::valid_typestatus($f["http://rs.tdwg.org/dwc/terms/typeStatus"], $f["http://rs.tdwg.org/dwc/terms/scientificName"])) continue;
                self::create_type_records_nmnh($f);
            }
            // if($i >= 1000) break; //debug
        } // end while{}
        fclose($file);
    }

    private function convert_rec_object_to_array($rec)
    {
        $f = array();
        $f['http://rs.tdwg.org/dwc/terms/institutionCode']      = $rec->institutionCode;
        $f['http://rs.tdwg.org/dwc/terms/typeStatus']           = (string) $rec->typeStatus;
        $f['http://rs.tdwg.org/dwc/terms/scientificName']       = trim(str_replace($rec->scientificNameAuthorship, " ", $rec->scientificName));
        $f['taxon_id']                                          = str_replace(" ", "_", $f['http://rs.tdwg.org/dwc/terms/scientificName']);
        $f["dataset"]                                           = "NHM";
        $f[""]                                                  = $rec->_id;
        $f["http://rs.tdwg.org/dwc/terms/occurrenceID"]            = $rec->occurrenceID;
        $f['http://rs.tdwg.org/dwc/terms/collectionCode']       = $rec->collectionCode;
        $f['institutionCode']                                   = $rec->institutionCode; //e.g. NHMUK
        $f['http://rs.tdwg.org/dwc/terms/catalogNumber']        = $rec->catalogNumber;
        $f['http://rs.tdwg.org/dwc/terms/recordedBy']           = $rec->recordedBy;
        $f['http://rs.tdwg.org/dwc/terms/individualCount']      = $rec->individualCount;
        $f['http://rs.tdwg.org/dwc/terms/sex']                  = $rec->sex;
        $f['http://rs.tdwg.org/dwc/terms/lifeStage']            = $rec->lifeStage;
        $f['http://rs.tdwg.org/dwc/terms/preparations']         = $rec->preparations;
        $f['http://rs.tdwg.org/dwc/terms/locality']             = $rec->locality;
        $f['http://rs.tdwg.org/dwc/terms/verbatimLatitude']     = $rec->verbatimLatitude;
        $f['http://rs.tdwg.org/dwc/terms/verbatimLongitude']    = $rec->verbatimLongitude;
        $f['http://rs.tdwg.org/dwc/terms/decimalLatitude']      = $rec->decimalLatitude;
        $f['http://rs.tdwg.org/dwc/terms/decimalLongitude']     = $rec->decimalLongitude;
        $f['http://rs.tdwg.org/dwc/terms/identifiedBy']         = $rec->identifiedBy;
        $f['http://rs.tdwg.org/dwc/terms/recordNumber']         = $rec->recordNumber;
        $f['http://rs.tdwg.org/dwc/terms/otherCatalogNumbers']  = $rec->otherCatalogNumbers;
        $f['http://rs.tdwg.org/dwc/terms/year']                 = $rec->year;
        $f['http://rs.tdwg.org/dwc/terms/month']                = $rec->month;
        $f['http://rs.tdwg.org/dwc/terms/day']                  = $rec->day;
        $f['http://rs.tdwg.org/dwc/terms/higherGeography']      = $rec->higherGeography;
        $f['http://rs.tdwg.org/dwc/terms/continent']            = $rec->continent;
        $f['http://rs.tdwg.org/dwc/terms/waterBody']            = $rec->waterBody;
        $f['http://rs.tdwg.org/dwc/terms/islandGroup']          = $rec->islandGroup;
        $f['http://rs.tdwg.org/dwc/terms/island']               = $rec->island;
        $f['http://rs.tdwg.org/dwc/terms/country']              = $rec->country;
        $f['http://rs.tdwg.org/dwc/terms/stateProvince']        = $rec->stateProvince;
        $f['http://rs.tdwg.org/dwc/terms/minimumElevationInMeters'] = $rec->minimumElevationInMeters;
        $f['http://rs.tdwg.org/dwc/terms/maximumElevationInMeters'] = $rec->maximumElevationInMeters;
        $f['http://rs.tdwg.org/dwc/terms/minimumDepthInMeters']     = $rec->minimumDepthInMeters;
        $f['http://rs.tdwg.org/dwc/terms/maximumDepthInMeters']     = $rec->maximumDepthInMeters;
        $f['http://rs.tdwg.org/dwc/terms/geodeticDatum']            = $rec->geodeticDatum;
        $f['http://rs.tdwg.org/dwc/terms/georeferenceProtocol']     = $rec->georeferenceProtocol;
        $f['http://rs.tdwg.org/dwc/terms/identificationQualifier']  = $rec->identificationQualifier;
        $f['http://rs.tdwg.org/dwc/terms/scientificNameAuthorship'] = $rec->scientificNameAuthorship;
        $f['http://rs.tdwg.org/dwc/terms/kingdom']                  = $rec->kingdom;
        $f['http://rs.tdwg.org/dwc/terms/phylum']                   = $rec->phylum;
        $f['http://rs.tdwg.org/dwc/terms/class']                    = $rec->class;
        $f['http://rs.tdwg.org/dwc/terms/order']                    = $rec->order;
        $f['http://rs.tdwg.org/dwc/terms/family']                   = $rec->family;
        $f['http://rs.tdwg.org/dwc/terms/genus']                    = $rec->genus;
        $f['http://rs.tdwg.org/dwc/terms/taxonRank']                = $rec->taxonRank;
        $f['source']                                                     = '';
        $f['http://rs.tdwg.org/dwc/terms/occurrenceRemarks']             = '';
        $f['http://rs.tdwg.org/dwc/terms/fieldNotes']                    = '';
        $f['http://rs.tdwg.org/dwc/terms/verbatimElevation']             = '';
        $f['http://rs.tdwg.org/dwc/terms/associatedSequences']           = '';
        $f['http://rs.tdwg.org/dwc/terms/startDayOfYear']                = '';
        $f['http://rs.tdwg.org/dwc/terms/endDayOfYear']                  = '';
        $f['http://rs.tdwg.org/dwc/terms/verbatimEventDate']             = '';
        $f['http://rs.tdwg.org/dwc/terms/fieldNumber']                   = '';
        $f['http://rs.tdwg.org/dwc/terms/county']                        = '';
        $f['http://rs.tdwg.org/dwc/terms/verbatimDepth']                 = '';
        $f['http://rs.tdwg.org/dwc/terms/verbatimCoordinateSystem']      = '';
        $f['http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters'] = '';
        $f['http://rs.tdwg.org/dwc/terms/georeferenceRemarks']           = '';
        if($val = @$rec->associatedMedia) $f['http://rs.tdwg.org/dwc/terms/associatedMedia'] = $val;
        return $f;
    }

    /* working but not being used at the moment, we are making use of their dwc-a file
    function export_nhm_gbif_to_eol($params) // using the NHM API service
    {
        $this->uris = self::get_uris($params);
        $limit = 500; $offset = 0; //orig limit=500 offset=0
        while(true)
        {
            $url = $this->service['specimen'] . "&limit=$limit&offset=$offset";
            if($contents = Functions::lookup_with_cache($url, $this->download_options))
            {
                $json = json_decode($contents);
                $returned = count($json->result->records);
                if(($offset % 100000) == 0) echo "\n offset: [" . number_format($offset) . "]\n";
                self::process_nhm_specimen_records($json);
                // break; //debug
            }
            $offset += $limit;
            if($returned < $limit) break;
            // break; //debug
        }
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }

    private function process_nhm_specimen_records($recs) // using the NHM API service
    {
        foreach($recs->result->records as $rec)
        {
            $f = self::convert_rec_object_to_array($rec);
            if(!self::valid_typestatus($f["http://rs.tdwg.org/dwc/terms/typeStatus"], $f["http://rs.tdwg.org/dwc/terms/scientificName"])) continue;
            self::create_type_records_nmnh($f);
        }
    }
    */

}
?>