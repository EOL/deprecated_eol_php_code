<?php
namespace php_active_record;
/*
India Biodiversity Portal archive
Partner provides an archive file, but needs adjustments:
- there are text(s) without descriptions
- invalid license - http://creativecommons.org/licenses/by-nc-nd/3.0/
- identical mediaURL

estimated execution time: 5 mins.

    http://rs.tdwg.org/dwc/terms/taxon:          14971  -       -
    http://purl.org/dc/dcmitype/StillImage:      3051   2919    2888
    http://purl.org/dc/dcmitype/Text:            58645  -       -
    http://eol.org/schema/reference/reference:   29199  -       -
    http://eol.org/schema/agent/agent:           481    -       -
    http://rs.gbif.org/terms/1.0/vernacularname: 28669  -       -

[26223436] => http://media.eol.org/content/2013/09/13/13/89532_88_88.jpg
[26320816] => http://media.eol.org/content/2013/09/13/11/18469_88_88.jpg
[26326626] => http://media.eol.org/content/2013/09/13/12/34302_88_88.jpg
[26223394] => http://media.eol.org/content/2013/09/13/13/88845_88_88.jpg
[26222959] => http://media.eol.org/content/2013/09/13/12/94524_88_88.jpg
[26223409] => http://media.eol.org/content/2013/09/13/13/43549_88_88.jpg
[26223408] => http://media.eol.org/content/2013/09/13/13/78495_88_88.jpg
[26222037] => http://media.eol.org/content/2013/09/13/11/73043_88_88.jpg
[26222038] => http://media.eol.org/content/2013/09/13/11/76788_88_88.jpg
[26222966] => http://media.eol.org/content/2013/09/13/12/43810_88_88.jpg
[26223174] => http://media.eol.org/content/2013/09/13/13/70686_88_88.jpg
[26326644] => http://media.eol.org/content/2013/09/13/12/25697_88_88.jpg
[26326643] => http://media.eol.org/content/2013/09/13/12/19237_88_88.jpg
[26326646] => http://media.eol.org/content/2013/09/13/12/42106_88_88.jpg
[26326645] => http://media.eol.org/content/2013/09/13/12/96300_88_88.jpg
[26324475] => http://media.eol.org/content/2013/09/13/12/34253_88_88.jpg
[26222112] => http://media.eol.org/content/2013/09/13/11/15058_88_88.jpg
[26222962] => http://media.eol.org/content/2013/09/13/12/14207_88_88.jpg
[26222669] => http://media.eol.org/content/2013/09/13/12/82699_88_88.jpg
[26222670] => http://media.eol.org/content/2013/09/13/12/28789_88_88.jpg
[26327080] => http://media.eol.org/content/2013/09/13/13/78112_88_88.jpg
[26326632] => http://media.eol.org/content/2013/09/13/12/75038_88_88.jpg
[26326627] => http://media.eol.org/content/2013/09/13/12/64309_88_88.jpg
[26223410] => http://media.eol.org/content/2013/09/13/13/36591_88_88.jpg
[26222172] => http://media.eol.org/content/2013/09/13/12/31035_88_88.jpg
[26324892] => http://media.eol.org/content/2013/09/13/12/16245_88_88.jpg
[26222598] => http://media.eol.org/content/2013/09/13/12/66567_88_88.jpg
[26326621] => http://media.eol.org/content/2013/09/13/12/90466_88_88.jpg
[26222965] => http://media.eol.org/content/2013/09/13/12/04654_88_88.jpg
[26223411] => http://media.eol.org/content/2013/09/13/13/26979_88_88.jpg
[26326622] => http://media.eol.org/content/2013/09/13/12/25064_88_88.jpg
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IndiaBiodiversityPortalAPI');
$timestart = time_elapsed();
$resource_id = 520;
$func = new IndiaBiodiversityPortalAPI($resource_id);
/* $func->check_if_image_is_broken(); exit; */
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>