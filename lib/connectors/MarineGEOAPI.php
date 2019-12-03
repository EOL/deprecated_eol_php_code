<?php
namespace php_active_record;
/* connector: [marine_geo.php] https://eol-jira.bibalex.org/browse/COLLAB-1004 */
class MarineGEOAPI
{
    function __construct($folder = null, $app)
    {
        $this->resource_id = $folder;
        $this->app = $app;
        // $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        // $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //orig expires quarterly
        // $this->download_options['expire_seconds'] = false; //debug only
        
        $this->api['coll_num'] = 'http://www.boldsystems.org/index.php/API_Public/specimen?ids=COLL_NUM&format=json';
        
        $this->input[$app]['path'] = DOC_ROOT.'/applications/specimen_export/temp/'; //input.xlsx
        $this->resources[$app]['path'] = CONTENT_RESOURCE_LOCAL_PATH."MarineGEO/";
        // $this->input[$app]['worksheets'] = array('Voucher Data', 'Specimen Details', 'Taxonomy Data', 'Collection Data'); //was never used

        /* Labels specimen export */
        $this->labels['Voucher Data']['Specimen Info Metadata'] = array('Sample ID','Field ID','Museum ID','Collection Code','Institution Storing');
        $this->labels['Taxonomy Data']['Taxonomy Metadata'] = array('Sample ID','Phylum','Class','Order','Family','Subfamily','Tribe','Genus','Species','Subspecies','Identifier','Identifier Email');
        $this->labels['Taxonomy Data']['Extended Fields (BOLD 3.1)'] = array('Identification Method','Taxonomy Notes');
        $this->labels['Specimen Details']['Specimen Details Metadata'] = array('Sample ID','Sex','Reproduction','Life Stage','Extra Info','Notes');
        $this->labels['Specimen Details']['Specimen Details Metadata Extended Fields (BOLD 3.1)'] = array('Voucher Status','Tissue Descriptor','External URLs','Associated Taxa','Associated Specimens');
        $this->labels['Collection Data']['Collection Info Metadata'] = array('Sample ID','Collectors','Collection Date','Country/Ocean','State/Province','Region','Sector','Exact Site','Lat','Lon','Elev');
        $this->labels['Collection Data']['Collection Info Metadata Extended Fields (BOLD 3.1)'] = array('Depth','Elevation Precision','Depth Precision','GPS Source','Coordinate Accuracy','Event Time','Collection Date Accuracy','Habitat','Sampling Protocol','Collection Notes','Site Code','Collection Event ID');

        /* ============================= START for image_export ============================= */
        
        /* ============================= END for image_export ============================= */
    }
    /* ========================================================== START for image_export ========================================================== */
    
