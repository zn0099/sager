<?php
session_start();
include_once("db.php"); 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

function addNewArticle($user_id, $conn) {
    $sub   = trim($_POST['subject']);
    $body  = trim($_POST['body']);
    $cat   = trim($_POST['category']);
    $date  = $_POST['Date_A'];

    $errors = [];
    if($sub === ''){
        $errors[] = "Subject is required.";
    } elseif(strlen($sub) < 3){
        $errors[] = "Subject must be at least 3 characters.";
    }
    if($body === ''){
        $errors[] = "Body is required.";
    } elseif(strlen($body) < 10){
        $errors[] = "Body must be at least 10 characters.";
    }
    if($cat === ''){
        $errors[] = "Category is required.";
    }
    if($date === ''){
        $errors[] = "Date is required.";
    }

    if(!empty($errors)){
        foreach($errors as $e){
            echo "<p style='color:red;'>$e</p>";
        }
        return;
    }

    $sql = "INSERT INTO article (subject, A_body, no_U, category, Date_A, no_E, page, activate, image)
            VALUES ('$sub', '$body', '$user_id', '$cat', '$date', NULL, NULL, 0, NULL)";
    $conn->exec($sql);

    $articleId = $conn->lastInsertId();

    // رفع عدّة صور اختيارية
    if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])){

        $uploadDir = 'uploads/';

        foreach($_FILES['images']['name'] as $index => $origName){

            if($_FILES['images']['error'][$index] == UPLOAD_ERR_OK){

                $tmpName  = $_FILES['images']['tmp_name'][$index];
                $ext      = pathinfo($origName, PATHINFO_EXTENSION);
                $newName  = 'art_'.$articleId.'_'.$index.'_'.time().'.'.$ext;
                $targetPath = $uploadDir.$newName;

                if(move_uploaded_file($tmpName, $targetPath)){
                    $sqlImg = "INSERT INTO images (no_A, File_IM) 
                               VALUES ($articleId, '$newName')";
                    $conn->exec($sqlImg);
                } else {
                    echo "<p style='color:red;'>ERROR: Cannot upload image ".htmlspecialchars($origName).".</p>";
                }
            }
        }
    }

    echo "<p>ARTICLE ADDED SUCCESSFULLY (Pending Approval)</p>";
}

function updateArticle($id, $conn) {
    $activate = $conn->query("SELECT activate FROM article WHERE no_A = $id")->fetchColumn();
    if($activate == 1){
        echo "<p style='color:red;'>You cannot edit an approved article.</p>";
        return;
    }

    $sub   = trim($_POST['subject']);
    $body  = trim($_POST['body']);
    $cat   = trim($_POST['category']);

    // تحديث بيانات المقال الأساسية
    $sql = "UPDATE article 
            SET subject='$sub', A_body='$body', category='$cat'
            WHERE no_A='$id'";
    $conn->exec($sql);

    // حذف الصور التي اختار المستخدم حذفها
    if(isset($_POST['delete_images']) && is_array($_POST['delete_images'])){
        $uploadDir = 'uploads/';
        foreach($_POST['delete_images'] as $imgName){
            $imgName = basename($imgName); // حماية بسيطة
            // حذف من الجدول
            $conn->exec("DELETE FROM images WHERE no_A='$id' AND File_IM='$imgName'");
            // حذف من المجلد
            $filePath = $uploadDir.$imgName;
            if(is_file($filePath)){
                @unlink($filePath);
            }
        }
    }

    // رفع صور جديدة (إن وُجدت)
    if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])){
        $uploadDir = 'uploads/';
        foreach($_FILES['images']['name'] as $index => $origName){
            if($_FILES['images']['error'][$index] == UPLOAD_ERR_OK){
                $tmpName  = $_FILES['images']['tmp_name'][$index];
                $ext      = pathinfo($origName, PATHINFO_EXTENSION);
                $newName  = 'art_'.$id.'_'.$index.'_'.time().'.'.$ext;
                $targetPath = $uploadDir.$newName;

                if(move_uploaded_file($tmpName, $targetPath)){
                    $sqlImg = "INSERT INTO images (no_A, File_IM) 
                               VALUES ($id, '$newName')";
                    $conn->exec($sqlImg);
                } else {
                    echo "<p style='color:red;'>ERROR: Cannot upload image ".htmlspecialchars($origName).".</p>";
                }
            }
        }
    }

    echo "<p>ARTICLE UPDATED SUCCESSFULLY</p>";
}

