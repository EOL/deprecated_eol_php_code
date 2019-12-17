<?php
namespace php_active_record;
/* connector: [conservation_evidence.php] */
class ConservationEvidenceDataAPI
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
            'resource_id'        => 'Conservation_Evidence',
            'expire_seconds'     => 60*60*24*30, //expires in 1 day
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 0.5, 'cache' => 1);
        // $this->download_options['expire_seconds'] = 0; //debug only
        $this->source_csv_species_list = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/ConservationEvidence/uniquetaxa_2019_03_06.csv';
        // $this->source_csv_path = DOC_ROOT."../other_files/natdb_harvest/";
        // $this->spreadsheet_for_mapping = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MAD_tool_NatDB/MADmap.xlsx"; //from Jen (DATA-1754)
            
        $this->api['species'] = 'http://staging.conservationevidence.com/binomial/redlistsearch?name=BINOMIAL&action=1&total=50';
        $this->source_url = 'https://www.conservationevidence.com/data/index?terms=BINOMIAL';
    }
    private function initialize_mapping()
    {
        /* seems not used at all...
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // print_r($this->uris);
        */
        // self::initialize_citations_file();
        // self::initialize_spreadsheet_mapping();
    }
    function start()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        $this->func->initialize_terms_remapping(); //for DATA-1841 terms remapping

        $tmp_file = Functions::save_remote_file_to_local($this->source_csv_species_list, $this->download_options);
        self::loop_csv_species_list($tmp_file);
        unlink($tmp_file);
        
        // self::main_write_archive();
        $this->archive_builder->finalize(true);
        
        // print_r($this->debug);
        // Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function loop_csv_species_list($local_csv)
    {
        $i = 0;
        $file = Functions::file_open($local_csv, "r");
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
                // print_r($rec); exit;
                self::process_record($rec);
            } //main records
            // if($i > 5) break; //debug only
        } //main loop
        fclose($file);
    }
    private function process_record($rec)
    {   /*Array(
            [species] => sylvaticum
            [genus] => Geranium
            [binom] => Geranium sylvaticum
            [family] => Geraniaceae
            [order] => Geraniales
            [class] => Magnoliopsida
        )*/
        $taxon_id = self::create_taxon($rec);
        $url = str_replace('BINOMIAL', $rec['binom'], $this->api['species']);
        if($ret = self::access_api($url)) {
            // print_r($ret['results']); exit;
            if($val = @$ret['results']) self::create_measurements($val, $taxon_id);
        }
    }
    private function access_api($url)
    {
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            return json_decode($json, true);
        }
    }
    private function create_taxon($rec)
    {
        $taxon_id = str_replace(" ", "_", strtolower($rec['binom']));
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $taxon_id;
        $taxon->scientificName  = $rec['binom'];
        if($rec['binom']) $taxon->genus = $rec['genus'];
        $taxon->class = ($rec['class'] != 'NA' ? $rec['class'] : '');
        $taxon->order = ($rec['order'] != 'NA' ? $rec['order'] : '');
        $taxon->family = ($rec['family'] != 'NA' ? $rec['family'] : '');
        $taxon->furtherInformationURL = str_replace('BINOMIAL', urlencode($rec['binom']), $this->source_url);
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        return $taxon_id;
    }
    private function create_measurements($recs, $taxon_id)
    {   /*Array(
            [0] => Array(
                    [id] => 69
                    [title] => Reduce management intensity on permanent grasslands (several interventions at once)
                    [url] => http://staging.conservationevidence.com/actions/69
                    [type] => Action
                )
            [1] => Array(
                    [id] => 131
                    [title] => Delay mowing or first grazing date on pasture or grassland
                    [url] => http://staging.conservationevidence.com/actions/131
                    [type] => Action
                )
        measurementType=> "conservation_action"
        measurementValue=> url, from the API results, eg: "http://staging.conservationevidence.com/actions/486"
        measurementRemarks=> title, from the API results, eg: "Provide artificial nesting sites for waders"
        */
        foreach($recs as $rec) {
            $mValue = $rec['url'];
            $mType = 'conservation_action';
            $rek = array();
            $rek["taxon_id"] = $taxon_id;
            $rek["catnum"] = $taxon_id."_".$rec['id'];
            $mOfTaxon = "";
            $rek['measurementRemarks'] = $rec['title'];
            $ret = $this->func->pre_add_string_types($rek, $mValue, $mType, $mOfTaxon);
        }
    }
    //====================================================================Conservation Evidence ends here. Copied templates below.
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
                    if($samplesize > 1) { //you can now add arbitrary cols in occurrence
                        $rek['occur']['SampleSize'] = $samplesize;              //occurrence_property - http://eol.org/schema/terms/SampleSize
                    }
                    
                    if($val = @$this->main[$species]['occurrence']) {
                        $rek = self::additional_occurrence_property($val, $rek, $metadata, $dataset);
                    }
                    $rek['referenceID'] = self::generate_reference($dataset);
                    $ret_MoT_true = $this->func->pre_add_string_types($rek, $mValue, $mType, $mOfTaxon); //1
                    $occurrenceID = $ret_MoT_true['occurrenceID'];
                    $measurementID = $ret_MoT_true['measurementID'];

                    /* now moved to occurrence
                    $rek = array();
                    $rek["taxon_id"] = $taxon_id;
                    $rek["catnum"] = ''; //can be blank coz occurrenceID is already generated.
                    $rek['occurrenceID'] = $occurrenceID; //this will be the occurrenceID for all mOfTaxon that is equal to 'false'. That is required.
                    if($samplesize > 1) {
                        $mType_var = 'http://eol.org/schema/terms/SampleSize';
                        $mValue_var = $samplesize;
                        $this->func->add_string_types($rek, $mValue_var, $mType_var, "false");
                    }
                    */
                    if($mapped_record['dataset'] == ".benesh.2017") {
                        $mType_var = 'http://eol.org/schema/terms/TrophicGuild';
                        $mValue_var = 'http://www.wikidata.org/entity/Q12806437';
                        $rek = array();
                        $rek["taxon_id"] = $taxon_id;
                        $rek["catnum"] = $csv_type."_".$mValue_var;
                        $rek['lifeStage'] = $mapped_record['http://rs.tdwg.org/dwc/terms/lifeStage'];  //measurement_property, yes this is arbitrary field in MoF
                        $rek['referenceID'] = self::generate_reference($mapped_record['dataset']);
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
                                $this->func->pre_add_string_types($rek, $mValue_var, $mType_var, "child"); //3
                            }
                        }
                    }

                }
            }
            
        }
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
        }
        return $retx;
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
