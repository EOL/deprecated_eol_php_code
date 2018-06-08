<?php
namespace php_active_record;
/* connector [185] Connector scrapes the partner's site, assembles the information and generates a DWC-A */
class TurbellarianAPI_v2
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->domain = "http://turbellaria.umaine.edu";
        $this->taxa_url = $this->domain . "/turb3.php?action=1&code=";
        $this->rights_holder = "National Science Foundation - Turbellarian Taxonomic Database";
        $this->agents = array();
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Seth Tyler");
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Steve Schilling");
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Matt Hooge");
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Louise Bush");
        $this->SPM = "http://rs.tdwg.org/ontology/voc/SPMInfoItems";
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';

        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->download_options = array('download_wait_time' => 500000, 'timeout' => 9600, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'expire_seconds' => 60*60*24*25);

        $this->page['main'] = "http://turbellaria.umaine.edu/turbella.php";
        $this->page['action_1'] = "http://turbellaria.umaine.edu/turb3.php?action=1&code=";
    }

    function start()
    {
        /* main operation
        $all_ids = self::get_all_ids();
        foreach($all_ids as $code) {
            self::process_page($code);
        }
        */

        self::process_page(3511); //3158 3191
        exit;
    }
    private function format_html($html)
    {
        $html = str_ireplace("<td >", "<td>", $html);
        $html = str_ireplace("<th >", "<th>", $html);
        $html = str_ireplace("<td>&nbsp;</td>", "", $html);
        return $html;
    }
    private function process_page($id)
    {
        $html = Functions::lookup_with_cache($this->page['action_1'].$id, $this->download_options);
        $html = self::format_html($html);
        $html = self::get_string_starting_from('table of subtaxa', $html);
        if(preg_match("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
            $str = $arr[1];
            $direct_images = self::get_direct_images($str, $id);        //action=2
            // $downline_images = self::get_downline_images($str, $id);    //action=23
            // $synonyms = self::get_synonyms($str, $id);                  //action=6
        }
    }
    private function get_direct_images($str, $id) //action=2
    {
        if(stripos($str, 'action=2&') !== false) {//string is found
            echo "\nwith direct image\n";
        }
    }
    private function get_all_ids()
    {
        $stack = array();
        $main_ids = self::get_main_ids(); //get main IDs from home page
        // print_r($main_ids); exit;
        foreach($main_ids as $id1) {
            $ids1 = self::get_valid_ids($id1); $stack = array_merge($stack, $ids1);
            foreach(array_keys($ids1) as $id2) {
                $ids2 = self::get_valid_ids($id2); $stack = array_merge($stack, $ids2);
                foreach(array_keys($ids2) as $id3) {
                    $ids3 = self::get_valid_ids($id3); $stack = array_merge($stack, $ids3);
                    foreach(array_keys($ids3) as $id4) {
                        $ids4 = self::get_valid_ids($id4); $stack = array_merge($stack, $ids4);
                        foreach(array_keys($ids4) as $id5) {
                            $ids5 = self::get_valid_ids($id5); $stack = array_merge($stack, $ids5);
                            foreach(array_keys($ids5) as $id6) {
                                $ids6 = self::get_valid_ids($id6); $stack = array_merge($stack, $ids6);
                                foreach(array_keys($ids6) as $id7) {
                                    $ids7 = self::get_valid_ids($id7); $stack = array_merge($stack, $ids7);
                                    foreach(array_keys($ids7) as $id8) { 
                                        $ids8 = self::get_valid_ids($id8); $stack = array_merge($stack, $ids8);
                                        foreach(array_keys($ids8) as $id9) { //exit("\nlevel 8\n");
                                            $ids9 = self::get_valid_ids($id9); $stack = array_merge($stack, $ids9);
                                            foreach(array_keys($ids9) as $id10) { //exit("\nlevel 9\n");
                                                $ids10 = self::get_valid_ids($id10); $stack = array_merge($stack, $ids10);
                                                foreach(array_keys($ids10) as $id11) { //exit("\nlevel 10\n");
                                                    $ids11 = self::get_valid_ids($id11); $stack = array_merge($stack, $ids11);
                                                    foreach(array_keys($ids11) as $id12) { //exit("\nlevel 11\n");
                                                        $ids12 = self::get_valid_ids($id12); $stack = array_merge($stack, $ids12);
                                                        foreach(array_keys($ids12) as $id13) { //exit("\nlevel 12\n");
                                                            $ids13 = self::get_valid_ids($id13); $stack = array_merge($stack, $ids13);
                                                            foreach(array_keys($ids13) as $id14) { //exit("\nlevel 13\n");
                                                                $ids14 = self::get_valid_ids($id14); $stack = array_merge($stack, $ids14);
                                                                foreach(array_keys($ids14) as $id15) { exit("\nlevel 14\n");
                                                                    $ids15 = self::get_valid_ids($id15); $stack = array_merge($stack, $ids15);
                                                                    foreach(array_keys($ids15) as $id16) { exit("\nlevel 15\n");
                                                                        $ids16 = self::get_valid_ids($id16); $stack = array_merge($stack, $ids16);
                                                                        foreach(array_keys($ids16) as $id17) { exit("\nlevel 16\n");
                                                                            $ids17 = self::get_valid_ids($id17); $stack = array_merge($stack, $ids17);
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        print_r($stack);
        echo "\n".count($stack)."\n";
        exit("\n-stopx-\n");
        return array_keys($stack);
    }
    private function get_valid_ids($id)
    {
        $valid = array();
        $html = Functions::lookup_with_cache($this->page['action_1'].$id, $this->download_options);
        $html = self::get_string_starting_from('table of subtaxa', $html);
        if(preg_match_all("/action=1&code=(.*?)<\/td>/ims", $html, $arr)) {
            print_r($arr[1]);
            foreach($arr[1] as $row) {
                if(stripos($row, '<font color="red">') !== false) continue; //string is found
                if(stripos($row, '<font color="00cc00">') !== false) continue; //string is found
                echo "\n[$row]";
                if(preg_match("/elix173(.*?)\"/ims", 'elix173'.$row, $arr2)) $valid[$arr2[1]] = '';
            }
        }
        return $valid;
    }
    private function get_string_starting_from($str, $html)
    {
        if(preg_match("/".$str."(.*?)elix173/ims", $html."elix173", $arr)) return $arr[1];
    }
    private function get_main_ids() //get main IDs from home page
    {
        $html = Functions::lookup_with_cache($this->page['main'], $this->download_options);
        if(preg_match_all("/action=1&code=(.*?)\"/ims", $html, $arr)) return $arr[1];
    }

    private function xxx()
    {
        /* manually adding Bilateria */
        $rec = array();
        $rec["sciname"] = "Bilateria";
        $rec["taxon_id"] = "Bilateria";
        $rec["authorship"] = "";
        $rec["parent_id"] = "";
        $rec["status"] = "";
        $this->create_instances_from_taxon_object($rec);
        $this->create_archive();
        /* remove temp dir */
        recursive_rmdir($this->TEMP_DIR);
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
    }
}
?>
