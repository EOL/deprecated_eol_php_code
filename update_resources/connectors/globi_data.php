<?php
namespace php_active_record;
/* 
http://content.eol.org/resources/20 Global Biotic Interactions
DATA-1812
globi_associations	Monday 2019-07-01 09:53:16 AM	{"association.tab":3097482,"occurrence_specific.tab":2288584,"reference.tab":327413,"taxon.tab":215828}
globi_associations	Thursday 2019-07-04 06:20:42 AM	{"association.tab":3097726,"occurrence_specific.tab":2288805,"reference.tab":327528,"taxon.tab":215846}
globi_associations	Tuesday 2019-09-24 02:36:25 PM	{"association.tab":3251759,"occurrence_specific.tab":2438087,"reference.tab":467632,"taxon.tab":217885} MacMini
globi_associations	Wednesday 2019-09-25 01:40:19 AM{"association.tab":3251759,"occurrence_specific.tab":2438087,"reference.tab":467632,"taxon.tab":217885} eol-archive
globi_associations	Sunday 2019-12-01 08:41:08 PM	{"association.tab":3484127,"occurrence_specific.tab":2642172,"reference.tab":457021,"taxon.tab":234408} eol-archive Consistent OK

decrease in associations is per: https://eol-jira.bibalex.org/browse/DATA-1812?focusedCommentId=64218&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64218
globi_associations	Sunday 2019-12-15 11:30:58 PM	{"association.tab":2603503,"occurrence_specific.tab":2091804,"reference.tab":457021,"taxon.tab":234408}
globi_associations	Monday 2019-12-16 01:56:00 PM	{"association.tab":2603503, "occurrence_specific.tab":2091804, "reference.tab":457021, "taxon.tab":199329, "time_elapsed":{"sec":1613.86,"min":26.9,"hr":0.45}}
globi_associations	Monday 2020-04-13 11:43:20 PM	{"association.tab":2661929, "occurrence_specific.tab":2147567, "reference.tab":615045, "taxon.tab":208774, "time_elapsed":{"sec":5826.61, "min":97.11, "hr":1.62}}
globi_associations	Tuesday 2020-04-14 03:39:15 AM	{"association.tab":2661929, "occurrence_specific.tab":2147567, "reference.tab":615045, "taxon.tab":208774, "time_elapsed":{"sec":5201.33, "min":86.69, "hr":1.44}}
globi_associations	Tuesday 2020-04-14 05:41:05 AM	{"association.tab":2661929, "occurrence_specific.tab":2147567, "reference.tab":615045, "taxon.tab":208774, "time_elapsed":{"sec":5284.99, "min":88.08, "hr":1.47}}
globi_associations	Tuesday 2020-04-14 09:44:41 AM	{"association.tab":2661977, "occurrence_specific.tab":2147567, "reference.tab":615045, "taxon.tab":208774, "time_elapsed":{"sec":3869.93, "min":64.5, "hr":1.07}}
globi_associations	Tuesday 2020-04-14 11:44:01 AM	{"association.tab":2661971, "occurrence_specific.tab":2147567, "reference.tab":615045, "taxon.tab":208774, "time_elapsed":{"sec":4666.34, "min":77.77, "hr":1.3}}
globi_associations	Tuesday 2020-04-14 09:31:59 PM	{"association.tab":2661971, "occurrence_specific.tab":2147567, "reference.tab":615045, "taxon.tab":208774, "time_elapsed":{"sec":2832.23, "min":47.2, "hr":0.79}} final OK

under development stage: DATA-1853
globi_associations	Monday 2020-05-25 10:28:41 AM	{"association.tab":2666676, "occurrence_specific.tab":2179040, "reference.tab":680781, "taxon.tab":215417, "time_elapsed":{"sec":4647.54, "min":77.46, "hr":1.29}}
globi_associations	Monday 2020-05-25 01:42:13 PM	{"association.tab":2666184, "occurrence_specific.tab":2179040, "reference.tab":680781, "taxon.tab":215417, "time_elapsed":{"sec":3179.47, "min":52.99, "hr":0.88}}
globi_associations	Tuesday 2020-05-26 01:24:31 AM	{"association.tab":2666186, "occurrence_specific.tab":2179040, "reference.tab":680781, "taxon.tab":215417, "time_elapsed":{"sec":3188.3, "min":53.14, "hr":0.89}}
globi_associations	Tuesday 2020-05-26 04:34:58 AM	{"association.tab":2666190, "occurrence_specific.tab":2179040, "reference.tab":680781, "taxon.tab":215417, "time_elapsed":{"sec":3168.94, "min":52.82, "hr":0.88}}
globi_associations	Wednesday 2020-05-27 06:37:12 AM{"association.tab":2666190, "occurrence_specific.tab":2179040, "reference.tab":680781, "taxon.tab":215417, "time_elapsed":{"sec":3283.43, "min":54.72, "hr":0.91}}
globi_associations	Wednesday 2020-05-27 08:43:06 PM{"association.tab":2666286, "occurrence_specific.tab":2179040, "reference.tab":680781, "taxon.tab":215417, "time_elapsed":{"sec":3553.95, "min":59.23, "hr":0.99}}
globi_associations	Thursday 2020-05-28 04:27:57 AM	{"association.tab":2666286, "occurrence_specific.tab":2179040, "reference.tab":680781, "taxon.tab":215417, "time_elapsed":{"sec":3471.95, "min":57.87, "hr":0.96}}

globi_associations	Wednesday 2020-08-12 07:05:01 AM{"association.tab":3271823, "occurrence_specific.tab":2780990, "reference.tab":1006155, "taxon.tab":224786, "time_elapsed":{"sec":6000.29, "min":100, "hr":1.67}}
globi_associations	Wednesday 2020-08-12 10:08:25 AM{"association.tab":3271823, "occurrence_specific.tab":2780990, "reference.tab":1006155, "taxon.tab":224786, "time_elapsed":{"sec":4306.75, "min":71.78, "hr":1.2}}

below here 'Jen's preferred term' is applied:

globi_associations	Wed 2020-08-12 12:56:39 PM  "association.tab":1866725, "occurrence_specific.tab":2780990, "reference.tab":1006155, "taxon.tab":224786, "time_elapsed":{"sec":3999.38, "min":66.66, "hr":1.11}}
globi_associations	Thu 2020-08-13 12:36:13 AM	{"association.tab":1866725, "occurrence_specific.tab":2763493, "reference.tab":1006155, "taxon.tab":224175, "time_elapsed":{"sec":4038.18, "min":67.3, "hr":1.12}}
DATA-1862
globi_associations	Wed 2020-09-02 11:34:39 AM	{"association.tab":1908403, "occurrence_specific.tab":2817930, "reference.tab":1047221, "taxon.tab":227253, "time_elapsed":{"sec":4410.59, "min":73.51, "hr":1.23}}
globi_associations	Thu 2020-09-03 10:36:57 AM	{"association.tab":1908403, "occurrence_specific.tab":2817930, "reference.tab":1047221, "taxon.tab":227253, "time_elapsed":{"sec":3999.01, "min":66.65, "hr":1.11}}
globi_associations	Mon 2020-12-07 03:29:50 AM	{"association.tab":2444196, "occurrence_specific.tab":3471794, "reference.tab":1139966, "taxon.tab":295119, "time_elapsed":{"sec":5412.4, "min":90.21, "hr":1.5}}
globi_associations	Mon 2020-12-07 10:03:14 AM	{"association.tab":2442251, "occurrence_specific.tab":3467973, "reference.tab":1139966, "taxon.tab":294923, "time_elapsed":{"sec":5026.15, "min":83.77, "hr":1.4}}
globi_associations	Mon 2020-12-21 03:31:08 AM	{"association.tab":2436952, "occurrence_specific.tab":3458133, "reference.tab":1139966, "taxon.tab":294496, "time_elapsed":{"sec":5099.66, "min":84.99, "hr":1.42}}
globi_associations	Mon 2020-12-21 10:17:04 PM	{"association.tab":2435698, "occurrence_specific.tab":3455958, "reference.tab":1139966, "taxon.tab":294442, "time_elapsed":{"sec":4937.43, "min":82.29, "hr":1.37}}
globi_associations	Wed 2021-02-24 04:49:21 PM	{"association.tab":2510170, "occurrence_specific.tab":3592395, "reference.tab":1402132, "taxon.tab":303931, "time_elapsed":{"sec":10879.17, "min":181.32, "hr":3.02}}
globi_associations	Tue 2021-03-30 03:51:24 AM	{"association.tab":2240907, "occurrence_specific.tab":3351484, "reference.tab":1414468, "taxon.tab":304554, "time_elapsed":{"sec":5442.52, "min":90.71, "hr":1.51}}
globi_associations	Mon 2021-05-10 01:40:30 PM	{"association.tab":2337436, "occurrence_specific.tab":3581811, "reference.tab":1565465, "taxon.tab":318274, "time_elapsed":{"sec":6551.32, "min":109.19, "hr":1.82}}
remove all occurrences and association records for taxa with specified ranks:
globi_associations	Tue 2021-05-11 02:29:20 AM	{"association.tab":2121276, "occurrence_specific.tab":3414583, "reference.tab":1565465, "taxon.tab":316748, "time_elapsed":{"sec":5216.04, "min":86.93, "hr":1.45}}
-> hmmm taxa with specified ranks were mistakenly removed as well. They should remain.
taxa now revived from prev mistake:
globi_associations	Tue 2021-05-11 07:02:50 AM	{"association.tab":2157305, "occurrence_specific.tab":3414583, "reference.tab":1565465, "taxon.tab":318274, "time_elapsed":{"sec":5394.6, "min":89.91, "hr":1.5}}
globi_associations_final	Mon 2021-06-14 08:04{"association.tab":2316688, "occurrence_specific.tab":3863894, "reference.tab":1746832, "taxon.tab":320638, "time_elapsed":{"sec":4964.02, "min":82.73, "hr":1.38}}
stable run:
globi_associations	Mon 2021-06-14 10:12:55 AM	{"association.tab":2372284, "occurrence_specific.tab":3863894, "reference.tab":1746832, "taxon.tab":320638, "time_elapsed":{"sec":5904.94, "min":98.42, "hr":1.64}}
stable run: without the 'null' enties. Association integrity OK:
globi_associations_final	Mon 2021-06-14 08:04:13 AM	{"association.tab":2316688, "occurrence_specific.tab":3863894, "reference.tab":1746832, "taxon.tab":320638, "time_elapsed":{"sec":4964.02, "min":82.73, "hr":1.38}}
globi_associations_final	Mon 2021-06-14 11:34:55 AM	{"association.tab":2316688, "occurrence_specific.tab":3863894, "reference.tab":1746832, "taxon.tab":320638, "time_elapsed":{"sec":10824.99, "min":180.42, "hr":3.01}}
globi_associations_final	Tue 2021-10-05 04:59:53 AM	{"association.tab":2760813, "occurrence_specific.tab":4731979, "reference.tab":2475313, "taxon.tab":324636, "time_elapsed":{"sec":15704.97, "min":261.75, "hr":4.36}}

globi_associations	        Mon 2021-10-25 11:45:11 AM	{"association.tab":2818779, "occurrence_specific.tab":4732095, "reference.tab":2475313, "taxon.tab":324636, "time_elapsed":{"sec":8331.12, "min":138.85, "hr":2.31}}
globi_associations_final	Mon 2021-10-25 01:34:04 PM	{"association.tab":2760813, "occurrence_specific.tab":4731979, "reference.tab":2475313, "taxon.tab":324636, "time_elapsed":{"sec":14863.43, "min":247.72, "hr":4.13}}

Last update for ver. 1.0:
globi_associations	        Mon 2022-01-10 09:31:54 PM	{"association.tab":2818779, "occurrence_specific.tab":4732095, "reference.tab":2475313, "taxon.tab":324636, "time_elapsed":{"sec":8534.33, "min":142.24, "hr":2.37}}
globi_associations_final	Mon 2022-01-10 11:14:03 PM	{"association.tab":2760813, "occurrence_specific.tab":4731979, "reference.tab":2475313, "taxon.tab":324636, "time_elapsed":{"sec":14663.86, "min":244.4, "hr":4.07}}
First update for ver. 1.1:
globi_associations	        Sat 2022-01-22 05:11:48 AM	{"association.tab":5601512, "occurrence_specific.tab":10229469, "reference.tab":5278334, "taxon.tab":346787, "time_elapsed":{"sec":13824.01, "min":230.4, "hr":3.84}}
globi_associations_final	Sat 2022-01-22 08:49:32 AM	{"association.tab":5552473, "occurrence_specific.tab":10229340, "reference.tab":5278334, "taxon.tab":346787, "time_elapsed":{"sec":26887.33, "min":448.12, "hr":7.47}}
globi_associations_delta	Mon 2022-02-28 09:46:12 AM	{"association.tab":5552473, "occurrence_specific.tab":10229340, "reference.tab":5278334, "taxon.tab":331234, "time_elapsed":{"sec":13951.76, "min":232.53, "hr":3.88}}
below with hashed referenceID, and consequently made unique referenceID (OK stable)
globi_associations_delta	Tue 2022-03-08 01:55:57 PM	{"association.tab":5552473, "occurrence_specific.tab":10229340, "reference.tab":5116596, "taxon.tab":331234, "time_elapsed":{"sec":15015.04, "min":250.25, "hr":4.17}}

below sudden big increase in data from partner:
globi_associations	        Mon 2022-06-27 04:27:46 PM	{"association.tab":8023468, "occurrence_specific.tab":15097828, "reference.tab":8102860, "taxon.tab":421428, "time_elapsed":{"sec":19699.58, "min":328.33, "hr":5.47}}
globi_associations_final	Mon 2022-06-27 10:05:38 PM	{"association.tab":7867153, "occurrence_specific.tab":15097697, "reference.tab":8102860, "taxon.tab":421428, "time_elapsed":{"sec":39971.63, "min":666.19, "hr":11.1}}
globi_associations_delta	Tue 2022-06-28 09:04:28 AM	{"association.tab":7867153, "occurrence_specific.tab":15097697, "reference.tab":7792945, "taxon.tab":337341, "time_elapsed":{"sec":21721.61, "min":362.03, "hr":6.03}}

below removed associations for NCBI:32644 (unidentified): https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=67098&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67098
globi_associations	Sun 2022-10-30 05:33:20 AM	        {"association.tab":8183356, "occurrence_specific.tab":15421727, "reference.tab":8266485, "taxon.tab":422129, "time_elapsed":{"sec":24341.1, "min":405.69, "hr":6.76}}
globi_associations_final	Sun 2022-10-30 11:33:56 AM	{"association.tab":8025536, "occurrence_specific.tab":15421586, "reference.tab":8266485, "taxon.tab":422129, "time_elapsed":{"sec":45977.57, "min":766.29, "hr":12.77}}
globi_associations_delta	Sun 2022-10-30 06:02:50 PM	{"association.tab":8025536, "occurrence_specific.tab":15421586, "reference.tab":7956621, "taxon.tab":338043, "time_elapsed":{"sec":22556.47, "min":375.94, "hr":6.27}}

below big data increase from Jorrit but also removed 19 taxa uidentified series
globi_associations	Tue 2022-11-01 04:22:27 AM	        {"association.tab":8927060, "occurrence_specific.tab":16909838, "reference.tab":9028116, "taxon.tab":427500, "time_elapsed":{"sec":24049.83, "min":400.83, "hr":6.68}}
globi_associations_final	Tue 2022-11-01 10:44:15 AM	{"association.tab":8767900, "occurrence_specific.tab":16909699, "reference.tab":9028116, "taxon.tab":427500, "time_elapsed":{"sec":46957.42, "min":782.62, "hr":13.04}}
globi_associations_delta	Tue 2022-11-01 05:48:33 PM	{"association.tab":8767900, "occurrence_specific.tab":16909699, "reference.tab":8701965, "taxon.tab":340625, "time_elapsed":{"sec":24545.2, "min":409.09, "hr":6.82}}

removed covid associations below:
globi_associations	        Mon 2023-01-23 01:02:56 AM	{"association.tab":2654750, "occurrence_specific.tab":10922455, "reference.tab":9357578, "taxon.tab":429727, "time_elapsed":{"sec":16530.09, "min":275.5, "hr":4.59}}
globi_associations_final	Mon 2023-01-23 05:04:14 AM	{"association.tab":2521697, "occurrence_specific.tab":10922302, "reference.tab":9357578, "taxon.tab":429727, "time_elapsed":{"sec":31008.27, "min":516.8, "hr":8.61}}
globi_associations_delta	Mon 2023-01-23 09:26:33 AM	{"association.tab":2521697, "occurrence_specific.tab":10922302, "reference.tab":9004684, "taxon.tab":340661, "time_elapsed":{"sec":15254.89, "min":254.25, "hr":4.24}}

globi_associations	        Mon 2023-01-23 10:52:40 PM	{"association.tab":2654749, "occurrence_specific.tab":10922454, "reference.tab":9357578, "taxon.tab":429727, "time_elapsed":{"sec":15231.97, "min":253.87, "hr":4.23}}
globi_associations_final	Tue 2023-01-24 02:52:10 AM	{"association.tab":2521696, "occurrence_specific.tab":10922301, "reference.tab":9357578, "taxon.tab":429727, "time_elapsed":{"sec":29602.08, "min":493.37, "hr":8.22}}
globi_associations_delta	Wed 2023-01-25 09:11:01 AM	{"association.tab":2521696, "occurrence_specific.tab":10922301, "reference.tab":9004684, "taxon.tab":340661, "time_elapsed":{"sec":15536.25, "min":258.94, "hr":4.32}}

globi_associations	        Mon 2023-02-06 07:36:39 AM	{"association.tab":2642728, "occurrence_specific.tab":10913611, "reference.tab":9357578, "taxon.tab":429727, "time_elapsed":{"sec":15285.85, "min":254.76, "hr":4.25}}
globi_associations_final	Mon 2023-02-06 11:43:28 AM	{"association.tab":2511024, "occurrence_specific.tab":10913458, "reference.tab":9357578, "taxon.tab":429727, "time_elapsed":{"sec":30095.84, "min":501.6, "hr":8.36}}
globi_associations_delta	Mon 2023-02-06 04:05:06 PM	{"association.tab":2511024, "occurrence_specific.tab":10913458, "reference.tab":9004684, "taxon.tab":340661, "time_elapsed":{"sec":15218.73, "min":253.65, "hr":4.23}}

below with Animalia and Metazoa associations removed:
globi_associations	        Tue 2023-02-14 03:52:11 PM	{"association.tab":3640774, "occurrence_specific.tab":13005590, "reference.tab":10407513, "taxon.tab":336401, "time_elapsed":{"sec":17580.58, "min":293.01, "hr":4.88}}
globi_associations_final	Tue 2023-02-14 08:33:53 PM	{"association.tab":3498886, "occurrence_specific.tab":13005415, "reference.tab":10407513, "taxon.tab":336401, "time_elapsed":{"sec":34482.14, "min":574.7, "hr":9.58}}
globi_associations_delta	Wed 2023-02-15 01:35:28 AM	{"association.tab":3498886, "occurrence_specific.tab":13005415, "reference.tab":10053973, "taxon.tab":336401, "time_elapsed":{"sec":17528.5, "min":292.14, "hr":4.87}}

below with Animalia and Metazoa associations added again:
globi_associations	        Wed 2023-02-15 04:10:37 PM	{"association.tab":3640790, "occurrence_specific.tab":13005607, "reference.tab":10407513, "taxon.tab":336401, "time_elapsed":{"sec":17572.34, "min":292.87, "hr":4.88}}
globi_associations_final	Wed 2023-02-15 08:52:09 PM	{"association.tab":3498902, "occurrence_specific.tab":13005432, "reference.tab":10407513, "taxon.tab":336401, "time_elapsed":{"sec":34464.21, "min":574.4, "hr":9.57}}
globi_associations_delta	Thu 2023-02-16 01:51:18 AM	{"association.tab":3498902, "occurrence_specific.tab":13005432, "reference.tab":10053973, "taxon.tab":336401, "time_elapsed":{"sec":17472.31, "min":291.21, "hr":4.85}}

globi_associations	        Thu 2023-02-16 09:37:34 AM	{"association.tab":3640774, "occurrence_specific.tab":13005590, "reference.tab":10407513, "taxon.tab":336401, "time_elapsed":{"sec":17854.32, "min":297.57, "hr":4.96}}
globi_associations_final	Thu 2023-02-16 02:24:15 PM	{"association.tab":3498886, "occurrence_specific.tab":13005415, "reference.tab":10407513, "taxon.tab":336401, "time_elapsed":{"sec":35055.89, "min":584.26, "hr":9.74}}
globi_associations_delta	Thu 2023-02-16 07:30:02 PM	{"association.tab":3498886, "occurrence_specific.tab":13005415, "reference.tab":10053973, "taxon.tab":336401, "time_elapsed":{"sec":17764.45, "min":296.07, "hr":4.93}}

All single-word scientificNames from Plazi were removed - associations removed.
globi_associations	        Fri 2023-02-17 03:31:23 PM	{"association.tab":3638127, "occurrence_specific.tab":13046741, "reference.tab":10506147, "taxon.tab":336533, "time_elapsed":{"sec":17999.04, "min":299.98, "hr":5}}
globi_associations_final	Fri 2023-02-17 08:10:50 PM	{"association.tab":3496568, "occurrence_specific.tab":13046567, "reference.tab":10506147, "taxon.tab":336533, "time_elapsed":{"sec":34766.06, "min":579.43, "hr":9.66}}
globi_associations_delta	Sat 2023-02-18 01:12:12 AM	{"association.tab":3496568, "occurrence_specific.tab":13046567, "reference.tab":10151728, "taxon.tab":336533, "time_elapsed":{"sec":17500.48, "min":291.67, "hr":4.86}}

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ one time reported to Jeremy:
I've just republished [Global Biotic Interactions] https://eol.org/resources/396
But the published version doesn't seem reflective of what we have in the DwCA.
In the DwCA this taxon (GBIF:2411317) only eats (RO_0002470) these 3 taxa: Gobiosoma bosc, Gammarus mucronatus, Rhithropanopeus harrisii.
As reflected in DwCA:
occurrence.tab
805b5b8a5129e4d85b1b2cc4fcb90cfd	GBIF:2411317																											
association.tab
    9971bf75a540c936fc080c9c58c9e7e3	805b5b8a5129e4d85b1b2cc4fcb90cfd	http://purl.obolibrary.org/obo/RO_0002470	40713ccda0816ad2d6d5ed35c8343451					http://gomexsi.tamucc.edu			b4ff2dd97e8a3c5920ba1bc6c8db779b
    246a137556bdcc3aaa1e9fee552bbc5e	805b5b8a5129e4d85b1b2cc4fcb90cfd	http://purl.obolibrary.org/obo/RO_0002470	e4ca6a35d15f33153700d48b6be93e25					http://gomexsi.tamucc.edu			b4ff2dd97e8a3c5920ba1bc6c8db779b
    349f0542f1568988f7af4da5ce086d9b	805b5b8a5129e4d85b1b2cc4fcb90cfd	http://purl.obolibrary.org/obo/RO_0002470	63752c1bb58d07ef9b22d366339e74da					http://gomexsi.tamucc.edu			b4ff2dd97e8a3c5920ba1bc6c8db779b
taxon.tab
    NCBI:203314	https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=203314			Gobiosoma bosc		Metazoa	Chordata	Actinopteri	Gobiiformes	Gobiidae	Gobiosoma	species		
    EOL_V2:344200	https://doi.org/10.5281/zenodo.1495266#344200			Gammarus mucronatus		Animalia	Arthropoda	Malacostraca	Amphipoda	Gammaridae	Gammarus	species		
    FBC:SLB:SpecCode:25697	https://sealifebase.org/summary/25697			Rhithropanopeus harrisii				Malacostraca	Decapoda	Panopeidae	Rhithropanopeus	species		
But in eol.org this taxon eats a lot of higher-level taxa including Metazoa:
https://eol.org/pages/46566400/data?predicate_id=696
Do we need to truncate this resource before reharvest-republish steps?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ end

----------------------------------------------------------------- Jenkins entry: as of Jan 2, 2024
cd /html/eol_php_code/update_resources/connectors

# step 1: OK
php5.6 globi_data.php jenkins
# generates globi_associations.tar.gz
# then also...
# generates globi_associations_final.tar.gz
#exit 1 #during tests

# step 2: OK - part of main operation. Temporarily commented as I'm fixing above script.
php5.6 make_hash_IDs_4Deltas.php jenkins '{"task": "", "resource":"Deltas_4hashing", "resource_id":"globi_associations_final"}'
# generates globi_associations_delta.tar.gz

# step 3: OK remove unused Reference entries
php5.6 remove_unused_references.php jenkins '{"resource_id": "globi_associations_delta", "resource": "remove_unused_references", "resource_name": "GloBI"}'
# generates globi_associations_tmp1.tar.gz

# step 4: OK remove unused Occurrence entries
php5.6 remove_unused_occurrences.php jenkins '{"resource_id": "globi_associations_tmp1", "resource": "remove_unused_occurrences", "resource_name": "GloBI"}'
# generates globi_associations_tmp2.tar.gz

# === LAST STEP: copy globi_associations_tmp2.tar.gz to globi_assoc.tar.gz OK
cd /html/eol_php_code/applications/content_server/resources
cp globi_associations_tmp2.tar.gz globi_assoc.tar.gz
ls -lt globi_associations_tmp2.tar.gz
ls -lt globi_assoc.tar.gz
# then delete globi_associations_tmp2.tar.gz
rm -f globi_associations_tmp2.tar.gz

cd /html/eol_php_code/update_resources/connectors
php5.6 ckan_api_access.php jenkins "c8392978-16c2-453b-8f0e-668fbf284b61"
----------------------------------------------------------------- Jenkins entry end

++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Stats:
As of May 27, 2020
[change the associationType to pathogen_of] => 168
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1098
[2. Records of plants parasitizing animals are likely to be errors] => 1280
[3. Records of plants having animals as hosts are likely to be errors] => 5861
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 978
[5. Records of plants laying eggs are likely to be errors'] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 1415
total rows = 10,632
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
As of May 28, 2020
[change the associationType to pathogen_of] => 168
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1098
[2. Records of plants parasitizing animals are likely to be errors] => 1236
[3. Records of plants having animals as hosts are likely to be errors] => 5861
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 978
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 1411
Total rows = 10,584
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
As of May 30|31, 2020 - Mac Mini
[change the associationType to pathogen_of] => 168
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1099
[2. Records of plants parasitizing animals are likely to be errors] => 1237
[3. Records of plants having animals as hosts are likely to be errors] => 5861
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 987
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 1411
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
As of Aug 12, 2020 - eol-archive
[change the associationType to pathogen_of] => 177
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 985
[2. Records of plants parasitizing animals are likely to be errors] => 847
[3. Records of plants having animals as hosts are likely to be errors] => 6332
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 989
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 1345
[7a. Records of organisms other than plants having flower visitors are probably errors] => 758
Total rows = 11,256

below here 'Jen's preferred term' is applied:

[change the associationType to pathogen_of] => 177
Latest version, for review.
- DwCA [OpenData|https://opendata.eol.org/dataset/globi/resource/c8392978-16c2-453b-8f0e-668fbf284b61]
- refuted records [OpenData|https://opendata.eol.org/dataset/globi/resource/92595520-35f3-48f2-95cf-ea67f7c455c3]
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 985
[2. Records of plants parasitizing animals are likely to be errors] => 847
[3. Records of plants having animals as hosts are likely to be errors] => 6332
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 989
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 1345
[7. Records of organisms other than plants having flower visitors are probably errors] => 758
Total rows = 11256
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
as of Sep 3, 2020:
[change the associationType to pathogen_of] => 177
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 990
[2. Records of plants parasitizing animals are likely to be errors] => 840
[3. Records of plants having animals as hosts are likely to be errors] => 1113
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 948
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 2217
[7. Records of organisms other than plants having flower visitors are probably errors] => 743
Total rows = 6851
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Hi Katja,
Field now renamed to "argumentReasonId".
as of Sep 5, 2020:
[change the associationType to pathogen_of] => 176
Latest version, for review.
- DwCA [OpenData|https://opendata.eol.org/dataset/globi/resource/c8392978-16c2-453b-8f0e-668fbf284b61]
- refuted records [OpenData|https://opendata.eol.org/dataset/globi/resource/92595520-35f3-48f2-95cf-ea67f7c455c3]
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 990
[2. Records of plants parasitizing animals are likely to be errors] => 840
[3. Records of plants having animals as hosts are likely to be errors] => 1113
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 950
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 2218
[7. Records of organisms other than plants having flower visitors are probably errors] => 741
Total rows = 6852
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
as of Dec 7, 2020
[change the associationType to pathogen_of] => 171

Hi Katja,
Latest as of Dec 7, 2020. For review.
- DwCA [OpenData|https://opendata.eol.org/dataset/globi/resource/c8392978-16c2-453b-8f0e-668fbf284b61]
- refuted records [OpenData|https://opendata.eol.org/dataset/globi/resource/92595520-35f3-48f2-95cf-ea67f7c455c3]

[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1485
[2. Records of plants parasitizing animals are likely to be errors] => 905
[3. Records of plants having animals as hosts are likely to be errors] => 1122
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 1405
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 1945
[7. Records of organisms other than plants having flower visitors are probably errors] => 599
Total rows = 7461

*Please take note #6 here is from old criteria: n=1945
{color:red}
sourceTaxon does NOT have kingdom "Viruses"
AND targetTaxon has kingdom "Viruses"
AND associationType is 
"ectoparasite of" (http://purl.obolibrary.org/obo/RO_0002632) OR 
"endoparasite of" (http://purl.obolibrary.org/obo/RO_0002634) OR 
"parasite of" (http://purl.obolibrary.org/obo/RO_0002444) OR 
"kleptoparasite of" (http://purl.obolibrary.org/obo/RO_0008503) OR 
"parasitoid of" http://purl.obolibrary.org/obo/RO_0002208 OR 
"pathogen of" (http://purl.obolibrary.org/obo/RO_0002556) OR 
"eats" (http://purl.obolibrary.org/obo/RO_0002470) OR 
"preys on" (http://purl.obolibrary.org/obo/RO_0002439)
{color}

The new criteria did not meet any records: n=0
{color:red}
sourceTaxon has kingdom "Viruses"
AND targetTaxon does NOT have kingdom "Viruses"
AND associationType is:
"has ectoparasite" (http://purl.obolibrary.org/obo/RO_0002633) OR
"has endoparasite" (http://purl.obolibrary.org/obo/RO_0002635) OR
"parasitized by" (http://purl.obolibrary.org/obo/RO_0002445) OR
"kleptoparasitized by" (http://purl.obolibrary.org/obo/RO_0008504) OR
"has parasitoid" (http://purl.obolibrary.org/obo/RO_0002209) OR
"has pathogen" (http://purl.obolibrary.org/obo/RO_0002557)
{color}

Thanks.
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
as of Dec 21, 2020
[change the associationType to pathogen_of] => 196

Hi Katja,
We got hits for #6, when adding the said 8 names as viral kingdoms.
Latest as of Dec 21, 2020. For review.
- DwCA [OpenData|https://opendata.eol.org/dataset/globi/resource/c8392978-16c2-453b-8f0e-668fbf284b61]
- refuted records [OpenData|https://opendata.eol.org/dataset/globi/resource/92595520-35f3-48f2-95cf-ea67f7c455c3]

[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1485
[2. Records of plants parasitizing animals are likely to be errors] => 905
[3. Records of plants having animals as hosts are likely to be errors] => 1122
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 1405
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 7244
[7. Records of organisms other than plants having flower visitors are probably errors] => 599
Total rows = 12760
Thanks.
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
as of Dec 22, 2020
[change the associationType to pathogen_of] => 196

Hi Katja,
We got hits for the new reverse #7.
Latest as of Dec 22, 2020. For review.
- DwCA [OpenData|https://opendata.eol.org/dataset/globi/resource/c8392978-16c2-453b-8f0e-668fbf284b61]
{"association.tab":2435698, "occurrence_specific.tab":3455958, "reference.tab":1139966, "taxon.tab":294442}
- refuted records [OpenData|https://opendata.eol.org/dataset/globi/resource/92595520-35f3-48f2-95cf-ea67f7c455c3]

[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1485
[2. Records of plants parasitizing animals are likely to be errors] => 905
[3. Records of plants having animals as hosts are likely to be errors] => 1122
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 1405
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 7244
[7. Records of organisms other than plants having flower visitors are probably errors] => 599
[Expand rule (#7) to also cover the reverse] => 1254 [DATA-1874|https://eol-jira.bibalex.org/browse/DATA-1874]
Total rows = 14014
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
as of Jan 11, 2021 -- initiated by Eli. Not asked by anyone.
[change the associationType to pathogen_of] => 190
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1404
[2. Records of plants parasitizing animals are likely to be errors] => 1271
[3. Records of plants having animals as hosts are likely to be errors] => 1198
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 1037
[6a. Records of other organisms parasitizing or eating viruses are likely to be errors] => 7553
[7a. Records of organisms other than plants having flower visitors are probably errors] => 624
[7c. Records of organisms other than plants having flower visitors are probably errors] => 1322
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
as of Jun 14,2021 - not submitted. As I was debugging the fix to clean Associations - integrity check
[should be no more of this type, otherwise report to Jen] => 1846
[change the associationType to pathogen_of] => 130
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1042
[2. Records of plants parasitizing animals are likely to be errors] => 1013
[3. Records of plants having animals as hosts are likely to be errors] => 1660
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 1031
[6a. Records of other organisms parasitizing or eating viruses are likely to be errors] => 6750
[7c. Records of organisms other than plants having flower visitors are probably errors] => 1895
[7a. Records of organisms other than plants having flower visitors are probably errors] => 1285
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// echo "\n".date("Y_m_d_H_i_s")."\n"; exit;
// $GLOBALS['ENV_DEBUG'] = true;

// https://api.inaturalist.org/v1/taxa/900074
// http://api.gbif.org/v1/species/3934982

/*
$sci = 'Bivalve RNA virus G4';
$sci = 'Macrophoma millepuncta var. spinosae';
$sci = 'Haemolaelaps glasgowi';
$sci = 'Triachora unifasciata';
// $sci = 'Mycoplasma phage phiMFV1';
$sci = 'Dizygomyza morosa';
$sci = 'Sitona (Sitona) cylindricollis';
$sci = 'Lathyrus linifolius var. montanus';
$sci = 'Gloriosa stripe mosaic virus';
$sci = 'Mycoplasma phage phiMFV1';
echo "\n$sci";
echo "\n".Functions::canonical_form($sci)."\n";
exit("\n-end test-\n");
*/

