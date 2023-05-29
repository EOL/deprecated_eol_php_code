<?php
namespace php_active_record;
/* connector: [polytraits_new.php]

*/
class PolytraitsNewAPI
{
    function __construct($folder = false)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->max_images_per_taxon = 10;
        $this->service['taxa list'] = "http://polytraits.lifewatchgreece.eu/traitspublic_taxa.php?pageID=";
        $this->service['name info'] = "http://polytraits.lifewatchgreece.eu/taxon/SCINAME/json/?exact=1&verbose=1&assoc=0";
        $this->service['trait info'] = "http://polytraits.lifewatchgreece.eu/traits/TAXON_ID/json/?verbose=1&assoc=1";
        $this->service['terms list'] = "http://polytraits.lifewatchgreece.eu/terms";
        $this->taxon_page = "http://polytraits.lifewatchgreece.eu/taxonpage/";

        $this->download_options = array('cache' => 1, 'resource_id' => 'polytraits', 'expire_seconds' => 60*60*24*30*6, 
        'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); //6 months to expire
        // $this->download_options['expire_seconds'] = false;
        $this->debug = array();
    }
    function initialize()
    {
        $this->terms_info = self::get_terms_info();
    }
    function start()
    {
        self::initialize();
        self::main();
        $this->archive_builder->finalize(true);
        // Functions::start_print_debug();
        print_r($this->debug);
    }
    private function main()
    {   $pageID = 0;
        while(true) { $pageID++;
            $url = $this->service['taxa list'].$pageID;
            if($html = Functions::lookup_with_cache($url, $this->download_options)) { // for taxa page lists...
                if(preg_match_all("/<i>(.*?)<\/td>/ims", $html, $arr)) {
                    if(count($arr[1]) == 0) break;
                    // print_r($arr[1]); exit;
                    /*Array(
                        [0] =>  Abarenicola pacifica</i> Healy & Wells, 1959
                        [1] =>  Aberrantidae</i> Wolf, 1987
                        [2] =>  Abyssoninoe</i> Orensanz, 1990
                        [9] =>  Aglaophamus agilis</i> (Langerhans, 1880)
                        [10] =>  Aglaophamus circinata</i> (Verrill in Smith & Harger, 1874)
                        [11] =>  Alciopidae</i> Ehlers, 1864<span style='color:grey;'> (subjective synonym of  
                                <i>Alciopidae</i> according to Rouse, G.W., Pleijel, F. (2001) )</span>*/
                    foreach($arr[1] as $row) {
                        /* this block excludes synonyms
                        if(stripos($row, "synonym of") !== false) { //string is found
                            continue;
                        }
                        // else continue; //debug only
                        */
                        $row = "<i>".$row; //echo "\n".$row;
                        $rek = array();
                        if(preg_match("/<i>(.*?)<\/i>/ims", $row, $arr2))         $rek['sciname'] = trim($arr2[1]);
                        if(preg_match("/<\/i>(.*?)elix/ims", $row."elix", $arr2)) $rek['author']  = trim($arr2[1]);
                        if(!$rek['sciname']) exit("\nInvestigate: no sciname\n");
                        // print_r($rek); exit;
                        self::process_taxon($rek);
                    }
                }
            }
            break; //debug only
            if($pageID >= 2) break; //debug only
        } //end while()
    }
    private function process_taxon($rek)
    {
        $obj = self::get_name_info($rek['sciname']); //print_r($obj); exit;
        self::write_taxon($obj);
        return;
        $url = str_ireplace('TAXON_ID', $obj->taxonID, $this->service['trait info']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $traits = json_decode($json);
            print_r($traits); exit;
        }
        exit("\nInvestigate: cannot lookup this taxonID = $obj->taxonID\n");
    }
    private function get_name_info($sciname)
    {   if(!$sciname) return;
        $options = $this->download_options;
        $options['expire_seconds'] = false; //doesn't expire
        $url = str_ireplace('SCINAME', urlencode($sciname), $this->service['name info']);
        if($json = Functions::lookup_with_cache($url, $options)) {
            $objs = json_decode($json);
            // print_r($objs); exit;
            /* sample valid taxon Array(
                [0] => stdClass Object(
                        [taxonID] => 1960
                        [taxon] => Abarenicola pacifica
                        [author] => Healy & Wells, 1959
                        [validID] => 1960
                        [valid_taxon] => Abarenicola pacifica
                        [valid_author] => Healy & Wells, 1959
                        [status] => accepted
                        [source_of_synonymy] => 
                        [rank] => Species
                    )
            )*/
            if(count($objs) == 1) return $objs[0];
            else {
                // print_r($objs); echo("\nInvestigate: multiple results\n");
                foreach($objs as $obj) {
                    @$status[$obj->status]++;
                }
                print_r($status); //exit;
                if($status['accepted'] == 1) {
                    foreach($objs as $obj) {
                        if($obj->status == 'accepted') return $obj; //return the only accepted taxon
                    }
                }
                elseif($status['accepted'] == 0) exit("\nInvestigate: zero accepted names\n");
                else                             exit("\nInvestigate: multiple accepted names\n");
            }
        }
    }
    private function get_terms_info()
    {   /*<table class='db_docu' id='DZ'>
                <tr>
                    <th colspan=2 class='traitheader'>Depth zonation (benthos)</th>
                </tr>
                <tbody>
                    <tr class='mod_sub_rows'>
                    <td class='traitdefinition' style='width:250px;'>Definition</td>
                    <td class='traitdefinition' >The depth at which an organism occurs in the water column. Commonly defined based on ecological features of the zonation.</td>
                    </tr>
                    <tr class='mod_sub_rows'>
                    <td class='traitdefinition'>Identifier</td>
                    <td class='traitdefinition'><a href='http://polytraits.lifewatchgreece.eu/terms/DZ' target='_blank'>http://polytraits.lifewatchgreece.eu/terms/DZ</a></td>
                    </tr>		    
                    <tr class='mod_sub_rows'>
                    <td class='traitdefinition'>Related terms</td>
                    <td class='traitdefinition'>maximum bottom depth</td>
                    </tr>
                    <tr class='mod_sub_rows'>
                    <td class='traitdefinition'>Additional explanations</td>
                    <td class='traitdefinition'></td>
                    </tr>		    
                    <tr>
                    <td class='trait_docu_subheader' colspan=2>Modalities</td>        
        */
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($this->service['terms list'], $options)) {
            /* measurementTypes */
            if(preg_match_all("/class=\'db_docu\'(.*?)class=\'trait_docu_subheader\'/ims", $html, $arr)) {
                // print_r($arr[1]); echo "\n".count($arr[1]).'\n'; exit;
                // <th colspan=2 class='traitheader'>Substrate type of settlement</th>
                foreach($arr[1] as $str) {
                    if(preg_match("/class=\'traitheader\'>(.*?)<\/th>/ims", $str, $arr2)) $trait = trim($arr2[1]);
                    else exit("\nNo trait name...\n");
                    if(preg_match("/href=\'(.*?)\'/ims", $str, $arr2)) $trait_uri = trim($arr2[1]);
                    else exit("\nNo trait uri...\n");
                    $final[$trait] = $trait_uri;
                }
            }
            $mTypes = array_keys($final);
            // print_r($mTypes); print_r($final); echo "".count($final)." terms\n"; exit;

            /* measurementValues */
            if(preg_match_all("/>Modalities<\/td>(.*?)<\/tr><\/tbody><\/table>/ims", $html, $arr)) {
                // print_r($arr[1]); echo "\n".count($arr[1])."\n"; exit;
                $values = array(); $i = -1;
                foreach($arr[1] as $str) { $i++; //echo "\n$str\n";
                    
                    if(preg_match_all("/onclick=\'expand_close(.*?)<\/table>/ims", $str, $arr2)) { //$str is entire block of all values of a mType
                        // print_r($arr2[1]); exit;
                        foreach($arr2[1] as $str2) { //echo "\n$str2\n"; //good debug
                            if(preg_match("/<td colspan=2>(.*?)<\/td>/ims", $str2, $arr3)) { //$str2 is for a single value
                                $string_value = trim($arr3[1]);
                                $left = '<span '; $right = '</span>'; //cannot use strip_tags()
                                $string_value = self::remove_all_in_between_inclusive($left, $right, $string_value);
                                $string_value = trim(str_replace("&nbsp;", "", $string_value));
                            }
                            if(preg_match("/Definition<\/td>(.*?)<\/td>/ims", $str2, $arr3)) { //$str2 is for a single value
                                $definition = trim(strip_tags($arr3[1]));
                                $a['definition'] = $definition;
                            }
                            if(preg_match("/Identifier<\/td>(.*?)<\/td>/ims", $str2, $arr3)) { //$str2 is for a single value
                                $identifier = trim(strip_tags($arr3[1]));
                                $a['identifier'] = $identifier;
                            }
                            $values[$mTypes[$i]][$string_value] = $a;
                            // print_r($values); echo "".count($values)." values\n"; exit("\n000\n"); //good debug per single value        
                        }
                        // print_r($values); echo "".count($values)." values\n"; exit("\n111\n"); //good debug - all values of a single mType
                    }
                } //end foreach()
                // print_r($values); echo "".count($values)." values\n"; //exit("\n222\n"); //good debug - all values of all mTypes
            }
        }
        // testing:
        if(count($values) == 48) echo "\nTotal count test OK.";
        if($values['Substrate type of settlement']['boulders']['identifier'] == 'http://purl.obolibrary.org/obo/ENVO_01000114') echo "\nIdentifier test 1 OK.";            
        if($values['Substrate type']['mixed']['identifier'] == 'http://polytraits.lifewatchgreece.eu/terms/SUBST_MIX') echo "\nIdentifier test 2 OK.";            
        if($values['Body size (max)']['<2.5 mm']['identifier'] == 'http://polytraits.lifewatchgreece.eu/terms/BS_1') echo "\nIdentifier test 3 OK."; //exit;
        return $values;
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

