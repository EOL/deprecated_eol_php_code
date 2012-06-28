<?php
namespace php_active_record;

class ContentArchiveValidator
{
    private $content_archive_reader;
    private $errors;
    private $warnings;
    private $stats;
    
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
    public function stats()
    {
        return $this->stats;
    }
    
    public function get_validation_errors()
    {
        if($this->validation_has_run) return;
        $this->validation_has_run = true;
        
        $this->errors = array();
        $this->warnings = array();
        $this->stats = array();
        if(!$this->content_archive_reader->tables)
        {
            $error = new \eol_schema\ContentArchiveError();
            $error->message = "Cannot read meta.xml. Make sure the archive does not contain a directory - just the archive files.";
            $this->errors[] = $error;
        }
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
        $i++;
        
        $new_errors = array();
        if(!isset($this->stats[$parameters['row_type']])) $this->stats[$parameters['row_type']] = array();
        if(!isset($this->stats[$parameters['row_type']]['Total'])) $this->stats[$parameters['row_type']]['Total'] = 0;
        $this->stats[$parameters['row_type']]['Total']++;
        
        if($parameters['row_type'] == 'http://eol.org/schema/media/document')
        {
            if(@$v = $row['http://purl.org/dc/terms/type'])
            {
                if(!isset($this->stats[$parameters['row_type']]['Total by type'])) $this->stats[$parameters['row_type']]['Total by type'] = array();
                if(!isset($this->stats[$parameters['row_type']]['Total by type'][$v])) $this->stats[$parameters['row_type']]['Total by type'][$v] = 0;
                $this->stats[$parameters['row_type']]['Total by type'][$v]++;
            }
            if(@$v = $row['http://rs.tdwg.org/audubon_core/subtype'])
            {
                if(!isset($this->stats[$parameters['row_type']]['Total by subtype'])) $this->stats[$parameters['row_type']]['Total by subtype'] = array();
                if(!isset($this->stats[$parameters['row_type']]['Total by subtype'][$v])) $this->stats[$parameters['row_type']]['Total by subtype'][$v] = 0;
                $this->stats[$parameters['row_type']]['Total by subtype'][$v]++;
            }
            if(@$v = $row['http://ns.adobe.com/xap/1.0/rights/UsageTerms'])
            {
                if(!isset($this->stats[$parameters['row_type']]['Total by license'])) $this->stats[$parameters['row_type']]['Total by license'] = array();
                if(!isset($this->stats[$parameters['row_type']]['Total by license'][$v])) $this->stats[$parameters['row_type']]['Total by license'][$v] = 0;
                $this->stats[$parameters['row_type']]['Total by license'][$v]++;
            }
            if(@$v = $row['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'])
            {
                if(!isset($this->stats[$parameters['row_type']]['Total by subject'])) $this->stats[$parameters['row_type']]['Total by subject'] = array();
                if(!isset($this->stats[$parameters['row_type']]['Total by subject'][$v])) $this->stats[$parameters['row_type']]['Total by subject'][$v] = 0;
                $this->stats[$parameters['row_type']]['Total by subject'][$v]++;
            }
            if(@$v = $row['http://purl.org/dc/terms/language'])
            {
                if(!isset($this->stats[$parameters['row_type']]['Total by language'])) $this->stats[$parameters['row_type']]['Total by language'] = array();
                if(!isset($this->stats[$parameters['row_type']]['Total by language'][$v])) $this->stats[$parameters['row_type']]['Total by language'][$v] = 0;
                $this->stats[$parameters['row_type']]['Total by language'][$v]++;
            }
            $new_errors = \eol_schema\MediaResource::validate_by_hash($row);
        }elseif($parameters['row_type'] == 'http://rs.tdwg.org/dwc/terms/taxon')
        {
            $new_errors = \eol_schema\Taxon::validate_by_hash($row);
        }elseif($parameters['row_type'] == 'http://rs.gbif.org/terms/1.0/vernacularname')
        {
            if(@$v = $row['http://purl.org/dc/terms/language'])
            {
                if(!isset($this->stats[$parameters['row_type']]['Total by language'])) $this->stats[$parameters['row_type']]['Total by language'] = array();
                if(!isset($this->stats[$parameters['row_type']]['Total by language'][$v])) $this->stats[$parameters['row_type']]['Total by language'][$v] = 0;
                $this->stats[$parameters['row_type']]['Total by language'][$v]++;
            }
            $new_errors = \eol_schema\VernacularName::validate_by_hash($row);
        }elseif($parameters['row_type'] == 'http://eol.org/schema/reference/reference')
        {
            $new_errors = \eol_schema\Reference::validate_by_hash($row);
        }elseif($parameters['row_type'] == 'http://eol.org/schema/agent/agent')
        {
            $new_errors = \eol_schema\Agent::validate_by_hash($row);
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
    
    public static function is_utf8($v)
    {
        $v = trim($v);
        if(!$v) return true;
        $return = Functions::is_utf8($v);
        return $return;
    }
}

?>