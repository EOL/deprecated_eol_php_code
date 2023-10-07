<?php
namespace php_active_record;
/* connector: 1st client: [gbif_download_request.php]
              2nd client: [gbif_download_request_for_NMNH.php]
              3rd client: the 6 GBIF country type records -> e.g. Germany, Sweden, etc.
              4th client: [gbif_download_request_for_iNat.php] 

THERE IS A CURL ISSUE: and the "--insecure" param as a sol'n actually works OK!
curl: (60) SSL certificate problem: certificate has expired
More details here: https://curl.haxx.se/docs/sslcerts.html

curl performs SSL certificate verification by default, using a "bundle"
of Certificate Authority (CA) public keys (CA certs). If the default
bundle file isn't adequate, you can specify an alternate file
using the --cacert option.
If this HTTPS server uses a certificate signed by a CA represented in
the bundle, the certificate verification probably failed due to a
problem with the certificate (it might be expired, or the name might
not match the domain name in the URL).
If you'd like to turn off curl's verification of the certificate, use
the -k (or --insecure) option.

                                               https://api.gbif.org/v1/occurrence/download/request/0389316-210914110416597.zip
curl --insecure -LsS -o 'NMNH_images_DwCA.zip' https://api.gbif.org/v1/occurrence/download/request/0389316-210914110416597.zip
*/
class GBIFdownloadRequestAPI
{
    function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        $this->gbif_username = 'eli_agbayani';
        $this->gbif_pw = 'ile173';
        $this->gbif_email = 'eagbayani@eol.org';
        
        // /* for resource_id equals 'GBIF_map_harvest'
        $this->taxon['Gadus ogac'] = 2415827;
        $this->taxon['Animalia'] = 1;
        $this->taxon['Plantae'] = 6;
        $this->taxon['Fungi'] = 5;
        $this->taxon['Chromista'] = 4;
        $this->taxon['Bacteria'] = 3;
        $this->taxon['Protozoa'] = 7;
        $this->taxon['incertae sedis'] = 0;
        $this->taxon['Archaea'] = 2;
        $this->taxon['Viruses'] = 8;
        // */
        
        if($this->resource_id == 'GBIF_map_harvest') $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF';
        elseif($this->resource_id == 'NMNH_images')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/NMNH_images';
        elseif($this->resource_id == 'iNat_images')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/iNat_images';
        elseif($this->resource_id == 'GBIF_Netherlands')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_Netherlands';
        elseif($this->resource_id == 'GBIF_France')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_France';
        elseif($this->resource_id == 'GBIF_Germany')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_Germany';
        elseif($this->resource_id == 'GBIF_Brazil')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_Brazil';
        elseif($this->resource_id == 'GBIF_Sweden')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_Sweden';
        elseif($this->resource_id == 'GBIF_UnitedKingdom')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_UnitedKingdom';
        else exit("\nresource_id not yet initialized\n");
        if(!is_dir($this->destination_path)) mkdir($this->destination_path);
        
