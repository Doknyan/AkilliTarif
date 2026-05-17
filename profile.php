<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Profil Bilgilerini Güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $calorie_target = (int)$_POST['daily_calorie_target'];
    $profile_image = null;

    // Resim Yükleme İşlemi
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileSize = $_FILES['profile_image']['size'];
        $fileType = $_FILES['profile_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = './img/profiles/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $profile_image = $newFileName;
            }
        }
    }

    try {
        // E-posta veya kullanıcı adı çakışması kontrolü (kendisi hariç)
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE (email = ? OR username = ?) AND user_id != ?");
        $stmt->execute([$email, $username, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Bu e-posta veya kullanıcı adı zaten kullanımda.";
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, daily_calorie_target = ?";
            $params = [$username, $email, $calorie_target];
            
            if ($profile_image) {
                $sql .= ", profile_image = ?";
                $params[] = $profile_image;
            }
            
            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;

            $stmt = $db->prepare($sql);
            if ($stmt->execute($params)) {
                $success = "Profil bilgileriniz başarıyla güncellendi.";
                $_SESSION['username'] = $username; // Session'ı güncelle
            } else {
                $error = "Güncelleme sırasında bir hata oluştu.";
            }
        }
    } catch (PDOException $e) {
        $error = "Bir hata oluştu: " . $e->getMessage();
    }
}

// Şifre Değiştirme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    try {
        $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch();

        if ($userData && password_verify($current_pass, $userData['password'])) {
            if ($new_pass === $confirm_pass) {
                if (strlen($new_pass) >= 6) {
                    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    if ($stmt->execute([$hashed_pass, $user_id])) {
                        $success = "Şifreniz başarıyla değiştirildi.";
                    }
                } else {
                    $error = "Yeni şifre en az 6 karakter olmalıdır.";
                }
            } else {
                $error = "Yeni şifreler eşleşmiyor.";
            }
        } else {
            $error = "Mevcut şifreniz hatalı.";
        }
    } catch (PDOException $e) {
        $error = "Bir hata oluştu: " . $e->getMessage();
    }
}

// Şeflik Talebi Gönderme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_chef_role'])) {
    $chef_document = null;

    if (isset($_FILES['chef_document']) && $_FILES['chef_document']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['chef_document']['tmp_name'];
        $fileName = $_FILES['chef_document']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('pdf', 'jpg', 'jpeg', 'png');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = 'doc_' . md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = './img/chef_docs/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $chef_document = $newFileName;
            } else {
                $error = "Belge yüklenirken bir hata oluştu.";
            }
        } else {
            $error = "Sadece PDF, JPG ve PNG dosyaları kabul edilir.";
        }
    } else {
        $error = "Lütfen şef olduğunuzu belgeleyen bir dosya seçin.";
    }

    if (!$error && $chef_document) {
        try {
            $stmt = $db->prepare("UPDATE users SET requested_role = 'chef', chef_document = ? WHERE user_id = ?");
            if ($stmt->execute([$chef_document, $user_id])) {
                $success = "Şeflik talebiniz ve belgeniz başarıyla iletildi. Admin onayı bekleniyor.";
            }
        } catch (PDOException $e) {
            $error = "Talep sırasında bir hata oluştu.";
        }
    }
}

$user = getUserById($db, $user_id) ?: [];
// requested_role bilgisini de alalım
$stmt = $db->prepare("SELECT requested_role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user['requested_role'] = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Ayarları - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body class="profile-page">
    <header>
        <?php
            require_once 'functions/header.php';
        ?>
    </header>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <?php if($error): ?> <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div> <?php endif; ?>
                <?php if($success): ?> <div class="alert alert-success shadow-sm"><?php echo $success; ?></div> <?php endif; ?>

                <div class="row g-4">
                    <!-- Bilgileri Düzenle -->
                    <div class="col-md-7">
                        <div class="card shadow-sm border-0 rounded-4">
                            <div class="card-body p-4">
                                <h3 class="fw-bold mb-4">Profil Bilgileri</h3>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="text-center mb-4">
                                        <?php if (!empty($user['profile_image'])): ?>
                                            <img src="img/profiles/<?php echo $user['profile_image']; ?>" class="rounded-circle border shadow-sm mb-3" style="width: 120px; height: 100px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center border shadow-sm mb-3" style="width: 120px; height: 120px; font-size: 3rem; font-weight: bold;">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Profil Resmi</label>
                                            <input type="file" name="profile_image" class="form-control form-control-sm rounded-pill">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Kullanıcı Adı</label>
                                        <input type="text" name="username" class="form-control rounded-pill px-3" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">E-posta Adresi</label>
                                        <input type="email" name="email" class="form-control rounded-pill px-3" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Günlük Kalori Hedefi (kcal)</label>
                                        <input type="number" name="daily_calorie_target" class="form-control rounded-pill px-3" value="<?php echo (int)($user['daily_calorie_target'] ?? 2000); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Rolünüz</label>
                                        <input type="text" class="form-control rounded-pill px-3 bg-light" value="<?php echo ucfirst(htmlspecialchars($user['role'] ?? 'user')); ?>" readonly>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-warning w-100 rounded-pill fw-bold py-2 mt-2 shadow-sm">Bilgileri Güncelle</button>
                                </form>

                                <?php if ($user['role'] === 'user'): ?>
                                    <hr class="my-4">
                                    <div class="chef-request-section p-3 rounded-4 bg-light border">
                                        <h5 class="fw-bold mb-2">Şeflik Talebi</h5>
                                        <?php if ($user['requested_role'] === 'chef'): ?>
                                            <div class="alert alert-info small mb-0">
                                                <i class="bi bi-clock-history"></i> Şeflik talebiniz iletildi, admin onayı bekleniyor.
                                            </div>
                                        <?php else: ?>
                                            <p class="small text-muted mb-3">Tarif eklemek ve şef özelliklerinden yararlanmak için şef olma talebinde bulunabilirsiniz.</p>
                                            <form method="POST" enctype="multipart/form-data" class="bg-white p-3 rounded border">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Doğrulama Belgesi (PDF veya Resim)</label>
                                                    <input type="file" name="chef_document" class="form-control form-control-sm" required>
                                                    <div class="form-text extra-small">Sertifika, diploma veya uzmanlık belgesi.</div>
                                                </div>
                                                <button type="submit" name="request_chef_role" class="btn btn-warning btn-sm w-100 rounded-pill fw-bold">Talebi Belgeyle Gönder</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Şifre Değiştir -->
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0 rounded-4">
                            <div class="card-body p-4">
                                <h3 class="fw-bold mb-4">Şifre Değiştir</h3>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Mevcut Şifre</label>
                                        <input type="password" name="current_password" class="form-control rounded-pill px-3" required>
                                    </div>
                                    <hr>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Yeni Şifre</label>
                                        <input type="password" name="new_password" class="form-control rounded-pill px-3" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Yeni Şifre (Tekrar)</label>
                                        <input type="password" name="confirm_password" class="form-control rounded-pill px-3" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-dark w-100 rounded-pill fw-bold py-2 mt-2 shadow-sm">Şifreyi Güncelle</button>
                                </form>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="text-muted small">Kayıt Tarihi: <?php echo htmlspecialchars($user['created_at'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>
