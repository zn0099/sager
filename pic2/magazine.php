<?php
session_start();
include_once("db.php");

if(isset($_GET['issue']) && $_GET['issue'] !== ''){
    $issue = (int)$_GET['issue'];
} else {
    $stmtLast = $conn->query("SELECT MAX(E_no) FROM info");
    $issue = (int)$stmtLast->fetchColumn();
    if($issue == 0) $issue = 1;
}

$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($currentPage < 1 || $currentPage > 6) $currentPage = 1;

function getIssueInfo($issue, $conn){
    $sql = "SELECT info.*, users.name_u 
            FROM info 
            LEFT JOIN users ON info.U_No = users.no_U
            WHERE info.E_no = $issue";
    return $conn->query($sql)->fetch(PDO::FETCH_ASSOC);
}

function getIssueArticlesByPage($issue, $page, $conn){
    $sql = "SELECT article.*, users.name_u 
            FROM article 
            JOIN users ON article.no_U = users.no_U
            WHERE article.no_E = :issue 
              AND article.activate = 1
              AND article.page = :pg
            ORDER BY article.no_A
            LIMIT 2";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':issue' => $issue, ':pg' => $page]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchArticles($term, $conn){
    $sql = "
        SELECT a.*, u.name_u, i.E_no
        FROM article a
        JOIN users u ON a.no_U = u.no_U
        JOIN info i  ON a.no_E = i.E_no   
        WHERE a.activate = 1
          AND (a.subject LIKE :q OR a.category LIKE :q)
        ORDER BY a.no_E DESC, a.page, a.no_A
    ";
    $stmt = $conn->prepare($sql);
    $like = '%'.$term.'%';
    $stmt->execute([':q' => $like]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$info = getIssueInfo($issue, $conn);
$isPublished = $info ? true : false;

$articles = [];
if($isPublished){
    $articles = getIssueArticlesByPage($issue, $currentPage, $conn);
}

$searchResults = [];
$searchTerm = '';
if(isset($_GET['search']) && trim($_GET['search']) !== ''){
    $searchTerm = trim($_GET['search']);
    $searchResults = searchArticles($searchTerm, $conn);
}
?>
<html>
<head>
<meta charset="utf-8">
<title>Magazine #<?php echo $issue; ?> - Page <?php echo $currentPage; ?></title>

<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <!-- زر تسجيل الدخول -->
    <div class="login-bar">
        <a href="login.php" class="btn-login">Login</a>
    </div>

    <div class="search-box">
        <form method="get" style="margin-bottom:10px;">
            <label>Issue Number: </label>
            <input type="number" name="issue" min="1" value="<?php echo $issue; ?>">
            <input type="hidden" name="page" value="1">
            <button type="submit">Show Issue</button>
        </form>

        <form method="get">
            <input type="hidden" name="issue" value="<?php echo $issue; ?>">
            <input type="hidden" name="page" value="<?php echo $currentPage; ?>">
            <label>Search Articles (title/category): </label>
            <input type="text" name="search" value="<?php echo($searchTerm); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="magazine-header">
        <h1>Magazine #<?php echo $issue; ?> - Page <?php echo $currentPage; ?></h1>
        <?php if($isPublished): ?>
            <p>Issue Date: <?php echo $info['date_I']; ?> |
            Editor: <?php echo htmlspecialchars($info['name_u'] ?? ''); ?></p>
        <?php else: ?>
            <p style="color:red;">This issue has not been published yet.</p>
        <?php endif; ?>
    </div>

    <?php if($isPublished): ?>
    <div class="page">
        <h2>Page <?php echo $currentPage; ?></h2>
        <?php
        if(count($articles) == 0){
            echo "<p>No articles on this page.</p>";
        } else {
            foreach($articles as $art){
                echo "<div class='article'>";
                echo "<div class='subject'>".$art['subject']."</div>";
                echo "<div class='author'>By: ".$art['name_u']."</div>";
                echo "<div class='category'>Category: ".$art['category']."</div>";

                // صور المقال
                $aid = (int)$art['no_A'];
                $imgRes = $conn->query("SELECT File_IM FROM images WHERE no_A = $aid");
                while($imgRow = $imgRes->fetch(PDO::FETCH_ASSOC)){
                    echo "<img src='uploads/".htmlspecialchars($imgRow['File_IM'])."' style='max-width:120px; max-height:120px; display:inline-block; margin:5px 5px;'>";
                }

                echo "<div class='body'>".$art['A_body']."</div>";
                echo "</div>";
            }
        }
        ?>
    </div>

    <div class="index">
        <?php for($p = 1; $p <= 6; $p++): ?>
            <?php if($p == $currentPage): ?>
                <span class="active"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="?issue=<?php echo $issue; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php if($searchTerm !== ''): ?>
    <div class="search-results">
        <h2>Search results for: "<?php echo($searchTerm); ?>"</h2>
        <?php
        if(count($searchResults) == 0){
            echo "<p>No articles found.</p>";
        } else {
            foreach($searchResults as $r){
                echo "<div class='article'>";
                echo "<div class='subject'>".$r['subject']."</div>";
                echo "<div class='author'>By: ".$r['name_u']."</div>";
                echo "<div class='category'>".$r['category']."</div>";

                // صور المقال 
                $aid = (int)$r['no_A'];
                $imgRes = $conn->query("SELECT File_IM FROM images WHERE no_A = $aid");
                while($imgRow = $imgRes->fetch(PDO::FETCH_ASSOC)){
                    echo "<img src='uploads/".htmlspecialchars($imgRow['File_IM'])."' style='max-width:120px; max-height:120px; display:inline-block; margin:5px 5px;'>";
                }

                echo "<div class='body'>".$r['A_body']."</div>";
                echo "<p>Issue: ".$r['E_no']." | Page: ".$r['page']."</p>";
                echo "</div>";
            }
        }
        ?>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
