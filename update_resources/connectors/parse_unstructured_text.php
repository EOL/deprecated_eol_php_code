<?php
namespace php_active_record;
/* DATA-1877: textmining more unstructured text
start of many iterations:
10088_5097	Tue 2021-04-13 01:26:39 PM	{"media_resource.tab":14220, "taxon.tab":13291, "time_elapsed":{"sec":1695.13, "min":28.25, "hr":0.47}}
10088_5097	Wed 2021-04-14 01:53:53 AM	{"media_resource.tab":14558, "taxon.tab":13606, "time_elapsed":{"sec":550.52, "min":9.18, "hr":0.15}}
10088_5097	Wed 2021-04-14 03:58:19 AM	{"media_resource.tab":12773, "taxon.tab":12084, "time_elapsed":{"sec":92.08, "min":1.53, "hr":0.03}}
10088_5097	Wed 2021-04-14 09:14:43 AM	{"media_resource.tab":12592, "taxon.tab":11935, "time_elapsed":{"sec":1255.52, "min":20.93, "hr":0.35}}
almost clean, first submission for review
10088_5097	Wed 2021-04-14 10:36:02 AM	{"media_resource.tab":12587, "taxon.tab":11930, "time_elapsed":{"sec":297.23, "min":4.95, "hr":0.08}}
10088_5097	Thu 2021-04-15 11:15:46 AM	{"media_resource.tab":12601, "taxon.tab":11936, "time_elapsed":{"sec":401.48, "min":6.69, "hr":0.11}}
10088_5097	Thu 2021-04-15 11:21:17 AM	{"media_resource.tab":12601, "taxon.tab":11936, "time_elapsed":{"sec":99.29, "min":1.65, "hr":0.03}}
10088_5097	Thu 2021-04-15 12:00:54 PM	{"media_resource.tab":12582, "taxon.tab":11919, "time_elapsed":{"sec":96.86, "min":1.61, "hr":0.03}}
10088_5097_ENV	Thu 2021-04-15 01:44:35 PM	{"measurement_or_fact_specific.tab":50022, "media_resource.tab":12582, "occurrence_specific.tab":50022, "taxon.tab":11919, "time_elapsed":{"sec":5788.24, "min":96.47, "hr":1.61}}
start where species sections with < 60 chars were removed: consistent, expected slightly decrease in all tables.
10088_5097	Sun 2021-04-18 01:30:45 PM	{"media_resource.tab":12211, "taxon.tab":11574, "time_elapsed":{"sec":362.79, "min":6.05, "hr":0.1}}
10088_5097_ENV	Sun 2021-04-18 01:42:34 PM	{"measurement_or_fact_specific.tab":49757, "media_resource.tab":12211, "occurrence_specific.tab":49757, "taxon.tab":11574, "time_elapsed":{"sec":704.52, "min":11.74, "hr":0.2}}
10088_5097	Mon 2021-04-19 09:17:13 AM	{"media_resource.tab":12211, "taxon.tab":11574, "time_elapsed":{"sec":405.85, "min":6.76, "hr":0.11}}
other adjustments:
10088_5097	Mon 2021-04-19 11:13:38 AM	{"media_resource.tab":12211, "taxon.tab":11574, "time_elapsed":{"sec":103.12, "min":1.72, "hr":0.03}}
10088_5097_ENV	Tue 2021-04-20 12:02:09 AM	{"measurement_or_fact_specific.tab":48153, "media_resource.tab":12211, "occurrence_specific.tab":48153, "taxon.tab":11574, "time_elapsed":{"sec":46101.88, "min":768.36, "hr":12.81}}
start here: remove any terms from the geographic ontology that include the string /ENVO_
10088_5097	Tue 2021-04-20 11:10:47 PM	{"media_resource.tab":12211, "taxon.tab":11574, "time_elapsed":{"sec":820.79, "min":13.68, "hr":0.23}}
10088_5097_ENV	Wed 2021-04-21 12:48:11 AM	{"measurement_or_fact_specific.tab":46711, "media_resource.tab":12211, "occurrence_specific.tab":46711, "taxon.tab":11574, "time_elapsed":{"sec":5834.72, "min":97.25, "hr":1.62}}
->expected decrease in MoF

10088_5097	Wed 2021-04-21 07:57:54 AM	{"association.tab":159, "media_resource.tab":12103, "occurrence.tab":170, "taxon.tab":11606, "time_elapsed":{"sec":109.99, "min":1.83, "hr":0.03}}
10088_5097_ENV	Wed 2021-04-21 08:47:34 AM	{"association.tab":159, "measurement_or_fact_specific.tab":45913, "media_resource.tab":12103, "occurrence.tab":170, "occurrence_specific.tab":45913, "taxon.tab":11606, "time_elapsed":{"sec":103.1, "min":1.72, "hr":0.03}}
good status: 
10088_5097	Wed 2021-04-21 09:31:33 AM	{"association.tab":159, "media_resource.tab":12103, "occurrence.tab":170, "taxon.tab":11606, "time_elapsed":{"sec":284.58, "min":4.74, "hr":0.08}}
10088_5097_ENV	Wed 2021-04-21 09:33:26 AM	{"association.tab":159, "measurement_or_fact_specific.tab":45913, "media_resource.tab":12103, "occurrence.tab":170, "occurrence_specific.tab":45913, "taxon.tab":11606, "time_elapsed":{"sec":106.64, "min":1.78, "hr":0.03}}
10088_5097	Wed 2021-04-21 12:31:24 PM	{"association.tab":157, "media_resource.tab":12100, "occurrence.tab":162, "taxon.tab":11596, "time_elapsed":{"sec":511.77, "min":8.53, "hr":0.14}}
10088_5097_ENV	Wed 2021-04-21 12:45:28 PM	{"association.tab":157, "measurement_or_fact_specific.tab":45913, "media_resource.tab":12100, "occurrence.tab":162, "occurrence_specific.tab":45913, "taxon.tab":11596, "time_elapsed":{"sec":836, "min":13.93, "hr":0.23}}
start where "(as E. ramosus)" is removed: expected decrease in associations
10088_5097	Thu 2021-04-22 06:43:27 AM	{"association.tab":136, "media_resource.tab":12100, "occurrence.tab":145, "taxon.tab":11580, "time_elapsed":{"sec":498.54, "min":8.31, "hr":0.14}}
10088_5097_ENV	Thu 2021-04-22 06:53:24 AM	{"association.tab":136, "measurement_or_fact_specific.tab":45913, "media_resource.tab":12100, "occurrence_specific.tab":46058, "taxon.tab":11580, "time_elapsed":{"sec":592, "min":9.87, "hr":0.16}}
after fixing and started bringing in associations from e.g. HOST., and other adjustments.
10088_5097	Fri 2021-04-23 01:43:14 AM	{"association.tab":363, "media_resource.tab":12109, "occurrence.tab":428, "taxon.tab":11732, "time_elapsed":{"sec":246.78, "min":4.11, "hr":0.07}}
10088_5097_ENV	Fri 2021-04-23 02:14:12 AM	{"association.tab":363, "measurement_or_fact_specific.tab":45915, "media_resource.tab":12109, "occurrence_specific.tab":46343, "taxon.tab":11732, "time_elapsed":{"sec":1849.73, "min":30.83, "hr":0.51}}

10088_5097	Fri 2021-04-23 10:38:22 PM	{"association.tab":358, "media_resource.tab":12372, "occurrence.tab":422, "taxon.tab":11963, "time_elapsed":{"sec":544.41, "min":9.07, "hr":0.15}}
10088_5097_ENV	Fri 2021-04-23 10:49:30 PM	{"association.tab":358, "measurement_or_fact_specific.tab":45915, "media_resource.tab":12372, "occurrence_specific.tab":46337, "taxon.tab":11963, "time_elapsed":{"sec":661.79, "min":11.03, "hr":0.18}}
after some updates
10088_5097	Tue 2021-04-27 12:06:18 AM	{"association.tab":358, "media_resource.tab":12218, "occurrence.tab":422, "taxon.tab":11835, "time_elapsed":{"sec":261.58, "min":4.36, "hr":0.07}}
10088_5097_ENV	Tue 2021-04-27 01:42:33 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47476, "media_resource.tab":12218, "occurrence_specific.tab":47898, "taxon.tab":11835, "time_elapsed":{"sec":5765.42, "min":96.09, "hr":1.6}}

10088_5097	Tue 2021-04-27 03:16:36 AM	{"association.tab":358, "media_resource.tab":12216, "occurrence.tab":422, "taxon.tab":11834, "time_elapsed":{"sec":777.37, "min":12.96, "hr":0.22}}
10088_5097_ENV	Tue 2021-04-27 03:30:23 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47474, "media_resource.tab":12216, "occurrence_specific.tab":47896, "taxon.tab":11834, "time_elapsed":{"sec":818.91, "min":13.65, "hr":0.23}}

10088_5097	Tue 2021-04-27 11:10:27 AM	{"association.tab":358, "media_resource.tab":12216, "occurrence.tab":422, "taxon.tab":11834, "time_elapsed":{"sec":783.36, "min":13.06, "hr":0.22}}
10088_5097_ENV	Tue 2021-04-27 11:22:32 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47436, "media_resource.tab":12216, "occurrence_specific.tab":47858, "taxon.tab":11834, "time_elapsed":{"sec":715.81, "min":11.93, "hr":0.2}}

10088_5097	Tue 2021-04-27 11:37:20 AM	{"association.tab":358, "media_resource.tab":12216, "occurrence.tab":422, "taxon.tab":11834, "time_elapsed":{"sec":443.47, "min":7.39, "hr":0.12}}
10088_5097_ENV	Tue 2021-04-27 11:44:58 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47436, "media_resource.tab":12216, "occurrence_specific.tab":47858, "taxon.tab":11834, "time_elapsed":{"sec":452.07, "min":7.53, "hr":0.13}}

list-type incorporated:
10088_5097	Wed 2021-04-28 08:04:40 AM	{"association.tab":358, "media_resource.tab":13188, "occurrence.tab":422, "taxon.tab":12795, "time_elapsed":{"sec":481.93, "min":8.03, "hr":0.13}}
10088_5097_ENV	Wed 2021-04-28 08:16:42 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47468, "media_resource.tab":13188, "occurrence_specific.tab":47890, "taxon.tab":12795, "time_elapsed":{"sec":711.2, "min":11.85, "hr":0.2}}

subject = #uses removed: BEST STATS FOR COMPARISON
10088_5097	Wed 2021-04-28 09:32:45 AM	{"association.tab":358, "media_resource.tab":13188, "occurrence.tab":422, "taxon.tab":12795, "time_elapsed":{"sec":482.53, "min":8.04, "hr":0.13}}
10088_5097_ENV	Wed 2021-04-28 09:40:11 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47468, "media_resource.tab":12222, "occurrence_specific.tab":47890, "taxon.tab":12795, "time_elapsed":{"sec":435.51, "min":7.26, "hr":0.12}}

expected increase in Media text objects
10088_5097	Wed 2021-04-28 12:00:30 PM	{"association.tab":358, "media_resource.tab":13306, "occurrence.tab":422, "taxon.tab":12867, "time_elapsed":{"sec":670.12, "min":11.17, "hr":0.19}}
10088_5097_ENV	Wed 2021-04-28 12:12:55 PM	{"association.tab":358, "measurement_or_fact_specific.tab":47468, "media_resource.tab":12222, "occurrence_specific.tab":47890, "taxon.tab":12867, "time_elapsed":{"sec":734.34, "min":12.24, "hr":0.2}}

expected some big changes in MoF fromt this point:
10088_5097	Sun 2021-05-02 10:30:35 PM	{"association.tab":358, "media_resource.tab":13306, "occurrence.tab":422, "taxon.tab":12867, "time_elapsed":{"sec":520.91, "min":8.68, "hr":0.14}}
10088_5097_ENV	Sun 2021-05-02 10:51:34 PM	{"association.tab":358, "measurement_or_fact_specific.tab":49319, "media_resource.tab":12222, "occurrence_specific.tab":49741, "taxon.tab":12867, "time_elapsed":{"sec":1249.22, "min":20.82, "hr":0.35}}

10088_5097	Mon 2021-05-03 12:56:04 AM	{"association.tab":358, "media_resource.tab":13770, "occurrence.tab":422, "taxon.tab":14014, "time_elapsed":{"sec":5561.63, "min":92.69, "hr":1.54}}
10088_5097_ENV	Mon 2021-05-03 01:03:49 AM	{"association.tab":358, "measurement_or_fact_specific.tab":49055, "media_resource.tab":12258, "occurrence_specific.tab":49477, "taxon.tab":14014, "time_elapsed":{"sec":455.55, "min":7.59, "hr":0.13}}

some epubs got included, expected increase:
10088_5097	Tue 2021-05-04 11:38:37 AM	{"association.tab":415, "media_resource.tab":13859, "occurrence.tab":467, "taxon.tab":14140, "time_elapsed":{"sec":4140.64, "min":69.01, "hr":1.15}}
10088_5097_ENV	Wed 2021-05-05 03:00:45 PM	{"association.tab":415, "measurement_or_fact_specific.tab":49290, "media_resource.tab":12347, "occurrence_specific.tab":49757, "taxon.tab":14140, "time_elapsed":{"sec":45763.69, "min":762.73, "hr":12.71}}
last run so far:
10088_5097	Wed 2021-05-05 11:04:45 PM	{"association.tab":413, "media_resource.tab":13697, "occurrence.tab":465, "taxon.tab":14001, "time_elapsed":{"sec":950.83, "min":15.85, "hr":0.26}}
10088_5097_ENV	Thu 2021-05-06 12:00:58 AM	{"association.tab":413, "measurement_or_fact_specific.tab":49419, "media_resource.tab":12185, "occurrence_specific.tab":49884, "taxon.tab":14001, "time_elapsed":{"sec":3362.21, "min":56.04, "hr":0.93}}

10088_5097	Thu 2021-05-06 04:47:50 AM	{"association.tab":413, "media_resource.tab":13709, "occurrence.tab":465, "taxon.tab":14011, "time_elapsed":{"sec":1530.71, "min":25.51, "hr":0.43}}
10088_5097_ENV	Thu 2021-05-06 05:40:09 AM	{"association.tab":413, "measurement_or_fact_specific.tab":49266, "media_resource.tab":12185, "occurrence_specific.tab":49731, "taxon.tab":14011, "time_elapsed":{"sec":3132.47, "min":52.21, "hr":0.87}}

10088_5097	Thu 2021-05-06 09:20:29 AM	{"association.tab":413, "media_resource.tab":13697, "occurrence.tab":465, "taxon.tab":14001, "time_elapsed":{"sec":743.97, "min":12.4, "hr":0.21}}
10088_5097_ENV	Thu 2021-05-06 09:30:32 AM	{"association.tab":413, "measurement_or_fact_specific.tab":49283, "media_resource.tab":12185, "occurrence_specific.tab":49748, "taxon.tab":14001, "time_elapsed":{"sec":593.88, "min":9.9, "hr":0.16}}
good stable stat:
10088_5097	Thu 2021-05-06 10:14:48 AM	{"association.tab":413, "media_resource.tab":13709, "occurrence.tab":465, "taxon.tab":14011, "time_elapsed":{"sec":626.03, "min":10.43, "hr":0.17}}
10088_5097_ENV	Thu 2021-05-06 10:17:00 AM	{"association.tab":413, "measurement_or_fact_specific.tab":49266, "media_resource.tab":12185, "occurrence_specific.tab":49731, "taxon.tab":14011, "time_elapsed":{"sec":126.81, "min":2.11, "hr":0.04}}

10088_5097	Sat 2021-05-08 09:51:11 AM	{"association.tab":413, "media_resource.tab":13579, "occurrence.tab":465, "taxon.tab":13204, "time_elapsed":{"sec":1014.91, "min":16.92, "hr":0.28}}
10088_5097_ENV	Sat 2021-05-08 10:01:10 AM	{"association.tab":413, "measurement_or_fact_specific.tab":49283, "media_resource.tab":12173, "occurrence_specific.tab":49748, "taxon.tab":13204, "time_elapsed":{"sec":591.33, "min":9.86, "hr":0.16}}
Submitted:
10088_5097	Mon 2021-05-10 08:54:34 AM	{"association.tab":413, "media_resource.tab":13579, "occurrence.tab":465, "taxon.tab":13204, "time_elapsed":{"sec":872.76, "min":14.55, "hr":0.24}}
10088_5097_ENV	Mon 2021-05-10 09:04:03 AM	{"association.tab":413, "measurement_or_fact_specific.tab":49147, "media_resource.tab":12173, "occurrence_specific.tab":49612, "taxon.tab":13204, "time_elapsed":{"sec":562.4, "min":9.37, "hr":0.16}}
after working with repo 2:
10088_5097	Thu 2021-05-13 05:36:03 PM	{"association.tab":365, "media_resource.tab":13218, "occurrence.tab":418, "taxon.tab":12575, "time_elapsed":{"sec":29283.26, "min":488.05, "hr":8.13}}
10088_5097_ENV	Thu 2021-05-13 05:43:10 PM	{"association.tab":365, "measurement_or_fact_specific.tab":49147, "media_resource.tab":11812, "occurrence_specific.tab":49565, "taxon.tab":12575, "time_elapsed":{"sec":420.43, "min":7.01, "hr":0.12}}
fixed list-type of repo 2:
10088_5097	Sun 2021-05-16 09:42:45 PM	{"association.tab":365, "media_resource.tab":13272, "occurrence.tab":418, "taxon.tab":12629, "time_elapsed":{"sec":1619.13, "min":26.99, "hr":0.45}}
10088_5097_ENV	Sun 2021-05-16 11:29:23 PM	{"association.tab":365, "measurement_or_fact_specific.tab":47983, "media_resource.tab":11812, "occurrence_specific.tab":48401, "taxon.tab":12629, "time_elapsed":{"sec":6389.05, "min":106.48, "hr":1.77}}

10088_5097	Sun 2021-05-16 11:48:05 PM	{"association.tab":365, "media_resource.tab":13272, "occurrence.tab":418, "taxon.tab":12629, "time_elapsed":{"sec":747.56, "min":12.46, "hr":0.21}}
10088_5097_ENV	Sun 2021-05-16 11:50:31 PM	{"association.tab":365, "measurement_or_fact_specific.tab":48036, "media_resource.tab":11812, "occurrence_specific.tab":48454, "taxon.tab":12629, "time_elapsed":{"sec":137.24, "min":2.29, "hr":0.04}}

10088_5097	Mon 2021-05-17 01:23:21 AM	{"association.tab":365, "media_resource.tab":13119, "occurrence.tab":418, "taxon.tab":12526, "time_elapsed":{"sec":907.16, "min":15.12, "hr":0.25}}
10088_5097_ENV	Mon 2021-05-17 01:25:04 AM	{"association.tab":365, "measurement_or_fact_specific.tab":48036, "media_resource.tab":11591, "occurrence_specific.tab":48454, "taxon.tab":12526, "time_elapsed":{"sec":94.42, "min":1.57, "hr":0.03}}

10088_5097	Mon 2021-05-17 03:48:07 AM	{"association.tab":365, "media_resource.tab":13117, "occurrence.tab":418, "taxon.tab":12567, "time_elapsed":{"sec":748.43, "min":12.47, "hr":0.21}}
10088_5097_ENV	Mon 2021-05-17 03:49:55 AM	{"association.tab":365, "measurement_or_fact_specific.tab":47827, "media_resource.tab":11589, "occurrence_specific.tab":48245, "taxon.tab":12567, "time_elapsed":{"sec":100.56, "min":1.68, "hr":0.03}}
new stable for now:
10088_5097	Mon 2021-05-17 10:14:02 AM	{"association.tab":365, "media_resource.tab":13063, "occurrence.tab":418, "taxon.tab":12547, "time_elapsed":{"sec":755.75, "min":12.6, "hr":0.21}}
10088_5097_ENV	Mon 2021-05-17 10:30:33 AM	{"association.tab":365, "measurement_or_fact_specific.tab":47859, "media_resource.tab":11535, "occurrence_specific.tab":48277, "taxon.tab":12547, "time_elapsed":{"sec":982.78, "min":16.38, "hr":0.27}}
further decreased:
10088_5097	Tue 2021-05-18 08:10:09 AM	{"association.tab":365, "media_resource.tab":12698, "occurrence.tab":418, "taxon.tab":12187, "time_elapsed":{"sec":1363.06, "min":22.72, "hr":0.38}}
10088_5097_ENV	Tue 2021-05-18 08:19:00 AM	{"association.tab":365, "measurement_or_fact_specific.tab":47768, "media_resource.tab":11453, "occurrence_specific.tab":48186, "taxon.tab":12187, "time_elapsed":{"sec":521.58, "min":8.69, "hr":0.14}}
after putting more stop patterns from spreadsheet
10088_5097	Wed 2021-05-19 09:55:52 AM	{"association.tab":365, "media_resource.tab":12677, "occurrence.tab":418, "taxon.tab":12176, "time_elapsed":{"sec":4284.89, "min":71.41, "hr":1.19}}
10088_5097_ENV	Wed 2021-05-19 10:10:17 AM	{"association.tab":365, "measurement_or_fact_specific.tab":47207, "media_resource.tab":11434, "occurrence_specific.tab":47625, "taxon.tab":12176, "time_elapsed":{"sec":856.04, "min":14.27, "hr":0.24}}

Repo 2: Smithsonian Contributions to Botany
10088_6943	Wed 2021-05-19 11:25:00 AM	{"media_resource.tab":1649, "taxon.tab":1549, "time_elapsed":{"sec":5000.82, "min":83.35, "hr":1.39}}
10088_6943_ENV	Wed 2021-05-19 12:57:18 PM	{"measurement_or_fact_specific.tab":4854, "media_resource.tab":1487, "occurrence_specific.tab":4854, "taxon.tab":1549, "time_elapsed":{"sec":5532.33, "min":92.21, "hr":1.54}}
With growth ontology:
10088_6943	Sat 2021-05-22 04:24:44 AM	{"media_resource.tab":1649, "taxon.tab":1549, "time_elapsed":{"sec":89.38, "min":1.49, "hr":0.02}}
10088_6943_ENV	Sat 2021-05-22 04:25:50 AM	{"measurement_or_fact_specific.tab":6300, "media_resource.tab":1487, "occurrence_specific.tab":6300, "taxon.tab":1549, "time_elapsed":{"sec":57.82, "min":0.96, "hr":0.02}}
excluded 1 growth uri
10088_6943	Tue 2021-06-01 01:22:50 AM	{"media_resource.tab":1649, "taxon.tab":1549, "time_elapsed":{"sec":104.04, "min":1.73, "hr":0.03}}
10088_6943_ENV	Tue 2021-06-01 01:23:38 AM	{"measurement_or_fact_specific.tab":6234, "media_resource.tab":1487, "occurrence_specific.tab":6234, "taxon.tab":1549, "time_elapsed":{"sec":38.39, "min":0.64, "hr":0.01}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ParseListTypeAPI');
require_library('connectors/ParseUnstructuredTextAPI');
$timestart = time_elapsed();
$func = new ParseUnstructuredTextAPI();

/* parsing result of PdfParser
$filename = 'pdf2text_output.txt';
$func->parse_text_file($filename);
*/
/* parsing result pf pdftotext (legacy xpdf in EOL codebase)
$filename = 'SCtZ-0293-Hi_res.txt';
$func->parse_pdftotext_result($filename);
*/
/* parsing
$filename = 'pdf2text_output.txt';
$func->parse_pdftotext_result($filename);
*/
/* parsing SCtZ-0293-Hi_res.html
$filename = 'SCtZ-0293-Hi_res.html';
$func->parse_pdf2htmlEX_result($filename);
*/
/* parsing SCZ637_pdftotext.txt
$filename = 'SCZ637_pdftotext.txt';
$func->parse_pdftotext_result($filename);
*/

