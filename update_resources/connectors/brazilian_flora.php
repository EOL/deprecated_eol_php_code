<?php
namespace php_active_record;
/* 
wget    http://ipt.jbrj.gov.br/jbrj/archive.do?r=lista_especies_flora_brasil -O lista_especies_flora_brasil.zip
wget -q http://ipt.jbrj.gov.br/jbrj/archive.do?r=lista_especies_flora_brasil -O /extra/other_files/GBIF_DwCA/lista_especies_flora_brasil.zip
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

$resource_id = 'brazilian_flora';
$dwca_file = "https://editors.eol.org/other_files/GBIF_DwCA/lista_especies_flora_brasil.zip";
process_resource_url($dwca_file, $resource_id, $timestart);

// $elapsed_time_sec = time_elapsed() - $timestart;
// echo "\n\n";
// echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
// echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
// echo "\nDone processing.\n";
/* 
The vernacularname file looks pretty good as is. 
The taxon file has a couple of columns we haven't heard of, which I'm comfortable leaving out. 
The occurrences file can be constructed as a 1->1 with no additional information.
The references file is pretty close, although the references are a bit sparse. It looks like the title column is redundant with bibliographicCitation and can be ignored. 
    Could you please concatenate creator, then date, then bibliographicCitation, separated by ". " to make the fullReference?

The distribution and speciesprofile files can both go to the measurementsOrFacts file. Distribution will need a slightly convoluted mapping:

locationID will be used for measurementValue
countryCode can be ignored

measurementType is determined by establishmentMeans:

NATIVA-> http://eol.org/schema/terms/NativeRange
CULTIVADA-> http://eol.org/schema/terms/IntroducedRange
NATURALIZADA-> http://eol.org/schema/terms/IntroducedRange
unless the string "endemism":"Endemica" appears in occurrenceRemarks, in which case the measurementType is http://eol.org/terms/endemic

The strings CULTIVADA and NATURALIZADA should be preserved in measurementRemarks

occurrenceRemarks also contains another section, beginning "phytogeographicDomain": and followed by comma separated strings in square brackets. 
Each string will also be a measurementValue and should get an additional record with the same measurementType, occurrence, etc.

wrinkle: where measurementType is http://eol.org/terms/endemic for the original records, http://eol.org/schema/terms/NativeRange should be used for any accompanying records based on the occurrenceRemarks strings.

speciesprofile also has a convoluted batch of strings in lifeForm. (habitat seems to be empty for now). There may be up to three sections in each cell, of the form:

{"measurementType":["measurementValue","measurementValue"],"measurementType":["measurementValue","measurementValue"],"measurementType":["measurementValue","measurementValue"]}

if that makes it clear...

measurementTypes:

lifeForm-> http://purl.obolibrary.org/obo/FLOPO_0900022
habitat-> http://rs.tdwg.org/dwc/terms/habitat
vegetationType-> http://eol.org/schema/terms/Habitat

I'll make you a mapping for all the measurementValue strings from both files.
*/
function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    /* Orig in meta.xml has capital letters. Just a note reminder.
    rowType="http://rs.tdwg.org/dwc/terms/Taxon">
    rowType="http://rs.gbif.org/terms/1.0/VernacularName">
    rowType="http://rs.gbif.org/terms/1.0/SpeciesProfile">
    rowType="http://rs.gbif.org/terms/1.0/Reference">
    rowType="http://rs.gbif.org/terms/1.0/Distribution">
    rowType="http://rs.tdwg.org/dwc/terms/ResourceRelationship">    IGNORE
    rowType="http://rs.gbif.org/terms/1.0/TypesAndSpecimen">        IGNORE
    */

    $preferred_rowtypes = array('http://rs.gbif.org/terms/1.0/vernacularname');
    /* These will be processed in BrazilianFloraAPI.php which will be called from DwCA_Utility.php
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
}
?>