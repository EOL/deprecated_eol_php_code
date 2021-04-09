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
$input = array('filename' => 'SCtZ-0011.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/',
                'lines_before_and_after_sciname' => 2);
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
wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0276/SCtZ-0276.txt

SCtZ-0103
wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0103/SCtZ-0103.txt
Gammaridean Amphipoda of Australia, Part III. The Phoxocephalidae
Gammaridean Amphipoda of Australia, Part I
*/

wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0280/SCtZ-0280.txt

The planarians (Turbellaria, Tricladida Paludicola) of Lake Ohrid in Macedonia
The Planarians (Turbellaria: Tricladida Paludicola) of Lake Ohrid in Macedonia
SCtZ-0280

The Scarabaeoides of Dominica (Coleoptera) : Bredin-Archbold-Smithsonian biological survey	
BREDIN-ARCHBOLD-SMITHSONIAN BIOLOGICAL SURVEY OF DOMINICA: The Superfamily Scarabaeoidea (Coleoptera)
SCtZ-0279

Shallow-water Pycnogonida of the Isthmus of Panamá and the coasts of Middle America	
Shallow-Water Pycnogonida of the Isthmus of Panama and the Coasts of Middle America
SCtZ-0293

New and little-known Crayfish of the virilis Section of the Genus Orconectes (Decapoda: Cambaridae) from the Southeastern United States	
New and Little-known Crayfishes of the virilis Section of Genus Orconectes (Decapoda: Cambaridae) from the Southeastern United States
SCtZ-0320

The tribe Alasmidontini (Unionidae, Anodontinae), Part I: Pegias, Alasmidonta and Arcidens	
The Tribe Alasmidontini (Unionidae: Anodontinae), Part I: Pegias, Alasmidonta, and Arcidens
SCtZ-0326

Studies of Ephydrinae (Diptera: Ephydridae), V: Systematics, Phylogeny, and Natural History of the Genera Cirrula Cresson and Dimecoenia Cresson in North America	
Studies of Ephydrinae (Diptera: Ephydridae), V: The Genera Cirrula Cresson and Dimecoenia Cresson in North America
SCtZ-0329

The ecology and behavior of Nephila maculata : a supplement
The Ecology and Behavior of Nephila maculata: A Supplement
SCtZ-0218

The Entocytherid Ostracods from North Carolina
The Entocytherid Ostracods of North Carolina
SCtZ-0247

Socio ecology of the blue monkeys (Cercopithecus mitis stuhlmanni) of the Kibale Forest, Uganda	
Socioecology of the Blue Monkeys (Cercopithecus mitis stuhlmanni) of the Kibale Forest, Uganda
SCtZ-0249

A further contribution of knowledge of the host relations of the parasitic cowbirds	
A Further Contribution to Knowledge of the Host Relations of the Parasitic Cowbirds
SCtZ-0235

The North American Moths of the General Phaeoses, Opogona, and Oinophila, with a Discussion of their Supergeneric Affinities (Lepidoptera: Tineidae)	
The North American Moths of the Genera Phaeoses, Opogona, and Oinophila, with a Discussion of Their Supergeneric Affinities (Lepidoptera: Tineidae)
SCtZ-0282

Gammaridean Amphipoda of Australia, Part III. the Phoxocephalidae
Gammaridean Amphipoda of Australia, Part III: The Phoxocephalidae
SCtZ-0245

A catalogue of the type-specimens of Recent cephalopods in the National Museum of Natural History
A Catalog of the Type-Specimens of Recent Cephalopoda in the National Museum of Natural History
SCtZ-0278

Revision of the pelagic amphipod genus Primo (Hyperiidea: Phrosinidae)
Revision of the Pelagic Amphipod Genus Primno (Hyperiidea: Phrosinidae)
SCTZ-0275

Anuran locomotion-structure and function
Anuran Locomotion—Structure and Function, 2: Jumping Performance of Semiaquatic, Terrestrial, and Arboreal Frogs
SCTZ-0276

Neotropical Microlepidoptera, XXI : new genera and species of Oecophoridae from Chile
Neotropical Microlepidoptera, XXI: New Genera and Species of Oecophoridae from Chile
SCtZ-0273


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>