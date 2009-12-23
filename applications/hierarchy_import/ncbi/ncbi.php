<?php

include_once(dirname(__FILE__) . "/../../../config/start.php");

Functions::require_module("darwincore");


// shell_exec("curl ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdump.tar.gz -o ".dirname(__FILE__)."/../downloads/ncbi_taxdump.tar.gz");
// // unzip the download
// shell_exec("tar -zxf ".dirname(__FILE__)."/../downloads/ncbi_taxdump.tar.gz");
// shell_exec("rm -f ".dirname(__FILE__)."/../downloads/ncbi_taxdump.tar.gz");




$GLOBALS['names'] = array();

echo "Memory: ".memory_get_usage()."\n";
get_names();
echo "Memory: ".memory_get_usage()."\n";
get_nodes();
echo "Memory: ".memory_get_usage()."\n";


// shell_exec("rm -f ".dirname(__FILE__)."/*.dmp");
// shell_exec("rm -f ".dirname(__FILE__)."/*.prt");
// shell_exec("rm -f ".dirname(__FILE__)."/*.txt");









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
            
            $GLOBALS['names'][$tax_id][] = array('name' => $name, 'name_class' => $name_class);
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
            //if($i>100) break;
            
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
            
            if(isset($GLOBALS['names'][$tax_id]))
            {
                // first loop and find all vernacular names
                $vernacular_names = array();
                foreach($GLOBALS['names'][$tax_id] as $name)
                {
                    if(in_array($name["name_class"], array("genbank common name", "common name")))
                    {
                        $vernacular_names['en'] = $name["name"];
                    }
                }
                
                // loop again to write out all scientific names and synonyms
                foreach($GLOBALS['names'][$tax_id] as $name)
                {
                    if($name["name_class"] == "valid")
                    {
                        $dwc_taxon = new DarwinCoreTaxon(array(
                                "taxonID"           => $tax_id,
                                "scientificName"    => $name["name"],
                                "parentNameUsageID" => $parent_tax_id,
                                "taxonRank"         => $rank,
                                "taxonomicStatus"   => "valid",
                                "vernacularNames"   => $vernacular_names));
                        fwrite($OUT, $dwc_taxon->__toXML());
                    }elseif(!in_array($name["name_class"], array("genbank common name", "common name")))
                    {
                        $dwc_taxon = new DarwinCoreTaxon(array(
                                "scientificName"    => $name["name"],
                                "parentNameUsageID" => $tax_id,
                                "taxonomicStatus"   => $name["name_class"]));
                        fwrite($OUT, $dwc_taxon->__toXML());
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