    /* ========================================================== END for image_export ========================================================== */
    function start($filename = false, $form_url = false, $uuid = false)
    {   
        /* may not be needed since output.xls is based on input.xls
        $coll_num = 'KB17-277';
        self::search_collector_no($coll_num); exit;
        */
        /*
        if($local_xls = Functions::save_remote_file_to_local($this->ant_habitat_mapping_file, array('cache' => 1, 'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'file_extension' => 'xlsx', 'expire_seconds' => false))) {}
        unlink($local_xls);
        */
        
        // /* for $form_url:
        if($form_url) $filename = self::process_form_url($form_url, $uuid); //this will download (wget) and save file in /specimen_export/temp/
        // */
        
        if(pathinfo($filename, PATHINFO_EXTENSION) == "zip") { //e.g. input.xlsx.zip
            $filename = self::process_zip_file($filename);
        }
        
        if(!$filename) $filename = 'input.xlsx';
        $input_file = $this->input[$this->app]['path'].$filename; //e.g. $filename is 'input_Eli.xlsx'
        if(file_exists($input_file)) {
            $this->resource_id = pathinfo($input_file, PATHINFO_FILENAME);
            self::read_input_file($input_file); //writes to text files for reading in next step.
            self::create_output_file();
        }
        else debug("\nInput file not found: [$input_file]\n");
    }
    private function process_form_url($form_url, $uuid)
    {   //wget -nc https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /Volumes/AKiTiO4/other_files/bundle_images/xxx/740.027116-1.jpg
        // exit("\n[$form_url]\n");
        $ext = pathinfo($form_url, PATHINFO_EXTENSION);
        $target = $this->input[$this->app]['path'].$uuid.".".$ext;
        $cmd = WGET_PATH . " $form_url -O ".$target; //wget -nc --> means 'no overwrite'
        $cmd .= " 2>&1";
        $shell_debug = shell_exec($cmd);
        if(stripos($shell_debug, "ERROR 404: Not Found") !== false) { //string is found
            exit("\n<i>URL path does not exist.\n$form_url</i>\n\n");
        }
        echo "\n---\n".trim($shell_debug)."\n---\n"; //exit;
        return pathinfo($target, PATHINFO_BASENAME);
    }
    private function process_zip_file($filename)
    {
        $test_temp_dir = create_temp_dir();
        $local = Functions::save_remote_file_to_local($this->input[$this->app]['path'].$filename);
        $output = shell_exec("unzip -o $local -d $test_temp_dir");
        // echo "<hr> [$output] <hr>";
        $ext = "xls";
        $new_local = self::get_file_inside_dir_with_this_extension($test_temp_dir."/*.$ext*");
        $new_local_ext = pathinfo($new_local, PATHINFO_EXTENSION);
        $destination = $this->input[$this->app]['path'].pathinfo($filename, PATHINFO_FILENAME).".$new_local_ext";
        /* debug only
        echo "\n\nlocal file = [$local]";
        echo "\nlocal dir = [$test_temp_dir]";
        echo "\nnew local file = [$new_local]";
        echo "\nnew_local_ext = [$new_local_ext]\n\n";
        echo "\ndestination = [$destination]\n\n";
        */
        Functions::file_rename($new_local, $destination);
        if($GLOBALS['ENV_DEBUG']) print_r(pathinfo($destination));

        //remove these 2 that were used above if file is a zip file
        unlink($local);
        recursive_rmdir($test_temp_dir);

        return pathinfo($destination, PATHINFO_BASENAME);
    }
    private function get_file_inside_dir_with_this_extension($files)
    {
        $arr = glob($files);
        return $arr[0];
        // foreach (glob($files) as $filename) echo "\n- $filename\n";
    }
    /* =======================================START create output file======================================= */
    private function create_output_file()
    {
        require_library('MarineGEO_XLSParser');
        $parser = new MarineGEO_XLSParser($this->labels, $this->resource_id);
        $parser->start();
    }
    /* ========================================END create output file======================================== */
    private function read_input_file($input_file)
    {
        $final = array();
        require_library('XLSParser'); $parser = new XLSParser();
        debug("\n reading: " . $input_file . "\n");

        // $temp = $parser->convert_sheet_to_array($input_file); //automatically gets 1st sheet
        // $temp = $parser->convert_sheet_to_array($input_file, '3'); //gets the 4th sheet. '0' gets the 1st sheet.
        
        $sheet_names = array('Voucher Data', 'Specimen Details', 'Taxonomy Data', 'Collection Data');
        foreach($sheet_names as $sheet_name) self::read_worksheet($sheet_name, $input_file, $parser);
    }
    private function read_worksheet($sheet_name, $input_file, $parser)
    {
        self::initialize_file($sheet_name);
        $temp = $parser->convert_sheet_to_array($input_file, NULL, NULL, false, $sheet_name);
        $headers = array_keys($temp);
        // print_r($temp); print_r($headers); exit;
        
        $fld = $headers[0]; $i = -1;
        foreach($temp[$fld] as $col) { $i++;
            $input_rec = array();
            
            //START check if entire row is blank, if yes then ignore row
            $ignoreRow = true;
            foreach($headers as $header) {
                if($val = trim(@$temp[$header][$i])) {
                    $ignoreRow = false;
                    break;
                }
            }
            //END check if entire row is blank, if yes then ignore row

            if(!$ignoreRow) {
                foreach($headers as $header) $input_rec[$header] = $temp[$header][$i];
                // echo "\ncount $i\n";
                // print_r($input_rec); //exit;
                $output_rec = self::compute_output_rec($input_rec, $sheet_name);
                self::write_output_rec_2txt($output_rec, $sheet_name);
            }
        }
    }
    private function initialize_file($sheet_name)
    {
        $filename = $this->resources[$this->app]['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
        $WRITE = Functions::file_open($filename, "w");
        fclose($WRITE);
    }
    private function write_output_rec_2txt($rec, $sheet_name)
    {
        $filename = $this->resources[$this->app]['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
        $fields = array_keys($rec);
        $WRITE = Functions::file_open($filename, "a");
        clearstatcache(); //important for filesize()
        if(filesize($filename) == 0) fwrite($WRITE, implode("\t", $fields) . "\n");
        $save = array();
        foreach($fields as $fld) $save[] = $rec[$fld];
        fwrite($WRITE, implode("\t", $save) . "\n");
        fclose($WRITE);
    }
    private function compute_output_rec($input_rec, $sheet_name)
    {
        $output_rec = array();
        $subheads = array_keys($this->labels[$sheet_name]);
        foreach($subheads as $subhead) {
            $fields = $this->labels[$sheet_name][$subhead]; //print_r($fields);
            foreach($fields as $field) {
                $output_rec[$field] = self::construct_output($sheet_name, $field, $input_rec);
            }
        }
        // print_r($output_rec); //good debug
        // exit("\nx001\n");
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
            /*=====Voucher Data=====*/
            case "Sample ID":               return $input_rec['Collector Number: (Version 1.2 elements (2))'];
            case "Field ID":                return $input_rec['Collector Number: (Version 1.2 elements (2))'];
            case "Museum ID":               return $input_rec['Institution Code: (Version 1.2 elements (1))'].":FISH:".$input_rec['Catalog No.Text: (MaNIS extensions (1))'];
            case "Collection Code":         return '';
            case "Institution Storing":     return ''; //to be mapped
            /*=====Specimen Details=====*/
            case "Sample ID":               return $input_rec['Collector Number: (Version 1.2 elements (2))'];
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
            /*=====Collection Data=====*/
            case "Collectors":      return $input_rec['Collector: (Version 1.2 elements (2))'];
            case "Collection Date": return self::format_Collection_Date($input_rec);
            case "Country/Ocean":   return $input_rec['Country: (Version 1.2 elements (3))'];
            case "State/Province":  return $input_rec['State/Province: (Version 1.2 elements (3))'];
            case "Region":          return $input_rec['County: (Version 1.2 elements (3))'];
            case "Sector":          return '';
            case "Exact Site":      return $input_rec['Locality: (Version 1.2 elements (3))'];
            case "Lat":             return $input_rec['Latitude: (Version 1.2 elements (3))'];
            case "Lon":             return $input_rec['Longitude: (Version 1.2 elements (3))'];
            case "Elev":            return $input_rec['Maximum Elevation: (Version 1.2 elements (4))'];
            //2nd half
            case "Depth":                       return $input_rec['Maximum Depth: (Version 1.2 elements (4))'];
            case "Elevation Precision":         return $input_rec['Maximum Elevation: (Version 1.2 elements (4))']-$input_rec['Minimum Elevation: (Version 1.2 elements (4))'];
            case "Depth Precision":             return $input_rec['Maximum Depth: (Version 1.2 elements (4))']-$input_rec['Minimum Depth: (Version 1.2 elements (4))'];
            case "GPS Source":                  return ''; //to be mapped
            case "Coordinate Accuracy":         return ''; //to be mapped
            case "Event Time":                  return '';
            case "Collection Date Accuracy":    return '';
            case "Habitat":                     return '';
            case "Sampling Protocol":           return '';
            case "Collection Notes":            return $input_rec['Minimum Depth: (Version 1.2 elements (4))']."-".$input_rec['Maximum Depth: (Version 1.2 elements (4))']." m";
            case "Site Code":                   return '';
            case "Collection Event ID":         return $input_rec['Field Number: (Version 1.2 elements (2))'];
            /*=====End=====*/
            default:
                exit("\nInvestigate field [$field] not defined.\n");
        }
    }
    private function format_Collection_Date($input_rec)
    {
        return $input_rec['Day Collected: (Version 1.2 elements (3))'].";".$input_rec['Month Collected: (Version 1.2 elements (3))'].";".$input_rec['Year Collected: (Version 1.2 elements (2))'];
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
