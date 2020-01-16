<?php
namespace php_active_record;
/* connector: taxon_image_bundles_part2.php */

class KatieXMLformatAPI
{
    function __construct($resource_id)
    {
        if(Functions::is_production()) {
            $this->file['source'] = 'http://localhost/other_files/bundle_images/DATA_1845/chiroptera_crops_all_transf_eli.csv';
            $this->path['destination'] = '/extra/other_files/bundle_images/xml/';
        }
        else {
            $this->file['source'] = 'http://localhost/other_files/bundle_images/DATA_1845/chiroptera_crops_all_transf_eli.csv';
            $this->path['destination'] = '/Volumes/AKiTiO4/other_files/bundle_images/xml/';
        }
        $this->download_options = array(
            'resource_id'        => $resource_id,  //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //should not expire
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5, 'cache' => 1);
    }
    public function start()
    {
        $local_tsv = Functions::save_remote_file_to_local($this->file['source'], $this->download_options);
        $i = 0;
        foreach(new FileIterator($local_tsv) as $line_number => $line) {
            $line = explode("\t", $line); $i++; 
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [folder] => images
                    [filename] => 31356195_aug.jpg
                    [path] => /content/drive/My Drive/fall19_smithsonian_informatics/train/images/31356195_aug.jpg
                    [width] => 274
                    [height] => 200
                    [xmin] => 20
                    [ymin] => 0
                    [xmax] => 246
                    [ymax] => 200
                    [] => 
                    [name] => Chiroptera
                )*/
                self::create_xml($rec);
                exit;
            }
        }
        unlink($local_tsv);
    }
    private function create_xml($rec)
    {
        $main = new \SimpleXMLElement("<annotation></annotation>");
        $main->addChild('folder', $rec['folder']);
        $main->addChild('filename', $rec['filename']);
        // Header('Content-type: text/xml');
        echo $main->asXML();
    }
    /*
    <?php
    $newsXML = new SimpleXMLElement("<news></news>");
    $newsXML->addAttribute('newsPagePrefix', 'value goes here');
    $newsIntro = $newsXML->addChild('content');
    $newsIntro->addAttribute('type', 'latest');
    Header('Content-type: text/xml');
    echo $newsXML->asXML();
    ?>
    */
}
?>