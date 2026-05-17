<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';
require_once 'functions/recipes.php';
require_once 'functions/render.php';

$min_cal = isset($_GET['min_cal']) ? (int)$_GET['min_cal'] : 0;
$max_cal = isset($_GET['max_cal']) ? (int)$_GET['max_cal'] : 1000;

$filters = [
    'min_calories' => $min_cal,
    'max_calories' => $max_cal
];

$results = advancedSearchRecipes($db, $filters);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalori Bazlı Tarifler - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
    <header>
        <?php require_once 'functions/header.php'; ?>
    </header>

    <main class="container py-5 mt-5">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="fw-bold">Kalori Filtresi Sonuçları</h1>
                <p class="text-muted">
                    <strong><?php echo $min_cal; ?> - <?php echo $max_cal; ?> kcal</strong> aralığındaki tarifler listeleniyor.
                </p>
                <div class="badge bg-dark rounded-pill px-3 py-2"><?php echo count($results); ?> Tarif Bulundu</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?php 
                    if (!empty($results)) {
                        render_recipe_cards($results);
                    } else {
                        echo '<div class="text-center py-5 bg-white rounded-4 shadow-sm">
                                <h4 class="text-muted">Bu aralıkta bir tarif bulunamadı.</h4>
                                <a href="index.php" class="btn btn-warning mt-3 rounded-pill px-4">Yeni Filtre Dene</a>
                              </div>';
                    }
                ?>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white pt-5 pb-3 mt-5">
        <div class="container text-center">
            <p class="small text-muted">&copy; 2026 Akıllı Tarif Sistemi. Tüm Hakları Saklıdır.</p>
        </div>
    </footer>
</body>
</html>