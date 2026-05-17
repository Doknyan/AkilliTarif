<?php
session_start();
require_once 'functions/db.php';

// Rastgele sıralama ve beğeni/yorum istatistikleri
$stmt = $db->query("
    SELECT r.recipe_id, r.title, r.image_path, u.username as chef_name,
           (SELECT COUNT(*) FROM favorites WHERE recipe_id = r.recipe_id) as fav_count,
           (SELECT COUNT(*) FROM comments WHERE recipe_id = r.recipe_id) as comment_count
    FROM recipes r
    LEFT JOIN users u ON r.chef_id = u.user_id
    ORDER BY fav_count DESC, comment_count DESC
");
$recipes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keşfet - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css?v=1.4">
    <style>
        .explore-header {
            max-width: 600px;
            margin: 0 auto 2rem auto;
            text-align: center;
        }
        /* Instagram Style Grid */
        .insta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px; /* Çok ince bir boşluk (Instagram tarzı) */
            max-width: 935px; /* Instagram web max width */
            margin: 0 auto;
            padding: 0 15px;
        }
        @media (max-width: 576px) {
            .insta-grid {
                gap: 2px;
                padding: 0; /* Mobilde tam ekran */
            }
        }
        .insta-item {
            position: relative;
            aspect-ratio: 1 / 1; /* Tam bir kare */
            overflow: hidden;
            background-color: #efefef;
            cursor: pointer;
        }
        /* Bazı görselleri daha büyük (2x2) yapmak için rastgele sınıflar eklenebilir ama standart 3'lü grid daha ikoniktir */
        .insta-item img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Görseli bozmadan kareye sığdır */
            transition: transform 0.3s ease;
        }
        
        .insta-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.4); /* Yarı saydam siyah arka plan */
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
            color: #fff;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .insta-item:hover .insta-overlay {
            opacity: 1;
        }
        .insta-overlay-stats {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .insta-overlay-stats i {
            margin-right: 0.4rem;
            font-size: 1.3rem;
        }
        .insta-overlay-chef {
            position: absolute;
            bottom: 12px;
            left: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .insta-overlay-chef i {
            margin-right: 5px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body class="bg-light">
    <header>
        <?php require_once 'functions/header.php'; ?>
    </header>

    <main class="py-4 mb-5" style="margin-top: 80px;">
        <div class="explore-header">
            <h2 class="fw-bold"><i class="bi bi-compass text-warning"></i> Keşfet</h2>
            <p class="text-muted small">Senin için seçtiğimiz yeni lezzetleri keşfet. Görselin üzerine gelerek beğenileri görebilirsin.</p>
        </div>

        <div class="insta-grid">
            <?php foreach ($recipes as $r): 
                $favs = (int)$r['fav_count'];
                $comments = (int)$r['comment_count'];
            ?>
                <div class="insta-item" onclick="window.location.href='recipe-detail.php?id=<?php echo $r['recipe_id']; ?>'">
                    <img src="img/recipe/<?php echo htmlspecialchars($r['image_path'] ?: 'default-food.jpg'); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>">
                    <div class="insta-overlay">
                        <div class="insta-overlay-stats">
                            <i class="bi bi-heart-fill"></i> <?php echo $favs; ?>
                        </div>
                        <div class="insta-overlay-stats">
                            <i class="bi bi-chat-fill"></i> <?php echo $comments; ?>
                        </div>
                        <div class="insta-overlay-chef">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($r['chef_name']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php 
    if(file_exists('functions/footer.php')) {
        require_once 'functions/footer.php'; 
    }
    ?>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
