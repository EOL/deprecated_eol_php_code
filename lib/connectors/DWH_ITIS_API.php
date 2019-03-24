<?php
namespace php_active_record;
/* connector: [dwh_itis.php] 
Part of this connector was taken from CSV2DwCA_Utility.php
*/
class DWH_ITIS_API
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
        
        $this->download_options = array( //Note: Database download files are currently from the 25-Feb-2019 data load.
            'expire_seconds'     => false, //expires false since we're not going to run periodically. And data dump uses specific date e.g. 25-Feb-2019
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
            
        /* 'Use' mapping from Jen: https://opendata.eol.org/dataset/africa-tree-database/resource/5bce8f9a-933e-4f23-bb4d-e7260f0ba1cf
        $this->use_mapping_from_jen = "https://opendata.eol.org/dataset/e31baa95-af6c-4539-a1d8-00f7364fadcd/resource/5bce8f9a-933e-4f23-bb4d-e7260f0ba1cf/download/use-mapping.csv";
        $this->addtl_mapping_from_jen = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/AfricaTreeDB/AfricaTreeLocalities.txt"; //based from Eli's un-mapped string report.
        $this->partner_bibliographicCitation = "Carrie Seltzer, William Wysocki, Melissa Palacios, Anna Eickhoff, Hannah Pilla, Jordan Aungst, Aaron Mercer, Jamie Quicho, Neil Voss, Man Xu, Henry J. Ndangalasi, Jon C. Lovett, Norbert J. Cordeiro. Posted October 21, 2015. The Africa Tree Database. Accessed at https://figshare.com/articles/SQL_of_Africa_Tree_Database/1526125, January 16, 2019.";
        $this->partner_source_url = "https://figshare.com/articles/SQL_of_Africa_Tree_Database/1526125";
        */
    }
    function convert_archive()
    {
        /* un-comment in real operation
        if(!($info = self::prepare_archive_for_access())) return;
        print_r(info); exit;
        */
        // /* debug - force assign
        $info = Array( //dir_44057
            // 'archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_44057/itisMySQL022519/',
            // 'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_44057/'
            'archive_path' => '/Users/eagbayani/Sites/eol_php_code/tmp/dir_89406/itisMySQL022519/',
            'temp_dir' => '/Users/eagbayani/Sites/eol_php_code/tmp/dir_89406/'
        );
        // */
        
        $temp_dir = $info['temp_dir'];
        // print_r($info); exit;
        $tables = array("taxonomic_units");
        echo "\nProcessing...\n";
        
        //step 1: get unnamed_taxon_ind == Y and all its children
        $what = 'unnamed_taxon_ind';
        $unnamed_taxon_ind_Y = self::process_file($info['archive_path'].'taxonomic_units', $what);
        print_r($unnamed_taxon_ind_Y); //exit;
        //step 2: get all children of $unnamed_taxon_ind_Y
        $children_of_unnamed = self::get_children_of_unnamed($unnamed_taxon_ind_Y);
        print_r($children_of_unnamed); exit;
        
        exit;
        foreach($tables as $tbl) {
            self::process_file($info['archive_path'].$tbl);
        }
        
        $this->archive_builder->finalize(true);
        exit;
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);

        //massage debug for printing
        Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function process_file($file, $what)
    {
        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line) {
            if(!$line) continue;
            $row = explode("|", $line);
            // print_r($row); exit;
            if(!$row) continue; //continue; or break; --- should work fine
            $i++; if(($i % 100000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = self::fill_up_blank_fieldnames($row);
                $count = count($fields);
                // print_r($fields);
            }
            else { //main records
                $values = $row;
                $k = 0; $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //important step
                // print_r($rec); exit;
                
                if($what == 'unnamed_taxon_ind') {
                    $taxon_id = $rec['col_1'];
                    $parent_id = $rec['col_18'];
                    @$this->debug['col10'][$rec['col_10']]++;
                    if($rec['col_10'] == "Y") $final[$taxon_id] = ''; //print_r($rec);
                    $this->child_of[$parent_id][] = $taxon_id;
                }
            }
        }
        // print_r($this->debug); exit;
        // print_r($this->child_of);
        // print_r($this->child_of['4324']); exit("\nstop\n");
        if($what == 'unnamed_taxon_ind') return array_keys($final);
    }
    private function get_children_of_unnamed($taxon_ids1)
    {
        $final = array();
        foreach($taxon_ids1 as $id1) {
            if($taxon_ids2 = @$this->child_of[$id1]) {
                $final = array_merge($final, $taxon_ids2);
                foreach($taxon_ids2 as $id2) {
                    if($taxon_ids3 = @$this->child_of[$id2]) {
                        $final = array_merge($final, $taxon_ids3);
                        foreach($taxon_ids3 as $id3) {
                            if($taxon_ids4 = @$this->child_of[$id3]) {
                                $final = array_merge($final, $taxon_ids4);

                                foreach($taxon_ids4 as $id4) {
                                    if($taxon_ids5 = @$this->child_of[$id4]) {
                                        $final = array_merge($final, $taxon_ids5);
                                    }
                                }


                            }
                        }
                    }
                }
            }       
        }
        return $final;
    }
    private function fill_up_blank_fieldnames($cols)
    {
        $i = 0;
        foreach($cols as $col) {
            $i++;
            $final['col_'.$i] = '';
        }
        return array_keys($final);
    }
    private function prepare_archive_for_access()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "ReadmeMySql.txt", array('timeout' => 172800, 'expire_seconds' => false)); //won't expire anymore
        return $paths;
    }
    /*
    function get_unmapped_strings()
    {
        self::initialize_mapping(); //un-comment in real operation
        if(!($info = self::prepare_archive_for_access())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        $locations = array("distribution.csv", "use.csv");
        echo "\nProcessing CSV archive...\n";
        foreach($tables['http://eol.org/schema/media/document'] as $tbl) {
            if(in_array($tbl->location, $locations)) {
                echo "\n -- Processing [$tbl->location]...\n";
                self::process_extension($tbl->file_uri, $tbl, $tbl->location, 'utility');
            }
        }
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
        //massage debug for printing
        $countries = array_keys($this->for_mapping['use.csv']); asort($countries);
        $territories = array_keys($this->for_mapping['distribution.csv']); asort($territories);
        $this->for_mapping = array();
        foreach($countries as $c) $this->for_mapping['use.csv'][$c] = '';
        foreach($territories as $c) $this->for_mapping['distribution.csv'][$c] = '';
        Functions::start_print_debug($this->for_mapping, $this->resource_id);
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
    private function process_extension($csv_file, $tbl, $group, $purpose = 'traitbank') //purpose = traitbank OR utility
    {
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
                $rec = array_map('trim', $rec); //important step
                // print_r($fields); print_r($rec); exit;
                // Array()
                if($purpose == 'traitbank') self::create_trait($rec, $group);
                elseif($purpose == 'taxon') self::create_taxon($rec);
                elseif($purpose == 'comnames') self::create_vernaculars($rec);
                elseif($purpose == 'reference') self::create_reference($rec);
                elseif($purpose == 'text_object') self::create_text_object($rec);
                elseif($purpose == 'utility') {
                    if($val = @$rec['Region']) $this->for_mapping = self::separate_strings($val, $this->for_mapping, $group);
                    if($val = @$rec['Use'])    $this->for_mapping = self::separate_strings($val, $this->for_mapping, $group);
                }
            } //main records
        } //main loop
        fclose($file);
    }
    private function create_vernaculars($rec)
    {
        // print_r($rec); exit;
        // Array()
        $this->taxa_with_trait[$rec['REF|Plant|theplant']] = ''; //to be used when creating taxon.tab
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec['REF|Plant|theplant'];
        $v->vernacularName  = $rec['common'];
        $v->language        = $rec['Language to Change ISO 639-3'];
        $v->countryCode     = $rec['country'];
        $this->archive_builder->write_object_to_file($v);
    }
    private function create_reference($rec)
    {
        // print_r($rec); exit;
        // Array()
        $r = new \eol_schema\Reference();
        $r->identifier = $rec['DEF_id'];
        $r->full_reference = $rec['author']." ".$rec['year'].". ".$rec['title'].".";
        $r->authorList = $rec['author'];
        $r->title = $rec['title'];
        // $r->uri = '';
        if(!isset($this->reference_ids[$r->identifier])) {
            $this->reference_ids[$r->identifier] = '';
            $this->archive_builder->write_object_to_file($r);
        }
    }
    private function create_text_object($rec)
    {
        // print_r($rec); //exit;
        // Array()
        $this->taxa_with_trait[$rec['REF|Plant|theplant']] = ''; //to be used when creating taxon.tab
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $rec['REF|Plant|theplant'];
        $mr->identifier     = $rec['DEF_id'];
        $mr->type           = $rec['type'];
        $mr->language       = 'en';
        $mr->format         = "text/html";
        $mr->CVterm         = $rec['Subject'];
        // $mr->Owner          = '';
        // $mr->rights         = '';
        $mr->title          = $rec['Title'];
        $mr->UsageTerms     = $rec['blank_1'];
        $mr->description    = $rec['description'];
        // $mr->LocationCreated = '';
        $mr->bibliographicCitation = $this->partner_bibliographicCitation;
        $mr->furtherInformationURL = $this->partner_source_url;
        $mr->referenceID = $rec['REF|Reference|ref'];
        if(!@$rec['REF|Reference|ref']) {
            print_r($rec);
            exit("\nNo reference!\n");
        }
        // if($agent_ids = )  $mr->agentID = implode("; ", $agent_ids);
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function create_taxon($rec)
    {
        if(!isset($this->taxa_with_trait[$rec['DEF_id']])) return;
        // print_r($rec); exit;
        // Array()
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
                $string_val = Functions::conv_to_utf8($string_val);
                $rec = array();
                $rec["taxon_id"] = $taxon_id;
                $rec["catnum"] = $taxon_id.'_'.$rek['id'];
                if($string_uri = self::get_string_uri($string_val)) {
                    $this->taxa_with_trait[$taxon_id] = ''; //to be used when creating taxon.tab
                    $rec['measurementRemarks'] = $string_val;
                    $rec['bibliographicCitation'] = $this->partner_bibliographicCitation;
                    $rec['source'] = $this->partner_source_url;
                    $rec['referenceID'] = 1;
                    $this->func->add_string_types($rec, $string_uri, $mtype, "true");
                }
                elseif($val = @$this->addtl_mappings[strtoupper(str_replace('"', "", $string_val))]) {
                    $this->taxa_with_trait[$taxon_id] = ''; //to be used when creating taxon.tab
                    $rec['measurementRemarks'] = $string_val;
                    self::write_addtl_mappings($val, $rec);
                }
                else $this->debug[$group][$string_val] = '';
            }
        }
    }
    private function write_addtl_mappings($rek, $rec)
    {
        // print_r($rek); exit;
        // Array()
        if($rek['measurementType'] == "DISCARD") return;
        $rec['measurementRemarks'] = $rek['measurementRemarks'];
        // print_r($rec); exit;
        // Array()
        $tmp = str_replace('"', "", $rek['measurementValue']);
        $tmp = explode(",", $tmp);
        $tmp = array_map('trim', $tmp);
        // print_r($tmp); exit;
        // Array()
        foreach($tmp as $string_uri) {
            $rec['bibliographicCitation'] = $this->partner_bibliographicCitation;
            $rec['source'] = $this->partner_source_url;
            $rec['referenceID'] = 1;
            $this->func->add_string_types($rec, $string_uri, $rek['measurementType'], "true");
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
    private function initialize_mapping()
    {
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        self::use_mapping_from_jen();
        // print_r($this->uris);
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
                // Array()
                $this->uris[$rec['Use string']] = $rec['URI'];
            } //main records
        } //main loop
        fclose($file);
        unlink($csv_file);
    }
    private function addtl_mappings()
    {
        $options = $this->download_options;
        // $options['expire_seconds'] = true; //debug only
        $tmp_file = Functions::save_remote_file_to_local($this->addtl_mapping_from_jen, $options);
        $i = 0;
        foreach(new FileIterator($tmp_file) as $line => $row) {
            $row = Functions::conv_to_utf8($row);
            $i++; 
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); //exit;
                // Array()
                $str = str_replace('"', "", $rec['distribution.csv']);
                $str = strtoupper($str);
                $final[$str] = $rec;
            }
        }
        unlink($tmp_file);
        $this->addtl_mappings = $final;
        // print_r($final); exit;
    }
    
    
    ===============
        works ok if you don't need to format/clean the entire row.
        $file = Functions::file_open($this->text_path[$type], "r");
        while(!feof($file)) { $row = fgetcsv($file); }
        fclose($file);
        
    
    */
}
?>
