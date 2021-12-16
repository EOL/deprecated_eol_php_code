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
118935	    Wed 2021-09-29 01:07:53 AM	{               "media.tab":1346,                     "taxon.tab":1345, "time_elapsed":{"sec":1.07, "min":0.02, "hr":0}}
118935_ENV	Wed 2021-09-29 01:10:08 AM	{"MoF.tab":1489,                    "occur.tab":1489, "taxon.tab":1345, "time_elapsed":{"sec":75, "min":1.25, "hr":0.02}}
DATA-1891:
118935	    Thu 2021-10-07 09:28:43 AM	{               "media.tab":1346,                     "taxon.tab":1345, "time_elapsed":{"sec":1.2, "min":0.02, "hr":0}}
118935_ENV	Thu 2021-10-07 09:30:17 AM	{"MoF.tab":1489,                    "occur.tab":1489, "taxon.tab":1345, "time_elapsed":{"sec":33.8, "min":0.56, "hr":0.01}}
gnfinder:
118935	    Tue 2021-10-19 10:04:43 AM	{               "media.tab":1346,                     "taxon.tab":1342, "time_elapsed":{"sec":4.26, "min":0.07, "hr":0}}
118935_ENV	Tue 2021-10-19 10:08:41 AM	{"MoF.tab":1489,                    "occur.tab":1489, "taxon.tab":1342, "time_elapsed":{"sec":177.77, "min":2.96, "hr":0.05}}

