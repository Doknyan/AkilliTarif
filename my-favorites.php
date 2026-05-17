<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';
require_once 'functions/recipes.php';
require_once 'functions/favorites.php';
require_once 'functions/render.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_favorite') {
    $recipe_id = (int)$_POST['recipe_id'];
    removeFavorite($db, $_SESSION['user_id'], $recipe_id);
    $_SESSION['flash_success'] = 'Tarif favorilerinizden çıkarıldı.';
    header("Location: my-favorites.php");
    exit;
}

$success_message = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$favorites = getUserFavorites($db, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorilerim - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body class="favorites-page">
    <header>
        <?php
            require_once 'functions/header.php';
        ?>
    </header>

    <main class="container py-5 mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold mb-0">Favorilerim</h1>
            
            <?php if ($favorites): ?>
                <div class="d-flex align-items-center gap-3 bg-white p-3 rounded-4 shadow-sm">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="pdf_layout" id="pdf_horizontal" value="horizontal" checked>
                        <label class="form-check-label" for="pdf_horizontal">Yatay</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="pdf_layout" id="pdf_vertical" value="vertical">
                        <label class="form-check-label" for="pdf_vertical">Dikey</label>
                    </div>
                    <button type="button" class="btn btn-warning fw-bold" id="createPdfBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-pdf me-1" viewBox="0 0 16 16">
                            <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                            <path d="M4.603 12.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.68 7.68 0 0 1 1.482-.645 19.701 19.701 0 0 0 1.062-2.227 7.269 7.269 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.192.12.54.1.935-.022.41-.131.827-.248 1.218a21.335 21.335 0 0 1-.694 1.956c.038.1.08.195.125.289.444.928.944 1.769 1.485 2.522.351.488.767.913 1.232 1.217.433.283.903.43 1.353.354a.67.67 0 0 1 .471.256c.111.152.12.35.045.534-.081.192-.262.333-.513.415-.425.139-.991.065-1.58-.238-1.028-.528-2.099-1.488-3.085-2.747a14.508 14.508 0 0 1-1.6 2.032c-.503.541-.95.892-1.393 1.085-.39.17-.743.23-1.001.23zm.31-.552c.166.027.347-.046.53-.191.2-.159.389-.377.572-.657a11.11 11.11 0 0 0-.521.748c-.244.382-.457.69-.58.85a.24.24 0 0 1-.001.001l.001-.001-.001.05zM5.56 9.603c-.22.124-.432.252-.636.387.05-.152.133-.302.26-.445a1.868 1.868 0 0 1 .687-.455c-.105.158-.21.332-.311.513zm2.803-5.072c.04-.109.044-.216.037-.262a.19.19 0 0 0-.017-.03.111.111 0 0 0-.044-.012c-.04.009-.09.051-.138.171a1.74 1.74 0 0 0-.014.58c.069-.147.132-.294.176-.447zm0 3.96c.128-.316.226-.612.3-.885a14.717 14.717 0 0 0-.616 1.433c.122.106.216.279.317.452zm2.145.313c.148.243.3.468.455.674a11.83 11.83 0 0 0 .52-.52 1.8 1.8 0 0 0-.425-.103.958.958 0 0 0-.55.051l-.548.252c.15.111.3.222.548.347z"/>
                        </svg>
                        PDF Oluştur
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($favorites): ?>
            <?php render_recipe_cards($favorites, true); ?>
            
            <!-- PDF İçin Yazdırma Bölümü (Normalde gizli) -->
            <div id="printArea">
                <?php foreach ($favorites as $fav): 
                    $fav_ingredients = getRecipeIngredients($db, $fav['recipe_id']);
                ?>
                    <div class="print-page">
                        <div class="print-container">
                            <div class="print-image-side">
                                <img src="img/recipe/<?php echo htmlspecialchars($fav['image_path'] ?: 'default-food.jpg'); ?>" class="print-image">
                            </div>
                            <div class="print-info-side">
                                <h1 class="print-title"><?php echo htmlspecialchars($fav['title']); ?></h1>
                                <div class="print-meta">
                                    <span>⏱ <?php echo $fav['preparation_time']; ?> dk</span> |
                                    <span>🔥 <?php echo $fav['calories']; ?> kcal</span> |
                                    <span>👤 <?php echo htmlspecialchars($fav['chef_name']); ?></span>
                                </div>
                                <div class="print-diet">
                                    <?php if ($fav['is_vegan']): ?><span class="print-badge">Vegan</span><?php endif; ?>
                                    <?php if ($fav['is_vegetarian']): ?><span class="print-badge">Vejetaryen</span><?php endif; ?>
                                    <?php if ($fav['is_lactose_free']): ?><span class="print-badge">Laktozsuz</span><?php endif; ?>
                                    <?php if ($fav['is_gluten_free']): ?><span class="print-badge">Glutensiz</span><?php endif; ?>
                                    <?php if ($fav['is_sugar_free']): ?><span class="print-badge">Şekersiz</span><?php endif; ?>
                                </div>
                                <div class="print-section">
                                    <h3 class="print-section-title">Malzemeler</h3>
                                    <ul class="print-ingredients">
                                        <?php foreach ($fav_ingredients as $ing): ?>
                                            <li><?php echo htmlspecialchars($ing['ingredient_name']); ?>: <?php echo (float)$ing['amount'] . ' ' . $ing['unit']; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="print-section">
                                    <h3 class="print-section-title">Hazırlanışı</h3>
                                    <div class="print-instructions">
                                        <?php echo nl2br(htmlspecialchars($fav['instructions'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning border-0 shadow-sm">Henüz favori tarifiniz yok.</div>
        <?php endif; ?>
    </main>

    <style>
        #printArea { display: none; }
        @media print {
            body * { visibility: hidden; }
            #printArea, #printArea * { visibility: visible; }
            #printArea { display: block !important; position: absolute; left: 0; top: 0; width: 100%; }
            .print-page { page-break-after: always; min-height: 100vh; padding: 10mm; }
            
            /* Yatay Düzen */
            .print-layout-horizontal .print-container { display: flex; gap: 30px; align-items: flex-start; }
            .print-layout-horizontal .print-image-side { flex: 0 0 45%; }
            .print-layout-horizontal .print-info-side { flex: 1; }
            
            /* Dikey Düzen */
            .print-layout-vertical .print-container { display: block; }
            .print-layout-vertical .print-image-side { width: 100%; margin-bottom: 20px; text-align: center; }
            .print-layout-vertical .print-image { max-height: 40vh; width: auto; }
            
            .print-image { width: 100%; border-radius: 15px; }
            .print-title { font-size: 28pt; font-weight: bold; margin-bottom: 10px; color: #000; }
            .print-meta { font-size: 12pt; color: #666; margin-bottom: 15px; }
            .print-diet { margin-bottom: 20px; }
            .print-badge { background: #eee; padding: 2pt 8pt; border-radius: 5pt; font-size: 10pt; margin-right: 5pt; border: 1px solid #ddd; }
            .print-section { margin-top: 20px; }
            .print-section-title { font-size: 16pt; font-weight: bold; border-bottom: 2px solid #ffc107; padding-bottom: 5px; margin-bottom: 10px; }
            .print-ingredients { list-style: square; padding-left: 20px; }
            .print-instructions { line-height: 1.6; font-size: 11pt; text-align: justify; }
        }
    </style>

    <script>
        document.getElementById('createPdfBtn').addEventListener('click', function() {
            const layout = document.querySelector('input[name="pdf_layout"]:checked').value;
            const printArea = document.getElementById('printArea');
            
            printArea.classList.remove('print-layout-horizontal', 'print-layout-vertical');
            printArea.classList.add('print-layout-' + layout);
            
            window.print();
        });
    </script>
</body>
</html>
