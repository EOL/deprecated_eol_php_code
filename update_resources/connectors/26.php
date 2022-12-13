<?php
namespace php_active_record;
/*
WORMS archive
Now partner provides/hosts a DWC-A file. Connector also converts Distribution text into structured data.
estimated execution time: 30 mins, excluding download time of the archive file from partner's server
*Started Jenkins execution 2017-Oct-10. Took 22 mins.
                                                                                                        2017
                24Sep'14    20Nov'14                    1Dec'14     1Apr'15     8Apr    15Jun   8March  6-Oct   10-Oct
agent:          [922]       948                         948         1015        1033    1044    1245    1352    1388
measurement:    [1,172,968] 1,484,488   diff 311,520    293,645     383423      383981  384798  411206  425373  428106
media_resource: [101,513]   102,009     diff 496        102,009     134461      134708  135570  144212  152067  160716
occurrence:     [279,966]   576,055                     291,683     380880      381457  382283          422847  425583
reference:      [319987]    322257                      322257      550506      552080  554390  581465  629030  637206
taxon:          [311866]    313006      diff 1,140      313006      539550      540877  512283  532578  573202  581849
vernacular:     [42231]     42226                       42226       46654       46657   46661   47809   72552   73967
                            
                            2017
with media objects:         6-Oct   10-Oct
[accepted]  =>      327966  332194  337287
[count]     =>      539549  573202  581849
[synonym]   =>      211583  203983  207575
[]          =>                      36987

dynamic hierarchy   27Apr2017   18May2017
[accepted] =>       303570      300105
[synonym] =>        134132      135024
[] =>               5329        0
[count] =>          443031      435129

total no parent:    134134      135026          ? not sure about these counts
/terms/taxon:       443440      435608          ? not sure about these counts

                            10-Oct
Total undefined parents:    2503
Total taxa without parents: 207577

Historical:
26	Tuesday 2017-10-10 12:02:32 AM	{"agent.tab":1388,"measurement_or_fact.tab":428106,"media_resource.tab":160716,"occurrence.tab":425583,"reference.tab":637206,"taxon.tab":581849,"vernacular_name.tab":73967}
26	Monday 2017-12-04 07:53:33 PM	{"agent.tab":1403,"measurement_or_fact.tab":431072,"media_resource.tab":164848,"occurrence.tab":428547,"reference.tab":661032,"taxon.tab":603823,"vernacular_name.tab":74103}
26	Friday 2018-03-02 08:54:46 AM	{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116}
26	Wednesday 2018-03-07 10:44:40 AM{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116}
26	Wednesday 2018-03-07 07:09:46 PM{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116} all-hash measurementID
26	Thursday 2018-03-08 08:14:32 PM	{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116}
26	Thursday 2018-10-18 02:32:29 AM	{"agent.tab":1533,"measurement_or_fact.tab":539555,"media_resource.tab":92033,"occurrence.tab":535762,"reference.tab":691566,"taxon.tab":625782,"vernacular_name.tab":74148}
26	Thursday 2018-10-18 09:38:17 PM	{"agent.tab":1533,"measurement_or_fact.tab":539555,"media_resource.tab":85041,"occurrence.tab":535762,"reference.tab":691566,"taxon.tab":625782,"vernacular_name.tab":74148}
26	Saturday 2019-08-10 12:25:16 AM	{"agent.tab":1592,"measurement_or_fact.tab":553562,"media_resource.tab":85088,"occurrence.tab":549786,"reference.tab":604956,"taxon.tab":579191,"vernacular_name.tab":79059}
exec time: ~30 minutes

as of Aug 23, 2019
26	Friday 2019-08-23 05:38:00 AM	{"agent.tab":1592,"association.tab":69630,"measurement_or_fact_specific.tab":2853465,"media_resource.tab":85088,"occurrence_specific.tab":2138761,"reference.tab":604956,"taxon.tab":579821,"vernacular_name.tab":79059} Mac Mini
26	Friday 2019-08-23 05:09:26 AM	{"agent.tab":1592,"association.tab":69630,"measurement_or_fact_specific.tab":2853465,"media_resource.tab":85088,"occurrence_specific.tab":2138761,"reference.tab":604956,"taxon.tab":579821,"vernacular_name.tab":79059} eol-archive
26	Friday 2019-08-23 08:53:32 AM	{"agent.tab":1592,"association.tab":69629,"measurement_or_fact_specific.tab":2853465,"media_resource.tab":85088,"occurrence_specific.tab":2138760,"reference.tab":604956,"taxon.tab":579820,"vernacular_name.tab":79059} eol-archive
start below of new traits, no synonyms, only accepted taxa
26	Monday 2019-08-26 05:58:25 AM	{"agent.tab":1592,"association.tab":69107,"measurement_or_fact_specific.tab":3354792,"media_resource.tab":85088,"occurrence_specific.tab":2141506,"reference.tab":604956,"taxon.tab":338472,"vernacular_name.tab":79059} eol-archive, same as Mac Mini
26	Tuesday 2019-09-03 11:43:25 PM	{"agent.tab":1597,"association.tab":73215,"measurement_or_fact_specific.tab":3376634,"media_resource.tab":85232,"occurrence_specific.tab":2160600,"reference.tab":608368,"taxon.tab":340007,"vernacular_name.tab":79081} consistent OK
26	Tuesday 2019-09-10 12:49:04 AM	{"agent.tab":1597,"association.tab":73215,"measurement_or_fact_specific.tab":3376634,"media_resource.tab":85232,"occurrence_specific.tab":2160600,"reference.tab":608368,"taxon.tab":340007,"vernacular_name.tab":79081}
26	Thursday 2019-09-19 11:46:55 AM	{"agent.tab":1597,"association.tab":73215,"measurement_or_fact_specific.tab":3376634,"media_resource.tab":85232,"occurrence_specific.tab":2160600,"reference.tab":608368,"taxon.tab":340007,"vernacular_name.tab":79081}
26	Thursday 2019-10-17 08:47:04 AM	{"agent.tab":1603,"association.tab":76387,"measurement_or_fact_specific.tab":3406848,"media_resource.tab":85431,"occurrence_specific.tab":2180297,"reference.tab":612137,"taxon.tab":341924,"vernacular_name.tab":79111,"time_elapsed":{"min":43.71,"hr":0.73}} consistent OK
26	Sunday 2019-11-10 12:50:29 AM	{"agent.tab":1612,"association.tab":76363,"measurement_or_fact_specific.tab":3410186,"media_resource.tab":85602,"occurrence_specific.tab":2182490,"reference.tab":614535,"taxon.tab":342039,"vernacular_name.tab":79130,"time_elapsed":{"sec":2548.89,"min":42.48,"hr":0.71}}

Based on new Dec 2019: http://www.marinespecies.org/export/eol/WoRMS2EoL.zip.
'http://rs.tdwg.org/dwc/terms/measurementType' == 'Feedingtype' does not exist anymore
So this means there is no more association data from WoRMS.
26	Friday 2019-12-06 07:34:50 AM	{"agent.tab":1615,"measurement_or_fact_specific.tab":3406168,"media_resource.tab":85783,"occurrence_specific.tab":2068198,"reference.tab":616890,"taxon.tab":335022,"vernacular_name.tab":79161,"time_elapsed":{"sec":2669.63,"min":44.49,"hr":0.74}}
26	Sunday 2019-12-15 11:54:39 PM	{"agent.tab":1615,"measurement_or_fact_specific.tab":3406168,"media_resource.tab":85783,"occurrence_specific.tab":2068198,"reference.tab":616890,"taxon.tab":335022,"vernacular_name.tab":79161,"time_elapsed":{"sec":2863.38,"min":47.72,"hr":0.8}}
26	Monday 2019-12-16 01:39:12 PM	{"agent.tab":1615,"measurement_or_fact_specific.tab":3406165,"media_resource.tab":85783,"occurrence_specific.tab":2068198,"reference.tab":616890,"taxon.tab":335022,"vernacular_name.tab":79161,"time_elapsed":{"sec":2689.37,"min":44.82,"hr":0.75}}
26	Thursday 2020-02-06 04:47:05 AM	{"agent.tab":1639,"measurement_or_fact_specific.tab":3416758,"media_resource.tab":86178,"occurrence_specific.tab":2073724,"reference.tab":639196,"taxon.tab":352690,"vernacular_name.tab":79229,"time_elapsed":{"sec":2752.68,"min":45.88,"hr":0.76}}
26	Tuesday 2020-02-11 04:42:40 AM	{"agent.tab":1639,"measurement_or_fact_specific.tab":3414599,"media_resource.tab":86178,"occurrence_specific.tab":2073724,"reference.tab":639196,"taxon.tab":352690,"vernacular_name.tab":79229,"time_elapsed":{"sec":2806.72,"min":46.78,"hr":0.78}}

Start where MoF records with parents in 26_undefined_parentMeasurementIDs.txt will be auto removed:
26	Tuesday 2020-02-11 09:50:00 AM	{"agent.tab":1639,"measurement_or_fact_specific.tab":3414427,"media_resource.tab":86178,"occurrence_specific.tab":2073724,"reference.tab":639196,"taxon.tab":352690,"vernacular_name.tab":79229,"time_elapsed":false}
26	Wednesday 2020-02-12 04:25:35 AM{"agent.tab":1639, "measurement_or_fact_specific.tab":3414427, "media_resource.tab":86178, "occurrence_specific.tab":2073724, "reference.tab":639196, "taxon.tab":352690, "vernacular_name.tab":79229,"time_elapsed":false}
                                    {"agent.tab":1639, "measurement_or_fact_specific.tab":3414427, "media_resource.tab":86178, "occurrence_specific.tab":2073724, "reference.tab":639196, "taxon.tab":352690, "vernacular_name.tab":79229}
26	Monday 2020-03-02 08:55:43 AM	{"agent.tab":1644, "measurement_or_fact_specific.tab":3408286, "media_resource.tab":86519, "occurrence_specific.tab":2106782, "reference.tab":641496, "taxon.tab":353383, "vernacular_name.tab":79240, "time_elapsed":{"sec":2881.02, "min":48.02, "hr":0.8}}
26	Monday 2020-03-02 09:28:04 AM	{"agent.tab":1644, "measurement_or_fact_specific.tab":3405872, "media_resource.tab":86519, "occurrence_specific.tab":2106782, "reference.tab":641496, "taxon.tab":353383, "vernacular_name.tab":79240, "time_elapsed":false}
Expected reduced no. of MoF and Occurrence --- so consistent OK
26	Thursday 2020-03-19 12:04:40 PM	{"agent.tab":1644, "measurement_or_fact_specific.tab":3374264, "media_resource.tab":86519, "occurrence_specific.tab":2082560, "reference.tab":641496, "taxon.tab":353383, "vernacular_name.tab":79240, "time_elapsed":false}
Batches of 2 rows:
26	Friday 2020-06-12 01:22:50 AM	{"agent.tab":1664, "measurement_or_fact_specific.tab":3417987, "media_resource.tab":87379, "occurrence_specific.tab":2121662, "reference.tab":648535, "taxon.tab":355711, "vernacular_name.tab":80309, "time_elapsed":{"sec":3890.43, "min":64.84, "hr":1.08}}
26	Friday 2020-06-12 02:14:25 AM	{"agent.tab":1664, "measurement_or_fact_specific.tab":3414249, "media_resource.tab":87379, "occurrence_specific.tab":2121662, "reference.tab":648535, "taxon.tab":355711, "vernacular_name.tab":80309, "time_elapsed":false}
26	Tue 2020-11-10 01:07:43 AM	    {"agent.tab":1682, "measurement_or_fact_specific.tab":3425398, "media_resource.tab":91653, "occurrence_specific.tab":2157834, "reference.tab":670315, "taxon.tab":367878, "vernacular_name.tab":82322, "time_elapsed":{"sec":3581.52, "min":59.69, "hr":0.99}}
26	Tue 2020-11-10 01:54:23 AM	    {"agent.tab":1682, "measurement_or_fact_specific.tab":3325053, "media_resource.tab":91653, "occurrence_specific.tab":2157834, "reference.tab":670315, "taxon.tab":367878, "vernacular_name.tab":82322, "time_elapsed":false}
Steady increase OK
26	Mon 2021-02-08 05:35:41 AM	{"agent.tab":1709, "measurement_or_fact_specific.tab":3335411, "media_resource.tab":92007, "occurrence_specific.tab":2163968, "reference.tab":677563, "taxon.tab":369567, "vernacular_name.tab":85178, "time_elapsed":false}
26	Tue 2021-05-11 12:23:29 PM	{"agent.tab":1771, "measurement_or_fact_specific.tab":3546416, "media_resource.tab":92507, "occurrence_specific.tab":2214608, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":{"sec":3891.53, "min":64.86, "hr":1.08}}
26	Tue 2021-05-11 01:15:15 PM	{"agent.tab":1771, "measurement_or_fact_specific.tab":3402028, "media_resource.tab":92507, "occurrence_specific.tab":2214437, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":false}
26	Tue 2021-05-11 11:54:18 PM	{"agent.tab":1771, "measurement_or_fact_specific.tab":3546416, "media_resource.tab":92507, "occurrence_specific.tab":2214608, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":{"sec":3820.85, "min":63.68, "hr":1.06}}
26	Wed 2021-05-12 12:44:52 AM	{"agent.tab":1771, "measurement_or_fact_specific.tab":3402028, "media_resource.tab":92507, "occurrence_specific.tab":2214437, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":false}
26	Wed 2021-05-12 09:00:05 AM	{"agent.tab":1771, "measurement_or_fact_specific.tab":3546416, "media_resource.tab":92507, "occurrence_specific.tab":2214608, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":{"sec":3976.91, "min":66.28, "hr":1.1}}
26	Wed 2021-05-12 09:50:29 AM	{"agent.tab":1771, "measurement_or_fact_specific.tab":3402209, "media_resource.tab":92507, "occurrence_specific.tab":2214608, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":false}
start removed all occurrences and trait records associated with specified taxa (WoRMS_mismapped_subgenera):
26	Wed 2021-05-12 12:20:44 PM	{"agent.tab":1771, "measurement_or_fact_specific.tab":3402028, "media_resource.tab":92507, "occurrence_specific.tab":2214437, "reference.tab":689291, "taxon.tab":373164, "vernacular_name.tab":85152, "time_elapsed":false}
26	Wed 2021-06-09 09:37:13 AM	{"agent.tab":1776, "measurement_or_fact_specific.tab":3402495, "media_resource.tab":92746, "occurrence_specific.tab":2214727, "reference.tab":692231, "taxon.tab":374006, "vernacular_name.tab":85169, "time_elapsed":false}
26	Wed 2021-06-09 11:53:22 AM	{"agent.tab":1776, "measurement_or_fact_specific.tab":3402495, "media_resource.tab":92746, "occurrence_specific.tab":2214727, "reference.tab":692231, "taxon.tab":374006, "vernacular_name.tab":85169, "time_elapsed":false}
26	Thu 2021-06-10 02:02:22 AM	{"agent.tab":1776, "measurement_or_fact_specific.tab":3402495, "media_resource.tab":92746, "occurrence_specific.tab":2214727, "reference.tab":692231, "taxon.tab":374006, "vernacular_name.tab":85169, "time_elapsed":false}

around Oct 13, 2021 - Jira                  {"agent.tab":1813, "MoF.tab":2526290, "media_resource.tab":94660, "occur.tab":2151130, "reference.tab":703450, "taxon.tab":377647, "vernacular_name.tab":85681} 
26_ENV	        Thu 2022-02-10 06:01:06 AM	{"agent.tab":1829, "MoF.tab":3286487, "media_resource.tab":95531, "occur.tab":2397461, "reference.tab":711533, "taxon.tab":379551, "vernacular_name.tab":85716, "time_elapsed":{"sec":4267.01, "min":71.12, "hr":1.19}}
26_ENV_final	Thu 2022-02-10 07:05:14 AM	{"agent.tab":1829, "MoF.tab":3286487, "media_resource.tab":95531, "occur.tab":2397461, "reference.tab":711533, "taxon.tab":379551, "vernacular_name.tab":85716, "time_elapsed":{"sec":3747.21, "min":62.45, "hr":1.04}}
since delta MoF and occurrence are less --- maybe a good thing. Definitely removed identical records in occurrence, and MoF.
26_delta	    Thu 2022-02-24 03:33:18 AM	{"agent.tab":1829, "MoF.tab":2738962, "media_resource.tab":95531, "occur.tab":1153098, "reference.tab":711533, "taxon.tab":379551, "vernacular_name.tab":85716, "time_elapsed":{"sec":2958.65, "min":49.31, "hr":0.82}}
26_delta	    Thu 2022-02-24 09:30:40 AM	{"agent.tab":1829, "MoF.tab":2738962, "media_resource.tab":95531, "occur.tab":1153098, "reference.tab":711533, "taxon.tab":379551, "vernacular_name.tab":85716, "time_elapsed":{"sec":2995.03, "min":49.92, "hr":0.83}}
26_delta	    Fri 2022-02-25 01:55:17 PM	{"agent.tab":1829, "MoF.tab":2738962, "media_resource.tab":95531, "occur.tab":1153098, "reference.tab":711533, "taxon.tab":379551, "vernacular_name.tab":85716, "time_elapsed":{"sec":3028.7, "min":50.48, "hr":0.84}}

26_delta	    Sun 2022-04-10 07:06:40 AM	{"agent":1855, "MoF":2253336, "media":95845, "occur":1105752, "ref":724227, "taxon":383405, "vernacular":86337, "time_elapsed":{"sec":2735.98, "min":45.6, "hr":0.76}}
26_delta	    Tue 2022-04-12 05:42:11 AM	{"agent":1855, "MoF":2253336, "media":95845, "occur":1105752, "ref":724227, "taxon":383405, "vernacular":86337, "time_elapsed":{"sec":3046.25, "min":50.77, "hr":0.85}}
26_delta_new	Tue 2022-04-12 12:20:59 PM	{"agent":1855, "MoF":2228457, "media":95845, "occur":1100358, "ref":724227, "taxon":383405, "vernacular":86337, "time_elapsed":{"sec":2358.21, "min":39.3, "hr":0.66}}
26_delta_new	Sun 2022-05-08 12:45:32 PM	{"agent":1855, "MoF":2231822, "media":95845, "occur":1103284, "ref":724227, "taxon":383405, "vernacular":86337, "time_elapsed":{"sec":3718.92, "min":61.98, "hr":1.03}}
START PROPER FILTER OF marine+terrestrial in MoF Habitat records: now only contradicting Habitat records are removed
26_delta_new	Mon 2022-05-09 06:50:30 AM	{"agent":1855, "MoF":2231822, "media":95845, "occur":1103284, "ref":724227, "taxon":383405, "vernacular":86337, "time_elapsed":{"sec":2411.52, "min":40.19, "hr":0.67}}
26_delta_new	Fri 2022-06-10 08:24:38 AM	{"agent":1871, "MoF":2261214, "media":96141, "occur":1125064, "ref":731895, "taxon":384507, "vernacular":86667, "time_elapsed":{"sec":2518.82, "min":41.98, "hr":0.7}}
26_delta_new	Thu 2022-06-23 10:30:42 PM	{"agent":1871, "MoF":2261054, "media":96141, "occur":1124944, "ref":731895, "taxon":384507, "vernacular":86667, "time_elapsed":{"sec":2425.16, "min":40.42, "hr":0.67}}

26_MoF_normalized	Mon 2022-05-09 09:10:53 {"agent":1855, "MoF":2231607, "media":95845, "occur":1103284, "ref":724227, "taxon":383405, "vernacular":86337, "time_elapsed":{"sec":2459.97, "min":41, "hr":0.68}}
26_MoF_normalized	Fri 2022-06-10 09:08:28 {"agent":1871, "MoF":2260999, "media":96141, "occur":1125064, "ref":731895, "taxon":384507, "vernacular":86667, "time_elapsed":{"sec":2619.43, "min":43.66, "hr":0.73}} Consistent OK
26_MoF_normalized	Thu 2022-06-23 11:13:17 {"agent":1871, "MoF":2260839, "media":96141, "occur":1124944, "ref":731895, "taxon":384507, "vernacular":86667, "time_elapsed":{"sec":2542.9, "min":42.38, "hr":0.71}}
Below expected decrease in Vernaculars: removed vernaculars where lang = "DEU"
Unexpected decrease in Occurrence though. Not yet sure the reason for decrease.
26	                Sun 2022-08-28 11:13:08 AM	{"agent":1879, "MoF":3063929, "media":98075, "occur":2433912, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":4133.49, "min":68.89, "hr":1.15}}
26_meta_recoded_1	Sun 2022-08-28 12:06:12 PM	{"agent":1879, "MoF":2904153, "media":98075, "occur":2433912, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":3084.07, "min":51.4, "hr":0.86}}
26_meta_recoded	    Sun 2022-08-28 12:59:38 PM	{"agent":1879, "MoF":2852741, "media":98075, "occur":2433912, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":3204.84, "min":53.41, "hr":0.89}}
26_ENV	            Sun 2022-08-28 02:01:22 PM	{"agent":1879, "MoF":2630398, "media":98075, "occur":2211569, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":3701.94, "min":61.7, "hr":1.03}}
26_ENV_final	    Sun 2022-08-28 02:55:36 PM	{"agent":1879, "MoF":2630398, "media":98075, "occur":2211569, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":3174.34, "min":52.91, "hr":0.88}}
26_delta	        Sun 2022-08-28 03:42:33 PM	{"agent":1879, "MoF":2234244, "media":98075, "occur":657098, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":2759.42, "min":45.99, "hr":0.77}}
26_delta_new	    Sun 2022-08-28 04:17:42 PM	{"agent":1879, "MoF":2212478, "media":98075, "occur":654508, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":2107.34, "min":35.12, "hr":0.59}}
26_MoF_normalized	Sun 2022-08-28 04:55:33 PM	{"agent":1879, "MoF":2212231, "media":98075, "occur":654508, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":2259.38, "min":37.66, "hr":0.63}}
Below expected decrease in MoF: removed MoF where measurementAccuracy = "inherited from 558"
26	                Tue 2022-08-30 04:56:49 AM	{"agent":1879, "MoF":2999052, "media":98075, "occur":2369035, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":4056.74, "min":67.61, "hr":1.13}}
26_meta_recoded_1	Tue 2022-08-30 07:19:01 AM	{"agent":1879, "MoF":2839276, "Media":98075, "occur":2369035, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":3075.03, "min":51.25, "hr":0.85}}
26_meta_recoded	    Tue 2022-08-30 08:10:55 AM	{"agent":1879, "MoF":2787864, "Media":98075, "occur":2369035, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":3111.54, "min":51.86, "hr":0.86}}
26_ENV	            Tue 2022-08-30 09:12:27 AM	{"agent":1879, "MoF":2565521, "Media":98075, "occur":2146692, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":3689.73, "min":61.5, "hr":1.02}}
26_ENV_final	    Tue 2022-08-30 10:06:43 AM	{"agent":1879, "MoF":2565521, "Media":98075, "occur":2146692, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":3197.23, "min":53.29, "hr":0.89}}
26_delta	        Tue 2022-08-30 10:53:10 AM	{"agent":1879, "MoF":2169367, "Media":98075, "occur":646202, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":2729.57, "min":45.49, "hr":0.76}}
26_delta_new	    Tue 2022-08-30 11:28:41 AM	{"agent":1879, "MoF":2147615, "Media":98075, "occur":643612, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":2130.26, "min":35.5, "hr":0.59}}
26_MoF_normalized	Tue 2022-08-30 12:05:48 PM	{"agent":1879, "MoF":2147368, "Media":98075, "occur":643612, "ref":738320, "taxon":385717, "vernacular":79700, "time_elapsed":{"sec":2216.26, "min":36.94, "hr":0.62}}

26_ENV_final	    Mon 2022-09-19 05:48:49 PM	{"agent.tab":1893, "MoF":2571363, "Media":98246, "occur":2150909, "ref":741350, "taxon.tab":386382, "vernacular_name.tab":79720, "time_elapsed":{"sec":3304.97, "min":55.08, "hr":0.92}}
26_delta	        Mon 2022-09-19 06:37:29 PM	{"agent.tab":1893, "MoF":2173593, "Media":98246, "occur":647793, "ref":741350, "taxon.tab":386382, "vernacular_name.tab":79720, "time_elapsed":{"sec":2860.2, "min":47.67, "hr":0.79}}
26_delta_new	    Mon 2022-09-19 07:14:50 PM	{"agent.tab":1893, "MoF":2151836, "Media":98246, "occur":645202, "ref":741350, "taxon.tab":386382, "vernacular_name.tab":79720, "time_elapsed":{"sec":2240.12, "min":37.34, "hr":0.62}}
26_MoF_normalized	Mon 2022-09-19 07:53:17 PM	{"agent.tab":1893, "MoF":2151589, "Media":98246, "occur":645202, "ref":741350, "taxon.tab":386382, "vernacular_name.tab":79720, "time_elapsed":{"sec":2296.74, "min":38.28, "hr":0.64}}

26_ENV_final	    Tue 2022-09-20 07:09:25 PM	{"agent.tab":1893, "MoF":2571315, "Media":98246, "occur":2150861, "ref":741350, "taxon.tab":386382, "vernacular_name.tab":79720, "time_elapsed":{"sec":3356.44, "min":55.94, "hr":0.93}}
26_delta	        Tue 2022-09-20 07:57:23 PM	{"agent.tab":1893, "MoF":2173545, "Media":98246, "occur":647793, "ref":741350, "taxon.tab":386382, "vernacular_name.tab":79720, "time_elapsed":{"sec":2819.33, "min":46.99, "hr":0.78}}
26_delta_new	    Tue 2022-09-20 08:35:37 PM	{"agent.tab":1893, "MoF":2151796, "Media":98246, "occur":645202, "ref":741350, "taxon.tab":386382, "vernacular_name.tab":79720, "time_elapsed":{"sec":2292.95, "min":38.22, "hr":0.64}}
26_MoF_normalized	Tue 2022-09-20 09:14:22 PM	{"agent.tab":1893, "MoF":2151549, "Media":98246, "occur":645202, "ref":741350, "taxon.tab":386382, "vernacular_name.tab":79720, "time_elapsed":{"sec":2313.89, "min":38.56, "hr":0.64}}
26_MoF_normalized	Wed 2022-10-26 04:45:50 PM	{"agent.tab":1898, "MoF":2180412, "Media":98575, "occur":647545, "ref":744105, "taxon.tab":387245, "vernacular_name.tab":79752, "time_elapsed":{"sec":2512.61, "min":41.88, "hr":0.7}}

Below start of removing all MoF term URIs that are not in EOL Terms File:

26_ENV_final	    Tue 2022-11-08 09:54:26 PM	{"agent.tab":1901, "MoF":2608723, "Media":98829, "occur":2187458, "reference":746822, "taxon.tab":388204, "vernacular_name.tab":79759, "time_elapsed":{"sec":3102.67, "min":51.71, "hr":0.86}}
26_delta	        Tue 2022-11-08 10:40:09 PM	{"agent.tab":1901, "MoF":2210150, "Media":98829, "occur":651427, "reference":746822, "taxon.tab":388204, "vernacular_name.tab":79759, "time_elapsed":{"sec":2687.03, "min":44.78, "hr":0.75}}
26_delta_new	    Tue 2022-11-08 11:14:58 PM	{"agent.tab":1901, "MoF":2188507, "Media":98829, "occur":648863, "reference":746822, "taxon.tab":388204, "vernacular_name.tab":79759, "time_elapsed":{"sec":2088.54, "min":34.81, "hr":0.58}}
26_MoF_normalized	Tue 2022-11-08 11:50:54 PM	{"agent.tab":1901, "MoF":2188265, "Media":98829, "occur":648863, "reference":746822, "taxon.tab":388204, "vernacular_name.tab":79759, "time_elapsed":{"sec":2146.01, "min":35.77, "hr":0.6}}
26_MoF_normalized	Wed 2022-11-09 02:19:11 PM	{"agent.tab":1901, "MoF":2188265, "Media":98829, "occur":648863, "reference":746822, "taxon.tab":388204, "vernacular_name.tab":79759, "time_elapsed":{"sec":2174.33, "min":36.24, "hr":0.6}}
26_MoF_normalized	Thu 2022-11-10 03:52:55 AM	{"agent.tab":1901, "MoF":2188265, "Media":98829, "occur":648863, "reference":746822, "taxon.tab":388204, "vernacular_name.tab":79759, "time_elapsed":{"sec":2172.75, "min":36.21, "hr":0.6}}


-rw-r--r-- 1 root      root       150939877 Aug 28 16:56 WoRMS.tar.gz
-rw-r--r-- 1 root      root       150949430 Aug 28 16:16 26_delta_new.tar.gz
-rw-r--r-- 1 root      root       152178574 Aug 28 15:41 26_delta.tar.gz
-rw-r--r-- 1 root      root       221312747 Aug 28 14:54 26_ENV_final.tar.gz
-rw-r--r-- 1 root      root       182703125 Aug 28 14:00 26_ENV.tar.gz
-rw-r--r-- 1 root      root       204955296 Aug 28 12:58 26_meta_recoded.tar.gz
-rw-r--r-- 1 root      root       205082864 Aug 28 12:04 26_meta_recoded_1.tar.gz
-rw-r--r-- 1 root      root       207230964 Aug 28 11:11 26.tar.gz


=========================================================================================
In Jenkins: run one connector after the other:
#OK
php5.6 26.php jenkins
#generates 26.tar.gz

#OK
php5.6 resource_utility.php jenkins '{"resource_id": "26_meta_recoded_1", "task": "metadata_recoding"}'
#generates 26_meta_recoded_1.tar.gz

#OK
php5.6 resource_utility.php jenkins '{"resource_id": "26_meta_recoded", "task": "metadata_recoding"}'
#generates 26_meta_recoded.tar.gz

#OK
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"World Register of Marine Species", "resource_id":"26", "subjects":"Habitat|Distribution"}'
#generates 26_ENV

#OK
php5.6 resource_utility.php jenkins '{"resource_id": "26_ENV_final", "task": "change_measurementIDs"}'
#generates 26_ENV_final.tar.gz

#OK
php5.6 make_hash_IDs_4Deltas.php jenkins '{"task": "", "resource":"Deltas_4hashing", "resource_id":"26_ENV_final"}'
#generates 26_delta.tar.gz

#OK: remove all Habitat contradicting MoF records for taxon;
# i.e.  MoF habitat value(s) that are descendants of both marine and terrestrial
php5.6 rem_marine_terr_desc.php jenkins '{"resource_id":"26_delta"}'
#generates: 26_delta_new.tar.gz

#OK: remove the orphan child records in MoF
php5.6 dwca_MoF_fix.php jenkins '{"resource_id":"26_delta_new", "resource":"MoF_normalized"}'
#generates 26_MoF_normalized.tar.gz

# total 8 php files to run

#LAST STEP: copy last transactional DwCA to WoRMS.tar.gz OK

cd /html/eol_php_code/applications/content_server/resources
cp 26_MoF_normalized.tar.gz WoRMS.tar.gz
ls -lt 26_MoF_normalized.tar.gz
ls -lt WoRMS.tar.gz
rm -f 26_MoF_normalized.tar.gz
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

// $a['eli'] = '222';
// // $a[201306015586596728] = "http://purl.jp/bio/4/id/201306015586596728";
// $b['eli'] = '2s2s2s';
// // $b['kk'] = 'kkk';
// // $b[2] = '333';
// // $c = $a + $b;
// $c = array_merge($a, $b);
// print_r($c); exit("\n-end-\n");

/* testing...
$id = 1;
$id = 607688;
// $json = Functions::lookup_with_cache("http://www.marinespecies.org/rest/AphiaChildrenByAphiaID/$id");
// echo "\n[$json]\n";

// if(Functions::url_already_cached("http://www.marinespecies.org/rest/AphiaChildrenByAphiaID/$id")) echo "\nalready cached\n";
// else echo "\nnot yet cached\n";

$error_no = Functions::fake_user_agent_http_get("http://www.marinespecies.org/rest/AphiaChildrenByAphiaID/$id", array("return_error_no" => true));
echo "\n[$error_no]\n";
if($error_no == 0) echo "\nAccess OK\n";
else echo "Error access";
exit;

From .tmproj file:
	<string>../web/cp_new/WoRMS/Feb2020/metastats-2.tsv</string>
	<string>../01 EOL Projects ++/JIRA/DATA-1827 WoRMS</string>
	<string>../web/cp_new/WoRMS</string>
    <key>../web/cp_new/WoRMS/WoRMS_native_intro_mapping.txt</key>
*/

