<?php
session_start();
require 'db.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Temel sorgu
$query = "
    SELECT g.* 
    FROM games g
    LEFT JOIN game_categories gc ON g.id = gc.game_id
    LEFT JOIN categories c ON gc.category_id = c.id
    WHERE 1=1
";
$params = [];

// Arama varsa ekle
if(!empty($search)) {
    $query .= " AND g.name LIKE ?";
    $params[] = "%$search%";
}

// Kategori filtresi varsa ekle
if(!empty($category)) {
    $query .= " AND c.name = ?";
    $params[] = $category;
}

$query .= " GROUP BY g.id";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$games = $stmt->fetchAll();

// Kategorileri al
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>13Games - Mağaza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <div class="store-header">
            <h2>Oyun Mağazası</h2>
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Oyun ara..." value="<?= htmlspecialchars($search) ?>">
                <select name="category">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $category == $cat['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Filtrele</button>
            </form>
        </div>

        <?php if(empty($games)): ?>
            <p class="no-results">Sonuç bulunamadı.</p>
        <?php else: ?>
            <div class="games">
                <?php foreach ($games as $game): ?>
                    <div class="game">
                        <img src="images/<?= htmlspecialchars($game['image_url']) ?>" alt="<?= htmlspecialchars($game['name']) ?>">
                        <div class="game-info">
                            <h3><?= htmlspecialchars($game['name']) ?></h3>
                            <p class="price"><?= htmlspecialchars($game['price']) ?> TL</p>
                            <a href="game.php?id=<?= $game['id'] ?>" class="btn">Detaylar</a>
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
