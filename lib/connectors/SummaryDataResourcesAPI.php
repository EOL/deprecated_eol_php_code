<?php
namespace php_active_record;
/* [SDR.php] */
class SummaryDataResourcesAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        /*
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */
        $this->download_options = array('resource_id' => 'SDR', 'timeout' => 60*5, 'expire_seconds' => 60*60*24, 'cache' => 1, 'download_wait_time' => 1000000);
        $this->debug = array();
        
        /* Terms relationships -> https://opendata.eol.org/dataset/terms-relationships */
        /* not used at the moment:
        $this->file['parent child']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/f8036c30-f4ab-4796-8705-f3ccd20eb7e9/download/parent-child-aug-16-2.csv";
        $this->file['parent child']['path'] = "http://localhost/cp/summary data resources/parent-child-aug-16-2.csv";
        */
        $this->file['parent child']['fields'] = array('parent', 'child'); //used more simple words instead of: array('parent_term_URI', 'subclass_term_URI');
        
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-aug-16-1-2.csv";
        $this->file['preferred synonym']['path'] = "http://localhost/cp/summary data resources/preferredsynonym-aug-16-1-2-3.csv";

        $this->file['preferred synonym']['fields'] = array('preferred', 'deprecated'); //used simple words instead of: array('preferred_term_URI', 'deprecated_term_URI');

        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child.csv";
        $this->file['parent child']['path_geoterms'] = "http://localhost/cp/summary data resources/geoterms-parent-child.csv";
        
        $this->dwca_file = "http://localhost/cp/summary data resources/carnivora_sample.tgz";
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . '/sample.txt';
        $this->temp_file = CONTENT_RESOURCE_LOCAL_PATH . '/temp.txt';
        
        if(Functions::is_production())  $this->working_dir = "/extra/summary data resources/page_ids/";
        else                            $this->working_dir = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/";
        $this->jen_isvat = "/Volumes/AKiTiO4/web/cp/summary data resources/2018 09 08/jen_isvat.txt";
        
        //for taxon summary
        /*
        if(Functions::is_production())  $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/b534cd22-d904-45e4-b0e2-aaf06cc0e2d6/download/eoldynamichierarchyv1revised.zip";
        else                            $this->EOL_DH = "http://localhost/cp/summary data resources/eoldynamichierarchyv1.zip";
        */
        if(Functions::is_production())  $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/bac4e11c-28ab-4038-9947-02d9f1b0329f/download/eoldynamichierarchywithlandmarks.zip";
        else                            $this->EOL_DH = "http://localhost/cp/summary%20data%20resources/DH/eoldynamichierarchywithlandmarks.zip";
        
        $this->EOL_DH = "http://localhost/cp/summary%20data%20resources/DH/eoldynamichierarchywithlandmarks.zip";
    }
    /* for 'parents' method:
    repeat basal values process on REP records aggregated from descendant taxa, to create a summary set of REP records (no PRM record). 
    MeasurementMethod= "summary of records available in EOL". 
    SampleSize = "[number of] descendant taxa with records". 
    Do not aggregate attribution data, but create source link to EOL, eg: https://beta.eol.org/terms/search_results?utf8=%E2%9C%93&clade_name=Ursidae&term_query%5Bclade_id%5D=7664&pred_name=geographic+distribution+includes&term_query%5Bfilters_attributes%5D%5B0%5D%5Bpred_uri%5D=http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPresent&term_query%5Bfilters_attributes%5D%5B0%5D%5Bop%5D=is_any&term_query%5Bresult_type%5D=record&commit=Search
    */
    function start()
    {
        /* WORKING
        $ret = self::get_summ_process_type_given_pred(); 
        print_r($ret); exit("\n".count($ret)."\n");
        */
        
        /*
        self::initialize();
        self::investigate_traits_csv(); exit;
        */

        // /* METHOD: taxon summary ============================================================================================================
        self::parse_DH();
        // $page_id = 328607; $predicate = "http://purl.obolibrary.org/obo/RO_0002439"; //preys on - no record
        $page_id = 7666; $page_id = 7662;
        $page_id = 7673; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        $page_id = 7662; $predicate = "http://purl.obolibrary.org/obo/RO_0002458"; //preyed upon by
        $page_id = 46559118; $predicate = "http://purl.obolibrary.org/obo/RO_0002439"; //preys on
        // $page_id = 328607; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        self::initialize();
        $ret = self::main_taxon_summary($page_id, $predicate);
        exit("\n-- method: 'taxon summary' ends --\n");
        // */

        /* METHOD: lifestage+statMeth ============================================================================================================
        self::initialize();
        $page_id = 347436; $predicate = "http://purl.obolibrary.org/obo/VT_0001259";
        $page_id = 347438; $page_id = 46559130;
        $ret = self::main_lifestage_statMeth($page_id, $predicate);
        print_r($ret);
        exit("\n-- main_lifestage_statMeth ends --\n");
        */
        /* METHOD: basal values  ============================================================================================================
        self::initialize_basal_values();
        // $page_id = 46559197; $predicate = "http://eol.org/schema/terms/Present";
        // $page_id = 46559217; $predicate = "http://eol.org/schema/terms/Present";
        $page_id = 7662; $predicate = "http://eol.org/schema/terms/Habitat";
        self::main_basal_values($page_id, $predicate); //works OK
        exit("\n-- end basal values --\n");
        */
    }
    private function extract_DH()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->EOL_DH, "taxa.txt", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $tables['taxa'] = 'taxa.txt';
        $paths['tables'] = $tables;
        return $paths;
    }
    private function parse_DH()
    {
        if(Functions::is_production()) {
            if(!($info = self::extract_DH())) return;
            print_r($info);
            $this->info_path = $info;
        }
        else { //local development only
            /*
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/EOL_dynamic_hierarchy/',   //for eoldynamichierarchyv1.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/',
                          'tables' => Array('taxa' => 'taxa.txt')); */
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',                         //for eoldynamichierarchywithlandmarks.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',
                          'tables' => Array('taxa' => 'taxa.txt'));
            $this->info_path = $info;
        }
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
            }
        }
        /* may not want to force assign this:
        $this->DH_2_EOL[93302] = 6061725; //Biota - Cellular Organisms
        */
        
        // remove temp dir
        // recursive_rmdir($info['temp_dir']);
        // echo ("\n temporary directory removed: " . $info['temp_dir']);
    }
    private function get_ancestry_via_DH($page_id)
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
                /* orig strategy
                $final2[] = $EOLid;
                */
                // /* new strategy: using Landmark value
                if($this->landmark_value_of[$EOLid]) $final2[] = $EOLid;
                // */
                // echo " $EOLid";
            }
            // else echo " none";
            $i++;
        }
        // print_r($final);
        return $final2;
    }
    private function main_taxon_summary($page_id, $predicate)
    {
        // /* working but seems not needed. Just bring it back when requested.
        $ancestry = self::get_ancestry_via_DH($page_id);
        echo "\n$page_id: (ancestors below, with {Landmark value} in curly brackets)";
        foreach($ancestry as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
        // */
        echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
        $path = self::get_txt_path_by_page_id($page_id);
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        if(!$recs) { echo "\nNo records for [$page_id] [$predicate].\n"; return; }
        echo "\nrecs: ".count($recs)."\n";
        // print_r($recs);
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
                echo "\n$page_id: (ancestors below, with {Landmark value} in curly brackets)";
                foreach($anc as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
                // */
                
                //start store counts:
                $k = 0;
                foreach($anc as $id) {
                    @$counts[$id]++;
                    if($k > 0) $children_of[$id][] = $anc[$k-1];
                    $k++;
                }
            }
        }
        
        /* good debug
        print_r($counts); print_r($children_of);
        */
        $final = array();
        foreach($recs as $rec) {
            if($page_id = @$rec['object_page_id']) {
                $anc = self::get_ancestry_via_DH($page_id);
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
        }
        
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
        foreach($final as $tip => $ancestors) {
            $root_ancestor[] = end($ancestors);
            $no_of_rows = count($ancestors);
            if($no_of_rows > 1) $immediate_children_of_root[$ancestors[$no_of_rows-2]] = ''; // rows should be > 1 bec if only 1 then there is no child for that root.
            if($no_of_rows == 1) $immediate_children_of_root[$tip] = '';
        }
        
        $root_ancestor = array_unique($root_ancestor);
        $immediate_children_of_root = array_keys($immediate_children_of_root);
        
        echo "\n root: "; print_r($root_ancestor);
        echo "\n immediate_children_of_root: "; print_r($immediate_children_of_root);
        
        return array('tree' => $final, 'root' => $root_ancestor, 'root label' => 'PRM', 'Selected' => $immediate_children_of_root, 'Selected label' => 'REP');
    }
    
    private function main_lifestage_statMeth($page_id, $predicate)
    {
        $path = self::get_txt_path_by_page_id($page_id);
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        if(!$recs) { echo "\nNo records for [$page_id] [$predicate].\n"; return; }
        echo "\nrecs: ".count($recs)."\n";
        // print_r($recs);
        if    ($ret = self::lifestage_statMeth_Step0($recs)) {}
        elseif($ret = self::lifestage_statMeth_Step1($recs)) {}
        elseif($ret = self::lifestage_statMeth_Step23456789($recs)) {}
        else exit("\nsingle simple answer (PRM) if still needed: put REP records in order of value and select one from the middle (arbitrary tie breaks OK)\n");
        if($val = @$ret['recs']) $ret['recs_total'] = count($val);
        return $ret;
    }
    private function lifestage_statMeth_Step0($recs)
    {
        if(count($recs) == 1) return array('label' => 'REP and PRM', 'recs' => $recs, 'step' => 0);
        else return false;
    }
    private function lifestage_statMeth_Step1($recs)
    {
        $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult");
        $final = array();
        foreach($recs as $rec) {
            /*
            Array(
                [eol_pk] => R143-PK39533097
                [page_id] => 46559130
                [scientific_name] => <i>Enhydra lutris</i>
                [resource_pk] => 16788
                [predicate] => http://purl.obolibrary.org/obo/VT_0001259
                [sex] => 
                [lifestage] => 
                [statistical_method] => 
                [source] => http://genomics.senescence.info/species/entry.php?species=Enhydra_lutris
                [object_page_id] => 
                [target_scientific_name] => 
                [value_uri] => 
                [literal] => 
                [measurement] => 26832.8
                [units] => http://purl.obolibrary.org/obo/UO_0000021
                [normal_measurement] => 26832.8
                [normal_units_uri] => http://purl.obolibrary.org/obo/UO_0000021
                [resource_id] => 50
            )
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
    private function get_txt_path_by_page_id($page_id)
    {
        $path = self::get_md5_path($this->working_dir, $page_id);
        return $path . $page_id . ".txt";
    }
    private function test() //basal values tests...
    {
        // self::utility_compare();
        /* IMPORTANT STEP: working OK - commented for now.
        self::working_dir(); self::generate_page_id_txt_files(); exit("\n\nText file generation DONE.\n\n");
        */
    }
    private function main_basal_values($page_id, $predicate) //for basal values
    {
        echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate, array('value_uri')); //3rd param array is required_fields
        if(!$recs) {
            echo "\nNo records for [$page_id] [$predicate].\n";
            return;
        }
        $uris = self::get_valueUris_from_recs($recs);
        self::set_ancestor_ranking_from_set_of_uris($uris);
        $ISVAT = self::get_initial_shared_values_ancestry_tree($recs); //initial "shared values ancestry tree"
        $ISVAT = self::sort_ISVAT($ISVAT);
        $info = self::add_new_nodes_for_NotRootParents($ISVAT);
        $new_nodes = $info['new_nodes'];    
        echo "\n\nnew nodes 0:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        
        $info['new_nodes'] = self::sort_ISVAT($new_nodes);
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
        
        // /*
        //for jen: 
        echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
        echo "\n\ninitial shared values ancestry tree: ".count($ISVAT)."\n";
        foreach($ISVAT as $a) echo "\n".$a[0]."\t".$a[1];
        echo "\n\nnew nodes 2:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        echo "\n\nRoots 2: ".count($roots)."\n"; print_r($roots);
        // exit("\n");
        // */
        
        //for step 1: So, first you must identify the tips- any values that don't appear in the left column. The parents, for step one, will be the values to the left of the tip values.
        $tips = self::get_tips($ISVAT);
        echo "\n tips: ".count($tips);
        foreach($tips as $tip) echo "\n$tip";
        echo "\n-end tips-\n"; //exit;

        if(count($tips) <= 5 ) {}
        else { // > 5
            $step_1 = self::get_step_1($ISVAT, $roots, $tips);
            echo "\nStep 1:".count($step_1)."\n";
            foreach($step_1 as $a) echo "\n".$a;
            echo "\n-end Step 1-\n";
            if(count($step_1) <= 4) {} //select set 1
            else {
                $step_2 = self::get_step_1($ISVAT, $roots, $step_1);
                echo "\nStep 2:".count($step_2)."\n";
                foreach($step_2 as $a) echo "\n".$a;
                echo "\n-end Step 2-\n";
                if(count($step_2) <= 4) {} //select set 2
                else {
                    $step_3 = self::get_step_1($ISVAT, $roots, $step_2);
                    echo "\nStep 3:".count($step_3)."\n";
                    foreach($step_3 as $a) echo "\n".$a;
                    echo "\n-end Step 3-\n";
                    if($step_2 == $step_3) {
                        echo "\nSteps 2 and 3 are identical.\n";
                        if(count($step_3) <= 4) {} //select set 3
                        else {
                            echo "\nSelect root ancestors\n"; //label PRM and REP if one record, REP if >1
                            if(count($roots) == 1) echo "\n----- label as: PRM and REP\n";
                            elseif(count($roots) > 1) echo "\n----- label as: REP\n";
                        }
                    }
                    else {
                        echo "\nStep 2 and Step 3 are different.\n";
                    }
                }
            }
        }
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

        if(WHAT IS SELECTED == 1) label as: "PRM and REP"
        elseif(WHAT IS SELECTED > 1) label as: "REP"

        So in our case: page_id: 7662 | predicate: [http://eol.org/schema/terms/Habitat]
        I will be creating new rocords based on 'ROOT_ANCESTORS'.
        */
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

    private function get_step_1($isvat, $roots, $tips)
    {   /* 
        - find all tips
        - find all nodes that are parents of tips
        - in each case, check whether either the tip or the parent is a root
            -- if either the tip or the parent is a root, put the tip in set 1
            -- if neither the tip nor the parent is a root, put the parent in set 1
        - (deduplicate set 1) */
        foreach($isvat as $a) {
            $parent_of_right[$a[1]] = $a[0];
        }
        foreach($tips as $tip) {
            if($parent = $parent_of_right[$tip]) {
                if(in_array($tip, $roots) || in_array($parent, $roots)) $final[$tip] = '';
                if(!in_array($tip, $roots) && !in_array($parent, $roots)) $final[$parent] = '';
            }
            else {
                if(in_array($tip, $roots)) $final[$tip] = '';
            }
        }
        $final = array_keys($final);
        asort($final);
        return $final;
    }
    private function utility_compare()
    {
        foreach(new FileIterator($this->jen_isvat) as $line_number => $line) {
            $arr[] = explode("\t", $line);
        }
        asort($arr); foreach($arr as $a) echo "\n".$a[0]."\t".$a[1];
        exit("\njen_isvat.txt\n");
    }
    private function merge_nodes($info, $ISVAT)
    {
        $new_nodes = $info['new_nodes'];
        $roots     = $info['roots'];
        
        $new_isvat = array_merge($ISVAT, $new_nodes);
        $new_isvat = self::sort_ISVAT($new_isvat);
        $new_isvat = self::remove_orphans_that_exist_elsewhere($new_isvat);
        
        $new_roots = $roots;
        foreach($new_isvat as $a) {
            if(!$a[0]) $new_roots[] = $a[1];
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
    {
        //first get all non-orphan rows
        foreach($isvat as $a) {
            if($a[0]) {
                $left[$a[0]] = '';
                $right[$a[1]] = '';
            }
        }
        //if orphan $a[1] exists elsewhere then remove that orphan row
        //The way I was thinking of documenting, it wouldn't need to be listed as an orphan if it also appears in any relationship pair.
        foreach($isvat as $a) {
            if(!$a[0] && (
                            isset($left[$a[1]]) || isset($right[$a[1]])
                         )
            ){
                echo "\n === $a[0] --- $a[1] === remove orphan coz it exists elsewhere \n"; //the orphan row ENVO_00000446 was removed here...
            }
            else $final[] = $a;
        }
        return $final;
    }
    private function sort_ISVAT($arr) //also remove parent nodes where there is only one child. Make child an orphan.
    {
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

        $discard_parents = array(); echo "\n--------------------\n";
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
    private function generate_page_id_txt_files()
    {
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++; echo " $i";
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
                )*/

                $path = self::get_md5_path($this->working_dir, $rec['page_id']);
                $txt_file = $path . $rec['page_id'] . ".txt";
                if(file_exists($txt_file)) {
                    $WRITE = fopen($txt_file, 'a');
                    fwrite($WRITE, implode("\t", $line)."\n");
                    fclose($WRITE);
                }
                else {
                    $WRITE = fopen($txt_file, 'w');
                    fwrite($WRITE, implode("\t", $fields)."\n");
                    fwrite($WRITE, implode("\t", $line)."\n");
                    fclose($WRITE);
                }
            }
        }
        fclose($file);
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
        $recs = array();
        /*
        $path = self::get_md5_path($this->working_dir, $page_id);
        $txt_file = $path . $page_id . ".txt";
        */
        $txt_file = self::get_txt_path_by_page_id($page_id);
        echo "\n$txt_file\n";
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
            // print_r($rec); exit;
            /*Array(
                [0] => http://www.geonames.org/6255151
                [1] => http://www.marineregions.org/gazetteer.php?p=details&id=australia
            )*/
            $unique[$rec[0]] = '';
        }
        //2nd step: check if parent is not root (meaning has parents), if yes: get parent and add the new node:
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
        $final = array();
        $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE);
        foreach($recs as $rec) {
            $term = $rec['value_uri'];
            $parent = self::get_parent_of_term($term);
            $final[] = array($parent, $term);
        }
        return $final;
    }
    function start_ok()
    {
        self::initialize();
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
    }
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
        /*
        foreach($uris as $term) {
            if($parents = @$this->parents_of[$term]) {
                foreach($parents as $parent) @$final[$parent]++;
            }
        }//end main
        */
        arsort($final);
        $final = array_keys($final);
        $this->ancestor_ranking = $final;

        arsort($final_preferred);
        // print_r($final_preferred);
        $final_preferred = array_keys($final_preferred);
        // print_r($final_preferred);
        $this->ancestor_ranking_preferred = $final_preferred;
    }
    private function get_rank_most_parent($parents, $preferred_terms = array())
    {
        if(!$preferred_terms) {
            //1st option: if any is a preferred name then choose that
            foreach($this->ancestor_ranking_preferred as $parent) {
                if(in_array($parent, $parents)) {
                    $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
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
                        return $parent;
                    }
                }
            }
            if(count($preferred_terms) == 1 && in_array($preferred_terms[0], $this->ancestor_ranking) && in_array($preferred_terms[0], $this->ancestor_ranking_preferred)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $preferred_terms[0]."\n"); fclose($WRITE);
                return $preferred_terms[0];
            }
        }
        
        //2nd option
        $inclusive = array_merge($parents, $preferred_terms);
        foreach($this->ancestor_ranking as $parent) {
            if(in_array($parent, $inclusive)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                return $parent;
            }
        }
        
        echo "\nInvestigate parents not included in ranking... weird...\n";
        print_r($inclusive);
        exit("\n===============\n");
    }
    private function get_parent_of_term($term)
    {
        echo "\n--------------------------------------------------------------------------------------------------------------------------------------- \n"."term in question: [$term]:\n";
        /*
        if($parents = @$this->parents_of[$term]) {
            echo "\nParents:\n"; print_r($parents);
        }
        else echo "\nNO PARENT\n";
        */
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
                // foreach($immediate_parents as $immediate) {
                //     echo "\nparent(s) of $immediate:";
                //     if($parents = @$this->parents_of[$immediate]) {
                //         print_r($parents);
                //     }
                //     else echo " -- NO parent";
                // }
            }
        }
    }
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
    function start_v1()
    {
        self::working_dir();
        $this->child_parent_list = self::generate_child_parent_list();
        // /* tests...
        $predicate = "http://reeffish.org/occursIn";
        $predicate = "http://eol.org/schema/terms/Present";
        $similar_terms = self::given_predicate_get_similar_terms($predicate);
        // print_r($similar_terms); exit;
        
        self::print_taxon_and_ancestry($similar_terms);
        self::given_predicates_get_values_from_traits_csv($similar_terms);
        exit("\n-end tests-\n");
        // */
        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
        // remove temp dir
        /* un-comment in real operation
        recursive_rmdir($this->main_paths['temp_dir']);
        echo ("\n temporary directory removed: " . $this->main_paths['temp_dir']);
        */
    }
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
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    [scientific_name] => <i>Tremarctos ornatus</i>
                    [resource_pk] => M_00329828
                    [predicate] => http://eol.org/schema/terms/Present
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://www.worldwildlife.org/publications/wildfinder-database
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [literal] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [measurement] => 
                    [units] => 
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [resource_id] => 20
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
                    [resource_pk] => M_00329828
                    [predicate] => http://eol.org/schema/terms/Present
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://www.worldwildlife.org/publications/wildfinder-database
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [literal] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [measurement] => 
                    [units] => 
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [resource_id] => 20
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
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/carnivora_sample',
                          'temp_dir'     => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/');
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
    private function get_summ_process_type_given_pred() //sheet found here: https://docs.google.com/spreadsheets/u/1/d/1Er57xyxT_-EZud3mNkTBn0fZ9yZi_01qtbwwdDkEsA0/edit?usp=sharing
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1Er57xyxT_-EZud3mNkTBn0fZ9yZi_01qtbwwdDkEsA0';
        $params['range']         = 'predicates!A2:F1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) {
            if($val = $item[0]) $final[$item[0]] = $item[5];
        }
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