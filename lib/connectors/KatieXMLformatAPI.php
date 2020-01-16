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
        $main->addChild('path', $rec['path']);
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
        $id = 
        self::save_xml($main->asXML(), $id)
    }
    private function save_xml($str, $id)
    {
        // default expire time is 30 days
        if(!isset($options['expire_seconds'])) $options['expire_seconds'] = 60*60*24*25; //default expires in 25 days
        if(!isset($options['timeout'])) $options['timeout'] = 120;
        if(!isset($options['cache_path'])) $options['cache_path'] = DOC_ROOT . $GLOBALS['MAIN_CACHE_PATH'];    //orig value in environment.php is 'tmp/cache/'

        $md5 = md5($url);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);

        if($resource_id = @$options['resource_id'])
        {
            $options['cache_path'] .= "$resource_id/";
            if(!file_exists($options['cache_path'])) mkdir($options['cache_path']);
        }

        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.cache";
        if(file_exists($cache_path))
        {
            $file_contents = file_get_contents($cache_path);
            $cache_is_valid = true;
            if(@$options['validation_regex'] && !preg_match("/". $options['validation_regex'] ."/ims", $file_contents))
            {
                $cache_is_valid = false;
            }
            if(($file_contents && $cache_is_valid) || (strval($file_contents) == "0" && $cache_is_valid))
            {
                $file_age_in_seconds = time() - filemtime($cache_path);
                if($file_age_in_seconds < $options['expire_seconds']) return $file_contents;
                if($options['expire_seconds'] === false) return $file_contents;
            }
            @unlink($cache_path);
        }
        $file_contents = Functions::get_remote_file($url, $options);
        if($FILE = Functions::file_open($cache_path, 'w+')) // normal
        {
            fwrite($FILE, $file_contents);
            fclose($FILE);
        }
        else // can happen when cache_path is from external drive with corrupt dir/file
        {
            if(!($h = Functions::file_open(DOC_ROOT . "/public/tmp/cant_delete.txt", 'a'))) return;
            fwrite($h, $cache_path . "\n");
            fclose($h);
        }
        return $file_contents;
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