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
        // if(!($info = self::start())) return; //uncomment in real operation
        
        // /* development only
        $info = Array('temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_26984/',
                      'tables' => Array('taxa' => "taxon.txt"));
        // */
        print_r($info);
        $this->extension_path = $info['temp_dir'];
        
        // exit("\nstopx\n");
        
        self::main_WoRMS();
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
        
        // remove temp dir
        // recursive_rmdir($info['temp_dir']);
        // echo ("\n temporary directory removed: " . $info['temp_dir']);
    }
    private function main_WoRMS()
    {
        /* un-comment in real operation
        $params['spreadsheetID'] = '11jQ-6CUJIbZiNwZrHqhR_4rqw10mamdA17iaNELWCBQ';
        $params['range']         = 'Sheet1!A2:B1030'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $parts = self::get_removed_branches_from_spreadsheet($params);
        $removed_branches = $parts['removed_brances'];
        // $one_word_names = $parts['one_word_names'];
        print_r($removed_branches);
        echo "\nremoved_branches total: ".count($removed_branches)."\n";
        // echo "\none_word_names total: ".count($one_word_names)."\n";
        // exit("\n-end-\n");
        */
        
        $taxID_info = self::get_taxID_nodes_info();
        echo "\ntaxID_info total: ".count($taxID_info)."\n";
        
        exit("\n-end-\n");

        $include[42984770] = "Ciliophora";
        $include[42990646] = "Oomycota";
        $include[42981251] = "Polycystina";
        $include[42985937] = "Eccrinida";
        $include[42985691] = "Microsporidia";
        $include[42983291] = "Mycetozoa";
        $include[42993626] = "Chaetocerotaceae";
        $include[42993677] = "Naviculaceae";
        $this->include = $include;
        
        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...Col Protists...\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 500000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit;
            /* good debug
            if(isset($include[$rec['taxonID']])) print_r($rec);
            */
            /*Array(
                [taxonID] => 10145857
                [scientificNameID] => Cil-CILI00024223
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 42998474
                [scientificName] => Amphileptus hirsutus Dumas, 1930
                [kingdom] => Chromista
                [phylum] => Ciliophora
                [class] => Gymnostomatea
                [order] => Pleurostomatida
                [family] => Amphileptidae
                [genus] => Amphileptus
                [taxonRank] => species
                [scientificNameAuthorship] => Dumas, 1930
                [taxonomicStatus] => accepted name
                [taxonRemarks] => 
            )*/
            $will_cont = false;
            
            /* This is commented because: there is Ciliophora that is genus, must be excluded. The Phylum Ciliophora is the one to be included. So name comparison was commented out.
            $ranks2check = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
            foreach($ranks2check as $rank2check) {
                $sciname = $rec[$rank2check];
                if(in_array($sciname, $include)) $will_cont = true;
            }
            */
            
            $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $include)) $will_cont = true; //this will actually include what is in the branch
            
            //==============================================================================
            if($will_cont) {
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $filtered_ids[$rec['taxonID']] = '';
                    $removed_branches[$rec['taxonID']] = '';
                    continue;
                }
                else $inclusive_taxon_ids[$rec['taxonID']] = '';
            }
            //==============================================================================
        } //end loop
        echo "\ntotal ids: ".count($inclusive_taxon_ids)."\n";
        
        
        //start 2nd loop
        $i = 0; echo "\nStart main process 2...CoL Protists...\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 500000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            if(isset($inclusive_taxon_ids[$rec['taxonID']])) {
                if(isset($filtered_ids[$rec['taxonID']])) continue;
                if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
                if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;
                if(isset($removed_branches[$rec['taxonID']])) continue;
                if(isset($removed_branches[$rec['acceptedNameUsageID']])) continue;
                if(isset($removed_branches[$rec['parentNameUsageID']])) continue;
                
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    continue;
                }
                self::write_taxon_DH($rec);
            }
        }
    }
    function start_tram_797(){}
    private function main_tram_797(){}
    private function replace_NotAssigned_name($rec)
    {   /*42981143 -- Not assigned -- order
        We would want to change the scientificName value to “Order not assigned” */
        $sciname = $rec['scientificName'];
        if($rank = $rec['taxonRank']) $sciname = ucfirst(strtolower($rank))." not assigned";
        return $sciname;
    }
    private function get_taxID_nodes_info()
    {
        echo "\nGenerating taxID_info...";
        $final = array(); $i = 0;
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
            // print_r($rec); exit;
            
            // if($rec['taxonomicStatus'] == "accepted name") 
            $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'r' => $rec['taxonRank']);
            
            // $temp[$rec['taxonomicStatus']] = ''; //debug
            /* debug
            if($rec['taxonID'] == "42987761") {
                print_r($rec); exit;
            }
            */
        }
        // print_r($temp); exit; //debug
        return $final;
    }
    private function get_ancestry_of_taxID($tax_id, $taxID_info)
    {   /* Array(
            [1] => Array(
                    [pID] => 1
                    [r] => no rank
                    [dID] => 8
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
        if($rec['acceptedNameUsageID']) $rec['parentNameUsageID'] = '';
        
        if($rec['scientificName'] == "Not assigned") $rec['scientificName'] = self::replace_NotAssigned_name($rec);
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxonID'];
        $taxon->parentNameUsageID       = $rec['parentNameUsageID'];
        $taxon->taxonRank               = $rec['taxonRank'];
        $taxon->scientificName          = $rec['scientificName'];
        $taxon->taxonomicStatus         = $rec['taxonomicStatus'];
        $taxon->acceptedNameUsageID     = $rec['acceptedNameUsageID'];
        $taxon->furtherInformationURL   = $rec['furtherInformationURL'];
        
        $this->debug['acceptedNameUsageID'][$rec['acceptedNameUsageID']] = '';

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