<?php
namespace php_active_record;
/* */
class ParseListTypeAPI_Memoirs extends Functions_Memoirs
{
    function __construct() {}
    /*#################################################################################################################################*/
    function parse_list_type_pdf($input)
    {   /*
        "newline
        Header [12 words or less, including ""List"", and at least one of taxon name, vernacular name, habitat term and/or geographic term] newline
        [lots of non-target text] newline
        Subheader [including higher taxon name] newline
        newline
        Line [including species name and geographic and/or habitat terms] newline
        Line [including species name and geographic and/or habitat terms] newline"
        */
        // print_r($input); exit("\nelix\n");
        /*Array(
            [filename] => SCtZ-0437.txt
            [type] => list
            [epub_output_txts_dir] => /Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/
            [lines_before_and_after_sciname] => 2
        )*/
        // /* start as copied template
        if($val = $input['epub_output_txts_dir']) $this->path['epub_output_txts_dir'] = $val;
        $this->lines_to_tag = array();
        $this->scinames = array();
        $filename = $input['filename'];
        $this->filename = $filename; //for referencing below
        $lines_before_and_after_sciname = $input['lines_before_and_after_sciname'];
        $this->magic_no = $this->no_of_rows_per_block[$lines_before_and_after_sciname];
        self::get_main_scinames_v2($filename); print_r($this->lines_to_tag); //exit("\nstopx\n");
        echo "\n lines_to_tag (list): ".count($this->lines_to_tag)."\n"; //exit("\n-end-\n");
        if(count($this->lines_to_tag)) {
            echo "\nList-type documents: [$filename]\n";
            $edited_file = self::add_taxon_tags_to_text_file_LT($filename); //exit("\nstop here muna\n");
            self::remove_some_rows_LT($edited_file); //exit("\nstop muna\n");
            $tagged_file = self::show_parsed_texts_for_mining_LT($edited_file);
            self::get_scinames_per_list($tagged_file);
            // // print_r($this->scinames); 
            // echo "\nRaw scinames count: ".count($this->scinames)."\n";
        }
        // */
    }
    private function get_scinames_per_list($tagged_file)
    {   echo "\nget_scinames_per_list()... looping [$tagged_file] \n";
        ///Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/SCtZ-0437_tagged_LT.txt
        $destination = str_replace("_tagged_LT.txt", "_descriptions_LT.txt", $tagged_file);
        $WRITE = fopen($destination, "w"); //initialize
        
        $contents = file_get_contents($tagged_file);
        if(preg_match_all("/<sciname=(.*?)<\/sciname>/ims", $contents, $a)) { // print_r($a[1]); exit;
            foreach($a[1] as $block) {
                $rows = explode("\n", $block);
                if(preg_match("/\'(.*?)\'/ims", $rows[0], $a2)) $list_header = $a2[1];
                array_shift($rows);
                $rows = array_filter($rows); //remove null arrays
                $rows = array_unique($rows); //make unique
                $rows = array_values($rows); //reindex key
                if($rows) {
                    if($this->pdf_id == '120083') {
                        if($list_header != "OZARK-OUACHITA PLECOPTERA SPECIES LIST") continue; //only has 1 legitimate list
                        else $list_header .= ". Ozark Mountain forests.";
                    }
                    elseif($this->pdf_id == '118237') { //skipped list-type anyway
                        if($list_header != "ADULT SYSTEMATIC TREATMENT") continue; //only has 1 legitimate list
                        // else $list_header .= ". Ozark Mountain forests.";
                    }
                    echo "\n------------------------\n$list_header\n------------------------\n";
                    // print_r($rows); //continue; //exit; //good debug
                    echo "\n n = ".count($rows)."\n"; //continue; //exit;
                    $i = 0; $possible_genus = array();
                    $possible_genux = ''; //for those lists where the row starts with a species name e.g. "bicolor Guignot 57–36! (Brazil)"
                    foreach($rows as $sciname_line) { $rek = array(); $i++;
                        if(substr($sciname_line,0,1) == " ") continue;
                        $rek['verbatim'] = $sciname_line;
                        
                        if(stripos($sciname_line, "...") !== false) continue; //string is found
                        if(stripos($sciname_line, " and ") !== false) continue; //string is found
                        if(stripos($sciname_line, ":") !== false) continue; //string is found
                        if(stripos($sciname_line, "=") !== false) continue; //string is found
                        if(stripos($sciname_line, '\\') !== false) continue; //string is found
                        if(stripos($sciname_line, '/') !== false) continue; //string is found
                        if(stripos($sciname_line, '%') !== false) continue; //string is found
                        if(stripos($sciname_line, ' mil ') !== false) continue; //string is found
                        if(stripos($sciname_line, '»') !== false) continue; //string is found
                        if(stripos($sciname_line, 'p.') !== false) continue; //string is found
                        if(stripos($sciname_line, '<') !== false) continue; //string is found
                        if(stripos($sciname_line, '>') !== false) continue; //string is found
                        if(stripos($sciname_line, '£') !== false) continue; //string is found

                        // /*
                        $cont = true;
                        $dont_have_these_chars_anywhere = array("Tj", "•", "■", "♦", "§", "»", "~", "*—", "-^", "«0", "«O", "jqL", "fNiri", "oooooooo", "^^",
                        "vooo", ".£", "CAr<", "c4r", "-3-r", "i^o", "*^D", '-"<*', "r<^", "ONTf", "—'0", "c^r", "S.S3", "/ivi", "^h", "r^", "Otj", "©",
                        "1-H-H", ",^", "OOONO", "— r-", "—«", "V-)", "— st", "«/", "t«M", "0000", "i—l", "i—", "iip1", "oooo", "i^", "-oo", "m^",
                        "Tt—", "^n", ">n", "VI—", "^—^", "c^", ">n", '^', "«", " are ", " from ", " to ", " in ", "river", "region");
                        foreach($dont_have_these_chars_anywhere as $char) {
                            if(stripos($sciname_line, $char) !== false) $cont = false; //found
                        }
                        if(!$cont) continue;
                        // */
                        
                        if(substr($sciname_line,0,1) == "*") $sciname_line = trim(substr($sciname_line,1,strlen($sciname_line)));
                        if(substr($sciname_line,0,1) == "?") continue;
                        if(substr($sciname_line,0,1) == "(") continue;
                        if(substr($sciname_line,0,1) == ".") continue;
                        
                        $sciname_line = str_ireplace("†","",$sciname_line); //special chars like this messes up GNRD and Gnparser
                        $sciname_line = str_replace(".—", " .— ", $sciname_line);
                        $sciname_line = Functions::remove_whitespace($sciname_line);
                        
                        // /* fill-up genus name for rows e.g. "bicolor Guignot 57–36! (Brazil)" --> SCtZ-0033.txt
                        $words = explode(" ", $sciname_line);
                        if(ctype_upper(substr($sciname_line,0,1))) { //names starting with upper case
                            $possible_genux = $words[0];
                        }
                        else { //rows starting with lower case
                            $sciname_line = $possible_genux." ".$sciname_line;
                        }
                        // */
                        if(strlen($words[0]) == 1) continue; //first word is just a single letter
                        if(count($words) == 1) continue;
                        if(count($words) > 6) continue;

                        if(ctype_lower(substr($words[0],0,1))) continue; //1st word should be capital letter
                        if(ctype_upper(substr($words[1],0,1))) continue; //2nd word should be small letter
                        
                        // /*
                        $no_first_word_equal_to_these = array('Number', 'more', 'Union', 'Type', 'Figs.', 'Fig.', 'Type');
                        $cont = true;
                        foreach($no_first_word_equal_to_these as $first) {
                            if($words[0] == $first) $cont = false;
                        }
                        if(!$cont) continue;
                        // */
                        
                        if(self::is_a_rank_name($words[0])) continue;

                        if(self::last_word_not_num_not_LT_4_digits($words)) {}
                        else continue;

                        if(self::row_where_all_words_have_max_three_chars($sciname_line)) continue;
                        
                        if($obj = self::run_gnparser($sciname_line)) {
                            $rek['normalized gnparser'] = @$obj[0]->normalized;
                        }
                        
                        // /* customized
                        $sciname_line = str_replace("s-*floridanus", "floridanus", $sciname_line); // SCtZ-0033
                        // $sciname_line = str_replace(", USNM", " , USNM", $sciname_line); // SCtZ-0613 --- already redundant from below

                        $sciname_line = str_replace(",", " , ", $sciname_line);
                        $sciname_line = str_replace(":", " : ", $sciname_line);
                        $sciname_line = str_replace(";", " ; ", $sciname_line);
                        $sciname_line = trim(Functions::remove_whitespace($sciname_line));
                        // */
                        
                        debug("\nrun_GNRD 3: [$sciname_line]\n");
                        if($obj = self::run_GNRD($sciname_line)) { //GNRD OBSOLETE, this now uses gnfinder
                            // $sciname = @$obj->names[0]->scientificName; //GNRD OBSOLETE
                            $sciname = @$obj[0];
                            if(!$sciname) continue; //new Jul 1, 2021
                            $rek['sciname GNRD'] = $sciname;
                            if($obj = self::run_gnparser($sciname_line)) {
                                $authorship = @$obj[0]->authorship->verbatim;
                                $rek['authorship gnparser'] = $authorship;
                                $rek['scientificName_author'] = trim("$sciname $authorship");
                                $rek['scientificName_author_cleaned'] = self::clean_sciname($rek['scientificName_author']);

                                // /* fill-up possible incomplete genus name. e.g. "A. plumosa" should be "Aristida plumosa"
                                $words = explode(" ", $sciname);
                                if(substr($sciname,1,2) == ". ") { //needs fill-up genus name -- assignment
                                    // print_r($rek); echo " - xxx ";//exit;
                                    $first_letter = substr($sciname,0,1);
                                    array_shift($words);
                                    $tmp = @$possible_genus[$first_letter]." ".implode(" ", $words)." ".$authorship;
                                    $rek['scientificName_author'] = trim(Functions::remove_whitespace($tmp));
                                    // exit("\n".$rek['scientificName_author']."\n-end-\n");
                                    $rek['scientificName_author_cleaned'] = self::clean_sciname($rek['scientificName_author']);
                                    // print_r($rek); echo " - aaa "; exit("\nstopx\n");
                                }
                                else $possible_genus[substr($sciname,0,1)] = $words[0]; //initialize
                                // */
                            }
                            
                            // /* reconcile gnparser vs GNRD
                            if(!@$rek['scientificName_author_cleaned']) $rek['scientificName_author_cleaned'] = $rek['sciname GNRD'];
                            // */
                            
                            if($GLOBALS["ENV_DEBUG"]) print_r($rek);
                            else echo "\nlist: [".$rek['scientificName_author_cleaned']."]\n";
                            // exit; //good debug
                            
                            // /* another filter criteria
                            $words = explode(" ", $rek['scientificName_author_cleaned']);
                            if(@$words[1]) {
                                if(ctype_upper(substr(@$words[1],0,1))) continue; //2nd word must not be capitalized
                            }
                            else continue; //there must be a 2nd word
                            // */
                            
                            $tmp = $rek['scientificName_author_cleaned'];
                            $tmp = str_replace(" ,", ",", $tmp);
                            $tmp = str_replace(" :", ":", $tmp);
                            $tmp = str_replace(" ;", ";", $tmp);
                            $tmp = trim(Functions::remove_whitespace($tmp));
                            $rek['scientificName_author_cleaned'] = $tmp;
                            
                            // /* good debug
                            if($rek['verbatim'] == "White River, Winslow.") {
                                print_r($rek); exit("\nstop muna\n");
                            }
                            // if(!$rek['sciname GNRD']) print_r($rek);
                            // */
                            
                            fwrite($WRITE, implode("\t", array($rek['scientificName_author_cleaned'], $rek['verbatim'], $list_header))."\n");
                            /*Array(
                                [verbatim] => Miscophus heliophilus Pulawski. 1 ♀. Captured in Malaise trap. The species has been described recently (Pulawski, 1968) from this unique specimen.
                                [normalized gnparser] => Miscophus heliophilus Pulawski.
                                [sciname GNRD] => Miscophus heliophilus
                                [authorship gnparser] => Pulawski.
                                [scientificName_author] => Miscophus heliophilus Pulawski.
                                [scientificName_author_cleaned] => Miscophus heliophilus Pulawski
                            )
                            Array(
                                [verbatim] => Achirus fluviatilis Meek and Steindachner, 1928, PA, Lenguado; Chirichigno, 1963:75.
                                [normalized gnparser] => Achirus fluviatilis Meek & Steindachner 1928
                                [sciname GNRD] => Achirus fluviatilis
                                [authorship gnparser] => Meek and Steindachner, 1928
                                [scientificName_author] => Achirus fluviatilis Meek and Steindachner, 1928
                                [scientificName_author_cleaned] => Achirus fluviatilis Meek and Steindachner, 1928
                            )
                            */
                        }
                        // if($i >= 10) break; //debug only
                    }
                }
            }
        }
        // exit("\n$tagged_file\n");
        fclose($WRITE);
    }
    private function is_a_rank_name($word)
    {
        $ranks = array('Kingdom', 'Phylum', 'Class', 'Order', 'Family', 'Genus', 'Tribe', 'Subgenus', 'Subtribe', 'Subfamily', 'Suborder', 'Subphylum', 'Subclass', 'Superfamily');
        foreach($ranks as $rank) {
            if(strtolower($rank) == strtolower($word)) return true;
        }
        return false;
    }
    private function clean_sciname($sciname)
    {   //"Navia acaulis Martius ex Schultes f." --> don't remove period (.)
        //"Navia acaulis Martius ex Schultes fff." -- remove period (.)
        $second_to_last_char = substr($sciname, strlen($sciname)-3, 1);
        if(substr($sciname, -1) == "." && $second_to_last_char != " ") $sciname = trim(substr($sciname, 0, strlen($sciname)-1)); //remove period if last char in name
        if(substr($sciname, -6) == ", USNM") $sciname = trim(substr($sciname, 0, strlen($sciname)-6));
        return $sciname;
    }
    private function clean_name($string)
    {
        $exclude = array("The ", "This "); //starts with these will be excluded, not a sciname
        foreach($exclude as $exc) {
            if(substr($string,0,strlen($exc)) == $exc) return false;
        }
        if(stripos($string, "...") !== false) return false; //string is found
        if(substr($string,0,3) == "s-*") $string = trim(substr($string,3,strlen($string)));
        if(substr($string,0,2) == "s-") $string = trim(substr($string,2,strlen($string)));
        if(substr($string,0,1) == "*") $string = trim(substr($string,1,strlen($string)));
        if(stripos($string, ", new species") !== false) {
            $string = trim(str_ireplace(", new species", "", $string));
        }
        if(stripos($string, ", new combination") !== false) {
            $string = trim(str_ireplace(", new combination", "", $string));
        }
        //for weird names, from Jen
        
        // Vriesia platynema Gaud. Bot. Voy. Bonite />/. (5(5. 1846
        $string = str_ireplace(array("/", "<", ">", "!", "|", "{", "}"), "", $string);
        $string = Functions::remove_whitespace($string);
        $string = str_replace("‘", "'", $string);
        $string = str_replace("’", "'", $string);
        return $string;
    }
    function run_GNRD_get_sciname_inXML($string)
    {   return false;
        exit("\n-----\n[$string]\nstop using 002\n");
        $string = str_replace(array("%", ">", "<"), "", $string);
        $url = $this->service['GNRD text input XML'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($xml_str = Functions::lookup_with_cache($url, $options)) { // echo "\n$xml_str\n";
            $xml = simplexml_load_string($xml_str);
            foreach($xml->names->name as $t) {
                // print_r($t); //good debug
                $t_dwc = $t->children("http://rs.tdwg.org/dwc/terms/");
                // if(stripos($string, "Sporobolus indicus") !== false) echo "\n-----\n11[".@$t_dwc->scientificName."]\n-----\n"; //string is found
                if($val = @$t_dwc->scientificName) return $val; //deliberately just gets the 1st one
            }
        }
        
        /* 3rd try - was never implemented yet. We'll see...
        $words = explode(" ", $string);
        if(count($words) >= 6) {
            $string = Functions::canonical_form($string);
            if($val = self::run_GNRD_get_sciname_inXML($string)) return $val;
        }
        */
        
        // if(stripos($string, "Sporobolus indicus") !== false) echo "\n-----\n22[$string]\n-----\n"; //string is found
        return false;
    }
    function run_GNRD($string)
    {
        if($string = self::clean_name($string)) {}
        else return false;
        
        //================================================================================================start gnfinder
        if($names = $this->get_names_from_gnfinder($string)) return $names;
        // 3rd try - New Sep 30, 2021
        $words = explode(" ", $string);
        if(count($words) >= 6) {
            $string2 = Functions::canonical_form($string);
            if($names = $this->get_names_from_gnfinder($string2)) return $names;
        }
        // another option - copied below when still using gnrd:
        if(!in_array($this->pdf_id, array('91225', '91362'))) return false;
        else { // per Jen: https://eol-jira.bibalex.org/browse/DATA-1890?focusedCommentId=66302&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66302
            /*
            llll => llii
            lll => ill
            lll => lli
            */
            if(stripos($string, "llll") !== false) { //string is found
                $string = str_ireplace("llll", "llii", $string);
                if($names = $this->get_names_from_gnfinder($string)) return $names;
            }
            elseif(stripos($string, "lll") !== false) { //string is found
                /* we have two tries here */
                //1st option
                $orig = $string;
                $string = str_ireplace("lll", "ill", $string);
                if($names = $this->get_names_from_gnfinder($string)) return $names;
                
                //2nd option
                $string = str_ireplace("lll", "lli", $orig);
                if($names = $this->get_names_from_gnfinder($string)) return $names;
            }
            /* and, where x is any consonant except l (letter el)
            xlx => xix
            xll => xil
            llx => lix
            */
            $orig = $string;
            $string = self::xlx_to_xix($string);
            if($names = $this->get_names_from_gnfinder($string)) return $names;
            
            $string = $orig;
            $string = self::xll_to_xil($string);
            if($names = $this->get_names_from_gnfinder($string)) return $names;

            $string = $orig;
            $string = self::llx_to_lix($string);
            if($names = $this->get_names_from_gnfinder($string)) return $names;
        }
        return false;
        exit("\nstop using 003\n");
        //================================================================================================end gnfinder
        
        $url = $this->service['GNRD text input'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            return $obj;
        }
        
        // /* 2nd try
        $string = str_replace(" ", "%20", $string);
        $url = $this->service['GNRD text input'].$string; //exit("\ngoes here [$string]...\n");
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            return $obj;
        }
        // */

        // /* 3rd try - New Sep 30, 2021
        $words = explode(" ", $string);
        if(count($words) >= 6) {
            $string2 = Functions::canonical_form($string);
            $url = $this->service['GNRD text input'].$string2; //exit("\ngoes here [$string]...\n");
            if($json = Functions::lookup_with_cache($url, $options)) {
                $obj = json_decode($json);
                return $obj;
            }
        }
        // */
        
        if(!in_array($this->pdf_id, array('91225', '91362'))) return false;
        else { // per Jen: https://eol-jira.bibalex.org/browse/DATA-1890?focusedCommentId=66302&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66302
            /*
            llll => llii
            lll => ill
            lll => lli
            */
            if(stripos($string, "llll") !== false) { //string is found
                $string = str_ireplace("llll", "llii", $string);
                if($ret = self::run_GNRD($string)) return $ret;
            }
            elseif(stripos($string, "lll") !== false) { //string is found
                /* we have two tries here */
                //1st option
                $orig = $string;
                $string = str_ireplace("lll", "ill", $string);
                if($ret = self::test_GNRD($string)) return $ret;
                //2nd option
                $string = str_ireplace("lll", "lli", $orig);
                if($ret = self::test_GNRD($string)) return $ret;
            }
            /* and, where x is any consonant except l (letter el)
            xlx => xix
            xll => xil
            llx => lix
            */
            $orig = $string;
            $string = self::xlx_to_xix($string);
            if($ret = self::test_GNRD($string)) return $ret;
            
            $string = $orig;
            $string = self::xll_to_xil($string);
            if($ret = self::test_GNRD($string)) return $ret;

            $string = $orig;
            $string = self::llx_to_lix($string);
            if($ret = self::test_GNRD($string)) return $ret;
        }
        return false;
    }
    private function test_GNRD($string)
    {   exit("\nstop using 004\n");
        $url = $this->service['GNRD text input'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            return $obj;
        }
    }
    // /*
    function run_gnparser($string)
    {
        if($string = self::clean_name($string)) {}
        else return false;
        $string = str_replace("/", "-", $string); //the former causes error
        $url = $this->service['GNParser'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json); // print_r($obj); //exit;
            return $obj;
        }
    }
    // */
    private function get_main_scinames_v2($filename) //get main 'headers for list type'
    {
        $local = $this->path['epub_output_txts_dir'].$filename;
        echo "\nprocessing: [$local]\n";

        // /* This is a different list of words from below. These rows can be removed from the final text blocks.
        $this->start_of_row_2_exclude = array("FIGURE", "Key to the", "Genus", "Family", "Subgenus", "Superfamily", "Subfamily",
        "? Subfamily");
        // */
        
        // /* This is a different list of words from above. These rows can be removed ONLY when hunting for the scinames.
        $exclude = array("*", "(", "Contents", "Literature", "Miscellaneous", "Introduction", "Appendix", "ACKNOWLEDGMENTS", "TERMINOLOGY",
        "ETYMOLOGY.", "TYPE-", 'Tribe');
        // */
        
        // /* loop text file
        $i = 0; $ctr = 0;
        foreach(new FileIterator($local) as $line => $row) { $ctr++;
            $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            $cont = true;
            // /* criteria 1, only for now
            if($row) {
                // if(stripos($row, "species list") !== false) echo "\n========\n1 $row\n=============\n"; //good debug
                
                // /* force include
                if(stripos($row, "Checklist of Amphibians") !== false           ||  //--> SCtZ-0010
                   stripos($row, "Creagrutus and Piabina species") !== false    ||  //--> SCtZ-0613
                   stripos($row, "Material Examined") !== false                 ||  //--> SCtZ-0609
                   stripos($row, "ADULT SYSTEMATIC TREATMENT") !== false          //--> 118237 - skipped list-type anyway
                   // stripos($row, "HOST-INDEX TO THE UREDINALES") !== false          //--> 91225 --- was never used at all
                  ) {
                    $rows[] = $row;
                    $rows = self::process_magic_no_v2($this->magic_no, $rows, $ctr);
                    continue;
                }
                // */
                
                if(stripos($row, "List of Participants") !== false) { $rows = array(); continue; } //string is found
                
                if(stripos($row, "list ") !== false) { //string is found
                    if(stripos($row, "Appendix") !== false)         { $rows = array(); continue; } //e.g. "Appendix A. List of specimen sightings and collections."
                    elseif(stripos($row, "see page") !== false)     { $rows = array(); continue; } //2nd repo - scb-0002
                    else                                            {} //proceeding OK...
                }
                elseif(stripos($row, "species list") !== false) {} //string is found //120083
                else { $rows = array(); continue; }
                // if(stripos($row, "species list") !== false) echo "\n========\n2 $row\n=============\n"; //good debug
            }
            // */
            $rows[] = $row;
            $rows = self::process_magic_no_v2($this->magic_no, $rows, $ctr);
        }
        // */
    }
    private function process_magic_no_v2($magic_no, $rows, $ctr)
    {
        if($magic_no == 5) {
            if(count($rows) == 5) { //start evaluating records of 5 rows
                if(!$rows[0] && !$rows[1] && !$rows[3] && !$rows[4]) {
                    if($rows[2]) {
                        $words = explode(" ", $rows[2]);
                        if(count($words) <= 12)  { //12 suggested by Jen
                            if(self::is_valid_list_header($rows[2])) {
                                if($GLOBALS["ENV_DEBUG"]) print_r($rows);
                                $this->scinames[$rows[2]] = ''; //for reporting
                                $this->lines_to_tag[$ctr-2] = '';
                            }
                            // else exit("\neli 100\n");
                        }
                    }
                }
                array_shift($rows); //remove 1st element, once it reaches 5 rows.
            }
            return $rows;
        }
        
        if($magic_no == 3) {
            if(count($rows) == 3) { //start evaluating records of 5 rows
                if(!$rows[0] && !$rows[2]) {
                    if($rows[1]) {
                        $words = explode(" ", $rows[1]);
                        if(count($words) <= 25)  { //orig is 6
                            if(self::is_valid_list_header($rows[1])) {
                                if($GLOBALS["ENV_DEBUG"]) print_r($rows);
                                $this->scinames[$rows[1]] = ''; //for reporting
                                $this->lines_to_tag[$ctr-1] = '';
                            }
                        }
                    }
                }
                array_shift($rows); //remove 1st element, once it reaches 5 rows.
            }
            return $rows;
        }
    }
    private function add_taxon_tags_to_text_file_LT($filename)
    {   //exit("\n[$filename]\n"); [SCtZ-0018.txt]
        echo "\nadd_taxon_tags_to_text_file_LT()...\n";
        $pdf_id = pathinfo($filename, PATHINFO_FILENAME); // exit("\n[$pdf_id]\n"); e.g. SCtZ-0609
        
        $local = $this->path['epub_output_txts_dir'].$filename;
        $temp_file = $local.".tmp";
        $edited_file = str_replace(".txt", "_edited_LT.txt", $local);
        copy($local, $edited_file);
        
        $WRITE = fopen($temp_file, "w"); //initialize
        $hits = 0;
        
        // /* loop text file
        $i = 0;
        foreach(new FileIterator($edited_file) as $line => $row) { $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            if(isset($this->lines_to_tag[$i])) { $hits++;
                $row = self::format_row_to_ListHeader($row);
                if($hits == 1)  $row = "<taxon sciname='$row'> ".$row;
                else            $row = "</taxon><taxon sciname='$row'> ".$row;
                // exit("\ngot one finally\n".$row."\n");
            }
            // else echo "\n[$row]\n";

            // /* to close tag the last block
            // if($pdf_id == '91225') { //manual specific --- was never used at all
            //     if($row == "New York Botanical Garden Libra") $row = "</taxon>$row";
            // }
            if($row == "Appendix") $row = "</taxon>$row";                   //SCtZ-0293.txt
            elseif($row == "References") $row = "</taxon>$row";             //SCtZ-0008.txt
            elseif($row == "General Conclusions") $row = "</taxon>$row";    //SCtZ-0029.txt
            elseif($row == "Bibliography") $row = "</taxon>$row";           //SCtZ-0011.txt
            elseif($row == "Key") $row = "</taxon>$row";                    //120083 7th doc
            
            if($pdf_id != 'SCtZ-0018') { //manual specific
                if($row == "Literature Cited") $row = "</taxon>$row";       //SCtZ-0007.txt
            }
            else {
                if($row == "Braun, Annette F.") $row = "</taxon>$row";      //SCtZ-0018.txt
            }
            if($pdf_id == 'SCtZ-0609') {
                if($row == "Figures") $row = "</taxon>$row";                //SCtZ-0609.txt
            }
            if($pdf_id == 'SCtZ-0613') {
                if($row == "ACKNOWLEDGMENTS") $row = "</taxon>$row";        //SCtZ-0613.txt
            }
            if($pdf_id == '118237') { //skipped list-type anyway
                if($row == "Spodoptera Guenee") $row = "</taxon>$row";        //118237.txt
            }
            // */
            // echo "\n$row";
            fwrite($WRITE, $row."\n");
        }//end loop text
        fclose($WRITE);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
        
        // print_r($this->lines_to_tag);
        echo "\n-end-\n";
        return $edited_file;
    }
    private function format_row_to_ListHeader($row)
    {   //e.g. "9. Annotated list of..." to "Annotated list of..."    //number infront removed
        /* old, not even good...
        $words = explode(" ", $row); // print_r($words); exit;
        if(substr($words[0], -1) == ".") {
            $tmp = str_replace(".", "", $words[0]);
            if(is_numeric($tmp)) array_shift($words);
            // print_r($words); exit("\nditox\n");
            return implode(" ", $words);
        }
        */
        $row = $this->remove_first_word_if_it_has_number($row);
        return $row;
    }
    private function remove_some_rows_LT($edited_file)
    {   echo "\nremove_some_rows_LT()...looping [$edited_file]\n";
        // exit("\nxxx[$edited_file]\n"); //e.g. /Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0018/SCtZ-0018_edited_LT.txt
        $local = $edited_file;
        $temp_file = $local.".tmp";
        $WRITE = fopen($temp_file, "w"); //initialize
        
        // /* This is a different list of words from above. These rows can be removed from the final text blocks.
        $exclude = $this->start_of_row_2_exclude;
        // */
        
        // /* loop text file
        $i = 0;
        foreach(new FileIterator($local) as $line => $row) { $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            
            /* criteria 1
            $cont = true;
            $exclude = array_merge($exclude, array("(", "Order ", "Family ", "Genus "));
            foreach($exclude as $start_of_row) {
                $len = strlen($start_of_row);
                if(substr($row,0,$len) == $start_of_row) {
                    $cont = false;
                    break;
                }
            }
            if(!$cont) continue;
            */
            
            // /* criteria 2: if first word is all caps e.g. ABSTRACT
            if($row) {
                if(stripos($row, "<taxon") !== false) {fwrite($WRITE, $row."\n"); continue;} //string is found
                if(stripos($row, "</taxon") !== false) {fwrite($WRITE, $row."\n"); continue;} //string is found
                
                // /* first word is all caps removed: OK
                $words = explode(" ", $row);
                $words = array_map('trim', $words); // print_r($words); //exit;
                if(ctype_upper($words[0]) && strlen($words[0]) > 1) continue;
                // */
                
                // /* 0018
                // Siskiwitia, new genus
                // alticolans, new species
                $row = str_ireplace(", new genus", "", $row);
                $row = str_ireplace(", new species", "", $row);
                // */
                
                if(is_numeric($row)) continue;
                if($row == "-") continue;
                if(is_numeric(substr($words[0],0,1))) continue; //e.g. table of contents section
            }
            // */
            fwrite($WRITE, $row."\n");
        }//end loop text
        fclose($WRITE);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
        echo "\n-end-\n";
    }
    private function is_valid_list_header($row)
    {
        if(stripos($row, "list") !== false) return true; //string is found
        elseif($row == "Creagrutus and Piabina species") return true;           //SCtZ-0613
        elseif($row == "Material Examined") return true;
        elseif($row == "ADULT SYSTEMATIC TREATMENT") return true;    //118237 - skipped list-type anyway
        else return false;
    }
    private function show_parsed_texts_for_mining_LT($edited_file)
    {
        $with_blocks_file = str_replace("_edited_LT.txt", "_tagged_LT.txt", $edited_file);
        $WRITE = fopen($with_blocks_file, "w"); //initialize
        $contents = file_get_contents($edited_file);
        if(preg_match_all("/<taxon (.*?)<\/taxon>/ims", $contents, $a)) { // print_r($a[1]);
            foreach($a[1] as $block) {
                $rows = explode("\n", $block);
                if(true) {
                    /* copied template
                    $last_sections_2b_removed = array("REMARKS.—", "REMARK.—", "AFFINITIES.—", "DISCUSSION.—", "NOTE.—", "NOTES.—");
                    $block = self::remove_last_sections($last_sections_2b_removed, $block);
                    */
                    $show = "\n-----------------------\n<$block</sciname>\n-----------------------\n";
                    /* copied template
                    if(self::is_valid_block("<$block</sciname>")) fwrite($WRITE, $show);
                    // else echo " -- not valid block"; //just debug
                    */
                    fwrite($WRITE, $show);
                }
            }
        }
        fclose($WRITE);
        echo "\nblocks: ".count($a[1])."\n";
        return $with_blocks_file;
    }
    function last_resort_to_clean_name($sciname_line, $WRITE_st) //this started from a copied template
    {
        if($this->resource_name == 'Kubitzki') { // exit("\n[$sciname_line]\nelix\n");
            // if(stripos($sciname_line, $this->in_question) !== false) exit("\n[$sciname_line]ee1\n"); //good debug - to see what string passes here.
            $words = explode(" ", trim($sciname_line)); //at this point value is "Euploca Nutt." OR "Tribe Aristolochieae". No more numeric/roman 1st part
            $first = $words[0];
            $second = @$words[1];
            if(in_array(ucfirst(strtolower($first)), $this->Kubitzki_intermediate_ranks) && $second) return $second; //for start pattern e.g. "2. Tribe Aristolochieae"
            return $words[0]; //for first 2 orig Start patterns: "3. Euploca Nutt." AND "Eucommiaceae"
        }
        
        if($this->resource_name == 'all_BHL') {
            // $words = explode(" ", $sciname_line);
            // if(count($words) >= 6) $sciname_line = Functions::canonical_form($sciname_line);
            
            // Sphagnum angstromii Hartm. f. ; Hartm. Skand. Fl
            // Sphagnum tabulate Sull. Musci Allegh. i'^- ; . 1845
            
            // Sphagnum angstromii Hartm. f. ; Hartm. Skand. Fl
            // Sphagnum tabulate Sull. Musci Allegh. i'^- ; . 1845
            
            // if(stripos($sciname_line, $this->in_question) !== false) exit("\n[$sciname_line]xx1\n"); //good debug - to see what string passes here.
            
            // manual: from 91155_source_taxa.txt
            if    (stripos($sciname_line, "Sphagnum angstromii Hartm. f.") !== false)         return Functions::canonical_form($sciname_line); //string is found
            elseif(stripos($sciname_line, "Sphagnum tabulate Sull. Musci Allegh.") !== false) return Functions::canonical_form($sciname_line); //string is found
        }
        
        $sciname_line = str_replace("*", "", $sciname_line);
        // /* manual 
        if($this->pdf_id == '119520') {
            if($sciname_line == 'Hypolimnas (Hypolimnas) salmicis (Drury)') return 'Hypolimnas salmicis'; //GNRD incorrectly outputs wrong name
            $sciname_line = str_ireplace("Iolaus (Epamera moyambina Stempffer and Bennett", "Iolaus (Epamera) moyambina Stempffer and Bennett", $sciname_line);
        }
        if($this->pdf_id == '119188') {
            if($sciname_line == 'Anoplius (Anoplius) toluco (Cameron)') return 'Anoplius toluco'; //GNRD incorrectly outputs wrong name
            $sciname_line = str_ireplace("{Hesperopompilus)", "(Hesperopompilus)", $sciname_line);
        }
        // */
        
        if(in_array($this->pdf_id, array('91225', '91362'))) {
            //1st case --- e.g. "Paspalum urvillel (P. velutinum)" or "Euchlaena mexlcana {E. luxurians)"
            $sciname_line = str_replace("{", "(", $sciname_line);
            $sciname_line = str_replace("}", ")", $sciname_line);
            $parts = explode("(", $sciname_line);
            $sciname_line = trim($parts[0]);
            //2nd case --- e.g. "Vlcia americana ; V. carollniana ; V. orcgona ;"
            $words = explode(";", $sciname_line);
            $sciname_line = trim($words[0]);
        }
        
        // /* Tiphia (Tiphia) intermedia Malloch
        $words = explode(" ", $sciname_line);
        if(count($words) >= 3) {
            if($words[1] == "(".$words[0].")") {
                if(in_array($this->pdf_id, array('119520', '119188'))) {}
                else { //the rest goes here
                    if(ctype_lower(substr($words[2],0,1))) return $sciname_line;
                }
            }
        }
        // */
        
        // /* manual adjustment
        if($sciname_line == "Megapodius molistructor") return $sciname_line;
        if(stripos($sciname_line, "Eunice segregate (Chamberlin, 1919a) restricted") !== false) return "Eunice segregate (Chamberlin, 1919a)";
        if($this->pdf_id == '91155') {
            $sciname_line = str_ireplace("nitidulusSchimp", "nitidulus Schimp", $sciname_line);
            $sciname_line = str_ireplace("tenellumPers", "tenellum Pers", $sciname_line);
        }
        $sciname_line = str_ireplace("'i^", "", $sciname_line); //30354
        $sciname_line = str_ireplace("Eurycotis bioUeyi Rehn", "Eurycotis biolleyi Rehn", $sciname_line); //30354
        // */
        // if(stripos($sciname_line, $this->in_question) !== false) exit("\n[$sciname_line]xx1\n"); //good debug - to see what string passes here.
        
        /*
        study this block further...
        if($numbers = $this->get_numbers_from_string($sciname_line)) { //if there is a single digit or 2-digit or 3-digit number in string then proceed to clean.
            foreach($numbers as $num) {
                if(strlen($num) <= 3) {break;}
            }
        }
        */
        
        // if(stripos($sciname_line, $this->in_question) !== false) exit("\n[$sciname_line]xx2\n"); //good debug - to see what string passes here.
        //Carabus bipustidatus Fab., Carabus crux-minor Oliv., and Carabus peltatus
        
        // /* ------------- last name cleaning ------------- use both gnparser and GNRD
        $orig = $sciname_line;
        if(substr($sciname_line,0,1) == "*") $sciname_line = trim(substr($sciname_line,1,strlen($sciname_line)));
        $sciname_line = str_ireplace("†","",$sciname_line); //special chars like this messes up GNRD and Gnparser
        $sciname_line = str_replace(".—", " .— ", $sciname_line);
        $sciname_line = Functions::remove_whitespace($sciname_line);
        /* might be overkill
        if($obj = self::run_gnparser($sciname_line)) $rek['normalized gnparser'] = @$obj[0]->normalized;
        */
        $sciname_line = str_replace(",", " , ", $sciname_line);
        $sciname_line = str_replace(":", " : ", $sciname_line);
        $sciname_line = str_replace(";", " ; ", $sciname_line);
        $sciname_line = trim(Functions::remove_whitespace($sciname_line));
        $sciname_line = str_replace('"', "&quot;", $sciname_line);

        // if(stripos($orig, $this->in_question) !== false) exit("\n[$sciname_line]xx3\n"); //good debug - to see what string passes here.
        if(in_array($this->pdf_id, array("91225", "91362", "91362_species")) || $this->resource_name == 'all_BHL' ) { //host-pathogen list pattern
            // return $sciname_line; //SPECIAL CASE -> to avoid GNRD call --- host-pathogen list pattern
            $words = explode(" ", $sciname_line);
            $words[1] = strtolower(@$words[1]); //2nd word set to small caps
            $sciname_line = trim(implode(" ", $words));
        }
        
        debug("\nrun_GNRD 1: [$sciname_line]\n");
        $obj = self::run_GNRD($sciname_line);
        if($this->resource_name == "MotAES") { //exclude rows with multiple binomials
            // if(count(@$obj->names) > 1) { //GNRD OBSOLETE
            if(count($obj) > 1) {
                /* good debug
                if(stripos($orig, $this->in_question) !== false) {
                    print_r($obj); //GNRD OBSOLETE $obj->names
                    exit("\n[$sciname_line]\n");
                }
                */
                
                // /* first criteria to be false is that there is > 1 binomial
                if(self::more_than_one_binomial($obj)) { //GNRD OBSOLETE $obj->names
                    echo "\nGNRD sees multiple binomials: [$sciname_line]\n"; print_r($obj); //exit;
                    return false;
                }
                // */
                
                // $verbatim_1 = $obj->names[0]->verbatim; //GNRD OBSOLETE
                // $verbatim_2 = $obj->names[1]->verbatim; //GNRD OBSOLETE
                $verbatim_1 = ''; //can't think of a counterpart in gnfinder
                $verbatim_2 = ''; //can't think of a counterpart in gnfinder
                if(stripos($verbatim_1, $verbatim_2) !== false) {
                    //echo "\ncheck ditox: [$sciname_line]\n";
                } //string is found //e.g. "Aeshna (Hesperaeschna) psilus"
                else {
                    // $scientificName_1 = $obj->names[0]->scientificName; //GNRD OBSOLETE
                    // $scientificName_2 = $obj->names[1]->scientificName; //GNRD OBSOLETE
                    $scientificName_1 = $obj[0];
                    $scientificName_2 = $obj[1];
                    //e.g. http://gnrd.globalnames.org/name_finder.json?text=Spialia ploetzi (Aurivillius)
                    if(self::is_2or_more_words($scientificName_1) && self::is_just_one_word($scientificName_2)) {}
                    else {
                        echo "\nGNRD sees multiple names: [$sciname_line][$scientificName_1][$scientificName_2]\n";
                        // print_r($obj->names); //good debug
                        return false;
                    }
                }
            }
        }
        // if($sciname = @$obj->names[0]->scientificName) { //GNRD OBSOLETE
        if($sciname = @$obj[0]) {
            /* new: to remedy those captured monomials - WON'T WORK HERE, SINCE STRTOLOWER() WAS ALREADY APPLIED TO 2ND WORD
            WAS IMPLEMENTED CORRECTLY ELSEWHERE...
            if(self::is_just_one_word($sciname)) {
                $sciname_line = $this->change_U_to_ll_caused_by_OCR($orig); //e.g. Bruchia longicoUis
                $obj = self::run_GNRD($sciname_line);
                $sciname = @$obj->names[0]->scientificName;
            }*/
        }
        else {
            if($sciname = $this->run_GNRD_get_sciname_inXML($sciname_line)) {}
            else {
                if(!in_array($this->pdf_id, array("91225", "91362"))) echo "\nGNRD doesn't recognize [$sciname_line]xxx\n";
                fwrite($WRITE_st, $sciname_line."\n");
                // /* New: Sep 28, 2011 - DATA-1891: customized - resource was INVESTIGATED (xxx_source_taxa.txt) and sciname_line is acceptable.
                if(in_array($this->pdf_id, array("118935", "30355"))) return $sciname_line;
                else return "GNRD does not recognize name"; //false; //rest goes here
                // */
            }
        }
        // if(stripos($orig, $this->in_question) !== false) exit("\n[$sciname][$sciname_line]xx4\n"); //good debug - to see what string passes here.
        if(self::is_just_one_word($sciname)) {  //false; //exclude if sciname is just one word, it is implied that it should be a binomial
            // exit("\n[$sciname] is a minomial\n");
            
            // /* to remedy list of monomials generated by gnfinder even if there is a binomial in the string. e.g. 15422 BHL
            if($val = $this->get_binomial_or_tri($sciname_line)) return $val;
            // */
            
            // /* New: Sep 28, 2011 - when working on DATA-1891
            $this->debug['monomial'][$sciname_line][$sciname] = Functions::canonical_form($sciname_line); //good debug for 118935
            $words = explode(" ", $sciname_line);
            if(count($words) >= 6) $sciname_line = Functions::canonical_form($sciname_line);
            //---start--- customized - resource was INVESTIGATED and the monomials by GNRD can be accepted as binomials
            if(in_array($this->pdf_id, array("118935", "120083", "118237", "30355"))) return $sciname_line; //Memoirs of the American Entomological Society (DATA-1887)
            elseif(in_array($this->pdf_id, array("15423", "91155", "15427", "91144", "91362_species"))) return $sciname_line; //North American Flora (DATA-1890) --- BHL
                                                //excluded: 15428(not all) 91225(assoc type) 91362(assoc type)
            else return "monomial"; //rest goes here
            //---end---
            // */
        }
        // if(stripos($orig, $this->in_question) !== false) exit("\n[$sciname][$sciname_line]xx4a\n"); //good debug - to see what string passes here.
        
        if(in_array($this->pdf_id, array("30353", "30354"))) $criteria = $sciname && self::binomial_or_more($sciname); //resources to be skipped more or less
        else                                        $criteria = $sciname; //rest of the resources, default
        if($criteria) {
            // if(stripos($orig, $this->in_question) !== false) exit("\n[$sciname][$sciname_line]xx4b\n"); //good debug - to see what string passes here.
            
            $rek['sciname GNRD'] = $sciname;
            if($this->resource_name == 'all_BHL' || in_array($this->pdf_id, array('15423', '91155', '15427'))) { //BHL --- more strict path
                /* might be overkill
                if($obj = self::run_gnparser($sciname_line)) {
                    $authorship = @$obj[0]->authorship->verbatim;
                    $rek['authorship gnparser'] = $authorship;
                    $rek['scientificName_author'] = trim("$sciname $authorship");
                    $rek['scientificName_author_cleaned'] = self::clean_sciname($rek['scientificName_author']);
                }
                if(!@$rek['scientificName_author_cleaned']) $rek['scientificName_author_cleaned'] = $rek['sciname GNRD']; //reconcile gnparser vs GNRD
                */
                if($obj = self::run_gnparser($sciname_line)) {
                    $canonical = @$obj[0]->canonical->full;
                    if($canonical && self::is_2or_more_words($canonical)) {
                        $rek['scientificName_author_cleaned'] = $canonical;
                    }
                    else $rek['scientificName_author_cleaned'] = $rek['sciname GNRD'];
                }
                else $rek['scientificName_author_cleaned'] = $rek['sciname GNRD'];
                /*
                if(stripos($orig, $this->in_question) !== false) { //good debug - to see what string passes here.
                    print_r($rek); exit("\n[$sciname][$sciname_line]xx4a\n");
                }
                */
            }
            else { // --- not strict at all
                $rek['scientificName_author_cleaned'] = $sciname;
            }
        }
        else {
            echo("\nNot sciname says GNRD 1: [$sciname_line]\n"); //e.g. "Exotylus cultus Davis" from 118935.txt
            return false;
        }
        // ------------- end ------------- */
        // if(stripos($orig, $this->in_question) !== false) exit("\n[$sciname][$sciname_line]xx5\n"); //good debug - to see what string passes here.
        
        if($ret = @$rek['scientificName_author_cleaned']) {
            $ret = str_replace(" ,", ",", $ret);
            $ret = str_replace(" :", ":", $ret);
            $ret = str_replace(" ;", ";", $ret);
            $ret = trim(Functions::remove_whitespace($ret));
            // /*
            $words = explode(" ", $ret);
            if(substr($words[0],-1) == ".") return false; //first word, last char must not be period e.g. "G. morhua"
            // */
            // if(stripos($orig, $this->in_question) !== false) exit("\n[$sciname][$ret]xx6\n"); //good debug - to see what string passes here.
            return $ret;
        }
        // if(stripos($orig, $this->in_question) !== false) exit("\n[$sciname][$orig]xx7\n"); //good debug - to see what string passes here.
        return $orig;
    }
    /*################################## Jen's utility ################################################################################*/
    function is_title_inside_epub_YN($title, $txtfile)
    {   // exit("\n$title\n$txtfile\n");
        $title .= '';
        $ret = self::check_title($txtfile, $title);
        if(!$ret['found']) {
            // echo "\nTITLE NOT FOUND\n---------------epub content\n".$ret['ten_rows']."\n---------------\n";
            return false;
        }
        else return true; //exit("\ntitle found\n");
    }
    private function check_title($txtfile, $title)
    {
        $i = 0; $final = "";
        foreach(new FileIterator($txtfile) as $line => $row) { $i++;
            $row = trim($row). "\n";
            $final .= $row;
            if($i >= 100) break;
        }
        /* title from epub file */
        $final = trim($final);
        $final = str_replace(array("/"), "", $final); //manual
        $final = str_ireplace("–", "-", $final); //manual
        $final = str_ireplace("Leafmining", "Leaf Mining", $final); //manual
        $final = str_ireplace("—", "-", $final); //manual
        $final = str_ireplace("á", "a", $final); //manual
        $final = str_ireplace("è", "e", $final); //manual
        //$final = str_ireplace("é", "e", $final); //manual
        // $final = str_ireplace("Catalog of Type", "Catalogue of Type", $final); //manual
        
        /* title from repository page */
        $title = self::get_first_8_words($title);
        $title = str_ireplace("Caddisflies ", "Caddisflies, ", $title); //manual
        $title = str_ireplace("Caddiflies", "Caddisflies", $title); //manual
        $title = str_ireplace("Solencera", "Solenocera", $title); //manual
        $title = str_ireplace("Indo- West", "Indo-West", $title); //manual
        $title = str_ireplace("Ostariophysi:Siluroidei", "Ostariophysi: Siluroidei", $title); //manual
        $title = str_ireplace("Echinodermata:Asteroidea", "Echinodermata: Asteroidea", $title); //manual
        $title = str_ireplace(" : ", ": ", $title); //manual
        $title = str_ireplace("--", "-", $title); //manual
        $title = str_ireplace("á", "a", $title); //manual
        $title = str_ireplace("BenthIdi", "Benthédi", $title); //manual

        if(stripos($final, $title) !== false) { //string is found
            return array("found" => true);
        }
        else {
            /* will review again
            if(self::meet_case_1($title, $final)) return array("found" => true);
            else return array("found" => false, "ten_rows" => $final);
            */
            return array("found" => false, "ten_rows" => $final);
        }
    }
    private function meet_case_1($repo, $epub)
    {   // repo -> Revision of the clearwing moth genus Osminia (Lepidoptera, Sesiidae)
        // epub -> Revision of the Clearwing Moth Genus Osminia (Lepidoptera: Sesiidae)
        if(preg_match("/\((.*?)\)/ims", $repo, $a1)) {
            if(preg_match("/\((.*?)\)/ims", $epub, $a2)) {
                $from_repo = str_replace(",", ":", $a1[1]);
                $repo = str_replace($a1[1], $from_repo, $repo);
                if(stripos($epub, $repo) !== false) return true;
                else return false;
            }
        }
    }
    private function get_first_8_words($title)
    {
        $a = explode(" ", $title);
        return trim(implode(" ", array($a[0], $a[1], $a[2], @$a[3], @$a[4], @$a[5], @$a[6], @$a[7])));
    }
    function one_word_and_higher_taxon($row)
    {
        if(stripos($row, "taxon>") !== false) return false; //string is found
        $words = explode(" ", $row); //print_r($words);
        if(count($words) == 1) {
            if(is_numeric($row)) return false;
            if(!ctype_alpha($row)) return false;
            if(stripos($row, ".—") !== false) return false; //string is found
            if(stripos($row, ",") !== false) return false; //string is found
            if(stripos($row, ":") !== false) return false; //string is found
            if(ctype_lower(substr($row,0,1))) return false; //first char must be all-caps
            if(self::is_a_sciname($row)) {
                if($GLOBALS["ENV_DEBUG"]) echo "\none_word_and_higher_taxon: [$row]\n";
                return true;
            }
        }
        return false;
    }
    private function is_a_sciname($str)
    {
        if(strlen($str) == 1) return false;                 //must be longer than 1 char
        if(!ctype_alpha($str)) return false;                //must be all letters
        if(ctype_lower(substr($str,0,1))) return false;     //must be capitalized
        debug("\nrun_GNRD 2: [$str]\n");
        if($obj = self::run_GNRD($str)) {
            // if(strtolower($str) == strtolower(@$obj->names[0]->scientificName)) return true; //GNRD OBSOLETE
            if(strtolower($str) == strtolower(@$obj[0])) return true;
            else {
                if($this->pdf_id == '120602') { //force GNRD to say its a name
                    if(in_array($str, array("Oulopteryginae", "Corydini", "Tiviini", "Euthyrrhaphini", "Compsodini", "Panesthiini", "Diplopterini", "Blattini", "Nyctiborini", "Megaloblattini", "Perisphaerini", "Litopeltiini", "Brachycolini", "Blaberini", "Parcoblattini", "Euphyllodromiini", "Euandroblattini", "Neoblattellini", "Pseudomopini", "Supellini", "Symplocini", "Baltini", "Ectobiini", "Chorisoneurini", "Anaplectini", "Ceuthobiini", "Oulopterygini", "Corydiini", "ElTTHYRRHAPHINAE", "Litopeltini", "Euphyllodromini", "Ectobhnae"))) return true;
                }
                if($this->pdf_id == '119520') { //force GNRD to say its a name
                    if(in_array($str, array("Rhodocerini", "Acraeinae", "Limenitini", "Marpesini", "Eunicini", "Liptenidae", "Pentilinae", "Lipteninae", "Epitolini", "Liphyridae", "Liphyrinae", "Lachnocnemini", "Gerydinae", "Gerydini", "Plebejinae", "Lampidini", "Everini", "Plebejini", "Zizeerini"))) return true;
                }

                if(!isset($this->investigate_2[$str])) {
                    echo "\nNot sciname says GNRD 2: [$str]\n";
                    $this->investigate_2[$str] = '';
                }
            }
        }
        return false;
    }
    function two_words_rank_and_sciname_combo($row)
    {
        if(stripos($row, "taxon>") !== false) return false; //string is found
        $ranks = array('Kingdom', 'Phylum', 'Class', 'Order', 'Family', 'Genus', 'Tribe', 'Subgenus', 'Subtribe', 'Subfamily', 'Suborder', 
        'Subphylum', 'Subclass', 'Superfamily');
        $words = explode(" ", $row); //print_r($words);
        if(count($words) == 2) {
            if(stripos($row, ",") !== false) return false; //string is found
            if(stripos($row, ":") !== false) return false; //string is found

            if(ctype_lower(substr($words[0],0,1))) return false;
            if(ctype_lower(substr($words[1],0,1))) return false;
            
            if(is_numeric($words[0])) return false;
            if(is_numeric($words[1])) return false;
            
            if(in_array($words[0], $ranks) && self::is_a_sciname($words[1])) {
                if($GLOBALS["ENV_DEBUG"]) echo "\ntwo_words_rank_and_sciname_combo: [$row]\n";
                return true;
            }
        }
    }
    function sciname_then_specific_words($row, $phrase)
    {   // e.g. "Isopterygium Excluded Taxa"
        if(stripos($row, $phrase) !== false) {  //string is found
            $words = explode(" ", $row); //print_r($words);
            if(self::is_a_sciname($words[0])) {
                if($GLOBALS["ENV_DEBUG"]) echo "\nsciname_then_specific_words: [".$row."]\n";
                return true;
            }
        }
        return false;
    }
    function numbered_then_sciname($row)
    {   // e.g. "2. Elmeriobryum Broth."
        $words = explode(" ", $row);
        if(count($words) > 15) return false;
        if(is_numeric($words[0])) {             //1st word is numeric
            if($third_word = @$words[2]) {      //if there is a 3rd word
                if(!self::is_capitalized($third_word)) return false;  //if there is a 3rd word, it must be capitalized
            }
            if(self::is_a_sciname(@$words[1])) { //echo "\n-[$row]-\n";
                if($GLOBALS["ENV_DEBUG"]) echo "\nnumbered_then_sciname: [".$row."]\n"; //2nd word is a sciname
                return true;
            }
        }
        return false;
    }
    private function is_capitalized($str)
    {
        $first_char = substr($str,0,1);
        if(ctype_upper($first_char)) return true;
        return false;
    }
    function is_sciname_in_15423($string)
    {   /*
        1. Sphaerocarpos texanus Aust. Bull. Torrey Club 6: 158. 1877.
        7. Riccia Curtisii T. P, James; (Aust. Proc. Acad. Phila. 1869: 231, 
        (1) Coelopoeta glutinosi Walsingham (Figs. 1, 2, 55, 55a, 55b, 101.) --- 118950
        */
        // /* manual adjustment
        $string = str_ireplace("Riccia Frostii", "Riccia frostii", $string);
        $string = str_ireplace("Sphaerocarpos Donnellii", "Sphaerocarpos donnellii", $string);
        $string = str_ireplace("SuUivantii", "sullivantii", $string);
        $string = str_ireplace("Dussiana", "dussiana", $string);
        $string = str_ireplace("Lindenbergiana", "lindenbergiana", $string);
        $string = str_ireplace("Bolanderi", "bolanderi", $string);
        $string = str_ireplace("Grimaldia calif omica", "Grimaldia californica", $string);
        // */
        
        $str = trim($string);
        $words = explode(" ", $str);

        /*
        if(stripos($str, $this->in_question) !== false) { //string is found
            echo "\n[$str]\n"; exit("\nreaches here 0\n[$str]\n");
        } 
        */
        
        // if(count($words) > 6) return false;
        if(@$words[0][1] == ";") return false; //2nd char is
        if(@$words[0][1] == ",") return false; //2nd char is
        if(@$words[0][0] == ".") return false; //1st char is
        
        if(stripos($str, "NORTH AMERICAN FLORA [V") !== false) return false; //string is found
        
        /* e.g. Subgenital plate broadly rounded (17) => not a species -- copied template
        if(preg_match("/\((.*?)\)/ims", $string, $arr)) if(is_numeric($arr[1])) return false;
        */

        /* anywhere in the string - copied template
        $exclude = array("(mm)", "%", "z g", " their", " uF", "Clcni", ".ics", " these", " for ", " only", "Snowf i eld", "Glacier", "Wyomi",
        "Co -", "Co.", " not ", " complex", "Tv. falayah", ".' /. szczytkoi Poulton", " page ", " mostly ", "annelides", " und ", " with ",
        "Scutellum small");
        foreach($exclude as $x) {
            if(stripos($string, $x) !== false) return false; //string is found
        }
        */
        
        // if(stripos($string, $this->in_question) !== false) exit("\nreaches here 1\n"); //string is found
        return self::is_sciname_in_118986($string);
    }
    function is_sciname_in_118920($string)
    {
        $str = trim($string);
        if($str == "Hosts and biology") return false;
        $words = explode(" ", $str);
        // if(count($words) > 6) return false; //orig
        if(count($words) > 15) return false; //new adapted when processing 27822.txt
        
        if(@$words[0][1] == ";") return false;
        if(@$words[1] == 'and') return false; //2nd word must not be this
        if(@$words[1] == 'subtree') return false; //2nd word must not be this
        if(end($words) == 'The') return false; //last word must not be this
        if(substr(@$words[1], -1) == '.') return false; //Tenthredinidae incl.
        
        // /* e.g. Subgenital plate broadly rounded (17) => not a species
        if(preg_match("/\((.*?)\)/ims", $string, $arr)) if(is_numeric($arr[1])) return false;
        // */
        
        // /* anywhere in the string
        $exclude = array("(mm)", "%", "z g", " their", " uF", "Clcni", ".ics", " these", " for ", " only", "Snowf i eld", "Glacier", "Wyomi",
        "Co -", "Co.", " not ", " complex", "Tv. falayah", ".' /. szczytkoi Poulton", " page ", " mostly ", "annelides", " und ", " with ",
        "Scutellum small", "£");
        foreach($exclude as $x) {
            if(stripos($string, $x) !== false) return false; //string is found
        }
        // */
        
        if($string == "An uregulai") return false;
        if($string == "Cascadoperla trictura") return false;
        // if(stripos($string, $this->in_question) !== false) exit("\nstopx 11 [$string]\n"); //string is found
        return self::is_sciname_in_118986($string);
    }
    function is_sciname_in_118986($string)
    {   /*
        Indies vacaensis 
        Laccophilus maculosus maculosus Say, new status 
        Laccophilus maculosus decipiens LeConte, new status 
        Laccophilus maculosus shermani Leech, new status 
        Laccophilus fasciatus fasciatus Aube, new synonymy and new status
        Laccophilus fasciatus rufus Melsheimer, restored name and new status 
        Coelopoeta glutinosi Walsingham (Figs. 1, 2, 55, 55a, 55b, 101.) --- 118950
        */
        if(stripos($string, " of ") !== false) return false; //doesn't have this char(s) e.g. Explanation of Figures 139

        /* format first: e.g. "Pegomyia palposa (Stein) (Figs. 1, 30, 54.)" --- copied template
        $string = trim(preg_replace('/\s*\(Fig[^)]*\)/', '', $string)); //remove Figs. parenthesis OK
        */
        
        $str = trim($string);
        $words = explode(" ", $str);
        // if(count($words) > 6) return false;

        // if(stripos($string, $this->in_question) !== false) exit("\nreaches here 2\n[$string]\n"); //string is found
        // /*
        $ret = self::shared_120082_118986($words, $str);
        if(!$ret) return false;
        // */

        // if(stripos($string, $this->in_question) !== false) exit("\nreaches here 3\n"); //string is found
        if($words[0] == 'Clypeal') return false;
        if($words[0] == 'Anal') return false;
        if($words[0] == 'Eyes') return false;
        if($words[0] == 'Labium') return false;
        if($words[1] == 'largely') return false; //Palpi largely yellow anabnormis Huckett
        if($words[0] == 'Number') return false; //"Number io"
        if($words[0] == 'Paregle') return false; //Genus starts with "Pegomyia"
        if($words[0] == 'Materials') return false;
        if($words[0] == 'Type') return false;
        
        if($this->pdf_id == '120083') {
            if(@$words[1][0] == "(") return false;
            if(trim($string) == "Males and Females") return false;
        }
        if(stripos($string, "Richland and") !== false) return false; //doesn't have this char(s) e.g. Richland and Bear Creeks
        return $string;
    }
    function is_sciname_in_120082($string)
    {   /*
        Aztecolus pablillo Chamberlin Figures 96, 110-118
        Spirobolus bungii Brandt Figures 4, 97, 127-131
        Spirobolus grahami, new species Figures 99, 133, 139-141, 152-160, 162, 164
        Spirobolus formosae, new species Figures 101, 135, 149-151, 163
        */
        if(stripos($string, " of ") !== false) return false; //doesn't have this char(s) e.g. Explanation of Figures 139
        if(stripos($string, "Hiltonius mimtis Chamberlin Ill") !== false) return false; //doesn't have this char(s)
        
        $left = 'Figures'; $right = '-eli_cha-';
        $string = self::remove_all_in_between_inclusive($left, $right, $string."-eli_cha-", true);
        $string = str_replace("-eli_cha-", "", $string);
        
        /* format first: e.g. "Gadus morhua (Figs. 1, 30, 54.)"
        $string = trim(preg_replace('/\s*\(Fig[^)]*\)/', '', $string)); //remove Figs. parenthesis OK
        */
        
        $str = trim($string);
        $words = explode(" ", $str);
        if(count($words) > 6) return false;
        // /*
        $ret = self::shared_120082_118986($words, $str);
        if(!$ret) return false;
        // */
        if($words[0] == 'Clypeal') return false;
        if($words[0] == 'Anal') return false;
        if($words[0] == 'Eyes') return false;
        if($words[0] == 'Labium') return false;
        if($words[1] == 'largely') return false; //Palpi largely yellow anabnormis Huckett
        if($words[0] == 'Number') return false; //"Number io"
        if($words[0] == 'Paregle') return false; //Genus starts with "Pegomyia"
        return $string;
    }
    private function shared_120082_118986($words, $str)
    {   //[(1) Coelopoeta glutinosi Walsingham (Figs. 1, 2, 55, 55a, 55b, 101.)] --- 118950
        
        // if(stripos($str, $this->in_question) !== false) exit("\nreaches here 3a\n[$str]\n"); //string is found
        if(strlen($str) <= 10) return false;
        if(count($words) < 2) return false;
        if(ctype_lower($words[0][0])) return false; //first word must be capitalized
        if($this->resource_name == 'all_BHL' || in_array($this->pdf_id, array('15423', '91155', '15427', '91225', '91362'))) {}
        else {
            if(ctype_upper($words[1][0])) return false; //2nd word must be lower case
        }
        
        // if(stripos($str, $this->in_question) !== false) exit("\nreaches here 3\n[$str]aaa\n"); //string is found
        if($words[0][0] == "(") return false; //must not start with this char(s) e.g. (Drawings by Frances A. McKittrick)
        if($words[0][0] == "'") return false; //must not start with this char(s) e.g. '- ■• '■
        
        // /* Important: added for production. Not detected in local
        if(stripos($str, "•") !== false) return false; //string is found
        if(stripos($str, ">n") !== false) return false; //string is found
        if(stripos($str, "■") !== false) return false; //string is found
        // */
        
        // /*
        $must_not_start_with_chars = array("<", ">", "_", "-", ",");
        foreach($must_not_start_with_chars as $char) {
            if($words[0][0] == "$char") return false; //must not start with this char(s)
        }
        // */
        // if(stripos($str, $this->in_question) !== false) exit("\nreaches here 3\n[$str]\n"); //string is found

        $first_word_must_not_be_these = array('On', 'Oh', 'Nm', 'Ov', '\or-', 'Indies');
        foreach($first_word_must_not_be_these as $char) {
            if($words[0] == "$char") return false; //must not start with this char(s)
        }

        if(@$words[0][1] == ".") return false; //2nd char must not be period (.) e.g. T. species?
        if(@$words[0][1] == '"') return false; //2nd char must not be period (") e.g. C"> ro r<->
        
        if(strlen($words[0]) == 1) return false; //e.g. O iH CVJ

        // if(stripos($str, $this->in_question) !== false) exit("\nreaches here 4a\n"); //string is found
        $dont_have_these_chars_anywhere = array("—", "~", "->", "<-", "«", "»", "©", " pp.", " ibid.", " of ", " is ", "(see", "species?", 
            "inquirendum");
        if($this->pdf_id == '120082') $dont_have_these_chars_anywhere[] = " and "; //4th doc
        if($this->pdf_id != '91365') $dont_have_these_chars_anywhere[] = " to ";
        
        if($this->resource_name == 'all_BHL' || in_array($this->pdf_id, array('15423', '91155', '15427'))) {} //1st 2nd, all BHL
        else {
            if($this->resource_name != "MotAES") $dont_have_these_chars_anywhere[] = "^";
            $dont_have_these_chars_anywhere = array_merge($dont_have_these_chars_anywhere, array("*", ":", " in ", " p."));
        }

        // 38. Sphagnum tenerum Sull. & Lesq.; Sull. in A. Gray, 
        // 39. Sphagnum tabulate Sull. Musci Allegh. i'*^-;. 1845.
        // 6. Bruchia Ravenelii Wilson; SuU. in A. Gray, Man.               got in 
        // 8. Bruchia brevifolia Sull. in A. Gray, Man. ed. 2. 617. 1856.   got in
        // 8. Ditrichum rufescens (Hampe) Broth, in E. & P. Nat. 
        // 1 . Seligeria campylopoda Kindb.; Macoun, Cat. Can. 
        
        // if(stripos($str, $this->in_question) !== false) exit("\ngoes here2...\n[$str]\n"); //string is found
        // Cryptocercus punctulatus Scudder (Plate X, figures 13 to 16.) 
        // Coelopoeta glutinosi Walsingham (Figs. 1, 2, 55, 55a, 55b, 101.) 

        /*
        if(stripos($str, $this->in_question) !== false) { //string is found
            print_r($dont_have_these_chars_anywhere);
            exit("\nreaches here 4b\n[$str]\n");
        }
        */

        foreach($dont_have_these_chars_anywhere as $char) {
            if(stripos($str, "$char") !== false) return false;
        }

        // if(stripos($str, $this->in_question) !== false) exit("\nreached here 4c\n[$str]\n"); //string is found
        // [Euphyllodromia decastigmata'i^ new species (Plate IV, figures 18 to 20.)]
        // Aeshna (Hesperaeschna) psilus PI. XL, fig. 531, PL XLI, figs. 539-554;

        if($this->get_numbers_from_string($words[0])) return false; //first word must not have a number
        if($this->get_numbers_from_string($words[1])) return false; //2nd word must not have a number
        // if(stripos($str, $this->in_question) !== false) exit("\nreaches here 4x\n[$str]\n"); //string is found
        if(in_array($this->pdf_id, array('119187'))) {} //Coryphaeschna luteipennis peninsularis Tables 8, 11, 13, 18; Map 7.
        elseif($this->resource_name == 'all_BHL') {} //15428.txt //4. Naias conferta A. Br. Sitz.-ber. Ges. Nat. Freunde Berlin 1868 : 17.
        else {
            if(self::last_word_not_num_not_LT_4_digits($words)) {}
            else return false;
        }
        // if(stripos($str, $this->in_question) !== false) exit("\nreaches here 4y\n"); //string is found
        return true;
    }
    public function last_word_not_num_not_LT_4_digits($words)
    {   // /* last word must not be a number with < 4 digits => e.g. "Second antennal segment extensively blackish 22"
        $last_word = end($words);
        if(is_numeric($last_word)) {
            if(strlen($last_word) < 4) return false;
        }
        // */
        return true;
    }
    public function remove_all_in_between_inclusive($left, $right, $html, $includeRight = true)
    {
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
            foreach($arr[1] as $str) {
                if($includeRight) { //original
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, '', $html);
                }
                else { //meaning exclude right
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, $right, $html);
                }
            }
        }
        return $html;
    }
    function str_begins_with($str, $substr)
    {
        if(substr($str,0,strlen($substr)) == $substr) return true;
        return false;
    }
    function row_where_all_words_have_max_three_chars($row)
    {
        $row = trim($row);
        $words = explode(" ", $row);
        foreach($words as $word) {
            if(strlen($word) > 4) return false;
        }
        return true;
    }
    function cutoff_source_text_file($filename, $end_str)
    {
        $new_filename = str_replace(".txt", "_cutoff.txt", $filename);
        $WRITE = fopen($this->path['epub_output_txts_dir'].$new_filename, "w"); //initialize
        $local = $this->path['epub_output_txts_dir'].$filename;
        // /* loop text file
        $i = 0; $saving = false;
        foreach(new FileIterator($local) as $line => $row) { $i++;
            if(($i % 5000) == 0) echo " $i";
            if(trim($row) == $end_str) $saving = true; //start saving
            if($saving) fwrite($WRITE, $row."\n");
        }
        // */
        fclose($WRITE);
        return $new_filename;
    }
    function binomial_or_more($sciname)
    {
        $words = explode(" ", trim($sciname));
        if(count($words) >= 2) return true;
        else return false;
    }
    function has_AA_BB_CC($contents)
    {
        $arr = array("AA.", "BB.", "CC.", "DD.", "EE.", "FF.", "GG.", "HH.", "II. Tegmina", "II. Size");
        foreach($arr as $letters) {
            if(strpos($contents, $letters) !== false) return true; //string is found
        }
        return false;
    }
    private function is_just_one_word($phrase)
    {
        $words = explode(" ", trim($phrase));
        if(count($words) == 1) return true;
        return false;
    }
    private function is_2or_more_words($phrase)
    {
        $words = explode(" ", trim($phrase));
        if(count($words) >= 2) return true;
        return false;
    }
    function is_a_Group_stop_pattern($row)
    {
        $words = explode(" ", $row);
        if($words[0] == 'Group') { //first word is 'Group'
            if(count($words) <= 3) return true;
        }
        
        // /* e.g. "polita group" 118978.txt
        if(count($words) == 2) {
            if(strtolower($words[1]) == 'group') return true; //2nd word is 'group'
        }
        // */
        
        // /* 2-3 words, where last word is 'group'
        // C. costatus group.
        // Purpuratus group
        // C. jurvus group.
        $row = str_replace(".", "", $row);
        $words = explode(" ", $row);
        if(count($words) <= 4) {
            $last_word = strtolower(end($words));
            $first_word = $words[0];
            if($this->first_char_is_capital($first_word) && $last_word == 'group') return true;
        }
        // */
        return false;
    }
    private function more_than_one_binomial($gnrd_arr)
    {   /* GNRD OBSOLETE Array(
        [0] => stdClass Object(
                [verbatim] => Carabus bipustidatus
                [scientificName] => Carabus bipustidatus
                [offsetStart] => 0
                [offsetEnd] => 20
        [1] => stdClass Object(
                [verbatim] => Carabus
                [scientificName] => Carabus
                [offsetStart] => 28
                [offsetEnd] => 35
        [2] => stdClass Object(
                [verbatim] => Carabus peltatus
                [scientificName] => Carabus peltatus
                [offsetStart] => 59
                [offsetEnd] => 75
        )*/
        /* from gnfinder: Array(
            [0] => Thalictroides
            [1] => Lates niloticus
            [2] => Calopogon
            [3] => Cymbidium pulchellum
            [4] => Conostylis americana
        )*/
        
        $binomials = 0;
        /* GNRD OBSOLETE
        foreach($gnrd_arr as $obj) {
            if(self::is_2or_more_words($obj->scientificName)) $binomials++;
        }
        */
        
        foreach($gnrd_arr as $sciname) {
            if(self::is_2or_more_words($sciname)) $binomials++;
        }
        if($binomials > 1) return true;
        return false;
    }
    function is_Section_stop_pattern($row) //Line of four words or less beginning "Section"
    {
        $words = explode(" ", $row);
        if(count($words) <= 4) {
            $orig = $words[0];
            $first_word = strtolower($words[0]);
            if($first_word == 'section') {
                if(ctype_upper(substr($orig,0,1))) return true;
            }
        }
        return false;
    }
    function is_New_then_RankName_stop_pattern($row)
    {   //e.g. "HYPOPHYTALA, new genus"
        $words = explode(" ", $row);
        if(count($words) <= 4) {
            $last_word = strtolower(end($words));
            if($this->is_a_rank_name($last_word)) {
                $total = count($words);
                $second_to_last_word = strtolower(@$words[$total-2]);
                if($second_to_last_word == 'new') return true;
            }
        }
        return false;
    }
    function if_Illustration_row($row)
    {   /* case 1 */
        $separators = array(":", ";");
        foreach($separators as $separator) {
            $exclude = array('Illustrations: ', 'Illustrations :', 'Illustration: ', 'Illustration :', '[Illustration :', 'iLLLtsTRATioNs:',
            'luuusTRATiON :', "Ii.i.usTR.\TioNs:", 'NoTB: ', "iLLUSTR.'i.TiON :", "IllustratonS :", "Illusteation: ", "Illustr.\\tions: ",
            "Illustr.^tions: ", "Illustr.itions: ", "IixustraTIOns:", "luLisTRATiONs:", "Iluustratio.s:", "Ilh-stkations:",
            "Illustratio.ns:", "Ilui'stration:", "Illustrations-", "M7 Illustrations:", "Illustr ations :", "US Illustrations:",
            "Illustr ation :", "Ili^ustratxons :", "BxsiccATi:", "ILLXTSTRATION r", "Ii,i,ustrations:", "FllustIItions :",
            "IivLUSTRATiONS", "Ili^ustration:", "I1.1.USTRAT10NS:", "Ii.i.usTR.\Tios:", "IllustraTio.v:", "ILLUSTR.^TION:",
            "Illustrations ■", "Ii^LusTRATiONS:", "Illustr.atio.s-:", "iLLUSTR.'iTiONs:", "Ii,i,i;sTRATio.Ns:", "Ili.i stations:",
            "Illistratio.v:", "■ Illustrations:", "Ill- VTKATIom:", "Illustratio.vs:", "Illustratio.n:", "Illl'STR ATIONS",
            "ILLISTRATIONS:", '" AJSTRATION :', "rRATlONs:", "Iulustkatiom"); //NoTB: 91144 and 91362_species
            foreach($exclude as $start_of_row) {
                $start_of_row = str_replace(":", $separator, $start_of_row);
                $len = strlen($start_of_row);
                if(substr($row,0,$len) == $start_of_row) return true;;
            }
        }
        /* case 2 */ //e.g. "' Illustrations :"
        $words = explode(" ", $row);
        $terms = array("Illustrations", "Illustration");
        foreach($terms as $term) {
            if((@$words[1] == $term && $words[2] == ":") || (@$words[1] == $term.":")) {
                if(strlen($words[0]) == 1) return true;
            }
        }
        /* case 3 
        "ILLISTRATIONS: "   "Illlstbations:"    "Illvstr.\tion:"
        "Illvstr.^tions"    "Illlstrations"     "Illlstratio.ns:"   */
        $words[0] = str_ireplace(array("'"), "", $words[0]); // 'iLLi'STRATioNs:
        $first3 = substr($words[0],0,3);
        $last4 = substr($words[0], -4);
        $last3 = substr($words[0], -3);
        foreach(array("ill", "ilu", "lll") as $str) {
            if(strtolower($first3) == $str && strtolower($last4) == "ons:") return true;
            if(strtolower($first3) == $str && strtolower($last4) == "ion:") return true;
            if(strtolower($first3) == $str && strtolower($last4) == "ions") return true;
            if(strtolower($first3) == $str && strtolower($last4) == "on-:") return true; // Illustration-:
            if(strtolower($first3) == $str && strtolower($last3) == "ns:") return true;
        }
        // "iLUisTRATiONs:" --- lLLUSTR.\TioNS:
        if(stripos($words[0], "stra") !== false && strtolower($last3) == "ns:") return true; // lM.t;sTRATio.NS:
        if(stripos($words[0], "stra") !== false && strtolower($last4) == "n-s:") return true; // Iulustratio.n-s:
        if(stripos($words[0], "usTR") !== false && strtolower($last3) == "ns:") return true; // Ii-i.usTR.xTio.Ns:
        if(stripos($words[0], "usTR") !== false && strtolower($last4) == "ion:") return true; // iLtusTRATioN:
        
        if(@$words[1] == ":") {
            foreach(array("ill", "ilu", "lll") as $str) {
                if(strtolower($first3) == $str && strtolower($last4) == "tion") return true; //        Illustr.\tion :
                if(strtolower($first3) == $str && strtolower($last4) == "io.n") return true; //        lLLUsrR.\Tio.N :
                if(strtolower($first3) == $str && strtolower($last4) == "tion") return true; //        Illustr.ition :
            }
        }
        return false;
    }
    private function xlx_to_xix($str)
    {   if(strpos($str, "l") !== false) { //string is found  //good debug
            $pos = strpos($str, "l");
            if ($pos === false) {} //not found
            else { //found
                $left = substr($str,$pos-1,1);
                $right = substr($str,$pos+1,1); // echo "\nleft [$left] | right [$right]\n";
                if(self::is_a_consonant_but_not_el($left) && self::is_a_consonant_but_not_el($right)) {
                    $from = $left."l".$right;
                    $to = $left."i".$right; // echo "\nfrom [$from] | to [$to]\n";
                    $str = str_replace($from, $to, $str);
                }
            }
        }
        return $str;
    }
    private function xll_to_xil($str)
    {   if(strpos($str, "ll") !== false) { //string is found  //good debug
            $pos = strpos($str, "l");
            if ($pos === false) {} //not found
            else { //found
                $left = substr($str,$pos-1,1);  // echo "\nleft [$left]\n";
                if(self::is_a_consonant_but_not_el($left)) {
                    $from = $left."ll";
                    $to = $left."il";   // echo "\nfrom [$from] | to [$to]\n";
                    $str = str_replace($from, $to, $str);
                }
            }
        }
        return $str;
    }
    private function llx_to_lix($str)
    {   if(strpos($str, "l") !== false) { //string is found  //good debug
            $pos = strpos($str, "l");
            if ($pos === false) {} //not found
            else { //found
                $right = substr($str,$pos+2,1); // echo "\nright [$right]\n";
                if(self::is_a_consonant_but_not_el($right)) {
                    $from = "ll".$right;
                    $to = "li".$right;  // echo "\nfrom [$from] | to [$to]\n";
                    $str = str_replace($from, $to, $str);
                }
            }
        }
        return $str;
    }
    private function is_a_consonant_but_not_el($letter)
    {
        $consonants = array("b", "c", "d", "f", "g", "h", "j", "k", "m", "n", "p", "q", "r", "s", "t", "v", "w", "x", "y", "z");
        if(in_array(strtolower($letter), $consonants)) return true;
        return false;
    }
    function has_numbers($s)
    {
        if($result = preg_replace("/[^0-9]+/", "", $s)) return true; //get only numbers
    }
    function has_letters($s)
    {
        if($result = preg_replace("/[^a-zA-Z]+/", "", $s)) return true; //get only letter
    }
    function initialize_files_and_folders($input)
    {   //print_r($input); exit;
        /*Array(
            [filename] => 15405.txt
            [lines_before_and_after_sciname] => 2
            [doc] => BHL
            [epub_output_txts_dir] => /Volumes/AKiTiO4/other_files/Smithsonian/BHL/15405/
        )*/
        if(!is_dir($input['epub_output_txts_dir'])) mkdir($input['epub_output_txts_dir']);
        $file = $input['epub_output_txts_dir'].$input['filename'];
        if(!file_exists($file)) self::download_txt_file($file, $input);
        else {
            if(!filesize($file)) self::download_txt_file($file, $input);
        }
    }
    private function download_txt_file($destination, $input)
    {   //exit("\n[$destination]\n[$doc]\n");
        $this->paths['BHL']['txt'] = "https://www.biodiversitylibrary.org/itemtext/";
        $this->paths['BHL']['pdf'] = "https://www.biodiversitylibrary.org/itempdf/";

        $doc = $input['doc']; $filename = $input['filename']; 
        $source = $this->paths[$doc]['txt'].str_replace(".txt", "", $filename);
        // $cmd = "wget -nc --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1"; --- no overwrite
        $cmd = "wget --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1";
        echo "\nDownloading...[$cmd]\n";
        $output = shell_exec($cmd); sleep(10);
        if(file_exists($destination) && filesize($destination)) echo "\n".$destination." downloaded successfully from $doc.\n";
        else                                                    exit("\nERROR: can not download ".$source."\n[$output]\n");
        if(!Functions::is_production()) {
            $destination = str_replace(".txt", ".pdf", $destination);
            $source = $this->paths[$doc]['pdf'].str_replace(".txt", "", $filename);
            // $cmd = "wget -nc --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1"; --- no overwrite
            $cmd = "wget --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1";
            echo "\nDownloading...[$cmd]\n";
            $output = shell_exec($cmd); //sleep(30);
            if(file_exists($destination) && filesize($destination)) echo "\n".$destination." downloaded successfully from $doc.\n";
            else                                                    exit("\nERROR: can not download ".$source."\n[$output]\n");
            exit("\n-Enough here...-\n");
        }
    }
}
?>