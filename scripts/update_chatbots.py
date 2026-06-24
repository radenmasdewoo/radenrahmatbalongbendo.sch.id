import os

def update_file(filepath):
    print(f"Checking {filepath}...")
    with open(filepath, 'r') as f:
        content = f.read()

    # Define markers for different versions of the chatbot
    markers = [
        ('<!-- ===== CHATBOT WIDGET - BP3NU RADEN RAHMAT ===== -->', '<!-- ===== AKHIR CHATBOT WIDGET ===== -->'),
        ('<!-- ===== CHATBOT WIDGET - BP3NU RADEN RAHMAT BALONGBENDO ===== -->', '<!-- ===== AKHIR CHATBOT WIDGET ===== -->'),
        ('<!-- Chatbot Widget -->', '<!-- End Chatbot Widget -->'),
        ('<!-- WIDGET CHAT AI -->', '<!-- AKHIR WIDGET CHAT AI -->'),
        ('<div id="rr-chat-fab"', '</script>\n    <!-- ===== AKHIR CHATBOT WIDGET ===== -->'), # Flexible match
    ]

    new_widget = """<!-- ===== CHATBOT WIDGET INTEGRATION (BP3NU RADEN RAHMAT) ===== -->
    <div id="rr-chat-widget-container"></div>
    <script>
        fetch('/chat-widget.html')
            .then(res => {
                if (!res.ok) throw new Error('Widget Error');
                return res.text();
            })
            .then(html => {
                if (html.includes('Sedang Pemeliharaan') || html.includes('Error Status: 404') || html.includes('Cloudflare')) return;
                const container = document.getElementById('rr-chat-widget-container');
                container.innerHTML = html;
                const scripts = container.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            }).catch(err => console.log('Chatbot failed:', err.message));
    </script>
    <!-- ===== AKHIR CHATBOT WIDGET ===== -->"""

    updated = False
    for start, end in markers:
        if start in content and end in content:
            start_idx = content.find(start)
            end_idx = content.find(end) + len(end)
            content = content[:start_idx] + new_widget + content[end_idx:]
            updated = True
            break
    
    # Special case for files that have the elements but maybe not the markers
    if not updated and ('rr-chat-window' in content or 'chat-widget-mi' in content or 'chat-widget-smp' in content):
        # If we can't find markers but find the IDs, we'll append to the end of body if possible
        if '</body>' in content:
            # Try to identify and remove existing fragments if we can
            # This is riskier so we'll just append it for now, 
            # or try to find common ID locations
            content = content.replace('</body>', new_widget + '\n</body>')
            updated = True

    if updated:
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated {filepath}")
    else:
        print(f"No chatbot found in {filepath}")

# Crawl files
root = '/mnt/webdata/htdocs'
exclude_dirs = ['rdmmi', '.git', 'api', 'assets', 'js', 'scripts', 'vendor', 'uploads']

for dirpath, dirnames, filenames in os.walk(root):
    # Filter directories in place
    dirnames[:] = [d for d in dirnames if d not in exclude_dirs]
    
    for filename in filenames:
        if filename.endswith('.html') and filename != 'chat-widget.html':
            update_file(os.path.join(dirpath, filename))
