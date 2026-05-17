<?php

function format_quantity($quantity, $unit): string
{
    $value = rtrim(rtrim(number_format((float) $quantity, 2, '.', ''), '0'), '.');
    return trim($value . ' ' . ($unit ?? ''));
}

function format_date_tr(?string $date): string
{
    if (!$date) {
        return 'Tarih yok';
    }

    return date('d.m.Y', strtotime($date));
}

function format_days_left($days_left): string
{
    if ($days_left === null) {
        return 'Tarih yok';
    }

    $days_left = (int) $days_left;

    if ($days_left < 0) {
        return abs($days_left) . ' gün geçti';
    }

    if ($days_left === 0) {
        return 'Bugün';
    }

    if ($days_left === 1) {
        return 'Yarın';
    }

    return $days_left . ' gün kaldı';
}

function render_ingredient_table(array $items, string $empty_message, string $badge_class, bool $show_actions = false): void
{
    if (!$items) {
        echo '<p class="text-muted mb-0">' . htmlspecialchars($empty_message) . '</p>';
        return;
    }
    ?>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Malzeme</th>
                    <th>Miktar</th>
                    <th>Son tüketim tarihi</th>
                    <th>Durum</th>
                    <?php if ($show_actions): ?>
                        <th></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($item['ingredient_name']); ?></td>
                        <td><?php echo htmlspecialchars(format_quantity($item['quantity'], $item['unit'])); ?></td>
                        <td><?php echo htmlspecialchars(format_date_tr($item['expiry_date'])); ?></td>
                        <td>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars(format_days_left($item['days_left'])); ?>
                            </span>
                        </td>
                        <?php if ($show_actions): ?>
                            <td>
                                <form method="POST" action="my-kitchen.php" class="d-inline">
                                    <input type="hidden" name="action" value="delete_single">
                                    <input type="hidden" name="ui_id" value="<?php echo (int) $item['ui_id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bu malzemeyi silmek istediğinizden emin misiniz?');">Sil</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function render_recipe_cards(array $recipes, bool $show_remove_favorite = false, string $col_class = 'row-cols-1 row-cols-md-2 row-cols-xl-4'): void
{
    if (!$recipes) {
        echo '<p class="text-muted mb-0">Bu malzemelere uygun tarif bulunamadı.</p>';
        return;
    }
    ?>
    <div class="row <?php echo $col_class; ?> g-4 mt-3">
        <?php foreach ($recipes as $recipe): 
            $img = $recipe['image_path'] ?: 'default-food.jpg';
        ?>
            <div class="col">
                <div class="card shadow-sm h-100 position-relative">
                    <?php if ($show_remove_favorite): ?>
                        <form method="POST" action="my-favorites.php" class="position-absolute top-0 end-0 m-2" style="z-index: 10;">
                            <input type="hidden" name="action" value="remove_favorite">
                            <input type="hidden" name="recipe_id" value="<?php echo (int)$recipe['recipe_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 0;" onclick="return confirm('Bu tarifi favorilerinizden çıkarmak istediğinize emin misiniz?');">
                                &times;
                            </button>
                        </form>
                    <?php endif; ?>
                    <img src="img/recipe/<?php echo htmlspecialchars($img); ?>" class="card-img-top" style="height: 200px; object-fit: cover; width: 100%;" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                        <p class="text-muted small mb-3">
                            
                            Şef: <a href="chef-recipes.php?id=<?php echo (int)$recipe['chefId']; ?>" class="text-decoration-none text-dark fw-bold"><?php echo htmlspecialchars($recipe['chef_name'] ?? 'Bilinmiyor'); ?></a>
                        </p>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="text-warning small">
                                    <?php 
                                        $rating = round((float)($recipe['avg_rating'] ?? 0));
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '★' : '☆';
                                        }
                                        // Eğer yorum sayısı da göstermek isterseniz:
                                        $rev = ($recipe['review_count'] ?? 0);
                                        if ($rev > 0) {
                                            echo ' <span class="badge bg-primary">' . $rev . '</span>';
                                        }
                                    ?>
                                </div>                                
                                <span class="badge bg-success"><?php echo htmlspecialchars($recipe['calories']); ?> kcal</span>
                            </div>
                            <?php 
                                $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
                                $is_chef = isset($_SESSION['role']) && $_SESSION['role'] === 'chef' && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $recipe['chefId'];    
                                $recipe_link = $is_admin || $is_chef ? "edit-recipe.php?id=" . (int)$recipe['recipe_id'] : "recipe-detail.php?id=" . (int)$recipe['recipe_id'];
                                $button_text = $is_admin || $is_chef? "Düzenle" : "İncele";
                            ?>
                            <a href="<?php echo $recipe_link; ?>" class="btn btn-sm btn-outline-warning w-100"><?php echo $button_text; ?></a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
