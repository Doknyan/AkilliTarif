<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'functions/db.php';
require_once 'functions/ingredients.php';
require_once 'functions/render.php';
require_once 'functions/auth.php';

// requireRole('admin','chef','user');
requireLogin();
$success = '';
$error = '';
$ingredient_options = getIngredientOptions($db);
$valid_ingredient_ids = getValidIngredientIds($ingredient_options);
$ingredient_categories = getIngredientCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'add_ingredient') {
        $ingredient_id_raw = (string) ($_POST['ingredient_id'] ?? '');
        $ingredient_id = $ingredient_id_raw === 'new' ? 0 : (int) $ingredient_id_raw;
        $new_ingredient_name = trim((string) ($_POST['new_ingredient_name'] ?? ''));
        $new_ingredient_unit = trim((string) ($_POST['new_ingredient_unit'] ?? ''));
        $quantity = (float) str_replace(',', '.', trim((string) ($_POST['quantity'] ?? '')));
        $expiry_date = trim((string) ($_POST['expiry_date'] ?? ''));

        if ($ingredient_id > 0 && !in_array($ingredient_id, $valid_ingredient_ids, true)) {
            $error = 'Lütfen geçerli bir malzeme seçin.';
        } elseif ($ingredient_id === 0 && $new_ingredient_name === '') {
            $error = 'Lütfen yeni malzeme adı girin.';
        } elseif ($ingredient_id === 0 && $new_ingredient_unit === '') {
            $error = 'Lütfen yeni malzeme için birim girin.';
        } elseif ($quantity <= 0) {
            $error = 'Miktar 0’dan büyük olmalıdır.';
        } elseif ($expiry_date === '') {
            $error = 'Son tüketim tarihi seçin.';
        } else {
            $expiry = DateTime::createFromFormat('Y-m-d', $expiry_date);
            $date_errors = DateTime::getLastErrors();

            if (!$expiry || ($date_errors !== false && ($date_errors['warning_count'] > 0 || $date_errors['error_count'] > 0))) {
                $error = 'Son tüketim tarihi geçerli değil.';
            }
        }

        if ($error === '') {
            if ($ingredient_id === 0) {
                $ingredient_id = lookupOrCreateIngredient($db, $new_ingredient_name, $new_ingredient_unit ?: null);
            }

            try {
                $existing_stmt = $db->prepare("
                    SELECT ui_id
                    FROM user_ingredients
                    WHERE user_id = ? AND ingredient_id = ? AND expiry_date = ?
                    LIMIT 1
                ");
                $existing_stmt->execute([$_SESSION['user_id'], $ingredient_id, $expiry_date]);
                $existing_id = $existing_stmt->fetchColumn();

                if ($existing_id) {
                    $update_stmt = $db->prepare("UPDATE user_ingredients SET quantity = quantity + ? WHERE ui_id = ?");
                    $update_stmt->execute([$quantity, $existing_id]);
                    $success = 'Malzeme dolabınızda vardı; miktarı güncellendi.';
                } else {
                    $insert_stmt = $db->prepare("
                        INSERT INTO user_ingredients (user_id, ingredient_id, quantity, expiry_date)
                        VALUES (?, ?, ?, ?)
                    ");
                    $insert_stmt->execute([$_SESSION['user_id'], $ingredient_id, $quantity, $expiry_date]);
                    $success = 'Malzeme dolabınıza eklendi.';
                }
            } catch (PDOException $e) {
                $error = 'Malzeme eklenemedi: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_single') {
        $ui_id = (int) ($_POST['ui_id'] ?? 0);

        if ($ui_id <= 0) {
            $error = 'Silinecek malzeme seçilmedi.';
        } else {
            try {
                $delete_stmt = $db->prepare("DELETE FROM user_ingredients WHERE ui_id = ? AND user_id = ?");
                $delete_stmt->execute([$ui_id, $_SESSION['user_id']]);

                if ($delete_stmt->rowCount() > 0) {
                    $success = 'Malzeme silindi.';
                } else {
                    $error = 'Malzeme silinemedi veya bulunamadı.';
                }
            } catch (PDOException $e) {
                $error = 'Malzeme silinemedi: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_expired_all') {
        try {
            $delete_all_stmt = $db->prepare("DELETE FROM user_ingredients WHERE user_id = ? AND expiry_date IS NOT NULL AND expiry_date < CURDATE()");
            $delete_all_stmt->execute([$_SESSION['user_id']]);
            $deleted = $delete_all_stmt->rowCount();

            if ($deleted > 0) {
                $success = 'Tüm son tüketim tarihi geçen malzemeler silindi.';
            } else {
                $success = 'Son tüketim tarihi geçen malzeme bulunamadı.';
            }
        } catch (PDOException $e) {
            $error = 'Malzemeler silinemedi: ' . $e->getMessage();
        }
    }
}

$ingredients = getUserIngredientRows($db, $_SESSION['user_id']);
$categories = categorizeUserIngredients($ingredients);
$expired_items = $categories['expired_items'];
$expiring_items = $categories['expiring_items'];
$fresh_items = $categories['fresh_items'];
$undated_items = $categories['undated_items'];
$expiring_recipes = getRecipesForExpiringIngredients($db, $_SESSION['user_id']);
$fresh_recipes = getRecipesForFreshIngredients($db, $_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dolabım - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=1.1">
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body class="my-kitchen-page">

    <header>
        <?php
            require_once 'functions/header.php';
        ?>  
    </header>

    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Dolabım</h1>
                <p class="text-muted mb-0">Dolabındaki malzemeleri son tüketim tarihine göre takip et.</p>
            </div>
            <span class="badge bg-dark fs-6"><?php echo count($ingredients); ?> malzeme</span>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Buzdolabıma malzeme ekle</h2>
                <form method="POST" action="my-kitchen.php" class="kitchen-add-form">
                    <input type="hidden" name="action" value="add_ingredient">

                    <div>
                        <label class="form-label">Malzeme</label>
                        <select name="ingredient_id" id="kitchenIngredientSelect" class="form-select" required>
                            <option value="" data-unit="">Malzeme seçin</option>
                            <option value="new" data-unit="">Yeni malzeme ekle</option>
                            <?php foreach ($ingredient_options as $ingredient): ?>
                                <option value="<?php echo (int) $ingredient['ingredient_id']; ?>" data-unit="<?php echo htmlspecialchars($ingredient['unit']); ?>">
                                    <?php echo htmlspecialchars($ingredient['ingredient_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="kitchenNewIngredientFields" class="d-none mt-3">
                        <label class="form-label">Yeni malzeme adı</label>

                        <input type="text" name="new_ingredient_name" class="form-control" placeholder="Malzeme adı">
                        <label class="form-label mt-3">Birim</label>
                        <input type="text" name="new_ingredient_unit" id="kitchenNewIngredientUnit" class="form-control" placeholder="Birim (gram, adet)">
                        <label class="form-label mt-3">Kategori</label>
                        <select name="new_ingredient_category" class="form-select">
                            <?php foreach ($ingredient_categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>   
                    </div>

                    <div>
                        <label class="form-label">Miktar</label>
                        <div class="input-group">
                            <input type="number" name="quantity" class="form-control" min="0.01" step="0.01" required>
                            <span class="input-group-text" id="kitchenIngredientUnit">Birim</span>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Son tüketim tarihi</label>
                        <input type="date" name="expiry_date" class="form-control" required>
                    </div>

                    <div class="kitchen-add-actions">
                        <button type="submit" class="btn btn-warning fw-bold">Malzeme ekle</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-0">Son tüketim tarihi geçenler</h2>
                        <small class="text-muted">Bu malzemeleri tek tek veya topluca silebilirsiniz.</small>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-danger"><?php echo count($expired_items); ?></span>
                        <form method="POST" action="my-kitchen.php" class="m-0">
                            <input type="hidden" name="action" value="delete_expired_all">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tüm son tüketim tarihi geçen malzemeleri silmek istediğinize emin misiniz?');">
                                Tümünü Sil
                            </button>
                        </form>
                    </div>
                </div>
                <?php render_ingredient_table($expired_items, 'Son tüketim tarihi geçen malzeme yok.', 'bg-danger', true); ?>
            </div>
        </section>

        <section class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-0">3 gün içinde tüketilmesi gerekenler</h2>
                        <small class="text-muted">Bu malzemelere uygun tarifleri aşağıda inceleyin.</small>
                    </div>
                    <span class="badge bg-warning text-dark"><?php echo count($expiring_items); ?></span>
                </div>
                <?php render_ingredient_table($expiring_items, 'Son tüketim tarihi 3 gün içinde dolacak malzeme yok.', 'bg-warning text-dark', true); ?>
                <div class="mt-4">
                    <h3 class="h6 fw-semibold mb-3">Yaklaşan son tüketim tarihine sahip malzemelere uygun tarifler</h3>
                    <?php render_recipe_cards($expiring_recipes, false, 'row-cols-1 row-cols-md-3 row-cols-lg-4'); ?>
                </div>
            </div>
        </section>

        <section class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-bold mb-0">Diğer malzemeler</h2>
                    <span class="badge bg-success"><?php echo count($fresh_items); ?></span>
                </div>
                <?php render_ingredient_table($fresh_items, 'Dolabında tarihi ileri olan malzeme yok.', 'bg-success', true); ?>
                
                <?php if ($fresh_recipes): ?>
                <div class="mt-4">
                    <h3 class="h6 fw-semibold mb-3">Dolabındaki diğer malzemelere uygun tarifler</h3>
                    <?php render_recipe_cards($fresh_recipes, false, 'row-cols-1 row-cols-md-3 row-cols-lg-4'); ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($undated_items): ?>
            <section class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 fw-bold mb-0">Tarihi girilmemiş malzemeler</h2>
                        <span class="badge bg-secondary"><?php echo count($undated_items); ?></span>
                    </div>
                    <?php render_ingredient_table($undated_items, 'Tarihi girilmemiş malzeme yok.', 'bg-secondary', true); ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <script>
        const kitchenIngredientSelect = document.getElementById('kitchenIngredientSelect');
        const kitchenIngredientUnit = document.getElementById('kitchenIngredientUnit');
        const kitchenNewFields = document.getElementById('kitchenNewIngredientFields');
        const kitchenNewUnit = document.getElementById('kitchenNewIngredientUnit');

        function refreshKitchenIngredientInputs() {
            const selectedOption = kitchenIngredientSelect.options[kitchenIngredientSelect.selectedIndex];
            if (kitchenIngredientSelect.value === 'new') {
                kitchenNewFields.classList.remove('d-none');
                kitchenIngredientUnit.textContent = kitchenNewUnit.value.trim() || 'Birim';
            } else {
                kitchenNewFields.classList.add('d-none');
                kitchenIngredientUnit.textContent = selectedOption.dataset.unit || 'Birim';
            }
        }

        kitchenIngredientSelect.addEventListener('change', refreshKitchenIngredientInputs);
        kitchenNewUnit.addEventListener('input', refreshKitchenIngredientInputs);
        refreshKitchenIngredientInputs();
    </script>
</body>
</html>