/* Start epub series: process our first file from the ticket */
$input = array('filename' => 'SCtZ-0293.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0293/');
$input = array('filename' => 'SCtZ-0001.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0001/');
$input = array('filename' => 'SCtZ-0008.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0008/');
$input = array('filename' => 'SCtZ-0016.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0016/');
$input = array('filename' => 'SCtZ-0025.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0025/');
$input = array('filename' => 'SCtZ-0011.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');

$input = array('filename' => 'SCTZ-0128.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0128/');
$input = array('filename' => 'SCtZ-0095.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0095/');
$input = array('filename' => 'SCtZ-0557.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0557/');
$input = array('filename' => 'SCtZ-0140.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0140/');
$input = array('filename' => 'SCTZ-0105.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0105/');
$input = array('filename' => 'SCtZ-0007.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/');
$input = array('filename' => 'SCtZ-0272.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0272/');
$input = array('filename' => 'SCtZ-0439.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0439/');
$input = array('filename' => 'SCTZ-0156.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0156/');
$input = array('filename' => 'SCtZ-0604.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0604/');
// -> 0604 I considered a regular species-type not a list-type

//start google sheet
$input = array('filename' => 'SCtZ-0004.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0004/');
$input = array('filename' => 'scz-0630.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/scz-0630/');
$input = array('filename' => 'SCtZ-0029.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0029/');
$input = array('filename' => 'SCtZ-0023.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0023/');
$input = array('filename' => 'SCtZ-0042.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0042/');
$input = array('filename' => 'SCtZ-0020.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0020/');
$input = array('filename' => 'SCtZ-0016.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0016/');
$input = array('filename' => 'SCtZ-0025.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0025/');
$input = array('filename' => 'SCtZ-0022.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0022/');
//May 4, 2021 Tue
$input = array('filename' => 'SCtZ-0019.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0019/');
$input = array('filename' => 'SCtZ-0002.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0002/');
$input = array('filename' => 'SCtZ-0017.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0017/');
$input = array('filename' => 'SCtZ-0009.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0009/');
//-> SCtZ-0009 no data, Has vernacular data for a good number of species though
$input = array('filename' => 'SCtZ-0003.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0003/');
$input = array('filename' => 'SCtZ-0616.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0616/');
$input = array('filename' => 'SCtZ-0617.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0617/');
$input = array('filename' => 'SCtZ-0615.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0615/');
//-> negative test, should not get any data
$input = array('filename' => 'SCtZ-0614.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/');
//-> has associations, species sections, no lists
$input = array('filename' => 'SCtZ-0612.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0612/');
$input = array('filename' => 'SCtZ-0605.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0605/');
// May 5, 2021 Wed
$input = array('filename' => 'SCtZ-0607.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0607/');
$input = array('filename' => 'SCtZ-0608.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0608/');
$input = array('filename' => 'SCtZ-0606.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0606/');
$input = array('filename' => 'SCtZ-0602.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0602/');
$input = array('filename' => 'SCtZ-0603.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0603/');
$input = array('filename' => 'SCtZ-0601.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0601/');
// // -> negative example, indeed no records created
$input = array('filename' => 'SCtZ-0598.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0598/');
$input = array('filename' => 'SCtZ-0594.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0594/');
    // wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0061/SCtZ-0061.txt

