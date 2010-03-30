<?php
/* connector for BOLD Systems 
estimated execution time: 
*/
$timestart = microtime(1);

//exit;
/*
2010Mar16   27,417

2010Mar30 114872 of 135176 Mastigoteuthis hjorti



http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=26136&iwidth=600
http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=111651&iwidth=600
http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=127144&iwidth=600
http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=1152&iwidth=600

Go to download page:
http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=279181
http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=26136
http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=93150

http://www.barcodinglife.org/views/taxbrowser.php?taxon=Gadus+morhua
http://www.barcodinglife.org/views/taxbrowser.php?taxon=Bimastos+welchi
http://www.barcodinglife.org/views/taxbrowser.php?taxon=Agaricus+pequinii

List of species per phylum
http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=Annelida
http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=Basidiomycota
http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=Chaetognatha
http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=Pyrrophycophyta

Get higher taxa:
http://www.boldsystems.org/views/taxbrowser.php?taxid=279181
http://www.boldsystems.org/views/taxbrowser.php?taxid=9

One set of URLs:
http://www.boldsystems.org/views/taxbrowser.php?taxid=195548
http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=195548
http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=195548&iwidth=600


date            taxid   with public barcode     with barcodes
2010 Jan 20     16105   5594    
2010 Mar 01     60749                           60749
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];


//only on local; to be deleted before going into production
/*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
exit;
*/
$wrap = "\n";
//$wrap = "<br>";

$phylum_service_url = "http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=";
//$species_service_url = "http://www.barcodinglife.org/views/taxbrowser.php?taxon="; //no longer working
$species_service_url = "http://www.boldsystems.org/views/taxbrowser.php?taxid=";


    //$species_group="Animals";
    
    //$species_group="Fungi"; //running...
    //$species_group="Plants";    //running...
    //$species_group="Protists";  //running... done

    //$species_group="Animals_1";    
    //$species_group="Animals_Arthropoda";    
    
    $species_group="Animals_2";    
    //$species_group="Animals_Echinodermata";    
    
    //$species_group="Animals_3";    //running...
    //$species_group="Animals_4";    //running... done
    
    print "$species_group $wrap";
    $txt_file="bold_id_list.txt";
    $txt_file="bold_id_list_" . $species_group . ".txt";

//********************************************************************************
// /* can be commented if TXT file has already been created
 
    $main_name_id_list=array();
    get_BOLD_taxa();//this will save to /files/$txt_file the id and sciname
    exit("<hr>TXT file saved -- $species_group.");
// */
//********************************************************************************

$main_name_id_list=array();
$main_name_id_list=get_from_txt();//this will retrieve the id and sciname from txt file

//exit;
// */
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

//$resource = new Resource(81); 
//print "<hr>resource id = " . $resource->id; exit;

$schema_taxa = array();
$used_taxa = array();

$id_list=array();

/* no longer being used
$query="Select distinct names.`string` as taxon_phylum From hierarchy_entries Inner Join ranks ON hierarchy_entries.rank_id = ranks.id
Inner Join names ON hierarchy_entries.name_id = names.id Where
ranks.id = 280  ";
//rank.id 280 = phylum
//$query .= " and names.`string` = 'Chordata' ";
//$query .= " and names.`string` = 'Chaetognatha' ";
//$query .= " and names.`string` = 'Pyrrophycophyta' ";
//$query .= " and names.`string` <> 'Annelida' ";
//$query .= " Order By names.`string` Asc ";
//$query .= " limit 1 ";
//print"<hr>$query<hr>";
$result = $mysqli->query($query);    
print "phylum count = " . $result->num_rows . "$wrap"; //exit;
$phylum_count = $result->num_rows;
*/