/* testing
require_library('connectors/GloBIDataAPI');
$func = new GloBIDataAPI(false, 'globi');

$id = 'EOL:23326858';
$id = 'EOL:5356331';
$id = 'EOL:5409252'; //sample which goes to gbif
// $kingdom = $func->get_kingdom_from_EOLtaxonID($id);

$id = 'INAT_TAXON:76884';
$kingdom = $func->get_kingdom_from_iNATtaxonID($id);

echo "\n[$kingdom]\n";
exit("\n-end test-\n");
*/

require_library('connectors/DwCA_Utility');
ini_set('memory_limit','22096M'); //required 12096M
$resource_id = "globi_associations";

// /* //main operation
if($dwca = get_latest_globi_snapshot()) echo "\nDwCA URL: [$dwca]\n";
else { //old - manually picked the URL
    exit("\nERROR: cannot get the DwCA URL from partner site.\n");
    $dwca = 'https://depot.globalbioticinteractions.org/snapshot/target/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
    $dwca = 'https://depot.globalbioticinteractions.org/snapshot/target/eol-globi-datasets-1.1-SNAPSHOT-darwin-core-aggregated.zip'; //started using 21Jan2022
}

echo ("\nWill eventually proceed with this file: [$dwca]\n");

/* if u want to overwrite and run locally: only during dev
$dwca = 'http://localhost/cp/GloBI_2019/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
$dwca = 'http://localhost/cp/GloBI_2019/eol-globi-datasets-1.1-SNAPSHOT-darwin-core-aggregated.zip';
*/

