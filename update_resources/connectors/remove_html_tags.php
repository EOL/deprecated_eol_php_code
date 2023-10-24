<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false; //true;
$timestart = time_elapsed();

require_library('connectors/RemoveHTMLTagsAPI');

$str = "the <span>quick brows</span> fox <a class='myclass' href='http://eol.org/page/173' target='mytarget'>jumps over</a> the lazy dog. <b>This is bold text</b>. <img src='https://mydomain.com/eli.jpg'>My picture </img> in Manila.";
$str = 'the <span>quick brows</span> fox <a class="myclass" href="http://eol.org/page/173" target="mytarget">jumps over</a> the lazy dog. <b>This is bold text</b>. <img class="class ko" src="https://mydomain.com/eli.jpg" style="..."> My picture in Manila.';
$str = file_get_contents(CONTENT_RESOURCE_LOCAL_PATH."test.txt");
// $str = 'Fortin, Masson et Cie, Paris. <a href="https://biodiversitylibrary.org/page/37029493" target="_blank"><b>link to Plates</b></a>.';
// $str = "Michel, A. 1909 espèce (<i>Syllis cirropunctata</i>, n.sp.) <i>Syllis amica</i> Qfg";
// $str = 'Linnaeus (1787: 202 vol 3 of "Amoenitates academicae") <a href="https://biodiversitylibrary.org/page/55937709" >https://biodiversitylibrary.org/page/55937709</a> on Noctiluca';
// $str = "<a href=javascript:openNewWindow('http://content.lib.utah.edu/w/d.php?d')>Hear Northern Cricket Frog calls at the Western Sound Archive.</a>";
// $str = "<a target=_blank href=https://doi.org/10.1371/journal.pone.0151781>Senevirathne et al. (2016)</a> ";
// $str = 'Amphitrite rosea Sowerby, 1806, <a href="http://biodiversitylibrary.org/page/28913955" >original plate at BHL</a>	';
// $str = "<a href='http://www.fishbase.org/Summary/SpeciesSummary.cfm?Genusname=Albula&speciesname=glossodonta' target=_fishbase>FishBase</a>";
// $str = "Personal communication, available as .pdf file from <a href=http://zoologi.snm.ku.dk>http://zoologi.snm.ku.dk</a> (english/staff/schiøtz/list of publications).	";
// $str = "Personal communication, available as .pdf file from <a href='elicha'>http://zoologi.snm.ku.dk</a> (english).";
// $str = 'Personal communication, available as .pdf file from <a href="elicha">http://zoologi.snm.ku.dk</a> (english).';
// $str = "<a href=javascript:openNewWindow('http://fishbase.org')>FishBase</a>";
// $str = "<a href=javascript:openNewWindow('fishbase.org')>FishBase</a>";

$str = '<a href=javascript:openNewWindow("http://fishbase.org")>FishBase</a>';
$str = '<a href=javascript:openNewWindow("fishbase.org")>FishBase</a>';
$str = "<a href=javascript:openNewWindow('https://sites.google.com/view/debanlab/movies');>here.</a>";
$str = '<a href="http://www.fao.org/fi/eims_search/advanced_s_result.asp?JOB_NO=T0725" target="_blank">Marine mammals of the world. </a>Jefferson, T.A., S. Leatherwood &amp; M.A. Webber - 1993. FAO species identification guide. Rome, FAO. 320 p. 587 figs. .&nbsp;';



$new = RemoveHTMLTagsAPI::remove_html_tags($str);
echo "\norig: [$str]\n";
echo "\nnew: [$new]\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
