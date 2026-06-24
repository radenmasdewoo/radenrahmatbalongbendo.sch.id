import re

html_path = '/mnt/webdata/htdocs/mi/indexalumni.html'
with open(html_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update Teacher Quote tags
content = content.replace(
    '<h3 class="fw-bold">PESAN DARI GURU</h3>',
    '<h3 class="fw-bold" id="teacher-quote-title">PESAN DARI GURU</h3>'
)
content = content.replace(
    '<p class="fs-5 mb-0">"Terbanglah tinggi, alumni 2026! Kejarlah mimpi kalian, tapi jangan lupakan akar kalian di MI R Rahmat. Kami bangga!" - <strong>Wali Kelas 6</strong></p>',
    '<p class="fs-5 mb-0" id="teacher-quote-text">"Terbanglah tinggi, alumni 2026! Kejarlah mimpi kalian, tapi jangan lupakan akar kalian di MI R Rahmat. Kami bangga!" - <strong>Wali Kelas 6</strong></p>'
)

# 2. Extract and replace the gallery container
start_marker = '<div class="row g-4 mt-2">'
end_marker = '</div>\n            </div>\n        </section>\n\n        <section class="py-4">'

start_idx = content.find(start_marker)
end_idx = content.find(end_marker, start_idx)

if start_idx != -1 and end_idx != -1:
    before = content[:start_idx]
    after = content[end_idx:]
    
    gallery_html = """<div class="row g-4 mt-2" id="alumni-gallery-container">
                    <!-- Cards akan di-generate otomatis oleh script di bawah -->
                </div>"""
    
    content = before + gallery_html + after

# 3. Add script tag at the bottom before </body>
script_injection = """
    <!-- Data Configuration -->
    <script src="alumni_data.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Update Teacher Quote
            if(teacherData) {
                document.getElementById('teacher-quote-title').innerHTML = teacherData.title;
                document.getElementById('teacher-quote-text').innerHTML = teacherData.quote;
            }

            // Render Alumni Cards
            const container = document.getElementById('alumni-gallery-container');
            if(container && typeof alumniData !== 'undefined') {
                container.innerHTML = '';
                alumniData.forEach(student => {
                    const card = document.createElement('div');
                    card.className = 'col-md-3 col-sm-6';
                    
                    const styleAttr = student.style ? `style="${student.style}"` : '';
                    
                    card.innerHTML = `
                        <div class="alumni-card">
                            <img src="${student.photo}" alt="${student.name}" class="student-photo" ${styleAttr}>
                            <h5 class="student-name">${student.name}</h5>
                            <p class="student-quote">${student.quote}</p>
                        </div>
                    `;
                    container.appendChild(card);
                });
            }
        });
    </script>
</body>"""

content = content.replace('</body>', script_injection)

with open(html_path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Successfully injected JS and cleared hardcoded cards.")
