<?php

function isFavorite(PDO $db, int $user_id, int $recipe_id): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND recipe_id = ?");
    $stmt->execute([$user_id, $recipe_id]);
    return (int) $stmt->fetchColumn() > 0;
}

function addFavorite(PDO $db, int $user_id, int $recipe_id): bool
{
    if (isFavorite($db, $user_id, $recipe_id)) {
        return true;
    }
    $stmt = $db->prepare("INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)");
    return $stmt->execute([$user_id, $recipe_id]);
}

function removeFavorite(PDO $db, int $user_id, int $recipe_id): bool
{
    $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?");
    return $stmt->execute([$user_id, $recipe_id]);
}

function getUserFavorites(PDO $db, int $user_id): array
{
    $stmt = $db->prepare("
        SELECT r.*, u.username AS chef_name, r.chef_id as chefId
        FROM favorites f
        JOIN recipes r ON f.recipe_id = r.recipe_id
        LEFT JOIN users u ON r.chef_id = u.user_id
        WHERE f.user_id = ?
        ORDER BY r.recipe_id DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
