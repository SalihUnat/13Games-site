<?php
require 'auth.php';

// Sadece adminler erişebilir
if (!$_SESSION['user']['is_admin']) {
    header("Location: index.php");
    exit();
}

// Oyun ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_game'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category_ids = $_POST['categories'] ?? [];

    // Resim yükleme
    if (!empty($_FILES['image']['name'])) {
        $image_name = basename($_FILES['image']['name']);
        $image_tmp = $_FILES['image']['tmp_name'];
        $target_dir = "images/";
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($image_tmp, $target_file)) {
            $_SESSION['error'] = "Resim yüklenirken hata oluştu!";
            header("Location: admin.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Resim dosyası seçmelisiniz!";
        header("Location: admin.php");
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO games (name, price, description, image_url) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $price, $description, $image_name]);
    $game_id = $pdo->lastInsertId();

    foreach ($category_ids as $category_id) {
        $stmt = $pdo->prepare("INSERT INTO game_categories (game_id, category_id) VALUES (?, ?)");
        $stmt->execute([$game_id, $category_id]);
    }

    $_SESSION['success'] = "Oyun başarıyla eklendi!";
    header("Location: admin.php");
    exit();
}

// Oyun silme
if (isset($_GET['delete_game'])) {
    $game_id = $_GET['delete_game'];

    $stmt = $pdo->prepare("SELECT price, image_url FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();

    if ($game) {
        $price = $game['price'];
        $image_url = $game['image_url'];

        $image_path = __DIR__ . "/images/" . $image_url;
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        $stmt = $pdo->prepare("SELECT user_id FROM library WHERE game_id = ?");
        $stmt->execute([$game_id]);
        $buyers = $stmt->fetchAll();

        foreach ($buyers as $buyer) {
            $stmtUpdate = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmtUpdate->execute([$price, $buyer['user_id']]);
        }

        $stmtDeleteLib = $pdo->prepare("DELETE FROM library WHERE game_id = ?");
        $stmtDeleteLib->execute([$game_id]);

        $stmtDeleteGC = $pdo->prepare("DELETE FROM game_categories WHERE game_id = ?");
        $stmtDeleteGC->execute([$game_id]);

        $stmtDeleteGame = $pdo->prepare("DELETE FROM games WHERE id = ?");
        $stmtDeleteGame->execute([$game_id]);

        if (isset($_SESSION['user'])) {
            foreach ($buyers as $buyer) {
                if ($_SESSION['user']['id'] == $buyer['user_id']) {
                    $_SESSION['user']['wallet_balance'] += $price;
                }
            }
        }

        $_SESSION['success'] = "Oyun başarıyla silindi, resmi kaldırıldı ve satın alanlara para iadesi yapıldı!";
    } else {
        $_SESSION['error'] = "Oyun bulunamadı.";
    }

    header("Location: admin.php");
    exit();
}

// Yorum silme
if (isset($_GET['delete_comment'])) {
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$_GET['delete_comment']]);

    $_SESSION['success'] = "Yorum başarıyla silindi!";
    header("Location: admin.php");
    exit();
}

// Kütüphaneden oyun silme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_library_game'])) {
    $user_id = $_POST['user_id'] ?? null;
    $game_id = $_POST['game_id'] ?? null;

    if ($user_id && $game_id) {
        $stmt = $pdo->prepare("SELECT price FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();

        if ($game) {
            $price = $game['price'];

            $stmt = $pdo->prepare("DELETE FROM library WHERE user_id = ? AND game_id = ?");
            $stmt->execute([$user_id, $game_id]);

            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$price, $user_id]);

            if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $user_id) {
                $_SESSION['user']['wallet_balance'] += $price;
            }

            $_SESSION['success'] = "Kütüphaneden oyun silindi ve fiyat iade edildi.";
            header("Location: admin.php");
            exit();
        } else {
            $_SESSION['error'] = "Oyun bulunamadı.";
        }
    } else {
        $_SESSION['error'] = "Eksik bilgi.";
    }
}

