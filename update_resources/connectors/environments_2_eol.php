<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1739
DATA-1851: reconstructing the Environments-EOL resource
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
============================================================================================================================
## different DwCA to submit for Wikipedia EN
cd /u/scripts/eol_php_code/applications/content_server/resources/
sshpass -f "/home/eagbayani/.pwd_file" scp wikipedia_en_traits.tar.gz eagbayani@eol-archive:/extra/eol_php_resources/.
#ends here...

## move this file for all connectors:
sshpass -f "/home/eagbayani/.pwd_file" scp EOL_FreshData_connectors.txt eagbayani@eol-archive:/extra/eol_php_resources/eol_backend2_connectors.txt

================================================== Pensoft annotator START ================================================== 
Implementation: Jenkins - Pensoft: we can run 3 connectors in eol-archive simultaneously.

php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"AmphibiaWeb text", "resource_id":"21", "subjects":"Distribution"}'
php5.6                    environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"AmphibiaWeb text", "resource_id":"21", "subjects":"Distribution"}'

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
================================================== Pensoft annotator END ================================ Wikipedia English
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
after monthly harvest:
wikipedia_en_traits_FTG	Fri 2021-04-16 09:40:13 AM	{"MoF.tab":148313, "occurrence.tab":148313, "taxon.tab":420490, "time_elapsed":{"sec":818.31, "min":13.64, "hr":0.23}}
wikipedia_en_traits	Fri 2021-04-16 09:42:18 AM	    {"MoF.tab":148313, "occurrence.tab":148313, "taxon.tab":81756, "time_elapsed":false} - consistent OK
removed traits for specified rank:
wikipedia_en_traits_FTG	Wed 2021-05-12 07:20:51 AM	{"MoF.tab":145396, "occurrence.tab":145396, "taxon.tab":420490, "time_elapsed":{"sec":757.46, "min":12.62, "hr":0.21}}
wikipedia_en_traits	Wed 2021-05-12 07:22:53 AM	    {"MoF.tab":145396, "occurrence.tab":145396, "taxon.tab":80650, "time_elapsed":false}
-> expected decrease in MoF
wikipedia_en_traits_FTG	Wed 2021-06-16 06:14:13 AM	{"MoF.tab":147964, "occurrence.tab":147964, "taxon.tab":424566, "time_elapsed":{"sec":801.07, "min":13.35, "hr":0.22}}
wikipedia_en_traits	Wed 2021-06-16 06:16:20 AM	    {"MoF.tab":147964, "occurrence.tab":147964, "taxon.tab":81677, "time_elapsed":false}
wikipedia_en_traits	Tue 2021-08-03 07:38:06 AM	    {"MoF.tab":151048, "occurrence.tab":151048, "taxon.tab":83240, "time_elapsed":false}

wikipedia_en_traits_tmp1	Tue 2021-08-31 11:38:54 {"MoF.tab":151048, "occurrence.tab":151048, "taxon.tab":83240, "time_elapsed":false}
wikipedia_en_traits	Tue 2021-08-31 11:40:53 AM	    {"MoF.tab":148894, "occurrence.tab":148894, "taxon.tab":83240, "time_elapsed":false} wrong!
wikipedia_en_traits	Tue 2021-08-31 11:50:27 AM	    {"MoF.tab":149887, "occurrence.tab":149887, "taxon.tab":83240, "time_elapsed":false} correct OK
after DATA-1893:
wikipedia_en_traits	Wed 2021-10-13 09:32:56 AM	    {"MoF.tab":149887, "occurrence.tab":149887, "taxon.tab":83240, "time_elapsed":false}

wikipedia_en_traits_FTG	Wed 2021-10-20 08:11:23 PM	{"MoF.tab":151048, "occurrence.tab":151048, "taxon.tab":430102, "time_elapsed":{"sec":867.93, "min":14.47, "hr":0.24}}
wikipedia_en_traits_tmp1	Wed 2021-10-20 08:13:42 {"MoF.tab":151048, "occurrence.tab":151048, "taxon.tab":83240, "time_elapsed":false}
wikipedia_en_traits	Wed 2021-10-20 08:15:56 PM	    {"MoF.tab":149887, "occurrence.tab":149887, "taxon.tab":83240, "time_elapsed":false}

