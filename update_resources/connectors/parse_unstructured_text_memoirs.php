<?php
namespace php_active_record;
/*
https://eol-jira.bibalex.org/browse/DATA-1858: Apply environmental tagging to English Wikipedia
https://eol-jira.bibalex.org/browse/DATA-1870: textmined habitat for additional resources
https://eol-jira.bibalex.org/browse/DATA-1877: textmining more unstructured text
https://eol-jira.bibalex.org/browse/DATA-1887: Memoirs of the American Entomological Society
https://eol-jira.bibalex.org/browse/DATA-1890: North American Flora
https://eol-jira.bibalex.org/browse/DATA-1891: new patterns for all textmined resources: generalized association data pattern

DATA-1877: textmining more unstructured text
118935	    Wed 2021-07-07 01:38:44 AM	{               "media.tab":1309,                     "taxon.tab":1308, "time_elapsed":{"sec":1.29, "min":0.02, "hr":0}}
118935_ENV	Wed 2021-07-07 01:41:51 AM	{"MoF.tab":1479,                    "occur.tab":1479, "taxon.tab":1308, "time_elapsed":{"sec":66.67, "min":1.11, "hr":0.02}}

------------------------------------------------------------
120081	Wed 2021-07-07 01:42:14 AM	    {               "media.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
120081_ENV	Wed 2021-07-07 01:44:41 AM	{"MoF.tab":523, "media.tab":95, "occur.tab":523, "taxon.tab":95, "time_elapsed":{"sec":26.99, "min":0.45, "hr":0.01}}
------------------------------------------------------------
120082	Wed 2021-07-07 01:45:01 AM	    {              "media.tab":25,                 "taxon.tab":25, "time_elapsed":{"sec":0.34, "min":0.01, "hr":0}}
120082_ENV	Wed 2021-07-07 01:47:37 AM	{"MoF.tab":61, "media.tab":25, "occur.tab":61, "taxon.tab":25, "time_elapsed":{"sec":36.02, "min":0.6, "hr":0.01}}
------------------------------------------------------------
118986	Wed 2021-07-07 01:48:10 AM	    {               "media.tab":41,                  "taxon.tab":41, "time_elapsed":{"sec":2.33, "min":0.04, "hr":0}}
118986_ENV	Wed 2021-07-07 01:50:39 AM	{"MoF.tab":511, "media.tab":41, "occur.tab":511, "taxon.tab":41, "time_elapsed":{"sec":28.31, "min":0.47, "hr":0.01}}
------------------------------------------------------------
118920	Wed 2021-07-07 01:50:55 AM	    {              "media.tab":27,                 "taxon.tab":27, "time_elapsed":{"sec":0.34, "min":0.01, "hr":0}}
118920_ENV	Wed 2021-07-07 01:53:15 AM	{"MoF.tab":73, "media.tab":27, "occur.tab":73, "taxon.tab":27, "time_elapsed":{"sec":20.2, "min":0.34, "hr":0.01}}
------------------------------------------------------------
120083	Wed 2021-07-07 01:54:01 AM	    {               "media.tab":379,                  "taxon.tab":294, "time_elapsed":{"sec":0.57, "min":0.01, "hr":0}}
120083_ENV	Wed 2021-07-07 01:56:33 AM	{"MoF.tab":752, "media.tab":186, "occur.tab":752, "taxon.tab":294, "time_elapsed":{"sec":31.82, "min":0.53, "hr":0.01}}
------------------------------------------------------------
118237	Wed 2021-07-07 01:57:24 AM	{"media.tab":46, "taxon.tab":33, "time_elapsed":{"sec":0.37, "min":0.01, "hr":0}}
118237_ENV	Wed 2021-07-07 02:06:23 AM	{"MoF.tab":596, "media.tab":46, "occur.tab":596, "taxon.tab":33, "time_elapsed":{"sec":418.59, "min":6.98, "hr":0.12}}
------------------------------------------------------------
MoftheAES_resources	Wed 2021-07-07 07:02:49 AM	{"MoF.tab":3995, "media.tab":420, "occur.tab":3995, "taxon.tab":1823, "time_elapsed":{"sec":67.24, "min":1.12, "hr":0.02}}

Perfect addition of stats: sum-up OK
118935_ENV	Wed 2021-07-07 01:41:51 AM	{"MoF.tab":1479,                    "occur.tab":1479, "taxon.tab":1308, "time_elapsed":{"sec":66.67, "min":1.11, "hr":0.02}}
120081_ENV	Wed 2021-07-07 01:44:41 AM	{"MoF.tab":523,   "media.tab":95,   "occur.tab":523,  "taxon.tab":95, "time_elapsed":{"sec":26.99, "min":0.45, "hr":0.01}}
120082_ENV	Wed 2021-07-07 01:47:37 AM	{"MoF.tab":61,    "media.tab":25,   "occur.tab":61,   "taxon.tab":25, "time_elapsed":{"sec":36.02, "min":0.6, "hr":0.01}}
118986_ENV	Wed 2021-07-07 01:50:39 AM	{"MoF.tab":511,   "media.tab":41,   "occur.tab":511,  "taxon.tab":41, "time_elapsed":{"sec":28.31, "min":0.47, "hr":0.01}}
118920_ENV	Wed 2021-07-07 01:53:15 AM	{"MoF.tab":73,    "media.tab":27,   "occur.tab":73,   "taxon.tab":27, "time_elapsed":{"sec":20.2, "min":0.34, "hr":0.01}}
120083_ENV	Wed 2021-07-07 01:56:33 AM	{"MoF.tab":752,   "media.tab":186,  "occur.tab":752,  "taxon.tab":294, "time_elapsed":{"sec":31.82, "min":0.53, "hr":0.01}}
118237_ENV	Wed 2021-07-07 02:06:23 AM	{"MoF.tab":596,   "media.tab":46,   "occur.tab":596,  "taxon.tab":33, "time_elapsed":{"sec":418.59, "min":6.98, "hr":0.12}}
MoftheAES_resources	Wed 2021-07-07 07:02{"MoF.tab":3995,  "media.tab":420,  "occur.tab":3995, "taxon.tab":1823, "time_elapsed":{"sec":67.24, "min":1.12, "hr":0.02}}                                                   
------------------------------------------------------------ This didn't sum-up well:
MoftheAES	Tue 2021-07-06 03:12:33 AM	{                                        "media.tab":1349,                                "taxon.tab":1263, "time_elapsed":{"sec":26.05, "min":0.43, "hr":0.01}}
MoftheAES_ENV	Tue 2021-07-06 03:57:26 {"MoF.tab":2915,"media.tab":374, "occur.tab":2915, "taxon.tab":1263, "time_elapsed":{"sec":2631.55, "min":43.86, "hr":0.73}}
MoftheAES	Tue 2021-07-06 07:28:58 AM	{                                        "media.tab":1349,                                "taxon.tab":1263, "time_elapsed":{"sec":1.29, "min":0.02, "hr":0}}
MoftheAES_ENV	Tue 2021-07-06 07:31:46 {"MoF.tab":2915,"media.tab":374, "occur.tab":2915, "taxon.tab":1263, "time_elapsed":{"sec":108.13, "min":1.8, "hr":0.03}}
OLD mof 3369    media 374   taxon 1790
NEW mof 2904    media 374   taxon 1263
------------------------------------------------------------ others MotAES
30355	Tue 2021-07-13 11:16:07 AM	{"media_resource.tab":2625,                                               "taxon.tab":2622, "time_elapsed":{"sec":1.99, "min":0.03, "hr":0}}
30355_ENV	Tue 2021-07-13 11:44:07 {"measurement_or_fact_specific.tab":2566, "occurrence_specific.tab":2566, "taxon.tab":2622, "time_elapsed":{"sec":1559.58, "min":25.99, "hr":0.43}}

27822	Thu 2021-07-15 10:14:21 AM	{                                        "media_resource.tab":85,                                 "taxon.tab":71, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
27822_ENV	Thu 2021-07-15 10:16:52 {"measurement_or_fact_specific.tab":115, "media_resource.tab":85, "occurrence_specific.tab":115,  "taxon.tab":71, "time_elapsed":{"sec":30.27, "min":0.5, "hr":0.01}}

30354	Thu 2021-07-15 10:14:35 AM	{                                       "media_resource.tab":87,                               "taxon.tab":87, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
30354_ENV	Thu 2021-07-15 10:17:08 {"measurement_or_fact_specific.tab":85, "media_resource.tab":87, "occurrence_specific.tab":85, "taxon.tab":87, "time_elapsed":{"sec":33.06, "min":0.55, "hr":0.01}}

119035	Tue 2021-07-20 05:22:11 AM	{"media_resource.tab":56, "taxon.tab":56, "time_elapsed":{"sec":0.39, "min":0.01, "hr":0}}
119035_ENV	Tue 2021-07-20 05:24:23 AM	{"measurement_or_fact_specific.tab":169, "media_resource.tab":56, "occurrence_specific.tab":169, "taxon.tab":56, "time_elapsed":{"sec":12.3, "min":0.21, "hr":0}}

118936	    Tue 2021-07-20 05:21:53 AM	{              "media_resource.tab":14,                                 "taxon.tab":14, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118936_ENV	Tue 2021-07-20 05:24:07 AM	{"MoF.tab":63, "media_resource.tab":14, "occurrence_specific.tab":63,   "taxon.tab":14, "time_elapsed":{"sec":14.65, "min":0.24, "hr":0}}
118936	    Tue 2021-09-28 08:16:44 AM	{              "media_resource.tab":14,                                 "taxon.tab":14, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
118936_ENV	Tue 2021-09-28 08:19:18 AM	{"MoF.tab":28, "media_resource.tab":14, "occurrence_specific.tab":28,   "taxon.tab":14, "time_elapsed":{"sec":33.39, "min":0.56, "hr":0.01}}

118946	Tue 2021-07-20 05:22:44 AM	{"media_resource.tab":102, "taxon.tab":101, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118946_ENV	Tue 2021-07-20 05:24:58 AM	{"measurement_or_fact_specific.tab":639, "media_resource.tab":102, "occurrence_specific.tab":639, "taxon.tab":101, "time_elapsed":{"sec":14.1, "min":0.24, "hr":0}}

118950	Tue 2021-07-20 05:22:18 AM	{                                        "media_resource.tab":55,                                "taxon.tab":55, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
118950_ENV	Tue 2021-07-20 05:24:31 {"measurement_or_fact_specific.tab":151, "media_resource.tab":55, "occurrence_specific.tab":151, "taxon.tab":55, "time_elapsed":{"sec":12.86, "min":0.21, "hr":0}}

120602	Wed 2021-07-21 11:08:20 AM	{                                          "media_resource.tab":20, "taxon.tab":20, "time_elapsed":{"sec":0.33, "min":0.01, "hr":0}}
120602_ENV	Wed 2021-07-21 11:10:48 {"measurement_or_fact_specific.tab":4, "occurrence_specific.tab":4, "taxon.tab":20, "time_elapsed":{"sec":28.63, "min":0.48, "hr":0.01}}

119187	Mon 2021-07-26 10:01:01 AM	{                                        "media_resource.tab":40,                                "taxon.tab":31, "time_elapsed":{"sec":10.09, "min":0.17, "hr":0}}
119187_ENV	Mon 2021-07-26 10:03:22 {"measurement_or_fact_specific.tab":156, "media_resource.tab":40, "occurrence_specific.tab":156, "taxon.tab":31, "time_elapsed":{"sec":21.59, "min":0.36, "hr":0.01}}

118941	Tue 2021-07-27 10:30:58 AM	{"media_resource.tab":94, "taxon.tab":94, "time_elapsed":{"sec":0.49, "min":0.01, "hr":0}}
118941_ENV	Tue 2021-07-27 10:43:12 AM	{"measurement_or_fact_specific.tab":351, "media_resource.tab":94, "occurrence_specific.tab":351, "taxon.tab":94, "time_elapsed":{"sec":614.26, "min":10.24, "hr":0.17}}

118978	    Tue 2021-07-27 10:54:30 AM	{               "media.tab":86,                                 "taxon.tab":82, "time_elapsed":{"sec":0.42, "min":0.01, "hr":0}}
118978_ENV	Tue 2021-07-27 10:56:44 AM	{"MoF.tab":616, "media.tab":86, "occurrence_specific.tab":616,  "taxon.tab":82, "time_elapsed":{"sec":14.12, "min":0.24, "hr":0}}
118978	    Thu 2021-07-29 08:22:33 AM	{               "media.tab":80,                                 "taxon.tab":78, "time_elapsed":{"sec":0.62, "min":0.01, "hr":0}}
118978_ENV	Thu 2021-07-29 08:24:56 AM	{"MoF.tab":617, "media.tab":80, "occurrence_specific.tab":617,  "taxon.tab":78, "time_elapsed":{"sec":22.83, "min":0.38, "hr":0.01}}
118978	    Tue 2021-09-28 08:52:45 AM	{               "media.tab":81,                                 "taxon.tab":79, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
118978_ENV	Tue 2021-09-28 08:56:28 AM	{"MoF.tab":281, "media.tab":81, "occurrence_specific.tab":281,  "taxon.tab":79, "time_elapsed":{"sec":102.67, "min":1.71, "hr":0.03}}

119188	Wed 2021-07-28 09:36:06 AM	{"media_resource.tab":182, "taxon.tab":177, "time_elapsed":{"sec":0.53, "min":0.01, "hr":0}}
119188_ENV	Wed 2021-07-28 09:38:30 AM	{"measurement_or_fact_specific.tab":890, "media_resource.tab":182, "occurrence_specific.tab":890, "taxon.tab":177, "time_elapsed":{"sec":24.02, "min":0.4, "hr":0.01}}

119520	Wed 2021-07-28 10:22:25 AM	{"media_resource.tab":675, "taxon.tab":675, "time_elapsed":{"sec":0.82, "min":0.01, "hr":0}}
119520_ENV	Wed 2021-07-28 10:25:25 AM	{"measurement_or_fact_specific.tab":2301, "media_resource.tab":675, "occurrence_specific.tab":2301, "taxon.tab":675, "time_elapsed":{"sec":60.5, "min":1.01, "hr":0.02}}

MoftheAES_resources	Thu 2021-07-29 09:06:03 AM	{"MoF.tab":12098, "media.tab":1889, "occurrence.tab":12098, "taxon.tab":5853, "time_elapsed":{"sec":118.6, "min":1.98, "hr":0.03}}
MoftheAES_resources	Mon 2021-08-09 11:10:28 AM	{"MoF.tab":12098, "media.tab":1889, "occurrence.tab":12098, "taxon.tab":5853, "time_elapsed":{"sec":115.66, "min":1.93, "hr":0.03}}
------------------------------------------------------------ North American Flora (DATA-1890) --- BHL
15423	    Tue 2021-07-13 07:19:04 {                                        "media_resource.tab":73,                                "taxon.tab":73, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
15423_ENV	Tue 2021-07-13 07:23:01 {"measurement_or_fact_specific.tab":338, "media_resource.tab":73, "occurrence_specific.tab":338, "taxon.tab":73, "time_elapsed":{"sec":115.98, "min":1.93, "hr":0.03}}
15423	    Tue 2021-09-28 05:03:49 {                                        "media_resource.tab":75,                                "taxon.tab":75, "time_elapsed":{"sec":3.2, "min":0.05, "hr":0}}
15423_ENV	Tue 2021-09-28 05:07:48 {"measurement_or_fact_specific.tab":347, "media_resource.tab":75, "occurrence_specific.tab":347, "taxon.tab":75, "time_elapsed":{"sec":118.99, "min":1.98, "hr":0.03}}
------------------------------------------------------------
91155	    Tue 2021-07-13 07:23:11 {                                        "media_resource.tab":107,                                "taxon.tab":107, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
91155_ENV	Tue 2021-07-13 07:28:06 {"measurement_or_fact_specific.tab":665, "media_resource.tab":107, "occurrence_specific.tab":665, "taxon.tab":107, "time_elapsed":{"sec":174.68, "min":2.91, "hr":0.05}}
91155	    Tue 2021-09-28 05:10:58 {                                        "media_resource.tab":100,                                "taxon.tab":100, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
91155_ENV	Tue 2021-09-28 05:13:04 {"measurement_or_fact_specific.tab":625, "media_resource.tab":100, "occurrence_specific.tab":625, "taxon.tab":100, "time_elapsed":{"sec":5.94, "min":0.1, "hr":0}}

15427	    Tue 2021-08-03 10:10:02 {                                        "media_resource.tab":152,                                "taxon.tab":152, "time_elapsed":{"sec":1.12, "min":0.02, "hr":0}}
15427_ENV	Tue 2021-08-03 10:12:20 {"measurement_or_fact_specific.tab":426, "media_resource.tab":152, "occurrence_specific.tab":426, "taxon.tab":152, "time_elapsed":{"sec":18, "min":0.3, "hr":0.01}}
15427	    Tue 2021-09-28 05:04:47 {                                        "media_resource.tab":154,                                "taxon.tab":154, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
15427_ENV	Tue 2021-09-28 05:09:13 {"measurement_or_fact_specific.tab":431, "media_resource.tab":154, "occurrence_specific.tab":431, "taxon.tab":154, "time_elapsed":{"sec":145.86, "min":2.43, "hr":0.04}}

15428	    Tue 2021-08-03 10:12:35 {                                        "media_resource.tab":190,                                "taxon.tab":190, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
15428_ENV	Tue 2021-08-03 10:14:56 {"measurement_or_fact_specific.tab":759, "media_resource.tab":190, "occurrence_specific.tab":759, "taxon.tab":190, "time_elapsed":{"sec":20.34, "min":0.34, "hr":0.01}}
15428	    Tue 2021-09-28 05:09:46 {                                        "media_resource.tab":194,                                "taxon.tab":194, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
15428_ENV	Tue 2021-09-28 05:11:50 {"measurement_or_fact_specific.tab":766, "media_resource.tab":194, "occurrence_specific.tab":766, "taxon.tab":194, "time_elapsed":{"sec":4.65, "min":0.08, "hr":0}}

91144	Tue 2021-08-03 10:15:15 PM	{                                        "media_resource.tab":192,                                "taxon.tab":192, "time_elapsed":{"sec":0.48, "min":0.01, "hr":0}}
91144_ENV	Tue 2021-08-03 10:17:34 {"measurement_or_fact_specific.tab":829, "media_resource.tab":192, "occurrence_specific.tab":829, "taxon.tab":192, "time_elapsed":{"sec":18.66, "min":0.31, "hr":0.01}}

91225	Thu 2021-08-05 12:07:49 PM	{"association.tab":4275, "occurrence.tab":4693, "taxon.tab":4859, "time_elapsed":{"sec":6.39, "min":0.11, "hr":0}}
91225	Thu 2021-08-12 05:38:11 AM	{"association.tab":4343, "occurrence.tab":4762, "taxon.tab":4932, "time_elapsed":{"sec":124.63, "min":2.08, "hr":0.03}}
91225	Mon 2021-09-27 01:12:13 PM	{"association.tab":4326, "occurrence.tab":4751, "taxon.tab":4931, "time_elapsed":{"sec":10.51, "min":0.18, "hr":0}}

91362	            Mon 2021-08-09 05:21:21 AM	{"assoc.tab":486,                                         "occur.tab":624,          "taxon.tab":656, "time_elapsed":{"sec":6.09, "min":0.1, "hr":0}}
91362_species	    Mon 2021-08-09 05:22:06 AM	{                                "media_resource.tab":56,                           "taxon.tab":56, "time_elapsed":{"sec":0.36, "min":0.01, "hr":0}}
91362_species_ENV	Mon 2021-08-09 05:24:21 AM	{                 "MoF.tab":182, "media_resource.tab":56, "occur_specific.tab":182, "taxon.tab":56, "time_elapsed":{"sec":14.46, "min":0.24, "hr":0}}
91362_resource	    Mon 2021-08-09 06:49:48 AM	{"assoc.tab":486, "MoF.tab":182, "media_resource.tab":56, "occur_specific.tab":806, "taxon.tab":684, "time_elapsed":{"sec":11.54, "min":0.19, "hr":0}}
Slight increase after a few months:
91362	            Mon 2021-09-27 10:51:47 AM	{"assoc.tab":491,                                         "occur.tab":630,          "taxon.tab":662, "time_elapsed":{"sec":5.82, "min":0.1, "hr":0}}
91362_species	    Mon 2021-09-27 10:38:46 AM	{                                "media_resource.tab":58,                           "taxon.tab":58, "time_elapsed":{"sec":0.36, "min":0.01, "hr":0}}
91362_species_ENV	Mon 2021-09-27 10:41:25 AM	{                 "MoF.tab":264, "media_resource.tab":58, "occur_specific.tab":264, "taxon.tab":58, "time_elapsed":{"sec":38.06, "min":0.63, "hr":0.01}}
91362_resource	    Mon 2021-09-27 10:17:17 PM	{"assoc.tab":491, "MoF.tab":264, "media_resource.tab":58, "occur_specific.tab":894, "taxon.tab":691, "time_elapsed":{"sec":3.75, "min":0.06, "hr":0}}

NorthAmericanFlora	Mon 2021-08-09 08:23:18 AM	{"association.tab":486, "MoF.tab":3199, "media.tab":770, "occurrence.tab":3823, "taxon.tab":1376, "time_elapsed":{"sec":37.56, "min":0.63, "hr":0.01}}

FUNGI list
15404	    Tue 2021-08-10 08:28:16 AM	{                "media_resource.tab":295,                                 "taxon.tab":289, "time_elapsed":{"sec":0.53, "min":0.01, "hr":0}}
15404_ENV	Tue 2021-08-10 08:30:34 AM	{"MoF.tab":1368, "media_resource.tab":295, "occurrence_specific.tab":1368, "taxon.tab":289, "time_elapsed":{"sec":18.79, "min":0.31, "hr":0.01}}
15404	    Mon 2021-09-27 10:15:20 AM	{                "media_resource.tab":299,                                 "taxon.tab":293, "time_elapsed":{"sec":0.62, "min":0.01, "hr":0}}
15404_ENV	Mon 2021-09-27 10:18:51 AM	{"MoF.tab":1346, "media_resource.tab":299, "occurrence_specific.tab":1346, "taxon.tab":293, "time_elapsed":{"sec":90.39, "min":1.51, "hr":0.03}}

15405	    Tue 2021-08-10 08:30:46 AM	{                "media_resource.tab":105,                                 "taxon.tab":105, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
15405_ENV	Tue 2021-08-10 08:32:59 AM	{"MoF.tab":402,  "media_resource.tab":105, "occurrence_specific.tab":402,  "taxon.tab":105, "time_elapsed":{"sec":13.38, "min":0.22, "hr":0}}
15405	    Mon 2021-09-27 10:20:22 AM	{                "media_resource.tab":105,                                 "taxon.tab":105, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
15405_ENV	Mon 2021-09-27 10:24:12 AM	{"MoF.tab":393,  "media_resource.tab":105, "occurrence_specific.tab":393,  "taxon.tab":105, "time_elapsed":{"sec":109.82, "min":1.83, "hr":0.03}}

NorthAmericanFlora_Fungi	Wed 2021-08-11 11:29:24 AM	{"MoF.tab":17250, "media_resource.tab":4498, "occurrence_specific.tab":17250, "taxon.tab":4491, "time_elapsed":{"sec":107.91, "min":1.8, "hr":0.03}}
NorthAmericanFlora_Fungi	Thu 2021-08-12 05:42:52 AM	{"MoF.tab":17228, "media_resource.tab":4498, "occurrence_specific.tab":17228, "taxon.tab":4491, "time_elapsed":{"sec":108.91, "min":1.82, "hr":0.03}}

Plant list:

15422	Thu 2021-08-12 09:07:35 AM	{"media_resource.tab":280, "taxon.tab":279, "time_elapsed":{"sec":0.66, "min":0.01, "hr":0}}
15422_ENV	Thu 2021-08-12 09:15:31 AM	{"measurement_or_fact_specific.tab":1131, "media_resource.tab":280, "occurrence_specific.tab":1131, "taxon.tab":279, "time_elapsed":{"sec":355.76, "min":5.93, "hr":0.1}}

15424	Thu 2021-08-12 09:09:04 AM	{"media_resource.tab":150, "taxon.tab":150, "time_elapsed":{"sec":0.47, "min":0.01, "hr":0}}
15424_ENV	Thu 2021-08-12 09:12:30 AM	{"measurement_or_fact_specific.tab":598, "media_resource.tab":150, "occurrence_specific.tab":598, "taxon.tab":150, "time_elapsed":{"sec":85.18, "min":1.42, "hr":0.02}}

15421	Thu 2021-08-12 09:14:25 AM	{"media_resource.tab":187, "taxon.tab":187, "time_elapsed":{"sec":0.49, "min":0.01, "hr":0}}
15421_ENV	Thu 2021-08-12 09:16:42 AM	{"measurement_or_fact_specific.tab":647, "media_resource.tab":187, "occurrence_specific.tab":647, "taxon.tab":187, "time_elapsed":{"sec":16.93, "min":0.28, "hr":0}}

15425	Thu 2021-08-12 09:12:34 AM	{"media_resource.tab":59, "taxon.tab":59, "time_elapsed":{"sec":0.43, "min":0.01, "hr":0}}
15425_ENV	Thu 2021-08-12 09:15:13 AM	{"measurement_or_fact_specific.tab":235, "media_resource.tab":59, "occurrence_specific.tab":235, "taxon.tab":59, "time_elapsed":{"sec":39.73, "min":0.66, "hr":0.01}}

15426	Thu 2021-08-12 09:15:37 AM	{"media_resource.tab":101, "taxon.tab":101, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
15426_ENV	Thu 2021-08-12 09:18:22 AM	{"measurement_or_fact_specific.tab":446, "media_resource.tab":101, "occurrence_specific.tab":446, "taxon.tab":101, "time_elapsed":{"sec":44.21, "min":0.74, "hr":0.01}}

15430	Thu 2021-08-12 09:15:41 AM	{"media_resource.tab":204, "taxon.tab":204, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
15430_ENV	Thu 2021-08-12 09:17:59 AM	{"measurement_or_fact_specific.tab":820, "media_resource.tab":204, "occurrence_specific.tab":820, "taxon.tab":204, "time_elapsed":{"sec":18.4, "min":0.31, "hr":0.01}}

15429	Thu 2021-08-12 09:18:37 AM	{"media_resource.tab":250, "taxon.tab":250, "time_elapsed":{"sec":0.51, "min":0.01, "hr":0}}
15429_ENV	Thu 2021-08-12 09:21:06 AM	{"measurement_or_fact_specific.tab":963, "media_resource.tab":250, "occurrence_specific.tab":963, "taxon.tab":250, "time_elapsed":{"sec":28.41, "min":0.47, "hr":0.01}}

91357	Thu 2021-08-12 09:21:25 AM	{"media_resource.tab":88, "taxon.tab":88, "time_elapsed":{"sec":0.44, "min":0.01, "hr":0}}
91357_ENV	Thu 2021-08-12 09:23:41 AM	{"measurement_or_fact_specific.tab":423, "media_resource.tab":88, "occurrence_specific.tab":423, "taxon.tab":88, "time_elapsed":{"sec":16.12, "min":0.27, "hr":0}}

------------------------------------------------------------
php5.6 parse_unstructured_text_memoirs.php jenkins '{"resource_id": "118935", "resource_name":"1st doc"}'
php5.6 parse_unstructured_text_memoirs.php jenkins '{"resource_id": "120081", "resource_name":"2nd doc"}'

parse_unstructured_text_memoirs.php _ '{"resource_id": "118935", "resource_name":"1st doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "120081", "resource_name":"2nd doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "120082", "resource_name":"4th doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "118986", "resource_name":"5th doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "118920", "resource_name":"6th doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "120083", "resource_name":"7th doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "118237", "resource_name":"8th doc"}' species sections
Other MoftheAES:
parse_unstructured_text_memoirs.php _ '{"resource_id": "30355", "resource_name":"others"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "27822", "resource_name":"MotAES"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "30353", "resource_name":"MotAES"}' // no records
parse_unstructured_text_memoirs.php _ '{"resource_id": "30354", "resource_name":"MotAES"}'
Jul 19, 2021 Mon
parse_unstructured_text_memoirs.php _ '{"resource_id": "119035", "resource_name":"MotAES"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "118946", "resource_name":"MotAES"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "118936", "resource_name":"MotAES"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "118236", "resource_name":"MotAES"}' // no records
parse_unstructured_text_memoirs.php _ '{"resource_id": "118981", "resource_name":"MotAES"}' // Jen confirms to ignore this doc.
119050 --- bad source OCR
parse_unstructured_text_memoirs.php _ '{"resource_id": "118950", "resource_name":"BHL"}' //(1) Stephensia cunilae Braun (Figs. 11, 24, 33, 52, 52a, 102, 102a.) 
Jul 20, 2021 Tue
parse_unstructured_text_memoirs.php _ '{"resource_id": "120602", "resource_name":"MotAES"}' // with some TLC was able to get some 'Present' data.
parse_unstructured_text_memoirs.php _ '{"resource_id": "119187", "resource_name":"MotAES"}'
Jul 27, 2021 Tue
parse_unstructured_text_memoirs.php _ '{"resource_id": "118978", "resource_name":"MotAES"}' 
parse_unstructured_text_memoirs.php _ '{"resource_id": "118941", "resource_name":"BHL"}' //(1) Bucculatrix fusicola Braun (Figs. 3, 41, 58, 58a, 58b, 59, 59a.) 
Jul 28, 2021 Wed
parse_unstructured_text_memoirs.php _ '{"resource_id": "119520", "resource_name":"MotAES"}' 
parse_unstructured_text_memoirs.php _ '{"resource_id": "119188", "resource_name":"MotAES"}' 

"119520_ENV", "119188_ENV"

=== START BHL RESOURCES ===
parse_unstructured_text_memoirs.php _ '{"resource_id": "15423", "resource_name":"all_BHL"}' //1
parse_unstructured_text_memoirs.php _ '{"resource_id": "91155", "resource_name":"all_BHL"}' //2
Aug 2 Mon
parse_unstructured_text_memoirs.php _ '{"resource_id": "15427", "resource_name":"all_BHL"}' //3
parse_unstructured_text_memoirs.php _ '{"resource_id": "15428", "resource_name":"all_BHL"}' //4
parse_unstructured_text_memoirs.php _ '{"resource_id": "91144", "resource_name":"all_BHL"}' //5
Aug 3 Tue
parse_unstructured_text_memoirs.php _ '{"resource_id": "91225", "resource_name":"MotAES"}' //6 --- host-pathogen list pattern
Aug 5 Thu
parse_unstructured_text_memoirs.php _ '{"resource_id": "91362", "resource_name":"MotAES"}'          //7 --- host-pathogen list pattern
parse_unstructured_text_memoirs.php _ '{"resource_id": "91362_species", "resource_name":"all_BHL"}' //7 --- "7a. Urocystis magica" --- same as 15428

====================== North American Flora ======================
FUNGI.txt
parse_unstructured_text_memoirs.php _ '{"resource_id": "15404", "resource_name":"all_BHL"}' //F1

15404	    Tue 2021-08-10 08:22:34 AM	{                "media.tab":295,                        "taxon.tab":289, "time_elapsed":{"sec":0.53, "min":0.01, "hr":0}}
15404_ENV	Tue 2021-08-10 08:24:58 AM	{"MoF.tab":1360, "media.tab":295, "occurrence.tab":1360, "taxon.tab":289, "time_elapsed":{"sec":24.55, "min":0.41, "hr":0.01}}
15404	    Tue 2021-08-10 08:28:16 AM	{                "media.tab":295,                        "taxon.tab":289, "time_elapsed":{"sec":0.53, "min":0.01, "hr":0}}
15404_ENV	Tue 2021-08-10 08:30:34 AM	{"MoF.tab":1368, "media.tab":295, "occurrence.tab":1368, "taxon.tab":289, "time_elapsed":{"sec":18.79, "min":0.31, "hr":0.01}}
15404	    Mon 2021-09-27 10:15:20 AM	{                "media.tab":299,                        "taxon.tab":293, "time_elapsed":{"sec":0.62, "min":0.01, "hr":0}}
15404_ENV	Mon 2021-09-27 10:18:51 AM	{"MoF.tab":1346, "media.tab":299, "occurrence.tab":1346, "taxon.tab":293, "time_elapsed":{"sec":90.39, "min":1.51, "hr":0.03}}

parse_unstructured_text_memoirs.php _ '{"resource_id": "15405", "resource_name":"all_BHL"}' //F2
parse_unstructured_text_memoirs.php _ '{"resource_id": "15406", "resource_name":"all_BHL"}' //F3
parse_unstructured_text_memoirs.php _ '{"resource_id": "15407", "resource_name":"all_BHL", "group":"Fungi"}'
PLANTS.txt
parse_unstructured_text_memoirs.php _ '{"resource_id": "15422", "resource_name":"all_BHL", "group":"Plants"}'
91357 91461 91336

NorthAmericanFlora_Plants	Thu 2021-08-26 08:27:21 AM	{"MoF.tab":44886, "media.tab":11382, "occurrence.tab":44886, "taxon.tab":11243, "time_elapsed":{"sec":267.87, "min":4.46, "hr":0.07}}
NorthAmericanFlora_Plants	Tue 2021-08-31 03:10:17 AM	{"MoF.tab":45013, "media.tab":11438, "occurrence.tab":45013, "taxon.tab":11299, "time_elapsed":{"sec":267.57, "min":4.46, "hr":0.07}}

====================== Kubitzki_et_al ======================
START PATTERNS:
sample genus:
"1. Zippelia Blume Figs. 109 A, 110A, B"
sample family:
"Eucommiaceae"
sample with intermediate ranks
    2. Tribe Aristolochieae
    I. Subfamily Amaranthoideae
    II. Subfam. Gomphrenoideae
    2a. Subtribe Isotrematinae
ADDITIONAL PATTERNS:
volii1993:
v. Subfam. Ruschioideae Schwantes in Ihlenf.,
I. Subfam. Mitrastemoidae

voliii1998:
v. Subfam. Hyacinthoideae Link (1829).
III. Subfam. lridioideae Pax (1882).
l3. Olsynium Raf. <--- misspelling, L for 1. Rats, I was hoping those wouldn't occur here
3. Tribe lxieae Dumort (1822).
50. Tritoniopsis 1. Bolus
53. Gladiolus 1. Figs. 90C, 92
67. Duthieastrum de Vos
7. /ohnsonia R. Br. Fig.95A-D Lanariaceae.

parse_unstructured_text_memoirs.php _ '{"resource_id": "volii1993", "resource_name":"Kubitzki", "group":"Kubitzki"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "voliii1998", "resource_name":"Kubitzki", "group":"Kubitzki"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "voliv1998", "resource_name":"Kubitzki", "group":"Kubitzki"}' --- nothing, short PDF file
parse_unstructured_text_memoirs.php _ '{"resource_id": "volv2003", "resource_name":"Kubitzki", "group":"Kubitzki"}'
Sep 20 Mon:
parse_unstructured_text_memoirs.php _ '{"resource_id": "volvi2004", "resource_name":"Kubitzki", "group":"Kubitzki"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "volvii2004", "resource_name":"Kubitzki", "group":"Kubitzki"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "volviii2007", "resource_name":"Kubitzki", "group":"Kubitzki"}'
Sep 21 Tue:
parse_unstructured_text_memoirs.php _ '{"resource_id": "volix2007", "resource_name":"Kubitzki", "group":"Kubitzki"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "volx2011", "resource_name":"Kubitzki", "group":"Kubitzki"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "volxi2014", "resource_name":"Kubitzki", "group":"Kubitzki"}'
Sep 22 Wed:
parse_unstructured_text_memoirs.php _ '{"resource_id": "volxii2015", "resource_name":"Kubitzki", "group":"Kubitzki"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "volxiii2015", "resource_name":"Kubitzki", "group":"Kubitzki"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "volxiv2016", "resource_name":"Kubitzki", "group":"Kubitzki"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "volxv2018", "resource_name":"Kubitzki", "group":"Kubitzki"}'

volii1993	Thu 2021-09-23 12:23:13 AM	{"media_resource.tab":1519, "taxon.tab":1496, "time_elapsed":{"sec":1.78, "min":0.03, "hr":0}}
voliii1998	Thu 2021-09-23 12:23:44 AM	{"media_resource.tab":545, "taxon.tab":529, "time_elapsed":{"sec":0.84, "min":0.01, "hr":0}}
volv2003	Thu 2021-09-23 12:23:57 AM	{"media_resource.tab":792, "taxon.tab":792, "time_elapsed":{"sec":0.94, "min":0.02, "hr":0}}
volvi2004	Thu 2021-09-23 12:24:11 AM	{"media_resource.tab":809, "taxon.tab":801, "time_elapsed":{"sec":1.04, "min":0.02, "hr":0}}
volvii2004	Thu 2021-09-23 12:24:26 AM	{"media_resource.tab":1046, "taxon.tab":1020, "time_elapsed":{"sec":1.11, "min":0.02, "hr":0}}
volviii2007	Thu 2021-09-23 12:24:45 AM	{"media_resource.tab":1934, "taxon.tab":1920, "time_elapsed":{"sec":1.77, "min":0.03, "hr":0}}
volix2007	Thu 2021-09-23 12:24:59 AM	{"media_resource.tab":568, "taxon.tab":562, "time_elapsed":{"sec":0.87, "min":0.01, "hr":0}}
volx2011	Thu 2021-09-23 12:25:12 AM	{"media_resource.tab":774, "taxon.tab":759, "time_elapsed":{"sec":0.96, "min":0.02, "hr":0}}
volxi2014	Thu 2021-09-23 12:25:30 AM	{"media_resource.tab":518, "taxon.tab":518, "time_elapsed":{"sec":9.02, "min":0.15, "hr":0}}
volxii2015	Thu 2021-09-23 12:25:35 AM	{"media_resource.tab":191, "taxon.tab":186, "time_elapsed":{"sec":0.54, "min":0.01, "hr":0}}
volxiii2015	Thu 2021-09-23 12:25:46 AM	{"media_resource.tab":719, "taxon.tab":715, "time_elapsed":{"sec":0.83, "min":0.01, "hr":0}}
volxiv2016	Thu 2021-09-23 12:25:57 AM	{"media_resource.tab":391, "taxon.tab":386, "time_elapsed":{"sec":0.92, "min":0.02, "hr":0}}
volxv2018	Thu 2021-09-23 12:26:14 AM	{"media_resource.tab":1141, "taxon.tab":1099, "time_elapsed":{"sec":1.21, "min":0.02, "hr":0}}
Kubitzki	Thu 2021-09-23 12:27:04 AM	{"media_resource.tab":10947, "taxon.tab":10780, "time_elapsed":{"sec":24.74, "min":0.41, "hr":0.01}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS["ENV_DEBUG"] = true;
require_library('connectors/Functions_Memoirs');
require_library('connectors/ParseListTypeAPI_Memoirs');
require_library('connectors/ParseUnstructuredTextAPI_Memoirs');
$timestart = time_elapsed();
// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$pdf_id = $param['resource_id'];
$resource_name = $param['resource_name'];
$group = @$param['group'];
$func = new ParseUnstructuredTextAPI_Memoirs($resource_name, $pdf_id);
/*
$str = "±";
// $str = substr($str,0,1);
if(ctype_upper($str)) exit("\nupper siya\n");
else exit("\nnot upper\n");
*/
/*
$row = "I )'. i lill'l'l, SPECIES boy";
$words = explode(" ", strtolower($row));
print_r($words);
$matches = array_keys($words, 'species');
print_r($matches);
$i = -1;
foreach($words as $word) { $i++;
    if($i <= $matches[0]) {
        echo "\n[$word]\n";
        if(stripos($word, "l") !== false) return true; //string is found
    }
}
echo "\n".strlen($row)."\n";
exit("\n-end test\n");
*/
/*
$val = $func->run_GNRD_get_sciname_inXML("Fuirena%20robusta%20Kunth%20,%20Enum.");
exit("\nxx[$val]xx\n-end-\n");
*/
/*
$s = "eli is 49 yrs old";
$s = "1c.";
echo "\n[$s]\n";
// $result = preg_replace("/[^0-9]+/", "", $s);        //get only numbers
$result = preg_replace("/[^a-zA-Z]+/", "", $s);  //get only letter
exit("\n[$result]\n");
*/

