import urllib.request
import json

url = "http://127.0.0.1:8080/api/ollama_proxy.php"
data = json.dumps({
    "system_instruction": "You are a helpful assistant.",
    "messages": [
        {"role": "user", "content": "Hello"}
    ]
}).encode('utf-8')

req = urllib.request.Request(url, data=data, headers={'Content-Type': 'application/json'}, method='POST')

try:
    with urllib.request.urlopen(req) as response:
        print("Status:", response.status)
        print("Response:", response.read().decode('utf-8'))
except urllib.error.URLError as e:
    print("Error:", e.reason)
    if hasattr(e, 'read'):
        print("Body:", e.read().decode('utf-8'))