/* FORCE assignment until new index from Jorrit becomes available
$dwca = "https://editors.eol.org/other_files/temp/eol-globi-datasets-1.1-SNAPSHOT-darwin-core-aggregated.zip";
*/

$func = new DwCA_Utility($resource_id, $dwca);

// worked in 1.0 but caused memory leak in 1.1 because latter is now a large DwCA and reference is a big file.
// $preferred_rowtypes = array('http://eol.org/schema/reference/reference'); //was forced to lower case in DwCA_Utility.php

// for 1.1 all four extensions will be parsed elsewhere (GloBIDataAPI). Not in DwCA_Utility.
$preferred_rowtypes = array();
$excluded_rowtypes = array('http://eol.org/schema/association', 'http://rs.tdwg.org/dwc/terms/occurrence', 
                           'http://eol.org/schema/reference/reference', 'http://rs.tdwg.org/dwc/terms/taxon');

$func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, false, $timestart); //3rd param true means delete folder
$func = false; //close memory
// */ //end main operation

/* used when testing changes in globi_associations.tar.gz (1st step). Comment in normal operation
echo "\n-Eli stop muna-\n"; return;
*/

// /*
$ret = run_utility($resource_id); //exit('stopx goes here...');
recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI
if($ret['undefined source occurrence'] || $ret['undefined target occurrence']) { echo "\nStart fixing Associations...\n";
// */
    $resource_id = "globi_associations_final"; //DwCA with fixed Associations tab
    $dwca = "https://editors.eol.org/eol_php_code/applications/content_server/resources/globi_associations.tar.gz";
    $func = new DwCA_Utility($resource_id, $dwca);
    $preferred_rowtypes = array();
    $excluded_rowtypes = array('http://eol.org/schema/association', 'http://rs.tdwg.org/dwc/terms/occurrence', 'http://eol.org/schema/reference/reference');
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, true, false, $timestart); //3rd param true means delete folder
    $ret = run_utility($resource_id); //check if Associations is finally fixed. Should be fixed at this point.
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI
// /*
}
// */

