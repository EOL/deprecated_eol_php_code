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
        $this->taxa = array();
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
        self::main(); exit("\nstop muna\n");
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
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
            
            
            $processed++;
        }
        fclose($file);
        echo "\nTotal rows: $i";
        echo "\nProcessed rows: $processed";
    }
    /*
    private function valid_rek($rek, $rec)
    {
        if(in_array($rek['Invasive'], array("Invasive", "Not invasive"))) $good[] = array('region' => $rek['region'], 'range' => $rek['Invasive'], "refs" => $refs, 'measurementRemarks' => $rem);
        return $good;
    }
    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec["taxon_id"];
        $taxon->scientificName  = $rec["Scientific name"];
        $taxon->taxonRank = self::get_rank($rec['ancestry'], $rec["Scientific name"]);
        foreach(array_keys($rec['ancestry']) as $rank) {
            if($rank == $taxon->taxonRank) break;
            if(in_array($rank, array('kingdom', 'phylum', 'class', 'order', 'family', 'genus'))) {
                $taxon->$rank = $rec['ancestry'][$rank];
            }
        }
        $taxon->furtherInformationURL = $rec["source_url"];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
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