start removed entities file (Vangelis' file) and use EOL terms file exclusively cause substantial increase in MoF than usual
wikipedia_en_traits_FTG	Tue 2022-02-01 05:29:21 PM	{"MoF.tab":268838, "occurrence.tab":268838, "taxon.tab":439104, "time_elapsed":{"sec":938.35, "min":15.64, "hr":0.26}}
wikipedia_en_traits_tmp1	Tue 2022-02-01 05:32:51 {"MoF.tab":268838, "occurrence.tab":268838, "taxon.tab":114758, "time_elapsed":false}
wikipedia_en_traits	Tue 2022-02-01 05:36:01 PM	    {"MoF.tab":257608, "occurrence.tab":257608, "taxon.tab":114758, "time_elapsed":false}

now generates wikipedia_en_traits_tmp2 instead of wikipedia_en_traits
wikipedia_en_traits_tmp2	Sat 2022-04-09 08:55:22 {"MoF.tab":257608, "occurrence.tab":257608, "taxon.tab":114758, "time_elapsed":false}
-> correct tally with last wikipedia_en_traits

Below start: remove all records for taxon with habitat value(s) that are descendants of both marine and terrestrial
wikipedia_en_traits_tmp3	Sat 2022-04-09 10:31:46 {"MoF.tab":240020, "occurrence.tab":240020, "taxon.tab":111796, "time_elapsed":{"sec":255.15, "min":4.25, "hr":0.07}} Mac Mini
wikipedia_en_traits_tmp3	Sun 2022-04-10 09:28:49 {"MoF.tab":240020, "occurrence.tab":240020, "taxon.tab":111796, "time_elapsed":{"sec":189.46, "min":3.16, "hr":0.05}} eol-archive
This will then be copied (cp in Jenkins terminal) to wikipedia_en_traits.tar.gz

LATEST HARVEST - GOOD STEADY INCREASE
617_ENV	Wed 2022-05-04 04:50:45 AM	                {"MoF.tab":273880, "occurrence.tab":273880, "taxon.tab":438670, "time_elapsed":{"sec":239413.12, "min":3990.22, "hr":66.5, "day":2.77}}
wikipedia_en_traits_FTG	Wed 2022-05-04 05:05:26 AM	{"MoF.tab":272695, "occurrence.tab":272695, "taxon.tab":438670, "time_elapsed":{"sec":875.51, "min":14.59, "hr":0.24}}
wikipedia_en_traits_tmp1	Wed 2022-05-04 05:09:05 {"MoF.tab":272695, "occurrence.tab":272695, "taxon.tab":116322, "time_elapsed":false}
wikipedia_en_traits_tmp2	Wed 2022-05-04 05:12:10 {"MoF.tab":261236, "occurrence.tab":261236, "taxon.tab":116322, "time_elapsed":false}
wikipedia_en_traits_tmp3	Wed 2022-05-04 05:15:14 {"MoF.tab":243419, "occurrence.tab":243419, "taxon.tab":113285, "time_elapsed":{"sec":184.05, "min":3.07, "hr":0.05}}

617_ENV	Tue 2022-05-24 06:49:06 PM	                {"MoF.tab":273880, "occurrence.tab":273880, "taxon.tab":438670, "time_elapsed":{"sec":20993.82, "min":349.9, "hr":5.83}}
wikipedia_en_traits_FTG	Tue 2022-05-24 07:04:59 PM	{"MoF.tab":272695, "occurrence.tab":272695, "taxon.tab":438670, "time_elapsed":{"sec":934.1, "min":15.57, "hr":0.26}}
wikipedia_en_traits_tmp1	Tue 2022-05-24 07:08:43 {"MoF.tab":272695, "occurrence.tab":272695, "taxon.tab":116322, "time_elapsed":false}
wikipedia_en_traits_tmp2	Tue 2022-05-24 07:12:24 {"MoF.tab":272297, "occurrence.tab":272297, "taxon.tab":116322, "time_elapsed":false}
wikipedia_en_traits_tmp3	Tue 2022-05-24 07:15:38 {"MoF.tab":253641, "occurrence.tab":253641, "taxon.tab":113217, "time_elapsed":{"sec":193.81, "min":3.23, "hr":0.05}}

wikipedia_en_traits_tmp1	Wed 2022-05-25 09:39:20 {"MoF.tab":272695, "occurrence.tab":272695, "taxon.tab":171844, "time_elapsed":false}
wikipedia_en_traits_tmp2	Wed 2022-05-25 09:43:02 {"MoF.tab":272297, "occurrence.tab":272297, "taxon.tab":171844, "time_elapsed":false}
wikipedia_en_traits_tmp3	Wed 2022-05-25 09:46:33 {"MoF.tab":253641, "occurrence.tab":253641, "taxon.tab":168739, "time_elapsed":{"sec":210.2, "min":3.5, "hr":0.06}}

wikipedia_en_traits_tmp3	Wed 2022-05-25 09:05:40 {"MoF.tab":253641, "occurrence.tab":253641, "taxon.tab":168739, "time_elapsed":{"sec":208.15, "min":3.47, "hr":0.06}}
wikipedia_en_traits_tmp4	Wed 2022-05-25 09:11:12 {"MoF.tab":253641, "occurrence.tab":253641, "taxon.tab":169088, "time_elapsed":{"sec":329.25, "min":5.49, "hr":0.09}}
wikipedia_en_traits_tmp4	Wed 2022-05-25 09:15:11 {"MoF.tab":253641, "occurrence.tab":253641, "taxon.tab":169090, "time_elapsed":{"sec":568.65, "min":9.48, "hr":0.16}}
wikipedia_en_traits_tmp4	Tue 2022-07-19 06:19:03 {"MoF.tab":259711, "occurrence.tab":259711, "taxon.tab":171805, "time_elapsed":{"sec":599.05, "min":9.98, "hr":0.17}} consistent OK

wikipedia_en_traits_FTG	Wed 2022-09-14 05:40:37 PM	{"MoF.tab":278959, "occurrence.tab":278959, "taxon.tab":442781, "time_elapsed":{"sec":918.44, "min":15.31, "hr":0.26}}
wikipedia_en_traits_tmp1	Wed 2022-09-14 05:44:42 PM	{"MoF.tab":278959, "occurrence.tab":278959, "taxon.tab":174676, "time_elapsed":false}
wikipedia_en_traits_tmp2	Wed 2022-09-14 05:48:25 PM	{"MoF.tab":278548, "occurrence.tab":278548, "taxon.tab":174676, "time_elapsed":false}
wikipedia_en_traits_tmp3	Wed 2022-09-14 05:51:59 PM	{"MoF.tab":259433, "occurrence.tab":259433, "taxon.tab":171482, "time_elapsed":{"sec":213.51, "min":3.56, "hr":0.06}}
wikipedia_en_traits_tmp4	Wed 2022-09-14 05:56:58 PM	{"MoF.tab":259433, "occurrence.tab":259433, "taxon.tab":171837, "time_elapsed":{"sec":296.06, "min":4.93, "hr":0.08}}
wikipedia_en_traits_tmp4	Wed 2022-09-14 06:00:58 PM	{"MoF.tab":259433, "occurrence.tab":259433, "taxon.tab":171838, "time_elapsed":{"sec":535.86, "min":8.93, "hr":0.15}}
-> this batch Sep 14, final resource has a slight decrease in nos. Still very acceptable.
wikipedia_en_traits_tmp4	Wed 2022-10-26 04:57:19 PM	{"MoF.tab":219379, "occurrence.tab":219379, "taxon.tab":162502, "time_elapsed":{"sec":325.58, "min":5.43, "hr":0.09}}
wikipedia_en_traits_tmp4	Wed 2022-10-26 05:00:45 PM	{"MoF.tab":219379, "occurrence.tab":219379, "taxon.tab":162503, "time_elapsed":{"sec":532.1, "min":8.87, "hr":0.15}}
-> this batch Oct 26 has expected decrease when 'well' and others are deliberately removed in MoF
Below with strict EOL terms file filter
617_ENV	Fri 2022-11-18 10:58:36 PM	                    {"MoF.tab":218225, "occurrence.tab":218225, "taxon.tab":442781, "time_elapsed":{"sec":2900.23, "min":48.34, "hr":0.81}}
wikipedia_en_traits_FTG	Fri 2022-11-18 11:12:18 PM	    {"MoF.tab":217553, "occurrence.tab":217553, "taxon.tab":442781, "time_elapsed":{"sec":808.16, "min":13.47, "hr":0.22}}
wikipedia_en_traits_tmp1	Fri 2022-11-18 11:15:25 PM	{"MoF.tab":217553, "occurrence.tab":217553, "taxon.tab":164469, "time_elapsed":false}
wikipedia_en_traits_tmp2	Fri 2022-11-18 11:18:17 PM	{"MoF.tab":217553, "occurrence.tab":217553, "taxon.tab":164469, "time_elapsed":false}
wikipedia_en_traits_tmp3	Fri 2022-11-18 11:21:08 PM	{"MoF.tab":205191, "occurrence.tab":205191, "taxon.tab":161956, "time_elapsed":{"sec":170.3, "min":2.84, "hr":0.05}}

These 3 should increment steadily: 80, 617_ENV, wikipedia_en_traits_tmp4
80	Tue 2022-12-06 06:30:04 PM	{"media_resource.tab":847228, "taxon.tab":442775, "time_elapsed":{"sec":977.44, "min":16.29, "hr":0.27}}
80	Sat 2023-02-25 04:30:38 PM	{"media_resource.tab":847222, "taxon.tab":442796, "time_elapsed":{"sec":1001.36, "min":16.69, "hr":0.28}}
80	Mon 2023-03-20 09:38:23 AM	{"media_resource.tab":859348, "taxon.tab":448895, "time_elapsed":{"sec":955.29, "min":15.92, "hr":0.27}}
80	Sun 2023-06-11 05:26:46 AM	{"media_resource.tab":866565, "taxon.tab":452624, "time_elapsed":{"sec":982.34, "min":16.37, "hr":0.27}}

617_ENV	Wed 2022-10-26 04:22:35 PM	{"measurement_or_fact_specific.tab":234990, "occurrence_specific.tab":234990, "taxon.tab":442781, "time_elapsed":{"sec":31811.18, "min":530.19, "hr":8.84}}
617_ENV	Fri 2022-11-18 10:58:36 PM	{"measurement_or_fact_specific.tab":218225, "occurrence_specific.tab":218225, "taxon.tab":442781, "time_elapsed":{"sec":2900.23, "min":48.34, "hr":0.81}}
617_ENV	Sat 2023-03-25 04:38:50 AM	{"measurement_or_fact_specific.tab":224703, "occurrence_specific.tab":224703, "taxon.tab":448895, "time_elapsed":{"sec":7369.04, "min":122.82, "hr":2.05}}
617_ENV	Thu 2023-06-15 12:14:33 PM	{"measurement_or_fact_specific.tab":228605, "occurrence_specific.tab":228605, "taxon.tab":452624, "time_elapsed":{"sec":254982.44, "min":4249.71, "hr":70.83, "day":2.95}}

wikipedia_en_traits_tmp4	Fri 2022-11-18 11:24:48 PM	{"MoF.tab":205191, "occurrence.tab":205191, "taxon.tab":162220, "time_elapsed":{"sec":217.87, "min":3.63, "hr":0.06}}
wikipedia_en_traits_tmp4	Fri 2022-11-18 11:27:53 PM	{"MoF.tab":205191, "occurrence.tab":205191, "taxon.tab":162221, "time_elapsed":{"sec":402.94, "min":6.72, "hr":0.11}}
wikipedia_en_traits_tmp4	Mon 2023-03-13 04:05:46 AM	{"MoF.tab":205191, "occurrence.tab":205191, "taxon.tab":162222, "time_elapsed":{"sec":458.19, "min":7.64, "hr":0.13}}
wikipedia_en_traits_tmp4	Fri 2023-03-24 10:37:23 AM	{"MoF.tab":205191, "occurrence.tab":205191, "taxon.tab":162222, "time_elapsed":{"sec":417.4, "min":6.96, "hr":0.12}}
wikipedia_en_traits_tmp4	Sat 2023-03-25 10:53:43 AM	{"MoF.tab":211080, "occurrence.tab":211080, "taxon.tab":165864, "time_elapsed":{"sec":231.71, "min":3.86, "hr":0.06}}
wikipedia_en_traits_tmp4	Thu 2023-06-15 12:45:10 PM	{"MoF.tab":214707, "occurrence.tab":214707, "taxon.tab":168011, "time_elapsed":{"sec":239.12, "min":3.99, "hr":0.07}}
below removed "ice", thus decrease in number.
wikipedia_en_traits_tmp4	Sat 2023-07-08 04:29:05 PM	{"MoF.tab":213688, "occurrence.tab":213688, "taxon.tab":167897, "time_elapsed":{"sec":233.63, "min":3.89, "hr":0.06}}

---------------Jenkins entry in eol-archive
cd /html/eol_php_code/update_resources/connectors

php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118935", "subjects":"Description|Uses"}'
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 2nd", "resource_id":"120081", "subjects":"Description|Uses"}'
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 4th", "resource_id":"120082", "subjects":"Description|Uses"}'
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 5th", "resource_id":"118986", "subjects":"Description|Uses"}'
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 6th", "resource_id":"118920", "subjects":"Description|Uses"}'
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 7th", "resource_id":"120083", "subjects":"Description|Uses"}'
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 8th", "resource_id":"118237", "subjects":"Description|Uses"}'
WILL USE TO PROCESS THE CONSOLIDATED DwCA: MoftheAES.tar.gz | Will then be used to generate: MoftheAES_ENV.tar.gz
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES",  "resource_id":"MoftheAES", "subjects":"Description|Uses"}'

OTHERS: used only during dev:
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES oth", "resource_id":"30355", "subjects":"Description|Uses"}'
27822
30354
------------------------------- 
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"15423", "subjects":"Description|Uses"}'
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"91155", "subjects":"Description|Uses"}'
       environments_2_eol.php _       '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"91155", "subjects":"Description|Uses"}'
