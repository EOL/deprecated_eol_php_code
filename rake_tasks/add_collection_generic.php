<?php                                                                                                                                                          
//define("ENVIRONMENT", "development");                                                                                                                          
define('DEBUG', true);                                                                                                                                         
define('MYSQL_DEBUG', true);                                                                                                                                   
//define('DEBUG_TO_FILE', true);                                                                                                                               


include_once(dirname(__FILE__) . "/../config/start.php");                                                                                                                     
                                                                                                                                                               
$mysqli =& $GLOBALS['mysqli_connection'];                                                                                                                      
                                                                                                                                                               
//############################# Variables to fill-up:                                                                                                          
$agent_fullname 				= "Shorefishes of the Tropical Eastern Pacific Online Information System";	//get this from agents!full_name                           
$tile 									= "Shorefishes of the Tropical Eastern Pacific";                                                                                       
$vetted 								= 1;                                                                                                                                   
$homepage 							= "http://www.neotropicalfishes.org/sftep/index.php";                                                                                  
$logo_url								= "http://www.neotropicalfishes.org/sftep/images/logo1.gif";                                                                           
$uri 										= "FOREIGNKEY";                                                                                                                        
$path_to_xml_resource 	= "http://128.128.175.77/mtce/stri_fish/txt/final.xml";                                                                                
//#############################                                                                                                                                

$mysqli->begin_transaction();                                                                                                                                  
                                                                                                                                                               
// /*                                                                                                                                                          
$mock_collection = Functions::mock_object("Collection",                                                                                                        
array(                                                                                                                                                         
		"agent_id" 	=> Agent::find($agent_fullname),                                                                                                               
		"title" 		=> $tile,                                                                                                                                      
		"vetted"		=> $vetted,                                                                                                                                    
		"link" 			=> $homepage,                                                                                                                                  
		"logo_url"	=> $logo_url,                                                                                                                                  
		"uri" 			=> $uri));                                                                                                                                     
$collection_id = Collection::insert($mock_collection);                                                                                                         
$collection = new Collection($collection_id);                                                                                                                  
// */                                                                                                                                                          
                                                                                                                                                               
//#######################################################################################################################                                      
//start get dataset                                                                                                                                            
$xml = simplexml_load_file($path_to_xml_resource, null, LIBXML_NOCDATA);                                                                                       
$i=0;                                                                                                                                                          
echo "Taxa count = " . count($xml) . "\n";                                                                                                                     
foreach($xml->taxon as $t)                                                                                                                                     
{                                                                                                                                                              
	$i++;                                                                                                                                                        
	//if($i <=3)                                                                                                                                                 
	//{                                                                                                                                                          
		$t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");                                                                                                   
		$t_dc = $t->children("http://purl.org/dc/elements/1.1/");                                                                                                  
                                                                                                                                                               
	    $sciname 	= Functions::import_decode($t_dwc->ScientificName);                                                                                            
		$source_url = Functions::import_decode($t_dc->source);                                                                                                     
	                                                                                                                                                             
		//print "$i. $sciname [$source_url]<br>";                                                                                                                  
	                                                                                                                                                             
		// /*                                                                                                                                                      
		$collection->add_mapping($sciname, $source_url);                                                                                                           
		// */                                                                                                                                                      
                                                                                                                                                               
	//}//if	                                                                                                                                                     
}                                                                                                                                                              
//#######################################################################################################################                                      
echo "Records processed = $i";                                                                                                                                 
$mysqli->end_transaction();                                                                                                                                    
                                                                                                                                                               
?>                                                                                                                                                             