<?php

require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$eol_site = "www.eol.org";
//$eol_site = "app1.eol.org";

$view = get_val_var('view');
if($view==""){$view=3;}


print"

<table cellpadding='1' cellspacing='0' border='0' style='font-size : small; font-family : Arial Unicode MS;'>
<tr><td>EoL Page Statistics 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>Beta Version</i>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font size='1'>";

if        ($view==1)    {print"View:&nbsp; Latest | <a href='display.php?view=2'>With label</a> | <a href='display.php?view=3'>History</a>";}
elseif    ($view==2)    {print"View:&nbsp; <a href='display.php?view=1'>Latest</a> | With label | <a href='display.php?view=3'>History</a>";}
elseif    ($view==3)    {print"View:&nbsp; <a href='display.php?view=1'>Latest</a> | <a href='display.php?view=2'>With label</a> | History";}

print" &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href='saved_stats.csv'>Download historical data as CSV</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href='../species_stat_marine/display.php'>Marine Stats &gt;&gt;</a>

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a target='cps' href='http://services.eol.org/eol_php_code/applications/partner_stat/index.php'>Content Partner Stats &gt;&gt;</a>

";

/*
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a target='google' href='http://services.eol.org/eol_php_code/applications/google_stats/index.php'>Google Stats &gt;&gt;</a>
*/



print"</font>
</td></tr>
</table>
<hr>";

//$qry="select * from page_stats_taxa where active = 'y' ";
//$qry="select * from page_stats_taxa where taxa_BHL_no_text > 0 order by date_created desc, time_created desc ";
$qry="select * from page_stats_taxa                            order by date_created desc ";
if($view != 3)  $qry .= "  limit 1";
else            $qry .= "  limit 7";
$sql = $mysqli->query($qry);

