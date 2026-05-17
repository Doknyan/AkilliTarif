import matplotlib.pyplot as plt
import matplotlib.patches as patches

def draw_table(ax, x, y, title, columns, width=2.5, height_per_row=0.3):
    height = (len(columns) + 1) * height_per_row
    # Draw table box
    rect = patches.Rectangle((x, y - height), width, height, linewidth=2, edgecolor='#333', facecolor='#f8f9fa')
    ax.add_patch(rect)
    
    # Draw title header
    header_rect = patches.Rectangle((x, y - height_per_row), width, height_per_row, linewidth=2, edgecolor='#333', facecolor='#e9ecef')
    ax.add_patch(header_rect)
    
    # Add title text
    ax.text(x + width/2, y - height_per_row/2, title, weight='bold', ha='center', va='center', fontsize=10)
    
    # Add columns
    for i, col in enumerate(columns):
        y_pos = y - (i + 2) * height_per_row + height_per_row/2
        text = col
        font_weight = 'normal'
        if 'PK' in col:
            font_weight = 'bold'
        ax.text(x + 0.1, y_pos, text, ha='left', va='center', fontsize=9, weight=font_weight)
    
    return x, y, width, height

def draw_connection(ax, start_pos, end_pos, label=""):
    # Simple line for now
    ax.annotate('', xy=end_pos, xytext=start_pos,
                arrowprops=dict(arrowstyle='->', lw=1.5, color='#555', connectionstyle="arc3,rad=.1"))

fig, ax = plt.subplots(figsize=(12, 10))
ax.set_xlim(0, 10)
ax.set_ylim(0, 10)
ax.axis('off')

# Table Definitions
tables = {
    'users': (0.5, 9.5, ['user_id (PK)', 'username', 'email', 'password', 'role', 'is_active']),
    'recipes': (4.0, 9.5, ['recipe_id (PK)', 'chef_id (FK)', 'title', 'instructions', 'prep_time', 'calories']),
    'ingredients': (7.5, 9.5, ['ingredient_id (PK)', 'ingredient_name', 'unit', 'category']),
    'recipe_ingredients': (5.8, 6.0, ['recipe_id (FK)', 'ingredient_id (FK)', 'amount']),
    'user_ingredients': (1.5, 6.0, ['ui_id (PK)', 'user_id (FK)', 'ingredient_id (FK)', 'quantity', 'expiry_date']),
    'comments': (0.5, 2.5, ['comment_id (PK)', 'user_id (FK)', 'recipe_id (FK)', 'comment_text', 'rating']),
    'favorites': (4.0, 2.5, ['user_id (PK, FK)', 'recipe_id (PK, FK)'])
}

table_coords = {}

for name, (x, y, cols) in tables.items():
    tx, ty, tw, th = draw_table(ax, x, y, name.upper(), cols)
    table_coords[name] = (tx, ty, tw, th)

# Relationships (Rough approximations for connections)
# users -> recipes
draw_connection(ax, (3.0, 8.5), (4.0, 8.5))
# recipes -> recipe_ingredients
draw_connection(ax, (5.25, 7.5), (5.8, 5.5))
# ingredients -> recipe_ingredients
draw_connection(ax, (7.5, 8.5), (7.0, 6.0))
# users -> user_ingredients
draw_connection(ax, (1.75, 7.5), (1.75, 6.0))
# ingredients -> user_ingredients
draw_connection(ax, (8.5, 8.0), (3.0, 5.0))
# users -> comments
draw_connection(ax, (0.75, 7.5), (0.75, 2.5))
# recipes -> comments
draw_connection(ax, (4.5, 7.5), (2.0, 2.0))
# users -> favorites
draw_connection(ax, (2.0, 8.0), (4.0, 2.0))
# recipes -> favorites
draw_connection(ax, (5.25, 7.5), (5.25, 2.5))

plt.title('Akıllı Tarif Veritabanı İlişki Diyagramı (ERD)', fontsize=16, pad=20)
plt.tight_layout()
plt.savefig('img/erd_diagram.png', dpi=300, bbox_inches='tight')
print("ERD image saved to img/erd_diagram.png")
