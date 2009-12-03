<?php
//#!/usr/local/bin/php
/*  turtles website 
http://www.iucn-tftsg.org/pub-chron/
*/

//exit;

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
 
$resource = new Resource(4); //exit($resource->id);


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
        print $i . " " . $path["url"] . "$wrap $wrap";        
        if      ($i == 0)               process_file1($path["url"]); //get the URLs
        elseif  ($i >= 1 and $i <= 7)   process_file2($path["url"]);           
        elseif  ($i >= 8 and $i <= 14)  process_file3($path["url"]);    
        elseif  ($i >= 15 and $i <= 16) process_file4($path["url"],$i); 
    }
    $i++;
}    

exit;

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
    
    print "$wrap";
    
    $str = Functions::get_remote_file($file);    
    
    //cleaning bad html code
    $str = str_ireplace('<a href="http://blog.jakerocheleau.com/?drag_me_to_hell">Drag Me to Hell movie</a>' , '', $str);	    
    $str = str_ireplace('<strong>005<em>.</em></strong></span><strong><em> </em></strong> </a>' , '<strong>005<em>.</em></strong></span><strong><em> </em></strong>', $str);	
    /*    
    $str = str_ireplace('Black-Knobbed Map Turtle, Black-Knobbed Sawback.' , 'Black-Knobbed Map Turtle, Black-Knobbed Sawback.</a>', $str);	
    $str = str_ireplace('<div style="display:none"><a href="http://www.baserinstincts.com/?finding_neverland">Finding Neverland buy</a></div>' , '', $str);	
    $str = str_ireplace('<p> </span><a href="http://www.iucn-tftsg.org/cbftt/toc-ind/toc/cuora-flavomarginata-035/">' , '</span><a href="http://www.iucn-tftsg.org/cbftt/toc-ind/toc/cuora-flavomarginata-035/">', $str);	
    $str = str_ireplace('<p> <u style="display:none"><a href="http://www.iucn-tftsg.org/?mad_max_beyond_thunderdome">Mad Max Beyond Thunderdome buy</a></u> </p>' , '', $str);	
    $str = str_ireplace('<strong style="display: none;"><a href="http://satellitephonesinfo.com/?next_friday">Next Friday buy</a></strong>' , '', $str);
    $str = str_ireplace('<form style="display:none"><a href="http://onepercentpress.com/?drumline">Drumline buy</a></form>' , '', $str);
    $str = str_ireplace('<p> <u style="display:none"><a href="http://satellitephonesinfo.com/?wall_e">WALL-E release</a></u> </p>' , '', $str);
    $str = str_ireplace('<div style="display:none"><a href="http://www.bcen.net/?tenacious_d_in_the_pick_of_destiny">Tenacious D in The Pick of Destiny release</a></div>' , '', $str);
    $str = str_ireplace('<em style="display:none"><a href="http://royalstreetinn.com/?cold_mountain">Cold Mountain full</a></em>' , '', $str);
    $str = str_ireplace('<div style="display:none"><a href="http://satellitephonesinfo.com/?boat_trip">Boat Trip trailer</a></div>' , '', $str);
    $str = str_ireplace('<em style="display:none"><a href="http://isighttech.com/?kevin_perry_go_large">Kevin &#038; Perry Go Large movie full</a></em>' , '', $str);
    $str = str_ireplace('<span><span style="font-style: italic;">Chelodina</span></span></a><a href="http://www.iucn-tftsg.org/cbftt/toc-ind/toc/chelodina-longicollis-031/"><em> longicollis</em> (Shaw 1794) &ndash; Eastern Long-Necked Turtle, Common Long-Necked Turtle,</a>' , 'Chelodina longicollis (Shaw 1794) &ndash; Eastern Long-Necked Turtle, Common Long-Necked Turtle,</a>', $str);
    */
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
        //if($i >= 3)break; //debug        //ditox
        $i++;
