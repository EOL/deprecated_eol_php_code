<?php
namespace php_active_record;
/* [.php] */
class SDRreportLib
{
    // public function __construct($folder)
    function __construct($archive_builder = false, $resource_id = false)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        // $this->resource_id = $folder;
        $this->download_options = array('resource_id' => 'SDR_all', 'timeout' => 60*5, 'expire_seconds' => 60*60*24, 'cache' => 1, 'download_wait_time' => 1000000);
        $this->debug = array();
        
        /* Terms relationships -> https://opendata.eol.org/dataset/terms-relationships */
        /* from template

        $this->file['parent child']['fields'] = array('parent', 'child'); //used more simple words instead of: array('parent_term_URI', 'subclass_term_URI');
        
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-aug-16-1-2.csv";
        $this->file['preferred synonym']['path'] = "http://localhost/cp/summary data resources/preferredsynonym-aug-16-1-2-3.csv";
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-sept-27.csv";
        $this->file['preferred synonym']['fields'] = array('preferred', 'deprecated'); //used simple words instead of: array('preferred_term_URI', 'deprecated_term_URI');

        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child.csv"; 
        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child-6-1.csv";
        $this->file['parent child']['path_habitat'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/c5ff5c62-a2ef-44be-9f59-88cd99bc8af2/download/habitat-parent-child-6-1.csv";
        
        $this->file['parent child']['path_geoterms'] = "http://localhost/cp/summary data resources/geoterms-parent-child.csv";
        $this->file['parent child']['path_geoterms'] = "http://localhost/cp/summary data resources/geoterms-parent-child-1.csv";
        $this->file['parent child']['path_geoterms'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/e1dcb51b-9a03-4069-b5bf-e18b6bc15798/download/geoterms-parent-child-1.csv";
        
        $this->dwca_file = "http://localhost/cp/summary_data_resources/traits_all_201905.zip";
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . '/sample.txt';
        $this->temp_file = CONTENT_RESOURCE_LOCAL_PATH . '/temp.txt';

        //for taxon summary
        if(Functions::is_production())  $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/bac4e11c-28ab-4038-9947-02d9f1b0329f/download/eoldynamichierarchywithlandmarks.zip";
        else                            $this->EOL_DH = "http://localhost/cp/summary data resources/DH/eoldynamichierarchywithlandmarks.zip";
        
        $this->EOL_DH = "http://localhost/cp/summary data resources/DH/eoldynamichierarchywithlandmarks.zip";
        $this->lifeState_statMeth_resource_file = CONTENT_RESOURCE_LOCAL_PATH . '/lifeStage_statMeth_resource.txt';
        
        $this->parentModeYN = false;
        $this->fullref = array();
        */

        /* testing
        $this->parent_BH_resource_txt = CONTENT_RESOURCE_LOCAL_PATH . '/parent_basal_values_Carnivora_resource.txt';
        $this->parent_BH_DwCA = CONTENT_RESOURCE_LOCAL_PATH . 'parent_basal_values_Carnivora.tar.gz';
        */
        