15427 15428 91144
-------------------------------

BHL Fungi:
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"15406", "subjects":"Description|Uses"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"'$resource_ID'", "subjects":"Description|Uses"}'
BHL Plants:
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"15422", "subjects":"Description|Uses", "group":"BHL_plants"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"'$resource_ID'", "subjects":"Description|Uses", "group":"BHL_plants"}'

--------------------------------------------------------------------------------------- PDF repository 10088_5097
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"10088_5097", "subjects":"Uses|Description"}'
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"10088_6943", "subjects":"Uses|Description"}'

environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"10088_5097", "subjects":"Uses|Description"}'

environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0011", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0437", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0033", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0018", "subjects":"Uses|Description"}'
SCtZ-0018 no traits detected
http://api.pensoft.net/annotator?text=List of Nearctic Walshiidae&ontologies=envo,eol-geonames
-> no anotations from Pensoft

environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0010", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0611", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0613", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0609", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0604", "subjects":"Uses|Description"}'

environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0004", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0007", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"scz-0630", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0029", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0023", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0042", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0020", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0016", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0025", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0022", "subjects":"Uses|Description"}'
May 4, 2021
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0019", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0002", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0017", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0003", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0616", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0617", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0615", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0614", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0612", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0605", "subjects":"Uses|Description"}'
May 5, 2021
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0607", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0608", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0606", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0602", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0603", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0598", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0594", "subjects":"Uses|Description"}'
May 6, 2021 Thu
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0002", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0001", "subjects":"Uses|Description"}'
May 10, 2021 Mon
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0003", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0004", "subjects":"Uses|Description"}'
May 13, 2021 Thu
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0007", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0009", "subjects":"Uses|Description"}'
May 18 Tue
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0013", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0027", "subjects":"Uses|Description"}'
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0094", "subjects":"Uses|Description"}'
May 19 Wed
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Botany", "resource_id":"scb-0093", "subjects":"Uses|Description"}'

