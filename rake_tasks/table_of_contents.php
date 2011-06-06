<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$log = HarvestProcessLog::create(array('process_name' => 'Table of Contents'));


$outfile_no_info_items = $GLOBALS['db_connection']->select_into_outfile("SELECT dotoc.* FROM data_objects_table_of_contents dotoc LEFT JOIN data_objects_info_items doii USING (data_object_id) where doii.data_object_id IS NULL");
$outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT doii.data_object_id, ii.toc_id FROM data_objects_info_items doii JOIN info_items ii ON (doii.info_item_id = ii.id) WHERE ii.toc_id!=0");
if(@filesize($outfile_no_info_items) && @filesize($outfile))
{
    $GLOBALS['db_connection']->begin_transaction();
    $GLOBALS['db_connection']->delete("TRUNCATE TABLE data_objects_table_of_contents");
    //$GLOBALS['db_connection']->delete_from_where('data_objects_table_of_contents', 'data_object_id', "SELECT data_object_id FROM data_objects_info_items");
    $GLOBALS['db_connection']->load_data_infile($outfile_no_info_items, 'data_objects_table_of_contents');
    $GLOBALS['db_connection']->load_data_infile($outfile, 'data_objects_table_of_contents');
    $GLOBALS['db_connection']->end_transaction();
}
unlink($outfile_no_info_items);
unlink($outfile);

$log->finished();

?>