if($view != 3)
{

//$i=0;
while( $row = $sql->fetch_assoc() )
{
    //$i++;    
    //if($row["type"]=='taxa')
    //{

//===========================================================================================    

$s1="Total number of pages:";
$s2="Total number of pages with names from CoL:";
$s3="Total number of pages with names not in CoL:";
$s4="Pages with content:";
$s5="Pages with text: ";
$s6="Pages with images: ";
$s7="Pages with text and images: ";
$s8="Pages with images and no text: ";
$s9="Pages with text and no images: ";

//print "<hr>Taxa count = " . number_format($row["taxa_count"]) . " <hr>"; //exit;
//<tr><td>$s0</td><td align='right'>" . number_format($row["taxa_count"]-$row["no_vet_obj"]) . "</td>

print"
<table cellpadding='1' cellspacing='0' border='0' style='font-size : small; font-family : Arial Unicode MS;'>

<tr><td><b>Overall Statistics</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
<font size='1'><i>Run date: $row[date_created]</i></font>
</td></tr>";


$col_total         = $row["pages_incol"];
$notcol_total     = $row["pages_not_incol"];

print"
<tr><td>$s1</td><td align='right'>" . number_format($col_total + $notcol_total) . "</td></tr>
<tr><td>$s2</td><td align='right'>" . number_format($col_total) . "</td></tr>
<tr><td>$s3</td><td align='right'>" . number_format($notcol_total) . "</td></tr>


<tr><td>$s4</td><td align='right'>" . number_format($row["taxa_count"]) . "</td>";
    if($view==2)
    {print"
    <td>&nbsp;&nbsp;</td>
    <td><font size='1'><i>taxa are published, not superceded, not untrusted, with published data object</td>";
    }
print"    
</tr>

<tr><td>$s5</td><td align='right'>" . number_format($row["taxa_text"]) . "</td>";
    if($view==2)
    {print"
    <td>&nbsp;&nbsp;</td>
    <td><font size='1'><i>
    pages with published text</td>";
    }
print"
</tr>
<tr><td>$s6</td><td align='right'>" . number_format($row["taxa_images"]) . "</td>";
    if($view==2){print"
    <td>&nbsp;&nbsp;</td>
    <td><font size='1'><i>
    pages with published image</td>";
    }
print"
</tr>
<tr><td>$s7</td><td align='right'>" . number_format($row["taxa_text_images"]) . "</td></tr>
<tr><td>$s8</td><td align='right'>" . number_format($row["taxa_images_no_text"]) . "</td></tr>
<tr><td>$s9</td><td align='right'>" . number_format($row["taxa_text_no_images"]) . "</td></tr>

</table>
<hr>
";

//===========================================================================================
$s5="Number of pages with at least one vetted data object: ";
$s6="Number of taxa with no data objects (in CoL), i.e. 'base pages': ";
$s1="Number of pages with a CoL name and a vetted data object in one category: ";     
$s2="Number of non CoL pages with a vetted data object in one category: ";
$s3="Number of pages with a CoL name with vetted data objects in more than one category: ";
$s4="Number of non CoL pages taxa with vetted data objects in more than one category: ";


print"
<table cellpadding='1' cellspacing='0' border='0' style='font-size : x-small; font-family : Arial Unicode MS;'>
<tr><td><b>Vetted Content Statistics</b></td></tr>
<tr><td>$s5</td> <td align='right'>" . number_format($row["vet_obj"]) . "</td>                            <td align='center' ></td></tr>
<tr><td>$s6</td> <td align='right'>" . number_format($row["no_vet_obj2"]) . "</td>                        <td align='center' ></td></tr>
<tr><td>$s1</td> <td align='right'>" . number_format($row["vet_obj_only_1cat_inCOL"]) . "</td>            <td align='center' width='200'></td></tr>
<tr><td>$s2</td> <td align='right'>" . number_format($row["vet_obj_only_1cat_notinCOL"]) . "</td>        <td align='center' ></td></tr>
<tr><td>$s3</td> <td align='right'>" . number_format($row["vet_obj_morethan_1cat_inCOL"]) . "</td>        <td align='center' ></td></tr>
<tr><td>$s4</td> <td align='right'>" . number_format($row["vet_obj_morethan_1cat_notinCOL"]) . "</td>    <td align='center' ></td></tr>
</table>";


//===========================================================================================


/*

*/

$s1="Pages with BHL links: ";
$s2="Pages with BHL links with no text: ";
$s3="Pages with links and no text: ";

print"
<hr>
<table cellpadding='1' cellspacing='0' border='0' style='font-size : x-small; font-family : Arial Unicode MS;'>
<tr><td><b>BHL Statistics</b></td></tr>
<tr><td>$s1</td> <td align='right'>" . number_format($row["with_BHL"]) . "</td>";

if($view==2){print"
    <td>&nbsp;&nbsp;</td>
    <td><font size='1'><i>
    taxa are published, not superceded, not untrusted, with BHL links (not limited to the CoL 2008 hierarchy)</td>";
            }            
print"
</tr>
<tr><td>$s2</td><td align='right'>" . number_format($row["taxa_BHL_no_text"]) . "</td>
</tr>
<tr><td>$s3</td><td align='right'>" . number_format($row["taxa_links_no_text"]) . "</td>";
if($view==2){print"
    <td>&nbsp;&nbsp;</td>
    <td><font size='1'><i>
    taxa are published, not superceded, not untrusted, with outlinks, no text (not limited to the CoL 2008 hierarchy)</td>";
            }
print"
</tr>
</table>";

//===========================================================================================


$s1="Approved pages awaiting publication:";
$s2="Pages with CoL names with content that requires curation:";
$s3="Pages NOT with CoL names with content that requires curation:";


//<br><i><font size='2'>data object from harvests pending publication, where the taxon doesn't have any published=true data objects (i.e. pages that will become countable after publication)</i></font>
print"
<hr>
<table cellpadding='1' cellspacing='0' border='0' style='font-size : x-small; font-family : Arial Unicode MS;'>
<tr><td><b>Curatorial Statistics</b></td></tr>
<tr><td>$s1</td> <td align='right'>" . number_format($row["vetted_not_published"]) . "</td>";

if($view==2){print"
    <td>&nbsp;&nbsp;</td>
    <td><font size='1'><i>
    taxa with at least one 'vetted=trusted' 'published=false'";
            }
print"
</tr>
<tr><td>$s2</td> <td align='right'>" . number_format($row["vetted_unknown_published_visible_inCol"]) . "</td>
";
if($view==2){print"
    <td>&nbsp;&nbsp;</td>
    <td><font size='1'><i>
    taxa in CoL     which have *only* 'vetted=unknown' and 'published=true' and 'visibilty=visible' data objects (taxa can be either trusted or not)";
            }
print"
</tr>
<tr><td>$s3</td> <td align='right'>" . number_format($row["vetted_unknown_published_visible_notinCol"]) . "</td>
";
if($view==2){print"
    <td>&nbsp;&nbsp;</td>
    <td><font size='1'><i>
    taxa NOT in CoL which have *only* 'vetted=unknown' and 'published=true' and 'visibilty=visible' data objects (taxa can be either trusted or not)";
            }
print"

</tr>
</table> ";

//===========================================================================================
    //}//if($row["type"]=='taxa')
}//end while    


//$qry="select * from page_stats_dataobjects where active = 'y' ";
$qry="select * from page_stats_dataobjects order by date_created desc limit 1";


$sql = $mysqli->query($qry);
while( $row = $sql->fetch_assoc() )
{

        

    //if($row["type"]=='data object')
    //{

    
    
$s0="Total number of data objects:";
$s1="Number of unvetted but visible data objects:";
$s2="Number of data objects that are visible and not reliable:";
$s3="Number of hidden and unvetted data objects:";
$s4="Number of hidden and unreliable data objects:";

print"<hr>
<table cellpadding='1' cellspacing='0' border='0' style='font-size : x-small; font-family : Arial Unicode MS;'>
<tr><td>
<b>Data Object Statistics</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<font size='1'><i>Run date: $row[date_created] $row[time_created]</i></font>
</td></tr>

<tr><td>$s0</td> <td align='right'>" . number_format($row["taxa_count"]) . "</td>";
    if($view != 1)
    {print"
    <td>&nbsp;&nbsp;</td>
    <td><font size='1'><i>
    data object is published</td>";
    }
print"
</tr>

<tr><td>$s1</td> <td align='right'>" . number_format($row["vetted_unknown_published_visible_uniqueGuid"]) . "</td>
";
if($view==2){print"
    <td>&nbsp;&nbsp;</td><td><font size='1'><i>
    data objects with 'vetted=unknown'   and 'published=true' and 'visibilty=visible' and 'data_object.guid' is unique";    }
print"
</tr>
<tr><td>$s2</td> <td align='right'>" . number_format($row["vetted_untrusted_published_visible_uniqueGuid"]) . "</td>
";
if($view==2){print"
    <td>&nbsp;&nbsp;</td><td><font size='1'><i>
    data objects with 'vetted=untrusted' and 'published=true' and 'visibilty=visible' and 'data_object.guid' is unique";    }
print"
</tr>
<tr><td>$s3</td> <td align='right'>" . number_format($row["vetted_unknown_published_notVisible_uniqueGuid"]) . "</td>
";
if($view==2){print"
    <td>&nbsp;&nbsp;</td><td><font size='1'><i>
    data objects with 'vetted=unknown'   and 'published=true' and 'visibility=false' and 'data_object.guid' is unique";    }
print"
</tr>
<tr><td>$s4</td> <td align='right'>" . number_format($row["vetted_untrusted_published_notVisible_uniqueGuid"]) . "</td>
";
if($view==2){print"
    <td>&nbsp;&nbsp;</td><td><font size='1'><i>
    data objects with 'vetted=untrusted' and 'published=true' and 'visibility=false' and 'data_object.guid' is unique";    }
print"
</tr>
</table>
";    
    //}//if($row["type"]=='data object')
    
}//while
}//if($view != 3)

//==================================================================
//==================================================================
//==================================================================
if($view == 3)
{
    $arr = array();
    $arr2 = array();    //to store the autoctr field in page_stats
    $i=0;
    while( $row = $sql->fetch_assoc() )
    {
        $col_total        = $row["pages_incol"];
        $notcol_total     = $row["pages_not_incol"];

        $arr['Run date'][$i] = "$row[date_created]";        
        
        $arr['Total number of pages'][$i]                        = number_format($col_total + $notcol_total);
        $arr['Total number of pages with names from CoL'][$i]    = number_format($col_total);
        $arr['Total number of pages with names not in CoL'][$i]    = number_format($notcol_total);                
        $arr['Pages with content'][$i]                 = number_format($row["taxa_count"]);
        $arr['Pages with text'][$i]                 = number_format($row["taxa_text"]);
        $arr['Pages with images'][$i]                 = number_format($row["taxa_images"]);
        $arr['Pages with text and images'][$i]         = number_format($row["taxa_text_images"]);
        $arr['Pages with images and no text'][$i]     = number_format($row["taxa_images_no_text"]);
        $arr['Pages with text and no images'][$i]     = number_format($row["taxa_text_no_images"]);                
        $arr['Pages with links (specialist projects) and no text'][$i]        = number_format($row["taxa_links_no_text"]);        
        
        $arr['Number of pages with at least one vetted data object'][$i] = number_format($row["vet_obj"]);
        $arr['Number of taxa with no data objects (in CoL), i.e. base pages'][$i] = number_format($row["no_vet_obj2"]);
        $arr['Number of pages with a CoL name and a vetted data object in one category'][$i] = number_format($row["vet_obj_only_1cat_inCOL"]);
        $arr['Number of non CoL pages with a vetted data object in one category'][$i] = number_format($row["vet_obj_only_1cat_notinCOL"]);
        $arr['Number of pages with a CoL name with vetted data objects in more than one category'][$i] = number_format($row["vet_obj_morethan_1cat_inCOL"]);
        $arr['Number of non CoL pages taxa with vetted data objects in more than one category'][$i] = number_format($row["vet_obj_morethan_1cat_notinCOL"]);

        $arr['Pages with BHL links'][$i]                = number_format($row["with_BHL"]);        
        $arr['Pages with BHL links with no text'][$i]   = number_format($row["taxa_BHL_no_text"]);                

        $arr['Approved pages awaiting publication'][$i] = number_format($row["vetted_not_published"]);
        $arr['Pages with CoL names with content that requires curation'][$i]     = number_format($row["vetted_unknown_published_visible_inCol"]);
        $arr['Pages NOT with CoL names with content that requires curation'][$i] = number_format($row["vetted_unknown_published_visible_notinCol"]);
        
        $arr['Taxa pages'][$i] = number_format($row["lifedesk_taxa"]);
        $arr['Data objects'][$i] = number_format($row["lifedesk_dataobject"]);        
       

        $arr2['id'][$i] = $row["id"];    // this is the page_stats!id which is an autoctr
        $i++;
    }    
    
    $label = array_keys($arr);
    print"<table cellpadding='1' cellspacing='0' border='0' style='font-size : x-small; font-family : Arial Unicode MS;'>";



    for ($i = 0; $i < count($arr); $i++) 
    {    
        if($i==0)print"<tr><td><b>Overall Statistics</b></td></tr>";
        if($i==11)print"<tr><td>&nbsp;</td></tr><tr><td><b>Vetted Content Statistics</b></td></tr>";
        if($i==17)print"<tr><td>&nbsp;</td></tr><tr><td><b>BHL Statistics</b></td></tr>";
        if($i==19)print"<tr><td>&nbsp;</td></tr><tr><td><b>Curatorial Statistics</b></td></tr>";
        
        if($i==22)print"<tr><td>&nbsp;</td></tr><tr><td><b>LifeDesks (in EOL Content Partner Registry) Stats &nbsp;&nbsp;        
        <a target='lifedesk' href='moreinfo.php?on=lifedesk'>More info</a>
        
        </b></td></tr>";
        //<a target='lifedesk' href='index.php?group=5&f=results'>More info</a>
        
        
        if ($i % 2 == 0){$vcolor = 'white';}
        else            {$vcolor = '#ccffff';}        
        print "<tr bgcolor=$vcolor>";

        if($label[$i] != 'Run date')print "<td><a href='graph.php?title=$label[$i]'>$label[$i]</a></td>";
        else                        print "<td>$label[$i]</td>";
        for ($k = 0; $k < $sql->num_rows; $k++) 
        {


            $href1="";$href2="";
            if    (    
                    @$arr[$label[$i]][$k] > 0 and
                    (
                        ( $label[$i] == "Approved pages awaiting publication" and $arr['Run date'][$k] >= '2009-05-11' )
                        or
                        (
                            (
                            $label[$i] == "Pages with CoL names with content that requires curation"    or
                            $label[$i] == "Pages NOT with CoL names with content that requires curation"                                                
                            ) 
                        )    
                    )
                    and $k == 0
                )
            {    //and $arr['Run date'][$k] >= '2009-05-11'
                /*until we save the ids again Feb2
                $href1 = "<a href='details_do.php?autoctr=" . $arr2['id'][$k] . "&label=$label[$i]'>";
                $href2 = "</a>";
                */
            }

            //print "<td align='right'>" . @$arr[$label[$i]][$k] . "</td><td width='10'>&nbsp;</td>";
    
            if(@$arr[$label[$i]][$k] == 0)@$arr[$label[$i]][$k]=" -- ";
            
            print "<td align='right'>$href1";
            print @$arr[$label[$i]][$k];
            print "$href2</td><td width='10'>&nbsp;</td>";
            
            
        }
        print "</tr>";
    }    
    print"</table>";
    //exit;        
    
    //=================================================================================================
    //start data objects
    print"<hr>";
    $qry="select * from page_stats_dataobjects order by date_created desc limit 7";
    $sql = $mysqli->query($qry);
    $arr = array();
    $arr2 = array();    //to store the autoctr field in page_stats
    $i=0;
    while( $row = $sql->fetch_assoc() )
    {
        $arr['Run date'][$i] = "$row[date_created]";        

        $arr['Total number of data objects'][$i]                                 = number_format($row["taxa_count"]);
        
        //all 4 now have onclick
        $arr['Number of unvetted but visible data objects'][$i]                 = number_format($row["vetted_unknown_published_visible_uniqueGuid"]);
        $arr['Number of data objects that are visible and not reliable'][$i]     = number_format($row["vetted_untrusted_published_visible_uniqueGuid"]);
        $arr['Number of hidden and unvetted data objects'][$i]                     = number_format($row["vetted_unknown_published_notVisible_uniqueGuid"]);
        $arr['Number of hidden and unreliable data objects'][$i]                 = number_format($row["vetted_untrusted_published_notVisible_uniqueGuid"]);
        
        // /* not yet in table, temporarily commented
        $arr['Number of user submitted text data objects (published)'][$i]           = number_format($row["user_submitted_text"]);
        // */
        
        $arr2['id'][$i] = $row["id"];    // this is the page_stats!id which is an autoctr
        $i++;
    }    
    
    $label = array_keys($arr);
    print"<table cellpadding='1' cellspacing='0' border='0' style='font-size : x-small; font-family : Arial Unicode MS;'>";



    for ($i = 0; $i < count($arr); $i++) 
    {    
        if($i==0)print"<tr><td><b>Data Object Statistics</b>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;        
        <a target='more stats' href='moreinfo.php?on=dataobjects'>Detailed stats (2-3 mins.)</a>
        </td></tr>";
        //<a target='more stats' href='index.php?group=4&f=results'>More stats (2-3 mins.)</a>
        
        //if($i==10)
        //if($i==16)

    
        if ($i % 2 == 0){$vcolor = 'white';}
        else               {$vcolor = '#ccffff';}        
        print "<tr bgcolor=$vcolor>";

        print "<td>$label[$i]</td>";
        for ($k = 0; $k < $sql->num_rows; $k++) 
        {            
            $href1="";$href2="";
            if    (    (
                    
                    $label[$i] == "Number of unvetted but visible data objects"    or
                    $label[$i] == "Number of data objects that are visible and not reliable"    or
                    $label[$i] == "Number of hidden and unvetted data objects"                    or
                    $label[$i] == "Number of hidden and unreliable data objects"
                    )    and @$arr[$label[$i]][$k] > 0
                        and $k == 0
                        
                )//and $arr['Run date'][$k] >= '2009-05-11'                        
            {
                /* temp commented 
                $href1 = "<a href='details_do.php?autoctr=" . $arr2['id'][$k] . "&label=$label[$i]&what=data_object'>";
                $href2 = "</a>";
                */
            }
            print "<td align='right'>$href1";
            print @$arr[$label[$i]][$k];
            print "$href2</td><td width='10'>&nbsp;</td>";
        }
        print "</tr>";
    }    
    print"</table>";

    
    //end data objects
    //=================================================================================================
    
}//if($view == 3)
//==================================================================
//==================================================================
//==================================================================



$sql->close();


print "<hr> 
<table cellpadding='1' cellspacing='0' border='0' style='font-size : x-small; font-family : Arial Unicode MS;'><tr><td>
<font size='1'><i>
-- end --
</i></font>
</td></tr></table>
";


function get_val_var($v)
{
    if         (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif     (isset($_POST["$v"])){$var=$_POST["$v"];}
    
    if(isset($var)){return $var;}
    else    {return NULL;}    
}


?>


