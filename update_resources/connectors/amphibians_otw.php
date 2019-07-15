<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-811
estimated execution time:
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AmphibiansOfTheWorldAPI');
$timestart = time_elapsed();

/* testing
$str = "Bolkay, 1912 , Mitt. Jahrb. K. Ungar. Geol. Anst., 19"; // >>> Bolkay, 1912
// $str = "Pillai and Chanda, 1973, Proc. Indian Acad. Sci., Ser. B, 78"; //  >>> Pillai and Chanda, 1973
// $str = "Sarkar and Ray, 2006, In Alfred (ed.), Fauna of Arunachal Pradesh, Part 1"; //  >>> Sarkar and Ray, 2006
// $str = "Vijayakumar, Dinesh, Prabhu, and Shanker, 2014, Zootaxa, 3893"; //  >>> Vijayakumar, Dinesh, Prabhu, and Shanker, 2014
// $str = "Boettger, 1880, Ber. Senckenb. Naturforsch. Ges., 1879–80"; //  >>> Boettger, 1880

$authority = get_authority_from_str($str);
echo "\n[$str]\n[$authority]\n";
exit("\nend muna\n");
*/


// /* normal operation
$resource_id = "aotw"; //amphibians of the world
$func = new AmphibiansOfTheWorldAPI($resource_id);
$func->start();
unset($func);
// Functions::finalize_dwca_resource($resource_id);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
