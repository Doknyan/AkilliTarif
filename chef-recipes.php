<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';
require_once 'functions/recipes.php';
require_once 'functions/render.php';

$is_logged_in = isLoggedIn();
$current_role = $is_logged_in ? strtolower($_SESSION['role'] ?? 'user') : '';

$chef_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$chef = null;
$recipes = [];

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

if ($chef_id > 0) {
    // Şef bilgilerini al
    $stmt = $db->prepare("SELECT username, profile_image FROM users WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$chef_id]);
    $chef = $stmt->fetch();

    if ($chef) {
        // Şefin tariflerini al
        $total_recipes = getChefRecipeCount($db, $chef_id);
        $total_pages = ceil($total_recipes / $limit);
        $recipes = getChefRecipesPaginated($db, $chef_id, $limit, $offset, $sort);
    }
}

// Şef bulunamadıysa listeye geri gönder
if (!$chef) {
    header("Location: chefs.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($chef['username']); ?> Şefin Tarifleri - Akıllı Tarif</title>
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
                <div class="mb-3">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold"> ŞEFİN MUTFAĞI </span>
                </div>
                <img src="img/profiles/<?php echo htmlspecialchars($chef['profile_image'] ?? 'default-profile.jpg'); ?>" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;" alt="<?php echo htmlspecialchars($chef['username']); ?>">    
                <h1 class="fw-bold"><?php echo htmlspecialchars($chef['username']); ?></h1>
                <p class="text-muted">Bu şef tarafından paylaşılan toplam <?php echo $total_recipes; ?> tarif listeleniyor.</p>
                <hr class="w-25 mx-auto">
            </div>
        </div>

        <div class="d-flex justify-content-end align-items-center mb-4 gap-3">
            <!-- Sıralama Seçici -->
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Sıralama:</span>
                <form method="GET" class="d-flex">
                    <input type="hidden" name="id" value="<?php echo $chef_id; ?>">
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
                    <input type="hidden" name="id" value="<?php echo $chef_id; ?>">
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

        <div class="row">
            <div class="col-12">
                <?php 
                    if (!empty($recipes)) {
                        render_recipe_cards($recipes);
                    } else {
                        echo '<div class="text-center py-5 bg-white rounded-4 shadow-sm">
                                <p class="text-muted mb-0">Bu şef henüz bir tarif paylaşmamış.</p>
                              </div>';
                    }
                ?>
            </div>
        </div>

        <!-- Pagination Navigasyonu -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-start-pill px-3" href="?id=<?php echo $chef_id; ?>&page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&sort=<?php echo $sort; ?>">Geri</a>
                </li>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $chef_id; ?>&page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&sort=<?php echo $sort; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-end-pill px-3" href="?id=<?php echo $chef_id; ?>&page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&sort=<?php echo $sort; ?>">İleri</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        
        <div class="text-center mt-5">
            <a href="chefs.php" class="btn btn-outline-dark rounded-pill px-4">← Tüm Şeflere Dön</a>
        </div>
    </main>

<?php require_once 'functions/footer.php'; ?>
</body>
</html>