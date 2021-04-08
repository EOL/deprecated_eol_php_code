<?php
namespace php_active_record;
/* DATA-1877: textmining more unstructured text
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ParseListTypeAPI');
require_library('connectors/ParseUnstructuredTextAPI');
$timestart = time_elapsed();
$func = new ParseUnstructuredTextAPI();

/* test
$str = "eli is 123 but cha is 23 and isaiah is 3";
if(preg_match_all('/\d+/', $str, $a)) //print_r($a[0]);
{
    $arr = $a[0];
    print_r($arr);
    foreach($arr as $num) {

        echo "\n$num is ".strlen($num)." digit(s)\n";
        
    }
}
exit("\n-end test-\n");
*/

/* test
$arr[] = 'aa';
$arr[] = 'bb';
print_r($arr);
$arr[] = 'cc';
$arr[] = 'dd';
$arr[] = 'ee';
print_r($arr);
array_shift($arr);
print_r($arr);
$arr[] = 'ff';
print_r($arr);
exit("\n-end test-\n");
*/

/* parsing result of PdfParser
$filename = 'pdf2text_output.txt';
$func->parse_text_file($filename);
*/
/* parsing result pf pdftotext (legacy xpdf in EOL codebase)
$filename = 'SCtZ-0293-Hi_res.txt';
$func->parse_pdftotext_result($filename);
*/
/* parsing
$filename = 'pdf2text_output.txt';
$func->parse_pdftotext_result($filename);
*/
/* parsing SCtZ-0293-Hi_res.html
$filename = 'SCtZ-0293-Hi_res.html';
$func->parse_pdf2htmlEX_result($filename);
*/
/* parsing SCZ637_pdftotext.txt
$filename = 'SCZ637_pdftotext.txt';
$func->parse_pdftotext_result($filename);
*/

// /* Start epub series: process our first file from the ticket
$input = array('filename' => 'SCtZ-0293.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0293/');
$input = array('filename' => 'SCtZ-0001.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0001/');
$input = array('filename' => 'SCtZ-0008.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0008/');
$input = array('filename' => 'SCtZ-0016.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0016/');
$input = array('filename' => 'SCtZ-0029.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0029/');
$input = array('filename' => 'SCtZ-0023.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0023/');
$input = array('filename' => 'SCtZ-0007.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/');
// $input = array('filename' => 'SCtZ-0025.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0025/');
// $input = array('filename' => 'SCtZ-0011.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');
// $input = array('filename' => 'SCtZ-0003.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0003/');

//List-type here:
$input = array('filename' => 'SCtZ-0011.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');
// $input = array('filename' => 'SCtZ-0033.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0033/');

$func->parse_pdftotext_result($input);
// */

