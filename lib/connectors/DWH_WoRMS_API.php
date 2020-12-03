<?php
namespace php_active_record;
/* connector: [dwh_worms_TRAM_798.php] - https://eol-jira.bibalex.org/browse/TRAM-798
                                       - https://eol-jira.bibalex.org/browse/TRAM-988 
taxonomicStatus values
----- accepted  total: 1
----- unaccepted  total: 1
----- doubtful  total: 1
-----   total: 1
*/
class DWH_WoRMS_API
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();

        //start TRAM-797 -----------------------------------------------------------
        $this->prune_further = array();
        $this->dwca['iterator_options'] = array('row_terminator' => "\n");
        /* OLD
        if(Functions::is_production())  $this->dwca_file = "http://www.marinespecies.org/export/eol/WoRMS2EoL.zip";
        else                            $this->dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
        */
        // /* NEW
        $this->dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "WoRMS2EoL_zip.tar.gz";
        $this->duplicates = array();
        // */
        
        $this->webservice['AphiaRecordByAphiaID'] = "http://www.marinespecies.org/rest/AphiaRecordByAphiaID/";
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'resource_id' => 26);
        $this->download_options["expire_seconds"] = false; //false means it will use cache always
                                                           //false - primarily used in lookup from WoRMS using AphiaID
        $this->download_options["expire_seconds"] = 60*60*24*30;
        $this->gnparser_api = 'https://parser.globalnames.org/api?q=';
        $this->WoRMS_report = CONTENT_RESOURCE_LOCAL_PATH."reports/duplicates_".date("Y_m_d").".txt";
    }
    // ----------------------------------------------------------------- start TRAM-797 -----------------------------------------------------------------
    private function start()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "taxon.tab", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $tables['taxa'] = 'taxon.tab';
        return array("temp_dir" => $temp_dir, "tables" => $tables);
    }
    function start_WoRMS()
    {
        if(Functions::is_production()) {
            if(!($info = self::start())) return; //uncomment in real operation

            $this->extension_path = $info['temp_dir'];
            self::main_WoRMS();
            $this->archive_builder->finalize(TRUE);
            if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);

            // remove temp dir
            recursive_rmdir($info['temp_dir']);
            echo ("\n temporary directory removed: " . $info['temp_dir']);
        }
        else { //local development only
            $info = Array('temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_52843/',
                          'tables' => Array('taxa' => "taxon.tab"));

            /* run to fill-in $info above:
            if(!($info = self::start())) return; //uncomment in real operation
            print_r($info); exit;
            */

            $this->extension_path = $info['temp_dir'];
            self::main_WoRMS();
            $this->archive_builder->finalize(TRUE);
            if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
        }
        print_r($this->debug);
        echo "\nDuplicates report: [$this->WoRMS_report]\n";
        copy($this->WoRMS_report, CONTENT_RESOURCE_LOCAL_PATH."reports/duplicates.txt");
        Functions::show_totals($this->WoRMS_report);
    }
    private function ids_for_2_and_3()
    {
        $i = 0; $final = array();
        foreach(new FileIterator($this->extension_path.'taxon.tab') as $line => $row) {
            $i++;
            if(($i % 200000) == 0) echo "\n count:[$i] ";
            if($i == 1) {
                $fields = explode("\t", $row);
                continue;
            }
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($fields as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            $rec = self::format_ids($rec);
            // print_r($rec); exit("\n111\n");
            /*Array(
                [taxonID] => 1
                [furtherInformationURL] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=1
                [referenceID] => WoRMS:citation:1
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 
                [scientificName] => Biota
                [namePublishedIn] => 
                [kingdom] => 
                [phylum] => 
                [class] => 
                [order] => 
                [family] => 
                [genus] => 
                [taxonRank] => kingdom
                [vernacularName] => Biota
                [taxonomicStatus] => accepted
                [taxonRemarks] => 
                [rightsHolder] => 
                [accessRights] => 
                [datasetName] => 
            )*/
            if(stripos($rec['taxonRemarks'], "REMAP_ON_EOL") !== false) $final[$rec['taxonID']] = ''; //string is found
            if($rec['taxonRank'] == 'species') {
                if(stripos($rec['scientificName'], "incertae sedis") !== false) $final[$rec['taxonID']] = ''; //string is found
            }
        }
        // print_r($final); exit("\nTaxa added to removed_branches: ".count($final)."\n");
        return array_keys($final);
    }
    private function main_WoRMS()
    {   /* from TRAM-798
        $include['123081'] = "Crinoidea";       $include['6'] = "Bacteria";         $include['599656'] = "Glaucophyta";     $include['852'] = "Rhodophyta"; 
        $include['17638'] = "Cryptophyta";      $include['369190'] = "Haptophyta";  $include['341275'] = "Centrohelida";    $include['536209'] = "Alveolata"; 
        $include['368898'] = "Heterokonta";     $include['582420'] = "Rhizaria";    $include['582161'] = "Euglenozoa";      $include['582180'] = "Malawimonadea"; 
        $include['582179'] = "Jakobea";         $include['582221'] = "Oxymonadida"; $include['582175'] = "Parabasalia";     $include['562616'] = "Hexamitidae"; 
        $include['562613'] = "Enteromonadidae"; $include['451649'] = "Heterolobosea"; 
        $include['582189'] = "Discosea";        $include['582188'] = "Tubulinea";   $include['103424'] = "Apusomonadidae";  $include['707610'] = "Rozellidae"; 
        $include['582263'] = "Nucleariida";     $include['582261'] = "Ministeriida";$include['580116'] = "Choanoflagellatea"; $include['391862'] = "Ichthyosporea";
        */
        // /* new batch for TRAM-988
        $include['1803'] = "Brachiopoda Duméril, 1805";     
        $include['146142'] = "Bryozoa";                     $include['2081'] = "Chaetognatha";  $include['1267'] = "Cnidaria Hatschek, 1888";
        $include['1248'] = "Ctenophora Eschscholtz, 1829";  $include['22586'] = "Cycliophora Funch & Kristensen, 1995";
        $include['14221'] = "Dicyemida van Beneden";        $include['1806'] = "Echinodermata Bruguière, 1791 [ex Klein, 1734]";
        $include['1271'] = "Entoprocta Nitsche, 1869";      $include['14262'] = "Gnathostomulida Riedl, 1969";
        $include['1818'] = "Hemichordata Bateson, 1885";    $include['101060'] = "Kinorhyncha Reinhard, 1885";
        $include['101061'] = "Loricifera Kristensen, 1983"; $include['51'] = "Mollusca";
        $include['845959'] = "Multicrustacea Regier, Shultz, Zwick, Hussey, Ball, Wetzer, Martin & Cunningham, 2010";
        $include['233983'] = "Myzostomida von Graff, 1877"; $include['799'] = "Nematoda";   
        $include['845957'] = "Oligostraca Zrzavý, Hypša & Vlášková, 1997";  $include['14220'] = "Orthonectida Giard, 1877";
        $include['1789'] = "Phoronida Hatschek, 1888";  $include['22737'] = "Placozoa Grell, 1971";  
        $include['558'] = "Porifera Grant, 1836";   $include['101063'] = "Priapulida Théel, 1906";  $include['1067'] = "Remipedia Yager, 1981";
        $include['1268'] = "Sipuncula Stephen, 1964";   $include['146420'] = "Tunicata Lamarck, 1816";
        // */
        $this->include = $include;
        
        /* per Katja: https://eol-jira.bibalex.org/browse/TRAM-988?focusedCommentId=65403&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65403
        Hi Eli,
        I'd like to make a few more tweaks to the WoRMS for DH resource:
        1. Please remove the following branches: Polychaeta, Ochrophyta, Bacteria, Alveolata, and Rhizaria. It was too difficult to try to align the WoRMS data for these taxa with other resources, so they are now integrated in the Annelida and Microbes patches.
        #2. There are still a few taxa with [REMAP_ON_EOL] in the taxonRemarks column. Please remove those and their children, currently 33 taxa total.
        #3. Please also remove all taxa of rank species that contain the string "incertae sedis" in the scientificName.
        Thanks!
        Polychaeta  - $include['883'] = "Polychaeta Grube, 1850";
        Ochrophyta  - $include['345465'] = "Ochrophyta Cavalier-Smith, 1995";
        Bacteria    - $include['6'] = "Bacteria";
        Alveolata   - $include['536209'] = "Alveolata Cavalier-Smith";
        Rhizaria    - $include['582420'] = "Rhizaria";
        */
        $removed_branches = array();
        $ids_2remove = self::ids_for_2_and_3(); //#2 and #3 above
        foreach($ids_2remove as $id) $removed_branches[$id] = '';
        
        /* OBSOLETE now for TRAM-988
        // actual spreadsheet: https://docs.google.com/spreadsheets/d/11jQ-6CUJIbZiNwZrHqhR_4rqw10mamdA17iaNELWCBQ/edit?usp=sharing
        $params['spreadsheetID'] = '11jQ-6CUJIbZiNwZrHqhR_4rqw10mamdA17iaNELWCBQ';
        $params['range']         = 'Sheet1!A2:B2000'; //actual range is up to B1030 only as of Aug 23, 2018. I set B2000 to put allowance for possible increase.
        $parts = self::get_removed_branches_from_spreadsheet($params);
        $removed_branches = $parts['removed_brances'];
        // $one_word_names = $parts['one_word_names']; may not be needed anymore...
        echo "\nremoved_branches total: ".count($removed_branches)."\n";
        */
        
        /*
        //IDs from WoRMS_DH_undefined_acceptedName_ids.txt -> from initial run
        $ids_2remove = array(146143, 179477, 179847, 103815, 143816, 851581, 427887, 559169, 1026180, 744813, 115400, 176036, 603470, 744962, 744966, 744967, 135564, 100983, 427861, 864183, 427860, 1005667);
        foreach($ids_2remove as $id) $removed_branches[$id] = '';
        */
        
        // /*IDs from WoRMS_DH_undefined_parent_ids.txt - from initial run TRAM-988
        $ids_2remove = array(1402365, 1411195, 1403036, 1412780, 1408309, 1413119);
        foreach($ids_2remove as $id) $removed_branches[$id] = '';
        // */
        
        $taxID_info = self::get_taxID_nodes_info();
        echo "\ntaxID_info (taxon.tab) total rows: ".count($taxID_info)."\n";
        
        /* just test
        $id = 156099;
        $id = 132874;
        $parentID = self::get_parentID_for_comparison($id, $taxID_info);
        exit("\n-end test-\n");
        */

        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...WoRMS...\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 200000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            $rec = self::format_ids($rec);
            // print_r($rec); //exit;
            /* good debug
            if(isset($include[$rec['taxonID']])) print_r($rec);
            */
            /*Array(
                [taxonID] => 1457230
                [furtherInformationURL] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=1457230
                [referenceID] => WoRMS:citation:1457230
                [acceptedNameUsageID] => 517638
                [parentNameUsageID] => 
                [scientificName] => Bebryce verrucosa Stiasny, 1942
                [namePublishedIn] => Stiasny, G. (1942). Ergebnisse der Nachuntersuchung der Muriceidae (Gorgonaria) der Siboga-Expedition. <em>Zoologischer Anzeiger.</em> 140 (9/10), 192–199.
                [kingdom] => Animalia
                [phylum] => Cnidaria
                [class] => Anthozoa
                [order] => Alcyonacea
                [family] => Plexauridae
                [genus] => Bebryce
                [taxonRank] => species
                [vernacularName] => Bebryce verrucosa --> this was manually added, canonical name generated by gnparser
                [taxonomicStatus] => unaccepted
                [taxonRemarks] => 
                [rightsHolder] => 
                [accessRights] => 
                [datasetName] => 
            )*/
            
            //start filter -----------------------------------------------------------------------------------------------------------------------------
            // 2. Remove taxa that have a blank entry for taxonomicStatus.
            if($rec['taxonomicStatus'] == '') {
                $filtered_ids[$rec['taxonID']] = '';
                $removed_branches[$rec['taxonID']] = '';
                continue;
            }

            // 1. Remove taxa whose parentNameUsageID points to a taxon that has taxonomicStatus:synonym or not 'accepted'
            if($parent_id = $rec['parentNameUsageID']) {
                if($parent_rek = @$taxID_info[$parent_id]) {
                    /* e.g. $taxID_info[$parent_id]
                    [535899] => Array(
                                [pID] => 535589
                                [r] => species
                                [s] => accepted
                            )
                    */
                    if($parent_rek['s'] != 'accepted') {
                        $filtered_ids[$rec['taxonID']] = '';
                        $removed_branches[$rec['taxonID']] = '';
                        continue;
                    }
                }
                else { //there is a parent but there is no record for the parent -> just set the parent to blank
                    $rec['parentNameUsageID'] = ''; //OK to do this.
                    // $this->debug['parent_id that is not in taxon.txt'][$parent_id] = '';
                }
            }
            
            // Do the same for taxa those acceptedNameUsageID points to a taxon that has taxonomicStatus:synonym or not 'accepted'
            if($accepted_id = $rec['acceptedNameUsageID']) {
                if($accepted_rek = @$taxID_info[$accepted_id]) {
                    /* e.g. $taxID_info[$accepted_id]
                    [535899] => Array(
                                [pID] => 535589
                                [r] => species
                                [s] => accepted
                            )
                    */
                    if($accepted_rek['s'] != 'accepted') {
                        $filtered_ids[$rec['taxonID']] = '';
                        $removed_branches[$rec['taxonID']] = '';
                        continue;
                    }
                }
                else { //there is an acceptedNameUsageID but there is no record in taxon.txt
                    $filtered_ids[$rec['taxonID']] = '';
                    $removed_branches[$rec['taxonID']] = '';
                    // $this->debug['accepted_id that is not in taxon.txt'][$accepted_id] = '';
                    continue;
                }
            }
            //end filter -----------------------------------------------------------------------------------------------------------------------------
            
            $will_cont = false;
            $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $include)) $will_cont = true; //this will actually include what is in the branch
            
            //==============================================================================
            if($will_cont) {
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $filtered_ids[$rec['taxonID']] = '';
                    $removed_branches[$rec['taxonID']] = '';
                    continue;
                }

                //now check the acceptedNameUsageID:
                if($accepted_id = $rec['acceptedNameUsageID']) {
                    $ancestry = self::get_ancestry_of_taxID($accepted_id, $taxID_info);
                    if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                        $filtered_ids[$rec['taxonID']] = '';
                        $removed_branches[$rec['taxonID']] = '';
                        continue;
                    }
                    //another filter:
                    if(!isset($taxID_info[$accepted_id])) {
                        continue;
                    }
                }
                $inclusive_taxon_ids[$rec['taxonID']] = '';
            }
            //==============================================================================
            // ------------- start flag duplicates --------------------
            // self::flag_duplicates($rec); DO NOT PUT THIS HERE... PUT IT BELOW BEFORE write_taxon_DH()
            // ------------- end flag duplicates --------------------
            
        } //end loop
        echo "\ntotal inclusive_taxon_ids: ".count($inclusive_taxon_ids)."\n";
        
        //start 2nd loop
        $i = 0; echo "\nStart main process 2...WoRMS...\n";
        echo "\nremoved_branches total: ".count($removed_branches)."\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 200000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            $rec = self::format_ids($rec);
            
            if(isset($inclusive_taxon_ids[$rec['taxonID']])) {
                if(isset($filtered_ids[$rec['taxonID']])) continue;
                if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
                if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;
                if(isset($removed_branches[$rec['taxonID']])) continue;
                if(isset($removed_branches[$rec['acceptedNameUsageID']])) continue;
                if(isset($removed_branches[$rec['parentNameUsageID']])) continue;
                
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) continue;
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $filtered_ids)) continue;
                
                if($rec['taxonomicStatus'] == 'accepted' && $rec['acceptedNameUsageID']) { //it didn't go here anyway...
                    echo "\n it never went here...from loop 2 \n";
                    if($accepted_rek = @$taxID_info[$rec['acceptedNameUsageID']]) {
                        if($accepted_rek['s'] == 'accepted') {
                            $status_from_api = self::status_from_api($rec['taxonID']);
                            if($status_from_api != 'accepted') {
                                $rec['taxonomicStatus'] = $status_from_api;
                                $rec['parentNameUsageID'] = '';
                            }
                            else $this->debug['accepted but with acceptedNameUsageID'][$rec['taxonID']] = '';
                        }
                    }
                }
                
                // ------------- start flag duplicates --------------------
                self::flag_duplicates($rec, $taxID_info);
                // ------------- end flag duplicates --------------------
                
                // /* build new set of $taxID_info 
                $taxID_info2[$rec['taxonID']] = array("sn" => $rec['scientificName'], 'cn' => $rec['vernacularName']);
                // */
                
                /* now moved to a 3rd loop, now with the introduction of the duplicates
                self::write_taxon_DH($rec);
                */
            }
        }
        /* debug only
        print_r($this->eli);
        print_r($this->duplicates["Haliclona varia"]); exit("\n-end munax-\n");
        */
        $removed_duplicate_taxon_ids = self::choose_1_among_duplicates($taxID_info, $taxID_info2);
        
        //start 3rd loop - this loop is basically just copied from loop 2. Except for the last part -> write_taxon_DH()
        $i = 0; echo "\nStart main process 2...WoRMS...\n";
        echo "\nremoved_branches total: ".count($removed_branches)."\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 200000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            $rec = self::format_ids($rec);
            
            if(isset($inclusive_taxon_ids[$rec['taxonID']])) {
                if(isset($filtered_ids[$rec['taxonID']])) continue;
                if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
                if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;
                if(isset($removed_branches[$rec['taxonID']])) continue;
                if(isset($removed_branches[$rec['acceptedNameUsageID']])) continue;
                if(isset($removed_branches[$rec['parentNameUsageID']])) continue;
                
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) continue;
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $filtered_ids)) continue;
                
                if($rec['taxonomicStatus'] == 'accepted' && $rec['acceptedNameUsageID']) { //it didn't go here anyway...
                    echo "\n it never went here...from loop 3 \n";
                    if($accepted_rek = @$taxID_info[$rec['acceptedNameUsageID']]) {
                        if($accepted_rek['s'] == 'accepted') {
                            $status_from_api = self::status_from_api($rec['taxonID']);
                            if($status_from_api != 'accepted') {
                                $rec['taxonomicStatus'] = $status_from_api;
                                $rec['parentNameUsageID'] = '';
                            }
                            else $this->debug['accepted but with acceptedNameUsageID'][$rec['taxonID']] = '';
                        }
                    }
                }
                
                $taxonID = $rec['taxonID'];
                if(isset($removed_duplicate_taxon_ids[$taxonID])) continue;
                if(!isset($taxID_info2[$taxonID])) continue; //just an insurance
                self::write_taxon_DH($rec);
            }
        }//end 3rd loop

    }
    private function choose_1_among_duplicates($taxID_info, $taxID_info2)
    {
        /*[Neolithodes indicus] => Array(
                    [Neolithodes] => Array(
                            [species] => Array(
                                    [0] => 1457236
                                )
                        )
                )
        */
        $WRITE = fopen($this->WoRMS_report, "w"); fclose($WRITE);
        $removed = array(); $this->removed = array();
        foreach($this->duplicates as $sciname => $rec) {
            foreach($rec as $genus_parent => $rec2) {
                foreach($rec2 as $rank => $rec3) {
                    if(count($rec3) > 1) {
                        $this->removed = array_merge($this->removed, $removed);
                        echo "\nDuplicate: [$sciname][$genus_parent][$rank]";
                        // print_r($rec3);
                        @$final_duplicates++;

                        // Duplicate: [Porella rhomboidalis][Porella][species]
                        //     [0] => 1406933
                        //     [1] => 1406934
                        // Duplicate: [Cytheropteron nealei][Cytheropteron][species]
                        //     [0] => 423316
                        //     [1] => 814976
                        //     [2] => 814978
                        /*
                        $taxID_info2[taxonID] = array("sn" => $rec['scientificName'], 'cn' => $rec['vernacularName']);
                        $taxID_info[taxonID] = array("pID" => $rec['parentNameUsageID'], 'r' => $rec['taxonRank'], 's' => $rec['taxonomicStatus'])
                        status     = $taxID_info[$taxonID]['s'];
                        rank       = $taxID_info[$taxonID]['r'];
                        sciname    = $taxID_info2[$taxonID]['sn'];
                        canonical  = $taxID_info2[$taxonID]['cn'];
                        authorship = trim(str_ireplace($taxID_info2[$taxonID]['cn'], "", $taxID_info2[$taxonID]['sn']));
                        */
                        $options = $rec3;
                        if($GLOBALS['ENV_DEBUG']) {echo "\n0-"; print_r($options);}
                        
                        // start removing duplicates...
                        $i = -1;
                        $possible_bring_back = array();
                        $removed = array();
                        foreach($rec3 as $taxonID) { $i++;
                            if($taxID_info[$taxonID]['s'] == 'doubtful') {
                                $removed[] = $taxonID;
                                $possible_bring_back[] = $taxonID;
                            }
                            // accepted | doubtful - reject 'doubtful' status
                        }
                        
                        $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                        if($GLOBALS['ENV_DEBUG']) {echo "1-"; print_r($options);}
                        
                        /* copied block
                        if(count($options) == 0) { //just pick 1
                            $removed = array_diff($removed, $possible_bring_back);
                            if(count($possible_bring_back) == 2) $removed[] = $possible_bring_back[1];
                            elseif(count($possible_bring_back) == 3) {
                                $removed[] = $possible_bring_back[1];
                                $removed[] = $possible_bring_back[2];
                            }
                            else exit("\nA case with 4 duplicates!\n");
                            $options = $possible_bring_back;
                            $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                            if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        }
                        */
                        
                        $before_2 = $options;
                        if(!$before_2) {$before_2 = $rec3; $removed = array();}
                        
                        if(count($options) == 0) $options = $rec3; //exit("\nnaku zero 1\n");
                        if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        else {
                            $i = -1;
                            $removed = array();
                            foreach($options as $taxonID) { $i++;
                                $authorship = self::get_correct_authorship($taxonID, $taxID_info2);
                                if(!$authorship) $removed[] = $taxonID;
                                // reject has no authorship data
                            }
                        }

                        $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                        if($GLOBALS['ENV_DEBUG']) {echo "2-"; print_r($options);}

                        $before_3 = $options;
                        if(!$before_3) $before_3 = $before_2;
                        if(!$before_3) {$before_3 = $rec3; $removed = array();}
                        
                        if(count($options) == 0) $options = $before_2; //exit("\nnaku zero 2\n");
                        if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        else {
                            $i = -1;
                            $removed = array();
                            foreach($options as $taxonID) { $i++;
                                /* first implementation - not enough e.g. "Halofolliculina annulata (Andrews, 1944) Hadzi, 1951"
                                $int = (int) filter_var($taxID_info2[$taxonID]['sn'], FILTER_SANITIZE_NUMBER_INT);
                                echo "\nint is [$int]\n";
                                if(strlen((string) $int) != 4) $removed[] = $taxonID;
                                */
                                
                                $without_4digit_no = true;
                                $authorship = self::get_correct_authorship($taxonID, $taxID_info2);
                                if(preg_match_all('!\d+!', $authorship, $matches)) {
                                    foreach($matches[0] as $num) {
                                        if(strlen((string) $num) == 4) $without_4digit_no = false;
                                    }
                                }
                                if($without_4digit_no) $removed[] = $taxonID;
                                // authorship data (=scientificName - canonical name) WITH 4-digit number | authorship data WITHOUT 4-digit number
                            }
                        }
                        
                        $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                        if($GLOBALS['ENV_DEBUG']) {echo "3-"; print_r($options);}

                        $before_4 = $options;
                        if(!$before_4) $before_4 = $before_3;
                        if(!$before_4) $before_4 = $before_2;
                        if(!$before_4) {$before_4 = $rec3; $removed = array();}

                        if(count($options) == 0) $options = $before_3; //exit("\nnaku zero 3\n");
                        if(count($options) == 0) exit("\nnaku zero 3 pa rin\n");
                        if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        else {
                            if(count($options) == 2) {
                                $authorship1 = self::get_correct_authorship($options[0], $taxID_info2);
                                $authorship2 = self::get_correct_authorship($options[1], $taxID_info2);
                                $int1 = (int) filter_var($authorship1, FILTER_SANITIZE_NUMBER_INT);
                                $int2 = (int) filter_var($authorship2, FILTER_SANITIZE_NUMBER_INT);
                                if($int2 > $int1) $removed[] = $options[1];
                                if($int1 > $int2) $removed[] = $options[0];
                            }
                            elseif(count($options) >= 3) {
                                $int = array();
                                $i = -1;
                                $cont = true;
                                $removed = array();
                                foreach($options as $taxonID) { $i++;
                                    $authorship = self::get_correct_authorship($taxonID, $taxID_info2);
                                    $int[$i] = (int) filter_var($authorship, FILTER_SANITIZE_NUMBER_INT);
                                    if(strlen((string) $int[$i]) != 4) $cont = false;
                                }
                                if($cont) {
                                    print_r($int); asort($int); print_r($int);
                                    $index = array_keys($int);
                                    print_r($index);
                                    for ($x = 1; $x <= count($options)-1; $x++) $removed[] = $options[$index[$x]];
                                    print_r($options); //exit("\ndebug muna\n");
                                }
                            }
                            else exit("\nComparing more than 3 numbers. No script for this yet.\n"); //should not go here
                            // authority date (4-digit number in authorship data) is smaller | authority date is larger

                            // Antipathes irregularis (Thomson & Simpson, 1905)
                            // Antipathes irregularis Cooper, 1909
                            // Antipathes irregularis Verrill, 1928
                            //     [0] => 283907
                            //     [1] => 283908
                            //     [2] => 411112
                            
                            // Array
                            // (
                            //     [0] => 1402393   Fenestella gracilis Condra, 1902                -> should be removed in final DwCA
                            //     [1] => 1402394   Fenestella gracilis Dana, 1849                  -> only one to remain OK
                            //     [2] => 1402395   Fenestella gracilis Nekhoroshev, 1932           -> should be removed in final DwCA
                            //     [3] => 1411188   Fenestella gracilis (Barrande in Pocta, 1894)   -> should be removed in final DwCA
                            // )
                        }

                        $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                        if($GLOBALS['ENV_DEBUG']) {echo "4-"; print_r($options);}

                        $before_5 = $options;
                        if(!$before_5) $before_5 = $before_4;
                        if(!$before_5) $before_5 = $before_3;
                        if(!$before_5) $before_5 = $before_2;
                        if(!$before_5) {$before_5 = $rec3; $removed = array();}

                        if(count($options) == 0) $options = $before_4; //exit("\nnaku zero 4\n");
                        if(count($options) == 0) exit("\nnaku zero 4 pa rin\n");
                        if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        else {
                            // if(in_array(798813, $options)) echo "\nMonitoring 111\n";
                            $i = -1;
                            $possible_bring_back = array();
                            $removed = array();
                            foreach($options as $taxonID) { $i++;
                                $authorship = self::get_correct_authorship($taxonID, $taxID_info2);
                                // if(in_array(798813, $options)) echo "\nauthorship: [$authorship]\n";
                                if(preg_match("/\((.*?)\)/ims", $authorship, $arr)) {
                                    // if(in_array(798813, $options)) echo " $taxonID - with parenthesis";
                                }
                                else {
                                    // if(in_array(798813, $options)) echo " $taxonID - without parenthesis";
                                    $removed[] = $taxonID;
                                    $possible_bring_back[] = $taxonID;
                                }
                                // reject without parenthesis
                            }
                            // if(in_array(798813, $options)) echo "\nMonitoring 222\n";
                            // if(in_array(798813, $options)) {
                            //     echo "\noptions: "; print_r($options); echo "\nremoved: "; print_r($removed); //exit("\nend monitor\n");
                            // }
                        }

                        $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                        if($GLOBALS['ENV_DEBUG']) {echo "5-"; print_r($options);}
                        
                        /* copied block
                        if(count($options) == 0) { //just pick 1
                            $removed = array_diff($removed, $possible_bring_back);
                            if(count($possible_bring_back) == 2) $removed[] = $possible_bring_back[1];
                            elseif(count($possible_bring_back) == 3) {
                                $removed[] = $possible_bring_back[1];
                                $removed[] = $possible_bring_back[2];
                            }
                            else exit("\nA case with 4 duplicates!\n");
                            $options = $possible_bring_back;
                            $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                            if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        }
                        */
                        
                        $before_6 = $options;
                        if(!$before_6) $before_6 = $before_5;
                        if(!$before_6) $before_6 = $before_4;
                        if(!$before_6) $before_6 = $before_3;
                        if(!$before_6) $before_6 = $before_2;
                        if(!$before_6) {$before_6 = $rec3; $removed = array();}
                        
                        if(count($options) == 0) $options = $before_5; //exit("\nnaku zero 5\n");
                        if(count($options) == 0) exit("\nnaku zero 5 pa rin\n");
                        if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        else {
                            $i = -1;
                            $possible_bring_back = array();
                            if($GLOBALS['ENV_DEBUG']) {echo "5a-"; print_r($options);}
                            $removed = array();
                            foreach($options as $taxonID) { $i++;
                                if(in_array(286731, $rec3)) echo "\n taxonID xxx [$taxonID]";
                                $arr = self::call_gnparser($taxID_info2[$taxonID]['sn']);
                                if($subgenus = @$arr[0]['details'][0]['infragenericEpithet']['value']) {
                                    $removed[] = $taxonID;
                                    $possible_bring_back[] = $taxonID;
                                    if(in_array(286731, $rec3)) echo " - with subgenus [$subgenus]";
                                }
                                else {
                                    if(in_array(286731, $rec3)) echo " - without subgenus [$subgenus]";
                                }
                                // reject with subgenus
                            }
                        }
                        if(in_array(286731, $rec3)) {
                            print_r($options); print_r($removed); //exit("\n-end muna-\n");
                        }

                        $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                        if($GLOBALS['ENV_DEBUG']) {echo "6-"; print_r($options);}

                        /* copied block
                        if(count($options) == 0) { //just pick 1
                            $removed = array_diff($removed, $possible_bring_back);
                            if(count($possible_bring_back) == 2) $removed[] = $possible_bring_back[1];
                            elseif(count($possible_bring_back) == 3) {
                                $removed[] = $possible_bring_back[1];
                                $removed[] = $possible_bring_back[2];
                            }
                            else exit("\nA case with 4 duplicates!\n");
                            $options = $possible_bring_back;
                            $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                            if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        }
                        */

                        $before_7 = $options;
                        if(!$before_7) $before_7 = $before_6;
                        if(!$before_7) $before_7 = $before_5;
                        if(!$before_7) $before_7 = $before_4;
                        if(!$before_7) $before_7 = $before_3;
                        if(!$before_7) $before_7 = $before_2;
                        if(!$before_7) {$before_7 = $rec3; $removed = array();}

                        if(count($options) == 0) $options = $before_6; //exit("\nnaku zero 6\n");
                        if(count($options) == 0) exit("\nnaku zero 6 pa rin\n");
                        if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        else {
                            $i = -1;
                            $removed = array();
                            foreach($options as $taxonID) { $i++;
                                if($taxID_info[$taxonID]['r'] == 'variety') $removed[] = $taxonID;
                                // taxonRank is subspecies | taxonRank is variety
                            }
                        }

                        $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                        if($GLOBALS['ENV_DEBUG']) {echo "7-"; print_r($options);}

                        $before_8 = $options;
                        if(!$before_8) $before_8 = $before_7;
                        if(!$before_8) $before_8 = $before_6;
                        if(!$before_8) $before_8 = $before_5;
                        if(!$before_8) $before_8 = $before_4;
                        if(!$before_8) $before_8 = $before_3;
                        if(!$before_8) $before_8 = $before_2;
                        if(!$before_8) {$before_8 = $rec3; $removed = array();}
                        if(!$before_8) {
                            echo "\nrec3 is: "; print_r($rec3);
                            exit("\nCannot go here...\n");
                        }
                        

                        if(count($options) == 0) $options = $before_7; //exit("\nnaku zero 7\n");
                        if(count($options) == 0) exit("\nnaku zero 7 pa rin\n");
                        if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        else {
                            $i = -1;
                            foreach($options as $taxonID) { $i++;
                                if($taxID_info[$taxonID]['r'] == 'form') $removed[] = $taxonID;
                                // taxonRank is variety | taxonRank is form */
                            }
                        }

                        $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                        if($GLOBALS['ENV_DEBUG']) {echo "8-"; print_r($options);}

                        $before_9 = $options;

                        if(count($options) == 0) $options = $before_8; //exit("\nnaku zero 8a\n");
                        if(count($options) == 0) exit("\nnaku zero 8a pa rin\n");
                        if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                        else {
                            // exit("\nneed to add more filter A\n");
                            echo "\nJUST PICK ONE, at this point...\n";
                            $removed = array();
                            print_r($options);
                            for ($x = 1; $x <= count($options)-1; $x++) $removed[] = $options[$x];
                            echo "\nremoved: "; print_r($removed);
                            $options = array_diff($options, $removed); $options = array_values($options); //reindex key
                            if($GLOBALS['ENV_DEBUG']) {echo "9-"; print_r($options);}

                            if(count($options) == 0) exit("\nnaku zero 8b\n");
                            if(count($options) == 1) {self::write_report($rec3, $options, $taxID_info2, $taxID_info); continue;}
                            else exit("\nneed to add more filter B\n");
                            @$this->debug['count need more filter']++;
                        }
                        
                        // if(count($rec3) > 2) print_r($rec3); //debug only
                        /*For each set of duplicates remove duplicate taxa until only a single taxon is left applying the following criteria in sequence:
                        Prefer | Reject
                        *accepted | doubtful
                        *scientificName has authorship data (i.e., is not the same as the canonical name) | scientificName has no authorship data
                        *authorship data (=scientificName - canonical name) WITH 4-digit number | authorship data WITHOUT 4-digit number
                        *authority date (4-digit number in authorship data) is smaller | authority date is larger
                        *authorship data WITH parentheses | authorship data WITHOUT parentheses
                        *scientificName does not include subgenus (a capitalized name in parentheses after the genus name) | scientificName includes subgenus
                        *taxonRank is subspecies | taxonRank is variety
                        *taxonRank is variety | taxonRank is form */
                    }
                }
            }
        }
        echo "\nDuplicates: [$final_duplicates]\n";
        foreach($this->removed as $taxon_id) $final[$taxon_id] = '';
        return $final;
    }
    public function get_correct_authorship($taxonID, $taxID_info2)
    {
        $canonical = $taxID_info2[$taxonID]['cn'];
        $sciname = $taxID_info2[$taxonID]['sn'];
        if($canonical) {
            $authorship = trim(str_replace($canonical, "", $sciname));
            if($authorship == $sciname) { //echo "\nmay problema\n";
                $arr = self::call_gnparser($sciname);
                if($val = @$arr[0]['authorship']) return $val;
                else {} //exit("\n[$sciname] no authorship detected by gnparser\n");
            }
            else return $authorship; //result of sciname - canonical = authorship
        }
        else { //no canonical
            $arr = self::call_gnparser($sciname);
            if($val = @$arr[0]['authorship']) return $val;
        }
        return ""; //$sciname;
    }
    private function write_report($rec3, $options, $taxID_info2, $taxID_info)
    {   /* just for guide on how to use the info list
        $taxID_info2[taxonID] = array("sn" => $rec['scientificName'], 'cn' => $rec['vernacularName']);
        $taxID_info[taxonID] = array("pID" => $rec['parentNameUsageID'], 'r' => $rec['taxonRank'], 's' => $rec['taxonomicStatus'])
        */
        $WRITE = fopen($this->WoRMS_report, "a");
        $remain = $options[0];
        foreach($rec3 as $taxonID) {
            $status = '';
            if($taxonID != $remain) $status = "removed";
            $arr = array($taxonID, $taxID_info2[$taxonID]['sn'], $taxID_info[$taxonID]['r'], $taxID_info[$taxonID]['s'], $status);
            fwrite($WRITE, implode("\t", $arr) . "\n");
        }
        fwrite($WRITE, "\n");
        fclose($WRITE);
    }
    function canonical_form_gnparser($sciname)
    {
        $arr = self::call_gnparser($sciname);
        return trim(@$arr[0]['canonicalName']['full']);
    }
    function call_gnparser($sciname)
    {   //source https://parser.globalnames.org/doc/api
        $sciname = str_replace(" ", "+", $sciname);
        $sciname = str_replace("&", "%26", $sciname);
        $url = $this->gnparser_api.$sciname;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $arr = json_decode($json, true); // print_r($arr); exit;
            return $arr;
        }
    }
    private function flag_duplicates($rec, $taxID_info)
    {   /*There are going to be a bunch of duplicate taxa in these branches that we don't want for the DH. 
        I suggest that we deal with them as described below. 
        We only care about duplicate taxa with taxonomicStatus accepted or doubtful here, not about synonyms (unaccepted).

        We'll only want to deduplicate taxa with the following ranks:
        species subspecies variety form */
        $taxonomicStatus = $rec['taxonomicStatus'];
        $taxonRank = $rec['taxonRank'];
        
        if($parentNameUsageID = $rec['parentNameUsageID']) {
            if($parent_name = @$this->taxonID_scientificName_info[$parentNameUsageID]) {
                if($parent_name_canonical = @$this->scientificName_canonical_info[$parent_name]) {}
                else exit("\nundefined parent name: [$parent_name]\n");
            }
            else exit("\nundefined parent id: [$parentNameUsageID]\n");
        }
        else { //mostly if not all, these are roots that's why there are no parents. Given list of roots from the ticket.
            // echo "\nNO parent ID: [$parentNameUsageID] [$rec[scientificName]] [$rec[vernacularName]]\n"; //good debug only
            // print_r($rec); exit;
            $parent_name = '';
            $parent_name_canonical = '';
        }
        
        /* THIS IS ELI'S STRATEGY, IS NOW COMMENTED. WILL USE KATJA'S PATH.
        // adjustment for e.g. "Abyssocypris subgen. Abyssocypris" should be compared as "Abyssocypris" only. 
        // That is remove everything after "subgen.", inclusive.
        // https://eol-jira.bibalex.org/browse/TRAM-988?focusedCommentId=65174&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65174
        if(stripos($parent_name_canonical, " subgen. ") !== false) { //string is found
            $tmp = explode(" subgen. ", $parent_name_canonical);
            $parent_name_canonical = trim($tmp[0]);
        }
        */
        
        // /* KATJA'S PATH - https://eol-jira.bibalex.org/browse/TRAM-988?focusedCommentId=65179&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65179
        if($parentNameUsageID = $rec['parentNameUsageID']) {
            $parentID = self::get_parentID_for_comparison($rec['taxonID'], $taxID_info); //returns either the parent or grandparent
            if($parent_name = @$this->taxonID_scientificName_info[$parentID]) {
                if($parent_name_canonical = @$this->scientificName_canonical_info[$parent_name]) {}
                else exit("\nundefined parent name2: [$parent_name]\n");
            }
            else exit("\nundefined parent id2: [$parentID]\n");
        }
        // */
        
        $genus = $rec['genus'];
        $genus_canonical = Functions::canonical_form($genus);
        if(self::number_of_words($genus_canonical) != 1) $genus_canonical = self::canonical_form_gnparser($genus);
        $scientificName = $rec['scientificName'];
        $canonical = $rec['vernacularName']; //canonical name from gnparser is stored here in vernacularName
        /* debug only
        if(in_array($rec['taxonID'], array(156099, 132874))) $this->eli[$canonical][$scientificName]["$genus_canonical|$parent_name_canonical"] = '';
        */
        if(in_array($taxonomicStatus, array('accepted', 'doubtful'))) {
            if(in_array($taxonRank, array('species', 'subspecies', 'variety', 'form'))) {
                if($taxonRank == 'species') $this->duplicates[$canonical]["$genus_canonical|$parent_name_canonical"][$taxonRank][] = $rec['taxonID'];
                else                        $this->duplicates[$canonical]["$genus_canonical|$parent_name_canonical"]['SVF'][] = $rec['taxonID'];
            }
        }
        /*A set of duplicates is where:
        The values of the parentNameUsageID AND genus columns are the same
        AND the canonical name (from gnparser) is the same

        Taxa of rank species should only have duplicates that are also of rank species, because their canonical names should be binomials. 
        Taxa of rank subspecies, form or variety should only have duplicates that are also of one of these ranks, 
        because their canonical names should be trinomials.
        */
        /*Array(
            [taxonID] => 1457230
            [furtherInformationURL] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=1457230
            [referenceID] => WoRMS:citation:1457230
            [acceptedNameUsageID] => 517638
            [parentNameUsageID] => 
            [scientificName] => Bebryce verrucosa Stiasny, 1942
            [namePublishedIn] => Stiasny, G. (1942). Ergebnisse der Nachuntersuchung der Muriceidae (Gorgonaria) der Siboga-Expedition. <em>Zoologischer Anzeiger.</em> 140 (9/10), 192–199.
            [kingdom] => Animalia
            [phylum] => Cnidaria
            [class] => Anthozoa
            [order] => Alcyonacea
            [family] => Plexauridae
            [genus] => Bebryce
            [taxonRank] => species
            [vernacularName] => Bebryce verrucosa
            [taxonomicStatus] => unaccepted
            [taxonRemarks] => 
            [rightsHolder] => 
            [accessRights] => 
            [datasetName] => 
        )*/
    }
    public function get_parentID_for_comparison($id, $taxID_info)
    {
        // $id = 156099; $id = 132874;
        $ancestry = self::get_ancestry_of_taxID($id, $taxID_info);
        // echo "\nancestry of [$id]\n"; print_r($ancestry);
        $parent = @$ancestry[1];
        $grandparent = @$ancestry[2]; 
        $parent_rec = $taxID_info[$parent];
        // echo "\nparent rec: "; print_r($parent_rec);
        if($parent_rec['r'] == 'subgenus') { //echo "\nuse grandparent for comparison\n";
            return $grandparent;
            // $grandparent_rec = $taxID_info[$grandparent];
            // echo "\ngrandparent rec: "; print_r($grandparent_rec);
        }
        else { //echo "\nuse parent for comparison\n";
            return $parent;
        }
        // ancestry of [156099]
        // Array(
        //     [0] => 156099 - given
        //     [1] => 131922 - parent
        //     [2] => 131834 - grandparent
        //     [3] => 131636
        //     [4] => 131598
        //     [5] => 607950
        //     [6] => 164811
        //     [7] => 558
        // )
    }
    private function number_of_words($str)
    {
        $str = trim($str);
        $arr = explode(" ", $str);
        return count($arr);
    }
    private function format_ids($rec)
    {
        $this->debug['taxonomicStatus values'][$rec['taxonomicStatus']] = '';
        
        $fields = array("taxonID", "parentNameUsageID", "acceptedNameUsageID");
        foreach($fields as $fld) $rec[$fld] = str_ireplace("urn:lsid:marinespecies.org:taxname:", "", $rec[$fld]);

        if($rec['taxonID'] == $rec['acceptedNameUsageID']) $rec['acceptedNameUsageID'] = ''; //OK, valid adjustment
        if($rec['taxonID'] == $rec['parentNameUsageID']) $rec['parentNameUsageID'] = ''; //just put it here, but may not be needed
        
        //root nodes in the includes should not have parents
        if(isset($this->include[$rec['taxonID']])) $rec['parentNameUsageID'] = ''; //OK

        /*
        [taxonID] => 163137
        [scientificName] => Chaetoceros throndsenii (Marino, Montresor & Zingone) Marino, Montresor & Zingone, 1991
        [parentNameUsageID] => 148985
        [taxonRank] => species
        [taxonomicStatus] => accepted
        [taxonRemarks] => [REMAP_ON_EOL]
        [acceptedNameUsageID] => 163143
        */
        if(stripos($rec['taxonRemarks'], "REMAP_ON_EOL") !== false) { //string is found
            if($rec['taxonID'] != $rec['acceptedNameUsageID'] && $rec['acceptedNameUsageID']) {
                $rec['taxonomicStatus'] = 'synonym';
                $rec['parentNameUsageID'] = ''; //will investigate if won't mess things up -> this actually lessens the no. of taxa
            }
        }

        /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. 
        In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. */
        if($rec['taxonomicStatus'] && $rec['taxonomicStatus'] != 'accepted' && $rec['acceptedNameUsageID']) $rec['parentNameUsageID'] = ''; //newly added
        
        // if(in_array($rec['taxonID'], array(700052,146143,1026180,681756,100983,427861))) {
        // if(in_array($rec['taxonID'], array(169693, 170666, 208739, 216130, 233336, 251753))) {
        //     print_r($rec); //exit;
        // }

        return $rec;
    }
    private function status_from_api($taxon_id)
    {
        if($json = Functions::lookup_with_cache($this->webservice['AphiaRecordByAphiaID'].$taxon_id, $this->download_options)) {
            $arr = json_decode($json, true);
            // print_r($arr);
            return $arr['status'];
        }
    }
    private function replace_NotAssigned_name($rec)
    {   /*42981143 -- Not assigned -- order
        We would want to change the scientificName value to “Order not assigned” */
        $sciname = $rec['scientificName'];
        if($rank = $rec['taxonRank']) $sciname = ucfirst(strtolower($rank))." not assigned";
        return $sciname;
    }
    private function get_taxID_nodes_info()
    {
        echo "\nGenerating taxID_info..."; $final = array(); $i = 0;
        $meta = self::get_meta_info();
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 500000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            $rec = self::format_ids($rec);
            
            // print_r($rec); exit;
            $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'r' => $rec['taxonRank'], 's' => $rec['taxonomicStatus']);
            
            // /* for TRAM-988
            $this->taxonID_scientificName_info[$rec['taxonID']] = $rec['scientificName']; //to be used below
            $this->scientificName_canonical_info[$rec['scientificName']] = $rec['vernacularName']; //to be used below
            // */
            
            /* debug
            if($rec['taxonID'] == "42987761") {
                print_r($rec); exit;
            }
            */
        }
        return $final;
    }
    private function get_ancestry_of_taxID($tax_id, $taxID_info)
    {   /* Array(
            [1] => Array(
                    [pID] => 123
                    [r] => species
                    [s] => accepted
                )
        )*/
        $final = array();
        $final[] = $tax_id;
        while($parent_id = @$taxID_info[$tax_id]['pID']) {
            if(!in_array($parent_id, $final)) $final[] = $parent_id;
            else {
                if($parent_id == 1) return $final;
                else {
                    print_r($final);
                    exit("\nInvestigate $parent_id already in array.\n");
                }
            }
            $tax_id = $parent_id;
        }
        return $final;
    }
    private function write_taxon_DH($rec)
    {   //from NCBI ticket: a general rule
        /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. */
        // if($rec['acceptedNameUsageID']) $rec['parentNameUsageID'] = ''; moved....
        
        // if($rec['scientificName'] == "Not assigned") $rec['scientificName'] = self::replace_NotAssigned_name($rec); not instructed to use in this resource
        
        
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxonID'];
        $taxon->parentNameUsageID       = $rec['parentNameUsageID'];
        $taxon->taxonRank               = $rec['taxonRank'];
        
        /* seize to use per Katja: https://eol-jira.bibalex.org/browse/TRAM-805?focusedCommentId=63337&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63337
        $rec['scientificName'] = self::format_incertae_sedis($rec['scientificName']);
        */
        
        $taxon->scientificName          = $rec['scientificName'];
        $taxon->taxonomicStatus         = $rec['taxonomicStatus'];
        $taxon->acceptedNameUsageID     = $rec['acceptedNameUsageID'];
        $taxon->furtherInformationURL   = $rec['furtherInformationURL'];
        $taxon->taxonRemarks            = $rec['taxonRemarks'];
        
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)
    {
        foreach($ancestry as $id) {
            /* use isset() instead
            if(in_array($id, $removed_branches)) return true;
            */
            if(isset($removed_branches[$id])) return true;
        }
        return false;
    }
    private function get_removed_branches_from_spreadsheet($params = false)
    {
        $final = array(); $final2 = array();
        if(!$params) {
            $params['spreadsheetID'] = '1c44ymPowJA2V3NdDNBiqNjvQ2PdCJ4Zgsa34KJmkbVA';
            $params['range']         = 'Sheet1!A2:B6264'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        }
        $rows = Functions::get_google_sheet_using_GoogleClientAPI($params);
        //start massage array
        foreach($rows as $item) {
            if($val = $item[0]) $final[$val] = '';
            $canonical = trim(Functions::canonical_form($item[1]));
            if(stripos($canonical, " ") !== false) continue; //string is found
            else {
                if($canonical) $final2[$canonical] = '';
            }
        }
        // print_r($final2); exit;
        return array('removed_brances' => $final, 'one_word_names' => $final2);
        /* if google spreadsheet suddenly becomes offline, use this: Array() */
    }
    private function more_ids_to_remove()
    {
        $a = array();
        $b = array();
        $c = array_merge($a, $b);
        return array_unique($c);
    }
    private function get_meta_info($row_type = false)
    {
        require_library('connectors/DHSourceHierarchiesAPI'); $func = new DHSourceHierarchiesAPI();
        $meta = $func->analyze_eol_meta_xml($this->extension_path."meta.xml", $row_type); //2nd param $row_type is rowType in meta.xml
        if($GLOBALS['ENV_DEBUG']) print_r($meta);
        return $meta;
    }
    private function format_incertae_sedis($str)
    {
        /*
        case 1: [One-word-name] incertae sedis
            Example: Bivalvia incertae sedis
            To: unplaced [One-word-name]
        
        case 2: [One-word-name] incertae sedis [other words]
        Example: Lyssacinosida incertae sedis Tabachnick, 2002
        To: unplaced [One-word-name]

        case 3: [more than 1 word-name] incertae sedis
        :: leave it alone for now
        Examples: Ascorhynchoidea family incertae sedis
        */
        $str = Functions::remove_whitespace($str);
        $str = trim($str);
        if(is_numeric(stripos($str, " incertae sedis"))) {
            $str = str_ireplace("incertae sedis", "incertae sedis", $str); //this will capture Incertae sedis
            $arr = explode(" incertae sedis", $str);
            if($val = @$arr[0]) {
                $space_count = substr_count($val, " ");
                if($space_count == 0) return "unplaced " . trim($val);
                else return $str;
            }
        }
        else return $str;
    }
    // ----------------------------------------------------------------- end TRAM-797 -----------------------------------------------------------------
    /*
    private function get_tax_ids_from_taxon_tab_working()
    {
        echo "\n get taxonIDs from taxon_working.tab\n";
        require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();
        $url = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id."_working" . "/taxon_working.tab";
        $suggested_fields = explode("\t", "taxonID	furtherInformationURL	referenceID	acceptedNameUsageID	parentNameUsageID	scientificName	taxonRank	taxonomicStatus"); //taxonID is what is important here.
        $var = $func->get_fields_from_tab_file($this->resource_id, array("taxonID"), $url, $suggested_fields, false); //since there is $url, the last/5th param is no longer needed, set to false.
        return $var['taxonID'];
    }
    */
}
?>