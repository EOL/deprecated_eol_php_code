<?php
namespace php_active_record;
/* */
class ParseSizePatternsAPI
{
    function __construct($resource_name = false)
    {
        $this->resource_name = $resource_name;
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->tsv['part_and_dimension_mapping'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/TextMining/part_and_dimension_mapping.txt";
        $this->tsv['unit_terms'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/TextMining/unit_terms.txt";
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
    function parse_size_patterns($html, $pdf_id, $orig_tmp = false)
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
        $sciname = $arr[0]; //shouldn't be used bec it is uncleaned e.g. "Periploca orichalcella (Clemens), new combination"
        $ret = self::get_relevant_blocks($arr); //print_r($ret); exit("\nstop muna\n");
        /* copied template
        if($this->resource_name == "NAF") $ret = self::get_relevant_blocks_using_On_FoundOn($arr, $ret, $orig_tmp); //DATA-1891
        */
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
    {
        // print_r($arr); print_r($this->size_mapping); exit;
        $final = array();
        foreach($arr as $row) {
            /*
            [Body Part term] [up to 10 intervening words and no sentence break] [number or number range] [units term] [dimension term]
            [Body Part term] [dimension term (noun form)] [up to three words and/or a colon and/or a dash] [number or number range] [units term]
            newline [number or number range] [units term] [dimension term]
            */
            
            // if(stripos($row, "1.5 cm. long") !== false) exit("\nuuuuuuuuuu\n[$row]\nuuuuuuuuuu\n"); //string is found
            
            
            if($ret = self::use_pattern_1($row)) {
                // echo("\naaa\n"); //exit;
            }
        }
        // print_r($final); exit("\n-eli1-\n");
        /*
        */
        return $final;
    }
    private function use_pattern_1($row)
    {
        $body_parts = array_keys($this->size_mapping);
        $body_parts = array_filter($body_parts); //remove null arrays
        $body_parts = array_unique($body_parts); //make unique
        $body_parts = array_values($body_parts); //reindex key
        // print_r($body_parts); exit;
        foreach($body_parts as $body_part) {
            if($ret = self::parse_row($body_part, $row)) return $ret;
        }
    }
    private function parse_row($body_part, $row)
    {   $main = array();
        $orig_row = $row;
        $row = str_ireplace("in diameter", "in_diameter", $row); //manual

        $row = str_replace(";", " ; ", $row); //manual
        $row = str_replace(",", " , ", $row); //manual
        $row = Functions::remove_whitespace($row);
        $words = explode(" ", $row);

        // /* 1st choice for body part
        if($body_part_key = self::get_Body_Part_term($body_part, $words)) {
            $main['Body_Part_term'] = $words[$body_part_key];
            $main['Body_Part_term_key'] = $body_part_key;
            $main['Body_Part_term_option'] = '1st choice';
        }
        else return false;
        
        
        if(stripos($row, "1.5 cm. long") !== false)
        {
            // echo("\nwwwwwwwwww\n");
            // print_r($main);
            // exit("\nuuuuuuuuuu\n[$row]\nuuuuuuuuuu\n"); //string is found
        }
        // */
        
        /*
        if($unit_key = self::get_units_term($words)) $main['units_term'] = $words[$unit_key];
        else return false;
        */
        if($term_key = self::get_dimension_term($words, $body_part)) {
            $main['dimension_term'] = $words[$term_key];
            $main['dimension_term_key'] = $term_key;
        }
        else return false;

        if($unit_key = self::get_units_term_v2($words, $term_key)) {
            $main['units_term'] = $words[$unit_key];
            $main['units_term_key'] = $unit_key;
        }
        else return false;

        if($number_key = self::get_number_or_number_range($words, $unit_key)) {
            $main['number_or_number_range'] = $words[$number_key];
            $main['number_or_number_range_key'] = $number_key;
        }
        else return false;

        // /* 2nd choice for body part
        if($body_part_key = self::get_Body_Part_term_v2($words, $number_key, $main['dimension_term'])) {
            $main['Body_Part_term'] = $words[$body_part_key];
            $main['Body_Part_term_key'] = $body_part_key;
            $main['Body_Part_term_option'] = '2nd choice';
        }
        // else return false;
        // */

        /* [Body Part term] [up to 10 intervening words and no sentence break] [number or number range] [units term] [dimension term] */
        if($main['Body_Part_term_key'] < $number_key && $number_key < $unit_key && $unit_key < $term_key) {
            $main['pattern'] = 1;
            $main['row'] = $orig_row;
            // print_r($words); print_r($main); echo("\n$row\n"); //exit;
            $x = array();
            $x['row'] = $main['row'];
            $x['pattern'] = $main['pattern'];
            
            $x['Body_Part_term'] = $main['Body_Part_term'];
            $x['Body_Part_term_key'] = $main['Body_Part_term_key'];
            $x['Body_Part_term_option'] = $main['Body_Part_term_option'];
            
            $x['number_or_number_range'] = $main['number_or_number_range'];
            $x['number_or_number_range_key'] = $main['number_or_number_range_key'];
            
            $x['units_term'] = $main['units_term'];
            $x['units_term_key'] = $main['units_term_key'];
            
            $x['dimension_term'] = $main['dimension_term'];
            $x['dimension_term_key'] = $main['dimension_term_key'];
            
            print_r($x);
            return $x;
        }
    }
    
    private function get_Body_Part_term_v2($words, $number_key, $dimension_term)
    {   /* orig vers. --- just subtract once
        $body_part_key = $number_key - 1;
        if($body_part_str = $words[$body_part_key]) {
            $arr = self::get_body_part_or_parts_for_a_term($dimension_term);
            if(in_array($body_part_str, $arr)) return $body_part_key;
        }
        */
        for($i=1; $i <= 9; $i++) { //about 10 intervening words
            $number_key--;
            $body_part_key = $number_key;
            if($body_part_str = @$words[$body_part_key]) {
                $arr = self::get_body_part_or_parts_for_a_term($dimension_term);
                if(in_array($body_part_str, $arr)) return $body_part_key;
            }
            else return false;
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
        if($unit_str = $words[$unit_key]) {
            $arr = array_keys($this->unit_terms);
            if(in_array($unit_str, $arr)) return $unit_key;
        }
    }

    private function get_Body_Part_term($body_part, $words)
    {   //print_r($this->size_mapping); print_r($words); exit("\n333\n");
        // foreach(array_keys($this->size_mapping) as $body_part) {
            $key = array_search($body_part, $words);
            if($key !== false) return $key; //str found in array
        // }
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
        // print_r($valid_dimension_terms); exit;
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
        foreach($this->size_mapping as $body_part => $recs) {
            foreach($recs as $rec) {
                if($rec['term'] == $term) $final[$body_part] = '';
            }
        }
        return array_keys($final);
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
        // $options['expire_seconds'] = 0; //debug only --- un-comment when source TSV files are updated
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
    private function is_one_word($str)
    {
        $arr = explode(" ", $str);
        if(count($arr) == 1) return true;
        return false;
    }
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