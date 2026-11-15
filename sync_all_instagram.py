#!/usr/bin/env python3
"""
sync_all_instagram.py - Surgical injection of Instagram posts into:
  1. /mnt/webdata/htdocs/berita.html          (ROOT - #news-container)
  2. /mnt/webdata/htdocs/smp/index.html       (SMP - .ig-feed-container)
  3. /mnt/webdata/htdocs/mi/index.html        (MI - .ig-feed-container mt-4)
Only replaces content inside the target container. Preserves entire header/footer/CSS/JS structure.
"""
import json
import sys

# ============ Configuration per target ============
TARGETS = [
    {
        "name": "ROOT berita.html",
        "html_path": "/mnt/webdata/htdocs/berita.html",
        "json_path": "/mnt/webdata/htdocs/api/news.json",
        "start_marker": 'id="news-container"',
        "end_marker": "  </main>",
        "max_posts": 15,
    },
    {
        "name": "SMP index.html",
        "html_path": "/mnt/webdata/htdocs/smp/index.html",
        "json_path": "/mnt/webdata/htdocs/smp/api/news.json",
        "start_marker": '<div class="ig-feed-container">',
        "end_marker": "</section>",
        "max_posts": 5,
    },
    {
        "name": "MI index.html",
        "html_path": "/mnt/webdata/htdocs/mi/index.html",
        "json_path": "/mnt/webdata/htdocs/mi/api/news_mi.json",
        "start_marker": '<div class="ig-feed-container mt-4">',
        "end_marker": "</section>",
        "max_posts": 5,
    },
]

IG_CACHE_BASE = "/api/ig_cache"


def C(text):
    """Escape HTML."""
    if text is None:
        return ""
    return (str(text)
            .replace("&", "&amp;")
            .replace("<", "&lt;")
            .replace(">", "&gt;")
            .replace('"', "&quot;")
            .replace("'", "&#39;"))


# Card template using @@PLACEHOLDER@@ to avoid format-string conflicts
CARD_TPL = """<div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <a href="@@LINK@@" target="_blank" rel="noopener" class="text-decoration-none">
                                <div class="position-relative overflow-hidden">
                                    @@MEDIA@@
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="card-title fw-bold text-dark" style="font-size:0.9rem; line-height:1.3; height:3.2em; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">@@TITLE@@</h6>
                                    <p class="card-text text-muted small" style="font-size:0.8rem; line-height:1.4; height:4.2em; overflow:hidden; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical;">@@EXCERPT@@</p>
                                </div>
                                <div class="card-footer bg-transparent border-0 p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> @@DATE@@</small>
                                        <small class="text-muted"><i class="fab fa-instagram me-1"></i> @@AUTHOR_SHORT@@</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>"""


def build_card(item, author_short):
    shortcode = C(item.get("shortcode", ""))
    title = C(item.get("title", "Tanpa Judul").strip())
    excerpt = C(item.get("excerpt", "").strip())
    link = C(item.get("link", "#"))
    date = C(item.get("date", ""))
    author = C(item.get("author", ""))
    img = item.get("img", "")
    is_video = item.get("is_video", False)
    caption = C(item.get("caption", ""))

    # Image path
    if not img or not str(img).startswith(IG_CACHE_BASE + "/"):
        img_path = IG_CACHE_BASE + "/" + shortcode + ".jpg"
    else:
        img_path = C(img)

    # Media block
    video_url = item.get("video", "")
    link = item.get("link", "")
    if is_video and video_url:
        # Instagram CDN blocks direct hotlinking from external domains
        # Show thumbnail with play button overlay, link to Instagram post
        media = (
            '<a href="{link}" target="_blank" rel="noopener" class="d-block position-relative">'
            '<img src="{img_path}" alt="Video Preview" loading="lazy"'
            ' style="width:100%;height:200px;object-fit:cover;">'
            '<div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center pointer-events-none">'
            '<i class="fas fa-play-circle text-white" style="font-size:2.5rem;"></i>'
            '</div>'
            '</a>'
        ).format(link=C(link), img_path=img_path)
    else:
        media = (
            '<img src="{img_path}" alt="News Media" loading="lazy"'
            ' style="width:100%;height:200px;object-fit:cover;"'
            ' onerror="this.src=\'https://via.placeholder.com/400x250/006837/ffffff?text=Image\';this.onerror=null;">'
        ).format(img_path=img_path)

    card = CARD_TPL
    card = card.replace("@@LINK@@", link)
    card = card.replace("@@MEDIA@@", media)
    card = card.replace("@@TITLE@@", title)
    card = card.replace("@@EXCERPT@@", excerpt)
    card = card.replace("@@DATE@@", date)
    card = card.replace("@@AUTHOR_SHORT@@", author_short)
    return card


def inject(target):
    html_path = target["html_path"]
    json_path = target["json_path"]
    start_marker = target["start_marker"]
    end_marker = target["end_marker"]
    max_posts = target["max_posts"]

    # Read HTML
    with open(html_path, "r", encoding="utf-8") as f:
        html = f.read()

    # Read JSON
    with open(json_path, "r", encoding="utf-8") as f:
        news = json.load(f)

    if not isinstance(news, list):
        print(f"ERROR [{target['name']}]: {json_path} is not a list", file=sys.stderr)
        return False

    # Find markers
    start_idx = html.find(start_marker)
    if start_idx < 0:
        print(f"ERROR [{target['name']}]: start marker not found", file=sys.stderr)
        return False

    end_idx = html.find(end_marker, start_idx)
    if end_idx < 0:
        print(f"ERROR [{target['name']}]: end marker not found", file=sys.stderr)
        return False

    # Build cards
    cards = []
    for item in news[:max_posts]:
        author_short = item.get("author", "").replace(".official", "").replace("balongbendo", "bendo")
        cards.append(build_card(item, author_short))

    prefix = html[:start_idx + len(start_marker)]
    suffix = html[end_idx:]

    new_content = prefix + "\n" + "\n".join(cards) + "\n" + suffix

    with open(html_path, "w", encoding="utf-8") as f:
        f.write(new_content)

    print(f"OK [{target['name']}]: {len(cards)} posts injected. Structure preserved.")
    return True


def main():
    results = []
    for t in TARGETS:
        results.append(inject(t))

    if all(results):
        print("\nSemua target berhasil di-update secara surgical (struktur aman).")
    else:
        print("\nBeberapa target gagal. Periksa log di atas.")
        sys.exit(1)


if __name__ == "__main__":
    main()
