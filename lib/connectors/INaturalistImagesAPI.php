<?php
namespace php_active_record;
/* connector: [430] */
class INaturalistImagesAPI
{
    function __construct($folder)
    {
        /*
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('resource_id' => '959', 'expire_seconds' => false, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); 
        */
        $this->dwca_file = "http://www.inaturalist.org/taxa/eol_media.dwca.zip";
        $this->dwca_file = "http://localhost/cp/iNaturalist/eol_media.dwca.zip";
    }

    function start_fix_supplied_archive_by_partner()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*25, 'cache' => 1)); //expires in 25 days 
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        print_r($paths);
        
        self::process_media_extension($archive_path);
        
        recursive_rmdir($temp_dir);
    }
    private function process_media_extension($archive_path)
    {
        $do_ids = array(); //for validation, prevent duplicate identifiers
        $csv_file = $archive_path."/media.csv";
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            $i++;
            if($i == 1) {
                $fields = $row;
                $count = count($fields);
            }
            else { //main records

                if($count != count($values)) { //row validation
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    $this->debug['wrong csv']['identifier'][$rec['identifier']] = '';
                    continue;
                }

                $values = $row;
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                
                //start process record =============================================================================================
                if($rec['taxonID'] && $rec['accessURI']){
                    if(self::valid_uri_url($rec['accessURI'])) continue;
                    if(self::valid_uri_url($rec['thumbnailURL'])) $rec['thumbnailURL'] = "";
                    $do_id = $rec['identifier'];
                    if(in_array($do_id, $do_ids))
                    {
                        exit("\nduplicate do_id\n");
                    }
                    else $do_ids[] = $do_id;
                }
                //end process record =============================================================================================
                // print_r($rec); exit;
                
            } //main records
        } //main loop
        fclose($file);
        print_r($this->debug);
    }
    private function valid_uri_url($str)
    {
        if(substr($str,0,7) == "http://") return true;
        elseif(substr($str,0,8) == "https://") return true;
        return false;
    }
    
    private function clean_html($html)
    {
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        return Functions::remove_whitespace($html);
    }

}
?>
