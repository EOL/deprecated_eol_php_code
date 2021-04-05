<?php
namespace php_active_record;
THIS IS THE VERSION WITH STILL VANGELIS SCRIPTS THAT ARE COMMENTED.
/* DATA-1851: reconstructing the Environments-EOL resource
Next step now is to combine all the steps within a general connector:
1. read any EOL DwCA resource (with text objects)
2. generate individual txt files for the articles with filename convention.
3. run environment_tagger (or Pensoft annotator) against these text files
4. generate the raw file: eol_tags_noParentTerms.tsv
5. generate the updated DwCA resource, now with Trait data from Environments
    5.1 append in MoF the new environments trait data
    5.2 include the following (if available) from the text object where trait data was derived from. Reflect this in the MoF.
      5.2.1 source - http://purl.org/dc/terms/source
      5.2.2 bibliographicCitation - http://purl.org/dc/terms/bibliographicCitation
      5.2.3 contributor - http://purl.org/dc/terms/contributor
      5.2.4 referenceID - http://eol.org/schema/reference/referenceID
      5.2.5 agendID -> contributor

Implementation: Vangelis - OBSOLETE
php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags", "resource":"AmphibiaWeb text", "resource_id":"21", "subjects":"Distribution"}'
php update_resources/connectors/environments_2_eol.php _ '{"task": "apply_formats_filters", "resource_id":"21"}'
php update_resources/connectors/environments_2_eol.php _ '{"task": "apply_formats_filters_latest", "resource_id":"21"}'

21_final	Mon 2020-09-07 06:20:02 AM	{"agent.tab":743, "MoF":8961, "media_resource.tab":8138, "occurrence.tab":8961, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":{"sec":47.32, "min":0.79, "hr":0.01}}
21_final	Tue 2020-09-08 01:09:17 AM	{"agent.tab":743, "MoF":8961, "media_resource.tab":8138, "occurrence.tab":8961, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":{"sec":48.43, "min":0.81, "hr":0.01}}
21_final	Mon 2020-09-14 04:24:19 AM	{"agent.tab":743, "MoF":8961, "media_resource.tab":8138, "occurrence.tab":8961, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":{"sec":47.69, "min":0.79, "hr":0.01}}
21_final	Mon 2020-09-14 12:23:34 PM	{"agent.tab":743, "MoF":7094, "media_resource.tab":8138, "occurrence.tab":7094, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":{"sec":47.03, "min":0.78, "hr":0.01}}

617_ENV	            Wed 2020-11-04 08:10:56 AM	{"MoF.tab":176794, "occurrence.tab":176794, "taxon.tab":411865, "time_elapsed":false}
wikipedia_en_traits	Wed 2020-11-04 08:39:13 AM	{"MoF.tab":176794, "occurrence.tab":176794, "taxon.tab":91492, "time_elapsed":false}

================================================== Vangelis tagger START ================================================== 
Implementation: Jenkins
cd /u/scripts/eol_php_code/
php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags", "resource":"wikipedia English", "resource_id":"617", "subjects":"Description"}'
-> generates 617_ENV.tar.gz
php update_resources/connectors/environments_2_eol.php _ '{"task": "apply_formats_filters", "resource_id":"617"}'
-> generates 617_ENVO.tar.gz
php update_resources/connectors/environments_2_eol.php _ '{"task": "apply_formats_filters_latest", "resource_id":"617"}'
-> generates 617_final.tar.gz

## Wikipedia EN creates a new DwCA for its traits. Not like 'AmphibiaWeb text'.
## Thus there is a new line for Wikipedia EN: it removes taxa without MoF
php update_resources/connectors/remove_taxa_without_MoF.php _ '{"resource_id": "617_final"}'
-> generates wikipedia_en_traits.tar.gz

617_final	        Mon 2020-09-07 11:41:14 PM	{"MoF":818305, "occurrence.tab":818305, "taxon.tab":410005, "time_elapsed":{"sec":596.04, "min":9.93, "hr":0.17}}
wikipedia_en_traits	Mon 2020-09-07 11:51:11 PM	{"MoF":818305, "occurrence.tab":818305, "taxon.tab":160598, "time_elapsed":false}

617_final	Tue 2020-09-08 02:05:06 AM	        {"MoF":818305, "occurrence.tab":818305, "taxon.tab":410005, "time_elapsed":{"sec":598.92, "min":9.98, "hr":0.17}}
wikipedia_en_traits	Tue 2020-09-08 02:15:01 AM	{"MoF":818305, "occurrence.tab":818305, "taxon.tab":160598, "time_elapsed":false}

617_final	Tue 2020-09-08 11:27:07 PM	        {"MoF":818305, "occurrence.tab":818305, "taxon.tab":410005, "time_elapsed":{"sec":600.27, "min":10, "hr":0.17}}
wikipedia_en_traits	Tue 2020-09-08 11:37:05 PM	{"MoF":818305, "occurrence.tab":818305, "taxon.tab":160598, "time_elapsed":false}

617_final	Mon 2020-09-14 05:26:31 AM	        {"MoF":818251, "occurrence.tab":818251, "taxon.tab":410005, "time_elapsed":{"sec":597.53, "min":9.96, "hr":0.17}}
wikipedia_en_traits	Mon 2020-09-14 05:36:27 AM	{"MoF":818251, "occurrence.tab":818251, "taxon.tab":160591, "time_elapsed":false}

Started cleaning eol_tags.tsv and eol_tags_noParentTerms.tsv
617_final	Mon 2020-09-14 01:02:29 PM	        {"MoF":509013, "occurrence.tab":509013, "taxon.tab":410005, "time_elapsed":{"sec":433.21, "min":7.22, "hr":0.12}}
wikipedia_en_traits	Mon 2020-09-14 01:08:45 PM	{"MoF":509013, "occurrence.tab":509013, "taxon.tab":160580, "time_elapsed":false}
wikipedia_en_traits	Thu 2020-10-01 12:41:54 PM	{"MoF":500273, "occurrence.tab":500273, "taxon.tab":160372, "time_elapsed":false}
wikipedia_en_traits	Thu 2020-10-08 02:10:08 AM	{"MoF":500273, "occurrence.tab":500273, "taxon.tab":160372, "time_elapsed":false}
start deduplication below:
wikipedia_en_traits	Mon 2020-10-12 10:36:39 AM	{"MoF":426193, "occurrence.tab":426193, "taxon.tab":157390, "time_elapsed":false}
================================================== Vangelis tagger END ================================================== 
## different DwCA to submit for Wikipedia EN
cd /u/scripts/eol_php_code/applications/content_server/resources/
sshpass -f "/home/eagbayani/.pwd_file" scp wikipedia_en_traits.tar.gz eagbayani@eol-archive:/extra/eol_php_resources/.
#ends here...

## move this file for all connectors:
sshpass -f "/home/eagbayani/.pwd_file" scp EOL_FreshData_connectors.txt eagbayani@eol-archive:/extra/eol_php_resources/eol_backend2_connectors.txt

================================================== Pensoft annotator START ================================================== 
Implementation: Jenkins - Pensoft: we can run 3 connectors in eol-archive simultaneously.

php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"AmphibiaWeb text", "resource_id":"21", "subjects":"Distribution"}'
-> generates 21_ENV.tar.gz
--------------------------------------------------------------------------------------------------------------------
php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"wikipedia English", "resource_id":"617", "subjects":"Description"}'
// php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"wikipedia English", "resource_id":"617", "subjects":"Distribution"}'
// php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"wikipedia English", "resource_id":"617", "subjects":"TaxonBiology"}'

Implementation: Jenkins: English Wikipedia
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"wikipedia English", "resource_id":"617", "subjects":"Description"}'
#generates 617_ENV.tar.gz

## Wikipedia EN creates a new DwCA for its traits. Not like 'AmphibiaWeb text'.
## Thus there is a new line for Wikipedia EN: it removes taxa without MoF
php5.6 remove_taxa_without_MoF.php jenkins '{"resource_id": "617_ENV"}'
#generates wikipedia_en_traits.tar.gz
--------------------------------------------------------------------------------------------------------------------
php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"World Register of Marine Species", "resource_id":"26", "subjects":"Habitat"}'

--------------------------------------------------------------------------------------------------------------------
================================================== Pensoft annotator END ================================================== Wikipedia English
Started using Pensoft:
617_ENV	            Thu 2020-11-05 07:49:33 AM	{"MoF.tab":176794, "occurrence.tab":176794, "taxon.tab":411865, "time_elapsed":false}
wikipedia_en_traits	Thu 2020-11-05 07:52:07 AM	{"MoF.tab":176794, "occurrence.tab":176794, "taxon.tab":91492, "time_elapsed":false}
Started Jen's mapping:
617_ENV	            Thu 2020-11-05 08:55:41 AM	{"MoF.tab":234579, "occurrence.tab":234579, "taxon.tab":411865, "time_elapsed":false}
wikipedia_en_traits	Thu 2020-11-05 08:58:54 AM	{"MoF.tab":234579, "occurrence.tab":234579, "taxon.tab":120059, "time_elapsed":false}
Addtl Jen's mapping: filtering out (removing) more rows in entities file.
617_ENV	            Wed 2020-12-09 08:07:57 AM	{"MoF.tab":169640, "occurrence.tab":169640, "taxon.tab":411865, "time_elapsed":false}
wikipedia_en_traits	Wed 2020-12-09 08:10:33 AM	{"MoF.tab":169640, "occurrence.tab":169640, "taxon.tab":102305, "time_elapsed":false}

617_ENV	Sun 2020-12-13 09:54:10 PM	                {"MoF":169640, "occurrence.tab":169640, "taxon.tab":412880, "time_elapsed":false}
wikipedia_en_traits_FTG	Sun 2020-12-13 10:29:36 PM	{"MoF":167647, "occurrence.tab":167647, "taxon.tab":412880, "time_elapsed":{"sec":794.64, "min":13.24, "hr":0.22}}
wikipedia_en_traits	Sun 2020-12-13 10:31:59 PM	    {"MoF":167647, "occurrence.tab":167647, "taxon.tab":101544, "time_elapsed":false}

wikipedia_en_traits_FTG	Sun 2020-12-13 11:56:49 PM	{"MoF":167647, "occurrence.tab":167647, "taxon.tab":412880, "time_elapsed":{"sec":805.86, "min":13.43, "hr":0.22}}
wikipedia_en_traits	Sun 2020-12-13 11:59:11 PM	    {"MoF":167647, "occurrence.tab":167647, "taxon.tab":101544, "time_elapsed":false}

- with latest monthly Wikipedia EN refresh (Dec 2020)
- with the new "terms_to_remove" list
617_ENV	Thu 2020-12-17 04:47:46 AM	                {"MoF.tab":171067, "occurrence.tab":171067, "taxon.tab":412880, "time_elapsed":{"sec":3160.23, "min":52.67, "hr":0.88}}
wikipedia_en_traits_FTG	Thu 2020-12-17 05:00:26 AM	{"MoF.tab":169039, "occurrence.tab":169039, "taxon.tab":412880, "time_elapsed":{"sec":751.7, "min":12.53, "hr":0.21}}
wikipedia_en_traits	Thu 2020-12-17 05:02:54 AM	    {"MoF.tab":169039, "occurrence.tab":169039, "taxon.tab":102305, "time_elapsed":false}

- removed 'sea' - expected decrease in MoF
617_ENV	Sun 2021-03-07 09:15:58 PM	                {"MoF.tab":131650, "occurrence.tab":131650, "taxon.tab":412880, "time_elapsed":{"sec":3687.23, "min":61.45, "hr":1.02}}
wikipedia_en_traits_FTG	Sun 2021-03-07 09:27:59 PM	{"MoF.tab":131477, "occurrence.tab":131477, "taxon.tab":412880, "time_elapsed":{"sec":714.08, "min":11.9, "hr":0.2}}
wikipedia_en_traits	Sun 2021-03-07 09:29:48 PM	    {"MoF.tab":131477, "occurrence.tab":131477, "taxon.tab":74839, "time_elapsed":false}

---------------Jenkins entry in eol-archive
cd /html/eol_php_code/update_resources/connectors

--------------------------------------------------------------------------------------- PDF repository 10088_5097
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"10088_5097", "subjects":"Description"}'
---------------------------------------------------------------------------------------

#step 1
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"wikipedia English", "resource_id":"617", "subjects":"Description"}'
#generates 617_ENV.tar.gz

#step 2: just a utility
#these 3 is just for stats = generates 3 reports
#php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1357", "habitat_filter": "saline water"}'
#php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1390", "habitat_filter": "saline water"}'
#php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q10908", "habitat_filter": "saline water"}'

#main operation is:
php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1390, Q1357, Q10908", "habitat_filter": "saline water"}'
#generates wikipedia_en_traits_FTG.tar.gz

#step 3: final step
## Wikipedia EN creates a new DwCA for its traits. Not like 'AmphibiaWeb text'.
## Thus there is a new line for Wikipedia EN: it removes taxa without MoF
php5.6 remove_taxa_without_MoF.php jenkins '{"resource_id": "wikipedia_en_traits_FTG"}'
#generates wikipedia_en_traits.tar.gz

===================================================================================================================== AmphibiaWeb
21_ENV	Wed 2020-12-02 07:01:55 PM	{"agent.tab":743, "MoF":2202, "media_resource.tab":8138, "occurrence.tab":2202, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":false}
START differentiate Wikipedia EN and other resources when treated by Pensoft. Expected increase in MoF
21_ENV	Wed 2020-12-02 08:18:37 PM	{"agent.tab":743, "MoF":2468, "media_resource.tab":8138, "occurrence.tab":2468, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":false}
started removing 'salt water' and its descendants:
21_ENV	Tue 2020-12-08 01:06:33 AM	{"agent.tab":743, "MoF":2085, "media_resource.tab":8138, "occurrence.tab":2085, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":false}
21_ENV	Tue 2020-12-08 01:17:47 AM	{"agent.tab":743, "MoF":1986, "media_resource.tab":8138, "occurrence.tab":1986, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":false}
21_ENV	Tue 2020-12-15 05:25:08 PM	{"agent.tab":743, "MoF":1986, "media_resource.tab":8138, "occurrence.tab":1986, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":{"sec":51.57, "min":0.86, "hr":0.01}}
XML from partnet refreshed:
21	Tue 2020-12-15 06:35:57 PM	    {"agent.tab":834,             "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":12.54, "min":0.21, "hr":0}}
21_ENV	Tue 2020-12-15 06:38:14 PM	{"agent.tab":834, "MoF":2097, "media_resource.tab":8454, "occurrence.tab":2097, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":136.82, "min":2.28, "hr":0.04}}
with the new "terms_to_remove" list (Unlike AntWeb, AmphibiaWeb is not affected.)
21	Thu 2020-12-17 03:47:05 AM	    {"agent.tab":834,             "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":12.57, "min":0.21, "hr":0}}
21_ENV	Thu 2020-12-17 03:48:10 AM	{"agent.tab":834, "MoF":2097, "media_resource.tab":8454, "occurrence.tab":2097, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":64.89, "min":1.08, "hr":0.02}}
removed 'sea' - expected decrease in MoF
21	Mon 2021-03-08 01:21:31 AM	    {"agent.tab":834,                 "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":14.44, "min":0.24, "hr":0}}
21_ENV	Mon 2021-03-08 01:22:27 AM	{"agent.tab":834, "MoF.tab":2094, "media_resource.tab":8454, "occurrence.tab":2094, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":55.83, "min":0.93, "hr":0.02}}
===================================================================================================================== WoRMS
HOW TO RUN:
php5.6 26.php jenkins
#generates 26.tar.gz

php5.6 resource_utility.php jenkins '{"resource_id": "26_meta_recoded_1", "task": "metadata_recoding"}'
#generates 26_meta_recoded_1.tar.gz

php5.6 resource_utility.php jenkins '{"resource_id": "26_meta_recoded", "task": "metadata_recoding"}'
#generates 26_meta_recoded.tar.gz

php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"World Register of Marine Species", 
                                        "resource_id":"26", "subjects":"Habitat|Distribution"}'
#generates 26_ENV
----------------------------------
26	Tue 2020-11-10 01:54:23 AM	     "agent.tab":1682, "measurement_or_fact.tab":3325053,       "media_resource.tab":91653, "occurrence_specific.tab":2157834, 
        "reference.tab":670315, "taxon.tab":367878, "vernacular_name.tab":82322, "time_elapsed":false}

Separated MoF: original traits and new traits from Habitats textmining:
26_ENV	Sun 2020-12-20 10:39:45 PM	{"agent.tab":1690,  "measurement_or_fact.tab":2544844, 
                                                        "measurement_or_fact_specific.tab":3752,            "media_resource.tab":91778, 
                                                                    "occurrence.tab":2167107, 
                                                                    "occurrence_specific.tab":3752, 
        "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":{"sec":2791.51, "min":46.53, "hr":0.78}}
Combined MoF:
26_ENV	Mon 2020-12-21 03:07:07 AM	{"agent.tab":1690, "measurement_or_fact_specific.tab":2548596, "media_resource.tab":91778, 
"occurrence_specific.tab":2170859, "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":{"sec":2605.43, "min":43.42, "hr":0.72}}
26_ENV	Mon 2020-12-21 10:02:56 AM	{"agent.tab":1690, "measurement_or_fact_specific.tab":2548596, "media_resource.tab":91778, 
"occurrence_specific.tab":2170859, "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":{"sec":2733, "min":45.55, "hr":0.76}}
26_ENV	Thu 2020-12-24 01:35:09 AM	{"agent.tab":1690, "measurement_or_fact_specific.tab":2548585, "media_resource.tab":91778, 
"occurrence_specific.tab":2170848, "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":{"sec":2537.16, "min":42.29, "hr":0.7}}

Time where error is admitted. Causing big increase in occurrence here. Found the problem -> in_array($resource_id, array()). resource_id has to be "26" and not 26 integer.
Another change here is adding lifeStage URI instead of just string.
26_ENV	Fri 2021-02-05 02:40:16 AM	{"agent.tab":1709, "measurement_or_fact_specific.tab":2552252, "media_resource.tab":92007, 
"occurrence_specific.tab":4335418, "reference.tab":677563, "taxon.tab":369567, "vernacular_name.tab":85178, "time_elapsed":{"sec":3080.13, "min":51.34, "hr":0.86}}

Here should fix the big occurrence:
26	Mon 2021-02-08 05:35:41 AM	{"agent.tab":1709, "measurement_or_fact_specific.tab":3335411, "media_resource.tab":92007, "occurrence_specific.tab":2163968, "reference.tab":677563, "taxon.tab":369567, "vernacular_name.tab":85178, "time_elapsed":false}
26_meta_recoded_1	Mon 2021-02-08 06:48:16 AM	{"agent.tab":1709, "measurement_or_fact_specific.tab":3190943, "media_resource.tab":92007, "occurrence.tab":2163968, "reference.tab":677563, "taxon.tab":369567, "vernacular_name.tab":85178, "time_elapsed":{"sec":3256.18, "min":54.27, "hr":0.9}}
26_meta_recoded	Mon 2021-02-08 07:35:52 AM	{"agent.tab":1709, "measurement_or_fact_specific.tab":2544770, "media_resource.tab":92007, 
"occurrence_specific.tab":2163968, "reference.tab":677563, "taxon.tab":369567, "vernacular_name.tab":85178, "time_elapsed":{"sec":2855.44, "min":47.59, "hr":0.79}}
26_ENV	Mon 2021-02-08 08:18:55 AM	{"agent.tab":1709, "measurement_or_fact_specific.tab":2548511, "media_resource.tab":92007, "occurrence_specific.tab":2167709, "reference.tab":677563, "taxon.tab":369567, "vernacular_name.tab":85178, "time_elapsed":{"sec":2582.03, "min":43.03, "hr":0.72}}
removed 'sea' - expected decrease in MoF
26_ENV	Wed 2021-03-10 05:09:58 AM	{"agent.tab":1749, "measurement_or_fact_specific.tab":2541359, "media_resource.tab":92097, "occurrence_specific.tab":2167217, "reference.tab":680112, "taxon.tab":370148, "vernacular_name.tab":85180, "time_elapsed":{"sec":2552.51, "min":42.54, "hr":0.71}}
now with Distribution textmined using eol-geonames ontology
26_ENV	Wed 2021-03-17 10:07:45 AM	{"agent.tab":1749, "measurement_or_fact_specific.tab":2578440, "media_resource.tab":92097, "occurrence_specific.tab":2204298, "reference.tab":680112, "taxon.tab":370148, "vernacular_name.tab":85180, "time_elapsed":{"sec":2927.3, "min":48.79, "hr":0.81}}
now with orig location string textmined in MoF
26_ENV	Mon 2021-03-22 02:27:35 AM	{"agent.tab":1749, "measurement_or_fact_specific.tab":2530239, "media_resource.tab":92097, "occurrence_specific.tab":2156097, "reference.tab":680112, "taxon.tab":370148, "vernacular_name.tab":85180, "time_elapsed":{"sec":2962.03, "min":49.37, "hr":0.82}}
now with exclude many URI from Jen's geo_synonyms.txt
26_ENV	Thu 2021-03-25 12:13:18 PM	{"agent.tab":1749, "measurement_or_fact_specific.tab":2425694, "media_resource.tab":92097, "occurrence_specific.tab":2051552, "reference.tab":680112, "taxon.tab":370148, "vernacular_name.tab":85180, "time_elapsed":{"sec":3229.15, "min":53.82, "hr":0.9}}
now added source where there is none
26_ENV	Mon 2021-03-29 12:56:36 PM	{"agent.tab":1749, "measurement_or_fact_specific.tab":2425694, "media_resource.tab":92097, "occurrence_specific.tab":2051552, "reference.tab":680112, "taxon.tab":370148, "vernacular_name.tab":85180, "time_elapsed":{"sec":3329.67, "min":55.49, "hr":0.92}}
===================================================================================================================== WoRMS end
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;
ini_set('memory_limit','8096M'); //required

$timestart = time_elapsed();
// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$task = $param['task'];
$resource = @$param['resource'];

/*
$a1 = array('blue', 'orange', 'green', 'red');
$a2 = array('orange', 'red');
$a3 = array_diff($a1, $a2);
// $a3 = array_diff($a2, $a1);
print_r($a1); print_r($a2); print_r($a3); exit;
*/
/* during development only. Not part of main operation
require_library('connectors/Environments2EOLAPI');
$func = new Environments2EOLAPI($param);
// $func->clean_eol_tags_tsv(); //works OK
$func->clean_noParentTerms(); //works OK
exit("\n-end-\n");
*/

