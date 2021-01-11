<?php
namespace php_active_record;
/* connector: [recode_unrecognized_fields.php]
*/
class RecodeUnrecognizedFieldsAPI
{
    function __construct($resource_id = NULL, $dwca_file = NULL, $params = array())
    {
        if($resource_id) {
            $this->resource_id = $resource_id;
            $this->dwca_file = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '.tar.gz';
        }
        $this->download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*1); //probably default expires in 1 day 60*60*24*1. Not false.
        $this->debug = array();
    }
    public function scan_dwca($dwca_file = false) //utility to search meta.xml for certain fields
    {
        if(!$dwca_file) $dwca_file = $this->dwca_file; //used if called elsewhere
        $paths = self::extract_dwca($dwca_file);
        if(is_file($paths.'meta.xml')) self::parse_meta_xml($paths.'meta.xml');
        else echo "\n- No meta.xml [$dwca_file]\n";
        
        // remove temp dir
        recursive_rmdir($paths['temp_dir']);
        echo ("\n temporary directory removed: " . $paths['temp_dir']);
    }
    private function extract_dwca($dwca_file)
    {
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, "meta.xml", $this->download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        print_r($paths); //exit("\n-exit muna-\n");
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_99613/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_99613/'
        );
        */
        return $paths;
    }
}
?>