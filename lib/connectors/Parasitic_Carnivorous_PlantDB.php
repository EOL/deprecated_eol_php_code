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
        $this->groups = array('parasitic', 'carnivorous');
        // $this->groups = array('carnivorous'); //debug only
        $this->file['parasitic'] = CONTENT_RESOURCE_LOCAL_PATH.'/parasitic_plants.txt';
        $this->file['carnivorous'] = CONTENT_RESOURCE_LOCAL_PATH.'/carnivorous_plants.txt';
    }
    function start_create_table()
    {   /* the search box does respond to a 1 letter string, so my suggestion is to do searches with a,e,i,o, u, and y; queries of the form
        http://www.omnisterra.com/bot/pp_home.cgi?name=a&submit=Submit&search=valid_only (parasitic plants)
        AND
        http://www.omnisterra.com/bot/cp_home.cgi?name=a&submit=Submit&search=accepted (carnivorous plants)
        */
        $letters = array('a','e','i','o','u','y');
        // $letters = array('u'); //debug only
        foreach($this->groups as $group) {
            foreach($letters as $letter) {
                echo "\nprocessing [$group][$letter]\n";
                $url = str_replace('LETTER', $letter, $this->services[$group]);
                self::parse_page($url, $group);
            }
        }
        // print_r($this->main_records);
        echo "\ntotal parasitic: ".count($this->main_records['parasitic'])."\n";
        echo "\ntotal carnivorous: ".count($this->main_records['carnivorous'])."\n";

        self::parse_main_records_then_print();
    }
    private function parse_main_records_then_print()
    {
        foreach($this->groups as $group) {
            $fields = self::get_headers($group);
            // print_r($fields); exit("\n[$group]\n");
            //start writing to flat file
            $f = Functions::file_open($this->file[$group], "w");
            fwrite($f, implode("\t", $fields)."\n");
            foreach($this->main_records[$group] as $key => $rec) {
                $rek = array();
                foreach($fields as $fld) $rek[] = @$rec[$fld];
                fwrite($f, implode("\t", $rek)."\n");
            }
            fclose($f);
        }
    }
    private function get_headers($group)
    {
        foreach($this->main_records[$group] as $key => $rec) {
            // print_r($rec); exit;
            foreach(array_keys($rec) as $key) {
                $key = str_replace(array("\n","\t"), "", $key);
                $final[$key] = '';
            }
        }
        return array_keys($final);
    }
    private function parse_page($url, $service)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = str_replace("</b><dd>", "</b></dd><dd>", $html);
            if(preg_match_all("/<dl>(.*?)<\/dl>/ims", $html, $arr)) {
                foreach($arr[1] as $dl) {
                    $cols = array();
                    if(preg_match("/<dt>(.*?)\n/ims", $dl, $arr2))        $cols[] = $arr2[1];
                    if(preg_match_all("/<dd>(.*?)<\/dd>/ims", $dl, $arr3)) $cols = array_merge($cols, $arr3[1]);
                    $cols = array_map('trim', $cols);
                    $cols = array_map('strip_tags', $cols);
                    // print_r($cols);
                    /* e.g. $cols
                    Array(
                        [0] => N: [Zeuxine violascens {Ridl.}]
                        [1] => P: Mat.Fl.Mal.Penins.1:218 (1907)
                        [2] => T: Mal. Penins., MY, (?)
                        [3] => S: =[Zeuxine purpurascens {Bl.}]
                    )
                    */
                    $ret = self::format_cols($cols);
                    $this->main_records[$service][$ret['id']] = $ret['cols'];
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
        // print_r($cols); //good debug
        foreach($cols as $col) {
            $tmp = explode(':', $col);
            $head = $tmp[0];
            /* good debug
            if($head == 'doi') {
                print_r($cols); exit("\ndoi\n");
            }
            */
            array_shift($tmp);
            $tmp = array_map('trim', $tmp);
            $value = implode(':', $tmp);
            $value = str_replace(array("\n","\t"), "", $value);
            $final[$head] = $value;
        }
        // print_r($final); //good debug
        return array('id' => md5(json_encode($final)), 'cols' => $final);
    }
}
?>
