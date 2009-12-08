<?php
//#!/usr/local/bin/php
/*  turtles website 
http://www.iucn-tftsg.org/pub-chron/
*/

//exit;

//define("ENVIRONMENT", "development");
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
//$wrap = "<br>"; 
 
$resource = new Resource(90); //exit($resource->id);


$schema_taxa = array();
$used_taxa = array();


//$file = "";

$urls = array( 0 => array( "url" => "http://www.iucn-tftsg.org/pub-chron/"                  , "active" => 1),   //
               1 => array( "url" => "http://www.flowervisitors.info/files/lt_bee.htm"       , "active" => 0),   //
               2 => array( "url" => "http://www.flowervisitors.info/files/st_bee.htm"       , "active" => 0)
             );

//$arr_name = array();
$i=0;
foreach($urls as $path)
{    
    if($path["active"])
    {        
        print $i+1 . ". " . $path["url"] . "$wrap";        
        if($i == 0) process_file1($path["url"]); //get the species page URLs to an array
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


/*
$str='  abc
        <p style="dxisplay:none"><a href="http://www.barryshamis.com/?evening">Evening divx</a></p>
        def
        <div style="dxisplay:none"><a href="http://isighttech.com/?grizzly_park">Grizzly Park film</a></div>
        ghi
        ';
$str = remove_tag_with_this_needle($str,"display:none");
print "$str";    
exit;
*/

function process_file1($file)
{        
    global $wrap;
    global $used_taxa;
    
    print "$wrap";
    
    $str = Functions::get_remote_file($file);    
    
    //cleaning bad html code
    $str = str_ireplace('<a href="http://blog.jakerocheleau.com/?drag_me_to_hell">Drag Me to Hell movie</a>' , '', $str);	    
    $str = str_ireplace('<strong>005<em>.</em></strong></span><strong><em> </em></strong> </a>' , '<strong>005<em>.</em></strong></span><strong><em> </em></strong>', $str);	
    //$str = str_ireplace('</a><a href="javascript:void(0)/*306*/">' , '', $str);
    
    $bad_url = array("http://blog.jakerocheleau.com/?drag_me_to_hell",
                     "http://royalstreetinn.com/?cold_mountain",
                     "http://onepercentpress.com/?drumline",
                     "javascript:void(0)/*306*/",
                     "http://www.baserinstincts.com/?finding_neverland",
                     "http://isighttech.com/?kevin_perry_go_large",
                     "http://www.iucn-tftsg.org/cbftt/toc-ind/toc/checklist/"
                    );    
                        
    $str = str_ireplace('<a href="http://www.iucn-tftsg.org/cbftt/toc-ind/toc' , '&arr[]=<a href="http://www.iucn-tftsg.org/cbftt/toc-ind/toc', $str);	  
    $arr=array();	
    parse_str($str);	
    print "after parse_str recs = " . count($arr) . "$wrap";	//print_r($arr);
    
    $arr2=array();
    for ($i = 0; $i < count($arr); $i++) 
    {
        $temp = $arr[$i];                
        $beg='<a href="'; $end1='">'; $end2="173xxx";            
        $url = trim(parse_html($temp,$beg,$end1,$end2,$end2,$end2,""));            
        if(!in_array($url,$bad_url))$arr2["$url"]=1;
    }    
    $arr2 = array_keys($arr2);    
    
    process_loop($arr2);
    
}//end function process_file1($file)

function process_loop($arr) //run each URL and extract data
{
    global $wrap;
    
    $i=0;
    foreach($arr as $url)
    {
        //if($i >= 5)break; //debug        //ditox
        $i++;
//        if($i == 18){
        if(1==1){

        $str = Functions::get_remote_file($url);            
        
        //$str = strip_tags($str,'<a><A><p><b><span><br><hr>');
        $str = clean_str($str);
        //clean html
        $str = str_ireplace('<span style="color: rgb(0, 0, 0);">' , '', $str);	    
        $str = str_ireplace('<span style="color: rgb(255, 0, 0);">' , '', $str);	    
        $str = str_ireplace('</span>' , '', $str);	    
        $str = str_ireplace('display: none' , 'display:none', $str);	                    
        //28
        $str = str_ireplace('<ul style="display:none"><li><a href="http://onepercentpress.com/?torn_curtain">Torn Curtain</a></li></ul>' , '</p>', $str);	    

        $str = remove_tag_with_this_needle($str,"display:none");                
        
        $str = str_ireplace('<sup>1</sup>' , '', $str);	    
        $str = str_ireplace('<sup>2</sup>' , '', $str);	    
        $str = str_ireplace('<sup>3</sup>' , '', $str);	    
        $str = str_ireplace('<sup>4</sup>' , '', $str);	    
        $str = str_ireplace('<sup>5</sup>' , '', $str);	    
        $str = str_ireplace('<sup>6</sup>' , '', $str);	    
        $str = str_ireplace('<sup>7</sup>' , '', $str);	    
        $str = str_ireplace('<sup>1,2</sup>' , '', $str);	    
        $str = str_ireplace('<sup>2,3</sup>' , '', $str);	    
        $str = str_ireplace('<sup>2,4</sup>' , '', $str);	            
       
        //get sciname                
        $beg='<p style="text-align: center;"><i><b>'; $end1='<br />'; $end2="173xxx";            
        $sciname = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
        if(!$sciname)
        {   $beg='<p style="text-align: center;"><b><i>'; $end1='<br />'; $end2="173xxx";            
            $sciname = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
        }
        if(!$sciname)
        {   $beg='<p style="text-align: center;"><strong><em>'; $end1='<br />'; $end2="173xxx";            
            $sciname = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
        }
        if(!$sciname)
        {   $beg='<p style="text-align: center;"><em><strong>'; $end1='<br />'; $end2="173xxx";            
            $sciname = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
        }        
        $sciname = str_ireplace('&ndash;' , '', $sciname);
        //end get sciname
                
        //get common name
        $tmp_str = $str;
        $pos = stripos($tmp_str,$sciname);     
        $tmp_str=trim(substr($tmp_str,$pos+strlen($sciname),strlen($tmp_str)));        
		
        //6 for
        $tmp_str = "xxx" . substr($tmp_str,6,strlen($tmp_str));
        $beg='xxx'; $end1='&ndash;'; $end2="173xxx";            
        $comname = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));            
            
        $comname = "xxx" . $comname;
        $beg='xxx'; $end1='</p>'; $end2="173xxx";            
        $comname = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));                        
        
        //$comname = str_ireplace(';' , '', $comname);  //not here but put below
        //end get common name
        
        //get agent
        $tmp_str = $str;
        $pos = stripos($tmp_str,$comname);     
        $tmp_str=trim(substr($tmp_str,$pos+strlen($comname),strlen($tmp_str)));                
        //print "<hr>pos is [[$pos]]<hr>$tmp_str"; exit;		
        //4 for </p>
        $tmp_str = "xxx" . substr($tmp_str,4,strlen($tmp_str));
        $beg='xxx'; $end1='&ndash;'; $end2="173xxx";            
        $agent = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));            
            
        $agent = "xxx" . $agent;
        $beg='xxx'; $end1='</p>'; $end2="173xxx";            
        $agent = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));                                
        //end get agent
        
        $tmp_str =strip_tags($tmp_str,"<p><b>");
        $tmp_str =clean_str($tmp_str);
        
        //bad html        
        $tmp_str = str_ireplace('&nbsp;&nbsp;&nbsp; IUCN 2007 Red List:' , 'IUCN 2007 Red List:', $tmp_str);
        $tmp_str = str_ireplace('&mdash;' , '&ndash;', $tmp_str);
     
