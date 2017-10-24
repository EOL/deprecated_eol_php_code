<?php
namespace php_active_record;
require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';

class ExcelToText
{
    public static $row_delimiter = "\n";
    public static $field_delimeter = "\t";
    public static $field_enclosure = "";

    private $path_to_spreadsheet;
    private $path_to_specified_output_path;
    private $errors;
    private $spreadsheet_reader;

    public function __construct($path_to_spreadsheet, $path_to_specified_output_path = null)
    {
        $this->path_to_spreadsheet = $path_to_spreadsheet;
        $this->path_to_specified_output_path = $path_to_specified_output_path;
        $this->errors = array();
        try
        {
            $this->spreadsheet_reader = self::prepare_reader($this->path_to_spreadsheet);
        }catch (\Exception $e)
        {
            echo "\nhas errors\n";
            $this->errors[] = "Unable to read Excel file";
        }
    }

    public function reset_errors()
    {
        $this->errors = array();
    }

    public function errors()
    {
        return $this->errors;
    }

    public function output_directory()
    {
        if($this->path_to_specified_output_path)
        {
            if(is_dir($this->path_to_specified_output_path)) return $this->path_to_specified_output_path;
            else
            {
                if(mkdir($this->path_to_specified_output_path)) return $this->path_to_specified_output_path;
                $this->errors[] = "Unable to extract contents from Excel file";
                return null;
            }
        }
        return create_temp_dir('dwca');
    }

    public function output_file()
    {
        if($this->path_to_specified_output_path) return $this->path_to_specified_output_path;
        return temp_filepath(true, 'xml');
    }


    public function is_new_schema_spreadsheet()
    {
        // previous problems reading spreadsheet
        if($this->errors || @!$this->spreadsheet_reader) 
        {
            // print("\nis new schema 111\n");
            return false;
        }
        if(!$this->has_proper_new_schema_worksheets())
        {
            // print("\nis new schema 222\n");
            return false;
        }
        if(!$this->has_proper_new_schema_control_rows())
        {
            // print("\nis new schema 333\n");
            return false;
        }
        if($this->errors)
        {
            // print("\nis new schema 444\n");
            return false;
        }
        // print("\nis new schema TRUE\n");
        return true;
    }

    public function is_old_schema_spreadsheet()
    {
        // previous problems reading spreadsheet
        if($this->errors || @!$this->spreadsheet_reader) return false;
        if(!$this->has_proper_old_schema_worksheets()) return false;
        if($this->errors) return false;
        return true;
    }


    public function has_proper_new_schema_worksheets()
    {
        // previous problems reading spreadsheet
        if($this->errors || @!$this->spreadsheet_reader) return false;

        $sheet_names = $this->spreadsheet_reader->getSheetNames();
        foreach($sheet_names as &$name) $name = trim(strtolower($name));
        if(!in_array('media', $sheet_names)) $this->errors[] = "Missing `media` worksheet";
        if(!in_array('taxa', $sheet_names)) $this->errors[] = "Missing `taxa` worksheet";
        if(!in_array('common names', $sheet_names)) $this->errors[] = "Missing `comon names` worksheet";
        if(!in_array('references', $sheet_names)) $this->errors[] = "Missing `references` worksheet";
        if(!in_array('agents', $sheet_names)) $this->errors[] = "Missing `agents` worksheet";
        if(!in_array('controlled terms', $sheet_names)) $this->errors[] = "Missing `controlled terms` worksheet";

        // new problems - missing expected worksheets
        if($this->errors) return false;
        return true;
    }

