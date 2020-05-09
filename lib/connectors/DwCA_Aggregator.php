<?php
namespace php_active_record;
/* connector: [aggregate_resources.php] - first client 
This lib basically combined DwCA's (.tar.gz) resources.
First client is combining several wikipedia languages -> combine_wikipedia_DwCAs(). Started with languages "ta", "el", "ceb".
2nd client is /connectors/wikipedia_ver2.php
*/
class DwCA_Aggregator
{
    function __construct($folder = NULL, $dwca_file = NULL, $DwCA_Type = 'wikipedia') //'wikipedia' is the first client of this lib.
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->DwCA_Type = $DwCA_Type;
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
                                  "http://eol.org/schema/reference/reference"       => "reference");
        /* copied template
        if(@$this->resource_id == 24) {
            $this->taxon_ids = array();
        }
        */
    }
    function combine_DwCAs($langs, $preferred_rowtypes = array())
    {
        foreach($langs as $this->lang) {
            echo "\n---Processing: [$this->lang]---\n";
            $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.$this->lang.'.tar.gz';
            if(file_exists($dwca_file)) {
                self::convert_archive($preferred_rowtypes, $dwca_file);
            }
            else echo "\nDwCA file does not exist [$dwca_file]\n";
        }
        $this->archive_builder->finalize(TRUE);
    }
    function combine_wikipedia_DwCAs($langs)
    {
        foreach($langs as $this->lang) {
            echo "\n---Processing: [$this->lang]---\n";
            $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.'wikipedia-'.$this->lang.'.tar.gz';
            if(file_exists($dwca_file)) {
                $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/media/document');
                self::convert_archive($preferred_rowtypes, $dwca_file);
            }
            else echo "\nDwCA file does not exist [$dwca_file]\n";
        }
        $this->archive_builder->finalize(TRUE);
    }
    private function convert_archive($preferred_rowtypes = false, $dwca_file)
    {   /* param $preferred_rowtypes is the option to include-only those row_types you want on your final DwCA.*/
        echo "\nConverting archive to EOL DwCA...\n";
        $info = self::start($dwca_file, array('timeout' => 172800, 'expire_seconds' => 0)); //1 day expire -> 60*60*24*1
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        /* e.g. $index -> these are the row_types
        Array
            [0] => http://rs.tdwg.org/dwc/terms/taxon
            [1] => http://rs.gbif.org/terms/1.0/vernacularname
            [2] => http://rs.tdwg.org/dwc/terms/occurrence
            [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
        */
        print_r($index); //exit; //good debug to see the all-lower case URIs
        foreach($index as $row_type) {
            /* ----------customized start------------ */
            if($this->resource_id == 'wikipedia_combined_languages') break; //all extensions will be processed elsewhere.
            if($this->resource_id == 'wikipedia_combined_languages_batch2') break; //all extensions will be processed elsewhere.
            /* ----------customized end-------------- */

            // /* copied template -- where regular DwCA is processed.
            if($preferred_rowtypes) {
                if(!in_array($row_type, $preferred_rowtypes)) continue;
            }
            if($extension_row_type = @$this->extensions[$row_type]) { //process only defined row_types
                // if($extension_row_type == 'document') continue; //debug only
                echo "\nprocessing...: [$row_type]: ".$extension_row_type."...\n";
                /* not used - copied template
                self::process_fields($harvester->process_row_type($row_type), $extension_row_type);
                */
                self::process_table($tables[$row_type][0], $extension_row_type);
            }
            else echo "\nun-initialized: [$row_type]: ".$extension_row_type."\n";
            // */
        }
        
        // /* ================================= start of customization =================================
        if(in_array($this->resource_id, array('wikipedia_combined_languages', 'wikipedia_combined_languages_batch2'))) {
            $tables = $info['harvester']->tables;
            // print_r($tables); exit;
            /*Array(
                [0] => http://rs.tdwg.org/dwc/terms/taxon
                [1] => http://eol.org/schema/media/document
            )*/
            self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
            self::process_table($tables['http://eol.org/schema/media/document'][0], 'document');
        }
        // ================================= end of customization ================================= */ 
        
        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
        if($this->debug) print_r($this->debug);
    }
    private function start($dwca_file = false, $download_options = array('timeout' => 172800, 'expire_seconds' => false)) //probably default expires in a month 60*60*24*30. Not false.
    {
        if($dwca_file) $this->dwca_file = $dwca_file;
        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths);
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_05106/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_05106/'
        );
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
    private function is_utf8($v)
    {
        $v = trim($v);
        if(!$v) return true;
        $return = Functions::is_utf8($v);
        return $return;
    }
    private function process_table($meta, $what)
    {   //print_r($meta);
        echo "\nprocessing [$what]...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            // $row = Functions::conv_to_utf8($row); //new line
            
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            
            /* new block
            if($i == 1) {
                $tmp = explode("\t", $row);
                $column_count = count($tmp);
            }
            */
            
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            
            // if($column_count != count($tmp)) continue; //new line
            
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => Q140
                [http://purl.org/dc/terms/source] => http://ta.wikipedia.org/w/index.php?title=%E0%AE%9A%E0%AE%BF%E0%AE%99%E0%AF%8D%E0%AE%95%E0%AE%AE%E0%AF%8D&oldid=2702618
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => Q127960
                [http://rs.tdwg.org/dwc/terms/scientificName] => Panthera leo
                [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Carl Linnaeus, 1758
            )*/

            /* special case. Selected by openning media.tab using Numbers while set description = 'test'. Get taxonID for that row */
            if($this->lang == 'el') {
                // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == 'Q18498') continue; 
            }
            if($this->lang == 'mk') {
                // if(in_array($rec['http://rs.tdwg.org/dwc/terms/taxonID'], array('Q10876', 'Q5185', 'Q10892', 'Q152', 'Q10798', 'Q8314', 'Q15574019'))) continue;
            }
            
            $uris = array_keys($rec);
            if($what == "taxon")           $o = new \eol_schema\Taxon();
            elseif($what == "document")    $o = new \eol_schema\MediaResource();
            
            if($this->DwCA_Type == 'wikipedia') {
                if($what == "taxon") {
                    $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                    if(stripos($rec['http://purl.org/dc/terms/source'], "wikipedia.org") !== false) $rec['http://purl.org/dc/terms/source'] = 'https://www.wikidata.org/wiki/'.$taxon_id; //string is found
                    if(!isset($this->taxon_ids[$taxon_id])) {
                        $this->taxon_ids[$taxon_id] = '';
                    }
                    else continue;
                }
            }
            
            /* Good debug
            elseif($what == "document") {
                $desc = @$rec['http://purl.org/dc/terms/description'];
                if($desc) {
                    $desc = str_ireplace(array("\n", "\t", "\r", chr(9), chr(10), chr(13), chr(0x0D), chr(0x0A), chr(0x0D0A)), " ", $desc);
                    $desc = Functions::conv_to_utf8($desc);
                }
                $rec['http://purl.org/dc/terms/description'] = 'eli'; //$desc;

                if($val = trim(@$rec['http://ns.adobe.com/xap/1.0/rights/UsageTerms'])) {}
                else exit("\nNo license\n"); //continue;
            }
            */
            
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 2) break; //debug only
        }
    }
}
?>