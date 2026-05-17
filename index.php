<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';
require_once 'functions/ingredients.php';
require_once 'functions/recipes.php';
require_once 'functions/render.php';

$is_logged_in = isLoggedIn();
$current_role = $is_logged_in ? strtolower($_SESSION['role'] ?? 'user') : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akıllı Tarif - Sağlıklı Yemek Planlama</title>

    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/bootstrap.bundle.min.js"></script>

    <script src="js/jquery-4.0.0.js"></script>

</head>


<body>

    <header><?php require_once 'functions/header.php'; ?></header>

<!-- Slider için verileri çek -->
<?php include 'functions/get_slider_recipes.php'; ?>

<div id="recipeSlider" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#recipeSlider" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#recipeSlider" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#recipeSlider" data-bs-slide-to="2"></button>
        <button type="button" data-bs-target="#recipeSlider" data-bs-slide-to="3"></button>
    </div>
    
    <div class="carousel-inner">
        <?php 
        $titles = [
            'latest' => 'En Yeni Tarifimiz',
            'popular' => 'En Çok İlgi Gören',
            'top_rated' => 'Yüksek Puanlı Lezzet',
            'random' => 'Senin İçin Seçtik'
        ];
        
        $active = "active";
        foreach ($slider_recipes as $key => $recipe): 
            if (!$recipe) continue;
            $img = $recipe['image_path'] ?: 'default-food.jpg';
        ?>
            <div class="carousel-item <?php echo $active; ?> hero-slide">
                <img src="img/recipe/<?php echo $img; ?>" class="d-block w-100 h-100" style="object-fit: cover;" alt="...">
                <div class="carousel-caption d-none d-md-block text-start p-4 bg-dark bg-opacity-75 rounded" style="backdrop-filter: blur(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
                    <span class="badge bg-warning text-dark mb-2 px-3 py-2"><?php echo $titles[$key]; ?></span>
                    <h2 class="display-6 fw-bold"><?php echo htmlspecialchars($recipe['title']); ?></h2>
                    <p class="text-light"><?php echo substr(htmlspecialchars($recipe['instructions']), 0, 100) . '...'; ?></p>
                    <a href="<?php echo getRecipeLink($recipe['recipe_id']); ?>" class="btn btn-warning fw-bold px-4">Tarife Git</a>
                </div>
            </div>
        <?php 
            $active = ""; // Sadece ilk item active olmalı
        endforeach; 
        ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#recipeSlider" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#recipeSlider" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<!-- Mutfağın durumunu göster -->
<?php if ($is_logged_in && $current_role === 'user'): ?>
<section class="container mb-5">
    <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold mb-1">📢 Dolap Hatırlatıcısı</h5>
            <p class="mb-0 small">
                <?php 
                $expired_count = getExpiringIngredientsCount($db, $_SESSION['user_id']);

                if($expired_count > 0) {
                    echo "Dikkat! Dolabında son tüketim tarihi yaklaşan veya geçen <strong>$expired_count</strong> malzeme var.";
                } else {
                    echo "Harika! Dolabındaki ürünlerin tarihi iyi görünüyor.";
                }
                ?>
            </p>
        </div>
        <a href="my-kitchen.php" class="btn btn-dark btn-sm rounded-pill px-4">Dolabıma Git</a>
    </div>
