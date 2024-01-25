<?php
namespace php_active_record;
/* This is generic way of calling ResourceUtility
removing taxa without MoF records.
first client: https://jenkins.eol.org/job/EOL%20Connectors/job/Environmental%20tagger%20for%20EOL%20resources/job/Wikipedia%20EN%20(English)/
              environments_2_eol.php for Wikipedia EN 

php update_resources/connectors/resource_utility.php _ '{"resource_id": "617_final", "task": "remove_taxa_without_MoF"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "wiki_en_report", "task": "report_4_Wikipedia_EN_traits"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "WoRMS2EoL_zip", "task": "add_canonical_in_taxa"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "26_ENV_final", "task": "change_measurementIDs"}'

 -------------------------- START of metadata_recoding  --------------------------
task_123
php update_resources/connectors/resource_utility.php _ '{"resource_id": "692_meta_recoded", "task": "metadata_recoding"}'

php update_resources/connectors/resource_utility.php _ '{"resource_id": "201_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "201_meta_recoded_2", "task": "metadata_recoding"}'

php update_resources/connectors/resource_utility.php _ '{"resource_id": "726_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "griis_meta_recoded", "task": "metadata_recoding"}'

task_67
php update_resources/connectors/resource_utility.php _ '{"resource_id": "770_meta_recoded", "task": "metadata_recoding"}'


php update_resources/connectors/resource_utility.php _ '{"resource_id": "natdb_meta_recoded_1", "task": "metadata_recoding"}'
->occurrenceRemarks
php update_resources/connectors/resource_utility.php _ '{"resource_id": "natdb_meta_recoded", "task": "metadata_recoding"}'
->lifeStage


php update_resources/connectors/resource_utility.php _ '{"resource_id": "copepods_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "42_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "727_meta_recoded", "task": "metadata_recoding"}'

php update_resources/connectors/resource_utility.php _ '{"resource_id": "707_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "try_dbase_2024_meta_recoded", "task": "metadata_recoding"}'
-> case where lifeStage is a col in MoF => move to a col in occurrence.

----------start Coral traits
php update_resources/connectors/resource_utility.php _ '{"resource_id": "cotr_meta_recoded_1", "task": "metadata_recoding"}'
-> fixes lifeStage
php update_resources/connectors/resource_utility.php _ '{"resource_id": "cotr_meta_recoded", "task": "metadata_recoding"}'
-> fixes eventDate as row in MoF
php update_resources/connectors/resource_utility.php _ '{"resource_id": "cotr_meta_recoded_final", "task": "metadata_recoding"}'
-> move a bunch of MoF columns as child rows in MoF (latest, as of Nov 17, 2021) --- https://eol-jira.bibalex.org/browse/DATA-1793?focusedCommentId=65808&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65808
----------end Coral traits

----------start WoRMS
WoRMS
-> case where lifeStage & sex is a row child in MoF => move to a col in occurrence
-> case where statisticalMethod is a row in MoF => move to a col in MoF
php update_resources/connectors/resource_utility.php _ '{"resource_id": "26_meta_recoded_1", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "26_meta_recoded", "task": "metadata_recoding"}'
----------end WoRMS

task_45
php update_resources/connectors/resource_utility.php _ '{"resource_id": "test_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "test2_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "test3_meta_recoded", "task": "metadata_recoding"}'
 -------------------------- END of metadata_recoding --------------------------

-------------------------- START of Unrecognized_fields --------------------------
php update_resources/connectors/resource_utility.php _ '{"resource_id": "Cicadellinae_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "Deltocephalinae_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "Appeltans_et_al_meta_recoded", "task": "metadata_recoding"}'

BioImages, the virtual fieldguide, UK (168.tar.gz)
php update_resources/connectors/resource_utility.php _ '{"resource_id": "168_meta_recoded", "task": "metadata_recoding"}'
168_meta_recoded	Thu 2021-01-14 07:53:44 AM	{"agent.tab":98, "media_resource.tab":129821, "taxon.tab":22302, "time_elapsed":{"sec":74.27, "min":1.24, "hr":0.02}} - eol-archive
168_meta_recoded	Sun 2021-01-31 11:17:51 PM	{"agent.tab":98, "media_resource.tab":129821, "taxon.tab":22302, "time_elapsed":{"sec":69.7, "min":1.16, "hr":0.02}}
168_meta_recoded	Mon 2021-02-01 09:43:36 PM	{"agent.tab":98, "media_resource.tab":129821, "taxon.tab":22302, "time_elapsed":{"sec":66.99, "min":1.12, "hr":0.02}}

Bioimages Vanderbilt (200) DwCA (200.tar.gz)
php update_resources/connectors/resource_utility.php _ '{"resource_id": "200_meta_recoded", "task": "metadata_recoding"}'

Braconid wasps, caterpillars and biocontrol
php update_resources/connectors/resource_utility.php _ '{"resource_id": "Braconids_meta_recoded", "task": "metadata_recoding"}'

Carrano, 2006 (1st client of task_move_col_in_occurrence_to_MoF_row_with_MeasurementOfTaxon_false)
php update_resources/connectors/resource_utility.php _ '{"resource_id": "Carrano_2006_meta_recoded", "task": "metadata_recoding"}'

Plant Growth Form Data from NMNH Botany specimens ---> (task_move_col_in_occurrence_to_MoF_row_with_MeasurementOfTaxon_false)
php update_resources/connectors/resource_utility.php _ '{"resource_id": "plant_growth_form_meta_recoded", "task": "metadata_recoding"}'

Catalogue of Life 2018-03-28 (col.tar.gz)
php update_resources/connectors/resource_utility.php _ '{"resource_id": "col_meta_recoded", "task": "metadata_recoding"}'

Families (ZADBI.xlsx) [V2 resource ID = 678] ---> CCP only
php update_resources/connectors/resource_utility.php _ '{"resource_id": "678_meta_recoded", "task": "metadata_recoding"}'

Eastfield College Scanning Electron Microscope Lab (ECSEML)
php update_resources/connectors/resource_utility.php _ '{"resource_id": "ECSEML_meta_recoded", "task": "metadata_recoding"}'

Freshwater and Marine Image Bank
php update_resources/connectors/resource_utility.php _ '{"resource_id": "fwater_marine_image_bank_meta_recoded", "task": "metadata_recoding"}'

snapshot circa Nov. 2015 (CCP and occurrence2MoF)
php update_resources/connectors/resource_utility.php _ '{"resource_id": "circa_meta_recoded", "task": "metadata_recoding"}'
circa_meta_recoded	Thu 2021-01-21 02:30:10 AM	{"agent.tab":1, "measurement_or_fact.tab":199328, "media_resource.tab":248157, "occurrence_specific.tab":28136, "taxon.tab":30629, "time_elapsed":{"sec":192.71, "min":3.21, "hr":0.05}}

-------------------------- END of Unrecognized_fields --------------------------

-------------------------- START MoF child records fixing --------------------------
php update_resources/connectors/resource_utility.php _ '{"resource_id": "Plant_Growth_Form", "task": "fix_MoF_child_records"}'
-------------------------- END MoF child records fixing --------------------------



201	                Wed 2020-10-14 02:15:39 PM	{"MoF":195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":518.17, "min":8.640000000000001, "hr":0.14}}
201_meta_recoded	Thu 2020-10-29 10:54:43 AM	{"MoF":148096, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":216.07, "min":3.6, "hr":0.06}}
less MoF is expected for 201_meta_recoded
201	                Tue 2020-12-01 09:56:56 PM	{"MoF":195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":503.2, "min":8.390000000000001, "hr":0.14}}
201_meta_recoded	Tue 2020-12-01 10:00:43 PM	{"MoF":148096, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":226.54, "min":3.78, "hr":0.06}}
201_meta_recoded_2	Thu 2021-01-21 10:07:44 AM	{"agent.tab":29, "measurement_or_fact.tab":290917, "media_resource.tab":204028, "occurrence_specific.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":216.62, "min":3.61, "hr":0.06}}


726	            Thursday 2019-12-05 09:09:30 AM	{"MoF":21485, "occurrence.tab":2838, "taxon.tab":968, "time_elapsed":{"sec":17.5,"min":0.29,"hr":0}}
726_meta_recoded	Thu 2020-10-29 11:44:26 AM	{"MoF":21485, "occurrence.tab":2838, "taxon.tab":968, "time_elapsed":{"sec":15.11, "min":0.25, "hr":0}}

770	                Tue 2020-09-15 09:20:16 AM	{"MoF":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":false}
770_meta_recoded	Wed 2020-10-28 09:37:23 AM	{"MoF":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":{"sec":8.01, "min":0.13, "hr":0}}

770	                Wed 2020-12-02 01:13:05 AM	{"MoF":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":false}
770_meta_recoded	Wed 2020-12-02 01:13:13 AM	{"MoF":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":{"sec":7.83, "min":0.13, "hr":0}}

natdb	                Fri 2020-07-17 11:24:08 AM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":293.77, "min":4.9, "hr":0.08}}
natdb_meta_recoded	    Wed 2020-10-28 09:43:50 AM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":82.73, "min":1.38, "hr":0.02}}
natdb_meta_recoded_1	Thu 2020-11-12 08:42:00 AM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":84.57, "min":1.41, "hr":0.02}}
natdb_meta_recoded	    Thu 2020-11-12 08:43:21 AM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":80.65, "min":1.34, "hr":0.02}}

natdb	                Tue 2020-12-01 10:00:47 PM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":312.39, "min":5.21, "hr":0.09}}
natdb_meta_recoded_1	Tue 2020-12-01 10:02:15 PM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":87.87, "min":1.46, "hr":0.02}}
natdb_meta_recoded	    Tue 2020-12-01 10:03:40 PM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":84.94, "min":1.42, "hr":0.02}}

natdb	                Wed 2021-02-24 10:30:06 AM	{"MoF":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":314.37, "min":5.24, "hr":0.09}}
natdb_meta_recoded_1	Wed 2021-02-24 10:31:57 AM	{"MoF":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":111.05, "min":1.85, "hr":0.03}}
natdb_meta_recoded	    Wed 2021-02-24 10:33:41 AM	{"MoF":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":103.38, "min":1.72, "hr":0.03}}


copepods	            Thu 2019-07-11 08:30:46 AM	{"MoF":21345,"occurrence.tab":18259,"reference.tab":925,"taxon.tab":2644}
copepods_meta_recoded	Wed 2020-10-28 09:47:22 AM	{"MoF":21345, "occurrence_specific.tab":18259, "reference.tab":925, "taxon.tab":2644, "time_elapsed":{"sec":21.39, "min":0.36, "hr":0.01}}

copepods	            Wed 2021-02-24 01:53:34 PM	{"MoF":21345, "occurrence.tab":18259, "reference.tab":925, "taxon.tab":2644, "time_elapsed":false}
copepods_meta_recoded	Wed 2021-02-24 01:53:56 PM	{"MoF":21345, "occurrence_specific.tab":18259, "reference.tab":925, "taxon.tab":2644, "time_elapsed":{"sec":21.44, "min":0.36, "hr":0.01}}

42	            Sun 2020-09-13 04:41:23 PM	{"agent.tab":146, "MoF":177712, "media_resource.tab":135702, "occurrence_specific.tab":161031, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":7343.42, "min":122.39, "hr":2.04}}
42_meta_recoded	Thu 2020-10-29 12:22:42 PM	{"agent.tab":146, "MoF":177712, "media_resource.tab":135702, "occurrence_specific.tab":161031, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":313.42, "min":5.22, "hr":0.09}}

42	            Wed 2020-12-02 12:38:02 AM	{"agent.tab":146, "MoF":165551, "media_resource.tab":135702, "occurrence_specific.tab":148873, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":7330.38, "min":122.17, "hr":2.04}}
42_meta_recoded	Wed 2020-12-02 12:42:55 AM	{"agent.tab":146, "MoF":165551, "media_resource.tab":135702, "occurrence_specific.tab":148873, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":291.26, "min":4.85, "hr":0.08}}

griis	            Wed 2020-10-28 02:09:49 AM	{"MoF":85499, "occurrence_specific.tab":57655, "taxon.tab":14891, "time_elapsed":{"sec":1007.65, "min":16.79, "hr":0.28}}
griis_meta_recoded	Mon 2020-11-02 08:36:01 AM	{"MoF":85499, "occurrence_specific.tab":57655, "taxon.tab":14891, "time_elapsed":{"sec":57.34, "min":0.96, "hr":0.02}}

griis	            Tue 2020-12-01 10:13:29 PM	{"MoF":85499, "occurrence_specific.tab":57655, "taxon.tab":14891, "time_elapsed":{"sec":1001.8, "min":16.7, "hr":0.28}}
griis_meta_recoded	Tue 2020-12-01 10:14:28 PM	{"MoF":85499, "occurrence_specific.tab":57655, "taxon.tab":14891, "time_elapsed":{"sec":59.3, "min":0.99, "hr":0.02}}

cotr	            Sat 2020-10-10 06:43:23 AM	{"MoF":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":82.14, "min":1.37, "hr":0.02}}
cotr_meta_recoded_1	Wed 2020-11-04 05:27:50 AM	{"MoF":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":53.87, "min":0.9, "hr":0.01}}
cotr_meta_recoded	Wed 2020-11-04 05:28:32 AM	{"MoF":52298, "occurrence.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":41.62, "min":0.69, "hr":0.01}}

cotr	            Tue 2020-12-01 09:58:59 PM	{"MoF":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":73.78, "min":1.23, "hr":0.02}}
cotr_meta_recoded_1	Tue 2020-12-01 09:59:45 PM	{"MoF":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":45.34, "min":0.76, "hr":0.01}}
cotr_meta_recoded	Tue 2020-12-01 10:00:30 PM	{"MoF":52298, "occurrence.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":45.16, "min":0.75, "hr":0.01}}

cotr	            Wed 2021-02-24 01:50:07 PM	{"MoF":56481, "occurrence_specific.tab":33335, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":72.63, "min":1.21, "hr":0.02}}
cotr_meta_recoded_1	Wed 2021-02-24 01:50:50 PM	{"MoF":56481, "occurrence_specific.tab":33335, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":42.93, "min":0.72, "hr":0.01}}
cotr_meta_recoded	Wed 2021-02-24 01:51:33 PM	{"MoF":52131, "occurrence.tab":33335, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":43.32, "min":0.72, "hr":0.01}}
cotr_meta_recoded	Wed 2021-11-10 06:44:25 AM	{"MoF":52131, "occurrence.tab":33335, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":37.41, "min":0.62, "hr":0.01}}
cotr_meta_recoded	Wed 2021-11-17 08:35:13 AM	{"MoF":52131, "occurrence.tab":33335, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":36.78, "min":0.61, "hr":0.01}}
bunch of MoF cols moved as child rows in MoF
cotr_meta_recoded_final	Wed 2021-11-17 08:35:48 {"MoF":54485, "occurrence.tab":33335, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":34.67, "min":0.58, "hr":0.01}}
HomeRange removed in MoF
cotr_meta_recoded	Mon 2021-11-22 10:29:04 AM	{"MoF":50654, "occurrence.tab":31858, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":35.93, "min":0.6, "hr":0.01}}
cotr_meta_recoded_final	Mon 2021-11-22 10:29:37 {"MoF":53008, "occurrence.tab":31858, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":33.47, "min":0.56, "hr":0.01}}
filled missing statisticalMethod values
cotr_meta_recoded	Tue 2021-11-23 11:33:32 PM	{"MoF":50654, "occurrence.tab":31858, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":39.98, "min":0.67, "hr":0.01}}
cotr_meta_recoded_final	Tue 2021-11-23 11:34:15 {"MoF":53008, "occurrence.tab":31858, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":43.53, "min":0.73, "hr":0.01}}
cotr_meta_recoded_final	Thu 2022-09-29 12:00:02 {"MoF":53008, "occurrence.tab":31858, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":36, "min":0.6, "hr":0.01}}

727	                Fri 2020-09-11 12:40:30 AM	{"agent.tab":1, "MoF":581778, "media_resource.tab":5, "occurrence_specific.tab":636468, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
727_meta_recoded	Mon 2020-11-02 08:59:01 AM	{"agent.tab":1, "MoF":581778, "media_resource.tab":5, "occurrence_specific.tab":636468, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":{"sec":524.71, "min":8.75, "hr":0.15}}
727	                Tue 2020-12-01 10:23:40 PM	{"agent.tab":1, "MoF":581779, "media_resource.tab":5, "occurrence_specific.tab":636469, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
727_meta_recoded	Tue 2020-12-01 10:32:27 PM	{"agent.tab":1, "MoF":581779, "media_resource.tab":5, "occurrence_specific.tab":636469, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":{"sec":526.2, "min":8.77, "hr":0.15}}
refreshed, lookup cache expired at this point: re-harvested
727	                Wed 2021-01-27 08:35:54 PM	{"agent.tab":1, "MoF":581781, "media_resource.tab":5, "occurrence_specific.tab":636471, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
727_meta_recoded	Wed 2021-01-27 08:45:30 PM	{"agent.tab":1, "MoF":581757, "media_resource.tab":5, "occurrence_specific.tab":636471, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":{"sec":576.47, "min":9.61, "hr":0.16}}

26_meta_recoded_1	Wed 2020-11-11 08:13:07 AM	{"agent.tab":1682, "MoF.tab":3180852, "media.tab":91653, "occurrence.tab":2157834, "reference.tab":670315, "taxon.tab":367878, "vernacular_name.tab":82322, "time_elapsed":{"sec":3044.71, "min":50.75, "hr":0.85}}
26_meta_recoded	    Wed 2020-11-11 09:00:11 AM	{"agent.tab":1682, "MoF.tab":2535563, "media.tab":91653, "occurrence.tab":2157834, "reference.tab":670315, "taxon.tab":367878, "vernacular_name.tab":82322, "time_elapsed":{"sec":2824, "min":47.07, "hr":0.78}}

26	                Fri 2020-12-11 03:09:55 AM	{"agent.tab":1690, "MoF.tab":3334428, "media.tab":91778, "occurrence.tab":2167107, "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":false}
26_meta_recoded_1	Fri 2020-12-11 04:02:47 AM	{"agent.tab":1690, "MoF.tab":3190221, "media.tab":91778, "occurrence.tab":2167107, "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":{"sec":3082.64, "min":51.38, "hr":0.86}}
26_meta_recoded	    Fri 2020-12-11 04:49:31 AM	{"agent.tab":1690, "MoF.tab":2544844, "media.tab":91778, "occurrence.tab":2167107, "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":{"sec":2803.96, "min":46.73, "hr":0.78}}

707	            Tuesday 2020-01-28 08:46:58 AM	{"MoF.tab":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":34.47,"min":0.57,"hr":0.01}}
707_meta_recoded	Wed 2020-11-11 08:09:23 AM	{"MoF.tab":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":16.94, "min":0.28, "hr":0}}
707_meta_recoded	Thu 2020-11-12 08:41:47 AM	{"MoF.tab":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":12.84, "min":0.21, "hr":0}}

707	            Wed 2020-12-02 01:07:42 AM	{"MoF":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":9.39, "min":0.16, "hr":0}}
707_meta_recodedWed 2020-12-02 01:07:51 AM	{"MoF":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":9.09, "min":0.15, "hr":0}}
707	            Wed 2020-12-02 01:09:05 AM	{"MoF":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":9, "min":0.15, "hr":0}}
707_meta_recodedWed 2020-12-02 01:09:14 AM	{"MoF":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":9.06, "min":0.15, "hr":0}}


692_meta_recoded	Wed 2020-10-21 11:28:41 AM	{"MoF":3849108, "occurrence_specific.tab":486561, "taxon.tab":162187, "time_elapsed":{"sec":1359.92, "min":22.67, "hr":0.38}}

692	                Tue 2020-12-01 10:13:22 PM	{"MoF":1924554, "occurrence.tab":486561, "taxon.tab":162187, "time_elapsed":{"sec":1129.53, "min":18.83, "hr":0.31}}
692_meta_recoded	Tue 2020-12-01 10:37:34 PM	{"MoF":3849108, "occurrence_specific.tab":486561, "taxon.tab":162187, "time_elapsed":{"sec":1451.08, "min":24.18, "hr":0.4}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
$task = $param['task'];
print_r($param);

if($resource_id == 'col_meta_recoded') ini_set('memory_limit','15096M'); //15096M

if($task == 'remove_taxa_without_MoF') {
    if(Functions::is_production()) $dwca_file = '/u/scripts/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
    else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/'.$resource_id.'.tar.gz';
    // /* ---------- customize here ----------
    if($resource_id == '617_final') $resource_id = "wikipedia_en_traits";
    else exit("\nERROR: [$task] resource_id not yet initialized. Will terminate.\n");
    // ----------------------------------------*/
}
elseif($task == 'report_4_Wikipedia_EN_traits') { //for Jen: https://eol-jira.bibalex.org/browse/DATA-1858?focusedCommentId=65155&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65155
    $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/wikipedia_en_traits.tar.gz';
    // $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/708.tar.gz'; //testing investigation only
}
elseif($task == 'add_canonical_in_taxa') {
    if($resource_id == 'WoRMS2EoL_zip') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/other_files/WoRMS/WoRMS2EoL.zip";
                                        // $dwca_file = "http://www.marinespecies.org/export/eol/WoRMS2EoL.zip";
        else                            $dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
    }
    else exit("\nERROR: [$task] resource_id not yet initialized. Will terminate.\n");
}
elseif($task == 'change_measurementIDs') {
    if($resource_id == '26_ENV_final') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/26_ENV.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/26_ENV.tar.gz";
    }
}

