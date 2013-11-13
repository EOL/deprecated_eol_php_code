<?php
namespace php_active_record;

class ReefFishDataConnector
{
    const DUMP_URL = "/Users/pleary/Downloads/datasets/reef_fish.txt";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        $this->taxon_ids = array();
    }

    public function build_archive()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->column_labels = array();
        $this->column_indices = array();
        foreach(new FileIterator(self::DUMP_URL) as $line_number => $line)
        {
            $line_data = explode("\t", $line);
            if($line_number == 0)
            {
                $this->column_labels = $line_data;
                foreach($this->column_labels as $k => $v) $this->column_indices[$v] = $k;
                continue;
            }
            $this->process_line_data($line_data);
        }
        $this->archive_builder->finalize(true);
    }

    public function process_line_data($line_data)
    {
        $taxon = $this->add_taxon($line_data);
        $occurrence = $this->add_occurrence($line_data, $taxon);
        $this->add_location_data($line_data, $occurrence);
    }

    private function add_taxon($line_data)
    {
        $t = new \eol_schema\Taxon();
        $t->scientificName = trim($line_data[$this->column_indices['Species']]);
        $t->taxonID = md5($t->scientificName);
        $this->archive_builder->write_object_to_file($t);
        return $t;
    }

    private function add_occurrence($line_data, $taxon)
    {
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = md5($taxon->taxonID . "occurrence");
        $o->taxonID = $taxon->taxonID;
        $this->archive_builder->write_object_to_file($o);
        return $o;
    }

    private function add_location_data($line_data, $occurrence)
    {
        $location_types = array('Papua New Guinea', 'New Caledonia', 'Solomon Islands', 'Vanuatu', 'Chesterfield', 'Fiji');
        foreach($location_types as $label)
        {
            if($v = trim($line_data[$this->column_indices[$label]]))
            {
                $this_label = $label;
                $m = new \eol_schema\MeasurementOrFact();
                $m->occurrenceID = $occurrence->occurrenceID;
                $m->measurementOfTaxon = 'true';
                $m->measurementType = "http://reeffish.org/occursIn";
                $m->measurementValue = "http://reeffish.org/". SparqlClient::to_underscore($this_label);
                $this->archive_builder->write_object_to_file($m);
            }
        }
    }
}

?>