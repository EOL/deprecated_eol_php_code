<?php
// 
// include_once(dirname(__FILE__)."/../../../config/environment.php");
// $mysqli =& $GLOBALS['mysqli_connection'];
// 
// //$mysqli->truncate_tables("test");
// 
// 
// 
// 
// $mysqli->begin_transaction();
// 
// get_ranks();
// echo "Got ranks\n";
// 
// get_file_data();
// echo "Got file data\n";
// 
// add_hierarchy();
// echo "Done\n";
// 
// $mysqli->end_transaction();
// 
// 
// 
// 
// 
// function get_file_data()
// {
//     $GLOBALS['names'] = array();
//     $GLOBALS['parents'] = array();
//     $GLOBALS['name_ranks'] = array();
//     
//     $file = file("../downloads/gbif_taxonomy.txt");
//     $i = 0;
//     foreach($file as $key => $line)
//     {
//         if($i % 1000 == 0) echo "$i\n";
//         $i++;
//         $parts = explode("\t", trim($line));
//         $count = count($parts);
//         
//         //if($count != 4) echo "$i: $line<br>\n";
//         
//         $id = Functions::import_decode($parts[0], true, true);
//         $parent_id = Functions::import_decode($parts[1], true, true);
//         $name = Functions::import_decode($parts[2], true, true);
//         $rank_id = Functions::import_decode($parts[3], true, true);
//         if($parent_id == "\N") $parent_id = 0;
//         if(!$parent_id) $parent_id = 0;
//         
//         //echo "$name\n";
//         
//         //if(@!$ranks[$rank_id]) echo "$i: $line<br>\n";
//         
//         //if(preg_match("/[^A-Za-z0-9 \(\)\[\]\.\?\" ,-]/", $name)) echo "$i: $line<br>\n";
//         
//         $GLOBALS['names'][$id] = Name::insert($name);
//         $GLOBALS['children'][$parent_id][$id] = true;
//         $GLOBALS['name_ranks'][$id] = $rank_id;
//         
//         //if($i >= 500000) break;
//         
//         unset($parts);
//         unset($line);
//         unset($file[$key]);
//     }
//     
//     unset($file);
// }
// 
// function add_hierarchy()
// {
//     global $mysqli;
//     
//     $agent_params = array(  "full_name"     => "Global Biodiversity Information Facility (GBIF)",
//                             "acronym"       => "GBIF",
//                             "homepage"      => "http://www.gbif.org/");
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
//     $hierarchy_params = array(  "label"                     => "GBIF Nub Taxonomy",
//                                 "description"               => "latest export",
//                                 "agent_id"                  => $agent_id,
//                                 "hierarchy_group_id"        => $hierarchy_group_id,
//                                 "hierarchy_group_version"   => $hierarchy_group_version,
//                                 "url"                       => "http://data.gbif.org/");
//     
//     $GLOBALS['hierarchy'] = new Hierarchy(Hierarchy::insert(Functions::mock_object("Hierarchy", $hierarchy_params)));
//     
//     foreach($GLOBALS['children'][0] as $child_id => $value)
//     {
//         //echo "add_hierarchy_entry($child_id, 0, );\n";
//         add_hierarchy_entry($child_id, 0, "");
//         //$mysqli->commit();
//     }
// }
// 
// 
// function add_hierarchy_entry($id, $parent_hierarchy_entry_id, $ancestry)
// {
//     $name_id = @$GLOBALS['names'][$id];
//     if(!$name_id) return false;
//         
//     $params = array("identifier"    => $id,
//                     "name_id"       => $name_id,
//                     "parent_id"     => $parent_hierarchy_entry_id,
//                     "hierarchy_id"  => $GLOBALS['hierarchy']->id,
//                     "rank_id"       => $GLOBALS['ranks'][$GLOBALS['name_ranks'][$id]],
//                     "ancestry"      => $ancestry);
//     
//     $hierarchy_entry = new HierarchyEntry(HierarchyEntry::insert($params));
//     unset($params);
//     unset($mock_hierarchy_entry);
//     
//     if($ancestry) $ancestry .= "|".$name_id;
//     else $ancestry = $name_id;
//     
//     if(@$GLOBALS['children'][$id])
//     {
//         foreach($GLOBALS['children'][$id] as $child_id => $value)
//         {
//             add_hierarchy_entry($child_id, $hierarchy_entry->id, $ancestry);
//         }
//         unset($GLOBALS['children'][$id]);
//     }
// }
// 
// 
// 
// 
// 
// 
// 
// 
// function get_ranks()
// {
//     $ranks = array();
//     $ranks["800"] =  Rank::insert("superkingdom");
//     $ranks["1000"] = Rank::insert("kingdom");
//     $ranks["1200"] = Rank::insert("subkingdom");
//     $ranks["1800"] = Rank::insert("superphylum");
//     $ranks["2000"] = Rank::insert("phylum");
//     $ranks["2200"] = Rank::insert("subphylum");
//     $ranks["2800"] = Rank::insert("superclass");
//     $ranks["3000"] = Rank::insert("class");
//     $ranks["3200"] = Rank::insert("subclass");
//     $ranks["3350"] = Rank::insert("infraclass");
//     $ranks["3800"] = Rank::insert("superorder");
//     $ranks["4000"] = Rank::insert("order");
//     $ranks["4200"] = Rank::insert("suborder");
//     $ranks["4350"] = Rank::insert("infraorder");
//     $ranks["4400"] = Rank::insert("parvorder");
//     $ranks["4500"] = Rank::insert("superfamily");
//     $ranks["5000"] = Rank::insert("family");
//     $ranks["5500"] = Rank::insert("subfamily");
//     $ranks["5600"] = Rank::insert("tribe");
//     $ranks["5700"] = Rank::insert("subtribe");
//     $ranks["6000"] = Rank::insert("genus");
//     $ranks["6001"] = Rank::insert("nothogenus");
//     $ranks["6500"] = Rank::insert("subgenus");
//     $ranks["6600"] = Rank::insert("section");
//     $ranks["6700"] = Rank::insert("subsection");
//     $ranks["6800"] = Rank::insert("series");
//     $ranks["6900"] = Rank::insert("subseries");
//     $ranks["6950"] = Rank::insert("species group");
//     $ranks["6975"] = Rank::insert("species subgroup");
//     $ranks["7000"] = Rank::insert("species");
//     $ranks["7001"] = Rank::insert("nothospecies");
//     $ranks["8000"] = Rank::insert("subspecies");
//     $ranks["8001"] = Rank::insert("nothosubspecies");
//     $ranks["8010"] = Rank::insert("variety");
//     $ranks["8011"] = Rank::insert("nothovariety");
//     $ranks["8020"] = Rank::insert("form");
//     $ranks["8021"] = Rank::insert("nothoform");
//     $ranks["8030"] = Rank::insert("biovar");
//     $ranks["8040"] = Rank::insert("serovar");
//     $ranks["8050"] = Rank::insert("cultivar");
//     $ranks["8080"] = Rank::insert("pathovar");
//     $ranks["8090"] = Rank::insert("infraspecific");
//     $ranks["8100"] = Rank::insert("aberration");
//     $ranks["8110"] = Rank::insert("mutation");
//     $ranks["8120"] = Rank::insert("race");
//     $ranks["8130"] = Rank::insert("confersubspecies");
//     $ranks["8140"] = Rank::insert("formaspecialis");
//     $ranks["8150"] = Rank::insert("hybrid");
//     $ranks["0"] =    Rank::insert("unknown");
//     
//     $GLOBALS['ranks'] = $ranks;
// }

?>