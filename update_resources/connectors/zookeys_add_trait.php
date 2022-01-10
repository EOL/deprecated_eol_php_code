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
    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
    /* This will be processed in AddTrait2EoLDwCA.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/measurementorfact
    http://rs.tdwg.org/dwc/terms/occurrence
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>