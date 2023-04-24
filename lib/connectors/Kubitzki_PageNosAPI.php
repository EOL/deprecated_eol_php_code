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

                echo "\n".$arr[1]."\n"; exit;
            }
            exit("\n-stop-\n");

        }



    }

}
?>