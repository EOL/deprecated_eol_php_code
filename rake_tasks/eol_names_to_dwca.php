<?php
namespace php_active_record;
/*
This script will generate a DWCA of all names in EOL.
execution time: 2.62 hours
*/

//$GLOBALS['ENV_NAME'] = 'v2staging';

require_once(dirname(__FILE__) ."/../config/environment.php");
$GLOBALS['mysqli'] =& $GLOBALS['mysqli_connection'];

set_time_limit(0);
$timestart = time_elapsed();

$path = DOC_ROOT . "/temp/EOL_names_in_DWCA/";
if(!file_exists($path))
{
    if(!mkdir($path, 0777)) exit("<hr>Permission to create directory denied.");
}

$min_taxon_concept_id = 0;
$max_taxon_concept_id = 0;
$result = $GLOBALS['mysqli']->query("SELECT MIN(tc.id) min, MAX(tc.id) max FROM taxon_concepts tc WHERE tc.published = 1 AND tc.supercedure_id = 0");
if($result && $row=$result->fetch_assoc())
{
    $min_taxon_concept_id = $row['min'] + 0;
    $max_taxon_concept_id = $row['max'];
}

// $min_taxon_concept_id = 114502; //debug
// $max_taxon_concept_id = 27514453; //debug

print "\n min tc_id: " . $min_taxon_concept_id;
print "\n max tc_id: " . $max_taxon_concept_id;

initialize_text_file($path . "taxa.txt");
initialize_text_file($path . "vernacularnames.txt");

$ranks = get_ranks_list();
$relations = get_relations_list();
//$GLOBALS['test_taxon_concept_ids'] = array(206692,1,218294,7921,218284,328450,213726,5503); //debug

