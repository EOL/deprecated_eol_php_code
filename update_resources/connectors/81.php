#!/usr/local/bin/php  
<?php
//
/* connector for BOLD Systems */
//exit;
/*
http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=26136&iwidth=600
http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=111651&iwidth=600
http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=127144&iwidth=600


http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=10325
http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=26136

http://www.barcodinglife.org/views/taxbrowser.php?taxon=Gadus+morhua
http://www.barcodinglife.org/views/taxbrowser.php?taxon=Bimastos+welchi

http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=Annelida
*/

//define("ENVIRONMENT", "development");
define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

//only on local; to be deleted before going into production
/*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
exit;
*/



////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

set_time_limit(0);
$resource = new Resource(81); //print $resource->id; exit;

$schema_taxa = array();
$used_taxa = array();

$id_list=array();

$wrap = "\n";
//$wrap = "<br>";

$phylum_service_url = "http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=";
$species_service_url = "http://www.barcodinglife.org/views/taxbrowser.php?taxon=";

$query="Select distinct taxa.taxon_phylum From taxa Where taxa.taxon_phylum Is Not Null and taxa.taxon_phylum <> '' ";
//$query .= " and taxon_phylum = 'Chordata' ";
//$query .= " and taxon_phylum = 'Chaetognatha' ";
//$query .= " and taxon_phylum <> 'Annelida' ";
$query .= " Order By taxa.taxon_phylum Asc ";
//$query .= " limit 1 ";
$result = $mysqli->query($query);    
print "phylum count = " . $result->num_rows . "$wrap"; //exit;