$total_taxid_count = 0;
$do_count = 0;//weird but needed here
$ctr=0;
$id_with_public_barcode=array();
//while($row=$result->fetch_assoc())     
//{
    $taxid_count=0;
    $taxid_count_with_barcode=0;        
    $ctr++;               
    
    /* $url = $phylum_service_url . trim($row["taxon_phylum"]);    */
    /* for debug - to limit no. of record to process
    $url = "http://127.0.0.1/bold2.xml";
    */    
    //if(!($xml = @simplexml_load_file($url)))continue;        
    $do_count = 0;
    $count_per_phylum=0;
    
    
    //foreach($xml->taxon as $main)
    foreach($main_name_id_list as $main)
    {     
        $count_per_phylum++;                  
        /*        
        print "$wrap $ctr of $phylum_count -- phylum = " . $row["taxon_phylum"];
        print " | $count_per_phylum of " . count($xml->taxon) . " $main->name  $wrap";
        */
        print "$wrap $count_per_phylum of " . count($main_name_id_list) . " " . $main["name"];
        
        
        //if($taxid_count > 15)continue;   //debug - to limit no. of taxa to process
        
        /*
        if(in_array($main->name, array("Prorocentrum cassubicum","Gymnodinium catenatum")))
        {print "<br>[" . $main->name . "]";}
        else continue;  
        */
        //debug to limit
        
        //===================================================================
        
        $arr = get_higher_taxa($main["id"]);
        $taxa = @$arr[0];
        $bold_stats = @$arr[1];
        $species_level = @$arr[2];
        $with_dobjects = @$arr[3];
        
        if(!$taxa and !$bold_stats and !$species_level and !$with_dobjects)continue;
        
        print"<pre>";print_r($taxa);print"</pre>";
        
        //===================================================================// check if there is content
        //$dc_source = $species_service_url . urlencode($main->name);                            
        $dc_source = $species_service_url . urlencode($main["id"]);                                    
        //$description=check_if_with_content($main["id"],$dc_source,$main->barcodes);
        $description=check_if_with_content($main["id"],$dc_source,1);
        if(!$description and !$taxa)continue;
        //===================================================================
    
        if(in_array($main["id"], $id_list)) continue;
        else $id_list[]=$main["id"];
    
        $taxid_count++;        
        
        //start #########################################################################  

        //if(intval($main->public_barcodes > 0))
        //if(intval($main->barcodes) > 0)
        if(true)
        {
            $id_with_public_barcode[]=$main["id"];
            $taxid_count_with_barcode++;
            
            // start comment here to just see count  /*            
            //start taxon part
            
            $taxon = str_replace(" ", "_", $main["name"]);
            
            
            if(@$used_taxa[$taxon])
            {
                $taxon_parameters = $used_taxa[$taxon];
            }
            else
            {
                
                $taxon_parameters = array();
                $taxon_parameters["identifier"] = $main["id"];

                $taxon_parameters["kingdom"] = @$taxa["kingdom"];
                $taxon_parameters["phylum"]  = @$taxa["phylum"];
                $taxon_parameters["class"]   = @$taxa["class"];
                $taxon_parameters["order"]   = @$taxa["order"];
                $taxon_parameters["family"]  = @$taxa["family"];
                $taxon_parameters["genus"]   = @$taxa["genus"];
                
                $taxon_parameters["scientificName"]= $main["name"];
                //$taxon_parameters["source"] = $species_service_url . urlencode($main["name"]);
                $taxon_parameters["source"] = $species_service_url . urlencode($main["id"]);
            
                $used_taxa[$taxon] = $taxon_parameters;            
            }            
            //end taxon part            

        if($with_dobjects)//this is synonymous to if id/url is resolvable
        {
        
            
            //1st text object
            if($description)
            {
                $do_count++;
                $title = "Barcode data";
                
                //$data_object_parameters = get_data_object($main["id"],$do_count,$dc_source,$main->barcodes,$description,$title);                   
                $data_object_parameters = get_data_object($main["id"],$do_count,$dc_source,1,$description,$title);                   
                
                $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);         
            }

            //another text object
            if($bold_stats)
            {
                $do_count++;                
                $description="Barcode of Life Data Systems (BOLD) Stats <br> $bold_stats";
                $title="Statistics of barcoding coverage";
                //$data_object_parameters = get_data_object($main["id"],$do_count,$dc_source,$main->barcodes,$description,$title);                   
                $data_object_parameters = get_data_object($main["id"],$do_count,$dc_source,1,$description,$title);                   
                $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);         
            }            
            
            //another text object
            $map_url = "http://www.boldsystems.org/lib/gis/mini_map_500w_taxonpage_occ.php?taxid=" . $main["id"];
            print"$wrap <a href='$map_url'>$map_url</a>";            
            if(url_exists($map_url))
            {
                $do_count++;                
                $description="Collection Sites: world map showing specimen collection locations for <i>" . $main["name"] . "</i> <div style='font-size : x-small;overflow : scroll;'> <img border='0' src='$map_url'> </div> ";
                
                $title="Locations of barcode samples";
                //$data_object_parameters = get_data_object($main["id"],$do_count,$dc_source,$main->barcodes,$description,$title);                   
                $data_object_parameters = get_data_object($main["id"],$do_count,$dc_source,1,$description,$title);                   
                $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);         
            }            
        
        }//if($taxa)//this is synonymous to if id/url is resolvable
                              
            
            

            $used_taxa[$taxon] = $taxon_parameters;
            // end comment here to just see count */
            
        }//with public barcodes
        
        //end #########################################################################        
    }
    if($taxid_count != 0)
    {
        echo "$wrap total=" . $taxid_count;
        echo "$wrap with barcode=" . $taxid_count_with_barcode;
        $total_taxid_count += $taxid_count;
    }
//}//end main loop

echo "$wrap$wrap total taxid = " . $total_taxid_count . " = " . count($id_list);
echo "$wrap$wrap total ids with public barcode = " . count($id_with_public_barcode);

foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
//$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "BOLD_" . $species_group . ".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              $wrap";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   $wrap";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr $wrap";

exit("$wrap$wrap Done processing - $species_group ");
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################


