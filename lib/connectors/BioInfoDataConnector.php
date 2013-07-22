<?php
namespace php_active_record;

class BioInfoDataConnector
{
    const TAXA_URL = "/Users/pleary/Downloads/datasets/eol_taxa.csv";
    const DUMP_URL = "/Users/pleary/Downloads/datasets/eol_taxon_relations.csv";

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
        $this->taxon_occurrences = array();
        foreach(new FileIterator(self::TAXA_URL) as $line_number => $line)
        {
            if($line_number % 1000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $line_data = ContentArchiveReader::line_to_array($line, ",", "\"");
            if($line_number == 0)
            {
                $this->column_labels = $line_data;
                foreach($this->column_labels as $k => $v) $this->column_indices[$v] = $k;
                continue;
            }
            $taxon = $this->add_taxon($line_data);
            $this->taxon_occurrences[$taxon->taxonID] = $this->add_occurrence($line_data, $taxon);
        }

        $this->column_labels = array();
        $this->column_indices = array();
        foreach(new FileIterator(self::DUMP_URL) as $line_number => $line)
        {
            if($line_number % 1000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
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
        $this->add_associations($line_data);
    }

    private function add_taxon($line_data)
    {
        $t = new \eol_schema\Taxon();
        $t->scientificName = trim($line_data[$this->column_indices['latin']] ." ". $line_data[$this->column_indices['authority']]);
        $t->family = $line_data[$this->column_indices['family']];
        $t->order = $line_data[$this->column_indices['order']];
        $t->phylum = $line_data[$this->column_indices['phylum']];
        $t->source = $line_data[$this->column_indices['url']];
        $t->taxonRank = strtolower($line_data[$this->column_indices['rank']]);
        $t->taxonID = $line_data[$this->column_indices['my taxon id']];
        $this->archive_builder->write_object_to_file($t);

        if($v = $line_data[$this->column_indices['english']])
        {
            $vernacular = new \eol_schema\VernacularName();
            $vernacular->taxonID = $t->taxonID;
            $vernacular->vernacularName = $v;
            $vernacular->language = 'en';
            $this->archive_builder->write_object_to_file($vernacular);
        }
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

    private function add_associations($line_data)
    {
        $source_taxon_id = trim($line_data[$this->column_indices['my active taxon id']]);
        $target_taxon_id = trim($line_data[$this->column_indices['my passive taxon id']]);
        $relationship = trim($line_data[$this->column_indices['active relation']]);
        if($source_taxon_id && $target_taxon_id && $relationship)
        {
            $m = new \eol_schema\Association();
            if(!$this->taxon_occurrences[$source_taxon_id]) return;
            if(!$this->taxon_occurrences[$target_taxon_id]) return;
            $m->occurrenceID = $this->taxon_occurrences[$source_taxon_id]->occurrenceID;
            $m->associationType = "http://bioinfo.org/". SparqlClient::to_underscore($relationship);
            $m->targetOccurrenceID = $this->taxon_occurrences[$target_taxon_id]->occurrenceID;
            $this->archive_builder->write_object_to_file($m);
        }
    }
}

?>