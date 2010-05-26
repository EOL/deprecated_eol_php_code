<?php
// 
// include_once(dirname(__FILE__)."/../../../config/environment.php");
// 
// $mysqli =& $GLOBALS['mysqli_connection'];
// 
// 
// //$mysqli->truncate_tables("development");
// $mysqli->begin_transaction();
// 
// 
// parse_file();
// add_hierarchy();
// start_process();
// 
// $mysqli->commit();
// Tasks::rebuild_nested_set($GLOBALS['hierarchy']->id);
// 
// 
// $mysqli->end_transaction();
// exit;
// 
// 
// 
// 
// function parse_file()
// {
//     global $mysqli;
//     
//     $line_number = 0;
//     $FILE = fopen(dirname(__FILE__) . "/../downloads/20090609_custar_export.txt", "r");
//     if($FILE)
//     {
//         while(!feof($FILE))
//         {
//             if($line_number % 5000 == 0)
//             {
//                 echo "$line_number\n";
//                 //$mysqli->commit();
//                 //if($line_number >= 5000) return;
//             }
//             $line_number++;
//             
//             $line = fgets($FILE, 4096);
//             $line = rtrim($line, "\n\r");
//             
//             if($line_number == 1) continue;
//             
//             $parts = explode("\t", $line);
//             $id = @trim($parts[0]);
//             $parent_id = @trim($parts[1]);
//             $name = @trim($parts[2]);
//             $rank = @trim($parts[3]);
//             
//             if(!preg_match("/^[0-9]+-[0-9]+$/", $id))
//             {
//                 echo "$line_number: BAD ID<br>\n";
//                 continue;
//             }
//             
//             if($parent_id != 0 && !preg_match("/^[0-9]+-[0-9]+$/", $parent_id))
//             {
//                 echo "$line_number: BAD PARENT ID<br>\n";
//                 continue;
//             }
//             
//             if(!$name)
//             {
//                 echo "$line_number: NO NAME<br>\n";
//                 continue;
//             }
//             
//             if(in_array($rank, array('pecies', 'sp', 'spcies', 'spoecies'))) $rank = 'species';
//             if(in_array($rank, array('susbpecies'))) $rank = 'subspecies';
//             if(in_array($rank, array(')', 'UNKNOWN', 'unranked', 'NULL'))) $rank = '';
//             if(in_array($rank, array('F'))) $rank = 'family';
//             if(in_array($rank, array('V', 'Variety'))) $rank = 'variety';
//             
//             if($rank) $rank_id = Rank::insert($rank);
//             else $rank_id = 0;
//             
//             $name_id = Name::insert($name);
//             
//             if(!$name_id)
//             {
//                 echo "$line_number: NAME DIDN'T GO IN<br>\n";
//                 continue;
//             }
//             
//             $GLOBALS['children'][$parent_id][] = $id;
//             $GLOBALS['names'][$id] = $name_id;
//             $GLOBALS['ranks'][$id] = $rank_id;
//         }
//         fclose($FILE);
//     }
// }
// 
// 
// 
// function add_hierarchy()
// {
//     global $mysqli;
//     
//     $agent_params = array(      "full_name"     => "CU*STAR",
//                                 "acronym"       => "CU*STAR",
//                                 "homepage"      => "http://starcentral.mbl.edu/");
//     
//     $agent_id = Agent::insert(Functions::mock_object("Agent", $agent_params));
//     $hierarchy_id = Hierarchy::find_by_agent_id($agent_id);
//     if($hierarchy_id)
//     {
//         $hierarchy = new Hierarchy($hierarchy_id);
//         $hierarchy_group_id = $hierarchy->hierarchy_group_id;
//         $hierarchy_group_version = $hierarchy->latest_group_version()+1;
//     }else
//     {
//         $hierarchy_group_id = Hierarchy::next_group_id();
//         $hierarchy_group_version = 1;
//     }
//     
//     $hierarchy_params = array(  "label"                     => "CU*STAR Classification",
//                                 "description"               => "CU*STAR Classification",
//                                 "agent_id"                  => $agent_id,
//                                 "hierarchy_group_id"        => $hierarchy_group_id,
//                                 "hierarchy_group_version"   => $hierarchy_group_version);
//     
//     $GLOBALS['hierarchy'] = new Hierarchy(Hierarchy::insert(Functions::mock_object("Hierarchy", $hierarchy_params)));
// }
// 
// function start_process()
// {
//     global $mysqli;
//     
//     foreach(@$GLOBALS['children'][0] as $id)
//     {
//         //echo "add_taxon($id, 0, '', 0);<br>\n";
//         add_taxon($id, 0, '', 0);
//     }
// }
// 
// function add_taxon($taxon_id, $parent_hierarchy_entry_id, $ancestry, $depth)
// {
//     global $mysqli;
//     
//     static $counter = 0;
//     if($counter % 5000 == 0) echo "counter: $counter; memory: ".memory_get_usage()."; time: ".Functions::time_elapsed()."\n";
//     $counter++;
//     
//     //if($depth==4) $mysqli->commit();
//     
//     if(@!$GLOBALS['names'][$taxon_id]) return false;
//     
//     $params = array("identifier"    => $taxon_id,
//                     "name_id"       => $GLOBALS['names'][$taxon_id],
//                     "parent_id"     => $parent_hierarchy_entry_id,
//                     "hierarchy_id"  => $GLOBALS['hierarchy']->id,
//                     "rank_id"       => $GLOBALS['ranks'][$taxon_id],
//                     "ancestry"      => $ancestry);
//     
//     $hierarchy_entry = new HierarchyEntry(HierarchyEntry::insert($params));
//     unset($params);
//     
//     if($ancestry) $ancestry .= "|" . $GLOBALS['names'][$taxon_id];
//     else $ancestry = $GLOBALS['names'][$taxon_id];
//     
//     if(@$GLOBALS['children'][$taxon_id])
//     {
//         foreach($GLOBALS['children'][$taxon_id] as $child_id)
//         {
//             add_taxon($child_id, $hierarchy_entry->id, $ancestry, $depth+1);
//         }
//     }
// }


?>