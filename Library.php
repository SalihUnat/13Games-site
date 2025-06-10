<?php
session_start();
require 'auth.php';

$stmt = $pdo->prepare("
    SELECT g.*, l.purchased_at 
    FROM games g
    JOIN library l ON g.id = l.game_id
    WHERE l.user_id = ?
    ORDER BY l.purchased_at DESC
");
$stmt->execute([$_SESSION['user']['id']]);
$games = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>13Games - Kütüphanem</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
   <?php include 'header.php'; ?>

    <main class="container">
        <h2>Kütüphanem</h2>
        
        <?php if(empty($games)): ?>
            <div class="empty-library">
                <p>Kütüphanenizde henüz oyun bulunmamaktadır.</p>
                <a href="store.php" class="btn">Mağazaya Gözat</a>
            </div>
        <?php else: ?>
            <div class="games">
                <?php foreach ($games as $game): ?>
                    <div class="game">
                        <img src="images/<?= $game['image_url'] ?>" alt="<?= $game['name'] ?>">
                        <div class="game-info">
                            <h3><?= $game['name'] ?></h3>
                            <p>Satın Alma Tarihi: <?= date('d.m.Y', strtotime($game['purchased_at'])) ?></p>
                            <div class="game-actions">
                                <a href="game.php?id=<?= $game['id'] ?>" class="btn">Oyna</a>
                                <a href="game.php?id=<?= $game['id'] ?>#comments" class="btn">Yorum Yap</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 13Games - Tüm hakları saklıdır</p>
        </div>
    </footer>
</body>
</html>