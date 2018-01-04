<?php
namespace php_active_record;

class BHL_Flickr_croppedImagesAPI
{
    public function __construct($folder)
    {
        $this->folder = $folder;
        $this->path['temp_dir'] = "/Volumes/Thunderbolt4/EOL_V2/";
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        
        $flickr_photo_ids = "https://editors.eol.org/eol_php_code/applications/content_server/resources/bhl_images_with_box_coordinates.txt";
    }

    private function start()
    {
        
    }
}
?>
