<?php 
include_once("connection_pdo.php");

function diplay_form($conn)
{//تكون في الفورم?>
<form method="post" action="#">
<table border='1'>
   <tr><td>id</td><td><input type="text" name="id"></td></tr>
   <br>

   <tr><td>name</td><td><input type="text" name="name"/></td></tr>
   <br/>
     
    <tr><td>Gender</td><td><input type="radio" name="gender" value="male"/>male
    <br/><input type="radio" name="gender" value="female"/>female</td></tr>
    <br/>

    <tr><td>nation</td><td><input list="n" name="nation"></td></tr>
        <datalist id="n">
        <?php 
        try{
            $sql="SELECT distinct nation FROM info";
            $rows=$conn->query($sql);//ترجع في البيانات كأنهم اوبجكت
            $all_rows=$rows->fetchALL();
            foreach($all_rows as $row)
            {//تقدر تعاملها ك اوبجكت او مصفوفة هني مصفوفة?>
             <option value="<?php echo $row['nation']; ?>"/>
  <?php        }

            }
catch(PDOException $e)
{
   echo "Error:" . $e->getMessage();
}?>
    </datalist></td></tr>
    <tr><td align='center' colspan='2' ><input name="ok" type="submit"/></td></tr>
    </table>
 </form>
<?php 
}//end function


function insert($conn)
{
//extract($_POST);تعطيها مصفوفة وتفكها ك متغيرات 
$id=$_POST['id'];
$name=$_POST['name'];
$gender=$_POST['gender'];
$n=$_POST['nation'];

try{  

   $sql="INSERT INTO info  values ($id ,'$name','$gender','$n')";
   $conn->exec($sql);
   echo "Record insert successfully";
}
catch(PDOException $e)
  {
    echo $sql ."<br>" .$e->getMessage();
  }
}
?>
<!DOCTYPY html>
<html >
<head >
<meta charset="utf-8">
</head>
<body>
<?php 
if(!isset($_POST['ok'])){
diplay_form($conn);
}else{
//validation
insert($conn);
}
?>
</body>
</html>