        $this->abbreviation['GBIF_Netherlands'] = "NL";
        $this->abbreviation['GBIF_France'] = "FR";
        $this->abbreviation['GBIF_Germany'] = "DE";
        $this->abbreviation['GBIF_Brazil'] = "BR";
        $this->abbreviation['GBIF_Sweden'] = "SE";
        $this->abbreviation['GBIF_UnitedKingdom'] = "GB";   //United Kingdom of Great Britain and Northern Ireland
    }
    function send_download_request($taxon_group) //this will overwrite any current download request. Run this once ONLY every harvest per taxon group.
    {
        $json = self::generate_json_request($taxon_group);
        self::save_json_2file($json);
        $arr = json_decode($json, true); // print_r($arr);
        /* orig per https://www.gbif.org/developer/occurrence#download
        curl --include --user userName:PASSWORD --header "Content-Type: application/json" --data @query.json https://api.gbif.org/v1/occurrence/download/request
        */
        $filename = $this->destination_path.'/query.json';
        $cmd = 'curl --insecure --include --user '.$this->gbif_username.':'.$this->gbif_pw.' --header "Content-Type: application/json" --data @'.$filename.' -s https://api.gbif.org/v1/occurrence/download/request';
        echo "\ncmd:\n[$cmd]\n";
        $output = shell_exec($cmd);
        echo "\nRequest output:\n[$output]\n";
        $lines = explode("\n", trim($output));
        if($key = trim(array_pop($lines))) { //get last line
            echo "\nDownload Key:[$key]\n";
            self::save_key_per_taxon_group($taxon_group, $key);
            return $key;
        }
        exit("\nCannot generate download key. Investigate [$taxon_group].\n");
    }
    function generate_sh_file($taxon_group)
    {
        if($key = self::retrieve_key_for_taxon($taxon_group)) {
            echo "\nDownload key for [$taxon_group]: [$key]\n";
            if($arr = self::can_proceed_to_download_YN($key)) {
                echo "\nCan proceed to download [$taxon_group].\n";
                self::create_bash_file($taxon_group, $arr['downloadLink']);
                return true;
            }
            else echo "\nCannot download yet [$taxon_group]. Download not yet ready.\n";
        }
        return false;
    }
    function check_if_all_downloads_are_ready_YN()
    {
        if($this->resource_id == 'GBIF_map_harvest') {
            $groups = array('Animalia', 'Plantae', 'Other7Groups');
            foreach($groups as $taxon_group) {
                if(!self::generate_sh_file($taxon_group)) {
                    echo "\n[$taxon_group] NOT yet ready :-( \n";
                    return false;
                }
                else echo "\n[$taxon_group] now ready OK :-) \n";
            }
        }
        /* moved this below, together with the 6 GBIF countries
        elseif($this->resource_id == 'NMNH_images') {
            $taxon_group = 'NMNH_images';
            if(!self::generate_sh_file($taxon_group)) return false;
        }
        */
        else { //for NMNH_images and the 6 GBIF countries and iNat_images
            $taxon_group = $this->resource_id;
            if(!self::generate_sh_file($taxon_group)) return false;
        }
        return true;
    }
    private function can_proceed_to_download_YN($key)
    {   /* orig
        curl -Ss https://api.gbif.org/v1/occurrence/download/0000022-170829143010713 | jq .
        e.g. Gadus ogac
        curl -Ss https://api.gbif.org/v1/occurrence/download/0018153-200613084148143 | jq .
        */
        /* original entry but it seems jq is not needed or it is not essential. It just gives a pretty-print json output.
        And since it does not work in our Rhel Linux eol-archive, I just removed it.
        $cmd = 'curl -Ss https://api.gbif.org/v1/occurrence/download/'.$key.' | jq .';
        */
        $cmd = 'curl --insecure -Ss https://api.gbif.org/v1/occurrence/download/'.$key;
        
        $output = shell_exec($cmd);
        echo "\nRequest output:\n[$output]\n"; //good debug
        $arr = json_decode($output, true);
        // print_r($arr); exit;
        if($arr['status'] == 'SUCCEEDED') return $arr;
        else return false;
    }
    private function generate_json_request($taxon_group)
    {
        if($this->resource_id == 'GBIF_map_harvest') { //=====================================================================
            $taxon = $this->taxon;
            
            if($taxon_group == 'Other7Groups')  $taxon_array = Array("type" => "in", "key" => "TAXON_KEY", "values" => Array(0 => $taxon['Fungi'],
                1 => $taxon['Chromista'], 2 => $taxon['Bacteria'], 3 => $taxon['Protozoa'], 
                4 => $taxon['Archaea'], 5 => $taxon['Viruses']));
                /* as of Oct 5, 2023, removed 'incertae sedis'. It was included from the beginning though.
                4 => $taxon['incertae sedis']
                */

            else $taxon_array = Array("type" => "equals", "key" => "TAXON_KEY", "value" => $taxon[$taxon_group]);
            
            $param = Array( 'creator' => $this->gbif_username,
                            'notificationAddresses' => Array(0 => $this->gbif_email),
                            'sendNotification' => 1,
                            'format' => 'DWCA',
                            'predicate' => Array(
                                                    'type' => 'and',
                                                    'predicates' => Array(
                                                                            0 => Array(
                                                                                    'type' => 'equals',
                                                                                    'key' => 'HAS_COORDINATE',
                                                                                    'value' => 'true'
                                                                                 ),
                                                                            1 => Array(
                                                                                    'type' => 'equals',
                                                                                    'key' => 'HAS_GEOSPATIAL_ISSUE',
                                                                                    'value' => 'false'
                                                                                 ),
                                                                            2 => $taxon_array
                                                                        )
                                                )
                     );
            return json_encode($param);
        } //end GBIF_map_harvest

        /*Filter used:
        {
          "and" : [
            "HasCoordinate is true",
            "HasGeospatialIssue is false",
            "TaxonKey is Animalia"
          ]
        }*/

        //==================================================================================================================================
        if($this->resource_id == 'NMNH_images') {
            $predicate = Array(
                'type' => 'and',
                'predicates' => Array(
                                        0 => Array(
                                                'type' => 'equals',
                                                'key' => 'DATASET_KEY',
                                                'value' => '821cc27a-e3bb-4bc5-ac34-89ada245069d',
                                                'matchCase' => ''
                                             ),
                                        1 => Array(
                                                'type' => 'or',
                                                'predicates' => Array(
                                                                    0 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'MEDIA_TYPE',
                                                                            'value' => 'StillImage',
                                                                            'matchCase' => ''
                                                                        ),
                                                                    1 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'MEDIA_TYPE',
                                                                            'value' => 'MovingImage',
                                                                            'matchCase' => ''
                                                                        ),
                                                                    2 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'MEDIA_TYPE',
                                                                            'value' => 'Sound',
                                                                            'matchCase' => ''
                                                                        )
                                                                )
                                             ),
                                        2 => Array(
                                                'type' => 'equals',
                                                'key' => 'OCCURRENCE_STATUS',
                                                'value' => 'present',
                                                'matchCase' => ''
                                            )
                )
            );
        } //end NMNH_images
        
        /* from its download DOI: https://doi.org/10.15468/dl.b5vdyg
        From the 2nd box. Click 'API' to get the json format of the request. Then in php run below, to get the array value.
        $arr = json_decode($json, true);
        */
        
        //==================================================================================================================================
        $gbif_countries = array("GBIF_Netherlands", "GBIF_France", "GBIF_Germany", "GBIF_Brazil", "GBIF_Sweden", "GBIF_UnitedKingdom");
        if(in_array($this->resource_id, $gbif_countries)) {
            $predicate = Array(
                            'type' => 'and',
                            'predicates' => Array(
                                                0 => Array(
                                                        'type' => 'isNotNull',
                                                        'parameter' => 'TYPE_STATUS'
                                                     ),
                                                1 => Array(
                                                        'type' => 'equals',
                                                        'key' => 'PUBLISHING_COUNTRY',
                                                        'value' => $this->abbreviation[$this->resource_id],
                                                        'matchCase' => ''
                                                     )
                                            )
                         );
        } //end GBIF countries
        //==================================================================================================================================
        if($this->resource_id == 'iNat_images') {
            $predicate = Array(
                'type' => 'and',
                'predicates' => Array(
                                        0 => Array(
                                                'type' => 'equals',
                                                'key' => 'DATASET_KEY',
                                                'value' => '50c9509d-22c7-4a22-a47d-8c48425ef4a7',
                                                'matchCase' => ''
                                             ),
                                        1 => Array(
                                                'type' => 'or',
                                                'predicates' => Array(
                                                                    0 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'LICENSE',
                                                                            'value' => 'CC_BY_NC_4_0',
                                                                            'matchCase' => ''
                                                                        ),
                                                                    1 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'LICENSE',
                                                                            'value' => 'CC_BY_4_0',
                                                                            'matchCase' => ''
                                                                        ),
                                                                    2 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'LICENSE',
                                                                            'value' => 'CC0_1_0',
                                                                            'matchCase' => ''
                                                                        )
                                                                )
                                             ),
                                        2 => Array(
                                                'type' => 'equals',
                                                'key' => 'MEDIA_TYPE',
                                                'value' => 'StillImage',
                                                'matchCase' => ''
                                            )
                )
            );
        } //end iNat
        /* from download page: https://doi.org/10.15468/dl.xr247r (API) */
        //==================================================================================================================================
        
        /* For all except $this->resource_id == 'GBIF_map_harvest' */
        $param = Array( 'creator' => $this->gbif_username,
                        'notificationAddresses' => Array(0 => $this->gbif_email),
                        'sendNotification' => 1,
                        'format' => 'DWCA',
                        'predicate' => $predicate);
        return json_encode($param);
    }
    private function save_json_2file($json)
    {
        $file = $this->destination_path.'/query.json';
        $fhandle = Functions::file_open($file, "w");
        fwrite($fhandle, $json);
        fclose($fhandle);
    }
    private function save_key_per_taxon_group($taxon_group, $key)
    {
        $file = $this->destination_path.'/download_key_'.$taxon_group.'.txt';
        $fhandle = Functions::file_open($file, "w");
        fwrite($fhandle, $key);
        fclose($fhandle);
    }
    private function retrieve_key_for_taxon($taxon_group)
    {
        $file = $this->destination_path.'/download_key_'.$taxon_group.'.txt';
        if(file_exists($file)) {
            if($key = trim(file_get_contents($file))) return $key;
            else exit("\nDownload key not found for [$taxon_group]\n");
        }
        else echo "\nNo download request for this taxon yet [$taxon_group].\n\n";
    }
    private function create_bash_file($taxon_group, $downloadLink)
    {
        $row1 = "#!/bin/sh";
        /* worked for the longest time. But started having this error msg: 
        "curl: (33) HTTP server doesn't seem to support byte ranges. Cannot resume."
        So I now removed the "-C"
        $row2 = "curl -L -o '".$taxon_group."_DwCA.zip' -C - $downloadLink";
        */
        $row2 = "curl --insecure -LsS -o '".$taxon_group."_DwCA.zip' $downloadLink";   //this worked OK as of Oct 17, 2021 (as of Sep 28, 2023)
        /* for some reason this -sS is causing error. BETTER TO NOT USE IT.
        -s is "silent"
        -S is show errors when it is "silent"
        */
        $file = $this->destination_path.'/run_'.$taxon_group.'.sh';
        $fhandle = Functions::file_open($file, "w");
        fwrite($fhandle, $row1."\n");
        fwrite($fhandle, $row2."\n");
        fclose($fhandle);
    }
}
?>