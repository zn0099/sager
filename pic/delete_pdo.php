<?php 
include_once("connection_pdo.php");
include_once("fun_search_pdo.php");

function delete($conn){
      $std=$_POST['id'];
try{
      foreach($std as $value){
        $sql="delete from info where id=$value";
        $conn->exec($sql);
        echo "Record deleted successfully";
      }
}
catch(PDOException $e)
     {
        echo $sql . "<br>" .$e->getMessage();
     }
$conn=null;
}?>

<!DOCTYPY html>
<html >
<head >
</head>
<body>
<?php 
  if(isset($_POST['nation']))
  {
    search_view_form_checkbox($conn);
  }elseif(isset($_POST['del']))
   {
     delete($conn);
   }else{
    display_form_search($conn);
   }
?>
</body>
</html>





