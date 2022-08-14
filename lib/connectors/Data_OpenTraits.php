<?php
namespace php_active_record;
/* data_4opentraits.php */
class Data_OpenTraits
{
    function __construct()
    {   //60*60*24 orig expire_seconds
        $this->download_options = array('resource_id' => 'opendata', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->opendata_api['tag taxonomic inference'] = 'https://opendata.eol.org/api/3/action/package_search?q=tags:%22taxonomic%20inference%22+license_id:%22notspecified%22+organization:%22eol-content-partners%22&start=START_NUM&rows=ROWS_PER_CALL&&sort=score+desc%2C+metadata_modified+desc';
        $this->opendata_page['package_id'] = 'https://opendata.eol.org/dataset/';
        // https://opendata.eol.org/dataset/owens-and-lewis-2018
        // https://opendata.eol.org/dataset/mcdermott-1964
        if(Functions::is_production()) $this->report_dir = "/extra/other_files/temp/";
        else                           $this->report_dir = "/Volumes/AKiTiO4/other_files/temp/";
        $this->filename = "data_4_opentraits.txt";
        /* https://opendata.eol.org/dataset/marine-ecology-literature -> needs only 1 resource from this dataset
        $this->exclude_resourced_IDs = array();
        */
        $this->DH_info = false;
    }
    private function get_nearest_common_ancester($EOLids)
    {
        $i = 0;
        foreach($EOLids as $eol_id) { $i++;
            echo "\neol_id: [$eol_id]\n";
            if($hc = self::lookup_DH($eol_id)) {
                echo("\n$eol_id - $hc\n");
                if($i >= 3) break;
            }
            exit("stopx");
            exit("\n no hit \n");
        }
    }
    private function lookup_DH($eol_id = false)
    {
        $dwca_url = 'http://localhost/other_files/DH/dhv21hc.zip';
        if(!$this->DH_info) $this->DH_info = self::extract_dwca($dwca_url, $this->download_options, "DH"); // print_r($this->DH_info);
        $tables = $this->DH_info['harvester']->tables; // print_r(array_keys($tables));
        $rowtype = "http://rs.tdwg.org/dwc/terms/taxon";
        if($eol_id) {
            echo "\npassed 1\n";
            $ret = self::process_table($tables[$rowtype][0], pathinfo($rowtype, PATHINFO_BASENAME)."_DH", $eol_id);
            return $ret;
        }
        // exit("\nexit 1\n");
        
    }
    function start()
    {
        self::lookup_DH(); //initialize DH access
        
        $start_num = 0;
        while(true) {
            $url = $this->opendata_api['tag taxonomic inference'];
            $url = str_replace("START_NUM", $start_num, $url);
            $url = str_replace("ROWS_PER_CALL", 50, $url);
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $obj = json_decode($json);
                // print_r($obj); exit;
                print("\nTotal: ".$obj->result->count);
                $current = count($obj->result->results);
                print("\nCurrent: ".$current);
                
                $i = 0;
                foreach($obj->result->results as $rec) { $i++;
                    // print(" ".count($rec->resources));
                    if(count($rec->resources) == 1) {
                        // print("\n $i. ".$rec->resources[0]->name."\n"); # resource name
                        $rek = self::get_rec_metadata($rec);
                        self::process_rec($rec, $i); // generates $this->batch
                        $rek = self::format_DwCA_data(); // will use $this->batch
                        
                    }
                    else {
                        print_r($rec);
                        exit("\nInvestigate: more than one resource in dataset\n");
                    }
                    exit("\nprocessed 1 dwca...\n");
                    
                }
                
                if($current < 50) break;
            }
            else break;
            $start_num += 50;
        }

        exit("\nstop muna\n");
        
        /* copied template
        if($json = Functions::lookup_with_cache($this->opendata_api['tag taxonomic inference'], $this->download_options)) {
            $obj = json_decode($json); //print_r($obj);
            $i = 0; $count = 0;
            foreach($obj->result->results as $rec) { //loop all resources with tags = 'taxonomic inference'
                // print_r($rec); exit("\n001\n");
                // print_r($rec->tags); exit;
                if(@$rec->tags{0}->name == 'taxonomic inference') { $count++;
                    self::process_rec($rec, $count);
                    $i++;
                    // if($i > 5) break; //debug only
                }
            }
            echo "\nResources: [$i]\n";
            // print_r($this->package); echo " - package"; //exit("exit 2"); //good debug
        }
        */
        /* assemble data then print */
        /* copied template
        foreach($this->package as $package_id => $ids) {
            foreach(array_keys($ids) as $id) $final[$id][] = $package_id;
        }
        asort($final);      echo "\n1 ".count($final)."\n";
        ksort($final);      echo "\n2 ".count($final)."\n";
        
        $f = Functions::file_open($this->report_dir.$this->filename, "w");
        fwrite($f, "EOLid"."\t"."Datasets"."\n");
        foreach($final as $taxonID => $datasets) {
            fwrite($f, $taxonID."\t".implode(", ", $datasets)."\n");
        }
        fclose($f);
        print_r($this->debug);
        */
    }
    private function get_rec_metadata($rec)
    {
        print("\n".$rec->name."\n");
        // print_r($rec); #exit;
        // [1]- 
        // [4]- Find in description: any doi. I think the string to look for is "doi.org", and bound the string by spaces, lopping off any trailing "."
        // [5]- Resource file url (to the file download; we won't need the resource page url)
        $ret['Dataset_name'] = $rec->title; #$rec->name;
        $ret['Dataset_url'] = $this->opendata_page['package_id'].$rec->name; #https://opendata.eol.org/dataset/marine-ecology-literature
        $ret['Dataset_desc'] = $rec->notes;
        $ret['DOI'] = self::get_doi_from_notes($rec);
        $ret['Resource_file'] = $rec->resources[0]->url;
        print_r($ret); #exit;
        return $ret;
    }
    private function get_doi_from_notes($rec)
    {
        $notes = $rec->notes;
        # $notes = "Triblehorn, J. D., & Yager, D. D. (2001). Broad versus narrow auditory tuning and corresponding bat-evasive flight 
        # behaviour in praying mantids. Journal of Zoology, 254(1), 27–40.  https://doi.org/10.1017/S095283690100053X";
        // $notes = "eli is here.";
        
        if(stripos($notes, "//doi.org/") !== false) {} //string is found
        else return "";
        
        $start_pos = strpos($notes, "//doi.org/");
        $i = $start_pos;
        $final = "";
        if($start_pos >= 0) {
            while(true) {
                $char = substr($notes, $i, 1);
                if($char == " ") break;
                if($char == "") break;
                $final .= $char;
                $i++;
            }
        }
        $final = self::remove_last_char_if_period($final);
        // echo "\nstart pos: [$start_pos]\n"; echo "\nDOI: [$final]\n";
        return "https:".$final;
    }
    private function remove_last_char_if_period($str)
    {
        $last_char = substr($str, -1); // returns last char of string
        if(in_array($last_char, array(".", '"'))) $str = substr($str, 0, -1); // remove last char of string
        return $str;
    }
    private function format_DwCA_data()
    {
        /* [7]- canonical|names|of|all|taxa|in|the|taxa|file If there are 2-10 of them (so, discard this if there's only one, or >10) */
        $canonicals = array_keys($this->batch['canonicals']);
        $canonicals = array_map('trim', $canonicals);
        asort($canonicals);
        // print_r($canonicals);
        $total = count($canonicals);
        if($total >= 2 && $total <= 10) $final['canonicals'] = implode("|", $canonicals);
        else $final['canonicals'] = "";
        
        /* [6]- Nearest common ancestor for all taxa in the taxa file */
        $EOLids = array_keys($this->batch['EOLids']);
        $EOLids = array_map('trim', $EOLids);
        asort($EOLids);
        print_r($EOLids);
        echo "\nEOLids: ".count($EOLids)."\n";
        echo "\ncanonicals: ".count($canonicals)."\n";
        // exit;
        $final['nearest common ancestor'] = self::get_nearest_common_ancester($EOLids);
        
        /* [8]- deduplicated, term names for all measurementType terms that appear in rows where measurementOfTaxon=true  */
        $mTypes = array_keys($this->batch['mType']);
        $arr = array();
        foreach($mTypes as $mType) $arr[] = pathinfo($mType, PATHINFO_BASENAME);
        asort($arr);
        print_r($arr);
        $final['mTypes'] = implode("|", $arr);
        
        print_r($final);
    }
    # =================================== ends here. Below are copied templates ===================================
    
