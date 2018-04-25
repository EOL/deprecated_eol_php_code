<?php
namespace php_active_record;
/* connector: [COL_trait_text.php]
*/
class COL_traits_textAPI
{
    function __construct($folder = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->taxa_ref_ids = array();
        $this->debug = array();
        $this->extensions = array('taxa'            => "http://rs.tdwg.org/dwc/terms/Taxon",
                                  'distribution'    => "http://rs.gbif.org/terms/1.0/Distribution",
                                  'description'     => "http://rs.gbif.org/terms/1.0/Description",
                                  'reference'       => "http://rs.gbif.org/terms/1.0/Reference",
                                  'speciesprofile'  => "http://rs.gbif.org/terms/1.0/SpeciesProfile",
                                  'vernacular'      => "http://rs.gbif.org/terms/1.0/VernacularName");
    }
    function convert_archive()
    {
        if(!($info = self::prepare_dwca())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        echo "\nConverting COL archive to EOL DwCA...\n";
        /* this is memory-intensive
        foreach($tables as $table) {
            $records = $harvester->process_row_type($table);
            // self::process_fields($records, pathinfo($table, PATHINFO_BASENAME));
            foreach($records as $rec) {
                echo "\n[$table]\n";
                print_r($rec); break;
            }
            $records = null;
        }
        */
        foreach($tables as $key => $values) {
            $tbl = $values[0];
            $items[$tbl->row_type] = $tbl->file_uri;
        }
        print_r($items);
        /* Array(
            [http://rs.tdwg.org/dwc/terms/Taxon] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//taxa.txt
            [http://rs.gbif.org/terms/1.0/Distribution] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//distribution.txt
            [http://rs.gbif.org/terms/1.0/Description] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//description.txt
            [http://rs.gbif.org/terms/1.0/Reference] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//reference.txt
            [http://rs.gbif.org/terms/1.0/SpeciesProfile] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//speciesprofile.txt
            [http://rs.gbif.org/terms/1.0/VernacularName] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//vernacular.txt
        )*/
        self::process_file($items[$this->extensions['reference']], 'reference');
        self::process_file($items[$this->extensions['taxa']], 'taxa');
        $this->archive_builder->finalize(TRUE);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }
    private function process_file($txt_file, $extension)
    {
        $i = 0; echo "\nProcessing $extension...\n";
        foreach(new FileIterator($txt_file) as $line_number => $line) {
            $line = Functions::remove_utf8_bom($line);
            $i++; if(($i % 10000) == 0) echo "\n".number_format($i)." $extension ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
            }
            else {
                $k = -1;
                $rec = array();
                foreach($fields as $field) {
                    $k++;
                    $rec[$field] = @$row[$k];
                }
                $rec = array_map('trim', $rec);
                // print_r($rec);
                if($extension == "taxa") self::process_taxon($rec);
                // elseif($extension == "destribution")    self::process_distribution($rec);
                // elseif($extension == "description")     self::process_description($rec);
                elseif($extension == "reference")       self::process_reference($rec);
                // elseif($extension == "speciesprofile")  self::process_speciesprofile($rec);
                // elseif($extension == "vernacular")      self::process_vernacular($rec);
                // if($i >= 10) break; //debug
            }
        }
    }
    private function process_reference($a)
    {
        /*
        -	references	http://purl.org/dc/terms/identifier
        http://purl.org/dc/terms/creator	references	http://eol.org/schema/reference/full_reference
        http://purl.org/dc/terms/date	references	http://eol.org/schema/reference/full_reference
        http://purl.org/dc/terms/title	references	http://eol.org/schema/reference/full_reference
        http://purl.org/dc/terms/source	references	http://eol.org/schema/reference/full_reference
        Processing reference...
        Array(
            [﻿taxonID] => 316423
            [creator] => Lepage, H.S.
            [date] => 1938
            [title] => [Catalog of coccids from Brazil.] Catálogo dos coccídeos do Brasil.
            [description] => Revista do Museu Paulista. São Paulo
            [identifier] => 
            [type] => taxon
        )
        Give each reference a unique ID and link these IDs to relevant taxa through the referenceID field in the taxa file.
        Concatenate creator, date, title, and source (description) into the full-reference field.
        */
        $r = new \eol_schema\Reference();
        $r->full_reference = self::format_full_ref($a);
        $r->identifier = md5($r->full_reference);
        // $r->uri = ''
        
        $this->taxon_reference_ids[$a['taxonID']][$r->identifier] = ''; //it has to be here. Coz a sigle reference maybe assigned to multiple taxa.
        
        if(!isset($this->reference_ids[$r->identifier]))
        {
            $this->reference_ids[$r->identifier] = ''; 
            $this->archive_builder->write_object_to_file($r);
        }
    }
    private function format_full_ref($a)
    {
        $final = "";
        if($val = $a['creator'])     $final .= "$val. ";
        if($val = $a['date'])        $final .= "$val. ";
        if($val = $a['title'])       $final .= "$val. ";
        if($val = $a['description']) $final .= "$val. ";
        return trim($final);
    }
    private function process_taxon($a)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $a['taxonID'];
        //  [﻿taxonID] => 316502
        $taxon->datasetID           = $a['datasetID'];
        $taxon->datasetName         = $a['datasetName'];
        $taxon->acceptedNameUsageID = $a['acceptedNameUsageID'];
        $taxon->parentNameUsageID   = $a['parentNameUsageID'];
        $taxon->taxonomicStatus     = $a['taxonomicStatus'];
        $taxon->taxonRank           = self::format_taxonRank($a);
        $taxon->scientificName      = $a['scientificName'];
        $taxon->kingdom             = $a['kingdom'];
        $taxon->phylum              = $a['phylum'];
        $taxon->class               = $a['class'];
        $taxon->order               = $a['order'];
        // $taxon->superfamily         = $a['superfamily'];
        $taxon->family              = $a['family'];
        // $taxon->genericName         = $a['genericName'];
        $taxon->genus               = $a['genus'];
        $taxon->subgenus            = $a['subgenus'];
        $taxon->specificEpithet     = $a['specificEpithet'];
        $taxon->infraspecificEpithet        = $a['infraspecificEpithet'];
        $taxon->scientificNameAuthorship    = $a['scientificNameAuthorship'];
        $taxon->nameAccordingTo     = $a['nameAccordingTo'];
        $taxon->modified            = $a['modified'];
        $taxon->taxonRemarks        = self::format_taxonRemarks($a);
        $taxon->scientificNameID    = $a['scientificNameID'];
        $taxon->furtherInformationURL   = $a['references'];
        if($reference_ids = @$this->taxon_reference_ids[$a['taxonID']]) $taxon->referenceID = implode("; ", array_keys($reference_ids));
        /*
        if(isset($this->taxon_ids[$taxon->taxonID])) return;
        $this->taxon_ids[$taxon->taxonID] = '';
        */
        $this->archive_builder->write_object_to_file($taxon);
        /* Processing taxa...
        Array(
            [identifier] => 
            [datasetID] => 26
            [datasetName] => ScaleNet in Species 2000 & ITIS Catalogue of Life: 28th March 2018
            [acceptedNameUsageID] => 316423
            [parentNameUsageID] => 
            [taxonomicStatus] => synonym
            [taxonRank] => species
            [verbatimTaxonRank] => 
            [scientificName] => Canceraspis brasiliensis Hempel, 1934
            [kingdom] => Animalia
            [phylum] => 
            [class] => 
            [order] => 
            [superfamily] => 
            [family] => 
            [genericName] => Canceraspis
            [genus] => Limacoccus
            [subgenus] => 
            [specificEpithet] => brasiliensis
            [infraspecificEpithet] => 
            [scientificNameAuthorship] => Hempel, 1934
            [source] => 
            [namePublishedIn] => 
            [nameAccordingTo] => 
            [modified] => 
            [description] => 
            [taxonConceptID] => 
            [scientificNameID] => Coc-100-7
            [references] => http://www.catalogueoflife.org/annual-checklist/2015/details/species/id/6a3ba2fef8659ce9708106356d875285/synonym/3eb3b75ad13a5d0fbd1b22fa1074adc0
            [isExtinct] => 
        )*/
    }
    private function format_taxonRank($a)
    {
        /* if there is a value for verbatimTaxonRank, this should take precedence over the CoL taxonRank value, except in cases where verbatimTaxonRank=aberration.
        For aberrations keep the CoL TaxonRank value. */
        if($val = $a['verbatimTaxonRank']) {
            if($val != 'aberration' && $val) return $val;
        }
        return $a['taxonRank'];
    }
    private function format_taxonRemarks($a)
    {
        /* Omit remarks if datasetID is one of the following: 15,21,45,50,134,174,190,199; These are not really taxonomic remarks. */
        $datasetIDs_2omit = array(15,21,45,50,134,174,190,199);
        if(!in_array($a['datasetID'], $datasetIDs_2omit)) return $a['description'];
    }
    /*
    Processing distribution...
    Array
    (
        [﻿taxonID] => 316424
        [locationID] => TDWG:GER-OO
        [locality] => Germany
        [occurrenceStatus] => 
        [establishmentMeans] => 
    )

    Processing description...
    Array
    (
        [﻿taxonID] => 316423
        [description] => Brazil
    )

    Processing speciesprofile...
    Array
    (
        [﻿taxonID] => 9237970
        [habitat] => terrestrial
    )

    Processing vernacular...
    Array
    (
        [﻿taxonID] => 316443
        [vernacularName] => Chile eriococcin
        [language] => English
        [countryCode] => 
        [locality] => 
        [transliteration] => 
    )
    */
    private function compute_for_dwca_file()
    {
        return "http://localhost/cp/COL/2018-03-28-archive-complete.zip";
    }
    private function prepare_dwca()
    {
        $dwca = self::compute_for_dwca_file();
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }

    private function process_extension($csv_file, $class, $tbl)
    {
    }
    private function get_google_sheet() //sheet found here: https://eol-jira.bibalex.org/browse/DATA-1744
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '19nQkPuuCB9lhQEoOByfdP0-Uwwhn5Y_uTu4zs_SVANI';
        $params['range']         = 'languages!A2:B451'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]] = $item[1];
        return $final;
    }
    
    
}
?>
