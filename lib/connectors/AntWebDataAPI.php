<?php
namespace php_active_record;
/*  connector: [dwca_utility.php _ 24]
    
*/
class AntWebDataAPI
{
    function __construct($taxon_ids, $archive_builder)
    {
        $this->taxon_ids = $taxon_ids;
        $this->archive_builder = $archive_builder;
        
        // $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        // $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        // $this->taxon_ids = array();
        // $this->occurrence_ids = array();
        // $this->media_ids = array();
        // $this->agent_ids = array();
        $this->debug = array();
        $this->api['genus_list'] = 'http://www.antweb.org/api/v2/?rank=genus&limit=100&offset=';
        $this->api['specimens'] = 'http://www.antweb.org/api/v2/?limit=100&offset='; //&genus=Acanthognathus
        
        $this->limit = 100;
        $this->download_options = array("timeout" => 60*60, "expire_seconds" => 60*60*24*25);
    }
    
    function start($harvester, $row_type)
    {
        // print_r($this->taxon_ids); exit;
        $this->uri_values = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        // print_r($this->uri_values);
        // echo("\n Philippines: ".$this->uri_values['Philippines']."\n"); exit;
        $genus_list = self::get_all_genus($harvester->process_row_type($row_type));
        echo "\n total genus: ".count($genus_list);
        /* $genus_list = self::get_all_genus_using_api(); //working but instead of genus; family values are given by API */
        self::process_genus($genus_list);
        print_r($this->debug);
    }
    
    private function process_genus($genus_list)
    {
        $i = 0; $total = count($genus_list);
        foreach($genus_list as $genus) {
            $i++; echo "\n processing $genus... $i of $total";
            $specimens = self::get_specimens_per_genus($genus);
            if(!$specimens) continue;
            // print_r($specimens);
            echo("\n".count($specimens)."\n");
            foreach($specimens as $rec)
            {   /* Array(
                       [url] => http://antweb.org/api/v2/?occurrenceId=CAS:ANTWEB:jtl725991
                       [catalogNumber] => jtl725991
                       [family] => formicidae
                       [subfamily] => myrmicinae
                       [genus] => Acanthomyrmex
                       [specificEpithet] => indet
                       [scientific_name] => acanthomyrmex indet
                       [typeStatus] => 
                       [stateProvince] => Sabah
                       [country] => Malaysia
                       [dateIdentified] => 2014-10-01
                       [dateCollected] => 2014-08-02
                       [habitat] => mature wet forest ex sifted leaf litter
                       [minimumElevationInMeters] => 350
                       [biogeographicregion] => Indomalaya
                       [geojson] => Array(
                               [type] => point
                               [coord] => Array(
                                       [0] => 4.64045
                                       [1] => 116.61342)
                           )
                   )*/
                $rec['taxon_id'] = strtolower($rec['scientific_name']);
                $rec['catnum'] = $rec['url'];
                if(!$rec['url']) {
                    print_r($rec);
                    exit("\ninvestigate no url\n");
                }
                
                if($country = @$rec['country']) {
                    if($country_uri = @$this->uri_values[$country]) {
                        if(!isset($this->taxon_ids[$rec['taxon_id']])) self::add_taxon($rec);
                        self::add_string_types($rec, $country_uri, "http://eol.org/schema/terms/Present", "true");
                    }
                    else $this->debug['undefined country'][$country] = '';
                }
                
                if($habitat = @$rec['habitat']) {
                    if($habitat_uri = @$this->uri_values[$habitat]) {
                        if(!isset($this->taxon_ids[$rec['taxon_id']])) self::add_taxon($rec);
                        self::add_string_types($rec, $habitat_uri, "http://eol.org/schema/terms/Habitat", "true");
                    }
                    else $this->debug['undefined habitat'][$habitat] = '';
                }
                
            }
            // break; //debug - get only first genus
        }
    }
    private function add_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['taxon_id'];
        $taxon->scientificName  = ucfirst($rec['scientific_name']);
        if($family = @$rec['family']) $taxon->family = ucfirst($family);
        /*
        $taxon->kingdom         = $t['dwc_Kingdom'];
        $taxon->phylum          = $t['dwc_Phylum'];
        $taxon->class           = $t['dwc_Class'];
        $taxon->order           = $t['dwc_Order'];
        $taxon->genus           = $t['dwc_Genus'];
        $taxon->furtherInformationURL = $t['dc_source'];
        if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        */
        $this->taxon_ids[$taxon->taxonID] = '';
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"];
        $occurrence_id = $catnum; // simply used catnum
        
