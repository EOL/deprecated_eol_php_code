<?php
namespace php_active_record;
/* connector: [723] NCBI GGI queries connector
*/
class NCBIGGIqueryAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1);

        // $this->families_list = "http://localhost/~eolit/cp/NCBIGGI/falo2.in";
        $this->families_list = "https://dl.dropboxusercontent.com/u/7597512/NCBI_GGI/falo2.in";

        // $this->family_service = "http://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term=";
        $this->family_service = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term=";
        /* to be used if u want to get all Id's, that is u will loop to get all Id's so server won't be overwhelmed: &retmax=10&retstart=0 */
    }

    function get_all_taxa()
    {
        $families = self::get_families();
        self::create_instances_from_taxon_object($families);
        $this->create_archive();
    }

    private function get_families()
    {
        $families = array();
        if(!$temp_path_filename = Functions::save_remote_file_to_local($this->families_list)) return;
        echo "\n[$temp_path_filename]\n";
        foreach(new FileIterator($temp_path_filename) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $temp = explode("[", $line);
                $family = trim($temp[0]);
                $families[$family] = 1;
            }
        }
        unlink($temp_path_filename);
        return array_keys($families);
    }
    
    private function create_instances_from_taxon_object($families)
    {
        $i = 0;
        foreach($families as $family)
        {
            if($family == "Family Unassigned") continue;
            $record = self::query_family($family);
            $i++; 
            // if($i >= 10) return; //debug
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $family;
            $taxon->scientificName  = $family;
            $taxon->taxonRank       = "family";
            $taxon->taxonRemarks    = "";
            $taxon->rightsHolder    = "";
            $taxon->furtherInformationURL = "";
            $this->taxa[$taxon->taxonID] = $taxon;
        }
    }
    
    private function query_family($family)
    {
        $contents = Functions::lookup_with_cache($this->family_service . $family, $this->download_options);
        if($xml = simplexml_load_string($contents))
        {
            $rec["taxon_id"] = $family;
            $rec["object_id"] = "_no_of_seq_in_genbank";
            self::add_string_types($rec, "Number Of Sequences In GenBank", $xml->Count, "http://eol.org/schema/terms/NumberOfSequencesInGenBank", $family);
        }
    }

    private function add_string_types($rec, $label, $value, $measurementType, $family)
    {
        echo "\n [$label]:[$value]\n";
        $taxon_id = (string) $rec["taxon_id"];
        $object_id = (string) $rec["object_id"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $object_id);
        $m->occurrenceID        = $occurrence->occurrenceID;
        $m->measurementOfTaxon  = 'true';
        $m->source              = $this->family_service . $family;
        $m->measurementType     = $measurementType;
        $m->measurementValue    = (string) $value;
        // $m->measurementMethod   = '';
        // $m->measurementRemarks  = '';
        // $m->contributor = "";
        // $m->referenceID = "";
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $object_id)
    {
        $occurrence_id = md5($taxon_id . 'o' . $object_id);
        $occurrence_id = $taxon_id . 'O' . $object_id; // suggested by Katja to use -- ['O' . $object_id]
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