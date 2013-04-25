<?php
namespace php_active_record;

class MysqliResultFileIterator extends FileIterator
{
    protected $outfile;
    
    public function __construct($query, $mysqli_connection)
    {
        $this->outfile = $mysqli_connection->select_into_outfile($query);
        parent::__construct($this->outfile, true);
    }
    
    protected function get_next_line()
    {
        if(isset($this->FILE) && !feof($this->FILE))
        {
            $line = fgets($this->FILE);
            $line = rtrim($line, "\r\n");
            
            // possibly faster but doesn't recognize both \n and \r
             //$line = stream_get_line($this->FILE, 65535, "\n");
             //$line = rtrim($line, "\r\n");
            
            // TODO: remove if ($line) because mysql results might be 0 or empty string
            if(($line || $line == 0) && !feof($this->FILE))
            {
                $this->current_line = explode("\t", $line);
                $this->line_number += 1;
                return $this->current_line;
            }
        }
        $this->current_line = null;
        return false;
    }
    
}

?>