elseif($task == 'metadata_recoding') {
    if($resource_id == '692_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/692.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/692.tar.gz";
    }
    elseif($resource_id == '201_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/201.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/201.tar.gz";
    }
    elseif($resource_id == '201_meta_recoded_2') {
        $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/201_meta_recoded.tar.gz";
    }

    elseif($resource_id == '726_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/726.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/726.tar.gz";
    }
    elseif($resource_id == 'griis_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/griis.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/griis.tar.gz";
    }
    elseif($resource_id == '770_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/770.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/770.tar.gz";
    }

    elseif($resource_id == 'natdb_meta_recoded_1') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/natdb.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/natdb.tar.gz";
    }
    elseif($resource_id == 'natdb_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/natdb_meta_recoded_1.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/natdb_meta_recoded_1.tar.gz";
    }

    elseif($resource_id == 'copepods_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/copepods.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/copepods.tar.gz";
    }
    elseif($resource_id == '42_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/42.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/42.tar.gz";
    }
    elseif($resource_id == '727_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/727.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/727.tar.gz";
    }

    elseif($resource_id == '707_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/707.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/707.tar.gz";
    }
    
    elseif($resource_id == 'try_dbase_2024_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/try_dbase_2024.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/try_dbase_2024.tar.gz";
    }



    elseif($resource_id == 'cotr_meta_recoded_1') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/cotr.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/cotr.tar.gz";
    }
    elseif($resource_id == 'cotr_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/cotr_meta_recoded_1.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/cotr_meta_recoded_1.tar.gz";
    }
    elseif($resource_id == 'cotr_meta_recoded_final') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/cotr_meta_recoded.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/cotr_meta_recoded.tar.gz";
    }

    elseif($resource_id == 'test_meta_recoded') { //task_45: no actual resource atm.
        $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/test_mUnit_sMethod.zip";
    }
    elseif($resource_id == 'test2_meta_recoded') { //task_45: first client is WorMS (26).
        $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/test_mUnit_sMethod_asChildInMoF.zip";
    }
    elseif($resource_id == 'test3_meta_recoded') { //task_67: first client is WorMS (26).
        $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/test_lifeStage_sex_asChildInMoF.zip";
    }

    elseif($resource_id == '26_meta_recoded_1') { //task_45: statisticalMethod | measurementUnit
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/26.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/26.tar.gz";
    }
    elseif($resource_id == '26_meta_recoded') { //task_67: lifeStage | sex
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/26_meta_recoded_1.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/26_meta_recoded_1.tar.gz";
    }

    // /* Unrecognized_fields
    elseif($resource_id == 'Cicadellinae_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/e4a7239b-7297-4a75-9fe9-1f5cff5e20d7/resource/7408693e-094a-4335-a0c9-b114d7dc64d3/download/archive.zip";
    }
    elseif($resource_id == 'Deltocephalinae_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/e4a7239b-7297-4a75-9fe9-1f5cff5e20d7/resource/5d6f7139-0d1f-4d9f-adb0-15ec7a1ea16e/download/archive.zip";
    }
    elseif($resource_id == 'Appeltans_et_al_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/b5b2b058-8b2c-4a2d-98f9-f4f5bba77ae5/resource/d9adfd62-01d7-41e1-a125-34130ce33cf4/download/archive.zip";
    }
    elseif($resource_id == '168_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/168.tar.gz";
    }
    elseif($resource_id == '200_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/200.tar.gz";
    }
    elseif($resource_id == 'Braconids_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/1838b614-4d4e-4c57-a0c0-4ac18c825f5f/resource/3c38b485-e5dc-44de-af7a-88d2f74e616c/download/archive.zip";
    }
    elseif($resource_id == 'Carrano_2006_meta_recoded') { //task_move_col_in_occurrence_to_MoF_row_with_MeasurementOfTaxon_false
        $dwca_file = "https://opendata.eol.org/dataset/e33a9544-1aa1-4e50-9efa-c04ef4098d57/resource/002cd101-cfa8-4b1c-a4e6-e4e45d00c3bc/download/archive.zip";
    }
    elseif($resource_id == 'col_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = CONTENT_RESOURCE_LOCAL_PATH."/col.tar.gz";
    }
    elseif($resource_id == '678_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/32fe565d-40d8-4903-b7ab-7fc778b9b396/resource/3b732b90-113d-4141-a9c2-588f3c3f3b95/download/archive.zip";
    }
    elseif($resource_id == 'ECSEML_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/b5e9a5d8-174e-4213-82f6-052a5cc46412/resource/ecd9ae2a-187c-427a-8b73-c05e8076008d/download/archive.zip";
    }
    elseif($resource_id == 'fwater_marine_image_bank_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/a4408d81-175e-4d0e-9111-c2d4742ebd9b/resource/194f10d4-3187-4be5-ac49-4518f57a1ff2/download/archive.zip";
    }
    elseif($resource_id == 'plant_growth_form_meta_recoded') { //task_move_col_in_occurrence_to_MoF_row_with_MeasurementOfTaxon_false
        exit("\nThis was fixed already as of Sep 29, 2022 (fix_MoF_child_records). Unless will be reported with new issues.\n");
        $dwca_file = "https://opendata.eol.org/dataset/f86b9ed4-770c-4d15-af55-46cfd86a3f39/resource/7a6fb0ff-5f99-47ee-8177-78c69a6b9c59/download/plantgrowthformmetarecoded.tar.gz";
        $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/plantgrowthformmetarecoded.tar.gz";
        // https://opendata.eol.org/dataset/f86b9ed4-770c-4d15-af55-46cfd86a3f39/resource/c89bb549-12de-437d-821e-fe92c2829854/download/copy-of-new-full-habit-sheet.xlsx
        // https://opendata.eol.org/dataset/f86b9ed4-770c-4d15-af55-46cfd86a3f39/resource/8f244e41-2ed8-48dd-9dd0-8e1338d4d77b/download/nmnhplantgrowthformdata.xlsx
        // https://opendata.eol.org/dataset/f86b9ed4-770c-4d15-af55-46cfd86a3f39/resource/7a6fb0ff-5f99-47ee-8177-78c69a6b9c59/download/plantgrowthformmetarecoded.tar.gz
    }
    elseif($resource_id == 'circa_meta_recoded') { //CCP and occurrence2MoF
        // $dwca_file = "https://opendata.eol.org/dataset/b4a77ad4-7f80-434f-a68f-aaabdfda3bb8/resource/9bc2fcb5-61c9-44d1-a691-df5287218ed8/download/archive.zip";
        $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/local_circa.tar.gz";
        exit("\nObsolete anyway. Replaced by [https://opendata.eol.org/dataset/harvard-museum-of-comparative-zoology/resource/c70577a3-7ba7-472f-b3de-bf3043beebfd]\n");
    }
    // */
    
    else exit("\nERROR: [$task] resource_id not yet initialized. Will terminate.\n");
}

