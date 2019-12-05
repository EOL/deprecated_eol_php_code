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
        $this->resource_id = $folder;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->occurrence_ids = array();
        $this->specimen_page_by_guid = "http://rotifera.hausdernatur.at/Specimen/Index/";
        $this->species_page_by_guid  = "http://rotifera.hausdernatur.at/Species/Index/";
        $this->zip_path = "http://localhost/cp_new/Rotifers/type_specimen.zip";
        $this->zip_path = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Rotifers/type_specimen.zip";
        $this->text_path = array();
        $this->identified_by = array();
        $this->habitats = array();
        $this->institution_codes = array();
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 2); //5 mins timeout
        $this->institutions_xls = "http://localhost/cp_new/Rotifers/World_Rotifer_institutionsURIS.xls";
        $this->institutions_xls = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Rotifers/World_Rotifer_institutionsURIS.xls";
    }
    function get_all_taxa()
    {
        $this->institution_uris = self::get_institution_uris();
        
        self::load_zip_contents();
        print_r($this->text_path);

        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();

        $fields = array("lngSpeciesSenior_ID", "lngSpecies_ID", "lngRepository_ID", "strCatNr", "lngPrepMeth_ID", "lngPersPrep_ID", "lngPersID2_ID", "lngPersID3_ID", "bytCountPrep", "lngDocuTypeSpecimen", "lngPrep_ID", "txtPrepNotes", "lngSpecimen_ID", "strTypeStat", "lngLoc_ID", "strLocName", "txtLocDescr", "sngElevation", "sngLatitudeWGS84", "sngLongitudeWGS84", "lngMacrohabitat_ID", "lngPermanency_ID", "strDepth", "lngGeology_ID", "lngSpecLocal_ID");
        $records_specimen = $func->make_array($this->text_path["sd_specimen"], $fields, "", array());
        array_shift($records_specimen);
        $fields = array("lngPersIdent_ID", "lngSpecimen_ID");
        $records_specimen_identifier = $func->make_array($this->text_path["sd_specimen_identifier"], $fields, "", array());
        array_shift($records_specimen_identifier);
        $fields = array("lngSpeciesSenior_ID", "lngMacroMicro_ID", "lngSpecies_ID");
        $records_habitat = $func->make_array($this->text_path["sd_macro_locality"], $fields, "", array());
        array_shift($records_habitat);

        /*
        $fields = array("lngLoc_ID", "strLocName", "txtLocDescr", "sngElevation", "sngLatitudeWGS84", "sngLongitudeWGS84", "lngMacrohabitat_ID", "lngPermanency_ID", "strDepth", "lngGeology_ID", "lngSpecLocal_ID", "lngSpecimen_ID", "lngSpeciesSenior_ID", "lngSpecies_ID");
        $records_locality = $func->make_array($this->text_path["sd_locality"], $fields, "", array());
        array_shift($records_locality);
        */

        $this->assemble_identified_by($records_specimen_identifier);
        $this->create_instances_from_taxon_object($records_specimen, "specimen");
        // $this->create_instances_from_taxon_object($records_locality, "locality");
        $this->create_instances_from_taxon_object($records_habitat, "habitat");
        $this->create_archive();

        // remove temp dir
        $path = $this->text_path["sd_specimen"];
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace("/type_specimen", "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }
    private function get_institution_uris()
    {
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        $arr = $func->convert_spreadsheet($this->institutions_xls, 0);
        $institutions = array();
        $k = 0;
        foreach($arr["Code"] as $code) {
            $institutions[$code] = $arr["URI"][$k];
            $k++;
        }
        echo "\nInstitutions from spreadsheet [$this->institutions_xls]:" . count($institutions) . "\n";
        return $institutions;
    }
    private function assemble_identified_by($records_specimen)
    {
        foreach($records_specimen as $rec) {
            $rec = self::clean_record($rec);
            $id = $rec["lngSpecimen_ID"];
            if($identifier = $rec["lngPersIdent_ID"]) {
                if(isset($this->identified_by[$id])) $this->identified_by[$id] .= ", " . $identifier;
                else $this->identified_by[$id] = $identifier;
            }
        }
    }
    private function create_instances_from_taxon_object($records, $type)
    {
        $processed_specimen = array();
        $processed_locality = array();
        $i = 0;
        foreach($records as $rec) {
            if($type == "habitat") $sciname = $rec["lngSpeciesSenior_ID"];
            else                   $sciname = $rec["lngSpecies_ID"];
            $sciname = trim(str_replace('"', '', $sciname));
            
            // manual adjustment
            if(!$sciname) continue; // blank
            if(in_array(substr($sciname,0,1), array("0","1","2","3","4","5","6","7","8","9"))) continue;
            
            $taxon_id = md5(str_replace(" ", "_", $sciname));
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                       = $taxon_id;
            $taxon->scientificName                = $sciname;
            $rec["taxon_id"] = $taxon_id;
            $rec["sciname"] = $sciname;
            $cont_save = false;
            
            if($type == "locality") {
                // if(!isset($processed_locality[$rec["lngLoc_ID"]]))
                // {
                //     $rec["catnum"] = $rec["lngLoc_ID"];
                //     self::process_locality($rec);
                //     $processed_locality[$rec["lngLoc_ID"]] = 1;
                // }
            }
            elseif($type == "specimen") {
                if($lngSpecimen_ID = $rec["lngSpecimen_ID"]) {
                    if(!isset($processed_specimen[$lngSpecimen_ID])) {
                        $rec["catnum"] = $lngSpecimen_ID;
                        $cont_save = self::process_specimen($rec);
                        $processed_specimen[$lngSpecimen_ID] = 1;
                    }
                }
            }
            elseif($type == "habitat") {
                $habitats = explode("/", $rec["lngMacroMicro_ID"]);
                foreach($habitats as $habitat) {
                    $habitat = trim($habitat);
                    if($habitat != "-" && $habitat) {
                        $rec["catnum"] = md5($sciname . "|" . $habitat);
                        $rec["habitat"] = $habitat;
                        $cont_save = self::process_habitat($rec);
                    }
                }
            }
            if($cont_save) $this->taxa[$taxon->taxonID] = $taxon;
        }//loop
    }
    private function process_habitat($rec)
    {
        $habitat = trim(str_replace('"', '', $rec["habitat"]));
        $habitat = utf8_encode($habitat);
        $value_uri = self::format_habitat_value($habitat);
        if(!$value_uri) {
            if($habitat != "Open water (tychoplanktonic)") echo "\n investigate [$habitat] no URI\n";
        }
        if(!isset($this->habitats[$rec["taxon_id"]][$value_uri])) {
            $this->habitats[$rec["taxon_id"]][$value_uri] = 1;
            if($val = $habitat) {
                $rec['parentMeasurementID'] = self::add_string_types($rec, "Habitat", $val, "http://purl.obolibrary.org/obo/RO_0002303", null, $value_uri); //old mtype = 'http://eol.org/schema/terms/Habitat'
            }
            if($val = $rec["sciname"]) self::add_string_types($rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
            return true;
        }
        return false;
    }
    private function format_habitat_value($habitat)
    {
        switch($habitat) {
            case "Lake, freshwater (mesotrophic)": return "http://eol.org/schema/terms/mesotrophicFreshwaterLake";
            case "Macrophytes (Ceratophyllum, Myriophyllum)": return "http://eol.org/schema/terms/macrophytes";
            case "Periphyton, and POM (littoral stones, gravel)": return "http://eol.org/schema/terms/periphyton";
            //http://eol.org/schema/terms/particulateOrganicMatter
            case "Macrophytes": return "http://eol.org/schema/terms/macrophytes";
            case "Stream, freshwater": return "http://purl.obolibrary.org/obo/ENVO_01000253"; //"http://eol.org/schema/terms/freshwaterStream";
            case "Stream bottom: POM (sand, gravel)": return "http://purl.obolibrary.org/obo/ENVO_00000383";
            case "Riparian wetland": return "http://eol.org/schema/terms/riparianWetland";
            case "Sedges (Carex sp.), 'open water' between plants": return "http://eol.org/schema/terms/betweenSedgesInOpenWater";
            case "River, freshwater (lowland)": return "http://purl.obolibrary.org/obo/ENVO_01000253"; //"http://eol.org/schema/terms/lowlandFreshwaterRiver";
            case "Stream bottom: POM (gravel, stones)": return "http://purl.obolibrary.org/obo/ENVO_00000383";
            case "Periphyton (littoral vegetation, roots of terrestrial vegetation)": return "http://eol.org/schema/terms/periphyton";
            //http://eol.org/schema/terms/littoralVegetation
            case "Periphyton (littoral vegetation)": return "http://eol.org/schema/terms/periphyton";
            //http://eol.org/schema/terms/littoralVegetation
            case "Lake, freshwater (tropical)": return "http://eol.org/schema/terms/tropicalFreshwaterLake";
            case "Pond, freshwater": return "http://purl.obolibrary.org/obo/ENVO_00000033"; //"http://eol.org/schema/terms/freshwaterPond";
            case "Open water (tychoplanktonic)": return false; // DO NOT USE
            case "Lake, freshwater": return "http://purl.obolibrary.org/obo/ENVO_01000252"; //"http://eol.org/schema/terms/freshwaterLake";
            case "Open water": return "http://purl.obolibrary.org/obo/ENVO_00002030"; //"http://eol.org/schema/terms/openWater";
            case "Tap water": return "http://purl.obolibrary.org/obo/ENVO_00003096"; //"http://eol.org/schema/terms/tapWater";
            case "Pond, peat bog": return "http://purl.obolibrary.org/obo/ENVO_00000044";
            case "Moss (Sphagnum, peat moss)": return "http://eol.org/schema/terms/moss";
            case "Pond, freshwater (eutrophic)": return "http://eol.org/schema/terms/eutrophicFreshwaterPond";
            case "Lake, freshwater (alpine glacier margin, ultraoligotrophic)": return "http://eol.org/schema/terms/ultraoligotrophicAlpineFreshwaterLake";
            case "Sand (littoral, glacial flour)": return "http://eol.org/schema/terms/littoralGlacialSand";
            case "Lagoon, mixosaline": return "http://eol.org/schema/terms/mixosalineLagoon";
            case "Algae, planktonic (parasitic in colonies of Uroglena)": return "https://www.wikidata.org/entity/Q184755"; //"http://eol.org/schema/terms/planktonicAlgae";
            case "Swimming pool": return "http://eol.org/schema/terms/swimmingPool";
            case "Biofilm on tiles": return "http://eol.org/schema/terms/tileSurface";
            case "Lake, freshwater (eutrophic)": return "http://eol.org/schema/terms/eutrophicFreshwaterLake";
            case "Pond, freshwater (artificial)": return "http://eol.org/schema/terms/artificialFreshwaterPond";
            case "Lake, freshwater (oligotrophic)": return "http://eol.org/schema/terms/oligotrophicFreshwaterLake";
            case "Lake, freshwater (dystrophic, montane)": return "http://eol.org/schema/terms/dystrophicMontaneFreshwaterLake";
            case "Lake, freshwater (subalpine)": return "http://eol.org/schema/terms/subalpineFreshwaterLake";
            case "Pond, freshwater (mesotrophic)": return "http://eol.org/schema/terms/mesotrophicFreshwaterPond";
            case "Acidic mining lake, freshwater": return "http://eol.org/schema/terms/acidicFreshwaterMiningLake";
            case "Periphyton": return "http://eol.org/schema/terms/periphyton";
            case "Open water (hypolimnion)": return "http://purl.obolibrary.org/obo/ENVO_00002130";
            case "Aquarium, freshwater": return "http://eol.org/schema/terms/freshwaterAquarium";
            case "Glass surface": return "http://eol.org/schema/terms/glassSurface";
            case "Lake, freshwater (alpine, ultraoligotrophic)": return "http://eol.org/schema/terms/ultraoligotrophicAlpineFreshwaterLake";
            case "Macrophytes (Utricularia, Nymphaea)": return "http://eol.org/schema/terms/macrophytes";
            case "Bottom sediments": return "http://purl.obolibrary.org/obo/ENVO_00002007";
            case "Bog, peat bog": return "http://purl.obolibrary.org/obo/ENVO_00000044";
            case "Littoral microhabitats": return "http://eol.org/schema/terms/littoralZone";
            case "Macrophytes (Utricularia)": return "http://eol.org/schema/terms/macrophytes";
            case "Pond, freshwater (alpine pastureland)": return "http://eol.org/schema/terms/alpinePasturelandFreshwaterPond";
            case "Pond, freshwater (alpine)": return "http://eol.org/schema/terms/alpineFreshwaterPond";
            case "Bog pond": return "http://eol.org/schema/terms/bogPond";
            case "Pond, peat bog (alpine)": return "http://purl.obolibrary.org/obo/ENVO_00000044";
            case "Pond, freshwater (fishpond)": return "http://eol.org/schema/terms/freshwaterFishPond";
            case "Pond, freshwater (pasture land)": return "http://eol.org/schema/terms/pasturelandFreshwaterPond";
            case "Lake, freshwater (ultraoligotrophic)": return "http://eol.org/schema/terms/ultraoligotrophicFreshwaterLake";
            case "Impoundment, freshwater (subalpine)": return "http://eol.org/schema/terms/subalpineFreshwaterImpoundment";
            case "Impoundment, freshwater (alpine)": return "http://eol.org/schema/terms/alpineFreshwaterImpoundment";
            case "Stream, freshwater (alpine)": return "http://eol.org/schema/terms/alpineFreshwaterStream";
            case "Stream bottom: POM (sand, gravel, mud)": return "http://purl.obolibrary.org/obo/ENVO_00000383";
            case "Stream, freshwater (glacial)": return "http://eol.org/schema/terms/glacialFreshwaterStream";
            case "Stream bottom": return "http://purl.obolibrary.org/obo/ENVO_00000383";
            case "Tributary": return "http://purl.obolibrary.org/obo/ENVO_00000495";
            default: return false;
        }
    }
    private function process_locality($rec, $sciname)
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
        
        $locality = "";
        if($val = $rec["strLocName"]) {
            if($locality) $locality .= ". " . $val;
            else $locality = $val;
        }
        /* this is now removed in locality e.g. "From Meksuwan et al. (2011) [Ref.18509]"
        if($val = $rec["txtLocDescr"])
        {
            if($locality) $locality .= ". " . $val;
            else $locality = $val;
        }
        */
        $locality = trim(str_replace('"', '', $locality));
        $locality = utf8_encode($locality);
        $locality = str_ireplace("??", "", $locality);
        if(in_array($locality, array("-"))) $locality = "";

        $verbatim_elevation = trim(str_ireplace(" m", "", $rec["sngElevation"]));
        if(in_array($verbatim_elevation, array("0", 0))) $verbatim_elevation = "";
        
        $latitude = $rec["sngLatitudeWGS84"];
        $longitude = $rec["sngLongitudeWGS84"];
        
        $field_notes = "";
        if($val = $rec["lngMacrohabitat_ID"]) {
            if($field_notes) $field_notes .= ". " . $val;
            else $field_notes = $val;
        }
        if($val = $rec["lngPermanency_ID"]) {
            if($field_notes) $field_notes .= ". " . $val;
            else $field_notes = $val;
        }
        if($val = $rec["strDepth"]) {
            if($field_notes) $field_notes .= ". water depth = " . $val;
            else $field_notes = "water depth = " . $val;
        }
        if($val = $rec["lngGeology_ID"]) {
            if($field_notes) $field_notes .= ". Geology = " . $val;
            else $field_notes = "Geology = " . $val;
        }
        $field_notes = trim(str_replace('"', '', $field_notes));
        if(in_array($field_notes, array("-"))) $field_notes = "";

        if($val = $locality)            self::add_string_types($rec, "Locality", $val, "http://rs.tdwg.org/dwc/terms/locality");
        if($val = $latitude)            self::add_string_types($rec, "Latitude", $val, "http://rs.tdwg.org/dwc/terms/decimalLatitude");
        if($val = $longitude)           self::add_string_types($rec, "Longitude", $val, "http://rs.tdwg.org/dwc/terms/decimalLongitude");
        if($val = $verbatim_elevation)  self::add_string_types($rec, "Verbatim elevation", $val, "http://rs.tdwg.org/dwc/terms/verbatimElevation");
        if($val = $field_notes)         self::add_string_types($rec, "Field notes", $val, "http://rs.tdwg.org/dwc/terms/fieldNotes");
    }
    private function clean_record($rec)
    {
        foreach(array_keys($rec) as $field) {
            if(is_numeric(stripos($rec[$field], "n.s."))) $rec[$field] = "";
        }
        return $rec;
    }
    private function process_specimen($rec)
    {
        $rec = self::clean_record($rec);
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
        $catalog_no = (string) $rec["strCatNr"];
        
        // $preparations = $rec["lngPrepMeth_ID"]; -- replaced by $rec["lngPrep_ID"] ("Microscope slide" replaced by "female")
        $preparations = $rec["lngPrep_ID"];
        $preparations = str_replace('"', '', $preparations);
        
        $count_of_individuals = $rec["bytCountPrep"];
        $identified_by = @$this->identified_by[$rec["lngSpecimen_ID"]];
        $identified_by = str_replace('"', '', $identified_by);

        $remarks = "";
        if($val = $rec["lngDocuTypeSpecimen"]) {
            if($remarks) $remarks .= ". " . $val;
            else $remarks = $val;
        }
        /* this is now removed in MeasurementRemarks
        if($val = $rec["lngPrep_ID"]) // e.g. "female"
        {
            if($remarks) $remarks .= ". " . $val;
            else $remarks = $val;
        }
        */
        if($val = $rec["txtPrepNotes"]) {
            if($remarks) $remarks .= ". " . $val;
            else $remarks = $val;
        }
        $remarks = trim(str_replace('"', '', $remarks));
        $remarks = utf8_encode($remarks);
        $remarks = str_ireplace("??", "", $remarks);
        if(in_array($remarks, array("-"))) $remarks = "";

        // if($sciname == "Collotheca orchidacea Meksuwan, Pholpunthin et Segers, 2013") //debug
        // {
        //     echo "\n\n lngDocuTypeSpecimen: " . $rec["lngDocuTypeSpecimen"];
        //     echo "\n lngPrep_ID: " . $rec["lngPrep_ID"];
        //     echo "\n txtPrepNotes:" . $rec["txtPrepNotes"];
        //     echo "\n lngPrepMeth_ID:" . $rec["lngPrepMeth_ID"];
        //     echo "\n";
        //     echo "\n preparations: [$preparations]";
        //     echo "\n remarks: [$remarks]\n";
        // }

        $recorded_by = "";
        if($val = $rec["lngPersPrep_ID"]) {
            if($recorded_by) $recorded_by .= ", " . $val;
            else $recorded_by = $val;
        }
        if($val = $rec["lngPersID2_ID"]) {
            if($recorded_by) $recorded_by .= ", " . $val;
            else $recorded_by = $val;
        }
        if($val = $rec["lngPersID3_ID"]) {
            if($recorded_by) $recorded_by .= ", " . $val;
            else $recorded_by = $val;
        }
        $recorded_by = str_replace('"', '', $recorded_by);
        
        if($type = self::format_type_data($rec["strTypeStat"], $rec)) {
            if($institution_code && $institution_code != "- n.s. -") $TSR = self::get_institution_uri($institution_code);
            else {
                $TSR = "http://eol.org/schema/terms/unknown";
                $institution_code = $TSR;
            }
            $rec['parentMeasurementID'] = self::add_string_types($rec, "TSR", $TSR, "http://eol.org/schema/terms/TypeSpecimenRepository", $remarks);
            self::add_string_types($rec, "Institution code", $institution_code, "http://rs.tdwg.org/dwc/terms/institutionCode");
            // self::add_string_types($rec, "Type information", $type, "http://eol.org/schema/terms/TypeInformation"); // old but working
            self::add_string_types($rec, "Type information", $type, "http://rs.tdwg.org/dwc/terms/typeStatus"); // new and now is being displayed in Data tab
            
            if($val = $preparations)            self::add_string_types($rec, "Preparations", $val, "http://rs.tdwg.org/dwc/terms/preparations");
            if($val = $catalog_no)              self::add_string_types($rec, "Catalog number", $val, "http://rs.tdwg.org/dwc/terms/catalogNumber");
            if($val = $count_of_individuals)    self::add_string_types($rec, "Count of individuals", $val, "http://rs.tdwg.org/dwc/terms/individualCount");
            if($val = $identified_by)           self::add_string_types($rec, "Identified by", $val, "http://rs.tdwg.org/dwc/terms/identifiedBy");
            if($val = $recorded_by)             self::add_string_types($rec, "Recorded by", $val, "http://rs.tdwg.org/dwc/terms/recordedBy");
            if($val = $sciname)                 self::add_string_types($rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
            self::process_locality($rec, $sciname);
            return true;
        }
    }
    private function get_institution_uri($code) // get URI from biocol.org if available
    {
        /* working but not needed anymore
        $url = "http://biocol.org/find-biorepositories?title=&combine=&field_institution_acronym_value=" . $code;
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match("/<tbody>(.*?)<\/tbody>/ims", $html, $arr))
            {
                if(preg_match_all("/<tr(.*?)<\/tr>/ims", $arr[1], $arr))
                {
                    if(preg_match("/<td class=\"views-field views-field-field-institution-acronym active\" >(.*?)<\/td>/ims", $arr[1][0], $arr2))
                    {
                        $inst_code = Functions::remove_whitespace($arr2[1]);
                        if($code == $inst_code)
                        {
                            if(preg_match("/<td class=\"views-field views-field-title\" >(.*?)<\/td>/ims", $arr[1][0], $arr))
                            {
                                if(preg_match("/href=\"(.*?)\"/ims", $arr[1], $arr)) return "http://biocol.org/" . $arr[1];
                            }
                        }
                    }
                }
            }
            else $this->institution_codes[$code] = ''; // institutions not found in biocol.org
        }
        return "http://eol.org/schema/terms/institution_" . strtoupper($code);
        */
        
        if($code == "PMNH") $code = "YPM"; // Peabody Museum of Natural History, Yale University, USA
        if($code == "OÖLM") $code = "OLML"; // Oberösterreichisches Landesmuseum, Linz, Austria
        if($code == "SIZ") $code = "SIZK"; // I. I. Schmalhausen Institute of Zoology, Kiev, Ukraine
        if($code == "NMCIC") $code = "CMN"; // National Museum of Natural Sciences, Ottawa, Ontario, Canada
        if($code == "BM") $code = "BMKB"; // Brunei Museum (Natural History Section), Darussalam, Brunei
        if($code == "NHM") $code = "NHMUK"; // Natural History Museum, South Kensington, London, UK
        if($code == "PSUZC") $code = "PSU"; // Prince of Songkla University Zoological Collection, Princess Maha Chakri Sirindhorn Natural History Museum, Thailand
        if($code == "RUG") $code = "UGMD"; // Ghent University, Institute of Animal Ecology, Belgium
        if($code == "Lund") $code = "MZLU"; // University of Lund, Limnological Institute, Sweden
        if($code == "ZIN-RAS") $code = "ZISP"; // Zoological Institute, Russian Academy of Science, St. Petersburg, Russia
        if($code == "SAM-A") $code = "SAMA"; // South Australian Museum, Adelaide, Australia
        if($code == "MNHNP") $code = "MNHN"; // Museum National d'Histoire Naturelle, Paris, France
        if($code == "UWDB") $code = "BDUW"; // University of Waterloo, Department of Biology, Waterloo, Ontario, Canada
        if($code == "ZSI") $code = "ZSIC"; // Zoological Survey of India, National Zoological Collections, Calcutta, India
        if($code == "SAM-CT") $code = "SAM"; // South African Museum, Cape Town, South Africa
        if($code == "UZAS-IZP") $code = "TASH"; // Uzbekistan Academy of Sciences, Institute of Zoology and Parasitology, Tashkent, Uzbekistan
        if($code == "CRUB") $code = "CRP"; // Centro Regional Universitario Bariloche, Universidad Nacional del Comahue, Bariloche, Argentina
        if($code == "ZITKD") $code = "TKU"; // Tokyo Kyoiku University (now Tsukuba University), Zoological Institute, Tokyo, Japan
        if($code == "IHB-CAS") $code = "IHB"; // Institute of Hydrobiology, Chinese Academy of Sciences, Wuhan, China
        if($code == "IBVV") $code = "IBIW"; // Papanin Institute of the Biology of Inland Waters, Russian Academy of Sciences, Borok, Russia
        if($code == "IMC") $code = "IM"; // Indian Museum, Calcutta, India
        if($code == "HRBNU") $code = "HANU"; // Harbin Normal University, Harbin, China
        if($code == "SYSU") $code = "SYS"; // Biology Museum, Sun Yat-sen University, Guangdong, China
        if($code == "MSU-KS") $code = "MSUT"; // Mahasarakham University, Science Museum, Thailand
        
        if($val = $this->institution_uris[$code]) return $val;
        else {
            echo "\nInvestigate: [$code] no URI from spreadsheet\n";
            echo "\n will try: " . "http://eol.org/schema/terms/institution_" . strtoupper($code);
            exit;
        }
    }
    private function add_string_types($rec, $label, $value, $mtype, $measurementRemarks = null, $value_uri = false)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence_id;

        if(in_array($label, array("TSR", "Habitat"))) {
            $m->measurementOfTaxon = 'true';
            $m->measurementRemarks = $measurementRemarks;
            if($label == "TSR") $m->source = $this->specimen_page_by_guid . $rec["lngSpecimen_ID"];
            elseif($label == "Habitat") $m->source = $this->species_page_by_guid . $rec["lngSpecies_ID"];
            $m->contributor = 'Rotifer World Catalog';
        }
        else { //child records
            $m->occurrenceID = '';
            $m->measurementOfTaxon = '';
            $m->parentMeasurementID = $rec['parentMeasurementID'];
        }
        
        if($label == "Verbatim elevation") $m->measurementUnit = "http://purl.obolibrary.org/obo/UO_0000008";
        $m->measurementType = $mtype;
        
        if($value_uri)  $m->measurementValue = $value_uri;
        else            $m->measurementValue = $value;
        
        $m->measurementMethod = '';
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
        return $m->measurementID;
    }
    private function format_type_data($type, $rec)
    {
        $type = str_ireplace("?", "", $type);
        $type = trim(strtolower($type));
        if    ($type == "allotype")  return "http://rs.tdwg.org/ontology/voc/TaxonName#Allotype";
        elseif($type == "holotype")  return "http://rs.tdwg.org/ontology/voc/TaxonName#Holotype";
        elseif($type == "iconotype") return "http://rs.tdwg.org/ontology/voc/TaxonName#Iconotype";
        elseif($type == "lectotype") return "http://rs.tdwg.org/ontology/voc/TaxonName#Lectotype";
        elseif($type == "neotype")   return "http://rs.tdwg.org/ontology/voc/TaxonName#Neotype";
        elseif(in_array($type, array("paralectotype", "paralectotypes"))) return "http://rs.tdwg.org/ontology/voc/TaxonName#Paralectotype";
        elseif(in_array($type, array("paratype", "paratypes")))           return "http://rs.tdwg.org/ontology/voc/TaxonName#Paratype";
        elseif(in_array($type, array("syntype", "syntypes")))             return "http://rs.tdwg.org/ontology/voc/TaxonName#Syntype";
        elseif(in_array($type, array("type", "type material")))           return "http://rs.tdwg.org/ontology/voc/TaxonName#Type";
        elseif($type == "topotypic material")                             return "http://rs.tdwg.org/ontology/voc/TaxonName#Topotype";
        else {
            if(in_array($type, array("holotype + allotype", "syntypes + allotype"))) {
                $types = explode(" + ", $type);
                foreach($types as $type) {
                    $rec["strTypeStat"] = $type;
                    self::process_specimen($rec);
                }
                return false;
            }
            if(!in_array($type, array("check!", "check this !!"))) echo "\n investigate undefined type [$type]\n";
            return false;
        }
    }
    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . 'O' . $catnum; // suggested by Katja to use -- ['O' . $catnum]
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
    }
    private function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }
    private function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::lookup_with_cache($this->zip_path, array('timeout' => 3600, 'download_attempts' => 2, 'delay_in_minutes' => 1))) {
            $parts = pathinfo($this->zip_path);
            $temp_file_path = $this->TEMP_FILE_PATH . "/" . $parts["basename"];
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip -o $temp_file_path -d $this->TEMP_FILE_PATH");
            if(!file_exists($this->TEMP_FILE_PATH . "/sd_specimen.txt")) {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/sd_specimen.txt")) return;
            }
            $this->text_path["sd_specimen"] = $this->TEMP_FILE_PATH . "/sd_specimen.txt";
            $this->text_path["sd_specimen_identifier"] = $this->TEMP_FILE_PATH . "/sd_specimen_identifier.txt";
            $this->text_path["sd_macro_locality"] = $this->TEMP_FILE_PATH . "/sd_macro_locality.txt";
        }
        else {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return;
        }
    }
}
?>
