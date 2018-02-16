<?php
namespace php_active_record;
/* This will combine 2 LifeDesk DwCA e.g.
- LD_afrotropicalbirds.tar.gz
- LD_afrotropicalbirds_multimedia.tar.gz

http://services.eol.org/resources/40.xml.gz
shhh quiet... - a hack in services.eol.org
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/LifeDeskToEOLAPI');
$func1 = new LifeDeskToEOLAPI();

require_library('connectors/ConvertEOLtoDWCaAPI');
require_library('connectors/CollectionsScrapeAPI');
require_library('connectors/DwCA_Utility');

/* MicroScope, FieldScope, Biscayne_BioBlitz -> have EOL XML, with media objects that are offline. Has Collections for source of media objects. Media objects from XML will be removed like that of LifeDesks */

$final = array();
// $lifedesks = array('277', '172', '155', '154'); $final = array_merge($final, $lifedesks); //rerun as LifeDesk
$lifedesks = array('277'); $final = array_merge($final, $lifedesks); //normal operation afrotropicalbirds
// $lifedesks = array('358'); $final = array_merge($final, $lifedesks); //normal operation

// [0] => 338, 352, 335, 324

/* template
$info['res_id'] = array('id' => col_id, 'domain' => 'http', 'OpenData_title' => 'xxx', 'resource_id' => res_id, 'prefix' => "EOL_");
$info['res_id']['xml_path'] = "http";
$info['res_id']['data_types'] = array('xxx'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')
*/

//has static xml, offline media, has collection
$info['358'] = array('id' => 22742, 'domain' => 'http://www.eol.org/content_partners/44/resources/358', 'OpenData_title' => 'STRI Neotropical fish distribution maps', 'resource_id' => 358, 'prefix' => "EOL_");
$info['358']['xml_path'] = "http://services.eol.org/resources/358.xml";
$info['358']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

