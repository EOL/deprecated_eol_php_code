<?php
namespace php_active_record;
/* */
class DwCA_Aggregator_Functions
{
    function __construct() {}
    function shorten_bibliographicCitation($meta, $bibliographicCitation)
    {   // exit("\n$meta->file_uri\n");
        // "/Volumes/AKiTiO4/eol_php_code_tmp/dir_07285//media.txt"
        $eml_file = str_ireplace("media.txt", "eml.xml", $meta->file_uri);
        if($xml = simplexml_load_file($eml_file)) { // print_r($xml);
            if($t = $xml->additionalMetadata->metadata->plaziMods) {
                $mods = $t->children("http://www.loc.gov/mods/v3"); // xmlns:mods="http://www.loc.gov/mods/v3"
                // echo "\n[".$mods->mods->typeOfResource."]\n"; //prints e.g. "text"
                $subset = trim((string) $mods->mods->relatedItem->part->detail->title);
                if($subset) {
                    // echo "\nmay subset:\n[".$subset."]\n"; //prints the subset of the bibliographicCitation --- good debug
                    $shortened = str_ireplace("($subset)", "", $bibliographicCitation);
                    $shortened = Functions::remove_whitespace($shortened);
                    if($shortened) return $shortened;
                }
            }
        }
        // exit("\nstop muna\n");
        return $bibliographicCitation;
    }
}
?>