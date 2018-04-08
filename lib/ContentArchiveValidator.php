<?php
namespace php_active_record;

class ContentArchiveValidator
{
    private $content_archive_reader;
    private $structural_errors;
    private $errors_by_line;
    private $warnings_by_line;
    private $archive_resource;
    private $skip_warnings;
    private $stats;

    public function __construct($content_archive_reader, $resource = NULL)
    {
        if(get_class($content_archive_reader) != 'php_active_record\ContentArchiveReader') return null;
        $this->content_archive_reader = $content_archive_reader;
        $this->validation_has_run = false;
        $this->archive_resource = $resource;
        $this->skip_warnings = false;
    }

    public function is_valid($skip_warnings = false)
    {
        $this->get_validation_errors($skip_warnings);
        if($this->structural_errors){
        	return false;
        }
        return true;
    }

    public function structural_errors()
    {
        return $this->structural_errors;
    }
    public function stats()
    {
        return $this->stats;
    }

    public function has_error_by_line($row_type, $file_location, $line_number)
    {
        if(isset($this->errors_by_line[$row_type][$file_location][$line_number])) return true;
        return false;
    }

    public function display_errors()
    {
        return self::group_exceptions($this->errors_by_line);
    }

    public function display_warnings()
    {
        return self::group_exceptions($this->warnings_by_line);
    }

    public static function group_exceptions($exceptions_by_line)
    {
        $grouped_exceptions = array();
        foreach($exceptions_by_line as $row_type => $files)
        {
            foreach($files as $file_location => $data)
            {
                foreach($data as $line_number => $exceptions)
                {
                    foreach($exceptions as $exception)
                    {
                        if(!isset($grouped_exceptions[$row_type][$file_location][$exception->uri][$exception->value][$exception->message]))
                        {
                            $grouped_exceptions[$row_type][$file_location][$exception->uri][$exception->value][$exception->message] = $exception;
                        }elseif($line_number)
                        {
                            $grouped_exceptions[$row_type][$file_location][$exception->uri][$exception->value][$exception->message]->line .= ", $line_number";
                        }
                    }
                }
            }
        }
        $simplified_errors = array();
        foreach($grouped_exceptions as $row_type => $d1)
        {
            foreach($d1 as $file_location => $d2)
            {
                foreach($d2 as $uri => $d3)
                {
                    foreach($d3 as $value => $d4)
                    {
                        foreach($d4 as $message => $exception)
                        {
                            $simplified_errors[] = $exception;
                        }
                    }
                }
            }
        }
        return $simplified_errors;
    }

    public function get_validation_errors($skip_warnings = false)
    {
        $this->skip_warnings = $skip_warnings;
        if($this->validation_has_run) return;
        $this->validation_has_run = true;

        $this->structural_errors = array();
        $this->errors_by_line = array();
        $this->warnings_by_line = array();
        $this->primary_keys_by_row_type = array();
        $this->stats = array();
        if(!$this->content_archive_reader->tables)
        {
            $error = new \eol_schema\ContentArchiveError();
            $error->message = 'Cannot read meta.xml. There may be a structural problem with this archive.';
            write_to_resource_harvesting_log("Cannot read meta.xml. There may be a structural problem with this archive.");
            $this->structural_errors[] = $error;
        }

        $classes_to_validate = array(
            '\eol_schema\MediaResource',
            '\eol_schema\Taxon',
            '\eol_schema\Reference',
            '\eol_schema\Agent',
            '\eol_schema\VernacularName',
            '\eol_schema\MeasurementOrFact',
            '\eol_schema\Association',
            '\eol_schema\Occurrence',
            '\eol_schema\Event'
        );
        $row_types_to_validate = array();
        foreach($classes_to_validate as $class_name)
        {
            if($class_name::validation_rules()) $row_types_to_validate[] = strtolower($class_name::ROW_TYPE);
        }

        foreach($this->content_archive_reader->tables as $row_type => $tables)
        {
            // TODO: duplicate primary keys
            // TODO: referential integrity
            if(!in_array(strtolower($row_type), $row_types_to_validate)) continue;
            if($GLOBALS['ENV_DEBUG'])
            {
            	write_to_resource_harvesting_log("Processing $row_type");
            	echo "Processing $row_type\n";
            }
            $this->content_archive_reader->process_row_type($row_type, array($this, 'validate_row'), array('row_type' => $row_type));
            if($row_type == 'http://rs.tdwg.org/dwc/terms/taxon')
            {
                $count_of_all_taxa = @$this->stats[$row_type]['Total'];
                if(!$count_of_all_taxa)
                {
                    $error = new \eol_schema\ContentArchiveError();
                    $error->message = 'There are no valid taxa in this archive.';
                    write_to_resource_harvesting_log("There are no valid taxa in this archive.");
                    $this->structural_errors[] = $error;
                }
            }
        }
    }

