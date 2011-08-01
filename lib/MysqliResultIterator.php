<?php
namespace php_active_record;

class MysqliResultIterator implements \Iterator
{
    private $result;
    private $current_row;
    private $row_number = 0;
    
    public function __construct(&$result)
    {
        // file must exist and be readable
        if($result && get_class($result) == 'mysqli_result')
        {
            $this->result =& $result;
        }else
        {
            trigger_error("MysqliResultIterator: Invalid file path or not readable: $file_path", E_USER_NOTICE);
        }
    }
    
    public function rewind()
    {
        $this->result->data_seek(0);
    }
    
    public function current()
    {
        // return the line that has already been read from the file
        if(isset($this->current_row)) return $this->current_row;
        
        // should only get here the very first time
        return $this->get_next_row();
    }
    
    public function key()
    {
        return $this->row_number;
    }
    
    public function next()
    {
        return $this->get_next_row();
    }
    
    public function valid()
    {
        return $this->current() !== false;
    }
    
    private function get_next_row()
    {
        if($row = $this->result->fetch_assoc())
        {
            $this->current_row = $row;
            $this->row_number += 1;
            return $this->current_row;
        }
        $this->current_row = null;
        return false;
    }
    
}

?>