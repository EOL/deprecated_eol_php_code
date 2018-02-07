<?php
namespace php_active_record;
/* connector for multiple resources.
This will use the collections page (scraping) with the dataObjects API to generate a DwCA file.
First client are the LifeDesk resources e.g. http://eol.org/collections/9528/images?sort_by=1&view_as=3
Eventually all LifeDesks from this ticket will be processed: 
https://eol-jira.bibalex.org/browse/DATA-1569

estimated execution time:
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CollectionsScrapeAPI');
$timestart = time_elapsed();

$resource_id = "LD_afrotropicalbirds_multimedia";
$collection_id = 9528; //106941 no taxon for its data_objects; //242; //358; //260; //325; //9528; 36734 => "Squat Lobster LIFEDESK"

$func = new CollectionsScrapeAPI($resource_id, $collection_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false); //3rd param true means resource folder will be deleted

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";


function lifedesk_multimedia_resources()
{
    //EOL collection ID	lifedesk domain	title for opendata
    $a[203] = array('LD_domain' => 'http://araneae.lifedesks.org/', 'OpenData_title' => 'Spiders LifeDesk');
    $a[204] = array('LD_domain' => 'http://eolspecies.lifedesks.org/', 'OpenData_title' => 'EOL Rapid Response Team LifeDesk');
    $a[206] = array('LD_domain' => 'http://trilobites.lifedesks.org/', 'OpenData_title' => 'Trilobites Online Database LifeDesk');
    $a[211] = array('LD_domain' => 'http://indianadunes.lifedesks.org/', 'OpenData_title' => 'Indiana Dunes Bioblitz LifeDesk');
    $a[228] = array('LD_domain' => 'http://eolinterns.lifedesks.org/', 'OpenData_title' => 'EOL Interns LifeDesk LifeDesk');
    $a[232] = array('LD_domain' => 'http://psora.lifedesks.org/', 'OpenData_title' => 'The lichen genus Psora LifeDesk');
    $a[234] = array('LD_domain' => 'http://corvidae.lifedesks.org/', 'OpenData_title' => 'Corvid Corroborree LifeDesk');
    $a[241] = array('LD_domain' => 'http://plantsoftibet.lifedesks.org/', 'OpenData_title' => 'Plants of Tibet LifeDesk');
    $a[242] = array('LD_domain' => 'http://caprellids.lifedesks.org/', 'OpenData_title' => 'Caprellids LifeDesk LifeDesk');
    $a[268] = array('LD_domain' => 'http://pleurotomariidae.lifedesks.org/', 'OpenData_title' => 'Pleurotomariidae LifeDesk');
    $a[299] = array('LD_domain' => 'http://halictidae.lifedesks.org/', 'OpenData_title' => 'Halictidae LifeDesk');
    $a[307] = array('LD_domain' => 'http://batrach.lifedesks.org/', 'OpenData_title' => 'Batrachospermales LifeDesk');
    $a[308] = array('LD_domain' => 'http://deepseafishes.lifedesks.org/', 'OpenData_title' => 'Deep-sea Fishes of the World LifeDesk');
    $a[322] = array('LD_domain' => 'http://arczoo.lifedesks.org/', 'OpenData_title' => 'iArcZoo LifeDesk');
    $a[328] = array('LD_domain' => 'http://snakesoftheworld.lifedesks.org/', 'OpenData_title' => 'Snake Species of the World LifeDesk');
    $a[330] = array('LD_domain' => 'http://mexinverts.lifedesks.org/', 'OpenData_title' => 'LifeDesk Invertebrados Marinos de México LifeDesk');
    $a[336] = array('LD_domain' => 'http://rotifera.lifedesks.org/', 'OpenData_title' => 'Marine Rotifera LifeDesk');
    $a[340] = array('LD_domain' => 'http://echinoderms.lifedesks.org/', 'OpenData_title' => 'Discover Life LifeDesk');
    $a[346] = array('LD_domain' => 'http://maldivesnlaccadives.lifedesks.org/', 'OpenData_title' => 'Maldives and Laccadives LifeDesk');
    $a[347] = array('LD_domain' => 'http://thrasops.lifedesks.org/', 'OpenData_title' => 'African Snakes of the Genus Thrasops LifeDesk');
    $a[9528] = array('LD_domain' => 'http://afrotropicalbirds.lifedesks.org/', 'OpenData_title' => 'Afrotropical birds in the RMCA LifeDesk');
    $a[16553] = array('LD_domain' => 'http://philbreo.lifedesks.org/', 'OpenData_title' => 'Amphibians and Reptiles of the Philippines LifeDesk');
    $a[111622] = array('LD_domain' => 'http://diptera.lifedesks.org/', 'OpenData_title' => 'EOL Rapid Response Team Diptera LifeDesk');
    $a[219] = array('LD_domain' => 'http://leptogastrinae.lifedesks.org/', 'OpenData_title' => 'Leptogastrinae LifeDesk');
    return $a;
}

?>
