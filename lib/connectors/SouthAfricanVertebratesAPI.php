<?php
namespace php_active_record;
/* connector: [548]
Partner provides 2 tab-delimited text files. The connector then generates a DWC-A file with parent-child format.
This also includes common names and synonyms.
*/
class SouthAfricanVertebratesAPI
{
    function __construct($folder = false)
    {
        $this->rank_order = array_reverse(array("Kingdom", "Phylum", "Sub Phylum", "Class", "Infraclass", "Super Cohort", "Cohort", "Super Order", "Order", "Suborder", "Infraorder", "Superfamily", "Family", "Subfamily", "Tribe", "Genus", "Species", "Infraspecies"));
        $this->taxa_all = array();

        // $this->taxa_path = DOC_ROOT . "/update_resources/connectors/files/SouthAfricanVertebrates/taxa.txt";
        // $this->vernacular_path = DOC_ROOT . "/update_resources/connectors/files/SouthAfricanVertebrates/common names.txt";

        // $this->taxa_path = "http://dl.dropbox.com/u/7597512/SouthAfricanVertebrates/taxa.txt";
        // $this->vernacular_path = "http://dl.dropbox.com/u/7597512/SouthAfricanVertebrates/common names.txt";

        $this->vernacular_path = "http://localhost/cp_new/SouthAfricanVertebrates/common names.txt";
        $this->taxa_path = "http://localhost/cp_new/SouthAfricanVertebrates/taxa.txt";

        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
    }

    function get_all_taxa()
    {
        debug("\n Processing... \n");
        $taxa = array();
        $i = 0;
        $temp_filepath = Functions::save_remote_file_to_local($this->taxa_path, array('timeout' => 4800, 'download_attempts' => 5));
        foreach(new FileIterator($temp_filepath, true) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            $i++;
            if($line)
            {
                $fields = explode("\t", trim($line));
                $fields = array_map('trim', $fields); //trims all array values in the array
                if($i == 1) $labels = $fields;
                else
                {
                    $j = 0;
                    $rows = array();
                    $sciname = utf8_encode(trim($fields[1])); // fields[1] is the ScientificName
                    $canonical = Functions::canonical_form(trim($sciname));
                    foreach($fields as $field)
                    {
                        if($j == 1) $rows['canonical'] = $canonical;
                        $rows[$labels[$j]] = utf8_encode($fields[$j]); 
                        $j++;
                    }
                    if(@$rows['TaxonomicStatus'] != "synonym") $taxa[$canonical] = $rows;
                    else $taxa[$sciname] = $rows;
                }
            }
        }
        $this->taxa_all = $taxa;
        $taxa = self::complete_missing_taxa($taxa);
        $taxa = self::assign_parent_id($taxa);
        $this->taxa_all = $taxa;
        unset($taxa);
        self::prepare_archive($this->taxa_all);
    }

    private function prepare_archive($taxa)
    {
        foreach($taxa as $canonical => $taxon)
        {
            if(@$taxon['TaxonomicStatus'] != "synonym") self::parse_record_element($taxon);
        }
        self::get_vernacular_names();
        self::get_synonyms();
        $this->create_archive();
    }

    private function complete_missing_taxa($taxa) // this will complete/add those missing taxa from the taxa list.
    {
        $rank_order = $this->rank_order;
        $rank_order = array_diff($rank_order, array('Infraspecies', 'Species'));
        foreach($taxa as $taxon)
        {
            if(@$taxon['TaxonomicStatus'] == "synonym") continue;
            foreach($rank_order as $rank)
            {
                if($name = trim($taxon[$rank]))
                {
                    if(!isset($this->taxa_all[$name])) 
                    {
                        $this->taxa_all[$name]['Identifier'] = $name . "_id";
                        $this->taxa_all[$name]['ScientificName'] = $name;
                        $this->taxa_all[$name]['Parent TaxonID'] = "";
                        $this->taxa_all[$name]['TaxonRank'] = $rank;
                        $this->taxa_all[$name]['added'] = true;
                    }
                }
            }
        }
        return $this->taxa_all;
    }

    private function assign_parent_id($taxa)
    {
        foreach($taxa as $canonical => $taxon)
        {
            if(@$taxon['TaxonomicStatus'] != "synonym") $taxa[$canonical]['Parent TaxonID'] = self::get_parent_id($taxon, $canonical);
        }
        return $taxa;
    }

    private function get_parent_id($taxon, $canonical)
    {
        $start = false;
        $rank = $taxon['TaxonRank'];
        foreach($this->rank_order as $rangk)
        {
            if($rank == $rangk)
            {
                $start = true;
                continue;
            }
            if($start)
            {
                if($rangk == "Species") // if Species meaning you are looking for the parent of an Infraspecies...
                {
                    $sciname = trim($taxon['Genus']) . " " . trim($taxon['Species']); // if Species then get the Genus...
                    if(isset($this->taxa_all[$sciname])) return $this->taxa_all[$sciname]['Identifier'];
                    else debug("\n Warning: [$sciname] not yet in all_taxa.\n"); // will cont. searching for the parent id on the next higher rank
                }
                else
                {
                    if($taxon_name = trim(@$taxon[$rangk]))
                    {
                        if(isset($this->taxa_all[$taxon_name]['Identifier'])) return $this->taxa_all[$taxon_name]['Identifier'];
                        else debug("\n Warning2: [$sciname] not yet in all_taxa.\n"); // will cont. searching for the parent id on the next higher rank
                    }
                }
            }
        }
    }

