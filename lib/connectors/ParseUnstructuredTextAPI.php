<?php
namespace php_active_record;
/* */
class ParseUnstructuredTextAPI extends ParseListTypeAPI
{
    function __construct()
    {
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);

        /* START epub series */
        // $this->path['epub_output_txts_dir'] = '/Volumes/AKiTiO4/other_files/epub/'; //dir for converted epubs to txts
        // $this->service['GNRD text input'] = 'httpz'; //'http://gnrd.globalnames.org/name_finder.json?text=';
        $this->service['GNParser'] = "https://parser.globalnames.org/api/v1/";
        $this->service['GNVerifier'] = "https://verifier.globalnames.org/api/v1/verifications/";
        /*
        https://parser.globalnames.org/api/v1/Periploca+hortatrix%2C+new+species
        https://parser.globalnames.org/api/v1/HOSTS (Table 1).—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens (Alt.) J.E. Smith also being mined (Braun, 1908a). Populus balsamifera L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.
        https://parser.globalnames.org/api/v1/Thespesia banalo Blanco, Fl. Filip. ed. 2, 382, 1845

        https://parser.globalnames.org/?q=https://parser.globalnames.org/api/v1/HOSTS (Table 1).—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens (Alt.) J.E. Smith also being mined (Braun, 1908a). Populus balsamifera L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.

        http://gnrd.globalnames.org/name_finder.json?text=Tiphia paupi Allen and Krombein, 1961
        http://gnrd.globalnames.org/name_finder.json?text=Tiphia (Tiphia) uruouma
        http://gnrd.globalnames.org/name_finder.json?text=Eunice segregate (Chamberlin, 1919a) restricted
        http://gnrd.globalnames.org/name_finder.json?text=Stilbosis lonchocarpella Busck, 1934, p. 157.
        */
        /* index key here is the lines_before_and_after_sciname */
        $this->no_of_rows_per_block[2] = 5; //orig, first sample epub (SCtZ-0293_convertio.txt)
        $this->no_of_rows_per_block[1] = 3; //orig, first sample epub (SCtZ-0293_convertio.txt)
        /* END epub series */
        
        // /* copied from SmithsonianPDFsAPI
        $list_type_from_google_sheet = array('SCtZ-0033', 'SCtZ-0011', 'SCtZ-0010', 'SCtZ-0611', 'SCtZ-0613', 'SCtZ-0609', 'SCtZ-0018',
        'scb-0002');
        $this->PDFs_that_are_lists = array_merge(array('SCtZ-0437'), $list_type_from_google_sheet);
        // SCtZ-0018 - Nearctic Walshiidae: notes and new taxa (Lepidoptera: Gelechioidea). Both list-type and species-sections type
        // SCtZ-0004 - not a list-type
        // SCtZ-0604 - I considered not a list-type but a regular species section type
        // */
        
        $this->assoc_prefixes = array("HOSTS", "HOST", "PARASITOIDS", "PARASITOID");
        
