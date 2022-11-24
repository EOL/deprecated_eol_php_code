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
        $this->DATA_FOLDER = "/Volumes/AKiTiO4/python_apps/textmine_data_".$SearchTerm."/data_BHL/";
        $this->report_files = array($SearchTerm."_scinames_pages.tsv", //--- un-comment in real operation
                                    // "saproxylic_scinames.tsv",  --- obsolete due to attribution; replaced by saproxylic_scinames_pages.tsv
                                    "scinames_list_".$SearchTerm."/names_from_tables_or_lists.tsv"
                                    );


        $this->term_uri['saproxylic'] = "http://eol.org/schema/terms/saproxylic";
        $this->mtype_uri['saproxylic'] = "http://eol.org/schema/terms/TrophicGuild";
    }

    function get_all_taxa()
    {
        require_library('connectors/BHL_Download_API');
        $this->api = new BHL_Download_API();
        
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
                
                // /* manual force-deletion (exclude) of taxa. An after-NLP filtering based here: https://eol-jira.bibalex.org/browse/DATA-1900?focusedCommentId=67148&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67148
                if(stripos($file, "saproxylic_scinames_pages.tsv") !== false) { //string is found
                    if(in_array($rec['Name'], array("Adscita statices", "Lasius brunneus", "Aleurostictus nobilis", "Cryptocephalus querceti"))) continue;
                }
                // */
                
                self::write_taxon($rec);
            }
        }
    }

    private function write_taxon($rec)
    {   /*Array( --- obsolete 
            [Name] => Hadronyche
            [InternalFile] => part_1.txt
            [ItemID] => 292464
            [CompleteNameIfAbbrev.] => 
            [Verified] => Yes
            [MatchType] => Exact
            [MatchedCanonical] => Hadronyche
            [PlantOrFungi] => No
        )*/
        /* --- current types
        Array( --- saproxylic_scinames_pages.tsv
            [Name] => Symmerus nobilis
            [InternalFile] => page_29453147.txt
            [PageID] => 29453147
            [CompleteNameIfAbbrev.] => 
            [Verified] => Yes
            [MatchType] => Exact
            [MatchedCanonical] => Symmerus nobilis
            [PlantOrFungi] => No
        )
        Array( --- scinames_list_saproxylic/names_from_tables_or_lists.tsv
            [Name] => Abraeus globosus
            [Marker] => 
            [InternalFile] => part_33.txt
            [ItemID] => 181000
            [Verified] => Yes
            [MatchType] => Exact
            [MatchedCanonical] => Abraeus globosus
            [PlantOrFungi] => No
        )*/
        // print_r($rec); exit;
        if(!isset($rec['PageID'])) {
            $rec['PageID'] = $this->api->get_PageId_where_string_exists_in_ItemID($rec['ItemID'], $rec['Name'], @$rec['Marker'], $rec['StartRow']);
        }
        
        if($val = @$rec['CompleteNameIfAbbrev.']) $sciname = $val;
        else                                      $sciname = $rec["Name"];
        if($rec['Verified'] == "Yes" && $rec['MatchType'] == "Exact" && $rec['PlantOrFungi'] == "No") {
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
            
            if($val = @$rec['Sentence']) $rek['measurementRemarks'] = $val;
            if($val = @$rec['Title']) $rek['measurementRemarks'] = $val;
            
            if($val = $rec['PageID']) $rek["source"] = "https://www.biodiversitylibrary.org/page/".$val;
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