//fix weird names found by Jen:
$input = array('filename' => 'SCtZ-0355.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0355/');
$input = array('filename' => 'SCtZ-0188.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0188/');
$input = array('filename' => 'SCtZ-0559.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0559/');
$input = array('filename' => 'SCtZ-0061.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0061/');


// May 6, 2021 Thu - 2nd repo
// $input = array('filename' => 'scb-0001.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0001/');
// http://rs.tdwg.org/dwc/terms/taxon: Total: 43
// http://purl.org/dc/dcmitype/Text: 52
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 174

// May 10, 2021 - 2nd repo
// $input = array('filename' => 'scb-0003.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0003/');
// -> Jen considered as list but not really. Better to acquire is as regular species-sections type
// http://rs.tdwg.org/dwc/terms/taxon: Total: 98
// http://purl.org/dc/dcmitype/Text: 98
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 99

// $input = array('filename' => 'scb-0004.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0004/');
// http://rs.tdwg.org/dwc/terms/taxon: Total: 23
// http://purl.org/dc/dcmitype/Text: 23
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 152

// May 13, 2021 Thu
// $input = array('filename' => 'scb-0006.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0006/');
// -> no records created, skipped for the meantime
// -> I don't think it's worth accomodating this case unless it turns out to be common.
// $input = array('filename' => 'scb-0005.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0005/');
// -> as expected, didn't create any records

