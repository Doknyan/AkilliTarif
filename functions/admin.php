<?php

function getChefRequests(PDO $db): array
{
    return getChefRequestsPaginated($db, 100, 0);
}

function getChefRequestsPaginated(PDO $db, int $limit, int $offset, string $sort_col = 'created_at', string $sort_dir = 'DESC'): array
{
    $allowed_cols = ['username', 'email', 'created_at'];
    $sort_col = in_array($sort_col, $allowed_cols) ? $sort_col : 'created_at';
    $sort_dir = strtoupper($sort_dir) === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $db->prepare("SELECT user_id, username, email, created_at, chef_document FROM users WHERE role = 'user' AND requested_role = 'chef' AND is_active = 1 ORDER BY $sort_col $sort_dir LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getChefRequestsCount(PDO $db): int
{
    return (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND requested_role = 'chef' AND is_active = 1")->fetchColumn();
}

function promoteToChef(PDO $db, int $user_id): bool
{
    // Belgeyi fiziksel olarak silelim
    $stmt = $db->prepare("SELECT chef_document FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $doc = $stmt->fetchColumn();
    if ($doc) {
        $file = __DIR__ . '/../img/chef_docs/' . $doc;
        if (is_file($file)) @unlink($file);
    }

    $stmt = $db->prepare("UPDATE users SET role = 'chef', requested_role = NULL, chef_document = NULL WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

function rejectChefRequest(PDO $db, int $user_id): bool
{
    // Belgeyi fiziksel olarak silelim
    $stmt = $db->prepare("SELECT chef_document FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $doc = $stmt->fetchColumn();
    if ($doc) {
        $file = __DIR__ . '/../img/chef_docs/' . $doc;
        if (is_file($file)) @unlink($file);
    }

    $stmt = $db->prepare("UPDATE users SET requested_role = NULL, chef_document = NULL WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

function getTableCount(PDO $db, string $table): int
{
    $allowed_tables = ['users', 'recipes', 'comments', 'ingredients', 'favorites'];

    if (!in_array($table, $allowed_tables, true)) {
        return 0;
    }

    return (int) $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}

function getRoleCounts(PDO $db): array
{
    $role_counts = ['admin' => 0, 'chef' => 0, 'user' => 0];
    foreach ($db->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role") as $row) {
        $role_counts[$row['role']] = (int) $row['total'];
    }
    return $role_counts;
}

function getAllUsers(PDO $db): array
{
    return getAllUsersPaginated($db, 100, 0);
}

function getAllUsersPaginated(PDO $db, int $limit, int $offset, string $sort_col = 'created_at', string $sort_dir = 'DESC'): array
{
    $allowed_cols = ['user_id', 'username', 'email', 'role', 'created_at'];
    $sort_col = in_array($sort_col, $allowed_cols) ? $sort_col : 'created_at';
    $sort_dir = strtoupper($sort_dir) === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $db->prepare("SELECT user_id, username, email, role, created_at FROM users ORDER BY $sort_col $sort_dir LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getUsersCount(PDO $db): int
{
    return (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

function deleteUser(PDO $db, int $user_id): bool
{
    try {
        $db->beginTransaction();
        
        $db->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM favorites WHERE user_id = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM user_ingredients WHERE user_id = ?")->execute([$user_id]);
        
        $recipes = $db->prepare("SELECT recipe_id FROM recipes WHERE chef_id = ?");
        $recipes->execute([$user_id]);
        while ($recipe = $recipes->fetch()) {
            $recipe_id = $recipe['recipe_id'];
            $db->prepare("DELETE FROM comments WHERE recipe_id = ?")->execute([$recipe_id]);
            $db->prepare("DELETE FROM favorites WHERE recipe_id = ?")->execute([$recipe_id]);
            $db->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$recipe_id]);
        }
        $db->prepare("DELETE FROM recipes WHERE chef_id = ?")->execute([$user_id]);
        
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return false;
    }
}
