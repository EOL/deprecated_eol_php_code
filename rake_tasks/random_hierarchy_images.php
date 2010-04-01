<?php

include_once(dirname(__FILE__) . "/../config/environment.php");


Functions::log("Starting random_hierarchy_images");

$species_rank_ids_array = array();
if($id = Rank::find('species')) $species_rank_ids_array[] = $id;
if($id = Rank::find('sp')) $species_rank_ids_array[] = $id;
if($id = Rank::find('sp.')) $species_rank_ids_array[] = $id;
if($id = Rank::find('subspecies')) $species_rank_ids_array[] = $id;
if($id = Rank::find('subsp')) $species_rank_ids_array[] = $id;
if($id = Rank::find('subsp.')) $species_rank_ids_array[] = $id;
if($id = Rank::find('variety')) $species_rank_ids_array[] = $id;
if($id = Rank::find('var')) $species_rank_ids_array[] = $id;
if($id = Rank::find('var.')) $species_rank_ids_array[] = $id;
if($id = Rank::find('infraspecies')) $species_rank_ids_array[] = $id;
if($id = Rank::find('form')) $species_rank_ids_array[] = $id;
if($id = Rank::find('nothospecies')) $species_rank_ids_array[] = $id;
if($id = Rank::find('nothosubspecies')) $species_rank_ids_array[] = $id;
if($id = Rank::find('nothovariety')) $species_rank_ids_array[] = $id;
$species_rank_ids = implode(",", $species_rank_ids_array);

$outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT distinct NULL, tcc.image_object_id, he.id, he.hierarchy_id, he.taxon_concept_id, n.italicized name FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN data_objects do ON (tcc.image_object_id=do.id) LEFT JOIN names n ON (he.name_id=n.id) WHERE tc.published=1 AND tc.vetted_id=".Vetted::insert("Trusted")." AND (he.lft=he.rgt-1 OR he.rank_id IN ($species_rank_ids)) AND tcc.image=1 AND do.vetted_id=".Vetted::insert("Trusted"));

file_randomize($outfile);

$GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS random_hierarchy_images_tmp LIKE random_hierarchy_images");
$GLOBALS['db_connection']->delete("TRUNCATE TABLE random_hierarchy_images_tmp");

$GLOBALS['db_connection']->load_data_infile($outfile, 'random_hierarchy_images_tmp');
$GLOBALS['db_connection']->swap_tables("random_hierarchy_images", "random_hierarchy_images_tmp");


unlink($outfile);
Functions::log("Ended random_hierarchy_images");


?>