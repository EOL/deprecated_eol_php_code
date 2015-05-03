<?php
namespace php_active_record;

/*
rundate     processed   unique names    recs in GNI
2009 09 17: 8286031     1535661
2009 11 25: 8667699     1572382         1,440,818
2010 02 05: 6299946     1789269         1,779,942
*/

//define("ENVIRONMENT", "slave_32"); //comment if to put in BEAST
include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

/* EOL to GNI-TCS */

$used = array();

/* local
$resource_path = "..\applications\content_server\gni_tcs_files\eol_names.xml";
*/

// /* beast and local
$resource_path = DOC_ROOT."applications/content_server/gni_tcs_files/eol_names.xml";
// */

if(!($FILE = fopen($resource_path, 'w+')))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
  return;
}

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

$i=0;

$qry="SELECT tc.id, n.string, ranks.label, he.hierarchy_id
From hierarchy_entries he
Inner Join names n ON n.id = he.name_id
Inner Join taxon_concepts tc ON he.taxon_concept_id = tc.id
Inner Join ranks ON he.rank_id = ranks.id
WHERE tc.published=1 AND tc.vetted_id <> " . Vetted::find("untrusted"); 
//$qry .= " limit 10";
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

?>