        // /* for gnfinder
        if(Functions::is_production()) $this->json_path = '/var/www/html/gnfinder/'; //'/html/gnfinder/';
        else                           $this->json_path = '/Volumes/AKiTiO4/other_files/gnfinder/';
        // */
    }
    /* Special chard mentioned by Dima, why GNRD stops running.
    str_replace("")
    str_replace("")
    */
    /*#################################################################################################################################*/
    function parse_pdftotext_result($input) //Mar 25, 2021 - start epub series
    {
        // print_r($input); print_r(pathinfo($input['filename'])); exit("\nelix 1\n");
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
        if(in_array($pdf_id, $this->PDFs_that_are_lists)) {
            echo "- IS A LIST, NOT SPECIES-DESCRIPTION-TYPE 02\n";
            $this->parse_list_type_pdf($input);
            if(in_array($pdf_id, array('SCtZ-0437', 'SCtZ-0010', 'SCtZ-0611', 'SCtZ-0609', 'scb-0002'))) return; //many lists have bad species sections
            elseif(in_array($pdf_id, array('SCtZ-0613'))) {} //lists with good species sections
            // 
            // return; //should be commented coz some list-type docs have species sections as well
        }
        // */
        
        if($val = $input['epub_output_txts_dir']) $this->path['epub_output_txts_dir'] = $val;
        
        $this->lines_to_tag = array();
        $this->scinames = array();
        
        $filename = $input['filename'];
        $this->filename = $filename; //for referencing below
        $lines_before_and_after_sciname = $input['lines_before_and_after_sciname'];
        $this->magic_no = $this->no_of_rows_per_block[$lines_before_and_after_sciname];
        self::get_main_scinames($filename); // print_r($this->lines_to_tag); 
        echo "\n lines_to_tag: ".count($this->lines_to_tag)."\n"; //exit("\n-end-\n");
        $edited_file = self::add_taxon_tags_to_text_file_v3($filename);
        self::remove_some_rows($edited_file);
        self::show_parsed_texts_for_mining($edited_file);
        // print_r($this->scinames); 
        echo "\nRaw scinames count: ".count($this->scinames)."\n";
    }
    //else           $row = "</taxon><taxon sciname='$sciname'> ".$row;
    private function get_main_scinames($filename)
    {
        $local = $this->path['epub_output_txts_dir'].$filename;
        
        /* start NEW ========================= */
        // /* remvoe 'Included species' sections. First client SCtZ-0007
        $local = $this->path['epub_output_txts_dir'].$filename;
        $cleaned_file = str_replace(".txt", "_cleaned.txt", $local);
        $contents = file_get_contents($local); //exit("\n$contents\n");
        if(preg_match_all("/included species(.*?)\n\n\n/ims", $contents, $a)) {
            // print_r($a[1]); exit("\ngot it\n");
            foreach($a[1] as $delete_this) {
                $contents = str_ireplace($delete_this, "", $contents);
            }
            $WRITE = fopen($cleaned_file, "w"); //initialize
            fwrite($WRITE, $contents."\n");
            fclose($WRITE);
            $local = $cleaned_file;
        }
        // */
        /* ========================= end NEW */

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

            $row = trim(preg_replace('/\s*\[[^)]*\]/', '', $row)); //remove brackets
            $row = trim($row);
            
            // /*
            if(stripos($row, "fig.") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, "incertae sedis") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, "cf.") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, "Figure") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " and ") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " cm ") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " p. ") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " pp.") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " pages") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, " less ") !== false) {$rows = array(); continue;} //string is found
            if(stripos($row, "series ") !== false) {$rows = array(); continue;} //string is found
            // */
            
            // /* customize
            if($pdf_id == 'SCtZ-0604') {
                if($row == "Ahl, E.") break; //so no more running GNRD for later part of the document
            }
            elseif($pdf_id == 'scb-0001') {
                if($row == "Alderwerelt van Rosenburgh, C. R. W. K. van") break; //so no more running GNRD for later part of the document
            }
            // */

            $cont = true;
            // /* criteria 1
            foreach($exclude as $start_of_row) {
                $len = strlen($start_of_row);
                // echo "\n$row\n".substr($row,0,$len)." == ".$start_of_row."\n"; //good debug
                if(substr($row,0,$len) == $start_of_row) {
                    $rows = array();
                    $cont = false; break;
                }
            }
            if(!$cont) continue;
            // */
            
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

            /* good debug
            //Capitophorus ohioensis Smith, 1940:141
            //Capitophorus ohioensis Smith, 1940:141 [type: apt.v.f., Columbus, Ohio, 15–X–1938, CFS, on Helianthus; in USNM].
            if(stripos($row, "Capitophorus ohioensis") !== false) exit("\nok 3\n[$row]\n"); //string is found
            */
            
            $cont = true;
            // /* criteria 3: any occurrence of these strings in any part of the row
            $exclude2 = array(" of ", " in ", " the ", " this ", " with ", "Three ", "There ", " are ", "…", " for ", " dos ", " on ");
            $exclude2 = array_merge($exclude2, array('order', 'family', 'subgenus', 'tribe')); //is valid "Anoplodactylus lagenus"
            foreach($exclude2 as $exc) {
                if(stripos($row, $exc) !== false) { //string is found
                    $rows = array();
                    $cont = false; break;
                }
            }
            if(!$cont) continue;
            // */

            //for weird names, from Jen
            $row = str_replace(array("“", "”"), "", $row); // “Clania” licheniphilus Koehler --> 0188.epub
            
            /* good debug
            if(stripos($row, "Thespesia howii") !== false) print("\nok 4\n[$row]\n"); //string is found
            */

            $rows[] = $row;
            $rows = self::process_magic_no($this->magic_no, $rows, $ctr);
        }
        // */
    }
    private function process_magic_no($magic_no, $rows, $ctr)
    {
        if($magic_no == 5) {
            if(count($rows) == 5) { //start evaluating records of 5 rows
                if(!$rows[0] && !$rows[1] && !$rows[3] && !$rows[4]) {
                    if($rows[2]) {
                        
                        // if(stripos($rows[2], "Capitophorus ohioensis") !== false) exit("\nok 2\n"); //string is found //good debug
                        
                        $words = explode(" ", $rows[2]);
                        if(count($words) <= 9)  { //orig is 6
                            
                            // if(stripos($rows[2], "Capitophorus ohioensis") !== false) exit("\nok 1\n".$rows[2]."\n"); //string is found //good debug
                            // echo "\n$rows[2] -- ";
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
                        }
                    }
                }
                array_shift($rows); //remove 1st element, once it reaches 5 rows.
            }
            return $rows;
        }
    }
    function get_numbers_from_string($str)
    {
        if(preg_match_all('/\d+/', $str, $a)) return $a[0];
    }
    function is_sciname($string, $doc_type = 'species_type') //for initial scinames list
    {
        
        $string = self::remove_if_first_chars_are("* ", $string, 2); //e.g. "* Enteromorpha clathrata (Roth) J. Agardh"
        
        $exclude = array("The ", "This ", "When "); //starts with these will be excluded, not a sciname
        foreach($exclude as $exc) {
            if(substr($string,0,strlen($exc)) == $exc) return false;
        }
        
        /*
        Hernán Ortega and Richard P. Vari
        Review of the Classification of the Rhinocryptidae and Menurae
        Helmut W. Zibrowius, Station Marine d’Endoume, Marseille, France.
        */
        $exclude = array("Hernán", "Review ", "Helmut W.", "List ", "Key ", " and ");
        foreach($exclude as $exc) {
            /* for the longest time
            if(substr($string,0,strlen($exc)) == $exc) return false;
            */
            // /* new 
            if(stripos($string, $exc) !== false) return false; //string is found
            // */
        }
        
        // /* e.g. "16a. Cerceris bougainvillensis solomonis, new subspecies" --- remove "16a."
        $string = self::remove_first_word_if_it_has_number($string);
        // */

        // echo "\nreach 1\n";
        
        // /* If there is a 2nd word, it should start with a lower case letter. That is if not "(". e.g. Enallopsammia Michelotti, 1871
        $words = explode(" ", $string);
        if($second_word = @$words[1]) { //there is 2nd word
            
            if($second_word == 'de') return false; //e.g. Dendrophyllia de Blainville, 1830
            elseif($second_word == 'd’Orbigny') return false; //e.g. Dactylosmilia d’Orbigny 1849
            elseif($second_word == 'new') return false; //e.g. Pourtalopsammia new genus
            
            $first_char_of_2nd_word = substr($second_word,0,1);
            $second_char_of_2nd_word = substr($second_word,1,1);
            if($first_char_of_2nd_word != "(" && ctype_upper($first_char_of_2nd_word)) return false; //Psammoseris Milne Edwards and Haime, 1851
            if($first_char_of_2nd_word == "(") { //Balanophyllia (Balanophyllia) Wood, 1844
                if($third_word = @$words[2]) { //there is a 3rd word
                    $first_char_of_3rd_word = substr($third_word,0,1);
                    if($first_char_of_3rd_word != "(" && ctype_upper($first_char_of_3rd_word)) return false; //first_char_of_3rd_word is upper case
                }
            }
        }
        // */

        // echo "\nreach 2 [$string]\n";

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
        // for weird names, form Jen
        $string = str_replace("‘", "'", $string);
        $string = str_replace("’", "'", $string);

        $names = $this->get_names_from_gnfinder($string);
        if($names) return true;
        else return false;
        exit("\nstop using 001\n");

        /* from GNRD
        http://gnrd.globalnames.org/name_finder.json?text=A+spider+named+Pardosa+moesta+Banks,+1892
        http://gnrd.globalnames.org/name_finder.json?text=boggianii Régimbart 00–526 (Paraguay)
        */
        $url = $this->service['GNRD text input'].$string; debug("\nGNRD 1: [$url]\n");
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
        if(stripos($row, " sp") !== false) return true;  //string is found
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
        // /*
        if(stripos($str, " species ") !== false) return false;  //string is found --- exclude "Synasterope species A" --- 0032
        if(substr($str, -8) == " species") return false;  //string is found --- 0034
        if(substr($str, -4) == " spp") return false;  //string is found --- 0067
        if(stripos($str, "Especies") !== false) return false;  //string is found --- exclude "Clave para las Especies de Farrodes Peters, nuevo género" --- 0062
        if(stripos($str, "fig.") !== false) return false;
        if(strtolower(substr($str, -11)) == " subspecies") return false;  //string is found ---	GENUS SPECIES Subspecies
        // */

        // /* intended to be removed: 10088_5097
        // "Species unplaced to group"
        if(stripos($str, "unplaced") !== false) return false;  //string is found
        // */
        
        /*
        63af40db0f8a7678c68804077364d4da	Metachroma

        6c827cf2d2b7cf1fde183d403d3a0069	Cerceris
        
        fa74cd32dbb9226cd22754a3fb2d5e7e	Pison
        ab2c88e05686b58d73d814992a45fba4	Trypoxylon
        
        7f95fef5c511b6e996d24ce6d252dae7	Brevipalpus
        
        874a71637e34eb4d6325c6d6f242896c	Ethmia
        
        c2d52b066ecfe841a8a387c66cff3463	Syrrhoites
        
        d42affb509cc5d1b1ee03fced5c8962b	Plutonaster
        
        7ef3607f0a2b2d17486c03b99bdde64f	Pectinaster
        
        86ec3c7bedcb1a72af2504763b8713f3	Circeaster
        
        */
        
        // /*
        $words = explode(" ", $str);
        $words = array_map('trim', $words);
        if(count($words) == 1) return false; //beceee5e9c6734374d0ee01d1ee03c2a	Isomyia
        if(count($words) == 2) { //493cff8f65ec17fe2c3a5974d8ac1803	Euborellia (Dohrn)
            $first_char_2nd_word = substr($words[1],0,1);
            if(is_numeric($first_char_2nd_word)) return false;
            if(ctype_upper($first_char_2nd_word)) return false; //06a2940e6881040955101a68e88c1f9c  Careospina Especies de Careospina Peters
            if($first_char_2nd_word == "(") return false;
        }
        // */
        
        // /* criteria 1
        $words = explode(" ", $str);
        $second_word = @$words[1];
        if(!$second_word) return false; //No 2nd word
        else {
            if(ctype_upper(substr($words[1],0,1))) return false; //2nd word is capitalized
        }
        // */
        
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
        $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
        foreach($ranks as $rank) {
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
        copy($local, $edited_file);
        // */

        $WRITE = fopen($temp_file, "w"); //initialize
        $hits = 0;
        
        // /* loop text file
        $i = 0; $count_of_blank_rows = 0;
        foreach(new FileIterator($edited_file) as $line => $row) { $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            
            // /* manual adjustment
            if($filename == "SCtZ-0063.txt") $row = str_ireplace("Euborellia ståli", "Euborellia stali", $row);
            // */
            
            if(!$row) $count_of_blank_rows++;
            else      $count_of_blank_rows = 0;
            
            if(isset($this->lines_to_tag[$i])) { $hits++;
                $row = self::format_row_to_sciname_v2($row); //fix e.g. "Amastus aphraates Schaus, 1927, p. 74."
                if(self::is_valid_species($row)) { //important last line
                    
                    // if(stripos($row, "45,") !== false) {exit("\nxx[$row]xx\n");}   //string is found  //good debug
                    
                    $sciname = self::last_resort_to_clean_name($row);
                    
                    $words = explode(" ", $sciname);
                    if(count($words) > 1) {
                        if($hits == 1)  $row = "<taxon sciname='$sciname'> ".$row;
                        else            $row = "</taxon><taxon sciname='$sciname'> ".$row;
                    }
                    else {
                        if($hits == 1)  $row = "<taxon sciname='$row'> ".$row;
                        else            $row = "</taxon><taxon sciname='$row'> ".$row;
                    }
                    
                }
            }
            // else echo "\n[$row]\n";

            // /* to close tag the last block
            if($row == "Appendix") $row = "</taxon>$row";                   //SCtZ-0293_convertio.txt
            elseif($row == "Literature Cited") $row = "</taxon>$row";       //SCtZ-0007.txt
            elseif($row == "References") $row = "</taxon>$row";             //SCtZ-0008.txt
            elseif($row == "General Conclusions") $row = "</taxon>$row";    //SCtZ-0029.txt
            elseif($row == "Bibliography") $row = "</taxon>$row";           //SCtZ-0011.txt
            elseif($row == "Bibliography: p.") $row = "</taxon>$row";       //scb-0007.txt
            elseif($row == "Zoogeography of the Species of the Genus Nannobrachium") $row = "</taxon>$row"; //SCtZ-0607.txt
            elseif($row == "The Achirus Group") $row = "</taxon>$row";                                      //SCtZ-0607.txt
            elseif($row == "Summary and Conclusions") $row = "</taxon>$row";
            elseif($row == "Appendix Tables") $row = "</taxon>$row";
            elseif($row == "Intermediates") $row = "</taxon>$row";          //scb-0007.txt

            elseif($row == "Dubious Binomials") $row = "</taxon>$row";                          //scb-0001.txt
            elseif($row == "Excluded Species") $row = "</taxon>$row";                           //scb-0001.txt

            elseif($row == "Glossary") $row = "</taxon>$row";                                   //scb-0009.txt
            elseif($row == "Aulonemia Goudot") $row = "</taxon>$row";                           //scb-0009.txt
            elseif($row == "MUSEUM D’HISTOIRE NATURELLE DE PARIS") $row = "</taxon>$row";       //scb-0009.txt
            elseif($row == "Arthrostylidium Ruprecht") $row = "</taxon>$row";                   //scb-0009.txt
            elseif($row == "POLYTRICHACEAE") $row = "</taxon>$row";                             //scb-0027.txt
            elseif($row == "Acknowledgments") $row = "</taxon>$row";                            //scb-0094.txt
            elseif($row == "Descriptive Biogeography") $row = "</taxon>$row";                   //SCtZ-0608.txt

            if($filename == "SCtZ-0612.txt") {
                if($row == "Figures") $row = "</taxon>$row";                                   //SCtZ-0612.txt
            }
            
            if($filename == "SCtZ-0606.txt") {
                if(stripos($row, "species indeterminate") !== false) $row = "</taxon>$row";   //string is found
            }
            
            // if($filename == "scb-0094.txt") { --> can be for all
                if(self::one_word_and_higher_taxon($row)) $row = "</taxon>$row";                //scb-0094.txt
            // }

            if(self::two_words_rank_and_sciname_combo($row)) $row = "</taxon>$row";             //for all
            // Tribe Beckerinini newline

            if($filename != "scb-0013.txt") {
                if(self::N_words_or_less_beginning_with_Key($row, 12)) $row = "</taxon>$row";   //scb-0001.txt
            }

            if(self::sciname_then_specific_words($row, "Excluded Taxa")) $row = "</taxon>$row"; //for all
            // e.g. "Isopterygium Excluded Taxa"

            if(self::numbered_then_sciname($row)) $row = "</taxon>$row"; //for all
            // e.g. "2. Elmeriobryum Broth."
            
            // if($count_of_blank_rows >= 5) $row = "</taxon>";       //scb-0009.txt --- NOT A GOOD IDEA
            // */

            // /* New: per Jen: https://eol-jira.bibalex.org/browse/DATA-1877?focusedCommentId=65856&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65856
            // remove species sections with < 60 chars long
            if(stripos($row, "<taxon ") !== false) {}   //string is found
            elseif(stripos($row, "</taxon>") !== false) {}   //string is found
            else {
                if($row) { //not blank
                    /* < 60 chars long rule only for the entire species section, not for individual species sections
                    if(strlen($row) < 60) { //will be removed coz its short: "HOST.—Helian thus." but should not for Assoc prefixes
                        $cont = false;
                        foreach($this->assoc_prefixes as $start_of_row) {
                            if(substr($row,0,strlen($start_of_row)) == $start_of_row) {$cont = true; break;}
                        }
                        if(!$cont) continue;
                    }
                    */
                }
            }
            // */
            
            fwrite($WRITE, $row."\n");
        }//end loop text
        fclose($WRITE);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
        
        // print_r($this->lines_to_tag);
        return $edited_file;
    }
    private function format_row_to_sciname_v2($row) //Amastus aphraates Schaus, 1927, p. 74.
    {
        $row = self::remove_first_word_if_it_has_number($row);
        
        $row = self::clean_sciname_here($row);
        if(stripos($row, " p. ") !== false) {   //string is found
            $obj = $this->run_gnparser($row);
            if($canonical = @$obj[0]->canonical->full) {}
            else {
                print_r($obj); exit("\nShould not go here...\n$row\n");
            }
            $row = trim($canonical." ".@$obj[0]->authorship->normalized);
        }
        $row = self::clean_sciname_here2($row);
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
        $name = trim(preg_replace('/\s*\[[^)]*\]/', '', $name)); //remove brackets
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
            
            $cont = true;
            // /* criteria 1
            foreach($exclude as $start_of_row) {
                $len = strlen($start_of_row);
                if(substr($row,0,$len) == $start_of_row) {
                    // $rows = array(); //copied template
                    $cont = false; break;
                }
            }
            if(!$cont) continue;
            // */
            
            // /* big customization:
            // if($pdf_id == "scz-0630") {
                if(stripos($row, "Abbreviations defined:") !== false) continue; //string is found
            // }
            // */
            
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
        $WRITE = fopen($with_blocks_file, "w"); //initialize
        $contents = file_get_contents($edited_file);
        if(preg_match_all("/<taxon (.*?)<\/taxon>/ims", $contents, $a)) {
            // print_r($a[1]);
            foreach($a[1] as $block) {
                $rows = explode("\n", $block);
                // if(count($rows) >= 5) {
                if(true) {
                    $last_sections_2b_removed = array("REMARKS.—", "REMARK.—", "REMARKS. ",
                    "AFFINITIES.—", "AFFINITY.—",
                    "DISCUSSIONS.—", "DISCUSSION.—",
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
                    // else echo " -- not valid block"; //just debug
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
            
            if(!self::has_species_string($sciname)) {
                if(self::is_sciname_we_want($sciname)) {

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
        if(strlen($tmp) < 60) return false;
        // echo "\n--------------------------------xxx\n".$tmp."\n--------------------------------yyy\n";
        // */
        
        @$this->debug['sciname cnt']++;
        if($GLOBALS['ENV_DEBUG']) {
            echo "\n[$sciname] ". $this->debug['sciname cnt'];
            echo " - Word count: ".$word_count."\n";
        }
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
        $ranks = array("Genus", "Family", "Order", "Suborder", "Subgenus", "Superfamily", "Subfamily", "? Subfamily");
        foreach($ranks as $rank) {
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