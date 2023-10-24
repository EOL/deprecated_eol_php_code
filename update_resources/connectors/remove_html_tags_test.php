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

$str = "<a href=javascript:openNewWindow('https://sites.google.com/view/debanlab/movies');>here.</a>";
$arr[] = $str;

$str = '<a href="http://www.fao.org/fi/eims_search/advanced_s_result.asp?JOB_NO=T0725" target="_blank">Marine mammals of the world. </a>Jefferson, T.A., S. Leatherwood &amp; M.A. Webber - 1993. FAO species identification guide. Rome, FAO. 320 p. 587 figs.';
$arr[] = $str;


// print_r($arr); exit;
$i = -1;
foreach($arr as $str) { $i++;
    $new = RemoveHTMLTagsAPI::remove_html_tags($str);
    // echo "\n $i. orig: [$str]\n";
    // echo "\n $i. x new: [$new]\n";
    
    /* Running it several times... */
    $new = RemoveHTMLTagsAPI::remove_html_tags($new);
    $new = RemoveHTMLTagsAPI::remove_html_tags($new);
    $new = RemoveHTMLTagsAPI::remove_html_tags($new);

    if    ($i == 0) if($new == "the quick brown fox (http://eol.org/page/173). Bold text. (image, https://mydomain.com/eli.jpg) My picture in Manila.") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 1) if($new == "the quick brown fox (http://eol.org/page/173). Bold text. (image, https://mydomain.com/eli.jpg) My picture in Manila.") echo "\n$i OK";  else errorx($i, $new); 
    elseif($i == 2) if($new == "Fortin, Masson et Cie, Paris. link to Plates (https://biodiversitylibrary.org/page/37029493).") echo "\n$i OK";  else errorx($i, $new);   
    elseif($i == 3) if($new == "Michel, A. 1909 espèce (Syllis cirropunctata, n.sp.) Syllis amica Qfg") echo "\n$i OK";  else errorx($i, $new);   
    elseif($i == 4) if($new == 'Linnaeus (1787: 202 vol 3 of "Amoenitates academicae") (https://biodiversitylibrary.org/page/55937709) on Noctiluca') echo "\n$i OK";  else errorx($i, $new);   
    elseif($i == 5) if($new == "Hear Northern Cricket Frog calls at the Western Sound Archive (http://content.lib.utah.edu/w/d.php?d).") echo "\n$i OK";  else errorx($i, $new);      
    elseif($i == 6) if($new == "Senevirathne et al. (2016)") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 7) if($new == "Amphitrite rosea Sowerby, 1806, original plate at BHL (http://biodiversitylibrary.org/page/28913955)") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 8) if($new == "FishBase (http://www.fishbase.org/Summary/SpeciesSummary.cfm?Genusname=Albula&speciesname=glossodonta)") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 9) if($new == "Personal communication, available as .pdf file from http://zoologi.snm.ku.dk (english/staff/schiøtz/list of publications).") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 10) if($new == "Personal communication, available as .pdf file from http://zoologi.snm.ku.dk (english).") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 11) if($new == "Personal communication, available as .pdf file from http://zoologi.snm.ku.dk (english).") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 12) if($new == "FishBase (http://fishbase.org)") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 13) if($new == "FishBase") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 14) if($new == "FishBase (http://fishbase.org)") echo "\n$i OK";  else errorx($i, $new);   
    elseif($i == 15) if($new == "FishBase") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 16) if($new == "Annélides règne l'histoire. Paris. Plates (https://biodiversitylibrary.org). (Stebbins 2003).(image, http://amp.org/s.gif) hyperlink (http://content.edu).") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 17) if($new == "here (https://sites.google.com/view/debanlab/movies).") echo "\n$i OK";  else errorx($i, $new);
    elseif($i == 18) if($new == "Marine mammals of the world (http://www.fao.org/fi/eims_search/advanced_s_result.asp?JOB_NO=T0725). Jefferson, T.A., S. Leatherwood &amp; M.A. Webber - 1993. FAO species identification guide. Rome, FAO. 320 p. 587 figs.") echo "\n$i OK";  else errorx($i, $new);
    else echo "\nERROR: $i - not initialized.\n";
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
function errorx($i, $new)
{
    echo "\n$i ERROR: [$new]";
}
?>
