<?php
namespace php_active_record;
/* called from /lib/connectors/DHSourceHierarchiesAPI_v3.php */
class GenericGetAncestryAPI
{
    function __construct()
    {
    }
    function get_ancestry_of_IDs_given_text_file($sought_IDs, $text_file)
    {
        /*step 1: get taxon_parent info list*/
        echo "\nGenerating info list [$text_file]\n";
        $i = 0;
        foreach(new FileIterator($text_file) as $line => $row) { $i++;
            if(!$row) continue;
            $rec = explode("\t|\t", $row);
            if($i == 1) {
                $fields = $rec;
                continue;
            }
            $rek = array(); $k = 0;
            foreach($fields as $field) {
                $rek[$field] = $rec[$k];
                $k++;
            }
            // print_r($rek); exit;
            /*Array(
                [uid] => Annelida
                [parent_uid] => 
                [name] => Annelida
                [rank] => phylum
                [sourceinfo] => 
                [] => 
            )*/
            $this->taxon_parent[$rek['uid']] = $rek['parent_uid'];
        }
        
        /*step 2: get ancestry of sought_IDs*/
        // $sought_IDs = array("Eurythoe-hedenborgi"); //debug only - forced value
        $final = array();
        foreach($sought_IDs as $id) {
            if($ancestry = self::get_ancestry_of($id)) {
                foreach($ancestry as $aydi) $final[$aydi] = '';
            }
        }
        $final = array_keys($final);
        // print_r($final); exit;
        return $final;
    }
    private function get_ancestry_of($id)
    {   //echo "\nancestry of [$id]\n";
        $ancestry = array();
        while(true) {
            if($parent = @$this->taxon_parent[$id]) {
                $ancestry[] = $parent;
                $id = $parent;
            }
            else break;
        }
        // print_r($ancestry); exit;
        return $ancestry;
    }
}
?>