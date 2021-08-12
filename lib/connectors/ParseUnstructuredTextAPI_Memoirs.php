<?php
namespace php_active_record;
/* */
class ParseUnstructuredTextAPI_Memoirs extends ParseListTypeAPI_Memoirs
{
    function __construct($resource_name)
    {
        $this->resource_name = $resource_name;
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);

        /* START epub series */
        // $this->path['epub_output_txts_dir'] = '/Volumes/AKiTiO4/other_files/epub/'; //dir for converted epubs to txts
        $this->service['GNRD text input'] = 'http://gnrd.globalnames.org/name_finder.json?text=';
        $this->service['GNParser'] = "https://parser.globalnames.org/api/v1/";
        /*
        http://gnrd.globalnames.org/name_finder.json?text=Melanoleuca collybiiformis. Murrill, Mycologia 5 : 216. 1913
        http://gnrd.globalnames.org/name_finder.json?text=Gadus
        
        https://parser.globalnames.org/api/v1/HOSTS (Table 1).—In North America, Populus tremuloides Michx., is the most...
        https://parser.globalnames.org/api/v1/Melanoleuca collybiiformis. Murrill, Mycologia 5 : 216. 1913
        
        
        not used:
        https://parser.globalnames.org/?q=https://parser.globalnames.org/api/v1/HOSTS (Table 1).—In North America, Populus tremuloides Michx...

        %26 - &     %2C - ,     %28 - (     %29 - )     %3B - ;
        + - space
        */
        /* index key here is the lines_before_and_after_sciname */
        $this->no_of_rows_per_block[2] = 5; //orig, first sample epub (SCtZ-0293_convertio.txt)
        $this->no_of_rows_per_block[1] = 3; //orig, first sample epub (SCtZ-0293_convertio.txt)
        /* END epub series */
        
        // /* copied from SmithsonianPDFsAPI
        $this->PDFs_that_are_lists = array('120083'); //118237 ignored list-type
        // */
        
