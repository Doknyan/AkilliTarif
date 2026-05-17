<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';
require_once 'functions/ingredients.php';
require_once 'functions/recipes.php';
require_once 'functions/render.php';

$ingredient_options = getIngredientOptions($db);
// Şef listesini getir
$chef_options = $db->query("SELECT user_id, username FROM users WHERE role = 'chef' AND is_active = 1 ORDER BY username")->fetchAll();

$filters = [
    'q' => $_GET['q'] ?? '',
    'is_vegan' => isset($_GET['is_vegan']),
    'is_gluten_free' => isset($_GET['is_gluten_free']),
    'is_sugar_free' => isset($_GET['is_sugar_free']),
    'is_lactose_free' => isset($_GET['is_lactose_free']),
    'is_vegetarian' => isset($_GET['is_vegetarian']),
    'min_time' => $_GET['min_time'] ?? 0,
    'max_time' => $_GET['max_time'] ?? 120,
    'min_calories' => $_GET['min_calories'] ?? 0,
    'max_calories' => $_GET['max_calories'] ?? 1000,
    'chef_id' => $_GET['chef_id'] ?? '',
    'ingredients' => $_GET['ingredients'] ?? []
];

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

$results = [];
$total_results = 0;
$total_pages = 0;
$is_searched = !empty($_GET);

if ($is_searched) {
    $total_results = advancedSearchRecipeCount($db, $filters);
    $total_pages = ceil($total_results / $limit);
    $results = advancedSearchRecipesPaginated($db, $filters, $limit, $offset, $sort);
}

