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
            'expire_seconds'     => 60*60*24, //expires in 1 day
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        // $this->download_options['expire_seconds'] = 0; //debug only
        $this->source_csv_path = DOC_ROOT."../other_files/natdb_harvest/";
        $this->spreadsheet_for_mapping = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MAD_tool_NatDB/ver_1/MADmap.xlsx"; //from Jen (DATA-1754) - OBSOLETE
        $this->spreadsheet_for_mapping = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MAD_tool_NatDB/ver_2/MADmap_Jan2020.xlsx"; //from Jen (DATA-1754) - updated mappings
        /* Reminder 'unit' to use in spreadsheet. Will need to edit Jen's spreadsheet version in Jira, if u want to download it again.
        seed_mass -- mg/seed
        wood_density -- g cm^3
        Host.no -- #
        */
        $this->citations_tsv_file = 'https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/MAD_tool_NatDB/citations_Eli_edited.tsv';
    }
    private function initialize_mapping()
    {
        /* seems not used at all...
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // print_r($this->uris);
        */
        self::initialize_citations_file();
        self::initialize_spreadsheet_mapping();
        // print_r($this->valid_set['map__.falster.2015_mm_']); exit("\n222\n");
    }
    function start()
    {
        /* $this->occurrence_properties = self::get_occurrence_properties(); --- You can now put arbitrary columns in the occurrences file */
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        $this->func->initialize_terms_remapping(); //for DATA-1841 terms remapping
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
        // print_r($this->debug);
        // print_r($this->main);
        // print_r($this->numeric_fields);
        // exit("\n-end for now-\n");
        Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function main_write_archive()
    {
        $taxa = array_keys($this->main);
        // print_r($taxa);
        foreach($taxa as $species) {
            $taxon_id = self::create_taxon($species);

            if($val = @$this->main[$species]['child measurement']) {
                $child_measurements = self::get_child_measurements($val);
            }
            else $child_measurements = array();
            
               if(!@$this->main[$species]['MeasurementOfTaxon=true']) continue;
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
                    
                    if(in_array($mType, array("http://www.wikidata.org/entity/Q1053008", "http://eol.org/schema/terms/TrophicGuild"))) {
                        $rek['lifeStage'] = $mapped_record['http://rs.tdwg.org/dwc/terms/lifeStage'];  //measurement_property, yes this is arbitrary field in MoF
                    }
                    else $rek['occur']['lifeStage'] = $mapped_record['http://rs.tdwg.org/dwc/terms/lifeStage'];  //occurrence_property
                    $rek['occur']['occurrenceRemarks'] = $metadata;                                              //occurrence_property

                    /* no more SampleSize in occurrence, move it to MoF child records. https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=65607&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65607
                    if($samplesize > 1) { //you can now add arbitrary cols in occurrence
                        $rek['occur']['SampleSize'] = $samplesize;              //occurrence_property - http://eol.org/schema/terms/SampleSize
                    }
                    */
                    
                    if($val = @$this->main[$species]['occurrence']) {
                        /* no more PATO_0000146, EO_0007196 in occurrence, move it to MoF child records. https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=65607&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65607
                        $rek = self::additional_occurrence_property($val, $rek, $metadata, $dataset);
                        */
                    }
                    $rek['referenceID'] = self::generate_reference($dataset);
                    $rek = self::further_adjustments($rek, $mValue);
                    $ret_MoT_true = $this->func->pre_add_string_types($rek, $mValue, $mType, $mOfTaxon); //1
                    $occurrenceID = $ret_MoT_true['occurrenceID'];
                    $measurementID = $ret_MoT_true['measurementID'];

                    // /* add child to MoF: SampleSize
                    $rek = array();
                    $rek["taxon_id"] = $taxon_id; //you don't need it
                    $rek["catnum"] = ''; //you don't need it
                    $rek['occurrenceID'] = '';
                    $rek['measurementOfTaxon'] = '';
                    $rek["parentMeasurementID"] = $measurementID;
                    if($samplesize > 1) {
                        $mType_var = 'http://eol.org/schema/terms/SampleSize';
                        $mValue_var = $samplesize;
                        $this->func->add_string_types($rek, $mValue_var, $mType_var, "");
                    }
                    // */
                    
                    // /* add child to MoF: PATO_0000146 and EO_0007196 ----------
                    if($val = @$this->main[$species]['occurrence']) {
                        self::add_other_child_records($val, $taxon_id, $measurementID);
                    }
                    // ---------- */
                    
                    if($mapped_record['dataset'] == ".benesh.2017") {
                        $mType_var = 'http://eol.org/schema/terms/TrophicGuild';
                        $mValue_var = 'http://www.wikidata.org/entity/Q12806437';
                        $rek = array();
                        $rek["taxon_id"] = $taxon_id;
                        $rek["catnum"] = $csv_type."_".$mValue_var;
                        $rek['lifeStage'] = $mapped_record['http://rs.tdwg.org/dwc/terms/lifeStage'];  //measurement_property, yes this is arbitrary field in MoF
                        $rek['referenceID'] = self::generate_reference($mapped_record['dataset']);
                        $rek = self::further_adjustments($rek, $mValue_var);
                        $this->func->pre_add_string_types($rek, $mValue_var, $mType_var, "true"); //2
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
                                $rek = array();
                                $rek["taxon_id"] = $taxon_id;
                                $rek["catnum"] = ''; //can be blank coz there'll be no occurrence for child measurements anyway.
                                $rek['occur']['occurrenceID'] = ''; //child measurements don't have occurrenceID
                                $rek['parentMeasurementID'] = $measurementID;
                                $mType_var = $m['mType'];
                                $mValue_var = $m['mValue'];
                                if($val = $m['info']['mu']) $rek['measurementUnit'] = $val;
                                if($val = $m['info']['mr']) $rek['measurementRemarks'] = $val;
                                $rek['referenceID'] = self::generate_reference($dataset);
                                $rek = self::further_adjustments($rek, $mValue_var);
                                $this->func->pre_add_string_types($rek, $mValue_var, $mType_var, "child"); //3
                            }
                        }
                    }

                }
            }
            
        }
    }
    private function further_adjustments($rek, $mValue)
    {   /* per: https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=65051&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65051
        where measurementValue = http://purl.obolibrary.org/obo/PATO_0001733 OR http://purl.obolibrary.org/obo/PATO_0001731
        that value also appears in measurementRemarks. Please remove it. I can't think of anything else that would need to be there; 
        they can be left blank. Thanks! */
        if(in_array($mValue, array('http://purl.obolibrary.org/obo/PATO_0001733', 'http://purl.obolibrary.org/obo/PATO_0001731'))) $rek['measurementRemarks'] = '';
        if(@$rek['measurementUnit'] == 'kg/kg') $rek['measurementUnit'] = 'http://purl.obolibrary.org/obo/UO_0010006';
        return $rek;
    }
    private function add_other_child_records($arr, $taxon_id, $measurementID)
    {
        $ret = array();
        foreach($arr as $mType => $rec) {
            if(in_array($mType, array('http://purl.obolibrary.org/obo/PATO_0000146', 'http://purl.obolibrary.org/obo/EO_0007196'))) {
            // if(in_array($mType, array('http://purl.obolibrary.org/obo/PATO_0000146'))) {
            // if(in_array($mType, array('http://purl.obolibrary.org/obo/EO_0007196'))) {
                $ret[$mType] = $rec;
                
                $rek = array();
                $rek["taxon_id"] = $taxon_id; //you don't need it
                $rek["catnum"] = ''; //you don't need it
                $rek['occurrenceID'] = '';
                $rek['measurementOfTaxon'] = '';
                $rek["parentMeasurementID"] = $measurementID;
                foreach($rec as $value => $record) {
                    $mType_var = $mType;
                    $mValue_var = $value;
                    $rek['measurementUnit'] = $record['r']['mu'];
                    $rek['measurementRemarks'] = $record['r']['md'];
                    $this->func->add_string_types($rek, $mValue_var, $mType_var, "");
                }
            }
        }
        /*
        if($ret) {
            echo "\n--------------\n";
            print_r($ret);
            echo "\n=============\n";
        }
        */
        /*Array(
            [http://purl.obolibrary.org/obo/PATO_0000146] => Array(
                    [25] => Array(
                            [temp__.benesh.2017_degc_] => 1
                            [r] => Array(
                                    [md] => Parasite.genus:Acanthocephalus;Parasite.group:acanthocephalan;Development.remarks:NA;Size.reported.as:NA;Size.remarks:NA;Author:Andryuk;Year:1979;Journal:Parazitologiya;Volume:13;Pages:530-539
                                    [mr] => 
                                    [mu] => http://purl.obolibrary.org/obo/UO_0000027
                                    [ds] => .benesh.2017
                                    [ty] => n
                                )
                        )
                    [22] => Array(
                            [temp__.benesh.2017_degc_] => 3
                            [r] => Array(
                                    [md] => Parasite.genus:Acanthocephalus;Parasite.group:acanthocephalan;Development.remarks:NA;Size.reported.as:range;Size.remarks:n for max is number of measured cystacanths from low int infections (<3);Author:Pilecka-Rapacz;Year:1986;Journal:Acta Parasitol.;Volume:26;Pages:233-250
                                    [mr] => 
                                    [mu] => http://purl.obolibrary.org/obo/UO_0000027
                                    [ds] => .benesh.2017
                                    [ty] => n
                                )
                        )

        Array(
            [http://purl.obolibrary.org/obo/EO_0007196] => Array(
                    [full light (bare ground)] => Array(
                            [light_full light (bare ground)_.falster.2015__] => 42
                            [r] => Array(
                                    [md] => studyName:Camac0000;location:Bogong High Plains, Victoria, Australia;latitude:-36.90574;longitude:147.27787;species:Grevillea australis;family:Proteaceae
                                    [mr] => 
                                    [mu] => 
                                    [ds] => .falster.2015
                                    [ty] => c
                                )
                        )
                )
        )*/
    }
    private function additional_occurrence_property($arr, $retx, $metadata_x, $dataset_x)
    {   /* sample $arr value
        $a['Gadus morhua']['occurrence'] = Array(
                "http://rs.tdwg.org/dwc/terms/fieldNotes" => Array(
                        "field wild" => Array(
                                "growingcondition_fw_.falster.2015__" => 15,
                                "r" => Array(
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
            // echo "\nproperty = $property\n";
            // print_r($rek1);
            foreach($rek1 as $prop_value => $rek2) {
                if($rek2['r']['md'] == $metadata_x && 
                   $rek2['r']['ds'] == $dataset_x) $final[pathinfo($property, PATHINFO_FILENAME)] = $prop_value;
            }
        }
        if($final) {
            // print_r($final);
            foreach($final as $property => $value) {
                /* per Jen: You can put arbitrary columns in the occurrences file now, not just a set list of "valid" fields. https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=63183&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63183
                if(!in_array($property, $this->occurrence_properties)) continue;
                */
                if(!isset($retx['occur'][$property])) $retx['occur'][$property] = $value;
                else {
                    if($retx['occur'][$property]) $retx['occur'][$property] .= ". Addtl: $value";
                    else                          $retx['occur'][$property] = $value;
                }
            }
            // print_r($retx);
            // if(in_array($property, array('PATO_0000146'))) { //debug only
            // if(in_array($property, array('EO_0007196'))) { //debug only
            //     print_r($arr);
            //     print_r($final);
            //     print_r($retx);
            // }
        }
        return $retx;
        /*Array(
            [taxon_id] => grevillea_australis
            [catnum] => n_0.00137
            [measurementUnit] => http://purl.obolibrary.org/obo/UO_0000008
            [measurementRemarks] => at base
            [statisticalMethod] => http://www.ebi.ac.uk/efo/EFO_0001444
            [occur] => Array(
                    [lifeStage] => 
                    [occurrenceRemarks] => studyName:Camac0000;location:Bogong High Plains, Victoria, Australia;latitude:-36.90574;longitude:147.27787;species:Grevillea australis;family:Proteaceae
                    [fieldNotes] => field wild
                    [EO_0007196] => full light (bare ground)
                )
        )
        */
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
        $species = $rec['species'];
        $species = trim(str_replace("_", " ", ucfirst($species)));
        //remove _sp
        if(substr($species, -3) == "_sp") $species = substr($species, 0, strlen($species)-3);
        $rec['species'] = trim($species);
        
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
            // if($rec['species'] != 'acer_pensylvanicum') return; //debug only
            // if($rec['species'] != 'Acer pensylvanicum') return; //debug only
            // if($rec['species'] != 'Acer saccharum') return; //debug only
            // if($rec['species'] != 'Acer campestre') return; //debug only - with seed_mass & wood_density
            // if($rec['species'] != 'Abbreviata caucasica') return; //debug only - with Host.no
            // if($rec['species'] != 'Anguilla anguilla') return; //debug only
            
            // if(in_array($rec['species'], array('Acer pensylvanicum', 'Acer saccharum', 'Acer campestre', 'Abbreviata caucasica', 'Anguilla anguilla'))) {}
            // else return;
            
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
                if(in_array($mUnit, array('NA', '#'))) $mUnit = '';
                $mRemarks = ($mapped_record['http://rs.tdwg.org/dwc/terms/measurementRemarks'] != "") ? $mapped_record['http://rs.tdwg.org/dwc/terms/measurementRemarks'] : $rec['value'];
                if($mValue == $mRemarks) $mRemarks = "";
                $mRemarks = self::mRemarks_map($mRemarks, $rec['dataset'], $mType);
                
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
    private function mRemarks_map($str, $dataset, $mType)
    {   /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=63188&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63188
        Interesting! You found a second use of some two letter codes that I'd only seen as primary measurementValue. Another fiddly one, then:
        where dataset =.falster.2015
        in measurementRemarks, please map these four values as follows:
        DA -> http://purl.obolibrary.org/obo/PATO_0001731
        DG -> http://purl.obolibrary.org/obo/PATO_0001731
        EA -> http://purl.obolibrary.org/obo/PATO_0001733
        EG -> http://purl.obolibrary.org/obo/PATO_0001733
        */
        if($dataset == ".falster.2015") {
            if    (in_array($str, array('DA', 'DG'))) $final = 'http://purl.obolibrary.org/obo/PATO_0001731';
            elseif(in_array($str, array('EA', 'EG'))) $final = 'http://purl.obolibrary.org/obo/PATO_0001733';
            else $final = $str;
        }
        else $final = $str;
        /* debug only
        if($final == "DA") {
            echo "\nstr: [$str]\n";
            echo "\ndataset: [$dataset]\n";
            echo "\nmType: [$mType]\n";
        }
        */
        /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=63189&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63189
        Some measurementRemarks to remove. Keeping original values in this field if available is nearly always helpful, but not in these cases:
        where measurementType is one of these
        http://purl.obolibrary.org/obo/GO_0000003
        http://purl.obolibrary.org/obo/GO_0007530
        http://purl.obolibrary.org/obo/IDOMAL_0002084
        and (measurementRemarks= yes OR measurementRemarks= no)
        please take the string out, leaving measurementRemarks blank. No need to map to anything new
        */
        if(in_array($mType, array("http://purl.obolibrary.org/obo/GO_0000003", "http://purl.obolibrary.org/obo/GO_0007530", "http://purl.obolibrary.org/obo/IDOMAL_0002084"))) {
            if(in_array($final, array("yes", "no"))) $final = "";
        }
        return $final;
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
        if($mUnit == "NA") $mUnit = '';
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
        $html = Functions::conv_to_utf8($html);
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
        $options['expire_seconds'] = 60;
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
    /* working but no longer needed, since you can now put arbitrary fields in occurrence extension.
    function get_occurrence_properties()
    {
        if($xml = Functions::lookup_with_cache("https://editors.eol.org/other_files/ontology/occurrence_extension.xml", $this->download_options)) {
            if(preg_match_all("/<property name=\"(.*?)\"/ims", $xml, $arr)) {
                print_r($arr[1]);
                return $arr[1];
            }
        }
    }
    */
    private function generate_reference($dataset)
    {
        if($ref = @$this->refs[$dataset]) {
            /* [.aubret.2015] => Array(
                    *[URL to paper] => http://www.nature.com/hdy/journal/v115/n4/full/hdy201465a.html
                    *[DOI] => 10.1038/hdy.2014.65
                    [Journal] => Heredity
                    *[Publisher] => Springer Nature
                    *[Title] => Island colonisation and the evolutionary rates of body size in insular neonate snakes
                    *[Author] => Aubret
                    [Year] => 2015
                    *[author_year] => .aubret.2015
                    [BibTeX citation] => @article{aubret2015,title={Island colonisation and the evolutionary rates of body size in insular neonate snakes},author={Aubret, F},journal={Heredity},volume={115},number={4},pages={349--356},year={2015},publisher={Nature Publishing Group}}
                    [Taxonomy ] => Animalia/Serpentes
                    [Person] => Katie
                    [WhoWroteFunction] => 
                    [Everything Completed?] => 
                    [] => 
                    *[full_ref] => Aubret. (2015). Island colonisation and the evolutionary rates of body size in insular neonate snakes. Heredity. Springer Nature.
                )
            */
            if($ref_id = @$ref['author_year']) {
                $r = new \eol_schema\Reference();
                $r->identifier = $ref_id;
                $r->full_reference = $ref['full_ref'];
                $r->uri = $ref['URL.to.paper'];
                $r->doi = $ref['DOI'];
                $r->publisher = $ref['Publisher'];
                $r->title = $ref['Title'];
                $r->authorList = $ref['Author'];
                if(!isset($this->reference_ids[$ref_id])) {
                    $this->reference_ids[$ref_id] = '';
                    $this->archive_builder->write_object_to_file($r);
                }
                return $ref_id;
            }
        }
        else $this->debug['no citations yet'][$dataset] = '';
    }
    private function initialize_citations_file()
    {
        /* orig but needed some manual massaging by Eli
        $tmp_file = $this->source_csv_path."/citations.tsv";
        If you use this, be sure remove the unlink() command below.
        */
        
        $tmp_file = Functions::save_remote_file_to_local($this->citations_tsv_file, $this->download_options);
        
        $i = 0;
        if(!file_exists($tmp_file)) {
            exit("\nFile does not exist: [$tmp_file]\n");
        }
        foreach(new FileIterator($tmp_file) as $line => $row) {
            $row = Functions::conv_to_utf8($row);
            $i++; 
            if($i == 1) {
                // URL.to.paper
                $fields = explode("\t", $row);
                print_r($fields); //exit;
            }
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                // print_r($tmp); //exit;
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                
                // print_r($rec); //exit;
                /*Array(
                    [URL to paper] => http://onlinelibrary.wiley.com/doi/10.1111/nph.13935/abstract
                    [DOI] => 10.1111/nph.13935
                    [Journal] => New Phytologist
                    [Publisher] => Wiley
                    [Title] => Plasticity in plant functional traits is shaped by variability in neighbourhood species composition
                    [Author] => Abakumova
                    [Year] => 2016
                    [author_year] => .abakumova.2016
                    [BibTeX citation] => @article {NPH:NPH13935,author = {Abakumova, Maria and Zobel, Kristjan and Lepik, Anu and Semchenko, Marina},title = {Plasticity in plant functional traits is shaped by variability in neighbourhood species composition},journal = {New Phytologist},volume = {211},number = {2},issn = {1469-8137},url = {http://dx.doi.org/10.1111/nph.13935},doi = {10.1111/nph.13935},pages = {455--463},keywords = {biotic environment, competition, functional traits, local adaptation, neighbour recognition, phenotypic plasticity, selection, spatial patterns},year = {2016},note = {2015-20353},}
                    [Taxonomy ] => Plantae
                    [Person] => Anne
                    [WhoWroteFunction] => 
                    [Everything Completed?] => 
                    [] => 
                )
                Last, F. M. (Year, Month Date Published). Article title. Retrieved from URL
                Last, F. M. (Year Published) Book. City, State: Publisher.
                */
                $full_ref = "$rec[Author]. ($rec[Year]). $rec[Title]. $rec[Journal]. $rec[Publisher].";
                $full_ref = trim(Functions::remove_whitespace($full_ref));
                $rec['full_ref'] = $full_ref;
                $this->refs[$rec['author_year']] = $rec;
            }
        }
        unlink($tmp_file);
        // exit("\nstop muna\n");
        /* as of Oct 14, 2019: 
        no citations yet
        ----- .albouy.2015  total: 1
        ----- .anderson.2015  total: 1
        */
        
        /* Latest citations.tsv from repo has ".albuoy.2015" */
        $this->refs['.albouy.2015'] = $this->refs['.albuoy.2015'];
        
        /* added by Jen: https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=64033&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64033
        Meanwhile, any luck on those dangling references? If not, I have a pretty good guess we can add manually:
        */
        $rek = array();
        $rek['author_year'] = ".anderson.2015";
        $rek['full_ref'] = "Jill T Anderson, Zachariah J. Gezon. 2015. Plasticity in functional traits in the context of climate change: a case study of the subalpine forb Boechera stricta (Brassicaceae). Global change biology 2015. DOI:10.1111/gcb.12770";
        $rek['URL.to.paper'] = '';
        $rek['DOI'] = "DOI:10.1111/gcb.12770";
        $rek['Publisher'] = "";
        $rek['Title'] = "Plasticity in functional traits in the context of climate change: a case study of the subalpine forb Boechera stricta (Brassicaceae)";
        $rek['Author'] = "Jill T Anderson, Zachariah J. Gezon.";
        $this->refs[$rek['author_year']] = $rek;

        /* No need for manual entry anymore for these two:
        $rek = array();
        $rek['author_year'] = '.albouy.2015';
        $rek['full_ref'] = "Albouy, C. , Lasram, F. B., Velez, L. , Guilhaumon, F. , Meynard, C. N., Boyer, S. , Benestan, L. , Mouquet, N. , Douzery, E. , Aznar, R. , Troussellier, M. , Somot, S. , Leprieur, F. , Le Loc'h, F. and Mouillot, D. (2015), FishMed: traits, phylogeny, current and projected species distribution of Mediterranean fishes, and environmental data. Ecology, 96: 2312-2313. doi:10.1890/14-2279.1";
        $rek['URL.to.paper'] = '';
        $rek['DOI'] = 'doi:10.1890/14-2279.1';
        $rek['Publisher'] = "";
        $rek['Title'] = "FishMed: traits, phylogeny, current and projected species distribution of Mediterranean fishes, and environmental data";
        $rek['Author'] = "Albouy, C. , Lasram, F. B., Velez, L. , Guilhaumon, F. , Meynard, C. N., Boyer, S. , Benestan, L. , Mouquet, N. , Douzery, E. , Aznar, R. , Troussellier, M. , Somot, S. , Leprieur, F. , Le Loc'h, F. and Mouillot, D.";
        $this->refs[$rek['author_year']] = $rek;

        $rek = array();
        $rek['author_year'] = ".goncalves.2018";
        $rek['full_ref'] = "Gonçalves, F. , Bovendorp, R. S., Beca, G. , Bello, C. , et al. (2018), ATLANTIC MAMMAL TRAITS: a data set of morphological traits of mammals in the Atlantic Forest of South America. Ecology, 99: 498-498. doi:10.1002/ecy.2106";
        $rek['URL.to.paper'] = '';
        $rek['DOI'] = "doi:10.1002/ecy.2106";
        $rek['Publisher'] = "";
        $rek['Title'] = "ATLANTIC MAMMAL TRAITS: a data set of morphological traits of mammals in the Atlantic Forest of South America";
        $rek['Author'] = "Gonçalves, F. , Bovendorp, R. S., Beca, G. , Bello, C. , et al.";
        $this->refs[$rek['author_year']] = $rek;
        */
        
        // print_r($this->refs); exit;
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
    /* ######################################################################################################################################### */
    /* ######################################################################################################################################### */
    /* ######################################################################################################################################### */
    /* seems not used at all... Dec 2, 2019 commented.
    private function get_string_uri($string)
    {
        switch ($string) { //put here customized mapping
            case "NR":                return false; //"DO NOT USE";
            // case "United States of America":    return "http://www.wikidata.org/entity/Q30";
        }
        if($string_uri = @$this->uris[$string]) return $string_uri;
    }
    */
}
?>
