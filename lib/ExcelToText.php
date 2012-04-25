<?php
namespace php_active_record;
require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';

class ExcelToText
{
    public static $row_delimiter = "\n";
    public static $field_delimeter = "\t";
    public static $field_enclosure = "";
    
    public static function worksheet_to_file($spreadsheet)
    {
        $archive_temp_directory_path = create_temp_dir('dwca');
        
        $objPHPExcel = self::prepare_reader($spreadsheet);
        $sheet_names = $objPHPExcel->getSheetNames();
        $worksheet_fields = array();
        foreach($sheet_names as $sheet_index => $sheet_name)
        {
            $objWorksheet = $objPHPExcel->setActiveSheetIndex($sheet_index);
            
            $OUTFILE = fopen($archive_temp_directory_path ."/$sheet_name.txt", "w+");
            $worksheet_fields[$sheet_name] = array();
            // $index will start at 1, not 0
            foreach($objWorksheet->getRowIterator() as $row_index => $row)
            {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $values = array();
                foreach($cellIterator as $column_index => $cell)
                {
                    $value = self::prepare_value($cell->getCalculatedValue());
                    /*
                        Row1: readable label
                        Row2: field type URI
                        Row3: required
                        Row4: foreign key worksheet
                        Row5: extension group label
                        Row6: extension thesaurus URI
                    */
                    if($row_index == 1)
                    {
                        $worksheet_fields[$sheet_name][$column_index]['label'] = $value;
                        $values[] = $value;
                    }
                    elseif($row_index == 2) $worksheet_fields[$sheet_name][$column_index]['uri'] = $value;
                    elseif($row_index == 3) $worksheet_fields[$sheet_name][$column_index]['required'] = strtolower($value);
                    elseif($row_index == 4) $worksheet_fields[$sheet_name][$column_index]['foreign_key'] = $value;
                    elseif($row_index == 5) $worksheet_fields[$sheet_name][$column_index]['group'] = $value;
                    elseif($row_index == 6) $worksheet_fields[$sheet_name][$column_index]['thesaurus'] = $value;
                    elseif($row_index == 7) $worksheet_fields[$sheet_name][$column_index]['definition'] = $value;
                    elseif($row_index == 8) $worksheet_fields[$sheet_name][$column_index]['comment'] = $value;
                    else $values[] = $value;
                }
                
                if($values)
                {
                    $row = self::$field_enclosure .
                           implode(self::$field_enclosure . self::$field_delimeter . self::$field_enclosure, $values) .
                           self::$field_enclosure . self::$row_delimiter;
                    fwrite($OUTFILE, $row);
                }
            }
            fclose($OUTFILE);
        }
        
        $META = fopen($archive_temp_directory_path ."/meta.xml", "w+");
        fwrite($META, self::meta_xml_from_worksheets($worksheet_fields));
        fclose($META);
        
        $info = pathinfo($archive_temp_directory_path);
        $dir_name = $info['basename'];
        shell_exec("tar -czf ". $archive_temp_directory_path .".tar.gz --directory=".DOC_ROOT."tmp/ $dir_name");
        return WEB_ROOT . 'tmp/'. $dir_name .'.tar.gz';
    }
    
    private static function meta_xml_from_worksheets($worksheet_fields)
    {
        $table_xmls = array();
        foreach($worksheet_fields as $worksheet_name => $fields)
        {
            $row_type = null;
            if($worksheet_name == 'media') $row_type = 'http://eol.org/schema/media/Document';
            elseif($worksheet_name == 'taxa') $row_type = 'http://rs.tdwg.org/dwc/terms/Taxon';
            elseif($worksheet_name == 'common names') $row_type = 'http://rs.gbif.org/terms/1.0/VernacularName';
            elseif($worksheet_name == 'references') $row_type = 'http://purl.org/ontology/bibo/Document';
            elseif($worksheet_name == 'agents') $row_type = 'http://eol.org/schema/agents/Agent';
            elseif($worksheet_name == 'controlled terms') continue;
            else
            {
                trigger_error("Unknown Worksheet Name: $worksheet_name", E_USER_WARNING);
                continue;
            }
            $table_xml = '  <table encoding="UTF-8"';
            $table_xml .= ' fieldsTerminatedBy="'. self::escape(self::$field_delimeter) .'"';
            $table_xml .= ' linesTerminatedBy="'. self::escape(self::$row_delimiter) .'"';
            if(self::$field_enclosure) $table_xml .= ' fieldsEnclosedBy="'. self::escape(self::$field_enclosure) .'"';
            $table_xml .= ' ignoreHeaderLines="1"';
            $table_xml .= ' rowType="'. $row_type .'"';
            $table_xml .= ">\n";
            $table_xml .= "    <files><location>$worksheet_name.txt</location></files>\n";
            
            $table_fields = array();
            foreach($fields as $field_index => $field_metadata)
            {
                $field_xml = "    <field index=\"$field_index\"";
                $field_xml .= ' term="'. $field_metadata['uri'] .'"';
                $field_xml .= '/>';
                $table_fields[] = $field_xml;
            }
            $table_xml .= implode("\n", $table_fields);
            $table_xml .= "\n  </table>";
            
            $table_xmls[] = $table_xml;
        }
        
        $meta_xml = "<?xml version=\"1.0\"?>\n";
        $meta_xml .= "<archive xmlns=\"http://rs.tdwg.org/dwc/text/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://rs.tdwg.org/dwc/text/  http://services.eol.org/schema/dwca/tdwg_dwc_text.xsd\">\n";
        $meta_xml .= implode("\n", $table_xmls);
        $meta_xml .= "\n</archive>";
        return $meta_xml;
    }
    
    private static function prepare_reader($path_to_spreadsheet)
    {
        $info = pathinfo($path_to_spreadsheet);
        $extension = $info['extension'];
        if($extension == "xls") $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        //memory intensive, slow response
        elseif($extension == "xlsx") $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        elseif($extension == "csv") $objReader = new \PHPExcel_Reader_CSV();
        
        $objPHPExcel = $objReader->load($path_to_spreadsheet);
        if($extension != "csv") $objReader->setReadDataOnly(true);
        return $objPHPExcel;
    }
    
    private static function prepare_value($value)
    {
        if(self::$row_delimiter)
        {
            $value = str_replace(self::$row_delimiter, $GLOBALS['db_connection']->real_escape_string(self::$row_delimiter), $value);
        }
        if(self::$field_delimeter)
        {
            $value = str_replace(self::$field_delimeter, $GLOBALS['db_connection']->real_escape_string(self::$field_delimeter), $value);
        }
        if(self::$field_enclosure)
        {
            $value = str_replace(self::$field_enclosure, $GLOBALS['db_connection']->real_escape_string(self::$field_enclosure), $value);
        }
        return $value;
    }
    
    private static function escape($str)
    {
        $str = $GLOBALS['db_connection']->real_escape_string($str);
        $str = str_replace("\t", "\\t", $str);
        return $str;
    }
}
?>