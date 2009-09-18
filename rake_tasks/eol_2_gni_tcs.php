#!/usr/local/bin/php
<?php

/*
run date 2009 09 17: 8286031 | unique names 1535661
*/

//define("ENVIRONMENT", "integration");
define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../config/start.php");

set_time_limit(0);
$mysqli =& $GLOBALS['mysqli_connection'];

/* EOL to GNI-TCS */

$used = array();
$resource_path = "..\applications\content_server\gni_tcs_files\eol_names.xml";
$FILE = fopen($resource_path, 'w+');

$header="<?xml version=\"1.0\" encoding=\"utf-8\"?>
<DataSet
  xmlns=\"http://gnapartnership.org/schemas/tcs/1.01\"
  xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
  xmlns:dwc=\"http://rs.tdwg.org/dwc/dwcore/\"
  xmlns:dc=\"http://purl.org/dc/elements/1.1/\"
  xmlns:gn=\"http://gnapartnership.org/schemas/0_1\"
  xsi:schemaLocation=\"http://gnapartnership.org/schemas/tcs/1.01 http://gnapartnership.org/gna_tcs/tcs_gni_v_0_1.xsd\">
  <TaxonNames>";
fwrite($FILE, $header);

/*
//kingdom
$kingdoms = array();
$result = $mysqli->query("SELECT he.id, he.lft, he.rgt, he.hierarchy_id, n.id, n.string FROM hierarchy_entries he JOIN names n ON (he.name_id=n.id) WHERE he.rank_id=".Rank::find("Kingdom"));
while($result && $row=$result->fetch_assoc())
{
    $row["string"] = htmlspecialchars($row["string"]);
    $kingdoms[] = $row;
}
*/

$i=0;

/*
// First get preferred names from Catalogue of Life
$qry="SELECT tc.id, n.string, ranks.label, he.hierarchy_id, he.lft, he.rgt FROM taxon_concepts tc JOIN taxon_concept_names tcn 
ON (tc.id=tcn.taxon_concept_id) JOIN hierarchy_entries he ON (tcn.source_hierarchy_entry_id=he.id) JOIN names n 
ON (tcn.name_id=n.id) Join ranks ON he.rank_id = ranks.id WHERE tc.published=1 
AND tc.vetted_id IN (0, ".Vetted::find('Trusted').") AND vern=0 
AND preferred=1 
AND he.hierarchy_id=".Hierarchy::find_by_label('Species 2000 & ITIS Catalogue of Life: Annual Checklist 2009'); //$qry .= " limit 10000,20";
$result = $mysqli->query($qry);
while($result && $row=$result->fetch_assoc())
{    
    $i++;
    $TaxonName = build_TaxonName($row["string"]);
    if($TaxonName)
    {        
        fwrite($FILE,$TaxonName); 
        $used[$row['id']] = 1;
    }
}
*/

// Then fill in the rest of the names with what is left over - ignoring the names we already used
/*
$qry="SELECT tc.id, n.string, ranks.label, he.hierarchy_id, he.lft, he.rgt FROM taxon_concepts tc JOIN taxon_concept_names tcn 
ON (tc.id=tcn.taxon_concept_id) JOIN names n ON (tcn.name_id=n.id) JOIN hierarchy_entries he 
ON (tcn.source_hierarchy_entry_id=he.id) Join ranks ON he.rank_id = ranks.id WHERE tc.published=1 
AND tc.vetted_id IN (0, ".Vetted::find('Trusted').") AND vern=0 AND preferred=1"; //$qry .= " limit 10000,20";
*/

$qry="SELECT tc.id, n.string, ranks.label, he.hierarchy_id, he.lft, he.rgt FROM taxon_concepts tc JOIN taxon_concept_names tcn 
ON (tc.id=tcn.taxon_concept_id) JOIN names n ON (tcn.name_id=n.id) JOIN hierarchy_entries he 
ON (tcn.source_hierarchy_entry_id=he.id) left Join ranks ON he.rank_id = ranks.id WHERE tc.published=1 
AND tc.vetted_id IN (0, ".Vetted::find('Trusted').") AND vern=0 "; 
//$qry .= " limit 10000,20";
//AND preferred=1

$result = $mysqli->query($qry);
while($result && $row=$result->fetch_assoc())
{
    $i++;
    if(@$used[$row['id']]) continue;            
    $TaxonName = build_TaxonName($row["string"],$row["label"],$row["id"]);
    if($TaxonName)
    {
        fwrite($FILE,$TaxonName); 
        $used[$row['id']] = 1;
    }
}

fwrite($FILE, "</TaxonNames></DataSet>");
fclose($FILE);

print"<hr>records processed = $i | unique names " . sizeof($used) . "<hr> Done processing.";

function build_TaxonName(&$str,&$label,&$id)
{       
    //global $row;            
    if($string = check_sciname($str))
    {
        $final = trim('
        <TaxonName id="' . $id . '" nomenclaturalCode="Indeterminate">
            <Simple>' . htmlspecialchars($string) . '</Simple>');
        
        if($label != "")
        {
            $final .= trim('
            <Rank>' . ucfirst(strtolower($label)) . '</Rank>');
        }
        
        $final .= trim('            
            <ProviderSpecificData>                
                <dc:source>http://www.eol.org/pages/' . $id . '</dc:source>
                <dc:identifier>' . $id . '</dc:identifier>
            </ProviderSpecificData>
        </TaxonName>');
        
        return $final;
        
        //<dwc:Kingdom>' . get_kingdom($row["hierarchy_id"], $row["lft"], $row["rgt"]) . '</dwc:Kingdom>
    }
    else return false;
}

function check_sciname($str)
{
    $string = trim($str);        
    $string = preg_replace("/\v/", " ", $string);
    if(!$string) return false;
    return $string;
}

/*
function get_kingdom($hierarchy_id, $lft, $rgt)
{
    global $kingdoms;    
    foreach($kingdoms as $kingdom)
    {
        if($kingdom["hierarchy_id"] == $hierarchy_id && $kingdom["lft"] < $lft && $kingdom["rgt"] > $rgt)
        {
            return $kingdom["string"];
        }
    }    
    return false;
}
*/

?>