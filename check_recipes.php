<?php
require 'functions/db.php';
$stmt = $db->query('SELECT recipe_id, title, image_path FROM recipes ORDER BY recipe_id DESC LIMIT 50');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['recipe_id'] . ' | ' . $row['title'] . ' | ' . $row['image_path'] . PHP_EOL;
}
?>