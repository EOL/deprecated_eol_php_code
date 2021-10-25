<?php
namespace php_active_record;
/* */
class ParseListTypeAPI extends Functions_Memoirs
{
    function __construct()
    {
        // $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
    }
    /*#################################################################################################################################*/
    function parse_list_type_pdf($input)
    {
        /*
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
    {   // exit("\n$tagged_file\n");
        ///Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/SCtZ-0437_tagged_LT.txt
        $destination = str_replace("_tagged_LT.txt", "_descriptions_LT.txt", $tagged_file);
        $WRITE = fopen($destination, "w"); //initialize
        
        $contents = file_get_contents($tagged_file);
        if(preg_match_all("/<sciname=(.*?)<\/sciname>/ims", $contents, $a)) {
            // print_r($a[1]); exit;
            foreach($a[1] as $block) {
                $rows = explode("\n", $block);
                if(preg_match("/\'(.*?)\'/ims", $rows[0], $a2)) $list_header = $a2[1];
                array_shift($rows);
                $rows = array_filter($rows); //remove null arrays
                $rows = array_unique($rows); //make unique
                $rows = array_values($rows); //reindex key
                if($rows) {
                    echo "\n------------------------\n$list_header\n------------------------\n";
                    // print_r($rows); //continue; //exit; //good debug
                    echo "\n n = ".count($rows)."\n"; //continue; //exit;
                    $i = 0; $possible_genus = array();
                    $possible_genux = ''; //for those lists where the row starts with a species name e.g. "bicolor Guignot 57–36! (Brazil)"
                    foreach($rows as $sciname_line) { $rek = array(); $i++;
                        $rek['verbatim'] = $sciname_line;
                        
                        if(stripos($sciname_line, "...") !== false) continue; //string is found
                        if(stripos($sciname_line, " and ") !== false) continue; //string is found
                        if(stripos($sciname_line, "Key to ") !== false) continue; //string is found --- e.g. "Key to Subspecies of Holophygdon melanesica"
                        /* good debug
                        if(stripos($sciname_line, "Holophygdon melanesica") !== false) exit("\n[$sciname_line]\nxxx\n");  //string is found
                        */
                        
                        /* divide it by period (.), then get the first array element
                        $a = explode(".", $sciname_line);
                        $sciname_line = trim($a[0]);
                        */
                        
                        if(substr($sciname_line,0,1) == "*") $sciname_line = trim(substr($sciname_line,1,strlen($sciname_line)));
                        if(substr($sciname_line,0,1) == "?") continue;
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
                        
                        if($obj = self::run_GNRD($sciname_line)) {
                            // $sciname = @$obj->names[0]->scientificName; //GNRD OBSOLETE
                            $sciname = @$obj[0];
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
                            
                            if($GLOBALS["ENV_DEBUG"]) {
                                print_r($rek);
                                echo "\nlist: [".$rek['scientificName_author_cleaned']."]\n";
                            }
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
        $string = str_replace("‘", "'", $string);
        $string = str_replace("’", "'", $string);
        return $string;
    }
    private function run_GNRD($string)
    {
        if($string = self::clean_name($string)) {}
        else return false;
        
        //================================================================================================start gnfinder
        if($names = $this->get_names_from_gnfinder($string)) return $names;
        return false;
        //================================================================================================end gnfinder
        
        // echo "\n-- [$string]\n";
        $url = $this->service['GNRD text input'].$string; debug("\nGNRD 2: [$url]\n");
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            return $obj;
        }
        return false;
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
                
                // /* force
                if(stripos($row, "Checklist of Amphibians") !== false           ||  //--> SCtZ-0010
                   stripos($row, "Creagrutus and Piabina species") !== false    ||  //--> SCtZ-0613
                   stripos($row, "Material Examined") !== false                     //--> SCtZ-0609
                  ) {
                    $rows[] = $row;
                    $rows = self::process_magic_no_v2($this->magic_no, $rows, $ctr);
                    continue;
                }
                // */
                
                if(stripos($row, "List of Participants") !== false) { $rows = array(); continue; } //string is found
                
                if(stripos($row, "list ") !== false) { //string is found
                    if(stripos($row, "Appendix") !== false) { $rows = array(); continue; } //e.g. "Appendix A. List of specimen sightings and collections."
                    elseif(stripos($row, "see page") !== false) { $rows = array(); continue; } //2nd repo - scb-0002
                    else {} //proceeding OK...
                }
                // elseif(stripos($row, " list") !== false) {} //string is found //not good strategy
                else { $rows = array(); continue; }
                
                // /* 2nd repo
                
                // */
                
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
            if($row == "Appendix") $row = "</taxon>$row";                   //SCtZ-0293.txt
            elseif($row == "References") $row = "</taxon>$row";             //SCtZ-0008.txt
            elseif($row == "General Conclusions") $row = "</taxon>$row";    //SCtZ-0029.txt
            elseif($row == "Bibliography") $row = "</taxon>$row";           //SCtZ-0011.txt
            
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
                if($row == "ACKNOWLEDGMENTS") $row = "</taxon>$row";                //SCtZ-0613.txt
            }
            
