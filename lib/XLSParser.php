<?php
class XLSParser
{    
    public function convert_sheet_to_array($spreadsheet)
    {
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';
        
        $ext = end(explode('.', $spreadsheet));
        if($ext == "xls")$objReader = PHPExcel_IOFactory::createReader('Excel5');
        elseif($ext == "xlsx")$objReader = PHPExcel_IOFactory::createReader('Excel2007'); //memory intensive, slow response        
        
        $objPHPExcel = $objReader->load($spreadsheet);
        $objReader->setReadDataOnly(true);
        $objWorksheet = $objPHPExcel->getActiveSheet();        
        //$objWorksheet = $objPHPExcel->setActiveSheetIndex(1); ==> worksheet pointer        
        $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
        $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5
        
        $arr_label = array();
        $arr_value = array();
                
        for ($row = 1; $row <= $highestRow; ++$row) 
        {
            for ($col = 0; $col <= $highestColumnIndex; ++$col) 
            {
                $cell = self::cell_value($objWorksheet, $col, $row);                       
                if($row==1) $arr_label[]=$cell;
                else
                {
                    $index = $arr_label[$col];
                    if($index)$arr_value[$index][]=$cell;            
                }            
            }
        }
        return $arr_value;    
    }
        
    public function create_specialist_project_xml($arr)
    {
        $schema_taxa = array();
        $used_taxa = array();
        $i=0;        
        foreach($arr["Scientific Name"] as $sciname)
        {
            $taxon_identifier = $sciname;        
            if(@$used_taxa[$taxon_identifier]) $taxon_parameters = $used_taxa[$taxon_identifier];
            else
            {
                $taxon_parameters = array();
                $taxon_parameters["identifier"] = $arr["ID"][$i];
                $taxon_parameters["kingdom"] = ucfirst(trim($arr["Kingdom"][$i]));
                $taxon_parameters["phylum"] = ucfirst(trim($arr["Phylum"][$i]));       
                $taxon_parameters["class"] = ucfirst(trim($arr["Class"][$i]));
                $taxon_parameters["order"] = ucfirst(trim($arr["Order"][$i]));
                $taxon_parameters["family"] = ucfirst(trim($arr["Family"][$i]));        
                $taxon_parameters["genus"] = ucfirst(trim($arr["Genus"][$i]));
                $taxon_parameters["scientificName"]= ucfirst(trim($arr["Scientific Name"][$i]));
                $taxon_parameters["source"] = trim($arr["Source URL"][$i]);
                $taxon_parameters["dataObjects"]= array();        
                $used_taxa[$taxon_identifier] = $taxon_parameters;
            }            
            $used_taxa[$taxon_identifier] = $taxon_parameters;                                
            $i++;        
        }//foreach        
                
        foreach($used_taxa as $taxon_parameters)
        {
            $schema_taxa[] = new SchemaTaxon($taxon_parameters);
        }
        ////////////////////// ---
        //SchemaDocument::print_taxon_xml($schema_taxa);
        return SchemaDocument::get_taxon_xml($schema_taxa);
    }

    private function cell_value($obj, $col, $row)
    {
        $cell = $obj->getCellByColumnAndRow($col, $row)->getValue();
        if(self::is_formula($cell)) return $obj->getCellByColumnAndRow($col, $row)->getCalculatedValue();
        else return $cell;
    }    
    
    private function is_formula($cell)
    {
        if(substr($cell,0,1)=="=")return true;
        else return false;
    }
    
}
?>