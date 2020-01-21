<?php
namespace php_active_record;
/*
Instructions here: https://eol-jira.bibalex.org/browse/COLLAB-1004?focusedCommentId=64212&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64212
*/
/* how to run:
$json = '{"Proj":"KANB", "Dept":"FISH", "Lic":"CreativeCommons – Attribution Non-Commercial (by-nc)", "Lic_yr":"", "Lic_inst":"", "Lic_cont":"", "Taxon":"Abudefduf"}';
php update_resources/connectors/bold2inat.php _ image_input.xlsx _ _ '$json'
php update_resources/connectors/bold2inat.php _ _ 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MarineGEO/image_input.xlsx' uuid001 '$json'

php update_resources/connectors/bold2inat.php _ _ _ _ '$json'
php update_resources/connectors/bold2inat.php _ _ _ _ '{"Proj":"KANB", "Dept":"FISH", "Lic":"CreativeCommons – Attribution Non-Commercial (by-nc)", "Lic_yr":"", "Lic_inst":"", "Lic_cont":"", "Taxon":"Abudefduf"}'

Sample with >1 image_urls 'Sebastapistes coniorta'
First record created:
{"id":37811483,"site_id":1,"created_at":"2020-01-21T00:28:38-10:00","created_at_details":{"date":"2020-01-21","day":21,"month":1,"year":2020,"hour":0,"week":4},
"observed_on":null,"observed_on_details":null,"time_observed_at":null,"place_ids":[1,11,1856,6693,52822,68200,97393],"quality_grade":"casual",
"taxon":{"id":121459,"rank":"species","rank_level":10,"iconic_taxon_id":47178,"ancestor_ids":[48460,1,2,355675,47178,47233,47295,788565,47296,121459],
"is_active":true,"min_species_taxon_id":121459,"name":"Lutjanus fulvus","parent_id":47296,"ancestry":"48460/1/2/355675/47178/47233/47295/788565/47296",
"min_species_ancestry":"48460,1,2,355675,47178,47233,47295,788565,47296,121459","extinct":false,"threatened":false,"introduced":true,"native":false,
"endemic":false,"taxon_schemes_count":1,"wikipedia_url":"http://en.wikipedia.org/wiki/Lutjanus_fulvus","current_synonymous_taxon_ids":null,"created_at":"2011-12-08T10:53:04+00:00",
"taxon_changes_count":0,"complete_species_count":null,"universal_search_rank":209,"observations_count":209,"flag_counts":{"unresolved":0,"resolved":0},
"atlas_id":null,"complete_rank":"subspecies","default_photo":{"square_url":"https://static.inaturalist.org/photos/68937/square.jpg?1444773193",
"attribution":"(c) DavidR.808, some rights reserved (CC BY-NC-SA), uploaded by David R","flags":[],
"medium_url":"https://static.inaturalist.org/photos/68937/medium.jpg?1444773193","id":68937,"license_code":"cc-by-nc-sa",
"original_dimensions":{"width":512,"height":341},"url":"https://static.inaturalist.org/photos/68937/square.jpg?1444773193"},
"iconic_taxon_name":"Actinopterygii","preferred_common_name":"Blacktail Snapper","wikipedia_summary":
"Lutjanus fulvus, commonly known as the blacktail snapper, is a marine fish native to the western Pacific and Indian Oceans, from East Africa to Japan and Australia."},
"uuid":"ed95e6f5-d35c-4168-8976-658f3153c69b","user":{"id":2533880,"login":"nmnh_fishes","spam":false,"suspended":false,"created_at":"2020-01-17T21:37:22+00:00",
"login_autocomplete":"nmnh_fishes","login_exact":"nmnh_fishes","name":"","name_autocomplete":"","orcid":null,"icon":null,"observations_count":1,"identifications_count":0,
"journal_posts_count":0,"activity_count":1,"universal_search_rank":1,"roles":[],"site_id":1,"icon_url":null,"preferences":{}},"captive":false,"created_time_zone":"Pacific/Honolulu",
"updated_at":"2020-01-21T00:28:38-10:00","observed_time_zone":"Pacific/Honolulu","time_zone_offset":"-10:00","uri":null,
"description":"Hawaii, Oahu, Kaneohe Bay, He`eia fish pond. Collected between 0-3 meters. Identified by: Zeehan Jaafar.","mappable":true,"species_guess":"Lutjanus fulvus","place_guess":
"Hawaii, Oahu, Kaneohe Bay, He`eia fish pond.","observed_on_string":null,"id_please":false,"out_of_range":null,"license_code":"cc-by-nc","geoprivacy":null,"taxon_geoprivacy":null,
"context_geoprivacy":null,"context_user_geoprivacy":null,"context_taxon_geoprivacy":null,"map_scale":null,"oauth_application_id":398,"community_taxon_id":null,"faves_count":0,
"cached_votes_total":0,"num_identification_agreements":0,"num_identification_disagreements":0,"identifications_most_agree":false,"identifications_some_agree":false,
"identifications_most_disagree":false,"project_ids":[],"project_ids_with_curator_id":[],"project_ids_without_curator_id":[],"reviewed_by":[2533880],"tags":[],"ofvs":[],"annotations":[],
"sounds":[],"ident_taxon_ids":[48460,1,2,355675,47178,47233,47295,788565,47296,121459],"identifications_count":0,"comments":[],"comments_count":0,"obscured":false,
"positional_accuracy":null,"public_positional_accuracy":null,"location":"21.4372,-157.806","geojson":{"type":"Point",
"coordinates":[-157.806,21.4372]},"votes":[],"outlinks":[],"owners_identification_from_vision":false,"preferences":{"auto_obscuration":true,"prefers_community_taxon":null},
"flags":[],"quality_metrics":[],"spam":false,"faves":[],"non_owner_ids":[],"identifications":[{"id":84482473,"uuid":"3b1303e9-23d3-461c-ac5f-daf51a530011","user":{"id":2533880,"login":"nmnh_fishes","spam":false,"suspended":false,"created_at":"2020-01-17T21:37:22+00:00","login_autocomplete":"nmnh_fishes","login_exact":"nmnh_fishes","name":"","name_autocomplete":"","orcid":null,"icon":null,"observations_count":1,"identifications_count":0,"journal_posts_count":0,"activity_count":1,"universal_search_rank":1,"roles":[],"site_id":1,"icon_url":null},"created_at":"2020-01-21T00:28:38-10:00","created_at_details":{"date":"2020-01-21","day":21,"month":1,"year":2020,"hour":0,"week":4},"body":null,"category":null,"current":true,"flags":[],"own_observation":true,"taxon_change":null,"vision":false,"disagreement":false,"previous_observation_taxon_id":121459,"spam":false,"taxon_id":121459,"hidden":false,"moderator_actions":[],"taxon":{"taxon_schemes_count":1,"ancestry":"48460/1/2/355675/47178/47233/47295/788565/47296","min_species_ancestry":"48460,1,2,355675,47178,47233,47295,788565,47296,121459","wikipedia_url":"http://en.wikipedia.org/wiki/Lutjanus_fulvus","current_synonymous_taxon_ids":null,"iconic_taxon_id":47178,"created_at":"2011-12-08T10:53:04+00:00","taxon_changes_count":0,"complete_species_count":null,"rank":"species","extinct":false,"id":121459,"universal_search_rank":209,"ancestor_ids":[48460,1,2,355675,47178,47233,47295,788565,47296],"observations_count":209,"is_active":true,"flag_counts":{"unresolved":0,"resolved":0},"min_species_taxon_id":121459,"rank_level":10,"atlas_id":null,"parent_id":47296,"complete_rank":"subspecies","name":"Lutjanus fulvus","default_photo":{"square_url":"https://static.inaturalist.org/photos/68937/square.jpg?1444773193","attribution":"(c) DavidR.808, some rights reserved (CC BY-NC-SA), uploaded by David R","flags":[],"medium_url":"https://static.inaturalist.org/photos/68937/medium.jpg?1444773193","id":68937,"license_code":"cc-by-nc-sa","original_dimensions":{"width":512,"height":341},"url":"https://static.inaturalist.org/photos/68937/square.jpg?1444773193"},"iconic_taxon_name":"Actinopterygii","preferred_common_name":"Blacktail Snapper","ancestors":[{"observations_count":18277238,"taxon_schemes_count":2,"ancestry":"48460","is_active":true,"flag_counts":{"unresolved":0,"resolved":7},"wikipedia_url":"http://en.wikipedia.org/wiki/Animal","current_synonymous_taxon_ids":null,"iconic_taxon_id":1,"rank_level":70,"taxon_changes_count":3,"atlas_id":null,"complete_species_count":null,"parent_id":48460,"complete_rank":"order","name":"Animalia","rank":"kingdom","extinct":false,"id":1,"default_photo":{"square_url":"https://static.inaturalist.org/photos/169/square.jpg?1545345841","attribution":"(c) David Midgley, some rights reserved (CC BY-NC-ND)","flags":[],"medium_url":"https://static.inaturalist.org/photos/169/medium.jpg?1545345841","id":169,"license_code":"cc-by-nc-nd","original_dimensions":{"width":1421,"height":1016},"url":"https://static.inaturalist.org/photos/169/square.jpg?1545345841"},"ancestor_ids":[48460,1],"iconic_taxon_name":"Animalia","preferred_common_name":"Animals"},{"observations_count":8337077,"taxon_schemes_count":2,"ancestry":"48460/1","is_active":true,"flag_counts":{"unresolved":0,"resolved":0},"wikipedia_url":"http://en.wikipedia.org/wiki/Chordate","current_synonymous_taxon_ids":null,"iconic_taxon_id":1,"rank_level":60,"taxon_changes_count":0,"atlas_id":null,"complete_species_count":72871,"parent_id":1,"complete_rank":"subspecies","name":"Chordata","rank":"phylum","extinct":false,"id":2,"default_photo":{"square_url":"https://static.inaturalist.org/photos/13861431/square.jpg?1545716483","attribution":"(c) Claudine Lamothe, some rights reserved (CC BY-NC)","flags":[],"medium_url":"https://static.inaturalist.org/photos/13861431/medium.jpg?1545716483","id":13861431,"license_code":"cc-by-nc","original_dimensions":{"width":2000,"height":1333},"url":"https://static.inaturalist.org/photos/13861431/square.jpg?1545716483"},"ancestor_ids":[48460,1,2],"iconic_taxon_name":"Animalia","preferred_common_name":"Chordates"},{"observations_count":8324953,"taxon_schemes_count":1,"ancestry":"48460/1/2","is_active":true,"flag_counts":{"unresolved":0,"resolved":1},"wikipedia_url":"http://en.wikipedia.org/wiki/Vertebrate","current_synonymous_taxon_ids":null,"iconic_taxon_id":1,"rank_level":57,"taxon_changes_count":1,"atlas_id":null,"complete_species_count":69775,"parent_id":2,"complete_rank":"subspecies","name":"Vertebrata","rank":"subphylum","extinct":false,"id":355675,"default_photo":{"square_url":"https://static.inaturalist.org/photos/42093156/square.jpg?1560678585","attribution":"(c) Laurent Hesemans, all rights reserved","flags":[],"medium_url":"https://static.inaturalist.org/photos/42093156/medium.jpg?1560678585","id":42093156,"license_code":null,"original_dimensions":{"width":2048,"height":1367},"url":"https://static.inaturalist.org/photos/42093156/square.jpg?1560678585"},"ancestor_ids":[48460,1,2,355675],"iconic_taxon_name":"Animalia","preferred_common_name":"Vertebrates"},{"observations_count":425269,"taxon_schemes_count":2,"ancestry":"48460/1/2/355675","is_active":true,"flag_counts":{"unresolved":0,"resolved":1},"wikipedia_url":"http://en.wikipedia.org/wiki/Actinopterygii","current_synonymous_taxon_ids":null,"iconic_taxon_id":47178,"rank_level":50,"taxon_changes_count":1,"atlas_id":null,"complete_species_count":33002,"parent_id":355675,"complete_rank":"subspecies","name":"Actinopterygii","rank":"class","extinct":false,"id":47178,"default_photo":{"square_url":"https://static.inaturalist.org/photos/1416/square.jpg?1545368126","attribution":"(c) Jenny, some rights reserved (CC BY)","flags":[],"medium_url":"https://static.inaturalist.org/photos/1416/medium.jpg?1545368126","id":1416,"license_code":"cc-by","original_dimensions":{"width":1390,"height":2048},"url":"https://static.inaturalist.org/photos/1416/square.jpg?1545368126"},"ancestor_ids":[48460,1,2,355675,47178],"iconic_taxon_name":"Actinopterygii","preferred_common_name":"Ray-finned Fishes"},{"observations_count":243194,"taxon_schemes_count":2,"ancestry":"48460/1/2/355675/47178","is_active":true,"flag_counts":{"unresolved":0,"resolved":0},"wikipedia_url":"http://en.wikipedia.org/wiki/Perciformes","current_synonymous_taxon_ids":null,"iconic_taxon_id":47178,"rank_level":40,"taxon_changes_count":2,"atlas_id":null,"complete_species_count":11492,"parent_id":47178,"complete_rank":"subspecies","name":"Perciformes","rank":"order","extinct":false,"id":47233,"default_photo":{"square_url":"https://static.inaturalist.org/photos/43021710/square.jpg?1561479662","attribution":"(c) Alejandra Lewandowski, all rights reserved","flags":[],"medium_url":"https://static.inaturalist.org/photos/43021710/medium.jpg?1561479662","id":43021710,"license_code":null,"original_dimensions":{"width":2048,"height":1365},"url":"https://static.inaturalist.org/photos/43021710/square.jpg?1561479662"},"ancestor_ids":[48460,1,2,355675,47178,47233],"iconic_taxon_name":"Actinopterygii","preferred_common_name":"Perch-like Fishes"},{"observations_count":5931,"taxon_schemes_count":1,"ancestry":"48460/1/2/355675/47178/47233","is_active":true,"flag_counts":{"unresolved":0,"resolved":0},"wikipedia_url":"http://en.wikipedia.org/wiki/Lutjanidae","current_synonymous_taxon_ids":null,"iconic_taxon_id":47178,"rank_level":30,"taxon_changes_count":0,"atlas_id":null,"complete_species_count":113,"parent_id":47233,"complete_rank":"subspecies","name":"Lutjanidae","rank":"family","extinct":false,"id":47295,"default_photo":{"square_url":"https://static.inaturalist.org/photos/97240/square.jpg?1444778096","attribution":"(c) 104623964081378888743, algunos derechos reservados (CC BY-NC-SA), uploaded by David R","flags":[],"medium_url":"https://static.inaturalist.org/photos/97240/medium.jpg?1444778096","id":97240,"license_code":"cc-by-nc-sa","original_dimensions":{"width":512,"height":410},"url":"https://static.inaturalist.org/photos/97240/square.jpg?1444778096"},"ancestor_ids":[48460,1,2,355675,47178,47233,47295],"iconic_taxon_name":"Actinopterygii","preferred_common_name":"Snappers"},{"observations_count":5649,"taxon_schemes_count":0,"ancestry":"48460/1/2/355675/47178/47233/47295","is_active":true,"flag_counts":{"unresolved":0,"resolved":0},"wikipedia_url":null,"current_synonymous_taxon_ids":null,"iconic_taxon_id":47178,"rank_level":27,"taxon_changes_count":0,"atlas_id":null,"complete_species_count":80,"parent_id":47295,"complete_rank":"subspecies","name":"Lutjaninae","rank":"subfamily","extinct":false,"id":788565,"default_photo":{"square_url":"https://static.inaturalist.org/photos/8050974/square.jpg?1495883597","attribution":"(c) Ian Shaw, all rights reserved","flags":[],"medium_url":"https://static.inaturalist.org/photos/8050974/medium.jpg?1495883597","id":8050974,"license_code":null,"original_dimensions":{"width":2048,"height":1357},"url":"https://static.inaturalist.org/photos/8050974/square.jpg?1495883597"},"ancestor_ids":[48460,1,2,355675,47178,47233,47295,788565],"iconic_taxon_name":"Actinopterygii"},{"observations_count":4777,"taxon_schemes_count":1,"ancestry":"48460/1/2/355675/47178/47233/47295/788565","is_active":true,"flag_counts":{"unresolved":0,"resolved":0},"wikipedia_url":"http://en.wikipedia.org/wiki/Lutjanus","current_synonymous_taxon_ids":null,"iconic_taxon_id":47178,"rank_level":20,"taxon_changes_count":0,"atlas_id":null,"complete_species_count":73,"parent_id":788565,"complete_rank":"subspecies","name":"Lutjanus","rank":"genus","extinct":false,"id":47296,"default_photo":{"square_url":"https://static.inaturalist.org/photos/66907/square.jpg?1545382259","attribution":"(c) Derek Keats, some rights reserved (CC BY-SA)","flags":[],"medium_url":"https://static.inaturalist.org/photos/66907/medium.jpg?1545382259","id":66907,"license_code":"cc-by-sa","original_dimensions":{"width":1024,"height":728},"url":"https://static.inaturalist.org/photos/66907/square.jpg?1545382259"},"ancestor_ids":[48460,1,2,355675,47178,47233,47295,788565,47296],"iconic_taxon_name":"Actinopterygii","preferred_common_name":"Common Snappers"}]},"previous_observation_taxon":{"taxon_schemes_count":1,"ancestry":"48460/1/2/355675/47178/47233/47295/788565/47296","min_species_ancestry":"48460,1,2,355675,47178,47233,47295,788565,47296,121459","wikipedia_url":"http://en.wikipedia.org/wiki/Lutjanus_fulvus","current_synonymous_taxon_ids":null,"iconic_taxon_id":47178,"created_at":"2011-12-08T10:53:04+00:00","taxon_changes_count":0,"complete_species_count":null,"rank":"species","extinct":false,"id":121459,"universal_search_rank":209,"ancestor_ids":[48460,1,2,355675,47178,47233,47295,788565,47296,121459],"observations_count":209,"is_active":true,"flag_counts":{"unresolved":0,"resolved":0},"min_species_taxon_id":121459,"rank_level":10,"atlas_id":null,"parent_id":47296,"complete_rank":"subspecies","name":"Lutjanus fulvus","default_photo":{"square_url":"https://static.inaturalist.org/photos/68937/square.jpg?1444773193","attribution":"(c) DavidR.808, some rights reserved (CC BY-NC-SA), uploaded by David R","flags":[],"medium_url":"https://static.inaturalist.org/photos/68937/medium.jpg?1444773193","id":68937,"license_code":"cc-by-nc-sa","original_dimensions":{"width":512,"height":341},"url":"https://static.inaturalist.org/photos/68937/square.jpg?1444773193"},"iconic_taxon_name":"Actinopterygii","preferred_common_name":"Blacktail Snapper"}}],"project_observations":[],"photos":[],"observation_photos":[],"application":{"id":398,"name":"BOLD2iNat","url":"https://editors.eol.org/eol_php_code/applications/BOLD2iNAT/","icon":"https://www.google.com/s2/favicons?domain=editors.eol.org"}}
*/