function get_phylum_list()
{
    global $wrap;
    $str = Functions::get_remote_file("http://www.boldsystems.org/views/taxbrowser_root.php");        
    $beg='Animals:'; $end1='>Barcodes :'; 
    $str = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
    //print "<hr>$str";
    
    $arr = get_name_id_from_array($str);
    print"<hr>All Phylum: " . count($arr) . "<pre>";print_r($arr);print"</pre>";              
    return $arr;    
}
function get_BOLD_taxa()
{
    global $main_name_id_list;
    global $species_group;
    
     /* normal operation
    $arr_phylum = get_phylum_list();
     */
    
    /* debug
    $arr_phylum = array(0 => array( "name" => "Brachiopoda" , "id" => 9),
                        1 => array( "name" => "Bryozoa"    , "id" => 7),
                        3 => array( "name" => "Xenoturbellida" , "id" => 88647)
                       );
    */    
    

    //Animals
    if($species_group=="Animals")
    {
    $arr_phylum = array(0 => array( "name" => "Acanthocephala"  , "id" => 11),
                        1 => array( "name" => "Annelida"        , "id" => 2),
                        2 => array( "name" => "Arthropoda"      , "id" => 20),
                        3 => array( "name" => "Brachiopoda"     , "id" => 9),
                        4 => array( "name" => "Bryozoa"         , "id" => 7),
                        5 => array( "name" => "Chaetognatha"    , "id" => 13),
                        6 => array( "name" => "Chordata"        , "id" => 18),
                        7 => array( "name" => "Cnidaria"        , "id" => 3),
                        8 => array( "name" => "Cycliophora"     , "id" => 79455),
                        9 => array( "name" => "Echinodermata"   , "id" => 4),
                        10 => array( "name" => "Echiura"         , "id" => 27333),
                        11 => array( "name" => "Gnathostomulida" , "id" => 78956),
                        12 => array( "name" => "Hemichordata"    , "id" => 21),
                        13 => array( "name" => "Mollusca"        , "id" => 23),
                        14 => array( "name" => "Nematoda"        , "id" => 19),
                        15 => array( "name" => "Onychophora"     , "id" => 10),
                        16 => array( "name" => "Platyhelminthes" , "id" => 5),
                        17 => array( "name" => "Pogonophora"     , "id" => 28524),
                        18 => array( "name" => "Porifera"        , "id" => 24818),
                        19 => array( "name" => "Rotifera"        , "id" => 16),
                        20 => array( "name" => "Sipuncula"       , "id" => 15),
                        21 => array( "name" => "Tardigrada"      , "id" => 26033),
                        22 => array( "name" => "Xenoturbellida"  , "id" => 88647)
                       );
    }

    if($species_group=="Animals_1")
    {
    $arr_phylum = array(0 => array( "name" => "Acanthocephala"  , "id" => 11),
                        1 => array( "name" => "Annelida"        , "id" => 2),                        
                        2 => array( "name" => "Brachiopoda"     , "id" => 9),
                        3 => array( "name" => "Bryozoa"         , "id" => 7)
                       );                        
    }
    if($species_group=="Animals_Arthropoda")
    {
    $arr_phylum = array(0 => array( "name" => "Arthropoda"      , "id" => 20)
                       );                        
    }
    
    if($species_group=="Animals_2")
    {
    $arr_phylum = array(0 => array( "name" => "Chaetognatha"    , "id" => 13),
                        1 => array( "name" => "Chordata"        , "id" => 18),
                        2 => array( "name" => "Cnidaria"        , "id" => 3),
                        3 => array( "name" => "Cycliophora"     , "id" => 79455)
                       );
    }

    if($species_group=="Animals_Echinodermata")
    {
    $arr_phylum = array(0 => array( "name" => "Echinodermata"   , "id" => 4)    
                       );
    }
    
    
    
    if($species_group=="Animals_3")
    {
    $arr_phylum = array(0 => array( "name" => "Echiura"         , "id" => 27333),
                        1 => array( "name" => "Gnathostomulida" , "id" => 78956),
                        2 => array( "name" => "Hemichordata"    , "id" => 21),
                        3 => array( "name" => "Mollusca"        , "id" => 23),
                        4 => array( "name" => "Nematoda"        , "id" => 19)
                       );
    }
    if($species_group=="Animals_4")
    {
    $arr_phylum = array(0 => array( "name" => "Onychophora"     , "id" => 10),
                        1 => array( "name" => "Platyhelminthes" , "id" => 5),
                        2 => array( "name" => "Pogonophora"     , "id" => 28524),
                        3 => array( "name" => "Porifera"        , "id" => 24818),
                        4 => array( "name" => "Rotifera"        , "id" => 16),
                        5 => array( "name" => "Sipuncula"       , "id" => 15),
                        6 => array( "name" => "Tardigrada"      , "id" => 26033),
                        7 => array( "name" => "Xenoturbellida"  , "id" => 88647)
                       );
    }


    //Fungi 
    if($species_group=="Fungi")
    {
    $arr_phylum = array(0 => array( "name" => "Ascomycota"      , "id" => 34),
                        1 => array( "name" => "Basidiomycota"   , "id" => 23675),
                        2 => array( "name" => "Chytridiomycota" , "id" => 23691),
                        3 => array( "name" => "Myxomycota"      , "id" => 83947),
                        4 => array( "name" => "Zygomycota"      , "id" => 23738)
                       );                        
    }
    
    //Plants 
    if($species_group=="Plants")
    {
    $arr_phylum = array(0 => array( "name" => "Bryophyta"           , "id" => 176192),
                        1 => array( "name" => "Chlorarachniophyta"  , "id" => 109954),
                        2 => array( "name" => "Chlorophyta"         , "id" => 112296),
                        3 => array( "name" => "Lycopodiophyta"      , "id" => 38696),
                        4 => array( "name" => "Magnoliophyta"       , "id" => 12),
                        5 => array( "name" => "Pinophyta"          , "id" => 251587),
                        6 => array( "name" => "Pteridophyta"       , "id" => 38074),
                        7 => array( "name" => "Pyrrophycophyta"    , "id" => 91354),
                        8 => array( "name" => "Rhodophyta"         , "id" => 48327),
                        9 => array( "name" => "Stramenopiles"      , "id" => 109924)
                       );    
    }
    
    //Protists                        
    if($species_group=="Protists")
    {
    $arr_phylum = array(0 => array( "name" => "Bacillariophyta"    , "id" => 74445),
                        1 => array( "name" => "Ciliophora"         , "id" => 72834),
                        2 => array( "name" => "Dinozoa"            , "id" => 70855),
                        3 => array( "name" => "Heterokontophyta"   , "id" => 53944),
                        4 => array( "name" => "Opalozoa"           , "id" => 72171),                        
                        5 => array( "name" => "Straminipila"       , "id" => 23715)
                       );
    }    
    
    /* debug
    $arr_phylum = array(0 => array( "name" => "Xenoturbellida"  , "id" => 88647)
                       );
    */    


    $arr=proc_phylum($arr_phylum);                
    $main_name_id_list = array_merge($arr_phylum,$arr);    
    print"<hr>All Taxa in BOLD: " . count($main_name_id_list);
    print"<pre>";print_r($main_name_id_list);print"</pre>";              
    save_to_txt($main_name_id_list);    
    //exit;
}

