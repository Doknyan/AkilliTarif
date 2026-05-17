<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';
require_once 'functions/admin.php';
require_once 'functions/recipes.php';

requireRole('admin');

$success_msg = '';
$error_msg = '';

// Pagination ayarları
$limit = 10;

// Sayfa numaralarını al
$users_page = isset($_GET['u_page']) ? max(1, (int)$_GET['u_page']) : 1;
$recipes_page = isset($_GET['r_page']) ? max(1, (int)$_GET['r_page']) : 1;
$requests_page = isset($_GET['req_page']) ? max(1, (int)$_GET['req_page']) : 1;

// Sıralama parametrelerini al
$u_sort = $_GET['u_sort'] ?? 'created_at';
$u_dir = $_GET['u_dir'] ?? 'DESC';
$r_sort = $_GET['r_sort'] ?? 'recipe_id';
$r_dir = $_GET['r_dir'] ?? 'DESC';
$req_sort = $_GET['req_sort'] ?? 'created_at';
$req_dir = $_GET['req_dir'] ?? 'DESC';

// Offset hesapla
$users_offset = ($users_page - 1) * $limit;
$recipes_offset = ($recipes_page - 1) * $limit;
$requests_offset = ($requests_page - 1) * $limit;

// İşlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['promote_id'])) {
        if (promoteToChef($db, (int)$_POST['promote_id'])) {
            $success_msg = "Kullanıcı başarıyla şef yapıldı!";
        } else {
            $error_msg = "İşlem başarısız oldu.";
        }
    } elseif (isset($_POST['reject_id'])) {
        if (rejectChefRequest($db, (int)$_POST['reject_id'])) {
            $success_msg = "Şeflik talebi reddedildi.";
        } else {
            $error_msg = "İşlem başarısız oldu.";
        }
    } elseif (isset($_POST['delete_user_id'])) {
        $del_id = (int)$_POST['delete_user_id'];
        if ($del_id === $_SESSION['user_id']) {
            $error_msg = "Kendinizi silemezsiniz!";
        } else {
            if (deleteUser($db, $del_id)) {
                $success_msg = "Kullanıcı başarıyla silindi.";
            } else {
                $error_msg = "Kullanıcı silinirken bir hata oluştu.";
            }
        }
    } elseif (isset($_POST['delete_recipe_id'])) {
        if (deleteRecipe($db, (int)$_POST['delete_recipe_id'])) {
            $success_msg = "Tarif başarıyla silindi.";
        } else {
            $error_msg = "Tarif silinirken bir hata oluştu.";
        }
    }
}

$stats = [
    'Kullanıcılar' => getTableCount($db, 'users'),
    'Tarifler' => getTableCount($db, 'recipes'),
    'Yorumlar' => getTableCount($db, 'comments'),
    'Malzemeler' => getTableCount($db, 'ingredients'),
    'Favoriler' => getTableCount($db, 'favorites'),
];

try {
    $role_counts = getRoleCounts($db);
    
    // Verileri çek
    $all_recipes = getAllRecipesAdminPaginated($db, $limit, $recipes_offset, $r_sort, $r_dir);
    $all_users = getAllUsersPaginated($db, $limit, $users_offset, $u_sort, $u_dir);
    $chef_requests = getChefRequestsPaginated($db, $limit, $requests_offset, $req_sort, $req_dir);
    
    // Toplam sayıları al (pagination için)
    $total_users = getUsersCount($db);
    $total_recipes = getRecipesCount($db);
    $total_requests = getChefRequestsCount($db);
    
    // Toplam sayfa sayıları
    $users_total_pages = ceil($total_users / $limit);
    $recipes_total_pages = ceil($total_recipes / $limit);
    $requests_total_pages = ceil($total_requests / $limit);

} catch (PDOException $e) {
    $dashboard_error = $e->getMessage();
}