    public function validate_row($row, $parameters)
    {
        static $i = 0;
        $i++;
        if($i % 10000 == 0 && $GLOBALS['ENV_DEBUG'])
        {
        	echo "$i: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
        	write_to_resource_harvesting_log($i . ": ". time_elapsed() . "::" . memory_get_usage());
        }

        $file_location = $parameters['archive_table_definition']->location;
        $new_exceptions = array();
        if($parameters['row_type'] == 'http://eol.org/schema/media/document')
        {
            if(@!$row['http://purl.org/dc/terms/license'] && @!$row['http://ns.adobe.com/xap/1.0/rights/UsageTerms'])
            {
                if($this->archive_resource && $this->archive_resource->license && $this->archive_resource->license->source_url)
                {
                    $row['http://ns.adobe.com/xap/1.0/rights/UsageTerms'] = $this->archive_resource->license->source_url;
                    unset($row['http://purl.org/dc/terms/license']);
                }
            }
            $new_exceptions = \eol_schema\MediaResource::validate_by_hash($row, $this->skip_warnings);
            $this->append_identifier_error($row, 'http://purl.org/dc/terms/identifier', $parameters, $new_exceptions);
            if(!self::any_exceptions_of_type_error($new_exceptions))
            {
                if(@$v = $row['http://purl.org/dc/terms/type']) $this->add_stat('type', $parameters['row_type'], $file_location, $v);
                if(@$v = $row['http://rs.tdwg.org/audubon_core/subtype']) $this->add_stat('subtype', $parameters['row_type'], $file_location, $v);
                if(@$v = $row['http://ns.adobe.com/xap/1.0/rights/UsageTerms']) $this->add_stat('license', $parameters['row_type'], $file_location, $v);
                if(@$v = $row['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm']) $this->add_stat('subject', $parameters['row_type'], $file_location, $v);
                if(@$v = $row['http://purl.org/dc/terms/language']) $this->add_stat('language', $parameters['row_type'], $file_location, $v);
                if(@$v = $row['http://purl.org/dc/terms/format']) $this->add_stat('format', $parameters['row_type'], $file_location, $v);
            }
        }elseif($parameters['row_type'] == 'http://rs.tdwg.org/dwc/terms/taxon')
        {
            $new_exceptions = \eol_schema\Taxon::validate_by_hash($row, $this->skip_warnings);
            $this->append_identifier_error($row, 'http://rs.tdwg.org/dwc/terms/taxonID', $parameters, $new_exceptions);
        }elseif($parameters['row_type'] == 'http://rs.gbif.org/terms/1.0/vernacularname')
        {
            $new_exceptions = \eol_schema\VernacularName::validate_by_hash($row, $this->skip_warnings);
            if(!self::any_exceptions_of_type_error($new_exceptions))
            {
                if(@$v = $row['http://purl.org/dc/terms/language']) $this->add_stat('language', $parameters['row_type'], $file_location, $v);
            }
        }elseif($parameters['row_type'] == 'http://eol.org/schema/reference/reference')
        {
            $new_exceptions = \eol_schema\Reference::validate_by_hash($row, $this->skip_warnings);
            $this->append_identifier_error($row, 'http://purl.org/dc/terms/identifier', $parameters, $new_exceptions);
        }elseif($parameters['row_type'] == 'http://eol.org/schema/agent/agent')
        {
            $new_exceptions = \eol_schema\Agent::validate_by_hash($row, $this->skip_warnings);
            $this->append_identifier_error($row, 'http://purl.org/dc/terms/identifier', $parameters, $new_exceptions);
        }elseif($parameters['row_type'] == 'http://rs.tdwg.org/dwc/terms/measurementorfact')
        {
            $new_exceptions = \eol_schema\MeasurementOrFact::validate_by_hash($row, $this->skip_warnings);
            $this->append_identifier_error($row, 'http://rs.tdwg.org/dwc/terms/measurementID', $parameters, $new_exceptions);
        }elseif($parameters['row_type'] == 'http://eol.org/schema/association')
        {
            $new_exceptions = \eol_schema\Association::validate_by_hash($row, $this->skip_warnings);
            $this->append_identifier_error($row, 'http://eol.org/schema/associationID', $parameters, $new_exceptions);
        }

        if(!self::any_exceptions_of_type_error($new_exceptions))
        {
            if(!isset($this->stats[$parameters['row_type']])) $this->stats[$parameters['row_type']] = array();
            if(!isset($this->stats[$parameters['row_type']]['Total'])) $this->stats[$parameters['row_type']]['Total'] = 0;
            $this->stats[$parameters['row_type']]['Total']++;
        }
        if($new_exceptions)
        {
            foreach($new_exceptions as $exception)
            {
                $exception->file = $parameters['archive_table_definition']->location;
                $exception->line = $parameters['archive_line_number'];
                if(get_class($exception) == 'eol_schema\ContentArchiveError')
                {
                    if(!isset($this->errors_by_line[$parameters['row_type']][$file_location][$exception->line]))
                    {
                        $this->errors_by_line[$parameters['row_type']][$file_location][$exception->line] = array();
                    }
                    $this->errors_by_line[$parameters['row_type']][$file_location][$exception->line][] = $exception;
                }elseif(!$this->skip_warnings)
                {
                    if(!isset($this->warnings_by_line[$parameters['row_type']][$file_location][$exception->line]))
                    {
                        $this->warnings_by_line[$parameters['row_type']][$file_location][$exception->line] = array();
                    }
                    $this->warnings_by_line[$parameters['row_type']][$file_location][$exception->line][] = $exception;
                }
            }
        }
    }

