<?php
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kullanıcı adı veya e-posta ile arama yapıyoruz
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user) {
        // Şifre doğrulama
        if (password_verify($password, $user['password'])) {
            // Kullanıcı bilgilerini sessiona kaydet
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'is_admin' => (bool)$user['is_admin'],
                'wallet_balance' => $user['wallet_balance']  // Bakiye eklendi
            ];

            // Eğer önceden yönlendirme yapılmak istenmişse
            if (isset($_SESSION['redirect'])) {
                $redirect = $_SESSION['redirect'];
                unset($_SESSION['redirect']);
                header("Location: $redirect");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Kullanıcı adı veya şifre hatalı!";
        }
    } else {
        $error = "Kullanıcı adı veya şifre hatalı!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>GameHub - Giriş Yap</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>

    <main class="container">
        <div class="form-container">
            <h2>Giriş Yap</h2>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı veya E-posta</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Giriş Yap</button>
            </form>

            <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2023 GameHub - Tüm hakları saklıdır</p>
        </div>
    </footer>
</body>
</html>
