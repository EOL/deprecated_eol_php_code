<?php
namespace php_active_record;
/* connector: [gbif_download_request.php] */
class GBIFdownloadRequestAPI
{
    function __construct()
    {
        $this->gbif_username = 'eli_agbayani';
        $this->gbif_pw = 'ile173';
        $this->gbif_email = 'eagbayani@eol.org';

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
        $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF';
        if(!is_dir($this->destination_path)) mkdir($this->destination_path);
    }
    function send_download_request($taxon_group)
    {
        $json = self::generate_json_request($taxon_group);
        self::save_json_2file($json);
        $arr = json_decode($json, true); // print_r($arr);
        /* orig per https://www.gbif.org/developer/occurrence#download
        curl --include --user userName:PASSWORD --header "Content-Type: application/json" --data @query.json https://api.gbif.org/v1/occurrence/download/request
        */
        $filename = CONTENT_RESOURCE_LOCAL_PATH.'query.json';
        $cmd = 'curl --include --user '.$this->gbif_username.':'.$this->gbif_pw.' --header "Content-Type: application/json" --data @'.$filename.' -s https://api.gbif.org/v1/occurrence/download/request';
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
    function start_download($taxon_group)
    {
        if($key = self::retrieve_key_for_taxon($taxon_group)) {
            echo "\nDownload key is: [$key]\n";
            if(self::can_proceed_to_download_YN($key)) {
                echo "\nCan proceed to download...\n";
            }
            else {
                echo "\nCannot download yet\n";
            }
        }
    }
    private function can_proceed_to_download_YN($key)
    {   /* orig
        curl -Ss https://api.gbif.org/v1/occurrence/download/0000022-170829143010713 | jq .
        */
        $cmd = 'curl -Ss https://api.gbif.org/v1/occurrence/download/'.$key.' | jq .';
        $output = shell_exec($cmd);
        echo "\nRequest output:\n[$output]\n";
        $arr = json_decode($output, true); // print_r($arr);
        if($arr['status'] == 'SUCCEEDED') return true;
        else return false;
    }
    private function generate_json_request($taxon_group)
    {
        $taxon = $this->taxon;
        if($taxon_group == 'Others')  $taxon_array = Array("type" => "in", "key" => "TAXON_KEY", "values" => Array(0 => $taxon['Fungi'],          1 => $taxon['Chromista'],
                                                                                                                   2 => $taxon['Bacteria'],       3 => $taxon['Protozoa'],
                                                                                                                   4 => $taxon['incertae sedis'], 5 => $taxon['Archaea'],
                                                                                                                   6 => $taxon['Viruses']));
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
    }
    private function save_json_2file($json)
    {
        $file = CONTENT_RESOURCE_LOCAL_PATH.'query.json';
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
        else exit("\nNo request to download for this taxon yet [$taxon_group].\n\n");
    }
}
?>