if($task == 'generate_eol_tags_pensoft') {
    $param['resource_id'] .= "_ENV"; //e.g. 21_ENV 617_ENV (destination)
    require_library('connectors/Pensoft2EOLAPI');
    $func = new Pensoft2EOLAPI($param);
    $download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*10); //expires in 10 days. Mostly connector refreshes once a month.
    // /* customize
    if($param['resource_id'] == '21_ENV') $download_options = array('timeout' => 172800, 'expire_seconds' => 0);
    // */
    $func->generate_eol_tags_pensoft($resource, $timestart, $download_options);
}

/* OBSOLETE: used using Vangelis tagger
if($task == 'generate_eol_tags') {                      //step 1            this will become OBSOLETE
    $param['resource_id'] .= "_ENV"; //e.g. 21_ENV 617_ENV (destination)
    require_library('connectors/Environments2EOLAPI');
    $func = new Environments2EOLAPI($param);
    $func->generate_eol_tags($resource);
}
elseif($task == 'apply_formats_filters') {              //step 2
    $param['resource_id'] .= "_ENVO";
    $resource_id = $param['resource_id']; //e.g. 21_ENVO 617_ENVO (destination)
    $old_resource_id = substr($resource_id, 0, strlen($resource_id)-1); //should get "21_ENV" "617_ENV"
    $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$old_resource_id.'.tar.gz';
    $dwca_file = '/u/scripts/eol_php_code/applications/content_server/resources/'.$old_resource_id.'.tar.gz';
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    $preferred_rowtypes = array();
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
    // $excluded_rowtypes will be processed in EnvironmentsFilters.php which will be called from DwCA_Utility.php
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
elseif($task == 'apply_formats_filters_latest') {       //step 3
    $param['resource_id'] .= "_final";
    $resource_id = $param['resource_id']; //e.g. 21_final 617_final (destination)
    $old_resource_id = str_replace('_final', '_ENVO', $resource_id); //e.g. 21_ENVO 617_ENVO
    if(Functions::is_production()) $dwca_file = '/u/scripts/eol_php_code/applications/content_server/resources/'.$old_resource_id.'.tar.gz';
    else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$old_resource_id.'.tar.gz';
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    $preferred_rowtypes = array();
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence', 
                               'http://rs.tdwg.org/dwc/terms/taxon');
    // $excluded_rowtypes will be processed in New_EnvironmentsEOLDataConnector.php which will be called from DwCA_Utility.php. Part of legacy filters.
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
*/
?>