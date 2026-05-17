<?php

function sendAuthEmail(string $email, string $code, string $type = 'activation'): bool
{
    if ($type === 'activation') {
        $subject = "Hesap Aktivasyon Kodu - Akıllı Tarif";
        $message = "Merhaba,\n\nHesabınızı aktifleştirmek için kodunuz: $code\n\nBu kod 5 dakika geçerlidir.";
    } else {
        $subject = "Şifre Sıfırlama Kodu - Akıllı Tarif";
        $message = "Merhaba,\n\nŞifrenizi sıfırlamak için kodunuz: $code\n\nBu kod 10 dakika geçerlidir.";
    }
    
    $headers = "From: no-reply@akillitarif.com";
    $mailSent = @mail($email, $subject, $message, $headers);

    if (!$mailSent) {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] TİP: $type | KOD: $code | ALICI: $email\n";
        file_put_contents('mail_log.txt', $logMessage, FILE_APPEND);
        return true; 
    }
    return $mailSent;
}

function requestPasswordReset(PDO $db, string $email): bool
{
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $db->prepare("UPDATE users SET reset_code = ?, reset_expiry = ? WHERE email = ?");
        if ($stmt->execute([$code, $expiry, $email])) {
            return sendAuthEmail($email, $code, 'reset');
        }
    }
    return false;
}

function resetPasswordWithCode(PDO $db, string $email, string $code, string $newPassword): bool
{
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND reset_code = ? AND reset_expiry >= NOW()");
    $stmt->execute([$email, $code]);
    if ($stmt->fetch()) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expiry = NULL WHERE email = ?");
        return $stmt->execute([$hashed, $email]);
    }
    return false;
}

function registerUser(PDO $db, string $username, string $email, string $password, string $role = 'user'): array|string|bool
{
    // E-posta veya kullanıcı adı kontrolü
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetchColumn() > 0) {
        return "Bu e-posta veya kullanıcı adı zaten kullanımda.";
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role, requested_role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        if ($stmt->execute([
            $username,
            $email,
            $hashedPassword,
            'user', // İlk kayıt her zaman user
            $role // Talep edilen rol (chef veya user)
        ])) {
            return true;
        }
        return "Kayıt sırasında veritabanı hatası oluştu.";
    } catch (PDOException $e) {
        return "Kayıt sırasında bir hata oluştu: " . $e->getMessage();
    }
}

function commitUserRegistration(PDO $db, array $userData): bool
{
    try {
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role, requested_role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        return $stmt->execute([
            $userData['username'],
            $userData['email'],
            $userData['password'],
            'user', // İlk kayıt her zaman user
            $userData['role'] // Talep edilen rol (chef veya user)
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function verifyUser(PDO $db, string $email, string $code): bool
{
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND activation_code = ? AND is_active = 0");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if ($user) {
        $now = date('Y-m-d H:i:s');
        if ($user['activation_expiry'] >= $now) {
            $update = $db->prepare("UPDATE users SET is_active = 1, activation_code = NULL, activation_expiry = NULL WHERE user_id = ?");
            return $update->execute([$user['user_id']]);
        }
    }
    return false;
}

function loginUser(PDO $db, string $email, string $password): ?array
{
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }

    return null;
}

function getUserById(PDO $db, int $user_id): ?array
{
    $stmt = $db->prepare("SELECT user_id, username, email, role, daily_calorie_target, created_at, profile_image FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: auth.php');
        exit;
    }
}

function requireRole(string|array $roles): void
{
    requireLogin();
    $userRole = strtolower($_SESSION['role'] ?? '');
    
    // Eğer tek bir string gelmişse diziye çevirelim
    $allowedRoles = is_array($roles) ? array_map('strtolower', $roles) : [strtolower($roles)];
    
    if (!in_array($userRole, $allowedRoles, true)) {
        header('Location: index.php');
        exit;
    }
}
