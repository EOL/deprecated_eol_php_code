<?php

class FileIterator implements Iterator
{
    private $FILE;
    private $current_line;
    private $line_number = -1; // bit of a hack. So the first iteration sets it to 0
    
    public function __construct($file_path)
    {
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
    
    private function get_next_line()
    {
        if(!feof($this->FILE))
        {
            $line = fgets($this->FILE, 65535);
            $this->current_line = rtrim($line, "\r\n");
            
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