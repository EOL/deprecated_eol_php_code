<?php
namespace php_active_record;

class LifeDeskAPI
{
    private $api_url;
    
    function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        $this->api_url = "http://". $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"];
    }
    
    function search($term, $hierarchy_id=null)
    {
        $term = trim($term);
        if(!$term) return array();
        
        $hierarchy_entries = array();
        $query = "SELECT DISTINCT he.id FROM canonical_forms c JOIN names n ON (c.id=n.canonical_form_id) JOIN hierarchy_entries he ON (n.id=he.name_id) JOIN hierarchies h ON (he.hierarchy_id=h.id) WHERE c.string='".$this->mysqli->real_escape_string($term)."' AND (h.browsable=1 OR h.label LIKE 'Species 2000 & ITIS Catalogue of Life%' OR h.label LIKE 'Integrated Taxonomic Information System%')";
        if($hierarchy_id) $query .= " AND he.hierarchy_id=$hierarchy_id";
        
        $result = $this->mysqli->query($query ." order by hierarchy_group_version desc, id asc");
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_entries[] = HierarchyEntry::find($row["id"]);
        }
        
        $results = array();
        
        foreach($hierarchy_entries as $k => $v)
        {
            $thisResult = array();
            $thisResult["name"] = $v->name->string;
            $thisResult["canonical_form"] = $v->name->canonical_form->string;
            $thisResult["id"] = $v->id;
            $thisResult["hierarchy_id"] = $v->hierarchy_id;
            $thisResult["ancestry"] = $v->ancestry_names();
            $thisResult["ranked_ancestry"] = $v->ranked_ancestry();
            $thisResult["rank"] = @$v->rank->translation->label;
            $thisResult["number_of_children"] = $v->number_of_children();
            $thisResult["number_of_children_synonyms"] = $v->number_of_children_synonyms();
            
            $hierarchyInfo = array();
            $hierarchyInfo["title"] = $v->hierarchy->label;
            $hierarchyInfo["description"] = $v->hierarchy->description;
            $hierarchyInfo["url"] = $v->hierarchy->url;
            $hierarchyInfo["indexed_on"] = $v->hierarchy->indexed_on;
            
            $thisResult["metadata"] = $hierarchyInfo;
            
            $results[] = $thisResult;
        }
        
        return $results;
    }
    
    function details($id,$ancestry)
    {
        $results = array();
        
        $entry = HierarchyEntry::find($id);
        $details = $this->get_details($entry);
        
        $currentNode = $entry;
        
        if($ancestry)
        {
            $parentInfo = array();
            while($parent = $currentNode->parent)
            {
                $info = array();
                $info["id"] = $parent->id;
                $info["hierarchy_id"] = $parent->hierarchy_id;
                $info["name"] = $parent->name->string;
                $info["canonical_form"] = $parent->name->canonical_form->string;
                $info["rank"] = $parent->rank->translation->label;
                $info["synonyms"] = array();
                
                $synonyms = $parent->synonyms();
                foreach($synonyms as $k => $v)
                {
                    $thisSynonym = array();
                    $thisSynonym["name"] = $v->name->string;
                    $thisSynonym["type"] = $v->synonym_relation->translation->label;
                    $thisSynonym["language_id"] = $v->language->translation->string;
                    
                    $info["synonyms"][] = $thisSynonym;
                }
                
                $info["children"][] = $details;
                
                $details = $info;
                
                $currentNode = $parent;
            }
        }
        
        
        
        $hierarchyInfo = array();
        $hierarchyInfo["title"] = $entry->hierarchy->label;
        $hierarchyInfo["description"] = $entry->hierarchy->description;
        $hierarchyInfo["url"] = $entry->hierarchy->url;
        $hierarchyInfo["indexed_on"] = $entry->hierarchy->indexed_on;
        
        $results["metadata"] = $hierarchyInfo;
        $results["hierarchy"] = $details;
        
        return $results;
    }
    
    function get_details($hierarchy_entry)
    {
        $details = array();
        $details["id"] = $hierarchy_entry->id;
        $details["hierarchy_id"] = $hierarchy_entry->hierarchy_id;
        $details["name"] = $hierarchy_entry->name->string;
        $details["canonical_form"] = $hierarchy_entry->name->canonical_form->string;
        $details["rank"] = $hierarchy_entry->rank->translation->label;
        $details["synonyms"] = array();
        
        $synonyms = $hierarchy_entry->synonyms;
        foreach($synonyms as $k => $v)
        {
            $thisSynonym = array();
            $thisSynonym["name"] = $v->name->string;
            $thisSynonym["type"] = $v->synonym_relation->translation->label;
            $thisSynonym["language_id"] = $v->language ? $v->language->translation->label : '';
            
            $details["synonyms"][] = $thisSynonym;
        }
        
        $details["children"] = array();
        
        $children = $hierarchy_entry->children();
        foreach($children as $k => $v)
        {
            $childDetails = $this->get_details($v);
            $details["children"][] = $childDetails;
        }
        
        return $details;
    }
    
    function details_tcs($id)
    {
        $entry = HierarchyEntry::find($id);
        if(@!$entry->id) return false;
        
        $nomenclaturalCode = "Zoological";
        $rankCode = "";
        switch(strtolower($entry->rank->translation->label))
        {
            case "kingdom":
                $rankCode = "reg";
                break;
            case "phylum":
                $rankCode = "phyl_div";
                break;
            case "class":
                $rankCode = "cl";
                break;
            case "order":
                $rankCode = "ord";
                break;
            case "family":
                $rankCode = "fam";
                break;
            case "genus":
                $rankCode = "gen";
                break;
            case "species":
                $rankCode = "sp";
                break;
        }
        
        $return = "";
        $return .= "  <TaxonNames>\n";
        $return .= "    <TaxonName id='n".$entry->name->id."' nomenclaturalCode='$nomenclaturalCode'>\n";
        $return .= "      <Simple>".htmlspecialchars($entry->name->string)."</Simple>\n";
        if($rankCode)
        {
            $return .= "      <Rank code='$rankCode'>".ucfirst(strtolower($entry->rank->translation->label))."</Rank>\n";
        }
        $return .= "      <CanonicalName>\n";
        $return .= "        <Simple>".htmlspecialchars($entry->name->canonical_form->string)."</Simple>\n";
        $return .= "      </CanonicalName>\n";
        if($ahe = $entry->agents_hierarchy_entries)
        {
            $return .= "      <ProviderSpecificData>\n";
            $return .= "        <NameSources>\n";
            foreach($ahe as $k => $v)
            {
                $return .= "          <NameSource>\n";
                $return .= "            <Simple>".htmlspecialchars($v->agent->full_name)."</Simple>\n";
                if($v->agent_role) $return .= "            <Role>".htmlspecialchars($v->agent_role->translation->label)."</Role>\n";
                $return .= "          </NameSource>\n";
            }
            $return .= "        </NameSources>\n";
            $return .= "      </ProviderSpecificData>\n";
        }
        $return .= "    </TaxonName>\n";
        $return .= "  </TaxonNames>\n";
        
        
        
        $return .= "  <TaxonConcepts>\n";
        $return .= "    <TaxonConcept id='c".$entry->id."'>\n";
        $return .= "      <Name scientific='true' ref='n".$entry->name->id."'>".htmlspecialchars($entry->name->string)."</Name>\n";
        if($rankCode)
        {
            $return .= "      <Rank code='$rankCode'>".ucfirst(strtolower($entry->rank->translation->label))."</Rank>\n";
        }
        $return .= "      <TaxonRelationships>\n";
        if($parent = $entry->parent())
        {
            $return .= "        <TaxonRelationship type='is child taxon of'>\n";
            $return .= "          <ToTaxonConcept ref='$this->api_url?function=details_tcs&amp;id=".$parent->id."' linkType='external'/>\n";
            $return .= "        </TaxonRelationship>\n";
        }
        if($children = $entry->children())
        {
            foreach($children as $k => $v)
            {
                $return .= "        <TaxonRelationship type='is parent taxon of'>\n";
                $return .= "          <ToTaxonConcept ref='$this->api_url?function=details_tcs&amp;id=".$v->id."' linkType='external'/>\n";
                $return .= "        </TaxonRelationship>\n";
            }
        }
        if($synonyms = $entry->synonyms())
        {
            foreach($synonyms as $k => $v)
            {
                $relationship = "has synonym";
                if(strtolower($v->synonym_relation->translation->label)=="common name") $relationship = "has vernacular";
                $return .= "        <TaxonRelationship type='$relationship'>\n";
                $return .= "          <ToTaxonConcept ref='$this->api_url?function=details_tcs&amp;sid=".$v->id."' linkType='external'/>\n";
                $return .= "        </TaxonRelationship>\n";
            }
        }
        $return .= "      </TaxonRelationships>\n";
        $return .= "    </TaxonConcept>\n";
        $return .= "  </TaxonConcepts>\n";
        
        return $return;
    }
    
    function details_tcs_synonym($id)
    {
        $entry = Synonym::find($id);
        
        $nomenclaturalCode = "Zoological";
        
        $scientific = "true";
        if($entry->synonym_relation && $entry->synonym_relation->translation && strtolower($entry->synonym_relation->translation->label)=="common name") $scientific = "false";
        
        $return = "";
        $return .= "  <TaxonNames>\n";
        $return .= "    <TaxonName id='n".$entry->name->id."' nomenclaturalCode='$nomenclaturalCode'>\n";
        $return .= "      <Simple>".htmlspecialchars($entry->name->string)."</Simple>\n";
        if($scientific=="true")
        {
            $return .= "      <CanonicalName>\n";
            $return .= "        <Simple>".htmlspecialchars($entry->name->canonical_form->string)."</Simple>\n";
            $return .= "      </CanonicalName>\n";
        }
        if($agents = $entry->agents)
        {
            $return .= "      <ProviderSpecificData>\n";
            $return .= "        <NameSources>\n";
            foreach($agents as $k => $v)
            {
                $return .= "          <NameSource>\n";
                $return .= "            <Simple>".htmlspecialchars($v->agent->display_name)."</Simple>\n";
                $return .= "            <Role>".htmlspecialchars($v->agent_role->translation->label)."</Role>\n";
                $return .= "          </NameSource>\n";
            }
            $return .= "        </NameSources>\n";
            $return .= "      </ProviderSpecificData>\n";
        }
        $return .= "    </TaxonName>\n";
        $return .= "  </TaxonNames>\n";
        
        
        
        $return .= "  <TaxonConcepts>\n";
        $return .= "    <TaxonConcept id='c".$entry->id."'>\n";
        $return .= "      <Name scientific='$scientific' ";
        if(@$entry->language->iso_639_1) $return .= "language='".$entry->language->iso_639_1."' ";
        $return .= "ref='n".$entry->name->id."'>".htmlspecialchars($entry->name->string)."</Name>\n";
        $return .= "    </TaxonConcept>\n";
        $return .= "  </TaxonConcepts>\n";
        
        return $return;
    }
}

?>
