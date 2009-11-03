<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
</head>

<body>

Test to see how barcode looks in an EOL species page:
<hr>
<form action="speciessummary.php">

<input type="radio" name="flag" value="text"> Text flag <br>
<input name="caption" value="With Barcode Sequence" size = '50'>

<hr>

<input type="radio" name="flag" value="image"> Image flag<br>
image height <input name="image_ht" size='6' value='30'>
<br>
caption <input name="caption2" value="With barcode sequence" size = '50'>

<hr>
<input type="radio" name="flag" value="barcode" checked> Actual barcode<br>
resized barcode height <input name="barcode_ht" size='6' value='200'><br>
resized barcode width <input name="barcode_wd" size='6' value='320'>


<hr>

Barcode width: <input name="barcode_width" value='900' size='7'>

<hr>
species name: <input name="species" value='<i>Gadus morhua</i> Linnaeus, 1758' size='60'>
<br>
e.g. <i>Oreochromis niloticus niloticus</i> (Linnaeus, 1758)
<hr>

<input type='submit' value='submit'>
<input type='reset' value='reset'>

</form>



</body>
</html>
