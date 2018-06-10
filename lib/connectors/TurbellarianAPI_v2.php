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
        $this->download_options['expire_seconds'] = false;
        
        $this->page['main'] = "http://turbellaria.umaine.edu/turbella.php";
        $this->page['action_1']  = "http://turbellaria.umaine.edu/turb3.php?action=1&code=";
        $this->page['action_2']  = "http://turbellaria.umaine.edu/turb3.php?action=2&code=";
        $this->page['action_23'] = "http://turbellaria.umaine.edu/turb3.php?action=23&code=";
        $this->page['action_16']  = "http://turbellaria.umaine.edu/turb3.php?action=16&code=";
    }

    function start()
    {
        // /* main operation
        $all_ids = self::get_all_ids();
        foreach($all_ids as $code) {
            echo " $code";
            self::process_page($code);
        }
        // */
        // self::process_page(3159); //3158 3191 4901 3511 5654 1223
        // self::get_valid_ids(3159);
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
            // echo "\n[$str]\n";
            // <th>Allostoma</th>
            // <td>Beneden, 1861</td>
            if(preg_match("/<th>(.*?)<\/th>/ims", $str, $arr)) $main_sci['name'] = $arr[1];
            if(preg_match("/<td>(.*?)<\/td>/ims", $str, $arr)) $main_sci['author'] = $arr[1];
            print_r($main_sci);
            
            // $direct_images = self::get_direct_images($str, $id);                        //action=2
            $invalid_names = self::get_invalid_names($html);
            // $downline_images = self::get_downline_images($str, $id, $invalid_names);    //action=23
            $distribution = self::parse_TableOfTaxa($html, $id, $invalid_names, 'distribution');          //action=16
            // $synonyms = self::parse_TableOfTaxa($html, $id, $invalid_names, 'synonyms');          //action=6
        }
    }
    private function parse_TableOfTaxa($html, $id, $invalid_names, $what)
    {
        $html = self::get_string_starting_from('table of taxa', $html);
        if(preg_match_all("/<tr(.*?)<\/tr>/ims", $html, $arr)) {
            foreach($arr[1] as $row) {
                if(stripos($row, '<font color="red">') !== false) continue; //string is found
                if(stripos($row, '<font color="00cc00">') !== false) continue; //string is found
                
                //start compute for $code
                /*
                [2] => <a href="/turb3.php?action=6&code=3190">synonyms</a>
                [3] => <a href="/turb3.php?action=13&code=3190"><img src="/icons/small/image.png" alt="card avail."></a>
                [4] => <a href="/turb3.php?action=11&code=3190&syn=2">literature</a>
                [5] => <a href="/turb3.php?action=16&code=3190&valid=0">dist'n</a>
                */
                if    (preg_match("/action=6&code=(.*?)\">synonyms<\/a>/ims", $row, $arr3))                         $code = $arr3[1];
                elseif(preg_match("/action=13&code=(.*?)\"><img src=\"\/icons\/small\/image.png\"/ims", $row, $arr3))  $code = $arr3[1];
                elseif(preg_match("/action=11&code=(.*?)&syn=2\">literature<\/a>/ims", $row, $arr3))                $code = $arr3[1];
                elseif(preg_match("/action=16&code=(.*?)&valid=0\">/ims", $row, $arr3))                             $code = $arr3[1];
                else exit("\nInvestigate cannot get id [$id]\n");
                echo "\ncode [$code]\n";
                //end compute for $code
                
                if($what == 'distribution') {
                    self::get_distribution($row, $code);
                }
                
                if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $arr2)) {
                    print_r($arr2[1]);
                }
            }
        }
    }
    private function get_distribution($str, $id) //action=16
    {
        if(stripos($str, 'action=16&') !== false) {//string is found
            echo "\nwith dist'n --> ";
            // <a href="/turb3.php?action=16&code=3190&valid=0">dist'n</a>
            if(preg_match("/action=16&code=".$id."(.*?)\"/ims", $str, $arr)) {
                $url = $this->page['action_16'].$id.$arr[1];
                echo "$url\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=16&code=13396&valid=0
                if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                    // $html = self::get_string_starting_from('table of thumbnail images', $html);
                    // if(preg_match_all("/<img src=\"(.*?)\"/ims", $html, $arr)) {
                    //     print_r($arr[1]);
                    //     return $arr[1];
                    // }
                }
            }
        }
        return false;
    }
    
    private function get_invalid_names($html) //get Red and Green highlighted taxa
    {
        $html = self::get_string_starting_from('table of taxa', $html);
        // <font color="red">graffi</font>
        // <font color="00cc00">calyx</font>
        $invalid_names = array();
        if(preg_match_all("/<font color=\"red\">(.*?)<\/font>/ims", $html, $arr))    $invalid_names = array_merge($invalid_names, $arr[1]);
        if(preg_match_all("/<font color=\"00cc00\">(.*?)<\/font>/ims", $html, $arr)) $invalid_names = array_merge($invalid_names, $arr[1]);
        print_r($invalid_names);
        return $invalid_names;
    }
    private function get_downline_images($str, $id, $exclude) //action=23
    {
        if(stripos($str, 'action=23&') !== false) {//string is found
            echo "\nwith downline image(s)\n";
            if(preg_match("/action=23&code=".$id."(.*?)\"/ims", $str, $arr)) {
                $url = $this->page['action_23'].$id.$arr[1];
                echo "\n$url\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=2&code=5654&smk=0
                if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                    $html = self::get_string_starting_from('table of images of species', $html);
                    $html = self::format_html($html);
                    if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
                        $final = array();
                        foreach($arr[1] as $tr) {
                            if(stripos($tr, '[no figure]') !== false) continue; //string is found
                            if(preg_match_all("/<td>(.*?)<\/td>/ims", $tr, $arr2)) {
                                $img_tbl_row = $arr2[1];
                                /* Array (e.g. $img_tbl_row
                                    [0] => calyx
                                    [1] => Graff, 1911
                                    [2] => <a href="/turb3.php?action=7&code=3197&ltr=a&eltr=&img=3197a.gif"><img src="media/thb3/3197a_thb.gif" width="90" alt="fig Allostoma calyx"></a>
                                ) */
                                if(in_array($img_tbl_row[0], $exclude)) continue;
                                // print_r($img_tbl_row);
                                
                                $downline = array();
                                if(preg_match("/&code=(.*?)&/ims", $img_tbl_row[2], $arr3)) $code = $arr3[1];
                                else exit("\nInvestigate 001 [$id]\n");
                                $downline[$code]['name'] = strip_tags($img_tbl_row[0]);
                                $downline[$code]['author'] = $img_tbl_row[1];
                                if(preg_match_all("/<img src=\"(.*?)\"/ims", $img_tbl_row[2], $arr4)) $downline[$code]['images'] = $arr4[1];
                                // print_r($downline);
                                if($downline) $final[] = $downline;
                            }
                        }
                        print_r($final);
                        return $final;
                    }
                }
            }
        }
        return false;
    }
    private function get_direct_images($str, $id) //action=2
    {
        if(stripos($str, 'action=2&') !== false) {//string is found
            echo "\nwith direct image(s)\n";
            //<a href="/turb3.php?action=2&code=3511&smk=1">
            if(preg_match("/action=2&code=".$id."(.*?)\"/ims", $str, $arr)) {
                $url = $this->page['action_2'].$id.$arr[1];
                echo "\n$url\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=2&code=5654&smk=0
                if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                    $html = self::get_string_starting_from('table of thumbnail images', $html);
                    if(preg_match_all("/<img src=\"(.*?)\"/ims", $html, $arr)) {
                        print_r($arr[1]);
                        return $arr[1];
                    }
                }
            }
        }
        return false;
    }
    private function get_all_ids()
    {
        $stack = array();
        $main_ids = self::get_main_ids(); //get main IDs from home page
        // print_r($main_ids); exit;
        
        // $main_ids = array(4014);
        
        foreach($main_ids as $id1) {
            $ids1 = self::get_valid_ids($id1); $stack = array_merge($stack, $ids1);
            foreach($ids1 as $id2) {
                $ids2 = self::get_valid_ids($id2); $stack = array_merge($stack, $ids2);
                foreach($ids2 as $id3) {
                    $ids3 = self::get_valid_ids($id3); $stack = array_merge($stack, $ids3);
                    foreach($ids3 as $id4) {
                        $ids4 = self::get_valid_ids($id4); $stack = array_merge($stack, $ids4);
                        foreach($ids4 as $id5) {
                            $ids5 = self::get_valid_ids($id5); $stack = array_merge($stack, $ids5);
                            foreach($ids5 as $id6) {
                                $ids6 = self::get_valid_ids($id6); $stack = array_merge($stack, $ids6);
                                foreach($ids6 as $id7) {
                                    $ids7 = self::get_valid_ids($id7); $stack = array_merge($stack, $ids7);
                                    foreach($ids7 as $id8) { 
                                        $ids8 = self::get_valid_ids($id8); $stack = array_merge($stack, $ids8);
                                        foreach($ids8 as $id9) { //exit("\nlevel 8\n");
                                            $ids9 = self::get_valid_ids($id9); $stack = array_merge($stack, $ids9);
                                            foreach($ids9 as $id10) { //exit("\nlevel 9\n");
                                                $ids10 = self::get_valid_ids($id10); $stack = array_merge($stack, $ids10);
                                                foreach($ids10 as $id11) { //exit("\nlevel 10\n");
                                                    $ids11 = self::get_valid_ids($id11); $stack = array_merge($stack, $ids11);
                                                    foreach($ids11 as $id12) { //exit("\nlevel 11\n");
                                                        $ids12 = self::get_valid_ids($id12); $stack = array_merge($stack, $ids12);
                                                        foreach($ids12 as $id13) { //exit("\nlevel 12\n");
                                                            $ids13 = self::get_valid_ids($id13); $stack = array_merge($stack, $ids13);
                                                            foreach($ids13 as $id14) { //exit("\nlevel 13\n");
                                                                $ids14 = self::get_valid_ids($id14); $stack = array_merge($stack, $ids14);
                                                                foreach($ids14 as $id15) { exit("\nlevel 14\n");
                                                                    $ids15 = self::get_valid_ids($id15); $stack = array_merge($stack, $ids15);
                                                                    foreach($ids15 as $id16) { exit("\nlevel 15\n");
                                                                        $ids16 = self::get_valid_ids($id16); $stack = array_merge($stack, $ids16);
                                                                        foreach($ids16 as $id17) { exit("\nlevel 16\n");
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
        echo "\n--------------------------\n";
        // print_r($stack);
        echo "\nNot unique: ".count($stack)."\n";
        $stack = array_unique($stack);
        echo "\nUnique: ".count($stack)."\n";
        // exit("\n-stopx-\n");
        return $stack;
    }
    private function get_valid_ids($id)
    {
        // if(in_array($id, array(0,1))) return array();
        $valid = array();
        $html = Functions::lookup_with_cache($this->page['action_1'].$id, $this->download_options);
        $html = self::get_string_starting_from('table of subtaxa', $html);
        if(preg_match_all("/action=1&code=(.*?)<\/td>/ims", $html, $arr)) {
            // print_r($arr[1]); exit;
            foreach($arr[1] as $row) {
                if(stripos($row, '<font color="red">') !== false) continue; //string is found
                if(stripos($row, '<font color="00cc00">') !== false) continue; //string is found
                // echo "\n[$row]";
                if(preg_match("/elix173(.*?)\"/ims", 'elix173'.$row, $arr2)) $valid[] = $arr2[1];
            }
        }
        // print_r($valid);
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
