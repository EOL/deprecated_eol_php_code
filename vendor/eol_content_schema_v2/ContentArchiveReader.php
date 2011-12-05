<?php
namespace php_active_record;

class ContentArchiveReader
{
    private $archive_is_local;
    private $archive_directory;
    private $core;
    
    function __construct($uri = null, $directory = null)
    {
        if($uri)
        {
            $this->archive_is_local = false;
            $this->load_archive($uri);
        }else
        {
            $this->archive_is_local = true;
            $this->load_from_directory($directory);
        }
        $this->load_metadata();
    }
    
    function __destruct()
    {
        if(!$this->archive_is_local) $this->cleanup();
    }
    
    function load_from_directory($directory)
    {
        $this->archive_directory = $directory;
    }
    
    function load_archive($uri)
    {
        // currently only recognizing tarred and gzipped files
        if(!preg_match("/^(.*)\.(tgz|tar\.gz|tar\.gzip)$/", $uri, $arr)) throw new \Exception("DarwinCore Archive must be tar/gzip");
        $file_contents = Functions::get_remote_file($uri);
        if(!$file_contents) throw new \Exception("Cannot access DarwinCore Archive at $uri");
        $directory_name = $arr[1];
        
        // // make temp dir
        $this->archive_directory = create_temp_dir();
        
        // copy contents to temp gzip file
        $temp_file_path = $this->archive_directory ."/dwca.tar.gz";
        $TMP = fopen($temp_file_path, "w+");
        fwrite($TMP, $file_contents);
        fclose($TMP);
        
        // extract contents of temp gzip file to temp dir
        $output = shell_exec("tar -xzf $temp_file_path -C $this->archive_directory");
    }
    
    function cleanup()
    {
        // remove tmp dir
        // if($this->archive_directory) shell_exec("rm -fr $this->archive_directory");
    }
    
    function load_metadata()
    {
        $metadata_xml = Functions::get_hashed_response($this->archive_directory."/meta.xml");
        
        $this->tables = array();
        // load the CORE if it exists
        foreach($metadata_xml->core as $core_xml)
        {
            $table_definition = $this->load_table_definition($core_xml);
            $this->tables[strtolower($table_definition->row_type)] = $table_definition;
            $this->core = $table_definition;
        }
        
        // load EXTENSIONS if they exist
        foreach($metadata_xml->extension as $extension_xml)
        {
            $table_definition = $this->load_table_definition($extension_xml);
            $this->tables[strtolower($table_definition->row_type)] = $table_definition;
        }
        
        // load TABLES if they exist
        foreach($metadata_xml->table as $table_xml)
        {
            $table_definition = $this->load_table_definition($table_xml);
            $this->tables[strtolower($table_definition->row_type)] = $table_definition;
        }
    }
    
    function load_table_definition($metadata_xml)
    {
        $table_definition = new \stdClass;
        
        // attributes
        $table_definition->row_type = Functions::import_decode(@$metadata_xml['rowType']);
        $table_definition->fields_terminated_by = Functions::import_decode(@$metadata_xml['fieldsTerminatedBy']);
        $table_definition->lines_terminated_by = Functions::import_decode(@$metadata_xml['linesTerminatedBy']);
        $table_definition->fields_enclosed_by = Functions::import_decode(@$metadata_xml['fieldsEnclosedBy']);
        $table_definition->compression = Functions::import_decode(@$metadata_xml['compression']);
        $table_definition->encoding = Functions::import_decode(@$metadata_xml['encoding']);
        $table_definition->ignore_header_lines = Functions::import_decode(@$metadata_xml['ignoreHeaderLines']);
        $table_definition->date_format = Functions::import_decode(@$metadata_xml['date_format']);
        
        // defaults
        if(!$table_definition->fields_terminated_by) $table_definition->core_field_terminator = "\t";
        if(!$table_definition->lines_terminated_by) $table_definition->core_line_terminator = "\n";
        if(!$table_definition->encoding) $table_definition->core_encoding = "ISO-8859-1";
        if(!$table_definition->ignore_header_lines) $table_definition->ignore_header_lines = 0;
        
        // SimpleXML turns \n into \\n which will confuse other things
        $table_definition->fields_terminated_by = self::convert_escaped_chars($table_definition->fields_terminated_by);
        $table_definition->lines_terminated_by = self::convert_escaped_chars($table_definition->lines_terminated_by);
        $table_definition->fields_enclosed_by = self::convert_escaped_chars($table_definition->fields_enclosed_by);
        
        // file location
        $table_definition->file_uri = (string) $metadata_xml->files->location;
        // the URI is relative so add the path to the temp directory
        if(strpos($table_definition->file_uri, "/") === false)
        {
            $table_definition->file_uri = $this->archive_directory ."/". $table_definition->file_uri;
        }
        
        // fields
        $table_definition->fields = array();
        $table_definition->foreign_keys = array();
        $table_definition->constants = array();
        
        // grabbing the ID if it exists
        if($id = $metadata_xml->id)
        {
            $index = (int) @$id['index'];
            $term = (string) @$id['term'];
            if(!$term && $table_definition->row_type == 'http://rs.tdwg.org/dwc/terms/Taxon') $term = 'http://rs.tdwg.org/dwc/terms/taxonID';
            $table_definition->fields[$index] = array(  'term'      => $term,
                                                        'default'   => '');
            $table_definition->id = $table_definition->fields[$index];
        }
        
        // grabbing the coreID if it exists
        if($id = $metadata_xml->coreid)
        {
            $index = (int) @$id['index'];
            $term = (string) @$id['term'];
            if(!$term && isset($this->core->id['term'])) $term = $this->core->id['term'];
            $table_definition->fields[$index] = array(  'term'      => $term,
                                                        'default'   => '');
            $table_definition->core_id = $table_definition->fields[$index];
        }
        
        // grabbing the foreignKeys if they exists
        foreach($metadata_xml->foreignKey as $foreign_key)
        {
            $index = (int) @$foreign_key['index'];
            $rowType = (string) @$foreign_key['rowType'];
            $table_definition->foreign_keys[$index] = $rowType;
        }
        
        // now all other fields
        foreach($metadata_xml->field as $field)
        {
            $index = (int) @$field['index'];
            $field_meta = array('term'      => (string) @$field['term'],
                                'type'      => (string) @$field['type'],
                                'default'   => (string) @$field['default']);
            if(isset($field['index'])) $table_definition->fields[$index] = $field_meta;
            else $table_definition->constants[] = $field_meta;
        }
        $table_definition->column_count = count($table_definition->fields);
        
        return $table_definition;
    }
    
