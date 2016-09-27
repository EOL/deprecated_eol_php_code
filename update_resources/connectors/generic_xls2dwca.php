<?php
namespace php_active_record;
/* This is a generic connector for a spreadsheet resource
execution time: varies on how big the spreadsheet is
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EOLSpreadsheetToArchiveAPI');
$timestart = time_elapsed();
ini_set('memory_limit', '5120M'); // 5GB maximum memory usage
$spreadsheets = array();
$big_file = false;

// Sarah Miller's spreadsheets
/*
$spreadsheets[] = 'https://www.dropbox.com/s/9b4ie0g024wszy6/carnivore dinosaurs.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/yiywpg5pyc3w102/EGG CHARACTERISTICS AND BREEDING SEASON FOR WOODS HOLE SPECIES.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/qx4ol1sugo6lqdw/Milk Traitbank Import.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/fh0jz5n4p0b4g39/Dragonfly Other Mes.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/tpg23r34brmrmmf/Life history data of lizards of the world export.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/zjxx83uj4ogo0fn/Social systems of mammalian species Transfer.xlsx?dl=0';

[Social systems of mammalian species Transfer](https://www.dropbox.com/s/zjxx83uj4ogo0fn/Social systems of mammalian species Transfer.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Social_systems_of_mammalian_species_Transfer.tar.gz)
[Life history data of lizards of the world export](https://www.dropbox.com/s/tpg23r34brmrmmf/Life history data of lizards of the world export.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Life_history_data_of_lizards_of_the_world_export.tar.gz)
[Dragonfly Other Mes](https://www.dropbox.com/s/fh0jz5n4p0b4g39/Dragonfly Other Mes.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Dragonfly_Other_Mes.tar.gz)
[Milk Traitbank Import](https://www.dropbox.com/s/qx4ol1sugo6lqdw/Milk Traitbank Import.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Milk_Traitbank_Import.tar.gz)
[EGG CHARACTERISTICS AND BREEDING SEASON FOR WOODS HOLE SPECIES](https://www.dropbox.com/s/yiywpg5pyc3w102/EGG CHARACTERISTICS AND BREEDING SEASON FOR WOODS HOLE SPECIES.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/EGG_CHARACTERISTICS_AND_BREEDING_SEASON_FOR_WOODS_HOLE_SPECIES.tar.gz)
[carnivore dinosaurs](https://www.dropbox.com/s/9b4ie0g024wszy6/carnivore dinosaurs.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/carnivore_dinosaurs.tar.gz)

[xxx](spreadsheet) is VALID - [DWC-A](dwca)
*/

/*
$spreadsheets[] = 'https://www.dropbox.com/s/llska2bdzl0elo1/climates.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/faxizw1w9lo9j1e/dana dinosaur transfer.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/wyrr5o56feycbaj/DC Flower Export.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/ximiz5oiedvgdee/eastern export.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/sb6snwmg3f3mu9j/Eggs copy.xlsx?dl=0';

[Eggs copy](https://www.dropbox.com/s/sb6snwmg3f3mu9j/Eggs copy.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Eggs_copy.tar.gz)
[eastern export](https://www.dropbox.com/s/ximiz5oiedvgdee/eastern export.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/eastern_export.tar.gz)
[DC Flower Export](https://www.dropbox.com/s/wyrr5o56feycbaj/DC Flower Export.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/DC_Flower_Export.tar.gz)
[dana dinosaur transfer](https://www.dropbox.com/s/faxizw1w9lo9j1e/dana dinosaur transfer.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/dana_dinosaur_transfer.tar.gz)
[climates](https://www.dropbox.com/s/llska2bdzl0elo1/climates.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/climates.tar.gz)
*/

