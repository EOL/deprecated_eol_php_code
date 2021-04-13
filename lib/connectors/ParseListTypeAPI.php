<?php
namespace php_active_record;
/* */
class ParseListTypeAPI
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
        
        // /* start as copied template
        if($val = $input['epub_output_txts_dir']) $this->path['epub_output_txts_dir'] = $val;
        $this->lines_to_tag = array();
        $this->scinames = array();
        $filename = $input['filename'];
        $this->filename = $filename; //for referencing below
        $lines_before_and_after_sciname = $input['lines_before_and_after_sciname'];
        $this->magic_no = $this->no_of_rows_per_block[$lines_before_and_after_sciname];
        self::get_main_scinames_v2($filename); print_r($this->lines_to_tag); //exit("\nstopx\n");
        echo "\n lines_to_tag: ".count($this->lines_to_tag)."\n"; //exit("\n-end-\n");

        $edited_file = self::add_taxon_tags_to_text_file_LT($filename);
        self::remove_some_rows_LT($edited_file);
        $tagged_file = self::show_parsed_texts_for_mining_LT($edited_file);
        self::get_scinames_per_list($tagged_file);
        // // print_r($this->scinames); 
        // echo "\nRaw scinames count: ".count($this->scinames)."\n";
        
        // */
    }
    private function get_scinames_per_list($tagged_file)
    {
        $contents = file_get_contents($tagged_file);
        if(preg_match_all("/<sciname=(.*?)<\/sciname>/ims", $contents, $a)) {
            // print_r($a[1]);
            foreach($a[1] as $block) {
                $rows = explode("\n", $block);
                if(preg_match("/\'(.*?)\'/ims", $rows[0], $a2)) $list_header = $a2[1];
                array_shift($rows);
                $rows = array_filter($rows); //remove null arrays
                $rows = array_unique($rows); //make unique
                $rows = array_values($rows); //reindex key
                if($rows) {
                    echo "\n------------------------\n$list_header\n------------------------\n";
                    print_r($rows); //continue; //exit; //good debug
                    echo "\n n = ".count($rows)."\n"; continue; //exit;
                    $i = 0;
                    foreach($rows as $sciname_line) { $rek = array(); $i++;
                        $rek['verbatim'] = $sciname_line;
                        
                        if(stripos($sciname_line, "...") !== false) continue; //string is found
                        
                        /* divide it by period (.), then get the first array element
                        $a = explode(".", $sciname_line);
                        $sciname_line = trim($a[0]);
                        */
                        
                        if($obj = self::run_gnparser($sciname_line)) {
                            $rek['normalized gnparser'] = @$obj[0]->normalized;
                        }
                        if($obj = self::run_GNRD($sciname_line)) {
                            $sciname = @$obj->names[0]->scientificName;
                            $rek['sciname GNRD'] = $sciname;
                            if($obj = self::run_gnparser($sciname_line)) {
                                $authorship = @$obj[0]->authorship->verbatim;
                                $rek['authorship gnparser'] = $authorship;
                                $rek['scientificName'] = trim("$sciname $authorship");
                            }
                            print_r($rek); //exit;
                        }
                        // if($i >= 10) break; //debug only
                    }
                }
            }
        }
        // exit("\n$tagged_file\n");
    }
    private function clean_name($string)
    {
        $exclude = array("The ", "This "); //starts with these will be excluded, not a sciname
        foreach($exclude as $exc) {
            if(substr($string,0,strlen($exc)) == $exc) return false;
        }
        
        if(stripos($string, "...") !== false) return false; //string is found
        
        if(substr($string,0,1) == "*") $string = trim(substr($string,1,strlen($string)));
        
        if(stripos($string, ", new species") !== false) {
            $string = trim(str_ireplace(", new species", "", $string));
        }
        
        return $string;
    }
    private function run_GNRD($string)
    {
        if($string = self::clean_name($string)) {}
        else return false;
        
        $url = $this->service['GNRD text input'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            return $obj;
        }
        return false;
    }
    // /*
    private function run_gnparser($string) //not used anymore...
    {
        if($string = self::clean_name($string)) {}
        else return false;

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

        // /* This is a different list of words from below. These rows can be removed from the final text blocks.
        $this->start_of_row_2_exclude = array("FIGURE", "Key to the", "Genus", "Family", "Subgenus", "Superfamily", "Subfamily",
        "? Subfamily");
        // */
        
        // /* This is a different list of words from above. These rows can be removed ONLY when hunting for the scinames.
        $exclude = array("*", "(", "Contents", "Literature", "Miscellaneous", "Introduction", "Appendix", "ACKNOWLEDGMENTS", "TERMINOLOGY",
        "ETYMOLOGY.", "TYPE-");
        // */
        
        // /* loop text file
        $i = 0; $ctr = 0;
        foreach(new FileIterator($local) as $line => $row) { $ctr++;
            $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            $cont = true;
            // /* criteria 1, only for now
            if($row) {
                if(stripos($row, "list") !== false) {} //string is found
                else {
                    $rows = array();
                    continue;
                }
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
                                // if($GLOBALS["ENV_DEBUG"])
                                print_r($rows);
                                $this->scinames[$rows[2]] = ''; //for reporting
                                $this->lines_to_tag[$ctr-2] = '';
                            }
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
                        if(count($words) <= 6)  { //orig is 6
                            if(self::is_valid_list_header($rows[1])) {
                                // if($GLOBALS["ENV_DEBUG"])
                                print_r($rows);
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
            
            if(pathinfo($filename, PATHINFO_FILENAME) != 'SCtZ-0018') { //manual specific
                if($row == "Literature Cited") $row = "</taxon>$row";       //SCtZ-0007.txt
            }
            else {
                if($row == "Braun, Annette F.") $row = "</taxon>$row";      //SCtZ-0018.txt
            }
            // */

            fwrite($WRITE, $row."\n");
        }//end loop text
        fclose($WRITE);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
        
        // print_r($this->lines_to_tag);
        return $edited_file;
    }
    private function format_row_to_ListHeader($row)
    {   //e.g. "9. Annotated list of..." to "Annotated list of..."    //number infront removed
        $words = explode(" ", $row); // print_r($words); exit;
        if(substr($words[0], -1) == ".") {
            $tmp = str_replace(".", "", $words[0]);
            if(is_numeric($tmp)) array_shift($words);
            // print_r($words); exit("\nditox\n");
            return implode(" ", $words);
        }
        return $row;
    }
    private function remove_some_rows_LT($edited_file)
    {
        // exit("\nxxx[$edited_file]\n");
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
                    $rows = array();
                    $cont = false; break;
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
                if(ctype_upper($words[0]) && strlen($words[0]) > 1) {
                    $rows = array();
                    continue;
                }
                // */
                
                //other filters:
                if(ctype_upper(substr($words[0],0,2)) && strlen($words[0]) >= 2) continue; //e.g. RECORD, FIGURE, etc.
                if(is_numeric($row)) continue;
                if($row == "-") continue;
                if(is_numeric(substr($words[0],0,1))) continue; //e.g. table of contents section
                
                if(!$this->is_sciname(trim($words[0]." ".@$words[1]), 'list_type')) continue;
                // if(!$this->is_sciname_LT(trim($words[0]." ".@$words[1]))) continue;
                
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
}
?>