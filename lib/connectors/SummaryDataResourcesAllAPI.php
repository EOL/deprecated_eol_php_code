<?php
namespace php_active_record;
/* [SDR_all.php] */
class SummaryDataResourcesAllAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        /*
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */
        $this->download_options = array('resource_id' => 'SDR_all', 'timeout' => 60*5, 'expire_seconds' => 60*60*24, 'cache' => 1, 'download_wait_time' => 1000000);
        $this->debug = array();
        
        /* Terms relationships -> https://opendata.eol.org/dataset/terms-relationships */
        /* not used at the moment:
        $this->file['parent child']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/f8036c30-f4ab-4796-8705-f3ccd20eb7e9/download/parent-child-aug-16-2.csv";
        $this->file['parent child']['path'] = "http://localhost/cp/summary data resources/parent-child-aug-16-2.csv";
        */
        $this->file['parent child']['fields'] = array('parent', 'child'); //used more simple words instead of: array('parent_term_URI', 'subclass_term_URI');
        
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-aug-16-1-2.csv";
        $this->file['preferred synonym']['path'] = "http://localhost/cp/summary data resources/preferredsynonym-aug-16-1-2-3.csv";
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-sept-27.csv";
        $this->file['preferred synonym']['fields'] = array('preferred', 'deprecated'); //used simple words instead of: array('preferred_term_URI', 'deprecated_term_URI');

        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child.csv"; 
        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child-6-1.csv"; 
        $this->file['parent child']['path_habitat'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/c5ff5c62-a2ef-44be-9f59-88cd99bc8af2/download/habitat-parent-child-6-1.csv";
        
        $this->file['parent child']['path_geoterms'] = "http://localhost/cp/summary data resources/geoterms-parent-child.csv";
        $this->file['parent child']['path_geoterms'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/e1dcb51b-9a03-4069-b5bf-e18b6bc15798/download/geoterms-parent-child-1.csv";
        
        // $this->dwca_file = "http://localhost/cp/summary data resources/carnivora_sample.tgz";
        $this->dwca_file = "http://localhost/cp/summary_data_resources/traits_all_201905.zip";
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . '/sample.txt';
        $this->temp_file = CONTENT_RESOURCE_LOCAL_PATH . '/temp.txt';
        
        if(Functions::is_production())  $this->working_dir = "/extra/summary data resources/page_ids/";
        else{
                                        // $this->working_dir = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/";
                                        $this->working_dir = "/Volumes/AKiTiO4/web/cp/summary_data_resources/page_ids/";
        }
        /* seems not used as all
        $this->jen_isvat = "/Volumes/AKiTiO4/web/cp/summary data resources/2018 09 08/jen_isvat.txt";
        */
        
        //for taxon summary
        /*
        if(Functions::is_production())  $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/b534cd22-d904-45e4-b0e2-aaf06cc0e2d6/download/eoldynamichierarchyv1revised.zip";
        else                            $this->EOL_DH = "http://localhost/cp/summary data resources/eoldynamichierarchyv1.zip";
        */
        if(Functions::is_production())  $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/bac4e11c-28ab-4038-9947-02d9f1b0329f/download/eoldynamichierarchywithlandmarks.zip";
        else                            $this->EOL_DH = "http://localhost/cp/summary data resources/DH/eoldynamichierarchywithlandmarks.zip";
        
        $this->EOL_DH = "http://localhost/cp/summary%20data%20resources/DH/eoldynamichierarchywithlandmarks.zip";
        $this->lifeState_statMeth_resource_file = CONTENT_RESOURCE_LOCAL_PATH . '/lifeStage_statMeth_resource.txt';
        
        $this->parentModeYN = false;
        $this->fullref = array();
        
        /* ------------------ NEW June 4, 2019 ------------------ */
        $this->main_dir = "/Volumes/AKiTiO4/web/cp/summary_data_resources/";
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    /*
    basal values
    parent basal values
    *write resource file: basal values
    write resource file: parent basal values
    
    taxon summary
    *parent taxon summary
    write resource file: taxon summary
    write resource file: parent taxon summary
    */
    private function generate_children_of_taxa_using_parentsCSV()
    {
        $file = fopen($this->main_paths['archive_path'].'/parents.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [child] => 47054812
                    [parent] => 7662
                )*/
                $child = $rec['child']; $parent = $rec['parent'];
                $children_of[$parent][$child] = '';
            }
        }
        foreach($children_of as $parent => $children) $final[$parent] = array_keys($children);
        $this->CSV_children_of = $final;
    }
    function generate_children_of_taxa_usingDH()
    {
        self::parse_DH(); self::initialize();
        self::gen_children_of_taxon_usingDH();
    }
    function print_parent_basal_values()
    {
        self::initialize_basal_values(); self::generate_children_of_taxa_using_parentsCSV();
        $predicates = self::get_summ_process_type_given_pred('opposite', 'parents!A2:C1000', 2, 'basal value'); print_r($predicates);
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH();

        $resource_id = 'parent_basal_values'; $WRITE = self::start_write2DwCA($resource_id, 'BV');

        foreach($predicates as $predicate) {
            foreach($page_ids as $page_id => $taxon) {
                // print_r($taxon); exit;
                // Array(
                //     [taxonRank] => order
                //     [Landmark] => 2
                // )
                if(!$page_id) continue;
                if(!@$taxon['taxonRank']) continue;
                if(@$taxon['taxonRank'] != "species" && $taxon['Landmark'] || @$taxon['taxonRank'] == "family") {
                    
                    $this->original_nodes_parent = array(); //initialize for every 'parent basal values' process
                    if($ret = self::main_parents_basal_values($page_id, $predicate)) {
                        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                        self::write_resource_file_BasalValues($ret, $WRITE, 'parent');
                    }
                }
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        print_r($this->debug);
        echo("\n-- end parents basal values --\n");
    }
    function test_parent_taxon_summary()
    {
        $this->parentModeYN = true;
        self::parse_DH(); self::initialize(); self::generate_children_of_taxa_using_parentsCSV();

        // $input[] = array('page_id' => 7662, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats -> orig test case
        // $input[] = array('page_id' => 4528789, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats
        $input[] = array('page_id' => 7672, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats //test case by Jen during dev. https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=62848&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62848
        // $input[] = array('page_id' => 7665, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats

        $resource_id = 'test_parent_taxon_summary'; $WRITE = self::start_write2DwCA($resource_id, 'TS');

        foreach($input as $i) {
            $page_id = $i['page_id']; $predicate = $i['predicate'];
            $this->taxon_summary_parent_recs = array(); $this->ISVAT_TS = array();
            if($ret = self::main_parents_taxon_summary($page_id, $predicate)) {
                $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                echo "\n\nFinal result (parent taxon summary):"; print_r($ret);
                self::write_resource_file_TaxonSummary($ret, $WRITE, 'parent');
            }
        }
        fclose($WRITE); self::end_write2DwCA(); print_r($this->debug);
        echo("\n-- end method: parents: taxon summary --\n");
    }
    function print_parent_taxon_summary()
    {
        $this->parentModeYN = true;
        self::parse_DH(); self::initialize();
        self::generate_children_of_taxa_using_parentsCSV();
        $predicates = self::get_summ_process_type_given_pred('opposite', 'parents!A2:C1000', 2, 'taxon summary'); print_r($predicates);
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH();

        $resource_id = 'parent_taxon_summary'; $WRITE = self::start_write2DwCA($resource_id, 'TS');

        foreach($predicates as $predicate) {
            foreach($page_ids as $page_id => $taxon) {
                // print_r($taxon); exit;
                // Array(
                //     [taxonRank] => order
                //     [Landmark] => 2
                // )
                if(!$page_id) continue;
                if(!@$taxon['taxonRank']) continue;
                if(@$taxon['taxonRank'] != "species" && $taxon['Landmark'] || @$taxon['taxonRank'] == "family") {
                    $this->taxon_summary_parent_recs = array(); $this->ISVAT_TS = array();
                    if($ret = self::main_parents_taxon_summary($page_id, $predicate)) {
                        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                        echo "\n\nFinal result (parent taxon summary):"; print_r($ret);
                        self::write_resource_file_TaxonSummary($ret, $WRITE, 'parent');
                    }
                }
            }
        }
        fclose($WRITE); self::end_write2DwCA(); print_r($this->debug);
        echo("\n-end print parent taxon summary-\n");
    }
    function print_basal_values()
    {
        //step 1: get all 'basal values' predicates:
        /*
        $predicates = self::get_summ_process_type_given_pred('opposite', 'predicates!A2:F1000', 5, 'basal values'); print_r($predicates);
        [0] => http://eol.org/schema/terms/Present
        [1] => http://eol.org/schema/terms/Habitat
        [2] => http://purl.obolibrary.org/obo/FLOPO_0900032
        */
        // $predicates = array('http://eol.org/schema/terms/Present'); $resource_id = 'basal_values_Present'; 
        $predicates = array('http://eol.org/schema/terms/Habitat'); $resource_id = 'basal_values_Habitat'; 

        $WRITE = self::start_write2DwCA($resource_id, 'BV');
        self::initialize_basal_values();
        /* removed. Moved below
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH();
        */

        /* for indicator */
        $total_predicates = count($predicates); $cnt_predicate = 0;

        foreach($predicates as $predicate) {
            $cnt_predicate++; /* for indicator */
            
            echo "\nGet page_ids for...[$predicate]\n";
            $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH(array($predicate));
            $total_page_ids = count($page_ids); $cnt_page_id = 0;
            
            foreach($page_ids as $page_id => $taxon) {
                /* for indicator */
                $cnt_page_id++;
                echo "\nPredicates $cnt_predicate of $total_predicates";
                echo "\nPage IDs $cnt_page_id of $total_page_ids\n";
                
                if(!$page_id) continue;
                if(@$taxon['taxonRank'] == "species") {
                    if($ret = self::main_basal_values($page_id, $predicate)) {
                        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                        self::write_resource_file_BasalValues($ret, $WRITE, 'non-parent');
                    }
                }
            }
        }
        fclose($WRITE); self::end_write2DwCA(); print_r($this->debug);
        echo("\n-end print resource files (Basal values)-\n");
    }
    function print_taxon_summary()
    {
        $this->parentModeYN = false;
        //step 1: get all 'taxon summary' predicates:
        $predicates = self::get_summ_process_type_given_pred('opposite', 'predicates!A2:F1000', 5, 'taxon summary'); echo "\nPredicates: ".count($predicates)."\n";
        self::initialize(); 
        /* removed bec it is getting page_ids without predicate in question. Moved below.
        echo "\nGet page_ids...\n";
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH($predicates);
        */
        //--------initialize start
        echo "\nparse DH...\n";
        self::parse_DH();
        $resource_id = 'taxon_summary'; $WRITE = self::start_write2DwCA($resource_id, 'TS');

        // $predicates = array('http://purl.obolibrary.org/obo/RO_0002470'); //debug only force assign
        /* for indicator */
        $total_predicates = count($predicates); $cnt_predicate = 0;

        foreach($predicates as $predicate) {
            $cnt_predicate++; $cnt_page_id = 0; /* for indicator */
            
            echo "\nGet page_ids for...[$predicate]\n";
            $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH(array($predicate));
            $total_page_ids = count($page_ids); $cnt_page_id = 0;
            
            foreach($page_ids as $page_id => $taxon) { //print_r($taxon);
                /* for indicator */
                $cnt_page_id++;
                echo "\nPredicates $cnt_predicate of $total_predicates";
                echo "\nPage IDs $cnt_page_id of $total_page_ids\n";
                
                if(!$page_id) continue;
                if(@$taxon['taxonRank'] == "species") {
                    $this->ISVAT_TS = array();
                    if($ret = self::main_taxon_summary($page_id, $predicate)) {
                        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                        echo "\n\nFinal result (taxon summary):"; print_r($ret);
                        self::write_resource_file_TaxonSummary($ret, $WRITE, 'non-parent');
                    }
                }
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        echo("\n-end print taxon summary-\n");
    }
    function print_lifeStage_statMeth()
    {
        //step 1: get all 'lifestage and statistical method' predicates:
        $predicates = self::get_summ_process_type_given_pred('opposite', 'predicates!A2:F1000', 5, 'lifestage and statistical method'); //3rd param is $item index no.
        self::initialize();
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH();
        //--------initialize start
        self::parse_DH();
        //write to file
        if(!($WRITE = Functions::file_open($this->lifeState_statMeth_resource_file, "w"))) return;
        $row = array("Page ID", 'eol_pk', "Predicate", "Label");
        fwrite($WRITE, implode("\t", $row). "\n");
        foreach($predicates as $predicate) {
            foreach($page_ids as $page_id => $taxon) {
                //print_r($taxon);
                if(!$page_id) continue;
                if(@$taxon['taxonRank'] == "species") {
                    if($ret = self::main_lifestage_statMeth($page_id, $predicate)) {
                        $row = array($page_id, $ret['recs'][0]['eol_pk'], $predicate, $ret['label']);
                        fwrite($WRITE, implode("\t", $row). "\n");
                    }
                }
            }
        }
        fclose($WRITE);
        echo("\n-end print resource files (lifestage+statMeth)-\n");
    }
    function test_parent_basal_values()
    {
        // { folder test case is [2018 10 02 basal values parent]}  ============================================================================================================
        self::initialize_basal_values(); self::generate_children_of_taxa_using_parentsCSV();
        // self::parse_DH(); //seems not needed here...?
        
        // $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Habitat"); //habitat includes -> orig test case
        // $input[] = array('page_id' => 7673, 'predicate' => "http://eol.org/schema/terms/Habitat"); //habitat includes -> questioned by Jen, missing ref under biblio field
        // $input[] = array('page_id' => 7665, 'predicate' => "http://eol.org/schema/terms/Habitat"); //habitat includes -> questioned by Jen, missing ref under biblio field
        // $input[] = array('page_id' => 7666, 'predicate' => "http://eol.org/schema/terms/Habitat"); //habitat includes
        $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Present"); //infinite loop

        $resource_id = 'test_parent_basal_values'; $WRITE = self::start_write2DwCA($resource_id, 'BV');

        foreach($input as $i) {
            $page_id = $i['page_id']; $predicate = $i['predicate'];
            $this->original_nodes_parent = array(); //initialize for every 'parent basal values' process
            if($ret = self::main_parents_basal_values($page_id, $predicate)) {
                echo "\nFinal (parent basal values): "; print_r($ret);
                $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                self::write_resource_file_BasalValues($ret, $WRITE, 'parent');
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        print_r($this->debug);
        echo("\n-- end method: parents: basal values --\n");
    }
    function test_lifeStage_statMeth()
    {
        self::initialize();
        $predicate = "http://purl.obolibrary.org/obo/VT_0001259";
        $page_ids = array(347436, 347438, 46559130);
        // $page_ids = array(328674);

        //write to file
        if(!($WRITE = Functions::file_open($this->lifeState_statMeth_resource_file, "w"))) return;
        $row = array("Page ID", 'eol_pk', "Predicate", "Label");
        fwrite($WRITE, implode("\t", $row). "\n");

        foreach($page_ids as $page_id) {
            $ret = self::main_lifestage_statMeth($page_id, $predicate);
            // print_r($ret); exit;
            $row = array($page_id, $ret['recs'][0]['eol_pk'], $predicate, $ret['label']);
            fwrite($WRITE, implode("\t", $row). "\n");
        }
        fclose($WRITE);
        echo("\n-- end method: lifestage_statMeth --\n");
    }
    function test_taxon_summary()
    {
        $this->parentModeYN = false;
        self::parse_DH(); self::initialize();
        $resource_id = 'test_taxon_summary'; $WRITE = self::start_write2DwCA($resource_id, 'TS');

        // $page_id = 328607; $predicate = "http://purl.obolibrary.org/obo/RO_0002439"; //preys on - no record
        // $page_id = 7673; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        // $page_id = 7662; $predicate = "http://purl.obolibrary.org/obo/RO_0002458"; //preyed upon by
        // $page_id = 46559118; $predicate = "http://purl.obolibrary.org/obo/RO_0002439"; //preys on
        // $page_id = 328607; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        // $page_id = 46559162; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        // $page_id = 46559217; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        // $input[] = array('page_id' => 46559118, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002439"); //preys on
        // $input[] = array('page_id' => 328609, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats
        $input[] = array('page_id' => 328598, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats //test case when writing to DwCA
        
        // $input[] = array('page_id' => 47054812, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats //supposedly no records
        
        foreach($input as $i) {
            $page_id = $i['page_id']; $predicate = $i['predicate'];
            $this->ISVAT_TS = array();
            if($ret = self::main_taxon_summary($page_id, $predicate)) {
                $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                echo "\n\nFinal result (taxon summary):"; print_r($ret);
                self::write_resource_file_TaxonSummary($ret, $WRITE, 'non-parent');
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        echo("\n-- end method: 'taxon summary' --\n");
    }
    function test_basal_values()
    {
        self::initialize_basal_values();
        $resource_id = 'test_basal_values'; $WRITE = self::start_write2DwCA($resource_id, 'BV');
        
        // $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328607, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328682, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328609, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328598, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 4442159, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 46559197, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 46559217, 'predicate' => "http://eol.org/schema/terms/Present");
        
        // $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Habitat"); //first test case     //test case with new 2nd deletion step
        // $input[] = array('page_id' => 328607, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 328682, 'predicate' => "http://eol.org/schema/terms/Habitat");
        $input[] = array('page_id' => 328609, 'predicate' => "http://eol.org/schema/terms/Habitat");                        //test case with new first & second deletion steps
        // $input[] = array('page_id' => 4442159, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 46559197, 'predicate' => "http://eol.org/schema/terms/Habitat");

        // $children = array(328598, 328609, 46559217, 328682, 328607); //force assignment, development only
        // $input[] = array('page_id' => 328598, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 46559154, 'predicate' => "http://eol.org/schema/terms/Habitat"); //reached step 7

        // $input[] = array('page_id' => 46559217, 'predicate' => "http://eol.org/schema/terms/Habitat"); //test case for write resource
        // $input[] = array('page_id' => 7673, 'predicate' => "http://eol.org/schema/terms/Habitat"); //questioned by Jen, missing ref under biblio field

        // $input[] = array('page_id' => 1037781, 'predicate' => "http://eol.org/schema/terms/Present"); //left seems infinite loop
        // $input[] = array('page_id' => 328604, 'predicate' => "http://eol.org/schema/terms/Present"); //left seems infinite loop

        foreach($input as $i) {
            /* temp block
            $this->taxon_ids = array(); $this->reference_ids = array(); $this->occurrence_ids = array();
            
            //write to DwCA
            $this->resource_id = $i['page_id']."_".pathinfo($i['predicate'], PATHINFO_BASENAME);
            $file = CONTENT_RESOURCE_LOCAL_PATH . "/".$this->resource_id."_resource.txt";

            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $this->resource_id . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

            //write to file
            if(!($WRITE = Functions::file_open($file, "w"))) return;
            $row = array("Page ID", 'eol_pk', "Value URI", "Label");
            fwrite($WRITE, implode("\t", $row). "\n");
            */
            
            $page_id = $i['page_id']; $predicate = $i['predicate'];
            if($ret = self::main_basal_values($page_id, $predicate)) {
                $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                print_r($ret);
                self::write_resource_file_BasalValues($ret, $WRITE, 'non-parent');
            }
            
            /* temp block
            fclose($WRITE);
            $this->archive_builder->finalize(TRUE);
            if(file_exists($this->path_to_archive_directory."taxon.tab")) Functions::finalize_dwca_resource($this->resource_id);
            */
        }
        fclose($WRITE); self::end_write2DwCA();
        print_r($this->debug);
        echo("\n-- end method: basal values --\n");
        // */
    }
    private function get_CSV_children_of($page_id)
    {
        $anaks = array();
        $children = @$this->CSV_children_of[$page_id];
        $anaks = array_merge($anaks, $children);
        foreach($children as $child) {
            if($children2 = @$this->CSV_children_of[$child]) $anaks = array_merge($anaks, $children2);
            else continue;
            foreach($children2 as $child2) {
                if($children3 = @$this->CSV_children_of[$child2]) $anaks = array_merge($anaks, $children3);
                else continue;
                foreach($children3 as $child3) {
                    if($children4 = @$this->CSV_children_of[$child3]) $anaks = array_merge($anaks, $children4);
                    else continue;
                    foreach($children4 as $child4) {
                        if($children5 = @$this->CSV_children_of[$child4]) $anaks = array_merge($anaks, $children5);
                        else continue;
                        foreach($children5 as $child5) {
                            if($children6 = @$this->CSV_children_of[$child5]) $anaks = array_merge($anaks, $children6);
                            else continue;
                            foreach($children6 as $child6) {
                                if($children7 = @$this->CSV_children_of[$child6]) $anaks = array_merge($anaks, $children7);
                                else continue;
                                exit("\nreached level 7. May need to extend more.\n");
                            }
                        }
                    }
                }
            }
        }
        $anaks = array_unique($anaks);
        return $anaks;
    }
    function start() //DH total recs 2,724,941
    {
        // /*
        self::initialize(); self::generate_children_of_taxa_using_parentsCSV();
        $main_page_id = 7665; //7662;
        $children = self::get_CSV_children_of($main_page_id); print_r($children);
        // */
        
        /*
        self::initialize();
        self::investigate_traits_csv(); exit;
        */
    }
    private function start_write2DwCA($resource_id, $method)
    {
        $this->resource_id = $resource_id;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $this->resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $file = CONTENT_RESOURCE_LOCAL_PATH . "/".$resource_id."_resource.txt";
        if(!($WRITE = Functions::file_open($file, "w"))) return;
        if($method == 'BV') $row = array("Page ID", 'eol_pk', "Value URI", "Label");
        if($method == 'TS') $row = array("Page ID", 'eol_pk', "object_page_id", "Label");
        fwrite($WRITE, implode("\t", $row). "\n");
        return $WRITE;
    }
    private function end_write2DwCA()
    {
        $this->archive_builder->finalize(TRUE);
        if(file_exists($this->path_to_archive_directory."taxon.tab")) Functions::finalize_dwca_resource($this->resource_id);
    }
    //############################################################################################ start write resource file - method = 'parent taxon summary'
    private function gen_children_of_taxon_usingDH() //started as investigate_DH(). DH total recs 2,724,941
    {
        /*
        $EOLid = 298458; $EOLid = 110698;
        $EOLid = 103449;
        echo " - EOLid: [$EOLid] "; 
        if($anc = self::get_ancestry_via_DH($EOLid, false)) { //2nd param false means that get all ancestry not just landmark taxa
            array_unshift($anc, $EOLid); //prepend $val front of $anc, $val becomes 1st record
            
            // print_r($anc);
            // foreach($anc as $page_id) {
            //     $json_file = self::get_txt_path_by_page_id($page_id, "_c.txt");
            //     echo "\n[$page_id] $json_file";
            // }
            
            self::gen_children_of_taxon_given_ancestry($anc);
        }
        else echo "\nNo ancestry [$val]\n";
        exit("\nstop muna\n");
        */
        
        $info = self::prep_DH(); $i = 0; $m = 2724950/5;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            // if($i == 487377) exit; //debug when developing...
            if($i == 1) $fields = $line;
            else {
                
                // /* breakdown when caching:
                $v = 416466;
                $cont = false;
                if($i >= 1 && $i < $m) $cont = true;
                // if($i >= $m && $i < $m*2) $cont = true;
                // if($i >= $m*2 && $i < $m*3) $cont = true;
                // if($i >= $m*3 && $i < $m*4) $cont = true;
                // if($i >= $m*4 && $i < $m*5) $cont = true;

                // if($i >= 642620 && $i < 642620+$v) $cont = true;
                // if($i >= 642620+$v && $i < 642620+($v*2)) $cont = true;
                // if($i >= 642620+($v*2) && $i < 642620+($v*3)) $cont = true;
                // if($i >= 642620+($v*3) && $i < 642620+($v*4)) $cont = true;
                // if($i >= 642620+($v*4) && $i < 642620+($v*5)) $cont = true;
                
                if(!$cont) continue;
                // */
                
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit;
                // /*
                // $rec['EOLid'] = 298458; //debug force assign
                if($EOLid = $rec['EOLid']) {
                    echo "\n".number_format($i);
                    echo " - EOLid: [$EOLid] "; 
                    if($anc = self::get_ancestry_via_DH($EOLid, false)) { //2nd param false means that get all ancestry not just landmark taxa
                        array_unshift($anc, $EOLid); //prepend $val front of $anc, $val becomes 1st record
                        self::gen_children_of_taxon_given_ancestry($anc);
                        // return; //debug
                    }
                    else echo "\nNo ancestry [$val]\n";
                }
                else echo "\nNo EOLid\n";
                // */
                if(($i % 1000) == 0) echo "\n".number_format($i);
            }
            // if($i > 400000) break; //debug
        }
    }
    private function gen_children_of_taxon_given_ancestry($anc)
    {
        $anc = array_reverse($anc);
        $temp = $anc;
        foreach($anc as $id) {
            array_shift($temp);
            if($temp) self::write_ancestry_to_file($id, $temp);
        }
    }
    private function write_ancestry_to_file($page_id, $children)
    {
        $children = array_filter($children); //lame try to delete unwanted null entries... did this when looking for answers why always ...-append... occurs
        $json_file = self::get_txt_path_by_page_id($page_id, "_c.txt");
        if(file_exists($json_file)) {
            // echo "\n[$page_id] $json_file\n";
            $json = trim(file_get_contents($json_file));
            $arr = json_decode($json, true);
            if(!is_array($arr) && is_null($arr)) $arr = array();
            echo "\nstarting count: ".count($arr)."\n";                 //good debug
            // /* ver 2
            $cont_write = false;
            foreach($children as $child) {
                if(!$child) continue;
                if(!in_array($child, $arr)) {
                    $arr[] = $child;
                    $cont_write = true;
                }
            }
            if($cont_write) { 
                echo "\nending count: ".count($arr)."\n";               //good debug
                echo " -append ";
                // echo "\n[$page_id] $json_file\n";
                $WRITE = fopen($json_file, 'w'); fwrite($WRITE, json_encode($arr)); fclose($WRITE);
            }
            else echo " -no add ";
            // */
        }
        else {
            echo " -new ";
            $WRITE = fopen($json_file, 'w'); fwrite($WRITE, json_encode($children)); fclose($WRITE);
            // echo "\nNEW: writing json [$page_id] [$json_file] [".count($children)."]";
        }
    }
    private function get_summ_process_type_given_pred($order = "normal", $range = 'predicates!A2:F1000', $item_index_no = 5, $filter) //sheet found here: https://docs.google.com/spreadsheets/u/1/d/1Er57xyxT_-EZud3mNkTBn0fZ9yZi_01qtbwwdDkEsA0/edit?usp=sharing
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1Er57xyxT_-EZud3mNkTBn0fZ9yZi_01qtbwwdDkEsA0';
        $params['range']         = $range; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) {
            if($uri = $item[0]) {
                if($order == "normal") $final[$uri] = @$item[$item_index_no];
                elseif($order == "opposite") {
                    if($var = @$item[$item_index_no]) $final[$var][] = $uri;
                }
            }
        }
        return $final[$filter];
    }
    //############################################################################################ start write resource file - method = 'taxon summary'
    private function write_resource_file_TaxonSummary($info, $WRITE, $parentYN) //previously $ret
    {
        $page_id = $info['page_id']; $predicate = $info['predicate'];
        /*step 1: get all eol_pks */
        if($parentYN == "non-parent") $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        elseif($parentYN == "parent") $recs = $this->taxon_summary_parent_recs;
        else exit("\nNot go here...\n");
        
        $found = array(); $existing_records_for_writing = array(); $eol_pks = array();
        foreach($info['Selected'] as $id) {
            foreach($recs as $rec) {
                if($rec['object_page_id'] == $id) {
                    $eol_pks[$rec['eol_pk']] = '';
                    $found[] = $id;
                    //write to file block
                    $row = array($page_id, $rec['eol_pk'], $id, $info['Label']); $existing_records_for_writing[] = $row;
                }
            }
        }
        if($existing_records_for_writing) self::adjust_if_needed_and_write_existing_records($existing_records_for_writing, $WRITE);
        $eol_pks = array_keys($eol_pks);
        echo "\n [$parentYN] Original recs: ".count($recs)."\n";
        if($new_records = array_diff($info['Selected'], $found)) {
            echo "\nTS - Not found in traits.csv. Create new record(s): "; print_r($new_records); //good debug
            /* ver 1
            $refs = self::get_refs_from_metadata_csv($eol_pks); //get refs for new records, same refs for all new records
            self::create_archive_TaxonSummary($new_records, $refs, $info);
            */
            // $this->info = $info;             //for debug only
            // /* ver 2 OK
            $new_records_refs = self::assemble_refs_for_new_recs_TS($new_records, $recs);
            self::create_archive_TaxonSummary($new_records, $new_records_refs, $info);
            // */
        }
        else echo "\nNo new records. Will not write to DwCA.\n";
    }
    //================================================================================================================================= start new scheme
    private function assemble_refs_for_new_recs_TS($new_records, $orig_recs)
    {
        foreach($new_records as $new) {
            $descendants_of[$new] = self::get_from_ISVAT_descendants_of_TS($new);                              //1. get from $this->ISVAT which are descendants of $new
            $eol_pks_of[$new] = self::get_eol_pks_of_new_from_origRecs_TS($orig_recs, $descendants_of[$new]);  //2. get eol_pks from orig recs
            $refs_of[$new] = self::get_refs_from_metadata_csv($eol_pks_of[$new]);                           //3. get refs using eol_pks
        }
        print_r($descendants_of); print_r($eol_pks_of); print_r($refs_of); //good debug
        return $refs_of;
    }
    private function get_from_ISVAT_descendants_of_TS($term) //working well
    {
        $final = array();
        foreach($this->ISVAT_TS as $key => $arr) {
            if(in_array($term, $arr)) $final[$key] = '';
            if($key == $term) $final[$key] = '';
        }
        return array_keys($final);
    }
    private function get_eol_pks_of_new_from_origRecs_TS($recs, $descendants)
    {
        $eol_pks = array();
        foreach($recs as $rec) {
            if(in_array($rec['object_page_id'], $descendants)) {
                $eol_pks[$rec['eol_pk']] = '';
            }
        }
        return array_keys($eol_pks);
    }
    //================================================================================================================================= end new scheme
    private function create_archive_TaxonSummary($new_records, $new_records_refs, $ret)
    {   /*Array(
        [root] => 46557930
        [root label] => PRM
        [Selected] => Array(
                [0] => 46557930
                [1] => 207661
            )
        [Label] => REP
        [page_id] => 328598
        [predicate] => http://purl.obolibrary.org/obo/RO_0002470
        )*/
        $taxon_id = $ret['page_id'];
        $taxon = $this->add_taxon(array('page_id' => $taxon_id));
        $type = pathinfo($ret['predicate'], PATHINFO_BASENAME);

        /* ver 1
        $reference_ids = '';
        if($refs) {
            if($reference_ids = self::create_references($refs)) $reference_ids = implode("; ", $reference_ids);
        }*/

        $occurrence_id = $this->add_occurrence_assoc($taxon_id, "$type"); // used in 'Summary Data Resources' implementation. Not the strategy used in EOL Associations
        foreach($new_records as $taxon_name_id) {
            /* $occurrence_id = $this->add_occurrence_assoc($taxon_id, $taxon_name_id . "_$type"); */ // used in orig EOL Associations implementation.
            $related_taxon = $this->add_taxon(array('page_id' => $taxon_name_id));
            $related_occurrence_id = $this->add_occurrence_assoc($related_taxon->taxonID, $taxon_id . "_$type");
            $a = new \eol_schema\Association_specific(); //take note used with '_specific'
            $a->label = self::get_assoc_label($ret, $taxon_name_id);
            $a->occurrenceID = $occurrence_id;
            $a->associationType = $ret['predicate'];
            $a->targetOccurrenceID = $related_occurrence_id;
            
            // $a->referenceID = $reference_ids; --> ver 1
            if($reference_ids = self::create_references($new_records_refs[$taxon_name_id])) $a->referenceID = implode("; ", $reference_ids); //ver 2 OK
            
            $a->measurementDeterminedDate = date("Y-M-d");
            
            // $a->source = "https://beta.eol.org/pages/$taxon->taxonID/data?predicate=".$ret['predicate']; //e.g. https://beta.eol.org/pages/46559217/data?predicate=http://eol.org/schema/terms/Habitat
            $a->source = "https://beta.eol.org/terms/search_results?utf8=✓&term_query[clade_id]=".$taxon->taxonID."&term_query[filters_attributes][0][pred_uri]=".$ret['predicate']."&term_query[filters_attributes][0][op]=is_any&term_query[result_type]=record&commit=Search";
            
            $a->measurementMethod   = 'summary of records available in EOL';
            $a->associationID = Functions::generate_measurementID($a, $this->resource_id, 'association');
            $this->archive_builder->write_object_to_file($a);
        }
    }
    /*
    private function write_resource_file_TaxonSummary_v1($ret)
    {   Array(
        [root] => 46557930
        [root label] => PRM
        [Selected] => Array(
                [0] => 46557930
                [1] => 207661
            )
        [Label] => REP
        [page_id] => 328598
        [predicate] => http://purl.obolibrary.org/obo/RO_0002470
        )
        $taxon_id = $ret['page_id'];
        $taxon = $this->add_taxon(array('page_id' => $taxon_id));
        $type = pathinfo($ret['predicate'], PATHINFO_BASENAME);
        $reference_ids = '';
        if($refs = @$ret['refs']) {
            if($reference_ids = self::create_references($refs)) $reference_ids = implode("; ", $reference_ids);
        }
        $occurrence_id = $this->add_occurrence_assoc($taxon_id, "$type"); // used in 'Summary Data Resources' implementation. Not the strategy used in EOL Associations
        foreach($ret['Selected'] as $taxon_name_id) {
            // --- $occurrence_id = $this->add_occurrence_assoc($taxon_id, $taxon_name_id . "_$type"); // used in orig EOL Associations implementation.
            $related_taxon = $this->add_taxon(array('page_id' => $taxon_name_id));
            $related_occurrence_id = $this->add_occurrence_assoc($related_taxon->taxonID, $taxon_id . "_$type");
            $a = new \eol_schema\Association_specific(); //take note used with '_specific'
            $a->label = self::get_assoc_label($ret, $taxon_name_id);
            $a->occurrenceID = $occurrence_id;
            $a->associationType = $ret['predicate'];
            $a->targetOccurrenceID = $related_occurrence_id;
            $a->referenceID = $reference_ids;
            $a->measurementDeterminedDate = date("Y-M-d");
            $a->source = "https://beta.eol.org/pages/$taxon->taxonID/data?predicate=".$ret['predicate']; //e.g. https://beta.eol.org/pages/46559217/data?predicate=http://eol.org/schema/terms/Habitat
            $a->measurementMethod   = 'summary of records available in EOL';
            $a->associationID = Functions::generate_measurementID($a, $this->resource_id, 'association');
            $this->archive_builder->write_object_to_file($a);
        }
    }
    */
    private function get_assoc_label($ret, $taxon_name_id)
    {
        if($ret['root'] == $taxon_name_id) return $ret['root label'];
        else                               return $ret['Label'];
    }
    private function add_occurrence_assoc($taxon_id, $label)
    {
        $occurrence_id = $taxon_id . "_" . str_replace(" ", "_", $label);
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        else {
            $this->archive_builder->write_object_to_file($o);
            $this->occurrence_ids[$occurrence_id] = '';
            return $occurrence_id;
        }
    }
    //############################################################################################ start write resource file - method = 'basal values'
    private function write_resource_file_BasalValues($info, $WRITE, $parentYN)
    {   /*when creating new records (non-tips), find and deduplicate all references and bibliographicCitations for each tip record below the node, and attach as references. MeasurementMethod= "summary of records available in EOL". Construct a source link to EOL, eg: https://beta.eol.org/pages/46559143/data */
        $page_id = $info['page_id']; $predicate = $info['predicate'];
        /*step 1: get all eol_pks */
        
        if($parentYN == "non-parent") $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate, array('value_uri')); //3rd param array is required_fields
        elseif($parentYN == "parent") $recs = $this->basal_values_parent_recs;
        else exit("\nNot go here...\n");
        
        echo "\n recs in writing: ".count($recs); //print_r($recs);
        $found = array(); $existing_records_for_writing = array(); $eol_pks = array();
        foreach($info['Selected'] as $id) {
            foreach($recs as $rec) {
                if($rec['value_uri'] == $id) {
                    $eol_pks[$rec['eol_pk']] = '';
                    $found[] = $id;
                    // /* write to file block
                    $row = array($page_id, $rec['eol_pk'], $id, $info['label']); //, $rec
                    $existing_records_for_writing[] = $row;
                    // */
                }
            }
        }
        if($existing_records_for_writing) self::adjust_if_needed_and_write_existing_records($existing_records_for_writing, $WRITE);
        
        $eol_pks = array_keys($eol_pks);
        if($new_records = array_diff($info['Selected'], $found)) {
            echo "\nBV - Not found in traits.csv. Create new record(s) [$page_id] [$predicate]: "; print_r($new_records); //good debug
            /* ver 1 obsolete
            $refs = self::get_refs_from_metadata_csv($eol_pks); //get refs for new records, same refs for all new records
            self::create_archive($new_records, $refs, $info);
            */
            // $this->info = $info;             //for debug only
            /* ver 2 OK */
            $new_records_refs = self::assemble_refs_for_new_recs($new_records, $recs);
            self::create_archive($new_records, $new_records_refs, $info);
        }
        else echo "\nNo new records. Will not write to DwCA.\n";
    }
    private function assemble_refs_for_new_recs($new_records, $orig_recs)
    {
        foreach($new_records as $new) {
            $descendants_of[$new] = self::get_from_ISVAT_descendants_of($new);                              //1. get from $this->ISVAT which are descendants of $new
            $eol_pks_of[$new] = self::get_eol_pks_of_new_from_origRecs($orig_recs, $descendants_of[$new]);  //2. get eol_pks from orig recs
            $refs_of[$new] = self::get_refs_from_metadata_csv($eol_pks_of[$new]);                           //3. get refs using eol_pks
        }
        print_r($descendants_of); print_r($eol_pks_of); print_r($refs_of); //good debug
        // foreach($refs_of as $key => $arr) echo "\n".count($arr)."\n"; //good debug for counting refs
        return $refs_of;
    }
    private function get_eol_pks_of_new_from_origRecs($recs, $descendants)
    {
        $eol_pks = array();
        foreach($recs as $rec) {
            if(in_array($rec['value_uri'], $descendants)) {
                $eol_pks[$rec['eol_pk']] = '';
            }
        }
        // echo "\nDONE: get_eol_pks_of_new_from_origRecs()\n";
        return array_keys($eol_pks);
    }
    private function get_from_ISVAT_descendants_of($term) //working well
    {
        // echo "\nSTART: get_from_ISVAT_descendants_of($term)\n";
        $desc_x = array($term);
        while($desc_x) {
            echo "\ndesc_x count: ".count($desc_x)." "; print_r($desc_x);
            foreach($this->ISVAT as $a) {
                if(in_array($a[0], $desc_x)) {
                    $temp[$a[1]] = '';
                    $desc_all[$a[1]] = '';
                    print_r($a);
                }
            }
            $temp = array_keys($temp);
            $desc_x = $temp;
            $temp = array();
        }
        // echo "\nDONE: get_from_ISVAT_descendants_of()\n";
        return array_keys($desc_all);
    }
    private function adjust_if_needed_and_write_existing_records($rows, $WRITE)
    {   /*For selected values available in multiple records, let's do an order of precedence based on metadata, with an arbitrary tie-breaker (which you'll need in this case; sorry!). 
          Please count the number of references attached to each candidate record, add 1 if there is a bibliographicCitation for the record, and choose the record with the highest number. 
          In case of a tie, break it with any arbitrary method you like.
        */
        /* forced test data
        $rows = array();
        $rows[] = array(46559217, 'R96-PK42940163', 'http://eol.org/schema/terms/temperate_grasslands_savannas_and_shrublands', 'REP');
        $rows[] = array(46559217, 'R512-PK24322763', 'http://purl.obolibrary.org/obo/ENVO_00000078', 'REP');
        $rows[] = array(46559217, 'R512-PK24381251', 'http://purl.obolibrary.org/obo/ENVO_00000220', 'REP');
        $rows[] = array(46559217, 'R512-PK24428398', 'http://purl.obolibrary.org/obo/ENVO_00000446', 'REP');
        // $rows[] = array(46559217, 'R512-PK24244192', 'http://purl.obolibrary.org/obo/ENVO_00000446', 'REP');
        // $rows[] = array(46559217, 'R512-PK23617608', 'http://purl.obolibrary.org/obo/ENVO_00000572', 'REP');
        // $rows[] = array(46559217, 'R512-PK24249316', 'http://purl.obolibrary.org/obo/ENVO_00002033', 'REP');
        // $rows[] = array(46559217, 'R512-PK24569594', 'http://purl.obolibrary.org/obo/ENVO_00000446', 'REP');
        */
        echo "\nExisting records: ".count($rows); //print_r($rows); //good debug
        //step 1: get counts
        foreach($rows as $row) {
            @$counts[$row[2]]++; //VERY IMPORTANT: the row[2] must be the value_uri for BV and object_page_id for TS
        }
        echo "\ncounts: "; print_r($counts);
        //step 2: get eol_pk if count > 1 -> meaning multiple records
        foreach($rows as $row) {
            $eol_pk = $row[1];
            $value_uri = $row[2];
            if($counts[$value_uri] > 1) @$study[$value_uri][] = $eol_pk;
        }
        if(!isset($study)) { echo "\nNo selected values available in multiple records.\n";
            foreach($rows as $row) fwrite($WRITE, implode("\t", $row). "\n");
            return;
        }
        //step 3: choose 1 among multiple eol_pks based on metadata (references + biblio). If same count just picked one.
        foreach($study as $value_uri => $eol_pks) {
            //get refs for each eol_pk
            $total = count($eol_pks); $i = 0;
            foreach($eol_pks as $eol_pk) {
                $i++; echo "\n[$i of $total] for ref count";
                $refs_of_eol_pk[$eol_pk][] = self::get_refs_from_metadata_csv(array($eol_pk)); //just for ref counts
            }
        }
        // echo "\n refs_of_eol_pk: "; print_r($refs_of_eol_pk);
        // echo "\n study: "; print_r($study);
        foreach($study as $value_uri => $eol_pks) {
            $ref_counts = array();
            foreach($eol_pks as $eol_pk) {
                $ref_counts[$eol_pk] = count($refs_of_eol_pk[$eol_pk]);
            }
            //compare counts and remove lesser, if equal just pick one
            // echo "\nref_counts: "; print_r($ref_counts);
            $remain[$value_uri][] = self::get_key_of_arr_with_biggest_value($ref_counts);
        }
        // echo "\n remain: ";print_r($remain);
        foreach($study as $value_uri => $eol_pks) {
            $remove[$value_uri] = array_diff($eol_pks, $remain[$value_uri]);
        }
        // echo "\n remove: ";print_r($remove);
        
        echo "\norig rows count: ".count($rows);
        //step 4: remove duplicate records
        $i = 0;
        foreach($rows as $row)
        {   /*Array(
            [0] => 46559217
            [1] => R512-PK24467582
            [2] => http://purl.obolibrary.org/obo/ENVO_00000447
            [3] => REP
            )*/
            $eol_pk = $row[1]; $value_uri = $row[2];
            if($eol_pk_2_remove = @$remove[$value_uri]) {
                if(in_array($eol_pk, $eol_pk_2_remove)) $rows[$i] = null;
            }
            $i++;
        }
        $rows = array_filter($rows);
        echo "\nnew rows count: ".count($rows)."\n";
        //step 5: finally writing the rows
        foreach($rows as $row) fwrite($WRITE, implode("\t", $row). "\n");
        return;
    }
    private function add_taxon($info)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $info['page_id'];
        $taxon->EOL_taxonID     = $info['page_id'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        return $taxon;
    }
    private function create_archive($records, $new_records_refs, $info) //EXTENSION_URL: http://rs.tdwg.org/dwc/xsd/tdwg_dwcterms.xsd
    {
        $taxon = self::add_taxon($info);
        foreach($records as $value_uri) { //e.g. http://purl.obolibrary.org/obo/ENVO_01001125
            $predicate = $info['predicate'];
            //start structured data
            $rec['label'] = $info['label'];
            $rec['taxon_id'] = $taxon->taxonID;
            $rec['measurementType'] = $predicate;
            $rec['measurementValue'] = $value_uri;
            /* ver 1
            if($reference_ids = self::create_references($refs)) $rec['referenceID'] = implode("; ", $reference_ids);
            */
            if($reference_ids = self::create_references($new_records_refs[$value_uri])) $rec['referenceID'] = implode("; ", $reference_ids);
            $rec['catnum'] = $taxon->taxonID . "_" . pathinfo($predicate, PATHINFO_BASENAME) . "_" . pathinfo($value_uri, PATHINFO_BASENAME);
            // $rec['source'] = "https://beta.eol.org/pages/$taxon->taxonID/data?predicate=$predicate"; //e.g. https://beta.eol.org/pages/46559217/data?predicate=http://eol.org/schema/terms/Habitat
            $rec['source'] = "https://beta.eol.org/terms/search_results?utf8=✓&term_query[clade_id]=".$taxon->taxonID."&term_query[filters_attributes][0][pred_uri]=".$predicate."&term_query[filters_attributes][0][op]=is_any&term_query[result_type]=record&commit=Search";
            // if($predicate == "http://eol.org/schema/terms/Habitat") self::add_string_types($rec);
            // elseif($predicate == "xxx")                             self::add_string_types($rec);
            self::add_string_types($rec);
        }
    }
    private function create_references($refs)
    {
        if(!$refs) return array();
        $reference_ids = array();
        foreach($refs as $ref_no => $full_ref) {
            $r = new \eol_schema\Reference();
            $r->identifier = $ref_no;
            $r->full_reference = $full_ref;
            $reference_ids[$r->identifier] = '';
            if(!isset($this->reference_ids[$r->identifier])) {
               $this->reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return array_keys($reference_ids);
    }
    private function add_string_types($rec)
    {
        $taxon_id = $rec['taxon_id'];
        $catnum = $rec['catnum'];
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum, $rec);

        $m = new \eol_schema\MeasurementOrFact_specific(); //NOTE: used a new class MeasurementOrFact_specific() for non-standard fields like 'm->label'
        $m->label               = $rec['label'];
        $m->occurrenceID        = $occurrence_id;
        $m->measurementOfTaxon  = 'true';
        $m->measurementType     = $rec['measurementType'];
        $m->measurementValue    = $rec['measurementValue'];
        $m->source              = $rec['source'];
        $m->measurementMethod   = 'summary of records available in EOL';
        $m->measurementDeterminedDate = "2018-Oct-10"; //date("Y-M-d");
        $m->referenceID   = @$rec['referenceID']; //not all have refs
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        // if($m->measurementID == "08eea7c40c13234a8e9699b52676236a_parent_basal_values")
        // if($m->measurementID == "dd832955e92c2a7b823ebf5c737057b9_parent_basal_values") 
        // {
        //     echo "\n**************************************************\n";
        //     print_r($this->info);
        //     exit("\nabove is eol_pks\n");
        // }
        $this->archive_builder->write_object_to_file($m);

        // $m->bibliographicCitation = "AmphibiaWeb: Information on amphibian biology and conservation. [web application]. 2015. Berkeley, California: AmphibiaWeb. Available: http://amphibiaweb.org/.";
        // $m->measurementRemarks  = '';
        // $m->contributor         = '';
        // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue'));
    }
    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum; //can be just this, no need to add taxon_id
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID      = $taxon_id;
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    function append_to_MySQL_table($table, $file_append)
    {
        echo "\nSaving [$table] records to MySQL...\n";
        if(filesize($file_append)) {
            /* Not needed here.
            //truncate first
            $sql = "TRUNCATE TABLE SDR.".$table.";";
            if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
            */
            //load data to a blank table
            $sql = "LOAD data local infile '".$file_append."' into table SDR.".$table.";";
            if($result = $this->mysqli->query($sql)) echo "\nSaved table [$table] to MySQL\n";
        }
        else echo "\nNothing to save.\n";
    }
    function generate_refs_per_eol_pk_MySQL()
    {
        //truncate first
        $table = 'metadata_refs'; $sql = "TRUNCATE TABLE SDR.".$table.";";
        if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
        
        $file_cnt = 1; $save = 0;
        $file_write = $this->main_dir."/MySQL_append_files/metadata_refs_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
        
        self::initialize();
        $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if(($i % 1000000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit("\nstopx refs\n");
                /*Array(
                    [eol_pk] => MetaTrait-122920149
                    [trait_eol_pk] => R788-PK74516597
                    [predicate] => http://rs.tdwg.org/dwc/terms/measurementMethod
                    [value_uri] => 
                    [measurement] => 
                    [units_uri] => 
                    [literal] => Adult body mass averaged across males and females and geographic locations.
                )*/
                // /* main operation
                $refs = array();
                if(count($fields) == count($line) && $rec['predicate'] == "http://eol.org/schema/reference/referenceID"    && $rec['literal']) $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if(count($fields) == count($line) && $rec['predicate'] == "http://purl.org/dc/terms/bibliographicCitation" && $rec['literal']) $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if($refs) {
                    $save++;
                    if(($save % 500000) == 0) {
                        echo "\nSaving...".number_format($save);
                        fclose($WRITE);
                        self::append_to_MySQL_table('metadata_refs', $this->main_dir."/MySQL_append_files/metadata_refs_".$file_cnt.".txt");
                        $file_cnt++;
                        $file_write = $this->main_dir."/MySQL_append_files/metadata_refs_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
                    }
                    self::write_report($rec, $fields, $WRITE);
                }
                // */
            }
            // if($i >= 5) return; //debug
        }
        fclose($WRITE);
        self::append_to_MySQL_table('metadata_refs', $this->main_dir."/MySQL_append_files/metadata_refs_".$file_cnt.".txt");
        fclose($file); exit("\n\nMetadata_refs to MySQL DONE.\n\n");
    }
    function generate_refs_per_eol_pk() //total eol_pks 39,931 Carnivora | 11,233,522 metadata.csv | 985,159 metadata_refs in MySQL
    {   
        exit; /* just save it to MySQL table. BE SURE TO INDEX eol_pk, trait_eol_pk */
        self::initialize();
        $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if(($i % 100000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                print_r($rec); //exit("\nstopx refs\n");
                /*Array(
                    [eol_pk] => MetaTrait-122920149
                    [trait_eol_pk] => R788-PK74516597
                    [predicate] => http://rs.tdwg.org/dwc/terms/measurementMethod
                    [value_uri] => 
                    [measurement] => 
                    [units_uri] => 
                    [literal] => Adult body mass averaged across males and females and geographic locations.
                )*/
                // /* main operation
                $refs = array();
                if(count($fields) == count($line) && $rec['predicate'] == "http://eol.org/schema/reference/referenceID"    && $rec['literal']) $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if(count($fields) == count($line) && $rec['predicate'] == "http://purl.org/dc/terms/bibliographicCitation" && $rec['literal']) $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if($refs) {
                    $json_file = self::get_txt_path_by_page_id($rec['trait_eol_pk'], ".txt");
                    $arr = array();
                    if(file_exists($json_file)) {
                        $json = file_get_contents($json_file);
                        $arr = json_decode($json, true);
                        echo "\n[$json_file] appended\n";
                    }
                    else echo "\n[$json_file] created\n";
                    foreach($refs as $refno => $fullref) if(!isset($arr[$refno])) $arr[$refno] = $fullref;
                    $WRITE = fopen($json_file, 'w'); fwrite($WRITE, json_encode($arr)); fclose($WRITE); 
                }
                // */
            }
            // if($i >= 5) return; //debug
        }
    }
    /* not being used anymore, since the use of MySQL tables
    function delete_all_eol_pks_refs_fileTXT() //total eol_pks 39,931
    {
        self::initialize();
        $eol_pks = self::get_unique_eol_pks_from_metadata_csv();
        $total = count($eol_pks); $i = 0;
        foreach($eol_pks as $eol_pk) {
            $i++; echo "\n $i of $total";
            $file = self::get_txt_path_by_page_id($eol_pk, ".txt");
            if(file_exists($file)) {
                if(unlink($file)) echo "\ndeleted";
            }
            else echo "\nnot found";
        }
    }
    private function get_unique_eol_pks_from_metadata_csv()
    {
        $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                if(count($fields) == count($line) && $rec['predicate'] == "http://eol.org/schema/reference/referenceID")    $eol_pks[$rec['trait_eol_pk']] = '';
                if(count($fields) == count($line) && $rec['predicate'] == "http://purl.org/dc/terms/bibliographicCitation") $eol_pks[$rec['trait_eol_pk']] = '';
            }
        }
        return array_keys($eol_pks);
    }
    */
    private function get_refs_from_metadata_csv($eol_pks)
    {
        $str = implode(",", $eol_pks);
        $str = str_replace(",", "','", $str);
        $str = "'".$str."'";
        $sql = "SELECT m.* from SDR.metadata_refs m WHERE m.trait_eol_pk IN (".$str.")";
        $result = $this->mysqli->query($sql);
        $final = array(); $final2 = array();
        while($result && $rec=$result->fetch_assoc()) $final[$rec['eol_pk']] = strip_tags($rec['literal']);
        //make fullref unique
        foreach($final as $refno => $fullref) {
            if(isset($this->fullref[$fullref])) {
                $refno = $this->fullref[$fullref];
                $final2[$refno] = $fullref;
            }
            else {
                $this->fullref[$fullref] = $refno;
                $final2[$refno] = $fullref;
            }
        }
        return $final2;
    }
    private function get_refs_from_metadata_csv_OLD($eol_pks) //replaced the ver 1, which is very slow
    {
        $final = array();
        foreach($eol_pks as $eol_pk) {
            $file = self::get_txt_path_by_page_id($eol_pk, ".txt");
            if(file_exists($file)) {
                $json = file_get_contents($file);
                $arr = json_decode($json, true);
                if($arr) $final = $final + $arr;
            }
        }
        //make fullref unique
        foreach($final as $refno => $fullref) {
            if(isset($this->fullref[$fullref])) {
                $refno = $this->fullref[$fullref];
                $final2[$refno] = $fullref;
            }
            else {
                $this->fullref[$fullref] = $refno;
                $final2[$refno] = $fullref;
            }
        }
        return $final2;
    }
    private function get_refs_from_metadata_csv_v1($eol_pks) //versy slow
    {
        // echo "\neol_pks to process: ".count($eol_pks)."\n";
        if(!$eol_pks) return array();
        $refs = array();
        $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec);exit;
                /*Array(
                    [eol_pk] => MetaTrait-19117935  [trait_eol_pk] => R261-PK22081478   [predicate] => http://rs.tdwg.org/dwc/terms/measurementMethod
                    [literal] => Activity cycle of each species measured for non-captive populations; adult or age unspecified individuals, male, female, or sex unspecified individuals; primary, secondary, or extrapolated sources; all measures of central tendency; in all localities. Species were defined as (1) nocturnal only, (2) nocturnal/crepuscular, cathemeral, crepuscular or diurnal/crepuscular and (3) diurnal only.  Based on information from primary and secondary literature sources.  See source for details. 
                    [measurement] => [value_uri] => [units] => [sex] => [lifestage] => [statistical_method] => [source] => 
                )*/
                if(in_array($rec['trait_eol_pk'], $eol_pks) && count($fields) == count($line) && $rec['predicate'] == "http://eol.org/schema/reference/referenceID")    $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if(in_array($rec['trait_eol_pk'], $eol_pks) && count($fields) == count($line) && $rec['predicate'] == "http://purl.org/dc/terms/bibliographicCitation") $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                // $debug[$rec['predicate']] = '';
            }
        }
        // print_r($refs); print_r($debug); exit;
        // echo "\nDONE: get_refs_from_metadata_csv()\n";
        return $refs;
    }
    private function get_sought_field($recs, $field)
    {
        foreach($recs as $rec) $final[$rec[$field]] = '';
        return array_keys($final);
    }
    private function get_page_ids_fromTraitsCSV_andInfo_fromDH($predicates)
    {   //step 1: get all page_ids from traits.csv
        $ret = self::get_fields_from_file(array('page_id'), 'traits.csv', $predicates);
        if(!$ret) return array();
        $page_ids = $ret['page_id']; $ret = ''; //unset
        //step 2 get desired info from DH
        $info = self::prep_DH();
        $i = 0;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                /*Array(
                    [taxonID] => -168611
                    [acceptedNameUsageID] => -168611
                    [parentNameUsageID] => -105852
                    [scientificName] => Torpediniformes
                    [taxonRank] => order
                    [source] => trunk:59edf7f2-b792-4351-9f37-562dd522eeca,WOR:10215,gbif:881
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 8898
                    [EOLidAnnotations] => multiple;
                    [Landmark] => 1
                )*/
                if(isset($page_ids[$rec['EOLid']])) $page_ids[$rec['EOLid']] = array('taxonRank' => $rec['taxonRank'], 'Landmark' => $rec['Landmark']);
            }
        }
        return $page_ids;
    }
    private function get_fields_from_file($headers, $filename, $predicates)
    {
        $sql = "SELECT DISTINCT(t.page_id) from SDR.traits t WHERE t.predicate = '".$predicates[0]."'";
        $result = $this->mysqli->query($sql);
        $final = array();
        while($result && $rec=$result->fetch_assoc()) {
            foreach($headers as $head) $final[$head][$rec[$head]] = '';
        }
        return $final;
        /* working but too slow for All Export File
        $file = fopen($this->main_paths['archive_path'].'/'.$filename, 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                Array(
                    [eol_pk] => R788-PK74508166
                    [page_id] => 1180180
                    [resource_pk] => 
                    [resource_id] => 694
                    [source] => 
                    [scientific_name] => <i>Zygodontomys brevicauda</i>
                    [predicate] => http://eol.org/schema/terms/ExtinctionStatus
                    [object_page_id] => 
                    [value_uri] => http://eol.org/schema/terms/extant
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [normal_units] => 
                    [measurement] => 
                    [units_uri] => 
                    [units] => 
                    [literal] => http://eol.org/schema/terms/extant
                )
                if(in_array($rec['predicate'], $predicates)) {
                    foreach($headers as $head) $final[$head][$rec[$head]] = '';
                }
            }
        }
        return $final;
        */
    }
    //############################################################################################ start method = 'parents basal values'
    private function main_parents_basal_values($main_page_id, $predicate)
    {   echo "\n#####################################################################\n";echo "\nMethod: parents basal values | Page ID: $main_page_id | Predicate: $predicate\n";
        /* 1. get all children of page_id with rank = species */
        // $children = array(328598, 328609, 46559217, 328682, 328607); //force assignment, development only

        // /*
        if($children = self::get_CSV_children_of($main_page_id)) {
            echo "\n*Children of [$main_page_id]: "; print_r($children);
        }
        else {
            echo "\n*No children found for [$main_page_id]\n";
            return array();
        }
        // */
        
        /* debug force assign
        if($main_page_id == 7662) $children = array(328598, 328609, 46559217, 328682, 328607); //force assignment, development only
        echo "\n*Children of [$main_page_id]: "; print_r($children);
        */
        
        /* obsolete
        2. get all values for each child from method = 'basal values'
        foreach($children as $page_id) {
            if($val = self::main_basal_values($page_id, $predicate)) $records[] = $val;
        }
        3. get all selected values
        $page_ids = array();
        $recs = array();
        foreach($records as $rec) {
            // if($val = @$rec['Selected']) $page_ids = array_merge($page_ids, $val); //version 1 - didn't use
            if($val = @$rec['recs']) $recs = array_merge($recs, $val);
        }
        */
        
        /* 2. get all recs for each child */
        $recs = array();
        foreach($children as $page_id) {
            $child_recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate, array('value_uri')); // echo "\n".count($child_recs)."\n";
            if($child_recs) $recs = array_merge($recs, $child_recs);
        }
        // echo "\n".count($recs)."\n";

        /* version 1 - didn't use
        $original_records = $page_ids;
        asort($original_records); $original_records = array_values($original_records); //reindexes key
        $page_ids = array_unique($page_ids);
        asort($page_ids);
        $page_ids = array_values($page_ids); //reindexes key
        echo "\n==========================================================\nParent process for taxon ID $main_page_id, predicate $predicate\n";
        echo "\nChildren used for computation: "; print_r($children);
        echo "\n==========================================================\nCombined values from the original records (all REC records of children), raw: ".count($original_records); // print_r($original_records);
        foreach($original_records as $id) echo "\n$id";
        echo "\n==========================================================\nDeduplicated: ".count($page_ids);
        print_r($page_ids);
        $uris = $page_ids;
        self::set_ancestor_ranking_from_set_of_uris($uris);
        $ISVAT = self::get_initial_shared_values_ancestry_tree_v2($uris); //initial "shared values ancestry tree" ---> parent left, term right
        if($val = self::main_basal_values(NULL, NULL, 'parent basal values', $ISVAT, $uris)) {
            print_r($val); exit("\nelix\n");
        }
        */
        if(!$recs) {
            echo "\nNo recs for any of the children for predicate [$predicate]\n";
            return false;
        }
        if($ret = self::main_basal_values(NULL, NULL, 'parent basal values', $recs)) {
            print_r($ret);
            foreach($ret['Selected'] as $term) { //debug only
                echo "\n[$term]: ";
                if($val = @$this->parents_of[$term]) print_r($val);
                else echo " -- no parent";
            }
            return $ret;
        }
    }
    /* not used anymore...
    private function get_initial_shared_values_ancestry_tree_v2($uris)
    {
        $final = array();
        foreach($uris as $term) {
            $parent = self::get_parent_of_term($term);
            $final[] = array($parent, $term);
        }
        return $final;
    }
    */
    //############################################################################################ start method = 'parents taxon summary'
    private function main_parents_taxon_summary($main_page_id, $predicate)
    {   echo "\n#####################################################################"; echo "\nMethod: parents taxon summary | Page ID: $main_page_id | Predicate: $predicate\n";
        /* 1. get all children of page_id with rank = species */
        // $children = array(328598, 46559162, 328607, 46559217, 328609); //force assign, during dev only
        // /*
        $children = array();
        $children[] = $main_page_id;
        // and, just for the taxon summary parents (not for the basal value parents) a change in the contributing child taxa: please include
        // all descendant taxa at all ranks, up to and including the taxon in question, so the summary for page 7666 should be based on a record pool including records for page 7666.
        // You may want to include a filter so, if we re-run this in a few months, the summary records created for 7666 are not included in the new pool of records.
        // (This is entirely because of the quality of the data. Basal value records, habitat and geography, include many questionable records at, for instance, the family level.
        // Interactions records include a lot of pretty reasonable records for the same taxa.)
        if($mga_anak = self::get_CSV_children_of($main_page_id)) $children = array_merge($children, $mga_anak);
        echo "\n*Children of [$main_page_id] inclusive: "; print_r($children);
        // */
        
        /* 2. get all values for each child from method = 'taxon summary' */
        // $children = array(328609); //debug
        $records = array();
        foreach($children as $page_id) {
            if($val = self::main_taxon_summary($page_id, $predicate)) {
                echo "\nFinal result: taxon summary: "; print_r($val);
                $records[] = $val;
                $this->debug['parent taxon summary'][$main_page_id."_".$predicate][] = $page_id; //the parent taxa and corresponding predicate, with children that have records. Not all children under this parent but only those with records.
            }
        }
        if(count($records) == 1) { echo "\n**Only 1 child has records. Use result of this child as result of the parent process ".count($records)." For [$main_page_id], $predicate\n";
            /*Array
                [0] => Array
                        [root] => 1642
                        [root label] => PRM and REP
                        [Selected] => Array
                                [0] => 45511473
                                [1] => 1642
                        [Label] => REP
                        [refs] => Array ---- this is now obsolete
                    )
            )*/
            $rec = $records[0];
            return array('root' => $rec['root'], 'root label' => $rec['root label'], 'Selected' => $rec['Selected'], 'Label' => $rec['Label']);
        }
        elseif(count($records) > 1) { echo "\n**Multiple children have records ".count($records)." For [$main_page_id], $predicate\n";
            // exit;
        }
        else echo "\n**No children, even inclusive have any records. For [$main_page_id], $predicate\n";
        
        
        if(!$records) return array();
        /* 3. get all selected values */
        $page_ids = array();
        foreach($records as $rec) {
            if($val = @$rec['Selected']) $page_ids = array_merge($page_ids, $val);
        }
        $original_records = $page_ids;
        $page_ids = array_unique($page_ids);
        $page_ids = array_values($page_ids); //reindexes key
        echo "\n==========================================================\nStart: parent process for taxon ID $main_page_id, predicate $predicate\n";
        echo "\nChildren used for computation: "; print_r($children);
        echo "\n==========================================================\nCombined values from the original records (all REC records of children), raw:";
        print_r($original_records);
        // asort($original_records); print_r($original_records);
        echo "\n==========================================================\nCombined values from the original records (all REC records of children), deduplicated:";
        print_r($page_ids);
        //now get similar report from 'taxon summary'
        echo "\n==========================================================\nHierarchies of taxon values:";
        $hierarchies_of_taxon_values = array();
        foreach($page_ids as $page_id) {
            $anc = self::get_ancestry_via_DH($page_id);
            $hierarchies_of_taxon_values[$page_id] = $anc;
        }

        // /* NEW STEP: If the common root of the dataset is anything else, you can leave it. Only remove it if it is in the magic 5 of deletable taxa. 
        // $hierarchies_of_taxon_values = self::adjust_2913056($hierarchies_of_taxon_values); MOVED BELOW...
        // */
        print_r($hierarchies_of_taxon_values);
        
        //start store counts 2:
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            $k = 0;
            foreach($anc as $id) {
                @$counts[$id]++;
                if($k > 0) $children_of[$id][] = $anc[$k-1];
                $k++;
            }
        }

        // print_r($counts); //print_r($children_of); //good debug
        $final = array();
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            foreach($anc as $id) {
                if($count = @$counts[$id]) {
                    if($count >= 2) { //meaning this ancestor exists in other recs
                        if($arr = @$children_of[$id]) {
                            $arr = array_unique($arr);
                            if(count($arr) > 1) $final[$page_id][] = $id; //meaning child is not the same for all recs
                        }
                    }
                }
            }
        }
        echo "\n==========================================================\nReduced hierarchies: \n";
        $hierarchies_of_taxon_values = array(); //to be used
        foreach($page_ids as $page_id) {
            echo "\n[$page_id] -> ";
            $hierarchies_of_taxon_values[$page_id] = '';
            if($val = @$final[$page_id]) {
                print_r($val);
                $hierarchies_of_taxon_values[$page_id] = $val;
            }
            else echo "no more ancestry";
        }

        // /* NEW STEP: If the common root of the dataset is anything else, you can leave it. Only remove it if it is in the magic 5 of deletable taxa. 
        $hierarchies_of_taxon_values = self::adjust_2913056($hierarchies_of_taxon_values);
        // */
        echo "\n==========================================================\nHierarchies after removal of the 5 deletable taxa:"; print_r($hierarchies_of_taxon_values);
        $final = $hierarchies_of_taxon_values; //needed assignment

        echo "\n==========================================================\nroots < 15% removal step:\n";
        /* ---------------------------------------------------------------------------------------------------------------------------------------
        "NEW STEP: IF there are multiple roots, discard those representing less than 15% of the original records",
        discard: yes, *in this step* discard means that whole hierarchy
        "original records" is a set just upstream of your second section in your result file: 
        "combined values from the original records (all REC records of children), deduplicated:Array". 
        The list I want is before deduplication, 
        i.e. if 207661 was a value for more than one of the child taxa, it should count more than once in the 15% calculation.
        */
        $ret_roots = self::get_all_roots($final); //get all roots of 'Reduced hierarchies'
        $all_roots = $ret_roots['roots'];
        $count_all_roots = count($all_roots);
        // echo "\nRoots info: "; print_r($ret_roots); //debug only

        // $all_roots = array(1, 42430800); //test force assignment -- debug only
        // if(true) {

        if($count_all_roots > 1) {
            echo "\nMultiple roots: "; print_r($all_roots);
            $temp_final = self::roots_lessthan_15percent_removal_step($original_records, $all_roots, $final);
            if($temp_final != $final) {
                $final = $temp_final;
                echo "\nHierarchies after discarding those representing less than 15% of the original records: "; print_r($final);
            }
            // else echo "\nfinal and temp_final are equal\n"; //just debug
            unset($temp_final);
        }
        else echo "\nJust one root ($all_roots[0]). Will skip this step.\n";

        echo "\n==========================================================\nFinal step:\n";
        /* ---------------------------------------------------------------------------------------------------------------------------------------
        IF >1 roots remain:,
        All the remaining roots are REP records,
        the one that appears in the most ancestries is the PRM,
        ,
        IF one root remains:,
        All direct children of the remaining root are REP records,
        the one that appears in the most ancestries is the PRM,
        (i.e. same behavior as taxon summary),
        ,
        "In this case, one root remains (taxon ID=1)",
        REP records:,
        2774383,
        166,
        10459935,
        ,
        PRM record:,
        2774383,
        */
        $ret_roots = self::get_all_roots($final); //get all roots of 'Reduced hierarchies'
        $all_roots = $ret_roots['roots'];
        $count_all_roots = count($all_roots);
        echo "\nList of root(s) and the corresponding no. of records it existed:"; print_r($ret_roots); //good debug
        if($count_all_roots == 1) {
            echo "\nAll direct children of the remaining root are REP records, the one that appears in the most ancestries is the PRM.\n";
            //from taxon summary:
            $ret = self::get_immediate_children_of_root_info($final);
            $immediate_children_of_root         = $ret['immediate_children_of_root'];
            $immediate_children_of_root_count   = $ret['immediate_children_of_root_count'];

            echo "\nImmediate children of root => and the no. of records it existed:";
            print_r($immediate_children_of_root_count); echo "\n";
            /* ver. 1 strategy
            $root_ancestor = array_unique($root_ancestor);
            */
            // /* ver. 2 strategy
            $root_ancestor = self::get_key_of_arr_with_biggest_value($immediate_children_of_root_count);
            // */
            $immediate_children_of_root = array_keys($immediate_children_of_root);

            echo "\nPRM record: $root_ancestor (the one that appears in the most ancestries)";
            echo "\nREP records: "; print_r($immediate_children_of_root);
            return array('tree' => $final, 'root' => $root_ancestor, 'root label' => 'PRM and REP', 'Selected' => $immediate_children_of_root, 'Label' => 'REP');
            
        } //end IF one root remains ------------------------------------------------------------
        elseif($count_all_roots > 1) { //has not met this criteria yet in our test cases.
            echo "\nMore than 1 root remain. All the remaining roots are REP records, the one that appears in the most ancestries is the PRM.\n";
            /* IF >1 roots remain:,
            - All the remaining roots are REP records,
            - the one that appears in the most ancestries is the PRM,
            e.g. List of roots and the corresponding no. of records it existed:
            $ret_roots = Array(
                [roots] => Array(
                        [0] => 1
                        [1] => 173 
                        [2] => 143
                [count_of_roots] => Array(
                        [1] => 7
                        [173] => 2
                        [143] = 1
            )*/
            $root_ancestor = self::get_key_of_arr_with_biggest_value($ret_roots['count_of_roots']);
            echo "\nPRM record: $root_ancestor (the one that appears in the most ancestries)";
            echo "\nREP records: "; print_r($ret_roots['roots']);
            return array('tree' => $final, 'root' => $root_ancestor, 'root label' => 'PRM and REP', 'Selected' => $ret_roots['roots'], 'Label' => 'REP');
        } //end if > 1 roots remain ------------------------------------------------------------
        exit("\nexit muna\n");
    }
    private function roots_lessthan_15percent_removal_step($original_records, $all_roots, $final_from_main)
    {
        /* compute how many records from the original_records does the root exists */
        foreach($original_records as $page_id) {
            $ancestries[] = self::get_ancestry_via_DH($page_id);
        }
        foreach($all_roots as $root) {
            if(!isset($final[$root])) $final[$root] = 0;
            foreach($ancestries as $anc) {
                if(in_array($root, $anc)) @$final[$root]++;
            }
        }
        // print_r($final); //good debug
        /* get those that are < 15% */
        $remove = array();
        foreach($final as $root => $count) {
            $percentage = ($count/count($original_records))*100;
            $final2['roots % in original records'][$root] = $percentage;
            if($percentage < 15) $remove[] = $root;
        }
        print_r($final2);
        // echo "\nremove: "; print_r($remove);
        
        if($remove) {
            /* remove from $final_from_main those with roots that are < 15% coverage in $original_records */
            foreach($final_from_main as $page_id => $ancestry) {
                if($ancestry) {
                    $orig_ancestry = $ancestry;
                    $root = array_pop($ancestry); //the last rec from an array
                    if(in_array($root, $remove)) {}
                    else $final3[$page_id] = $orig_ancestry;
                }
                else $final3[$page_id] = $ancestry;
            }
            // echo "\nwent here 01\n";
            return $final3;
        }
        else  {
            // echo "\nwent here 02\n";
            return $final_from_main;
        }
    }
    private function get_all_roots($reduced_hierarchies)
    {
        $final = array();
        foreach($reduced_hierarchies as $page_id => $anc) {
            if($anc) {
                $last = array_pop($anc);
                $final[$last] = '';
                @$count_of_roots[$last]++;
            }
            else { //case where both a root and a tip.
                $final[$page_id] = '';
                @$count_of_roots[$page_id]++;
            }
        }
        return array('roots' => array_keys($final), 'count_of_roots' => $count_of_roots);
    }
    private function adjust_2913056($hierarchies_of_taxon_values) //If the common root of the dataset is anything else, you can leave it. Only remove it if it is 2913056 
    {
        /* Rules:
        - If the root node is any of these five, and if it is common to all 'hierarchies_of_taxon_values', then I'll remove that root node from all hierarchies.
        - If there are multiple root nodes, but all are included in the magic five -> remove all
        - if there are multiple root nodes, some are outside of the magic five -> remove magic 5 roots, leave the others
        */
        $root_nodes_to_remove = array(46702381, 2910700, 6061725, 2908256, 2913056);
        $cont_for_more = false;
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            if(!is_array($anc)) {
                $final[$page_id] = $anc;
                continue;
            }
            $orig_anc = $anc;
            $last = array_pop($anc);
            if(in_array($last, $root_nodes_to_remove)) {
                $final[$page_id] = $anc;
                $cont_for_more = true;
            }
            else $final[$page_id] = $orig_anc;
        }
        if($cont_for_more) {
            while(true) {
                $cont_for_more = false;
                foreach($final as $page_id => $anc) {
                    $orig_anc = $anc;
                    $last = array_pop($anc);
                    if(in_array($last, $root_nodes_to_remove)) {
                        $final2[$page_id] = $anc;
                        $cont_for_more = true;
                    }
                    else $final2[$page_id] = $orig_anc;
                }
                if($cont_for_more) {
                    $final = $final2;
                    $final2 = array();
                }
                else break; //break from while true
            }
            return $final2;
        }
        else return $final;
        
        /* version 1 obsolete
        $life = 2913056;
        $remove_last_rec = true;
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            $last = array_pop($anc);
            if($last != $life) {
                $remove_last_rec = false; //if only if one is not $life then don't remove last rec.
                break; //end loop
            }
        }
        if($remove_last_rec) {
            echo "\nNOTE: Common root of hierarchies of taxon values (n=".count($hierarchies_of_taxon_values).") is 'Life:2913056'. Will remove this common root.\n";
            $final = array();
            foreach($hierarchies_of_taxon_values as $page_id => $anc) {
                array_pop($anc);
                $final[$page_id] = $anc;
            }
            return $final;
        }
        else return $hierarchies_of_taxon_values;
        */
    }
    //############################################################################################ end method = 'parents'
    private function extract_DH()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->EOL_DH, "taxa.txt", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $tables['taxa'] = 'taxa.txt';
        $paths['tables'] = $tables;
        return $paths;
    }
    private function prep_DH()
    {
        if(Functions::is_production()) {
            if(!($info = self::extract_DH())) return;
            print_r($info);
            // $this->info_path = $info;
        }
        else { //local development only
            /*
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/EOL_dynamic_hierarchy/',   //for eoldynamichierarchyv1.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/',
                          'tables' => Array('taxa' => 'taxa.txt')); */
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',                         //for eoldynamichierarchywithlandmarks.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',
                          'tables' => Array('taxa' => 'taxa.txt'));
            
            $info = Array('archive_path' => '/Volumes/AKiTiO4/web/cp/summary data resources/DH/eoldynamichierarchywithlandmarks/',   //for eoldynamichierarchywithlandmarks.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',
                          'tables' => Array('taxa' => 'taxa.txt'));

            // $this->info_path = $info;
        }
        return $info;
    }
    private function parse_DH()
    {
        $info = self::prep_DH();
        $i = 0;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [taxonID] => -168611
                    [acceptedNameUsageID] => -168611
                    [parentNameUsageID] => -105852
                    [scientificName] => Torpediniformes
                    [taxonRank] => order
                    [source] => trunk:59edf7f2-b792-4351-9f37-562dd522eeca,WOR:10215,gbif:881
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 8898
                    [EOLidAnnotations] => multiple;
                    [Landmark] => 1
                )
                Array(
                    [taxonID] => 93302
                    [acceptedNameUsageID] => 93302
                    [parentNameUsageID] => -1
                    [scientificName] => Cellular Organisms
                    [taxonRank] => clade
                    [source] => trunk:b72c3e8e-100e-4e47-82f6-76c3fd4d9d5f
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 6061725
                    [EOLidAnnotations] => manual;
                    [Landmark] => 
                )
                */
                /* debugging
                // if($rec['EOLid'] == 3014446) {print_r($rec); exit;}
                // if($rec['taxonID'] == 93302) {print_r($rec); exit;}
                // if($rec['Landmark']) print_r($rec);
                if(in_array($rec['EOLid'], Array(7687,3014522,42399419,32005829,3014446,2908256))) print_r($rec);
                */
                $this->EOL_2_DH[$rec['EOLid']] = $rec['taxonID'];
                $this->DH_2_EOL[$rec['taxonID']] = $rec['EOLid'];
                $this->parent_of_taxonID[$rec['taxonID']] = $rec['parentNameUsageID'];
                $this->landmark_value_of[$rec['EOLid']] = $rec['Landmark'];
                if($rec['taxonRank'] == 'family') $this->is_family[$rec['EOLid']] = '';
            }
        }
        /* may not want to force assign this:
        $this->DH_2_EOL[93302] = 6061725; //Biota - Cellular Organisms
        */
        
        // remove temp dir
        // recursive_rmdir($info['temp_dir']);
        // echo ("\n temporary directory removed: " . $info['temp_dir']);
    }
    private function get_ancestry_via_DH($page_id, $landmark_only = true)
    {
        $final = array(); $final2 = array();
        $taxonID = @$this->EOL_2_DH[$page_id];
        if(!$taxonID) {
            echo "\nThis page_id [$page_id] is not found in DH.\n";
            return array();
        }
        while(true) {
            if($parent = @$this->parent_of_taxonID[$taxonID]) $final[] = $parent;
            else break;
            $taxonID = $parent;
        }
        $i = 0;
        foreach($final as $taxonID) {
            // echo "\n$i. [$taxonID] => ";
            if($EOLid = @$this->DH_2_EOL[$taxonID]) {
                /* new strategy: using Landmark value   ver 1
                if($this->landmark_value_of[$EOLid]) $final2[] = $EOLid; */

                if($landmark_only) { //default; new strategy: using Landmark value   ver 2
                    if($this->landmark_value_of[$EOLid] || isset($this->is_family[$EOLid])) $final2[] = $EOLid;
                }
                else { //orig strategy
                    $final2[] = $EOLid;
                }
            }
            $i++;
        }
        return $final2;
    }
    private function main_taxon_summary($page_id, $predicate)
    {
        /* working but seems not needed. Just bring it back when requested.
        $ancestry = self::get_ancestry_via_DH($page_id);
        echo "\n$page_id: (ancestors below, with {Landmark value} in curly brackets)";
        foreach($ancestry as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
        */
        echo "\n================================================================Method: taxon summary\npage_id: $page_id | predicate: [$predicate]\n";
        // $path = self::get_txt_path_by_page_id($page_id); //not needed anymore
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        if(!$recs) { echo "\nNo records for [$page_id] [$predicate].\n"; return; }
        echo "\nrecs: ".count($recs)."\n";
        // print_r($recs); exit;
        
        if($this->parentModeYN) $this->taxon_summary_parent_recs = array_merge($this->taxon_summary_parent_recs, $recs); //to be in writing resource file
        
        /* Jen's verbatim instruction: to get the reduced 'tree'
        For each ancestor, find all recs in which it appears (recs set 1)
        If the parent of that ancestor is the same in all the recs in rec set 1, remove the parent

        Eli's interpretation: which gets the same results:
        - get all ancestors that exist also in other recs.
        - among these ancestors, select those where it has > 1 children. Don't include those with the same child in its occurrence in other recs.
        */
        foreach($recs as $rec) {
            if($page_id = @$rec['object_page_id']) {
                $anc = self::get_ancestry_via_DH($page_id);
                // /* initial report for Jen
                // echo "\nAncestry [$page_id]: "; print_r($anc); //orig initial report
                if($anc) {
                    echo "\n$page_id: (ancestors below, with {Landmark value} in curly brackets)";
                    foreach($anc as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
                }
                // */
                //start store counts 1:
                $k = 0;
                foreach($anc as $id) {
                    @$counts[$id]++;
                    @$orig_counts_with_left[$id]++;
                    if($k > 0) $children_of[$id][] = $anc[$k-1];
                    $k++;
                }
                @$orig_counts_with_left[$page_id]++;
            }
        }
        // print_r($counts); print_r($children_of); //good debug
        $final = array(); $eol_pks = array(); //$eol_pks here is for getting the refs
        foreach($recs as $rec) {
            if($page_id = @$rec['object_page_id']) {
                $anc = self::get_ancestry_via_DH($page_id);
                foreach($anc as $id) {
                    if($count = @$counts[$id]) {
                        if($count >= 2) { //meaning this ancestor exists in other recs
                            if($arr = @$children_of[$id]) {
                                $arr = array_unique($arr);
                                if(count($arr) > 1) {
                                    $final[$page_id][] = $id; //meaning child is not the same for all recs
                                    $eol_pks[$rec['eol_pk']] = ''; // probably where we get the refs, at least for this single test case it was right on
                                }
                            }
                        }
                    }
                }
            }
        }
        /* For refs START --- commented since refs are now assembled at write_resource_file_TaxonSummary()
        $refs = array();
        if($eol_pks) $refs = self::get_refs_from_metadata_csv(array_keys($eol_pks));
        For refs END */
        $refs = array();
        
        echo "\n==========================================\nTips on left. Ancestors on right.\n";
        // print_r($final);
        foreach($final as $tip => $ancestors) {
            echo "\n$tip: (reduced ancestors below, with {Landmark value} in curly brackets)";
            foreach($ancestors as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
        }
        echo "\n";
        /* may not need this anymore: get tips
        $tips = array_keys($final); //next step is get all tips from $final; 
        echo "\n tips: ".count($tips)." - "; print_r($tips);
        */
        /* from Jen: After the tree is constructed:
        - Select all immediate children of the root and label REP.
        - Label the root PRM
        */
        echo "\n final array: ".count($final); print_r($final); 
        
        $this->ISVAT_TS = $this->ISVAT_TS + $final;
        
        if(!$final) return false;
        /* WORKING WELL but was made into a function -> get_immediate_children_of_root_info($final)
        foreach($final as $tip => $ancestors) {
            $root_ancestor[] = end($ancestors);
            $no_of_rows = count($ancestors);
            if($no_of_rows > 1) $idx = $ancestors[$no_of_rows-2]; // rows should be > 1 bec if only 1 then there is no child for that root.
            elseif($no_of_rows == 1) $idx = $tip; 
            else exit("\nInvestigate: won't go here...\n");
            $immediate_children_of_root[$idx] = '';
            @$immediate_children_of_root_count[$idx]++;
        }
        */
        $ret = self::get_immediate_children_of_root_info($final);
        $immediate_children_of_root         = $ret['immediate_children_of_root'];
        $immediate_children_of_root_count   = $ret['immediate_children_of_root_count'];
        
        echo "\nImmediate children of root => no. of records it existed:";
        print_r($immediate_children_of_root_count); echo "\n";
        /* ver. 1 strategy
        $root_ancestor = array_unique($root_ancestor);
        */
        // /* ver. 2 strategy
        $root_ancestor = self::get_key_of_arr_with_biggest_value($immediate_children_of_root_count);
        // */
        $immediate_children_of_root = array_keys($immediate_children_of_root);
        
        echo "\n root: "; print_r($root_ancestor);
        // echo "\n immediate_children_of_root: "; print_r($immediate_children_of_root);
        /* START NEW: Per Jen: Please make the PRM record, not the root, but the REP record that appears in the most hierarchies in the original list. 
        I think we might end up doing that with all four applicable methods (basal values and taxon summary, regular and parents). */
        echo "\nImmediate children of root => no. of records it existed FROM THE ORIGINAL RECORDS: ";
        foreach($immediate_children_of_root as $id) {
            echo "\n [$id] => ".$orig_counts_with_left[$id];
        }
        return array('root' => $root_ancestor, 'root label' => 'PRM and REP', 'Selected' => $immediate_children_of_root, 'Label' => 'REP', 'refs' => $refs);
        //'tree' => $final, 'orig_counts_with_left' => $orig_counts_with_left
    }
    private function get_immediate_children_of_root_info($final)
    {
        foreach($final as $tip => $ancestors) {
            if($ancestors) {
                $root_ancestor[] = end($ancestors);
                $no_of_rows = count($ancestors);
                if($no_of_rows > 1) $idx = $ancestors[$no_of_rows-2]; // rows should be > 1 bec if only 1 then there is no child for that root.
                elseif($no_of_rows == 1) $idx = $tip; 
                else exit("\nInvestigate: won't go here...\n");
                $immediate_children_of_root[$idx] = '';
                @$immediate_children_of_root_count[$idx]++;
            }
        }
        return array('immediate_children_of_root' => $immediate_children_of_root, 'immediate_children_of_root_count' => $immediate_children_of_root_count);
    }
    private function get_key_of_arr_with_biggest_value($arr)
    {
        $val = 0;
        foreach($arr as $key => $value) {
            if($value > $val) $ret = $key;
            $val = $value;
        }
        return $ret;
    }
    private function main_lifestage_statMeth($page_id, $predicate)
    {   echo "\n================================================================Method: lifestage & statMeth\npage_id: $page_id | predicate: [$predicate]\n";
        // $path = self::get_txt_path_by_page_id($page_id); //not needed anymore
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        if(!$recs) { echo "\nNo records for [$page_id] [$predicate].\n"; return; }
        echo "\nCandidate records: ".count($recs)."\n"; //print_r($recs); //good debug
        if    ($ret = self::lifestage_statMeth_Step0($recs)) {}
        elseif($ret = self::lifestage_statMeth_Step1($recs)) {}
        elseif($ret = self::lifestage_statMeth_Step23456789($recs)) {}
        else exit("\nsingle simple answer (PRM) if still needed: put REP records in order of value and select one from the middle (arbitrary tie breaks OK)\n");
        if($val = @$ret['recs']) $ret['recs_total'] = count($val);
        if(count($ret['recs']) > 1) exit("\nMore than 1 record, do sort and pick median record.\n");
        return $ret;
    }
    private function lifestage_statMeth_Step0($recs)
    {
        if(count($recs) == 1) return array('label' => 'PRM and REP', 'recs' => $recs, 'step' => 0);
        else return false;
    }
    private function lifestage_statMeth_Step1($recs)
    {
        $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult");
        $final = array();
        foreach($recs as $rec) {
            /* print_r($rec); exit;
            Array(
                [eol_pk] => R143-PK39533097
                [page_id] => 46559130
                [scientific_name] => <i>Enhydra lutris</i>
                ...more fields below
            */
            if(in_array($rec['lifestage'], $possible_adult_lifestage)) {
                $statMethods = array("http://semanticscience.org/resource/SIO_001109", "http://semanticscience.org/resource/SIO_001110", "http://semanticscience.org/resource/SIO_001111");
                if(in_array($rec['statistical_method'], $statMethods)) $final[] = $rec;
            }
        }
        if(!$final) return false;
        else {
            if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => 1);
            elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => 1);
        }
    }
    private function lifestage_statMeth_Step23456789($recs) //steps 2,3,4,5 & 6 7 8 & 9
    {
        /* Step 2,3,4,5 */
        $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult");
        $statMethods = array("http://eol.org/schema/terms/average", "http://semanticscience.org/resource/SIO_001114", "http://www.ebi.ac.uk/efo/EFO_0001444", ""); //in specific order
        $step = 1;
        foreach($statMethods as $method) { $step++;
            $final = array();
            foreach($recs as $rec) {
                if(in_array($rec['lifestage'], $possible_adult_lifestage)) {
                    if($rec['statistical_method'] == $method) $final[] = $rec;
                }
            }
            if($final) {
                if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => $step);
                elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => $step);
            }
        }
        /* Step 6 , 7 , 8 */
        $stages = array("http://purl.obolibrary.org/obo/PO_0007134", "", "http://eol.org/schema/terms/subadult"); //in specific order
        $step = 5;
        foreach($stages as $stage) { $step++;
            $final = array();
            foreach($recs as $rec) {
                if($rec['lifestage'] == $stage) $final[] = $rec;
            }
            if($final) {
                if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => $step);
                elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => $step);
            }
        }
        /* Step 9 */
        $final = array();
        foreach($recs as $rec) {
            $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult", "adult");
            if(in_array($rec['lifestage'], $possible_adult_lifestage)) {
                $statMethods = array("http://semanticscience.org/resource/SIO_001113");
                if(in_array($rec['statistical_method'], $statMethods)) $final[] = $rec;
            }
        }
        if(!$final) return false;
        else {
            if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => 9);
            elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => 9);
        }
        return false;
    }
    private function get_txt_path_by_page_id($page_id, $ext = ".txt")
    {
        $path = self::get_md5_path($this->working_dir, $page_id);
        return $path . $page_id . $ext;
    }
    // private function main_basal_values($page_id, $predicate, $type = 'basal values', $param_isvat = false, $original_nodes = array()) //version 1 - didn't use
    private function main_basal_values($page_id, $predicate, $type = 'basal values', $recs = array()) //for basal values
    {
        $this->original_nodes = array(); //IMPORTANT to initialize especially for multiple calls of this function main_basal_values()
        if($type == 'basal values') {
            $this->parent_basal_values_YesNo = false;
            echo "\n================================================================Method: basal values\npage_id: $page_id | predicate: [$predicate]\n";
            $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate, array('value_uri')); //3rd param array is required_fields
            if(!$recs) {
                echo "\nNo records for [$page_id] [$predicate].\n";
                return false;
            }
        }
        elseif($type == 'parent basal values') { //for parent basal values
            $this->parent_basal_values_YesNo = true;
            
            /* version 1 - didn't use
            $this->original_nodes = $original_nodes;
            $ISVAT = $param_isvat;
            */
            $this->basal_values_parent_recs = $recs;
            $this->original_nodes = $this->original_nodes_parent;
            if(!$recs) {
                echo "\n01. No records for [$page_id] [$predicate].\n";
                return false;
            }
        }
        else {
            exit("\nShould not go here...\n");
        }
        echo "\n recs: ".count($recs);
        $uris = self::get_valueUris_from_recs($recs);
        echo "\n uris: ".count($uris); print_r($uris);
        
        self::set_ancestor_ranking_from_set_of_uris($uris);
        // print_r($this->ancestor_ranking_preferred); exit;
        $ISVAT = self::get_initial_shared_values_ancestry_tree($recs); //initial "shared values ancestry tree" ---> parent left, term right

        $ISVAT = self::sort_ISVAT($ISVAT, 1);
        if(!$ISVAT) return false;
        $info = self::add_new_nodes_for_NotRootParents($ISVAT);
        $new_nodes = $info['new_nodes'];
        echo "\n\nnew nodes 0:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        
        $info['new_nodes'] = self::sort_ISVAT($new_nodes, 2);
        $new_nodes = $info['new_nodes'];
        $roots     = $info['roots'];
        /* good debug
        echo "\n\nnew nodes 1:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        echo "\n\nRoots 1: ".count($roots)."\n"; print_r($roots);
        */
        
        // /* merge
        $info = self::merge_nodes($info, $ISVAT);
        $ISVAT     = $info['new_isvat'];
        $roots     = $info['new_roots'];
        $new_nodes = array();
        // */
        
        // /* for jen: 
        echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
        echo "\n\ninitial shared values ancestry tree: ".count($ISVAT)."\n";
        foreach($ISVAT as $a) echo "\n".$a[0]."\t".$a[1];
        $this->ISVAT = $ISVAT;
        // echo "\n\nnew nodes: ".count($new_nodes)."\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1]; //good debug
        echo "\n\nInitial roots: ".count($roots)."\n"; print_r($roots);
        // */
        
        //for step 1: So, first you must identify the tips- any values that don't appear in the left column. The parents, for step one, will be the values to the left of the tip values.
        $tips = self::get_tips($ISVAT);
        echo "\n tips: ".count($tips);
        foreach($tips as $tip) echo "\n$tip";
        echo "\n-end tips-\n"; //exit;
        
        if(count($tips) <= 5 ) $selected = $tips;
        else { // > 5
            // /* Two new steps from Jen & Katja                                               UNCOMMENT IN REAL OPERATION...
            $ret_from_2new_steps = self::two_new_steps($ISVAT, $roots, $tips);
            $roots = $ret_from_2new_steps['roots'];
            $tips = $ret_from_2new_steps['tips'];
            $ISVAT = $ret_from_2new_steps['ISVAT'];
            echo "\nnew tips: ".count($tips); foreach($tips as $tip) echo "\n".$tip;
            echo "\n";
            // */
            echo "\n\nroots after deletion-steps: ".count($roots)."\n"; print_r($roots);
            
            $step_1 = self::get_step_1($ISVAT, $roots, $tips, 1);
            if(count($step_1) <= 4) $selected = $step_1; //select set 1
            else {
                $step_2 = self::get_step_1($ISVAT, $roots, $step_1, 2);
                if(count($step_2) <= 4) $selected = $step_2; //select set 2
                else {
                    $step_3 = self::get_step_1($ISVAT, $roots, $step_2, 3);
                    if($step_2 == $step_3) {
                        echo "\nSteps 2 and 3 are identical.\n";
                        if(count($step_3) <= 4) $selected = $step_3; //select set 3
                        else {
                            echo "\nSelect root ancestors\n";
                            $selected = $roots;
                        }
                    }
                    else {
                        echo "\nStep 2 and Step 3 are different. Proceed with Step 4\n";
                        $step_4 = self::get_step_1($ISVAT, $roots, $step_3, 4);
                        if($step_3 == $step_4) {
                            echo "\nSteps 3 and 4 are identical.\n";
                            if(count($step_4) <= 4) $selected = $step_4; //select set 4
                            else {
                                echo "\nSelect root ancestors\n";
                                $selected = $roots;
                            }
                        }
                        else {
                            echo "\nStep 3 and Step 4 are different. Proceed with Step 5\n";
                            // exit("\nConstruct Step 5\n");
                            $step_5 = self::get_step_1($ISVAT, $roots, $step_4, 5);
                            if($step_4 == $step_5) {
                                echo "\nSteps 4 and 5 are identical.\n";
                                if(count($step_5) <= 4) $selected = $step_5; //select set 5
                                else {
                                    echo "\nSelect root ancestors\n";
                                    $selected = $roots;
                                }
                            }
                            else {
                                echo "\nStep 4 and Step 5 are different. Proceed with Step 6\n";
                                // exit("\nConstruct Step 6\n");
                                $step_6 = self::get_step_1($ISVAT, $roots, $step_5, 6);
                                if($step_5 == $step_6) {
                                    echo "\nSteps 5 and 6 are identical.\n";
                                    if(count($step_6) <= 4) $selected = $step_6; //select set 6
                                    else {
                                        echo "\nSelect root ancestors\n";
                                        $selected = $roots;
                                    }
                                }
                                else {
                                    echo "\nStep 5 and Step 6 are different. Proceed with Step 7\n";
                                    // exit("\nConstruct Step 7\n");
                                    $step_7 = self::get_step_1($ISVAT, $roots, $step_6, 7);
                                    if($step_6 == $step_7) {
                                        echo "\nSteps 6 and 7 are identical.\n";
                                        if(count($step_7) <= 4) $selected = $step_7; //select set 7
                                        else {
                                            echo "\nSelect root ancestors\n";
                                            $selected = $roots;
                                        }
                                    }
                                    elseif($step_5 == $step_7 && $step_4 == $step_6) {
                                        echo "\nSteps 5 and 7 are identical; Steps 4 and 6 are identical.\n";
                                        if(count($step_7) <= 4) $selected = $step_7; //select set 7
                                        else {
                                            echo "\nSelect root ancestors\n";
                                            $selected = $roots;
                                        }
                                    }
                                    else {
                                        echo "\nStep 6 and Step 7 are different. Proceed with Step 8\n";
                                        exit("\nConstruct Step 8\n");
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        //label PRM and REP if one record, REP if > 1
        if    (count($selected) == 1) $label = 'PRM and REP';
        elseif(count($selected) > 1)  $label = 'REP';
        echo "\n----- label as: [$label]\n";
        $selected = array_values($selected); //reindex array
        
        $ret = array('Selected' => $selected, 'label' => $label);
        // if($type == 'basal values') $ret['recs'] = $recs;    //if you want add 'recs' to the return value
        return $ret;
        /*
        if tips <= 5 SELECT ALL TIPS 
        else
            GET SET_1
            if SET_1 <= 4 SELECT SET_1
            else 
                GET SET_2
                if SET_2 <= 4 SELECT SET_2
                else
                    GET SET_3
                    if SET_2 == SET_3
                        if SET_3 <= 4 SELECT SET_3
                        else SELECT ROOT_ANCESTORS
                    else CONTINUE PROCESS UNTIL all parents of the values in the set are roots, THEN IF <= 4 SELECT THAT SET else SELECT ROOT_ANCESTORS.

        if(WHAT IS SELECTED == 1) label as: 'PRM and REP'
        elseif(WHAT IS SELECTED > 1) label as: 'REP'

        So in our case: page_id: 7662 | predicate: [http://eol.org/schema/terms/Habitat]
        I will be creating new rocords based on 'ROOT_ANCESTORS'.
        */
    }
    private function two_new_steps($ISVAT, $roots, $tips)
    {   echo "\nroots: ".count($roots)." "; print_r($roots);
        /* Important definition of terms by Jen:
        - rows with just one node in them are only needed for orphans (nodes that don't appear anywhere else). These nodes are both tips and roots.
        - tips are nodes that never appear on the left of a two node row
        - roots are nodes that never appear on the right of a two node row
        - nodes that appear both on the left of a two node row and on the right of a two node row are neither roots nor tips
        
            DELETE ALONG WITH CHILDREN
            look for these nodes in the list of roots
            are there any other roots aside from the nodes in this list?
                if not, do nothing
                if so, keep the root nodes that are NOT on this list, and all their descendants. Discard all other nodes 
            the list:
        */
        echo "\n--------------------------------------------DELETE ALONG WITH CHILDREN step: -START-\n";
        $delete_list_1 = array('http://purl.obolibrary.org/obo/ENVO_00000094', 'http://purl.obolibrary.org/obo/ENVO_01000155', 'http://purl.obolibrary.org/obo/ENVO_00000002', 'http://purl.obolibrary.org/obo/ENVO_00000077');
        $delete_list_1[] = "http://purl.obolibrary.org/obo/ENVO_00000358"; $delete_list_1[] = "http://purl.obolibrary.org/obo/ENVO_00000144";
        echo "\nDelete List: "; print_r($delete_list_1);
        if($roots_inside_the_list = self::get_roots_inside_the_list($roots, $delete_list_1)) {
            echo "\nThere are root(s) in the 1st delete-list: ".count($roots_inside_the_list)." "; print_r($roots_inside_the_list);
            echo "\norig 'shared values ancestry tree': ".count($ISVAT)."\n";
            foreach($ISVAT as $a) {
                if(              in_array($a[0], $roots_inside_the_list)) {}
                elseif(!$a[0] && in_array($a[1], $roots_inside_the_list)) {}
                else $new_isvat[] = $a;
            }
            echo "\ntrimmed shared ancestry tree: ".count($new_isvat); foreach($new_isvat as $a) echo "\n".$a[0]."\t".$a[1];
            $roots = array_diff($roots, $roots_inside_the_list);
            echo "\n\nnew roots: ".count($roots)."\n"; print_r($roots);
            /* working; but not needed here coz we're not adding roots here
            $cleaned = self::remove_undesirable_roots($roots);
            if($cleaned != $roots) {
                $roots = $cleaned;
                echo "\n\nnew roots (removed non-root): ".count($roots)."\n"; print_r($roots);
            }*/
        }
        else {
            echo "\nAll root nodes are not on the list. Keeping all root nodes and all descendants. Do nothing.\n";
            $new_isvat = $ISVAT;
        }
        echo "\n-------------------------------------------- -END-\n";
        /*DELETE, BUT KEEP THE CHILDREN
            look for these nodes in the list of roots
            remove them. Their immediate children are now roots.
            the list:
        (it's OK if occasionally this leaves you with no records.)
        */
        echo "\n--------------------------------------------DELETE, BUT KEEP THE CHILDREN step: -START-\n";
        $delete_list_2 = array('http://purl.obolibrary.org/obo/ENVO_01001305', 'http://purl.obolibrary.org/obo/ENVO_00002030', 'http://purl.obolibrary.org/obo/ENVO_01000687');
        $delete_list_2[] = "http://purl.obolibrary.org/obo/ENVO_00001995"; $delete_list_2[] = "http://purl.obolibrary.org/obo/ENVO_00002227";
        echo "\nDelete List: "; print_r($delete_list_2);
        echo "\n\nroots: ".count($roots)."\n"; print_r($roots);
        if($roots_inside_the_list = self::get_roots_inside_the_list($roots, $delete_list_2)) {
            echo "\nThere are root(s) found in the 2nd delete-list: ".count($roots_inside_the_list)." "; print_r($roots_inside_the_list);
            //1. get $temp_tree_deleted, will use this in deciding which can be root, which can be removed.
            foreach($new_isvat as $a) {
                if(!in_array($a[0], $roots_inside_the_list)) $temp_tree_deleted[] = $a;
                else $possible_root_or_deleted[$a[1]] = '';
            }
            $possible_root_or_deleted = array_keys($possible_root_or_deleted);
            echo "\n-----------Diagnostics -START- -----------\n*Nodes in question: "; print_r($possible_root_or_deleted);
            $all_left_of_tree = self::get_one_side_of_tree($temp_tree_deleted, 'left');
            $all_right_of_tree = self::get_one_side_of_tree($temp_tree_deleted, 'right');
            //2. decide which can be root and which can be removed.
            $orphans = array(); $add_2_roots = array(); $neither_root_nor_tip = array(); //initialization
            foreach($possible_root_or_deleted as $node) {
                if(!in_array($node, $all_left_of_tree) && !in_array($node, $all_right_of_tree)) {
                    $orphans[] = $node;
                    $add_2_roots[$node] = '';
                }
                elseif(in_array($node, $all_left_of_tree) && in_array($node, $all_right_of_tree)) $neither_root_nor_tip[] = $node;
                elseif(in_array($node, $all_left_of_tree) && !in_array($node, $all_right_of_tree)) $add_2_roots[$node] = '';
            }
            foreach($new_isvat as $a) {
                if(in_array($a[0], $roots_inside_the_list)) {
                    if(in_array($a[1], $orphans)) $new_isvat_2[] = array("", $a[1]);
                }
                else $new_isvat_2[] = $a;
            }
            
            $add_2_roots = array_keys($add_2_roots);
            echo "*Will become orphan/single rows: "; print_r($orphans);
            echo "*Will be added to roots: "; print_r($add_2_roots);
            // /* working; moving back and forth as first - DEFINITELY UNCOMMENT
            $add_2_roots = self::remove_undesirable_roots($add_2_roots, $delete_list_2);
            // */
            echo "*Will be added to roots (removed non-root): "; print_r($add_2_roots);
            
            echo "*Neither root nor tip: "; print_r($neither_root_nor_tip);
            echo "-----------Diagnostics -END- -----------\n";
            
            echo "\ntrimmed shared ancestry tree: ".count($new_isvat_2); foreach($new_isvat_2 as $a) echo "\n".$a[0]."\t".$a[1];
            $roots = array_diff($roots, $roots_inside_the_list);
            if($add_2_roots) {
                echo "\nHas additional roots.\n";
                $roots = array_merge($roots, $add_2_roots);
            }
            else echo "\nNo additional roots.\n";
            if($roots_inside_the_list) echo "\nRoots got reduced";
            echo "\n\nnew roots: ".count($roots)."\n"; print_r($roots);
        }
        else {
            echo "\nNo roots inside the list. Do nothing.\n";
            $new_isvat_2 = $new_isvat;
        }
        echo "\n-------------------------------------------- -END-\n";
        return array('roots' => $roots, 'tips' => self::get_tips($new_isvat_2), 'ISVAT' => $new_isvat_2);
    }
    private function get_one_side_of_tree($tree, $side)
    {
        foreach($tree as $a) {
            if    ($side == 'left') $final[$a[0]] = '';
            elseif($side == 'right') $final[$a[1]] = '';
        }
        return array_keys($final);
    }
    private function get_roots_inside_the_list($roots, $list)
    {
        $roots_inside_the_list = array();
        foreach($roots as $root) {
            if(in_array($root, $list)) $roots_inside_the_list[] = $root;
        }
        return $roots_inside_the_list;
    }
    private function get_tips($isvat)
    {
        foreach($isvat as $a) {
            $left[$a[0]] = '';
            $right[$a[1]] = '';
        }
        $right = array_keys($right);
        foreach($right as $node) {
            if(!isset($left[$node])) $final[$node] = '';
        }
        $final = array_keys($final);
        asort($final);
        return $final;
    }
    private function get_step_1($isvat, $roots, $tips, $step_no)
    {   /*  - find all tips
            - find all nodes that are parents of tips
            - in each case, check whether either the tip or the parent is a root
                -- if either the tip or the parent is a root, put the tip in set 1
                -- if neither the tip nor the parent is a root, put the parent in set 1
            - (deduplicate set 1) */
        foreach($isvat as $a) {
            $parent_of_right[$a[1]] = $a[0];
        }
        foreach($tips as $tip) {
            if($parent = @$parent_of_right[$tip]) {
                if(in_array($tip, $roots) || in_array($parent, $roots)) $final[$tip] = '';
                if(!in_array($tip, $roots) && !in_array($parent, $roots)) $final[$parent] = '';
            }
            else {
                if(in_array($tip, $roots)) $final[$tip] = '';
            }
        }
        $final = array_keys($final);
        asort($final);
        $final = array_values($final); //reindex key
        
        //optional display
        echo "\nStep $step_no:".count($final)."\n";
        foreach($final as $a) echo "\n".$a;
        echo "\n-end Step $step_no-\n";
        
        return $final;
    }
    private function remove_undesirable_roots($roots, $delete_list = array())
    {
        $temp = array();
        foreach($roots as $root) {
            if($parents = @$this->parents_of[$root]) {
                $parents = array_diff($parents, $delete_list);
                if($parents) {}
                else $temp[$root] = '';
            }
            else $temp[$root] = '';
        }
        return array_keys($temp);
    }
    private function merge_nodes($info, $ISVAT)
    {
        $new_nodes = $info['new_nodes'];
        $roots     = $info['roots'];
        $roots     = self::remove_undesirable_roots($roots); //new step to remove un-desirable roots
        
        $new_isvat = array_merge($ISVAT, $new_nodes);
        $new_isvat = self::sort_ISVAT($new_isvat, 3);
        $new_isvat = self::remove_orphans_that_exist_elsewhere($new_isvat);
        
        $new_roots = $roots;
        foreach($new_isvat as $a) {
            if(!$a[0]) {
                /* orig
                $new_roots[] = $a[1];
                */
                // /* new: to catch those who became roots but not suppose to be
                // if($this->parent_basal_values_YesNo) {
                    if(@$this->parents_of[$a[1]]) {}
                    else $new_roots[] = $a[1];
                // }
                // */
            }
        }
        asort($new_roots);
        
        //scan new isvat for new roots
        foreach($new_isvat as $a) {
            if(!$a[0]) continue;
            if(@$this->parents_of[$a[0]]) {} // echo " - not root, has parents ".count($arr);
            else $new_roots[] = $a[0];
        }
        $new_roots = array_unique($new_roots);
        $new_roots = array_filter($new_roots); //remove null values
        asort($new_roots);
        return array('new_roots' => $new_roots, 'new_isvat' => $new_isvat);
    }
    private function remove_orphans_that_exist_elsewhere($isvat) //that is remove the orphan row
    {   //first get all non-orphan rows
        foreach($isvat as $a) {
            if($a[0]) {
                $left[$a[0]] = '';
                $right[$a[1]] = '';
            }
        }
        //if orphan $a[1] exists elsewhere then remove that orphan row
        //The way I was thinking of documenting, it wouldn't need to be listed as an orphan if it also appears in any relationship pair.
        foreach($isvat as $a) {
            if(!$a[0] && ( isset($left[$a[1]]) || isset($right[$a[1]]) ) ) {
                echo "\n === $a[0] --- $a[1] === remove orphan coz it exists elsewhere \n"; //the orphan row ENVO_00000446 was removed here...
            }
            else $final[] = $a;
        }
        return $final;
    }
    private function sort_ISVAT($arr, $num) //also remove parent nodes where there is only one child. Make child an orphan.
    {
        if(!$arr) return array();
        rsort($arr);
        foreach($arr as $a) {
            @$temp[$a[0]][$a[1]] = ''; //to be used in $totals
            $right_cols[$a[1]] = '';
            $temp2[$a[0]] = $a[1];
            $left_cols[$a[0]] = '';
        }
        asort($temp);
        foreach($temp as $key => $value) $totals[$key] = count($value);
        print_r($totals);

        $discard_parents = array(); echo "\n-------------------- [$num]\n";
        foreach($totals as $key => $total_children) {
            if($total_children == 1) {
                echo "\n $key: with 1 child ";
                if(isset($right_cols[$key])) echo " -- appears in a relationship pair (right)";
                /* "Ancestors can be removed if they are parents of only one node BUT that node must NOT be an original node" THIS IS WRONG RULE!!!
                elseif(isset($this->original_nodes[$temp2[$key]])) {
                    echo "\nxxx $key --- ".@$temp2[$key]." parent of just 1 node BUT an original node\n";
                }
                */
                // /* THIS IS THE CORRECT RULE
                elseif(isset($this->original_nodes[$key])) {
                    echo "\nxxx $key --- parent of just 1 node BUT ancestor is an original node\n";
                }
                // */
                else $discard_parents[] = $key;
            }
        }
        echo "\n discarded_parents:"; print_r($discard_parents); echo "\n-----\n";
        
        $final = array();
        foreach($arr as $a) {
            if(in_array($a[0], $discard_parents)) $final[] = array("", $a[1]);
            else                                  $final[] = array($a[0], $a[1]);
        }
        asort($final);
        $final = array_unique($final, SORT_REGULAR);
        return $final;
    }
    function generate_page_id_txt_files_MySQL()
    {
        $file_cnt = 1;
        $file_write = $this->main_dir."/MySQL_append_files/traits_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
        
        self::working_dir();
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r'); //11,276,098 rows in traits.csv | 11,276,097 in MySQL
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++;
            if(($i % 100000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /* Fields from the [traits_all_201905.zip]:
                    eol_pk,page_id,resource_pk,resource_id,source,scientific_name,predicate,object_page_id,value_uri,normal_measurement,normal_units_uri,
                    normal_units,measurement,units_uri,units,literal
                It is missing these 4 fields from the carnivora sample:
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [target_scientific_name] => 
                Array(
                    [eol_pk] => R788-PK74508166
                    [page_id] => 1180180
                    [resource_pk] => 
                    [resource_id] => 694
                    [source] => 
                    [scientific_name] => <i>Zygodontomys brevicauda</i>
                    [predicate] => http://eol.org/schema/terms/ExtinctionStatus
                    [object_page_id] => 
                    [value_uri] => http://eol.org/schema/terms/extant
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [normal_units] => 
                    [measurement] => 
                    [units_uri] => 
                    [units] => 
                    [literal] => http://eol.org/schema/terms/extant
                )*/
                if(($i % 1000000) == 0) {
                    echo "\nSaving...".number_format($i);
                    fclose($WRITE);
                    $file_cnt++;
                    $file_write = $this->main_dir."/MySQL_append_files/traits_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
                }
                self::write_report($rec, $fields, $WRITE);
                // if($i >= 100) break; //debug only
            }
        }
        fclose($WRITE);
        fclose($file); exit("\n\nTraits to MySQL DONE.\n\n");
    }
    private function write_report($save_rec, $fields, $fileH)
    {
        $arr = array();
        foreach($fields as $f) $arr[] = @$save_rec[$f];
        fwrite($fileH, implode("\t", $arr)."\n");
    }
    function generate_page_id_txt_files() /* you MUST just save this to MySQL table. Index fields: page_id, predicate */
    {   exit;
        self::working_dir();
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r'); //11,276,098 rows in traits.csv
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [eol_pk] => R96-PK42724728
                    [page_id] => 328673
                    [scientific_name] => <i>Panthera pardus</i>
                    [resource_pk] => M_00238837
                    [predicate] => http://eol.org/schema/terms/Present
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://www.worldwildlife.org/publications/wildfinder-database
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => http://eol.org/schema/terms/Southern_Zanzibar-Inhambane_coastal_forest_mosaic
                    [literal] => http://eol.org/schema/terms/Southern_Zanzibar-Inhambane_coastal_forest_mosaic
                    [measurement] => 
                    [units] => 
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [resource_id] => 20
                    )
                */
                $txt_file = self::get_txt_path_by_page_id($rec['page_id']);
                // /* normal operation ----------------------------------------------------------------------- working OK
                if(file_exists($txt_file)) {
                    // echo "\nAppend [$txt_file] ";
                    $WRITE = fopen($txt_file, 'a');
                    fwrite($WRITE, implode("\t", $line)."\n");
                    fclose($WRITE);
                }
                else {
                    // echo "\nCreated [$txt_file] ";
                    $WRITE = fopen($txt_file, 'w');
                    fwrite($WRITE, implode("\t", $fields)."\n");
                    fwrite($WRITE, implode("\t", $line)."\n");
                    fclose($WRITE);
                }
                // */

                /* use if u want to delete txt files ----------------------------------------------------------------------- working OK
                if(file_exists($txt_file)) {
                    echo "\nFound [$txt_file] ";
                    if(unlink($txt_file)) echo "- deleted";
                    else                  echo "- not deleted";
                }
                */
                // if($i >= 1000) break; //debug only
            }
        }
        fclose($file); exit("\n\nText file generation DONE.\n\n");
    }
    private function get_md5_path($path, $taxonkey)
    {
        $md5 = md5($taxonkey);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($path . $cache1)) mkdir($path . $cache1);
        if(!file_exists($path . "$cache1/$cache2")) mkdir($path . "$cache1/$cache2");
        return $path . "$cache1/$cache2/";
    }
    private function assemble_recs_for_page_id_from_text_file($page_id, $predicate, $required_fields = array())
    {
        $sql = "SELECT t.* from SDR.traits t WHERE t.page_id = $page_id AND t.predicate = '".$predicate."'";
        $result = $this->mysqli->query($sql);
        $recs = array();
        while($result && $rec=$result->fetch_assoc()) {
            // print_r($rec); exit;
            /* e.g. Method: basal values
            Array(
                [eol_pk] => R512-PK71412778
                [page_id] => 46559217
                [resource_pk] => 
                [resource_id] => 413
                [source] => http://eol.org/pages/1053894
                [scientific_name] => <i>Vulpes lagopus</i> (Linnaeus 1758)
                [predicate] => http://eol.org/schema/terms/Habitat
                [object_page_id] => 
                [value_uri] => http://purl.obolibrary.org/obo/ENVO_00000112
                [normal_measurement] => 
                [normal_units_uri] => 
                [normal_units] => 
                [measurement] => 
                [units_uri] => 
                [units] => 
                [literal] => http://purl.obolibrary.org/obo/ENVO_00000112
            )*/
            if($required_fields) {
                foreach($required_fields as $required_fld) {
                    if(!$rec[$required_fld]) continue; //e.g. value_uri
                    else $recs[] = $rec;
                }
            }
            else $recs[] = $rec;
            $this->original_nodes[$rec['value_uri']] = '';
            $this->original_nodes_parent[$rec['value_uri']] = '';
        }
        return $recs;
    }
    private function assemble_recs_for_page_id_from_text_file_OLD($page_id, $predicate, $required_fields = array())
    {
        $recs = array();
        $txt_file = self::get_txt_path_by_page_id($page_id); // echo "\n$txt_file\n";
        if(!file_exists($txt_file)) { // echo "\nFile does not exist.\n";
            return false;
        }
        $i = 0;
        foreach(new FileIterator($txt_file) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /* Array( old during development with Jen
                    [page_id] => 46559197
                    [scientific_name] => <i>Arctocephalus tropicalis</i>
                    [predicate] => http://eol.org/schema/terms/Present
                    [value_uri] => http://www.marineregions.org/gazetteer.php?p=details&id=australia
                )*/
                /*Array(
                    [eol_pk] => R143-PK39533505
                    [page_id] => 46559197
                    [scientific_name] => <i>Arctocephalus tropicalis</i>
                    [resource_pk] => 17255
                    [predicate] => http://eol.org/schema/terms/WeaningAge
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://genomics.senescence.info/species/entry.php?species=Arctocephalus_tropicalis
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => 
                    [literal] => 
                    [measurement] => 239
                    [units] => http://purl.obolibrary.org/obo/UO_0000033
                    [normal_measurement] => 0.6543597746702533
                    [normal_units_uri] => http://purl.obolibrary.org/obo/UO_0000036
                    [resource_id] => 50
                )*/
                if($predicate == $rec['predicate']) {
                    if($required_fields) {
                        foreach($required_fields as $required_fld) {
                            if(!$rec[$required_fld]) continue; //e.g. value_uri
                            else $recs[] = $rec;
                        }
                    }
                    else $recs[] = $rec;
                }
                $this->original_nodes[$rec['value_uri']] = '';
                $this->original_nodes_parent[$rec['value_uri']] = '';
            }
        }
        return $recs;
    }
    private function initialize()
    {
        self::working_dir();
    }
    private function initialize_basal_values()
    {
        self::working_dir();
        self::generate_terms_values_child_parent_list($this->file['parent child']['path_habitat']);
        self::generate_terms_values_child_parent_list($this->file['parent child']['path_geoterms']);
        self::generate_preferred_child_parent_list();
    }
    private function add_new_nodes_for_NotRootParents($list)
    {   //1st step: get unique parents
        foreach($list as $rec) {
            /*Array(
                [0] => http://www.geonames.org/6255151
                [1] => http://www.marineregions.org/gazetteer.php?p=details&id=australia
            )*/
            $unique[$rec[0]] = '';
        }
        //2nd step: check if parent is not root (meaning has parents), if yes: get parent and add the new node:
        $recs = array(); $roots = array();
        foreach(array_keys($unique) as $child) {
            // echo "\n$child: ";
            if($arr = @$this->parents_of[$child]) { // echo " - not root, has parents ".count($arr);
                foreach($arr as $new_parent) {
                    if($new_parent) $recs[] = array($new_parent, $child);
                }
            }
            else $roots[] = $child; // echo " - already root";
        }
        return array('roots' => $roots, 'new_nodes' => $recs);
    }
    private function get_valueUris_from_recs($recs)
    {
        $uris = array();
        foreach($recs as $rec) $uris[] = $rec['value_uri'];
        return $uris;
    }
    private function get_initial_shared_values_ancestry_tree($recs)
    {
        $final = array(); $i = 0;
        $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE);
        foreach($recs as $rec) { $i++;
            $term = $rec['value_uri'];
            /* old ways
            $parent = self::get_parent_of_term($term, $i);
            $final[] = array($parent, $term);
            */
            $pairs = self::create_pairs_from_this_term($term, $i);
            $final = array_merge($final, $pairs);
        }
        return $final;
    }
    /* may not be needed anymore...
    function start_ok()
    {   self::initialize();
        $uris = array(); //just during development --- assign uris here...
        self::set_ancestor_ranking_from_set_of_uris($uris);
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=australia";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4366";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4364";
        $terms[] = "http://www.geonames.org/2186224";
        $terms[] = "http://www.geonames.org/3370751";                               //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1914";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1904";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1910";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4276";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4365";
        $terms[] = "http://www.geonames.org/953987";
        $terms[] = "http://www.marineregions.org/mrgid/1914";
        $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE);
        foreach($terms as $term) self::get_parent_of_term($term);
        exit("\nend 01\n");
    }*/
    private function set_ancestor_ranking_from_set_of_uris($uris)
    {
        $final = array(); $final_preferred = array();
        foreach($uris as $term) {
            if(!$term) continue;
            if($preferred_terms = @$this->preferred_names_of[$term]) {
                // echo "\nThere are preferred term(s):\n";
                // print_r($preferred_terms);
                foreach($preferred_terms as $pterm) {
                    @$final_preferred[$pterm]++;
                    // echo "\nparent(s) of $pterm:";
                    if($parents = @$this->parents_of[$pterm]) {
                        // print_r($parents);
                        foreach($parents as $parent) @$final[$parent]++;
                    }
                    // else echo " -- NO parent";
                }
            }
            else { //no preferred term
                if($parents = @$this->parents_of[$term]) {
                    foreach($parents as $parent) @$final[$parent]++;
                }
                // else exit("\n\nHmmm no preferred and no immediate parent for term: [$term]\n\n"); //seems acceptable
            }
        }//end main
        arsort($final);
        $this->ancestor_ranking_withCounts = $final;
        $final = array_keys($final);
        $this->ancestor_ranking = $final;

        arsort($final_preferred);
        $this->ancestor_ranking_preferred_withCounts = $final_preferred;
        print_r($final_preferred); //exit;
        $final_preferred = array_keys($final_preferred);
        $this->ancestor_ranking_preferred = $final_preferred;
    }
    private function get_rank_most_parent($parents, $preferred_terms = array())
    {
        if(!$preferred_terms) {
            echo "\nancestor_ranking_preferred: "; print_r($this->ancestor_ranking_preferred_withCounts);
            
            //1st option: if any is a preferred name then choose that
            foreach($this->ancestor_ranking_preferred as $parent) {
                if(in_array($parent, $parents)) {
                    $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                    echo "\nwent here 01\n";
                    return $parent;
                }
            }
        }
        else {
            // don't do THIS if preferred + parents are all inside $this->ancestor_ranking
            $all_inside = true;
            $temp = array_merge($parents, $preferred_terms);
            foreach($temp as $id) {
                if(!in_array($id, $this->ancestor_ranking)) $all_inside = false;
            }
            if(!$all_inside) {
                //THIS:
                foreach($this->ancestor_ranking as $parent) {
                    if(in_array($parent, $preferred_terms)) {
                        $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                        echo "\nwent here 02\n";
                        return $parent;
                    }
                }
            }
            if(count($preferred_terms) == 1 && in_array($preferred_terms[0], $this->ancestor_ranking) && in_array($preferred_terms[0], $this->ancestor_ranking_preferred)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $preferred_terms[0]."\n"); fclose($WRITE);
                echo "\nwent here 03\n";
                return $preferred_terms[0];
            }
        }
        
        //2nd option
        echo "\nlast option: ancestor_ranking "; print_r($this->ancestor_ranking_withCounts);
        $inclusive = array_merge($parents, $preferred_terms);
        foreach($this->ancestor_ranking as $parent) {
            if(in_array($parent, $inclusive)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                echo "\nwent here 04\n";
                return $parent;
            }
        }
        
        echo "\nInvestigate parents not included in ranking... weird...\n";
        print_r($inclusive);
        exit("\n===============\n");
    }
    private function create_pairs_from_this_term($term, $num) //this will replace get_parent_of_term()
    {
        echo "\n--------------------------------------------------------------------------------------------------------------------------------------- \n"."term in question: [$term] $num:\n";
        $pairs = array();
        if($preferred_terms = @$this->preferred_names_of[$term]) {
            echo "\nThere are preferred term(s):\n";
            print_r($preferred_terms);
            foreach($preferred_terms as $preferred) {
                if($val = self::is_pair_OK($preferred, $term)) $pairs[] = $val;
            }
            foreach($preferred_terms as $preferred) {
                echo "\nparent(s) of $preferred:\n";
                if($parents = @$this->parents_of[$preferred]) {
                    print_r($parents);
                    foreach($parents as $parent) {
                        if($val = self::is_pair_OK($parent, $preferred)) $pairs[] = $val;
                    }
                }
                else echo " -- NO parent";
            }
        }
        else {
            echo "\nThere is NO preferred term\n";
            if($immediate_parents = @$this->parents_of[$term]) {
                echo "\nThere are immediate parent(s) for term in question:\n";
                print_r($immediate_parents);
                foreach($immediate_parents as $parent) {
                    if($val = self::is_pair_OK($parent, $term)) $pairs[] = $val;
                }
            }
        }
        foreach($pairs as $a) echo "\n".$a[0]." - ".$a[1];
        return $pairs;
    }
    private function is_pair_OK($parent_orig, $child_orig)
    {
        
        $parent = self::remove_protocol($parent_orig);
        $child = self::remove_protocol($child_orig);

        // causes infinite loop
        // $parent = $parent_orig;
        // $child = $child_orig;
        
        
        if($parent == $child) {
            if($parent_orig != $child_orig) $this->debug[] = "Investigate: [$parent_orig] [$child_orig] meaning diff protocol";
            return false;
        }
        return array($parent_orig, $child_orig);
    }
    private function remove_protocol($url)
    {
        $arr = explode("://", $url);
        return $arr[1];
    }
    /*
    private function get_parent_of_term($term, $num) -- replaced by create_pairs_from_this_term()
    {
        echo "\n--------------------------------------------------------------------------------------------------------------------------------------- \n"."term in question: [$term] $num:\n";
        if($preferred_terms = @$this->preferred_names_of[$term]) {
            echo "\nThere are preferred term(s):\n";
            print_r($preferred_terms);
            foreach($preferred_terms as $term) {
                echo "\nparent(s) of $term:\n";
                if($parents = @$this->parents_of[$term]) {
                    print_r($parents);
                    $chosen = self::get_rank_most_parent($parents, $preferred_terms);
                    echo "\nCHOSEN PARENT: ".$chosen."\n";
                    return $chosen;
                }
                else echo " -- NO parent";
            }
        }
        else {
            echo "\nThere is NO preferred term\n";
            if($immediate_parents = @$this->parents_of[$term]) {
                echo "\nThere are immediate parent(s) for term in question:\n";
                print_r($immediate_parents);
                $chosen = self::get_rank_most_parent($immediate_parents);
                echo "\nCHOSEN PARENT*: ".$chosen."\n";
                return $chosen;
            }
        }
        // $this->preferred_names_of[$term]    function generate_preferred_child_parent_list
        // $this->parents_of[$term]            function generate_terms_values_child_parent_list
    }
    */
    private function generate_preferred_child_parent_list()
    {
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym']['path'], $this->download_options);
        $file = fopen($temp_file, 'r'); $i = 0;
        $fields = $this->file['preferred synonym']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /* Array(
                    [preferred] => http://marineregions.org/mrgid/19161
                    [deprecated] => http://marineregions.org/gazetteer.php?p=details&id=19161
                )*/
                $this->preferred_names_of[$rec['deprecated']][] = $rec['preferred'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    private function get_ancestry_of_term($page_id)
    {
        $final = array(); $final2 = array();
        if($parent_ids = @$this->terms_values_child_parent_list[$page_id]) {
            foreach($parent_ids as $temp_id) {
                while(true) {
                    if($parent_ids2 = @$this->terms_values_child_parent_list[$temp_id]) {
                        foreach($parent_ids2 as $temp_id2) {
                            while(true) {
                                if($parent_ids3 = @$this->terms_values_child_parent_list[$temp_id2]) {
                                    foreach($parent_ids3 as $temp_id3) {
                                        $final['L3'][] = $temp_id3;
                                        $final2[$temp_id3] = '';
                                        $temp_id2 = $temp_id3;
                                    }
                                }
                                else break;
                            }
                            $final['L2'][] = $temp_id2;
                            $final2[$temp_id2] = '';
                            $temp_id = $temp_id2;
                        }
                    }
                    else break;
                }
                $final['L1'][] = $temp_id;
                $final2[$temp_id] = '';
                $page_id = $temp_id;
            }
        }
        return array($final, array_keys($final2));
        /*
        $final = array();
        $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->terms_values_child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
        */
    }
    private function generate_terms_values_child_parent_list($file = false)
    {
        if(!$file) exit("\nUndefined file: [$file]\n");
        $temp_file = Functions::save_remote_file_to_local($file, $this->download_options);
        $file = fopen($temp_file, 'r');
        $i = 0;
        $fields = $this->file['parent child']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /* Array(
                    [parent] => ﻿http://purl.obolibrary.org/obo/ENVO_00000111
                    [child] => http://purl.obolibrary.org/obo/ENVO_01000196
                )*/
                $this->parents_of[$rec['child']][] = $rec['parent'];
                $this->children_of[$rec['parent']][] = $rec['child'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    /*
    function start_v1()
    {
        self::working_dir();
        $this->child_parent_list = self::generate_child_parent_list();
        // start tests...
        $predicate = "http://reeffish.org/occursIn";
        $predicate = "http://eol.org/schema/terms/Present";
        $similar_terms = self::given_predicate_get_similar_terms($predicate);
        // print_r($similar_terms); exit;
        
        self::print_taxon_and_ancestry($similar_terms);
        self::given_predicates_get_values_from_traits_csv($similar_terms);
        exit("\n-end tests-\n");
        // end tests
        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
        // remove temp dir
        // un-comment in real operation
        recursive_rmdir($this->main_paths['temp_dir']);
        echo ("\n temporary directory removed: " . $this->main_paths['temp_dir']);
        //
    }
    */
    private function generate_child_parent_list()
    {
        $file = fopen($this->main_paths['archive_path'].'/parents.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [child] => 47054812
                    [parent] => 7662
                )*/
                $final[$rec['child']] = $rec['parent'];
            }
        }
        fclose($file);
        return $final;
    }
    private function print_taxon_and_ancestry($preds)
    {
        $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Taxa (with ancestry) having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "ancestry"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    ...more fields below
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    $ancestry = self::get_ancestry_using_page_id($rec['page_id']);
                    if(!isset($printed_already[$rec['page_id']])) {
                        fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], implode("|", $ancestry)))."\n");
                        $printed_already[$rec['page_id']] = '';
                    }
                }
            }
        }
        fclose($file);
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
    }
    private function given_predicates_get_values_from_traits_csv($preds)
    {
        $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Records from traits.csv having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "predicate", "value_uri OR literal"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) {
                $fields = $line;
                print_r($fields); //exit;
            }
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    [scientific_name] => <i>Tremarctos ornatus</i>
                    ...more fields below
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    // echo "\n".self::get_value($rec);
                    // print_r($rec); //exit;
                    fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], $rec['predicate'], self::get_value($rec)))."\n");
                }
            }
        }
        fclose($file);
    }
    private function get_ancestry_using_page_id($page_id)
    {
        $final = array(); $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
    }
    private function get_value($rec)
    {
        if($val = @$rec['value_uri']) return $val;
        if($val = @$rec['literal']) return $val;
    }
    private function setup_working_dir()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "traits.csv", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        return $paths;
    }
    private function working_dir()
    {
        if(Functions::is_production()) {
            if(!($info = self::setup_working_dir())) return;
            $this->main_paths = $info;
        }
        else { //local development only
            /*
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/carnivora_sample',
                          'temp_dir'     => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/');
            */
            $info = Array('archive_path' => '/Volumes/AKiTiO4/web/cp/summary_data_resources/trait_bank',
                          'temp_dir'     => '/Library/WebServer/Documents/eol_php_code/tmp/not being used/'); //this field not being used ATM.
            $this->main_paths = $info;
        }
    }
    private function given_predicate_get_similar_terms($pred) //used during initial report to Jen
    {
        $final = array();
        $final[$pred] = ''; //processed predicate is included
        //from 'parent child':
        $temp_file = Functions::save_remote_file_to_local($this->file['parent child'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[0] == $pred) $final[$line[1]] = '';
        }
        fclose($file); unlink($temp_file);
        //from 'preferred synonym':
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[1] == $pred) $final[$line[0]] = '';
        }
        fclose($file); unlink($temp_file);
        $final = array_keys($final);
        //start write
        $WRITE = fopen($this->report_file, 'w');
        fwrite($WRITE, "REPORT FOR PREDICATE: $pred\n\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fwrite($WRITE, "Similar terms from [terms relationship files]:\n\n");
        foreach($final as $url) fwrite($WRITE, $url . "\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
        //end write
        return $final;
    }
    private function investigate_traits_csv()
    {
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit;
                /*Array(
                    [eol_pk] => R96-PK42724728
                    [page_id] => 328673
                    [scientific_name] => <i>Panthera pardus</i>
                    ...more fields below
                )*/
                // if($rec['target_scientific_name']) print_r($rec);
                // if($rec['lifestage']) print_r($rec);
                if($rec['object_page_id']) print_r($rec);
            }
        }
    }
    /* not used at the moment
    private function choose_term_type($predicate)
    {
        switch ($predicate) {
            case "http://eol.org/schema/terms/Habitat":
                return 'path_habitat'; //break;
            case "http://eol.org/schema/terms/Present":
                return 'path_geoterms'; //break;
            default:
                exit("\nPredicate [$predicate] not yet assigned to what term_type.\n");
        }
    }
    */

    /* report for Jen
    self::parse_DH();
    $WRITE = fopen($this->report_file, 'w');
    $i = 0;
    foreach(new FileIterator($this->info_path['archive_path'].$this->info_path['tables']['taxa']) as $line_number => $line) {
        $line = explode("\t", $line); $i++;
        if($i == 1) $fields = $line;
        else {
            if(!$line[0]) break;
            $rec = array(); $k = 0;
            foreach($fields as $fld) {
                $rec[$fld] = $line[$k]; $k++;
            }
            // print_r($rec); exit;
            if($page_id = $rec['EOLid']) {
                $ancestry = self::get_ancestry_via_DH($page_id);
                fwrite($WRITE, $page_id . "\t" . implode(" | ", $ancestry) . "\n");
            }
        }
    }
    fclose($WRITE); exit(); exit("\n-end report-\n");
    */
    /* another report for Jen
    self::initialize();
    $i = 0;
    $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
    while(($line = fgetcsv($file)) !== FALSE) {
        $i++; if($i == 1) $fields = $line;
        else {
            $rec = array(); $k = 0;
            foreach($fields as $fld) {
                $rec[$fld] = $line[$k]; $k++;
            }
            // print_r($rec); exit;
            if($page_id = @$rec['object_page_id'])  $final[$page_id] = '';
            if($page_id = @$rec['page_id'])         $final[$page_id] = '';
        }
    }
    $WRITE = fopen($this->report_file, 'w');
    $final = array_keys($final);
    foreach($final as $page_id) {
        $ancestry = self::get_ancestry_via_DH($page_id);
        fwrite($WRITE, $page_id . "\t" . implode(" | ", $ancestry) . "\n");
    }
    fclose($WRITE); exit("\n-end report-\n");
    */

        /*
    Hi Jen, we are now just down to 1 discrepancy. But I think (hopefully) the last one is just something you've missed doing it manually.
    But more importantly, let me share my algorithm how I chose the parent. Please review closely and suggest improvement or even revise if needed.
    I came up with this using our case scenario for page_id 46559197 and your explanations why you chose your parents.
    Like what I said it came down to now just 1 discrepancy.

    I process each of the 12 terms, one by one.
        http://www.marineregions.org/gazetteer.php?p=details&id=australia
        http://www.marineregions.org/gazetteer.php?p=details&id=4366
        http://www.marineregions.org/gazetteer.php?p=details&id=4364
        http://www.geonames.org/2186224
        http://www.geonames.org/3370751
        http://www.marineregions.org/gazetteer.php?p=details&id=1914
        http://www.marineregions.org/gazetteer.php?p=details&id=1904
        http://www.marineregions.org/gazetteer.php?p=details&id=1910
        http://www.marineregions.org/gazetteer.php?p=details&id=4276
        http://www.marineregions.org/gazetteer.php?p=details&id=4365
        http://www.geonames.org/953987
        http://www.marineregions.org/mrgid/1914

    1.  First I get the preferred term(s) of the term in question. 
        Case A: If there are any: e.g. (pref1, pref2) Mostly only 1 preferred term.
            I get the immediate parent(s) each of the preferred terms. 
            e.g. pref1_parent1, pref1_parent2, pref1_parent3

        Case B: If there are NO preferred term(s)
            I get the immediate parent(s) of the term in question.
            e.g. term_parent1, term_parent2

    2.  Then whatever the Case be, I sent the collected items to the ranking selection.
            */
}
?>