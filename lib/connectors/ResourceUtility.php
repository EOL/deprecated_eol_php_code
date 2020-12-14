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
        
        /* For environments_names.tsv processing */
        $this->ontology['env_names'] = "https://github.com/eliagbayani/vangelis_tagger/raw/master/eol_tagger/environments_names.tsv";
    }
    /*============================================================ STARTS add_canonical_in_taxa =================================================*/
    function add_canonical_in_taxa($info) //Func2
    {
        //step 1: build-up sciname-canonical info list
        $file_cnt = 0;
        while(true) { $file_cnt++;
            $destination = $this->gnparsed_scinames."_".$file_cnt;
            if(file_exists($destination)) {
                foreach(new FileIterator($destination) as $line => $row) {
                    if(!$row) continue;
                    // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
                    $rec = explode("\t", $row);
                    // print_r($rec); //exit("\ndebug1...\n");
                    /*Array(
                        [0] => d0f24211-8123-5397-8685-485dac20542c
                        [1] => Saccamminopsis camelopardalis Schallreuter, 1985
                        [2] => Saccamminopsis camelopardalis
                        [3] => Saccamminopsis camelopardalis
                        [4] => Schallreuter 1985
                        [5] => 1985
                        [6] => 1
                    )*/
                    $this->sciname_canonical_info[trim($rec[1])] = trim($rec[3]);
                }
            }
            else break;
        }
        //step 2: write to taxa the new column canonicalname
        $tables = $info['harvester']->tables;
        self::process_taxon_Func2($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write taxa');
        //step 3: write document extension - just copy
        /* working but not needed for DH purposes
        self::carry_over_extension($tables['http://eol.org/schema/media/document'][0], 'document');
        self::carry_over_extension($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'measurementorfact');
        */
        echo "\nTotal scinames no canonical generated: ".count($this->debug['sciname no canonical generated']);
    }
    function gen_canonical_list_from_taxa($info) //Func2
    {
        $tables = $info['harvester']->tables;
        self::process_taxon_Func2($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write scinames list for gnparser'); //generate WoRMS2EoL_zip_scinames.txt_1...
        self::insert_canonical_in_taxa();
    }
    private function insert_canonical_in_taxa()
    {   //step 1: run gnparser, generate WoRMS2EoL_zip_canonical.txt_1...
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
    }
    private function process_taxon_Func2($meta, $task)
    {   //print_r($meta);
        echo "\nResourceUtility...($task)...\n"; $i = 0;
        
        if($task == 'write scinames list for gnparser') {
            $file_cnt = 1;
            $WRITE = fopen($this->extracted_scinames."_".$file_cnt, "w"); $eli = 0;
        }
        
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
            if($task == 'write scinames list for gnparser') {
                if(($i % 400000) == 0) {
                    $file_cnt++;
                    fclose($WRITE);
                    $WRITE = fopen($this->extracted_scinames."_".$file_cnt, "w");
                }
                // /* for scientificName
                if($scientificName = trim($rec['http://rs.tdwg.org/dwc/terms/scientificName'])){}
                else $eli++;
                fwrite($WRITE, $scientificName . "\n");
                // */
                
                /* for genus - was never used though
                if($genus = trim($rec['http://rs.tdwg.org/dwc/terms/genus'])){}
                else $eli++;
                if(!isset($written_taxa[$genus])) {
                    fwrite($WRITE, $genus . "\n");
                    $written_taxa[$genus] = '';
                }
                */
                
            }
            elseif($task == 'write taxa') {
                // /* for scientificName
                $scientificName = trim($rec['http://rs.tdwg.org/dwc/terms/scientificName']);
                if($canonical = $this->sciname_canonical_info[$scientificName]) {
                    $rec['http://rs.tdwg.org/dwc/terms/vernacularName'] = $canonical; //deliberately used vernacularName for canonical values
                }
                else {
                    // print_r($rec); exit("\nsciname no canonical generated\n");
                    $this->debug['sciname no canonical generated'][$scientificName] = '';
                    $rec['http://rs.tdwg.org/dwc/terms/vernacularName'] = $scientificName;
                }
                // */

                /* for genus - was never used though
                $genus = trim($rec['http://rs.tdwg.org/dwc/terms/genus']);
                if($canonical = $this->sciname_canonical_info[$genus]) {
                    $rec['http://rs.tdwg.org/dwc/terms/vernacularName'] = $canonical; //deliberately used 'vernacularName' to store canonical values
                }
                else {
                    // print_r($rec); exit("\nsciname no canonical generated\n");
                    $this->debug['sciname no canonical generated'][$genus] = '';
                    $rec['http://rs.tdwg.org/dwc/terms/vernacularName'] = $genus;
                }
                */
                
                if($this->resource_id == 'WoRMS2EoL_zip') {
                    $rec['http://purl.org/dc/terms/accessRights'] = $rec['http://purl.org/dc/terms/rights'];
                    unset($rec['http://purl.org/dc/terms/rights']); //'rights' is undefined in WoRMS taxon dictionary
                }
                
                $uris = array_keys($rec);
                $o = new \eol_schema\Taxon();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
        if($task == 'write scinames list for gnparser') {
            fclose($WRITE);
            echo "\nNo. of records without scientificName (should be zero) = $eli\n";
        }
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
            if($taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID']) $this->taxon_ids[$taxonID] = '';
            if($parentNameUsageID = @$rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID']) $this->taxon_ids[$parentNameUsageID] = '';
            if($acceptedNameUsageID = @$rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']) $this->taxon_ids[$acceptedNameUsageID] = '';
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
        self::get_env_names_info_list(); // print_r($env_names_info_list); exit("\nstop munax\n");
        $tables = $info['harvester']->tables;
        self::process_MoF_Func3($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'report for Jen');
    }
    private function get_env_names_info_list()
    {
        $options = array('expire_seconds' => 60*60*24);
        $local_file = Functions::save_remote_file_to_local($this->ontology['env_names'], $options);
        /*
        1007000016	ocean
        1007000032	wadi
        1007000048	canopy
        1007000064	water body
        */
        foreach(new FileIterator($local_file) as $line => $row) {
            // $i++; if(($i % 100) == 0) echo "\n".number_format($i);
            if(!$row) continue;
            $arr = explode("\t", $row);
            $this->env_names_string_code[$arr[1]] = $arr[0];
            $this->env_names_joined[$arr[1]][$arr[0]] = '';
        }
        unlink($local_file);
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
        
        // /* works OK report - multiple_terms_single_string.txt
        echo "\nmultiple_terms_single_string report";
        echo "\nLEGEND: ** To be deleted in environments_names.tsv\n";
        foreach($debug as $string => $terms) {
            if(count($terms) > 1) {
                echo "\n------------------------------------------------------\n[$string]"; print_r($terms);
                // get what is to be deleted in environments_names
                foreach($terms as $uri => $wala) {
                    $filename = pathinfo($uri, PATHINFO_FILENAME);
                    $filename = str_replace("_", ":", $filename);
                    // /* good debug
                    if($code1 = @$this->env_names_string_code[$filename]) echo "\nLookup [$filename] => ".$code1;
                    // */
                    if(preg_match("/\"(.*?)\"/ims", $string, $a)) {
                        $habitats = explode("|", $a[1]);
                        // echo "\nTo be deleted in environments_names.tsv:";
                        foreach($habitats as $habitat) {
                            if(isset($this->env_names_joined[$habitat][$code1])) {
                                $to_delete[$code1."\t".$habitat] = '';
                                echo "\n ** ".$code1." $habitat";
                            }
                        }
                    }
                }
            }
        }
        echo "\n------------------------------------------------------\n";
        echo "SUMMARY: To be deleted in environments_names.tsv:";
        $to_delete = array_keys($to_delete);
        print_r($to_delete);
        // */

        /*
        foreach($debug2 as $string => $terms) { //works OK report - Sample_B.txt
            if(count($terms) > 1) {
                echo "\n[$string]"; print_r($terms);
            }
        }
        */
    }
    /*================================================== ENDS report_4_Wikipedia_EN_traits =================================================*/

    private function carry_over_extension($meta, $class)
    {   //print_r($meta);
        echo "\nResourceUtility...carry_over_extension ($class)...\n"; $i = 0;
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
            /**/
            $uris = array_keys($rec);
            if    ($class == "vernacular")          $c = new \eol_schema\VernacularName();
            elseif($class == "agent")               $c = new \eol_schema\Agent();
            elseif($class == "reference")           $c = new \eol_schema\Reference();
            elseif($class == "taxon")               $c = new \eol_schema\Taxon();
            elseif($class == "document")            $c = new \eol_schema\MediaResource();
            elseif($class == "occurrence")          $c = new \eol_schema\Occurrence();
            elseif($class == "measurementorfact")   $c = new \eol_schema\MeasurementOrFact();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }

}
?>