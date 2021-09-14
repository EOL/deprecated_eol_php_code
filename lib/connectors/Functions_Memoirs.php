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
                if(file_exists($destination) && filesize($destination)) echo "\n".$destination." txt already converted.\n";
                else {
                    $rows = file($source);
                    $WRITE = Functions::file_open($destination, "w"); //initialize
                    foreach($rows as $row) fwrite($WRITE, $row."\n");
                    fclose($WRITE);
                }
                // break; //debug only
            }
        }
    }
    function first_char_is_capital($str)
    {
        $first_char = substr($str,0,1);
        if(ctype_upper($first_char)) return true;
        return false;
    }
    function is_sciname_in_Kubitzki($string)
    {
        /* sample genus
        1. Zippelia Blume Figs. 109 A, 110A, B
        */
        if(stripos($string, "±") !== false) return false; //string is found
        // if(stripos($string, ":") !== false) return false; //string is found //CANNOT USE THIS: e.g. "9. Compsoneura Warb. Fig. 100 C, F Bicuiba de Wilde, Beitr. BioI. Pfl. 66: 119 (1992)."
        
        /* manual --- transferred
        $string = str_replace("1. UlmusL.", "1. Ulmus L.", $string);
        $string = str_replace("Bosea L., Sp. Pl.: 225 (1753).", "6. Bosea L., Sp. Pl.: 225 (1753).", $string); //weird, number is removed in OCR
        // manual e.g. "6.Pilostyles Guillemin"
        $string = str_replace(".", ". ", $string);
        $string = Functions::remove_whitespace($string);
        */
        
        // /* e.g. "(2). Wien: Fr. Beck, pp. 44-55."
        if(substr($string,0,1) == "(") return false;
        // */

        // /* e.g. "7. Berlinianche (Harms) Vattimo" --- remove parenthesis
        $string = str_replace(array("(", ")"), "", $string);
        // */
        
        $words = explode(" ", trim($string));
        $first = @$words[0]; $second = @$words[1]; $third = @$words[2];
        if($first && $second && $third) {
            /*
            [1. Magnoliid Families] => 
            [2. Hamamelid Families] => 
            [2. Micromolecnlar Evidence. Among vascular plants,] => 
            [3. Macromolecular Evidence. In a serological study,] => 
            [5. Flower Characters. In the Centrospermae, differ-] => 
            [8. Vegetative Characters. Stipules or stipule-like ap-] => 
            [2. Teil, Bd.10. Berlin: Gebrtider Borntraeger. 364 pp.] => 
            */
            $not_in_third = array("Families", "Evidence.", "Characters.", "Group", "Tepals", "I");
            if(in_array($third, $not_in_third)) return false;

            $not_in_second = array("Tepals", "Leyden:", "The", "Fruit"); //e.g. "326. Leyden: Noordhoff."
            if(in_array($second, $not_in_second)) return false;
            
            if(is_numeric($first) && $this->first_char_is_capital($second) && $this->first_char_is_capital($third)
                                  && !in_array($second, $this->ranks)
                                  && strlen($first) <= 4 //120.
                                  && substr($first,-1) == "." // exclude e.g. "011 UrI Lui Frl GI"
                                  && strlen($second) >= 2 && strlen($third) >= 1 // e.g. "2. Rafflesia R Br."
                                  && substr($second,1,1) != "," // exclude e.g. "40 A, Oxford: Clarendon Press, pp.105-128."
                                  && substr($second,-1) != "."  // exclude e.g. "3. Annuals. Carpels connate to various degrees"
                                  && substr($second,-1) != ","  // exclude e.g. "2. Teil, Bd.10. Berlin: Gebrtider Borntraeger. 364 pp."
                                  ) return true;
            return false;
        }
        elseif(count($words) == 1) { //2nd Start pattern --- e.g. "Berberidaceae"
            if(substr($string, -3) == "eae" && $this->first_char_is_capital($string) && substr($string,0,1) != "?") {
                return true;
            }
            else return false;
        }
        else return false;
    }
    // "1. The Annona group has to be united with the"
    function considered_allcaps_tobe_removed($row) //designed for "Kubitzki" resource only
    {
        if($row == "SUBDIVISION AND RELATIONSHIPS WITHIN THE FAMILY.") return false;
        /* let us wait for Jen on this one:
        if($this->first_part_of_string("INFORMAL GROUPS WITHIN THE ", $row)) return false; //"INFORMAL GROUPS WITHIN THE ANNONACEAE"
        */
        
        /*
        U.KUHN
        with substantial additions by V. BITTRICH,
        R. CAROLIN, H.FREITAG, I. C. HEDGE,
        P. UOTILA and P. G. WILSON
        Wu CHENG-YIH and K.KUBITZKI
        Wu CHENG-YIH AND K.KUBITZKI
        Wu CHENG-YIH and K.KUBITZKI
        */
        $orig = $row; //for debug only
        $tobe_removed = array(".", ",", 'with', 'substantial', 'additions', 'by', 'and', "-", "Wu ");
        $tobe_removed[] = " "; //should be the last char --- IMPORTANT
        $tmp = $row;
        $tmp = trim(str_ireplace("K.KuBITZKI", "K.KUBITZKI", $tmp));
        foreach($tobe_removed as $r) $tmp = str_ireplace($r, "", $tmp);
        $tmp = Functions::remove_whitespace(trim($tmp));
        /* good debug
        if(stripos($orig, "CHENG-YIH") !== false) { //string is found
            exit("\n-----\n[$tmp]\n-----\n");
        }*/
        if($tmp && ctype_upper($tmp)) return true;
        return false;
    }
    function considered_OCR_garbage_tobe_removed($row)
    {
        $row = str_replace('"', "_", $row); //just for easy quoting...
        $garbage = array("'.", "fi", "~ ,", "'Ii _ •", "..", "K '_", "\)", "'··'-", "_ _ ' 't... ,", "• f'", ";. '~..", ".~ :.", "~ . i", "'_, x~", "( ...•", "'~.;:_ .,", ". t' -:.", "''''.' .,.. _ , ·v.,.. .", "_, ': .", "(~ -", 
            ",! ,~ - ~rh", ". . ,!~) c_\~ 0", "i\ 13 qy");
        foreach($garbage as $r) {
            if($row == $r) return true;
        }
        return false;
    }
    function first_part_of_string($needle, $row)
    {
        $len = strlen($needle);
        if(substr($row,0,$len) == $needle) return true;
        else return false;
    }
}
?>