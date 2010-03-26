<?php

class test_iconv extends SimpletestUnitBase
{
    function testProperValidation()
    {
        $iconv_encoding_list = shell_exec('iconv --list');
        $this->assertPattern("/UTF-8/im", $iconv_encoding_list, "ICONV must be installed and working with UTF-8 files");
        $this->assertPattern("/UTF-16/im", $iconv_encoding_list, "ICONV must be installed and working with UTF-16 files");
    }
}

?>