</section>
<?php endif; ?>

    <!-- Son eklenen tarifler için verileri çek -->
    <main class="container my-5">
        
        <!-- Günün En Çok Beğenilenleri -->
        <section class="mb-5 bg-warning bg-opacity-10 p-4 rounded-4 border border-warning shadow-sm">
            <h2 class="fw-bold mb-4 text-warning text-darken"><i class="bi bi-star-fill text-warning"></i> Günün En Çok Oy Alanları</h2>
            <div id="daily-top-recipes">
                <?php 
                    $daily_recipes = getDailyTopRecipes($db, 4);
                    if (empty($daily_recipes)) {
                        echo '<p class="text-muted text-center py-3">Bugün henüz hiçbir tarife oy verilmedi. <a href="explore.php" class="text-warning fw-bold">Keşfet</a> sayfasından tariflere göz atıp ilk oyu sen verebilirsin!</p>';
                    } else {
                        render_recipe_cards($daily_recipes);
                    }
                ?>
            </div>
        </section>

        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Son Eklenen Tarifler</h2>
                <a href="all-recipes.php" class="text-warning fw-bold text-decoration-none">Tümünü Gör <i class="bi bi-arrow-right"></i></a>
            </div>
            <div id="latest-recipes">
                <?php 
                    $latest_recipes = getRecentRecipes($db, 4);
                    render_recipe_cards($latest_recipes);
                ?>
            </div>
        </section>

        <!-- Ünlü Şefler Reklam Alanı -->
        <?php
            $arda_id = $db->query("SELECT user_id FROM users WHERE email='arda@akillitarif.com'")->fetchColumn() ?: '#';
            $somer_id = $db->query("SELECT user_id FROM users WHERE email='somer@akillitarif.com'")->fetchColumn() ?: '#';
            $danilo_id = $db->query("SELECT user_id FROM users WHERE email='danilo@akillitarif.com'")->fetchColumn() ?: '#';
        ?>
        <section class="mb-5 py-5 bg-dark text-white rounded-4 shadow-lg text-center" style="background: linear-gradient(135deg, #1f1f1f, #000);">
            <h2 class="fw-bold mb-3 text-warning">Ünlü Şefler Bizi Tercih Ediyor! <i class="bi bi-patch-check-fill"></i></h2>
            <p class="text-light mb-5 px-3">Türkiye'nin en usta isimleri gizli tariflerini Akıllı Tarif'te paylaşıyor. Onların lezzet sırlarını keşfet.</p>
            <div class="row justify-content-center g-4 px-3">
                <div class="col-6 col-md-3 text-center">
                    <a href="<?php echo $arda_id !== '#' ? 'chef-recipes.php?id=' . $arda_id : '#'; ?>" class="text-decoration-none text-white">
                        <div class="rounded-circle overflow-hidden mx-auto mb-3 shadow" style="width: 120px; height: 120px; border: 4px solid #ffc107;">
                            <img src="img/profiles/arda.jpg" alt="Arda Türkmen" style="width:100%; height:100%; object-fit: cover;">
                        </div>
                        <h5 class="fw-bold mb-1">Arda Türkmen <i class="bi bi-patch-check-fill text-primary small"></i></h5>
                        <small class="text-warning fw-bold">Usta Şef</small>
                    </a>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <a href="<?php echo $somer_id !== '#' ? 'chef-recipes.php?id=' . $somer_id : '#'; ?>" class="text-decoration-none text-white">
                        <div class="rounded-circle overflow-hidden mx-auto mb-3 shadow" style="width: 120px; height: 120px; border: 4px solid #ffc107;">
                            <img src="img/profiles/somer.jpg" alt="Somer Sivrioğlu" style="width:100%; height:100%; object-fit: cover;">
                        </div>
                        <h5 class="fw-bold mb-1">Somer Sivrioğlu <i class="bi bi-patch-check-fill text-primary small"></i></h5>
                        <small class="text-warning fw-bold">Gastronomi Uzmanı</small>
                    </a>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <a href="<?php echo $danilo_id !== '#' ? 'chef-recipes.php?id=' . $danilo_id : '#'; ?>" class="text-decoration-none text-white">
                        <div class="rounded-circle overflow-hidden mx-auto mb-3 shadow" style="width: 120px; height: 120px; border: 4px solid #ffc107;">
                            <img src="img/profiles/danilo.jpg" alt="Danilo Zanna" style="width:100%; height:100%; object-fit: cover;">
                        </div>
                        <h5 class="fw-bold mb-1">Danilo Zanna <i class="bi bi-patch-check-fill text-primary small"></i></h5>
                        <small class="text-warning fw-bold">İtalyan Şef</small>
                    </a>
                </div>
            </div>
        </section>

        <!-- Popüler tarifler için verileri çek -->
        <section class="mb-5 bg-light p-4 rounded-4">
            <h2 class="fw-bold mb-4">En Popüler Tarifler</h2>
            <div id="popular-recipes">
                <?php 
                    $popular_recipes = getPopularRecipes($db, 4);
                    render_recipe_cards($popular_recipes);
                ?>
            </div>
        </section>


    <div class="hero-section mb-5">
        <div class="container text-center text-white py-5">
            <h1 class="display-4 fw-bold">Ne Pişireceğine Karar Veremedin mi?</h1>
            <p class="lead">Evdeki malzemelerinle en sağlıklı tarifleri keşfet.</p>
            
            <div class="row justify-content-center mt-4">
                <div class="col-md-8">
                    <form action="search_results.php" method="GET">
                        <div class="input-group input-group-lg border-0 shadow-lg">
                            <input type="text" name="q" id="recipeSearch" class="form-control border-0" placeholder="Tarif, malzeme veya kategori ara..." required>
                            <button class="btn btn-warning px-4" type="submit">Ara</button>
                        </div>
                    </form>
                    <div class="mt-2">
                        <a href="advanced-search.php" class="text-white text-decoration-none small">⚙️ Gelişmiş Arama</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="mb-5 bg-white p-3 rounded-4 shadow-sm">
        <h2 class="fw-bold mb-4 text-center">Kalori Hedefine Göre Göz At</h2>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form action="browse-calories.php" method="GET">
                    <div class="calorie-slider-container mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span id="minCalDisplay" class="fw-bold text-success">0 kcal</span>
                            <span id="maxCalDisplay" class="fw-bold text-danger">1000 kcal</span>
                        </div>
                        <div class="dual-range">
                            <div class="slider-track" id="homeCalTrack"></div>
                            <input type="range" name="min_cal" class="form-range" min="0" max="1000" step="50" value="0" id="minCalRange">
                            <input type="range" name="max_cal" class="form-range" min="0" max="1000" step="50" value="1000" id="maxCalRange">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold py-2">Filtreyi Uygula</button>
                </form>
            </div>
        </div>
    </section>

        <script>
            const minRange = document.getElementById('minCalRange');
            const maxRange = document.getElementById('maxCalRange');
            const minDisplay = document.getElementById('minCalDisplay');
            const maxDisplay = document.getElementById('maxCalDisplay');
            const calTrack = document.getElementById('homeCalTrack');

            function updateSlider() {
                if (parseInt(minRange.value) > parseInt(maxRange.value)) {
                    minRange.value = maxRange.value;
                }

                const min = parseInt(minRange.min);
                const max = parseInt(minRange.max);
                const percent1 = ((minRange.value - min) / (max - min)) * 100;
                const percent2 = ((maxRange.value - min) / (max - min)) * 100;

                calTrack.style.background = `linear-gradient(to right, #ddd ${percent1}%, #555 ${percent1}%, #555 ${percent2}%, #ddd ${percent2}%)`;

                minDisplay.textContent = minRange.value + ' kcal';
                maxDisplay.textContent = maxRange.value + ' kcal';
            }

            minRange.oninput = updateSlider;
            maxRange.oninput = updateSlider;
            updateSlider();
        </script>

    </main>

    <?php require_once 'functions/footer.php'; ?>
    </body>
    </html>