            // */
            // echo "\n$row";
            fwrite($WRITE, $row."\n");
        }//end loop text
        fclose($WRITE);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
        
        // print_r($this->lines_to_tag);
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
    {
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
            
            $cont = true;
            // /* criteria 1
            $exclude = array_merge($exclude, array("(", "Order ", "Family ", "Genus "));
            foreach($exclude as $start_of_row) {
                $len = strlen($start_of_row);
                if(substr($row,0,$len) == $start_of_row) {
                    $cont = false;
                    break;
                }
            }
            if(!$cont) continue;
            // */
            
            // /* criteria 2: if first word is all caps e.g. ABSTRACT
            if($row) {
                if(stripos($row, "<taxon") !== false) {fwrite($WRITE, $row."\n"); continue;} //string is found
                if(stripos($row, "</taxon") !== false) {fwrite($WRITE, $row."\n"); continue;} //string is found
                
                // /* first word is all caps removed: OK
                $words = explode(" ", $row);
                $words = array_map('trim', $words); // print_r($words); //exit;
                if(ctype_upper($words[0]) && strlen($words[0]) > 1) continue;
                // */
                
                /* 2nd word must start with small letter --- COMMENT THIS - VERY WRONG TO PUT IT HERE
                if($second_word = @$words[1]) {
                    $first_letter_of_2nd_word = substr($second_word,0,1);
                    if(ctype_upper($first_letter_of_2nd_word)) continue;
                }
                */
                
                // /* 0018
                // Siskiwitia, new genus
                // alticolans, new species
                $row = str_ireplace(", new genus", "", $row);
                $row = str_ireplace(", new species", "", $row);
                // */
                
                // /* 0018 - manual adjustment
                // Perimede, Chambers, 1874a
                $row = str_ireplace("Perimede, Chambers, 1874a", "Perimede Chambers, 1874a", $row); //remove "," comma between name and author
                // */
                
                
                //other filters:
                if(ctype_upper(substr($words[0],0,2)) && strlen($words[0]) >= 2) continue; //e.g. RECORD, FIGURE, etc.
                if(is_numeric($row)) continue;
                if($row == "-") continue;
                if(is_numeric(substr($words[0],0,1))) continue; //e.g. table of contents section
                
                /* New: May 6, 2021 - SEEMS A SCINAME CHECK IS NOT NEEDED HERE AFTER ALL
                if(!$this->is_sciname(trim($words[0]." ".@$words[1]), 'list_type')) continue;
                // if(!$this->is_sciname_LT(trim($words[0]." ".@$words[1]))) continue;
                */
            }
            // */
            
            fwrite($WRITE, $row."\n");
        }//end loop text
        fclose($WRITE);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
    }
    /* not used atm
    private function is_sciname_LT($string)
    {
        // criteria 1 = exclude if line starts with these: 
        $exclude = array("The ", "This ");
        foreach($exclude as $exc) {
            if(substr($string,0,strlen($exc)) == $exc) return false;
        }
        // criteria 2
        if($this->is_sciname_using_GNRD($string)) return true;
        else return false;
    }
    */
    private function is_valid_list_header($row)
    {
        if(stripos($row, "list") !== false) return true; //string is found
        elseif($row == "Creagrutus and Piabina species") return true;           //SCtZ-0613
        elseif($row == "Material Examined") return true;
        else return false;
    }
    private function show_parsed_texts_for_mining_LT($edited_file)
    {
        $with_blocks_file = str_replace("_edited_LT.txt", "_tagged_LT.txt", $edited_file);
        $WRITE = fopen($with_blocks_file, "w"); //initialize
        $contents = file_get_contents($edited_file);
        if(preg_match_all("/<taxon (.*?)<\/taxon>/ims", $contents, $a)) {
            // print_r($a[1]);
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
    function last_resort_to_clean_name($sciname_line) //this started from a copied template
    {
        /* intended to be removed: 10088_5097 --- wasn't fixed here SCtZ-0611
        // "Holophygdon melanesica Subspecies"
        if(strtolower(substr($sciname_line, -11)) == " subspecies") return false;
        */
        
        // /* Tiphia (Tiphia) intermedia Malloch
        $words = explode(" ", $sciname_line);
        if(count($words) >= 3) {
            if($words[1] == "(".$words[0].")") {
                if(ctype_lower(substr($words[2],0,1))) return $sciname_line;
            }
        }
        // */
        
        // /* manual adjustment
        if($sciname_line == "Megapodius molistructor") return $sciname_line;
        if(stripos($sciname_line, "Eunice segregate (Chamberlin, 1919a) restricted") !== false) return "Eunice segregate (Chamberlin, 1919a)";
        // */
        
        // if(stripos($sciname_line, "segregate") !== false) exit("\n[$sciname_line]\n"); //good debug - to see what string passes here.
        
        $proceed = true;
        if($numbers = $this->get_numbers_from_string($sciname_line)) { //if there is a single digit or 2-digit or 3-digit number in string then proceed to clean.
            foreach($numbers as $num) {
                if(strlen($num) <= 3) {$cont = false; break; }
            }
        }
        if($proceed) {}
        else return $sciname_line; //no need to clean further
        
        // /* ------------- last name cleaning ------------- use both gnparser and GNRD
        $orig = $sciname_line;
        if(substr($sciname_line,0,1) == "*") $sciname_line = trim(substr($sciname_line,1,strlen($sciname_line)));
        $sciname_line = str_ireplace("†","",$sciname_line); //special chars like this messes up GNRD and Gnparser
        $sciname_line = str_replace(".—", " .— ", $sciname_line);
        $sciname_line = Functions::remove_whitespace($sciname_line);
        
        if($obj = self::run_gnparser($sciname_line)) $rek['normalized gnparser'] = @$obj[0]->normalized;
        
        $sciname_line = str_replace(",", " , ", $sciname_line);
        $sciname_line = str_replace(":", " : ", $sciname_line);
        $sciname_line = str_replace(";", " ; ", $sciname_line);
        $sciname_line = trim(Functions::remove_whitespace($sciname_line));
        
        if($obj = self::run_GNRD($sciname_line)) {
            // $sciname = @$obj->names[0]->scientificName; //GNRD OBSOLETE
            $sciname = @$obj[0];
            $rek['sciname GNRD'] = $sciname;
            if($obj = self::run_gnparser($sciname_line)) {
                $authorship = @$obj[0]->authorship->verbatim;
                $rek['authorship gnparser'] = $authorship;
                $rek['scientificName_author'] = trim("$sciname $authorship");
                $rek['scientificName_author_cleaned'] = self::clean_sciname($rek['scientificName_author']);
            }
            if(!@$rek['scientificName_author_cleaned']) $rek['scientificName_author_cleaned'] = $rek['sciname GNRD']; //reconcile gnparser vs GNRD
        }
        // ------------- end ------------- */
        if($ret = @$rek['scientificName_author_cleaned']) {
            $ret = str_replace(" ,", ",", $ret);
            $ret = str_replace(" :", ":", $ret);
            $ret = str_replace(" ;", ";", $ret);
            $ret = trim(Functions::remove_whitespace($ret));
            return $ret;
        }
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
    {
        // repo -> Revision of the clearwing moth genus Osminia (Lepidoptera, Sesiidae)
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
            if(ctype_lower(substr($row,0,1))) return false;
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
        
        if($obj = self::run_GNRD($str)) {
            // if(strtolower($str) == strtolower(@$obj->names[0]->scientificName)) return true; //GNRD OBSOLETE
            if(strtolower($str) == strtolower(@$obj[0])) return true;
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
}
?>