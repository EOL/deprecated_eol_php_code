<?php
namespace php_active_record;
/* MycoBank Classification - uses their webservice to get their entire classification of > 400k current name and synonyms
estimated execution time: 

fixed invalid parent_id: 
http://rs.tdwg.org/dwc/terms/taxon: Total: 413135

$names = array("Fungi", "Ascomycota", "Pezizomycotina", "Dothideomycetes", "Dothideomycetidae", "Capnodiales", "Mycosphaerellaceae", "Sphaerella", "Sphaerella tini");
$names = array("Fungi", "Basidiomycota", "Agaricomycotina", "Agaricomycetes", "Agaricomycetidae", "Agaricales", "Marasmiaceae", "Chamaeceras", "Chamaeceras brasiliensis", "Marasmius");
$names = array("Selenia perforans", "Selenia", "Montagnula perforans");

http://www.eol.org/content_partners/614/resources/671
*/
return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MycoBankAPI');
$timestart = time_elapsed();
$resource_id = 671;
$func = new MycoBankAPI($resource_id);
$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function utility_append_text_loop()
{
    echo "\n backing up first...";
    $filename = DOC_ROOT . "/public/tmp/mycobank/mycobank_dump.txt";
    copy($filename, DOC_ROOT . "/public/tmp/mycobank/mycobank_dump_backup.txt");
    echo "\n backup done. \n";
    for ($x=1; $x <= 1; $x++)
    {
        $str = Functions::format_number_with_leading_zeros($x, "2");
        $filename = DOC_ROOT . "/public/tmp/mycobank/mycobank_dump_add" . $str . ".txt";
        if(!($READ = Functions::file_open($filename, "r"))) return;
        $contents = fread($READ, filesize($filename));
        fclose($READ);
        echo "\n copying... $filename";
        $filename = DOC_ROOT . "/public/tmp/mycobank/mycobank_dump.txt";
        echo "\n to... $filename\n";
        if(!($WRITE = Functions::file_open($filename, "a"))) return;
        fwrite($WRITE, $contents);
        fclose($WRITE);
    }
}

?>