//      print $tmp_str; exit; //ditox
//      print $str; exit; //ditox //for images and maps

        //get distribution2
        $beg='Distribution:'; $end1='</div>'; $end2="173xxx";            
        $caption = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,"",true));                                                
        $temp = $caption;
        $caption = trim(strip_tags($caption));                
        //-----------------------------------------------
        $map_url = "http://www.iucn-tftsg.org/wp-content/uploads/image/Maps/";
        $beg=$map_url; $end1='.jpg'; $end2='.bmp'; $end3='.png';            
        $map = trim(parse_html($str,$beg,$end1,$end2,$end3,$end2,"",true));                                        
        if($map=="")
        {   $map_url = "http://www.iucn-tftsg.org/wp-content/uploads/2009/02/";
            $beg=$map_url; $end1='.jpg'; $end2='.bmp'; $end3='.png';            
            $map = trim(parse_html($temp,$beg,$end1,$end2,$end3,$end2,"",true));                                        
        }        
        $map = $map_url . $map . ".jpg";        
        $handle = @fopen($map,'r');
        if(!$handle)
        {
            $map = str_ireplace('.jpg' , '.bmp', $map);	            
            $handle = @fopen($map,'r');
            if(!$handle)$map = str_ireplace('.bmp' , '.png', $map);	            
        }                
        //-----------------------------------------------
        
        
        
        $map_caption = "
        <div style='font-size : x-small;overflow : scroll;'>
        <table align='center' border='0'>
        <tr><td align='center'>";
        
        if($i == 1)$map_caption .= "<img height='650' width='350'";
        else       $map_caption .= "<img height='410' width='500'";          
        
        $map_caption .= " src='$map'></td></tr>
        </table></div>
        ";        
        
        $map_caption .= "
        <table align='center' border='0'>
        <tr><td>$caption</td></tr>
        </table>        
        ";        
        //end get distribution2        

        //get image
        $image_url = "http://www.iucn-tftsg.org/wp-content/uploads/image/Photos/";                
        $beg=$image_url; $end1='.jpg'; $end2="173xxx";            
        $image = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,"",true));                                        
        if($image=="")
        {   $image_url = "http://www.iucn-tftsg.org/wp-content/uploads/2009/02/";
            $beg=$image_url; $end1='.jpg'; $end2="173xxx";            
            $image = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        $image = $image_url . $image . ".jpg";
        //end get image
        
        //get image caption                
        $beg='(Adobe Acrobat 6.0 or later required)'; $end1='Distribution:'; $end2="173xxx";            
        $img_caption = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));                                        
        $img_caption = strip_tags($img_caption);        
        $img_caption = str_ireplace('&nbsp;' , '', $img_caption);        

        $beg='Photo by'; $end1='<b>'; $end2='<br />';            
        $img_agent = trim(parse_html($img_caption,$beg,$end1,$end2,$end2,$end2,"",true));                                        
        if($img_agent=="")
        {   $beg='Photos by'; $end1='<b>'; $end2='<br />';            
            $img_agent = trim(parse_html($img_caption,$beg,$end1,$end2,$end2,$end2,"",true));                                        
        }        
        $img_agent = trim(strip_tags($img_agent));
        $img_agent = str_ireplace('&nbsp;' , '', $img_agent);
        
        
        //end get image caption    

        //get summary        
        $beg='Summary</b>. &ndash;'; $end1='</p>'; $end2="173xxx";            
        $summary = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));                                        
        if($summary=="")
        {   $beg='Summary</b><p> . &ndash;'; $end1='</p>'; $end2="173xxx";            
            $summary = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($summary=="")
        {   $beg='Summary</b>  . &ndash;'; $end1='</p>'; $end2="173xxx";            
            $summary = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        if($summary=="")
        {   $beg='Summary</b></p><p>. &ndash;'; $end1='</p>'; $end2="173xxx";            
            $summary = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        if($summary=="")
        {   $beg='Summary</b>. &mdash;'; $end1='</p>'; $end2="173xxx";            
            $summary = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($summary=="")
        {   $beg='Summary. &ndash;'; $end1='</p>'; $end2="173xxx";            
            $summary = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($summary=="")
        {   $beg='Summary. &mdash;'; $end1='</p>'; $end2="173xxx";            
            $summary = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}

        
        
            
        //end get summary
        
        //get distribution
        $beg='Distribution</b>. &ndash;'; $end1='</p>'; $end2="173xxx";            
        $distribution = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));                                                
        if($distribution == "")
        {   $beg='Distribution</b></p>&nbsp;<p>. &ndash;'; $end1='</p>'; $end2="173xxx";
            $distribution = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        if($distribution == "")
        {   $beg='Distribution</b></p><p>. &ndash;'; $end1='</p>'; $end2="173xxx";
            $distribution = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($distribution == "")
        {   $beg='Distribution</b><p> . &ndash;'; $end1='</p>'; $end2="173xxx";
            $distribution = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($distribution == "")
        {   $beg='Distribution</b>  . &ndash;'; $end1='</p>'; $end2="173xxx";
            $distribution = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($distribution == "")
        {   $beg='Distribution. &ndash;'; $end1='</p>'; $end2="173xxx";
            $distribution = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($distribution == "")
        {   $beg='Distribution<p> . &ndash;'; $end1='</p>'; $end2="173xxx";
            $distribution = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($distribution == "")
        {   $beg='Distribution. &mdash;'; $end1='</p>'; $end2="173xxx";
            $distribution = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        //end get distribution        

        //get synonymy
        $beg='Synonymy</b>. &ndash;'; $end1='</p>'; $end2="173xxx";            
        $synonymy = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));                                                
        if($synonymy == "")
        {   $beg='Synonymy. &mdash;'; $end1='</p>'; $end2="173xxx";            
            $synonymy = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        if($synonymy == "")
        {   $beg='Synonymy. &ndash;'; $end1='</p>'; $end2="173xxx";            
            $synonymy = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        if($synonymy == "")
        {   $beg='Synonymy</b><p>   </p><p>  </p><p>. &ndash;'; $end1='</p>'; $end2="173xxx";            
            $synonymy = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        if($synonymy == "")
        {   $beg='Synonymy</b><p> . &ndash;'; $end1='</p>'; $end2="173xxx";            
            $synonymy = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        if($synonymy == "")
        {   $beg='Synonymy</b>  . &ndash;'; $end1='</p>'; $end2="173xxx";            
            $synonymy = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        if($synonymy == "")
        {   $beg='Synonymy<p> . &ndash;'; $end1='</p>'; $end2="173xxx";            
            $synonymy = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        if($synonymy == "")
        {   $beg='Synonymy  . &ndash;'; $end1='</p>'; $end2="173xxx";            
            $synonymy = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}        
        
        $synonymy = str_ireplace('and' , '&', $synonymy);
        //end get synonymy            

        //get status
        $beg='Status</b>. &ndash;'; $end1='</p>'; $end2="173xxx";            
        $status = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));                                        
        if($status=="")
        {   $beg='Status</b>  . &ndash;'; $end1='</p>'; $end2="173xxx";            
            $status = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($status=="")
        {   $beg='Status</b><p> . &ndash;'; $end1='</p>'; $end2="173xxx";            
            $status = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($status=="")
        {   $beg='Status</b></p><p>. &ndash;'; $end1='</p>'; $end2="173xxx";            
            $status = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($status=="")
        {   $beg='Status. &ndash;'; $end1='</p>'; $end2="173xxx";            
            $status = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($status=="")
        {   $beg='Status<p> . &ndash;'; $end1='</p>'; $end2="173xxx";            
            $status = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        //end get status
        
        //get citation
        $beg='Citation:</b>'; $end1='</p>'; $end2="173xxx";            
        $citation = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));                                        
        if($citation == "")
        {   $beg='Citation:</b></p>'; $end1='</p>'; $end2="173xxx";            
            $citation = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($citation == "")
        {   $beg='Citation:</b>  </p>'; $end1='</p>'; $end2="173xxx";            
            $citation = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        if($citation == "")
        {   $beg='Citation:</p>'; $end1='</p>'; $end2="173xxx";            
            $citation = trim(parse_html($tmp_str,$beg,$end1,$end2,$end2,$end2,"",true));}
        //end get citation
        
        //get pdf url
        $beg='http://www.iucn-tftsg.org/wp-content/uploads/file/Accounts/'; $end1='.pdf'; $end2="173xxx";            
        $pdf_url = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,"",true));                                        
        $pdf_url = $beg . $pdf_url . $end1;        
        if(!($handle = @fopen($pdf_url,'r')))$pdf_url = "";    
        
        
        //end get pdf url
        
                
        //$sciname = str_ireplace('<p>' , '', $sciname);	    
		$sciname = strip_tags($sciname);            
		$comname = strip_tags($comname);            
        $agent = strip_tags($agent);                    
        $summary = strip_tags($summary);            
        if($pdf_url != "")$summary .= "<p><a href='$pdf_url'>Download the full article on the IUCN Tortoise and Freshwater Turtle Specialist Group site</a>";
        
        
        $distribution = strip_tags($distribution);            
        $synonymy = strip_tags($synonymy);            
        $status = strip_tags($status);                    
        $citation = strip_tags($citation);                            
        
        print "$i. $sciname [$comname] [$agent]         
        <br><u>PDF url:</u><br> [$pdf_url] 
        <br><u>summary:</u><br> [$summary]
        ";
        
        
        
        /*
        print"              
        
        <br><u>distribution:</u><br> [$distribution]        
        <br><u>synonymy:</u><br> [$synonymy]
        <br><u>status:</u><br> [$status]        
        <br><u>citation:</u><br> [$citation]
        <br><img height='400' width='600' src='$image'><br>$image
        <br><u>image caption:</u><br> [$img_caption]
        <br><u>map + caption:</u><br> [$map_caption]                
        <br><u>image agent:</u><br> [$img_agent]
        "; */
        print "<hr>";        
                        
        assign_variables($sciname,$comname,$agent,$summary,$distribution,$synonymy,$status,$citation,$image,$img_caption,$img_agent,$map_caption,$url,$i);                            
        }        
    }//main loop
}//end process_loop()

function assign_variables($sciname,$comname,$agent,$summary,$distribution,$synonymy,$status,$citation,$image,$img_caption,$img_agent,$map_caption,$url,$k)
{
    global $used_taxa;
    
    $kingdom="Animalia";
    
        $genus = substr($sciname,0,stripos($sciname," "));
        $taxon_identifier = "iucn_ssc_" . $k;
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

            /////////////////////////////////////////////////////////////
            $taxon_parameters["commonNames"] = array();
            $arr_comname=conv_2array($comname);
            //for ($i = 0; $i < count($arr_comname); $i++) 
            foreach ($arr_comname as $commonname) 
            {
                $commonname = str_ireplace(';' , '', $commonname);
                $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => $commonname, "language" => "en"));
            }
            /////////////////////////////////////////////////////////////
            $taxon_params["synonyms"] = array();
            $arr_synonym=conv_2array($synonymy);
            foreach ($arr_synonym as $synonym) 
            {
                $taxon_parameters["synonyms"][] = new SchemaSynonym(array("synonym" => $synonym, "relationship" => "synonym"));
            }
            /////////////////////////////////////////////////////////////
            
            $taxon_parameters["dataObjects"]= array();        
            $used_taxa[$taxon_identifier] = $taxon_parameters;
        }        
        
        //start text dataobject                
        $dc_identifier = "GenDesc_" . $taxon_identifier;    
        $desc = $summary;
        $title = "Summary";
        $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
        $type = "text";
        $reference = $citation;        
        $data_object_parameters = get_data_object($dc_identifier, $desc, $title, $url, $subject, $type, $reference, $agent);       
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
        //end text dataobject                    
        
        //start text dataobject                
        $dc_identifier = "Distribution_" . $taxon_identifier;    
        $desc = $distribution . "<br>" . $map_caption;
        $title = "Distribution";
        $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
        $type = "text";
        $reference = $citation;        
        $data_object_parameters = get_data_object($dc_identifier, $desc, $title, $url, $subject, $type, $reference, $agent);       
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
        //end text dataobject                    
        
        //start text dataobject                
        $dc_identifier = "Status_" . $taxon_identifier;    
        $desc = $status;
        $title = "Status";
        $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus";
        $type = "text";
        $reference = $citation;        
        $data_object_parameters = get_data_object($dc_identifier, $desc, $title, $url, $subject, $type, $reference, $agent);       
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
        //end text dataobject                    
        

        //start img dataobject                
        $dc_identifier = "Image_" . $taxon_identifier;    
        $desc = $img_caption;
        $title = "";
        $subject = "";
        $type = "image";
        $reference = "";        
        
        $mediaurl = $image;
        $agent=$img_agent;

        $data_object_parameters = get_data_object($dc_identifier, $desc, $title, $url, $subject, $type, $reference, $agent, $mediaurl);       
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
        //end img dataobject                    

        
        
        $used_taxa[$taxon_identifier] = $taxon_parameters;                                
        
    return "";        
}

function conv_2array($list)
{    
    $list = str_ireplace('and', ',', $list);	    
    $arr = explode(",",$list);        
    for ($i = 0; $i < count($arr); $i++) 
    {
        $arr[$i]=trim($arr[$i]);
    }
    return $arr;
}

function get_data_object($id, $description, $title, $url, $subject, $type, $reference, $agent, $mediaurl=NULL)
{
     
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
        $dataObjectParameters["identifier"] = $id;    
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        
        $dataObjectParameters["mimeType"] = "image/jpeg";
        //$dataObjectParameters["thumbnailURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=thumb";
        $dataObjectParameters["mediaURL"] = $mediaurl;
    }


    ///////////////////////////////////
    $agent = conv_2array($agent);
    foreach ($agent as $agent) 
    {
        $agentParameters = array();        
        $agentParameters["homepage"] = "http://www.iucn-tftsg.org/";
        $agentParameters["role"] = "author";
        $agentParameters["fullName"] = $agent;
        $agents[] = new SchemaAgent($agentParameters);
    }        
    $dataObjectParameters["agents"] = $agents;    
    ///////////////////////////////////
    
    $dataObjectParameters["description"] = $description;        
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;    
    
    $dataObjectParameters["language"] = "en";        
    $dataObjectParameters["source"] = $url;    

    $dataObjectParameters["rights"] = "Copyright 2009 IUCN Tortoise and Freshwater Turtle Specialist Group";
    $dataObjectParameters["rightsHolder"] = "IUCN/SSC Tortoise and Freshwater Turtle Specialist Group";
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by-nc/3.0/";
    ///////////////////////////////////
    if($reference != "")
    {
        $dataObjectParameters["references"] = array();
        $referenceParameters = array();
        $referenceParameters["fullReference"] = trim($reference);
        $references[] = new SchemaReference($referenceParameters);
        $dataObjectParameters["references"] = $references;
    }    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();        
    $audienceParameters = array();      
    $audienceParameters["label"] = "Expert users";      $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    $audienceParameters["label"] = "General public";    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    ///////////////////////////////////
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
function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL,$exit_on_first_match=NULL)	//str = the html block
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
        if(strtolower(substr($str,$i,$beg_len)) == strtolower($beg))
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
function remove_tag_with_this_needle($str,$needle)
{
    $pos = stripos($str,$needle); //get pos of needle   
    if($pos != ""){
    
    
    $char="";
    $accumulate=""; $start_get=false;
    while ($char != "<") //get pos of < start tag
    {
        $pos--;
        $char = substr($str,$pos,1);
        
        if($char == " ")$start_get = true;
        if($start_get)$accumulate .= $char;                
    }
    //print "pos_of_start_tag [$pos]<br>";
    $pos_of_start_tag = $pos;
    
    //now determine what type of tag it is
    $accumulate = substr($accumulate,0,strlen($accumulate)-1);
    $accumulate = reverse_str($accumulate);
    //print "<hr>$str<hr>$accumulate";               
    
    //now find the pos of the end tag e.g. </div
    $char="";
    $pos = $pos_of_start_tag;
    $end_tag = "</" . $accumulate . ">";
    //print "<br>end tag is " . $end_tag;
    while ($char != $end_tag )
    {   
        $pos++;  
        $char = substr($str,$pos,strlen($end_tag));                
    }    
    //print"<hr>pos of end tag [$pos]<hr>";       
    $pos_of_end_tag = $pos;
    $str = remove_substr_from_this_positions($str,$pos_of_start_tag,$pos_of_end_tag,strlen($end_tag));    
    if(stripos($str,$needle) != "")$str = remove_tag_with_this_needle($str,$needle);    
    
    }
    
    return trim(clean_str($str));
}
function remove_substr_from_this_positions($str,$startpos,$endpos,$len_of_end_tag)
{
    $str1 = substr($str,0,$startpos);
    $str2 = substr($str,$endpos+$len_of_end_tag,strlen($str));
    return $str1 . $str2;
}
function reverse_str($str)
{
    $accumulate="";
    $length = strlen($str)-1;
    for ($i = $length; $i >= 0; $i--) 
    {
        $accumulate .= substr($str,$i,1);
    }    
    return trim($accumulate);
}

?>