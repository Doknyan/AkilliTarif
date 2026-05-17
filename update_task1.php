<?php
require 'functions/db.php';
$updates = [
    65 => 'recipe_65.jpg', 66 => 'recipe_66.jpg', 67 => 'recipe_67.jpg', 68 => 'recipe_68.jpg',
    69 => 'recipe_69.jpg', 70 => 'recipe_70.jpg', 71 => 'recipe_71.jpg', 72 => 'recipe_72.jpg',
    73 => 'recipe_73.jpg', 74 => 'recipe_74.jpg', 75 => 'recipe_75.jpg', 76 => 'recipe_76.jpg',
    77 => 'recipe_77.jpg', 78 => 'recipe_78.jpg', 79 => 'recipe_79.jpg', 80 => 'recipe_80.jpg',
    81 => 'recipe_81.jpg', 82 => 'recipe_82.jpg', 83 => 'recipe_83.jpg', 84 => 'recipe_84.jpg'
];
foreach ($updates as $id => $img) {
    $stmt = $db->prepare('UPDATE recipes SET image_path = ? WHERE recipe_id = ?');
    $stmt->execute([$img, $id]);
}
echo "Updated 20 recipes.\n";
