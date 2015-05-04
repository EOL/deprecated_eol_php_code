<?php
namespace php_active_record;

class DarwinCoreArchiveHarvester
{
    private $archive_directory;
    private $core;
    
    function __construct($uri)
    {
        $this->load_archive($uri);
        $this->load_core_metadata();
    }
    
    function __destruct()
    {
        $this->cleanup();
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
        if(!($TMP = fopen($temp_file_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$temp_file_path);
          return;
        }
        fwrite($TMP, $file_contents);
        fclose($TMP);
        
        // extract contents of temp gzip file to temp dir
        $output = shell_exec("tar -xzf $temp_file_path -C $this->archive_directory");
    }
    
    function cleanup()
    {
        // remove tmp dir
        if($this->archive_directory) shell_exec("rm -fr $this->archive_directory");
    }
    
    function load_core_metadata()
    {
        $metadata_xml = Functions::get_hashed_response($this->archive_directory."/meta.xml");
        
        $this->core = $this->load_core_or_extension($metadata_xml->core);
        if(strcasecmp($this->core->row_type, "http://rs.tdwg.org/dwc/terms/Taxon") != 0) throw new \Exception("Core row type must be http://rs.tdwg.org/dwc/terms/Taxon (".$this->core->row_type.")");
        
        $this->extensions = array();
        foreach($metadata_xml->extension as $extension_xml)
        {
            $extension_object = $this->load_core_or_extension($extension_xml);
            $this->extensions[strtolower($extension_object->row_type)] = $extension_object;
        }
    }
    
    function load_core_or_extension($metadata_xml)
    {
        $extension = new \stdClass;
        
        // attributes
        $extension->row_type = Functions::import_decode(@$metadata_xml['rowType']);
        $extension->fields_terminated_by = Functions::import_decode(@$metadata_xml['fieldsTerminatedBy']);
        $extension->lines_terminated_by = Functions::import_decode(@$metadata_xml['linesTerminatedBy']);
        $extension->fields_enclosed_by = Functions::import_decode(@$metadata_xml['fieldsEnclosedBy']);
        $extension->compression = Functions::import_decode(@$metadata_xml['compression']);
        $extension->encoding = Functions::import_decode(@$metadata_xml['encoding']);
        $extension->ignore_header_lines = Functions::import_decode(@$metadata_xml['ignoreHeaderLines']);
        $extension->date_format = Functions::import_decode(@$metadata_xml['date_format']);
        
        // defaults
        if(!$extension->fields_terminated_by) $extension->core_field_terminator = "\t";
        if(!$extension->lines_terminated_by) $extension->core_line_terminator = "\n";
        if(!$extension->encoding) $extension->core_encoding = "ISO-8859-1";
        if(!$extension->ignore_header_lines) $extension->ignore_header_lines = 0;
        
        // SimpleXML turns \n into \\n which will confuse other things
        $extension->fields_terminated_by = self::convert_escaped_chars($extension->fields_terminated_by);
        $extension->lines_terminated_by = self::convert_escaped_chars($extension->lines_terminated_by);
        $extension->fields_enclosed_by = self::convert_escaped_chars($extension->fields_enclosed_by);
        
        // file location
        $extension->file_uri = (string) $metadata_xml->files->location;
        // the URI is relative so add the path to the temp directory
        if(strpos($extension->file_uri, "/") === false)
        {
            $extension->file_uri = $this->archive_directory ."/". $extension->file_uri;
        }
        
        // fields
        $extension->fields = array();
        $extension->constants = array();
        
        // grabbing the ID if it exists
        if($id = $metadata_xml->id)
        {
            $index = (int) @$id['index'];
            $term = (string) @$id['term'];
            $type = (string) @$id['type'];
            if(!$term) $term = "http://rs.tdwg.org/dwc/terms/taxonID";
            $extension->fields[$index] = array('term'      => $term,
                                                'type'      => $type,
                                                'default'   => '');
            $extension->id = $extension->fields[$index];
        }
        
        // grabbing the coreID if it exists
        if($id = $metadata_xml->coreid)
        {
            $index = (int) @$id['index'];
            $term = (string) @$id['term'];
            $type = (string) @$id['type'];
            if(!$term) $term = "http://rs.tdwg.org/dwc/terms/taxonID";
            $extension->fields[$index] = array('term'      => $term,
                                                'type'      => $type,
                                                'default'   => '');
            $extension->core_id = $extension->fields[$index];
        }
        
        // now all other fields
        foreach($metadata_xml->field as $field)
        {
            $index = (int) @$field['index'];
            $field_meta = array('term'      => (string) @$field['term'],
                                'type'      => (string) @$field['type'],
                                'default'   => (string) @$field['default']);
            if(isset($field['index'])) $extension->fields[$index] = $field_meta;
            else $extension->constants[] = $field_meta;
        }
        $extension->column_count = count($extension->fields);
        
        return $extension;
    }
    
    function get_core_taxa()
    {
        $file_contents = Functions::get_remote_file($this->core->file_uri);
        $lines = explode($this->core->lines_terminated_by, $file_contents);
        
        $all_taxa = array();
        $line_num = 0;
        foreach($lines as $line)
        {
            if($line_num%10000==0) echo "$line_num: ".memory_get_usage()."\n";
            $line_num++;
            if($this->core->ignore_header_lines && $line_num == 1) continue;
            if(!trim($line)) continue;
            $line = preg_replace("/^".preg_quote($this->core->fields_enclosed_by, "/")."/", "", $line);
            $line = preg_replace("/".preg_quote($this->core->fields_enclosed_by, "/")."$/", "", $line);
            $fields = explode($this->core->fields_enclosed_by.$this->core->fields_terminated_by.$this->core->fields_enclosed_by, $line);
            if($fields) $all_taxa[] = $this->generate_taxon($fields, $this->core);
            
            if($line_num%10000==1)
            {
                $count = count($all_taxa);
                print_r($all_taxa[$count-1]);
                //if($line_num>5) return $all_taxa;
            }
        }
        return $all_taxa;
    }
    
    function get_vernaculars()
    {
        $vernaculars = array();
        if(isset($this->extensions['http://rs.gbif.org/ipt/terms/1.0/vernacularname']))
        {
            $extension = $this->extensions['http://rs.gbif.org/ipt/terms/1.0/vernacularname'];
            $file_contents = Functions::get_remote_file($extension->file_uri);
            $lines = explode($extension->lines_terminated_by, $file_contents);
            
            $line_num = 0;
            foreach($lines as $line)
            {
                $line_num++;
                if($extension->ignore_header_lines && $line_num == 1) continue;
                if(!trim($line)) continue;
                $line = preg_replace("/^".preg_quote($extension->fields_enclosed_by, "/")."/", "", $line);
                $line = preg_replace("/".preg_quote($extension->fields_enclosed_by, "/")."$/", "", $line);
                $fields = explode($extension->fields_enclosed_by.$extension->fields_terminated_by.$extension->fields_enclosed_by, $line);
                if($fields) $vernaculars[] = $this->generate_taxon($fields, $extension);
            }
        }
        return $vernaculars;
    }
    
    private function generate_taxon($fields, $extension)
    {
        $taxon_attributes = array();
        foreach($fields as $index => $value)
        {
            if(isset($extension->fields[$index]))
            {
                $field_metadata = $extension->fields[$index];
                $value = self::convert_escaped_chars($value);
                $taxon_attributes[$field_metadata['term']] = $value;
            }else echo "There is no declared field for this index ($index)\n";
        }
        foreach($extension->constants as $field_metadata)
        {
            if($value = self::convert_escaped_chars($field_metadata[$default]))
            {
                $taxon_attributes[$field_metadata['term']] = $value;
            }
        }
        $taxon = new DarwinCoreTaxon($taxon_attributes);
        
        // when the acceptedNameID is set to itself - then just unset the acceptedNameID
        if(isset($taxon->taxonID) && isset($taxon->acceptedNameUsageID) && trim($taxon->acceptedNameUsageID))
        {
            unset($taxon->acceptedNameUsageID);
        }
        unset($taxon_attributes);
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