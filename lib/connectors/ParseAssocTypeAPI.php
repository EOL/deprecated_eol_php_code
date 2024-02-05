<?php
namespace php_active_record;
/* */
class ParseAssocTypeAPI
{
    function __construct($resource_name = false)
    {
        $this->resource_name = $resource_name;
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->assoc_prefixes = array("HOSTS", "HOST", "PARASITOIDS", "PARASITOID");
        // /* DATA-1891 --- this block is indeed needed.
        $this->assoc_prefixes[] = "HOST PLANTS";
        $this->assoc_prefixes[] = "HOST PLANT";
        // */
        // $this->service['GNParser'] = "https://parser.globalnames.org/api/v1/"; --- might never been used here...
        // $this->service['GNRD text input'] = 'httpz'; //'http://gnrd.globalnames.org/name_finder.json?text='; OBSOLETE GNRD
        $this->service['GNVerifier'] = "https://verifier.globalnames.org/api/v1/verifications/";
    }
    /*#################################################################################################################################*/
    private function initialize()
    {
        // /* for gnfinder
        if(Functions::is_production()) $this->json_path = '/var/www/html/gnfinder/'; //'/html/gnfinder/';
        else                           $this->json_path = '/Volumes/AKiTiO4/other_files/gnfinder/';
        // */
        require_library('connectors/Functions_Memoirs');
        $this->func = new Functions_Memoirs($this->json_path, $this->service, $this->download_options); 
    }
    function parse_associations($html, $pdf_id, $orig_tmp = false) //for "HOST" "HOST PLANT" "ON" "FOUND ON" etc.
    {   
        self::initialize();
        //exit("\n[$this->resource_name]\n");
        $this->pdf_id = $pdf_id; //works but not being used atm.
        $arr = explode("<br>", $html); //print_r($arr); exit("\nelix2\n");
        /*[35] => 
          [36] => HOSTS (Table 1).—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens (Alt.) J.E. Smith also being mined (Braun, 1908a). Populus balsamifera L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.
          [37] => 
          [38] => PARASITOIDS (Table 2).—Braconidae: Apanteles ornigus Weed, Apanteles sp., Pholetesor sp., probably salicifoliella (Mason); Eulophidae: Chrysocharis sp., Cirrospilus cinctithorax (Girault), Cirrospilus sp., Closterocerus tricinctus (Ashmead), Closterocerus sp., near trifasciatus, Horismenus fraternus (Fitch), Pediobius sp., Pnigalio flavipes (Ashmead), Pnigalio tischeriae (Ashmead) (regarded by some as a junior synonym of Pnigalio flavipes), Pnigalio near proximus (Ashmead), Pnigalio sp., Sympiesis conica (Provancher), Sympiesis sp., Tetrastichus sp.; Ichneumonidae: Alophosternum foliicola (Cushman), Diadeg-ma sp., stenosomus complex, Scambus decorus (Whalley); Pteromalidae: Pteromalus sp. (most records from Auerbach (1991), in which a few records may pertain only to Phyllonorycter nipigon).
        */
        $sciname = $arr[0]; //shouldn't be used bec it is uncleaned e.g. "Periploca orichalcella (Clemens), new combination"
        $ret = self::get_relevant_blocks($arr); //print_r($ret); exit("\nstop muna\n");
        /*Array(
            [HOSTS] => HOSTS.—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens J.E. Smith also being mined. Populus balsamifera L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.
            [HOST] => HOST(Table 173) (Mola mola). Gadus ogac.
            [HOST PLANTS] => HOST PLANTS.—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens J.E. Smith also being mined. Gadus morhua L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.
            [PARASITOIDS] => PARASITOIDS.—Braconidae: Apanteles ornigus Weed, Apanteles sp., Pholetesor sp., probably salicifoliella; Eulophidae: Chrysocharis sp., Cirrospilus cinctithorax, Cirrospilus sp., Closterocerus tricinctus, Closterocerus sp., near trifasciatus, Horismenus fraternus, Pediobius sp., Pnigalio flavipes, Pnigalio tischeriae, Pnigalio near proximus, Pnigalio sp., Sympiesis conica, Sympiesis sp., Tetrastichus sp.; Ichneumonidae: Alophosternum foliicola, Diadeg-ma sp., stenosomus complex, Scambus decorus; Pteromalidae: Pteromalus sp., in which a few records may pertain only to Phyllonorycter nipigon).
        )
        */
        if($this->resource_name == "NAF") $ret = self::get_relevant_blocks_using_On_FoundOn($arr, $ret, $orig_tmp); //DATA-1891
        $assoc = self::get_associations($ret); //print_r($assoc); 
        if($val = @$assoc['On']) print_r($assoc);        //just can't wait to have a hit
        if($val = @$assoc['Found on']) print_r($assoc);  //just can't wait to have a hit
        /*Array(
            [HOSTS] => Array(
                    [Populus tremuloides] => 
                    [Populus grandidentata] => 
                )
            [HOST] => Array(
                    [Mola mola] => 
                    [Gadus ogac] => 
                )
        */
        // exit("\n[$sciname]\n-end assoc-\n");
        return array('assoc' => $assoc);
    }
    private function get_associations($rows)
    {
        $scinames = array();
        foreach($rows as $prefix => $row) {
            $orig_row = trim(Functions::remove_whitespace($row));
            // /* DATA-1891
            $row = str_replace(array("(", ")"), " ", $row);
            $row = Functions::remove_whitespace($row);
            // */
            $row = str_replace(":", ",", $row);
            $row = str_replace("—", ",", $row);
            $row = str_replace(";", ",", $row);
            $row = trim(Functions::remove_whitespace($row));
            $row = Functions::conv_to_utf8($row);
            $parts = explode(",", $row); //exploded via a comma (","), since GNRD can't detect scinames from block of text sometimes.
            
            $possible_genuses = array();
            
            foreach($parts as $part) {

                // /* remove period from end of string
                //HOST.—Helian thus.  -> remove period
                //Gadus morhua L.     -> don't remove period
                if(substr($part, -1) == ".") {
                    $len = strlen($part);
                    if(substr($part,$len-3,1) != " ") $part = substr($part,0,$len-1); //"Helian thus." -> remove period
                }
                // */
                
                // /* manual: these names are not recordnized by GNRD. So we manually accept it. Alerted Dima (GNRD).
                /* not supposed to be a real species name: https://verifier.globalnames.org/?capitalize=on&format=html&names=Helianthus
                if($part == "Helian thus") {
                    $scinames[$prefix][$part] = '';
                    continue;
                }
                */
                // */
                
                $possible_genus = "";
                $obj_names = self::run_GNRD_assoc($part); //echo "\nGNRD for: [$part]\n"; print_r($obj); //exit;
                if(!$obj_names) continue;
                // foreach(@$obj->names as $name) { OBSOLETE GNRD
                foreach(@$obj_names as $name) {
                    // $tmp = $name->scientificName; //OBSOLETE GNRD
                    $tmp = $name;
                    /*
                    Populus tremuloides
                    P. grandidentata
                    P. canescens
                    Populus balsamifera
                    P. deltoides
                    Salix
                    Populus alba
                    P. nigra
                    P. tremula
                    */
                    // /* possible genus
                    $words = explode(" ", $tmp);
                    if(substr($tmp,1,2) != ". ") {
                        $possible_genus = trim($words[0]);
                        $possible_genuses[] = trim($words[0]);
                    }
                    if(substr($tmp,1,2) == ". " && substr($tmp,0,1) === substr($possible_genus,0,1)) {
                        array_shift($words); //remove first element "P."
                        $new_sci = $possible_genus." ".implode(" ", $words);
                        $scinames["$prefix"][$new_sci] = "('$prefix'). $orig_row";
                        // exit("\ngoes here...\n");
                    }
                    // /* New: good inclusion to complete genus names. Not perfect but better than nothing.
                    elseif(substr($tmp,1,2) == ". ") { //will use $possible_genuses here
                        foreach($possible_genuses as $pg) {
                            if(substr($tmp,0,1) === substr($pg,0,1)) {
                                array_shift($words); //remove first element "P."
                                $new_sci = $pg." ".implode(" ", $words);
                                $scinames["$prefix"][$new_sci] = "('$prefix'). $orig_row";
                            }
                        }
                    }
                    // */
                    else {
                        if(self::is_one_word($tmp)) continue;
                        $scinames[$prefix][$tmp] = "('$prefix'). $orig_row";
                    }
                    // */
                } //end obj->names loop
            }
        }
        // print_r($scinames); exit("\nexit muna\n");
        return $scinames;
    }
    private function get_relevant_blocks($arr)
    {   //print_r($this->assoc_prefixes); exit;
        /*
        print_r($arr); exit;
        [49] => 
        [50] => PARASITOID (Table 2).(Chanos chanos).
        [51] => 
        [52] => HOST PLANT (Table 1) ;Mola mola,
        */
        $final = array();
        foreach($arr as $string) {
            foreach($this->assoc_prefixes as $prefix) {
                // //a space
                // echo "\nprocess: [$string]\n".substr($string,0,strlen($prefix)+1)." === [$prefix ]"."\n"; //debug only
                
                //a space
                if(substr($string,0,strlen($prefix)+1) === "$prefix"." ") {
                    $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
                    $final[$prefix] = $string; debug("\n[$string][$prefix]['space'][1]\n");
                    continue;
                }
                //a period (.)
                if(substr($string,0,strlen($prefix)+1) === "$prefix".".") {
                    $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
                    $final[$prefix] = $string; debug("\n[$string][$prefix]['period'][2]\n");
                    continue;
                }
                //a diff hyphen (—)
                if(substr($string,0,strlen($prefix)+3) === "$prefix"."—") { //take note +3 NOT +1
                    $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
                    $final[$prefix] = $string; debug("\n[$string][$prefix]['diff hyphen'][3]\n");
                    continue;
                }
                
                // /* DATA-1891: a list of punctuation, I'd say... short dash, long dash, comma, period, semicolon, colon, and open parenthesis.
                $punctuations = array("-", "_", ",", ";", ":", "(");
                foreach($punctuations as $punctuation) {
                    if(substr($string,0,strlen($prefix)+1) === "$prefix".$punctuation) {
                        // $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis --- comment this to enable 'open parenthesis' punctuation
                        $final[$prefix] = $string; // exit("\ngoes here...\n");
                        break;
                    }
                }
                // */
            }
        }
        // print_r($final); exit("\n-eli1-\n");
        /*Array(
            [HOSTS] => HOSTS (Table 1).—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens (Alt.) J.E. Smith also being mined (Braun, 1908a). Populus balsamifera L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.
            [PARASITOIDS] => PARASITOIDS (Table 2).—Braconidae: Apanteles ornigus Weed, Apanteles sp., Pholetesor sp., probably salicifoliella (Mason); Eulophidae: Chrysocharis sp., Cirrospilus cinctithorax (Girault), Cirrospilus sp., Closterocerus tricinctus (Ashmead), Closterocerus sp., near trifasciatus, Horismenus fraternus (Fitch), Pediobius sp., Pnigalio flavipes (Ashmead), Pnigalio tischeriae (Ashmead) (regarded by some as a junior synonym of Pnigalio flavipes), Pnigalio near proximus (Ashmead), Pnigalio sp., Sympiesis conica (Provancher), Sympiesis sp., Tetrastichus sp.; Ichneumonidae: Alophosternum foliicola (Cushman), Diadeg-ma sp., stenosomus complex, Scambus decorus (Whalley); Pteromalidae: Pteromalus sp. (most records from Auerbach (1991), in which a few records may pertain only to Phyllonorycter nipigon).
        )
        */
        return $final;
    }
    private function get_relevant_blocks_using_On_FoundOn($arr, $final, $orig_tmp)
    {
        $tmp = str_replace("\n", "<br>", $orig_tmp);
        $arr = explode("<br>", $tmp);
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        // print_r($arr); exit("\nelix3\n");
        /*Array(
            [0] => Sphaerocarpos texanus Aust. Bull. Torrey Club 6: 158. 1877
            [1] => Sphaerocarpos terrestris Bisch. Nova Acta Acad. Leop.-Carol. 13: 829, in part. 1827
            [2] => Found on Oreochromis niloticus Elba.
            [3] => sphaerocarpos cahfornicus Aust. Bull. Torrey Club 6- 305 1879
        */
        $assoc_prefixes = array("On", "Found on");
        foreach($arr as $string) {
            foreach($assoc_prefixes as $prefix) {
                // //a space
                // echo "\nprocess: [$string]\n".substr($string,0,strlen($prefix)+1)." === [$prefix ]"."\n"; //debug only
                /* DATA-1891: a list of punctuation, I'd say... short dash, long dash, comma, period, semicolon, colon, and open parenthesis, etc.
                newline
                "On"/"Found on" [possible punctuation as above] [target text *beginning with* species names] newline */
                $punctuations = array(" ", ".", "—", "-", "_", ",", ";", ":", "(");
                foreach($punctuations as $punctuation) {
                    $add = 1;
                    if($punctuation == "—") $add = 3;
                    $left = substr($string,0,strlen($prefix)+$add);
                    $right = "$prefix".$punctuation;
                    if(strcmp($left, $right) == 0) {
                        $new_str = trim(substr($string, strlen($left), strlen($string)));
                        // debug("\n'$left' is equal to '$right' in a case sensitive string comparison.\n-----\n[$left]\n[$string][$new_str]\nelix1\n-----\n");
                        $words = explode(" ", $new_str);
                        $new_words = array();
                        $new_words[] = $words[0];
                        $new_words[] = @$words[1];
                        // print_r($new_words); //exit("\nelix4\n");
                        $new_str = implode(" ", $new_words);
                        // if($obj = self::run_GNRD_assoc($new_str)) { OBSOLETE GNRD
                        
                        // /* cont. to search gnfinder if $new_str looks line a sciname. Works here bec. sciname must come directly after
                        if(!ctype_upper(substr($new_str,0,1))) continue;
                        // */
                        
                        // echo "\nsearch: [$new_str]\n"; //good debug --- for "On" AND "Found on" prefixes only
                        if($obj_names = self::run_GNRD_assoc($new_str)) {
                            // if($sciname = @$obj->names[0]->scientificName) { OBSOLETE GNRD
                            if($sciname = @$obj_names[0]) {
                                $final[$prefix] = $sciname; // exit("\ngoes here...\n");
                            }
                        }
                    }
                    // else echo "\n'$left' is not equal to '$right' in a case sensitive string comparison";
                }
                // */
            }
        }
        // print_r($final); exit("\n-eli1-\n");
        return $final;
    }
    private function run_GNRD_assoc($string)
    {
        $string = trim($string);
        if(!$string) return false;
        // echo "\nstring: [$string]\n";
        // /*
        if($names = $this->func->get_names_from_gnfinder($string)) return $names;
        return false;
        // */
        $url = $this->service['GNRD text input'].$string; debug("\nGNRD 3: [$url]\n");
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            return $obj;
        }
        return false;
    }
    private function is_one_word($str)
    {
        $arr = explode(" ", $str);
        if(count($arr) == 1) return true;
        return false;
    }
    /*
    */
    function write_associations($rec, $taxon, $archive_builder, $meta, $taxon_ids, $bibliographicCitation = "") //2nd param is source taxon object
    {   //exit("\ndito 2\n");
        $this->taxon_ids = $taxon_ids;
        $this->archive_builder = $archive_builder;
        // print_r($rec); exit("\n111\n");
        /*Array(
            [HOST] => Array(
                    [Populus tremuloides] => 
                    [Populus grandidentata] => 
                )
            [PARASITOID] => Array(
                    [Cirrospilus cinctithorax] => 
                    [Closterocerus tricinctus] => 
                )
            [pdf_id] => SCtZ-0614
        )*/
        
        // HOST(s)/HOST PLANT(s)   associationType=http://purl.obolibrary.org/obo/RO_0002454
        // PARASITOID(s)           associationType=http://purl.obolibrary.org/obo/RO_0002209
        
        foreach($rec as $assoc_type => $scinames) { if($assoc_type == 'pdf_id') continue;
            $remarks = $scinames;
            $scinames = array_keys($scinames);
            $associationType = self::get_assoc_type($assoc_type);
            foreach($scinames as $target_sciname) {
                $occurrence = $this->add_occurrence($taxon, "$taxon->scientificName $associationType");
                $related_taxon = $this->add_taxon($target_sciname);
                $related_occurrence = $this->add_occurrence($related_taxon, "$related_taxon->scientificName $associationType");
                $a = new \eol_schema\Association();
                $a->associationID = md5("$occurrence->occurrenceID $associationType $related_occurrence->occurrenceID");
                $a->occurrenceID = $occurrence->occurrenceID;
                $a->associationType = $associationType;
                $a->targetOccurrenceID = $related_occurrence->occurrenceID;
                $a->source = @$meta[$rec['pdf_id']]['dc.relation.url'];
                $a->measurementRemarks = $remarks[$target_sciname]; //this is the while block of text
                $a->bibliographicCitation = $bibliographicCitation;
                // print_r($a); exit("\n-cha-\n");
                if(!isset($this->association_ids[$a->associationID])) {
                    $this->archive_builder->write_object_to_file($a);
                    $this->association_ids[$a->associationID] = '';
                }
            }
        }
        return $this->taxon_ids;
    }
    private function add_occurrence($taxon, $identification_string)
    {
        $occurrence_id = md5($taxon->taxonID . $this->pdf_id . "assoc_occur" . $identification_string);
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon->taxonID;
        if(!isset($this->occurrence_ids[$occurrence_id])) {
            $this->archive_builder->write_object_to_file($o);
            $this->occurrence_ids[$occurrence_id] = '';
        }
        return $o;
    }
    private function add_taxon($taxon_name)
    {
        /* copied template
        $taxon_id = md5($taxon_name);
        if(isset($this->taxon_ids[$taxon_id])) return $this->taxon_ids[$taxon_id];
        $t = new \eol_schema\Taxon();
        $t->taxonID = $taxon_id;
        $t->scientificName = $taxon_name;
        $t->order = $order;
        $this->archive_builder->write_object_to_file($t);
        $this->taxon_ids[$taxon_id] = $t;
        return $t;
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = md5($taxon_name);
        $taxon->scientificName  = $taxon_name;
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        return $taxon;
    }
    private function get_assoc_type($assoc_type)
    {   /*
        HOST(s)/HOST PLANT(s)   associationType=http://purl.obolibrary.org/obo/RO_0002454
        PARASITOID(s)           associationType=http://purl.obolibrary.org/obo/RO_0002209
        */
        if(stripos($assoc_type, "HOST") !== false)          return "http://purl.obolibrary.org/obo/RO_0002454"; //string is found
        if(stripos($assoc_type, "PARASITOID") !== false)    return "http://purl.obolibrary.org/obo/RO_0002209"; //string is found
        if(in_array($assoc_type, array("On", "Found on")))  return "http://purl.obolibrary.org/obo/RO_0002454"; //DATA-1891
        /* for "North American Flora" only --- TODO
        if($assoc_type == "On")             return "http://purl.obolibrary.org/obo/RO_0002454";
        elseif($assoc_type == "Found on")   return "http://purl.obolibrary.org/obo/RO_0002454";
        */
        exit("\n-----\nUndefined association type (SI to Zoology Botany): [$assoc_type]\n-----\n");
        return false;
    }
}
?>