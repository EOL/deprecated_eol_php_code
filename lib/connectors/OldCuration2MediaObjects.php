<?php
namespace php_active_record;
/* connector: [curation2media_objects.php] 
*/
class OldCuration2MediaObjects
{
    function __construct($folder = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->debug = array();
        
        $working_dir = "/Volumes/Thunderbolt4/old_curation_2media/7662/";
        $this->path['media_csv'] = $working_dir."media.csv";
        
    }
    public function start()
    {
        self::investigate_media();
    }
    private function investigate_media()
    {
        $file = fopen($this->path['media_csv'], 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++; echo " $i";
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                print_r($rec); //exit;
                /*
                */
            }
        }
        fclose($file);
        
    }
}
?>
