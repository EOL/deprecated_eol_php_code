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

?>

<?php
if($file_url || $file_upload)
{
    if($file_url) print "The file entered was: <b>$file_url</b><br/>";
    elseif($file_upload) print "You uploaded: <b>$file_upload</b><br/><br/>";
    
    
    if(!$errors)
    {
        print 'This file contained no errors<br/><br/>';
        if(!$warnings)
        {
            print 'This file contained no warnings<br/><br/>';
        }
    }
    
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
    
    if($errors)
    {
        ?>
        <hr/>
        <h3>Errors</h3>
        <blockquote><pre><?php
        foreach($errors as $error)
        {
            if(is_string($error))
            {
                print "Error Message: $error<br/>";
            }else
            {
                if($error->file) print "File: $error->file<br/>";
                if($error->line) print "Line: $error->line<br/>";
                if($error->uri) print "URI: $error->uri<br/>";
                print "Error Message: $error->message<br/>";
                if($error->value) print "Line Value: $error->value<br/>";
            }
            print "<br/>";
            print "<br/>";
        }
        ?></pre></blockquote>
        <?php
    }
    
    if($warnings)
    {
        ?>
        <hr/>
        <h3>Warnings</h3>
        <blockquote><pre><?php
        foreach($warnings as $warning)
        {
            if($warning->file) print "File: $warning->file<br/>";
            if($warning->line) print "Line: $warning->line<br/>";
            if($warning->uri) print "URI: $warning->uri<br/>";
            print "Warning Message: $warning->message<br/>";
            if($warning->value) print "Line Value: $warning->value<br/>";
            print "<br/>";
            print "<br/>";
        }
        ?></pre></blockquote>
        <?php
    }
}

?>