<?php
session_start();
require 'db.php';

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        
        $_SESSION['success'] = "Kayıt başarılı! Giriş yapabilirsiniz.";
        header("Location: login.php");
        exit();
    } catch(PDOException $e) {
        $error = "Kayıt başarısız! Kullanıcı adı veya email zaten kullanımda.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>GameHub - Kayıt Ol</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>

    <main class="container">
        <div class="form-container">
            <h2>Kayıt Ol</h2>
            <?php if($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Kayıt Ol</button>
            </form>
            
            <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2023 GameHub - Tüm hakları saklıdır</p>
        </div>
    </footer>
</body>
</html>