//        if($i >= 33){
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
        
        /*        
        $str = str_ireplace('<em style="display:none"><a href="http://www.bcen.net/?the_trial_of_billy_jack">The Trial of Billy Jack video</a></em>' , '', $str);	    
        //7
        $str = str_ireplace('<div style="display:none"><a href="http://www.baserinstincts.com/?zombieland">Zombieland hd</a></div>' , '', $str);	    
        //11
        $str = str_ireplace('<u style="display:none"><a href="http://johnquiggin.com/?appleseed">Appleseed dvdrip</a></u>' , '', $str);	    
        //16
        $str = str_ireplace('<p style="display:none"><a href="http://www.barryshamis.com/?gundam_wing_endless_waltz">Gundam Wing: Endless Waltz hd</a></p>' , '', $str);	    
        //18
        $str = str_ireplace('<div style="display:none"><a href="http://www.baserinstincts.com/?clubland">Clubland trailer</a></div>' , '', $str);	    
        $str = str_ireplace('<p style="display:none"><a href="http://www.chainreaction-community.net/?the_lord_of_the_rings_the_return_of_the_king">The Lord of the Rings: The Return of the King movie download</a></p>' , '', $str);	    
        //5
        $str = str_ireplace('<form style="display:none"><a href="http://royalstreetinn.com/?kiss_me_deadly">Kiss Me Deadly the movie</a></form>' , '', $str);	    
        $str = str_ireplace('<em style="display:none"><a href="http://satellitephonesinfo.com/?traffic">Traffic download</a></em>' , '', $str);	    
        $str = str_ireplace('<u style="display:none"><a href="http://onepercentpress.com/?powder">Powder release</a></u>' , '', $str);	    
        $str = str_ireplace('<form style="display:none"><a href="http://chessasia.net/?monsters_inc_">Monsters, Inc. download</a></form>' , '', $str);	    
        //9
        $str = str_ireplace('<u style="display:none"><a href="http://onepercentpress.com/?1492_conquest_of_paradise">1492: Conquest of Paradise ipod</a></u>' , '', $str);	    
        $str = str_ireplace('<u style="display:none"><a href="http://www.chainreaction-community.net/?borderland">Borderland dvd</a></u>' , '', $str);	    
        //10
        $str = str_ireplace('<form style="display:none"><a href="http://www.barryshamis.com/?ben_hur">Ben-Hur video</a></form>' , '', $str);	    
        //21
        $str = str_ireplace('<u style="display:none"><a href="http://www.womeningreen.org/?old_school">Old School hd</a></u>' , '', $str);	    
        //22
        $str = str_ireplace('<div style="display:none"><a href="http://www.bcen.net/?tenacious_d_in_the_pick_of_destiny">Tenacious D in The Pick of Destiny rip</a></div>' , '', $str);	    
        //23
        $str = str_ireplace('<em style="display:none"><a href="http://royalstreetinn.com/?braveheart">Braveheart download</a></em>' , '', $str);	    
        //31
        $str = str_ireplace('<p style="display:none"><a href="http://satellitephonesinfo.com/?sorority_row">Sorority Row psp</a></p>' , '', $str);	    
        //26
        <form style="display:none"><a href="http://chessasia.net/?demon_seed">Demon Seed divx</a></form>
        <p style="display:none"><a href="http://www.barryshamis.com/?evening">Evening divx</a></p>
        <div style="display:none"><a href="http://isighttech.com/?grizzly_park">Grizzly Park film</a></div>
        */      



        
        
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
        {
            $beg='<p style="text-align: center;"><b><i>'; $end1='<br />'; $end2="173xxx";            
            $sciname = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
        }
        if(!$sciname)
        {
            $beg='<p style="text-align: center;"><strong><em>'; $end1='<br />'; $end2="173xxx";            
            $sciname = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
        }
        if(!$sciname)
        {
            $beg='<p style="text-align: center;"><em><strong>'; $end1='<br />'; $end2="173xxx";            
            $sciname = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
        }
        //$sciname = strip_tags($sciname);            
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
        //end get common name
        
        //get agent
        $tmp_str = $str;
        $pos = stripos($tmp_str,$comname);     
        $tmp_str=trim(substr($tmp_str,$pos+strlen($comname),strlen($tmp_str)));                
        //print "<hr><hr>$tmp_str"; exit;		
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

        /*
        $tmp_str = str_ireplace('Podocnemis lewyana<p>  is primarily' , 'Podocnemis lewyana is primarily', $tmp_str);
        $tmp_str = str_ireplace('(assessed 2000)<p> <p> ; CITES:' , '(assessed 2000); CITES:', $tmp_str);
        */
        
        
        
        
//        print $tmp_str; exit; //ditox

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

