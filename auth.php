<?php
session_start();
require_once 'functions/db.php';
require_once 'functions/auth.php';

$error = "";
$success = "";

// FORM İŞLEMLERİ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // B0T DENETİMİ 
    if (!empty($_POST['address_field'])) {
        die("Güvenlik ihlali: Bot algılandı!"); 
        exit;
    }

    // Kayıt İşlemi
    if (isset($_POST['register'])) {
        $username = htmlspecialchars($_POST['username']);
        $email    = htmlspecialchars($_POST['email']);
        $password = $_POST['password'];
        $requested_role = isset($_POST['request_chef']) ? 'chef' : 'user';

        $result = registerUser($db, $username, $email, $password, $requested_role);
        if ($result === true) {
            $success = "Hesabınız başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz.";
        } else {
            $error = is_string($result) ? $result : "Bilinmeyen bir hata oluştu."; 
        }
    }
    // Doğrulama İşlemi
    elseif (isset($_POST['verify'])) {
        $tempUser = $_SESSION['temp_user'] ?? null;
        $code  = $_POST['activation_code'];

        if ($tempUser && $tempUser['code'] === $code && time() <= $tempUser['expiry']) {
            if (commitUserRegistration($db, $tempUser)) {
                unset($_SESSION['temp_user']);
                $success = "Hesabınız başarıyla oluşturuldu ve aktifleştirildi! Şimdi giriş yapabilirsiniz.";
            } else {
                $error = "Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.";
            }
        } else {
            $error = "Hatalı kod veya süresi dolmuş (5 dakika)! Lütfen tekrar kayıt olun.";
            if (time() > ($tempUser['expiry'] ?? 0)) unset($_SESSION['temp_user']);
        }
    }
    // Şifre Sıfırlama Talebi
    elseif (isset($_POST['forgot_password'])) {
        $email = htmlspecialchars($_POST['email']);
        if (requestPasswordReset($db, $email)) {
            $_SESSION['reset_email'] = $email;
            $success = "Şifre sıfırlama kodu e-postanıza gönderildi.";
        } else {
            $error = "Bu e-posta adresiyle kayıtlı aktif bir hesap bulunamadı.";
        }
    }
    // Şifre Sıfırlama İşlemi
    elseif (isset($_POST['reset_password'])) {
        $email = $_SESSION['reset_email'] ?? '';
        $code  = $_POST['reset_code'];
        $pass  = $_POST['new_password'];

        if (resetPasswordWithCode($db, $email, $code, $pass)) {
            unset($_SESSION['reset_email']);
            $success = "Şifreniz başarıyla güncellendi! Giriş yapabilirsiniz.";
        } else {
            $error = "Hatalı kod veya süresi dolmuş! Lütfen tekrar deneyin.";
        }
    }
    // Standart Kullanıcı/Şef Giriş İşlemi
    elseif (isset($_POST['login'])) {
        $email = $_POST['email'];
        $pass  = $_POST['password'];
        $user  = loginUser($db, $email, $pass);

        if ($user) {
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $redirect = ($user['role'] === 'admin') ? 'admin-dashboard.php' : 'index.php';
            header("Location: " . $redirect);
            exit;
        } else {
            $error = "Hatalı e-posta veya şifre!";
        }
    }
    // Admin Giriş İşlemi
    elseif (isset($_POST['admin_login'])) {
        $email = $_POST['email'];
        $pass  = $_POST['password'];
        $user  = loginUser($db, $email, $pass);

        if ($user) {
            if ($user['role'] !== 'admin') {
                $error = "Bu alandan sadece yöneticiler giriş yapabilir.";
            } else {
                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];
                header("Location: admin-dashboard.php");
                exit;
            }
        } else {
            $error = "Hatalı e-posta veya şifre!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/x-icon/favicon.png">
    <title>Giriş / Kayıt - Akıllı Tarif</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css?v=1.2">
</head>
<body class="auth-page bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm p-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-egg-fried me-1"></i> AKILLI TARİF</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door-fill me-1"></i> Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="all-recipes.php"><i class="bi bi-journal-text me-1"></i> Tarifler</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <?php if($error): ?> 
                    <div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?></div> 
                <?php endif; ?>
                <?php if($success): ?> 
                    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?></div> 
                <?php endif; ?>

                <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
                    <?php if (!isset($_SESSION['temp_user']) && !isset($_SESSION['reset_email']) && !isset($_GET['action'])): ?>
                    <ul class="nav nav-tabs nav-fill bg-white" id="authTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-3 fw-bold small text-nowrap" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab"><i class="bi bi-person-fill"></i> Kullanıcı</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-3 fw-bold small text-nowrap text-danger" id="admin-login-tab" data-bs-toggle="tab" data-bs-target="#admin-login" type="button" role="tab"><i class="bi bi-shield-lock-fill"></i> Admin</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-3 fw-bold small text-nowrap" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab"><i class="bi bi-person-plus-fill"></i> Kaydol</button>
                        </li>
                    </ul>
                    <?php endif; ?>

                    <div class="tab-content p-4 bg-white" id="authTabContent">
                        
                        <?php if (isset($_SESSION['temp_user'])): ?>
                            <div class="tab-pane fade show active" id="verify" role="tabpanel">
                                <h4 class="text-center mb-4"><i class="bi bi-envelope-paper-fill text-primary"></i> E-posta Doğrulama</h4>
                                <p class="text-center small text-muted">Lütfen <strong><?php echo $_SESSION['temp_user']['email']; ?></strong> adresine gelen 6 haneli kodu girin.</p>
                                <form action="auth.php" method="POST">
                                    <div class="mb-3">
                                        <input type="text" name="activation_code" class="form-control p-3 text-center fw-bold fs-4" maxlength="6" placeholder="000000" required>
                                    </div>
                                    <div class="b0tdenetle" style="display: none;" aria-hidden="true">
                                        <input type="text" name="address_field">
                                    </div>
                                    <button type="submit" name="verify" class="btn btn-primary w-100 py-3 fw-bold rounded-pill">Doğrula ve Tamamla</button>
                                    <div class="text-center mt-3">
                                        <a href="logout.php" class="text-decoration-none small text-danger">İptal Et</a>
                                    </div>
                                </form>
                            </div>

                        <?php elseif (isset($_SESSION['reset_email'])): ?>
                            <div class="tab-pane fade show active" id="reset" role="tabpanel">
                                <h4 class="text-center mb-4">Yeni Şifre Belirle</h4>
                                <p class="text-center small text-muted"><strong><?php echo $_SESSION['reset_email']; ?></strong> adresine gönderilen kodu ve yeni şifrenizi girin.</p>
                                <form action="auth.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Sıfırlama Kodu</label>
                                        <input type="text" name="reset_code" class="form-control p-2 text-center fw-bold" maxlength="6" placeholder="000000" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Yeni Şifre</label>
                                        <input type="password" name="new_password" class="form-control p-2" required>
                                    </div>
                                    <div class="b0tdenetle" style="display: none;" aria-hidden="true">
                                        <input type="text" name="address_field">
                                    </div>
                                    <button type="submit" name="reset_password" class="btn btn-dark w-100 py-2 fw-bold rounded-pill">Şifreyi Güncelle</button>
                                    <div class="text-center mt-3">
                                        <a href="logout.php" class="text-decoration-none small text-danger">İptal Et</a>
                                    </div>
                                </form>
                            </div>

                        <?php elseif (isset($_GET['action']) && $_GET['action'] == 'forgot'): ?>
                            <div class="tab-pane fade show active" id="forgot" role="tabpanel">
                                <h4 class="text-center mb-4">Şifremi Unuttum</h4>
                                <p class="text-center small text-muted">Şifrenizi sıfırlamak için kayıtlı e-posta adresinizi girin.</p>
                                <form action="auth.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">E-posta Adresi</label>
                                        <input type="email" name="email" class="form-control p-2" required>
                                    </div>
                                    <div class="b0tdenetle" style="display: none;" aria-hidden="true">
                                        <input type="text" name="address_field">
                                    </div>
                                    <button type="submit" name="forgot_password" class="btn btn-warning w-100 py-2 fw-bold rounded-pill">Sıfırlama Kodu Gönder</button>
                                    <div class="text-center mt-3">
                                        <a href="auth.php" class="text-decoration-none small text-dark">Giriş Yap'a Dön</a>
                                    </div>
                                </form>
                            </div>

                        <?php else: ?>
                            <!-- Standart Kullanıcı Girişi -->
                            <div class="tab-pane fade show active" id="login" role="tabpanel">
                                <h5 class="text-center fw-bold mb-4">Kullanıcı & Şef Girişi</h5>
                                <form action="auth.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">E-posta Adresi</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                            <input type="email" name="email" class="form-control border-start-0 py-2" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Şifre</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                            <input type="password" name="password" class="form-control border-start-0 py-2" required>
                                        </div>
                                    </div>
                                    <div class="mb-4 text-end">
                                        <a href="auth.php?action=forgot" class="small text-muted text-decoration-none">Şifremi Unuttum?</a>
                                    </div>
                                    <div class="b0tdenetle" style="display: none;" aria-hidden="true">
                                        <input type="text" name="address_field" id="address_field">
                                    </div>
                                    <button type="submit" name="login" class="btn btn-warning w-100 py-2 fw-bold rounded-pill shadow-sm">Giriş Yap</button>
                                </form>
                            </div>

                            <!-- Admin Girişi -->
                            <div class="tab-pane fade" id="admin-login" role="tabpanel">
                                <div class="text-center mb-4">
                                    <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle p-3 mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-shield-lock-fill fs-3"></i>
                                    </div>
                                    <h5 class="fw-bold text-danger">Yönetici Girişi</h5>
                                </div>
                                <form action="auth.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Yönetici E-posta</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-danger border-end-0"><i class="bi bi-envelope-fill text-danger"></i></span>
                                            <input type="email" name="email" class="form-control border-danger border-start-0 py-2" required>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label text-muted small fw-bold">Şifre</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-danger border-end-0"><i class="bi bi-key-fill text-danger"></i></span>
                                            <input type="password" name="password" class="form-control border-danger border-start-0 py-2" required>
                                        </div>
                                    </div>
                                    <div class="b0tdenetle" style="display: none;" aria-hidden="true">
                                        <input type="text" name="address_field">
                                    </div>
                                    <button type="submit" name="admin_login" class="btn btn-danger w-100 py-2 fw-bold rounded-pill shadow-sm">Yönetici Olarak Giriş Yap</button>
                                </form>
                            </div>

                            <!-- Kayıt Formu -->
                            <div class="tab-pane fade" id="register" role="tabpanel">
                                <h5 class="text-center fw-bold mb-4">Yeni Hesap Oluştur</h5>
                                <form action="auth.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Kullanıcı Adı</label>
                                        <input type="text" name="username" class="form-control py-2" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">E-posta Adresi</label>
                                        <input type="email" name="email" class="form-control py-2" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Şifre</label>
                                        <input type="password" name="password" class="form-control py-2" required>
                                    </div>
                                    <!--
                                    <div class="mb-4 form-check bg-light p-3 rounded">
                                        <input type="checkbox" name="request_chef" class="form-check-input ms-1" id="chefCheck">
                                        <label class="form-check-label small ms-2 fw-semibold text-dark" for="chefCheck">
                                            👨‍🍳 Şef olarak kaydolmak istiyorum
                                            <br><small class="text-muted fw-normal">(Yönetici onayı gerektirir)</small>
                                        </label>
                                    </div>
                                    /-->
                                    <div class="b0tdenetle" style="display: none;" aria-hidden="true">
                                        <input type="text" name="address_field">
                                    </div>
                                    <button type="submit" name="register" class="btn btn-dark w-100 py-2 fw-bold rounded-pill shadow-sm">Hesap Oluştur</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
