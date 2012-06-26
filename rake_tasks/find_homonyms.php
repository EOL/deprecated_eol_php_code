<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");


$hf = new HomonymFinder();
$hf->begin();

// useful homonym list:
//   http://sn2000.taxonomy.nl/Main/Index/Homonyms/A1.htm


class HomonymFinder
{
    private $mysqli;
    private $solr;
    private $hierarchy_kingdoms;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->lookup_hierarchies();
    }
    
    public function begin()
    {
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries')) return false;
        $this->solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
        
        // $result = $this->mysqli->query("SELECT n.canonical_form_id, n.string, he.taxon_concept_id, count(*) count FROM names n JOIN hierarchy_entries he ON (n.id=he.name_id) WHERE he.hierarchy_id=".Hierarchy::default_id()." AND n.clean_name NOT REGEXP BINARY ' ' GROUP BY canonical_form_id HAVING count > 1 ORDER BY count DESC");
        $result = $this->mysqli->query("SELECT n.canonical_form_id, n.string, he.taxon_concept_id, count(distinct taxon_concept_id) count FROM names n JOIN hierarchy_entries he ON (n.id=he.name_id) JOIN hierarchies h ON (he.hierarchy_id=h.id) WHERE h.browsable=1 AND n.clean_name NOT REGEXP BINARY ' ' GROUP BY canonical_form_id HAVING count > 1 ORDER BY count DESC");
        $i = 0;
        while($result && $row=$result->fetch_assoc())
        {
            //if($i % 20 == 0) echo "$i\n";
            $i++;
            $this->evaluate_concept($row['taxon_concept_id'], $row['string']);
        }
        
        //$this->evaluate_concept(49216);
    }
    
    public function lookup_hierarchies()
    {
        $this->hierarchy_kingdoms = array();
        if($id = Hierarchy::find_by_label('Tropicos taxon details'))
        {
            $this->hierarchy_kingdoms[$id] = 'plantae';
        }
        if($id = Hierarchy::find_by_label('USDA Plants'))
        {
            $this->hierarchy_kingdoms[$id] = 'plantae';
        }
        if($id = Hierarchy::find_by_label('Nomenclator Zoologicus Record Detail'))
        {
            $this->hierarchy_kingdoms[$id] = 'animalia';
        }
        if($id = Hierarchy::find_by_label('GRIN taxon details'))
        {
            $this->hierarchy_kingdoms[$id] = 'plantae';
        }
        if($id = Hierarchy::find_by_label('eFloras'))
        {
            $this->hierarchy_kingdoms[$id] = 'plantae';
        }
    }
    
    
    public function evaluate_concept($taxon_concept_id, $name = '')
    {
        //echo "$taxon_concept_id\n";
        //$trusted_id = Vetted::insert('trusted');
        $trusted_id = 5;
        $results = $this->solr->get_results("taxon_concept_id:$taxon_concept_id&rows=500");
        $kingdoms_for_concept = array();
        foreach($results as $result)
        {
            //if(isset($result->vetted_id[0]) && $result->vetted_id[0] == $trusted_id)
            if(isset($result->hierarchy_id[0]) && $result->hierarchy_id[0] != 129)
            {
                if(isset($result->kingdom[0]))
                {
                    $kingdom = strtolower($result->kingdom[0]);
                    if($kingdom == "metazoa") $kingdom = "animalia";
                    if($kingdom == "viridiplantae") $kingdom = "plantae";
                    if($kingdom == "planta") $kingdom = "plantae";
                    if($kingdom == "protoctista") $kingdom = "protozoa";
                    if($kingdom == "chromista") $kingdom = "protozoa";
                    if($kingdom == "monera") $kingdom = "bacteria";
                    if($kingdom == "chromalveolata") $kingdom = "protozoa";
                    if($kingdom == "protist") $kingdom = "protozoa";
                    if($kingdom == "protista") $kingdom = "protozoa";
                    if($kingdom == "animslis") $kingdom = "animalia";
                    if($kingdom == "animalis") $kingdom = "animalia";
                    if($kingdom == "aninalia") $kingdom = "animalia";
                    if($kingdom == "eubacteria") $kingdom = "bacteria";
                    @$kingdoms_for_concept[$kingdom]++;
                }
                if($kingdom = @$this->hierarchy_kingdoms[$result->hierarchy_id[0]])
                {
                    @$kingdoms_for_concept[$kingdom]++;
                }
            }
        }
        
        if(count($kingdoms_for_concept) > 1)
        {
            echo "$name : $taxon_concept_id\n";
            print_r($kingdoms_for_concept);
        }
    }
    
}

?>