    function table_exists($row_type)
    {
        return isset($this->tables[strtolower($row_type)]);
    }
    
    // if a callback is included, each row will be sent as an array to the callback method.
    // otherwise the entire table will be returned as an array of all rows
    function process_table($row_type, $callback = NULL, $parameters = NULL)
    {
        if(isset($this->tables[strtolower($row_type)]))
        {
            $table_definition = $this->tables[strtolower($row_type)];
            $this->table_iterator_index = 0;
            $all_rows = array();
            
            // rows are on newlines, so we can stream the file with an iterator
            if($table_definition->lines_terminated_by == "\n")
            {
                foreach(new FileIterator($table_definition->file_uri) as $line_number => $line)
                {
                    $fields = $this->parse_table_row($table_definition, $line, $parameters);
                    if($fields == "this is the break message") break;
                    if($fields && $callback) call_user_func($callback, $fields, $parameters);
                    elseif($fields) $all_rows[] = $fields;
                }
            }
            
            // otherwise we need to load the entire file into memory and split it
            else
            {
                $file_contents = Functions::get_remote_file($table_definition->file_uri);
                $lines = explode($this->core->lines_terminated_by, $file_contents);
                foreach($lines as $line)
                {
                    $fields = $this->parse_table_row($table_definition, $line);
                    if($fields && $callback) call_user_func($callback, $fields, $parameters);
                    elseif($fields) $all_rows[] = $fields;
                }
            }
            return $all_rows;
        }
    }
    
    // this method uses the instance variable $this->table_iterator_index to determine which row of the file
    // it is reading. That variable must be reset before reading a new file to properly ignore the header line
    function parse_table_row($table_definition, $line, $parameters = null)
    {
        // if($this->table_iterator_index % 10000 == 0) echo "Parsing table row $this->table_iterator_index: ".memory_get_usage()."\n";
        $this->table_iterator_index++;
        if(isset($parameters['parse_row_limit']) && $this->table_iterator_index > $parameters['parse_row_limit']) return "this is the break message";
        if($table_definition->ignore_header_lines && $this->table_iterator_index <= $table_definition->ignore_header_lines) return array();
        
        if(!trim($line)) return array();
        if($table_definition->fields_enclosed_by)
        {
            $line = preg_replace("/^".preg_quote($table_definition->fields_enclosed_by, "/")."/", "", $line);
            $line = preg_replace("/".preg_quote($table_definition->fields_enclosed_by, "/")."$/", "", $line);
        }
        
        $fields = explode($table_definition->fields_enclosed_by . $table_definition->fields_terminated_by . $table_definition->fields_enclosed_by, $line);
        return self::assign_field_types($table_definition, $fields);
    }
    
    static function assign_field_types($table_definition, $fields)
    {
        $row_field_types = array();
        // unescape all fields at once
        $fields = implode("||||", $fields);
        $fields = self::unescape_string($fields);
        $fields = explode("||||", $fields);
        foreach($fields as $index => $value)
        {
            if(strcasecmp($value, "/n") == 0) $value = null;
            if($f = @$table_definition->fields[$index])
            {
                if($value == '' && isset($f['default']))
                {
                    $row_field_types[$f['term']] = $f['default'];
                }else $row_field_types[$f['term']] = $value;
            }else
            {
                // echo "There is no declared field for this index ($index)\n";
            }
        }
        foreach($table_definition->constants as $field_metadata)
        {
            if($value = self::convert_escaped_chars($field_metadata['default']))
            {
                if(!isset($row_field_types[$field_metadata['term']]) || $row_field_types[$field_metadata['term']] == '')
                {
                    $row_field_types[$field_metadata['term']] = $value;
                }
            }
        }
        return $row_field_types;
    }
    
    private static function unescape_string($str)
    {
        $str = str_ireplace("\\n", "\n", $str);
        $str = str_ireplace("\\r", "\r", $str);
        $str = str_ireplace("\\t", "\t", $str);
        if(strcasecmp($str, "/n") == 0) return null;
        return $str;
    }
    
    private static function convert_escaped_chars($str)
    {
        // strcmp does a case insensitive string comparison
        if(strcasecmp($str, "\\n") == 0) return "\n";
        if(strcasecmp($str, "\\r") == 0) return "\r";
        if(strcasecmp($str, "\\t") == 0) return "\t";
        if(strcasecmp($str, "/n") == 0) return null;
        return $str;
    }
}

?>