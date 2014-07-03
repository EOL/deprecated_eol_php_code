<?php
namespace php_active_record;
/* connector: [] */
class GGBNetworkAPI
{
    function __construct($folder = null, $query = null)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->measurement_ids = array();
        $this->download_options = array('expire_seconds' => 2592000, 'download_wait_time' => 4000000, 'timeout' => 10800, 'download_attempts' => 2);
        // GGBN data portal:
        $this->ggbn_domain = "http://www.dnabank-network.org/"; // alternate domain
        $this->ggbn_domain = "http://data.ggbn.org/";
        $this->kingdom_service_ggbn = $this->ggbn_domain . "Query.php?kingdom=";
    }

    function get_all_taxa()
    {
        /*
        Animalia: 20721     pages to access: [415]
        Archaea: 503        pages to access: [11]
        Bacteria: 17875     pages to access: [358]
        Chromista: 54       pages to access: [2]
        Fungi: 12           pages to access: [1]
        Plantae: 9632       pages to access: [193]
        Protozoa: 5         pages to access: [1]
        */
        $kingdoms = array("Animalia", "Archaea", "Bacteria", "Chromista", "Fungi", "Plantae", "Protozoa");
        foreach($kingdoms as $kingdom) self::query_kingdom_GGBN_info($kingdom);
        $this->create_archive();
    }

    private function query_kingdom_GGBN_info($kingdom)
    {
        $records = array();
        $rec["source"] = $this->kingdom_service_ggbn . $kingdom;
        $rec["taxon_id"] = $kingdom;
        if($html = Functions::lookup_with_cache($rec["source"], $this->download_options))
        {
            $has_data = false;
            if(preg_match("/<b>(.*?) entries found/ims", $html, $arr) || preg_match("/<b>(.*?) entry found/ims", $html, $arr))
            {
                print "\n $kingdom: " . $arr[1] . "\n";
                $pages = self::get_number_of_pages($arr[1]);
                print "\n pages to access: [$pages]\n";
                for ($i = 1; $i <= $pages; $i++)
                {
                    echo "\n $i of $pages ";
                    if($i > 1)
                    {
                        $rec["source"] = $this->kingdom_service_ggbn . $kingdom . "&page=$i";
                        $html = Functions::lookup_with_cache($rec["source"], $this->download_options);
                    }
                    if($temp = self::process_html($html, $rec["source"])) $records = array_merge($records, $temp);
                }
            }
        }
        self::create_instances_from_taxon_object($records);
    }

    private function get_number_of_pages($num)
    {
        return ceil($num/50);
    }

    private function process_html($html, $source_link)
    {
        $temp = array();
        $html = str_ireplace("<tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'>", "<tr style='elix'>", $html);
        if(preg_match_all("/<tr style=\'elix\'>(.*?)<\/tr>/ims", $html, $arr))
        {
            foreach($arr[1] as $row)
            {
                $href = "";
                if(preg_match("/href=\"(.*?)\"/ims", $row, $arr2)) $href = $arr2[1];
                $row = strip_tags($row, "<td>");
                $row = str_ireplace(array(" width='40%'" , " valign='top'", " colspan='2'"), "", $row);
                if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $arr2))
                {
                    $col = array_map('trim', $arr2[1]);
                    $sciname = @$col[0];
                    
                    $rec_id = md5(implode("_", $col));
                    
                    if    ($val = @$col[2]) $dna_no = $val;
                    elseif($val = @$col[3]) $dna_no = $val;
                    else                    $dna_no = "not defined";
                    
                    $temp[$sciname]["rekords"][] = array("rec_id" => $rec_id, "country" => @$col[1], "dna_no" => $dna_no, "specimen_no" => @$col[3], "href" => $href);
                    $temp[$sciname]["source"] = $source_link;
                }
            }
        }
        print " -- [" . count($temp) . "]";
        return $temp;
    }

    private function create_instances_from_taxon_object($records)
    {
        foreach($records as $sciname => $taxon_dna_records)
        {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                 = strtolower(str_replace(" ", "_", Functions::canonical_form($sciname)));
            $taxon->scientificName          = $sciname;
            $taxon->furtherInformationURL   = $taxon_dna_records["source"];
            $this->taxa[$taxon->taxonID] = $taxon;
            continue; // debug - comment to exclude structured data
            foreach($taxon_dna_records["rekords"] as $record)
            {
                $rec = array();
                $rec["taxon_id"]    = $taxon->taxonID;
                $rec["source"]      = $this->ggbn_domain . $record["href"];

                $rec["object_id"]   = $record["rec_id"] . "_dna_no";
                $measurement        = "http://rs.tdwg.org/dwc/terms/catalogNumber";
                self::add_string_types($rec, "dna_no", $record["dna_no"], $measurement, $sciname, "true");

                $rec["object_id"]   = $record["rec_id"] . "_specimen_no";
                $measurement        = "http://rs.tdwg.org/ontology/voc/Specimen#specimenID";
                self::add_string_types($rec, "specimen_no", $record["specimen_no"], $measurement, $sciname, "false");

                if(!is_numeric(stripos($record["country"], "unknown")))
                {
                    $rec["object_id"]   = $record["rec_id"] . "_country";
                    $measurement        = "http://rs.tdwg.org/dwc/terms/country";
                    self::add_string_types($rec, "country", $record["country"], $measurement, $sciname, "false");
                }
            }
        }
    }

    private function add_string_types($rec, $label, $value, $measurementType, $family, $measurementOfTaxon)
    {
        $taxon_id = (string) $rec["taxon_id"];
        $object_id = (string) $rec["object_id"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $object_id);
        $m->occurrenceID        = $occurrence->occurrenceID;
        $m->measurementOfTaxon  = $measurementOfTaxon;
        $m->source              = @$rec["source"];
        if($val = $measurementType) $m->measurementType = $val;
        else                        $m->measurementType = "http://ggbn.org/". SparqlClient::to_underscore($label);
        $m->measurementValue = (string) $value;
        if(!isset($this->measurement_ids[$m->occurrenceID]))
        {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->occurrenceID] = '';
        }
    }

    private function add_occurrence($taxon_id, $object_id)
    {
        $occurrence_id = $taxon_id . '_' . $object_id;
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    private function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }

}
?>