<?php
namespace php_active_record;
/*
*/
class TaxonomicValidationRules
{
    function __construct()
    {
        $this->can_compute_higherClassificationYN = false; //default is false
    }
    private function initialize()
    {   // /* 1st:
        require_library('connectors/RetrieveOrRunAPI');
        $task2run = 'gnparser';
        $download_options['expire_seconds'] = false; //doesn't expire
        $main_path = 'gnparser_cmd';
        $this->RoR = new RetrieveOrRunAPI($task2run, $download_options, $main_path);
        // */
        // /* 2nd:
        require_library('connectors/DwCA_Utility');
        $this->HC = new DwCA_Utility(); // HC - higherClassification functions
        // */
        // /* 3rd:
        $this->temp_dir = CONTENT_RESOURCE_LOCAL_PATH . '/Taxonomic_Validation/'.$this->resource_id."/";
        if(!is_dir($this->temp_dir)) mkdir($this->temp_dir);
        // */
        $this->DH_file = CONTENT_RESOURCE_LOCAL_PATH . '/Taxonomic_Validation/dh21eolid/taxon.tab';
    }
    function process_user_file($txtfile, $tsvFileYN = true)
    {
        // echo "\n[".$txtfile."] [$this->resource_id]\n"; exit;
        self::initialize();
        if($tsvFileYN) {
            self::parse_user_file($txtfile);

            // self::parse_TSV_file($this->DH_file, 'load DH file');
            // exit("\nditox 1\n");
            self::parse_TSV_file($this->temp_dir."processed.txt", 'name match and validate');
            // recursive_rmdir($this->temp_dir);
        }
        exit("\n-stop muna-\n");
    }
    private function parse_TSV_file($txtfile, $task)
    {   
        if($task == "load DH file") echo "\nLoading DH 2.1 ";
        $i = 0; debug("\n[$txtfile]\n");
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 1000000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec);
            //###############################################################################################
            if($task == "load DH file") {
                // print_r($rec); exit("\nstopx\n");
                $canonicalName = $rec['canonicalName'];
                $this->DH_info[$canonicalName][] = $rec;
                // print_r($this->DH_info); exit("\nditox 3\n");
                /*Array(
                    [Life] => Array(
                            [0] => Array(
                                    [taxonID] => EOL-000000000001
                                    [source] => trunk:4038af35-41da-469e-8806-40e60241bb58
                                    [furtherInformationURL] => 
                                    [acceptedNameUsageID] => 
                                    [parentNameUsageID] => 
                                    [scientificName] => Life
                                    [taxonRank] => 
                                    [taxonomicStatus] => accepted
                                    [datasetID] => trunk
                                    [canonicalName] => Life
                                    [authority] => 
                                    [eolID] => 2913056
                                    [Landmark] => 3
                                    [higherClassification] => 
                                )
                        )
                )*/
            }
            //###############################################################################################
            if($task == "name match and validate") {
                /* implement this way
                $arr['Eli'][0] = array()
                $arr['Cha'][0] = array()
                $arr['Cha'][1] = array()
                */
                // print_r($rec); exit("\nditox 2\n");
                self::name_match_validate($rec);
            }
            //###############################################################################################
        } //end foreach()
        if($task == "load DH file") {
            echo "\nLoaded DH 2.1 DONE.";
            echo "\ntotal: ".count($this->DH_info)."\n"; //exit;
        }
    }
    private function name_match_validate($rec)
    {   /*Array(
            [taxonID] => ABPR3_Abrus_precatorius
            [scientificName] => Abrus precatorius
            [canonicalName] => Abrus precatorius
            [scientificNameAuthorship] => 
            [taxonRank] => species
            [taxonomicStatus] => accepted
            [higherClassification] => Plantae|Magnoliopsida|Fabales|Fabaceae|Abrus
        )*/
        
    }
    private function parse_user_file($txtfile)
    {   $i = 0; debug("\n[$txtfile]\n");
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 100) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec);
            echo "\nRAW REC:"; print_r($rec); //exit("\nstopx\n");
            /*Array(
                [taxonID] => Archaea
                [scientificName] => Archaea
                [EOLid] => 7920
            )*/

            // /* ---------- for higherClassification
            if($i == 2) {
                if($this->can_compute_higherClassificationYN = self::can_compute_higherClassification($rec)) {
                    if($records = $this->HC->create_records_array($txtfile)) {
                        $this->HC->build_id_name_array($records); //print_r($this->HC->id_name); exit;
                    }
                    else exit("\nNo records\n");
                }
                // else exit("\nCannot compute HC\n"); //no need to trap, acceptable case
            }
            // ---------- */

            // /* for calling gnparser
            $input = array('sciname' => $rec['scientificName']);
            $json = $this->RoR->retrieve_data($input); //call gnparser
            $obj = json_decode($json); //print_r($obj); echo("\n[".$json."]\n");
            // */
            $raw = array();
            $raw['taxonID']                     = self::build_taxonID($rec);
            $raw['scientificName']              = self::build_scientificName($rec);
            $raw['canonicalName']               = self::build_canonicalName($rec, $obj);
            $raw['scientificNameAuthorship']    = self::build_scientificNameAuthorship($rec, $obj);
            $raw['taxonRank']                   = self::build_taxonRank($rec, $obj, $raw['canonicalName']);
            $raw['taxonomicStatus']             = self::build_taxonomicStatus($rec);
            $raw['higherClassification']        = self::build_higherClassification($rec);
            echo "\nPROCESSED REC:"; print_r($raw);
            self::write_output_rec_2txt($raw, "processed");
            // break; //debug only
            if($i >= 5) break; //debug only
        } //end foreach()
    }
    private function build_higherClassification($rec)
    {   /* If there is a higherClassification field, use the value from this field. 
        If not construct a pipe-separated list of ancestors based on information in the parentNameUsageID fields or 
        the taxonomy fields (kingdom|phylum|class|order|family|subfamily|genus|subgenus). Some files will not have any higher classification information at all. */
        if($val = @$rec['higherClassification']) return $val;
        else {
            if(isset($rec['parentNameUsageID'])) return self::get_higherClassification($rec); //1st option
            $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'subfamily', 'genus', 'subgenus');
            foreach($ranks as $rank) {
                if(isset($rec[$rank])) {
                    return self::generate_higherClass_using_ancestry_fields($rec); //2nd option
                    break;
                }
            }
        }
    }
    private function build_taxonomicStatus($rec)
    {   /* If there is a taxonomicStatus field, use the value from this field. If not, we infer that all taxa have taxonomicStatus = accepted. */
        if($val = @$rec['taxonomicStatus']) return $val;
        else return 'accepted';
    }
    private function build_taxonRank($rec, $obj, $canonicalName)
    {   /* If there is a taxonRank field, use the value from this field. If not, we can to infer the rank for some taxa as follows:
        - It the canonical name is a binomial (gnparser: cardinality=2), infer taxonRank = species
        - If the canonical name is a trinomial or multinomial (gnparser: cardinality≥3), infer taxonRank = infraspecies
        - If the canonical name is a uninomial (gnparser cardinality=1) and ends in “idae” or “aceae”, infer taxonRank= family 
        Whenever a rank is inferred, we’ll want to add a flag to the quality notes in the report (see below). */
        if($val = @$rec['taxonRank']) return $val;
        else {
            if($obj->cardinality == 2) return 'species';
            if($obj->cardinality >= 3) return 'infraspecies';
            if($obj->cardinality == 1) {
                if(substr($canonicalName, -4) == "idae")  return 'family';
                if(substr($canonicalName, -5) == "aceae") return 'family';
            }
        }
    }
    private function build_scientificNameAuthorship($rec, $obj)
    {   /* If there is a scientificNameAuthorship field, use the value from that field. 
        If not, get the authority data from gnparser ( ​​"authorship">"normalized"). Not all files will have authorship data in the scientificName column, 
        so scientificNameAuthorship may be empty. */
        if    ($val = @$rec['scientificNameAuthorship']) return $val;
        elseif($val = @$obj->authorship->normalized)     return $val;
    }
    private function build_canonicalName($rec, $obj)
    {   /* If a canonicalName field is available use the value from that field. If not, use the gnparser canonical. 
        For names that don’t get parsed ("parsed": false), use the full scientificName string as the canonicalName value and 
        add an unparsed flag to the quality warnings in the report (see below). 
        For names that do get parsed, use the CanonicalSimple value as the canonicalName value for all taxa except the following:
        - Use the gnparser CanonicalFull value for hybrids, i.e., if the gnparser report has a "hybrid": "NAMED_HYBRID" statement.
        - Use the gnparser CanonicalFull value for subgenera, i.e., if the gnparser CanonicalFull value has the string “ subgen. ” in it. */
        if($val = @$rec['canonicalName']) return $val;
        else {
            // exit("\nparsed: [".$obj->parsed."]\n");
            if($obj->parsed == 1) { // names that get parsed
                $CanonicalFull = $obj->canonical->full;
                if(@$obj->hybrid == "NAMED_HYBRID")                 return $CanonicalFull;
                if(stripos($CanonicalFull, " subgen. ") !== false)  return $CanonicalFull; //found string
                return $obj->canonical->simple;
            }
            else {
                echo "\ngot entire sciname: for "; print_r($rec);
                return $rec['scientificName'];
            }
        }
    }
    private function build_scientificName($rec)
    {   /* If a scientificName field is available use the value from that field. Every DwC-A or taxa file should have this field. 
           If the user file is a taxa list, we will interpret the names as scientificName values. */
        if($val = @$rec['scientificName']) return $val;
    }
    private function build_taxonID($rec)
    {
        if($val = @$rec['taxonID']) return $val;
    }
    private function get_higherClassification($rec)
    {
        $parent_id = $rec['parentNameUsageID'];
        $str = "";
        while($parent_id) {
            if($parent_id) {
                $str .= trim(@$this->HC->id_name[$parent_id]['scientificName'])."|";
                $parent_id = @$this->HC->id_name[$parent_id]['parentNameUsageID'];
            }
        }
        $str = substr($str, 0, strlen($str)-1); // echo "\norig: [$str]";
        $arr = explode("|", $str);
        $arr = array_reverse($arr);
        $str = implode("|", $arr); // echo "\n new: [$str]\n";
        return $str;
    }
    private function generate_higherClass_using_ancestry_fields($rec)
    {
        $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'subfamily', 'genus', 'subgenus');
        $str = "";
        foreach($ranks as $rank) {
            if($val = @$rec[$rank]) $str .= $val."|";
        }
        $str = substr(trim($str), 0, -1); // remove last char from string
        return $str;
    }
    private function can_compute_higherClassification($rec)
    {
        if(!isset($rec["taxonID"])) return false;
        if(!isset($rec["scientificName"])) return false;
        if(!isset($rec["parentNameUsageID"])) return false;
        return true;
    }
    /*=========================================================================*/ // COPIED TEMPLATE BELOW
    /*=========================================================================*/
    // private function initialize_file($sheet_name)
    // {    
    //     $filename = $this->resources['path'].$this->resource_id."_invalid_values.txt";
    //     $WRITE = Functions::file_open($filename, "w"); fclose($WRITE);
    // }
    private function write_output_rec_2txt($rec, $basename)
    {
        $filename = $this->temp_dir.$basename.".txt";
        $fields = array_keys($rec);
        $WRITE = Functions::file_open($filename, "a");
        clearstatcache(); //important for filesize()
        if(filesize($filename) == 0) fwrite($WRITE, implode("\t", $fields) . "\n");
        $save = array();
        foreach($fields as $fld) $save[] = $rec[$fld];
        fwrite($WRITE, implode("\t", $save) . "\n");
        fclose($WRITE);
    }
}
?>