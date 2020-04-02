<?php
namespace php_active_record;
/* connector: taxon_image_bundles_part2.php */

class KatieXMLformatAPI
{
    function __construct($resource_id, $param)
    {
        $this->param = $param;
        // print_r($param); exit;
        /* stdClass Object (
            [eol_page_id] => 7631
            [sci] => Chiroptera
        )
        */
        if(Functions::is_production()) {
            $this->path['destination'] = '/extra/other_files/bundle_images/xml/';
            $this->prefix = 'https://editors.eol.org/other_files/';
        }
        else {
            $this->path['destination'] = '/Volumes/AKiTiO4/other_files/bundle_images/xml/';
            $this->prefix = 'http://localhost/other_files/';
        }
        
        // /* these entries change based on whatever batch is given by Katie
        $this->file['Chiroptera']['source'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Katie_bundle_images/chiroptera_crops_all_transf_eli.csv';
        $this->file['Lepidoptera_test']['source'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Katie_bundle_images/lepidoptera_crops_test_notaug_transf_foreli.tsv';
        $this->file['Lepidoptera_train']['source'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Katie_bundle_images/lepidoptera_crops_train_aug_all_transf_foreli.tsv';

        //next batch: Apr 1, 2020
        $this->file['anura_crops_test_notaug_fin']['source'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Katie_bundle_images/anura_crops_test_notaug_fin_foreli.tsv';
        $this->file['carnivora_crops_test_notaug_fin']['source'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Katie_bundle_images/carnivora_crops_test_notaug_fin_foreli.tsv';
        $this->file['coleoptera_crops_test_notaug_fin']['source'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Katie_bundle_images/coleoptera_crops_test_notaug_fin_foreli.tsv';
        $this->file['multitaxa_crops_test_notaug_fin']['source'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Katie_bundle_images/multitaxa_crops_test_notaug_fin_foreli.tsv';
        $this->file['multitaxa_crops_train_aug_all_fin']['source'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Katie_bundle_images/multitaxa_crops_train_aug_all_fin_foreli.tsv';
        $this->file['squamata_crops_test_notaug_fin']['source'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Katie_bundle_images/squamata_crops_test_notaug_fin_foreli.tsv';
        
        $filename = "xml_for_".$param->sci.".txt";
        // */
        
        if(!file_exists($this->path['destination'])) mkdir($this->path['destination']);
        $this->report_file = str_replace('xml/', '', $this->path['destination']) . $filename;
        $this->download_options = array(
            'resource_id'        => $resource_id,  //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //should not expire
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5, 'cache' => 1);
    }
    public function start()
    {
        if($FILE = Functions::file_open($this->report_file, 'w')) fclose($FILE); //initialize report file
        $local_tsv = Functions::save_remote_file_to_local($this->file[$this->param->sci]['source'], $this->download_options);
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
                // if($i >= 5) break; //debug
            }
        }
        unlink($local_tsv);
        $report_file = self::format_url_path($this->report_file);
        echo "\nreport_file: $report_file\n";
    }
    private function create_xml($rec)
    {
        $main = new \SimpleXMLElement("<annotation></annotation>");
        $main->addChild('folder', $rec['folder']);
        $main->addChild('filename', $rec['filename']);
        
        $rec_path = self::format_path($rec['path']);
        $main->addChild('path', $rec_path);

        $source = $main->addChild('source');
            $source->addChild('database', 'Unknown');
        $size = $main->addChild('size');
            $size->addChild('width', $rec['width']);
            $size->addChild('height', $rec['height']);
            $size->addChild('depth', '3');
        $main->addChild('segmented', '0');
        $object = $main->addChild('object');
            $object->addChild('name', $rec['name']);
            $object->addChild('pose', 'Unspecified');
            $object->addChild('truncated', '1');
            $object->addChild('difficult', '0');
            $bndbox = $object->addChild('bndbox');
                $bndbox->addChild('xmin', $rec['xmin']);
                $bndbox->addChild('ymin', $rec['ymin']);
                $bndbox->addChild('xmax', $rec['xmax']);
                $bndbox->addChild('ymax', $rec['ymax']);
        /*
        <annotation>
            <folder>bats_test_annotation</folder>
            <filename>31356195_aug.jpg</filename>
            <path>
                /content/drive/My Drive/fall19_smithsonian_informatics/train/images/31356195_aug.jpg
            </path>
            <source>
                <database>Unknown</database>
            </source>
            <size>
                <width>200</width>
                <height>274</height>
                <depth>3</depth>
            </size>
            <segmented>0</segmented>
            <object>
                <name>Chiroptera</name>
                <pose>Unspecified</pose>
                <truncated>1</truncated>
                <difficult>0</difficult>
                <bndbox>
                    <xmin>20</xmin>
                    <ymin>0</ymin>
                    <xmax>46</xmax>
                    <ymax>200</ymax>
                </bndbox>
            </object>
        </annotation>
        */
        // Header('Content-type: text/xml');
        // echo $main->asXML();
        self::save_xml($main->asXML(), $rec);
    }
    private function format_path($str)
    {
        $str = trim($str);
        if(substr($str,0,1) == "/") {
            $new = trim(substr($str,1,strlen($str)));
            return $new;
        }
        else return $str;
    }
    private function save_xml($xml_str, $rec)
    {   /*Array(
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
        $options['cache_path'] = $this->path['destination'];
        $filename_xml = pathinfo($rec['filename'], PATHINFO_FILENAME).'.xml';
        $md5 = md5($rec['filename']);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$filename_xml";
        debug("\n[$cache_path]\n");
        $url_path = self::format_url_path($cache_path);
        if(file_exists($cache_path)) debug("\nFile already exists\n");
        else {
            debug("\nCreating file\n");
            if($FILE = Functions::file_open($cache_path, 'w+')) {
                fwrite($FILE, $xml_str);
                fclose($FILE);
            }
        }
        if($FILE = Functions::file_open($this->report_file, 'a')) {
            fwrite($FILE, $url_path."\n");
            fclose($FILE);
        }
    }
    private function format_url_path($local_path)
    {
        $arr = explode('other_files/', $local_path);
        return $this->prefix.$arr[1];
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