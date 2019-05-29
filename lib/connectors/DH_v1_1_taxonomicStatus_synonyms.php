<?php
namespace php_active_record;
/* connector: [dwh_postproc_TRAM_809.php] - TRAM-809 */
class DH_v1_1_taxonomicStatus_synonyms
{
    function __construct($folder) {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        if(Functions::is_production()) { //not used in eol-archive yet, might never be used anymore...
            /*
            $this->download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/extra/other_files/DWH/TRAM-807/"; //download_wait_time is 1/4 of a second -> 1000000/4
            */
        }
        else {
            $this->download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path          = "/Volumes/AKiTiO4/d_w_h/TRAM-808/";
            $this->main_path_TRAM_809 = "/Volumes/AKiTiO4/d_w_h/TRAM-809/";
            // $this->file['new DH'] = $this->main_path."DH_v1_1_postproc/taxon.tab";
            // $this->file['old DH'] = $this->main_path."eoldynamichierarchywithlandmarks/taxa.txt";
        }
        $this->mysqli =& $GLOBALS['db_connection'];
        
        $sources_path = "/Volumes/AKiTiO4/d_w_h/2019_04/"; //new - TRAM-805 - 2nd Smasher run
        $this->sh['NCBI']['source']     = $sources_path."/NCBI_Taxonomy_Harvest_DH/";
        $this->sh['NCBI']['syn_status'] = 'synonym';
        
        $this->sh['ASW']['source']      = $sources_path."/amphibianspeciesoftheworld/";
        $this->sh['ASW']['syn_status']  = 'invalid';
        
        $this->sh['ODO']['source']      = $sources_path."/worldodonata/";
        $this->sh['ODO']['syn_status']  = 'synonym';
        
        $this->sh['BOM']['source']      = $sources_path."/kitchingetal2018/";
        $this->sh['BOM']['syn_status']  = 'synonym';
        
        $this->sh['WOR']['source']      = $sources_path."/WoRMS_DH/";
        $this->sh['WOR']['syn_status']  = 'synonym';
        
        $this->sh['COL']['source'] = '/Volumes/AKiTiO4/web/cp/COL/2019-02-20-archive-complete/';
        /* 'synonym' OR 'ambiguous synonym' */
        
        $this->write_fields = array('taxonID', 'source', 'furtherInformationURL', 'parentNameUsageID', 'scientificName', 'taxonRank', 'taxonRemarks', 
                                    'datasetID', 'canonicalName', 'EOLid', 'EOLidAnnotations', 'higherClassification', 'taxonomicStatus', 'acceptedNameUsageID');
        $this->write_fields_rep = array('scientificName', 'source', 'acceptedNameUsageID', 'taxonID');
    }
    function step_6() //6. Deduplicate synonyms
    {
        // /* uncomment in real operation
        require_library('connectors/DHSourceHierarchiesAPI_v2'); $func = new DHSourceHierarchiesAPI_v2('');
        $ordered_sources = $func->get_order_of_hierarchies();
        $ordered_sources[] = 'manual';
        print_r($ordered_sources); //exit;
        // */

        $file_append = $this->main_path_TRAM_809."/synonyms_2be_discarded.txt"; $this->WRITE = fopen($file_append, "w"); fwrite($this->WRITE, implode("\t", $this->write_fields)."\n");
        $source = $this->main_path_TRAM_809."/synonyms.txt";
        // $source = $this->main_path_TRAM_809."/synonyms_sample.txt"; //debug only
        $syn_list = self::get_syn_list($source);
        foreach($syn_list as $key => $recs) {
            if(count($recs) > 1) {
                // print_r($recs); //good debug
                self::write_discard_syn_2text($ordered_sources, $recs);
            }
        }
        fclose($this->WRITE);
        self::regenerate_synonyms_without_duplicates();
        /* results:
        Synonyms to be discarded: 276
        /Volumes/AKiTiO4/d_w_h/TRAM-809//synonyms.txt: [1682374]
        /Volumes/AKiTiO4/d_w_h/TRAM-809//synonyms_deduplicated.txt: [1682099]
        Delete process count: [276]
        */
    }
    function add_synonyms_to_DH()
    {   // --> use:
        // $this->main_path_TRAM_809."/synonyms_minted.txt";
        // $this->main_path_TRAM_809."/new_DH_taxonStatus.txt";     //new DH with taxonomicStatus
        // --> output:
        // $this->main_path_TRAM_809."/new_DH_with_synonyms.txt";   //new DH with synonyms

        $file_append = $this->main_path_TRAM_809."/new_DH_with_synonyms.txt"; $WRITE = fopen($file_append, "w"); fwrite($WRITE, implode("\t", $this->write_fields)."\n");
        self::show_totals($file_append);

        $source = $this->main_path_TRAM_809."/new_DH_taxonStatus.txt";
        self::append_file($source, $WRITE);
        
        $source = $this->main_path_TRAM_809."/synonyms_minted.txt";
        // $source = $this->main_path_TRAM_809."/synonyms_minted_sample.txt"; //debug only
        self::append_file($source, $WRITE);

        self::show_totals($source);

        self::show_totals($file_append);
    }
    private function append_file($source, $WRITE)
    {
        $fields = $this->write_fields;
        echo "\nReading [$source]...\n"; $i = 0;
        foreach(new FileIterator($source) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                /* $fields in set above
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                */
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonID] => SYN-000000000001
                [source] => NCBI
                [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1
                [parentNameUsageID] => 
                [scientificName] => all
                [taxonRank] => no rank
                [taxonRemarks] => 
                [datasetID] => NCBI
                [canonicalName] => 
                [EOLid] => 
                [EOLidAnnotations] => 
                [higherClassification] => 
                [taxonomicStatus] => synonym
                [acceptedNameUsageID] => EOL-000000000001
            )*/
            self::write_report($rec, $fields, $WRITE);
            // if($i < 5) break; //debug
        }
    }
    function regenerate_synonyms_without_duplicates()
    {
        // --> input:
        // $this->main_path_TRAM_809."/synonyms.txt";
        // --> to be removed from synonyms.txt:
        // $this->main_path_TRAM_809."/synonyms_2be_discarded.txt";
        // --> output:
        // $this->main_path_TRAM_809."/synonyms_deduplicated.txt";
        
        $file = $this->main_path_TRAM_809."/synonyms_2be_discarded.txt";
        $delete_syn_compact_ids = self::fetch_records_from_file($file, 'synonyms_2be_discarded');
        echo "\nSynonyms to be discarded: ".count($delete_syn_compact_ids)."\n";
        
        $source = $this->main_path_TRAM_809."/synonyms.txt";
        // $source = $this->main_path_TRAM_809."/synonyms_sample.txt"; //debug only
        self::show_totals($source);
        $file_append = $this->main_path_TRAM_809."/synonyms_deduplicated.txt"; $WRITE = fopen($file_append, "w"); fwrite($WRITE, implode("\t", $this->write_fields)."\n");
        
        $delete_count = 0;
        echo "\nReading [$source]...\n"; $i = 0;
        foreach(new FileIterator($source) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                /* wouldn't achieve expected result if you use $row[0] alone
                if(!@$row[0]) continue;
                if(!@$row[0] && !@$row[5]) continue; //row5 is scientificName. row0 here is expected as null/blank
                */
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonID] => 1_1
                [source] => NCBI
                [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1
                [parentNameUsageID] => 
                [scientificName] => all
                [taxonRank] => no rank
                [taxonRemarks] => 
                [datasetID] => NCBI
                [canonicalName] => 
                [EOLid] => 
                [EOLidAnnotations] => 
                [higherClassification] => 
                [taxonomicStatus] => synonym
                [acceptedNameUsageID] => EOL-000000000001
            )*/
            $tmp = self::compact_syn_row($rec);
            if(!isset($delete_syn_compact_ids[$tmp])) {
                self::write_report($rec, $this->write_fields, $WRITE);
            }
            else $delete_count++;
        }
        fclose($WRITE);
        self::show_totals($file_append);
        echo "\nDelete process count: [$delete_count]\n";
    }
    private function compact_syn_row($rec)
    {
        return $rec['taxonID']."_".$rec['scientificName']."_".$rec['acceptedNameUsageID']."_".$rec['datasetID'];
    }
    private function fetch_records_from_file($file, $purpose)
    {
        echo "\nReading [$file]...\n"; $i = 0; $final = array();
        foreach(new FileIterator($file) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopxs\n");
            /*Array(
                [taxonID] => inv-Arcifera-Cope-1889-B
                [source] => ASW
                [furtherInformationURL] => http://research.amnh.org/herpetology/amphibia/index.html
                [parentNameUsageID] => 
                [scientificName] => Arcifera Cope, 1889
                [taxonRank] => order
                [taxonRemarks] => synonymous original name
                [datasetID] => ASW
                [canonicalName] => 
                [EOLid] => 
                [EOLidAnnotations] => 
                [higherClassification] => 
                [taxonomicStatus] => invalid
                [acceptedNameUsageID] => EOL-000000618834
            )*/
            if($purpose == 'synonyms_2be_discarded') {
                $tmp = self::compact_syn_row($rec);
                $final[$tmp] = '';
            }
        }
        return $final;
    }
    private function write_discard_syn_2text($ordered_sources, $recs)
    {
        $final = array(); $cont = true;
        foreach($ordered_sources as $source) {
            foreach($recs as $rec) {
                if($source == $rec['source']) {
                    $final['retain'] = self::compact_syn_row($rec);
                    $cont = false;
                    break;
                }
            }
            if(!$cont) break;
        }
        /* create discard list... */
        foreach($recs as $rec) {
            $temp = self::compact_syn_row($rec);
            if($temp != $final['retain']) $final['discard'][] = $rec;
        }
        // print_r($final); //good debug
        /* write to text discard list */
        if($discards = @$final['discard']) {
            foreach($discards as $save) self::write_report($save, $this->write_fields, $this->WRITE);
        }
    }
    private function get_syn_list($source)
    {
        echo "\nReading [$source]...\n";
        $i = 0; $list = array();
        foreach(new FileIterator($source) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonID] => 42273049
                [source] => COL:42227223
                [furtherInformationURL] => http://www.catalogueoflife.org/col/details/species/id/8534d672d9b8cfb4e479fd3e91435efc/synonym/b160b9c1d485fb176336e2d8fdeb4c98
                [parentNameUsageID] => 
                [scientificName] => Geastrum schweinitzii var. stipitatum (Solms) P. Ponce de León, 1968
                [taxonRank] => infraspecies
                [taxonRemarks] => 
                [datasetID] => COL
                [canonicalName] => 
                [EOLid] => 
                [EOLidAnnotations] => 
                [higherClassification] => 
                [taxonomicStatus] => synonym
                [acceptedNameUsageID] => EOL-000002269028
            )
            Two synonyms are equivalent IF
            (1) They have the same acceptedNameUsageID
            (2) They have exactly the same scientificName
            */
            $list[$rec['acceptedNameUsageID']."_".$rec['scientificName']][] = $rec;
        }
        return $list;
    }
    private function show_totals($file)
    {
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);  echo "\n$file: [$total]\n";
    }
    function step_5() //5. Add manually curated synonyms
    {
        $file_append = $this->main_path_TRAM_809."/synonyms.txt"; 
        // $file_append = $this->main_path_TRAM_809."/synonyms_sample.txt"; //only during development - debug only
        $this->WRITE = fopen($file_append, "a"); //IMPORTANT TO USE 'a' HERE. NOT 'w'. So it doesn't overwrite.
        self::show_totals($file_append);
        $add_syns = self::get_manually_curated_syns();
        // print_r($add_syns); 
        echo "\n".count($add_syns)."\n";
        /*[1251] => Array(
                    [0] => Xerus rutilus
                    [1] => EOL-000000636192
                    [2] => invalid
                    [3] => species
                )*/
        foreach($add_syns as $s) {
            $save = array('scientificName' => $s[0], 'acceptedNameUsageID' => $s[1], 'taxonomicStatus' => $s[2], 'taxonRank' => @$s[3], 'datasetID' => 'manual');
            self::write_report($save, $this->write_fields, $this->WRITE);
            // break; //debug only
        }
        fclose($this->WRITE);
        self::show_totals($file_append);
        /*  /Volumes/AKiTiO4/d_w_h/TRAM-809//synonyms.txt: [1681123]
            1251
            /Volumes/AKiTiO4/d_w_h/TRAM-809//synonyms.txt: [1682374] OK
        */
    }
    private function get_manually_curated_syns() //sheet found here: TRAM-809
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1XreJW9AMKTmK13B32AhiCVc7ZTerNOH6Ck_BJ2d4Qng';
        $params['range']         = 'manually curated synonyms!A2:D1300'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        return $arr; //no need to massage anymore...
    }
    function step_2() //step 3 included here as well. Next step after this is step_5() "5. Add manually curated synonyms"
    {
        exit("\nDone step_2() May 28 already.\n");
        $file_append = $this->main_path_TRAM_809."/synonyms.txt";                  $this->WRITE     = fopen($file_append, "w"); fwrite($this->WRITE, implode("\t", $this->write_fields)."\n");
        $file_append = $this->main_path_TRAM_809."/synonyms_removed_in_step3.txt"; $this->WRITE_REP = fopen($file_append, "w"); fwrite($this->WRITE_REP, implode("\t", $this->write_fields_rep)."\n");
        // /* run data sources 
        self::process_data_source('NCBI');
        self::process_data_source('ASW');
        self::process_data_source('ODO');
        self::process_data_source('BOM');
        self::process_data_source('WOR');
        // */
        // /*
        $this->sh['COL']['syn_status']  = 'ambiguous synonym';      self::process_data_source('COL', true); // 3 minutes execution
        $this->sh['COL']['syn_status']  = 'synonym';                self::process_data_source('COL', true); // 19 minutes execution
        // */
        fclose($this->WRITE);
        fclose($this->WRITE_REP);
        Functions::start_print_debug($this->debug, $this->resource_id."_syn_totals");
        echo "\n-- End step_2() -- \n";
    }
    private function process_data_source($what, $postProcessYN = false)
    {   echo "\nProcessing synonyms from [$what]...\n";
        require_library('connectors/DHSourceHierarchiesAPI_v2'); $this->func = new DHSourceHierarchiesAPI_v2('');
        $this->what = $what;
        $meta = $this->func->get_meta($what, $postProcessYN);
        self::get_info_from_taxon_tab($meta);
    }
    private function get_info_from_taxon_tab($meta)
    {
        $what = $meta['what']; $i = 0;
        $m = 3963198/15; //used when caching COL
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            if($this->sh[$what]['syn_status'] == $rec['taxonomicStatus']) { // print_r($rec); //exit("\nstopx 1\n");
                /* NCBI Array(
                    [taxonID] => 1_1
                    [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1
                    [referenceID] => 
                    [acceptedNameUsageID] => 1
                    [parentNameUsageID] => 
                    [scientificName] => all
                    [taxonRank] => no rank
                    [taxonomicStatus] => synonym
                )
                ASW Array(
                    [taxonID] => inv-Abrana-cotti-Parker-1931
                    [scientificName] => Abrana cotti Parker, 1931
                    [taxonRank] => species
                    [taxonomicStatus] => invalid
                    [parentNameUsageID] => 
                    [taxonRemarks] => synonymous original name
                    [acceptedNameUsageID] => v-Ptychadena-schillukorum-(Werner-1908)
                    ***[nameAccordingTo] => Parker, 1931 , Proc. Zool. Soc. London, 1930
                    [furtherInformationURL] => http://research.amnh.org/herpetology/amphibia/index.html
                )
                ODO Array(
                    [taxonID] => Heliocharitidae
                    [acceptedNameUsageID] => Dicteriadidae 
                    [parentNameUsageID] => 
                    [scientificName] => Heliocharitidae
                    [taxonRank] => family
                    [furtherInformationURL] => https://www.pugetsound.edu/academics/academic-resources/slater-museum/biodiversity-resources/dragonflies/world-odonata-list2/
                    [taxonomicStatus] => synonym
                    [taxonRemarks] => 
                )
                BOM Array(
                    [taxonID] => Zanolidae
                    [scientificName] => Zanolidae McDunnough 1938
                    [parentNameUsageID] => 
                    [kingdom] => Metazoa
                    [phylum] => Arthropoda
                    [class] => Insecta
                    [order] => Lepidoptera
                    [family] => 
                    [genus] => 
                    [taxonRank] => 
                    [furtherInformationURL] => https://doi.org/10.3897/BDJ.6.e22236
                    [taxonomicStatus] => synonym
                    [taxonRemarks] => 
                    [acceptedNameUsageID] => Apatelodidae
                    [referenceID] => 10.3897/BDJ.6.e22236
                )
                WOR Array(
                    [taxonID] => 101234
                    [furtherInformationURL] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=101234
                    [acceptedNameUsageID] => 
                    [parentNameUsageID] => 101185
                    [scientificName] => Strobilidium proboscidiferum (Milne, 1886) Kahl, 1932
                    [taxonRank] => species
                    [taxonomicStatus] => synonym
                    [taxonRemarks] => 
                )
                COL Array(
                    [taxonID] => 316502
                    [identifier] => 
                    [datasetID] => 26
                    [datasetName] => ScaleNet in Species 2000 & ITIS Catalogue of Life: 20th February 2019
                    [acceptedNameUsageID] => 316423
                    [parentNameUsageID] => 
                    [taxonomicStatus] => synonym
                    [taxonRank] => species
                    [verbatimTaxonRank] => 
                    [scientificName] => Canceraspis brasiliensis Hempel, 1934
                    [kingdom] => Animalia
                    [phylum] => 
                    [class] => 
                    [order] => 
                    [superfamily] => 
                    [family] => 
                    [genericName] => Canceraspis
                    [genus] => Limacoccus
                    [subgenus] => 
                    [specificEpithet] => brasiliensis
                    [infraspecificEpithet] => 
                    [scientificNameAuthorship] => Hempel, 1934
                    [source] => 
                    [namePublishedIn] => 
                    [nameAccordingTo] => 
                    [modified] => 
                    [description] => 
                    [taxonConceptID] => 
                    [scientificNameID] => Coc-100-7
                    [references] => http://www.catalogueoflife.org/col/details/species/id/6a3ba2fef8659ce9708106356d875285/synonym/3eb3b75ad13a5d0fbd1b22fa1074adc0
                    [isExtinct] => 
                )
                */
                // $final[$rec['taxonID']] = array("aID" => $rec['acceptedNameUsageID'], 'n' => $rec['scientificName'], 'r' => $rec['taxonRank'], 's' => $rec['taxonomicStatus']);
                
                /* debug only - force assign
                // 326788 26 ScaleNet in Species 2000 & ITIS Catalogue of Life: 28th March 2018 326787 synonym species Icerya nuda Green, 1930 Animalia Icerya Crypticerya nuda Green, 1930 Coc-18232-2495 http://www.catalogueoflife.org/col/details/species/id/79daf66d28d88a076cbea2279d45c4cf/synonym/fb46c4638716d4b74f506b40a7349a21
                // 326788 26 ScaleNet in Species 2000 & ITIS Catalogue of Life: 28th March 2018 326787 synonym species Icerya nuda Green, 1930 Animalia Icerya Crypticerya nuda Green, 1930 Coc-18232-2495 http://www.catalogueoflife.org/annual-checklist/2015/details/species/id/79daf66d28d88a076cbea2279d45c4cf/synonym/fb46c4638716d4b74f506b40a7349a21
                $rec = Array(
                    'taxonID' => '326788',
                    'identifier' => '',
                    'datasetID' => '',
                    'datasetName' => '',
                    'acceptedNameUsageID' => '',
                    'parentNameUsageID' => '',
                    'taxonomicStatus' => 'synonym',
                    'taxonRank' => 'species',
                    'verbatimTaxonRank' => '',
                    'scientificName' => 'Icerya nuda Green, 1930',
                    'references' => 'http://www.catalogueoflife.org/col/details/species/id/79daf66d28d88a076cbea2279d45c4cf/synonym/fb46c4638716d4b74f506b40a7349a21'
                );
                */

                $accepted_id = false;
                if(in_array($what, array('COL', 'CLP'))) {
                    if($rec_acceptedNameUsageID = self::get_COL_acceptedNameUsageID_from_url($rec['references'])) {
                        $accepted_id = self::is_acceptedName_in_DH($what.":".$rec_acceptedNameUsageID);
                    }
                }
                else $accepted_id = self::is_acceptedName_in_DH($what.":".$rec['acceptedNameUsageID']); // 'NCBI', 'ASW', 'ODO', 'BOM', 'WOR'

                if($accepted_id) { //e.g. param is 'NCBI:1'
                    // echo " -found-"; //add this synonym to DH
                    if(in_array($what, array('ASW', 'BOM', 'ODO'))) $cont = true;
                    else { //COL, NCBI, WOR
                        /* breakdown when caching:
                        $cont = false;
                        // if($i >=  1    && $i < $m) $cont = true;     done
                        // if($i >=  $m   && $i < $m*2) $cont = true;   done
                        // if($i >=  $m*2 && $i < $m*3) $cont = true;   done
                        // if($i >=  $m*3 && $i < $m*4) $cont = true;   done
                        // if($i >=  $m*4 && $i < $m*5) $cont = true;   done
                        // if($i >=  $m*5 && $i < $m*6) $cont = true;   processing...
                        // if($i >=  $m*6 && $i < $m*7) $cont = true;   processing...
                        // if($i >=  $m*7 && $i < $m*8) $cont = true;   done
                        // if($i >=  $m*8 && $i < $m*9) $cont = true;   done
                        // if($i >=  $m*9 && $i < $m*10) $cont = true;  done
                        // if($i >=  $m*10 && $i < $m*11) $cont = true; done
                        // if($i >=  $m*11 && $i < $m*12) $cont = true; processing
                        // if($i >=  $m*12 && $i < $m*13) $cont = true; done
                        // if($i >=  $m*13 && $i < $m*14) $cont = true; processing
                        // if($i >=  $m*14 && $i < $m*15) $cont = true; done
                        // m = 264213.2
                        if(!$cont) continue;
                        */
                        if($with_dup_YN = self::with_duplicates_in_DH_YN($rec, $accepted_id)) {
                            // echo "\n-------------------------This synonym is excluded [$accepted_id] "; print_r($rec); echo "\n-------------------------\n";
                            /*
                            scientificName: The scientificName string of the synonym - Icerya nuda Green, 1930
                            source: The source hierarchy where the synonym came from - COL
                            acceptedNameUsageID: DH taxon to which synonym would be attached - EOL-000001941761
                            taxonID of other DH taxon for which there is a canonical match: EOL-000001941880
                            This synonym is excluded Array(
                                [taxonID] => 326788
                                [identifier] => 
                                [datasetID] => 
                                [datasetName] => 
                                [acceptedNameUsageID] => 
                                [parentNameUsageID] => 
                                [taxonomicStatus] => synonym
                                [taxonRank] => species
                                [verbatimTaxonRank] => 
                                [scientificName] => Icerya nuda Green, 1930
                                [references] => http://www.catalogueoflife.org/col/details/species/id/79daf66d28d88a076cbea2279d45c4cf/synonym/fb46c4638716d4b74f506b40a7349a21
                            )*/
                            $for_reporting = array('scientificName' => $rec['scientificName'], 'source' => $what, 'acceptedNameUsageID' => $accepted_id, 'taxonID' => $with_dup_YN);
                            // print_r($for_reporting);
                            /*Array(
                                [scientificName] => Icerya nuda Green, 1930
                                [source] => COL
                                [acceptedNameUsageID] => EOL-000001941761
                                [taxonID] => EOL-000001941880
                            )*/
                            // /* uncomment in real operation
                            self::write_report($for_reporting, $this->write_fields_rep, $this->WRITE_REP);
                            // */
                            // if($rec['taxonID'] == '326788') exit("\nstop muna\n");  //debug only
                            continue; //good
                        }
                        else {}
                    }
                    
                    // /* relevant metadata (if available):
                    $taxonRemarks = '';
                    if(in_array($what, array('IOC', 'ASW', 'ODO', 'BOM', 'WOR'))) $taxonRemarks = @$rec['taxonRemarks'];
                    $datasetID = $what;
                    if(in_array($what, array('COL', 'CLP'))) {
                        // see instructions Use COL-datasetID from original file. If datasetID is "Species 2000" or it is not available, use COL.
                        // see instructions Use COL-datasetID from original file. If datasetID is "Species 2000" or it is not available, use COL.
                        if($val = $rec['datasetID']) {
                            if($val == "Species 2000") $datasetID = 'COL';
                            else                       $datasetID = 'COL-'.$val;
                        }
                        else                           $datasetID = 'COL';
                    }
                    // */
                    
                    $save = array(
                    'taxonID' => $rec['taxonID'], //for minting next
                    'source' => $what, //"$what:".$rec['acceptedNameUsageID'],
                    'furtherInformationURL' => self::format_fIURL($rec, $what), //$rec['furtherInformationURL'],
                    'parentNameUsageID' => '', //$rec['parentNameUsageID'],
                    'scientificName' => $rec['scientificName'],
                    'taxonRank' => $rec['taxonRank'],
                    'taxonRemarks' => $taxonRemarks,    //relevant metadata (if available):
                    'datasetID' => $datasetID,          //relevant metadata (if available):
                    'canonicalName' => '',
                    'EOLid' => '',
                    'EOLidAnnotations' => '',
                    'higherClassification' => '',
                    'taxonomicStatus' => $rec['taxonomicStatus'], //'synonym',
                    'acceptedNameUsageID' => $accepted_id);
                    
                    // /* uncomment in real operation
                    self::write_report($save, $this->write_fields, $this->WRITE);
                    // */
                    @$this->debug['count synonyms'][$what]++; //stats
                    
                    // print_r($save); exit("\nsynonym included\n");
                }
                // else echo " -not found-"; //debug only
            }
        }
    }
    private function write_report($save_rec, $fields, $fileH)
    {
        $arr = array();
        foreach($fields as $f) $arr[] = @$save_rec[$f];
        // print_r($arr);
        fwrite($fileH, implode("\t", $arr)."\n");
    }
    private function with_duplicates_in_DH_YN($rec, $accepted_id)
    {
        $canonical = self::get_canonical($rec);
        // echo("\n[$canonical] [$accepted_id]\n");
        
        // /* manual --- testing phase
        if($rec['scientificName'] == 'The Myxobacteria') $canonical = 'Myxobacteria';
        if($rec['scientificName'] == 'The Mycoplasmas') $canonical = 'Mycoplasmas';
        // */
        
        // /* will trap questionable gnparser results
        if($canonical == 'The') {
            print_r($rec); exit("\ninvestigate 100\n");
        }
        // */
        
        $canonical_4sql = str_replace("'", "\'", $canonical);
        $sql = "SELECT t.* from DWH.newDH_optimal t WHERE t.canonicalName = '".$canonical_4sql."' AND t.taxonRank = '".$rec['taxonRank']."'";
        $sql .= " AND t.taxonID != '".$accepted_id."'"; //imperative

        // echo("\naaa\n$sql\n");
        $result = $this->mysqli->query($sql);
        $rows = array();
        while($result && $row=$result->fetch_assoc()) $rows[] = $row;
        if($rows) {
            // echo "\n-------------------------Found duplicate canonical in DH "; print_r($rows); echo "\n-------------------------\n";
            return $rows[0]['taxonID'];
            return true;
            /*[0] => Array(
                        [taxonID] => EOL-000000017878
                        [scientificName] => Shewanella affinis Ivanova, Nedashkovskaya, Sawabe, Zhukova, Frolova, Nicolau, Mikhailov & Bowman, 2004
                        [canonicalName] => Shewanella affinis
                        [source] => WOR:396097
                        [taxonRank] => species
                    )
            Please report all the synonyms that were removed during this step 
            (scientificName, source, acceptedNameUsageID, taxonID of other DH taxon for which there is a canonical match).
            */
        }
        return false;
    }
    private function get_canonical($rec)
    {
        $sci = $rec['scientificName'];
        // $sci = 'Tricornina (Bicornina) Jordan, 1964'; //debug only force assign
        // $sci = 'Tricornina (Bicornina) jordan, 1964'; //debug only force assign
        // $sci = 'Ceroputo pilosellae Šulc, 1898'; //debug only force assign
        /* new May 26, 2019 - e.g. $sci is 'The Myxobacteria'. Worked but maybe can be used in the future. Not needed now.
        if(substr($sci,0,4) == 'The ') return trim(substr($sci,4,strlen($sci)));
        */
        $json = $this->func->get_json_from_cache($sci); $eol_canonical = self::parse_json_get_canonical($json);
        return $eol_canonical;
    }
    private function parse_json_get_canonical($json)
    {
        $obj = json_decode($json); //print_r($obj); //exit;
        if($val = @$obj->canonicalName->valueRanked) return $val;
        else return $obj->verbatim;
    }
    private function format_fIURL($rec, $what)
    {
        if(in_array($what, array('COL', 'CLP'))) return $rec['references'];
        else                                     return $rec['furtherInformationURL'];
    }
    private function get_COL_acceptedNameUsageID_from_url($url)
    {   /* http://www.catalogueoflife.org/col/details/species/id/[acceptedNameUsageID]]/synonym/[taxonIDofSynonym]
        So this synonym:
        316502 26 ScaleNet in Species 2000 & ITIS Catalogue of Life: 28th March 2018 316423 synonym species Canceraspis brasiliensis Hempel, 1934 Animalia Canceraspis Limacoccus brasiliensis Hempel, 1934 Coc-100-7 
        http://www.catalogueoflife.org/annual-checklist/2015/details/species/id/6a3ba2fef8659ce9708106356d875285/synonym/3eb3b75ad13a5d0fbd1b22fa1074adc0	
        points to this DH taxon:
        EOL-000001939037 COL:6a3ba2fef8659ce9708106356d875285 http://www.catalogueoflife.org/col/details/species/id/6a3ba2fef8659ce9708106356d875285	EOL-000001939036 Limacoccus brasiliensis (Hempel, 1934) species COL-26 Limacoccus brasiliensis
        */
        if(preg_match("/\/species\/id\/(.*?)\/synonym\//ims", $url, $arr)) return $arr[1];
    }
    private function is_acceptedName_in_DH($source_id) //e.g. 'NCBI:944587' OR 'COL:6a3ba2fef8659ce9708106356d875285'
    {   /*(b) Check if the accepted name is in the DH
    From the pool of eligible synonyms, we only want to import synonyms of taxa we are actually using in the DH. To confirm usage, 
    check if the acceptedNameUsageID of the synonym is represented in the DH source column, with the proper source acronym prefix. For example, this synonym in the NCBI resource:
    944587_1 https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=944587	944587 Achlya sparrowi species synonym
    points to this DH taxon:
    EOL-000000094375 NCBI:944587,CLP:45bd70145e07edfd1e59299651108cb6 https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=944587	EOL-000000094355 Achlya sparrowii species NCBI Achlya sparrowii
    so we would want to import it and assign EOL-000000094375 as its acceptedNameUsageID.
        */
        $source_id_4sql = str_replace("'", "\'", $source_id);
        $sql = "SELECT h.taxonID, h.source_id FROM DWH.taxonID_source_ids_newDH h WHERE h.source_id = '".$source_id_4sql."'";
        $row = self::run_sql($sql);
        if($val = $row['taxonID']) return $val;
        return false;
    }
    private function run_sql($sql)
    {
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) return $row;
        return false;
    }
    function create_append_text($source = '', $table = '') //do only once
    {
        exit("\nDone already\n");
        $source = $this->main_path."/new_DH_before_step4.txt";          //obsolete
        $source = $this->main_path_TRAM_809."/new_DH_taxonStatus.txt";  //correct input
        
        // /*
        $table = 'taxonID_source_ids_newDH';
        $file_append = $this->main_path_TRAM_809."/".$table.".txt";
        $WRITE = fopen($file_append, "w"); //will overwrite existing
        // */
        
        // /* for 3. Check for conflicts with DH valid/accepted name assertions
        $table2 = 'newDH_optimal';
        $file_append2 = $this->main_path_TRAM_809."/".$table2.".txt";
        $WRITE2 = fopen($file_append2, "w"); //will overwrite existing
        // */

        require_library('connectors/DH_v1_1_Mapping_EOL_IDs'); $func = new DH_v1_1_Mapping_EOL_IDs('');
        $i = 0;
        foreach(new FileIterator($source) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec); // print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonID] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherInformationURL] => 
                [parentNameUsageID] => 
                [scientificName] => Life
                [taxonRank] => clade
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Life
                [EOLid] => 2913056
                [EOLidAnnotations] => 
            )*/
            
            // /*
            $source_ids = $func->get_all_source_identifiers($rec['source']);
            foreach($source_ids as $source_id) {
                $arr = array();
                $arr = array($rec['taxonID'], $source_id);
                fwrite($WRITE, implode("\t", $arr)."\n");
            }
            // */
            
            // /* for 3. Check for conflicts with DH valid/accepted name assertions --- this was added later on
            $arr = array();
            $arr = array($rec['taxonID'], $rec['scientificName'], $rec['canonicalName'], $rec['source'], $rec['taxonRank']);
            fwrite($WRITE2, implode("\t", $arr)."\n");
            // */
        }
        // /* 
        fclose($WRITE);  $func->append_to_MySQL_table($table, $file_append);
        // */
        fclose($WRITE2); $func->append_to_MySQL_table($table2, $file_append2);
        echo "\nend create_append_text()\n";
    }
    function step_1()
    {   echo "\nStart step 1...\n";
        $this->debug = array();
        // $this->retired_old_DH_taxonID = array();
        $file = $this->main_path."/new_DH_before_step4.txt"; //last DH output of TRAM-808
        $file = $this->main_path."/with_higherClassification/1558361160.txt"; //last DH output of TRAM-808 --> with higherClassification
        $file = $this->main_path."/with_higherClassification/1558827333.txt"; //latest
        $file = $this->main_path."/with_higherClassification/1559013226.txt"; //latest May 28, 2019
        /* initialize info global ------------------------------------------------------------------------------*/
        require_library('connectors/DH_v1_1_postProcessing');
        $func = new DH_v1_1_postProcessing(1);
        /*We want to add taxonomicStatus to DH taxa based on the following rules:
        taxonomicStatus: accepted
        Apply to all descendants of the following taxa:
            Archaeplastida (EOL-000000097815)
            Cyanobacteria (EOL-000000000047)
            Fungi (EOL-000002172573) EXCEPT Microsporidia (EOL-000002172574)
            Gyrista (EOL-000000085512)
            Eumycetozoa (EOL-000000096158)
            Protosteliida (EOL-000000097604)
            Dinoflagellata (EOL-000000025794)
        taxonomicStatus: valid
            Apply to all other taxa including Microsporidia (EOL-000002172574), which is a descendant of Fungi.
        */
        self::get_taxID_nodes_info($file); //for new DH
        
        /* old
        $children_of['Microsporidia'] = $func->get_descendants_of_taxID("EOL-000002172574", false, $this->descendants); echo "\nDone Microsporidia";
        $children_of['Archaeplastida'] = $func->get_descendants_of_taxID("EOL-000000097815", false, $this->descendants); echo "\nDone Archaeplastida";
        $children_of['Cyanobacteria'] = $func->get_descendants_of_taxID("EOL-000000000047", false, $this->descendants); echo "\nDone Cyanobacteria";
        $children_of['Fungi'] = $func->get_descendants_of_taxID("EOL-000002172573", false, $this->descendants); echo "\nDone Fungi";
        $children_of['Gyrista'] = $func->get_descendants_of_taxID("EOL-000000085512", false, $this->descendants); echo "\nDone Gyrista";
        $children_of['Eumycetozoa'] = $func->get_descendants_of_taxID("EOL-000000096158", false, $this->descendants); echo "\nDone Eumycetozoa";
        $children_of['Protosteliida'] = $func->get_descendants_of_taxID("EOL-000000097604", false, $this->descendants); echo "\nDone Protosteliida";
        $children_of['Dinoflagellata'] = $func->get_descendants_of_taxID("EOL-000000025794", false, $this->descendants); echo "\nDone Dinoflagellata\n";
        */
        
        $children = $func->get_descendants_of_taxID("EOL-000002172574", false, $this->descendants); echo "\nDone Microsporidia";
        foreach($children as $child) $children_of['Microsporidia'][$child] = '';
        $children = $func->get_descendants_of_taxID("EOL-000000097815", false, $this->descendants); echo "\nDone Archaeplastida";
        foreach($children as $child) $children_of['Archaeplastida'][$child] = '';
        $children = $func->get_descendants_of_taxID("EOL-000000000047", false, $this->descendants); echo "\nDone Cyanobacteria";
        foreach($children as $child) $children_of['Cyanobacteria'][$child] = '';
        $children = $func->get_descendants_of_taxID("EOL-000002172573", false, $this->descendants); echo "\nDone Fungi";
        foreach($children as $child) $children_of['Fungi'][$child] = '';
        $children = $func->get_descendants_of_taxID("EOL-000000085512", false, $this->descendants); echo "\nDone Gyrista";
        foreach($children as $child) $children_of['Gyrista'][$child] = '';
        $children = $func->get_descendants_of_taxID("EOL-000000096158", false, $this->descendants); echo "\nDone Eumycetozoa";
        foreach($children as $child) $children_of['Eumycetozoa'][$child] = '';
        $children = $func->get_descendants_of_taxID("EOL-000000097604", false, $this->descendants); echo "\nDone Protosteliida";
        foreach($children as $child) $children_of['Protosteliida'][$child] = '';
        $children = $func->get_descendants_of_taxID("EOL-000000025794", false, $this->descendants); echo "\nDone Dinoflagellata\n";
        foreach($children as $child) $children_of['Dinoflagellata'][$child] = '';
        
        echo "\nMicrosporidia: ".count($children_of['Microsporidia'])."\n";
        echo "\nArchaeplastida: ".count($children_of['Archaeplastida'])."\n";
        echo "\nCyanobacteria: ".count($children_of['Cyanobacteria'])."\n";
        echo "\nFungi: ".count($children_of['Fungi'])."\n";
        echo "\nGyrista: ".count($children_of['Gyrista'])."\n";
        echo "\nEumycetozoa: ".count($children_of['Eumycetozoa'])."\n";
        echo "\nProtosteliida: ".count($children_of['Protosteliida'])."\n";
        echo "\nDinoflagellata: ".count($children_of['Dinoflagellata'])."\n";
        // exit("\nstopx\n");
        unset($this->descendants);
        /* loop new DH -----------------------------------------------------------------------------------------*/
        $file_append = $this->main_path_TRAM_809."/new_DH_taxonStatus.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line) {
            $i++; if(($i % 300000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields);
                $fields[] = 'taxonomicStatus'; //print_r($fields);
                fwrite($WRITE, implode("\t", $fields)."\n");
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            //------------------------------------------------------start taxonomicStatus
            // Microsporidia    : 1465
            // Protosteliida    : 8
            // Cyanobacteria    : 1334
            // Gyrista          : 9729
            // Eumycetozoa      : 1406
            // Dinoflagellata   : 1470
            // Fungi            : 148395
            // Archaeplastida   : 443598
            $taxonID = $rec['taxonID'];
            if(isset($children_of['Microsporidia'][$taxonID])) $rec['taxonomicStatus'] = 'valid';
            if(!@$rec['taxonomicStatus']) {
                if(isset($children_of['Protosteliida'][$taxonID])) $rec['taxonomicStatus'] = 'accepted';
                elseif(isset($children_of['Cyanobacteria'][$taxonID])) $rec['taxonomicStatus'] = 'accepted';
                elseif(isset($children_of['Gyrista'][$taxonID])) $rec['taxonomicStatus'] = 'accepted';
                elseif(isset($children_of['Eumycetozoa'][$taxonID])) $rec['taxonomicStatus'] = 'accepted';
                elseif(isset($children_of['Dinoflagellata'][$taxonID])) $rec['taxonomicStatus'] = 'accepted';
                elseif(isset($children_of['Fungi'][$taxonID])) $rec['taxonomicStatus'] = 'accepted';
                elseif(isset($children_of['Archaeplastida'][$taxonID])) $rec['taxonomicStatus'] = 'accepted';
            }
            if(!@$rec['taxonomicStatus']) $rec['taxonomicStatus'] = 'valid';
            //------------------------------------------------------end taxonomicStatus
            // print_r($rec); exit;
            /*Array(
                [taxonID] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherInformationURL] => 
                [parentNameUsageID] => 
                [scientificName] => Life
                [taxonRank] => clade
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Life
                [EOLid] => 2913056
                [EOLidAnnotations] => 
                [higherClassification] => 
                [taxonomicStatus] => valid
            )*/
            /* start writing */
            $save = array();
            foreach($fields as $head) $save[] = $rec[$head];
            fwrite($WRITE, implode("\t", $save)."\n");
        }
        fclose($WRITE);
        Functions::start_print_debug($this->debug, $this->resource_id."_step1");
    }
    private function get_taxID_nodes_info($txtfile)
    {
        $this->taxID_info = array(); $this->descendants = array(); //initialize global vars
        $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 300000) == 0) echo "\n".number_format($i)." ";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec); // print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonid] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherinformationurl] => 
                [parentnameusageid] => 
                [scientificname] => Life
                [taxonrank] => clade
                [taxonremarks] => 
                [datasetid] => trunk
                [canonicalname] => Life
                [eolid] => 2913056
                [eolidannotations] => 
            )*/
            // $this->taxID_info[$rec['uid']] = array("pID" => $rec['parent_uid'], 'r' => $rec['rank'], 'n' => $rec['name'], 's' => $rec['sourceinfo'], 'f' => $rec['flags']); //used for ancesty and more
            $this->descendants[$rec['parentnameusageid']][$rec['taxonid']] = ''; //used for descendants (children)
        }
    }
}
?>