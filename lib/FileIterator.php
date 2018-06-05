<?php
namespace php_active_record;

class FileIterator implements \Iterator
{
    protected $FILE;
    protected $file_path;
    protected $current_line;
    protected $line_number = -1; // bit of a hack. So the first iteration sets it to 0
    protected $remove_file_on_destruct;
    protected $trim_newlines;
    
    public function __construct($file_path, $remove_file_on_destruct = false, $trim_newlines = true, $options = array())
    {
        $this->file_path = $file_path;
        $this->remove_file_on_destruct = $remove_file_on_destruct;
        $this->trim_newlines = $trim_newlines;
        $this->options = $options;
        // file must exist and be readable
        if(is_readable($file_path))
        {
            $this->FILE = fopen($file_path, "r");
        }else
        {
            trigger_error("FileIterator: Invalid file path or not readable: $file_path", E_USER_NOTICE);
            debug("FileIterator: Invalid file path or not readable: $file_path", E_USER_NOTICE);
        }
    }
    
    public function __destruct()
    {
        if(isset($this->FILE))
        {
            fclose($this->FILE);
        }
        
        if($this->remove_file_on_destruct && $this->file_path)
        {
            unlink($this->file_path);
        }
    }
    
    public function rewind()
    {
        fseek($this->FILE, 0);
    }
    
    public function current()
    {
        // return the line that has already been read from the file
        if(isset($this->current_line)) return $this->current_line;
        
        // should only get here the very first time
        return $this->get_next_line();
    }
    
    public function key()
    {
        return $this->line_number;
    }
    
    public function next()
    {
        return $this->get_next_line();
    }
    
    public function valid()
    {
        return $this->current() !== false;
    }
    
    protected function get_next_line()
    {
        if(isset($this->FILE) && !feof($this->FILE))
        {
            if($row_terminator = @$this->options['row_terminator']) { //added by Eli
                $this->current_line = stream_get_line($this->FILE, 65535, $row_terminator);
            }
            else { 
                //original sole process for get_next_line() ----------------------------------
                if ($this->trim_newlines) {
                    // // possibly faster but doesn't recognize both \n and \r
                    // $this->current_line = stream_get_line($this->FILE, 65535, "\n");
                    $this->current_line = rtrim(fgets($this->FILE), "\r\n");
                } else {
                    $this->current_line = fgets($this->FILE);
                }
                //end of original block ----------------------------------
            }
            
            $this->line_number += 1;
            return $this->current_line;
        }
        $this->current_line = null;
        return false;
    }
    
}

?>