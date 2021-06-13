<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from first client: globi_data.php for DATA-1886] */
class DWCA_Associations_Fix
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
        $this->debug = array();
    }
    function start($info)
    {
        /* step 1: get all occurrenceIDs stored in a txt file */
        $url = CONTENT_RESOURCE_LOCAL_PATH . "reports/" . "globi_associations" . "_source_target_NotInOccurrence.txt";
        $lines = file($url);
        $lines = array_map('trim', $lines);
        // print_r($lines); echo "\n".count($lines)."\n"; exit;
        foreach($lines as $line) {
            $arr = explode("\t", $line); //print_r($arr);
            $IDs[$arr[1]][$arr[0]] = '';
        }
        // print_r($IDs); echo "\n".count($IDs)."\n"; exit;
        /*  [source] => Array(
                    [xx1] =>
                    [xx2] =>
                )
            [target] => Array(
                    [globi:occur:source:29847854-http://taxon-concept.plazi.org/id/Animalia/simplex_Topsent_1892-VISITS_FLOWERS_OF] => 
                    [globi:occur:source:33893565-http://taxon-concept.plazi.org/id/Animalia/simplex_Topsent_1892-VISITS_FLOWERS_OF] => 
                )
        */
        unset($lines);
        /* step 2: loop to Association and remove those entries where source or target is found in $IDs */
        $tables = $info['harvester']->tables;
        // /* for Globi, these extensions are too big to be processed in DwCA_Utility. Memory issue. This just copies, carryover of the table.
        self::process_extension($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'occurrence');
        self::process_extension($tables['http://eol.org/schema/reference/reference'][0], 'reference');
        // */
        self::process_association($tables['http://eol.org/schema/association'][0], $IDs); //main process here
    }
    private function process_association($meta, $IDs)
    {   //print_r($meta);
        echo "\nprocess_association...\n"; $i = 0;
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
                [http://eol.org/schema/associationID] => globi:assoc:2-ITIS:554049-ATE-ITIS:24773
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => globi:occur:source:2-ITIS:554049-ATE
                [http://eol.org/schema/associationType] => http://purl.obolibrary.org/obo/RO_0002470
                [http://eol.org/schema/targetOccurrenceID] => globi:occur:target:2-ITIS:554049-ATE-ITIS:24773
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://purl.org/dc/terms/source] => Groom, Q.J., Maarten De Groot, M. & Marčiulynienė, D. (2020) Species interation data manually extracted from literature for species .
                [http://purl.org/dc/terms/bibliographicCitation] => 
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => globi:ref:2
            )*/
            //===========================================================================================================================================================
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $targetOccurrenceID = $rec['http://eol.org/schema/targetOccurrenceID'];
            if(isset($IDs['source'][$occurrenceID])) continue;
            if(isset($IDs['target'][$targetOccurrenceID])) continue;
            //===========================================================================================================================================================
            $o = new \eol_schema\Association();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    private function process_extension($meta, $class)
    {   //print_r($meta);
        echo "\nprocess_extension [$class]...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                // /* some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field['term']);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];
                // */
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            } print_r($rec); //exit;
            //===========================================================================================================================================================
            if($class == 'occurrence') { //for Globi
                if(isset($rec['basisOfRecord']))        unset($rec['basisOfRecord']);
                if(isset($rec['physiologicalState']))   unset($rec['physiologicalState']);
                if(isset($rec['bodyPart']))             unset($rec['bodyPart']);
            }
            //===========================================================================================================================================================
            if($class == 'occurrence') $o = new \eol_schema\Occurrence();
            if($class == 'reference') $o = new \eol_schema\Reference();
            $uris = array_keys($rec); print_r($uris); exit("\ndito eli\n");
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
}
?>