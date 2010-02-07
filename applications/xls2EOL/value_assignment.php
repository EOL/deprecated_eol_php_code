<?php
//test feb6 sunday
$old_s=173;
for ($s=0; $s <= $no_of_sheets-1; $s++) 
{
    if(1==1)
     {
         //print"<table border='1'>";
         if($old_s != $s){//print"<tr><td colspan='2'>{{$wsheet[$s]}}</td></tr>";
                         }
        $old_s = $s;     
        for ($row = 1; $row <= $data->sheets[$s]['numRows']; $row++) 
        {
            //##########################################################################################          
            //print"<tr>";
            for ($col = 1; $col <= $data->sheets[$s]['numCols']; $col++) 
            {
                $str = @$data->sheets[$s]['cells'][$row][$col];               
                //##########################################################################################                                                  
                $tmp = $col-1;
               
                $arr = array_keys($sheet[$s]);
                $text_index = @$arr[$tmp];               
          
                $sheet[$s][$text_index][$row-1] = $str;
                    
                //print'<TD>';
                /*     
                $char = substr($str,0,1);
                if(ord($char) > 127)          {$x .= "<item>$str</item>"; //echo "$str ";               }
                else {$x .= "<item>" . utf8_encode($str) . "</item>"; //echo utf8_encode($str) . " ";     }          */
                //echo "$str";     //for debugging
                //print"</td>";
                //##########################################################################################     
               
              }//cols
              //print'</tr>';
              //##########################################################################################          
        }//rows
        //print'</table>';     
    }//if($s==2)
}//sheets

//start re-order
for ($s=0; $s <= sizeof($wsheet)-1; $s++) 
{
     $arr = array_keys($sheet[$s]);
     for ($t=0; $t <= sizeof($arr)-1; $t++) 
     {
          /*
          $wsheet[0]='Contributors';
          $wsheet[1]='Attributions';
          $wsheet[2]='Text descriptions';
          $wsheet[3]='References';
          $wsheet[4]='Multimedia';
          $wsheet[5]='Taxon Information (optional)';
          $wsheet[6]='More common names (optional)';          
          */
          switch ($s)//worksheet number
               {    case 0:     $startrow = 1; break;
                    case 1:     $startrow = 1; break;
                    case 2:     $startrow = 2; break;
                    case 3:     $startrow = 1; break;
                    case 4:     $startrow = 2; break;
                    case 5:     $startrow = 1; break;
                    case 6:     $startrow = 1; break;
                    default:    $startrow = 0;
               }                              
          $sheet[$s][$arr[$t]] = reorder_index($sheet[$s][$arr[$t]],$startrow);               
          //$sheet[5]['Kingdom'] = reorder_index($sheet[5]['Kingdom']);
     }     
}
//end re-order
?>