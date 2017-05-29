<?php
namespace php_active_record;
// connector: [ictv.php]
class ICTVdwcaAPI
{
    function __construct($folder)
    {
        $this->page_to_download_the_spreadsheet = "http://www.birds.cornell.edu/clementschecklist/download/";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->single_reference_for_all = false;
        $this->levels = array("kingdom" => 1, "phylum" => 2, "class" => 3, "order" => 4, "family" => 5, "genus" => 6, "species" => 7, "subspecies" => 8);
        $this->download_options = array('expire_seconds' => 60*60*60*24*365, 'download_wait_time' => 3000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1); //expires in 1 year
    }

    function get_all_taxa($data_dump_url = false)
    {
        $this->data_dump_url = self::get_dump_url();
        // $this->data_dump_url = "http://localhost/cp/ICTV/ICTV Master Species List 2016 v1.1.xlsx"; //debug

        $records = self::parse_xls();
        // print_r($records);
        foreach($records as $key => $rec)
        {
            $t = new \eol_schema\Taxon();
            $t->taxonID = str_ireplace("ICTVonline=", "ICTV:", $key);
            if($val = @$rec['sciname'])
            {
                $t->scientificName = $val;
                $t->taxonRank = @$rec['rank'];
            }
            elseif($val = @$rec['Species'])
            {
                $t->scientificName = $val;
                $t->taxonRank = "species";
                $t->source = "https://talk.ictvonline.org/taxonomy/p/taxonomy-history?taxnode_id=".str_ireplace("ICTV:", "", $t->taxonID);
            }
            $t->parentNameUsageID = @$rec['parent_id']; //placed @ bec ICTV:Viruses doesn't have parent_id
            if(!isset($this->taxon_ids[$t->taxonID]))
            {
                $this->taxon_ids[$t->taxonID] = '';
                $this->archive_builder->write_object_to_file($t);
            }
        }
        $this->archive_builder->finalize(TRUE);
        // remove tmp file
        unlink($this->data_dump_url);
        debug("\n temporary file removed: [$this->data_dump_url]");
    }

    private function parse_xls()
    {
        if($this->data_dump_url = Functions::save_remote_file_to_local($this->data_dump_url, array('cache' => 1, 'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 5, 'file_extension' => 'xlsx')))
        {
            require_library('XLSParser');
            $parser = new XLSParser();
            debug("\n reading: " . $this->data_dump_url . "\n");
            $temp = $parser->convert_sheet_to_array($this->data_dump_url, 2);
            $records = $parser->prepare_data($temp, "single", "Taxon History URL", "Order", "Family", "Subfamily", "Genus", "Species", "Type Species?", "Exemplar Accession Number", "Exemplar Isolate", "Genome Composition", "Last Change", "MSL of Last Change", "Proposal", "Taxon History URL");
            // print_r($records); echo count($records);
            $records = self::add_nodes_for_order_family_genus($records);
            return $records;
        }
    }
    
    private function add_nodes_for_order_family_genus($records)
    {
        // [Order] => Unassigned
        // [Family] => Virgaviridae
        // [Genus] => Tobravirus
        
        //add root node "Viruses"
        $records["ICTV:Viruses"] = array("sciname" => "Viruses");
        
        //add order family subfamily genus nodes
        foreach($records as $key => $rec)
        {
            $rec = array_map("trim", $rec);
            
            if($order = @$rec['Order'])
            {
                if($order == "Unassigned") $order = "unplaced Viruses";
                if(!isset($records[$order]))    $records["ICTV:$order"] = array("sciname" => $order, "rank" => "Order", "parent_id" => "ICTV:Viruses");
                if($order == "unplaced Viruses") 
                {
                    $records[$key]['Order'] = "unplaced Viruses";
                    $rec['Order']           = "unplaced Viruses";
                }
            }
            if($family = @$rec['Family'])
            {
                if(!isset($records[$family]))       $records["ICTV:$family"] = array("sciname" => $family, "rank" => "Family", "parent_id" => self::get_parent($rec, 'family'));
            }
            if($subfamily = @$rec['Subfamily'])
            {
                if(!isset($records[$subfamily]))    $records["ICTV:$subfamily"] = array("sciname" => $subfamily, "rank" => "Subfamily", "parent_id" => self::get_parent($rec, 'subfamily'));
            }
            if($genus = @$rec['Genus'])
            {
                if(!isset($records[$genus]))        $records["ICTV:$genus"] = array("sciname" => $genus, "rank" => "Genus", "parent_id" => self::get_parent($rec, 'genus'));
            }
            
            if(@$rec['Species'])
            {
                //get parent of species level
                $records[$key]['parent_id'] = self::get_parent($rec, 'species');
            }
            
        }
        return $records;
    }

    private function get_parent($rec, $rank)
    {   /*
        [Order] => Tymovirales
        [Family] => Alphaflexiviridae
        [Subfamily] => 
        [Genus] => Potexvirus
        [Species] => Lily virus X
        */

        if($rank == "species")
        {
            if(($genus       = @$rec['Genus'])        && @$rec['Genus'] != "Unassigned")      return "ICTV:$genus";
            if(($subfamily   = @$rec['Subfamily'])    && @$rec['Subfamily'] != "Unassigned")  return "ICTV:$subfamily";
            if(($family      = @$rec['Family'])       && @$rec['Family'] != "Unassigned")     return "ICTV:$family";
            if(($order       = @$rec['Order'])        && @$rec['Order'] != "Unassigned")      return "ICTV:$order";
            return "ICTV:Viruses";
        }
        elseif($rank == "genus")
        {
            if(($subfamily   = @$rec['Subfamily'])    && @$rec['Subfamily'] != "Unassigned")  return "ICTV:$subfamily";
            if(($family      = @$rec['Family'])       && @$rec['Family'] != "Unassigned")     return "ICTV:$family";
            if(($order       = @$rec['Order'])        && @$rec['Order'] != "Unassigned")      return "ICTV:$order";
            return "ICTV:Viruses";
        }
        elseif($rank == "subfamily")
        {
            if(($family      = @$rec['Family'])       && @$rec['Family'] != "Unassigned")     return "ICTV:$family";
            if(($order       = @$rec['Order'])        && @$rec['Order'] != "Unassigned")      return "ICTV:$order";
            return "ICTV:Viruses";
        }
        elseif($rank == "family")
        {
            if(($order       = @$rec['Order'])        && @$rec['Order'] != "Unassigned")      return "ICTV:$order";
            return "ICTV:Viruses";
        }
    }

    private function get_dump_url()
    {
        $url = "https://talk.ictvonline.org/files/master-species-lists/m/msl";
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match("/<h3 class=\"name\">(.*?)<\/h3>/ims", $html, $arr))
            {
                if(preg_match("/\"(.*?)\"/ims", $arr[1], $arr2))
                {
                    return $arr2[1]."/download";
                }
            }
        }
        return false;
    }

}
?>