function save_to_txt($arr)
{
    global $txt_file;
    
	$str="";        
	//for ($i = 0; $i < count($arr); $i++) 		
    foreach ($arr as $value)
	{
		$str .= $value["id"] . "\t" . $value["name"] . "\n";    //"\t" is tab
	}  
	$filename = "files/" . $txt_file;
	if($fp = fopen($filename,"w+")){fwrite($fp,$str);fclose($fp);}		    
    return "";    
}//function save_to_txt2

function proc_phylum($arr)
{   
    global $species_service_url;    
    global $main_name_id_list;
    global $wrap;
    

    foreach ($arr as $a)//phylum loop
    {
        print $wrap . $a["name"] . " -- " . $a["id"];
        $str = Functions::get_remote_file($species_service_url . $a["id"]);        
        $arr2 = proc_subtaxa_block($str);        
        //print"<pre>";print_r($arr2);print"</pre>";               
        $main_name_id_list = array_merge($main_name_id_list, $arr2);                
        foreach ($arr2 as $a2)//class loop
        {
            print $wrap . $a2["name"] . " -- " . $a2["id"];
            $str = Functions::get_remote_file($species_service_url . $a2["id"]);        
            $arr3 = proc_subtaxa_block($str);        
            //print"<pre>";print_r($arr3);print"</pre>";               
            $main_name_id_list = array_merge($main_name_id_list, $arr3);                
            foreach ($arr3 as $a3)//order loop
            {
                print $wrap . $a3["name"] . " -- " . $a3["id"];
                $str = Functions::get_remote_file($species_service_url . $a3["id"]);        
                $arr4 = proc_subtaxa_block($str);        
                //print"<pre>";print_r($arr4);print"</pre>";               
                $main_name_id_list = array_merge($main_name_id_list, $arr4);                            
                foreach ($arr4 as $a4)//family loop
                {
                    print $wrap . $a4["name"] . " -- " . $a4["id"];
                    $str = Functions::get_remote_file($species_service_url . $a4["id"]);        
                    $arr5 = proc_subtaxa_block($str);        
                    //print"<pre>";print_r($arr5);print"</pre>";               
                    $main_name_id_list = array_merge($main_name_id_list, $arr5);                                            
                    foreach ($arr5 as $a5)//subfamily if there is any or Genus loop
                    {                        
                        print $wrap . $a5["name"] . " -- " . $a5["id"];
                        $str = Functions::get_remote_file($species_service_url . $a5["id"]);        
                        $arr6 = proc_subtaxa_block($str);        
                        //print"<pre>";print_r($arr6);print"</pre>";               
                        $main_name_id_list = array_merge($main_name_id_list, $arr6);                                            
                        foreach ($arr6 as $a6)//Genus if there was subfamily above loop
                        {
                            print $wrap . $a6["name"] . " -- " . $a6["id"];
                            $str = Functions::get_remote_file($species_service_url . $a6["id"]);        
                            $arr7 = proc_subtaxa_block($str);        
                            //print"<pre>";print_r($arr7);print"</pre>";               
                            $main_name_id_list = array_merge($main_name_id_list, $arr7);
                        }                            
                    }                    
                }                
            }               
        }                   
    }   
    return $main_name_id_list;     
}
function proc_subtaxa_block($str)
{
    $beg='<h2>Sub-taxa</h2>'; $end1='</ul>'; 
    $str = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
        //side script check if        
        $pos = stripos($str,"Species List - Progress");    
        if(is_numeric($pos)){print" -stop here- "; return array();}    
    
 
    $final = get_name_id_from_array($str);
    return $final;    
}
function get_name_id_from_array($str)
{
    $str = strip_tags($str,"<a>");    
    $str = str_ireplace('<a' , 'xxx<a', $str);	
    $str = str_ireplace('xxx' , "&arr[]=", $str);	
    $arr=array(); parse_str($str);	    
    //print"<pre>";print_r($arr);print"</pre>";

    $final=array();
    foreach ($arr as $a)
    {
        $name = "xxx" . get_str_from_anchor_tag($a);        
        $beg='xxx'; $end1='['; //to remove "[number]"
        $name = trim(parse_html($name,$beg,$end1,$end1,$end1,$end1,""));            
        //print $name . " -- ";
        
        $id = get_href_from_anchor_tag($a)."xxx";
        $beg='taxid='; $end1='xxx'; 
        $id = trim(parse_html($id,$beg,$end1,$end1,$end1,$end1,""));            
        //print $id . "<br>";
        
        $final[]=array("name" => $name, "id" => $id);
    }   
    //print"<pre>";print_r($final);print"</pre>";    
    return $final;
}