function deleteArticle($id, $conn) {
    $activate = $conn->query("SELECT activate FROM article WHERE no_A = $id")->fetchColumn();
    if($activate == 1){
        echo "<p style='color:red;'>You cannot delete an approved article.</p>";
        return;
    }

    $sql = "DELETE FROM article WHERE no_A='$id'";
    $conn->exec($sql);
    echo "<p>ARTICLE DELETED</p>";
}

if (isset($_POST['add'])) {
    addNewArticle($user_id, $conn);
    header("Location: udashbord.php");
    exit;
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    updateArticle($id, $conn);
}

if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    deleteArticle($id, $conn);
}

$filterSql = " AND activate = 0";
if (isset($_POST['show_approved'])) {
    $filterSql = " AND activate = 1";
} elseif (isset($_POST['show_all'])) {
    $filterSql = "";
}
if (isset($_POST['show_pending'])) {
    $filterSql = " AND activate = 0";
}
?>
<html>
<head>
    <meta charset="utf-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="top-links">
        <h2>Welcome <?php echo $user_name; ?></h2>
    </div>
    <hr>

    <form method="post">
        <input type="submit" name="new_article" value="Add New Article">
    </form>
    <hr>

    <div class="filter-box">
        <form method="post" style="margin-bottom:15px;">
            <input type="submit" name="show_all" value="All Articles">
            <input type="submit" name="show_approved" value="Approved Articles">
            <input type="submit" name="show_pending" value="Pending Articles">
        </form>
    </div>
    <hr>

<?php

if (isset($_POST['new_article'])) {
?>
    <h2>ADD NEW ARTICLE</h2>
    <form method="post" enctype="multipart/form-data" name="articleForm" onsubmit="return validateArticle();">
        <p>Subject</p>
        <input type="text" name="subject">

        <p>Body</p>
        <textarea name="body"></textarea>

        <p>Category</p>
        <input type="text" name="category">

        <p>Date</p>
        <input type="date" name="Date_A">

        <input type="file" name="images[]" multiple>

        <input type="submit" name="add" value="ADD ARTICLE">
    </form>
    <hr>
<?php
}

if (isset($_POST['edit'])) {
    $editId = $_POST['id'];

    $stmtEdit = $conn->query("SELECT * FROM article WHERE no_A = $editId AND no_U = '$user_id'");
    if($stmtEdit->rowCount() > 0){
        $editRow = $stmtEdit->fetch(PDO::FETCH_ASSOC);
        if($editRow['activate'] == 0){
            ?>
            <h2>Edit Article #<?php echo $editRow['no_A']; ?></h2>
            <form method="post" name="editForm" onsubmit="return validateEdit();" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $editRow['no_A']; ?>">

                <p>Subject</p>
                <input type="text" name="subject" value="<?php echo($editRow['subject']); ?>">

                <p>Body</p>
                <textarea name="body"><?php echo ($editRow['A_body']); ?></textarea>

                <p>Category</p>
                <input type="text" name="category" value="<?php echo ($editRow['category']); ?>">

                <p>Current Images:</p>
                <?php
                $aid = (int)$editRow['no_A'];
                $imgRes = $conn->query("SELECT File_IM FROM images WHERE no_A = $aid");
                $imgCount = 0;
                while($imgRow = $imgRes->fetch(PDO::FETCH_ASSOC)){
                    $imgName = $imgRow['File_IM'];
                    echo "<div style='display:inline-block; text-align:center; margin:5px;'>";
                    echo "<img src='uploads/".htmlspecialchars($imgName)."' style='max-width:100px; max-height:100px; display:block; margin-bottom:3px;'>";
                    echo "<label style='font-size:12px;'>
                            <input type='checkbox' name='delete_images[]' value='".htmlspecialchars($imgName)."'>
                            Delete
                          </label>";
                    echo "</div>";
                    $imgCount++;
                }
                if($imgCount == 0){
                    echo "<p>No images for this article.</p>";
                }
                ?>

                <p>Add New Images (optional):</p>
                <input type="file" name="images[]" multiple>

                <br><br>
                <input type="submit" name="update" value="UPDATE ARTICLE">
            </form>
            <hr>
            <?php
        } else {
            echo "<p style='color:red;'>This article is approved and cannot be edited.</p>";
        }
    }
}