        $unique_id = md5($taxon_id.$measurementType.$value);

        $cont = $this->add_occurrence($taxon_id, $occurrence_id, $rec, $unique_id);

        if($cont) {
            $m = new \eol_schema\MeasurementOrFact();
            $m->occurrenceID       = $occurrence_id;
            $m->measurementOfTaxon = $measurementOfTaxon;
            if($measurementOfTaxon == "true") {
                $m->source      = @$rec["url"];
                $m->contributor = @$rec["contributor"];
                if($referenceID = @$rec["referenceID"]) $m->referenceID = $referenceID;
            }
            $m->measurementType  = $measurementType;
            $m->measurementValue = $value;
            // $m->bibliographicCitation = $this->bibliographic_citation;
            if($val = @$rec['measurementUnit'])     $m->measurementUnit = $val;
            if($val = @$rec['measurementMethod'])   $m->measurementMethod = $val;
            if($val = @$rec['statisticalMethod'])   $m->statisticalMethod = $val;
            if($val = @$rec['measurementRemarks'])  $m->measurementRemarks = $val;
            $this->archive_builder->write_object_to_file($m);
        }
    }
    private function add_occurrence($taxon_id, $occurrence_id, $rec, $unique_id)
    {
        if(isset($this->occurrence_ids[$unique_id])) return false;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        /* Array(
                   [url] => http://antweb.org/api/v2/?occurrenceId=CAS:ANTWEB:jtl725991
                   *[catalogNumber] => jtl725991
                   [family] => formicidae
                   [subfamily] => myrmicinae
                   [genus] => Acanthomyrmex
                   [specificEpithet] => indet
                   [scientific_name] => acanthomyrmex indet
                   [typeStatus] => 
                   *[stateProvince] => Sabah
                   *[country] => Malaysia
                   *[dateIdentified] => 2014-10-01
                   *[dateCollected] => 2014-08-02
                   [habitat] => mature wet forest ex sifted leaf litter
                   [minimumElevationInMeters] => 350
                   *[biogeographicregion] => Indomalaya
                   [geojson] => Array(
                           [type] => point
                           [coord] => Array(
                                   [0] => 4.64045
                                   [1] => 116.61342)
                       )
               )*/
        $o->catalogNumber = @$rec['catalogNumber'];
        $o->dateIdentified = @$rec['dateIdentified'];
        $o->eventDate = @$rec['dateCollected'];
        $o->locality = '';
        if($val = @$rec['stateProvince']) {
            if($o->locality) $o->locality .= " stateProvince: $val.";
            else             $o->locality  = " stateProvince: $val.";
        }
        if($val = @$rec['country']) {
            if($o->locality) $o->locality .= " country: $val.";
            else             $o->locality  = " country: $val.";
        }
        if($val = @$rec['biogeographicregion']) {
            if($o->locality) $o->locality .= " biogeographicregion: $val.";
            else             $o->locality  = " biogeographicregion: $val.";
        }
        // $o->decimalLatitude
        // $o->decimalLongitude
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$unique_id] = '';
        return true;
    }

    private function get_specimens_per_genus($genus)
    {
        $final = array();
        $offset = 0;
        while(true) {
            $url = $this->api['specimens'].$offset."&genus=$genus";
            // echo "\n[$url]";
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $arr = json_decode($json, true);
                if(isset($arr['specimens']['empty_set'])) break;
                else $final = array_merge($final, $arr['specimens']);
                if(count($arr['specimens']) < $this->limit) break;
            }
            $offset += $this->limit;
        }
        return $final;
    }


    private function get_all_genus_using_api()
    {
        $final = array();
        $offset = 0;
        while(true) {
            $url = $this->api['genus_list'].$offset;
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $arr = json_decode($json, true);
                foreach($arr['specimens'] as $specimen) $final[] = $specimen['genus'];
                if(count($arr['specimens']) < $this->limit) break;
            }
            $offset += $this->limit;
        }
        return array_unique($final);
    }
    
    // /* working well but not used. Used API instead
    private function get_all_genus($records)
    {
        $genus_list = array();
        foreach($records as $rec) {
            // $keys = array_keys($rec); print_r($keys);
            $sciname = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
            $arr = explode(" ", $sciname);
            $genus = $arr[0];
            $genus_list[$genus] = '';
        }
        return array_keys($genus_list);
    }
    // */
}
?>
