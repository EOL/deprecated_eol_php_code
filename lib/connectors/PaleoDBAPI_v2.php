<?php
namespace php_active_record;
// connector: [pbdb_fresh_harvest.php]
class PaleoDBAPI_v2
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('cache' => 1, 'resource_id' => $folder, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1, 
        'expire_seconds' => 60*60*24*10); //cache expires in 10 days // orig
        // $this->download_options['expire_seconds'] = false; //debug

        if(Functions::is_production()) {
            $this->service["taxon"] = "https://paleobiodb.org/data1.2/taxa/list.json?all_taxa&variant=all&pres=regular&show=full,attr,app,classext,etbasis,ref&rowcount=true&datainfo=true&save=alltaxa.json";
            $this->spreadsheet_mappings = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/PaleoDB/pbdb_mappings.xlsx";
        }
        else {
            $this->service["taxon"] = "http://localhost/cp/PaleoDB/TRAM-746/alltaxa.json";
            $this->spreadsheet_mappings = "http://localhost/cp_new/PaleoDB/pbdb_mappings.xlsx";
        }
        
        $this->spreadsheet_options = array('resource_id' => $folder, 'cache' => 1, 'timeout' => 3600, 'file_extension' => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2); //set 'cache' to 0 if you don't want to cache spreadsheet
        $this->spreadsheet_options['expire_seconds'] = 0; //60*60*24; //60*60*24; //expires after 1 day
        
        $this->map['acceptedNameUsageID']       = "acc";
        $this->map['phylum']                    = "phl";
        $this->map['class']                     = "cll";
        $this->map['order']                     = "odl";
        $this->map['family']                    = "fml";
        $this->map['genus']                     = "gnl";
        $this->map['taxonID']                   = "oid";
        // $this->map['taxonID']                   = "vid";
        $this->map['scientificName']            = "nam";
        $this->map['scientificNameAuthorship']  = "att";
        // $this->map['furtherInformationURL']     = "oid";
        $this->map['parentNameUsageID']         = "par";
        $this->map['taxonRank']                 = "rnk";
        $this->map['taxonomicStatus']           = "tdf";
        $this->map['nameAccordingTo']           = "ref";
        $this->map['vernacularName']            = "nm2";

        $this->source_url = "https://paleobiodb.org/classic/checkTaxonInfo?is_real_user=1&taxon_no=";
                             
        /* used in PaleoDBAPI.php
        $this->service["collection"] = "http://paleobiodb.org/data1.1/colls/list.csv?vocab=pbdb&limit=10&show=bin,attr,ref,loc,paleoloc,prot,time,strat,stratext,lith,lithext,geo,rem,ent,entname,crmod&taxon_name=";
        $this->service["occurrence"] = "http://paleobiodb.org/data1.1/occs/list.csv?show=loc,time&limit=10&base_name=";
        $this->service["reference"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=displayRefResults&type=view&reference_no=";
        $this->service["source"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=checkTaxonInfo&is_real_user=1&taxon_no=";
        */
    }

    function get_all_taxa($descendants_of_parents_without_entries = false)
    {
        // /* DATA-1841 terms remapping
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        $this->func->initialize_terms_remapping(60); //for DATA-1841 terms remapping | 60 seconds expire
        // print_r($this->func->remapped_terms);
        echo("\nremapped_terms: ".count($this->func->remapped_terms)."\n");
        // */
        
        if($val = $descendants_of_parents_without_entries) $this->descendants_of_parents_without_entries = $val;
        else                                               $this->descendants_of_parents_without_entries = array();

        /* test
        $arr = self::get_uris($this->spreadsheet_mappings);
        print_r($arr);
        exit("\nstop muna\n");
        */
        self::parse_big_json_file();
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }
    private function get_uris($spreadsheet)
    {
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        $spreadsheet_options = $this->spreadsheet_options;
        if($spreadsheet) {
            if($arr = $func->convert_spreadsheet($spreadsheet, 0, $spreadsheet_options)) {
                // print_r($arr); exit;
                // FIELD    VALUE   URI measurementType measurementRemarks  eol life stage (in occurrence)
                $i = 0;
                foreach($arr['FIELD'] as $field) {
                    $final[$field][$arr['VALUE'][$i]] = array('uri' => $arr['URI'][$i], 'mtype' => $arr['measurementType'][$i], 'mrem' => $arr['measurementRemarks'][$i], 'lifestage' => $arr['eol life stage'][$i]);
                    $i++;
                }
            }
        }
        return $final;
        /* orig working
        if($spreadsheet) {
            if($arr = $func->convert_spreadsheet($spreadsheet, 0, $spreadsheet_options)) {
                 foreach($fields as $key => $value) {
                     $i = 0;
                     if(@$arr[$key]) {
                         foreach($arr[$key] as $item) {
                             $item = trim($item);
                             if($item) {
                                 $temp = $arr[$value][$i];
                                 $temp = trim(str_replace(array("\n"), "", $temp));
                                 $uris[$item] = $temp;
                                 if(!Functions::is_utf8($temp)) echo "\nnot utf8: [$temp]\n";
                             }
                             $i++;
                         }
                     }
                 }
            }
        }
        return $uris;
        */
    }
    private function parse_big_json_file()
    {
        $this->uris = self::get_uris($this->spreadsheet_mappings);
        if($jsonfile = Functions::save_remote_file_to_local($this->service["taxon"], $this->download_options)) {}
        else exit("\n\nPartner server is un-available. \nProgram will terminate.\n\n");
        $i = 0;
        foreach(new FileIterator($jsonfile) as $line_number => $line) {
            $line = Functions::conv_to_utf8($line);
            $i++;
            if(($i % 10000) == 0) echo "\n" . " - $i ";
            // echo "\n-------------------------\n".$line;
            if(substr($line, 0, strlen('{"oid":')) == '{"oid":') {
                $str = substr($line, 0, -1); //remove last char (",") the comma, very important to convert from json to array.
                $arr = json_decode($str, true);
                $taxon_id = self::create_taxon_archive($arr);
                if($taxon_id === false) continue;
                if($taxon_id == false) continue;
                
                /* ver 1 obsolete
                // Important: Taxa that have "flg":"V" are synonyms, spelling variants, and variants with alternative ranks. For these we only want to use the taxon information as 
                // outlined in the taxa sheet.  Ignore measurements and vernaculars associated with these records.  
                if($flg = @$arr['flg']) {
                    if($flg == "V") continue;
                }
                */
                
                self::create_vernacular_archive($arr, $taxon_id);
                self::create_trait_archive($arr, $taxon_id);
            }
            // if($i > 1000) break; //debug
        }
        unlink($jsonfile);
    }
    private function generate_id_from_array_record($arr)
    {
        $json = json_encode($arr);
        // exit("\n[$json]\n");
        return md5($json);
    }
    private function create_trait_archive($a, $taxon_id)
    {
        $rec = array();
        $rec["taxon_id"] = $taxon_id;
        $rec["catnum"]   = self::generate_id_from_array_record($a);
        $rec['source']                = $this->source_url . self::numerical_part($a['oid']);
        $rec['bibliographicCitation'] = 'The Paleobiology Database, https://paleobiodb.org';
        //--------------------------------------------------------------------------------------------------------------------------------
        if(@$a['ext'] == '0') {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/ExtinctionStatus";
            $rec['measurementValue']    = "http://eol.org/schema/terms/extinct";
            $rec['measurementRemarks']  = '';
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        if(@$a['ext'] == '1') {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/ExtinctionStatus";
            $rec['measurementValue']    = "http://eol.org/schema/terms/extant";
            $rec['measurementRemarks']  = '';
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        //--------------------------------------------------------------------------------------------------------------------------------
        if($arr = @$this->uris['interval values'][@$a['tei']]) { //interval values
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/FossilFirst"; //hard-coded
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = $arr['mrem'];
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        if($arr = @$this->uris['interval values'][@$a['tli']]) { //interval values
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/FossilLast"; //hard-coded
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = $arr['mrem'];
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        //--------------------------------------------------------------------------------------------------------------------------------
        if($val = @$a['fea']) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/FossilFirst";
            $rec['measurementValue']    = $val;
            $rec['measurementRemarks']  = '';
            $rec['measurementUnit']     = 'http://eol.org/schema/terms/paleo_megaannum';
            $rec['statisticalMethod']   = 'http://semanticscience.org/resource/SIO_001114';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        if($val = @$a['lla']) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/FossilLast";
            $rec['measurementValue']    = $val;
            $rec['measurementRemarks']  = '';
            $rec['measurementUnit']     = 'http://eol.org/schema/terms/paleo_megaannum';
            $rec['statisticalMethod']   = 'http://semanticscience.org/resource/SIO_001113';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        
        if($val = @$a['jtc']) $jco_jsa_jth_jsr_addtl_rem = "Inferred from $val.";
        else                  $jco_jsa_jth_jsr_addtl_rem = "";
        //--------------------------------------------------------------------------------------------------------------------------------
        /* working OK but only for single values
        if($arr = @$this->uris['skeletal composition values'][@$a['jco']]) {
            $cont = true;
            $val = @$a['jco'];
            if($val == "no hard parts") $cont = false;
            
            if($cont) {
                $rec['measurementOfTaxon']  = "true";
                $rec['measurementType']     = $arr['mtype'];
                $rec['measurementValue']    = $arr['uri'];
                $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jco_jsa_jth_jsr_addtl_rem);
                self::add_string_types($rec);
            }
        }
        */
        if($var = @$a['jco']) { //an approach that also deals with multiple values
            $var_arr = explode(",", $var);
            $var_arr = self::optimize_array($var_arr);
            $i = 0;
            foreach($var_arr as $var) {
                if($arr = @$this->uris['skeletal composition values'][$var]) {
                    $cont = true;
                    if($var == "no hard parts") $cont = false;
                    if($cont) {
                        $i++;
                        $rec['measurementOfTaxon']  = "true";
                        $rec['measurementType']     = ($i == 1) ? $arr['mtype'] : "http://eol.org/schema/terms/skeletalComp2";
                        $rec['measurementValue']    = $arr['uri'];
                        $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jco_jsa_jth_jsr_addtl_rem);
                        $rec['measurementUnit']     = '';
                        $rec['statisticalMethod']   = '';
                        $rec['lifestage']           = '';
                        self::add_string_types($rec);
                    }
                }
            }
        }
        //--------------------------------------------------------------------------------------------------------------------------------
        if($arr = @$this->uris['skeletal structure values'][@$a['jsa']]) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = $arr['mtype'];
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = $arr['mrem'];
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        //--------------------------------------------------------------------------------------------------------------------------------
        if($arr = @$this->uris['skeleton thickness values'][@$a['jth']]) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = $arr['mtype'];
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = $arr['mrem'];
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        //--------------------------------------------------------------------------------------------------------------------------------
        /* working but just single values
        if($arr = @$this->uris['skeletal reinforcement values'][@$a['jsr']]) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = $arr['mtype'];
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = $arr['mrem'];
            self::add_string_types($rec);
        }
        */
        if($var = @$a['jsr']) { //an approach that also deals with multiple values
            $var_arr = explode(",", $var);
            $var_arr = self::optimize_array($var_arr);
            $i = 0;
            foreach($var_arr as $var) {
                if($arr = @$this->uris['skeletal reinforcement values'][$var]) {
                    $cont = true;
                    if($cont) {
                        $i++;
                        $rec['measurementOfTaxon']  = "true";
                        $rec['measurementType']     = $arr['mtype'];
                        $rec['measurementValue']    = $arr['uri'];
                        $rec['measurementRemarks']  = $arr['mrem'];
                        $rec['measurementUnit']     = '';
                        $rec['statisticalMethod']   = '';
                        $rec['lifestage']           = '';
                        self::add_string_types($rec);
                    }
                }
            }
        }
        
        if($val = @$a['jdc']) $jdt_addtl_rem = "Inferred from $val.";
        else                  $jdt_addtl_rem = "";
        //--------------------------------------------------------------------------------------------------------------------------------
        /* OK but only for single values
        if($arr = @$this->uris['diet values'][@$a['jdt']]) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = $arr['mtype'];
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jdt_addtl_rem);
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        */
        if($var = @$a['jdt']) { //an approach that also deals with multiple values
            $var_arr = explode(",", $var);
            $var_arr = self::optimize_array($var_arr);
            $i = 0;
            foreach($var_arr as $var) {
                if($arr = @$this->uris['diet values'][$var]) {
                    $cont = true;
                    if($cont) {
                        $i++;
                        $rec['measurementOfTaxon']  = "true";
                        $rec['measurementType']     = $arr['mtype'];
                        $rec['measurementValue']    = $arr['uri'];
                        $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jdt_addtl_rem);
                        $rec['measurementUnit']     = '';
                        $rec['statisticalMethod']   = '';
                        $rec['lifestage']           = '';
                        self::add_string_types($rec);
                    }
                }
            }
        }

        if($val = @$a['jec']) $jev_addtl_rem = "Inferred from $val.";
        else                  $jev_addtl_rem = "";
        //--------------------------------------------------------------------------------------------------------------------------------
        /* OK but only for single values
        if($arr = @$this->uris['environment values'][@$a['jev']]) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = $arr['mtype'];
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jev_addtl_rem);
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = $arr['lifestage'];
            self::add_string_types($rec);
        }
        */
        if($var = @$a['jev']) { //an approach that also deals with multiple values
            $var_arr = explode(",", $var);
            $var_arr = self::optimize_array($var_arr);
            $i = 0;
            foreach($var_arr as $var) {
                if($arr = @$this->uris['environment values'][$var]) {
                    $cont = true;
                    if($cont) {
                        $i++;
                        $rec['measurementOfTaxon']  = "true";
                        $rec['measurementType']     = $arr['mtype'];
                        $rec['measurementValue']    = $arr['uri'];
                        $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jev_addtl_rem);
                        $rec['measurementUnit']     = '';
                        $rec['statisticalMethod']   = '';
                        $rec['lifestage']           = $arr['lifestage'];
                        self::add_string_types($rec);
                    }
                }
            }
        }



        if($val = @$a['jhc']) $jlh_addtl_rem = "Inferred from $val.";
        else                  $jlh_addtl_rem = "";
        //--------------------------------------------------------------------------------------------------------------------------------
        if($arr = @$this->uris['life mode values'][@$a['jlh']]) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = $arr['mtype'];
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jlh_addtl_rem);
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }

        if($val = @$a['jmc']) $jmo_addtl_rem = "Inferred from $val.";
        else                  $jmo_addtl_rem = "";
        //--------------------------------------------------------------------------------------------------------------------------------
        /* OK but just for single values
        if($arr = @$this->uris['motility values'][@$a['jmo']]) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = $arr['mtype'];
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jmo_addtl_rem);
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = $arr['lifestage'];
            self::add_string_types($rec);
        }
        */
        if($var = @$a['jmo']) { //an approach that also deals with multiple values
            $var_arr = explode(",", $var);
            $var_arr = self::optimize_array($var_arr);
            $i = 0;
            foreach($var_arr as $var) {
                if($arr = @$this->uris['motility values'][$var]) {
                    $cont = true;
                    if($cont) {
                        $i++;
                        $rec['measurementOfTaxon']  = "true";
                        $rec['measurementType']     = $arr['mtype'];
                        $rec['measurementValue']    = $arr['uri'];
                        $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jmo_addtl_rem);
                        $rec['measurementUnit']     = '';
                        $rec['statisticalMethod']   = '';
                        $rec['lifestage']           = $arr['lifestage'];
                        self::add_string_types($rec);
                    }
                }
            }
        }
        
        

        if($val = @$a['jrc']) $jre_addtl_rem = "Inferred from $val.";
        else                  $jre_addtl_rem = "";
        //--------------------------------------------------------------------------------------------------------------------------------
        /* OK but only for single values
        if($arr = @$this->uris['reproduction values'][@$a['jre']]) {
            // ignore these:
            // brooding
            // dispersal=.*?
            $cont = true;
            $val = @$a['jre'];
            if($val == "brooding") $cont = false;
            if(substr($val, 0, 10) == "dispersal=") $cont = false;
            if($cont) {
                $rec['measurementOfTaxon']  = "true";
                $rec['measurementType']     = $arr['mtype'];
                $rec['measurementValue']    = $arr['uri'];
                $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jre_addtl_rem);
                $rec['measurementUnit']     = '';
                $rec['statisticalMethod']   = '';
                $rec['lifestage']           = '';
                self::add_string_types($rec);
            }
        }
        */
        if($var = @$a['jre']) { //an approach that also deals with multiple values
            $var_arr = explode(",", $var);
            $var_arr = self::optimize_array($var_arr);
            $i = 0;
            foreach($var_arr as $var) {
                if($arr = @$this->uris['reproduction values'][$var]) {
                    $cont = true;
                    if($var == "brooding") $cont = false;
                    if(substr($var, 0, 10) == "dispersal=") $cont = false;
                    if($cont) {
                        $i++;
                        $rec['measurementOfTaxon']  = "true";
                        $rec['measurementType']     = $arr['mtype'];
                        $rec['measurementValue']    = $arr['uri'];
                        $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jre_addtl_rem);
                        $rec['measurementUnit']     = '';
                        $rec['statisticalMethod']   = '';
                        $rec['lifestage']           = '';
                        self::add_string_types($rec, $a);
                    }
                }
            }
        }
        

        if($val = @$a['jvc']) $jvs_addtl_rem = "Inferred from $val.";
        else                  $jvs_addtl_rem = "";
        //--------------------------------------------------------------------------------------------------------------------------------
        if($arr = @$this->uris['vision values'][@$a['jvs']]) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = $arr['mtype'];
            $rec['measurementValue']    = $arr['uri'];
            $rec['measurementRemarks']  = self::write_remarks($arr['mrem'], $jvs_addtl_rem);
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        //--------------------------------------------------------------------------------------------------------------------------------
        if($val = @$a['noc']) {
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/fossilOccPBDB";
            $rec['measurementValue']    = $val;
            $rec['measurementRemarks']  = '';
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec['lifestage']           = '';
            self::add_string_types($rec);
        }
        //-------------------------------------------------------------------------------------------------------------------------------- for all measurements:
        
        /* just a template from another resource
        $rec = array();
        $rec["taxon_id"]            = $line['taxon_id'];
        $rec["catnum"]              = "_" . str_replace(" ", "_", $line['text']);
        $rec['measurementOfTaxon']  = "true";
        $rec['measurementType']     = "http://eol.org/schema/terms/Habitat";
        $rec['measurementValue']    = $uri;
        $rec['measurementMethod']   = 'text mining';
        $rec["contributor"]         = '<a href="http://environments-eol.blogspot.com/2013/03/welcome-to-environments-eol-few-words.html">Environments-EOL</a>';
        $rec["source"]              = "http://eol.org/pages/" . str_replace('EOL:', '', $rec["taxon_id"]);
        $rec['measurementRemarks']  = "source text: \"" . $line['text'] . "\"";
        if($val = self::get_reference_ids($line)) $rec['referenceID'] = implode("; ", $val);
        self::add_string_types($rec);
        */
    }
    private function write_remarks($rem1, $rem2)
    {
        $rem1 = trim($rem1);
        $rem2 = trim($rem2);
        $final = "";
        if($rem1) $final = $rem1;
        if($rem2) $final .= " $rem2";
        return trim($final);
    }
    private function add_string_types($rec, $a = false) //$a is only for debugging
    {
        if(!@$rec['measurementType']) {
            print_r($rec);
            print_r($a);
            exit("\nMight need to investigate.\n");
        }

        // /* from DATA-1814
        $rec = self::adjustments_per_Jen_Katja($rec);
        if(!$rec) return;
        // */

        // /* DATA-1841 terms remapping
        if($new_uri = @$this->func->remapped_terms[$rec['measurementType']]) $rec['measurementType'] = $new_uri;
        if($new_uri = @$this->func->remapped_terms[$rec['measurementValue']]) $rec['measurementValue'] = $new_uri;
        // */
        
        /* https://eol-jira.bibalex.org/browse/DATA-1831?focusedCommentId=64627&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64627
        One more misunderstood clade. I think the easiest filter would be to remove all trait records with this string in measurementRemarks:
        "Inferred from Echinoidea."
        It could probably also be done through the resource taxon hierarchy if that's easier or cleaner...
        */
        if(stripos($rec['measurementRemarks'], "Inferred from Echinoidea") !== false) return; //string is found
        
        
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"], $rec);
        unset($rec['catnum']);
        unset($rec['taxon_id']);
        unset($rec['lifestage']);
        
        $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID = $occurrence_id;
        foreach($rec as $key => $value) $m->$key = $value;
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }
    private function adjustments_per_Jen_Katja($rec)
    {
        if($rec['measurementType'] == 'http://eol.org/schema/terms/skeletalComp2') {
            $rec['measurementType'] = 'http://purl.obolibrary.org/obo/OBA_1000106';
            if($rec['measurementRemarks']) $rec['measurementRemarks'] .= ". secondary component";
            else                         $rec['measurementRemarks'] = "secondary component";
        }
        /*
        where measurementType=http://eol.org/schema/terms/skeletalComp2
        change it to http://purl.obolibrary.org/obo/OBA_1000106, and add the text string "secondary component" to measurementRemarks, concatenating if needed.
        */
        
        $mValues = array('http://purl.obolibrary.org/obo/CHEBI_52239', 'http://purl.obolibrary.org/obo/CHEBI_52255', 'http://eol.org/schema/terms/lowMgCalcite', 
                         'http://purl.obolibrary.org/obo/CHEBI_64389', 'http://eol.org/schema/terms/highMgCalcite', 'http://eol.org/schema/terms/intermediateMgCalcite', 
                         'http://purl.obolibrary.org/obo/CHEBI_26020');
        if($rec['measurementType'] == 'http://purl.obolibrary.org/obo/OBA_1000106' && in_array($rec['measurementValue'], $mValues)) return false;
        /*
        Where measurementType=http://purl.obolibrary.org/obo/OBA_1000106
        and measurementValue= one of these:
        http://purl.obolibrary.org/obo/CHEBI_52239      http://purl.obolibrary.org/obo/CHEBI_52255
        http://eol.org/schema/terms/lowMgCalcite        http://purl.obolibrary.org/obo/CHEBI_64389
        http://eol.org/schema/terms/highMgCalcite       http://eol.org/schema/terms/intermediateMgCalcite
        http://purl.obolibrary.org/obo/CHEBI_26020
        remove the record (they duplicate a better curated source)
        */
        
        if($rec['measurementType'] == 'http://purl.obolibrary.org/obo/OBA_1000106' && $rec['measurementValue'] == 'http://purl.obolibrary.org/obo/PORO_0000108') {
            $rec['measurementType'] = 'http://eol.org/schema/terms/SkeletalReinforcement';
        }
        /*
        Where measurementType=http://purl.obolibrary.org/obo/OBA_1000106
        and measurementValue=http://purl.obolibrary.org/obo/PORO_0000108
        change measurementType to
        http://eol.org/schema/terms/SkeletalReinforcement
        */
        
        // /* adjustment: DATA-1831 - So for taxa with and records with measurementValue=http://eol.org/schema/terms/extant , please remove any records with measurementType=http://eol.org/schema/terms/FossilLast
        if($rec['measurementType'] == 'http://eol.org/schema/terms/FossilLast' && 
           $rec['measurementValue'] == 'http://eol.org/schema/terms/extant') return false;
        // */
        
        return $rec;
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
    private function create_vernacular_archive($a, $taxon_id)
    {
        if($vernacular = @$a[$this->map['vernacularName']]) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $taxon_id;
            $v->vernacularName  = $vernacular;
            $v->language        = 'en';
            $this->archive_builder->write_object_to_file($v);
        }
    }
    private function create_taxon_archive($a)
    {
        /*(
            [oid] => txn:10
            [rnk] => 5
            [nam] => Actinomma
            [att] => Haeckel 1862
            [par] => txn:64926
            [rid] => ref:6930
            [ext] => 1
            [noc] => 97
            [fea] => 247.2
            [fla] => 242
            [lea] => 86.3
            [lla] => 70.6
            [tei] => Middle Triassic
            [tli] => Cretaceous
            [siz] => 10
            [exs] => 1
            [cll] => Radiolaria
            [cln] => txn:4
            [odl] => Spumellaria
            [odn] => txn:5
            [fml] => Actinommidae
            [fmn] => txn:64926
            [gnl] => Actinomma
            [gnn] => txn:10
            [jev] => marine
            [jec] => Radiolaria
            [jmo] => passively mobile
            [jmc] => Radiolaria
            [jlh] => planktonic
            [jhc] => Radiolaria
            [jdt] => omnivore
            [jdc] => Radiolaria
            [jco] => silica
            [jtc] => Radiolaria
            [ref] => J. J. Sepkoski, Jr. 2002. A compendium of fossil marine animal genera. Bulletins of American Paleontology 363:1-560
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonomicStatus          = self::compute_taxonomicStatus($a);
        $this->debug['taxonomicStatus'][$taxon->taxonomicStatus] = '';
        $taxon->taxonID                  = self::compute_taxonID($a, $taxon->taxonomicStatus);
        $taxon->scientificName           = $a[$this->map['scientificName']];
        if(!$taxon->scientificName) return false;
        $taxon->scientificNameAuthorship = @$a[$this->map['scientificNameAuthorship']];
        $taxon->taxonRank                = self::compute_taxonRank($a);
        $taxon->acceptedNameUsageID      = self::numerical_part(@$a[$this->map['acceptedNameUsageID']]);
        $taxon->nameAccordingTo          = @$a[$this->map['nameAccordingTo']];

        if($val = @$a[$this->map['taxonID']]) $taxon->furtherInformationURL = $this->source_url . self::numerical_part($val);

        if(@$a[$this->map['acceptedNameUsageID']]) {} //acceptedNameUsageID => "acc"
        else {
            $taxon->parentNameUsageID = self::numerical_part(@$a[$this->map['parentNameUsageID']]);
            $taxon->phylum  = @$a[$this->map['phylum']];
            $taxon->class   = @$a[$this->map['class']];
            $taxon->order   = @$a[$this->map['order']];
            $taxon->family  = @$a[$this->map['family']];
            $taxon->genus   = @$a[$this->map['genus']];
        }

        if($rank = @$taxon->taxonRank) { //by Eli alone: if taxon is genus then exclude genus from ancestry.
            if(in_array($rank, array('kingdom', 'phylum', 'class', 'order', 'family', 'genus'))) $taxon->$rank = "";
        }
        
        if($taxonID = @$taxon->taxonID) {
            if(in_array($taxonID, $this->descendants_of_parents_without_entries)) {
                return false;
            }
        }
        if($taxon->taxonomicStatus == 'invalid subgroup') return false;
        $this->archive_builder->write_object_to_file($taxon);
        
        // Important: Taxa that have an acc parameter are synonyms, spelling variants, and variants with alternative ranks. For these we only want to use the taxon information as outlined above. 
        // Ignore measurements and vernaculars associated with these records.               
        if(@$a[$this->map['acceptedNameUsageID']]) {
            $this->debug['synonym statuses'][$taxon->taxonomicStatus] = '';
            return false;
        }
        
        return $taxon->taxonID;
    }
    private function compute_taxonID($a, $taxon_status)
    {
        /* ver 1 obsolete
        if($vid = @$a['vid']) return self::numerical_part($a['oid'])."-".self::numerical_part($vid);
        else                  return self::numerical_part($a['oid']);
        */
        // /* latest ver: https://eol-jira.bibalex.org/browse/TRAM-746?focusedCommentId=62820&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62820
        // This should be the taxonID if taxonomicStatus = accepted, i.e., if there are is no acc parameter. Use only the numerical part. 
        // If there is an acc parameter, modify the oid, by appending an integer, making sure taxonIDs remain unique if there are multiple synonyms; 
        // i. e., if the oid is 123, make the taxonID 123-1 for the first synonym, 123-2 for the second synonym, etc.

        // just for reference
        // $this->map['acceptedNameUsageID']       = "acc";
        // $this->map['taxonID']                   = "oid";
        // $this->map['parentNameUsageID']         = "par";
        // $this->map['taxonRank']                 = "rnk";
        // $this->map['taxonomicStatus']           = "tdf";

        // if($tdf = @$a[$this->map['taxonomicStatus']]) $this->debug['taxonomicStatus'][$tdf] = ''; //just for debug stats

        if($taxon_status == 'accepted') {
            if($acc = @$a['acc']) exit("\nShould not go here!\n");
            return self::numerical_part($a['oid']);
        }
        if($acc = @$a['acc']) {
            $taxon_id = self::numerical_part($a['oid']);
            @$this->taxon_id_synonym_count[$taxon_id]++;
            return $taxon_id."_".$this->taxon_id_synonym_count[$taxon_id];
        }
        else return self::numerical_part($a['oid']);
        // */
    }
    private function numerical_part($var)
    {
        if(!$var) return "";
        $temp = explode(":", $var);
        return $temp[1];
    }
    private function compute_taxonRank($a)
    {
        $mappings = self::get_rank_mappings();
        if($num = @$a[$this->map['taxonRank']]) {
            if($val = $mappings[$num]) return $val;
        }
        return "";
    }
    private function compute_taxonomicStatus($a)
    {
        $mappings = self::get_taxon_status_mappings();
        if($str_index = @$a[$this->map['taxonomicStatus']]) {
            if($val = $mappings[$str_index]) return $val;
        }
        return "accepted";
    }
    private function optimize_array($arr)
    {
        $arr = array_map('trim', $arr); //trim all individual array values
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        return $arr;
    }
    private function get_taxon_status_mappings()
    {
        $s['invalid subgroup of'] = "invalid subgroup";
        $s['nomen dubium'] = "nomen dubium";
        $s['nomen nudum'] = "nomen nudum";
        $s['nomen oblitum'] = "nomen oblitum";
        $s['nomen vanum'] = "nomen vanum";
        $s['objective synonym of'] = "objective synonym";
        $s['replaced by'] = "replaced";
        $s['subjective synonym of'] = "subjective synonym";
        $s['corrected to'] = "corrected";
        $s['misspelling of'] = "misspelling";
        $s['obsolete variant of'] = "obsolete variant";
        $s['reassigned as'] = "reassigned";
        $s['recombined as'] = "recombined";
        $s['no value'] = "accepted";
        $s[''] = "accepted";
        return $s;
    }
    private function get_rank_mappings()
    {
        $r[2] = "subspecies";
        $r[3] = "species";
        $r[4] = "subgenus";
        $r[5] = "genus";
        $r[6] = "subtribe";
        $r[7] = "tribe";
        $r[8] = "subfamily";
        $r[9] = "family";
        $r[10] = "superfamily";
        $r[11] = "infraorder";
        $r[12] = "suborder";
        $r[13] = "order";
        $r[14] = "superorder";
        $r[15] = "infraclass";
        $r[16] = "subclass";
        $r[17] = "class";
        $r[18] = "superclass";
        $r[19] = "subphylum";
        $r[20] = "phylum";
        $r[21] = "superphylum";
        $r[22] = "subkingdom";
        $r[23] = "kingdom";
        $r[25] = "unranked clade";
        $r[26] = "informal";
        return $r;
    }

    function get_descendants_given_parent_ids($dwca_file, $parent_ids)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 0, 'cahce' => 0)); //true means it will re-download, will not use cache. Set TRUE when developing
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];

        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        
        $taxa_records = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');
        $this->parentID_taxonID = self::get_ids($taxa_records);
        $descendant_ids = self::get_all_descendants_of_these_parents($parent_ids);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        
        return $descendant_ids;
    }
    private function get_all_descendants_of_these_parents($parent_ids)
    {
        echo "\ncount parent_ids = ".count($parent_ids)."\n";
        echo "\ncount parentID_taxonID = ".count($this->parentID_taxonID)."\n";
        
        $final = array();
        foreach($parent_ids as $parent_id) {
            $ids = self::get_descendants_of_this_parent($parent_id);
            debug("\ndescendants of [$parent_id]: ".count($ids));
            if($ids) $final = array_merge($final, $ids);
        }
        return array_unique($final);
    }
    private function get_descendants_of_this_parent($parent_id)
    {
        $final = array();
        if($arr = @$this->parentID_taxonID[$parent_id]) {
            $final = array_merge($final, $arr);
            foreach($arr as $parent_id2) {
                if($arr2 = @$this->parentID_taxonID[$parent_id2]) {
                    $final = array_merge($final, $arr2);
                    foreach($arr2 as $parent_id3) {
                        if($arr3 = @$this->parentID_taxonID[$parent_id3]) {
                            $final = array_merge($final, $arr3);
                            foreach($arr3 as $parent_id4) {
                                if($arr4 = @$this->parentID_taxonID[$parent_id4]) {
                                    $final = array_merge($final, $arr4);
                                    foreach($arr4 as $parent_id5) {
                                        if($arr5 = @$this->parentID_taxonID[$parent_id5]) {
                                            $final = array_merge($final, $arr5);
                                            foreach($arr5 as $parent_id6) {
                                                if($arr6 = @$this->parentID_taxonID[$parent_id6]) {
                                                    $final = array_merge($final, $arr6);
                                                    foreach($arr6 as $parent_id7) {
                                                        if($arr7 = @$this->parentID_taxonID[$parent_id7]) {
                                                            $final = array_merge($final, $arr7);
                                                            foreach($arr7 as $parent_id8) {
                                                                if($arr8 = @$this->parentID_taxonID[$parent_id8]) {
                                                                    $final = array_merge($final, $arr8);
                                                                    foreach($arr8 as $parent_id9) {
                                                                        if($arr9 = @$this->parentID_taxonID[$parent_id9]) {
                                                                            $final = array_merge($final, $arr9);
                                                                            foreach($arr9 as $parent_id10) {
                                                                                if($arr10 = @$this->parentID_taxonID[$parent_id10]) {
                                                                                    $final = array_merge($final, $arr10);
                                                                                    foreach($arr10 as $parent_id11) {
                                                                                        if($arr11 = @$this->parentID_taxonID[$parent_id11]) {
                                                                                            $final = array_merge($final, $arr11);
                                                                                            foreach($arr11 as $parent_id12) {
                                                                                                if($arr12 = @$this->parentID_taxonID[$parent_id12]) {
                                                                                                    $final = array_merge($final, $arr12);
                                                                                                    foreach($arr12 as $parent_id13) {
                                                                                                        if($arr13 = @$this->parentID_taxonID[$parent_id13]) {
                                                                                                            $final = array_merge($final, $arr13);
                                                                                                            foreach($arr13 as $parent_id14) {
                                                                                                                if($arr14 = @$this->parentID_taxonID[$parent_id14]) {
                                                                                                                    $final = array_merge($final, $arr14);

        foreach($arr14 as $parent_id15) {
            if($arr15 = @$this->parentID_taxonID[$parent_id15]) {
                $final = array_merge($final, $arr15);
                foreach($arr15 as $parent_id16) {
                    if($arr16 = @$this->parentID_taxonID[$parent_id16]) {
                        $final = array_merge($final, $arr16);
                        foreach($arr16 as $parent_id17) {
                            if($arr17 = @$this->parentID_taxonID[$parent_id17]) {
                                $final = array_merge($final, $arr17);
                                foreach($arr17 as $parent_id18) {
                                    if($arr18 = @$this->parentID_taxonID[$parent_id18]) {
                                        $final = array_merge($final, $arr18);
                                        foreach($arr18 as $parent_id19) {
                                            if($arr19 = @$this->parentID_taxonID[$parent_id19]) {
                                                $final = array_merge($final, $arr19);
                                                foreach($arr19 as $parent_id20) {
                                                    if($arr20 = @$this->parentID_taxonID[$parent_id20]) {
                                                        $final = array_merge($final, $arr20);
                                                        exit("\nreached level 20\n");
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return array_unique($final);
    }
    private function get_ids($records)
    {
        $final = array();
        foreach($records as $rec) {
            /* sample $rec
            Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 15082
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => https://paleobiodb.org/classic/checkTaxonInfo?taxon_no=15082
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => Kosmermoceras
                [http://rs.tdwg.org/dwc/terms/nameAccordingTo] => W. J. Arkell. 1952. Jurassic ammonites from Jebel Tuwaiq, central Arabia. Philosophical Transactions of the Royal Society of London, Series B, Biological Sciences 236:241-313
                [http://rs.tdwg.org/dwc/terms/kingdom] => 
                [http://rs.tdwg.org/dwc/terms/taxonRank] => genus
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => (Arkell 1952)
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 298980
                [http://rs.tdwg.org/dwc/terms/phylum] => Mollusca
                [http://rs.tdwg.org/dwc/terms/class] => Cephalopoda
                [http://rs.tdwg.org/dwc/terms/order] => Ammonitida
                [http://rs.tdwg.org/dwc/terms/family] => Stephanoceratidae
                [http://rs.tdwg.org/dwc/terms/genus] => 
            )
            */
            $parent_id = @$rec["http://rs.tdwg.org/dwc/terms/parentNameUsageID"];
            $taxon_id = @$rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            if($parent_id && $taxon_id) $final[$parent_id][] = $taxon_id;
        }
        return $final;
    }

}
?>