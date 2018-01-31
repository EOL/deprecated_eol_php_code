<?php
namespace php_active_record;
/* connector: copepods.php */
class MarineCopepodsAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        // $this->taxon_ids = array();
        // $this->occurrence_ids = array();
        // $this->media_ids = array();
        // $this->agent_ids = array();
        $this->debug = array();
        $this->download_options = array("timeout" => 60*60, "expire_seconds" => 60*60*24*25, "resource_id" => "MPC"); //marine planktonic copepods
        $this->page['species'] = "https://copepodes.obs-banyuls.fr/en/fichesp.php?sp=";
        $this->page['ref_list'] = "https://copepodes.obs-banyuls.fr/en/ref_auteursd.php";
        $this->page['biblio_1'] = "https://copepodes.obs-banyuls.fr/en/biblio_pre.php?deb=";
        $this->page['biblio_2'] = "https://copepodes.obs-banyuls.fr/en/biblio.php?deb=";
        $this->bibliographic_citation = "Razouls C., de Bovée F., Kouwenberg J. et Desreumaux N., 2005-2017. - Diversity and Geographic Distribution of Marine Planktonic Copepods. Available at http://copepodes.obs-banyuls.fr/en";
        $this->resource_reference_ids = array();
    }
    
    private function get_ref_minimum()
    {
        $final = array();
        if($html = Functions::lookup_with_cache($this->page['ref_list'], $this->download_options)) {
            if(preg_match("/InstanceBeginEditable name=\"Contenu\"(.*?)<\/table>/ims", $html, $a1)) {
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $a1[1], $a2)) {
                    foreach($a2[1] as $r) {
                        if(preg_match_all("/<div (.*?)<\/div>/ims", $r, $a3)) {
                            $div = array();
                            foreach($a3[1] as $s) {
                                $s = strip_tags("<div ".$s);
                                $div[] = $s;
                            }
                            $div[0] = str_replace("-", "", $div[0]);
                            $div = array_map('trim', $div);
                            // print_r($div);
                            $final[$div[0]] = @$div[1];
                        }
                    }
                }
            }
            else exit("\nInvestigate: No ref 1\n");
        }
        // print_r($final);
        return $final;
    }
    private function get_ref_maximum($what, $letter)
    {
        $final = array();
        if($html = Functions::lookup_with_cache($this->page[$what].$letter, $this->download_options)) {
            if(preg_match("/<a name\=$letter>(.*?)<\/table>/ims", $html, $a1)) {
                // exit("\n".$a1[1]."\n");
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $a1[1], $a2)) {
                    foreach($a2[1] as $r) {
                        $r = strip_tags($r, "<em>");
                        // echo "\n[$r]\n";
                        $parts = explode(". - ", $r);
                        $final[$parts[0]] = @$parts[1];
                    }
                }
                else exit("\nInvestigate: No ref 4\n");
            }
            else exit("\nInvestigate: No ref 3\n");
        }
        else exit("\nInvestigate: No ref 2\n");
        // print_r($final); exit;
        return $final;
    }
    private function get_fullreference_by_refno($refno)
    {
        $refno_author_list = self::get_ref_minimum();
        if($str = $refno_author_list[$refno]) {
            echo "\n[$refno] [$str]\n"; //[65] [Sars, 1903] | [67] [Grice & Hulsemann, 1967] []
            
            //manual: special cases
            if($refno == 22) $str = "Brodsky, 1967"; //22 - Brodsky, 1950 (1967)
            if($refno == 46) $str = 'Giesbrecht, ["1892"]'; //46 - Giesbrecht, 1892
            if($refno == 138) $str = "Bowshall, 1979"; //138 - Boxshall, 1979     ---> this is typo on their website
            if($refno == 67) $str = "Hulsemann & Grice, 1964"; //"Hulsemann K. & Grice G.D., 1964"; //67 - Grice & Hulsemann, 1967
            if($refno == 226) $str = "Hulsemann & Grice, 1964";                                    //226 - Grice & Hulsemann, 1965
            if($refno == 100) $str = "Hulsemann & Grice, 1964";                                    //100 - Grice & Hulsemann, 1968

            if($refno == 1) $str = "Sars, 1924-1925"; //"Sars G.O., 1924-1925" //1 - Sars, 1925
            if($refno == 262) $str = "Guangshan & Honglin, 1997"; // 262 - Guangshan & Honglin, 1998
            if($refno == 610) $str = "Boxhall & Roe, 1980"; //"Boxhall G.A. & Roe H.S.J., 1980"  //610 - Boxshall & Roe, 1980      ---> this is typo on their website
            if($refno == 964) $str = "M. Ali, Al-Yamani & Prusova, 2007"; //"Ali M., Al-Yamani F. & Prusova I., 2007"     //964 - M. Ali, Faiza Al-Yamani & Prusova, 2007
            if($refno == 971) $str = "Othsuka, Boxshall & Shimomura, 2005"; //"Othsuka S., Boxshall G.A. & Shimomura M., 2005"     //971 - Ohtsuka, Boxshall & Shimomura, 2005
            if($refno == 78) $str = "Wolfenden, 1905";  //"Wolfenden R.N., 1905"   //78 - Wolfenden, 1905 (1906)
            if($refno == 906) $str = "Unal & Shmeleva, 2002";  //"Unal E. & Shmeleva A.A., 2002"   //906 - Ünal & Shmeleva, 2002
            if($refno == 909) $str = "Bradford-Grieve, 1999 b"; //909 - Bradford-Grieve, 1999b
            if($refno == 344) $str = "Grice & Lawson, (1977) 1978"; // 344 - Grice & Lawson, 1977 (1978)
            if($refno == 795) $str = "Krishnaswami, 1953"; //795 - Krishnaswamy, 1953 (n°2)
            
            $str = str_replace(array("al.", ",", "&"), " ", $str);
            $str = Functions::remove_whitespace($str);
            $str = self::clean_str($str);
            $words = explode(" ", $str);
            $words = self::clean_words($words);
            print_r($words); //exit;
            if($fullref_by_letter = self::get_ref_maximum("biblio_1", substr($str,0,1))) {
                echo "\n1nd try\n";
                if($fullref = self::search_words($fullref_by_letter, $words)) return $fullref;
            }
            if($fullref_by_letter = self::get_ref_maximum("biblio_2", substr($str,0,1))) {
                echo "\n2nd try\n";
                if($fullref = self::search_words($fullref_by_letter, $words)) return $fullref;
            }
        }
        else exit("\nInvestigate can't find refno [$refno]\n");
    }
    private function clean_str($str) //e.g. "A. Scott, 1909" will just be "Scott, 1909"
    {
        if(substr($str,1,2) == ". ") return trim(substr($str,3,strlen($str)));
        return $str;
    }
    private function clean_words($words) //e.g. "A. Scott, 1909". The first array value "A." will be removed from $words
    {
        asort($words);
        $words = array_values($words); //reindex key
        $i = 0;
        foreach($words as $word) {
            if(strlen($word) == 2 && substr($word,1,1) == ".") $words[$i] = null;
            $i++;
        }
        $words = array_filter($words); //remove null arrays
        asort($words);
        $words = array_values($words); //reindex key
        return $words;
    }
    private function search_words($fullref_by_letter, $words)
    {
        // print_r($fullref_by_letter);
        // print_r($words);
        $arr = array_keys($fullref_by_letter);
        foreach($arr as $phrase) //$pharse e.g. "Gurney R., 1933 a"
        {
            $orig = $phrase;
            $phrase = str_replace(array(","), "", $phrase);
            $phrase = Functions::remove_whitespace($phrase);
            $phrase_arr = explode(" ", $phrase);
            /*
            $result = array_intersect($words, $phrase_arr);
            $result = array_values($result);
            if($words == $result) return $orig;
            */

            //start search each word
            if(self::all_words_are_inside_phrase($words, $phrase_arr)) return array($orig, $fullref_by_letter[$orig]);
            
        }
        return false;
    }
    /*
    Array
    (
        [0] => 1967
        [1] => Grice
        [2] => Hulsemann
    )
    */
    private function all_words_are_inside_phrase($words, $phrase_arr)
    {
        // print_r($phrase_arr);
        foreach($words as $word) {
            if(!in_array($word, $phrase_arr)) return false;
        }
        return true;
    }
    function start()
    {
        /* testing... 5 37 65
        $refno = 189; 
        $full_reference = self::get_fullreference_by_refno($refno); 
        print_r($full_reference);
        exit("\n[$refno]\n");
        // $refno_author_list = self::get_ref_minimum(); exit;
        // $part1 = self::get_ref_maximum("biblio_1"); exit;
        // $part2 = self::get_ref_maximum("biblio_2"); exit;
        */
        
        $this->uri_values = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true

        /* echo("\n Philippines: ".$this->uri_values['Philippines']."\n"); */
        
        /* just testing...
        for($sp=1; $sp<=470; $sp++) { //459
            $rec = self::parse_species_page($sp);
        } 
        */
        /* <select name="sp" id="sp" onChange="javascript:form.submit();">
                              <option value="0">Choose another species</option>
                              <option value="1">Acartia (Acanthacartia) bacorehuiensis</option>
                              </select>
        */
        
        // /* normal operation
        if($html = Functions::lookup_with_cache($this->page['species']."1", $this->download_options)) {
            $html = str_ireplace('<option value="0">Choose another species</option>', "", $html);
            if(preg_match("/<select name=\"sp\"(.*?)<\/select>/ims", $html, $a1)) {
                if(preg_match_all("/<option value=(.*?)<\/option>/ims", $a1[1], $a2)) {
                    // print_r($a2[1]);
                    // echo "\n".count($a2[1])."\n";
                    foreach($a2[1] as $str) { // "1173">Xanthocalanus squamatus
                        if(preg_match("/\"(.*?)\"/ims", $str, $a3)) {
                            $rec = self::parse_species_page($a3[1]);
                            self::write_archive($rec);
                        }
                    }
                }
            }
        }
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug['NZ']);
        // */
        
        /* 
           // 466 - not range but single value
           // 1198 - fix ['refx][M] ... problematic string is "; (91) M: ? 1,9;"
           // 187 - fix saw this: has * asterisk
           // [] => Array
           // (
           //     [0] => 1125
           // )
           
        $sp = 111; //111; 
        $rec = self::parse_species_page($sp);
        self::write_archive($rec);
        $this->archive_builder->finalize(TRUE);
        */
        // print_r($this->debug);
    }
    private function parse_species_page($sp)
    {
        $rec = array();
        if($html = Functions::lookup_with_cache($this->page['species'].$sp, $this->download_options)) {
            $rec['taxon_id'] = $sp;
            // <div class="Style4"><b><em>Bradyidius armatus</em></b>&nbsp;&nbsp;Giesbrecht, 1897&nbsp;&nbsp;&nbsp;(F,M)</div>
            if(preg_match("/<div class=\"Style4\">(.*?)<\/div>/ims", $html, $a1)) {
                // echo "\n". $a1[1]; //<b><em>Bradyidius armatus</em></b>&nbsp;&nbsp;Giesbrecht, 1897&nbsp;&nbsp;&nbsp;(F,M)
                if(preg_match("/<em>(.*?)<\/em>/ims", $a1[1], $a2)) $rec['species'] = $a2[1];
                else exit("\nInvestigate: no species 2 [$sp]\n");
                if(preg_match("/&nbsp;&nbsp;(.*?)&nbsp;&nbsp;/ims", $a1[1], $a2)) $rec['author'] = $a2[1];
            }
            else exit("\nInvestigate: no species 1 [$sp]\n");
            $rec['ancestry'] = self::parse_ancestry($html, $sp);
            $rec['NZ'] = self::get_NZ($html, $sp);
            $this->debug['NZ'][$rec['NZ']] = '';
            $rec['Lg'] = self::get_Lg($html, $sp);
        }
        print_r($rec);
        return $rec;
    }
    private function get_Lg($html, $sp)
    {   /* Lg.: </td><td><div align="left">	(&nbsp;<a href="javascript:popUpWindow('ref_auteursd.php',700,800);">
        <img src='images/boite-outils/icones/derneirespubli.gif' width=15 height="14" border='0' align="absmiddle"></a>&nbsp;<a href="javascript:popUpWindow('ref_auteursd.php',700,800);">
        References of the authors concerning dimensions</a>&nbsp;)
        </div></td></tr><tr><td></td><td>(5) F: 1,7; (37) F: 2,7-2,65; M: 2,2-1,5; (65) F: 2,65; M: 2,2; <b>{F: 1,70-2,70; M: 1,50-2,20}</b></td></tr>
        */
        $final = array();
        if(preg_match("/>Lg.: <\/td>(.*?)\}/ims", $html, $a)) {
            echo "\nLg = [$a[1]]\n";
            if(preg_match("/<td>\((.*?)<\/td>/ims", $a[1]."}</b></td>", $a2)) {
                // echo "\nLg = $a2[1]\n"; //5) F: 1,7; (37) F: 2,7-2,65; M: 2,2-1,5; (65) F: 2,65; M: 2,2; <b>{F: 1,70-2,70; M: 1,50-2,20}</b>
                $str_for_refs = "(".$a2[1]; //to be used below...
                if(preg_match("/\{(.*?)\}/ims", $a2[1], $a3)) {
                    // echo "\n".$a3[1]."\n"; //F: 1,70-2,70; M: 1,50-2,20
                    $arr = explode(";", $a3[1]);
                    $arr = array_map('trim', $arr);
                    // print_r($arr);
                    // Array (
                    //     [0] => F: 1,70-2,70
                    //     [1] => M: 1,50-2,20
                    // )
                    foreach($arr as $k) {
                        if(strpos($k, "F:") !== false) { //string is found
                            $k = str_replace("F: ", "", $k);
                            $range = explode("-", $k);
                            $final['F']['min'] = $range[0];
                            $final['F']['max'] = @$range[1];
                        }
                        elseif(strpos($k, "M:") !== false) { //string is found
                            $k = str_replace("M: ", "", $k);
                            $range = explode("-", $k);
                            $final['M']['min'] = $range[0];
                            $final['M']['max'] = @$range[1];
                        }
                    }//end foreach()
                }
                
                //for refs
                if(preg_match_all("/\((.*?)\)/ims", $str_for_refs, $a)) {
                    $final['ref nos'] = $a[1];
                }

                //start ref assignments ==================================================================
                //for ref assignments: e.g. //(5) F: 1,7; (37) F: 2,7-2,65; M: 2,2-1,5; (65) F: 2,65; M: 2,2; <b>{F: 1,70-2,70; M: 1,50-2,20}</b>
                // echo "\n[$str_for_refs]\n";
                $pos = strpos($str_for_refs, "<b>");
                if($pos)
                {
                    $str = trim(substr($str_for_refs,0,$pos));
                    // echo "\n[$str]\n"; //(5) F: 1,7; (37) F: 2,7-2,65; M: 2,2-1,5; (65) F: 2,65; M: 2,2;
                    $arr = explode(";", $str);
                    $arr = array_map('trim', $arr);
                    $arr = array_filter($arr); //remove null array values
                    // print_r($arr);
                    /* Array (
                        [0] => (5) F: 1,7
                        [1] => (37) F: 2,7-2,65
                        [2] => M: 2,2-1,5
                        [3] => (65) F: 2,65
                        [4] => M: 2,2
                        [5] => 
                    ) */
                    
                    $refx = array();
                    foreach($arr as $k) {
                        if(preg_match("/\((.*?)\)/ims", $k, $a)) { //uses preg_match not preg_match_all coz I assume that there is only 1 ref no. inside parenthesis
                            $refno = $a[1];
                            $k = trim(preg_replace('/\s*\([^)]*\)/', '', $k)); //remove parenthesis
                            
                            if(strpos($k, "F:") !== false) { //string is found
                                $k = str_replace("F: ", "", $k);
                                $range = explode("-", $k);
                                // if($val = $range[0]) $refx['F'][$refno][] = self::convert_num_with_comma_to_2decimal_places($val);
                                // if($val = @$range[1]) $refx['F'][$refno][] = self::convert_num_with_comma_to_2decimal_places($val);
                                // if($val = $range[0]) $refx['F'][] = array("val" => self::convert_num_with_comma_to_2decimal_places($val), "refno" => $refno);
                                // if($val = @$range[1]) $refx['F'][] = array("val" => self::convert_num_with_comma_to_2decimal_places($val), "refno" => $refno);
                                if($val = $range[0]) $refx['F'][self::convert_num_with_comma_to_2decimal_places($val)][] = $refno;
                                if($val = @$range[1]) $refx['F'][self::convert_num_with_comma_to_2decimal_places($val)][] = $refno;
                            }
                            elseif(strpos($k, "M:") !== false) { //string is found
                                $k = str_replace("M: ", "", $k);
                                $range = explode("-", $k);
                                // if($val = $range[0]) $refx['M'][$refno][] = self::convert_num_with_comma_to_2decimal_places($val);
                                // if($val = @$range[1]) $refx['M'][$refno][] = self::convert_num_with_comma_to_2decimal_places($val);
                                // if($val = $range[0]) $refx['M'][] = array("val" => self::convert_num_with_comma_to_2decimal_places($val), "refno" => $refno);
                                // if($val = @$range[1]) $refx['M'][] = array("val" => self::convert_num_with_comma_to_2decimal_places($val), "refno" => $refno);
                                if($val = $range[0]) $refx['M'][self::convert_num_with_comma_to_2decimal_places($val)] = $refno;
                                if($val = @$range[1]) $refx['M'][self::convert_num_with_comma_to_2decimal_places($val)] = $refno;
                            }
                        }
                    }//end foreach()
                    if($refx) $final['refx'] = $refx;
                }
                //end ref assignments ==================================================================
            }
        }
        else $this->debug['no Lg'][$sp]; //exit("\nInvestigate: no Lg [$sp]\n");
        return $final;
    }
    private function convert_num_with_comma_to_2decimal_places($num)
    {   //e.g. 1,7 to 1,70
        $orig = $num;
        $num = str_replace(",", ".", $num);
        if(is_numeric($num)) {
            if(strlen($num) <= 4) $decimal_places = 2;
            else                  $decimal_places = 3;
            $num = number_format($num, $decimal_places);
            $num = str_replace(".", ",", $num);
            return $num;
        }
        else return $orig;
    }
    private function get_NZ($html, $sp)
    {   //<tr><td valign="top" width="30">NZ: </td><td>13 + 1 doubtful</td></tr>
        if(preg_match("/>NZ: <\/td>(.*?)<\/tr>/ims", $html, $a)) return strip_tags($a[1]);
        else $this->debug['no NZ'][$sp]; //exit("\nInvestigate: no NZ [$sp]\n");
    }
    private function parse_ancestry($html, $sp)
    {
        $ancestry = array();
        $ranks = array("Order", "Superfamily", "Family", "Genus");
        //<div align="left"><b>Calanoida</b> <em>( Order )</em></div>
        if(preg_match_all("/<div align=\"left\">(.*?)<\/div>/ims", $html, $a1)) {
            foreach($ranks as $rank) {
                foreach($a1[1] as $div) { //string is found
                    if(strpos($div, "( $rank )") !== false) { //&nbsp;&nbsp;&nbsp;&nbsp;<b>Clausocalanoidea</b> <em>( Superfamily )</em>
                        // echo "\n$div";
                        if(preg_match("/<b>(.*?)<\/b>/ims", $div, $b)) $ancestry[$rank] = $b[1];
                    }
                }
            }
        }
        if(!$ancestry) exit("\nInvestigate: no ancestry [$sp]\n");
        return $ancestry;
    }
    private function write_archive($rec)
    {
        self::add_taxon($rec);
        self::add_trait($rec);
    }
    private function add_taxon($rec)
    {   /* [taxon_id] = 111
        [species] => Bradyidius armatus
        [author] => Giesbrecht, 1897
        [ancestry] => Array
        (
            [Order] => Calanoida
            [Superfamily] => Clausocalanoidea
            [Family] => Aetideidae
            [Genus] => Bradyidius
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['taxon_id'];
        $taxon->scientificName  = $rec['species'];
        if($val = @$rec['ancestry']['Order']) $taxon->order = $val;
        if($val = @$rec['ancestry']['Family']) $taxon->family = $val;
        if($val = @$rec['ancestry']['Genus']) $taxon->genus = $val;
        $taxon->furtherInformationURL = $this->page['species'].$rec['taxon_id'];
        // if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        $this->taxon_ids[$taxon->taxonID] = '';
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function add_trait($rec)
    {   //for NZ: ----------------------------------------------------- 1st trait
        if($nz = @$rec['NZ']) { //e.g. "13 + 1 doubtful"
            if($nz_uri = self::get_value_uri($nz)) {
                $rec['catnum'] = $rec['taxon_id']."_NZ";
                $rec['measurementRemarks'] = $nz;
                self::add_string_types($rec, $nz_uri, "http://eol.org/schema/terms/Present", "true");
            }
            else $this->debug['undefined NZ'][$nz] = '';
        }
        //for Lg: : ----------------------------------------------------- 2nd trait
        /* [Lg] => Array
                [F] => Array
                        [min] => 0,73
                        [max] => 0,91
                [M] => Array
                        [min] => 0,73
                        [max] => 
                [ref nos] => Array
                        [0] => 226
                [refx] => Array
                    (
                        [F] => Array
                            (
                                [0,91] => Array
                                        [0] => 226
                                [0,73] => Array
                                        [0] => 226
                            )
                    )
            )*/
        //initialize some vars:
        $rec['catnum'] = "";
        $rec['sex'] = "";
        $rec['measurementAccuracy'] = "http://purl.bioontology.org/ontology/LNC/LP64451-5";
        $rec['measurementMethod'] = "Literature review";

        //for female --------
        if(@$rec['Lg']['F']['min'] && @$rec['Lg']['F']['max']) { //has both min & max
            $rec['catnum'] = $rec['taxon_id']."_Lg_F";
            if($min = $rec['Lg']['F']['min']) {
                $rec['statisticalMethod'] = "http://semanticscience.org/resource/SIO_001113"; //min value
                $rec['sex'] = "http://purl.obolibrary.org/obo/PATO_0000383"; //female sex
                $rec["referenceID"] = @$rec['Lg']['refx']['F'][$min];
                self::add_string_types($rec, $min, "http://purl.obolibrary.org/obo/CMO_0000013", "true");
            }
            if($max = $rec['Lg']['F']['max']) {
                $rec['statisticalMethod'] = "http://semanticscience.org/resource/SIO_001114"; //max value
                $rec['sex'] = "http://purl.obolibrary.org/obo/PATO_0000383"; //female sex
                $rec["referenceID"] = @$rec['Lg']['refx']['F'][$max];
                self::add_string_types($rec, $max, "http://purl.obolibrary.org/obo/CMO_0000013", "true");
            }
            /* sample from FishBase:
            FB-Habitat-2_7112bfabffc2954c164c64cf0b2057bd	true	http://rs.tdwg.org/dwc/terms/verbatimDepth	0	   http://semanticscience.org/resource/SIO_001113	
            FB-Habitat-2_7112bfabffc2954c164c64cf0b2057bd	true	http://rs.tdwg.org/dwc/terms/verbatimDepth	20	   http://semanticscience.org/resource/SIO_001114	
            */
        }
        else { //not a range value but just one value
            $rec['catnum'] = $rec['taxon_id']."_Lg_F";
            if($min = $rec['Lg']['F']['min']) {
                $rec['statisticalMethod'] = ""; //not a range
                $rec['sex'] = "http://purl.obolibrary.org/obo/PATO_0000383"; //female sex
                $rec["referenceID"] = @$rec['Lg']['refx']['F'][$min];
                self::add_string_types($rec, $min, "http://purl.obolibrary.org/obo/CMO_0000013", "true");
            }
            if($max = $rec['Lg']['F']['max']) {
                $rec['statisticalMethod'] = ""; //not a range
                $rec['sex'] = "http://purl.obolibrary.org/obo/PATO_0000383"; //female sex
                $rec["referenceID"] = @$rec['Lg']['refx']['F'][$max];
                self::add_string_types($rec, $max, "http://purl.obolibrary.org/obo/CMO_0000013", "true");
            }
        }
        //for male --------
        if(@$rec['Lg']['M']['min'] && @$rec['Lg']['M']['max']) { //has both min & max
            $rec['catnum'] = $rec['taxon_id']."_Lg_M";
            if($min = $rec['Lg']['M']['min']) {
                $rec['statisticalMethod'] = "http://semanticscience.org/resource/SIO_001113"; //min value
                $rec['sex'] = "http://purl.obolibrary.org/obo/PATO_0000384"; //male sex
                $rec["referenceID"] = @$rec['Lg']['refx']['M'][$min];
                self::add_string_types($rec, $min, "http://purl.obolibrary.org/obo/CMO_0000013", "true");
            }
            if($max = $rec['Lg']['M']['max']) {
                $rec['statisticalMethod'] = "http://semanticscience.org/resource/SIO_001114"; //max value
                $rec['sex'] = "http://purl.obolibrary.org/obo/PATO_0000384"; //male sex
                $rec["referenceID"] = @$rec['Lg']['refx']['M'][$max];
                self::add_string_types($rec, $max, "http://purl.obolibrary.org/obo/CMO_0000013", "true");
            }
            /* sample from FishBase:
            FB-Habitat-2_7112bfabffc2954c164c64cf0b2057bd	true	http://rs.tdwg.org/dwc/terms/verbatimDepth	0	   http://semanticscience.org/resource/SIO_001113	
            FB-Habitat-2_7112bfabffc2954c164c64cf0b2057bd	true	http://rs.tdwg.org/dwc/terms/verbatimDepth	20	   http://semanticscience.org/resource/SIO_001114	
            */
        }
        else { //not a range value but just one value
            $rec['catnum'] = $rec['taxon_id']."_Lg_M";
            if($min = $rec['Lg']['M']['min']) {
                $rec['statisticalMethod'] = ""; //not a range
                $rec['sex'] = "http://purl.obolibrary.org/obo/PATO_0000384"; //male sex
                $rec["referenceID"] = @$rec['Lg']['refx']['M'][$min];
                self::add_string_types($rec, $min, "http://purl.obolibrary.org/obo/CMO_0000013", "true");
            }
            if($max = $rec['Lg']['M']['max']) {
                $rec['statisticalMethod'] = ""; //not a range
                $rec['sex'] = "http://purl.obolibrary.org/obo/PATO_0000384"; //male sex
                $rec["referenceID"] = @$rec['Lg']['refx']['M'][$max];
                self::add_string_types($rec, $max, "http://purl.obolibrary.org/obo/CMO_0000013", "true");
            }
        }
        
    }
    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"];
        $occurrence_id = $catnum; // simply used catnum

        /* not needed for this resource
        $unique_id = md5($taxon_id.$measurementType.$value);
        $occurrence_id = $unique_id; //because one catalog no. can have 2 MeasurementOrFact entries. Each for country and habitat.
        */
        
        $cont = $this->add_occurrence($taxon_id, $occurrence_id, $rec);

        if($cont) {
            $m = new \eol_schema\MeasurementOrFact();
            $m->occurrenceID       = $occurrence_id;
            $m->measurementOfTaxon = $measurementOfTaxon;
            if($measurementOfTaxon == "true") {
                $m->source      = $this->page['species'].$rec["taxon_id"];
                $m->contributor = '';
                if($val = @$rec["referenceID"]) {
                    if($reference_ids = self::write_references($val)) $m->referenceID = implode("; ", $reference_ids);
                }
            }
            $m->measurementType  = $measurementType;
            $m->measurementValue = $value;
            $m->bibliographicCitation = $this->bibliographic_citation." (".date("m/d/Y").")."; //same for all, for this resource
            if($val = @$rec['measurementUnit'])     $m->measurementUnit = $val;
            if($val = @$rec['measurementMethod'])   $m->measurementMethod = $val;
            if($val = @$rec['statisticalMethod'])   $m->statisticalMethod = $val;
            if($val = @$rec['measurementAccuracy'])   $m->measurementAccuracy = $val;
            if($val = @$rec['measurementRemarks'])  $m->measurementRemarks = $val;
            $this->archive_builder->write_object_to_file($m);
        }
    }
    private function add_occurrence($taxon_id, $occurrence_id, $rec)
    {
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $o->sex = @$rec['sex'];
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return true;
    }
    private function write_references($arr)
    {
        $refids = array();
        foreach($arr as $refno)
        {
            if($fullref = self::get_fullreference_by_refno($refno))
            {
                $fullref = implode(". - ", $fullref);
                $r = new \eol_schema\Reference();
                $r->full_reference = $fullref;
                $r->identifier = $refno;
                // $r->uri = '';
                $refids[] = $refno;
                if(!isset($this->resource_reference_ids[$r->identifier])) {
                   $this->resource_reference_ids[$r->identifier] = '';
                   $this->archive_builder->write_object_to_file($r);
                }
            }
            else {
                if(!in_array($refno, array(189,415,200))) exit("\nInvestigate no fullref [$refno]\n");
            }
        }
        return $refids;
    }
    private function get_value_uri($value)
    {
        if($uri = @$this->uri_values[$value]) return $uri;
        else {
            switch ($value) { //put here customized mapping
                case "United States of America":        return "http://www.wikidata.org/entity/Q30";
                case "Port of Entry":                   return false; //"DO NOT USE";
            }
        }
    }

    
    
    /*
    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"];
        $occurrence_id = $catnum; // simply used catnum
        $m = new \eol_schema\MeasurementOrFact();
        $this->add_occurrence($taxon_id, $occurrence_id, $rec);
        $m->occurrenceID       = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        if($measurementOfTaxon == "true")
        {
            $m->source      = @$rec["source"];
            $m->contributor = @$rec["contributor"];
            if($referenceID = @$rec["referenceID"]) $m->referenceID = $referenceID;
        }
        $m->measurementType  = $measurementType;
        $m->measurementValue = $value;
        $m->bibliographicCitation = $this->bibliographic_citation;
        if($val = @$rec['measurementUnit'])     $m->measurementUnit = $val;
        if($val = @$rec['measurementMethod'])   $m->measurementMethod = $val;
        if($val = @$rec['statisticalMethod'])   $m->statisticalMethod = $val;
        if($val = @$rec['measurementRemarks'])  $m->measurementRemarks = $val;
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $occurrence_id, $rec)
    {
        if(isset($this->occurrence_ids[$occurrence_id])) return;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        if($val = @$rec['sex']) $o->sex = $val;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return;
    }
    
    */
    
    
    /*

    */
}
?>

