<?php
namespace php_active_record;
/* connector: [wikipedia_html.php]
This generates a single HTML page for every wikipedia-xxx.tar.gz. It gets one text object and generates an HTML page for it.
The subject used is ---> CVterm == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description"
*/
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
        // used as list of langs to generate HTML for
    }
    function start()
    {
        $txt_file = self::get_languages_list(); // exit("\n$txt_file\n");
        $i = 0;
        foreach(new FileIterator($txt_file) as $line => $row) { $i++; 
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //print_r($rec); exit;
                /* Array(
                    [language] => ceb
                    [count] => 1729986
                ) */
                if($rec['language'] == "en")        $filename = "80";
                elseif($rec['language'] == "de")    $filename = "957";
                else                                $filename = "wikipedia-".$rec['language']; //mostly goes here
                self::save_taxon_text_to_html($filename);
            }
        }
        echo "\nNo DwCA: ".count($this->debug['[No DwcA]'])."\n";
        print_r($this->debug);
        self::generate_main_html_page(); //uses ["reports/wikipedia_html/*.html"] in eol-archive to select HTML to be included in main.html.
        /* To do:
        self::generate_main_html_page2(); //uses [taxon_wiki_per_language_count_2023_08.txt] to select HTML to be included in main.html.
        That is to have a descending order of total taxa in main.html.
        */
    }
    private function generate_main_html_page()
    {
        $dir = CONTENT_RESOURCE_LOCAL_PATH."reports/wikipedia_html/";
        $files = glob($dir . "*.html");
        if($files) {
            $filecount = count($files); echo "\nHTML count: [$filecount]\n";
            print_r($files);
            /* Array(
                [0] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/wikipedia_html/80.html
                [1] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/wikipedia_html/ceb.html
                [2] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/wikipedia_html/nl.html
            )*/

            if(Functions::is_production())  $path = "https://editors.eol.org/eol_php_code/applications/content_server/resources/reports/wikipedia_html/";
            else                            $path = "http://localhost/eol_php_code/applications/content_server/resources_3/reports/wikipedia_html/";

            $main_html = $dir."main.html";

            $OUT = fopen($main_html, "w");
            $first = self::get_first_part_of_html();
            fwrite($OUT, $first);

            foreach($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME); //e.g. be-x-old for be-x-old.html
                if($filename == "main") continue;
                $href = $path.$filename.".html";
                $anchor = "<a href = '$href'>$filename</a> &nbsp;|&nbsp; ";
                fwrite($OUT, $anchor);
            }
            fwrite($OUT, "</body></html>");
            fclose($OUT);
        }
    }
    private function get_first_part_of_html()
    {
        return '<!DOCTYPE html>
        <html><head>
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
            <title>Wikipedia Languages Test HTML</title>
        </head><body>';
    }
    function save_taxon_text_to_html($filename)
    {
        $dwca = CONTENT_RESOURCE_LOCAL_PATH . "/$filename".".tar.gz";
        if(!file_exists($dwca)) {
            $this->debug['No DwcA'][$filename] = '';
            return;
        }
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
            $media_tab = $tbl["file_uri"];
            if(file_exists($media_tab)) self::process_extension($media_tab, $filename);    
            else {
                $this->debug['media tab does not exist'][$filename] = '';
            }
        }
        // */

        /* during dev only
        $tbl = array();
        $tbl["location"] = "media_resource.tab";
        $tbl["file_uri"] = "/Volumes/AKiTiO4/eol_php_code_tmp/dir_41651";
        $media_tab = $tbl["file_uri"]."/".$tbl["location"];
        echo "\n -- Processing [$tbl[location]]...\n";
        self::process_extension($media_tab, $filename);
        */

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
    private function process_extension($media_tab, $filename)
    {
        $i = 0; $savedYN = false;
        foreach(new FileIterator($media_tab) as $line => $row) { $i++; 
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
        if(!$savedYN) $this->debug['no HTML page generated'][$filename];
    }
    private function save_to_html($desc, $filename)
    {
        $filename = str_replace("wikipedia-","",$filename);
        $html_file = $this->html_path.$filename.".html";
        $WRITE = fopen($html_file, "w");

        $first = self::get_first_part_of_html();
        fwrite($WRITE, $first);
        fwrite($WRITE, $desc);
        fwrite($WRITE, "</body></html>");
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