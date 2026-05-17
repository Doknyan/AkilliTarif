import os
import urllib.request
import re
import ssl

# Bypass SSL verification for convenience (not for production, but okay for this task)
ssl._create_default_https_context = ssl._create_unverified_context

urls = {
    65: "https://wellmadebykiley.com/blog/strawberry-cinnamon-roll-bites",
    66: "https://wellmadebykiley.com/blog/brown-butter-blueberry-fritter-cake",
    67: "https://wellmadebykiley.com/blog/blueberry-pie-donut-bars",
    68: "https://wellmadebykiley.com/blog/brown-butter-banana-pudding-cupcakes",
    69: "https://wellmadebykiley.com/blog/peanut-butter-smores-oatmeal-cookie-skillet-with-brown-butter",
    70: "https://wellmadebykiley.com/blog/funfetti-cinnamon-rolls",
    71: "https://wellmadebykiley.com/blog/blueberry-cinnamon-roll-bread-pudding",
    72: "https://wellmadebykiley.com/blog/old-fashioned-chocolate-chip-banana-bread-donuts",
    73: "https://wellmadebykiley.com/blog/blueberry-crumble-cheesecake-bars",
    74: "https://wellmadebykiley.com/blog/blueberry-fritters-with-brown-butter-lemon-glaze",
    75: "https://wellmadebykiley.com/blog/lemon-curd-pistachio-cake",
    76: "https://wellmadebykiley.com/blog/cinnamon-roll-croissants",
    77: "https://wellmadebykiley.com/blog/crinkled-brown-butter-chocolate-chip-cookies",
    78: "https://wellmadebykiley.com/blog/bakery-style-blueberry-cream-cheese-muffins",
    79: "https://wellmadebykiley.com/blog/chocolate-chip-bread-pudding-with-butter-rum-sauce",
    80: "https://wellmadebykiley.com/blog/flower-donuts",
    81: "https://wellmadebykiley.com/blog/cheesecake-stuffed-blueberry-crumble-cookie-skillet-with-brown-butter",
    82: "https://wellmadebykiley.com/blog/carrot-cake-cinnamon-rolls",
    83: "https://wellmadebykiley.com/blog/self-saucing-banana-pudding-cake",
    84: "https://wellmadebykiley.com/blog/carrot-cake-sticky-toffee-pudding"
}

output_dir = "img/recipe"
if not os.path.exists(output_dir):
    os.makedirs(output_dir)

results = []

for recipe_id, url in urls.items():
    try:
        print(f"Fetching {url}...")
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req) as response:
            html = response.read().decode('utf-8')
            
            # Find og:image
            match = re.search(r'<meta property="og:image" content="([^"]+)"', html)
            if match:
                img_url = match.group(1)
                # Clean URL (Squarespace often adds ?format=...)
                clean_img_url = img_url.split('?')[0]
                ext = os.path.splitext(clean_img_url)[1]
                if not ext:
                    ext = ".jpg"
                
                filename = f"recipe_{recipe_id}{ext}"
                filepath = os.path.join(output_dir, filename)
                
                print(f"Downloading {img_url} to {filepath}...")
                urllib.request.urlretrieve(img_url, filepath)
                results.append((recipe_id, filename))
            else:
                print(f"No image found for ID {recipe_id}")
    except Exception as e:
        print(f"Error for ID {recipe_id}: {e}")

print("\nResults for database update:")
for recipe_id, filename in results:
    print(f"UPDATE recipes SET image_path = '{filename}' WHERE recipe_id = {recipe_id};")
