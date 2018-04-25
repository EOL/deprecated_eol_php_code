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
        $this->debug = array();
        $this->extensions = array("http://rs.tdwg.org/dwc/terms/Taxon"          => 'taxa',
                                  "http://rs.gbif.org/terms/1.0/Distribution"   => 'distribution',
                                  "http://rs.gbif.org/terms/1.0/Description"    => 'description',
                                  "http://rs.gbif.org/terms/1.0/Reference"      => 'reference',
                                  "http://rs.gbif.org/terms/1.0/SpeciesProfile" => 'speciesprofile',
                                  "http://rs.gbif.org/terms/1.0/VernacularName" => 'vernacular');
    }

    function convert_archive()
    {
        if(!($info = self::prepare_dwca())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        echo "\nConverting COL archive to EOL DwCA...\n";

        /*
        taxa.txt - names & hierarchy, extinct/extant measurements
        vernacular.txt - common names
        reference.txt - taxon references
        speciesprofile.txt - TraitBank habitat data
        distribution.txt - TraitBank distribution data
        description.txt - text objects (distribution notes)
        */
        
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
            /*
            if($class = @$this->extensions[$tbl->row_type]) //process only defined row_types
            {
                echo "\n -- Processing [$class]...\n";
                self::process_extension($tbl->file_uri, $class, $tbl);
            }
            else exit("\nInvalid row_type [$tbl->row_type]\n");
            */
        }
        print_r($items);
        /*
        Array(
            [http://rs.tdwg.org/dwc/terms/Taxon] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//taxa.txt
            [http://rs.gbif.org/terms/1.0/Distribution] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//distribution.txt
            [http://rs.gbif.org/terms/1.0/Description] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//description.txt
            [http://rs.gbif.org/terms/1.0/Reference] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//reference.txt
            [http://rs.gbif.org/terms/1.0/SpeciesProfile] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//speciesprofile.txt
            [http://rs.gbif.org/terms/1.0/VernacularName] => /Library/WebServer/Documents/eol_php_code/tmp/dir_49996//vernacular.txt
        )
        */
        // self::process_taxa($items[$this->extensions['taxa']])
        foreach($items as $row_type => $file_uri) self::process_file($file_uri, $this->extensions[$row_type]);
        // $this->archive_builder->finalize(TRUE);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }
    private function process_file($txt_file, $extension)
    {
        $i = 0; echo "\nProcessing $extension...\n";
        foreach(new FileIterator($txt_file) as $line_number => $line) {
            $i++;
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
                print_r($rec);
                if($extension == "taxa")                process_taxon($rec);
                // elseif($extension == "destribution")    process_distribution($rec);
                // elseif($extension == "description")     process_description($rec);
                // elseif($extension == "reference")       process_reference($rec);
                // elseif($extension == "speciesprofile")  process_speciesprofile($rec);
                // elseif($extension == "vernacular")      process_vernacular($rec);
                if($i >= 10) break; //debug
            }
        }
    }
    private function process_taxon($rec)
    {
        /* Processing taxa...
        Array(
            [﻿taxonID] => 316502
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

    Processing reference...
    Array
    (
        [﻿taxonID] => 316423
        [creator] => Lepage, H.S.
        [date] => 1938
        [title] => [Catalog of coccids from Brazil.] Catálogo dos coccídeos do Brasil.
        [description] => Revista do Museu Paulista. São Paulo
        [identifier] => 
        [type] => taxon
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
