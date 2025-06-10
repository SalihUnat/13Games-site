<?php
require 'auth.php';

if (!isset($_SESSION['user']['wallet_balance'])) {
    $_SESSION['user']['wallet_balance'] = 0;
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);

    if($amount <= 0) {
        $error = "Lütfen geçerli bir miktar girin.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        if($stmt->execute([$amount, $_SESSION['user']['id']])) {
            $_SESSION['user']['wallet_balance'] += $amount;
            $success = "Cüzdanınıza başarıyla $amount TL yüklendi!";
        } else {
            $error = "Bakiye yükleme sırasında bir hata oluştu.";
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Cüzdan Yükle - GameHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>

    <main class="container">
        <h2>Cüzdan Bakiyeniz: <?= number_format($_SESSION['user']['wallet_balance'], 2) ?> TL</h2>

        <?php if($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="wallet.php" class="wallet-form">
            <div class="form-group">
                <label for="amount">Yüklenecek Miktar (TL)</label>
                <input type="number" step="0.01" min="0.01" id="amount" name="amount" required>
            </div>
            <button type="submit" class="btn">Bakiye Yükle</button>
        </form>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2023 GameHub - Tüm hakları saklıdır</p>
        </div>
    </footer>
</body>
</htm
