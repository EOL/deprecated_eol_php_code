<?php
//#!/usr/local/bin/php
/* flowervisitors connector */

//exit;

define("ENVIRONMENT", "development");
//define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];

 /*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
 */

$wrap = "\n"; 
$wrap = "<br>"; 
 
$resource = new Resource(1); //exit($resource->id);


$schema_taxa = array();
$used_taxa = array();


//$file = "";

$urls = array( 0 => array( "url" => "http://flowervisitors.info/index.htm"                  , "active" => 0),

               1 => array( "url" => "http://www.flowervisitors.info/files/lt_bee.htm"       , "active" => 0),
               2 => array( "url" => "http://www.flowervisitors.info/files/st_bee.htm"       , "active" => 0),
               3 => array( "url" => "http://www.flowervisitors.info/files/wasps.htm"        , "active" => 0),
               4 => array( "url" => "http://www.flowervisitors.info/files/beetles.htm"      , "active" => 0),
               5 => array( "url" => "http://www.flowervisitors.info/files/plant_bugs.htm"   , "active" => 0),
               6 => array( "url" => "http://www.flowervisitors.info/files/lepidoptera.htm"  , "active" => 0),
               
               7  => array( "url" => "http://flowervisitors.info/insects/birds.htm"         , "active" => 0),
               8  => array( "url" => "http://flowervisitors.info/insects/bees.htm"          , "active" => 0),
               9  => array( "url" => "http://flowervisitors.info/insects/wasps.htm"         , "active" => 0),
               10 => array( "url" => "http://flowervisitors.info/insects/flies.htm"         , "active" => 1),   //dd              
               11 => array( "url" => "http://flowervisitors.info/insects/moths.htm"         , "active" => 0),
               12 => array( "url" => "http://flowervisitors.info/insects/beetles.htm"       , "active" => 0),
               13 => array( "url" => "http://flowervisitors.info/insects/bugs.htm"          , "active" => 0)               
             );

$i=0;
foreach($urls as $path)
{    
    if($path["active"])
    {
        print $i . " " . $path["url"] . "$wrap $wrap";        
        if      ($i == 0)               process_file1($path["url"]);        
        elseif  ($i >= 1 and $i <= 6)   process_file2($path["url"]);           
        elseif  ($i >= 7 and $i <= 13)  process_file3($path["url"]);           
    }
    $i++;
}    

foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---
print "$wrap -- Done processing -- "; exit;



function process_file3($file)
{       
    global $wrap;
    global $used_taxa;
    
    $str = Functions::get_remote_file($file);
    $str = clean_str($str);

    //special case, html error
    $str = str_ireplace('<TD ALIGN="CENTER"><A HREF="bees/agapostemon_virescens.htm"></A><B><FONT FACE="Times New Roman"><A HREF="bees/agapostemon_virescens.htm" NAME="agapostemon_virescens">Agapostemon virescens</A></FONT></B><FONT COLOR="#0000FF"><B><FONT FACE="Times New Roman"></FONT></B></FONT></TD>' , '<TD ALIGN="CENTER"><FONT COLOR="#0000FF"><B><FONT FACE="Times New Roman"><A HREF="bees/agapostemon_virescens.htm" NAME="agapostemon_texanus">Agapostemon virescens</A></FONT></B></FONT></TD>', $str);	    
      
    
  	$pos1 = stripos($str,"<TABLE");     
   	//$pos2 = stripos($str,"</TABLE>");	
    $pos2 = stripos($str,"Return to:");	
    
   	//if($pos1 != "" and $pos2 != "") $str = trim(substr($str,$pos1,$pos2-$pos1+8));
    if($pos1 != "" and $pos2 != "") $str = trim(substr($str,$pos1,$pos2-$pos1));
    $str=strip_tags($str,'<TR><A>');
    //print "<hr>$str"; exit;
    
    $str = str_ireplace('</TR>' , "&arr[]=", $str);	
    $str=strip_tags($str,'<A>');
    
    $str=trim($str);
    $str=substr($str,0,strlen($str)-7);   //to remove last part of string "&arr[]="

    //print "<hr>$str"; exit;

    $arr=array();	
    parse_str($str);	
    print "after parse_str recs = " . count($arr) . "$wrap $wrap";	//print_r($arr);

    process_loop($arr,"http://flowervisitors.info/insects/","Animalia");

}//end function process_file3($file)

function process_file1($file)
{    
    global $wrap;
    global $used_taxa;
    
    $str = Functions::get_remote_file($file);
    $str = clean_str($str);

    $str = str_ireplace('&times;' , "", $str);    //for Vernonia × illinoensis (Illinois Ironweed)

    $pos = stripos($str,"<!-- Google CSE Search Box Ends -->");
    $str = trim(substr($str,$pos+35,strlen($str)));

    $str = str_ireplace('<BR>' , "<br>", $str);	

    $str = strip_tags($str, '<br><a>');
    $str = "<br>" . $str;

    $str = str_ireplace('<br><br>' , "&arr[]=", $str);	
    $str = str_ireplace('<br>' , "&arr[]=", $str);	

    $arr=array();	
    parse_str($str);	
    print "after parse_str recs = " . count($arr) . "$wrap $wrap";	//print_r($arr);

    //print $str;
    
    process_loop($arr,"http://flowervisitors.info/","Plantae");
    
}//end function process_file1($file)

