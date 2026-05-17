import os
from PIL import Image, ImageOps

source_dir = 'img/recipe'
target_dir = 'img/1_to_1'

if not os.path.exists(target_dir):
    os.makedirs(target_dir)

def get_padding_color(img):
    # Resmin kenarlarındaki renklerin ortalamasını alarak daha 'akıllı' bir dolgu rengi seçer
    # Basitlik için beyaz kullanabiliriz, ancak kenar renkleri daha profesyonel durur.
    # Burada varsayılan olarak beyaz (255, 255, 255) döndüreceğiz.
    return (255, 255, 255)

processed_count = 0

for filename in os.listdir(source_dir):
    if filename.lower().endswith(('.png', '.jpg', '.jpeg', '.avif', '.webp')):
        source_path = os.path.join(source_dir, filename)
        target_path = os.path.join(target_dir, filename)
        
        try:
            with Image.open(source_path) as img:
                width, height = img.size
                
                if width == height:
                    # Zaten kare ise direkt kopyala
                    img.save(target_path)
                    continue
                
                # Kare tuval boyutu
                new_size = max(width, height)
                
                # Resmin formatına göre uygun mod seçimi (transparanlık varsa RGBA)
                mode = img.mode
                if mode not in ('RGB', 'RGBA'):
                    img = img.convert('RGB')
                    mode = 'RGB'
                
                # Dolgu rengi (resimlerin çoğu yemek resmi olduğu için beyaz arka plan genellikle daha iyi durur)
                fill_color = (255, 255, 255)
                if mode == 'RGBA':
                    fill_color = (255, 255, 255, 0)
                
                # Yeni kare resim oluştur
                new_img = Image.new(mode, (new_size, new_size), fill_color)
                
                # Orijinal resmi ortaya yerleştir
                left = (new_size - width) // 2
                top = (new_size - height) // 2
                new_img.paste(img, (left, top))
                
                # Kaydet
                new_img.save(target_path)
                processed_count += 1
                print(f"Processed: {filename} -> {new_size}x{new_size}")
                
        except Exception as e:
            print(f"Error processing {filename}: {e}")

print(f"\nİşlem tamamlandı. Toplam {processed_count} resim kare hale getirildi ve '{target_dir}' klasörüne kaydedildi.")
