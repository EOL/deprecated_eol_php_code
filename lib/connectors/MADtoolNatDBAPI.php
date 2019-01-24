<?php
namespace php_active_record;
/* connector: [mad_natdb.php] */
class MADtoolNatDBAPI
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->debug = array();
        $this->for_mapping = array();
        $this->download_options = array(
            'expire_seconds'     => 60*60*24*30, //expires in 1 month
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->source_csv_path = DOC_ROOT."../other_files/natdb_harvest/";
        $this->spreadsheet_for_mapping = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MAD_tool_NatDB/MADmap.xlsx"; //from Jen (DATA-1754)
    }
    private function initialize_mapping()
    {
        /* un-comment in real operation
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // print_r($this->uris);
        */
        self::initialize_spreadsheet_mapping();
        // print_r($this->valid_set['map__.falster.2015_mm_']); exit("\n222\n");
    }
    function start()
    {
        $this->occurrence_properties = self::get_occurrence_properties();
        
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        self::initialize_mapping();

        // /* un-comment in real operation
        $csv = array('file' => $this->source_csv_path."categorical.csv", 'type' => 'categorical'); //only categorical.csv have 'record type' = taxa
        self::process_extension($csv, "taxa"); //purpose = taxa
        // */
        /* not needed anymore
        $csv = array('file' => $this->source_csv_path."numeric.csv", 'type' => 'numeric'); //only numeric.cs have 'record type' = 'child measurement'
        self::process_extension($csv, "child measurement"); //purpose = child measurement
        print_r($this->childm); //exit("\n-end childm-\n");
        */
        // /*
        $csv = array('file' => $this->source_csv_path."categorical.csv", 'type' => 'categorical');
        self::process_extension($csv, 'mof occurrence child');
        $csv = array('file' => $this->source_csv_path."numeric.csv", 'type' => 'numeric'); //only numeric.cs have 'record type' = 'child measurement'
        self::process_extension($csv, 'mof occurrence child');
        // */
        
        self::main_write_archive();
        $this->archive_builder->finalize(true);
        
        //massage debug for printing
        /*
        $countries = array(); $territories = array();
        if($use_csv = @$this->debug['use.csv']) {
            if($countries = array_keys($use_csv)) asort($countries);
        }
        if($distribution_csv = @$this->debug['distribution.csv']) {
            if($territories = array_keys($distribution_csv)) asort($territories);
        }
        $this->debug = array();
        foreach($countries as $c) $this->debug['use.csv'][$c] = '';
        foreach($territories as $c) $this->debug['distribution.csv'][$c] = '';
        Functions::start_print_debug($this->debug, $this->resource_id);
        */
        print_r($this->debug);
        print_r($this->main);
        // print_r($this->numeric_fields);
        // exit("\n-end for now-\n");
    }
    private function main_write_archive()
    {
        $taxa = array_keys($this->main);
        print_r($taxa);
        foreach($taxa as $species) {
            $taxon_id = self::create_taxon($species);

            if($val = @$this->main[$species]['child measurement']) {
                $child_measurements = self::get_child_measurements($val);
            }
            else $child_measurements = array();
            

            foreach($this->main[$species]['MeasurementOfTaxon=true'] as $mType => $rec3) {
                // echo "\n ------ $mType\n";
                // print_r($rec3);
                foreach($rec3 as $mValue => $rec4) {
                    // echo "\n --------- $mValue\n";
                    // print_r($rec4);
                    $keys = array_keys($rec4);
                    // print_r($keys);
                    $tmp = $keys[0];
                    $samplesize = $rec4[$keys[0]];
                    $metadata = $rec4['r']['md'];
                    $dataset = $rec4['r']['ds'];
                    $mRemarks = $rec4['r']['mr'];
                    $mUnit = $rec4['r']['mu'];
                    $csv_type = $rec4['r']['ty']; //this is either 'c' or 'n'. Came from 'categorical.csv' or 'numerical.csv'.
                    // echo "\n - tmp = [$tmp]\n - metadata = [$metadata]\n - samplesize = [$samplesize]\n";
                    
                    /*Array( --- $mapped_record
                        [variable] => Common_length
                        [value] => 
                        [dataset] => .albouy.2015
                        [unit] => cm
                        [-->] => -->
                        [measurementType] => http://purl.obolibrary.org/obo/CMO_0000013
                        [measurementValue] => 
                        [record type] => MeasurementOfTaxon=true
                        [http://rs.tdwg.org/dwc/terms/measurementUnit] => http://purl.obolibrary.org/obo/UO_0000015
                        [http://rs.tdwg.org/dwc/terms/lifeStage] => 
                        [http://eol.org/schema/terms/statisticalMethod] => http://eol.org/schema/terms/average
                        [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                    )*/
                    if($mapped_record = @$this->valid_set[$tmp]) {}
                    else exit("\nShould not go here...\n");
                    
                    $rek = array();
                    $rek["taxon_id"] = $taxon_id;
                    // $rek["catnum"] = substr($csv['type'],0,1)."_".$rec['blank_1'];
                    // $rek["catnum"] = ""; //bec. of redundant value, non-unique
                    $rek["catnum"] = $csv_type."_".$mValue;
                    
                    $mOfTaxon = "true";
                    $rek['measurementUnit'] = $mUnit;
                    $rek['measurementRemarks'] = $mRemarks;
                    $rek['statisticalMethod'] = $mapped_record['http://eol.org/schema/terms/statisticalMethod'];
                    $rek['lifeStage'] = $mapped_record['http://rs.tdwg.org/dwc/terms/lifeStage'];   //occurrence_property
                    $rek['occurrenceRemarks'] = $metadata;                                          //occurrence_property
                    
                    if($val = @$this->main[$species]['occurrence']) {
                        $rek = self::additional_occurrence_property($val, $rek, $metadata, $dataset);
                    }
                    $occurrenceID = $this->func->add_string_types($rek, $mValue, $mType, $mOfTaxon);

                    $rek = array();
                    $rek["taxon_id"] = $taxon_id;
                    $rek["catnum"] = ''; //can be blank coz occurrenceID is already generated.
                    $rek['occurrenceID'] = $occurrenceID; //this will be the occurrenceID for all mOfTaxon that is equal to 'false'. That is required.
                    if($samplesize > 1) {
                        $mType = 'http://eol.org/schema/terms/SampleSize';
                        $mValue = $samplesize;
                        $this->func->add_string_types($rek, $mValue, $mType, "false");
                    }
                    
                    if($mapped_record['dataset'] == ".benesh.2017") {
                        $mType = 'http://eol.org/schema/terms/TrophicGuild';
                        $mValue = 'http://www.wikidata.org/entity/Q12806437';
                        $this->func->add_string_types($rek, $mValue, $mType, "false");
                    }
                    
                    if($val = $child_measurements) {
                        foreach($child_measurements as $m) {
                            /*Array(
                                        [mType] => http://eol.org/schema/terms/AnnualPrecipitation
                                        [mValue] => 1300
                                        [info] => Array(
                                                [md] => studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae
                                                [mr] => 
                                                [mu] => http://purl.obolibrary.org/obo/UO_0000016
                                                [ds] => .falster.2015
                                                [ty] => n
                                            )
                                    )
                            */
                            if($metadata == $m['info']['md'] && $dataset == $m['info']['ds']) {
                                $mType = $m['mType'];
                                $mValue = $m['mValue'];
                                $this->func->add_string_types($rek, $mValue, $mType, "false");
                            }
                        }
                    }
                    


                }
            }
            
            
        }
    }
    private function additional_occurrence_property($arr, $retx, $metadata_x, $dataset_x)
    {
        /* sample $arr value
        $a['Gadus morhua']['occurrence'] = Array
            (
                "http://rs.tdwg.org/dwc/terms/fieldNotes" => Array
                    (
                        "field wild" => Array
                            (
                                "growingcondition_fw_.falster.2015__" => 15,
                                "r" => Array
                                    (
                                        "md" => "studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae",
                                        "mr" => "FW",
                                        "mu" => "NA"
                                    )
                            )
                    ),
                "sex" => array("male" => array())
            );
        */
        $final = array();
        foreach($arr as $property => $rek1) {
            echo "\nproperty = $property\n";
            print_r($rek1);
            foreach($rek1 as $prop_value => $rek2) {
                if($rek2['r']['md'] == $metadata_x && 
                   $rek2['r']['ds'] == $dataset_x) $final[pathinfo($property, PATHINFO_FILENAME)] = $prop_value;
            }
        }
        if($final) {
            print_r($final);
            foreach($final as $property => $value) {
                if(!in_array($property, $this->occurrence_properties)) continue;
                if(!isset($retx[$property])) $retx[$property] = $value;
                else {
                    if($retx[$property]) $retx[$property] .= ". Addtl: $value";
                    else $retx[$property] = $value;
                }
            }
            print_r($retx);
        }
        return $retx;
    }
    private function create_taxon($species)
    {
        /*[Tsuga canadensis] => Array(
                [ancestry] => Array(
                        [Family] => Pinaceae
                        [Genus] => Tsuga
                        [Phylum] => Gymnosperms
                    )
            )*/
        $taxon_id = str_replace(" ", "_", strtolower($species));
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $taxon_id;
        $taxon->scientificName  = $species;
        if($val = @$this->main[$species]['ancestry']['Family']) $taxon->family = $val;
        if($val = @$this->main[$species]['ancestry']['Genus']) $taxon->genus = $val;
        if($val = @$this->main[$species]['ancestry']['Phylum']) $taxon->phylum = $val;
        // $taxon->taxonRank             = '';
        // $taxon->furtherInformationURL = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        return $taxon_id;
    }
    private function process_extension($csv, $purpose)
    {
        $i = 0;
        $file = Functions::file_open($csv['file'], "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            $row = self::clean_html($row); // print_r($row);
            $i++; if(($i % 300000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
                // print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n"); exit;
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //important step
                // print_r($rec); //exit;
                self::process_record($rec, $csv, $purpose);
            } //main records
            // if($i > 5) break;
        } //main loop
        fclose($file);
    }
    private function process_record($rec, $csv, $purpose)
    {
        /*Array(
            [blank_1] => 1
            [species] => abudefduf vaigiensis
            [metadata] => id:133;Super_class:osteichthyen;Order:Perciformes;Family:Pomacentridae;Genus:Abudefduf
            *[variable] => IUCN_Red_List_Category
            *[value] => np
            *[units] => NA
            *[dataset] => .albouy.2015
        )
        If the columns variable, value, dataset, and unit match the mapping, generate a record using the fields on the right of the mapping. 
        Where the mapping has nothing in the value field, any value from the source file will do, 
        and should be copied into the measurementValue in the created record. 
        This is mostly for numeric records.
        */
        
        /* generating index value $tmp for $this->valid_set */
        if(isset($this->numeric_fields[$rec['variable']])) $value = "";
        else                                               $value = $rec['value'];
        $tmp = $rec['variable']."_".$value."_".$rec['dataset']."_".self::blank_if_NA($rec['units'])."_";
        $tmp = strtolower($tmp);
        // echo "\n[$tmp]"; exit;
        
        if($mapped_record = @$this->valid_set[$tmp]) {
            if(stripos($rec['species'], "unknown") !== false) return; //string is found
            
            /* good debug
            if($rec['variable'] == "growingCondition") {
                print_r($rec); print_r($mapped_record); exit("\n-end muna-\n");
                @$this->debug[$rec['variable']][$rec['value']][$rec['dataset']] = $rec['units'];
            }
            */
            if($rec['species'] != 'acer_pensylvanicum') return; //debug only
            /*
            "acer_pensylvanicum" -- has MOF, occurrence, child measurement - best for testing
            "abies_sachalinensis" -- with occurrence
            "Catharus fuscescens" -- has MOF, occurrence, good for testing
            "Tsuga canadensis" -- has taxa
            "ctenomys_minutus" -- has special char in metadata "Cherem/Maur?cio"
            */
            
            if($purpose == "taxa") {
                if($mapped_record['record type'] == 'taxa') self::assign_ancestry($rec, $mapped_record);
                return;
            }
            elseif($purpose == "mof occurrence child") {
                // /* actual record assignment
                $record_type = $mapped_record['record type'];
                if($record_type == 'taxa') return;

                $mType = $mapped_record['measurementType'];
                // $mOfTaxon = ($record_type == "MeasurementOfTaxon=true") ? "true" : "";
                $mValue   = ($mapped_record['measurementValue'] != "")                             ? $mapped_record['measurementValue']                             : $rec['value'];
                $mUnit    = ($mapped_record['http://rs.tdwg.org/dwc/terms/measurementUnit'] != "") ? $mapped_record['http://rs.tdwg.org/dwc/terms/measurementUnit'] : $rec['units'];
                $mRemarks = ($mapped_record['http://rs.tdwg.org/dwc/terms/measurementRemarks'] != "") ? $mapped_record['http://rs.tdwg.org/dwc/terms/measurementRemarks'] : $rec['value'];
                if($mValue == $mRemarks) $mRemarks = "";

                @$this->main[$rec['species']][$record_type][$mType][$mValue][$tmp]++;
                @$this->main[$rec['species']][$record_type][$mType][$mValue]['r'] = array('md' => $rec['metadata'], 'mr' => $mRemarks, 'mu' => $mUnit, 
                                                                                          'ds' => $rec['dataset'], 'ty' => substr($csv['type'],0,1));

                // if(isset($this->numeric_fields[$rec['variable']])) {} --> might be an overkill to use $this->numeric_fields
                // */
                return;
            }
            /* obsolete
            elseif($purpose == "child measurement") {
                if($mapped_record['record type'] == 'child measurement') self::assign_child_measurement($rec, $mapped_record);
            }
            */
            
            /*  ------------------------------------------------------- all these below here are only debug purposes only -------------------------------------------------------
            // if($mapped_record['record type'] == 'child measurement') {
                if($rec['species'] == 'acer_pensylvanicum') {
                    @$this->debug['test_taxon'][$mapped_record['record type']][$mapped_record['variable']][$rec['value']]++;
                    // print_r($rec); print_r($mapped_record); 
                }
                else return;
            // }
            // else return;
            */
            
            // print_r($rec); print_r($mapped_record); exit;
            /*
            if($rec['species'] == 'Catharus fuscescens' || $rec['species'] == 'catharus_fuscescens') {
                // print_r($rec); print_r($mapped_record); 
                @$this->debug['test_taxon'][$mapped_record['record type']]++;
                if($mapped_record['record type'] == 'occurrence') {
                    print_r($rec); print_r($mapped_record); 
                }
                return;
            }
            */
            /*
            // if($rec['metadata'] == 'id:133;Super_class:osteichthyen;Order:Perciformes;Family:Pomacentridae;Genus:Abudefduf')
            // if($rec['metadata'] == 'Species.common.name:Veery' && $rec['dataset'] == ".brown.2015")
            if($rec['species'] == 'Catharus fuscescens') //has MOF and occurrence, good for testing
            // if($rec['species'] == 'Catharus fuscescens' && $mapped_record['variable'] == "Migration.Strategy")
            {
                // print_r($rec); print_r($mapped_record); 
                @$this->debug['test_taxon'][$mapped_record['record type']][$mapped_record['variable']]++;
                if($mapped_record['record type'] == 'MeasurementOfTaxon=true') { //MeasurementOfTaxon=true OR occurrence
                    print_r($rec); print_r($mapped_record); 
                }
                // return;
            }
            else return;
            */
            /*
            // if($mapped_record['record type'] == 'child measurement') {
            if($rec['species'] == 'Tsuga heterophylla') {
                @$this->debug['test_taxon'][$mapped_record['record type']][$mapped_record['variable']]++;
                print_r($rec); print_r($mapped_record); //exit;
                return;
            }
            */
            
            /*
            if($mapped_record['measurementType'] == 'http://rs.tdwg.org/dwc/terms/lifeStage') {
                print_r($rec); print_r($mapped_record); 
                return;
            }
            */
            
            // echo "\n[$tmp]"; print_r($mapped_record); print_r($rec); exit("\n111\n");
            /*[common_length__.albouy.2015_cm_]Array( --- $mapped_record
                [variable] => Common_length
                [value] => 
                [dataset] => .albouy.2015
                [unit] => cm
                [-->] => -->
                [measurementType] => http://purl.obolibrary.org/obo/CMO_0000013
                [measurementValue] => 
                [record type] => MeasurementOfTaxon=true
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => http://purl.obolibrary.org/obo/UO_0000015
                [http://rs.tdwg.org/dwc/terms/lifeStage] => 
                [http://eol.org/schema/terms/statisticalMethod] => http://eol.org/schema/terms/average
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
            )
            Array( --- $rec
                [blank_1] => 1
                [species] => abudefduf vaigiensis
                [metadata] => id:133;Super_class:osteichthyen;Order:Perciformes;Family:Pomacentridae;Genus:Abudefduf
                [variable] => Common_length
                [value] => 15
                [units] => cm
                [dataset] => .albouy.2015
            )
            */
        }
    }
    private function assign_child_measurement($rec, $mapped_record)
    {
        /*Array( --- $rec
            [blank_1] => 1999010
            [species] => acer_pensylvanicum
            [metadata] => studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae
            [variable] => map
            [value] => 1300
            [units] => mm
            [dataset] => .falster.2015
        )
        Array( --- $mapped_record
            [variable] => map
            [value] => 
            [dataset] => .falster.2015
            [unit] => mm
            [-->] => -->
            [measurementType] => http://eol.org/schema/terms/AnnualPrecipitation
            [measurementValue] => 
            [record type] => child measurement
            [http://rs.tdwg.org/dwc/terms/measurementUnit] => http://purl.obolibrary.org/obo/UO_0000016
            [http://rs.tdwg.org/dwc/terms/lifeStage] => 
            [http://eol.org/schema/terms/statisticalMethod] => 
            [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
        )
        */
        $mType  = $mapped_record['measurementType'];
        $mValue = ($mapped_record['measurementValue'] != "")                             ? $mapped_record['measurementValue']                             : $rec['value'];
        $mUnit  = ($mapped_record['http://rs.tdwg.org/dwc/terms/measurementUnit'] != "") ? $mapped_record['http://rs.tdwg.org/dwc/terms/measurementUnit'] : $rec['units'];
        
        $this->childm[$rec['species']][$mType][$mValue][$mUnit] = array('metadata' => $rec['metadata'], 'dataset' => $rec['dataset']);
    }
    private function assign_ancestry($rec, $mapped_record)
    {
        /*Array( --- $rec
            [blank_1] => 143100
            [species] => Tsuga heterophylla
            [metadata] => Family:Pinaceae;Genus:Tsuga;Phylum:G
            [variable] => Phylum
            [value] => G
            [units] => NA
            [dataset] => .ameztegui.2016
        )
        Array( --- $mapped_record
            [variable] => Phylum
            [value] => G
            [dataset] => .ameztegui.2016
            [unit] => 
            [-->] => -->
            [measurementType] => http://rs.tdwg.org/dwc/terms/phylum
            [measurementValue] => Gymnosperms
            [record type] => taxa
            [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
            [http://rs.tdwg.org/dwc/terms/lifeStage] => 
            [http://eol.org/schema/terms/statisticalMethod] => 
            [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
        )
        */
        if    ($val = $mapped_record['measurementValue']) $value = $val;
        elseif($val = $rec['value'])                      $value = $val;
        $this->main[$rec['species']]['ancestry'][$rec['variable']] = $value;
    }
    private function blank_if_NA($str)
    {
        if($str == "NA") return "";
        else return $str;
    }
    private function clean_html($arr)
    {
        $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
    }
    private function initialize_spreadsheet_mapping()
    {
        $final = array();
        $options = $this->download_options;
        $options['file_extension'] = 'xlsx';
        $local_xls = Functions::save_remote_file_to_local($this->spreadsheet_for_mapping, $options);
        require_library('XLSParser');
        $parser = new XLSParser();
        debug("\n reading: " . $local_xls . "\n");
        $map = $parser->convert_sheet_to_array($local_xls);
        $fields = array_keys($map);
        // print_r($map);
        print_r($fields); //exit;
        // foreach($fields as $field) echo "\n$field: ".count($map[$field]); //debug only
        /* get valid_set - the magic 4 fields */
        $i = -1;
        foreach($map['variable'] as $var) {
            $i++;
            if(in_array($var, array("Location.Code"))) continue;
            $tmp = $var."_".$map['value'][$i]."_".$map['dataset'][$i]."_".$map['unit'][$i]."_";
            $tmp = strtolower($tmp);
            $valid_set[$tmp] = self::get_corresponding_rek_from_mapping_spreadsheet($i, $fields, $map);
            //get numeric fields (e.g. Maximum_length). To be used when figuring out which are valid sets, where numeric values should be blank.
            if(!$map['value'][$i]) $this->numeric_fields[$var] = '';
        }
        // print_r($valid_set); exit;
        $this->valid_set = $valid_set;
        unlink($local_xls);
    }
    private function get_corresponding_rek_from_mapping_spreadsheet($i, $fields, $map)
    {
        $final = array();
        foreach($fields as $field) $final[$field] = $map[$field][$i];
        return $final;
    }
    private function get_child_measurements($arr)
    {
        $final = array();
        foreach($arr as $mType => $rek1) {
            $rec = array();
            foreach($rek1 as $mValue => $rek2) {
                $rec['mType'] = $mType;
                $rec['mValue'] = $mValue;
                $rec['info'] = $rek2['r'];
            }
            if($rec) $final[] = $rec;
        }
        return $final;
    }
    function get_occurrence_properties()
    {
        if($xml = Functions::lookup_with_cache("https://editors.eol.org/other_files/ontology/occurrence_extension.xml", $this->download_options)) {
            if(preg_match_all("/<property name=\"(.*?)\"/ims", $xml, $arr)) {
                print_r($arr[1]);
                return $arr[1];
            }
        }
    }
    /* ######################################################################################################################################### */
    /* ######################################################################################################################################### */
    /* ######################################################################################################################################### */
    private function get_string_uri($string)
    {
        switch ($string) { //put here customized mapping
            case "NR":                return false; //"DO NOT USE";
            // case "United States of America":    return "http://www.wikidata.org/entity/Q30";
        }
        if($string_uri = @$this->uris[$string]) return $string_uri;
    }
    private function separate_strings($str, $ret, $group)
    {
        $arr = explode(";", $str);
        $arr = array_map('trim', $arr);
        foreach($arr as $item) {
            if(!isset($this->uris[$item])) $ret[$group][$item] = '';
                                        // $ret[$group][$item] = '';
        }
        return $ret;
    }
    private function fill_up_blank_fieldnames($fields)
    {
        $i = 0;
        foreach($fields as $field) {
            if($field) $final[$field] = '';
            else {
                $i++;
                $final['blank_'.$i] = '';
            } 
        }
        return array_keys($final);
    }
}
?>
