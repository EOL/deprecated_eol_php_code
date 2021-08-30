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
                if($this->first_word_is_allcaps($arr[1])) {
                    $this->Distribution_Stop_pattern[$ctr-1] = ''; // e.g. "2. LINDMANIA Mez, in DC. Monog. Phan. 9: 535. 1896."
                    // echo "\n-----\n"; print_r($rows2); echo "\n-----\n";
                }
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
            if($this->first_word_is_RomanNumeral($arr[1])) { // print_r($arr); echo("\n[$arr[1]]elix1");
                $words = explode(" ", trim($arr[1]));
                if($second = @$words[1]) {
                    if(ctype_upper($second)) {
                        $this->Distribution_Stop_pattern[$ctr-1] = '';
                        // print_r($rows2); exit("\n".$arr[1]."\nxxx\n");
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
        $first = $words[0];
        $first = str_replace(array(".", ","), "", $first);
        if($this->str_is_RomanNumeral($first)) return true;
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
}
?>