    private function process_rec($rec, $count)
    {   // print_r($rec); exit("\nrec struct:\n");
        /* 
        [num_resources] => 1
        [name] => lewis-and-taylor-1965
        */
        
        // if(in_array($rec->name, array('mineralogy', 'marine-ecology-literature'))) {}
        // else {
            if(count($rec->resources) > 1) { //print_r($rec);
                $this->debug['More than one resources'][$rec->name] = '';
                // exit("\nMore than one resources?\n");
            }
        // }
        
        foreach($rec->resources as $resource) self::process_resource($resource, $rec->name, count($rec->resources), $count);
    }
    private function process_resource($res, $dataset_name, $resources_count, $count)
    {   // print_r($res); exit("\nresource struct:\n");
        /*stdClass Object(
            [mimetype] => 
            [cache_url] => 
            [hash] => 
            [description] => 
            [name] => marine ecology lit v5
            [format] => ZIP
            [url] => https://opendata.eol.org/dataset/86081133-3db1-4ffc-8b1f-2bbba1d1f948/resource/e56f7eff-6b71-4f92-92e1-558a82d55df8/download/archive.zip
            [cache_last_updated] => 
            [package_id] => 86081133-3db1-4ffc-8b1f-2bbba1d1f948
            [created] => 2020-08-26T20:57:48.155974
            [state] => active
            [mimetype_inner] => 
            [webstore_last_updated] => 
            [last_modified] => 2021-04-19T15:39:23.568261
            [position] => 0
            [revision_id] => 751d1c8c-13ce-4a07-8836-52c9075bf49a
            [webstore_url] => 
            [url_type] => upload
            [id] => e56f7eff-6b71-4f92-92e1-558a82d55df8
            [resource_type] => 
            [size] => 
        )*/
        
        /* copied template
        if(in_array($res->id, $this->exclude_resourced_IDs)) return;
        */
        
        echo "\nProcessing [$count]. ".$dataset_name." -> ".$res->name."...\n";
        $this->batch = array();
        $ext = pathinfo($res->url, PATHINFO_EXTENSION);
        if(in_array($ext, array('zip', 'gz'))) self::process_dwca($res->url);
        else exit("Investigate resource file: [$res->url]");

        /* copied template
        if($resources_count == 1)       $id_to_use = $dataset_name;
        elseif($resources_count > 1)    $id_to_use = $dataset_name."/resource/".$res->id;
        else exit("\nNo resources!\n");
        $this->package[$id_to_use] = $this->batch;
        */
        
        // print_r($this->batch); exit("\n-exit muna-\n");
    }
    private function process_dwca($dwca_url)
    {
        $info = self::extract_dwca($dwca_url, $this->download_options, "regular");
        // print_r($info); exit("\nexit 1\n");
        $tables = $info['harvester']->tables;
        // print_r(array_keys($tables));
        $rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.tdwg.org/dwc/terms/measurementorfact'); //normal operation
        // $rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact'); //debug only
        foreach($rowtypes as $rowtype) self::process_table($tables[$rowtype][0], pathinfo($rowtype, PATHINFO_BASENAME), false);
        // recursive_rmdir($info['temp_dir']); //remove temp folder
    }
    private function process_table($meta, $rowtype, $eol_id = false)
    {   //print_r($meta); exit;
        echo "\nprocess_table...[$meta->file_uri]\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = @$tmp[$k];
                $k++;
            } // print_r($rec); exit("\nstop muna\n");
            $rec = array_map('trim', $rec);
            #=====================================================================================
            if($rowtype == "measurementorfact") {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/measurementID] => 0
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => 46531931
                    [http://eol.org/schema/measurementOfTaxon] => TRUE
                    [http://eol.org/schema/parentMeasurementID] => 
                    [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/TrophicGuild
                    [http://rs.tdwg.org/dwc/terms/measurementValue] => http://eol.org/schema/terms/phytoplanktivore
                    [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                    [http://eol.org/schema/terms/statisticalMethod] => 
                    [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                    [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                    [http://purl.org/dc/terms/bibliographicCitation] => Takeshi Naganuma. 1996. Canoid copepods: linking lower-higher trophic levels by linking lower-higher Reynolds numbers. MARINE ECOLOGY PROGRESS SERIES, 99: 311-313
                    [http://purl.org/dc/terms/source] => https://www.int-res.com/articles/meps/136/m136p311.pdf
                    [http://eol.org/schema/reference/referenceID] => 
                )*/
                $mType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
                $mValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
                $measurementOfTaxon = $rec['http://eol.org/schema/measurementOfTaxon'];
                if(strtolower($measurementOfTaxon) == 'true') $this->batch['mType'][$mType] = '';
                else $this->debug['other mType values'][$mType] = '';
            }
            #=====================================================================================
            elseif($rowtype == "taxon") {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/taxonID] => 46531931
                    [http://rs.tdwg.org/dwc/terms/scientificName] => Calanoida
                    [http://rs.tdwg.org/dwc/terms/kingdom] => 
                    [http://rs.tdwg.org/dwc/terms/phylum] => 
                    [http://eol.org/schema/EOLid] => 46531931
                )*/
                $scientificName = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
                    
                $this->batch['canonicals'][$scientificName] = '';
                
                $EOLid = $rec['http://eol.org/schema/EOLid'];
                if($EOLid) $this->batch['EOLids'][$EOLid] = '';
            }
            #=====================================================================================
            elseif($rowtype == "taxon_DH") {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/taxonID] => EOL-000000000001
                    [http://purl.org/dc/terms/source] => trunk:4038af35-41da-469e-8806-40e60241bb58
                    [http://rs.tdwg.org/ac/terms/furtherInformationURL] => 
                    [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                    [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 
                    [http://rs.tdwg.org/dwc/terms/scientificName] => Life
                    [http://rs.tdwg.org/dwc/terms/taxonRank] => 
                    [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted
                    [http://rs.tdwg.org/dwc/terms/datasetID] => trunk
                    [http://rs.gbif.org/terms/1.0/canonicalName] => Life
                    [http://eol.org/schema/EOLid] => 2913056
                    [http://eol.org/schema/Landmark] => 3
                    [http://rs.tdwg.org/dwc/terms/higherClassification] => 
                )*/
                // print_r($rec); exit("\nstop muna\n");
                // echo "\npassed 3\n";
                $EOLid = $rec['http://eol.org/schema/EOLid'];
                $hc = $rec['http://rs.tdwg.org/dwc/terms/higherClassification'];
                if($eol_id == $EOLid) return $hc;
            }
            #=====================================================================================
        }
    }
    private function extract_dwca($dwca_file = false, $download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1), $type = "regular") //default expires in 1 day 60*60*24*1. Not false.
    {
        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        print_r($paths); exit("\n-exit muna-\n");
        */

        // /* development only
        if($type == "regular") {
            $paths = Array(
                'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_09600/',
                'temp_dir'     => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_09600/'
            );
        }
        elseif($type == "DH") {
            $paths = Array(
                'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_55799/',
                'temp_dir'     => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_55799/'
            );
        }
        // */
        
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    /* copied template but seems not used ?
    private function format_title($str)
    {
        if($str == "Queirós et al, 2013") $str = "queiros-et-al-2013";
        $str = strtolower($str);
        $str = str_replace(" ", "-", $str);
        $str = str_replace(array(","), "", $str);
        return $str;
    }
    */
}
?>