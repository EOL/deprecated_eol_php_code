<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];
$mysqli_prod = load_mysql_environment('integration_production');






$used = array();
$ncbi_concept_ids = array();
$result = $mysqli->query("SELECT m.foreign_key, tcn.taxon_concept_id id, tcn.vern FROM mappings m JOIN taxon_concept_names tcn ON (m.name_id=tcn.name_id) WHERE m.collection_id=10");
while($result && $row=$result->fetch_assoc())
{
    if($row['vern'] != 0) continue;
    if(@$used[$row['foreign_key']]) continue;
    $ncbi_concept_ids[$row['id']][] = $row['foreign_key'];
    
    $used[$row['foreign_key']] = 1;
}





if(!($FILE = fopen(dirname(__FILE__) . '/../temp/ncbi_linkout.ft', 'w+')))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . '/../temp/ncbi_linkout.ft');
  return;
}
$details = <<<HERE
prid:   7164
dbase:  Taxonomy
stype:  taxonomy/phylogenetic
!base:  http://www.eol.org/pages/?
------

HERE;
fwrite($FILE, $details);

$used = array();
echo "START 1\n";
$result = $mysqli->query("SELECT tc.id, n.id name_id, n.string FROM taxon_concepts tc JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id) JOIN hierarchy_entries he ON (tcn.source_hierarchy_entry_id=he.id) JOIN names n ON (tcn.name_id=n.id) WHERE tc.published=1 AND tc.vetted_id IN (0, 5) AND vern=0 AND preferred=1 AND he.hierarchy_id=106");
echo "DONE 1\n";
$i = 1;
while($result && $row=$result->fetch_assoc())
{
    $id = $row['id'];
    $name = $row['string'];
    
    if(@$used[$id]) continue;
    if(@!$ncbi_concept_ids[$id]) continue;
    
    $uids = implode(",", $ncbi_concept_ids[$id]);
    $line = <<<HERE
linkid: $i
uids:   $uids
base:   &base;
rule:   $id
name:   $name
------

HERE;
    
    $i++;
    fwrite($FILE, $line);
    
    $used[$id] = 1;
}

echo "START 2\n";
$result = $mysqli_prod->query("SELECT tc.id, n.id name_id, n.string FROM taxon_concepts tc JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id) JOIN names n ON (tcn.name_id=n.id) WHERE tc.published=1 AND tc.vetted_id IN (0, 5) AND vern=0 AND preferred=1");
echo "DONE 2\n";
while($result && $row=$result->fetch_assoc())
{
    $id = $row['id'];
    $name = $row['string'];
    
    if(@$used[$id]) continue;
    if(@!$ncbi_concept_ids[$id]) continue;
    
    $uids = implode(",", $ncbi_concept_ids[$id]);
    $line = <<<HERE
linkid: $i
uids:   $uids
base:   &base;
rule:   $id
name:   $name
------

HERE;
    
    $i++;
    fwrite($FILE, $line);
    
    $used[$id] = 1;
}

fclose($FILE);

?>