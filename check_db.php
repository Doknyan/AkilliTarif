<?php
require_once __DIR__ . '/functions/db.php';

$stmt = $db->query("SELECT recipe_id, title, image_path FROM recipes WHERE recipe_id >= 65");
$recipes = $stmt->fetchAll();

echo "Found " . count($recipes) . " recipes with ID >= 65.\n";
foreach ($recipes as $r) {
    echo "ID: " . $r['recipe_id'] . ", Title: " . $r['title'] . ", Image: " . $r['image_path'] . "\n";
}
