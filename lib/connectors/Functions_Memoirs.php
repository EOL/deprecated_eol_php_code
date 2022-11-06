<?php
namespace php_active_record;
/* */
class Functions_Memoirs
{
    function __construct($json_path = false, $service = array(), $download_options = array())
    {
        if($json_path) $this->json_path = $json_path;
        if($service) $this->service = $service;
        if($download_options) $this->download_options = $download_options;
        // print_r($this->service);
        // print_r($this->download_options);
        // exit("\n$this->json_path\n");
    }
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
        if(ctype_upper($words[0])) return true; //use this: return $words[0]; --- if u want to debug
        return false;
    }
    function first_word_more_than_one_char($row)
    {
        $words = explode(" ", $row);
        if(strlen($words[0]) > 2) return true;
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
                if($this->first_word_is_allcaps($arr[1]) && $this->first_word_more_than_one_char($arr[1])) {
                    $this->Distribution_Stop_pattern[$ctr-1] = ''; // e.g. "2. LINDMANIA Mez, in DC. Monog. Phan. 9: 535. 1896."
                    $this->track_Distribution_Stop_pattern[$ctr-1] = 'a1';
                    // echo "\n-----\n"; print_r($rows2); echo "\n-----\n";
                }
                elseif(strtolower(substr($possible_name, -2)) == "ae") { //18 Pubentissimae.
                    $this->Distribution_Stop_pattern[$ctr-1] = '';
                    $this->track_Distribution_Stop_pattern[$ctr-1] = 'a2';
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
                    if(ctype_upper($second) && strlen($second) > 2) { //e.g. "U. S. Nat. Herb. 16: 192. 1913." 91345.txt BHL
                        // echo "\n[$ctr][$ctr]\n";
                        $this->Distribution_Stop_pattern[$ctr-1] = '';
                        $this->track_Distribution_Stop_pattern[$ctr-1] = 'a3';
                        // if(stripos($rows2[1], $this->in_question) !== false) { //string is found
                        //     print_r($rows2); exit("\n[$second]\n-end elix 04-\n");
                        // }
                    }
                    elseif(strtolower(substr($second, -2)) == "ae") {
                        $this->Distribution_Stop_pattern[$ctr-1] = '';
                        $this->track_Distribution_Stop_pattern[$ctr-1] = 'a4';
                    }
                    else {
                        if(ctype_upper(substr($second,0,1))) { //1st letter is all caps
                            if($this->run_GNRD_get_sciname_inXML($second)) {
                                $this->Distribution_Stop_pattern[$ctr-1] = '';
                                $this->track_Distribution_Stop_pattern[$ctr-1] = 'a5';
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
            if(!in_array(strtoupper($str[$x]), $roman_numerals)) return false;
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
    {   /* sample genus
        1. Zippelia Blume Figs. 109 A, 110A, B
        */
        $string = trim($string); //new Sep 15
        
        /* possible exclude row --- return false --- if these strings exist in the $row
        " from" " taxa"
        */
        
        // if(stripos($string, $this->in_question) !== false) exit("\n[$string][]\nelix a0\n"); //string is found
        
        if(stripos($string, "±") !== false) return false; //string is found
        // if(stripos($string, ":") !== false) return false; //string is found //CANNOT USE THIS: e.g. "9. Compsoneura Warb. Fig. 100 C, F Bicuiba de Wilde, Beitr. BioI. Pfl. 66: 119 (1992)."
        
        // /* e.g. "(2). Wien: Fr. Beck, pp. 44-55."
        if(substr($string,0,1) == "(") return false;
        // */

        $words = explode(" ", $string);
        $first = @$words[0]; $second = @$words[1]; $third = @$words[2];
        $forth = @$words[3]; //e.g. "67. Duthieastrum de Vos" --- 4th word is "Vos"

        // /* e.g. "7. Berlinianche (Harms) Vattimo" --- remove parenthesis
        $third = str_replace(array("(", ")"), "", $third);
        // */

        if($first && $second && $third) {
            /*
            [1. Magnoliid Families] => 
            [2. Micromolecnlar Evidence. Among vascular plants,] => 
            [5. Flower Characters. In the Centrospermae, differ-] => 
            [2. Teil, Bd.10. Berlin: Gebrtider Borntraeger. 364 pp.] => 
            */
            $not_in_third = array("Families", "Evidence.", "Characters.", "Group", "Tepals", "I");
            if(in_array($third, $not_in_third)) return false;

            $not_in_second = array("Tepals", "The", "Fruit", "Royal", "Leaf", "Special", "Ancestral", "Major", "Breeding", "Scape", 
                "Zoophilic", "Leaves", "In", "Novel", "New", "South", "Northern", "Plants", "Berlin", "Old", "Some", "Many", "Kew",
                "Not", "Late", "Eastern"); //e.g. "3. Zoophilic Pollination" OR "10. Leaves V-shaped in cross-section 8. Kniphofia"
            if(in_array($second, $not_in_second)) return false;

            // /*
            if($this->first_part_of_string("Seed", $second)) return false;
            // e.g. "4. Seedling Organization"
            // e.g. "35. Seeds D-shaped, plants American 57. Chlidanthus"
            // */
            
            /* sample genus
                1. Zippelia Blume Figs. 109 A, 110A, B
            reported by Jen:
            voliii1998:
            l3. Olsynium Raf. <--- misspelling, L for 1. Rats, I was hoping those wouldn't occur here       DONE
            50. Tritoniopsis 1. Bolus           DONE
            53. Gladiolus 1. Figs. 90C, 92      DONE
            67. Duthieastrum de Vos             DONE
            7. /ohnsonia R. Br. Fig.95A-D Lanariaceae. <--- extra weird. We needn't bend over backwards,    DONE
                                                            since I'll be processing this resource manually downstream anyway
            */
            $second_word_first_char = substr($second,0,1);
            $second_word_last_char = substr($second, -1);
            $third_word_last_char = substr($third, -1);
            
            // if(stripos($string, $this->in_question) !== false) exit("\n[$string][$first]\nelix a1\n"); //string is found
            
            if(self::is_valid_numeric($first) && $this->first_char_is_capital($second) 
                  && ( $this->first_char_is_capital($third) || ($third == "de" && $this->first_char_is_capital($forth)) ) // "67. Duthieastrum de Vos"
                  && !in_array($second, $this->ranks)
                  && strlen($first) <= 5 //120. | "1047. Aaronsohnia Warb. & Eig"
                  && substr($first,-1) == "." // exclude e.g. "011 UrI Lui Frl GI"
                  && strlen($second) >= 2 && strlen($third) >= 1 // e.g. "2. Rafflesia R Br."

                  && !in_array($second_word_last_char, array(".", ",", ":"))
                  // exclude e.g. "3. Annuals. Carpels connate to various degrees"
                  // exclude e.g. "2. Teil, Bd.10. Berlin: Gebrtider Borntraeger. 364 pp."
                  // exclude e.g. "5. Taipei: Epoch Publishing Co. , pp. 859-1137."

                  && !in_array($third_word_last_char, array(":"))
                  // exclude e.g. "2. Monocotyledonous Organization:"

                  && !in_array($second_word_first_char, array("(")) // exclude "405. (In Chinese with Engl. summ.)"
                  // && $this->is_sciname_in_GNRD($second)
                  ) return true;
            elseif($sciname = self::get_name_from_intermediate_rank_pattern($string)) return $sciname;
            else return false;
        }
        elseif(count($words) == 1) { //2nd Start pattern --- e.g. "Berberidaceae"
            if( //substr($string, -3) == "eae" 
                in_array(substr($string, -3), array("eae", "ae1"))
                && $this->first_char_is_capital($string) && substr($string,0,1) != "?") {
                return true;
            }
            else return false;
        }
        else return false;
    }
    private function is_valid_numeric($str)
    {
        if(is_numeric($str)) return true;
        // 2nd option
        $chars = $this->chars_that_can_be_nos_but_became_letters_due2OCR;
        $str = str_ireplace($chars, "3", $str); //l3. Olsynium Raf. <--- misspelling, L for 1. Rats, I was hoping those wouldn't occur here
        if(is_numeric($str)) return true;
    }
    function is_sciname_in_GNRD($name)
    {
        if($obj = $this->run_GNRD($name)) {
            if(count(@$obj->names) > 0) return true;
        }
        else {
            if($name = $this->run_GNRD_get_sciname_inXML($name)) return true;
        }
    }
    function get_name_from_intermediate_rank_pattern($string)
    {   /* 
        2. Tribe Aristolochieae
        I. Subfamily Amaranthoideae
        II. Subfam. Gomphrenoideae
        2a. Subtribe Isotrematinae --- ? wait on Jen's decision
        V. Subfam. Mollinedioideae Thorne (1974)
        Reported by Jen: fixed
        volii1993:
        v. Subfam. Ruschioideae Schwantes in Ihlenf.,   DONE
        I. Subfam. Mitrastemoidae                       DONE
        voliii1998:
        v. Subfam. Hyacinthoideae Link (1829).          DONE
        III. Subfam. lridioideae Pax (1882).            DONE
        3. Tribe lxieae Dumort (1822).                  DONE
        VI.5. Subtribe Centratherinae H. Rob.,          DONE
        */
        $words = explode(" ", $string);
        $first = @$words[0]; $second = @$words[1]; $third = @$words[2];
        if($first && $second && $third) {
            // if(stripos($string, $this->in_question) !== false) exit("\n[$string][$first]\ncha 00\n"); //string is found
            // [VI5. Subtribe Centratherinae H. Rob. ,][VI5.]
            if(is_numeric($first) || self::first_word_is_RomanNumeral($string) || self::is_hybrid_number($first, $string)) {
                if(in_array($second, $this->Kubitzki_intermediate_ranks)) {
                    if($this->first_char_is_capital($third)) {
                        /* good debug
                        if(stripos($string, $this->in_question) !== false) exit("\n[$string][$first]\ncha 01\n"); //string is found
                        if(in_array(substr($third, -3), array("eae", "nae", "dae"))) {
                            if(stripos($string, $this->in_question) !== false) exit("\n[$string][$first][$third]\ncha 02\n"); //string is found
                        }
                        */
                        if(in_array(substr($third, -3), array("eae", "nae", "dae"))) return $third; //sciname ends with "eae" or "nae"
                    }
                }
            }
        }
        return false;
    }
    function remove_first_word_if_it_is_RomanNumeral($string)
    {
        if(self::first_word_is_RomanNumeral($string)) {
            $words = explode(" ", $string);
            array_shift($words);
            $string = implode(" ", $words);
        }
        return $string;
    }
    function is_hybrid_number($string, $row = "") //e.g. "2a"
    {   // if(stripos($row, $this->in_question) !== false) exit("\n[$row][$string]\nelix 01\n"); //string is found
        if($this->has_letters($string) && $this->has_numbers($string)) return true; // e.g. "7a."
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
    function numbered_Key_to_phrase($row)
    {   /*
        1. Key to the Major Systematic and
        2. Key to Genera of Coronantheroid
        13. Key to Didymocarpoid Gesneriaceae of
        */
        $words = explode(" ", trim($row));
        if(is_numeric($words[0]) && @$words[1] == "Key" && @$words[2] == "to" && @$words[3]) return true;
    }
    function adjust_hybrid_bullet_pt($row)
    {   // e.g. "VI.5. Subtribe Centratherinae H. Rob.,"
        $words = explode(" ", trim($row));
        if($first = @$words[0]) {
            if(self::is_hybrid_number($first) && substr($first, -1) == ".") {
                $first = str_replace(".", "", $first);
                $first .= ".";
                $words[0] = $first;
                $row = implode(" ", $words);
            }
        }
        return $row;
    }
    function adjust_family_name_special_case($row)
    {   // /* e.g. "Aextoxicaceae1" to: "Aextoxicaceae"
        $words = explode(" ", trim($row));
        $first = @$words[0];
        if(count($words) == 1) {
            if(substr($first, -4) == "eae1") return substr($first,0,strlen($first)-1);
        }
        return $row;
    }
    function change_U_to_ll_caused_by_OCR($sciname_line) //e.g. Bruchia longicoUis [improvement series]
    {
        // if(stripos($sciname_line, "mcauisteri") !== false) echo("\nfound: [$sciname_line]\n"); //string is found --- debug only
        $orig = $sciname_line;
        $words = explode(" ", $sciname_line);
        // debug("\n----------\norig: [$sciname_line]\n");
        if(count($words) >= 2) {
            $second = $words[1];
            $first_char = substr($second,0,1);
            $second = substr($second,1,strlen($second)-1); //remove first char
            // debug("\nnew: [$second]\nfirst char: [$first_char]\n");
            $pos = strpos($second, 'U');
            // debug("\npos: [$pos]\n");
            
            if($pos === false) {} //not found
            else { //found OK
                //loop each char in $second, and check for upper and lower cases
                $lower_case = 0; $upper_case = 0;
                for($i = 0; $i <= strlen($second)-1; $i++) {
                    $char = $second[$i];
                    if(in_array($char, array(".", ",", ";", ":", "-", "(", ")", "*", "#", "!"))) continue;
                    // echo " $char"; //good debug
                    if(ctype_lower($char)) @$lower_case++;
                    else                   @$upper_case++;
                }
                // debug("\nlower: [$lower_case]\nupper: [$upper_case]");
                if($lower_case >= 3 && ($upper_case == 1 || $upper_case == 2)) { //replace "U" to "ll" --- "Riccia McAUisteri" to "Riccia McAllisteri" -> $upper_case is 2
                                                                                               // orig --- "Bruchia longicoUis" -> $upper_case is 1
                     $second = str_replace("U", "ll", $second);
                     $second = $first_char.$second;
                     // debug("\nnew second: [$second]\n");
                     $words[1] = $second;
                }
                //end loop
            }
        }
        $sciname_line = implode(" ", $words);
        $sciname_line = str_replace("lll", "lli", $sciname_line);
        if($orig != $sciname_line) echo "\nfinal: [$sciname_line]\n----------\n";
        // if(stripos($orig, "mcauisteri") !== false) exit("\nfound: [$sciname_line]\n"); //string is found --- debug only
        return $sciname_line;
    }
    function change_l_to_i_if_applicable($sci) //[improvement series]
    {   /* e.g.
        $sci = "gracllens";
        // $sci = "hlrsuta";
        // $sci = "inclsa";
        */
        for($i = 0; $i <= strlen($sci)-1; $i++) {
            $char = $sci[$i];
            if($char == "l") {
                $char_before = @$sci[$i-1];
                $char_after = @$sci[$i+1];
                // echo "\nchar_before: [$char_before]\n";
                // echo "\nchar_after: [$char_after]\n";
                if($char_before && $char_after) {
                    if(self::is_a_consonant_but_not_y($char_before) && self::is_a_consonant($char_after)) $sci[$i] = "i";
                }
            }
        }
        return $sci;
    }
    function is_a_vowel($letter)
    {
        $vowels = array("a", "e", "i", "o", "u");
        if(in_array(strtolower($letter), $vowels)) return true;
        return false;
    }
    function is_a_consonant($letter)
    {
        $consonant = array("b", "c", "d", "f", "g", "h", "k", "j", "l", "m", "n", "p", "q", "r", "s", "t", "v", "w", "x", "y", "z");
        if(in_array(strtolower($letter), $consonant)) return true;
        return false;
    }
    function is_a_consonant_but_not_y($letter)
    {
        $consonant = array("b", "c", "d", "f", "g", "h", "k", "j", "l", "m", "n", "p", "q", "r", "s", "t", "v", "w", "x", "z");
        if(in_array(strtolower($letter), $consonant)) return true;
        return false;
    }
    function run_gnverifier($string, $expire_seconds = false)
    {
        $string = self::format_string_4gnparser($string);
        $url = $this->service['GNVerifier'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($expire_seconds) $options['expire_seconds'] = $expire_seconds;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json); // print_r($obj); //exit;
            return $obj;
        }
        /*Array(
            [0] => stdClass Object(
                    [inputId] => 7f4b292e-e764-582b-bb64-989da231e28f
                    [input] => Bulbochaete cimarronea Taft , Bull. Torrey Club 62 : 282. 1935
                    [matchType] => Exact
                    [bestResult] => stdClass Object(
                            ...
                            [matchedName] => Bulbochaete cimarronea
                            [matchedCardinality] => 2
                            [matchedCanonicalSimple] => Bulbochaete cimarronea
                            [matchedCanonicalFull] => Bulbochaete cimarronea
        */
    }
    private function format_string_4gnparser($str)
    {   //Append a vertical line separated array of strings to your domain url. 
        //Make sure that '&' in the names are escaped as '%26', and spaces are escaped as '+'. 
        // %26 - &          // %2C - ,
        // %28 - (          // %29 - )
        // %3B - ;          // + - space
        // $str = str_replace(",", "%2C", $str);
        // $str = str_replace("(", "%28", $str);
        // $str = str_replace(")", "%29", $str);
        // $str = str_replace(";", "%3B", $str);
        // option 1
        $str = str_replace(" ", "+", $str);
        $str = str_replace("&", "%26", $str);
        return $str;
    }
    /*================== START gnfinder =====================*/
    function get_names_from_gnfinder($desc, $params = array()) //old name is "retrieve_partial()" //1st param $id, 2nd param $desc, 3rd param $loop - copied template
    {
        if(isset($params['refresh'])) $refresh = $params['refresh'];
        else                          $refresh = false; //default
        
        /* not implemented
        if($val = @$params['coverage']) $this->coverage = $val; // can be 'all' or 'binomial'
        else                            $this->coverage = 'binomial'; //default. 'binomial' means bi or trinomial or more...
        */
        
        $arr = self::gen_array_input(trim($desc)); //for id use
        $id = md5(json_encode($arr));
        
        // /* if you want to refresh call, meaning expire cache and save new cache ----------
        if($refresh) { //this block is exactly copied from below
            return self::do_run_partial($desc, $id);
        }
        // ---------- */
        
        if($arr = self::retrieve_json($id, 'partial', $desc)) {
            // echo "\n[111]\n"; //retrieved, already created.
            return self::select_envo($arr, $desc);
            /*e.g. return value: Array(
                [0] => Thalictroides
                [1] => Lates niloticus
                [2] => Calopogon
                [3] => Cymbidium pulchellum
                [4] => Conostylis americana
            )*/
        }
        else {
            return self::do_run_partial($desc, $id);
        }
    }
    private function do_run_partial($desc, $id)
    {
        if($json = self::run_partial($desc)) {
            self::save_json($id, $json, 'partial');
            /* now start access newly created. */
            if($arr = self::retrieve_json($id, 'partial', $desc)) {
                // echo "\n[222][$desc]\n"; //newly created
                return self::select_envo($arr, $desc);
            }
            else {
                exit("\nShould not go here, since record should be created now.\n[$id]\n[$desc]\n[$json]\n");
            }
        }
        else {
            exit("\n================\n -- nothing to save B...\n[$id]\n[$desc]\n================\n"); //doesn't go here. Previously exit()
        }
    }
    private function select_envo($arr, $desc)
    {   //print_r($arr['names']); exit;
        //print_r($arr); exit;
        
        $totalWords = @$arr['metadata']['totalWords']; //exit("\n$totalWords\n"); --- seems not used anyway
        
        $final = array();
        if(@$arr['names']) { $i = 0;
            foreach($arr['names'] as $n) { $i++;
                
                // if($totalWords <= 3) { //just get 1 record
                //     if($i > 1) break;
                // }
                
                /* my first try
                // 1st try - at least 2 words
                if($val = @$n['verification']['bestResult']['matchedCanonicalSimple']) {
                    if(self::more_than_one_word($val)) {
                        $final[] = $val; continue;
                    }
                }
                if($val = @$n['verification']['preferredResults'][0]['matchedCanonicalSimple']) {
                    if(self::more_than_one_word($val)) {
                        $final[] = $val; continue;
                    }
                }
                if($val = @$n['name']) {
                    if(self::more_than_one_word($val)) {
                        $final[] = $val; continue;
                    }
                }
                // 2nd try any string
                if($val = @$n['verification']['bestResult']['matchedCanonicalSimple']) {$final[] = $val; continue;}
                if($val = @$n['verification']['preferredResults'][0]['matchedCanonicalSimple']) {$final[] = $val; continue;}
                if($val = @$n['name']) {$final[] = $val; continue;}
                */

                /* this block is equal to what is below it:
                if($val = @$n['name']) {
                    if(self::more_than_one_word($val)) {
                        $final[] = $val; continue;
                    }
                }
                if($val = @$n['name']) {$final[] = $val; continue;}
                */
                if($val = @$n['name']) {$final[] = $val; continue;}
                
                
            } //end loop
        }
        if($final) return $final;
        /*Array(
            [names] => Array(
                    [0] => Array(
                            [cardinality] => 1
                            [verbatim] => Thalictroides,
                            [name] => Thalictroides
                            [oddsLog10] => 5.542387538236
                            [start] => 0
                            [end] => 14
                            [annotationNomenType] => NO_ANNOT
                        )
                    [1] => Array(
                            [cardinality] => 1
                            [verbatim] => Calopogon,
                            [name] => Calopogon
                            [oddsLog10] => 3.9172974723412
                            [start] => 52
                            [end] => 62
                            [annotationNomenType] => NO_ANNOT
                            [verification] => Array(
                                    [inputId] => 338886bc-417a-5b67-91d9-e2d609f38501
                                    [input] => Calopogon
                                    [matchType] => Exact
                                    [bestResult] => Array(
                                            [dataSourceId] => 1
                                            [dataSourceTitleShort] => Catalogue of Life
                                            [curation] => Curated
                                            [recordId] => 3FWL
                                            [entryDate] => 2021-06-21
                                            [matchedName] => Calopogon
                                            [matchedCardinality] => 1
                                            [matchedCanonicalSimple] => Calopogon
                                            [matchedCanonicalFull] => Calopogon
                                        )
                                    [preferredResults] => Array(
                                            [0] => Array(
                                                    [dataSourceId] => 1
                                                    [dataSourceTitleShort] => Catalogue of Life
                                                    [curation] => Curated
                                                    [recordId] => 3FWL
                                                    [entryDate] => 2021-06-21
                                                    [matchedName] => Calopogon
                                                    [matchedCardinality] => 1
                                                    [matchedCanonicalSimple] => Calopogon
                                                    [matchedCanonicalFull] => Calopogon
                                                )
        */
        /* 2nd try */
        // echo "\ngo to 2nd try...[$desc]\n";
        $final = array();
        $obj = $this->run_gnverifier($desc); // regular call
        // print_r($obj);
        if($val = @$obj[0]->bestResult->matchedCanonicalSimple) $final[] = $val;
        elseif($val = @$obj[0]->bestResult->currentCanonicalSimple) $final[] = $val;
        return $final;
    }
    private function retrieve_json($id, $what, $desc)
    {   $file = self::retrieve_path($id, $what);
        if(is_file($file)) {
            $json = file_get_contents($file); // echo "\nRetrieved OK [$id]";
            return json_decode($json, true);
        }
    }
    private function gen_array_input($text)
    {
        return array("text" => $text,
        "noBayes"       => false,
        "oddsDetails"   => false, //true adds more stats, not needed
        "language"      => "eng",
        "wordsAround"   => 0,
        "verification"  => false, //default false
        "sources"       => array(1,12,169) //orig array(1,12,169). Can also be just array()
        );
    }
    private function run_partial($text)
    {   
        $arr = self::gen_array_input($text);
        $json = json_encode($arr); // exit("\n$json\n");
        $str = str_replace('"', '\"', $json); //exit("\n$str\n");
        $cmd = 'curl -ksS "https://gnfinder.globalnames.org/api/v1/find" -H  "accept: application/json" -H  "Content-Type: application/json" -d "'.$str.'"';
        $cmd .= " 2>&1";
        sleep(1); //sleep for 1 second
        $json = shell_exec($cmd);
        return $json;
    }
    private function retrieve_path($id, $what) //$id is "$taxonID_$identifier"
    {   $filename = "$id.json";
        $md5 = md5($id); //seems twice md5() already at this point.
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        return $this->json_path . "$cache1/$cache2/$filename";
    }
    private function save_json($id, $json, $what)
    {   $file = self::build_path($id, $what);
        if($f = Functions::file_open($file, "w")) {
            fwrite($f, $json);
            fclose($f);
        }
        else exit("\nCannot write file\n");
    }
    private function build_path($id, $what) //$id is "$taxonID_$identifier"
    {
        $filename = "$id.json";
        $md5 = md5($id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($this->json_path . $cache1)) mkdir($this->json_path . $cache1);
        if(!file_exists($this->json_path . "$cache1/$cache2")) mkdir($this->json_path . "$cache1/$cache2");
        return $this->json_path . "$cache1/$cache2/$filename";
    }
    /*==================== END gnfinder =====================*/
    function more_than_one_word($str)
    {
        $words = explode(" ", trim($str));
        if(count($words) > 1) return true;
        else return false;
    }
    function get_binomial_or_tri($sciname_line)
    {   // option 1 - finds a real name
        if($obj = $this->run_gnverifier($sciname_line)) {
            if($val = @$obj[0]->bestResult->matchedCanonicalSimple) {
                $val = self::basic_format_for_names($val);
                if(self::more_than_one_word($val)) return $val;
            }
            if($val = @$obj[0]->bestResult->currentCanonicalSimple) {
                $val = self::basic_format_for_names($val);
                if(self::more_than_one_word($val)) return $val;
            }
        }
        /*
        // option 2 - finds a possible name
        if($obj = $this->run_gnparser($sciname_line)) {
            if($val = @$obj[0]->canonical->full) {
                $val = self::basic_format_for_names($val);
                if(self::more_than_one_word($val)) return $val;
            }
        }
        */
        return false;
    }
    private function basic_format_for_names($str)
    {
        $str = str_ireplace(" unknown", "", $str);          //BHL 15409.txt "Telia unknown"
        $str = Functions::remove_whitespace(trim($str));
        return $str;
    }
}
?>