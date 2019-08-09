<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from 727.php for DATA-1819] */
class USDAPlants2019
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);

        /*
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], $ret);
        */
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        $i = 0;
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
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => M1
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => O1
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://eol.org/schema/associationID] => 
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/TO_0002725
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://eol.org/schema/terms/perennial
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://rs.tdwg.org/dwc/terms/measurementAccuracy] => 
                [http://eol.org/schema/terms/statisticalMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => Source term: Duration. Some plants have different Durations...
                [http://purl.org/dc/terms/source] => http://plants.usda.gov/core/profile?symbol=ABGR4
                [http://purl.org/dc/terms/bibliographicCitation] => The PLANTS Database, United States Department of Agriculture,...
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => 
            )*/

            /* Metadata: For records with measurementType=A, please add lifeStage=B
            A B
            http://eol.org/schema/terms/SeedlingSurvival    http://purl.obolibrary.org/obo/PPO_0001007
            http://purl.obolibrary.org/obo/FLOPO_0015519    http://purl.obolibrary.org/obo/PO_0009010
            http://purl.obolibrary.org/obo/TO_0000207       http://purl.obolibrary.org/obo/PATO_0001701
            */
            $mtype = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $lifeStage = '';
            if($mtype == 'http://eol.org/schema/terms/SeedlingSurvival') $lifeStage = 'http://purl.obolibrary.org/obo/PPO_0001007';
            // elseif($mtype == 'http://purl.obolibrary.org/obo/FLOPO_0015519') $lifeStage = '';
            elseif($mtype == 'http://purl.obolibrary.org/obo/TO_0000207') $lifeStage = 'http://purl.obolibrary.org/obo/PATO_0001701';

            /* and for records with measurementType=C, please add bodyPart=D
            C D
            http://purl.obolibrary.org/obo/PATO_0001729     http://purl.obolibrary.org/obo/PO_0025034
            http://purl.obolibrary.org/obo/FLOPO_0015519    http://purl.obolibrary.org/obo/PO_0009010
            http://purl.obolibrary.org/obo/TO_0000207       http://purl.obolibrary.org/obo/UBERON_0000468
            */
            $bodyPart = '';
            if($mtype == 'http://purl.obolibrary.org/obo/PATO_0001729') $bodyPart = 'http://purl.obolibrary.org/obo/PO_0025034';
            // elseif($mtype == 'http://purl.obolibrary.org/obo/FLOPO_0015519') $bodyPart = '';
            elseif($mtype == 'http://purl.obolibrary.org/obo/TO_0000207') $bodyPart = 'http://purl.obolibrary.org/obo/UBERON_0000468';
            
            $rec['http://rs.tdwg.org/dwc/terms/lifeStage'] = $lifeStage;
            $this->occurrenceID_bodyPart[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = $bodyPart;

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
    private function process_taxon($meta, $ret)
    {   //print_r($meta);
        $i = 0;
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
            // print_r($rec); exit;
            /**/
            
            $o = new \eol_schema\Taxon();
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
        $i = 0;
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
            print_r($rec); exit;
            /**/
            
            if($bodyPart = @$this->occurrenceID_bodyPart[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']]) $rec['http:/eol.org/globi/terms/bodyPart'] = $bodyPart
            
            $o = new \eol_schema\Occurrence();
            $uris = array_keys($rec);
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
