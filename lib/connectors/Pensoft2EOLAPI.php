<?php
namespace php_active_record;
/* connector: [environments_2_eol.php]

This is for Pensoft annotator.
While an old, close to obsolete version (Environments2EOLAPI.php) is for Vangelis tagger.
----------------------------------------------
Below is just for reference how to access OpenData resource: e.g. Amphibia Web text
https://opendata.eol.org/api/3/action/resource_search?query=name:AmphibiaWeb%20text
https://opendata.eol.org/api/3/action/resource_show?id=639efbfb-3b79-49e7-894f-50df4fa25da8
----------------------------------------------
NOTES:
- Wikipedia EN and AmphibiaWeb textmining path is the same.
- AntWeb has a path of its own, since its the unique nature of just applying Pensoft Annotator to those un-mapped habitat strings.
----------------------------------------------
Frist clients:
- AmphibiaWeb textmined
- AntWeb textmined: Biology
- WoRMS textmined: Habitat and Distribution

http://api.pensoft.net/annotator?text=The Nearctic author was Urban C.&ontologies=envo,eol-geonames
-> with annotations
http://api.pensoft.net/annotator?text=I like playing in the shrub&ontologies=growth
-> no anotations from Pensoft

http://api.pensoft.net/annotator?text=OZARK-OUACHITA PLECOPTERA SPECIES LIST. Ozark Mountain forests.&ontologies=envo,eol-geonames
*/
class Pensoft2EOLAPI extends Functions_Pensoft
{
    function __construct($param)
    {
        $this->param = $param; // print_r($param); exit;
        /*Array(
            [task] => generate_eol_tags_pensoft
            [resource] => Pensoft_journals
            [resource_id] => 834_ENV
            [subjects] => GeneralDescription|Distribution
        )*/
        // /* add ontologies Yes/No in the id caching of Pensoft calls.
        if(in_array($this->param['resource_id'], array('617_ENV', '21_ENV', '26_ENV'))) $this->includeOntologiesYN = false; //Wikipedia EN | AmphibiaWeb text | WoRMS
        else $this->includeOntologiesYN = true; //the rest
        // */
        
        if(in_array($param['resource_id'], array('617_ENV', 'TreatmentBank_ENV'))) $this->modulo = 10000; //50000; //Wikipedia EN
        else                                                                       $this->modulo = 1000;
        /*-----------------------Resources-------------------*/
        // $this->DwCA_URLs['AmphibiaWeb text'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/21.tar.gz';
        /*-----------------------Subjects-------------------*/
        $this->subjects['Distribution'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution';
        $this->subjects['Description'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description';
        $this->subjects['TaxonBiology'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
        $this->subjects['Habitat'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat';
        $this->subjects['Uses'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses'; //for list-type in SI PDFs
        $this->subjects['GeneralDescription'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription'; //first client ZooKeys (20.tar.gz)
        
        /* Wikipedia EN
        http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description:  389994
        http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology: 382437
        */
        /*-----------------------Paths----------------------*/
        if(Functions::is_production()) $this->root_path = '/var/www/html/Pensoft_annotator/'; //'/html/Pensoft_annotator/';
        else                           $this->root_path = '/opt/homebrew/var/www/Pensoft_annotator/';
        
        if($this->param['resource_id'] == '617_ENV') {} //Wikipedia EN
        else { //rest of the resources
            $tmp = str_replace('_ENV', '', $param['resource_id']);
            $this->root_path .= $tmp.'/';
            if(!is_dir($this->root_path)) mkdir($this->root_path);
            // exit($this->root_path);
        }
        
        /*
        $this->eol_tagger_path      = $this->root_path.'eol_tagger/';
        $this->text_data_path       = $this->root_path.'test_text_data/';
        $this->eol_scripts_path     = $this->root_path.'eol_scripts/';
        */
        $this->eol_tags_path        = $this->root_path.'eol_tags/';
        $this->eol_tags_destination = $this->eol_tags_path.'eol_tags.tsv';
        $this->json_temp_path['metadata'] = $this->root_path.'temp_json/';
        $this->json_temp_path['partial'] = $this->root_path.'json_partial/'; //for partial, every 2000 chars long
        
        if(!is_dir($this->json_temp_path['metadata'])) mkdir($this->json_temp_path['metadata']);
        if(!is_dir($this->json_temp_path['partial'])) mkdir($this->json_temp_path['partial']);
        if(!is_dir($this->eol_tags_path)) mkdir($this->eol_tags_path);
        
        /*-----------------------Others---------------------*/
        $this->num_of_saved_recs_bef_run_tagger = 1000; //1000 orig;
        if($val = @$param['subjects']) {
            $this->allowed_subjects = self::get_allowed_subjects($val);
            echo "\n allowed_subjects: "; print_r($this->allowed_subjects);
        }
        
        $this->download_options = array('expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);
        $this->call['opendata resource via name'] = "https://opendata.eol.org/api/3/action/resource_search?query=name:RESOURCE_NAME";
        $this->entities_file = 'https://github.com/eliagbayani/vangelis_tagger/raw/master/eol_tagger/for_entities.txt';
        
        $this->descendants_habitat_group['saline water'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/AmphibiaWeb/descendants_of_salt_water.csv';
        $this->descendants_habitat_group['aquatic']    = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/AmphibiaWeb/descendants_of_aquatic.csv';
        //remove across all textmined resources: cloud, cut
        $this->remove_across_all_resources = array('http://purl.obolibrary.org/obo/ENVO_01000760', 'http://purl.obolibrary.org/obo/ENVO_00000474');
        $this->remove_across_all_resources[] = 'http://purl.obolibrary.org/obo/ENVO_00000016'; //per Jen: https://eol-jira.bibalex.org/browse/DATA-1858?focusedCommentId=65552&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65552
        $this->another_set_exclude_URIs = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Pensoft_Annotator/terms_implying_missing_filter.txt';
        $this->another_set_exclude_URIs_02 = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Pensoft_Annotator/terms_to_remove.txt';
        $this->another_set_exclude_URIs_03 = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Pensoft_Annotator/geo_synonyms.txt';
        $this->labels_to_remove_file = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Pensoft_Annotator/blacklist_labels_all_resources.txt';


        $this->pensoft_run_cnt = 0;
        if($val = @$param['ontologies']) $this->ontologies = $val;      // 1st client is the utility run_partial.php
        else                             $this->ontologies = "envo";    // orig
        /* from DATA-1853 - exclude ranks */
        $this->excluded_ranks = array('class', 'infraclass', 'infrakingdom', 'infraorder', 'infraphylum', 'kingdom', 'order', 'phylum', 'subclass', 'subkingdom', 'suborder', 'subphylum', 'subtribe', 'superclass', 'superfamily', 'superkingdom', 'superorder', 'superphylum', 'division', 'domain', 'grandorder', 'parvorder', 'realm', 'subdivision', 'tribe');
        $this->pensoft_service = "https://api.pensoft.net/annotator?text=MY_DESC&ontologies=MY_ONTOLOGIES";
        /* DATA-1893: new patterns for all textmined resources: life history ontology */
        $this->new_patterns_4textmined_resources = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Pensoft_Annotator/life_history.txt";
        
        $this->to_delete_file = "";
        $this->debug = array();

        /* TreatmentBank only: exclude first words: not used anymore
        $words = array('diagrammatic', 'diagram', 'diagrams', 'fig', 'figs', 'figure', 'figures', 'ref', 'refs', 'ref.:', 'reference', 'references', 
                       'table', 'tables', 'image.', 'images.', 'copyright', 'abbreviation', 'abbreviations', 'image', 'images', 'acknowledgement', 'acknowledgements');
        foreach($words as $word) {
            $arr[$word] = '';
            $arr[$word."."] = '';
        }
        ksort($arr);
        $this->exclude_first_words = $arr;
        $this->debug['detected_first_words'] = array();
        */

        // /* soil compositions
        $labels = array('sandy soil', 'clay soil', 'dry soil', 'garden soil', 'forest soil', 'muddy soil', 'red soil', 'field soil', 'volcanic soil', 'surface soil', 
        'dune soil', 'arable soil', 'agricultural soil', 'meadow soil', 'orchard soil', 'alluvial soil', 'grassland soil', 'pasture soil', 'peat soil', 'steppe soil', 
        'farm soil', 'alpine soil', 'roadside soil', 'tropical soil', 'beech forest soil', 'fluvisol', 'luvisol', 'cambisol', 'regosol', 'leptosol', 'gleysol', 'vertisol');
        foreach($labels as $label) $this->soil_compositions[$label] = '';
        // */
    }
    public function initialize_remaps_deletions_adjustments()
    {
        self::init_DATA_1841_terms_remapped();  //generates $this->remapped_terms               -> used in apply_adjustments()
        self::initialize_mRemark_assignments(); //generates $this->mRemarks                     -> used in apply_adjustments()
        self::initialize_delete_mRemarks();     //generates $this->delete_MoF_with_these_labels -> used in apply_adjustments()
        self::initialize_delete_uris();         //generates $this->delete_MoF_with_these_uris   -> used in apply_adjustments()
        /* echo "\nto test if these 4 variables are populated: ";
        echo("\n remapped_terms: "              .count($this->remapped_terms)."");
        echo("\n mRemarks: "                    .count($this->mRemarks)."");
        echo("\n delete_MoF_with_these_labels: ".count($this->delete_MoF_with_these_labels)."");
        echo("\n delete_MoF_with_these_uris: "  .count($this->delete_MoF_with_these_uris).""); echo("\n---------------\n"); */
        $this->initialize_new_patterns();         //generates $this->new_patterns   -> used in xxx() --- DATA-1893
        // echo("\n new_patterns: "  .count($this->new_patterns)."\n"); print_r($this->new_patterns); exit;
        $this->allowed_terms_URIs = self::get_allowed_value_type_URIs_from_EOL_terms_file(); //print_r($this->allowed_terms_URIs);
        echo ("\nallowed_terms_URIs from EOL terms file: [".count($this->allowed_terms_URIs)."]\n");
    }
    function generate_eol_tags_pensoft($resource, $timestart = '', $download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*30))
    {   //print_r($this->param); exit;
        if(!self::test_is_passed()) exit("\nTest failed. Needed service is not available\n");
        else echo "\nTest passed OK\n";
        
        // /* ------------------------- customize -------------------------
        if($this->param['resource_id'] == '21_ENV') { //AmphibiaWeb text: entire resource was processed.
            $this->descendants_of_saline_water = self::get_descendants_of_habitat_group('saline water'); //saline water. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65409&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65409
        }
        // ------------------------- end customize ------------------------- */
        
        self::lookup_opendata_resource();
        // /* un-comment in real operation
        self::initialize_files();
        // */
        $info = self::parse_dwca($resource, $download_options); // print_r($info); exit;
        $tables = $info['harvester']->tables;
        print_r(array_keys($tables)); //exit;

        // /* ------------------------- customize -------------------------
        $this->exclude_taxonIDs = array(); //initialize
        self::process_table_taxa($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]); //this will gen. $this->exclude_taxonIDs
        // ------------------------- end customize ------------------------- */
        
        // /* this is used to apply all the remaps, deletions, adjustments:
        self::initialize_remaps_deletions_adjustments();
        // */

        // /* un-comment in real operation
        // /* un-comment real operation
        self::process_table($tables['http://eol.org/schema/media/document'][0]); //generates individual text files & runs environment tagger
        // */
        // exit("\nDebug early exit...\n"); //if u want to investigate the individual text files.
        // print_r($this->debug);
        
        // /* NEW: for WoRMS only: annotate WoRMS orig strings for MoF with mType = Present. Basically convert them to URIs
        if($this->param['resource_id'] == '26_ENV') {
            self::process_table_v2_WoRMS($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], "info_list");
            self::process_table_v2_WoRMS($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], "annotate"); // exit("\nelix 3\n");
        }
        // */
        
        /* report for Jen - 'difference' report
        self::generate_difference_report(); exit("\n-end report-\n");
        */
        self::noParentTerms_less_entities_file(); //exit("\nstop muna 1\n");
        
        // */
        /* ----- stat 2nd part ----- */
        $obj_identifiers = self::get_unique_obj_identifiers(); // get unique IDs from noParentTerms
        $agent_ids = self::save_media_metadata_for_these_objects($obj_identifiers, $tables['http://eol.org/schema/media/document'][0]);
        if($val = @$tables['http://eol.org/schema/agent/agent']) self::save_agent_metadata_for_these_agents($agent_ids, $val[0]);
        // /* un-comment in real operation
        recursive_rmdir($info['temp_dir']); //remove temp folder used for DwCA parsing
        // */
        /* ----- stat 3rd part ----- */ //adjust DwCA in question. Either add MoF or update MoF.
        $dwca_file = $this->DwCA_URLs[$resource];
        require_library('connectors/DwCA_Utility');
        $func = new DwCA_Utility($this->param['resource_id'], $dwca_file, $this->param);
        $preferred_rowtypes = array();
        /* These 2 will be processed in Environments2EOLfinal.php which will be called from DwCA_Utility.php
        http://rs.tdwg.org/dwc/terms/occurrence
        http://rs.tdwg.org/dwc/terms/measurementorfact
        */
        $preferred_rowtypes = false; //means process all rowtypes, except what's in $excluded_rowtypes
        // $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/measurementorfact'); //not used
        
        $excluded_rowtypes = array();
        
        // /* -------------------- start customize --------------------
        if($this->param['resource_id'] == '617_ENV') $excluded_rowtypes = array('http://eol.org/schema/media/document'); //Wikipedia EN -> creates a new DwCA
        elseif($this->param['resource_id'] == '21_ENV') $excluded_rowtypes = array(); //AmphibiaWeb text -> doesn't create a new DwCA
        if(in_array($this->param['resource_id'], array("10088_5097_ENV", "10088_6943_ENV", "118935_ENV", "120081_ENV", "120082_ENV", "118986_ENV", "118920_ENV", "120083_ENV", 
            "118237_ENV", "MoftheAES_ENV", "30355_ENV", "27822_ENV", "30354_ENV", "119035_ENV", "118946_ENV", "118936_ENV", "118950_ENV",
            "120602_ENV", "119187_ENV", "118978_ENV", "118941_ENV", "119520_ENV", "119188_ENV",
            "15423_ENV", "91155_ENV"))) {
            $excluded_rowtypes = array('http://eol.org/schema/media/document');
            $excluded_rowtypes[] = 'http://rs.tdwg.org/dwc/terms/measurementorfact'; //to exclude the MoF size patterns from xxx.tar.gz
        }
        elseif($this->param['resource'] == 'all_BHL') {
            $excluded_rowtypes = array('http://eol.org/schema/media/document');
            $excluded_rowtypes[] = 'http://rs.tdwg.org/dwc/terms/measurementorfact'; //to exclude the MoF size patterns from xxx.tar.gz
        }
        /* these 3 also to exclude the MoF size patterns from xxx.tar.gz --- if ever 
           added 'http://rs.tdwg.org/dwc/terms/measurementorfact' */
        if(stripos($this->param['resource_id'], "SCtZ-") !== false) $excluded_rowtypes = array('http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact'); //string is found
        elseif(stripos($this->param['resource_id'], "scb-") !== false)  $excluded_rowtypes = array('http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact'); //string is found
        elseif(stripos($this->param['resource_id'], "scz-") !== false)  $excluded_rowtypes = array('http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact'); //string is found
        
        
        // WoRMS -> doesn't create a new DwCA. But MoF is too big, memory issue.
        // Also MoF and Occurrence will be moved to MoF_specific and Occurrence_specific, together with the new traits from textmined Habitat articles.
        if($this->param['resource_id'] == '26_ENV') $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
        // ---------------------- end customize ----------------------*/
        $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
        Functions::finalize_dwca_resource($this->param['resource_id'], false, false, $timestart); //3rd param false means don't delete folder
        // exit("\nstop muna - used in debugging\n");

        // /* New: add testing for undefined childen in MoF
        self::run_utility($this->param['resource_id']);
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$this->param['resource_id']."/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI
        // */

        /* 4th part */
        if(is_dir($this->json_temp_path['metadata'])) {
            recursive_rmdir($this->json_temp_path['metadata']);
            mkdir($this->json_temp_path['metadata']);
        }
        echo "\nHow many times Pensoft Annotator is run: [$this->pensoft_run_cnt]\n";
        
        $index = "Should not go here, since record should be created now";
        if($val = @$this->debug[$index]) echo "\n$index : [$val]\n";
        $index = "NOT FOUND IN EOL TERMS FILE";
        if(isset($this->debug[$index])) {
            echo "\n$index: "; print_r($this->debug[$index]);
        }
        if(isset($this->debug['counts'])) print_r($this->debug['counts']);

        if($val = @$this->debug['detected_first_words']) {ksort($val); echo "\ndetected_first_words: "; print_r($val);} //for TreatmentBank only
    }
    private function run_utility($resource_id)
    {
        // /* utility ==========================
        require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();

        $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'parentMeasurementID', 'measurement_or_fact_specific.tab');
        echo "\nTotal undefined parents MoF [$resource_id]: " . count($undefined_parents)."\n";
        // ===================================== */
    }
    
    private function generate_difference_report() //utility report only, not part of main operation of textmining
    {
        // print_r($this->all_envo_terms); exit;
        $old = $this->all_envo_terms;
        print_r($old);
        $this->all_envo_terms = array_keys($this->all_envo_terms);
        // print_r($this->all_envo_terms); //exit;
        foreach($this->all_envo_terms as $t) $pensoft_envo_terms[] = pathinfo($t, PATHINFO_BASENAME);
        $envo_from_entities = self::get_envo_from_entities_file();
        // print_r($envo_from_entities); exit;
        $difference = array_diff($pensoft_envo_terms, $envo_from_entities);
        echo "\n pensoft_envo_terms: ".count($pensoft_envo_terms);
        echo "\n envo_from_entities: ".count($envo_from_entities);
        echo "\n difference: ".count($difference)."\n";
        $difference = array_values($difference); //reindex key
        // print_r($difference);
        /* $old e.g. Array(
            [http://purl.obolibrary.org/obo/ENVO_01000739] => habitat
            [http://purl.obolibrary.org/obo/ENVO_01001023] => radiation
            [http://purl.obolibrary.org/obo/ENVO_00002164] => fossil
        */
        $i = 0;
        foreach($difference as $term) { $i++;
            $uri = 'http://purl.obolibrary.org/obo/'.$term;
            echo "\n[$i] $uri -> ".$old[$uri];
        }
        exit("\n-end difference report-\n");
    }
    private function get_envo_from_entities_file()
    {
        $local = Functions::save_remote_file_to_local($this->entities_file, array('cache' => 1, 'expire_seconds' => 60)); //60*60*24
        foreach(new FileIterator($local) as $line => $row) {
            if(!$row) continue;
            $tmp = explode("\t", $row);
            // print_r($tmp); //exit;
            /*Array(
                [0] => 1009000003
                [1] => -27
                [2] => ENVO:01000057
            )*/
            $final[str_replace('ENVO:', 'ENVO_', $tmp[2])] = '';
        }
        unlink($local);
        // print_r($final); exit;
        $final = array_keys($final);
        echo "\nentities count 1: ".count($final);
        $filter_out = self::filter_out_from_entities();
        $final = array_diff($final, $filter_out);
        echo "\nentities count 2: ".count($final);
        return $final;
    }
    private function initialize_files()
    {
        // /* copied template, not needed in Pensoft yet
        /* OBSOLETE
        $files = array($this->eol_tags_destination, $this->eol_tags_path.'eol_tags_noParentTerms.tsv');     //Vangelis tagger
        */
        $files = array($this->eol_tags_path.'eol_tags_noParentTerms.tsv');                                  //Pensoft annotator
        foreach($files as $file) {
            if($f = Functions::file_open($file, "w")) {
                fclose($f);
                echo "\nFile truncated: [$file]\n";
            }
        }
        // */
        if(is_dir($this->json_temp_path['metadata'])) {
            recursive_rmdir($this->json_temp_path['metadata']);
            mkdir($this->json_temp_path['metadata']);
        }
        else mkdir($this->json_temp_path['metadata']);
    }
    private function parse_dwca($resource, $download_options)
    {   
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->DwCA_URLs[$resource], "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        print_r($paths); //exit("\n-exit muna-\n");
        // */
        /* development only
        $paths = Array("archive_path" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_00817/",
                       "temp_dir" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_00817/");
        */
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    private function process_table($meta) //parses document extension //generates individual text files & runs environment tagger
    {   //print_r($meta);
        echo "\nprocess ".$meta->file_uri."...\n";
        echo "\nRun Pensoft annotator...\n";
        $i = 0; $saved = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % $this->modulo) == 0) echo "\nxyz".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\n[1]\n");

            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            
            // /* New: implement rank filter for occurrences and traits as described in DATA-1853.
            if(isset($this->exclude_taxonIDs[$taxonID])) continue; //first client is Wikipedia EN (617_ENV)
            // */
            
            // if($taxonID != 'Q1000262') continue; //debug only
            
            /* debug only --- range ranges caching cache
            // if($this->param['resource_id'] == "617_ENV") { //total 841539 objects in media tab '617_ENV'
            //     $m = 841539/3; # can run 3 connectors. Comment 2 rows and un-comment 1 row.
            //     // if($i >= 1 &&    $i < $m) {}
            //     // if($i >= $m &&   $i < $m*2) {}
            //     // if($i >= $m*2 && $i < $m*3) {}
            //     if($i >= 480000) {}
            //     else continue; 
            // }
            if($this->param['resource_id'] == "TreatmentBank_ENV") { //total rows in media tab -> $m = 2,083,549 -> as of 19Dec2023, rounded to 2083600
                $m = 2083600/3; # rounded . can run 3 connectors. Comment 2 rows and un-comment 1 row.
                // if($i >= 1 &&    $i < $m) {}
                // if($i >= $m &&   $i < $m*2) {}
                if($i >= $m*2 && $i < $m*3) {}
                else continue; 
            }
            */
            
            // print_r($this->allowed_subjects); exit;
            if(self::valid_record($rec)) {
                /*Array( e.g. WoRMS
                    [http://purl.org/dc/terms/identifier] => WoRMS:note:103872
                    [http://rs.tdwg.org/dwc/terms/taxonID] => 257053
                    [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
                    [http://rs.tdwg.org/audubon_core/subtype] => 
                    [http://purl.org/dc/terms/format] => text/html
                    [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat
                    [http://purl.org/dc/terms/title] => habitat
                    [http://purl.org/dc/terms/description] => intertidal to shallow infratidal
                )*/
                
                $this->ontologies = "envo"; //always 'envo' unless WoRMS' distribution texts.
                
                // /* -------------------- start customize --------------------
                if($this->param['resource_id'] == '26_ENV') { //for WoRMS only with title = 'habitat' and 'distribution' will be processed.
                    if(strtolower($rec['http://purl.org/dc/terms/title']) == 'habitat') @$this->text_that_are_habitat++;
                    elseif(strtolower($rec['http://purl.org/dc/terms/title']) == 'distribution') $this->ontologies = "eol-geonames";
                    else continue;
                }
                
                // exit("\n[".$this->param['resource_id']."]\nelix\n"); //good debug
                /* === Edit for new resources === */
                /* assign ontologies assign ontology */
                if(in_array($this->param['resource_id'], array("10088_5097_ENV"))) $this->ontologies = "envo,eol-geonames";
                elseif(in_array($this->param['resource_id'], array("10088_6943_ENV"))) $this->ontologies = "envo,eol-geonames,growth";
                elseif(stripos($this->param['resource_id'], "SCtZ-") !== false)        $this->ontologies = "envo,eol-geonames"; //string is found
                elseif(stripos($this->param['resource_id'], "scb-") !== false)         $this->ontologies = "envo,eol-geonames,growth"; //string is found
                elseif(stripos($this->param['resource_id'], "scz-") !== false)         $this->ontologies = "envo,eol-geonames"; //string is found
                elseif(in_array($this->param['resource_id'], array("118935_ENV")))     $this->ontologies = "eol-geonames";
                if(in_array($this->param['resource_id'], array("120081_ENV", "120082_ENV", "118986_ENV", "118920_ENV", "120083_ENV", 
                    "118237_ENV", "MoftheAES_ENV", "30355_ENV", "27822_ENV", "30354_ENV", "119035_ENV", "118946_ENV", "118936_ENV", "118950_ENV",
                    "120602_ENV", "119187_ENV", "118978_ENV", "118941_ENV", "119520_ENV", "119188_ENV",
                    "15423_ENV", "91155_ENV"))) $this->ontologies = "envo,eol-geonames";
                elseif($this->param['resource'] == 'all_BHL') $this->ontologies = "envo,eol-geonames";
                if(@$this->param['group'] == 'BHL_plants') $this->ontologies = "envo,eol-geonames,growth"; //overwrites prev value
                
                // /* DATA-1897: Pensoft journals (textmining)
                if($this->param['resource_id'] == "TreatmentBank_ENV") {
                    $rec = $this->process_table_TreatmentBank_ENV($rec);
                    if(!$rec) continue;
                } //end TreatmentBank_ENV

                if($this->param['resource_id'] == "20_ENV")             $this->ontologies = "envo,eol-geonames"; //ZooKeys
                if($this->param['resource_id'] == "832_ENV")            $this->ontologies = "envo,eol-geonames"; //Subterranean Biology
                if($this->param['resource'] == 'Pensoft_journals')      $this->ontologies = "envo,eol-geonames"; //DATA-1897 Pensoft journals (textmining)
                // */
                
                // exit("\nontologies: [$this->ontologies]\n");
                // ---------------------- end customize ----------------------*/
                
                // print_r($rec); exit("\n[2]\n");
                
                $this->debug['subjects'][$rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm']] = '';
                // $this->debug['titles'][$rec['http://purl.org/dc/terms/title']] = ''; //debug only
                // $saved++; //debug only
                $this->results = array();
                // $this->eli = array(); //good debug

                /* debug only; during dev only --- force assignment of string to textmine
                $rec['http://purl.org/dc/terms/description'] = file_get_contents(DOC_ROOT."/tmp2/sample_treatment.txt");
                */

                self::save_article_2_txtfile($rec);
                // exit("\nstop muna\n");
            }
            // break; //get only 1 record, during dev only
            // if($i >= 10) break; //debug only         --- limit the no. of records processed
            // if($saved >= 20) break; //debug only     --- limit the no. of records processed
        } //end loop
        if($this->param['resource_id'] == '26_ENV') echo("\n text_that_are_habitat: ".$this->text_that_are_habitat."\n");
    }
    private function process_table_v2_WoRMS($meta, $what)
    {   //print_r($meta);
        echo "\n process_table_v2_WoRMS ".$meta->file_uri."...\n";
        $i = 0; $saved = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\n[1]\n");
            if($what == 'info_list') { //occurrence extension
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0191a5b6bbeexxxbe3f101758872e911_26
                    [http://rs.tdwg.org/dwc/terms/taxonID] => 1054700
                    [http://rs.tdwg.org/dwc/terms/lifeStage] => 
                    [http://rs.tdwg.org/dwc/terms/sex] => 
                )*/
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];

                if(!isset($this->exclude_taxonIDs[$taxonID])) {
                    $this->occurrenceID_taxonID[$occurrenceID] = $taxonID;
                }

            }
            elseif($what == 'annotate') { //MoF extension
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/measurementID] => 286376_1054700
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0191a5b6bbeexxxbe3f101758872e911_26
                    [http://eol.org/schema/measurementOfTaxon] => true
                    [http://eol.org/schema/parentMeasurementID] => 
                    [http://rs.tdwg.org/dwc/terms/measurementType] => http://rs.tdwg.org/dwc/terms/habitat
                    [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_01000024
                    [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                    [http://eol.org/schema/terms/statisticalMethod] => 
                    [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                    [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                    [http://rs.tdwg.org/dwc/terms/measurementMethod] => inherited from urn:lsid:marinespecies.org:taxname:101, Gastropoda Cuvier, 1795
                    [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                    [http://purl.org/dc/terms/source] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=1054700
                    [http://purl.org/dc/terms/bibliographicCitation] => 
                    [http://purl.org/dc/terms/contributor] => 
                    [http://eol.org/schema/reference/referenceID] => 
                )*/
                $measurementType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
                $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if($measurementType == 'http://eol.org/schema/terms/Present' && $measurementValue) {
                    if($taxonID = @$this->occurrenceID_taxonID[$occurrenceID]) {
                        $this->ontologies = "eol-geonames";
                        // print_r($rec); exit("\nfound 1\n");
                        $this->results = array();
                        self::save_article_2_txtfile_MoF($rec, $taxonID);    
                    }
                    // else exit("\nShould not go here\n"); //can definitely possibly go here...
                }
            }
        }
    }
    private function process_table_taxa($meta)
    {   //print_r($meta);
        echo "\nprocess_table_taxa() ".$meta->file_uri."...\n";
        $i = 0; $saved = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % $this->modulo) == 0) echo "\nyyy".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit("\nstop 1\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => Q80005
                [http://purl.org/dc/terms/source] => http://en.wikipedia.org/w/index.php?title=Fern&oldid=956482677
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => Q178249
                [http://rs.tdwg.org/dwc/terms/scientificName] => Filicophyta
                [http://rs.tdwg.org/dwc/terms/taxonRank] => phylum
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => 
            )*/
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $taxonRank = $rec['http://rs.tdwg.org/dwc/terms/taxonRank'];

            if($this->param['resource_id'] == '617_ENV') { //Wikipedia EN - remove traits for specified ranks
                if(in_array($taxonRank, $this->excluded_ranks)) $this->exclude_taxonIDs[$taxonID] = '';
            }

            // /* new: Nov 21, 2023:
            if($scientificName = @$rec["http://rs.tdwg.org/dwc/terms/scientificName"]) {
                if(!Functions::valid_sciname_for_traits($scientificName)) $this->exclude_taxonIDs[$taxonID] = '';
            }
            // */

        }
        echo "\nexclude_taxonIDs: ".count($this->exclude_taxonIDs)."\n";
    }
    private function save_article_2_txtfile($rec) //Media extension
    {   /* Array(
        [http://purl.org/dc/terms/identifier] => 8687_distribution
        [http://rs.tdwg.org/dwc/terms/taxonID] => 8687
        [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
        [http://purl.org/dc/terms/format] => text/plain
        [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
        [http://purl.org/dc/terms/title] => Distribution and Habitat
        [http://purl.org/dc/terms/description] => <p><i>Abavorana nazgul</i> is only known from the mountain, Gunung Jerai, in the state of Kedah on the west coast of Peninsular Malaysia. It is associated with riparian habitats, and can be found near streams. It has been only been found at elevations between 800 – 1200 m (Quah et al. 2017).</p>
        [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://amphibiaweb.org/cgi/amphib_query?where-genus=Abavorana&where-species=nazgul&account=amphibiaweb
        [http://purl.org/dc/terms/language] => en
        [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by/3.0/
        [http://eol.org/schema/agent/agentID] => 40dafcb8c613187d62bc1033004b43b9
        [http://eol.org/schema/reference/referenceID] => d08a99802fc760abbbfc178a391f9336; 8d5b9dee4f523c6243387c962196b8e0; 4d496c9853b52d6d4ee443b4a6103cca
        )*/
        // exit("\ntaxonID: ".$rec['http://rs.tdwg.org/dwc/terms/taxonID']."\n"); //debug only
        // exit("\n[".$this->param['resource_id']."]\n"); //e.g. '617_ENV'
        $basename = $rec['http://rs.tdwg.org/dwc/terms/taxonID']."_-_".$rec['http://purl.org/dc/terms/identifier'];
        $desc = strip_tags($rec['http://purl.org/dc/terms/description']);
        $desc = trim(Functions::remove_whitespace($desc));

        self::retrieve_annotation($basename, $desc); //it is in this routine where the pensoft annotator is called/run
        self::write_to_pensoft_tags($basename);
    }
    private function save_article_2_txtfile_MoF($rec, $taxonID) //MoF extension
    {   /*Array(
            [http://rs.tdwg.org/dwc/terms/measurementID] => 286376_1054700
            [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0191a5b6bbeexxxbe3f101758872e911_26
            [http://eol.org/schema/measurementOfTaxon] => true
            [http://eol.org/schema/parentMeasurementID] => 
            [http://rs.tdwg.org/dwc/terms/measurementType] => http://rs.tdwg.org/dwc/terms/habitat
            [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_01000024
            [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
            [http://eol.org/schema/terms/statisticalMethod] => 
            [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
            [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
            [http://rs.tdwg.org/dwc/terms/measurementMethod] => inherited from urn:lsid:marinespecies.org:taxname:101, Gastropoda Cuvier, 1795
            [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
            [http://purl.org/dc/terms/source] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=1054700
            [http://purl.org/dc/terms/bibliographicCitation] => 
            [http://purl.org/dc/terms/contributor] => 
            [http://eol.org/schema/reference/referenceID] => 
        )*/
        $basename = $taxonID."_-_".$rec['http://rs.tdwg.org/dwc/terms/measurementID'];
        $desc = strip_tags($rec['http://rs.tdwg.org/dwc/terms/measurementValue']);
        $desc = trim(Functions::remove_whitespace($desc));
        self::retrieve_annotation($basename, $desc); //it is in this routine where the pensoft annotator is called/run
        self::write_to_pensoft_tags($basename);
    }
    private function write_to_pensoft_tags($basename)
    {
        $file = $this->eol_tags_path."eol_tags_noParentTerms.tsv";
        if($f = Functions::file_open($file, "a")) {
            /*Array( [http://purl.obolibrary.org/obo/ENVO_00002011] => freshwater
                     [http://purl.obolibrary.org/obo/ENVO_00000026] => well
            )*/
            // print_r($this->eli); //good debug
            // /* NEW
            foreach($this->results as $uri => $rek) {
                if($ret = self::apply_adjustments($uri, $rek['lbl'])) {
                    $uri = $ret['uri'];
                    $label = $ret['label'];
                    /* for utility report only, not part of main operation
                    $this->all_envo_terms[$uri] = $label; //for stats only - report for Jen
                    */
                }
                else continue;
                
                if(stripos($uri, "ENVO_") !== false) { //string is found
                    $arr = array($basename, '', '', $label, pathinfo($uri, PATHINFO_FILENAME), $rek['ontology'], ""); //7th param is mType
                }
                else $arr = array($basename, '', '', $label, $uri, $rek['ontology'], ""); //7th param is mType
                
                /*===== CUSTOMIZE START =====*/
                // /* DATA-1893 - a provision to assign measurementType as early as this stage
                if(!in_array($this->param['resource_id'], array('617_ENV'))) { //excluding Wikipedia EN for now
                    if($assignment = @$this->new_patterns[$label]) {
                        $arr = array($basename, '', '', $label, $assignment['mValue'], $rek['ontology'], $assignment['mType']);
                    }
                }
                // */
                
                // /* implement soil composition
                if(in_array($this->param['resource_id'], array('617_ENV', 'TreatmentBank_ENV'))) { //Wikipedia EN & TreatmentBank
                    if($possible_mtype = @$rek['mtype']) $arr[6] = $possible_mtype; //7th param is mType
                }
                // */

                $uri = self::WoRMS_URL_format($uri); # can be general, for all resources

                // /* for all resources: exclude terms not in EOL terms file
                if(!isset($this->allowed_terms_URIs[$uri])) {
                    // echo "\n-----------------\nhuli ka! NOT FOUND IN EOL TERMS FILE\n"; print_r($rek); print_r($ret); print_r($arr); echo "-----------------\n";
                    continue;
                }
                // */
                /*===== CUSTOMIZE END =====*/
                
                fwrite($f, implode("\t", $arr)."\n");
            }
            // */
            fclose($f);
        }
    }
    /* OBSOLETE: had a hard limit of 2000. Replaced by one below.
    public function retrieve_annotation($id, $desc)
    {
        $len = strlen($desc);
        $loops = $len/2000; //echo("\n\n[$loops]");
        $loops = ceil($loops);
        $ctr = 0;
        sleep(0.5);
        for($loop = 1; $loop <= $loops; $loop++) { //echo "\n[$loop of $loops]";
            $str = substr($desc, $ctr, 2000);
            $str = utf8_encode($str);
            // if($loop == 29) exit("\n--------\n[$str]\n---------\n");
            
            if($this->includeOntologiesYN)  $id = md5($str.$this->ontologies); //for now only for those SI PDFs/epubs
            else                            $id = md5($str); //orig, the rest goes here...
            
            self::retrieve_partial($id, $str, $loop);
            $ctr = $ctr + 2000;
        }
        // print_r($this->results); exit("\n[$loops]\n");
        if(isset($this->results)) return $this->results; //the return value is used in AntWebAPI.php
    }
    */
    public function retrieve_annotation($id, $desc)
    {
        // /* new: massage description for TreatmentBank (Nov 27, 2023)
        if($this->param['resource_id'] == "TreatmentBank_ENV") $desc = $this->format_TreatmentBank_desc($desc);
        // return; //during dev only
        // */

        // exit("\nontologies retrieve_annotation(): [$this->ontologies]\n");
        $desc = str_replace("....", "", $desc);
        $desc = str_replace("----", "", $desc);
        $desc = str_replace("????", "", $desc);
        $desc = str_replace("000000", "", $desc);
        $desc = str_replace("����", "", $desc);
        $desc = str_replace("ï¿½", "", $desc);
        
        $orig_batch_length = 1900; // ideal for now 1900 so it does not give the max string error.
        $batch_length = $orig_batch_length;
        // $desc = "-12345- -678910- -1112131415- -1617181920- -2122- -2324- -252627- -28- -2930-";
        $len = strlen($desc);
        $loops = $len/$batch_length; //echo("\nloops: [$loops]\n");
        $loops = ceil($loops);
        $ctr = 0;
        sleep(0.5);
        for($loop = 1; $loop <= $loops; $loop++) { //echo "\n[$loop of $loops]";
            // ----- block check start -----
            $i = 100;
            $new_b_l = $batch_length;
            for($x = 1; $x <= $i; $x++) {
                $char_ahead = substr($desc, $ctr+$new_b_l, 1); //print("\nchar_ahead: [$char_ahead]");
                if($char_ahead == " " || $char_ahead == "") {
                    $batch_length = $new_b_l;
                    $str = substr($desc, $ctr, $batch_length);
                    break;
                }
                $new_b_l++;
            }
            // ----- block check end -----
            if(!isset($str)) {
                echo "\nINVESTIGATE: str var not defined. [$id]\n[$desc]\n"; // rare case where char_ahead has always a value
                $str = substr($desc, $ctr, $batch_length);
                echo "\nWill now proceed with: [$id]\n[$str]\n";
            }
            
            // /* sub main operation
            // $str = utf8_encode($str);            // commented Nov 20, 2023
            $str = Functions::conv_to_utf8($str);   // added Nov 20, 2023
            $str = self::format_str($str);
            if($this->includeOntologiesYN)  $id = md5($str.$this->ontologies); //for now only for those SI PDFs/epubs
            else                            $id = md5($str); //orig, the rest goes here...
            if($str) self::retrieve_partial($id, $str, $loop);
            // */
            
            $ctr = $ctr + $batch_length;
            // echo "\nbatch $loop: [$str][$ctr][$batch_length]\n"; //good debug
            $batch_length = $orig_batch_length;
        } //end outer for loop
        if(isset($this->results)) return $this->results; //the return value is used in AntWebAPI.php
    }
    private function format_str($str) //manual intervention
    {   /* per: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67728&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67728
        I've only seen this in TreatmentBank. It would be a problem in any connector, but I'll bet there's something about the format of their service that makes 
        TreatmentBank vulnerable: multiword terms should not be found where the words are separated by punctuation: ( or ) or / or , or ;
        eg: http://treatment.plazi.org/id/F7AB94E4F5B59F2C76DF9B7856BDFA5C "field soil"
            http://treatment.plazi.org/id/216FC728FFC5EF2A738326C8AB1BCD40 "river island" */

        $str = str_replace("š", "s", $str);     //Strangely Pensoft converts Košice to "Koš<b>ice</b>"  -> erroneously creates "ice"
        $str = str_ireplace("ď", "d", $str);    //Strangely Pensoft converts Fenďa to "<b>Fen</b>ďa"    -> erroneously creates "fen"

        // /* false-positive: https://eol-jira.bibalex.org/browse/DATA-1877?focusedCommentId=65818&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65818
        $str = str_ireplace("United States National Museum", "", $str);
        // */

        $separators = array("(", ")", "/", ",", ";", ":");
        foreach($separators as $separator) $str = str_replace($separator, "\n", $str);
        return $str;
    }
    private function retrieve_partial($id, $desc, $loop)
    {   // echo "\n[$id]\n";
        // echo("\nstrlen: ".strlen($desc)."\n"); // good debug
        if($arr = self::retrieve_json($id, 'partial', $desc)) {
            // if($loop == 29) { print_r($arr['data']); //exit; }
            // print_r($arr); //exit; //good debug ***** this is the orig annotator output
            if(isset($arr['data'])) self::select_envo($arr['data']);
            else {
                echo "\n-=-=-=-=-=-=-=111\n[".$this->to_delete_file."]\n";
                print_r($arr);
                echo("\n[---$id---]\n[---$desc---]\n");
                echo("\n[".$arr['text'][0]."]\n");
                echo("\nInvestigate: might need to decrease orig_batch_length variable.\n strlen: ".strlen($desc)."\n");
                return;
            }    
            // echo("\nretrieved partial OK\n"); //good debug
        }
        else {
            if($json = self::run_partial($desc)) {
                self::save_json($id, $json, 'partial');
                // echo("\nSaved partial OK\n"); //good debug
                /* now start access newly created. The var $this->results will now be populated. */
                if($arr = self::retrieve_json($id, 'partial', $desc)) {
                    if(isset($arr['data'])) self::select_envo($arr['data']);
                    else {
                        echo "\n-=-=-=-=-=-=-=222\n[".$this->to_delete_file."]\n";
                        print_r($arr);
                        echo("\n222[---$id---]\n[---$desc---]\n[---$json---]\n");
                        echo("\n[".$arr['text'][0]."]\n");
                        echo("\nInvestigate: might need to decrease orig_batch_length variable.\n strlen: ".strlen($desc)."\n");
                        return;
                    }
                    // echo("\nretrieved (newly created) partial OK\n"); //good debug
                }
                else {
                    echo("\nShould not go here, since record should be created now.\n[$id]\n[$desc]\n[$json]\n strlen: ".strlen($desc)."\n"); //should not go here. Previously exit().
                    @$this->debug["Should not go here, since record should be created now"]++;
                    // exit("\nstop muna\n[".$this->to_delete_file."]\n");
                }
            }
            else {
                /* working; good debug. I assume these cases are network hiccups from Pensoft.
                echo("\n================\n -- nothing to save A...\n[$id]\n[$desc]\n[$loop] strlen: ".strlen($desc)."\n".$this->ontologies); //doesn't go here. Previously exit()
                //for debug only: to investigate further
                $file = self::build_path($id, 'partial');
                echo "\nfile: [$file]\n================\n"; */
                @$this->debug['nothing to save A...']++;
            }
        }
    }
    private function select_envo($arr)
    {   /*Array(
            [0] => Array(
                    [id] => http://purl.obolibrary.org/obo/ENVO_00000083
                    [lbl] => Hill
                    [context] => 2015. ^ Patterson, B. D. (2004). The Lions of Tsavo: Exploring the Legacy of Africa's Notorious Man-Eaters. New York: McGraw <b>Hill</b> Professional. ISBN 978-0-07-136333-4. ^ Patterson, B. D.; Neiburger, E. J.; Kasiki, S. M. (2003). 2.0.CO;2 "Tooth Breakage and Dental Disease
                    [length] => 4
                    [position] => 877
                    [ontology] => envo
                    [type] => CLASS
                    [is_synonym] => 
                    [color] => #F7F3E3
                    [is_word] => 1
                    [hash] => dda9a35f1c55d220ce83d768af23bfd5
                )
        */
        foreach($arr as $rek) {
            // /* general for all:
            
            // /* new: Nov 22, 2023 - Eli's initiative -- never use this
            // if($rek['is_word'] != "1") continue;
            // if($rek['is_synonym'] == "1") continue;
            // */

            // /* should not get 'fen' --- [context] => Almost all of these are incorrect e.g. 1 ‘‘<b>fen</b>. ov.’’ fenestra ovalis
            //    but should get 'philippines'       => in the valley of the dead found in <b>Philippines</b>.
            $needle = "<b>".$rek['lbl']."</b>.";
            if(stripos($rek['context'], $needle) !== false) { //string is found
                $needle = $rek['lbl'];
                if($this->substri_count($rek['context'], $needle) > 1) continue; //meaning an abbreviation and the whole word was also found inside the context.
            }
            // */

            // /* new: Nov 22, 2023 - Eli's initiative. Until a better sol'n is found. e.g. "Cueva de Altamira"
            if(stripos($rek['lbl'], " de ") !== false) continue; //string is found
            // */

            // print_r($this->param); //exit;
            // /* new Nov 23, 2023 per https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67733&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67733
            if(in_array($this->param['resource_id'], array('617_ENV', 'TreatmentBank_ENV'))) { //Wikipedia EN & TreatmentBank for now
                if(isset($this->labels_to_remove[$rek['lbl']])) continue;
            }
            // */

            $rek['id'] = self::WoRMS_URL_format($rek['id']); # can be general, for all resources
            // echo "\nGoes- 80\n"; print_r($rek);

            if($rek['ontology'] == "eol-geonames") { //per https://eol-jira.bibalex.org/browse/DATA-1877?focusedCommentId=65861&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65861
                // echo "\nGoes- 81\n";
                
                // /* un-comment to allow just 4 terms. Comment to allow all terms under geonames with 'ENVO' uri. It was in the past totally disallowing terms in geonames that have ENVO uri.
                if(stripos($rek['id'], "ENVO_") !== false) { //string is found
                    if(in_array($rek['lbl'], array('forest', 'woodland', 'grassland', 'savanna'))) {} //accepts these terms, and maybe more once allowed by Jen.
                    else continue;
                }
                // */
                // if commented there is error in tests for  "marine"

                // echo "\nGoes- 82\n";
                if(in_array($rek['lbl'], array('jordan', 'guinea', 'washington'))) continue; //always remove
                if(in_array($rek['id'], array('http://www.geonames.org/1327132',                //https://eol-jira.bibalex.org/browse/DATA-1887?focusedCommentId=66190&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66190
                                              'https://www.geonames.org/3463504'))) continue;   //https://eol-jira.bibalex.org/browse/DATA-1887?focusedCommentId=66197&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66197
                
                // /* exclude if context has certain strings that denote a literature reference - FOR ALL RESOURCES
                // vol. 8, p. 67. 1904.Tylobolus uncigerus, Brolemann, Ann. Soc. Ent. <b>France</b>, vol. 83, pp. 9, 22, fig.
                $parts_of_lit_ref = array(' vol.', ' p.', ' pp.', ' fig.', ' figs.');
                $cont = true;
                foreach($parts_of_lit_ref as $part) {
                    if(stripos($rek['context'], $part) !== false) $cont = false; //string is found
                }
                if(!$cont) continue;
                // */
            }
            // */
            // echo "\nGoes- 100\n";
            // /*
            if($rek['ontology'] == "envo") { //ontology habitat
                /* all legit combined below
                if(in_array($rek['lbl'], array('mesa', 'laguna'))) continue; //https://eol-jira.bibalex.org/browse/DATA-1877?focusedCommentId=65899&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65899
                if(in_array($rek['lbl'], array('rapids'))) continue; //118950_ENV https://eol-jira.bibalex.org/browse/DATA-1887?focusedCommentId=66259&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66259
                // remove 'ocean' (measurementValue = http://purl.obolibrary.org/obo/ENVO_00000447) for all resources. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1897?focusedCommentId=66613&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66613
                if(in_array($rek['lbl'], array('ocean', 'sea'))) continue;
                if($rek['id'] == 'http://purl.obolibrary.org/obo/ENVO_00000447') continue;                
                // per: https://eol-jira.bibalex.org/browse/DATA-1914 - as of Sep 20, 2022
                if(in_array($rek['lbl'], array('organ', 'field', 'well', 'adhesive', 'quarry', 'reservoir', 'umbrella', 'plantation', 'bar', 'planktonic material'))) continue;
                // exclude per: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67731&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67731
                // planktonic material
                */
                if($rek['id'] == 'http://purl.obolibrary.org/obo/ENVO_00000447') continue;                
                if(in_array($rek['lbl'], array('mesa', 'laguna', 'rapids', 'ocean', 'sea', 'organ', 'field', 'well', 'adhesive', 'quarry', 'reservoir', 'umbrella', 'plantation', 'bar', 'planktonic material'))) continue;
            }
            // */
            
            // /*
            if($rek['ontology'] == "growth") {
                if(in_array($rek['id'], array('https://www.wikidata.org/entity/Q16868813'))) continue; //https://eol-jira.bibalex.org/browse/DATA-1877?focusedCommentId=66125&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66125
            }
            // */
            
            // /* customize
            // exit("\n".$this->param['resource_id']."\n");
            if($this->param['resource_id'] == '21_ENV') { //AmphibiaWeb text
                if($rek['id'] == 'http://purl.obolibrary.org/obo/ENVO_00002010') continue; //saline water. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65409&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65409
                if(isset($this->descendants_of_saline_water[$rek['id']])) continue;
            }
            if(in_array($rek['id'], $this->remove_across_all_resources)) continue; //remove 'cloud', 'cut' for all resources
            // */
            // echo "\nGoes- 101\n";

            // /* customize: remove all records with measurementValue = http://purl.obolibrary.org/obo/ENVO_00000447
            // for all resources of: Memoirs of the American Entomological Society
            if(in_array($this->param['resource_id'], array("118935_ENV", "120081_ENV", "120082_ENV", "118986_ENV", "118920_ENV", 
                    "120083_ENV", "118237_ENV", "MoftheAES_ENV", "30355_ENV", "27822_ENV", "30354_ENV", "119035_ENV", "118946_ENV", "118936_ENV", "118950_ENV",
                    "120602_ENV", "119187_ENV", "118978_ENV", "118941_ENV", "119520_ENV", "119188_ENV"))) {
                if($rek['id'] == 'http://purl.obolibrary.org/obo/ENVO_00000447') continue; //remove 'ocean' Per Jen: https://eol-jira.bibalex.org/browse/DATA-1887?focusedCommentId=66228&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66228
            }
            // */
            
            $validTraitYN = self::John_Hill_vs_hill_mountain($rek);
            if(!$validTraitYN) continue;
            
            /* DATA-1893
            nothing to add here...
            */

            // /* another general for all: https://eol-jira.bibalex.org/browse/DATA-1897?focusedCommentId=66605&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66605
            $context = strip_tags($rek['context']);
            if(stripos($context, "India ink") !== false && $rek['lbl'] == "india") { //string is found
                // print_r($rek);
                continue; 
            }
            // */
            
            /*
            guatemala               Panthea <b>guatemala</b>                        EXCLUDE
            niger                   Enoplochiton <b>niger</b>                       EXCLUDE
            patagonia               Pseudomorpha <b>patagonia</b>                   EXCLUDE
            cerrado                 of the <b>cerrado</b>
            ural                    (preural 1 + <b>ural</b> 1)
            polar regions           extreme <b>polar regions</b>
            neotropics              the <b>neotropics</b>
            worldwide               is the <b>worldwide</b> unique
            subarctic               arctic and <b>subarctic</b>
            nearctic                other <b>nearctic</b> member
            southern hemisphere     in the <b>southern hemisphere</b>
            chile                   southern <b>Chile</b>

            Gulf Of Mexico                      United States: <b>Gulf of Mexico</b>
            Western Guinean Lowland Forests     West Sudanian savanna, <b>Western Guinean lowland forests</b>
            Central Asia            In <b>central Asia</b>
            Western Australia       (north-<b>western Australia</b>)
            Eastern Africa          and south-<b>eastern Africa</b>
            Eastern North America   across <b>eastern North America</b> from
            Eastern Canada          in <b>eastern Canada</b>
            Northern Europe         Barents Sea, <b>northern Europe</b>
            Eastern North America   across <b>eastern North America</b>
            Tropical Africa         in <b>tropical Africa</b>
            New Zealand             and <b>New-Zealand</b>
            */
            
            // /* another customized for a resource
            if($this->param['resource_id'] == "20_ENV" && $rek['lbl'] == "mon") continue; //related to TO DO below
            // */
            
            /* TO DO: must have an inteligent partial string to annotate --- e.g. PJ_ZooKeys_20
            orig text: Prov. Limon, Parque Internacional La Amistad
            Array(
                [id] => http://www.geonames.org/1308528
                [lbl] => mon
                [context] => <b>mon</b>, Parque Internacional La Amistad, Valle del Silencio, Alrededor del Refugio y Sendero Circular, 9.110281-82.961934, 2450 m, 22������27 September 2003,
            */
            
            // /* another general for all: https://eol-jira.bibalex.org/browse/DATA-1897?focusedCommentId=66606&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66606
            // if a string e.g. species "Enoplochiton niger", then annotator must not get 'niger' as a country name.
            if($rek['ontology'] == 'eol-geonames') {
                $lbl = $rek['lbl'];
                if(ctype_upper($lbl[0])) {} //continue
                else { //starts with small letter e.g. "chile", "niger"
                    $context = $rek['context'];
                    $needle = "<b>".ucfirst($lbl)."</b>";
                    if(strpos($context, $needle) !== false) {} //e.g. "<b>Chile</b>" //continue //string is found
                    else {
                        $needle = "<b>".$lbl."</b>";
                        $needle_tmp = "<b>".str_replace(" ", "_", $lbl)."</b>";
                        $context_tmp = str_replace($needle, $needle_tmp, $context);
                        if(strpos($context, $needle) !== false) { //e.g. 'niger' //string is found
                            if($before_needle = self::get_word_before_needle($needle_tmp, $context_tmp)) {
                                if(!ctype_alpha($before_needle[0])) {} //continue --- starts with "(" or any number
                                else {
                                    if(ctype_lower($before_needle[0])) {} //continue
                                    else { // word before needle is alpha and capital letter
                                        $possible_sciname = $before_needle." ".$lbl;
                                        
                                        /* commented Nov 4, 2022. Will investigate soon.
                                        if(self::is_valid_taxon($possible_sciname)) {
                                            // echo "\nNot a valid geonames: lbl: [$lbl] | possible_sciname: [$possible_sciname] | context: [$rek[context]]\n";  //good debug
                                            // print_r($rek); //good debug
                                            continue;
                                        }
                                        else {} //continue
                                        */
                                    }
                                }
                            }
                            else {} //continue; --- case where the needle is the first word in the context
                        }
                    }
                }
            }
            // */
            // echo "\nGoes- 102\n";

            // /* ----- New: Nov 8, 2022 - EOL Terms file ----- START
            // print_r($this->results);
            // Array(
            //     [http://purl.obolibrary.org/obo/ENVO_01000204] => array("lbl" => "tropical", "ontology" => "envo");
            // )
            if(!isset($this->allowed_terms_URIs[$rek['id']])) {
                // echo "\nEOL Terms file: ".count($this->allowed_terms_URIs)."\n";
                // echo "\nhulix ka! NOT FOUND IN EOL TERMS FILE: [".$rek['id']."]";
                @$this->debug["NOT FOUND IN EOL TERMS FILE"][$rek['id']]++;
                // print_r($rek); echo "-----------------\n";
                continue;
            }
            // ----- New: Nov 8, 2022 - EOL Terms file ----- END */

            // /* Nov 15, 2023: "linn" -> http://purl.obolibrary.org/obo/ENVO_00000040 
            // Any output from this source string should be discarded. It's a common form of the author string "Linnaeus"
            // https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67722&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67722
            // http://purl.obolibrary.org/obo/ENVO_00000040	source text: "linn"
            if($rek['id'] == "http://purl.obolibrary.org/obo/ENVO_00000040" || $rek['lbl'] == "linn") continue;
            // */

            // /* Eli's initiative: applied this one early on. Before, it was applied later on the process.
            if($ret = self::apply_adjustments($rek['id'], $rek['lbl'])) {
                $rek['id'] = $ret['uri'];
                $rek['lbl'] = $ret['label'];
            }
            else continue;
            // */

            // /* soil composition https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67736&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67736
            // These terms tend to be used for plants, though not always. I've never been very happy with the term "habitat" for them. 
            // I think I have a better idea now. Let me know if this is not practical: I'd like to keep their measurementValue mappings as they are, 
            // but change their measurementType to http://purl.obolibrary.org/obo/ENVO_09200008 (soil composition), for TreatmentBank and wikipedia textmining.
            if(in_array($this->param['resource_id'], array('617_ENV', 'TreatmentBank_ENV'))) { //Wikipedia EN & TreatmentBank
                if(isset($this->soil_compositions[$rek['lbl']])) $rek['mtype'] = "http://purl.obolibrary.org/obo/ENVO_09200008";
            }
            // */
            
            //============= below this point is where $this->results is populated =============
            if($this->param['resource_id'] == '617_ENV') { //Wikipedia EN
                if(ctype_lower(substr($rek['lbl'],0,1))) { //bec. references has a lot like 'Urban C.' which are authors.
                    $this->results[$rek['id']] = array("lbl" => $rek['lbl'], "ontology" => $rek['ontology'], "mtype" => @$rek['mtype']);
                    // $this->eli[$rek['id']][] = $rek['lbl']; //good debug
                }
                // else exit("\nWent here...\n"); //means Wikipedia EN is strict. "Sri Lanka" will be excluded.
            }
            else { //rest of the resources --> Just be sure the citation, reference, biblio parts of text is not included as input to Pensoft
                $this->results[$rek['id']] = array("lbl" => $rek['lbl'], "ontology" => $rek['ontology'], "mtype" => @$rek['mtype']);
            }
            // echo "\nGoes- 103\n";

        } //end foreach()
    }
    private function is_valid_taxon($str)
    {
        require_library('connectors/Functions_Memoirs');
        require_library('connectors/ParseListTypeAPI_Memoirs');
        require_library('connectors/ParseUnstructuredTextAPI_Memoirs'); 
        $func = new ParseUnstructuredTextAPI_Memoirs(false, false);
        $obj = $func->run_gnverifier($str); //print_r($obj); //exit;
        if($obj[0]->matchType == 'Exact') {
            if($val = $obj[0]->bestResult->matchedName) {
                if(self::correct_cardinality($val, $str)) return $val;
            }
            if($val = $obj[0]->bestResult->currentName) {
                if(self::correct_cardinality($val, $str)) return $val;
            }
            if($val = $obj[0]->bestResult->currentCanonicalFull) {
                if(self::correct_cardinality($val, $str)) return $val;
            }
        }
        return false;
    }
    private function correct_cardinality($val, $str)
    {
        if(self::more_than_one_word($str)) {
            if(self::more_than_one_word($val)) return true;
        }
        else {
            if(!self::more_than_one_word($val)) return true;
        }
        return false;
    }
    private function more_than_one_word($string)
    {
        $parts = explode(" ", $string);
        if(count($parts) > 1) return true;
        else return false;
    }
    private function get_word_before_needle($needle, $context)
    {
        $context = str_ireplace("\n", " ", $context);
        $context = str_ireplace("\t", " ", $context);
        $context = Functions::remove_whitespace($context);
        $context = str_replace("<b>", " <b>", $context);
        $context = str_replace("</b>", "</b> ", $context);
        $context = Functions::remove_whitespace($context);
        $words = explode(" ", $context);
        foreach($words as $index => $word) {
            if($word == $needle) {
                if($index == 0) { //meaning the $needle is the first word in the $context
                    // echo "\nditox:\nneedle:[$needle]\ncontext:[$context]\n"; print_r($words); //good debug
                    return "";
                }
                return $words[$index-1];
            }
        }
        print_r($words);
        exit("\nERROR: needle: [$needle]\ncontext: [$context]\n");
    }
    /* not used eventually
    private function leave_first_char_as_is_and_others_as_small_letter($str)
    {
        $first_char = $str[0];
        $second_char_onwards = strtolower(trim(substr($str,1,strlen($str))));
        return $first_char.$second_char_onwards;
    }
    */
    private function retrieve_json($id, $what, $desc)
    {
        $file = self::retrieve_path($id, $what);
        $this->to_delete_file = $file;
        // echo "\nfile = [$file]\n"; //good debug
        if(is_file($file)) {
            $json = file_get_contents($file); // echo "\nRetrieved OK [$id]";
            // echo "-R-"; //R for retrieved
            @$this->debug['counts']['R']++;
            // echo "\nfile: [$file]\n"; // good debug
            return json_decode($json, true);
        }
    }
    private function run_partial($desc)
    {   //echo "\nRunning Pensoft annotator...";
        /*
        http://api.pensoft.net/annotator?text=West Sahara woodlands&ontologies=eol-geonames
        http://api.pensoft.net/annotator?text=ocean marine sanctuary&ontologies=envo
        */
                
        $this->pensoft_run_cnt++;
        $uri = str_replace("MY_DESC", urlencode($desc), $this->pensoft_service);
        $uri = str_replace("MY_ONTOLOGIES", $this->ontologies, $uri);
        /* worked for the longest time. Just refactored for cleaner script.
        $cmd = 'curl -s GET "http://api.pensoft.net/annotator?text='.urlencode($desc).'&ontologies='.$this->ontologies.'"';
        */
        $cmd = 'curl -s GET "'.$uri.'"';
        $cmd .= " 2>&1";
        // sleep(2); //temporary
        $json = shell_exec($cmd); //echo "-C-"; //C for curl
        @$this->debug['counts']['C']++;
        // echo "\n$desc\n---------"; // echo "\n$json\n-------------\n"; //exit("\n111\n");
        return $json;
    }
    private function retrieve_path($id, $what) //$id is "$taxonID_$identifier"
    {
        $filename = "$id.json";
        $md5 = md5($id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        return $this->json_temp_path[$what] . "$cache1/$cache2/$filename";
    }
    private function save_json($id, $json, $what)
    {
        $file = self::build_path($id, $what);
        if($f = Functions::file_open($file, "w")) {
            fwrite($f, $json);
            fclose($f);
        }
        else exit("\nCannot write file\n");
    }
    private function build_path($id, $what) //$id is "$taxonID_$identifier"
    {
        $filename = "$id.json";
        $md5 = md5($id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($this->json_temp_path[$what] . $cache1)) mkdir($this->json_temp_path[$what] . $cache1);
        if(!file_exists($this->json_temp_path[$what] . "$cache1/$cache2")) mkdir($this->json_temp_path[$what] . "$cache1/$cache2");
        return $this->json_temp_path[$what] . "$cache1/$cache2/$filename";
    }
    /* obsolete, still from Vangelis
    private function gen_noParentTerms()
    {   echo "\nRun gen_noParentTerms()...\n";
        $current_dir = getcwd(); //get current dir
        chdir($this->root_path);
        // ./eol_scripts/exclude-parents-E.pl eol_tags/eol_tags.tsv eol_scripts/envo_child_parent.tsv > eol_tags/eol_tags_noParentTerms.tsv
        $cmd = "./eol_scripts/exclude-parents-E.pl $this->eol_tags_destination $this->eol_scripts_path"."envo_child_parent.tsv > $this->eol_tags_path"."eol_tags_noParentTerms.tsv";
        shell_exec($cmd);
        chdir($current_dir); //go back to current dir
        // un-comment if you want to investigate raw source files: eol_tags.tsv and eol_tags_noParentTerms.tsv
        // exit("\nStop muna, will investigate\n"); //comment in real operation
    }
    */
    private function valid_record($rec)
    {   if($rec['http://purl.org/dc/terms/type'] == 'http://purl.org/dc/dcmitype/Text' &&
           in_array(@$rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'], $this->allowed_subjects) &&
           @$rec['http://purl.org/dc/terms/description'] && $rec['http://rs.tdwg.org/dwc/terms/taxonID'] && 
           $rec['http://purl.org/dc/terms/identifier']) return true;
        else return false;
    }
    private function John_Hill_vs_hill_mountain($rek) // accept small case 'hill', ignore upper case 'Hill'. Latter can be a person's name.
    {   
        // /* new: Nov 20, 2023
        if($rek['lbl'] == "lete") { //strangely Pensoft's context generates: "соmр<b>lete</b>"
            if    (strpos($rek['context'], " <b>lete</b>") !== false) {} //string is found, case sensitive
            elseif(strpos($rek['context'], " lete ")       !== false) {} //string is found, case sensitive
            else return false;
        }
        // */
        
        /*
        "id": "http://purl.obolibrary.org/obo/ENVO_00000083",
        "lbl": "hill",
        "context": "the river and next to the <b>hill</b>",

        "id": "http://purl.obolibrary.org/obo/ENVO_00000083",
        "lbl": "hill",
        "context": "Michael R. and Joseph <b>Hill</b>",
        */
        $words = array('urban', 'hill'); //Urban C. -> is a name
        foreach($words as $word) {
            if($rek['lbl'] == $word) {
                if(strpos($rek['context'], $word) !== false) return true;   //if small letter then OK   //string is found
                else return false;                                          //if not found, meaning big letter. then exclude. Might be a person's name.
            }
        }
        return true; //for most part
    }
    private function get_allowed_subjects($pipe_delimited)
    {   $arr = explode("|", $pipe_delimited);
        foreach($arr as $subject) {
            if($val = @$this->subjects[$subject]) $allowed_subjects[] = $val;
            else exit("\nSubject not yet initialized [$subject]\n");
        }
        return $allowed_subjects;
    }
    function build_info_tables(){}
    private function get_unique_obj_identifiers()
    {
        $tsv = $this->eol_tags_path.'eol_tags_noParentTerms.tsv';
        foreach(new FileIterator($tsv) as $line_number => $row) {
            $arr = explode("\t", $row); // print_r($arr); exit;
            /* Array(
                [0] => 1005_-_1005_distribution.txt
                [1] => 117
                [2] => 122
                [3] => shrubs
                [4] => ENVO:00000300
                [5] => envo
            )*/
            $arr[0] = str_replace('.txt', '', $arr[0]);
            $a = explode("_-_", $arr[0]);
            if($val = @$a[1]) $ids[$val] = '';
        }
        return $ids;
    }
    private function save_media_metadata_for_these_objects($obj_identifiers, $meta)
    {   echo "\nsave_media_metadata_for_these_objects()...";
        // $this->json_temp_path = create_temp_dir() . "/"; //abandoned. not used anymore.
        echo("\njson temp path: ".$this->json_temp_path['metadata']."\n");
        $agent_ids = array();
        $i = 0; $saved = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % $this->modulo) == 0) echo "\nzzz".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\n".count($obj_identifiers)."\n");
            /* Array(
                [http://purl.org/dc/terms/identifier] => 8687_distribution
                [http://rs.tdwg.org/dwc/terms/taxonID] => 8687
                [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
                [http://purl.org/dc/terms/format] => text/plain
                [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
                [http://purl.org/dc/terms/title] => Distribution and Habitat
                [http://purl.org/dc/terms/description] => <p><i>Abavorana nazgul</i> is only known from the mountain, Gunung Jerai, in the state of Kedah on the west coast of Peninsular Malaysia. It is associated with riparian habitats, and can be found near streams. It has been only been found at elevations between 800 – 1200 m (Quah et al. 2017).</p>
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://amphibiaweb.org/cgi/amphib_query?where-genus=Abavorana&where-species=nazgul&account=amphibiaweb
                [http://purl.org/dc/terms/language] => en
                [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by/3.0/
                [http://eol.org/schema/agent/agentID] => 40dafcb8c613187d62bc1033004b43b9
                [http://eol.org/schema/reference/referenceID] => d08a99802fc760abbbfc178a391f9336; 8d5b9dee4f523c6243387c962196b8e0; 4d496c9853b52d6d4ee443b4a6103cca
            )*/
            $identifier = $rec['http://purl.org/dc/terms/identifier'];
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if(isset($obj_identifiers[$identifier])) {
                $final = array();
                if($val = @$rec['http://purl.org/dc/terms/source']) $final['source'] = $val;
                if($val = @$rec['http://rs.tdwg.org/ac/terms/furtherInformationURL']) $final['source'] = $val;
                if($val = @$rec['http://purl.org/dc/terms/bibliographicCitation']) $final['bibliographicCitation'] = $val;
                if($val = @$rec['http://purl.org/dc/terms/contributor']) $final['contributor'] = $val;
                if($val = @$rec['http://eol.org/schema/reference/referenceID']) $final['referenceID'] = $val;
                if($val = @$rec['http://eol.org/schema/agent/agentID']) {
                    $final['agentID'] = $val;
                    $ids = explode(";", trim($val));
                    $ids = array_map('trim', $ids);
                    foreach($ids as $id) {
                        $agent_ids[$id] = '';
                    }
                }
                
                // /* ================= customize start =================
                if($this->param['resource_id'] == '20_ENV') {
                    /*Please move the doi from References to the Source column. https://eol-jira.bibalex.org/browse/DATA-1897?focusedCommentId=66602&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66602 */
                    if($val = @$rec['http://eol.org/schema/reference/referenceID']) $final['referenceID'] = '';
                    if($val = @$rec['http://eol.org/schema/reference/referenceID']) $final['source'] = $val;
                }
                /* Eli's initiative: bring text description to measurementRemarks MoF
                                     --- works OK but no instructions to do so
                                     --- part of pair #001 1of2
                if($this->param['resource'] == 'Pensoft_journals') {
                    if($val = @$rec['http://purl.org/dc/terms/description']) $final['measurementRemarks'] = str_replace("\\n", "", $val);
                }
                */
                // ================= customize end ================= */
                
                if($final) {
                    $json = json_encode($final);
                    self::save_json($taxonID."_".$identifier, $json, 'metadata');
                }
            }
        }
        return $agent_ids;
    }
    private function save_agent_metadata_for_these_agents($agent_ids, $meta)
    {   echo "\nsave_agent_metadata_for_these_agents()...";
        echo("\njson temp path: ".$this->json_temp_path['metadata']."\n");
        $i = 0; $saved = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 1000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\n".count($agent_ids)."\n");
            /* Array(
                [http://purl.org/dc/terms/identifier] => 40dafcb8c613187d62bc1033004b43b9
                [http://xmlns.com/foaf/spec/#term_name] => Zheng Oong
                [http://eol.org/schema/agent/agentRole] => author
                [http://xmlns.com/foaf/spec/#term_homepage] => 
            )*/
            $identifier = $rec['http://purl.org/dc/terms/identifier'];
            if(isset($agent_ids[$identifier])) {
                $final = array();
                if($val = @$rec['http://purl.org/dc/terms/identifier']) $final['identifier'] = $val;
                if($val = @$rec['http://xmlns.com/foaf/spec/#term_name']) $final['term_name'] = $val;
                if($val = @$rec['http://eol.org/schema/agent/agentRole']) $final['agentRole'] = $val;
                if($val = @$rec['http://xmlns.com/foaf/spec/#term_homepage']) $final['term_homepage'] = $val;
                if($final) {
                    $json = json_encode($final);
                    self::save_json("agent_".$identifier, $json, 'metadata');
                }
            }
        }
    }
    private function get_opendata_dwca_url($resource_name)
    {
        $url = str_replace('RESOURCE_NAME', $resource_name, $this->call['opendata resource via name']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $arr = json_decode($json, true); // print_r($arr);
            if($recs = @$arr['result']['results']) {
                foreach($recs as $rec) {
                    if($rec['name'] == $resource_name) return $rec['url'];
                }
            }
        }
    }
    private function lookup_opendata_resource()
    {
        print_r($this->param);
        /* Array(
            [task] => generate_eol_tags
            [resource] => AmphibiaWeb text
            [resource_id] => 21_ENV
            [subjects] => Distribution
        )*/
        $resource_name = $this->param['resource'];
        
        // /* customized
        // exit("\n".$this->param['resource_id']."\n");
        if($this->param['resource_id'] == '26_ENV') {
            $this->DwCA_URLs[$resource_name] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/26_meta_recoded.tar.gz'; //bec. record is private in OpenData.eol.org
            print_r($this->DwCA_URLs);
            return;
        }
        // */
        
        // /* during dev only
        if(!Functions::is_production()) {
            if($this->param['resource_id'] == '617_ENV') {
                $this->DwCA_URLs[$resource_name] = 'http://localhost/eol_php_code/applications/content_server/resources_3/80.tar.gz';
                print_r($this->DwCA_URLs);
                return;
            }
        }
        // */
        
        echo "\nresource_name is: [$resource_name]\n"; //exit;
        if($dwca_url = self::get_opendata_dwca_url($resource_name)) {
            /* based here:
            $this->DwCA_URLs['AmphibiaWeb text'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/21.tar.gz';
            */
            $this->DwCA_URLs[$resource_name] = $dwca_url;
            print_r($this->DwCA_URLs);
        }
        else {
            $tmp = str_replace("_ENV", "", $this->param['resource_id']);
            if(Functions::is_production()) $dwca_url = "https://editors.eol.org/eol_php_code/applications/content_server/resources/".$tmp.".tar.gz";
            else                           $dwca_url = "http://localhost/eol_php_code/applications/content_server/resources_3/".$tmp.".tar.gz";
            echo "\nDwCA URL: $dwca_url\n".$this->param['resource_id']."\n";
            if(Functions::ping_v2($dwca_url)) {
                $this->DwCA_URLs[$resource_name] = $dwca_url;
                print_r($this->DwCA_URLs);
            }
            else exit("\nOpenData resource not found [$resource_name]\n");
        }
        // exit("\n-exit muna-\n");
    }
    private function noParentTerms_less_entities_file()
    {   echo "\nCleaning noParentTerms...\n";
        /*
        Jen has spoken, entities file is now obsolete: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=66641&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66641
        */
        /* step 1: get_envo_from_entities_file
        $envo_from_entities = self::get_envo_from_entities_file();
        // print_r($envo_from_entities); exit;
        // Array(
        //     [0] => _entities_3
        //     [1] => ENVO_00000002
        //     [2] => ENVO_00000012
        //     [3] => ENVO_00000013
        //     [4] => ENVO_00000014
        foreach($envo_from_entities as $envo_term) $envoFromEntities[$envo_term] = '';
        unset($envo_from_entities);
        */
        
        /* step 2: loop */
        if(copy($this->eol_tags_path."eol_tags_noParentTerms.tsv", $this->eol_tags_path."eol_tags_noParentTerms.tsv.old")) echo "\nCopied OK (eol_tags_noParentTerms.tsv)\n";
        else exit("\nERROR: Copy failed (eol_tags_noParentTerms.tsv)\n");
        /* sample entry in eol_tags_noParentTerms.tsv.old
        Q27075389_-_3fbbae3f2254cfaa6d3116e0289bf7a5			boreal	http://www.wikidata.org/entity/Q1342399
        Q27075917_-_1513ce4574ed644a72e3f8471b848964			boreal	http://www.wikidata.org/entity/Q1342399
        Q28122714_-_6403c7c5a4729f8a0a26c58725779c5b			subarctic	http://www.wikidata.org/entity/Q1342399
        Q62854736_-_f1bc9ada6ddeb011d7e1c3037a71f6fe			subarctic	http://www.wikidata.org/entity/Q1342399
        Q140_-_3534a7422ad054e6972151018c05cb38			habitat	ENVO_01000739
        Q140_-_3534a7422ad054e6972151018c05cb38			radiation	ENVO_01001023
        Q140_-_3534a7422ad054e6972151018c05cb38			climate	ENVO_01001082
        
        From this point we now have 6 columns. The 6th is the ontology (NEW)
        From WoRMS process:
        244557_-_WoRMS:note:86413			Western Australia	http://www.geonames.org/2058645	eol-geonames
        244557_-_WoRMS:note:86413			Arafura Sea	http://www.marineregions.org/mrgid/4347	eol-geonames
        244558_-_WoRMS:note:86414			mud	ENVO_01000001	envo
        244558_-_WoRMS:note:86414			gravel	ENVO_01000018	envo
        903E305AF00D922FFF7B0AC2D6C4F88A.taxon_-_903E305AF00D922FFF7B0AC2D6C4F88A.text			alpine	ENVO_01000340	envo	
        */
        $f = Functions::file_open($this->eol_tags_path."eol_tags_noParentTerms.tsv", "w"); fclose($f); //initialize
        $file = $this->eol_tags_path."eol_tags_noParentTerms.tsv.old"; $i = 0;
        foreach(new FileIterator($file) as $line => $row) {
            $i++; //if(($i % $this->modulo) == 0) echo "\n".number_format($i);
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row); // print_r($tmp); exit;
            /*Array(
                [0] => Q140_-_3534a7422ad054e6972151018c05cb38
                [1] => 
                [2] => 
                [3] => habitat
                [4] => ENVO_01000739
                [5] => envo
            )*/
            
            if($tmp[5] == "envo") {
                /* entities file now OBSOLETE
                $envo_term = pathinfo($tmp[4], PATHINFO_BASENAME); //bec it can be "http://www.wikidata.org/entity/Q1342399" or "ENVO_01001082".
                if(isset($envoFromEntities[$envo_term])) {
                    $f = Functions::file_open($this->eol_tags_path."eol_tags_noParentTerms.tsv", "a");
                    fwrite($f, $row."\n");
                    fclose($f);
                } */
                if(!self::is_unique_row($tmp)) continue; //new 16Jun2022 to prevent duplicates
                $f = Functions::file_open($this->eol_tags_path."eol_tags_noParentTerms.tsv", "a");
                fwrite($f, $row."\n"); //echo "\n[$row]\n"; //good debug
                fclose($f);
            }
            elseif(in_array($tmp[5], array('eol-geonames', 'growth'))) {
                if(!self::is_unique_row($tmp)) continue; //new 16Jun2022 to prevent duplicates
                $f = Functions::file_open($this->eol_tags_path."eol_tags_noParentTerms.tsv", "a");
                fwrite($f, $row."\n");
                fclose($f);
            }
            else exit("\nUndefined ontology: [".$tmp[5]."]\nWill terminate now (2).\n");
        }
        $out = shell_exec("wc -l " . $this->eol_tags_path."eol_tags_noParentTerms.tsv.old"); echo "\n eol_tags_noParentTerms.tsv.old ($out)\n";
        $out = shell_exec("wc -l " . $this->eol_tags_path."eol_tags_noParentTerms.tsv");     echo "\n eol_tags_noParentTerms.tsv ($out)\n";
    }
    private function is_unique_row($tmp) //$tmp is an array
    {
        /*
        903E305AF00D922FFF7B0AC2D6C4F88A.taxon_-_903E305AF00D922FFF7B0AC2D6C4F88A.text			italy	http://www.geonames.org/3175395	eol-geonames	
        903E305AF00D922FFF7B0AC2D6C4F88A.taxon_-_04d3143bfe8a216c037d8a4c27394c75			italy	http://www.geonames.org/3175395	eol-geonames	
        */
        $str = $tmp[0]; //903E305AF00D922FFF7B0AC2D6C4F88A.taxon_-_903E305AF00D922FFF7B0AC2D6C4F88A.text
        $arr = explode("_-_", $str);
        $word1 = $arr[0]; //get the taxon part
        $word2 = $tmp[3]; //italy
        $md5 = md5($word1.$word2);
        if(isset($this->unique_rows[$md5])) return false;
        else {
            $this->unique_rows[$md5] = '';
            return true;
        }
    }
    public function apply_adjustments($uri, $label) //apply it here: ALL_remap_replace_remove.txt
    {
        if(in_array($uri, array("http://purl.obolibrary.org/obo/ENVO_00000029", "http://purl.obolibrary.org/obo/ENVO_00000104")) && $label == 'ravine') $uri = "http://purl.obolibrary.org/obo/ENVO_00000100";
            
        // /* Eli's initiative: mountain should take ENVO_00000081 and not ENVO_00000264
        if(in_array($uri, array("http://purl.obolibrary.org/obo/ENVO_00000264")) && in_array($label, array('mountain', 'mountains'))) $uri = "http://purl.obolibrary.org/obo/ENVO_00000081";
        // */
                
        if($new_uri = @$this->mRemarks[$label]) $uri = $new_uri;
        if($new_uri = @$this->remapped_terms[$uri]) $uri = $new_uri;
        if(isset($this->delete_MoF_with_these_labels[$label])) return false;
        if(isset($this->delete_MoF_with_these_uris[$uri])) return false;

        // /* customize
        if($this->param['resource_id'] == '21_ENV') { //AmphibiaWeb text
            if($uri == 'http://purl.obolibrary.org/obo/ENVO_00002010') return false; //saline water. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65409&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65409
            if(isset($this->descendants_of_saline_water[$uri]))        return false; //saline water. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65409&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65409
        }
        if(in_array($uri, $this->remove_across_all_resources)) return false; //remove 'cloud', 'cut' for all resources
        // */
        
        return array('label' => $label, 'uri' => $uri);
    }
    private function init_DATA_1841_terms_remapped()
    {
        require_library('connectors/TropicosArchiveAPI');
        /* START DATA-1841 terms remapping */
        $url = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Terms_remapped/DATA_1841_terms_remapped.tsv";
        $func = new TropicosArchiveAPI(NULL); //to initialize variable $this->uri_values in TropicosArchiveAPI
        $this->remapped_terms = $func->add_additional_mappings(true, $url, 60*60*24); //*this is not add_additional_mappings() 60*60*24
        echo "\nremapped_terms: ".count($this->remapped_terms)."\n";
        /* END DATA-1841 terms remapping */
        
        /* this row now deleted in: "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Terms_remapped/DATA_1841_terms_remapped.tsv"
        per: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65470&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65470
        http://purl.obolibrary.org/obo/ENVO_01000251	http://www.wikidata.org/entity/Q1342399
        */
        
        // /* for WoRMS only: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65471&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65471
        // http://purl.obolibrary.org/obo/ENVO_01000127 (canyon) => http://purl.obolibrary.org/obo/ENVO_00000267 (submarine canyon)
        // http://purl.obolibrary.org/obo/ENVO_00000087 (cliff) => http://purl.obolibrary.org/obo/ENVO_00000088 (sea cliff)
        // http://purl.obolibrary.org/obo/ENVO_00000182 (plateau) => discard. This could mean a few different things
        if($this->param['resource_id'] == '26_ENV') { //WoRMS only
            $this->remapped_terms['http://purl.obolibrary.org/obo/ENVO_01000127'] = 'http://purl.obolibrary.org/obo/ENVO_00000267';
            $this->remapped_terms['http://purl.obolibrary.org/obo/ENVO_00000087'] = 'http://purl.obolibrary.org/obo/ENVO_00000088';
        }
        // */
    }
    private function initialize_delete_mRemarks()
    {
        // if measurementRemarks is any of these, then delete MoF
        $a1 = array('range s', 'ranges', 'range s', 'rang e', 'bamboo', 'barrens', 'breaks', 'mulga', 'chanaral');
        $a2 = array('ridge', 'plateau', 'plateaus', 'crests', 'canyon', 'terrace', 'canyons', 'gullies', 'notches', 'terraces', 'bluff', 'cliffs', 'gulch', 'gully', 'llanos', 'plantations', 'sierra', 'tunnel');
        $a3 = array('chemical product', 'cosmetic product', 'paper product', 'zoological garden', 'world heritage site', 'wildlife management area', 'warehouse', 'vivarium', 'terrarium', 'saline water aquarium', 
        'road cut', 'road', 'populated place', 'plant feed', 'oil spill', 'oil tank', 'oil well', 'oil reservoir', 'oil', 'nature reserve', 'national nature reserve', 'national park', 
        'national wildlife refuge', 'mouth', 'military training area', 'industrial waste', 'geographic feature', 'geothermal field', 'geothermal power plant', 'fresh water aquarium', 'elevation', 
        'bridge', 'blowhole', 'bakery', 'aquarium', 'anthropogenic geographic feature', 'animal habitation', 'air conditioning unit', 'activated sludge', 'agricultural feature');
        $labels = array_merge($a1, $a2, $a3);
        foreach($labels as $label) $this->delete_MoF_with_these_labels[$label] = '';
    }
    private function initialize_mRemark_assignments()
    {
        $mRemarks["open waters"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["open-water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["openwater"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["open water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["dry stream beds"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["dry streambeds"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["dry stream-beds"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["dry stream bed"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["dry streambed"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["coral heads"] = "http://purl.obolibrary.org/obo/ENVO_01000049";
        $mRemarks["coral head"] = "http://purl.obolibrary.org/obo/ENVO_01000049";
        $mRemarks["glades"] = "http://purl.obolibrary.org/obo/ENVO_00000444";
        $mRemarks["glade"] = "http://purl.obolibrary.org/obo/ENVO_00000444";
        $mRemarks["seaway"] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        $mRemarks["tide way"] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        $mRemarks["tideway"] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        $mRemarks["sea-way"] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        $mRemarks["herbaceous areas"] = "http://purl.obolibrary.org/obo/ENVO_01001305";
        $mRemarks["loch"] = "http://purl.obolibrary.org/obo/ENVO_01000252";
        $mRemarks["croplands"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["cropland"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["crop land"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["agricultural regions"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["agricultural region"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["crop-lands"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["cultivated croplands"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["cultivated s"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["crop lands"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["sea vents"] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        $mRemarks["active chimneys"] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        $mRemarks["sea vent"] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        $mRemarks["active chimney"] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        $mRemarks["embayments"] = "http://purl.obolibrary.org/obo/ENVO_00000032";
        $mRemarks["embayment"] = "http://purl.obolibrary.org/obo/ENVO_00000032";
        $mRemarks["brush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["bush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["brushes"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["caatinga"] = "http://purl.obolibrary.org/obo/ENVO_00000883";
        $mRemarks["caatingas"] = "http://purl.obolibrary.org/obo/ENVO_00000883";
        $mRemarks["coniferous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["coniferous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["coniferous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["coniferousforest"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["coniferousforests"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["deciduous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduous-forest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduousforest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduousforests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["equatorial forest"] = "http://purl.obolibrary.org/obo/ENVO_01000220";
        $mRemarks["equatorial forests"] = "http://purl.obolibrary.org/obo/ENVO_01000220";
        $mRemarks["equatorial rain forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["equatorial rain forests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["equatorial rainforest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["equatorial rainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["jungle"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["jungles"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["mallee scrub"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["mangrove forest"] = "http://purl.obolibrary.org/obo/ENVO_01000181";
        $mRemarks["mangrove forests"] = "http://purl.obolibrary.org/obo/ENVO_01000181";
        $mRemarks["mangrove- forest"] = "http://purl.obolibrary.org/obo/ENVO_01000181";
        $mRemarks["monsoon forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["monsoon forests"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["monsoon-forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["mulga scrub"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["pine grove"] = "http://purl.obolibrary.org/obo/ENVO_01000240";
        $mRemarks["pine groves"] = "http://purl.obolibrary.org/obo/ENVO_01000240";
        $mRemarks["pinegrove"] = "http://purl.obolibrary.org/obo/ENVO_01000240";
        $mRemarks["rain forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rain forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rain forests"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rain-forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rain-forests"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rainforest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rainforests"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["sage brush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["sage-brush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["sagebrush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["sagebrushes"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["taiga"] = "http://eol.org/schema/terms/boreal_forests_taiga";
        $mRemarks["taigas"] = "http://eol.org/schema/terms/boreal_forests_taiga";
        $mRemarks["thorn forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["thorn forests"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["thorn-forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["thornforest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["thornforests"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["tropical rain forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rain forests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rain-forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rainforest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropicalrainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";

        $mRemarks["coast"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal areas"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal strip"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal region"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coasts"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal regions"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["costal"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["littoral"] = "http://eol.org/schema/terms/littoralZone";
        $mRemarks["Sea coast"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["forests"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["deciduous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["groves"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["deciduous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["Forest Reserve"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["Forest Reserves"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["open-water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["open water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["rivers"] = "http://purl.obolibrary.org/obo/ENVO_01000253";
        $mRemarks["foothill"] = "http://purl.obolibrary.org/obo/ENVO_00000083";
        $mRemarks["foothills"] = "http://purl.obolibrary.org/obo/ENVO_00000083";
        $mRemarks["palm grove"] = "http://purl.obolibrary.org/obo/ENVO_01000220";
        $mRemarks["glades"] = "http://purl.obolibrary.org/obo/ENVO_00000444";
        $mRemarks["agricultural sites"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["open-water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["open water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";

        $mRemarks["mountains"] = "http://purl.obolibrary.org/obo/ENVO_00000081";
        $mRemarks["nunatak"] = "http://purl.obolibrary.org/obo/ENVO_00000181";

        $mRemarks["hills"] = "http://purl.obolibrary.org/obo/ENVO_00000083";
        $mRemarks["rainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["rainforest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["rain forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["thorn forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["deciduous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["tropical rainforest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rain forests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["deciduous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["tropical rain forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["coniferous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["thorn forests"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["rain-forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["rain forests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["Jungle"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["coniferous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["equatorial forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["monsoon forests"] = "http://purl.obolibrary.org/obo/ENVO_00000879";
        $mRemarks["thornforest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["reforested areas"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        // per https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=64619&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64619 */
        $mRemarks["seamounts"] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
        $mRemarks["seamount"] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
        $mRemarks["seamount chain"] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
        $mRemarks["range of seamounts"] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
        $this->mRemarks = $mRemarks;
    }
    private function initialize_delete_uris()
    {
        $uris = array('http://purl.obolibrary.org/obo/ENVO_00000104', 'http://purl.obolibrary.org/obo/ENVO_00002033', 'http://purl.obolibrary.org/obo/ENVO_00000304', 
        'http://purl.obolibrary.org/obo/ENVO_00000486', 'http://purl.obolibrary.org/obo/ENVO_00002000', 'http://purl.obolibrary.org/obo/ENVO_00000086', 
        'http://purl.obolibrary.org/obo/ENVO_00000220', 'http://purl.obolibrary.org/obo/ENVO_00000113', 'http://purl.obolibrary.org/obo/ENVO_00002232', 
        'http://purl.obolibrary.org/obo/ENVO_02000047', 'http://purl.obolibrary.org/obo/ENVO_00003031', 'http://purl.obolibrary.org/obo/ENVO_00002276', 
        'http://purl.obolibrary.org/obo/ENVO_00000121', 'http://purl.obolibrary.org/obo/ENVO_00000099', 'http://purl.obolibrary.org/obo/ENVO_00000377', 
        'http://purl.obolibrary.org/obo/ENVO_00000165', 'http://purl.obolibrary.org/obo/ENVO_00003903', 'http://purl.obolibrary.org/obo/ENVO_02000054', 
        'http://purl.obolibrary.org/obo/ENVO_00010624', 'http://purl.obolibrary.org/obo/ENVO_01000243', 'http://purl.obolibrary.org/obo/ENVO_01000114', 
        'http://purl.obolibrary.org/obo/ENVO_00003885', 'http://purl.obolibrary.org/obo/ENVO_00003044', 'http://purl.obolibrary.org/obo/ENVO_00000369', 
        'http://purl.obolibrary.org/obo/ENVO_00000158', 'http://purl.obolibrary.org/obo/ENVO_00000526', 'http://purl.obolibrary.org/obo/ENVO_02000058', 
        'http://purl.obolibrary.org/obo/ENVO_00002169', 'http://purl.obolibrary.org/obo/ENVO_00002206', 'http://purl.obolibrary.org/obo/ENVO_00002026', 
        'http://purl.obolibrary.org/obo/ENVO_00002170', 'http://purl.obolibrary.org/obo/ENVO_00000272', 'http://purl.obolibrary.org/obo/ENVO_00002116', 
        'http://purl.obolibrary.org/obo/ENVO_00002186', 'http://purl.obolibrary.org/obo/ENVO_00000293', 'http://purl.obolibrary.org/obo/ENVO_00000223', 
        'http://purl.obolibrary.org/obo/ENVO_00000514', 'http://purl.obolibrary.org/obo/ENVO_2000001', 'http://purl.obolibrary.org/obo/ENVO_00000320', 
        'http://purl.obolibrary.org/obo/ENVO_02000006', 'http://purl.obolibrary.org/obo/ENVO_00000474', 'http://purl.obolibrary.org/obo/ENVO_00000523', 
        'http://purl.obolibrary.org/obo/ENVO_00000074', 'http://purl.obolibrary.org/obo/ENVO_00000309', 'http://purl.obolibrary.org/obo/ENVO_00000037', 
        'http://purl.obolibrary.org/obo/ENVO_00002158', 'http://purl.obolibrary.org/obo/ENVO_00000291', 'http://purl.obolibrary.org/obo/ENVO_00003064', 
        'http://purl.obolibrary.org/obo/ENVO_00000449', 'http://purl.obolibrary.org/obo/ENVO_01000136', 'http://purl.obolibrary.org/obo/ENVO_00010506', 
        'http://purl.obolibrary.org/obo/ENVO_00002020', 'http://purl.obolibrary.org/obo/ENVO_00002027', 'http://purl.obolibrary.org/obo/ENVO_00000114', 
        'http://purl.obolibrary.org/obo/ENVO_00000294', 'http://purl.obolibrary.org/obo/ENVO_00000295', 'http://purl.obolibrary.org/obo/ENVO_00000471', 
        'http://purl.obolibrary.org/obo/ENVO_00000443', 'http://purl.obolibrary.org/obo/ENVO_00002002', 'http://purl.obolibrary.org/obo/ENVO_00000411', 
        'http://purl.obolibrary.org/obo/ENVO_00002164', 'http://purl.obolibrary.org/obo/ENVO_00002983', 'http://purl.obolibrary.org/obo/ENVO_00000011', 
        'http://purl.obolibrary.org/obo/ENVO_00000050', 'http://purl.obolibrary.org/obo/ENVO_00000131', 'http://purl.obolibrary.org/obo/ENVO_00002168', 
        'http://purl.obolibrary.org/obo/ENVO_00000340', 'http://purl.obolibrary.org/obo/ENVO_00005780', 'http://purl.obolibrary.org/obo/ENVO_00002041', 
        'http://purl.obolibrary.org/obo/ENVO_00002171', 'http://purl.obolibrary.org/obo/ENVO_00002028', 'http://purl.obolibrary.org/obo/ENVO_00002023', 
        'http://purl.obolibrary.org/obo/ENVO_00002025', 'http://purl.obolibrary.org/obo/ENVO_00003859', 'http://purl.obolibrary.org/obo/ENVO_00000468', 
        'http://purl.obolibrary.org/obo/ENVO_02000000', 'http://purl.obolibrary.org/obo/ENVO_00000098', 'http://purl.obolibrary.org/obo/ENVO_00000174', 
        'http://purl.obolibrary.org/obo/ENVO_00000311', 'http://purl.obolibrary.org/obo/ENVO_00000424', 'http://purl.obolibrary.org/obo/ENVO_00000391', 
        'http://purl.obolibrary.org/obo/ENVO_00000533', 'http://purl.obolibrary.org/obo/ENVO_00000178', 'http://purl.obolibrary.org/obo/ENVO_00000066', 
        'http://purl.obolibrary.org/obo/ENVO_01000057', 'http://purl.obolibrary.org/obo/ENVO_01000066', 'http://purl.obolibrary.org/obo/ENVO_00000509', 
        'http://purl.obolibrary.org/obo/ENVO_00000427', 'http://purl.obolibrary.org/obo/ENVO_00010621', 'http://purl.obolibrary.org/obo/ENVO_01000207', 
        'http://purl.obolibrary.org/obo/ENVO_00002035', 'http://purl.obolibrary.org/obo/ENVO_00010442', 'http://purl.obolibrary.org/obo/ENVO_00000076', 
        'http://purl.obolibrary.org/obo/ENVO_00001996', 'http://purl.obolibrary.org/obo/ENVO_00000003', 'http://purl.obolibrary.org/obo/ENVO_00000180', 
        'http://purl.obolibrary.org/obo/ENVO_00000477', 'http://purl.obolibrary.org/obo/ENVO_00000414', 'http://purl.obolibrary.org/obo/ENVO_00000359', 
        'http://purl.obolibrary.org/obo/ENVO_00000048', 'http://purl.obolibrary.org/obo/ENVO_00005804', 'http://purl.obolibrary.org/obo/ENVO_00005805', 
        'http://purl.obolibrary.org/obo/ENVO_2000006', 'http://purl.obolibrary.org/obo/ENVO_02000004', 'http://purl.obolibrary.org/obo/ENVO_00002271', 
        'http://purl.obolibrary.org/obo/ENVO_00000480', 'http://purl.obolibrary.org/obo/ENVO_00002139', 'http://purl.obolibrary.org/obo/ENVO_00000305', 
        'http://purl.obolibrary.org/obo/ENVO_00000134', 'http://purl.obolibrary.org/obo/ENVO_00002984', 'http://purl.obolibrary.org/obo/ENVO_00000191', 
        'http://purl.obolibrary.org/obo/ENVO_00000339', 'http://purl.obolibrary.org/obo/ENVO_00003860', 'http://purl.obolibrary.org/obo/ENVO_00000481', 
        'http://purl.obolibrary.org/obo/ENVO_00002214', 'http://purl.obolibrary.org/obo/ENVO_00000358', 'http://purl.obolibrary.org/obo/ENVO_00000302', 
        'http://purl.obolibrary.org/obo/ENVO_00001995', 'http://purl.obolibrary.org/obo/ENVO_00000022', 'http://purl.obolibrary.org/obo/ENVO_01000017', 
        'http://purl.obolibrary.org/obo/ENVO_00002055', 'http://purl.obolibrary.org/obo/ENVO_00004638', 'http://purl.obolibrary.org/obo/ENVO_00003930', 
        'http://purl.obolibrary.org/obo/ENVO_00000092', 'http://purl.obolibrary.org/obo/ENVO_00002016', 'http://purl.obolibrary.org/obo/ENVO_00002018', 
        'http://purl.obolibrary.org/obo/ENVO_00003043', 'http://purl.obolibrary.org/obo/ENVO_00002056', 'http://purl.obolibrary.org/obo/ENVO_00000403', 
        'http://purl.obolibrary.org/obo/ENVO_00003030', 'http://purl.obolibrary.org/obo/ENVO_00000539', 'http://purl.obolibrary.org/obo/ENVO_01000016', 
        'http://purl.obolibrary.org/obo/ENVO_00000361', 'http://purl.obolibrary.org/obo/ENVO_00002044', 'http://purl.obolibrary.org/obo/ENVO_00000393', 
        'http://purl.obolibrary.org/obo/ENVO_00000027', 'http://purl.obolibrary.org/obo/ENVO_00000419', 'http://purl.obolibrary.org/obo/ENVO_00000331', 
        'http://purl.obolibrary.org/obo/ENVO_00000330', 'http://purl.obolibrary.org/obo/ENVO_00000394', 'http://purl.obolibrary.org/obo/ENVO_00010504', 
        'http://purl.obolibrary.org/obo/ENVO_00000543', 'http://purl.obolibrary.org/obo/ENVO_00003323', 'http://purl.obolibrary.org/obo/ENVO_00003096', 
        'http://purl.obolibrary.org/obo/ENVO_02000001', 'http://purl.obolibrary.org/obo/ENVO_00000122', 'http://purl.obolibrary.org/obo/ENVO_00000499', 
        'http://purl.obolibrary.org/obo/ENVO_00000094', 'http://purl.obolibrary.org/obo/ENVO_00002264', 'http://purl.obolibrary.org/obo/ENVO_00002272', 
        'http://purl.obolibrary.org/obo/ENVO_00002001', 'http://purl.obolibrary.org/obo/ENVO_00002043', 'http://purl.obolibrary.org/obo/ENVO_00000029', 
        'http://purl.obolibrary.org/obo/ENVO_00000547', 'http://purl.obolibrary.org/obo/ENVO_00000292', 'http://purl.obolibrary.org/obo/ENVO_00000421', 
        'http://purl.obolibrary.org/obo/ENVO_00000043', 'http://purl.obolibrary.org/obo/ENVO_00000409', 'http://purl.obolibrary.org/obo/ENVO_00002040', 
        'http://purl.obolibrary.org/obo/ENVO_00001998', 'http://purl.obolibrary.org/obo/ENVO_00000376', 'http://purl.obolibrary.org/obo/ENVO_00002152', 
        'http://purl.obolibrary.org/obo/ENVO_00002123', 'http://purl.obolibrary.org/obo/ENVO_00000530', 'http://purl.obolibrary.org/obo/ENVO_00000564', 
        'http://purl.obolibrary.org/obo/ENVO_00002277', 'http://purl.obolibrary.org/obo/ENVO_00000438', 'http://purl.obolibrary.org/obo/ENVO_2000004',     
        'http://purl.obolibrary.org/obo/ENVO_00000367', 'http://purl.obolibrary.org/obo/ENVO_00000363', 'http://purl.obolibrary.org/obo/ENVO_00000305', 
        'http://purl.obolibrary.org/obo/ENVO_00000358', 'http://purl.obolibrary.org/obo/ENVO_00000064', 'http://purl.obolibrary.org/obo/ENVO_00000515', 
        'http://purl.obolibrary.org/obo/ENVO_01000246', 'http://purl.obolibrary.org/obo/ENVO_00010622', 'http://purl.obolibrary.org/obo/ENVO_00010625', 
        'http://purl.obolibrary.org/obo/ENVO_00002000', 'http://purl.obolibrary.org/obo/ENVO_00000376', 'http://purl.obolibrary.org/obo/ENVO_00000011', 
        'http://purl.obolibrary.org/obo/ENVO_00000291', 'http://purl.obolibrary.org/obo/ENVO_00002277', 'http://purl.obolibrary.org/obo/ENVO_00000393', 
        'http://purl.obolibrary.org/obo/ENVO_00000547', 'http://purl.obolibrary.org/obo/ENVO_01000243', 'http://purl.obolibrary.org/obo/ENVO_00000514', 
        'http://purl.obolibrary.org/obo/ENVO_00000533', 'http://purl.obolibrary.org/obo/ENVO_00000104', 'http://purl.obolibrary.org/obo/ENVO_00000320', 
        'http://purl.obolibrary.org/obo/ENVO_00000220', 'http://purl.obolibrary.org/obo/ENVO_00000029', 'http://purl.obolibrary.org/obo/ENVO_00000293', 
        'http://purl.obolibrary.org/obo/ENVO_00000174', 'http://purl.obolibrary.org/obo/ENVO_00000480', 'http://purl.obolibrary.org/obo/ENVO_00004638', 
        'http://purl.obolibrary.org/obo/ENVO_00002139', 'http://purl.obolibrary.org/obo/ENVO_00000477', 'http://purl.obolibrary.org/obo/ENVO_2000001', 
        'http://purl.obolibrary.org/obo/ENVO_00000331', 'http://purl.obolibrary.org/obo/ENVO_00000292', 'http://purl.obolibrary.org/obo/ENVO_01000016', 
        'http://purl.obolibrary.org/obo/ENVO_00000499', 'http://purl.obolibrary.org/obo/ENVO_00000427', 'http://purl.obolibrary.org/obo/ENVO_00002041', 
        'http://purl.obolibrary.org/obo/ENVO_00000294', 'http://purl.obolibrary.org/obo/ENVO_00000122', 'http://purl.obolibrary.org/obo/ENVO_00010624', 
        'http://purl.obolibrary.org/obo/ENVO_00002271', 'http://purl.obolibrary.org/obo/ENVO_00002026', 'http://purl.obolibrary.org/obo/ENVO_00000302', 
        'http://purl.obolibrary.org/obo/ENVO_00000550', 'http://purl.obolibrary.org/obo/ENVO_00000178', 'http://purl.obolibrary.org/obo/ENVO_00000480', 
        'http://purl.obolibrary.org/obo/ENVO_00000086', 'http://purl.obolibrary.org/obo/ENVO_00002055', 'http://purl.obolibrary.org/obo/ENVO_01000047',
        'http://purl.obolibrary.org/obo/ENVO_2000000', 'http://purl.obolibrary.org/obo/ENVO_00003893', 'http://purl.obolibrary.org/obo/ENVO_00003895', 'http://purl.obolibrary.org/obo/ENVO_00010625', 
        'http://purl.obolibrary.org/obo/ENVO_00000375', 'http://purl.obolibrary.org/obo/ENVO_00000374', 'http://purl.obolibrary.org/obo/ENVO_00003963', 'http://purl.obolibrary.org/obo/ENVO_00010622', 
        'http://purl.obolibrary.org/obo/ENVO_00000349', 'http://purl.obolibrary.org/obo/ENVO_00002197', 'http://purl.obolibrary.org/obo/ENVO_00000515', 'http://purl.obolibrary.org/obo/ENVO_00000064', 
        'http://purl.obolibrary.org/obo/ENVO_00000062', 'http://purl.obolibrary.org/obo/ENVO_02000055', 'http://purl.obolibrary.org/obo/ENVO_00002061', 'http://purl.obolibrary.org/obo/ENVO_00002183', 
        'http://purl.obolibrary.org/obo/ENVO_01000003', 'http://purl.obolibrary.org/obo/ENVO_00002185', 'http://purl.obolibrary.org/obo/ENVO_00002985', 'http://purl.obolibrary.org/obo/ENVO_00000363', 
        'http://purl.obolibrary.org/obo/ENVO_00000366', 'http://purl.obolibrary.org/obo/ENVO_00000367', 'http://purl.obolibrary.org/obo/ENVO_00000364', 'http://purl.obolibrary.org/obo/ENVO_00000479', 
        'http://purl.obolibrary.org/obo/ENVO_00000561', 'http://purl.obolibrary.org/obo/ENVO_00002267', 'http://purl.obolibrary.org/obo/ENVO_00000000', 'http://purl.obolibrary.org/obo/ENVO_00000373', 
        'http://purl.obolibrary.org/obo/ENVO_00002215', 'http://purl.obolibrary.org/obo/ENVO_00002198', 'http://purl.obolibrary.org/obo/ENVO_00000176', 'http://purl.obolibrary.org/obo/ENVO_00000075', 
        'http://purl.obolibrary.org/obo/ENVO_00000168', 'http://purl.obolibrary.org/obo/ENVO_00003864', 'http://purl.obolibrary.org/obo/ENVO_00002196', 'http://purl.obolibrary.org/obo/ENVO_00000002', 
        'http://purl.obolibrary.org/obo/ENVO_00005803', 'http://purl.obolibrary.org/obo/ENVO_00002874', 'http://purl.obolibrary.org/obo/ENVO_00002046', 'http://purl.obolibrary.org/obo/ENVO_00000077');
        foreach($uris as $uri)                              $this->delete_MoF_with_these_uris[$uri] = '';
        foreach($this->remove_across_all_resources as $uri) $this->delete_MoF_with_these_uris[$uri] = ''; //remove cloud, cut for all resources

        // commented so far: https://eol-jira.bibalex.org/browse/DATA-1713?focusedCommentId=65447&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65447
        /* another set of excluded URIs. From Jen (AntWeb): https://eol-jira.bibalex.org/browse/DATA-1713?focusedCommentId=65443&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65443
        $str = file_get_contents($this->another_set_exclude_URIs);
        $arr = explode("\n", $str);
        $arr = array_map('trim', $arr);
        // print_r($arr); exit("\n".count($arr)."\n");
        foreach($arr as $uri) $this->delete_MoF_with_these_uris[$uri] = '';
        */
        
        // /* 
        $to_delete_sources = array($this->another_set_exclude_URIs_02,  //Jen: "I've found a bunch more measurementValue terms we should ALWAYS remove." : https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65451&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65451
                                   $this->another_set_exclude_URIs_03); //Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65780&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65780
        foreach($to_delete_sources as $source) {
            $str = file_get_contents($source);
            $arr = explode("\n", $str);
            $arr = array_map('trim', $arr);
            $arr = array_filter($arr); //remove null arrays
            $arr = array_unique($arr); //make unique
            $arr = array_values($arr); //reindex key
            // print_r($arr); exit("\n".count($arr)."\n");
            foreach($arr as $uri) $this->delete_MoF_with_these_uris[$uri] = '';
        }
        // */
        
        // /* for WoRMS only: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65471&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65471
        // http://purl.obolibrary.org/obo/ENVO_00000182 (plateau) => discard. This could mean a few different things
        if($this->param['resource_id'] == '26_ENV') { //WoRMS only
            $this->delete_MoF_with_these_uris['http://purl.obolibrary.org/obo/ENVO_00000182'] = '';
        }
        // */

        // /* remove list of labels
        $str = file_get_contents($this->labels_to_remove_file);
        $arr = explode("\n", $str);
        $arr = array_map('trim', $arr);
        foreach($arr as $label) if($label) $this->labels_to_remove[$label] = '';
        // print_r($this->labels_to_remove); exit("\nlabels_to_remove: ".count($this->labels_to_remove)."\n");
        // */        
    }
    private function filter_out_from_entities()
    {   //from: https://eol-jira.bibalex.org/browse/DATA-1858?focusedCommentId=65359&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65359
        return array('ENVO_00000026', 'ENVO_01000342', 'ENVO_00000241', 'ENVO_01000001', 'ENVO_00002982', 'ENVO_01000628', 'ENVO_00002053', 'ENVO_00000014', 'ENVO_01000018', 'ENVO_00000167', 
        'ENVO_00002007', 'ENVO_00000856', 'ENVO_00000084', 'ENVO_00000040', 'ENVO_00000083', 'ENVO_01000155', 'ENVO_00000078', 'ENVO_00000444', 'ENVO_00000025', 'ENVO_00000032', 'ENVO_00002008', 
        'ENVO_00000495', 'ENVO_00000101', 'ENVO_00002015', 'ENVO_00000255', 'ENVO_00002054', 'ENVO_00000418', 'ENVO_00000463', 'ENVO_00000247', 'ENVO_01000236', 'ENVO_00000284', 'ENVO_00002034', 
        'ENVO_00000439', 'ENVO_00000115', 'ENVO_00000381', 'ENVO_00000133', 'ENVO_01000005', 'ENVO_00002140', 'ENVO_00000231', 'ENVO_00000166', 'ENVO_00012408', 'ENVO_00010505', 'ENVO_00002226', 
        'ENVO_00000235', 'ENVO_00000275', 'ENVO_00002870', 'ENVO_00000475', 'ENVO_00002269', 'ENVO_00000138', 'ENVO_01000158', 'ENVO_00000195', 'ENVO_00001997', 'ENVO_02000059', 'ENVO_00000440', 
        'ENVO_00002013', 'ENVO_00000102', 'ENVO_00005792', 'ENVO_00000298', 'ENVO_00010358', 'ENVO_01000002', 'ENVO_01000006', 'ENVO_00000085', 'ENVO_00000163', 'ENVO_00000520', 'ENVO_00002118', 
        'ENVO_00002144', 'ENVO_00003982', 'ENVO_00000149', 'ENVO_00000110', 'ENVO_00000313', 'ENVO_00000429', 'ENVO_00000500', 'ENVO_00000236', 'ENVO_00000245', 'ENVO_00005754', 'ENVO_00000422', 
        'ENVO_00000535', 'ENVO_00000120', 'ENVO_00000155', 'ENVO_01000019', 'ENVO_00000069', 'ENVO_00000139', 'ENVO_00000145', 'ENVO_00000473', 'ENVO_00000534', 'ENVO_00005742', 'ENVO_00005747', 
        'ENVO_00000072', 'ENVO_00000287', 'ENVO_00000400', 'ENVO_00000496', 'ENVO_00000497', 'ENVO_00000544', 'ENVO_00002270', 'ENVO_00000036', 'ENVO_00000119', 'ENVO_00000140', 'ENVO_00000157', 
        'ENVO_00000256', 'ENVO_00002063', 'ENVO_00003041', 'ENVO_00005799', 'ENVO_01000063', 'ENVO_00000042', 'ENVO_00000079', 'ENVO_00000152', 'ENVO_00000160', 'ENVO_00000252', 'ENVO_00000271', 
        'ENVO_00000282', 'ENVO_00000289', 'ENVO_00000290', 'ENVO_00000470', 'ENVO_00000483', 'ENVO_00000522', 'ENVO_00000548', 'ENVO_00002231', 'ENVO_00005739', 'ENVO_00005756', 'ENVO_00005767', 
        'ENVO_00005775', 'ENVO_01000219', 'ENVO_02000084');
    }
    public function get_descendants_of_habitat_group($what)
    {
        $url = $this->descendants_habitat_group[$what];
        $local = Functions::save_remote_file_to_local($url, array('cache' => 1, 'expire_seconds' => 60*60*24));
        $arr = explode("\n", file_get_contents($local));
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        unlink($local);
        foreach($arr as $uri) $final[$uri] = '';
        // print_r($final); exit("\n\n");
        return $final;
    }
    private function test_is_passed()
    {
        $desc = "I live in a valley in Northern Philippines";
        $ontology = "envo";
        $uri = str_replace("MY_DESC", urlencode($desc), $this->pensoft_service);
        $uri = str_replace("MY_ONTOLOGIES", $ontology, $uri);
        $json = Functions::lookup_with_cache($uri, array('expire_seconds' => 60));
        $arr = json_decode($json, true); //print_r($arr); exit;
        /*Array(
            [data] => Array(
                    [0] => Array(
                            [id] => http://purl.obolibrary.org/obo/ENVO_00000100
                            [lbl] => valley
                            [context] => I live in a <b>valley</b> in Northern Philippines
                            ...
        */
        if($arr['data'][0]['id'] == 'http://purl.obolibrary.org/obo/ENVO_00000100' && $arr['data'][0]['lbl'] == 'valley') return true;
        return false;
    }
}
?>