/* a utility
$func->utility_download_txt_files();
*/
/*
Real misfiled:
Taxonomy, sexual dimorphism, vertical distribution, and evolutionary zoogeography of the bathypelagic fish genus Stomias (Stomiatidae)
SCtZ-0031

Ten Rhyparus from the Western Hemisphere (Coleoptera: Scarabaeidae: Aphodiinae)	
SCtZ-0021


wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0185/SCtZ-0185.txt

Bredin-Archibold-Smithsonian Biological Survey of Dominica: The family Dolichopodidae with some related Antillean and Panamian species (Diptera)	
BREDIN-ARCHBOLD-SMITHSONIAN BIOLOGICAL SURVEY OF DOMINICA
The Family Dolichopodidae with Some Related Antillean and Panamanian Species (Diptera)
SCtZ-0185

Antarctic Ostracoda (Myodocopina) Parts 1 and 2
Antarctic Ostracoda (Myodocopina) [IN TWO PARTS]
SCtZ-0163.1

Revision of the blenniid fish genus Omobranchus, with descriptions of three new species and notes on other species of the tribe Omobranchini
Revision of the Blenniid Fish Genus Omobranchus with Descriptions of Three New Species and Notes on Other Species of the Tribe Omobranchini
SCtZ-0177

Revision of the stonefly family Nemouridae (Plecoptera) : a study of the world fauna at the generic level
Revision of the Stonefly Family Nemouridae (Plecoptera): A Study of the World Fauna at the Generic Level
SCtZ-0211

Habitats, Flora, Mammals, and Wasps of Gebel Uweinat, Libyan Desert
Habitats, Flora, Mammals, and Wasps of Gebel ’Uweinat, Libyan Desert
SCtZ-0011

Nearctic Walshiidae: notes and new taxa (Lepidoptera: Gelechioidea)
Nearctic Walshiidae Notes and New Taxa (Lepidoptera: Gelechioidea)
SCtZ-0018

Bredin-Archbold-Smithsonian biological survey of Dominica: West Indian Stenomidae (Lepidoptera: Gelechioidea)
Bredin-Archbold-
Smithsonian Biological
Survey of Dominica:
West Indian Stenomidae
(Lepidoptera:
Gelechioidea)
SCtZ-0004

Bredin-Archbold-Smithsonian biological survey of Dominica: Bethyloidea (Hymenoptera)
Bredin-Archbold-
Smithsonian Biological
Survey of Dominica:
Bethyloidea
(Hymenoptera)
SCtZ-0003

A monograph of the Cephalopoda of the North Atlantic: The Family Cycloteuthidae
A Monograph of the
Cephalopoda of the North Atlantic: The Family Cycloteuthidae
SCtZ-0005

Indian Ocean Kinorhyncha: 1. Condyloderes and Sphenoderes, new cyclorhagid genera
Indian Ocean Kinorhyncha: 1, Condyloderes and Sphenoderes, New Cyclorhagid Genera.
SCtZ-0014

Notes on some stomatopod Crustacea from southern Africa
Notes on Some
Stomatopod Crustacea
from
Southern Africa
SCtZ-0001

The Avifauna of northern Latin America: a symposium held at the Smithsonian Institution, 13-15 April 1966
The Avifauna of Northern Latin America
A Symposium Held at the Smithsonian Institution 13–15 April 1966
SCtZ-0026

Some behavior patterns of platyrrhine monkeys: II. Saguinus geoffroyi and some other tamarins
Some Behavior Patterns of Platyrrhine Monkeys II. Saguinus geoffroyi and Some Other Tamarins
SCtZ-0028

Copepods parasites on sharks from the west coast of Florida
Copepods Parasitic on Sharks from the West Coast of Florida
SCtZ-0038

Systematics, distribution, and evolution of the chub genus Nocomis Girard (Pisces, Cyprinidae) of eastern United States, with descriptions of new species
Systematics, Distribution, and Evolution the Chub Genus Nocomis Girard (Pisces, Cyprinidae) of Eastern United States, With Descriptions of New Species
SCtZ-0085

Systematics of the subterranean amphipod genus Stygobromus (Gammaridae) : Part I. Species of the western United States
Systematics of the Subterranean Amphipod Genus Stygobromus (Gammaridae), Part I: Species of the Western United States
SCtZ-0160

Studies of Neotropical Caddisflies XVIII: New Species of Rhyacophilidae and Glossosomatidae (Trichoptera)
Studies of Neotropical Caddisflies, XVIII: New Species of Rhyacophilidae and Glossosomatidae (Trichoptera)
SCtZ-0169

Studies of Neotropical Caddisflies XVII: The Genus Smicridea from North and Central America (Trichoptera: Hydropsychidae)
Studies of Neotropical Caddisflies, XVII: The Genus Smicridea from North and Central America (Trichoptera: Hydropsychidae)
SCtZ-0167

A review of the genus Cancellus (Crustacea: Diogenidae), with the description of a new species from the Caribbean Sea
A Review of the Genus Cancellus (Crustacea: Diogenidae) with the Description of a New Species from the Caribbean Sea
SCtZ-0150

Western Atlantic Shrimps of the Genus Solencera with Description of a New Species (Crustacea: Decapoda: Penaeidae)
Western Atlantic Shrimps of the Genus Solenocera with Description of a New Species (Crustacea: Decapoda: Penaeidae)
SCtZ-0153

The Subgenera of the Crayfish Genus Procambarus (Decapoda, Astacidae)
The Subgenera of the Crayfish Genus Procambarus (Decapoda: Astacidae)
SCtZ-0117

Keys to the species of Oratosquilla (Crustacea, Stomatopoda), with descriptions of two new species
Keys to the of Oratosquilla (Crustacea: Stomatopoda), with Descriptions of Two New Species
SCtZ-0071

Bredin-Archbold Smithsonian Biological Survey of Dominica: Burrowing sponges, Genus Siphonodictyon Bergquist, from the Caribbean
Bredin-Archbold-Smithsonian Biological Survey of Dominica: Burrowing Sponges, Genus Siphonodictyon Bergquist, From The Caribbean
SCtZ-0077

Keys to the Hawaiian marine Gammaridea, 0-30 meters
Keys to the Hawaiian Marine Gammaridea, 0–30 Meters
SCtZ-0058

Bredin--Archbold--Smithsonian biological survey of Dominica: Bostrichidae, Inopeplidae, Lagriidae, Lyctidae, Lymexylonidae, Melandryidae, Monommidae, Rhipiceridae, and Rhipiphoridae (Coleoptera)
Bredin-Archbold-Smithsonian Biological Survey of Dominica: Bostrichidae, Inopeplidae, Lagriidae, Lyctidae, Lymexylonidae, Melandryidae, Monommidae, Rhipiceridae, and Rhipiphoridae (Coleoptera)
SCtZ-0070
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>