<?php
namespace php_active_record;

class ContentArchiveValidator
{
    private $content_archive_reader;
    private $structural_errors;
    private $errors_by_line;
    private $warnings_by_line;
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
        if($this->structural_errors) return false;
        return true;
    }
    
    public function structural_errors()
    {
        return $this->structural_errors;
    }
    public function errors_by_line()
    {
        return $this->errors_by_line;
    }
    public function warnings_by_line()
    {
        return $this->warnings_by_line;
    }
    public function stats()
    {
        return $this->stats;
    }
    
    public function has_error_by_line($row_type, $line_number)
    {
        if(isset($this->errors_by_line[$row_type][$line_number])) return true;
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
        foreach($exceptions_by_line as $file_path => $data)
        {
            foreach($data as $line_number => $exception)
            {
                if(!isset($grouped_exceptions[$file_path][$exception->uri][$exception->value][$exception->message]))
                {
                    $grouped_exceptions[$file_path][$exception->uri][$exception->value][$exception->message] = $exception;
                }elseif($line_number)
                {
                    $grouped_exceptions[$file_path][$exception->uri][$exception->value][$exception->message]->line .= ", $line_number";
                }
            }
        }
        $simplified_errors = array();
        foreach($grouped_exceptions as $file_path => $d1)
        {
            foreach($d1 as $uri => $d2)
            {
                foreach($d2 as $value => $d3)
                {
                    foreach($d3 as $message => $exception)
                    {
                        $simplified_errors[] = $exception;
                    }
                }
            }
        }
        return $simplified_errors;
    }
    
    public function get_validation_errors()
    {
        if($this->validation_has_run) return;
        $this->validation_has_run = true;
        
        $this->structural_errors = array();
        $this->errors_by_line = array();
        $this->warnings_by_line = array();
        $this->stats = array();
        if(!$this->content_archive_reader->tables)
        {
            $error = new \eol_schema\ContentArchiveError();
            $error->message = "Cannot read meta.xml. There may be a structural problem with this archive.";
            $this->structural_errors[] = $error;
        }
        // looping through archive, one entire file at a time
        foreach($this->content_archive_reader->tables as $row_type => $table)
        {
            // TODO: duplicate primary keys
            // TODO: referential integrity
            $this->content_archive_reader->process_row_type($row_type, array($this, 'validate_row'), array('row_type' => $row_type));
            if($row_type == 'http://rs.tdwg.org/dwc/terms/taxon')
            {
                $count_of_all_taxa = @$this->stats[$row_type]['Total'];
                // if(isset($this->errors_by_line[$row_type]))
                // {
                //     $count_of_taxa_errors = count($this->errors_by_line[$row_type]);
                //     if($count_of_taxa_errors >= $count_of_all_taxa)
                //     {
                //         $error = new \eol_schema\ContentArchiveError();
                //         $error->message = "There are no valid taxa in this archive.";
                //         $this->structural_errors[] = $error;
                //     }
                // }
                if(!$count_of_all_taxa)
                {
                    $error = new \eol_schema\ContentArchiveError();
                    $error->message = "There are no valid taxa in this archive.";
                    $this->structural_errors[] = $error;
                }
            }
        }
    }
    
    public function validate_row($row, $parameters)
    {
        static $i = 0;
        $i++;
        
        $new_errors = array();
        if($parameters['row_type'] == 'http://eol.org/schema/media/document')
        {
            $new_errors = \eol_schema\MediaResource::validate_by_hash($row);
            if(!$new_errors)
            {
                if(@$v = $row['http://purl.org/dc/terms/type']) $this->add_stat('type', $parameters['row_type'], $v);
                if(@$v = $row['http://rs.tdwg.org/audubon_core/subtype']) $this->add_stat('subtype', $parameters['row_type'], $v);
                if(@$v = $row['http://ns.adobe.com/xap/1.0/rights/UsageTerms']) $this->add_stat('license', $parameters['row_type'], $v);
                if(@$v = $row['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm']) $this->add_stat('subject', $parameters['row_type'], $v);
                if(@$v = $row['http://purl.org/dc/terms/language']) $this->add_stat('language', $parameters['row_type'], $v);
                if(@$v = $row['http://purl.org/dc/terms/format']) $this->add_stat('format', $parameters['row_type'], $v);
            }
        }elseif($parameters['row_type'] == 'http://rs.tdwg.org/dwc/terms/taxon')
        {
            $new_errors = \eol_schema\Taxon::validate_by_hash($row);
        }elseif($parameters['row_type'] == 'http://rs.gbif.org/terms/1.0/vernacularname')
        {
            $new_errors = \eol_schema\VernacularName::validate_by_hash($row);
            if(!$new_errors)
            {
                if(@$v = $row['http://purl.org/dc/terms/language']) $this->add_stat('language', $parameters['row_type'], $v);
            }
        }elseif($parameters['row_type'] == 'http://eol.org/schema/reference/reference')
        {
            $new_errors = \eol_schema\Reference::validate_by_hash($row);
        }elseif($parameters['row_type'] == 'http://eol.org/schema/agent/agent')
        {
            $new_errors = \eol_schema\Agent::validate_by_hash($row);
        }
        
        if(!$new_errors)
        {
            if(!isset($this->stats[$parameters['row_type']])) $this->stats[$parameters['row_type']] = array();
            if(!isset($this->stats[$parameters['row_type']]['Total'])) $this->stats[$parameters['row_type']]['Total'] = 0;
            $this->stats[$parameters['row_type']]['Total']++;
        }else
        {
            foreach($new_errors as $new_error)
            {
                $new_error->file = $parameters['archive_table_definition']->location;
                $new_error->line = $parameters['archive_line_number'];
                if(get_class($new_error) == 'eol_schema\ContentArchiveError')
                {
                    if(isset($this->errors_by_line[$parameters['row_type']][$new_error->line])) continue;
                    $this->errors_by_line[$parameters['row_type']][$new_error->line] = $new_error;
                }else
                {
                    if(isset($this->warnings_by_line[$parameters['row_type']][$new_error->line])) continue;
                    $this->warnings_by_line[$parameters['row_type']][$new_error->line] = $new_error;
                }
            }
        }
    }
    
    private function add_stat($label, $row_type, $value)
    {
        $index = "Total by $label";
        if(!isset($this->stats[$row_type][$index])) $this->stats[$row_type][$index] = array();
        if(!isset($this->stats[$row_type][$index][$value])) $this->stats[$row_type][$index][$value] = 0;
        $this->stats[$row_type][$index][$value]++;
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