$info['338'] = array('id' => 22050, 'domain' => 'http://www.eol.org/content_partners/451/resources/338', 'OpenData_title' => 'Entiminae', 'resource_id' => 338, 'prefix' => "EOL_");
$info['338']['xml_path'] = "http://services.eol.org/resources/338.xml";
$info['338']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, offline media, has collection
$info['352'] = array('id' => 21245, 'domain' => 'http://www.eol.org/content_partners/271/resources/352', 'OpenData_title' => '2011 Field Museum REU and Intern Program', 'resource_id' => 352);
$info['352']['xml_path'] = "http://services.eol.org/resources/352.xml";
$info['352']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, offline media, has collection
$info['335'] = array('id' => 16350, 'domain' => 'http://www.eol.org/content_partners/74/resources/335', 'OpenData_title' => 'BibAlex LifeDesk', 'resource_id' => 335);
$info['335']['xml_path'] = "http://services.eol.org/resources/335.xml";
$info['335']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, offline media, has collection
$info['324'] = array('id' => 12378, 'domain' => 'http://www.eol.org/content_partners/440/resources/324', 'OpenData_title' => 'Macalester College Biology 476 - Fall 2011 Education LifeDesk Pages', 'resource_id' => 324, 'prefix' => "EOL_");
$info['324']['xml_path'] = "http://services.eol.org/resources/324.xml";
$info['324']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, offline media, has collection
$info['288'] = array('id' => 362, 'domain' => 'http://www.eol.org/content_partners/261/resources/288', 'OpenData_title' => 'mx2EOL release candidate', 'resource_id' => 288, 'prefix' => "EOL_");
$info['288']['xml_path'] = "http://services.eol.org/resources/288.xml";
$info['288']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, offline media, has collection
$info['297'] = array('id' => 7744, 'domain' => 'http://www.eol.org/content_partners/390/resources/297', 'OpenData_title' => 'IABIN resource', 'resource_id' => 297, 'prefix' => "EOL_");
$info['297']['xml_path'] = "http://services.eol.org/resources/297.xml";
$info['297']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, offline media, has collection
$info['281'] = array('id' => 361, 'domain' => 'http://www.eol.org/content_partners/381/resources/281', 'OpenData_title' => 'Natural History Museum Species of the day', 'resource_id' => 281, 'prefix' => "EOL_");
$info['281']['xml_path'] = "http://services.eol.org/resources/281.xml";
$info['281']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, offline media, has collection
$info['279'] = array('id' => 360, 'domain' => 'http://www.eol.org/content_partners/17/resources/279', 'OpenData_title' => 'Wolf Spiders of Australia', 'resource_id' => 279, 'prefix' => "EOL_");
$info['279']['xml_path'] = "http://services.eol.org/resources/279.xml";
$info['279']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, offline media, has collection
$info['277'] = array('id' => 358, 'domain' => 'http://www.eol.org/content_partners/379/resources/277', 'OpenData_title' => 'Bryozoa LifeDesk', 'resource_id' => 277);
$info['277']['xml_path'] = "http://services.eol.org/resources/277.xml";
$info['277']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, offline media, has collection
$info['276'] = array('id' => 357, 'domain' => 'http://www.eol.org/content_partners/353/resources/276', 'OpenData_title' => 'INBio resource', 'resource_id' => 276, 'prefix' => "EOL_");
$info['276']['xml_path'] = "http://services.eol.org/resources/276.xml";
$info['276']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has static xml, online media, has collection
$info['275'] = array('id' => 356, 'domain' => 'http://www.eol.org/content_partners/348/resources/275', 'OpenData_title' => 'Soapberry Bugs resource June 2011', 'resource_id' => 275, 'prefix' => "EOL_");
$info['275']['xml_path'] = "http://services.eol.org/resources/275.xml";
$info['275']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml, offline media, has collection
$info['271'] = array('id' => 353, 'domain' => 'http://www.eol.org/content_partners/17/resources/271', 'OpenData_title' => 'Bugs for Bugs', 'resource_id' => 271, 'prefix' => "EOL_");
$info['271']['xml_path'] = "http://services.eol.org/resources/271.xml";
$info['271']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml, offline media, has collection
$info['269'] = array('id' => 351, 'domain' => 'http://www.eol.org/content_partners/17/resources/269', 'OpenData_title' => 'Barry Armstead Photography', 'resource_id' => 269, 'prefix' => "EOL_");
$info['269']['xml_path'] = "http://services.eol.org/resources/269.xml";
$info['269']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml, has audio but offline, has collection
$info['257'] = array('id' => 344, 'domain' => 'http://www.eol.org/content_partners/359/resources/257', 'OpenData_title' => 'One Species at a Time Podcasts', 'resource_id' => 257, 'prefix' => "EOL_");
$info['257']['xml_path'] = "http://services.eol.org/resources/257.xml";
$info['257']['data_types'] = array('sounds'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//with xml, no connector, offline media, has collection
$info['256'] = array('id' => 343, 'domain' => 'http://www.eol.org/content_partners/358/resources/256', 'OpenData_title' => 'Harmful Phytoplankton Resource', 'resource_id' => 256, 'prefix' => "EOL_");
$info['256']['xml_path'] = "http://services.eol.org/resources/256.xml";
$info['256']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml, lifedesk but will use xml from services.eol.org, has collection
$info['254'] = array('id' => 341, 'domain' => 'http://www.eol.org/content_partners/265/resources/254', 'OpenData_title' => '2011 Latin School Project Week', 'resource_id' => 254, 'prefix' => "EOL_");
$info['254']['xml_path'] = "http://services.eol.org/resources/254.xml";
$info['254']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//no connector, with xml but cannot recreate, with online video but offline thumbnail
$info[233] = array('id' => 327, 'domain' => 'http://www.eol.org/content_partners/100/resources/233', 'OpenData_title' => 'Undersea Production test 2', 'resource_id' => 233, 'prefix' => "EOL_");
$info[233]['xml_path'] = "http://services.eol.org/resources/233.xml"; //http
$info[233]['data_types'] = array('video'); //possible values array('images', 'video', 'sounds', 'text')

//start new items above --------------------------------------------------------------

//has xml, has text and media with offline media, has collection
$info['217'] = array('id' => 315, 'domain' => 'http://www.eol.org/content_partners/254/resources/217', 'OpenData_title' => 'OEB130_2010', 'resource_id' => 217, 'prefix' => "EOL_");
$info['217']['xml_path'] = "http://services.eol.org/resources/217.xml";
$info['217']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml, has text and media with offline media, has collection
$info['172'] = array('id' => 282, 'domain' => 'http://www.eol.org/content_partners/265/resources/172', 'OpenData_title' => 'eNemo', 'resource_id' => 172);
$info['172']['xml_path'] = "http://services.eol.org/resources/172.xml";
$info['172']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml, has text and media with offline media, has collection
$info['159'] = array('id' => 272, 'domain' => 'http://www.eol.org/content_partners/223/resources/159', 'OpenData_title' => 'The Harvard University Herpetology Course', 'resource_id' => 159, 'prefix' => "EOL_");
$info['159']['xml_path'] = "http://services.eol.org/resources/159.xml";
$info['159']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml with offline media, has collection
$info['155'] = array('id' => 270, 'domain' => 'http://www.eol.org/content_partners/213/resources/155', 'OpenData_title' => 'Sedges of Carex subgenus Vignea', 'resource_id' => 155);
$info['155']['xml_path'] = "http://services.eol.org/resources/155.xml";
$info['155']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml with offline media, has collection
$info['154'] = array('id' => 269, 'domain' => 'http://www.eol.org/content_partners/247/resources/154', 'OpenData_title' => 'Carex of the World', 'resource_id' => 154);
$info['154']['xml_path'] = "http://services.eol.org/resources/154.xml";
$info['154']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml, offline media_url, has collection
$info['137'] = array('id' => 258, 'domain' => 'http://www.eol.org/content_partners/230/resources/137', 'OpenData_title' => "The Field Museum Member's Night EOL Photo Scavenger Hunt 2010", 'resource_id' => 137, 'prefix' => "EOL_");
$info['137']['xml_path'] = "http://services.eol.org/resources/137.xml";
$info['137']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml, offline media_url, has collection. BTW a lifedesk
$info['133'] = array('id' => 256, 'domain' => 'http://www.eol.org/content_partners/227/resources/133', 'OpenData_title' => 'Embiotocidae LifeDesk', 'resource_id' => 133);
$info['133']['xml_path'] = "http://services.eol.org/resources/133.xml";
$info['133']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has XML with offline media_url, has collection
$info['119'] = array('id' => 245, 'domain' => 'http://www.eol.org/content_partners/195/resources/119', 'OpenData_title' => 'Photosynth Resource', 'resource_id' => 119, 'prefix' => "EOL_");
$info['119']['xml_path'] = "http://services.eol.org/resources/119.xml";
$info['119']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has provider XML, has text and media where media_url is offline. has collection
$info['103'] = array('id' => 233, 'domain' => 'http://www.eol.org/content_partners/151/resources/103', 'OpenData_title' => 'Braconidae resource', 'resource_id' => 103, 'prefix' => "EOL_");
$info['103']['xml_path'] = "http://www.sharkeylab.org/sharkeylab/Misc/EOLAlabagrus2010MR29.xml";
$info['103']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has XML, with valid media, also has collection. Same no. of objects bet. XML and collection. SPECIAL also has 36.php which is also valid
$info['36_snapshot_2018_02_15'] = array('id' => 192, 'domain' => 'http://www.eol.org/content_partners/47/resources/36', 'OpenData_title' => 'Scott Namestnik', 'resource_id' => 36, 'prefix' => "EOL_");
$info['36_snapshot_2018_02_15']['xml_path'] = "http://services.eol.org/resources/36.xml"; //http
$info['36_snapshot_2018_02_15']['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

//has xml with media, media is offline
$info['43'] = array('id' => 199, 'domain' => 'http://www.eol.org/content_partners/53/resources/43', 'OpenData_title' => 'Finding Species', 'resource_id' => 43, 'prefix' => "EOL_");
$info['43']['xml_path'] = "http://services.eol.org/resources/43.xml";
$info['43']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml with text and media, media is offline
$info['35'] = array('id' => 191, 'domain' => 'http://www.eol.org/content_partners/44/resources/35', 'OpenData_title' => 'STRI Neotropical Fishes', 'resource_id' => 35, 'prefix' => "EOL_");
$info['35']['xml_path'] = "http://services.eol.org/resources/35.xml";
$info['35']['data_types'] = array('images'); //what is available in its Collection - //possible values array('images', 'video', 'sounds', 'text')

//has xml with text and media, media is offline
$info['374'] = array('id' => 26210, 'domain' => 'http://www.eol.org/content_partners/478/resources/374', 'OpenData_title' => 'Butterfly Master Upload File', 'resource_id' => 374, 'prefix' => "EOL_");
$info['374']['xml_path'] = "http://services.eol.org/resources/374.xml";
$info['374']['data_types'] = array('images'); //what is available in its Collection

//has xml with text and media, media is offline
$info['180'] = array('id' => 290, 'domain' => 'http://www.eol.org/content_partners/269/resources/180', 'OpenData_title' => 'Keys to Babamunida, Crosnierita, Onconida, Phylladiorhynchus, Plesionida', 'resource_id' => 180, 'prefix' => "EOL_");
$info['180']['xml_path'] = "http://services.eol.org/resources/180.xml";
$info['180']['data_types'] = array('images'); //what is available in its Collection

$info['Biscayne_BioBlitz'] = array('id' => 251, 'domain' => 'http://www.eol.org/content_partners/58/resources/126', 'OpenData_title' => 'Biscayne BioBlitz Resource', 'resource_id' => 126, 'prefix' => "EOL_");
$info['Biscayne_BioBlitz']['xml_path'] = "http://services.eol.org/resources/126.xml";
$info['Biscayne_BioBlitz']['data_types'] = array('images'); //what is available in its Collection

/* ran in Archive already
$info['FieldScope'] = array('id'=>196, 'LD_domain' => 'http://www.eol.org/content_partners/58/resources/41', 'OpenData_title' => 'FieldScope', 'resource_id' => 41, 'prefix' => "EOL_");
$info['FieldScope']['xml_path'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/OpenData/EOLxml_2_DWCA/FieldScope_41/41.xml.gz";
$info['FieldScope']['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text') - get objects of this data_type from Collections

$info['MicroScope'] = array('id'=>180, 'LD_domain' => 'http://eol.org/content_partners/5/resources/19', 'OpenData_title' => 'micro*scope', 'resource_id' => 19, 'prefix' => "EOL_");
$info['MicroScope']['xml_path'] = "http://localhost/cp_new/OpenData/EOLxml_2_DWCA/microscope/microscope.xml.gz";
$info['MicroScope']['xml_path'] = "https://opendata.eol.org/dataset/4a668cee-f1da-4e95-9ed1-cb755a9aca4f/resource/55ad629d-dd89-4bac-8fff-96f219f4b323/download/microscope.xml.gz";
$info['MicroScope']['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')
*/

/* normal operation
$lifedesks = array("drosophilidae", "mochokidae", "berry", "echinoderms", "eleodes", "empidinae");                  $final = array_merge($final, $lifedesks);
$lifedesks = array("gastrotricha", "reduviidae", "heteroptera", "capecodlife", "idorids", "evaniidae");             $final = array_merge($final, $lifedesks);
$lifedesks = array("araneoidea", "archaeoceti", "calintertidalinverts", "chileanbees", "halictidae", "nlbio");      $final = array_merge($final, $lifedesks);
$lifedesks = array("surinamewaterbeetles", "scarabaeoidea", "pipunculidae", "ncfishes", "biomarks");                $final = array_merge($final, $lifedesks);
$lifedesks = array("spiderindia", "speciesindia", "skinklink", "scarab", "nzicn", "bcbiodiversity");                $final = array_merge($final, $lifedesks);
$lifedesks = array("pterioidea", "westernghatfishes", "cephalopoda");                                               $final = array_merge($final, $lifedesks);
*/

$info['araneae']            = array('id'=>203, 'LD_domain' => 'http://araneae.lifedesks.org/', 'OpenData_title' => 'Spiders LifeDesk');
$info['eolspecies']         = array('id'=>204, 'LD_domain' => 'http://eolspecies.lifedesks.org/', 'OpenData_title' => 'EOL Rapid Response Team LifeDesk');
$info['trilobites']         = array('id'=>206, 'LD_domain' => 'http://trilobites.lifedesks.org/', 'OpenData_title' => 'Trilobites Online Database LifeDesk');
$info['indianadunes']       = array('id'=>211, 'LD_domain' => 'http://indianadunes.lifedesks.org/', 'OpenData_title' => 'Indiana Dunes Bioblitz LifeDesk');
$info['eolinterns']         = array('id'=>228, 'LD_domain' => 'http://eolinterns.lifedesks.org/', 'OpenData_title' => 'EOL Interns LifeDesk LifeDesk');
$info['psora']              = array('id'=>232, 'LD_domain' => 'http://psora.lifedesks.org/', 'OpenData_title' => 'The lichen genus Psora LifeDesk');
$info['corvidae']           = array('id'=>234, 'LD_domain' => 'http://corvidae.lifedesks.org/', 'OpenData_title' => 'Corvid Corroborree LifeDesk');
$info['plantsoftibet']      = array('id'=>241, 'LD_domain' => 'http://plantsoftibet.lifedesks.org/', 'OpenData_title' => 'Plants of Tibet LifeDesk');
$info['caprellids']         = array('id'=>242, 'LD_domain' => 'http://caprellids.lifedesks.org/', 'OpenData_title' => 'Caprellids LifeDesk LifeDesk');
$info['pleurotomariidae']   = array('id'=>268, 'LD_domain' => 'http://pleurotomariidae.lifedesks.org/', 'OpenData_title' => 'Pleurotomariidae LifeDesk');
$info['halictidae']         = array('id'=>299, 'LD_domain' => 'http://halictidae.lifedesks.org/', 'OpenData_title' => 'Halictidae LifeDesk');
$info['batrach']            = array('id'=>307, 'LD_domain' => 'http://batrach.lifedesks.org/', 'OpenData_title' => 'Batrachospermales LifeDesk');
$info['deepseafishes']      = array('id'=>308, 'LD_domain' => 'http://deepseafishes.lifedesks.org/', 'OpenData_title' => 'Deep-sea Fishes of the World LifeDesk');
$info['arczoo']             = array('id'=>322, 'LD_domain' => 'http://arczoo.lifedesks.org/', 'OpenData_title' => 'iArcZoo LifeDesk');
$info['snakesoftheworld']   = array('id'=>328, 'LD_domain' => 'http://snakesoftheworld.lifedesks.org/', 'OpenData_title' => 'Snake Species of the World LifeDesk');
$info['mexinverts']         = array('id'=>330, 'LD_domain' => 'http://mexinverts.lifedesks.org/', 'OpenData_title' => 'LifeDesk Invertebrados Marinos de México LifeDesk');
$info['echinoderms']        = array('id'=>334, 'LD_domain' => 'http://echinoderms.lifedesks.org/', 'OpenData_title' => 'The Echinoderms of Panama');
$info['rotifera']           = array('id'=>336, 'LD_domain' => 'http://rotifera.lifedesks.org/', 'OpenData_title' => 'Marine Rotifera LifeDesk');
$info['maldivesnlaccadives'] = array('id'=>346, 'LD_domain' => 'http://maldivesnlaccadives.lifedesks.org/', 'OpenData_title' => 'Maldives and Laccadives LifeDesk');
$info['thrasops']           = array('id'=>347, 'LD_domain' => 'http://thrasops.lifedesks.org/', 'OpenData_title' => 'African Snakes of the Genus Thrasops LifeDesk');
$info['afrotropicalbirds']  = array('id'=>9528, 'LD_domain' => 'http://afrotropicalbirds.lifedesks.org/', 'OpenData_title' => 'Afrotropical birds in the RMCA LifeDesk');
$info['philbreo']           = array('id'=>16553, 'LD_domain' => 'http://philbreo.lifedesks.org/', 'OpenData_title' => 'Amphibians and Reptiles of the Philippines LifeDesk');
$info['diptera']            = array('id'=>111622, 'LD_domain' => 'http://diptera.lifedesks.org/', 'OpenData_title' => 'EOL Rapid Response Team Diptera LifeDesk');
$info['leptogastrinae']     = array('id'=>219, 'LD_domain' => 'http://leptogastrinae.lifedesks.org/', 'OpenData_title' => 'Leptogastrinae LifeDesk');

/* this works OK. but was decided not to add ancestry if original source doesn't have ancestry. Makes sense.
$ancestry['afrotropicalbirds'] = array('kingdom' => 'Animalia', 'phylum' => 'Chordata', 'class' => 'Aves'); 
*/

/* un-comment in normal operation
$final = array_merge($final, array_keys($info));
*/

$final = array_unique($final);
print_r($final); echo "\n".count($final)."\n"; //exit;

// /* normal operation
foreach($final as $ld) {
    /*
    $params[$ld]["remote"]["lifedesk"]      = "http://" . $ld . ".lifedesks.org/eol-partnership.xml.gz";
    $params[$ld]["remote"]["name"]          = $ld;
    */
    $params[$ld]["local"]["lifedesk"]       = "http://localhost/cp_new/LD2EOL/" . $ld . "/eol-partnership.xml.gz";
    $params[$ld]["local"]["lifedesk"]       = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/LD2EOL/$ld/eol-partnership.xml.gz";
    $params[$ld]["local"]["name"]           = $ld;
    $params[$ld]["local"]["ancestry"]       = @$ancestry[$ld];
    
    // start EOL regular resources e.g. MicroScope
    if($val = @$info[$ld]['xml_path']) $params[$ld]["local"]["lifedesk"] = $val;
}
$cont_compile = false;

foreach($final as $lifedesk) {
    if($val = @$info[$lifedesk]['prefix']) $prefix = $val;
    else                                   $prefix = "LD_";
    
    $taxa_from_orig_LifeDesk_XML = array(); //https://eol-jira.bibalex.org/browse/DATA-1569?focusedCommentId=62081&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62081
    $taxa_from_orig_LifeDesk_XML = $func1->export_lifedesk_to_eol($params[$lifedesk]["local"], $prefix);
    if(Functions::url_exists($params[$lifedesk]["local"]["lifedesk"])) {
        convert_xml_2_dwca($prefix.$lifedesk); //convert XML to DwCA
        $cont_compile = true;
    }

    // start generate the 2nd DwCA -------------------------------
    $resource_id = $prefix.$lifedesk."_multimedia";
    if($collection_id = @$info[$lifedesk]['id']) { //9528;
        
        if($val = @$info[$lifedesk]['data_types']) $data_types = $val; //for EOL_
        else                                       $data_types = array('images', 'video', 'sounds'); //for LD_
        
        $func2 = new CollectionsScrapeAPI($resource_id, $collection_id, $data_types); //3rd param only has values for EOL_. Blank is for LD_.
        $func2->start($taxa_from_orig_LifeDesk_XML);
        Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means resource folder will be deleted
        $cont_compile = true;
    }
    else echo "\nNo Collection for this LifeDesk.\n";
    // end generate the 2nd DwCA -------------------------------
    
    //  --------------------------------------------------- start compiling the 2 DwCA files into 1 final DwCA --------------------------------------------------- 
    if($cont_compile) {
        $dwca_file = false;
        $resource_id = $prefix.$lifedesk."_final";
        $func2 = new DwCA_Utility($resource_id, $dwca_file); //2nd param is false bec. it'll process multiple archives, see convert_archive_files() in library DwCA_Utility.php

        $archives = array();
        /* use this if we're getting taxa info (e.g. ancestry) from Collection
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH.$prefix.$lifedesk."_multimedia.tar.gz")) $archives[] = $prefix.$lifedesk."_multimedia";
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH.$prefix.$lifedesk.".tar.gz"))            $archives[] = $prefix.$lifedesk;
        */
        // Otherwise let the taxa from LifeDesk XML be prioritized
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH.$prefix.$lifedesk.".tar.gz"))            $archives[] = $prefix.$lifedesk;
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH.$prefix.$lifedesk."_multimedia.tar.gz")) $archives[] = $prefix.$lifedesk."_multimedia";


        $func2->convert_archive_files($archives); //this is same as convert_archive(), only it processes multiple DwCA files not just one.
        unset($func2);
        Functions::finalize_dwca_resource($resource_id, false, true);
        
        /* working but removed since sometimes a LifeDesk only provides names without objects at all
        //---------------------new start generic_normalize_dwca() meaning remove taxa without objects, only leave taxa with objects in final dwca
        $tar_gz = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz";
        if(file_exists($tar_gz)) {
            $func = new DwCA_Utility($resource_id, $tar_gz);
            $func->convert_archive_normalized();
            Functions::finalize_dwca_resource($resource_id);
        }
        //---------------------new end
        */
    }
    //  --------------------------------------------------- end compiling the 2 DwCA files into 1 final DwCA --------------------------------------------------- 
}
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function convert_xml_2_dwca($resource_id)
{
    if(Functions::is_production()) $params["eol_xml_file"] = "http://editors.eol.org/eol_php_code/applications/content_server/resources/".$resource_id.".xml"; //e.g. LD_afrotropicalbirds
    else                           $params["eol_xml_file"] = "http://localhost/eol_php_code/applications/content_server/resources/".$resource_id.".xml"; //e.g. LD_afrotropicalbirds
    
    
    $params["filename"]     = "no need to mention here.xml";
    $params["dataset"]      = "LifeDesk XML files";
    $params["resource_id"]  = $resource_id;
    $func = new ConvertEOLtoDWCaAPI($resource_id);
    
    /* u need to set this to expire now = 0 ... if there is change in ancestry information... */
    // $func->export_xml_to_archive($params, true, 60*60*24*15); // true => means it is an XML file, not an archive file nor a zip file. Expires in 15 days.
    $func->export_xml_to_archive($params, true, 0); // true => means it is an XML file, not an archive file nor a zip file. Expires now.

    Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means resource folder will be deleted
    Functions::delete_if_exists(CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".xml");
}

?>