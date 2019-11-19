<?php
namespace php_active_record;

class MarineGEO_XLSParser
{
    const SUBJECTS = "";
    function __construct($labels)
    {
        $this->labels = $labels;
        $this->output['worksheets'] = array('Voucher Info', 'Taxonomy', 'Specimen Details', 'Collection Data');
        $this->sheet_mappings['Voucher Info'] = 'Voucher Data';
        $this->sheet_mappings['Taxonomy'] = 'Taxonomy Data';
        $this->sheet_mappings['Specimen Details'] = 'Specimen Details';
        $this->sheet_mappings['Collection Data'] = 'Collection Data';
    }
    public function start()
    {
        $output_file = CONTENT_RESOURCE_LOCAL_PATH."Isaiah.xls";
        // print_r($this->labels);
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';
        $objPHPExcel = new \PHPExcel();
        
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('Sheet ONE');
        $objPHPExcel->createSheet();
        
        $objPHPExcel->setActiveSheetIndex(1);
        $objPHPExcel->getActiveSheet()->setTitle('Sheet 222');
        $objPHPExcel->createSheet();

        //save Excel file
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php';
        // require_once '/Library/WebServer/Documents/eol_php_code/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php'; //by Eli
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($output_file);
    }
    public function convert_sheet_to_array($spreadsheet, $sheet_index_number = NULL, $startRow = NULL, $save_params = false, $sheet_index_name = NULL)
    {
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';
        
        if(!isset($this->open_spreadsheets)) $this->open_spreadsheets = array();
        $temp = explode('.', $spreadsheet); //to avoid E_STRICT warning - only variables can be passed by reference
        $ext = strtolower(end($temp));
        if(isset($this->open_spreadsheets['spreadsheet']))
        {
            $objPHPExcel = $this->open_spreadsheets['spreadsheet'];
        }else
        {
            if    ($ext == "xls") $objReader = \PHPExcel_IOFactory::createReader('Excel5');
            elseif($ext == "xlsx")$objReader = \PHPExcel_IOFactory::createReader('Excel2007'); //memory intensive, slow response
            elseif($ext == "zip") $objReader = \PHPExcel_IOFactory::createReader('Excel2007'); //memory intensive, slow response
            elseif($ext == "csv") $objReader = new \PHPExcel_Reader_CSV();
            if($ext != "csv") $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($spreadsheet);
            $this->open_spreadsheets['spreadsheet'] = $objPHPExcel;
        }
        if(is_null($sheet_index_number)) {
            if(is_null($sheet_index_name)) $objWorksheet = $objPHPExcel->getActiveSheet();
            else                           $objWorksheet = $objPHPExcel->setActiveSheetIndexByName($sheet_index_name);
        }
        else
        {
            if($sheet_index_number+1 > $objPHPExcel->getSheetCount()) return false;
            $objWorksheet = $objPHPExcel->setActiveSheetIndex($sheet_index_number);
        }
        $highestRow         = $objWorksheet->getHighestRow(); // e.g. 10
        $highestColumn      = $objWorksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5
        $sheet_label = array();
        $sheet_value = array();
        if(is_null($startRow)) $startRow = 1;
        if($save_params) $FILE = Functions::file_open($save_params['path']."/".$save_params['worksheet_title'].".txt", 'w');
        for ($row = $startRow; $row <= $highestRow; ++$row)
        {
            if($save_params) $saved_row = array();
            for ($col = 0; $col <= $highestColumnIndex; ++$col)
            {
                $cell = self::cell_value($objWorksheet, $col, $row, $ext);
                if($row == $startRow)
                {
                    $sheet_label[] = $cell;
                    if($save_params) $saved_row[] = $cell;
                }
                else
                {
                    // /* special
                    if($sheet_label[0] == $sheet_label[1]) {
                        $sheet_label[1] .= "_2";
                    }
                    // print_r($sheet_label); exit;
                    // */
                    $index = trim($sheet_label[$col]);
                    if($index)
                    {
                        if($save_params) $saved_row[] = $cell;
                        else $sheet_value[$index][] = $cell;
                    }
                }
            }
            if($save_params) fwrite($FILE, implode("\t", $saved_row)."\n");
        }
        if($save_params) fclose($FILE);
        return $sheet_value;
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