// Filtreleri URL parametrelerine dönüştüren yardımcı fonksiyon
function buildQuery($params) {
    return http_build_query(array_merge($_GET, $params));
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelişmiş Arama - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-image: url('img/bg/pure.png');
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
        }
        .filter-section {
            background-color: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .ingredient-list-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <header>
        <?php require_once 'functions/header.php'; ?>
    </header>

    <main class="container py-5 mt-4">
        <div class="row">
            <div class="col-lg-4">
                <div class="filter-section mb-4">
                    <h4 class="fw-bold mb-4">Filtrele</h4>
                    <form action="advanced-search.php" method="GET">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Anahtar Kelime</label>
                            <input type="text" name="q" class="form-control rounded-pill" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Tarif adı...">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Beslenme Tercihleri</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_vegan" id="vegan" <?php echo $filters['is_vegan'] ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="vegan">Vegan</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_gluten_free" id="gluten" <?php echo $filters['is_gluten_free'] ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="gluten">Glutensiz</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_sugar_free" id="sugar" <?php echo $filters['is_sugar_free'] ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="sugar">Şekersiz</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_lactose_free" id="lactose" <?php echo $filters['is_lactose_free'] ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="lactose">Laktozsuz</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_vegetarian" id="vegetarian" <?php echo $filters['is_vegetarian'] ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="vegetarian">Vejetaryen</label>
                            </div>
                        </div>

                        <!-- Süre Slider -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Hazırlama Süresi (Dk)</label>
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span id="minTimeDisplay"><?php echo $filters['min_time']; ?> dk</span>
                                <span id="maxTimeDisplay"><?php echo $filters['max_time']; ?> dk</span>
                            </div>
                            <div class="dual-range">
                                <div class="slider-track" id="timeTrack"></div>
                                <input type="range" name="min_time" class="form-range" min="0" max="180" step="5" value="<?php echo $filters['min_time']; ?>" id="minTimeRange">
                                <input type="range" name="max_time" class="form-range" min="0" max="180" step="5" value="<?php echo $filters['max_time']; ?>" id="maxTimeRange">
                            </div>
                        </div>

                        <!-- Kalori Slider -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Kalori (kcal)</label>
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span id="minCalDisplay"><?php echo $filters['min_calories']; ?> kcal</span>
                                <span id="maxCalDisplay"><?php echo $filters['max_calories']; ?> kcal</span>
                            </div>
                            <div class="dual-range">
                                <div class="slider-track" id="calTrack"></div>
                                <input type="range" name="min_calories" class="form-range" min="0" max="1500" step="50" value="<?php echo $filters['min_calories']; ?>" id="minCalRange">
                                <input type="range" name="max_calories" class="form-range" min="0" max="1500" step="50" value="<?php echo $filters['max_calories']; ?>" id="maxCalRange">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Şef Seçimi</label>
                            <select name="chef_id" class="form-select rounded-pill">
                                <option value="">Tüm Şefler</option>
                                <?php foreach ($chef_options as $chef): ?>
                                    <option value="<?php echo $chef['user_id']; ?>" <?php echo $filters['chef_id'] == $chef['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($chef['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">İçerdiği Malzemeler</label>
                            <div class="ingredient-list-container">
                                <?php foreach ($ingredient_options as $opt): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="ingredients[]" value="<?php echo $opt['ingredient_id']; ?>" id="ing_<?php echo $opt['ingredient_id']; ?>" <?php echo in_array($opt['ingredient_id'], $filters['ingredients']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label small" for="ing_<?php echo $opt['ingredient_id']; ?>">
                                            <?php echo htmlspecialchars($opt['ingredient_name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold py-2 shadow-sm">Uygula ve Ara</button>
                        <a href="advanced-search.php" class="btn btn-outline-secondary w-100 rounded-pill fw-bold py-2 mt-2">Temizle</a>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold mb-0">Arama Sonuçları</h2>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($is_searched): ?>
                            <span class="badge bg-dark rounded-pill"><?php echo $total_results; ?> tarif bulundu</span>
                            
                            <!-- Sıralama Seçici -->
                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-muted">Sıralama:</span>
                                <form method="GET" class="d-flex">
                                    <?php foreach ($_GET as $key => $val): ?>
                                        <?php if ($key !== 'sort' && $key !== 'page'): ?>
                                            <?php if (is_array($val)): ?>
                                                <?php foreach ($val as $v): ?>
                                                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>[]" value="<?php echo htmlspecialchars($v); ?>">
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($val); ?>">
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <select name="sort" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                                        <?php foreach ($sort_options as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $sort === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="page" value="1">
                                </form>
                            </div>

                            <!-- Limit Seçici -->
                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-muted">Sayfa başına:</span>
                                <form method="GET" class="d-flex">
                                    <?php foreach ($_GET as $key => $val): ?>
                                        <?php if ($key !== 'limit' && $key !== 'page'): ?>
                                            <?php if (is_array($val)): ?>
                                                <?php foreach ($val as $v): ?>
                                                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>[]" value="<?php echo htmlspecialchars($v); ?>">
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($val); ?>">
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <select name="limit" class="form-select form-select-sm rounded-pill pr-5" onchange="this.form.submit()">
                                        <?php foreach ($limit_options as $opt): ?>
                                            <option value="<?php echo $opt; ?>" <?php echo $limit === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="page" value="1">
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php 
                if ($is_searched) {
                    if (count($results) > 0) {
                        render_recipe_cards($results);
                        
                        // Pagination Navigasyonu
                        if ($total_pages > 1) {
                            echo '<nav class="mt-5"><ul class="pagination justify-content-center">';
                            
                            // Geri butonu
                            $prev_disabled = $page <= 1 ? 'disabled' : '';
                            echo '<li class="page-item ' . $prev_disabled . '">
                                    <a class="page-link rounded-start-pill px-3" href="?' . buildQuery(['page' => $page - 1]) . '">Geri</a>
                                  </li>';
                            
                            // Sayfa numaraları
                            for ($i = 1; $i <= $total_pages; $i++) {
                                $active = $page === $i ? 'active' : '';
                                echo '<li class="page-item ' . $active . '">
                                        <a class="page-link" href="?' . buildQuery(['page' => $i]) . '">' . $i . '</a>
                                      </li>';
                            }
                            
                            // İleri butonu
                            $next_disabled = $page >= $total_pages ? 'disabled' : '';
                            echo '<li class="page-item ' . $next_disabled . '">
                                    <a class="page-link rounded-end-pill px-3" href="?' . buildQuery(['page' => $page + 1]) . '">İleri</a>
                                  </li>';
                            
                            echo '</ul></nav>';
                        }
                    } else {
                        echo '
                        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                            <h4 class="text-muted">Eşleşen tarif bulunamadı.</h4>
                            <p class="small">Lütfen filtreleri değiştirerek tekrar deneyin.</p>
                        </div>';
                    }
                } else {
                    echo '
                    <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                        <h4 class="text-muted">Keşfetmeye Hazır mısın?</h4>
                        <p class="small">Sol taraftaki filtreleri kullanarak dilediğin tarifi bulabilirsin.</p>
                    </div>';
                }
                ?>
            </div>
        </div>
    </main>

<?php require_once 'functions/footer.php'; ?>
<script>
    function setupDualRange(minId, maxId, minDispId, maxIdDisp, trackId, unit) {
        const minRange = document.getElementById(minId);
        const maxRange = document.getElementById(maxId);
        const minDisplay = document.getElementById(minDispId);
        const maxDisplay = document.getElementById(maxIdDisp);
        const track = document.getElementById(trackId);

        function update() {
            if (parseInt(minRange.value) > parseInt(maxRange.value)) {
                minRange.value = maxRange.value;
            }
            
            const min = parseInt(minRange.min);
            const max = parseInt(minRange.max);
            const percent1 = ((minRange.value - min) / (max - min)) * 100;
            const percent2 = ((maxRange.value - min) / (max - min)) * 100;
            
            track.style.background = `linear-gradient(to right, #ddd ${percent1}%, #555 ${percent1}%, #555 ${percent2}%, #ddd ${percent2}%)`;

            minDisplay.textContent = minRange.value + ' ' + unit;
            maxDisplay.textContent = maxRange.value + ' ' + unit;
        }

        minRange.oninput = update;
        maxRange.oninput = update;
        update();
    }

    setupDualRange('minTimeRange', 'maxTimeRange', 'minTimeDisplay', 'maxTimeDisplay', 'timeTrack', 'dk');
    setupDualRange('minCalRange', 'maxCalRange', 'minCalDisplay', 'maxCalDisplay', 'calTrack', 'kcal');
</script>
</body>
</html>
