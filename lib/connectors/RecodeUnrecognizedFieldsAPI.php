<?php
namespace php_active_record;
/* connector: [recode_unrecognized_fields.php]
*/
class RecodeUnrecognizedFieldsAPI
{
    function __construct($resource_id = NULL)
    {
        if($resource_id) {
            $this->resource_id = $resource_id;
            $this->dwca_file = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '.tar.gz';
        }
        $this->download_options = array('resource_id' => 'UnrecognizedFields', 'timeout' => 172800, 'expire_seconds' => 60*60*24*1, 'download_wait_time' => 1000000); //probably default expires in 1 day 60*60*24*1. Not false.
        $this->debug = array();
        
        $this->unrecognized_fields_report['opendata'] = CONTENT_RESOURCE_LOCAL_PATH.'/reports/unrecognized_fields_opendata.txt';
        $this->unrecognized_fields_report['local path'] = CONTENT_RESOURCE_LOCAL_PATH.'/reports/unrecognized_fields.txt';
        //Below used if DwCA files are from OpenData.eol.org:
        $this->opendata_resources_list = 'https://opendata.eol.org/api/3/action/package_list';
        $this->opendata_resource_info = 'https://opendata.eol.org/api/3/action/package_show?id=RESOURCE_ID';
    }
    public function scan_a_resource($resource_id)
    {
        $file = CONTENT_RESOURCE_LOCAL_PATH.$resource_id.'.tar.gz';
        // $file = CONTENT_RESOURCE_LOCAL_PATH.$resource_id.'.zip'; //debug only -- force value
        self::sought_fields($resource_id); //initialize
        self::scan_dwca($file, false, $resource_id);
    }
    public function process_OpenData_resources()
    {
        self::sought_fields('opendata'); //initialize
        $dwca_files = self::get_all_tr_gz_files_in_OpenData(); //print_r($dwca_files);
        // echo "\n--Report--\n"; self::print_report('opendata'); //working OK but now called separately
    }
    private function get_all_tr_gz_files_in_OpenData()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*1; //1 hour expires
        if($json = Functions::lookup_with_cache($this->opendata_resources_list, $options)) {
            $IDs = json_decode($json, true); //print_r($IDs); exit;
            $total = count($IDs['result']); $i = 0;
            foreach($IDs['result'] as $id) { $i++;
                echo "\n$i of $total [$id]\n";
                // $id = 'fishbase'; //debug only -- forced value
                $url = str_replace('RESOURCE_ID', $id, $this->opendata_resource_info); // exit("\n[$url]\n");
                if($json = Functions::lookup_with_cache($url, $options)) {
                    $info = json_decode($json, true); //print_r($info); exit;
                    foreach($info['result']['resources'] as $res) {
                        $res['main_dataset'] = $id;
                        // print_r($res); //good debug
                        /*Array(
                            [package_id] => e4a7239b-7297-4a75-9fe9-1f5cff5e20d7
                            [id] => 7408693e-094a-4335-a0c9-b114d7dc64d3
                            [name] => Cicadellinae
                            [url] => https://opendata.eol.org/dataset/e4a7239b-7297-4a75-9fe9-1f5cff5e20d7/resource/7408693e-094a-4335-a0c9-b114d7dc64d3/download/archive.zip
                        )*/
                        $pathinfo = pathinfo($res['url']);
                        // print_r($pathinfo); //exit;
                        /*Array(
                            [dirname] => https://editors.eol.org/eol_php_code/applications/content_server/resources
                            [basename] => 42_meta_recoded.tar.gz
                            [extension] => gz
                            [filename] => 42_meta_recoded.tar
                        )
                        Array(
                            [dirname] => https://opendata.eol.org/dataset/e4a7239b-7297-4a75-9fe9-1f5cff5e20d7/resource/7408693e-094a-4335-a0c9-b114d7dc64d3/download
                            [basename] => archive.zip
                            [extension] => zip
                            [filename] => archive
                        )*/
                        if(stripos($res['url'], "editors.eol.org/eol_php_code/applications/content_server/resources") !== false && 
                           substr($res['url'], -7) != '.txt.gz' &&
                           substr($res['url'], -4) != '.tsv' &&
                           substr($res['url'], -8) != '.json.gz') {
                            $file = CONTENT_RESOURCE_LOCAL_PATH."/".$pathinfo['basename'];
                            self::scan_dwca($file, $res, 'opendata');
                        }
                        elseif((stripos($res['url'], "opendata.eol.org/dataset/") !== false) && ($pathinfo['basename'] == 'archive.zip')) {
                            $file = $res['url'];
                            self::scan_dwca($file, $res, 'opendata');
                        }
                        else continue; //ignore
                    }
                }
                // break; //debug only -- PROCESS JUST 1 RECORD
            } //end foreach()
        }
    }
    public function process_all_resources()
    {
        // /*
        self::sought_fields('local path'); //initialize
        $dwca_files = self::get_all_tr_gz_files_in_resources_folder(); //print_r($dwca_files);
        foreach($dwca_files as $file) { echo "\nProcessing [$file]...\n";
            // $file = '24.tar.gz'; //debug only - forced value
            $file = CONTENT_RESOURCE_LOCAL_PATH . $file;
            self::scan_dwca($file, false, 'local path');
            // break; //debug only -- PROCESS JUST 1 RECORD
        }
        // */
        echo "\n--Report--\n"; self::print_report('local path');
    }
    public function print_report($what)
    {
        $file = self::get_file($what);
        foreach(new FileIterator($file) as $line_number => $line) {
            print_r(json_decode($line, true));
        }
    }
    private function get_all_tr_gz_files_in_resources_folder()
    {
        $arr = array();
        foreach(glob(CONTENT_RESOURCE_LOCAL_PATH . "*.tar.gz") as $filename) {
            $basename = pathinfo($filename, PATHINFO_BASENAME);
            $arr[$basename] = '';
        }
        ksort($arr);
        return array_keys($arr);
    }
    private function get_file($what)
    {
        if(in_array($what, array('local path', 'opendata'))) $file = $this->unrecognized_fields_report[$what];
        else                                                 $file = CONTENT_RESOURCE_LOCAL_PATH.'/reports/'.$what.'.txt';
        return $file;
    }
    public function scan_dwca($dwca_file, $resource_info = array(), $what) //utility to search meta.xml for certain fields
    {
        if($paths = self::extract_dwca($dwca_file)) {
            if(is_file($paths['temp_dir'].'meta.xml')) {
                $ret = self::parse_meta_xml($paths['temp_dir'].'meta.xml');
                $xml_info = $ret['final']; //print_r($xml_info); exit;
                $location = $ret['location'];
                if($found = self::search_sought_fields($xml_info, $dwca_file, $resource_info)) {
                    // echo "\nFOUND: ";
                    /* look deeper if the said fields have actual values. */
                    $found = self::scrutinize_tables($found, $location, $paths, $xml_info);
                    // print_r($xml_info); print_r($location); exit;
                    if($GLOBALS['ENV_DEBUG']) print_r($found); //good debug
                    // print_r($found); exit("\nexit muna\n");

                    /* write to report: */
                    if(@$found['main dataset']) echo "\n[".$found['main dataset']."] [".$found['resource name']."] [".$found['resource ID']."]\n";
                    $file = self::get_file($what);
                    $WRITE = Functions::file_open($file, "a");
                    fwrite($WRITE, json_encode($found) . "\n");
                    fclose($WRITE);
                }
            }
            else echo "\n- No meta.xml [$dwca_file]\n";
        }
        else echo "\nERROR: Cannot extract [$dwca_file]\n";
        
        // remove temp dir
        // /*
        if($val = $paths['temp_dir']) {
            recursive_rmdir($val);
            // echo ("\n temporary directory removed: [$val]\n");
        }
        // */
    }
    private function scrutinize_tables($found, $location, $paths, $xml_info)
    {   //print_r($found);
        /*Array $found
        (
            [tables] => Array(
                    [http://eol.org/schema/media/Document] => Array(
                            [0] => http://purl.org/dc/terms/contributor
                            [1] => http://purl.org/dc/terms/creator
                            [2] => http://purl.org/dc/terms/publisher
                            [3] => http://eol.org/schema/media/thumbnailURL
                        )
                    [http://rs.tdwg.org/dwc/terms/Occurrence] => Array(
                            [0] => http://rs.tdwg.org/dwc/terms/catalogNumber
                            [1] => http://rs.tdwg.org/dwc/terms/collectionCode
                            [2] => http://rs.tdwg.org/dwc/terms/institutionCode
                            [3] => http://rs.tdwg.org/dwc/terms/eventID
                        )
                )
            [DwCA] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources/copepod_sizes_Archive.zip
        )*/
        foreach($found['tables'] as $rowtype => $uri_fields) {
            // echo "\n$rowtype\n"; print_r($fields); continue; //debug only
            $file = $paths['temp_dir'].$location[$rowtype]; // echo "\n$file\n";
            $basenames = self::get_sought_fields($uri_fields, $file, $xml_info[$rowtype]);
            $i = 0;
            foreach(new FileIterator($file) as $line => $row) {
                $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
                if(!$row) continue;
                // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
                $tmp = explode("\t", $row);
                if($i == 1) {
                    $fields = $tmp;
                    continue;
                }
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    if(!$field) continue;
                    $rec[$field] = $tmp[$k]; //put "@" as @$tmp[$k] during development
                    $k++;
                } //print_r($rec); //exit;
                $rec = array_map('trim', $rec);
                foreach(array_keys($basenames) as $basename) if(@$rec[$basename]) $basenames[$basename] = 'Y';
            }//end foreach()
            $found['with values'][$rowtype] = $basenames;
        }
        return $found;
    }
    private function get_sought_fields($uri_fields, $file, $orig_fields)
    {   // print_r($uri_fields); print_r($orig_fields); exit("\n$file\n");
        $info = self::build_uri_label_info($orig_fields, $file); //e.g. $info['http://purl.org/dc/terms/publisher'] = 'Publisher'
        /* Initially it was manually done. Not good.
        //MEDIA
        $info['http://purl.org/dc/terms/publisher'] = "Publisher";
        $info['http://purl.org/dc/terms/contributor'] = "Contributor";
        $info['http://purl.org/dc/terms/creator'] = "Creator";
        $info['http://eol.org/schema/media/thumbnailURL'] = "ThumbnailURL";
        //OCCURRENCE
        $info['http://rs.tdwg.org/dwc/terms/basisOfRecord'] = "Basis of Record";        //??
        $info['http://rs.tdwg.org/dwc/terms/catalogNumber'] = "Catalog Number";
        $info['http://rs.tdwg.org/dwc/terms/collectionCode'] = "Collection Code";
        $info['http://rs.tdwg.org/dwc/terms/countryCode'] = "Country Code";             //??
        $info['http://rs.tdwg.org/dwc/terms/institutionCode'] = "Institution Code";
        $info['http://rs.tdwg.org/dwc/terms/eventID'] = "Event ID";
        */
        foreach($uri_fields as $uri) {
            if($val = $info[$uri]) $basenames[$val] = '';
            else exit("\nUnknown field: [$uri]\n");
        }
        return $basenames;
    }
    private function build_uri_label_info($uris, $file)
    {   $i = 0;
        foreach(new FileIterator($file) as $line => $row) {
            $i++; 
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            if($i == 1) {
                $fields = $tmp;
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    if(!$field) continue;
                    $info[$uris[$k]] = $fields[$k];
                    $k++;
                }
                $info = array_map('trim', $info); // print_r($info); exit;
                return $info;
            }
        }
    }
    private function search_sought_fields($xml_info, $dwca_file, $resource_info)
    {
        $sought_rowType_names = array('MEDIA', 'OCCURRENCES');
        $rowTypes_info['MEDIA'] = array('http://eol.org/schema/media/Document'); //array bec. in the future it may be e.g. 'http://rs.gbif.org/terms/1.0/multimedia'
        $rowTypes_info['OCCURRENCES'] = array('http://rs.tdwg.org/dwc/terms/Occurrence');
        // print_r($xml_info);
        // print_r($resource_info);
        /*Array(
            [package_id] => e4a7239b-7297-4a75-9fe9-1f5cff5e20d7
            [id] => 7408693e-094a-4335-a0c9-b114d7dc64d3
            [name] => Cicadellinae
            ...
        )*/
        $final = array();
        foreach($sought_rowType_names as $name) { //e.g. 'MEDIA'
            foreach($this->sought[$name] as $uri) { // echo "\n$uri\n";
                foreach($xml_info as $rowType => $fields) {
                    if(in_array($rowType, $rowTypes_info[$name])) {
                        if(in_array($uri, $fields)) {
                            if($resource_info) {
                                $final['main dataset'] = $resource_info['main_dataset'];
                                $final['resource name'] = $resource_info['name'];
                                $final['resource ID'] = $resource_info['id'];
                            }
                            $final['tables'][$rowType][] = $uri;
                            $final['DwCA'] = $dwca_file;
                            // $final[$dwca_file][$rowType][] = $uri; //orig
                        }
                    }
                }
            }
        }
        // print_r($final); exit;
        return $final;
    }
    private function parse_meta_xml($meta_xml)
    {
        // echo "\n$meta_xml\n";
        $xml = simplexml_load_file($meta_xml);
        $final = array(); $location = array();
        foreach($xml->table as $tab) {
            // print_r($tab);
            /*SimpleXMLElement Object(
                [@attributes] => Array(
                        [encoding] => UTF-8
                        [fieldsTerminatedBy] => \t
                        [linesTerminatedBy] => \n
                        [ignoreHeaderLines] => 1
                        [rowType] => http://rs.tdwg.org/dwc/terms/Taxon
                    )
                [files] => SimpleXMLElement Object(
                        [location] => taxon.tab
                    )
                [field] => Array(
                        [0] => SimpleXMLElement Object(
                                [@attributes] => Array(
                                        [index] => 0
                                        [term] => http://rs.tdwg.org/dwc/terms/taxonID
                                    )
                            )
            */
            $rowType = (string) $tab{'rowType'}; //echo "\n$rowType";
            foreach($tab->field as $fld) { //echo "\n".$fld{'term'}."\n";
                $location[$rowType] = (string) $tab->files->location;
                $final[$rowType][] = (string) $fld{'term'};
            }
        }
        // print_r($final);
        return array('final' => $final, 'location' => $location);
    }
    private function extract_dwca($dwca_file)
    {
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, "meta.xml", $this->download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); //exit("\n-exit muna-\n");
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_81560/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_81560/'
        );
        */
        return $paths;
    }
    private function sought_fields($what)
    {
        $file = self::get_file($what);
        $WRITE = Functions::file_open($file, "w"); //initialize report
        fclose($WRITE);
        
        /*REFERENCES
        FYI, but you can leave these as is. We're not using them yet, but when we get cleverer with references, we aught to:
            http://eol.org/schema/reference/publicationType
            http://purl.org/ontology/bibo/pageEnd
            http://purl.org/ontology/bibo/pageStart
            http://purl.org/dc/terms/language

        MEDIA
        These are important; I can't believe I never noticed they were missing. Oops. They can all be recoded via the agents file, 
        with Agent Role assigned accordingly: */
        $this->sought['MEDIA'] = array('http://purl.org/dc/terms/contributor', 'http://purl.org/dc/terms/creator', 'http://purl.org/dc/terms/publisher');
        
        /* Hi Jen,
        Yes, we have agent roles for 'creator' and 'publisher'. But none for 'contributor'.
        Maybe we can also just say 'creator' or 'author' in agents for 'contributor'?
        Thanks.
        */

        /* Discard. We make our own thumbnails now for all media, even if someone is trying to make them for us:*/
        $this->sought['MEDIA'][] = 'http://eol.org/schema/media/thumbnailURL';

        /* FYI, but you can leave these as is. They're either redundant or not super important, but let's not throw them away.
            http://ns.adobe.com/xap/1.0/CreateDate
            http://ns.adobe.com/xap/1.0/Rating
            http://purl.org/dc/terms/audience
            http://purl.org/dc/terms/modified
            http://purl.org/dc/terms/rights
            http://purl.org/dc/terms/spatial
            http://rs.tdwg.org/ac/terms/derivedFrom
            http://www.w3.org/2003/01/geo/wgs84_pos#alt
            http://www.w3.org/2003/01/geo/wgs84_pos#lat
            http://www.w3.org/2003/01/geo/wgs84_pos#long

        I'm not sure what this is. Can you get me an example of some content from this field? */
        $this->sought['MEDIA'][] = 'http://rs.tdwg.org/ac/terms/additionalInformation';
        
        /*
        OCCURRENCES
        Recode as MoF records of with MeasurementOfTaxon=false: */
        $this->sought['OCCURRENCES'] = array('http://rs.tdwg.org/dwc/terms/basisOfRecord', 'http://rs.tdwg.org/dwc/terms/catalogNumber', 'http://rs.tdwg.org/dwc/terms/collectionCode', 
                                             'http://rs.tdwg.org/dwc/terms/countryCode', 'http://rs.tdwg.org/dwc/terms/institutionCode');
        /*
        Discard. But alert me if you find a resource with an actual Events file. Its contents will need recoding too: */
        $this->sought['OCCURRENCES'][] = 'http://rs.tdwg.org/dwc/terms/eventID';
        /*
        GLOBI
        I think there's currently a typo in both of these fields (http:/eol.org...) but either way, they want recoding again. 
        I think this will work- MoF records, measurementOfTaxon=false, should attach these to their occurrences. But they should be mapped to eol terms:

            http://eol.org/globi/terms/bodyPart => http://eol.org/schema/terms/bodyPart
            http://eol.org/globi/terms/physiologicalState => http://eol.org/schema/terms/physiologicalState

        I think the Occurrences records and the GloBI records will have mostly plain text strings as values, rather than URIs, which is fine. They'll do for metadata values.
        */
    }
}
?>