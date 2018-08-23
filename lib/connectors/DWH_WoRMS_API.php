<?php
namespace php_active_record;
/* connector: [dwh_worms_TRAM_798.php] - https://eol-jira.bibalex.org/browse/TRAM-798 
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
        
        if(Functions::is_production())  $this->dwca_file = "http://www.marinespecies.org/export/eol/WoRMS2EoL.zip";
        else                            $this->dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
        
        $this->webservice['AphiaRecordByAphiaID'] = "http://www.marinespecies.org/rest/AphiaRecordByAphiaID/";
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'resource_id' => 26);
        $this->download_options["expire_seconds"] = false; //debug - false means it will use cache
        
    }
    // ----------------------------------------------------------------- start TRAM-797 -----------------------------------------------------------------
    private function start()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "taxon.txt", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $tables['taxa'] = 'taxon.txt';
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
            $info = Array('temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_26984/',
                          'tables' => Array('taxa' => "taxon.txt"));
            $this->extension_path = $info['temp_dir'];
            self::main_WoRMS();
            $this->archive_builder->finalize(TRUE);
            if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
            // remove temp dir
            // recursive_rmdir($info['temp_dir']);
            // echo ("\n temporary directory removed: " . $info['temp_dir']);
        }
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

        /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. */
        if($rec['taxonomicStatus'] && $rec['taxonomicStatus'] != 'accepted' && $rec['acceptedNameUsageID']) $rec['parentNameUsageID'] = ''; //newly added
        
        // if(in_array($rec['taxonID'], array(700052,146143,1026180,681756,100983,427861))) {
        // if(in_array($rec['taxonID'], array(169693, 170666, 208739, 216130, 233336, 251753))) {
        //     print_r($rec); //exit;
        // }

        return $rec;
    }
    private function main_WoRMS()
    {
        $include['123081'] = "Crinoidea"; 
        $include['6'] = "Bacteria"; 
        $include['599656'] = "Glaucophyta"; 
        $include['852'] = "Rhodophyta"; 
        $include['17638'] = "Cryptophyta"; 
        $include['369190'] = "Haptophyta"; 
        $include['341275'] = "Centrohelida"; 
        $include['536209'] = "Alveolata"; 
        $include['368898'] = "Heterokonta"; 
        $include['582420'] = "Rhizaria"; 
        $include['582161'] = "Euglenozoa"; 
        $include['582180'] = "Malawimonadea"; 
        $include['582179'] = "Jakobea"; 
        $include['582221'] = "Oxymonadida"; 
        $include['582175'] = "Parabasalia"; 
        $include['562616'] = "Hexamitidae"; 
        $include['562613'] = "Enteromonadidae"; 
        $include['451649'] = "Heterolobosea"; 
        $include['582189'] = "Discosea"; 
        $include['582188'] = "Tubulinea"; 
        $include['103424'] = "Apusomonadidae"; 
        $include['707610'] = "Rozellidae"; 
        $include['582263'] = "Nucleariida"; 
        $include['582261'] = "Ministeriida"; 
        $include['580116'] = "Choanoflagellatea"; 
        $include['391862'] = "Ichthyosporea";
        $this->include = $include;
        
        $removed_branches = array();
        // /* un-comment in real operation
        $params['spreadsheetID'] = '11jQ-6CUJIbZiNwZrHqhR_4rqw10mamdA17iaNELWCBQ';
        $params['range']         = 'Sheet1!A2:B2000'; //actual range is up to B1030 only as of Aug 23, 2018. I set B2000 to put allowance for possible increase.
        $parts = self::get_removed_branches_from_spreadsheet($params);
        $removed_branches = $parts['removed_brances'];
        // $one_word_names = $parts['one_word_names']; may not be needed anymore...
        echo "\nremoved_branches total: ".count($removed_branches)."\n";
        // */
        
        /*
        //IDs from WoRMS_DH_undefined_acceptedName_ids.txt
        $ids_2remove = array(146143, 179477, 179847, 103815, 143816, 851581, 427887, 559169, 1026180, 744813, 115400, 176036, 603470, 744962, 744966, 744967, 135564, 100983, 427861, 864183, 427860, 1005667);
        foreach($ids_2remove as $id) $removed_branches[$id] = '';
        */
        
        $taxID_info = self::get_taxID_nodes_info();
        echo "\ntaxID_info (taxons.txt) total rows: ".count($taxID_info)."\n";

        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...Col WoRMS...\n";
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
                [taxonID] => 1
                [scientificName] => Biota
                [parentNameUsageID] => 
                [kingdom] => 
                [phylum] => 
                [class] => 
                [order] => 
                [family] => 
                [genus] => 
                [taxonRank] => kingdom
                [furtherInformationURL] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=1
                [taxonomicStatus] => accepted
                [taxonRemarks] => 
                [namePublishedIn] => 
                [referenceID] => WoRMS:citation:1
                [acceptedNameUsageID] => 1
                [rights] => 
                [rightsHolder] => 
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
        } //end loop
        echo "\ntotal ids: ".count($inclusive_taxon_ids)."\n";
        
        
        //start 2nd loop
        $i = 0; echo "\nStart main process 2...CoL WoRMS...\n";
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
                
                if($rec['taxonomicStatus'] == 'accepted' && $rec['acceptedNameUsageID']) {
                    echo "\ngoes here...\n";
                    if($accepted_rek = @$taxID_info[$rec['acceptedNameUsageID']]) {
                        if($accepted_rek['s'] == 'accepted') { //it didn't go here anyway...
                            $status_from_api = self::status_from_api($rec['taxonID']);
                            if($status_from_api != 'accepted') {
                                $rec['taxonomicStatus'] = $status_from_api;
                                $rec['parentNameUsageID'] = '';
                            }
                            else $this->debug['accepted but with acceptedNameUsageID'][$rec['taxonID']] = '';
                        }
                    }
                }
                
                self::write_taxon_DH($rec);
            }
        }
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
        
        $rec['scientificName'] = self::format_incertae_sedis($rec['scientificName']);
        
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