elseif($task == 'fix_MoF_child_records') { // 1st client for this task
    if($resource_id == 'Plant_Growth_Form') {
        exit("\nThis was fixed already as of Sep 29, 2022. Unless will be reported with new issues.\n");
        if(Functions::is_production())  $dwca_file = "https://opendata.eol.org/dataset/f86b9ed4-770c-4d15-af55-46cfd86a3f39/resource/7a6fb0ff-5f99-47ee-8177-78c69a6b9c59/download/plantgrowthformmetarecoded.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/plantgrowthformmetarecoded.tar.gz";
        /* I just ran this locally. And uploaded the local result (Plant_Growth_Form.tar.gz) to: https://opendata.eol.org/dataset/habitdata/resource/7a6fb0ff-5f99-47ee-8177-78c69a6b9c59
        Interestingly CKAN renamed the filename to: "plantgrowthform.tar.gz".
        */
    }
    else exit("\nresource_id not initialized for this task [$task].\n");
    $resource_id .= '_fxMoFchild';
}

else exit("\nERROR: task not yet initialized. Will terminate.\n");
process_resource_url($dwca_file, $resource_id, $task, $timestart);

// /* add testing for undefined childen in MoF - utility only
if(in_array($resource_id, array('26_ENV_final', 'cotr_meta_recoded_final')) || 
    in_array($task, array('fix_MoF_child_records', 'metadata_recoding'))) {
    run_utility($resource_id);
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI
}
// */

