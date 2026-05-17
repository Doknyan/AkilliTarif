<?php

function insertRecipe(PDO $db, int $chef_id, string $title, string $instructions, ?int $preparation_time, ?int $calories, int $is_vegan, int $is_gluten_free, int $is_sugar_free, int $is_lactose_free, int $is_vegetarian, ?string $image_path): int
{
    $stmt = $db->prepare(
        "INSERT INTO recipes (
            chef_id, title, instructions, preparation_time, calories,
            is_vegan, is_gluten_free, is_sugar_free, is_lactose_free, is_vegetarian, image_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $chef_id,
        $title,
        $instructions,
        $preparation_time,
        $calories,
        $is_vegan,
        $is_gluten_free,
        $is_sugar_free,
        $is_lactose_free,
        $is_vegetarian,
        $image_path,
    ]);

    return (int) $db->lastInsertId();
}

function updateRecipe(PDO $db, int $recipe_id, string $title, string $instructions, ?int $preparation_time, ?int $calories, int $is_vegan, int $is_gluten_free, int $is_sugar_free, int $is_lactose_free, int $is_vegetarian, ?string $image_path): void
{
    $sql = "UPDATE recipes SET 
            title = ?, 
            instructions = ?, 
            preparation_time = ?, 
            calories = ?, 
            is_vegan = ?, 
            is_gluten_free = ?, 
            is_sugar_free = ?, 
            is_lactose_free = ?, 
            is_vegetarian = ?";
    
    $params = [
        $title,
        $instructions,
        $preparation_time,
        $calories,
        $is_vegan,
        $is_gluten_free,
        $is_sugar_free,
        $is_lactose_free,
        $is_vegetarian
    ];

    if ($image_path !== null) {
        $sql .= ", image_path = ?";
        $params[] = $image_path;
    }

    $sql .= " WHERE recipe_id = ?";
    $params[] = $recipe_id;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
}

function addRecipeIngredients(PDO $db, int $recipe_id, array $recipe_ingredients): void
{
    $stmt = $db->prepare(
        "INSERT INTO recipe_ingredients (recipe_id, ingredient_id, amount) VALUES (?, ?, ?)"
    );

    foreach ($recipe_ingredients as $ingredient_id => $amount) {
        $stmt->execute([$recipe_id, $ingredient_id, $amount]);
    }
}

function buildRecipeIngredientRows(array $posted_ingredient_ids, array $posted_amounts, array $posted_new_names, array $posted_new_units, array $posted_new_categories, array $valid_ingredient_ids, array &$recipe_ingredients, array &$pending_custom_ingredients): ?string
{
    foreach ($posted_ingredient_ids as $index => $posted_ingredient_id) {
        $ingredient_id_raw = (string) $posted_ingredient_id;
        $ingredient_id = $ingredient_id_raw === 'new' ? 0 : (int) $ingredient_id_raw;
        $amount_raw = trim((string) ($posted_amounts[$index] ?? ''));
        $amount = (float) str_replace(',', '.', $amount_raw);
        $new_ingredient_name = trim((string) ($posted_new_names[$index] ?? ''));
        $new_ingredient_unit = trim((string) ($posted_new_units[$index] ?? ''));
        $new_ingredient_category = trim((string) ($posted_new_categories[$index] ?? 'Diğer'));

        $isEmptyRow = $ingredient_id_raw === '' && $amount_raw === '' && $new_ingredient_name === '' && $new_ingredient_unit === '';
        if ($isEmptyRow) {
            continue;
        }

        if ($amount <= 0) {
            return 'Malzeme satırlarında miktar 0’dan büyük olmalıdır.';
        }

        if ($ingredient_id > 0) {
            if (!in_array($ingredient_id, $valid_ingredient_ids, true)) {
                return 'Malzeme satırlarında geçerli malzeme seçin.';
            }

            $recipe_ingredients[$ingredient_id] = ($recipe_ingredients[$ingredient_id] ?? 0) + $amount;
            continue;
        }

        if ($new_ingredient_name === '') {
            return 'Yeni malzeme adı girin veya listeden bir malzeme seçin.';
        }

        if ($new_ingredient_unit === '') {
            return 'Yeni malzeme için birim girin.';
        }

        $pending_custom_ingredients[] = [
            'name' => $new_ingredient_name,
            'unit' => $new_ingredient_unit,
            'category' => $new_ingredient_category,
            'amount' => $amount,
        ];
    }

    return null;
}