// $input = array('filename' => 'scb-0007.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0007/');
// http://rs.tdwg.org/dwc/terms/taxon: Total: 3
// http://purl.org/dc/dcmitype/Text: 3
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 74

// $input = array('filename' => 'scb-0009.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0009/');
// http://rs.tdwg.org/dwc/terms/taxon: Total: 10
// http://purl.org/dc/dcmitype/Text: 10
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 28



// May 17 Mon
// $input = array('filename' => 'SCtZ-0032.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0032/');
// -> 7 scinames
// $input = array('filename' => 'SCtZ-0034.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0034/');
// -> 103 scinames
// $input = array('filename' => 'SCtZ-0062.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0062/');
// -> 10 scinames
// $input = array('filename' => 'SCtZ-0067.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0067/');
// -> 16 scinames
// $input = array('filename' => 'SCtZ-0063.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0063/');
// -> 11 scinames
// $input = array('filename' => 'SCtZ-0113.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0113/');
// -> 91 scinames
// $input = array('filename' => 'SCtZ-0007.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/');
// -> 19 scinames
$input = array('filename' => 'SCTZ-0275.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0275/');
// -> 4 scinames
// $input = array('filename' => 'SCTZ-0469.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0469/');
// -> 23 scinames
// $input = array('filename' => 'SCtZ-0006.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0006/');
// -> 6 scinames

