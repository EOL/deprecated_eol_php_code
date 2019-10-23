<?php
namespace php_active_record;
// connector: [gbif_classification.php]
class NatlChecklistReplacementConnAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array(
            'resource_id'        => 'gbif',
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'cache' => 1);
        
        /* from copied template
        if(Functions::is_production()) {
            $this->service["backbone_dwca"] = "http://rs.gbif.org/datasets/backbone/backbone-current.zip";
            $this->service["gbif_classification"] = "https://editors.eol.org/eol_php_code/applications/content_server/resources/gbif_classification.tar.gz";
        }
        else {
            $this->service["backbone_dwca"] = "http://localhost/cp/GBIF_Backbone_Archive/backbone-current.zip";
            $this->service["gbif_classification"] = "/Volumes/MacMini_HD2/work_temp/gbif_classification.tar.gz";
        }
        */
        $this->log_file = CONTENT_RESOURCE_LOCAL_PATH.'xxx.txt';
        $this->debug = array();
    }
    function start()
    {   $paths = self::access_dwca('backbone_dwca');
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        if(!($file = Functions::file_open($this->log_file, "w"))) return;
        fwrite($file, implode("\t", array('taxonID', 'scientificName', 'searched string', 'flag'))."\n");
        fclose($file);
        
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        $this->archive_builder->finalize(TRUE);

        // /* un-comment in real operation
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
    }
    private function access_dwca($dwca, $expire_seconds = false)
    {   
        $download_options = $this->download_options;
        if($expire_seconds) $download_options['expire_seconds'] = $expire_seconds;
        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->service[$dwca], "meta.xml", $download_options);
        print_r($paths); exit;
        */
        // /* local when developing
        if($dwca == 'gbif_classification') {
            $paths = Array(
                "archive_path" => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_classification/",
                "temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_classification/"
            );
        }
        if($dwca == 'DH0.9') {
            $paths = Array(
                'archive_path' => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_DH09/",
                'temp_dir' => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_DH09/"
            );
        }
        // */
        return $paths;
    }
    private function process_taxon($meta)
    {   //print_r($meta);
        require_library('connectors/Eol_v3_API');
        $func = new Eol_v3_API();
        
        echo "\nprocess_taxon...\n"; $i = 0;
        $m = 5858200/7; //total rows = 5,858,143. Rounded to 5858200. For caching.
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
            // print_r($rec); exit;
            /**/
            // if($i >= 10) break;
        }
    }
    private function log_record($rec, $sciname = '', $flag = '')
    {
        if(!($file = Functions::file_open($this->log_file, "a"))) return;
        fwrite($file, implode("\t", array($rec['http://rs.tdwg.org/dwc/terms/taxonID'], $rec['http://rs.tdwg.org/dwc/terms/scientificName'], "[$sciname]", $flag))."\n");
        fclose($file);
    }
    private function write_archive($rec, $eol_rec)
    {
        $fields = array_keys($rec);
        /**/
        $taxon = new \eol_schema\Taxon();
        $taxon->EOLid = $eol_rec['id'];
        foreach($fields as $field) {
            $var = pathinfo($field, PATHINFO_BASENAME);
            if(in_array($var, array('genericName'))) continue;
            $taxon->$var = $rec[$field];
        }
        $this->archive_builder->write_object_to_file($taxon);
    }
}
?>