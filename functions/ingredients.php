<?php

function getIngredientOptions(PDO $db): array
{
    return $db->query(
        "SELECT ingredient_id, ingredient_name, unit, category FROM ingredients ORDER BY ingredient_name"
    )->fetchAll();
}

function getIngredientCategories(): array
{
    return [
        'Sebze',
        'Meyve',
        'Kuru yemiş veya atıştırmalık',
        'Bakliyat',
        'Baharat',
        'Süt veya süt ürünleri',
        'Et',
        'Balık veya su ürünleri',
        'Sos',
        'Katkı',
        'Un veya tahıl ürünleri',
        'Yağ',
        'Konserve',
        'Hayvansal diğer gıda',
        'Şeker/tatlı maddeleri',
        'Tuz',
        'Çikolata/kakao ürünleri',
        'Mayalar ve fermente ürünleri',
        'Diğer'
    ];
}

function getValidIngredientIds(array $ingredient_options): array
{
    return array_map('intval', array_column($ingredient_options, 'ingredient_id'));
}

function findIngredientByName(PDO $db, string $ingredient_name): ?int
{
    $stmt = $db->prepare(
        "SELECT ingredient_id FROM ingredients WHERE LOWER(ingredient_name) = LOWER(?) LIMIT 1"
    );
    $stmt->execute([$ingredient_name]);
    $result = $stmt->fetchColumn();

    return $result ? (int) $result : null;
}

function createIngredient(PDO $db, string $ingredient_name, ?string $unit, string $category = 'Diğer'): int
{
    $stmt = $db->prepare(
        "INSERT INTO ingredients (ingredient_name, unit, category) VALUES (?, ?, ?)"
    );
    $stmt->execute([$ingredient_name, $unit ?: null, $category]);

    return (int) $db->lastInsertId();
}

function lookupOrCreateIngredient(PDO $db, string $ingredient_name, ?string $unit, string $category = 'Diğer'): int
{
    $existing_id = findIngredientByName($db, $ingredient_name);

    if ($existing_id) {
        return $existing_id;
    }

    return createIngredient($db, $ingredient_name, $unit, $category);
}

function getUserIngredientRows(PDO $db, int $user_id): array
{
    $stmt = $db->prepare(
        "SELECT
            ui.ui_id,
            ui.quantity,
            ui.expiry_date,
            i.ingredient_name,
            i.unit,
            DATEDIFF(ui.expiry_date, CURDATE()) AS days_left
        FROM user_ingredients ui
        JOIN ingredients i ON ui.ingredient_id = i.ingredient_id
        WHERE ui.user_id = ?
        ORDER BY
            CASE
                WHEN ui.expiry_date IS NULL THEN 3
                WHEN ui.expiry_date < CURDATE() THEN 0
                WHEN ui.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 1
                ELSE 2
            END,
            ui.expiry_date ASC,
            i.ingredient_name ASC"
    );
    $stmt->execute([$user_id]);

    return $stmt->fetchAll();
}

function categorizeUserIngredients(array $ingredients): array
{
    $expired_items = [];
    $expiring_items = [];
    $fresh_items = [];
    $undated_items = [];

    foreach ($ingredients as $ingredient) {
        if ($ingredient['expiry_date'] === null) {
            $undated_items[] = $ingredient;
            continue;
        }

        $days_left = (int) $ingredient['days_left'];

        if ($days_left < 0) {
            $expired_items[] = $ingredient;
        } elseif ($days_left <= 3) {
            $expiring_items[] = $ingredient;
        } else {
            $fresh_items[] = $ingredient;
        }
    }

    return [
        'expired_items' => $expired_items,
        'expiring_items' => $expiring_items,
        'fresh_items' => $fresh_items,
        'undated_items' => $undated_items,
    ];
}

function getExpiringIngredientsCount(PDO $db, int $user_id, int $days = 3): int
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_ingredients WHERE user_id = ? AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)");
    $stmt->execute([$user_id, $days]);
    return (int) $stmt->fetchColumn();
}

function getRecipesForExpiringIngredients(PDO $db, int $user_id): array
{
    $stmt = $db->prepare(
        "SELECT DISTINCT r.recipe_id, r.title, r.calories, r.image_path, u.username AS chef_name, r.chef_id as chefId
         FROM recipes r
         JOIN recipe_ingredients ri ON r.recipe_id = ri.recipe_id
         JOIN user_ingredients ui ON ri.ingredient_id = ui.ingredient_id
         LEFT JOIN users u ON r.chef_id = u.user_id
         WHERE ui.user_id = ?
           AND ui.expiry_date IS NOT NULL
           AND ui.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
         ORDER BY r.recipe_id DESC"
    );
    $stmt->execute([$user_id]);

    return $stmt->fetchAll();
}

function getRecipesForFreshIngredients(PDO $db, int $user_id): array
{
    $stmt = $db->prepare(
        "SELECT DISTINCT r.recipe_id, r.title, r.calories, r.image_path, u.username AS chef_name, r.chef_id as chefId
         FROM recipes r
         JOIN recipe_ingredients ri ON r.recipe_id = ri.recipe_id
         JOIN user_ingredients ui ON ri.ingredient_id = ui.ingredient_id
         LEFT JOIN users u ON r.chef_id = u.user_id
         WHERE ui.user_id = ?
           AND (ui.expiry_date IS NULL OR ui.expiry_date > DATE_ADD(CURDATE(), INTERVAL 3 DAY))
         ORDER BY r.recipe_id DESC
         LIMIT 12"
    );
    $stmt->execute([$user_id]);

    return $stmt->fetchAll();
}
