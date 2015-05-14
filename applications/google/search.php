<?php

//define("DEBUG", true);
include_once("../../config/environment.php");

if(@$_POST["nameList"])
{
    $names = explode("\n",$_POST["nameList"]);
    
    if(!($OUT = fopen(DOC_ROOT."temp/google_output.txt","w+")))
    {
      debug("Can't open file : temp/google_output.txt");
      return;
    }
    
    while(list($key,$val)=each($names))
    {
        $urls = array();
        
        $name = trim($val);
        $name = urlencode($name);
        if(!$name) continue;
        
        echo "$val...<br>";
        flush();
        
        for($start=0 ; $start<20 ; $start+=4)
        {
            $file = google_search($name, $start);
            $json = json_decode($file, 1);
            flush();
            
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
    
    echo "<br>search results powered by Google<br><br><a href='".WEB_ROOT."temp/google_output.txt'>Download Results</a><br>";
}else
{
    echo "Enter a list of names:<br>
        <form action='search.php' method='post'>
            <textarea name='nameList' rows='30' cols='40'></textarea>
            <input type=submit value=Submit>
        </form><br><br>search results powered by Google";
}




function google_search($name, $start)
{
    $url = "http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=$name&start=$start&userip=".$_SERVER['SERVER_ADDR'];
    // print_r($_SERVER);
    // echo "$url<br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['SERVER_NAME']);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1");
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}








?>