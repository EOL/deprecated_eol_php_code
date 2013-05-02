<?php
namespace php_active_record;

class AnageDataConnector
{
    const DUMP_URL = "/Users/pleary/Downloads/datasets/anage_data.txt";

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
            if($line_number % 1000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            if($line_number >= 10000) break;
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
        $this->add_numeric_data($line_data, $taxon->taxonID);
    }

    private function add_taxon($line_data)
    {
        $t = new \eol_schema\Taxon();
        $t->scientificName = trim($line_data[$this->column_indices['Genus']] ." ". $line_data[$this->column_indices['Species']]);
        $t->family = $line_data[$this->column_indices['Family']];
        $t->order = $line_data[$this->column_indices['Order']];
        $t->class = $line_data[$this->column_indices['Class']];
        $t->phylum = $line_data[$this->column_indices['Phylum']];
        $t->kingdom = $line_data[$this->column_indices['Kingdom']];
        $t->taxonID = md5($t->scientificName . $t->family . $t->order . $t->class . $t->phylum . $t->kingdom);
        $this->archive_builder->write_object_to_file($t);

        if($v = $line_data[$this->column_indices['Common name']])
        {
            $vernacular = new \eol_schema\VernacularName();
            $vernacular->taxonID = $t->taxonID;
            $vernacular->vernacularName = $v;
            $vernacular->language = 'en';
            $this->archive_builder->write_object_to_file($vernacular);
        }

        return $t;
    }

    private function add_numeric_data($line_data, $taxon_id)
    {
        $numeric_types = array('Female maturity (days)', 'Male maturity (days)', 'Gestation/Incubation (days)',
            'Weaning (days)', 'Litter/Clutch size', 'Litters/Clutches per year', 'Inter-litter/Interbirth interval',
            'Birth weight (g)', 'Weaning weight (g)', 'Adult weight (g)', 'Growth rate (1/days)', 'Maximum longevity (yrs)',
            'IMR (per yr)', 'MRDT (yrs)', 'Metabolic rate (W)', 'Body mass (g)', 'Temperature (K)');
        foreach($numeric_types as $label)
        {
            if($v = trim($line_data[$this->column_indices[$label]]))
            {
                $this_label = $label;
                $unit_of_measure = null;
                if(preg_match("/^(.*) \((.+)\)$/", $label, $arr))
                {
                    $this_label = $arr[1];
                    $unit_of_measure = "http://anage.org/". SparqlClient::to_underscore(str_replace("/", "_", $arr[2]));
                }
                $m = new \eol_schema\MeasurementOrFact();
                $m->taxonID = $taxon_id;
                $m->measurementType = "http://anage.org/". SparqlClient::to_underscore($this_label);
                $m->measurementValue = $v;
                $m->measurementUnit = $unit_of_measure;
                $this->archive_builder->write_object_to_file($m);
            }
        }
    }
}

?>