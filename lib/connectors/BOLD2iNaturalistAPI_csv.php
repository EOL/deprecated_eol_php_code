<?php
namespace php_active_record;
/* class extends from BOLD2iNaturalistAPI - this is for Katie O. csv template upload to iNat */
class BOLD2iNaturalistAPI_csv
{
    function __construct()
    {
    }
    function process_KatieO_csv($filename)
    {
        $csv_file = $this->input['path'].$filename; //exit("\n[$csv_file]\n");
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            $row = self::clean_html($row); // print_r($row);
            $i++; if(($i % 2000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row; //print_r($fields); //exit("\nfields daw1\n");
                $fields = self::fill_up_blank_fieldnames($fields); print_r($fields);
                $count = count($fields);
            }
            else { //main records
                $values = $row;
                if(count($fields) != count($values)) { //row validation - correct no. of columns
                    print_r($values); print_r($rec); print_r($fields);
                    echo "\n$count != ".count($values)."\n";
                    exit("\nWrong CSV format for this row.\n");
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //print_r($rec); //exit;
                /*Array(
                    [uniqueID] => 2019_USATXS_0001
                    [museumID] => Smithsonian
                    [date] => 5/29/19
                    [scientificName] => Minuca rapax
                    [locality] => Oso Bay
                    [latitude] => 27.707445
                    [longitude] => -97.323996
                    [CMECS_geoform] => NA
                    [CMECS_substrate] => NA
                    [habitat] => NA
                    [microhabitat] => Brackish Marsh
                    [depthRange] => NA
                    [lifeStage] => adult
                    [sex] => male
                    [aliveOrDead] => NA
                    [recordedBy] => NA
                    [relevantMedia] => https://photos.geome-db.org/44/Sample_Photo/GOM_BB_MarGEO_TXS_MinucaRapax_img_046_1024.4.jpg
                    [notes] => NA

                    [uniqueID] => 
                    [museumID] => 
                    [CMECS_geoform] => 
                    [CMECS_substrate] => 
                    [habitat] => https://www.inaturalist.org/observation_fields/10
                    [microhabitat] => https://www.inaturalist.org/observation_fields/2882
                    [depthRange] => https://www.inaturalist.org/observation_fields/8906
                    [lifeStage] => https://www.inaturalist.org/observation_fields/4650
                    [sex] => https://www.inaturalist.org/observation_fields/5
                    [aliveOrDead] => https://www.inaturalist.org/observation_fields/10751
                    [recordedBy] => https://www.inaturalist.org/observation_fields/453


                )*/
                
                // /* main assignment routine
                $rek = array();
                if(true) {
                    $rek['sciname'] = $rec['scientificName'];
                    $rek['rank'] = '';
                    debug("\nSearching for '$rek[sciname]' with rank '$rek[rank]'\n");
                    $rek['iNat_taxonID'] = $this->get_iNat_taxonID($rek);
                    $rek['iNat_desc'] = ($rec['notes']!='NA') ? $rec['notes'] : "";
                    $rek['coordinates'] = $this->get_coordinates($rec);
                    $rek['iNat_place_guess'] = $rec['locality'];
                    $rek['image_urls'] = self::get_image_urls($rec);
                    $rek['date_collected'] = $rec['date'];
                    $count++;
                    // self::save_observation_and_images_2iNat($rek, $rec);
                    print_r($rek);
                }
                // */
                
            }
        }
    }
    // private function get_coordinates($rec)
    // {
    //     if($rec['lat'] && $rec['lon']) {
    //         return array('lat' => $rec['lat'], 'lon' => $rec['lon']);
    //     }
    // }
    
    private function fill_up_blank_fieldnames($cols)
    {
        $i = 0;
        foreach($cols as $col) {
            $i++;
            if(!$col) $final['col_'.$i] = '';
            else      $final[$col] = '';
        }
        return array_keys($final);
    }
    private function clean_html($arr)
    {
        $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
    }
}
?>