// $str = "the quick blk brown"; echo "\n[$str]\n";     $str = xlx_to_xix($str);
// $str = "the quick rll brown"; echo "\n[$str]\n";     $str = xll_to_xil($str);
// $str = "the quick llt brown"; echo "\n[$str]\n";     $str = llx_to_lix($str);
// exit("\n[$str]\n");

/*
$var = "Calyptospora columnaris, 682";
// $var = "Melampsorella elatina. 681";
// $var = "Melampsorella elatina. Ill, 681"; 
// $var = "Ravenelia Thornberiana, 713";

$var = trim(preg_replace('/[0-9]+/', '', $var)); //remove For Western Arabic numbers (0-9):
$last_chars = array(",", ".");
foreach($last_chars as $last_char) {
    $last = substr($var, -1);
    if($last == $last_char) $var = substr($var,0,strlen($var)-1);
}

$words = explode(" ", $var);
$var = $words[0]." ".strtolower($words[1]);
$var = Functions::canonical_form($var);

exit("\n[$var]\n");
*/

// $var = "Melampsorella elatina. Ill, 681";
// $var = "Hyalopsora Aspidiotus, 681";
// exit("\n".Functions::canonical_form($var)."\n");
// $var = "brown fox I'redinopsis the quick";
// $var = str_ireplace("", "", $var);
// exit("\n[$var]\n");

