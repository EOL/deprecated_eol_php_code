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
    public function process_all_resources()
    {
        $arr = array();
        foreach(glob(CONTENT_RESOURCE_LOCAL_PATH . "*.tar.gz") as $filename) {
            $pathinfo = pathinfo($filename, PATHINFO_BASENAME);
            $arr[$pathinfo] = '';
        }
        print_r($arr);
        return $arr;
    }
    public function scan_dwca($dwca_file = false) //utility to search meta.xml for certain fields
    {
        if(!$dwca_file) $dwca_file = $this->dwca_file; //used if called elsewhere
        $paths = self::extract_dwca($dwca_file);
        if(is_file($paths['temp_dir'].'meta.xml')) self::parse_meta_xml($paths['temp_dir'].'meta.xml');
        else echo "\n- No meta.xml [$dwca_file]\n";
        
        // remove temp dir
        /*
        recursive_rmdir($paths['temp_dir']);
        echo ("\n temporary directory removed: " . $paths['temp_dir']);
        */
    }
    private function parse_meta_xml($meta_xml)
    {
        echo "\n$meta_xml\n";
        $xml = simplexml_load_file($meta_xml);
        $final = array();
        foreach($xml->table as $tab) {
            // print_r($tab);
            /*SimpleXMLElement Object(
                [@attributes] => Array(
                        [encoding] => UTF-8
                        [fieldsTerminatedBy] => \t
                        [linesTerminatedBy] => \n
                        [ignoreHeaderLines] => 1
                        [rowType] => http://rs.tdwg.org/dwc/terms/Taxon
                    )
                [files] => SimpleXMLElement Object(
                        [location] => taxon.tab
                    )
                [field] => Array(
                        [0] => SimpleXMLElement Object(
                                [@attributes] => Array(
                                        [index] => 0
                                        [term] => http://rs.tdwg.org/dwc/terms/taxonID
                                    )
                            )
            */
            $rowType = (string) $tab{'rowType'}; //echo "\n$rowType";
            foreach($tab->field as $fld) { //echo "\n".$fld{'term'}."\n";
                $final[$rowType][] = (string) $fld{'term'};
            }
        }
        print_r($final);
    }
    private function extract_dwca($dwca_file)
    {
        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, "meta.xml", $this->download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        print_r($paths); exit("\n-exit muna-\n");
        */

        // /* development only
        $paths = Array(
            // 'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_28647/',
            // 'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_28647/'
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_81560/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_81560/'
        );
        // */
        return $paths;
    }
}
?>