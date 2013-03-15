<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
exit;

if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $mysqli_slave = load_mysql_environment('slave');
else $mysqli_slave = $GLOBALS['db_connection'];


$log = HarvestProcessLog::create(array('process_name' => 'Random Hierarchy Images'));

$species_rank_ids = implode(",", Rank::species_ranks_ids());

$outfile = $mysqli_slave->select_into_outfile("SELECT distinct NULL, tcc.image_object_id, he.id, he.hierarchy_id, he.taxon_concept_id, n.italicized name FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN data_objects do ON (tcc.image_object_id=do.id) JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id) LEFT JOIN names n ON (he.name_id=n.id) WHERE tc.published=1 AND tc.vetted_id=".Vetted::trusted()->id." AND (he.lft=he.rgt-1 OR he.rank_id IN ($species_rank_ids)) AND tcc.image=1 AND dohe.vetted_id=".Vetted::trusted()->id);

file_randomize($outfile);

$GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS random_hierarchy_images_tmp LIKE random_hierarchy_images");
$GLOBALS['db_connection']->delete("TRUNCATE TABLE random_hierarchy_images_tmp");

$GLOBALS['db_connection']->load_data_infile($outfile, 'random_hierarchy_images_tmp');
unlink($outfile);

$result = $GLOBALS['db_connection']->query("SELECT 1 FROM random_hierarchy_images_tmp LIMIT 1");
if($result && $row=$result->fetch_assoc())
{
    $GLOBALS['db_connection']->swap_tables("random_hierarchy_images", "random_hierarchy_images_tmp");
}

$log->finished();

?>
