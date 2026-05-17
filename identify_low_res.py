import os
from PIL import Image

recipe_dir = 'img/recipe'
to_replace = []

if os.path.exists(recipe_dir):
    for filename in os.listdir(recipe_dir):
        if filename.lower().endswith(('.png', '.jpg', '.jpeg', '.avif', '.webp')):
            filepath = os.path.join(recipe_dir, filename)
            try:
                with Image.open(filepath) as img:
                    width, height = img.size
                    # Koşullar: 1:1 değilse VEYA (genişlik < 1024 VEYA yükseklik < 1024)
                    if width != height or width < 1024 or height < 1024:
                        # recipe_id'yi çekmeye çalış (recipe_10_... -> 10)
                        parts = filename.split('_')
                        recipe_id = "unknown"
                        if len(parts) > 1 and parts[1].isdigit():
                            recipe_id = parts[1]
                        elif parts[0] == "recipe" and len(parts) > 1: # recipe_65.jpg formatı için
                            potential_id = parts[1].split('.')[0]
                            if potential_id.isdigit():
                                recipe_id = potential_id
                        
                        to_replace.append({
                            'filename': filename,
                            'id': recipe_id,
                            'size': f"{width}x{height}",
                            'reason': "ratio" if width != height else "resolution"
                        })
            except Exception as e:
                pass

for item in to_replace:
    print(f"ID: {item['id']} | File: {item['filename']} | Size: {item['size']} | Reason: {item['reason']}")
