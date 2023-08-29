<?php
namespace php_active_record;
/* connector: [wikipedia_html.php] */
class WikipediaHtmlAPI
{
    function __construct()
    {
        $this->debug = array();
        $this->download_options = array(
            'expire_seconds'     => 60*60*24*30, //expires in 1 month
            'download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        
        $this->html_path = CONTENT_RESOURCE_LOCAL_PATH . "/reports/wikipedia_html/";
        if(!is_dir($this->html_path)) mkdir($this->html_path);

        //https://editors.eol.org/eol_php_code/applications/content_server/resources/reports/taxon_wiki_per_language_count_2023_08.txt
        $this->source_languages = CONTENT_RESOURCE_LOCAL_PATH."reports/taxon_wiki_per_language_count_YYYY_MM.txt";
    }
    function start()
    {
        $txt_file = self::get_languages_list();
        exit("\n$txt_file\n");

    }
    function save_text_to_html($filename)
    {
        $results = array();
        $dwca = CONTENT_RESOURCE_LOCAL_PATH . "/$filename".".tar.gz";
        // /* un-comment in real operation
        if(!($info = self::prepare_archive_for_access($dwca))) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        foreach($tables['http://eol.org/schema/media/document'] as $tbl) { //always just 1 record
            $tbl = (array) $tables['http://eol.org/schema/media/document'][0];
            // print_r($tbl); exit;
            // [location] => media_resource.tab
            // [file_uri] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_41336//media_resource.tab
            $taxon_tab = $tbl["file_uri"];
            if(file_exists($taxon_tab)) self::process_extension($taxon_tab, $filename);    
            else {
                $results['media does not exist'][$filename] = '';
            }

        }
        // */

        /* during dev only
        $tbl = array();
        $tbl["location"] = "media_resource.tab";
        $tbl["file_uri"] = "/Volumes/AKiTiO4/eol_php_code_tmp/dir_41651";
        $taxon_tab = $tbl["file_uri"]."/".$tbl["location"];
        echo "\n -- Processing [$tbl[location]]...\n";
        self::process_extension($taxon_tab, $filename);
        */

        print_r($results);
        print_r($this->debug);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
    }
    private function prepare_archive_for_access($dwca)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*30)); //1 month expires
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields) ||
           !($tables["http://eol.org/schema/media/document"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate. [$dwca]");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    private function process_extension($taxon_tab, $filename)
    {
        $i = 0; $savedYN = false;
        foreach(new FileIterator($taxon_tab) as $line => $row) { $i++; 
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); // print_r($rec); exit;
            
                if($rec['CVterm'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description") {
                    self::save_to_html($rec['description'], $filename);
                    $savedYN = true;
                }
                else continue;
                /* the shorter text object
                if($rec['CVterm'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology") self::save_to_html($rec['description'], $filename);
                else continue;
                */
                break; //part of main operation, process only 1 record.
                // if($i >= 1000) break; //debug only
            }
        }
        if(!$savedYN) $this->debug['no HTML page'][$filename];
    }
    private function save_to_html($desc, $filename)
    {
        $html_file = $this->html_path.$filename.".html";
        $WRITE = fopen($html_file, "w");
        fwrite($WRITE, $desc);
        fclose($WRITE);
    }
    private function get_languages_list()
    {
        $ym = date("Y_m"); //2023_08 for Aug 2023 --- current month
        $txt_file = str_replace("YYYY_MM", $ym, $this->source_languages);
        if(file_exists($txt_file)) return $txt_file;
        else {
            $minus_1_month = date("Y_m", strtotime("-1 months")); //minus 1 month --- 2023_07
            $txt_file = str_replace("YYYY_MM", $minus_1_month, $this->source_languages);
            if(file_exists($txt_file)) return $txt_file;
            else {
                $minus_2_month2 = date("Y_m", strtotime("-2 months")); //minus 2 month2 --- 2023_06
                $txt_file = str_replace("YYYY_MM", $minus_2_month2, $this->source_languages);
                if(file_exists($txt_file)) return $txt_file;
                else exit("\nInvestigate: No source text file for list of languages [$txt_file].\n");
            }
        }
    }
}
?>