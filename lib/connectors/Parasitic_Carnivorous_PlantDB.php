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
        $letters = array('u');
        $services = array('parasitic', 'carnivorous');
        foreach($services as $service) {
            foreach($letters as $letter) {
                $url = str_replace('LETTER', $letter, $this->services[$service]);
                self::parse_page($url);
            }
        }
        // print_r($this->main_records);
        echo "\ntotal: ".count($this->main_records)."\n";
    }
    private function parse_page($url)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = str_replace("</b><dd>", "</b></dd><dd>", $html);
            if(preg_match_all("/<dl>(.*?)<\/dl>/ims", $html, $arr)) {
                // print_r($arr[1]);
                foreach($arr[1] as $dl) {
                    $cols = array();
                    if(preg_match("/<dt>(.*?)\n/ims", $dl, $arr2))        $cols[] = $arr2[1];
                    if(preg_match_all("/<dd>(.*?)<\/dd>/ims", $dl, $arr3)) $cols = array_merge($cols, $arr3[1]);
                    $cols = array_map('trim', $cols);
                    $cols = array_map('strip_tags', $cols);
                    print_r($cols);
                    /* e.g. $cols
                    Array(
                        [0] => N: [Zeuxine violascens {Ridl.}]
                        [1] => P: Mat.Fl.Mal.Penins.1:218 (1907)
                        [2] => T: Mal. Penins., MY, (?)
                        [3] => S: =[Zeuxine purpurascens {Bl.}]
                    )
                    */
                    $ret = self::format_cols($cols);
                    // print_r($ret); exit;
                    $this->main_records[$ret['id']] = $ret['cols'];
                }
            }
        }
        // exit("\nend muna\n");
    }
    private function format_cols($cols)
    {   /* almost good but not quite since "LFR: " is sometimes "LFR:". So not uniformed.
        foreach($cols as $col) {
            $tmp = explode(': ', $col);
            $tmp = array_map('trim', $tmp);
            $final[$tmp[0]] = $tmp[1];
        }
        return array('id' => md5(json_encode($final)), 'cols' => $final);
        */
        print_r($cols);
        foreach($cols as $col) {
            $tmp = explode(':', $col);
            $head = $tmp[0];
            array_shift($tmp);
            $tmp = array_map('trim', $tmp);
            $value = implode(':', $tmp);
            $final[$head] = $value;
        }
        print_r($final);
        return array('id' => md5(json_encode($final)), 'cols' => $final);
    }
}
?>
