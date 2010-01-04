<?php

//define("ENVIRONMENT", "staging");
include_once(dirname(__FILE__) . "/../../../config/start.php");


shell_exec("curl ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdump.tar.gz -o ".dirname(__FILE__)."/../downloads/ncbi_taxdump.tar.gz");
// unzip the download
shell_exec("tar -zxf ".dirname(__FILE__)."/../downloads/ncbi_taxdump.tar.gz");
shell_exec("rm -f ".dirname(__FILE__)."/../downloads/ncbi_taxdump.tar.gz");




$GLOBALS['names'] = array();

echo "Memory: ".memory_get_usage()."\n";
get_names();
echo "Memory: ".memory_get_usage()."\n";
get_nodes();
echo "Memory: ".memory_get_usage()."\n";


$agent_params = array(  "full_name"     => "National Center for Biotechnology Information",
                        "acronym"       => "NCBI");
                            
$agent_id = Agent::insert(Functions::mock_object("Agent", $agent_params));
$agent_hierarchy_id = Hierarchy::find_by_agent_id($agent_id);
if($agent_hierarchy_id)
{
    $agent_hierarchy = new Hierarchy($agent_hierarchy_id);
    $hierarchy_group_id = $agent_hierarchy->hierarchy_group_id;
    $hierarchy_group_version = $agent_hierarchy->latest_group_version()+1;
}else
{
    $hierarchy_group_id = Hierarchy::next_group_id();
    $hierarchy_group_version = 1;
}
$hierarchy_params = array(  "label"                     => "NCBI Taxonomy",
                            "description"               => "latest export",
                            "agent_id"                  => $agent_id,
                            "hierarchy_group_id"        => $hierarchy_group_id,
                            "hierarchy_group_version"   => $hierarchy_group_version);
$hierarchy = new Hierarchy(Hierarchy::insert(Functions::mock_object("Hierarchy", $hierarchy_params)));


$uri = dirname(__FILE__) . "/out.xml";
DarwinCoreHarvester::harvest($uri, $hierarchy);


shell_exec("rm -f ".dirname(__FILE__)."/*.dmp");
shell_exec("rm -f ".dirname(__FILE__)."/*.prt");
shell_exec("rm -f ".dirname(__FILE__)."/*.txt");




function get_names()
{
    $FILE = fopen(dirname(__FILE__)."/names.dmp", "r");
    while(!feof($FILE))
    {
        if($line = fgets($FILE, 4096))
        {
            $line = rtrim($line, "\r\n");
            $parts = explode("\t|", $line);
            
            $tax_id = trim($parts[0]);
            $name = trim($parts[1]);
            $name_class = trim($parts[3]);
            
            // 1 is the node with name root - I'd rather have several superkingdoms than one with name 'root'
            if($tax_id == 1) continue;
            // the scientific name is the valid name for the node
            if($name_class == "scientific name") $name_class = "valid";
            // remove single and double quotes from ('")word or phrase('")
            while(preg_match("/(^| )(('|\")(.*?)\\3)( |-|$)/",$name,$arr))
            {
                $name = str_replace($arr[2], $arr[4], $name);
            }
            while(preg_match("/  /", $name)) $name = str_replace("  ", " ", $name);
            
            //$GLOBALS['names'][$tax_id][] = array('name' => $name, 'name_class' => $name_class);
            $GLOBALS['names'][$tax_id][$name_class][] = $name;
        }
    }
    fclose($FILE);
}

function get_nodes()
{
    $FILE = fopen(dirname(__FILE__)."/nodes.dmp", "r");
    $OUT = fopen(dirname(__FILE__)."/out.xml", "w+");
    
    fwrite($OUT, DarwinCoreRecordSet::xml_header());
    
    $i = 0;
    while(!feof($FILE))
    {
        if($line = fgets($FILE, 4096))
        {
            $i++;
            //if($i>500) break;
            
            $line = rtrim($line, "\r\n");
            $parts = explode("\t|", $line);
            
            $tax_id = trim($parts[0]);
            $parent_tax_id = trim($parts[1]);
            $rank = trim($parts[2]);
            $hidden_flag = trim($parts[10]);
            $comments = trim($parts[12]);
            
            // I'd rather have and empty rank than a rank with label 'no rank'
            if($rank == "no rank") $rank = "";
            // tax_id 1 is the node 'root`'. I think it make more sense to have several superkingdoms
            if($parent_tax_id == 1) $parent_tax_id = 0;
            
            if(isset($GLOBALS['names'][$tax_id]['valid']))
            {
                // first loop and find all vernacular names
                $vernacular_names = array();
                foreach($GLOBALS['names'][$tax_id] as $name_class => $array)
                {
                    if(in_array($name_class, array("genbank common name", "common name")))
                    {
                        foreach($array as $name) $vernacular_names[] = $name;
                    }
                }
                
                $dwc_taxon = new DarwinCoreTaxon(array(
                        "taxonID"           => $tax_id,
                        "scientificName"    => $GLOBALS['names'][$tax_id]['valid'][0],
                        "parentNameUsageID" => $parent_tax_id,
                        "taxonRank"         => $rank,
                        "taxonomicStatus"   => "valid",
                        "vernacularNames"   => $vernacular_names));
                fwrite($OUT, $dwc_taxon->__toXML());
                
                foreach($GLOBALS['names'][$tax_id] as $name_class => $array)
                {
                    if(!in_array($name_class, array("genbank common name", "common name", "valid")))
                    {
                        foreach($array as $name)
                        {
                            $dwc_taxon = new DarwinCoreTaxon(array(
                                    "scientificName"    => $name,
                                    "parentNameUsageID" => $tax_id,
                                    "taxonomicStatus"   => $name_class));
                            fwrite($OUT, $dwc_taxon->__toXML());
                        }
                    }
                }
            }
        }
    }
    
    fwrite($OUT, DarwinCoreRecordSet::xml_footer());
    
    fclose($OUT);
    fclose($FILE);
}


?>