---------------------------------------------------------------------------------------as of Sep 6, 2021
#STEP 1
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Raw English Wikipedia - initial step", "resource_id":"617", "subjects":"Description"}'
#generates 617_ENV.tar.gz
#the resource that is "Raw English Wikipedia - initial step" pertains to 80.tar.gz which is
    this DwCA: https://opendata.eol.org/dataset/wikip/resource/8fcddff7-f866-447b-8624-31e14757a9df
#the DwCA will be looked up via API using the resource name "Raw English Wikipedia - initial step".

#STEP 2: just a utility
#these 3 is just for stats = generates 3 reports
#php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1357", "habitat_filter": "saline water"}'
#php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1390", "habitat_filter": "saline water"}'
#php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q10908", "habitat_filter": "saline water"}'

#main operation is:
php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1390, Q1357, Q10908", "habitat_filter": "saline water"}'
#generates wikipedia_en_traits_FTG.tar.gz

#STEP 3: final step
# Wikipedia EN creates a new DwCA for its traits. Not like 'AmphibiaWeb text'.
# Thus there is an extra step for Wikipedia EN: it removes taxa without MoF
php5.6 remove_taxa_without_MoF.php jenkins '{"resource_id": "wikipedia_en_traits_FTG"}'
# OLD: generates wikipedia_en_traits.tar.gz
# NEW: generates wikipedia_en_traits_tmp1.tar.gz

