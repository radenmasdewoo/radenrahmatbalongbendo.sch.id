# Implementation Plan - Chatbot Cloudflare Maintenance Fix

## Understanding the Issue
1. The user reported that a "Pemeliharaan/Maintenance" page containing a Memory Game appears at the bottom of the page (`smp/index.html`).
2. This happens because the fetch operation (`fetch('/chat-widget.html')`) fails or is intercepted by Cloudflare Workers (as per user's prompt).
3. The Cloudflare Worker returns a 404 HTML Maintenance Page.
4. The JavaScript immediately puts the fetched HTML into `<div id="rr-chat-widget-container"></div>` using `.innerHTML` without validating if the response is actually valid HTML or if the HTTP status code is 200.
5. And because `API TOKEN.txt` is updated, the `api/ollama_proxy.php` runs correctly now but we need to ensure the JS logic gracefully fails.

## Steps
1. **Update `scripts/update_chatbots.py`:**
   Modify the Python script that injects the chatbot widget across all files. The new widget payload will check for `res.ok` and ensure the `html` content does not contain error strings (like "Sedang Pemeliharaan" or "Attention Required! | Cloudflare") before inserting it into the DOM.
2. **Execute Python Script:**
   Run `python3 /mnt/webdata/htdocs/scripts/update_chatbots.py` to fix the JavaScript integration across all (`.html`) files in the project.
3. **Notify User:**
   Prompt the user to review the updated fallback handler on all `index.html` files.
