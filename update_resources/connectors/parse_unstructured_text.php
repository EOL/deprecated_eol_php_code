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
/* var lines_before_and_after_sciname is important. It is the lines before and after the "list header". */
$input = array('filename' => 'SCtZ-0033.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0033/');
// $input = array('filename' => 'SCtZ-0437.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/'); //List of Freshwater Fishes of Peru
$input = array('filename' => 'SCtZ-0011.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');

// $input = array('filename' => 'SCtZ-0018.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0018/');
//-> this has genus in one line and species in 2nd line

$input['lines_before_and_after_sciname'] = 2;

if($input['filename'] == 'SCtZ-0018.txt') $input['lines_before_and_after_sciname'] = 1;


if(Functions::is_production()) $input['epub_output_txts_dir'] = str_replace("/Volumes/AKiTiO4/other_files/Smithsonian/", "/extra/other_files/Smithsonian/", $input['epub_output_txts_dir']);

$func->parse_pdftotext_result($input);
// */

/* a utility
$func->utility_download_txt_files();
*/

/*
Real misfiled:
1. Taxonomy, sexual dimorphism, vertical distribution, and evolutionary zoogeography of the bathypelagic fish genus Stomias (Stomiatidae)
SCtZ-0031
2. Ten Rhyparus from the Western Hemisphere (Coleoptera: Scarabaeidae: Aphodiinae)	
SCtZ-0021
3.Gammaridean Amphipoda of Australia, Part III. The Phoxocephalidae
Gammaridean Amphipoda of Australia, Part I
SCtZ-0103
4.The Caridean shrimps (Crustacea:Decapoda) of the Albatross Philippine Expedition, 1907-1910, Part 7: Families Atyidae, Eugonatonotidae, Rhynchocinetidae, Bathypalaemonidae, Processidae, and Hippolytidae
The Caridean Shrimps (Crustacea: Decapoda) of the Albatross Philippine Expedition, 1907–1910, Part 5: Family Alpheidae
SCTZ-0466

wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/SCtZ-0437.txt
*/
/*

A Revision of the Axinellidae (Porifera: Demospongiae) of the Central-West Atlantic Region
A Revision of Axinellidae (Porifera: Demospongiae) of the Central West Atlantic Region
SCtZ-0598

Systematics and Biogeography of cephalopods. Volume II
Systematics and Biogeography of Cephalopods
VOLUME II
SCtZ-0586.2

Systematics and Biogeography of cephalopods. Volume I
Systematics and Biogeography of Cephalopods
VOLUME I
SCtZ-0586.1

Ostracoda (Myodocopina) of Tulear reef complex, SW Madagascar
Ostracoda (Myodocopina) of Tuléar Reef Complex, SW Madagascar
SCtZ-0595 ???

Myodocopoid Ostracoda from Exuma Sound, Bahamas, and from the marine caves and blue holes in the Bahamas, Bermuda, and Mexico
Myodocopid Ostracoda from Exuma Sound, Bahamas, and from Marine Caves and Blue Holes in the Bahamas, Bermuda, and Mexico
SCtZ-0606

Biodiversity of Domatia Occupants (Ants, Wasps, Bees, and Others) of the Sri Lankan Myrmecophyte Humboldtia laurifolia Vahl (Fabaceae)
Biodiversity of the Domatia Occupants (Ants, Wasps, Bees, and Others) of the Sri Lankan Myrmecophyte Humboldtia laurifolia Vahl (Fabaceae)
SCtZ-0603

Jimmorinia a new genus of myodocopid Ostracoda (Cypridinidae) from the Bahamas, Jamaica, Honduras, and Panamá
Jimmorinia, a New Genus of Myodocopid Ostracoda (Cypridinidae) from the Bahamas, Jamaica, Honduras, and Panama
SCtZ-0605

Biology and Systematics of the North Phyllonorycter Leafminers on Salicaceae, with a Synoptical Catalogue of the Palearctic Species Lepidoptera: Gracillariidae)
Biology and Systematics of the North American Phyllonorycter Leafminers on Salicaceae, with a Synoptic Catalog of the Palearctic Species (Lepidoptera: Gracillariidae)
SCtZ-0614

Lace bug genera of the world, II : subfamily Tinginae : tribes Litadeini and Ypsotingini (Heteroptera:Tingidae)
Lace Bug Genera of the World, II: Subfamily Tinginae: Tribes Litadeini and Ypsotingini (Heteroptera: Tingidae)
SCtZ-0611

A monograph of the family Arrhenophanidea( Lepidoptera: Tineoidea)
A Monograph of the Family Arrhenophanidae (Lepidoptera: Tineoidea)
SCtZ-0620

Studies of Halictinae (Apoidea:Halictidae)
Studies of Halictinae (Apoidea: Halictidae), II: Revision of Sphecodogastra Ashmead, Floral Specialists of Onagraceae
SCtZ-0610

Catalogue of Type Specimens of Recent Crocodilia and Testudines in the National Museum of Natural History, Smithsonian Institution
Catalog of Type Specimens of Recent Crocodilia and Testudines in the National Museum of Natural History, Smithsonian Institution
scz-0626 ???

The neotropical fish family Chilodontidae (Teleostei: Characiformes) : a phylogenetic study and a revision of Caenotropus Günther
The Neotropical Fish Family Chilodontidae (Teleostei: Characiformes): A Phylogenetic Study and a Revision of Caenotropus Günther
SCtZ-0577

A review of hermit crabs of the genus Xylopagurus A. Milne Edwards, 1880 (Crustacea: Decapoda: Paguridae), with description of two new species
A Review of the Hermit Crabs of the Genus Xylopagurus A. Milne Edwards, 1880 (Crustacea: Decapoda: Paguridae), Including Descriptions of Two New Species
SCtZ-0570

Systematics of the trans-Andean species of Creagrutus (Ostariophysi:Characiformes:Characidae)
Systematics of the Trans-Andean Species of Creagrutus (Ostariophysi: Characiformes: Characidae)
SCtZ-0551

&quot;Larval&quot; and juvenile cephalopods: a manual for their identification	
“Larval” and Juvenile Cephalopods: A Manual for Their Identification
SCtZ-0513

External morphology of the genus Aegla (Crustacea, Anomura, Aeglidae)
External Morphology of the Genus Aegla (Crustacea: Anomura: Aeglidae)
SCtZ-0453

Pycnogonida of the Western Pacific Islands. III. Recent Smithsonian-Philippine Expeditions
Pycnogonida of the Western Pacific Islands, III: Recent Smithsonian-Philippine Expeditions
SCtZ-0468

A Revision of the Neotropical Riffle Beetle Genus Stenhelmoides (Coleoptera: Elmidae)
A Revision of the Neotropical Aquatic Beetle Genus Stenhelmoides (Coleoptera: Elmidae)
SCtZ-0479

Morphology of Luvarus imperialis (Luvaridae) with a phylogenetic analysis of the Acanthuroidei (Pisces)
Morphology of Luvarus imperialis (Luvaridae), with a Phylogenetic Analysis of the Acanthuroidei (Pisces)
SCtZ-0485

Ostracoda (Myodocopina, Cladocopina, Halocypridina) from Anchialine Caves in Bermuda
Ostracoda (Myodocopina, Cladocopina, Halocypridina) Mainly from Anchialine Caves in Bermuda
SCTZ-0475

An Illustrated Checklist of the American Crayfishes (Decapoda, Astacidae, Cambaridae, Parastacidae)
An Illustrated Checklist of the American Crayfishes (Decapoda: Astacidae, Cambaridae, and Parastacidae)
SCTZ-0480

Studies of Neotropical Caddiflies, XXXIX: The Genus Smicridea in the Chilean Subregion (Trichoptera: Hydropsychidae)
Studies of Neotropical Caddisflies, XXXIX: The Genus Smicridea in the Chilean Subregion (Trichoptera: Hydropsychidae)
SCtZ-0472 ???

Decapod and stomatopod crustaceans from Ascension Island, south Atlantic Ocean
Decapod and Stomatopod Crustacea from Ascension Island, South Atlantic Ocean
SCtZ-0503

Pycnogonida of Waters Adjacent to Japan
Pycnogonida from Waters Adjacent to Japan
SCtZ-0512

Stomatopod Crustacea collected by the Galathea Expedition, 1950-1952,with a list of Stomatopoda known from depths below 400 meters
Stomatopod Crustacea Collected by the Galathea Expedition, 1950-1952, with a List of Stomatopoda Known from Depths below 400 Meters
SCtZ-0521

Evolution, Comparative Morphology, and Identification of the Emaeine Butterfly Genus Rekoa Kay (Lycaenidae: Theclinae)
Evolution, Comparative Morphology, and Identification of the Eumaeine Butterfly Genus Rekoa Kaye (Lycaenidae: Theclinae)
SCtZ-0498

The ground beetles of Central America (Carabidae) I: Carabinae (in part): Notiophilini, Loricerini, Carabini
The Ground-Beetles of Central America (Carabidae), Part II: Notiophilini, Loricerini, and Carabini
SCtZ-0501

Phylogenetic relationships of hedgehogs and gymnures (Mammalia, Insectivora, Erinaceidae)
Phylogenetic Relationships of Hedgehogs and Gymnures (Mammalia: Insectivora: Erinaceidae)
SCtZ-0518

Monograph of the genus Cerithium Bruguiere in the Indo-Pacific (Cerithiidae: Prosobranchia)
Monograph of the Genus Cerithium Bruguière in the Indo-Pacific (Cerithiidae: Prosobranchia)
SCtZ-0510 ???

Myodocopid Ostracoda of the BenthIdi Expedition, 1977, to the NE Mozambique Channel, Indian Ocean
Myodocopid Ostracoda of the Benthédi Expedition, 1977, to the NE Mozambique Channel, Indian Ocean
SCtZ-0531 ???

Stylasteridae (Hydrozoa: Hydroida) of the Galapagos Islands
Stylasteridae (Hydrozoa: Hydroida) of the Galápagos Islands
SCtZ-0426 ???

Families Oplophoridae and Nematocarcinidae. The caridean shrimps of the Albatross Philippine Expedition, 1907-1910, part 4
The Caridean Shrimps (Crustacea: Decapoda) of the Albatross Philippine Expedition, 1907–1910, Part 4: Families Oplophoridae and Nematocarcinidae
SCtZ-0432

Revision of the Atlantic Brisingida (Echinodermata:Asteroidea), with description of a new genus and family
Revision of the Atlantic Brisingida (Echinodermata: Asteroidea), with Description of a New Genus and Family
SCtZ-0435 ???

The catfishes of the neotropical family Helogenidae (Ostariophysi:Siluroidei)
The Catfishes of the Neotropical Family Helogenidae (Ostariophysi: Siluroidei)
SCtZ-0442 ???
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>