// $input = array('filename' => 'SCtZ-0614.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/');
//-> has associations (57), species sections (9), no lists
// resources/SCtZ-0614/association.tab]    :: total: [57]
// resources/SCtZ-0614/media_resource.tab] :: total: [9]
// resources/SCtZ-0614/occurrence.tab]     :: total: [57]
// resources/SCtZ-0614/taxon.tab]          :: total: [53]

// New - May 18 Tue    
// $input = array('filename' => 'scb-0013.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0013/');
// -> 11 scinames
// $input = array('filename' => 'scb-0027.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0027/');
// -> 131 scinames
// $input = array('filename' => 'scb-0094.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0094/');
// -> 116 scinames

// May 19 Wed
// $input = array('filename' => 'SCtZ-0031.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0031/');
// -> none

// $input = array('filename' => 'scb-0092.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0092/');
// -> none
$input = array('filename' => 'scb-0093.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0093/');
// -> 34 scinames

// $input = array('filename' => 'SCtZ-0084.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0084/');
// -> 27 scinames

// $input = array('filename' => 'SCtZ-0107.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0107/');

// wget https://editors.eol.org/other_files/Smithsonian/epub_10088_6943/scb-0092/scb-0092.txt


/* ---------------------------------- List-type here:
// variable lines_before_and_after_sciname is important. It is the lines before and after the "list header".

$input = array('filename' => 'SCtZ-0011.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');
//-> good list data, no species sections

$input = array('filename' => 'SCtZ-0437.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/'); //List of Freshwater Fishes of Peru
//-> good list data, very bad species sections

$input = array('filename' => 'SCtZ-0033.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0033/');
//-> good list data, a list-type with genus in one line and species in 2nd line. No species sections

// $input = array('filename' => 'SCtZ-0010.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0010/');
// $input = array('filename' => 'SCtZ-0611.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0611/');
$input = array('filename' => 'SCtZ-0613.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0613/');
//-> has good many species sections
// $input = array('filename' => 'SCtZ-0609.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0609/');
// -> 60 rows

// May 6, 2021 Thu - 2nd repo
$input = array('filename' => 'scb-0002.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0002/');
// -> http://rs.tdwg.org/dwc/terms/taxon:             Total: 162
// -> http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 165

// May 17 Mon - 1st repo
// $input = array('filename' => 'SCtZ-0018.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0018/');
//-> a list-type with genus in one line and species in 2nd line BUT no traits detected by Pensoft AND ALSO has good species sections
// -> 38 scinames


    // wget https://editors.eol.org/other_files/Smithsonian/epub_10088_6943/scb-0003/scb-0003.txt

---------------------------------- */