    public function has_proper_new_schema_control_rows()
    {
        // previous problems reading spreadsheet
        if($this->errors || @!$this->spreadsheet_reader) return false;

        $controlled_fields = self::read_controlled_fields_from_spreadsheet($this->spreadsheet_reader);
        if(@$controlled_fields['media'][0]['label'] != 'MediaID') $this->errors[] = "Problem reading structure of spreadsheet [media : label]";
        if(@$controlled_fields['media'][0]['uri'] != 'http://purl.org/dc/terms/identifier') $this->errors[] = "Problem reading structure of spreadsheet [media : uri]";

        if(@$controlled_fields['taxa'][0]['label'] != 'Identifier') $this->errors[] = "Problem reading structure of spreadsheet [taxa : label]";
        if(@$controlled_fields['taxa'][0]['uri'] != 'http://rs.tdwg.org/dwc/terms/taxonID') $this->errors[] = "Problem reading structure of spreadsheet [taxa : uri]";

        if(@$controlled_fields['common names'][0]['label'] != 'TaxonID') $this->errors[] = "Problem reading structure of spreadsheet [common names : label]";
        if(@$controlled_fields['common names'][0]['uri'] != 'http://rs.tdwg.org/dwc/terms/taxonID') $this->errors[] = "Problem reading structure of spreadsheet [common names : uri]";

        if(@$controlled_fields['references'][0]['label'] != 'ReferenceID') $this->errors[] = "Problem reading structure of spreadsheet [references : label]";
        if(@$controlled_fields['references'][0]['uri'] != 'http://purl.org/dc/terms/identifier') $this->errors[] = "Problem reading structure of spreadsheet [references : uri]";

        if(@$controlled_fields['agents'][0]['label'] != 'AgentID') $this->errors[] = "Problem reading structure of spreadsheet [agents : label]";
        if(@$controlled_fields['agents'][0]['uri'] != 'http://purl.org/dc/terms/identifier') $this->errors[] = "Problem reading structure of spreadsheet [agents : uri]";

        if(@$controlled_fields['controlled terms'][0]['label'] != 'Agent Roles') $this->errors[] = "Problem reading structure of spreadsheet [controlled_terms : label]";
        # not really a URI here, but its in the place the URI is for other sheets
        if(@$controlled_fields['controlled terms'][0]['uri'] != 'Animator') $this->errors[] = "Problem reading structure of spreadsheet [controlled_terms : values]";

        // new problems - missing expected worksheets
        if($this->errors) return false;
        return true;
    }

    public function has_proper_old_schema_worksheets()
    {
        // previous problems reading spreadsheet
        if($this->errors || @!$this->spreadsheet_reader) return false;

        $sheet_names = $this->spreadsheet_reader->getSheetNames();
        foreach($sheet_names as &$name) $name = trim(strtolower($name));
        if(!in_array('contributors', $sheet_names)) $this->errors[] = "Missing `Contributors` worksheet";
        if(!in_array('attributions', $sheet_names)) $this->errors[] = "Missing `Attributions` worksheet";
        if(!in_array('text descriptions', $sheet_names)) $this->errors[] = "Missing `Text descriptions` worksheet";
        if(!in_array('references', $sheet_names)) $this->errors[] = "Missing `References` worksheet";
        if(!in_array('multimedia', $sheet_names)) $this->errors[] = "Missing `Multimedia` worksheet";
        if(!in_array('taxon information', $sheet_names)) $this->errors[] = "Missing `Taxon Information` worksheet";
        if(!in_array('more common names (optional)', $sheet_names)) $this->errors[] = "Missing `More common names (optional)` worksheet";
        if(!in_array('synonyms', $sheet_names)) $this->errors[] = "Missing `Synonyms` worksheet";

        // new problems - missing expected worksheets
        if($this->errors) return false;
        return true;
    }