$total_taxid_count = 0;
$do_count = 0;//weird but needed here
$ctr=0;
$id_with_public_barcode=array();
while($row=$result->fetch_assoc())     
{
    $taxid_count=0;
    $taxid_count_with_barcode=0;
    
    $ctr++;   
    print "$wrap $ctr. phylum = " . $row["taxon_phylum"] . "$wrap";
        
    $url = $phylum_service_url . trim($row["taxon_phylum"]);
    
    /* for debug - to limit no. of record to process
    $url = "http://128.128.175.77/bold.xml";
    */
    
    if(!($xml = @simplexml_load_file($url)))continue;    
    
    $do_count = 0;
    foreach($xml->taxon as $main)
    {                       
        if(in_array("$main->taxid", $id_list)) continue;
        else $id_list[]=$main->taxid;
    
        $taxid_count++;        
        
        //start #########################################################################  

        if(intval($main->public_barcodes > 0))
        {
            $id_with_public_barcode[]=$main->taxid;
            $taxid_count_with_barcode++;
            
            // start comment here to just see count  /*            
            //start taxon part
            $taxon = str_replace(" ", "_", $main->name);
            if(@$used_taxa[$taxon])
            {
                $taxon_parameters = $used_taxa[$taxon];
            }
            else
            {
                $taxon_parameters = array();
                $taxon_parameters["identifier"] = $main->taxid;
                $taxon_parameters["scientificName"]= $main->name;
                $taxon_parameters["source"] = $species_service_url . urlencode($main->name);
            
                $used_taxa[$taxon] = $taxon_parameters;            
            }            
            //end taxon part            

            $do_count++;

            $dc_source = $species_service_url . urlencode($main->name);                            
            $data_object_parameters = get_data_object($main->taxid,$do_count,$dc_source,$main->public_barcodes);                   
            $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);         
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
}//end main loop

echo "$wrap$wrap total taxid = " . $total_taxid_count . " = " . count($id_list);
echo "$wrap$wrap total ids with public barcode = " . count($id_with_public_barcode);

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

echo "$wrap$wrap Done processing.";

function get_data_object($taxid,$do_count,$dc_source,$public_barcodes)
{    
    global $wrap;
    /*            
    Ratnasingham S, Hebert PDN. Compilers. 2009. BOLD : Barcode of Life Data System.
    World Wide Web electronic publication. www.boldsystems.org, version (08/2009). 
    */
    //start get text dna sequece
    $src = "http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=" . $taxid . "&iwidth=400";
    if($public_barcodes > 0)
    {
        $url = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";
        $arr = get_text_dna_sequence($url);
        $count_sequence     = $arr[0];
        $text_dna_sequence  = $arr[1];
        $url_fasta_file     = $arr[2];
        
        print "$wrap [$public_barcodes]=[$count_sequence] $wrap ";
        
        if($count_sequence > 0)
        {
            if($public_barcodes == 1)$str="There is 1 barcode sequence available from BOLD and GenBank. Below is the sequence of the barcode region Cytochrome oxidase subunit 1 (COI or COX1) from a member of the species. See the <a target='BOLDSys' href='$dc_source'>BOLD taxonomy browser</a> for more complete information about this specimen. Other sequences that do not yet meet barcode criteria may also be available.";
            else $str="There are $count_sequence barcode sequences available from BOLD and GenBank. Below is a sequence of the barcode region Cytochrome oxidase subunit 1 (COI or COX1) from a member of the species. See the <a target='BOLDSys' href='$dc_source'>BOLD taxonomy browser</a> for more complete information about this specimen and other sequences.";
            $str .= "<br>&nbsp;<br>";                
            $text_dna_sequence .= "<br>&nbsp;<br> -- end -- <br>&nbsp;<br>";                
        }
        else $str="";
    }
    else $text_dna_sequence = '';    

    //
    //
    //
    //if($text_dna_sequence)
    if(trim($text_dna_sequence) != "")
    {
        $temp = "<br>&nbsp;<br>$str ";
        $temp .= "<div style='font-size : x-small;overflow : scroll;'> $text_dna_sequence </div>";
        /* one-click         
        $url_fasta_file = "http://services.eol.org/eol_php_code/applications/barcode/get_text_dna_sequence.php?taxid=$taxid";
        */
        
        /* 2-click per PL advice */
        $url_fasta_file = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";        
        $temp .= "<br><a target='fasta' href='$url_fasta_file'>Download FASTA File</a>";
    }
    else $temp = "<br>&nbsp;<br>No available public DNA sequences <br>";     
    //Genetic Barcode
    $description = "
    The following is a representative barcode sequence, the centroid of all available sequences for this species.    
    <br><a target='barcode' href='$src'><img src='$src' height=''></a>" . $temp;        
    //end get text dna sequence
    
    $dataObjectParameters = array();    
    //$dataObjectParameters["title"] = "Molecular and Genetics";    
    $dataObjectParameters["title"] = "Barcode data";        
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
    $audience = array(  0 => array(     "Expert users"),
                        1 => array(     "General public")
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
        /*   
        $count_sequence_now = substr_count($text_dna_sequence, '>');            
        $text_dna_sequence = str_ireplace(">", "<br>&nbsp;<br>", $text_dna_sequence);        
        $text_dna_sequence = str_ireplace("----", "", $text_dna_sequence);        
        $text_dna_sequence = str_ireplace("---", "|||", $text_dna_sequence);        
        $text_dna_sequence = str_ireplace("-", "", $text_dna_sequence);        
        $text_dna_sequence = str_ireplace("|||", "---", $text_dna_sequence);                                
        if($text_dna_sequence != "")
        {
            $str="";
            if($count_sequence > 2)
            {
                $str .= "There are at least $count_sequence barcode sequences available from BOLD and GenBank. ";          
                $str .= "Below are first of the two sequences available from BOLD. ";
                $str .= "Click <a target='BOLDSys' href='$dc_source'>here</a> to see all available DNA sequences and more information about them."; 
            }        
            if($count_sequence == 2)
            {
                $str .= "There are at least $count_sequence barcode sequences available from BOLD and GenBank. ";          
                $str .= "Below are the two sequences available from BOLD. ";
                $str .= "Click <a target='BOLDSys' href='$dc_source'>here</a> to see more information about them."; 
            }
            if($count_sequence_now == 1)
            {
                $str .= "Below is the available sequence from BOLD.";
                $str .= "Click <a target='BOLDSys' href='$dc_source'>here</a> to see more information about it. "; 
            }                
        }
        else                        
        $str = "You can <a target='BOLDSys' href='$dc_source'>check</a> BOLD Systems for more information. ";         
        */        

?>