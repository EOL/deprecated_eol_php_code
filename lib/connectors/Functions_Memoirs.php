<?php
namespace php_active_record;
/* */
class Functions_Memoirs
{
    function __construct() {}
    function is_DoubtfulSpecies_stop_pattern($row)
    {   /*Array(
            [0] => <br>I
            [1] => )'.
            [2] => i
            [3] => lill'l'l,
            [4] => SPECIES<br>
        )*/
        $words = explode(" ", strtolower($row)); //print_r($words);
        if(count($words) <= 5 && in_array("species", $words) && strlen($row) <= 30) {
            $matches = array_keys($words, 'species'); //print_r($matches);
            $i = -1;
            foreach($words as $word) { $i++;
                if($i <= $matches[0]) { //echo "\n[$word]\n";
                    if(stripos($word, "l") !== false) return true; //string is found
                    if(stripos($word, "doubt") !== false) return true; //string is found
                }
            }
        }
    }
    function is_ExcludedSpecies_stop_pattern($row) // "ExcLLTDED Species."
    {
        $words = explode(" ", $row);
        $words = array_map('trim', $words);
        if(count($words) == 2) {
            $first = $words[0];
            $second = $words[1];
            if(strtolower(substr($second,0,7)) == "species") {
                $first_3 = strtolower(substr($first,0,3));
                $last_3 = strtolower(substr($first, -3));
                if($first_3 == "exc" && $last_3 == "ded") return true;
            }
        }
    }
    function first_word_is_allcaps($row)
    {
        $words = explode(" ", $row);
        if(ctype_upper($words[0])) return true;
        return false;
    }
    function possible_Number_then_AllCapsTaxon_Stop_pattern($rows2, $ctr) //42. CAILLIEA Guill. & Perr. Fl. Seneg. 239. 1833.
    {
        $rows2 = array_map('trim', $rows2);
        if(!$rows2[0] && $rows2[1] && !$rows2[2]) {
            // /* Includes cases like these: "42. CAILLIEA Guill. & Perr. Fl. Seneg. 239. 1833." --- must be a Stop pattern
            $arr = $rows2;
            if($this->first_word_is_numeric($arr[1])) { // print_r($arr); echo("\n[$arr[1]]elix1");
                $arr[1] = $this->remove_first_word_if_it_has_number($arr[1]); // echo("\n[$arr[1]]elix1");
                /* copied template
                if(self::one_word_and_higher_taxon($arr[1])) {
                    $this->Distribution_Stop_pattern[$ctr-2] = ''; // minus 2 bec. the actual row is to be Stopped
                }
                */
                $words = explode(" ", $arr[1]);
                $possible_name = $words[0];
                $possible_name = str_replace(array("."), "", $possible_name);
                if($this->first_word_is_allcaps($arr[1])) {
                    $this->Distribution_Stop_pattern[$ctr-1] = ''; // e.g. "2. LINDMANIA Mez, in DC. Monog. Phan. 9: 535. 1896."
                    // echo "\n-----\n"; print_r($rows2); echo "\n-----\n";
                }
                elseif(strtolower(substr($possible_name, -2)) == "ae") { //18 Pubentissimae.
                    $this->Distribution_Stop_pattern[$ctr-1] = '';
                }
                /* DON'T CHECK NAME VIA GNRD in this path. Check only GNRD if it came from get_main_scinames()
                else {
                    if(ctype_upper(substr($possible_name,0,1))) { //1st letter is all caps
                        if($this->run_GNRD_get_sciname_inXML($possible_name)) {
                            $this->Distribution_Stop_pattern[$ctr-1] = '';
                        }
                    }
                }
                */
            }
            // */
        }
        array_shift($rows2); //remove 1st element, once it reaches 3 rows.
        return $rows2;
    }
    function first_word_is_numeric($str)
    {
        $words = explode(" ", trim($str));
        if(is_numeric($words[0])) return true;
        return false;
    }
    function possible_RomanNumeral_then_AllCapsTaxon_Stop_pattern($rows2, $ctr)
    {
        $rows2 = array_map('trim', $rows2);
        if(!$rows2[0] && $rows2[1] && !$rows2[2]) {
            /* Includes cases like these: --- must be a Stop pattern
            II. ACACIEAE.
            in. MIMOSEAE.
            VlU. CAESALPINLEAE. */
            $arr = $rows2;
            
            // if(stripos($arr[1], $this->in_question) !== false) { //string is found
            //     print_r($rows2); exit("\n-end elix 01-\n");
            // }
            
            if($this->first_word_is_RomanNumeral($arr[1])) { // print_r($arr); echo("\n[$arr[1]]elix1");
                // if(stripos($arr[1], $this->in_question) !== false) { //string is found
                //     print_r($rows2); exit("\n-end elix 02-\n");
                // }
                $words = explode(" ", trim($arr[1]));
                $words = array_map('trim', $words);
                if($second = @$words[1]) {
                    $second = str_replace(array("."), "", $second);
                    // if(stripos($arr[1], $this->in_question) !== false) { //string is found
                    //     print_r($rows2); exit("\n[$second]\n-end elix 03-\n");
                    // }
                    if(ctype_upper($second)) {
                        // echo "\n[$ctr][$ctr]\n";
                        $this->Distribution_Stop_pattern[$ctr-1] = '';
                        // if(stripos($rows2[1], $this->in_question) !== false) { //string is found
                        //     print_r($rows2); exit("\n[$second]\n-end elix 04-\n");
                        // }
                    }
                    elseif(strtolower(substr($second, -2)) == "ae") {
                        $this->Distribution_Stop_pattern[$ctr-1] = '';
                    }
                    else {
                        if(ctype_upper(substr($second,0,1))) { //1st letter is all caps
                            if($this->run_GNRD_get_sciname_inXML($second)) {
                                $this->Distribution_Stop_pattern[$ctr-1] = '';
                            }
                            /* can be commented coz you just need to motivate GNRD API to work all the time AND not accept that you'll need to retry
                            several times to get their output
                            elseif($this->is_sciname_using_GNRD($second)) {
                                $this->Distribution_Stop_pattern[$ctr-1] = '';
                            }
                            */
                        }
                    }
                }
            }
        }
        array_shift($rows2); //remove 1st element, once it reaches 3 rows.
        return $rows2;
    }
    function first_word_is_RomanNumeral($str)
    {
        $words = explode(" ", trim($str));
        if($first = $words[0]) {
            if(substr($first, -1) != ".") return false;
            $first = str_replace(array(".", ","), "", $first);
            if($this->str_is_RomanNumeral($first)) return true;
        }
        return false;
    }
    function str_is_RomanNumeral($str)
    {
        $str = (string) str_replace(array("i", "n", "l", "U"), "V", $str);
        $roman_numerals = array("I", "V", "X", "L", "C", "D", "M");
        for($x = 0; $x <= strlen($str)-1; $x++) {
            if(!in_array($str[$x], $roman_numerals)) return false;
        }
        return true;
    }
    function number_number_period($row) //"1 1 . Cracca leucosericea Rydberg, sp. nov."
    {   $orig_row = $row;
        $words = explode(" ", trim($row));
        $words = array_map('trim', $words);
        if($first = @$words[0]) {} else return $row;
        if($second = @$words[1]) {} else return $row;
        if($third = @$words[2]) {} else return $row;
        if(is_numeric($first) && is_numeric($second) && $third == ".") {
            array_shift($words); //remove 1st element
            array_shift($words); //remove 2nd element
            $words[0] = "3.";
            $row = implode(" ", $words);
            echo("\norig_row: [$orig_row] -> new row: [$row]\n"); //good debug
        }
        return $row;
    }
    function download_Kubitzki_pdfs()
    {
        if(Functions::is_production()) $path = '/extra/other_files/Smithsonian/Kubitzki_et_al/';
        else                           $path = '/Volumes/AKiTiO4/other_files/Smithsonian/Kubitzki_et_al/';
        if(!is_dir($path)) mkdir($path);
        // $url = "https://opendata.eol.org/api/3/action/package_show?id=kubitzki-source-files"; //private in OpenData
        $url = "https://editors.eol.org//other_files/Smithsonian/Kubitzki_et_al/OpenData/Kubitzki_PDFs.json";
        if($json = Functions::lookup_with_cache($url, array("expire_seconds" => false))) {
            $obj = json_decode($json);
            $resources = $obj->result->resources; // print_r($resources);
            foreach($resources as $r) {
                print_r(pathinfo($r->url)); //exit;
                /*Array(
                    [dirname] => https://opendata.eol.org/dataset/91d4f4d2-3ce1-400a-ab4a-fbde1ba3218a/resource/8d806e20-e421-4c88-a7ea-d8e18c11bde3/download
                    [basename] => volii1993.pdf
                    [extension] => pdf
                    [filename] => volii1993
                )*/
                $pdf_id = pathinfo($r->url, PATHINFO_FILENAME);
                $filename = pathinfo($r->url, PATHINFO_BASENAME);
                $source = pathinfo($r->url, PATHINFO_DIRNAME);
                $current_path = $path.$pdf_id."/";
                if(!is_dir($current_path)) mkdir($current_path);
                $destination = $current_path.$filename;
                if(file_exists($destination) && filesize($destination)) echo "\n".$destination." already downloaded.\n";
                else {
                    // $cmd = "wget -nc --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1"; --- no overwrite
                    $cmd = "wget --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1";
                    echo "\nDownloading...[$cmd]\n";
                    $output = shell_exec($cmd); //sleep(10);
                    if(file_exists($destination) && filesize($destination)) echo "\n".$destination." downloaded successfully.\n";
                    else exit("\nERROR: Cannot download [$source]\n");
                }
            
                /* start convert to txt file */
                // /Volumes/AKiTiO4/other_files/Smithsonian/Kubitzki_et_al/volxiv2016/volxiv2016.pdf
                $source = $destination;
                $destination = str_replace(".pdf", "_raw.txt", $destination);
                if(file_exists($destination) && filesize($destination)) echo "\n".$destination." already converted.\n";
                else {
                    $cmd = "pdftotext -raw $source $destination"; $cmd .= " 2>&1";
                    echo "\nPDF to TXT...[$cmd]\n";
                    $output = shell_exec($cmd);
                    if(file_exists($destination) && filesize($destination)) echo "\n".$destination." converted successfully.\n";
                    else exit("\nERROR: Cannot convert [$source]\n");
                }
                /* start to add a blank line between all rows */
                $source = $destination;
                $destination = str_replace("_raw.txt", ".txt", $destination);
                $rows = file($source);
                $WRITE = Functions::file_open($destination, "w"); //initialize
                foreach($rows as $row) fwrite($WRITE, $row."\n");
                fclose($WRITE);
                // break; //debug only
            }
        }
    }
}
?>