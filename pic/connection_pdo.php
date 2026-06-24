<?php
//ربط قاعدة بيانات الصفحة
$servername= 'mysql:host=localhost; dbname=student';
$username= 'root';
$password='';
$dbname='student';

try{//صار اتصال
$conn = new PDO($servername,$username, $password);//كونستراكتر للكلاس بي دي او
   $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//اي خطأ ممكن يصير بنتعامل مع ك استتناء اكسبشن
   echo "Connected successfully";//رسالة توضح ان صار اتصال بدون مشاكل

  }
catch(PDOException $e)
    {
     echo "Error :" . $e->getMessage();
     //die("Connection failed :" . $conn->connect_error);توقف الأكسبرشن
    }

?>
