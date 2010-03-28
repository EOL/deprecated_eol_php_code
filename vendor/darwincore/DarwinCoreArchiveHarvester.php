<?php

class DarwinCoreArchiveHarvester
{
    private $archive_directory;
    private $core;
    
    function __construct($uri)
    {
        $this->core = new stdClass;
        $this->load_archive($uri);
        $this->load_core_metadata();
    }
    
    function load_archive($uri)
    {
        // currently only recognizing tarred and gzipped files
        if(!preg_match("/^(.*)\.(tgz|tar\.gz|tar\.gzip)$/", $uri, $arr)) throw new Exception("DarwinCore Archive must be tar/gzip");
        $file_contents = Functions::get_remote_file($uri);
        if(!$file_contents) throw new Exception("Cannot access DarwinCore Archive at $uri");
        $directory_name = $arr[1];
        
        // // make temp dir
        $this->archive_directory = DOC_ROOT."temp/dwca";
        shell_exec("mkdir $this->archive_directory");
        
        // copy contents to temp gzip file
        $temp_file_path = $this->archive_directory ."/test.tar.gz";
        $TMP = fopen($temp_file_path, "w+");
        fwrite($TMP, $file_contents);
        fclose($TMP);
        
        // extract contents of temp gzip file to temp dir
        $output = shell_exec("tar -xzf $temp_file_path -C ".DOC_ROOT."temp/dwca");
    }
    
    function cleanup()
    {
        // remove tmp dir
        if($this->archive_directory) shell_exec("rm -fdr $this->archive_directory");
    }
    
    function load_core_metadata()
    {
        $metadata_xml = Functions::get_hashed_response($this->archive_directory."/meta.xml");
        
        // core attributes
        $this->core->row_type = Functions::import_decode(@$metadata_xml->core['rowType']);
        $this->core->fields_terminated_by = Functions::import_decode(@$metadata_xml->core['fieldsTerminatedBy']);
        $this->core->lines_terminated_by = Functions::import_decode(@$metadata_xml->core['linesTerminatedBy']);
        $this->core->fields_enclosed_by = Functions::import_decode(@$metadata_xml->core['fieldsEnclosedBy']);
        $this->core->compression = Functions::import_decode(@$metadata_xml->core['compression']);
        $this->core->encoding = Functions::import_decode(@$metadata_xml->core['encoding']);
        $this->core->ignore_header_lines = Functions::import_decode(@$metadata_xml->core['ignoreHeaderLines']);
        $this->core->date_format = Functions::import_decode(@$metadata_xml->core['date_format']);
        if(strcasecmp($this->core->row_type, "http://rs.tdwg.org/dwc/terms/Taxon") != 0) throw new Exception("Core row type must be http://rs.tdwg.org/dwc/terms/Taxon (".$this->core->row_type.")");
        
        // core defaults
        if(!$this->core->fields_terminated_by) $this->core->core_field_terminator = "\t";
        if(!$this->core->lines_terminated_by) $this->core->core_line_terminator = "\n";
        if(!$this->core->encoding) $this->core->core_encoding = "ISO-8859-1";
        if(!$this->core->ignore_header_lines) $this->core->ignore_header_lines = 0;
        
        // SimpleXML turns \n into \\n which will confuse other things
        $this->core->fields_terminated_by = self::convert_escaped_chars($this->core->fields_terminated_by);
        $this->core->lines_terminated_by = self::convert_escaped_chars($this->core->lines_terminated_by);
        $this->core->fields_enclosed_by = self::convert_escaped_chars($this->core->fields_enclosed_by);
        
        // core file location
        $this->core->file_uri = (string) $metadata_xml->core->files->location;
        // the URI is relative so add the path to the temp directory
        if(strpos($this->core->file_uri, "/") === false)
        {
            $this->core->file_uri = $this->archive_directory ."/". $this->core->file_uri;
        }
        
        // fields
        $this->core->fields = array();
        $this->core->constants = array();
        
        // grabbing the ID if it exists
        if($id = $metadata_xml->core->id)
        {
            $index = (int) @$id['index'];
            $term = (string) @$id['term'];
            $type = (string) @$id['type'];
            if(!$term) $term = "http://rs.tdwg.org/dwc/terms/taxonID";
            $this->core->fields[$index] = array('term'      => $term,
                                                'type'      => $type,
                                                'default'   => '');
        }
        
        // now all other fields
        foreach($metadata_xml->core->field as $field)
        {
            $index = (int) @$field['index'];
            $field_meta = array('term'      => (string) @$field['term'],
                                'type'      => (string) @$field['type'],
                                'default'   => (string) @$field['default']);
            if(isset($field['index'])) $this->core->fields[$index] = $field_meta;
            else $this->core->constants[] = $field_meta;
        }
        $this->core->column_count = count($this->core->fields);
    }
    
    function get_core_taxa()
    {
        $file_contents = Functions::get_remote_file($this->core->file_uri);
        $lines = explode($this->core->lines_terminated_by, $file_contents);
        
        $all_taxa = array();
        $line_num = 0;
        foreach($lines as $line)
        {
            $line_num++;
            if($this->core->ignore_header_lines && $line_num == 1) continue;
            if(!trim($line)) continue;
            $line = preg_replace("/^".preg_quote($this->core->fields_enclosed_by, "/")."/", "", $line);
            $line = preg_replace("/".preg_quote($this->core->fields_enclosed_by, "/")."$/", "", $line);
            $fields = explode($this->core->fields_enclosed_by.$this->core->fields_terminated_by.$this->core->fields_enclosed_by, $line);
            if($fields) $all_taxa[] = $this->generate_taxon($fields);
        }
        return $all_taxa;
    }
    
    private function generate_taxon($fields)
    {
        $taxon_attributes = array();
        foreach($fields as $index => $value)
        {
            if(isset($this->core->fields[$index]))
            {
                $field_metadata = $this->core->fields[$index];
                $value = self::convert_escaped_chars($value);
                $taxon_attributes[$field_metadata['term']] = $value;
            }else echo "There is no declared field for this index ($index)\n";
        }
        $taxon = new DarwinCoreTaxon($taxon_attributes);
        return $taxon;
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