function getRecipeById(PDO $db, int $recipe_id): ?array
{
    $stmt = $db->prepare("SELECT r.*, u.user_id as chefId, u.user_id, u.username as chef_name, u.profile_image as profile_image, AVG(c.rating) as avg_rating, COUNT(c.comment_id) AS review_count 
                           FROM recipes r 
                           LEFT JOIN users u ON r.chef_id = u.user_id 
                           LEFT JOIN comments c ON r.recipe_id = c.recipe_id
                           WHERE r.recipe_id = ?
                           GROUP BY r.recipe_id");
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch();
    return $recipe ?: null;
}

function getRecipeIngredients(PDO $db, int $recipe_id): array
{
    $stmt = $db->prepare("SELECT i.ingredient_id, i.ingredient_name, ri.amount, i.unit 
                               FROM recipe_ingredients ri 
                               JOIN ingredients i ON ri.ingredient_id = i.ingredient_id 
                               WHERE ri.recipe_id = ?");
    $stmt->execute([$recipe_id]);
    return $stmt->fetchAll();
}

function getChefRecipes(PDO $db, int $chef_id): array
{
    $stmt = $db->prepare("
        SELECT r.recipe_id, r.title, r.image_path, r.calories, r.chef_id as chefId, u.username AS chef_name, u.profile_image as profile_image,
        r.preparation_time, AVG(c.rating) as avg_rating, COUNT(c.comment_id) AS review_count
        FROM recipes r
        LEFT JOIN comments c ON r.recipe_id = c.recipe_id
        LEFT JOIN users u ON r.chef_id = u.user_id
        WHERE r.chef_id = ?
        GROUP BY r.recipe_id
        ORDER BY r.recipe_id DESC
    ");
    $stmt->execute([$chef_id]);
    return $stmt->fetchAll();
}

function getSortSql(string $sort): string
{
    $options = [
        'newest' => 'r.recipe_id DESC',
        'oldest' => 'r.recipe_id ASC',
        'rating' => 'avg_rating DESC',
        'calories_low' => 'r.calories ASC',
        'calories_high' => 'r.calories DESC',
        'time_short' => 'r.preparation_time ASC'
    ];
    return $options[$sort] ?? $options['newest'];
}

function getChefRecipesPaginated(PDO $db, int $chef_id, int $limit, int $offset, string $sort = 'newest'): array
{
    $order_sql = getSortSql($sort);
    $stmt = $db->prepare("
        SELECT r.recipe_id, r.title, r.image_path, r.calories, r.chef_id as chefId, u.username AS chef_name, u.profile_image as profile_image,
        r.preparation_time, AVG(c.rating) as avg_rating, COUNT(c.comment_id) AS review_count
        FROM recipes r
        LEFT JOIN comments c ON r.recipe_id = c.recipe_id
        LEFT JOIN users u ON r.chef_id = u.user_id
        WHERE r.chef_id = ?
        GROUP BY r.recipe_id
        ORDER BY $order_sql
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $chef_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getChefRecipeCount(PDO $db, int $chef_id): int
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM recipes WHERE chef_id = ?");
    $stmt->execute([$chef_id]);
    return (int)$stmt->fetchColumn();
}

function advancedSearchRecipes(PDO $db, array $filters): array
{
    return advancedSearchRecipesPaginated($db, $filters, 50, 0);
}

function advancedSearchRecipesPaginated(PDO $db, array $filters, int $limit, int $offset, string $sort = 'newest'): array
{
    $order_sql = getSortSql($sort);
    $queryParts = buildAdvancedSearchQuery($filters);
    $sql = "SELECT r.*, u.user_id as chefId, u.username AS chef_name, AVG(co.rating) as avg_rating,
                                  COUNT(co.comment_id) AS review_count
            FROM recipes r
            LEFT JOIN users u ON r.chef_id = u.user_id
            LEFT JOIN comments co ON r.recipe_id = co.recipe_id";

    if ($queryParts['where']) {
        $sql .= " WHERE " . implode(" AND ", $queryParts['where']);
    }

    $sql .= " GROUP BY r.recipe_id ORDER BY $order_sql LIMIT ? OFFSET ?";

    $stmt = $db->prepare($sql);
    $params = $queryParts['params'];
    $paramCount = count($params);
    for ($i = 0; $i < $paramCount; $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    $stmt->bindValue($paramCount + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramCount + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function advancedSearchRecipeCount(PDO $db, array $filters): int
{
    $queryParts = buildAdvancedSearchQuery($filters);
    $sql = "SELECT COUNT(DISTINCT r.recipe_id) FROM recipes r";

    if ($queryParts['where']) {
        $sql .= " WHERE " . implode(" AND ", $queryParts['where']);
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($queryParts['params']);
    return (int)$stmt->fetchColumn();
}

function buildAdvancedSearchQuery(array $filters): array
{
    $where = [];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = "(r.title LIKE ? OR r.instructions LIKE ?)";
        $params[] = "%{$filters['q']}%";
        $params[] = "%{$filters['q']}%";
    }

    if (!empty($filters['is_vegan'])) {
        $where[] = "r.is_vegan = 1";
    }
    if (!empty($filters['is_gluten_free'])) {
        $where[] = "r.is_gluten_free = 1";
    }
    if (!empty($filters['is_sugar_free'])) {
        $where[] = "r.is_sugar_free = 1";
    }
    if (!empty($filters['is_lactose_free'])) {
        $where[] = "r.is_lactose_free = 1";
    }
    if (!empty($filters['is_vegetarian'])) {
        $where[] = "r.is_vegetarian = 1";
    }

    if (!empty($filters['min_time'])) {
        $where[] = "r.preparation_time >= ?";
        $params[] = (int)$filters['min_time'];
    }

    if (!empty($filters['max_time'])) {
        $where[] = "r.preparation_time <= ?";
        $params[] = (int)$filters['max_time'];
    }

    if (!empty($filters['min_calories'])) {
        $where[] = "r.calories >= ?";
        $params[] = (int)$filters['min_calories'];
    }

    if (!empty($filters['max_calories'])) {
        $where[] = "r.calories <= ?";
        $params[] = (int)$filters['max_calories'];
    }

    if (!empty($filters['chef_id'])) {
        $where[] = "r.chef_id = ?";
        $params[] = (int)$filters['chef_id'];
    }

    if (!empty($filters['ingredients']) && is_array($filters['ingredients'])) {
        $ingredientCount = count($filters['ingredients']);
        $placeholders = implode(',', array_fill(0, $ingredientCount, '?'));
        $where[] = "r.recipe_id IN (
            SELECT recipe_id 
            FROM recipe_ingredients 
            WHERE ingredient_id IN ($placeholders) 
            GROUP BY recipe_id 
            HAVING COUNT(DISTINCT ingredient_id) = ?
        )";
        foreach ($filters['ingredients'] as $id) {
            $params[] = (int)$id;
        }
        $params[] = $ingredientCount;
    }

    return ['where' => $where, 'params' => $params];
}

function searchRecipes(PDO $db, string $query, int $limit = 20): array
{
    $searchTerm = "%$query%";
    $stmt = $db->prepare("
        SELECT r.*, u.user_id as chefId, u.username AS chef_name, u.profile_image as profile_image, AVG(c.rating) as avg_rating, 
        COUNT(c.comment_id) AS review_count 
        FROM recipes r
        LEFT JOIN users u ON r.chef_id = u.user_id
        LEFT JOIN comments c ON r.recipe_id = c.recipe_id
        WHERE r.title LIKE ? OR r.instructions LIKE ?
        GROUP BY r.recipe_id
        ORDER BY r.recipe_id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(2, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getRecentRecipes(PDO $db, int $limit = 5): array
{
    $stmt = $db->prepare("
        SELECT r.recipe_id, r.title, r.calories, r.image_path,r.chef_id as chefId, u.username AS chef_name, AVG(c.rating) as avg_rating, r.chef_id
        FROM recipes r
        LEFT JOIN users u ON r.chef_id = u.user_id
        LEFT JOIN comments c ON r.recipe_id = c.recipe_id
        GROUP BY r.recipe_id
        ORDER BY r.recipe_id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getPaginatedRecipes(PDO $db, int $limit, int $offset, string $sort = 'newest'): array
{
    $order_sql = getSortSql($sort);
    $stmt = $db->prepare("
        SELECT r.recipe_id, r.title, r.calories, r.image_path, r.chef_id as chefId, u.username AS chef_name, AVG(c.rating) as avg_rating, r.chef_id, COUNT(c.comment_id) as review_count
        FROM recipes r
        LEFT JOIN users u ON r.chef_id = u.user_id
        LEFT JOIN comments c ON r.recipe_id = c.recipe_id
        GROUP BY r.recipe_id
        ORDER BY $order_sql
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTotalRecipeCount(PDO $db): int
{
    return (int)$db->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
}

function getRecipeLink(int $recipe_id): string
{
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    return ($is_admin ? "edit-recipe.php" : "recipe-detail.php") . "?id=" . $recipe_id;
}

function getPopularRecipes(PDO $db, int $limit = 4): array
{
    $stmt = $db->prepare("SELECT 
        r.*, 
        u.user_id AS chefId,
        u.username AS chef_name, 
        AVG(c.rating) AS avg_rating, 
        COUNT(c.comment_id) AS review_count
    FROM recipes r 
    LEFT JOIN users u ON r.chef_id = u.user_id 
    LEFT JOIN comments c ON r.recipe_id = c.recipe_id 
    GROUP BY r.recipe_id, u.username
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function deleteRecipe(PDO $db, int $recipe_id): bool
{
    try {
        $db->beginTransaction();
        
        $db->prepare("DELETE FROM comments WHERE recipe_id = ?")->execute([$recipe_id]);
        $db->prepare("DELETE FROM favorites WHERE recipe_id = ?")->execute([$recipe_id]);
        $db->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$recipe_id]);
        
        $stmt = $db->prepare("SELECT image_path FROM recipes WHERE recipe_id = ?");
        $stmt->execute([$recipe_id]);
        $img = $stmt->fetchColumn();
        if ($img && $img !== 'default-food.jpg') {
            $file = __DIR__ . '/../img/recipe/' . $img;
            if (is_file($file)) {
                @unlink($file);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM recipes WHERE recipe_id = ?");
        $stmt->execute([$recipe_id]);
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return false;
    }
}

function getAllRecipesAdmin(PDO $db): array
{
    return getAllRecipesAdminPaginated($db, 100, 0);
}

function getAllRecipesAdminPaginated(PDO $db, int $limit, int $offset, string $sort_col = 'recipe_id', string $sort_dir = 'DESC'): array
{
    $allowed_cols = [
        'recipe_id' => 'r.recipe_id',
        'title' => 'r.title',
        'chef_name' => 'chef_name',
        'calories' => 'r.calories',
        'preparation_time' => 'r.preparation_time'
    ];
    $sort_sql = $allowed_cols[$sort_col] ?? 'r.recipe_id';
    $sort_dir = strtoupper($sort_dir) === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $db->prepare("
        SELECT r.recipe_id, r.title, r.calories, r.preparation_time, u.username as chef_name
        FROM recipes r
        LEFT JOIN users u ON r.chef_id = u.user_id
        ORDER BY $sort_sql $sort_dir
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getRecipesCount(PDO $db): int
{
    return (int) $db->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
}
function getDailyTopRecipes(PDO $db, int $limit = 4): array
{
    $stmt = $db->prepare("SELECT 
        r.*, 
        u.user_id AS chefId,
        u.username AS chef_name, 
        AVG(c.rating) AS avg_rating, 
        COUNT(c.comment_id) AS review_count
    FROM recipes r 
    LEFT JOIN users u ON r.chef_id = u.user_id 
    JOIN comments c ON r.recipe_id = c.recipe_id 
    WHERE DATE(c.created_at) = CURDATE()
    GROUP BY r.recipe_id, u.username
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}