    private function parse_record_element($rec)
    {
        $reference_ids = array();
        $ref_ids = array();
        $agent_ids = array();
        $rec = $this->create_instances_from_taxon_object($rec, $reference_ids);
    }

    private function get_vernacular_names()
    {
        $temp_filepath = Functions::save_remote_file_to_local($this->vernacular_path, array('timeout' => 4800, 'download_attempts' => 5));
        foreach(new FileIterator($temp_filepath, true) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            if($line)
            {
                $fields = explode("\t", trim($line));
                $fields = array_map('trim', $fields); //trims all array values in the array
                $common_name = @$fields[1];
                $sciname = Functions::canonical_form(trim(@$fields[0]));
                $taxon_id = @$this->taxa_all[$sciname]['Identifier'];
                if($common_name == '' || $taxon_id == '' || $sciname == '') continue;
                $language = self::get_language(@$fields[3]);
                $vernacular = new \eol_schema\VernacularName();
                $vernacular->taxonID = $taxon_id;
                $vernacular->vernacularName = (string) $common_name;
                $vernacular->language = $language;
                $vernacular_id = md5("$vernacular->taxonID|$vernacular->vernacularName|$vernacular->language");
                if(!isset($this->vernacular_name_ids[$vernacular_id]))
                {
                    $this->archive_builder->write_object_to_file($vernacular);
                    $this->vernacular_name_ids[$vernacular_id] = 1;
                }
            }
        }
    }

    private function get_synonyms()
    {
        foreach($this->taxa_all as $canonical => $taxon)
        {
            if(@$taxon['TaxonomicStatus'] != 'synonym') continue;
            $synonym = new \eol_schema\Taxon();
            $synonym->scientificName = (string) $taxon['ScientificName'];
            $synonym->acceptedNameUsageID = $taxon['Parent TaxonID'];
            $synonym->taxonomicStatus = 'synonym';
            $synonym->taxonID = md5($taxon['Parent TaxonID'] . "|$synonym->scientificName|$synonym->taxonomicStatus");
            if(!$synonym->scientificName) continue;
            if(!isset($this->taxon_ids[$synonym->taxonID]))
            {
                $this->archive_builder->write_object_to_file($synonym);
                $this->taxon_ids[$synonym->taxonID] = 1;
            }
        }
    }

    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);

            // [5] => Sub Phylum
            // [7] => Infraclass
            // [8] => Super Cohort
            // [9] => Cohort
            // [10] => Super Order
            // [12] => Suborder
            // [13] => Infraorder
            // [14] => Superfamily
            // [16] => Subfamily
            // [17] => Tribe
            // [19] => Species
            // [20] => Infraspecies
            // [26] => ReferenceID

        $taxon_rank = trim(@$rec['TaxonRank']);
        if($taxon_rank != "") @$rec[$taxon_rank] = ""; // e.g. if taxon rank is 'Genus', then the genus field should be blank.

        $taxon_id = (string) $rec['Identifier'];
        $taxon->taxonID                     = $taxon_id;
        $taxon->taxonRank                   = (string) $rec['TaxonRank'];
        $taxon->scientificName              = (string) $rec['ScientificName'];
        $taxon->scientificNameAuthorship    = "";
        $taxon->vernacularName              = "";
        $taxon->parentNameUsageID           = (string) @$rec['Parent TaxonID'];
        $taxon->kingdom                     = (string) @$rec['Kingdom'];
        $taxon->phylum                      = (string) @$rec['Phylum'];
        $taxon->class                       = (string) @$rec['Class'];
        $taxon->order                       = (string) @$rec['Order'];
        $taxon->family                      = (string) @$rec['Family'];
        $taxon->genus                       = (string) @$rec['Genus'];
        $taxon->furtherInformationURL       = (string) self::get_url(@$rec['FurtherInformationURL']);
        $taxon->specificEpithet             = "";
        $taxon->taxonomicStatus             = (string) @$rec['TaxonomicStatus'];
        $taxon->nomenclaturalCode           = "";
        $taxon->nomenclaturalStatus         = "";
        $taxon->acceptedNameUsage           = "";
        $taxon->acceptedNameUsageID         = "";
        $taxon->namePublishedIn             = (string) @$rec['NamePublishedIn'];
        $taxon->taxonRemarks                = (string) @$rec['TaxonRemarks'];
        $taxon->infraspecificEpithet        = "";
        $this->taxa[$taxon_id] = $taxon;
        return $rec;
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

    private function get_url($urls)
    {
        $urls = explode("|", $urls);
        return @$urls[0];
    }

    private function get_language($lang)
    {
        switch(trim($lang))
        {
            case "Afrikaans": return "af";
            case "Dutch": return "nl";
            case "English": return "en";
            case "English synonym": return "en";
            case "French": return "fr";
            case "German": return "de";
            case "Herero": return "hz";
            case "Khoikhoi": return "khi";
            case "Kwangali": return "kwn";
            case "Lozi": return "loz";
            case "Nama/Damara": return "naq";
            case "Ndebele": return "nr";
            case "North Sotho": return "nso";
            case "Ovambo": return "kua";
            case "Portuguese": return "pt";
            case "San": return "cuk";
            case "Sepedi": return "nso";
            case "Shona": return "sn";
            case "SiSwati": return "ss";
            case "Sotho": return "st";
            case "South Sotho": return "st";
            case "Swahili": return "sw";
            case "Swati": return "ss";
            case "Swazi": return "sz";
            case "Tsonga": return "ts";
            case "Tswana": return "tn";
            case "Venda": return "ve";
            case "Xhosa": return "xh";
            case "Yei": return "jei";
            case "Zulu": return "zu";
            default: return $lang;
        }
    }

}
?>