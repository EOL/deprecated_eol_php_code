<?php
namespace php_active_record;

require_library('FileIterator');

class test_file_iterator extends SimpletestUnitBase
{
    function testFileIterator()
    {
        $temp_filepath = temp_filepath();
        $this->assertFalse(file_exists($temp_filepath), 'File shouldnt exist yet');
        
        $file_lines = array();
        $file_lines[] = "first line";
        $file_lines[] = "second\tline";
        $file_lines[] = "   third  line    \t";
        $file_lines[] = "   ";
        $file_lines[] = "";
        
        if(!($FILE = fopen($temp_filepath, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $temp_filepath);
          return;
        }
        fwrite($FILE, implode("\n", $file_lines));
        fclose($FILE);
        
        foreach(new FileIterator($temp_filepath) as $line_number => $line)
        {
            $this->assertTrue($line == $file_lines[$line_number], 'All lines should be the same');
        }
        
        $this->assertTrue(file_exists($temp_filepath), 'File should still exist');
        unlink($temp_filepath);
    }
    
    function testFileIteratorDestroy()
    {
        $file_lines = array();
        $file_lines[] = "first line";
        $file_lines[] = "second line";
        $file_lines[] = "third line";
        
        $temp_filepath = temp_filepath();
        if(!($FILE = fopen($temp_filepath, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $temp_filepath);
          return;
        }
        fwrite($FILE, implode("\n", $file_lines));
        fclose($FILE);
        
        foreach(new FileIterator($temp_filepath, true) as $line_number => $line)
        {
            $this->assertTrue($line == $file_lines[$line_number], 'All lines should be the same');
        }
        
        $this->assertFalse(file_exists($temp_filepath), 'File should have been removed');
    }
}

?>