    private function append_identifier_error($row, $identifier_uri, $parameters, &$errors)
    {
        if($id = @$row[$identifier_uri])
        {
            if(isset($this->primary_keys_by_row_type[$parameters['row_type']][$id]))
            {
                $errors[] = new \eol_schema\ContentArchiveError(array('message' => 'Duplicate identifiers'));
            }else $this->primary_keys_by_row_type[$parameters['row_type']][$id] = true;
        }
    }

    private function add_stat($label, $row_type, $file_location, $value)
    {
        $index = "Total by $label";
        if(!isset($this->stats[$row_type][$index])) $this->stats[$row_type][$index] = array();
        if(!isset($this->stats[$row_type][$index][$value])) $this->stats[$row_type][$index][$value] = 0;
        $this->stats[$row_type][$index][$value]++;
    }

    public function delete_validation_cache()
    {
        unset($this->primary_keys_by_row_type);
        unset($this->stats);
        unset($this->warnings_by_line);
    }

    public static function validate_url($url, $suffix = null)
    {
        if(!$suffix && preg_match("/\.([a-z]{2,4})$/", $url, $arr)) $suffix = $arr[1];
        if($temp_dir = ContentManager::download_temp_file_and_assign_extension($url, $suffix, array('suffix' => $suffix, 'timeout' => 900))) //15 minutes timeout (900 seconds)
        {
            if(is_dir($temp_dir))
            {
                if(file_exists($temp_dir . "/meta.xml"))
                {
                    $archive = new ContentArchiveReader(null, $temp_dir);
                    $validator = new ContentArchiveValidator($archive);
                    $validator->get_validation_errors();
                    recursive_rmdir($temp_dir); //Nov 21, 2017 //echo "<hr>temp_dir deleted [$temp_dir]</hr>";
                    return array( 'errors' => $validator->display_errors(),
                                  'structural_errors' => $validator->structural_errors(),
                                  'warnings' => $validator->display_warnings(),
                                  'stats' => $validator->stats() );
                }else
                {
                    $error = new \eol_schema\ContentArchiveError();
                    $error->message = "Unable to locate a meta.xml file. Make sure the archive does not contain a directory - just the archive files.";
                    recursive_rmdir($temp_dir); //Nov 21, 2017 //echo "<hr>temp_dir deleted [$temp_dir]</hr>";
                    return array( 'structural_errors' => array( $error ));
                }
                recursive_rmdir($temp_dir);
            }else
            {
                $path_parts = pathinfo($temp_dir);
                $extension = @$path_parts['extension'];
                $archive_tmp_dir = @$path_parts['dirname'] ."/". @$path_parts['filename'];
                recursive_rmdir($archive_tmp_dir);
                mkdir($archive_tmp_dir);
                if($extension == 'xlsx' || $extension == 'xls')
                {
                    require_library('ExcelToText');
                    $archive_converter = new ExcelToText($temp_dir, $archive_tmp_dir);
                    if($archive_converter->errors()) return array( 'errors' => $archive_converter->errors());
                    if($archive_converter->is_new_schema_spreadsheet())
                    {
                        $archive_converter->convert_to_new_schema_archive();
                        if($archive_converter->errors()) return array( 'errors' => $archive_converter->errors());
                        $archive = new ContentArchiveReader(null, $archive_tmp_dir);
                        $validator = new ContentArchiveValidator($archive);
                        $validator->get_validation_errors();

                        //--------------------------------------added Apr 7, 2018
                        /* $temp_dir value at this point is:
                        [/Library/WebServer/Documents/eol_php_code/applications/content_server/tmp/34b9691942ddfd05b2feda198493316f.xlsx]
                        Should delete (.xls or .xlsx and .tar.gz) and the temp folder
                        */
                        Functions::delete_if_exists($temp_dir);                                         //this deletes filename.xlsx or .xls
                        Functions::delete_if_exists(str_ireplace(".xlsx", ".tar.gz", $temp_dir));       //this deletes the filename.tar.gz OR the one below
                        Functions::delete_if_exists(str_ireplace(".xls", ".tar.gz", $temp_dir));        //this deletes the filename.tar.gz
                        recursive_rmdir(str_ireplace(array(".xlsx", ".xls"), "", $archive_tmp_dir));    //this removes the temp folder /34b9691942ddfd05b2feda198493316f/
                        //--------------------------------------added Apr 7, 2018

                        return array( 'errors' => $validator->display_errors(),
                                      'structural_errors' => $validator->structural_errors(),
                                      'warnings' => $validator->display_warnings(),
                                      'stats' => $validator->stats() );
                    }
                    $error = new \eol_schema\ContentArchiveError();
                    $error->message = "Unable to determine the template of Excel file";
                    return array( 'structural_errors' => array( $error ));
                }else
                {
                    $error = new \eol_schema\ContentArchiveError();
                    $error->message = "The uploaded file was not in a format we recognize";
                    return array( 'structural_errors' => array( $error ));
                }
                wildcard_rm($archive_tmp_dir);
            }
        }else
        {
            $error = new \eol_schema\ContentArchiveError();
            $error->message = "There was a problem with the uploaded file";
            return array( 'structural_errors' => array( $error ));
        }
    }

    /*
        Some basic rules
    */
    public static function exists($v)
    {
        if($v === '' || $v === NULL) return false;
        return true;
    }

    public static function is_utf8($v)
    {
        $v = trim($v);
        if(!$v) return true;
        $return = Functions::is_utf8($v);
        return $return;
    }

    public static function any_exceptions_of_type_error($exceptions)
    {
        foreach($exceptions as $exception)
        {
            if(get_class($exception) == 'eol_schema\ContentArchiveError') return true;
        }
    }
}

?>
