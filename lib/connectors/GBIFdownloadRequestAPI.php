<?php
namespace php_active_record;
/* connector: [gbif_download_request.php] */
class GBIFdownloadRequestAPI
{
    function __construct()
    {
        $this->gbif_username = 'eli_agbayani';
        $this->gbif_pw = 'ile173';
        /*
        others: 0017549-200613084148143
        */
    }
    function send_download_request($taxon_group)
    {
        $json = self::generate_json_request($taxon_group);
        self::save_json_2file($json);
        $arr = json_decode($json, true);
        print_r($arr);
        /* orig per https://www.gbif.org/developer/occurrence#download
        curl --include --user userName:PASSWORD --header "Content-Type: application/json" --data @query.json https://api.gbif.org/v1/occurrence/download/request
        */
        $filename = CONTENT_RESOURCE_LOCAL_PATH.'query.json';
        $cmd = 'curl --include --user '.$this->gbif_username.':'.$this->gbif_pw.' --header "Content-Type: application/json" --data @'.$filename.' https://api.gbif.org/v1/occurrence/download/request';
        $output = shell_exec($cmd);
        echo "\nRequest output:\n[$output]\n";
        
        44359969
        44359969
        44610800
    }
    function check_download_request_status($key)
    {
        /* orig
        curl -Ss https://api.gbif.org/v1/occurrence/download/0000022-170829143010713 | jq .
        */
        $cmd = 'curl -Ss https://api.gbif.org/v1/occurrence/download/'.$key.' | jq .';
        $output = shell_exec($cmd);
        echo "\nRequest output:\n[$output]\n";
    }
    private function generate_json_request($taxon_group)
    {
        $taxon['Animalia'] = 1;
        $taxon['Plantae'] = 6;
        $taxon['Fungi'] = 5;
        $taxon['Chromista'] = 4;
        $taxon['Bacteria'] = 3;
        $taxon['Protozoa'] = 7;
        $taxon['incertae sedis'] = 0;
        $taxon['Archaea'] = 2;
        $taxon['Viruses'] = 8;
        if($taxon_group == 'Animalia')    $taxon_array = Array("type" => "equals", "key" => "TAXON_KEY", "value" => $taxon['Animalia']);
        elseif($taxon_group == 'Plantae') $taxon_array = Array("type" => "equals", "key" => "TAXON_KEY", "value" => $taxon['Plantae']);
        elseif($taxon_group == 'Others')  $taxon_array = Array("type" => "in", "key" => "TAXON_KEY", "values" => Array(0 => $taxon['Fungi'],          1 => $taxon['Chromista'],
                                                                                                                       2 => $taxon['Bacteria'],       3 => $taxon['Protozoa'],
                                                                                                                       4 => $taxon['incertae sedis'], 5 => $taxon['Archaea'],
                                                                                                                       6 => $taxon['Viruses']));
        $param = Array( 'creator' => 'eli_agbayani',
                        'notificationAddresses' => Array(0 => 'eagbayani@eol.org'),
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
        $filename = CONTENT_RESOURCE_LOCAL_PATH.'query.json';
        $fhandle = Functions::file_open($filename, "w");
        fwrite($fhandle, $json);
        fclose($fhandle);
    }
}
?>