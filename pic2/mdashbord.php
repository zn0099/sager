<?php
session_start();
include_once("db.php");

if(!isset($_SESSION['editor_id'])){
    header("Location: login.php");
    exit;
}
$editor_name = $_SESSION['editor_name'];

function setSection() {
    if(isset($_POST['show_pending']))   return 'pending';
    if(isset($_POST['show_approved']))  return 'approved';
    if(isset($_POST['show_issues']))    return 'issues';
    return 'pending';
}

function getPendingArticles($conn){
    $sql = "SELECT article.*, users.name_u 
            FROM article 
            JOIN users ON article.no_U = users.no_U 
            WHERE activate = 0 
            ORDER BY no_A DESC";
    return $conn->query($sql);
}

function getApprovedUnassignedArticles($conn){
    $sql = "SELECT article.*, users.name_u 
            FROM article 
            JOIN users ON article.no_U = users.no_U 
            WHERE activate = 1 AND no_E IS NULL 
            ORDER BY no_A ASC";
    return $conn->query($sql);
}

function getArticlesByIssue($issue, $conn){
    $sql = "SELECT article.*, users.name_u 
            FROM article 
            JOIN users ON article.no_U = users.no_U 
            WHERE activate = 1 AND no_E = $issue 
            ORDER BY page, no_A";
    return $conn->query($sql);
}

function approveArticle($id, $conn) {
    $conn->exec("UPDATE article SET activate = 1 WHERE no_A = $id");
    echo "<p style='color:green;'>ARTICLE APPROVED</p>";
}

function rejectArticle($id, $conn) {
    $conn->exec("DELETE FROM article WHERE no_A = $id");
    echo "<p>ARTICLE REJECTED AND DELETED FROM DATABASE</p>";
}

function getNextIssueNumber($conn){
    $max = $conn->query("SELECT MAX(E_no) FROM info")->fetchColumn();
    if($max === null){
        return 1;
    }
    return ((int)$max) + 1;
}

