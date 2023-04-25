<?php
namespace php_active_record;
/* connector: kubitzki_pagenos.php */
class Kubitzki_PageNosAPI
{
    function __construct()
    {
        $this->names_page_nos_list = array();
    }
    function start()
    {
        if(Functions::is_production()) $path = '/extra/other_files/Smithsonian/Kubitzki_et_al/'; //have not run here
        else                           $path = '/Volumes/AKiTiO4/other_files/Smithsonian/Kubitzki_et_al/';

        $folders = $path . "v*"; $i = 0;
        foreach(glob($folders) as $folder) { $i++;

            // if($i != 14) continue; //debug only - run 1x1

            $actual_folder = pathinfo($folder, PATHINFO_FILENAME);
            $filename = "$folder/$actual_folder.txt";
            echo "\n$filename --- ".filesize($filename)."\n[$actual_folder]\n";

            if(in_array($actual_folder, array('volii1993', 'voliii1998', 'volix2007', 'volv2003', 'volvi2004', 'volvii2004', 'volviii2007'))) $start_str = "References to main entries in bold-faced print, to illustrations in italics.";            
            elseif(in_array($actual_folder, array('voliv1998'))) $start_str = "-Manually Added-";
            elseif(in_array($actual_folder, array('volx2011', 'volxi2014', 'volxii2015', 'volxiv2016', 'volxv2018'))) $start_str = "References to accepted names in bold-faced print, to synonyms in upright print, to illustrations in italics.";            
            elseif(in_array($actual_folder, array('volxiii2015'))) $start_str = "References to main entries of accepted names in bold-faced print, to synonyms in upright print, to illustrations in italics.";

            $end_str = "elix";
            if(in_array($actual_folder, array('volxi2014'))) $end_str = "Index 331";
            if(in_array($actual_folder, array('volxii2015'))) $end_str = "Index 213";
            if(in_array($actual_folder, array('volxv2018'))) $end_str = "Index 567";

            $contents = file_get_contents($filename);
            $contents = str_replace("", "", $contents);
            if(preg_match("/".preg_quote($start_str, '/')."(.*?)".$end_str."/ims", $contents.$end_str, $arr)) {
                $rows = $arr[1];
                $tmp = self::parse_rows($rows, $actual_folder);

                // /* special adjustment
                if(in_array($actual_folder, array('volx2011', 'volxi2014', 'volxii2015', 'volxiii2015', 'volxiv2016', 'volxv2018'))) {
                    $tmp = self::remove_comma_from_names($tmp);
                }
                // */

                $this->names_page_nos_list = array_merge_recursive($this->names_page_nos_list, $tmp);
                echo "\nCurrent: ".count($tmp)."\n";
                echo "\nTotal: ".count($this->names_page_nos_list)."\n";
            }
            // exit("\n-stop-\n");
            // if($i == 1) break; //debug only
        }
        print_r($this->names_page_nos_list);
        if($this->names_page_nos_list['volii1993']['Acacia'] == 343) echo "\nTest passed OK"."\n";
        if($this->names_page_nos_list['volii1993']['Selleola'] == '227, 652') echo "\nTest passed OK"."\n";
        if($this->names_page_nos_list['volxv2018']['Z. absinthiifolia'] == 15) echo "\nTest passed OK"."\n";
        
    } //end loop
    private function parse_rows($rows, $actual_folder)
    {
        $rows = explode("\n", $rows);
        $rows = array_map('trim', $rows);
        $rows = array_filter($rows); //remove null arrays
        // $rows = array_unique($rows); //make unique //NEVER use here. It defeats the purpose of getting all page nos.
        $rows = array_values($rows); //reindex key
        $rows = array_map('trim', $rows);
        // print_r($rows); exit;
        /*
        Scleroblitum 262, 266
        Sclerocactus 176,177,192
        Z. begoniifolia 516, 517
        */
        foreach($rows as $row) {

            if(stripos($row, " Index") !== false) continue; //this will ignore rows like "123 Index" //string is found

            // $row = utf8_encode($row);
            $row = str_ireplace(array("\n", "\t", chr(9), chr(13), chr(10)), "", $row);
            $row = trim($row);
            if($row) { //echo "\n$row";
                if(self::startsWith($row) == 'alpha') {
                    // echo "\n$row";
                    $ret = self::separate_name_with_pagenos($row);
                    $final[$ret['name']][] = $ret['page_nos'];
                    $prev_name = $ret['name'];
                }
                elseif(self::startsWith($row) == 'numeric') {
                    // exit("\ngoes here [$row]\n[$prev_name]\n");
                    $final[$prev_name][] = $row;
                    // echo "\n$row";
                }
                else { //needed since not all start of row is alpha and numeric only. e.g. ~~173~~~
                    $final[$prev_name][] = $row;
                }

            }
        } //end loop

        // print_r($final);
        /*
        [Scleranthus] => Array
        (
            [0] => 208, 210-213, 215, 216,
            [1] => 218,229
        )
        [Scleroblitum] => Array
            (
                [0] => 262, 266
            )
        */

        /* next step: combine page nos. into a single string */
        foreach($final as $name => $nos) {
            $str = implode(", ", $nos);
            $str = trim($str);
            $str = Functions::remove_whitespace($str);
            $str = str_replace("---", "-", $str);
            $str = str_replace("--", "-", $str);
            $str = str_replace(",,", ",", $str);
            $str = trim($str);
            $str = str_ireplace(array("\n", "\t", chr(9), chr(13), chr(10)), "", $str);

            $arr = explode(",", $str);
            $arr = array_map('trim', $arr);
            $arr = array_filter($arr); //remove null arrays
            $str = implode(", ", $arr);
            $str = trim($str);
            if(substr($str, -1) == ",") $str = substr($str,0,strlen($str)-1);
            $str = trim($str);

            $str = rtrim($str, ',');
            if(strlen($name) > 1) $final2[$actual_folder][$name] = $str;

        }
        // print_r($final2);
        return $final2;
    }
    private function separate_name_with_pagenos($row)
    {
        $name = array();
        $page_nos = array();
        $parts = explode(" ", $row);
        foreach($parts as $part) {
            if(self::startsWith($part) == 'alpha') $name[] = $part;
            if(self::startsWith($part) == 'numeric') $page_nos[] = $part;
        }
        return array("name" => implode(" ", $name), "page_nos" => implode(" ", $page_nos));
    }
    private function startsWith($str) {
        $c = $str[0];
        if (ctype_alpha($c)){
            return 'alpha';
        } else if (ctype_digit($c)){
            return 'numeric';
        } else {
            return 'other';
        }
    }
    private function remove_comma_from_names($tmp)
    {   /* orig - working OK, array doesn't have volume e.g. "volxv2018"
        [Richeria,] => 85
        [Richeria sect. Podocalyx,] => 91
        foreach($tmp as $name => $page_nos) {
            $name = trim($name);
            if(substr($name, -1) == ",") $name = substr($name,0,strlen($name)-1);
            $final[$name] = $page_nos;
        }
        return $final;
        */

        /* new - array with volume e.g. "volxv2018" - working OK */
        $volumes = array_keys($tmp);
        // print_r($volumes); exit;
        foreach($volumes as $vol) {
            $tmp = $tmp[$vol];
            foreach($tmp as $name => $page_nos) {
                $name = trim($name);
                if(substr($name, -1) == ",") $name = substr($name,0,strlen($name)-1);
                $final[$vol][$name] = $page_nos;
            }
        }
        return $final;
    }

}
?>