function get_href_from_anchor_tag($str){$beg='href="'; $end1='"';$temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));return $temp;}


function get_higher_taxa($taxid)
{
    /* this function will get:
        taxonomy 
        BOLD stats 
        boolean if species-level taxa 
        if id/url is resolvable
    */
    global $wrap;
    /*
    <span class="taxon_name">Aphelocoma californica PS-1 {species}&nbsp;
        <a title="phylum"href="taxbrowser.php?taxid=18">Chordata</a>; 
        <a title="class"href="taxbrowser.php?taxid=51">Aves</a>; 
        <a title="order"href="taxbrowser.php?taxid=321">Passeriformes</a>; 
        <a title="family"href="taxbrowser.php?taxid=1160">Corvidae</a>; 
        <a title="genus"href="taxbrowser.php?taxid=4698">Aphelocoma</a>;     
    </span>    

    <span class="taxon_name">Gastrolepidia {genus}&nbsp;
        <a title="phylum"href="taxbrowser.php?taxid=2">Annelida</a>; 
        <a title="class"href="taxbrowser.php?taxid=24489">Polychaeta</a>; 
        <a title="order"href="taxbrowser.php?taxid=25265">Phyllodocida</a>; 
        <a title="family"href="taxbrowser.php?taxid=28521">Polynoidae</a>;
    </span>
    
    */
    $arr = array();

    $file="http://www.boldsystems.org/views/taxbrowser.php?taxid=" . $taxid;
    $orig_str = Functions::get_remote_file($file);        
        //side script - to check if id/url is even resolvable
        $pos = stripos($orig_str,"fatal error");    
        if(is_numeric($pos)){print" -fatal error found- "; return array(false,false,false,false);}
 
        //print"$orig_str"; exit;

        
    $str = $orig_str;
    $beg='taxon_name">'; $end1='</span>'; 
    $str = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
    
        //side script to check if species level taxa
        $pos = stripos($str,"{species}");    
        if(is_numeric($pos)){$species_level=true;}
        else                {$species_level=false;}           
        
    
    //print"$str";
    $str = str_ireplace('<a title=' , 'xxx<a title=', $str);	
    $str = str_ireplace('</a>' , '</a>yyy', $str);	    
    $str = str_ireplace('xxx' , "&arr[]=", $str);	
    $arr=array();	
    parse_str($str);	
    //print "after parse_str recs = " . count($arr) . "$wrap $wrap";	           
    //print"<pre>";print_r($arr);print"</pre>";
    $taxa=array();    
    foreach ($arr as $a)
    {
        $index = get_title_from_anchor_tag($a);
        $taxa["$index"] = get_str_from_anchor_tag($a);
    }
    
    //=========================================================================//start get BOLD stats
    $beg='<h2>BOLD Stats</h2>'; $end1='</table>';     
    $str = trim(parse_html($orig_str,$beg,$end1,$end1,$end1,$end1,""));            
    $str = strip_tags($str,"<tr><td><table>");
    $str = str_ireplace('width="100%"',"",$str);    
    $pos = stripos($str,"Species List - Progress");    
    $str = substr($str,0,$pos) . "</td></tr></table>";    
    print"$wrap $str";
    //$str is BOLD stats
    //=========================================================================
    
    $arr=array($taxa,$str,$species_level,true);
    return $arr;
}

function get_str_from_anchor_tag($str)
{   $beg='">'; $end1='</a>';
    $temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));return $temp;}
function get_title_from_anchor_tag($str){$beg='<a title="'; $end1='"';$temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));return $temp;}

