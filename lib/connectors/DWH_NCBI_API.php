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
        $this->file['nodes.dmp']['fields'] = array("tax_id", "parent_tax_id", "rank", "embl_code", "division_id", "inherited div flag", "genetic code id", "inherited GC flag", 
        "mitochondrial genetic code id", "inherited MGC flag", "GenBank hidden flag", "hidden subtree root flag", "comments");
    }

    function start()
    {
        /* test
        $taxID_info = self::get_taxID_nodes_info();
        $ancestry = self::get_ancestry_of_taxID(415666, $taxID_info); print_r($ancestry);
        $ancestry = self::get_ancestry_of_taxID(503548, $taxID_info); print_r($ancestry);
        exit("\n-end tests-\n");
        */
        /* test
        $removed_branches = self::get_removed_branches_from_spreadsheet(); print_r($removed_branches);
        exit("\n-end tests-\n");
        */
        
        self::main(); //exit("\nstop muna\n");
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
    }
    private function get_ancestry_of_taxID($tax_id, $taxID_info)
    {
        /* Array(
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
            // print_r($rec); exit;
            if(isset($final[$rec['tax_id']])) exit("\nInvestigate not unique tax_id in nodes.dmp\n");
            $final[$rec['tax_id']] = array("pID" => $rec['parent_tax_id'], 'r' => $rec['rank'], 'dID' => $rec['division_id']);
            
            // print_r($final); exit;
        }
        fclose($file);
        return $final;
        // exit("\nstopx\n");
    }
    private function main()
    {
        $taxID_info['xxx'] = array("pID" => '', 'r' => '', 'dID' => '');
        $taxID_info = self::get_taxID_nodes_info();
        $removed_branches = self::get_removed_branches_from_spreadsheet();

        echo "\nMain processing...";
        $fields = $this->file['names.dmp']['fields'];
        $file = Functions::file_open($this->file['names.dmp']['path'], "r");
        $i = 0; $processed = 0;
        if(!$file) exit("\nFile not found!\n");
        while (($row = fgets($file)) !== false) {
            $i++;
            $row = explode("\t|", $row); array_pop($row); $row = array_map('trim', $row);
            if(($i % 300000) == 0) echo "\n count:[$i] ";
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
            // print_r($rec); exit;
            /* Array(
                [tax_id] => 1
                [name_txt] => all
                [unique_name] => 
                [name_class] => synonym
            )*/
          
            /* start filtering: 
            1. Filter by division_id: Remove taxa where division_id in nodes.dmp is 7 (environmental samples) or 11 (synthetic and chimeric taxa) */
            if(in_array($taxID_info[$rec['tax_id']]['dID'], array(7,11))) continue;
            // Total rows: 2687427      Processed rows: 2609534

            /* 2. Filter by text string
            a. Remove taxa that have the string “environmental sample” in their scientific name. This will get rid of those environmental samples that don’t have the environmental samples division for some reason. */
            if(stripos($rec['name_txt'], "environmental sample") !== false) continue; //string is found
            // Total rows: 2687427      Processed rows: 2609488
            
            /* b. Remove all taxa of rank species where the scientific name includes one of the following strings: sp.|aff.|cf.|nr.
            This will get rid of a lot of the samples that haven’t been identified to species. */
            if($taxID_info[$rec['tax_id']]['r'] == 'species') {
                if(stripos($rec['name_txt'], " sp.") !== false) continue; //string is found
                elseif(stripos($rec['name_txt'], " aff.") !== false) continue; //string is found
                elseif(stripos($rec['name_txt'], " cf.") !== false) continue; //string is found
                elseif(stripos($rec['name_txt'], " nr.") !== false) continue; //string is found
            }
            // Total rows: 2687427      Processed rows: 1686211
            
            if(in_array($rec['name_class'], array("blast name", "type material", "includes", "acronym", "genbank acronym"))) continue; //ignore these names
            
            /* 3. Remove branches */
            if(in_array($rec['name_class'], array("scientific name", "common name", "genbank common name"))) {
                $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $this->debug['id with an ancestry that is included among removed branches'][$rec['tax_id']] = '';
                    // echo "\nid with an ancestry that is included among removed branches [".$rec['tax_id']."]";
                    continue;
                }
                self::write_taxon($rec, $ancestry, $taxID_info[$rec['tax_id']]);
            }
            // Total rows: 2687427      Processed rows: 1648267

            $processed++;
        }
        fclose($file);
        echo "\nTotal rows: $i";
        echo "\nProcessed rows: $processed";
    }
    private function write_taxon($rec, $ancestry, $taxid_info)
    {
        /* Array(
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
        if(in_array($rec['name_class'], array("scientific name"))) {
            $tax_id = $rec['tax_id'];
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID = $tax_id;
            $taxon->parentNameUsageID = $taxid_info['pID'];
            $taxon->taxonRank = $taxid_info['r'];
            $taxon->scientificName = $rec['name_txt'];
            $taxon->taxonomicStatus = "accepted";
            $taxon->furtherInformationURL = "https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=".$tax_id;
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
    private function an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)
    {
        foreach($ancestry as $id) {
            if(in_array($id, $removed_branches)) return true;
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
        $final = array_keys($final);
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
    /*
    private function valid_rek($rek, $rec)
    {
        if(in_array($rek['Invasive'], array("Invasive", "Not invasive"))) $good[] = array('region' => $rek['region'], 'range' => $rek['Invasive'], "refs" => $refs, 'measurementRemarks' => $rem);
        return $good;
    }
    private function get_mtype_for_range($range)
    {
        switch($range) {
            case "Introduced":                  return "http://eol.org/schema/terms/IntroducedRange";
            case "Invasive":                    return "http://eol.org/schema/terms/InvasiveRange";
            case "Native":                      return "http://eol.org/schema/terms/NativeRange";
            case "Not invasive":                return "http://eol.org/schema/terms/NonInvasiveRange";
            case "Present, few occurrences":    return "http://eol.org/schema/terms/presentAndRare";
            case "Absent, formerly present":    return "http://eol.org/schema/terms/absentFormerlyPresent";
            case "Eradicated":                  return "http://eol.org/schema/terms/absentFormerlyPresent";
            case "Localised":                   return "http://eol.org/schema/terms/Present";
            case "Widespread":                  return "http://eol.org/schema/terms/Present";
            case "Present":                     return "http://eol.org/schema/terms/Present";
        }
        if(in_array($range, $this->considered_as_Present)) return "http://eol.org/schema/terms/Present";
    }
    private function write_reference($ref)
    {
        if(!@$ref['full_ref']) return false;
        $re = new \eol_schema\Reference();
        $re->identifier = md5($ref['full_ref']);
        $re->full_reference = $ref['full_ref'];
        if($path = @$ref['ref_url']) $re->uri = $this->domain['ISC'].$path; // e.g. https://www.cabi.org/isc/abstract/20000808896
        if(!isset($this->reference_ids[$re->identifier])) {
            $this->archive_builder->write_object_to_file($re);
            $this->reference_ids[$re->identifier] = '';
        }
        return $re->identifier;
    }
    */

}
?>