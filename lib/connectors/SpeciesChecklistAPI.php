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
        require_library('connectors/GBIFoccurrenceAPI_DwCA');
        $this->gbif_func = new GBIFoccurrenceAPI_DwCA();
        
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
                /* This will format source based on DATA-1817 */
                if($val = $rec['http://purl.org/dc/terms/source']) $rec['http://purl.org/dc/terms/source'] = self::convert_2gbif_url($val);
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
    function convert_2gbif_url($url)
    {   /*
        change old % 28 -->> (
        change old % 29 -->> )
        change both % 20 -->> space
        change both % 2C -->> comma
        */
        $url = str_replace("%28", "(", $url);
        $url = str_replace("%29", ")", $url);
        $url = str_replace("%20", " ", $url);
        $url = str_replace("%2C", ",", $url);
/*        
http://gimmefreshdata.github.io/?limit=5000000&taxonSelector=Enhydra lutris&traitSelector=&wktString
=GEOMETRYCOLLECTION(POLYGON ((-65.022 63.392, -74.232 64.672, -84.915 71.353, -68.482 68.795, -67.685 66.286, -65.022 63.392)),
                    POLYGON ((-123.126 49.079, -129.911 53.771, -125.34 69.52, -97.874 68.532, -85.754 68.217, -91.525 63.582, -77.684 60.542, -64.072 59.817, -55.85 53.249, -64.912 43.79, -123.126 49.079))
                   )

https://www.gbif.org/occurrence/map?geometry=POLYGON((-65.022 63.392, -74.232 64.672, -84.915 71.353, -68.482 68.795, -67.685 66.286, -65.022 63.392))
                                   &geometry=POLYGON((-123.126 49.079, -129.911 53.771, -125.34 69.52, -97.874 68.532, -85.754 68.217, -91.525 63.582, -77.684 60.542, -64.072 59.817, -55.85 53.249, -64.912 43.79, -123.126 49.079))
*/
        if(preg_match("/taxonSelector=(.*?)\&/ims", $url, $arr)) {
            $sciname = $arr[1];
            if($taxon_key = $this->gbif_func->get_usage_key($sciname)) {}
            else return '';
        }
        else return '';

        if(preg_match_all("/POLYGON \(\((.*?)\)\)/ims", $url, $arr))    {} //print_r($arr[1]);
        elseif(preg_match_all("/POLYGON\(\((.*?)\)\)/ims", $url, $arr)) {} //print_r($arr[1]);
        else exit("\n========================\n[$url]\n========================\nInvestigate url format\n");
        
        $this->pre_gbif_url = 'https://www.gbif.org/occurrence/map?taxon_key=TAXONKEY&';
        foreach($arr[1] as $str) $parts[] = 'geometry=POLYGON(('.$str.'))';
        // print_r($parts);
        $final = $this->pre_gbif_url . implode("&", $parts);
        $final = str_replace('TAXONKEY', $taxon_key, $final);
        $final = str_replace(" ", "%20", $final);
        $final = str_replace(",", "%2C", $final);
        echo "\n[$sciname]\n$final\n";
        return $final;
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
