<?php
namespace php_active_record;
/* connector: [marine_geo.php] https://eol-jira.bibalex.org/browse/COLLAB-1004 */
class MarineGEOAPI
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        // $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        // $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //orig expires quarterly
        // $this->download_options['expire_seconds'] = false; //debug only
        
        $this->api['coll_num'] = 'http://www.boldsystems.org/index.php/API_Public/specimen?ids=COLL_NUM&format=json';
        
        $this->input['path'] = '/Volumes/AKiTiO4/other_files/MarineGeo/'; //input.xlsx
        $this->input['worksheets'] = array('Voucher Data', 'Specimen Details', 'Taxonomy Data', 'Collection Data');

        $this->labels['Voucher Info']['Specimen Info Metadata'] = array('Sample ID','Field ID','Museum ID','Collection Code','Institution Storing');
        
        $this->labels['Taxonomy']['Taxonomy Metadata'] = array('Sample ID','Phylum','Class','Order','Family','Subfamily','Tribe','Genus','Species','Subspecies','Identifier','Identifier Email');
        $this->labels['Taxonomy']['Extended Fields (BOLD 3.1)'] = array('Identification Method','Taxonomy Notes');
        
        
        $this->labels['Specimen Details']['Specimen Details Metadata'] = array('Sample ID','Sex','Reproduction','Life Stage','Extra Info','Notes');
        $this->labels['Specimen Details']['Specimen Details Metadata Extended Fields (BOLD 3.1)'] = array('Voucher Status','Tissue Descriptor','External URLs','Associated Taxa','Associated Specimens');


    }
    function start()
    {   
        // /*
        $coll_num = 'KB17-277';
        self::search_collector_no($coll_num); //exit;
        // */
        $input_file = $this->input['path'].'input.xlsx';
        self::read_input_file($input_file);
        
    }
    private function read_input_file($input_file)
    {
        /*
        if($local_xls = Functions::save_remote_file_to_local($this->ant_habitat_mapping_file, array('cache' => 1, 'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'file_extension' => 'xlsx', 'expire_seconds' => false)))
        {}
        unlink($local_xls);
        */
        $final = array();
        require_library('XLSParser'); $parser = new XLSParser();
        debug("\n reading: " . $input_file . "\n");

        // $temp = $parser->convert_sheet_to_array($input_file); //automatically gets 1st sheet
        // $temp = $parser->convert_sheet_to_array($input_file, '3'); //gets the 4th sheet. '0' gets the 1st sheet.
        $sheet_name = 'Specimen Details';
        $temp = $parser->convert_sheet_to_array($input_file, NULL, NULL, false, $sheet_name);
        
        $headers = array_keys($temp);
        // print_r($temp);
        print_r($headers);
        
        $fld = $headers[0]; $i = -1;
        foreach($temp[$fld] as $col) { $i++;
            $rec = array();
            foreach($headers as $header) {
                $rec[$header] = $temp[$header][$i];
            }
            // print_r($rec); exit;
            $output = self::compute_output($rec, $sheet_name);
        }
        
        exit;
        return $final;
    }
    private function compute_output($rec, $sheet_name)
    {
        
    }
    private function search_collector_no($coll_num)
    {
        $url = str_replace('COLL_NUM', $coll_num, $this->api['coll_num']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $arr = json_decode($json, true);
            print_r($arr);
        }
    }
}
?>