function process_loop($arr,$path,$kingdom)
{
    global $wrap;
    
    $i=0;
    foreach($arr as $species)
    {
        //if($i >= 3)break;
        $i++;

        $species = clean_str($species);
        //print "{$species}";       
        /* <A HREF="plants/velvetleaf.htm" NAME="velvetleaf">Abutilon theophrastii (Velvet Leaf)</A> */
        $sciname="";$commonname="";$url="";    
        $beg='HREF="'; $end1='" NAME'; $end2="173xxx";    $url = $path . trim(parse_html($species,$beg,$end1,$end2,$end2,$end2,"",true));    

        $species = strip_tags($species);            
        $species = "xxx" . $species;    
    
        $beg='('; $end1=')'; $end2="173xxx";    $commonname = parse_html($species,$beg,$end1,$end2,$end2,$end2,"");
        $beg='xxx'; $end1='('; $end2="173xxx";    $sciname = trim(parse_html($species,$beg,$end1,$end2,$end2,$end2,""));
    
        print "[$sciname][$commonname][$url]";

        $str = Functions::get_remote_file($url);    
        //start get title
        $title="";
        $beg='</TITLE>'; $end1='<HR'; $end2="173xxx";    
        $title = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
        $title = str_ireplace('<BR>' , " ", $title);        
        $title = trim(strip_tags($title)) . " (<i>$sciname</i>)";
        print "[$title] $wrap"; //exit;
        //end get title
        $str = clean_str($str);        
        //start get desc            
        $desc="";
        $beg='<BLOCKQUOTE>'; $end1='</BLOCKQUOTE>'; $end2="173xxx";    
        $desc = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,"",true));            
        $desc = strip_tags($desc,"<br><b><i>");            
        print "[$desc]";
        print"<hr>";
        //end get desc    
        
        $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations";
        
        //if($desc != "") 
        assign_variables($sciname,$kingdom,$url,$commonname,$desc,$title,$subject);                
        
    }//main loop

}//end process_loop()


function process_file2($file)
{
    global $wrap;
    global $used_taxa;
    $str = Functions::get_remote_file($file);
    $str = clean_str($str);

    
    $beg='<BLOCKQUOTE>'; $end1='</BLOCKQUOTE>'; $end2="173xxx";    
    $str = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,"",true));            
    $str = strip_tags($str,"<i><b><font>");            
    $str = str_ireplace('<FONT  COLOR="#3333FF">' , '<FONT COLOR="#3333FF">', $str);	
    
    
    $str = str_ireplace('&amp;' , '###', $str);	
    
    //print "<hr>$str<str>";        exit;
    
    $str = str_ireplace('<B><FONT COLOR="#3333FF">' , '&arr[]=', $str);	
    //$str = str_ireplace('###' , '&amp;', $str);	
    //print "<hr>$str<str>";
    $arr=array();	
    parse_str($str);	
    print "after parse_str recs = " . count($arr) . "$wrap $wrap";	//print_r($arr);
    
    $i=0;
    foreach($arr as $species)
    {
        if($i >= 3)break;
        $i++;
        $species = str_ireplace('###' , '&amp;', $species);	        
        //print "$species <hr><hr>";
        //start get name
        $species = "***" . $species;
        $beg='***'; $end1=')'; $end2="173xxx";    
        $name = trim(parse_html($species,$beg,$end1,$end2,$end2,$end2,"",true)).")"; //print "[$name]";        
        //end
        //start get sciname, commonname
        $name = "***" . $name;
        $beg='***'; $end1='('; $end2="173xxx";    
        $sciname = trim(parse_html($name,$beg,$end1,$end2,$end2,$end2,"",true)); print "[$sciname]";        
        $beg='('; $end1=')'; $end2="173xxx";    
        $commonname = trim(parse_html($name,$beg,$end1,$end2,$end2,$end2,"",true)); print "[$commonname]";        
        //end
        //start get desc
        $pos = stripos($species,")");
        $desc = trim(substr($species,$pos+1,strlen($species))); 
        $desc = strip_tags($desc, '<i>');
        //print "[[$desc]]";
        //end                
        print "<hr>";       
        
        $kingdom="";
        $url=$file;
        $title="Description";
        $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
        
        //if($desc != "") 
        assign_variables($sciname,$kingdom,$url,$commonname,$desc,$title,$subject);        
    }        
}//end function process_file2($file)


