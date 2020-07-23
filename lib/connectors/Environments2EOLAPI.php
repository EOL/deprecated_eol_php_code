<?php
namespace php_active_record;
/* connector: [environments_2_eol.php]
*/
class Environments2EOLAPI
{
    function __construct($param)
    {
        print_r($param);
        // if($folder) {
        //     $this->resource_id = $folder;
        //     $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        //     $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        // }
        // $this->debug = array();
        $this->DwCA_URLs['AmphibiaWeb text'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/21.tar.gz';
    }

    // $info = self::start(false, array('timeout' => 172800, 'expire_seconds' => 60*60*24*30)); //1 month expire
    
    private function parse_dwca($resource, $download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*30))
    {
        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->DwCA_URLs[$resource], "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit("\n-exit muna-\n");
        */
        // /* development only
        $paths = Array("archive_path" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_64006/",
                       "temp_dir" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_64006/");
        // */
        
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
    function gen_txt_files_4_articles($resource)
    {
        $info = self::parse_dwca($resource);
        // print_r($info); exit;
        $tables = $info['harvester']->tables;
        self::process_table($tables['http://eol.org/schema/media/document'][0]);
    }
    private function process_table($meta)
    {   //print_r($meta);
        echo "\nprocess media tab...\n";
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            print_r($rec); exit;
        }
    }
}
?>
