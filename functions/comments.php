<?php

function addComment(PDO $db, int $user_id, int $recipe_id, string $comment_text, int $rating): bool
{
    $stmt = $db->prepare("
        INSERT INTO comments (user_id, recipe_id, comment_text, rating)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$user_id, $recipe_id, $comment_text, $rating]);
}

function getRecipeComments(PDO $db, int $recipe_id): array
{
    $stmt = $db->prepare("SELECT c.*, u.username FROM comments c 
                               JOIN users u ON c.user_id = u.user_id 
                               WHERE c.recipe_id = ? ORDER BY c.created_at DESC");
    $stmt->execute([$recipe_id]);
    return $stmt->fetchAll();
}
