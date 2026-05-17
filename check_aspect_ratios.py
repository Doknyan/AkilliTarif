import os
from PIL import Image

recipe_dir = 'img/recipe'
output_list = []

if not os.path.exists(recipe_dir):
    print(f"Directory {recipe_dir} not found.")
else:
    for filename in os.listdir(recipe_dir):
        if filename.lower().endswith(('.png', '.jpg', '.jpeg', '.avif', '.webp')):
            filepath = os.path.join(recipe_dir, filename)
            try:
                with Image.open(filepath) as img:
                    width, height = img.size
                    if width != height:
                        output_list.append(f"{filename}: {width}x{height} (Ratio: {width/height:.2f})")
            except Exception as e:
                output_list.append(f"{filename}: Error - {str(e)}")

if output_list:
    print("\n".join(output_list))
else:
    print("All images are 1:1 or no images found.")
