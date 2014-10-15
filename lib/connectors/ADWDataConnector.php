<?php
namespace php_active_record;

class ADWDataConnector
{
    // const DUMP_URL = "/Users/pleary/Downloads/datasets/adw_new.txt";
    const DUMP_URL = "/Users/eolit/Sites/cp/ADW/adw_new.txt";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        $this->taxon_ids = array();
    }

    public function build_archive()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->occurrence_ids = array();

        $in_quote = null;
        $current_line = null;
        $this->column_labels = array();
        $this->column_indices = array();
        foreach(new FileIterator(self::DUMP_URL) as $line_number => $line)
        {
            if($line_number == 0)
            {
                $this->column_labels = explode("\t", $line);
                foreach($this->column_labels as $k => $v) $this->column_indices[$v] = $k;
                continue;
            }
            if($in_quote) $current_line .= ";$line";
            else $current_line = $line;

            if(preg_match("/\"[^\t\"]+$/", $line))
            {
                $in_quote = true;
                continue;
            }elseif($in_quote && !preg_match("/^[^\t]+\"/", $line)) continue;
            $in_quote = false;

            $line_data = explode("\t", str_replace("\"", "", $current_line));
            $column_count = count($line_data);
            if($column_count != 23)
            {
                echo "Line $line_number :: There was a problem with this line. Column count $column_count should be 23 (is $column_count)\n";
                print_r($line_data);
                return;
            }
            $this->process_line_data($line_data);
        }

        $this->archive_builder->finalize(true);
    }

    public function process_line_data($line_data)
    {
        $taxon = $this->add_taxon($line_data[$this->column_indices['Species']], $line_data[$this->column_indices['Order']]);
        $this->add_boolean_types($line_data, $taxon);
        $this->add_numeric_types($line_data, $taxon);
        $this->add_string_types($line_data, $taxon);
        $this->add_predators($line_data, $taxon);
    }

    private function add_taxon($taxon_name, $order = null)
    {
        $taxon_id = md5($taxon_name);
        if(isset($this->taxon_ids[$taxon_id])) return $this->taxon_ids[$taxon_id];
        $t = new \eol_schema\Taxon();
        $t->taxonID = $taxon_id;
        $t->scientificName = $taxon_name;
        $t->order = $order;
        $this->archive_builder->write_object_to_file($t);
        $this->taxon_ids[$taxon_id] = $t;
        return $t;
    }

    private function add_occurrence($taxon, $identification_string)
    {
        $occurrence_id = md5($taxon->taxonID . 'occurrence' . $identification_string);
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon->taxonID;
        $m->measurementOfTaxon = 'true';
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    private function add_boolean_types($line_data, $taxon)
    {
        $boolean_types = array('Holarctic', 'Cosmopolitan', 'Island endemic');
        foreach($boolean_types as $label)
        {
            if($v = trim($line_data[$this->column_indices[$label]]))
            {
                $m = new \eol_schema\MeasurementOrFact();
                $occurrence = $this->add_occurrence($taxon, $label);
                $m->occurrenceID = $occurrence->occurrenceID;
                $m->measurementOfTaxon = 'true';
                $m->measurementType = "http://adw.org/". SparqlClient::to_underscore($label);
                $m->measurementValue = "true";
                $this->archive_builder->write_object_to_file($m);
            }
        }
    }

    private function add_numeric_types($line_data, $taxon)
    {
        $numeric_types = array('Depth - average - m', 'Depth - extreme low - m', 'Depth - extreme high - m', 'Mass - average - g', 'Mass - extreme high - g', 'Mass - extreme low - g', 'Length - average - mm', 'Length - extreme high - mm', 'Length - extreme low - mm');
        foreach($numeric_types as $label)
        {
            if($v = trim($line_data[$this->column_indices[$label]]))
            {
                if(!is_numeric($v)) continue;
                $this_label = $label;
                $unit_of_measure = null;
                if(preg_match("/^(.*) - (.+)$/", $label, $arr))
                {
                    $this_label = $arr[1];
                    $unit_of_measure = "http://adw.org/". SparqlClient::to_underscore(str_replace("/", "_", $arr[2]));
                }
                $m = new \eol_schema\MeasurementOrFact();
                $occurrence = $this->add_occurrence($taxon, 'numeric');
                $m->occurrenceID = $occurrence->occurrenceID;
                $m->measurementOfTaxon = 'true';
                $m->measurementType = "http://adw.org/". SparqlClient::to_underscore(str_replace(" - ", " ", $this_label));
                $m->measurementValue = $v;
                $m->measurementUnit = $unit_of_measure;
                $this->archive_builder->write_object_to_file($m);
            }
        }
    }

    private function add_string_types($line_data, $taxon)
    {
        $string_types = array('Biogeographic Regions', 'Aquatic Biomes', 'Wetlands', 'Other Habitat Features',
            'Primary Diet', 'Animal Foods', 'Plant Foods', 'Other Foods');
        foreach($string_types as $label)
        {
            if($v = trim($line_data[$this->column_indices[$label]]))
            {
                $values = explode(";", $v);
                foreach($values as $value)
                {
                    $value = trim($value);
                    $this_label = $label;
                    $m = new \eol_schema\MeasurementOrFact();
                    $m->measurementOfTaxon = 'true';
                    if($this_label == 'Biogeographic Regions')
                    {
                        $qualification = null;
                        if(preg_match("/^(.+) :: (.+)$/", $value, $arr))
                        {
                            $value = $arr[1];
                            $qualification = $arr[2];
                        }
                        $this_label = trim("$qualification range");
                    }elseif($this_label == 'Primary Diet')
                    {
                        if(preg_match("/ :: (.+)$/", $value, $arr)) $value = $arr[1];
                    }

                    $occurrence = $this->add_occurrence($taxon, $value);
                    $m->occurrenceID = $occurrence->occurrenceID;
                    $m->measurementType = "http://adw.org/". SparqlClient::to_underscore($this_label);
                    $m->measurementValue = "http://adw.org/". SparqlClient::to_underscore($value);
                    $this->archive_builder->write_object_to_file($m);
                }
            }
        }
    }

    private function add_predators($line_data, $taxon)
    {
        if($v = trim($line_data[$this->column_indices['Known Predators']]))
        {
            $values = explode(";", $v);
            foreach($values as $key => $value)
            {
                $taxon_name = null;
                if(preg_match("/\(([A-Z][a-z]+( [a-z]+)?)\)/", trim($value), $arr)) $taxon_name = $arr[1];
                elseif(preg_match("/(^| )([A-Z][a-z]+ [a-z]+)\)?$/", trim($value), $arr)) $taxon_name = $arr[2];
                elseif(preg_match("/^([A-Z][a-z]+ [a-z]+) \(/", trim($value), $arr)) $taxon_name = $arr[1];
                else continue;

                $occurrence = $this->add_occurrence($taxon, $taxon_name);
                $related_taxon = $this->add_taxon($taxon_name);
                $related_occurrence = $this->add_occurrence($related_taxon, $taxon->scientificName);
                $a = new \eol_schema\Association();
                $a->occurrenceID = $occurrence->occurrenceID;
                $a->associationType = "http://adw.org/hasPredator";
                $a->targetOccurrenceID = $related_occurrence->occurrenceID;
                $this->archive_builder->write_object_to_file($a);
            }
        }
    }
}

?>