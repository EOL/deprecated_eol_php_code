<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from assign_EOLid.php] */
class DwCA_RunGNParser
{
    function __construct($archive_builder, $resource_id, $archive_path)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->archive_path = $archive_path;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*1, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->paths['wikidata_hierarchy'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/wikidata/wikidataEOLidMappings.txt';
        $this->service['GNParser'] = 'https://parser.globalnames.org/api/v1/';

        /*
        Install gnparser in command line: https://github.com/gnames/gnparser/blob/master/README.md#installation

        gnparser file -f json-compact --input step3_scinames.txt --output step3_gnparsed.txt
        gnparser name -f simple 'Tricornina (Bicornina) jordan, 1964'
        gnparser name -f simple 'Ceroputo pilosellae Šulc, 1898'
        gnparser name -f simple 'The Myxobacteria'
        */
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        $tables = $info['harvester']->tables; // print_r($tables); exit;
        $extensions = array_keys($tables); //print_r($extensions); exit;
        /*Array(
            [0] => http://rs.tdwg.org/dwc/terms/taxon
        )*/
        $tbl = "http://rs.tdwg.org/dwc/terms/taxon";
        self::process_table($tables[$tbl][0], 'write_archive');
    }
    private function process_table($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_table: [$what] [$meta->file_uri]...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) { $i++;
            if(($i % 10000) == 0) echo "\n".number_format($i)." - ";
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
                [http://rs.tdwg.org/dwc/terms/taxonID] => 12
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://reflora.jbrj.gov.br/reflora/listaBrasil/FichaPublicaTaxonUC/FichaPublicaTaxonUC.do?id=FB12
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 120181
                [http://rs.tdwg.org/dwc/terms/scientificName] => Agaricales
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                [http://rs.tdwg.org/dwc/terms/kingdom] => Fungi
                [http://rs.tdwg.org/dwc/terms/phylum] => Basidiomycota
                [http://rs.tdwg.org/dwc/terms/class] => 
                [http://rs.tdwg.org/dwc/terms/order] => Agaricales
                [http://rs.tdwg.org/dwc/terms/family] => 
                [http://rs.tdwg.org/dwc/terms/genus] => 
                [http://rs.tdwg.org/dwc/terms/taxonRank] => order
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => 
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted
                [http://purl.org/dc/terms/modified] => 2018-08-10 11:58:06.954
            )*/

            /* caching           122658/6=20443
            $m = 20443; // divided by 6
            $m = 30665; // divided by 4
            // if($i >= 1 && $i <= $m) {}
            // if($i >= $m && $i <= $m*2) {}
            // if($i >= $m*2 && $i <= $m*3) {}
            if($i >= $m*3 && $i <= $m*4) {}
            // if($i >= $m*4 && $i <= $m*5) {} 
            // if($i >= $m*5 && $i <= $m*6) {} 
            else continue;
            */

            if($what == 'write_archive') {
                // /* assign canonical name
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $scientificName = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];

                // $rec['http://rs.tdwg.org/dwc/terms/canonicalName'] = self::lookup_canonical_name($scientificName, 'simple'); //working but too many calls
                $rec['http://rs.tdwg.org/dwc/terms/canonicalName'] = self::run_gnparser($scientificName, 'simple'); //working but too many calls

                // */

                // print_r($rec); exit;
                $o = new \eol_schema\Taxon();
                $uris = array_keys($rec); // print_r($uris); //exit;
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
            // if($i >= 5) break;
        }
    }
    function run_gnparser($sciname, $type)
    {   // e.g. gnparser -f pretty "Quadrella steyermarkii (Standl.) Iltis &amp; Cornejo"
        if($sciname = trim($sciname)) {
            $cmd = 'gnparser -f pretty "'.$sciname.'"';
            if($json = shell_exec($cmd)) { //echo "\n$json\n"; //good debug
                if($obj = json_decode($json)) { //print_r($obj); //exit("\nstop muna\n"); //good debug
                    if(@$obj->canonical) {
                        if($type == 'simple') return $obj->canonical->simple;
                        elseif($type == 'full') return $obj->canonical->full;
                        else exit("\nUndefined type. Will exit.\n");    
                    }
                }
            }    
        }
    }
    function lookup_canonical_name($sciname, $type)
    {
        $obj = self::call_gnparser_service($sciname);
        if(!$obj) return;
        // print_r($obj); exit;
        /*Array(
        [0] => stdClass Object(
                [parsed] => 1
                [quality] => 1
                [verbatim] => Agaricales
                [normalized] => Agaricales
                [canonical] => stdClass Object(
                        [stemmed] => Agaricales
                        [simple] => Agaricales
                        [full] => Agaricales
                    )
                [cardinality] => 1
                [id] => e7410ae0-31ac-584b-a362-12cccbd99527
                [parserVersion] => v1.7.1
            )
        )*/
        $obj = $obj[0];
        if($type == 'simple') return $obj->canonical->simple;
        elseif($type == 'full') return $obj->canonical->full;
        else exit("\nUndefined type. Will exit.\n");
    }
    private function call_gnparser_service($sciname)
    {
        $sciname = str_replace(" ", "+", $sciname);
        $sciname = str_replace("&", "%26", $sciname);
        $url = $this->service['GNParser'].$sciname;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        $options['resource_id'] = 'gnparser';
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json); // print_r($obj); //exit;
            return $obj;
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
    /* copied template
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
            if($val = @$rec['EOLid']) $this->taxonID_EOLid_info[$rec['taxonID']] = $val;
        }
        unlink($tmp_file);
    }
    */
}
?>