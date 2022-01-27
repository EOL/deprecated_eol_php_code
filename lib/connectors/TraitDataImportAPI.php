<?php
namespace php_active_record;
/* ALL THIS FROM COPIED TEMPLATE: MarineGEOAPI.php
connectors: [marine_geo.php] [marine_geo_image.php] https://eol-jira.bibalex.org/browse/COLLAB-1004
real connector now: [trait_data_import.php] DATA-1882: spreadsheet to DwC-A widget
*/
class TraitDataImportAPI
{
    function __construct($app)
    {
        $this->resource_id = ''; //will be initialized in start()
        $this->app = $app;
        // $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        // $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        
        $this->download_options = array('cache' => 1, 'resource_id' => 'MarineGEO', 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //orig expires quarterly
        // $this->download_options['expire_seconds'] = false; //debug only

        $this->api['coll_num'] = 'http://www.boldsystems.org/index.php/API_Public/specimen?ids=COLL_NUM&format=json';
        
        /* ============================= START for specimen_export ============================= */
        if($app == 'specimen_export') {}
        /* ============================= END for specimen_export ============================= */

        /* ============================= START for image_export ============================= */
        if($app == 'trait_data_import') {
            // $this->input['path'] = DOC_ROOT.'/applications/specimen_image_export/temp/'; //input.xlsx
            $this->input['path'] = DOC_ROOT.'/applications/trait_data_import/temp/'; //input.xlsx
            $dir = $this->input['path'];
            if(!is_dir($dir)) mkdir($dir);
            
            // $this->resources['path'] = CONTENT_RESOURCE_LOCAL_PATH."MarineGEO_sie/";
            $this->resources['path'] = CONTENT_RESOURCE_LOCAL_PATH."Trait_Data_Import/";
            $dir = $this->resources['path'];
            if(!is_dir($dir)) mkdir($dir);
            
            $dir = $this->resources['path'].'TSVs';
            if(!is_dir($dir)) mkdir($dir);
            
            $this->input['worksheets'] = array('data', 'references', 'vocabulary'); //'data' is the 1st worksheet from Trait_template.xlsx
        }
        /* ============================= END for image_export ============================= */
    }
    function start($filename = false, $form_url = false, $uuid = false, $json = false)
    {
        /* copied template
        if($this->app == 'trait_data_import') {
            if($json) {
                $this->manual_entry = json_decode($json);
                self::generate_info_list_tsv($this->manual_entry->Proj);
            }
        } */
        
        // /* for $form_url:
        if($form_url && $form_url != '_') $filename = self::process_form_url($form_url, $uuid); //this will download (wget) and save file in /specimen_export/temp/
        // */
        
        if(pathinfo($filename, PATHINFO_EXTENSION) == "zip") { //e.g. input.xlsx.zip
            $filename = self::process_zip_file($filename);
        }
        
        if(!$filename) $filename = 'input.xlsx'; //kinda debug mode. not used in real operation. But ok to stay un-commented.
        $input_file = $this->input['path'].$filename; //e.g. $filename is 'input_Eli.xlsx'
        if(file_exists($input_file)) {
            $this->resource_id = pathinfo($input_file, PATHINFO_FILENAME);
            self::read_input_file($input_file); //writes to text files for reading in next step.
            // exit("\neli 3\n[$this->resource_id]\n");
            self::create_output_file();
        }
        else debug("\nInput file not found: [$input_file]\n");
    }
    private function process_form_url($form_url, $uuid)
    {   //wget -nc https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /Volumes/AKiTiO4/other_files/bundle_images/xxx/740.027116-1.jpg
        $ext = pathinfo($form_url, PATHINFO_EXTENSION);
        $target = $this->input['path'].$uuid.".".$ext;
        $cmd = WGET_PATH . " $form_url -O ".$target; //wget -nc --> means 'no overwrite'
        $cmd .= " 2>&1";
        $shell_debug = shell_exec($cmd);
        if(stripos($shell_debug, "ERROR 404: Not Found") !== false) exit("\n<i>URL path does not exist.\n$form_url</i>\n\n"); //string is found
        echo "\n---\n".trim($shell_debug)."\n---\n"; //exit;
        return pathinfo($target, PATHINFO_BASENAME);
    }
    private function process_zip_file($filename)
    {
        $test_temp_dir = create_temp_dir();
        $local = Functions::save_remote_file_to_local($this->input['path'].$filename);
        $output = shell_exec("unzip -o $local -d $test_temp_dir");
        // echo "<hr> [$output] <hr>";
        $ext = "xls";
        $new_local = self::get_file_inside_dir_with_this_extension($test_temp_dir."/*.$ext*");
        $new_local_ext = pathinfo($new_local, PATHINFO_EXTENSION);
        $destination = $this->input['path'].pathinfo($filename, PATHINFO_FILENAME).".$new_local_ext";
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
    /* =======================================START create DwCA ======================================= */
    private function create_output_file()
    {
        // /* trait data import does not create XML but rather DwCA
        self::create_DwCA();
        // */
    }
    private function create_DwCA()
    {
        $tsv['data'] = CONTENT_RESOURCE_LOCAL_PATH."Trait_Data_Import/".$this->resource_id."_data.txt";
        self::parse_tsv($tsv['data'], 'read_data');
    }
    
    private function parse_tsv($txtfile, $task)
    {   $i = 0; echo "\n[$task] [$txtfile]\n";
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec);
            print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxon name] => Sciuridae
                [kingdom] => 
                [phylum] => 
                [family] => 
                [eolid] => 8703
                [predicate] => behavioral circadian rhythm
                [value] => diurnal
                [units] => 
                [statistical method] => 
                [sex] => 
                [lifestage] => 
                [inherit] => yes
                [stops at] => 34418|111049
                [measurementremarks] => sun-loving chaps, squirrels
                [measurementmethod] => 
                [bibliographiccitation] => Hunt David M., Carvalho Livia S., Cowing Jill A. and Davies Wayne L. 2009Evolution and spectral tuning of visual pigments in birds and mammalsPhil. Trans. R. Soc. B3642941–2955. http://doi.org/10.1098/rstb.2009.0044
                [source] => http://doi.org/10.1098/rstb.2009.0044
                [referenceid] => Jones 2009
                [personal communication] => 
            )*/
            
        } //end foreach()
    }
    /* ========================================END create DwCA ======================================== */
    private function read_input_file($input_file)
    {
        $final = array();
        require_library('XLSParser'); $parser = new XLSParser();
        debug("\n reading: " . $input_file . "\n");
        /* tests
        $temp = $parser->convert_sheet_to_array($input_file);        //automatically gets 1st sheet
        $temp = $parser->convert_sheet_to_array($input_file, '3');   //gets the 4th sheet. '0' gets the 1st sheet.
        */
        $sheet_names = $this->input['worksheets'];
        foreach($sheet_names as $sheet_name) self::read_worksheet($sheet_name, $input_file, $parser);
    }
    private function read_worksheet($sheet_name, $input_file, $parser)
    {
        self::initialize_file($sheet_name);
        $temp = $parser->convert_sheet_to_array($input_file, NULL, NULL, false, $sheet_name);
        $headers = array_keys($temp);
        // print_r($temp); print_r($headers); exit("\neli 2\n"); //good debug
        
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
                // print_r($input_rec); exit("\neli 1\n");
                /*Array(
                    [taxon name] => Sciuridae
                    [kingdom] => 
                    [phylum] => 
                    [family] => 
                    [eolID] => 8703
                    [predicate] => behavioral circadian rhythm
                    [value] => diurnal
                    [units] => 
                    [statistical method] => 
                    [sex] => 
                    [lifestage] => 
                    [inherit] => yes
                    [stops at] => 34418|111049
                    [measurementRemarks] => sun-loving chaps, squirrels
                    [measurementMethod] => 
                    [bibliographicCitation] => Hunt David M., Carvalho Livia S., Cowing Jill A. and Davies Wayne L. 2009Evolution and spectral tuning of visual pigments in birds and mammalsPhil. Trans. R. Soc. B3642941–2955. http://doi.org/10.1098/rstb.2009.0044
                    [source] => http://doi.org/10.1098/rstb.2009.0044
                    [referenceID] => Jones 2009
                    [personal communication] => 
                )*/
                /* copied
                $output_rec = self::compute_output_rec($input_rec, $sheet_name);
                self::write_output_rec_2txt($output_rec, $sheet_name);
                */
                self::write_output_rec_2txt($input_rec, $sheet_name);
            }
        }
    }
    private function initialize_file($sheet_name)
    {
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
        $WRITE = Functions::file_open($filename, "w");
        fclose($WRITE);
    }
    private function write_output_rec_2txt($rec, $sheet_name)
    {
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
        $fields = array_keys($rec);
        $WRITE = Functions::file_open($filename, "a");
        clearstatcache(); //important for filesize()
        if(filesize($filename) == 0) fwrite($WRITE, implode("\t", $fields) . "\n");
        $save = array();
        foreach($fields as $fld) $save[] = $rec[$fld];
        fwrite($WRITE, implode("\t", $save) . "\n");
        fclose($WRITE);
    }
    function test() //very initial stages.
    {
        /*
        if($local_xls = Functions::save_remote_file_to_local($this->ant_habitat_mapping_file, array('cache' => 1, 'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'file_extension' => 'xlsx', 'expire_seconds' => false))) {}
        unlink($local_xls);
        */
    }
}
?>