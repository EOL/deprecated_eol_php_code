<?php
namespace php_active_record;
/* This is a generic utility for DwCA post-processing.
first client: called from DwCA_Utility.php, which is called from remove_taxa_without_MoF.php
2nd client  : add canonical_name inside taxon.tab using gnparser command-line
            : called from DwCA_Utility.php, which is called from add_canonical_in_taxa.php
*/
class ResourceUtility
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        /* For task: add_canonical_in_taxa */
        $this->extracted_scinames = $GLOBALS['MAIN_TMP_PATH'] . $this->resource_id . "_scinames.txt";
        $this->gnparsed_scinames = $GLOBALS['MAIN_TMP_PATH'] . $this->resource_id . "_canonical.txt";
    }
    /*============================================================ STARTS add_canonical_in_taxa =================================================*/
    function add_canonical_in_taxa($info) //Func2
    {   
        $tables = $info['harvester']->tables;
        self::process_taxon_Func2($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write sciname list for gnparser');
        self::insert_canonical_in_taxa();
    }
    private function insert_canonical_in_taxa()
    {   //step 1: run gnparser
        $file_cnt = 0;
        while(true) { $file_cnt++;
            $source = $this->extracted_scinames."_".$file_cnt;
            $destination = $this->gnparsed_scinames."_".$file_cnt;
            if(file_exists($source)) {
                $cmd = "gnparser file -f simple --input $source --output $destination"; //'simple' or 'json-compact'
                $out = shell_exec($cmd); echo "\n$out\n";
            }
            else break;
        }
        //step 2: 
    }
    private function process_taxon_Func2($meta, $task)
    {   //print_r($meta);
        echo "\nResourceUtility...($task)...\n"; $i = 0;
        
        $file_cnt = 1;
        $WRITE = fopen($this->extracted_scinames."_".$file_cnt, "w"); $eli = 0;
        
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug1...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => urn:lsid:marinespecies.org:taxname:1
                [http://rs.tdwg.org/dwc/terms/scientificName] => Biota
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] =>
                ...
            )*/
            if($task == 'write sciname list for gnparser') {
                if(($i % 400000) == 0) {
                    $file_cnt++;
                    fclose($WRITE);
                    $WRITE = fopen($this->extracted_scinames."_".$file_cnt, "w");
                }
                if($val = trim($rec['http://rs.tdwg.org/dwc/terms/scientificName'])){}
                else $eli++;
                
                fwrite($WRITE, $rec['http://rs.tdwg.org/dwc/terms/scientificName'] . "\n");
            }
            elseif($task == 'write taxa') {
                $uris = array_keys($rec);
                $o = new \eol_schema\Taxon();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
        fclose($WRITE);
        echo "\nelix = $eli\n";
        // exit;
    }
    /*============================================================ ENDS add_canonical_in_taxa ===================================================*/
    
    /*============================================================ STARTS remove_taxa_without_MoF =================================================*/
    function remove_taxa_without_MoF($info) //Func1
    {   
        $tables = $info['harvester']->tables;
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);    //build $this->taxon_ids
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);              //write taxa
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...read occurrences...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /**/
            //------------------------------------------------------------------------------
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $this->taxon_ids[$taxonID] = '';
            //------------------------------------------------------------------------------
        }
    }
    private function process_taxon($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...write taxa...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /**/
            //------------------------------------------------------------------------------
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if(!isset($this->taxon_ids[$taxonID])) continue;
            //------------------------------------------------------------------------------
            $uris = array_keys($rec);
            $o = new \eol_schema\Taxon();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    /*============================================================ ENDS remove_taxa_without_MoF ==================================================*/
    /*================================================== STARTS report_4_Wikipedia_EN_traits ===============================================*/
    function report_4_Wikipedia_EN_traits($info) //Func3
    {
        $tables = $info['harvester']->tables;
        self::process_MoF_Func3($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'report for Jen');
    }
    private function process_MoF_Func3($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...read MoF...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => a68a8ae49e178d85af09d5682c52c60e_617_ENV
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => a3232ea9cf84b8c1aa2e2691441805c6_617_ENV
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/RO_0002303
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_01000206
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "temperate"
                [http://purl.org/dc/terms/source] => https://eol.org/search?q=Brentidae
            )*/
            //-------------------------------------
            $debug[$rec['http://rs.tdwg.org/dwc/terms/measurementRemarks']][$rec['http://rs.tdwg.org/dwc/terms/measurementValue']] = '';
            //-------------------------------------
            /* Sample B:
            Q1000266_-_c4c5f6b1da59da518a855dd311b66421.txt 1716 1720 coast ENVO:00000303
            Q1000266_-_c4c5f6b1da59da518a855dd311b66421.txt 1862 1867 coasts ENVO:00000303
            */
            $debug2[$rec['http://rs.tdwg.org/dwc/terms/measurementValue']][$rec['http://rs.tdwg.org/dwc/terms/measurementRemarks']] = '';
            //-------------------------------------
        }
        // print_r($debug); exit;
        /*
        foreach($debug as $string => $terms) {  //works OK report
            if(count($terms) > 1) {
                echo "\n[$string]"; print_r($terms);
            }
        }*/
        foreach($debug2 as $string => $terms) { //works OK report
            if(count($terms) > 1) {
                echo "\n[$string]"; print_r($terms);
            }
        }
    }
    /*================================================== ENDS report_4_Wikipedia_EN_traits =================================================*/
}
?>