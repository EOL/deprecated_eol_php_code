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
    
    if($xsd)
    {
        print "The file's schema is located at: <b>$xsd</b><br/><br/>";
    }else
    {
        print "This file has no schema declaration";
        debug("This file has no schema declaration");
        return;
    }
    
    
    if(!$errors)
    {
        print 'This file is valid according to its defined XSD<br/><br/><br/><br/>';
    }else
    {
        ?>
        <hr/>
        This file is not valid:<br/>
        <blockquote>
        
        <?php
        foreach($errors as $error)
        {
            print "&nbsp;&nbsp;&nbsp;&nbsp;$error<br/>";
        }
        ?>
        
        </blockquote>
        <?php
        return;
    }
    
    if($is_eol_schema && !$eol_errors && !$eol_warnings)
    {
        print 'This file also passed all EOL internal validations<br/><br/>';
    }
    
    if($is_eol_schema && $eol_errors)
    {
        ?>
        <hr/>
        ...but there were EOL internal validation <b>errors</b> (so not all data objects will appear in EOL):<br/>
        <blockquote>
        
        <?php
        foreach($eol_errors as $scientific_name => $error)
        {
            print "$scientific_name<br/>";
            foreach($error as $message => $value)
            {
                print "&nbsp;&nbsp;&nbsp;&nbsp;$message<br/>";
            }
            print "<br/>";
        }
        ?>
        
        </blockquote>
        <?php
    }
    
    if($is_eol_schema && $eol_warnings)
    {
        ?>
        <hr/>
        ...but there were EOL internal validation <b>warnings</b>:<br/>
        <blockquote>
        
        <?php
        foreach($eol_warnings as $scientific_name => $warning)
        {
            print "$scientific_name<br/>";
            foreach($warning as $message => $value)
            {
                print "&nbsp;&nbsp;&nbsp;&nbsp;$message<br/>";
            }
            print "<br/>";
        }
        ?>
        
        </blockquote>
        <?php
    }
}

?>