/* e.g. php 26.php jenkins taxonomy */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/ContributorsMapAPI');
require_library('connectors/WormsArchiveAPI');
$timestart = time_elapsed();
ini_set('memory_limit','10096M'); //required. From 7096M

if($cmdline_params['what'] == "taxonomy") $resource_id = "26_taxonomy";     //'taxonomy' -> used for DWH
else {                                                                      //'media_objects' is for original resource = 26
    $resource_id = "26";
    $cmdline_params['what'] = "media_objects";
}

// /* //main operation
$func = new WormsArchiveAPI($resource_id);
$func->get_all_taxa($cmdline_params['what']); 
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param should be false so it doesn't remove the /26/ folder which will be used below when diagnosing...
// */

/* utility - run this after 6 connectors during build-up
$func = new WormsArchiveAPI($resource_id);
$func->trim_text_files();
exit("\n");
*/

/* utility - Aug 25, 2019: https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=63762&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63762
This script lists all mtypes and its mvalue that were missing bec. it wasn't initialized by Jen yet. Probably deliberately done to exclude them.
$func = new WormsArchiveAPI($resource_id);
$func->investigate_missing_parents_in_MoF();
exit("\n");
*/

/* utility - May 11, 2021: https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=65930&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65930
require_library('connectors/WoRMS_post_process');
$func = new WoRMS_post_process(false, false);
$func->lookup_WoRMS_mismapped_subgenera();
exit("\n-end-\n");
*/

