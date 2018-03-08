<?php
namespace php_active_record;
    /* 
        Expects:
            $file_url
            $file_upload
            $is_eol_schema
            $xsd
            $errors
            $eol_errors
            $eol_warnings
    */
$GLOBALS['ENV_DEBUG'] = true;
?>

<?php
if($file_url || $file_upload)
{
    if($file_url) print "The file entered was: <b>$file_url</b><br/><br/>";
    elseif($file_upload) print "You uploaded: <b>$file_upload</b><br/><br/>";
    
    $pass_color = "#008000";
    $fail_color = "#FF0000";
    $partial_pass_color = "#FF8000";
    $color = $pass_color;
    $status = "Valid";
    if($structural_errors)
    {
        $color = $fail_color;
        $status = "Invalid";
    }elseif($errors)
    {
        $color = $partial_pass_color;
        $status = "Partially Valid";
    }
    print "<hr><h2 style='color:$color;'>This archive is $status</h2>";
    
    
    if($stats)
    {
        echo "<hr><h3>Statistics</h3><blockquote>";
        foreach($stats as $row_type => $row_stats)
        {
            echo "<b>$row_type</b>:<blockquote>";
            foreach($row_stats as $title => $values)
            {
                if(is_array($values))
                {
                    echo "$title:<blockquote>";
                    foreach($values as $subtitle => $value)
                    {
                        echo "$subtitle: $value<br/>";
                    }
                    echo "</blockquote>";
                }else
                {
                    echo "$title: $values<br/>";
                }
            }
            echo "</blockquote>";
        }
        echo "</blockquote>";
    }
    
    if($errors || $structural_errors)
    {
        ?>
        <hr/>
        <h3>Errors</h3>
        <blockquote><pre><?php
            display_exceptions($structural_errors);
            display_exceptions($errors);
        ?></pre></blockquote>
        <?php
    }
    
    if($warnings)
    {
        ?>
        <hr/>
        <h3>Warnings</h3>
        <blockquote><pre><?php display_exceptions($warnings); ?></pre></blockquote>
        <?php
    }
}

function display_exceptions($exceptions)
{
    if($exceptions)
    {
        foreach($exceptions as $exception)
        {
            print_exception($exception);
        }
    }
}

function print_exception($exception)
{
    if(is_string($exception))
    {
        print "Message: $exception<br/>";
    }else
    {
        if($exception->file) print "File: $exception->file<br/>";
        if($exception->line) print "Line: $exception->line<br/>";
        if($exception->uri) print "URI: $exception->uri<br/>";
        print "Message: $exception->message<br/>";
        if($exception->value) print "Line Value: $exception->value<br/>";
    }
    print "<br/>";
    print "<br/>";
}

?>