function check_if_with_content($taxid,$dc_source,$public_barcodes)
{
    global $wrap;
    global $species_level;
    
    /*            
    Ratnasingham S, Hebert PDN. Compilers. 2009. BOLD : Barcode of Life Data System.
    World Wide Web electronic publication. www.boldsystems.org, version (08/2009). 
    */
    
    //start get text dna sequece
    $src = "http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=" . $taxid . "&iwidth=400";
    if($species_level)
    {
        if(barcode_image_available($src))        
        {
            $description = "
            The following is a representative barcode sequence, the centroid of all available sequences for this species.    
            <br><a target='barcode' href='$src'><img src='$src' height=''></a>";
        }
        else $description = "Barcode image not yet available.";
        
        $description .= "<br>&nbsp;<br>";
    }
    else $description = "";
    //else $description = "Barcode image only available of species-level taxa";

    
if($species_level)
{
    if($public_barcodes > 0)
    {    
        $url = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";
        $arr = get_text_dna_sequence($url);
        $count_sequence     = $arr[0];
        $text_dna_sequence  = $arr[1];
        $url_fasta_file     = $arr[2];        
        print "$wrap [$public_barcodes]=[$count_sequence] $wrap ";        
        $str="";        
        if($count_sequence > 0)
        {
            if($count_sequence == 1)$str="There is 1 barcode sequence available from BOLD and GenBank. 
                                    Below is the sequence of the barcode region Cytochrome oxidase subunit 1 (COI or COX1) from a member of the species. 
                                    See the <a target='BOLDSys' href='$dc_source'>BOLD taxonomy browser</a> for more complete information about this specimen. 
                                    Other sequences that do not yet meet barcode criteria may also be available.";
                                    
            else                    $str="There are $count_sequence barcode sequences available from BOLD and GenBank. 
                                    Below is a sequence of the barcode region Cytochrome oxidase subunit 1 (COI or COX1) from a member of the species. 
                                    See the <a target='BOLDSys' href='$dc_source'>BOLD taxonomy browser</a> for more complete information about this specimen and other sequences.";

            $str .= "<br>&nbsp;<br>";                
            $text_dna_sequence .= "<br>-- end --<br>";                
        }

    }
    else $text_dna_sequence = "";    

    //
    if(trim($text_dna_sequence) != "")
    {
        $temp = "$str ";
        $temp .= "<div style='font-size : x-small;overflow : scroll;'> $text_dna_sequence </div>";
        /* one-click         
        $url_fasta_file = "http://services.eol.org/eol_php_code/applications/barcode/get_text_dna_sequence.php?taxid=$taxid";
        */
        
        /* 2-click per PL advice */
        $url_fasta_file = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";        
        $temp .= "<br><a target='fasta' href='$url_fasta_file'>Download FASTA File</a>";
    }
    else 
    {
        $temp = "No available public DNA sequences <br>";     
        return false;
    }   
}//if($species_level)
else
{
    /* 2-click per PL advice */
    $url_fasta_file = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";        
    $temp = "<a target='fasta' href='$url_fasta_file'>Download FASTA File</a>";    
}

    $description .= $temp;
    
    //end get text dna sequence
    return $description;    
}

function barcode_image_available($src)
{
    $str = Functions::get_remote_file($src);            
    
    /*
    ERROR: Only species level taxids are accepted
    ERROR: Unable to retrieve sequence
    */
    
    $ans = stripos($str,"ERROR:");
    
    if(is_numeric($ans))return false;
    else                return true;
}


function get_data_object($taxid,$do_count,$dc_source,$public_barcodes,$description,$title=NULL)
{        
    $dataObjectParameters = array();    
        
    $dataObjectParameters["title"] = $title;        
    
    $dataObjectParameters["description"] = $description;    
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;        
    $dataObjectParameters["identifier"] = $taxid . "_" . $do_count;
    //$dataObjectParameters["rights"] = "Copyright 2009 - partner name";
    $dataObjectParameters["rightsHolder"] = "Barcode of Life Data Systems";
    if(true)
    {
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#MolecularBiology";
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
    }    
    /*
    $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";    
    $dataObjectParameters["mimeType"] = "image/png";
    */    
    $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";    
    $dataObjectParameters["mimeType"] = "text/html";        
    $dataObjectParameters["language"] = "en";
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by/3.0/";
    //$dataObjectParameters["thumbnailURL"] = "";
    //$dataObjectParameters["mediaURL"] = "http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=" . $taxid . "&iwidth=600";    
    $dataObjectParameters["source"] = $dc_source;
    
    //==========================================================================================
    $agent = array(0 => array(     "role" => "compiler" , "homepage" => "http://www.boldsystems.org/" , "Sujeevan Ratnasingham"),
                   1 => array(     "role" => "compiler" , "homepage" => "http://www.boldsystems.org/" , "Paul D.N. Hebert")
                    );
    
    $agents = array();
    foreach($agent as $agent)
    {  
        $agentParameters = array();
        $agentParameters["role"]     = $agent["role"];
        $agentParameters["homepage"] = $agent["homepage"];
        $agentParameters["logoURL"]  = "";        
        $agentParameters["fullName"] = $agent[0];
        $agents[] = new SchemaAgent($agentParameters);
    }
    $dataObjectParameters["agents"] = $agents;    
    //==========================================================================================
    $audience = array(  0 => array("Expert users"),
                        1 => array("General public")
                     );        
    $audiences = array();
    foreach($audience as $audience)
    {  
        $audienceParameters = array();
        $audienceParameters["label"]    = $audience[0];
        $audiences[] = new SchemaAudience($audienceParameters);
    }
    $dataObjectParameters["audiences"] = $audiences;    
    //==========================================================================================
    return $dataObjectParameters;
}
function get_text_dna_sequence($url)
{
    set_time_limit(0);
    ini_set('memory_limit','3500M');

    //$str = get_file_contents($url); //print $str;  
    $str = Functions::get_remote_file($url);    
    
    $beg='../temp/'; $end1='fasta.fas'; $end2="173xxx"; $end3="173xxx";			
    $folder = parse_html($str,$beg,$end1,$end2,$end3,$end3,"");	        

    $str="";    
    if($folder != "")
    {
        $url="http://www.boldsystems.org/temp/" . $folder . "/fasta.fas";
        //$str = get_file_contents($url);
        $str = Functions::get_remote_file($url);
    }    
        
    
    //start get only 2 sequence 
    /* working but we will not get the first 2 sequence anymore
    if($str)
    {   $found=0;
        $str=trim($str);
        for ($i = 0; $i < strlen($str); $i++) 
        {
            if(substr($str,$i,1) == ">")$found++;
            if($found == 3)break;
        }
        $str = substr($str,0,$i-1);    
    }
    */
    //end get only 2 sequence
    
    $count_sequence = substr_count($str, '>');    
    //start get the single sequence = longest, with least N char
    $best_sequence = get_best_sequence($str);    
    //end    
 
    $arr=array();
    $arr[]=$count_sequence;
    $arr[]=$best_sequence;   
    $arr[]=$url;
    return $arr;
}
// /*
function get_file_contents($url)
{
    $handle = fopen($url, "r");	
    $contents = '';
    if ($handle)
    {        
      	while (!feof($handle)){$contents .= fread($handle, 8192);}
       	fclose($handle);	
    }     
    return $contents;
}
// */        
function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL)	//str = the html block
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
		//if(substr($str,$i,$beg_len) == $beg)
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

