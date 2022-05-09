<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from first client: dwca_MoF_fix.php for DATA-1886] 
Right now the fix means: remove the orphan child records in MoF
*/
class DWCA_Measurements_Fix
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
    {   echo "\nDWCA_Measurements_Fix...\n";
        $tables = $info['harvester']->tables;
        /*step 1: loop MoF and get all measurementIDs -> $this->measurementIDs */
        self::process_extension($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'MoF', 'build-up');
        /*step 2: loop MoF again, now delete those recs where parentMeasurementID not in $this->measurementIDs. Start write */
        self::process_extension($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'MoF', 'write');
    }
    private function process_extension($meta, $class, $what)
    {   //print_r($meta);
        echo "\nprocess_extension [$class][$what]...DWCA_Measurements_Fix...\n"; $i = 0;
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
            } //print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => M315930
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => CT100000
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.geonames.org/6252001
                [http://purl.org/dc/terms/source] => https://www.gbif.org/occurrence/map?taxon_key=9576216&geometry=POLYGON((-90.706%2029.151%2C%20-122.761%2047.269%2C%20-75.09%2038.321%2C%20-81.461%2030.757%2C%20-90.706%2029.151%2C%20-90.706%2029.151))
                [http://purl.org/dc/terms/contributor] => Compiler: Anne E Thessen
                [http://eol.org/schema/reference/referenceID] => R01|R02
            )*/
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            if($what == 'build-up') {
                if($class == 'MoF') {
                    $this->measurementIDs[$rec['http://rs.tdwg.org/dwc/terms/measurementID']] = '';
                }
            }
            elseif($what == 'write') {
                if($class == 'MoF') {
                    if($parentMeasurementID = @$rec['http://eol.org/schema/parentMeasurementID']) {
                        if(!isset($this->measurementIDs[$parentMeasurementID])) continue; //remove orphan records in MoF
                    }
                    
                    // /* customize: In SC_unitedstates, please replace the MoF element http://purl.org/dc/terms/contributor 
                    // with https://www.wikidata.org/entity/Q29514511 and the content, `Compiler: Anne E Thessen`, 
                    // with https://orcid.org/0000-0002-2908-3327
                    if($contributor = @$rec['http://purl.org/dc/terms/contributor']) {
                        if($contributor == "Compiler: Anne E Thessen") {
                            unset($rec['http://purl.org/dc/terms/contributor']);
                            $rec['https://www.wikidata.org/entity/Q29514511'] = 'https://orcid.org/0000-0002-2908-3327';
                        }
                    }
                    else unset($rec['http://purl.org/dc/terms/contributor']);
                    // */
                    
                }
                
                if($class == 'MoF')             $o = new \eol_schema\MeasurementOrFact_specific();
                elseif($class == 'occurrence')  $o = new \eol_schema\Occurrence_specific();
                elseif($class == 'reference')   $o = new \eol_schema\Reference();
                $uris = array_keys($rec); //print_r($uris); exit("\ndito eli\n");
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
    }
}
?>