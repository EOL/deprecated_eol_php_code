<?php
namespace php_active_record;
/* This is a generic connector for a spreadsheet resource
execution time: varies on how big the spreadsheet is
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EOLSpreadsheetToArchiveAPI');
$timestart = time_elapsed();

$spreadsheets = array();

// /* Sarah Miller's spreadsheets

// $spreadsheets[] = 'https://www.dropbox.com/s/u17km6pnylf6cx3/WWF 2.xlsx?dl=0';
// $spreadsheets[] = 'https://www.dropbox.com/s/k49tww9xgb2xd8k/WWF.xlsx?dl=0';
// $spreadsheets[] = 'https://www.dropbox.com/s/bnrbmrttgithwa1/Avian body sizes in relation to fecundity%2C mating system%2C display behavior%2C and resource sharing Export.xls?dl=0';
// $spreadsheets[] = 'https://www.dropbox.com/s/dlybjsx410h90rh/WWF Habitats.xlsx?dl=0';

$spreadsheets[] = 'https://www.dropbox.com/s/9b4ie0g024wszy6/carnivore dinosaurs.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/llska2bdzl0elo1/climates.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/faxizw1w9lo9j1e/dana dinosaur transfer.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/wyrr5o56feycbaj/DC Flower Export.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/ximiz5oiedvgdee/eastern export.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/yiywpg5pyc3w102/EGG CHARACTERISTICS AND BREEDING SEASON FOR WOODS HOLE SPECIES.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/sb6snwmg3f3mu9j/Eggs copy.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/vaysqf4w2aok34j/Eggs.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/o97ue4gkya9s6br/Life history characteristics of placental non-volant mammals.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/tkd9xijxrkp509a/Macroecological mammalian body mass copy.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/om3fhew94hx2kva/Male tenure length and variance in lifetime reproductive success recorded for mammals Transfer.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/qx4ol1sugo6lqdw/Milk Traitbank Import.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/nm50iktes7cg6kw/Reptile Export.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/fh0jz5n4p0b4g39/Dragonfly Other Mes.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/2j3zxjmhurckd6x/Avian Mass Export.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/y77wsxfdo22c6t0/Coral Skeletons.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/tpg23r34brmrmmf/Life history data of lizards of the world export.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/li8l8r18bjpzbi9/Mikesell phenological data.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/kawt45g3auvrvfl/Parrot Fish.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/vtehcwnkcbfhvby/PterosaurData Transfer.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/zjxx83uj4ogo0fn/Social systems of mammalian species Transfer.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/0rfmqrj97v6e37y/Dragonflies Measurements 2.xlsx?dl=0';
// */

/* Jen's spreadsheets
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 22/Barton Finkel 2013.xlsx';
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 22/Barton Pershing 2013.xlsx';
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 22/Olenina 2006.xlsx';
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 24/EGG CHARACTERISTICS AND BREEDING SEASON FOR WOODS HOLE SPECIES.xls';
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 28/Avian body sizes in relation to fecundity%2C mating system%2C display behavior%2C and resource sharing Export.xls';
$spreadsheets[] = 'https://www.dropbox.com/s/bnrbmrttgithwa1/Avian body sizes in relation to fecundity%2C mating system%2C display behavior%2C and resource sharing Export.xls?dl=0';
*/

print_r($spreadsheets);
foreach($spreadsheets as $spreadsheet)
{
    $resource_id = str_replace(" ", "_", urldecode(pathinfo($spreadsheet, PATHINFO_FILENAME)));
    echo "\n[$resource_id]";
    $func = new EOLSpreadsheetToArchiveAPI($resource_id);
    $func->convert_to_dwca($spreadsheet);
    Functions::finalize_dwca_resource($resource_id);
    $elapsed_time_sec = time_elapsed() - $timestart;
    echo "\n\n";
    echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
    echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
    echo "\nDone processing.\n";
}

?>
