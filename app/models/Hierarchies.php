<?php
namespace php_active_record;

class Hierarchy extends ActiveRecord
{
    public static function delete($id)
    {
        if(!$id) return false;
        $hierarchy = Hierarchy::find($id);
        if(!$hierarchy->id) return false;

        $mysqli =& $GLOBALS['mysqli_connection'];
        $mysqli->begin_transaction();

        $mysqli->delete("DELETE ahe FROM hierarchy_entries he JOIN agents_hierarchy_entries ahe ON (he.id=ahe.hierarchy_entry_id) WHERE he.hierarchy_id=$id");
        $mysqli->delete("DELETE her FROM hierarchy_entries he JOIN hierarchy_entries_refs her ON (he.id=her.hierarchy_entry_id) WHERE he.hierarchy_id=$id");
        $mysqli->delete("DELETE s FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) WHERE he.hierarchy_id=$id AND s.hierarchy_id=$id");
        $mysqli->delete("DELETE he FROM hierarchy_entries he WHERE he.hierarchy_id=$id");
        $mysqli->delete("DELETE FROM hierarchies WHERE id=$id");

        $mysqli->end_transaction();
    }

    public function latest_group_version()
    {
        $result = $this->mysqli->query("SELECT max(hierarchy_group_version) as max FROM hierarchies WHERE hierarchy_group_id=$this->hierarchy_group_id");
        if($result && $row=$result->fetch_assoc()) return $row["max"];

        return 0;
    }

    public function count_entries()
    {
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM hierarchy_entries WHERE hierarchy_id=$this->id");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
        return 0;
    }

    static function find_last_by_agent_id($agent_id)
    {
        return Hierarchy::find_by_agent_id($agent_id, array('order' => 'id DESC', 'limit' => 1));
    }

    static function default_id()
    {
        if(defined('DEFAULT_HIERARCHY_LABEL') && $h = self::find_by_label(DEFAULT_HIERARCHY_LABEL)) return $h->id;
        else return null;
    }

    static function contributors()
    {
        return Hierarchy::find_or_create_by_label('Encyclopedia of Life Contributors');
    }

    static function ubio()
    {
        return Hierarchy::find_by_label('uBio Namebank');
    }

    static function wikipedia()
    {
        return Hierarchy::find_by_label('Wikipedia');
    }

    public static function fix_published_flags_for_taxon_concepts()
    {
        // publish all the concepts that are unpublished but have published entries
        $GLOBALS['db_connection']->update_where("taxon_concepts", "id", "SELECT tc.id FROM hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) WHERE he.published=1 AND he.visibility_id=".Visibility::visible()->id." AND tc.published=0", "published=1");

        // unpublish concepts with no published entries
        $GLOBALS['db_connection']->update_where("taxon_concepts", "id", "SELECT tc.id FROM taxon_concepts tc LEFT JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id AND he.published=1) WHERE tc.published=1 AND he.id IS NULL", "published=0");

        // unpublish concepts that have been superceded
        $GLOBALS['db_connection']->update_where("taxon_concepts", "id", "SELECT id FROM taxon_concepts tc WHERE supercedure_id!=0 AND published=1", "published=0");
    }

    public static function fix_improperly_trusted_concepts()
    {
        // trust concepts that have visible trusted entries
        $GLOBALS['db_connection']->update_where("taxon_concepts", "id", "SELECT tc.id FROM taxon_concepts tc JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) WHERE tc.vetted_id!=".Vetted::trusted()->id." AND he.visibility_id=".Visibility::visible()->id." AND he.vetted_id=".Vetted::trusted()->id, "vetted_id=".Vetted::trusted()->id);

        // untrust concepts that have no visible trusted entries
        $GLOBALS['db_connection']->update_where("taxon_concepts", "id", "SELECT tc.id FROM taxon_concepts tc LEFT JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id AND he.visibility_id=".Visibility::visible()->id." AND he.vetted_id=".Vetted::trusted()->id.") WHERE tc.published=1 AND tc.vetted_id=".Vetted::trusted()->id." AND tc.supercedure_id=0 AND he.id IS NULL", "vetted_id=".Vetted::unknown()->id);
    }

    static function col_2009()
    {
        return Hierarchy::find_by_label('Species 2000 & ITIS Catalogue of Life: Annual Checklist 2009');
    }

    static function colcn_2009()
    {
      return Hierarchy::find_by_label('Catalogue of Life China');
    }

    static function gbif()
    {
      return Hierarchy::find_by_label('GBIF Nub Taxonomy');
    }

    static function next_group_id()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];

        $result = $mysqli->query("SELECT max(hierarchy_group_id) as max FROM hierarchies");
        if($result && $row=$result->fetch_assoc()) return $row["max"]+1;

        return 1;
    }
}

?>
