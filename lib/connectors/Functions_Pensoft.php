<?php
namespace php_active_record;
/* */
class Functions_Pensoft
{
    function __construct() {}
    function initialize_new_patterns()
    {   // exit("\n[$this->new_patterns_4textmined_resources]\nelix1\n");
        $str = file_get_contents($this->new_patterns_4textmined_resources);
        $arr = explode("\n", $str);
        $arr = array_map('trim', $arr);
        // $arr = array_filter($arr); //remove null arrays
        // $arr = array_unique($arr); //make unique
        // $arr = array_values($arr); //reindex key
        // print_r($arr); //exit("\n".count($arr)."\n");
        $i = 0;
        foreach($arr as $row) { $i++;
            $cols = explode("\t", $row);
            if($i == 1) {
                $fields = $cols;
                continue;
            }
            else {
                $k = -1;
                foreach($fields as $fld) { $k++;
                    $rec[$fld] = $cols[$k];
                }
            }
            // print_r($rec); exit;
            /*Array(
                [string] => evergreen
                [measurementType] => http://purl.obolibrary.org/obo/FLOPO_0008548
                [measurementValue] => http://purl.obolibrary.org/obo/PATO_0001733
            )*/
            $this->new_patterns[$rec['string']] = array('mType' => $rec['measurementType'], 'mValue' => $rec['measurementValue']);
        }
    }
    function get_allowed_value_type_URIs_from_EOL_terms_file($download_options = false)
    {
        if($download_options) $options = $download_options;         //bec this func is also called from other libs
        else                  $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*1; //1 day expires
        if($yml = Functions::lookup_with_cache("https://raw.githubusercontent.com/EOL/eol_terms/main/resources/terms.yml", $options)) {
            /*  type: value
                uri: http://eol.org/schema/terms/neohapantotype
                parent_uris:        */
            if(preg_match_all("/type\: value(.*?)parent_uris\:/ims", $yml, $a)) {
                $arr = array_map('trim', $a[1]); // print_r($arr); exit;
                foreach($arr as $line) {
                    $uri = str_replace("uri: ", "", $line);
                    $final[$uri] = '';
                }
            }
            else exit("\nInvestigate: EOL terms file structure had changed.\n");
        }
        else exit("\nInvestigate: EOL terms file not accessible.\n");
        return $final;
    }
    function consolidate_with_EOL_Terms($mappings) # called from Functions.php
    {
        $download_options = array('expire_seconds' => 60*60*24); //expires 1 day
        $allowed_terms_URIs = self::get_allowed_value_type_URIs_from_EOL_terms_file($download_options);
        echo ("\nallowed_terms_URIs from EOL terms file: [".count($allowed_terms_URIs)."]\n");
        /*
        FROM Functions.php USED BY CONNECTORS:
        [Côte d'Ivoirereturn] => http://www.geonames.org/2287781
        [United States Virgin Islands] => http://www.wikidata.org/entity/Q11703
        [Netherlands Antillesreturn] => https://www.wikidata.org/entity/Q25227

        FROM EOL TERMS FILE:
        [http://www.geonames.org/5854968] => 
        [https://www.wikidata.org/entity/Q11703] => 
        [https://www.geonames.org/149148] => 
        */
        // step 1: create $info from eol terms file
        $tmp = array_keys($allowed_terms_URIs);
        unset($allowed_terms_URIs); # to clear memory
        foreach($tmp as $orig_uri) {
            $arr = explode(":", $orig_uri);
            $sub_uri = $arr[1];
            $info[$sub_uri] = $orig_uri; # $info['//www.wikidata.org/entity/Q11703'] = 'https://www.wikidata.org/entity/Q11703'
        }
        // step 2: loop $mappings, search each uri
        $ret = array();
        foreach($mappings as $string => $uri) {
            $arr = explode(":", $uri); // print_r($arr);
            $sub_uri = @$arr[1]; # '//www.wikidata.org/entity/Q11703'
            if($new_uri = @$info[$sub_uri]) $ret[$string] = $new_uri;
            else $ret[$string] = $uri;
        }
        return $ret;
    }
    function WoRMS_URL_format($path) # called from Pensoft2EOLAPI.php for now.
    {
        if(stripos($path, "marineregions.org/gazetteer.php?p=details&id=") !== false) { //string is found
            /* per: https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=67177&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67177
            http://www.marineregions.org/gazetteer.php?p=details&id=3314
            to the equivalent in this form:
            http://www.marineregions.org/mrgid/3314
            */
            if(preg_match("/id=(.*?)elix/ims", $path."elix", $arr)) {
                $id = $arr[1];
                return "http://www.marineregions.org/mrgid/".$id;
            }
        }
        return $path;
    }
    function format_TreatmentBank_desc($desc)
    {   /* working Ok with our original text from extension: http://eol.org/schema/media/Document
        $desc = '\n'.$desc.'\n';
        $desc = str_replace('\n', ' elicha ', $desc);
        $desc = Functions::remove_whitespace($desc);
        $parts = explode("elicha", $desc); // print_r($parts); //exit;
        $final = array();
        foreach($parts as $part) {
            if($first_word = self::get_first_word_of_string($part)) {
                $this->debug['detected_first_words'][$first_word] = '';
                if(!isset($this->exclude_first_words[$first_word])) $final[] = $part;    
            }
        } 
        return implode("\n", $final);
        */

        /* utility, for Jen. Get "\nCommon names." OR "\nNames.". For decision making by Jen.
        we may not need this anymore...
        */

        /* debug only
        ksort($this->debug['detected_first_words']);
        echo "\ndetected_first_words: ";
        print_r($this->debug['detected_first_words']); 
        print_r($this->exclude_first_words);
        // exit("\n-stop muna-\n");
        */

        // /* now using the extension: http://rs.gbif.org/terms/1.0/Description
        if(preg_match("/deposited (.*?)\. /ims", $desc, $arr)) {
            $substr = $arr[1];
            $desc = str_replace($substr, "", $desc);
        }
        if(preg_match("/References: (.*?)elicha/ims", $desc."elicha", $arr)) { //no tests yet, to do:
            $substr = $arr[1];
            $desc = str_replace($substr, "", $desc);
        }
        return $desc;
        // */
    }
    private function get_first_word_of_string($str)
    {
        $arr = explode(' ', trim($str)); 
        return strtolower($arr[0]); 
    }
    function substri_count($haystack, $needle) //a case-insensitive substr_count()
    {
        return substr_count(strtoupper($haystack), strtoupper($needle));
    }
    function process_table_TreatmentBank_ENV($rec)
    {
        $description_type = $rec['http://rs.tdwg.org/ac/terms/additionalInformation'];
        $title            = $rec['http://purl.org/dc/terms/title'];
        // $this->ontologies = "envo,eol-geonames"; //orig
        if($title == 'Title for eol-geonames')                                      $this->ontologies = "eol-geonames";
        elseif(in_array($description_type, array("distribution", "conservation")))  $this->ontologies = "envo,eol-geonames";
        elseif(in_array($description_type, array("description", "biology_ecology", "diagnosis", "materials_examined"))) $this->ontologies = "envo"; //the rest
        else return false;

        /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67753&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67753
                    https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67763&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67763
        Actually, let's make one change; let's try them both in [distribution]. 
        Habitat does seem to be described pretty often in that section. 
        When I've mulled over the next draft, if coverage for geographic records seems thin, we might try geonames also in [materials examined], 
        but that section might also be a minefield of specimen-holding institution names, so it'll depend on whether we have succeeded in 
        dealing with those elsewhere. Anyway, in general locality text strings seem to appear many times, so it may not be necessary. 
            INCLUDED:
                [description]           "envo"
                [biology_ecology]       "envo"
                [diagnosis]             "envo"
                [materials_examined]    "envo"
                [distribution]          "envo,eol-geonames"
                [conservation] =>       "envo,eol-geonames"

            Additional text types: EXCLUDED
                [synonymic_list] => 
                [vernacular_names] =>
                [] => 
                [material] => 
                [ecology] => 
                [biology] => 

            Future considerations: EXCLUDED
                [food_feeding] => 
                [breeding] => 
                [activity] => 
                [use] => 
        */

        // /* current filters
        if    (in_array($description_type, array("synonymic_list", "vernacular_names", "", "material", "ecology", "biology"))) return false; //continue;
        elseif(in_array($description_type, array("food_feeding", "breeding", "activity", "use"))) return false; //continue; //Future considerations
        // */  

        return $rec;      
    }
}
?>