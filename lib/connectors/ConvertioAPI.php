<?php
namespace php_active_record;
/* connector: [convertio.php] for testing */
class ConvertioAPI
{
    public function __construct($destination_folder = false)
    {
        // if(Functions::is_production()) $this->path['working_dir'] = '/extra/other_files/Smithsonian/epub/';
        // else                           $this->path['working_dir'] = '/Volumes/AKiTiO4/other_files/Smithsonian/epub/';
    }
    /*
    --------------------------- SAMPLE IF INPUT IS TO POST A LOCAL FILE ---------------------------
    STEP 1:
    curl -i -X POST -d '{"apikey": "ELI API KEY", "input":"upload", "outputformat":"txt"}' http://api.convertio.co/convert
    -> start, initialize request
    {"code":200,"status":"ok","data":{"id":"5aa1df42168c5b872947e0c0cc68fe34"}}
    This step required only if chooses input = 'upload' on previous step. 
    In order to upload file for conversion, you need to do a following PUT request

    STEP 2:
    curl -i -X PUT --upload-file 'SCtZ-0293.epub' http://api.convertio.co/convert/5aa1df42168c5b872947e0c0cc68fe34/SCtZ-0293.epub
    -> PUT request for local file
    {"code":200,"status":"ok","data":{"id":"5aa1df42168c5b872947e0c0cc68fe34","file":"SCtZ-0293.epub","size":3754860}}

    STEP 3:
    curl -i -X GET http://api.convertio.co/convert/5aa1df42168c5b872947e0c0cc68fe34/status
    -> get status
    {"code":200,"status":"ok","data":{"id":"5aa1df42168c5b872947e0c0cc68fe34","step":"finish","step_percent":100,"minutes":"1",
      "output":{"url":"https:\/\/s110.convertio.me\/p\/PiX4oKMhB9YQZYnX_k-OIg\/0faab539f8de23cd027d32cbddd6b620\/SCtZ-0293.txt","size":"272899"}}}
    - end -
    */
    function initialize_request() //step 1
    {   // -i ->     --include       Include protocol headers in the output (H/F)
        if(!isset($this->api_key)) {
            $this->api_key = CONVERTIO_API_KEY_1;
            echo "\nkey initialized OK\n";
        }
        else echo "\nkey initialized already\n";
        $cmd = "curl -S -s -X POST -d "."'".'{"apikey": "'.$this->api_key.'", "input":"upload", "outputformat":"txt"}'."' http://api.convertio.co/convert";
        $cmd .= " 2>&1";
        $json = shell_exec($cmd);           //echo "\n$json\n";
        $obj = json_decode(trim($json));    //print_r($obj);
        /*
        {"code":200,"status":"ok","data":{"id":"cb13182ccbd69f6c74618f5a47d1b065"}}
        stdClass Object(
            [code] => 200
            [status] => ok
            [data] => stdClass Object(
                    [id] => cb13182ccbd69f6c74618f5a47d1b065
                )
        )
        */
        if($obj->status == "ok") {
            // print_r($obj);
            // echo "\nOK api_id: ". (string) $obj->data->id."\n";
            return (string) $obj->data->id;
        }
        else {
            /*stdClass Object(
                [code] => 422
                [status] => error
                [error] => No convertion minutes left
            )*/
            // print_r($obj);
            self::switch_api();
            if($id = self::initialize_request()) return $id; //important line. It loops but also returns the id.
            else exit("\nERRORx: Should not go here.\n");
        }
        return false;
    }
    private function switch_api()
    {
        if($this->api_key == CONVERTIO_API_KEY_1) { 
            $this->api_key = CONVERTIO_API_KEY_2;
            echo "\nkey 1 expired, will try key 2\n";
        }
        elseif($this->api_key == CONVERTIO_API_KEY_2) {
            $this->api_key = CONVERTIO_API_KEY_3;
            echo "\nkey 2 expired, will try key 3\n";
        }
        elseif($this->api_key == CONVERTIO_API_KEY_3) {
            $this->api_key = CONVERTIO_API_KEY_4;
            echo "\nkey 3 expired, will try key 4\n";
        }
        elseif($this->api_key == CONVERTIO_API_KEY_4) {
            $this->api_key = CONVERTIO_API_KEY_5;
            echo "\nkey 4 expired, will try key 5\n";
        }
        elseif($this->api_key == CONVERTIO_API_KEY_5) {
            echo "\nkey 5 expired, will terminate now...\n";
            exit("\nERRORx: call initialize failed. All keys expired.\n");
        }
    }
    function upload_local_file($source, $filename, $api_id) //step 2
    {   /*
        curl -S -s -X PUT --upload-file 'SCtZ-0293.epub' http://api.convertio.co/convert/5aa1df42168c5b872947e0c0cc68fe34/SCtZ-0293.epub
        -> PUT request for local file
        */
        // echo "\nsource: $source\n";
        // echo "\nfilename: $filename\n";
        // echo "\napi_id: $api_id\n";
        if(!filesize($source)) {
            echo "\nERRORx: Will not upload, .epub file is empty.\n";
            return false;
        }
        $cmd = "curl -S -s -X PUT --upload-file '".$source."' http://api.convertio.co/convert/".$api_id."/".$filename;
        $cmd .= " 2>&1";
        $json = shell_exec($cmd);           //echo "\n$json\n";
        $obj = json_decode(trim($json));    //print_r($obj);
        if($obj->status == "ok") return $obj;
        else {
            echo "\n$cmd\n"; print_r($obj); print("\nERRORx: file upload failed [$filename].\n");
        }
        return false;
    }
    function check_status($api_id, $ctr = 0, $filename) //3rd param $filename is for debug only
    {
        $cmd = "curl -S -s -X GET http://api.convertio.co/convert/".$api_id."/status";
        $cmd .= " 2>&1";
        $json = shell_exec($cmd);           //echo "\n$json\n";
        $obj = json_decode(trim($json));    //print_r($obj);
        if($obj->status != "ok") {
            echo "\n--------\n$cmd\n"; print_r($obj); print("\nERROR: status check failed [$filename]\n--------\n");
        }
        if($obj->status == "ok" && $obj->data->step_percent == 100) return $obj;
        else {
            print_r($obj);
            echo("\nSTATUS: still processing...Check again after 2 minutes\n");
            sleep(60*2);
            $ctr++;
            if($ctr >= 4) {
                exit("\nTried 3x already. Investigate Convertio, daily limit might have been reached.\n");
            }
            self::check_status($api_id, $ctr);
        }
        return false;
        /*stdClass Object(
            [code] => 200
            [status] => ok
            [data] => stdClass Object(
                    [id] => 31d5363e37189a0650835c5c9a26d2b2
                    [step] => finish
                    [step_percent] => 100
                    [minutes] => 1
                    [output] => stdClass Object(
                            [url] => https://s183.convertio.me/p/aiRl8zoMB5y_RX5c0RY4Fw/0faab539f8de23cd027d32cbddd6b620/SCtZ-0007.txt
                            [size] => 157826
                        )
                )
        )*/
    }
}
?>