function render_pagination($current_page, $total_pages, $param_name, $anchor) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation" class="mt-4"><ul class="pagination justify-content-center">';
    
    // Mevcut tüm GET parametrelerini alalım
    $query_params = $_GET;

    // Geri
    $disabled = ($current_page <= 1) ? 'disabled' : '';
    $query_params[$param_name] = $current_page - 1;
    $prev_url = '?' . http_build_query($query_params) . '#' . $anchor;
    $html .= "<li class='page-item $disabled'><a class='page-link' href='$prev_url'>&laquo;</a></li>";
    
    // Sayfa numaraları
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($total_pages > 7) {
            if ($i > 2 && $i < $total_pages - 1 && abs($i - $current_page) > 2) {
                if ($i == 3 || $i == $total_pages - 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                continue;
            }
        }
        $active = ($i == $current_page) ? 'active' : '';
        $query_params[$param_name] = $i;
        $url = '?' . http_build_query($query_params) . '#' . $anchor;
        $html .= "<li class='page-item $active'><a class='page-link' href='$url'>$i</a></li>";
    }
    
    // İleri
    $disabled = ($current_page >= $total_pages) ? 'disabled' : '';
    $query_params[$param_name] = $current_page + 1;
    $next_url = '?' . http_build_query($query_params) . '#' . $anchor;
    $html .= "<li class='page-item $disabled'><a class='page-link' href='$next_url'>&raquo;</a></li>";
    
    $html .= '</ul></nav>';
    return $html;
}

