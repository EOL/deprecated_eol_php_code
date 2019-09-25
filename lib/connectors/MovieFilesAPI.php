<?php
namespace php_active_record;
// connector: [movie_files.php]
class MovieFilesAPI
{
    function __construct($folder, $dwca_file)
    {
        $this->resource_id = $folder;
        $this->dwca = $dwca_file;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array(
            'resource_id'        => 'dwca_and_media',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'cache' => 1);

        if(Functions::is_production()) {
            $this->path['source']       = '/extra/other_files/EOL_media/resources/';
        }
        else {
            $this->path['source']       = '/Volumes/AKiTiO4/web/cp/EOL_media/resources/';
        }

        // $this->log_file = CONTENT_RESOURCE_LOCAL_PATH.'gbif_names_not_found_in_eol.txt';
    }
    private function access_dwca()
    {   
        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca, "meta.xml", $this->download_options);
        print_r($paths);
        */
        // /* local when developing
        $paths = Array(
            "archive_path" => "/Library/WebServer/Documents/eol_php_code/tmp/dir_60894_mov/",
            "temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/dir_60894_mov/"
        );
        // */
        return $paths;
    }
    function start()
    {   $paths = self::access_dwca();
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        // print_r($tables); exit;
        // if(!($file = Functions::file_open($this->log_file, "w"))) return;
        // fwrite($file, implode("\t", array('taxonID', 'scientificName', 'searched string', 'flag'))."\n");
        // fclose($file);
        
        self::process_media($tables['http://eol.org/schema/media/document'][0]);
        // $this->archive_builder->finalize(TRUE);

        /* un-comment in real operation
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        */
    }
    private function process_media($meta)
    {   //print_r($meta);
        echo "\nprocess_taxon...\n"; $i = 0;
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
            print_r($rec); exit;
            /*Array(
            )*/

            // if($i >= 90) break;
        }
    }
    private function log_record($rec, $sciname = '', $flag = '')
    {
        if(!($file = Functions::file_open($this->log_file, "a"))) return;
        fwrite($file, implode("\t", array($rec['http://rs.tdwg.org/dwc/terms/taxonID'], $rec['http://rs.tdwg.org/dwc/terms/scientificName'], "[$sciname]", $flag))."\n");
        fclose($file);
    }
}
?>