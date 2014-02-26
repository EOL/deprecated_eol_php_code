<?php
namespace php_active_record;
/* connector: [750] DATA-1413 FEIS structured data 
SPG provides 2 csv files. These were generated from the FEIS portal: http://www.feis-crs.org/beta/faces/SearchByOther.xhtml
This connector parses the csv files, gets the invasiveness info and other metadata and generates the EOL archive file.
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
        $this->export_basenames = array("reviews", "reviews-2");
        // $this->species_list_export = "http://localhost/~eolit/cp/FEIS/reviews.zip";
        $this->species_list_export = "https://dl.dropboxusercontent.com/u/7597512/FEIS/reviews.zip";
    }

    function generate_IUCN_data()
    {
        $basenames = $this->export_basenames;
        $text_path = self::load_zip_contents($this->species_list_export, array('timeout' => 3600, 'download_attempts' => 1, 'delay_in_minutes' => 1), $basenames, ".csv");
        print_r($text_path);
        self::csv_to_array($text_path["reviews"], "invasive");
        self::csv_to_array($text_path["reviews-2"], "noninvasive");
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

    private function csv_to_array($csv_file, $type)
    {
        $i = 0;
        $file = fopen($csv_file, "r");
        if(!$file) return;
        while(!feof($file))
        {
            $temp = fgetcsv($file);
            $i++;
            echo "\n $i - ";
            if($i == 1) continue; // ignore first line of CSV file
            if($i == 2)
            {
                $fields = $temp;
                print_r($fields);
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
                $rec["taxon_id"] = strtolower(str_replace(" ", "_", $rec["Scientific Name"]));
                $rec["type"] = $type;
                $this->create_instances_from_taxon_object($rec);
                $this->process_invasiveness($rec);
            }
        }
        fclose($file);
    }

    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec["taxon_id"];
        $taxon->scientificName          = $rec["Scientific Name"];
        $taxon->furtherInformationURL   = $rec["Link"];
        echo " - " . $taxon->scientificName . " [$taxon->taxonID]";
        $this->archive_builder->write_object_to_file($taxon);
    }

    private function process_invasiveness($record)
    {
        $rec = array();
        $rec["taxon_id"] = $record["taxon_id"];
        $rec["source"] = $record["Link"];
        $rec["catnum"] = "_" . $record["type"];
        
        if    ($record["type"] == "invasive")    $uri = "http://eol.org/schema/terms/feisInvasive";
        elseif($record["type"] == "noninvasive") $uri = "http://eol.org/schema/terms/feisNotInvasive";
        else return;
        
        $remarks = "Review Acronym: " . $record["Review Acronym"];
        self::add_string_types("true", $rec, "Invasiveness", $uri, "http://eol.org/schema/terms/InvasiveNoxiousStatus", $remarks);
        
        if($val = $record["Scientific Name"])  self::add_string_types(null, $rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
        if($val = $record["Common Name"])      self::add_string_types(null, $rec, "Common Name", $val, "http://rs.tdwg.org/dwc/terms/vernacularName");
        if($val = $record["Review Date"])      self::add_string_types(null, $rec, "Review Date", $val, "http://rs.tdwg.org/dwc/terms/measurementDeterminedDate");
    }
    
    private function add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype, $measurementRemarks = null)
    {
        echo "\n [$label]:[$value]\n";
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
            // $m->contributor = '';
            // $m->measurementMethod = '';
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
                foreach($files as $file)
                {
                    $text_path[$file] = $temp_path . "/" . $file . $extension;
                }
            }
            else return;
        }
        else debug("\n\n Connector terminated. Remote files are not ready.\n\n");
        return $text_path;
    }

}
?>