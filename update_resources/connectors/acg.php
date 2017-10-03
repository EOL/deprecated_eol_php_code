<?php
namespace php_active_record;
/* connector for Guanacaste Conservation Area (ACG) in Costa Rica (COLLAB-510)
estimated execution time:
Connector grabs data from the site, assembles the EOL archive file
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");

// $f = "Liomys-salvini/Liomys-trapping-session-crew-morning-of-10-January-1991.jpg";
// print_r(pathinfo($f));
// exit;

// $s = " tros (Fig. 5). En la (Fig. 6) se ";
// $s = "Tercer estadío Zaretis itys. En el tercer estadio para el dia 21 Julio 2012 como seobserva, cambio de color y tamaño es de color café con manchas negras en el cuerpo yuna línea café a los costados, (Fig.3) y su cabeza es de color cafe con líneas blancas (Fig.4) ella mide 15 milimetros ,y esta comiendo en el ápice de las hojas, y conforme vacreciendo ella va ir cambiando.<a href='http://www.acguanacaste.ac.cr/images/parataxonomos/anabelle-cordoba/Zaretis-itysAnabelle-Cordoba-1enero2013-3.jpg'>(Fig. 3)</a>";
// $s = "(Figs. 8, 9, 10 y 11) lskdfsdkfs; tros (Fig. 5). En (fig 3)la (Fig. 6) se (figura 4)";
// get_figure_nos($s);
// exit;

require_library('connectors/ACGuanacasteAPI');
$timestart = time_elapsed();
$resource_id = 1;
$func = new ACGuanacasteAPI($resource_id);
$func->get_all_taxa($resource_id);
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

// function get_figure_nos($string)
// {
//     $final = array();
//     if(preg_match_all("/\(fig(.*?)\)/ims", $string, $matches)) 
//     {
//         foreach($matches[1] as $match)
//         {
//             // separators are either ',' or 'y' or 'and'
//             $arr = explode(",", $match);
//             $arr = array_merge($arr, explode("y", $match));
//             $arr = array_merge($arr, explode("and", $match));
//             foreach($arr as $r)
//             {
//                 $r = str_ireplace(array(" ", "."), "", $r); 
//                 preg_match("|\d+|", $r, $m); // get only the numeric
//                 $final[] = $m[0];
//             }
//         }
//     }
//     $final = array_unique($final);
//     asort($final);
//     $final = array_values($final);
//     return $final;
// }

?>