<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    } 
    require_once 'functions/auth.php';
    $is_logged_in = isLoggedIn();
    $current_role = $is_logged_in ? strtolower($_SESSION['role'] ?? 'user') : '';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<nav class="navbar navbar-expand-lg navbar-dark bg-transparent p-3 site-navbar">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-egg-fried me-1"></i> AKILLI TARİF</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door-fill me-1"></i> Ana Sayfa </a></li>
                        <li class="nav-item"><a class="nav-link" href="explore.php"><i class="bi bi-compass-fill me-1"></i> Keşfet </a></li>
                        <li class="nav-item"><a class="nav-link" href="all-recipes.php"><i class="bi bi-journal-text me-1"></i> Tarifler </a></li>
                        <li class="nav-item"><a class="nav-link" href="advanced-search.php"><i class="bi bi-search me-1"></i> Arama </a></li>
                        <li class="nav-item"><a class="nav-link" href="chefs.php"><i class="bi bi-person-badge-fill me-1"></i> Şeflerimiz </a></li>
                        
                        <?php if ($is_logged_in): ?>
                            <?php if ($current_role === 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link text-warning fw-bold" href="admin-dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Yönetim Paneli</a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDrop" role="button" data-bs-toggle="dropdown">
                                    <?php 
                                    $stmt = $db->prepare("SELECT profile_image FROM users WHERE user_id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $header_p_img = $stmt->fetchColumn();
                                    
                                    if ($header_p_img): ?>
                                        <img src="img/profiles/<?php echo $header_p_img; ?>" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-warning text-white rounded-circle me-2 d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 0.8rem; font-weight: bold;">
                                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                    <?php if ($current_role === 'admin'): ?>
                                        <li><a class="dropdown-item" href="admin-dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Yönetim Paneli</a></li>
                                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-fill me-2"></i>Profilim</a></li>
                                        <li><a class="dropdown-item" href="my-recipes.php"><i class="bi bi-journal-bookmark-fill me-2"></i>Tariflerim</a></li>
                                        <li><a class="dropdown-item" href="add-recipe.php"><i class="bi bi-plus-circle-fill me-2"></i>Yeni tarif ekle</a></li>
                                        <li><a class="dropdown-item" href="my-kitchen.php"><i class="bi bi-basket-fill me-2"></i>Dolabım</a></li>
                                        <li><a class="dropdown-item" href="my-favorites.php"><i class="bi bi-heart-fill me-2"></i>Favorilerim</a></li>
                                    <?php elseif ($current_role === 'chef'): ?>
                                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-fill me-2"></i>Profilim</a></li>
                                        <li><a class="dropdown-item" href="my-recipes.php"><i class="bi bi-journal-bookmark-fill me-2"></i>Tariflerim</a></li>
                                        <li><a class="dropdown-item" href="add-recipe.php"><i class="bi bi-plus-circle-fill me-2"></i>Yeni tarif ekle</a></li>
                                        <li><a class="dropdown-item" href="my-kitchen.php"><i class="bi bi-basket-fill me-2"></i>Dolabım</a></li>
                                        <li><a class="dropdown-item" href="my-favorites.php"><i class="bi bi-heart-fill me-2"></i>Favorilerim</a></li>
                                    <?php else: ?>
                                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-fill me-2"></i>Profilim</a></li>
                                        <li><a class="dropdown-item" href="my-kitchen.php"><i class="bi bi-basket-fill me-2"></i>Dolabım</a></li>
                                        <li><a class="dropdown-item" href="my-favorites.php"><i class="bi bi-heart-fill me-2"></i>Favorilerim</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Çıkış yap</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a href="auth.php" class="btn btn-warning text-dark fw-bold ms-lg-3"><i class="bi bi-box-arrow-in-right me-1"></i> Giriş / Kaydol</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>