$pdf_id = pathinfo($input['filename'], PATHINFO_FILENAME);
$input['lines_before_and_after_sciname'] = 2; //default
if(in_array($pdf_id, array('SCtZ-0007', 'SCtZ-0025', 'SCtZ-0020', 'SCtZ-0019', 'SCtZ-0011', 'SCtZ-0010', 'SCtZ-0611', 'SCtZ-0613',
    'scb-0001', 'scb-0002', 'scb-0003', 'scb-0006', 'scb-0004', 'scb-0007'))) $input['lines_before_and_after_sciname'] = 1;

if(Functions::is_production()) $input['epub_output_txts_dir'] = str_replace("/Volumes/AKiTiO4/other_files/Smithsonian/", "/extra/other_files/Smithsonian/", $input['epub_output_txts_dir']);

// /*

if(stripos($input['epub_output_txts_dir'], "epub_10088_5097") !== false) $folder = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/$pdf_id/";
if(stripos($input['epub_output_txts_dir'], "epub_10088_6943") !== false) $folder = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/$pdf_id/";

$postfix = array("_tagged.txt", "_tagged_LT.txt", "_edited.txt", "_edited_LT.txt", "_descriptions_LT.txt");
foreach($postfix as $post) {
    $txt_filename = pathinfo($folder, PATHINFO_BASENAME)."$post";
    $txt_filename = $folder."/".$txt_filename;
    echo "\n$txt_filename - ";
    if(file_exists($txt_filename)) if(unlink($txt_filename)) echo " deleted OK\n";
    else                                                     echo " does not exist\n";
}
// exit("\n-end for now-\n");
// */

$func->parse_pdftotext_result($input);

/* a utility
$func->utility_download_txt_files();
*/

/*
Real misfiled:
1. Taxonomy, sexual dimorphism, vertical distribution, and evolutionary zoogeography of the bathypelagic fish genus Stomias (Stomiatidae)
SCtZ-0031
2. Ten Rhyparus from the Western Hemisphere (Coleoptera: Scarabaeidae: Aphodiinae)	
SCtZ-0021
3.Gammaridean Amphipoda of Australia, Part III. The Phoxocephalidae
Gammaridean Amphipoda of Australia, Part I
SCtZ-0103
4.The Caridean shrimps (Crustacea:Decapoda) of the Albatross Philippine Expedition, 1907-1910, Part 7: Families Atyidae, Eugonatonotidae, Rhynchocinetidae, Bathypalaemonidae, Processidae, and Hippolytidae
The Caridean Shrimps (Crustacea: Decapoda) of the Albatross Philippine Expedition, 1907–1910, Part 5: Family Alpheidae
SCTZ-0466

wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/SCtZ-0437.txt
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>