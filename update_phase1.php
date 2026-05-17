<?php
require_once __DIR__ . '/functions/db.php';

$phase1_urls = [
    65 => "https://wellmadebykiley.com/blog/strawberry-cinnamon-roll-bites",
    66 => "https://wellmadebykiley.com/blog/brown-butter-blueberry-fritter-cake",
    67 => "https://wellmadebykiley.com/blog/blueberry-pie-donut-bars",
    68 => "https://wellmadebykiley.com/blog/brown-butter-banana-pudding-cupcakes",
    69 => "https://wellmadebykiley.com/blog/peanut-butter-smores-oatmeal-cookie-skillet-with-brown-butter",
    70 => "https://wellmadebykiley.com/blog/funfetti-cinnamon-rolls",
    71 => "https://wellmadebykiley.com/blog/blueberry-cinnamon-roll-bread-pudding",
    72 => "https://wellmadebykiley.com/blog/old-fashioned-chocolate-chip-banana-bread-donuts",
    73 => "https://wellmadebykiley.com/blog/blueberry-crumble-cheesecake-bars",
    74 => "https://wellmadebykiley.com/blog/blueberry-fritters-with-brown-butter-lemon-glaze",
    75 => "https://wellmadebykiley.com/blog/lemon-curd-pistachio-cake",
    76 => "https://wellmadebykiley.com/blog/cinnamon-roll-croissants",
    77 => "https://wellmadebykiley.com/blog/crinkled-brown-butter-chocolate-chip-cookies",
    78 => "https://wellmadebykiley.com/blog/bakery-style-blueberry-cream-cheese-muffins",
    79 => "https://wellmadebykiley.com/blog/chocolate-chip-bread-pudding-with-butter-rum-sauce",
    80 => "https://wellmadebykiley.com/blog/flower-donuts",
    81 => "https://wellmadebykiley.com/blog/cheesecake-stuffed-blueberry-crumble-cookie-skillet-with-brown-butter",
    82 => "https://wellmadebykiley.com/blog/carrot-cake-cinnamon-rolls",
    83 => "https://wellmadebykiley.com/blog/self-saucing-banana-pudding-cake",
    84 => "https://wellmadebykiley.com/blog/carrot-cake-sticky-toffee-pudding"
];

$known_images = [
    65 => "https://images.squarespace-cdn.com/content/v1/65c3e29954a54703b46fe0ae/69afee19-2c0a-4321-9892-5ce9d68d710d/IMG_2049+3.jpg",
    66 => "https://images.squarespace-cdn.com/content/v1/65c3e29954a54703b46fe0ae/a2796204-e1a2-4bf4-a83a-9ba4fc4d8957/IMG_1282.jpg",
    67 => "https://images.squarespace-cdn.com/content/v1/65c3e29954a54703b46fe0ae/cea61b14-8476-40f6-9ea2-3769ade94fe7/IMG_1521+2.jpg",
    68 => "https://images.squarespace-cdn.com/content/v1/65c3e29954a54703b46fe0ae/ace480fd-f303-42b1-8032-6cb6060befcd/IMG_0975+5.jpg",
    69 => "https://images.squarespace-cdn.com/content/v1/65c3e29954a54703b46fe0ae/93ba4a5a-07e9-4787-8672-ab46f133b9e8/IMG_0383+2.jpg",
    70 => "https://images.squarespace-cdn.com/content/v1/65c3e29954a54703b46fe0ae/f0ba73c6-01f5-48aa-9891-c542c774e9fc/IMG_9802+2.jpg",
    71 => "https://images.squarespace-cdn.com/content/v1/65c3e29954a54703b46fe0ae/1da73620-dc4d-46f8-be42-1d74ffc45927/IMG_7925+2.jpg",
    72 => "https://images.squarespace-cdn.com/content/v1/65c3e29954a54703b46fe0ae/1ea7e64f-9cb8-4faa-a11f-6db87852fd66/IMG_9129.jpg",
    73 => "https://images.squarespace-cdn.com/content/v1/65c3e29954a54703b46fe0ae/eaca901c-81b1-4df5-9aaa-b8a09dcc8fe7/IMG_6845+2.jpg"
];

$img_dir = __DIR__ . '/img/recipe/';
if (!is_dir($img_dir)) {
    mkdir($img_dir, 0777, true);
}

foreach ($phase1_urls as $id => $url) {
    echo "Processing ID $id: $url\n";
    
    $img_url = isset($known_images[$id]) ? $known_images[$id] : "";
    
    if (!$img_url) {
        // Fetch HTML
        $html = shell_exec("curl.exe -L -s \"$url\"");
        if (!$html) {
            echo "Failed to fetch $url\n";
            continue;
        }
        
        // Extract main image URL - Look for IMG_ in squarespace links
        if (preg_match('/https:\/\/images\.squarespace-cdn\.com\/content\/v1\/[^"]+\/IMG_[^"]+\.jpg/', $html, $matches)) {
            $img_url = $matches[0];
        }
    }
    
    if ($img_url) {
        echo "Found image: $img_url\n";
        $filename = "recipe_$id.jpg";
        $filepath = $img_dir . $filename;
        
        // Download image
        shell_exec("curl.exe -s -o \"$filepath\" \"$img_url\"");
        
        // Update DB
        $stmt = $db->prepare("UPDATE recipes SET image_path = ? WHERE recipe_id = ?");
        $stmt->execute([$filename, $id]);
        echo "Updated DB and downloaded image for ID $id\n";
    } else {
        echo "Could not find image for ID $id\n";
    }
}

function preg_xml_match($pattern, $subject, &$matches) {
    return preg_match($pattern, $subject, $matches);
}
