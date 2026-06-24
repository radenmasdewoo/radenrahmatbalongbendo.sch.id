import re
import os
import json
import requests
import time

# AI API Keys
GEMINI_API_KEY = "AIzaSyBQ-UMRsqvWol4Cy3q0Nu4R7D4ck4Nmsrs"
GROQ_API_KEY = "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"

BERITA_PATH = "/mnt/webdata/htdocs/berita.html"
INDEX_PATH = "/mnt/webdata/htdocs/index.html"
NEWS_JSON_PATH = "/mnt/webdata/htdocs/api/news.json"

def get_word_count(text):
    # Remove HTML tags for word count
    clean_text = re.sub('<[^<]+?>', '', text)
    return len(clean_text.split())

def expand_article(title, current_content):
    print(f"Expanding article: {title}")
    prompt = f"""
    Tolong kembangkan artikel berita sekolah di bawah ini menjadi 300-500 kata dalam Bahasa Indonesia yang formal, inspiratif, dan menarik.
    Artikel harus berkaitan dengan kegiatan di BP3NU Raden Rahmat Balongbendo (MI/SMP/SMA).
    
    Judul: {title}
    Konten awal: {current_content}
    
    Pastikan menggunakan bahasa yang baik dan benar (PUEBI), serta tonjolkan nilai-nilai karakter, keislaman (Ahlussunnah wal Jamaah), dan prestasi siswa.
    Kembangkan narasi yang mendalam tentang hikmah dan manfaat kegiatan tersebut bagi masa depan siswa.
    Berikan output dalam format HTML sederhana (hanya tag <p>).
    Jangan sertakan judul lagi di dalam konten.
    """
    
    url = "https://api.groq.com/openai/v1/chat/completions"
    headers = {
        "Authorization": f"Bearer {GROQ_API_KEY}",
        "Content-Type": "application/json"
    }
    payload = {
        "model": "llama-3.1-8b-instant",
        "messages": [
            {"role": "system", "content": "Anda adalah asisten penulis konten kreatif profesional yang ahli dalam pendidikan Islam."},
            {"role": "user", "content": prompt}
        ],
        "temperature": 0.7,
        "max_tokens": 1024
    }
    
    for attempt in range(3):
        try:
            response = requests.post(url, json=payload, headers=headers, timeout=40)
            if response.status_code == 429:
                print(f"Rate limit hit, waiting 10 seconds (attempt {attempt+1}/3)...")
                time.sleep(10)
                continue
                
            response.raise_for_status()
            data = response.json()
            if 'choices' in data and len(data['choices']) > 0:
                expanded_text = data['choices'][0]['message']['content']
                # Clean up any markdown code blocks
                expanded_text = re.sub(r'```html|```', '', expanded_text).strip()
                return expanded_text
            return current_content
        except Exception as e:
            print(f"Error calling Groq API (attempt {attempt+1}/3): {e}")
            time.sleep(5)
            
    return current_content

