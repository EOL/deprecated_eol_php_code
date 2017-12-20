<?php
namespace php_active_record;

class EOLv2MetadataAPI
{
    // private $mysqli;
    // private $name_parser_client;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        // $mysqli       =& $GLOBALS['mysqli_connection'];
        // IF(cp.description_of_data IS NOT NULL, cp.description_of_data, r.description) as desc_of_data
    }

    public function start()
    {
        $sql = "SELECT cp.id, cp.full_name, r.title, he.published_at
                FROM content_partners cp
                JOIN resources r ON (cp.id=r.content_partner_id)
                JOIN harvest_events he ON (r.id=he.resource_id)
                WHERE r.id = 42
                ORDER BY cp.id";
                
        $sql = "SELECT cp.id as partner_id, cp.full_name as partner_name, s.label as status, r.id as resource_id, r.title as resource_title, s2.label as resource_status,
            cp.description as overview, cp.homepage as url, 
            cpa.mou_url as agreement_url, cpa.signed_on_date as signed_date, cpa.signed_by, cpa.created_at as create_date,
            cp.description_of_data as desc_of_data, cp.user_id as manager_eol_id
            FROM content_partners cp
            JOIN translated_content_partner_statuses s ON (cp.content_partner_status_id=s.id)
            JOIN resources r ON (cp.id=r.content_partner_id)
            JOIN translated_resource_statuses s2 ON (r.resource_status_id=s2.id)
            
            JOIN content_partner_agreements cpa ON (cp.id=cpa.content_partner_id)
            WHERE s.language_id = 152 AND s2.language_id = 152
            ORDER BY cp.id limit 6";

            // $result = $mysqli->query("SELECT r.hierarchy_id, max(he.id) as max FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) GROUP BY r.hierarchy_id");
            // $result = $mysqli->query("SELECT r.hierarchy_id, max(he.id) as max FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) GROUP BY r.hierarchy_id");
            
            $result = $this->mysqli->query($sql);
            $recs = array();
            while($result && $row=$result->fetch_assoc()) {
                $first_pub = $this->mysqli->select_value("SELECT min(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row['resource_id']);
                $last_pub = $this->mysqli->select_value("SELECT max(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row['resource_id']);

                $recs[$row['partner_id']] = array('partner_name' => $row['partner_name'], 'partner_id' => $row['partner_id'], 'status' => $row['status'],
                'overview' => $row['overview'], 'url' => $row['url'], 'agreement_url_from_db' => $row['agreement_url'], 'agreement_url' => self::fix_agreement_url($row['agreement_url']), 
                'signed_by' => $row['signed_by'], 'signed_date' => $row['signed_date'], 'create_date' => $row['create_date'], 'desc_of_data' => $row['desc_of_data'],
                'manager_eol_id' => $row['manager_eol_id']
                );
                $recs[$row['partner_id']]['resources'][] = array('resource_title' => $row['resource_title'], 'first_pub' => $first_pub, 'last_pub' => $last_pub, 'status' => $row['resource_status']);
                
                $sql = "SELECT cpc.id as eol_contact_id, cpc.given_name, cpc.family_name, cpc.email, cpc.homepage, cpc.telephone, cpc.address, s.label as contact_role
                FROM content_partner_contacts cpc JOIN translated_contact_roles s ON (cpc.contact_role_id=s.id) 
                WHERE cpc.content_partner_id = ".$row['partner_id']." AND s.language_id = 152";
                $contacts = $this->mysqli->query($sql);
                while($contacts && $row2=$contacts->fetch_assoc()) {
                    $recs[$row['partner_id']]['contacts'][] = array('eol_contact_id' => $row2['eol_contact_id'], 'given_name' => $row2['given_name'], 'family_name' => $row2['family_name'], 'email' => $row2['email'],
                    'homepage' => $row2['homepage'], 'telephone' => $row2['telephone'], 'address' => $row2['address'], 'contact_role' => $row2['contact_role'],);
                }
                
                // $harvest_event = HarvestEvent::find($row['max']);
                // if(!$harvest_event->published_at) $GLOBALS['hierarchy_preview_harvest_event'][$row['hierarchy_id']] = $row['max'];
            }
            print_r($recs);
    }
    private function fix_agreement_url($url_from_db)
    {
        //                   /files/pdfs/mou/EOL_FishBase-mou.pdf
        // http://www.eol.org/files/pdfs/mou/EOL_FishBase-mou.pdf
        if(substr($url_from_db, 0, 16) == "/files/pdfs/mou/") return "http://www.eol.org".$url_from_db;
        $url_from_db = str_replace("content8.eol.org", "content.eol.org", $url_from_db);
        $url_from_db = str_replace("content4.eol.org", "content.eol.org", $url_from_db);
        $url_from_db = str_replace("content1.eol.org", "content.eol.org", $url_from_db);
        return $url_from_db;
    }
    
    /*
    public function begin()
    {
        $start = $this->mysqli->select_value("SELECT MIN(id) FROM names");
        $max_id = $this->mysqli->select_value("SELECT MAX(id) FROM names");
        $limit = 100000;
        
        $this->mysqli->begin_transaction();
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->check_st_john($i, $limit);
            // $this->generate_ranked_canonical_forms($i, $limit);
        }
        $this->mysqli->end_transaction();
    }
    
    public function check_st_john($start, $limit)
    {
        $query = "SELECT id, string FROM names WHERE string REGEXP BINARY 'st\\\\\.-[a-z]'
            AND id BETWEEN $start AND ". ($start+$limit-1);
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $id = $row[0];
            $string = $row[1];
            $canonical_form_string = Functions::canonical_form($string);
            if($canonical_form = CanonicalForm::find_or_create_by_string($canonical_form_string))
            {
                echo "UPDATE names SET canonical_form_id=$canonical_form->id WHERE id=$id\n";
                $this->mysqli->update("UPDATE names SET canonical_form_id=$canonical_form->id, ranked_canonical_form_id=$canonical_form->id WHERE id=$id");
            }
        }
        $this->mysqli->commit();
    }
    
    public function check($start, $limit)
    {
        echo "Looking up $start, $limit\n";
        $query = "SELECT n.id, canonical.string, ranked_canonical.string  FROM names n
            JOIN canonical_forms canonical ON (n.canonical_form_id=canonical.id)
            JOIN canonical_forms ranked_canonical ON (n.ranked_canonical_form_id=ranked_canonical.id)
            WHERE n.ranked_canonical_form_id != n.canonical_form_id
            AND n.id BETWEEN $start AND ". ($start+$limit-1);
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $name_id = $row[0];
            $canonical = trim($row[1]);
            $ranked_canonical = trim($row[2]);
            
            // ranked has "zz", normal does not. This is indicative of a particular kind of encoding problem
            if(strpos($ranked_canonical, "zz") !== false && strpos($canonical, "zz") === false)
            {
                // echo "UPDATE names SET ranked_canonical_form_id = canonical_form_id WHERE id = $id\n";
                $this->mysqli->update("UPDATE names SET ranked_canonical_form_id = canonical_form_id WHERE id = $name_id");
            }
        }
        $this->mysqli->commit();
    }
    
    public function generate_ranked_canonical_forms($start, $limit)
    {
        echo "Looking up $start, $limit\n";
        $query = "SELECT id, string FROM names
            WHERE id BETWEEN $start AND ". ($start+$limit-1)."
            AND (ranked_canonical_form_id IS NULL OR ranked_canonical_form_id=0)";
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $id = $row[0];
            $string = trim($row[1]);
            if(!$string || strlen($string) == 1) continue;

            // $canonical_form = trim($client->lookup_string($string));
            // if($count % 5000 == 0)
            // {
            //     echo "       > Parsed $count names ($id : $string : $canonical_form). Time: ". time_elapsed() ."\n";
            // }
            // $count++;
            // 
            // // report this problem
            // if(!$canonical_form) continue;
            // 
            // $canonical_form_id = CanonicalForm::find_or_create_by_string($canonical_form)->id;
            // $GLOBALS['db_connection']->query("UPDATE names SET ranked_canonical_form_id=$canonical_form_id WHERE id=$id");
        }
        $this->mysqli->commit();
    }
    */
}

?>