        // /* actual operation
        $this->parent_BH_resource_txt = CONTENT_RESOURCE_LOCAL_PATH . '/parent_basal_values_resource.txt';
        $this->parent_BH_DwCA = CONTENT_RESOURCE_LOCAL_PATH . 'parent_basal_values.tar.gz';
        // */
        
    }
    function start($info) //this is called from DwCA_Utility.php
    {
        self::build_lookup_table(); //to initialize $this->parent_children_ids
        
        $tables = $info['harvester']->tables;
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]); //this is to lookup taxonID from occurrenceID
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]); //we need to add SampleSize
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        self::initialize();
        $this->func->initialize_basal_values(); // this will let you access $this->children_of via $this->func->children_of
                                                // or                       $this->parents_of via $this->func->parents_of
        // print_r($this->func->children_of); exit;
        
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 784b567e27b92085e014fa14c58b8608_parent_basal_values_Carnivora
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 400f0fb56ea4a6a052c5a79cec19e2c9_parent_basal_values_Carnivora
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://eol.org/schema/terms/Australasia
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 2018-Oct-10
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => summary of records available in EOL
                [http://purl.org/dc/terms/source] => https://eol.org/terms/search_results?utf8=✓&term_query[clade_id]=7662&term_query[filters_attributes][0][pred_uri]=http://eol.org/schema/terms/Present&term_query[filters_attributes][0][op]=is_any&term_query[result_type]=record&commit=Search
                [http://purl.obolibrary.org/obo/IAO_0000009] => REP
                [http://eol.org/schema/reference/referenceID] => 
            )*/

            $taxonID = $this->linkage_oID_tID[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']];
            $rec['SampleSize'] = self::get_SampleSize_for_MoF($taxonID, $rec['http://rs.tdwg.org/dwc/terms/measurementValue']);

            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function get_SampleSize_for_MoF($taxonID, $value_uri)
    {
        /* 1st option */
        if($children_terms = @$this->func->children_of[$value_uri]) {
            // print_r($children_terms); exit("\n$value_uri\n");
            $total_str = "";
            foreach($children_terms as $child_term) {
                // print_r($this->parent_children_ids[$taxonID]); exit; //
                if($page_ids = @$this->parent_children_ids[$taxonID][$child_term]) $total_str = $total_str . ";" . $page_ids;
            }
            if($total_str) {
                $arr = explode(";", $total_str);
                $arr = array_filter($arr); //remove null arrays
                $arr = array_unique($arr); //make unique
                $arr = array_values($arr); //reindex key
                if($val = count($arr)) return $val;
            }
        }
        /* 2nd option */
        if($val = self::compute_samplesize($taxonID, $value_uri)) return $val;
        
        echo("\nInvestigate no match in SampleSize lookup in MoF [$taxonID] [$value_uri]\n");
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 400f0fb56ea4a6a052c5a79cec19e2c9_parent_basal_values_Carnivora
                [http://rs.tdwg.org/dwc/terms/taxonID] => 7662
            )*/
            $this->linkage_oID_tID[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];

            /* no need to create 
            $o = new \eol_schema\Occurrence();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            */
        }
    }
    
    //========================================================================================
    function update_parentBV_reports()
    {
        self::build_lookup_table();
        self::add_SampleSize_4parent_BV_resource_txt();
        self::add_SampleSize_4parent_BV_MoF();
    }
    private function add_SampleSize_4parent_BV_MoF()
    {
        $dwca_file = $this->parent_BH_DwCA;
        
        require_library('connectors/DwCA_Utility');
        $func = new DwCA_Utility($this->resource_id, $dwca_file);

        /* Orig in meta.xml has capital letters. Just a note reminder.
        rowType="http://rs.tdwg.org/dwc/terms/Taxon">
        rowType="http://rs.tdwg.org/dwc/terms/Occurrence">
        rowType="http://rs.tdwg.org/dwc/terms/MeasurementOrFact">
        rowType="http://eol.org/schema/reference/Reference">
        */

        $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.tdwg.org/dwc/terms/occurrence', 'http://eol.org/schema/reference/reference');
        /* This 1 will be processed in SDRreportLib.php which will be called from DwCA_Utility.php
        http://rs.tdwg.org/dwc/terms/measurementorfact
        */
        $func->convert_archive($preferred_rowtypes);
        Functions::finalize_dwca_resource($this->resource_id);
    }
    private function add_SampleSize_4parent_BV_resource_txt()
    {
        $temp_txt = CONTENT_RESOURCE_LOCAL_PATH . '/SDR_tmp.txt';
        copy($this->parent_BH_resource_txt, $temp_txt);
        
        $WRITE = Functions::file_open($this->parent_BH_resource_txt, 'w');
        
        $i = 0;
        foreach(new FileIterator($temp_txt) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) {
                $fields = $line;
                if(!in_array('SampleSize', $line)) $line[] = 'SampleSize';
                fwrite($WRITE, implode("\t", $line)."\n");
            }
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /*Array(
                    [Page ID] => 7662
                    [eol_pk] => R512-PK71589851
                    [Value URI] => http://purl.obolibrary.org/obo/ENVO_00000067
                    [Label] => REP
                )*/
                $save = array($rec['Page ID'], $rec['eol_pk'], $rec['Value URI'], $rec['Label'], self::compute_samplesize($rec['Page ID'], $rec['Value URI']));
                fwrite($WRITE, implode("\t", $save)."\n");
            }
        }
        fclose($WRITE);
        unlink($temp_txt);
    }
    private function compute_samplesize($page_id, $value_uri)
    {
        if($page_ids = @$this->parent_children_ids[$page_id][$value_uri]) {
            $arr = explode(";", $page_ids);
            if($arr) return count($arr);
        }
        else {
            echo("\nInvestigate no match in SampleSize lookup [$page_id] [$value_uri]\n");
        }
    }
    private function build_lookup_table()
    {
        $i = 0; $txt_file = CONTENT_RESOURCE_LOCAL_PATH . '/SampleSize_table.txt';
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
                /* Array(
                    [parent_id] => 7662
                    [value_term] => http://www.geonames.org/1062947
                    [children_ids] => 347438;347436;999164;127498;128480;289555;347437;1037781;1053877;328607
                )*/
                $this->parent_children_ids[$rec['parent_id']][$rec['value_term']] = $rec['children_ids'];
            }
        }
    }
    private function initialize()
    {
        require_library('connectors/SummaryDataResourcesAllAPI');
        $this->func = new SummaryDataResourcesAllAPI('');
    }
    function gen_SampleSize_4parent_BV($dbase, $page_ids_param)
    {
        $filename = CONTENT_RESOURCE_LOCAL_PATH.'/SampleSize_table.txt';
        $WRITE = Functions::file_open($filename, 'w');
        fwrite($WRITE, implode("\t", array('parent_id', 'value_term', 'children_ids'))."\n");
        fclose($WRITE);
        
        self::initialize();
        $this->func->parse_DH(); //this was needed for $this->report_SampleSize
        $this->dbname = 'traits_'.$dbase;
        $this->func->initialize_basal_values(); 
        $predicates = $this->func->get_summ_process_type_given_pred('opposite', 'parents!A2:C1000', 2, 'basal value'); print_r($predicates);
        echo "\nGet page_ids for parent (BV)...\n";
        if($page_ids_param) {
            $page_ids = $this->func->get_page_ids_andInfo_fromDH($page_ids_param);
        }
        else {
            $page_ids = $this->func->get_page_ids_andInfo_fromDH();
        }
        $total_page_ids = count($page_ids);
        /* for indicator */ $total_predicates = count($predicates); $cnt_predicate = 0;
        foreach($predicates as $predicate) {
            $cnt_predicate++; /* for indicator */
            $cnt_page_id = 0;
            $m = 2237554/3; //for breakdown when caching...
            foreach($page_ids as $page_id => $taxon) {
                $this->report_SampleSize = array();
                /* for indicator */
                $cnt_page_id++;
                echo "\nPredicates $cnt_predicate of $total_predicates";
                echo "\nPage IDs $cnt_page_id of $total_page_ids\n";
                /* breakdown when caching:
                $cont = false;
                if($cnt_page_id >= 1 && $cnt_page_id < $m) $cont = true;
                // if($cnt_page_id >= $m && $cnt_page_id < $m*2) $cont = true;
                // if($cnt_page_id >= $m*2 && $cnt_page_id < $m*3) $cont = true;
                if(!$cont) continue;
                */
                if(!$page_id) continue;
                if(!@$taxon['taxonRank']) continue;
                if(@$taxon['taxonRank'] != "species" && $taxon['Landmark'] || @$taxon['taxonRank'] == "family") {
                    self::main_gen_SampleSize_4parent_BV($page_id, $predicate);
                }
                // print_r($this->report_SampleSize); //good debug
                self::write_SampleSize_2txt();
            }
        }
        echo("\n-- end gen_SampleSize_for_parent_BV --\n");
    }
    private function write_SampleSize_2txt()
    {   /*[7662] => Array (
        [https://www.wikidata.org/entity/Q648799] => Array(
                    [46559130] => 
                    [46559154] => 
                    [46559162] => 
                    [46559170] => 
                    [46559206] => 
                    [46559208] => 
        )*/
        $WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH.'/SampleSize_table.txt', 'a');
        foreach($this->report_SampleSize as $page_id => $rek) {
            foreach($rek as $uri => $page_ids) {
                $page_ids = array_keys($page_ids);
                $arr = array($page_id, $uri, implode(";", $page_ids));
                fwrite($WRITE, implode("\t", $arr)."\n");
            }
        }
        fclose($WRITE);
    }
    private function main_gen_SampleSize_4parent_BV($main_page_id, $predicate)
    {   /* 1. get all children of page_id with rank = species */
        if($children = $this->func->get_children_from_txt_file($main_page_id, false)) { //Value is now cached to txt file
            echo "\n*Children of [$main_page_id]: ".count($children)."\n"; //print_r($children); *Children of [164]: 1433142
        }
        else { echo "\n*No children found for [$main_page_id]\n"; return array(); }
        
        if($children = $this->func->get_childrenTBP_from_txt_file($main_page_id, $children, $predicate)) {
            $children_count = count($children);
            echo "\n*Children TBP of [$main_page_id]: ".$children_count."\n";
            if($children_count > 1000) return array();
        }
        else {
            echo "\n*No children TBP found for [$main_page_id]\n";
            return array();
        }
        /* 2. get all recs for each child */
        $recs = $this->func->get_all_recs_for_each_pageID($children, $predicate, $this->dbname); // echo "\n".count($recs)."\n"; exit("\nxxx\n");
        if(!$recs) {
            echo "\nNo recs for any of the children for predicate [$predicate]\n";
            return false;
        }
        // /* to get SampleSize count from: https://eol-jira.bibalex.org/browse/DATA-1773?focusedCommentId=63621&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63621
        self::buildup_SampleSize_data($main_page_id, $recs, $predicate, $children); //was semi-abandoned bec. it is very slow
        // */
    }
    private function buildup_SampleSize_data($main_page_id, $recs_from_descendants, $predicate, $children)
    { /* each record should have a SampleSize= the number of descendant taxa with records with that value in their ancestry (or as their record value). */
        /* Array(
        [0] => Array( -- many fields removed here...
                [page_id] => 347438 --> this is the descendant/child taxon
                [predicate] => http://eol.org/schema/terms/Habitat
                [value_uri] => http://purl.obolibrary.org/obo/ENVO_00000446
        )*/
        // echo "\n".count($this->EOL_2_DH)."\n"; echo "\n".count($this->DH_2_EOL)."\n"; //exit;
        foreach($recs_from_descendants as $rec) { $page_id = $rec['page_id'];
            $this->report_SampleSize[$main_page_id][$rec['value_uri']][$page_id] = ''; //(or as their record value)
        }
        return; //ends here - below works but seems not needed - Aug 6, 2019

        /* step 4 maybe redundant
        foreach($children as $page_id) {
            if($anc = $this->func->get_ancestry_via_DH($page_id, false)) { // print_r($anc);
                // echo("\n[".$page_id."]has ancestry [".count($anc)."]\n");
                // echo "\ndoing this now...\n";
                if($recs_from_ancestry = $this->func->get_all_recs_for_each_pageID($anc, $predicate, $this->dbname)) {
                    // echo "\n recs_from_ancestry of descendant [$page_id]: ".count($recs_from_ancestry)."\n";     //debug
                    // echo "\nfirst rec in recs_from_ancestry: ".$recs_from_ancestry[0]['page_id']."\n";           //debug
                    foreach($recs_from_ancestry as $rec) {
                        // print_r($rec); exit;
                        // Array(
                        //     [page_id] => 44709
                        //     [scientific_name] => Cryptoprocta
                        //     [predicate] => http://eol.org/schema/terms/Habitat
                        //     [value_uri] => http://purl.obolibrary.org/obo/ENVO_00002009
                        //     many other fields...
                        // )
                        $this->report_SampleSize[$main_page_id][$rec['value_uri']][$page_id] = ''; //the number of descendant taxa with records with that value in their ancestry
                    }
                }
            }
        }
        */
    }

}