#STEP 4: new step
# remove contradicting traits in MoF
php5.6 remove_contradicting_traits_from_MoF.php jenkins '{"resource_id": "wikipedia_en_traits_tmp1"}'
# OLD: generates the final: wikipedia_en_traits.tar.gz
# NEW: generates wikipedia_en_traits_tmp2.tar.gz

#STEP 5: remove all records for taxon with habitat value(s) that are 
#        descendants of both marine and terrestrial
php5.6 rem_marine_terr_desc.php jenkins '{"resource_id":"wikipedia_en_traits_tmp2"}'
#generates: wikipedia_en_traits_tmp3.tar.gz

#LAST STEP: copy wikipedia_en_traits_tmp3.tar.gz to wikipedia_en_traits.tar.gz OK
cd /html/eol_php_code/applications/content_server/resources
cp wikipedia_en_traits_tmp3.tar.gz wikipedia_en_traits.tar.gz 
====================================================================================================== AmphibiaWeb
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
with the new "terms_to_remove" list (Unlike AntWeb, AmphibiaWeb (21) is not affected.)
21	Thu 2020-12-17 03:47:05 AM	    {"agent.tab":834,             "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":12.57, "min":0.21, "hr":0}}
21_ENV	Thu 2020-12-17 03:48:10 AM	{"agent.tab":834, "MoF":2097, "media_resource.tab":8454, "occurrence.tab":2097, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":64.89, "min":1.08, "hr":0.02}}
removed 'sea' - expected decrease in MoF
21	Mon 2021-03-08 01:21:31 AM	    {"agent.tab":834,                 "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":14.44, "min":0.24, "hr":0}}
21_ENV	Mon 2021-03-08 01:22:27 AM	{"agent.tab":834, "MoF.tab":2094, "media_resource.tab":8454, "occurrence.tab":2094, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":55.83, "min":0.93, "hr":0.02}}
separated agents - names by ";" "and" "," etc.
21	Wed 2021-05-26 12:24:44 AM	    {"agent.tab":782,                 "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":11.78, "min":0.2, "hr":0}}
21_ENV	Wed 2021-05-26 12:31:38 AM	{"agent.tab":782, "MoF.tab":2094, "media_resource.tab":8454, "occurrence.tab":2094, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":33.85, "min":0.56, "hr":0.01}}
more adjustments:
21	Wed 2021-05-26 05:54:13 AM	    {"agent.tab":777,                 "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":11.43, "min":0.19, "hr":0}}
21_ENV	Wed 2021-05-26 05:54:40 AM	{"agent.tab":777, "MoF.tab":5115, "media_resource.tab":8454, "occurrence.tab":2094, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":27.2, "min":0.45, "hr":0.01}}
stable run:
21	Thu 2021-05-27 01:20:43 AM	    {"agent.tab":777,                 "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":12.92, "min":0.22, "hr":0}}
21_ENV	Thu 2021-05-27 01:21:12 AM	{"agent.tab":777, "MoF.tab":5115, "media_resource.tab":8454, "occurrence.tab":2094, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":29.05, "min":0.48, "hr":0.01}}
contributor back as column in MoF
21	Mon 2021-05-31 08:50:52 PM	    {"agent.tab":777,                 "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":13.01, "min":0.22, "hr":0}}
21_ENV	Mon 2021-05-31 08:51:43 PM	{"agent.tab":777, "MoF.tab":2094, "media_resource.tab":8454, "occurrence.tab":2094, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":50.28, "min":0.84, "hr":0.01}}
first contributor is a column, the rest go as child MoF.
21	Thu 2021-06-03 09:26:37 AM	    {"agent.tab":777,                 "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":14.37, "min":0.24, "hr":0}}
21_ENV	Thu 2021-06-03 09:27:57 AM	{"agent.tab":777, "MoF.tab":3021, "media_resource.tab":8454, "occurrence.tab":2094, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":80.21, "min":1.34, "hr":0.02}}
21_ENV	Mon 2021-06-07 05:47:13 AM	{"agent.tab":777, "MoF.tab":3021, "media_resource.tab":8454, "occurrence.tab":2094, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":111.27, "min":1.85, "hr":0.03}}
21	Wed 2021-06-09 02:15:43 AM	    {"agent.tab":777,                 "media_resource.tab":8454,                        "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":12.37, "min":0.21, "hr":0}}
21_ENV	Wed 2021-06-09 02:17:14 AM	{"agent.tab":777, "MoF.tab":3021, "media_resource.tab":8454, "occurrence.tab":2094, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":90.63, "min":1.51, "hr":0.03}}
after DATA-1893:
21_ENV	Wed 2021-10-13 10:30:40 AM	{"agent.tab":777, "MoF.tab":3015, "media_resource.tab":8454, "occurrence.tab":2091, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2321, "time_elapsed":{"sec":117.84, "min":1.96, "hr":0.03}}
21_ENV	Wed 2022-10-26 07:45:29 AM	{"agent.tab":777, "MoF.tab":4266, "media_resource.tab":8454, "occurrence.tab":2941, "reference.tab":5799, "taxon.tab":2346, "vernacular_name.tab":2320, "time_elapsed":{"sec":241.92, "min":4.03, "hr":0.07}}
========================================================================================================= WoRMS
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
26_ENV	Mon 2021-05-10 01:00:05 AM	{"agent.tab":1749, "measurement_or_fact_specific.tab":2415845, "media_resource.tab":92097, "occurrence_specific.tab":2041703, "reference.tab":680112, "taxon.tab":370148, "vernacular_name.tab":85180, "time_elapsed":{"sec":3124.88, "min":52.08, "hr":0.87}}
26_ENV	Tue 2021-05-11 10:53:48 AM	{"agent.tab":1771, "measurement_or_fact_specific.tab":2458884, "media_resource.tab":92507, "occurrence_specific.tab":2091386, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":{"sec":3309.03, "min":55.15, "hr":0.92}}
26_ENV	Tue 2021-05-11 03:57:46 PM	{"agent.tab":1771, "measurement_or_fact_specific.tab":2458884, "media_resource.tab":92507, "occurrence_specific.tab":2091386, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":{"sec":3278.52, "min":54.64, "hr":0.91}}
-> steady increase
start removed all occurrences and trait records associated with specified taxa (WoRMS_mismapped_subgenera):
26_ENV	Wed 2021-05-12 03:07:37 PM	{"agent.tab":1771, "measurement_or_fact_specific.tab":2458703, "media_resource.tab":92507, "occurrence_specific.tab":2091215, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":{"sec":3633.44, "min":60.56, "hr":1.01}}
26_ENV	Fri 2021-05-14 06:56:47 AM	{"agent.tab":1771, "measurement_or_fact_specific.tab":2458703, "media_resource.tab":92507, "occurrence_specific.tab":2091215, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":{"sec":3263.46, "min":54.39, "hr":0.91}}
26_ENV	Wed 2021-06-09 06:30:41 AM	{"agent.tab":1776, "measurement_or_fact_specific.tab":2458816, "media_resource.tab":92746, "occurrence_specific.tab":2091228, "reference.tab":692231, "taxon.tab":374006, "vernacular_name.tab":85169, "time_elapsed":{"sec":3257.8, "min":54.3, "hr":0.9}}
26_ENV	Wed 2021-06-09 02:39:24 PM	{"agent.tab":1776, "measurement_or_fact_specific.tab":2458816, "media_resource.tab":92746, "occurrence_specific.tab":2091228, "reference.tab":692231, "taxon.tab":374006, "vernacular_name.tab":85169, "time_elapsed":{"sec":3397.34, "min":56.62, "hr":0.94}}
26_ENV	Fri 2021-09-10 05:04:40 AM	{"agent.tab":1804, "measurement_or_fact_specific.tab":2500985, "media_resource.tab":93238, "occurrence_specific.tab":2129864, "reference.tab":700222, "taxon.tab":376056, "vernacular_name.tab":85681, "time_elapsed":{"sec":3676.25, "min":61.27, "hr":1.02}}
26_ENV	        Wed 2021-09-22 12:11:41 PM	{"agent.tab":1804, "measurement_or_fact_specific.tab":2500985, "media_resource.tab":93238, "occurrence_specific.tab":2129864, "reference.tab":700222, "taxon.tab":376056, "vernacular_name.tab":85681, "time_elapsed":{"sec":3387.77, "min":56.46, "hr":0.94}}
after DATA-1893:
26_ENV	        Wed 2021-10-13 07:20:25 AM	{"agent.tab":1813, "measurement_or_fact_specific.tab":2526290, "media_resource.tab":94660, "occurrence_specific.tab":2151130, "reference.tab":703450, "taxon.tab":377647, "vernacular_name.tab":85681, "time_elapsed":{"sec":3905.4, "min":65.09, "hr":1.08}}
26_ENV_final	Wed 2021-10-13 08:14:18 AM	{"agent.tab":1813, "measurement_or_fact_specific.tab":2526290, "media_resource.tab":94660, "occurrence.tab":2151130,          "reference.tab":703450, "taxon.tab":377647, "vernacular_name.tab":85681, "time_elapsed":{"sec":3160.64, "min":52.68, "hr":0.88}}

26_ENV	        Wed 2021-10-20 05:43:23 AM	{"agent.tab":1813, "measurement_or_fact_specific.tab":2526290, "media_resource.tab":94660, "occurrence_specific.tab":2151130, "reference.tab":703450, "taxon.tab":377647, "vernacular_name.tab":85681, "time_elapsed":{"sec":5240.29, "min":87.34, "hr":1.46}}
26_ENV_final	Wed 2021-10-20 06:44:02 AM	{"agent.tab":1813, "measurement_or_fact_specific.tab":2526290, "media_resource.tab":94660, "occurrence.tab":2151130,          "reference.tab":703450, "taxon.tab":377647, "vernacular_name.tab":85681, "time_elapsed":{"sec":3240.83, "min":54.01, "hr":0.9}}
=========================================================================================================== WoRMS end

20_ENV	Wed 2022-01-05 08:09:41 AM	{"agent.tab":2031, "MoF.tab":9771, "media.tab":28979, "occur.tab":9771, "reference.tab":1420, "taxon.tab":8830, "time_elapsed":{"sec":26703.6, "min":445.06, "hr":7.42}}
20_ENV	Wed 2022-01-12 03:28:32 AM	{"agent.tab":2031, "MoF.tab":9771, "media.tab":28979, "occur.tab":9771, "reference.tab":1420, "taxon.tab":8830, "time_elapsed":{"sec":52.31, "min":0.87, "hr":0.01}}

829	Sat 2022-01-01 11:07:12 PM	    {"agent.tab":661,                "media.tab":8315,                  "reference.tab":288, "taxon.tab":2700, "time_elapsed":false}
829_ENV	Wed 2022-01-05 11:58:28 PM	{"agent.tab":661, "MoF.tab":192, "media.tab":8315, "occur.tab":192, "reference.tab":288, "taxon.tab":2700, "time_elapsed":{"sec":3919.08, "min":65.32, "hr":1.09}}
829	Sat 2022-01-08 11:07:09 PM	    {"agent.tab":646,                "media.tab":7842,                  "reference.tab":277, "taxon.tab":2575, "time_elapsed":false}
829_ENV	Wed 2022-01-12 06:56:06 AM	{"agent.tab":646, "MoF.tab":184, "media.tab":7842, "occur.tab":184, "reference.tab":277, "taxon.tab":2575, "time_elapsed":{"sec":486.9, "min":8.12, "hr":0.14}}
829_ENV	Wed 2022-01-12 07:14:50 AM	{"agent.tab":646, "MoF.tab":184, "media.tab":7842, "occur.tab":184, "reference.tab":277, "taxon.tab":2575, "time_elapsed":{"sec":16.45, "min":0.27, "hr":0}}

832	Sat 2022-01-01 11:26:03 PM	    {"agent.tab":14,               "media.tab":64,                 "reference.tab":4, "taxon.tab":11, "time_elapsed":false}
832_ENV	Thu 2022-01-06 12:34:13 AM	{"agent.tab":14, "MoF.tab":14, "media.tab":64, "occur.tab":14, "reference.tab":4, "taxon.tab":11, "time_elapsed":{"sec":144.2, "min":2.4, "hr":0.04}}
832	Sat 2022-01-08 11:26:02 PM	    {"agent.tab":6,                "media.tab":14,                 "reference.tab":2, "taxon.tab":3, "time_elapsed":false}
832_ENV	Wed 2022-01-12 06:48:19 AM	{"agent.tab":6,                "media.tab":14,                 "reference.tab":2, "taxon.tab":3, "time_elapsed":{"sec":4.56, "min":0.08, "hr":0}}
832_ENV	Wed 2022-01-12 07:02:30 AM	{"agent.tab":6,                "media.tab":14,                 "reference.tab":2, "taxon.tab":3, "time_elapsed":{"sec":10.07, "min":0.17, "hr":0}}
832_ENV	Wed 2022-01-12 08:03:27 AM	{"agent.tab":6, "media_resource.tab":14, "reference.tab":2, "taxon.tab":3, "time_elapsed":{"sec":3.83, "min":0.06, "hr":0}}

830_ENV	Thu 2022-01-06 12:35:25 AM	{"agent.tab":43, "MoF.tab":1, "media.tab":62, "occur.tab":1, "reference.tab":13, "taxon.tab":39, "time_elapsed":{"sec":36.69, "min":0.61, "hr":0.01}}
798_ENV	Thu 2022-01-06 02:46:34 AM	{"agent.tab":17, "MoF.tab":5, "media.tab":66, "occur.tab":5, "reference.tab":11, "taxon.tab":22, "time_elapsed":{"sec":41.68, "min":0.69, "hr":0.01}}
834_ENV	Thu 2022-01-06 04:21:47 AM	{"agent.tab":20, "MoF.tab":25, "media.tab":231, "occur.tab":25, "reference.tab":11, "taxon.tab":90, "time_elapsed":{"sec":107.84, "min":1.8, "hr":0.03}}
792_ENV	Thu 2022-01-06 02:42:31 AM	{"agent.tab":27, "MoF.tab":4, "media.tab":280, "occur.tab":4, "reference.tab":17, "taxon.tab":108, "time_elapsed":{"sec":140.98, "min":2.35, "hr":0.04}}
826_ENV	Thu 2022-01-06 03:04:19 AM	{"agent.tab":165, "MoF.tab":64, "media.tab":585, "occur.tab":64, "reference.tab":49, "taxon.tab":199, "time_elapsed":{"sec":561.56, "min":9.36, "hr":0.16}}

830_ENV	Wed 2022-01-12 06:36:05 AM	{"agent.tab":43, "MoF.tab":1, "media.tab":62, "occur.tab":1, "reference.tab":13, "taxon.tab":39, "time_elapsed":{"sec":7.04, "min":0.12, "hr":0}}
798_ENV	Wed 2022-01-12 06:36:20 AM	{"agent.tab":17, "MoF.tab":5, "media.tab":66, "occur.tab":5, "reference.tab":11, "taxon.tab":22, "time_elapsed":{"sec":4.48, "min":0.07, "hr":0}}
834_ENV	Wed 2022-01-12 06:37:25 AM	{"agent.tab":22, "MoF.tab":24, "media.tab":230, "occur.tab":24, "reference.tab":11, "taxon.tab":90, "time_elapsed":{"sec":11.03, "min":0.18, "hr":0}}
792_ENV	Wed 2022-01-12 06:37:34 AM	{"agent.tab":27, "MoF.tab":4, "media.tab":280, "occur.tab":4, "reference.tab":17, "taxon.tab":108, "time_elapsed":{"sec":4.78, "min":0.08, "hr":0}}
826_ENV	Wed 2022-01-12 06:39:25 AM	{"agent.tab":144, "MoF.tab":62, "media.tab":609, "occur.tab":62, "reference.tab":48, "taxon.tab":208, "time_elapsed":{"sec":97.03, "min":1.62, "hr":0.03}}

830_ENV	Wed 2022-01-12 08:03:26 AM	{"agent.tab":43, "MoF.tab":11, "media.tab":62, "occur.tab":11, "reference.tab":13, "taxon.tab":39, "time_elapsed":{"sec":42.85, "min":0.71, "hr":0.01}}
830_ENV	Wed 2022-01-26 07:41:30 AM	{"agent.tab":43, "MoF.tab":10, "media.tab":62, "occur.tab":10, "reference.tab":13, "taxon.tab":39, "time_elapsed":{"sec":11.82, "min":0.2, "hr":0}} Mac Mini

798_ENV	Wed 2022-01-12 08:03:31 AM	{"agent.tab":17, "MoF.tab":18, "media.tab":66, "occur.tab":18, "reference.tab":11, "taxon.tab":22, "time_elapsed":{"sec":41.55, "min":0.69, "hr":0.01}}
834_ENV	Wed 2022-01-12 08:05:54 AM	{"agent.tab":22, "MoF.tab":158, "media.tab":230, "occur.tab":158, "reference.tab":11, "taxon.tab":90, "time_elapsed":{"sec":127.39, "min":2.12, "hr":0.04}}
792_ENV	Wed 2022-01-12 08:05:31 AM	{"agent.tab":27, "MoF.tab":123, "media.tab":280, "occur.tab":123, "reference.tab":17, "taxon.tab":108, "time_elapsed":{"sec":175.75, "min":2.93, "hr":0.05}}

826_ENV	Wed 2022-01-12 08:15:26 AM	{"agent.tab":144, "MoF.tab":247, "media.tab":609, "occur.tab":247, "reference.tab":48, "taxon.tab":208, "time_elapsed":{"sec":745.47, "min":12.42, "hr":0.21}}
removed 'ocean' in MoF
826_ENV	Wed 2022-01-19 05:37:18 AM	{"agent.tab":144, "MoF.tab":246, "media.tab":609, "occur.tab":246, "reference.tab":48, "taxon.tab":208, "time_elapsed":{"sec":9.35, "min":0.16, "hr":0}}
===================================================================================================================== Pensoft journals START
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"TreatmentBank", "subjects":"Uses"}'

ZooKeys (20)**
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"20", "subjects":"GeneralDescription|Distribution|Description"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 5898
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 4931
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description: 2
Above generates: --> 20_ENV.tar.gz
Then run: zookeys_add_trait.php --> makes use of [20_ENV.tar.gz] and generates [20_ENV_final.tar.gz]

Zookeys (829)**
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"829", "subjects":"GeneralDescription|Distribution"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 1640
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 1066

Subterranean Biology (832)* | No MoF
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"832", "subjects":"GeneralDescription|Distribution"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 10
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 4

Mycokeys (830)**
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"830", "subjects":"GeneralDescription|Distribution"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 16
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 8

Nota Lepidopterologica (798)**
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"798", "subjects":"GeneralDescription|Distribution"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 10
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 11

Zoosystematics and Evolution (834)**
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"834", "subjects":"GeneralDescription|Distribution"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 21
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 44

Deutsche Entomologische Zeitschrift (792)*
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"792", "subjects":"GeneralDescription|Distribution"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 36
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 47

Phytokeys (826) **
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"826", "subjects":"GeneralDescription|Distribution"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 134
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 103

Comparative Cytogenetics (554) - No MoF
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"EOL_554_final", "subjects":"GeneralDescription"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 1

Journal of Hymenoptera Research (831) - No MoF
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"831", "subjects":"GeneralDescription|Distribution"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 51
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 44
===================================================================================================================== Pensoft journals END
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
    require_library('connectors/Functions_Pensoft');
    require_library('connectors/Pensoft2EOLAPI');
    $func = new Pensoft2EOLAPI($param);
    $download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*10); //expires in 10 days. Mostly connector refreshes once a month.
    $download_options = array('timeout' => 172800, 'expire_seconds' => 0); //this is the obvious correct expiration
    // /* customize
    if($param['resource_id'] == '21_ENV') $download_options = array('timeout' => 172800, 'expire_seconds' => 0);
    // */
    $func->generate_eol_tags_pensoft($resource, $timestart, $download_options);
}
?>