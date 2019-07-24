<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from species_checklists.php] */
class SpeciesChecklistAPI
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
        $tbls = array_keys($tables); print_r($tbls);
        foreach($tbls as $tbl) {
            self::process_extension($tables[$tbl][0]); //this is just to copy extension but with customization as described in DATA-1817
        }
    }
    private function get_dwca_short_fields($meta_fields)
    {
        foreach($meta_fields as $f) $final[] = pathinfo($f['term'], PATHINFO_FILENAME);
        return $final;
    }
    private function process_extension($meta)
    {   //print_r($meta->fields); //exit;
        echo "\nProcesing $meta->row_type ...\n";
        $dwca_short_fields = self::get_dwca_short_fields($meta->fields);
        $class = strtolower(pathinfo($meta->row_type, PATHINFO_FILENAME));
        
        if($class != "taxon") {
            if(isset($this->unique_taxon_ids)) $this->unique_taxon_ids = ''; //just remove from memory
        }
        
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            /* not followed since meta.xml is not reflective of the actual dwca. DwCA seems manually created.
            if($meta->ignore_header_lines && $i == 1) continue;
            */
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            
            // print_r($dwca_short_fields); print_r($tmp); exit;

            if(in_array($tmp[0], $dwca_short_fields)) continue; //this means if first row is the header fields then ignore

            // echo "\n".count($meta->fields);
            // echo "\n".count($tmp); exit("\n");
            /* commented since child records have lesser columns, but should be accepted.
            if(count($meta->fields) != count($tmp)) continue;
            */
            
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = @$tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /**/

            if    ($class == "vernacular")          $o = new \eol_schema\VernacularName();
            elseif($class == "agent")               $o = new \eol_schema\Agent();
            elseif($class == "reference")           $o = new \eol_schema\Reference();
            elseif($class == "taxon")               $o = new \eol_schema\Taxon();
            elseif($class == "document")            $o = new \eol_schema\MediaResource();
            elseif($class == "occurrence")          $o = new \eol_schema\Occurrence();
            elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
            else {
                print_r($meta);
                exit("\nUndefined class [$class]\n");
            }

            if($class == 'taxon') { //print_r($rec); exit;
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/taxonID] => T100000
                    [http://rs.tdwg.org/dwc/terms/scientificName] => Argyrosomus inodorus
                    [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => T100001
                    [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                )*/
                if(isset($this->unique_taxon_ids[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue; //will cause duplicate taxonID
                else $this->unique_taxon_ids[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = '';
            }
            
            if($class == 'measurementorfact') { // print_r($rec); exit;
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/measurementID] => measurementID
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => occurrenceID
                    [http://eol.org/schema/parentMeasurementID] => parentMeasurementID
                    [http://eol.org/schema/measurementOfTaxon] => measurementOfTaxon
                    [http://rs.tdwg.org/dwc/terms/measurementType] => measurementType
                    [http://rs.tdwg.org/dwc/terms/measurementValue] => measurementValue
                    [http://eol.org/schema/reference/referenceID] => referenceID
                    [http://purl.org/dc/terms/contributor] => contributor
                    [http://purl.org/dc/terms/source] => source
                )*/
                /* This means children record should be presented correctly. */
                if(!$rec['http://rs.tdwg.org/dwc/terms/measurementID'] || !$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']) { //means probably a child record
                    if(!$rec['http://eol.org/schema/parentMeasurementID']) {
                        print_r($rec); exit("\nThis child record has to have a parentMeasurementID\n");
                    }
                    else $rec['http://rs.tdwg.org/dwc/terms/measurementID'] = $rec['http://eol.org/schema/parentMeasurementID']."_".pathinfo($rec['http://rs.tdwg.org/dwc/terms/measurementType'], PATHINFO_BASENAME);
                }
            }
            
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
    /* this is just to copy the extension as is. No customization.
    private function process_generic($meta)
    {   //print_r($meta);
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
            $o = new \eol_schema\Association();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    */
    
}
?>
