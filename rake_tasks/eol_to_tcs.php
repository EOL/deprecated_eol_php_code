<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

if(!($OUT = fopen("../temp/eol.tcs.xml", "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " ."../temp/eol.tcs.xml");
  return;
}

$header = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<DataSet
  xmlns=\"http://gnapartnership.org/schemas/tcs/1.01\"
  xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
  xmlns:dwc=\"http://rs.tdwg.org/dwc/dwcore/\"
  xmlns:dc=\"http://purl.org/dc/elements/1.1/\"
  xmlns:gn=\"http://gnapartnership.org/schemas/0_1\"
  xsi:schemaLocation=\"http://gnapartnership.org/schemas/tcs/1.01 http://gnapartnership.org/gna_tcs/tcs_gni_v_0_1.xsd\">

  <TaxonNames>
";

fwrite($OUT, $header);



$kingdoms = array();

$result = $mysqli->query("SELECT he.id, he.lft, he.rgt, he.hierarchy_id, n.id, n.string FROM hierarchy_entries he JOIN names n ON (he.name_id=n.id) WHERE he.rank_id=".Rank::find("Kingdom"));
while($result && $row=$result->fetch_assoc())
{
    $row["string"] = htmlspecialchars($row["string"]);
    $kingdoms[] = $row;
}


$result = $mysqli->query("SELECT MAX(id) as max FROM taxon_concepts");
$row = $result->fetch_assoc();
$max = $row["max"];
$start = 1;
$interval = 50000;
$i = 0;

// $start = 100000;
// $max = $start + 2;
// $interval = 12800;

while($start < $max)
{
    $records = "";
    
    debug($start);
    $result = $mysqli->query("SELECT tcn.taxon_concept_id id, n.string, he.hierarchy_id, he.lft, he.rgt FROM taxon_concept_names tcn JOIN hierarchy_entries he ON (tcn.taxon_concept_id=he.taxon_concept_id) JOIN names n ON (he.name_id=n.id) WHERE tcn.taxon_concept_id BETWEEN $start AND ".($start+$interval-1)." AND tcn.source_hierarchy_entry_id!=0 AND tcn.preferred=1 GROUP BY tcn.taxon_concept_id");
    while($result && $row=$result->fetch_assoc())
    {
        if($i%50000 == 0) echo "$i\n";
        $i++;
        
        $id = $row["id"];
        $string = trim($row["string"]);
        
        $string = preg_replace("/\v/", " ", $string);
        if(!$string) continue;
        
        //[^A-ZÀÂÅÅÃÄÁÆČÇÉÈÊËÍÌÎÏÑÓÒÔØÕÖÚÙÛÜßβĶŘŠŞγÝŽŒa-záááàâåãäăαæčćçéèêëĕíìîïǐĭñńóòôøõöŏúùûüůśšşřğ×žźpýýýÿœœ0-9 \/\*\.\?\"{}\[\]\(\)<>%\|\\\n!~$@^,;:'°’`_#&+=-]
        if(preg_match("/[^A-ZÀÂÅÅÃÄÁÆČÇÉÈÊËÍÌÎÏÑÓÒÔØÕÖÚÙÛÜßβĶŘŠŞγÝŽŒa-záááàâåãäăαæčćçéèêëĕíìîïǐĭñńóòôøõöŏúùûüůśšşřğ×žźpýýýÿœœ0-9 \/\*\.\?\"{}\[\]\(\),;:'°’`_#&+=-]/", $string))
        {
            //echo "$string\n";
            continue;
        }
        
        $records .= "    <TaxonName id=\"$id\" nomenclaturalCode=\"Indeterminate\">\n";
        $records .= "      <Simple>".htmlspecialchars($string)."</Simple>\n";
        $records .= "      <ProviderSpecificData>\n";
        if($kingdom = get_kingdom($row["hierarchy_id"], $row["lft"], $row["rgt"])) $records .= "        <dwc:Kingdom>$kingdom</dwc:Kingdom>\n";
        $records .= "        <dc:source>http://www.eol.org/pages/$id</dc:source>\n";
        $records .= "        <dc:identifier>$id</dc:identifier>\n";
        $records .= "      </ProviderSpecificData>\n";
        $records .= "    </TaxonName>\n";
    }
    
    fwrite($OUT, $records);
    $start += $interval;
}



$footer = "  </TaxonNames>
</DataSet>
";

fwrite($OUT, $footer);

fclose($OUT);


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





?>