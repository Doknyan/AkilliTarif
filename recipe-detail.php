<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/recipes.php';
require_once 'functions/favorites.php';
require_once 'functions/comments.php';
require_once 'functions/auth.php';

// URL'den ID'yi al ve güvenli hale getir
$recipe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_logged_in = isLoggedIn();

// Admin ise düzenleme sayfasına yönlendir
// if ($is_logged_in && $_SESSION['role'] === 'admin') {
//     header("Location: edit-recipe.php?id=" . $recipe_id);
//     exit;
// }

$success_message = $_SESSION['flash_success'] ?? '';
$error_message = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($recipe_id <= 0) {
    header("Location: index.php");
    exit;
}

try {
    $recipe = getRecipeById($db, $recipe_id);

    if (!$recipe) {
        die("Tarif bulunamadı!");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$is_logged_in) {
            $_SESSION['flash_error'] = 'Favori eklemek veya yorum yapmak için giriş yapmalısınız.';
            header("Location: recipe-detail.php?id=" . $recipe_id);
            exit;
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'add_favorite') {
            if (isFavorite($db, $_SESSION['user_id'], $recipe_id)) {
                $_SESSION['flash_success'] = 'Bu tarif zaten favorilerinizde.';
            } else {
                addFavorite($db, $_SESSION['user_id'], $recipe_id);
                $_SESSION['flash_success'] = 'Tarif favorilerinize eklendi.';
            }

            header("Location: recipe-detail.php?id=" . $recipe_id);
            exit;
        }

        if ($action === 'add_comment') {
            $rating = (int) ($_POST['rating'] ?? 0);
            $comment_text = trim($_POST['comment_text'] ?? '');

            if ($rating < 1 || $rating > 5) {
                $_SESSION['flash_error'] = 'Lütfen 1 ile 5 arasında bir değerlendirme seçin.';
            } elseif ($comment_text === '') {
                $_SESSION['flash_error'] = 'Yorum alanı boş bırakılamaz.';
            } else {
                addComment($db, $_SESSION['user_id'], $recipe_id, $comment_text, $rating);
                $_SESSION['flash_success'] = 'Yorumunuz ve değerlendirmeniz eklendi.';
            }

            header("Location: recipe-detail.php?id=" . $recipe_id);
            exit;
        }

        $_SESSION['flash_error'] = 'Geçersiz işlem.';
        header("Location: recipe-detail.php?id=" . $recipe_id);
        exit;
    }

    $ingredients = getRecipeIngredients($db, $recipe_id);
    $comments = getRecipeComments($db, $recipe_id);
    $is_favorite = $is_logged_in ? isFavorite($db, $_SESSION['user_id'], $recipe_id) : false;

} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <title><?php echo htmlspecialchars($recipe['title']); ?> - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

    <header>
        <?php
            require_once 'functions/header.php';
        ?>  
    </header>

    <div class="container my-5">
        <?php if ($success_message): ?>
            <div class="alert alert-success border-0 shadow-sm"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger border-0 shadow-sm"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <img src="img/recipe/<?php echo htmlspecialchars($recipe['image_path'] ?: 'default-food.jpg'); ?>" class="img-fluid w-100" style="max-height: 400px; object-fit: cover;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h1 class="fw-bold"><?php echo htmlspecialchars($recipe['title']); ?></h1>
                            <span class="badge bg-warning text-dark p-2 fs-6"><?php echo $recipe['calories']; ?> kcal</span>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <p class="text-muted mb-0">Ekleyen Şef: <a href="chef-recipes.php?id=<?php echo $recipe['chef_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($recipe['chef_name'] ?? 'Bilinmiyor'); ?></a></p>

                            <?php if ($is_logged_in): ?>
                                <?php if ($is_favorite): ?>
                                    <a href="my-favorites.php" class="btn btn-success btn-sm rounded-pill px-3">Favorilerimde</a>
                                <?php else: ?>
                                    <form method="POST" action="recipe-detail.php?id=<?php echo $recipe_id; ?>">
                                        <input type="hidden" name="action" value="add_favorite">
                                        <button type="submit" class="btn btn-warning btn-sm rounded-pill px-3">Favorilerime ekle</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="auth.php" class="btn btn-outline-warning btn-sm rounded-pill px-3">Favorilerime ekle</a>
                            <?php endif; ?>
                        </div>
                        <hr>
                        <h4 class="fw-bold mt-4">Hazırlanışı</h4>
                        <p class="lh-lg"><?php echo nl2br(htmlspecialchars($recipe['instructions'])); ?></p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h4 class="fw-bold mb-4">Yorumlar (<?php echo count($comments); ?>)</h4>

                    <?php if ($is_logged_in): ?>
                        <form method="POST" action="recipe-detail.php?id=<?php echo $recipe_id; ?>" class="border-bottom pb-4 mb-4">
                            <input type="hidden" name="action" value="add_comment">

                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Değerlendirme</label>
                                <div class="star-rating mb-2">
                                    <input type="radio" name="rating" id="star5" value="5" required>
                                    <label for="star5" title="5 - Harika">★</label>
                                    <input type="radio" name="rating" id="star4" value="4">
                                    <label for="star4" title="4 - Çok iyi">★</label>
                                    <input type="radio" name="rating" id="star3" value="3">
                                    <label for="star3" title="3 - İyi">★</label>
                                    <input type="radio" name="rating" id="star2" value="2">
                                    <label for="star2" title="2 - Orta">★</label>
                                    <input type="radio" name="rating" id="star1" value="1">
                                    <label for="star1" title="1 - Zayıf">★</label>
                                    
                                </div>
                                <span class="star-rating-text" id="rating-text">Puan seçin</span>
                            </div>

                            <div class="mb-3">
                                <label for="comment_text" class="form-label fw-semibold">Yorumunuz</label>
                                <textarea name="comment_text" id="comment_text" class="form-control" rows="4" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-warning fw-bold">Yorum ekle</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning border-0">
                            Yorum eklemek, değerlendirme yapmak veya favorilere eklemek için <a href="auth.php" class="alert-link">giriş yapın</a>.
                        </div>
                    <?php endif; ?>

                    <?php if (!$comments): ?>
                        <p class="text-muted mb-0">Henüz yorum yapılmamış.</p>
                    <?php endif; ?>

                    <?php foreach($comments as $comment): ?>
                        <?php $display_rating = max(1, min(5, (int) $comment['rating'])); ?>
                        <div class="border-bottom mb-3 pb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                <span class="text-warning">
                                    <?php echo str_repeat('★', $display_rating) . str_repeat('☆', 5 - $display_rating); ?>
                                </span>
                            </div>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h4 class="fw-bold mb-4">Malzemeler</h4>
                    <ul class="list-group list-group-flush">
                        <?php foreach($ingredients as $ing): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <?php echo htmlspecialchars($ing['ingredient_name']); ?>
                                <span class="badge bg-secondary rounded-pill">
                                    <?php echo (float)$ing['amount'] . ' ' . $ing['unit']; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-warning bg-opacity-10">
                    <h5 class="fw-bold mb-3">Tarif Özeti</h5>
                    <p class="mb-1">⏱ <strong>Süre:</strong> <?php echo $recipe['preparation_time']; ?> dk</p>
                    <p class="mb-1">🥑 <strong>Vegan:</strong> <?php echo $recipe['is_vegan'] ? 'Evet' : 'Hayır'; ?></p>
                    <p class="mb-1">🌾 <strong>Glutensiz:</strong> <?php echo $recipe['is_gluten_free'] ? 'Evet' : 'Hayır'; ?></p>
                    <p class="mb-1">🥛 <strong>Laktozsuz:</strong> <?php echo $recipe['is_lactose_free'] ? 'Evet' : 'Hayır'; ?></p>
                    <p class="mb-1">🥗 <strong>Vejetaryen:</strong> <?php echo $recipe['is_vegetarian'] ? 'Evet' : 'Hayır'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'functions/footer.php'; ?>

</body>
</html>
