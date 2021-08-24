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
}
?>