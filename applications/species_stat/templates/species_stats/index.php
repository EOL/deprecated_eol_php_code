<?php
// the VIEW
// the form
// for viewing - as template

/* 
Expects:
    optional:
    $tc_id
*/

$tc_id = get_val_var("tc_id");
$proceed = get_val_var("proceed");
if($tc_id == ""){$tc_id=206692;}

function get_val_var($v)
{
    if         (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif     (isset($_POST["$v"])){$var=$_POST["$v"];}
    
    if(isset($var))
    {
        return $var;
    }
    else    
    {
        return NULL;
    }    
}
    
    
?>

<HTML>
    <HEAD>
        <TITLE>Species Stats</TITLE>
    </HEAD>
    <BODY>
        <h4>Stats Maintenance</h4>
        
        <form action="index.php" method="post" name="fn"> 
        
        <!---        
        taxon concept id = <input type="text" name="tc_id" value="<?php print $tc_id; ?>"> 
        <i>e.g. 206692 or 206692,583069,2621375,2621381,203973</i>
        <hr>
        If taxon concept id is blank it will take the first <input style="text-align : right;" type="text" name="limit" value="100"> records from taxon_concepts
        <hr>
        --->
        
        <input type="hidden" name="tc_id">
        <input type="hidden" name="limit">        

        <input type="radio" name="group" value="1"         checked>Taxa stats
        <input type="radio" name="group" value="2"                >Data object stats
        <input type="radio" name="group" value="4"                >Data object stats (more)
        <input type="radio" name="group" value="3"                >BHL and general outlinks
        <input type="radio" name="group" value="5"                >LifeDesk stats                
        
        <br>
        <input type="submit" value="Submit">
        <input type="reset" value="Reset">
        <input type="hidden" name="f" value="results">
        </form>
        &nbsp;
        <hr>
        <a href="archive.php">Stats History >> </a>
        
        
        <hr>
        <a href="index.php?group=1&f=results">Link run -- Taxa stats</a>                <br>
        <a href="index.php?group=2&f=results">Link run -- Data object stats</a>         <br>
        <a href="index.php?group=4&f=results">Link run -- Data object stats (more)</a>  <br>
        <a href="index.php?group=3&f=results">Link run -- BHL and general outlinks</a>  <br>
        <a href="save_stats.php">Save daily stats to a CSV file</a>  <br>
        <a href="index.php?group=5&f=results">Link run -- LifeDesk stats</a>  <br>
        
    </BODY>
</HTML>

<?php 

if($proceed == "y" )
{
    ?>
    <script language="javascript1.2">document.forms.fn.submit()</script>
    <?php    
}

?>