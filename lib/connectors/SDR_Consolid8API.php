<?php
namespace php_active_record;
/* connector: [called from:
1. DwCA_Utility.php, which is called from SDR_consolid8.php for DATA-1777]
2. SDR_consolid8.php _ SDR_consolidated -> when consolidating all reports
*/
class SDR_Consolid8API
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        //initialize
        /* local source
        $this->input['parent_BV_consolid8']['txt_file'] = CONTENT_RESOURCE_LOCAL_PATH.'parent_basal_values_resource.txt.zip';
        $this->input['TS_consolid8']['txt_file'] = CONTENT_RESOURCE_LOCAL_PATH.'taxon_summary_resource.txt.zip';
        $this->input['parent_TS_consolid8']['txt_file'] = CONTENT_RESOURCE_LOCAL_PATH.'parent_taxon_summary_resource.txt.zip';
        */
        $this->input['parent_BV_consolid8']['txt_file'] = 'https://editors.eol.org/other_files/SDR/parent_basal_values_resource.txt.zip';
        $this->input['TS_consolid8']['txt_file'] = 'https://editors.eol.org/other_files/SDR/taxon_summary_resource.txt.zip';
        $this->input['parent_TS_consolid8']['txt_file'] = 'https://editors.eol.org/other_files/SDR/parent_taxon_summary_resource.txt.zip';
        //end initialize
        
        //For consolidating all reports:
        $this->all['BV'] = 'https://editors.eol.org/other_files/SDR/BV_consolid8.tar.gz';
        $this->all['pBV'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/parent_BV_consolid8.tar.gz';
        $this->all['TS'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/TS_consolid8.tar.gz';
        $this->all['pTS'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/parent_TS_consolid8.tar.gz';
        $this->all['Numerical'] = 'https://editors.eol.org/other_files/SDR/Numerical_value.zip';
        $this->debug = array();
    }
    /* ==================== START consolidate all ==================== */
    function consolidate_all_reports() //combining multiple DwCA's files
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $this->resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array("directory_path" => $this->path_to_archive_directory));
        
        foreach($this->all as $what => $dwca) {
            echo "\n$what = $dwca\n";
            if($what == 'Numerical') self::append_resource_txt($dwca);
            else {
                $info = self::extract($dwca, array("timeout" => 172800, 'expire_seconds' => 60*60*24*1));
                $temp_dir = $info['temp_dir'];
                $harvester = $info['harvester'];
                $tables = $info['tables'];
                $index = $info['index'];
                self::append_dwca($info);
                // /* un-comment in real operation -- remove temp dir
                recursive_rmdir($temp_dir);
                echo ("\n temporary directory removed: " . $temp_dir);
                // */
            }
        }
        
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
        // exit("\nelix100\n");
    }
    private function append_dwca($info)
    {
        $tables = $info['harvester']->tables; // print_r(array_keys($tables)); //exit("\nelix1\n");
        /*Array(
            [0] => http://rs.tdwg.org/dwc/terms/measurementorfact
            [1] => http://rs.tdwg.org/dwc/terms/taxon
            [2] => http://rs.tdwg.org/dwc/terms/occurrence
        )*/
        foreach(array_keys($tables) as $extension) { // print_r(pathinfo($extension)); exit("\nelix2\n");
            self::process_table($tables[$extension][0], pathinfo($extension, PATHINFO_BASENAME));
        }
    }
    private function extract($dwca_file = false, $download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1))
    {
        if($dwca_file) $this->dwca_file = $dwca_file;
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit("\n-exit muna-\n");
        // */
        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_37560/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_37560/'
        );
        */
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            exit("\nInvalid archive file. Program will terminate [SDR_Consolid8API.php].\n");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    /* ==================== END consolidate all ==================== */
    function start($info)
    {
        $tables = $info['harvester']->tables;
        $MoF = $tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0];
        self::process_table($MoF, 'measurement');
        if(in_array($this->resource_id, array("TS_consolid8", "parent_TS_consolid8"))) {
            $Assoc = $tables['http://eol.org/schema/association'][0];
            self::process_table($Assoc, 'association');
        }
        self::append_resource_txt();
    }
    private function process_table($meta, $what)
    {   //print_r($meta);
        echo "\n process_table...[$what]\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            // print_r($meta->fields);
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k]; //put "@" as @$tmp[$k] during development
                $k++;
            } //print_r($rec); exit("\naaa\n");
            /*Array( parent_BV_consolid8
                [http://rs.tdwg.org/dwc/terms/measurementID] => 81ec79aed2c2cc9ef21dbb386ad75d0d_parent_basal_values
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => f5c907b74855c54eac52d520c95cf30e_parent_basal_values
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://eol.org/schema/terms/Australasia
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 2018-Oct-10
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => summary of records available in EOL
                [http://purl.org/dc/terms/source] => https://eol.org/terms/search_results?utf8=✓&term_query[clade_id]=288&term_query[filters_attributes][0][pred_uri]=http://eol.org/schema/terms/Present&term_query[filters_attributes][0][op]=is_any&term_query[result_type]=record&commit=Search
                [http://eol.org/schema/parentMeasurementID] => 
            )
            Array( TS_consolid8
                [http://rs.tdwg.org/dwc/terms/measurementID] => 0d377fe29dac1b3a346a1e31dd41c472_taxon_summary
                [http://eol.org/schema/parentMeasurementID] => aef4e0c71c786d2a0390e2d9f13cb8b1_taxon_summary
                [http://rs.tdwg.org/dwc/terms/measurementType] => https://eol.org/schema/terms/exemplary
                [http://rs.tdwg.org/dwc/terms/measurementValue] => https://eol.org/schema/terms/representative
            )
            Array( parent_TS_consolid8
                [http://rs.tdwg.org/dwc/terms/measurementID] => 9c7a40d47a1cb5b1cdb25f2e6111112f_parent_taxon_summary
                [http://eol.org/schema/parentMeasurementID] => 3f26abe2c962521fce28ad706a344eb6_parent_taxon_summary
                [http://rs.tdwg.org/dwc/terms/measurementType] => https://eol.org/schema/terms/exemplary
                [http://rs.tdwg.org/dwc/terms/measurementValue] => https://eol.org/schema/terms/representative
            )
            */
            $rec = array_map('trim', $rec);
            self::write_MoF_rec($rec, $what);
            // if($i >= 10) break; //debug only
        }
    }
    private function write_MoF_rec($rec, $what)
    {
        if    ($what == 'taxon') $m = new \eol_schema\Taxon();
        elseif($what == 'occurrence') $m = new \eol_schema\Occurrence();
        elseif($what == 'measurement') $m = new \eol_schema\MeasurementOrFact_specific();
        elseif($what == 'measurementorfact') $m = new \eol_schema\MeasurementOrFact_specific();
        elseif($what == 'association') $m = new \eol_schema\Association_specific();
        else exit("\nUn-initialized class [$what] here.\n");
        
        $uris = array_keys($rec);
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            if($field == 'IAO_0000009') $field = 'label';
            $m->$field = $rec[$uri];
        }
        /* add measurementID if missing --- New Jan 14, 2021 --- copied template
        if(!isset($m->measurementID)) {
            $m->measurementID = Functions::generate_measurementID($m, $this->resource_id); //3rd param is optional. If blank then it will consider all properties of the extension
        }
        */

        if(in_array($what, array('measurement', 'measurementorfact'))) {
            if(!isset($this->measurementIDs[$m->measurementID])) {
                $this->measurementIDs[$m->measurementID] = '';
                $this->archive_builder->write_object_to_file($m);
            }
        }
        elseif($what == 'association') {
            if(!isset($this->associationIDs[$m->associationID])) {
                $this->associationIDs[$m->associationID] = '';
                $this->archive_builder->write_object_to_file($m);
            }
        }
        elseif($what == 'taxon') {
            if(!isset($this->taxonIDs[$m->taxonID])) {
                $this->taxonIDs[$m->taxonID] = '';
                $this->archive_builder->write_object_to_file($m);
            }
        }
        elseif($what == 'occurrence') {
            if(!isset($this->occurrenceIDs[$m->occurrenceID])) {
                $this->occurrenceIDs[$m->occurrenceID] = '';
                $this->archive_builder->write_object_to_file($m);
            }
        }
        else exit("\nUn-initialized class [$what].\n");
        return $m;
    }
    private function append_resource_txt($use_this_zip_file = false)
    {   
        if($use_this_zip_file) $zip_file = $use_this_zip_file;
        else                   $zip_file = $this->input[$this->resource_id]['txt_file'];
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_zip_file($zip_file, array("timeout" => 172800, 'expire_seconds' => 60*60*24*1));
        // print_r(pathinfo($paths['temp_file_path'])); exit;
        /*Array(
            [dirname] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_20647
            [basename] => Numerical_value.zip
            [extension] => zip
            [filename] => Numerical_value
        )*/
        $pathinfo_basename = pathinfo($paths['temp_file_path'], PATHINFO_BASENAME);
        if($pathinfo_basename == 'Numerical_value.zip') $Numerical_value_YN = true;
        else $Numerical_value_YN = false;
        //print_r($paths); exit("\nbbb\n");
        /*Array(    [extracted_file] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_68666/parent_basal_values_resource.txt
                    [temp_dir] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_68666/
                    [temp_file_path] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_68666/parent_basal_values_resource.txt.zip
        )
        Array(      [extracted_file] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_73146/taxon_summary_resource.txt
                    [temp_dir] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_73146/
                    [temp_file_path] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_73146/taxon_summary_resource.txt.zip
        )
        Array(      [extracted_file] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_24478/parent_taxon_summary_resource.txt
                    [temp_dir] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_24478/
                    [temp_file_path] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_24478/parent_taxon_summary_resource.txt.zip
        )
        Array(      [extracted_file] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_52744/Numerical_value
                    [temp_dir] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_52744/
                    [temp_file_path] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_52744/Numerical_value.zip
        )*/
        $local = $paths['extracted_file']; //normal operation
        
        // /* during all-consolidation for Numerical text file
        if($Numerical_value_YN) $local = $paths['temp_dir'].'lifeStage_statMeth_resource.txt';
        // */
        
        echo "\n append_resource_txt [$this->resource_id]...\n";
        $i = 0;
        foreach(new FileIterator($local) as $line => $row) { $i++;
            if(!$row) continue;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                continue;
            }
            $rek = array(); $k = 0;
            foreach($fields as $field) {
                $rek[$field] = $rec[$k];
                $k++;
            }
            // print_r($rek); exit("\nccc\n");
            /*Array( parent_BV_consolid8
                [Page ID] => 288
                [eol_pk] => R98-PK130902594
                [Value URI] => http://www.marineregions.org/mrgid/1904
                [Label] => https://eol.org/schema/terms/representative
            )*/
            /*Array( from original process...
                [0] => Array(
                        [0] => 46559217
                        [1] => R101-PK131219120
                        [2] => http://www.marineregions.org/mrgid/1908
                        [3] => https://eol.org/schema/terms/representative  )
                [1] => Array(
                        [0] => 46559217
                        [1] => R406-PK131150454
                        [2] => http://www.marineregions.org/mrgid/1912
                        [3] => https://eol.org/schema/terms/representative  )
                )
            */
            /*Array( TS_consolid8
                [Page ID] => 190593
                [eol_pk] => R20-PK21695705
                [object_page_id] => 1061757
                [Label] => https://eol.org/schema/terms/representative
            )
            Array( parent_TS_consolid8
                [Page ID] => 288
                [eol_pk] => R20-PK21836738
                [object_page_id] => 901752
                [Label] => https://eol.org/schema/terms/representative
            )
            Array( Numerical text file
                [Page ID] => 45327507
                [eol_pk] => R556-PK43762063
                [Predicate] => http://eol.org/schema/terms/ArmPlusDiscLength
                [Label] => https://eol.org/schema/terms/representative
            )
            */
            $m = new \eol_schema\MeasurementOrFact_specific(); //NOTE: used a new class MeasurementOrFact_specific() for non-standard fields like 'm->label'
            $m->measurementType     = 'https://eol.org/schema/terms/exemplary';
            $m->measurementValue    = $rek['Label']; //$row[3];
            $m->parentMeasurementEolPk = $rek['eol_pk']; //$row[1]; //http://eol.org/schema/parentMeasurementEolPk
            $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement_specific');
            if(!isset($this->measurementIDs[$m->measurementID])) {
                $this->measurementIDs[$m->measurementID] = '';
                $this->archive_builder->write_object_to_file($m);
            }
            
            /* Seems no need to add taxa here. Since Page ID wan't be recorded anyway. DO NOT UN-COMMENT.
            // if($Numerical_value_YN) {
            if(true) {
                $m = new \eol_schema\Taxon();
                $m->EOL_taxonID = $rek['Page ID'];
                $m->taxonID = $rek['Page ID'];
                if(!isset($this->taxonIDs[$m->taxonID])) {
                    $this->taxonIDs[$m->taxonID] = '';
                    $this->archive_builder->write_object_to_file($m);
                    @$this->debug[$pathinfo_basename]['taxon added from Numerical_value']++;
                }
            }
            */
            
        }
        recursive_rmdir($paths['temp_dir']);
        echo ("\n temporary directory removed: " . $paths['temp_dir']);
    }
}
?>