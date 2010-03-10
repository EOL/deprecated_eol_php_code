<?php

define("USING_SPM", true);
//define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/environment.php");

//$url = "plazi_spm_9_24_08.xml";
//$url = "files/plazi_spm_10_20_08.xml";
$url = "files/SalmoSalar.rdf";



$document = new RDFDocument($url);


$species_profiles = $document->get_species_profiles();
foreach($species_profiles as $profile)
{
    echo "<strong>$profile</strong><br>";
    $concepts = $profile->taxon_concepts();
    foreach($concepts as $concept)
    {
        echo $concept->get_literal("tc:nameString")."<br>";
        $taxonName = $concept->get_resource("tc:hasName", "TaxonName");
        echo $taxonName->name_complete()."<br>";
    }
    
    $info_items = $profile->info_items();
    foreach($info_items as $info_item)
    {
        echo "<strong>$info_item</strong><br><blockquote>";
        echo "<strong>Type</strong>: ".$info_item->get_type()."<br>";
        echo "<strong>Content</strong>: ".$info_item->get_literal("spm:hasContent")."<br>";
        
        echo "</blockquote>";
    }
    
    echo "<hr>";
}




/*
    $rdf_xml = preg_replace("/[\n\r]/"," ", Functions::get_remote_file("files/$url"));
    $lsids = get_lsids($rdf_xml);




    $resolved = array();

    function get_lsids($rdf_xml)
    {
        global $resolved;

        $lsids = array();

        while(preg_match("/(.*?)\"(urn:lsid:[^\"]+)\"(.*)$/m",$rdf_xml,$arr))
        {
            $lsid = $arr[2];
            $lsids[$lsid] = 1;
            echo "$lsid<br>";
            flush();

            $rdf_xml = $arr[3];
        }

        foreach($lsids as $lsid => $v)
        {
            if(preg_match("/osuc_relationships/", $lsid)) continue;
            echo "<b>Resolving $lsid</b><br>";
            if(@$resolved[$lsid]) continue;
            if(file_exists("files/$lsid")) continue;
            $resolved[$lsid] = 1;

            $rdf_xml = Functions::get_remote_file(LSID_RESOLVER . $lsid);

            $FILE = fopen("files/$lsid", "w+");
            fwrite($FILE, $rdf_xml);
            fclose($FILE);

            get_lsids(preg_replace("/[\n\r]/"," ", $rdf_xml));
        }

        return $lsids;
    }
*/



?>
