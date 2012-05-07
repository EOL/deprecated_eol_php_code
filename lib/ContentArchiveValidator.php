<?php
namespace php_active_record;

class ContentArchiveValidator
{
    private $content_archive_reader;
    private $errors;
    private $warnings;
    
    public function __construct($content_archive_reader)
    {
        if(get_class($content_archive_reader) != 'php_active_record\ContentArchiveReader') return null;
        $this->content_archive_reader = $content_archive_reader;
        $this->validation_has_run = false;
    }
    
    public function is_valid()
    {
        $this->get_validation_errors();
        if(!$this->errors) return true;
        return false;
    }
    
    public function errors()
    {
        return $this->errors;
    }
    
    public function warnings()
    {
        return $this->warnings;
    }
    
    public function get_validation_errors()
    {
        if($this->validation_has_run) return;
        $this->validation_has_run = true;
        
        $this->errors = array();
        $this->warnings = array();
        // looping through all files in the archive
        foreach($this->content_archive_reader->tables as $row_type => $table)
        {
            // duplicate primary keys
            // referential integrity
            $this->content_archive_reader->process_table($row_type, array($this, 'validate_row'), array('row_type' => $row_type));
        }
    }
    
    public function validate_row($row, $parameters)
    {
        static $i = 0;
        // if($i % 10000 == 0)
        // {
        //     echo "i: $i\n";
        //     echo "time: ". time_elapsed() ."\n";
        //     echo "memory: ". memory_get_usage() ."\n\n";
        // }
        $i++;
        
        $new_errors = array();
        if($parameters['row_type'] == 'http://eol.org/schema/media/document')
        {
            $new_errors = \eol_schema\MediaResource::validate_by_hash($row);
        }elseif($parameters['row_type'] == 'http://rs.tdwg.org/dwc/terms/taxon')
        {
            $new_errors = \eol_schema\Taxon::validate_by_hash($row);
        }elseif($parameters['row_type'] == 'http://rs.gbif.org/terms/1.0/vernacularname')
        {
            $new_errors = \eol_schema\VernacularName::validate_by_hash($row);
        }elseif($parameters['row_type'] == 'http://eol.org/schema/reference/reference')
        {
            $new_errors = \eol_schema\Reference::validate_by_hash($row);
        }
        
        foreach($new_errors as $new_error)
        {
            $new_error->file = $parameters['archive_table_definition']->location;
            $new_error->line = $parameters['archive_line_number'];
            if(get_class($new_error) == 'eol_schema\ContentArchiveError') $this->errors[] = $new_error;
            else $this->warnings[] = $new_error;
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
}

?>