def process_berita():
    with open(BERITA_PATH, 'r', encoding='utf-8') as f:
        content = f.read()

    # Regex to find articles
    article_pattern = re.compile(r'(<article class="full-article-card".*?>(.*?)</article>)', re.DOTALL)
    articles = article_pattern.findall(content)
    
    updated_content = content
    new_news_list = []

    for full_match, inner_content in articles:
        # Extract title
        title_match = re.search(r'<h2.*?>\s*(.*?)\s*</h2>', inner_content, re.DOTALL)
        title = title_match.group(1).strip() if title_match else "Tanpa Judul"
        # Clean title from extra whitespace and newlines
        title = re.sub(r'\s+', ' ', title).strip()
        
        # Extract image
        img_match = re.search(r'<img src="(.*?)"', inner_content)
        img_src = img_match.group(1) if img_match else ""
        
        # Extract link (Instagram)
        link_match = re.search(r'<a href="(https://www\.instagram\.com/p/.*?/)"', inner_content)
        link = link_match.group(1) if link_match else ""
        
        # Extract date
        date_match = re.search(r'far fa-calendar-alt.*?</i>\s*(.*?)</span>', inner_content, re.DOTALL)
        date = date_match.group(1).strip() if date_match else ""

        # Extract source content
        content_div_pattern = re.compile(r'(<div class="article-content".*?>(.*?)</div>)', re.DOTALL)
        content_match = content_div_pattern.search(inner_content)
        
        if content_match:
            full_div = content_match.group(1)
            article_body = content_match.group(2).strip()
            word_count = get_word_count(article_body)
            
            if word_count < 300:
                expanded_body = expand_article(title, article_body)
                new_div = f'<div class="article-content" style="font-size: 1rem; color: #4b5563; line-height: 1.8; text-align: justify;">\n{expanded_body}\n                        </div>'
                
                # Update in the temp full article match
                new_full_match = full_match.replace(full_div, new_div)
                # Update in the main content
                updated_content = updated_content.replace(full_match, new_full_match)
                article_body = expanded_body
                # Delay to avoid API rate limits
                time.sleep(3)
            
            # Prepare for news.json and index.html
            excerpt = re.sub('<[^<]+?>', '', article_body).strip()[:180].replace('\n', ' ') + "..."
            new_news_list.append({
                "title": title,
                "excerpt": excerpt,
                "img": img_src,
                "link": link,
                "date": date,
                "author": "Asisten Raden Rahmat",
                "timestamp": int(time.time())
            })

    # Save updated berita.html
    with open(BERITA_PATH, 'w', encoding='utf-8') as f:
        f.write(updated_content)
    
    return new_news_list

def generate_index_card(news):
    return f"""
                    <div class="news-card">
                        <div class="news-img-box">
                             <img src="{news['img']}" alt="News Media" loading="lazy" style="width:100%;height:140px;object-fit:cover;">
                        </div>
                        <div class="news-details">
                            <div class="news-meta">
                                <span><i class="far fa-calendar-alt"></i> {news['date']}</span>
                                <span><i class="far fa-user"></i> @{news['author']}</span>
                            </div>
                            <a href="{news['link']}" target="_blank" rel="noopener" class="news-title">{news['title']}</a>
                            <p class="news-excerpt">{news['excerpt']}</p>
                            <a href="{news['link']}" target="_blank" rel="noopener" style="color: var(--accent); font-weight: 700;">Baca Selengkapnya <i class="fas fa-chevron-right"></i></a>
                        </div>
                    </div>"""

def sync_to_index(new_news_list):
    with open(INDEX_PATH, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Take latest 6 articles
    news_html = "\n".join([generate_index_card(n) for n in new_news_list[:6]])
    
    pattern = re.compile(r'(<div id="news-container".*?>)(.*?)(</div>)', re.DOTALL)
    if pattern.search(content):
        updated_content = pattern.sub(rf'\1\n{news_html}\n                    \3', content)
        with open(INDEX_PATH, 'w', encoding='utf-8') as f:
            f.write(updated_content)
        print("Updated index.html.")
    else:
        print("news-container not found in index.html")

def sync_to_json(new_news_list):
    if os.path.exists(NEWS_JSON_PATH):
        try:
            with open(NEWS_JSON_PATH, 'r', encoding='utf-8') as f:
                existing_news = json.load(f)
        except:
            existing_news = []
    else:
        existing_news = []
    
    final_news = []
    seen_links = set()
    
    for news in new_news_list:
        final_news.append(news)
        if news['link']: seen_links.add(news['link'])
            
    for old in existing_news:
        if old.get('link') not in seen_links:
            final_news.append(old)
            
    with open(NEWS_JSON_PATH, 'w', encoding='utf-8') as f:
        json.dump(final_news, f, indent=4)

if __name__ == "__main__":
    print("Starting news synchronization and AI expansion...")
    news_list = process_berita()
    sync_to_json(news_list)
    # sync_to_index(news_list) # Disabled: index.html dynamically loads news from news.json, direct injection breaks layout.
    print("Process completed successfully!")