/*
$var1 = "paRt";
$var2 = "part";
if (strcmp($var1, $var2) == 0) echo "\n$var1 is equal to $var2 in a case sensitive string comparison";
else                           echo "\n$var1 is not equal to $var2 in a case sensitive string comparison";
exit("\n-test-\n");
*/
/*
$str = "1. Geothallus tuberosus Campb. Bot. Gaz. 21: 13. 1896.";
$words = explode(" ", $str); print_r($words);
if(is_numeric($words[0])) {
    array_shift($words); //remove first element
    print_r($words); //exit;
    $str = implode(" ", $words);
    exit("\n$str\n");
}
*/
/*
$row = "EZRA TOWNSEND CRESSON 2J";
$tmp = str_replace(array(" ",".",","), "", $row);
$tmp = preg_replace('/[0-9]+/', '', $tmp); //remove For Western Arabic numbers (0-9):
$tmp = trim($tmp);
if(ctype_upper($tmp)) echo "\nupper [$tmp]\n";  //entire row is upper case //EZRA TOWNSEND CRESSON
else echo "\nlower [$tmp]\n";
exit;
*/
/*
$string = "Pegomyia palposa (Stein) (Figs. 1, 30, 54.)";
$string = trim(preg_replace('/\s*\(Fig[^)]*\)/', '', $string)); //remove parenthesis OK
[Pegomyia palposa (Stein)]
echo "\n[$string]\n";
exit("\n");
*/
/*--------------------------------------------------------------------------------------------------------------*/
if(in_array($pdf_id, array('30353', '118236', '118981'))) exit("\nThis document is ignored [$pdf_id]. Will terminate.\n");
$rec[118935] = array('filename' => '118935.txt', 'lines_before_and_after_sciname' => 1); /*1 stable stats: blocks: 1267|1312  Raw scinames count: 1322 */
$rec[120081] = array('filename' => '120081.txt', 'lines_before_and_after_sciname' => 2); /*2 stable stats: blocks: 97    Raw scinames count: 98 */
$rec[120082] = array('filename' => '120082.txt', 'lines_before_and_after_sciname' => 2); /*4 stable stats: blocks: 25    Raw scinames count: 25 */
$rec[118986] = array('filename' => '118986.txt', 'lines_before_and_after_sciname' => 2); /*5 stable stats: blocks: 43    Raw scinames count: 43 */
$rec[118920] = array('filename' => '118920.txt', 'lines_before_and_after_sciname' => 2); /*6 stable stats: blocks: 40|27    Raw scinames count: 44|27 */
$rec[120083] = array('filename' => '120083.txt', 'lines_before_and_after_sciname' => 2); /*7 stable stats: blocks: 192|193   Raw scinames count: 200|191 
                                                                                           wc -l -> 193 120083_descriptions_LT.txt */