$batch_size = 100;
for($i = $min_taxon_concept_id; $i <= $max_taxon_concept_id; $i += $batch_size)
{
    $min = $i;
    $max = $i + $batch_size;
    if($min > 1) $min++; //comment to debug
    print "\n processing from $min to $max";

    $hierarchy_entry_ids = array();

    $synonyms = array();
    $synonyms = get_synonyms($min, $max, $relations, $ranks);
    $hierarchy_entry_ids = array_merge($hierarchy_entry_ids, get_array_of_specified_field($synonyms, 'hierarchy_entry_id'));

    $vernaculars = array();
    $vernaculars = get_vernaculars($min, $max);
    $hierarchy_entry_ids = array_merge($hierarchy_entry_ids, get_array_of_specified_field($vernaculars, 'hierarchy_entry_id'));

    $taxa = array();
    $taxa = get_taxa($min, $max, $ranks, $hierarchy_entry_ids);

    write_to_text_file($taxa, $path . "taxa.txt", $synonyms, $vernaculars);
    unset($taxa);
    unset($synonyms);
    write_to_text_file_vernacular($vernaculars, $path . "vernacularnames.txt");

    if(isset($GLOBALS['test_taxon_concept_ids'])) break;
    //if($i >= 1000) break; //debug
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds   \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours   \n";
exit("\n\n Done processing.");

function get_taxa($min, $max, $ranks, $hierarchy_entry_ids)
{
    $sql = "SELECT DISTINCT n.string sciname, tc.id taxon_concept_id, he.id hierarchy_entry_id, he.hierarchy_id, r.rank_id
            FROM taxon_concepts tc 
            JOIN hierarchy_entries he on he.taxon_concept_id = tc.id JOIN names n on he.name_id = n.id
            LEFT JOIN translated_ranks r ON he.rank_id = r.rank_id
            WHERE tc.published=1 AND tc.supercedure_id=0 
            AND he.vetted_id=" . Vetted::trusted()->id . " AND he.published=1 AND he.visibility_id=" . Visibility::visible()->id;

    if(isset($GLOBALS['test_taxon_concept_ids'])) $sql.=" and tc.id in (" . implode(",", $GLOBALS['test_taxon_concept_ids']).")";
    else $sql .= " AND tc.id BETWEEN $min AND $max";
    $sql .= "  ORDER BY tc.id, n.string, he.id";
    $result = $GLOBALS['mysqli']->query($sql);
    print " t:" . $result->num_rows;
    $names = array();
    $temp = array();
    while($result && $row=$result->fetch_assoc())
    {
        $add_row = false;
        if (!in_array(trim($row['sciname']), $temp)) $add_row = true;
        else
        {
            if (in_array($row['hierarchy_entry_id'], $hierarchy_entry_ids)) $add_row = true;
            else $add_row = false;
        }            
        $temp[] = trim($row['sciname']);
        if($add_row)
        {
            $names[] = array("scientificName" => $row['sciname'],
            "taxon_concept_id" => $row['taxon_concept_id'],
            "hierarchy_entry_id" => $row['hierarchy_entry_id'],
            "rank" => @$ranks[$row['rank_id']]);
        }
    }
    unset($temp);
    return $names;
}

function get_synonyms($min, $max, $relations, $ranks)
{
    $sql = "SELECT DISTINCT he.taxon_concept_id, n.string synonym, t.synonym_relation_id, s.id synonym_id, s.hierarchy_entry_id, r.rank_id
            FROM hierarchy_entries he 
            JOIN synonyms s ON he.id = s.hierarchy_entry_id 
            JOIN names n ON s.name_id = n.id
            LEFT JOIN translated_synonym_relations t ON s.synonym_relation_id = t.synonym_relation_id
            JOIN hierarchies h ON s.hierarchy_id = h.id 
            LEFT JOIN translated_ranks r ON he.rank_id = r.rank_id
            WHERE s.synonym_relation_id NOT IN (" . SynonymRelation::find_by_translated('label', "common name")->id . "," . SynonymRelation::find_by_translated('label', "genbank common name")->id . ")
            AND h.browsable=1 AND s.published=1 
            AND he.vetted_id=" . Vetted::trusted()->id . " AND he.published=1 AND he.visibility_id=" . Visibility::visible()->id;

    if(isset($GLOBALS['test_taxon_concept_ids'])) $sql.=" and he.taxon_concept_id in (" . implode(",", $GLOBALS['test_taxon_concept_ids']).")";
    else $sql .= " AND he.taxon_concept_id BETWEEN $min AND $max";
    $sql .= " ORDER BY he.taxon_concept_id, n.string";
    $result = $GLOBALS['mysqli']->query($sql);
    print " s:" . $result->num_rows;
    $names = array();
    $temp = array();
    while($result && $row=$result->fetch_assoc())
    {
        // 20622603 1   Animalia    http://eol.org/pages/1  kingdom     
        // 36474369 1   Animalia    http://eol.org/pages/1  kingdom     
        // 30083080 1   Animalia Linnaeus, 1758 http://eol.org/pages/1          
        // 28991246 1   Metazoa http://eol.org/pages/1  kingdom     
        // s_5163509    1   Animalia    http://eol.org/pages/1/names/synonyms   kingdom 28991246    synonym
        // s_5163510    1   animals http://eol.org/pages/1/names/synonyms   kingdom 28991246    blast name
        
        if (!in_array($row['synonym'], $temp)) $names[$row['hierarchy_entry_id']][] = array("hierarchy_entry_id" => $row['hierarchy_entry_id'],
                                                                "taxon_concept_id" => $row['taxon_concept_id'],
                                                                "synonym_id" => "s_" . $row['synonym_id'],
                                                                "synonym" => $row['synonym'],
                                                                "relation" => @$relations[$row['synonym_relation_id']],
                                                                "rank" => @$ranks[$row['rank_id']]);
        $temp[] = $row['synonym'];
    }
    unset($temp);
    return $names;
}

function get_vernaculars($min, $max)
{
    $sql = "SELECT DISTINCT s.hierarchy_entry_id, he.taxon_concept_id, n.string vernacular, l.iso_639_1 language
            FROM hierarchy_entries he 
            JOIN synonyms s ON he.id = s.hierarchy_entry_id 
            JOIN names n ON s.name_id = n.id
            LEFT JOIN languages l ON s.language_id = l.id
            WHERE s.synonym_relation_id IN (" . SynonymRelation::find_by_translated('label', "common name")->id . "," . SynonymRelation::find_by_translated('label', "genbank common name")->id . ")
            AND he.vetted_id=" . Vetted::trusted()->id . " AND he.published=1 AND he.visibility_id=" . Visibility::visible()->id;

    if(isset($GLOBALS['test_taxon_concept_ids'])) $sql.=" and he.taxon_concept_id in (" . implode(",", $GLOBALS['test_taxon_concept_ids']).")";
    else $sql .= " AND he.taxon_concept_id BETWEEN $min AND $max";
    $sql .= " ORDER BY he.taxon_concept_id, n.string ASC, s.language_id DESC";
    $result = $GLOBALS['mysqli']->query($sql);
    print " v:" . $result->num_rows;
    $names = array();
    $temp = array();
    while($result && $row=$result->fetch_assoc())
    {
        if (!in_array($row['vernacular'], $temp)) $names[$row['hierarchy_entry_id']][] = array("hierarchy_entry_id" => $row['hierarchy_entry_id'],
                                                                   "taxon_concept_id" => $row['taxon_concept_id'],
                                                                   "vernacular" => $row['vernacular'],
                                                                   "lang" => $row['language']);
        $temp[] = $row['vernacular'];
    }
    unset($temp);
    return $names;
}

function write_to_text_file($records, $filename, $synonyms, $vernaculars)
{
    $line = '';
    if(!($OUT = fopen($filename, "a")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$filename);
      return;
    }
    $temp = array();
    foreach($records as $record)
    {
        if(@$record["taxon_concept_id"])
        {
            $source = "http://eol.org/pages/" . $record['taxon_concept_id'] . "/entries/" . $record['hierarchy_entry_id'] . "/overview";
            $source = "http://eol.org/pages/" . $record['taxon_concept_id'];
            $line .= $record['hierarchy_entry_id'] . "\t" .
                    $record['taxon_concept_id'] . "\t" .
                    $record['scientificName'] . "\t" .
                    $source . "\t" .
                    $record['rank'] . "\t" .
                    "" . "\t" .
                    "" . "\n";

            if(@$synonyms[$record['hierarchy_entry_id']])
            {
                foreach(@$synonyms[$record['hierarchy_entry_id']] as $synonym)
                {
                    $source = "http://eol.org/pages/" . $synonym['taxon_concept_id'] . "/names/synonyms";
                    $line .= $synonym['synonym_id'] . "\t" .
                             $synonym['taxon_concept_id'] . "\t" .
                             $synonym['synonym'] . "\t" .
                             $source . "\t" .
                             $synonym['rank'] . "\t" .
                             $synonym['hierarchy_entry_id'] . "\t" .
                             $synonym['relation'] . "\n";
                }
            }
        }
    }
    fwrite($OUT, $line);
    fclose($OUT);
}

function write_to_text_file_vernacular($records, $filename)
{
    $line = '';
    if(!($OUT = fopen($filename, "a")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$filename);
      return;
    }
    foreach($records as $recordz)
    {
        foreach($recordz as $record)
        {
            if(@$record["taxon_concept_id"])
            {
                $line .= $record['hierarchy_entry_id'] . "\t" . $record['taxon_concept_id'] . "\t" . $record['vernacular'] . "\t" . $record['lang'] . "\n";
            }
        }
    }
    fwrite($OUT, $line);
    fclose($OUT);
}

function initialize_text_file($filename)
{
    if(!($f = fopen($filename, "w")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
      return;
    }
    fclose($f);
} 

function get_ranks_list()
{
    $sql = "SELECT r.label, r.rank_id FROM translated_ranks r WHERE r.language_id=152";
    $result = $GLOBALS['mysqli']->query($sql);
    $ranks = array();
    while($result && $row=$result->fetch_assoc()) $ranks[$row['rank_id']] = $row['label'];
    return $ranks;
}

function get_relations_list()
{
    $sql = "SELECT r.label, r.synonym_relation_id FROM translated_synonym_relations r WHERE r.language_id=152";
    $result = $GLOBALS['mysqli']->query($sql);
    $relations = array();
    while($result && $row=$result->fetch_assoc()) $relations[$row['synonym_relation_id']] = $row['label'];
    return $relations;
}

function get_array_of_specified_field($records, $field)
{
    $compiled = array();
    foreach($records as $id => $record) $compiled[$id] = '';
    return array_keys($compiled);
}

?>