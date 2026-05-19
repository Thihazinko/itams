"""
Crop tall full-page screenshots down to the top 1000px so they fit slide layouts.
Safe to re-run; only crops images taller than the target.
"""
from pathlib import Path
from PIL import Image

SRC = Path(__file__).resolve().parent / "screenshots"
TARGET_H = 1000

count = 0
for png in sorted(SRC.glob("*.png")):
    img = Image.open(png)
    w, h = img.size
    if h <= TARGET_H:
        continue
    img.crop((0, 0, w, TARGET_H)).save(png)
    count += 1
    print(f"  cropped {png.name}: {w}x{h} -> {w}x{TARGET_H}")

print(f"Done. Cropped {count} file(s).")
