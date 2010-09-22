<?php
class NameStat
{    
    function sort_by_key($arr,$key_string,$key_string2)
    {
        foreach ($arr as $key => $row) 
        {
            $sort_key[$key]  = $row[$key_string];
            $sort_key2[$key]  = $row[$key_string2];
        }
        array_multisort($sort_key, SORT_ASC, $sort_key2, SORT_DESC, $arr);    
        return $arr;
    }
    
    function clean_array($arr)
    {
        $cleaned_array=array();
        foreach($arr as $r)
        {
            $cleaned_array[]=trim($r);
        }    
        return $cleaned_array;
    }
    
    function show_table($arr)
    {
        print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : x-small; font-family : Arial Unicode MS;'>	
            <tr align='center'>
                <td rowspan='2'>Searched</td>
                <td rowspan='2'>Name</td>
                <td rowspan='2'>ID</td>
                <td colspan='3'># of Data Objects</td>
            </tr>
            <tr align='center'>
                <td>Text</td>
                <td>Image</td>
                <td>Total</td>            
            </tr>
            ";
        
        $sciname="";    
        $color="white";
        foreach($arr as $row)
        {        
            if($sciname <> $row["orig_sciname"]) 
            {            
                $sciname = $row["orig_sciname"];
                if($color=="white")$color="aqua";
                else               $color="white";
            }
            print"
            <tr bgcolor='$color'>
                <td >"               . utf8_decode($row["orig_sciname"]) . "</td>
                <td >"               . utf8_decode($row["sciname"]) . "</td>
                <td align='center'><a target='_eol' href='http://www.eol.org/pages/" . $row["tc_id"] . "'>" . $row["tc_id"] . "</a></td>            
                <td align='right'>"  . $row["text"] . "</td>
                <td align='right'>"  . $row["image"] . "</td>
                <td align='right'>"  . $row["total_objects"] . "</td>
            </tr>";        
        }    
        print"</table>";
    }
    
    function sort_details($arr_details)
    {
        $returns = $GLOBALS["returns"];
        
        usort($arr_details, "self::cmp");
        //start limit number of returns    
        $array_count = count($arr_details);
        if($returns > 0)
        {
            for ($i = 0; $i < $array_count; $i++) 
            {
                if($i > $returns-1)
                {
                    unset($arr_details[$i]);
                }
            }     
        }    
        return $arr_details;
    }
    
    function cmp($a, $b)
    {
        $sort_order = $GLOBALS["sort_order"];    
        return $a["$sort_order"] < $b["$sort_order"];
    }
    
    function get_details($xml,$orig_sciname)
    {
        $arr=array();
        foreach($xml->entry as $species)
        {
            $arr_do = self::get_objects_info("$species->id","$species->title","$orig_sciname");        
            $arr[]=$arr_do;
        }            
        return $arr;
    }
    
    function get_objects_info($id,$sciname,$orig_sciname)
    {
        $api_put_taxid_1 = $GLOBALS["api_put_taxid_1"];
        $api_put_taxid_2 = $GLOBALS["api_put_taxid_2"];
        $sciname_4color = $GLOBALS["sciname_4color"];        
        
        $id=str_ireplace("http://www.eol.org/pages/","",$id);
        $file = $api_put_taxid_1 . $id . $api_put_taxid_2;
        $xml = Functions::get_hashed_response($file);    
        $text=0;$image=0;
        foreach($xml->taxon->dataObject as $object)
        {
                if      ($object->dataType == "http://purl.org/dc/dcmitype/StillImage") $image++;
                elseif  ($object->dataType == "http://purl.org/dc/dcmitype/Text") $text++;        
        }
        $total_objects=$image + $text;    
        if($orig_sciname != $sciname_4color)
        {
            $sciname_4color=$sciname;
        }    
        return array($orig_sciname=>1,"orig_sciname"=>$orig_sciname,"tc_id"=>$id,"sciname"=>$sciname,"text"=>$text,"image"=>$image,"total_objects"=>$total_objects);
    }
    
    function array_trim($a,$len) 
    { 	
    	$b=array();
    	$j = 0; 
    	for ($i = 0; $i < $len; $i++) 
    	{ 
    		if (array_key_exists($i,$a))
    		{
    			if (trim($a[$i]) != "") { $b[$j++] = $a[$i]; } 		
    		}
    	} 	
    	return $b; 
    }
}
?>