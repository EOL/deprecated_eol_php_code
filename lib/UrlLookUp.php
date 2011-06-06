<?php
namespace php_active_record;

class UrlLookUp
{
    function get_names($xml,$nameBankURL,$arr_4saving)
    {       
        $arr=array();
        $html="<table cellpadding='3' cellspacing='0' border='0' style='font-size : small; font-family : Arial Unicode MS;'>";    
        $i=0;
        foreach($xml->allNames->entity as $name)
        {        
            if($name->score >= 0.99 and isset($name->namebankID))
            {
                $i++;
                $url = $nameBankURL . $name->namebankID;
                $html .= "<tr><td><a href='$url' target='namebank'>$name->nameString</a></td></tr>";                                      
                $arr_4saving[]=array("name"=>$name->nameString, "url"=>$url);
            }
        }
        $html.="</table>";    
        return array($html,$i,$arr_4saving);
    }
    
    function array_trim($a) 
    { 	
    	$b=array(); $j = 0; 
    	for ($i = 0; $i < count($a); $i++) 
    	{ 
    		if (trim($a[$i]) != "") { $b[$j++] = $a[$i]; } 
    	} 
    	return $b; 
    }
    
    function is_well_formed($xmlfile)
    {
    	$curl = curl_init();
    	curl_setopt($curl, CURLOPT_URL, $xmlfile);
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    	$output = curl_exec($curl);
    	curl_close($curl);
    	if (simplexml_load_string($output)) {return true;} 
    	else 								{return false;}
    }
    
    function save_to_txt($arr)
    {    
    	$str="";        
    	foreach($arr as $r)
    	{
            $str .= $r["name"] . "\t" . $r["url"] . "\n";        
    	}
        $fileidx = time();  
        $filename ="temp/" . $fileidx . ".txt"; 
    	if($fp = fopen($filename,"a")){fwrite($fp,$str);fclose($fp);}		
        print "<hr><i>Use a spreadsheet to open the tab-delimited TXT file created for ";
    	print "<a target='tab_delimited' href=$filename> - Download - </a></i>";        
    }    
}        
?>