function render_sortable_th($label, $col_name, $current_sort, $current_dir, $sort_param, $dir_param, $anchor, $extra_classes = '') {
    $query_params = $_GET;
    $new_dir = ($current_sort === $col_name && $current_dir === 'ASC') ? 'DESC' : 'ASC';
    
    $query_params[$sort_param] = $col_name;
    $query_params[$dir_param] = $new_dir;
    
    $url = '?' . http_build_query($query_params) . '#' . $anchor;
    
    $icon = '';
    if ($current_sort === $col_name) {
        $icon = $current_dir === 'ASC' ? ' <i class="bi bi-arrow-up small"></i>' : ' <i class="bi bi-arrow-down small"></i>';
    }
    
    return "<th class='$extra_classes'><a href='$url' class='text-dark text-decoration-none'>$label$icon</a></th>";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
    <header>
        <?php require_once 'functions/header.php'; ?>
    </header>

    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Yönetim Paneli</h1>
                <p class="text-muted mb-0">Sistemi yönetin ve içerikleri kontrol edin.</p>
            </div>
        </div>

        <?php if (isset($dashboard_error)): ?>
            <div class="alert alert-danger">Dashboard verileri alınamadı: <?php echo htmlspecialchars($dashboard_error); ?></div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <section class="row g-3 mb-5">
            <?php foreach ($stats as $label => $value): ?>
                <div class="col-6 col-lg">
                    <div class="card shadow-sm h-100 border-0 border-start border-4 border-warning">
                        <div class="card-body">
                            <span class="text-muted small text-uppercase fw-bold"><?php echo htmlspecialchars($label); ?></span>
                            <h2 class="fw-bold mb-0"><?php echo $value; ?></h2>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Sekmeler -->
        <ul class="nav nav-pills mb-4 gap-2" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill px-4" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="true">
                    <i class="bi bi-people-fill me-1"></i> Kullanıcı Yönetimi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="recipes-tab" data-bs-toggle="tab" data-bs-target="#recipes" type="button" role="tab" aria-controls="recipes" aria-selected="false">
                    <i class="bi bi-journal-text me-1"></i> Tarif Yönetimi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab" aria-controls="requests" aria-selected="false">
                    <i class="bi bi-person-lines-fill me-1"></i> Şeflik Talepleri
                    <?php if (isset($chef_requests) && count($chef_requests) > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo count($chef_requests); ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="adminTabsContent">
            
            <!-- Kullanıcı Yönetimi Sekmesi -->
            <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <?php echo render_sortable_th('ID', 'user_id', $u_sort, $u_dir, 'u_sort', 'u_dir', 'users', 'ps-4'); ?>
                                        <?php echo render_sortable_th('Kullanıcı Adı', 'username', $u_sort, $u_dir, 'u_sort', 'u_dir', 'users'); ?>
                                        <?php echo render_sortable_th('E-posta', 'email', $u_sort, $u_dir, 'u_sort', 'u_dir', 'users'); ?>
                                        <?php echo render_sortable_th('Rol', 'role', $u_sort, $u_dir, 'u_sort', 'u_dir', 'users'); ?>
                                        <?php echo render_sortable_th('Kayıt Tarihi', 'created_at', $u_sort, $u_dir, 'u_sort', 'u_dir', 'users'); ?>
                                        <th class="text-end pe-4">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_users as $u): ?>
                                        <tr>
                                            <td class="ps-4 text-muted">#<?php echo $u['user_id']; ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($u['username']); ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td>
                                                <?php 
                                                    $r = $u['role'];
                                                    $bClass = $r === 'admin' ? 'bg-danger' : ($r === 'chef' ? 'bg-warning text-dark' : 'bg-secondary');
                                                ?>
                                                <span class="badge <?php echo $bClass; ?>"><?php echo strtoupper($r); ?></span>
                                            </td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($u['created_at'])); ?></td>
                                            <td class="text-end pe-4">
                                                <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı tamamen silmek istediğinize emin misiniz? (Tüm tarifleri, yorumları silinir!)');">
                                                    <input type="hidden" name="delete_user_id" value="<?php echo $u['user_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">Siz</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php echo render_pagination($users_page, $users_total_pages, 'u_page', 'users'); ?>
            </div>

            <!-- Tarif Yönetimi Sekmesi -->
            <div class="tab-pane fade" id="recipes" role="tabpanel" aria-labelledby="recipes-tab">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <?php echo render_sortable_th('ID', 'recipe_id', $r_sort, $r_dir, 'r_sort', 'r_dir', 'recipes', 'ps-4'); ?>
                                        <?php echo render_sortable_th('Tarif Adı', 'title', $r_sort, $r_dir, 'r_sort', 'r_dir', 'recipes'); ?>
                                        <?php echo render_sortable_th('Şef', 'chef_name', $r_sort, $r_dir, 'r_sort', 'r_dir', 'recipes'); ?>
                                        <?php echo render_sortable_th('Kalori', 'calories', $r_sort, $r_dir, 'r_sort', 'r_dir', 'recipes'); ?>
                                        <?php echo render_sortable_th('Süre', 'preparation_time', $r_sort, $r_dir, 'r_sort', 'r_dir', 'recipes'); ?>
                                        <th class="text-end pe-4">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_recipes as $recipe): ?>
                                        <tr>
                                            <td class="ps-4 text-muted">#<?php echo $recipe['recipe_id']; ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($recipe['title']); ?></td>
                                            <td><?php echo htmlspecialchars($recipe['chef_name'] ?? 'Bilinmiyor'); ?></td>
                                            <td><?php echo (int) $recipe['calories']; ?> kcal</td>
                                            <td><?php echo (int) $recipe['preparation_time']; ?> dk</td>
                                            <td class="text-end pe-4">
                                                <a href="recipe-detail.php?id=<?php echo $recipe['recipe_id']; ?>" class="btn btn-sm btn-outline-info" title="İncele">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                                <a href="edit-recipe.php?id=<?php echo $recipe['recipe_id']; ?>" class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bu tarifi silmek istediğinize emin misiniz?');">
                                                    <input type="hidden" name="delete_recipe_id" value="<?php echo $recipe['recipe_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($all_recipes)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">Henüz tarif yok.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php echo render_pagination($recipes_page, $recipes_total_pages, 'r_page', 'recipes'); ?>
            </div>

            <!-- Şeflik Talepleri Sekmesi -->
            <div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests-tab">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <?php echo render_sortable_th('Kullanıcı Adı', 'username', $req_sort, $req_dir, 'req_sort', 'req_dir', 'requests', 'ps-4'); ?>
                                        <?php echo render_sortable_th('E-posta', 'email', $req_sort, $req_dir, 'req_sort', 'req_dir', 'requests'); ?>
                                        <?php echo render_sortable_th('Kayıt Tarihi', 'created_at', $req_sort, $req_dir, 'req_sort', 'req_dir', 'requests'); ?>
                                        <th>Belge</th>
                                        <th class="text-end pe-4">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chef_requests as $user): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <?php if ($user['chef_document']): ?>
                                                    <a href="img/chef_docs/<?php echo $user['chef_document']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                        <i class="bi bi-file-earmark-text-fill me-1"></i> Belgeyi Gör
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">Belge yok</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı şef yapmak istediğinize emin misiniz?');">
                                                    <input type="hidden" name="promote_id" value="<?php echo $user['user_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Şef Yap
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bu şeflik talebini reddetmek istediğinize emin misiniz?');">
                                                    <input type="hidden" name="reject_id" value="<?php echo $user['user_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3">
                                                        <i class="bi bi-x-circle-fill me-1"></i> Reddet
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($chef_requests)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Şu an bekleyen talep bulunmuyor.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php echo render_pagination($requests_page, $requests_total_pages, 'req_page', 'requests'); ?>
            </div>

        </div>
    </main>

    <script>
    // URL'deki hash'e göre doğru sekmeyi aç
    document.addEventListener("DOMContentLoaded", function() {
        var hash = window.location.hash;
        if (hash) {
            var tabEl = document.querySelector('button[data-bs-target="' + hash + '"]');
            if (tabEl) {
                var tab = new bootstrap.Tab(tabEl);
                tab.show();
            }
        }
    });
    </script>
</body>
</html>
