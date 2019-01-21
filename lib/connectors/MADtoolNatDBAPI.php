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
    function start()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        self::initialize_mapping();
        
        $csv = array('file' => $this->source_csv_path."categorical.csv", 'type' => 'categorical');
        $csv = array('file' => $this->source_csv_path."numeric.csv", 'type' => 'numeric');
        self::process_extension($csv);
        
        // $this->archive_builder->finalize(true);
        
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
        // print_r($this->numeric_fields);
        exit("\n-end for now-\n");
    }
    private function process_extension($csv)
    {
        $i = 0;
        $file = Functions::file_open($csv['file'], "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            $row = self::clean_html($row); // print_r($row);
            $i++; if(($i % 100000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
                print_r($fields);
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
                self::process_record($rec, $csv);
            } //main records
            // if($i > 5) break;
        } //main loop
        fclose($file);
    }
    private function process_record($rec, $csv)
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
        
        if(isset($this->numeric_fields[$rec['variable']])) $value = "";
        else                                               $value = $rec['value'];
        
        
        $tmp = $rec['variable']."_".$value."_".$rec['dataset']."_".self::blank_if_NA($rec['units'])."_";
        $tmp = strtolower($tmp);
        // echo "\n[$tmp]"; exit;
        
        if(isset($this->valid_set[$tmp])) {
            @$this->debug[$rec['variable']][$rec['value']] = '';
            $mapped_record = $this->valid_set[$tmp];
            echo "\n[$tmp]"; print_r($mapped_record); print_r($rec); exit("\n111\n");
        }
        
        /* good debug
        if($rec['variable'] == "Maximum_length") {
            // print_r($rec); exit;
            @$this->debug[$rec['variable']][$rec['value']][$rec['dataset']] = $rec['units'];
        }
        */
        
        
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
    /* ######################################################################################################################################### */
    /* ######################################################################################################################################### */
    /* ######################################################################################################################################### */
    private function create_taxon($rec)
    {
        if(!isset($this->taxa_with_trait[$rec['DEF_id']])) return;
        // print_r($rec); exit;
        /*Array(
            [DEF_id] => 1
            [family] => Alangiaceae 
            [genus] => Alangium
            [scientific name] => Alangium chinense 
            [species] => chinense
            [subspecies] => 
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['DEF_id'];
        $taxon->scientificName  = $rec['scientific name'];
        $taxon->family          = $rec['family'];
        $taxon->genus           = $rec['genus'];
        // $taxon->taxonRank             = '';
        // $taxon->furtherInformationURL = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function create_trait($rek, $group)
    {
        if($group == "distribution.csv") {
            $arr = explode(";", $rek['Region']);
            $taxon_id = $rek['Plant No'];
            $mtype = "http://eol.org/schema/terms/Present";
        }
        elseif($group == "use.csv") {
            $arr = explode(";", $rek['Use']);
            $taxon_id = $rek['Plant'];
            $mtype = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Use";
        }
        $arr = array_map('trim', $arr);
        // print_r($arr); exit;
        foreach($arr as $string_val) {
            if($string_val) {
                $rec = array();
                $rec["taxon_id"] = $taxon_id;
                $rec["catnum"] = $taxon_id.'_'.$rek['id'];
                if($string_uri = self::get_string_uri($string_val)) {
                    $this->taxa_with_trait[$taxon_id] = ''; //to be used when creating taxon.tab
                    $rec['measurementRemarks'] = $string_val;
                    $this->func->add_string_types($rec, $string_uri, $mtype, "true");
                }
                else $this->debug[$group][$string_val] = '';
            }
        }
    }
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
    private function use_mapping_from_jen()
    {
        $csv_file = Functions::save_remote_file_to_local($this->use_mapping_from_jen, $this->download_options);
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            $row = self::clean_html($row);
            // print_r($row);
            $i++; if(($i % 2000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
                print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                // print_r($fields); print_r($rec); exit;
                /*Array(
                    [Use string] => timber
                    [URI] => http://purl.obolibrary.org/obo/EUPATH_0000001
                    [blank_1] => 
                    [blank_2] => 
                    [blank_3] => 
                    [blank_4] => 
                )*/
                $this->uris[$rec['Use string']] = $rec['URI'];
            } //main records
        } //main loop
        fclose($file);
        unlink($csv_file);
    }
}
?>
