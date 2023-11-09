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
https://www.flickr.com/photo.gne?id=50996846868
https://www.flickr.com/photo.gne?id=50993925303
https://www.flickr.com/photo.gne?id=50997663907

from API: http://farm66.static.flickr.com/65535/50997663907_9f6a0699c9.jpg
        : http://farm66.static.flickr.com/65535/50997663907_9f6a0699c9_b.jpg (bigger)
from web: https://live.staticflickr.com/65535/50997663907_9f6a0699c9_b.jpg (bigger)

from API: http://farm66.static.flickr.com/65535/50902281443_66d1299d8d.jpg
        : http://farm66.static.flickr.com/65535/50902281443_66d1299d8d_b.jpg (bigger)
from web: https://live.staticflickr.com/65535/50902281443_66d1299d8d_b.jpg (bigger)
          
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

How to build a Flickr photo url:
photo id="49966353906" secret="2a16a36b56" server="65535" farm="66" 
photo id="50902281443" secret="66d1299d8d" server="65535" farm="66"
$photo_url = "http://farm".$farm.".static.flickr.com/".$server."/".$photo_id."_".$secret.".jpg";
http://farm66.static.flickr.com/65535/49966353906_2a16a36b56.jpg
http://farm66.static.flickr.com/65535/50902281443_66d1299d8d.jpg
*/
class BOLD2iNaturalistAPI_csv
{
    function __construct()
    {
    }
    function process_KatieO_csv($filename)
    {
        $csv_file = $this->input['path'].$filename; //exit("\n[$csv_file]\n");
        $i = 0; $valid_records = 0;
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
                if(!self::valid_record($rec)) {
                    echo "\nNOT valid record.\n";
                    print_r($rec);
                    continue;
                }
                echo "\nA valid record at this point.\n"; $valid_records++;
                $OFields = array();
                foreach($this->observation_fields as $field) {
                    if($val = @$rec[$field]) $OFields[] = array('id' => $this->OField_ID[$field], 'value' => $val);
                }

                /* good debug
                print_r($rec);
                print_r($this->observation_fields);
                print_r($OFields); exit("\nelix\n");
                */
                
                // /* main assignment routine
                $rek = array();
                if(true) {
                    $rek['sciname'] = $rec['scientificName'];
                    $rek['rank'] = '';
                    debug("\nSearching for '$rek[sciname]' with rank '$rek[rank]'\n");
                    $rek['iNat_taxonID'] = $this->get_iNat_taxonID($rek);
                    $rek['iNat_desc'] = $rec['notes'];
                    $rek['coordinates'] = $this->get_coordinates($rec);
                    $rek['geoprivacy'] = @$rec['Geoprivacy'];
                    $rek['iNat_place_guess'] = $rec['locality'];
                    $rek['image_urls'] = self::get_arr_from_pipe_delimited_string($rec['relevantMedia']);
                    $rek['image_urls_ext'] = $rec['relevantMedia_ext'];

                    $rek['date_collected'] = self::format_inat_date($rec['date']);
                    $rek['OFields'] = $OFields;
                    
                    /* Ken-ichi's way for Flickr is not working
                    $rek['flickr_photo_IDs'] = self::get_arr_from_pipe_delimited_string(@$rec['FlickrID']);
                    */
                    $rek['flickr_photo_IDs'] = ""; //always blank
                    
                    if($val = @$rec['FlickrID']) {
                        $rek['image_urls'] = self::get_urls_using_flickr_ids($val);
                        $rec['relevantMedia'] = implode("|", $rek['image_urls']);
                    }
                    if(!@$rek['image_urls']) continue;

                    $count++;
                    $this->save_observation_and_images_2iNat($rek, $rec); //un-comment in real operation
                    // print_r($rek); exit("\n000\n");
                }
                // */
            }
        } //end while()
        echo "\nValid records: [$valid_records]\n";
    }

    function format_inat_date($str)
    {
        $date = str_replace('/', '-', $str);
        //step 1
        $parts = explode("-", $date);
        if(is_numeric(@$parts[0]) && is_numeric(@$parts[1]) && is_numeric($parts[2])) {
            return str_replace('-', '/', $date);
        }
        else {
            $new = date('m/d/Y', strtotime($date));
            if($new == "12/31/1969") return str_replace('-', '/', $str);
            else return $new;    
        }

        /* These are the only possible date formats: e.g. May 1, 2021:
            May 1, 2021
            01/05/2021
            01-05-2021
            01-May-21
            01/May/21
            01-May-2021
            01/May/2021
        */
    }
    
    private function get_urls_using_flickr_ids($pipe_delim_flickr_ids) //return pipe-delimited URLs
    {
        $flickr_ids = self::get_arr_from_pipe_delimited_string($pipe_delim_flickr_ids);
        $final = array();
        foreach($flickr_ids as $photo_id) {
            if($photo_url = self::get_flickr_photo_url($photo_id)) $final[$photo_url] = '';
        }
        return array_keys($final);
    }
    public function get_flickr_photo_url($photo_id)
    {
        if($info = self::flickr_photos_getInfo($photo_id)) {
            $url1 = "https://farm".$info['farm'].".static.flickr.com/".$info['server']."/".$photo_id."_".$info['secret']."_b.jpg"; //bigger image
            $url2 = "https://farm".$info['farm'].".static.flickr.com/".$info['server']."/".$photo_id."_".$info['secret'].".jpg"; //orig
            if(Functions::ping_v2($url1)) return $url1;
            if(Functions::ping_v2($url2)) return $url2;
            echo "\n----------\nFlickr ID: $photo_id\n";
            exit("\nERROR: Should not go here. Contact eagbayani@eol.org if you see this message.\n----------\n");
        }
        else echo "\nFlickr photo inaccessible [$photo_id]\n";
    }
    private function flickr_photos_getInfo($photo_id)
    {   /*
        flickr.photos.getInfo
        e.g. https://www.flickr.com/services/api/flickr.photos.getInfo.html
        https://api.flickr.com/services/rest/?&method=flickr.photos.getInfo&api_key=1142baaccbaf8c6cc46f8f4ce26a7135&photo_id=48861925538
        */
        $url = "https://api.flickr.com/services/rest/?&method=flickr.photos.getInfo&api_key=".FLICKR_API_KEY."&photo_id=$photo_id";
        if($xml = Functions::lookup_with_cache($url, $this->download_options)) {
            /*
            <photo id="2733" secret="123456" server="12" farm="66"
            */
            if(preg_match("/secret=\"(.*?)\"/ims", $xml, $arr)) $ret['secret'] = $arr[1];
            if(preg_match("/server=\"(.*?)\"/ims", $xml, $arr)) $ret['server'] = $arr[1];
            if(preg_match("/farm=\"(.*?)\"/ims", $xml, $arr)) $ret['farm'] = $arr[1];
            if(!@$ret['secret'] || !@$ret['server'] || !@$ret['farm']) return false;
            return $ret;
        }
    }
    private function valid_record($rec)
    {
        if(!@$rec['relevantMedia'] && !@$rec['FlickrID']) return false;
        if($val = @$rec['relevantMedia']) {
            if(pathinfo($val, PATHINFO_EXTENSION) == "" && !@$rec['relevantMedia_ext']) return false;
        }
        /* No FlickrID atm | comment to allow Flickr IDs | un-comment to disable FlickrID
        if(!@$rec['relevantMedia'] && @$rec['FlickrID']) return false;
        */
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
        $html = str_ireplace(array("﻿", "\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html)); //NOTICE weird 1st char
        $html = str_ireplace("> |", ">", $html);
        $html = Functions::conv_to_utf8($html);
        $arr = explode($delimeter, $html);
        $arr = array_map('trim', $arr);
        return $arr;
    }
}
?>