$games = $pdo->query("SELECT * FROM games")->fetchAll();
$comments = $pdo->query("SELECT c.*, u.username, g.name as game_name FROM comments c JOIN users u ON c.user_id = u.id JOIN games g ON c.game_id = g.id ORDER BY c.created_at DESC")->fetchAll();
$libraries = $pdo->query("SELECT l.id AS lib_id, u.id AS user_id, u.username, g.id AS game_id, g.name AS game_name FROM library l JOIN users u ON l.user_id = u.id JOIN games g ON l.game_id = g.id ORDER BY u.username, g.name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>13Games - Admin Paneli</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
<?php if(isset($_SESSION['success'])): ?><div class="success"><?=$_SESSION['success']?></div><?php unset($_SESSION['success']); endif; ?>
<?php if(isset($_SESSION['error'])): ?><div class="error"><?=$_SESSION['error']?></div><?php unset($_SESSION['error']); endif; ?>

<section class="admin-section">
    <h2>Oyun Ekle</h2>
    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <div class="form-group">
            <label for="name">Oyun Adı</label>
            <input type="text" name="name" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        </div>
        <div class="form-group">
            <label for="price">Fiyat</label>
            <input type="number" name="price" step="0.01" required value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>">
        </div>
        <div class="form-group">
            <label for="categories">Kategoriler</label>
            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 8px;">
                <?php foreach ($categories as $cat): ?>
                    <label class="category-checkbox">
                        <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                        <?= (isset($_POST['categories']) && in_array($cat['id'], $_POST['categories'])) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>

                <?php endforeach; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Açıklama</label>
            <textarea name="description" required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
        </div>
        <div class="form-group">
            <label for="image">Resim</label>
            <input type="file" name="image" accept="image/*" required>
        </div>
        <button type="submit" name="add_game" class="btn">Oyun Ekle</button>
    </form>
</section>

<section class="admin-section">
    <h2>Oyunlar</h2>
    <div class="admin-games">
        <?php foreach ($games as $game): ?>
            <?php
            $stmt = $pdo->prepare("SELECT name FROM categories c JOIN game_categories gc ON c.id = gc.category_id WHERE gc.game_id = ?");
            $stmt->execute([$game['id']]);
            $game_cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
            ?>
            <div class="admin-game">
                <img src="images/<?=htmlspecialchars($game['image_url'])?>" width="100" alt="<?=htmlspecialchars($game['name'])?>">
                <div class="game-details">
                    <h3><?=htmlspecialchars($game['name'])?></h3>
                    <p><?=htmlspecialchars($game['price'])?> TL - <?=implode(', ', $game_cats)?></p>
                </div>
                <div class="game-actions">
                    <a href="game.php?id=<?=$game['id']?>" class="btn">Görüntüle</a>
                    <a href="admin.php?delete_game=<?=$game['id']?>" class="btn danger" onclick="return confirm('Bu oyunu silmek istediğinize emin misiniz?')">Sil</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="admin-section">
    <h2>Yorumlar</h2>
    <div class="admin-comments">
        <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <div class="comment-header">
                    <span><strong><?=htmlspecialchars($comment['username'])?></strong> - <?=htmlspecialchars($comment['game_name'])?></span>
                    <span><?=date('d.m.Y H:i', strtotime($comment['created_at']))?></span>
                </div>
                <p><?=nl2br(htmlspecialchars($comment['comment']))?></p>
                <div class="comment-actions">
                    <a href="admin.php?delete_comment=<?=$comment['id']?>" class="btn danger" onclick="return confirm('Bu yorumu silmek istediğinize emin misiniz?')">Sil</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="admin-section">
    <h2>Kullanıcı Kütüphaneleri</h2>
    <?php if (count($libraries) === 0): ?>
        <p>Henüz satın alınmış oyun yok.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Kullanıcı</th><th>Oyun</th><th>İşlem</th></tr>
            </thead>
            <tbody>
                <?php foreach ($libraries as $lib): ?>
                    <tr>
                        <td><?=htmlspecialchars($lib['username'])?></td>
                        <td><?=htmlspecialchars($lib['game_name'])?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Bu oyunu kullanıcının kütüphanesinden silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="user_id" value="<?=$lib['user_id']?>">
                                <input type="hidden" name="game_id" value="<?=$lib['game_id']?>">
                                <button type="submit" name="delete_library_game" class="btn danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
</main>
<footer><div class="container"><p>&copy; 2025 13Games  - Tüm hakları saklıdır</p></div></footer>
</body>
</html>
