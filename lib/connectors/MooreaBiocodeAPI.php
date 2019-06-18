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
        $this->download_options = array( 
            'expire_seconds'     => 60*60*24*10, //10 days
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->main_path = CONTENT_RESOURCE_LOCAL_PATH . '/330_pre/';
        $this->spreadsheet_url = 'http://localhost/cp_new/MooreaBiocode/Moorea-Tahiti.xls';
        $this->spreadsheet_url = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MooreaBiocode/Moorea-Tahiti.xls';
    }
    function start()
    {
        $field_no_info = self::convert_xls_2array(); //exit;
        self::loop_media_tab();
        $this->archive_builder->finalize(true);
        
        /* uncomment in real operation
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        */
        //massage debug for printing
        // Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function convert_xls_2array()
    {
        if($local_path = Functions::save_remote_file_to_local($this->spreadsheet_url, array('file_extension' => 'xls', 'cache' => 1, 'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 3))) {
            require_library('XLSParser'); $parser = new XLSParser();
            debug("\nreading: " . $this->spreadsheet_url . "\n");
            $temp = $parser->convert_sheet_to_array($local_path);
            $records = $parser->prepare_data($temp, "single", "Field Number", "Field Number", "Family", "Full Name", "Genus"); // print_r($records);
            debug("\n" . count($records));
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
    private function loop_media_tab()
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
            }
        }
    }
    /*
    private function write_taxon_DH($rec)
    {   
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec['taxonID'];
        $taxon->scientificName              = $rec['scientificName'];
        $taxon->scientificNameAuthorship    = $rec['scientificNameAuthorship'];     //from look-ups
        $taxon->canonicalName               = $rec['canonicalName'];                //from look-ups
        $taxon->parentNameUsageID           = $rec['parentNameUsageID'];
        $taxon->taxonRank                   = $rec['taxonRank'];                    //from look-ups
        $taxon->taxonomicStatus             = $rec['taxonomicStatus'];
        $taxon->acceptedNameUsageID         = $rec['acceptedNameUsageID'];
        $taxon->furtherInformationURL       = $rec['furtherInformationURL'];
        $taxon->taxonRemarks                = $rec['taxonRemarks'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function create_text_object($rec)
    {
        $this->taxa_with_trait[$rec['REF|Plant|theplant']] = ''; //to be used when creating taxon.tab
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $rec['REF|Plant|theplant'];
        $mr->identifier     = $rec['DEF_id'];
        $mr->type           = $rec['type'];
        $mr->language       = 'en';
        $mr->format         = "text/html";
        $mr->CVterm         = $rec['Subject'];
        // $mr->Owner          = '';
        // $mr->rights         = '';
        $mr->title          = $rec['Title'];
        $mr->UsageTerms     = $rec['blank_1'];
        $mr->description    = $rec['description'];
        // $mr->LocationCreated = '';
        $mr->bibliographicCitation = $this->partner_bibliographicCitation;
        $mr->furtherInformationURL = $this->partner_source_url;
        $mr->referenceID = $rec['REF|Reference|ref'];
        if(!@$rec['REF|Reference|ref']) {
            print_r($rec);
            exit("\nNo reference!\n");
        }
        // if($agent_ids = )  $mr->agentID = implode("; ", $agent_ids);
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    */
}
?>