    /*
        Returns the path to the directory that is the archive
    */
    public function convert_to_new_schema_archive()
    {
        // previous problems reading spreadsheet
        if($this->errors || @!$this->spreadsheet_reader) return false;
        if(!$this->is_new_schema_spreadsheet()) return false;

        $archive_temp_directory_path = $this->output_directory();
        // fail if for some reason there is no valid output directory
        if($archive_temp_directory_path === null) return false;

        $sheet_names = $this->spreadsheet_reader->getSheetNames();
        $worksheet_fields = array();
        // loop through all the worksheets in the file
        foreach($sheet_names as $sheet_index => $sheet_name)
        {
            if($sheet_name == "controlled terms") continue;
            $worksheet_reader = $this->spreadsheet_reader->setActiveSheetIndex($sheet_index);
            $worksheetTitle = $worksheet_reader->getTitle();
            $highest_row = $worksheet_reader->getHighestRow(); // e.g. 10
            $highest_column = $worksheet_reader->getHighestColumn(); // e.g 'F'
            $highest_column_index = \PHPExcel_Cell::columnIndexFromString($highest_column);
            $number_of_columns = ord($highest_column) - 64;

            if(!($OUTFILE = fopen($archive_temp_directory_path ."/$sheet_name.txt", "w+")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$archive_temp_directory_path ."/$sheet_name.txt");
              return;
            }
            $worksheet_fields[$sheet_name] = array();
            for($row_index = 1; $row_index <= $highest_row; $row_index++)
            {
                static $i = 0;
                $i++;
                // if($i % 100 == 0) echo "$i - ".time_elapsed()."\n";
                $values = array();
                for ($column_index = 0; $column_index < $highest_column_index; $column_index++)
                {
                    $cell = $worksheet_reader->getCellByColumnAndRow($column_index, $row_index, true);
                    if($cell === null) $value = null;
                    else $value = self::prepare_value($cell->getCalculatedValue());
                    /*
                        Row1: readable label
                        Row2: field type URI
                        Row3: required
                        Row4: foreign key worksheet
                        Row5: extension group label
                        Row6: extension thesaurus URI
                        Row7: definition
                        Row8: comment
                        Row9: extension thesaurus URI
                    */
                    if($row_index > 9)
                    {
                        $value = self::fix_spreadsheet_shorthand($sheet_name, @$worksheet_fields[$sheet_name][$column_index]['uri'], $value);
                        $values[] = $value;
                    }elseif($row_index == 1)
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
                    elseif($row_index == 9) $worksheet_fields[$sheet_name][$column_index]['example'] = $value;
                }

                if($values)
                {
                    $all_empty_values = true;
                    foreach($values as $value)
                    {
                        if($value)
                        {
                            $all_empty_values = false;
                            break;
                        }
                    }
                    if(!$all_empty_values)
                    {
                        $row = self::$field_enclosure .
                               implode(self::$field_enclosure . self::$field_delimeter . self::$field_enclosure, $values) .
                               self::$field_enclosure . self::$row_delimiter;
                        fwrite($OUTFILE, $row);
                    }
                }
            }
            fclose($OUTFILE);
        }

        if(!($META = fopen($archive_temp_directory_path ."/meta.xml", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ".$archive_temp_directory_path ."/meta.xml");
          return;
        }
        fwrite($META, self::meta_xml_from_worksheets($worksheet_fields));
        fclose($META);

        $info = pathinfo($archive_temp_directory_path);
        $temporary_tarball_path = temp_filepath();
        $final_tarball_path = $archive_temp_directory_path .".tar.gz";
        shell_exec("tar -czf $temporary_tarball_path --directory=". $info['dirname'] ."/". $info['basename'] ." .");
        @unlink($new_tarball_path);
        if(copy($temporary_tarball_path, $final_tarball_path))
          unlink($temporary_tarball_path);

        return $archive_temp_directory_path;
    }

    /*
        Returns a string containing XML representing the contents of the spreadsheet
    */
    public function convert_to_old_schema_xml()
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $xml = $parser->create_eol_xml($this->path_to_spreadsheet);

        $output_file = $this->output_file();
        if(!($OUT = fopen($output_file, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $output_file);
          return;
        }
        fwrite($OUT, $xml);
        fclose($OUT);
        return $output_file;
    }

    private static function fix_spreadsheet_shorthand($worksheet_name, $uri, $value)
    {
        if($worksheet_name == 'media' && strtolower($uri) == 'http://purl.org/dc/terms/type')
        {
            static $data_types = array('movingimage', 'sound', 'stillimage', 'text');
            if(in_array(strtolower($value), $data_types))
            {
                $value = "http://purl.org/dc/dcmitype/". $value;
            }
        }elseif($worksheet_name == 'media' && strtolower($uri) == 'http://iptc.org/std/iptc4xmpext/1.0/xmlns/cvterm')
        {
            static $subjects = array('associations', 'behaviour', 'biology', 'conservation', 'conservationstatus',
                'cyclicity', 'cytology', 'description', 'diagnosticdescription', 'diseases', 'dispersal', 'distribution',
                'ecology', 'evolution', 'generaldescription', 'genetics', 'growth', 'habitat', 'key', 'legislation',
                'lifecycle', 'lifeexpectancy', 'lookalikes', 'management', 'migration', 'molecularbiology', 'morphology',
                'physiology', 'populationbiology', 'procedures', 'reproduction', 'riskstatement', 'size', 'taxonbiology',
                'threats', 'trends', 'trophicstrategy', 'uses');
            if(in_array(strtolower($value), $subjects))
            {
                $value = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#". $value;
            }
        }elseif($worksheet_name == 'media' && strtolower($uri) == 'http://ns.adobe.com/xap/1.0/rights/usageterms')
        {
            if($value == 'cc-by 3.0') $value = "http://creativecommons.org/licenses/by/3.0/";
            elseif($value == 'cc-nc 3.0') $value = "http://creativecommons.org/licenses/by-nc/3.0/";
            elseif($value == 'cc-sa 3.0') $value = "http://creativecommons.org/licenses/by-sa/3.0/";
            elseif($value == 'cc-nc-sa 3.0') $value = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            elseif(strtolower($value) == 'public domain') $value = "http://creativecommons.org/licenses/publicdomain/";
        }
        return $value;
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
            elseif($worksheet_name == 'references') $row_type = 'http://eol.org/schema/reference/Reference';
            elseif($worksheet_name == 'agents') $row_type = 'http://eol.org/schema/agent/Agent';
            elseif($worksheet_name == 'measurements or facts') $row_type = 'http://rs.tdwg.org/dwc/terms/MeasurementOrFact';
            elseif($worksheet_name == 'associations') $row_type = 'http://eol.org/schema/Association';
            elseif($worksheet_name == 'occurrences') $row_type = 'http://rs.tdwg.org/dwc/terms/Occurrence';
            elseif($worksheet_name == 'events') $row_type = 'http://rs.tdwg.org/dwc/terms/Event';
            elseif($worksheet_name == 'locations') $row_type = 'http://purl.org/dc/terms/Location';
            elseif($worksheet_name == 'controlled terms') continue;
            else
            {
                // trigger_error("Unknown Worksheet Name: $worksheet_name", E_USER_WARNING);
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

    public static function read_controlled_fields_from_spreadsheet($spreadsheet_reader)
    {
        $worksheet_fields = array();
        $sheet_names = $spreadsheet_reader->getSheetNames();
        // loop through all the worksheets in the file
        foreach($sheet_names as $sheet_index => $sheet_name)
        {
            $worksheet_reader = $spreadsheet_reader->setActiveSheetIndex($sheet_index);
            foreach($worksheet_reader->getRowIterator() as $row_index => $row)
            {
                // only need to confirm the first 9 rows
                if($row_index > 9) break;
                $row_reader = $row->getCellIterator();
                $row_reader->setIterateOnlyExistingCells(false);
                foreach($row_reader as $column_index => $cell)
                {
                    $value = self::prepare_value($cell->getCalculatedValue());
                    /*
                        Row1: readable label
                        Row2: field type URI
                        Row3: required
                        Row4: foreign key worksheet
                        Row5: extension group label
                        Row6: extension thesaurus URI
                        Row7: definition
                        Row8: comment
                        Row9: extension thesaurus URI
                    */
                    if($row_index == 1) $worksheet_fields[$sheet_name][$column_index]['label'] = $value;
                    elseif($row_index == 2) $worksheet_fields[$sheet_name][$column_index]['uri'] = $value;
                    elseif($row_index == 3) $worksheet_fields[$sheet_name][$column_index]['required'] = strtolower($value);
                    elseif($row_index == 4) $worksheet_fields[$sheet_name][$column_index]['foreign_key'] = $value;
                    elseif($row_index == 5) $worksheet_fields[$sheet_name][$column_index]['group'] = $value;
                    elseif($row_index == 6) $worksheet_fields[$sheet_name][$column_index]['thesaurus'] = $value;
                    elseif($row_index == 7) $worksheet_fields[$sheet_name][$column_index]['definition'] = $value;
                    elseif($row_index == 8) $worksheet_fields[$sheet_name][$column_index]['comment'] = $value;
                    elseif($row_index == 9) $worksheet_fields[$sheet_name][$column_index]['example'] = $value;
                }
            }
        }
        return $worksheet_fields;
    }

    private static function prepare_reader($path_to_spreadsheet)
    {
        $info = pathinfo($path_to_spreadsheet);
        $extension = $info['extension'];
        if($extension == "xls") $excel_reader = \PHPExcel_IOFactory::createReader('Excel5');
        //memory intensive, slow response
        elseif($extension == "xlsx") $excel_reader = \PHPExcel_IOFactory::createReader('Excel2007');
        elseif($extension == "zip") $excel_reader = \PHPExcel_IOFactory::createReader('Excel2007');
        elseif($extension == "csv") $excel_reader = new \PHPExcel_Reader_CSV();
        // print "\nOK 111\n";
        if(!$excel_reader->canRead($path_to_spreadsheet)) throw new \Exception('Cannot read this file');
        if($extension != "csv") $excel_reader->setReadDataOnly(true);
        $objPHPExcel = $excel_reader->load($path_to_spreadsheet);
        // print "\nOK 222\n";
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
