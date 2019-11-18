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

        /* Labels */
        $this->labels['Voucher Info']['Specimen Info Metadata'] = array('Sample ID','Field ID','Museum ID','Collection Code','Institution Storing');
        $this->labels['Taxonomy Data']['Taxonomy Metadata'] = array('Sample ID','Phylum','Class','Order','Family','Subfamily','Tribe','Genus','Species','Subspecies','Identifier','Identifier Email');
        $this->labels['Taxonomy Data']['Extended Fields (BOLD 3.1)'] = array('Identification Method','Taxonomy Notes');
        $this->labels['Specimen Details']['Specimen Details Metadata'] = array('Sample ID','Sex','Reproduction','Life Stage','Extra Info','Notes');
        $this->labels['Specimen Details']['Specimen Details Metadata Extended Fields (BOLD 3.1)'] = array('Voucher Status','Tissue Descriptor','External URLs','Associated Taxa','Associated Specimens');
        $this->labels['Collection Data']['Collection Info Metadata'] = array('Sample ID','Collectors','Collection Date','Country/Ocean','State/Province','Region','Sector','Exact Site','Lat','Lon','Elev');
        $this->labels['Collection Data']['Collection Info Metadata Extended Fields (BOLD 3.1)'] = array('Depth','Elevation Precision','Depth Precision','GPS Source','Coordinate Accuracy','Event Time','Collection Date Accuracy','Habitat','Sampling Protocol','Collection Notes','Site Code','Collection Event ID');
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
        $sheet_name = 'Specimen Details'; //from input
        $sheet_name = 'Taxonomy Data';
        $temp = $parser->convert_sheet_to_array($input_file, NULL, NULL, false, $sheet_name);
        
        $headers = array_keys($temp);
        // print_r($temp);
        print_r($headers);
        
        $fld = $headers[0]; $i = -1;
        foreach($temp[$fld] as $col) { $i++;
            $input_rec = array();
            foreach($headers as $header) {
                $input_rec[$header] = $temp[$header][$i];
            }
            print_r($input_rec); //exit;
            $output_rec = self::compute_output_rec($input_rec, $sheet_name);
            exit;
        }
        
        exit;
        return $final;
    }
    private function compute_output_rec($input_rec, $sheet_name)
    {
        $output_rec = array();
        $subheads = array_keys($this->labels[$sheet_name]);
        foreach($subheads as $subhead) {
            $fields = $this->labels[$sheet_name][$subhead]; print_r($fields);
            foreach($fields as $field) {
                $output_rec[$field] = self::construct_output($sheet_name, $field, $input_rec);
            }
        }
        print_r($output_rec);
        exit("\nx001\n");
        return $output_rec;
    }
    private function construct_output($sheet_name, $field, $input_rec)
    {   /* Array(
        [Collector Number: (Version 1.2 elements (2))] => KB17-277
        [Sex: (Sex/Stage)] => F
        [Reproduction Description] => nonreproductive
        [Life Stage: (Version 1.3 changes (1))] => juv
        [Kind: (Measurements Details)] => 
        [Verbatim value: (Measurements Details)] => 
        [Unit: (Measurements Details)] => 
        [Note: (Note Details)] => Archival sample.
        [Secondary Sample Type] => Fin-clip
        [GUID: (GUIDs)] => ark:/65665/3d6ffd3e4-1188-40e4-848a-2f1dff71abe0
        )
        Array(
            [0] => Sample ID
            [1] => Sex
            [2] => Reproduction
            [3] => Life Stage
            [4] => Extra Info
            [5] => Notes
        )
        Array(
            [0] => Voucher Status
            [1] => Tissue Descriptor
            [2] => External URLs
            [3] => Associated Taxa
            [4] => Associated Specimens
        )*/
        switch ($field) {
            /*=====Specimen Details=====*/
            case "Sample ID": return $input_rec['Collector Number: (Version 1.2 elements (2))'];
                // echo "Your favorite color is red!";
                // break;
            case "Sex":                     return $input_rec['Sex: (Sex/Stage)'];
            case "Reproduction":            return $input_rec['Reproduction Description'];
            case "Life Stage":              return $input_rec['Life Stage: (Version 1.3 changes (1))'];
            case "Extra Info":              return ''; //No Equivalent
            case "Notes":                   return $input_rec['Note: (Note Details)'];
            case "Voucher Status":          return ''; //No Equivalent
            case "Tissue Descriptor":       return ''; //to be mapped
            case "External URLs":           return "http://n2t.net/".$input_rec['GUID: (GUIDs)'];
            case "Associated Taxa":         return ''; //No Equivalent
            case "Associated Specimens":    return ''; //No Equivalent
            /*=====Taxonomy Data=====*/
            case "Phylum":                  return $input_rec['Phylum: (Version 1.2 elements (1))'];
            case "Class":                   return $input_rec['Class: (Version 1.2 elements (1))'];
            case "Order":                   return $input_rec['Order: (Version 1.2 elements (1))'];
            case "Family":                  return $input_rec['Family: (Version 1.2 elements (1))'];
            case "Subfamily":               return ''; //No Equivalent
            case "Tribe":                   return ''; //No Equivalent
            case "Genus":                   return $input_rec['Genus: (Version 1.2 elements (1))'];
            case "Species":                 return $input_rec['Species: (Version 1.2 elements (2))'];
            case "Subspecies":              return ''; //get from API
            case "Identifier":              return $input_rec['Identified By: (Version 1.2 elements (2))'];
            case "Identifier Email":        return ''; //No Equivalent
            case "Identification Method":   return ''; //No Equivalent
            case "Taxonomy Notes":          return ''; //No Equivalent
            default:
                exit("\nInvestigate field [$field] not defined.\n");
        }
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