$rec[118237] = array('filename' => '118237.txt', 'lines_before_and_after_sciname' => 2); /*8 stable stats: blocks: 46    Raw scinames count: 34|33 | list-type but skipped */
/* TO DO: 
doc 5: didn't get a valid binomial: "Laccophilus spergatus Sharp (Figs. 98-105, 297)"
*/
// === other MotAES ===
if($resource_name == 'MotAES') {
    $arr = array('filename' => $pdf_id.'.txt', 'lines_before_and_after_sciname' => 1); //1st client here is 27822
    if(in_array($pdf_id, array('118936', '118236', '118978', '119520', '119188'))) $arr['lines_before_and_after_sciname'] = 2;
    $rec[119035]['lines_before_and_after_sciname'] = 1;
    $rec[118946]['lines_before_and_after_sciname'] = 1;
    $rec[119187]['lines_before_and_after_sciname'] = 1;
    
    
    $rec[$pdf_id] = $arr;
    /*
    27822 --- blocks: 123|127|124|107   Raw scinames: 171|159|175 
    30353 --- blocks: 2   Raw scinames: 26 (skipped)
    30354 --- blocks: 89|81   Raw scinames: 174|165
    119035 --- blocks: 56|48   Raw scinames: 109|57
    118946 --- blocks: 102|92   Raw scinames: 172|105
    118936 --- blocks: 14|15   Raw scinames: 19
    120602 --- blocks: 20   Raw scinames: 40
    119187 --- blocks: 72   Raw scinames: 241 (71-239)
    118978 --- blocks: 96   Raw scinames: 106
    119520 --- blocks: 677|662   Raw scinames: 706|705
    119188 --- blocks: 185   Raw scinames: 197
    91225 --- blocks: xx   Raw scinames: xx --- host-pathogen list pattern
    */
}
$rec['30355'] = array('filename' => '30355.txt', 'lines_before_and_after_sciname' => 1); /* blocks: 2611   Raw scinames: 2641 */
$rec['118950'] = array('filename' => '118950.txt', 'lines_before_and_after_sciname' => 2); /* blocks: 56   Raw scinames: 56 */
$rec['118941'] = array('filename' => '118941.txt', 'lines_before_and_after_sciname' => 1); /* blocks: 99   Raw scinames: 101 */

