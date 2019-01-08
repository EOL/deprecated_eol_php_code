<?php
namespace php_active_record;
/* connector: [africa_tree_db.php] 
Part of this connector was taken from CSV2DwCA_Utility.php
*/
class AfricaTreeDBAPI
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
    }
    function convert_archive()
    {
    }
    private function prepare_archive_for_access()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => false)); //won't expire anymore
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
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
                self::process_extension($tbl->file_uri, $tbl, $tbl->location);
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
    private function process_extension($csv_file, $tbl, $group)
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
                // print_r($fields); print_r($rec); exit;
                
                /*Array(
                    [id] => dist_99
                    [blank_1] => http://purl.org/dc/dcmitype/Text
                    [blank_2] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
                    [Plant No] => 99
                    [Region] => Eastern Arc Mountains: Udzungwa Mts ; Eastern Arc Mountains: West Usambara Mts
                    [Ref] => 1
                    [blank_3] => http://creativecommons.org/licenses/by-sa/3.0/
                )
                */
                if($val = @$rec['Region']) $this->for_mapping = self::separate_strings($val, $this->for_mapping, $group);
                if($val = @$rec['Use'])    $this->for_mapping = self::separate_strings($val, $this->for_mapping, $group);
                
                
            } //main records
        } //main loop
        fclose($file);
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
    private function initialize_mapping()
    {
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
    }
}
?>
