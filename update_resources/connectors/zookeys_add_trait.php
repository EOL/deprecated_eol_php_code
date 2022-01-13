<?php
namespace php_active_record;
/* Original notes from: Pensoft_annotator project -> environments_2_eol.php
ZooKeys (20)*
php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"Pensoft_journals", "resource_id":"20", "subjects":"GeneralDescription|Distribution|Description"}'
http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription: 5898
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 4931
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description: 2
Above generates: --> 20_ENV.tar.gz
Then run: zookeys_add_trait.php --> makes use of [20_ENV.tar.gz] and generates [20_ENV_final.tar.gz]

20_ENV	        Wed 2022-01-05 08:09:41 AM	{"agent.tab":2031,                  "MoF.tab":9771, "media.tab":28979, "occur.tab":9771, "reference.tab":1420, "taxon.tab":8830, "time_elapsed":{"sec":26703.6, "min":445.06, "hr":7.42}}
20_ENV_final	Tue 2022-01-11 07:13:42 AM	{"agent.tab":2031, "assoc.tab":21,  "MoF.tab":9771, "media.tab":28979, "occur.tab":9803, "reference.tab":1420, "taxon.tab":8847, "time_elapsed":{"sec":202.26, "min":3.37, "hr":0.06}}
20_ENV	        Wed 2022-01-12 03:28:32 AM	{"agent.tab":2031,                  "MoF.tab":9771, "media.tab":28979, "occur.tab":9771, "reference.tab":1420, "taxon.tab":8830, "time_elapsed":{"sec":52.31, "min":0.87, "hr":0.01}}
20_ENV_final	Wed 2022-01-12 03:29:08 AM	{"agent.tab":2031, "assoc.tab":21,  "MoF.tab":9771, "media.tab":28979, "occur.tab":9803, "reference.tab":1420, "taxon.tab":8847, "time_elapsed":{"sec":34.83, "min":0.58, "hr":0.01}}
20_ENV	        Wed 2022-01-12 08:11:39 AM	{"agent.tab":2031,                  "MoF.tab":9771, "media.tab":28979, "occur.tab":9771, "reference.tab":1420, "taxon.tab":8830, "time_elapsed":{"sec":88.93, "min":1.48, "hr":0.02}}
20_ENV_final	Wed 2022-01-12 08:12:14 AM	{"agent.tab":2031, "assoc.tab":21,  "MoF.tab":9771, "media.tab":28979, "occur.tab":9803, "reference.tab":1420, "taxon.tab":8847, "time_elapsed":{"sec":33.89, "min":0.56, "hr":0.01}}

This can be a generic script for other resources: purpose of adding trait to existing DwCA resource.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = "20_ENV_final";
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/20_ENV.tar.gz';
process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    /* Orig in meta.xml has capital letters. Just a note reminder. */
    // $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon'); //debug only during dev
    $preferred_rowtypes = array(); //blank meaning all existing rowtypes will just be carried over
    /* new Associations will be added in AddTrait2EoLDwCA.php, which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>