import os
import re
import urllib.parse

html_path = '/mnt/webdata/htdocs/mi/indexalumni.html'
with open(html_path, 'r', encoding='utf-8') as f:
    content = f.read()

images = [
    "WhatsApp Image 2026-05-13 at 17.47.27-fotor-20260514123643.png",
    "WhatsApp Image 2026-05-13 at 17.47.28 (1)-fotor-20260514122631.png",
    "WhatsApp Image 2026-05-13 at 17.47.28 (2)-fotor-2026051412296.png",
    "WhatsApp Image 2026-05-13 at 17.47.28-fotor-20260514123022.png",
    "WhatsApp Image 2026-05-13 at 17.47.29 (1)-fotor-20260514122422.png",
    "WhatsApp Image 2026-05-13 at 17.47.29 (2)-fotor-2026051412260.png",
    "WhatsApp Image 2026-05-13 at 17.47.29-fotor-20260514122530.png",
    "WhatsApp Image 2026-05-13 at 17.47.30 (1)-fotor-20260514122458.png",
    "WhatsApp Image 2026-05-13 at 17.47.30-fotor-20260514122954.png",
    "WhatsApp Image 2026-05-13 at 17.47.31 (1)-fotor-20260514122928.png",
    "WhatsApp Image 2026-05-13 at 17.47.31 (2)-fotor-20260514122353.png",
    "WhatsApp Image 2026-05-13 at 17.47.31-fotor-20260514122322.png",
    "WhatsApp Image 2026-05-13 at 17.47.32 (1)-fotor-20260514122234.png",
    "WhatsApp Image 2026-05-13 at 17.47.32-fotor-20260514123237.png",
    "WhatsApp Image 2026-05-13 at 17.47.33 (1)-fotor-20260514123341.png",
    "WhatsApp Image 2026-05-13 at 17.47.33-fotor-20260514123621.png",
    "WhatsApp Image 2026-05-13 at 17.47.34 (1)-fotor-2026051412336.png",
    "WhatsApp Image 2026-05-13 at 17.47.34 (2)-fotor-20260514122842.png",
    "WhatsApp Image 2026-05-13 at 17.47.34-fotor-2026051412325.png",
    "WhatsApp Image 2026-05-13 at 17.47.35-fotor-20260514123137.png",
    "WhatsApp Image 2026-05-13 at 17.47.36 (1)-fotor-20260514123552.png",
    "WhatsApp Image 2026-05-13 at 17.47.36-fotor-20260514123512.png",
    "WhatsApp Image 2026-05-13 at 17.47.37 (1)-fotor-20260514122811.png",
    "WhatsApp Image 2026-05-13 at 17.47.37-fotor-20260514123417.png",
    "WhatsApp Image 2026-05-13 at 17.47.38 (1)-fotor-20260514123110.png",
    "WhatsApp Image 2026-05-13 at 17.47.38-fotor-20260514123047.png",
    "WhatsApp Image 2026-05-13 at 17.47.39 (1)-fotor-20260514123440.png",
    "WhatsApp Image 2026-05-13 at 17.47.39-fotor-20260514122711.png"
]

encoded_images = [urllib.parse.quote(img) for img in images]

pattern = r'src="uploads/alumni/(boy1\.png|girl1\.png|boy2\.png|girl2\.png)"'

count = 0
def replacer(match):
    global count
    img_name = encoded_images[count % len(encoded_images)]
    count += 1
    return f'src="uploads/alumni/{img_name}"'

new_content = re.sub(pattern, replacer, content)

with open(html_path, 'w', encoding='utf-8') as f:
    f.write(new_content)
print(f"Replaced {count} instances.")