/*
$taxid="26136";
$taxid="24493";
$taxid="32306"; //just 1 sequence
$url = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";
$str = Functions::get_remote_file($url);     
$beg='../temp/'; $end1='fasta.fas'; $end2="173xxx"; $end3="173xxx";			
$folder = parse_html($str,$beg,$end1,$end2,$end3,$end3,"");	        
$str="";    
if($folder != "")
{
    $url="http://www.boldsystems.org/temp/" . $folder . "/fasta.fas";
    //$str = get_file_contents($url);
    $str = Functions::get_remote_file($url);    
    $best_sequence = get_best_sequence($str);
}    
exit("<hr>$best_sequence<hr>");
*/

function get_best_sequence($str)
{
    set_time_limit(0);
    ini_set('memory_limit','3500M');

    $str = str_ireplace('>' , '&arr[]=', $str);	
    $arr=array();	
    parse_str($str);	
    //print "after parse_str recs = " . count($arr) . "<br>";	//print_r($arr);
    
    if(count($arr)>0)
    {
        $biggest=0;
        $index_with_longest_txt=0;
        for ($i = 0; $i < count($arr); $i++) 
        {
            //$dna=trim($dna);
            $dna = trim($arr[$i]);
            //print "$dna ";
            $pos = strrpos($dna,"|");
            //print "[$pos]<br>" ;
            $new_dna = trim(substr($dna,$pos+1,strlen($dna)));        
            $new_dna = str_ireplace(array("-", " "), "", $new_dna);                
            $len_new_dna = strlen($new_dna);
            //print "[$new_dna]<br>[" . $len_new_dna . "]" ;
            //print "<hr>";       
            if($biggest < $len_new_dna)
            {
                $biggest = $len_new_dna;
                $index_with_longest_txt=$i;
            }
        }    
        //print"<hr><hr>biggest = $biggest [$arr[$index_with_longest_txt]]";
        return $arr[$index_with_longest_txt];    
    }    
    else return "";
}

function url_exists($url) {

    set_time_limit(0);
    ini_set('memory_limit','3500M');


    /*
    $resURL = curl_init();
    curl_setopt($resURL, CURLOPT_URL, $strURL);
    curl_setopt($resURL, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($resURL, CURLOPT_HEADERFUNCTION, 'curlHeaderCallback');
    curl_setopt($resURL, CURLOPT_FAILONERROR, 1);
    //curl_exec ($resURL);
    $intReturnCode = curl_getinfo($resURL, CURLINFO_HTTP_CODE);
    curl_close ($resURL);
    */
    

    $ch = curl_init();  
    curl_setopt($ch,CURLOPT_URL,$url);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // not to display the post submission
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);  
    $output = curl_exec($ch);
    $intReturnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);

    
    if ($intReturnCode != 200 && $intReturnCode != 302 && $intReturnCode != 304) return false;
    else                                                                         return true ;
} 

function get_from_txt()
{
    global $txt_file;
    $filename = "files/" . $txt_file;
    $fd = fopen ($filename, "r");
    $contents = fread ($fd,filesize ($filename));    
    fclose ($fd);
    
    $delimiter = "\n";
    $splitcontents = explode($delimiter, $contents);
    $counter = "";
    //echo $contents;
    
    $arr=array();
    foreach ( $splitcontents as $value )
    {    
        $counter = $counter+1;
        //echo "<b>Split $counter: </b> $value <br>";        
        if($value)
        {
            $temp = explode("\t", $value);
            //print_r($temp); //exit;
            $arr[]=array("name" => &$temp[1] , "id" => &$temp[0]);
        }        
    }    
    //print"<pre>";print_r($arr);print"</pre>"; exit;    
    return $arr;
}//end func

