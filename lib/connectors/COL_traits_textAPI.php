<?php
namespace php_active_record;
/* connector: [COL_trait_text.php]
*/
class COL_traits_textAPI
{
    function __construct($folder = false)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array('cache' => 1, 'expire_seconds' => 60*60*24*30*6, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1); //6 months to expire
    }

    function start()
    {
        
    }
}
?>