<?
	// Make sure nothing is cached
	header("Cache-Control: must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H")-2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
	// So that the loading indicator is visible
	sleep(1);
	// The id of the node being opened
	$id = $_GET["id"];
	echo "[\n";
	$nodes = ($id == "0") ? 1 : rand(1,5);
	for($i = 1; $i <= $nodes; $i++) {
		if($i > 1) echo ",\n";
		echo "\t{\n";
		echo "\t\tattributes: { id : '".$_GET["id"]."_".$i."' },\n";
		echo "\t\tstate: '".( ($id == 0 || rand(0,100)%2 == 0) ? "closed" : "" )."',\n";
		echo "\t\tdata: '".$_GET["id"]."_".$i."'\n";
		echo "\t}";
	}
	echo "\n]";
	exit();
?>