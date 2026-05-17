<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';
require_once 'functions/recipes.php';
require_once 'functions/render.php';

$is_logged_in = isLoggedIn();
$current_role = $is_logged_in ? strtolower($_SESSION['role'] ?? 'user') : '';

// Aktif şefleri ve tarif sayılarını getir
$stmt = $db->query("
    SELECT u.user_id, u.username, COUNT(r.recipe_id) as recipe_count, u.profile_image 
    FROM users u
    JOIN recipes r ON u.user_id = r.chef_id
    WHERE u.is_active = 1
    GROUP BY u.user_id
    ORDER BY recipe_count DESC
");
$chefs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şeflerimiz - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <style>
        .chef-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 20px;
        }
        .chef-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
        }
        .chef-avatar {
            width: 100px;
            height: 100px;
            background-color: #ffc107;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <header>
        <?php require_once 'functions/header.php'; ?>
    </header>

    <main class="container py-5 mt-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Usta Şeflerimiz</h1>
            <p class="text-muted">Lezzetli tariflerin arkasındaki yetenekli isimlerle tanışın.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($chefs as $chef): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card chef-card shadow-sm p-4 text-center h-100">
                        <div class="chef-avatar">
                            <?php
                            if ($chef['profile_image']):
                                echo '<img src="img/profiles/' . htmlspecialchars($chef['profile_image']) . '" alt="' . htmlspecialchars($chef['username']) . '" class="img-fluid rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">';
                            else:
                                echo strtoupper(substr($chef['username'], 0, 1));
                            endif;  
                            ?>
                        </div>
                        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($chef['username']); ?></h4>
                        <p class="text-muted small mb-3"><?php echo $chef['recipe_count']; ?> Paylaşılan Tarif</p>
                        <a href="chef-recipes.php?id=<?php echo $chef['user_id']; ?>" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">Tariflerini Gör</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($chefs)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Henüz kayıtlı bir şef bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php require_once 'functions/footer.php'; ?>
</body>
</html>