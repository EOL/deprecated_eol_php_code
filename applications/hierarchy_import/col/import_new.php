<?php
namespace php_active_record;

include_once(dirname(__FILE__)."/../../../config/environment.php");


$mysqli =& $GLOBALS['mysqli_connection'];
$mysqli_col = load_mysql_environment("icol2011ac");

require_once('col_importer.php');






$importer = new COLImporter($mysqli_col);


// 
// 
// echo COLImporter::get_memory_in_mb() . "\n";
// $taxon_ranks = array();
// $taxon_ranks2 = array();
// $result = $mysqli_col->query("SELECT id, taxonomic_rank_id, source_database_id FROM taxon");
// while($result && $row=$result->fetch_assoc())
// {
//     $taxon_ranks[$row['id']] = $row['taxonomic_rank_id'];
//     $taxon_ranks2[$row['id']] = $row['source_database_id'];
// }
// echo COLImporter::get_memory_in_mb() . "\n";
// 



?>