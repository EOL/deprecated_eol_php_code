<?php
namespace php_active_record;

class MarineGEO_XLSParser
{
    const SUBJECTS = "";
    function __construct($labels, $resource_id, $app)
    {
        $this->labels = $labels;
        $this->resource_id = $resource_id;
        $this->app = $app;
        if($app == 'specimen_export') {
            $this->output['worksheets'] = array('Voucher Info', 'Taxonomy', 'Specimen Details', 'Collection Data');
            $this->sheet_mappings['Voucher Data'] = 'Voucher Info';
            $this->sheet_mappings['Taxonomy Data'] = 'Taxonomy';
            $this->sheet_mappings['Specimen Details'] = 'Specimen Details';
            $this->sheet_mappings['Collection Data'] = 'Collection Data';
            $this->resources['path'] = CONTENT_RESOURCE_LOCAL_PATH."MarineGEO/";
        }
        elseif($app == 'specimen_image_export') {
            $this->output['worksheets'] = array('Lab Sheet', 'MOOP');
            $this->sheet_mappings['Lab Sheet'] = 'Lab Sheet';
            $this->sheet_mappings['MOOP'] = 'MOOP';

            $this->resources['path'] = CONTENT_RESOURCE_LOCAL_PATH."MarineGEO_sie/";
        }
    }
    private function get_no_of_cols_per_worksheet($worksheet, $labels)
    {
        $final = 0;
        $main_heads = $labels[$worksheet];
        foreach($main_heads as $key => $fields) $final = $final + count($fields);
        return $final;
    }
    public function create_specimen_image_export()
    {
        // print_r($this->labels); exit;
        /*Array(
            [Sheet1] => Array(
                    [Lab Sheet] => Array(
                            [0] => Process ID
                            [1] => Sample ID
                            [2] => Field ID
                    [MOOP] => Array(
                            [0] => Image File
                            [1] => Original Specimen
                            [2] => View Metadata
                            [3] => Caption
                            [4] => Measurement
                            [5] => Measurement Type
                            [6] => Sample Id
                            [7] => Process Id
                            [8] => License Holder
                            [9] => License
                            [10] => License Year
                            [11] => License Institution
                            [12] => License Contact
                            [13] => Photographer
        */
        $alpha = array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H', 9 => 'I', 10 => 'J', 11 => 'K', 12 => 'L', 13 => 'M', 14 => 'N', 15 => 'O', 16 => 'P', 17 => 'Q', 18 => 'R', 19 => 'S', 20 => 'T', 21 => 'U', 22 => 'V', 23 => 'W', 24 => 'X ', 25 => 'Y', 26 => 'Z');
        $labels = $this->labels['Sheet1'];
        $output_file = $this->resources['path'].$this->resource_id.".xls";
        // print_r($this->labels); exit;
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';
        define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
        
        /*
        const cache_in_memory               = 'Memory';
        const cache_in_memory_gzip          = 'MemoryGZip';
        const cache_in_memory_serialized    = 'MemorySerialized';
        const cache_igbinary                = 'Igbinary';
        const cache_to_discISAM             = 'DiscISAM';
        const cache_to_apc                  = 'APC';
        const cache_to_memcache             = 'Memcache';
        const cache_to_phpTemp              = 'PHPTemp';
        const cache_to_wincache             = 'Wincache';
        const cache_to_sqlite               = 'SQLite';
        const cache_to_sqlite3              = 'SQLite3';
        */
        
        //set cache method
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory; //options for memory in CachedObjectStorageFactory.php
        if (!\PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            die($cacheMethod . " caching method is not available" . EOL);
        }

        $objPHPExcel = new \PHPExcel();

        // /*
        $worksheets = array_keys($labels);
        if($GLOBALS['ENV_DEBUG']) print_r($worksheets); //exit;
        $sheetIndex = -1;
        foreach($worksheets as $worksheet) { $sheetIndex++;
            // echo "\n$sheetIndex\n";
            $objPHPExcel->setActiveSheetIndex($sheetIndex);
            
            $no_of_cols_per_worksheet = self::get_no_of_cols_per_worksheet($worksheet, $labels);
            if($GLOBALS['ENV_DEBUG']) echo "\nno_of_cols_per_worksheet: $no_of_cols_per_worksheet\n";
            for($c = 1; $c <= $no_of_cols_per_worksheet; $c++) $objPHPExcel->getActiveSheet()->getColumnDimension($alpha[$c])->setWidth(20);
            
            $objPHPExcel->getActiveSheet()->setTitle($this->sheet_mappings[$worksheet]);
            // $objPHPExcel->getActiveSheet()->setCellValue('A1', "xxx");
            
            /*
            $main_heads = array_keys($labels[$worksheet]);
            if($GLOBALS['ENV_DEBUG']) print_r($main_heads);
            $col = 1;
            foreach($main_heads as $main_head) { //writing main heads
                $no_of_cols = count($labels[$worksheet][$main_head]);
                // echo "\ncols# $no_of_cols\n";
                $objPHPExcel->getActiveSheet()->mergeCells($alpha[$col]."1:".$alpha[$col+$no_of_cols-1]."1");
                $objPHPExcel->getActiveSheet()->setCellValue($alpha[$col]."1", $main_head);
                $objPHPExcel->getActiveSheet()->getStyle($alpha[$col]."1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $col = $col+$no_of_cols;
            }

            $col = 1;
            foreach($main_heads as $main_head) { //writing sub-heads
                $heads = $labels[$worksheet][$main_head];
                foreach($heads as $head) {
                    $objPHPExcel->getActiveSheet()->setCellValue($alpha[$col]."2", $head);
                    $col++;
                }
            }
            */
            
            $col = 1;
            $heads = $labels[$worksheet];
            foreach($heads as $head) {
                $objPHPExcel->getActiveSheet()->setCellValue($alpha[$col]."1", $head);
                $col++;
            }
            
            $main_heads = array();
            $row_num = 2;
            self::get_txt_file_write_2excel($objPHPExcel, $worksheet, $main_heads, $labels, $alpha, $row_num);
            
            $objPHPExcel->createSheet();
        }//loop worksheets

        $objPHPExcel->removeSheetByIndex(2);
        
        //save Excel file
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php';

        // /*
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($output_file);
        // */
        /*
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($output_file);
        */
        
        return;
        
    }
    public function create_specimen_export()
    {
        // print_r($this->labels); exit;
        /*Array(
            [Voucher Data] => Array(
                    [Specimen Info Metadata] => Array(
                            [0] => Sample ID
                            [1] => Field ID
                        )
                )
            [Taxonomy Data] => Array(
                    [Taxonomy Metadata] => Array(
                            [0] => Sample ID
                            [1] => Phylum
                        )
                    [Extended Fields (BOLD 3.1)] => Array(
                            [0] => Identification Method
                        )
                )
        */
        $alpha = array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H', 9 => 'I', 10 => 'J', 11 => 'K', 12 => 'L', 13 => 'M', 14 => 'N', 15 => 'O', 16 => 'P', 17 => 'Q', 18 => 'R', 19 => 'S', 20 => 'T', 21 => 'U', 22 => 'V', 23 => 'W', 24 => 'X ', 25 => 'Y', 26 => 'Z');
        $labels = $this->labels;
        $output_file = $this->resources['path'].$this->resource_id.".xls";
        // print_r($this->labels); exit;
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';
        define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
        
        /*
        const cache_in_memory               = 'Memory';
        const cache_in_memory_gzip          = 'MemoryGZip';
        const cache_in_memory_serialized    = 'MemorySerialized';
        const cache_igbinary                = 'Igbinary';
        const cache_to_discISAM             = 'DiscISAM';
        const cache_to_apc                  = 'APC';
        const cache_to_memcache             = 'Memcache';
        const cache_to_phpTemp              = 'PHPTemp';
        const cache_to_wincache             = 'Wincache';
        const cache_to_sqlite               = 'SQLite';
        const cache_to_sqlite3              = 'SQLite3';
        */
        
        //set cache method
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory; //options for memory in CachedObjectStorageFactory.php
        if (!\PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            die($cacheMethod . " caching method is not available" . EOL);
        }

        $objPHPExcel = new \PHPExcel();

        // /*
        $worksheets = array_keys($labels);
        if($GLOBALS['ENV_DEBUG']) print_r($worksheets); //exit;
        $sheetIndex = -1;
        foreach($worksheets as $worksheet) { $sheetIndex++;
            // echo "\n$sheetIndex\n";
            $objPHPExcel->setActiveSheetIndex($sheetIndex);
            
            $no_of_cols_per_worksheet = self::get_no_of_cols_per_worksheet($worksheet, $labels);
            if($GLOBALS['ENV_DEBUG']) echo "\nno_of_cols_per_worksheet: $no_of_cols_per_worksheet\n";
            for($c = 1; $c <= $no_of_cols_per_worksheet; $c++) $objPHPExcel->getActiveSheet()->getColumnDimension($alpha[$c])->setWidth(20);
            
            $objPHPExcel->getActiveSheet()->setTitle($this->sheet_mappings[$worksheet]);
            // $objPHPExcel->getActiveSheet()->setCellValue('A1', "xxx");
            
            // /*
            $main_heads = array_keys($labels[$worksheet]);
            if($GLOBALS['ENV_DEBUG']) print_r($main_heads);
            $col = 1;
            foreach($main_heads as $main_head) { //writing main heads
                $no_of_cols = count($labels[$worksheet][$main_head]);
                // echo "\ncols# $no_of_cols\n";
                $objPHPExcel->getActiveSheet()->mergeCells($alpha[$col]."1:".$alpha[$col+$no_of_cols-1]."1");
                $objPHPExcel->getActiveSheet()->setCellValue($alpha[$col]."1", $main_head);
                $objPHPExcel->getActiveSheet()->getStyle($alpha[$col]."1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $col = $col+$no_of_cols;
            }

            $col = 1;
            foreach($main_heads as $main_head) { //writing sub-heads
                $heads = $labels[$worksheet][$main_head];
                foreach($heads as $head) {
                    $objPHPExcel->getActiveSheet()->setCellValue($alpha[$col]."2", $head);
                    $col++;
                }
            }
            // */
            
            $row_num = 3;
            self::get_txt_file_write_2excel($objPHPExcel, $worksheet, $main_heads, $labels, $alpha, $row_num);
            
            $objPHPExcel->createSheet();
        }//loop worksheets

        $objPHPExcel->removeSheetByIndex(4);
        
        //save Excel file
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php';

        // /*
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($output_file);
        // */
        /*
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($output_file);
        */
        
        return;
        // */
        
        /* working when testing examples
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('Sheet ONE');
        
        $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', "merged title");
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        
        $objPHPExcel->getActiveSheet()->mergeCells('M1:O1');
        $objPHPExcel->getActiveSheet()->setCellValue('M1', "merged title 2");
        $objPHPExcel->getActiveSheet()->getStyle('M1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->createSheet();

        $objPHPExcel->setActiveSheetIndex(1);
        $objPHPExcel->getActiveSheet()->setTitle('Sheet 222');
        $objPHPExcel->createSheet();

        $objPHPExcel->setActiveSheetIndex(2);
        $objPHPExcel->getActiveSheet()->setTitle('Sheet 333');
        $objPHPExcel->createSheet();

        //save Excel file
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php';
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($output_file);
        */
    }
    private function get_txt_file_write_2excel($objPHPExcel, $worksheet, $main_heads, $labels, $alpha, $row_num)
    {
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", $worksheet).".txt";
        $i = 0;
        foreach(new FileIterator($filename) as $line_number => $line) { // 'true' will auto delete temp_filepath
            $i++; if(($i % 10000) == 0) echo "\n [$path] ".number_format($i) . " ";
            // if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                continue;
            }
            else {
                if(!@$row[0]) continue; //$row[0] is gbifID
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = $row[$k];
                    $k++;
                }
            }
            // print_r($rec); //exit("\nstopx\n");
            // /* start writing to excel
            $col = 1;
            if($main_heads) {
                foreach($main_heads as $main_head) { //writing sub-heads
                    $heads = $labels[$worksheet][$main_head];
                    foreach($heads as $head) {
                        $objPHPExcel->getActiveSheet()->setCellValue($alpha[$col].$row_num, $rec[$head]);
                        $col++;
                    }
                }
            }
            else {
                // print_r($labels); exit;
                $heads = $labels[$worksheet];
                foreach($heads as $head) {
                    $objPHPExcel->getActiveSheet()->setCellValue($alpha[$col].$row_num, $rec[$head]);
                    $col++;
                }
            }
            $row_num++;
            // */
        }
    }
    function cell_value($obj, $col, $row, $ext)
    {
        $cell = $obj->getCellByColumnAndRow($col, $row)->getValue();
        if($ext == "csv") return $cell;
        if(self::is_formula($cell)) return $obj->getCellByColumnAndRow($col, $row)->getCalculatedValue();
        else return $cell;
    }
    function is_formula($cell)
    {
        if(substr($cell, 0, 1) == "=") {
            /* to trap problems in a cell, display $cell here then exit */
            return true;
        }
        else return false;
    }
}
?>