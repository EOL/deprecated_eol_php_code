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
        $this->species_list_export = "http://localhost/~eolit/cp/FEIS/FEIS.zip";
        $this->species_list_export = "https://dl.dropboxusercontent.com/u/7597512/FEIS/FEIS.zip";
    }

    function generate_FEIS_data()
    {
        $basenames = array_keys($this->export_basenames);
        $text_path = self::load_zip_contents($this->species_list_export, array('timeout' => 3600, 'download_attempts' => 1, 'delay_in_minutes' => 1), $basenames, ".csv");
        print_r($text_path);
        foreach($this->export_basenames as $type => $uri) self::csv_to_array($text_path[$type], $type, $uri);
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        $basename = $basenames[0];
        $path = $text_path[$basename];
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace($basename, "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
        print_r($this->debug);
    }

    private function csv_to_array($csv_file, $type, $uri)
    {
        $i = 0;
        $file = fopen($csv_file, "r");
        if(!$file) return;
        while(!feof($file))
        {
            $temp = fgetcsv($file);
            $i++;
            echo "\n $i - ";
            if($i == 1) continue;   // ignore first line of CSV file
            if($i == 2)             // 2nd row gets the field labels
            {
                $fields = $temp;
                if(count($fields) != 5)
                {
                    $this->debug["not5"][$fields[0]] = 1;
                    continue;
                }
            }
            else
            {
                $rec = array();
                $k = 0;
                // 2 checks if valid record
                if(!$temp) continue;
                if(count($temp) != 5)
                {
                    $this->debug["not5"][$temp[0]] = 1;
                    continue;
                }
                foreach($temp as $t)
                {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                
                $rec["type"] = $type;
                $rec["uri"] = $uri;
                $rec = self::manual_adjustment_on_names($rec);
                $this->create_instances_from_taxon_object($rec);
                $this->process_structured_data($rec);
            }
        }
        fclose($file);
    }

    private function manual_adjustment_on_names($rec)
    {
        switch($rec["Scientific Name"])
        {
            case "Vaccinium alaskensis":                $rec["Scientific Name"] = "Vaccinium ovalifolium";
            case "Vaccinium alaskaense":                $rec["Scientific Name"] = "Vaccinium ovalifolium";
            case "Taxus candensis":                     $rec["Scientific Name"] = "Taxus canadensis";
            case "Symphiotrichum leave":                $rec["Scientific Name"] = "Symphyotrichum laeve";
            case "Sporobolus flexuous":                 $rec["Scientific Name"] = "Sporobolus flexuosus";
            case "Schoenoplectus actus":                $rec["Scientific Name"] = "Schoenoplectus acutus";
            case "Populus deltoides var. mislizeni":    $rec["Scientific Name"] = "Populus deltoides subsp. wislizeni";
            case "Pinus leiophylla var. chihuahuan":    $rec["Scientific Name"] = "Pinus leiophylla var. chihuahuana";
            case "Cladonia rangeferia":                 $rec["Scientific Name"] = "Cladonia rangiferina";
            case "Cladonia rangiferia":                 $rec["Scientific Name"] = "Cladonia rangiferina";
            case "Baccharis piluaris":                  $rec["Scientific Name"] = "Baccharis pilularis";
            case "Achnatherum thurberiana":             $rec["Scientific Name"] = "Achnatherum thurberianum";
            case "Cushenbury milkvetch":                $rec["Scientific Name"] = "Astragalus albens";
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
        echo " - " . $taxon->scientificName . " [$taxon->taxonID]";
        if(!isset($this->taxa[$taxon->taxonID]))
        {
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
        if(in_array($record["type"], $this->life_forms))
        {
            $data["uri"] = "http://eol.org/schema/terms/PlantHabit";
            $data["value"] = $record["uri"];
            $data["remarks"] = self::life_form_remarks($record["type"]);
        }
        else
        {
            $data["uri"] = $record["uri"];
            $data["value"] = "United States (USA)";
        }
        
        $remarks = "FEIS taxon abbreviation: " . $record["Review Acronym"];
        if($val = @$data["remarks"]) $remarks .= ". " . $val;
        
        self::add_string_types("true", $rec, "", $data["value"], $data["uri"], $remarks);
        if($val = $record["Scientific Name"])  self::add_string_types(null, $rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
        if($val = $record["Review Date"])      self::add_string_types(null, $rec, "Review Date", $val, "http://rs.tdwg.org/dwc/terms/measurementDeterminedDate");
    }
    
    private function add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype, $measurementRemarks = null)
    {
        echo "\n [$label]:[$value]:[$mtype]\n";
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        
        if($mtype)  $m->measurementType = $mtype;
        else        $m->measurementType = "http://feis.org/". SparqlClient::to_underscore($label); // currently won't pass here
            
        $m->measurementValue = $value;
        if($val = $measurementOfTaxon) $m->measurementOfTaxon = $val;
        if($measurementOfTaxon)
        {
            $m->source = $rec["source"];
            $m->measurementRemarks = $measurementRemarks;
            // not used... $m->contributor, $m->measurementMethod
        }
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    private function life_form_remarks($type)
    {
        switch($type)
        {
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
        if($file_contents = Functions::get_remote_file($zip_path, $download_options))
        {
            $parts = pathinfo($zip_path);
            $temp_file_path = $temp_path . "/" . $parts["basename"];
            $TMP = fopen($temp_file_path, "w");
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("tar -xzf $temp_file_path -C $temp_path");
            if(file_exists($temp_path . "/" . $files[0] . $extension))
            {
                foreach($files as $file) $text_path[$file] = $temp_path . "/" . $file . $extension;
            }
            else return;
        }
        else debug("\n\n Connector terminated. Remote files are not ready.\n\n");
        return $text_path;
    }

}
?>