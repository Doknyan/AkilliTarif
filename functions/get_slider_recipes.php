<?php
require 'db.php';

$slider_recipes = [];

try {
    // 1. En son eklenen yemek
    $slider_recipes['latest'] = $db->query("SELECT * FROM recipes ORDER BY recipe_id DESC LIMIT 1")->fetch();

    // 2. En çok ziyaret edilen yemek (image_path veya id üzerinden simüle edelim)
    // Eğer veritabanında 'view_count' yoksa en çok yorum alanı çekiyoruz
    $slider_recipes['popular'] = $db->query("SELECT r.* FROM recipes r 
        LEFT JOIN comments c ON r.recipe_id = c.recipe_id 
        GROUP BY r.recipe_id ORDER BY COUNT(c.comment_id) DESC LIMIT 1")->fetch();

    // 3. En yüksek puanlı yemek
    $slider_recipes['top_rated'] = $db->query("SELECT r.* FROM recipes r 
        JOIN comments c ON r.recipe_id = c.recipe_id 
        GROUP BY r.recipe_id ORDER BY AVG(c.rating) DESC LIMIT 1")->fetch();

    // 4. Rastgele bir yemek (Yukarıdakilerden farklı olması için)
    $exclude_ids = [
        $slider_recipes['latest']['recipe_id'], 
        $slider_recipes['popular']['recipe_id'], 
        $slider_recipes['top_rated']['recipe_id']
    ];
    $exclude_str = implode(',', array_filter($exclude_ids));
    
    $slider_recipes['random'] = $db->query("SELECT * FROM recipes 
        WHERE recipe_id NOT IN ($exclude_str) 
        ORDER BY RAND() LIMIT 1")->fetch();

} catch (PDOException $e) {
    die("Slider hatası: " . $e->getMessage());
}
?>