<?php
namespace php_active_record;
/* connector: [] Flower color EOL photos connector
We received a spreadsheet from the partner.
Connector downloads the file, extracts, reads it, assembles the data and generates the EOL DWC-A resource.
*/
class FlowerColorAPI
{
    function __construct($params)
    {
        $this->params = $params;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $params["resource_id"] . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->spreadsheet_file = $params["spreadsheet_file"];
        $this->occurrence_ids = array();
    }

    function get_all_taxa()
    {
        $spreadsheet_options = array("cache" => 1, "timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2);
        $spreadsheet_options["spreadsheet_file"] = $this->spreadsheet_file;
        // $spreadsheet_options["expire_seconds"] = 0; // false => won't expire; 0 => expires now
        if($records = self::get_array_from_spreadsheet($spreadsheet_options))
        {
            self::create_instances_from_taxon_object($records);
            $this->archive_builder->finalize(TRUE);
        }
    }

    private function create_instances_from_taxon_object($records)
    {
        foreach($records as $rec)
        {
            if(in_array($rec["Species"], array("RED", "GREEN", "CYAN", "MAGENTA", "BLUE"))) continue;
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = md5($rec["Species"]);
            $taxon->scientificName              = $rec["Species"];
            $taxon->furtherInformationURL       = $rec["Link"];
            $taxon->family                      = $rec["Family"];
            $taxon->taxonRank                   = "species";
            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
            
            //structured data & common name
            $rec["taxon_id"] = $taxon->taxonID;
            $rec["catnum"]   = $rec["record_id"];
            if($rec["Closest PATO color"]) self::get_color($rec);
            if($rec["Common_Name"])        self::get_vernacular($rec);
        }
    }

    private function get_color($rec) //color structured data
    {
        $rec["source"]              = $rec["Link"];
        $rec["measurementRemarks"]  = $rec["Notes"];
        if($val = $rec["Closest PATO color"])
        {
            self::add_string_types($rec, $val, "http://purl.obolibrary.org/obo/TO_0000537", "true"); // flower color
        }
    }

    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"], $rec);
        $m->occurrenceID        = $occurrence_id;
        $m->measurementOfTaxon  = $measurementOfTaxon;
        $m->measurementType     = $measurementType;
        $m->measurementValue    = $value;
        
        if($measurementOfTaxon == "true")
        {   // so that measurementRemarks (and source, contributor, etc.) appears only once in the [measurement_or_fact.tab]
            $m->measurementRemarks      = @$rec["measurementRemarks"];
            $m->source                  = @$rec["source"];
            // $m->bibliographicCitation   = '';
            // $m->contributor             = '';
            if($referenceID = @$rec["reference_id"]) $m->referenceID = $referenceID;
        }
        // $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID      = $taxon_id;

        /* not used at the moment
        [Alpine] => 
        [Locality_Notes] => native
        [R] => 210
        [G] => 210
        [B] => 210
        [h] => 0
        [s] => 0
        [v] => 0.823529411764706
        [h_degree] => 0
        [s_percent] => 0
        [v_percnet] => 82
        [converted_hue] => -60
        [specimen_number] => 946
        [Blue in Name?] => 
        [Purple in Name?] => 
        */

        if($val = @$rec["Locality"])            $o->locality = $val;
        if($val = @$rec["Elevation_meters"])    $o->verbatimElevation = $val;
        if($val = @$rec["Lat"])                 $o->decimalLatitude = $val;
        if($val = @$rec["Long"])                $o->decimalLongitude = $val;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
    }

    private function get_vernacular($rec)
    {
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec["taxon_id"];
        $v->vernacularName  = $rec["Common_Name"];
        $v->language        = 'en';
        if(!isset($this->taxon_vernacular[$v->vernacularName]))
        {
            $this->taxon_vernacular[$v->vernacularName] = '';
            $this->archive_builder->write_object_to_file($v);
        }
        // $v->source          = '';
        // $v->isPreferredName = '';
    }

    public function get_array_from_spreadsheet($options)
    {
        $records = array();
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        if($spreadsheet = @$options["spreadsheet_file"])
        {
            if($arr = $func->convert_spreadsheet($spreadsheet, 0, $options))
            {
                $fields = array_keys($arr);
                $fields[] = "record_id";
                $i = 0;
                foreach($arr[$fields[0]] as $val)
                {
                    $rec = array();
                    foreach($fields as $field)
                    {
                        if($field == "record_id") $rec[$field] = $i + 1;
                        else                      $rec[$field] = $arr[$field][$i];
                    }
                    if($rec) $records[] = $rec;
                    $i++;
                }
            }
        }
        return $records;
    }

}
?>