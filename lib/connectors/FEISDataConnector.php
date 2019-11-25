<?php
namespace php_active_record;
/* connector: [750]
DATA-1413 FEIS structured data 
The FEIS portal export features provide 12 csv files: http://www.feis-crs.org/beta/faces/SearchByOther.xhtml
This connector parses the csv files, gets the invasiveness, nativity and life_form info and other metadata and generates the EOL archive file.
*/
class FEISDataConnector
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->debug = array();
        $this->export_basenames = array("Invasive"      => "http://eol.org/schema/terms/InvasiveRange",
                                        "Noninvasive"   => "http://eol.org/schema/terms/NonInvasiveRange",
                                        "Native"        => "http://eol.org/schema/terms/NativeRange",
                                        "Nonnative"     => "http://eol.org/schema/terms/IntroducedRange",
                                        "Vine"          => "http://eol.org/schema/terms/vine",
                                        "Tree"          => "http://eol.org/schema/terms/tree",
                                        "Shrub"         => "http://eol.org/schema/terms/shrub",
                                        "Graminoid"     => "http://eol.org/schema/terms/graminoid",
                                        "Forb"          => "http://eol.org/schema/terms/forbHerb",
                                        "Fern"          => "http://eol.org/schema/terms/forbHerb",
                                        "Bryophyte"     => "http://eol.org/schema/terms/nonvascular"
                                        ); // "Cactus" is excluded (DATA-1413)

        $this->life_forms = array("Vine", "Tree", "Shrub", "Graminoid", "Forb", "Fern", "Bryophyte");
        // $this->species_list_export = "https://dl.dropboxusercontent.com/u/7597512/FEIS/FEIS.zip";
        // $this->species_list_export = "http://localhost/cp_new/FEIS/FEIS.zip";
        $this->species_list_export = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/FEIS/FEIS.zip";
    }
    function generate_FEIS_data()
    {
        /* START DATA-1841 terms remapping */
        require_library('connectors/TraitGeneric');
        $func = new TraitGeneric(false, false); //params are false and false bec. we just need to access 1 function.
        $this->remapped_terms = $func->initialize_terms_remapping();
        echo "\nremapped_terms: ".count($this->remapped_terms)."\n";
        /* END DATA-1841 terms remapping */
        
        $basenames = array_keys($this->export_basenames);
        $text_path = self::load_zip_contents($this->species_list_export, array('timeout' => 3600, 'download_attempts' => 1, 'delay_in_minutes' => 1), $basenames, ".csv");
        foreach($this->export_basenames as $type => $uri) self::csv_to_array($text_path[$type], $type, $uri);
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        $basename = $basenames[0];
        $path = $text_path[$basename];
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace($basename, "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
        if($val = $this->debug) print_r($val);
    }
    private function csv_to_array($csv_file, $type, $uri)
    {
        echo "\n[$type]";
        /* Note: Before there are only 5 columns in the CSV files. Now there are already 6 columns */
        $i = 0;
        if(!($file = Functions::file_open($csv_file, "r"))) return;
        while(!feof($file)) {
            $temp = fgetcsv($file);
            if(is_array($temp)) $temp = array_map('trim', $temp);
            $i++;
            if($i == 1) continue;   // ignore first line of CSV file
            if($i == 2) {           // 2nd row gets the field labels
                $fields = $temp;
                if(count($fields) != 6) {
                    $this->debug["not5"][$fields[0]] = '';
                    continue;
                }
            }
            else {
                $rec = array();
                $k = 0;
                if(!$temp) continue;
                foreach($temp as $t) {
                    $rec[@$fields[$k]] = $t;
                    $k++;
                }
                
                if(is_numeric(stripos($rec["Scientific Name"], ";"))) { //each row has multiple scinames separated by ';' semicolon.
                    $reks = self::process_multiple_scinames($rec);
                    foreach($reks as $rec) {
                        $rec["type"] = $type;
                        $rec["uri"] = $uri;
                        $rec = self::manual_adjustment_on_names($rec);
                        $this->create_instances_from_taxon_object($rec);
                        $this->process_structured_data($rec);
                    }
                }
                else { //each row has one sciname
                    $rec["type"] = $type;
                    $rec["uri"] = $uri;
                    $rec = self::manual_adjustment_on_names($rec);
                    $this->create_instances_from_taxon_object($rec);
                    $this->process_structured_data($rec);
                }
            }
        }
        fclose($file);
    }
    private function process_multiple_scinames($rec)
    {
        $reks = array();
        $scinames = explode(";", $rec["Scientific Name"]);
        $scinames = array_map('trim', $scinames);
        $comnames = explode(";", $rec["Common Name"]);
        $comnames = array_map('trim', $comnames);
        $z = 0;
        $genus_species = array();
        foreach($scinames as $sciname) {
            if($z === 0) $genus = self::get_genus($sciname);
            $species = self::get_species($sciname);
            $rek = array();
            $rek['Acronym']                 = $rec['Acronym'];
            $rek['Link']                    = $rec['Link'];
            $rek['Scientific Name']         = $genus . " " . $species;
            $rek['Common Name']             = @$comnames[$z];
            $rek['Review Date']             = $rec['Review Date'];
            $rek['Fire Study Availability'] = $rec['Fire Study Availability'];
            $z++;
            if($rek['Scientific Name']) $reks[] = $rek;
        }
        return $reks;
    }
    private function manual_adjustment_on_names($rec)
    {
        switch($rec["Scientific Name"]) {
            case "Vaccinium alaskensis":                $rec["Scientific Name"] = "Vaccinium ovalifolium"; break;
            case "Vaccinium alaskaense":                $rec["Scientific Name"] = "Vaccinium ovalifolium"; break;
            case "Taxus candensis":                     $rec["Scientific Name"] = "Taxus canadensis"; break;
            case "Symphiotrichum leave":                $rec["Scientific Name"] = "Symphyotrichum laeve"; break;
            case "Sporobolus flexuous":                 $rec["Scientific Name"] = "Sporobolus flexuosus"; break;
            case "Schoenoplectus actus":                $rec["Scientific Name"] = "Schoenoplectus acutus"; break;
            case "Populus deltoides var. mislizeni":    $rec["Scientific Name"] = "Populus deltoides subsp. wislizeni"; break;
            case "Pinus leiophylla var. chihuahuan":    $rec["Scientific Name"] = "Pinus leiophylla var. chihuahuana"; break;
            case "Cladonia rangeferia":                 $rec["Scientific Name"] = "Cladonia rangiferina"; break;
            case "Cladonia rangiferia":                 $rec["Scientific Name"] = "Cladonia rangiferina"; break;
            case "Baccharis piluaris":                  $rec["Scientific Name"] = "Baccharis pilularis"; break;
            case "Achnatherum thurberiana":             $rec["Scientific Name"] = "Achnatherum thurberianum"; break;
            case "Cushenbury milkvetch":                $rec["Scientific Name"] = "Astragalus albens";
                                                        $rec["Common Name"] = "Cushenbury milkvetch"; 
                                                        break;
            /*
            case "Botrychium matricariaefolium":        $rec["Scientific Name"] = "Botrychium matricariifolium"; 
            Leo decided to leave it as is and just use "Botrychium matricariaefolium"
            */
        }
        $rec["taxon_id"] = strtolower(str_replace(" ", "_", $rec["Scientific Name"]));
        return $rec;
    }
    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec["taxon_id"];
        $taxon->scientificName          = $rec["Scientific Name"];
        $taxon->furtherInformationURL   = $rec["Link"];
        if(!isset($this->taxa[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxa[$taxon->taxonID] = 1;
        }
    }
    private function process_structured_data($record)
    {
        $rec = array();
        $rec["taxon_id"] = $record["taxon_id"];
        $rec["source"] = $record["Link"];
        $rec["catnum"] = $record["type"];
        
        /* previous implementation by Leo:
        "http://eol.org/schema/terms/InvasiveNoxiousStatus"
            - "http://eol.org/schema/terms/feisInvasive";
            - "http://eol.org/schema/terms/feisNotInvasive";
        */
        
        $data = array();
        if(in_array($record["type"], $this->life_forms)) {
            $data["uri"] = "http://eol.org/schema/terms/PlantHabit";
            $data["value"] = $record["uri"];
            $data["remarks"] = self::life_form_remarks($record["type"]);
        }
        else {
            $data["uri"] = $record["uri"];
            $data["value"] = "United States (USA)";
        }
        
        $remarks = "FEIS taxon abbreviation: " . $record["Acronym"];
        if($val = @$data["remarks"]) $remarks .= ". " . $val;
        
        self::add_string_types("true", $rec, "", $data["value"], $data["uri"], $remarks);
        if($val = $record["Scientific Name"])  self::add_string_types(null, $rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
        if($val = $record["Review Date"])      self::add_string_types(null, $rec, "Review Date", $val, "http://rs.tdwg.org/dwc/terms/measurementDeterminedDate");
    }
    private function add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype, $measurementRemarks = null)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence_id;
        
        if($mtype)  $m->measurementType = $mtype;
        else        $m->measurementType = "http://feis.org/". SparqlClient::to_underscore($label); // currently won't pass here
            
        $m->measurementValue = $value;
        if($val = $measurementOfTaxon) $m->measurementOfTaxon = $val;
        if($measurementOfTaxon) {
            $m->source = $rec["source"];
            $m->measurementRemarks = $measurementRemarks;
            // not used... $m->contributor, $m->measurementMethod
        }
        
        /* START DATA-1841 terms remapping */
        if($new_uri = @$this->remapped_terms[$m->measurementType]) $m->measurementType = $new_uri;
        if($new_uri = @$this->remapped_terms[$m->measurementValue]) $m->measurementValue = $new_uri;
        /* END DATA-1841 terms remapping */
        
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }
    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    private function life_form_remarks($type)
    {
        switch($type) {
            case "Bryophyte":   return "Source value: Bryophyte";
            case "Fern":        return "Source value: Fern or Fern Ally";
            case "Forb":        return "Source value: Forb";
            case "Vine":        return "Source value: Vine or liana";
        }
    }
    private function load_zip_contents($zip_path, $download_options, $files, $extension)
    {
        $text_path = array();
        $temp_path = create_temp_dir();
        if($file_contents = Functions::get_remote_file($zip_path, $download_options)) {
            $parts = pathinfo($zip_path);
            $temp_file_path = $temp_path . "/" . $parts["basename"];
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip -o $temp_file_path -d $temp_path");
            if(file_exists($temp_path . "/" . $files[0] . $extension)) {
                foreach($files as $file) $text_path[$file] = $temp_path . "/" . $file . $extension;
            }
            else return;
        }
        else debug("\n\n Connector terminated. Remote files are not ready.\n\n");
        return $text_path;
    }
    private function get_genus($sciname)
    {
        $arr = explode(" ", $sciname);
        return trim($arr[0]);
    }
    private function get_species($sciname)
    {
        $arr = explode(" ", $sciname);
        array_shift($arr);
        return implode(" ", $arr);
    }
}
?>