    // [taxon] => Abarenicola pacifica
    // [author] => Healy & Wells, 1959
    // [valid_taxon] => Abarenicola pacifica
    // [valid_author] => Healy & Wells, 1959
    // [taxonomic_status] => 
    // [source_of_synonymy] => 
    // [parent] => Abarenicola

    private function write_taxon($obj)
    {   /*stdClass Object(
            [taxonID] => 1960
            [taxon] => Abarenicola pacifica
            [author] => Healy & Wells, 1959
            [validID] => 1960
            [valid_taxon] => Abarenicola pacifica
            [valid_author] => Healy & Wells, 1959
            [status] => accepted
            [source_of_synonymy] => 
            [rank] => Species
        )
        sample synonym taxon stdClass Object(
            [taxonID] => 1249
            [taxon] => Alciopidae
            [author] => Ehlers, 1864
            [validID] => 1249
            [valid_taxon] => Alciopidae
            [valid_author] => Ehlers, 1864
            [status] => subjective synonym
            [source_of_synonymy] => Rouse, G.W., Pleijel, F. (2001) Polychaetes. Oxford University Press,Oxford.354pp.
            [rank] => Family
        )*/
        $this->debug['status values'][$obj->status] = '';

        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $obj->taxonID;
        $taxon->scientificName              = $obj->taxon;
        $taxon->scientificNameAuthorship    = $obj->author;
        $taxon->taxonRank                   = strtolower($obj->rank);
        $taxon->source                      = $this->taxon_page.$obj->taxonID; //furtherInformationURL
        $taxon->taxonomicStatus             = $obj->status;
        if($obj->status == 'accepted') {
            if(isset($obj->parentNameUsageID)) {
                if($val = @$obj->parentNameUsageID) $taxon->parentNameUsageID = $val;
            }
            else                                    $taxon->parentNameUsageID = self::get_parentID_of_name($obj->taxon);
        }

        if(stripos($obj->status, "synonym") !== false) { //string is found
            $taxon->acceptedNameUsageID = self::get_taxon_info_of_name($obj->valid_taxon, 'taxonID');
        }

        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
    }
    private function get_parentID_of_name($sciname)
    {
        $taxon_id = self::get_taxon_info_of_name($sciname, 'taxonID');
        $ancestry = self::get_ancestry($taxon_id); //print_r($ancestry); exit;
        // /* solution for having undefined parents
        self::write_ancestry($ancestry);
        // */
        $ret = end($ancestry); // print_r($ret); exit("\n-end ancestry-\n");
        if($val = @$ret['taxonID']) return $val;
    }
    private function write_ancestry($ancestry)
    {
        $ancestry = array_reverse($ancestry);
        // print_r($ancestry); //exit("\nxxx\n");
        /*Array(
            [0] => Array(
                    [sciname] => Abarenicola
                    [rank] => genus
                    [taxonID] => 1957
                    [profile] => stdClass Object(
                            [taxonID] => 1957
                            [taxon] => Abarenicola
                            [author] => Wells, 1959
                            [validID] => 1957
                            [valid_taxon] => Abarenicola
                            [valid_author] => Wells, 1959
                            [status] => accepted
                            [source_of_synonymy] => 
                            [rank] => Genus
                        )
                )
        */

        if(!$ancestry) return;

        // /* assign parentNameUsageID
        $i = -1;
        foreach($ancestry as $r) { $i++;
            if(isset($ancestry[$i]['profile'])) {
                $ancestry[$i]['profile']->parentNameUsageID = @$ancestry[$i+1]['taxonID'];
            }
        }
        // */
        // print_r($ancestry); exit("\nyyy\n");
        foreach($ancestry as $r) {
            if(@$r['profile']) self::write_taxon($r['profile']);
        }
    }
    private function get_taxon_info_of_name($sciname, $what = 'all')
    {   if(!$sciname) return;
        if($obj = self::get_name_info($sciname)) {
            if($what == 'taxonID') {
                if($val = $obj->taxonID) return $val;
            }
            elseif($what == 'all') return $obj;    
        }
        exit("\nInvestigate: cannot locate sciname: [$sciname]\n");
    }
    function get_ancestry($taxon_id)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($this->taxon_page.$taxon_id, $options)) {
            $left = "<span class='taxonpath'>";
            $right = "</span>";
            // <span class='taxonpath'>Polychaeta (Class)  > Polychaeta Palpata (Subclass)  > Phyllodocida (Order)  > Nephtyidae (Family)  >  <i> Aglaophamus</i> (Genus) </span>
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                // print_r($arr[1]); exit;
                $str = $arr[1];
                $arr = explode(" > ", $str);
                $arr = array_map('trim', $arr);
                // print_r($arr); //exit;
                /*Array(
                    [0] => Polychaeta (Class)
                    [1] => Polychaeta Palpata (Subclass)
                    [2] => Terebellida (Order)
                    [3] => Acrocirridae (Family)
                    [4] => <i> Acrocirrus</i> (Genus)
                )*/
                $reks = array();
                foreach($arr as $string) {
                    $string = trim(strip_tags($string));
                    $rek = array();
                    $rek['sciname'] = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis OK
                    $rek['rank'] = self::get_string_between("(", ")", $string);
                    if($ret = self::get_taxon_info_of_name($rek['sciname'], 'all')) {
                        $rek['taxonID'] = $ret->taxonID;
                        $rek['profile'] = $ret;    
                    }
                    // print_r($ret); exit;
                    $reks[] = $rek;
                }
                // print_r($reks); exit; //good debug
                return $reks;
            }
        }
    }
    private function get_string_between($left, $right, $string)
    {
        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $string, $arr)) return strtolower(trim($arr[1]));
        return;
    }

    // =========================================================================================
    // ========================================================================================= copied template below
    // =========================================================================================
    private function main_x()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30*3; //3 months
        $csv_file = Functions::save_remote_file_to_local($this->service['plant_list'], $options);
        $out = shell_exec("wc -l ".$csv_file); echo "$out";
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            // print_r($row);
            $i++; if(($i % 2000) == 0) echo "\n$i";
            if($i == 1) {
                $fields = $row;
                // $fields = self::fill_up_blank_fieldnames($fields); // copied template
                $count = count($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    continue;
                }
                $k = 0; $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //important step
                // print_r($fields); print_r($rec); exit;
                if($rec['Synonym Symbol']) { //meaning it is a synonym
                    self::write_synonym($rec);
                    continue;
                }
                self::process_rec($rec);
            }
            // if($i > 10) break; //debug only
            // break;
        }
        unlink($csv_file);
    }
    function lookup_profile($symbol)
    {
        $url = $this->serviceUrls->plantsServicesUrl.'PlantProfile?symbol='.$symbol;
        // https://plantsservices.sc.egov.usda.gov/api/PlantProfile?symbol=ABBA
        if($json = Functions::lookup_with_cache($url, $this->download_options)) return $json;
    }
    private function process_rec($rec)
    {   /*Array(
        [Symbol] => ABAB
        [Synonym Symbol] => 
        [Scientific Name with Author] => Abutilon abutiloides (Jacq.) Garcke ex Hochr.
        [Common Name] => shrubby Indian mallow
        [Family] => Malvaceae
        )*/
        // $rec['Symbol'] = "ACOCC"; //"ABBA"; //force assign | ABES with copyrights | ACOCC subspecies
        if($json = self::lookup_profile($rec['Symbol'])) {
            $profile = json_decode($json); //print_r($profile); exit;
            /*Array( PlantProfile
                [Id] => 65791
                [Symbol] => ABAB
                [ScientificName] => <i>Abutilon abutiloides</i> (Jacq.) Garcke ex Hochr.
                [ScientificNameComponents] => 
                [CommonName] => shrubby Indian mallow
                [Group] => Dicot
                [RankId] => 180
                [Rank] => Species
                ...and many more...even trait data
            */

            // /*
            if(!isset($profile->Id)) {
                self::create_taxon_archive_using_rec($rec);
                return;
            }
            // */

            echo "\nSymbol: ".$rec['Symbol']." | Plant ID: $profile->Id | HasImages: $profile->HasImages"; //exit;
            $this->symbol_plantID_info[$rec['Symbol']] = $profile->Id;
            self::create_taxon_archive($rec, $profile);
            self::create_taxon_ancestry($profile);

            if($profile->HasImages > 0) {
                if($imgs = self::get_images($profile)) {
                    /* moved up, since we are now adding synonyms as well. Some taxa have synonyms but no images.
                    self::create_taxon_archive($rec, $profile);
                    self::create_taxon_ancestry($profile);
                    */
                    self::create_media_archive($profile->Id, $imgs);
                }
                // exit;
            }
            else {
                echo " | no images";
                // print_r($profile); exit("\nhas no images!\n");
            }
        }
        else exit("\ncannot lookup\n");
    }
    private function get_images($obj)
    {
        $url = $this->serviceUrls->plantsServicesUrl.'PlantImages?plantId='.$obj->Id;
        // https://plantsservices.sc.egov.usda.gov/api/PlantImages?plantId=15309
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $imgs = json_decode($json); //print_r($objs); exit("\n111\n");
            echo " | No. of images: ".count($imgs);
            return $imgs;
            /*[1] => stdClass Object(
                [ImageID] => 80
                [StandardSizeImageLibraryPath] => /ImageLibrary/standard/abes_002_shp.jpg
                [ThumbnailSizeImageLibraryPath] => /ImageLibrary/thumbnail/abes_002_thp.jpg
                [LargeSizeImageLibraryPath] => /ImageLibrary/large/abes_002_lhp.jpg
                [OriginalSizeImageLibraryPath] => /ImageLibrary/original/abes_002_php.jpg
                [Copyright] => 1            --- should not be 1
                [CommonName] => Pedro Acevedo-Rodriguez
                [Title] => 
                [ImageCreationDate] => 
                [Collection] => 
                [InstitutionName] => Smithsonian Institution, Department of Botany
                [ImageLocation] => United States, Virgin Islands, Saint John Co.
                [Comment] => 
                [EmailAddress] => RUSSELLR@si.edu
                [LiteratureTitle] => 
                [LiteratureYear] => 0
                [LiteraturePlace] => 
                [ProvidedBy] => Smithsonian Institution, Department of Botany
                [ScannedBy] => 
            )*/
        }

    }
    
    private function write_synonym($rec)
    {   /*Array( e.g. synonym record
        [Symbol] => ABAB
        [Synonym Symbol] => ABAM5
        [Scientific Name with Author] => Abutilon americanum (L.) Sweet
        [Common Name] => 
        [Family] => 
        )*/
        if($acceptedNameUsageID = @$this->symbol_plantID_info[$rec['Symbol']]) {}
        else {
            return; //no acceptedNameUsageID for this synonym
            print_r($rec);
            exit("\nInvestigate: no acceptedNameUsageID\n");
        }
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec['Synonym Symbol'];
        /* copied template, but does not work here
        $ret = self::parse_sciname($profile->ScientificName);
        $taxon->scientificName              = $ret['sciname'];
        $taxon->scientificNameAuthorship    = $ret['author'];
        */
        $taxon->scientificName = $rec['Scientific Name with Author'];
        $taxon->taxonomicStatus          = 'synonym';
        $taxon->acceptedNameUsageID      = $acceptedNameUsageID;
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
    }
    private function create_vernacular($rec)
    {   //print_r($rec); exit;
        if(!$rec['Common Name']) return;
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec["taxonID"];
        $v->vernacularName  = $rec['Common Name'];
        $v->language        = "en";
        $vernacular_id = md5("$v->taxonID|$v->vernacularName|$v->language");
        if(!isset($this->vernacular_name_ids[$vernacular_id])) {
           $this->vernacular_name_ids[$vernacular_id] = '';
           $this->archive_builder->write_object_to_file($v);
        }
    }
    private function parse_sciname($name_str)
    {   // e.g. "<i>Abutilon abutiloides</i> (Jacq.) Garcke ex Hochr."
        // <i>Achnatherum occidentale</i> (Thurb.) Barkworth ssp. <i>californicum</i> (Merr. & Burtt Davy) Barkworth
        if(preg_match_all("/<i>(.*?)<\/i>/ims", $name_str, $arr)) {

            if(count($arr[1]) == 1) {
                $sciname = trim($arr[1][0]);
                // echo "\n[$name_str]\n[".$arr[1]."]\n"; exit; //good debug
                $author = strip_tags($name_str);
                $author = trim(str_replace($sciname, "", $author));
                return array('sciname' => $sciname, 'author' => $author);    
            }
            elseif(count($arr[1]) > 1) {
                /* ver 1
                $sciname = implode(" ", $arr[1]); echo (" >2 words -[$sciname]- ");
                $author = "";
                // ----- get author of subspecies or variety e.g. "<i>Achnatherum occidentale</i> (Thurb.) Barkworth ssp. <i>californicum</i> (Merr. & Burtt Davy) Barkworth"
                $last = end($arr[1]);
                if(preg_match("/<i>".$last."<\/i>(.*?)elix/ims", $name_str."elix", $arr2)) {
                    $author = trim($arr2[1]); // echo "\n[".$author."]\n";
                }
                // -----
                return array('sciname' => $sciname, 'author' => $author);    
                */
                // /* ver 2
                return array('sciname' => Functions::remove_whitespace(strip_tags($name_str)), 'author' => '');    
                // */
            }
            else exit("\ninvestigate here...\n");

        }
        else {
            // exit("\nNo italics\n$name_str\n");
            return array('sciname' => $name_str, 'author' => '');    
        }
    }
    private function get_available_image_path($img)
    {   /*
        [StandardSizeImageLibraryPath] => /ImageLibrary/standard/abli_001_shp.jpg
        [LargeSizeImageLibraryPath] => /ImageLibrary/large/abli_001_lhp.jpg
        [OriginalSizeImageLibraryPath] => /ImageLibrary/original/abli_001_php.jpg
        */
        if($val = trim($img->LargeSizeImageLibraryPath)) return $val;
        if($val = trim($img->StandardSizeImageLibraryPath)) return $val;
        if($val = trim($img->OriginalSizeImageLibraryPath)) return $val;
        return false;
    }
    private function create_media_archive($taxid, $imgs)
    {   //print_r($imgs); //exit;
        // return;
        foreach($imgs as $img) {
            if($img->Copyright) continue; //proceed only if not copyrighted
            // /*
            $image_file_path = self::get_available_image_path($img);
            if(!$image_file_path) continue; //blank path
            // */
            // print_r($img);
            $mr = new \eol_schema\MediaResource();
            // if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
            if($agent_ids = self::format_agents($img)) $mr->agentID = implode("; ", $agent_ids);
            $mr->taxonID                = $taxid;
            $mr->identifier             = $img->ImageID;
            $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
            $mr->format                 = Functions::get_mimetype($image_file_path);
            // $mr->furtherInformationURL  = '';
            $mr->description            = self::format_description($img);
            $mr->UsageTerms             = 'http://creativecommons.org/publicdomain/zero/1.0/'; //'http://creativecommons.org/licenses/publicdomain/'; //'http://creativecommons.org/licenses/by-nc-sa/3.0/';
            $mr->Owner                  = $img->ProvidedBy;
            // /*
            $rights = "<p>This image is not copyrighted and may be freely used for any purpose. Please credit the artist, original publication if applicable, and the USDA-NRCS PLANTS Database. The following format is suggested and will be appreciated:</p>";
            if($val = $img->CommonName) {
                $rights .= "<p>$val @ USDA-NRCS PLANTS Database</p>"; 
            }
            $rights .= "<p>If you cite PLANTS in a bibliography, please use the following: USDA, NRCS. [insert current year here]. PLANTS Database (https://plants.sc.egov.usda.gov/, [insert current date here]). National Plant Data Team, Greensboro, NC 27401-4901 USA.</p>";
            $mr->rights = Functions::remove_whitespace($rights);
            // */

            $tmp = $this->serviceUrls->imageLibraryUrl . $image_file_path;
            $mr->accessURI = str_replace("/ImageLibrary/ImageLibrary/", "/ImageLibrary/", $tmp);
            // https://plants.sc.egov.usda.gov/ImageLibrary/large/abam_016_lvp.jpg

            // $mr->Rating                 = '';
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';            
                @$this->image_cap[$mr->taxonID]++;
            }    
        }
    }
    private function format_description($img)
    {
        $tmp = "";
        if($val = $img->Title) $tmp .= $val.". ";
        if($val = $img->Comment) $tmp .= $val.". ";
        if($val = $img->CommonName) $tmp .= $val.". ";
        if($val = $img->ProvidedBy) $tmp .= "Provided by ".$val.". ";
        if($val = $img->ImageLocation) $tmp .= $val.". ";
        return trim($tmp);
    }
    private function format_agents($img)
    {   /*[0] => stdClass Object(
                    [ImageID] => 157
                    [StandardSizeImageLibraryPath] => /ImageLibrary/standard/abli_001_shp.jpg
                    [ThumbnailSizeImageLibraryPath] => /ImageLibrary/thumbnail/abli_001_thp.jpg
                    [LargeSizeImageLibraryPath] => /ImageLibrary/large/abli_001_lhp.jpg
                    [OriginalSizeImageLibraryPath] => /ImageLibrary/original/abli_001_php.jpg
                    [Copyright] => 
                    [CommonName] => Tracey Slotta
                    [Title] => 
                    [ImageCreationDate] => 
                    [Collection] => 
                    [InstitutionName] => ARS Systematic Botany and Mycology Laboratory
                    [ImageLocation] => United States, Texas
                    [Comment] => 
                    [EmailAddress] => 
                    [LiteratureTitle] => 
                    [LiteratureYear] => 0
                    [LiteraturePlace] => 
                    [ProvidedBy] => ARS Systematic Botany and Mycology Laboratory
                    [ScannedBy] => 
                )
        */
        $agent_ids = array();
        if($agent_name = $img->InstitutionName) {
            $tmp_ids = self::create_agent($agent_name, 'source');
            $agent_ids = array_merge($agent_ids, $tmp_ids);
        }
        if($agent_name = $img->ProvidedBy) {
            $tmp_ids = self::create_agent($agent_name, 'source');
            $agent_ids = array_merge($agent_ids, $tmp_ids);
        }
        if($agent_name = $img->CommonName) {
            $tmp_ids = self::create_agent($agent_name, 'photographer');
            $agent_ids = array_merge($agent_ids, $tmp_ids);
        }
        $agent_ids = array_filter($agent_ids); //remove null arrays
        $agent_ids = array_unique($agent_ids); //make unique
        $agent_ids = array_values($agent_ids); //reindex key
        return $agent_ids;
    }
    private function create_agent($agent_name, $role)
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = $agent_name;
        $r->agentRole       = $role;
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = '';
        $agent_ids[] = $r->identifier;
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }
    private function get_parent_id($profile)
    {   // print_r($profile->Ancestors); //exit;
        $index = count($profile->Ancestors) - 2; //to get index of immediate parent
        // print_r($profile->Ancestors[$index]); exit("\n".$profile->Ancestors[$index]->Id."\n");
        return $profile->Ancestors[$index]->Id;
    }
    private function create_taxon_ancestry($profile)
    {
        $i = -1;
        foreach($profile->Ancestors as $a) { $i++; //print_r($a);
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = $a->Id;
            $taxon->parentNameUsageID   = @$profile->Ancestors[$i-1]->Id;
            // /* old
            if($a->ScientificName) {
                $ret = self::parse_sciname($a->ScientificName);
                $taxon->scientificName              = $ret['sciname'];
                $taxon->scientificNameAuthorship    = $ret['author'];    
            }
            else {
                print_r($profile); print_r($a);
                exit("\ninvestigate no sciname in ancestry list\n");
            }
            // */

            $taxon->taxonRank                   = strtolower($a->Rank);
            $taxon->source = 'https://plants.usda.gov/home/plantProfile?symbol='.$a->Symbol; //furtherInformationURL
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);    
            }
            /* start vernaculars */
            $rec = array();
            $rec['taxonID']     = $a->Id;
            $rec['Common Name'] = $a->CommonName;
            self::create_vernacular($rec);
        }
    }

    private function create_taxon_archive_using_rec($rec)
    {   /*Array(
        [Symbol] => ABAB
        [Synonym Symbol] => 
        [Scientific Name with Author] => Abutilon abutiloides (Jacq.) Garcke ex Hochr.
        [Common Name] => shrubby Indian mallow
        [Family] => Malvaceae
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec['Symbol'];
        $ret = self::parse_sciname($rec['Scientific Name with Author']);
        $taxon->scientificName              = $ret['sciname'];
        $taxon->scientificNameAuthorship    = $ret['author'];    
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
    }
    private function get_US_states_list()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30*12; //1 year
        $tsv_file = Functions::save_remote_file_to_local($this->github['US State list'], $options);
        $out = shell_exec("wc -l ".$tsv_file); echo "\nUS States/Territories: $out";
        $i = 0;
        foreach(new FileIterator($tsv_file) as $line_number => $line) { $i++;
            $row = explode("\t", $line);
            $abbrev = $row[1];
            $state_name = $row[0];
            $this->US_abbrev_state[$abbrev] = $state_name;
        }
        unlink($tsv_file);
    }
    // =========================================================================================
    // ========================================================================================= copied template below
    // =========================================================================================
    private function download_and_extract_remote_file($file = false, $use_cache = false)
    {
        if(!$file) $file = $this->data_dump_url; // used when this function is called elsewhere
        $download_options = $this->download_options;
        $download_options['timeout'] = 172800;
        $download_options['file_extension'] = 'txt.zip';
        $download_options['expire_seconds'] = 60*60*24*30;
        if($use_cache) $download_options['cache'] = 1;
        // $download_options['cache'] = 0; // 0 only when developing //debug - comment in real operation
        $temp_path = Functions::save_remote_file_to_local($file, $download_options);
        echo "\nunzipping this file [$temp_path]... \n";
        shell_exec("unzip -o " . $temp_path . " -d " . DOC_ROOT."tmp/"); //worked OK
        unlink($temp_path);
        if(is_dir(DOC_ROOT."tmp/"."__MACOSX")) recursive_rmdir(DOC_ROOT."tmp/"."__MACOSX");
    }
    //==================================================================================================================
    // private function process_record($taxid)
    // {
    //     self::create_trait_archive($a);
    // }
    private function create_trait_archive($a)
    {
        /*            */
        if($val = @$a['stats']['publicrecords']) {
            $rec = array();
            $rec["taxon_id"]            = $a['taxid'];
            $rec["catnum"]              = self::generate_id_from_array_record($a);
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/NumberPublicRecordsInBOLD";
            $rec['measurementValue']    = $val;
            $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
            self::add_string_types($rec);
        }
    }
    private function add_string_types($rec, $a = false) //$a is only for debugging
    {
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"], $rec);
        unset($rec['catnum']);
        unset($rec['taxon_id']);
        
        $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID = $occurrence_id;
        foreach($rec as $key => $value) $m->$key = $value;
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }
    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        if($val = @$rec['lifestage']) $o->lifeStage = $val;
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
}
?>