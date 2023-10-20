<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/RemoveHTMLTagsAPI');

$str = "the <span>quick brows</span> fox <a class='myclass' href='http://eol.org/page/173' target='mytarget'>jumps over</a> the lazy dog. <b>This is bold text</b>. <img src='https://mydomain.com/eli.jpg'>My picture </img> in Manila.";
$str = 'the <span>quick brows</span> fox <a class="myclass" href="http://eol.org/page/173" target="mytarget">jumps over</a> the lazy dog. <b>This is bold text</b>. <img class="class ko" src="https://mydomain.com/eli.jpg" style="..."> My picture in Manila.';
$str = file_get_contents(CONTENT_RESOURCE_LOCAL_PATH."test.txt");
// $str = 'Fortin, Masson et Cie, Paris. <a href="https://biodiversitylibrary.org/page/37029493" target="_blank"><b>link to Plates</b></a>.';
// $str = "Michel, A. 1909. Sur les divers types de stolons chez les Syllidiens, spécialement sur une nouvelle espèce (<i>Syllis cirropunctata</i>, n.sp.) à stolon acéphale et 
// sur la réobservation du stolon tétracère de <i>Syllis amica</i> Qfg. Comptes Rendus de l'Académie des Science, Paris 148: 318-320.		
// Sur les divers types de stolons chez les Syllidiens, spécialement sur une nouvelle espèce (<i>Syllis cirropunctata</i>, n.sp.) à stolon acéphale et sur 
// la réobservation du stolon tétracère de <i>Syllis amica</i> Qfg					";
// $str = 'Linnaeus (1787: 202 vol 3 of "Amoenitates academicae") <a href="https://biodiversitylibrary.org/page/55937709" >https://biodiversitylibrary.org/page/55937709</a> on Noctiluca';

// $str = '';
// (Stebbins 2003).<a href=javascript:openNewWindow('http://content.lib.utah.edu/cdm4/item_viewer.php?CISOROOT=/wss&CISOPTR=955&CISOBOX=1&REC=1')>
// <img align="top" src="http://amphibiaweb.org/images/sound3.gif"> Hear Northern Cricket Frog calls at the Western Sound Archive.</a>

// $str = "<a href=javascript:openNewWindow('http://content.lib.utah.edu/w/d.php?d')>Hear Northern Cricket Frog calls at the Western Sound Archive.</a>";

$str = "<a target=_blank href=https://doi.org/10.1371/journal.pone.0151781>Senevirathne et al. (2016)</a> ";
$str = 'Amphitrite rosea Sowerby, 1806, <a href="http://biodiversitylibrary.org/page/28913955" >original plate at BHL</a>	';
$str = "<a href='http://www.fishbase.org/Summary/SpeciesSummary.cfm?Genusname=Albula&speciesname=glossodonta' target=_fishbase>FishBase</a>";

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
