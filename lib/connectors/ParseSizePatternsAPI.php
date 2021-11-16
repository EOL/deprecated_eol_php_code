<?php
namespace php_active_record;
/* */
class ParseSizePatternsAPI
{
    function __construct($resource_name = false, $param = array())
    {
        $this->resource_name = $resource_name;
        $this->param = $param;
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->tsv['part_and_dimension_mapping'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/TextMining/part_and_dimension_mapping.txt";
        $this->tsv['unit_terms'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/TextMining/unit_terms.txt";
        $this->sentence_breaks = array(";", ":", ".");
    }
    /*#################################################################################################################################*/
    private function initialize()
    {   /*
        // for gnfinder ----------
        if(Functions::is_production()) $this->json_path = '/html/gnfinder/';
        else                           $this->json_path = '/Volumes/AKiTiO4/other_files/gnfinder/';
        // ----------
        require_library('connectors/Functions_Memoirs');
        $this->func = new Functions_Memoirs($this->json_path, $this->service, $this->download_options); 
        */
    }
    // function parse_associations($html, $pdf_id, $orig_tmp = false) //for "HOST" "HOST PLANT" "ON" "FOUND ON" etc.
    function parse_size_patterns($html, $pdf_id, $orig_tmp = false, $sciname)
    {
        self::initialize();
        // exit("\n[$this->resource_name]\n");
        $this->pdf_id = $pdf_id; //works but not being used atm.
        $arr = explode("<br>", $html); 
        
        /* during dev --- force assign
        $arr[] = "orange head 0.5-1 mm. in diameter; conidia ellipsoid, hyaline, 5-6X 2/^; perithecia in";
        // print_r($arr); exit("\nelix2\n");
        */
        /*Array(
            [0] => Hyponectria cacti (Ellis & Ev.) Seaver, Mycologia 1 : 20. 1909
            [1] => Nectriella Cadi Ellis & Ev. Jour. Myc. 8 : 66. 1902.
            ...
            [5] => Distribution : Known only from the type locality. '
            [6] => orange head 0.5-1 mm. in diameter; conidia ellipsoid, hyaline, 5-6X 2/^; perithecia in
        )*/
        $sizes = self::get_size_patterns($arr); //print_r($ret); exit("\nstop muna\n");
        /* copied template
        if($this->resource_name == "NAF") $ret = self::get_relevant_blocks_using_On_FoundOn($arr, $ret, $orig_tmp); //DATA-1891
        */
        
        // exit("\n[$sciname]\n-end assoc-\n");
        // return array('sizes' => $sizes);
        // if(isset($this->debug)) print_r($this->debug);
        
        $sizes = self::make_unique($sizes, $sciname);
        return $sizes;
    }
    private function get_size_patterns($arr)
    {   // print_r($arr); print_r($this->size_mapping); exit;
        $final = array();
        foreach($arr as $row) {
            /*
            [Body Part term] [up to 10 intervening words and no sentence break] [number or number range] [units term] [dimension term]
            [Body Part term] [dimension term (noun form)] [up to three words and/or a colon and/or a dash] [number or number range] [units term]
            newline [number or number range] [units term] [dimension term]
            */
            // if(stripos($row, "1.5 cm. long") !== false) exit("\nuuuuuuuuuu\n[$row]\nuuuuuuuuuu\n"); //string is found
            
            // /* un-comment in normal operation
            if($ret = self::use_pattern_1($row)) {  // print_r($ret); // hits per row
                $final = array_merge($final, $ret);
            }
            // */
            // /* un-comment in normal operation
            if($ret = self::use_pattern_3_4($row)) {  // print_r($ret); // hits per row
                $final = array_merge($final, $ret);
            }
            // */
            if($ret = self::use_pattern_2($row)) {  // print_r($ret); // hits per row
                $final = array_merge($final, $ret);
            }
        }
        // print_r($final); exit("\n-eli1-\n");
        return $final;
    }
    private function use_pattern_1($row)
    {   
        if($ret = self::parse_row_pattern_1($row)) return $ret;
    }
    private function use_pattern_3_4($row)
    {   
        if($ret = self::parse_row_pattern_3_4($row)) return $ret;
    }
    private function use_pattern_2($row)
    {   
        if($ret = self::parse_row_pattern_2($row)) return $ret;
    }
    private function format_row($row)
    {
        $row = str_ireplace("in diameter", "in_diameter", $row);
        $row = str_ireplace("snout vent", "snout_vent", $row);
        $row = str_ireplace("head body", "head_body", $row);
        $row = str_ireplace("in greatest width", "in_greatest_width", $row);
        
        // /*
        $row = str_ireplace("archegonial thallus", "archegonial_thallus", $row);
        $row = str_ireplace("antheridial thallus", "antheridial_thallus", $row);
        // archegonial thallus => use mapping for thallus, but in occurrence, sex=male, http://purl.obolibrary.org/obo/PATO_0000384
        // antheridial thallus => use mapping for thallus, but in occurrence, sex=female, http://purl.obolibrary.org/obo/PATO_0000383
        // */
        
        // /* dimension strings:
        $strings = array("high", "long", "wide", "in_diameter", "wingspan", "thick", "in_greatest_width");
        foreach($strings as $string) {
            $row = str_ireplace(" $string.", " $string .", $row);
        }
        $row = Functions::remove_whitespace($row);
        // */
        
        /*
        [SOURCE] => NAF - 15423
        [row] => Thallus bright-green, more or less phosphorescent in appearance, plane, mostly 0.5-1 cm. long and 2-3 mm. wide, 
        the individual branches mostly 1-1,5 mm. wide;
        */
        $row = str_ireplace("individual b", "individual_b", $row); //so it can be excluded
        
        // so it can be ignored:
        $row = str_ireplace("part of a ", "part_of_a_", $row);
        
        //Pluralized:
        $row = str_ireplace("stems ", "stem ", $row);
        $row = str_ireplace("plants ", "plant ", $row);
        // $row = str_ireplace("bodies ", "body ", $row);                   //ignored
        $row = str_ireplace("leaves ", "leaf ", $row);
        // $row = str_ireplace("laminas ", "lamina ", $row);                //ignored
        $row = str_ireplace("rhizomes ", "rhizome ", $row);
        $row = str_ireplace("trunks ", "trunk ", $row);
        $row = str_ireplace("carapaces ", "carapace ", $row);
        $row = str_ireplace("snout_vents ", "snout_vent ", $row);
        $row = str_ireplace("snout-vents ", "snout-vent ", $row);
        $row = str_ireplace("head_bodies ", "head_body ", $row);
        $row = str_ireplace("head-bodies ", "head-body ", $row);
        $row = str_ireplace("heads ", "head ", $row);
        //Pluralized:
        $row = str_ireplace("capsules ", "capsule ", $row);
        $row = str_ireplace("fruits ", "fruit ", $row);
        $row = str_ireplace("achenes ", "achene ", $row);
        $row = str_ireplace("berries ", "berry ", $row);
        $row = str_ireplace("drupes ", "drupe ", $row);
        $row = str_ireplace("samaras ", "samara ", $row);
        //Pluralized:
        $row = str_ireplace("individuals ", "individual ", $row);
        $row = str_ireplace("specimens ", "specimen ", $row);
        //others
        $row = str_replace(";", " ; ", $row);
        $row = str_replace(",", " , ", $row);
        $row = str_replace(":", " : ", $row);
        
        // Plants 1-1.5 cm. high; stems short E.J. Phyle
        /* don't use this
        $row = str_ireplace(".", " . ", $row);
        $row = str_ireplace(" mm .", " mm.", $row);
        $row = str_ireplace(" cm .", " cm.", $row);
        $row = str_ireplace(" m .", " m.", $row);
        $row = str_ireplace(" in .", " in.", $row);
        $row = str_ireplace(" ft .", " ft.", $row);
        $row = str_ireplace(" dm .", " dm.", $row);
        */
        
        $row = Functions::remove_whitespace($row);
        return $row;
    }
    private function parse_row_pattern_1($row)
    {   /*
        1st: [Body Part term] [up to 10 intervening words and no sentence break] [number or number range] [units term] [dimension term]
        2nd: [Body Part term] [dimension term (noun form)] [up to three words and/or a colon and/or a dash] [number or number range] [units term]
        3rd: newline [number or number range] [units term] [dimension term]
        */
        $main = array();
        $orig_row = $row;
        $row = self::format_row($row);
        $words = explode(" ", $row);

        if($positions = self::scan_words_get_dimension_term_positions($words, $row)) {}
        else return;

        $this->eli = false;
        /* debug only
        if(substr($row,0,27) == "Archegonial thallus cuneate") {
            print_r($words); print_r($positions);
            exit("\n$row\n");
            $this->eli = true;
        }
        */

        /*Array(  --- $positions
            [0] => 16 - long
            [1] => 18 - wide
            [2] => 47 - thick
        )*/
        $final = array();
        foreach($positions as $term_key) {
            $main = array(); //initialize
            // if($term_key = self::get_dimension_term($words, $body_part)) {
                $main['dimension_term'] = $words[$term_key];
                $main['dimension_term_key'] = $term_key;
            // }
            // else return false;

            if($unit_key = self::get_units_term_v2($words, $term_key)) {
                $main['units_term'] = $words[$unit_key];
                $main['units_term_key'] = $unit_key;
            }
            else continue;
            if($this->eli) echo "\npass 100\n";
            if($number_key = self::get_number_or_number_range($words, $unit_key)) {
                $main['number_or_number_range'] = $words[$number_key];
                $main['number_or_number_range_key'] = $number_key;
            }
            else continue;
            if($this->eli) echo "\npass 200\n";

            // /*
            $body_part_key = self::get_Body_Part_term_v2($words, $number_key, $main['dimension_term']); //1st pattern
            if($this->eli) echo "\nbody_part_key: [$body_part_key][$term_key]\n";

            if($body_part_key > 0 || $body_part_key === 0) { //makes it pattern 1
                $main['Body_Part_term'] = $words[$body_part_key];
                $main['Body_Part_term_key'] = $body_part_key;
                // $main['Body_Part_term_option'] = '2nd choice'; //debug only
            }
            else continue;
            // */
            if($this->eli) echo("\npass 300\n");
            
            /* [Body Part term] [up to 10 intervening words and no sentence break] [number or number range] [units term] [dimension term] */
            if($main['Body_Part_term_key'] < $number_key && $number_key < $unit_key && $unit_key < $term_key) {
                $main['pattern'] = '1st';
                $main['row'] = $orig_row;
                // print_r($words); print_r($main); echo("\n$row\n"); //exit;
                $x = array();
                $x['SOURCE'] = $this->resource_name . " - " . $this->pdf_id;
                $x['row'] = $main['row'];
                $x['pattern'] = $main['pattern'];
                // $x['search body_part'] = $body_part;

                $x['Body_Part_term'] = $main['Body_Part_term'];
                // $x['Body_Part_term_key'] = $main['Body_Part_term_key']; //debug purposes only
                // $x['Body_Part_term_option'] = $main['Body_Part_term_option']; //debug purposes only

                $x['number_or_number_range'] = $main['number_or_number_range'];
                // $x['number_or_number_range_key'] = $main['number_or_number_range_key']; //debug purposes only

                $x['units_term'] = $main['units_term'];
                // $x['units_term_key'] = $main['units_term_key']; //debug purposes only

                $x['dimension_term'] = $main['dimension_term'];
                // $x['dimension_term_key'] = $main['dimension_term_key']; //debug purposes only

                $x = self::assign_further_metadata($x);
                // print_r($x); print_r($positions);
                @$this->debug['count'][$main['pattern']]++;
                $final[] = $x;
            }
        } //================================================= end foreach()
        if($final) return $final;
        /*
        if($unit_key = self::get_units_term($words)) $main['units_term'] = $words[$unit_key];
        else return false;
        */
    }
    private function assign_further_metadata($x)
    {
        if($body_part = @$x['Body_Part_term']) {
            // archegonial thallus => use mapping for thallus, but in occurrence, sex=male, http://purl.obolibrary.org/obo/PATO_0000384
            // antheridial thallus => use mapping for thallus, but in occurrence, sex=female, http://purl.obolibrary.org/obo/PATO_0000383
            if($body_part == 'archegonial_thallus') $x['occurrence_sex'] = 'http://purl.obolibrary.org/obo/PATO_0000384'; //male
            elseif($body_part == 'antheridial_thallus') $x['occurrence_sex'] = 'http://purl.obolibrary.org/obo/PATO_0000383'; //female
        }
        return $x;
    }
    private function parse_row_pattern_3_4($row)
    {   /* newline [number or number range] [units term] [dimension term] */
        $main = array();
        $orig_row = $row;
        $row = self::format_row($row);
        $words = explode(" ", $row);

        if($positions = self::scan_words_get_dimension_term_positions($words, $row, 3)) {} //2nd param $row is just debug here. 3rd param is pattern No.
        else return;
        /*Array(  --- $positions
            [0] => 16 - long
            [1] => 18 - wide
            [2] => 47 - thick
        )*/
        $final = array();
        foreach($positions as $term_key) {
            $main = array(); //initialize

            $main['dimension_term'] = $words[$term_key];
            $main['dimension_term_key'] = $term_key;

            if($unit_key = self::get_units_term_v2($words, $term_key)) {
                $main['units_term'] = $words[$unit_key];
                $main['units_term_key'] = $unit_key;
            }
            else continue;

            if($number_key = self::get_number_or_number_range($words, $unit_key)) {
                $main['number_or_number_range'] = $words[$number_key];
                $main['number_or_number_range_key'] = $number_key;
            }
            else continue;

            $body_part_key = self::get_Body_Part_term_v2($words, $number_key, $main['dimension_term']); //3rd pattern
            if($body_part_key > 0 || $body_part_key === 0) {} // what makes it a pattern 1
            elseif($number_key == 0) { //what makes it a pattern 3 per Jen
                /* 3rd: newline [number or number range] [units term] [dimension term] */
                $main['pattern'] = '3rd';
                $final = self::proceed_pattern_3_filter_step($main, $number_key, $unit_key, $term_key, $orig_row, $final);
            }
            else { // not too relaxed - Per Eli
                /* 4th: [zero to 5 (any) words] [number or number range] [units term] [dimension term] */
                $main['pattern'] = '4th';
                $fifth_word = @$words[$number_key-6];
                if($fifth_word) continue;
                $final = self::proceed_pattern_3_filter_step($main, $number_key, $unit_key, $term_key, $orig_row, $final);
            }
            /* not used --- this doesn't care how many words before the number_or_number_range. It just assumes the number_or_number_range found is valid.
            else { // too relaxed - Per Eli
                $final = self::proceed_pattern_3_filter_step($main, $number_key, $unit_key, $term_key, $orig_row, $final);
            }
            */
        } //================================================= end foreach()
        if($final) return $final;
        /*
        if($unit_key = self::get_units_term($words)) $main['units_term'] = $words[$unit_key];
        else return false;
        */
    }
    private function proceed_pattern_3_filter_step($main, $number_key, $unit_key, $term_key, $orig_row, $final)
    {   /* newline [number or number range] [units term] [dimension term] */
        if($number_key < $unit_key && $unit_key < $term_key) {
            $main['row'] = $orig_row;
            // print_r($words); print_r($main); echo("\n$row\n"); //exit;
            $x = array();
            $x['SOURCE'] = $this->resource_name . " - " . $this->pdf_id;
            $x['row'] = $main['row'];
            $x['pattern'] = $main['pattern'];

            $x['number_or_number_range'] = $main['number_or_number_range'];
            // $x['number_or_number_range_key'] = $main['number_or_number_range_key']; //debug purposes only

            $x['units_term'] = $main['units_term'];
            // $x['units_term_key'] = $main['units_term_key']; //debug purposes only

            $x['dimension_term'] = $main['dimension_term'];
            // $x['dimension_term_key'] = $main['dimension_term_key']; //debug purposes only

            $x = self::assign_further_metadata($x);
            // print_r($x); // print_r($positions);
            @$this->debug['count'][$main['pattern']]++;
            $final[] = $x;
        }
        return $final;
    }
    private function scan_words_get_dimension_term_positions($words, $row, $pattern_no = 1) //2nd param $row is just for debug
    {
        if($pattern_no == 1) $dimension_terms = array("high", "long", "wide", "in_diameter", "wingspan", "thick", "in_greatest_width");
        elseif($pattern_no == 3) $dimension_terms = array("high", "long", "wingspan");
        else exit("\nUn-initialized pattern\n");
        $i = -1;
        $positions = array();
        // print_r($words);
        foreach($words as $word) { $i++;
            if(in_array($word, $dimension_terms)) $positions[] = $i; //$positions[] = $i." - $word";
        }
        if($positions) {
            // print_r($positions); echo "\n[$row]\n"; //exit;
            return $positions;
        }
    }
    private function get_Body_Part_term_v2($words, $number_key, $dimension_term)
    {
        // /* additional filter: if there is > 1 body part terms in the intervening 10-word, then ignore
        if(self::has_more_than_1_bodypart_terms_in_intervening_words($words, $number_key, $dimension_term)) return false;
        // */
        
        for($i=1; $i <= 15; $i++) { //about 10 intervening words
            $number_key--;
            $body_part_key = $number_key;
            if($body_part_str = @$words[$body_part_key]) {
                
                if(in_array($body_part_str, $this->sentence_breaks)) return false;
                $arr = self::get_body_part_or_parts_for_a_term($dimension_term);
                
                /*
                if($body_part_str == "Plant") {
                    print_r($words);
                    print_r($arr);
                    if(in_array(strtolower($body_part_str), $arr)) echo "\nfound naman[$body_part_key]\n";
                    else echo "\nnot found\n";
                    // exit;
                }
                */
                
                if(in_array(strtolower($body_part_str), $arr)) return $body_part_key;
            }
            else return false;
        }
    }
    private function has_more_than_1_bodypart_terms_in_intervening_words($words, $number_key, $dimension_term)
    {   /* debug only
        print_r($words);
        if($words[0] == 'plant' && $words[1] == 'densely' && $words[2] == 'gregarious') {
            print_r($words); exit;
        }
        */
        $body_part_key = $number_key;
        $final = array();
        for($i=1; $i <= 10; $i++) { //about 10 intervening words
            $body_part_key--;
            if($body_part_str = @$words[$body_part_key]) {
                if(in_array($body_part_str, $this->sentence_breaks)) break;
                $arr = self::get_body_part_or_parts_for_a_term($dimension_term);
                if(in_array(strtolower($body_part_str), $arr)) $final[$body_part_str] = '';
            }
            else break; //meaning end of line
        }
        if(count($final) >= 2) {
            // print_r($words); print_r($final); echo "-elix1-"; exit; //debug only
            return true;
        }
    }
    private function get_number_or_number_range($words, $unit_key)
    {
        $number_key = $unit_key - 1;
        if($number_str = $words[$number_key]) {
            if(self::get_numbers_from_string($number_str)) return $number_key;
        }
    }
    private function get_units_term_v2($words, $term_key)
    {
        $unit_key = $term_key - 1;
        if($unit_str = @$words[$unit_key]) {
            $arr = array_keys($this->unit_terms);
            // print_r($this->unit_terms); print_r($arr); exit;
            if(in_array(strtolower($unit_str), $arr)) return $unit_key;
        }
    }
    private function get_units_term($words)
    {   //print_r($this->unit_terms); exit;
        foreach(array_keys($this->unit_terms) as $unit) {
            $key = array_search($unit, $words);
            if($key !== false) return $key; //str found in array
        }
    }
    private function get_dimension_term($words, $body_part)
    {   
        $valid_dimension_terms = self::get_dimension_term_or_terms_for_a_body_part($body_part);
        // print_r($valid_dimension_terms); //exit;
        foreach($valid_dimension_terms as $term) {
            $key = array_search($term, $words);
            if($key !== false) return $key; //str found in array
        }
    }
    private function get_dimension_term_or_terms_for_a_body_part($body_part)
    {
        // print_r($this->size_mapping); exit("\n222\n");
        /*[head] => Array(
                   [0] => Array(
                           [term] => wide
                           [term_noun] => width
                           [uri] => http://eol.org/schema/HeadWidth
                       )
                   [1] => Array(
                           [term] => long
                           [term_noun] => length
                           [uri] => http://purl.obolibrary.org/obo/OBA_VT0000038
                       )
                   [2] => Array(
                           [term] => in_diameter
                           [term_noun] => diameter
                           [uri] => http://eol.org/schema/HeadWidth
                       )
               )
        */
        $final = array();
        if($recs = $this->size_mapping[$body_part]) {
            foreach($recs as $rec) $final[] = $rec['term'];
        }
        else exit("\nUndefined body part: [$body_part]\n");
        return $final;
    }
    private function get_body_part_or_parts_for_a_term($term)
    {   // print_r($this->size_mapping); exit("\n222\n");
        /*[head] => Array(
                   [0] => Array(
                           [term] => wide
                           [term_noun] => width
                           [uri] => http://eol.org/schema/HeadWidth
                       )
                   [1] => Array(
                           [term] => long
                           [term_noun] => length
                           [uri] => http://purl.obolibrary.org/obo/OBA_VT0000038
                       )
                   [2] => Array(
                           [term] => in_diameter
                           [term_noun] => diameter
                           [uri] => http://eol.org/schema/HeadWidth
                       )
               )
        */
        $final = array();
        foreach($this->size_mapping as $body_part => $recs) {
            foreach($recs as $rec) {
                if($rec['term'] == $term) $final[$body_part] = '';
            }
        }
        return array_keys($final);
    }
    private function parse_row_pattern_2($row)
    {   /*
        1st: [Body Part term] [up to 10 intervening words and no sentence break] [number or number range] [units term] [dimension term]
        2nd: [Body Part term] [dimension term (noun form)] [up to three words and/or a colon and/or a dash] [number or number range] [units term]
        3rd: newline [number or number range] [units term] [dimension term]
        */
        $main = array();
        $orig_row = $row;
        $row = self::format_row($row);
        $words = explode(" ", $row);
        if($positions = self::scan_words_get_body_part_positions($words, $row)) {}
        else return;
        /*Array( --- all body part items
            [0] => plant
            [1] => body
            [2] => leaf
            [3] => lamina
            [4] => rhizome
            [5] => trunk
            [6] => stem
            [7] => carapace
            [8] => snout_vent
            [9] => snout-vent
            [10] => head_body
            [11] => head-body
            [12] => head
            [13] => Newly added:
            [14] => Eli added:
        )
        Array( --- $positions
            [0] => 19
        )*/
        $final = array();
        foreach($positions as $body_part_key) {
            $main = array(); //initialize
            /*[Body Part term] [dimension term (noun form)] [up to three words and/or a colon and/or a dash] [number or number range] [units term]*/

            $main['Body_Part_term'] = $words[$body_part_key];
            $main['Body_Part_term_key'] = $body_part_key;

            if($term_key = self::get_dimension_term_noun($words, $body_part_key)) {
                $main['dimension_term_noun'] = $words[$term_key];
                $main['dimension_term_noun_key'] = $term_key;
            }
            else continue;

            if($number_key = self::get_number_or_number_range_onwards($words, $term_key)) {
                $main['number_or_number_range'] = $words[$number_key];
                $main['number_or_number_range_key'] = $number_key;
            }
            else continue;

            if($unit_key = self::get_units_term_onwards($words, $number_key)) {
                $main['units_term'] = $words[$unit_key];
                $main['units_term_key'] = $unit_key;
            }
            else continue;

            if($body_part_key < $term_key && $term_key < $number_key && $number_key < $unit_key) {
                $main['pattern'] = '2nd';
                $main['row'] = $orig_row;
                // print_r($words); print_r($main); echo("\n$row\n"); //exit;
                $x = array();
                $x['SOURCE'] = $this->resource_name . " - " . $this->pdf_id;
                $x['row'] = $main['row'];
                $x['pattern'] = $main['pattern'];

                $x['Body_Part_term'] = $main['Body_Part_term'];
                $x['dimension_term_noun'] = $main['dimension_term_noun'];
                $x['number_or_number_range'] = $main['number_or_number_range'];
                $x['units_term'] = $main['units_term'];

                $x = self::assign_further_metadata($x);
                // print_r($x); exit("\nfinally a hit\n");
                @$this->debug['count'][$main['pattern']]++;
                $final[] = $x;
            }
        } //================================================= end foreach()
        if($final) return $final;
    }
    private function get_dimension_term_noun($words, $body_part_key)
    {
        $term_nouns = array("height", "length", "width", "diameter", "in_diameter", "wingspan", "thickness");
        $term_key = $body_part_key + 1;
        if($term_noun = @$words[$term_key]) {
            if(in_array(strtolower($term_noun), $term_nouns)) return $term_key;
        }
    }
    /*[Body Part term] [dimension term (noun form)] [up to three words and/or a colon and/or a dash] [number or number range] [units term]*/
    private function get_number_or_number_range_onwards($words, $term_key) //for pattern 2nd
    {
        $number_key = $term_key;
        for($i=1; $i <= 5; $i++) { //about 3 words between the number_number_range
            $number_key++;
            if($number_str = @$words[$number_key]) {
                if(self::get_numbers_from_string($number_str)) return $number_key;
            }
            else return false;
        }
    }
    private function get_units_term_onwards($words, $number_key)
    {
        $unit_key = $number_key + 1;
        if($unit_str = @$words[$unit_key]) {
            $arr = array_keys($this->unit_terms);
            if(in_array(strtolower($unit_str), $arr)) return $unit_key;
        }
    }
    private function scan_words_get_body_part_positions($words, $row) //2nd param $row is just for debug
    {   // /*
        $body_parts = array_keys($this->size_mapping);
        $body_parts = array_filter($body_parts); //remove null arrays
        $body_parts = array_unique($body_parts); //make unique
        $body_parts = array_values($body_parts); //reindex key
        // print_r($body_parts); exit;
        // */
        $i = -1;
        $positions = array();
        foreach($words as $word) { $i++;
            if(in_array(strtolower($word), $body_parts)) $positions[] = $i; //$positions[] = $i." - $word";
        }
        if($positions) {
            // print_r($positions); echo "\n[$row]\n"; //exit;
            return $positions;
        }
    }
    private function get_numbers_from_string($str)
    {
        if(preg_match_all('/\d+/', $str, $a)) return $a[0];
    }
    function load_mappings()
    {   /* not used for now
        $map['body part string'] = array("plant", "body", "leaf", "lamina", "rhizome", "trunk", "stem", "carapace", "snout vent", "snout-vent", "head body", "head-body", "head");
        $map['dimension string'] = array("high", "long", "wide", "in diameter", "wingspan");
        $map['dimension string (noun form)'] = array("height", "length", "width", "diameter", "wingspan");
        */
        $this->size_mapping = self::loop_tsv('part_and_dimension_mapping');
        $this->unit_terms = self::loop_tsv('unit_terms');
        // print_r($this->size_mapping); print_r($this->unit_terms); exit("\n111\n");
    }
    private function loop_tsv($what)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*12; //12 hrs //0; //debug only --- un-comment when source TSV files are updated
        $options['cache'] = 1;
        if($local_tsv = Functions::save_remote_file_to_local($this->tsv[$what], $options)) {
            $arr = file($local_tsv);
            unlink($local_tsv);
            $i = 0;
            foreach($arr as $row) { $i++;
                $rek = explode("\t", $row);
                $rek = array_map('trim', $rek);
                if($i == 1) $fields = $rek;
                else {
                    $k = 0; $rec = array();
                    foreach($fields as $fld) {
                        $rec[$fld] = @$rek[$k];
                        $k++;
                    }
                    // print_r($rec);
                    /*Array(
                        [Body Part string] => leaf
                        [dimension term] => long
                        [dimension term (noun form)] => length
                        [URI] => http://purl.obolibrary.org/obo/FLOPO_0001133
                    )*/
                    if($what == 'part_and_dimension_mapping') {
                        $body_part = $rec['Body Part string'];
                        $term = $rec['dimension term'];
                        $term_noun = $rec['dimension term (noun form)'];
                        $final[$body_part][] = array('term' => $term, 'term_noun' => $term_noun, 'uri' => $rec['URI']);
                    }
                    /*Array(
                        [units string] => mm
                        [uri] => http://purl.obolibrary.org/obo/UO_0000016
                    )*/
                    elseif($what == 'unit_terms') {
                        // print_r($rec); exit;
                        if($val = $rec['units string']) $final[$val] = $rec['uri'];
                    }
                }
            }
        }
        return $final;
    }
    function write_input_output_report_for_Jen($size_patterns, $sciname)
    {   /*Array(
            [0] => Array(
                    [SOURCE] => NAF - 15406
                    [row] => Stromata consisting of a sterile stem and a subglobose fertile head; stem very slender, 2-8 cm. long, yellowish; head 5-8X4mm., gold en -yellow, darker with age, roughened, by the prominent necks ; perithecia ovoid, immersed or partially immersed ; asci cylindric, 6.5-7 /i thick; spores filiform, many-septate, hyaline, finally separating into segments 6-8 " long.
                    [pattern] => 1st
                    [Body_Part_term] => stem
                    [number_or_number_range] => 2-8
                    [units_term] => cm.
                    [dimension_term] => long
                )
        )*/
        // print_r($size_patterns); exit("\nx\n");
        // print_r($this->param); exit;
        /*Array(
            [resource_id] => 15423
            [resource_name] => NAF
            [doc] => BHL
            [IOReport] => NAF_first7
        )*/
        $filename = CONTENT_RESOURCE_LOCAL_PATH."reports/".$this->param['IOReport']."_size_patterns_".date("Y_m_d").".txt";
        $WRITE = fopen($filename, "a"); //initialize
        foreach($size_patterns as $rek) {
            foreach($rek as $key => $val) {
                /* no longer needed
                if($key == 'SOURCE') $str = "[$key] => $val - $sciname";
                else                 $str = "[$key] => $val";
                */
                $str = "[$key] => $val";
                fwrite($WRITE, $str."\n\n");
            }
            fwrite($WRITE, "------------------------------------------------------------------------------------------\n");
        }
        fclose($WRITE);
    }
    private function is_one_word($str)
    {
        $arr = explode(" ", $str);
        if(count($arr) == 1) return true;
        return false;
    }
    function write_MoF_size($rekords, $taxon, $archive_builder, $meta, $taxon_ids, $bibliographicCitation = "", $resource_id) //2nd param is source taxon object
    {   //exit("\ndito 2\n");
        $this->taxon_ids = $taxon_ids;
        $this->archive_builder = $archive_builder;
        // print_r($rekords); //exit("\n111\n");
        /*Array(
            [0] => Array(
                    [SOURCE] => NAF - 15406
                    [row] => Stromata with a slender stalk 1-2 mm. long and a globose or clavate red head ; conidia nearly ellipsoid, straight or a little curved, 3-5 X 2 m, granular within ; perithecia few, surrounding the base of the stalked stroma, sessile, globose, smooth, orange, finally partially collapsed ; asci clavate, about 80 X 13-16 /u ; spores 2-seriate, ovoid, 22-26 X 7 /«, filled with numerous oil-drops.
                    [pattern] => 4th
                    [number_or_number_range] => 1-2
                    [units_term] => mm.
                    [dimension_term] => long
                )
            [sciname] => Sphaerostilbe cinnabarina
            [pdf_id] => 15406
        )*/
        $sciname = $rekords['sciname']; unset($rekords['sciname']);
        $pdf_id = $rekords['pdf_id']; unset($rekords['pdf_id']);
        // print_r($rekords); exit("\n222\n");
        
        // /*
        foreach($rekords as $rek) {
            if($rek['pattern'] == '4th') {//exclude 4th pattern from resource. But include it in input-output report.
                if($rek['dimension_term'] == 'high') {} //include
                else continue;
            }
            $rec = array();
            $rec["taxon_id"] = $taxon->taxonID;
            $rec["catnum"] = md5(json_encode($rek));
            $rec['measurementValue'] = $rek['number_or_number_range'];
            $rec['measurementType'] = self::given_body_part_and_term_get_uri(@$rek['Body_Part_term'], $rek['dimension_term'], $rek); //3rd param $rek for debug only
            if($val = $this->unit_terms[$rek['units_term']]) $rec['measurementUnit'] = $val;
            else exit("\nUndefined unit: [".$rek['units_term']."]\n");
            $rec['measurementRemarks'] = "$sciname. ".$rek['row'];
            $rec['source'] = @$meta[$pdf_id]['dc.relation.url'];
            $rec['bibliographicCitation'] = $bibliographicCitation;
            if($sex = @$rek['occurrence_sex']) $rec['occur']['sex'] = $sex;
            $func = new TraitGeneric($resource_id, $archive_builder, false);
            $func->add_string_types($rec, $rec['measurementValue'], $rec['measurementType'], "true");
            // if($uri) $this->func->add_string_types($rex, $uri, 'http://purl.org/dc/terms/contributor', "child"); --- copied template
        }
        // */
    }
    private function given_body_part_and_term_get_uri($body_part, $term, $rex) //$rex for debug only
    {   // print_r($this->size_mapping); exit("\n222\n");
        foreach($this->size_mapping[strtolower($body_part)] as $rec) {
            if($rec['term'] == $term) return $rec['uri'];
        }
        print_r($rex);
        exit("\nInvestigate: undefined URI for body_part:[$body_part] | term:[$term]\n");
        /*[head] => Array(
                   [0] => Array(
                           [term] => wide
                           [term_noun] => width
                           [uri] => http://eol.org/schema/HeadWidth
                       )
                   [1] => Array(
                           [term] => long
                           [term_noun] => length
                           [uri] => http://purl.obolibrary.org/obo/OBA_VT0000038
                       )
                   [2] => Array(
                           [term] => in_diameter
                           [term_noun] => diameter
                           [uri] => http://eol.org/schema/HeadWidth
                       )
               )
        */
    }
    private function make_unique($sizes, $sciname)
    {   //print_r($sizes); exit;
        /*Array(
            [0] => Array(
                    [SOURCE] => NAF - 15425
                    [row] => Plants 2-3 mm. high, pale green, hyaline; stems mostly simple; leaves 3-5 pairs, the upper much larger, 1-1.5 mm. long, very thin and soft, oblong-lanceolate, acute, entire, ecostate; leaf-cells oblong-hexagonal, about 30 X 45-60 m, very thin-walled, a single row at the margin narrow and elongated; dioicous; sporophyte terminal; seta 1-2 mm. long; capsule oblong-ovoid, erect and symmetric; calyptra cylindric-conic, covering the beak only; operculum about 1 mm. long, long-rostrate, a little shorter than the urn; peristome normal; spores 11-15 /x in diameter, in autumn.
                    [pattern] => 1st
                    [Body_Part_term] => plant
                    [number_or_number_range] => 2-3
                    [units_term] => mm.
                    [dimension_term] => high
                )
            [1] => Array(
                    [SOURCE] => NAF - 15425
                    [row] => Plants 2-3 mm. high, pale green, hyaline; stems mostly simple; leaves 3-5 pairs, the upper much larger, 1-1.5 mm. long, very thin and soft, oblong-lanceolate, acute, entire, ecostate; leaf-cells oblong-hexagonal, about 30 X 45-60 m, very thin-walled, a single row at the margin narrow and elongated; dioicous; sporophyte terminal; seta 1-2 mm. long; capsule oblong-ovoid, erect and symmetric; calyptra cylindric-conic, covering the beak only; operculum about 1 mm. long, long-rostrate, a little shorter than the urn; peristome normal; spores 11-15 /x in diameter, in autumn.
                    [pattern] => 1st
                    [Body_Part_term] => leaf
                    [number_or_number_range] => 1-1.5
                    [units_term] => mm.
                    [dimension_term] => long
                )
        )*/
        $final = array();
        foreach($sizes as $rec) {
            $rec['SOURCE'] .= " - $sciname";
            $json = json_encode($rec);
            $md5 = md5($json);
            if(!isset($this->saved[$md5])) {
                $final[] = $rec;
                $this->saved[$md5] = '';
            }
        }
        return $final;
    }
}
?>