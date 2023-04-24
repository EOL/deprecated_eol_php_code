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
        $rows = array_unique($rows); //make unique
        $rows = array_values($rows); //reindex key
        $rows = array_map('trim', $rows);
        // print_r($rows); exit;
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
                else {
                    $final[$prev_name][] = $row;

                }

            }
            /*
            Scleroblitum 262, 266
            Sclerocactus 176,177,192
            Z. begoniifolia 516, 517
            */
        }

        print_r($final);


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