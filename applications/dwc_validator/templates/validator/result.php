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
    elseif($file_upload) print "You uploaded: <b>$file_upload</b><br/>";
    
    if(!$errors)
    {
        print 'This file is valid<br/><br/><br/><br/>';
    }else
    {
        ?>
        <hr/>
        This file contained errors:<br/>
        <blockquote><pre><?php
        foreach($errors as $error)
        {
            print "File: $error->file<br/>";
            print "Line: $error->line<br/>";
            if($error->uri) print "URI: $error->uri<br/>";
            print "Error Message: $error->message<br/>";
            if($error->value) print "Line Value: $error->value<br/>";
            print "<br/>";
            print "<br/>";
        }
        ?></pre></blockquote>
        <?php
    }
    
    if(!$warnings)
    {
        print 'This file also contained no warnings<br/><br/>';
    }
    
    if($warnings)
    {
        ?>
        <hr/>
        This file contained warnings:<br/>
        <blockquote><pre><?php
        foreach($warnings as $warning)
        {
            print "File: $warning->file<br/>";
            print "Line: $warning->line<br/>";
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