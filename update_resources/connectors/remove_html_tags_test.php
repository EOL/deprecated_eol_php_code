<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false; //true;

$timestart = time_elapsed();

require_library('connectors/RemoveHTMLTagsAPI');

$arr = array();
$arr[] = "the <span>quick</span> brown <a class='myclass' href='http://eol.org/page/173' target='mytarget'>fox</a>. <b>Bold text</b>. <img src='https://mydomain.com/eli.jpg'>My picture in Manila.";
$arr[] = 'the <span>quick</span> brown <a class="myclass" href="http://eol.org/page/173" target="mytarget">fox</a>. <b>Bold text</b>. <img class="class ko" src="https://mydomain.com/eli.jpg" style="..."> My picture in Manila.';
$arr[] = 'Fortin, Masson et Cie, Paris. <a href="https://biodiversitylibrary.org/page/37029493" target="_blank"><b>link to Plates</b></a>.';
$arr[] = "Michel, A. 1909 espèce (<i>Syllis cirropunctata</i>, n.sp.) <i>Syllis amica</i> Qfg";
$arr[] = 'Linnaeus (1787: 202 vol 3 of "Amoenitates academicae") <a href="https://biodiversitylibrary.org/page/55937709" >https://biodiversitylibrary.org/page/55937709</a> on Noctiluca';
$arr[] = "<a href=javascript:openNewWindow('http://content.lib.utah.edu/w/d.php?d')>Hear Northern Cricket Frog calls at the Western Sound Archive.</a>";
$arr[] = "<a target=_blank href=https://doi.org/10.1371/journal.pone.0151781>Senevirathne et al. (2016)</a> ";
$arr[] = 'Amphitrite rosea Sowerby, 1806, <a href="http://biodiversitylibrary.org/page/28913955" >original plate at BHL</a>	';
$arr[] = "<a href='http://www.fishbase.org/Summary/SpeciesSummary.cfm?Genusname=Albula&speciesname=glossodonta' target=_fishbase>FishBase</a>";
$arr[] = "Personal communication, available as .pdf file from <a href=http://zoologi.snm.ku.dk>http://zoologi.snm.ku.dk</a> (english/staff/schiøtz/list of publications).	";
$arr[] = "Personal communication, available as .pdf file from <a href='elicha'>http://zoologi.snm.ku.dk</a> (english).";
$arr[] = 'Personal communication, available as .pdf file from <a href="elicha">http://zoologi.snm.ku.dk</a> (english).';
$arr[] = "<a href=javascript:openNewWindow('http://fishbase.org')>FishBase</a>";
$arr[] = "<a href=javascript:openNewWindow('fishbase.org')>FishBase</a>";
$arr[] = '<a href=javascript:openNewWindow("http://fishbase.org")>FishBase</a>';
$arr[] = '<a href=javascript:openNewWindow("fishbase.org")>FishBase</a>';
$str = file_get_contents(CONTENT_RESOURCE_LOCAL_PATH."test.txt");
$arr[] = $str;
// print_r($arr); exit;
$i = -1;
foreach($arr as $str) { $i++;
    $new = RemoveHTMLTagsAPI::remove_html_tags($str);
    echo "\n $i. orig: [$str]\n";
    echo "\n $i. new: [$new]\n";    
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
