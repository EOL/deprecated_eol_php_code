<?php
namespace php_active_record;
/* 
*/
class TaxonomicValidationRules
{
    function __construct()
    {
    }
    private function initialize()
    {   // /* 1st:
        require_library('connectors/RetrieveOrRunAPI');
        $task2run = 'gnparser';
        $download_options['expire_seconds'] = false; //doesn't expire
        $main_path = 'gnparser_cmd';
        $this->RoR = new RetrieveOrRunAPI($task2run, $download_options, $main_path);
        // */
    }
    function process_user_file($txtfile, $tsvFileYN = true)
    {
        // echo "\n[".$input_file."] [$this->resource_id]\n";
        self::initialize();
        if($tsvFileYN) {
            self::parse_tsv($txtfile);
        }
        exit("\n-stop muna-\n");
    }
    private function parse_tsv($txtfile)
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
            print_r($rec); //exit("\nstopx\n");
            /*Array(
                [taxonID] => Archaea
                [scientificName] => Archaea
                [EOLid] => 7920
            )*/
            // /* for calling gnparser
            $input = array('sciname' => $rec['scientificName']);
            $json = $this->RoR->retrieve_data($input); //call gnparser
            $obj = json_decode($json); print_r($obj); exit("\n[".$json."]\n");
            // */
            $raw = array();
            $raw['taxonID']                     = self::build_taxonID($rec);
            $raw['scientificName']              = self::build_scientificName($rec);
            $raw['canonicalName']               = self::build_canonicalName($rec, $obj);
            $raw['scientificNameAuthorship']    = self::build_scientificNameAuthorship($rec, $obj);
            $raw['taxonRank']                   = self::build_taxonRank($rec, $obj, $raw['canonicalName']);

            break; //debug only
        } //end foreach()
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
            if(!$obj->parsed || $obj->parsed === false || $obj->parsed == 'false') return $rec['scientificName'];
            else { // names that get parsed
                $CanonicalFull = $obj->canonical->full;
                if(@$obj->hybrid == "NAMED_HYBRID")                 return $CanonicalFull;
                if(stripos($CanonicalFull, " subgen. ") !== false)  return $CanonicalFull; //found string
                return $obj->canonical->simple;
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
    /*=========================================================================*/ // COPIED TEMPLATE BELOW
    /*=========================================================================*/
    private function initialize_file($sheet_name)
    {
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
        $WRITE = Functions::file_open($filename, "w"); fclose($WRITE);
        
        $filename = $this->resources['path'].$this->resource_id."_invalid_values.txt";
        $WRITE = Functions::file_open($filename, "w"); fclose($WRITE);
    }
    private function write_output_rec_2txt($rec, $sheet_name)
    {
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
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