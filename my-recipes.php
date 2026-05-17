<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';
require_once 'functions/recipes.php';
require_once 'functions/render.php';

requireRole(['chef', 'admin']);

// Pagination ve Sıralama ayarları
$limit_options = [12, 24, 48];
$sort_options = [
    'newest' => 'En Yeni',
    'oldest' => 'En Eski',
    'rating' => 'En Yüksek Puan',
    'calories_low' => 'En Düşük Kalori',
    'calories_high' => 'En Yüksek Kalori',
    'time_short' => 'En Kısa Süre'
];

$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : 12;
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_recipes = getChefRecipeCount($db, $_SESSION['user_id']);
$total_pages = ceil($total_recipes / $limit);
$recipes = getChefRecipesPaginated($db, $_SESSION['user_id'], $limit, $offset, $sort);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tariflerim - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body class="my-recipes-page">
    <header>
        <?php
            require_once 'functions/header.php';
        ?>
    </header>
        

    <main class="container py-5 mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Tariflerim</h1>
                <p class="text-muted mb-0">Eklediğiniz toplam <?php echo $total_recipes; ?> tarif listeleniyor.</p>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Sıralama Seçici -->
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">Sıralama:</span>
                    <form method="GET" class="d-flex">
                        <select name="sort" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                            <?php foreach ($sort_options as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $sort === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>

                <!-- Limit Seçici -->
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">Sayfa başına:</span>
                    <form method="GET" class="d-flex">
                        <select name="limit" class="form-select form-select-sm rounded-pill pr-5" onchange="this.form.submit()">
                            <?php foreach ($limit_options as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo $limit === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>
            </div>
        </div>

        <?php if ($recipes): ?>
            <?php render_recipe_cards($recipes); ?>

            <!-- Pagination Navigasyonu -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-start-pill px-3" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&sort=<?php echo $sort; ?>">Geri</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&sort=<?php echo $sort; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-end-pill px-3" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&sort=<?php echo $sort; ?>">İleri</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-warning border-0 shadow-sm">
                Henüz tarif eklemediniz. İlk tarifinizi eklemek için <a href="add-recipe.php" class="alert-link">buraya tıklayın</a>.
            </div>
        <?php endif; ?>
    </main>
    <?php require_once 'functions/footer.php'; ?>
</body>
</html>
