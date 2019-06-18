<?php
namespace php_active_record;
/* connector: [330.php] */
class MooreaBiocodeAPI
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->debug = array();
        // $this->download_options = array('expire_seconds' => 60*60*24*10, 'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->main_path = CONTENT_RESOURCE_LOCAL_PATH . '/330_pre/';
        $this->spreadsheet_url = 'http://localhost/cp_new/MooreaBiocode/Moorea-Tahiti.xls';
        $this->spreadsheet_url = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MooreaBiocode/Moorea-Tahiti.xls';
    }
    function start()
    {
        $taxon_info = self::build_taxon_info();
        $field_no_info = self::convert_xls_2array(); //exit;
        self::loop_media_tab($field_no_info, $taxon_info);
        $taxon_info = ''; $field_no_info = '';
        self::write_agents();
        $this->archive_builder->finalize(true);
        
        /* uncomment in real operation
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        */
        //massage debug for printing
        // Functions::start_print_debug($this->debug, $this->resource_id);
    }
    function investigate_taxon_tab()
    {   $i = 0; $need2investigate = false;
        foreach(new FileIterator(CONTENT_RESOURCE_LOCAL_PATH . '/330/'.'taxon.tab') as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                $final[$rec['scientificName']][] = $rec['scientificName'];
            }
        }
        foreach($final as $taxon => $recs) {
            if(count($recs) > 1) {
                print_r($recs);
                $need2investigate = true;
            }
            // else echo " ".count($recs); //debug only
        }
        echo "\n-end util [$i]-\n";
        if($need2investigate) echo "\nERROR: Need to investigate, duplicate scientificName\n";
        else                  echo "\nOK, nothing to investigate.\n";
    }
    private function write_agents()
    {   $i = 0;
        foreach(new FileIterator($this->main_path.'agent.tab') as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                $a = new \eol_schema\Agent();
                foreach(array_keys($rec) as $field) $a->$field = $rec[$field];
                if(!isset($this->agent_ids[$a->identifier])) {
                    $this->archive_builder->write_object_to_file($a);
                    $this->agent_ids[$a->identifier] = '';
                }
            }
        }
    }
    private function build_taxon_info()
    {   $i = 0;
        foreach(new FileIterator($this->main_path.'taxon.tab') as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /*Array(
                    [taxonID] => 02828bab8a94aed5a740750ebecec3d0
                    [furtherInformationURL] => http://calphotos.berkeley.edu/cgi/img_query?seq_num=226925&one=T
                    [scientificName] => Abdopus abaculus
                )*/
                if($rec['scientificName']) $final[$rec['taxonID']] = array('scientificName' => $rec['scientificName'], 'furtherInformationURL' => $rec['furtherInformationURL']);
            }
        }
        return $final;
    }
    private function loop_media_tab($field_no_info, $taxon_info)
    {   $i = 0;
        foreach(new FileIterator($this->main_path.'media_resource.tab') as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /*Array(
                    [identifier] => CalPhotos:4444 4444 0907 1138
                    [taxonID] => 02828bab8a94aed5a740750ebecec3d0
                    [type] => http://purl.org/dc/dcmitype/StillImage
                    [format] => image/jpeg
                    [description] => All Biocode files are based on field identifications to the best of the researcher&rsquo;s ability at the time.
                    [accessURI] => http://calphotos.berkeley.edu/imgs/512x768/4444_4444/0907/1138.jpeg
                    [furtherInformationURL] => http://calphotos.berkeley.edu/cgi/img_query?seq_num=226925&one=T
                    [derivedFrom] => BMOO-01716
                    [CreateDate] => 2007-09-15
                    [UsageTerms] => http://creativecommons.org/licenses/by-nc-sa/3.0/
                    [Owner] => 2007 Moorea Biocode
                    [agentID] => ab8bb395b63deea16c782c8d044b635a; 7eeb5a08e98d22541aa36585dda44870
                    [LocationCreated] => Fore reef NE of Tareu Pass (Moorea, French Polynesia)
                )
                */
                $taxon_rec = array(); $rek = array();
                if($rek = @$field_no_info[$rec['derivedFrom']]) {
                    // exit("\nwent here\n");
                    /*[BMOO-02955] => Array(
                            [Field Number] => BMOO-02955
                            [Family] => Acroporidae
                            [Full Name] => Acropora austera
                            [Genus] => Acropora
                            [Phyla ID] => xxx
                        )
                    */
                    if(@$rek['Full Name']) {
                        $taxon_rec = array('taxonID' => strtolower(str_replace(' ','_',$rek['Full Name'])), 'scientificName' => $rek['Full Name'], 'family' => $rek['Family'], 
                                           'genus' => $rek['Genus'], 'furtherInformationURL' => $rec['furtherInformationURL']);
                                           /* we can add this if requested: 'phylum' => $rek['Phyla ID'] */
                        $rec['taxonID'] = $taxon_rec['taxonID'];
                    }
                    elseif($rek = @$taxon_info[$rec['taxonID']]) {
                        $taxon_rec = array('taxonID' => $rec['taxonID'], 'scientificName' => $rek['scientificName'], 'furtherInformationURL' => $rec['furtherInformationURL']);
                    }
                    else {
                        print_r($rec); print_r($rek);
                        exit("\ninvestigate 02\n");
                    }
                }
                elseif($rek = @$taxon_info[$rec['taxonID']]) {
                    $taxon_rec = array('taxonID' => $rec['taxonID'], 'scientificName' => $rek['scientificName'], 'furtherInformationURL' => $rec['furtherInformationURL']);
                }
                else {
                    print_r($rec);
                    exit("\ninvestigate 01\n");
                }
                self::write_taxon($taxon_rec);
                self::write_object($rec);
            }
        }
    }
    private function write_taxon($taxon_rec)
    {
        $taxon = new \eol_schema\Taxon();
        foreach(array_keys($taxon_rec) as $field) $taxon->$field = $taxon_rec[$field];
        if(!isset($this->taxon_ids[$taxon->scientificName])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->scientificName] = '';
        }
        /* debug only
        else {
            print_r($taxon_rec);
            echo "\nalready existing: [$taxon->scientificName]\n";
        }
        */
    }
    private function write_object($rec)
    {
        $mr = new \eol_schema\MediaResource();
        foreach(array_keys($rec) as $field) $mr->$field = $rec[$field];
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function convert_xls_2array()
    {
        if($local_path = Functions::save_remote_file_to_local($this->spreadsheet_url, array('file_extension' => 'xls', 'cache' => 1, 'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 3))) {
            require_library('XLSParser'); $parser = new XLSParser();
            debug("\nreading: " . $this->spreadsheet_url . "\n");
            $temp = $parser->convert_sheet_to_array($local_path);
            $records = $parser->prepare_data($temp, "single", "Field Number", "Field Number", "Family", "Full Name", "Genus", "Phyla ID"); // print_r($records);
            debug("\n" . count($records));
            unlink($local_path);
            return $records;
            /*[BMOO-17424] => Array(
                        [Field Number] => BMOO-17424
                        [Family] => 
                        [Full Name] => Antipatharia
                        [Genus] => 
                    )
                [BMOO-02955] => Array(
                        [Field Number] => BMOO-02955
                        [Family] => Acroporidae
                        [Full Name] => Acropora austera
                        [Genus] => Acropora
                    )
            */
        }
    }
}
?>
