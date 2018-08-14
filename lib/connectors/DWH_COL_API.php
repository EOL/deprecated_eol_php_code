<?php
namespace php_active_record;
/* connector: [dwh_col_TRAM_797.php] - https://eol-jira.bibalex.org/browse/TRAM-797

*/
class DWH_COL_API
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->download_options = array('resource_id' => $folder, 'download_wait_time' => 1000000, 'timeout' => 60*2, 'download_attempts' => 1, 'cache' => 1); // 'expire_seconds' => 0
        $this->debug = array();
        $this->alternative_names = array("synonym", "equivalent name", "in-part", "misspelling", "genbank synonym", "misnomer", "anamorph", "genbank anamorph", "teleomorph", "authority");

        //start TRAM-797 -----------------------------------------------------------
        $this->prune_further = array();
        $this->extension_path = CONTENT_RESOURCE_LOCAL_PATH . "col/"; //this folder is from DATA-1755
        $this->dwca['iterator_options'] = array('row_terminator' => "\n");
    }
    // ----------------------------------------------------------------- start TRAM-797 -----------------------------------------------------------------
    private function get_meta_info($row_type = false)
    {
        require_library('connectors/DHSourceHierarchiesAPI'); $func = new DHSourceHierarchiesAPI();
        $meta = $func->analyze_eol_meta_xml($this->extension_path."meta.xml", $row_type); //2nd param $row_type is rowType in meta.xml
        print_r($meta);
        return $meta;
    }
    function start_CoLProtists()
    {
        self::main_CoLProtists();
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
    }

    function start_tram_797()
    {
        /* test
        $parts = self::get_removed_branches_from_spreadsheet();
        $removed_branches = $parts['removed_brances'];
        $one_word_names = $parts['one_word_names'];
        print_r($removed_branches);
        print_r($one_word_names);
        exit("\n-end tests-\n");
        */
        /* test
        // 10145857 Amphileptus hirsutus Dumas, 1930
        // 10147309 Aspidisca binucleata Kahl
        $taxID_info = self::get_taxID_nodes_info();
        $ancestry = self::get_ancestry_of_taxID(10145857, $taxID_info); print_r($ancestry);
        $ancestry = self::get_ancestry_of_taxID(10147309, $taxID_info); print_r($ancestry);
        exit("\n-end tests-\n");
        */
        /*
        $taxID_info = self::get_taxID_nodes_info();
        $parts = self::get_removed_branches_from_spreadsheet();
        $removed_branches = $parts['removed_brances'];
        $one_word_names = $parts['one_word_names'];
        $ids = array(42987761,42987788,42987780,42987793,42987792,42987781,42987798,42987775,42987777,40160866,40212453);
        foreach($ids as $id) {
            $ancestry = self::get_ancestry_of_taxID($id, $taxID_info);
            echo "\n ancestry of [$id]:"; print_r($ancestry);
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) echo "\n[$id] removed\n";
            else                                                                                    echo "\n[$id] NOT removed\n";
        }
        exit("\n-end tests-\n");
        */
        
        self::main_tram_797(); //exit("\nstop muna\n");
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
    }
    private function main_tram_797()
    {
        $taxID_info = self::get_taxID_nodes_info(); //exit;
        $parts = self::get_removed_branches_from_spreadsheet();
        $removed_branches = $parts['removed_brances'];
        $one_word_names = $parts['one_word_names'];
        
        echo "\nremoved_branches total: ".count($removed_branches)."\n";
        /* if to add more brances to be removed:
        $removed_branches = array();
        foreach($this->prune_further as $id) $removed_branches[$id] = '';
        $add = self::more_ids_to_remove();
        foreach($add as $id) $removed_branches[$id] = '';
        */
        
        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...\n";
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
            //start filter
            
            // eli added start ----------------------------------------------------------------------------
            $ranks2check = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
            $vcont = true;
            foreach($ranks2check as $rank2check) {
                $sciname = $rec[$rank2check];
                if(isset($one_word_names[$sciname])) {
                    $filtered_ids[$rec['taxonID']] = '';
                    $removed_branches[$rec['taxonID']] = '';
                    $vcont = false;
                    /* debug
                    if($rec['taxonID'] == 42987761) {
                        print_r($rec); exit("\n stopped 100 \n");
                    }
                    */
                }
            }
            if(!$vcont) continue; //next taxon
            // eli added end ----------------------------------------------------------------------------
            
            // if($rec['taxonomicStatus'] == "accepted name") {
                /* Remove branches */
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['taxonID']] = '';
                    $filtered_ids[$rec['taxonID']] = '';
                    $removed_branches[$rec['taxonID']] = '';
                    /* debug
                    if($rec['taxonID'] == 42987761) {
                        print_r($rec); exit("\n stopped 200 \n");
                    }
                    */
                    continue;
                }
            // }
        } //end loop

        echo "\nStart main process 2...\n"; $i = 0;
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
            /*Array(
                [taxonID] => 10145857
                [furtherInformationURL] => http://www.catalogueoflife.org/annual-checklist/2015/details/species/id/ce9e04c173abb9b9bc76357e069c4026
                [scientificNameID] => Cil-CILI00024223
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 42998474
                [scientificName] => Amphileptus hirsutus Dumas, 1930
                [nameAccordingTo] => 
                [kingdom] => Chromista
                [phylum] => Ciliophora
                [class] => Gymnostomatea
                [order] => Pleurostomatida
                [family] => Amphileptidae
                [genus] => Amphileptus
                [subgenus] => 
                [specificEpithet] => hirsutus
                [infraspecificEpithet] => 
                [taxonRank] => species
                [scientificNameAuthorship] => Dumas, 1930
                [taxonomicStatus] => accepted name
                [taxonRemarks] => 
                [modified] => 
                [datasetID] => 113
                [datasetName] => CilCat in Species 2000 & ITIS Catalogue of Life: 28th March 2018
                [referenceID] => 
            )*/
            
            if($rec['scientificName'] == "Not assigned") $rec['scientificName'] = self::replace_NotAssigned_name($rec);
            
            if(isset($filtered_ids[$rec['taxonID']])) continue;
            if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
            if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;

            if(isset($removed_branches[$rec['taxonID']])) continue;
            if(isset($removed_branches[$rec['acceptedNameUsageID']])) continue;
            if(isset($removed_branches[$rec['parentNameUsageID']])) continue;
            
            // if($rec['taxonomicStatus'] == "accepted name") {
                /* Remove branches */
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['taxonID']] = '';
                    /* debug
                    if($rec['taxonID'] == 42987761) {
                        print_r($rec); exit("\n stopped 300 \n");
                    }
                    */
                    continue;
                }
            // }
            self::write_taxon_DH($rec);
        } //end loop
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
    {   //from NCBI ticket:
        /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. */

        if($rec['acceptedNameUsageID']) $rec['parentNameUsageID'] = '';
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxonID'];
        $taxon->parentNameUsageID       = $rec['parentNameUsageID'];
        $taxon->taxonRank               = $rec['taxonRank'];
        $taxon->scientificName          = $rec['scientificName'];
        $taxon->taxonomicStatus         = $rec['taxonomicStatus'];
        $taxon->acceptedNameUsageID     = $rec['acceptedNameUsageID'];
        $taxon->furtherInformationURL   = $rec['furtherInformationURL'];

        /* optional, I guess
        $taxon->scientificNameID    = $rec['scientificNameID'];
        $taxon->nameAccordingTo     = $rec['nameAccordingTo'];
        $taxon->kingdom             = $rec['kingdom'];
        $taxon->phylum              = $rec['phylum'];
        $taxon->class               = $rec['class'];
        $taxon->order               = $rec['order'];
        $taxon->family              = $rec['family'];
        $taxon->genus               = $rec['genus'];
        $taxon->subgenus            = $rec['subgenus'];
        $taxon->specificEpithet     = $rec['specificEpithet'];
        $taxon->infraspecificEpithet        = $rec['infraspecificEpithet'];
        $taxon->scientificNameAuthorship    = $rec['scientificNameAuthorship'];
        $taxon->taxonRemarks        = $rec['taxonRemarks'];
        $taxon->modified            = $rec['modified'];
        $taxon->datasetID           = $rec['datasetID'];
        $taxon->datasetName         = $rec['datasetName'];
        */

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
    private function get_removed_branches_from_spreadsheet()
    {
        $params['spreadsheetID'] = '1c44ymPowJA2V3NdDNBiqNjvQ2PdCJ4Zgsa34KJmkbVA';
        $params['range']         = 'Sheet1!A2:B6217'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
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