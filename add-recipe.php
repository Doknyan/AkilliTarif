<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/ingredients.php';
require_once 'functions/recipes.php';
require_once 'functions/auth.php';

requireRole(['admin', 'chef']);

$error = '';
$success = '';
$allowed_image_extensions = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];
$max_image_size = 5 * 1024 * 1024;
$ingredient_options = getIngredientOptions($db);
$valid_ingredient_ids = getValidIngredientIds($ingredient_options);
$ingredient_categories = getIngredientCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $preparation_time = (int) ($_POST['preparation_time'] ?? 0);
    $calories = (int) ($_POST['calories'] ?? 0);
    $image_path = null;
    $is_vegan = isset($_POST['is_vegan']) ? 1 : 0;
    $is_gluten_free = isset($_POST['is_gluten_free']) ? 1 : 0;
    $is_sugar_free = isset($_POST['is_sugar_free']) ? 1 : 0;
    $is_lactose_free = isset($_POST['is_lactose_free']) ? 1 : 0;
    $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
    $posted_ingredient_ids = $_POST['ingredient_id'] ?? [];
    $posted_ingredient_amounts = $_POST['ingredient_amount'] ?? [];
    $posted_new_ingredient_names = $_POST['new_ingredient_name'] ?? [];
    $posted_new_ingredient_units = $_POST['new_ingredient_unit'] ?? [];
    $posted_new_ingredient_categories = $_POST['new_ingredient_category'] ?? [];
    $recipe_ingredients = [];
    $pending_custom_ingredients = [];

    $ingredient_error = buildRecipeIngredientRows(
        $posted_ingredient_ids,
        $posted_ingredient_amounts,
        $posted_new_ingredient_names,
        $posted_new_ingredient_units,
        $posted_new_ingredient_categories,
        $valid_ingredient_ids,
        $recipe_ingredients,
        $pending_custom_ingredients
    );

    if ($ingredient_error !== null) {
        $error = $ingredient_error;
    }

    if ($title === '' || $instructions === '') {
        $error = 'Tarif adı ve hazırlanışı zorunludur.';
    } elseif ($error === '') {
        if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['recipe_image'];

            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $error = 'Görsel yüklenirken bir hata oluştu.';
            } elseif ($upload['size'] > $max_image_size) {
                $error = 'Görsel dosyası en fazla 5 MB olabilir.';
            } else {
                $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
                $image_info = @getimagesize($upload['tmp_name']);

                if (!in_array($extension, $allowed_image_extensions, true) || $image_info === false) {
                    $error = 'Lütfen JPG, PNG, WEBP, AVIF veya GIF formatında geçerli bir görsel yükleyin.';
                } else {
                    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'recipe';

                    if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
                        $error = 'Görsel klasörü yazılabilir değil.';
                    } else {
                        $image_path = 'recipe_' . $_SESSION['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                        $target_path = $upload_dir . DIRECTORY_SEPARATOR . $image_path;

                        if (!move_uploaded_file($upload['tmp_name'], $target_path)) {
                            $error = 'Görsel dosyası kaydedilemedi.';
                            $image_path = null;
                        }
                    }
                }
            }
        }

        if ($error === '') {
            try {
                $db->beginTransaction();

                $recipe_id = insertRecipe(
                    $db,
                    $_SESSION['user_id'],
                    $title,
                    $instructions,
                    $preparation_time ?: null,
                    $calories ?: null,
                    $is_vegan,
                    $is_gluten_free,
                    $is_sugar_free,
                    $is_lactose_free,
                    $is_vegetarian,
                    $image_path
                );

                if ($recipe_ingredients) {
                    addRecipeIngredients($db, $recipe_id, $recipe_ingredients);
                }

                foreach ($pending_custom_ingredients as $pending_custom) {
                    $ingredient_id = lookupOrCreateIngredient($db, $pending_custom['name'], $pending_custom['unit'], $pending_custom['category']);
                    addRecipeIngredients($db, $recipe_id, [$ingredient_id => $pending_custom['amount']]);
                }

                $db->commit();
                $success = 'Tarif başarıyla eklendi.';
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                if ($image_path) {
                    $saved_file = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $image_path;

                    if (is_file($saved_file)) {
                        unlink($saved_file);
                    }
                }

                $error = 'Tarif kaydedilemedi: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Tarif Ekle - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=1.1">
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body class="add-recipe-page">
    <header>
        <?php
            require_once 'functions/header.php';
        ?>
    </header>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="fw-bold mb-4">Yeni Tarif Ekle</h1>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="my-recipes.php" class="alert-link">Tariflerime dön</a>.</div>
                        <?php endif; ?>

                        <form method="POST" action="add-recipe.php" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Tarif adı</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Hazırlanışı</label>
                                <textarea name="instructions" class="form-control" rows="7" required></textarea>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0">Malzemeler</label>
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="addIngredientRow">Malzeme ekle</button>
                                </div>

                                <div class="recipe-ingredients-list" id="recipeIngredientsList">
                                    <div class="recipe-ingredient-row">
                                        <select name="ingredient_id[]" class="form-select recipe-ingredient-select">
                                            <option value="" data-unit="">Malzeme seçin</option>
                                            <option value="new" data-unit="">Yeni malzeme ekle</option>
                                            <?php foreach ($ingredient_options as $ingredient): ?>
                                                <option value="<?php echo (int) $ingredient['ingredient_id']; ?>" data-unit="<?php echo htmlspecialchars($ingredient['unit']); ?>">
                                                    <?php echo htmlspecialchars($ingredient['ingredient_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <div class="recipe-ingredient-custom-fields d-none mt-2">
                                            <input type="text" name="new_ingredient_name[]" class="form-control recipe-new-ingredient-name mb-2" placeholder="Yeni malzeme adı">
                                            <input type="text" name="new_ingredient_unit[]" class="form-control recipe-new-ingredient-unit mb-2" placeholder="Birim (gram, adet, su bardağı)">
                                            <select name="new_ingredient_category[]" class="form-select recipe-new-ingredient-category">
                                                <option value="">Kategori seçin</option>
                                                <?php foreach ($ingredient_categories as $category): ?>
                                                    <option value="<?php echo htmlspecialchars($category); ?>">
                                                        <?php echo htmlspecialchars($category); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <input type="number" name="ingredient_amount[]" class="form-control recipe-ingredient-amount" min="0" step="1" placeholder="Miktar">
                                        <span class="recipe-ingredient-unit">Birim</span>
                                        <button type="button" class="btn btn-outline-danger btn-sm recipe-ingredient-remove">Kaldır</button>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Süre (dk)</label>
                                    <input type="number" name="preparation_time" class="form-control" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kalori</label>
                                    <input type="number" name="calories" class="form-control" min="0">
                                </div>
                            </div>

                            <div class="my-4">
                                <label class="form-label fw-semibold">Görsel dosyası</label>
                                <div class="recipe-upload-dropzone" id="recipeUploadDropzone">
                                    <input type="file" name="recipe_image" id="recipe_image" class="visually-hidden" accept="image/*">
                                    <div class="recipe-upload-preview d-none" id="recipeUploadPreviewWrap">
                                        <img src="" alt="Seçilen tarif görseli" id="recipeUploadPreview">
                                    </div>
                                    <div class="recipe-upload-copy">
                                        <strong id="recipeUploadTitle">Görseli buraya sürükleyip bırakın</strong>
                                        <span id="recipeUploadFileName">JPG, PNG, WEBP, AVIF veya GIF dosyası seçebilirsiniz.</span>
                                    </div>
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="selectRecipeImage">Görsel seç</button>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-3 my-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_vegan" id="is_vegan">
                                    <label class="form-check-label" for="is_vegan">Vegan</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_gluten_free" id="is_gluten_free">
                                    <label class="form-check-label" for="is_gluten_free">Glutensiz</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_sugar_free" id="is_sugar_free">
                                    <label class="form-check-label" for="is_sugar_free">Şekersiz</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_lactose_free" id="is_lactose_free">
                                    <label class="form-check-label" for="is_lactose_free">Laktozsuz</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_vegetarian" id="is_vegetarian">
                                    <label class="form-check-label" for="is_vegetarian">Vejetaryen</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning fw-bold">Tarifi kaydet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        const dropzone = document.getElementById('recipeUploadDropzone');
        const fileInput = document.getElementById('recipe_image');
        const selectButton = document.getElementById('selectRecipeImage');
        const fileName = document.getElementById('recipeUploadFileName');
        const previewWrap = document.getElementById('recipeUploadPreviewWrap');
        const preview = document.getElementById('recipeUploadPreview');
        const ingredientsList = document.getElementById('recipeIngredientsList');
        const addIngredientRow = document.getElementById('addIngredientRow');

        function updateIngredientUnit(row) {
            const select = row.querySelector('.recipe-ingredient-select');
            const unit = row.querySelector('.recipe-ingredient-unit');
            const customFields = row.querySelector('.recipe-ingredient-custom-fields');
            const customUnitInput = row.querySelector('.recipe-new-ingredient-unit');

            if (select.value === 'new') {
                customFields.classList.remove('d-none');
                unit.textContent = customUnitInput.value.trim() || 'Birim';
            } else {
                customFields.classList.add('d-none');
                const selectedOption = select.options[select.selectedIndex];
                unit.textContent = selectedOption.dataset.unit || 'Birim';
            }
        }

        function refreshIngredientRow(row) {
            row.querySelector('.recipe-ingredient-select').value = '';
            row.querySelector('.recipe-ingredient-amount').value = '';
            row.querySelector('.recipe-ingredient-unit').textContent = 'Birim';
            row.querySelector('.recipe-ingredient-custom-fields').classList.add('d-none');
            row.querySelector('.recipe-new-ingredient-name').value = '';
            row.querySelector('.recipe-new-ingredient-unit').value = '';
        }

        function refreshIngredientRemoveButtons() {
            const rows = ingredientsList.querySelectorAll('.recipe-ingredient-row');

            rows.forEach((row) => {
                row.querySelector('.recipe-ingredient-remove').disabled = rows.length === 1;
            });
        }

        function addEmptyIngredientRow() {
            const firstRow = ingredientsList.querySelector('.recipe-ingredient-row');
            const newRow = firstRow.cloneNode(true);

            refreshIngredientRow(newRow);
            ingredientsList.appendChild(newRow);
            refreshIngredientRemoveButtons();
        }

        addIngredientRow.addEventListener('click', addEmptyIngredientRow);

        ingredientsList.addEventListener('change', (event) => {
            if (event.target.classList.contains('recipe-ingredient-select')) {
                updateIngredientUnit(event.target.closest('.recipe-ingredient-row'));
            }
        });

        ingredientsList.addEventListener('input', (event) => {
            if (event.target.classList.contains('recipe-new-ingredient-unit')) {
                const row = event.target.closest('.recipe-ingredient-row');
                if (row.querySelector('.recipe-ingredient-select').value === 'new') {
                    row.querySelector('.recipe-ingredient-unit').textContent = event.target.value.trim() || 'Birim';
                }
            }
        });

        ingredientsList.addEventListener('click', (event) => {
            if (!event.target.classList.contains('recipe-ingredient-remove')) {
                return;
            }

            const rows = ingredientsList.querySelectorAll('.recipe-ingredient-row');

            if (rows.length > 1) {
                event.target.closest('.recipe-ingredient-row').remove();
                refreshIngredientRemoveButtons();
            }
        });

        refreshIngredientRemoveButtons();

        function showSelectedImage(file) {
            if (!file) {
                return;
            }

            fileName.textContent = file.name;

            if (file.type.startsWith('image/')) {
                preview.src = URL.createObjectURL(file);
                previewWrap.classList.remove('d-none');
            }
        }

        selectButton.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('click', (event) => {
            if (event.target !== selectButton) {
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', () => {
            showSelectedImage(fileInput.files[0]);
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            const file = event.dataTransfer.files[0];

            if (!file) {
                return;
            }

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            showSelectedImage(file);
        });
    </script>
</body>
</html>
