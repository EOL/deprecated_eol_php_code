<?php
namespace php_active_record;
/* connector: [parasitic_carnivorous_plants.php] https://eol-jira.bibalex.org/browse/DATA-1834
*/
class Parasitic_Carnivorous_PlantDB
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        // $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        // $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //orig expires quarterly
        // $this->download_options['expire_seconds'] = false; //debug only

        $this->services['parasitic'] = 'http://www.omnisterra.com/bot/pp_home.cgi?name=LETTER&submit=Submit&search=valid_only';
        $this->services['carnivorous'] = 'http://www.omnisterra.com/bot/cp_home.cgi?name=LETTER&submit=Submit&search=accepted';
    }
    function start_create_table()
    {   /* the search box does respond to a 1 letter string, so my suggestion is to do searches with a,e,i,o, u, and y; queries of the form
        http://www.omnisterra.com/bot/pp_home.cgi?name=a&submit=Submit&search=valid_only (parasitic plants)
        AND
        http://www.omnisterra.com/bot/cp_home.cgi?name=a&submit=Submit&search=accepted (carnivorous plants)
        */
        $letters = array('a','e','i','o','u','y');
        $services = array('parasitic', 'carnivorous');
        foreach($services as $service) {
            foreach($letters as $letter) {
                $url = str_replace('LETTER', $letter, $this->services[$service]);
                self::parse_page($url);
            }
        }
    }
    private function parse_page($url)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match_all("/<dl>(.*?)<\/dl>/ims", $html, $arr)) {
                // print_r($arr[1]);
                foreach($arr[1] as $dl) {
                    $cols = array();
                    if(preg_match("/<dt>(.*?)\n/ims", $dl, $arr2))        $cols[] = $arr2[1];
                    if(preg_match_all("/<dd>(.*?)<\/dd>/ims", $dl, $arr3)) $cols = array_merge($cols, $arr3[1]);
                    print_r($cols);
                    
                }
            }
        }
        exit("\nend muna\n");
    }
}
?>
