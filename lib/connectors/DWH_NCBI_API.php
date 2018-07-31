<?php
namespace php_active_record;
/* connector: [dwh_ncbi.php]
https://eol-jira.bibalex.org/browse/TRAM-795
*/

class DWH_NCBI_API
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->download_options = array('resource_id' => $folder, 'download_wait_time' => 1000000, 'timeout' => 60*2, 'download_attempts' => 1, 'cache' => 1); // 'expire_seconds' => 0
        $this->debug = array();
        $this->file['names.dmp']['path'] = "/Volumes/AKiTiO4/d_w_h/TRAM-795/taxdump/names.dmp";
        $this->file['names.dmp']['fields'] = array("tax_id", "name_txt", "unique_name", "name_class");
        $this->file['nodes.dmp']['path'] = "/Volumes/AKiTiO4/d_w_h/TRAM-795/taxdump/nodes.dmp";
        $this->file['nodes.dmp']['fields'] = array("tax_id", "parent_tax_id", "rank", "embl_code", "division_id", "inherited div flag", "genetic code id", "inherited GC flag", "mitochondrial genetic code id", "inherited MGC flag", "GenBank hidden flag", "hidden subtree root flag", "comments");
        $this->file['citations.dmp']['path'] = "/Volumes/AKiTiO4/d_w_h/TRAM-795/taxdump/citations.dmp";
        $this->file['citations.dmp']['fields'] = array("cit_id", "cit_key", "pubmed_id", "medline_id", "url", "text", "taxid_list");
        $this->alternative_names = array("synonym", "equivalent name", "in-part", "misspelling", "genbank synonym", "misnomer", "anamorph", "genbank anamorph", "teleomorph", "authority");
        //start TRAM-796 -----------------------------------------------------------
        $this->prune_further = array(10239, 12884, 3193, 4751, 33208);
        $this->extension_path = CONTENT_RESOURCE_LOCAL_PATH . "NCBI_Taxonomy_Harvest/";
        $this->dwca['iterator_options'] = array('row_terminator' => "\n");
    }
    // ----------------------------------------------------------------- start TRAM-796
    private function get_meta_info()
    {
        require_library('connectors/DHSourceHierarchiesAPI');
        $func = new DHSourceHierarchiesAPI();
        $meta = $func->analyze_eol_meta_xml($this->extension_path."meta.xml");
        print_r($meta);
        return $meta;
    }
    function tram_796_start() //pruning further
    {
        /* test
        $arr = array("endophyte SE2/SE4", "Phytophthora citricola IV", "Hyperolius nasutus A JK-2009",  "Cryptococcus neoformans AD hybrid", "Gadus morhua");
        $arr = array("Hyperolius nasutus AB");
        foreach($arr as $sci) {
            if(self::with_consecutive_capital_letters($sci)) echo "\nYES ($sci)\n";
            else echo "\nNO ($sci)\n";
        }
        exit;
        */
        
        $taxID_info = self::get_taxID_nodes_info();
        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            //start filter
            /*Array(
                [taxonID] => 1_1
                [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1
                [acceptedNameUsageID] => 1
                [parentNameUsageID] => 
                [scientificName] => all
                [taxonRank] => no rank
                [taxonomicStatus] => synonym
                [referenceID] => 
            )*/
            /* 1. Remove ALL taxa (and their children, grandchildren, etc.) that have one of the following strings in their scientific name: uncultured|unidentified */
            // /*
            if($rec['taxonomicStatus'] == "accepted") {
                if(stripos($rec['scientificName'], "uncultured") !== false)   {$filtered_ids[$rec['taxonID']] = ''; continue;} //string is found
                if(stripos($rec['scientificName'], "unidentified") !== false) {$filtered_ids[$rec['taxonID']] = ''; continue;} //string is found
            }
            // */
            /* 2. ONLY for taxa where division_id in nodes.dmp IS 0 (bacteria & archaea), we want to remove all taxa that have the string “unclassified” in their scientific name. */
            // /*
            if($rec['taxonomicStatus'] == "accepted") {
                if(in_array($taxID_info[$rec['taxonID']]['dID'], array(0))) {
                    if(stripos($rec['scientificName'], "unclassified") !== false) {$filtered_ids[$rec['taxonID']] = ''; 
                        // print_r($rec); print_r($taxID_info[$rec['taxonID']]); exit("\nrule 2\n"); //good debug
                        continue;} //string is found
                }
            }
            // */
            /* 3. ONLY for taxa where division_id in nodes.dmp IS NOT 0 (things that are not bacteria or archaea), we want to remove all taxa of RANK species that have consecutive 
            capital letters not separated by a white space in their scientific name, e.g., things like “endophyte SE2/SE4” or “Phytophthora citricola IV” or “Hyperolius nasutus A JK-2009” or 
            “Cryptococcus neoformans AD hybrid” */
            if($rec['taxonomicStatus'] == "accepted") {
                if($taxID_info[$rec['taxonID']]['dID'] != 0) {
                    $rank = $taxID_info[$rec['taxonID']]['r'];
                    if($rank == "species") {
                        if(self::with_consecutive_capital_letters($rec['scientificName'])) {
                            print_r($rec); print_r($taxID_info[$rec['taxonID']]); exit("\nrule 3\n"); //good debug
                        }
                    }
                }
            }
            
        }

        echo "\nStart main process 2...\n";
        exit("\nstop muna\n");
    }
    private function with_consecutive_capital_letters($str)
    {
        echo "\n$str\n";
        $words = explode(" ", $str);
        print_r($words);
        foreach($words as $word) {
            echo "\n word -- $word\n";
            $word = preg_replace("/[^a-zA-Z]/", "", $word); //get only a-z A-Z
            echo "\n new word -- $word\n";
            $with_small = false;
            for ($i = 0; $i <= strlen($word)-1; $i++) {
                if(is_numeric($word[$i])) continue;
                echo " ".$word[$i];
                if(!ctype_upper($word[$i])) $with_small = true;
            }
            if(!$with_small && strlen($word) > 1) return true;
        }
        return false;
    }
    // ----------------------------------------------------------------- end TRAM-796
    function start()
    {
        // 19   https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=19      18  Pelobacter carbinolicus species accepted    2912; 5381
        /* test
        $taxID_info = self::get_taxID_nodes_info();
        $ancestry = self::get_ancestry_of_taxID(936556, $taxID_info); print_r($ancestry);
        // $ancestry = self::get_ancestry_of_taxID(503548, $taxID_info); print_r($ancestry);
        exit("\n-end tests-\n");
        */
        /* test
        $removed_branches = self::get_removed_branches_from_spreadsheet(); print_r($removed_branches);
        exit("\n-end tests-\n");
        */
        /*
        self::browse_citations();
        exit("\n-end tests-\n");
        */
        self::main(); //exit("\nstop muna\n");
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
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
    private function get_taxID_nodes_info()
    {
        echo "\nGenerating taxID_info...";
        $final = array();
        $fields = $this->file['nodes.dmp']['fields'];
        $file = Functions::file_open($this->file['nodes.dmp']['path'], "r");
        $i = 0;
        if(!$file) exit("\nFile not found!\n");
        while (($row = fgets($file)) !== false) {
            $i++;
            $row = explode("\t|", $row);
            array_pop($row);
            $row = array_map('trim', $row);
            if(($i % 300000) == 0) echo "\n count:[$i] ";
            $row = array_map('trim', $row);
            $vals = $row;
            if(count($fields) != count($vals)) {
                print_r($vals); exit("\nNot same count ".count($fields)." != ".count($vals)."\n"); continue;
            }
            if(!$vals[0]) continue;
            $k = -1; $rec = array();
            foreach($fields as $field) {
                $k++;
                $rec[$field] = $vals[$k];
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit;
            if(isset($final[$rec['tax_id']])) exit("\nInvestigate not unique tax_id in nodes.dmp\n");
            $final[$rec['tax_id']] = array("pID" => $rec['parent_tax_id'], 'r' => $rec['rank'], 'dID' => $rec['division_id']);
            // print_r($final); exit;
        }
        fclose($file);
        return $final;
    }
    private function main()
    {
        $filtered_ids = array();
        $taxon_refs = self::browse_citations();
        
        $taxID_info['xxx'] = array("pID" => '', 'r' => '', 'dID' => '');
        $taxID_info = self::get_taxID_nodes_info();
        
        $removed_branches = self::get_removed_branches_from_spreadsheet();
        /* additional IDs are taken from undefined_parents report after each connector run */
        $removed_branches[1296341] = '';
        $removed_branches[993557] = '';
        $removed_branches[1391733] = '';
        /* no need to add this, same result anyway
        $removed_branches[1181] = '';
        $removed_branches[1188] = '';
        $removed_branches[56615] = '';
        $removed_branches[59765] = '';
        $removed_branches[169066] = '';
        $removed_branches[242159] = '';
        $removed_branches[252598] = '';
        $removed_branches[797742] = '';
        $removed_branches[1776082] = '';
        */
        echo "\nMain processing...";
        $fields = $this->file['names.dmp']['fields'];
        $file = Functions::file_open($this->file['names.dmp']['path'], "r");
        $i = 0; $processed = 0;
        if(!$file) exit("\nFile not found!\n");
        $this->ctr = 1; $old_id = "elix";
        while (($row = fgets($file)) !== false) {
            $i++;
            $row = explode("\t|", $row); array_pop($row); $row = array_map('trim', $row);
            if(($i % 300000) == 0) echo "\n count:[$i] ";
            $row = array_map('trim', $row);
            $vals = $row;
            if(count($fields) != count($vals)) {
                print_r($vals); exit("\nNot same count ".count($fields)." != ".count($vals)."\n"); continue;
            }
            if(!$vals[0]) continue;
            $k = -1; $rec = array();
            foreach($fields as $field) {
                $k++;
                $rec[$field] = $vals[$k];
            }
            $rec = array_map('trim', $rec);
            /* good debug --------------------------------------------------------------------------------------------
            if($rec['tax_id'] == 1391733) { //1844527
                print_r($rec); print_r($taxID_info[$rec['tax_id']]); 
                
                if(isset($filtered_ids[$rec['tax_id']])) exit("\ntax_id is part of filtered\n");
                $parent_id = $taxID_info[$rec['tax_id']]['pID'];
                if(isset($filtered_ids[$parent_id])) exit("\nparent id is part of filtered\n");
                
                $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
                print_r($ancestry);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    echo "\ntaxon where an id in its ancestry is included among removed branches\n";
                }
                else echo "\nNot part of removed branch\n";
                exit("\ncha 01\n");
            }
            -------------------------------------------------------------------------------------------- */
            // print_r($rec); exit;
            /* Array(
                [tax_id] => 1
                [name_txt] => all
                [unique_name] => 
                [name_class] => synonym
            )*/
            /* start filtering: 
            1. Filter by division_id: Remove taxa where division_id in nodes.dmp is 7 (environmental samples) or 11 (synthetic and chimeric taxa) */
            if(in_array($taxID_info[$rec['tax_id']]['dID'], array(7,11))) {$filtered_ids[$rec['tax_id']] = ''; continue;}
            // Total rows: 2687427      Processed rows: 2609534

            /* 2. Filter by text string
            a. Remove taxa that have the string “environmental sample” in their scientific name. This will get rid of those environmental samples that don’t have the environmental samples division for some reason. */
            if(stripos($rec['name_txt'], "environmental sample") !== false) {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
            // Total rows: 2687427      Processed rows: 2609488
            
            /* b. Remove all taxa of rank species where the scientific name includes one of the following strings: sp.|aff.|cf.|nr.
            This will get rid of a lot of the samples that haven’t been identified to species. */

            /*
            85262	|	African violet	|		|	common name	|
            85262	|	Saintpaulia ionantha	|		|	synonym	|
            85262	|	Saintpaulia ionantha H.Wendl.	|		|	authority	|
            85262	|	Saintpaulia sp. 'Sigi Falls'	|		|	includes	|
            85262	|	Streptocarpus ionanthus	|		|	scientific name	|
            85262	|	Streptocarpus ionanthus (H.Wendl.) Christenh.	|		|	authority	|
            85262	|	Streptocarpus sp. 'Sigi Falls'	|		|	includes	|
            
            Irregardless of the other filter rules. Let us look at this single rule:
            "Remove all taxa of rank species where the scientific name includes one of the following strings: sp.|aff.|cf.|nr."
            
            Assuming 85262 is rank 'species'.
            Is "Streptocarpus ionanthus" with name class = "scientific name" be excluded since the alternative names has ' sp.'.
            Or the rule for removing taxa with ' sp.' only affects where name class is "scientific name".
            So in this case "Streptocarpus ionanthus" will be included since it doesn't have ' sp.'
            And alternatives will only be:
            85262	|	Saintpaulia ionantha	|		|	synonym	|
            85262	|	Saintpaulia ionantha H.Wendl.	|		|	authority	|
            85262	|	Streptocarpus ionanthus (H.Wendl.) Christenh.	|		|	authority	|
            */
            
            if($rec['name_class'] == "scientific name") {
                $rank = $taxID_info[$rec['tax_id']]['r'];
                if(in_array($rank, array('species', 'no rank'))) {
                    if(stripos($rec['name_txt'], " sp.") !== false)      {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " aff.") !== false) {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " cf.") !== false)  {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " nr.") !== false)  {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                }
            }
            elseif(in_array($rec['name_class'], $this->alternative_names)) {
                if(stripos($rec['name_txt'], " sp.") !== false)      {continue;} //string is found
                elseif(stripos($rec['name_txt'], " aff.") !== false) {continue;} //string is found
                elseif(stripos($rec['name_txt'], " cf.") !== false)  {continue;} //string is found
                elseif(stripos($rec['name_txt'], " nr.") !== false)  {continue;} //string is found
            }
            // Total rows: xxx      Processed rows: xxx
            
            if(in_array($rec['name_class'], array("blast name", "type material", "includes", "acronym", "genbank acronym"))) continue; //ignore these names
            
            /* 3. Remove branches 
            // if(in_array($rec['name_class'], array("scientific name", "common name", "genbank common name"))) {
                $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['tax_id']] = '';
                    continue;
                }
                if($old_id != $rec['tax_id']) $this->ctr = 1;
                else                          $this->ctr++;
                $old_id = $rec['tax_id'];
                
                if($val = @$taxon_refs[$rec['tax_id']]) $reference_ids = array_keys($val);
                else                                    $reference_ids = array();
                
                self::write_taxon($rec, $ancestry, $taxID_info[$rec['tax_id']], $reference_ids);
            // }
            */
            // Total rows: 2687427      Processed rows: 1648267
            $processed++;
        }
        fclose($file);
        
        // =================================================start 2
        echo "\nMain processing 2...";
        $fields = $this->file['names.dmp']['fields'];
        $file = Functions::file_open($this->file['names.dmp']['path'], "r");
        $i = 0; $processed = 0;
        if(!$file) exit("\nFile not found!\n");
        $this->ctr = 1; $this->old_id = "elix";
        while (($row = fgets($file)) !== false) {
            $i++;
            $row = explode("\t|", $row); array_pop($row); $row = array_map('trim', $row);
            if(($i % 300000) == 0) echo "\n count:[$i] ";
            $row = array_map('trim', $row);
            $vals = $row;
            if(count($fields) != count($vals)) {
                print_r($vals); exit("\nNot same count ".count($fields)." != ".count($vals)."\n"); continue;
            }
            if(!$vals[0]) continue;
            $k = -1; $rec = array();
            foreach($fields as $field) {
                $k++;
                $rec[$field] = $vals[$k];
            }
            $rec = array_map('trim', $rec);
            /* Array(
                [tax_id] => 1
                [name_txt] => all
                [unique_name] => 
                [name_class] => synonym
            )*/

            if(in_array($rec['name_class'], array("blast name", "type material", "includes", "acronym", "genbank acronym"))) continue; //ignore these names
            if(isset($filtered_ids[$rec['tax_id']])) continue;
            $parent_id = $taxID_info[$rec['tax_id']]['pID'];
            $parent_id = trim($parent_id);
            if(isset($filtered_ids[$parent_id])) continue;
            
            /* 3. Remove branches */
            $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['tax_id']] = '';
                continue;
            }
            
            if($val = @$taxon_refs[$rec['tax_id']]) $reference_ids = array_keys($val);
            else                                    $reference_ids = array();

            if($this->old_id != $rec['tax_id']) $this->ctr = 1;
            else {}
            $this->old_id = $rec['tax_id'];
            
            self::write_taxon($rec, $ancestry, $taxID_info[$rec['tax_id']], $reference_ids);
            
            if($this->old_id != $rec['tax_id']) {}
            else {
                if(in_array($rec['name_class'], $this->alternative_names)) $this->ctr++;
            }
            
            $processed++;
        }
        fclose($file);
        // =================================================end 2
        // Total rows: 2687427 Processed rows: 1508421 ------ looks OK finally
        echo "\nTotal rows: $i";
        echo "\nProcessed rows: $processed";
    }
    private function write_taxon($rec, $ancestry, $taxid_info, $reference_ids)
    {   /* Array(
            [tax_id] => 1
            [name_txt] => all
            [unique_name] => 
            [name_class] => synonym
        )
        Array(
            [1] => Array(
                    [pID] => 1
                    [r] => no rank
                    [dID] => 8
                )
        )*/
        /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. Sorry, I realize I didn't make this clear in my initial instructions. 
        I have added a note about it now. */
        if(!in_array($rec['name_class'], array("common name", "genbank common name"))) {
            $computed_ids = self::format_tax_id($rec);
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID = $computed_ids['tax_id'];
            
            if($rec['name_class'] == "scientific name") $taxon->parentNameUsageID = $taxid_info['pID'];
            else                                        $taxon->parentNameUsageID = "";
            
            $taxon->taxonRank = $taxid_info['r'];
            $taxon->scientificName = $rec['name_txt'];
            $taxon->taxonomicStatus = self::format_status($rec['name_class']);
            $taxon->acceptedNameUsageID = $computed_ids['acceptedNameUsageID'];
            $taxon->furtherInformationURL = "https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=".$rec['tax_id'];
            if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
        }
        if(in_array($rec['name_class'], array("common name", "genbank common name"))) {
            if($common_name = @$rec['name_txt']) {
                $v = new \eol_schema\VernacularName();
                $v->taxonID = $rec["tax_id"];
                $v->vernacularName = trim($common_name);
                $v->language = "en";
                $vernacular_id = md5("$v->taxonID|$v->vernacularName|$v->language");
                if(!isset($this->vernacular_ids[$vernacular_id])) {
                    $this->vernacular_ids[$vernacular_id] = '';
                    $this->archive_builder->write_object_to_file($v);
                }
            }
        }
    }
    private function format_tax_id($rec)
    {   /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. Sorry, I realize I didn't make this clear in my initial instructions. 
        I have added a note about it now. */
        
        if($rec['name_class'] == "scientific name")                    return array('tax_id' => $rec['tax_id']                 , 'acceptedNameUsageID' => '');
        elseif(in_array($rec['name_class'], $this->alternative_names)) return array('tax_id' => $rec['tax_id']."_".$this->ctr  , 'acceptedNameUsageID' => $rec['tax_id']);
        else {
            print_r($rec);
            exit("\nInvestigate cha001\n");
        }
    }
    private function format_status($name_class)
    {
        $verbatim = array("equivalent name", "in-part", "misspelling", "genbank synonym", "misnomer", "anamorph", "genbank anamorph", "teleomorph", "authority");
        if($name_class == "scientific name") return "accepted";
        elseif($name_class == "synonym") return "synonym";
        elseif(in_array($name_class, $verbatim)) return $name_class;
        exit("\nUndefined name_class [$name_class]\n");
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
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1eWXWK514ivl072FLm7dF2MpL9W29bs6XYDbPjHtWlxE';
        $params['range']         = 'Sheet1!A2:A16'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]] = '';
        /* $final = array_keys($final); */ //commented so we can use isset() instead of in_array()
        return $final;
        /* if google spreadsheet suddenly becomes offline, use this:
        Array(
            [0] => 12908
            [1] => 28384
            [2] => 2323
            [3] => 1035835
            [4] => 1145094
            [5] => 119167
            [6] => 590738
            [7] => 1407750
            [8] => 272150
            [9] => 1899546
            [10] => 328343
            [11] => 463707
            [12] => 410859
            [13] => 341675
            [14] => 56059
        )*/
    }
    private function browse_citations()
    {
        $this->debug['citation wrong cols'] = 0;
        echo "\nCitations...";
        $final = array();
        $fields = $this->file['citations.dmp']['fields'];
        $file = Functions::file_open($this->file['citations.dmp']['path'], "r");
        $i = 0;
        if(!$file) exit("\nFile not found!\n");
        while (($row = fgets($file)) !== false) {
            $i++;
            $row = explode("\t|", $row);
            array_pop($row);
            $row = array_map('trim', $row);
            if(($i % 10000) == 0) echo "\n count:[$i] ";
            $vals = $row;
            if(count($fields) != count($vals)) {
                // print_r($vals); exit("\nNot same count ".count($fields)." != ".count($vals)."\n"); 
                /* there is just 1 or 2 erroneous records, just ignore */
                $this->debug['citation wrong cols']++;
                continue;
            }
            if(!$vals[0]) continue;
            $k = -1; $rec = array();
            foreach($fields as $field) {
                $k++;
                $rec[$field] = $vals[$k];
            }
            /*Array(
                [cit_id] => 24654
                [cit_key] => Catalan P et al. 2009
                [pubmed_id] => 0
                [medline_id] => 0
                [url] => 
                [text] => Catalan P, Soreng RJ, Peterson PM and Peterson P. 2009. Festuca aloha and Festuca molokaiensis (Poaceae: Loliinae), two new species from Hawaii. Journal of the Botanical Research Institute of Texas 3(1): 51-58.
                [taxid_list] => 650148 650149
            )*/
            // print_r($rec); //exit;
            $taxids = explode(" ", $rec['taxid_list']);
            foreach($taxids as $tax_id) {
                $final[$tax_id][$rec['cit_id']] = '';
                self::write_reference($rec);
            }
        }
        // print_r($final);
        // print_r(array_keys($final[1352138])); exit;
        // print_r($this->debug); exit;
        fclose($file);
        return $final;
    }
    private function write_reference($ref)
    {
        if(!@$ref['text']) return false;
        $re = new \eol_schema\Reference();
        $re->identifier     = $ref['cit_id'];
        $re->full_reference = $ref['text'];
        $re->uri            =  $ref['url']; 
        if(!isset($this->reference_ids[$re->identifier])) {
            $this->archive_builder->write_object_to_file($re);
            $this->reference_ids[$re->identifier] = '';
        }
    }
    /*
    private function get_mtype_for_range($range)
    {
        switch($range) {
            case "Introduced":                  return "http://eol.org/schema/terms/IntroducedRange";
            case "Invasive":                    return "http://eol.org/schema/terms/InvasiveRange";
            case "Native":                      return "http://eol.org/schema/terms/NativeRange";
        }
        if(in_array($range, $this->considered_as_Present)) return "http://eol.org/schema/terms/Present";
    }
    */
}
?>