<?php
namespace php_active_record;
/* data_4opentraits.php */
class Data_OpenTraits
{
    function __construct()
    {   //60*60*24 orig expire_seconds
        $this->download_options = array('resource_id' => 'opendata', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->opendata_api['tag taxonomic inference'] = 'https://opendata.eol.org/api/3/action/package_search?q=tags:%22taxonomic%20inference%22+license_id:%22notspecified%22+organization:%22eol-content-partners%22&start=START_NUM&rows=ROWS_PER_CALL&&sort=metadata_modified+desc';
        $this->opendata_page['package_id'] = 'https://opendata.eol.org/dataset/';
        // https://opendata.eol.org/dataset/owens-and-lewis-2018
        // https://opendata.eol.org/dataset/mcdermott-1964
        
        if(Functions::is_production()) $this->report_dir =           "/extra/other_files/temp/";
        else                           $this->report_dir = "/Volumes/AKiTiO4/other_files/temp/";

        if(Functions::is_production()) $this->save_dir =           "/extra/other_files/OpenTraits/";
        else                           $this->save_dir = "/Volumes/AKiTiO4/other_files/OpenTraits/";
        if(!is_dir($this->save_dir)) mkdir($this->save_dir);

        /* the func save_higherClassifaction_as_cache() was discontinued.
        if(Functions::is_production()) $this->cache_dir = "/extra/OpenTraits_cache/";
        else                           $this->cache_dir = "/Volumes/AKiTiO4/OpenTraits_cache/";
        if(!is_dir($this->cache_dir)) mkdir($this->cache_dir);
        */
        
        $this->filename = "data_4_opentraits.txt";
        $this->filename = "data_4_opentraits_".date("Y_m_d_H").".txt";
        $this->filename = "data_4_opentraits_".date("Y_m_d_H-i-s").".txt";
        
        /* https://opendata.eol.org/dataset/marine-ecology-literature -> needs only 1 resource from this dataset
        $this->exclude_resourced_IDs = array();
        */
        $this->DH_info = false;
        /* sample of EOLid not found in DH, but searching for sciname = "Tiphobia" is found in DH.
        grep "49315902" taxon.tab --> not found in DH
        grep "Tiphobia" taxon.tab --> found in DH
        EOL-000000818797	MOL:Tiphobia			EOL-000000818692	Tiphobia E. A. Smith, 1880	genus	accepted	MOL	Tiphobia	52589339	
        	Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Mollusca|Gastropoda|Caenogastropoda|Cerithioidea|Paludomidae
     {"hc":"Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Mollusca|Gastropoda|Caenogastropoda|Cerithioidea|Paludomidae"}
     -> succesfully picked-up the hc and assigned in our system.
        */
    }
    function process_pipe_delim_values($hc) // works OK!
    {
        $arrays = array();
        foreach($hc as $eol_id => $str) { // echo "\n[$str]";
            $arrays[] = explode("|", $str);
        }
        
        if(count($arrays) == 1) {  // means there is only 1 taxa in the taxa file with EOLid.
            echo "\nmeans there is only 1 taxa in the taxa file with EOLid.\n";
            print_r($hc);
            print_r($arrays);
            return end($arrays[0]);
        }
        print_r($arrays);

        $result = array();
        if($arrays) {
            $array1 = $arrays[0];
            for($i = 1; $i <= count($arrays)-1; $i++) { // echo "\n[$i]";
                $array2 = $arrays[$i];
                $result = array_intersect($array1, $array2);
                $array1 = $result; // ready for next loop
            }
        }

        echo "\nFinal result:\n";
        if($result) {
            print_r($result);
            echo "\nnearest common ancestor: [".end($result)."]\n";
            return end($result);
        }
        else echo "\nNo intersection at this point.\n"; // may not reach this point.
    }
    private function get_nearest_common_ancester($EOLids)
    {
        # step 1: get all pipe-delimited higherclassification
        $hc = array(); $i = 0;
        foreach($EOLids as $eol_id) { $i++;
            echo "\neol_id: [$eol_id] $i of ".count($EOLids)."\n";
            if($pipe_delimited = self::lookup_DH($eol_id)) {
                echo("\n$eol_id - $pipe_delimited\n");
                $hc[$eol_id] = $pipe_delimited;
                // if($i >= 2) break; // debug only, during dev only
            }
            else { // $eol_id doesn't exist, let us try to search the sciname
                if($sciname = @$this->info_EOLid_sciname[$eol_id]) {

                    // if($pipe_delimited = self::lookup_DH_sciname($eol_id, $sciname, false)) { # orig, works but too slow
                    if($pipe_delimited = @$this->eolID_hc[$eol_id]) {
                        
                        // /* copied template
                        echo("\n$eol_id [$sciname] - $pipe_delimited\n");
                        $hc[$eol_id] = $pipe_delimited;
                        // */
                    }
                    
                }
            }
        }
        
        /*
        $hc[2] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Gnathifera|Syndermata";
        $hc[38] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Annelida|Pleistoannelida|Sedentaria|Clitellata|Hirudinea|Acanthobdellidea";
        $hc[46] = "Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Annelida|Pleistoannelida|Sedentaria|Clitellata";
        */
        # step 2:
        $nearest_common_ancester = self::process_pipe_delim_values($hc);

        // /* new:
        if(count($EOLids) == 1 && !$nearest_common_ancester) return "use sciname";
        // */

        return $nearest_common_ancester;
        // exit("stopx");
    }
    private function lookup_DH_build_info_list()
    {
        $tables = $this->DH_info['harvester']->tables;
        $rowtype = "http://rs.tdwg.org/dwc/terms/taxon";
        self::process_table($tables[$rowtype][0], pathinfo($rowtype, PATHINFO_BASENAME)."_BuildUp", false);
    }
    function lookup_DH($eol_id = false, $deleteFolder_YN = false)
    {
        $dwca_url = 'http://localhost/other_files/DH/dhv21hc.zip';
        if(!$this->DH_info) $this->DH_info = self::extract_dwca($dwca_url, $this->download_options, "DH"); // print_r($this->DH_info);
        $tables = $this->DH_info['harvester']->tables; // print_r(array_keys($tables));
        $rowtype = "http://rs.tdwg.org/dwc/terms/taxon";
        if($eol_id) {
            // echo "\npassed 1\n";
            
            /* not being used anymore
            $higherClassification = self::get_cache_higherClassification($eol_id);
            */
            $higherClassification = @$this->eolID_hc[$eol_id];
            return $higherClassification;
            
            /* not being used anymore
            if($higherClassification === false) {
                $higherClassification = self::process_table($tables[$rowtype][0], pathinfo($rowtype, PATHINFO_BASENAME)."_DH", $eol_id);
                self::save_2cache_higherClassification($eol_id, $higherClassification);
                return $higherClassification;
            }
            else return $higherClassification; // can be null or with real value
            */
        }
        // exit("\nexit 1\n");
        
        if($deleteFolder_YN) recursive_rmdir($this->DH_info['temp_dir']);
    }
    /* not being used anymore
    function lookup_DH_sciname($eol_id, $sciname, $deleteFolder_YN = false)
    {
        $dwca_url = 'http://localhost/other_files/DH/dhv21hc.zip';
        if(!$this->DH_info) $this->DH_info = self::extract_dwca($dwca_url, $this->download_options, "DH"); // print_r($this->DH_info);
        $tables = $this->DH_info['harvester']->tables; // print_r(array_keys($tables));
        $rowtype = "http://rs.tdwg.org/dwc/terms/taxon";
        if($eol_id) {
            
            $higherClassification = self::get_cache_higherClassification($eol_id);
            
            if($higherClassification === false) { // no cache file yet === SEEMS IT DOESN'T GO HERE
                exit("\nSEEMS IT DOESN'T GO HERE\n");
                // 
                // $higherClassification = self::process_table($tables[$rowtype][0], pathinfo($rowtype, PATHINFO_BASENAME)."_DH", $eol_id);
                // 
                $higherClassification = self::process_table($tables[$rowtype][0], pathinfo($rowtype, PATHINFO_BASENAME)."_DH", $sciname, "scientificName");
                self::save_2cache_higherClassification($eol_id, $higherClassification);
                return $higherClassification;
            }
            elseif($higherClassification == "null" || $higherClassification == "" || $higherClassification == null) {
                $higherClassification = self::process_table($tables[$rowtype][0], pathinfo($rowtype, PATHINFO_BASENAME)."_DH", $sciname, "scientificName");
                self::save_2cache_higherClassification($eol_id, $higherClassification);
                return $higherClassification;
            }
            else return $higherClassification; // can be null or with real value
        }
        // exit("\nexit 1\n");
        
        if($deleteFolder_YN) recursive_rmdir($this->DH_info['temp_dir']);
    }
    */
    private function get_cache_higherClassification($eol_id)
    {
        $file = $this->save_dir.$eol_id.".txt";
        if(file_exists($file)) {
            $json = file_get_contents($file);
            $arr = json_decode($json, true);
            echo "\nretrieved...[$eol_id] "; print_r($arr);
            // exit("\nretrieved hc\n");
            return $arr['hc'];
        }
        else return false;
    }
    private function save_2cache_higherClassification($eol_id, $higherClassification)
    {
        $file = $this->save_dir.$eol_id.".txt";
        $f = Functions::file_open($file, "w");
        $save = array("hc" => $higherClassification);
        fwrite($f, json_encode($save));
        fclose($f);
        echo "\nsaved...[$eol_id] "; print_r($save);
        // exit("\nsaved hc\n");
    }
    function start()
    {
        $f = Functions::file_open($this->report_dir.$this->filename, "w"); fclose($f); # initialize report text file
        self::lookup_DH(false); //initialize DH access
        // /* new build $this->eolID_hc[eol_id] = hc
        self::lookup_DH_build_info_list();
        // */
        
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
                foreach($obj->result->results as $rec) { $i++; $rek = array();
                    // print(" ".count($rec->resources));
                    if(count($rec->resources) == 1) { //with new CKAN this part is ToDo: will need to query all resources per dataset_id
                        // print("\n $i. ".$rec->resources[0]->name."\n"); # resource name
                        $rek['OpenData'] = self::get_rec_metadata($rec);
                        self::process_rec($rec, $i); // generates $this->batch
                        $rek['DwCA'] = self::format_DwCA_data(); // will use $this->batch
                        
                        // /*
                        if($rek['DwCA']['nearest common ancestor'] == 'use sciname') {
                            foreach($this->get_sciname_of_this_EOLid as $eol_id => $taxon) $rek['DwCA']['nearest common ancestor'] = $taxon;
                        }
                        // */
                        
                    }
                    else {
                        print_r($rec);
                        exit("\nInvestigate: more than one resource in dataset\n");
                    }
                    // print_r($rek);
                    self::write_2text_file($rek);
                    // exit("\nprocessed 1 dwca...\n");
                    
                }
                
                if($current < 50) break;
            }
            else break;
            $start_num += 50;
        }
        print_r($this->debug);
        self::lookup_DH(false, true); // 2nd param true means delete folder
        print("\n -- end report -- \n");
    }
    private function write_2text_file($rek)
    {   /*Array(
            [OpenData] => Array(
                    [Dataset_name] => marine ecology literature
                    [Dataset_url] => https://opendata.eol.org/dataset/marine-ecology-literature
                    [Dataset_desc] => A collection, from the literature, of traits relating to trophic guild, habitat and host relationships of marine invertebrates
                    [DOI] => 
                    [Resource_file] => https://opendata.eol.org/dataset/86081133-3db1-4ffc-8b1f-2bbba1d1f948/resource/e56f7eff-6b71-4f92-92e1-558a82d55df8/download/archive.zip
                )
            [DwCA] => Array(
                    [nearest common ancestor] => Spiralia
                    [canonicals] => 
                    [mTypes] => CMO_0000013|Diet|EcomorphologicalGuild|NCIT_C25513|Present|Q1053008|Q33596|RO_0002303|RO_0002454|RO_0002634|RO_0008503|TrophicGuild|burrowDepth|burrowDiameter
                )
        )*/
        $f = Functions::file_open($this->report_dir.$this->filename, "a");
        /*
        layout: dataset
        id: [1] converted to kebab-case (eg: Dunn et al, 2015 => dunn-et-al-2015)
        name: [1]
        contentURL: [5]
        datasetDOI_URL: [2]
        contactName: Jen Hammock
        contactEmail: secretariat@eol.org|jen.hammock@gmail.org
        license: CC0
        traitList: [8]
        higherGeography:
        decimalLatitude:
        decimalLongitude:
        taxon: [6]
        eventDate:
        paperDOIcitation: [4]
        description: [3]
        taxaList: [7]
        usefulClasses:
        dataStandard:
        standardizationScripts:
        webpage:
        */
        fwrite($f, "============================================================"."\n");
        fwrite($f, "layout: dataset"."\n");
        fwrite($f, "id: ".self::format_kebab_case($rek['OpenData']['Dataset_name'])."\n");
        fwrite($f, "name: ".$rek['OpenData']['Dataset_name']."\n");
        fwrite($f, "contentURL: ".$rek['OpenData']['Resource_file']."\n");
        fwrite($f, "datasetDOI_URL: ".$rek['OpenData']['Dataset_url']."\n");
        fwrite($f, "contactName: Jen Hammock"."\n");
        fwrite($f, "contactEmail: secretariat@eol.org|jen.hammock@gmail.org"."\n");
        fwrite($f, "license: CC0"."\n");
        fwrite($f, "traitList: ".$rek['DwCA']['mTypes']."\n");
        fwrite($f, "higherGeography:"."\n");
        fwrite($f, "decimalLatitude:"."\n");
        fwrite($f, "decimalLongitude:"."\n");
        fwrite($f, "taxon: ".$rek['DwCA']['nearest common ancestor']."\n");
        fwrite($f, "eventDate:"."\n");
        fwrite($f, "paperDOIcitation: ".$rek['OpenData']['DOI']."\n");
        fwrite($f, "description: ".$rek['OpenData']['Dataset_desc']."\n");
        fwrite($f, "taxaList: ".$rek['DwCA']['canonicals']."\n");
        fwrite($f, "usefulClasses:"."\n");
        fwrite($f, "dataStandard:"."\n");
        fwrite($f, "standardizationScripts:"."\n");
        fwrite($f, "webpage:"."\n");
        fclose($f);
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
        /* [6]- Nearest common ancestor for all taxa in the taxa file */
        $EOLids = array_keys($this->batch['EOLids']);
        $EOLids = array_map('trim', $EOLids);
        asort($EOLids);
        print_r($EOLids); //exit;
        
        // if(count($EOLids) == 272) {
        //     exit("\nelix...\n");
        // }
        
        echo "\nEOLids: ".count($EOLids)."\n";
        $final['nearest common ancestor'] = self::get_nearest_common_ancester($EOLids);

        /* [7]- canonical|names|of|all|taxa|in|the|taxa|file If there are 2-10 of them (so, discard this if there's only one, or >10) */
        $canonicals = array_keys($this->batch['canonicals']);
        $canonicals = array_map('trim', $canonicals);
        asort($canonicals);
        // print_r($canonicals);
        echo "\ncanonicals: ".count($canonicals)."\n";
        $total = count($canonicals);
        if($total >= 2 && $total <= 10) $final['canonicals'] = implode("|", $canonicals);
        else $final['canonicals'] = "";
        
        
        /* [8]- deduplicated, term names for all measurementType terms that appear in rows where measurementOfTaxon=true  */
        $mTypes = array_keys($this->batch['mType']);
        $arr = array();
        foreach($mTypes as $mType) $arr[] = pathinfo($mType, PATHINFO_BASENAME);
        asort($arr);
        print_r($arr);
        $final['mTypes'] = implode("|", $arr);
        
        print_r($final);
        return $final;
    }
    private function format_kebab_case($str)
    {   // (eg: Dunn et al, 2015 => dunn-et-al-2015)
        
        if(stripos($str, "ó") !== false) { //string is found --------- e.g. "Queirós"
            $str = str_ireplace("ó", "o", $str);
        }
        
        $str = strtolower($str);
        $str = str_replace(array(" "), "-", $str);
        $str = str_replace(array(","), "", $str);
        return $str;
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
        
        foreach($rec->resources as $resource) {
            $this->get_sciname_of_this_EOLid = array();
            self::process_resource($resource, $rec->name, count($rec->resources), $count);
        }
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
        recursive_rmdir($info['temp_dir']); //remove temp folder --- un-comment in real operation
    }
    private function process_table($meta, $rowtype, $eol_id = false, $what2search = false)
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
                if($EOLid) {
                    $this->batch['EOLids'][$EOLid] = '';
                    $this->info_EOLid_sciname[$EOLid] = $scientificName;
                }
                
                /* single taxon in taxa file and there is no higherClassification in DH:
                taxonID	scientificName	eolID
                Eryonoidea	Eryonoidea	46516723
                Thaumastochelidae	Thaumastochelidae	52207788
                */
                // /* Manual adjustment:
                if(in_array($EOLid, array(46516723, 52207788))) $this->get_sciname_of_this_EOLid[$EOLid] = $scientificName;
                // */
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
                $hc = $rec['http://rs.tdwg.org/dwc/terms/higherClassification'];
                
                if($what2search == 'scientificName') {
                    $canonicalName = $rec['http://rs.gbif.org/terms/1.0/canonicalName'];
                    $scientificName = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
                    if($eol_id == $canonicalName) return $hc;
                    if($eol_id == $scientificName) return $hc;
                }
                else {
                    $EOLid = $rec['http://eol.org/schema/EOLid'];
                    if($eol_id == $EOLid) return $hc;
                }
            }
            #=====================================================================================
            elseif($rowtype == "taxon_BuildUp") {
                $EOLid = $rec['http://eol.org/schema/EOLid'];
                $hc    = $rec['http://rs.tdwg.org/dwc/terms/higherClassification'];
                $this->eolID_hc[$EOLid] = $hc;
            }
            #=====================================================================================
        }
    }
    private function extract_dwca($dwca_file = false, $download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1), $type = "regular") //default expires in 1 day 60*60*24*1. Not false.
    {
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit("\n-exit muna-\n");
        // */

        /* development only
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
        */
        
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