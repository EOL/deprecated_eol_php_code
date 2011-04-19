<?php                                                                                                                                                          


include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

/* fullname must come from agents!full_name */

$providers=array();
$providers[] = array(   "full_name" => "diArk - A resource for eukaryotic genome research",
                        "title"     => "diArk",
                        "vetted"    => "1",
                        "homepage"  => "http://www.diark.org/diark",
                        "logo_url"  => "http://www.diark.org/images/layout/diark_logo.png",
                        "uri"       => "http://www.diark.org/diark/species_list?query=FOREIGNKEY",
                        "xml_path"  => "http://www.goenmr.de/~bham/diark2010_01_04.xml"
                    );

$providers[] = array(   "full_name" => "Aves 3D",
                        "title"     => "Aves 3D",
                        "vetted"    => "1",
                        "homepage"  => "http://aves3d.org/",
                        "logo_url"  => "http://aves3d.org/images/logo.png",
                        "uri"       => "http://aves3d.org/species_instances/FOREIGNKEY",
                        "xml_path"  => "http://aves3d.org/browse/xmlgen"
                    );

foreach ($providers as $provider)
{
    $params="";
    foreach ($provider as $key => $value) 
    {
        echo "\$aprovider[$key] => $value \n";
        $params .= $value .",";        
    }
    $params = trim($params);
    $params = substr($params,0,strlen($params)-1);
    $temp = process($params);
    echo"\n";
}

exit("\n -end process- ");
//###########################################################################################################
//###########################################################################################################

function process($params)
{
    global $mysqli;
    
    $arr = explode(",",$params);
    
    $mysqli->begin_transaction();                                                                                                                                                                                                                                                                                                 
    // /*                                                                                                                                                          
    $mock_collection = Functions::mock_object("Collection",                                                                                                        
    array(                                                                                                                                                         
            "agent_id"     => Agent::find($arr[0]),
            "title"        => $arr[1],                      
            "vetted"       => $arr[2],                    
            "link"         => $arr[3],               
            "logo_url"     => $arr[4],                    
            "uri"          => $arr[5]));                   
    $collection_id = Collection::insert($mock_collection); 
    $collection = new Collection($collection_id);                                                                                                                  
    // */                                                                                                                                                               
    //#######################################################################################################################
    //start get dataset
    $xml = simplexml_load_file($arr[6], null, LIBXML_NOCDATA);
    $i=0;                                                                                                                                                          
    echo "Taxa count = " . count($xml) . "\n";                                                                                                                     
    foreach($xml->taxon as $t)                                                                                                                                     
    {                                                                                                                                                              
        $i++;
        //if($i <= 2)
        if(true)
        {                                                                                                                                                          
            $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
            $t_dc  = $t->children("http://purl.org/dc/elements/1.1/");        

            $sciname        = Functions::import_decode($t_dwc->ScientificName);
            $source_url     = Functions::import_decode($t_dc->source);
            $dc_identifier  = Functions::import_decode($t_dc->identifier);
 
            $sciname        = str_ireplace("&nbsp;", "", $sciname);
            $dc_identifier  = str_ireplace("&nbsp;", "", $dc_identifier);
            
            if      ($arr[1] == "diArk")     $second_param = $sciname;
            elseif  ($arr[1] == "Aves 3D")   $second_param = $dc_identifier;
            
            //print "\n title = $arr[1] \n";
                        
            print "$i. $sciname [$second_param] \n ";
            // /*
            //$collection->add_mapping($sciname, $source_url);
            $collection->add_mapping($sciname, $second_param);
            // */
        }
    }                                                                                                                                                              
    //#######################################################################################################################                                      
    echo " \n Records processed = $i";                                                                                                                                 
    $mysqli->end_transaction();                                                                                                                                    
}
                                                                                                                                                               
?>                                                                                                                                                             