// print_r(pathinfo('http://www.boldsystems.org/index.php/API_Public/specimen?container=KANB&format=tsv')); exit;

include_once(dirname(__FILE__) . "/../../config/environment.php");
// /*
$GLOBALS['ENV_DEBUG'] = false;
$GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
// error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //report all errors except notice and warning
// */
ini_set('memory_limit','7096M');
require_library('connectors/BOLD2iNaturalistAPI');
$timestart = time_elapsed();

/* test
// $json = '{"access_token":"9700e251a7fe77c83efc7c6819abbe65cda07ae41f874403036d97b91c4b15b6","token_type":"Bearer","scope":"write login","created_at":1579520773}';
// $arr = json_decode($json, true);
// print_r($arr);
$url = 'http://www.boldsystems.org/pics/KANB/USNM_442246_photograph_KB17_073_110.5mmSL_LRP_17_13+1507842990.JPG';
print_r(pathinfo($url));
echo "\n".pathinfo($url, PATHINFO_BASENAME)."\n";
exit("\n\n");
*/

/* test
{"observation": {"description": "test observation"}}
{"observation": {"description": "test observation"}}

$arr['observation'] = array('description' => 'test observation');
$json = json_encode($arr);
exit("\n$json\n");
*/


$params['jenkins_or_cron']  = @$argv[1];
$params['filename']         = @$argv[2];
$params['form_url']         = @$argv[3];
$params['uuid']             = @$argv[4];
$params['json']             = @$argv[5];

// print_r($params); exit;
/*Array(
    [jenkins_or_cron] => jenkins
    [filename] => 1574915471.zip
)*/
if($val = $params['filename']) $filename = $val;
else                           $filename = '';
if($val = $params['form_url']) $form_url = $val;
else                           $form_url = '';
if($val = $params['uuid'])     $uuid = $val;
else                           $uuid = '';
if($val = $params['json'])     $json = $val;
else                           $json = '';

$resource_id = ''; //no longer used from here
$func = new BOLD2iNaturalistAPI('bold2inat');
$func->start($filename, $form_url, $uuid, $json);
// Functions::get_time_elapsed($timestart);
?>