function assign_variables($sciname,$kingdom,$url,$commonname,$desc,$title,$subject)
{
    global $used_taxa;
    
        $genus = substr($sciname,0,stripos($sciname," "));
        $taxon_identifier = str_replace(" ", "_", $sciname);                
        $dc_identifier = "txt_" . $taxon_identifier;    
        if(@$used_taxa[$taxon_identifier])
        {
            $taxon_parameters = $used_taxa[$taxon_identifier];
        }
        else
        {
            $taxon_parameters = array();
            $taxon_parameters["identifier"] = $taxon_identifier;
            $taxon_parameters["kingdom"] = $kingdom;
            $taxon_parameters["genus"] = $genus;
            $taxon_parameters["scientificName"]= $sciname;        
            $taxon_parameters["source"] = $url;        
            $taxon_parameters["commonNames"] = array();
            $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => $commonname, "language" => "en"));
            $taxon_parameters["dataObjects"]= array();        
            $used_taxa[$taxon_identifier] = $taxon_parameters;
        }        
        //start text dataobject        
        $data_object_parameters = get_data_object($dc_identifier, $desc, $title, $url, $subject);       
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
        //end text dataobject                    
        $used_taxa[$taxon_identifier] = $taxon_parameters;                                
        
    return "";        
}


function get_data_object($id, $description, $title, $url, $subject)
{
    $type="text";
    
    $dataObjectParameters = array();
    
    if($type == "text")
    {   
        $dataObjectParameters["identifier"] = $id;    
        $dataObjectParameters["title"] = $title;
        ///////////////////////////////////    
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = $subject;
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
        ///////////////////////////////////        
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";    
        $dataObjectParameters["mimeType"] = "text/html";        
    }
    else
    {
        $dataObjectParameters["identifier"] = "img_" . $id;    
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $dataObjectParameters["mimeType"] = "image/jpeg";
        $dataObjectParameters["thumbnailURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=thumb";
        $dataObjectParameters["mediaURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=jpg";        
    }


    $dataObjectParameters["description"] = $description;        
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;    
    
    $dataObjectParameters["language"] = "en";    
    
    $dataObjectParameters["source"] = $url;
    

    $dataObjectParameters["rights"] = "Copyright &#169; 2002-2009 by Dr. John Hilty";
    $dataObjectParameters["rightsHolder"] = "John Hilty";
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by-nc/3.0/";

    
    ///////////////////////////////////
    $agentParameters = array();
    $agentParameters["homepage"] = "http://flowervisitors.info/";
    $agentParameters["role"] = "source";
    $agentParameters["fullName"] = "John Hilty";
    $agents[] = new SchemaAgent($agentParameters);
    $dataObjectParameters["agents"] = $agents;    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();        
    $audienceParameters = array();      
    $audienceParameters["label"] = "Expert users";      $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    $audienceParameters["label"] = "General public";    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    return $dataObjectParameters;
}

function clean_str($str)
{    
    $str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);			
    //$str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '#', $str);			
    // this line counts how many # as num, and repeats this char in num times, then replaces these chars with just 1 space ' ' 
    //$str = str_replace(str_repeat("#", substr_count($str, '#')), ' ', $str);
    return $str;
}
function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL,$exit_on_first_match=false)	//str = the html block
{
    //PRINT "[$all]"; exit;
	$beg_len = strlen(trim($beg));
	$end1_len = strlen(trim($end1));
	$end2_len = strlen(trim($end2));
	$end3_len = strlen(trim($end3));	
	$end4_len = strlen(trim($end4));		
	//print "[[$str]]";

	$str = trim($str); 	
	$str = $str . "|||";	
	$len = strlen($str);	
	$arr = array(); $k=0;	
	for ($i = 0; $i < $len; $i++) 
	{
		if(substr($str,$i,$beg_len) == $beg)
		{	
			$i=$i+$beg_len;
			$pos1 = $i;			
			//print substr($str,$i,10) . "<br>";									
			$cont = 'y';
			while($cont == 'y')
			{
				if(	substr($str,$i,$end1_len) == $end1 or 
					substr($str,$i,$end2_len) == $end2 or 
					substr($str,$i,$end3_len) == $end3 or 
					substr($str,$i,$end4_len) == $end4 or 
					substr($str,$i,3) == '|||' )
				{
					$pos2 = $i - 1; 					
					$cont = 'n';					
					$arr[$k] = substr($str,$pos1,$pos2-$pos1+1);																				
					//print "$arr[$k] $wrap";					                    
					$k++;
				}
				$i++;
			}//end while
			$i--;			
            
            //start exit on first occurrence of $beg
            if($exit_on_first_match)break;
            //end exit on first occurrence of $beg
            
		}		
	}//end outer loop
    if($all == "")	
    {
        $id='';
	    for ($j = 0; $j < count($arr); $j++){$id = $arr[$j];}		
        return $id;
    }
    elseif($all == "all") return $arr;	
}//end function

?>