/*
$spreadsheets[] = 'https://www.dropbox.com/s/vaysqf4w2aok34j/Eggs.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/tkd9xijxrkp509a/Macroecological mammalian body mass copy.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/om3fhew94hx2kva/Male tenure length and variance in lifetime reproductive success recorded for mammals Transfer.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/nm50iktes7cg6kw/Reptile Export.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/li8l8r18bjpzbi9/Mikesell phenological data.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/vtehcwnkcbfhvby/PterosaurData Transfer.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/2j3zxjmhurckd6x/Avian Mass Export.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/kawt45g3auvrvfl/Parrot Fish.xlsx?dl=0';

[Parrot Fish](https://www.dropbox.com/s/kawt45g3auvrvfl/Parrot Fish.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Parrot_Fish.tar.gz)
[Avian Mass Export](https://www.dropbox.com/s/2j3zxjmhurckd6x/Avian Mass Export.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Avian_Mass_Export.tar.gz)
[PterosaurData Transfer](https://www.dropbox.com/s/vtehcwnkcbfhvby/PterosaurData Transfer.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/PterosaurData_Transfer.tar.gz)
[Mikesell phenological data](https://www.dropbox.com/s/li8l8r18bjpzbi9/Mikesell phenological data.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Mikesell_phenological_data.tar.gz)
[Reptile Export](https://www.dropbox.com/s/nm50iktes7cg6kw/Reptile Export.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Reptile_Export.tar.gz)
[Male tenure length and variance in lifetime reproductive success recorded for mammals Transfer](https://www.dropbox.com/s/om3fhew94hx2kva/Male tenure length and variance in lifetime reproductive success recorded for mammals Transfer.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Male_tenure_length_and_variance_in_lifetime_reproductive_success_recorded_for_mammals_Transfer.tar.gz)
[Macroecological mammalian body mass copy](https://www.dropbox.com/s/tkd9xijxrkp509a/Macroecological mammalian body mass copy.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Macroecological_mammalian_body_mass_copy.tar.gz)
[Eggs](https://www.dropbox.com/s/vaysqf4w2aok34j/Eggs.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Eggs.tar.gz)
*/

/*
$spreadsheets[] = 'https://www.dropbox.com/s/0rfmqrj97v6e37y/Dragonflies Measurements 2.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/o97ue4gkya9s6br/Life history characteristics of placental non-volant mammals.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/y77wsxfdo22c6t0/Coral Skeletons.xlsx?dl=0';

[Coral Skeletons](https://www.dropbox.com/s/y77wsxfdo22c6t0/Coral Skeletons.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Coral_Skeletons.tar.gz)
[Life history characteristics of placental non-volant mammals](https://www.dropbox.com/s/o97ue4gkya9s6br/Life history characteristics of placental non-volant mammals.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Life_history_characteristics_of_placental_non-volant_mammals.tar.gz)
[Dragonflies Measurements 2](https://www.dropbox.com/s/0rfmqrj97v6e37y/Dragonflies Measurements 2.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Dragonflies_Measurements_2.tar.gz)
*/

/* Jen's spreadsheets
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 22/Barton Finkel 2013.xlsx';
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 22/Barton Pershing 2013.xlsx';
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 22/Olenina 2006.xlsx';
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 24/EGG CHARACTERISTICS AND BREEDING SEASON FOR WOODS HOLE SPECIES.xls';
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 03 28/Avian body sizes in relation to fecundity%2C mating system%2C display behavior%2C and resource sharing Export.xls';
$spreadsheets[] = 'https://www.dropbox.com/s/bnrbmrttgithwa1/Avian body sizes in relation to fecundity%2C mating system%2C display behavior%2C and resource sharing Export.xls?dl=0';
*/

/*
// the big spreadsheets: moved to 799.php
$spreadsheets[] = 'https://www.dropbox.com/s/u17km6pnylf6cx3/WWF 2.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/k49tww9xgb2xd8k/WWF.xlsx?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/bnrbmrttgithwa1/Avian body sizes in relation to fecundity%2C mating system%2C display behavior%2C and resource sharing Export.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/dlybjsx410h90rh/WWF Habitats.xlsx?dl=0';
$big_file = true;
*/

/*
// $spreadsheets[] = 'https://www.dropbox.com/s/kxt9ypcxa4ttj05/Bird Incubation.xlsx?dl=0';
// $spreadsheets[] = 'https://www.dropbox.com/s/ys8l3vwzkljxcsv/Toxic Set finalish.xlsx?dl=0';
// [Toxic Set finalish.xlsx](https://www.dropbox.com/s/ys8l3vwzkljxcsv/Toxic Set finalish.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Toxic_Set_finalish.tar.gz)
// [Incubation.xlsx](https://www.dropbox.com/s/kxt9ypcxa4ttj05/Bird Incubation.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Bird_Incubation.tar.gz)
$spreadsheets[] = 'https://www.dropbox.com/s/xknmozz5mlnl88u/climates.xls?dl=0';
Please see [undefined URIs](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/undefinedURIs_2016_04_06.txt) from [WFF Regions version 2.xlsx](https://www.dropbox.com/s/k5yzq5jv5hd1p2s/WFF Regions version 2.xlsx?dl=0)
[climates.xls](https://www.dropbox.com/s/xknmozz5mlnl88u/climates.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/climates.tar.gz)
*/

