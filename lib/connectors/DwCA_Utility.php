<?php
namespace php_active_record;
/* connector: [dwca_utility.php]
Processes any DwCA archive file.
Using the parentNameUsageID, generates a new DwCA with a new taxon column: http://rs.tdwg.org/dwc/terms/higherClassification
*/

class DwCA_Utility
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();

        // $this->dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
        $this->dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/ioc-birdlist.tar.gz";
        // $this->dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/26.tar.gz";
        
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26);
        $this->download_options["expire_seconds"] = false; //debug - false means it will use cache
        $this->debug = array();
    }

    function get_all_taxa()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => true)); //true means it will re-download, will not use cache. Set TRUE when developing
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];

        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        print_r($tables);
        $index = array_keys($tables);
        print_r($index);

        $records = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');
        if(self::can_compute_higherClassification($records))
        {
            // /*
            self::build_id_name_array($records);                            echo "\n1 of 8\n";
            $records = self::generate_higherClassification_field($records); echo "\n2 of 8\n";
            // */
            
            /*
            Array
                [0] => http://rs.tdwg.org/dwc/terms/taxon
                [1] => http://rs.gbif.org/terms/1.0/vernacularname
                [2] => http://rs.tdwg.org/dwc/terms/occurrence
                [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
            */
            
            /* Please take note of some Meta XML entries have upper and lower case differences */
            $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                      "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                      "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                      "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",
                                      "http://eol.org/schema/media/document"            => "document",
                                      "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                      "http://eol.org/schema/agent/agent"               => "agent");
            // /*
            foreach($index as $row_type)
            {
                if($this->extensions[$row_type] == "taxon") self::process_fields($records, $this->extensions[$row_type]);
                else                                        self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
            }
            $this->archive_builder->finalize(TRUE);
            // */
                                                                                        echo "\n3 of 8\n";
        }
        else echo "\nCannot compute higherClassification.\n";

        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        print_r($this->debug);
    }

    private function process_fields($records, $class)
    {
        foreach($records as $rec)
        {
            if    ($class == "vernacular")  $c = new \eol_schema\VernacularName();
            elseif($class == "agent")       $c = new \eol_schema\Agent();
            elseif($class == "reference")   $c = new \eol_schema\Reference();
            elseif($class == "taxon")       $c = new \eol_schema\Taxon();
            elseif($class == "document")    $c = new \eol_schema\MediaResource();
            elseif($class == "occurrence")  $c = new \eol_schema\Occurrence();
            elseif($class == "measurementorfact")   $c = new \eol_schema\MeasurementOrFact();
            
            if($class == "taxon")
            {
                print_r($rec);
                // exit("\n");
            }
            
            $keys = array_keys($rec);
            foreach($keys as $key)
            {
                $temp = pathinfo($key);
                $field = $temp["basename"];

                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];

                $c->$field = $rec[$key];

                // if($field == "taxonID") $c->$field = self::get_worms_taxon_id($c->$field); //not used here, only in WoRMS connector
            }
            $this->archive_builder->write_object_to_file($c);
        }
    }

    private function build_id_name_array($records)
    {
        foreach($records as $rec)
        {
            // [http://rs.tdwg.org/dwc/terms/taxonID] => 6de0dc42e8f4fc2610cb4287a4505764
            // [http://rs.tdwg.org/dwc/terms/scientificName] => Accipiter cirrocephalus rosselianus Mayr, 1940
            $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $this->id_name[$taxon_id]['scientificName'] = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
            $this->id_name[$taxon_id]['parentNameUsageID'] = (string) $rec["http://rs.tdwg.org/dwc/terms/parentNameUsageID"];
        }
    }
    
    private function generate_higherClassification_field($records)
    {   /*
        Array
            [http://rs.tdwg.org/dwc/terms/taxonID] => 5e2712849c197671c260f53809836273
            [http://rs.tdwg.org/dwc/terms/scientificName] => Passerina leclancherii leclancherii Lafresnaye, 1840
            [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 49fc924007e33cc43908fed677d5499a
        */
        $i = 0;
        foreach($records as $rec)
        {
            $rec["higherClassification"] = self::get_higherClassification($rec);
            print_r($rec);
            $records[$i]["higherClassification"] = $rec["higherClassification"];
            print_r($records[$i]);
            $i++;
        }
        return $records;
    }
    
    private function get_higherClassification($rek)
    {
        $parent_id = $rek['http://rs.tdwg.org/dwc/terms/parentNameUsageID'];
        $str = "";
        while($parent_id)
        {
            if($parent_id)
            {
                $str .= Functions::canonical_form(trim($this->id_name[$parent_id]['scientificName']))."|";
                $parent_id = @$this->id_name[$parent_id]['parentNameUsageID'];
            }
        }
        $str = substr($str, 0, strlen($str)-1);
        echo "\norig: [$str]";
        $arr = explode("|", $str);
        $arr = array_reverse($arr);
        $str = implode("|", $arr);
        echo "\n new: [$str]\n";
        return $str;
    }

    private function can_compute_higherClassification($records)
    {
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/taxonID"])) return false;
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/scientificName"])) return false;
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/parentNameUsageID"])) return false;
        return true;
    }
    //ends here 
    
    /* not used at the moment...
    private function create_taxa($taxa)
    {
        foreach($taxa as $t)
        {   
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $t['AphiaID'];
            $taxon->scientificName  = trim($t['scientificname'] . " " . $t['authority']);
            $taxon->taxonRank       = $t['rank'];
            $taxon->taxonomicStatus = $t['status'];
            $taxon->source          = $this->taxon_page . $t['AphiaID'];
            $taxon->parentNameUsageID = $t['parent_id'];
            $taxon->acceptedNameUsageID     = $t['valid_AphiaID'];
            $taxon->bibliographicCitation   = $t['citation'];
            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
        }
    }
    */
    
}
?>