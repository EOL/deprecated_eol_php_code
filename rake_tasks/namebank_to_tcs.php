<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];



if(!($OUT = fopen("../temp/namebank.tcs.xml", "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " ."../temp/namebank.tcs.xml");
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



$result = $mysqli->query("SELECT MAX(id) as max FROM names");
$row = $result->fetch_assoc();
$max = $row["max"];
$start = 1;
$interval = 50000;
$i = 0;

//$max = 2;
//$interval = 12800;

while($start < $max)
{
    $records = "";
    
    debug($start);
    $result = $mysqli->query("SELECT n.namebank_id, n.string FROM names n JOIN name_languages nl ON (n.id=nl.name_id) WHERE n.id BETWEEN $start AND ".($start+$interval-1)." AND n.namebank_id!=0 AND nl.language_id=".Language::find("Scientific Name")." GROUP BY n.id");
    while($result && $row=$result->fetch_assoc())
    {
        if($i%50000 == 0) echo "$i\n";
        $i++;
            
        $namebank_id = $row["namebank_id"];
        $string = trim($row["string"]);
        
        $string = preg_replace("/\v/", " ", $string);
        if(!$string) continue;
        
        if(preg_match("/[^A-ZÀÂÅÅÃÄÁÆČÇÉÈÊËÍÌÎÏÑÓÒÔØÕÖÚÙÛÜßβĶŘŠŞγŽŒa-záááàâåãäăαæčćçéèêëĕíìîïǐĭñńóòôøõöŏúùûüůśšşřğ×žźpýýýÿœœ0-9 \/\.\?\"{}\[\]\(\),;:'’`_#&+=-]/", $string))
        {
            continue;
        }
        
        $records .= "    <TaxonName id=\"$namebank_id\" nomenclaturalCode=\"Indeterminate\">\n";
        $records .= "      <Simple>".htmlspecialchars($string)."</Simple>\n";
        $records .= "      <ProviderSpecificData>\n";
        $records .= "        <dc:source>http://www.ubio.org/browser/details.php?namebankID=$namebank_id</dc:source>\n";
        $records .= "        <dc:identifier>urn:lsid:ubio.org:namebank:$namebank_id</dc:identifier>\n";
        $records .= "        <dwc:GlobalUniqueIdentifier>urn:lsid:ubio.org:namebank:$namebank_id</dwc:GlobalUniqueIdentifier>\n";
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

?>