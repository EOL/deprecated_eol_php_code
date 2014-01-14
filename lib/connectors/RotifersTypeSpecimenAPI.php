<?php
namespace php_active_record;
/* connector: [726]
Connector processes two tab-delimited text files generated from Rotifers MS-Access database.
Connector downloads the zip file, extracts, reads, assembles the data and generates the EOL DWC-A resource.
*/
class RotifersTypeSpecimenAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->occurrence_ids = array();
        $this->page_by_guid = "http://rotifera.hausdernatur.at/Specimen/Index/";
        $this->zip_path = "http://localhost/~eolit/cp/Rotifers/type_specimen.zip";
        $this->zip_path = "https://dl.dropboxusercontent.com/u/7597512/Rotifers/type_specimen.zip";
        $this->text_path = array();
        $this->identified_by = array();
    }

    function get_all_taxa()
    {
        self::load_zip_contents();
        print_r($this->text_path);

        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();

        $fields = array("lngSpeciesSenior_ID", "lngSpecies_ID", "lngRepository_ID", "strCatNr", "lngPrepMeth_ID", "lngPersPrep_ID", "lngPersID2_ID", "lngPersID3_ID", "bytCountPrep", "lngDocuTypeSpecimen", "lngPrep_ID", "txtPrepNotes", "lngPersIdent_ID", "lngSpecimen_ID");
        $records_specimen = $func->make_array($this->text_path["sd_specimen"], $fields, "", array());
        array_shift($records_specimen);

        $fields = array("lngLoc_ID", "strLocName", "txtLocDescr", "sngElevation", "sngLatitudeWGS84", "sngLongitudeWGS84", "lngMacrohabitat_ID", "lngPermanency_ID", "strDepth", "lngGeology_ID", "lngSpecLocal_ID", "lngSpecimen_ID", "lngSpeciesSenior_ID", "lngSpecies_ID");
        $records_locality = $func->make_array($this->text_path["sd_locality"], $fields, "", array());
        array_shift($records_locality);

        $this->assemble_identified_by($records_specimen);
        $this->create_instances_from_taxon_object($records_specimen, "specimen");
        $this->create_instances_from_taxon_object($records_locality, "locality");

        $this->create_archive();

        // remove temp dir
        $path = $this->text_path["sd_specimen"];
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace("/type_specimen", "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }

    private function assemble_identified_by($records_specimen)
    {
        foreach($records_specimen as $rec)
        {
            $id = $rec["lngSpecimen_ID"];
            if($rec["lngPersIdent_ID"])
            {
                if(isset($this->identified_by[$id])) $this->identified_by[$id] .= ", " . $rec["lngPersIdent_ID"];
                else $this->identified_by[$id] = $rec["lngPersIdent_ID"];
            }
        }
    }
    
    private function create_instances_from_taxon_object($records, $type)
    {
        $processed_specimen = array();
        $processed_locality = array();
        
        $i = 0;
        foreach($records as $rec)
        {
            // $i++; if($i >= 50) return; // debug - used on preview mode
            
            $sciname = trim(str_replace('"', '', $rec["lngSpecies_ID"]));
            
            // manual adjustment
            if(!$sciname) continue; // blank
            if(stripos($sciname, "Philadelphia") !== false) continue;
            if(stripos($sciname, "Good slide") !== false) continue;
            if(stripos($sciname, "Natural has") !== false) continue;
            if(in_array($sciname, array("-n.s.-", "0435", "0228", "MRAC", "NUM", "CDJ", "USNM", "ANSP", "0267", "0141", "Jersabek, C D", "Myers, F J", "Segers, H"))) continue;
            
            $taxon_id = str_replace(" ", "_", $sciname);
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                       = $taxon_id;
            $taxon->scientificName                = $sciname;
            $this->taxa[$taxon->taxonID] = $taxon;
            $rec["taxon_id"] = $taxon_id;
            $rec["sciname"] = $sciname;
            if($type == "locality")
            {
                if(!isset($processed_locality[$rec["lngLoc_ID"]]))
                {
                    $rec["catnum"] = $rec["lngLoc_ID"];
                    self::process_locality($rec);
                    $processed_locality[$rec["lngLoc_ID"]] = 1;
                }
            }
            elseif($type == "specimen")
            {
                if(!isset($processed_specimen[$rec["lngSpecimen_ID"]]))
                {
                    $rec["catnum"] = $rec["lngSpecimen_ID"];
                    self::process_specimen($rec);
                    $processed_specimen[$rec["lngSpecimen_ID"]] = 1;
                }
            }
        }
    }

    private function process_locality($rec)
    {
        /*
        ick, for these you need to get to tblLocality via tblSpecimenLocality, using IngSpecimen_ID and IngLoc_ID
        In tblLocality
        (Locality) strLocName, txtLocDesc-> concatenated in Locality
        (Locality) sngElevation->Verbatim Elevation (strip out the "_m" and attach units = http://purl.obolibrary.org/obo/UO_0000008 ?)
        (Locality) sngLatitudeWGS84->Decimal Latitude
        (Locality) sngLongitudeWGS84-> Decimal Longitude
        (Locality) IngMacrohabitat_ID, IngPermanency_ID, "water depth="strDepth, "Geology="IngGeology->concatenated into Field Notes, skip those last two if blank?
        */
        // "lngLoc_ID", "", "", "", "", "", "", "", "", "", "lngSpecLocal_ID", "lngSpecimen_ID", "lngSpeciesSenior_ID", "lngSpecies_ID");
        
        $sciname = $rec["sciname"];
        $locality = "";
        if($val = $rec["strLocName"])
        {
            if($locality) $locality .= ". " . $val;
            else $locality = $val;
        }
        if($val = $rec["txtLocDescr"])
        {
            if($locality) $locality .= ". " . $val;
            else $locality = $val;
        }

        $verbatim_elevation = $rec["sngElevation"];
        $latitude = $rec["sngLatitudeWGS84"];
        $longitude = $rec["sngLongitudeWGS84"];
        
        $remarks = "";
        if($val = $rec["lngMacrohabitat_ID"])
        {
            if($remarks) $remarks .= ". " . $val;
            else $remarks = $val;
        }
        if($val = $rec["lngPermanency_ID"])
        {
            if($remarks) $remarks .= ". " . $val;
            else $remarks = $val;
        }
        if($val = $rec["strDepth"])
        {
            if($remarks) $remarks .= ". water depth = " . $val;
            else $remarks = "water depth = " . $val;
        }
        if($val = $rec["lngGeology_ID"])
        {
            if($remarks) $remarks .= ". Geology = " . $val;
            else $remarks = "Geology = " . $val;
        }
        $field_notes = str_replace('"', '', $remarks);

        if($val = $locality)
        {
                                            self::add_string_types($rec, "Locality", $val, "http://rs.tdwg.org/dwc/terms/locality", $remarks);
            if($val = $latitude)            self::add_string_types($rec, "Latitude", $val, "http://rs.tdwg.org/dwc/terms/decimalLatitude");
            if($val = $longitude)           self::add_string_types($rec, "Longitude", $val, "http://rs.tdwg.org/dwc/terms/decimalLongitude");
            if($val = $verbatim_elevation)  self::add_string_types($rec, "Verbatim elevation", $val, "http://rs.tdwg.org/dwc/terms/verbatimElevation");
            if($val = $field_notes)         self::add_string_types($rec, "Field notes", $val, "http://rs.tdwg.org/dwc/terms/fieldNotes");
            if($val = $sciname)             self::add_string_types($rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
        }
    }


    private function process_specimen($rec)
    {
        /*
        In tblSpecimen
        IngRepository->Institution Code
        strCatNr->Catalog Number
        IngPrepMeth_ID->Preparations
        IngPersPrep_ID, IngPersID2_ID, IngPersID3__ID >Recorded by (concatenated instructions in Recorded By field in spreadsheet template say this is okay, anyway, )
        bytCountPrep-> Count of Individuals
        IngDocuTypeSpecimen, IngPrep_ID, txtPrepNotes->concatenated into Remarks
        
        In tblSpecimenIdentifier
        IngPersIdent_ID-> Identified By (you can get multiple hits for this using IngSpecimen_ID to map them to specimens- concatenating the people okay?)
        */
        
        $sciname = $rec["sciname"];
        $institution_code = $rec["lngRepository_ID"];
        $catalog_no = $rec["strCatNr"];
        $preparations = $rec["lngPrepMeth_ID"];
        $preparations = str_replace('"', '', $preparations);
        
        $count_of_individuals = $rec["bytCountPrep"];
        $identified_by = $this->identified_by[$rec["lngSpecimen_ID"]];
        $identified_by = str_replace('"', '', $identified_by);
        
        $remarks = "";
        if($val = $rec["lngDocuTypeSpecimen"])
        {
            if($remarks) $remarks .= ". " . $val;
            else $remarks = $val;
        }
        if($val = $rec["lngPrep_ID"])
        {
            if($remarks) $remarks .= ". " . $val;
            else $remarks = $val;
        }
        if($val = $rec["txtPrepNotes"])
        {
            if($remarks) $remarks .= ". " . $val;
            else $remarks = $val;
        }

        $recorded_by = "";
        if($val = $rec["lngPersPrep_ID"])
        {
            if($recorded_by) $recorded_by .= ", " . $val;
            else $recorded_by = $val;
        }
        if($val = $rec["lngPersID2_ID"])
        {
            if($recorded_by) $recorded_by .= ", " . $val;
            else $recorded_by = $val;
        }
        if($val = $rec["lngPersID3_ID"])
        {
            if($recorded_by) $recorded_by .= ", " . $val;
            else $recorded_by = $val;
        }
        $recorded_by = str_replace('"', '', $recorded_by);
        
        if($val = $preparations)
        {
                                                self::add_string_types($rec, "Preparations", $val, "http://rs.tdwg.org/dwc/terms/preparations", $remarks);
            if($val = $institution_code)        self::add_string_types($rec, "Institution code", $val, "http://rs.tdwg.org/dwc/terms/institutionCode");
            if($val = $catalog_no)              self::add_string_types($rec, "Catalog number", $val, "http://rs.tdwg.org/dwc/terms/catalogNumber");
            if($val = $count_of_individuals)    self::add_string_types($rec, "Count of individuals", $val, "http://rs.tdwg.org/dwc/terms/individualCount");
            if($val = $identified_by)           self::add_string_types($rec, "Identified by", $val, "http://rs.tdwg.org/dwc/terms/identifiedBy");
            if($val = $recorded_by)             self::add_string_types($rec, "Recorded by", $val, "http://rs.tdwg.org/dwc/terms/recordedBy");
            if($val = $sciname)                 self::add_string_types($rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
        }
    }

    private function add_string_types($rec, $label, $value, $mtype, $measurementRemarks = null)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"]; // two diff sources for locality and specimen datasets
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;

        if(in_array($label, array("Preparations", "Locality")))
        {
            $m->measurementOfTaxon = 'true';
            $m->measurementRemarks = $measurementRemarks;
            $m->source = $this->page_by_guid . $rec["lngSpecimen_ID"];
            $m->contributor = 'Rotifer World Catalog'; // if this doesn't work then use the 'contributor' implementation above that are commented
        }
        
        if($label == "Verbatim elevation") $m->measurementUnit = "http://purl.obolibrary.org/obo/UO_0000008";
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . 'O' . $catnum; // suggested by Katja to use -- ['O' . $catnum]
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    private function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }

    private function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::get_remote_file($this->zip_path, array('timeout' => 172800, 'download_attempts' => 2)))
        {
            $parts = pathinfo($this->zip_path);
            $temp_file_path = $this->TEMP_FILE_PATH . "/" . $parts["basename"];
            $TMP = fopen($temp_file_path, "w");
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("tar -xzf $temp_file_path -C $this->TEMP_FILE_PATH");
            if(!file_exists($this->TEMP_FILE_PATH . "/species.txt")) 
            {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/sd_specimen.txt")) return;
            }
            $this->text_path["sd_specimen"] = $this->TEMP_FILE_PATH . "/sd_specimen.txt";
            $this->text_path["sd_locality"] = $this->TEMP_FILE_PATH . "/sd_locality.txt";
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return;
        }
    }

}
?>