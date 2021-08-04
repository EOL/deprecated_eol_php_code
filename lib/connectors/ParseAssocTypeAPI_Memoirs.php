<?php
namespace php_active_record;
/* */
class ParseAssocTypeAPI_Memoirs
{
    function __construct()
    {
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->assoc_prefixes = array("HOSTS", "HOST", "PARASITOIDS", "PARASITOID");
        $this->service['GNRD text input'] = 'http://gnrd.globalnames.org/name_finder.json?text=';
        /*
        http://gnrd.globalnames.org/name_finder.json?text=Ravenelia Acaeiae-pennatulae
        */
        $this->service['GNParser'] = "https://parser.globalnames.org/api/v1/";
    }
    /*#################################################################################################################################*/
    function parse_associations($html, $pdf_id, $WRITE)
    {
        $this->WRITE = $WRITE;
        $this->pdf_id = $pdf_id; //works but not being used atm.
        $arr = explode("<br>", $html); //print_r($arr); exit("\n[$html]\n");
        $arr = array_map('trim', $arr);
        /*Array(
            [0] => Abies amabilis
            [1] => Calyptospora columnaris, 682
            [2] => Melampsorella elatina. 681
            [3] => Pucciniastrum pustulatum, 677
            [4] => Uredinopsis macrosperma, 684
        )*/
        $sciname = $arr[0]; //not used though
        $assoc = self::get_associations($arr);
        // exit("\n[$sciname]\n-end assoc-\n");
        return array('assoc' => $assoc);
    }
    private function get_associations($rows)
    {
        // print_r($rows);
        array_shift($rows);
        // print_r($rows); exit;
        /*Array(
            [0] => Calyptospora columnaris, 682
            [1] => Melampsorella elatina. 681
            [2] => Pucciniastrum pustulatum, 677
            [3] => Uredinopsis macrosperma, 684
            Melampsorella elatina. Ill, 681 
        )*/
        $scinames = array();
        foreach($rows as $var) {
            $orig = $var;
            $var = trim(preg_replace('/\s*\[[^)]*\]/', '', $var)); //remove brackets including inside
            $var = trim(preg_replace('/[0-9]+/', '', $var)); //remove For Western Arabic numbers (0-9):
            $last_chars = array(",", ".");
            foreach($last_chars as $last_char) {
                $last = substr($var, -1);
                if($last == $last_char) $var = substr($var,0,strlen($var)-1);
            }
            $words = explode(" ", $var);
            if(ctype_lower(@$words[0][0])) { //first word, first char is lower case
                // echo "\nInvestigate OCR [$orig]\n";
                fwrite($this->WRITE, "1\t".$orig."\n");
                continue;
            }
            if(!ctype_upper(@$words[0][0])) { //first word, first char is lower case
                // echo "\nInvestigate OCR 2 [$orig]\n";
                fwrite($this->WRITE, "2\t".$orig."\n");
                continue;
            }
            
            $var = trim($words[0]." ".strtolower(@$words[1]));
            $var = Functions::canonical_form($var);

            // /*
            $cont = true;
            $special_chars = array("'", "&gt;", "&lt;", "&quot;", "-", ">", "<", "»", "»", "/");
            foreach($special_chars as $special) {
                if(stripos($var, $special) !== false) {
                    // echo "\nInvestigate OCR 3 [$orig]\n"; //string is found
                    fwrite($this->WRITE, "3\t".$orig."\n");
                    $cont = false;
                }
            }
            if(!$cont) continue;
            // */

            if($obj = self::run_GNRD_assoc($var)) {
                if($val = @$obj->names[0]->scientificName) $scinames[trim($var)] = ''; //take note that $var is taken, not $val
            }
            
        }
        
        return array("RO_0002453" => $scinames);
    }
    private function run_GNRD_assoc($string)
    {
        $string = trim($string);
        $url = $this->service['GNRD text input'].$string;
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
    private function run_gnparser_assoc($string)
    {
        $string = self::format_string_4gnparser($string);
        $url = $this->service['GNParser'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json); // print_r($obj); //exit;
            return $obj;
        }
    }
    private function format_string_4gnparser($str)
    {
        // %26 - &
        // %2C - ,
        // %28 - (
        // %29 - )
        // %3B - ;
        // + - space
        $str = str_replace(",", "%2C", $str);
        $str = str_replace("(", "%28", $str);
        $str = str_replace(")", "%29", $str);
        $str = str_replace(";", "%3B", $str);
        $str = str_replace(" ", "+", $str);
        $str = str_replace("&", "%26", $str);
        return $str;
    }
    */
    function write_associations($rec, $taxon, $archive_builder, $meta, $taxon_ids) //2nd param is source taxon object
    {
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
        // http://purl.obolibrary.org/obo/RO_0002453
        
        foreach($rec as $assoc_type => $scinames) { if($assoc_type == 'pdf_id') continue;
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
        if(stripos($assoc_type, "HOST") !== false) return "http://purl.obolibrary.org/obo/RO_0002454"; //string is found
        if(stripos($assoc_type, "PARASITOID") !== false) return "http://purl.obolibrary.org/obo/RO_0002209"; //string is found
        if(stripos($assoc_type, "RO_0002453") !== false) return "http://purl.obolibrary.org/obo/RO_0002453"; //string is found
        return false;
    }
}
?>