if($task == 'fix_MoF_child_records') {
    $source         = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz";
    $destination    = str_replace("_fxMoFchild", "", $source);
    if(Functions::file_rename($source, $destination)) {
        echo "\nFinal step (rename) OK.\n";
        echo "\n - source: [$source]";
        echo "\n - destination: [$destination]\n";
    }
    else echo "\nERROR: Final step (rename), unsuccessful [$destination].\n";
}

/* Start Functions */
function process_resource_url($dwca_file, $resource_id, $task, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    
    if($task == 'remove_taxa_without_MoF') {
        if(in_array($resource_id, array('wikipedia_en_traits'))) {
            $preferred_rowtypes = array();
            $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
            /* These below will be processed in ResourceUtility.php which will be called from DwCA_Utility.php
            http://rs.tdwg.org/dwc/terms/taxon
            */
        }
    }
    elseif($task == 'change_measurementIDs') { //1st client is WoRMS: https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=66426&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66426
        if(in_array($resource_id, array('26_ENV_final'))) {
            $preferred_rowtypes = array();
            $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact');
            /* These below will be processed in Change_measurementIDs.php which will be called from DwCA_Utility.php
            http://rs.tdwg.org/dwc/terms/measurementorfact
            */
        }
    }
    elseif($task == 'report_4_Wikipedia_EN_traits') {
        $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact'); //best to set this to array() and just set $excluded_rowtypes to taxon
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact');
    }
    elseif($task == 'add_canonical_in_taxa') {
        /* working but not needed for DH purposes
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        */
        $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
    }

    elseif($task == 'fix_MoF_child_records') {
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact');
        /* These below will be processed in FixMoFChildRecordsAPI.php which will be called from DwCA_Utility.php
        http://rs.tdwg.org/dwc/terms/measurementorfact
        */
    }

    elseif($task == 'metadata_recoding') {
        $preferred_rowtypes = array();
        if(in_array($resource_id, array('201_meta_recoded', '726_meta_recoded', 'cotr_meta_recoded', 'test2_meta_recoded',
                                        '26_meta_recoded_1', 'cotr_meta_recoded_final'))) {
            $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact'); //means occurrence tab is just carry-over
        }

        elseif(in_array($resource_id, array('col_meta_recoded'))) $excluded_rowtypes = array('http://eol.org/schema/media/document',
            'http://rs.tdwg.org/dwc/terms/taxon');

        //CCP and missing measurementID
        elseif(in_array($resource_id, array('Cicadellinae_meta_recoded', 'Deltocephalinae_meta_recoded', 'Appeltans_et_al_meta_recoded',
            '200_meta_recoded', 'Braconids_meta_recoded'))) {
            $excluded_rowtypes = array('http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        }

        elseif(in_array($resource_id, array('168_meta_recoded'))) {
            $excluded_rowtypes = array('http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
            $excluded_rowtypes[] = 'http://rs.tdwg.org/dwc/terms/taxon'; //per DATA-1878
        }
        
        //CCP only
        elseif(in_array($resource_id, array('678_meta_recoded', 'ECSEML_meta_recoded', 'fwater_marine_image_bank_meta_recoded'))) {
            $excluded_rowtypes = array('http://eol.org/schema/media/document');
        }
        //occurrence2MoF only
        elseif(in_array($resource_id, array('Carrano_2006_meta_recoded', 'plant_growth_form_meta_recoded'))) {
            $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/occurrence');
        }
            
        //CCP and occurrence2MoF
        elseif(in_array($resource_id, array('circa_meta_recoded', '201_meta_recoded_2'))) {
            $excluded_rowtypes = array('http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/occurrence');
        }
        
        else $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        /* works but just testing. COMMENT IN REAL OPERATION
        if($resource_id == '168_meta_recoded') $excluded_rowtypes[] = 'http://eol.org/schema/agent/agent';
        */
    }
    
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    
    if(in_array($resource_id, array('26_ENV_final', 'cotr_meta_recoded_final')) || 
        in_array($task, array('fix_MoF_child_records', 'metadata_recoding'))) {
        Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd row 'false' means not delete working dir
    }
    else Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //rest goes here
}
function run_utility($resource_id)
{
    // /* utility ==========================
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();

    if(in_array($resource_id, array('201_meta_recoded_2'))) $MoF_file = 'measurement_or_fact.tab';
    else                                                    $MoF_file = 'measurement_or_fact_specific.tab'; //rest goes here
    
    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'parentMeasurementID', $MoF_file);
    echo "\nTotal undefined parents MoF [$resource_id]: " . count($undefined_parents)."\n";
    // ===================================== */
}
?>