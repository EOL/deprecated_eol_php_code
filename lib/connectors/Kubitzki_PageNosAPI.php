<?php
namespace php_active_record;
/* connector: kubitzki_pagenos.php */
class Kubitzki_PageNosAPI
{
    function __construct()
    {
    }
    function start()
    {
        if(Functions::is_production()) $path = '/extra/other_files/Smithsonian/Kubitzki_et_al/'; //have not run here
        else                           $path = '/Volumes/AKiTiO4/other_files/Smithsonian/Kubitzki_et_al/';

        $folders = $path . "v*";
        foreach(glob($folders) as $folder) {
            $actual_folder = pathinfo($folder, PATHINFO_FILENAME);
            $filename = "$folder/$actual_folder.txt";
            echo "\n$filename --- ".filesize($filename)."\n[$actual_folder]\n";

            if(in_array($actual_folder, array('volii1993'))) $start_str = "References to main entries in bold-faced print, to illustrations in italics.";

            $contents = file_get_contents($filename);
            if(preg_match("/".preg_quote($start_str, '/')."(.*?)elix/ims", $contents."elix", $arr)) {
                $rows = $arr[1];
                self::parse_rows($rows);
            }
            exit("\n-stop-\n");
        }

    } //end loop
    private function parse_rows($rows)
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
            $final2[$name] = $str;
        }
        print_r($final2);

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

}
?>