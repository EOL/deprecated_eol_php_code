<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
system("clear");

$log = HarvestProcessLog::create(array('process_name' => 'Update Connector Resources'));

$mysqli =& $GLOBALS['mysqli_connection'];

$manager = new ContentManager();

$connectors = Functions::get_files_in_dir(dirname(__FILE__) . "/connectors");
foreach($connectors as $file)
{
    if(!preg_match("/^(.*)\.php$/", $file, $arr)) continue;

    $resource = Resource::find($arr[1]);
    if(!@$resource->id) continue;
    if(!$resource->ready_to_update()) continue;

    // resources to skip as they are scheduled seperately
    if($resource->id == 15) continue; // Flickr
    if($resource->id == 71) continue; // Wikimedia Commons
    if($resource->id == 80) continue; // Wikipedia
    if($resource->id == 211) continue; // IUCN Redlist
    if($resource->id == 31) continue; // BioPix
    if($resource->id == 83) continue; // MorphBank
    if($resource->id == 185) continue; // Turbellarian
    if($resource->id == 223) continue; // DiscoverLife maps
    if($resource->id == 252) continue; // DiscoverLife keys
    if($resource->id == 218) continue; // Tropicos
    if($resource->id == 190) continue; // FishWise
    if($resource->id == 171) continue; // OBIS data
    if($resource->id == 168) continue; // BioImages
    if($resource->id == 138) continue; // Afrotropical
    if($resource->id == 123) continue; // AquaMaps
    if($resource->id == 98) continue; // Hexacorallians
    if($resource->id == 81) continue; // BOLD Systems - higher level taxa
    if($resource->id == 212) continue; // BOLD Systems - species level taxa
    if($resource->id == 68) continue; // Dutch Species Catalogue
    if($resource->id == 63) continue; // INOTAXA
    if($resource->id == 26) continue; // WORMS
    if($resource->id == 266) continue; // US Fish and Wildlife Services

    echo "$file...\n";
    shell_exec(PHP_BIN_PATH . dirname(__FILE__) . "/connectors/". $file." ENV_NAME=slave");
}

$log->finished();
?>