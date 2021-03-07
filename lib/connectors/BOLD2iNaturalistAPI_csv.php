<?php
namespace php_active_record;
/* class extends from BOLD2iNaturalistAPI - this is for Katie O. csv template upload to iNat
https://www.inaturalist.org/pages/api+reference#post-observations
- added provision for atomized data by using Observation Fields Attributes.
- added provision to use Flick photos.

The photo Flickr ID is 291793938 in these examples:
https://www.flickr.com/photos/samjudson/291793938/
https://www.flickr.com/photo.gne?id=291793938
https://www.flickr.com/photo.gne?id=49966353906
https://www.flickr.com/photo.gne?id=50902281443 (Katie's image)


How to get a direct image URL for a Flickr Photo
    Click a photo to open it.
    Click the Download icon.
    Click View all sizes.
    Click on the size you want to see.
    Right-click the image.
    Select Copy Image Location or Copy Image URL or Copy Image Address (text may vary per browser).
    -end-

e.g.
https://live.staticflickr.com/65535/50902281443_66d1299d8d_c_d.jpg
https://live.staticflickr.com/65535/50902281443_fe473ab2f3_k_d.jpg
*/
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
                $fields = self::fill_up_blank_fieldnames($fields); //print_r($fields);
                $count = count($fields);
            }
            else { //main records
                $values = $row;
                if(count($fields) != count($values)) { //row validation - correct no. of columns
                    print_r($values); print_r($fields);
                    echo "\n$count != ".count($values)."\n";
                    exit("\nWrong CSV format for this row.\n");
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = ($values[$k] != 'NA') ? $values[$k] : "";
                    $k++;
                }
                $rec = array_map('trim', $rec); //print_r($rec); //exit;
                /*Array( $rec
                    *[uniqueID] => 2019_USATXS_0001
                    *[museumID] => Smithsonian
                    [date] => 5/29/19
                    [scientificName] => Minuca rapax
                    [locality] => Oso Bay
                    [latitude] => 27.707445
                    [longitude] => -97.323996
                    *[CMECS_geoform] => NA
                    *[CMECS_substrate] => NA
                    *[habitat] => NA
                    *[microhabitat] => Brackish Marsh
                    *[depthRange] => NA
                    *[lifeStage] => adult
                    *[sex] => male
                    *[aliveOrDead] => NA
                    *[recordedBy] => NA
                    [relevantMedia] => https://photos.geome-db.org/44/Sample_Photo/GOM_BB_MarGEO_TXS_MinucaRapax_img_046_1024.4.jpg
                    [notes] => NA
                )*/
                if(!self::valid_record($rec)) continue;
                $OFields = array();
                foreach($this->observation_fields as $field) {
                    if($val = @$rec[$field]) $OFields[] = array('id' => $this->OField_ID[$field], 'value' => $val);
                }
                
                // /* main assignment routine
                $rek = array();
                if(true) {
                    $rek['sciname'] = $rec['scientificName'];
                    $rek['rank'] = '';
                    debug("\nSearching for '$rek[sciname]' with rank '$rek[rank]'\n");
                    $rek['iNat_taxonID'] = $this->get_iNat_taxonID($rek);
                    $rek['iNat_desc'] = $rec['notes'];
                    $rek['coordinates'] = $this->get_coordinates($rec);
                    $rek['iNat_place_guess'] = $rec['locality'];
                    $rek['image_urls'] = self::get_arr_from_pipe_delimited_string($rec['relevantMedia']);
                    $rek['date_collected'] = $rec['date'];
                    $rek['OFields'] = $OFields;
                    $rek['flickr_photo_IDs'] = self::get_arr_from_pipe_delimited_string(@$rec['FlickrID']);
                    $rek['image_urls_ext'] = $rec['relevantMedia_ext'];
                    $count++;
                    $this->save_observation_and_images_2iNat($rek, $rec); //un-comment in real operation
                    // print_r($rek); exit("\n000\n");
                }
                // */
            }
        }
    }
    private function valid_record($rec)
    {
        if(!@$rec['relevantMedia'] && !@$rec['FlickrID']) return false;
        if($val = @$rec['relevantMedia']) {
            if(pathinfo($val, PATHINFO_EXTENSION) == "" && !@$rec['relevantMedia_ext']) return false;
        }
        // /* No FlickrID atm | comment to allow Flickr IDs | un-comment to disable FlickrID
        if(!@$rec['relevantMedia'] && @$rec['FlickrID']) return false;
        // */
        return true;
    }
    private function get_arr_from_pipe_delimited_string($str)
    {
        if($str) {
            $arr = explode("|", $str);
            $arr = array_map('trim', $arr); // print_r($arr);
            return $arr;
        }
    }
    private function fill_up_blank_fieldnames($cols) //copied template
    {   $i = 0;
        foreach($cols as $col) {
            $i++;
            if(!$col) $final['col_'.$i] = '';
            else      $final[$col] = '';
        }
        return array_keys($final);
    }
    private function clean_html($arr) //copied template
    {   $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
    }
}
?>