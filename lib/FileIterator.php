<?php
namespace php_active_record;

class FileIterator implements \Iterator
{
    protected $FILE;
    protected $file_path;
    protected $current_line;
    protected $line_number = -1; // bit of a hack. So the first iteration sets it to 0
    protected $remove_file_on_destruct;
    
    public function __construct($file_path, $remove_file_on_destruct = false)
    {
        $this->file_path = $file_path;
        $this->remove_file_on_destruct = $remove_file_on_destruct;
        // file must exist and be readable
        if(is_readable($file_path))
        {
            $this->FILE = fopen($file_path, "r");
        }else
        {
            trigger_error("FileIterator: Invalid file path or not readable: $file_path", E_USER_NOTICE);
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
            $line = fgets($this->FILE);
            $this->current_line = rtrim($line, "\r\n");
            unset($line);
            // // possibly faster but doesn't recognize both \n and \r
            // $this->current_line = stream_get_line($this->FILE, 65535, "\n");
            
            $this->line_number += 1;
            return $this->current_line;
        }
        $this->current_line = null;
        return false;
    }
    
}

?>