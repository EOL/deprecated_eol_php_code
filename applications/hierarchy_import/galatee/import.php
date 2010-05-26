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
// $GLOBALS['next_identifier'] = 1;
// 
// parse_file();
// add_hierarchy();
// start_process();
// 
// // echo "<pre>";
// // print_r($GLOBALS['children']);
// // echo "</pre>";
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
//     $FILE = fopen(dirname(__FILE__) . "/../downloads/galatee.txt", "r");
//     if($FILE)
//     {
//         while(!feof($FILE))
//         {
//             if($line_number % 5000 == 0) echo "$line_number\n";
//             $line_number++;
//             
//             $line = fgets($FILE, 4096);
//             $line = rtrim($line, "\n\r");
//             
//             if($line_number == 1) continue;
//             
//             $parts = explode("\t", $line);
//             $source = @trim($parts[0]);
//             $identifier = @trim($parts[1]);
//             $galatee_name = @trim($parts[2]);
//             $scientific_name = @trim($parts[3]);
//             $french_name = @trim($parts[4]);
//             $english_name = @trim($parts[5]);
//             $author = @trim($parts[6]);
//             
//             $row_names = array();
//             
//             $row_names[] = array('name' => trim($parts[7]), 'rank' => 'kingdom');
//             $row_names[] = array('name' => trim($parts[8]), 'rank' => 'phylum');
//             $row_names[] = array('name' => trim($parts[9]), 'rank' => 'class');
//             $row_names[] = array('name' => trim($parts[10]), 'rank' => 'order');
//             $row_names[] = array('name' => trim($parts[11]), 'rank' => 'family');
//             $row_names[] = array('name' => trim($parts[12]), 'rank' => 'genus');
//             $row_names[] = array('name' => trim($parts[14]), 'rank' => 'species');
//             
//             $last_identifier = 0;
//             foreach($row_names as $key => $arr)
//             {
//                 if(@!$row_names[$key+1]['name'])
//                 {
//                     $arr['name'] = $scientific_name." ".$author;
//                     $arr['common_names']['english'][] = $english_name;
//                     $arr['common_names']['french_name'][] = $french_name;
//                 }
//                 
//                 $name_id = Name::insert($arr['name']);
//                 $rank_id = Rank::insert($arr['rank']);
//                 if(!$name_id) break;
//                 
//                 if(@$GLOBALS['identifiers'][$name_id]) $identifier = $GLOBALS['identifiers'][$name_id];
//                 else
//                 {
//                     $identifier = $GLOBALS['identifiers'][$name_id] = $GLOBALS['next_identifier'];
//                     $GLOBALS['next_identifier']++;
//                     
//                     $GLOBALS['children'][$last_identifier][] = $identifier;
//                     $GLOBALS['names'][$identifier] = $name_id;
//                     $GLOBALS['ranks'][$identifier] = $rank_id;
//                     
//                     if(@$arr['common_names'])
//                     {
//                         foreach($arr['common_names'] as $lang => $cnames)
//                         {
//                             $language_id = Language::insert($lang);
//                             foreach($cnames as $cname)
//                             {
//                                 $common_name_id = Name::insert($cname);
//                                 $GLOBALS['common_names'][$identifier][$language_id][] = $common_name_id;
//                             }
//                         }
//                     }
//                 }
//                 
//                 $last_identifier = $identifier;
//             }
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
//     $agent_params = array(      "full_name"     => "Galatee",
//                                 "acronym"       => "Galatee");
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
//     $hierarchy_params = array(  "label"                     => "Galatee",
//                                 "description"               => "Galatee",
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
//     $params = array("name_id"       => $GLOBALS['names'][$taxon_id],
//                     "parent_id"     => $parent_hierarchy_entry_id,
//                     "hierarchy_id"  => $GLOBALS['hierarchy']->id,
//                     "rank_id"       => $GLOBALS['ranks'][$taxon_id],
//                     "ancestry"      => $ancestry);
//     
//     $hierarchy_entry = new HierarchyEntry(HierarchyEntry::insert($params));
//     unset($params);
//     
//     if(@$GLOBALS['common_names'][$taxon_id])
//     {
//         foreach($GLOBALS['common_names'][$taxon_id] as $language_id => $cnames)
//         {
//             foreach($cnames as $name_id)
//             {
//                 $hierarchy_entry->add_synonym($name_id, SynonymRelation::insert('common name'), $language_id, 1);
//             }
//         }
//     }
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