<?php
session_start();
require 'db.php';

// Popüler oyunları al (en çok satılanlar)
$popularGames = $pdo->query("
    SELECT g.*, COUNT(l.game_id) as sales 
    FROM games g
    LEFT JOIN library l ON g.id = l.game_id
    GROUP BY g.id
    ORDER BY sales DESC
    LIMIT 4
")->fetchAll();

// Yeni çıkan oyunlar
$newGames = $pdo->query("
    SELECT * FROM games 
    ORDER BY release_date DESC 
    LIMIT 4
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>13Games - Ana Sayfa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <section class="hero">
            <div class="hero-content">
                <h2>Yeni ve Popüler Oyunları Keşfet</h2>
                <p>En iyi oyun deneyimi için 13Games'a hoş geldiniz!</p>
                <a href="store.php" class="btn">Mağazaya Gözat</a>
            </div>
        </section>

        <section class="section">
            <h2>Popüler Oyunlar</h2>
            <div class="games">
                <?php foreach ($popularGames as $game): ?>
                    <div class="game">
                        <img src="images/<?= $game['image_url'] ?>" alt="<?= $game['name'] ?>">
                        <div class="game-info">
                            <h3><?= $game['name'] ?></h3>
                            <p>Fiyat: <?= $game['price'] ?> TL</p>
                            <a href="game.php?id=<?= $game['id'] ?>" class="btn">Detaylar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section">
            <h2>Yeni Çıkanlar</h2>
            <div class="games">
                <?php foreach ($newGames as $game): ?>
                    <div class="game">
                        <img src="images/<?= $game['image_url'] ?>" alt="<?= $game['name'] ?>">
                        <div class="game-info">
                            <h3><?= $game['name'] ?></h3>
                            <p>Fiyat: <?= $game['price'] ?> TL</p>
                            <a href="game.php?id=<?= $game['id'] ?>" class="btn">Detaylar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 13Games - Tüm hakları saklıdır</p>
        </div>
    </footer>
</body>
</html>