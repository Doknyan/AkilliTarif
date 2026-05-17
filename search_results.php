<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';
require_once 'functions/recipes.php';
require_once 'functions/render.php';

$is_logged_in = isLoggedIn();
$current_role = $is_logged_in ? strtolower($_SESSION['role'] ?? 'user') : '';

$query = isset($_GET['q']) ? trim(htmlspecialchars($_GET['q'])) : '';
$results = [];

if ($query !== '') {
    $results = searchRecipes($db, $query);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $query; ?> İçin Arama Sonuçları - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
    <header>
        <?php require_once 'functions/header.php'; ?>
    </header>

    <main class="container py-5 mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="fw-bold">Arama Sonuçları</h1>
                <p class="text-muted">
                    <?php if ($query !== ''): ?>
                        "<strong><?php echo $query; ?></strong>" araması için <?php echo count($results); ?> sonuç bulundu.
                    <?php else: ?>
                        Lütfen bir arama terimi girin.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?php 
                if ($query !== '') {
                    render_recipe_cards($results);
                }
                ?>
            </div>
        </div>

        <?php if (count($results) === 0 && $query !== ''): ?>
            <div class="text-center py-5">
                <img src="img/recipe/default-food.jpg" alt="Sonuç yok" class="mb-4 rounded-circle" style="width: 150px; height: 150px; object-fit: cover; opacity: 0.5;">
                <h3>Üzgünüz, eşleşen bir tarif bulamadık.</h3>
                <p class="text-muted">Farklı anahtar kelimeler denemeye ne dersiniz?</p>
                <a href="index.php" class="btn btn-warning rounded-pill px-4 mt-3">Ana Sayfaya Dön</a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-dark text-white pt-5 pb-3">
        <div class="container text-center">
            <p class="small text-muted">&copy; 2026 Akıllı Tarif Sistemi. Tüm Hakları Saklıdır.</p>
        </div>
    </footer>
</body>
</html>