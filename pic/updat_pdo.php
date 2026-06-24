<?php 
include_once("connection_pdo.php");
include_once("fun_search_pdo.php");


function display_form_update($v,$conn){?>
<form action="#" method="post">
id:<?php echo $v['id'];?><input type="hidden" name="id" value="<?php echo $v['id']?>"></br>
name:<input type="text" name="name" value="<?php echo $v['name'];?>"></br>
<input type="radio" name="gender" value="male" <?php if($v['gender']=='male') echo "checked='checked'";?>>male</br>
<input type="radio" name="gender" value="female" <?php if($v['gender']=='female') echo "checked='checked'";?>>female</br>
nation:<input list="n" name="nation" value="<?php echo $v['nation']; ?>">
       <datalist id="n">
       <?php fill_datalist_nationality($conn);?>
       </datalist>
       <input  type="submit" name="update" value="update">
</form>
<?php }//end function

function get_student_info($conn){
$id=$_POST['id'];
try{
   $query="SELECT * FROM info where id=$id ";
   $rows=$conn->query($query);
   $row=$rows->fetch(PDO::FETCH_ASSOC);
    }
catch(PDOException $e) 
{
echo "Error:".$e->getMessage();
}
return $row ;
}//end function 
?>
<html>
<head>
</head>
<body>
<?php
if($_SERVER["REQUEST_METHOD"]=="GET")//جاي اول مرة ف بتكتب علي شن تبحث
{
     display_form_search($conn);

}elseif(isset($_POST['button_nation']))//يعرض في نتيجة البحث ونختار منهم
{
     search_view_form_radio($conn);

}elseif(isset($_POST['button_getid']))//نكتب البيانات الجديدة
{
     $v=get_student_info($conn);//بيانات الطالب كلهم
     display_form_update($v,$conn);//تطبع في بيانات الطالب مع امكانية تغيرهم  

}elseif(isset($_POST['update']))//تحديث
{
      extract($_POST);//تفك المصفوفة لمتغيرات

      try{
           $query="UPDATE info SET name='$name', gender='$gender', nation='$nation' where id=$id";
           $conn->exec($query);
           echo "Record updat successfully";
         }catch(PDOException $e){
           echo $query ."</br>" . $e->getMessage();
           echo"dddd";
         }
}        
 ?>
<body>
</html>

