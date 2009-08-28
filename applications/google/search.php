<?php

//define("DEBUG", true);
include_once("../../config/start.php");

if(@$_POST["nameList"])
{
    $names = explode("\n",$_POST["nameList"]);
    
    $OUT = fopen("output.txt","w+");
    
    while(list($key,$val)=each($names))
    {
        $urls = array();
        
        $name = trim($val);
        $name = urlencode($name);
        
        echo "$val...<br>";
        flush();
        
        for($start=0 ; $start<20 ; $start+=4)
        {
            $file = Functions::get_remote_file("http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=".$name."&start=$start");
            $json = json_decode($file, 1);
            
            flush();
            
//            echo "<pre>";
//            print_r($json);
//            echo "</pre>";
            
            while(list($key2,$val2)=each($json['responseData']['results']))
            {
                $urls[] = $val2['unescapedUrl'];
            }
        }
        
        while(list($key2,$val2)=each($urls))
        {
            fwrite($OUT,"$name\t$val2\n");
        }
    }
    
    fclose($OUT);
    
    echo "<a href='output.txt'>Download Results</a><br>";
}else
{
    echo "Enter a list of names:<br>
        <form action='search.php' method='post'>
            <textarea name='nameList' rows='30' cols='40'></textarea>
            <input type=submit value=Submit>
        </form>";
}









?>