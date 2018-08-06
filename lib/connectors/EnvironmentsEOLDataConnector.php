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

        $this->species_list_export = "http://localhost/cp/Environments/eol_env_annotations_noParentTerms.tar.gz";   //local
        $this->species_list_export = "http://download.jensenlab.org/EOL/eol_env_annotations_noParentTerms.tar.gz";  //still works Aug 6, 2018
        
        /* add: 'resource_id' => "eol_api" ;if you want to add the cache inside a folder [eol_api] inside [eol_cache] */
        $this->download_options = array(
            'resource_id'        => 'eol_api',                              //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //since taxon_concept_id and hierarchy_entry_id won't change the resulting API response won't also change. Another option is 1 year to expire
            'download_wait_time' => 3000000, 'timeout' => 3600, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        
        if(Functions::is_production()) $this->download_options['cache_path'] = '/extra/eol_php_cache/';
        else                           $this->download_options['cache_path'] = '/Volumes/Thunderbolt4/eol_cache/'; //used in Functions.php for all general cache
        
        // stats
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->need_to_check_tc_id_dump_file = $this->TEMP_DIR . "need_to_check_tc_id.txt";
        $this->debug = array();
    }
    /*
    Array
    (
        [0] => EOL:194
        [1] => 25066375;http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat
        [2] => scrub forest
        [3] => ENVO:00000300
    )
    Array
    (
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

    private function csv_to_array($tsv_file)
    {
        $excluded_uris = self::excluded_measurement_values(); //from here: https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=62373&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62373
        $fields = array("taxon_id", "do_id_subchapter", "text", "envo", "5th_col");
        $i = 0; $m = 1700000/5; // = 340000
        foreach(new FileIterator($tsv_file) as $line_number => $line) {
            $temp = explode("\t", $line);
            $i++;
            if(($i % 100) == 0) echo "\n".number_format($i)." - ";
            
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
                
                self::create_instances_from_taxon_object($taxon);
                self::create_data($taxon, $rec);
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
            if($tc['scientificName'] == $sciname) // 1st option
            {
                $tc_rec = $tc;
                break;
            }
        }
        if(!$tc_rec) {
            foreach($tcs as $tc) {
                if($tc['scientificName'] == Functions::canonical_form($sciname)) // 2nd option
                {
                    $tc_rec = $tc;
                    break;
                }
            }
        }
        if(!$tc_rec) {
            foreach($tcs as $tc) {
                if(Functions::canonical_form($tc['scientificName']) == Functions::canonical_form($sciname)) // 3rd option
                {
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
        Array
        (
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
        if($val = self::get_reference_ids($line)) $rec['referenceID'] = implode("; ", $val);
        self::add_string_types($rec);
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
    private function get_reference_ids($line)
    {
        if($ref = $line['5th_col']) {
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref;
            $r->identifier = md5($ref);
            // $r->uri = '';
            if(!isset($this->resource_reference_ids[$r->identifier])) {
               $this->resource_reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
            return array($r->identifier);
        }
        return false;
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