/*
<taxon>
     <Kingdom>Animalia</Kingdom>
     <Phylum>Chordata</Phylum>
     <Class>Actinopterygii (ray-finned fishes)</Class>
     <Order>Perciformes</Order>
     <Family>Cichlidae</Family>
     <Genus>Oreochromis</Genus>
     <name>Marenzelleria arctia</name>
     <taxid>78933</taxid>
     <dna_sequence>
          <record>
               GBAN0755-06|DQ309271|Marenzelleria arctia|---------------------------------------------------------------------GGACTTTTAGGAACATCTATA---AGGCTTCTAATTCGAGCAGAATTAGGCCAACCTGGCTCTTTGCTAGGTAGA---GACCAACTTTATAACACTATTGTTACCGCCCACGCCTTTCTAATAATTTTCTTTCTTGTAATGCCTGTATTTATTGGCGGCTTCGGAAACTGACTTCTTCCTTTAATA---CTTGGTGCTCCAGACATGGCATTCCCGCGTCTAAATAACATAAGATTCTGACTTCTTCCTCCCTCTTTAACACTTCTCGTCTCCTCTGCAGCCGTAGAAAAAGGAGTGGGAACAGGATGAACAGTCTACCCTCCTTTATCAGGCAATTTAGCCCACGCAGGACCTTCTGTAGATCTG---GCTATTTTCTCACTTCATTTAGCAGGGGTTTCTTCTATTTTAGGGGCTCTAAATTTTATTACAACAATTATTAACATACGATGAAAAGGACTACGTCTAGAGCGTATCCCATTATTCGTTTGATCTGTAGTTATTACAGCTGTTCTTCTTCTTCTATCACTTCCAGTTCTAGCAGGA---GCCATTACAATACTTCTAACTGATCGTAATCTTAACACATCTTTCTTTGACCCTGCAGGAGGAGGAGATCCTATTCTGTACCAACACTTATTTTGA-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
          </record>          
          <record>
               GBAN1765-08|EF137728|Marenzelleria arctia|---------------------------------------------------------------------GGACTTTTAGGAACATCTATA---AGGCTTCTAATTCGAGCAGAATTAGGCCAACCTGGCTCTTTGCTAGGTAGA---GACCAACTTTATAACACTATTGTTACCGCCCACGCCTTTCTAATAATTTTCTTTCTTGTAATGCCTGTATTTATTGGCGGCTTCGGAAACTGACTTCTTCCTTTAATA---CTTGGTGCTCCAGACATGGCATTCCCGCGTCTAAATAACATAAGATTCTGACTTCTTCCTCCCTCTTTAACACTTCTCGTCTCCTCTGCAGCCGTAGAAAAAGGAGTGGGAACAGGATGAACAGTCTACCCTCCTTTATCAGGCAATTTAGCCCACGCAGGACCTTCTGTAGATCTG---GCTATTTTCTCACTTCATTTAGCAGGGGTTTCTTCTATTTTAGGGGCTCTAAATTTTATTACAACAATTATTAACATACGATGAAAAGGACTACGTCTAGAGCGTATCCCATTATTCGTTTGATCTGTAGTTATTACAGCTGTTCTTCTTCTTCTATCACTTCCAGTTCTAGCAGGA---GCCATTACAATACTTCTAACTGATCGTAATCTAAACACATCTTTCTTTGACCCTGCAGGAGGAGGAGATCCTATTCTGTACCAACACTTATTTTGA-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
          </record>          
          <record>
               GBAN0756-06|DQ309272|Marenzelleria arctia|---------------------------------------------------------------------GGACTTTTAGGAACATCTATA---AGGCTTCTAATTCGAGCAGAATTAGGCCAACCTGGCTCTTTGCTAGGTATA---GACCAACTTTATAACACTATTGTTACCGCCCACGCCTTTCTAATAATTTTCTTTCTTGTAATGCCTGTATTTATTGGCGGCTTCGGAAACTGACTTCTTCCTTTAATA---CTTGGTGCTCCAGACATGGCATTCCCGCGTCTAAATAACATAAGATTCTGACTTCTTCCTCCCTCTTTAACACTTCTCGTCTCCTCTGCAGCCGTAGAAAAAGGAGTGGGAACAGGATGAACAGTCTACCCTCCTTTATCAGGCAATTTAGCCCACGCAGGACCTTCTGTAGATCTG---GCTATTTTCTCACTTCATTTAGCAGGGGTTTCTTCTATTTTAGGGGCTCTAAATTTTATTACAACAATTATTAACATACGATGAAAAGGACTACGTCTAGAGCGTATCCCATTATTCGTTTGATCTGTAGTTATTACAGCTGTTCTTCTTCTTCTATCACTTCCAGTTCTAGCAGGA---GCCATTACAATACTTCTAACTGATCGTAATCTTAACACATCTTTCTTTGACCCTGCAGGAGGAGGAGATCCTATTCTGTACCAACACTTATTTTGA-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
          </record>          
     </dna_sequence     
</taxon>
*/
?>