<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from species_checklists.php] */
class SpeciesChecklistAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->opendata_dataset_api = 'https://opendata.eol.org/api/3/action/package_show?id=';
        $this->download_options = array(
            'resource_id'        => 'SCR', //species checklist resources
            'expire_seconds'     => 60*60*24*30*3, //ideally 3 months to expire
            'download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        
        /* addtl adjustments per Jen: https://eol-jira.bibalex.org/browse/DATA-1817?focusedCommentId=63662&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63662 */
        $this->mapping['nationalchecklists'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/DATA-1817/national-checklists-sourcefixes.tsv';
        $this->mapping['water-body-checklists'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/DATA-1817/water-body-checklists-sourcefixes.tsv';
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        // /* buildup lookup table for adjustment mapping here: https://eol-jira.bibalex.org/browse/DATA-1817?focusedCommentId=63662&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63662
        self::build_lookup_adjustment_tbl();
        // */
        
        require_library('connectors/GBIFoccurrenceAPI_DwCA');
        $this->gbif_func = new GBIFoccurrenceAPI_DwCA();
        
        $tables = $info['harvester']->tables;
        $tbls = array_keys($tables); print_r($tbls);
        // $tbls = array('http://rs.tdwg.org/dwc/terms/measurementorfact'); //debug only - forced -- comment in real operation
        foreach($tbls as $tbl) {
            self::process_extension($tables[$tbl][0]); //this is just to copy extension but with customization as described in DATA-1817
        }
    }
    private function build_lookup_adjustment_tbl()
    {
        $files = array($this->mapping['nationalchecklists'], $this->mapping['water-body-checklists']);
        foreach($files as $file) {
            $local = Functions::save_remote_file_to_local($file);
            $i = 0;
            foreach(new FileIterator($local) as $line => $row) { $i++;
                if(!$row) continue;
                if($i == 1) $fields = explode("\t", $row);
                else {
                    $rec = explode("\t", $row);
                    $k = -1; $rek = array();
                    foreach($fields as $field) {
                        $k++;
                        $rek[$field] = $rec[$k];
                    }
                    // print_r($rek); exit;
                    /*Array(
                        [resource name] => Ireland Species List
                        [resource url] => https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_ireland.tar.gz
                        [new source ending] => &country=IE
                    )*/
                    $resource_id = pathinfo($rek['resource url'], PATHINFO_FILENAME);
                    $resource_id = str_replace('.tar', '', $resource_id);
                    $this->ending_info[$resource_id] = $rek['new source ending'];
                }
            }
            unlink($local);
        }
    }
    private function get_dwca_short_fields($meta_fields)
    {
        foreach($meta_fields as $f) $final[] = pathinfo($f['term'], PATHINFO_FILENAME);
        return $final;
    }
    private function process_extension($meta)
    {   //print_r($meta->fields); //exit;
        echo "\nProcessing $meta->row_type ...\n";
        $dwca_short_fields = self::get_dwca_short_fields($meta->fields);
        $class = strtolower(pathinfo($meta->row_type, PATHINFO_FILENAME));
        
        if($class != "taxon") {
            if(isset($this->unique_taxon_ids)) $this->unique_taxon_ids = ''; //just remove from memory
        }
        
        $i = 0;
        
        // print_r($meta);
        if(file_exists($meta->file_uri)) echo "\nexists: [$meta->file_uri]\n";
        else echo "\ndoes not exist: [$meta->file_uri]\n";
        // exit;
        
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 25000) == 0) echo "\n".number_format($i);
            /* not followed since meta.xml is not reflective of the actual dwca. DwCA seems manually created.
            if($meta->ignore_header_lines && $i == 1) continue;
            */
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            
            // print_r($dwca_short_fields); print_r($tmp); exit;

            if(in_array($tmp[0], $dwca_short_fields)) continue; //this means if first row is the header fields then ignore

            // echo "\n".count($meta->fields);
            // echo "\n".count($tmp); exit("\n");
            /* commented since child records have lesser columns, but should be accepted.
            if(count($meta->fields) != count($tmp)) continue;
            */
            
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = @$tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /**/

            if    ($class == "vernacular")          $o = new \eol_schema\VernacularName();
            elseif($class == "agent")               $o = new \eol_schema\Agent();
            elseif($class == "reference")           $o = new \eol_schema\Reference();
            elseif($class == "taxon")               $o = new \eol_schema\Taxon();
            elseif($class == "document")            $o = new \eol_schema\MediaResource();
            elseif($class == "occurrence")          $o = new \eol_schema\Occurrence();
            elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
            else {
                print_r($meta);
                exit("\nUndefined class [$class]\n");
            }

            if($class == 'taxon') { //print_r($rec); exit;
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/taxonID] => T100000
                    [http://rs.tdwg.org/dwc/terms/scientificName] => Argyrosomus inodorus
                    [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => T100001
                    [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                )*/
                if(isset($this->unique_taxon_ids[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue; //will cause duplicate taxonID
                else $this->unique_taxon_ids[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = '';
            }
            
            if($class == 'measurementorfact') { // print_r($rec); exit;
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/measurementID] => measurementID
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => occurrenceID
                    [http://eol.org/schema/parentMeasurementID] => parentMeasurementID
                    [http://eol.org/schema/measurementOfTaxon] => measurementOfTaxon
                    [http://rs.tdwg.org/dwc/terms/measurementType] => measurementType
                    [http://rs.tdwg.org/dwc/terms/measurementValue] => measurementValue
                    [http://eol.org/schema/reference/referenceID] => referenceID
                    [http://purl.org/dc/terms/contributor] => contributor
                    [http://purl.org/dc/terms/source] => source
                )*/
                /* This means children record should be presented correctly. */
                if(!$rec['http://rs.tdwg.org/dwc/terms/measurementID'] || !$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']) { //means probably a child record
                    if(!$rec['http://eol.org/schema/parentMeasurementID']) {
                        print_r($rec); exit("\nThis child record has to have a parentMeasurementID\n");
                    }
                    else $rec['http://rs.tdwg.org/dwc/terms/measurementID'] = $rec['http://eol.org/schema/parentMeasurementID']."_".pathinfo($rec['http://rs.tdwg.org/dwc/terms/measurementType'], PATHINFO_BASENAME);
                }
                /* This will format source based on DATA-1817 */
                if($val = $rec['http://purl.org/dc/terms/source']) $rec['http://purl.org/dc/terms/source'] = self::convert_2gbif_url($val);
            }
            
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    function convert_2gbif_url($url)
    {   /*
        change old % 28 -->> (
        change old % 29 -->> )
        change both % 20 -->> space
        change both % 2C -->> comma
        */
        $url = str_replace("%28", "(", $url);
        $url = str_replace("%29", ")", $url);
        $url = str_replace("%20", " ", $url);
        $url = str_replace("%2C", ",", $url);
/*        
http://gimmefreshdata.github.io/?limit=5000000&taxonSelector=Enhydra lutris&traitSelector=&wktString
=GEOMETRYCOLLECTION(POLYGON ((-65.022 63.392, -74.232 64.672, -84.915 71.353, -68.482 68.795, -67.685 66.286, -65.022 63.392)),
                    POLYGON ((-123.126 49.079, -129.911 53.771, -125.34 69.52, -97.874 68.532, -85.754 68.217, -91.525 63.582, -77.684 60.542, -64.072 59.817, -55.85 53.249, -64.912 43.79, -123.126 49.079))
                   )

https://www.gbif.org/occurrence/map?geometry=POLYGON((-65.022 63.392, -74.232 64.672, -84.915 71.353, -68.482 68.795, -67.685 66.286, -65.022 63.392))
                                   &geometry=POLYGON((-123.126 49.079, -129.911 53.771, -125.34 69.52, -97.874 68.532, -85.754 68.217, -91.525 63.582, -77.684 60.542, -64.072 59.817, -55.85 53.249, -64.912 43.79, -123.126 49.079))
*/
        if(preg_match("/taxonSelector=(.*?)\&/ims", $url, $arr)) {
            $sciname = $arr[1];
            if($taxon_key = $this->gbif_func->get_usage_key($sciname)) {}
            else return '';
        }
        else return '';

        if(preg_match_all("/POLYGON \(\((.*?)\)\)/ims", $url, $arr))    {} //print_r($arr[1]);
        elseif(preg_match_all("/POLYGON\(\((.*?)\)\)/ims", $url, $arr)) {} //print_r($arr[1]);
        else exit("\n========================\n[$url]\n========================\nInvestigate url format\n");
        
        $this->pre_gbif_url = 'https://www.gbif.org/occurrence/map?taxon_key=TAXONKEY&';
        foreach($arr[1] as $str) $parts[] = 'geometry=POLYGON(('.$str.'))';
        // print_r($parts);
        
        // &geometry=POLYGON((-69.5208%20-55.74669,-76.44768%20-51.64948,-70.49721%20-19.53163,-75.99586%20-16.12312,-82.01431%20-5.93742,-81.18622%20-1.29639,-179.89494%20-2.09358,-179.71436%20-63.65479,-69.2942%20-62.96402,-69.5208%20-55.74669))
        // https://www.gbif.org/occurrence/map?taxon_key=5789284&geometry=POLYGON((158.466%20-31.877%2C%20158.818%20-29.458%2C%20167.583%20-22.733%2C%20161.018%20-8.866%2C%20156.562%20-6.645%2C%20147.216%20-5.178%2C%20140.529%20-2.431%2C%20139.738%20-2.349%2C%20138.828%20-1.945%2C%20137.705%20-1.499%2C%20135.131%20-3.359%2C%20134.538%20-2.461%2C%20134.125%20-1.897%2C%20134.102%20-1.12%2C%20129.66064453124997%200.790%2C%20179.121%200.175%2C%20178.857%20-46.920%2C%20178.945%20-60.500%2C%20146.425%20-60.413%2C%20146.601%20-43.580%2C%20167.6%20-47.215%2C%20168.162%20-47.083%2C%20168.99%20-46.666%2C%20169.589%20-46.566%2C%20170.066%20-46.209%2C%20170.742%20-45.861%2C%20170.571%20-45.734%2C%20170.972%20-45.127%2C%20171.618%20-44.131%2C%20172.812%20-43.88%2C%20173.094%20-43.739%2C%20172.864%20-43.115%2C%20173.625%20-42.421%2C%20174.277%20-41.743%2C%20176.131%20-40.993%2C%20176.724%20-40.235%2C%20176.875%20-39.461%2C%20177.866%20-39.099%2C%20178.355%20-38.046%2C%20178.637%20-37.579%2C%20177.336%20-37.988%2C%20176.176%20-37.663%2C%20175.942%20-37.465%2C%20175.769%20-36.704%2C%20175.344%20-36.482%2C%20175.173%20-36.942%2C%20174.771%20-36.446%2C%20174.427%20-35.789%2C%20174.366%20-35.34%2C%20173.787%20-35.004%2C%20173.210%20-34.732%2C%20173.053%20-34.414%2C%20158.466%20-31.877))&geometry=POLYGON((-80.859%20-0.450%2C%20-138.691%200.604%2C%20-178.769%200.955%2C%20-178.945%20-36.536%2C%20-179.296%20-60.586%2C%20-74.003%20-59.584%2C%20-70.136%20-55.235%2C%20-74.179%20-52.968%2C%20-75.9375%20-48.407%2C%20-73.828%20-43.905%2C%20-73.300%20-38.487%2C%20-71.542%20-32.630%2C%20-71.191%20-26.833%2C%20-70.3125%20-20.066%2C%20-71.015%20-17.905%2C%20-75.058%20-15.548%2C%20-77.871%20-10.585%2C%20-80.683%20-5.364%2C%20-80.859%20-0.450))

        if($ending = @$this->ending_info[$this->resource_id]) $final = $this->pre_gbif_url . $ending; //implement new source field ending e.g. [SC_usvirgin] => &country=VI
        else                                                  $final = $this->pre_gbif_url . implode("&", $parts); //orig
        $final = str_replace('TAXONKEY', $taxon_key, $final);
        $final = str_replace(" ", "%20", $final);
        $final = str_replace(",", "%2C", $final);
        // echo "\n[$sciname]\n$final\n";
        return $final;
    }
    function get_opendata_resources($dataset, $all_fields = false)
    {
        $options = $this->download_options;
        // $options['expire_seconds'] = 60*5; //60*60*24; //1 day expires //debug only - during dev only
        if($json = Functions::lookup_with_cache($this->opendata_dataset_api.$dataset, $options)) {
            $o = json_decode($json);
            if($all_fields) return $o->result->resources;
            foreach($o->result->resources as $res) $final[$res->url] = '';
        }
        return array_keys($final);
    }
    /*================================================================= ENDS HERE ======================================================================*/
    
    /*================================================================= for report utility START ======================================================================*/
    function parse_dwca_for_report($resource, $dataset)
    {
        $file = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH."/$dataset".".txt", "a");
        /*sample $resource value = stdClass Object(
            [state] => active
            [description] => A list of species from Afghanistan collected using effechecka and geonames polygons
            [format] => Darwin Core Archive
            [name] => Afghanistan Species List
            [url] => https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_afganistan.tar.gz
            more fields below and above...
        )*/
        $dwca_file = $resource->url;
        $info = self::prepare_archive_for_access($dwca_file);
        $meta = $info['harvester']->tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0];
        $sample_source_url = self::parse_MoF($meta);

        $arr = array($resource->name, $resource->url, $sample_source_url);
        fwrite($file, implode("\t", $arr)."\n");
        fclose($file);

        // remove temp dir
        recursive_rmdir($info['temp_dir']); echo ("\n temporary directory removed: " . $info['temp_dir']);
    }
    private function parse_MoF($meta)
    {
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => M100000
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => CT100000
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.geonames.org/1149361
                [http://purl.org/dc/terms/source] => https://www.gbif.org/occurrence/map?taxon_key=2495659&geometry=POLYGON((60.879%2029.862%2C%2061.713%2031.376%2C%2060.586%2033.143%2C%2060.901%2033.537%2C%2060.478%2034.079%2C%2060.998%2034.633%2C%2061.191%2035.29%2C%2061.387%2035.562%2C%2062.302%2035.141%2C%2063.011%2035.428%2C%2063.141%2035.776%2C%2063.935%2036.04%2C%2064.615%2036.423%2C%2065.102%2037.236%2C%2065.766%2037.545%2C%2067.496%2037.272%2C%2067.899%2037.064%2C%2068.303%2037.106%2C%2068.813%2037.244%2C%2069.008%2037.301%2C%2069.367%2037.405%2C%2069.954%2037.564%2C%2070.183%2037.862%2C%2070.498%2038.118%2C%2070.873%2038.464%2C%2071.136%2038.4%2C%2071.332%2037.883%2C%2071.561%2036.757%2C%2072.67%2037.021%2C%2073.309%2037.462%2C%2073.746%2037.222%2C%2074.683%2037.404%2C%2074.747%2037.275%2C%2074.572%2037.034%2C%2074.152%2036.91%2C%2073.144%2036.894%2C%2072.214%2036.664%2C%2071.569%2036.329%2C%2071.485%2035.754%2C%2071.593%2035.498%2C%2071.536%2035.09%2C%2071.219%2034.748%2C%2071.007%2034.461%2C%2070.502%2033.944%2C%2069.992%2033.74%2C%2070.298%2033.426%2C%2069.876%2033.096%2C%2069.535%2032.866%2C%2069.323%2031.941%2C%2068.57%2031.828%2C%2068.287%2031.757%2C%2067.713%2031.521%2C%2067.763%2031.328%2C%2066.969%2031.313%2C%2066.387%2030.934%2C%2066.362%2029.968%2C%2064.353%2029.545%2C%2063.56%2029.489%2C%2060.879%2029.862))
                [http://purl.org/dc/terms/contributor] => Compiler: Anne E Thessen
                [http://eol.org/schema/reference/referenceID] => R01|R02
            )*/
            return $rec['http://purl.org/dc/terms/source']; //a sample of the contents of one record in the furtherInformationURL field
        }
    }
    private function prepare_archive_for_access($dwca_file)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => false)); //won't expire anymore
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
    /*================================================================= for report utility END ========================================================================*/
    
    /* this is just to copy the extension as is. No customization.
    private function process_generic($meta)
    {   //print_r($meta);
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            $o = new \eol_schema\Association();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    */
}
?>