/* 8-April-2016
// $spreadsheets[] = 'https://www.dropbox.com/s/xknmozz5mlnl88u/climates.xls?dl=0';
// $spreadsheets[] =  'http://localhost/eol_php_code/public/tmp/xls/big/spreadsheets/climates.xls';
// [climates.xls](https://www.dropbox.com/s/xknmozz5mlnl88u/climates.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/climates.tar.gz)
// $spreadsheets[] =  'https://www.dropbox.com/s/k39t8hcvacyww9v/Coral Skeletons.xlsx?dl=0';
// [Coral Skeletons.xlsx](https://www.dropbox.com/s/k39t8hcvacyww9v/Coral Skeletons.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Coral_Skeletons.tar.gz)
*/

/*
// $spreadsheets[] = 'https://www.dropbox.com/s/tt1tqajojkyzsy5/bioluminescent.xls?dl=0';
$spreadsheets[] = 'https://www.dropbox.com/s/mui6yyhssln7jr3/bioluminescent.xlsx?dl=0';
[bioluminescent.xlsx](https://www.dropbox.com/s/mui6yyhssln7jr3/bioluminescent.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/JenH/bioluminescent.tar.gz)
*/

/*
// $spreadsheets[] = 'https://www.dropbox.com/s/wb64tp7bpjmlnq8/Dragonfly%20Locality.xlsx?dl=0';
$spreadsheets[] = 'http://localhost/eol_php_code/public/tmp/xls/2016 04 18/Dragonfly%20Locality.xlsx';
[Dragonfly Locality.xlsx](https://www.dropbox.com/s/wb64tp7bpjmlnq8/Dragonfly%20Locality.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/SarahM/Dragonfly_Locality.tar.gz)
*/

/*
$spreadsheets[] = 'https://www.dropbox.com/s/lh79w1egk4orxn3/DCBirds_eol_import_spreadsheet.xls?dl=0';
[DCBirds_eol_import_spreadsheet.xls](https://www.dropbox.com/s/lh79w1egk4orxn3/DCBirds_eol_import_spreadsheet.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/JenH/DCBirds_eol_import_spreadsheet.tar.gz)
*/

/* 27-Apr-2016
// $spreadsheets[] = 'https://www.dropbox.com/s/bksnchuqlzeu0bj/EdwardsEtAl2015.xlsx?dl=0';
// $spreadsheets[] = 'https://www.dropbox.com/s/e1uzf1hcrdi4nbr/animal%20seed%20size.xlsx?dl=0';
$spreadsheets[] = 'http://localhost/cp/spreadsheets/animal%20seed%20size.xlsx';
// $spreadsheets[] = 'https://www.dropbox.com/s/ioevc9xye354us9/Benedetti_2015.xlsx?dl=0';
[animal seed size.xlsx](https://www.dropbox.com/s/e1uzf1hcrdi4nbr/animal%20seed%20size.xlsx?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/JenH/animal_seed_size.tar.gz)
*/

/* May 5, 2016 - from Jen
$spreadsheets[] = 'https://www.dropbox.com/s/1529pseqma8b4c3/scleractinia%20lifestye.xls?dl=0';
[scleractinia lifestye.xls](https://www.dropbox.com/s/1529pseqma8b4c3/scleractinia%20lifestye.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/JenH/scleractinia_lifestye.tar.gz)
*/

// /* May 27, 2016 - from Jen
$spreadsheets[] = 'https://www.dropbox.com/s/bwjluijh3npl29i/dcBirds_eol_import_spreadsheet_2.xls?dl=0';
// [dcBirds_eol_import_spreadsheet_2.xls](https://www.dropbox.com/s/bwjluijh3npl29i/dcBirds_eol_import_spreadsheet_2.xls?dl=0) is VALID - [DWC-A](https://dl.dropboxusercontent.com/u/7597512/spreadsheets/resources/JenH/dcBirds_eol_import_spreadsheet_2.tar.gz)
// */

print_r($spreadsheets);
foreach($spreadsheets as $spreadsheet)
{
    $resource_id = str_replace(" ", "_", urldecode(pathinfo($spreadsheet, PATHINFO_FILENAME)));
    echo "\n[$resource_id]";
    $func = new EOLSpreadsheetToArchiveAPI($resource_id);
    $func->convert_to_dwca($spreadsheet);
    /* $func->convert_to_text_to_dwca($spreadsheet);working but not being used... since XLSParser still loads entire worksheet into memory */
    Functions::finalize_dwca_resource($resource_id, $big_file);
    $elapsed_time_sec = time_elapsed() - $timestart;
    echo "\n\n";
    echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
    echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
    echo "\nDone processing.\n";
}
?>
