<?php
namespace php_active_record;

class test_clean_name extends SimpletestUnitBase
{
    function testCleanNameFunction()
    {
        $this->runTestsFromCSV('clean_name.csv');
    }
    
    function runTestsFromCSV($csv_file)
    {
        $file = file(dirname(__FILE__) . "/../rails_csv_files/$csv_file");
        foreach($file as $line => $test_case)
        {
            if($line==0) continue;
            if(!preg_match("/^\s*#/", $test_case) && preg_match("/^\"(.*)\"\t\"(.*)\"$/", $test_case, $arr))
            {
                $test_value_1 = trim($arr[1]);
                $test_value_2 = trim($arr[2]);
                
                $this->assertTrue($test_value_2 == Functions::clean_name($test_value_1), "$test_value_2 should be the clean name of $test_value_1 (we got ".Functions::clean_name($test_value_1).") on line (". ($line+1) .")");
            }
        }
    }
}

?>
