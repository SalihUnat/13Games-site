<header>
    <div class="container">
        <h1>GameHub</h1>
        <nav>
            <a href="index.php">Ana Sayfa</a>
            <a href="store.php">Mağaza</a>
            <?php if(isset($_SESSION['user'])): ?>
                <a href="library.php">Kütüphanem</a>
                <a href="wallet.php">Cüzdan</a>
                <?php if($_SESSION['user']['is_admin']): ?>
                    <a href="admin.php">Admin Paneli</a>
                <?php endif; ?>
                <a href="logout.php">Çıkış Yap</a>
            <?php else: ?>
                <a href="login.php">Giriş Yap</a>
                <a href="register.php">Kayıt Ol</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
