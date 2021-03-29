<?php
namespace php_active_record;
/* */
class TRAM_992_API
{
    function __construct()
    {
        $this->download_options = array('resource_id' => 'opendata', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->opendata_api['tag taxonomic inference'] = 'https://opendata.eol.org/api/3/action/package_search?q=taxonomic+inference&start=0&rows=200&&sort=metadata_modified+desc';
        $this->opendata_page['package_id'] = 'https://opendata.eol.org/dataset/';
    }
    function start()
    {
        if($json = Functions::lookup_with_cache($this->opendata_api['tag taxonomic inference'], $this->download_options)) {
            $obj = json_decode($json); //print_r($obj);
            $i = 0;
            foreach($obj->result->results as $rec) {
                // print_r($rec->tags); exit;
                if(@$rec->tags{0}->name == 'taxonomic inference') {
                    self::process_rec($rec);
                    $i++;
                }
            }
            echo "\nResources: [$i]\n";
            print_r($this->package); exit("exit 2");
        }
    }
    private function process_rec($rec)
    {   //print_r($rec);
        if(count($rec->resources) > 1) { print_r($rec); exit("\nMore than one resources?\n"); }
        foreach($rec->resources as $resource) self::process_resource($resource);
    }
    private function process_resource($res)
    {   print_r($res);
        /*stdClass Object(
            [description] => 
            [name] => Lewis and Taylor, 1965
            [package_id] => 10c26a35-e332-4c56-94fd-a5b39d245ff6
            [format] => ZIP
            [url] => https://opendata.eol.org/dataset/10c26a35-e332-4c56-94fd-a5b39d245ff6/resource/98edf631-a461-4761-a25e-f36c6527dc46/download/archive.zip
            [id] => 98edf631-a461-4761-a25e-f36c6527dc46
        )*/
        $this->batch = array();
        self::process_dwca($res->url);
        $this->package[$res->package_id] = $this->batch;
        // print_r($this->batch);
        // exit("\n-exit muna-\n");
    }
    private function process_dwca($dwca_url)
    {
        $info = self::extract_dwca($dwca_url, $this->download_options);
        // print_r($info); exit("\nexit 1\n");
        
        $tables = $info['harvester']->tables;
        print_r(array_keys($tables));
        $rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        $rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact');
        foreach($rowtypes as $rowtype) self::process_table($tables[$rowtype][0]);
    }
    private function process_table($meta)
    {   //print_r($meta); exit;
        echo "\nprocess_table...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            } //print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => Thysanoptera
                [http://rs.tdwg.org/dwc/terms/scientificName] => Thysanoptera
                [http://rs.tdwg.org/dwc/terms/kingdom] => 
                [http://rs.tdwg.org/dwc/terms/phylum] => 
                [http://rs.tdwg.org/dwc/terms/order] => 
                [http://rs.tdwg.org/dwc/terms/family] => 
                [http://eol.org/schema/EOLid] => 1095
            )
            Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 1
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => Thysanoptera
                [http://eol.org/schema/measurementOfTaxon] => TRUE
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/VT_0001502
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.wikidata.org/entity/Q906470
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => field study
                [http://purl.org/dc/terms/bibliographicCitation] => Lewis, T. and Taylor, L.R. (1965), Diurnal periodicity of flight by insects. Transactions of the Royal Entomological Society of London, 116: 393-435. https://doi.org/10.1111/j.1365-2311.1965.tb02304.x
                [http://purl.org/dc/terms/source] => https://doi.org/10.1111/j.1365-2311.1965.tb02304.x
            )*/
            $eol_id = @$rec['http://eol.org/schema/EOLid'];
            $mType = @$rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $mValue = @$rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
            
            if($eol_id) $this->batch['taxonID'][$eol_id] = '';
            if(in_array($mType, array('https://eol.org/schema/terms/starts_at', 'https://eol.org/schema/terms/stops_at'))) {
                if($mValue) $this->batch['taxonID'][$mValue] = '';
            }
        }
    }
    private function extract_dwca($dwca_file = false, $download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1)) //probably default expires in 1 day 60*60*24*1. Not false.
    {
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit("\n-exit muna-\n");
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_83164/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_83164/'
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
}
?>