// === START BHL RESOURCES ===
$rec['15423'] = array('filename' => '15423.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'BHL'); /*1 blocks: 73|75    Raw scinames: 96|96 */
$rec['91155'] = array('filename' => '91155.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'BHL'); /*2 blocks: 101|105   Raw scinames: 125|124 */
$rec['15427'] = array('filename' => '15427.txt', 'lines_before_and_after_sciname' => 2, 'doc' => 'BHL'); /*3 blocks: 155   Raw scinames: 183 */
$rec['15428'] = array('filename' => '15428.txt', 'lines_before_and_after_sciname' => 2, 'doc' => 'BHL'); /*3 blocks: 194   Raw scinames: 243 */
$rec['91144'] = array('filename' => '91144.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'BHL'); /*3 blocks: 196   Raw scinames: 246 */
$rec['91362_species'] = array('filename' => '91362_species.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'BHL'); /*3 blocks: 58 | Raw scinames: 62 */

// === FUNGI.txt ===
$rec['15404'] = array('filename' => '15404.txt', 'lines_before_and_after_sciname' => 2, 'doc' => 'BHL'); /*3 blocks: 292   Raw scinames: 343 */
$rec['15405'] = array('filename' => '15405.txt', 'lines_before_and_after_sciname' => 2, 'doc' => 'BHL'); /*3 blocks: xxx   Raw scinames: xxx */
$rec['15406'] = array('filename' => '15406.txt', 'lines_before_and_after_sciname' => 2, 'doc' => 'BHL'); /*3 blocks: xxx   Raw scinames: xxx */
if(in_array($group, array('Fungi', 'Plants'))) {
    $rec[$pdf_id] = array('filename' => $pdf_id.'.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'BHL');
}
$rec['15422'] = array('filename' => '15422.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'BHL'); /*3 blocks: xxx   Raw scinames: xxx */

// === Kubitzki_et_al ===
$rec['volii1993'] = array('filename' => 'volii1993.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'Kubitzki_et_al');
if($resource_name == 'Kubitzki') {
    $rec[$pdf_id] = array('filename' => $pdf_id.'.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'Kubitzki_et_al');
}
/*--------------------------------------------------------------------------------------------------------------*/
if($val = @$rec[$pdf_id]) $input = $val;
else exit("\nUndefined PDF ID\n");
/* ---------------------------------- List-type here:
// variable lines_before_and_after_sciname is important. It is the lines before and after the "list header".
---------------------------------- */
$pdf_id = pathinfo($input['filename'], PATHINFO_FILENAME);
if($val = @$input['doc']) $doc = $val;
else                      $doc = "MoftheAES";
if(Functions::is_production()) $input['epub_output_txts_dir'] = '/extra/other_files/Smithsonian/'.$doc.'/'.$pdf_id.'/';
else                           $input['epub_output_txts_dir'] = '/Volumes/AKiTiO4/other_files/Smithsonian/'.$doc.'/'.$pdf_id.'/';
// /*
$folder = $input['epub_output_txts_dir'];
if(!is_dir($folder)) mkdir($folder);
$postfix = array("_tagged.txt", "_tagged_LT.txt", "_edited.txt", "_edited_LT.txt", "_descriptions_LT.txt");
foreach($postfix as $post) {
    $txt_filename = pathinfo($folder, PATHINFO_BASENAME)."$post";
    $txt_filename = $folder."/".$txt_filename;
    echo "\n$txt_filename - ";
    if(file_exists($txt_filename)) if(unlink($txt_filename)) echo " deleted OK\n";
    // else                                                     echo " does not exist OK\n";
}
// exit("\n-end for now-\n");
// */
// print_r($input); exit;
$func->parse_pdftotext_result($input);

/* a utility - copied template
$func->utility_download_txt_files();
*/
/*
wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/SCtZ-0437.txt
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>