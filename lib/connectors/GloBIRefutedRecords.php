<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from globi_refuted.php] 
Status: WAS NOT USED
*/
class GloBIRefutedRecords
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
        
        //step 1 is build info list
        self::process_association($tables['http://eol.org/schema/association'][0], 'build info');
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'build info');
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'build info');
        //step 2 write report
        
        print_r($this->debug);
    }
    private function process_association($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_association [$what]\n";
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
                [http://eol.org/schema/associationID] => globi:assoc:1-FBC:FB:SpecCode:3128-ENDOPARASITE_OF-GBIF:5967411
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => globi:occur:source:1-FBC:FB:SpecCode:3128-ENDOPARASITE_OF
                [http://eol.org/schema/associationType] => http://purl.obolibrary.org/obo/RO_0002634
                [http://eol.org/schema/targetOccurrenceID] => globi:occur:target:1-FBC:FB:SpecCode:3128-ENDOPARASITE_OF-GBIF:5967411
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://purl.org/dc/terms/source] => Soleto-Casas RC and Simões N (2020). Parasitic and commensal invertebrates of echinoderms from American Tropical And Subtropical Atlantic manually extracted from literature.. Accessed at <https://zenodo.org/record/3742346/files/BDMYRepository/Echino-Interactions-V3.zip> on 25 May 2020.
                [http://purl.org/dc/terms/bibliographicCitation] => 
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => globi:ref:1
            )*/
            $associationType = $rec['http://eol.org/schema/associationType'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $targetOccurrenceID = $rec['http://eol.org/schema/targetOccurrenceID'];
            if($what == 'build info') {
            }
            elseif($what == 'create extension') {}
        }
    }
    private function process_occurrence($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_occurrence [$what]\n";
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
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => globi:occur:source:1-FBC:FB:SpecCode:3128-ENDOPARASITE_OF
                [http://rs.tdwg.org/dwc/terms/taxonID] => FBC:FB:SpecCode:3128
                [http://rs.tdwg.org/dwc/terms/institutionCode] => 
                [http://rs.tdwg.org/dwc/terms/collectionCode] => 
                [http://rs.tdwg.org/dwc/terms/catalogNumber] => 
                [http://rs.tdwg.org/dwc/terms/sex] => 
                [http://rs.tdwg.org/dwc/terms/lifeStage] => 
                [http://rs.tdwg.org/dwc/terms/reproductiveCondition] => 
                [http://rs.tdwg.org/dwc/terms/behavior] => 
                [http://rs.tdwg.org/dwc/terms/establishmentMeans] => 
                [http://rs.tdwg.org/dwc/terms/occurrenceRemarks] => 
                [http://rs.tdwg.org/dwc/terms/individualCount] => 
                [http://rs.tdwg.org/dwc/terms/preparations] => 
                [http://rs.tdwg.org/dwc/terms/fieldNotes] => 
                [http://rs.tdwg.org/dwc/terms/samplingProtocol] => 
                [http://rs.tdwg.org/dwc/terms/samplingEffort] => 
                [http://rs.tdwg.org/dwc/terms/identifiedBy] => 
                [http://rs.tdwg.org/dwc/terms/dateIdentified] => 
                [http://rs.tdwg.org/dwc/terms/eventDate] => 
                [http://purl.org/dc/terms/modified] => 
                [http://rs.tdwg.org/dwc/terms/locality] => 
                [http://rs.tdwg.org/dwc/terms/decimalLatitude] => 
                [http://rs.tdwg.org/dwc/terms/decimalLongitude] => 
                [http://rs.tdwg.org/dwc/terms/verbatimLatitude] => 
                [http://rs.tdwg.org/dwc/terms/verbatimLongitude] => 
                [http://rs.tdwg.org/dwc/terms/verbatimElevation] => 
                [http://rs.tdwg.org/dwc/terms/basisOfRecord] => 
                [http:/eol.org/globi/terms/physiologicalState] => 
                [http:/eol.org/globi/terms/bodyPart] => 
            )*/
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($what == 'build info') {
            }
            elseif($what == 'create extension') {}
            
            // if($i >= 10) break; //debug only
        }
    }
    private function process_taxon($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_taxon [$what]\n";
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
                [http://rs.tdwg.org/dwc/terms/taxonID] => EOL:229668
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://eol.org/pages/229668
                [http://eol.org/schema/reference/referenceID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => Dryas octopetala
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                [http://rs.tdwg.org/dwc/terms/kingdom] => Plantae
                [http://rs.tdwg.org/dwc/terms/phylum] => Tracheophyta
                [http://rs.tdwg.org/dwc/terms/class] => Magnoliopsida
                [http://rs.tdwg.org/dwc/terms/order] => Rosales
                [http://rs.tdwg.org/dwc/terms/family] => Rosaceae
                [http://rs.tdwg.org/dwc/terms/genus] => Dryas
                [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => 
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
            )*/
            if($what == 'build info') {
            }
            elseif($what == 'create extension') {}
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