// /* main operation - continued
run_utility($resource_id);
recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH."26/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI
// */

// ==============================================================================================================================
// /* NEW Feb 11, 2020: start auto-remove children of 26_undefined_parentMeasurementIDs.txt in MoF ------------------------------
if(@filesize(CONTENT_RESOURCE_LOCAL_PATH.'26_undefined_parentMeasurementIDs.txt')) {
    echo "\nGoes here...\n";
    $resource_id = "26";
    $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/26.tar.gz';
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    $preferred_rowtypes = array("http://rs.tdwg.org/dwc/terms/taxon", "http://eol.org/schema/media/document", 
                        "http://eol.org/schema/reference/reference", "http://eol.org/schema/agent/agent", "http://rs.gbif.org/terms/1.0/vernacularname");
    // These 2 will be processed in WoRMS_post_process.php which will be called from DwCA_Utility.php
    // http://rs.tdwg.org/dwc/terms/measurementorfact
    // http://rs.tdwg.org/dwc/terms/occurrence

    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id);

    run_utility($resource_id);
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH."26/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI
}
// ------------------------------------------------------------------------------------------------------------------------------ */
// ==============================================================================================================================

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function run_utility($resource_id)
{
    // /* utility ==========================
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();

    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //2nd param true means output will write to text file
    echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);

    $without = $func->get_all_taxa_without_parent($resource_id, true); //true means output will write to text file
    echo "\nTotal taxa without parents:" . count($without)."\n"; unset($without);

    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'parentMeasurementID', 'measurement_or_fact_specific.tab');
    echo "\nTotal undefined parents MoF:" . count($undefined_parents)."\n";
    // ===================================== */
}
/* as of March 7, 2017
[ranks] => Array
    (
        [kingdom] => 
        [phylum] => 
        [subphylum] => 
        [order] => 
        [class] => 
        [subclass] => 
        [family] => 
        [suborder] => 
        [subfamily] => 
        [superfamily] => 
        [superorder] => 
        [infraorder] => 
        [superclass] => 
        [genus] => 
        [subkingdom] => 
        [tribe] => 
        [subgenus] => 
        [species] => 
        [variety] => 
        [subspecies] => 
        [section] => 
        [subsection] => 
        [form] => 
        [subvariety] => 
        [subform] => 
        [] => 
        [subtribe] => 
    )

[status] => Array
    (
        [accepted] => 
        [synonym] => 
        [] => 
    )

[establishmentMeans] => Array
    (
        [] => 
        [Alien] => 
        [Native - Endemic] => 
        [Native] => 
        [Origin uncertain] => 
        [Origin unknown] => 
        [Native - Non-endemic] => 
    )

[occurrenceStatus] => Array
    (
        [present] => 
        [excluded] => 
        [doubtful] => 
    )
*/
?>