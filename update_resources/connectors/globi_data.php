<?php
namespace php_active_record;
/* DATA-1812
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
ini_set('memory_limit','9096M'); //required
$resource_id = "globi_associations";

// /* //main operation
$dwca = 'https://depot.globalbioticinteractions.org/snapshot/target/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
// $dwca = 'http://localhost/cp/GloBI_2019/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
$func = new DwCA_Utility($resource_id, $dwca);
$preferred_rowtypes = array('http://eol.org/schema/reference/reference'); //was forced to lower case in DwCA_Utility.php
$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, false, $timestart); //3rd param true means delete folder
$func = false; //close memory
// */

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

// globi_associations   Mon 2021-06-14 03:47:56 AM          {"association.tab":2372284, "occurrence_specific.tab":3863894, "reference.tab":1746832, "taxon.tab":320638, "time_elapsed":{"sec":5715.38, "min":95.26, "hr":1.59}}
// globi_associations_final Mon 2021-06-14 04:59:55 AM  {"association.tab":2354206, "occurrence_specific.tab":3830488, "reference.tab":1730724, "taxon.tab":319496, "time_elapsed":{"sec":10034.44, "min":167.24, "hr":2.79}}
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
?>