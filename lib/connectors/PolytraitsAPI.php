<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from polytraits.php] */
class PolytraitsAPI extends ContributorsMapAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->debug = array();
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        if(in_array($this->resource_id, array('Polytraits'))) {
            $options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1);
            $this->contributor_mappings = $this->get_contributor_mappings($this->resource_id, $options);
            // print_r($this->contributor_mappings);
            echo "\n contributor_mappings: ".count($this->contributor_mappings)."\n";
            // exit("oks [$this->resource_id]");
        }
        
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        if($this->debug) print_r($this->debug);
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...\n"; $i = 0;
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
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 1
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 1725
                [http://eol.org/schema/measurementOfTaxon] => TRUE
                [http://eol.org/schema/associationID] => 
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/SizeClass
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://polytraits.lifewatchgreece.eu/terms/BS_3
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://eol.org/schema/terms/statisticalMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 9/4/10
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 508
                [http://purl.org/dc/terms/source] => http://polytraits.lifewatchgreece.eu
                [http://purl.org/dc/terms/bibliographicCitation] => Polytraits Team (2018). Polytraits: A database on biological traits of polychaetes.. LifewatchGreece, Hellenic Centre for Marine Research. Accessed on 2018-08-16. Available from http://polytraits.lifewatchgreece.eu
                [http://purl.org/dc/terms/contributor] => Panagiotis Dimitriou
                [http://eol.org/schema/reference/referenceID] => 1406
            )*/
            $contributor = $rec['http://purl.org/dc/terms/contributor'];
            if($uri = @$this->contributor_mappings[$contributor]) {}
            else { //no mapping yet for this contributor
                $this->debug['undefined contributor'][$contributor] = '';
                $uri = $contributor;
            }
            $rec['http://purl.org/dc/terms/contributor'] = $uri;
            //===========================================================================================================================================================
            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
