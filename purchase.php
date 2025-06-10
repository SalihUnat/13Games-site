<?php
require 'auth.php';

if(!isset($_GET['game_id'])) {
    header("Location: store.php");
    exit();
}

$game_id = $_GET['game_id'];

// Kullanıcının zaten bu oyuna sahip olup olmadığını kontrol et
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM library 
    WHERE user_id = ? AND game_id = ?
");
$stmt->execute([$_SESSION['user']['id'], $game_id]);
$already_owned = $stmt->fetchColumn();

if($already_owned) {
    $_SESSION['error'] = "Bu oyun zaten kütüphanenizde bulunuyor.";
    header("Location: game.php?id=" . $game_id);
    exit();
}

// Oyunun fiyatını çek
$stmt = $pdo->prepare("SELECT price FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if(!$game) {
    $_SESSION['error'] = "Oyun bulunamadı.";
    header("Location: store.php");
    exit();
}

$price = $game['price'];
$balance = $_SESSION['user']['wallet_balance'];

// Bakiye kontrolü
if($balance < $price) {
    $_SESSION['error'] = "Yeterli bakiyeniz yok.";
    header("Location: game.php?id=" . $game_id);
    exit();
}

// Satın alma işlemi
$pdo->beginTransaction();

try {
    // Bakiyeden düş
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
    $stmt->execute([$price, $_SESSION['user']['id']]);

    // Kütüphaneye ekle
    $stmt = $pdo->prepare("INSERT INTO library (user_id, game_id) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user']['id'], $game_id]);

    $pdo->commit();

    // Sessiondaki bakiye güncelle
    $_SESSION['user']['wallet_balance'] -= $price;

    $_SESSION['success'] = "Oyun başarıyla satın alındı!";
    header("Location: library.php");
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Satın alma işlemi sırasında bir hata oluştu.";
    header("Location: game.php?id=" . $game_id);
    exit();
}