------------------------------------------------------------
120081	    Tue 2021-07-13 09:14:50 AM	{               "media.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.39, "min":0.01, "hr":0}}
120081_ENV	Tue 2021-07-13 09:17:10 AM	{"MoF.tab":526, "media.tab":95, "occur.tab":526, "taxon.tab":95, "time_elapsed":{"sec":20.07, "min":0.33, "hr":0.01}}
120081	    Wed 2021-09-29 01:05:17 AM	{               "media.tab":97,                  "taxon.tab":97, "time_elapsed":{"sec":0.42, "min":0.01, "hr":0}}
120081_ENV	Wed 2021-09-29 01:09:06 AM	{"MoF.tab":532, "media.tab":97, "occur.tab":532, "taxon.tab":97, "time_elapsed":{"sec":109.53, "min":1.83, "hr":0.03}}
gnfinder:
120081	    Tue 2021-10-19 10:10:42 AM	{               "media.tab":97,                  "taxon.tab":97, "time_elapsed":{"sec":0.63, "min":0.01, "hr":0}}
120081_ENV	Tue 2021-10-19 10:13:21 AM	{"MoF.tab":532, "media.tab":97, "occur.tab":532, "taxon.tab":97, "time_elapsed":{"sec":38.85, "min":0.65, "hr":0.01}}
------------------------------------------------------------
120082	    Wed 2021-07-07 01:45:01 AM	{              "media.tab":25,                 "taxon.tab":25, "time_elapsed":{"sec":0.34, "min":0.01, "hr":0}}
120082_ENV	Wed 2021-07-07 01:47:37 AM	{"MoF.tab":61, "media.tab":25, "occur.tab":61, "taxon.tab":25, "time_elapsed":{"sec":36.02, "min":0.6, "hr":0.01}}
120082	    Wed 2021-09-29 01:23:45 AM	{              "media.tab":27,                 "taxon.tab":27, "time_elapsed":{"sec":0.35, "min":0.01, "hr":0}}
120082_ENV	Wed 2021-09-29 01:26:20 AM	{"MoF.tab":67, "media.tab":27, "occur.tab":67, "taxon.tab":27, "time_elapsed":{"sec":34.31, "min":0.57, "hr":0.01}}
Mac mini
120082	    Tue 2021-10-19 02:47:01 AM	{              "media.tab":27,                 "taxon.tab":27, "time_elapsed":{"sec":0.7, "min":0.01, "hr":0}}

------------------------------------------------------------
118986	    Wed 2021-07-07 01:48:10 AM	{               "media.tab":41,                  "taxon.tab":41, "time_elapsed":{"sec":2.33, "min":0.04, "hr":0}}
118986_ENV	Wed 2021-07-07 01:50:39 AM	{"MoF.tab":511, "media.tab":41, "occur.tab":511, "taxon.tab":41, "time_elapsed":{"sec":28.31, "min":0.47, "hr":0.01}}
118986	    Wed 2021-09-29 01:26:40 AM	{               "media.tab":44,                  "taxon.tab":44, "time_elapsed":{"sec":0.42, "min":0.01, "hr":0}}
118986_ENV	Wed 2021-09-29 01:29:47 AM	{"MoF.tab":545, "media.tab":44, "occur.tab":545, "taxon.tab":44, "time_elapsed":{"sec":66.78, "min":1.11, "hr":0.02}}
------------------------------------------------------------
118920	    Wed 2021-09-29 01:32:51 AM	{               "media.tab":39,                     "taxon.tab":39, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
118920_ENV	Wed 2021-09-29 01:36:35 AM	{"MoF.tab":85,  "media.tab":39, "occur.tab":85,     "taxon.tab":39, "time_elapsed":{"sec":164.69, "min":2.74, "hr":0.05}}
gnfinder:
118920	    Tue 2021-10-19 10:21:20 AM	{               "media.tab":39,                     "taxon.tab":39, "time_elapsed":{"sec":0.39, "min":0.01, "hr":0}}
118920_ENV	Tue 2021-10-19 10:22:40 AM	{"MoF.tab":85,  "media.tab":39, "occur.tab":85,     "taxon.tab":39, "time_elapsed":{"sec":20.26, "min":0.34, "hr":0.01}}
118920	    Thu 2021-10-21 08:58:59 AM	{               "media.tab":39,                     "taxon.tab":39, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
118920_ENV	Thu 2021-10-21 09:00:08 AM	{"MoF.tab":85,  "media.tab":39, "occur.tab":85,     "taxon.tab":39, "time_elapsed":{"sec":8.83, "min":0.15, "hr":0}}

------------------------------------------------------------
120083	    Wed 2021-07-07 01:54:01 AM	{               "media.tab":379,                  "taxon.tab":294, "time_elapsed":{"sec":0.57, "min":0.01, "hr":0}}
120083_ENV	Wed 2021-07-07 01:56:33 AM	{"MoF.tab":752, "media.tab":186, "occur.tab":752, "taxon.tab":294, "time_elapsed":{"sec":31.82, "min":0.53, "hr":0.01}}
120083	    Wed 2021-09-29 06:55:15 AM	{               "media.tab":381,                  "taxon.tab":295, "time_elapsed":{"sec":0.58, "min":0.01, "hr":0}}
120083_ENV	Wed 2021-09-29 06:57:38 AM	{"MoF.tab":774, "media.tab":188, "occur.tab":774, "taxon.tab":295, "time_elapsed":{"sec":23.57, "min":0.39, "hr":0.01}}
Mac mini
120083	    Tue 2021-10-19 04:18:05 AM	{               "media.tab":381,                  "taxon.tab":293, "time_elapsed":{"sec":1.07, "min":0.02, "hr":0}}
------------------------------------------------------------
118237	    Wed 2021-07-07 01:57:24 AM	{               "media.tab":46,                     "taxon.tab":33, "time_elapsed":{"sec":0.37, "min":0.01, "hr":0}}
118237_ENV	Wed 2021-07-07 02:06:23 AM	{"MoF.tab":596, "media.tab":46, "occur.tab":596,    "taxon.tab":33, "time_elapsed":{"sec":418.59, "min":6.98, "hr":0.12}}
118237	    Wed 2021-09-29 07:02:16 AM	{               "media.tab":46,                     "taxon.tab":33, "time_elapsed":{"sec":0.43, "min":0.01, "hr":0}}
118237_ENV	Wed 2021-09-29 07:04:38 AM	{"MoF.tab":596, "media.tab":46, "occur.tab":596,    "taxon.tab":33, "time_elapsed":{"sec":21.87, "min":0.36, "hr":0.01}}
------------------------------------------------------------
MoftheAES_resources	Wed 2021-07-07 07:02:49 AM	{"MoF.tab":3995, "media.tab":420, "occur.tab":3995, "taxon.tab":1823, "time_elapsed":{"sec":67.24, "min":1.12, "hr":0.02}}
------------------------------------------------------------ others MotAES
30355	    Tue 2021-07-13 11:16:07 AM	{                                       "media.tab":2625,   "taxon.tab":2622, "time_elapsed":{"sec":1.99, "min":0.03, "hr":0}}
30355_ENV	Tue 2021-07-13 11:44:07     {"MoF.tab":2566,    "occur.tab":2566,                       "taxon.tab":2622, "time_elapsed":{"sec":1559.58, "min":25.99, "hr":0.43}}
30355	    Thu 2021-07-15 10:16:35 AM	{                                       "media.tab":2601,   "taxon.tab":2598, "time_elapsed":{"sec":1.93, "min":0.03, "hr":0}}
30355_ENV	Thu 2021-07-15 10:19:42 AM	{"MoF.tab":2566,    "occur.tab":2566,                       "taxon.tab":2598, "time_elapsed":{"sec":67.31, "min":1.12, "hr":0.02}}
30355	    Wed 2021-09-29 10:51:53 AM	{                                       "media.tab":2631,   "taxon.tab":2628, "time_elapsed":{"sec":1.96, "min":0.03, "hr":0}}
30355_ENV	Wed 2021-09-29 10:54:20 AM	{"MoF.tab":2571,    "occur.tab":2571,                       "taxon.tab":2628, "time_elapsed":{"sec":26.77, "min":0.45, "hr":0.01}}

27822	    Thu 2021-07-15 10:14:21 AM	{               "media_resource.tab":85,                    "taxon.tab":71, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
27822_ENV	Thu 2021-07-15 10:16:52     {"MoF.tab":115, "media_resource.tab":85, "occur.tab":115,   "taxon.tab":71, "time_elapsed":{"sec":30.27, "min":0.5, "hr":0.01}}
27822	    Thu 2021-07-15 10:57:31 AM	{               "media_resource.tab":84,                    "taxon.tab":70, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
27822_ENV	Thu 2021-07-15 10:59:51 AM	{"MoF.tab":115, "media_resource.tab":84, "occur.tab":115,   "taxon.tab":70, "time_elapsed":{"sec":19.35, "min":0.32, "hr":0.01}}
27822	    Wed 2021-09-29 06:35:15 AM	{               "media_resource.tab":81,                    "taxon.tab":70, "time_elapsed":{"sec":0.42, "min":0.01, "hr":0}}
27822_ENV	Wed 2021-09-29 06:43:52 AM	{"MoF.tab":282, "media_resource.tab":81, "occur.tab":282,   "taxon.tab":70, "time_elapsed":{"sec":396.49, "min":6.61, "hr":0.11}}

30354	    Thu 2021-07-15 10:14:35 AM	{               "media_resource.tab":87,                    "taxon.tab":87, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
30354_ENV	Thu 2021-07-15 10:17:08     {"MoF.tab":85,  "media_resource.tab":87, "occur.tab":85,    "taxon.tab":87, "time_elapsed":{"sec":33.06, "min":0.55, "hr":0.01}}
30354	    Thu 2021-07-15 10:57:47 AM	{               "media_resource.tab":87,                    "taxon.tab":87, "time_elapsed":{"sec":0.39, "min":0.01, "hr":0}}
30354_ENV	Thu 2021-07-15 11:00:02 AM	{"MoF.tab":85,  "media_resource.tab":87, "occur.tab":85,    "taxon.tab":87, "time_elapsed":{"sec":15.58, "min":0.26, "hr":0}}
30354	    Wed 2021-09-29 07:34:19 AM	{               "media_resource.tab":89,                    "taxon.tab":89, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
30354_ENV	Wed 2021-09-29 07:39:51 AM	{"MoF.tab":115, "media_resource.tab":89, "occur.tab":115,   "taxon.tab":89, "time_elapsed":{"sec":211.45, "min":3.52, "hr":0.06}}

119035	    Tue 2021-07-20 05:22:11 AM	{               "media.tab":56,                     "taxon.tab":56, "time_elapsed":{"sec":0.39, "min":0.01, "hr":0}}
119035_ENV	Tue 2021-07-20 05:24:23 AM	{"MoF.tab":169, "media.tab":56, "occur.tab":169,    "taxon.tab":56, "time_elapsed":{"sec":12.3, "min":0.21, "hr":0}}
119035	    Wed 2021-09-29 11:24:36 AM	{               "media.tab":56,                     "taxon.tab":56, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
119035_ENV	Wed 2021-09-29 11:25:30 AM	{"MoF.tab":174, "media.tab":56, "occur.tab":174,    "taxon.tab":56, "time_elapsed":{"sec":23.65, "min":0.39, "hr":0.01}}
-----------------------------------------------------
118936	    Tue 2021-07-20 05:21:53 AM	{              "media_resource.tab":14,                                 "taxon.tab":14, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118936_ENV	Tue 2021-07-20 05:24:07 AM	{"MoF.tab":63, "media_resource.tab":14, "occurrence_specific.tab":63,   "taxon.tab":14, "time_elapsed":{"sec":14.65, "min":0.24, "hr":0}}
118936	    Wed 2021-09-29 05:49:00 AM	{              "media_resource.tab":14,                                 "taxon.tab":14, "time_elapsed":{"sec":0.36, "min":0.01, "hr":0}}
118936_ENV	Wed 2021-09-29 05:51:08 AM	{"MoF.tab":63, "media_resource.tab":14, "occurrence_specific.tab":63,   "taxon.tab":14, "time_elapsed":{"sec":7.13, "min":0.12, "hr":0}}
-----------------------------------------------------
118946	    Tue 2021-07-20 05:22:44 AM	{               "media.tab":102,                        "taxon.tab":101, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118946_ENV	Tue 2021-07-20 05:24:58 AM	{"MoF.tab":639, "media.tab":102,    "occur.tab":639,    "taxon.tab":101, "time_elapsed":{"sec":14.1, "min":0.24, "hr":0}}
118946	    Wed 2021-09-29 09:55:19 AM	{               "media.tab":102,                        "taxon.tab":101, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118946_ENV	Wed 2021-09-29 09:56:57 AM	{"MoF.tab":671, "media.tab":102,    "occur.tab":671,    "taxon.tab":101, "time_elapsed":{"sec":67.94, "min":1.13, "hr":0.02}}

118950	    Tue 2021-07-20 05:22:18 AM	{               "media.tab":55,                     "taxon.tab":55, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
118950_ENV	Tue 2021-07-20 05:24:31     {"MoF.tab":151, "media.tab":55, "occur.tab":151,    "taxon.tab":55, "time_elapsed":{"sec":12.86, "min":0.21, "hr":0}}
118950	    Tue 2021-10-05 11:42:43 AM	{               "media.tab":56,                     "taxon.tab":56, "time_elapsed":{"sec":0.43, "min":0.01, "hr":0}}
118950_ENV	Tue 2021-10-05 11:44:49 AM	{"MoF.tab":155, "media.tab":56, "occur.tab":155,    "taxon.tab":56, "time_elapsed":{"sec":6.14, "min":0.1, "hr":0}}

120602	    Wed 2021-07-21 11:08:20 AM	{                           "media_resource.tab":20,    "taxon.tab":20, "time_elapsed":{"sec":0.33, "min":0.01, "hr":0}}
120602_ENV	Wed 2021-07-21 11:10:48     {"MoF.tab":4, "occur.tab":4,                            "taxon.tab":20, "time_elapsed":{"sec":28.63, "min":0.48, "hr":0.01}}
120602	    Wed 2021-09-29 06:17:41 AM	{                           "media_resource.tab":20,    "taxon.tab":20, "time_elapsed":{"sec":0.34, "min":0.01, "hr":0}}
120602_ENV	Wed 2021-09-29 06:19:47 AM	{"MoF.tab":4, "occur.tab":4,                            "taxon.tab":20, "time_elapsed":{"sec":5.51, "min":0.09, "hr":0}}

119187	    Mon 2021-07-26 10:47:26 AM	{               "media_resource.tab":38,                    "taxon.tab":30, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
119187_ENV	Mon 2021-07-26 10:49:40 AM	{"MoF.tab":139, "media_resource.tab":38, "occur.tab":139,   "taxon.tab":30, "time_elapsed":{"sec":13.65, "min":0.23, "hr":0}}
119187	    Wed 2021-09-29 11:11:55 AM	{               "media_resource.tab":39,                    "taxon.tab":30, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
119187_ENV	Wed 2021-09-29 11:12:34 AM	{"MoF.tab":130, "media_resource.tab":39, "occur.tab":130,   "taxon.tab":30, "time_elapsed":{"sec":9.63, "min":0.16, "hr":0}}
119187	    Wed 2021-10-20 11:43:58 AM	{               "media_resource.tab":47,                    "taxon.tab":35, "time_elapsed":{"sec":0.59, "min":0.01, "hr":0}}
119187_ENV	Wed 2021-10-20 11:44:41 AM	{"MoF.tab":150, "media_resource.tab":47, "occur.tab":150,   "taxon.tab":35, "time_elapsed":{"sec":12.01, "min":0.2, "hr":0}}
119187	    Sun 2021-10-31 10:02:51 PM	{               "media_resource.tab":40,                    "taxon.tab":30, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
119187_ENV	Sun 2021-10-31 10:03:30 PM	{"MoF.tab":127, "media_resource.tab":40, "occur.tab":127,   "taxon.tab":30, "time_elapsed":{"sec":8.64, "min":0.14, "hr":0}}
119187	    Mon 2021-11-08 12:59:07 AM	{               "media_resource.tab":40,                    "taxon.tab":30, "time_elapsed":{"sec":0.5, "min":0.01, "hr":0}}
119187_ENV	Mon 2021-11-08 12:59:44 AM	{"MoF.tab":127, "media_resource.tab":40, "occur.tab":127,   "taxon.tab":30, "time_elapsed":{"sec":6.93, "min":0.12, "hr":0}}

118941	    Tue 2021-07-27 10:30:58 AM	{               "media_resource.tab":94,                    "taxon.tab":94, "time_elapsed":{"sec":0.49, "min":0.01, "hr":0}}
118941_ENV	Tue 2021-07-27 10:43:12 AM	{"MoF.tab":351, "media_resource.tab":94, "occur.tab":351,   "taxon.tab":94, "time_elapsed":{"sec":614.26, "min":10.24, "hr":0.17}}
118941	    Wed 2021-09-29 10:54:33 AM	{               "media_resource.tab":99,                    "taxon.tab":99, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118941_ENV	Wed 2021-09-29 10:56:39 AM	{"MoF.tab":360, "media_resource.tab":99, "occur.tab":360,   "taxon.tab":99, "time_elapsed":{"sec":6.07, "min":0.1, "hr":0}}

118978	    Tue 2021-07-27 10:54:30 AM	{               "media.tab":86,                                 "taxon.tab":82, "time_elapsed":{"sec":0.42, "min":0.01, "hr":0}}
118978_ENV	Tue 2021-07-27 10:56:44 AM	{"MoF.tab":616, "media.tab":86, "occurrence_specific.tab":616,  "taxon.tab":82, "time_elapsed":{"sec":14.12, "min":0.24, "hr":0}}
118978	    Thu 2021-07-29 08:22:33 AM	{               "media.tab":80,                                 "taxon.tab":78, "time_elapsed":{"sec":0.62, "min":0.01, "hr":0}}
118978_ENV	Thu 2021-07-29 08:24:56 AM	{"MoF.tab":617, "media.tab":80, "occurrence_specific.tab":617,  "taxon.tab":78, "time_elapsed":{"sec":22.83, "min":0.38, "hr":0.01}}
118978	    Wed 2021-09-29 10:53:52 AM	{               "media.tab":81,                                 "taxon.tab":79, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
118978_ENV	Wed 2021-09-29 10:56:00 AM	{"MoF.tab":627, "media.tab":81, "occurrence_specific.tab":627,  "taxon.tab":79, "time_elapsed":{"sec":7.25, "min":0.12, "hr":0}}

119188	    Wed 2021-07-28 09:36:06 AM	{               "media_resource.tab":182,                   "taxon.tab":177, "time_elapsed":{"sec":0.53, "min":0.01, "hr":0}}
119188_ENV	Wed 2021-07-28 09:38:30 AM	{"MoF.tab":890, "media_resource.tab":182, "occur.tab":890,  "taxon.tab":177, "time_elapsed":{"sec":24.02, "min":0.4, "hr":0.01}}
119188	    Wed 2021-09-29 06:10:52 AM	{               "media_resource.tab":184,                   "taxon.tab":179, "time_elapsed":{"sec":0.54, "min":0.01, "hr":0}}
119188_ENV	Wed 2021-09-29 06:12:59 AM	{"MoF.tab":910, "media_resource.tab":184, "occur.tab":910,  "taxon.tab":179, "time_elapsed":{"sec":6.58, "min":0.11, "hr":0}}

119520	    Wed 2021-07-28 10:22:25 AM	{                   "media_resource.tab":675,                   "taxon.tab":675, "time_elapsed":{"sec":0.82, "min":0.01, "hr":0}}
119520_ENV	Wed 2021-07-28 10:25:25 AM	{"MoF.tab":2301,    "media_resource.tab":675, "occur.tab":2301, "taxon.tab":675, "time_elapsed":{"sec":60.5, "min":1.01, "hr":0.02}}
119520	    Thu 2021-07-29 08:24:28 AM	{                   "media_resource.tab":676,                   "taxon.tab":676, "time_elapsed":{"sec":0.82, "min":0.01, "hr":0}}
119520_ENV	Thu 2021-07-29 08:26:51 AM	{"MoF.tab":2302,    "media_resource.tab":676, "occur.tab":2302, "taxon.tab":676, "time_elapsed":{"sec":22.88, "min":0.38, "hr":0.01}}
119520	    Wed 2021-09-29 06:13:12 AM	{                   "media_resource.tab":677,                   "taxon.tab":677, "time_elapsed":{"sec":0.79, "min":0.01, "hr":0}}
119520_ENV	Wed 2021-09-29 06:15:20 AM	{"MoF.tab":2266,    "media_resource.tab":677, "occur.tab":2266, "taxon.tab":677, "time_elapsed":{"sec":7.99, "min":0.13, "hr":0}}
119520	    Wed 2021-09-29 10:55:02 AM	{                   "media_resource.tab":677,                   "taxon.tab":677, "time_elapsed":{"sec":0.81, "min":0.01, "hr":0}}
119520_ENV	Wed 2021-09-29 10:57:13 AM	{"MoF.tab":2278,    "media_resource.tab":677, "occur.tab":2278, "taxon.tab":677, "time_elapsed":{"sec":10.82, "min":0.18, "hr":0}}
gnfinder
119520	    Wed 2021-10-20 11:49:29 AM	{                   "media_resource.tab":667,                   "taxon.tab":667, "time_elapsed":{"sec":0.99, "min":0.02, "hr":0}}
119520_ENV	Wed 2021-10-20 11:52:10 AM	{"MoF.tab":2282,    "media_resource.tab":667, "occur.tab":2282, "taxon.tab":667, "time_elapsed":{"sec":40.88, "min":0.68, "hr":0.01}}

MoftheAES_resources	Thu 2021-07-29 09:06:03 AM	{"MoF.tab":12098, "media.tab":1889, "occurrence.tab":12098, "taxon.tab":5853, "time_elapsed":{"sec":118.6, "min":1.98, "hr":0.03}}
MoftheAES_resources	Mon 2021-08-09 11:10:28 AM	{"MoF.tab":12098, "media.tab":1889, "occurrence.tab":12098, "taxon.tab":5853, "time_elapsed":{"sec":115.66, "min":1.93, "hr":0.03}}
MoftheAES_resources	Wed 2021-09-29 09:56:19 PM	{"MoF.tab":12428, "media.tab":1919, "occurrence.tab":12428, "taxon.tab":5951, "time_elapsed":{"sec":47.55, "min":0.79, "hr":0.01}}
MoftheAES_resources	Thu 2021-10-07 10:11:44 AM	{"MoF.tab":12428, "media.tab":1919, "occurrence.tab":12428, "taxon.tab":5951, "time_elapsed":{"sec":39.96, "min":0.67, "hr":0.01}}
gnfinder
MoftheAES_resources	Wed 2021-10-20 11:09:38 AM	{"MoF.tab":12356, "media.tab":1884, "occurrence.tab":12356, "taxon.tab":5905, "time_elapsed":{"sec":50.32, "min":0.84, "hr":0.01}}
MoftheAES_resources	Wed 2021-10-20 12:15:09 PM	{"MoF.tab":12356, "media.tab":1884, "occurrence.tab":12356, "taxon.tab":5905, "time_elapsed":{"sec":73.79, "min":1.23, "hr":0.02}}
MoftheAES_resources	Thu 2021-10-21 08:19:16 AM	{"MoF.tab":12472, "media.tab":1983, "occurrence.tab":12472, "taxon.tab":6017, "time_elapsed":{"sec":42.03, "min":0.7, "hr":0.01}}
MoftheAES_resources	Thu 2021-10-21 09:39:42 AM	{"MoF.tab":12483, "media.tab":1983, "occurrence.tab":12483, "taxon.tab":6017, "time_elapsed":{"sec":39.71, "min":0.66, "hr":0.01}}
MoftheAES_resources	Fri 2021-10-22 12:15:53 AM	{"MoF.tab":12483, "media.tab":1983, "occurrence.tab":12483, "taxon.tab":6017, "time_elapsed":{"sec":42.2, "min":0.7, "hr":0.01}}
not relaxed - is good
MoftheAES_resources	Fri 2021-10-22 01:29:37 AM	{"MoF.tab":12363, "media.tab":1935, "occurrence.tab":12363, "taxon.tab":5967, "time_elapsed":{"sec":37.48, "min":0.62, "hr":0.01}}
assoc true gnfinder
MoftheAES_resources	Wed 2021-10-27 04:29:20 AM	{"MoF.tab":12363, "media.tab":1935, "occurrence.tab":12363, "taxon.tab":5967, "time_elapsed":{"sec":87.9, "min":1.47, "hr":0.02}}
after first review: size patterns
MoftheAES_resources	Thu 2021-11-11 08:19:53 AM	{"MoF.tab":12377, "media.tab":1935, "occurrence.tab":12377, "taxon.tab":5967, "time_elapsed":{"sec":38.67, "min":0.64, "hr":0.01}}
MoftheAES_resources	Thu 2021-12-02 08:12:32 AM	{"MoF.tab":12381, "media.tab":1935, "occurrence.tab":12381, "taxon.tab":5967, "time_elapsed":{"sec":40.17, "min":0.67, "hr":0.01}}

------------------------------------------------------------ North American Flora (DATA-1890) --- BHL - 7 documents
15423	    Tue 2021-07-13 07:19:04     {               "media_resource.tab":73,                   "taxon.tab":73, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
15423_ENV	Tue 2021-07-13 07:23:01     {"MoF.tab":338, "media_resource.tab":73, "occur.tab":338,  "taxon.tab":73, "time_elapsed":{"sec":115.98, "min":1.93, "hr":0.03}}
15423	    Tue 2021-09-28 05:03:49     {               "media_resource.tab":75,                   "taxon.tab":75, "time_elapsed":{"sec":3.2, "min":0.05, "hr":0}}
15423_ENV	Tue 2021-09-28 05:07:48     {"MoF.tab":347, "media_resource.tab":75, "occur.tab":347,  "taxon.tab":75, "time_elapsed":{"sec":118.99, "min":1.98, "hr":0.03}}
15423	    Thu 2021-09-30 09:23:55 AM	{               "media_resource.tab":75,                   "taxon.tab":75, "time_elapsed":{"sec":0.37, "min":0.01, "hr":0}}
15423_ENV	Thu 2021-09-30 09:24:32 AM	{"MoF.tab":289, "media_resource.tab":75, "occur.tab":289,  "taxon.tab":75, "time_elapsed":{"sec":6.82, "min":0.11, "hr":0}}
15423	    Wed 2021-10-20 10:55:28 AM	{               "media_resource.tab":76,                   "taxon.tab":76, "time_elapsed":{"sec":0.99, "min":0.02, "hr":0}}
15423_ENV	Wed 2021-10-20 10:56:20 AM	{"MoF.tab":342, "media_resource.tab":76, "occur.tab":342,  "taxon.tab":76, "time_elapsed":{"sec":21.77, "min":0.36, "hr":0.01}}
15423	    Sun 2021-10-31 09:38:30 PM	{               "media_resource.tab":76,                   "taxon.tab":76, "time_elapsed":{"sec":0.89, "min":0.01, "hr":0}}
15423_ENV	Sun 2021-10-31 09:39:14 PM	{"MoF.tab":342, "media_resource.tab":76, "occur.tab":342,  "taxon.tab":76, "time_elapsed":{"sec":13.72, "min":0.23, "hr":0}}
15423	    Mon 2021-11-08 02:22:06 AM	{               "media_resource.tab":76,                   "taxon.tab":76, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
15423_ENV	Mon 2021-11-08 02:22:44 AM	{"MoF.tab":342, "media_resource.tab":76, "occur.tab":342,  "taxon.tab":76, "time_elapsed":{"sec":7.96, "min":0.13, "hr":0}}
Mac mini:
15423	    Mon 2021-10-18 11:01:18 PM	{               "media_resource.tab":76,                   "taxon.tab":77, "time_elapsed":{"sec":0.73, "min":0.01, "hr":0}}
------------------------------------------------------------
91155	    Tue 2021-07-13 07:23:11     {               "media_resource.tab":107,                   "taxon.tab":107, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
91155_ENV	Tue 2021-07-13 07:28:06     {"MoF.tab":665, "media_resource.tab":107, "occur.tab":665,  "taxon.tab":107, "time_elapsed":{"sec":174.68, "min":2.91, "hr":0.05}}
91155	    Thu 2021-09-30 02:11:53 AM	{               "media_resource.tab":105,                   "taxon.tab":105, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
91155_ENV	Thu 2021-09-30 02:12:30 AM	{"MoF.tab":646, "media_resource.tab":105, "occur.tab":646,  "taxon.tab":105, "time_elapsed":{"sec":6.67, "min":0.11, "hr":0}}
91155	    Thu 2021-09-30 09:24:46 AM	{               "media_resource.tab":108,                   "taxon.tab":108, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
91155_ENV	Thu 2021-09-30 09:25:22 AM	{"MoF.tab":661, "media_resource.tab":108, "occur.tab":661,  "taxon.tab":108, "time_elapsed":{"sec":6, "min":0.1, "hr":0}}

15427	    Tue 2021-08-03 10:10:02     {               "media_resource.tab":152,                  "taxon.tab":152, "time_elapsed":{"sec":1.12, "min":0.02, "hr":0}}
15427_ENV	Tue 2021-08-03 10:12:20     {"MoF.tab":426, "media_resource.tab":152, "occur.tab":426, "taxon.tab":152, "time_elapsed":{"sec":18, "min":0.3, "hr":0.01}}
15427	    Thu 2021-09-30 09:25:37 AM	{               "media_resource.tab":156,                  "taxon.tab":156, "time_elapsed":{"sec":0.44, "min":0.01, "hr":0}}
15427_ENV	Thu 2021-09-30 09:26:24 AM	{"MoF.tab":428, "media_resource.tab":156, "occur.tab":428, "taxon.tab":156, "time_elapsed":{"sec":17.74, "min":0.3, "hr":0}}

15428	    Tue 2021-08-03 10:12:35     {               "media_resource.tab":190,                  "taxon.tab":190, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
15428_ENV	Tue 2021-08-03 10:14:56     {"MoF.tab":759, "media_resource.tab":190, "occur.tab":759, "taxon.tab":190, "time_elapsed":{"sec":20.34, "min":0.34, "hr":0.01}}
15428	    Thu 2021-09-30 09:26:38 AM	{               "media_resource.tab":194,                  "taxon.tab":194, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
15428_ENV	Thu 2021-09-30 09:27:15 AM	{"MoF.tab":766, "media_resource.tab":194, "occur.tab":766, "taxon.tab":194, "time_elapsed":{"sec":7.19, "min":0.12, "hr":0}}

91144	    Tue 2021-08-03 10:15:15 PM	{               "media_resource.tab":192,                  "taxon.tab":192, "time_elapsed":{"sec":0.48, "min":0.01, "hr":0}}
91144_ENV	Tue 2021-08-03 10:17:34     {"MoF.tab":829, "media_resource.tab":192, "occur.tab":829, "taxon.tab":192, "time_elapsed":{"sec":18.66, "min":0.31, "hr":0.01}}
91144	    Thu 2021-09-30 07:30:41 AM	{               "media_resource.tab":196,                  "taxon.tab":196, "time_elapsed":{"sec":0.47, "min":0.01, "hr":0}}
91144_ENV	Thu 2021-09-30 07:31:19 AM	{"MoF.tab":842, "media_resource.tab":196, "occur.tab":842, "taxon.tab":196, "time_elapsed":{"sec":8.36, "min":0.14, "hr":0}}
91144	    Thu 2021-09-30 09:27:28 AM	{               "media_resource.tab":198,                  "taxon.tab":198, "time_elapsed":{"sec":0.47, "min":0.01, "hr":0}}
91144_ENV	Thu 2021-09-30 09:28:15 AM	{"MoF.tab":869, "media_resource.tab":198, "occur.tab":869, "taxon.tab":198, "time_elapsed":{"sec":17.5, "min":0.29, "hr":0}}
start gnfinder
91144	    Tue 2021-10-19 04:42:27 AM	{               "media_resource.tab":199,                  "taxon.tab":199, "time_elapsed":{"sec":0.62, "min":0.01, "hr":0}}
91144_ENV	Tue 2021-10-19 04:43:51 AM	{"MoF.tab":871, "media_resource.tab":199, "occur.tab":871, "taxon.tab":199, "time_elapsed":{"sec":54.3, "min":0.91, "hr":0.02}}

91225	Thu 2021-08-05 12:07:49 PM	{"assoc.tab":4275, "occurrence.tab":4693, "taxon.tab":4859, "time_elapsed":{"sec":6.39, "min":0.11, "hr":0}}
91225	Thu 2021-08-12 05:38:11 AM	{"assoc.tab":4343, "occurrence.tab":4762, "taxon.tab":4932, "time_elapsed":{"sec":124.63, "min":2.08, "hr":0.03}}
91225	Mon 2021-09-27 01:12:13 PM	{"assoc.tab":4326, "occurrence.tab":4751, "taxon.tab":4931, "time_elapsed":{"sec":10.51, "min":0.18, "hr":0}}
91225	Thu 2021-09-30 09:28:56 AM	{"assoc.tab":4330, "occurrence.tab":4755, "taxon.tab":4934, "time_elapsed":{"sec":9.01, "min":0.15, "hr":0}}
91225	Thu 2021-10-07 09:21:18 AM	{"assoc.tab":4330, "occurrence.tab":4755, "taxon.tab":4934, "time_elapsed":{"sec":17.77, "min":0.3, "hr":0}}
91225	Tue 2021-10-19 06:57:53 AM	{"assoc.tab":5094, "occurrence.tab":5503, "taxon.tab":5811, "time_elapsed":{"sec":2252.17, "min":37.54, "hr":0.63}}

91362	            Mon 2021-08-09 05:21:21 AM	{"assoc.tab":486,                                         "occur.tab":624,          "taxon.tab":656, "time_elapsed":{"sec":6.09, "min":0.1, "hr":0}}
91362_species	    Mon 2021-08-09 05:22:06 AM	{                                "media_resource.tab":56,                           "taxon.tab":56, "time_elapsed":{"sec":0.36, "min":0.01, "hr":0}}
91362_species_ENV	Mon 2021-08-09 05:24:21 AM	{                 "MoF.tab":182, "media_resource.tab":56, "occur_specific.tab":182, "taxon.tab":56, "time_elapsed":{"sec":14.46, "min":0.24, "hr":0}}
91362_resource	    Mon 2021-08-09 06:49:48 AM	{"assoc.tab":486, "MoF.tab":182, "media_resource.tab":56, "occur_specific.tab":806, "taxon.tab":684, "time_elapsed":{"sec":11.54, "min":0.19, "hr":0}}
-------------Slight increase after a few months:
91362	            Thu 2021-09-30 07:34:37 AM	{"assoc.tab":491,                                         "occur.tab":630,          "taxon.tab":662, "time_elapsed":{"sec":4.72, "min":0.08, "hr":0}}
91362_species	    Thu 2021-09-30 07:34:54 AM	{                                "media_resource.tab":58,                           "taxon.tab":58, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
91362_species_ENV	Thu 2021-09-30 07:35:31 AM	{                 "MoF.tab":264, "media_resource.tab":58, "occur_specific.tab":264, "taxon.tab":58, "time_elapsed":{"sec":6.5, "min":0.11, "hr":0}}
91362_resource	    Thu 2021-09-30 07:35:35 AM	{"assoc.tab":491, "MoF.tab":264, "media_resource.tab":58, "occur_specific.tab":894, "taxon.tab":691, "time_elapsed":{"sec":3.73, "min":0.06, "hr":0}}
-------------
91362	            Thu 2021-09-30 09:29:16 AM	{"assoc.tab":491,                                         "occur.tab":630,          "taxon.tab":662, "time_elapsed":{"sec":2.94, "min":0.05, "hr":0}}
91362_species	    Thu 2021-09-30 09:29:31 AM	{                                "media_resource.tab":61,                           "taxon.tab":61, "time_elapsed":{"sec":0.36, "min":0.01, "hr":0}}
91362_species_ENV	Thu 2021-09-30 09:30:11 AM	{                 "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":267, "taxon.tab":61, "time_elapsed":{"sec":10.08, "min":0.17, "hr":0}}
91362_resource	    Thu 2021-09-30 09:30:15 AM	{"assoc.tab":491, "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":897, "taxon.tab":694, "time_elapsed":{"sec":3.6, "min":0.06, "hr":0}}
-------------
91362	            Tue 2021-10-19 07:36:11 AM	{"assoc.tab":587,                                         "occur.tab":724,          "taxon.tab":782, "time_elapsed":{"sec":1565.71, "min":26.1, "hr":0.43}}
91362_species	    Tue 2021-10-19 07:42:40 AM	{                                "media_resource.tab":61,                           "taxon.tab":61, "time_elapsed":{"sec":16.32, "min":0.27, "hr":0}}
91362_species_ENV	Tue 2021-10-19 07:43:36 AM	{                 "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":267, "taxon.tab":61, "time_elapsed":{"sec":25.94, "min":0.43, "hr":0.01}}
91362_resource	    Tue 2021-10-19 07:43:41 AM	{"assoc.tab":587, "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":991, "taxon.tab":810, "time_elapsed":{"sec":4.47, "min":0.07, "hr":0}}
gnfinder not relaxed
91362	            Fri 2021-10-22 01:42:43 AM	{"assoc.tab":586,                                         "occur.tab":723,          "taxon.tab":778, "time_elapsed":{"sec":1294.55, "min":21.58, "hr":0.36}}
91362_species	    Fri 2021-10-22 01:43:01 AM	{                                "media_resource.tab":61,                           "taxon.tab":61, "time_elapsed":{"sec":1.28, "min":0.02, "hr":0}}
91362_species_ENV	Fri 2021-10-22 01:43:38 AM	{                 "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":267, "taxon.tab":61, "time_elapsed":{"sec":7.7, "min":0.13, "hr":0}}
91362_resource	    Fri 2021-10-22 01:43:42 AM	{"assoc.tab":586, "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":990, "taxon.tab":806, "time_elapsed":{"sec":3.71, "min":0.06, "hr":0}}
assoc true gnfinder
91362	            Tue 2021-10-26 10:55:58 PM	{"assoc.tab":604,                                         "occur.tab":755,          "taxon.tab":796, "time_elapsed":{"sec":399.91, "min":6.67, "hr":0.11}}
91362_species	    Tue 2021-10-26 10:57:14 PM	{                                "media_resource.tab":61,                           "taxon.tab":61, "time_elapsed":{"sec":52.6, "min":0.88, "hr":0.01}}
91362_species_ENV	Tue 2021-10-26 10:57:56 PM	{                 "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":267, "taxon.tab":61, "time_elapsed":{"sec":11.13, "min":0.19, "hr":0}}
91362_resource	    Tue 2021-10-26 10:57:59 PM	{"assoc.tab":604, "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":1022,"taxon.tab":821, "time_elapsed":{"sec":3.71, "min":0.06, "hr":0}}
after size patterns:
91362	            Tue 2021-11-09 05:46:23 AM	{"assoc.tab":604,                                         "occur.tab":755,           "taxon.tab":796, "time_elapsed":{"sec":3.88, "min":0.06, "hr":0}}
91362_species	    Tue 2021-11-09 05:46:39 AM	{                                "media_resource.tab":61,                            "taxon.tab":61, "time_elapsed":{"sec":1.26, "min":0.02, "hr":0}}
91362_species_ENV	Tue 2021-11-09 05:47:18 AM	{                 "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":267,  "taxon.tab":61, "time_elapsed":{"sec":8.71, "min":0.15, "hr":0}}
91362_resource	    Tue 2021-11-09 05:47:22 AM	{"assoc.tab":604, "MoF.tab":267, "media_resource.tab":61, "occur_specific.tab":1022, "taxon.tab":821, "time_elapsed":{"sec":3.77, "min":0.06, "hr":0}}

-------------
NorthAmericanFlora	Thu 2021-08-12 05:42:08 AM	{"assoc.tab":4829, "MoF.tab":3199, "media.tab":770, "occurrence.tab":8585, "taxon.tab":6198, "time_elapsed":{"sec":43.78, "min":0.73, "hr":0.01}}
NorthAmericanFlora	Mon 2021-10-11 10:23:50 AM	{"assoc.tab":4821, "MoF.tab":3339, "media.tab":794, "occurrence.tab":8724, "taxon.tab":6226, "time_elapsed":{"sec":17.61, "min":0.29, "hr":0}}
after DATA-1893
NorthAmericanFlora	Wed 2021-10-13 10:54:47 AM	{"assoc.tab":4821, "MoF.tab":3339, "media.tab":794, "occurrence.tab":8724, "taxon.tab":6226, "time_elapsed":{"sec":16.21, "min":0.27, "hr":0}}
here discovered GNRD is gone :-(
NorthAmericanFlora	Thu 2021-10-14 10:06:44 PM	{"assoc.tab":5264, "MoF.tab":3336, "media.tab":794, "occurrence.tab":9172, "taxon.tab":6661, "time_elapsed":{"sec":49.85, "min":0.83, "hr":0.01}}
NorthAmericanFlora	Sun 2021-10-17 11:09:29 AM	{"assoc.tab":5264, "MoF.tab":3336, "media.tab":794, "occurrence.tab":9172, "taxon.tab":6661, "time_elapsed":{"sec":19, "min":0.32, "hr":0.01}}
NorthAmericanFlora	Tue 2021-10-19 07:44:12 AM	{"assoc.tab":5681, "MoF.tab":3336, "media.tab":794, "occurrence.tab":9563, "taxon.tab":7110, "time_elapsed":{"sec":20.89, "min":0.35, "hr":0.01}}
gnfinder
NorthAmericanFlora	Wed 2021-10-20 09:46:50 AM	{"assoc.tab":5681, "MoF.tab":3336, "media.tab":794, "occurrence.tab":9563, "taxon.tab":7110, "time_elapsed":{"sec":21.7, "min":0.36, "hr":0.01}}
NorthAmericanFlora	Thu 2021-10-21 08:18:57 AM	{"assoc.tab":5865, "MoF.tab":3338, "media.tab":795, "occurrence.tab":9743, "taxon.tab":7362, "time_elapsed":{"sec":19.54, "min":0.33, "hr":0.01}}
NorthAmericanFlora	Fri 2021-10-22 12:15:47 AM	{"assoc.tab":5865, "MoF.tab":3338, "media.tab":795, "occurrence.tab":9743, "taxon.tab":7362, "time_elapsed":{"sec":17.89, "min":0.3, "hr":0}}
gnfinder not relaxed --- OK
NorthAmericanFlora	Fri 2021-10-22 01:44:07 AM	{"assoc.tab":5648, "MoF.tab":3338, "media.tab":795, "occurrence.tab":9533, "taxon.tab":7069, "time_elapsed":{"sec":17.66, "min":0.29, "hr":0}}
assoc true gnfinder
NorthAmericanFlora	Wed 2021-10-27 04:28:40 AM	{"assoc.tab":5695, "MoF.tab":3338, "media.tab":795, "occurrence.tab":9609, "taxon.tab":7111, "time_elapsed":{"sec":40.25, "min":0.67, "hr":0.01}}
NorthAmericanFlora	Wed 2021-10-27 10:11:37 PM	{"assoc.tab":5695, "MoF.tab":3338, "media.tab":795, "occurrence.tab":9609, "taxon.tab":7111, "time_elapsed":{"sec":17.14, "min":0.29, "hr":0}}
after size patterns:
NorthAmericanFlora	Tue 2021-11-09 05:47:47 AM	{"assoc.tab":5695, "MoF.tab":3674, "media.tab":795, "occurrence.tab":9945, "taxon.tab":7111, "time_elapsed":{"sec":17.34, "min":0.29, "hr":0}}
remove pattern 4th:
NorthAmericanFlora	Tue 2021-11-09 08:47:44 PM	{"assoc.tab":5695, "MoF.tab":3599, "media.tab":795, "occurrence.tab":9870, "taxon.tab":7111, "time_elapsed":{"sec":17.39, "min":0.29, "hr":0}}
after first review: size patterns
NorthAmericanFlora	Thu 2021-11-11 08:19:39 AM	{"assoc.tab":5695, "MoF.tab":3895, "media.tab":795, "occurrence.tab":10166,"taxon.tab":7111, "time_elapsed":{"sec":18.2, "min":0.3, "hr":0.01}}
NorthAmericanFlora	Tue 2021-11-16 10:45:55 AM	{"assoc.tab":5695, "MoF.tab":4077, "media.tab":795, "occurrence.tab":10348,"taxon.tab":7111, "time_elapsed":{"sec":17.48, "min":0.29, "hr":0}}
NorthAmericanFlora	Thu 2021-12-02 08:12:59 AM	{"assoc.tab":5695, "MoF.tab":4094, "media.tab":795, "occurrence.tab":10365,"taxon.tab":7111, "time_elapsed":{"sec":17.47, "min":0.29, "hr":0}}

---------- start FUNGI list: ----------
"15404", "15405", "15406", "15407", "15408", "15409", "15410", "15411", "15412", 
"15413", "15414", "15415", "15416", "15417", "15418", "15419", "15420", "15421"

15404	    Tue 2021-08-10 08:22:34 AM	{                "media.tab":295,                        "taxon.tab":289, "time_elapsed":{"sec":0.53, "min":0.01, "hr":0}}
15404_ENV	Tue 2021-08-10 08:24:58 AM	{"MoF.tab":1360, "media.tab":295, "occurrence.tab":1360, "taxon.tab":289, "time_elapsed":{"sec":24.55, "min":0.41, "hr":0.01}}
15404	    Tue 2021-08-10 08:28:16 AM	{                "media.tab":295,                        "taxon.tab":289, "time_elapsed":{"sec":0.53, "min":0.01, "hr":0}}
15404_ENV	Tue 2021-08-10 08:30:34 AM	{"MoF.tab":1368, "media.tab":295, "occurrence.tab":1368, "taxon.tab":289, "time_elapsed":{"sec":18.79, "min":0.31, "hr":0.01}}
15404	    Mon 2021-09-27 10:15:20 AM	{                "media.tab":299,                        "taxon.tab":293, "time_elapsed":{"sec":0.62, "min":0.01, "hr":0}}
15404_ENV	Mon 2021-09-27 10:18:51 AM	{"MoF.tab":1346, "media.tab":299, "occurrence.tab":1346, "taxon.tab":293, "time_elapsed":{"sec":90.39, "min":1.51, "hr":0.03}}
15404	    Thu 2021-09-30 10:22:55 AM	{                "media.tab":299,                        "taxon.tab":293, "time_elapsed":{"sec":0.66, "min":0.01, "hr":0}}
15404_ENV	Thu 2021-09-30 10:23:43 AM	{"MoF.tab":1346, "media.tab":299, "occurrence.tab":1346, "taxon.tab":293, "time_elapsed":{"sec":17.41, "min":0.29, "hr":0}}
Mac mini: gnfinder
15404	    Mon 2021-10-18 10:33:41 AM	{                "media.tab":300,                        "taxon.tab":294, "time_elapsed":{"sec":1.8, "min":0.03, "hr":0}}
15404	    Tue 2021-10-19 02:14:26 AM	{                "media.tab":303,                        "taxon.tab":297, "time_elapsed":{"sec":1.24, "min":0.02, "hr":0}}
eol-archive: gnfinder
15404	    Tue 2021-10-19 10:07:22 AM	{                "media.tab":303,                        "taxon.tab":297, "time_elapsed":{"sec":2.2, "min":0.04, "hr":0}}
15404_ENV	Tue 2021-10-19 10:09:02 AM	{"MoF.tab":1372, "media.tab":303, "occurrence.tab":1372, "taxon.tab":297, "time_elapsed":{"sec":69.13, "min":1.15, "hr":0.02}}

15405	    Tue 2021-08-10 08:30:46 AM	{                "media_resource.tab":105,                   "taxon.tab":105, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
15405_ENV	Tue 2021-08-10 08:32:59 AM	{"MoF.tab":402,  "media_resource.tab":105, "occur.tab":402,  "taxon.tab":105, "time_elapsed":{"sec":13.38, "min":0.22, "hr":0}}
15405	    Mon 2021-09-27 10:20:22 AM	{                "media_resource.tab":105,                   "taxon.tab":105, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
15405_ENV	Mon 2021-09-27 10:24:12 AM	{"MoF.tab":393,  "media_resource.tab":105, "occur.tab":393,  "taxon.tab":105, "time_elapsed":{"sec":109.82, "min":1.83, "hr":0.03}}
15405	    Thu 2021-09-30 10:23:56 AM	{                "media_resource.tab":105,                   "taxon.tab":105, "time_elapsed":{"sec":0.43, "min":0.01, "hr":0}}
15405_ENV	Thu 2021-09-30 10:26:06 AM	{"MoF.tab":393,  "media_resource.tab":105, "occur.tab":393,  "taxon.tab":105, "time_elapsed":{"sec":10.62, "min":0.18, "hr":0}}

The discrepancy is the improvement in capturing species sections.
- e.g. removal of 'Doubtful species' section. Volume 15406 is a good example.
15406	    Tue 2021-08-10 11:26:45 AM	{               "media.tab":235,                                "taxon.tab":235, "time_elapsed":{"sec":0.48, "min":0.01, "hr":0}}
15406_ENV	Tue 2021-08-10 11:28:58 AM	{"MoF.tab":601, "media.tab":235, "occurrence_specific.tab":601, "taxon.tab":235, "time_elapsed":{"sec":12.8, "min":0.21, "hr":0}}
15406	    Thu 2021-09-30 11:19:35 AM	{               "media.tab":238,                                "taxon.tab":238, "time_elapsed":{"sec":0.56, "min":0.01, "hr":0}}
15406_ENV	Thu 2021-09-30 11:23:25 AM	{"MoF.tab":577, "media.tab":238, "occurrence_specific.tab":577, "taxon.tab":238, "time_elapsed":{"sec":109.86, "min":1.83, "hr":0.03}}
15406	    Mon 2021-10-11 08:26:56 AM	{"assoc.tab":3, "media.tab":238, "occurrence.tab":6,            "taxon.tab":241, "time_elapsed":{"sec":0.63, "min":0.01, "hr":0}}
15406_ENV	Mon 2021-10-11 08:27:32 AM	{"assoc.tab":3, 
                                         "MoF.tab":591, "media.tab":238, "occurrence_specific.tab":597, "taxon.tab":241, "time_elapsed":{"sec":6.15, "min":0.1, "hr":0}}
gnfinder not relaxed
15406	    Fri 2021-10-22 12:49:35 AM	{"assoc.tab":3, "media.tab":243, "occurrence.tab":6,            "taxon.tab":246, "time_elapsed":{"sec":65.75, "min":1.1, "hr":0.02}}
15406_ENV	Fri 2021-10-22 12:50:15 AM	{"assoc.tab":3, 
                                         "MoF.tab":585, "media.tab":243, "occurrence_specific.tab":591, "taxon.tab":246, "time_elapsed":{"sec":9.73, "min":0.16, "hr":0}}

15406	    Mon 2021-11-08 06:14:09 AM	{"assoc.tab":3, "media.tab":243, "occurrence.tab":6,            "taxon.tab":246, "time_elapsed":{"sec":1.02, "min":0.02, "hr":0}}
15406_ENV	Mon 2021-11-08 06:14:49 AM	{"assoc.tab":3, 
                                         "MoF.tab":585, "media.tab":243, "occurrence_specific.tab":591, "taxon.tab":246, "time_elapsed":{"sec":9.48, "min":0.16, "hr":0}}

Mac mini
15406	    Tue 2021-10-26 02:57:43 AM	{"assoc.tab":3, "media.tab":240, "occurrence.tab":6,            "taxon.tab":243, "time_elapsed":{"sec":0.97, "min":0.02, "hr":0}}


15412	    Tue 2021-08-10 01:10:22 PM	{                "media.tab":99,                                  "taxon.tab":99, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
15412_ENV	Tue 2021-08-10 01:12:37 PM	{"MoF.tab":1225, "media.tab":99,  "occurrence_specific.tab":1225, "taxon.tab":99, "time_elapsed":{"sec":14.39, "min":0.24, "hr":0}}
15412	    Fri 2021-10-01 10:35:26 AM	{                "media.tab":108,                                 "taxon.tab":108, "time_elapsed":{"sec":0.7, "min":0.01, "hr":0}}
15412_ENV	Fri 2021-10-01 10:43:16 AM	{"MoF.tab":1157, "media.tab":108, "occurrence_specific.tab":1157, "taxon.tab":108, "time_elapsed":{"sec":349.19, "min":5.82, "hr":0.1}}
15412	    Mon 2021-10-11 06:34:09 AM	{                "media.tab":108,                                 "taxon.tab":108, "time_elapsed":{"sec":65.14, "min":1.09, "hr":0.02}}
15412_ENV	Mon 2021-10-11 06:34:55 AM	{"MoF.tab":1202, "media.tab":108, "occurrence_specific.tab":1202, "taxon.tab":108, "time_elapsed":{"sec":15.31, "min":0.26, "hr":0}}

15418	    Tue 2021-08-10 12:57:16 PM	{               "media.tab":266,                                "taxon.tab":265, "time_elapsed":{"sec":0.5, "min":0.01, "hr":0}}
15418_ENV	Tue 2021-08-10 01:05:36 PM	{"MoF.tab":472, "media.tab":266, "occurrence_specific.tab":472, "taxon.tab":265, "time_elapsed":{"sec":380.14, "min":6.34, "hr":0.11}}
15418	    Sun 2021-10-03 03:08:29 AM	{               "media.tab":270,                                "taxon.tab":269, "time_elapsed":{"sec":0.75, "min":0.01, "hr":0}}
15418_ENV	Sun 2021-10-03 03:10:58 AM	{"MoF.tab":459, "media.tab":270, "occurrence_specific.tab":459, "taxon.tab":269, "time_elapsed":{"sec":27.53, "min":0.46, "hr":0.01}}
15418	    Mon 2021-10-11 06:17:24 AM	{               "media.tab":270,                                "taxon.tab":269, "time_elapsed":{"sec":0.6, "min":0.01, "hr":0}}
15418_ENV	Mon 2021-10-11 06:18:01 AM	{"MoF.tab":459, "media.tab":270, "occurrence_specific.tab":459, "taxon.tab":269, "time_elapsed":{"sec":6.76, "min":0.11, "hr":0}}
gnfinder:
15418	    Tue 2021-10-19 11:23:17 PM	{               "media.tab":276,                                "taxon.tab":275, "time_elapsed":{"sec":0.86, "min":0.01, "hr":0}}
15418_ENV	Tue 2021-10-19 11:25:01 PM	{"MoF.tab":475, "media.tab":276, "occurrence_specific.tab":475, "taxon.tab":275, "time_elapsed":{"sec":74.18, "min":1.24, "hr":0.02}}

15420	    Thu 2021-08-12 04:49:12 AM	{                "media.tab":143,                                 "taxon.tab":143, "time_elapsed":{"sec":0.42, "min":0.01, "hr":0}}
15420_ENV	Thu 2021-08-12 04:51:26 AM	{"MoF.tab":456,  "media.tab":143, "occurrence_specific.tab":456,  "taxon.tab":143, "time_elapsed":{"sec":13.53, "min":0.23, "hr":0}}
15420	    Fri 2021-10-01 11:12:49 AM	{                "media.tab":146,                                 "taxon.tab":146, "time_elapsed":{"sec":0.43, "min":0.01, "hr":0}}
15420_ENV	Fri 2021-10-01 11:15:19 AM	{"MoF.tab":456,  "media.tab":146, "occurrence_specific.tab":456,  "taxon.tab":146, "time_elapsed":{"sec":30.06, "min":0.5, "hr":0.01}}

15421	    Thu 2021-08-12 09:14:25 AM	{               "media.tab":187,                                "taxon.tab":187, "time_elapsed":{"sec":0.49, "min":0.01, "hr":0}}
15421_ENV	Thu 2021-08-12 09:16:42 AM	{"MoF.tab":647, "media.tab":187, "occurrence_specific.tab":647, "taxon.tab":187, "time_elapsed":{"sec":16.93, "min":0.28, "hr":0}}
15421	    Sun 2021-10-03 03:29:25 AM	{               "media.tab":187,                                "taxon.tab":187, "time_elapsed":{"sec":0.47, "min":0.01, "hr":0}}
15421_ENV	Sun 2021-10-03 03:31:35 AM	{"MoF.tab":625, "media.tab":187, "occurrence_specific.tab":625, "taxon.tab":187, "time_elapsed":{"sec":10.63, "min":0.18, "hr":0}}
after DATA-1893:
15421	    Wed 2021-10-13 09:40:23 AM	{               "media.tab":187,                                "taxon.tab":187, "time_elapsed":{"sec":0.5, "min":0.01, "hr":0}}
15421_ENV	Wed 2021-10-13 09:41:04 AM	{"MoF.tab":625, "media.tab":187, "occurrence_specific.tab":625, "taxon.tab":187, "time_elapsed":{"sec":10.76, "min":0.18, "hr":0}}

NorthAmericanFlora_Fungi	Thu 2021-08-12 05:42:52 AM	{"MoF.tab":17228, "media_resource.tab":4498, "occurrence_specific.tab":17228, "taxon.tab":4491, "time_elapsed":{"sec":108.91, "min":1.82, "hr":0.03}}
NorthAmericanFlora_Fungi	Mon 2021-10-11 10:24:51 AM	{"assoc.tab":3, 
                                                         "MoF.tab":17168, "media_resource.tab":4597, "occurrence_specific.tab":17174, "taxon.tab":4592, "time_elapsed":{"sec":38.07, "min":0.63, "hr":0.01}}
after DATA-1893
NorthAmericanFlora_Fungi	Wed 2021-10-13 10:55:10 AM	{"assoc.tab":3, 
                                                         "MoF.tab":17168, "media_resource.tab":4597, "occurrence_specific.tab":17174, "taxon.tab":4592, "time_elapsed":{"sec":36.92, "min":0.62, "hr":0.01}}
gnfinder:
NorthAmericanFlora_Fungi	Wed 2021-10-20 09:47:18 AM	{"assoc.tab":3, 
                               seems too big             "MoF.tab":17857, "media_resource.tab":5347, "occurrence_specific.tab":17863, "taxon.tab":4713, "time_elapsed":{"sec":45.07, "min":0.75, "hr":0.01}}
NorthAmericanFlora_Fungi	Wed 2021-10-20 10:56:17 AM	{"assoc.tab":3, 
                                                         "MoF.tab":17801, "media_resource.tab":5332, "occurrence_specific.tab":17807, "taxon.tab":4713, "time_elapsed":{"sec":52.04, "min":0.87, "hr":0.01}}
NorthAmericanFlora_Fungi	Fri 2021-10-22 12:15:24 AM	{"assoc.tab":3, 
                                                         "MoF.tab":18296, "media_resource.tab":5705, "occurrence_specific.tab":18302, "taxon.tab":4744, "time_elapsed":{"sec":76.46, "min":1.27, "hr":0.02}}
gnfinder not relaxed --- OK
NorthAmericanFlora_Fungi	Sun 2021-10-24 07:49:09 AM	{"assoc.tab":3, 
                                                         "MoF.tab":10991, "media_resource.tab":4624, "occurrence_specific.tab":10997, "taxon.tab":4618, "time_elapsed":{"sec":63.1, "min":1.05, "hr":0.02}}
assoc true gnfinder
NorthAmericanFlora_Fungi	Wed 2021-10-27 04:28:54 AM	{"assoc.tab":3, 
                                                         "MoF.tab":10991, "media_resource.tab":4624, "occurrence_specific.tab":10997, "taxon.tab":4618, "time_elapsed":{"sec":65.62, "min":1.09, "hr":0.02}}
after first review: size patterns
NorthAmericanFlora_Fungi	Thu 2021-11-11 08:19:58 AM	{"assoc.tab":3, 
                                                         "MoF.tab":11039, "media_resource.tab":4624, "occurrence_specific.tab":11045, "taxon.tab":4618, "time_elapsed":{"sec":34.23, "min":0.57, "hr":0.01}}
NorthAmericanFlora_Fungi	Thu 2021-12-02 08:13:40 AM	{"assoc.tab":3, 
                                                         "MoF.tab":11043, "media_resource.tab":4624, "occurrence_specific.tab":11049, "taxon.tab":4618, "time_elapsed":{"sec":33.45, "min":0.56, "hr":0.01}}
---------- end FUNGI list: ----------

---------- Plant list: ----------
15422	    Fri 2021-08-13 02:06:15 AM	{                "media.tab":280,                            "taxon.tab":279, "time_elapsed":{"sec":0.55, "min":0.01, "hr":0}}
15422_ENV	Fri 2021-08-13 02:08:32 AM	{"MoF.tab":1131, "media.tab":280, "occur_specific.tab":1131, "taxon.tab":279, "time_elapsed":{"sec":17.47, "min":0.29, "hr":0}}
15422	    Tue 2021-10-05 10:42:44 AM	{                "media.tab":272,                            "taxon.tab":270, "time_elapsed":{"sec":0.55, "min":0.01, "hr":0}}
15422_ENV	Tue 2021-10-05 10:44:55 AM	{"MoF.tab":1113, "media.tab":272, "occur_specific.tab":1113, "taxon.tab":270, "time_elapsed":{"sec":11.12, "min":0.19, "hr":0}}
Mac mini:
15422	    Mon 2021-10-18 11:04:07 AM	{                "media.tab":262,                            "taxon.tab":260, "time_elapsed":{"sec":1.04, "min":0.02, "hr":0}}
gnparser to the rescue: get_binomial_or_tri()
15422	    Tue 2021-10-19 02:14:35 AM	{                "media.tab":279,                            "taxon.tab":278, "time_elapsed":{"sec":0.95, "min":0.02, "hr":0}}


15424	    Wed 2021-08-18 11:33:44 AM	{               "media.tab":151,                            "taxon.tab":151, "time_elapsed":{"sec":1.8, "min":0.03, "hr":0}}
15424_ENV	Wed 2021-08-18 11:35:59 AM	{"MoF.tab":598, "media.tab":151, "occur_specific.tab":598,  "taxon.tab":151, "time_elapsed":{"sec":14.63, "min":0.24, "hr":0}}
15424	    Sun 2021-10-03 04:01:56 AM	{               "media.tab":151,                            "taxon.tab":151, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
15424_ENV	Sun 2021-10-03 04:04:57 AM	{"MoF.tab":598, "media.tab":151, "occur_specific.tab":598,  "taxon.tab":151, "time_elapsed":{"sec":150.99, "min":2.52, "hr":0.04}}

15425	    Thu 2021-08-12 09:12:34 AM	{               "media.tab":59,                             "taxon.tab":59, "time_elapsed":{"sec":0.43, "min":0.01, "hr":0}}
15425_ENV	Thu 2021-08-12 09:15:13 AM	{"MoF.tab":235, "media.tab":59, "occur_specific.tab":235,   "taxon.tab":59, "time_elapsed":{"sec":39.73, "min":0.66, "hr":0.01}}
15425	    Tue 2021-10-05 10:40:24 AM	{               "media.tab":66,                             "taxon.tab":66, "time_elapsed":{"sec":0.38, "min":0.01, "hr":0}}
15425_ENV	Tue 2021-10-05 10:42:33 AM	{"MoF.tab":276, "media.tab":66, "occur_specific.tab":276,   "taxon.tab":66, "time_elapsed":{"sec":9.41, "min":0.16, "hr":0}}

15426	    Thu 2021-08-12 09:15:37 AM	{               "media.tab":101,                            "taxon.tab":101, "time_elapsed":{"sec":0.41, "min":0.01, "hr":0}}
15426_ENV	Thu 2021-08-12 09:18:22 AM	{"MoF.tab":446, "media.tab":101, "occur_specific.tab":446,  "taxon.tab":101, "time_elapsed":{"sec":44.21, "min":0.74, "hr":0.01}}
15426	    Tue 2021-10-05 10:41:29 AM	{               "media.tab":113,                            "taxon.tab":113, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
15426_ENV	Tue 2021-10-05 10:42:11 AM	{"MoF.tab":491, "media.tab":113, "occur_specific.tab":491,  "taxon.tab":113, "time_elapsed":{"sec":12.05, "min":0.2, "hr":0}}
15426	    Mon 2021-11-08 02:33:58 AM	{               "media.tab":113,                            "taxon.tab":113, "time_elapsed":{"sec":0.57, "min":0.01, "hr":0}}
15426_ENV	Mon 2021-11-08 02:34:34 AM	{"MoF.tab":491, "media.tab":113, "occur_specific.tab":491,  "taxon.tab":113, "time_elapsed":{"sec":6.71, "min":0.11, "hr":0}}

15430	    Thu 2021-08-12 09:15:41 AM	{               "media.tab":204,                            "taxon.tab":204, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
15430_ENV	Thu 2021-08-12 09:17:59 AM	{"MoF.tab":820, "media.tab":204, "occur_specific.tab":820,  "taxon.tab":204, "time_elapsed":{"sec":18.4, "min":0.31, "hr":0.01}}
15430	    Tue 2021-10-05 10:38:08 AM	{               "media.tab":204,                            "taxon.tab":204, "time_elapsed":{"sec":0.49, "min":0.01, "hr":0}}
15430_ENV	Tue 2021-10-05 10:40:20 AM	{"MoF.tab":833, "media.tab":204, "occur_specific.tab":833,  "taxon.tab":204, "time_elapsed":{"sec":12.53, "min":0.21, "hr":0}}

15429	    Thu 2021-08-12 09:18:37 AM	{               "media.tab":250,                            "taxon.tab":250, "time_elapsed":{"sec":0.51, "min":0.01, "hr":0}}
15429_ENV	Thu 2021-08-12 09:21:06 AM	{"MoF.tab":963, "media.tab":250, "occur_specific.tab":963,  "taxon.tab":250, "time_elapsed":{"sec":28.41, "min":0.47, "hr":0.01}}

91208	    Mon 2021-08-23 01:38:23 AM	{               "media_resource.tab":83,                                "taxon.tab":83, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
91208_ENV	Mon 2021-08-23 01:40:42 AM	{"MoF.tab":984, "media_resource.tab":83, "occurrence_specific.tab":984, "taxon.tab":83, "time_elapsed":{"sec":18.38, "min":0.31, "hr":0.01}}
91208	    Tue 2021-10-05 09:49:41 AM	{               "media_resource.tab":83,                                "taxon.tab":83, "time_elapsed":{"sec":0.45, "min":0.01, "hr":0}}
91208_ENV	Tue 2021-10-05 09:50:24 AM	{"MoF.tab":978, "media_resource.tab":83, "occurrence_specific.tab":978, "taxon.tab":83, "time_elapsed":{"sec":13.38, "min":0.22, "hr":0}}

91357	    Wed 2021-08-18 08:57:46 AM	{               "media.tab":150,                            "taxon.tab":150, "time_elapsed":{"sec":0.7, "min":0.01, "hr":0}}
91357_ENV	Wed 2021-08-18 09:02:38 AM	{"MoF.tab":659, "media.tab":150, "occur_specific.tab":659,  "taxon.tab":150, "time_elapsed":{"sec":172.1, "min":2.87, "hr":0.05}}
91357	    Sun 2021-10-03 04:33:03 AM	{               "media.tab":150,                            "taxon.tab":150, "time_elapsed":{"sec":0.49, "min":0.01, "hr":0}}
91357_ENV	Sun 2021-10-03 04:36:14 AM	{"MoF.tab":678, "media.tab":150, "occur_specific.tab":678,  "taxon.tab":150, "time_elapsed":{"sec":161.25, "min":2.69, "hr":0.04}}

91348	    Thu 2021-08-26 02:31:54 AM	{               "media_resource.tab":227,                                "taxon.tab":227, "time_elapsed":{"sec":0.47, "min":0.01, "hr":0}}
91348_ENV	Thu 2021-08-26 02:33:09 AM	{"MoF.tab":703, "media_resource.tab":227, "occurrence_specific.tab":703, "taxon.tab":227, "time_elapsed":{"sec":15.37, "min":0.26, "hr":0}}
91348	    Mon 2021-10-04 02:10:29 AM	{               "media_resource.tab":230,                                "taxon.tab":230, "time_elapsed":{"sec":0.47, "min":0.01, "hr":0}}
91348_ENV	Mon 2021-10-04 02:12:43 AM	{"MoF.tab":715, "media_resource.tab":230, "occurrence_specific.tab":715, "taxon.tab":230, "time_elapsed":{"sec":13.87, "min":0.23, "hr":0}}

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

=== START BHL RESOURCES === 7 documents
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
parse_unstructured_text_memoirs.php _ '{"resource_id": "15405", "resource_name":"all_BHL"}' //F2
parse_unstructured_text_memoirs.php _ '{"resource_id": "15406", "resource_name":"all_BHL"}' //F3
parse_unstructured_text_memoirs.php _ '{"resource_id": "15407", "resource_name":"all_BHL", "group":"Fungi"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "15418", "resource_name":"all_BHL", "group":"Fungi"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "15409", "resource_name":"all_BHL", "group":"Fungi"}'

PLANTS.txt ---- upward up to: 15432 --- for DATA-1891
parse_unstructured_text_memoirs.php _ '{"resource_id": "15422", "resource_name":"all_BHL", "group":"Plants"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "91345", "resource_name":"all_BHL", "group":"Plants"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "91208", "resource_name":"all_BHL", "group":"Plants"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "91209", "resource_name":"all_BHL", "group":"Plants"}'


91357 91461 91336

91336	    Sun 2021-08-22 08:04:57 PM	{               "media_resource.tab":154,                                "taxon.tab":154, "time_elapsed":{"sec":0.58, "min":0.01, "hr":0}}
91336_ENV	Sun 2021-08-22 08:07:21 PM	{"MoF.tab":862, "media_resource.tab":154, "occurrence_specific.tab":862, "taxon.tab":154, "time_elapsed":{"sec":23.38, "min":0.39, "hr":0.01}}
91336	    Tue 2021-10-05 10:38:05 AM	{               "media_resource.tab":156,                                "taxon.tab":156, "time_elapsed":{"sec":0.44, "min":0.01, "hr":0}}
91336_ENV	Tue 2021-10-05 10:38:47 AM	{"MoF.tab":875, "media_resource.tab":156, "occurrence_specific.tab":875, "taxon.tab":156, "time_elapsed":{"sec":11.63, "min":0.19, "hr":0}}

91461	    Wed 2021-08-25 09:08:35 AM	{               "media_resource.tab":46,                                "taxon.tab":46, "time_elapsed":{"sec":0.35, "min":0.01, "hr":0}}
91461_ENV	Wed 2021-08-25 09:09:48 AM	{"MoF.tab":253, "media_resource.tab":46, "occurrence_specific.tab":253, "taxon.tab":46, "time_elapsed":{"sec":13.01, "min":0.22, "hr":0}}
91461	    Sun 2021-10-03 12:05:18 PM	{               "media_resource.tab":45,                                "taxon.tab":45, "time_elapsed":{"sec":0.35, "min":0.01, "hr":0}}
91461_ENV	Sun 2021-10-03 12:05:57 PM	{"MoF.tab":253, "media_resource.tab":45, "occurrence_specific.tab":253, "taxon.tab":45, "time_elapsed":{"sec":9.55, "min":0.16, "hr":0}}

91334	    Wed 2021-08-25 09:11:17 AM	{               "media_resource.tab":117,                                   "taxon.tab":117, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
91334_ENV	Wed 2021-08-25 09:12:32 AM	{"MoF.tab":393, "media_resource.tab":117, "occurrence_specific.tab":393,    "taxon.tab":117, "time_elapsed":{"sec":15.02, "min":0.25, "hr":0}}
91334	    Tue 2021-10-05 09:46:46 AM	{               "media_resource.tab":118,                                   "taxon.tab":118, "time_elapsed":{"sec":0.44, "min":0.01, "hr":0}}
91334_ENV	Tue 2021-10-05 09:47:23 AM	{"MoF.tab":394, "media_resource.tab":118, "occurrence_specific.tab":394,    "taxon.tab":118, "time_elapsed":{"sec":7.06, "min":0.12, "hr":0}}

91228	    Wed 2021-08-25 09:12:49 AM	{               "media_resource.tab":373,                                "taxon.tab":373, "time_elapsed":{"sec":0.6, "min":0.01, "hr":0}}
91228_ENV	Wed 2021-08-25 09:14:08 AM	{"MoF.tab":940, "media_resource.tab":373, "occurrence_specific.tab":940, "taxon.tab":373, "time_elapsed":{"sec":19.27, "min":0.32, "hr":0.01}}
91228	    Mon 2021-10-04 12:20:49 AM	{               "media_resource.tab":373,                                "taxon.tab":373, "time_elapsed":{"sec":0.59, "min":0.01, "hr":0}}
91228_ENV	Mon 2021-10-04 12:23:05 AM	{"MoF.tab":949, "media_resource.tab":373, "occurrence_specific.tab":949, "taxon.tab":373, "time_elapsed":{"sec":15.92, "min":0.27, "hr":0}}

15436	    Mon 2021-08-30 01:51:29 AM	{               "media_resource.tab":241,                                "taxon.tab":241, "time_elapsed":{"sec":0.54, "min":0.01, "hr":0}}
15436_ENV	Mon 2021-08-30 01:52:49 AM	{"MoF.tab":930, "media_resource.tab":241, "occurrence_specific.tab":930, "taxon.tab":241, "time_elapsed":{"sec":19.61, "min":0.33, "hr":0.01}}
15436	    Tue 2021-10-05 09:56:15 AM	{               "media_resource.tab":242,                                "taxon.tab":242, "time_elapsed":{"sec":1.95, "min":0.03, "hr":0}}
15436_ENV	Tue 2021-10-05 09:59:03 AM	{"MoF.tab":954, "media_resource.tab":242, "occurrence_specific.tab":954, "taxon.tab":242, "time_elapsed":{"sec":47.69, "min":0.79, "hr":0.01}}
gnfinder not relaxed
15436	    Sun 2021-10-24 09:51:17 PM	{               "media_resource.tab":244,                                "taxon.tab":244, "time_elapsed":{"sec":0.86, "min":0.01, "hr":0}}
15436_ENV	Sun 2021-10-24 09:52:18 PM	{"MoF.tab":941, "media_resource.tab":244, "occurrence_specific.tab":941, "taxon.tab":244, "time_elapsed":{"sec":30.14, "min":0.5, "hr":0.01}}

15434	    Wed 2021-08-25 09:18:09 AM	{               "media_resource.tab":288,                                   "taxon.tab":288, "time_elapsed":{"sec":0.53, "min":0.01, "hr":0}}
15434_ENV	Wed 2021-08-25 09:19:25 AM	{"MoF.tab":617, "media_resource.tab":288, "occurrence_specific.tab":617,    "taxon.tab":288, "time_elapsed":{"sec":16.14, "min":0.27, "hr":0}}
15434	    Sun 2021-10-03 11:58:35 PM	{               "media_resource.tab":290,                                   "taxon.tab":290, "time_elapsed":{"sec":0.5, "min":0.01, "hr":0}}
15434_ENV	Sun 2021-10-03 11:59:17 PM	{"MoF.tab":622, "media_resource.tab":290, "occurrence_specific.tab":622,    "taxon.tab":290, "time_elapsed":{"sec":12.17, "min":0.2, "hr":0}}
gnfinder
15434	    Wed 2021-10-20 04:08:44 AM	{               "media_resource.tab":298,                                   "taxon.tab":298, "time_elapsed":{"sec":1.19, "min":0.02, "hr":0}}
15434_ENV	Wed 2021-10-20 04:10:53 AM	{"MoF.tab":643, "media_resource.tab":298, "occurrence_specific.tab":643,    "taxon.tab":298, "time_elapsed":{"sec":98.9, "min":1.65, "hr":0.03}}

91345	    Thu 2021-08-26 02:42:05 AM	{                "media_resource.tab":292,                                  "taxon.tab":292, "time_elapsed":{"sec":0.55, "min":0.01, "hr":0}}
91345_ENV	Thu 2021-08-26 02:43:22 AM	{"MoF.tab":1009, "media_resource.tab":292, "occurrence_specific.tab":1009,  "taxon.tab":292, "time_elapsed":{"sec":17.13, "min":0.29, "hr":0}}
91345	    Tue 2021-10-05 09:19:53 AM	{                "media_resource.tab":292,                                  "taxon.tab":292, "time_elapsed":{"sec":4.43, "min":0.07, "hr":0}}
91345_ENV	Tue 2021-10-05 09:20:40 AM	{"MoF.tab":1017, "media_resource.tab":292, "occurrence_specific.tab":1017,  "taxon.tab":292, "time_elapsed":{"sec":16.39, "min":0.27, "hr":0}}

91209	    Tue 2021-08-31 12:57:27 AM	{                "media_resource.tab":301,                                  "taxon.tab":301, "time_elapsed":{"sec":0.57, "min":0.01, "hr":0}}
91209_ENV	Tue 2021-08-31 12:59:02 AM	{"MoF.tab":1248, "media_resource.tab":301, "occurrence_specific.tab":1248,  "taxon.tab":301, "time_elapsed":{"sec":35.79, "min":0.6, "hr":0.01}}
91209	    Mon 2021-10-04 01:09:16 AM	{                "media_resource.tab":301,                                  "taxon.tab":301, "time_elapsed":{"sec":0.56, "min":0.01, "hr":0}}
91209_ENV	Mon 2021-10-04 01:10:02 AM	{"MoF.tab":1258, "media_resource.tab":301, "occurrence_specific.tab":1258,  "taxon.tab":301, "time_elapsed":{"sec":15.99, "min":0.27, "hr":0}}

91529	    Mon 2021-08-30 09:27:52 AM	{               "media.tab":184,                            "taxon.tab":184, "time_elapsed":{"sec":0.46, "min":0.01, "hr":0}}
91529_ENV	Mon 2021-08-30 09:29:08 AM	{"MoF.tab":607, "media.tab":184, "occur_specific.tab":607,  "taxon.tab":184, "time_elapsed":{"sec":15.46, "min":0.26, "hr":0}}
91529	    Tue 2021-10-05 03:49:04 AM	{               "media.tab":184,                            "taxon.tab":184, "time_elapsed":{"sec":0.48, "min":0.01, "hr":0}}
91529_ENV	Tue 2021-10-05 03:49:45 AM	{"MoF.tab":617, "media.tab":184, "occur_specific.tab":617,  "taxon.tab":184, "time_elapsed":{"sec":10.88, "min":0.18, "hr":0}}
after DATA-1893
91529	    Wed 2021-10-13 10:47:24 AM	{               "media.tab":184,                            "taxon.tab":184, "time_elapsed":{"sec":0.51, "min":0.01, "hr":0}}
91529_ENV	Wed 2021-10-13 10:48:03 AM	{"MoF.tab":617, "media.tab":184, "occur_specific.tab":617,  "taxon.tab":184, "time_elapsed":{"sec":8.71, "min":0.15, "hr":0}}
gnfinder not relaxed
91529	    Sun 2021-10-24 10:49:20 PM	{               "media.tab":187,                            "taxon.tab":187, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
91529_ENV	Sun 2021-10-24 10:50:04 PM	{"MoF.tab":630, "media.tab":187, "occur_specific.tab":630,  "taxon.tab":187, "time_elapsed":{"sec":13.08, "min":0.22, "hr":0}}

91335	    Mon 2021-08-30 01:53:08 AM	{               "media_resource.tab":215,                                "taxon.tab":215, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
91335_ENV	Mon 2021-08-30 01:54:31 AM	{"MoF.tab":593, "media_resource.tab":215, "occurrence_specific.tab":593, "taxon.tab":215, "time_elapsed":{"sec":22.66, "min":0.38, "hr":0.01}}
91335	    Tue 2021-10-05 04:09:39 AM	{               "media_resource.tab":216,                                "taxon.tab":216, "time_elapsed":{"sec":0.47, "min":0.01, "hr":0}}
91335_ENV	Tue 2021-10-05 04:11:50 AM	{"MoF.tab":593, "media_resource.tab":216, "occurrence_specific.tab":593, "taxon.tab":216, "time_elapsed":{"sec":10.4, "min":0.17, "hr":0}}
gnfinder
91335	    Wed 2021-10-20 05:03:33 AM	{               "media_resource.tab":220,                                "taxon.tab":220, "time_elapsed":{"sec":1.82, "min":0.03, "hr":0}}
91335_ENV	Wed 2021-10-20 05:05:17 AM	{"MoF.tab":592, "media_resource.tab":220, "occurrence_specific.tab":592, "taxon.tab":220, "time_elapsed":{"sec":73.54, "min":1.23, "hr":0.02}}

NorthAmericanFlora_Plants	Tue 2021-08-31 03:10:17 AM	{"MoF.tab":45013, "media.tab":11438, "occurrence.tab":45013, "taxon.tab":11299, "time_elapsed":{"sec":267.57, "min":4.46, "hr":0.07}}
NorthAmericanFlora_Plants	Mon 2021-10-11 10:25:59 AM	{"MoF.tab":45513, "media.tab":11476, "occurrence.tab":45513, "taxon.tab":11335, "time_elapsed":{"sec":94.02, "min":1.57, "hr":0.03}}
after DATA-1893:
NorthAmericanFlora_Plants	Wed 2021-10-13 10:56:00 AM	{"MoF.tab":45513, "media.tab":11476, "occurrence.tab":45513, "taxon.tab":11335, "time_elapsed":{"sec":93.09, "min":1.55, "hr":0.03}}
gnfinder: relaxed --- too relaxed I think
NorthAmericanFlora_Plants	Wed 2021-10-20 09:48:15 AM	{"MoF.tab":46185, "media.tab":11646, "occurrence.tab":46185, "taxon.tab":11501, "time_elapsed":{"sec":111.03, "min":1.85, "hr":0.03}}
gnfinder not relaxed --- OK
NorthAmericanFlora_Plants	Mon 2021-10-25 12:58:48 AM	{"MoF.tab":45807, "media.tab":11574, "occurrence.tab":45807, "taxon.tab":11430, "time_elapsed":{"sec":113.66, "min":1.89, "hr":0.03}}
assoc true gnfinder
NorthAmericanFlora_Plants	Wed 2021-10-27 06:00:07 AM	{"MoF.tab":45807, "media.tab":11574, "occurrence.tab":45807, "taxon.tab":11430, "time_elapsed":{"sec":94.86, "min":1.58, "hr":0.03}}
after first review: size patterns
NorthAmericanFlora_Plants	Thu 2021-11-11 08:21:24 AM	{"MoF.tab":58940, "media.tab":11574, "occurrence.tab":58940, "taxon.tab":11430, "time_elapsed":{"sec":115.91, "min":1.93, "hr":0.03}}
NorthAmericanFlora_Plants	Wed 2021-12-01 07:50:32 AM	{"MoF.tab":62949, "media.tab":11574, "occurrence.tab":62949, "taxon.tab":11430, "time_elapsed":{"sec":104.95, "min":1.75, "hr":0.03}}
NorthAmericanFlora_Plants	Thu 2021-12-02 08:15:33 AM	{"MoF.tab":62873, "media.tab":11574, "occurrence.tab":62873, "taxon.tab":11430, "time_elapsed":{"sec":106.47, "min":1.77, "hr":0.03}}

----------summary
NorthAmericanFlora          {"assoc.tab":5695,  "MoF.tab":3338,  "media.tab":795,   "occur.tab":9609,  "taxon.tab":7111, "time_elapsed":{"sec":17.14, "min":0.29, "hr":0}}
NorthAmericanFlora_Plants   {                   "MoF.tab":45807, "media.tab":11574, "occur.tab":45807, "taxon.tab":11430, "time_elapsed":{"sec":94.86, "min":1.58, "hr":0.03}}
NorthAmericanFlora_Fungi    {"assoc.tab":3,     "MoF.tab":10991, "media.tab":4624,  "occur.tab":10997, "taxon.tab":4618, "time_elapsed":{"sec":65.62, "min":1.09, "hr":0.02}}
NorthAmericanFlora_All      {"assoc.tab":5698, "MoF.tab":60136,  "media.tab":16993, "occur.tab":66413, "taxon.tab":21588, "time_elapsed":{"sec":59.07, "min":0.98, "hr":0.02}}



NorthAmericanFlora_All	Thu 2021-12-02 08:16:48 AM	{"association.tab":5698, "measurement_or_fact_specific.tab":78010, "media_resource.tab":16993, "occurrence_specific.tab":84287, "taxon.tab":21588, "time_elapsed":{"sec":65.91, "min":1.1, "hr":0.02}}


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
volii1993	Wed 2021-10-13 06:09:25 AM	{"media_resource.tab":1523, "taxon.tab":1500, "time_elapsed":{"sec":2.9, "min":0.05, "hr":0}}
gnfinder not relaxed
volii1993	Sun 2021-10-24 07:59:31 AM	{"media_resource.tab":1519, "taxon.tab":1496, "time_elapsed":{"sec":4.16, "min":0.07, "hr":0}}

voliii1998	Thu 2021-09-23 12:23:44 AM	{"media_resource.tab":545, "taxon.tab":529, "time_elapsed":{"sec":0.84, "min":0.01, "hr":0}}
gnfinder not relaxed
voliii1998	Sun 2021-10-24 08:07:29 AM	{"media_resource.tab":545, "taxon.tab":529, "time_elapsed":{"sec":0.88, "min":0.01, "hr":0}}

volv2003	Thu 2021-09-23 12:23:57 AM	{"media_resource.tab":792, "taxon.tab":792, "time_elapsed":{"sec":0.94, "min":0.02, "hr":0}}
gnfinder not relaxed
volv2003	Sun 2021-10-24 08:07:44 AM	{"media_resource.tab":792, "taxon.tab":792, "time_elapsed":{"sec":1.03, "min":0.02, "hr":0}}


volvi2004	Thu 2021-09-23 12:24:11 AM	{"media_resource.tab":809, "taxon.tab":801, "time_elapsed":{"sec":1.04, "min":0.02, "hr":0}}
volvi2004	Sun 2021-10-24 08:11:09 AM	{"media_resource.tab":809, "taxon.tab":801, "time_elapsed":{"sec":1.08, "min":0.02, "hr":0}}

volvii2004	Thu 2021-09-23 12:24:26 AM	{"media_resource.tab":1046, "taxon.tab":1020, "time_elapsed":{"sec":1.11, "min":0.02, "hr":0}}
volvii2004	Sun 2021-10-24 08:11:24 AM	{"media_resource.tab":1046, "taxon.tab":1020, "time_elapsed":{"sec":1.17, "min":0.02, "hr":0}}

volviii2007	Thu 2021-09-23 12:24:45 AM	{"media_resource.tab":1934, "taxon.tab":1920, "time_elapsed":{"sec":1.77, "min":0.03, "hr":0}}
volviii2007	Wed 2021-10-13 06:17:11 AM	{"media_resource.tab":1935, "taxon.tab":1921, "time_elapsed":{"sec":2.79, "min":0.05, "hr":0}}
volviii2007	Sun 2021-10-24 08:11:47 AM	{"media_resource.tab":1934, "taxon.tab":1920, "time_elapsed":{"sec":1.97, "min":0.03, "hr":0}}

volix2007	Thu 2021-09-23 12:24:59 AM	{"media_resource.tab":568, "taxon.tab":562, "time_elapsed":{"sec":0.87, "min":0.01, "hr":0}}
volix2007	Sun 2021-10-24 08:12:01 AM	{"media_resource.tab":568, "taxon.tab":562, "time_elapsed":{"sec":1.28, "min":0.02, "hr":0}}

volx2011	Thu 2021-09-23 12:25:12 AM	{"media_resource.tab":774, "taxon.tab":759, "time_elapsed":{"sec":0.96, "min":0.02, "hr":0}}
volx2011	Sun 2021-10-24 08:12:14 AM	{"media_resource.tab":774, "taxon.tab":759, "time_elapsed":{"sec":1.02, "min":0.02, "hr":0}}

volxi2014	Thu 2021-09-23 12:25:30 AM	{"media_resource.tab":518, "taxon.tab":518, "time_elapsed":{"sec":9.02, "min":0.15, "hr":0}}
volxi2014	Sun 2021-10-24 08:12:25 AM	{"media_resource.tab":518, "taxon.tab":518, "time_elapsed":{"sec":0.81, "min":0.01, "hr":0}}

volxii2015	Thu 2021-09-23 12:25:35 AM	{"media_resource.tab":191, "taxon.tab":186, "time_elapsed":{"sec":0.54, "min":0.01, "hr":0}}
volxii2015	Sun 2021-10-24 08:12:31 AM	{"media_resource.tab":191, "taxon.tab":186, "time_elapsed":{"sec":0.53, "min":0.01, "hr":0}}

volxiii2015	Thu 2021-09-23 12:25:46 AM	{"media_resource.tab":719, "taxon.tab":715, "time_elapsed":{"sec":0.83, "min":0.01, "hr":0}}
volxiii2015	Sun 2021-10-24 08:12:43 AM	{"media_resource.tab":719, "taxon.tab":715, "time_elapsed":{"sec":0.92, "min":0.02, "hr":0}}

volxiv2016	Thu 2021-09-23 12:25:57 AM	{"media_resource.tab":391, "taxon.tab":386, "time_elapsed":{"sec":0.92, "min":0.02, "hr":0}}
volxiv2016	Sun 2021-10-24 08:12:55 AM	{"media_resource.tab":391, "taxon.tab":386, "time_elapsed":{"sec":0.71, "min":0.01, "hr":0}}

volxv2018	Thu 2021-09-23 12:26:14 AM	{"media_resource.tab":1141, "taxon.tab":1099, "time_elapsed":{"sec":1.21, "min":0.02, "hr":0}}
volxv2018	Wed 2021-10-13 06:15:22 AM	{"media_resource.tab":1141, "taxon.tab":1099, "time_elapsed":{"sec":1.8, "min":0.03, "hr":0}}
volxv2018	Sun 2021-10-24 08:13:14 AM	{"media_resource.tab":1141, "taxon.tab":1099, "time_elapsed":{"sec":1.31, "min":0.02, "hr":0}}

Kubitzki	Thu 2021-09-23 12:27:04 AM	{"media_resource.tab":10947, "taxon.tab":10780, "time_elapsed":{"sec":24.74, "min":0.41, "hr":0.01}}
gnfinder not relaxed --- not sure ??? why exactly the same when using GNRD
Kubitzki	Sun 2021-10-24 08:14:20 AM	{"media_resource.tab":10947, "taxon.tab":10780, "time_elapsed":{"sec":25.12, "min":0.42, "hr":0.01}}
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
// $arr = array("text" => "Thalictroides, 18s per doz.\nvitifoiia, Is. 6d. each Gadus morhua\nCalopogon, or Cymbidium pul-\n\ncheilum, 1 5s. per doz.\nConostylis americana, 2i. 6d.\n",
$arr = array("text" => "Thalictroides, 18s per doz.\nvitifoiia, Is. 6d. each\nCalopogon, or Cymbidium pul-\n\ncheilum, 1 5s. per doz.\nConostylis americana, 2i. 6d.\n",
"noBayes" => false,
"oddsDetails" => false, //true adds more stats, not needed
"language" => "eng",
"wordsAround" => 0,
"verification" => false, //default false
"sources" => array(1,12,169) //orig array(1,12,169). Can also be just array()
);

$json = json_encode($arr); // exit("\n$json\n");
$str = str_replace('"', '\"', $json); //exit("\n$str\n");
$cmd = 'curl -ksS "https://gnfinder.globalnames.org/api/v1/find" -H  "accept: application/json" -H  "Content-Type: application/json" -d "'.$str.'"';
$json = shell_exec($cmd);
$obj = json_decode($json, true);
print_r($obj);
exit("\n-end-\n");
*/

/* works OK - final test
$text = "Thalictroides, 18s per doz.\nvitifoiia, Is. 6d. each Lates niloticus\nCalopogon, or Cymbidium pul-\n\ncheilum, 1 5s. per doz.\nConostylis americana, 2i. 6d.\n";
$text = "Carex breweri boott , 111. Carex morhua 142. pi. 455. 1867";
// $text = "Riccia mcallisteri L.";
// $text = "Bulbochaete cimarronea Taft , Bull. Torrey Club 62 : 282. 1935";
// $text = "Potosinae mackenzie. XerochlaenaE Holm , Am. Jour. .Sci. IV. 16 : 455 , in small";
// $text = "Telia unknown";
// $text = "Charaxes aemelia aemelia Doumet";
$text = "Micropentila adelgunda Staudinger Gadus";
// $text = "Micropentila adelgunda S. lives with Gadus morhua L.";
$text = "Leptocercini";
$text = "Belonois calypso calypso (Drury)";
// $text = "Exotylus cultus Davis";
// $text = "Inga ink nil";
$text = "Key to Subspecies of Holophygdon melanesica";
$text = "Polygonoides";
// $names = $func->get_names_from_gnfinder($text);             // regular call
$names = $func->get_names_from_gnfinder($text, array('refresh' => true));    // 2nd param to force-refresh, set it to -> true
print_r($names); exit("\n-end test-\n");
*/

/* works OK
$text = "Bulbochaete cimarronea Taft , Bull. Torrey Club 62 : 282. 1935";
// $text = "Sinfitas do Brasil Central. I. Themos olfe rsi i (Klug) (Hym";
// $text = "Gadus morhua is cool.";
$text = "Potosinae mackenzie. XerochlaenaE Holm , Am. Jour. .Sci. IV. 16 : 455 , in small";
$text = "Telia unknown";
$text = "Charaxes aemelia aemelia Doumet";
$text = "Leptocercini";
$text = "Belonois calypso calypso (Drury)";
// $text = "Exotylus cultus Davis";
// $text = "Inga ink nil";
$text = "Key to Subspecies of Holophygdon melanesica";

$obj = $func->run_gnverifier($text);        // regular call
// $obj = $func->run_gnverifier($text, 1);  // 2nd param is expire_seconds
print_r($obj); exit("\n");

$ret = $func->get_binomial_or_tri($text);
echo "\n get_binomial_or_tri: [$ret]\n";
exit("\n-end test-\n");
*/

/*
$text = "Gadus morhua L.";
// $text = "Thalictroides, 18s per doz.\nvitifoiia, Is. 6d. each\nCalopogon, or Cymbidium pul-\n\ncheilum, 1 5s. per doz.\nConostylis americana, 2i. 6d.\n";
// $text = "Potosinae mackenzie";
// $text = "Telia unknown";
// $text = "Liphyridae";
// $text = "LEPTOCERCINI";
// $text = "Exotylus cultus Davis";
$text = "Inga ink nil";
$text = "Key to Subspecies of Holophygdon melanesica";
$obj = $func->run_gnparser($text);
print_r($obj); exit("\n");
*/

/*
$row = "Riccia dictyospora M. A. Howe, Bull. Torrey";
$row = "Targionia hypophylla"; //Targionia hypophyila
echo "\n[$row]\n";
$new = $func->change_l_to_i_if_applicable($row);
exit("\n[$new]\n");
*/

// $test = "Sphagnum angstromii Hartm. f. ; Hartm. Skand. Fl";     echo "\n[".Functions::canonical_form($test)."]\n";
// $test = "Sphagnum tabulate Sull. Musci Allegh. i'^- ; . 1845";  echo "\n[".Functions::canonical_form($test)."]\n";
// $test = "gadus morhua Linn.";  echo "\n[".Functions::canonical_form($test)."]\n";
// exit("\n\n");

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
$var1 = "part";
$var2 = "Part";
if (strcmp($var1, $var2) == 0) echo "\n$var1 is equal to $var2 in a case sensitive string comparison";
else                           echo "\n$var1 is not equal to $var2 in a case sensitive string comparison";
echo "\n[".strlen("—")."]\n"; //diff hyphen
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
/*
################################################################################## check for all association file types
For BHL 15406 - NAF Fungi
php update_resources/connectors/parse_unstructured_text_memoirs.php _ '{"resource_id": "15406", "resource_name":"all_BHL"}'
php update_resources/connectors/process_SI_pdfs_memoirs.php _ '{"resource_id": "15406", "resource_name":"NAF", "doc": "BHL"}'
php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"15406", "subjects":"Description|Uses"}'
php update_resources/connectors/aggregate_NorthAF_Fungi.php 

For BHL 91362 - NAF - 1st 7 docs (host-pathogen list pattern)
php update_resources/connectors/parse_unstructured_text_memoirs.php _ '{"resource_id": "91362", "resource_name":"MotAES"}'
php update_resources/connectors/process_SI_pdfs_memoirs.php _ '{"resource_id": "91362", "resource_name":"7th BHL", "doc": ""}'
91362 doesn't have and _ENV version.
php update_resources/connectors/aggregate_91362.php

SCtZ-0614
php update_resources/connectors/parse_unstructured_text.php
php update_resources/connectors/process_SI_pdfs.php
environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"SI Contributions to Zoology", "resource_id":"SCtZ-0614", "subjects":"Uses|Description"}'

wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_5097_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_6943_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/MoftheAES_resources.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/NorthAmericanFlora.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/NorthAmericanFlora_Fungi.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/NorthAmericanFlora_Plants.tar.gz
##################################################################################
*/
?>