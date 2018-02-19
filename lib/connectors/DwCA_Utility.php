<?php
namespace php_active_record;
/* connector: [dwca_utility.php]
Processes any DwCA archive file.
Using the parentNameUsageID, generates a new DwCA with a new taxon column: http://rs.tdwg.org/dwc/terms/higherClassification
User Warning: Undefined property `rights` on eol_schema\Taxon as defined by `http://rs.tdwg.org/dwc/xsd/tdwg_dwcterms.xsd` in /Library/WebServer/Documents/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 168
*/
class DwCA_Utility
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        /* un-comment if it will cause probs to other connectors
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26);
        $this->download_options["expire_seconds"] = false; //debug - false means it will use cache
        */
        $this->debug = array();
        
        /* Please take note of some Meta XML entries have upper and lower case differences */
        $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                  "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                  "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                  "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",
                                  "http://eol.org/schema/media/document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                  "http://eol.org/schema/agent/agent"               => "agent",

                                  //start of other row_types: check for NOTICES or WARNINGS, add here those undefined URIs
                                  "http://rs.gbif.org/terms/1.0/description"        => "document",
                                  "http://rs.gbif.org/terms/1.0/multimedia"         => "document",
                                  "http://eol.org/schema/reference/reference"       => "reference",
                                  );

                                  /*
                                  [1] => http://rs.gbif.org/terms/1.0/speciesprofile
                                  [6] => http://rs.gbif.org/terms/1.0/typesandspecimen
                                  [7] => http://rs.gbif.org/terms/1.0/distribution
                                  */
    
        if($this->resource_id == 24) {
            $this->taxon_ids = array();
        }
    }

    private function start($dwca_file = false)
    {
        if($dwca_file) $this->dwca_file = $dwca_file; //used by /conncectors/lifedesk_eol_export.php
        
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => true)); //true means it will re-download, will not use cache. Set TRUE when developing
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    
    function count_records_in_dwca()
    {
        if(!($info = self::start())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        $totals = array();
        foreach($index as $row_type) {
            $count = self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type], false); //3rd param = false means count only, no archive will be generated
            $totals[$row_type] = $count;
        }
        print_r($totals);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
    }
    
    function convert_archive() //same as convert_archive_by_adding_higherClassification(); just doesn't generate higherClassification
    {
        echo "\nConverting archive to EOL DwCA...\n";
        $info = self::start();
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        /*
        Array
            [0] => http://rs.tdwg.org/dwc/terms/taxon
            [1] => http://rs.gbif.org/terms/1.0/vernacularname
            [2] => http://rs.tdwg.org/dwc/terms/occurrence
            [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
        */
        
        foreach($index as $row_type) {
            if(@$this->extensions[$row_type]) { //process only defined row_types
                // if(@$this->extensions[$row_type] == 'document') continue; //debug only
                echo "\nprocessed: [$row_type]: ".@$this->extensions[$row_type]."\n";
                self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
            }
            else echo "\nun-processed: [$row_type]: ".@$this->extensions[$row_type]."\n";
        }
        
        // /* ================================= start of customization =================================
        if($this->resource_id == 24)
        {
            require_library('connectors/AntWebDataAPI');
            $func = new AntWebDataAPI($this->taxon_ids, $this->archive_builder);
            $func->start($harvester, 'http://rs.tdwg.org/dwc/terms/taxon');
        }
        // ================================= end of customization ================================= */ 
        
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }
    function convert_archive_files($lifedesks) //used by: connectors/lifedesk_eol_export.php
    {
        foreach($lifedesks as $ld) //e.g. $ld = "LD_afrotropicalbirds" or "LD_afrotropicalbirds_multimedia"
        {
            $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.$ld.".tar.gz";
            // $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/".$ld.".tar.gz";
            echo "\nConverting multiple DwCA files [$ld] into one final DwCA...\n";
            $info = self::start($dwca_file);
            $temp_dir = $info['temp_dir'];
            $harvester = $info['harvester'];
            $tables = $info['tables'];
            $index = $info['index'];
            /*
            Array
                [0] => http://rs.tdwg.org/dwc/terms/taxon
                [1] => http://rs.gbif.org/terms/1.0/vernacularname
                [2] => http://rs.tdwg.org/dwc/terms/occurrence
                [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
            */
            foreach($index as $row_type) {
                if(@$this->extensions[$row_type]) { //process only defined row_types
                    // if(@$this->extensions[$row_type] == 'document') continue; //debug only
                    echo "\nprocessed: [$ld][$row_type]: ".@$this->extensions[$row_type]."\n";

                    /* good debug; debug only
                    if($ld == "LD_afrotropicalbirds") {
                        if($row_type == "http://rs.tdwg.org/dwc/terms/taxon") {
                            print_r($harvester->process_row_type($row_type));
                            // exit;
                        }
                    }
                    */
                    
                    self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
                }
                else echo "\nun-processed: [$row_type]: ".@$this->extensions[$row_type]."\n";
            }
            // remove temp dir
            recursive_rmdir($temp_dir); echo ("\n temporary directory removed: " . $temp_dir);
        } //end foreach()

        $this->archive_builder->finalize(TRUE);
        // if($this->debug) print_r($this->debug); //to limit lines of output
    }
    
    function convert_archive_by_adding_higherClassification()
    {
        echo "\ndoing this: convert_archive_by_adding_higherClassification()\n";
        $info = self::start();
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        $records = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');
        if(self::can_compute_higherClassification($records)) {
            echo "\n1 of 3\n";  self::build_id_name_array($records);
            echo "\n2 of 3\n";  $records = self::generate_higherClassification_field($records);
            /*
            Array
                [0] => http://rs.tdwg.org/dwc/terms/taxon
                [1] => http://rs.gbif.org/terms/1.0/vernacularname
                [2] => http://rs.tdwg.org/dwc/terms/occurrence
                [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
            */
            echo "\n3 of 3\n";
            foreach($index as $row_type) {
                if(@$this->extensions[$row_type]) { //process only defined row_types
                    if($this->extensions[$row_type] == "taxon") self::process_fields($records, $this->extensions[$row_type]);
                    else                                        self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
                }
            }
            $this->archive_builder->finalize(TRUE);
        }
        else echo "\nCannot compute higherClassification.\n";
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }

    function convert_archive_normalized() //this same as above two, but this removes taxa that don't have objects. Only taxa with objects will remain in taxon.tab.
    {
        echo "\ndoing this: convert_archive_normalized()\n";
        $info = self::start();
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        if($records = $harvester->process_row_type('http://eol.org/schema/media/Document'))
        {
            $taxon_ids_with_objects = self::build_taxonIDs_with_objects_array($records);        echo "\n1 of 3\n";
            $records = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');      echo "\n2 of 3\n";
            $records = self::remove_taxa_without_objects($records, $taxon_ids_with_objects);    echo "\n3 of 3\n";
            foreach($index as $row_type) {
                if(@$this->extensions[$row_type]) { //process only defined row_types
                    if($this->extensions[$row_type] == "taxon") self::process_fields($records, $this->extensions[$row_type]);
                    else                                        self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
                }
            }
            $this->archive_builder->finalize(TRUE);
        }
        else {
            echo "\nNo data objects for this resource [$this->resource_id].\n";
            recursive_rmdir($this->path_to_archive_directory);
        }
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }


    private function process_fields($records, $class, $generateArchive = true)
    {
        //start used in validation
        $do_ids = array();
        $taxon_ids = array();
        $ref_ids = array();
        //end used in validation
        $count = 0;
        foreach($records as $rec)
        {
            $count++;
            if    ($class == "vernacular")  $c = new \eol_schema\VernacularName();
            elseif($class == "agent")       $c = new \eol_schema\Agent();
            elseif($class == "reference")   $c = new \eol_schema\Reference();
            elseif($class == "taxon")       $c = new \eol_schema\Taxon();
            elseif($class == "document")    $c = new \eol_schema\MediaResource();
            elseif($class == "occurrence")  $c = new \eol_schema\Occurrence();
            elseif($class == "measurementorfact")   $c = new \eol_schema\MeasurementOrFact();
            
            // if($class == "taxon") print_r($rec);
            
            $keys = array_keys($rec);
            foreach($keys as $key)
            {
                $temp = pathinfo($key);
                $field = $temp["basename"];

                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];

                //#################### start some validations ---------------------------- put other validations in this block, as needed ################################################
                if($class == "reference") {
                    if($field == "full_reference" && !@$rec[$key] && $field == "title" && !@$rec[$key]) { //meaning full_reference AND title are blank or null
                        $c = false; break;
                    }
                }
                // some reference from DwCA: http://depot.globalbioticinteractions.org/release/org/eol/eol-globi-datasets/0.5/eol-globi-datasets-0.5-darwin-core-aggregated.tar.gz
                // <field index="0" term="http://purl.org/dc/terms/identifier"/>
                // <field index="1" term="http://eol.org/schema/reference/publicationType"/>
                // <field index="2" term="http://eol.org/schema/reference/full_reference"/>
                // <field index="3" term="http://eol.org/schema/reference/primaryTitle"/>
                // <field index="4" term="http://purl.org/dc/terms/title"/>
                
                if(in_array($field, array("accessURI","thumbnailURL","furtherInformationURL"))) {
                    if($val = @$rec[$key]) { //if not blank
                        if(!self::valid_uri_url($val)) { //then should be valid URI or URL
                            // $c = false; break; //you don't totally exclude the entire data_object but just set the field URI/URL to blank
                            $rec[$key] = "";
                            print_r($rec);
                            echo "\nURI/URL set to blank because it is invalid.\n";
                        }
                    }
                }
                
                // not been tested yet. Was working with dwca_utility.php _ 430 -> iNaturalist
                /* should work
                if($class == "document") { //meaning media objecs ---> filter out duplicate data_object identifiers
                    if($field == "identifier") {
                        $do_id = @$rec[$key];
                        if(isset($do_ids[$do_id])) {
                            $c = false; break; //exclude entire data_object entry if id already exists
                        }
                        else $do_ids[$do_id] = '';
                    }
                }
                */

                /* Need to have unique taxon ids. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have already unique taxon ids.
                Useful for e.g. DATA-1724 resource 'plant_forms_habitat_and_distribution'.
                */
                if(in_array($this->resource_id, array('plant_forms_habitat_and_distribution-adjusted')) || in_array(substr($this->resource_id,0,3), array('LD_', 'EOL'))) {
                    if($class == "taxon") {
                        if($field == "taxonID") {
                            $taxon_id = @$rec[$key];
                            if(isset($this->taxon_ids[$taxon_id])) {
                                $this->debug['duplicate_taxon_ids'][$taxon_id] = '';
                                $c = false; break; //exclude entire taxon entry if id already exists
                            }
                            else $this->taxon_ids[$taxon_id] = '';
                        }
                    }
                }

                /* Need to have unique reference ids. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have already unique ref ids.
                Useful for e.g. DATA-1724 resource 'plant_forms_habitat_and_distribution'.
                */
                /*
                Also used for: https://eol-jira.bibalex.org/browse/DATA-1733 --> Shelled_animal_body_mass, added this resource bec. it doesn't have unique ref ids.
                */
                if(in_array($this->resource_id, array('plant_forms_habitat_and_distribution-adjusted', 'Shelled_animal_body_mass-adjusted'))) {
                    if($class == "reference") {
                        if($field == "identifier") {
                            $identifier = @$rec[$key];
                            if(isset($ref_ids[$identifier])) {
                                $this->debug['duplicate_ref_ids'][$identifier] = '';
                                $c = false; break; //exclude entire reference entry if id already exists
                            }
                            else $ref_ids[$identifier] = '';
                        }
                    }
                }
                
                /* measurementType must have value. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have non-null measurementType.
                Useful for e.g. https://eol-jira.bibalex.org/browse/DATA-1733 - 'Shelled_animal_body_mass'
                */
                if(in_array($this->resource_id, array('Shelled_animal_body_mass-adjusted'))) {
                    if($class == "measurementorfact") {
                        if($field == "measurementType" && !@$rec[$key]) { //meaning measurementType is blank or null, then exclude entire row.
                            $c = false; break;
                        }
                    }
                }
                
                /* Need to have unique agent ids. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have already unique ref ids.
                First used for DATA-1569 resource 'lifedesks.tar.gz', connector [lifedesk_eol_export.php]
                */
                if(in_array($this->resource_id, array('lifedesks')) || in_array(substr($this->resource_id,0,3), array('LD_', 'EOL'))) {
                    if($class == "agent") {
                        if($field == "identifier") {
                            $identifier = @$rec[$key];
                            if(isset($this->agent_ids[$identifier])) {
                                $this->debug['duplicate_agent_ids'][$identifier] = '';
                                $c = false; break; //exclude entire agent entry if id already exists
                            }
                            else $this->agent_ids[$identifier] = '';
                        }
                    }
                }
                
                
                //#################### end some validations ----------------------------  #########################################################################

                $c->$field = $rec[$key];

                // if($field == "taxonID") $c->$field = self::get_worms_taxon_id($c->$field); //not used here, only in WoRMS connector
            }//end loop foreach()
            if($generateArchive) {
                if($c) {
                    $this->archive_builder->write_object_to_file($c); //to facilitate validations
                    
                    //start customization here ========================================
                    if($this->resource_id == 24) {
                        if($class == "taxon") {
                            $this->taxon_ids[$c->taxonID] = '';
                            // print_r($c); exit;
                        }
                    }
                    //end customization here ========================================
                }
            }
        } //main loop
        return $count;
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
    {   /* e.g. $rec
        Array
            [http://rs.tdwg.org/dwc/terms/taxonID] => 5e2712849c197671c260f53809836273
            [http://rs.tdwg.org/dwc/terms/scientificName] => Passerina leclancherii leclancherii Lafresnaye, 1840
            [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 49fc924007e33cc43908fed677d5499a
        */
        $i = 0;
        foreach($records as $rec)
        {
            $higherClassification = self::get_higherClassification($rec);
            $records[$i]["higherClassification"] = $higherClassification; //assign value to main $records -> UNCOMMENT in real operation
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
                $str .= Functions::canonical_form(trim(@$this->id_name[$parent_id]['scientificName']))."|";
                $parent_id = @$this->id_name[$parent_id]['parentNameUsageID'];
            }
        }
        $str = substr($str, 0, strlen($str)-1);
        // echo "\norig: [$str]";
        $arr = explode("|", $str);
        $arr = array_reverse($arr);
        $str = implode("|", $arr);
        // echo "\n new: [$str]\n";
        return $str;
    }

    private function can_compute_higherClassification($records)
    {
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/taxonID"])) return false;
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/scientificName"])) return false;
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/parentNameUsageID"])) return false;
        return true;
    }
    private function valid_uri_url($str)
    {
        if(substr($str,0,7) == "http://") return true;
        elseif(substr($str,0,8) == "https://") return true;
        return false;
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
    
    //=====================================================================================================================
    //start functions for the interface tool "genHigherClass"
    //=====================================================================================================================
    
    function tool_generate_higherClassification($file)
    {
        if($records = self::create_records_array($file))
        {
            self::build_id_name_array($records);                                //echo "\n1 of 3\n";
            $records = self::generate_higherClassification_field($records);     //echo "\n2 of 3\n";
            $fields = self::normalize_fields($records[0]);

            //start write to file
            // $file = str_replace(".", "_higherClassification.", $file);
            if(!($f = Functions::file_open($file, "w"))) return;
            fwrite($f, implode("\t", $fields)."\n");
            foreach($records as $rec) fwrite($f, implode("\t", $rec)."\n");
            fclose($f);
            // echo "\n3 of 3\n";
            return true;
        }
        else return false;
    }
    
    private function create_records_array($file)
    {
        $records = array();
        $i = 0;
        foreach(new FileIterator($file) as $line => $row)
        {
            $i++;
            if($i == 1)
            {
                $fields = explode("\t", $row);
                $k = 0;
                foreach($fields as $field) //replace it with the long field URI
                {
                    if($field == "taxonID") $fields[$k] = "http://rs.tdwg.org/dwc/terms/taxonID";
                    elseif($field == "scientificName") $fields[$k] = "http://rs.tdwg.org/dwc/terms/scientificName";
                    elseif($field == "parentNameUsageID") $fields[$k] = "http://rs.tdwg.org/dwc/terms/parentNameUsageID";
                    $k++;
                }
            }
            else
            {
                $rec = array();
                $cols = explode("\t", $row);
                $k = 0;
                foreach($fields as $field)
                {
                    $rec[$field] = @$cols[$k];
                    $k++;
                }
                if($rec)
                {
                    if($i == 3) //can check this early if we can compute for higherClassification
                    {
                        if(!self::can_compute_higherClassification($records)) return false;
                    }
                    $records[] = $rec;
                }
            }
        }
        return $records;
    }
    
    private function normalize_fields($arr)
    {
        $fields = array_keys($arr);
        $k = 0;
        foreach($fields as $field)
        {
            $fields[$k] = pathinfo($field, PATHINFO_FILENAME);
            $k++;
        }
        return $fields;
    }
    //=====================================================================================================================
    //end functions for the interface tool "genHigherClass"
    //=====================================================================================================================

    // these 2 functions used in convert_archive_normalized()
    private function build_taxonIDs_with_objects_array($records)
    {
        $taxon_ids = array();
        foreach($records as $rec) {
            $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $taxon_ids[$taxon_id] = '';
        }
        return array_keys($taxon_ids);
    }
    private function remove_taxa_without_objects($records, $taxon_ids_with_objects)
    {
        echo "\n start taxa count: ".count($records);
        $i = -1;
        foreach($records as $rec) {
            $i++;
            $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $taxon_status = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
            if(!in_array($taxon_id, $taxon_ids_with_objects) && !in_array($taxon_status, array('synonym'))) $records[$i] = null;
        }
        $records = array_filter($records); //remove null arrays
        $records = array_values($records); //reindex key
        echo "\n end taxa count: ".count($records);
        return $records;
    }

    //=====================================================================================================================
    //start OTHER functions
    //=====================================================================================================================
    function get_uri_value($raw, $uri_values) //$raw e.g. "Philippines" ---- good func but not yet used, soon...
    {
        if($uri = @$uri_values[$raw]) return $uri;
        else {
            switch ($raw) { //put here customized mapping
                case "United States of America":    return "http://www.wikidata.org/entity/Q30";
                case "Port of Entry":               return false; //"DO NOT USE"
            }
        }
        return false;
    }
    
    //=====================================================================================================================
    //end OTHER functions
    //=====================================================================================================================

}
?>