<?php
//#!/usr/local/bin/php  
//connector for BOLD Systems

/*
http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=26136&iwidth=600
http://www.barcodinglife.org/views/taxbrowser.php?taxon=Gadus+morhua
http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=Annelida
http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=26136
*/

//define("ENVIRONMENT", "development");
define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", true);
define("DEBUG", false);

include_once(dirname(__FILE__) . "/../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];

//only on local; to be deleted before going into production
 /*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
exit;
 */
set_time_limit(0);
$resource = new Resource(81);
//print $resource->id; exit;

$schema_taxa = array();
$used_taxa = array();

$id_list=array();

//$wrap = "\n";
$wrap = "<br>";

$phylum_service_url = "http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=";
$species_service_url = "http://www.barcodinglife.org/views/taxbrowser.php?taxon=";

$query="Select distinct taxa.taxon_phylum From taxa Where taxa.taxon_phylum Is Not Null and taxa.taxon_phylum <> '' Order By taxa.taxon_phylum Asc ";
//$query .= " limit 1 ";
$result = $mysqli->query($query);    

print "phylum count = " . $result->num_rows . "<hr>"; //exit;

$total_taxid_count = 0;
$do_count = 0;//weird but needed here
$ctr=0;
while($row=$result->fetch_assoc())     
{
    $taxid_count=0;
    $ctr++;   
    print "<hr> $ctr. phylum = " . $row["taxon_phylum"] . "<br>";
        
    $url = $phylum_service_url . trim($row["taxon_phylum"]);
    //$xml = @simplexml_load_file($url);
    
    if(!($xml = @simplexml_load_file($url)))continue
    
    
    $do_count = 0;
    foreach($xml->taxon as $main)
    {                       
        if(in_array("$main->taxid", $id_list)) continue;
        else $id_list[]=$main->taxid;
    
        //print $main->taxid . " - $do_count";    
        $taxid_count++;
        
        
        //start #########################################################################  
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

        if(intval($main->public_barcodes > 0))
        {
            if($do_count == 0)//echo "$wrap$wrap phylum = " . $row["taxon_phylum"] . "$wrap";
            $do_count++;

            $dc_source = $species_service_url . urlencode($main->name);                            
            $data_object_parameters = get_data_object($main->taxid,$do_count,$dc_source,$main->public_barcodes);       
            
            $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
    
            $used_taxa[$taxon] = $taxon_parameters;

        }//with public barcodes
        
        //end #########################################################################
        
    }
    if($taxid_count != 0)
    {
        echo "total=" . $taxid_count;
        $total_taxid_count += $taxid_count;
    }
}//end main loop

echo "$wrap$wrap total taxid = " . $total_taxid_count;

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
    /*            
    Ratnasingham S, Hebert PDN. Compilers. 2009. BOLD : Barcode of Life Data System.
    World Wide Web electronic publication. www.boldsystems.org, version (08/2009). 
    */

    //start get text dna sequece

    $src = "http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=" . $taxid . "&iwidth=400";

    if($public_barcodes > 0)
    {
        $url = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";
        $text_dna_sequence = get_text_dna_sequence($url);                        
        $text_dna_sequence = str_ireplace(">", "<br>&nbsp;<br>", $text_dna_sequence);        
        $text_dna_sequence = str_ireplace("----", "", $text_dna_sequence);        
        $text_dna_sequence = str_ireplace("---", "|||", $text_dna_sequence);        
        $text_dna_sequence = str_ireplace("-", "", $text_dna_sequence);        
        $text_dna_sequence = str_ireplace("|||", "---", $text_dna_sequence);        
        
        $text_dna_sequence = "        
        You can copy-paste sequence below or <a target='BOLDSys' href='$dc_source'>download</a> it from BOLD Systems." 
        . $text_dna_sequence . "<br>&nbsp;<br> -- end -- <br>&nbsp;<br>";
    }
    else $text_dna_sequence = '';
    
    if($text_dna_sequence)
    {
        $temp = "<br>&nbsp;<br>Available Public Sequence(s) = $public_barcodes ";
        $temp .= "<div style='font-size : x-small;overflow : scroll;'> $text_dna_sequence </div>";
    }
    else $temp = "<br>&nbsp;<br>No Available Public Sequences <br>"; 
    
    $description = "<a href='$src'><img src='$src' height=''></a>" . $temp;
    
    
    ////////////
    /*
<a href='http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=79683&iwidth=400'><img src='http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=79683&iwidth=400' height=''></a>    
<hr>
     <div style=
     "  font-size : x-small;
	    overflow : scroll;
        
     ">
        <!--- replace ---- with space --->    
     Public barcodes available = 9 <br><hr>GBSP1293-06|DQ320493|Dollfusentis chandleri|</p><p>TTAGAACTAGGTAGTGGTGGGTTGTTAATTGGTGAT---GAGCATTTATATAATGTGTTATTTACGAGTCATGCCATTATGATGGTCTTTTTCTTAGTAATACCTCTGTTTATAGGGGGTTTTGGTAATTGGATAATCCCTGTGATA---GTGGGGTGTAGGGATATGCTATTCCCTCGTTTAAATAATTTAAGGTTTTTACTAGTTCCTATAAGGTTGGTATTATTTTTAGTGTCTATATATTTAGAGGGGGGTGGTGGA---GGATGGACTATGTACCCACCCTTAATTTTAAGAAGGTACAGTCCTAGGATGTCTGTAGATATAATGGTG---TTAAGTCTTCATATAGCAGGCCTATCTTCATTGTTAGGGTCAATTAATATTATTTTGACAAGAGTTATAGTTAGAGTTCTTCATGGGTTGGGGGAGACTATCCCATTGTTGGTGTGGTCTTTACTAGTTACTGCTGGATTGGTTGTTTTAACTGTACCTGTCCTATCTGCT--</p><p><hr>GBSP1292-06|DQ320492|Dollfusentis chandleri|</p><p>TTAGAACTAGGTAGTGGTGGGTTGTTAATTGGTGAT---GAGCATTTATATAATGTGTTAGTTACGAGTCATGCCATTATGATGGTCTTTTTCTTAGTAATACCTCTGTTTATAGGGGGTTTTGGTAATTGGATAATCCCTGTGATA---GTGGGGTGTAGGGATATGTTATTCCCTCGTTTAAATAATTTAAGGTTTTTACTAGTTCCTATAAGGTTGGTATTATTTTTAGTGTCTATATATTTAGAGGGGGGTGGTGGA---GGATGGACTATGTACCCACCCTTAATTTTAAGAAGGTACAGTCCTAGGATGTCTGTAGATATAATGGTG---TTAAGTCTTCATATAGCAGGCCTATCTTCATTGTTAGGGTCAATTAATATTATTTTGACAAGAGTTATAGTTAGAGTTCTTCATGGGTTGGGGGAGACTATCCCATTGTTGGTGTGGTCTTTACTAGTTACTGCTGGATTGGTGTTTTTAACTGTACCTGTCCTAGCTGCA--</p><p><hr>GBSP1291-06|DQ320490|Dollfusentis chandleri|</p><p>TTAGAACTAGGTAGTGGTGGGTTGTTAATTGGTGAT---GAGCATTTATATAATGTGTTAGTTACGAGTCATGCCATTATGATGGTCTTTTTCTTAGTAATACCTCTGTTTATAGGGGGTTTTGGTAATTGGATAATCCCTGTGATA---GTGGGGTGTAGGGATATGCTATTCCCTCGTTTAAATAATTTAAGGTTTTTACTAGTTCCTATAAGGTTGGTATTATTTTTAGTGTCTATATATTTAGAGGGGGGTGGTGGA---GGATGGACTATGTACCCACCCTTAATTTTAAGAAGGTACAGTCCTAGGATGTCTGTAGATATAATGGTG---TTAAGTCTTCATATAGCAGGCCTATCTTCATTGTTAGGGTCAATTAATATTATTTTGACAAGAGTTATAGTTAGAGTTCTTCATGGGTTGGGGGAGACTATCCCATTGTTGGTGTGGTCTTTTCTAGTTACTGCTGGATTGGTTGTTTTAACTGTACCTGTCCTAGCTGCA--</p><p><hr>GBSP1290-06|DQ320489|Dollfusentis chandleri|</p><p>TTAGAACTAGGTAGTGGTGGGTTGTTAATTGGTGAT---GAGCATTTATATAATGTGTTAGTTACGAGTCATGCCATTATGATGGTCTTTTTCTTAGTAATACCTCTGTTTATAGGGGGTTTTGGTAATTGGATAATCCCTGTGATA---GTGGGGTGTAGGGATATGTTATTCCCTCGTTTAAATAATTTAAGGTTTTTACTAGTTCCTATAAGGTTGGTATTATTTTTAGTGTCTATATATTTAGAGGGGGGTGGTGGA---GGATGGACTATGTACCCACCTTTAATTTTAAGAAGGTACAGTCCTAGGATGTCTGTAGATATAATGGTG---TTAAGTCTTCATATAGCAGGCCTATCTTCATTGTTAGGGTCAATTAATATTATTTTGACAAGAGTTATAGTTAGAGTTCTTCATGGGTTGGGGGAGACTATCCCATTGTTGGTGTGGTCTTTACTAGTTACTGCTGGATTGGTTGTTTTAACTGTACCTGTCCTAGCTGCA--</p><p><hr>GBSP1289-06|DQ320488|Dollfusentis chandleri|</p><p>TTAAAACTAGGTAGTGGTGGGTTGTTAATTGGTGAT---GAGCATTTATATAATGTGTTAGTTACGAGTCATGCCATTATGATGGTCTTTTTCTTAGTAATACCTCTGTTTATAGGGGGTTTTGGTAATTGGATAATCCCTGTGATA---GTGGGGTGTAGGGATATGTTATTCCCTCGTTTAAATAATTTAAGGTTTTTACTAGTTCCTATAAGGTTGGTATTATTTTTAGTGTCTATATATTTAGAGGGGGGTGGTGGA---GGATGGACTATGTACCCACCCTTAATTTTAAGAAGGTACAGTCCTAGGATGTCTGTAGATATAATGGTG---TTAAGTCTTCATATAGCAGGCCTATCTTCATTGTTAGGGTCAATTAATATTATTTTGACAAGAGTTATAGTTAGAGTTCTTCATGGGTTGGGGGAGACTATCCCATTGTTGGTGTGGTCTTTACTAGTTACTGCTGGATTGGTTGTTTTAACTGTACCTGTCCTAGCTGCA--</p><p><hr>GBSP1288-06|DQ320487|Dollfusentis chandleri|</p><p>TTAGAACTAGGTAGTGGTGGGTTGTTAATTGGTGAT---GAGCATTTATATAATGTGTTAGTTACGAGTCATGCCATTATGATGGTCTTTTTCTTAGTAATACCTCTGTTTATAGGGGGTTTTGGTAATTGGATAATCCCTGTGATA---GTGGGGTGTAGGGATATGTTATTCCCTCGTTTAAATAATTTAAGGTTTTTACTAGTTCCTATAAGGTTGGTATTATTTTTAGTGTCTATATATTTAGAGGGGGGTGGTGGA---GGGTGGACTATGTACCCACCCCTAATTTTAAGAAGGTACAGCCCTAGGATGTCTGTAGATATAATGGTG---TTAAGTCTTCATATAGCAGGCTTATCTTCATTGTTAGGGTCAATTAATATTATTTTGACAAGAGTTATAGTTAGAGTTCTTCATGGGTTGGGGGAGACTATCCCATTGTTGGTGTGGTCTTTACTAGTTACTGCTGGATTGGTTGTTTTAACTGTACCTGTTCTAGCTGCA--</p><p><hr>GBSP1287-06|DQ320486|Dollfusentis chandleri|</p><p>TTAGAACTAGGTAGTGGTGGGTTGTTAATTGGTGAT---GAGCATTTATATAATGTGTTAGTTACGAGTCATGCCATTATGATGGTCTTTTTCTTAGTAATACCTCTGTTTATAGGGGGTTTTGGTAATTGGATAATCCCTGTGATA---GTGGGGTGTAGGGATATGTTATTCCCTCGTTTAAATAATTTAAGGTTTTTACTAGTTCCTATAAGGTTGGTATTATTTTTAGTGTCTATATATTTAGAGGGGGGTGGTGGA---GGATGGACTATGTACCCACCCTTAATTTTAAGAAGGTACAGTCCTAGGATGTCTGTAGATATAATGGTG---TTAAGTCTTCATATAGCAGGCCTATCTTCATTGTTAGGGTCAATTAATATTATTTTGACAAGAGTTATAGTTAGAGTTCTTCATGGGTTGGGGGAGGCTATCCCATTGTTGGTGTGGTCTTTACTAGTTACTGCTGGATTGGTTGTTTTAACTGTACCTGTCCTAGCTGCA--</p><p><hr>GBSP1286-06|DQ320485|Dollfusentis chandleri|</p><p>TTAGAACTAGGTAGTGGTGGGTTGTTAATTGGTGAT---GAGCATTTATATAATGTGTTAGTTACGAGTCATGCCATTATGATGGTCTTTTTCTTAGTAATACCTCTGTTTATAGGGGGTTTTGGTAATTGGATAATCCCTGTGATA---GTGGGGTGTAGGGATATGTTATTCCCTCGTTTAAATAATTTAAGGTTTTTACTAGTTCCTATAAGGTTGGTATTATTTTTAGTGTCTATATATTTAGAGGGGGGTGGTGGA---GGATGGACTATGTACCCACCCTTAATTTTAAGAAGGTACAGTCCTAGGATGTCTGTAGATATAATGGTG---TTAAGTCTTCATATAGCAGGCCTATCTTCATTGTTAGGGTCAATTAATATTATTTTGACAAGAGTTATAGTTAGAGTTCTTCATGGGTTGGGGGAGACTATCCCATTGTTGGTGTGGTCTTTACTAGTTACTGCTGGATTGGTTGTTTTAACTGTACCTGTCCTAGCTGCA--</p><p><hr>GBSP1285-06|DQ320484|Dollfusentis chandleri|</p><p>TTAGAACTAGGTAGTGGTGGGTTGTTAATTGGTGAT---GAGCATTTATATAATGTGTTAGTTACGAGTCATGCCATTATGATGGTCTTTTTCTTAGTAATACCTCTGTTTATAGGGGGTTTTGGTAATTGGATAATCCCTGTGATA---GTGGGGTGTAGGGATATGTTATTCCCTCGTTTAAATAATTTAAGGTTTTTACTAGTTCCTATAAGGTTGGTATTATTTTTAGTGTCTATATATTTAGAGGGGGGTGGTGGA---GGATGGACTATGTACCCACCCTTAATTTTAAGAAGGTACAGTCCTAGGATGTCTGTAGATATAATGGTG---TTAAGTCTTCATATAGCAGGCCTATCTTCATTGTTAGGGTCAATTAATATTATTTTGACAAGAGTTATAGTTAGAGTTCTTCATGGGTTGGGGGAGACTATCCCATTGTTGGTGTGGTCTTTACTAGTTACTGCTGGATTGGTTGTTTTAACTGTACCTGTCCTAGCTGCA--</p><p></p><p>

     </div>    
     */
    ////////////
        
    //end get text dna sequence
    
    $dataObjectParameters = array();
    
    $dataObjectParameters["title"] = "Molecular and Genetics";
    
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
    $str = get_file_contents($url); //print $str;  
    $beg='../temp/'; $end1='fasta.fas'; $end2="173xxx"; $end3="173xxx";			
    $folder = parse_html($str,$beg,$end1,$end2,$end3,$end3,"");	        
    $url="http://www.boldsystems.org/temp/" . $folder . "/fasta.fas";
    $str = get_file_contents($url);
    return $str;
}
function get_file_contents($url)
{
    $handle = fopen($url, "r");	
    if ($handle)
    {
        $contents = '';
      	while (!feof($handle)){$contents .= fread($handle, 8192);}
       	fclose($handle);	
       	$str = $contents;
    }     
    return $str;
}        
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
					
					//print "$arr[$k] <hr>";
					
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