/*
how to extract .tar.gz into a folder: works OK
mkdir globi_associations
tar -xzf globi_associations.tar.gz -C globi_associations
chmod 775 globi_associations
*/

function run_utility($resource_id)
{
    // /* utility ==========================
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $ret = $func->check_if_source_and_taxon_in_associations_exist($resource_id, false, 'occurrence_specific.tab');
    echo "\nundefined source occurrence [$resource_id]:" . count(@$ret['undefined source occurrence'])."\n";
    echo "\nundefined target occurrence [$resource_id]:" . count(@$ret['undefined target occurrence'])."\n";
    return $ret;
    // ===================================== */
}
function get_latest_globi_snapshot()
{   // 1st option
    $url = "https://www.globalbioticinteractions.org/data";
    if($html = Functions::lookup_with_cache($url, array('expire_seconds' => 0))) {
        if(preg_match_all("/<a href=\"(.*?)\"/ims", $html, $arr)) { // print_r($arr[1]); exit;
            foreach($arr[1] as $href) {
                if(strpos($href, "SNAPSHOT-darwin-core-aggregated.zip") !== false) { //string is found
                    $sought_file1 = $href;
                    break;
                }
            }
        }
    }
    // 2nd option: https://www.globalbioticinteractions.org/data.tsv (from Joritt)
    $url = "https://www.globalbioticinteractions.org/data.tsv";
    $arr = file($url); // print_r($arr); exit;
    foreach($arr as $row) {
        $arr = explode("\t", $row);
        if($arr[0] == "dwca-by-study.zip") { // print_r($arr);
            $sought_file2 = $arr[2];
        }
    }
    echo "\nsought_file1: [$sought_file1]\n";
    echo "\nsought_file2: [$sought_file2]\n";
    if($sought_file1 == $sought_file2) {
        echo "\nOK same URLs detected. Will proceed.\n";
        return $sought_file2;
    }
    else exit("\nDIFFERENT URLs detected. Will NOT proceed.\n");
}
?>