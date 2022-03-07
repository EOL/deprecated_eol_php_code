<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from assign_EOLid.php] */
class DwCA_AssignEOLidAPI
{
    function __construct($archive_builder, $resource_id, $archive_path)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->archive_path = $archive_path;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*1, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->paths['wikidata_hierarchy'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/wikidata/wikidataEOLidMappings.txt';
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        $tables = $info['harvester']->tables; // print_r($tables); exit;
        $extensions = array_keys($tables); //print_r($extensions); exit;
        /*Array(
            [0] => http://rs.tdwg.org/dwc/terms/taxon
        )*/
        
        //step 1: 
        self::get_taxonID_EOLid_list();
        
        //step 2:
        $this->unique_ids = array();
        $tbl = "http://rs.tdwg.org/dwc/terms/taxon";
        self::process_table($tables[$tbl][0], 'write_archive');
    }
    private function get_taxonID_EOLid_list()
    {
        $tmp_file = Functions::save_remote_file_to_local($this->paths[$this->resource_id], $this->download_options);
        $i = 0;
        foreach(new FileIterator($tmp_file) as $line_number => $line) {
            $i++; if(($i % 1000) == 0) echo "\n".number_format($i)." ";
            // if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                $tmp_fields = $fields;
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonID] => Q1001350
                [EOLid] => 52212033
            )*/
            if($val = @$rec['EOLid']) $this->taxonID_EOLid_info[$rec['taxonID']] = $val;
        }
        unlink($tmp_file);
    }
    private function process_table($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_table: [$what] [$meta->file_uri]...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) { $i++;
            if($what != "get_total_images_count_per_taxon") {
                if(($i % 100000) == 0) echo "\n".number_format($i). " [$what]";
            }
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => Q867927
                [http://purl.org/dc/terms/source] => https://www.wikidata.org/wiki/Q867927
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => Q204219
                [http://rs.tdwg.org/dwc/terms/scientificName] => Methanopyri
                [http://rs.tdwg.org/dwc/terms/taxonRank] => class
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => George M. Garrity, 2002
                [http://rs.gbif.org/terms/1.0/canonicalName] => Methanopyri
            )*/
            if($what == 'write_archive') {
                // /* assign the EOLid
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $rec['http://eol.org/schema/EOLid'] = @$this->taxonID_EOLid_info[$taxonID];
                // */
                $o = new \eol_schema\Taxon();
                $uris = array_keys($rec); // print_r($uris); //exit;
                $row_str = "";
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                }
                $unique_field = $o->taxonID;
                if(!isset($this->unique_ids[$unique_field])) {
                    $this->unique_ids[$unique_field] = '';
                    $this->archive_builder->write_object_to_file($o);
                }
            }
        }
    }
    private function get_field_from_uri($uri)
    {
        $field = pathinfo($uri, PATHINFO_BASENAME);
        $parts = explode("#", $field);
        if($parts[0]) $field = $parts[0];
        if(@$parts[1]) $field = $parts[1];
        return $field;
    }
}
?>