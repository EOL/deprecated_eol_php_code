<?php
namespace php_active_record;
/* connector: [708] */
class EnvironmentsEOLDataConnector
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        $this->taxon_ids = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();

        
        /* add: 'resource_id' => "eol_api" ;if you want to add the cache inside a folder [eol_api] inside [eol_cache] */
        $this->download_options = array(
            'resource_id'        => 'eol_api',                              //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //since taxon_concept_id and hierarchy_entry_id won't change the resulting API response won't also change. Another option is 1 year to expire
            'download_wait_time' => 3000000, 'timeout' => 3600, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        
        if(Functions::is_production()) {
            $this->species_list_export = "http://download.jensenlab.org/EOL/eol_env_annotations_noParentTerms.tar.gz";  //still works Aug 6, 2018
            $this->download_options['cache_path'] = '/extra/eol_php_cache/';
        }
        else {
            $this->species_list_export = "http://localhost/cp/Environments/eol_env_annotations_noParentTerms.tar.gz";   //local
            $this->download_options['cache_path'] = '/Volumes/Thunderbolt4/eol_cache/'; //used in Functions.php for all general cache
        }
        
        $this->file['marine_terms'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Environments/marine_terms.csv";
        $this->file['terrestrial_taxa'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Environments/terrestrial_taxa.tsv";
        $this->file['dangling_terrestrial_taxa'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Environments/dangling terrestrial taxa.txt";
        
        // stats
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->need_to_check_tc_id_dump_file = $this->TEMP_DIR . "need_to_check_tc_id.txt";
        $this->debug = array();
        exit("\n\nObsolete. See 708.php for more details.\n\n");
    }
    /*
    Array(
        [0] => EOL:194
        [1] => 25066375;http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat
        [2] => scrub forest
        [3] => ENVO:00000300
    )
    Array(
        [0] => EOL:21586
        [1] => 31568075;http://www.eol.org/voc/table_of_contents#Wikipedia;http://en.wikipedia.org/w/index.php?title=Beenakia_dacostae&oldid=632149823
        [2] => forests
        [3] => ENVO:01000174
        [4] => "Beenakia dacostae." <i>Wikipedia, The Free Encyclopedia</i>. 22 Jul 2014, 07:03 UTC. 3 Nov 2014 &lt;<a href="http://en.wikipedia.org/w/index.php?title=Beenakia_dacostae&oldid=632149823">http://en.wikipedia.org/w/index.php?title=Beenakia_dacostae&oldid=632149823</a>&gt;.
    )
    */
    function generate_EnvEOL_data()
    {
        /* obsolete doesn't work anymore...
        require_library('connectors/IUCNRedlistDataConnector');
        $func = new IUCNRedlistDataConnector();
        $basenames = array("eol_env_annotations_noParentTerms"); // list of needed basenames
        $options = $this->download_options;
        $options['expire_seconds'] = 0; //2592000 * 3; // 3 months before cache expires
        $text_path = $func->load_zip_contents($this->species_list_export, $options, $basenames, ".tsv");
        print_r($text_path); exit;
        */
        
        $tsv_filename = 'eol_env_annotations_noParentTerms.tsv';
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->species_list_export, $tsv_filename, array('timeout' => 172800, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $temp_dir = $paths['temp_dir'];
        // print_r($paths); exit;
        
        self::csv_to_array($temp_dir.$tsv_filename);
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        $parts = pathinfo($temp_dir.$tsv_filename);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
        recursive_rmdir($this->TEMP_DIR); // comment this if u want to check "need_to_check_tc_id.txt"

        /* run problematic tc_ids with cache=0 --- a utility
        $tc_ids = self::get_dump();
        foreach($tc_ids as $tc_id) {
            $rec['taxon_id'] = $tc_id;
            self::prepare_taxon($rec);
        }
        exit("\n-exit-\n");
        */
        if($this->debug) print_r($this->debug);
    }
    private function get_excluded_eol_ids($file = false)
    {
        if($file) $source_files = array($file);
        else      $source_files = array($this->file['terrestrial_taxa'], $this->file['dangling_terrestrial_taxa']);
        foreach($source_files as $source_file) {
            $temp_file = Functions::save_remote_file_to_local($source_file, $this->download_options);
            $i = 0;
            foreach(new FileIterator($temp_file) as $line_number => $line) { // 'true' will auto delete temp_filepath
                $i++;
                if($i == 1) $line = strtolower($line);
                $row = explode("\t", $line);
                if($i == 1) {
                    $fields = $row;
                    continue;
                }
                else {
                    if(!@$row[0]) continue; //$row[0] is gbifID
                    $k = 0; $rec = array();
                    foreach($fields as $fld) {
                        $rec[$fld] = $row[$k];
                        $k++;
                    }
                }
                // print_r($rec); exit("\nstopx\n");
                if($val = trim(@$rec['taxon id'])) $final[$val] = '';   //for original file
                if($val = trim(@$rec['taxon url'])) $final[$val] = '';  //for dangling file
            }
            unlink($temp_file);
            echo "\n[$source_file: $i] [".count($final)."]\n";
        }
        return $final;
    }
    private function get_excluded_terms()
    {
        $contents = file_get_contents($this->file['marine_terms']);
        $arr = explode("\n", $contents);
        foreach($arr as $uri) $final[trim($uri)] = '';
        return $final;
    }
    private function csv_to_array($tsv_file)
    {
        /* investigates if 'dangling' has taxon_ids not found in original 'terrestrial_taxa' file.
        $terrestrial_taxa = self::get_excluded_eol_ids($this->file['terrestrial_taxa']);
        $terrestrial_taxa = array_keys($terrestrial_taxa);
        $dangling_terrestrial_taxa = self::get_excluded_eol_ids($this->file['dangling_terrestrial_taxa']);
        // print_r($dangling_terrestrial_taxa); exit("\n111\n");
        $dangling_terrestrial_taxa = array_keys($dangling_terrestrial_taxa);
        $diff = array_diff($dangling_terrestrial_taxa, $terrestrial_taxa); //found in dangling but not found in original.
        print_r($diff); //confirmed there are those records...
        exit("\n-end tests...-\n");
        */
        
        //from here: https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=62965&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62965
        $this->excluded_eol_ids = self::get_excluded_eol_ids();
        $this->excluded_terms = self::get_excluded_terms();
        
        $excluded_uris = self::excluded_measurement_values(); //from here: https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=62373&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62373
        $fields = array("taxon_id", "do_id_subchapter", "text", "envo", "5th_col");
        $i = 0; $m = 1700000/5; // = 340000
        foreach(new FileIterator($tsv_file) as $line_number => $line) {
            $temp = explode("\t", $line);
            $i++;
            if(($i % 10000) == 0) echo "\n".number_format($i)." - ";
            
            /* breakdown when caching
            $cont = false;
            // if($i >= 1    && $i < $m)    $cont = true;
            // if($i >= $m   && $i < $m*2)  $cont = true;
            // if($i >= $m*2 && $i < $m*3)  $cont = true;
            // if($i >= $m*3 && $i < $m*4)  $cont = true;
            // if($i >= $m*4 && $i < $m*5)  $cont = true;
            if(!$cont) continue;
            */
            
            $rec = array();
            if(!$temp) continue;
            // if(count($temp) != 4) continue; //-- obsolete
            if(count($temp) != 5) continue;

            // fill-up record
            $k = 0;
            foreach($temp as $t) {
                $rec[$fields[$k]] = $t;
                $k++;
            }
            // $rec['taxon_id'] = "EOL:7225673"; //debug
            if($taxon = self::prepare_taxon($rec)) {
                // print_r($taxon); // good thing to show
                
                $uri = self::format_uri($rec['envo']);
                if(in_array($uri, $excluded_uris)) continue;
                
                $ret = self::create_data($taxon, $rec);
                if($ret) self::create_instances_from_taxon_object($taxon); //only create the taxa if trait is created
            }
            // if($i >= 10) break; //debug
        } // end foreach
    }
    private function prepare_taxon($rec)
    {
        $taxon = self::get_taxon_info($rec);
        return $taxon;
    }
    private function get_taxon_info($rec)
    {
        $taxon_id = $rec["taxon_id"];
        $included_ranks = array("kingdom", "phylum", "class", "order", "family", "genus");
        $taxon = array();
        $url = "http://eol.org/api/pages/1.0/" . str_replace('EOL:', '', $taxon_id) . ".json?images=0&videos=0&sounds=0&maps=0&text=0&iucn=false&subjects=overview&licenses=all&details=true&common_names=false&synonyms=false&references=false&vetted=0&cache_ttl=";
        $options = $this->download_options;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $arr = json_decode($json, true);
            // print_r($arr);
            $sciname = $arr['scientificName'];
            $match = false;
            if($tc = self::get_the_right_tc_record(@$arr['taxonConcepts'], $sciname)) {
                $match = true;
                if($he_id = $tc['identifier']) {
                    // echo "\n chosen tc: [$he_id]\n";
                    $taxon['taxon_id']       = $taxon_id;
                    $taxon['scientificName'] = $tc['scientificName']; // this is equal to $sciname
                    $taxon['rank']           = @$tc['taxonRank'];
                    $url = "http://eol.org/api/hierarchy_entries/1.0/" . $he_id . ".json?common_names=false&synonyms=false&cache_ttl=";
                    if($json = Functions::lookup_with_cache($url, $options)) {
                        $arr = json_decode($json, true);
                        // print_r($arr);
                        if(!$taxon['rank']) $taxon['rank'] = @$arr['taxonRank'];
                        $i = 0;

                        if($loop = @$arr['ancestors']) {
                            foreach($loop as $ancestor) {
                                if($rank = @$ancestor['taxonRank']) {
                                    if(in_array($rank, $included_ranks)) {
                                        $taxon['ancestry'][$rank] = $ancestor['scientificName'];
                                        $i++;
                                    }
                                }
                                if($i >= 2) break; // just two will be enough for mapping names during name reconciliation
                            }
                        }
                    }
                }
            } // if $tc exists
            if(!@$match) {
                // echo "\ninvestigate no real match [$taxon_id], no hierarchy_entry therefore no ancestry\n"; //e.g. http://eol.org/api/pages/1.0/6862766.xml?images=0&videos=0&sounds=0&maps=0&text=0&iucn=false&subjects=overview&licenses=all&details=true&common_names=false&synonyms=false&references=false&vetted=0&cache_ttl=
                $taxon['taxon_id']       = $taxon_id;
                $taxon['scientificName'] = $sciname;
                $taxon['ancestry'] = array();
                // will proceed without rank and ancestry
            }
        }
        return $taxon;
    }

    private function get_the_right_tc_record($tcs, $sciname)
    {
        if(!$tcs) return false;
        $tc_rec = false;
        foreach($tcs as $tc) {
            if($tc['scientificName'] == $sciname) { // 1st option
                $tc_rec = $tc;
                break;
            }
        }
        if(!$tc_rec) {
            foreach($tcs as $tc) {
                if($tc['scientificName'] == Functions::canonical_form($sciname)) { // 2nd option
                    $tc_rec = $tc;
                    break;
                }
            }
        }
        if(!$tc_rec) {
            foreach($tcs as $tc) {
                if(Functions::canonical_form($tc['scientificName']) == Functions::canonical_form($sciname)) { // 3rd option
                    $tc_rec = $tc;
                    break;
                }
            }
        }
        return $tc_rec;
    }
    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxon_id'];
        $taxon->scientificName          = $rec['scientificName'];
        if(!isset($rec['ancestry'])) self::save_to_dump($taxon->taxonID, $this->need_to_check_tc_id_dump_file);
        else {
            foreach(@$rec['ancestry'] as $rank => $name) {
                if(!$rank) continue;
                $taxon->$rank = $name;
            }
        }
        // echo " - $taxon->scientificName [$taxon->taxonID]";
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function create_data($record, $line)
    {
        /*
        Old:
        [taxon_id] => 57534
        [do_id_subchapter] => 26258437;http://www.eol.org/voc/table_of_contents#Wikipedia
        [text] => freshwater
        [envo] => ENVO:00002011
        
        New:
        Array(
            [taxon_id] => EOL:7
            [do_id_subchapter] => 25937933;http://www.eol.org/voc/table_of_contents#Wikipedia
            [text] => terrestrial
            [envo] => ENVO:00000446
        )
        */
        
        $line['text'] = str_replace(array("  ", "   "), " ", $line['text']);
        $line['text'] = strtolower($line['text']);
        
        /* old ways
        //works for: [do_id_subchapter] => 17763523;http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description
        $parts = explode("#", $line['do_id_subchapter']);
        $subject = str_replace(" ", "_", strtolower(trim($parts[1])));
        */
        
        //works for: [do_id_subchapter] => 17763523;http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description;http://eolspecies.lifedesks.org/pages/30554
        $temp = explode(";", $line['do_id_subchapter']);
        $parts = explode("#", $temp[1]);
        $subject = str_replace(" ", "_", strtolower(trim($parts[1])));
        
        if($subject == 'taxonbiology')           $subject = 'brief_summary';
        elseif($subject == 'biology')            $subject = 'comprehensive_description';
        elseif($subject == 'generaldescription') $subject = 'comprehensive_description';
        elseif($subject == 'description')        $subject = 'comprehensive_description';
        elseif($subject == 'wikipedia')             $subject = 'comprehensive_description'; // 'wikipedia' in EoL V2
        elseif($subject == 'habitat')               $subject = 'habitat';
        elseif($subject == 'reproduction')          $subject = 'reproduction';
        elseif($subject == 'distribution')          $subject = 'distribution';
        elseif($subject == 'trophicstrategy')       $subject = 'trophic_strategy';
        elseif($subject == 'behaviour')             $subject = 'behavior';
        elseif($subject == 'conservationstatus')    $subject = 'conservation_status';
        elseif($subject == 'lifecycle')             $subject = 'life_cycle';
        elseif($subject == 'dispersal')             $subject = 'dispersal';
        elseif($subject == 'ecology')               $subject = 'ecology';
        elseif($subject == 'physiology')            $subject = 'physiology';
        elseif($subject == 'conservation')          $subject = 'conservation';
        elseif($subject == 'populationbiology')     $subject = 'population_biology';
        elseif($subject == 'migration')             $subject = 'migration';
        elseif($subject == 'development')           $subject = 'development';
        elseif($subject == 'notes')                 $subject = 'notes';
        else {
            // print_r($line); exit("\nInvestigate subject: [$subject]\n");
            $this->debug['undefined subjects'][$subject] = '';
        }

        // print_r($line); exit("\n[$subject]\n");

        $uri = self::format_uri($line['envo']);
        $rec = array();
        $rec["taxon_id"]            = $line['taxon_id'];
        $rec["catnum"]              = "_" . str_replace(" ", "_", $line['text']);
        $rec['measurementOfTaxon']  = "true";
        $rec['measurementType']     = "http://eol.org/schema/terms/Habitat";
        $rec['measurementValue']    = $uri;
        $rec['measurementMethod']   = 'text mining';
        $rec["contributor"]         = '<a href="http://environments-eol.blogspot.com/2013/03/welcome-to-environments-eol-few-words.html">Environments-EOL</a>';

        /* old
        $rec["source"]              = "http://eol.org/pages/" . str_replace('EOL:', '', $rec["taxon_id"]) . "/details#". $subject;
        */
        $rec["source"]              = "http://eol.org/pages/" . str_replace('EOL:', '', $rec["taxon_id"]);
        
        $rec['measurementRemarks']  = "source text: \"" . $line['text'] . "\"";
        $ret = false;
        if($rec = self::adjustments($rec)) {
            if($val = self::get_reference_ids($line)) $rec['referenceID'] = implode("; ", $val);
            $ret = self::add_string_types($rec);
            if($ret) self::get_reference_ids($line, true);
        }
        return $ret;
    }
    private function get_reference_ids($line, $writeYN = false)
    {
        if($ref = $line['5th_col']) {
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref;
            $r->identifier = md5($ref);
            // $r->uri = '';
            if($writeYN) {
                if(!isset($this->resource_reference_ids[$r->identifier])) {
                   $this->resource_reference_ids[$r->identifier] = '';
                   $this->archive_builder->write_object_to_file($r);
                }
            }
            return array($r->identifier);
        }
        return false;
    }
    private function adjustments($rec) //https://eol-jira.bibalex.org/browse/DATA-1768
    {   /*this is for https://opendata.eol.org/dataset/environments-eol-project/resource/f5cfda47-d73f-4535-b729-79c8523a5300
        The partner will someday be able to resume work at their end, at which point we'll pass them this mapping, but for now, we need to clean some things up. Three methods so far:

        current term -> replace with
        http://purl.obolibrary.org/obo/ENVO_00000264 -> http://purl.obolibrary.org/obo/ENVO_01000342
        http://purl.obolibrary.org/obo/ENVO_00000550 -> http://purl.obolibrary.org/obo/ENVO_01000342

        match two:
        in records where
        measurementValue=http://purl.obolibrary.org/obo/ENVO_00000029 OR http://purl.obolibrary.org/obo/ENVO_00000104
        AND
        measurementRemarks=source text: "ravine"
        replace the measurementValue with http://purl.obolibrary.org/obo/ENVO_00000100

        finally, any other records where measurementValue=http://purl.obolibrary.org/obo/ENVO_00000104
        delete record

        More deleteable records:
        measurementValue=http://purl.obolibrary.org/obo/ENVO_00002033
        */
        //1st adjustment
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000264") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000342";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000550") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000342";
        
        // https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=62852&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62852
        // and, measurementValue terms to map to a different term:
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000303") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000144") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000569") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00002009") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000446";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000015") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00002227") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00002010";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000360") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000106") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000177";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00002011") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000873";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00002037") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000873";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000111") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000045") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000020";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000097") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000179";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000030") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000067";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000150") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000049";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_01000240") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_02000049") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00002150";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00002019") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000322";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000570") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000322";
        if($rec['measurementValue'] == "http://purl.obolibrary.org/obo/ENVO_00000190") $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000027";
        
        //2nd adjustment
        if(in_array($rec['measurementValue'], array("http://purl.obolibrary.org/obo/ENVO_00000029", "http://purl.obolibrary.org/obo/ENVO_00000104")) &&
           $rec['measurementRemarks'] == 'source text: "ravine"')                      $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000100";
         
         
        // https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=62853&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62853 
        // records to revise based on measurementRemarks:
        // where measurementRemarks= measurementValue
        if($rec['measurementRemarks'] == 'source text: "open waters"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        if($rec['measurementRemarks'] == 'source text: "open-water"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        if($rec['measurementRemarks'] == 'source text: "openwater"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        if($rec['measurementRemarks'] == 'source text: "open water"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        if($rec['measurementRemarks'] == 'source text: "dry stream beds"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        if($rec['measurementRemarks'] == 'source text: "dry streambeds"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        if($rec['measurementRemarks'] == 'source text: "dry stream-beds"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        if($rec['measurementRemarks'] == 'source text: "dry stream bed"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        if($rec['measurementRemarks'] == 'source text: "dry streambed"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        if($rec['measurementRemarks'] == 'source text: "coral heads"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000049";
        if($rec['measurementRemarks'] == 'source text: "coral head"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000049";
        if($rec['measurementRemarks'] == 'source text: "glades"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000444";
        if($rec['measurementRemarks'] == 'source text: "glade"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000444";
        if($rec['measurementRemarks'] == 'source text: "seaway"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        if($rec['measurementRemarks'] == 'source text: "tide way"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        if($rec['measurementRemarks'] == 'source text: "tideway"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        if($rec['measurementRemarks'] == 'source text: "sea-way"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        if($rec['measurementRemarks'] == 'source text: "herbaceous areas"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01001305";
        if($rec['measurementRemarks'] == 'source text: "loch"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000252";
        if($rec['measurementRemarks'] == 'source text: "croplands"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        if($rec['measurementRemarks'] == 'source text: "cropland"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        if($rec['measurementRemarks'] == 'source text: "crop land"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        if($rec['measurementRemarks'] == 'source text: "agricultural regions"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        if($rec['measurementRemarks'] == 'source text: "agricultural region"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        if($rec['measurementRemarks'] == 'source text: "crop-lands"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        if($rec['measurementRemarks'] == 'source text: "cultivated croplands"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        if($rec['measurementRemarks'] == 'source text: "cultivated s"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        if($rec['measurementRemarks'] == 'source text: "crop lands"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        if($rec['measurementRemarks'] == 'source text: "sea vents"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        if($rec['measurementRemarks'] == 'source text: "active chimneys"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        if($rec['measurementRemarks'] == 'source text: "sea vent"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        if($rec['measurementRemarks'] == 'source text: "active chimney"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        if($rec['measurementRemarks'] == 'source text: "embayments"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000032";
        if($rec['measurementRemarks'] == 'source text: "embayment"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000032";
        if($rec['measurementRemarks'] == 'source text: "brush"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        if($rec['measurementRemarks'] == 'source text: "bush"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        if($rec['measurementRemarks'] == 'source text: "brushes"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        if($rec['measurementRemarks'] == 'source text: "caatinga"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000883";
        if($rec['measurementRemarks'] == 'source text: "caatingas"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_00000883";
        if($rec['measurementRemarks'] == 'source text: "coniferous forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        if($rec['measurementRemarks'] == 'source text: "coniferous forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        if($rec['measurementRemarks'] == 'source text: "coniferous forests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        if($rec['measurementRemarks'] == 'source text: "coniferousforest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        if($rec['measurementRemarks'] == 'source text: "coniferousforests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        if($rec['measurementRemarks'] == 'source text: "deciduous forests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        if($rec['measurementRemarks'] == 'source text: "deciduous forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        if($rec['measurementRemarks'] == 'source text: "deciduous forests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        if($rec['measurementRemarks'] == 'source text: "deciduous-forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        if($rec['measurementRemarks'] == 'source text: "deciduousforest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        if($rec['measurementRemarks'] == 'source text: "deciduousforests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        if($rec['measurementRemarks'] == 'source text: "equatorial forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000220";
        if($rec['measurementRemarks'] == 'source text: "equatorial forests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000220";
        if($rec['measurementRemarks'] == 'source text: "equatorial rain forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        if($rec['measurementRemarks'] == 'source text: "equatorial rain forests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        if($rec['measurementRemarks'] == 'source text: "equatorial rainforest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        if($rec['measurementRemarks'] == 'source text: "equatorial rainforests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        if($rec['measurementRemarks'] == 'source text: "jungle"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "jungles"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "mallee scrub"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        if($rec['measurementRemarks'] == 'source text: "mangrove forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000181";
        if($rec['measurementRemarks'] == 'source text: "mangrove forests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000181";
        if($rec['measurementRemarks'] == 'source text: "mangrove- forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000181";
        if($rec['measurementRemarks'] == 'source text: "monsoon forest"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "monsoon forests"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "monsoon-forest"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "mulga scrub"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        if($rec['measurementRemarks'] == 'source text: "pine grove"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000240";
        if($rec['measurementRemarks'] == 'source text: "pine groves"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000240";
        if($rec['measurementRemarks'] == 'source text: "pinegrove"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000240";
        if($rec['measurementRemarks'] == 'source text: "rain forest"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "rain forest"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "rain forests"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "rain-forest"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "rain-forests"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "rainforest"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "rainforests"') $rec['measurementValue'] = "http://eol.org/schema/terms/wet_forest";
        if($rec['measurementRemarks'] == 'source text: "sage brush"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        if($rec['measurementRemarks'] == 'source text: "sage-brush"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        if($rec['measurementRemarks'] == 'source text: "sagebrush"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        if($rec['measurementRemarks'] == 'source text: "sagebrushes"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        if($rec['measurementRemarks'] == 'source text: "taiga"') $rec['measurementValue'] = "http://eol.org/schema/terms/boreal_forests_taiga";
        if($rec['measurementRemarks'] == 'source text: "taigas"') $rec['measurementValue'] = "http://eol.org/schema/terms/boreal_forests_taiga";
        if($rec['measurementRemarks'] == 'source text: "thorn forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        if($rec['measurementRemarks'] == 'source text: "thorn forests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        if($rec['measurementRemarks'] == 'source text: "thorn-forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        if($rec['measurementRemarks'] == 'source text: "thornforest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        if($rec['measurementRemarks'] == 'source text: "thornforests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        if($rec['measurementRemarks'] == 'source text: "tropical rain forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        if($rec['measurementRemarks'] == 'source text: "tropical rain forests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        if($rec['measurementRemarks'] == 'source text: "tropical rain-forest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        if($rec['measurementRemarks'] == 'source text: "tropical rainforest"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        if($rec['measurementRemarks'] == 'source text: "tropical rainforests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        if($rec['measurementRemarks'] == 'source text: "tropicalrainforests"') $rec['measurementValue'] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        
        // https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=62854&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62854
        // and records to discard based on measurementRemarks:
        $to_delete = array('source text: "range s"', 'source text: "ranges"', 'source text: "range s"', 'source text: "rang e"', 'source text: "bamboo"', 'source text: "barrens"', 'source text: "breaks"', 
                           'source text: "mulga"', 'source text: "chanaral"');
        if(in_array($rec['measurementRemarks'], $to_delete)) return false;
        
        
        //3rd adjustment
        $to_delete = array("http://purl.obolibrary.org/obo/ENVO_00000104", "http://purl.obolibrary.org/obo/ENVO_00002033", "http://purl.obolibrary.org/obo/ENVO_00000304", 
                           "http://purl.obolibrary.org/obo/ENVO_00000486", "http://purl.obolibrary.org/obo/ENVO_00002000", "http://purl.obolibrary.org/obo/ENVO_00000086", 
                           "http://purl.obolibrary.org/obo/ENVO_00000220");
        
        //https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=62851&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62851
        $to_delete = self::more_to_delete($to_delete);
        
                           
        if(in_array($rec['measurementValue'], $to_delete)) return false;
        return $rec;
    }
    private function more_to_delete($final)
    {
        $str = "http://purl.obolibrary.org/obo/ENVO_00000113, http://purl.obolibrary.org/obo/ENVO_00002232, http://purl.obolibrary.org/obo/ENVO_02000047, http://purl.obolibrary.org/obo/ENVO_00003031, 
        http://purl.obolibrary.org/obo/ENVO_00002276, http://purl.obolibrary.org/obo/ENVO_00000121, http://purl.obolibrary.org/obo/ENVO_00000099, http://purl.obolibrary.org/obo/ENVO_00000377, 
        http://purl.obolibrary.org/obo/ENVO_00000165, http://purl.obolibrary.org/obo/ENVO_00003903, http://purl.obolibrary.org/obo/ENVO_02000054, http://purl.obolibrary.org/obo/ENVO_00010624, 
        http://purl.obolibrary.org/obo/ENVO_01000243, http://purl.obolibrary.org/obo/ENVO_01000114, http://purl.obolibrary.org/obo/ENVO_00003885, http://purl.obolibrary.org/obo/ENVO_00003044, 
        http://purl.obolibrary.org/obo/ENVO_00000369, http://purl.obolibrary.org/obo/ENVO_00000158, http://purl.obolibrary.org/obo/ENVO_00000526, http://purl.obolibrary.org/obo/ENVO_02000058, 
        http://purl.obolibrary.org/obo/ENVO_00002169, http://purl.obolibrary.org/obo/ENVO_00002206, http://purl.obolibrary.org/obo/ENVO_00002026, http://purl.obolibrary.org/obo/ENVO_00002170, 
        http://purl.obolibrary.org/obo/ENVO_00000272, http://purl.obolibrary.org/obo/ENVO_00002116, http://purl.obolibrary.org/obo/ENVO_00002186, http://purl.obolibrary.org/obo/ENVO_00000293, 
        http://purl.obolibrary.org/obo/ENVO_00000223, http://purl.obolibrary.org/obo/ENVO_00000514, http://purl.obolibrary.org/obo/ENVO_2000001, http://purl.obolibrary.org/obo/ENVO_00000320, 
        http://purl.obolibrary.org/obo/ENVO_02000006, http://purl.obolibrary.org/obo/ENVO_00000474, http://purl.obolibrary.org/obo/ENVO_00000523, http://purl.obolibrary.org/obo/ENVO_00000074, 
        http://purl.obolibrary.org/obo/ENVO_00000309, http://purl.obolibrary.org/obo/ENVO_00000037, http://purl.obolibrary.org/obo/ENVO_00002158, http://purl.obolibrary.org/obo/ENVO_00000291, 
        http://purl.obolibrary.org/obo/ENVO_00003064, http://purl.obolibrary.org/obo/ENVO_00000449, http://purl.obolibrary.org/obo/ENVO_01000136, http://purl.obolibrary.org/obo/ENVO_00010506, 
        http://purl.obolibrary.org/obo/ENVO_00002020, http://purl.obolibrary.org/obo/ENVO_00002027, http://purl.obolibrary.org/obo/ENVO_00000114, http://purl.obolibrary.org/obo/ENVO_00000294, 
        http://purl.obolibrary.org/obo/ENVO_00000295, http://purl.obolibrary.org/obo/ENVO_00000471, http://purl.obolibrary.org/obo/ENVO_00000443, http://purl.obolibrary.org/obo/ENVO_00002002, 
        http://purl.obolibrary.org/obo/ENVO_00000411, http://purl.obolibrary.org/obo/ENVO_00002164, http://purl.obolibrary.org/obo/ENVO_00002983, http://purl.obolibrary.org/obo/ENVO_00000011, 
        http://purl.obolibrary.org/obo/ENVO_00000050, http://purl.obolibrary.org/obo/ENVO_00000131, http://purl.obolibrary.org/obo/ENVO_00002168, http://purl.obolibrary.org/obo/ENVO_00000340, 
        http://purl.obolibrary.org/obo/ENVO_00005780, http://purl.obolibrary.org/obo/ENVO_00002041, http://purl.obolibrary.org/obo/ENVO_00002171, http://purl.obolibrary.org/obo/ENVO_00002028, 
        http://purl.obolibrary.org/obo/ENVO_00002023, http://purl.obolibrary.org/obo/ENVO_00002025, http://purl.obolibrary.org/obo/ENVO_00003859, http://purl.obolibrary.org/obo/ENVO_00000468, 
        http://purl.obolibrary.org/obo/ENVO_02000000, http://purl.obolibrary.org/obo/ENVO_00000098, http://purl.obolibrary.org/obo/ENVO_00000174, http://purl.obolibrary.org/obo/ENVO_00000311, 
        http://purl.obolibrary.org/obo/ENVO_00000424, http://purl.obolibrary.org/obo/ENVO_00000391, http://purl.obolibrary.org/obo/ENVO_00000533, http://purl.obolibrary.org/obo/ENVO_00000178, 
        http://purl.obolibrary.org/obo/ENVO_00000066, http://purl.obolibrary.org/obo/ENVO_01000057, http://purl.obolibrary.org/obo/ENVO_01000066, http://purl.obolibrary.org/obo/ENVO_00000509, 
        http://purl.obolibrary.org/obo/ENVO_00000427, http://purl.obolibrary.org/obo/ENVO_00010621, http://purl.obolibrary.org/obo/ENVO_01000207, http://purl.obolibrary.org/obo/ENVO_00002035, 
        http://purl.obolibrary.org/obo/ENVO_00010442, http://purl.obolibrary.org/obo/ENVO_00000076, http://purl.obolibrary.org/obo/ENVO_00001996, http://purl.obolibrary.org/obo/ENVO_00000003, 
        http://purl.obolibrary.org/obo/ENVO_00000180, http://purl.obolibrary.org/obo/ENVO_00000477, http://purl.obolibrary.org/obo/ENVO_00000414, http://purl.obolibrary.org/obo/ENVO_00000359, 
        http://purl.obolibrary.org/obo/ENVO_00000048, http://purl.obolibrary.org/obo/ENVO_00005804, http://purl.obolibrary.org/obo/ENVO_00005805, http://purl.obolibrary.org/obo/ENVO_2000006, 
        http://purl.obolibrary.org/obo/ENVO_02000004, http://purl.obolibrary.org/obo/ENVO_00002271, http://purl.obolibrary.org/obo/ENVO_00000480, http://purl.obolibrary.org/obo/ENVO_00002139, 
        http://purl.obolibrary.org/obo/ENVO_00000305, http://purl.obolibrary.org/obo/ENVO_00000134, http://purl.obolibrary.org/obo/ENVO_00002984, http://purl.obolibrary.org/obo/ENVO_00000191, 
        http://purl.obolibrary.org/obo/ENVO_00000339, http://purl.obolibrary.org/obo/ENVO_00003860, http://purl.obolibrary.org/obo/ENVO_00000481, http://purl.obolibrary.org/obo/ENVO_00002214, 
        http://purl.obolibrary.org/obo/ENVO_00000358, http://purl.obolibrary.org/obo/ENVO_00000302, http://purl.obolibrary.org/obo/ENVO_00000022, http://purl.obolibrary.org/obo/ENVO_00001995, 
        http://purl.obolibrary.org/obo/ENVO_01000017, http://purl.obolibrary.org/obo/ENVO_00002055, http://purl.obolibrary.org/obo/ENVO_00004638, http://purl.obolibrary.org/obo/ENVO_00003930, 
        http://purl.obolibrary.org/obo/ENVO_00000092, http://purl.obolibrary.org/obo/ENVO_00002016, http://purl.obolibrary.org/obo/ENVO_00002018, http://purl.obolibrary.org/obo/ENVO_00003043, 
        http://purl.obolibrary.org/obo/ENVO_00002056, http://purl.obolibrary.org/obo/ENVO_00000403, http://purl.obolibrary.org/obo/ENVO_00003030, http://purl.obolibrary.org/obo/ENVO_00000539, 
        http://purl.obolibrary.org/obo/ENVO_01000016, http://purl.obolibrary.org/obo/ENVO_00000361, http://purl.obolibrary.org/obo/ENVO_00002044, http://purl.obolibrary.org/obo/ENVO_00000393, 
        http://purl.obolibrary.org/obo/ENVO_00000027, http://purl.obolibrary.org/obo/ENVO_00000419, http://purl.obolibrary.org/obo/ENVO_00000331, http://purl.obolibrary.org/obo/ENVO_00000330, 
        http://purl.obolibrary.org/obo/ENVO_00000394, http://purl.obolibrary.org/obo/ENVO_00010504, http://purl.obolibrary.org/obo/ENVO_00000543, http://purl.obolibrary.org/obo/ENVO_00003323, 
        http://purl.obolibrary.org/obo/ENVO_00003096, http://purl.obolibrary.org/obo/ENVO_02000001, http://purl.obolibrary.org/obo/ENVO_00000122, http://purl.obolibrary.org/obo/ENVO_00000499, 
        http://purl.obolibrary.org/obo/ENVO_00000094, http://purl.obolibrary.org/obo/ENVO_00002264, http://purl.obolibrary.org/obo/ENVO_00002272, http://purl.obolibrary.org/obo/ENVO_00002001, 
        http://purl.obolibrary.org/obo/ENVO_00002043, http://purl.obolibrary.org/obo/ENVO_00000029, http://purl.obolibrary.org/obo/ENVO_00000547, http://purl.obolibrary.org/obo/ENVO_00000292, 
        http://purl.obolibrary.org/obo/ENVO_00000421, http://purl.obolibrary.org/obo/ENVO_00000043, http://purl.obolibrary.org/obo/ENVO_00000409, http://purl.obolibrary.org/obo/ENVO_00002040, 
        http://purl.obolibrary.org/obo/ENVO_00001998, http://purl.obolibrary.org/obo/ENVO_00000376, http://purl.obolibrary.org/obo/ENVO_00002152, http://purl.obolibrary.org/obo/ENVO_00002123, 
        http://purl.obolibrary.org/obo/ENVO_00000530, http://purl.obolibrary.org/obo/ENVO_00000564, http://purl.obolibrary.org/obo/ENVO_00002277, http://purl.obolibrary.org/obo/ENVO_00000438, 
        http://purl.obolibrary.org/obo/ENVO_2000004";
        $arr = explode(",", $str); $arr = array_map('trim', $arr);
        $final = array_merge($final, $arr);
        return $final;
    }
    private function format_uri($raw_envo)
    {
        return "http://purl.obolibrary.org/obo/" . str_replace(":", "_", $raw_envo);
    }
    private function excluded_measurement_values() //from here: https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=62373&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62373
    {
        $a = "http://purl.obolibrary.org/obo/ENVO_2000000 http://purl.obolibrary.org/obo/ENVO_00003893 http://purl.obolibrary.org/obo/ENVO_00003895 http://purl.obolibrary.org/obo/ENVO_00010625 http://purl.obolibrary.org/obo/ENVO_00000375 http://purl.obolibrary.org/obo/ENVO_00000374 http://purl.obolibrary.org/obo/ENVO_00003963 http://purl.obolibrary.org/obo/ENVO_00010622 http://purl.obolibrary.org/obo/ENVO_00000349 http://purl.obolibrary.org/obo/ENVO_00002197 http://purl.obolibrary.org/obo/ENVO_00000515 http://purl.obolibrary.org/obo/ENVO_00000064 http://purl.obolibrary.org/obo/ENVO_00000062 http://purl.obolibrary.org/obo/ENVO_02000055 http://purl.obolibrary.org/obo/ENVO_00002061 http://purl.obolibrary.org/obo/ENVO_00002183 http://purl.obolibrary.org/obo/ENVO_01000003 http://purl.obolibrary.org/obo/ENVO_00002185 http://purl.obolibrary.org/obo/ENVO_00002985 http://purl.obolibrary.org/obo/ENVO_00000363 http://purl.obolibrary.org/obo/ENVO_00000366 http://purl.obolibrary.org/obo/ENVO_00000367 http://purl.obolibrary.org/obo/ENVO_00000364 http://purl.obolibrary.org/obo/ENVO_00000479 http://purl.obolibrary.org/obo/ENVO_00000561 http://purl.obolibrary.org/obo/ENVO_00002267 http://purl.obolibrary.org/obo/ENVO_00000000 http://purl.obolibrary.org/obo/ENVO_00000373 http://purl.obolibrary.org/obo/ENVO_00002215 http://purl.obolibrary.org/obo/ENVO_00002198 http://purl.obolibrary.org/obo/ENVO_00000176 http://purl.obolibrary.org/obo/ENVO_00000075 http://purl.obolibrary.org/obo/ENVO_00000168 http://purl.obolibrary.org/obo/ENVO_00003864 http://purl.obolibrary.org/obo/ENVO_00002196 http://purl.obolibrary.org/obo/ENVO_00000002 http://purl.obolibrary.org/obo/ENVO_00005803 http://purl.obolibrary.org/obo/ENVO_00002874 http://purl.obolibrary.org/obo/ENVO_00002046 http://purl.obolibrary.org/obo/ENVO_00000077";
        return explode(" ", $a);
    }
    private function add_string_types($rec)
    {
        /* old ways
        // since all measurements have measurementOfTaxon = 'true' then the occurrence_id will not be used twice
        if($occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"]))
        {
            unset($rec['catnum']);
            unset($rec['taxon_id']);
            $m = new \eol_schema\MeasurementOrFact();
            $m->occurrenceID = $occurrence_id;
            foreach($rec as $key => $value) $m->$key = $value;
            $this->archive_builder->write_object_to_file($m);
        }
        */
        
        // /* new https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=62965&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62965
        $taxon_id = str_replace("EOL:", "", $rec['taxon_id']);
        if(isset($this->excluded_eol_ids) && isset($this->excluded_terms[$rec['measurementValue']])) return false;
        // */
        
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"]);
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
        return true;
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = str_replace('EOL:', '', $taxon_id) . $catnum;

        /* old ways
        // since all measurements have measurementOfTaxon = 'true' then the occurrence_id will not be used twice
        if(isset($this->occurrence_ids[$occurrence_id])) return false;
        */
        
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
        
        /* old ways
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
        */
    }

    private function save_to_dump($rec, $filename)
    {
        if(isset($rec["measurement"]) && is_array($rec)) {
            $fields = array("family", "count", "taxon_id", "object_id", "source", "label", "measurement");
            $data = "";
            foreach($fields as $field) $data .= $rec[$field] . "\t";
            if(!($WRITE = Functions::file_open($filename, "a"))) return;
            fwrite($WRITE, $data . "\n");
            fclose($WRITE);
        }
        else {
            if(!($WRITE = Functions::file_open($filename, "a"))) return;
            if($rec && is_array($rec)) fwrite($WRITE, json_encode($rec) . "\n");
            else                       fwrite($WRITE, $rec . "\n");
            fclose($WRITE);
        }
    }
    
    private function get_dump() // utility
    {
        $names = array();
        $dump_file = DOC_ROOT . "/temp/need_to_check_tc_id.txt";
        foreach(new FileIterator($dump_file) as $line_number => $line) {
            if($line) $names[$line] = "";
        }
        return array_keys($names);
    }

    function list_folders_with_corrupt_files() // utility
    {
        $folders = array();
        foreach(new FileIterator(DOC_ROOT . "/temp/cant_delete.txt") as $line_number => $line) {
            $parts = pathinfo($line);
            $folders[@$parts["dirname"]] = '';
        }
        $folders = array_keys($folders);
        $folders = array_filter(array_map('trim', $folders));
        print_r($folders);
    }

}
?>