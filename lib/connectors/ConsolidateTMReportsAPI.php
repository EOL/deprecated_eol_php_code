<?php
namespace php_active_record;
// connector: [consolidate_tm_reports]
class ConsolidateTMReportsAPI
{
    function __construct($folder, $SearchTerm)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->SearchTerm = $SearchTerm;
        $this->DATA_FOLDER = "/Volumes/AKiTiO4/python_apps/textmine_data/data_BHL/";
        $this->report_files = array("saproxylic_scinames.tsv", 
                                    "scinames_list_saproxylic/names_from_tables_or_lists.tsv");

        $this->term_uri['saproxylic'] = "http://eol.org/schema/terms/saproxylic";
        $this->mtype_uri['saproxylic'] = "http://eol.org/schema/terms/TrophicGuild";
    }

    function get_all_taxa()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        $this->func->initialize_terms_remapping(); //for DATA-1841 terms remapping
        
        foreach($this->report_files as $tsv) {
            self::process_report($this->DATA_FOLDER.$tsv);
        }
        $this->archive_builder->finalize(TRUE);
    }
    private function process_report($file)
    {
        echo "\nProcessing...[$file]\n";
        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line) {
            $line = explode("\t", $line); $i++; if(($i % 100) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                $rec = array_map('trim', $rec);
                self::write_taxon($rec);
            }
        }
    }

    private function write_taxon($rec)
    {   /*Array(
            [Name] => Hadronyche
            [InternalFile] => part_1.txt
            [ItemID] => 292464
            [CompleteNameIfAbbrev.] => 
            [Verified] => Yes
            [MatchType] => Exact
            [MatchedCanonical] => Hadronyche
            [PlantOrFungi] => No
        )*/
        if($val = @$rec['CompleteNameIfAbbrev.']) $sciname = $val;
        else                                      $sciname = $rec["Name"];
        if($rec['Verified'] == "Yes" && $rec['MatchType'] == "Exact") {
            $taxon_id = md5($rec["Name"]);
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $taxon_id;
            $taxon->scientificName  = $sciname;
            if(isset($this->taxon_ids[$taxon_id])) return;
            $this->taxon_ids[$taxon_id] = '';
            $this->archive_builder->write_object_to_file($taxon);

            // add trait
            $mType = $this->term_uri[$this->SearchTerm];
            $mValue = $this->mtype_uri[$this->SearchTerm];
            $measurementOfTaxon = "true";
            $rek = array();
            $rek["taxon_id"] = $taxon_id;
            $rek["catnum"] = md5($taxon_id."_".$this->SearchTerm);
            $this->func->add_string_types($rek, $mValue, $mType, $measurementOfTaxon);
        }
        
        
    }
    /*
    private function add_string_types($taxon_id, $label, $value, $mtype)
    {
        $catnum = "h";
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementOfTaxon = 'true';
        $m->source = $this->taxon_link["species"] . str_replace("s_", "", $taxon_id);
        $m->contributor = 'AlgaeBase';
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
    }
    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . 'O' . $catnum; // suggested by Katja to use -- ['O' . $catnum]
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }
    */
}
?>