$sql = "SELECT * FROM article WHERE no_U='$user_id' $filterSql";
$result = $conn->query($sql);
if ($result->rowCount() > 0) {
    echo "<h2>Your Articles</h2>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<div class='article'>";
        echo "ID: " . $row['no_A'] . "<br>";
        echo "Subject: " . $row['subject'] . "<br>";
        echo "Category: " . $row['category'] . "<br>";
        echo "Body: " . $row['A_body'] . "<br>";
        echo "Issue: " . ($row['no_E'] ?? 'Not assigned yet') . "<br>";
        echo "Page: " . ($row['page'] ?? 'Not set') . "<br>";
        echo "Activate: " . $row['activate'] . "<br>";

        //   صور 
        $aid = (int)$row['no_A'];
        $imgRes = $conn->query("SELECT File_IM FROM images WHERE no_A = $aid");
        $imgCount = 0;
        while($imgRow = $imgRes->fetch(PDO::FETCH_ASSOC)){
            echo "<img src='uploads/".htmlspecialchars($imgRow['File_IM'])."' style='max-width:100px; max-height:100px; margin:5px 5px;'>";
            $imgCount++;
        }
        if($imgCount == 0){
            echo "<br>No images for this article.<br>";
        }

        if ($row['activate'] == 0) {
            ?>
            <form method="post" style="margin-top:10px;">
                <input type="hidden" name="id" value="<?php echo $row['no_A']; ?>">
                <input type="submit" name="edit" value="Edit">
                <input type="submit" name="delete" value="Delete">
            </form>
            <?php
        } else {
            echo "<p>This article is approved and cannot be edited or deleted.</p>";
        }
        echo "</div>";
    }
} else {
    echo "<p>No articles found.</p>";
}
?>

</div>

<script type="text/javascript">
function validateArticle() {
    var form    = document.articleForm;
    var subject = form.subject.value.trim();
    var body    = form.body.value.trim();
    var cat     = form.category.value.trim();
    var date    = form.Date_A.value;

    if (subject === "") {
        alert("Subject is required");
        form.subject.focus();
        return false;
    }

    if (body === "") {
        alert("Body is required");
        form.body.focus();
        return false;
    }
    if (body.length < 10) {
        alert("Body must be at least 10 characters");
        form.body.focus();
        return false;
    }

    if (cat === "") {
        alert("Category is required");
        form.category.focus();
        return false;
    }

    if (date === "") {
        alert("Date is required");
        form.Date_A.focus();
        return false;
    }

    return true;
}

function validateEdit() {
    var form    = document.editForm;
    var subject = form.subject.value.trim();
    var body    = form.body.value.trim();
    var cat     = form.category.value.trim();

    if (subject === "") {
        alert("Subject is required");
        form.subject.focus();
        return false;
    }

    if (body === "") {
        alert("Body is required");
        form.body.focus();
        return false;
    }
    if (body.length < 10) {
        alert("Body must be at least 10 characters");
        form.body.focus();
        return false;
    }

    if (cat === "") {
        alert("Category is required");
        form.category.focus();
        return false;
    }

    return true;
}
</script>

</body>
</html>