/*                
//Subspecies</big>  </b><big>. &ndash;        
*/      
                
        //$sciname = str_ireplace('<p>' , '', $sciname);	    
		$sciname = strip_tags($sciname);            
		$comname = strip_tags($comname);            
        $agent = strip_tags($agent);            
        
        $synonymy = strip_tags($synonymy);            
        $status = strip_tags($status);            
        
        
        print "$i. $sciname [$comname] [$agent] 
        <br>summary:<br> [$summary]
        <br>distribution:<br> [$distribution]        
        <br>synonymy:<br> [$synonymy]
        <br>status:<br> [$status]        
        ";
        /*
        print"        
        
        "; */
        print "<hr>";
        
        
        //$subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations";        
        //assign_variables($sciname,$kingdom,$url,$commonname,$desc,$title,$subject);                
    
        }        
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
        //if($i >= 3)break; //debug
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
        print "$wrap $wrap";       
        
        $kingdom="";
        $url=$file;
        $title="Description";
        $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
        
        //if($desc != "") 
        assign_variables($sciname,$kingdom,$url,$commonname,$desc,$title,$subject);        
    }        
}//end function process_file2($file)

function process_file4($file,$type)
{
    global $wrap;
    global $used_taxa;
    //global $arr_name;
    
    $str = Functions::get_remote_file($file);
    $str = clean_str($str);
    
    //print "<hr>$str<str>"; exit;
    
    $beg='<BLOCKQUOTE>'; $end1='</BLOCKQUOTE>'; $end2="173xxx";    
    $str = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,"",true));            
    $str = strip_tags($str,"<BR>");            
    
    $str = str_ireplace('<BR>' , '&arr[]=', $str);	
    //print "<hr>$str<str>";
    $arr=array();	
    parse_str($str);	
    print "after parse_str recs = " . count($arr) . "$wrap $wrap";	//print_r($arr);
    
    $arr_sciname = array();
    $arr_name = array();    
    $i=0;
    foreach($arr as $species)
    {
        $i++;
        //no break (debug) for this process
        
        if(stripos($species," = ") != "")
        {

            
            //print "$i. [$species]";
            $species = "xxx" . $species . "yyy";
            
            $beg='xxx'; $end1='('; $end2="=";    
            $sciname = trim(parse_html($species,$beg,$end1,$end2,$end2,$end2,"",true));            
            
            $tribe="";
            $beg='('; $end1=')'; $end2="173xxx";    
            $tribe = trim(parse_html($species,$beg,$end1,$end2,$end2,$end2,"",true));            
            
            $beg='='; $end1='yyy'; $end2="173xxx";    
            $commonname = trim(parse_html($species,$beg,$end1,$end2,$end2,$end2,"",true));            
            
            //print "--- $sciname --- $commonname $wrap";
            //Anthophoridae (Anthophorini) = Anthophorine Bees 
            
            $r = explode(",",$commonname);
            for ($ctr = 0; $ctr <= sizeof($r) - 1; $ctr++) 
            {
                $temp = trim(str_ireplace('  ', ' ', $r[$ctr]));
                
                if(trim($temp)=="etc.")continue;
                
                if($tribe != "")$temp .= " ($tribe)";
                //$name[$sciname][] = $temp;
                $arr_name[$sciname][$temp] = 1;
                $arr_sciname[$sciname] = 1;
            }                    
            
            $kingdom = "";            
            $url = $file;
            $desc = "";
            $title = "";
            $subject = "";
            //======================================
            //assign_variables($sciname,$kingdom,$url,$commonname,$desc,$title,$subject);              
            //start assign_variables
            
            //end
            
        }
        
    }   


    $arr_sciname = array_keys($arr_sciname);
    
    $i=0;
    foreach($arr_sciname as $sciname)
    {
        $i++; print "$i. "; print "$sciname $wrap";                       

        $genus = substr($sciname,0,stripos($sciname," "));
        $taxon_identifier = str_replace(" ", "_", $sciname);                
        $dc_identifier = "txt_" . $taxon_identifier;    
            
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = $taxon_identifier;
        $taxon_parameters["kingdom"] = $kingdom;
        if($type==15)$taxon_parameters["family"] = $sciname;
        $taxon_parameters["genus"] = $genus;
        $taxon_parameters["scientificName"]= $sciname;        
        $taxon_parameters["source"] = $url;                                
        
        $arr_comname = array_keys($arr_name["$sciname"]);        
        $taxon_parameters["commonNames"] = array();
        foreach($arr_comname as $commonname)
        {            
            $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => $commonname, "language" => "en"));
        }                
        
        $used_taxa[$taxon_identifier] = $taxon_parameters;            
        
        

    }

    print "$wrap $wrap" . count($arr_name);
    print "<pre>";print_r($arr_name);print "</pre>";
    
    //exit;
    
}//end function process_file4($file)



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