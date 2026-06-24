<?php 
function display_form_search($conn){?>
<form  action="#"  method ="post">
      <select name="nation">
        <option></option>
        <?php fill_nationality($conn); ?>
      </select>

     <input type="submit" name="button_nation"/>
</form>
<?php
}


function fill_datalist_nationality($conn)
{
try {
      $query="SELECT distinct nation FROM info";
      $rows=$conn->query($query);//تيبل مؤقت
      while($row=$rows->fetch(PDO::FETCH_OBJ))
      {?>
           <option value="<?php echo $row->nation; ?>"> <?php echo $row->nation;?> </option>
<?php
      }
    }
catch(PDOException $e)
  {
    echo "Error :" . $e->getMessage();
  }
}



function fill_nationality($conn){
try{
  $sql="SELECT distinct nation FROM info";
  $rows=$conn->query($sql);
  while($row=$rows->fetch(PDO::FETCH_OBJ))
  {?>
      <option value="<?php echo $row->nation; ?>"><?php echo $row->nation; ?></option>
<?php
  }
}catch(PDOException $e)
 {
  echo "error". $e->getMessage();
 }

}

function search_view_form_radio($conn){
$nat=$_POST['nation'];
try{
    $sq="SELECT * FROM info WHERE nation=?";
    $rows=$conn->prepare($sq);//مفروض نستخدم ركوير
    //$rows->setFetchMode(PDO::FETCH_ASSOC);
    $rows->execute(array($nat));
    echo'<form method="post" action="#">';
    echo "<table border='1' ><tr><th>id number student name </th></tr>";
    while($row=$rows->fetch(PDO::FETCH_OBJ)){
         echo "<tr><td><input type='radio' name='id' value='".$row->id."'>" . $row->id . ":" . $row->name . "</td></tr>"; 
   }
    echo '<tr><td><input type="submit" name="button_getid"></td></tr>';
    echo"</table></form>";
   }catch(PDOException $e){
      echo "error:" . $e->getMessage();
   }

}

function search_view_form_checkbox($conn)
{
      $nat=$_POST['nation'];
      try{
           $sql="SELECT * FROM info where nation=?";//اشارة استفهام لأنها حاجة بيجيبها من الفورم
           $rows=$conn->prepare($sql);//
           $rows->setFetchMode(PDO:: FETCH_ASSOC);
           $rows->execute(array($nat));
           $n=$rows->rowCount();//عدد السجلات يلي بنعبيهم
           if($n >0)//مافيش سجلات
           {
             echo '<form action="#" method="post">';
             echo "<table border='1'> <tr><td> id number : student name </th></tr>";
             $row=$rows->fetchAll();
             for($i=0 ; $i<$n ;$i++) 
             {
               echo "<tr><td> <input type='checkbox' name='id[]' value='".$row[$i]['id']."'>".$row[$i]['id']. ":" .$row[$i]['name']."</td></tr>";//$i الصف الاول والتاني وهكذا
             }//end for
             echo '<tr><td><input type="submit" name="del"></td><tr>'; 
             echo "</table></form>";
           }else{
                 echo "there is no records"; 
                }

         }//end try
        catch(PDOException $e){
              echo "ERROE:" .$e->getMessage();
             }
}//end function
?>