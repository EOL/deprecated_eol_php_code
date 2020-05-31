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

As of May 28, 2020
[change the associationType to pathogen_of] => 168
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1098
[2. Records of plants parasitizing animals are likely to be errors] => 1236
[3. Records of plants having animals as hosts are likely to be errors] => 5861
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 978
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 1411
Total rows = 10,584

As of May 30|31, 2020 - Mac Mini
[change the associationType to pathogen_of] => 168
[1. Records of non-carnivorous plants eating animals are likely to be errors] => 1099
[2. Records of plants parasitizing animals are likely to be errors] => 1237
[3. Records of plants having animals as hosts are likely to be errors] => 5861
[4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors] => 987
[5. Records of plants laying eggs are likely to be errors] => 0
[6. Records of other organisms parasitizing or eating viruses are likely to be errors] => 1411
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
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


// /* //main operation
require_library('connectors/DwCA_Utility');
$resource_id = "globi_associations";
$dwca = 'https://depot.globalbioticinteractions.org/snapshot/target/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
// $dwca = 'http://localhost/cp/GloBI_2019/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
$func = new DwCA_Utility($resource_id, $dwca);

/*reminder upper-case used in meta.xml e.g. 'http://rs.tdwg.org/dwc/terms/Taxon', 'http://eol.org/schema/reference/Reference' */
$preferred_rowtypes = array('http://eol.org/schema/reference/reference'); //was forced to lower case in DwCA_Utility.php

$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, true, $timestart);
// */
?>