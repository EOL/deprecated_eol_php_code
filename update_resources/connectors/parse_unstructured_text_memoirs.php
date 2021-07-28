<?php
namespace php_active_record;
/* DATA-1877: textmining more unstructured text
118935	Mon 2021-06-21 10:33:30 AM	    {                "media.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":3.64, "min":0.06, "hr":0}}
118935_ENV	Mon 2021-06-21 10:42:29 AM	{"MoF.tab":1448, "media.tab":1309, "occur.tab":1448, "taxon.tab":1308, "time_elapsed":{"sec":490.28, "min":8.17, "hr":0.14}}
118935	Tue 2021-06-22 12:37:41 AM	    {                "media.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.3, "min":0.02, "hr":0}}
118935_ENV	Tue 2021-06-22 12:40:29 AM	{"MoF.tab":1448, "media.tab":1309, "occur.tab":1448, "taxon.tab":1308, "time_elapsed":{"sec":167.64, "min":2.79, "hr":0.05}}
118935	Tue 2021-06-22 01:07:09 AM	    {                "media.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.38, "min":0.02, "hr":0}}
118935_ENV	Tue 2021-06-22 01:08:55 AM	{"MoF.tab":1447,                            "occur.tab":1447, "taxon.tab":1308, "time_elapsed":{"sec":105.96, "min":1.77, "hr":0.03}}
118935	Tue 2021-06-22 12:32:41 PM	    {                "media.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.11, "min":0.02, "hr":0}}
118935_ENV	Tue 2021-06-22 12:32:57 PM	{"MoF.tab":1447,                            "occur.tab":1447, "taxon.tab":1308, "time_elapsed":{"sec":16.69, "min":0.28, "hr":0}}
118935	Wed 2021-06-23 02:59:52 AM	    {                "media.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.1, "min":0.02, "hr":0}}
118935_ENV	Wed 2021-06-23 03:01:06 AM	{"MoF.tab":1447,                            "occur.tab":1447, "taxon.tab":1308, "time_elapsed":{"sec":13.62, "min":0.23, "hr":0}}
removed 'Chin' in eol-geonames
118935	Thu 2021-06-24 12:33:22 AM	    {                "media.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.09, "min":0.02, "hr":0}}
118935_ENV	Thu 2021-06-24 12:35:46 AM	{"MoF.tab":1447,                            "occur.tab":1447, "taxon.tab":1308, "time_elapsed":{"sec":83.93, "min":1.4, "hr":0.02}}
must have a big script change:
118935	Tue 2021-07-06 07:05:18 AM	    {                "media.tab":782,                    "taxon.tab":781, "time_elapsed":{"sec":1.16, "min":0.02, "hr":0}}
118935_ENV	Tue 2021-07-06 07:08:38 AM	{"MoF.tab":983,                             "occur.tab":983,  "taxon.tab":781, "time_elapsed":{"sec":140.04, "min":2.33, "hr":0.04}}
brought back script change: correctly
118935	Tue 2021-07-06 10:05:29 AM	    {               "media.tab":1309,                             "taxon.tab":1308, "time_elapsed":{"sec":1.08, "min":0.02, "hr":0}}
118935_ENV	Tue 2021-07-06 10:07:10 AM	{"MoF.tab":1479,                            "occur.tab":1479, "taxon.tab":1308, "time_elapsed":{"sec":41.18, "min":0.69, "hr":0.01}}
118935	Wed 2021-07-07 01:38:44 AM	    {               "media.tab":1309,                             "taxon.tab":1308, "time_elapsed":{"sec":1.29, "min":0.02, "hr":0}}
118935_ENV	Wed 2021-07-07 01:41:51 AM	{"MoF.tab":1479,                            "occur.tab":1479, "taxon.tab":1308, "time_elapsed":{"sec":66.67, "min":1.11, "hr":0.02}}
------------------------------------------------------------
120081	Tue 2021-06-22 12:43:28 PM	    {               "media.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.92, "min":0.02, "hr":0}}
120081_ENV	Tue 2021-06-22 12:48:10 PM	{"MoF.tab":519, "media.tab":95, "occur.tab":519, "taxon.tab":95, "time_elapsed":{"sec":280.95, "min":4.68, "hr":0.08}}
120081	Wed 2021-06-23 02:59:39 AM	    {               "media.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
120081_ENV	Wed 2021-06-23 03:00:53 AM	{"MoF.tab":633, "media.tab":95, "occur.tab":633, "taxon.tab":95, "time_elapsed":{"sec":14.16, "min":0.24, "hr":0}}
120081	Wed 2021-06-23 11:35:43 AM	    {               "media.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":3.21, "min":0.05, "hr":0}}
120081_ENV	Wed 2021-06-23 11:37:11 AM	{"MoF.tab":633, "media.tab":95, "occur.tab":633, "taxon.tab":95, "time_elapsed":{"sec":24.65, "min":0.41, "hr":0.01}}
removed 'Chin' in eol-geonames
120081	Thu 2021-06-24 12:31:23 AM	    {               "media.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
120081_ENV	Thu 2021-06-24 12:32:56 AM	{"MoF.tab":632, "media.tab":95, "occur.tab":632, "taxon.tab":95, "time_elapsed":{"sec":33.1, "min":0.55, "hr":0.01}}
remove traits in eol-geonames if inside literature reference
120081	Fri 2021-06-25 08:13:25 AM	    {               "media.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":2.23, "min":0.04, "hr":0}}
120081_ENV	Fri 2021-06-25 08:15:48 AM	{"MoF.tab":523, "media.tab":95, "occur.tab":523, "taxon.tab":95, "time_elapsed":{"sec":81.53, "min":1.36, "hr":0.02}}
120081	Tue 2021-07-06 07:09:13 AM	    {               "media.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.42, "min":0.01, "hr":0}}
120081_ENV	Tue 2021-07-06 07:10:50 AM	{"MoF.tab":523, "media.tab":95, "occur.tab":523, "taxon.tab":95, "time_elapsed":{"sec":37.72, "min":0.63, "hr":0.01}}
120081	Wed 2021-07-07 01:42:14 AM	    {               "media.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
120081_ENV	Wed 2021-07-07 01:44:41 AM	{"MoF.tab":523, "media.tab":95, "occur.tab":523, "taxon.tab":95, "time_elapsed":{"sec":26.99, "min":0.45, "hr":0.01}}
------------------------------------------------------------
120082	Thu 2021-06-24 11:27:32 AM	    {                                       "media.tab":25,                               "taxon.tab":25, "time_elapsed":{"sec":0.37, "min":0.01, "hr":0}}
120082_ENV	Thu 2021-06-24 11:31:27 AM	{"MoF.tab":92, "media.tab":25, "occur.tab":92, "taxon.tab":25, "time_elapsed":{"sec":175.03, "min":2.92, "hr":0.05}}
remove Distrito Federal,https://www.geonames.org/3463504
120082	Fri 2021-06-25 07:37:02 AM	    {                                       "media.tab":25,                               "taxon.tab":25, "time_elapsed":{"sec":3.79, "min":0.06, "hr":0}}
120082_ENV	Fri 2021-06-25 07:38:51 AM	{"MoF.tab":91, "media.tab":25, "occur.tab":91, "taxon.tab":25, "time_elapsed":{"sec":48.61, "min":0.81, "hr":0.01}}
remove traits in eol-geonames if inside literature reference
120082	Fri 2021-06-25 07:59:19 AM	    {                                       "media.tab":25,                               "taxon.tab":25, "time_elapsed":{"sec":1.44, "min":0.02, "hr":0}}
120082_ENV	Fri 2021-06-25 08:00:49 AM	{"MoF.tab":61, "media.tab":25, "occur.tab":61, "taxon.tab":25, "time_elapsed":{"sec":28.45, "min":0.47, "hr":0.01}}
120082	Mon 2021-06-28 07:59:54 AM	    {                                       "media.tab":25,                               "taxon.tab":25, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
120082_ENV	Mon 2021-06-28 08:01:23 AM	{"MoF.tab":61, "media.tab":25, "occur.tab":61, "taxon.tab":25, "time_elapsed":{"sec":27.93, "min":0.47, "hr":0.01}}
120082	Tue 2021-07-06 07:11:15 AM	    {                                       "media.tab":25,                               "taxon.tab":25, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
120082_ENV	Tue 2021-07-06 07:12:49 AM	{"MoF.tab":61, "media.tab":25, "occur.tab":61, "taxon.tab":25, "time_elapsed":{"sec":33.79, "min":0.56, "hr":0.01}}
120082	Wed 2021-07-07 01:45:01 AM	    {              "media.tab":25,                 "taxon.tab":25, "time_elapsed":{"sec":0.34, "min":0.01, "hr":0}}
120082_ENV	Wed 2021-07-07 01:47:37 AM	{"MoF.tab":61, "media.tab":25, "occur.tab":61, "taxon.tab":25, "time_elapsed":{"sec":36.02, "min":0.6, "hr":0.01}}
------------------------------------------------------------
118986	Tue 2021-06-29 11:36:55 PM	    {                                        "media.tab":41,                                "taxon.tab":41, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
118986_ENV	Tue 2021-06-29 11:48:52 PM	{"MoF.tab":512, "media.tab":41, "occur.tab":512, "taxon.tab":41, "time_elapsed":{"sec":657.64, "min":10.96, "hr":0.18}}
118986	Wed 2021-06-30 01:32:52 AM	    {                                        "media.tab":41,                                "taxon.tab":41, "time_elapsed":{"sec":1.88, "min":0.03, "hr":0}}
118986_ENV	Wed 2021-06-30 01:36:21 AM	{"MoF.tab":512, "media.tab":41, "occur.tab":512, "taxon.tab":41, "time_elapsed":{"sec":148.16, "min":2.47, "hr":0.04}}
118986	Thu 2021-07-01 01:25:48 AM	    {                                        "media.tab":41,                                "taxon.tab":41, "time_elapsed":{"sec":0.58, "min":0.01, "hr":0}}
118986_ENV	Thu 2021-07-01 01:27:42 AM	{"MoF.tab":512, "media.tab":41, "occur.tab":512, "taxon.tab":41, "time_elapsed":{"sec":54.05, "min":0.9, "hr":0.02}}
118986	Tue 2021-07-06 07:13:09 AM	    {               "media.tab":41,                  "taxon.tab":41, "time_elapsed":{"sec":0.42, "min":0.01, "hr":0}}
118986_ENV	Tue 2021-07-06 07:24:29 AM	{"MoF.tab":511, "media.tab":41, "occur.tab":511, "taxon.tab":41, "time_elapsed":{"sec":619.78, "min":10.33, "hr":0.17}}
118986	Wed 2021-07-07 01:48:10 AM	    {               "media.tab":41,                  "taxon.tab":41, "time_elapsed":{"sec":2.33, "min":0.04, "hr":0}}
118986_ENV	Wed 2021-07-07 01:50:39 AM	{"MoF.tab":511, "media.tab":41, "occur.tab":511, "taxon.tab":41, "time_elapsed":{"sec":28.31, "min":0.47, "hr":0.01}}
------------------------------------------------------------
118920	Wed 2021-06-30 07:48:29 AM	    {                                       "media.tab":27,                               "taxon.tab":27, "time_elapsed":{"sec":0.36, "min":0.01, "hr":0}}
118920_ENV	Wed 2021-06-30 07:52:50 AM	{"MoF.tab":74, "media.tab":27, "occur.tab":74, "taxon.tab":27, "time_elapsed":{"sec":200.19, "min":3.34, "hr":0.06}}
118920	Tue 2021-07-06 07:24:48 AM	    {              "media.tab":27,                 "taxon.tab":27, "time_elapsed":{"sec":0.36, "min":0.01, "hr":0}}
118920_ENV	Tue 2021-07-06 07:26:23 AM	{"MoF.tab":74, "media.tab":27, "occur.tab":74, "taxon.tab":27, "time_elapsed":{"sec":35.55, "min":0.59, "hr":0.01}}
118920	Wed 2021-07-07 01:50:55 AM	    {              "media.tab":27,                 "taxon.tab":27, "time_elapsed":{"sec":0.34, "min":0.01, "hr":0}}
118920_ENV	Wed 2021-07-07 01:53:15 AM	{"MoF.tab":73, "media.tab":27, "occur.tab":73, "taxon.tab":27, "time_elapsed":{"sec":20.2, "min":0.34, "hr":0.01}}
------------------------------------------------------------
120083	Mon 2021-07-05 06:48:23 AM	    {                                        "media.tab":383,                                "taxon.tab":294, "time_elapsed":{"sec":4.32, "min":0.07, "hr":0}}
120083_ENV	Mon 2021-07-05 07:02:36 AM	{"MoF.tab":752, "media.tab":190, "occur.tab":752, "taxon.tab":294, "time_elapsed":{"sec":793.29, "min":13.22, "hr":0.22}}
120083	Tue 2021-07-06 02:50:01 AM	    {                                        "media.tab":379,                                "taxon.tab":294, "time_elapsed":{"sec":5.62, "min":0.09, "hr":0}}
120083_ENV	Tue 2021-07-06 02:56:09 AM	{"MoF.tab":752, "media.tab":186, "occur.tab":752, "taxon.tab":294, "time_elapsed":{"sec":305.1, "min":5.09, "hr":0.08}}
120083	Tue 2021-07-06 07:27:16 AM	    {               "media.tab":379,                  "taxon.tab":294, "time_elapsed":{"sec":0.57, "min":0.01, "hr":0}}
120083_ENV	Tue 2021-07-06 07:28:48 AM	{"MoF.tab":752, "media.tab":186, "occur.tab":752, "taxon.tab":294, "time_elapsed":{"sec":32.02, "min":0.53, "hr":0.01}}
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

27822	Wed 2021-07-14 11:46:49 AM	{                                        "media_resource.tab":123,                                "taxon.tab":68, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
27822_ENV	Wed 2021-07-14 11:52:54 {"measurement_or_fact_specific.tab":112, "media_resource.tab":123, "occurrence_specific.tab":112, "taxon.tab":68, "time_elapsed":{"sec":245.09, "min":4.08, "hr":0.07}}
27822	Thu 2021-07-15 10:14:21 AM	{                                        "media_resource.tab":85,                                 "taxon.tab":71, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
27822_ENV	Thu 2021-07-15 10:16:52 {"measurement_or_fact_specific.tab":115, "media_resource.tab":85, "occurrence_specific.tab":115,  "taxon.tab":71, "time_elapsed":{"sec":30.27, "min":0.5, "hr":0.01}}

30354	Wed 2021-07-14 11:47:24 AM	{                                       "media_resource.tab":79,                               "taxon.tab":79, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
30354_ENV	Wed 2021-07-14 11:52:22 {"measurement_or_fact_specific.tab":81, "media_resource.tab":79, "occurrence_specific.tab":81, "taxon.tab":79, "time_elapsed":{"sec":178.01, "min":2.97, "hr":0.05}}
30354	Thu 2021-07-15 10:14:35 AM	{                                       "media_resource.tab":87,                               "taxon.tab":87, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
30354_ENV	Thu 2021-07-15 10:17:08 {"measurement_or_fact_specific.tab":85, "media_resource.tab":87, "occurrence_specific.tab":85, "taxon.tab":87, "time_elapsed":{"sec":33.06, "min":0.55, "hr":0.01}}


119035	Mon 2021-07-19 11:55:23 AM	{"media_resource.tab":48, "taxon.tab":48, "time_elapsed":{"sec":0.7, "min":0.01, "hr":0}}
119035_ENV	Mon 2021-07-19 12:00:28 PM	{"measurement_or_fact_specific.tab":169, "media_resource.tab":48, "occurrence_specific.tab":169, "taxon.tab":48, "time_elapsed":{"sec":185.41, "min":3.09, "hr":0.05}}
119035	Tue 2021-07-20 05:22:11 AM	{"media_resource.tab":56, "taxon.tab":56, "time_elapsed":{"sec":0.39, "min":0.01, "hr":0}}
119035_ENV	Tue 2021-07-20 05:24:23 AM	{"measurement_or_fact_specific.tab":169, "media_resource.tab":56, "occurrence_specific.tab":169, "taxon.tab":56, "time_elapsed":{"sec":12.3, "min":0.21, "hr":0}}

118936	Mon 2021-07-19 11:58:31 AM	{"media_resource.tab":14, "taxon.tab":14, "time_elapsed":{"sec":0.35, "min":0.01, "hr":0}}
118936_ENV	Mon 2021-07-19 12:04:14 PM	{"measurement_or_fact_specific.tab":63, "media_resource.tab":14, "occurrence_specific.tab":63, "taxon.tab":14, "time_elapsed":{"sec":223.14, "min":3.72, "hr":0.06}}
118936	Tue 2021-07-20 05:21:53 AM	{"media_resource.tab":14, "taxon.tab":14, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118936_ENV	Tue 2021-07-20 05:24:07 AM	{"measurement_or_fact_specific.tab":63, "media_resource.tab":14, "occurrence_specific.tab":63, "taxon.tab":14, "time_elapsed":{"sec":14.65, "min":0.24, "hr":0}}

118946	Mon 2021-07-19 11:59:01 AM	{"media_resource.tab":92, "taxon.tab":91, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118946_ENV	Mon 2021-07-19 12:12:06 PM	{"measurement_or_fact_specific.tab":639, "media_resource.tab":92, "occurrence_specific.tab":639, "taxon.tab":91, "time_elapsed":{"sec":664.58, "min":11.08, "hr":0.18}}
118946	Tue 2021-07-20 05:22:44 AM	{"media_resource.tab":102, "taxon.tab":101, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118946_ENV	Tue 2021-07-20 05:24:58 AM	{"measurement_or_fact_specific.tab":639, "media_resource.tab":102, "occurrence_specific.tab":639, "taxon.tab":101, "time_elapsed":{"sec":14.1, "min":0.24, "hr":0}}

118950	Mon 2021-07-19 12:01:14 PM	{                                        "media_resource.tab":55,                                "taxon.tab":55, "time_elapsed":{"sec":0.37, "min":0.01, "hr":0}}
118950_ENV	Mon 2021-07-19 12:07:54 {"measurement_or_fact_specific.tab":152, "media_resource.tab":55, "occurrence_specific.tab":152, "taxon.tab":55, "time_elapsed":{"sec":280.61, "min":4.68, "hr":0.08}}
118950	Tue 2021-07-20 05:22:18 AM	{                                        "media_resource.tab":55,                                "taxon.tab":55, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
118950_ENV	Tue 2021-07-20 05:24:31 {"measurement_or_fact_specific.tab":151, "media_resource.tab":55, "occurrence_specific.tab":151, "taxon.tab":55, "time_elapsed":{"sec":12.86, "min":0.21, "hr":0}}

120602	Wed 2021-07-21 11:08:20 AM	{                                          "media_resource.tab":20, "taxon.tab":20, "time_elapsed":{"sec":0.33, "min":0.01, "hr":0}}
120602_ENV	Wed 2021-07-21 11:10:48 {"measurement_or_fact_specific.tab":4, "occurrence_specific.tab":4, "taxon.tab":20, "time_elapsed":{"sec":28.63, "min":0.48, "hr":0.01}}

119187	Mon 2021-07-26 10:01:01 AM	{                                        "media_resource.tab":40,                                "taxon.tab":31, "time_elapsed":{"sec":10.09, "min":0.17, "hr":0}}
119187_ENV	Mon 2021-07-26 10:03:22 {"measurement_or_fact_specific.tab":156, "media_resource.tab":40, "occurrence_specific.tab":156, "taxon.tab":31, "time_elapsed":{"sec":21.59, "min":0.36, "hr":0.01}}

118941	Tue 2021-07-27 10:30:58 AM	{"media_resource.tab":94, "taxon.tab":94, "time_elapsed":{"sec":0.49, "min":0.01, "hr":0}}
118941_ENV	Tue 2021-07-27 10:43:12 AM	{"measurement_or_fact_specific.tab":351, "media_resource.tab":94, "occurrence_specific.tab":351, "taxon.tab":94, "time_elapsed":{"sec":614.26, "min":10.24, "hr":0.17}}

118978	Tue 2021-07-27 10:54:30 AM	{"media_resource.tab":86, "taxon.tab":82, "time_elapsed":{"sec":0.42, "min":0.01, "hr":0}}
118978_ENV	Tue 2021-07-27 10:56:44 AM	{"measurement_or_fact_specific.tab":616, "media_resource.tab":86, "occurrence_specific.tab":616, "taxon.tab":82, "time_elapsed":{"sec":14.12, "min":0.24, "hr":0}}

------------------------------------------------------------ North American Flora (DATA-1890) --- BHL
15423	Thu 2021-07-08 09:19:19 AM	{                                        "media_resource.tab":66,                                "taxon.tab":66, "time_elapsed":{"sec":1.79, "min":0.03, "hr":0}}
15423_ENV	Thu 2021-07-08 09:22:29 {"measurement_or_fact_specific.tab":340, "media_resource.tab":66, "occurrence_specific.tab":340, "taxon.tab":66, "time_elapsed":{"sec":70.23, "min":1.17, "hr":0.02}}
removed "ocean - ENVO_00000447":
15423	Mon 2021-07-12 05:58:28 AM	{                                        "media_resource.tab":70,                                "taxon.tab":70, "time_elapsed":{"sec":0.8, "min":0.01, "hr":0}}
15423_ENV	Mon 2021-07-12 06:04:35 {"measurement_or_fact_specific.tab":324, "media_resource.tab":70, "occurrence_specific.tab":324, "taxon.tab":70, "time_elapsed":{"sec":247.19, "min":4.12, "hr":0.07}}
15423	Tue 2021-07-13 07:19:04 AM	{                                        "media_resource.tab":73,                                "taxon.tab":73, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
15423_ENV	Tue 2021-07-13 07:23:01 {"measurement_or_fact_specific.tab":338, "media_resource.tab":73, "occurrence_specific.tab":338, "taxon.tab":73, "time_elapsed":{"sec":115.98, "min":1.93, "hr":0.03}}
------------------------------------------------------------
91155	Thu 2021-07-08 09:37:48 AM	{                                        "media_resource.tab":98,                                 "taxon.tab":98, "time_elapsed":{"sec":0.79, "min":0.01, "hr":0}}
91155_ENV	Thu 2021-07-08 09:40:38 {"measurement_or_fact_specific.tab":672, "media_resource.tab":98, "occurrence_specific.tab":672,  "taxon.tab":98, "time_elapsed":{"sec":50.01, "min":0.83, "hr":0.01}}
removed "ocean - ENVO_00000447":
91155	Mon 2021-07-12 06:08:04 AM	{                                        "media_resource.tab":107,                                "taxon.tab":107, "time_elapsed":{"sec":3.3, "min":0.06, "hr":0}}
91155_ENV	Mon 2021-07-12 06:13:05 {"measurement_or_fact_specific.tab":663, "media_resource.tab":107, "occurrence_specific.tab":663, "taxon.tab":107, "time_elapsed":{"sec":180.47, "min":3.01, "hr":0.05}}
91155	Tue 2021-07-13 07:23:11 AM	{                                        "media_resource.tab":107,                                "taxon.tab":107, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
91155_ENV	Tue 2021-07-13 07:28:06 {"measurement_or_fact_specific.tab":665, "media_resource.tab":107, "occurrence_specific.tab":665, "taxon.tab":107, "time_elapsed":{"sec":174.68, "min":2.91, "hr":0.05}}
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
parse_unstructured_text_memoirs.php _ '{"resource_id": "15423", "resource_name":"1st BHL"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "91155", "resource_name":"2nd BHL"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "15427", "resource_name":"3nd BHL"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS["ENV_DEBUG"] = true;
require_library('connectors/ParseListTypeAPI_Memoirs');
require_library('connectors/ParseUnstructuredTextAPI_Memoirs');
$timestart = time_elapsed();
// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$pdf_id = $param['resource_id'];
$resource_name = $param['resource_name'];
$func = new ParseUnstructuredTextAPI_Memoirs($resource_name);
/*
$str = "Diplocheila (s. str.) daldorfi Crotch";
$words = explode(" ", $str);
print_r($words);
if($words[1] == "(s.") unset($words[1]);
if($words[2] == "str.)") unset($words[2]);
$str = implode(" ", $words);
exit("\n[$str]\n");
*/
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
    119187 --- blocks: 72   Raw scinames: 241
    118978 --- blocks: 97   Raw scinames: 106
    */
}
$rec['30355'] = array('filename' => '30355.txt', 'lines_before_and_after_sciname' => 1); /* blocks: 2611   Raw scinames: 2641 */
$rec['118950'] = array('filename' => '118950.txt', 'lines_before_and_after_sciname' => 2); /* blocks: 56   Raw scinames: 56 */
$rec['118941'] = array('filename' => '118941.txt', 'lines_before_and_after_sciname' => 1); /* blocks: 99   Raw scinames: 101 */

// === START BHL RESOURCES ===
$rec['15423'] = array('filename' => '15423.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'BHL'); /*1 blocks: 73|75    Raw scinames: 96|96 */
$rec['91155'] = array('filename' => '91155.txt', 'lines_before_and_after_sciname' => 1, 'doc' => 'BHL'); /*2 blocks: 105|108   Raw scinames: 124|124 */
$rec['15427'] = array('filename' => '15427.txt', 'lines_before_and_after_sciname' => 2, 'doc' => 'BHL'); /*3 blocks: 158   Raw scinames: 173 */

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