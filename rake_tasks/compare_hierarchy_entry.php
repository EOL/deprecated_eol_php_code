<?php
exit;
namespace php_active_record;

$id = @$argv[1];

if(!$id || !is_numeric($id))
{
    echo "\n\n\tcompare_hierarchy_entries.php [id]\n\n";
    exit;
}


include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

CompareHierarchies::test_compare_single_entry($id);


?>