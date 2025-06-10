<?php
session_start();
require 'db.php';

// Oyun bilgilerini al
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$_GET['id']]);
$game = $stmt->fetch();

if(!$game) {
    header("Location: index.php");
    exit();
}

// Oyun kategorilerini al
$stmt = $pdo->prepare("
    SELECT c.name 
    FROM categories c
    JOIN game_categories gc ON c.id = gc.category_id
    WHERE gc.game_id = ?
");
$stmt->execute([$_GET['id']]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Yorumları al
$stmt = $pdo->prepare("
    SELECT c.*, u.username 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.game_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$_GET['id']]);
$comments = $stmt->fetchAll();

// Kullanıcı bu oyuna sahip mi?
$owned = false;
if(isset($_SESSION['user'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM library WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$_SESSION['user']['id'], $_GET['id']]);
    $owned = $stmt->fetchColumn() > 0;
}

// Yorum ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user']) && $owned) {
    $comment = $_POST['comment'];
    
    $stmt = $pdo->prepare("
        INSERT INTO comments (user_id, game_id, comment) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user']['id'],
        $_GET['id'],
        $comment
    ]);
    
    header("Location: game.php?id=" . $_GET['id'] . "#comments");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>GameHub - <?= htmlspecialchars($game['name']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>

<main class="container">
    <div class="game-detail">
        <img src="images/<?= htmlspecialchars($game['image_url']) ?>" alt="<?= htmlspecialchars($game['name']) ?>" class="game-image">
        <div class="game-info">
            <h2><?= htmlspecialchars($game['name']) ?></h2>
            <p class="price"><?= htmlspecialchars($game['price']) ?> TL</p>
            <p class="category">
                Kategoriler:
                <?php if (!empty($categories)): ?>
                    <?= htmlspecialchars(implode(', ', $categories)) ?>
                <?php else: ?>
                    Yok
                <?php endif; ?>
            </p>
            <p class="description"><?= nl2br(htmlspecialchars($game['description'])) ?></p>

            <?php if(isset($_SESSION['user'])): ?>
                <?php if($owned): ?>
                    <p class="owned">✔️ Bu oyun kütüphanenizde bulunuyor</p>
                <?php else: ?>
                    <a href="purchase.php?game_id=<?= $game['id'] ?>" class="btn">Satın Al</a>
                <?php endif; ?>
            <?php else: ?>
                <p>Satın almak için <a href="login.php">giriş yapmalısınız</a></p>
            <?php endif; ?>
        </div>
    </div>

    <section class="comments" id="comments">
        <h3>Yorumlar</h3>

        <?php if(isset($_SESSION['user']) && $owned): ?>
            <div class="comment-form">
                <form method="POST">
                    <div class="form-group">
                        <label for="comment">Yorumunuz</label>
                        <textarea id="comment" name="comment" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn">Yorum Gönder</button>
                </form>
            </div>
        <?php elseif(isset($_SESSION['user']) && !$owned): ?>
            <p>Yorum yapabilmek için bu oyuna sahip olmalısınız.</p>
        <?php else: ?>
            <p>Yorum yapmak için <a href="login.php">giriş yapmalısınız</a></p>
        <?php endif; ?>

        <?php foreach($comments as $comment): ?>
            <div class="comment">
                <div class="comment-header">
                    <span class="comment-author"><?= htmlspecialchars($comment['username']) ?></span>
                    <span class="comment-date"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></span>
                </div>
                <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
            </div>
        <?php endforeach; ?>

        <?php if(empty($comments)): ?>
            <p>Henüz yorum yapılmamış.</p>
        <?php endif; ?>
    </section>
</main>

<footer>
    <div class="container">
        <p>&copy; 2023 GameHub - Tüm hakları saklıdır</p>
    </div>
</footer>
</body>
</html>
