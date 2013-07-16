<?php
namespace php_active_record;

class ObisDataConnector
{
    const DUMP_URL = "/Users/pleary/Downloads/datasets/obis.csv";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
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
            // if($line_number >= 100) break;
            $line_data = ContentArchiveReader::line_to_array($line, ",", "\"");
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
        $t = new \eol_schema\Taxon();
        $t->taxonID = $line_data[$this->column_indices['tname_id']];
        $t->scientificName = trim($line_data[$this->column_indices['tname']] ." ". $line_data[$this->column_indices['tauthor']]);
        // if(!preg_match("/Makaira nigricans/", $t->scientificName)) return;
        $this->archive_builder->write_object_to_file($t);

        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = md5($t->taxonID. 'occurrence');
        $o->taxonID = $t->taxonID;
        $this->archive_builder->write_object_to_file($o);

        static $fields_to_ignore = array('id', 'tname', 'tauthor', 'tname_id', 'n', 'ndepth', 'ndate', 'nwoa', 'ntaxa');
        static $fields_for_taxon = array('minlat', 'maxlat', 'minlon', 'maxlon', 'mindepth', 'maxdepth');
        foreach($line_data as $index => $value)
        {
            if(!$value) continue;
            if(!isset($this->column_labels[$index]))
            {
                echo "There is a problem - more columns in this row than in the header\n";
                continue;
            }
            $column_label = $this->column_labels[$index];
            if(in_array($column_label, $fields_to_ignore)) continue;
            $m = new \eol_schema\MeasurementOrFact();
            $m->occurrenceID = $o->occurrenceID;
            if(in_array($column_label, $fields_for_taxon)) $m->taxonID = $t->taxonID;
            $m->measurementType = "http://iobis.org/". $column_label;
            $m->measurementValue = $value;
            $this->archive_builder->write_object_to_file($m);
        }
    }
}

?>