function publishNewIssueFromCurrentBatch($conn){
    if(!isset($_POST['page_choice']) || !is_array($_POST['page_choice'])){
        echo "<p style='color:red;'>You must choose a page for each article before publishing.</p>";
        return;
    }

    $page_choice = $_POST['page_choice']; // [no_A => page]

    $res = getApprovedUnassignedArticles($conn);
    if($res->rowCount() == 0){
        echo "<p style='color:red;'>No approved articles in current batch.</p>";
        return;
    }

    $articles = [];
    while($row = $res->fetch(PDO::FETCH_ASSOC)){
        $articles[] = $row;
    }

    if(count($articles) != 12){
        echo "<p style='color:red;'>Current batch must have exactly 12 approved articles to publish (now: ".count($articles).").</p>";
        return;
    }

    $pageCounts = [1=>0,2=>0,3=>0,4=>0,5=>0,6=>0];

    foreach($articles as $art){
        $id = $art['no_A'];
        if(!isset($page_choice[$id]) || $page_choice[$id] < 1 || $page_choice[$id] > 6){
            echo "<p style='color:red;'>You must choose a valid page (1..6) for article ID $id.</p>";
            return;
        }
        $p = (int)$page_choice[$id];
        $pageCounts[$p]++;
    }

    for($p=1; $p<=6; $p++){
        if($pageCounts[$p] != 2){
            echo "<p style='color:red;'>Each page must have exactly 2 articles. Page $p now has ".$pageCounts[$p]." articles.</p>";
            return;
        }
    }

    $editorId = $_SESSION['editor_id'] ?? null;
    $today    = date('Y-m-d');
    $nextIssue = getNextIssueNumber($conn);
    $conn->exec("INSERT INTO info (E_no, U_No, date_I)
                 VALUES ($nextIssue, ".(int)$editorId.", '$today')");

    foreach($articles as $art){
        $id = $art['no_A'];
        $p  = (int)$page_choice[$id];
        $conn->exec("UPDATE article 
                     SET no_E = $nextIssue, page = $p 
                     WHERE no_A = $id");
    }

    echo "<p style='color:green;'>Magazine #".$nextIssue." has been published (6 pages × 2 articles) on $today.</p>";
    echo "<p><a href='magazine.php?issue=".$nextIssue."' target='_blank'>Open Magazine #".$nextIssue."</a></p>";
}

function listIssues($conn){
    $res = $conn->query("SELECT * FROM info ORDER BY E_no ASC");
    if($res->rowCount() == 0){
        echo "<p>No issues published yet.</p>";
        return;
    }
    while($row = $res->fetch(PDO::FETCH_ASSOC)){
        echo "<p>Issue #".$row['E_no']." - Date: ".$row['date_I']." - Editor ID: ".$row['U_No']." 
              <a href='magazine.php?issue=".$row['E_no']."' target='_blank'>View</a></p>";
    }
}

if(isset($_POST['activate'])) {
    approveArticle($_POST['id'], $conn);
}

if(isset($_POST['reject'])) {
    rejectArticle($_POST['id'], $conn);
}

if(isset($_POST['publish_current_batch'])) {
    publishNewIssueFromCurrentBatch($conn);
}

// وضع خاص لتهيئة الإصدار الجديد (شاشة اختيار الصفحات)
$issue_builder_mode = false;
if(isset($_POST['start_issue_builder'])){
    $issue_builder_mode = true;
}

$section = setSection();

?>
<html>
<head>
    <meta charset="utf-8">
    <title>Editor Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="top-links">
        <h2>Welcome Editor: <?php echo $editor_name; ?></h2>
        <!-- يمكنك إضافة Logout هنا عند الحاجة -->
    </div>

    <hr>

    <div class="filter-box">
        <form method="post">
            <input type="submit" name="show_pending" value="Pending Articles">
            <input type="submit" name="show_approved" value="Approved Articles">
            <input type="submit" name="show_issues" value="Published Issues">
        </form>
    </div>

<?php
// إذا لم نكن في وضع بناء الإصدار، نعرض الأقسام العادية
if(!$issue_builder_mode){

    if($section == 'pending'){
        echo "<h2>Pending Articles (Requests)</h2>";
        $pending = getPendingArticles($conn);
        if($pending->rowCount() > 0){
            while($row = $pending->fetch(PDO::FETCH_ASSOC)){
                echo "<div class='article'>";
                echo "ID: ".$row['no_A']."<br>";
                echo "Subject: ".$row['subject']."<br>";
                echo "Author: ".$row['name_u']."<br>";
                echo "Category: ".$row['category']."<br>";
                echo "Body: ".$row['A_body']."<br>";
                echo "Date: ".$row['Date_A']."<br>";

                // عرض الصور
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

                echo "<form method='post' style='margin-top:8px;'>";
                echo "<input type='hidden' name='id' value='".$row['no_A']."'>";
                echo "<input type='submit' name='activate' value='APPROVE'>";
                echo "<input type='submit' name='reject' value='REJECT'>";
                echo "</form>";

                echo "</div>";
            }
        } else {
            echo "<p>No pending articles.</p>";
        }

        // زر إصدار جديد أسفل المعلّقة والمقبولة
        echo "<hr>";
        echo "<form method='post' style='text-align:center; margin-top:10px;'>
                <button type='submit' name='start_issue_builder'>إصدار جديد</button>
              </form>";
    }

    if($section == 'approved'){
        echo "<h2>Approved Articles (View Only)</h2>";
        $approved = getApprovedUnassignedArticles($conn);
        if($approved->rowCount() > 0){
            while($row = $approved->fetch(PDO::FETCH_ASSOC)){
                echo "<div class='article'>";
                echo "ID: ".$row['no_A']."<br>";
                echo "Subject: ".$row['subject']."<br>";
                echo "Author: ".$row['name_u']."<br>";
                echo "Category: ".$row['category']."<br>";
                echo "Body: ".$row['A_body']."<br>";

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

                echo "</div>";
            }
        } else {
            echo "<p>No approved articles in current batch.</p>";
        }

        // زر إصدار جديد أسفل هذا القسم أيضًا
        echo "<hr>";
        echo "<form method='post' style='text-align:center; margin-top:10px;'>
                <button type='submit' name='start_issue_builder'>إصدار جديد</button>
              </form>";
    }

    if($section == 'issues'){
        echo "<h2>Published Issues</h2>";
        listIssues($conn);
    }

} else {
    // وضع بناء الإصدار الجديد: اختيار الصفحات لكل مقال مقبول
    echo "<h2>إصدار جديد - توزيع المقالات المقبولة على الصفحات</h2>";
    echo "<p>اختر رقم صفحة لكل مقال (1..6) ثم اضغط زر إصدار.</p>";

    echo "<form method='post'>";

    $approved = getApprovedUnassignedArticles($conn);
    if($approved->rowCount() > 0){
        while($row = $approved->fetch(PDO::FETCH_ASSOC)){
            echo "<div class='article'>";
            echo "ID: ".$row['no_A']."<br>";
            echo "Subject: ".$row['subject']."<br>";
            echo "Author: ".$row['name_u']."<br>";
            echo "Category: ".$row['category']."<br>";
            echo "Body: ".$row['A_body']."<br>";

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

            $id = $row['no_A'];

            echo "<p>Choose page:</p>";
            for($p=1; $p<=6; $p++){
                echo "<label>
                        <input type='radio' name='page_choice[".$id."]' value='".$p."'> Page ".$p."
                      </label> ";
            }

            echo "</div>";
        }

        echo "<input type='submit' name='publish_current_batch' value='PUBLISH new '>";

    } else {
        echo "<p>No approved articles in current batch.</p>";
    }

    echo "</form>";
}
?>

</div>
</body>
</html>
