<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from 26.php */
class WoRMS_post_process
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        /* not used here...
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        */
    }
    /*================================================================= STARTS HERE ======================================================================*/
    private function get_undefined_parentMeasurementIDs()
    {
        $contents = file_get_contents(CONTENT_RESOURCE_LOCAL_PATH.'26_undefined_parentMeasurementIDs.txt');
        $arr = explode("\n", $contents);
        $arr = array_map('trim', $arr);
        foreach($arr as $a) if($a) $final[$a] = '';
        return $final;
    }
    function start($info)
    {
        $this->undefined_parentMeasurementIDs = self::get_undefined_parentMeasurementIDs();
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        unset($this->occurrenceID_bodyPart);
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
            } // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 286376_1054700
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0191a5b6bbee617be3f101758872e911_26
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://rs.tdwg.org/dwc/terms/habitat
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_01000024
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => inherited from urn:lsid:marinespecies.org:taxname:101, Gastropoda Cuvier, 1795
                [http://purl.org/dc/terms/source] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=1054700
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://purl.org/dc/terms/bibliographicCitation] => 
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => 
            )*/
            //===========================================================================================================================================================
            if($parent_id = $rec['http://eol.org/schema/parentMeasurementID']) {
                if(isset($this->undefined_parentMeasurementIDs[$parent_id])) {
                    $this->occurrence_2be_deleted[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                    continue;
                }
            }
            //===========================================================================================================================================================
            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nprocess_occurrence...\n"; $i = 0;
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
            } //print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0191a5b6bbee617be3f101758872e911_26
                [http://rs.tdwg.org/dwc/terms/taxonID] => 1054700
            )*/
            $uris = array_keys($rec);
            //===========================================================================================================================================================
            $occur_id = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if(isset($this->occurrence_2be_deleted[$occur_id])) continue;
            //===========================================================================================================================================================
            $o = new \eol_schema\Occurrence_specific();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>