        $this->assoc_prefixes = array("HOSTS", "HOST", "PARASITOIDS", "PARASITOID");
        $this->ranks  = array('Kingdom', 'Phylum', 'Class', 'Order', 'Family', 'Genus', 'Tribe', 'Subgenus', 'Subtribe', 'Subfamily', 'Suborder', 
                              'Subphylum', 'Subclass', 'Superfamily', "? Subfamily");
        $this->in_question = "Lycogala flavofuscum";
        $this->activeYN['91362'] = "waiting..."; //1st sample where first part of doc is ignored. Up to a certain point.
        $this->activeYN['91225'] = "waiting...";
    }
    /*#################################################################################################################################*/
    function parse_pdftotext_result($input) //Mar 25, 2021 - start epub series
    {   //print_r($input); print_r(pathinfo($input['filename'])); exit("\nelix 1\n");
        $this->initialize_files_and_folders($input);
        /*Array(
            [filename] => SCtZ-0007.txt
            [lines_before_and_after_sciname] => 1
            [epub_output_txts_dir] => /Volumes/AKiTiO4/other_files/Smithsonian/epub/SCtZ-0007/
            [type] => {blank} or 'list'
        )
        Array(
            [filename] => SCtZ-0437.txt
            [type] => list
            [epub_output_txts_dir] => /Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/
        )
        */
        $this->debug['sciname cnt'] = 0;
        // /* this serves when script is called from parse_unstructured_text.php --- un-comment in real operation
        $pdf_id = pathinfo($input['filename'], PATHINFO_FILENAME);
        $this->pdf_id = $pdf_id;
        if(in_array($pdf_id, $this->PDFs_that_are_lists)) {
            echo "- IS A LIST, NOT SPECIES-DESCRIPTION-TYPE 02\n";
            $this->parse_list_type_pdf($input);
            if(in_array($pdf_id, array('SCtZ-0437'))) return; //many lists have bad species sections
            elseif(in_array($pdf_id, array('120083'))) {} //lists with good species sections
            // 
            // return; //should be commented coz some list-type docs have species sections as well
        }
        // */
        
        if($val = $input['epub_output_txts_dir']) $this->path['epub_output_txts_dir'] = $val;
        
        $this->lines_to_tag = array();
        $this->scinames = array();
        $filename = $input['filename'];
        
        // /* new: delete start of text up to certain point e.g. 120083.txt
        if($this->pdf_id == '120083') {
            echo("\nfilename 1: $filename\n");
            $filename = self::cutoff_source_text_file($filename, "Emergence occurs from December to early March.");
            echo("\nfilename 2: $filename\n");
        }
        // */
        
        $this->filename = $filename; //for referencing below
        $lines_before_and_after_sciname = $input['lines_before_and_after_sciname'];
        $this->magic_no = $this->no_of_rows_per_block[$lines_before_and_after_sciname];
        self::get_main_scinames($filename); // print_r($this->lines_to_tag); 
        // print_r($this->scinames); exit;
        echo "\n lines_to_tag: ".count($this->lines_to_tag)."\n"; //exit("\n-end-\n");
        $edited_file = self::add_taxon_tags_to_text_file_v3($filename);
        self::remove_some_rows($edited_file);
        self::show_parsed_texts_for_mining($edited_file);
        // print_r($this->scinames); 
        echo "\nRaw scinames count: ".count($this->scinames)."\n";
        
        if(isset($this->investigate_1)) print_r($this->investigate_1);
        if(isset($this->investigate2)) print_r($this->investigate2);
    }
    private function get_main_scinames($filename)
    {
        $local = $this->path['epub_output_txts_dir'].$filename;

        // /* This is a different list of words from below. These rows can be removed from the final text blocks.
        $this->start_of_row_2_exclude = array("FIGURE", "TABLE", "PLATE", "Key to the", "Genus", "Family", "Order", "Subgenus", "Superfamily", "Subfamily",
        "? Subfamily", "Suborder", "Subgenus", "Tribe", "Infraorder");
        // */
        
        // /* This is a different list of words from above. These rows can be removed ONLY when hunting for the scinames.
        $exclude = array("(", "Contents", "Literature", "Miscellaneous", "Introduction", "Appendix", "ACKNOWLEDGMENTS", "TERMINOLOGY",
        "ETYMOLOGY.", "TYPE-", "COMPOSITION");
        $exclude = array_merge($exclude, $this->start_of_row_2_exclude);
        // */
        
        // /* customize 
        $pdf_id = pathinfo($filename, PATHINFO_FILENAME); //exit("\n[$pdf_id]\n");
        if($pdf_id != 'scb-0003') $exclude[] = "*";
        // */
        
        // /* loop text file
        $i = 0; $ctr = 0;
        foreach(new FileIterator($local) as $line => $row) { $ctr++;
            $i++; if(($i % 5000) == 0) echo " $i";

            // /* New
            if($this->pdf_id == '91362') {
                if(stripos($row, "REVISED HOST-INDEX TO THE USTILAGINALES") !== false) $this->activeYN[$this->pdf_id] = "processing...";
            }
            elseif($this->pdf_id == '91225') {
                if(stripos($row, "HOST-INDEX TO THE UREDINALES") !== false) $this->activeYN[$this->pdf_id] = "processing...";
            }
            // */
            
            if($this->pdf_id == '119520') {} //accept brackets e.g. "[Coeliades bixana Evans]"
            else $row = trim(preg_replace('/\s*\[[^)]*\]/', '', $row)); //remove brackets //the rest goes here
            $row = trim($row);
            
            if(in_array($this->pdf_id, array('118935', '30355'))) { //118935 1st doc
                // /* manual: floridanus ((Fcenus ) Bradley, Trans. Am. Ent. Soc, xxxiv, 112.
                $row = str_replace("((Fcenus", "(Fcenus", $row);
                //infrarubens (Nomada vicinalis; Cockerell, Bull. 94, Colo. Exp. Sta., 84.
                $row = str_replace("(Nomada vicinalis;", "(Nomada vicinalis)", $row);
                //others:
                $row = str_replace("((Nomada)", "(Nomada)", $row);
                $row = str_replace("(Monedula(", "(Monedula)", $row);
                // */
                if($this->pdf_id == '118935') {
                    $this->letter_case_err = array('albidopictus', 'antennatus', 'attractus', 'auratus', 'cordoviensis', 'coccinifera', 'coloradensis', 
                    'cuttus', 'latus', 'lineatus', 'linitus', 'magus', 'multicinctus', 'occidentalis', 'excavatus', 'foveatus', 'albopictus', 'elongatus', 
                    'stigmatalis', 'subalbatus', 'sulphurea', 'sumichrasti', 'trivittatus', 'zonatus', 'albopictus', 'constrictus', 'cubensis', 'extricatus', 
                    'fasciatus', 'convexus', 'cookii', 'costatus', 'coxatus', 'cressoni', 'cultriformis', 'cultus', 'curvator', 'curvineura', 'elongatus',
                    'cc-nvergens', 'cinctus', 'corvallisensis', 'colorata', 'consultus', 'clarkii', 'carolina', 'azygos', 'w-scripta', 'subdistans', 
                    'scurra', 'delodontus', 'confederata', 'clypeopororia', 'coactipostica', 'cockerelli', 'columbiana', 'carolina', 'scelesta', 'virgatus',
                    'sulcus', 'ocellatus', 'corrugatus', 'rugosus', 'inordinatus', 'distinctus', 'suffusus', 'punctatus', 'chalcifrons', 'subfrigidus', 
                    'subtilis', 'solani', 'striatus', 'ephippiatus', 'dentatus', 'confertus', 'asperatus', 'u-scripta','usitata');
                    foreach($this->letter_case_err as $word) $row = str_ireplace($word, $word, $row);
                }
            }
            
            // if(stripos($row, $this->in_question) !== false) exit("\nsearch 1\n[$row]\n");   //string is found
            
            /* NOT FOR MEMOIRS
            if(stripos($row, "fig.") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, "incertae sedis") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, "cf.") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, "Figure") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " and ") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " cm ") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " p. ") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " pp.") !== false) {$rows = array(); continue;} //string is found
            */
            
            // caryae (Selandria) Norton, Packard's Guide to Study of Ins., 1869, p. 224.       // has "p."
            /*
            salicicola (Euura) E. A. Smith, N. Am. Entom., i, 41.                               //multiple word for the 3rd word
            infrarubens (Nomada vicinalis; Cockerell, Bull. 94, Colo. Exp. Sta., 84.
            */

            // if(stripos($row, $this->in_question) !== false) exit("\nsearch 1\n[$row]\n");   //string is found

            // /* make "( 73 )" to "(73)" --- // ( 73 ) Bucculatrix domicola new species --- 118941
            // if($this->pdf_id == '118941') {
                if(preg_match("/\((.*?)\)/ims", $row, $ret)) { //trim what is inside the parenthesis
                    $inside_parenthesis = $ret[1];
                    $row = str_replace("($inside_parenthesis)", "(".trim($inside_parenthesis).")", $row);
                }
            // }
            // */
            
            $cont = true;
            
            // /* criteria 2: if first word is all caps e.g. ABSTRACT
            if($row) {
                $words = explode(" ", $row);
                if(ctype_upper($words[0]) && strlen($words[0]) > 1) {
                    // print_r($words); //exit;
                    $rows = array(); continue;
                }
                //another
                if(@$words[1] == "species") { //2nd word is 'species'
                    $rows = array(); continue;
                }
                //another
                if(is_numeric(substr(@$words[1],0,1))) { //2nd word starts with a number
                    $rows = array(); continue;
                }
                //another: "Propontocypris (Propontocypris), 27"
                if(substr(@$words[1],0,1) == "(") { //2nd word starts with "("
                    if($third = @$words[2]) { //has a 3rd word
                        if(is_numeric(substr($third,0,1))) { //3rd word starts with a number
                            $rows = array(); continue;
                        }
                        if($third == 'species') { // "Propontocypris (Schedopontocypris) species 2"
                            $rows = array(); continue;
                        }
                    }
                }
                
            }
            // */

            // if(stripos($row, $this->in_question) !== false) exit("\nsearch 2\n[$row]\n");   //string is found

            /* good debug
            //Capitophorus ohioensis Smith, 1940:141
            //Capitophorus ohioensis Smith, 1940:141 [type: apt.v.f., Columbus, Ohio, 15–X–1938, CFS, on Helianthus; in USNM].
            if(stripos($row, "Capitophorus ohioensis") !== false) exit("\nok 3\n[$row]\n"); //string is found
            */
            
            $cont = true;
            /* NOT FOR MEMOIRS
            // criteria 3: any occurrence of these strings in any part of the row
            $exclude2 = array(" of ", " in ", " the ", " this ", " with ", "Three ", "There ", " are ", "…", " for ", " dos ", " on ");
            $exclude2 = array_merge($exclude2, array('order', 'family', 'subgenus', 'tribe')); //is valid "Anoplodactylus lagenus"
            foreach($exclude2 as $exc) {
                if(stripos($row, $exc) !== false) { //string is found
                    $rows = array();
                    $cont = false; break;
                }
            }
            if(!$cont) continue;
            */

            if(stripos($row, "salicicola (") !== false) echo "\nsearch 4\n";   //string is found
            
            //for weird names, from Jen
            $row = str_replace(array("“", "”"), "", $row); // “Clania” licheniphilus Koehler --> 0188.epub
            
            /* good debug
            if(stripos($row, "Thespesia howii") !== false) print("\nok 4\n[$row]\n"); //string is found
            */

            $rows[] = $row;
            $rows = self::process_magic_no($this->magic_no, $rows, $ctr);
            
            // /* new routine
            if($this->resource_name == 'all_BHL') {
                $rows2[] = $row;
                if(count($rows2) == 4) $rows2 = self::possible_Distribution_Stop_pattern($rows2, $ctr);
            }
            // */
        }
        // */
    }
    private function possible_Distribution_Stop_pattern($rows2, $ctr)
    {
        $rows2 = array_map('trim', $rows2);
        if(!$rows2[0] && !$rows2[2] && !$rows2[3]) {
            if(substr($rows2[1],0,13) == "Distribution:") {
                print_r($rows2); exit;
                $this->Distribution_Stop_pattern[$ctr-1] = '';
            }
        }
        array_shift($rows2); //remove 1st element, once it reaches 5 rows.
        return $rows2;
    }
    private function process_magic_no($magic_no, $rows, $ctr)
    {
        if($magic_no == 5) {
            if(count($rows) == 5) { //start evaluating records of 5 rows
                if(!$rows[0] && !$rows[1] && !$rows[3] && !$rows[4]) {
                    if($rows[2]) {
                        
                        // if(stripos($rows[2], $this->in_question) !== false) exit("\nok 2\n"); //string is found //good debug
                        
                        $words = explode(" ", $rows[2]);
                        $limit = 15; //9; //orig limit is 6
                        /*
                        if(in_array($this->pdf_id, array('15423', '91155', '15427', //BHL
                                                         '118936', '118950', '118941'))) $limit = 15; */
                        if(count($words) <= $limit)  {
                            // if(stripos($rows[2], $this->in_question) !== false) exit("\nok 1\n".$rows[2]."\n"); //string is found //good debug
                            if(self::is_sciname($rows[2])) {
                                // /*
                                // if(!self::has_species_string($rows[2])) {}
                                //these 3 lines removed from the if() above
                                if($GLOBALS["ENV_DEBUG"]) print_r($rows);
                                $this->scinames[$rows[2]] = ''; //for reporting
                                $this->lines_to_tag[$ctr-2] = '';
                                // */
                            }
                            // else echo "not sci";
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
                        if(count($words) <= 15)  { //orig is 6
                            // echo("\ngoes here 1 [$rows[1]]...\n");
                            if(self::is_sciname($rows[1])) {
                                // echo("\ngoes here 2 [$rows[1]]...\n");
                                // /* 
                                // if(!self::has_species_string($rows[1])) {}
                                //these 3 lines removed from the if() above
                                if($GLOBALS["ENV_DEBUG"]) print_r($rows);
                                $this->scinames[$rows[1]] = ''; //for reporting
                                $this->lines_to_tag[$ctr-1] = '';
                                // */
                            }
                            /* good debug
                            else {
                                if(stripos($rows[1], "salicicola (") !== false) {   //string is found
                                    exit("\nxxx2[$rows[1]]\n");
                                }
                            }
                            */
                        }
                    }
                }
                array_shift($rows); //remove 1st element, once it reaches 3 rows.
            }
            return $rows;
        }
    }
    function get_numbers_from_string($str)
    {
        if(preg_match_all('/\d+/', $str, $a)) return $a[0];
    }
    private function is_sciname_in_120081($string)
    {
        // /* format first: e.g. "Pegomyia palposa (Stein) (Figs. 1, 30, 54.)"
        $string = trim(preg_replace('/\s*\(Fig[^)]*\)/', '', $string)); //remove Figs. parenthesis OK
        // */
        
        $str = trim($string); //Pegomyia atlanis Huckett
        $words = explode(" ", $str);
        if(count($words) > 6) return false;
        if(count($words) < 2) return false;
        if(ctype_lower($words[0][0])) return false; //first word must be capitalized
        if(ctype_upper($words[1][0])) return false; //2nd word must be lower case
        if(stripos($str, ":") !== false) return false; //doesn't have ":"
        if(stripos($str, "—") !== false) return false; //doesn't have this char(s)
        if(stripos($str, " p.") !== false) return false; //doesn't have this char(s)
        if(stripos($str, " of ") !== false) return false; //doesn't have this char(s)
        if(stripos($str, " to ") !== false) return false; //doesn't have this char(s)
        if(stripos($str, "(see") !== false) return false; //doesn't have this char(s)
        if($words[0] == 'Veins') return false; //Veins brownish, calyptrae whitish apicalis (Stein)
        if($words[1] == 'largely') return false; //Palpi largely yellow anabnormis Huckett
        if(self::get_numbers_from_string($words[0])) return false; //first word must not have a number
        if(self::get_numbers_from_string($words[1])) return false; //2nd word must not have a number
        if($words[0] == 'Number') return false; //"Number io"
        if($words[0] == 'Paregle') return false; //Genus starts with "Pegomyia"
        // /* last word must not be a number with < 4 digits => e.g. "Second antennal segment extensively blackish 22"
        $last_word = end($words);
        if(is_numeric($last_word)) {
            if(strlen($last_word) < 4) return false;
        }
        // */
        return $string;
    }
    private function is_sciname_in_memoirs($string) //for 1st doc only
    {
        // print("\n$string\n");
        /* wootonae (Perdita) Cockerell, Ent. News, ix, 215. */
        // $string = "cseruleum (Hedychrum) Norton, Trans. Am. Ent. Soc, vii, 239.";
        // $string = "feria (Otlophorus innumerabilis) Davis, Trans. Am. Ent. Soc, xxiv, 276.";
        // $string = "salicicola (Euura) E. A. Smith, N. Am. Entom., i, 41.";
        $str = trim($string);
        
        // if(stripos($string, "(Schizocerus)") !== false) exit("\n[11 $string]\n"); //string is found
        
        // /* if > 1 word inside parenthesis e.g. "(Otlophorus innumerabilis)", then convert to "(Otlophorus_innumerabilis)"
        if(preg_match("/\((.*?)\)/ims", $str, $ret)) {
            $inside_parenthesis = $ret[1];
            $new_inside_parenthesis = str_replace(" ", "_", $inside_parenthesis);
            $str = str_replace("($inside_parenthesis)", "($new_inside_parenthesis)", $str);
        }
        // */
        
        // /* if > 1 word for the supposedly 3rd word e.g. "salicicola (Euura) E. A. Smith, N. Am. Entom., i, 41."
        if(preg_match("/\)(.*?)\,/ims", $str, $ret)) {
            // echo "\nstr1 = [$str]\n";
            if($inside_thirdword = trim($ret[1])) {
                // echo "\n inside_thirdword: [$inside_thirdword]\n";
                $new_inside_thirdword = str_replace(" ", "_", $inside_thirdword);
                $str = str_replace(") $inside_thirdword,", ") $new_inside_thirdword,", $str);
            }
            else { //abdominalis (Schizocerus), Proc. Ent. Soc. Phil., iv, 243, cf.
                $words = explode(" ", $str);
                $str = $words[0]." ".substr($words[1],0,strlen($words[1])-1); //remove "," comma -> e.g. "dimmockii (Nematus), Trans. Am. Ent. Soc, viii, 6, 9."
            }
            // exit("\nstr2 = [$str]\n");
        }
        // */
        $arr = explode(" ", $str); 
        // if(stripos($str, "(Schizocerus)") !== false) {print_r($arr); exit;} //string is found
        /*cseruleum (Hedychrum) Norton, Trans. Am. Ent. Soc, vii, 239.
        Array(
            [0] => cseruleum
            [1] => (Hedychrum)
            [2] => Norton,
            [3] => Trans.
            [4] => Am.
            [5] => Ent.
            [6] => Soc,
            [7] => vii,
            [8] => 239.
        )*/
        // /*
        $tmp = $arr[0]; //e.g. cseruleum | pinus-rigida
        $tmp = str_replace(array("CRESSON 6l", ",", ".", " ", "-", "'"), "", $tmp);
        $tmp = preg_replace('/[0-9]+/', '', $tmp); //remove For Western Arabic numbers (0-9):
        if(!ctype_lower($tmp)) return false;                //1st word is all small letter
        // */
        
        /*
        if(stripos($str, "(Schizocerus)") !== false) { //string is found
            // print_r($arr);
            // exit("\n[22 $str]\n");
            Array(
                [0] => abdominalis
                [1] => (Schizocerus)
            )
        } */
        
        if(@$arr[1][0] != "(") return false;                //2nd word starts with "("
        // if(stripos($str, "Schizocerus") !== false) exit("\n[22b $str]\n"); //string is found
        
        if(count($arr) >= 3) {
            if(substr($arr[2],-1) != ",") return false;         //3rd should end with "," comma
        }
        // if(stripos($str, "Schizocerus") !== false) exit("\n[22d $str]\n"); //string is found
        
        if(preg_match("/\((.*?)\)/ims", $arr[1], $ret)) {
            $second = trim($ret[1]);
            $second = str_replace("_", " ", $second);
            // if(stripos($str, "(Schizocerus)") !== false) exit("\n2nd: [$second]\n"); //string is found
        }
        else return false;
        
        // if(stripos($str, "Schizocerus") !== false) exit("\n[33 $str]\n"); //string is found
        
        $third = substr(@$arr[2], 0, strlen(@$arr[2])-1);
        $third = str_replace("_", " ", $third);
        $sciname = $second." ".$arr[0]." ".$third;
        // exit("\nsciname: $sciname\n-elix-\n");
        return trim($sciname);
    }
    function is_sciname($string, $doc_type = 'species_type') //for initial scinames list
    {
        // if(stripos($string, $this->in_question) !== false) {exit("\nxx[$string]xx 11\n");}   //string is found  //good debug
        
        // /* NEW
        if(!isset($this->activeYN[$this->pdf_id])) {} //just continue, un-initialized resource
        else {
            if($this->activeYN[$this->pdf_id] == "waiting...") return false;
            elseif($this->activeYN[$this->pdf_id] == "processing...") {} //just continue
        }
        // */
        
        // /* manual - BHL
        $string = str_ireplace("1 . Seligeria campylopoda", "1. Seligeria campylopoda", $string);
        if(stripos($string, "canadensis (Smi") !== false) return false; //string is found -> 30355.txt
        if($this->pdf_id == '15428') {
            $string = str_ireplace("1 Potamogeton Purshii", "? Potamogeton Purshii", $string);
        }
        // */
        
        // /* manual 
        if($this->pdf_id == '91362_species') {
            $string = str_ireplace("6.?a. Ustilago Claytoniae", "63a. Ustilago Claytoniae", $string);
        }
        // */
        
        if(in_array($this->pdf_id, array("91225", "91362"))) {
            if($numbers = self::get_numbers_from_string($string)) return false;
            $chars = array(" see ", ", sec ", " , sec", ".see ", ", set-", " , KC ", ", set ", ", ice ", ", MC ", ", Bee ", ", ee ", 
            " sec ", " tee ",
            "'", ">", "<", "»", "»", "/");
            $chars[] = " [";
            foreach($chars as $char) {
                if(stripos($string, $char) !== false) return false; //string is found
            }
        }
        
        
        // /* manual - MotAES
        if(preg_match("/\(Plate(.*?)\)/ims", $string, $a)) { //remove parenthesis e.g. "Cariblatta lutea lutea (Saussure and Zehntner) (Plate II, figures i and 2.)"
            $string = trim(str_ireplace("(Plate".$a[1].")", "", $string));
        }
        //'3" Compsodes mexicanus (Saussure) --- 27822.txt
        $string = str_replace("'3\"", "", $string);
        $string = str_ireplace("Eurycotis bioUeyi Rehn", "Eurycotis biolleyi Rehn", $string); //30354
        
        if($this->pdf_id == '27822') { // Nyctihora laevigata and numerous Carihlatta insidaris --- GNRD didn't recognize that there are 2 binomials here
            if(stripos($string, "Nyctihora laevigata and numerous Carihlatta") !== false) return false; //string is found
        }
        // */
        
        if($this->pdf_id == '118941') $string = str_replace("Bucculatrix Columbiana", "Bucculatrix columbiana", $string);
        if($this->pdf_id == '119520') $string = str_replace("Epitola Ieonina Staudinger", "Epitola leonina Staudinger", $string);
        // if($this->pdf_id == '91144') $string = str_replace("COLEANTHUSvSeidel;", "COLEANTHUS Seidel;", $string);
        
        
        // if(stripos($string, $this->in_question) !== false) exit("\nhanap 0 [$string]\n"); //string is found
        if(in_array($this->pdf_id, array('118935', '30355'))) { //118935 - 1st doc
            if(self::is_sciname_in_memoirs($string)) return true;
            else return false;
        }
        elseif($this->pdf_id == '120081') { //2nd doc
            if(self::is_sciname_in_120081($string)) return true;
            else return false;
        }
        elseif($this->pdf_id == '120082') { //4th doc
            if(self::is_sciname_in_120082($string)) return true;
            else return false;
        }
        elseif($this->pdf_id == '118986') { //5th doc
            if(self::is_sciname_in_118986($string)) return true;
            else return false;
        }
        elseif(in_array($this->pdf_id, array('118920', '120083', '118237')) || $this->resource_name == 'MotAES') { //6th 7th 8th doc
            
            // if(stripos($string, $this->in_question) !== false) exit("\nstopx 09 [$string]\n"); //string is found
            
            // /* manual
            if(stripos($string, "Measurement") !== false) return false; //string is found
            // */
            if($this->pdf_id == '27822') {
                // /*
                $words = explode(" ", $string);
                if(count($words) <= 2) return false;
                // */
            }
            // /* anywhere in the string
            $exclude = array('The ', 'This ', 'These ', 'Photograph', ' after'); //'<^>', '<OC'
            foreach($exclude as $x) {
                if(stripos($string, $x) !== false) return false; //string is found
            }
            // */
            // /* start of the string
            $cont = true;
            $exclude = array_merge($exclude, array("From ", '"', ".")); //"WOittO", "G>", "H^l)", "Nfu-j", "XSr-"
            if($this->pdf_id == '119187') { //manual
                $exclude[] = "Prothorax pale brown";
                $exclude[] = 'Pterostigma brown ochre';
                $exclude[] = 'Aeshna (Hesperaeschna) psilus 194';
            }
            foreach($exclude as $start_of_row) {
                $len = strlen($start_of_row);
                if(substr($string,0,$len) == $start_of_row) {
                    $cont = false;
                    break;
                }
            }
            if(!$cont) return false;
            // */
            
            // /*
            $words = explode(" ", $string);
            if(strlen(@$words[1]) == 1) return false; //2nd word is just 1 char long
            if(substr($words[0], -1) == ",") return false; //1st word last char is a comma ","
            // */
            
            // /* 118978
            if(@$words[1] == "(s.") unset($words[1]);
            if(@$words[2] == "str.)") unset($words[2]);
            $string = implode(" ", $words);
            // */
            
            // if(stripos($string, $this->in_question) !== false) exit("\nstopx 10 [$string]\n"); //string is found

            /* good debug
            if(stripos($string, $this->in_question) !== false) {
                if(self::is_sciname_in_118920($string)) exit("\nelix true\n[$string]\n");
                else exit("\nelix false\n[$string]\n");
            }
            */
            
            if(self::is_sciname_in_118920($string)) return true;
            else return false;
        }
        elseif($this->resource_name == 'all_BHL' || in_array($this->pdf_id, array('15423', '91155', '15427', //BHL
                                             '118950', '118941'))) { //and BHL-like e.g. 118950
            // /* manual
            if(stripos($string, "Not seen") !== false) return false; //string is found
            // */
            
            // if(stripos($string, $this->in_question) !== false) {exit("\nxx[$string]xx2\n");}   //string is found  //good debug
            
            $words = explode(" ", trim($string));
            
            // 118941
            // ( 73 ) Bucculatrix domicola new species 
            // (59) Bucculatrix Columbiana new species (Figs. 162, 162a, 163, 163a.) 
            // (91 ) Bucculatrix anaticula new species (Figs. 225, 225a, 226.) 
            // ( 99 ) Bucculatrix thurberiella Busck 
            
            // /* e.g. "(1) Coelopoeta glutinosi Walsingham (Figs. 1, 2, 55, 55a, 55b, 101.)" 118950

            if(in_array($this->pdf_id, array('118950', '118941'))) { //BHL-like
                $first = $words[0];
                if(preg_match("/\((.*?)\)/ims", $first, $a)) {
                    if(!is_numeric($a[1])) return false;
                }
                else return false;
            }
            // */
            // if(stripos($string, $this->in_question) !== false) exit("\nxx[$string]xx4\n"); //string is found  //good debug
            
            $words[0] = str_replace(array("(", ")", ","), "", $words[0]);
            if($this->pdf_id == "91362_species") {
                if($this->has_letters($words[0]) && $this->has_numbers($words[0])) {} //  7a. Urocystis magica
                else return false;
            }
            else { //the rest goes here
                if(!is_numeric($words[0])) return false;
            }
            $string = self::remove_first_word_if_it_has_number($string);

            /* good debug
            if(stripos($string, $this->in_question) !== false) {
                if(self::is_sciname_in_15423($string)) echo "\nis sci OK\n";
                else echo "\nnot sci\n";
                exit("\n[$string]\n");
            }
            */

            if(self::is_sciname_in_15423($string)) return true;
            else return false;
        }
        /* ----- end Memoirs ----- */

        if(stripos($string, "salicicola (") !== false) echo "\nhanap 1 [$string]\n"; //string is found
        
        $string = self::remove_if_first_chars_are("* ", $string, 2); //e.g. "* Enteromorpha clathrata (Roth) J. Agardh"
        
        $exclude = array("The ", "This ", "When "); //starts with these will be excluded, not a sciname
        foreach($exclude as $exc) {
            if(substr($string,0,strlen($exc)) == $exc) return false;
        }
        
        if(stripos($string, "salicicola (") !== false) echo "\nhanap 2 [$string]\n"; //string is found
        
        // /* e.g. "16a. Cerceris bougainvillensis solomonis, new subspecies" --- remove "16a."
        $string = self::remove_first_word_if_it_has_number($string);
        // */

        // echo "\nreach 1\n";
        
        if($doc_type != "list_type") {
            if(ctype_lower(substr($string,0,1))) return false;
        }
        // echo "\nreach 2a [$string]\n";
        if(substr($string,1,1) == "." && !is_numeric(substr($string,0,1))) return false; //not e.g. "C. Allan Child"
        // echo "\nreach 2b [$string]\n";
        // /* exclude one-word names e.g. "Sarsiellidae"
        $words = explode(" ", $string);
        if(count($words) == 1) return false;
        // */

        if(stripos($string, "salicicola (") !== false) echo "\nhanap 3 [$string]\n"; //string is found

        // echo "\nreach 3\n";
        // [Thespesia howii Hu, Fl. China, Fam. 153:69, T.22, F.3, 1955.]
        
        // /*
        if($numbers = self::get_numbers_from_string($string)) { //if there is a single digit or 2-digit or 3-digit number in string then not sciname.
            foreach($numbers as $num) {
                if(strlen($num) <= 3) {
                    if(stripos($string, " species $num") !== false) return true; //e.g. "Pontocypris species 1" //string is found
                    elseif(stripos($string, "$num.") !== false) { //e.g. 13. Oratosquilla gonypetes (Kemp, 1911) //string is found
                        if(substr($string,0,strlen("$num.")) == "$num.") {} //return true;
                    }
                    // else return false; //this is very wrong but it has been here for a while. SHOULD REMAIN COMMENTED
                }
            }
        }
        // */
        
        // echo "\nreach 4\n";
        
        if(stripos($string, "salicicola (") !== false) echo "\nhanap 4 [$string]\n"; //string is found
        
        echo "\nrun it with GNRD: [$string]\n";
        if($doc_type == 'species_type') {
            if(self::is_sciname_using_GNRD($string)) return true;
            else return false;
        }
        elseif($doc_type == 'list_type') {
            if(ctype_upper(substr($string,0,1))) { //only names starting with upper case will go to GNRD
                if(self::is_sciname_using_GNRD($string)) return true;
                else return false;
            }
            else return true; //e.g. brasiliensis (Régimbart) 95–238 (Brazil) --> SCtZ-0033.txt
        }
        else exit("\nERRORx: no doc_type\n");

        exit("\nShould not go here anymore...\n");
        return true; //seems it doesn't go here anymore
    }
    function is_sciname_using_GNRD($string)
    {
        /* from GNRD
        http://gnrd.globalnames.org/name_finder.json?text=A+spider+named+Pardosa+moesta+Banks,+1892
        http://gnrd.globalnames.org/name_finder.json?text=boggianii Régimbart 00–526 (Paraguay)
        http://gnrd.globalnames.org/name_finder.json?text=Andrena columbiana
        */
        // for weird names, form Jen
        $string = str_replace("‘", "'", $string);
        $string = str_replace("’", "'", $string);

        $url = $this->service['GNRD text input'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            if($obj->names) return true;
        }
        return false;
    }
    private function has_species_string($row)
    {
        if(stripos($row, " genus") !== false) return true;  //string is found
        if(stripos($row, " Subspecies") !== false) return true;  //string is found
        if(stripos($row, " sp.") !== false) return true;  //string is found
        if(stripos($row, " sp ") !== false) return true;  //string is found
        // if(stripos($row, " sp") !== false) return true;  //string is found --- cannot use this: e.g. "Ceratiomyxa sphaerosperma"
        if(stripos($row, " spp") !== false) return true;  //string is found
        if(stripos($row, " species") !== false) {  //string is found
            if(stripos($row, "new species") !== false) {}  //string is found
            elseif(stripos($row, " species 1") !== false) {}  //string is found
            elseif(stripos($row, " species 2") !== false) {}  //string is found
            elseif(stripos($row, " species 3") !== false) {}  //string is found
            elseif(stripos($row, " species 4") !== false) {}  //string is found
            elseif(stripos($row, " species 5") !== false) {}  //string is found
            elseif(stripos($row, " species 6") !== false) {}  //string is found
            elseif(stripos($row, " species 7") !== false) {}  //string is found
            elseif(stripos($row, " species 8") !== false) {}  //string is found
            elseif(stripos($row, " species 9") !== false) {}  //string is found
            elseif(stripos($row, " species 10") !== false) {}  //string is found
            elseif(stripos($row, " species 11") !== false) {}  //string is found
            elseif(stripos($row, " species 12") !== false) {}  //string is found
            else return true;
        }
        return false;
    }
    private function is_valid_species($str)
    {
        $orig = $str;
        // /* "Xestoblatta immaculata new species (Plate IV, figure 16.)"
        $phrases = array("new species", "new combination", "new subspecies"); //remove 'new species' phrase and onwards
        foreach($phrases as $phrase) {
            if(preg_match("/".$phrase."(.*?)xxx/ims", $str."xxx", $a)) {
                $str = trim(str_replace($phrase.$a[1], "", $str));
            }
        }
        // */
        // if(stripos($orig, $this->in_question) !== false) {exit("\nxx[$str]xx\n");}   //string is found  //good debug
        
        if($this->pdf_id == '120083') {
            if($str == ';al society') return false;
        }

        // if(stripos($str, $this->in_question) !== false) {exit("\nxx[$str]xx2\n");}   //string is found  //good debug
        // xx[Chrysopilus velutinus Loew (PI. Ill, fig. 27)]xx33

        // /*
        if(stripos($str, " species ") !== false) return false;  //string is found --- exclude "Synasterope species A" --- 0032
        if(substr($str, -8) == " species") return false;  //string is found --- 0034
        if(substr($str, -4) == " spp") return false;  //string is found --- 0067
        if(stripos($str, "Especies") !== false) return false;  //string is found --- exclude "Clave para las Especies de Farrodes Peters, nuevo género" --- 0062
        // if(stripos($str, "fig.") !== false) return false; // conflict with 118946, but not sure with prev. documents
        if(strtolower(substr($str, -11)) == " subspecies") return false;  //string is found ---	Holophygdon melanesica Subspecies
        // */
        
        // if(stripos($str, $this->in_question) !== false) {exit("\nxx[$str]xx3\n");}   //string is found  //good debug
        //Diplocheila latifrons darlingtoni, 3 new subspecies
        
        // /*
        $words = explode(" ", $str);
        $words = array_map('trim', $words);
        if(count($words) == 1) return false; //beceee5e9c6734374d0ee01d1ee03c2a	Isomyia
        if(count($words) == 2) { //493cff8f65ec17fe2c3a5974d8ac1803	Euborellia (Dohrn)
            $first_char_2nd_word = substr($words[1],0,1);
            if(is_numeric($first_char_2nd_word)) return false;
            if(in_array($this->pdf_id, array('15423', '91155', '15427', '91225', '91362')) || $this->resource_name == 'all_BHL') {}
            else { //Plant names have capitalized species part.
                if(ctype_upper($first_char_2nd_word)) return false; //06a2940e6881040955101a68e88c1f9c  Careospina Especies de Careospina Peters
            }
            if($first_char_2nd_word == "(") return false;
        }
        // */
        
        // if(stripos($str, $this->in_question) !== false) {exit("\nxx[$str]xx4\n");}   //string is found  //good debug
        
        // /* criteria 1
        $words = explode(" ", $str);
        $second_word = @$words[1];
        if(!$second_word) return false; //No 2nd word
        else {
            if(in_array($this->pdf_id, array('15423', '91155', '15427', '91225', '91362')) || $this->resource_name == 'all_BHL') {}
            else { //Plant names have capitalized species part.
                if(ctype_upper(substr($words[1],0,1))) return false; //2nd word is capitalized
            }
        }
        // */
        // if(stripos($str, $this->in_question) !== false) {exit("\nxx[$str]xx5\n");}   //string is found  //good debug
        
        if(substr($str,0,1) == "(") return false;
        
        if(stripos($str, "gen. nov.") !== false) return false;  //string is found
        // e.g. Eguchipsammia, gen. nov.
        
        // /* criteria: if first 3 chars are upper case then exclude
        if(ctype_upper(substr($str,0,3))) return false; //e.g. "TYPE-SPECIES.—Ancohenia hawaiiensis Kornicker, 1976, monotypy."
        // */
        
        // /*
        if(stripos($str, " from ") !== false) return false; //string is found
        // */
        
        // /*
        // bb7bc8588a2d5341f0a6be706547b585 O. striata
        // a14d6967a3e39c47acc6282fbc6207a1 O. asiatica
        // 3c6f75781e17fac48b341d71ea9ec40d O. stephensoni
        if(substr($str,1,2) == ". ") return false;
        // */
        
        /* criteria 2: any part of the row where rank value exists
        foreach($this->ranks as $rank) {
            found
        }
        */
        
        /* cannot do this bec. many like e.g. Nicomedes difficilis Kinberg, 1866: 179.—Hartman 1949:57.
        if(stripos($str, ":") !== false) return false;  //string is found
        */
        
        /* cannot do it also
        if(stripos($str, "?") !== false) return false;  //string is found
        */
        return true;
    }
    private function add_taxon_tags_to_text_file_v3($filename)
    {
        // exit("\n[$filename]\n"); //SCtZ-0063.txt
        // /* orig working OK
        $local = $this->path['epub_output_txts_dir'].$filename;
        $orig_local = $local;
        
        $cleaned = str_replace(".txt", "_cleaned.txt", $local); //new line
        if(file_exists($cleaned)) $local = $cleaned;
        
        $temp_file = $local.".tmp";
        $edited_file = str_replace(".txt", "_edited.txt", $orig_local);
        // /* new
        $edited_file = str_replace("_cutoff", "", $edited_file);
        // */
        copy($local, $edited_file);
        // */

        $WRITE = fopen($temp_file, "w"); //initialize
        $hits = 0;
        
        // /* host-pathogen list pattern:
        $investigate_file = str_replace("_edited.txt", "_source_taxa.txt", $edited_file);
        $WRITE_st = fopen($investigate_file, "w"); //initialize
        // */
        
        // /* loop text file
        $i = 0; $count_of_blank_rows = 0;
        foreach(new FileIterator($edited_file) as $line => $row) { $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            $orig_row = $row;
            // /* manual - BHL
            $row = str_ireplace("1 . Seligeria campylopoda", "1. Seligeria campylopoda", $row);
            $row = str_ireplace("(Mesoleptus,)", "(Mesoleptus),", $row);    //30355 doc
            $row = str_ireplace("(Ichneumon,)", "(Ichneumon),", $row);      //30355 doc
            $row = str_ireplace("( Nomad, 1),", "(Nomada),", $row);      //30355 doc
            $row = str_ireplace("Hlltonius", "Hiltonius", $row);    //120082
            $row = str_ireplace("Eurycotis^'' caraibea", "Eurycotis caraibea", $row);    //27822
            // */

            if($this->pdf_id == '119520') $row = str_ireplace("Teniorhinus ignita (Mabille) (Fig. 52, <J genitalia)", "Teniorhinus ignita (Mabille)", $row);    //119520
            if($this->pdf_id == '119520') $row = str_replace("Epitola Ieonina Staudinger", "Epitola leonina Staudinger", $row);
            if($this->pdf_id == '15428') $row = str_ireplace("3. Sparga'nium californicum", "3. Sparganium californicum", $row);
            if($this->pdf_id == '91144') $row = str_replace("COLEANTHUSvSeidel;", "COLEANTHUS Seidel;", $row);
            if($this->pdf_id == '15427') $row = str_replace("MARATTIASw.", "MARATTIA Sw.", $row);
            if($this->pdf_id == '15427') $row = str_replace("ANEMIA' Sw.", "ANEMIA Sw.", $row);
            if($this->pdf_id == '15427') $row = str_replace("Botrychium calif ornicum", "Botrychium californicum", $row);

            if($this->pdf_id == '15404') {
                $row = str_ireplace("Jtsrefeldia maxima", "Brefeldia maxima", $row);
                $row = str_ireplace("Comatrieha rubens", "Comatricha rubens", $row);
                $row = str_ireplace("Didymium listen", "Didymium Listeri", $row);
            }
            
            if($this->pdf_id == '91225') {
                $row = str_ireplace("Agoseris hlrsuta", "Agoseris hirsuta", $row);
                $row = str_ireplace("Abies ama bills", "Abies amabilis", $row);
                $row = str_ireplace("Aegopogon ten el I us", "Aegopogon tenellus", $row);
                $row = str_ireplace("Abutilon hlrtum", "Abutilon hirtum", $row);
                $row = str_ireplace("I'redinopsis", "Uredinopsis", $row);
                $row = str_ireplace("l/redinopsis mirabilis", "Uredinopsis mirabilis", $row);
                $row = str_ireplace("T'redinopsis Osmundae", "Uredinopsis Osmundae", $row);
                $row = str_ireplace("miastnmi pustulatum", "Pucciniastrum pustulatum", $row);
                $row = str_ireplace("Uredinopaa Copelaod", "Uredinopsis Copelandi", $row);
                $row = str_ireplace("Cerotelium desmiutn", "Cerotelium desmium", $row);
                $row = str_ireplace("Horr.ria parviflora", "Borreria parviflora", $row);
                $row = str_ireplace("■ircinia lateritia", "Micropuccinia lateritia", $row);
                $row = str_ireplace("N'igredo", "Nigredo", $row);
                $row = str_ireplace("mat rosperma, 684", "Uredinopsis macrosperma, 684", $row);
                $row = str_ireplace("rio[i,i> macrosperma, 684", "Uredinopsis macrosperma, 684", $row);
                $row = str_ireplace("I'rcdo ramonensis, 810", "Uredo ramonensis, 810", $row);
                $row = str_ireplace("Bullaria Hw-ra. n. J13", "Bullaria Hieracii, 513", $row);
                $row = str_ireplace("Bullaria Mi»r.»cti, 513", "Bullaria Hieracii, 513", $row);
                $row = str_ireplace("aOcropucchna Grindeliae, 576", "Micropuccinia Grindeliae, 576", $row);
                $row = str_ireplace("Mkropuc oinia Grindeliae 576", "Micropuccinia Grindeliae, 576", $row);
                $row = str_ireplace("Mir ropiir riuia Grindeliae, 576", "Micropuccinia Grindeliae, 576", $row);
                $row = str_ireplace("- osporium Solidaginis, 655", "Coleosporium Solidaginis, 655", $row);
            }
            if($this->pdf_id == '91362') {
                $row = str_ireplace("Vlcia americana", "Vicia americana", $row);
                $row = str_ireplace("L'stilago Tritici, 981", "Ustilago Tritici, 981", $row);
                $row = str_ireplace("Muhlenber^a arenacea", "Muhlenbergia arenacea", $row);
                $row = str_ireplace("Muhlenber^a filiformls", "Muhlenbergia filiformis", $row);
            }
            elseif($this->pdf_id == '91362_species') {
                $row = str_ireplace("MELANOPSICHITJM", "MELANOPSICHIUM", $row);
                $row = str_ireplace("TESTICDLARIA", "TESTICULARIA", $row);
                $row = str_ireplace("IJISTKIBUTion:", "Distribution:", $row);
                $row = str_ireplace("TvPK ix^Cality;", "Type Locality:", $row);
                $row = str_ireplace("DiSTRiBUTIo.v;", "Distribution:", $row);
            }
            elseif($this->pdf_id == '15416') {
                $row = str_ireplace("Plicatura -^ttad^lupensis", "Plicatura obliqua", $row);
            }
            elseif($this->pdf_id == '15421') {
                $row = str_ireplace("Cortinarius bnmneofulvus", "Cortinarius brunneofulvus", $row);
                $row = str_ireplace("Cortinanus jubennus", "Cortinarius juberinus", $row);
            }
            
            
            if($this->pdf_id == '15427') { //start of row
                // $words = array("ANEMIA' sw.");
                // foreach($words as $word) {
                //     $len = strlen($word);
                //     if(substr($row,0,$len) == $word) exit("\nelix 1\n");  //$row = "</taxon>$row";
                // }
                // if(strpos($row, "ANEMIA") !== false) {exit("\nxx[$row]xx00\n");}   //string is found  //good debug
            }
            
            // if($this->pdf_id == '118935') { //1st doc
            if(in_array($this->pdf_id, array('118935', '30355'))) {
                // /* manual: floridanus ((Fcenus ) Bradley, Trans. Am. Ent. Soc, xxxiv, 112.
                $row = str_replace("((Fcenus", "(Fcenus", $row);
                //infrarubens (Nomada vicinalis; Cockerell, Bull. 94, Colo. Exp. Sta., 84.
                $row = str_replace("(Nomada vicinalis;", "(Nomada vicinalis)", $row);
                //others:
                $row = str_replace("((Nomada)", "(Nomada)", $row);
                $row = str_replace("(Monedula(", "(Monedula)", $row);
                // */
                if($this->pdf_id == '118935') {
                    foreach($this->letter_case_err as $word) $row = str_ireplace($word, $word, $row);
                }
                if($ret = self::is_sciname_in_memoirs($row)) $row = $ret;
            }
            elseif($this->pdf_id == '120081') { //2nd doc
                if($ret = self::is_sciname_in_120081($row)) $row = $ret;
            }
            elseif($this->pdf_id == '120082') { //4th doc
                if($ret = self::is_sciname_in_120082($row)) $row = $ret;
            }
            elseif($this->pdf_id == '118986') { //5th doc
                if($ret = self::is_sciname_in_118986($row)) $row = $ret;
            }
            elseif(in_array($this->pdf_id, array('118920', '120083', '118237')) || $this->resource_name == 'MotAES') { //6th 7th 8th doc
                
                // /* 118978
                $words = explode(" ", $row);
                if(@$words[1] == "(s.") unset($words[1]);
                if(@$words[2] == "str.)") unset($words[2]);
                $row = implode(" ", $words);
                // */
                
                if($this->pdf_id == '118978') $row = str_replace("Diplocheila (Neorembusj latifrons Dejean", "Diplocheila (Neorembusj) latifrons Dejean", $row);
                
                if($ret = self::is_sciname_in_118920($row)) $row = $ret;
                // if(stripos($row, $this->in_question) !== false) {exit("\nxx[$row]xx\n");}   //string is found  //good debug
            }
            elseif($this->resource_name == 'all_BHL' || in_array($this->pdf_id, array('15423', '91155', '15427', //BHL
                                                                                         '118950', '118941'))) { //and BHL-like
                
                if($this->pdf_id == '118941') $row = str_replace("Bucculatrix Columbiana", "Bucculatrix columbiana", $row);
                
                 // /* make "( 73 )" to "(73)" --- // ( 73 ) Bucculatrix domicola new species --- 118941
                 if(preg_match("/\((.*?)\)/ims", $row, $ret)) {
                     $inside_parenthesis = $ret[1];
                     $row = str_replace("($inside_parenthesis)", "(".trim($inside_parenthesis).")", $row);
                 }
                 // */

                 $words = array('Notes:', 'Note:', 'Note;', 'Notes;', "Notb:", "Notb :");
                 foreach($words as $word) {
                     $len = strlen($word);
                     if(substr($row,0,$len) == $word)  $row = "</taxon>$row";
                 }

                
                 if($this->pdf_id == '91362_species') {
                     /*another case:
                     from: "15. Ustilago Zeae."
                     This must now be a STOP pattern --- a binomial
                     */
                     // if($row == "15. Ustilago Zeae.") { //exit("\ngot it row [$row]\n"); //good debug
                     //     exit("\ngot it row [$row]\n");
                     // }
                     $words = explode(" ", $row);
                     if(count($words) == 3) {
                         if($this->has_numbers($words[0])) { //echo "\nhere 111\n";
                             if(ctype_upper($words[1][0])) { //1st word, 1st char should be capital
                                 if(self::is_sciname_using_GNRD($row)) { //print_r($words); //echo "\nhere 222\n";
                                     $row = "</taxon>$row";
                                     echo "\nDetected as STOP pattern: [$orig_row] [$row]\n";
                                 }
                             }
                         }
                     }
                 }
                
                
                $words = explode(" ", trim($row));
                if(is_numeric(str_replace(",", "", $words[0]))) { //e.g. "4, REBOULIA Raddi, Opusc..." -> there is comma in first word
                    $row = self::remove_first_word_if_it_has_number($row);
                    $words = explode(" ", trim($row));
                    // /* automatically set 2nd word as small caps
                    if(@$words[1]) {
                        $words[1] = strtolower($words[1]);
                        $row = implode(" ", $words);
                    }
                    // */
                }
                if($ret = self::is_sciname_in_15423($row)) $row = $ret;
            }

            if(!$row) $count_of_blank_rows++;
            else      $count_of_blank_rows = 0;
            
            if(isset($this->Distribution_Stop_pattern[$i])) $row = "</taxon>$row";
            
            if(isset($this->lines_to_tag[$i])) { $hits++;
                // if(stripos($row, $this->in_question) !== false) {exit("\nxx[$row]xx00\n");}   //string is found  //good debug
                $row = self::format_row_to_sciname_v2($row); //fix e.g. "Amastus aphraates Schaus, 1927, p. 74."
                // if(stripos($row, $this->in_question) !== false) {exit("\nxx[$row]xx11\n");}   //string is found  //good debug
                if(self::is_valid_species($row)) { //important last line
                    // if(stripos($row, $this->in_question) !== false) {exit("\nxx[$row]xx22\n");}   //string is found  //good debug

                    // /*
                    if($sciname = self::last_resort_to_clean_name($row, $WRITE_st)) {
                        // if(stripos($row, $this->in_question) !== false) {exit("\nxx[$row][$sciname]xx33\n");}   //string is found  //good debug
                        
                        $words = explode(" ", $sciname);
                        if(count($words) > 1) {
                            if($hits == 1)  $row = "<taxon sciname='$sciname'> ".$row;
                            else            $row = "</taxon><taxon sciname='$sciname'> ".$row;
                        }
                        else { //a valid else statement, needed statement for sure
                            exit("\n-----\nA sign to investigate: [$sciname]\n[$row]\n-----\n");
                            if($hits == 1)  $row = "<taxon sciname='$row'> ".$row;
                            else            $row = "</taxon><taxon sciname='$row'> ".$row;
                        }
                    }
                    else {
                        // if(stripos($row, $this->in_question) !== false) {exit("\nxx[$row]xx33a\n");}   //string is found  //good debug
                        if(in_array($this->pdf_id, array("91225", "91362"))) $row = "</taxon>$row"; //IMPORTANT for 91225 
                    }
                    // */
                }
            }
            /* good debug
            else {
                if(stripos($row, "salicicola") !== false) {   //string is found
                    exit("\nxxx1[$row]\n");
                }
            }
            */
            //start terminal criteria => stop patterns
            if($row == "INDEX.")            $row = "</taxon>$row";
            if($row == "INDEX")             $row = "</taxon>$row";
            if($row == "ACKNOWLEDGMENTS")   $row = "</taxon>$row"; //118237.txt
            if($row == "Pseudomopoid Complex")  $row = "</taxon>$row";
            if(strtolower($row) == "doubtful and excluded species")  $row = "</taxon>$row";
            if(strtolower($row) == "doubtful species")  $row = "</taxon>$row";
            if(strcmp($row, "CORRECTIONS") == 0) $row = "</taxon>$row"; //$var1 is equal to $var2 in a case sensitive string comparison
            if(substr($row,0,4) == "Key ")      $row = "</taxon>$row";
            if(substr(strtoupper($row),0,6) == "TABLE ")    $row = "</taxon>$row";
            
            if($this->is_Section_stop_pattern($row)) $row = "</taxon>$row";
            if($this->is_New_then_RankName_stop_pattern($row)) $row = "</taxon>$row";

            if($this->pdf_id == '118941') if($row == "List of the North America") $row = "</taxon>$row";
            if($this->pdf_id == '119520') if($row == "404 butterflies of liberia") $row = "</taxon>$row";

            if(in_array($this->pdf_id, array("91225", "91362"))) {
                $chars = array(" see ", ", sec ", " , sec", ".see ", ", set-", " , KC ", ", set ", ", ice ", ", MC ", ", Bee ", ", ee ",
                " sec ", " tee ");
                foreach($chars as $char) {
                    if(stripos($row, $char) !== false) {
                        $row = "</taxon>$row"; //string is found
                        break;
                    }
                }
            }
            // if($this->pdf_id == '91362_species') {
            //     $words = array('Notes:', 'Note:');
            //     foreach($words as $word) {
            //         $len = strlen($word);
            //         if(substr($row,0,$len) == $word)  $row = "</taxon>$row";
            //     }
            //     /*another case:
            //     from: "15. Ustilago Zeae."
            //     now: "Ustilago zeae."
            //     This must now be a STOP pattern --- a binomial
            //     */
            //     $words = explode(" ", $row);
            //     if(count($words) == 2) {
            //         if(ctype_upper($words[0][0]) && ctype_lower($words[1][0])) { //1st word, 1st char should be capital, 2nd word, 1st char lower case
            //             if(self::is_sciname_using_GNRD($row)) {
            //                 $row = "</taxon>$row";
            //                 echo "\nDetected as STOP pattern: [$orig_row] [$row]\n";
            //             }
            //         }
            //     }
            // }

            if($this->pdf_id == '120082') { //4th doc
                $words = array('Table', 'Key', 'Remarks. —', 'Nomen inquirendum', 'Literature Cited');
                foreach($words as $word) {
                    $len = strlen($word);
                    if(substr($row,0,$len) == $word)  $row = "</taxon>$row";
                }
                //-----------------------------------
                // newline
                // newline
                // [any combination of rank name and/or taxon name w/rank above species] newline"
                // /* copied template
                // */
                $words = explode(" ", $row);
                foreach($this->ranks as $rank) {
                    if($words[0] == $rank && ctype_upper($words[1][0])) $row = "</taxon>$row"; //e.g. Genus Spirobolus Brandt
                    //1st word is a rank name && 2nd word starts with a capital letter
                }
            }

            // TODO: add a way to remove portions of the txt file, start_text to end_text

            if(in_array($this->pdf_id, array('118986', '118920', '120083', '118978'))) { //5th 6th 7th doc
                // $words = array('Literature Cited', 'Map', 'Fig.', 'Figure');
                $words = array('Literature Cited', 'Map', 'Figures ');
                if($this->pdf_id == '118978') {
                    $words[] = "Diplocheila polita group";
                    // $words[] = 'Badister flavipes and transversus';
                }
                foreach($words as $word) {
                    $len = strlen($word);
                    if(substr($row,0,$len) == $word)  $row = "</taxon>$row";
                }
            }
            // newline
            // [paragraph beginning with ""Fig."" or ""Figure""]"

            // /*
            // if($this->pdf_id == '118935') { //1st doc or those similar with 1st doc e.g. 30355
            if(in_array($this->pdf_id, array('118935', '30355'))) {
                $tmp = str_replace(array("CRESSON 6l", ",", ".", " ", "-", "'", "\\"), "", $row);   //MEM. .\M. ENT. SOC, 2.
                $tmp = preg_replace('/[0-9]+/', '', $tmp); //remove For Western Arabic numbers (0-9):
                $tmp = trim($tmp);
                if(ctype_upper($tmp)) $row = "</taxon>$row";  //entire row is upper case //e.g. "EZRA TOWNSEND CRESSON" or "MEM. AM. ENT. SOC, V."
                                                              //EZRA TOWNSEND CRESSON 5 -> entire row is uppercase with numeric
            }
            if($this->pdf_id == '120081') { //2nd doc
                $row = str_replace("<taxon", "-elicha 1-", $row); //start ---
                $row = str_replace("</taxon>", "-elicha 2-", $row); //start ---
                $row = str_replace("'> ", "-elicha 3-", $row); //start ---

                /* remove "<?" "<j" "<f" */
                $str = $row;
                $pos = strpos($str, "<");
                if($pos !== false) { //string is found
                    $substr = substr($str, $pos, 2);
                    // echo "\n[$str]\n[$pos]\n[$substr]\n";
                    $str = str_replace($substr, "", $str);
                    $row = Functions::remove_whitespace($str);
                }
                /* remove ">" */
                $row = str_replace(">", "", $row);

                $row = str_replace("-elicha 1-", "<taxon", $row); //end ---
                $row = str_replace("-elicha 2-", "</taxon>", $row); //end ---
                $row = str_replace("-elicha 3-", "'> ", $row); //end ---
            }
            // */

            /*
            if($this->pdf_id == '118986') {
                $row = str_replace("<taxon", "-elicha 1-", $row); //start ---
                $row = str_replace("</taxon>", "-elicha 2-", $row); //start ---
                $row = str_replace("'> ", "-elicha 3-", $row); //start ---

                $row = str_replace("<", "&lt;", $row);
                $row = str_replace(">", "&gt;", $row);

                $row = str_replace("-elicha 1-", "<taxon", $row); //end ---
                $row = str_replace("-elicha 2-", "</taxon>", $row); //end ---
                $row = str_replace("-elicha 3-", "'> ", $row); //end ---
            }
            */
            
            if(in_array($this->pdf_id, array('118920', '120083'))) { // 6th 7th doc
                $words = array('Ecology', 'Literature Cited', 'species group', 'SPECIES GROUP');
                foreach($words as $word) {
                    $len = strlen($word);
                    if(substr($row,0,$len) == $word)  $row = "</taxon>$row";
                }
            }
            // newline
            // [paragraph beginning with ""Fig."" or ""Figs.""]"
            
            if($this->pdf_id == '120083') { //7th doc
                //Table 2. Ozark-Ouachita Plecoptera...
                if($row) {
                    $words = explode(" ", trim($row));
                    if($words[0] == "Table" && is_numeric($words[1])) $row = "</taxon>$row";
                    //--------------
                    if(trim($row) == "Nymphs") $row = "</taxon>$row";
                    //--------------
                    $words = array('Key to', "Inside margin of mature wing pads", "Hind wing with anal lobe", "Brown pigment band across head wider");
                    foreach($words as $word) {
                        $len = strlen($word);
                        if(substr($row,0,$len) == $word)  $row = "</taxon>$row";
                    }
                }
            }
            if($this->resource_name == 'all_BHL' || in_array($this->pdf_id, array('15423', '91155', '15427'))) { //BHL
                if($this->if_Illustration_row($row)) $row = "</taxon>$row";
            }
            
            if($this->resource_name == 'all_BHL' || in_array($this->pdf_id, array('15423', '91155', '15427', //BHL
                                         '118950', '118941')) || $this->resource_name == 'MotAES') {

                if($row == "List of Genera and Species") $row = "</taxon>$row";
                
                // at this point the numeric part is already removed
                // /* The genus sections like below, are now stop patterns.
                // 1. LUWULARIA (Micheli) Adans. Fam. PI. 2: 15. 1763.
                // 2. CONOCEPHALUM* Weber; Wiggers, Prim. Fl. Holsat. 82. 1780.
                // 1. SPHAEROCARPOS* (Micheli) Boehm.in Ludwig, 
                // 2. GEOTHALLUS Campb. Bot. Gaz. 21: 13. 1896. 
                // 4, REBOULIA Raddi, Opusc. Sci. Bologna 2: 357. 1818. (Rebouillia.)
                // /* manual
                $row = str_ireplace("SWARTZIAEhrh.;", "SWARTZIA Ehrh.;", $row);
                // */
                $words = explode(" ", $row);
                $first_word = str_ireplace("*", "", $words[0]);
                $first_word = trim(preg_replace('/[0-9]+/', '', $first_word)); //remove For Western Arabic numbers (0-9):

                if($this->pdf_id == '120602') { //exit("\nelix\n");
                    if(in_array(strtoupper($first_word), array("OULOPTERYGIDAE", "DIPLOPTERIDAE", "PANESTHIDAE", "LATINDIINI", "POLYPHAGINI", "PANESTHIIDAE", "EUSTEGASTINI", "SCHNPTERINI", "BRACHYCOLINAE", "CEUTHOBIINAE", "PSEUDOMOPINAE", "NAUPHOETINI", "EPILAMPRINI", "CALOLAMPRINI", "THORACINI", "PARANAUPHOETINI", "ONISCOSOMINI", "LEUCOPHAEINI", "PANCHLORINI", "PHORASPIDINI", "PARATROPINI", "EUSTEGAST", "ISCHNOPTERINI"))) $row = "</taxon>$row";
                }

                if(ctype_upper($first_word)) {
                    if(strlen($first_word) > 2) {
                        if(in_array($first_word, array('LUWULARIA', 'SPHAGNUM', 'ANDREAEA', 'OSMUNDA', 'CYATHEACBAB', 'ANEMIA'))) $row = "</taxon>$row";
                        else {
                            if(self::is_sciname_using_GNRD($first_word)) $row = "</taxon>$row";
                            else {
                                if(!isset($this->investigate_1[$first_word])) {
                                    echo "\nInvestigate 1: [$first_word] not sciname says GNRD\n";
                                    $this->investigate_1[$first_word] = '';
                                }
                            }
                        }
                    }
                }
                // */
                
                // /* rank row must be stop pattern
                // Order MARCHAWTIALES 
                // Family 3 . TARGIONI ACEAE 
                // Family 4. SAUTERIACEAE 
                // Family 6. MARCHANTIACEAE 
                // Family 1. SPHAERO CARP ACE AE 
                // 1st word is a rank name && 2nd word starts with a capital letter, and 2nd word is taxon
                $tmp_row = str_replace(array(".","*","-"), "", $row);
                $tmp_row = preg_replace('/[0-9]+/', '', $tmp_row); //remove For Western Arabic numbers (0-9):
                $tmp_row = trim(Functions::remove_whitespace($tmp_row));
                $words = explode(" ", $tmp_row);
                foreach($this->ranks as $rank) {
                    if($words[0] == $rank && ctype_upper(@$words[1][0])) { //echo "\nChecking rank row...\n";
                        if(in_array($words[1], array('MARCHAWTIALES', 'TARGIONI', 'SPHAERO', 'MUSCI', 'SELIGERLACEAE'))) $row = "</taxon>$row";
                        else {
                            if(self::is_sciname_using_GNRD($words[1])) $row = "</taxon>$row"; //e.g. Genus Spirobolus Brandt
                            else echo "\nInvestigate 2: [$words[1]] not sciname says GNRD\n";
                        }
                    }
                }
                // */
                
                // /* two addtl stop patterns: https://eol-jira.bibalex.org/browse/DATA-1890?focusedCommentId=66240&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66240
                // $words = array('Illustration:', 'Illustrations:');
                $words = array('Exsicc', 'Exsioc:', 'Exstcc.', 'E.xsicc:', 'Kxsicc', 'FxsiccATAE', "^^' ^Exs'^iccataE");
                foreach($words as $word) {
                    $len = strlen($word);
                    if(strtolower(substr($row,0,$len)) == strtolower($word)) {
                        $row = "</taxon>$row";
                        break;
                    }
                }
                // */
            }
            
            if($row == "Bibliography") $row = "</taxon>$row";
            elseif($row == "CATALOGUES") $row = "</taxon>$row";
            
            if($this->is_a_Group_stop_pattern($row)) $row = "</taxon>$row"; //119035.txt
            
            if(in_array($this->pdf_id, array('120602'))) {
                if(self::one_word_and_higher_taxon($row)) $row = "</taxon>$row";                //120602.txt e.g. "Corydiini"
            }
            if(self::two_words_rank_and_sciname_combo($row)) $row = "</taxon>$row";         // Tribe Beckerinini newline
            
            
            /* to close tag the last block
            if($row == "Appendix") $row = "</taxon>$row";                   //SCtZ-0293_convertio.txt
            elseif($row == "Literature Cited") $row = "</taxon>$row";       //SCtZ-0007.txt
            elseif($row == "References") $row = "</taxon>$row";             //SCtZ-0008.txt
            elseif($row == "General Conclusions") $row = "</taxon>$row";    //SCtZ-0029.txt
            elseif($row == "Bibliography") $row = "</taxon>$row";           //SCtZ-0011.txt
            elseif($row == "Bibliography: p.") $row = "</taxon>$row";       //scb-0007.txt
            elseif($row == "Summary and Conclusions") $row = "</taxon>$row";
            elseif($row == "Appendix Tables") $row = "</taxon>$row";
            elseif($row == "Intermediates") $row = "</taxon>$row";          //scb-0007.txt
            elseif($row == "Glossary") $row = "</taxon>$row";               //scb-0009.txt
            elseif($row == "Acknowledgments") $row = "</taxon>$row";        //scb-0094.txt
            
            if(self::sciname_then_specific_words($row, "Excluded Taxa")) $row = "</taxon>$row"; //for all
            // e.g. "Isopterygium Excluded Taxa"

            if(self::numbered_then_sciname($row)) $row = "</taxon>$row"; //for all
            // e.g. "2. Elmeriobryum Broth."
            
            if($filename != "scb-0013.txt") {
                if(self::N_words_or_less_beginning_with_Key($row, 12)) $row = "</taxon>$row";   //scb-0001.txt
            }
            
            */

            /* New: per Jen: https://eol-jira.bibalex.org/browse/DATA-1877?focusedCommentId=65856&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65856
            // remove species sections with < 60 chars long
            if(stripos($row, "<taxon ") !== false) {}   //string is found
            elseif(stripos($row, "</taxon>") !== false) {}   //string is found
            else {
                if($row) { //not blank
                }
            }
            */
            fwrite($WRITE, $row."\n");
            
            /* ===== End of document --- ignore everything that follows from this point ===== */
            if($this->pdf_id == '118946') {
                if($row == "</taxon>Bibliography") break;  //has "</taxon>" bec. it is used as stop pattern above
            }
            if($row == "Plate III. Bonariensis Rambur, Aeshna (Neureclipa)") break; //119187
            
            if($this->pdf_id == '119188') {
                if($row == "Valid names are printed in italics. Page numbers of new taxa are printed") break;
            }
            if($this->pdf_id == '119520') {
                if($row == "</taxon>404 butterflies of liberia") break; //has "</taxon>" bec. it is used as stop pattern above
            }

            // if($this->pdf_id == '91225') { //only during dev --- debug only
            //     if($row == "Dicaeoma Rhamni, 313") break;
            // }
            // if($this->pdf_id == '91362') { //only during dev --- debug only
            //     if($row == "Sphacelotheca Seymouriana, 994") break;
            // }

            if($this->pdf_id == '91362_species') {
                if($row == "REVISED HOST-INDEX TO THE USTILAGINALES") break; 
            }
            /* ===== FUNGI.txt ===== */
            // if($this->pdf_id == '15404') { //only during dev --- debug only
            //     if($row == "Illustration: Univ. Iowa Stud. Nat. Hist. 16: 154.") break;
            //     if($row == "</taxon>Illustration: Univ. Iowa Stud. Nat. Hist. 16: 154.") break;
            // }
            
            // if($this->pdf_id == '15405') { //only during dev --- debug only
            //     if($row == "Order MONOBLEPHARIDALES") break;
            //     if($row == "</taxon>Order MONOBLEPHARIDALES") break;
            // }
            
            // /* used in Fungi list
            if($this->resource_name == 'all_BHL') {
                if(strcmp($row, "CORRECTIONS") == 0) break; //$var1 is equal to $var2 in a case sensitive string comparison
                if(strcmp($row, "</taxon>CORRECTIONS") == 0) break; //$var1 is equal to $var2 in a case sensitive string comparison
                // ---- Plan list ----
                if($row == "extra-limital species") break; //15422
            }
            // */

            
            
            // if($this->pdf_id == '15422') { //only during dev --- debug only
            //     if($row == "Plate l.f.8.") break;
            // }
            
        }//end loop text
        fclose($WRITE);
        fclose($WRITE_st);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
        
        // print_r($this->lines_to_tag);
        return $edited_file;
    }
    private function format_row_to_sciname_v2($row) //Amastus aphraates Schaus, 1927, p. 74.
    {   $showYN = false;
        $row = self::remove_first_word_if_it_has_number($row);
        // if(stripos($row, $this->in_question) !== false) {exit("\nxx[$row]aa00\n");}   //string is found  //good debug
        $row = self::clean_sciname_here($row);
        // if(stripos($row, $this->in_question) !== false) {exit("\nxx[$row]aa11\n");}   //string is found  //good debug
        if(stripos($row, " p. ") !== false) {   //string is found
            $obj = $this->run_gnparser($row);
            if($canonical = @$obj[0]->canonical->full) {
                $row = trim($canonical." ".@$obj[0]->authorship->normalized);
            }
            else {
                print_r($obj); echo("\n-----\nShould not go here...Investigate:\n$row\n"); $showYN = true;
            }
        }
        $row = self::clean_sciname_here2($row);
        if($showYN) echo "\nEnds up with value: [$row]\n-----\n";
        return $row;
    }
    function clean_sciname_here($name)
    {
        // /* criteria 1
        $pos = stripos($name, ", new ");
        if($pos > 5) $name = substr($name, 0, $pos);
        $name = Functions::remove_whitespace($name);
        // */
        // /* criteria
        $pos = stripos($name, ", newspecies");
        if($pos > 5) $name = substr($name, 0, $pos);
        $name = Functions::remove_whitespace($name);
        // */
        // /* Blastobasis indigesta Meyrick, 1931, revised status
        $pos = stripos($name, ", revised ");
        if($pos > 5) $name = substr($name, 0, $pos);
        $name = Functions::remove_whitespace($name);
        // */
        // /* criteria 2 -- for weird names, from Jen:
        $name = self::remove_if_first_chars_are("*", $name, 1); //e.g. *Percnon gibbesi (H. Milne Edwards, 1853)
        $name = self::remove_if_first_chars_are("* ", $name, 2); //e.g. * Percnon gibbesi (H. Milne Edwards, 1853)
        $name = self::remove_if_first_chars_are("?", $name, 1); //e.g. ?Antarctoecia nordenskioeldi Ulmer
        $name = str_replace(array("“", "”"), "", $name); // “Clania” licheniphilus Koehler
        // */
        
        // if(stripos($name, "Capitophorus ohioensis") !== false) exit("\nok 22\n[$name]\n"); //string is found //good debug
        
        if($this->pdf_id == '119520') {} //accept brackets e.g. "[Coeliades bixana Evans]"
        else $name = trim(preg_replace('/\s*\[[^)]*\]/', '', $name)); //remove brackets //rest goes here
        // if(stripos($name, "Capitophorus ohioensis") !== false) exit("\nok 23\n[$name]\n"); //string is found //good debug
        
        // /* remove (.) period if last char
        if(substr($name, -1) == ".") $name = substr($name,0,strlen($name)-1);
        // */
        
        // /* remove "?" question mark
        $name = str_replace("(?)", "", $name);
        $name = str_replace("?", "", $name);
        $name = trim(Functions::remove_whitespace($name));
        // */
        return $name;
    }
    function clean_sciname_here2($name)
    {
        // Gammaropsis digitata (Schellenberg) from Canton Island
        if($name == "Gammaropsis digitata (Schellenberg) from Canton Island") $name = "Gammaropsis digitata (Schellenberg)";
        
        // Elasmopus ?rapax Costa from Eastern Pacific
        if($name == "Elasmopus ?rapax Costa from Eastern Pacific") $name = "Elasmopus ?rapax Costa";

        // Rutiderma rostratum Juday, 1907, emendation
        $name = trim(str_ireplace(", emendation", "", $name));
        
        // Caecidotea nodulus (Williams, 1970) (Maryland specimens)
        $name = trim(str_ireplace("(Maryland specimens)", "", $name));
        return $name;
    }
    private function remove_some_rows($edited_file)
    {   // print_r(pathinfo($edited_file));
        /*Array(
            [dirname] => /Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/scz-0630
            [basename] => scz-0630_edited.txt
            [extension] => txt
            [filename] => scz-0630_edited
        )*/
        $pdf_id = str_replace("_edited", "", pathinfo($edited_file, PATHINFO_FILENAME)); //e.g. "scz-0630"
        
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

            // /* criteria 1
            $cont = true;
            foreach($exclude as $start_of_row) {
                $len = strlen($start_of_row);
                if(substr($row,0,$len) == $start_of_row) {
                    // $rows = array(); //copied template
                    $cont = false; break;
                }
            }
            if(!$cont) continue;
            // */
            
            if($this->pdf_id == '118978') if($row == 'Dicaehis ambigiuis Laferte') continue; //manual
            
            // if($this->pdf_id == '118941') {
                if($this->str_begins_with($row, "(Figs.")) continue;
                if($this->str_begins_with($row, "(Fig.")) continue;
                
            // }

            // if($this->pdf_id == '118935') { //1st doc
            if(in_array($this->pdf_id, array('118935', '30355'))) {
                $row = str_ireplace("[Antennae damaged; abdomen detached. |", "[Antennae damaged; abdomen detached.]", $row);
            }

            if($this->pdf_id == '118237' || $this->resource_name == 'MotAES') { //8th doc
                $words = explode(" ", $row);
                if(is_numeric($row) && count($words) == 1) continue; //entire row is numeric, mostly these are page numbers.
            }

            if($this->resource_name == 'all_BHL' || in_array($this->pdf_id, array('15423', '91155', '15427', //BHL
                                                                                     '118950', '118941'))) { //BHL-like
                
                if($this->pdf_id == '118941') if(stripos($row, "BUCCULATRIX in NORTH AMERICA") !== false) continue; //string is found
                if(stripos($row, "NORTH AMERICAN FLORA [V") !== false) continue; //string is found
                if($this->pdf_id == '91155') if(stripos($row, "SPHAGNACEAE") !== false) continue; //string is found
                if(stripos($row, "Volume") !== false) continue; //string is found
                if(stripos($row, "VoLUMB") !== false) continue; //string is found
                if(stripos($row, "V01.UME") !== false) continue; //string is found
                // /*
                //Part 1, 1913] ANDREAEACEAE 37 -> first word case sensitive comparison == "Part"
                $words = explode(" ", $row);
                $var1 = $words[0];
                $var2 = "Part";
                if (strcmp($var1, $var2) == 0) continue;  //echo "\n$var1 is equal to $var2 in a case sensitive string comparison";
                // else                                     echo "\n$var1 is not equal to $var2 in a case sensitive string comparison";
                // */
            }
            
            if(in_array($this->pdf_id, array('120081', '120082', '118986', '118920', '120083', '118237')) || 
                $this->resource_name == 'MotAES' || $this->resource_name == 'BHL') { //2nd, 4th, 5th 6th 7th 8th docs
                // /* 118986 5th doc
                $ignore = array("MATERIAL EXAMINED", "GEOGRAPHICAL RANGE AND HABITAT PREFERENCES"); //ignore these even if all-caps
                $ignore[] = "REVISION OF SPODOPTERA GUENEE"; //8th doc 118237
                $cont = true;
                foreach($ignore as $start_of_row) {
                    $len = strlen($start_of_row);
                    if(substr($row,0,$len) == $start_of_row) $cont = false;
                }
                if(!$cont) continue;
                // */
                if($cont) {
                    // /* remove if all row's letters are all-caps -> e.g. MEM. AMER. ENT. SOC, IO | e.g. 120 NORTH AMERICAN GENUS PEGOMYIA (DIPTERA: MUSCIDAE) 
                    $tmp = $row;
                    $tmp = str_replace(array("(xj", ",", ".", " ", "-", "'", ":", "(", ")", "&", '2"J', "6l", "/"), "", $tmp);
                    $tmp = preg_replace('/[0-9]+/', '', $tmp); //remove For Western Arabic numbers (0-9):
                    $new_word = array();
                    if($tmp) {
                        for($i = 0; $i <= strlen($tmp)-1; $i++) {
                            $char = $tmp[$i];
                            if(ctype_alpha($char)) $new_word[] = $char;
                        }
                        $new_word = implode("A", $new_word);
                        if(ctype_upper($new_word)) continue;
                    }
                    // */
                }
                
                // /* anywhere in the string, remove
                $cont = true;
                $dont_have_these_chars_anywhere = array("WIIiLlAM", "MEM. AM.", "NORTH AMERICAN ELACHISTIDAE", "ELACHIST1DAE", "ELACH1STIDAE");
                foreach($dont_have_these_chars_anywhere as $char) {
                    if(stripos($row, $char) !== false) $cont = false; //found
                }
                if(!$cont) continue;
                // */
            }

            if(in_array($this->pdf_id, array('118986', '118920' ,'120083'))) { //5th 6th 7th doc
                if($this->str_begins_with($row, 'Figure ')) continue;
                if($this->str_begins_with($row, 'Figure.')) continue;
                if($this->str_begins_with($row, '(Figs.')) continue;
                if(is_numeric($row)) continue;
                if($row) {
                    if($this->row_where_all_words_have_max_three_chars($row)) continue;
                }
                
                // /*
                $cont = true;
                $dont_have_these_chars_anywhere = array("Tj", "•", "■", "♦", "§", "»", "~", "*—", "-^", "«0", "«O", "jqL", "fNiri", "oooooooo", "^^",
                "vooo", ".£", "CAr<", "c4r", "-3-r", "i^o", "*^D", '-"<*', "r<^", "ONTf", "—'0", "c^r", "S.S3", "/ivi", "^h", "r^", "Otj", "©",
                "1-H-H", ",^", "OOONO", "— r-", "—«", "V-)", "— st", "«/", "t«M", "0000", "i—l", "i—", "iip1", "oooo", "i^", "-oo", "m^",
                "Tt—", "^n", ">n", "VI—", "^—^", "c^", ">n");
                foreach($dont_have_these_chars_anywhere as $char) {
                    if(stripos($row, $char) !== false) $cont = false; //found
                }
                if(!$cont) continue;
                // */

                // /*
                $cont = true;
                $dont_have_these_chars_anywhere = array("i&Ttik", "ratio 65.2%");
                foreach($dont_have_these_chars_anywhere as $char) {
                    if(stripos($row, $char) !== false) $cont = false; //found
                }
                if(!$cont) continue;
                // */
                
                if($row == "l st visible") continue;

                if($this->pdf_id == '118986') {
                    // /* these rows are from the vertical oriented pages of the PDF. The converted .txt file is garbage for this part.
                    $cont = true;
                    $remove_rows = array("antennol comb", "palettes", "profemorol setae", "mesofemoral setae", "metatibial spurs", "metatarsal lobes", "mesosternol epimeron", "melasternal epislernum", "prosternal process", "mesocoxa", "metosternal wing", "— epipleuron", "metacoxal file", "abdominal segment", "postcoxal process", "6th visible", "abdominal segment", "oval plate");
                    foreach($remove_rows as $r) {
                        if($row == $r) $cont = false;
                    }
                    if(!$cont) continue;
                    // */
                }

                if($this->pdf_id == '118920') {
                    // /* these rows are from the garbage part of the PDF. The converted .txt file is garbage for this part.
                    $cont = true;
                    $remove_rows = array("collum", "2nd-3rd", "5th-7th", "8th-9th", "-11th", "13th- 14th", "llth-14th", "3rd-4th",
                    "-10th", "-13th", "6th-7th", "5th-8th", "-14th");
                    foreach($remove_rows as $r) {
                        if($row == $r) $cont = false;
                    }
                    if(!$cont) continue;
                    // */
                }

                if($this->pdf_id == '120083') {
                    // /* garbage part
                    $cont = true;
                    $remove_rows = array(";al society");
                    foreach($remove_rows as $r) {
                        if($row == $r) $cont = false;
                    }
                    if(!$cont) continue;
                    // */
                    
                    // /*
                    $cont = true;
                    $dont_have_these_chars_start = array("Figs.");
                    foreach($dont_have_these_chars_start as $char) {
                        if(substr($row,0,strlen($char)) == $char) $cont = false;
                    }
                    if(!$cont) continue;
                    // */
                }
                
                
            }
            fwrite($WRITE, $row."\n");
        }//end loop text
        fclose($WRITE);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
    }
    /*#################################################################################################################################*/
    private function show_parsed_texts_for_mining($edited_file)
    {   // exit("\n$edited_file\n");
        $pdf_id = str_replace("_edited", "", pathinfo($edited_file, PATHINFO_FILENAME)); //e.g. "scz-0630"
        
        $with_blocks_file = str_replace("_edited.txt", "_tagged.txt", $edited_file);
        // /* new
        $with_blocks_file = str_replace("_cutoff", "", $with_blocks_file);
        // */
        $WRITE = fopen($with_blocks_file, "w"); //initialize
        $contents = file_get_contents($edited_file);
        if(preg_match_all("/<taxon (.*?)<\/taxon>/ims", $contents, $a)) {
            // print_r($a[1]); exit;
            foreach($a[1] as $block) {
                
                if($this->resource_name == "MotAES") {
                    if($this->has_AA_BB_CC($block)) continue; //worked OK --- remove species sections inside Keys section
                }
                
                $rows = explode("\n", $block);
                // if(count($rows) >= 5) {
                if(true) {
                    $last_sections_2b_removed = array("Explanation of Plates", "Diagnosis and Discussion. —", "REMARKS.—", "REMARK.—", "REMARKS. ",
                    "AFFINITIES.—", "AFFINITY.—",
                    "DISCUSSIONS.—", "DISCUSSION.—", "Discussion. —",
                    "LIFE HISTORY NOTES.—", "LIFE HISTORY NOTE.—", "NOTES.—", "NOTE.—");
                    $block = self::remove_last_sections($last_sections_2b_removed, $block, $pdf_id);
                    
                    $show = "\n-----------------------\n<$block</sciname>\n-----------------------\n"; // echo $show;
                    /*
                    <sciname='Pontocypria humesi Maddocks (Nosy Bé, Madagascar)'> Pontocypria humesi Maddocks (Nosy Bé, Madagascar)
                    </sciname>
                    */
                    if(self::is_valid_block("<$block</sciname>")) { // echo $show;
                        fwrite($WRITE, $show);
                    }
                    // else echo " -- not valid block -- \n-----\n$block\n-----\n"; //just debug
                }
            }
        }
        fclose($WRITE);
        echo "\nblocks: ".count($a[1])."\n";
    }
    private function species_section_append_pattern($begin, $end, $block)
    {
        if(preg_match("/".preg_quote($begin, '/')."(.*?)".preg_quote($end, '/')."/ims", $block, $a)) {
            $block = str_replace($begin.$a[1], "", $block);
        }
        return $block;
    }
    private function remove_last_sections($sections, $block, $pdf_id)
    {
        // /* for SCtZ-0001 -> remove REMARKS.— but include sections after it e.g. DISTRIBUTION.—
        $begin = "REMARKS.—";       $end = "DISTRIBUTION.—"; //works also but better to use "\n". Or maybe case to case basis.
        $block = self::species_section_append_pattern($begin, $end, $block);
        // */
        // /* for SCtZ-0007 -> remove AFFINITIES.— but include sections after it e.g. DISTRIBUTION.—
        $begin = "AFFINITIES.—";    $end = "DISTRIBUTION.—"; //works also but better to use "\n". Or maybe case to case basis.
        $block = self::species_section_append_pattern($begin, $end, $block);
        // */
        // /* for SCtZ-0023 -> remove DISCUSSION.— but include sections after it e.g. VARIATION.—
        $begin = "DISCUSSION.—";    $end = "VARIATION.—"; //works also but better to use "\n". Or maybe case to case basis.
        $block = self::species_section_append_pattern($begin, $end, $block);
        // */
        // /* for scz-0630 -> remove REMARKS. but include sections after it e.g. TYPE SPECIES.—
        if($pdf_id == "scz-0630") {
            $begin = "REMARKS. ";   $end = "TYPE SPECIES. "; //works also but better to use "\n". Or maybe case to case basis.
            $block = self::species_section_append_pattern($begin, $end, $block);
        }
        // */
        
        // /* SCtZ-0607 -> remove DISCUSSION.— but include DISTRIBUTION AND GEOGRAPHIC VARIATION.—
        $begin = "DISCUSSION.—";    $end = "DISTRIBUTION AND GEOGRAPHIC VARIATION.—";
        $block = self::species_section_append_pattern($begin, $end, $block);
        // */
        
        // /* SCtZ-0594
        $begin = "REMARKS.—";    $end = "MATERIAL EXAMINED.—";
        $block = self::species_section_append_pattern($begin, $end, $block);
        
        $begin = "REMARKS.—";    $end = "ADULT.—";
        $block = self::species_section_append_pattern($begin, $end, $block);

        $begin = "REMARKS.—";    $end = "MATERIAL.—";
        $block = self::species_section_append_pattern($begin, $end, $block);
        // */

        foreach($sections as $section) {
            $str = "elicha".$block;
            if(preg_match("/elicha(.*?)".preg_quote($section, '/')."/ims", $str, $a2)) $block = $a2[1];
        }
        return $block;
    }
    private function is_valid_block($block)
    {
        if(preg_match("/<sciname=\'(.*?)\'/ims", $block, $a)) {
            $sciname = $a[1];
            // if(stripos($sciname, $this->in_question) !== false) {exit("\nxx[$sciname]mm11\n");}   //string is found  //good debug
            if(!self::has_species_string($sciname)) {
                // if(stripos($sciname, $this->in_question) !== false) {exit("\nxx[$sciname]mm22\n");}   //string is found  //good debug
                
                if(self::is_sciname_we_want($sciname)) {
                    // if(stripos($sciname, $this->in_question) !== false) {exit("\nxx[$sciname]mm33\n");}   //string is found  //good debug

                    $contents = Functions::remove_whitespace(trim(strip_tags($block)));
                    $word_count = self::get_number_of_words($contents);
                    
                    /* word count filter doesn't make sense at this point.
                    if($this->filename == 'SCtZ-0007.txt') //2nd PDF
                        if($word_count < 100) return false;
                    elseif($this->filename == 'elix') {}
                    else { //SCtZ-0293_convertio.txt goese here, our 1st PDF
                    }
                    */
                    
                    if($sciname == $contents) return false;
                }
                else return false;
            }
            else return false;
        }
        
        // /* entire species section should not be < 60 chars long
        $tmp = Functions::remove_whitespace(trim($block));
        $tmp = str_replace("\n\n\n\n", "\n\n", $tmp);
        $tmp = str_replace("\n\n\n", "\n\n", $tmp);
        $tmp = str_replace("\n\n\n", "\n\n", $tmp);
        $tmp = str_replace("\n\n\n", "\n\n", $tmp);
        $tmp = str_replace("\n\n\n", "\n\n", $tmp);
        $tmp = str_replace("\n", "<br>", $tmp);
        $arr = explode("<br><br>", $tmp);
        array_shift($arr); //print_r($arr);
        $tmp = implode("<br>", $arr);
        $tmp = strip_tags($tmp);
        
        // if(strlen($tmp) < 60) return false; //this is dangerous
        
        // echo "\n--------------------------------xxx\n".$tmp."\n--------------------------------yyy\n";
        // */
        
        @$this->debug['sciname cnt']++;
        debug("\n[$sciname] ". $this->debug['sciname cnt'] . " - Word count: ".$word_count."\n");
        return true;
    }
    private function get_number_of_words($contents)
    {
        $arr = explode(" ", $contents);
        return count($arr);
    }
    private function is_sciname_we_want($sciname)
    {   
        // /*
        if($numbers = self::get_numbers_from_string($sciname)) { //if there is a single digit or 2-digit or 3-digit number in string then not sciname.
            foreach($numbers as $num) {
                if(strlen($num) <= 3) {
                    if(stripos($sciname, " species $num") !== false) return false; //e.g. "Pontocypris species 1" //string is found
                }
            }
        }
        // */
        
        // /*
        foreach($this->ranks as $rank) {
            $len = strlen($rank);
            if(substr($sciname,0,$len) == $rank) return false;
        }
        // */
        return true;
    }
    function remove_first_word_if_it_has_number($string)
    {
        $words = explode(" ", $string); // print_r($words); exit;
        if(self::get_numbers_from_string($words[0])) { //first word has number(s)
            array_shift($words);
            $string = implode(" ", $words);
        }
        return $string;
    }
    private function remove_if_first_chars_are($char, $name, $length)
    {
        // if(substr($name,0,1) == $char) $name = trim(substr($name,1,strlen($name))); //e.g. ?Antarctoecia nordenskioeldi Ulmer
        if(substr($name,0,$length) == $char) $name = trim(substr($name,$length,strlen($name))); //e.g. ?Antarctoecia nordenskioeldi Ulmer
        return $name;                                                                           //e.g. * Enteromorpha clathrata (Roth) J. Agardh
    }
    private function N_words_or_less_beginning_with_Key($row, $no_of_words)
    {
        $words = explode(" ", $row);
        if($words[0] == "Key") {
            if(count($words) <= $no_of_words) return true;
        }
        return false;
    }
    function utility_download_txt_files() //for Mac mini only
    {   //SCtZ-0001
        $pdf_ids = array("SCtZ-0008", "SCtZ-0016", "SCtZ-0023", "SCtZ-0030", "SCtZ-0038", "SCtZ-0045", "SCtZ-0053", "SCtZ-0060", "SCtZ-0067", "SCtZ-0074", "SCtZ-0082", "SCtZ-0089", "SCtZ-0096",
        "SCtZ-0002", "SCtZ-0009", "SCtZ-0017", "SCtZ-0024", "SCtZ-0031", "SCtZ-0039", "SCtZ-0046", "SCtZ-0054", "SCtZ-0061", "SCtZ-0068", "SCtZ-0075", "SCtZ-0083", "SCtZ-0090", "SCtZ-0099",
        "SCtZ-0003", "SCtZ-0011", "SCtZ-0018", "SCtZ-0025", "SCtZ-0032", "SCtZ-0040", "SCtZ-0047", "SCtZ-0055", "SCtZ-0062", "SCtZ-0069", "SCtZ-0076", "SCtZ-0084", "SCtZ-0091", "SCtZ-0100",
        "SCtZ-0004", "SCtZ-0012", "SCtZ-0019", "SCtZ-0026", "SCtZ-0034", "SCtZ-0041", "SCtZ-0048", "SCtZ-0056", "SCtZ-0063", "SCtZ-0070", "SCtZ-0077", "SCtZ-0085", "SCtZ-0092", "SCtZ-0104",
        "SCtZ-0005", "SCtZ-0013", "SCtZ-0020", "SCtZ-0027", "SCtZ-0035", "SCtZ-0042", "SCtZ-0049", "SCtZ-0057", "SCtZ-0064", "SCtZ-0071", "SCtZ-0078", "SCtZ-0086", "SCtZ-0093", "SCtZ-0106",
        "SCtZ-0006", "SCtZ-0014", "SCtZ-0021", "SCtZ-0028", "SCtZ-0036", "SCtZ-0043", "SCtZ-0051", "SCtZ-0058", "SCtZ-0065", "SCtZ-0072", "SCtZ-0080", "SCtZ-0087", "SCtZ-0094", "SCtZ-0107",
        "SCtZ-0007", "SCtZ-0015", "SCtZ-0022", "SCtZ-0029", "SCtZ-0037", "SCtZ-0044", "SCtZ-0052", "SCtZ-0059", "SCtZ-0066", "SCtZ-0073", "SCtZ-0081", "SCtZ-0088", "SCtZ-0095", "SCtZ-0111");
        foreach($pdf_ids as $id) {
            // https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0001/SCtZ-0007.txt
            // destination:    [/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/SCtZ-0007.txt]
            $txt_url = "https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/".$id."/".$id.".txt";
            $path = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/".$id."/";
            if(!is_dir($path)) mkdir($path);
            else continue; //so not to process thosed processed already
            $destination = $path.$id.".txt";
            $cmd = "wget -nc ".$txt_url." -O $destination";
            $cmd .= " 2>&1";
            $json = shell_exec($cmd);
            echo "\n$json\n";
            sleep(3);
            // break; //debug only
        }
    }
}
?>